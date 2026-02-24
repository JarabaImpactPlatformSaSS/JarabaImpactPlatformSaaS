<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad OrderAgro.
 *
 * Representa un pedido completo en el marketplace AgroConecta.
 * Un pedido puede contener items de múltiples productores y se divide
 * automáticamente en sub-pedidos (SuborderAgro) por productor.
 *
 * @ContentEntityType(
 *   id = "order_agro",
 *   label = @Translation("Pedido Agro"),
 *   label_collection = @Translation("Pedidos Agro"),
 *   label_singular = @Translation("pedido agro"),
 *   label_plural = @Translation("pedidos agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\OrderAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\OrderAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\OrderAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\OrderAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\OrderAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "order_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.order_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "order_number",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-orders/{order_agro}",
 *     "add-form" = "/admin/content/agro-orders/add",
 *     "edit-form" = "/admin/content/agro-orders/{order_agro}/edit",
 *     "delete-form" = "/admin/content/agro-orders/{order_agro}/delete",
 *     "collection" = "/admin/content/agro-orders",
 *   },
 * )
 */
class OrderAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Estados válidos del pedido.
     */
    const STATE_DRAFT = 'draft';
    const STATE_PENDING = 'pending';
    const STATE_PAID = 'paid';
    const STATE_PROCESSING = 'processing';
    const STATE_READY = 'ready';
    const STATE_SHIPPED = 'shipped';
    const STATE_PICKED_UP = 'picked_up';
    const STATE_DELIVERED = 'delivered';
    const STATE_COMPLETED = 'completed';
    const STATE_CANCELLED = 'cancelled';
    const STATE_RETURN_REQUESTED = 'return_requested';
    const STATE_RETURNED = 'returned';

    /**
     * Returns all state labels keyed by state constant.
     *
     * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
     *   Associative array of state => translated label.
     */
    public static function getStateLabels(): array
    {
        return [
            self::STATE_DRAFT => t('Carrito'),
            self::STATE_PENDING => t('Pendiente de pago'),
            self::STATE_PAID => t('Pagado'),
            self::STATE_PROCESSING => t('En preparación'),
            self::STATE_READY => t('Listo para envío'),
            self::STATE_SHIPPED => t('Enviado'),
            self::STATE_PICKED_UP => t('Recogido'),
            self::STATE_DELIVERED => t('Entregado'),
            self::STATE_COMPLETED => t('Completado'),
            self::STATE_CANCELLED => t('Cancelado'),
            self::STATE_RETURN_REQUESTED => t('Devolución solicitada'),
            self::STATE_RETURNED => t('Devuelto'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Número de pedido público (AGR-YYYYMMDD-XXXX)
        $fields['order_number'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Número de pedido'))
            ->setDescription(t('Número público del pedido (AGR-YYYYMMDD-XXXX).'))
            ->setSetting('max_length', 32)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tenant ID para multi-tenancy
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Marketplace donde se realizó el pedido.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Cliente (usuario Drupal)
        $fields['customer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Cliente'))
            ->setDescription(t('Usuario que realizó el pedido.'))
            ->setSetting('target_type', 'user')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Email de contacto
        $fields['email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email'))
            ->setDescription(t('Email de contacto del cliente.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Teléfono
        $fields['phone'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Teléfono'))
            ->setDescription(t('Teléfono de contacto.'))
            ->setSetting('max_length', 20)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado del pedido (state machine)
        $fields['state'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado actual del pedido.'))
            ->setDefaultValue(self::STATE_DRAFT)
            ->setSetting('max_length', 32)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Dirección de facturación (JSON serializado)
        $fields['billing_address'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Dirección de facturación'))
            ->setDescription(t('Dirección de facturación en formato JSON.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Dirección de envío (JSON serializado)
        $fields['shipping_address'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Dirección de envío'))
            ->setDescription(t('Dirección de envío en formato JSON.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Método de entrega
        $fields['delivery_method'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Método de entrega'))
            ->setDescription(t('Tipo de entrega: shipping, pickup_origin, pickup_point.'))
            ->setDefaultValue('shipping')
            ->setSetting('max_length', 32)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha preferida de entrega
        $fields['delivery_date_preferred'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha preferida de entrega'))
            ->setDescription(t('Fecha preferida por el cliente para la entrega.'))
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Notas de entrega
        $fields['delivery_notes'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Notas de entrega'))
            ->setDescription(t('Instrucciones especiales para la entrega.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Subtotal (sin envío)
        $fields['subtotal'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Subtotal'))
            ->setDescription(t('Suma de items sin envío.'))
            ->setDefaultValue('0.00')
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Coste de envío total
        $fields['shipping_total'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Envío total'))
            ->setDescription(t('Coste total de envío.'))
            ->setDefaultValue('0.00')
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descuentos
        $fields['discount_total'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Descuento total'))
            ->setDescription(t('Descuentos aplicados.'))
            ->setDefaultValue('0.00')
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // IVA total
        $fields['tax_total'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('IVA total'))
            ->setDescription(t('Total de impuestos.'))
            ->setDefaultValue('0.00')
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Total final
        $fields['total'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Total'))
            ->setDescription(t('Total final del pedido.'))
            ->setDefaultValue('0.00')
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Moneda
        $fields['currency'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Moneda'))
            ->setDescription(t('Código de moneda ISO 4217.'))
            ->setDefaultValue('EUR')
            ->setSetting('max_length', 3)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Método de pago
        $fields['payment_method'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Método de pago'))
            ->setDescription(t('Método de pago usado (card, bizum, google_pay, etc.).'))
            ->setSetting('max_length', 32)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado del pago
        $fields['payment_state'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Estado del pago'))
            ->setDescription(t('Estado del pago: pending, authorized, paid, refunded, failed.'))
            ->setDefaultValue('pending')
            ->setSetting('max_length', 32)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Stripe PaymentIntent ID
        $fields['stripe_payment_intent'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Stripe PaymentIntent'))
            ->setDescription(t('ID del PaymentIntent de Stripe.'))
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Notas del cliente
        $fields['customer_notes'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Notas del cliente'))
            ->setDescription(t('Notas escritas por el cliente.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Notas internas (admin)
        $fields['internal_notes'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Notas internas'))
            ->setDescription(t('Notas internas para administración.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de confirmación
        $fields['placed_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de confirmación'))
            ->setDescription(t('Fecha/hora en que el pedido fue confirmado.'))
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de completado
        $fields['completed_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de completado'))
            ->setDescription(t('Fecha/hora en que el pedido fue completado.'))
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Campos de sistema
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Genera el número de pedido automáticamente.
     *
     * @return string
     *   Número de pedido con formato AGR-YYYYMMDD-XXXX.
     */
    public static function generateOrderNumber(): string
    {
        return 'AGR-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene el total formateado.
     *
     * @return string
     *   El total con formato (ej: "89,95 €").
     */
    public function getFormattedTotal(): string
    {
        $total = $this->get('total')->value ?? 0;
        $currency = $this->get('currency')->value ?? 'EUR';
        return number_format((float) $total, 2, ',', '.') . ' ' . ($currency === 'EUR' ? '€' : $currency);
    }

    /**
     * Verifica si el pedido puede ser cancelado.
     *
     * @return bool
     *   TRUE si el pedido puede cancelarse.
     */
    public function isCancellable(): bool
    {
        return in_array($this->get('state')->value, [
            self::STATE_DRAFT,
            self::STATE_PENDING,
            self::STATE_PAID,
        ]);
    }

    /**
     * Obtiene la etiqueta legible del estado.
     *
     * @return string
     *   Etiqueta traducida del estado.
     */
    public function getStateLabel(): string
    {
        $labels = static::getStateLabels();
        return (string) ($labels[$this->get('state')->value] ?? $this->get('state')->value);
    }

}
