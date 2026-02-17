<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define la entidad Log de Auditoria de Documentos (DocumentAuditLog).
 *
 * ESTRUCTURA:
 * Registro append-only e inmutable de todas las operaciones realizadas
 * sobre documentos seguros. Implementa hash chain para garantizar la
 * integridad del trail de auditoria.
 *
 * LOGICA:
 * Cada entrada contiene un hash_chain calculado como:
 * SHA-256(previous_hash + current_record_data). Esto permite verificar
 * que el log no ha sido alterado. Las entradas NO pueden editarse ni
 * eliminarse — el AccessControlHandler retorna AccessResult::forbidden()
 * para operaciones update y delete.
 *
 * RELACIONES:
 * - DocumentAuditLog -> SecureDocument (document_id): documento auditado.
 * - DocumentAuditLog -> User (actor_id): usuario que realizo la accion.
 *
 * @ContentEntityType(
 *   id = "document_audit_log",
 *   label = @Translation("Log de Auditoria Documental"),
 *   label_collection = @Translation("Logs de Auditoria Documental"),
 *   label_singular = @Translation("log de auditoria"),
 *   label_plural = @Translation("logs de auditoria"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_legal_vault\Access\DocumentAuditLogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "document_audit_log",
 *   admin_permission = "administer vault",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/document-audit-log",
 *     "canonical" = "/admin/content/document-audit-log/{document_audit_log}",
 *   },
 * )
 */
class DocumentAuditLog extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: REFERENCIA AL DOCUMENTO
    // =========================================================================

    $fields['document_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Documento'))
      ->setDescription(new TranslatableMarkup('Documento seguro sobre el que se registra la accion.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'secure_document')
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: DATOS DE LA ACCION
    // =========================================================================

    $fields['action'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Accion'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'created' => new TranslatableMarkup('Creado'),
        'viewed' => new TranslatableMarkup('Visualizado'),
        'downloaded' => new TranslatableMarkup('Descargado'),
        'shared' => new TranslatableMarkup('Compartido'),
        'signed' => new TranslatableMarkup('Firmado'),
        'revoked' => new TranslatableMarkup('Revocado'),
        'deleted' => new TranslatableMarkup('Eliminado'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['actor_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Actor'))
      ->setDescription(new TranslatableMarkup('Usuario que realizo la accion.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('view', TRUE);

    $fields['actor_ip'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('IP del Actor'))
      ->setDescription(new TranslatableMarkup('Direccion IP desde la que se realizo la accion.'))
      ->setSetting('max_length', 45);

    // =========================================================================
    // BLOQUE 3: METADATOS Y HASH CHAIN
    // =========================================================================

    $fields['details'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Detalles'))
      ->setDescription(new TranslatableMarkup('Metadatos adicionales de la accion en formato clave-valor.'));

    $fields['hash_chain'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Hash Chain'))
      ->setDescription(new TranslatableMarkup('SHA-256(prev_hash + current_record) para integridad del log.'))
      ->setSetting('max_length', 64);

    // =========================================================================
    // BLOQUE 4: TIMESTAMP
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    return $fields;
  }

}
