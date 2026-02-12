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
 * Define la entidad ShippingMethodAgro.
 *
 * Representa un método de envío disponible (ej: Estándar, Express,
 * Punto de Recogida, Envío Frío). Cada método se asocia a zonas
 * de envío y define tarifas por peso/precio.
 *
 * TIPOS DE CÁLCULO DE TARIFA:
 * - flat_rate: Tarifa fija independiente del peso/precio.
 * - weight_based: Tarifa por tramo de peso.
 * - price_based: Tarifa por tramo de precio del pedido.
 * - free: Siempre gratis.
 *
 * @ContentEntityType(
 *   id = "shipping_method_agro",
 *   label = @Translation("Método de Envío Agro"),
 *   label_collection = @Translation("Métodos de Envío Agro"),
 *   label_singular = @Translation("método de envío agro"),
 *   label_plural = @Translation("métodos de envío agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\ShippingMethodAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\ShippingMethodAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\ShippingMethodAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\ShippingMethodAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\ShippingMethodAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "shipping_method_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.shipping_method_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-shipping-methods/{shipping_method_agro}",
 *     "add-form" = "/admin/content/agro-shipping-methods/add",
 *     "edit-form" = "/admin/content/agro-shipping-methods/{shipping_method_agro}/edit",
 *     "delete-form" = "/admin/content/agro-shipping-methods/{shipping_method_agro}/delete",
 *     "collection" = "/admin/content/agro-shipping-methods",
 *   },
 * )
 */
class ShippingMethodAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    const CALC_FLAT = 'flat_rate';
    const CALC_WEIGHT = 'weight_based';
    const CALC_PRICE = 'price_based';
    const CALC_FREE = 'free';

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
            ->setDescription(t('Marketplace propietario.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Nombre del método.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre visible (ej: Envío Estándar, Express 24h, Recogida en Tienda).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descripción para el cliente.
        $fields['description'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Detalle visible en el checkout (ej: Entrega en 3-5 días laborables).'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -9,
                'settings' => ['rows' => 2],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de cálculo.
        $fields['calculation_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de cálculo'))
            ->setDescription(t('Cómo se calcula la tarifa de envío.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::CALC_FLAT => t('Tarifa fija'),
                self::CALC_WEIGHT => t('Por peso'),
                self::CALC_PRICE => t('Por precio pedido'),
                self::CALC_FREE => t('Gratis'),
            ])
            ->setDefaultValue(self::CALC_FLAT)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tarifa base (flat rate o mínimo).
        $fields['base_rate'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Tarifa base'))
            ->setDescription(t('Coste fijo de envío en € (flat rate) o tarifa mínima.'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue('0.00')
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tabla de tarifas por tramo (JSON).
        $fields['rate_table'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Tabla de tarifas'))
            ->setDescription(t('JSON con tramos: [{"min":0,"max":5,"rate":3.50},{"min":5,"max":10,"rate":5.00}]. Aplica según tipo de cálculo.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -6,
                'settings' => ['rows' => 4],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Envío gratis a partir de X €.
        $fields['free_threshold'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Envío gratis desde'))
            ->setDescription(t('Importe del pedido desde el cual el envío es gratis (0 = no aplica).'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue('0.00')
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Plazo de entrega estimado (texto).
        $fields['delivery_estimate'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Plazo de entrega'))
            ->setDescription(t('Estimación visible (ej: 3-5 días laborables, 24 horas, Recogida inmediata).'))
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Peso máximo soportado (kg).
        $fields['max_weight'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Peso máximo (kg)'))
            ->setDescription(t('Peso máximo soportado por este método (0 = sin límite).'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue('0.00')
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Zonas de envío (JSON array de IDs de ShippingZoneAgro).
        $fields['zone_ids'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Zonas de envío'))
            ->setDescription(t('JSON array de IDs de zonas donde está disponible este método. Vacío = todas.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -2,
                'settings' => ['rows' => 2],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // ¿Requiere envío frío?
        $fields['requires_cold_chain'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Cadena de frío'))
            ->setDescription(t('Indica si este método soporta cadena de frío para productos perecederos.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Posición para ordenación.
        $fields['position'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Posición'))
            ->setDescription(t('Orden de aparición en el checkout.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado activo.
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDescription(t('Solo los métodos activos aparecen en el checkout.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 1,
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
     * Obtiene el tipo de cálculo.
     */
    public function getCalculationType(): string
    {
        return $this->get('calculation_type')->value ?? self::CALC_FLAT;
    }

    /**
     * Obtiene la tarifa base.
     */
    public function getBaseRate(): float
    {
        return (float) ($this->get('base_rate')->value ?? 0);
    }

    /**
     * Obtiene la tabla de tarifas.
     */
    public function getRateTable(): array
    {
        $json = $this->get('rate_table')->value;
        if (empty($json)) {
            return [];
        }
        $decoded = json_decode($json, TRUE);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Obtiene el umbral de envío gratis.
     */
    public function getFreeThreshold(): float
    {
        return (float) ($this->get('free_threshold')->value ?? 0);
    }

    /**
     * Obtiene los IDs de zonas.
     */
    public function getZoneIds(): array
    {
        $json = $this->get('zone_ids')->value;
        if (empty($json)) {
            return [];
        }
        $decoded = json_decode($json, TRUE);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Indica si está activo.
     */
    public function isActive(): bool
    {
        return (bool) $this->get('is_active')->value;
    }

    /**
     * Indica si requiere cadena de frío.
     */
    public function requiresColdChain(): bool
    {
        return (bool) $this->get('requires_cold_chain')->value;
    }

    /**
     * Calcula el coste de envío para un pedido.
     *
     * @param float $subtotal
     *   Subtotal del pedido en €.
     * @param float $totalWeight
     *   Peso total del pedido en kg.
     *
     * @return float
     *   Coste del envío en €.
     */
    public function calculateRate(float $subtotal, float $totalWeight = 0): float
    {
        // Envío gratis si supera el umbral.
        $threshold = $this->getFreeThreshold();
        if ($threshold > 0 && $subtotal >= $threshold) {
            return 0.0;
        }

        $type = $this->getCalculationType();

        return match ($type) {
            self::CALC_FREE => 0.0,
            self::CALC_FLAT => $this->getBaseRate(),
            self::CALC_WEIGHT => $this->calculateFromTable($totalWeight),
            self::CALC_PRICE => $this->calculateFromTable($subtotal),
            default => $this->getBaseRate(),
        };
    }

    /**
     * Busca la tarifa en la tabla de tramos.
     */
    protected function calculateFromTable(float $value): float
    {
        $table = $this->getRateTable();
        if (empty($table)) {
            return $this->getBaseRate();
        }

        foreach ($table as $tier) {
            $min = (float) ($tier['min'] ?? 0);
            $max = (float) ($tier['max'] ?? PHP_FLOAT_MAX);
            if ($value >= $min && $value < $max) {
                return (float) ($tier['rate'] ?? 0);
            }
        }

        // Si no encaja en ningún tramo, usar el último.
        $last = end($table);
        return (float) ($last['rate'] ?? $this->getBaseRate());
    }

    /**
     * Obtiene el plazo de entrega.
     */
    public function getDeliveryEstimate(): string
    {
        return $this->get('delivery_estimate')->value ?? '';
    }

    /**
     * Formatea la tarifa para el usuario.
     */
    public function getFormattedRate(float $subtotal, float $weight = 0): string
    {
        $rate = $this->calculateRate($subtotal, $weight);
        if ($rate <= 0) {
            return (string) t('Gratis');
        }
        return number_format($rate, 2, ',', '.') . ' €';
    }

}
