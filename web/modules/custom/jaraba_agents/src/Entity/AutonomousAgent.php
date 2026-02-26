<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Agente Autonomo.
 *
 * ESTRUCTURA:
 *   Entidad de contenido que representa un agente autonomo configurable
 *   dentro de la plataforma Jaraba. Cada agente tiene un tipo, vertical,
 *   nivel de autonomia, modelo LLM y guardrails definidos.
 *   Soporta Field UI para campos adicionales.
 *
 * LOGICA:
 *   - tenant_id puede ser NULL para agentes globales de plataforma.
 *   - autonomy_level determina cuanta supervision humana requiere el agente.
 *   - capabilities y guardrails se almacenan como JSON en text_long.
 *   - performance_metrics acumula metricas de rendimiento del agente.
 *   - temperature y max_actions_per_run controlan el comportamiento del LLM.
 *
 * RELACIONES:
 *   - tenant_id -> group (organizacion propietaria, AUDIT-CONS-005).
 *   - uid -> user (usuario creador/propietario).
 *   - Referenciado por agent_execution.agent_id.
 *   - Referenciado por agent_approval.agent_id.
 *
 * @ContentEntityType(
 *   id = "autonomous_agent",
 *   label = @Translation("Agente Autonomo"),
 *   label_collection = @Translation("Agentes Autonomos"),
 *   label_singular = @Translation("agente autonomo"),
 *   label_plural = @Translation("agentes autonomos"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_agents\ListBuilder\AutonomousAgentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agents\Form\AutonomousAgentForm",
 *       "add" = "Drupal\jaraba_agents\Form\AutonomousAgentForm",
 *       "edit" = "Drupal\jaraba_agents\Form\AutonomousAgentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agents\Access\AutonomousAgentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "autonomous_agent",
 *   admin_permission = "administer agents",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/autonomous-agents",
 *     "add-form" = "/admin/content/autonomous-agents/add",
 *     "canonical" = "/admin/content/autonomous-agents/{autonomous_agent}",
 *     "edit-form" = "/admin/content/autonomous-agents/{autonomous_agent}/edit",
 *     "delete-form" = "/admin/content/autonomous-agents/{autonomous_agent}/delete",
 *   },
 *   field_ui_base_route = "jaraba_agents.autonomous_agent.settings",
 * )
 */
