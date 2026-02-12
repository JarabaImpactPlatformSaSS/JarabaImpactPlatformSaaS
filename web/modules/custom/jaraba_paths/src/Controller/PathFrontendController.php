<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controlador frontend para itinerarios de digitalización.
 *
 * Usa entityTypeManager() heredado de ControllerBase.
 */
class PathFrontendController extends ControllerBase
{

    /**
     * Catálogo de itinerarios.
     *
     * /paths
     */
    public function catalog(): array
    {
        // Cargar todos los paths publicados
        $storage = $this->entityTypeManager()->getStorage('digitalization_path');
        $query = $storage->getQuery()
            ->condition('status', 1)
            ->accessCheck(TRUE)
            ->sort('created', 'DESC');

        $ids = $query->execute();
        $paths = $storage->loadMultiple($ids);

        // Preparar datos para el template
        $pathCards = [];
        foreach ($paths as $path) {
            $pathCards[] = [
                'id' => $path->id(),
                'uuid' => $path->uuid(),
                'title' => $path->label(),
                'description' => $path->get('description')->value ?? '',
                'sector' => $path->get('target_sector')->value ?? 'general',
                'difficulty' => $path->get('difficulty_level')->value ?? 'intermediate',
                'duration_weeks' => $path->get('estimated_weeks')->value ?? 4,
                'phases_count' => $this->countPhases($path->id()),
                'is_featured' => (bool) ($path->get('is_featured')->value ?? FALSE),
                'image_url' => $this->getPathImageUrl($path),
                'url' => $path->toUrl()->toString(),
            ];
        }

        // Obtener sectores únicos para filtros
        $sectors = $this->getUniqueSectors($paths);
        $difficulties = [
            'beginner' => $this->t('Principiante'),
            'intermediate' => $this->t('Intermedio'),
            'advanced' => $this->t('Avanzado'),
        ];

        return [
            '#theme' => 'path_catalog',
            '#paths' => $pathCards,
            '#featured_paths' => array_filter($pathCards, fn($p) => $p['is_featured']),
            '#sectors' => $sectors,
            '#difficulties' => $difficulties,
            '#total_count' => count($pathCards),
            '#cache' => ['max-age' => 3600],
            '#attached' => [
                'library' => ['jaraba_paths/catalog'],
            ],
        ];
    }

    /**
     * Detalle de itinerario.
     *
     * /path/{digitalization_path}
     */
    public function detail($digitalization_path): array
    {
        $storage = $this->entityTypeManager()->getStorage('digitalization_path');

        // Si viene como ID o como entidad
        if (is_numeric($digitalization_path)) {
            $path = $storage->load($digitalization_path);
        } else {
            $path = $digitalization_path;
        }

        if (!$path) {
            throw new NotFoundHttpException();
        }

        // Cargar fases del itinerario
        $phaseStorage = $this->entityTypeManager()->getStorage('path_phase');
        $phaseIds = $phaseStorage->getQuery()
            ->condition('path_id', $path->id())
            ->accessCheck(TRUE)
            ->sort('order', 'ASC')
            ->execute();
        $phases = $phaseStorage->loadMultiple($phaseIds);

        // Preparar fases con sus módulos
        $phasesData = [];
        foreach ($phases as $phase) {
            $modules = $this->getPhaseModules($phase->id());
            $phasesData[] = [
                'id' => $phase->id(),
                'title' => $phase->label(),
                'description' => $phase->get('description')->value ?? '',
                'duration_days' => $phase->get('estimated_days')->value ?? 7,
                'order' => $phase->get('order')->value ?? 0,
                'modules' => $modules,
                'modules_count' => count($modules),
            ];
        }

        // Verificar si el usuario actual está inscrito
        $currentUser = $this->currentUser();
        $enrollment = NULL;
        if ($currentUser->isAuthenticated()) {
            $enrollmentStorage = $this->entityTypeManager()->getStorage('path_enrollment');
            $enrollments = $enrollmentStorage->loadByProperties([
                'path_id' => $path->id(),
                'user_id' => $currentUser->id(),
            ]);
            $enrollment = !empty($enrollments) ? reset($enrollments) : NULL;
        }

        // Obtener outcomes/objetivos
        $outcomes = $this->getPathOutcomes($path);

        return [
            '#theme' => 'path_detail',
            '#path' => $path,
            '#title' => $path->label(),
            '#description' => $path->get('description')->value ?? '',
            '#sector' => $path->get('target_sector')->value ?? 'general',
            '#difficulty' => $path->get('difficulty_level')->value ?? 'intermediate',
            '#duration_weeks' => $path->get('estimated_weeks')->value ?? 4,
            '#phases' => $phasesData,
            '#phases_count' => count($phasesData),
            '#outcomes' => $outcomes,
            '#enrollment' => $enrollment,
            '#is_enrolled' => $enrollment !== NULL,
            '#enrollment_progress' => $enrollment ? ($enrollment->get('progress_percent')->value ?? 0) : 0,
            '#image_url' => $this->getPathImageUrl($path),
            '#cache' => ['max-age' => 0],
            '#attached' => [
                'library' => ['jaraba_paths/detail'],
                'drupalSettings' => [
                    'pathDetail' => [
                        'pathId' => $path->id(),
                        'pathUuid' => $path->uuid(),
                        'enrollEndpoint' => '/api/v1/paths/' . $path->uuid() . '/enroll',
                    ],
                ],
            ],
        ];
    }

