<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define la entidad Entrega de Documento (DocumentDelivery) — FASE B2.
 *
 * ESTRUCTURA:
 * Registro de entrega de un documento a un cliente a traves del portal.
 * Controla el ciclo de vida de la entrega: envio, notificacion,
 * visualizacion, descarga, acuse y firma.
 *
 * LOGICA:
 * Cuando un abogado entrega un documento al cliente, se crea un registro
 * de delivery. El estado sigue el ciclo: sent -> notified -> viewed ->
 * downloaded -> acknowledged -> signed. Cada transicion se registra con
 * timestamp. El campo requires_acknowledgment obliga al cliente a confirmar
 * recepcion.
 *
 * RELACIONES:
 * - DocumentDelivery -> ClientCase (case_id): expediente vinculado.
 * - DocumentDelivery -> SecureDocument (document_id): documento entregado.
 * - DocumentDelivery -> User (delivered_by): abogado que entrega.
 * - DocumentDelivery -> User (recipient_id): cliente destinatario.
 *
 * @ContentEntityType(
 *   id = "document_delivery",
 *   label = @Translation("Entrega de Documento"),
 *   label_collection = @Translation("Entregas de Documentos"),
 *   label_singular = @Translation("entrega de documento"),
 *   label_plural = @Translation("entregas de documentos"),
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
 *   base_table = "document_delivery",
 *   admin_permission = "administer vault",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/document-deliveries",
 *     "canonical" = "/admin/content/document-deliveries/{document_delivery}",
 *     "delete-form" = "/admin/content/document-deliveries/{document_delivery}/delete",
 *   },
 * )
 */
class DocumentDelivery extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: REFERENCIAS PRINCIPALES
    // =========================================================================

    $fields['case_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Expediente'))
      ->setDescription(new TranslatableMarkup('Expediente juridico vinculado a la entrega.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'client_case')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['document_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Documento'))
      ->setDescription(new TranslatableMarkup('Documento seguro entregado.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'secure_document')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: PARTICIPANTES
    // =========================================================================

    $fields['delivered_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Entregado por'))
      ->setDescription(new TranslatableMarkup('Abogado que realiza la entrega.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('view', TRUE);

    $fields['recipient_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Destinatario'))
      ->setDescription(new TranslatableMarkup('Cliente que recibe el documento.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: DATOS DE LA ENTREGA
    // =========================================================================

    $fields['message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Mensaje'))
      ->setDescription(new TranslatableMarkup('Mensaje para el destinatario.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notification_channels'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Canales de Notificacion'))
      ->setDescription(new TranslatableMarkup('Canales por los que se notifica: email, sms, push.'));

    $fields['requires_acknowledgment'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Requiere Acuse'))
      ->setDescription(new TranslatableMarkup('Si el destinatario debe confirmar la recepcion.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['requires_signature'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Requiere Firma'))
      ->setDescription(new TranslatableMarkup('Si el destinatario debe firmar el documento.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: ESTADO Y TRACKING
    // =========================================================================

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('sent')
      ->setSetting('allowed_values', [
        'sent' => new TranslatableMarkup('Enviado'),
        'notified' => new TranslatableMarkup('Notificado'),
        'viewed' => new TranslatableMarkup('Visualizado'),
        'downloaded' => new TranslatableMarkup('Descargado'),
        'acknowledged' => new TranslatableMarkup('Acusado'),
        'signed' => new TranslatableMarkup('Firmado'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['viewed_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Visualizado el'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['downloaded_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Descargado el'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['acknowledged_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Acusado el'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['download_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Contador de Descargas'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: TIMESTAMP
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    return $fields;
  }

}
