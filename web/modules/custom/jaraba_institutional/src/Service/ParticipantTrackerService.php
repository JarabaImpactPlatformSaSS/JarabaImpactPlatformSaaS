<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de tracking de participantes en programas institucionales.
 *
 * Estructura: Gestiona inscripcion, seguimiento y resultados de
 *   participantes. Calcula indicadores de insercion laboral y
 *   formacion para reporting FUNDAE/FSE+.
 *
 * Logica: El enrollment valida que el programa este activo antes de
 *   inscribir. Los indicadores se calculan en tiempo real desde
 *   las entidades ProgramParticipant.
 */
class ParticipantTrackerService {

  /**
   * Resultados de empleo validos para participantes.
   */
  protected const VALID_OUTCOMES = [
    'employed',
    'self_employed',
    'training',
    'unemployed',
  ];

  /**
   * Campos que se pueden actualizar en un participante.
   */
  protected const UPDATABLE_FIELDS = [
    'first_name',
    'last_name',
    'email',
    'phone',
    'status',
    'hours_orientation',
    'hours_training',
    'certifications_obtained',
    'notes',
  ];

  /**
   * Construye el servicio de tracking de participantes.
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
   * Inscribe un nuevo participante en un programa.
   *
   * Estructura: Crea una entidad program_participant asociada al
   *   programa indicado. Verifica que el programa este activo.
   *
   * Logica: Solo se permite inscribir participantes en programas con
   *   estado 'active'. La fecha de inscripcion se establece
   *   automaticamente si no se proporciona. El tenant_id se hereda
   *   del programa (AUDIT-CONS-005).
   *
   * @param int $programId
   *   ID del programa en el que inscribir al participante.
   * @param array $data
   *   Datos del participante: first_name, last_name, email, etc.
   *
   * @return array
   *   ['success' => true, 'participant_id' => int] o error.
   */
  public function enroll(int $programId, array $data): array {
    try {
      // Verificar que el programa existe y esta activo.
      $programStorage = $this->entityTypeManager->getStorage('institutional_program');
      $program = $programStorage->load($programId);

      if (!$program) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Programa con ID @id no encontrado.',
            ['@id' => $programId]
          ),
        ];
      }

      // Verificar pertenencia al tenant actual (AUDIT-CONS-005).
      $programTenantId = $program->get('tenant_id')->target_id ?? NULL;
      if ($programTenantId !== $this->tenantContext->getCurrentTenantId()) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'No tiene permisos para inscribir participantes en este programa.'
          ),
        ];
      }

      // Verificar que el programa este en estado activo.
      $programStatus = $program->get('status')->value ?? '';
      if ($programStatus !== 'active') {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Solo se pueden inscribir participantes en programas activos. Estado actual: @status.',
            ['@status' => $programStatus]
          ),
        ];
      }

      $participantStorage = $this->entityTypeManager->getStorage('program_participant');

      // Preparar valores de la entidad participante.
      $values = [
        'program_id' => $programId,
        'tenant_id' => $this->tenantContext->getCurrentTenantId(),
        'enrollment_date' => $data['enrollment_date'] ?? date('Y-m-d'),
        'status' => 'active',
      ];

      // Asignar campos opcionales del participante.
      $participantFields = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'dni',
        'gender',
        'birth_date',
        'education_level',
        'employment_status_prior',
        'hours_orientation',
        'hours_training',
        'notes',
      ];
      foreach ($participantFields as $field) {
        if (isset($data[$field])) {
          $values[$field] = $data[$field];
        }
      }

      $entity = $participantStorage->create($values);
      $entity->save();

      $this->logger->info('Participante inscrito en programa @program: @name (ID: @id)', [
        '@program' => $programId,
        '@name' => ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''),
        '@id' => $entity->id(),
      ]);

      return [
        'success' => TRUE,
        'participant_id' => (int) $entity->id(),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al inscribir participante en programa @program: @error', [
        '@program' => $programId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al inscribir participante: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

  /**
   * Actualiza el resultado de empleo de un participante.
   *
   * Estructura: Actualiza el campo employment_outcome y opcionalmente
   *   employment_date en la entidad program_participant.
   *
   * Logica: Solo acepta resultados validos definidos en VALID_OUTCOMES.
   *   Si el resultado es 'employed' o 'self_employed', se establece
   *   employment_date automaticamente si no se proporciona. El
   *   estado del participante se actualiza a 'completed'.
   *
   * @param int $participantId
   *   ID del participante.
   * @param string $outcome
   *   Resultado: employed, self_employed, training, unemployed.
   * @param string|null $employmentDate
   *   Fecha de empleo (formato Y-m-d). Obligatoria para employed/self_employed.
   *
   * @return array
   *   ['success' => true] o error.
   */
  public function updateOutcome(int $participantId, string $outcome, ?string $employmentDate = NULL): array {
    try {
      // Validar que el outcome sea valido.
      if (!in_array($outcome, self::VALID_OUTCOMES, TRUE)) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Resultado "@outcome" no valido. Valores aceptados: @valid.',
            [
              '@outcome' => $outcome,
              '@valid' => implode(', ', self::VALID_OUTCOMES),
            ]
          ),
        ];
      }

      $storage = $this->entityTypeManager->getStorage('program_participant');
      $participant = $storage->load($participantId);

      if (!$participant) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Participante con ID @id no encontrado.',
            ['@id' => $participantId]
          ),
        ];
      }

      // Verificar pertenencia al tenant actual (AUDIT-CONS-005).
      $participantTenantId = $participant->get('tenant_id')->target_id ?? NULL;
      if ($participantTenantId !== $this->tenantContext->getCurrentTenantId()) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'No tiene permisos para modificar este participante.'
          ),
        ];
      }

      // Actualizar resultado de empleo.
      $participant->set('employment_outcome', $outcome);
      $participant->set('status', 'completed');

      // Si es empleo o autoempleo, establecer fecha de empleo.
      if (in_array($outcome, ['employed', 'self_employed'], TRUE)) {
        $dateValue = $employmentDate ?? date('Y-m-d');
        $participant->set('employment_date', $dateValue);
      }

      $participant->save();

      $this->logger->info('Participante @id: resultado actualizado a @outcome.', [
        '@id' => $participantId,
        '@outcome' => $outcome,
      ]);

      return ['success' => TRUE];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al actualizar resultado del participante @id: @error', [
        '@id' => $participantId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al actualizar resultado: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

  /**
   * Obtiene participantes de un programa con paginacion.
   *
   * Estructura: Consulta entidades program_participant filtradas
   *   por program_id con soporte de paginacion.
   *
   * Logica: Verifica que el programa pertenezca al tenant actual
   *   antes de retornar participantes (AUDIT-CONS-005).
   *
   * @param int $programId
   *   ID del programa.
   * @param int $limit
   *   Numero maximo de resultados.
   * @param int $offset
   *   Desplazamiento para paginacion.
   *
   * @return array
   *   ['participants' => [...], 'total' => int].
   */
  public function getByProgram(int $programId, int $limit = 50, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('program_participant');

      // Contar total de participantes del programa.
      $total = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('program_id', $programId)
        ->condition('tenant_id', $this->tenantContext->getCurrentTenantId())
        ->count()
        ->execute();

      // Consulta paginada.
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('program_id', $programId)
        ->condition('tenant_id', $this->tenantContext->getCurrentTenantId())
        ->sort('enrollment_date', 'DESC')
        ->range($offset, $limit)
        ->execute();

      $participants = !empty($ids) ? $storage->loadMultiple($ids) : [];

      return [
        'participants' => array_values($participants),
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener participantes del programa @id: @error', [
        '@id' => $programId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'participants' => [],
        'total' => 0,
      ];
    }
  }

  /**
   * Obtiene participantes filtrados por criterios multiples.
   *
   * Estructura: Consulta participantes con filtros opcionales de
   *   program_id, status y employment_outcome.
   *
   * Logica: Siempre filtra por tenant_id del contexto actual
   *   (AUDIT-CONS-005). Los filtros se aplican condicionalmente.
   *
   * @param array $filters
   *   Filtros: 'program_id', 'status', 'employment_outcome'.
   * @param int $limit
   *   Numero maximo de resultados.
   * @param int $offset
   *   Desplazamiento para paginacion.
   *
   * @return array
   *   ['participants' => [...], 'total' => int].
   */
  public function getParticipantsFiltered(array $filters, int $limit = 50, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('program_participant');

      // Construir consulta con filtros dinamicos.
      $buildQuery = function () use ($storage, $filters) {
        $query = $storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('tenant_id', $this->tenantContext->getCurrentTenantId());

        if (!empty($filters['program_id'])) {
          $query->condition('program_id', $filters['program_id']);
        }

        if (!empty($filters['status'])) {
          $query->condition('status', $filters['status']);
        }

        if (!empty($filters['employment_outcome'])) {
          if (is_array($filters['employment_outcome'])) {
            $query->condition('employment_outcome', $filters['employment_outcome'], 'IN');
          }
          else {
            $query->condition('employment_outcome', $filters['employment_outcome']);
          }
        }

        return $query;
      };

      // Contar total.
      $total = (int) $buildQuery()->count()->execute();

      // Consulta paginada.
      $query = $buildQuery();
      $query->sort('enrollment_date', 'DESC')
        ->range($offset, $limit);

      $ids = $query->execute();
      $participants = !empty($ids) ? $storage->loadMultiple($ids) : [];

      return [
        'participants' => array_values($participants),
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener participantes filtrados: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'participants' => [],
        'total' => 0,
      ];
    }
  }

  /**
   * Calcula indicadores de rendimiento de un programa.
   *
   * Estructura: Agrega metricas de participantes: totales, activos,
   *   completados, abandonos, tasa de insercion, horas promedio.
   *
   * Logica: Los indicadores se calculan en tiempo real desde las
   *   entidades ProgramParticipant. La tasa de insercion mide
   *   (employed + self_employed) / total_con_resultado * 100.
   *
   * @param int $programId
   *   ID del programa.
   *
   * @return array
   *   Indicadores: total_participants, active, completed, dropout,
   *   insertion_rate, avg_hours_orientation, avg_hours_training.
   */
  public function calculateIndicators(int $programId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('program_participant');
      $tenantId = $this->tenantContext->getCurrentTenantId();

      // Total de participantes del programa.
      $totalParticipants = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('program_id', $programId)
        ->condition('tenant_id', $tenantId)
        ->count()
        ->execute();

      // Participantes activos.
      $active = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('program_id', $programId)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'active')
        ->count()
        ->execute();

      // Participantes completados.
      $completed = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('program_id', $programId)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'completed')
        ->count()
        ->execute();

      // Participantes que abandonaron.
      $dropout = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('program_id', $programId)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'dropout')
        ->count()
        ->execute();

      // Calcular tasa de insercion laboral.
      $employedCount = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('program_id', $programId)
        ->condition('tenant_id', $tenantId)
        ->condition('employment_outcome', ['employed', 'self_employed'], 'IN')
        ->count()
        ->execute();

      $insertionRate = $totalParticipants > 0
        ? round(($employedCount / $totalParticipants) * 100, 2)
        : 0.0;

      // Calcular horas promedio de orientacion y formacion.
      $avgHoursOrientation = 0.0;
      $avgHoursTraining = 0.0;

      if ($totalParticipants > 0) {
        $allIds = $storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('program_id', $programId)
          ->condition('tenant_id', $tenantId)
          ->execute();

        if (!empty($allIds)) {
          $participants = $storage->loadMultiple($allIds);
          $totalOrientation = 0.0;
          $totalTraining = 0.0;

          foreach ($participants as $participant) {
            $totalOrientation += (float) ($participant->get('hours_orientation')->value ?? 0);
            $totalTraining += (float) ($participant->get('hours_training')->value ?? 0);
          }

          $count = count($participants);
          $avgHoursOrientation = round($totalOrientation / $count, 2);
          $avgHoursTraining = round($totalTraining / $count, 2);
        }
      }

      return [
        'total_participants' => $totalParticipants,
        'active' => $active,
        'completed' => $completed,
        'dropout' => $dropout,
        'insertion_rate' => $insertionRate,
        'avg_hours_orientation' => $avgHoursOrientation,
        'avg_hours_training' => $avgHoursTraining,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al calcular indicadores del programa @id: @error', [
        '@id' => $programId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'total_participants' => 0,
        'active' => 0,
        'completed' => 0,
        'dropout' => 0,
        'insertion_rate' => 0.0,
        'avg_hours_orientation' => 0.0,
        'avg_hours_training' => 0.0,
      ];
    }
  }

  /**
   * Actualiza campos permitidos de un participante.
   *
   * Estructura: Actualiza solo los campos definidos en UPDATABLE_FIELDS
   *   para la entidad program_participant indicada.
   *
   * Logica: Valida que cada campo a actualizar este en la lista
   *   de campos permitidos. Verifica pertenencia al tenant
   *   (AUDIT-CONS-005) antes de guardar.
   *
   * @param int $participantId
   *   ID del participante a actualizar.
   * @param array $data
   *   Pares campo => valor a actualizar.
   *
   * @return array
   *   ['success' => true] o error.
   */
  public function updateParticipant(int $participantId, array $data): array {
    try {
      $storage = $this->entityTypeManager->getStorage('program_participant');
      $participant = $storage->load($participantId);

      if (!$participant) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Participante con ID @id no encontrado.',
            ['@id' => $participantId]
          ),
        ];
      }

      // Verificar pertenencia al tenant actual (AUDIT-CONS-005).
      $participantTenantId = $participant->get('tenant_id')->target_id ?? NULL;
      if ($participantTenantId !== $this->tenantContext->getCurrentTenantId()) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'No tiene permisos para modificar este participante.'
          ),
        ];
      }

      // Filtrar y aplicar solo campos permitidos.
      $updatedFields = [];
      foreach ($data as $field => $value) {
        if (in_array($field, self::UPDATABLE_FIELDS, TRUE)) {
          $participant->set($field, $value);
          $updatedFields[] = $field;
        }
      }

      if (empty($updatedFields)) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'No se proporcionaron campos validos para actualizar. Campos permitidos: @fields.',
            ['@fields' => implode(', ', self::UPDATABLE_FIELDS)]
          ),
        ];
      }

      $participant->save();

      $this->logger->info('Participante @id actualizado. Campos: @fields.', [
        '@id' => $participantId,
        '@fields' => implode(', ', $updatedFields),
      ]);

      return ['success' => TRUE];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al actualizar participante @id: @error', [
        '@id' => $participantId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al actualizar participante: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

  /**
   * Obtiene las inscripciones mas recientes del tenant para el dashboard.
   *
   * Estructura: Consulta las ultimas inscripciones de participantes
   *   en cualquier programa del tenant actual.
   *
   * Logica: Ordena por enrollment_date descendente para mostrar
   *   los participantes mas recientes. Filtra por tenant_id
   *   (AUDIT-CONS-005).
   *
   * @param int $limit
   *   Numero maximo de participantes a retornar.
   *
   * @return array
   *   Array de entidades program_participant.
   */
  public function getRecentParticipants(int $limit = 10): array {
    try {
      $storage = $this->entityTypeManager->getStorage('program_participant');

      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $this->tenantContext->getCurrentTenantId())
        ->sort('enrollment_date', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      return array_values($storage->loadMultiple($ids));
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener participantes recientes: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
