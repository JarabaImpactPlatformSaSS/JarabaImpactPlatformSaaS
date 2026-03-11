<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad InscripcionSesionEi.
 *
 * Inscripción de un participante a una sesión programada del programa
 * Andalucía +ei, con tracking de asistencia y cómputo de horas.
 *
 * LABEL-NULLSAFE-001: No hay campo "label" en entity_keys.
 *
 * @ContentEntityType(
 *   id = "inscripcion_sesion_ei",
 *   label = @Translation("Inscripción a Sesión"),
 *   label_collection = @Translation("Inscripciones a Sesiones"),
 *   label_singular = @Translation("inscripción a sesión"),
 *   label_plural = @Translation("inscripciones a sesiones"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\InscripcionSesionEiListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\InscripcionSesionEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\InscripcionSesionEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\InscripcionSesionEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\InscripcionSesionEiAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "inscripcion_sesion_ei",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/inscripciones-sesion-ei/{inscripcion_sesion_ei}",
 *     "add-form" = "/admin/content/inscripciones-sesion-ei/add",
 *     "edit-form" = "/admin/content/inscripciones-sesion-ei/{inscripcion_sesion_ei}/edit",
 *     "delete-form" = "/admin/content/inscripciones-sesion-ei/{inscripcion_sesion_ei}/delete",
 *     "collection" = "/admin/content/inscripciones-sesion-ei",
 *   },
 *   field_ui_base_route = "entity.inscripcion_sesion_ei.settings",
 * )
 */
class InscripcionSesionEi extends ContentEntityBase implements InscripcionSesionEiInterface, EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getSesionId(): ?int {
    $value = $this->get('sesion_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getParticipanteId(): ?int {
    $value = $this->get('participante_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEstado(): string {
    return $this->get('estado')->value ?? self::ESTADO_INSCRITO;
  }

  /**
   * {@inheritdoc}
   */
  public function isAsistenciaVerificada(): bool {
    return (bool) ($this->get('asistencia_verificada')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getHorasComputadas(): float {
    return (float) ($this->get('horas_computadas')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getActuacionStoId(): ?int {
    $value = $this->get('actuacion_sto_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Owner field (uid) provided by EntityOwnerTrait — configure display.
    $fields['uid']
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario que registró la inscripción.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece esta inscripción.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === DATOS DE INSCRIPCIÓN ===

    $fields['sesion_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sesión Programada'))
      ->setDescription(t('Sesión programada a la que se inscribe el participante.'))
      ->setSetting('target_type', 'sesion_programada_ei')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['participante_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participante'))
      ->setDescription(t('Participante inscrito en la sesión.'))
      ->setSetting('target_type', 'programa_participante_ei')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['estado'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la inscripción a la sesión.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        InscripcionSesionEiInterface::ESTADO_INSCRITO => t('Inscrito'),
        InscripcionSesionEiInterface::ESTADO_CONFIRMADO => t('Confirmado'),
        InscripcionSesionEiInterface::ESTADO_ASISTIO => t('Asistió'),
        InscripcionSesionEiInterface::ESTADO_NO_ASISTIO => t('No Asistió'),
        InscripcionSesionEiInterface::ESTADO_CANCELADO => t('Cancelado'),
        InscripcionSesionEiInterface::ESTADO_JUSTIFICADO => t('Justificado'),
      ])
      ->setDefaultValue(InscripcionSesionEiInterface::ESTADO_INSCRITO)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_inscripcion'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Inscripción'))
      ->setDescription(t('Fecha en que el participante se inscribió a la sesión.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === ASISTENCIA ===

    $fields['fecha_asistencia'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Asistencia'))
      ->setDescription(t('Fecha en que se registró la asistencia.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['asistencia_verificada'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Asistencia Verificada'))
      ->setDescription(t('Indica si la asistencia ha sido verificada por un coordinador.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['horas_computadas'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Computadas'))
      ->setDescription(t('Horas que se computan para esta inscripción/asistencia.'))
      ->setDefaultValue(0)
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === REGISTRO STO ===

    $fields['actuacion_sto_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Actuación STO'))
      ->setDescription(t('Actuación STO vinculada a esta inscripción.'))
      ->setSetting('target_type', 'actuacion_sto')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['motivo_cancelacion'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Motivo de Cancelación'))
      ->setDescription(t('Motivo por el que se canceló o justificó la no asistencia.'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === CAMPOS DE SISTEMA ===

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
