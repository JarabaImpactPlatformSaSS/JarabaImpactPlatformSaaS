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
 * Define la entidad PlanEmprendimientoEi.
 *
 * Representa el plan de negocio de un participante que elige la vía de
 * emprendimiento dentro del itinerario PIIL Andalucía +ei.
 *
 * Sprint 7 — Plan Maestro Andalucía +ei Clase Mundial.
 *
 * @ContentEntityType(
 *   id = "plan_emprendimiento_ei",
 *   label = @Translation("Plan de Emprendimiento +ei"),
 *   label_collection = @Translation("Planes de Emprendimiento"),
 *   label_singular = @Translation("plan de emprendimiento"),
 *   label_plural = @Translation("planes de emprendimiento"),
 *   label_count = @PluralTranslation(
 *     singular = "@count plan de emprendimiento",
 *     plural = "@count planes de emprendimiento",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\PlanEmprendimientoEiListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\PlanEmprendimientoEiAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\PlanEmprendimientoEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\PlanEmprendimientoEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\PlanEmprendimientoEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "plan_emprendimiento_ei",
 *   admin_permission = "administer andalucia ei",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/plan-emprendimiento-ei/{plan_emprendimiento_ei}",
 *     "add-form" = "/admin/content/plan-emprendimiento-ei/add",
 *     "edit-form" = "/admin/content/plan-emprendimiento-ei/{plan_emprendimiento_ei}/edit",
 *     "delete-form" = "/admin/content/plan-emprendimiento-ei/{plan_emprendimiento_ei}/delete",
 *     "collection" = "/admin/content/plan-emprendimiento-ei",
 *   },
 *   field_ui_base_route = "entity.plan_emprendimiento_ei.settings",
 * )
 */
class PlanEmprendimientoEi extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityOwnerTrait;
  use EntityChangedTrait;

  /**
   * Fases de emprendimiento paralelas a PIIL.
   */
  public const FASE_IDEACION = 'ideacion';
  public const FASE_VALIDACION = 'validacion';
  public const FASE_LANZAMIENTO = 'lanzamiento';
  public const FASE_CONSOLIDACION = 'consolidacion';

  /**
   * Formas jurídicas objetivo.
   */
  public const FORMAS_JURIDICAS = [
    'autonomo' => 'Autónomo/a',
    'sl' => 'Sociedad Limitada',
    'coop_trabajo' => 'Cooperativa de Trabajo',
    'coop_social' => 'Cooperativa de Iniciativa Social',
    'comunidad_bienes' => 'Comunidad de Bienes',
  ];

  /**
   * Diagnósticos de viabilidad.
   */
  public const DIAGNOSTICOS_VIABILIDAD = [
    'viable' => 'Viable',
    'viable_con_condiciones' => 'Viable con condiciones',
    'no_viable' => 'No viable',
    'pendiente' => 'Pendiente de diagnóstico',
  ];

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Owner (ENTITY-001).
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields['uid']
      ->setLabel(t('Author'))
      ->setDescription(t('The user who created this plan.'))
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner');

    // Label.
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del plan'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Participante referencia (ENTITY-FK-001: misma entidad del módulo).
    $fields['participante_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participante'))
      ->setDescription(t('Participante propietario del plan de emprendimiento.'))
      ->setSetting('target_type', 'programa_participante_ei')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tenant (ENTITY-FK-001).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant al que pertenece este plan.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    // Idea de negocio.
    $fields['idea_negocio'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Idea de negocio'))
      ->setDescription(t('Descripción de la idea de negocio del participante.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Sector CNAE.
    $fields['sector'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sector'))
      ->setDescription(t('Sector CNAE de la actividad.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Forma jurídica objetivo.
    $fields['forma_juridica_objetivo'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Forma jurídica objetivo'))
      ->setSetting('allowed_values', self::FORMAS_JURIDICAS)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Fase de emprendimiento.
    $fields['fase_emprendimiento'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Fase de emprendimiento'))
      ->setSetting('allowed_values', [
        self::FASE_IDEACION => 'Ideación',
        self::FASE_VALIDACION => 'Validación',
        self::FASE_LANZAMIENTO => 'Lanzamiento',
        self::FASE_CONSOLIDACION => 'Consolidación',
      ])
      ->setDefaultValue(self::FASE_IDEACION)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Referencias cross-módulo a jaraba_business_tools (integer — ENTITY-FK-001).
    $fields['canvas_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Business Model Canvas ID'))
      ->setDescription(t('ID del BMC vinculado en jaraba_business_tools.'))
      ->setDisplayConfigurable('form', TRUE);

    $fields['mvp_hypothesis_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('MVP Hypothesis ID'))
      ->setDescription(t('ID de la hipótesis MVP vinculada.'))
      ->setDisplayConfigurable('form', TRUE);

    $fields['projection_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Financial Projection ID'))
      ->setDescription(t('ID de la proyección financiera vinculada.'))
      ->setDisplayConfigurable('form', TRUE);

    // Fechas de alta.
    $fields['fecha_alta_reta'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha alta RETA'))
      ->setDescription(t('Fecha de alta en RETA (si aplica).'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_alta_iae'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha alta IAE'))
      ->setDescription(t('Fecha de alta en IAE (si aplica).'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Financiación.
    $fields['inversion_inicial'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Inversión inicial'))
      ->setDescription(t('Inversión inicial estimada en euros.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fuentes_financiacion'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Fuentes de financiación'))
      ->setDescription(t('JSON: [{tipo, importe, estado}]'))
      ->setDisplayConfigurable('form', TRUE);

    $fields['necesita_microcredito'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Necesita microcrédito'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Resultados.
    $fields['primer_cliente_fecha'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha primer cliente'))
      ->setDescription(t('Fecha del primer cliente/ingreso.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['facturacion_acumulada'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Facturación acumulada'))
      ->setDescription(t('Facturación acumulada desde alta en euros.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['empleo_generado'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Empleo generado'))
      ->setDescription(t('Número de empleos generados (incluido el propio).'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Mentor de emprendimiento.
    $fields['mentor_emprendimiento_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Mentor de emprendimiento'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Diagnóstico de viabilidad.
    $fields['diagnostico_viabilidad'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Diagnóstico de viabilidad'))
      ->setSetting('allowed_values', self::DIAGNOSTICOS_VIABILIDAD)
      ->setDefaultValue('pendiente')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Notas.
    $fields['notas'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Notas del orientador sobre el plan.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 40,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Status.
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 90,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Gets the entrepreneurship phase.
   */
  public function getFaseEmprendimiento(): string {
    return $this->get('fase_emprendimiento')->value ?? self::FASE_IDEACION;
  }

  /**
   * Gets the viability diagnosis.
   */
  public function getDiagnosticoViabilidad(): string {
    return $this->get('diagnostico_viabilidad')->value ?? 'pendiente';
  }

  /**
   * Gets the participant ID.
   */
  public function getParticipanteId(): ?int {
    $val = $this->get('participante_id')->target_id;
    return $val !== NULL ? (int) $val : NULL;
  }

  /**
   * Whether the plan has achieved launch phase (RETA or IAE alta).
   */
  public function isLanzado(): bool {
    return !empty($this->get('fecha_alta_reta')->value)
      || !empty($this->get('fecha_alta_iae')->value);
  }

  /**
   * Whether the plan has a first client/revenue.
   */
  public function tienePrimerCliente(): bool {
    return !empty($this->get('primer_cliente_fecha')->value);
  }

}
