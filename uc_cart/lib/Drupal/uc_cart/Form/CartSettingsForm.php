<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Form\CartSettingsForm.
 */

namespace Drupal\uc_cart\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure general shopping cart settings for this site.
 */
class CartSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_cart_cart_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $cart_config = \Drupal::config('uc_cart.settings');

    $form['cart-settings'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'js' => array(
          'vertical-tabs' => drupal_get_path('module', 'uc_cart') . '/js/uc_cart.admin.js',
        ),
      ),
    );

    $form['general'] = array(
      '#type' => 'details',
      '#title' => t('Basic settings'),
      '#group' => 'cart-settings',
    );

    $form['general']['uc_cart_add_item_msg'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display a message when a customer adds an item to their cart.'),
      '#default_value' => $cart_config->get('add_item_msg'),
    );
    $form['general']['uc_add_item_redirect'] = array(
      '#type' => 'textfield',
      '#title' => t('Add to cart redirect'),
      '#description' => t('Enter the page to redirect to when a customer adds an item to their cart, or &lt;none&gt; for no redirect.'),
      '#default_value' => $cart_config->get('add_item_redirect'),
      '#size' => 32,
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
    );

    $form['general']['uc_cart_empty_button'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show an "Empty cart" button on the cart page.'),
      '#default_value' => $cart_config->get('empty_button'),
    );

    $form['general']['uc_minimum_subtotal'] = array(
      '#type' => 'uc_price',
      '#title' => t('Minimum order subtotal'),
      '#description' => t('Customers will not be allowed to check out if the subtotal of items in their cart is less than this amount.'),
      '#default_value' => $cart_config->get('minimum_subtotal'),
    );

    $form['lifetime'] = array(
      '#type' => 'details',
      '#title' => t('Cart lifetime'),
      '#description' => t('Set the length of time that products remain in the cart. Cron must be running for this feature to work.'),
      '#group' => 'cart-settings',
    );

    $range = range(1, 60);
    $form['lifetime']['anonymous'] = array(
      '#type' => 'fieldset',
      '#title' => t('Anonymous users'),
      '#attributes' => array('class' => array('uc-inline-form', 'clearfix')),
    );
    $form['lifetime']['anonymous']['uc_cart_anon_duration'] = array(
      '#type' => 'select',
      '#title' => t('Duration'),
      '#options' => array_combine($range, $range),
      '#default_value' => $cart_config->get('anon_duration'),
    );
    $form['lifetime']['anonymous']['uc_cart_anon_unit'] = array(
      '#type' => 'select',
      '#title' => t('Units'),
      '#options' => array(
        'minutes' => t('Minute(s)'),
        'hours' => t('Hour(s)'),
        'days' => t('Day(s)'),
        'weeks' => t('Week(s)'),
        'years' => t('Year(s)'),
      ),
      '#default_value' => $cart_config->get('anon_unit'),
    );

    $form['lifetime']['authenticated'] = array(
      '#type' => 'fieldset',
      '#title' => t('Authenticated users'),
      '#attributes' => array('class' => array('uc-inline-form', 'clearfix')),
    );
    $form['lifetime']['authenticated']['uc_cart_auth_duration'] = array(
      '#type' => 'select',
      '#title' => t('Duration'),
      '#options' => array_combine($range, $range),
      '#default_value' => $cart_config->get('auth_duration'),
    );
    $form['lifetime']['authenticated']['uc_cart_auth_unit'] = array(
      '#type' => 'select',
      '#title' => t('Units'),
      '#options' => array(
        'hours' => t('Hour(s)'),
        'days' => t('Day(s)'),
        'weeks' => t('Week(s)'),
        'years' => t('Year(s)'),
      ),
      '#default_value' => $cart_config->get('auth_unit'),
    );

    $form['continue_shopping'] = array(
      '#type' => 'details',
      '#title' => t('Continue shopping element'),
      '#description' => t('These settings control the <em>continue shopping</em> option on the cart page.'),
      '#group' => 'cart-settings',
    );
    $form['continue_shopping']['uc_continue_shopping_type'] = array(
      '#type' => 'radios',
      '#title' => t('<em>Continue shopping</em> element'),
      '#options' => array(
        'link' => t('Text link'),
        'button' => t('Button'),
        'none' => t('Do not display'),
      ),
      '#default_value' => $cart_config->get('continue_shopping_type'),
    );
    $form['continue_shopping']['uc_continue_shopping_use_last_url'] = array(
      '#type' => 'checkbox',
      '#title' => t('Make <em>continue shopping</em> go back to the last item that was added to the cart.'),
      '#description' => t('If this is disabled or the item is unavailable, the URL specified below will be used.'),
      '#default_value' => $cart_config->get('continue_shopping_use_last_url'),
    );
    $form['continue_shopping']['uc_continue_shopping_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Default <em>continue shopping</em> destination'),
      '#default_value' => $cart_config->get('continue_shopping_url'),
      '#size' => 32,
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
    );

    $form['breadcrumb'] = array(
      '#type' => 'details',
      '#title' => t('Cart breadcrumb'),
      '#description' => t('Drupal automatically adds a <em>Home</em> breadcrumb to the cart page, or you can use these settings to specify a custom breadcrumb.'),
      '#group' => 'cart-settings',
    );
    $form['breadcrumb']['uc_cart_breadcrumb_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Cart page breadcrumb text'),
      '#description' => t('Leave blank to use the default <em>Home</em> breadcrumb.'),
      '#default_value' => $cart_config->get('breadcrumb_text'),
    );
    $form['breadcrumb']['uc_cart_breadcrumb_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Cart page breadcrumb destination'),
      '#default_value' => $cart_config->get('breadcrumb_url'),
      '#size' => 32,
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $cart_config = \Drupal::config('uc_cart.settings');
    $cart_config
      ->set('add_item_msg', $form_state['values']['uc_cart_add_item_msg'])
      ->set('add_item_redirect', $form_state['values']['uc_add_item_redirect'])
      ->set('empty_button', $form_state['values']['uc_cart_empty_button'])
      ->set('minimum_subtotal', $form_state['values']['uc_minimum_subtotal'])
      ->set('anon_duration', $form_state['values']['uc_cart_anon_duration'])
      ->set('anon_unit', $form_state['values']['uc_cart_anon_unit'])
      ->set('auth_duration', $form_state['values']['uc_cart_auth_duration'])
      ->set('auth_unit', $form_state['values']['uc_cart_auth_unit'])
      ->set('continue_shopping_type', $form_state['values']['uc_continue_shopping_type'])
      ->set('continue_shopping_use_last_url', $form_state['values']['uc_continue_shopping_use_last_url'])
      ->set('continue_shopping_url', $form_state['values']['uc_continue_shopping_url'])
      ->set('breadcrumb_text', $form_state['values']['uc_cart_breadcrumb_text'])
      ->set('breadcrumb_url', $form_state['values']['uc_cart_breadcrumb_url'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
