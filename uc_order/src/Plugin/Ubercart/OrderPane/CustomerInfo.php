<?php

/**
 * @file
 * Contains \Drupal\uc_order\Plugin\Ubercart\OrderPane\CustomerInfo.
 */

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\EditableOrderPanePluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Manage the information for the customer's user account.
 *
 * @UbercartOrderPane(
 *   id = "customer",
 *   title = @Translation("Customer info"),
 *   weight = 3,
 * )
 */
class CustomerInfo extends EditableOrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return 'pos-left';
  }

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    if ($view_mode != 'customer') {
      if ($order->getOwnerId()) {
        $build['uid'] = array(
          '#type' => 'link',
          '#prefix' => t('Customer number') . ': ',
          '#suffix' => '<br />',
          '#title' => $order->getOwnerId(),
          '#url' => Url::fromRoute('entity.user.canonical', ['user' => $order->getOwnerId()]),
        );
      }
      $build['primary_email'] = array(
        '#type' => 'link',
        '#prefix' => t('E-mail address') . ': ',
        '#title' => $order->getEmail(),
        '#url' => Url::fromUri('mailto:' . $order->getEmail()),
      );
      return $build;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $output = '<div class="order-pane-icons">';
    $output .= ' <img src="' . base_path() . drupal_get_path('module', 'uc_store')
      . '/images/order_view.gif" alt="' . t('Search for an existing customer.') . '" '
      . 'title="' . t('Search for an existing customer.') . '" onclick="load_customer_search();" />';
    $output .= ' <img src="' . base_path() . drupal_get_path('module', 'uc_store')
      . '/images/menu_customers_small.gif" alt="' . t('Create a new customer.') . '" '
      . 'title="' . t('Create a new customer.') . '" onclick="load_new_customer_form();" />';
    $output .= '</div>';
    $output .= '<div id="customer-select"></div>';

    $form['icons'] = array(
      '#type' => 'markup',
      '#markup' => $output,
    );

    $form['uid'] = array(
      '#type' => 'hidden',
      '#default_value' => $order->getOwnerId(),
    );
    $form['uid_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Customer number'),
      '#default_value' => $order->getOwnerId(),
      '#maxlength' => 10,
      '#size' => 10,
      '#disabled' => TRUE,
    );
    $form['primary_email'] = array(
      '#type' => 'email',
      '#title' => t('E-mail address'),
      '#default_value' => $order->getEmail(),
      '#maxlength' => 64,
      '#size' => 32,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(OrderInterface $order, array &$form, FormStateInterface $form_state) {
    $order->setOwnerId($form_state->getValue('uid'));
    $order->setEmail($form_state->getValue('primary_email'));
  }

}
