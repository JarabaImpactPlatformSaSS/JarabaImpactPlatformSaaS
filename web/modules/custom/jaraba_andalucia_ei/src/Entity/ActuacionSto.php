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
 * Define la entidad ActuacionSto.
 *
 * Registra cada actuacion individual del itinerario PIIL:
 * orientacion individual, orientacion grupal, formacion,
 * tutoria, prospección, intermediación.
 *
 * @ContentEntityType(
 *   id = "actuacion_sto",
 *   label = @Translation("Actuación STO"),
 *   label_collection = @Translation("Actuaciones STO"),
 *   label_singular = @Translation("actuación STO"),
 *   label_plural = @Translation("actuaciones STO"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ActuacionStoListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\ActuacionStoForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\ActuacionStoForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\ActuacionStoForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\ActuacionStoAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "actuacion_sto",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "contenido",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/actuaciones-sto/{actuacion_sto}",
 *     "add-form" = "/admin/content/actuaciones-sto/add",
 *     "edit-form" = "/admin/content/actuaciones-sto/{actuacion_sto}/edit",
 *     "delete-form" = "/admin/content/actuaciones-sto/{actuacion_sto}/delete",
 *     "collection" = "/admin/content/actuaciones-sto",
 *   },
 *   field_ui_base_route = "entity.actuacion_sto.settings",
 * )
 */
class ActuacionSto extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Tipos de actuación válidos.
   */
  public const TIPOS_ACTUACION = [
    'orientacion_individual' => 'Orientación individual',
    'orientacion_grupal' => 'Orientación grupal',
    'formacion' => 'Formación',
    'tutoria' => 'Tutoría',
    'prospeccion' => 'Prospección empresarial',
    'intermediacion' => 'Intermediación laboral',
  ];

  /**
   * Lugares válidos.
   */
  public const LUGARES = [
    'presencial_sede' => 'Presencial (sede)',
    'presencial_empresa' => 'Presencial (empresa)',
    'online_videoconf' => 'Online (videoconferencia)',
    'online_plataforma' => 'Online (plataforma)',
    'telefonico' => 'Telefónico',
  ];

  /**
   * Obtiene el tipo de actuación.
   */
  public function getTipoActuacion(): string {
    return $this->get('tipo_actuacion')->value ?? '';
  }

  /**
   * Obtiene la duración en minutos.
   */
  public function getDuracionMinutos(): int {
    return (int) ($this->get('duracion_minutos')->value ?? 0);
  }

  /**
   * Obtiene la duración en horas (decimal).
   */
  public function getDuracionHoras(): float {
    return round($this->getDuracionMinutos() / 60, 2);
  }

  /**
   * Indica si es una actuación grupal.
   */
  public function isGrupal(): bool {
    return $this->getTipoActuacion() === 'orientacion_grupal';
  }

  /**
   * Indica si requiere VoBo SAE.
   */
  public function requiereVoboSae(): bool {
    return $this->getTipoActuacion() === 'formacion';
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Owner.
    $fields['uid']
      ->setLabel(t('Registrado por'))
      ->setDescription(t('Usuario que registra la actuación.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece esta actuación.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === DATOS DE LA ACTUACIÓN ===

    $fields['participante_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participante'))
      ->setDescription(t('Participante al que se dirige la actuación.'))
      ->setSetting('target_type', 'programa_participante_ei')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo_actuacion'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Actuación'))
      ->setDescription(t('Tipo de actuación del itinerario PIIL.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', array_map('t', self::TIPOS_ACTUACION))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha'))
      ->setDescription(t('Fecha de la actuación.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hora_inicio'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hora Inicio'))
      ->setDescription(t('Hora de inicio (HH:MM).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 5)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hora_fin'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hora Fin'))
      ->setDescription(t('Hora de finalización (HH:MM).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 5)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['duracion_minutos'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duración (minutos)'))
      ->setDescription(t('Duración calculada automáticamente desde hora inicio/fin.'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['contenido'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Contenido'))
      ->setDescription(t('Descripción breve de la actuación realizada.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resultado'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Resultado'))
      ->setDescription(t('Resultado y conclusiones de la actuación.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['lugar'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Lugar'))
      ->setDescription(t('Modalidad y lugar de la actuación.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', array_map('t', self::LUGARES))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['orientador_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Orientador/Formador'))
      ->setDescription(t('Profesional que realiza la actuación.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === FASE Y FIRMA ===

    $fields['fase_participante'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Fase al momento'))
      ->setDescription(t('Fase PIIL del participante cuando se realizó la actuación.'))
      ->setSetting('allowed_values', [
        'acogida' => t('Acogida'),
        'diagnostico' => t('Diagnóstico'),
        'atencion' => t('Atención'),
        'insercion' => t('Inserción'),
        'seguimiento' => t('Seguimiento'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['recibo_servicio_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recibo de Servicio'))
      ->setDescription(t('Documento recibo de servicio generado.'))
      ->setSetting('target_type', 'expediente_documento')
      ->setDisplayConfigurable('view', TRUE);

    $fields['firmado_participante'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Firmado por Participante'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['firmado_orientador'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Firmado por Orientador'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === VOBO SAE (solo formación) ===

    $fields['vobo_sae_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('VoBo SAE'))
      ->setDescription(t('Estado del Visto Bueno del SAE para formación.'))
      ->setSetting('allowed_values', [
        'no_requerido' => t('No requerido'),
        'pendiente' => t('Pendiente'),
        'aprobado' => t('Aprobado'),
        'rechazado' => t('Rechazado'),
      ])
      ->setDefaultValue('no_requerido')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vobo_sae_fecha'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha VoBo SAE'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vobo_sae_documento_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Documento VoBo SAE'))
      ->setSetting('target_type', 'expediente_documento')
      ->setDisplayConfigurable('view', TRUE);

    // === GRUPAL ===

    $fields['grupo_participantes_ids'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Participantes (grupal)'))
      ->setDescription(t('IDs de participantes separados por coma para actuaciones grupales.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === STO ===

    $fields['sto_exportado'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Exportado a STO'))
      ->setDescription(t('Si esta actuación fue incluida en una exportación STO.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === SISTEMA ===

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
