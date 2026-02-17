<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Notificacion LexNET (LexnetNotification).
 *
 * ESTRUCTURA:
 * Entidad que representa una notificacion judicial recibida a traves del
 * sistema LexNET. Almacena datos del organo judicial, tipo de notificacion,
 * plazos computados, adjuntos y vinculacion opcional con expedientes.
 *
 * LOGICA:
 * Las notificaciones se sincronizan desde LexNET via polling periodico.
 * Cada notificacion tiene un ciclo de vida: pending -> read -> linked -> archived.
 * El campo computed_deadline se calcula a partir de received_at + deadline_days.
 * La vinculacion con un expediente (case_id) es opcional y se realiza manualmente
 * o por coincidencia de procedure_number.
 *
 * RELACIONES:
 * - LexnetNotification -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 * - LexnetNotification -> ClientCase (case_id): expediente vinculado (opcional).
 * - LexnetNotification -> User (uid): usuario que recibio/proceso la notificacion.
 *
 * @ContentEntityType(
 *   id = "lexnet_notification",
 *   label = @Translation("Notificacion LexNET"),
 *   label_collection = @Translation("Notificaciones LexNET"),
 *   label_singular = @Translation("notificacion LexNET"),
 *   label_plural = @Translation("notificaciones LexNET"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_lexnet\ListBuilder\LexnetNotificationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_lexnet\Form\LexnetNotificationForm",
 *       "add" = "Drupal\jaraba_legal_lexnet\Form\LexnetNotificationForm",
 *       "edit" = "Drupal\jaraba_legal_lexnet\Form\LexnetNotificationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_lexnet\Access\LexnetNotificationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "lexnet_notification",
 *   admin_permission = "administer lexnet",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "subject",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/lexnet-notifications",
 *     "add-form" = "/admin/content/lexnet-notifications/add",
 *     "canonical" = "/admin/content/lexnet-notifications/{lexnet_notification}",
 *     "edit-form" = "/admin/content/lexnet-notifications/{lexnet_notification}/edit",
 *     "delete-form" = "/admin/content/lexnet-notifications/{lexnet_notification}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_lexnet.lexnet_notification.settings",
 * )
 */
class LexnetNotification extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    // Compute deadline from received_at + deadline_days.
    $received = $this->get('received_at')->value;
    $days = (int) $this->get('deadline_days')->value;
    if ($received && $days > 0 && empty($this->get('computed_deadline')->value)) {
      $deadline = strtotime($received . ' +' . $days . ' days');
      if ($deadline !== FALSE) {
        $this->set('computed_deadline', date('Y-m-d\TH:i:s', $deadline));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: MULTI-TENANT Y VINCULACION
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece la notificacion.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['case_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Expediente Vinculado'))
      ->setDescription(new TranslatableMarkup('Expediente juridico vinculado a esta notificacion (opcional).'))
      ->setSetting('target_type', 'client_case')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: IDENTIFICACION LEXNET
    // =========================================================================

    $fields['external_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID Externo LexNET'))
      ->setDescription(new TranslatableMarkup('Identificador unico de la notificacion en LexNET.'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('view', ['weight' => -10])
      ->setDisplayConfigurable('view', TRUE);

    $fields['notification_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de Notificacion'))
      ->setRequired(TRUE)
      ->setDefaultValue('notificacion_electronica')
      ->setSetting('allowed_values', [
        'resolucion' => new TranslatableMarkup('Resolucion'),
        'comunicacion' => new TranslatableMarkup('Comunicacion'),
        'requerimiento' => new TranslatableMarkup('Requerimiento'),
        'citacion' => new TranslatableMarkup('Citacion'),
        'emplazamiento' => new TranslatableMarkup('Emplazamiento'),
        'notificacion_electronica' => new TranslatableMarkup('Notificacion Electronica'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: DATOS JUDICIALES
    // =========================================================================

    $fields['court'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Organo Judicial'))
      ->setDescription(new TranslatableMarkup('Juzgado o tribunal que emite la notificacion.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['procedure_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Numero de Procedimiento'))
      ->setDescription(new TranslatableMarkup('Numero de autos o procedimiento judicial.'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Asunto'))
      ->setDescription(new TranslatableMarkup('Asunto o titulo de la notificacion.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 500)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: FECHAS Y PLAZOS
    // =========================================================================

    $fields['received_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Recepcion'))
      ->setDescription(new TranslatableMarkup('Fecha y hora en que se recibio la notificacion.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['acknowledged_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Acuse'))
      ->setDescription(new TranslatableMarkup('Fecha y hora en que se acuso recibo de la notificacion.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['deadline_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Dias de Plazo'))
      ->setDescription(new TranslatableMarkup('Numero de dias habiles de plazo para la notificacion.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['computed_deadline'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Plazo Computado'))
      ->setDescription(new TranslatableMarkup('Fecha limite calculada a partir de received_at + deadline_days.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('view', ['weight' => 9])
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: ADJUNTOS Y DATOS CRUDOS
    // =========================================================================

    $fields['attachments'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Adjuntos'))
      ->setDescription(new TranslatableMarkup('Mapa de adjuntos descargados de la notificacion (filename => uri).'));

    $fields['raw_data'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Datos Crudos'))
      ->setDescription(new TranslatableMarkup('Respuesta cruda de LexNET para auditoria.'))
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 6: ESTADO
    // =========================================================================

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => new TranslatableMarkup('Pendiente'),
        'read' => new TranslatableMarkup('Leida'),
        'linked' => new TranslatableMarkup('Vinculada'),
        'archived' => new TranslatableMarkup('Archivada'),
      ])
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 7: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'));

    return $fields;
  }

}
