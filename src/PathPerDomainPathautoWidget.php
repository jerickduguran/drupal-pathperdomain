<?php

namespace Drupal\pathperdomain;

use Drupal\pathauto\PathautoWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\domain\DomainLoaderInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\pathperdomain\PathPerDomainLoaderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the core path widget.
 */
class PathPerDomainPathautoWidget extends PathautoWidget implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\domain\DomainLoaderInterface
   */
  protected $domainLoaderManager;

  /**
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $accountManager;

  /**
   * @var \Drupal\pathauto\PathautoGeneratorInterface
   */
  protected $pathautoGeneratorManager;

  /**
   * @var \Drupal\pathperdomain\PathPerDomainLoaderInterface
   */
  protected $domainPathLoaderManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactoryManager;

  /**
   * PathPerDomainPathautoWidget constructor.
   *
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param array $third_party_settings
   * @param \Drupal\domain\DomainLoaderInterface $domain_loader_manager
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   * @param \Drupal\Core\Session\AccountInterface $account_manager
   * @param \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator_manager
   * @param \Drupal\pathperdomain\PathPerDomainLoaderInterface $pathperdomain_loader_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory_manager
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, DomainLoaderInterface $domain_loader_manager, AliasManagerInterface $alias_manager, AccountInterface $account_manager, PathautoGeneratorInterface $pathauto_generator_manager, PathPerDomainLoaderInterface $pathperdomain_loader_manager, ConfigFactoryInterface $config_factory_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->domainLoaderManager = $domain_loader_manager;
    $this->aliasManager = $alias_manager;
    $this->accountManager = $account_manager;
    $this->pathautoGeneratorManager = $pathauto_generator_manager;
    $this->domainPathLoaderManager = $pathperdomain_loader_manager;
    $this->configFactoryManager = $config_factory_manager->get('pathperdomain.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('domain.loader'),
      $container->get('path.alias_manager'),
      $container->get('current_user'),
      $container->get('pathauto.generator'),
      $container->get('pathperdomain.loader'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $config = $this->configFactoryManager;
    $enabled_entity_types = $config->get('entity_types');
    $enabled_entity_types = array_filter($enabled_entity_types);

    if (empty($enabled_entity_types[$entity_type])) {
      return $element;
    }

    //$pattern = $this->pathautoGeneratorManager->getPatternByEntity($entity);
    //if (empty($pattern)) {
      //return $element;
    //}

    if ($domains = $this->domainLoaderManager->loadMultipleSorted()) {
      $entity_id = $entity->id();
      $langcode = $entity->language()->getId();
      $show_delete = FALSE;
      $pathperdomain_loader = $this->domainPathLoaderManager;

      $element['pathperdomain'] = [
        '#tree' => TRUE,
        '#type' => 'details',
        '#title' => $this->t('Domain-specific paths'),
        '#group' => 'path_settings',
        '#weight' => 110,
        '#open' => TRUE,
        '#access' => $this->accountManager->hasPermission('edit domain path entity'),
      ];

      foreach ($domains as $domain_id => $domain) {
        $path = FALSE;
        $properties = [
          'entity_id' => $entity_id,
          'language' => $langcode,
          'domain_id' => $domain_id,
          'entity_type' => $entity_type,
        ];
        if ($entity_id && $pathperdomains = $pathperdomain_loader->loadByProperties($properties)) {
          foreach ($pathperdomains as $pathperdomain) {
            $path = $pathperdomain->get('alias')->value;
          }
        }

        $default = '';
        if ($path) {
          $show_delete = TRUE;
        }

        $element['pathperdomain']['pathperdomain_delete'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Delete domain-specific aliases'),
          '#default_value' => FALSE,
          '#access' => $show_delete,
        ];

        $element['pathperdomain'][$domain_id] = [
          '#type' => 'textfield',
          '#title' => Html::escape(rtrim($domain->getPath(), '/')),
          '#default_value' => $path ? $path : $default,
          //'#element_validate' => ['pathauto_pattern_validate'],
          '#access' => $this->accountManager->hasPermission('edit domain path entity'),
          '#states' => [
            'disabled' => [
              'input[name="path[' . $delta . '][pathauto]"]' => ['checked' => TRUE]
            ]
          ]
        ];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateFormElement(array &$element, FormStateInterface $form_state) {
    parent::validateFormElement($element, $form_state);
    if ($errors = $form_state->getErrors()) {
      foreach ($errors as $name => $error) {
        if ($name == 'path][0' && isset($element['alias'])) {
          $form_state->clearErrors();
          $form_state->setError($element['alias'], $error);
          break;
        }
      }
      return;
    }

    $entity = $form_state->getFormObject()->getEntity();
    $entity_id = $entity->id();
    $path_values = $form_state->getValue('path');
    $pathperdomain_values = isset($path_values[0]['pathperdomain']) ? $path_values[0]['pathperdomain'] : [];
    if (!empty($pathperdomain_values['pathperdomain_delete'])) {
      return;
    }
    unset($pathperdomain_values['pathperdomain_delete']);
    $alias = isset($path_values[0]['alias']) ? $path_values[0]['alias'] : NULL;
    $pathperdomain_loader = \Drupal::service('pathperdomain.loader');
    $domains = \Drupal::service('domain.loader')->loadMultipleSorted();
    // Make sure we don't duplicate anything.
    foreach ($pathperdomain_values as $domain_id => $path) {
      $key = ($domain_id == -1) ? 0 : $domain_id;
      if (!empty($path) && $path == $alias) {
        $form_state->setError($element['pathperdomain'][$key], t('Domain path "%path" matches the default path alias. You may leave the element blank.', ['%path' => $path]));
      }
      elseif (!empty($path)) {
        self::validatePathPerDomainValue($form_state, $pathperdomain_loader, $element, $domains, $domain_id, $entity_id, $path, $key);
      }
    }
  }

  /**
   * Validate handler for domain path value.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\pathperdomain\PathPerDomainLoaderInterface $pathperdomain_loader
   * @param $element
   * @param $domains
   * @param $domain_id
   * @param $entity_id
   * @param $path
   * @param $key
   */
  public static function validatePathPerDomainValue(FormStateInterface $form_state, PathPerDomainLoaderInterface $pathperdomain_loader, $element, $domains, $domain_id, $entity_id, $path, $key) {
    $path_value = rtrim(trim($path), " \\/");
    if ($path_value && $path_value[0] !== '/') {
      $form_state->setError($element['pathperdomain'][$key], t('Domain path "%path" needs to start with a slash.', ['%path' => $path]));
    }
    if (!empty($entity_id) && $pathperdomain_entity_data = $pathperdomain_loader->loadByProperties(['alias' => $path])) {
      foreach ($pathperdomain_entity_data as $pathperdomain_entity) {
        $check_entity_id = $pathperdomain_entity->get('entity_id')->target_id;
        $check_domain_id = $pathperdomain_entity->get('domain_id')->target_id;
        if ($check_entity_id != $entity_id
          && $check_domain_id == $key) {
          $pathperdomain = $domains[$domain_id]->getPath();
          $form_state->setError($element['pathperdomain'][$key], t('Domain path %path matches an existing domain path alias for %pathperdomain.', ['%path' => $path, '%pathperdomain' => $pathperdomain]));
        }
      }
    }
  }

}
