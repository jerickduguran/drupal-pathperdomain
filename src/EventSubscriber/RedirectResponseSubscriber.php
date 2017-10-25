<?php

namespace Drupal\pathperdomain\EventSubscriber;

use Drupal\Component\HttpFoundation\SecuredRedirectResponse; 
use Drupal\Core\Routing\LocalRedirectResponse; 
use Symfony\Component\HttpFoundation\Response; 
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse; 
use Drupal\Core\EventSubscriber\RedirectResponseSubscriber as BaseRedirectResponseSubscriber;
 
class RedirectResponseSubscriber extends BaseRedirectResponseSubscriber {
 
 
  public function checkRedirectUrl(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof RedirectResponse) {
      $request = $event->getRequest();

      // Let the 'destination' query parameter override the redirect target.
      // If $response is already a SecuredRedirectResponse, it might reject the
      // new target as invalid, in which case proceed with the old target.
      $destination = $request->query->get('destination');
      if ($destination) {
        // The 'Location' HTTP header must always be absolute.
        $destination = $this->getDestinationAsAbsoluteUrl($destination, $request->getSchemeAndHttpHost());
        try {
          $response->setTargetUrl($destination);
        }
        catch (\InvalidArgumentException $e) {
        }
      }

      // Regardless of whether the target is the original one or the overridden
      // destination, ensure that all redirects are safe.
      if (!($response instanceof SecuredRedirectResponse)) {
        try {
          // SecuredRedirectResponse is an abstract class that requires a
          // concrete implementation. Default to LocalRedirectResponse, which
          // considers only redirects to within the same site as safe.
          $safe_response = LocalRedirectResponse::createFromRedirectResponse($response);
          $safe_response->setRequestContext($this->requestContext);
        }
        catch (\InvalidArgumentException $e) { 
		  throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        $event->setResponse($safe_response);
      }
    }
  }
 
}