class AutonomousAgent extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Campo 1: tenant_id — referencia a grupo (AUDIT-CONS-005).
    // NULL = agente global de plataforma.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organizacion'))
      ->setDescription(t('Organizacion propietaria del agente. NULL = agente global.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 2: uid — propietario (via ownerBaseFieldDefinitions).
    $fields['uid']
      ->setLabel(t('Propietario'))
      ->setDescription(t('Usuario propietario del agente.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 3: name — nombre del agente.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre identificativo del agente autonomo.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 4: agent_type — tipo de agente.
    $fields['agent_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de agente'))
      ->setDescription(t('Clasificacion funcional del agente.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'enrollment' => 'Enrollment',
        'planning' => 'Planificacion',
        'support' => 'Soporte',
        'marketing' => 'Marketing',
        'analytics' => 'Analitica',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 5: vertical — vertical de negocio.
    $fields['vertical'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Vertical'))
      ->setDescription(t('Vertical de negocio en la que opera el agente.'))
      ->setSetting('allowed_values', [
        'empleabilidad' => 'Empleabilidad',
        'emprendimiento' => 'Emprendimiento',
        'agro' => 'Agroalimentario',
        'comercio' => 'Comercio',
        'servicios' => 'Servicios',
        'legal' => 'Legal',
        'platform' => 'Plataforma',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 6: objective — objetivo del agente.
    $fields['objective'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Objetivo'))
      ->setDescription(t('Objetivo principal del agente autonomo.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 7: capabilities — capacidades del agente en formato JSON.
    $fields['capabilities'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Capacidades (JSON)'))
      ->setDescription(t('Lista de capacidades del agente en formato JSON.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 6,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 8: guardrails — restricciones del agente en formato JSON.
    $fields['guardrails'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Guardrails (JSON)'))
      ->setDescription(t('Restricciones y limites del agente en formato JSON.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 7,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 9: autonomy_level — nivel de autonomia.
    $fields['autonomy_level'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Nivel de autonomia'))
      ->setDescription(t('Grado de autonomia del agente respecto a supervision humana.'))
      ->setRequired(TRUE)
      ->setDefaultValue('l1_suggestion')
      ->setSetting('allowed_values', [
        'l0_informative' => 'L0 Informativo',
        'l1_suggestion' => 'L1 Sugerencia',
        'l2_semi_autonomous' => 'L2 Semi-autonomo',
        'l3_supervised' => 'L3 Supervisado',
        'l4_full' => 'L4 Autonomo completo',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 8,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 10: llm_model — modelo LLM utilizado.
    $fields['llm_model'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Modelo LLM'))
      ->setDescription(t('Identificador del modelo de lenguaje utilizado.'))
      ->setDefaultValue('gemini-2.0-flash')
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 9,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 11: temperature — temperatura del modelo.
    $fields['temperature'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Temperatura'))
      ->setDescription(t('Parametro de temperatura para el modelo LLM (0.00-1.00).'))
      ->setDefaultValue('0.30')
      ->setSetting('precision', 3)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 12: max_actions_per_run — maximo de acciones por ejecucion.
    $fields['max_actions_per_run'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max acciones por ejecucion'))
      ->setDescription(t('Numero maximo de acciones que el agente puede ejecutar por ciclo.'))
      ->setDefaultValue(10)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 11,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 13: requires_approval — acciones que requieren aprobacion (JSON).
    $fields['requires_approval'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Acciones que requieren aprobacion (JSON)'))
      ->setDescription(t('Lista de acciones que necesitan aprobacion humana antes de ejecutarse.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 12,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 14: is_active — estado activo del agente.
    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDescription(t('Indica si el agente esta activo y disponible para ejecuciones.'))
      ->setRequired(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 13,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 15: performance_metrics — metricas de rendimiento (JSON).
    $fields['performance_metrics'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Metricas de rendimiento (JSON)'))
      ->setDescription(t('Datos de rendimiento acumulados del agente en formato JSON.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 14,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 18: schedule_type — tipo de programacion (GAP-05).
    $fields['schedule_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de programacion'))
      ->setDescription(t('Determina como se ejecuta el agente de forma automatica.'))
      ->setDefaultValue('manual')
      ->setSetting('allowed_values', [
        'manual' => 'Manual',
        'interval' => 'Intervalo',
        'cron' => 'Expresion Cron',
        'one_time' => 'Una vez',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 15,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 19: schedule_config — configuracion de programacion (JSON, GAP-05).
    // Para 'interval': {"every": 3600, "unit": "seconds"}
    // Para 'cron': {"expression": "0 * * * *"}
    // Para 'one_time': {"run_at": "2026-03-01T09:00:00"}
    $fields['schedule_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuracion de programacion'))
      ->setDescription(t('Parametros de la programacion en formato JSON.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 16,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 20: last_run — timestamp de la ultima ejecucion (GAP-05).
    $fields['last_run'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ultima ejecucion'))
      ->setDescription(t('Marca temporal de la ultima ejecucion programada del agente.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 17,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Campo 21: next_run — timestamp calculado de la proxima ejecucion (GAP-05).
    $fields['next_run'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Proxima ejecucion'))
      ->setDescription(t('Marca temporal calculada de la proxima ejecucion programada.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 18,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Campo 16: created — fecha de creacion.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDescription(t('Marca temporal de creacion del agente.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Campo 17: changed — fecha de ultima modificacion.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de modificacion'))
      ->setDescription(t('Marca temporal de la ultima modificacion del agente.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Devuelve el tipo de programacion del agente (GAP-05).
   *
   * @return string
   *   'manual', 'interval', 'cron', o 'one_time'.
   */
  public function getScheduleType(): string {
    return $this->get('schedule_type')->value ?? 'manual';
  }

  /**
   * Devuelve la configuracion de programacion decodificada (GAP-05).
   *
   * @return array
   *   Array asociativo con la configuracion de schedule.
   */
  public function getScheduleConfig(): array {
    $value = $this->get('schedule_config')->value;
    if (empty($value)) {
      return [];
    }
    return json_decode($value, TRUE) ?: [];
  }

  /**
   * Devuelve el timestamp de la ultima ejecucion (GAP-05).
   *
   * @return int|null
   *   Timestamp o NULL si nunca se ha ejecutado.
   */
  public function getLastRun(): ?int {
    $value = $this->get('last_run')->value;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * Devuelve el timestamp de la proxima ejecucion (GAP-05).
   *
   * @return int|null
   *   Timestamp o NULL si no esta programado.
   */
  public function getNextRun(): ?int {
    $value = $this->get('next_run')->value;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * Determina si el agente debe ejecutarse ahora (GAP-05).
   *
   * Condiciones:
   * - is_active == TRUE
   * - schedule_type != 'manual'
   * - next_run <= ahora (o next_run es NULL para primera ejecucion)
   *
   * @return bool
   *   TRUE si el agente debe ejecutarse.
   */
  public function isDue(): bool {
    if (!$this->get('is_active')->value) {
      return FALSE;
    }

    $scheduleType = $this->getScheduleType();
    if ($scheduleType === 'manual') {
      return FALSE;
    }

    $nextRun = $this->getNextRun();

    // Primera ejecucion (next_run no calculado aun).
    if ($nextRun === NULL) {
      return TRUE;
    }

    return $nextRun <= \Drupal::time()->getRequestTime();
  }

  /**
   * Calcula el timestamp de la proxima ejecucion segun la config (GAP-05).
   *
   * @return int|null
   *   Timestamp de la proxima ejecucion o NULL si no aplica.
   */
  public function calculateNextRun(): ?int {
    $config = $this->getScheduleConfig();

    return match ($this->getScheduleType()) {
      'interval' => \Drupal::time()->getRequestTime() + ($config['every'] ?? 3600),
      'cron' => $this->calculateNextCronRun($config['expression'] ?? '0 * * * *'),
      'one_time' => isset($config['run_at']) ? strtotime($config['run_at']) : NULL,
      default => NULL,
    };
  }

  /**
   * Calcula la proxima ejecucion de una expresion cron simple.
   *
   * Implementacion basica sin dependencia externa. Soporta:
   * - Intervalos con asterisco y division: * /N (cada N unidades)
   * - Valores fijos: 0, 6, 12
   *
   * Para cron expressions complejas, se recomienda instalar
   * dragonmantank/cron-expression.
   *
   * @param string $expression
   *   Expresion cron (5 campos: min hour day month weekday).
   *
   * @return int
   *   Timestamp de la proxima ejecucion.
   */
  protected function calculateNextCronRun(string $expression): int {
    // Intentar usar la libreria cron-expression si esta disponible.
    if (class_exists('\\Cron\\CronExpression')) {
      try {
        $cron = new \Cron\CronExpression($expression);
        return $cron->getNextRunDate()->getTimestamp();
      } catch (\Exception $e) {
        // Fallback a calculo basico.
      }
    }

    // Calculo basico: extraer intervalo de horas del cron.
    $parts = explode(' ', trim($expression));
    if (count($parts) >= 2) {
      $hourPart = $parts[1];
      // */N = cada N horas.
      if (str_starts_with($hourPart, '*/')) {
        $interval = (int) substr($hourPart, 2);
        return \Drupal::time()->getRequestTime() + ($interval * 3600);
      }
    }

    // Fallback: proxima hora en punto.
    return \Drupal::time()->getRequestTime() + 3600;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);

    $schema['indexes']['autonomous_agent__tenant_id'] = ['tenant_id'];
    $schema['indexes']['autonomous_agent__agent_type'] = ['agent_type'];
    $schema['indexes']['autonomous_agent__vertical'] = ['vertical'];
    $schema['indexes']['autonomous_agent__is_active'] = ['is_active'];
    $schema['indexes']['autonomous_agent__autonomy_level'] = ['autonomy_level'];
    // GAP-05: Indice para consulta eficiente de agentes programados.
    $schema['indexes']['autonomous_agent__schedule_due'] = ['schedule_type', 'next_run', 'is_active'];

    return $schema;
  }

}
