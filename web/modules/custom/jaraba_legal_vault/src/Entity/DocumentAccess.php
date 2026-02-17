<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define la entidad Acceso a Documento (DocumentAccess).
 *
 * ESTRUCTURA:
 * Registro de acceso compartido a un documento seguro. Cada registro
 * representa un grant de acceso a un destinatario especifico con
 * permisos granulares, limite de descargas y fecha de expiracion.
 *
 * LOGICA:
 * Al compartir un documento se genera un access_token unico y se
 * re-cifra la DEK para el destinatario. El acceso puede requerir
 * autenticacion o permitir acceso anonimo via token. Los accesos
 * se revocan cambiando is_revoked a TRUE.
 *
 * RELACIONES:
 * - DocumentAccess -> SecureDocument (document_id): documento compartido.
 * - DocumentAccess -> User (grantee_id): usuario destinatario.
 * - DocumentAccess -> User (granted_by): usuario que comparte.
 *
 * @ContentEntityType(
 *   id = "document_access",
 *   label = @Translation("Acceso a Documento"),
 *   label_collection = @Translation("Accesos a Documentos"),
 *   label_singular = @Translation("acceso a documento"),
 *   label_plural = @Translation("accesos a documentos"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_vault\Access\DocumentAccessAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "document_access",
 *   admin_permission = "administer vault",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/document-access",
 *     "canonical" = "/admin/content/document-access/{document_access}",
 *     "delete-form" = "/admin/content/document-access/{document_access}/delete",
 *   },
 * )
 */
class DocumentAccess extends ContentEntityBase {

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
      ->setDescription(new TranslatableMarkup('Documento seguro al que se otorga acceso.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'secure_document')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: DESTINATARIO
    // =========================================================================

    $fields['grantee_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Destinatario'))
      ->setDescription(new TranslatableMarkup('Usuario al que se otorga acceso.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['grantee_email'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Email del Destinatario'))
      ->setDescription(new TranslatableMarkup('Email del destinatario (para acceso por invitacion).'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: TOKEN Y CIFRADO
    // =========================================================================

    $fields['access_token'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Token de Acceso'))
      ->setDescription(new TranslatableMarkup('Token unico para acceso al documento compartido.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    $fields['encrypted_dek'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('DEK Cifrada'))
      ->setDescription(new TranslatableMarkup('DEK re-cifrada para el destinatario.'));

    // =========================================================================
    // BLOQUE 4: PERMISOS Y LIMITES
    // =========================================================================

    $fields['permissions'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Permisos'))
      ->setDescription(new TranslatableMarkup('Permisos granulares: view, download, print, etc.'));

    $fields['max_downloads'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Descargas Maximas'))
      ->setDescription(new TranslatableMarkup('Numero maximo de descargas permitidas (0 = ilimitado).'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['download_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Contador de Descargas'))
      ->setDescription(new TranslatableMarkup('Numero de descargas realizadas.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: EXPIRACION Y REVOCACION
    // =========================================================================

    $fields['expires_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Expiracion'))
      ->setDescription(new TranslatableMarkup('Fecha en que expira el acceso compartido.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['requires_auth'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Requiere Autenticacion'))
      ->setDescription(new TranslatableMarkup('Si el acceso requiere que el destinatario inicie sesion.'))
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_revoked'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Revocado'))
      ->setDescription(new TranslatableMarkup('Si el acceso ha sido revocado.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 6: QUIEN COMPARTE Y TIMESTAMPS
    // =========================================================================

    $fields['granted_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Compartido por'))
      ->setDescription(new TranslatableMarkup('Usuario que otorgo el acceso.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    return $fields;
  }

}
