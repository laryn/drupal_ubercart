<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Form\EmptyCartForm.
 */

namespace Drupal\uc_cart\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_cart\Controller\Cart;

/**
 * Confirm that the customer wants to empty their cart.
 */
class EmptyCartForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to empty your shopping cart?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('uc_cart.cart');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_empty_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cart = Cart::create(\Drupal::getContainer());
    $cart->emptyCart();
    $form_state->setRedirect('uc_cart.cart');
  }

}