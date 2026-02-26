<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * GAP-AUD-012: Serves the Agent Card at /.well-known/agent.json.
 *
 * Implements the A2A (Agent-to-Agent) discovery protocol.
 * External agents can discover this agent's capabilities via this endpoint.
 */
class AgentCardController extends ControllerBase {

  /**
   * GET /.well-known/agent.json — Agent Card discovery.
   */
  public function agentCard(Request $request): JsonResponse {
    $baseUrl = $request->getSchemeAndHttpHost();

    $card = [
      'name' => 'Jaraba Impact Platform AI',
      'description' => 'Multi-vertical SaaS AI agent system for impact ecosystems. Supports marketing, storytelling, customer experience, support, sales, and domain-specific AI actions.',
      'protocol' => 'a2a/1.0',
      'version' => '1.0.0',
      'url' => $baseUrl,
      'endpoints' => [
        'task_submit' => $baseUrl . '/api/v1/a2a/tasks/send',
        'task_status' => $baseUrl . '/api/v1/a2a/tasks/{task_id}',
        'task_cancel' => $baseUrl . '/api/v1/a2a/tasks/{task_id}/cancel',
      ],
      'authentication' => [
        'type' => 'bearer',
        'header' => 'Authorization',
        'optional_hmac' => [
          'header' => 'X-Signature',
          'algorithm' => 'HMAC-SHA256',
        ],
      ],
      'rate_limit' => [
        'requests_per_hour' => 100,
        'burst' => 10,
      ],
      'capabilities' => [
        'actions' => [
          'generate_content' => [
            'description' => 'Generate marketing or editorial content',
            'input' => ['prompt', 'tone', 'vertical', 'language'],
            'output' => ['content', 'metadata'],
          ],
          'analyze_sentiment' => [
            'description' => 'Analyze sentiment of text content',
            'input' => ['text', 'language'],
            'output' => ['sentiment', 'score', 'aspects'],
          ],
          'seo_suggestions' => [
            'description' => 'Generate SEO optimization suggestions',
            'input' => ['content', 'keyword', 'url'],
            'output' => ['suggestions', 'score'],
          ],
          'brand_voice_check' => [
            'description' => 'Check content alignment with brand voice',
            'input' => ['text', 'tenant_id'],
            'output' => ['alignment_score', 'suggestions'],
          ],
          'skill_inference' => [
            'description' => 'Extract skills from unstructured text (CV, profile)',
            'input' => ['text', 'language'],
            'output' => ['skills', 'confidence'],
          ],
        ],
        'verticals' => [
          'empleabilidad', 'emprendimiento', 'comercioconecta',
          'agroconecta', 'jarabalex', 'serviciosconecta',
          'formacion', 'jaraba_content_hub',
        ],
        'languages' => ['es', 'en'],
        'multimodal' => [
          'audio_input' => TRUE,
          'image_input' => TRUE,
        ],
      ],
    ];

    return new JsonResponse($card, 200, [
      'Cache-Control' => 'public, max-age=3600',
    ]);
  }

}
