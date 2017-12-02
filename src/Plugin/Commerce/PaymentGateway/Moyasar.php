<?php

namespace Drupal\commerce_moyasar\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;


/**
 * Provides Moyasar payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "moyasar_gateway",
 *   label = "Moyasar",
 *   display_label = "Payment via Moyasar",
 *    forms = {
 *     "add-payment-method" = "Drupal\commerce_moyasar\PluginForm\Moyasar\MoyasarForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "visa", "mastercard",
 *   },
 * )
 */
class Moyasar extends OnsitePaymentGatewayBase implements MoyasarInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    \Moyasar\Client::setApiKey($this->configuration['secret_key']);

  }

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
  /*
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      // Validate the secret key.
      if (!empty($values['secret_key'])) {
        try {
        //  if ($payments = count(\Moyasar\Payment::all()) >= 0) {
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
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);

    // card data are temporary stored in global session variable which is a bad idea
    $tempstore = \Drupal::service('user.private_tempstore')->get('commerce_moyasar');

      $amount = $payment->getAmount();
      $amount = $amount->multiply(100)->getNumber();
      $currency_code = $payment->getAmount()->getCurrencyCode();
      $description = "";

      //$callback_url = Url::fromUri('internal:')->toString();

      //// HUGE SECURITY PROBLEM HERE /////
      /// No tokenization method available in the gateway ///
      $source = [
        "type" => 'creditcard',
        "name" => $tempstore->get('card_owner_name'),
        "number" => $tempstore->get('card_number'),
        "cvc" => $tempstore->get('card_cvv'),
        "month" => $tempstore->get('card_exp_month'),
        "year" => $tempstore->get('card_exp_year'),
      ];

    try {
      $response = \Moyasar\Payment::create($amount, $source, $description, $currency_code);
    }
    catch (\HttpRequestNotFound $e) {
     // throw new InvalidRequestException($e->getMessage());
      \Drupal::logger('commerce_moyasar')->error($e->getMessage());
    }
    if (isset($response['status']) && $response['status'] == 'initiated') {
      $payment->state = $capture ? 'capture_completed' : 'authorization';

      // supposedly moyasar sends back transaction_url withe the initiated payment object
      $transaction_url = $response['source']['transaction_url'];

     // $next_state     = $capture ? 'completed' : 'authorization';
      //$payment->setState($next_state);

      $payment->setRemoteId($response['id']);
      $payment->setAuthorizedTime(\Drupal::time()->getRequestTime());
      if ($capture) {
        $payment->setState('completed');
      }
      $payment->save();
      drupal_set_message($this->t('Your payment was successful with Order ID : @orderid', [
        '@orderid' => $payment->getOrderId(),
      ]));
    }
    else {
      \Drupal::logger('commerce_moyasar')->error(t('Payment could not be processed'));
      throw new DeclineException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    $capture_amount = $amount->multiply(100)->getNumber();
    $currency_code = $payment->getAmount()->getCurrencyCode();
    $description = "";
    //$callback_url = Url::fromUri('internal:')->toString();

    if ((int) $capture_amount > 0) {
      $remote_id = $payment->getRemoteId();

      // There is a transaction_url that should be brought here to capture payment

      // card data are temporary stored in a global session variable which is a bad idea
      $tempstore = \Drupal::service('user.private_tempstore')->get('commerce_moyasar');
      $source    = [
        "type"   => 'creditcard',
        "name"   => $tempstore->get('card_owner_name'),
        "number" => $tempstore->get('card_number'),
        "cvc"    => $tempstore->get('card_cvv'),
        "month"  => $tempstore->get('card_exp_month'),
        "year"   => $tempstore->get('card_exp_year'),
      ];
    try {
      $response = \Moyasar\Payment::create($capture_amount, $source, $description, $currency_code);
    }
    catch (\HttpRequestNotFound $e) {
      //throw new InvalidRequestException($e->getMessage());
      \Drupal::logger('commerce_moyasar')->error($e->getMessage());
    }

    if (isset($response['status']) == 'paid') {
      $payment->state = 'capture_completed';
      $payment->setAmount($amount);
      $payment->setState('completed');
      $payment->save();
    }
    else {
      \Drupal::logger('commerce_moyasar')->error(t('Amount could not be captured.'));
      throw new DeclineException();
    }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);

    $remote_id = $payment->getRemoteId();
    $refund_amount = $payment->getAmount()->multiply(100)->getNumber();

    try {
      $response = \Moyasar\Payment::refund($remote_id, $refund_amount);
    }
    catch (\HttpRequestNotFound $e) {
      throw new InvalidRequestException($e->getMessage());
    }

    if (isset($response['status']) == 'refunded') {
      $payment->setState('authorization_voided');
      $payment->save();
    }
    else {
      \Drupal::logger('commerce_moyasar')->error(t('Amount could not be captured.'));
      throw new DeclineException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $remote_id = $payment->getRemoteId();
    $refund_amount = $payment->getAmount()->multiply(100)->getNumber();

    try {
      $response = \Moyasar\Payment::refund($remote_id, $refund_amount);
    }
    catch (\HttpRequestNotFound $e) {
      throw new InvalidRequestException($e->getMessage());
    }

    if (isset($response['status']) == 'refunded') {
      $old_refunded_amount = $payment->getRefundedAmount();
      $new_refunded_amount = $old_refunded_amount->add($amount);

      if ($new_refunded_amount->lessThan($payment->getAmount())) {
        $payment->setState('partially_refunded');
      }
      else {
        $payment->setState('refunded');
      }

      $payment->setRefundedAmount($new_refunded_amount);
      $payment->save();
    }
    else {
      \Drupal::logger('commerce_moyasar')->error(t('Amount could not be refunded.'));
      throw new DeclineException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
      $required_keys = [
        'type', 'number',
      ];

    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }

      $payment_remote_id = $payment_method->getOriginalId();
    try {
      $payment_details = \Moyasar\Payment::fetch($payment_remote_id);
    }
    catch (\HttpRequestNotFound $e) {
      throw new InvalidRequestException($e->getMessage());
    }

    //var_dump($payment_details);

    if (!empty($payment_details)) {
    //  $payment_method->card_type = 'visa';
    //  $payment_method->card_number = '4111111111111111';

      //error I get from line 327: Notice: Undefined property: stdClass::$source
      // and Notice: Trying to get property of non-object
     $payment_method->card_type = $this->mapCreditCardType($payment_details->source->company->value);
    $payment_method->card_number = [substr($payment_details->source->number->value, -4)];
     // $payment_method->card_exp_month = $payment_details['expiration']['month'];
     // $payment_method->card_exp_year = $payment_details['expiration']['year'];
     $remote_id = $payment_details->id->value;
    //  $remote_id = '234567';
    //  $expires = CreditCard::calculateExpirationTimestamp($payment_details['expiration']['month'], $payment_details['expiration']['year']);
      $payment_method->setRemoteId($remote_id);
     // $payment_method->setExpiresTime($expires);
      $payment_method->save();
    }
    else {
      \Drupal::logger('commerce_moyasar')->error(t('Can\'t generate payment details.'));
      throw new DeclineException();
    }
    // ################################
    // delete temp session vars here -- PS. needs testing
    $tempstore = \Drupal::service('user.private_tempstore')->get('commerce_moyasar');
    $keys = ['card_owner_name', 'card_number', 'card_cvv', 'card_exp_month', 'card_exp_year'];
    foreach ($keys as $key) {
      $tempstore->delete($key);
    }
    // ##################################
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // Delete the local entity.
    $payment_method->delete();
  }

  /**
   * Maps the Moyasar credit card type to a Commerce credit card type.
   *
   * @param string $card_type
   *   The credit card type.
   *
   * @return string
   *   The Commerce credit card type.
   */
  protected function mapCreditCardType($card_type) {
    $map = [
      'MasterCard' => 'mastercard',
      'Visa' => 'visa',
    ];
    if (!isset($map[$card_type])) {
      throw new HardDeclineException(sprintf('Unsupported credit card type "%s".', $card_type));
    }

    return $map[$card_type];
  }

}
