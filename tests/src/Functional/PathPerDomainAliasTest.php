<?php

namespace Drupal\Tests\pathperdomain\Functional;


/**
 * Tests the domain path aliases saving from edit form.
 *
 * @group pathperdomain
 */
class PathPerDomainAliasTest extends PathPerDomainTestBase {
  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   *
   */
  public function testPathPerDomainAliasesFill() {

    // Create alias.
    $edit = [];
    foreach ($this->domains as $domain) {
      $edit['path[0][pathperdomain][' . $domain->id() . ']'] = '/' . $this->randomMachineName(8);
    }

    $node1 = $this->drupalCreateNode();

    $edit['path[0][alias]'] = '/' . $this->randomMachineName(8);
    $this->drupalPostForm('node/' . $node1->id() . '/edit', $edit, t('Save'));

    $this->drupalGet('node/' . $node1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);


    // check the redirects from domains. in all cases must be the current main domain
    foreach ($this->domains as $domain) {
      $this->drupalGet('node/' . $node1->id());
      if ($domain->isDefault()) {
        $this->assertSession()
          ->addressEquals($edit['path[0][pathperdomain][' . $domain->id() . ']']);
      }
      else {
        $this->assertSession()
          ->addressNotEquals($edit['path[0][pathperdomain][' . $domain->id() . ']']);
      }
    }
  }
}
