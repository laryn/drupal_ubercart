<?php

/**
 * @file
 * Contains \Drupal\uc_tax\Form\TaxRateDeleteForm.
 */

namespace Drupal\uc_tax\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Deletes a tax rate.
 */
class TaxRateDeleteForm extends ConfirmFormBase {

  /**
   * The tax rate to be deleted.
   */
  protected $rate;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete @name?', ['@name' => $this->rate->name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('uc_tax.overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_tax_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tax_rate = NULL) {
    $this->rate = uc_tax_rate_load($tax_rate);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    uc_tax_rate_delete($this->rate->id);

    drupal_set_message(t('Tax rate %name deleted.', ['%name' => $this->rate->name]));

    $form_state->setRedirect('uc_tax.overview');
  }

}