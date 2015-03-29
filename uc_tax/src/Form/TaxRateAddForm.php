<?php

/**
 * @file
 * Contains \Drupal\uc_tax\Form\TaxRateAddForm.
 */

namespace Drupal\uc_tax\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the new tax rate form.
 */
class TaxRateAddForm extends TaxRateFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rate = parent::submitForm($form, $form_state);

    drupal_set_message(t('Tax rate %name created.', ['%name' => $rate->name]));

    //$form_state['redirect'] = 'admin/store/settings/taxes/manage/uc_tax_' . $rate->id;
    $form_state->setRedirect('uc_tax.overview');
  }

}