<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad DashboardWidget para widgets de dashboard.
 *
 * PROPOSITO:
 * Almacena widgets individuales que se renderizan dentro de un dashboard.
 * Cada widget tiene un tipo de visualizacion, fuente de datos,
 * configuracion de consulta y posicion en el grid del dashboard.
 *
 * LOGICA:
 * - widget_type: tipo de visualizacion (line_chart, bar_chart, pie_chart,
 *   number_card, table, funnel, cohort_heatmap).
 * - data_source: nombre del origen de datos (tabla o servicio).
 * - query_config: JSON con metric, dimensions, filters, date_range.
 * - display_config: JSON con colores, etiquetas, formato.
 * - dashboard_id: referencia al dashboard contenedor.
 * - position: cadena "row:col:width:height" para ubicacion en grid.
 * - widget_status: active (visible) o hidden (oculto).
 *
 * @ContentEntityType(
 *   id = "dashboard_widget",
 *   label = @Translation("Dashboard Widget"),
 *   label_collection = @Translation("Dashboard Widgets"),
 *   label_singular = @Translation("dashboard widget"),
 *   label_plural = @Translation("dashboard widgets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count dashboard widget",
 *     plural = "@count dashboard widgets",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_analytics\ListBuilder\DashboardWidgetListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_analytics\Form\DashboardWidgetForm",
 *       "add" = "Drupal\jaraba_analytics\Form\DashboardWidgetForm",
 *       "edit" = "Drupal\jaraba_analytics\Form\DashboardWidgetForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_analytics\Access\DashboardWidgetAccessControlHandler",
 *   },
 *   base_table = "dashboard_widget",
 *   admin_permission = "administer jaraba analytics",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/content/dashboard-widgets",
 *     "canonical" = "/admin/content/dashboard-widgets/{dashboard_widget}",
 *     "add-form" = "/admin/content/dashboard-widgets/add",
 *     "edit-form" = "/admin/content/dashboard-widgets/{dashboard_widget}/edit",
 *     "delete-form" = "/admin/content/dashboard-widgets/{dashboard_widget}/delete",
 *   },
 * )
 */
class DashboardWidget extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Widget type constants.
   */
  public const TYPE_LINE_CHART = 'line_chart';
  public const TYPE_BAR_CHART = 'bar_chart';
  public const TYPE_PIE_CHART = 'pie_chart';
  public const TYPE_NUMBER_CARD = 'number_card';
  public const TYPE_TABLE = 'table';
  public const TYPE_FUNNEL = 'funnel';
  public const TYPE_COHORT_HEATMAP = 'cohort_heatmap';

  /**
   * Widget status constants.
   */
  public const STATUS_ACTIVE = 'active';
  public const STATUS_HIDDEN = 'hidden';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The widget name.'))
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

    $fields['widget_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Widget Type'))
      ->setDescription(t('The type of visualization for this widget.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::TYPE_LINE_CHART => t('Line Chart'),
        self::TYPE_BAR_CHART => t('Bar Chart'),
        self::TYPE_PIE_CHART => t('Pie Chart'),
        self::TYPE_NUMBER_CARD => t('Number Card'),
        self::TYPE_TABLE => t('Table'),
        self::TYPE_FUNNEL => t('Funnel'),
        self::TYPE_COHORT_HEATMAP => t('Cohort Heatmap'),
      ])
      ->setDefaultValue(self::TYPE_LINE_CHART)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data_source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Data Source'))
      ->setDescription(t('The data source identifier for this widget.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['query_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Query Configuration'))
      ->setDescription(t('JSON query configuration (metric, dimensions, filters, date_range).'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 15,
        'settings' => [
          'rows' => 5,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['display_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Display Configuration'))
      ->setDescription(t('JSON display configuration (colors, labels, format).'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 20,
        'settings' => [
          'rows' => 5,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['dashboard_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Dashboard'))
      ->setDescription(t('The analytics dashboard this widget belongs to.'))
      ->setSetting('target_type', 'analytics_dashboard')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['position'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Position'))
      ->setDescription(t('Grid position as "row:col:width:height".'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['widget_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The widget visibility status.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_ACTIVE => t('Active'),
        self::STATUS_HIDDEN => t('Hidden'),
      ])
      ->setDefaultValue(self::STATUS_ACTIVE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 35,
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
   * Gets the widget name.
   */
  public function getName(): string {
    return $this->get('name')->value ?? '';
  }

  /**
   * Gets the widget type.
   */
  public function getWidgetType(): string {
    return $this->get('widget_type')->value ?? self::TYPE_LINE_CHART;
  }

  /**
   * Gets the data source.
   */
  public function getDataSource(): string {
    return $this->get('data_source')->value ?? '';
  }

  /**
   * Gets the query configuration as an array.
   */
  public function getQueryConfig(): array {
    $value = $this->get('query_config')->value;
    if ($value) {
      $decoded = json_decode($value, TRUE);
      return is_array($decoded) ? $decoded : [];
    }
    return [];
  }

  /**
   * Gets the display configuration as an array.
   */
  public function getDisplayConfig(): array {
    $value = $this->get('display_config')->value;
    if ($value) {
      $decoded = json_decode($value, TRUE);
      return is_array($decoded) ? $decoded : [];
    }
    return [];
  }

  /**
   * Gets the parent dashboard ID.
   */
  public function getDashboardId(): ?int {
    $value = $this->get('dashboard_id')->target_id;
    return $value ? (int) $value : NULL;
  }

  /**
   * Gets the position string.
   */
  public function getPosition(): string {
    return $this->get('position')->value ?? '1:1:4:3';
  }

  /**
   * Gets parsed position as an array.
   *
   * @return array
   *   Associative array with keys: row, col, width, height.
   */
  public function getParsedPosition(): array {
    $parts = explode(':', $this->getPosition());
    return [
      'row' => (int) ($parts[0] ?? 1),
      'col' => (int) ($parts[1] ?? 1),
      'width' => (int) ($parts[2] ?? 4),
      'height' => (int) ($parts[3] ?? 3),
    ];
  }

  /**
   * Gets the widget status.
   */
  public function getWidgetStatus(): string {
    return $this->get('widget_status')->value ?? self::STATUS_ACTIVE;
  }

}
