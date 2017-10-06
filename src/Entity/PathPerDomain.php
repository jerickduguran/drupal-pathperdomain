<?php
/**
 * @file
 * Contains \Drupal\pathperdomain\Entity\PathPerDomain.
 */

namespace Drupal\pathperdomain\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\pathperdomain\PathPerDomainInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;

/**
 * Defines the PathPerDomain entity.
 *
 * @ingroup pathperdomain
 *
 *
 * @ContentEntityType(
 *   id = "pathperdomain",
 *   label = @Translation("Domain path entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\pathperdomain\Controller\PathPerDomainListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\pathperdomain\Form\PathPerDomainForm",
 *       "edit" = "Drupal\pathperdomain\Form\PathPerDomainForm",
 *       "delete" = "Drupal\pathperdomain\Form\PathPerDomainDeleteForm",
 *     },
 *     "access" = "Drupal\pathperdomain\PathPerDomainAccessControlHandler",
 *   },
 *   base_table = "pathperdomain",
 *   admin_permission = "administer domain path entity",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "domain_id" = "domain_id",
 *     "alias" = "alias",
 *     "language" = "language",
 *     "entity_type" = "entity_type",
 *     "entity_id" = "entity_id"
 *   },
 *   links = {
 *     "canonical" = "/pathperdomain/{pathperdomain}",
 *     "edit-form" = "/admin/config/pathperdomain/{pathperdomain}/edit",
 *     "delete-form" = "/admin/config/pathperdomain/{pathperdomain}/delete",
 *     "collection" = "/admin/config/pathperdomain"
 *   }
 * )
 */
class PathPerDomain extends ContentEntityBase  implements PathPerDomainInterface {

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Term entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Domain path entity.'))
      ->setReadOnly(TRUE);

    // Owner field of the Domain path.
    // Entity reference field, holds the reference to the domain object.
    // The view shows the title field of the domain.
    // The form presents a auto complete field for the domain title.
    $fields['domain_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Domain Id'))
      ->setDescription(t('The Title of the associated domain.'))
      ->setSetting('target_type', 'domain')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
        'weight' => -7,
      ])
      ->setRequired(TRUE);

    // Name field for the domain path.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['alias'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Alias'))
      ->setDescription(t('The alias of the Domain path entity.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setRequired(TRUE);

    $fields['language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The language of Domain path entity.'))
      ->setRequired(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type of the Domain path entity.'));

    // Owner field of the Domain path.
    // Entity reference field, holds the reference to the entity object.
    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity Id'))
      ->setDescription(t('The Id of the associated entity.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (\Drupal::moduleHandler()->moduleExists('path')) {
      \Drupal::service('path.alias_storage')->save($this->getSource() , $this->get('alias')->value, $this->get('language')->value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Ensure that all nodes deleted are removed from the search index.
    if (\Drupal::moduleHandler()->moduleExists('path')) {
      foreach ($entities as $entity) {
        $conditions = [
          'source' => $entity->getSource(),
          'langcode' => $entity->get('language')->value,
        ];
        \Drupal::service('path.alias_storage')->delete($conditions);
      }
    }
  }


  /**
   * Gets the source base URL.
   *
   * @return string
   */
  public function getUrl() {
    $url = '';
    $domain_id = $this->get('domain_id')->get(0)->getValue()['target_id'];
    $entity_type = $this->get('entity_type')->value;
    $entity_id = $this->get('entity_id')->get(0)->getValue()['target_id'];

    /*if (!$this->domain_id->entity->isDefault()) {
      $url = Url::fromRoute('pathperdomain.view', [
        'domain' => $domain_id,
        'entity_type' => $entity_type,
        'node' => $nid
      ]);
    }
    else {
      $url = $this->entity_id->entity->toUrl();
    }*/

    $url = Url::fromRoute("pathperdomain.view.$entity_type", [
      'domain' => $domain_id,
      $entity_type => $entity_id
    ]);

    return $url;
  }

  /**
   * Get system path for pathperdomain source
   *
   * @return string
   */
  public function getSource() {
    return '/pathperdomain/' . $this->get('domain_id')->target_id . '/' . $this->get('entity_type')->value . '/' . $this->get('entity_id')->target_id;
  }

  /**
   * Get system path for pathperdomain source
   *
   * @return string
   */
  public function getAlias() {
    return $this->get('alias')->value;
  }
}
