<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Regla de Pricing.
 *
 * Configura cómo se calcula el precio para cada métrica de uso.
 * Soporta modelos flat, tiered, per_unit y package.
 *
 * @ContentEntityType(
 *   id = "pricing_rule",
 *   label = @Translation("Regla de Pricing"),
 *   label_collection = @Translation("Reglas de Pricing"),
 *   label_singular = @Translation("regla de pricing"),
 *   label_plural = @Translation("reglas de pricing"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_usage_billing\PricingRuleListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_usage_billing\Form\PricingRuleForm",
 *       "add" = "Drupal\jaraba_usage_billing\Form\PricingRuleForm",
 *       "edit" = "Drupal\jaraba_usage_billing\Form\PricingRuleForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_usage_billing\Access\PricingRuleAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "pricing_rule",
 *   admin_permission = "administer usage billing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/pricing-rules/{pricing_rule}",
 *     "add-form" = "/admin/content/pricing-rules/add",
 *     "edit-form" = "/admin/content/pricing-rules/{pricing_rule}/edit",
 *     "delete-form" = "/admin/content/pricing-rules/{pricing_rule}/delete",
 *     "collection" = "/admin/content/pricing-rules",
 *   },
 *   field_ui_base_route = "jaraba_usage_billing.pricing_rule.settings",
 * )
 */
class PricingRule extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * Constantes para modelos de pricing.
   */
  public const MODEL_FLAT = 'flat';
  public const MODEL_TIERED = 'tiered';
  public const MODEL_PER_UNIT = 'per_unit';
  public const MODEL_PACKAGE = 'package';

  /**
   * Valores permitidos para pricing_model.
   */
  public const PRICING_MODELS = [
    self::MODEL_FLAT => 'Tarifa Plana',
    self::MODEL_TIERED => 'Escalonado',
    self::MODEL_PER_UNIT => 'Por Unidad',
    self::MODEL_PACKAGE => 'Paquete',
  ];

  /**
   * Constantes para estados.
   */
  public const STATUS_ACTIVE = 'active';
  public const STATUS_INACTIVE = 'inactive';

  /**
   * Valores permitidos para status.
   */
  public const STATUSES = [
    self::STATUS_ACTIVE => 'Activo',
    self::STATUS_INACTIVE => 'Inactivo',
  ];

  /**
   * Comprueba si la regla está activa.
   */
  public function isActive(): bool {
    return $this->get('status')->value === self::STATUS_ACTIVE;
  }

  /**
   * Obtiene la configuración de tiers decodificada.
   *
   * @return array
   *   Array de tiers o vacío.
   */
  public function getDecodedTiers(): array {
    $raw = $this->get('tiers_config')->value;
    if (!$raw) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre descriptivo de la regla de pricing.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['metric_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Métrica'))
      ->setDescription(t('Métrica a la que aplica esta regla de pricing.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['pricing_model'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modelo de Pricing'))
      ->setDescription(t('Tipo de modelo de cálculo de precio.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::MODEL_PER_UNIT)
      ->setSetting('allowed_values', self::PRICING_MODELS)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tiers_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuración de Tiers'))
      ->setDescription(t('JSON con la configuración de niveles/tiers de pricing.'))
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio por Unidad'))
      ->setDescription(t('Precio unitario base (para modelos flat y per_unit).'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 4)
      ->setDefaultValue('0.0000')
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Moneda'))
      ->setDescription(t('Código ISO de la moneda (EUR, USD, etc.).'))
      ->setRequired(TRUE)
      ->setDefaultValue('EUR')
      ->setSetting('max_length', 3)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant (group) al que aplica esta regla. Vacío = regla global.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado de la regla de pricing.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_ACTIVE)
      ->setSetting('allowed_values', self::STATUSES)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

}
