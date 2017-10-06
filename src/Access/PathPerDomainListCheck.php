<?php

namespace Drupal\pathperdomain\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Custom access control handler for the domain path overview page.
 */
class PathPerDomainListCheck {

  /**
   * Handles route permissions on the domain path list page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account making the route request.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public static function viewPathPerDomainList(AccountInterface $account) {
    if ($account->hasPermission('administer domain path entity') || $account->hasPermission('view domain path list')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
