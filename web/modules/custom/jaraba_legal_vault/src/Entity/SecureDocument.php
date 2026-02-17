<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Documento Seguro (SecureDocument).
 *
 * ESTRUCTURA:
 * Entidad pivote de la boveda documental JarabaLex. Representa un documento
 * cifrado con metadatos de almacenamiento, versionado y control de acceso.
 * Soporta cifrado envelope (DEK + KEK) y firma digital.
 *
 * LOGICA:
 * Cada documento almacena la ruta cifrada, hash del contenido para verificacion
 * de integridad, y metadatos de cifrado (DEK envuelto, IV, tag). El versionado
 * se implementa mediante parent_version_id, creando una cadena de versiones.
 * Soft-delete cambia el status a 'deleted' sin eliminar fisicamente.
 *
 * RELACIONES:
 * - SecureDocument -> User (owner_id): propietario del documento.
 * - SecureDocument -> User (uid): creador del registro.
 * - SecureDocument -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 * - SecureDocument -> ClientCase (case_id): expediente vinculado.
 * - SecureDocument -> TaxonomyTerm (category_tid): categoria documental.
 * - SecureDocument -> SecureDocument (parent_version_id): version anterior.
 * - SecureDocument <- DocumentAccess (document_id): accesos compartidos.
 * - SecureDocument <- DocumentAuditLog (document_id): auditoria.
 *
 * @ContentEntityType(
 *   id = "secure_document",
 *   label = @Translation("Documento Seguro"),
 *   label_collection = @Translation("Documentos Seguros"),
 *   label_singular = @Translation("documento seguro"),
 *   label_plural = @Translation("documentos seguros"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_vault\ListBuilder\SecureDocumentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_vault\Form\SecureDocumentForm",
 *       "add" = "Drupal\jaraba_legal_vault\Form\SecureDocumentForm",
 *       "edit" = "Drupal\jaraba_legal_vault\Form\SecureDocumentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_vault\Access\SecureDocumentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "secure_document",
 *   admin_permission = "administer vault",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/vault-documents",
 *     "add-form" = "/admin/content/vault-documents/add",
 *     "canonical" = "/admin/content/vault-documents/{secure_document}",
 *     "edit-form" = "/admin/content/vault-documents/{secure_document}/edit",
 *     "delete-form" = "/admin/content/vault-documents/{secure_document}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_vault.secure_document.settings",
 * )
 */
class SecureDocument extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: PROPIETARIO Y TENANT
    // =========================================================================

    $fields['owner_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Propietario'))
      ->setDescription(new TranslatableMarkup('Usuario propietario del documento.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => -5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece el documento.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => -4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['case_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Expediente'))
      ->setDescription(new TranslatableMarkup('Expediente juridico vinculado al documento.'))
      ->setSetting('target_type', 'client_case')
      ->setDisplayOptions('form', ['weight' => -3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: IDENTIFICACION DEL DOCUMENTO
    // =========================================================================

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Titulo'))
      ->setDescription(new TranslatableMarkup('Titulo descriptivo del documento.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Descripcion'))
      ->setDescription(new TranslatableMarkup('Descripcion detallada del documento.'))
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: METADATOS DEL ARCHIVO
    // =========================================================================

    $fields['original_filename'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre Original'))
      ->setDescription(new TranslatableMarkup('Nombre original del archivo subido.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mime_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Tipo MIME'))
      ->setDescription(new TranslatableMarkup('Tipo MIME del archivo (ej: application/pdf).'))
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('view', TRUE);

    $fields['file_size'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Tamano del Archivo'))
      ->setDescription(new TranslatableMarkup('Tamano del archivo en bytes.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['storage_path'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Ruta de Almacenamiento'))
      ->setDescription(new TranslatableMarkup('Ruta interna donde se almacena el archivo cifrado.'))
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: INTEGRIDAD Y CIFRADO
    // =========================================================================

    $fields['content_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Hash del Contenido'))
      ->setDescription(new TranslatableMarkup('SHA-256 del contenido original para verificacion de integridad.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    $fields['encrypted_dek'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('DEK Cifrada'))
      ->setDescription(new TranslatableMarkup('Data Encryption Key envuelta con la KEK del tenant.'));

    $fields['encryption_iv'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('IV de Cifrado'))
      ->setDescription(new TranslatableMarkup('Vector de inicializacion utilizado en el cifrado AES-GCM.'))
      ->setSetting('max_length', 32);

    $fields['encryption_tag'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Tag de Cifrado'))
      ->setDescription(new TranslatableMarkup('Tag de autenticacion GCM para verificar integridad del cifrado.'))
      ->setSetting('max_length', 32);

    // =========================================================================
    // BLOQUE 5: CLASIFICACION Y VERSIONADO
    // =========================================================================

    $fields['category_tid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Categoria'))
      ->setDescription(new TranslatableMarkup('Categoria documental del documento.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['version'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Version'))
      ->setDescription(new TranslatableMarkup('Numero de version del documento.'))
      ->setDefaultValue(1)
      ->setDisplayConfigurable('view', TRUE);

    $fields['parent_version_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Version Anterior'))
      ->setDescription(new TranslatableMarkup('Referencia a la version anterior del documento.'))
      ->setSetting('target_type', 'secure_document')
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 6: FIRMA Y EXPIRACION
    // =========================================================================

    $fields['is_signed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Firmado'))
      ->setDescription(new TranslatableMarkup('Indica si el documento ha sido firmado digitalmente.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['expires_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Expiracion'))
      ->setDescription(new TranslatableMarkup('Fecha en que el documento expira y debe ser renovado.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 7: ESTADO
    // =========================================================================

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => new TranslatableMarkup('Borrador'),
        'active' => new TranslatableMarkup('Activo'),
        'archived' => new TranslatableMarkup('Archivado'),
        'deleted' => new TranslatableMarkup('Eliminado'),
      ])
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 8: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'));

    return $fields;
  }

}
