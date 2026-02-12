<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API Controller for LMS operations.
 */
class LmsApiController extends ControllerBase
{

    /**
     * Lists courses.
     */
    public function listCourses(Request $request): JsonResponse
    {
        $courses = $this->entityTypeManager()
            ->getStorage('lms_course')
            ->loadByProperties(['is_published' => TRUE]);

        $result = [];
        foreach ($courses as $course) {
            $result[] = [
                'id' => $course->id(),
                'title' => $course->getTitle(),
                'duration_minutes' => $course->getDurationMinutes(),
                'difficulty' => $course->getDifficultyLevel(),
            ];
        }

        return new JsonResponse(['courses' => $result, 'total' => count($result)]);
    }

    /**
     * Gets a single course.
     */
    public function getCourse(int $course_id): JsonResponse
    {
        $course = $this->entityTypeManager()->getStorage('lms_course')->load($course_id);

        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        return new JsonResponse([
            'id' => $course->id(),
            'title' => $course->getTitle(),
            'description' => $course->getDescription(),
            'duration_minutes' => $course->getDurationMinutes(),
            'difficulty' => $course->getDifficultyLevel(),
        ]);
    }

    /**
     * Enrolls in a course.
     */
    public function enroll(int $course_id, Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if ($user->isAnonymous()) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $user_id = (int) $user->id();

        // Verify course exists.
        $course = $this->entityTypeManager()->getStorage('lms_course')->load($course_id);
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        // Check if user is already enrolled.
        $existing = $this->entityTypeManager()->getStorage('lms_enrollment')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('user_id', $user_id)
            ->condition('course_id', $course_id)
            ->execute();

        if (!empty($existing)) {
            $enrollment = $this->entityTypeManager()->getStorage('lms_enrollment')
                ->load(reset($existing));
            return new JsonResponse([
                'success' => TRUE,
                'enrollment_id' => (int) $enrollment->id(),
                'status' => $enrollment->get('status')->value ?? 'active',
                'message' => 'Already enrolled',
            ], 200);
        }

        // Create new enrollment.
        $enrollment = $this->entityTypeManager()->getStorage('lms_enrollment')->create([
            'user_id' => $user_id,
            'course_id' => $course_id,
            'status' => 'active',
            'progress_percent' => 0,
            'enrolled_at' => date('Y-m-d\TH:i:s'),
        ]);
        $enrollment->save();

        return new JsonResponse([
            'success' => TRUE,
            'enrollment_id' => (int) $enrollment->id(),
            'course_id' => $course_id,
            'status' => 'active',
            'enrolled_at' => $enrollment->get('enrolled_at')->value,
        ], 201);
    }

