<?php

/**
 * @file
 * Contains \Drupal\uc_tax\Tests\StoredTaxTest.
 */

namespace Drupal\uc_tax\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests that historical tax data is stored correctly, and that the proper amount is displayed.
 *
 * @group Ubercart
 */
class StoredTaxTest extends UbercartTestBase {

  public static $modules = ['uc_cart', 'uc_payment', 'uc_payment_pack', 'uc_tax'];
  public static $adminPermissions = [/*'administer rules', */'administer taxes'];

  protected function loadTaxLine($order_id) {
    $order = uc_order_load($order_id, TRUE);
    foreach ($order->line_items as $line) {
      if ($line['type'] == 'tax') {
        return $line;
      }
    }
    return FALSE;
  }

  protected function assertTaxLineCorrect($line, $rate, $when) {
    $this->assertTrue($line, t('The tax line item was saved to the order ' . $when));
    $this->assertTrue(number_format($rate * $this->product->price->value, 2) == number_format($line['amount'], 2), t('Stored tax line item has the correct amount ' . $when));
    $this->assertFieldByName('line_items[' . $line['line_item_id'] . '][li_id]', $line['line_item_id'], t('Found the tax line item ID ' . $when));
    $this->assertText($line['title'], t('Found the tax title ' . $when));
    $this->assertText(uc_currency_format($line['amount']), t('Tax display has the correct amount ' . $when));
  }

  public function testTaxDisplay() {
    $this->drupalLogin($this->adminUser);

    // Enable a payment method for the payment preview checkout pane.
    $edit = array('methods[check][status]' => 1);
    $this->drupalPostForm('admin/store/settings/payment', $edit, t('Save configuration'));

    // Create a 20% inclusive tax rate.
    $rate = (object) array(
      'name' => $this->randomMachineName(8),
      'rate' => 0.2,
      'taxed_product_types' => array('product'),
      'taxed_line_items' => array(),
      'weight' => 0,
      'shippable' => 0,
      'display_include' => 1,
      'inclusion_text' => '',
    );
    uc_tax_rate_save($rate);

    $this->drupalGet('admin/store/settings/taxes');
    $this->assertText($rate->name, t('Tax was saved successfully.'));

    // $this->drupalGet("admin/store/settings/taxes/manage/uc_tax_$rate->id");
    // $this->assertText(t('Conditions'), t('Rules configuration linked to tax.'));

    $this->addToCart($this->product);

    // Manually step through checkout. $this->checkout() doesn't know about taxes.
    $this->drupalPostForm('cart', array(), 'Checkout');
    $this->assertText(
      t('Enter your billing address and information here.'),
      t('Viewed cart page: Billing pane has been displayed.')
    );
    $this->assertRaw($rate->name, t('Tax line item displayed.'));
    $this->assertRaw(uc_currency_format($rate->rate * $this->product->price->value), t('Correct tax amount displayed.'));

    // Submit the checkout page.
    $edit = $this->populateCheckoutForm();
    $this->drupalPostForm('cart/checkout', $edit, t('Review order'));
    $this->assertRaw(t('Your order is almost complete.'));
    $this->assertRaw($rate->name, t('Tax line item displayed.'));
    $this->assertRaw(uc_currency_format($rate->rate * $this->product->price->value), t('Correct tax amount displayed.'));

    // Complete the review page.
    $this->drupalPostForm(NULL, array(), t('Submit order'));

    $order_id = db_query("SELECT order_id FROM {uc_orders} WHERE delivery_first_name = :name", [':name' => $edit['panes[delivery][first_name]']])->fetchField();
    if ($order_id) {
      $this->pass(
        t('Order %order_id has been created', ['%order_id' => $order_id])
      );

      $this->drupalGet('admin/store/orders/' . $order_id . '/edit');
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $rate->rate, 'on initial order load');

      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', array(), t('Save changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $rate->rate, 'after saving order');

      // Change tax rate and ensure order doesn't change.
      $oldrate = $rate->rate;
      $rate->rate = 0.1;
      $rate = uc_tax_rate_save($rate);

      // Save order because tax changes are only updated on save.
      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', array(), t('Save changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $oldrate, 'after rate change');

      // Change taxable products and ensure order doesn't change.
      $class = $this->createProductClass();
      $rate->taxed_product_types = array($class->getEntityTypeId());
      uc_tax_rate_save($rate);
      // entity_flush_caches();
      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', array(), t('Save changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $oldrate, 'after applicable product change');

      // Change order Status back to in_checkout and ensure tax-rate changes now update the order.
      entity_load('uc_order', $order_id)
        ->setStatusId('in_checkout')
        ->save();
      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', array(), t('Save changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertFalse($this->loadTaxLine($order_id), t('The tax line was removed from the order when order status changed back to in_checkout.'));

      // Restore taxable product and ensure new tax is added.
      $rate->taxed_product_types = array('product');
      uc_tax_rate_save($rate);
      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', array(), t('Save changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $rate->rate, 'when order status changed back to in_checkout');
    }
    else {
      $this->fail(t('No order was created.'));
    }
  }

}