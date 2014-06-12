<?php

/**
 * @file
 * Contains \Drupal\uc_product\Plugin\views\field\BuyItNow.
 */

namespace Drupal\uc_product\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to provide payment method.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_product_buy_it_now")
 */
class BuyItNow extends FieldPluginBase {

  /**
   * Overrides FieldPluginBase::render().
   */
  public function render(ResultRow $values) {
    $nid = $this->getValue($values);
    $node = node_load($nid);
    if (uc_product_is_product($node)) {
      $form = drupal_get_form('\Drupal\uc_product\Form\BuyItNowForm', $node);
      return drupal_render($form);
    }
  }

}