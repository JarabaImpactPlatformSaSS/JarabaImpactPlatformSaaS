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
 * Define la entidad IndicadorFsePlus.
 *
 * Registra indicadores obligatorios FSE+ (Fondo Social Europeo Plus)
 * recogidos en 3 momentos: entrada, salida, seguimiento a 6 meses.
 *
 * @ContentEntityType(
 *   id = "indicador_fse_plus",
 *   label = @Translation("Indicador FSE+"),
 *   label_collection = @Translation("Indicadores FSE+"),
 *   label_singular = @Translation("indicador FSE+"),
 *   label_plural = @Translation("indicadores FSE+"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\IndicadorFsePlusListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\IndicadorFsePlusForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\IndicadorFsePlusForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\IndicadorFsePlusForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\IndicadorFsePlusAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "indicador_fse_plus",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "momento_recogida",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/indicadores-fse-plus/{indicador_fse_plus}",
 *     "add-form" = "/admin/content/indicadores-fse-plus/add",
 *     "edit-form" = "/admin/content/indicadores-fse-plus/{indicador_fse_plus}/edit",
 *     "delete-form" = "/admin/content/indicadores-fse-plus/{indicador_fse_plus}/delete",
 *     "collection" = "/admin/content/indicadores-fse-plus",
 *   },
 *   field_ui_base_route = "entity.indicador_fse_plus.settings",
 * )
 */
