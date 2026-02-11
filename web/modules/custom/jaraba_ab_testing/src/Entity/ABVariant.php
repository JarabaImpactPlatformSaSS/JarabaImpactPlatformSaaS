<?php

namespace Drupal\jaraba_ab_testing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Variante A/B.
 *
 * Estructura: Entidad hija de ABExperiment que representa una variante
 *   individual dentro de un experimento A/B. Contiene datos de
 *   identificación (nombre, variant_key), referencia al experimento
 *   padre (experiment_id), configuración (es control, peso de tráfico,
 *   datos JSON), métricas de rendimiento (visitantes, conversiones,
 *   ingresos), multi-tenant (tenant_id) y metadatos temporales.
 *
 * Lógica: Cada ABVariant pertenece a exactamente un ABExperiment vía
 *   experiment_id. El campo is_control marca la variante de referencia
 *   (grupo de control). El campo traffic_weight determina qué
 *   porcentaje del tráfico del experimento recibe esta variante.
 *   variant_data almacena configuración específica en JSON (contenido,
 *   estilos, precios, etc.). Los campos visitors, conversions y
 *   revenue se actualizan atómicamente mediante los métodos
 *   incrementVisitors(), incrementConversions() y addRevenue().
 *
 * Relaciones: Pertenece a un ABExperiment (experiment_id). Pertenece
 *   a un Tenant (tenant_id). Puede ser referenciada por
 *   ABExperiment.winner_variant.
 *
 * Sintaxis: Content Entity con base_table propia, sin bundles.
 *   Usa EntityChangedTrait para timestamps automáticos.
 *
 * @ContentEntityType(
 *   id = "ab_variant",
 *   label = @Translation("A/B Variant"),
 *   label_collection = @Translation("Variantes A/B"),
 *   label_singular = @Translation("variante A/B"),
 *   label_plural = @Translation("variantes A/B"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_ab_testing\ListBuilder\ABVariantListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_ab_testing\Form\ABVariantForm",
 *       "add" = "Drupal\jaraba_ab_testing\Form\ABVariantForm",
 *       "edit" = "Drupal\jaraba_ab_testing\Form\ABVariantForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_ab_testing\Access\ABVariantAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ab_variant",
 *   admin_permission = "administer ab testing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/ab-variant/{ab_variant}",
 *     "add-form" = "/admin/content/ab-variant/add",
 *     "edit-form" = "/admin/content/ab-variant/{ab_variant}/edit",
 *     "delete-form" = "/admin/content/ab-variant/{ab_variant}/delete",
 *     "collection" = "/admin/content/ab-variants",
 *   },
 *   field_ui_base_route = "jaraba_ab_testing.variant.settings",
 * )
 */
class ABVariant extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // 1. IDENTITY
    // =========================================================================

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de la Variante'))
      ->setDescription(t('Nombre descriptivo de la variante, p.ej. "Control", "Variante A".'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['variant_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Clave de Variante'))
      ->setDescription(t('Identificador técnico de la variante, p.ej. "control", "variant_a", "variant_b".'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 2. EXPERIMENT
    // =========================================================================

    $fields['experiment_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Experimento'))
      ->setDescription(t('Experimento A/B al que pertenece esta variante.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'ab_experiment')
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 3. CONFIG
    // =========================================================================

    $fields['is_control'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Es Control'))
      ->setDescription(t('Marca esta variante como el grupo de control (referencia).'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['traffic_weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Peso de Tráfico'))
      ->setDescription(t('Porcentaje de tráfico del experimento que recibe esta variante (0-100).'))
      ->setRequired(TRUE)
      ->setDefaultValue(50)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['variant_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Datos de la Variante'))
      ->setDescription(t('JSON con la configuración específica de esta variante (contenido, estilos, precios, etc.).'))
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 4. METRICS
    // =========================================================================

    $fields['visitors'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Visitantes'))
      ->setDescription(t('Número total de visitantes asignados a esta variante.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['conversions'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Conversiones'))
      ->setDescription(t('Número total de conversiones registradas en esta variante.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['revenue'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Ingresos (EUR)'))
      ->setDescription(t('Ingresos totales generados por esta variante.'))
      ->setDefaultValue('0')
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 17])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 5. MULTI-TENANT
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece esta variante.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 6. METADATA
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

  /**
   * Calcula la tasa de conversión de esta variante.
   *
   * Estructura: Método helper que combina visitors y conversions.
   * Lógica: Si visitors es 0, devuelve 0.0 para evitar división
   *   por cero. En caso contrario, devuelve el cociente
   *   conversions / visitors como float.
   * Sintaxis: Operación aritmética con protección contra división por cero.
   *
   * @return float
   *   Tasa de conversión entre 0.0 y 1.0.
   */
  public function getConversionRate(): float {
    $visitors = (int) $this->get('visitors')->value;
    if ($visitors === 0) {
      return 0.0;
    }
    $conversions = (int) $this->get('conversions')->value;
    return $conversions / $visitors;
  }

  /**
   * Incrementa el contador de visitantes en 1.
   *
   * Estructura: Método mutador sobre el campo visitors.
   * Lógica: Suma 1 al valor actual de visitors. No guarda la entidad;
   *   el llamador debe invocar save() explícitamente.
   * Sintaxis: Escritura directa sobre campo integer sin retorno.
   */
  public function incrementVisitors(): void {
    $current = (int) $this->get('visitors')->value;
    $this->set('visitors', $current + 1);
  }

  /**
   * Incrementa el contador de conversiones en 1.
   *
   * Estructura: Método mutador sobre el campo conversions.
   * Lógica: Suma 1 al valor actual de conversions. No guarda la entidad;
   *   el llamador debe invocar save() explícitamente.
   * Sintaxis: Escritura directa sobre campo integer sin retorno.
   */
  public function incrementConversions(): void {
    $current = (int) $this->get('conversions')->value;
    $this->set('conversions', $current + 1);
  }

  /**
   * Añade ingresos a la variante.
   *
   * Estructura: Método mutador sobre el campo revenue.
   * Lógica: Suma el importe recibido al valor actual de revenue.
   *   No guarda la entidad; el llamador debe invocar save()
   *   explícitamente.
   * Sintaxis: Operación aritmética sobre campo decimal.
   *
   * @param float $amount
   *   Importe a añadir en EUR.
   */
  public function addRevenue(float $amount): void {
    $current = (float) $this->get('revenue')->value;
    $this->set('revenue', $current + $amount);
  }

  /**
   * Comprueba si esta variante es el grupo de control.
   *
   * Estructura: Método helper que evalúa el campo is_control.
   * Lógica: Devuelve el valor booleano del campo is_control.
   * Sintaxis: Lectura directa del valor del campo boolean.
   *
   * @return bool
   *   TRUE si esta variante es el grupo de control.
   */
  public function isControl(): bool {
    return (bool) $this->get('is_control')->value;
  }

}
