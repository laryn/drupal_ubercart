<?php

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_order\Entity\OrderStatus;
use Drupal\uc_order\Event\OrderStatusEmailUpdateEvent;
use Drupal\uc_order\OrderInterface;

/**
 * Updates an order's status and optionally adds comments.
 */
class OrderUpdateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_view_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $form['order_comment_field'] = [
      '#type' => 'details',
      '#title' => $this->t('Add an order comment'),
    ];
    $form['order_comment_field']['order_comment'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Order comments are used primarily to communicate with the customer.'),
    ];

    $form['admin_comment_field'] = [
      '#type' => 'details',
      '#title' => $this->t('Add an admin comment'),
    ];
    $form['admin_comment_field']['admin_comment'] = [
      '#type' => 'textarea',
      '#description' => $this->t('Admin comments are only seen by store administrators.'),
    ];

    $form['current_status'] = [
      '#type' => 'value',
      '#value' => $order->getStatusId(),
    ];

    $form['order_id'] = [
      '#type' => 'value',
      '#value' => $order->id(),
    ];

    $form['controls'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['uc-inline-form']],
      '#weight' => 10,
    ];
    $form['controls']['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Order status'),
      '#default_value' => $order->getStatusId(),
      '#options' => OrderStatus::getOptionsList(),
    ];
    $form['controls']['notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send e-mail notification on update.'),
    ];

    $form['controls']['actions'] = ['#type' => 'actions'];
    $form['controls']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $this->currentUser()->id();

    if (!$form_state->isValueEmpty('order_comment')) {
      uc_order_comment_save($form_state->getValue('order_id'), $uid, $form_state->getValue('order_comment'), 'order', $form_state->getValue('status'), $form_state->getValue('notify'));
    }

    if (!$form_state->isValueEmpty('admin_comment')) {
      uc_order_comment_save($form_state->getValue('order_id'), $uid, $form_state->getValue('admin_comment'));
    }

    if ($form_state->getValue('status') != $form_state->getValue('current_status')) {
      Order::load($form_state->getValue('order_id'))
        ->setStatusId($form_state->getValue('status'))
        ->save();

      if ($form_state->isValueEmpty('order_comment')) {
        uc_order_comment_save($form_state->getValue('order_id'), $uid, '-', 'order', $form_state->getValue('status'), $form_state->getValue('notify'));
      }
    }

    // Let Rules send email if requested.
    if ($form_state->getValue('notify')) {
      $order = Order::load($form_state->getValue('order_id'));
      // rules_invoke_event('uc_order_status_email_update', $order);
      $event = new OrderStatusEmailUpdateEvent($order);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(OrderStatusEmailUpdateEvent::EVENT_NAME, $event);
    }

    drupal_set_message($this->t('Order updated.'));
  }

}
