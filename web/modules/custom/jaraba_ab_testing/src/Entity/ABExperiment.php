<?php

namespace Drupal\jaraba_ab_testing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Experimento A/B.
 *
 * Estructura: Entidad central de jaraba_ab_testing que representa un
 *   experimento A/B (variante de página, email, pricing, CTA, feature
 *   flag o personalizado). Contiene datos de identificación (nombre,
 *   machine_name), configuración del experimento (tipo, hipótesis,
 *   métricas), segmentación de audiencia (target, porcentaje de
 *   tráfico), estado (draft, running, paused, completed, archived),
 *   configuración estadística (umbral de confianza, tamaño mínimo
 *   de muestra, días mínimos), programación (fechas inicio/fin,
 *   auto-completar), multi-tenant (tenant_id) y caché de resultados
 *   (visitantes totales, conversiones totales).
 *
 * Lógica: Un ABExperiment pertenece a un usuario (uid) y a un tenant
 *   (tenant_id). El campo status controla el ciclo de vida: solo los
 *   experimentos con estado 'running' reciben tráfico. El campo
 *   winner_variant referencia la variante ganadora cuando el
 *   experimento se completa. Los campos total_visitors y
 *   total_conversions son cachés agregados que se actualizan desde
 *   las variantes hijas. El sistema estadístico usa
 *   confidence_threshold, minimum_sample_size y minimum_runtime_days
 *   para determinar cuándo un resultado es significativo.
 *
 * Relaciones: Tiene múltiples ABVariant hijos vía experiment_id.
 *   winner_variant apunta a un ABVariant específico. tenant_id
 *   referencia la entidad Tenant del ecosistema. uid referencia
 *   al usuario creador.
 *
 * Sintaxis: Content Entity con base_table propia, sin bundles.
 *   Usa EntityChangedTrait para timestamps automáticos y
 *   EntityOwnerTrait para la relación con el usuario propietario.
 *
 * @ContentEntityType(
 *   id = "ab_experiment",
 *   label = @Translation("A/B Experiment"),
 *   label_collection = @Translation("Experimentos A/B"),
 *   label_singular = @Translation("experimento A/B"),
 *   label_plural = @Translation("experimentos A/B"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_ab_testing\ListBuilder\ABExperimentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_ab_testing\Form\ABExperimentForm",
 *       "add" = "Drupal\jaraba_ab_testing\Form\ABExperimentForm",
 *       "edit" = "Drupal\jaraba_ab_testing\Form\ABExperimentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_ab_testing\Access\ABExperimentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ab_experiment",
 *   admin_permission = "administer ab testing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/ab-experiment/{ab_experiment}",
 *     "add-form" = "/admin/content/ab-experiment/add",
 *     "edit-form" = "/admin/content/ab-experiment/{ab_experiment}/edit",
 *     "delete-form" = "/admin/content/ab-experiment/{ab_experiment}/delete",
 *     "collection" = "/admin/content/ab-experiments",
 *   },
 *   field_ui_base_route = "jaraba_ab_testing.experiment.settings",
 * )
 */
