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
 * Define la entidad Expediente Juridico (ClientCase).
 *
 * ESTRUCTURA:
 * Entidad pivote del vertical JarabaLex. Representa un expediente juridico
 * que vincula cliente, abogado asignado, area legal, juzgado y plazos.
 * Genera automaticamente un numero de referencia EXP-YYYY-NNNN en preSave().
 *
 * LOGICA:
 * Al crear un expediente, se auto-genera el case_number con formato
 * EXP-YYYY-NNNN donde YYYY es el ano actual y NNNN es un correlativo
 * secuencial. El ciclo de vida es: active -> on_hold -> completed -> archived.
 * Cada cambio de estado genera una CaseActivity via hook_entity_update().
 *
 * RELACIONES:
 * - ClientCase -> User (assigned_to): abogado asignado al expediente.
 * - ClientCase -> User (uid): creador/propietario del expediente.
 * - ClientCase -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 * - ClientCase -> TaxonomyTerm (legal_area): area juridica.
 * - ClientCase <- CaseActivity (case_id): actividades del expediente.
 * - ClientCase <- ClientInquiry (converted_to_case_id): consultas convertidas.
 *
 * @ContentEntityType(
 *   id = "client_case",
 *   label = @Translation("Expediente"),
 *   label_collection = @Translation("Expedientes"),
 *   label_singular = @Translation("expediente"),
 *   label_plural = @Translation("expedientes"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_cases\ListBuilder\ClientCaseListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_cases\Form\ClientCaseForm",
 *       "add" = "Drupal\jaraba_legal_cases\Form\ClientCaseForm",
 *       "edit" = "Drupal\jaraba_legal_cases\Form\ClientCaseForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_cases\Access\ClientCaseAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "client_case",
 *   admin_permission = "manage legal cases",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/legal-cases",
 *     "add-form" = "/admin/content/legal-cases/add",
 *     "canonical" = "/admin/content/legal-cases/{client_case}",
 *     "edit-form" = "/admin/content/legal-cases/{client_case}/edit",
 *     "delete-form" = "/admin/content/legal-cases/{client_case}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_cases.client_case.settings",
 * )
 */
class ClientCase extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    if ($this->isNew() && empty($this->get('case_number')->value)) {
      $year = date('Y');
      $query = $storage->getQuery()
        ->condition('case_number', "EXP-{$year}-", 'STARTS_WITH')
        ->sort('id', 'DESC')
        ->range(0, 1)
        ->accessCheck(FALSE);
      $ids = $query->execute();
      $next = 1;
      if (!empty($ids)) {
        $last = $storage->load(reset($ids));
        if ($last) {
          preg_match('/EXP-\d{4}-(\d{4})/', $last->get('case_number')->value, $m);
          $next = ((int) ($m[1] ?? 0)) + 1;
        }
      }
      $this->set('case_number', sprintf('EXP-%s-%04d', $year, $next));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: IDENTIFICACION DEL EXPEDIENTE
    // =========================================================================

    $fields['case_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Numero de Referencia'))
      ->setDescription(new TranslatableMarkup('Referencia auto-generada EXP-YYYY-NNNN.'))
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('view', ['weight' => -10])
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Titulo'))
      ->setDescription(new TranslatableMarkup('Titulo descriptivo del expediente.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('active')
      ->setSetting('allowed_values', [
        'active' => new TranslatableMarkup('Activo'),
        'on_hold' => new TranslatableMarkup('En espera'),
        'completed' => new TranslatableMarkup('Completado'),
        'archived' => new TranslatableMarkup('Archivado'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['priority'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Prioridad'))
      ->setRequired(TRUE)
      ->setDefaultValue('medium')
      ->setSetting('allowed_values', [
        'low' => new TranslatableMarkup('Baja'),
        'medium' => new TranslatableMarkup('Media'),
        'high' => new TranslatableMarkup('Alta'),
        'critical' => new TranslatableMarkup('Critica'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['case_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de Expediente'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'civil' => new TranslatableMarkup('Civil'),
        'penal' => new TranslatableMarkup('Penal'),
        'laboral' => new TranslatableMarkup('Laboral'),
        'mercantil' => new TranslatableMarkup('Mercantil'),
        'administrativo' => new TranslatableMarkup('Administrativo'),
        'tributario' => new TranslatableMarkup('Tributario'),
        'familia' => new TranslatableMarkup('Familia'),
        'contencioso' => new TranslatableMarkup('Contencioso-Administrativo'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: MULTI-TENANT Y ASIGNACION
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece el expediente.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Asignado a'))
      ->setDescription(new TranslatableMarkup('Abogado o profesional asignado al expediente.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: DATOS DEL CLIENTE
    // =========================================================================

    $fields['client_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre del Cliente'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_email'] = BaseFieldDefinition::create('email')
      ->setLabel(new TranslatableMarkup('Email del Cliente'))
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Telefono del Cliente'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_nif'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('NIF/CIF del Cliente'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: DESCRIPCION Y AREA LEGAL
    // =========================================================================

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Descripcion'))
      ->setDescription(new TranslatableMarkup('Descripcion detallada del expediente.'))
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['legal_area'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Area Legal'))
      ->setDescription(new TranslatableMarkup('Taxonomia del area juridica.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: DATOS JUDICIALES
    // =========================================================================

    $fields['court_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Juzgado'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['court_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Numero de Juzgado'))
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', ['weight' => 31])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['filing_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Interposicion'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', ['weight' => 32])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['next_deadline'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Proximo Plazo'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 33])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['opposing_party'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Parte Contraria'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 34])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 6: VALORACION Y NOTAS
    // =========================================================================

    $fields['estimated_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Valor Estimado'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 40])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Notas Internas'))
      ->setDisplayOptions('form', ['weight' => 41])
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
