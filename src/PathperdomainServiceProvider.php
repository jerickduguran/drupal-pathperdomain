<?php

namespace Drupal\pathperdomain;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase; 
 
class PathperdomainServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) { 
	  
    $definition = $container->getDefinition('redirect.route_normalizer_request_subscriber');
    $definition->setClass('Drupal\pathperdomain\EventSubscriber\RouteNormalizerRequestSubscriber');
	  
    $definition = $container->getDefinition('redirect_response_subscriber');
    $definition->setClass('Drupal\pathperdomain\EventSubscriber\RedirectResponseSubscriber');
	
  }
}