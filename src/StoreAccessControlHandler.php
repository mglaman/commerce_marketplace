<?php

namespace Drupal\commerce_marketplace;

use Drupal\commerce\EntityAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Overrides the Store entity access handler.
 */
class StoreAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Only allow users to create one store.
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);
    if ($result->isNeutral() || !$result->isForbidden()) {
      $entity_storage = \Drupal::entityTypeManager()->getStorage($this->entityTypeId);
      $existing_stores = $entity_storage->loadByProperties(['uid' => $account->id()]);
      $result = AccessResult::allowedIf(empty($existing_stores));
    }
    return $result;
  }

}
