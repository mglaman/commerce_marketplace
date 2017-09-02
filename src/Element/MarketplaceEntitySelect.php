<?php

namespace Drupal\commerce_marketplace\Element;

use Drupal\commerce\Element\EntitySelect;
use Drupal\Core\Form\FormStateInterface;

/**
 * Overrides the commerce entity select form element.
 */
class MarketplaceEntitySelect extends EntitySelect {

  /**
   * {@inheritdoc}
   */
  public static function processEntitySelect(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processEntitySelect($element, $form_state, $complete_form);

    // Do not render the options for selecting stores which are not owned by the
    // current user except an admin who should be able to select any store.
    // @todo Remove when the core #2499645 is fixed.
    // @see https://www.drupal.org/node/2848232
    if ($element['#target_type'] == 'commerce_store') {
      $user = \Drupal::currentUser();
      if (!$user->hasPermission('administer commerce_store')) {
        $id = $user->id();
        foreach (\Drupal::entityManager()->getStorage('commerce_store')->loadMultiple() as $store) {
          if ($store->getOwnerId() != $id) {
            unset($element['value']['#options'][$store->id()]);
          }
        }
      }
    }

    return $element;
  }

}