class IndicadorFsePlus extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Momentos de recogida de indicadores FSE+.
   */
  public const MOMENTOS_RECOGIDA = [
    'entrada' => 'Entrada',
    'salida' => 'Salida',
    'seguimiento_6m' => 'Seguimiento 6 meses',
  ];

  /**
   * Situaciones laborales FSE+.
   */
  public const SITUACIONES_LABORALES = [
    'desempleado_larga' => 'Desempleado/a larga duración (>12 meses)',
    'desempleado_corta' => 'Desempleado/a corta duración (<12 meses)',
    'inactivo' => 'Inactivo/a',
    'ocupado_cuenta_ajena' => 'Ocupado/a por cuenta ajena',
    'ocupado_cuenta_propia' => 'Ocupado/a por cuenta propia',
  ];

  /**
   * Niveles educativos ISCED.
   */
  public const NIVELES_ISCED = [
    'isced_0' => 'ISCED 0 — Educación de la primera infancia',
    'isced_1' => 'ISCED 1 — Educación primaria',
    'isced_2' => 'ISCED 2 — Educación secundaria baja',
    'isced_3' => 'ISCED 3 — Educación secundaria alta',
    'isced_4' => 'ISCED 4 — Educación postsecundaria no terciaria',
    'isced_5' => 'ISCED 5 — Educación terciaria ciclo corto',
    'isced_6' => 'ISCED 6 — Grado o equivalente',
    'isced_7' => 'ISCED 7 — Máster o equivalente',
    'isced_8' => 'ISCED 8 — Doctorado o equivalente',
  ];

  /**
   * Tipos de discapacidad.
   */
  public const TIPOS_DISCAPACIDAD = [
    'fisica' => 'Física',
    'sensorial' => 'Sensorial',
    'intelectual' => 'Intelectual',
    'mental' => 'Mental',
    'multiple' => 'Múltiple',
  ];

  /**
   * Zonas de residencia.
   */
  public const ZONAS_RESIDENCIA = [
    'urbana' => 'Urbana',
    'rural' => 'Rural',
    'intermedia' => 'Intermedia',
  ];

  /**
   * Tipos de contrato resultado.
   */
  public const TIPOS_CONTRATO = [
    'indefinido' => 'Indefinido',
    'temporal' => 'Temporal',
    'sin_contrato' => 'Sin contrato',
  ];

  /**
   * Tipos de cualificación.
   */
  public const TIPOS_CUALIFICACION = [
    'certificado_profesionalidad' => 'Certificado de Profesionalidad',
    'titulo_fp' => 'Título de Formación Profesional',
    'titulo_universitario' => 'Título Universitario',
    'certificado_competencias' => 'Certificado de Competencias',
    'otro' => 'Otro',
  ];

  /**
   * Obtiene el momento de recogida.
   */
  public function getMomentoRecogida(): string {
    return $this->get('momento_recogida')->value ?? '';
  }

  /**
   * Obtiene la etiqueta legible del momento de recogida.
   *
   * LABEL-NULLSAFE-001: entity_keys label = momento_recogida (list_string).
   */
  public function getMomentoRecogidaLabel(): string {
    $momento = $this->getMomentoRecogida();
    return self::MOMENTOS_RECOGIDA[$momento] ?? $momento;
  }

  /**
   * Indica si los indicadores de entrada están completados.
   */
  public function isEntrada(): bool {
    return $this->getMomentoRecogida() === 'entrada';
  }

  /**
   * Indica si los indicadores de salida están completados.
   */
  public function isSalida(): bool {
    return $this->getMomentoRecogida() === 'salida';
  }

  /**
   * Indica si es el seguimiento a 6 meses.
   */
  public function isSeguimiento6m(): bool {
    return $this->getMomentoRecogida() === 'seguimiento_6m';
  }

  /**
   * Indica si es un momento de resultado (salida o seguimiento).
   */
  public function isMomentoResultado(): bool {
    return in_array($this->getMomentoRecogida(), ['salida', 'seguimiento_6m'], TRUE);
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
      ->setDescription(t('Usuario que registra los indicadores.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este indicador.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === DATOS DE RECOGIDA ===
    $fields['participante_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participante'))
      ->setDescription(t('Participante al que corresponden los indicadores FSE+.'))
      ->setSetting('target_type', 'programa_participante_ei')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['momento_recogida'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Momento de Recogida'))
      ->setDescription(t('Momento en que se recogen los indicadores FSE+.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', array_map('t', self::MOMENTOS_RECOGIDA))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_recogida'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Recogida'))
      ->setDescription(t('Fecha en que se registran los indicadores.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === INDICADORES DE ENTRADA (sociodemográficos) ===
    $fields['situacion_laboral'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Situación Laboral'))
      ->setDescription(t('Situación laboral al momento de la recogida.'))
      ->setSetting('allowed_values', array_map('t', self::SITUACIONES_LABORALES))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nivel_educativo_isced'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Nivel Educativo (ISCED)'))
      ->setDescription(t('Nivel educativo según clasificación ISCED.'))
      ->setSetting('allowed_values', array_map('t', self::NIVELES_ISCED))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discapacidad'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Discapacidad'))
      ->setDescription(t('Indica si la persona tiene discapacidad reconocida.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discapacidad_tipo'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Discapacidad'))
      ->setDescription(t('Tipo de discapacidad reconocida.'))
      ->setSetting('allowed_values', array_map('t', self::TIPOS_DISCAPACIDAD))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discapacidad_grado'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Grado de Discapacidad (%)'))
      ->setDescription(t('Porcentaje de discapacidad reconocido (0-100).'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['pais_origen'] = BaseFieldDefinition::create('string')
      ->setLabel(t('País de Origen'))
      ->setDescription(t('Código ISO 3166-1 alpha-3 del país de origen.'))
      ->setSetting('max_length', 3)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nacionalidad'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nacionalidad'))
      ->setDescription(t('Código ISO 3166-1 alpha-3 de la nacionalidad.'))
      ->setSetting('max_length', 3)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hogar_unipersonal'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Hogar Unipersonal'))
      ->setDescription(t('Indica si la persona vive sola.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hijos_a_cargo'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Hijos/as a Cargo'))
      ->setDescription(t('Número de hijos/as a cargo.'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['zona_residencia'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Zona de Residencia'))
      ->setDescription(t('Tipo de zona donde reside la persona.'))
      ->setSetting('allowed_values', array_map('t', self::ZONAS_RESIDENCIA))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['situacion_sin_hogar'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Situación Sin Hogar'))
      ->setDescription(t('Indica si la persona se encuentra en situación de sinhogarismo.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['comunidad_marginada'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Comunidad Marginada'))
      ->setDescription(t('Indica si la persona pertenece a una comunidad marginada.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === INDICADORES DE RESULTADO (salida / seguimiento 6 meses) ===
    $fields['situacion_laboral_resultado'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Situación Laboral (Resultado)'))
      ->setDescription(t('Situación laboral al momento de salida o seguimiento.'))
      ->setSetting('allowed_values', array_map('t', self::SITUACIONES_LABORALES))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo_contrato_resultado'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Contrato (Resultado)'))
      ->setDescription(t('Tipo de contrato obtenido al finalizar la intervención.'))
      ->setSetting('allowed_values', array_map('t', self::TIPOS_CONTRATO))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cualificacion_obtenida'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Cualificación Obtenida'))
      ->setDescription(t('Indica si se ha obtenido una cualificación durante la intervención.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 22,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo_cualificacion'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Cualificación'))
      ->setDescription(t('Tipo de cualificación obtenida.'))
      ->setSetting('allowed_values', array_map('t', self::TIPOS_CUALIFICACION))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 23,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mejora_situacion'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mejora de Situación'))
      ->setDescription(t('Indica si se ha producido una mejora en la situación de la persona.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 24,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['inclusion_social'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Inclusión Social'))
      ->setDescription(t('Indica si se ha logrado la inclusión social de la persona.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === SISTEMA ===
    $fields['completado'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Completado'))
      ->setDescription(t('Indica si la ficha de indicadores está completa.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notas'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Observaciones o notas adicionales sobre los indicadores.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 31,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
