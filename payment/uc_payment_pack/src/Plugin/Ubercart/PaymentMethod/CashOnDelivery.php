<?php

/**
 * @file
 * Contains \Drupal\uc_payment_pack\Plugin\Ubercart\PaymentMethod\CashOnDelivery.
 */

namespace Drupal\uc_payment_pack\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines the cash on delivery payment method.
 *
 * @PaymentMethod(
 *   id = "cod",
 *   name = @Translation("Cash on delivery"),
 *   title = @Translation("Cash on delivery"),
 *   checkout = FALSE,
 *   no_gateway = TRUE,
 *   configurable = TRUE,
 *   weight = 1,
 * )
 */
class CashOnDelivery extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $cod_config = \Drupal::config('uc_payment_pack.cod.settings');

    $build['policy'] = array(
      '#markup' => '<p>' . $cod_config->get('policy') . '</p>'
    );

    if (($max = $cod_config->get('max_order')) > 0 && is_numeric($max)) {
      $build['eligibility'] = array(
        '#markup' => '<p>' . t('Orders totalling more than !number are <b>not eligible</b> for COD.', ['!number' => uc_currency_format($max)]) . '</p>'
      );
    }

    if ($cod_config->get('delivery_date')) {
      $build += $this->deliveryDateForm($order);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $cod_config = \Drupal::config('uc_payment_pack.cod.settings');

    if ($cod_config->get('delivery_date')) {
      $order->payment_details = $form_state->getValue(['panes', 'payment', 'details']);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReview(OrderInterface $order) {
    $cod_config = \Drupal::config('uc_payment_pack.cod.settings');

    $review = array();

    if ($cod_config->get('delivery_date')) {
      $date = uc_date_format(
        $order->payment_details['delivery_month'],
        $order->payment_details['delivery_day'],
        $order->payment_details['delivery_year']
      );
      $review[] = array('title' => t('Delivery date'), 'data' => $date);
    }

    return $review;
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $cod_config = \Drupal::config('uc_payment_pack.cod.settings');

    $build = array();

    if ($cod_config->get('delivery_date') &&
      isset($order->payment_details['delivery_month']) &&
      isset($order->payment_details['delivery_day']) &&
      isset($order->payment_details['delivery_year'])) {
      $build['#markup'] = t('Desired delivery date:') . '<br />' .
        uc_date_format(
          $order->payment_details['delivery_month'],
          $order->payment_details['delivery_day'],
          $order->payment_details['delivery_year']
        );
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditDetails(OrderInterface $order) {
    $cod_config = \Drupal::config('uc_payment_pack.cod.settings');

    $build = array();

    if ($cod_config->get('delivery_date')) {
      $build = $this->deliveryDateForm($order);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function orderLoad(OrderInterface $order) {
    $result = db_query('SELECT * FROM {uc_payment_cod} WHERE order_id = :id', [':id' => $order->id()]);
    if ($row = $result->fetchObject()) {
      $order->payment_details = array(
        'delivery_month' => $row->delivery_month,
        'delivery_day'   => $row->delivery_day,
        'delivery_year'  => $row->delivery_year,
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderSave(OrderInterface $order) {
    if (isset($order->payment_details['delivery_month']) &&
        isset($order->payment_details['delivery_day']) &&
        isset($order->payment_details['delivery_year'])) {
      db_merge('uc_payment_cod')
        ->key(array('order_id' => $order->id()))
        ->fields(array(
          'delivery_month' => $order->payment_details['delivery_month'],
          'delivery_day'   => $order->payment_details['delivery_day'],
          'delivery_year'  => $order->payment_details['delivery_year'],
        ))
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(OrderInterface $order) {
    $cod_config = \Drupal::config('uc_payment_pack.cod.settings');
    $max = $cod_config->get('max_order');

    if ($max > 0 && $order->getTotal() > $max) {
      $result[] = array(
        'pass' => FALSE,
        'message' => t('Your final order total exceeds the maximum for COD payment.  Please go back and select a different method of payment.')
      );
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderDelete(OrderInterface $order) {
    db_delete('uc_payment_cod')
      ->condition('order_id', $order->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm() {
    return \Drupal\uc_payment_pack\Form\CashOnDeliverySettingsForm::create(\Drupal::getContainer());
  }

  /**
   * Collect additional information for the "Cash on Delivery" payment method.
   */
  protected function deliveryDateForm($order) {
    $month = !empty($order->payment_details['delivery_month']) ? $order->payment_details['delivery_month'] : \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'n');
    $day   = !empty($order->payment_details['delivery_day'])   ? $order->payment_details['delivery_day']   : \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'j');
    $year  = !empty($order->payment_details['delivery_year'])  ? $order->payment_details['delivery_year']  : \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'Y');

    $form['description'] = array(
      '#markup' => '<div>' . t('Enter a desired delivery date:') . '</div>',
    );
    $form['delivery_month'] = uc_select_month(NULL, $month);
    $form['delivery_day']   = uc_select_day(NULL, $day);
    $form['delivery_year']  = uc_select_year(NULL, $year, \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'Y'), \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'Y') + 1);

    return $form;
  }

}