<?php

namespace Drupal\pathperdomain\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PathPerDomainSettingsForm.
 * @package Drupal\pathperdomain\Form
 * @ingroup pathperdomain
 */
class PathPerDomainSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'pathperdomain_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pathperdomain.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pathperdomain.settings');
    $enabled_entity_types = $config->get('entity_types');

    $form['entity_types'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Enabled entity types for domain paths'),
      '#tree' => TRUE,
    ];

    // Get all applicable entity types.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (is_subclass_of($entity_type->getClass(), FieldableEntityInterface::class) && $entity_type->hasLinkTemplate('canonical')) {
        $default_value = !empty($enabled_entity_types[$entity_type_id]) ? $enabled_entity_types[$entity_type_id] : NULL;
        $form['entity_types'][$entity_type_id] = [
          '#type' => 'checkbox',
          '#title' => $entity_type->getLabel(),
          '#default_value' => $default_value,
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('pathperdomain.settings')
      ->set('entity_types', $form_state->getValue('entity_types'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
