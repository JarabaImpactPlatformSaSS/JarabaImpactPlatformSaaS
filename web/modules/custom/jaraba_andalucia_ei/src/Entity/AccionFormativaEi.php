<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad AccionFormativaEi.
 *
 * Representa una acción formativa del programa Andalucía +ei.
 * Cada acción requiere aprobación VoBo del SAE para ser legalmente
 * válida y poder computar horas de formación para la justificación
 * económica. Soporta revisiones para audit trail del workflow VoBo.
 *
 * @ContentEntityType(
 *   id = "accion_formativa_ei",
 *   label = @Translation("Acción Formativa"),
 *   label_collection = @Translation("Acciones Formativas"),
 *   label_singular = @Translation("acción formativa"),
 *   label_plural = @Translation("acciones formativas"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\AccionFormativaEiListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\AccionFormativaEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\AccionFormativaEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\AccionFormativaEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\AccionFormativaEiAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "accion_formativa_ei",
 *   revision_table = "accion_formativa_ei_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "label" = "titulo",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/acciones-formativas-ei/{accion_formativa_ei}",
 *     "add-form" = "/admin/content/acciones-formativas-ei/add",
 *     "edit-form" = "/admin/content/acciones-formativas-ei/{accion_formativa_ei}/edit",
 *     "delete-form" = "/admin/content/acciones-formativas-ei/{accion_formativa_ei}/delete",
 *     "collection" = "/admin/content/acciones-formativas-ei",
 *     "version-history" = "/admin/content/acciones-formativas-ei/{accion_formativa_ei}/revisions",
 *     "revision" = "/admin/content/acciones-formativas-ei/{accion_formativa_ei}/revision/{accion_formativa_ei_revision}/view",
 *   },
 *   field_ui_base_route = "entity.accion_formativa_ei.settings",
 * )
 */
