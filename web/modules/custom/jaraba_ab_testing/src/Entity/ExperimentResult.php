<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Resultado de Experimento.
 *
 * Estructura: Entidad que almacena los resultados estadisticos calculados
 *   para cada variante de un experimento A/B. Contiene datos de
 *   identificacion (metric_name, variant_id), referencia al experimento
 *   (experiment_id), metricas estadisticas (sample_size, mean, std_dev,
 *   confidence_interval, p_value), resultado de significancia
 *   (is_significant, lift) y momento de calculo (calculated_at).
 *
 * Logica: Cada ExperimentResult almacena los resultados de una metrica
 *   especifica para una variante de un experimento. El campo p_value
 *   indica la probabilidad de que el resultado sea debido al azar.
 *   El campo is_significant se marca como TRUE cuando el p_value
 *   esta por debajo del umbral de confianza del experimento. El campo
 *   lift indica la mejora porcentual respecto al control.
 *
 * Relaciones: Pertenece a un ABExperiment (experiment_id). Pertenece
 *   a un Tenant (tenant_id).
 *
 * Sintaxis: Content Entity con base_table propia, sin bundles.
 *   Usa EntityChangedTrait para timestamps automaticos.
 *
 * @ContentEntityType(
 *   id = "experiment_result",
 *   label = @Translation("Resultado de Experimento"),
 *   label_collection = @Translation("Resultados de Experimento"),
 *   label_singular = @Translation("resultado de experimento"),
 *   label_plural = @Translation("resultados de experimento"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_ab_testing\ListBuilder\ExperimentResultListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_ab_testing\Form\ExperimentResultForm",
 *       "add" = "Drupal\jaraba_ab_testing\Form\ExperimentResultForm",
 *       "edit" = "Drupal\jaraba_ab_testing\Form\ExperimentResultForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_ab_testing\Access\ExperimentResultAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "experiment_result",
 *   fieldable = TRUE,
 *   admin_permission = "administer ab testing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "metric_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/experiment-result/{experiment_result}",
 *     "add-form" = "/admin/content/experiment-result/add",
 *     "edit-form" = "/admin/content/experiment-result/{experiment_result}/edit",
 *     "delete-form" = "/admin/content/experiment-result/{experiment_result}/delete",
 *     "collection" = "/admin/content/experiment-results",
 *   },
 *   field_ui_base_route = "entity.experiment_result.settings",
 * )
 */
class ExperimentResult extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // 1. REFERENCIA AL EXPERIMENTO
    // =========================================================================

    $fields['experiment_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Experimento'))
      ->setDescription(t('Experimento A/B al que pertenecen estos resultados.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'ab_experiment')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 2. IDENTIFICACION DE VARIANTE Y METRICA
    // =========================================================================

    $fields['variant_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Clave de Variante'))
      ->setDescription(t('Identificador de la variante evaluada.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['metric_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de la Metrica'))
      ->setDescription(t('Nombre de la metrica evaluada, p.ej. conversion_rate, click_rate.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 3. METRICAS ESTADISTICAS
    // =========================================================================

    $fields['sample_size'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tamano de Muestra'))
      ->setDescription(t('Numero de observaciones incluidas en el calculo.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mean'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Media'))
      ->setDescription(t('Valor medio de la metrica para esta variante.'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 6)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['std_dev'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Desviacion Estandar'))
      ->setDescription(t('Desviacion estandar de la metrica para esta variante.'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 6)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['confidence_interval'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Intervalo de Confianza'))
      ->setDescription(t('Intervalo de confianza en formato JSON, p.ej. {"lower": 0.12, "upper": 0.18}.'))
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 4. SIGNIFICANCIA ESTADISTICA
    // =========================================================================

    $fields['p_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('P-Value'))
      ->setDescription(t('Valor p del test estadistico. Valores menores indican mayor significancia.'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 6)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_significant'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Es Significativo'))
      ->setDescription(t('Indica si el resultado es estadisticamente significativo.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['lift'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Lift'))
      ->setDescription(t('Mejora porcentual de esta variante respecto al control.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 4)
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 5. MOMENTO DE CALCULO
    // =========================================================================

    $fields['calculated_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Calculo'))
      ->setDescription(t('Timestamp del momento en que se calcularon estos resultados.'))
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 6. MULTI-TENANT
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenecen estos resultados.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 7. METADATA
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