class ABExperiment extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // 1. IDENTITY
    // =========================================================================

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Experimento'))
      ->setDescription(t('Nombre descriptivo del experimento A/B.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine Name'))
      ->setDescription(t('Identificador único tipo slug, p.ej. homepage_cta_test. Se usa en la API y en el frontend.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 2. EXPERIMENT CONFIG
    // =========================================================================

    $fields['experiment_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Experimento'))
      ->setDescription(t('Categoría del experimento A/B.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'page_variant' => t('Variante de Página'),
        'email_variant' => t('Variante de Email'),
        'pricing_variant' => t('Variante de Pricing'),
        'cta_variant' => t('Variante de CTA'),
        'feature_flag' => t('Feature Flag'),
        'custom' => t('Personalizado'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hypothesis'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Hipótesis'))
      ->setDescription(t('Hipótesis que se quiere validar con este experimento.'))
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['primary_metric'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Métrica Principal'))
      ->setDescription(t('KPI primario para evaluar el resultado del experimento.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'conversion_rate' => t('Tasa de Conversión'),
        'click_rate' => t('Tasa de Clics'),
        'revenue' => t('Ingresos'),
        'engagement' => t('Engagement'),
        'custom' => t('Personalizada'),
      ])
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['secondary_metrics'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Métricas Secundarias'))
      ->setDescription(t('JSON con métricas adicionales a monitorizar, p.ej. ["bounce_rate","time_on_page"].'))
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 3. TARGETING
    // =========================================================================

    $fields['target_audience'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Audiencia Objetivo'))
      ->setDescription(t('Segmento de usuarios que participarán en el experimento.'))
      ->setRequired(TRUE)
      ->setDefaultValue('all')
      ->setSetting('allowed_values', [
        'all' => t('Todos los visitantes'),
        'new_visitors' => t('Nuevos visitantes'),
        'returning' => t('Visitantes recurrentes'),
        'segment' => t('Segmento específico'),
      ])
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['audience_segment'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID de Segmento'))
      ->setDescription(t('Identificador del segmento de audiencia cuando target_audience es "segment".'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['traffic_percentage'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Porcentaje de Tráfico'))
      ->setDescription(t('Porcentaje del tráfico total que se asigna al experimento (1-100).'))
      ->setRequired(TRUE)
      ->setDefaultValue(100)
      ->setSetting('min', 1)
      ->setSetting('max', 100)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 4. STATUS
    // =========================================================================

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado del Experimento'))
      ->setDescription(t('Estado actual del ciclo de vida del experimento.'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => t('Borrador'),
        'running' => t('En ejecución'),
        'paused' => t('Pausado'),
        'completed' => t('Completado'),
        'archived' => t('Archivado'),
      ])
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['winner_variant'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Variante Ganadora'))
      ->setDescription(t('Referencia a la variante declarada como ganadora.'))
      ->setSetting('target_type', 'ab_variant')
      ->setDisplayOptions('form', ['weight' => 16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 5. STATISTICAL CONFIG
    // =========================================================================

    $fields['confidence_threshold'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Umbral de Confianza'))
      ->setDescription(t('Nivel de confianza estadística requerido (0.00 a 1.00). Por defecto 0.95 (95%).'))
      ->setRequired(TRUE)
      ->setDefaultValue('0.95')
      ->setSetting('precision', 4)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['minimum_sample_size'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tamaño Mínimo de Muestra'))
      ->setDescription(t('Número mínimo de visitantes por variante antes de evaluar resultados.'))
      ->setRequired(TRUE)
      ->setDefaultValue(100)
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['minimum_runtime_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Días Mínimos de Ejecución'))
      ->setDescription(t('Número mínimo de días que debe ejecutarse el experimento.'))
      ->setRequired(TRUE)
      ->setDefaultValue(7)
      ->setDisplayOptions('form', ['weight' => 22])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 6. SCHEDULING
    // =========================================================================

    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Inicio'))
      ->setDescription(t('Fecha y hora de inicio del experimento.'))
      ->setDisplayOptions('form', ['weight' => 25])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Fin'))
      ->setDescription(t('Fecha y hora de finalización programada.'))
      ->setDisplayOptions('form', ['weight' => 26])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['auto_complete'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Auto-completar'))
      ->setDescription(t('Si TRUE, el experimento se completa automáticamente al alcanzar end_date o significancia estadística.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 27])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 7. MULTI-TENANT
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este experimento.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 8. METADATA
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    // =========================================================================
    // 9. RESULTS CACHE
    // =========================================================================

    $fields['total_visitors'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Visitantes'))
      ->setDescription(t('Caché agregado del total de visitantes en todas las variantes.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 35])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_conversions'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Conversiones'))
      ->setDescription(t('Caché agregado del total de conversiones en todas las variantes.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 36])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Comprueba si el experimento está en ejecución.
   *
   * Estructura: Método helper que evalúa el campo status.
   * Lógica: Devuelve TRUE solo cuando el estado es 'running'.
   * Sintaxis: Lectura directa del valor del campo list_string.
   *
   * @return bool
   *   TRUE si el experimento tiene estado 'running'.
   */
  public function isRunning(): bool {
    return $this->get('status')->value === 'running';
  }

  /**
   * Comprueba si el experimento está completado.
   *
   * Estructura: Método helper que evalúa el campo status.
   * Lógica: Devuelve TRUE solo cuando el estado es 'completed'.
   * Sintaxis: Lectura directa del valor del campo list_string.
   *
   * @return bool
   *   TRUE si el experimento tiene estado 'completed'.
   */
  public function isCompleted(): bool {
    return $this->get('status')->value === 'completed';
  }

  /**
   * Calcula los días transcurridos desde el inicio del experimento.
   *
   * Estructura: Método helper que combina start_date con la fecha actual.
   * Lógica: Si no hay start_date definida, devuelve 0. En caso contrario,
   *   calcula la diferencia en días entre la fecha de inicio y el momento
   *   actual usando DateTimeImmutable.
   * Sintaxis: Operación con DateTimeImmutable y DateInterval.
   *
   * @return int
   *   Número de días desde start_date hasta ahora, o 0 si no hay fecha.
   */
  public function getDurationDays(): int {
    $start = $this->get('start_date')->value;
    if (empty($start)) {
      return 0;
    }
    $start_date = new \DateTimeImmutable($start);
    $now = new \DateTimeImmutable();
    $diff = $now->diff($start_date);
    return (int) $diff->days;
  }

  /**
   * Calcula la tasa de conversión global del experimento.
   *
   * Estructura: Método helper que combina total_visitors y total_conversions.
   * Lógica: Si total_visitors es 0, devuelve 0.0 para evitar división
   *   por cero. En caso contrario, devuelve el cociente
   *   total_conversions / total_visitors como float.
   * Sintaxis: Operación aritmética con protección contra división por cero.
   *
   * @return float
   *   Tasa de conversión entre 0.0 y 1.0.
   */
  public function getOverallConversionRate(): float {
    $visitors = (int) $this->get('total_visitors')->value;
    if ($visitors === 0) {
      return 0.0;
    }
    $conversions = (int) $this->get('total_conversions')->value;
    return $conversions / $visitors;
  }

  /**
   * Comprueba si se ha alcanzado el tamaño mínimo de muestra.
   *
   * Estructura: Método helper que compara total_visitors con
   *   minimum_sample_size.
   * Lógica: Devuelve TRUE si el total de visitantes es igual o
   *   superior al tamaño mínimo de muestra configurado.
   * Sintaxis: Comparación directa entre dos campos integer.
   *
   * @return bool
   *   TRUE si total_visitors >= minimum_sample_size.
   */
  public function hasMinimumSample(): bool {
    $visitors = (int) $this->get('total_visitors')->value;
    $minimum = (int) $this->get('minimum_sample_size')->value;
    return $visitors >= $minimum;
  }

}
