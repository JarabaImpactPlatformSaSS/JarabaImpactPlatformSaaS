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
 * Define la entidad PromotionAgro.
 *
 * Representa una promoción o descuento aplicable a productos, categorías
 * o al carrito completo. Soporta descuentos porcentuales, fijos, envío
 * gratis, compra-X-lleva-Y. Configurable por rango de fechas, mínimos
 * de compra y productos/categorías objetivo.
 *
 * TIPOS DE DESCUENTO:
 * - percentage: Porcentaje sobre el subtotal (ej: 15% de descuento).
 * - fixed_amount: Cantidad fija de descuento (ej: 5€ de descuento).
 * - free_shipping: Envío gratuito.
 * - buy_x_get_y: Compra X unidades, lleva Y gratis.
 *
 * @ContentEntityType(
 *   id = "promotion_agro",
 *   label = @Translation("Promoción Agro"),
 *   label_collection = @Translation("Promociones Agro"),
 *   label_singular = @Translation("promoción agro"),
 *   label_plural = @Translation("promociones agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\PromotionAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\PromotionAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\PromotionAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\PromotionAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\PromotionAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "promotion_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.promotion_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-promotions/{promotion_agro}",
 *     "add-form" = "/admin/content/agro-promotions/add",
 *     "edit-form" = "/admin/content/agro-promotions/{promotion_agro}/edit",
 *     "delete-form" = "/admin/content/agro-promotions/{promotion_agro}/delete",
 *     "collection" = "/admin/content/agro-promotions",
 *   },
 * )
 */
class PromotionAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Constantes de tipos de descuento.
     */
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed_amount';
    const TYPE_FREE_SHIPPING = 'free_shipping';
    const TYPE_BUY_X_GET_Y = 'buy_x_get_y';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Tenant ID.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Marketplace propietario de esta promoción.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Nombre de la promoción.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre interno de la promoción (ej: Rebajas de Verano 2026).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descripción pública.
        $fields['description'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Texto visible para el cliente en el checkout (ej: ¡15% en todos los aceites!).'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -9,
                'settings' => ['rows' => 3],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de descuento.
        $fields['discount_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de descuento'))
            ->setDescription(t('Mecanismo de descuento aplicado.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::TYPE_PERCENTAGE => t('Porcentaje'),
                self::TYPE_FIXED => t('Cantidad fija'),
                self::TYPE_FREE_SHIPPING => t('Envío gratis'),
                self::TYPE_BUY_X_GET_Y => t('Compra X lleva Y'),
            ])
            ->setDefaultValue(self::TYPE_PERCENTAGE)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Valor del descuento (porcentaje o cantidad).
        $fields['discount_value'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Valor del descuento'))
            ->setDescription(t('Porcentaje (ej: 15.00) o cantidad fija en € (ej: 5.00).'))
            ->setRequired(TRUE)
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue('0.00')
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Pedido mínimo para aplicar.
        $fields['minimum_order'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Pedido mínimo'))
            ->setDescription(t('Importe mínimo del carrito para aplicar la promoción (0 = sin mínimo).'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue('0.00')
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descuento máximo aplicable (tope).
        $fields['max_discount'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Descuento máximo'))
            ->setDescription(t('Tope máximo de descuento en € (0 = sin tope). Útil para porcentajes altos.'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue('0.00')
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de inicio.
        $fields['start_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de inicio'))
            ->setDescription(t('Inicio de la vigencia de la promoción.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de fin.
        $fields['end_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de fin'))
            ->setDescription(t('Fin de la vigencia. Si vacía, la promoción no caduca.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Usos máximos totales.
        $fields['max_uses'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Usos máximos'))
            ->setDescription(t('Número máximo de veces que se puede usar (0 = ilimitado).'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Contador de usos actuales.
        $fields['current_uses'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Usos actuales'))
            ->setDescription(t('Número de veces que se ha aplicado.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('form', FALSE)
            ->setDisplayConfigurable('view', TRUE);

        // Usos por usuario.
        $fields['max_uses_per_user'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Usos por usuario'))
            ->setDescription(t('Límite de usos por usuario individual (0 = ilimitado).'))
            ->setDefaultValue(1)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Categorías objetivo (JSON array de IDs).
        $fields['target_categories'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Categorías objetivo'))
            ->setDescription(t('IDs de categorías donde aplica (JSON). Vacío = todas las categorías.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 1,
                'settings' => ['rows' => 2],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Productos objetivo (JSON array de IDs).
        $fields['target_products'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Productos objetivo'))
            ->setDescription(t('IDs de productos donde aplica (JSON). Vacío = todos los productos.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 2,
                'settings' => ['rows' => 2],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Config extra Buy X Get Y (JSON).
        $fields['bxgy_config'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Configuración Compra X Lleva Y'))
            ->setDescription(t('JSON: {"buy_quantity": 3, "get_quantity": 1, "get_product_id": null}.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 3,
                'settings' => ['rows' => 2],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // ¿Acumulable con otras promociones?
        $fields['stackable'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Acumulable'))
            ->setDescription(t('¿Se puede combinar con otras promociones activas?'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Prioridad (para resolver conflictos).
        $fields['priority'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Prioridad'))
            ->setDescription(t('Mayor prioridad = se evalúa primero. Útil cuando varias promociones aplican.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado activo.
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDescription(t('Solo las promociones activas pueden aplicarse.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Campos de sistema.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    // ===================================================
    // Helpers
    // ===================================================

    /**
     * Obtiene el tipo de descuento.
     */
    public function getDiscountType(): string
    {
        return $this->get('discount_type')->value ?? self::TYPE_PERCENTAGE;
    }

    /**
     * Obtiene la etiqueta legible del tipo de descuento.
     */
    public function getDiscountTypeLabel(): string
    {
        $labels = [
            self::TYPE_PERCENTAGE => t('Porcentaje'),
            self::TYPE_FIXED => t('Cantidad fija'),
            self::TYPE_FREE_SHIPPING => t('Envío gratis'),
            self::TYPE_BUY_X_GET_Y => t('Compra X lleva Y'),
        ];
        return (string) ($labels[$this->getDiscountType()] ?? $this->getDiscountType());
    }

    /**
     * Obtiene el valor del descuento.
     */
    public function getDiscountValue(): float
    {
        return (float) ($this->get('discount_value')->value ?? 0);
    }

    /**
     * Obtiene el pedido mínimo requerido.
     */
    public function getMinimumOrder(): float
    {
        return (float) ($this->get('minimum_order')->value ?? 0);
    }

    /**
     * Obtiene el descuento máximo permitido.
     */
    public function getMaxDiscount(): float
    {
        return (float) ($this->get('max_discount')->value ?? 0);
    }

    /**
     * Indica si la promoción está activa.
     */
    public function isActive(): bool
    {
        return (bool) $this->get('is_active')->value;
    }

    /**
     * Indica si la promoción está vigente por fechas.
     */
    public function isWithinDateRange(): bool
    {
        $now = new \DateTime();
        $start = $this->get('start_date')->value;
        $end = $this->get('end_date')->value;

        if ($start && new \DateTime($start) > $now) {
            return FALSE;
        }
        if ($end && new \DateTime($end) < $now) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Indica si quedan usos disponibles.
     */
    public function hasUsesRemaining(): bool
    {
        $maxUses = (int) $this->get('max_uses')->value;
        if ($maxUses === 0) {
            return TRUE; // Ilimitado.
        }
        return (int) $this->get('current_uses')->value < $maxUses;
    }

    /**
     * Indica si es acumulable con otras.
     */
    public function isStackable(): bool
    {
        return (bool) $this->get('stackable')->value;
    }

    /**
     * Obtiene las categorías objetivo como array de IDs.
     */
    public function getTargetCategories(): array
    {
        $json = $this->get('target_categories')->value;
        if (empty($json)) {
            return [];
        }
        $decoded = json_decode($json, TRUE);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Obtiene los productos objetivo como array de IDs.
     */
    public function getTargetProducts(): array
    {
        $json = $this->get('target_products')->value;
        if (empty($json)) {
            return [];
        }
        $decoded = json_decode($json, TRUE);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Obtiene la configuración BXGY.
     */
    public function getBxgyConfig(): array
    {
        $json = $this->get('bxgy_config')->value;
        if (empty($json)) {
            return ['buy_quantity' => 0, 'get_quantity' => 0, 'get_product_id' => NULL];
        }
        $decoded = json_decode($json, TRUE);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Incrementa el contador de usos.
     */
    public function incrementUses(): self
    {
        $current = (int) $this->get('current_uses')->value;
        $this->set('current_uses', $current + 1);
        return $this;
    }

    /**
     * Obtiene la prioridad.
     */
    public function getPriority(): int
    {
        return (int) ($this->get('priority')->value ?? 0);
    }

    /**
     * Formatea el descuento para mostrar al usuario.
     */
    public function getFormattedDiscount(): string
    {
        $type = $this->getDiscountType();
        $value = $this->getDiscountValue();

        return match ($type) {
            self::TYPE_PERCENTAGE => number_format($value, 0) . '%',
            self::TYPE_FIXED => number_format($value, 2, ',', '.') . ' €',
            self::TYPE_FREE_SHIPPING => (string) t('Envío gratis'),
            self::TYPE_BUY_X_GET_Y => (string) t('Compra @buy lleva @get', [
                '@buy' => $this->getBxgyConfig()['buy_quantity'] ?? 0,
                '@get' => $this->getBxgyConfig()['get_quantity'] ?? 0,
            ]),
            default => (string) $value,
        };
    }

}
