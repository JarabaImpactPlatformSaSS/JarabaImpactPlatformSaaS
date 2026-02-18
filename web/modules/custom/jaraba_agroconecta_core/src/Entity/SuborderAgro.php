<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad SuborderAgro.
 *
 * Representa un sub-pedido que agrupa los items de un mismo productor.
 * Permite gestión independiente, tracking y payout por productor.
 * Se crea automáticamente al confirmar un pedido maestro (OrderAgro).
 *
 * @ContentEntityType(
 *   id = "suborder_agro",
 *   label = @Translation("Sub-pedido Agro"),
 *   label_collection = @Translation("Sub-pedidos Agro"),
 *   label_singular = @Translation("sub-pedido agro"),
 *   label_plural = @Translation("sub-pedidos agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\SuborderAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\SuborderAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\SuborderAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\SuborderAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "suborder_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.suborder_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "suborder_number",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-suborders/{suborder_agro}",
 *     "edit-form" = "/admin/content/agro-suborders/{suborder_agro}/edit",
 *     "delete-form" = "/admin/content/agro-suborders/{suborder_agro}/delete",
 *     "collection" = "/admin/content/agro-suborders",
 *   },
 * )
 */
class SuborderAgro extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * Estados de payout.
     */
    const PAYOUT_PENDING = 'pending';
    const PAYOUT_TRANSFERRED = 'transferred';
    const PAYOUT_FAILED = 'failed';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Número de sub-pedido (AGR-YYYYMMDD-XXXX-P1)
        $fields['suborder_number'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Número de sub-pedido'))
            ->setDescription(t('Número único del sub-pedido.'))
            ->setSetting('max_length', 40)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al pedido padre
        $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Pedido padre'))
            ->setDescription(t('Pedido maestro al que pertenece este sub-pedido.'))
            ->setSetting('target_type', 'order_agro')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al productor
        $fields['producer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Productor'))
            ->setDescription(t('Productor responsable de este sub-pedido.'))
            ->setSetting('target_type', 'producer_profile')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado del sub-pedido (mismos estados que OrderAgro)
        $fields['state'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado actual del sub-pedido.'))
            ->setDefaultValue(OrderAgro::STATE_PAID)
            ->setSetting('max_length', 32)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Subtotal items del productor
        $fields['subtotal'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Subtotal'))
            ->setDescription(t('Suma de items del productor.'))
            ->setRequired(TRUE)
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Envío atribuido
        $fields['shipping_amount'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Envío'))
            ->setDescription(t('Coste de envío atribuido a este productor.'))
            ->setDefaultValue('0.00')
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Porcentaje de comisión
        $fields['commission_rate'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('% Comisión'))
            ->setDescription(t('Porcentaje de comisión de la plataforma.'))
            ->setRequired(TRUE)
            ->setDefaultValue('5.00')
            ->setSetting('precision', 4)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Importe de comisión
        $fields['commission_amount'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Importe comisión'))
            ->setDescription(t('Comisión en valor absoluto.'))
            ->setRequired(TRUE)
            ->setDefaultValue('0.00')
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Pago neto al productor
        $fields['producer_payout'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Pago al productor'))
            ->setDescription(t('Importe neto a transferir al productor.'))
            ->setRequired(TRUE)
            ->setDefaultValue('0.00')
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Stripe Transfer ID
        $fields['stripe_transfer_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Stripe Transfer'))
            ->setDescription(t('ID del Transfer de Stripe al productor.'))
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado del payout
        $fields['payout_state'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Estado payout'))
            ->setDescription(t('Estado de la transferencia: pending, transferred, failed.'))
            ->setDefaultValue(self::PAYOUT_PENDING)
            ->setSetting('max_length', 32)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Número de seguimiento
        $fields['tracking_number'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Número de seguimiento'))
            ->setDescription(t('Número de tracking del envío.'))
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // URL de tracking
        $fields['tracking_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL de seguimiento'))
            ->setDescription(t('URL para seguimiento del envío.'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de envío
        $fields['shipped_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de envío'))
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de entrega
        $fields['delivered_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de entrega'))
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Notas del productor
        $fields['producer_notes'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Notas del productor'))
            ->setDescription(t('Notas del productor sobre este sub-pedido.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tenant ID
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Organización propietaria.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Campos de sistema
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Obtiene los envíos asociados a este sub-pedido.
     *
     * @return \Drupal\jaraba_agroconecta_core\Entity\AgroShipmentInterface[]
     */
    public function getShipments(): array {
      return \Drupal::entityTypeManager()->getStorage('agro_shipment')->loadByProperties([
        'sub_order_id' => $this->id(),
      ]);
    }

    /**
     * Calcula la comisión y el payout del productor.
     *
     * @param float $commissionRate
     *   Porcentaje de comisión (ej: 5.0 para 5%).
     */
    public function calculateCommission(float $commissionRate): void
    {
        $subtotal = (float) $this->get('subtotal')->value;
        $commission = round($subtotal * ($commissionRate / 100), 2);
        $payout = round($subtotal - $commission, 2);

        $this->set('commission_rate', $commissionRate);
        $this->set('commission_amount', $commission);
        $this->set('producer_payout', $payout);
    }

}
