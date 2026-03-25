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
 * Define la entidad InsercionLaboral.
 *
 * Registra el detalle completo de cada insercion laboral lograda
 * por un participante, diferenciando por tipo: cuenta ajena,
 * cuenta propia y agrario.
 *
 * @ContentEntityType(
 *   id = "insercion_laboral",
 *   label = @Translation("Inserción Laboral"),
 *   label_collection = @Translation("Inserciones Laborales"),
 *   label_singular = @Translation("inserción laboral"),
 *   label_plural = @Translation("inserciones laborales"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\InsercionLaboralListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\InsercionLaboralForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\InsercionLaboralForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\InsercionLaboralForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\InsercionLaboralAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "insercion_laboral",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "empresa_nombre",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/inserciones-laborales/{insercion_laboral}",
 *     "add-form" = "/admin/content/inserciones-laborales/add",
 *     "edit-form" = "/admin/content/inserciones-laborales/{insercion_laboral}/edit",
 *     "delete-form" = "/admin/content/inserciones-laborales/{insercion_laboral}/delete",
 *     "collection" = "/admin/content/inserciones-laborales",
 *   },
 *   field_ui_base_route = "entity.insercion_laboral.settings",
 * )
 */
class InsercionLaboral extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Tipos de inserción válidos.
   */
  public const TIPOS_INSERCION = [
    'cuenta_ajena' => 'Cuenta ajena',
    'cuenta_propia' => 'Cuenta propia',
    'agrario' => 'Agrario',
  ];

  /**
   * Tipos de contrato válidos (cuenta ajena).
   */
  public const TIPOS_CONTRATO = [
    'indefinido' => 'Indefinido',
    'temporal' => 'Temporal',
    'practicas' => 'Prácticas',
    'obra_servicio' => 'Obra y servicio',
  ];

  /**
   * Tipos de jornada válidos.
   */
  public const TIPOS_JORNADA = [
    'completa' => 'Completa',
    'parcial' => 'Parcial',
  ];

  /**
   * Modelos fiscales válidos (cuenta propia).
   */
  public const MODELOS_FISCALES = [
    '036' => 'Modelo 036',
    '037' => 'Modelo 037',
  ];

  /**
   * Obtiene el tipo de inserción.
   */
  public function getTipoInsercion(): string {
    return $this->get('tipo_insercion')->value ?? '';
  }

  /**
   * Indica si es cuenta ajena.
   */
  public function isCuentaAjena(): bool {
    return $this->getTipoInsercion() === 'cuenta_ajena';
  }

  /**
   * Indica si es cuenta propia.
   */
  public function isCuentaPropia(): bool {
    return $this->getTipoInsercion() === 'cuenta_propia';
  }

  /**
   * Indica si es agrario.
   */
  public function isAgrario(): bool {
    return $this->getTipoInsercion() === 'agrario';
  }

  /**
   * Indica si está verificado documentalmente.
   */
  public function isVerificado(): bool {
    return (bool) ($this->get('verificado')->value ?? FALSE);
  }

  /**
   * Obtiene la fecha de alta SS/RETA.
   */
  public function getFechaAlta(): ?string {
    return $this->get('fecha_alta')->value;
  }

  /**
   * Obtiene el nombre de empresa (cuenta ajena o agraria).
   */
  public function getEmpresaNombre(): string {
    if ($this->isAgrario()) {
      return $this->get('empresa_agraria')->value ?? '';
    }
    return $this->get('empresa_nombre')->value ?? '';
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
      ->setDescription(t('Usuario que registra la inserción.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // TENANT-001: Toda query DEBE filtrar por tenant.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece esta inserción laboral.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === DATOS PRINCIPALES ===
    $fields['participante_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participante'))
      ->setDescription(t('Participante que logra la inserción laboral.'))
      ->setSetting('target_type', 'programa_participante_ei')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo_insercion'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Inserción'))
      ->setDescription(t('Modalidad de la inserción laboral.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', array_map('t', self::TIPOS_INSERCION))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_alta'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Alta'))
      ->setDescription(t('Fecha de alta en Seguridad Social o RETA.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['verificado'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Verificado'))
      ->setDescription(t('Indica si la inserción está verificada documentalmente.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === CUENTA AJENA ===
    $fields['empresa_nombre'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Empresa'))
      ->setDescription(t('Razón social de la empresa contratante.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['empresa_cif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CIF Empresa'))
      ->setDescription(t('CIF de la empresa contratante.'))
      ->setSetting('max_length', 12)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo_contrato'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Contrato'))
      ->setDescription(t('Modalidad contractual.'))
      ->setSetting('allowed_values', array_map('t', self::TIPOS_CONTRATO))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['jornada'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Jornada'))
      ->setDescription(t('Tipo de jornada laboral.'))
      ->setSetting('allowed_values', array_map('t', self::TIPOS_JORNADA))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['horas_semanales'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Horas Semanales'))
      ->setDescription(t('Número de horas semanales de la jornada.'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Sprint 15: Fechas de contrato — requeridas por ActuacionComputeService
    // para validar ≥4 meses (PIIL BBRR criterio persona insertada).
    $fields['fecha_inicio_contrato'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Inicio Contrato'))
      ->setDescription(t('Fecha de inicio del contrato laboral.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 4.1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_fin_contrato'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Fin Contrato'))
      ->setDescription(t('Fecha de fin del contrato laboral (vacío si indefinido).'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 4.2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['codigo_cuenta_cotizacion'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código Cuenta Cotización'))
      ->setDescription(t('CCC de la empresa.'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sector_actividad'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sector de Actividad'))
      ->setDescription(t('Sector de actividad económica de la empresa.'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === CUENTA PROPIA ===
    $fields['fecha_alta_reta'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Alta RETA'))
      ->setDescription(t('Fecha de alta en el Régimen Especial de Trabajadores Autónomos.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cnae_actividad'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CNAE Actividad'))
      ->setDescription(t('Código CNAE de la actividad económica.'))
      ->setSetting('max_length', 10)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sector_emprendimiento'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sector de Emprendimiento'))
      ->setDescription(t('Sector del emprendimiento autónomo.'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['modelo_fiscal'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modelo Fiscal'))
      ->setDescription(t('Modelo de declaración censal presentado.'))
      ->setSetting('allowed_values', array_map('t', self::MODELOS_FISCALES))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === AGRARIO ===
    $fields['empresa_agraria'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Empresa Agraria'))
      ->setDescription(t('Nombre de la empresa o explotación agraria.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo_cultivo'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de Cultivo'))
      ->setDescription(t('Tipo de cultivo o actividad agraria.'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_inicio_campana'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Inicio Campaña'))
      ->setDescription(t('Fecha de inicio de la campaña agraria.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 22,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_fin_campana'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha Fin Campaña'))
      ->setDescription(t('Fecha de fin de la campaña agraria.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 23,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === DOCUMENTACIÓN ===
    $fields['documento_acreditativo_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Documento Acreditativo'))
      ->setDescription(t('Documento del expediente que acredita la inserción.'))
      ->setSetting('target_type', 'expediente_documento')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notas'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Observaciones adicionales sobre la inserción.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 31,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === SISTEMA ===
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
