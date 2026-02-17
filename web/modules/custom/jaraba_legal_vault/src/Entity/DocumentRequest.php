<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Solicitud de Documento (DocumentRequest) — FASE B2.
 *
 * ESTRUCTURA:
 * Solicitud de documento dentro del portal de cliente. Un abogado crea
 * una solicitud especificando el tipo de documento requerido, instrucciones
 * y plazo. El cliente sube el documento a traves del portal.
 *
 * LOGICA:
 * El ciclo de vida es: pending -> uploaded -> reviewing -> approved/rejected.
 * Cuando el cliente sube un documento, se vincula via uploaded_document_id
 * y el estado cambia a 'uploaded'. El abogado puede aprobar o rechazar
 * con motivo. Los recordatorios se controlan con reminder_count.
 *
 * RELACIONES:
 * - DocumentRequest -> ClientCase (case_id): expediente vinculado.
 * - DocumentRequest -> TaxonomyTerm (document_type_tid): tipo de documento.
 * - DocumentRequest -> SecureDocument (uploaded_document_id): documento subido.
 * - DocumentRequest -> User (reviewed_by): revisor.
 * - DocumentRequest -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 *
 * @ContentEntityType(
 *   id = "document_request",
 *   label = @Translation("Solicitud de Documento"),
 *   label_collection = @Translation("Solicitudes de Documentos"),
 *   label_singular = @Translation("solicitud de documento"),
 *   label_plural = @Translation("solicitudes de documentos"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_vault\ListBuilder\DocumentRequestListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_vault\Form\DocumentRequestForm",
 *       "add" = "Drupal\jaraba_legal_vault\Form\DocumentRequestForm",
 *       "edit" = "Drupal\jaraba_legal_vault\Form\DocumentRequestForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_vault\Access\DocumentAccessAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "document_request",
 *   admin_permission = "administer vault",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/document-requests",
 *     "add-form" = "/admin/content/document-requests/add",
 *     "canonical" = "/admin/content/document-requests/{document_request}",
 *     "edit-form" = "/admin/content/document-requests/{document_request}/edit",
 *     "delete-form" = "/admin/content/document-requests/{document_request}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_vault.document_request.settings",
 * )
 */
class DocumentRequest extends ContentEntityBase implements EntityOwnerInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: REFERENCIA AL EXPEDIENTE Y TIPO
    // =========================================================================

    $fields['case_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Expediente'))
      ->setDescription(new TranslatableMarkup('Expediente juridico vinculado a la solicitud.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'client_case')
      ->setDisplayOptions('form', ['weight' => -5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['document_type_tid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tipo de Documento'))
      ->setDescription(new TranslatableMarkup('Tipo de documento solicitado (taxonomia).'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => -4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: DATOS DE LA SOLICITUD
    // =========================================================================

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Titulo'))
      ->setDescription(new TranslatableMarkup('Titulo descriptivo de la solicitud.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['instructions'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Instrucciones'))
      ->setDescription(new TranslatableMarkup('Instrucciones para el cliente sobre el documento requerido.'))
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_required'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Obligatorio'))
      ->setDescription(new TranslatableMarkup('Si el documento es obligatorio para el expediente.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['deadline'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha Limite'))
      ->setDescription(new TranslatableMarkup('Fecha limite para subir el documento.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: ESTADO Y REVISION
    // =========================================================================

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => new TranslatableMarkup('Pendiente'),
        'uploaded' => new TranslatableMarkup('Subido'),
        'reviewing' => new TranslatableMarkup('En Revision'),
        'approved' => new TranslatableMarkup('Aprobado'),
        'rejected' => new TranslatableMarkup('Rechazado'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uploaded_document_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Documento Subido'))
      ->setDescription(new TranslatableMarkup('Referencia al documento seguro subido por el cliente.'))
      ->setSetting('target_type', 'secure_document')
      ->setDisplayConfigurable('view', TRUE);

    $fields['reviewed_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Revisado por'))
      ->setDescription(new TranslatableMarkup('Usuario que reviso el documento.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('view', TRUE);

    $fields['rejection_reason'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Motivo de Rechazo'))
      ->setDescription(new TranslatableMarkup('Motivo de rechazo del documento subido.'))
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reminder_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Recordatorios Enviados'))
      ->setDescription(new TranslatableMarkup('Numero de recordatorios enviados al cliente.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: TENANT Y TIMESTAMPS
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece la solicitud.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    return $fields;
  }

}
