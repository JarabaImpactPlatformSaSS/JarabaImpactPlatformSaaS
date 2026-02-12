<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de inscripciones en itinerarios.
 *
 * Reutiliza el patrón de EnrollmentService de jaraba_lms.
 */
class PathEnrollmentService
{

    protected EntityTypeManagerInterface $entityTypeManager;
    protected Connection $database;
    protected AccountProxyInterface $currentUser;
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Connection $database,
        AccountProxyInterface $currentUser,
        LoggerInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->database = $database;
        $this->currentUser = $currentUser;
        $this->logger = $loggerFactory;
    }

    /**
     * Inscribe a un usuario en un itinerario.
     *
     * @param int $userId
     *   ID del usuario.
     * @param int $pathId
     *   ID del itinerario.
     * @param int|null $diagnosticId
     *   ID del diagnóstico que originó la inscripción (opcional).
     *
     * @return array
     *   Resultado de la inscripción.
     */
    public function enroll(int $userId, int $pathId, ?int $diagnosticId = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('path_enrollment');

        // Verificar si ya existe inscripción
        $existing = $storage->loadByProperties([
            'user_id' => $userId,
            'path_id' => $pathId,
        ]);

        if (!empty($existing)) {
            $enrollment = reset($existing);
            return [
                'success' => TRUE,
                'enrollment_id' => $enrollment->id(),
                'already_enrolled' => TRUE,
                'message' => 'Ya estás inscrito en este itinerario.',
            ];
        }

        // Crear nueva inscripción
        try {
            $enrollment = $storage->create([
                'user_id' => $userId,
                'path_id' => $pathId,
                'diagnostic_id' => $diagnosticId,
                'status' => 'active',
                'progress_percent' => 0,
                'steps_completed' => 0,
            ]);
            $enrollment->save();

            $this->logger->info('User @user enrolled in path @path', [
                '@user' => $userId,
                '@path' => $pathId,
            ]);

            // Otorgar XP de inscripción
            $this->awardEnrollmentXp($userId);

            return [
                'success' => TRUE,
                'enrollment_id' => $enrollment->id(),
                'already_enrolled' => FALSE,
                'message' => '¡Inscripción completada! Ya puedes empezar tu itinerario.',
            ];
        } catch (\Exception $e) {
            $this->logger->error('Enrollment error: @error', ['@error' => $e->getMessage()]);
            return [
                'success' => FALSE,
                'message' => 'Error al inscribirse: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Auto-enrollment después de diagnóstico.
     */
    public function autoEnrollFromDiagnostic(int $userId, int $diagnosticId, int $recommendedPathId): array
    {
        return $this->enroll($userId, $recommendedPathId, $diagnosticId);
    }

    /**
     * Obtiene las inscripciones activas de un usuario.
     */
    public function getUserEnrollments(int $userId): array
    {
        $storage = $this->entityTypeManager->getStorage('path_enrollment');
        return $storage->loadByProperties([
            'user_id' => $userId,
            'status' => 'active',
        ]);
    }

    /**
     * Otorga XP por inscripción.
     */
    protected function awardEnrollmentXp(int $userId): void
    {
        if (\Drupal::hasService('ecosistema_jaraba_core.impact_credit')) {
            try {
                $creditService = \Drupal::service('ecosistema_jaraba_core.impact_credit');
                $creditService->awardCredits($userId, 'path_enrolled', NULL, []);
            } catch (\Exception $e) {
                // Log pero no bloquear
            }
        }
    }

}
