<?php

namespace Drupal\pathperdomain\EventSubscriber;
 
use Drupal\Core\Routing\RequestHelper; 
use Symfony\Component\HttpKernel\Event\GetResponseEvent; 
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\redirect\EventSubscriber\RouteNormalizerRequestSubscriber as BaseRouteNormalizerRequestSubscriber;

class RouteNormalizerRequestSubscriber extends BaseRouteNormalizerRequestSubscriber {
 
  public function onKernelRequestRedirect(GetResponseEvent $event) {

    if (!$this->config->get('route_normalizer_enabled') || !$event->isMasterRequest()) {
      return;
    }

    $request = $event->getRequest();
    if ($request->attributes->get('_disable_route_normalizer')) {
      return;
    }

    if ($this->redirectChecker->canRedirect($request)) {
      // The "<current>" placeholder can be used for all routes except the front
      // page because it's not a real route.
      $route_name = $this->pathMatcher->isFrontPage() ? '<front>' : '<current>';

      // Don't pass in the query here using $request->query->all()
      // since that can potentially modify the query parameters.
      $options = ['absolute' => TRUE];
      $redirect_uri = $this->urlGenerator->generateFromRoute($route_name, [], $options);

      // Strip off query parameters added by the route such as a CSRF token.
      if (strpos($redirect_uri, '?') !== FALSE) {
        $redirect_uri  = strtok($redirect_uri, '?');
      }

      // Append back the request query string from $_SERVER.
      $query_string = $request->server->get('QUERY_STRING');
      if ($query_string) {
        $redirect_uri .= '?' . $query_string;
      }

      // Remove /index.php from redirect uri the hard way.
      if (!RequestHelper::isCleanUrl($request)) {
        // This needs to be fixed differently.
        $redirect_uri = str_replace('/index.php', '', $redirect_uri);
      }

      $original_uri = $request->getSchemeAndHttpHost() . $request->getRequestUri();
      $original_uri = urldecode($original_uri);
      $redirect_uri = urldecode($redirect_uri);
      if ($redirect_uri != $original_uri) {  
        $response = new TrustedRedirectResponse($redirect_uri, $this->config->get('default_status_code'));
        $response->headers->set('X-Drupal-Route-Normalizer', 1);
        $event->setResponse($response);
        // Disable page cache for redirects as that results in unpredictable
        // behavior, e.g. when a trailing ? without query parameters is
        // involved.
        // @todo Remove when https://www.drupal.org/node/2761639 is fixed in
        //   Drupal core.
        \Drupal::service('page_cache_kill_switch')->trigger();
      }
    }
  } 

}
