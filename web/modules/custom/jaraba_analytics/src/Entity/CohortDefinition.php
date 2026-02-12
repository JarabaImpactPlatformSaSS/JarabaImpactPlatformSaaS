<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad CohortDefinition para análisis de cohortes.
 *
 * PROPÓSITO:
 * Almacena definiciones de cohortes para análisis de retención,
 * comparación de segmentos y curvas de actividad por grupo temporal.
 * Cada cohorte define un conjunto de usuarios agrupados por un criterio
 * (fecha de registro, primera compra, vertical, etc.) y un rango de fechas.
 *
 * LÓGICA:
 * - cohort_type: criterio de agrupación (registration_date, first_purchase,
 *   vertical, custom).
 * - date_range_start / date_range_end: rango temporal que define la cohorte.
 * - filters: JSON con filtros adicionales (ej: {"vertical": "empleabilidad"}).
 * - tenant_id: aislamiento multi-tenant mediante referencia a grupo.
 *
 * @ContentEntityType(
 *   id = "cohort_definition",
 *   label = @Translation("Cohort Definition"),
 *   label_collection = @Translation("Cohort Definitions"),
 *   label_singular = @Translation("cohort definition"),
 *   label_plural = @Translation("cohort definitions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count cohort definition",
 *     plural = "@count cohort definitions",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_analytics\CohortDefinitionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_analytics\Form\CohortDefinitionForm",
 *       "add" = "Drupal\jaraba_analytics\Form\CohortDefinitionForm",
 *       "edit" = "Drupal\jaraba_analytics\Form\CohortDefinitionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_analytics\Access\CohortDefinitionAccessControlHandler",
 *   },
 *   base_table = "cohort_definition",
 *   admin_permission = "administer jaraba analytics",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/jaraba/analytics/cohorts",
 *     "canonical" = "/admin/jaraba/analytics/cohorts/{cohort_definition}",
 *     "add-form" = "/admin/jaraba/analytics/cohorts/add",
 *     "edit-form" = "/admin/jaraba/analytics/cohorts/{cohort_definition}/edit",
 *     "delete-form" = "/admin/jaraba/analytics/cohorts/{cohort_definition}/delete",
 *   },
 * )
 */
class CohortDefinition extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Constantes de tipo de cohorte.
   */
  public const TYPE_REGISTRATION_DATE = 'registration_date';
  public const TYPE_FIRST_PURCHASE = 'first_purchase';
  public const TYPE_VERTICAL = 'vertical';
  public const TYPE_CUSTOM = 'custom';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The cohort definition name.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (group) this cohort belongs to.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cohort_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Cohort Type'))
      ->setDescription(t('The criteria used to group users into this cohort.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::TYPE_REGISTRATION_DATE => t('Registration Date'),
        self::TYPE_FIRST_PURCHASE => t('First Purchase'),
        self::TYPE_VERTICAL => t('Vertical'),
        self::TYPE_CUSTOM => t('Custom'),
      ])
      ->setDefaultValue(self::TYPE_REGISTRATION_DATE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['date_range_start'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date Range Start'))
      ->setDescription(t('Start of the cohort date range.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 15,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['date_range_end'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date Range End'))
      ->setDescription(t('End of the cohort date range.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 20,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['filters'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Filters'))
      ->setDescription(t('Additional JSON filters for cohort member selection.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Gets the cohort name.
   */
  public function getName(): string {
    return $this->get('name')->value ?? '';
  }

  /**
   * Gets the cohort type.
   */
  public function getCohortType(): string {
    return $this->get('cohort_type')->value ?? self::TYPE_REGISTRATION_DATE;
  }

  /**
   * Gets the date range start value.
   */
  public function getDateRangeStart(): ?string {
    return $this->get('date_range_start')->value;
  }

  /**
   * Gets the date range end value.
   */
  public function getDateRangeEnd(): ?string {
    return $this->get('date_range_end')->value;
  }

  /**
   * Gets the filters as an associative array.
   */
  public function getFilters(): array {
    $value = $this->get('filters')->getValue();
    return $value[0] ?? [];
  }

  /**
   * Gets the tenant ID if set.
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value ? (int) $value : NULL;
  }

}
