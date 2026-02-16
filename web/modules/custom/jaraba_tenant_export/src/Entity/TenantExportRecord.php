<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Registro de Exportación de Tenant.
 *
 * Almacena el estado y metadatos de cada solicitud de exportación.
 * Soporta exportación completa, parcial y portabilidad GDPR Art. 20.
 *
 * @ContentEntityType(
 *   id = "tenant_export_record",
 *   label = @Translation("Registro de Exportación de Tenant"),
 *   label_collection = @Translation("Exportaciones de Tenant"),
 *   label_singular = @Translation("exportación de tenant"),
 *   label_plural = @Translation("exportaciones de tenant"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_tenant_export\ListBuilder\TenantExportRecordListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_tenant_export\Form\TenantExportRecordForm",
 *       "add" = "Drupal\jaraba_tenant_export\Form\TenantExportRecordForm",
 *       "edit" = "Drupal\jaraba_tenant_export\Form\TenantExportRecordForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_tenant_export\Access\TenantExportRecordAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "tenant_export_record",
 *   admin_permission = "administer tenant exports",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/tenant-export-record/{tenant_export_record}",
 *     "add-form" = "/admin/content/tenant-export-record/add",
 *     "edit-form" = "/admin/content/tenant-export-record/{tenant_export_record}/edit",
 *     "delete-form" = "/admin/content/tenant-export-record/{tenant_export_record}/delete",
 *     "collection" = "/admin/content/tenant-export-records",
 *   },
 *   field_ui_base_route = "jaraba_tenant_export.tenant_export_record.settings",
 * )
 */
class TenantExportRecord extends ContentEntityBase implements TenantExportRecordInterface, EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function isCompleted(): bool {
    return $this->get('status')->value === 'completed';
  }

  /**
   * {@inheritdoc}
   */
  public function isExpired(): bool {
    $expiresAt = (int) $this->get('expires_at')->value;
    if (!$expiresAt) {
      return FALSE;
    }
    return time() > $expiresAt;
  }

  /**
   * {@inheritdoc}
   */
  public function isDownloadable(): bool {
    return $this->isCompleted() && !$this->isExpired() && !empty($this->get('file_path')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getProgress(): int {
    return (int) ($this->get('progress')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusLabel(): string {
    $labels = [
      'queued' => (string) t('En cola'),
      'collecting' => (string) t('Recopilando datos'),
      'packaging' => (string) t('Empaquetando'),
      'completed' => (string) t('Completado'),
      'failed' => (string) t('Fallido'),
      'expired' => (string) t('Expirado'),
      'cancelled' => (string) t('Cancelado'),
    ];
    $status = $this->get('status')->value;
    return $labels[$status] ?? $status;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // AUDIT-CONS-005: Referencia al grupo/tenant.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant (Group)'))
      ->setDescription(t('Grupo al que pertenece esta exportación.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant Entity'))
      ->setDescription(t('Entidad tenant asociada.'))
      ->setSetting('target_type', 'tenant')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['requested_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Solicitado por'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['export_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Exportación'))
      ->setRequired(TRUE)
      ->setDefaultValue('full')
      ->setSetting('allowed_values', [
        'full' => t('Completa'),
        'partial' => t('Parcial'),
        'gdpr_portability' => t('Portabilidad GDPR'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('queued')
      ->setSetting('allowed_values', [
        'queued' => t('En cola'),
        'collecting' => t('Recopilando'),
        'packaging' => t('Empaquetando'),
        'completed' => t('Completado'),
        'failed' => t('Fallido'),
        'expired' => t('Expirado'),
        'cancelled' => t('Cancelado'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['progress'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Progreso'))
      ->setDescription(t('Porcentaje de progreso (0-100).'))
      ->setDefaultValue(0)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['current_phase'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Fase Actual'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['requested_sections'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Secciones Solicitadas'))
      ->setDescription(t('JSON array de secciones a exportar.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['file_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ruta del Archivo'))
      ->setSetting('max_length', 2048)
      ->setDisplayConfigurable('view', TRUE);

    $fields['file_size'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tamaño del Archivo'))
      ->setDescription(t('Tamaño en bytes.'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['file_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash del Archivo'))
      ->setDescription(t('SHA-256 hash para verificación de integridad.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    $fields['section_counts'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Conteo por Sección'))
      ->setDescription(t('JSON con el número de registros por sección.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['error_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Mensaje de Error'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['expires_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expira'))
      ->setDescription(t('Timestamp en que la exportación deja de estar disponible.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['download_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token de Descarga'))
      ->setDescription(t('UUID para URL de descarga segura.'))
      ->setSetting('max_length', 36)
      ->setDisplayConfigurable('view', TRUE);

    $fields['download_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Descargas'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    $fields['completed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Completado'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
