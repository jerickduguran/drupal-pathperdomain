<?php

namespace Drupal\pathperdomain\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\pathperdomain\PathPerDomainInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Domain Path entity routes.
 */
class PathPerDomainViewController extends ControllerBase {

  /**
   * Redirect domain path view page to domain path edit page.
   *
   * @param \Drupal\pathperdomain\PathPerDomainInterface $pathperdomain
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function view(PathPerDomainInterface $pathperdomain) {
    return new RedirectResponse(Url::fromRoute('entity.pathperdomain.edit_form', ['pathperdomain' => $pathperdomain->id()])->toString());
  }

}
