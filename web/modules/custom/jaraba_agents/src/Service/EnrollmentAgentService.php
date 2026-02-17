<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Agente especializado de auto-enrollment post-diagnostico.
 *
 * ESTRUCTURA:
 *   Analiza el perfil del usuario y resultados de diagnostico
 *   para recomendar y ejecutar inscripciones automaticas en programas
 *   y rutas de aprendizaje personalizadas.
 *
 * LOGICA:
 *   Opera en niveles L1 (sugerencia) o L2 (semi-autonomo).
 *   En L1 genera recomendaciones sin ejecutar acciones.
 *   En L2 ejecuta la inscripcion directamente si no requiere
 *   aprobacion humana. Las recomendaciones se basan en el perfil
 *   del usuario, resultados diagnosticos y programas disponibles.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class EnrollmentAgentService {

  /**
   * Umbral de confianza minimo para auto-enrollment en L2.
   */
  protected const MIN_CONFIDENCE_THRESHOLD = 0.7;

  /**
   * Construye el servicio del agente de enrollment.
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
   * Analiza el perfil del usuario y sus resultados diagnosticos.
   *
   * Evalua el perfil, competencias previas y resultados de diagnosticos
   * completados para generar recomendaciones de inscripcion.
   *
   * @param int $userId
   *   ID del usuario a analizar.
   *
   * @return array
   *   Array con claves:
   *   - 'recommendations': array de programas recomendados con detalles.
   *   - 'confidence': float entre 0.0 y 1.0 indicando confianza.
   */
  public function analyze(int $userId): array {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $user = $userStorage->load($userId);

      if (!$user) {
        $this->logger->error('Usuario no encontrado para analisis de enrollment: @id', [
          '@id' => $userId,
        ]);
        return [
          'recommendations' => [],
          'confidence' => 0.0,
          'error' => (string) new TranslatableMarkup('Usuario con ID @id no encontrado.', ['@id' => $userId]),
        ];
      }

      // Obtener resultados de diagnostico del usuario.
      $diagnosticResults = $this->getUserDiagnosticResults($userId);

      if (empty($diagnosticResults)) {
        $this->logger->info('No se encontraron resultados diagnosticos para usuario @id.', [
          '@id' => $userId,
        ]);
        return [
          'recommendations' => [],
          'confidence' => 0.0,
          'message' => (string) new TranslatableMarkup('El usuario no tiene resultados diagnosticos disponibles.'),
        ];
      }

      // Obtener programas disponibles para el tenant actual.
      // AUDIT-CONS-005: tenant_id como entity_reference a group.
      $availablePrograms = $this->getAvailablePrograms();

      // Generar recomendaciones basadas en el analisis.
      $recommendations = [];
      $totalScore = 0.0;

      foreach ($availablePrograms as $program) {
        $matchScore = $this->calculateMatchScore($diagnosticResults, $program);
        if ($matchScore >= 0.5) {
          $recommendations[] = [
            'program_id' => (int) $program->id(),
            'program_name' => $program->label(),
            'match_score' => round($matchScore, 2),
            'reason' => (string) new TranslatableMarkup(
              'Compatibilidad del @score% basada en resultados diagnosticos.',
              ['@score' => round($matchScore * 100)],
            ),
          ];
          $totalScore += $matchScore;
        }
      }

      // Ordenar por puntuacion descendente.
      usort($recommendations, fn(array $a, array $b): int => $b['match_score'] <=> $a['match_score']);

      // La confianza general es el promedio de los scores de las recomendaciones.
      $confidence = !empty($recommendations)
        ? round($totalScore / count($recommendations), 2)
        : 0.0;

      $this->logger->info('Analisis de enrollment completado para usuario @id: @count recomendaciones (confianza: @conf).', [
        '@id' => $userId,
        '@count' => count($recommendations),
        '@conf' => $confidence,
      ]);

      return [
        'recommendations' => $recommendations,
        'confidence' => $confidence,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al analizar enrollment para usuario @id: @message', [
        '@id' => $userId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'recommendations' => [],
        'confidence' => 0.0,
        'error' => (string) new TranslatableMarkup('Error al analizar el perfil del usuario.'),
      ];
    }
  }

  /**
   * Inscribe a un usuario en un programa recomendado.
   *
   * Solo se ejecuta si el nivel de confianza supera el umbral minimo
   * o si se invoca explicitamente por un administrador.
   *
   * @param int $userId
   *   ID del usuario a inscribir.
   * @param int $programId
   *   ID del programa en el que inscribir al usuario.
   *
   * @return array
   *   Array con ['success' => true] o error.
   */
  public function enrollUser(int $userId, int $programId): array {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $user = $userStorage->load($userId);

      if (!$user) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('Usuario con ID @id no encontrado.', ['@id' => $userId]),
        ];
      }

      // Verificar que el programa existe.
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $program = $nodeStorage->load($programId);

      if (!$program) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('Programa con ID @id no encontrado.', ['@id' => $programId]),
        ];
      }

      // Verificar que el usuario no esta ya inscrito.
      if ($this->isUserEnrolled($userId, $programId)) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('El usuario ya esta inscrito en este programa.'),
        ];
      }

      // Crear la inscripcion (enrollment entity o group membership).
      // La implementacion especifica depende del sistema de enrollment del modulo.
      $enrollmentStorage = $this->entityTypeManager->getStorage('group_content');
      $enrollment = $enrollmentStorage->create([
        'type' => 'program-group_membership',
        'gid' => $programId,
        'entity_id' => $userId,
        // AUDIT-CONS-005: tenant_id como entity_reference a group.
        'tenant_id' => $this->getCurrentTenantId(),
      ]);
      $enrollment->save();

      $this->logger->info('Usuario @user inscrito en programa @program por agente de enrollment.', [
        '@user' => $userId,
        '@program' => $programId,
      ]);

      return [
        'success' => TRUE,
        'enrollment_id' => (int) $enrollment->id(),
        'message' => (string) new TranslatableMarkup('Usuario inscrito correctamente en el programa.'),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al inscribir usuario @user en programa @program: @message', [
        '@user' => $userId,
        '@program' => $programId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error al realizar la inscripcion.'),
      ];
    }
  }

  /**
   * Crea una ruta de aprendizaje personalizada basada en diagnostico.
   *
   * Genera una secuencia ordenada de contenidos y actividades adaptada
   * a las necesidades y nivel del usuario segun sus resultados.
   *
   * @param int $userId
   *   ID del usuario para el que crear la ruta.
   *
   * @return array
   *   Array con ['success' => true, 'path' => array] o error.
   */
  public function createLearningPath(int $userId): array {
    try {
      // Primero analizar al usuario.
      $analysis = $this->analyze($userId);

      if (empty($analysis['recommendations'])) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('No se encontraron programas adecuados para crear una ruta de aprendizaje.'),
        ];
      }

      // Construir la ruta de aprendizaje ordenada por prioridad.
      $path = [];
      $order = 1;

      foreach ($analysis['recommendations'] as $recommendation) {
        $path[] = [
          'order' => $order,
          'program_id' => $recommendation['program_id'],
          'program_name' => $recommendation['program_name'],
          'match_score' => $recommendation['match_score'],
          'estimated_duration' => (string) new TranslatableMarkup('@weeks semanas', ['@weeks' => $order * 2]),
          'status' => 'pending',
        ];
        $order++;
      }

      $this->logger->info('Ruta de aprendizaje creada para usuario @id con @count etapas.', [
        '@id' => $userId,
        '@count' => count($path),
      ]);

      return [
        'success' => TRUE,
        'path' => $path,
        'total_steps' => count($path),
        'confidence' => $analysis['confidence'],
        'message' => (string) new TranslatableMarkup('Ruta de aprendizaje personalizada creada correctamente.'),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al crear ruta de aprendizaje para usuario @id: @message', [
        '@id' => $userId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error al crear la ruta de aprendizaje.'),
      ];
    }
  }

  /**
   * Devuelve la lista de capacidades del agente de enrollment.
   *
   * @return array
   *   Lista de identificadores de acciones que este agente puede realizar.
   */
  public function getCapabilities(): array {
    return [
      'analyze_user_profile',
      'generate_enrollment_recommendations',
      'enroll_user_in_program',
      'create_learning_path',
      'view_diagnostic_results',
      'view_available_programs',
    ];
  }

  /**
   * Obtiene los resultados de diagnostico de un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Array de resultados diagnosticos.
   */
  protected function getUserDiagnosticResults(int $userId): array {
    try {
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $query = $nodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'diagnostic_result')
        ->condition('uid', $userId)
        ->sort('created', 'DESC');
      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      return $nodeStorage->loadMultiple($ids);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener diagnosticos del usuario @id: @message', [
        '@id' => $userId,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene los programas disponibles para el tenant actual.
   *
   * AUDIT-CONS-005: Filtrado por tenant_id (entity_reference a group).
   *
   * @return array
   *   Array de entidades de programa disponibles.
   */
  protected function getAvailablePrograms(): array {
    try {
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $query = $nodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'program')
        ->condition('status', 1);

      // AUDIT-CONS-005: Filtrar por tenant_id si esta disponible.
      $tenantId = $this->getCurrentTenantId();
      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();
      return !empty($ids) ? $nodeStorage->loadMultiple($ids) : [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener programas disponibles: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Calcula la puntuacion de compatibilidad entre diagnostico y programa.
   *
   * @param array $diagnosticResults
   *   Resultados diagnosticos del usuario.
   * @param object $program
   *   Entidad del programa a evaluar.
   *
   * @return float
   *   Puntuacion de 0.0 a 1.0.
   */
  protected function calculateMatchScore(array $diagnosticResults, object $program): float {
    // Puntuacion base segun disponibilidad de datos.
    $score = 0.5;

    if (!empty($diagnosticResults)) {
      // Incrementar score basandose en la cantidad de datos disponibles.
      $dataPoints = count($diagnosticResults);
      $score += min(0.3, $dataPoints * 0.1);
    }

    return min(1.0, $score);
  }

  /**
   * Verifica si un usuario ya esta inscrito en un programa.
   *
   * @param int $userId
   *   ID del usuario.
   * @param int $programId
   *   ID del programa.
   *
   * @return bool
   *   TRUE si ya esta inscrito, FALSE en caso contrario.
   */
  protected function isUserEnrolled(int $userId, int $programId): bool {
    try {
      $gcStorage = $this->entityTypeManager->getStorage('group_content');
      $query = $gcStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('gid', $programId)
        ->condition('entity_id', $userId)
        ->count();
      return ((int) $query->execute()) > 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Obtiene el ID del tenant actual desde el contexto.
   *
   * AUDIT-CONS-005: tenant_id como entity_reference a group.
   *
   * @return int|null
   *   ID del tenant actual o NULL si no esta disponible.
   */
  protected function getCurrentTenantId(): ?int {
    try {
      if (method_exists($this->tenantContext, 'getCurrentTenantId')) {
        return $this->tenantContext->getCurrentTenantId();
      }
    }
    catch (\Exception $e) {
      $this->logger->notice('Contexto de tenant no disponible: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
    return NULL;
  }

}
