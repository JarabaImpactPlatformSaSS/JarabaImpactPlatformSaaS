<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad de log de eventos VeriFactu (SIF).
 *
 * Entidad inmutable (append-only) que registra todos los eventos del
 * Sistema Informatico de Facturacion (SIF) segun RD 1007/2023.
 * Incluye su propio hash encadenado para trazabilidad.
 *
 * RESTRICCIONES:
 * - Append-only: No se permite edicion ni borrado.
 * - El AccessControlHandler deniega update/delete para todos los roles.
 * - 12 tipos de evento definidos por la normativa.
 *
 * Spec: Doc 179, Seccion 2.2. Plan: FASE 1, entregable F1-2.
 *
 * @ContentEntityType(
 *   id = "verifactu_event_log",
 *   label = @Translation("VeriFactu Event Log"),
 *   label_collection = @Translation("VeriFactu Event Logs"),
 *   label_singular = @Translation("VeriFactu event log entry"),
 *   label_plural = @Translation("VeriFactu event log entries"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_verifactu\ListBuilder\VeriFactuEventLogListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_verifactu\Access\VeriFactuEventLogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "verifactu_event_log",
 *   admin_permission = "administer verifactu",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "event_type",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/verifactu-event-log/{verifactu_event_log}",
 *     "collection" = "/admin/content/verifactu-event-logs",
 *   },
 *   field_ui_base_route = "jaraba_verifactu.verifactu_event_log.settings",
 * )
 */
class VeriFactuEventLog extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['event_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Event Type'))
      ->setDescription(t('SIF event type per RD 1007/2023.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'SYSTEM_START' => t('System Start'),
        'RECORD_CREATE' => t('Record Created'),
        'RECORD_CANCEL' => t('Record Cancelled'),
        'CHAIN_BREAK' => t('Chain Break Detected'),
        'CHAIN_RECOVERY' => t('Chain Recovery'),
        'AEAT_SUBMIT' => t('AEAT Submission'),
        'AEAT_RESPONSE' => t('AEAT Response'),
        'CERTIFICATE_CHANGE' => t('Certificate Changed'),
        'CONFIG_CHANGE' => t('Configuration Changed'),
        'AUDIT_ACCESS' => t('Audit Access'),
        'INTEGRITY_CHECK' => t('Integrity Check'),
        'MANUAL_INTERVENTION' => t('Manual Intervention'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Severity'))
      ->setRequired(TRUE)
      ->setDefaultValue('info')
      ->setSetting('allowed_values', [
        'info' => t('Info'),
        'warning' => t('Warning'),
        'error' => t('Error'),
        'critical' => t('Critical'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Human-readable description of the event.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['details'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Details'))
      ->setDescription(t('JSON with structured event details.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['hash_event'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Event Hash'))
      ->setDescription(t('SHA-256 hash of this event entry.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hash_previous_event'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Previous Event Hash'))
      ->setDescription(t('SHA-256 hash of the previous event entry.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    $fields['record_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Related Record'))
      ->setDescription(t('The VeriFactu invoice record related to this event, if any.'))
      ->setSetting('target_type', 'verifactu_invoice_record')
      ->setDisplayConfigurable('view', TRUE);

    $fields['actor_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Actor'))
      ->setDescription(t('The user who triggered the event.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('view', TRUE);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP Address'))
      ->setSetting('max_length', 45)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

}
