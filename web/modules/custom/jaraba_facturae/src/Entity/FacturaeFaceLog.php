<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad de log de comunicaciones FACe.
 *
 * Registro inmutable (append-only) de todas las comunicaciones SOAP con
 * el portal FACe para auditoria y trazabilidad completa.
 *
 * RESTRICCIONES CRITICAS:
 * - Append-only: No se permite UPDATE ni DELETE para ningun rol.
 * - El AccessControlHandler deniega update/delete incluso para administradores.
 * - Cada entrada registra request/response SOAP, codigos de resultado,
 *   duracion y referencia al documento Facturae.
 *
 * Spec: Doc 180, Seccion 2.3.
 * Plan: FASE 6, entregable F6-3.
 *
 * @ContentEntityType(
 *   id = "facturae_face_log",
 *   label = @Translation("Facturae FACe Log"),
 *   label_collection = @Translation("Facturae FACe Logs"),
 *   label_singular = @Translation("Facturae FACe log entry"),
 *   label_plural = @Translation("Facturae FACe log entries"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_facturae\ListBuilder\FacturaeFaceLogListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_facturae\Access\FacturaeFaceLogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "facturae_face_log",
 *   admin_permission = "administer facturae",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "operation",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/facturae-face-log/{facturae_face_log}",
 *     "collection" = "/admin/content/facturae-face-logs",
 *   },
 *   field_ui_base_route = "jaraba_facturae.facturae_face_log.settings",
 * )
 */
class FacturaeFaceLog extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (Group) this log entry belongs to.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('view', TRUE);

    $fields['facturae_document_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Facturae Document'))
      ->setDescription(t('Reference to the Facturae document for this communication.'))
      ->setSetting('target_type', 'facturae_document')
      ->setDisplayConfigurable('view', TRUE);

    $fields['operation'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Operation'))
      ->setDescription(t('Type of FACe SOAP operation performed.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'send_invoice' => t('Send Invoice'),
        'query_status' => t('Query Status'),
        'cancel_invoice' => t('Cancel Invoice'),
        'query_units' => t('Query Admin Units'),
        'query_invoices' => t('Query Invoice List'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['soap_action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SOAP Action'))
      ->setDescription(t('SOAP method invoked on FACe endpoint.'))
      ->setSetting('max_length', 100)
      ->setDisplayConfigurable('view', TRUE);

    $fields['request_xml'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Request XML'))
      ->setDescription(t('Complete SOAP request sent to FACe.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['response_xml'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Response XML'))
      ->setDescription(t('Complete SOAP response received from FACe.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['response_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Response Code'))
      ->setDescription(t('FACe result code.'))
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('view', TRUE);

    $fields['response_description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Response Description'))
      ->setDescription(t('FACe result description text.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['registry_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Registry Number'))
      ->setDescription(t('FACe registration number assigned to the invoice.'))
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('view', TRUE);

    $fields['http_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('HTTP Status'))
      ->setDescription(t('HTTP status code of the SOAP call.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['duration_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duration (ms)'))
      ->setDescription(t('Duration of the SOAP call in milliseconds.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['error_detail'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Error Detail'))
      ->setDescription(t('Detailed error information if the call failed.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP Address'))
      ->setDescription(t('Source IP address of the call.'))
      ->setSetting('max_length', 45)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('User who triggered this FACe operation.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp of this log entry.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
