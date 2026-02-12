<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de tracking de progreso en itinerarios.
 *
 * Reutiliza patrón de ProgressTrackingService de jaraba_lms.
 */
class PathProgressService
{

    protected EntityTypeManagerInterface $entityTypeManager;
    protected Connection $database;
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Connection $database,
        LoggerInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->database = $database;
        $this->logger = $loggerFactory;
    }

    /**
     * Marca un paso como completado.
     */
    public function completeStep(int $enrollmentId, int $stepId): array
    {
        $enrollmentStorage = $this->entityTypeManager->getStorage('path_enrollment');
        $stepStorage = $this->entityTypeManager->getStorage('path_step');

        /** @var \Drupal\jaraba_paths\Entity\PathEnrollment $enrollment */
        $enrollment = $enrollmentStorage->load($enrollmentId);
        /** @var \Drupal\jaraba_paths\Entity\PathStep $step */
        $step = $stepStorage->load($stepId);

        if (!$enrollment || !$step) {
            return ['success' => FALSE, 'message' => 'Inscripción o paso no encontrado.'];
        }

        // Registrar completado
        $xpReward = (int) ($step->get('xp_reward')->value ?? 10);
        $enrollment->recordStepCompletion($stepId, $xpReward);

        // Recalcular progreso
        $progress = $this->calculateProgress($enrollment);
        $enrollment->set('progress_percent', $progress);

        // Verificar si completó todo
        if ($progress >= 100) {
            $enrollment->set('status', 'completed');
            $enrollment->set('completed_at', time());
        }

        $enrollment->save();

        $this->logger->info('Step @step completed for enrollment @enrollment. Progress: @progress%', [
            '@step' => $stepId,
            '@enrollment' => $enrollmentId,
            '@progress' => $progress,
        ]);

        return [
            'success' => TRUE,
            'xp_earned' => $xpReward,
            'progress_percent' => $progress,
            'is_completed' => $progress >= 100,
        ];
    }

    /**
     * Calcula el progreso de una inscripción.
     */
    public function calculateProgress($enrollment): float
    {
        $pathId = $enrollment->get('path_id')->target_id;
        $stepsCompleted = (int) ($enrollment->get('steps_completed')->value ?? 0);

        // Contar total de pasos obligatorios del path
        $totalSteps = $this->countPathSteps($pathId, TRUE);

        if ($totalSteps === 0) {
            return 0;
        }

        return min(100, round(($stepsCompleted / $totalSteps) * 100, 2));
    }

    /**
     * Cuenta los pasos de un itinerario.
     */
    public function countPathSteps(int $pathId, bool $requiredOnly = FALSE): int
    {
        // Obtener fases del path
        $phases = $this->entityTypeManager->getStorage('path_phase')
            ->loadByProperties(['path_id' => $pathId]);

        $totalSteps = 0;

        foreach ($phases as $phase) {
            // Obtener módulos de la fase
            $modules = $this->entityTypeManager->getStorage('path_module')
                ->loadByProperties(['phase_id' => $phase->id()]);

            foreach ($modules as $module) {
                // Contar pasos del módulo
                $query = $this->entityTypeManager->getStorage('path_step')
                    ->getQuery()
                    ->condition('module_id', $module->id())
                    ->accessCheck(FALSE);

                if ($requiredOnly) {
                    $query->condition('is_required', TRUE);
                }

                $totalSteps += $query->count()->execute();
            }
        }

        return $totalSteps;
    }

    /**
     * Obtiene el resumen de progreso de un usuario.
     */
    public function getProgressSummary(int $userId): array
    {
        $enrollments = $this->entityTypeManager->getStorage('path_enrollment')
            ->loadByProperties(['user_id' => $userId]);

        $summary = [
            'total_enrollments' => count($enrollments),
            'active' => 0,
            'completed' => 0,
            'total_steps_completed' => 0,
            'total_xp_earned' => 0,
        ];

        foreach ($enrollments as $enrollment) {
            $status = $enrollment->get('status')->value;
            if ($status === 'active') {
                $summary['active']++;
            } elseif ($status === 'completed') {
                $summary['completed']++;
            }

            $summary['total_steps_completed'] += (int) $enrollment->get('steps_completed')->value;
            $summary['total_xp_earned'] += (int) $enrollment->get('xp_earned')->value;
        }

        return $summary;
    }

}
