<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Psr\Log\LoggerInterface;

/**
 * Brand Voice Trainer — Pipeline Qdrant + feedback loop (F11 Doc 187).
 *
 * Indexes brand voice examples as embeddings in Qdrant, collects human
 * feedback (approve/reject/edit), and refines brand voice configuration
 * based on approved examples.
 */
class BrandVoiceTrainerService {

  use StringTranslationTrait;

  protected const COLLECTION_NAME = 'jaraba_brand_voice';
  protected const VECTOR_DIMENSIONS = 1536;
  protected const EMBEDDING_MODEL = 'text-embedding-3-small';
  protected const SIMILARITY_THRESHOLD = 0.75;

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AiProviderPluginManager $aiProvider,
    protected TenantBrandVoiceService $brandVoice,
    protected AIObservabilityService $observability,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Indexes a brand voice example into Qdrant.
   *
   * @param int $tenantId
   *   Tenant ID.
   * @param string $text
   *   Example text that matches the desired brand voice.
   * @param string $context
   *   Context of the example (e.g. 'social_post', 'email', 'chat').
   * @param string $quality
   *   Quality rating: 'excellent', 'good', 'acceptable'.
   *
   * @return array
   *   Result with point_id and success status.
   */
  public function indexExample(int $tenantId, string $text, string $context = 'general', string $quality = 'good'): array {
    try {
      $qdrant = $this->getQdrantClient();
      if (!$qdrant) {
        return ['success' => FALSE, 'error' => 'Qdrant client not available'];
      }

      $this->ensureCollection($qdrant);

      $embedding = $this->generateEmbedding($text);
      if (empty($embedding)) {
        return ['success' => FALSE, 'error' => 'Failed to generate embedding'];
      }

      $pointId = $qdrant->generatePointId("bv_{$tenantId}_{$context}_" . substr(md5($text), 0, 8));

      $qdrant->upsertPoints([
        [
          'id' => $pointId,
          'vector' => $embedding,
          'payload' => [
            'tenant_id' => $tenantId,
            'text' => $text,
            'context' => $context,
            'quality' => $quality,
            'type' => 'brand_voice_example',
            'indexed_at' => time(),
          ],
        ],
      ], self::COLLECTION_NAME);

      $this->logger->info('Brand voice example indexed for tenant @id (context: @ctx).', [
        '@id' => $tenantId,
        '@ctx' => $context,
      ]);

      // FIX-021: Log observability for embedding generation.
      $this->observability->log([
        'agent_id' => 'brand_voice_trainer',
        'action' => 'index_example',
        'tier' => 'fast',
        'model_id' => self::EMBEDDING_MODEL,
        'provider_id' => 'openai',
        'tenant_id' => $tenantId,
        'vertical' => 'platform',
        'input_tokens' => (int) ceil(mb_strlen($text) / 4),
        'output_tokens' => 0,
        'duration_ms' => 0,
        'success' => TRUE,
      ]);

      return ['success' => TRUE, 'point_id' => $pointId];
    }
    catch (\Exception $e) {
      $this->logger->error('Error indexing brand voice example: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Records feedback on an AI-generated output.
   *
   * @param int $tenantId
   *   Tenant ID.
   * @param string $originalOutput
   *   The AI-generated text.
   * @param string $feedback
   *   Feedback type: 'approve', 'reject', 'edit'.
   * @param string|null $editedText
   *   Corrected text if feedback is 'edit'.
   * @param string $context
   *   Context of the output.
   *
   * @return array
   *   Result with success status.
   */
  public function recordFeedback(int $tenantId, string $originalOutput, string $feedback, ?string $editedText = NULL, string $context = 'general'): array {
    try {
      // Store feedback as entity for audit trail.
      $storage = $this->entityTypeManager->getStorage('ai_agent_execution');

      // If approved or edited, index as a positive example.
      if ($feedback === 'approve') {
        $result = $this->indexExample($tenantId, $originalOutput, $context, 'good');
      }
      elseif ($feedback === 'edit' && $editedText) {
        // The edited version is the gold standard.
        $result = $this->indexExample($tenantId, $editedText, $context, 'excellent');
      }
      else {
        $result = ['success' => TRUE, 'action' => 'rejection_recorded'];
      }

      $this->logger->info('Brand voice feedback recorded: @feedback for tenant @id.', [
        '@feedback' => $feedback,
        '@id' => $tenantId,
      ]);

      return $result + ['feedback' => $feedback];
    }
    catch (\Exception $e) {
      $this->logger->error('Error recording feedback: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Computes brand alignment score for a text against tenant's voice.
   *
   * @param int $tenantId
   *   Tenant ID.
   * @param string $text
   *   Text to evaluate.
   *
   * @return array
   *   Result with score (0.0-1.0), top_matches, and alignment status.
   */
  public function computeAlignmentScore(int $tenantId, string $text): array {
    try {
      $qdrant = $this->getQdrantClient();
      if (!$qdrant) {
        return ['score' => 0.0, 'status' => 'unavailable'];
      }

      $embedding = $this->generateEmbedding($text);
      if (empty($embedding)) {
        return ['score' => 0.0, 'status' => 'embedding_failed'];
      }

      $filter = [
        'must' => [
          ['key' => 'tenant_id', 'match' => ['value' => $tenantId]],
          ['key' => 'type', 'match' => ['value' => 'brand_voice_example']],
        ],
      ];

      $results = $qdrant->vectorSearch(
        $embedding,
        $filter,
        5,
        0.5,
        self::COLLECTION_NAME,
      );

      if (empty($results)) {
        return [
          'score' => 0.0,
          'status' => 'no_examples',
          'message' => (string) $this->t('No hay ejemplos de brand voice indexados para este tenant.'),
        ];
      }

      // Average similarity of top matches.
      $totalScore = 0.0;
      $topMatches = [];
      foreach ($results as $result) {
        $score = $result['score'] ?? 0.0;
        $totalScore += $score;
        $topMatches[] = [
          'score' => round($score, 4),
          'context' => $result['payload']['context'] ?? 'general',
          'quality' => $result['payload']['quality'] ?? 'unknown',
        ];
      }

      $avgScore = $totalScore / count($results);
      $status = $avgScore >= self::SIMILARITY_THRESHOLD ? 'aligned' : 'needs_improvement';

      return [
        'score' => round($avgScore, 4),
        'status' => $status,
        'top_matches' => $topMatches,
        'examples_count' => count($results),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error computing alignment: @error', ['@error' => $e->getMessage()]);
      return ['score' => 0.0, 'status' => 'error', 'error' => $e->getMessage()];
    }
  }

  /**
   * Refines brand voice configuration from approved examples.
   *
   * Uses LLM to analyze approved examples and suggest personality
   * trait updates and new preferred/forbidden terms.
   *
   * @param int $tenantId
   *   Tenant ID.
   *
   * @return array
   *   Suggested updates to brand voice configuration.
   */
  public function refineBrandVoice(int $tenantId): array {
    try {
      $qdrant = $this->getQdrantClient();
      if (!$qdrant) {
        return ['success' => FALSE, 'error' => 'Qdrant client not available'];
      }

      // Retrieve approved examples.
      $filter = [
        'must' => [
          ['key' => 'tenant_id', 'match' => ['value' => $tenantId]],
          ['key' => 'type', 'match' => ['value' => 'brand_voice_example']],
          ['key' => 'quality', 'match' => ['value' => 'excellent']],
        ],
      ];

      // Use a zero-vector search with only filter to get all excellent examples.
      $zeroVector = array_fill(0, self::VECTOR_DIMENSIONS, 0.0);
      $results = $qdrant->vectorSearch($zeroVector, $filter, 20, 0.0, self::COLLECTION_NAME);

      if (count($results) < 3) {
        return [
          'success' => FALSE,
          'error' => (string) $this->t('Se necesitan al menos 3 ejemplos aprobados para refinar la voz de marca.'),
          'current_count' => count($results),
        ];
      }

      // Collect example texts.
      $examples = [];
      foreach ($results as $result) {
        $examples[] = $result['payload']['text'] ?? '';
      }

      // Use LLM to analyze patterns.
      $analysisPrompt = $this->buildAnalysisPrompt($examples);
      $provider = $this->aiProvider->createInstance('openai');

      $chatInput = new ChatInput([
        new ChatMessage('system', 'Eres un experto en analisis de comunicacion de marca. Responde SOLO en JSON valido.'),
        new ChatMessage('user', $analysisPrompt),
      ]);

      $startTime = microtime(TRUE);
      $response = $provider->chat($chatInput, 'gpt-4o-mini', ['temperature' => 0.3]);
      $text = $response->getNormalized()->getText();
      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      $suggestions = json_decode($text, TRUE);
      if (!$suggestions) {
        return ['success' => FALSE, 'error' => 'Failed to parse LLM analysis'];
      }

      $this->logger->info('Brand voice refined for tenant @id from @count examples.', [
        '@id' => $tenantId,
        '@count' => count($examples),
      ]);

      // FIX-021: Log observability for LLM refinement call.
      $this->observability->log([
        'agent_id' => 'brand_voice_trainer',
        'action' => 'refine_brand_voice',
        'tier' => 'fast',
        'model_id' => 'gpt-4o-mini',
        'provider_id' => 'openai',
        'tenant_id' => $tenantId,
        'vertical' => 'platform',
        'input_tokens' => (int) ceil(mb_strlen($analysisPrompt) / 4),
        'output_tokens' => (int) ceil(mb_strlen($text) / 4),
        'duration_ms' => $durationMs,
        'success' => TRUE,
      ]);

      return [
        'success' => TRUE,
        'suggestions' => $suggestions,
        'examples_analyzed' => count($examples),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error refining brand voice: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Gets training stats for a tenant.
   */
  public function getTrainingStats(int $tenantId): array {
    try {
      $qdrant = $this->getQdrantClient();
      if (!$qdrant) {
        return ['total_examples' => 0, 'status' => 'unavailable'];
      }

      $filter = [
        'must' => [
          ['key' => 'tenant_id', 'match' => ['value' => $tenantId]],
          ['key' => 'type', 'match' => ['value' => 'brand_voice_example']],
        ],
      ];

      $zeroVector = array_fill(0, self::VECTOR_DIMENSIONS, 0.0);
      $results = $qdrant->vectorSearch($zeroVector, $filter, 100, 0.0, self::COLLECTION_NAME);

      $byQuality = ['excellent' => 0, 'good' => 0, 'acceptable' => 0];
      $byContext = [];
      foreach ($results as $result) {
        $q = $result['payload']['quality'] ?? 'unknown';
        if (isset($byQuality[$q])) {
          $byQuality[$q]++;
        }
        $ctx = $result['payload']['context'] ?? 'general';
        $byContext[$ctx] = ($byContext[$ctx] ?? 0) + 1;
      }

      return [
        'total_examples' => count($results),
        'by_quality' => $byQuality,
        'by_context' => $byContext,
        'can_refine' => $byQuality['excellent'] >= 3,
        'status' => 'active',
      ];
    }
    catch (\Exception $e) {
      return ['total_examples' => 0, 'status' => 'error'];
    }
  }

  /**
   * Builds the analysis prompt for brand voice refinement.
   */
  protected function buildAnalysisPrompt(array $examples): string {
    $exampleList = '';
    foreach ($examples as $i => $text) {
      $exampleList .= "Ejemplo " . ($i + 1) . ":\n" . $text . "\n\n";
    }

    return <<<PROMPT
Analiza los siguientes ejemplos de comunicacion de marca y extrae patrones:

{$exampleList}

Responde en JSON con esta estructura exacta:
{
  "personality_traits": {
    "formality": <0.0-1.0>,
    "warmth": <0.0-1.0>,
    "confidence": <0.0-1.0>,
    "humor": <0.0-1.0>,
    "technical": <0.0-1.0>
  },
  "preferred_terms": ["termino1", "termino2"],
  "forbidden_terms": ["termino1", "termino2"],
  "tone_description": "descripcion breve del tono",
  "writing_patterns": ["patron1", "patron2"],
  "recommended_archetype": "professional|artisan|innovative|friendly|expert|playful|luxury|eco"
}
PROMPT;
  }

  /**
   * Gets the Qdrant client if available.
   */
  protected function getQdrantClient(): ?object {
    if (\Drupal::hasService('jaraba_rag.qdrant_client')) {
      return \Drupal::service('jaraba_rag.qdrant_client');
    }
    return NULL;
  }

  /**
   * Ensures the brand voice collection exists in Qdrant.
   */
  protected function ensureCollection(object $qdrant): void {
    $qdrant->ensureCollection(self::COLLECTION_NAME, self::VECTOR_DIMENSIONS);
  }

  /**
   * Generates an embedding vector for text.
   */
  protected function generateEmbedding(string $text): array {
    try {
      $provider = $this->aiProvider->createInstance('openai');
      $result = $provider->embeddings($text, self::EMBEDDING_MODEL);
      if ($result && method_exists($result, 'getNormalized')) {
        $vector = $result->getNormalized();
        if (!empty($vector) && is_array($vector)) {
          return $vector;
        }
      }
      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('Embedding generation failed: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

}
