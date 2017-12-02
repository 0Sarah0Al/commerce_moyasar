<?php

namespace Drupal\commerce_moyasar\PluginForm\Sadad;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class SadadForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_moyasar\Plugin\Commerce\PaymentGateway\Sadad $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $payment_gateway_configuration = $payment_gateway_plugin->getConfiguration();
    $api_key = $payment_gateway_configuration['secret_key'];

    $amount = $payment->getAmount();
    $formatted_amount = $this->formatNumber($amount->getNumber());
    $currency_code = $payment->getAmount()->getCurrencyCode();

    $tempstore = \Drupal::service('user.private_tempstore')->get('commerce_moyasar');
    $sadad_id = $tempstore->get('sadad_id');

    $data = [
      "amount" => $formatted_amount,
      "source" => [
        "type" => 'sadad',
        "username" => $sadad_id,
        "success_url" => $form['#return_url'],
        ],
      "description" => '',
      "currency" => $currency_code,
    ];
    /// passing the API key for authentication is missing here 
    $redirect_method = 'post';
    // $redirect_url = "https://" . "$api_key" . "@" . "api.moyasar.com/v1/payments";
    $redirect_url = "https://api.moyasar.com/v1/payments";

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }
  /**
   * Formats the charge amount for Moyasar.
   *
   * @param integer $amount
   *   The amount being charged.
   *
   * @return integer
   *   The formatted amount.
   */
  protected function formatNumber($amount) {
    $amount = $amount * 100;
    $amount = number_format($amount, 0, '.', '');
    return $amount;
  }

}
