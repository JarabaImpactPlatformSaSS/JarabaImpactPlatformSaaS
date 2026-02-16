<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Entity;

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
 * Define la entidad Consulta Juridica (ClientInquiry).
 *
 * ESTRUCTURA:
 * Consulta pre-expediente recibida por cualquier canal (web, telefono,
 * email, referido, LexNET). Puede convertirse en un expediente ClientCase
 * tras el triaje y asignacion.
 *
 * LOGICA:
 * Al crear una consulta se auto-genera inquiry_number con formato
 * CON-YYYY-NNNN. El ciclo de vida es: pending -> triaged -> assigned
 * -> converted | rejected. La conversion a expediente se realiza via
 * InquiryManagerService::convertToCase().
 *
 * RELACIONES:
 * - ClientInquiry -> User (uid): creador/propietario.
 * - ClientInquiry -> User (assigned_to): abogado asignado.
 * - ClientInquiry -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 * - ClientInquiry -> ClientCase (converted_to_case_id): expediente convertido.
 * - ClientInquiry <- InquiryTriage (inquiry_id): resultado del triaje IA.
 *
 * @ContentEntityType(
 *   id = "client_inquiry",
 *   label = @Translation("Consulta Juridica"),
 *   label_collection = @Translation("Consultas Juridicas"),
 *   label_singular = @Translation("consulta"),
 *   label_plural = @Translation("consultas"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_cases\ListBuilder\ClientInquiryListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_cases\Form\ClientInquiryForm",
 *       "add" = "Drupal\jaraba_legal_cases\Form\ClientInquiryForm",
 *       "edit" = "Drupal\jaraba_legal_cases\Form\ClientInquiryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_cases\Access\ClientInquiryAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "client_inquiry",
 *   admin_permission = "manage legal inquiries",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "subject",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/legal-inquiries",
 *     "add-form" = "/admin/content/legal-inquiries/add",
 *     "canonical" = "/admin/content/legal-inquiries/{client_inquiry}",
 *     "edit-form" = "/admin/content/legal-inquiries/{client_inquiry}/edit",
 *     "delete-form" = "/admin/content/legal-inquiries/{client_inquiry}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_cases.client_inquiry.settings",
 * )
 */
class ClientInquiry extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    if ($this->isNew() && empty($this->get('inquiry_number')->value)) {
      $year = date('Y');
      $query = $storage->getQuery()
        ->condition('inquiry_number', "CON-{$year}-", 'STARTS_WITH')
        ->sort('id', 'DESC')
        ->range(0, 1)
        ->accessCheck(FALSE);
      $ids = $query->execute();
      $next = 1;
      if (!empty($ids)) {
        $last = $storage->load(reset($ids));
        if ($last) {
          preg_match('/CON-\d{4}-(\d{4})/', $last->get('inquiry_number')->value, $m);
          $next = ((int) ($m[1] ?? 0)) + 1;
        }
      }
      $this->set('inquiry_number', sprintf('CON-%s-%04d', $year, $next));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: IDENTIFICACION DE LA CONSULTA
    // =========================================================================

    $fields['inquiry_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Numero de Consulta'))
      ->setDescription(new TranslatableMarkup('Referencia auto-generada CON-YYYY-NNNN.'))
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('view', ['weight' => -10])
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Asunto'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => new TranslatableMarkup('Pendiente'),
        'triaged' => new TranslatableMarkup('Triada'),
        'assigned' => new TranslatableMarkup('Asignada'),
        'converted' => new TranslatableMarkup('Convertida a Expediente'),
        'rejected' => new TranslatableMarkup('Rechazada'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['source'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Origen'))
      ->setRequired(TRUE)
      ->setDefaultValue('web_form')
      ->setSetting('allowed_values', [
        'web_form' => new TranslatableMarkup('Formulario Web'),
        'phone' => new TranslatableMarkup('Telefono'),
        'email' => new TranslatableMarkup('Email'),
        'referral' => new TranslatableMarkup('Referido'),
        'lexnet' => new TranslatableMarkup('LexNET'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['priority'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Prioridad'))
      ->setDefaultValue('medium')
      ->setSetting('allowed_values', [
        'low' => new TranslatableMarkup('Baja'),
        'medium' => new TranslatableMarkup('Media'),
        'high' => new TranslatableMarkup('Alta'),
        'critical' => new TranslatableMarkup('Critica'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: MULTI-TENANT Y ASIGNACION
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Asignado a'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: DATOS DEL CONSULTANTE
    // =========================================================================

    $fields['client_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre del Consultante'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_email'] = BaseFieldDefinition::create('email')
      ->setLabel(new TranslatableMarkup('Email del Consultante'))
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Telefono del Consultante'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: DESCRIPCION
    // =========================================================================

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Descripcion'))
      ->setDescription(new TranslatableMarkup('Detalle de la consulta juridica.'))
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['case_type_requested'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Tipo de Caso Solicitado'))
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: CONVERSION A EXPEDIENTE
    // =========================================================================

    $fields['converted_to_case_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Expediente Convertido'))
      ->setDescription(new TranslatableMarkup('ID del expediente ClientCase al que se convirtio esta consulta.'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Notas Internas'))
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 6: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'));

    return $fields;
  }

}
