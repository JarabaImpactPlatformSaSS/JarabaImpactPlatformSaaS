<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Actividad de Expediente (CaseActivity).
 *
 * ESTRUCTURA:
 * Registro append-only de eventos sobre un expediente. Cada actividad
 * registra un cambio de estado, nota, documento anadido, plazo o
 * comunicacion vinculada al expediente.
 *
 * LOGICA:
 * Las actividades son append-only: solo se crean, no se editan ni
 * eliminan. El ActivityLoggerService crea entradas automaticamente
 * al detectar cambios de estado o eventos relevantes. El campo
 * is_client_visible controla si el cliente puede ver la actividad.
 *
 * RELACIONES:
 * - CaseActivity -> ClientCase (case_id): expediente al que pertenece.
 * - CaseActivity -> User (actor_uid): usuario que realizo la accion.
 *
 * @ContentEntityType(
 *   id = "case_activity",
 *   label = @Translation("Actividad de Expediente"),
 *   label_collection = @Translation("Actividades de Expediente"),
 *   label_singular = @Translation("actividad"),
 *   label_plural = @Translation("actividades"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_cases\Access\CaseActivityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "case_activity",
 *   admin_permission = "manage legal cases",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/legal-case-activities",
 *     "add-form" = "/admin/content/legal-case-activities/add",
 *     "canonical" = "/admin/content/legal-case-activities/{case_activity}",
 *   },
 *   field_ui_base_route = "entity.case_activity.settings",
 * )
 */
class CaseActivity extends ContentEntityBase implements EntityOwnerInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: REFERENCIA AL EXPEDIENTE
    // =========================================================================

    $fields['case_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Expediente'))
      ->setDescription(new TranslatableMarkup('Expediente al que pertenece esta actividad.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'client_case')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: DATOS DE LA ACTIVIDAD
    // =========================================================================

    $fields['activity_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de Actividad'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'note' => new TranslatableMarkup('Nota'),
        'status_change' => new TranslatableMarkup('Cambio de Estado'),
        'document_added' => new TranslatableMarkup('Documento Anadido'),
        'deadline_set' => new TranslatableMarkup('Plazo Establecido'),
        'communication' => new TranslatableMarkup('Comunicacion'),
        'billing_event' => new TranslatableMarkup('Evento de Facturacion'),
        'case_created' => new TranslatableMarkup('Expediente Creado'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Descripcion'))
      ->setDescription(new TranslatableMarkup('Detalle de la actividad registrada.'))
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['actor_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Actor'))
      ->setDescription(new TranslatableMarkup('Usuario que realizo la accion.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['metadata'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Metadatos'))
      ->setDescription(new TranslatableMarkup('Datos adicionales en formato clave-valor.'));

    $fields['is_client_visible'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Visible para el Cliente'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    return $fields;
  }

}
