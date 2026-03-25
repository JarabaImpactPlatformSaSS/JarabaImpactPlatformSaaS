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
 * Define la entidad ProspeccionEmpresarial.
 *
 * Registra actividades de prospección e intermediación con empresas
 * para la inserción laboral de participantes del programa Andalucía +ei.
 * Parte de la fase de Intermediación del itinerario PIIL CV 2025.
 *
 * @ContentEntityType(
 *   id = "prospeccion_empresarial",
 *   label = @Translation("Prospección Empresarial"),
 *   label_collection = @Translation("Prospecciones Empresariales"),
 *   label_singular = @Translation("prospección empresarial"),
 *   label_plural = @Translation("prospecciones empresariales"),
 *   label_count = @PluralTranslation(
 *     singular = "@count prospección empresarial",
 *     plural = "@count prospecciones empresariales",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ProspeccionEmpresarialListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_andalucia_ei\ProspeccionEmpresarialAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\ProspeccionEmpresarialForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\ProspeccionEmpresarialForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\ProspeccionEmpresarialForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "prospeccion_empresarial",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "empresa_nombre",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/prospeccion-empresarial/{prospeccion_empresarial}",
 *     "add-form" = "/admin/content/prospeccion-empresarial/add",
 *     "edit-form" = "/admin/content/prospeccion-empresarial/{prospeccion_empresarial}/edit",
 *     "delete-form" = "/admin/content/prospeccion-empresarial/{prospeccion_empresarial}/delete",
 *     "collection" = "/admin/content/prospeccion-empresarial",
 *   },
 *   field_ui_base_route = "entity.prospeccion_empresarial.settings",
 * )
 */
class ProspeccionEmpresarial extends ContentEntityBase implements ProspeccionEmpresarialInterface, EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Estados de la prospección.
   */
  public const ESTADOS = [
    'lead' => 'Lead (identificada)',
    'contactado' => 'Contactado',
    'interesado' => 'Interesado',
    'colaborador' => 'Colaborador activo',
    'descartado' => 'Descartado',
  ];

  /**
   * Tipos de colaboración posibles.
   */
  public const TIPOS_COLABORACION = [
    'practicas' => 'Prácticas profesionales',
    'contratacion' => 'Contratación directa',
    'formacion_dual' => 'Formación dual',
    'emprendimiento' => 'Apoyo al emprendimiento',
    'mentoria' => 'Mentoría empresarial',
  ];

  /**
   * {@inheritdoc}
   */
  public function getEmpresaNombre(): string {
    return $this->get('empresa_nombre')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCif(): string {
    return $this->get('cif')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSector(): string {
    return $this->get('sector')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getEstado(): string {
    return $this->get('estado')->value ?? 'lead';
  }

  /**
   * {@inheritdoc}
   */
  public function setEstado(string $estado): self {
    $this->set('estado', $estado);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTipoColaboracion(): string {
    return $this->get('tipo_colaboracion')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function isColaboradorActivo(): bool {
    return $this->getEstado() === 'colaborador';
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
      ->setDescription(t('Orientador/prospector que gestiona esta empresa.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === RELACIONES ===
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece esta prospección.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === DATOS DE LA EMPRESA ===
    $fields['empresa_nombre'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de la Empresa'))
      ->setDescription(t('Razón social o nombre comercial.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CIF/NIF'))
      ->setDescription(t('Código de identificación fiscal de la empresa.'))
      ->setSetting('max_length', 12)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sector'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sector de Actividad'))
      ->setDescription(t('Sector económico principal de la empresa.'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tamano_empresa'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tamaño'))
      ->setDescription(t('Tamaño de la empresa por número de empleados.'))
      ->setSetting('allowed_values', [
        'micro' => t('Microempresa (<10)'),
        'pequena' => t('Pequeña (10-49)'),
        'mediana' => t('Mediana (50-249)'),
        'grande' => t('Grande (≥250)'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['provincia'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Provincia'))
      ->setDescription(t('Provincia donde opera la empresa.'))
      ->setSetting('allowed_values', [
        'cadiz' => t('Cádiz'),
        'granada' => t('Granada'),
        'malaga' => t('Málaga'),
        'sevilla' => t('Sevilla'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === CONTACTO ===
    $fields['contacto_nombre'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Persona de Contacto'))
      ->setDescription(t('Nombre de la persona de contacto en la empresa.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['contacto_cargo'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Cargo del Contacto'))
      ->setDescription(t('Puesto o cargo del contacto.'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['contacto_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email de Contacto'))
      ->setDescription(t('Dirección de correo electrónico.'))
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['contacto_telefono'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Teléfono de Contacto'))
      ->setDescription(t('Número de teléfono.'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === ESTADO DE LA PROSPECCIÓN ===
    $fields['estado'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la prospección.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', array_map('t', static::ESTADOS))
      ->setDefaultValue('lead')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo_colaboracion'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Colaboración'))
      ->setDescription(t('Tipo de colaboración ofrecida o acordada.'))
      ->setSetting('allowed_values', array_map('t', static::TIPOS_COLABORACION))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === PUESTOS DISPONIBLES ===
    $fields['puestos_disponibles'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Puestos Disponibles'))
      ->setDescription(t('Número de puestos ofertados o potenciales.'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['perfiles_demandados'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Perfiles Demandados'))
      ->setDescription(t('Descripción de perfiles profesionales que la empresa necesita.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === SEGUIMIENTO ===
    $fields['fecha_primer_contacto'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Primer Contacto'))
      ->setDescription(t('Fecha del primer contacto con la empresa.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_ultimo_seguimiento'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Último Seguimiento'))
      ->setDescription(t('Fecha de la última interacción con la empresa.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notas'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas de Seguimiento'))
      ->setDescription(t('Observaciones y notas de las interacciones.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === PARTICIPANTES VINCULADOS ===
    $fields['participantes_derivados'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Participantes Derivados'))
      ->setDescription(t('Número de participantes derivados a esta empresa.'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['participantes_insertados'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Participantes Insertados'))
      ->setDescription(t('Número de participantes que consiguieron inserción en esta empresa.'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === PUBLICACIÓN ===
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publicado'))
      ->setDescription(t('Estado de publicación de la prospección.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 90,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // === CAMPOS DE SISTEMA ===
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
