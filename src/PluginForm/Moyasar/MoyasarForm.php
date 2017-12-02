<?php

namespace Drupal\commerce_moyasar\PluginForm\Moyasar;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;


class MoyasarForm extends BasePaymentMethodAddForm {

  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    $element = parent::buildCreditCardForm($element, $form_state);
    $element['number']['#placeholder'] = 'Credit card number';
    $element['card_owner_name'] = [
      '#type' => 'textfield',
      '#title' => t('Card owner name'),
      '#size' => 20,
      '#attributes' => ['autocomplete' => 'off'],
      '#weight' => -1,
      '#required' => TRUE,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    $card_owner_name = $values['card_owner_name'];
    $card_number = $values['number'];
    $card_cvv = $values['security_code'];
    $card_exp_month = $values['expiration']['month'];
    $card_exp_year = $values['expiration']['year'];

    $tempstore = \Drupal::service('user.private_tempstore')->get('commerce_moyasar');
    $tempstore->set('card_owner_name', $card_owner_name);
    $tempstore->set('card_number', $card_number);
    $tempstore->set('card_cvv', $card_cvv);
    $tempstore->set('card_exp_month', $card_exp_month);
    $tempstore->set('card_exp_year', $card_exp_year);
  }

}
