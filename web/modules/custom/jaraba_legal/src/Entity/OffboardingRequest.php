<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD OFFBOARDING REQUEST — Solicitud de baja del servicio.
 *
 * ESTRUCTURA:
 * Content Entity que gestiona el proceso completo de baja de un tenant.
 * Incluye periodo de gracia, exportación de datos, facturación final
 * y certificado de eliminación.
 *
 * LÓGICA DE NEGOCIO:
 * - El workflow sigue: requested→grace_period→export_pending→export_complete→data_deletion→completed.
 * - Durante el periodo de gracia, el tenant puede cancelar la solicitud.
 * - La exportación genera archivos en los formatos configurados (JSON, CSV).
 * - Al completar la eliminación, se genera un hash de certificado.
 * - El proceso es gestionado por OffboardingManagerService.
 *
 * RELACIONES:
 * - tenant_id → Group (referencia al tenant propietario)
 * - requested_by → User (usuario que solicitó la baja)
 * - export_file_id → File (archivo de exportación generado)
 * - final_invoice_id → BillingInvoice (factura final del tenant)
 *
 * Spec: Doc 184 §2.4. Plan: FASE 5, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "offboarding_request",
 *   label = @Translation("Offboarding Request"),
 *   label_collection = @Translation("Offboarding Requests"),
 *   label_singular = @Translation("offboarding request"),
 *   label_plural = @Translation("offboarding requests"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal\ListBuilder\OffboardingRequestListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal\Form\OffboardingRequestForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal\Access\OffboardingRequestAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "offboarding_request",
 *   admin_permission = "administer legal",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "tenant_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/offboarding-request/{offboarding_request}",
 *     "add-form" = "/admin/content/offboarding-request/add",
 *     "edit-form" = "/admin/content/offboarding-request/{offboarding_request}/edit",
 *     "delete-form" = "/admin/content/offboarding-request/{offboarding_request}/delete",
 *     "collection" = "/admin/content/offboarding-requests",
 *   },
 *   field_ui_base_route = "jaraba_legal.offboarding_request.settings",
 * )
 */
class OffboardingRequest extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TENANT (aislamiento multi-tenant) ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant que solicita la baja.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre del tenant'))
      ->setDescription(new TranslatableMarkup('Nombre del tenant para referencia en el proceso de baja.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => -9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- SOLICITANTE ---

    $fields['requested_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Solicitado por'))
      ->setDescription(new TranslatableMarkup('Usuario que inició la solicitud de baja.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- MOTIVO ---

    $fields['reason'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Motivo'))
      ->setDescription(new TranslatableMarkup('Razón de la solicitud de baja.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'voluntary' => new TranslatableMarkup('Voluntaria'),
        'non_payment' => new TranslatableMarkup('Impago'),
        'aup_violation' => new TranslatableMarkup('Violación AUP'),
        'contract_end' => new TranslatableMarkup('Fin de contrato'),
        'other' => new TranslatableMarkup('Otro'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reason_details'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Detalles del motivo'))
      ->setDescription(new TranslatableMarkup('Explicación detallada del motivo de baja.'))
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ESTADO ---

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setDescription(new TranslatableMarkup('Estado actual del proceso de offboarding.'))
      ->setRequired(TRUE)
      ->setDefaultValue('requested')
      ->setSetting('allowed_values', [
        'requested' => new TranslatableMarkup('Solicitada'),
        'grace_period' => new TranslatableMarkup('Periodo de gracia'),
        'export_pending' => new TranslatableMarkup('Exportación pendiente'),
        'export_complete' => new TranslatableMarkup('Exportación completada'),
        'data_deletion' => new TranslatableMarkup('Eliminación de datos'),
        'completed' => new TranslatableMarkup('Completada'),
        'cancelled' => new TranslatableMarkup('Cancelada'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- PERIODO DE GRACIA ---

    $fields['grace_period_end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fin del periodo de gracia'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC del fin del periodo de gracia.'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- EXPORTACIÓN ---

    $fields['export_file_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Archivo de exportación'))
      ->setDescription(new TranslatableMarkup('Archivo generado con los datos exportados del tenant.'))
      ->setSetting('target_type', 'file')
      ->setDisplayConfigurable('view', TRUE);

    // --- FACTURACIÓN ---

    $fields['final_invoice_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Factura final'))
      ->setDescription(new TranslatableMarkup('Factura final generada para el tenant.'))
      ->setSetting('target_type', 'billing_invoice')
      ->setDisplayConfigurable('view', TRUE);

    // --- CERTIFICADO DE ELIMINACIÓN ---

    $fields['deletion_certificate_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Hash certificado de eliminación'))
      ->setDescription(new TranslatableMarkup('Hash SHA-256 del certificado de eliminación de datos.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    // --- COMPLETADO ---

    $fields['completed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de completado'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC de finalización del proceso de offboarding.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- TIMESTAMPS ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'))
      ->setDescription(new TranslatableMarkup('Fecha de creación del registro.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'))
      ->setDescription(new TranslatableMarkup('Fecha de última modificación.'));

    return $fields;
  }

  /**
   * Comprueba si el offboarding está completado.
   */
  public function isCompleted(): bool {
    return $this->get('status')->value === 'completed';
  }

  /**
   * Comprueba si el offboarding ha sido cancelado.
   */
  public function isCancelled(): bool {
    return $this->get('status')->value === 'cancelled';
  }

  /**
   * Comprueba si el offboarding está en periodo de gracia.
   */
  public function isInGracePeriod(): bool {
    return $this->get('status')->value === 'grace_period';
  }

  /**
   * Comprueba si la exportación de datos está completada.
   */
  public function isExportComplete(): bool {
    return in_array($this->get('status')->value, ['export_complete', 'data_deletion', 'completed'], TRUE);
  }

  /**
   * Comprueba si tiene certificado de eliminación.
   */
  public function hasDeletionCertificate(): bool {
    return !empty($this->get('deletion_certificate_hash')->value);
  }

}
