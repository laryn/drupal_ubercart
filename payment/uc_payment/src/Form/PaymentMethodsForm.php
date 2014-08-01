<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Form\PaymentMethodsForm.
 */

namespace Drupal\uc_payment\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_payment\Plugin\PaymentMethodManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure available payment methods for the store.
 */
class PaymentMethodsForm extends ConfigFormBase {

  /**
   * The payment method manager.
   *
   * @var \Drupal\uc_payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * Constructs a PaymentMethodsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\uc_payment\Plugin\PaymentMethodManager $payment_method_manager
   *   The payment method plugin manager.
   */
  public function __construct(ConfigFactory $config_factory, PaymentMethodManager $payment_method_manager) {
    parent::__construct($config_factory);

    $this->paymentMethodManager = $payment_method_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.uc_payment.method')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_payment_methods_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['methods'] = array(
      '#type' => 'table',
      '#header' => array(t('Payment method'), t('List position'), t('Operations')),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'uc-payment-method-weight',
        ),
      ),
    );

    foreach (uc_payment_method_list() as $id => $method) {
      $form['methods'][$id]['#attributes']['class'][] = 'draggable';
      $form['methods'][$id]['status'] = array(
        '#type' => 'checkbox',
        '#title' => String::checkPlain($method['name']),
        '#default_value' => $method['checkout'],
      );
      $form['methods'][$id]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $method['name'])),
        '#title_display' => 'invisible',
        '#default_value' => $method['weight'],
        '#attributes' => array(
          'class' => array('uc-payment-method-weight'),
        ),
      );

      if (empty($method['no_gateway'])) {
        $gateways = _uc_payment_gateway_list($id, TRUE);
        $options = array();
        foreach ($gateways as $gateway_id => $gateway) {
          $options[$gateway_id] = $gateway['title'];
        }
        if ($options) {
          $form['methods'][$id]['status']['#title'] .= ' (' . t('includes %gateways', array('%gateways' => implode(', ', $options))) . ')';
        }
      }

      $links = array();

      if (!isset($method['configurable'])) {
        // Determine whether a legacy plugin has a valid settings form.
        $method_settings = $this->paymentMethodManager->createInstance($id)->settingsForm(array(), $form_state);
        if (is_array($method_settings)) {
          $links['settings'] = array(
            'title' => t('Settings'),
            'href' => 'admin/store/settings/payment/method/' . $id,
          );
        }
      }
      elseif ($method['configurable']) {
        $links['settings'] = array(
          'title' => t('Settings'),
          'href' => 'admin/store/settings/payment/method/' . $id,
        );
      }

      // $links['conditions'] = array(
      //   'title' => t('Conditions'),
      //   'href' => 'admin/store/settings/payment/manage/uc_payment_method_' . $id,
      // );

      $form['methods'][$id]['settings'] = array(
        '#type' => 'operations',
        '#links' => $links,
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('uc_payment.settings')
      ->set('methods', $form_state['values']['methods'])
      ->save();

    $this->paymentMethodManager->clearCachedDefinitions();

    parent::submitForm($form, $form_state);
  }

}