    /**
     * Mi progreso.
     *
     * /my-progress
     */
    public function myProgress(): array
    {
        $currentUser = $this->currentUser();

        if (!$currentUser->isAuthenticated()) {
            return [
                '#theme' => 'path_progress',
                '#enrollments' => [],
                '#requires_login' => TRUE,
                '#cache' => ['max-age' => 0],
            ];
        }

        // Cargar inscripciones del usuario
        $enrollmentStorage = $this->entityTypeManager()->getStorage('path_enrollment');
        $enrollmentIds = $enrollmentStorage->getQuery()
            ->condition('user_id', $currentUser->id())
            ->accessCheck(TRUE)
            ->sort('enrolled_at', 'DESC')
            ->execute();
        $enrollments = $enrollmentStorage->loadMultiple($enrollmentIds);

        // Preparar datos de cada inscripción
        $enrollmentsData = [];
        $activeEnrollment = NULL;

        foreach ($enrollments as $enrollment) {
            $path = $this->entityTypeManager()->getStorage('digitalization_path')
                ->load($enrollment->get('path_id')->target_id);

            if (!$path)
                continue;

            $progress = $enrollment->get('progress_percent')->value ?? 0;
            $status = $enrollment->get('status')->value ?? 'active';

            $enrollmentData = [
                'id' => $enrollment->id(),
                'path_id' => $path->id(),
                'path_title' => $path->label(),
                'path_url' => $path->toUrl()->toString(),
                'progress' => $progress,
                'status' => $status,
                'started_at' => $enrollment->get('enrolled_at')->value,
                'last_activity' => $enrollment->get('last_activity_at')->value,
                'current_phase' => $this->getCurrentPhase($enrollment),
                'next_step' => $this->getNextStep($enrollment),
                'xp_earned' => $enrollment->get('xp_earned')->value ?? 0,
            ];

            $enrollmentsData[] = $enrollmentData;

            // Primera inscripción activa es la principal
            if (!$activeEnrollment && $status === 'active') {
                $activeEnrollment = $enrollmentData;
            }
        }

        // Estadísticas del usuario
        $stats = [
            'total_enrollments' => count($enrollmentsData),
            'completed' => count(array_filter($enrollmentsData, fn($e) => $e['status'] === 'completed')),
            'in_progress' => count(array_filter($enrollmentsData, fn($e) => $e['status'] === 'active')),
            'total_xp' => array_sum(array_column($enrollmentsData, 'xp_earned')),
        ];

        return [
            '#theme' => 'path_progress',
            '#enrollments' => $enrollmentsData,
            '#active_enrollment' => $activeEnrollment,
            '#stats' => $stats,
            '#requires_login' => FALSE,
            '#cache' => ['max-age' => 0],
            '#attached' => [
                'library' => ['jaraba_paths/progress'],
            ],
        ];
    }

    /**
     * Cuenta las fases de un path.
     */
    protected function countPhases(int|string $pathId): int
    {
        return (int) $this->entityTypeManager()->getStorage('path_phase')
            ->getQuery()
            ->condition('path_id', (int) $pathId)
            ->accessCheck(TRUE)
            ->count()
            ->execute();
    }

    /**
     * Obtiene URL de imagen del path.
     */
    protected function getPathImageUrl($path): ?string
    {
        // Por ahora retornar null, implementar cuando haya campo de imagen
        return NULL;
    }

    /**
     * Obtiene sectores únicos de los paths.
     */
    protected function getUniqueSectors(array $paths): array
    {
        $sectors = [];
        $sectorLabels = [
            'comercio' => $this->t('Comercio'),
            'servicios' => $this->t('Servicios'),
            'hosteleria' => $this->t('Hostelería'),
            'agro' => $this->t('Agroalimentación'),
            'industria' => $this->t('Industria'),
            'tech' => $this->t('Tecnología'),
            'general' => $this->t('General'),
        ];

        foreach ($paths as $path) {
            $sector = $path->get('target_sector')->value ?? 'general';
            if (!isset($sectors[$sector])) {
                $sectors[$sector] = $sectorLabels[$sector] ?? ucfirst($sector);
            }
        }

        return $sectors;
    }

    /**
     * Obtiene módulos de una fase.
     */
    protected function getPhaseModules(int $phaseId): array
    {
        $moduleStorage = $this->entityTypeManager()->getStorage('path_module');
        $moduleIds = $moduleStorage->getQuery()
            ->condition('phase_id', $phaseId)
            ->accessCheck(TRUE)
            ->sort('order', 'ASC')
            ->execute();
        $modules = $moduleStorage->loadMultiple($moduleIds);

        $modulesData = [];
        foreach ($modules as $module) {
            $modulesData[] = [
                'id' => $module->id(),
                'title' => $module->label(),
                'description' => $module->get('description')->value ?? '',
                'duration_hours' => $module->get('estimated_hours')->value ?? 1,
            ];
        }

        return $modulesData;
    }

    /**
     * Obtiene outcomes del path.
     */
    protected function getPathOutcomes($path): array
    {
        $outcomes = $path->get('outcomes')->value ?? '';
        if (empty($outcomes)) {
            return [
                $this->t('Digitaliza procesos clave de tu negocio'),
                $this->t('Mejora tu presencia online'),
                $this->t('Implementa herramientas de productividad'),
            ];
        }
        return array_filter(explode("\n", $outcomes));
    }

    /**
     * Obtiene la fase actual del usuario.
     */
    protected function getCurrentPhase($enrollment): ?array
    {
        // Simplificado - en implementación real se calcula desde completed_steps
        return NULL;
    }

    /**
     * Obtiene el siguiente paso a completar.
     */
    protected function getNextStep($enrollment): ?array
    {
        // Simplificado - en implementación real se calcula
        return NULL;
    }

}
