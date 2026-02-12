<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Método de Pago de Billing.
 *
 * Cache local de métodos de pago sincronizados desde Stripe.
 * Se actualiza vía webhooks (payment_method.attached/detached).
 *
 * @ContentEntityType(
 *   id = "billing_payment_method",
 *   label = @Translation("Método de Pago"),
 *   label_collection = @Translation("Métodos de Pago"),
 *   label_singular = @Translation("método de pago"),
 *   label_plural = @Translation("métodos de pago"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_billing\ListBuilder\BillingPaymentMethodListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_billing\Form\BillingPaymentMethodForm",
 *       "add" = "Drupal\jaraba_billing\Form\BillingPaymentMethodForm",
 *       "edit" = "Drupal\jaraba_billing\Form\BillingPaymentMethodForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_billing\Access\BillingPaymentMethodAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "billing_payment_method",
 *   admin_permission = "administer billing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "stripe_payment_method_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/billing-payment-method/{billing_payment_method}",
 *     "add-form" = "/admin/content/billing-payment-method/add",
 *     "edit-form" = "/admin/content/billing-payment-method/{billing_payment_method}/edit",
 *     "delete-form" = "/admin/content/billing-payment-method/{billing_payment_method}/delete",
 *     "collection" = "/admin/content/billing-payment-methods",
 *   },
 *   field_ui_base_route = "jaraba_billing.billing_payment_method.settings",
 * )
 */
class BillingPaymentMethod extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * Comprueba si es el método de pago predeterminado.
   */
  public function isDefault(): bool {
    return (bool) $this->get('is_default')->value;
  }

  /**
   * Comprueba si la tarjeta ha expirado.
   */
  public function isExpired(): bool {
    $type = $this->get('type')->value;
    if ($type !== 'card') {
      return FALSE;
    }
    $expMonth = (int) $this->get('card_exp_month')->value;
    $expYear = (int) $this->get('card_exp_year')->value;
    if (!$expMonth || !$expYear) {
      return FALSE;
    }
    $now = new \DateTime();
    $expDate = new \DateTime("{$expYear}-{$expMonth}-01");
    $expDate->modify('last day of this month');
    return $now > $expDate;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stripe_payment_method_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Payment Method ID'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stripe_customer_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Customer ID'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'card' => t('Tarjeta'),
        'sepa_debit' => t('SEPA Débito Directo'),
        'bank_transfer' => t('Transferencia Bancaria'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['card_brand'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Marca de Tarjeta'))
      ->setSetting('max_length', 32)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['card_last4'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Últimos 4 Dígitos'))
      ->setSetting('max_length', 4)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['card_exp_month'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Mes de Expiración'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['card_exp_year'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Año de Expiración'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_default'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Predeterminado'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('active')
      ->setSetting('allowed_values', [
        'active' => t('Activo'),
        'expired' => t('Expirado'),
        'detached' => t('Desvinculado'),
      ])
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

}
