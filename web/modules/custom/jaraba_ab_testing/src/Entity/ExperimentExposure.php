<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Exposicion de Experimento.
 *
 * Estructura: Entidad que registra cada exposicion individual de un
 *   visitante a una variante de experimento A/B. Almacena datos del
 *   visitante (visitor_id, user_id), contexto (device_type, browser,
 *   country, UTM), momento de exposicion (exposed_at) y resultado
 *   de conversion (converted, conversion_value).
 *
 * Logica: Cada ExperimentExposure vincula un visitante con una variante
 *   especifica de un experimento. El campo visitor_id permite trackear
 *   visitantes anonimos. El campo user_id referencia al usuario
 *   autenticado si existe. Los campos UTM permiten analizar el origen
 *   del trafico. El campo converted se marca como TRUE cuando el
 *   visitante completa la accion deseada.
 *
 * Relaciones: Pertenece a un ABExperiment (experiment_id). Pertenece
 *   opcionalmente a un User (user_id). Pertenece a un Tenant (tenant_id).
 *
 * Sintaxis: Content Entity con base_table propia, sin bundles.
 *   Usa EntityChangedTrait para timestamps automaticos.
 *
 * @ContentEntityType(
 *   id = "experiment_exposure",
 *   label = @Translation("Exposicion de Experimento"),
 *   label_collection = @Translation("Exposiciones de Experimento"),
 *   label_singular = @Translation("exposicion de experimento"),
 *   label_plural = @Translation("exposiciones de experimento"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_ab_testing\ListBuilder\ExperimentExposureListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_ab_testing\Form\ExperimentExposureForm",
 *       "add" = "Drupal\jaraba_ab_testing\Form\ExperimentExposureForm",
 *       "edit" = "Drupal\jaraba_ab_testing\Form\ExperimentExposureForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_ab_testing\Access\ExperimentExposureAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "experiment_exposure",
 *   fieldable = TRUE,
 *   admin_permission = "administer ab testing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "visitor_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/experiment-exposure/{experiment_exposure}",
 *     "add-form" = "/admin/content/experiment-exposure/add",
 *     "edit-form" = "/admin/content/experiment-exposure/{experiment_exposure}/edit",
 *     "delete-form" = "/admin/content/experiment-exposure/{experiment_exposure}/delete",
 *     "collection" = "/admin/content/experiment-exposures",
 *   },
 *   field_ui_base_route = "entity.experiment_exposure.settings",
 * )
 */
class ExperimentExposure extends ContentEntityBase implements EntityChangedInterface {

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
      ->setDescription(t('Experimento A/B al que pertenece esta exposicion.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'ab_experiment')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 2. VARIANTE Y VISITANTE
    // =========================================================================

    $fields['variant_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Clave de Variante'))
      ->setDescription(t('Identificador de la variante asignada al visitante.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['visitor_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID del Visitante'))
      ->setDescription(t('Identificador unico del visitante anonimo para tracking.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario autenticado asociado a esta exposicion, si existe.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 3. CONTEXTO DEL VISITANTE
    // =========================================================================

    $fields['device_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Dispositivo'))
      ->setDescription(t('Tipo de dispositivo desde el que se accedio.'))
      ->setSetting('allowed_values', [
        'desktop' => t('Desktop'),
        'mobile' => t('Mobile'),
        'tablet' => t('Tablet'),
      ])
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['browser'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Navegador'))
      ->setDescription(t('Nombre del navegador del visitante.'))
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Pais'))
      ->setDescription(t('Codigo ISO de 2 caracteres del pais del visitante.'))
      ->setSetting('max_length', 2)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 4. PARAMETROS UTM
    // =========================================================================

    $fields['utm_source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('UTM Source'))
      ->setDescription(t('Parametro utm_source del trafico de origen.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['utm_campaign'] = BaseFieldDefinition::create('string')
      ->setLabel(t('UTM Campaign'))
      ->setDescription(t('Parametro utm_campaign del trafico de origen.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 5. TRACKING DE EXPOSICION Y CONVERSION
    // =========================================================================

    $fields['exposed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Momento de Exposicion'))
      ->setDescription(t('Timestamp del momento en que el visitante fue expuesto a la variante.'))
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['converted'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Convertido'))
      ->setDescription(t('Indica si el visitante completo la accion de conversion.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['conversion_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor de Conversion'))
      ->setDescription(t('Valor monetario o numerico asociado a la conversion.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 4)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // 6. MULTI-TENANT
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece esta exposicion.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 12])
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
