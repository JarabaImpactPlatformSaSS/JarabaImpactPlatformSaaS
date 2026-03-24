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
 * Define la entidad NegocioProspectadoEi.
 *
 * Registra negocios prospectados por participantes del programa Andalucía +ei
 * para la fase de prospección comercial del itinerario PIIL.
 *
 * @ContentEntityType(
 *   id = "negocio_prospectado_ei",
 *   label = @Translation("Negocio Prospectado"),
 *   label_collection = @Translation("Negocios Prospectados"),
 *   label_singular = @Translation("negocio prospectado"),
 *   label_plural = @Translation("negocios prospectados"),
 *   label_count = @PluralTranslation(
 *     singular = "@count negocio prospectado",
 *     plural = "@count negocios prospectados",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ListBuilder\NegocioProspectadoEiListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\NegocioProspectadoEiAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\NegocioProspectadoEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\NegocioProspectadoEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\NegocioProspectadoEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "negocio_prospectado_ei",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "nombre_negocio",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/negocios-prospectados-ei/{negocio_prospectado_ei}",
 *     "add-form" = "/admin/content/negocios-prospectados-ei/add",
 *     "edit-form" = "/admin/content/negocios-prospectados-ei/{negocio_prospectado_ei}/edit",
 *     "delete-form" = "/admin/content/negocios-prospectados-ei/{negocio_prospectado_ei}/delete",
 *     "collection" = "/admin/content/negocios-prospectados-ei",
 *   },
 *   field_ui_base_route = "entity.negocio_prospectado_ei.settings",
 * )
 */
class NegocioProspectadoEi extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Sectores de actividad.
   */
  public const SECTORES = [
    'hosteleria' => 'Hostelería',
    'comercio' => 'Comercio',
    'profesional' => 'Profesional',
    'agro' => 'Agro',
    'salud' => 'Salud',
    'educacion' => 'Educación',
    'turismo' => 'Turismo',
    'servicios' => 'Servicios',
  ];

  /**
   * Clasificación de urgencia.
   */
  public const CLASIFICACION_URGENCIA = [
    'rojo' => 'Urgente',
    'amarillo' => 'Moderado',
    'verde' => 'Bajo',
  ];

  /**
   * Estados del embudo comercial.
   */
  public const ESTADOS_EMBUDO = [
    'identificado' => 'Identificado',
    'contactado' => 'Contactado',
    'interesado' => 'Interesado',
    'propuesta' => 'Propuesta',
    'acuerdo' => 'Acuerdo',
    'conversion' => 'Conversión',
  ];

  /**
   * Niveles de satisfacción de prueba.
   */
  public const SATISFACCION_PRUEBA = [
    'muy_satisfecho' => 'Muy satisfecho',
    'satisfecho' => 'Satisfecho',
    'neutro' => 'Neutro',
    'insatisfecho' => 'Insatisfecho',
  ];

  /**
   * Provincias disponibles.
   */
  public const PROVINCIAS = [
    'malaga' => 'Málaga',
    'sevilla' => 'Sevilla',
  ];

  /**
   * Obtiene el nombre del negocio.
   */
  public function getNombreNegocio(): string {
    return $this->get('nombre_negocio')->value ?? '';
  }

  /**
   * Obtiene el sector.
   */
  public function getSector(): string {
    return $this->get('sector')->value ?? '';
  }

  /**
   * Obtiene la clasificación de urgencia.
   */
  public function getClasificacionUrgencia(): string {
    return $this->get('clasificacion_urgencia')->value ?? '';
  }

  /**
   * Obtiene el estado del embudo.
   */
  public function getEstadoEmbudo(): string {
    return $this->get('estado_embudo')->value ?? 'identificado';
  }

  /**
   * Establece el estado del embudo.
   */
  public function setEstadoEmbudo(string $estado): self {
    $this->set('estado_embudo', $estado);
    return $this;
  }

  /**
   * Indica si el negocio se ha convertido a pago.
   */
  public function isConvertidoAPago(): bool {
    return (bool) ($this->get('convertido_a_pago')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Owner field (uid) provided by EntityOwnerTrait.
    $fields['uid']
      ->setLabel(t('Prospector'))
      ->setDescription(t('Usuario que registró este negocio prospectado.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 90,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === DATOS DEL NEGOCIO ===

    $fields['nombre_negocio'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Negocio'))
      ->setDescription(t('Nombre comercial del negocio prospectado.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sector'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Sector'))
      ->setDescription(t('Sector de actividad del negocio.'))
      ->setSetting('allowed_values', array_map('t', static::SECTORES))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['direccion'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dirección'))
      ->setDescription(t('Dirección física del negocio.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['provincia'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Provincia'))
      ->setDescription(t('Provincia donde opera el negocio.'))
      ->setSetting('allowed_values', array_map('t', static::PROVINCIAS))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['persona_contacto'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Persona de Contacto'))
      ->setDescription(t('Nombre de la persona de contacto en el negocio.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['telefono'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Teléfono'))
      ->setDescription(t('Número de teléfono del negocio.'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email'))
      ->setDescription(t('Dirección de correo electrónico del negocio.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['url_web'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL Web'))
      ->setDescription(t('Sitio web del negocio.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === EVALUACIÓN ===

    $fields['url_google_maps'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL Google Maps'))
      ->setDescription(t('Enlace a la ficha del negocio en Google Maps.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['valoracion_google'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valoración Google'))
      ->setDescription(t('Puntuación media en Google (0.0-5.0).'))
      ->setSetting('precision', 2)
      ->setSetting('scale', 1)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['num_resenas'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Número de Reseñas'))
      ->setDescription(t('Cantidad de reseñas en Google.'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['clasificacion_urgencia'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Clasificación de Urgencia'))
      ->setDescription(t('Nivel de urgencia para la prospección.'))
      ->setSetting('allowed_values', array_map('t', static::CLASIFICACION_URGENCIA))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['estado_embudo'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado del Embudo'))
      ->setDescription(t('Etapa actual en el embudo de prospección.'))
      ->setSetting('allowed_values', array_map('t', static::ESTADOS_EMBUDO))
      ->setDefaultValue('identificado')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['pack_compatible'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Pack Compatible'))
      ->setDescription(t('Packs de servicio compatibles (JSON).'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === PROGRAMA ===

    // ENTITY-FK-001: entity_reference para entidad del mismo módulo.
    $fields['participante_asignado'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participante Asignado'))
      ->setDescription(t('Participante del programa asignado a este negocio.'))
      ->setSetting('target_type', 'programa_participante_ei')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_primer_contacto'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Primer Contacto'))
      ->setDescription(t('Fecha del primer contacto con el negocio.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_acuerdo_prueba'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Acuerdo Prueba'))
      ->setDescription(t('Fecha en que se acordó el periodo de prueba.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['satisfaccion_prueba'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Satisfacción Prueba'))
      ->setDescription(t('Nivel de satisfacción tras el periodo de prueba.'))
      ->setSetting('allowed_values', array_map('t', static::SATISFACCION_PRUEBA))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['convertido_a_pago'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Convertido a Pago'))
      ->setDescription(t('Indica si el negocio se ha convertido a cliente de pago.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notas'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Observaciones y notas sobre la prospección.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['prospectado_por'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Prospectado Por'))
      ->setDescription(t('Usuario que realizó la prospección.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // ENTITY-FK-001: tenant_id SIEMPRE entity_reference.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este negocio prospectado.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === CAMPOS DE SISTEMA ===

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
