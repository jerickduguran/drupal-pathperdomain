<?php

namespace Drupal\pathperdomain\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class PathPerDomainProcessor implements InboundPathProcessorInterface {

  /**
   * {@inheritDoc}
   */
  public function processInbound($path, Request $request) {
    $domainCurrent = \Drupal::service('domain.negotiator')->getActiveDomain();
    $domainPathLoader = \Drupal::service('pathperdomain.loader');
    $languageManager = \Drupal::languageManager();

    $properties = [
      'domain_id' => $domainCurrent->id(),
      'alias' => $path,
      'language' => $languageManager->getCurrentLanguage()->getId(),
    ];

    $domainPathEntities = $domainPathLoader->loadByProperties($properties);

    if (empty($domainPathEntities)) return $path;

    $domainPathEntity = reset($domainPathEntities);

    return $domainPathEntity->getSource();
  }
}
