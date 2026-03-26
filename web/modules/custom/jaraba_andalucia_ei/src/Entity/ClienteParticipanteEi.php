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
 * Define la entidad ClienteParticipanteEi.
 *
 * Registra los clientes captados por participantes del programa Andalucía +ei
 * durante la fase de comercialización de los packs de servicios digitales.
 *
 * @ContentEntityType(
 *   id = "cliente_participante_ei",
 *   label = @Translation("Cliente del Participante"),
 *   label_collection = @Translation("Clientes del Participante"),
 *   label_singular = @Translation("cliente del participante"),
 *   label_plural = @Translation("clientes del participante"),
 *   label_count = @PluralTranslation(
 *     singular = "@count cliente del participante",
 *     plural = "@count clientes del participante",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ListBuilder\ClienteParticipanteEiListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\ClienteParticipanteEiAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\ClienteParticipanteEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\ClienteParticipanteEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\ClienteParticipanteEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "cliente_participante_ei",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "nombre_negocio",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/clientes-participante-ei/{cliente_participante_ei}",
 *     "add-form" = "/admin/content/clientes-participante-ei/add",
 *     "edit-form" = "/admin/content/clientes-participante-ei/{cliente_participante_ei}/edit",
 *     "delete-form" = "/admin/content/clientes-participante-ei/{cliente_participante_ei}/delete",
 *     "collection" = "/admin/content/clientes-participante-ei",
 *   },
 *   field_ui_base_route = "entity.cliente_participante_ei.settings",
 * )
 */
class ClienteParticipanteEi extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Sectores de actividad del cliente.
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
   * Packs de servicios digitales contratables.
   */
  public const PACKS = [
    'contenido_digital' => 'Contenido Digital',
    'asistente_virtual' => 'Asistente Virtual',
    'presencia_online' => 'Presencia Online',
    'tienda_digital' => 'Tienda Digital',
    'community_manager' => 'Community Manager',
  ];

  /**
   * Modalidades de contratación.
   */
  public const MODALIDADES = [
    'basico' => 'Básico',
    'estandar' => 'Estándar',
    'premium' => 'Premium',
  ];

  /**
   * Estados de la relación comercial.
   */
  public const ESTADOS = [
    'prospecto' => 'Prospecto',
    'piloto' => 'Piloto',
    'activo' => 'Activo',
    'pausado' => 'Pausado',
    'baja' => 'Baja',
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
   * Obtiene el pack contratado.
   */
  public function getPackContratado(): string {
    return $this->get('pack_contratado')->value ?? '';
  }

  /**
   * Obtiene la modalidad.
   */
  public function getModalidad(): string {
    return $this->get('modalidad')->value ?? '';
  }

  /**
   * Obtiene el estado de la relación.
   */
  public function getEstado(): string {
    return $this->get('estado')->value ?? 'prospecto';
  }

  /**
   * Establece el estado de la relación.
   */
  public function setEstado(string $estado): self {
    $this->set('estado', $estado);
    return $this;
  }

  /**
   * Indica si el cliente está en periodo piloto.
   */
  public function isPiloto(): bool {
    return (bool) ($this->get('es_piloto')->value ?? FALSE);
  }

  /**
   * Obtiene el precio mensual.
   */
  public function getPrecioMensual(): string {
    return $this->get('precio_mensual')->value ?? '0.00';
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Owner field (uid) provided by EntityOwnerTrait.
    $fields['uid']
      ->setLabel(t('Registrado por'))
      ->setDescription(t('Usuario que registró este cliente.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 90,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === DATOS DEL NEGOCIO ===

    // ENTITY-FK-001: entity_reference para entidad del mismo módulo.
    $fields['participante_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participante'))
      ->setDescription(t('Participante del programa que captó este cliente.'))
      ->setSetting('target_type', 'programa_participante_ei')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nombre_negocio'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Negocio'))
      ->setDescription(t('Nombre comercial del negocio cliente.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nombre_contacto'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Contacto'))
      ->setDescription(t('Nombre de la persona de contacto en el negocio.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('Dirección de correo electrónico del cliente.'))
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['telefono'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Teléfono'))
      ->setDescription(t('Número de teléfono del cliente.'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sector'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Sector'))
      ->setDescription(t('Sector de actividad del negocio cliente.'))
      ->setSetting('allowed_values', array_map('t', static::SECTORES))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === PACK Y SERVICIO ===
    $fields['pack_contratado'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Pack Contratado'))
      ->setDescription(t('Pack de servicios digitales contratado por el cliente.'))
      ->setSetting('allowed_values', array_map('t', static::PACKS))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['modalidad'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modalidad'))
      ->setDescription(t('Modalidad de contratación del pack.'))
      ->setSetting('allowed_values', array_map('t', static::MODALIDADES))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['precio_mensual'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio Mensual'))
      ->setDescription(t('Precio mensual acordado en euros.'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === ESTADO Y RELACIÓN ===
    $fields['estado'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la relación comercial con el cliente.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', array_map('t', static::ESTADOS))
      ->setDefaultValue('prospecto')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_inicio'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Inicio'))
      ->setDescription(t('Fecha de inicio de la relación comercial.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['es_piloto'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Es Piloto'))
      ->setDescription(t('Indica si el cliente está en periodo de prueba piloto.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === NOTAS ===
    $fields['notas'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Observaciones y notas sobre el cliente.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['feedback_piloto'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Feedback del Piloto'))
      ->setDescription(t('Feedback recogido durante el periodo de prueba piloto.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // ENTITY-FK-001: tenant_id SIEMPRE entity_reference.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este cliente.'))
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