    /**
     * Gets user enrollments.
     */
    public function getUserEnrollments(): JsonResponse
    {
        $user = $this->currentUser();
        if ($user->isAnonymous()) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $userId = (int) $user->id();

        try {
            $enrollmentIds = $this->entityTypeManager()
                ->getStorage('lms_enrollment')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $userId)
                ->sort('created', 'DESC')
                ->execute();

            $enrollments = $this->entityTypeManager()
                ->getStorage('lms_enrollment')
                ->loadMultiple($enrollmentIds);

            $result = [];
            foreach ($enrollments as $enrollment) {
                $course = $this->entityTypeManager()
                    ->getStorage('lms_course')
                    ->load($enrollment->get('course_id')->target_id);

                $result[] = [
                    'id' => (int) $enrollment->id(),
                    'course_id' => (int) $enrollment->get('course_id')->target_id,
                    'course_title' => $course ? $course->getTitle() : '',
                    'status' => $enrollment->get('status')->value ?? 'active',
                    'progress_percent' => (float) ($enrollment->get('progress_percent')->value ?? 0),
                    'enrolled_at' => $enrollment->get('enrolled_at')->value ?? '',
                ];
            }

            return new JsonResponse(['enrollments' => $result, 'total' => count($result)]);
        } catch (\Exception $e) {
            return new JsonResponse(['enrollments' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Gets progress for an enrollment.
     */
    public function getProgress(int $enrollment_id): JsonResponse
    {
        try {
            $enrollment = $this->entityTypeManager()
                ->getStorage('lms_enrollment')
                ->load($enrollment_id);

            if (!$enrollment) {
                return new JsonResponse(['error' => 'Enrollment not found'], 404);
            }

            // Verificar que el enrollment pertenece al usuario actual.
            $userId = (int) $this->currentUser()->id();
            if ((int) $enrollment->getOwnerId() !== $userId) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            $courseId = (int) $enrollment->get('course_id')->target_id;

            // Obtener lecciones del curso.
            $lessonIds = $this->entityTypeManager()
                ->getStorage('lms_lesson')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('course_id', $courseId)
                ->sort('weight', 'ASC')
                ->execute();

            $lessons = $this->entityTypeManager()
                ->getStorage('lms_lesson')
                ->loadMultiple($lessonIds);

            // Obtener lecciones completadas desde state.
            $completedLessons = $this->state()->get("lms_completed_{$enrollment_id}", []);

            $lessonProgress = [];
            foreach ($lessons as $lesson) {
                $lid = (int) $lesson->id();
                $lessonProgress[] = [
                    'id' => $lid,
                    'title' => $lesson->get('title')->value ?? '',
                    'completed' => in_array($lid, $completedLessons, TRUE),
                ];
            }

            return new JsonResponse([
                'enrollment_id' => $enrollment_id,
                'progress_percent' => (float) ($enrollment->get('progress_percent')->value ?? 0),
                'status' => $enrollment->get('status')->value ?? 'active',
                'total_lessons' => count($lessons),
                'completed_lessons' => count($completedLessons),
                'lessons' => $lessonProgress,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Records progress.
     */
    public function recordProgress(int $enrollment_id, Request $request): JsonResponse
    {
        try {
            $enrollment = $this->entityTypeManager()
                ->getStorage('lms_enrollment')
                ->load($enrollment_id);

            if (!$enrollment) {
                return new JsonResponse(['error' => 'Enrollment not found'], 404);
            }

            $userId = (int) $this->currentUser()->id();
            if ((int) $enrollment->getOwnerId() !== $userId) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            $data = json_decode($request->getContent(), TRUE);
            $lessonId = (int) ($data['lesson_id'] ?? 0);

            if ($lessonId <= 0) {
                return new JsonResponse(['error' => 'lesson_id is required'], 400);
            }

            $courseId = (int) $enrollment->get('course_id')->target_id;

            // Registrar lección como completada.
            $completedLessons = $this->state()->get("lms_completed_{$enrollment_id}", []);
            if (!in_array($lessonId, $completedLessons, TRUE)) {
                $completedLessons[] = $lessonId;
                $this->state()->set("lms_completed_{$enrollment_id}", $completedLessons);
            }

            // Calcular progreso.
            $totalLessons = (int) $this->entityTypeManager()
                ->getStorage('lms_lesson')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('course_id', $courseId)
                ->count()
                ->execute();

            $progressPercent = $totalLessons > 0
                ? round((count($completedLessons) / $totalLessons) * 100, 1)
                : 0;

            // Actualizar enrollment.
            $enrollment->set('progress_percent', $progressPercent);
            if ($progressPercent >= 100) {
                $enrollment->set('status', 'completed');
            }
            $enrollment->save();

            return new JsonResponse([
                'success' => TRUE,
                'enrollment_id' => $enrollment_id,
                'lesson_id' => $lessonId,
                'progress_percent' => $progressPercent,
                'status' => $enrollment->get('status')->value,
                'completed_lessons' => count($completedLessons),
                'total_lessons' => $totalLessons,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Lists learning paths.
     */
    public function listLearningPaths(): JsonResponse
    {
        try {
            $courseStorage = $this->entityTypeManager()->getStorage('lms_course');

            // Agrupar cursos publicados por dificultad como "paths" implícitos.
            $courseIds = $courseStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('is_published', TRUE)
                ->sort('difficulty', 'ASC')
                ->sort('created', 'ASC')
                ->execute();

            $courses = $courseStorage->loadMultiple($courseIds);

            $paths = [];
            foreach ($courses as $course) {
                $difficulty = $course->getDifficultyLevel();
                if (!isset($paths[$difficulty])) {
                    $paths[$difficulty] = [
                        'id' => $difficulty,
                        'name' => ucfirst($difficulty) . ' Path',
                        'difficulty' => $difficulty,
                        'courses' => [],
                        'total_duration' => 0,
                    ];
                }
                $duration = $course->getDurationMinutes();
                $paths[$difficulty]['courses'][] = [
                    'id' => (int) $course->id(),
                    'title' => $course->getTitle(),
                    'duration_minutes' => $duration,
                ];
                $paths[$difficulty]['total_duration'] += $duration;
            }

            return new JsonResponse([
                'learning_paths' => array_values($paths),
                'total' => count($paths),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['learning_paths' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Gets learning path recommendations.
     */
    public function getRecommendedPaths(): JsonResponse
    {
        $user = $this->currentUser();
        if ($user->isAnonymous()) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $userId = (int) $user->id();

        try {
            // Obtener cursos ya completados.
            $completedIds = $this->entityTypeManager()
                ->getStorage('lms_enrollment')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $userId)
                ->condition('status', 'completed')
                ->execute();

            $completedCourseIds = [];
            if (!empty($completedIds)) {
                $enrollments = $this->entityTypeManager()
                    ->getStorage('lms_enrollment')
                    ->loadMultiple($completedIds);
                foreach ($enrollments as $enrollment) {
                    $completedCourseIds[] = (int) $enrollment->get('course_id')->target_id;
                }
            }

            // Determinar nivel recomendado.
            $recommendedDifficulty = 'beginner';
            if (count($completedCourseIds) >= 5) {
                $recommendedDifficulty = 'advanced';
            } elseif (count($completedCourseIds) >= 2) {
                $recommendedDifficulty = 'intermediate';
            }

            // Buscar cursos no completados en el nivel recomendado.
            $query = $this->entityTypeManager()
                ->getStorage('lms_course')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('is_published', TRUE)
                ->condition('difficulty', $recommendedDifficulty)
                ->sort('created', 'DESC')
                ->range(0, 5);

            if (!empty($completedCourseIds)) {
                $query->condition('id', $completedCourseIds, 'NOT IN');
            }

            $courseIds = $query->execute();
            $courses = $this->entityTypeManager()
                ->getStorage('lms_course')
                ->loadMultiple($courseIds);

            $recommendations = [];
            foreach ($courses as $course) {
                $recommendations[] = [
                    'id' => (int) $course->id(),
                    'title' => $course->getTitle(),
                    'difficulty' => $course->getDifficultyLevel(),
                    'duration_minutes' => $course->getDurationMinutes(),
                    'reason' => "Recomendado por tu nivel: {$recommendedDifficulty}",
                ];
            }

            return new JsonResponse([
                'recommendations' => $recommendations,
                'recommended_level' => $recommendedDifficulty,
                'completed_courses' => count($completedCourseIds),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['recommendations' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Gets the state service.
     */
    protected function state(): \Drupal\Core\State\StateInterface
    {
        return \Drupal::state();
    }

}
