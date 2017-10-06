<?php

namespace Drupal\Tests\pathperdomain\Functional;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests the domain path creation API.
 *
 * @group pathperdomain
 */
class PathPerDomainCreateTest extends PathPerDomainTestBase {
  /**
   * Tests initial domain path creation.
   */
  public function testPathPerDomainCreate() {
    $admin = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domain path entity',
      'edit domain path entity',
    ]);
    $this->drupalLogin($admin);

    $list_href = 'admin/config/pathperdomain';

    $this->drupalGet($list_href);
    $this->assertSession()->statusCodeEquals(200);

    // No domain paths should exist.
    $this->domainPathTableIsEmpty();

    // Create a new domain programmatically.
    $pathperdomain_storage = \Drupal::service('pathperdomain.loader')->getStorage();
    $pathperdomain_entity = $pathperdomain_storage->create(['type' => 'pathperdomain']);
    $properties_map = [
      'alias' => '/test-alias',
      'domain_id' => 'http://test.com/',
      'language' => 'und',
      'entity_type' => 'node',
      'entity_id' => 1,
    ];
    foreach ($properties_map as $field => $value) {
      $pathperdomain_entity->set($field, $value);
    }

    foreach (array_keys($properties_map) as $key) {
      $property = $pathperdomain_entity->get($key);
      $this->assertTrue(isset($property), new FormattableMarkup('New $pathperdomain->@key property is set to default value: %value.', array('@key' => $key, '%value' => $property)));
    }
    $pathperdomain_entity->save();

    // Did it save correctly?
    $loaded_path_entity_data = \Drupal::service('pathperdomain.loader')->loadByProperties(['entity_id' => 1]);
    $loaded_path_entity = !empty($loaded_path_entity_data) ? reset($loaded_path_entity_data) : NULL;
    $default_id = !empty($loaded_path_entity) ? $loaded_path_entity->id() : NULL;
    $this->assertTrue(!empty($default_id), 'Domain path has been set.');

    // Does it load correctly?
    $new_pathperdomain = \Drupal::service('pathperdomain.loader')->load($default_id);
    $this->assertTrue($new_pathperdomain->id() == $pathperdomain_entity->id(), 'Domain path loaded properly.');

    // Has domain path id been set?
    //$this->assertTrue($new_pathperdomain->getDomainId(), 'Domain path id set properly.');

    // Has a UUID been set?
    $this->assertTrue($new_pathperdomain->uuid(), 'Entity UUID set properly.');

    $this->drupalGet($list_href);
    $this->assertSession()->statusCodeEquals(200);

    // Check that links are printed.
    $edit_href = "admin/config/pathperdomain/{$pathperdomain_entity->id()}/edit";
    $this->assertSession()->linkByHrefExists($edit_href, 0, 'Link found ' . $edit_href);
    $this->assertSession()->assertEscaped($pathperdomain_entity->id());
    $this->drupalGet($edit_href);
    $this->assertSession()->statusCodeEquals(200);

    // Delete the domain path.
    $pathperdomain_entity->delete();
    $pathperdomain_entity = \Drupal::service('pathperdomain.loader')->load($default_id, TRUE);
    $this->assertTrue(empty($pathperdomain_entity), 'Domain path record deleted.');

    // No domain path should exist.
    $this->domainPathTableIsEmpty();
    $this->drupalGet($list_href);
    $this->assertSession()->statusCodeEquals(200);
  }
}
