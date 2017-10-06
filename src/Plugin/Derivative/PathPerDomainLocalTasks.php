<?php

/**
 * @file
 * Contains \Drupal\pathperdomain\Plugin\Derivative\PathPerDomainLocalTasks.
 */

namespace Drupal\pathperdomain\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Defines dynamic domain path view local tasks.
 */
class PathPerDomainLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The local task manager
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskManager;

  /**
   * Creates an PathPerDomainLocalTasks object.
   *
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $local_task_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(LocalTaskManagerInterface $local_task_manager, TranslationInterface $string_translation) {
    $this->localTaskManager = $local_task_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.menu.local_task'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $pathperdomain_helper = \Drupal::service('pathperdomain.helper');
    $enabled_entity_types = $pathperdomain_helper->getConfiguredEntityTypes();

    if ($enabled_entity_types) {
      foreach ($enabled_entity_types as $entity_type) {
        $tasks = $this->localTaskManager->getLocalTasksForRoute("entity.$entity_type.canonical");
        if (!empty($tasks)) {
          $tasks = reset($tasks);

          $this->derivatives["pathperdomain.view.$entity_type"]['title'] = t('View');
          $this->derivatives["pathperdomain.view.$entity_type"]['route_name'] = "pathperdomain.view.$entity_type";
          $this->derivatives["pathperdomain.view.$entity_type"]['base_route'] = "pathperdomain.view.$entity_type";

          foreach ($tasks as $task_id => $task) {
            // don't include View tab with standart alias
            if ($task_id !== "entity.$entity_type.canonical") {
              $this->derivatives["pathperdomain.view.$task_id"]['title'] = $task->getTitle();
              $this->derivatives["pathperdomain.view.$task_id"]['weight'] = $task->getWeight();
              $this->derivatives["pathperdomain.view.$task_id"]['route_name'] = $task->getRouteName();
              $this->derivatives["pathperdomain.view.$task_id"]['base_route'] = "pathperdomain.view.$entity_type";
            }
          }
        }
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
