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
	    
	$domainCurrent  = \Drupal::service('domain.negotiator')->getActiveDomain();  
	$node_path		= \Drupal::service("path.alias_manager")->getPathByAlias($path);
	$args			= explode('/', $node_path); 
	$entity		    = \Drupal::entityTypeManager()->getStorage("node")->load(end($args));   

	if ($entity && $target_id = domain_source_get($entity)){
		$source = \Drupal::service("domain.loader")->load($target_id);  
		if($source->id() != $domainCurrent->id() && $domainCurrent->isDefault()){   
			$options["base_url"] = trim($source->getUrl(),"/");  
			//try using node
			$targetPath			= '/pathperdomain/'.$source->id().'/node/' . $entity->id();   
			$domainPathEntities = $this->getDomainPathEntities($targetPath);
			if(!empty($domainPathEntities)){   
				return $domainPathEntities['alias'];
			}   
			//try using path 
			$targetPath			= '/pathperdomain/' . $source->id().$path;   
			$domainPathEntities = $this->getDomainPathEntities($targetPath);
			if(!empty($domainPathEntities)){  
				return $domainPathEntities['alias'];
			}  
		} 
	}   	
	 
	 
	$domainPathEntities = $this->getDomainPathEntities($targetPath);
		
	if(!empty($domainPathEntities)){  
		return $domainPathEntities['alias'];
	}  

	// Cached URLs that have been processed by this outbound path 
	if ($bubbleable_metadata) {
	   $bubbleable_metadata 
	   ->addCacheContexts(['url.query_args:pathperdomain']);
	} 
	 
	return $path;
  }
  
  protected function getDomainPathEntities($path)
  { 
	  $pathAliasHelper  = \Drupal::service('pathauto.alias_storage_helper');
	  $languageManager  = \Drupal::languageManager(); 
	 
	  return $pathAliasHelper->loadBySource($path,$languageManager->getCurrentLanguage()->getId()); 
  }
}
