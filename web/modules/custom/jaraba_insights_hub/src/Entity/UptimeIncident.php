<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Uptime Incident.
 *
 * ESTRUCTURA:
 * Entidad que agrupa checks fallidos consecutivos en un incidente
 * de disponibilidad. Registra la duracion total, numero de checks
 * fallidos y si se envio alerta.
 *
 * LOGICA:
 * Un incidente se abre cuando se superan los checks fallidos consecutivos
 * configurados en uptime_alert_threshold. Se resuelve automaticamente
 * cuando el endpoint vuelve a responder correctamente. La duracion se
 * calcula como resolved_at - started_at.
 *
 * RELACIONES:
 * - UptimeIncident -> Tenant (tenant_id): tenant propietario
 * - UptimeIncident <- UptimeMonitorService: creado/resuelto por
 * - UptimeIncident <- UptimeIncidentListBuilder: listado en admin
 * - UptimeIncident <- InsightsDashboardService: consultado por
 *
 * @ContentEntityType(
 *   id = "uptime_incident",
 *   label = @Translation("Uptime Incident"),
 *   label_collection = @Translation("Uptime Incidents"),
 *   label_singular = @Translation("uptime incident"),
 *   label_plural = @Translation("uptime incidents"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_insights_hub\ListBuilder\UptimeIncidentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_insights_hub\Access\InsightsAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "uptime_incident",
 *   admin_permission = "administer insights hub",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/uptime-incident/{uptime_incident}",
 *     "collection" = "/admin/content/uptime-incidents",
 *   },
 *   field_ui_base_route = "entity.uptime_incident.settings",
 * )
 */
class UptimeIncident extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este incidente.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Endpoint ---
    $fields['endpoint'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Endpoint'))
      ->setDescription(t('URL del endpoint afectado.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 500)
      ->setDisplayConfigurable('view', TRUE);

    // --- Status ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual del incidente.'))
      ->setRequired(TRUE)
      ->setDefaultValue('ongoing')
      ->setSetting('allowed_values', [
        'ongoing' => 'En Curso',
        'resolved' => 'Resuelto',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Started At ---
    $fields['started_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Inicio'))
      ->setDescription(t('Timestamp de inicio del incidente.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Resolved At ---
    $fields['resolved_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Resolucion'))
      ->setDescription(t('Timestamp de resolucion del incidente.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Duration ---
    $fields['duration_seconds'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duracion (segundos)'))
      ->setDescription(t('Duracion total del incidente en segundos.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Failed Checks ---
    $fields['failed_checks'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Checks Fallidos'))
      ->setDescription(t('Numero de checks fallidos durante el incidente.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // --- Alert Sent ---
    $fields['alert_sent'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Alerta Enviada'))
      ->setDescription(t('Indica si se envio alerta por este incidente.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Resolution Note ---
    $fields['resolution_note'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Nota de Resolucion'))
      ->setDescription(t('Descripcion de la causa y resolucion del incidente.'))
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
