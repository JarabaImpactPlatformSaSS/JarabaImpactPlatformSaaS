<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Registro de Tiempo (TimeEntry).
 *
 * ESTRUCTURA:
 * Entidad de control de tiempo para expedientes juridicos. Registra
 * horas trabajadas por abogado, descripcion, tarifa y si es facturable.
 * Se vincula opcionalmente a una factura una vez facturado.
 *
 * LOGICA:
 * Cada registro de tiempo se asocia a un expediente (case_id) y un
 * usuario (user_id). El campo is_billable determina si se incluye en
 * la facturacion. Al vincular a una factura, se establece invoice_id.
 *
 * RELACIONES:
 * - TimeEntry -> ClientCase (case_id): expediente al que pertenece.
 * - TimeEntry -> User (user_id): abogado que registro el tiempo.
 * - TimeEntry -> LegalInvoice (invoice_id): factura asociada (opcional).
 * - TimeEntry -> User (uid): creador/propietario del registro.
 * - TimeEntry -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 *
 * @ContentEntityType(
 *   id = "time_entry",
 *   label = @Translation("Registro de Tiempo"),
 *   label_collection = @Translation("Registros de Tiempo"),
 *   label_singular = @Translation("registro de tiempo"),
 *   label_plural = @Translation("registros de tiempo"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_billing\ListBuilder\TimeEntryListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_billing\Form\TimeEntryForm",
 *       "add" = "Drupal\jaraba_legal_billing\Form\TimeEntryForm",
 *       "edit" = "Drupal\jaraba_legal_billing\Form\TimeEntryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_billing\Access\TimeEntryAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "time_entry",
 *   admin_permission = "administer billing",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/time-entries",
 *     "add-form" = "/admin/content/time-entries/add",
 *     "canonical" = "/admin/content/time-entries/{time_entry}",
 *     "edit-form" = "/admin/content/time-entries/{time_entry}/edit",
 *     "delete-form" = "/admin/content/time-entries/{time_entry}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_billing.time_entry.settings",
 * )
 */
class TimeEntry extends ContentEntityBase implements EntityOwnerInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: MULTI-TENANT
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece el registro.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: REFERENCIAS
    // =========================================================================

    $fields['case_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Expediente'))
      ->setDescription(new TranslatableMarkup('Expediente al que se imputa el tiempo.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'client_case')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Profesional'))
      ->setDescription(new TranslatableMarkup('Abogado o profesional que registro el tiempo.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: DATOS DEL REGISTRO
    // =========================================================================

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Descripcion'))
      ->setDescription(new TranslatableMarkup('Descripcion de la tarea realizada.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha'))
      ->setDescription(new TranslatableMarkup('Fecha del registro de tiempo.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['duration_minutes'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Duracion (minutos)'))
      ->setDescription(new TranslatableMarkup('Tiempo dedicado en minutos.'))
      ->setRequired(TRUE)
      ->setSetting('min', 1)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tarifa por Hora'))
      ->setDescription(new TranslatableMarkup('Tarifa horaria aplicada en euros.'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_billable'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Facturable'))
      ->setDescription(new TranslatableMarkup('Indica si este tiempo es facturable al cliente.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: VINCULACION A FACTURA
    // =========================================================================

    $fields['invoice_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Factura'))
      ->setDescription(new TranslatableMarkup('Factura a la que se ha imputado este tiempo (opcional).'))
      ->setSetting('target_type', 'legal_invoice')
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    return $fields;
  }

}
