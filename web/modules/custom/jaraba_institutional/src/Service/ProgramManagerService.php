<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion del ciclo de vida de programas institucionales.
 *
 * Estructura: Gestiona CRUD y transiciones de estado para programas
 *   STO/PIIL/FUNDAE/FSE+. Centraliza la logica de negocio de programas
 *   separandola del controller (API) y del form (admin).
 *
 * Logica: Las transiciones de estado siguen un flujo unidireccional:
 *   draft → active → reporting → closed → audited.
 *   El metodo getDashboardStats() agrega indicadores clave.
 */
class ProgramManagerService {

  /**
   * Transiciones de estado validas (flujo unidireccional).
   *
   * Cada clave es un estado origen y su valor es un array de estados
   * destino permitidos.
   */
  protected const STATUS_TRANSITIONS = [
    'draft' => ['active'],
    'active' => ['reporting'],
    'reporting' => ['closed'],
    'closed' => ['audited'],
    'audited' => [],
  ];

  /**
   * Construye el servicio de gestion de programas.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad de Drupal.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_institutional.
   * @param object $tenantContext
   *   Servicio de contexto de tenant (AUDIT-CONS-005).
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected object $tenantContext,
  ) {}

  /**
   * Obtiene los programas activos del tenant actual.
   *
   * Estructura: Consulta entidades institutional_program con estado
   *   'active' o 'reporting', ordenados por fecha de inicio descendente.
   *
   * Logica: Filtra siempre por tenant_id del contexto actual
   *   (AUDIT-CONS-005). Soporta paginacion con limit/offset.
   *
   * @param int $limit
   *   Numero maximo de resultados (por defecto 20).
   * @param int $offset
   *   Desplazamiento para paginacion (por defecto 0).
   *
   * @return array
   *   Array con claves 'programs' (array de entidades) y 'total' (int).
   */
  public function getActivePrograms(int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('institutional_program');

      // Consulta para contar el total de programas activos del tenant.
      $countQuery = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', ['active', 'reporting'], 'IN')
        ->condition('tenant_id', $this->tenantContext->getCurrentTenantId());
      $total = (int) $countQuery->count()->execute();

      // Consulta paginada con ordenacion por fecha de inicio descendente.
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', ['active', 'reporting'], 'IN')
        ->condition('tenant_id', $this->tenantContext->getCurrentTenantId())
        ->sort('start_date', 'DESC')
        ->range($offset, $limit);

      $ids = $query->execute();
      $programs = !empty($ids) ? $storage->loadMultiple($ids) : [];

      return [
        'programs' => array_values($programs),
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener programas activos: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'programs' => [],
        'total' => 0,
      ];
    }
  }

  /**
   * Obtiene programas filtrados por criterios multiples.
   *
   * Estructura: Extiende getActivePrograms() con filtros adicionales
   *   de status, program_type y funding_entity.
   *
   * Logica: Cada filtro se aplica condicionalmente. Si no se pasa
   *   un filtro, no se restringe por ese campo. Siempre filtra por
   *   tenant_id (AUDIT-CONS-005).
   *
   * @param array $filters
   *   Filtros opcionales: 'status', 'program_type', 'funding_entity'.
   * @param int $limit
   *   Numero maximo de resultados.
   * @param int $offset
   *   Desplazamiento para paginacion.
   *
   * @return array
   *   Array con claves 'programs' y 'total'.
   */
  public function getProgramsFiltered(array $filters, int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('institutional_program');

      // Construir consulta base con filtro de tenant obligatorio.
      $buildQuery = function () use ($storage, $filters) {
        $query = $storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('tenant_id', $this->tenantContext->getCurrentTenantId());

        if (!empty($filters['status'])) {
          if (is_array($filters['status'])) {
            $query->condition('status', $filters['status'], 'IN');
          }
          else {
            $query->condition('status', $filters['status']);
          }
        }

        if (!empty($filters['program_type'])) {
          $query->condition('program_type', $filters['program_type']);
        }

        if (!empty($filters['funding_entity'])) {
          $query->condition('funding_entity', $filters['funding_entity']);
        }

        return $query;
      };

      // Contar total de resultados con los filtros aplicados.
      $countQuery = $buildQuery();
      $total = (int) $countQuery->count()->execute();

      // Consulta paginada con filtros y ordenacion.
      $query = $buildQuery();
      $query->sort('start_date', 'DESC')
        ->range($offset, $limit);

      $ids = $query->execute();
      $programs = !empty($ids) ? $storage->loadMultiple($ids) : [];

      return [
        'programs' => array_values($programs),
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener programas filtrados: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'programs' => [],
        'total' => 0,
      ];
    }
  }

  /**
   * Crea un nuevo programa institucional.
   *
   * Estructura: Crea una entidad institutional_program con los datos
   *   proporcionados. Asigna automaticamente el tenant_id del contexto.
   *
   * Logica: Valida campos obligatorios antes de crear. El estado
   *   inicial es siempre 'draft'. El tenant_id se inyecta automaticamente
   *   desde el contexto de tenant (AUDIT-CONS-005).
   *
   * @param array $data
   *   Datos del programa: name, program_type, funding_entity, etc.
   *
   * @return array
   *   ['success' => true, 'program_id' => int] o
   *   ['success' => false, 'error' => string].
   */
  public function createProgram(array $data): array {
    try {
      // Validar campos obligatorios.
      $requiredFields = ['name', 'program_type'];
      foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
          return [
            'success' => FALSE,
            'error' => (string) new TranslatableMarkup(
              'El campo @field es obligatorio.',
              ['@field' => $field]
            ),
          ];
        }
      }

      $storage = $this->entityTypeManager->getStorage('institutional_program');

      // Preparar valores de la entidad con tenant_id obligatorio.
      $values = [
        'name' => $data['name'],
        'program_type' => $data['program_type'],
        'status' => 'draft',
        'tenant_id' => $this->tenantContext->getCurrentTenantId(),
      ];

      // Campos opcionales que se asignan si estan presentes.
      $optionalFields = [
        'funding_entity',
        'start_date',
        'end_date',
        'total_budget',
        'description',
        'program_code',
        'max_participants',
      ];
      foreach ($optionalFields as $field) {
        if (isset($data[$field])) {
          $values[$field] = $data[$field];
        }
      }

      $entity = $storage->create($values);
      $entity->save();

      $this->logger->info('Programa institucional creado: @name (ID: @id)', [
        '@name' => $data['name'],
        '@id' => $entity->id(),
      ]);

      return [
        'success' => TRUE,
        'program_id' => (int) $entity->id(),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al crear programa: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al crear el programa: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

  /**
   * Actualiza el estado de un programa validando transiciones permitidas.
   *
   * Estructura: Valida que la transicion de estado sea valida segun
   *   STATUS_TRANSITIONS antes de actualizarla en la entidad.
   *
   * Logica: El flujo es unidireccional:
   *   draft → active → reporting → closed → audited.
   *   No se permiten retrocesos ni saltos de estado. El tenant_id
   *   se verifica para garantizar aislamiento (AUDIT-CONS-005).
   *
   * @param int $programId
   *   ID del programa a actualizar.
   * @param string $newStatus
   *   Nuevo estado deseado.
   *
   * @return array
   *   ['success' => true] o ['success' => false, 'error' => string].
   */
  public function updateStatus(int $programId, string $newStatus): array {
    try {
      $storage = $this->entityTypeManager->getStorage('institutional_program');
      $program = $storage->load($programId);

      if (!$program) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Programa con ID @id no encontrado.',
            ['@id' => $programId]
          ),
        ];
      }

      // Verificar que el programa pertenece al tenant actual.
      $programTenantId = $program->get('tenant_id')->target_id ?? NULL;
      if ($programTenantId !== $this->tenantContext->getCurrentTenantId()) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'No tiene permisos para modificar este programa.'
          ),
        ];
      }

      $currentStatus = $program->get('status')->value ?? 'draft';

      // Validar que el estado actual sea reconocido.
      if (!isset(self::STATUS_TRANSITIONS[$currentStatus])) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Estado actual "@current" no reconocido.',
            ['@current' => $currentStatus]
          ),
        ];
      }

      // Validar que la transicion sea permitida.
      if (!in_array($newStatus, self::STATUS_TRANSITIONS[$currentStatus], TRUE)) {
        $allowedStr = implode(', ', self::STATUS_TRANSITIONS[$currentStatus]);
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Transicion de "@current" a "@new" no permitida. Transiciones validas: @allowed.',
            [
              '@current' => $currentStatus,
              '@new' => $newStatus,
              '@allowed' => $allowedStr ?: 'ninguna',
            ]
          ),
        ];
      }

      $program->set('status', $newStatus);
      $program->save();

      $this->logger->info('Programa @id: estado cambiado de @old a @new.', [
        '@id' => $programId,
        '@old' => $currentStatus,
        '@new' => $newStatus,
      ]);

      return ['success' => TRUE];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al actualizar estado del programa @id: @error', [
        '@id' => $programId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al actualizar el estado: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

  /**
   * Obtiene estadisticas agregadas del dashboard de programas.
   *
   * Estructura: Agrega metricas clave de todos los programas del tenant:
   *   totales, activos, participantes, presupuesto y tasa de insercion.
   *
   * Logica: Ejecuta multiples consultas de entidad para calcular
   *   cada indicador. Los calculos de presupuesto e insercion se
   *   derivan de los campos de las entidades program y participant.
   *
   * @return array
   *   Array con: total_programs, active_programs, total_participants,
   *   total_budget, budget_executed, avg_insertion_rate.
   */
  public function getDashboardStats(): array {
    try {
      $programStorage = $this->entityTypeManager->getStorage('institutional_program');
      $participantStorage = $this->entityTypeManager->getStorage('program_participant');
      $tenantId = $this->tenantContext->getCurrentTenantId();

      // Total de programas del tenant.
      $totalPrograms = (int) $programStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->count()
        ->execute();

      // Programas activos (status = 'active').
      $activePrograms = (int) $programStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'active')
        ->count()
        ->execute();

      // Obtener IDs de todos los programas del tenant para consultas de participantes.
      $programIds = $programStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->execute();

      $totalParticipants = 0;
      $totalBudget = 0.0;
      $budgetExecuted = 0.0;
      $avgInsertionRate = 0.0;

      if (!empty($programIds)) {
        $programIdValues = array_values($programIds);

        // Contar participantes de todos los programas del tenant.
        $totalParticipants = (int) $participantStorage->getQuery()
          ->accessCheck(TRUE)
          ->condition('program_id', $programIdValues, 'IN')
          ->count()
          ->execute();

        // Cargar programas para calcular presupuesto agregado.
        $programs = $programStorage->loadMultiple($programIds);
        foreach ($programs as $program) {
          $totalBudget += (float) ($program->get('total_budget')->value ?? 0);
          $budgetExecuted += (float) ($program->get('budget_executed')->value ?? 0);
        }

        // Calcular tasa de insercion: (empleados + autoempleo) / total con resultado.
        $employedCount = (int) $participantStorage->getQuery()
          ->accessCheck(TRUE)
          ->condition('program_id', $programIdValues, 'IN')
          ->condition('employment_outcome', ['employed', 'self_employed'], 'IN')
          ->count()
          ->execute();

        $withOutcome = (int) $participantStorage->getQuery()
          ->accessCheck(TRUE)
          ->condition('program_id', $programIdValues, 'IN')
          ->condition('employment_outcome', NULL, 'IS NOT NULL')
          ->count()
          ->execute();

        $avgInsertionRate = $withOutcome > 0
          ? round(($employedCount / $withOutcome) * 100, 2)
          : 0.0;
      }

      return [
        'total_programs' => $totalPrograms,
        'active_programs' => $activePrograms,
        'total_participants' => $totalParticipants,
        'total_budget' => $totalBudget,
        'budget_executed' => $budgetExecuted,
        'avg_insertion_rate' => $avgInsertionRate,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener estadisticas del dashboard: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'total_programs' => 0,
        'active_programs' => 0,
        'total_participants' => 0,
        'total_budget' => 0.0,
        'budget_executed' => 0.0,
        'avg_insertion_rate' => 0.0,
      ];
    }
  }

  /**
   * Carga un programa individual por su ID.
   *
   * Estructura: Carga una entidad institutional_program y verifica
   *   que pertenezca al tenant actual.
   *
   * Logica: Retorna NULL si el programa no existe o no pertenece
   *   al tenant actual (AUDIT-CONS-005).
   *
   * @param int $programId
   *   ID del programa a cargar.
   *
   * @return object|null
   *   La entidad del programa o NULL si no se encuentra.
   */
  public function getProgram(int $programId): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('institutional_program');
      $program = $storage->load($programId);

      if (!$program) {
        return NULL;
      }

      // Verificar pertenencia al tenant actual (AUDIT-CONS-005).
      $programTenantId = $program->get('tenant_id')->target_id ?? NULL;
      if ($programTenantId !== $this->tenantContext->getCurrentTenantId()) {
        $this->logger->warning('Intento de acceso a programa @id de otro tenant.', [
          '@id' => $programId,
        ]);
        return NULL;
      }

      return $program;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al cargar programa @id: @error', [
        '@id' => $programId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
