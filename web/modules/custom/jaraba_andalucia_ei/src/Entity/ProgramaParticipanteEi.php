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
 * Define la entidad ProgramaParticipanteEi.
 *
 * Participante en el Programa Andalucía +ei con datos de seguimiento,
 * tracking de horas de mentoría IA y transiciones de fase PIIL.
 *
 * @ContentEntityType(
 *   id = "programa_participante_ei",
 *   label = @Translation("Participante Andalucía +ei"),
 *   label_collection = @Translation("Participantes Andalucía +ei"),
 *   label_singular = @Translation("participante Andalucía +ei"),
 *   label_plural = @Translation("participantes Andalucía +ei"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ProgramaParticipanteEiListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\ProgramaParticipanteEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\ProgramaParticipanteEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\ProgramaParticipanteEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\ProgramaParticipanteEiAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "programa_participante_ei",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "dni_nie",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/andalucia-ei/{programa_participante_ei}",
 *     "add-form" = "/admin/content/andalucia-ei/add",
 *     "edit-form" = "/admin/content/andalucia-ei/{programa_participante_ei}/edit",
 *     "delete-form" = "/admin/content/andalucia-ei/{programa_participante_ei}/delete",
 *     "collection" = "/admin/content/andalucia-ei",
 *   },
 *   field_ui_base_route = "entity.programa_participante_ei.settings",
 * )
 */
