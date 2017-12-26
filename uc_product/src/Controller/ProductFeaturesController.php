<?php

namespace Drupal\uc_product\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Controller routines for product feature routes.
 */
class ProductFeaturesController extends ControllerBase {

  /**
   * Displays the product features tab on a product node edit form.
   */
  public function featuresOverview(NodeInterface $node) {
    $header = [
      $this->t('Type'),
      $this->t('Description'),
      $this->t('Operations'),
    ];

    $rows = [];
    $features = uc_product_feature_load_multiple($node->id());
    foreach ($features as $feature) {
      $operations = [
        'edit' => ['title' => $this->t('Edit'), 'url' => Url::fromRoute('uc_product.feature_edit', ['node' => $node->id(), 'fid' => $feature->fid, 'pfid' => $feature->pfid])],
        'delete' => ['title' => $this->t('Delete'), 'url' => Url::fromRoute('uc_product.feature_delete', ['node' => $node->id(), 'fid' => $feature->fid, 'pfid' => $feature->pfid])],
      ];
      $rows[] = [
        ['data' => uc_product_feature_data($feature->fid, 'title')],
        ['data' => ['#markup' => $feature->description]],
        ['data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ]],
      ];
    }

    $build['features'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['class' => ['uc-product-features']],
      '#empty' => $this->t('No features found for this product.'),
    ];

    $build['add_form'] = $this->formBuilder()->getForm('Drupal\uc_product\Form\ProductFeatureAddForm', $node);

    return $build;
  }

  /**
   * Displays the add feature form.
   */
  public function featureAdd(NodeInterface $node, $fid) {
    $func = uc_product_feature_data($fid, 'callback');
    $form_state = new FormState();
    $form_state->setBuildInfo(['args' => [$node, NULL]]);
    return $this->formBuilder()->buildForm($func, $form_state);
  }

  /**
   * Displays the edit feature form.
   */
  public function featureEdit(NodeInterface $node, $fid, $pfid) {
    $func = uc_product_feature_data($fid, 'callback');
    $form_state = new FormState();
    $form_state->setBuildInfo(['args' => [$node, uc_product_feature_load($pfid)]]);
    return $this->formBuilder()->buildForm($func, $form_state);
  }

}
