<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad de lote de remision AEAT VeriFactu.
 *
 * Cada batch contiene hasta 1.000 registros VeriFactu para envio
 * simultaneo a la AEAT via SOAP. Registra el estado del envio,
 * la respuesta XML de la AEAT y el desglose de registros
 * aceptados/rechazados.
 *
 * Spec: Doc 179, Seccion 2.3. Plan: FASE 1, entregable F1-3.
 *
 * @ContentEntityType(
 *   id = "verifactu_remision_batch",
 *   label = @Translation("VeriFactu Remision Batch"),
 *   label_collection = @Translation("VeriFactu Remision Batches"),
 *   label_singular = @Translation("VeriFactu remision batch"),
 *   label_plural = @Translation("VeriFactu remision batches"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_verifactu\ListBuilder\VeriFactuRemisionBatchListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_verifactu\Access\VeriFactuInvoiceRecordAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "verifactu_remision_batch",
 *   admin_permission = "administer verifactu",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/verifactu-remision-batch/{verifactu_remision_batch}",
 *     "collection" = "/admin/content/verifactu-remision-batches",
 *   },
 *   field_ui_base_route = "jaraba_verifactu.verifactu_remision_batch.settings",
 * )
 */
class VeriFactuRemisionBatch extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Batch Status'))
      ->setRequired(TRUE)
      ->setDefaultValue('queued')
      ->setSetting('allowed_values', [
        'queued' => t('Queued'),
        'sending' => t('Sending'),
        'sent' => t('Sent'),
        'partial_error' => t('Partial Error'),
        'error' => t('Error'),
      ])
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_records'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Records'))
      ->setDescription(t('Total number of records in this batch (max 1000).'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['accepted_records'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Accepted Records'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rejected_records'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Rejected Records'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sent_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Sent At'))
      ->setDescription(t('Timestamp when the batch was sent to AEAT.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['response_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Response At'))
      ->setDescription(t('Timestamp when AEAT response was received.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['request_xml'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Request XML'))
      ->setDescription(t('Complete SOAP request XML sent to AEAT.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['response_xml'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Response XML'))
      ->setDescription(t('Complete SOAP response XML from AEAT.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['error_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Error Message'))
      ->setDescription(t('Error message if the batch failed.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['retry_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Retry Count'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['aeat_environment'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('AEAT Environment'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'production' => t('Production'),
        'testing' => t('Testing'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Checks if the batch was fully successful.
   */
  public function isFullyAccepted(): bool {
    return $this->get('status')->value === 'sent'
      && (int) $this->get('rejected_records')->value === 0;
  }

}
