<?php

namespace Drupal\commerce_moyasar\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;


/**
 * @CommerceCheckoutPane(
 *  id = "sadad_checkout_pane",
 *  label = @Translation("Sadad Checkout"),
 *  display_label = @Translation("Sadad Checkout"),
 *  default_step = "order_information",
 *  wrapper_element = "fieldset",
 * )
 */
class SadadCheckout extends CheckoutPaneBase implements CheckoutPaneInterface {


  /**
  * {@inheritdoc}
  */
  public function isVisible() {
    $payment_info_pane = $this->checkoutFlow->getPane('payment_information');
    return $payment_info_pane->isVisible() && $payment_info_pane->getStepId() != '_disabled';
    // This pane can't be used without the PaymentInformation pane.
   //$payment_info_pane = $this->checkoutFlow->getPane('payment_information');
   // $payment_method = $this->order->get('payment_method')->entity;
   //return $payment_method->label() == 'Sadad';
  }

  /**
  * {@inheritdoc}
  */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {

    $pane_form['sadad_online_payment_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sadad online payment ID'),
      '#attributes' => ['autocomplete' => 'off'],
      '#placeholder' => 'Sadad online payment ID',
      '#required' => TRUE,
    ];

    return $pane_form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $sadad_id = $values['sadad_online_payment_id'];
    $tempstore = \Drupal::service('user.private_tempstore')->get('commerce_moyasar');
    $tempstore->set('sadad_id', $sadad_id);
  }

}
