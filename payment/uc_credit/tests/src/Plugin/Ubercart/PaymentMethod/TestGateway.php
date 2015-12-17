<?php

/**
 * @file
 * Contains \Drupal\test_gateway\Plugin\Ubercart\PaymentMethod\TestGateway.
 */

namespace Drupal\test_gateway\Plugin\Ubercart\PaymentMethod;

use Drupal\uc_credit\CreditCardPaymentMethodBase;

/**
 * Defines the test gateway payment method.
 *
 * This is a dummy payment gateway to use for testing or as an example. All
 * payments using this test gateway will succeed, except when:
 * - Credit card number equals '0000000000000000'. (Note that ANY card number
 *   that fails the Luhn algorithm check performed by uc_credit will not even be
 *   submitted to this gateway).
 * - CVV equals '000'.
 * - Credit card is expired.
 * - Payment amount equals 12.34 in store currency units.
 * - Customer's billing first name equals 'Fictitious'.
 * - Customer's billing telephone number equals '8675309'.
 *
 * @UbercartPaymentMethod(
 *   id = "test_gateway",
 *   name = @Translation("Test gateway"),
 * )
 */
class TestGateway extends CreditCardPaymentMethodBase {

  /**
   * {@inheritdoc}
   */
  protected function chargeCard($order, $amount, $txn_type, $reference = NULL) {
    $user = \Drupal::currentUser();

    // cc_exp_month and cc_exp_year are also validated by
    // _uc_credit_valid_card_expiration() on the checkout form.
    $month = $order->payment_details['cc_exp_month'];
    $year  = $order->payment_details['cc_exp_year'];
    if ($year < 100) {
      $year = $year + 2000;
    }

    // Card is expired at 0:00 on the first day of the next month.
    $expiration_date = mktime(0, 0, 0, $month + 1, 1, $year);

    // Conditions for failure are described in file documentation block above.
    // All other transactions will succeed.
    if ($order->payment_details['cc_number'] == '0000000000000000' ||
      (isset($order->payment_details['cc_cvv']) && $order->payment_details['cc_cvv'] == '000') ||
      ($expiration_date - REQUEST_TIME) <= 0                     ||
      $amount == 12.34                                           ||
      $order->billing_first_name == 'Fictitious'                 ||
      $order->billing_phone == '8675309'                            ) {
      $success = FALSE;
    }
    else {
      $success = TRUE;
    }

    // Uncomment this line to see the order object.  The information for the
    // payment is in the $order->payment_details array.
     drupal_set_message('<pre>' . print_r($order->payment_details, TRUE) . '</pre>');

    if ($success) {
      $message = t('Credit card charged: @amount', ['@amount' => uc_currency_format($amount)]);
      uc_order_comment_save($order->id(), $user->id(), $message, 'admin');
    }
    else {
      $message = t('Credit card charge failed.');
      uc_order_comment_save($order->id(), $user->id(), $message, 'admin');
    }

    $result = array(
      'success' => $success,
      'comment' => t('Card charged, resolution code: 0022548315'),
      'message' => $success ? t('Credit card payment processed successfully.') : t('Credit card charge failed.'),
      'uid' => $user->id(),
    );

    return $result;
  }

}