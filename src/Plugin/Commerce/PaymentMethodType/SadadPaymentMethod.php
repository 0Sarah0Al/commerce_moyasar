<?php

namespace Drupal\commerce_moyasar\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the Sadad payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "sadad",
 *   label = @Translation("Sadad payment"),
 *   create_label = @Translation("Sadad payment"),
 * )
 */
class SadadPaymentMethod extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $args = [
      '@sadad_account' => $payment_method->sadad_account->value,
    ];
    return $this->t('Sadad account (@sadad_account)', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['sadad_account'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Sadad Account'))
      ->setDescription(t('Sadad online payment ID.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