class ProgramaParticipanteEi extends ContentEntityBase implements ProgramaParticipanteEiInterface, EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getDniNie(): string {
    return $this->get('dni_nie')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setDniNie(string $dni_nie): self {
    $this->set('dni_nie', $dni_nie);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getColectivo(): string {
    return $this->get('colectivo')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getFaseActual(): string {
    return $this->get('fase_actual')->value ?? 'acogida';
  }

  /**
   * {@inheritdoc}
   */
  public function setFaseActual(string $fase): self {
    $this->set('fase_actual', $fase);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHorasMentoriaIa(): float {
    return (float) ($this->get('horas_mentoria_ia')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getHorasMentoriaHumana(): float {
    return (float) ($this->get('horas_mentoria_humana')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalHorasOrientacion(): float {
    $individual = (float) ($this->get('horas_orientacion_ind')->value ?? 0);
    $grupal = (float) ($this->get('horas_orientacion_grup')->value ?? 0);
    return $individual + $grupal + $this->getHorasMentoriaIa() + $this->getHorasMentoriaHumana();
  }

  /**
   * {@inheritdoc}
   */
  public function canTransitToInsercion(): bool {
    // Sprint 15: Los 4 criterios normativos PIIL BBRR para persona atendida.
    // 1. ≥10h orientación laboral (individual + grupal + mentoría).
    $horasOrientacion = $this->getTotalHorasOrientacion();
    // 2. ≥2h orientación individual (normativa PIIL BBRR Art. 6.2).
    $horasIndividual = (float) ($this->get('horas_orientacion_ind')->value ?? 0);
    // 3. ≥50h formación.
    $horasFormacion = (float) ($this->get('horas_formacion')->value ?? 0);
    // 4. ≥75% asistencia a sesiones formativas.
    $asistencia = (float) ($this->get('asistencia_porcentaje')->value ?? 0);

    return $horasOrientacion >= 10
            && $horasIndividual >= 2
            && $horasFormacion >= 50
            && $asistencia >= 75;
  }

  /**
   * {@inheritdoc}
   */
  public function hasReceivedIncentivo(): bool {
    return (bool) ($this->get('incentivo_recibido')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getIncentivoFechaPago(): ?string {
    return $this->get('incentivo_fecha_pago')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRenunciadoIncentivo(): bool {
    return (bool) ($this->get('incentivo_renuncia')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getIncentivoRenunciaFecha(): ?string {
    return $this->get('incentivo_renuncia_fecha')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isAcuerdoParticipacionFirmado(): bool {
    return (bool) ($this->get('acuerdo_participacion_firmado')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getAcuerdoParticipacionFecha(): ?string {
    return $this->get('acuerdo_participacion_fecha')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isDaciFirmado(): bool {
    return (bool) ($this->get('daci_firmado')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isFseEntradaCompletado(): bool {
    return (bool) ($this->get('fse_entrada_completado')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getSemanaActual(): int {
    return (int) ($this->get('semana_actual')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getMotivoBaja(): string {
    return $this->get('motivo_baja')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDimeScore(): ?int {
    $value = $this->get('dime_score')->value;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getHorasOrientacionInsercion(): float {
    return (float) ($this->get('horas_orientacion_insercion')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getAsistenciaPorcentaje(): float {
    return (float) ($this->get('asistencia_porcentaje')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function isPersonaAtendida(): bool {
    return (bool) ($this->get('es_persona_atendida')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isPersonaInsertada(): bool {
    return (bool) ($this->get('es_persona_insertada')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isAlumni(): bool {
    return (bool) ($this->get('is_alumni')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidateProfileId(): ?int {
    $value = $this->get('candidate_profile_id')->value;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCanvasId(): ?int {
    $value = $this->get('canvas_id')->value;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // === DATOS DE IDENTIFICACIÓN ===
    // Owner field (uid) provided by EntityOwnerTrait — configure display.
    $fields['uid']
      ->setLabel(t('Usuario Drupal'))
      ->setDescription(t('Usuario vinculado al participante.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este participante.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Grupo Andalucía +ei'))
      ->setDescription(t('Grupo del programa al que pertenece.'))
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['dni_nie'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DNI/NIE'))
      ->setDescription(t('Documento identificativo del participante.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 12)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['colectivo'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Colectivo'))
      ->setDescription(t('Colectivo vulnerable destino PIIL CV 2025.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'larga_duracion' => t('Desempleados larga duración (>12 meses)'),
        'mayores_45' => t('Mayores de 45 años'),
        'migrantes' => t('Personas migrantes'),
        'perceptores_prestaciones' => t('Perceptores de prestaciones/subsidios'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Sprint 15: Restringido a Málaga + Sevilla según Ficha Técnica FT_679.
    // 15 proyectos Málaga + 30 proyectos Sevilla = 45 total.
    $fields['provincia_participacion'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Provincia'))
      ->setDescription(t('Provincia de inscripción en el STO (FT_679: Málaga 15 + Sevilla 30).'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'malaga' => t('Málaga'),
        'sevilla' => t('Sevilla'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_alta_sto'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Alta STO'))
      ->setDescription(t('Fecha de registro en el STO (inmutable).'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === FASE PIIL ===
    $fields['fase_actual'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Fase PIIL'))
      ->setDescription(t('Fase actual del participante en el itinerario PIIL CV 2025.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'acogida' => t('Acogida'),
        'diagnostico' => t('Diagnóstico'),
        'atencion' => t('Atención'),
        'insercion' => t('Inserción'),
        'seguimiento' => t('Seguimiento'),
        'baja' => t('Baja'),
      ])
      ->setDefaultValue('acogida')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === TRACKING DE HORAS ===
    $fields['horas_orientacion_ind'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Orientación Individual'))
      ->setDescription(t('Horas de orientación individual acumuladas.'))
      ->setDefaultValue(0)
      ->setSetting('precision', 8)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['horas_orientacion_grup'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Orientación Grupal'))
      ->setDescription(t('Horas de orientación grupal acumuladas.'))
      ->setDefaultValue(0)
      ->setSetting('precision', 8)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['horas_formacion'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Formación'))
      ->setDescription(t('Horas de formación acumuladas (LMS + talleres).'))
      ->setDefaultValue(0)
      ->setSetting('precision', 8)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['horas_mentoria_ia'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Mentoría IA'))
      ->setDescription(t('Horas acumuladas con el Tutor IA (Copilot).'))
      ->setDefaultValue(0)
      ->setSetting('precision', 8)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['horas_mentoria_humana'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Mentoría Humana'))
      ->setDescription(t('Horas acumuladas con mentor humano.'))
      ->setDefaultValue(0)
      ->setSetting('precision', 8)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === CARRIL Y PROGRAMA ===
    $fields['carril'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Carril'))
      ->setDescription(t('Carril del programa seleccionado.'))
      ->setSetting('allowed_values', [
        'impulso_digital' => t('Impulso Digital (Empleabilidad)'),
        'acelera_pro' => t('Acelera Pro (Emprendimiento)'),
        'hibrido' => t('Híbrido'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === INCENTIVO ECONÓMICO ===
    $fields['incentivo_recibido'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Incentivo €528 Recibido'))
      ->setDescription(t('Indica si el participante ha recibido el incentivo económico.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['incentivo_fecha_pago'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Pago Incentivo'))
      ->setDescription(t('Fecha en que se pagó el incentivo económico de €528.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['incentivo_renuncia'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Renuncia al Incentivo'))
      ->setDescription(t('Indica si el participante ha renunciado al incentivo económico de €528.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['incentivo_renuncia_fecha'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Renuncia Incentivo'))
      ->setDescription(t('Fecha en que el participante formalizó la renuncia al incentivo.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === INSERCIÓN LABORAL ===
    $fields['tipo_insercion'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Inserción'))
      ->setDescription(t('Tipo de inserción laboral conseguida.'))
      ->setSetting('allowed_values', [
        'cuenta_ajena' => t('Cuenta Ajena'),
        'cuenta_propia' => t('Cuenta Propia'),
        'agrario' => t('Especial Agrario'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_insercion'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Inserción'))
      ->setDescription(t('Fecha de inserción laboral verificada.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === SINCRONIZACIÓN STO ===
    $fields['sto_sync_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado Sincronización STO'))
      ->setDescription(t('Estado de sincronización con el STO.'))
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'synced' => t('Sincronizado'),
        'error' => t('Error'),
      ])
      ->setDefaultValue('pending')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === ACUERDO DE PARTICIPACIÓN ===
    // Documento bilateral: Acuerdo_participacion_ICV25.odt
    // Firmado por el participante al inicio del programa.
    $fields['acuerdo_participacion_firmado'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Acuerdo de Participación Firmado'))
      ->setDescription(t('Indica si se ha firmado el Acuerdo de Participación bilateral (Acuerdo_participacion_ICV25).'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['acuerdo_participacion_fecha'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Firma Acuerdo de Participación'))
      ->setDescription(t('Fecha en que se firmó el Acuerdo de Participación.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === DACI (Documento de Aceptación de Compromisos e Información) ===
    // Anexo normativo: Anexo_DACI_ICV25.odt
    // El participante acepta compromisos y es informado de sus derechos.
    // Documento DISTINTO del Acuerdo de Participación.
    $fields['daci_firmado'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('DACI Firmado'))
      ->setDescription(t('Indica si se ha firmado el DACI (Documento de Aceptación de Compromisos e Información, Anexo_DACI_ICV25).'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['daci_fecha_firma'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Firma DACI'))
      ->setDescription(t('Fecha en que se firmó el DACI (Anexo_DACI_ICV25).'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === FSE+ ===
    $fields['fse_entrada_completado'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('FSE+ Entrada Completado'))
      ->setDescription(t('Indicadores FSE+ en el momento de entrada recogidos.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fse_salida_completado'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('FSE+ Salida Completado'))
      ->setDescription(t('Indicadores FSE+ en el momento de salida recogidos.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['indicadores_6m_completado'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Indicadores 6 Meses Completado'))
      ->setDescription(t('Indicadores FSE+ de resultado a los 6 meses post-salida recogidos.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === PROGRAMA Y TEMPORALIZACIÓN ===
    $fields['fecha_inicio_programa'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Inicio Programa'))
      ->setDescription(t('Fecha de inicio del itinerario personalizado.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_fin_programa'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Fin Programa'))
      ->setDescription(t('Fecha de finalización o baja del itinerario.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['semana_actual'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Semana Actual'))
      ->setDescription(t('Semana del programa en la que se encuentra el participante.'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['motivo_baja'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Motivo de Baja'))
      ->setDescription(t('Motivo de baja del programa, si aplica.'))
      ->setSetting('allowed_values', [
        'abandono_voluntario' => t('Abandono voluntario'),
        'insercion_lograda' => t('Inserción laboral lograda'),
        'incumplimiento' => t('Incumplimiento de compromisos'),
        'fin_programa' => t('Finalización del periodo del programa'),
        'exclusion_normativa' => t('Exclusión por causa normativa'),
        'otro' => t('Otro motivo'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === DIAGNÓSTICO DIME ===
    $fields['dime_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Score DIME'))
      ->setDescription(t('Puntuación del diagnóstico DIME (0-20). Sincronizado desde Copilot v2.'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['dime_fecha'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Diagnóstico DIME'))
      ->setDescription(t('Fecha en que se completó el diagnóstico DIME.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === HORAS ORIENTACIÓN PARA INSERCIÓN ===
    $fields['horas_orientacion_insercion'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Orientación Inserción'))
      ->setDescription(t('Horas de orientación específicas para inserción laboral (de las 40h requeridas para módulo inserción).'))
      ->setDefaultValue(0)
      ->setSetting('precision', 8)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['asistencia_porcentaje'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Porcentaje de Asistencia'))
      ->setDescription(t('Porcentaje de asistencia calculado sobre actuaciones programadas.'))
      ->setDefaultValue(0)
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === MÓDULOS ECONÓMICOS (computed) ===
    $fields['es_persona_atendida'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('¿Persona Atendida?'))
      ->setDescription(t('Cumple requisitos de persona atendida: ≥10h orientación (≥2h individual) + ≥50h formación + ≥75% asistencia.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['es_persona_insertada'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('¿Persona Insertada?'))
      ->setDescription(t('Cumple requisitos de persona insertada: persona atendida + ≥40h orientación inserción + ≥4 meses alta SS.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === INTEGRACIÓN CROSS-VERTICAL ===
    // ENTITY-FK-001: FKs cross-módulo como integer (no entity_reference).
    $fields['candidate_profile_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Perfil de Candidato'))
      ->setDescription(t('ID del CandidateProfile en jaraba_candidate (cross-vertical).'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['canvas_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Business Model Canvas'))
      ->setDescription(t('ID del BusinessModelCanvas en jaraba_business_tools (solo carril Acelera, cross-vertical).'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === ALUMNI ===
    $fields['is_alumni'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('¿Alumni?'))
      ->setDescription(t('El participante ha completado el programa con éxito y forma parte del Club Alumni.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['alumni_fecha'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Alumni'))
      ->setDescription(t('Fecha en que se convirtió en alumni.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('view', TRUE);

    $fields['alumni_disponible_mentoria'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Disponible para Mentoría Alumni'))
      ->setDescription(t('El alumni está disponible para mentorizar a participantes de futuras ediciones.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === SPRINT 8: BARRERAS DE ACCESO ===
    // Sprint 8 — Motor de Adaptación por Colectivo y Barreras.
    // JSON estructurado con 8 tipos de barrera: idioma, brecha_digital,
    // carga_cuidados, situacion_administrativa, vivienda, salud_mental,
    // violencia_genero, movilidad_geografica.
    $fields['barreras_acceso'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Barreras de acceso'))
      ->setDescription(t('JSON con tipos de barrera y niveles para adaptación del itinerario.'))
      ->setDisplayConfigurable('form', TRUE);

    // === CAMPOS 2ª EDICIÓN (SPEC-2E-001) ===
    $fields['ruta_programa'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Ruta del programa'))
      ->setDescription(t('Ruta elegida por el participante durante Orientación Inicial.'))
      ->setSetting('allowed_values', [
        'autoempleo' => t('Ruta A — Autoempleo'),
        'empleo' => t('Ruta B — Empleo por cuenta ajena'),
        'hibrida' => t('Ruta Híbrida'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nivel_digital'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Nivel digital'))
      ->setDescription(t('Evaluado en OI-1.1. Determina intensidad de acompañamiento en Módulo 0.'))
      ->setSetting('allowed_values', [
        'autonomo' => t('A — Autónomo (usa smartphone y apps con soltura)'),
        'apoyo' => t('B — Necesita apoyo (usa WhatsApp pero poco más)'),
        'nivelacion' => t('C — Nivelación (dificultades significativas)'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['pack_preseleccionado'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Packs preseleccionados'))
      ->setDescription(t('JSON array de 1-3 IDs de packs preseleccionados en Orientación Inicial.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['pack_confirmado'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Pack confirmado'))
      ->setDescription(t('Pack de servicios elegido definitivamente en Módulo 1.'))
      ->setSetting('allowed_values', [
        'contenido_digital' => t('Pack 1 — Contenido Digital (desde 150€/mes)'),
        'asistente_virtual' => t('Pack 2 — Asistente Virtual (desde 150€/mes)'),
        'presencia_online' => t('Pack 3 — Presencia Online (desde 150€/mes)'),
        'tienda_digital' => t('Pack 4 — Tienda Digital (desde 300€/mes)'),
        'community_manager' => t('Pack 5 — Community Manager (desde 150€/mes)'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['objetivos_smart'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Objetivos SMART'))
      ->setDescription(t('JSON array de 3 objetivos {objetivo, indicador, plazo}. Definidos en OI-2.2.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['perfil_riasec'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Perfil RIASEC'))
      ->setDescription(t('JSON resultado del test de intereses vocacionales Holland (6 dimensiones).'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['compromiso_firmado'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Compromiso de Participación Firmado'))
      ->setDescription(t('Documento de compromiso firmado por el participante.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['compromiso_fecha'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Firma Compromiso'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('form', TRUE);

    $fields['estado_programa_2e'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado 2ª Edición'))
      ->setDescription(t('Estado granular del participante en la 2ª Edición del programa.'))
      ->setSetting('allowed_values', [
        'inscrito' => t('Inscrito (pendiente inicio OI)'),
        'orientacion' => t('En Orientación Inicial'),
        'formacion' => t('En Formación (Módulos 0-5)'),
        'acompanamiento' => t('En Acompañamiento Inserción'),
        'insertado' => t('Insertado (≥4 meses SS)'),
        'baja' => t('Baja'),
      ])
      ->setDefaultValue('inscrito')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['meses_ss_acumulados'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Meses SS acumulados'))
      ->setDescription(t('Meses de alta en Seguridad Social acumulados. Objetivo ≥4 (3 si agrario).'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['negocio_piloto_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Negocio piloto asignado'))
      ->setDescription(t('FK al negocio prospectado asignado como cliente piloto (ENTITY-FK-001: integer para cross-entity).'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['pack_servicio_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Pack de servicio publicado'))
      ->setDescription(t('FK al PackServicioEi publicado por el participante.'))
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
