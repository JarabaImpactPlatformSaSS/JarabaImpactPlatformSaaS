<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the AI Feedback REST endpoint.
 *
 * POST /api/v1/ai/feedback — Creates an AI Feedback entity from user input.
 *
 * CSRF-API-001: CSRF token validation is handled by the routing layer
 * via `_csrf_token: 'TRUE'` in the route definition. Drupal validates
 * the X-Drupal-Ajax-Token header automatically for POST requests.
 *
 * ENTITY-APPEND-001: Feedback is append-only. No update or delete endpoints.
 *
 * FIX-034: AI Feedback entity and endpoint.
 */
class AiFeedbackController extends ControllerBase
{

    // Note: $entityTypeManager and $currentUser are inherited from ControllerBase.

    /**
     * Constructs an AiFeedbackController.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   The current user.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('current_user'),
        );
    }

    /**
     * Receives and stores AI feedback.
     *
     * Accepts a JSON body with:
     * - response_id (string, required): The AI response being rated.
     * - rating (int, required): Rating from 1 to 5.
     * - comment (string, optional): Free-text feedback.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The incoming HTTP request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with success status and created entity ID, or error.
     */
    public function submit(Request $request): JsonResponse
    {
        // Parse JSON body.
        $content = $request->getContent();
        $data = json_decode($content, TRUE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Invalid JSON in request body.',
            ], 400);
        }

        // Validate required fields.
        $responseId = $data['response_id'] ?? '';
        if (empty($responseId)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'The field "response_id" is required.',
            ], 400);
        }

        $rating = $data['rating'] ?? NULL;
        if ($rating === NULL || !is_numeric($rating)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'The field "rating" is required and must be numeric.',
            ], 400);
        }

        $rating = (int) $rating;
        if ($rating < 1 || $rating > 5) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'The field "rating" must be between 1 and 5.',
            ], 400);
        }

        $comment = $data['comment'] ?? NULL;

        try {
            $storage = $this->entityTypeManager->getStorage('ai_feedback');
            $values = [
                'response_id' => $responseId,
                'user_id' => $this->currentUser->id(),
                'rating' => $rating,
            ];

            if ($comment !== NULL && $comment !== '') {
                $values['comment'] = $comment;
            }

            $entity = $storage->create($values);
            $entity->save();

            return new JsonResponse([
                'success' => TRUE,
                'data' => [
                    'id' => (int) $entity->id(),
                    'response_id' => $responseId,
                    'rating' => $rating,
                ],
            ], 201);
        }
        catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Failed to save feedback.',
            ], 500);
        }
    }

}
