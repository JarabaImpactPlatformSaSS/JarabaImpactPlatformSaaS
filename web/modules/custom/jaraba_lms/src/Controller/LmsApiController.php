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
        // TODO: Implement enrollment logic
        return new JsonResponse(['success' => TRUE, 'enrollment_id' => 0], 201);
    }

    /**
     * Gets user enrollments.
     */
    public function getUserEnrollments(): JsonResponse
    {
        return new JsonResponse(['enrollments' => []]);
    }

    /**
     * Gets progress for an enrollment.
     */
    public function getProgress(int $enrollment_id): JsonResponse
    {
        return new JsonResponse(['progress' => 0]);
    }

    /**
     * Records progress.
     */
    public function recordProgress(int $enrollment_id, Request $request): JsonResponse
    {
        return new JsonResponse(['success' => TRUE]);
    }

    /**
     * Lists learning paths.
     */
    public function listLearningPaths(): JsonResponse
    {
        return new JsonResponse(['learning_paths' => []]);
    }

    /**
     * Gets learning path recommendations.
     */
    public function getRecommendedPaths(): JsonResponse
    {
        return new JsonResponse(['recommendations' => []]);
    }

}
