<?php

/**
 * @file
 * Contains \Drupal\pathperdomain\Exception\PathPerDomainRedirectLoopException
 */

namespace Drupal\pathperdomain\Exception;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Exception for when a redirect loop is detected.
 */
class PathPerDomainRedirectLoopException extends \RuntimeException {

  /**
   * Formats a redirect loop exception message.
   *
   * @param string $path
   *   The path that results in a redirect loop.
   * @param int $rid
   *   The redirect ID that is involved in a loop.
   */
  public function __construct($domain_id, $entity_type, $id) {
    parent::__construct(new FormattableMarkup('Redirect loop identified for %entity_type nid:%id on domain:%domain', ['%entity_type' => $entity_type, '%id' => $id, '%domain' => $domain_id]));
  }

}
