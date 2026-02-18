<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Ficha Tecnica STO (APPEND-ONLY — ENTITY-APPEND-001).
 *
 * ESTRUCTURA:
 *   Entidad de contenido inmutable (append-only) que representa una ficha
 *   tecnica del Servicio Territorial de Orientacion (STO). Una vez creada,
 *   no puede ser editada ni eliminada para garantizar la integridad
 *   documental y trazabilidad ante auditorias.
 *
 * LOGICA:
 *   - Solo se permite el formulario de creacion (handler "default").
 *   - No se definen handlers "add", "edit" ni "delete".
 *   - No se implementa EntityChangedInterface (sin campo 'changed').
 *   - El campo ficha_number se auto-genera con formato STO-YYYY-NNNN
 *     (ENTITY-AUTONUMBER-001).
 *   - Soporta fichas generadas por IA (ai_generated, ai_model_used).
 *   - Incluye estado de firma digital (signature_status, signed_at).
 *   - El access handler debe devolver AccessResult::forbidden() para
 *     operaciones de update y delete.
 *
 * RELACIONES:
 *   - tenant_id: referencia a 'group' (AUDIT-CONS-005).
 *   - participant_id: referencia a 'program_participant'.
 *   - pdf_file_id: referencia a 'file' (PDF generado).
 *
 * @ContentEntityType(
 *   id = "sto_ficha",
 *   label = @Translation("Ficha Tecnica STO"),
 *   label_collection = @Translation("Fichas Tecnicas STO"),
 *   label_singular = @Translation("ficha STO"),
 *   label_plural = @Translation("fichas STO"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_institutional\ListBuilder\StoFichaListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_institutional\Form\StoFichaForm",
 *     },
 *     "access" = "Drupal\jaraba_institutional\Access\StoFichaAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "sto_ficha",
 *   admin_permission = "administer institutional",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "ficha_number",
 *   },
 *   links = {
 *     "collection" = "/admin/content/sto-fichas",
 *     "add-form" = "/admin/content/sto-fichas/add",
 *     "canonical" = "/admin/content/sto-fichas/{sto_ficha}",
 *   },
 *   field_ui_base_route = "jaraba_institutional.sto_ficha.settings",
 * )
 */
class StoFicha extends ContentEntityBase {

  /**
   * {@inheritdoc}
   *
   * Auto-numeracion de fichas STO (ENTITY-AUTONUMBER-001).
   *
   * Genera automaticamente el numero de ficha con formato STO-YYYY-NNNN
   * cuando se crea una nueva entidad y el campo ficha_number esta vacio.
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if ($this->isNew() && empty($this->get('ficha_number')->value)) {
      $year = date('Y');
      $prefix = 'STO';
      $query = $storage->getQuery()
        ->condition('ficha_number', $prefix . '-' . $year . '-', 'STARTS_WITH')
        ->sort('id', 'DESC')
        ->range(0, 1)
        ->accessCheck(FALSE);
      $ids = $query->execute();
      $next = 1;
      if (!empty($ids)) {
        $last = $storage->load(reset($ids));
        if ($last) {
          $parts = explode('-', $last->get('ficha_number')->value);
          $next = ((int) end($parts)) + 1;
        }
      }
      $this->set('ficha_number', sprintf('%s-%s-%04d', $prefix, $year, $next));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- tenant_id: referencia al grupo (AUDIT-CONS-005) ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Tenant'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('El grupo (tenant) al que pertenece esta ficha.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- participant_id: referencia al participante ---
    $fields['participant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Participante'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Participante de programa asociado a esta ficha.'))
      ->setSetting('target_type', 'program_participant')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -19,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ficha_number ---
    $fields['ficha_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Numero de ficha'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Auto: STO-YYYY-NNNN'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -18,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ficha_type ---
    $fields['ficha_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Tipo de ficha'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Clasificacion de la ficha dentro del itinerario.'))
      ->setRequired(TRUE)
      ->setDefaultValue('initial')
      ->setSetting('allowed_values', [
        'initial' => 'Ficha inicial',
        'progress' => 'Ficha de seguimiento',
        'final' => 'Ficha final',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -17,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- diagnostico_empleabilidad ---
    $fields['diagnostico_empleabilidad'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Diagnostico de empleabilidad'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Diagnostico de empleabilidad'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- itinerario_insercion ---
    $fields['itinerario_insercion'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Itinerario de insercion'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Itinerario personalizado de insercion'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- acciones_orientacion ---
    $fields['acciones_orientacion'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Acciones de orientacion'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Acciones de orientacion realizadas'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- resultados ---
    $fields['resultados'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Resultados'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Resultados: insercion, formacion, certificaciones'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ai_generated ---
    $fields['ai_generated'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Generado por IA'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Indica si la ficha fue generada por inteligencia artificial.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ai_model_used ---
    $fields['ai_model_used'] = BaseFieldDefinition::create('string')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Modelo IA utilizado'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Identificador del modelo de IA utilizado para generar la ficha.'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- pdf_file_id ---
    $fields['pdf_file_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Archivo PDF'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Archivo PDF generado de la ficha tecnica.'))
      ->setSetting('target_type', 'file')
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- signature_status ---
    $fields['signature_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Estado de firma'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Estado de la firma digital de la ficha.'))
      ->setRequired(FALSE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pendiente',
        'signed' => 'Firmada',
        'rejected' => 'Rechazada',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- signed_at ---
    $fields['signed_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha de firma'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha y hora en que se firmo la ficha.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- created (NO changed — append-only ENTITY-APPEND-001) ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha de creacion'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha y hora de creacion del registro.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
