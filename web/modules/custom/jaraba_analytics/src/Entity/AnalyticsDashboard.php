<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad AnalyticsDashboard para dashboards BI personalizables.
 *
 * PROPOSITO:
 * Almacena dashboards de analytics configurables con layout en grid,
 * estados de comparticion y propiedad por usuario y tenant.
 * Cada dashboard puede contener multiples widgets organizados
 * en una cuadricula flexible.
 *
 * LOGICA:
 * - layout_config: JSON con la configuracion del grid (columnas, filas, gaps).
 * - is_default: indica si este dashboard es el predeterminado para el tenant.
 * - is_shared: indica si el dashboard es visible para otros usuarios del tenant.
 * - dashboard_status: active (visible) o archived (oculto).
 * - owner_id: usuario propietario del dashboard.
 * - tenant_id: aislamiento multi-tenant mediante referencia a grupo.
 *
 * @ContentEntityType(
 *   id = "analytics_dashboard",
 *   label = @Translation("Analytics Dashboard"),
 *   label_collection = @Translation("Analytics Dashboards"),
 *   label_singular = @Translation("analytics dashboard"),
 *   label_plural = @Translation("analytics dashboards"),
 *   label_count = @PluralTranslation(
 *     singular = "@count analytics dashboard",
 *     plural = "@count analytics dashboards",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_analytics\ListBuilder\AnalyticsDashboardListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_analytics\Form\AnalyticsDashboardForm",
 *       "add" = "Drupal\jaraba_analytics\Form\AnalyticsDashboardForm",
 *       "edit" = "Drupal\jaraba_analytics\Form\AnalyticsDashboardForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_analytics\Access\AnalyticsDashboardAccessControlHandler",
 *   },
 *   base_table = "analytics_dashboard",
 *   admin_permission = "administer jaraba analytics",
 *   field_ui_base_route = "jaraba_analytics.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/content/analytics-dashboards",
 *     "canonical" = "/admin/content/analytics-dashboards/{analytics_dashboard}",
 *     "add-form" = "/admin/content/analytics-dashboards/add",
 *     "edit-form" = "/admin/content/analytics-dashboards/{analytics_dashboard}/edit",
 *     "delete-form" = "/admin/content/analytics-dashboards/{analytics_dashboard}/delete",
 *   },
 * )
 */
class AnalyticsDashboard extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Dashboard status constants.
   */
  public const STATUS_ACTIVE = 'active';
  public const STATUS_ARCHIVED = 'archived';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The dashboard name.'))
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

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('A description of this dashboard.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['layout_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Layout Configuration'))
      ->setDescription(t('JSON grid layout configuration (columns, rows, gaps).'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 10,
        'settings' => [
          'rows' => 5,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['is_default'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Default Dashboard'))
      ->setDescription(t('Whether this is the default dashboard for the tenant.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_shared'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Shared'))
      ->setDescription(t('Whether this dashboard is shared with other tenant users.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['owner_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user who owns this dashboard.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (group) this dashboard belongs to.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['dashboard_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The dashboard status.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_ACTIVE => t('Active'),
        self::STATUS_ARCHIVED => t('Archived'),
      ])
      ->setDefaultValue(self::STATUS_ACTIVE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 30,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Gets the dashboard name.
   */
  public function getName(): string {
    return $this->get('name')->value ?? '';
  }

  /**
   * Gets the description.
   */
  public function getDescription(): ?string {
    return $this->get('description')->value;
  }

  /**
   * Gets the layout configuration as an array.
   */
  public function getLayoutConfig(): array {
    $value = $this->get('layout_config')->value;
    if ($value) {
      $decoded = json_decode($value, TRUE);
      return is_array($decoded) ? $decoded : [];
    }
    return [];
  }

  /**
   * Gets whether this is the default dashboard.
   */
  public function isDefault(): bool {
    return (bool) $this->get('is_default')->value;
  }

  /**
   * Gets whether this dashboard is shared.
   */
  public function isShared(): bool {
    return (bool) $this->get('is_shared')->value;
  }

  /**
   * Gets the owner user ID.
   */
  public function getOwnerId(): ?int {
    $value = $this->get('owner_id')->target_id;
    return $value ? (int) $value : NULL;
  }

  /**
   * Gets the tenant ID.
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value ? (int) $value : NULL;
  }

  /**
   * Gets the dashboard status.
   */
  public function getDashboardStatus(): string {
    return $this->get('dashboard_status')->value ?? self::STATUS_ACTIVE;
  }

}
