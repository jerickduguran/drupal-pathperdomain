<?php

namespace Drupal\pathperdomain\Routing;

use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class PathPerDomainRouteSubscriber {

  /**
   * @return \Symfony\Component\Routing\RouteCollection
   */
  public function routes() {
    $route_provider = \Drupal::service('router.route_provider');

    $route_collection = new RouteCollection();
    $pathperdomain_helper = \Drupal::service('pathperdomain.helper');
    $enabled_entity_types = $pathperdomain_helper->getConfiguredEntityTypes();

    foreach ($enabled_entity_types as $enabled_entity_type) {

      $route = $route_provider->getRouteByName("entity.$enabled_entity_type.canonical");
      $route->setPath('pathperdomain/{domain}/' . $enabled_entity_type. '/{' . $enabled_entity_type . '}');
      $route->addRequirements([
        '_custom_access' => '\Drupal\pathperdomain\PathPerDomainAccess::access',
      ]);

      // Add our route to the collection
      $route_collection->add('pathperdomain.view.' . $enabled_entity_type, $route);
    }

    return $route_collection;
  }

}
