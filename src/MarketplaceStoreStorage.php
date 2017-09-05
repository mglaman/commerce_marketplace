<?php

namespace Drupal\commerce_marketplace;

use Drupal\commerce_store\StoreStorage;

/**
 * Overrides the store storage class.
 */
class MarketplaceStoreStorage extends StoreStorage {

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    $uid = $this->getCurrentUserId();
    if ($uid === 0) {
      // No stores for the anonymous user.
      return [];
    }
    $stores = parent::loadMultiple($ids);
    // Do not return the stores which are not owned by the current user except
    // an admin ($uid === FALSE) which should be able to access any store.
    // @todo Remove when the core #2499645 is fixed.
    // @see https://www.drupal.org/node/2848232
    if ($uid) {
      foreach ($stores as $index => $store) {
        if ($store->getOwnerId() != $uid) {
          unset($stores[$index]);
        }
      }
    }

    return $stores;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery($conjunction = 'AND') {
    $query = parent::getQuery($conjunction);

    // If the current user is not admin we restrict the query to the stores
    // owned by the user or, if the $uid === 0, return the query for the
    // anonymous user which obviously cannot be the owner of any store.
    if (($uid = $this->getCurrentUserId()) || $uid === 0) {
      $query->condition('uid', $uid);
    }

    return $query;
  }

  /**
   * Helper method to check the current user access to a commerce store.
   *
   * @return FALSE|int
   *   FALSE if the user is admin; user ID if the user has permission to view
   *   own store; an anonymous user ID (0) otherwise.
   */
  protected function getCurrentUserId() {
    $user = \Drupal::currentUser();
    $uid = FALSE;

    if (!$user->hasPermission($this->entityType->getAdminPermission())) {
      $uid = $user->hasPermission('view own commerce_store') ? $user->id() : 0;
    }

    return  $uid;
  }

}
