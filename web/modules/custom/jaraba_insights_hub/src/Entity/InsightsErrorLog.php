<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Insights Error Log.
 *
 * ESTRUCTURA:
 * Entidad que almacena errores capturados de JavaScript, PHP y APIs
 * con deduplicacion por hash. Cada registro unico se identifica por
 * error_hash y se incrementan las ocurrencias cuando se repite.
 *
 * LOGICA:
 * Los errores se capturan desde el frontend (JS), backend (PHP) o
 * endpoints de API. El campo error_hash sirve como clave de deduplicacion.
 * El status controla el ciclo de vida del error:
 * open -> acknowledged -> resolved / ignored.
 *
 * RELACIONES:
 * - InsightsErrorLog -> Tenant (tenant_id): tenant propietario
 * - InsightsErrorLog <- ErrorTrackingService: creado/actualizado por
 * - InsightsErrorLog <- InsightsErrorLogListBuilder: listado en admin
 * - InsightsErrorLog <- InsightsDashboardService: consultado por
 *
 * @ContentEntityType(
 *   id = "insights_error_log",
 *   label = @Translation("Insights Error Log"),
 *   label_collection = @Translation("Insights Error Logs"),
 *   label_singular = @Translation("insights error log"),
 *   label_plural = @Translation("insights error logs"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_insights_hub\ListBuilder\InsightsErrorLogListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_insights_hub\Access\InsightsAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "insights_error_log",
 *   admin_permission = "administer insights hub",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/insights-error/{insights_error_log}",
 *     "collection" = "/admin/content/insights-errors",
 *   },
 *   field_ui_base_route = "entity.insights_error_log.settings",
 * )
 */
class InsightsErrorLog extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este error.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Error Hash (deduplication key) ---
    $fields['error_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash del Error'))
      ->setDescription(t('Hash SHA-256 para deduplicacion de errores.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    // --- Error Type ---
    $fields['error_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Error'))
      ->setDescription(t('Origen del error.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'js' => 'JavaScript',
        'php' => 'PHP',
        'api' => 'API',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Severity ---
    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Severidad'))
      ->setDescription(t('Nivel de severidad del error.'))
      ->setDefaultValue('error')
      ->setSetting('allowed_values', [
        'error' => 'Error',
        'warning' => 'Warning',
        'info' => 'Info',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Message ---
    $fields['message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Mensaje'))
      ->setDescription(t('Mensaje descriptivo del error.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Stack Trace ---
    $fields['stack_trace'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Stack Trace'))
      ->setDescription(t('Traza completa del error.'));

    // --- File Path ---
    $fields['file_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Archivo'))
      ->setDescription(t('Ruta del archivo donde ocurrio el error.'))
      ->setSetting('max_length', 500);

    // --- Line Number ---
    $fields['line_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Linea'))
      ->setDescription(t('Numero de linea donde ocurrio el error.'));

    // --- URL ---
    $fields['url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL'))
      ->setDescription(t('URL donde se produjo el error.'))
      ->setSetting('max_length', 500);

    // --- Occurrences ---
    $fields['occurrences'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Ocurrencias'))
      ->setDescription(t('Numero de veces que se ha repetido este error.'))
      ->setDefaultValue(1)
      ->setDisplayConfigurable('view', TRUE);

    // --- First Seen ---
    $fields['first_seen_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Primera Aparicion'))
      ->setDescription(t('Timestamp de la primera vez que se detecto el error.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Last Seen ---
    $fields['last_seen_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ultima Aparicion'))
      ->setDescription(t('Timestamp de la ultima vez que se detecto el error.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Status ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado de gestion del error.'))
      ->setDefaultValue('open')
      ->setSetting('allowed_values', [
        'open' => 'Abierto',
        'acknowledged' => 'Reconocido',
        'resolved' => 'Resuelto',
        'ignored' => 'Ignorado',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
