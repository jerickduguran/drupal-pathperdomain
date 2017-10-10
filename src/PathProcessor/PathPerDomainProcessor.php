<?php

namespace Drupal\pathperdomain\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;

class PathPerDomainProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface  {

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
  
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL){ 
	  
	  if(isset($options['absolute']) && false == $options['absolute']){   
		$domainCurrent    = \Drupal::service('domain.negotiator')->getActiveDomain();
		$pathAliasHelper  = \Drupal::service('pathauto.alias_storage_helper');
		$languageManager  = \Drupal::languageManager();
 
		$targetPath			= '/pathperdomain/' . $domainCurrent->id() .$path; 
		$domainPathEntities = $pathAliasHelper->loadBySource($targetPath,$languageManager->getCurrentLanguage()->getId());
		
		if(!empty($domainPathEntities)){ 
		    return $domainPathEntities['alias'];
		}  
	  }   
	  
	  return $path;
  }
}
