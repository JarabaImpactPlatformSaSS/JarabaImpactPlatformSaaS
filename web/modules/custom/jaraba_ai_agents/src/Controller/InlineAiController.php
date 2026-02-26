<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_ai_agents\Service\InlineAiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Inline AI suggestion API.
 *
 * GAP-AUD-009: POST /api/v1/inline-ai/suggest
 * Receives field name, current value, and entity type, returns AI suggestions.
 *
 * @see CSRF-API-001
 * @see INLINE-AI-001
 */
class InlineAiController extends ControllerBase implements ContainerInjectionInterface
{

    public function __construct(
        protected readonly InlineAiService $inlineAiService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_ai_agents.inline_ai'),
        );
    }

    /**
     * Handles POST /api/v1/inline-ai/suggest.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The HTTP request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with suggestions array.
     */
    public function suggest(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE) ?: [];

        $field = $data['field'] ?? '';
        $entityType = $data['entity_type'] ?? '';

        if (empty($field) || empty($entityType)) {
            return new JsonResponse([
                'error' => 'Missing required parameters: field, entity_type',
            ], 400);
        }

        $value = $data['value'] ?? '';
        $context = $data['context'] ?? [];

        $result = $this->inlineAiService->suggest($field, $value, $entityType, $context);

        return new JsonResponse($result);
    }

}