class AccionFormativaEi extends ContentEntityBase implements AccionFormativaEiInterface, EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitulo(): string {
    return $this->get('titulo')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTipoFormacion(): string {
    return $this->get('tipo_formacion')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getHorasPrevistas(): float {
    return (float) ($this->get('horas_previstas')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getCarril(): string {
    return $this->get('carril')->value ?? 'comun';
  }

  /**
   * {@inheritdoc}
   */
  public function getEstado(): string {
    return $this->get('estado')->value ?? 'borrador';
  }

  /**
   * {@inheritdoc}
   */
  public function requiereVoboSae(): bool {
    // Toda acción de tipo formación requiere VoBo SAE según PIIL BBRR Art.7.
    $tipo = $this->getTipoFormacion();
    return in_array($tipo, [
      'presencial',
      'online_sincrona',
      'online_asincrona',
      'mixta',
      'taller_practico',
    ], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function isVoboAprobado(): bool {
    return $this->getEstado() === 'vobo_aprobado';
  }

  /**
   * {@inheritdoc}
   */
  public function canExecute(): bool {
    if (!$this->requiereVoboSae()) {
      return TRUE;
    }
    return in_array($this->getEstado(), ['vobo_aprobado', 'en_ejecucion', 'finalizada'], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCategoria(): string {
    return $this->get('categoria')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getModalidad(): string {
    return $this->get('modalidad')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCourseId(): ?int {
    $value = $this->get('course_id')->value;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    // Owner.
    $fields['uid']
      ->setLabel(t('Creado por'))
      ->setDescription(t('Coordinador/a que define la acción formativa.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece esta acción formativa.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === DATOS PRINCIPALES ===

    $fields['titulo'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título'))
      ->setDescription(t('Nombre de la acción formativa. Ejemplo: "Módulo 1: Competencias Digitales Básicas".'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['descripcion'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Objetivos y contenidos de la acción formativa. Incluya competencias a desarrollar.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo_formacion'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Formación'))
      ->setDescription(t('Modalidad formativa según normativa PIIL. Determina si requiere VoBo del SAE.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', array_map('t', AccionFormativaEiInterface::TIPOS_FORMACION))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['categoria'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Categoría Temática'))
      ->setDescription(t('Área de conocimiento principal.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', array_map('t', AccionFormativaEiInterface::CATEGORIAS))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['modalidad'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modalidad'))
      ->setDescription(t('Cómo se imparte la formación.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', array_map('t', AccionFormativaEiInterface::MODALIDADES))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['carril'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Carril'))
      ->setDescription(t('Carril del programa al que aplica esta acción. "Común" aplica a todos.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue('comun')
      ->setSetting('allowed_values', array_map('t', AccionFormativaEiInterface::CARRILES))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === HORAS Y DURACIÓN ===

    $fields['horas_previstas'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Previstas'))
      ->setDescription(t('Duración total prevista en horas. Ejemplo: 10.5 para diez horas y media.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['horas_ejecutadas'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Ejecutadas'))
      ->setDescription(t('Horas efectivamente impartidas. Se actualiza automáticamente desde las sesiones.'))
      ->setDefaultValue('0.00')
      ->setRevisionable(TRUE)
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['numero_sesiones'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Número de Sesiones'))
      ->setDescription(t('Cantidad de sesiones planificadas.'))
      ->setDefaultValue(0)
      ->setRevisionable(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === VOBO SAE ===

    $fields['estado'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la acción formativa en el workflow VoBo SAE.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue('borrador')
      ->setSetting('allowed_values', array_map('t', AccionFormativaEiInterface::ESTADOS))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vobo_codigo'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código VoBo SAE'))
      ->setDescription(t('Código de aprobación emitido por el SAE.'))
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vobo_fecha_envio'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Envío VoBo'))
      ->setDescription(t('Fecha en que se envió la solicitud de VoBo al SAE.'))
      ->setRevisionable(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vobo_fecha_respuesta'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Respuesta VoBo'))
      ->setDescription(t('Fecha en que el SAE respondió a la solicitud de VoBo.'))
      ->setRevisionable(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vobo_motivo_rechazo'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Motivo Rechazo VoBo'))
      ->setDescription(t('Motivo del rechazo emitido por el SAE (para subsanación).'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vobo_documento_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Documento VoBo'))
      ->setDescription(t('Documento de solicitud/respuesta de VoBo en el expediente.'))
      ->setSetting('target_type', 'expediente_documento')
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === SPRINT 14: ALINEAMIENTO STO Y MATERIALES ===

    $fields['contenido_sto'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Contenido STO'))
      ->setDescription(t('Tipificación de contenido según STO para acciones formativas.'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values_function', 'jaraba_andalucia_ei_contenidos_formacion_sto')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subcontenido_sto'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Subcontenido STO'))
      ->setDescription(t('Subcontenido formativo según tipificación STO.'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values_function', 'jaraba_andalucia_ei_subcontenidos_formacion_sto')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // ENTITY-FK-001: Same module = entity_reference.
    $fields['materiales'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Materiales Didácticos'))
      ->setDescription(t('Materiales y recursos vinculados a esta acción formativa.'))
      ->setSetting('target_type', 'material_didactico_ei')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === REFERENCIAS CROSS-MODULE (ENTITY-FK-001: integer para cross-module) ===

    $fields['course_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Curso LMS'))
      ->setDescription(t('ID del curso en jaraba_lms (si existe contenido digital asociado).'))
      ->setRevisionable(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['interactive_content_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Contenido Interactivo'))
      ->setDescription(t('ID del contenido interactivo en jaraba_interactive (si existe).'))
      ->setRevisionable(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === FORMADOR ===

    $fields['formador_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Formador/a'))
      ->setDescription(t('Profesional responsable de impartir la formación.'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['formador_nombre'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Formador (externo)'))
      ->setDescription(t('Si el formador no es usuario de la plataforma.'))
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === METADATOS ===

    $fields['orden'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Orden'))
      ->setDescription(t('Orden de la acción dentro del plan formativo.'))
      ->setDefaultValue(0)
      ->setRevisionable(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notas_internas'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas Internas'))
      ->setDescription(t('Notas internas del coordinador (no visibles para participantes).'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === TIMESTAMPS ===

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creación'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Última actualización'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
