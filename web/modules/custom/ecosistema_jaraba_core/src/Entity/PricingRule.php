<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Entidad de regla de precios por métrica y plan.
 *
 * PROPÓSITO:
 * Permite definir precios personalizados por métrica (api_calls, ai_tokens,
 * storage_mb, etc.) y plan SaaS, reemplazando los precios hardcodeados
 * en TenantMeteringService::UNIT_PRICES.
 *
 * LÓGICA:
 * Soporta 4 modelos de pricing:
 * - flat: precio fijo por unidad
 * - tiered: precio escalonado (ej: 0-100 gratis, 101-1000 a €0.01)
 * - volume: precio según volumen total (el tier aplica a TODAS las unidades)
 * - package: bloques de N unidades a precio fijo
 *
 * DIRECTRICES:
 * - Cada regla se asocia a un plan + métrica (combinación única)
 * - PricingRuleEngine evalúa en cascada: PricingRule > TenantMeteringService defaults
 * - El campo tiers almacena JSON con estructura [{from, to, price}]
 * - included_quantity define cantidad gratuita incluida en el plan
 *
 * @ContentEntityType(
 *   id = "pricing_rule",
 *   label = @Translation("Pricing Rule"),
 *   label_collection = @Translation("Pricing Rules"),
 *   label_singular = @Translation("pricing rule"),
 *   label_plural = @Translation("pricing rules"),
 *   label_count = @PluralTranslation(
 *     singular = "@count pricing rule",
 *     plural = "@count pricing rules",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\PricingRuleListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\ecosistema_jaraba_core\Form\PricingRuleForm",
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\PricingRuleForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\PricingRuleForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ecosistema_jaraba_core\Access\PricingRuleAccessControlHandler",
 *   },
 *   base_table = "pricing_rule",
 *   data_table = "pricing_rule_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer pricing rules",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/pricing-rules",
 *     "add-form" = "/admin/structure/pricing-rules/add",
 *     "canonical" = "/admin/structure/pricing-rules/{pricing_rule}",
 *     "edit-form" = "/admin/structure/pricing-rules/{pricing_rule}/edit",
 *     "delete-form" = "/admin/structure/pricing-rules/{pricing_rule}/delete",
 *   },
 *   field_ui_base_route = "entity.pricing_rule.collection",
 * )
 */
class PricingRule extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Modelos de pricing soportados.
   */
  public const MODEL_FLAT = 'flat';
  public const MODEL_TIERED = 'tiered';
  public const MODEL_VOLUME = 'volume';
  public const MODEL_PACKAGE = 'package';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Nombre descriptivo de la regla.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre descriptivo de la regla de precios (ej: "API Calls - Plan Pro").'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Plan SaaS asociado (entity_reference a saas_plan).
    $fields['plan_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Plan SaaS'))
      ->setDescription(t('Plan al que aplica esta regla de precios. Dejar vacío para regla global por defecto.'))
      ->setSetting('target_type', 'saas_plan')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -8,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tipo de métrica (api_calls, ai_tokens, storage_mb, etc.).
    $fields['metric_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Métrica'))
      ->setDescription(t('Métrica de uso a la que aplica el precio.'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'api_calls' => 'Llamadas API',
          'ai_tokens' => 'Tokens IA',
          'storage_mb' => 'Almacenamiento (MB)',
          'orders' => 'Pedidos procesados',
          'products' => 'Productos activos',
          'customers' => 'Clientes',
          'emails_sent' => 'Emails enviados',
          'bandwidth_gb' => 'Ancho de banda (GB)',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Modelo de pricing.
    $fields['pricing_model'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modelo de Precios'))
      ->setDescription(t('Cómo se calcula el coste: flat (fijo), tiered (escalonado), volume (volumen), package (paquete).'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::MODEL_FLAT)
      ->setSettings([
        'allowed_values' => [
          self::MODEL_FLAT => 'Precio fijo por unidad',
          self::MODEL_TIERED => 'Escalonado (graduated)',
          self::MODEL_VOLUME => 'Volumen (all-units)',
          self::MODEL_PACKAGE => 'Paquete (bloque)',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Cantidad incluida gratis en el plan.
    $fields['included_quantity'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Cantidad Incluida'))
      ->setDescription(t('Unidades gratuitas incluidas en el plan antes de empezar a cobrar.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Precio unitario (solo para modelo flat).
    $fields['unit_price'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Precio Unitario (€)'))
      ->setDescription(t('Precio por unidad en euros. Aplica solo al modelo flat.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -4,
        'settings' => [
          'decimal_separator' => ',',
          'thousand_separator' => '.',
          'prefix_suffix' => TRUE,
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tiers JSON (para modelos tiered, volume, package).
    $fields['tiers'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Escalones de Precios (JSON)'))
      ->setDescription(t('Definición JSON de escalones. Formato: [{"from":0,"to":100,"price":0},{"from":101,"to":1000,"price":0.01}]'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -3,
        'settings' => [
          'rows' => 6,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Moneda (ISO 4217).
    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Moneda'))
      ->setDescription(t('Código ISO 4217 de la moneda (EUR, USD, GBP).'))
      ->setDefaultValue('EUR')
      ->setSettings([
        'max_length' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Estado activo/inactivo.
    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activa'))
      ->setDescription(t('Solo las reglas activas se aplican al calcular costes.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creación'))
      ->setDescription(t('Fecha en que se creó la regla de precios.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de modificación'))
      ->setDescription(t('Fecha de la última modificación.'));

    return $fields;
  }

  /**
   * Obtiene los tiers decodificados como array.
   *
   * @return array
   *   Array de tiers con keys: from, to, price.
   */
  public function getDecodedTiers(): array {
    $raw = $this->get('tiers')->value;
    if (empty($raw)) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

}
