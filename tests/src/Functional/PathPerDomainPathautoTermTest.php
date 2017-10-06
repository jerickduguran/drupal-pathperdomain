<?php

namespace Drupal\Tests\pathperdomain\Functional;

use Drupal\Tests\pathperdomain\PathPerDomainTestHelperTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests the domain path with pathauto patterns for Node entity.
 *
 * @group pathperdomain
 */
class PathPerDomainPathautoTermTest extends PathPerDomainTestBase {

  use PathPerDomainTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test for pathauto pattern generation for each domain
   */
  public function testPathPerDomainPathautoTerm() {
    Vocabulary::create(['vid' => 'test'])->save();

    // create term
    $term = Term::create([
        'name' => $this->randomMachineName(8),
        'vid' => 'test'
      ]
    );
    $term->save();

    // create default pattern
    $pattern = $this->createPattern('taxonomy_term', '/pathauto/[term:tid]', -1);
    $this->addBundleCondition($pattern, 'taxonomy_term', 'test');
    $pattern->save();

    //create patterns for each domain
    foreach ($this->domains as $domain) {
      $pattern = $this->createPattern('taxonomy_term', '/term-' . $domain->id() .'/[term:tid]', -1);
      $this->addBundleCondition($pattern, 'bundles', 'test');

      // add domains settings
      $pattern->setThirdPartySetting(
        'pathperdomain',
        'domains',
        [$domain->id() => $domain->id()]
      );

      $pattern->save();

      // check each pattern for proper settings
      $this->drupalGet('admin/config/search/path/patterns/' . $pattern->id());
      $this->assertSession()->statusCodeEquals(200);
    }

    // check all patterns
    $this->drupalGet('admin/config/search/path/patterns');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [];
    // check for automatic alias
    $edit['path[0][pathauto]'] = 1;
    $this->drupalPostForm('taxonomy/term/' . $term->id() . '/edit', $edit, t('Save'));

    // check aliases for domains was generated
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
  }
}