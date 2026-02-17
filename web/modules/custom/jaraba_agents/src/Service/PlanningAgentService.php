<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Agente especializado de planificacion empresarial.
 *
 * ESTRUCTURA:
 *   Analiza datos diagnosticos de tenants para generar planes
 *   de negocio estructurados con fases, tareas y asignaciones.
 *   Soporta planes de crecimiento, optimizacion y expansion.
 *
 * LOGICA:
 *   Analiza fortalezas, debilidades y oportunidades del tenant a
 *   partir de datos diagnosticos y metricas existentes. Genera
 *   planes con fases secuenciales y tareas accionables que se
 *   asignan a usuarios del tenant.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class PlanningAgentService {

  /**
   * Tipos de plan soportados por el agente.
   */
  protected const VALID_PLAN_TYPES = ['growth', 'optimization', 'expansion'];

  /**
   * Construye el servicio del agente de planificacion.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_agents.
   * @param object $tenantContext
   *   Servicio de contexto de tenant para filtrado multi-tenant.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly object $tenantContext,
  ) {}

  /**
   * Analiza los datos diagnosticos de un tenant.
   *
   * Evalua la informacion del grupo/tenant para identificar
   * fortalezas, debilidades y oportunidades de mejora.
   *
   * @param int $tenantId
   *   ID del grupo/tenant a analizar (AUDIT-CONS-005: entity_reference a group).
   *
   * @return array
   *   Array con claves:
   *   - 'strengths': array de fortalezas identificadas.
   *   - 'weaknesses': array de debilidades identificadas.
   *   - 'opportunities': array de oportunidades detectadas.
   *   - 'data_quality': float indicando calidad de datos (0.0-1.0).
   */
  public function analyzeDiagnostic(int $tenantId): array {
    try {
      // AUDIT-CONS-005: Cargar entidad group por tenant_id.
      $groupStorage = $this->entityTypeManager->getStorage('group');
      $group = $groupStorage->load($tenantId);

      if (!$group) {
        $this->logger->error('Tenant no encontrado para analisis diagnostico: @id', [
          '@id' => $tenantId,
        ]);
        return [
          'strengths' => [],
          'weaknesses' => [],
          'opportunities' => [],
          'data_quality' => 0.0,
          'error' => (string) new TranslatableMarkup('Tenant con ID @id no encontrado.', ['@id' => $tenantId]),
        ];
      }

      // Obtener datos diagnosticos asociados al tenant.
      $diagnosticData = $this->getTenantDiagnosticData($tenantId);
      $metrics = $this->getTenantMetrics($tenantId);

      $strengths = [];
      $weaknesses = [];
      $opportunities = [];

      // Analizar datos disponibles para clasificar.
      if (!empty($diagnosticData)) {
        foreach ($diagnosticData as $diagnostic) {
          $score = (float) ($diagnostic->get('field_score')->value ?? 0);
          $area = $diagnostic->get('field_area')->value ?? (string) new TranslatableMarkup('General');

          if ($score >= 0.7) {
            $strengths[] = [
              'area' => $area,
              'score' => round($score, 2),
              'description' => (string) new TranslatableMarkup('Alto rendimiento en el area de @area.', ['@area' => $area]),
            ];
          }
          elseif ($score < 0.4) {
            $weaknesses[] = [
              'area' => $area,
              'score' => round($score, 2),
              'description' => (string) new TranslatableMarkup('Area de mejora identificada en @area.', ['@area' => $area]),
            ];
          }
          else {
            $opportunities[] = [
              'area' => $area,
              'score' => round($score, 2),
              'description' => (string) new TranslatableMarkup('Oportunidad de crecimiento en @area.', ['@area' => $area]),
            ];
          }
        }
      }

      // Calcular calidad de datos basandose en cantidad de informacion.
      $dataPoints = count($diagnosticData) + count($metrics);
      $dataQuality = min(1.0, $dataPoints / 10);

      $this->logger->info('Analisis diagnostico completado para tenant @id: @s fortalezas, @w debilidades, @o oportunidades.', [
        '@id' => $tenantId,
        '@s' => count($strengths),
        '@w' => count($weaknesses),
        '@o' => count($opportunities),
      ]);

      return [
        'strengths' => $strengths,
        'weaknesses' => $weaknesses,
        'opportunities' => $opportunities,
        'data_quality' => round($dataQuality, 2),
        // AUDIT-CONS-005: tenant_id como entity_reference a group.
        'tenant_id' => $tenantId,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al analizar diagnostico del tenant @id: @message', [
        '@id' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'strengths' => [],
        'weaknesses' => [],
        'opportunities' => [],
        'data_quality' => 0.0,
        'error' => (string) new TranslatableMarkup('Error al analizar los datos diagnosticos del tenant.'),
      ];
    }
  }

  /**
   * Genera un plan de negocio estructurado para un tenant.
   *
   * Crea un plan con fases secuenciales y tareas accionables basado
   * en el tipo de plan solicitado y el analisis diagnostico.
   *
   * @param int $tenantId
   *   ID del grupo/tenant (AUDIT-CONS-005: entity_reference a group).
   * @param string $planType
   *   Tipo de plan: 'growth', 'optimization' o 'expansion'.
   *
   * @return array
   *   Plan estructurado con fases y tareas, o error.
   */
  public function generatePlan(int $tenantId, string $planType = 'growth'): array {
    try {
      // Validar tipo de plan.
      if (!in_array($planType, self::VALID_PLAN_TYPES, TRUE)) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Tipo de plan "@type" no valido. Tipos permitidos: @valid.',
            ['@type' => $planType, '@valid' => implode(', ', self::VALID_PLAN_TYPES)],
          ),
        ];
      }

      // Obtener analisis diagnostico como base del plan.
      $analysis = $this->analyzeDiagnostic($tenantId);
      if (isset($analysis['error'])) {
        return [
          'success' => FALSE,
          'error' => $analysis['error'],
        ];
      }

      // Generar fases del plan segun el tipo.
      $phases = $this->buildPlanPhases($planType, $analysis);

      $planTypeLabels = [
        'growth' => (string) new TranslatableMarkup('Crecimiento'),
        'optimization' => (string) new TranslatableMarkup('Optimizacion'),
        'expansion' => (string) new TranslatableMarkup('Expansion'),
      ];

      $plan = [
        'plan_type' => $planType,
        'plan_type_label' => $planTypeLabels[$planType] ?? $planType,
        // AUDIT-CONS-005: tenant_id como entity_reference a group.
        'tenant_id' => $tenantId,
        'phases' => $phases,
        'total_phases' => count($phases),
        'total_tasks' => array_sum(array_map(fn(array $phase): int => count($phase['tasks'] ?? []), $phases)),
        'created_at' => date('Y-m-d\TH:i:s'),
        'data_quality' => $analysis['data_quality'],
      ];

      $this->logger->info('Plan de @type generado para tenant @id: @phases fases, @tasks tareas.', [
        '@type' => $planType,
        '@id' => $tenantId,
        '@phases' => $plan['total_phases'],
        '@tasks' => $plan['total_tasks'],
      ]);

      return [
        'success' => TRUE,
        'plan' => $plan,
        'message' => (string) new TranslatableMarkup('Plan de @type generado correctamente.', ['@type' => $planTypeLabels[$planType] ?? $planType]),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al generar plan para tenant @id: @message', [
        '@id' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error al generar el plan de negocio.'),
      ];
    }
  }

  /**
   * Crea tareas a partir de un plan generado.
   *
   * Transforma las tareas del plan en entidades de tarea asignables
   * a usuarios del tenant.
   *
   * @param int $tenantId
   *   ID del grupo/tenant (AUDIT-CONS-005: entity_reference a group).
   * @param array $plan
   *   Plan estructurado generado por generatePlan().
   *
   * @return array
   *   Array con ['success' => true, 'task_ids' => array] o error.
   */
  public function assignTasks(int $tenantId, array $plan): array {
    try {
      if (empty($plan['phases'])) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('El plan no contiene fases con tareas para asignar.'),
        ];
      }

      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $taskIds = [];

      foreach ($plan['phases'] as $phase) {
        foreach ($phase['tasks'] ?? [] as $task) {
          $taskEntity = $nodeStorage->create([
            'type' => 'task',
            'title' => $task['title'] ?? (string) new TranslatableMarkup('Tarea sin titulo'),
            'field_description' => $task['description'] ?? '',
            'field_priority' => $task['priority'] ?? 'medium',
            'field_phase' => $phase['name'] ?? '',
            'field_estimated_hours' => $task['estimated_hours'] ?? 0,
            'status' => 1,
            // AUDIT-CONS-005: tenant_id como entity_reference a group.
            'tenant_id' => $tenantId,
          ]);
          $taskEntity->save();
          $taskIds[] = (int) $taskEntity->id();
        }
      }

      $this->logger->info('@count tareas creadas del plan para tenant @id.', [
        '@count' => count($taskIds),
        '@id' => $tenantId,
      ]);

      return [
        'success' => TRUE,
        'task_ids' => $taskIds,
        'total_tasks_created' => count($taskIds),
        'message' => (string) new TranslatableMarkup('@count tareas creadas y asignadas correctamente.', ['@count' => count($taskIds)]),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al asignar tareas del plan para tenant @id: @message', [
        '@id' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error al crear las tareas del plan.'),
      ];
    }
  }

  /**
   * Devuelve la lista de capacidades del agente de planificacion.
   *
   * @return array
   *   Lista de identificadores de acciones que este agente puede realizar.
   */
  public function getCapabilities(): array {
    return [
      'analyze_tenant_diagnostic',
      'generate_growth_plan',
      'generate_optimization_plan',
      'generate_expansion_plan',
      'assign_plan_tasks',
      'view_tenant_metrics',
      'view_diagnostic_data',
    ];
  }

  /**
   * Obtiene datos diagnosticos asociados a un tenant.
   *
   * @param int $tenantId
   *   ID del tenant (AUDIT-CONS-005: entity_reference a group).
   *
   * @return array
   *   Array de entidades de resultado diagnostico.
   */
  protected function getTenantDiagnosticData(int $tenantId): array {
    try {
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $query = $nodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'diagnostic_result')
        // AUDIT-CONS-005: tenant_id como entity_reference a group.
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC');
      $ids = $query->execute();
      return !empty($ids) ? $nodeStorage->loadMultiple($ids) : [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener datos diagnosticos del tenant @id: @message', [
        '@id' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene metricas del tenant para analisis.
   *
   * @param int $tenantId
   *   ID del tenant (AUDIT-CONS-005: entity_reference a group).
   *
   * @return array
   *   Array de metricas disponibles.
   */
  protected function getTenantMetrics(int $tenantId): array {
    try {
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $query = $nodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'metric')
        // AUDIT-CONS-005: tenant_id como entity_reference a group.
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->range(0, 50);
      $ids = $query->execute();
      return !empty($ids) ? $nodeStorage->loadMultiple($ids) : [];
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Construye las fases del plan segun el tipo y analisis.
   *
   * @param string $planType
   *   Tipo de plan: growth, optimization o expansion.
   * @param array $analysis
   *   Resultados del analisis diagnostico.
   *
   * @return array
   *   Array de fases con tareas estructuradas.
   */
  protected function buildPlanPhases(string $planType, array $analysis): array {
    $phases = [];

    switch ($planType) {
      case 'growth':
        $phases[] = [
          'name' => (string) new TranslatableMarkup('Fase 1: Diagnostico y Linea Base'),
          'order' => 1,
          'duration_weeks' => 2,
          'tasks' => $this->generateDiagnosticTasks($analysis),
        ];
        $phases[] = [
          'name' => (string) new TranslatableMarkup('Fase 2: Fortalecimiento de Areas Clave'),
          'order' => 2,
          'duration_weeks' => 4,
          'tasks' => $this->generateStrengtheningTasks($analysis['strengths'] ?? []),
        ];
        $phases[] = [
          'name' => (string) new TranslatableMarkup('Fase 3: Expansion y Escalamiento'),
          'order' => 3,
          'duration_weeks' => 6,
          'tasks' => $this->generateGrowthTasks($analysis['opportunities'] ?? []),
        ];
        break;

      case 'optimization':
        $phases[] = [
          'name' => (string) new TranslatableMarkup('Fase 1: Auditoria de Procesos'),
          'order' => 1,
          'duration_weeks' => 2,
          'tasks' => $this->generateAuditTasks($analysis),
        ];
        $phases[] = [
          'name' => (string) new TranslatableMarkup('Fase 2: Correccion de Debilidades'),
          'order' => 2,
          'duration_weeks' => 4,
          'tasks' => $this->generateWeaknessTasks($analysis['weaknesses'] ?? []),
        ];
        $phases[] = [
          'name' => (string) new TranslatableMarkup('Fase 3: Medicion y Ajuste'),
          'order' => 3,
          'duration_weeks' => 2,
          'tasks' => $this->generateMeasurementTasks(),
        ];
        break;

      case 'expansion':
        $phases[] = [
          'name' => (string) new TranslatableMarkup('Fase 1: Analisis de Mercado'),
          'order' => 1,
          'duration_weeks' => 3,
          'tasks' => $this->generateMarketAnalysisTasks($analysis),
        ];
        $phases[] = [
          'name' => (string) new TranslatableMarkup('Fase 2: Preparacion de Infraestructura'),
          'order' => 2,
          'duration_weeks' => 4,
          'tasks' => $this->generateInfrastructureTasks(),
        ];
        $phases[] = [
          'name' => (string) new TranslatableMarkup('Fase 3: Lanzamiento y Penetracion'),
          'order' => 3,
          'duration_weeks' => 6,
          'tasks' => $this->generateLaunchTasks($analysis['opportunities'] ?? []),
        ];
        break;
    }

    return $phases;
  }

  /**
   * Genera tareas de diagnostico inicial.
   */
  protected function generateDiagnosticTasks(array $analysis): array {
    return [
      [
        'title' => (string) new TranslatableMarkup('Recopilar indicadores clave de rendimiento'),
        'description' => (string) new TranslatableMarkup('Identificar y documentar los KPIs actuales de la organizacion.'),
        'priority' => 'high',
        'estimated_hours' => 8,
      ],
      [
        'title' => (string) new TranslatableMarkup('Evaluar recursos disponibles'),
        'description' => (string) new TranslatableMarkup('Inventario de recursos humanos, tecnologicos y financieros.'),
        'priority' => 'medium',
        'estimated_hours' => 6,
      ],
    ];
  }

  /**
   * Genera tareas de fortalecimiento de areas clave.
   */
  protected function generateStrengtheningTasks(array $strengths): array {
    $tasks = [];
    foreach ($strengths as $strength) {
      $tasks[] = [
        'title' => (string) new TranslatableMarkup('Potenciar area: @area', ['@area' => $strength['area'] ?? 'General']),
        'description' => (string) new TranslatableMarkup('Desarrollar estrategia para maximizar el rendimiento en @area.', ['@area' => $strength['area'] ?? 'General']),
        'priority' => 'medium',
        'estimated_hours' => 10,
      ];
    }
    if (empty($tasks)) {
      $tasks[] = [
        'title' => (string) new TranslatableMarkup('Identificar areas de fortaleza'),
        'description' => (string) new TranslatableMarkup('Realizar analisis para identificar las principales fortalezas.'),
        'priority' => 'medium',
        'estimated_hours' => 8,
      ];
    }
    return $tasks;
  }

  /**
   * Genera tareas de crecimiento basadas en oportunidades.
   */
  protected function generateGrowthTasks(array $opportunities): array {
    $tasks = [];
    foreach ($opportunities as $opportunity) {
      $tasks[] = [
        'title' => (string) new TranslatableMarkup('Explotar oportunidad: @area', ['@area' => $opportunity['area'] ?? 'General']),
        'description' => (string) new TranslatableMarkup('Plan de accion para capitalizar la oportunidad en @area.', ['@area' => $opportunity['area'] ?? 'General']),
        'priority' => 'high',
        'estimated_hours' => 12,
      ];
    }
    if (empty($tasks)) {
      $tasks[] = [
        'title' => (string) new TranslatableMarkup('Investigar nuevas oportunidades de crecimiento'),
        'description' => (string) new TranslatableMarkup('Analisis de mercado para identificar oportunidades de expansion.'),
        'priority' => 'high',
        'estimated_hours' => 10,
      ];
    }
    return $tasks;
  }

  /**
   * Genera tareas de auditoria de procesos.
   */
  protected function generateAuditTasks(array $analysis): array {
    return [
      [
        'title' => (string) new TranslatableMarkup('Mapear procesos actuales'),
        'description' => (string) new TranslatableMarkup('Documentar todos los procesos operativos de la organizacion.'),
        'priority' => 'high',
        'estimated_hours' => 12,
      ],
      [
        'title' => (string) new TranslatableMarkup('Identificar cuellos de botella'),
        'description' => (string) new TranslatableMarkup('Analizar flujos de trabajo para detectar ineficiencias.'),
        'priority' => 'high',
        'estimated_hours' => 8,
      ],
    ];
  }

  /**
   * Genera tareas de correccion de debilidades.
   */
  protected function generateWeaknessTasks(array $weaknesses): array {
    $tasks = [];
    foreach ($weaknesses as $weakness) {
      $tasks[] = [
        'title' => (string) new TranslatableMarkup('Corregir debilidad: @area', ['@area' => $weakness['area'] ?? 'General']),
        'description' => (string) new TranslatableMarkup('Plan de mejora para el area de @area (puntuacion actual: @score).', [
          '@area' => $weakness['area'] ?? 'General',
          '@score' => $weakness['score'] ?? 0,
        ]),
        'priority' => 'high',
        'estimated_hours' => 10,
      ];
    }
    if (empty($tasks)) {
      $tasks[] = [
        'title' => (string) new TranslatableMarkup('Identificar areas de mejora prioritarias'),
        'description' => (string) new TranslatableMarkup('Analisis detallado para identificar debilidades organizacionales.'),
        'priority' => 'medium',
        'estimated_hours' => 8,
      ];
    }
    return $tasks;
  }

  /**
   * Genera tareas de medicion y ajuste.
   */
  protected function generateMeasurementTasks(): array {
    return [
      [
        'title' => (string) new TranslatableMarkup('Definir metricas de seguimiento'),
        'description' => (string) new TranslatableMarkup('Establecer KPIs para monitorear el progreso de las optimizaciones.'),
        'priority' => 'medium',
        'estimated_hours' => 4,
      ],
      [
        'title' => (string) new TranslatableMarkup('Programar revisiones periodicas'),
        'description' => (string) new TranslatableMarkup('Configurar calendario de revisiones semanales de indicadores.'),
        'priority' => 'low',
        'estimated_hours' => 2,
      ],
    ];
  }

  /**
   * Genera tareas de analisis de mercado.
   */
  protected function generateMarketAnalysisTasks(array $analysis): array {
    return [
      [
        'title' => (string) new TranslatableMarkup('Estudio de mercado objetivo'),
        'description' => (string) new TranslatableMarkup('Investigar el mercado potencial para la expansion.'),
        'priority' => 'high',
        'estimated_hours' => 16,
      ],
      [
        'title' => (string) new TranslatableMarkup('Analisis de competencia'),
        'description' => (string) new TranslatableMarkup('Evaluar competidores directos e indirectos en el mercado objetivo.'),
        'priority' => 'high',
        'estimated_hours' => 12,
      ],
    ];
  }

  /**
   * Genera tareas de preparacion de infraestructura.
   */
  protected function generateInfrastructureTasks(): array {
    return [
      [
        'title' => (string) new TranslatableMarkup('Evaluar capacidad tecnologica'),
        'description' => (string) new TranslatableMarkup('Verificar que la infraestructura soporta la expansion planificada.'),
        'priority' => 'high',
        'estimated_hours' => 8,
      ],
      [
        'title' => (string) new TranslatableMarkup('Planificar recursos humanos'),
        'description' => (string) new TranslatableMarkup('Determinar necesidades de contratacion para la expansion.'),
        'priority' => 'medium',
        'estimated_hours' => 6,
      ],
    ];
  }

  /**
   * Genera tareas de lanzamiento basadas en oportunidades.
   */
  protected function generateLaunchTasks(array $opportunities): array {
    $tasks = [
      [
        'title' => (string) new TranslatableMarkup('Ejecutar plan de lanzamiento'),
        'description' => (string) new TranslatableMarkup('Implementar la estrategia de penetracion en el nuevo mercado.'),
        'priority' => 'high',
        'estimated_hours' => 20,
      ],
    ];
    foreach ($opportunities as $opportunity) {
      $tasks[] = [
        'title' => (string) new TranslatableMarkup('Capitalizar oportunidad: @area', ['@area' => $opportunity['area'] ?? 'General']),
        'description' => (string) new TranslatableMarkup('Accion especifica para aprovechar @area en el nuevo mercado.', ['@area' => $opportunity['area'] ?? 'General']),
        'priority' => 'medium',
        'estimated_hours' => 10,
      ];
    }
    return $tasks;
  }

}
