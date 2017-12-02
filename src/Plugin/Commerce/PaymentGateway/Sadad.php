<?php

namespace Drupal\commerce_moyasar\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides Sadad payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "sadad",
 *   label = "Sadad",
 *   display_label = "Sadad",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_moyasar\PluginForm\Sadad\SadadForm",
 *   },
 *   payment_method_types = {"sadad"},
 * )
 */
class Sadad extends OffsitePaymentGatewayBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
             'publishable_key' => '',
             'secret_key' => '',
           ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['publishable_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publishable Key'),
      '#default_value' => $this->configuration['publishable_key'],
      '#required' => TRUE,
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#default_value' => $this->configuration['secret_key'],
      '#required' => TRUE,
    ];

    return $form;
  }
  /**
   * {@inheritdoc}
   */
/*  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      // Validate the secret key.
      if (!empty($values['secret_key'])) {
        try {
          \Moyasar\Client::setApiKey($values['secret_key']);
         // if ($payments = count(\Moyasar\Payment::all()) >= 0) {
          //  $form_state->setError($form['secret_key'], $this->t('The provided secret key is working'));
         // }
        }
        catch (\HttpRequestNotFound $e) {
          $form_state->setError($form['secret_key'], $this->t('Invalid secret key.'));
        }
      }
    }
  }*/

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['publishable_key'] = $values['publishable_key'];
      $this->configuration['secret_key'] = $values['secret_key'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $request = json_decode($request);
    $payment_status = $request->get('status');

    if ($payment_status == 'paid') {
      $trans_id = $request->get('id');

        $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
        $payment = $payment_storage->create([
          'state' => 'authorization',
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $this->entityId,
          'order_id' => $order->id(),
          'remote_id' => $trans_id,
          'remote_state' => $payment_status,
          'authorized' => \Drupal::time()->getRequestTime(),
        ]);
        $payment->save();

        drupal_set_message($this->t('Your payment was successful with Order ID : @orderid', [
          '@orderid' => $order->id(),
        ]));
      }
    else {
      drupal_set_message($payment_status);
      throw new PaymentGatewayException();
    }
  }

}
