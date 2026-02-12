<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for Lesson API endpoints.
 */
class LessonController extends ControllerBase
{

    /**
     * Marks a lesson as complete for the current user.
     *
     * @param int $lesson_id
     *   The lesson ID.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response.
     */
    public function markComplete(int $lesson_id): JsonResponse
    {
        $user = $this->currentUser();

        if ($user->isAnonymous()) {
            return new JsonResponse(['success' => FALSE, 'error' => 'Authentication required'], 401);
        }

        try {
            // Load the lesson
            $lesson = $this->entityTypeManager()->getStorage('lms_lesson')->load($lesson_id);

            if (!$lesson) {
                return new JsonResponse(['success' => FALSE, 'error' => 'Lesson not found'], 404);
            }

            // Find or create progress record
            // This would typically update an enrollment or progress entity
            // For now, we'll store in a simple progress tracking table
            $database = \Drupal::database();

            $database->merge('lms_lesson_progress')
                ->keys([
                    'lesson_id' => $lesson_id,
                    'uid' => $user->id(),
                ])
                ->fields([
                    'completed' => 1,
                    'completed_at' => time(),
                ])
                ->execute();

            return new JsonResponse([
                'success' => TRUE,
                'lesson_id' => $lesson_id,
                'message' => 'Lesson marked as complete',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
