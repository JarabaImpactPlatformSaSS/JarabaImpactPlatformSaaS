<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de analisis de sentimiento para resenas.
 *
 * Clasifica resenas como positive/neutral/negative usando
 * heuristicas de texto y opcionalmente IA.
 *
 * B-09: AI Sentiment Overlay.
 */
class ReviewSentimentService {

  /**
   * Palabras positivas en espanol e ingles.
   */
  private const POSITIVE_WORDS = [
    'excelente', 'genial', 'perfecto', 'fantastico', 'increible', 'maravilloso',
    'recomiendo', 'recomendable', 'satisfecho', 'encanta', 'rapido', 'profesional',
    'calidad', 'amable', 'puntual', 'eficiente', 'bueno', 'mejor', 'super',
    'excellent', 'great', 'perfect', 'amazing', 'wonderful', 'recommend',
    'satisfied', 'love', 'fast', 'professional', 'quality', 'friendly', 'best',
  ];

  /**
   * Palabras negativas en espanol e ingles.
   */
  private const NEGATIVE_WORDS = [
    'malo', 'pesimo', 'terrible', 'horrible', 'desastre', 'decepcion',
    'decepcionante', 'lento', 'caro', 'estafa', 'engano', 'peor', 'nunca',
    'problema', 'queja', 'devolucion', 'impuntual', 'grosero',
    'bad', 'worst', 'terrible', 'horrible', 'disaster', 'disappointing',
    'slow', 'expensive', 'scam', 'fraud', 'never', 'problem', 'complaint',
    'refund', 'rude', 'awful', 'poor',
  ];

  public function __construct(
    protected readonly LoggerInterface $logger,
    protected readonly mixed $modelRouter = NULL,
    protected readonly mixed $aiProvider = NULL,
  ) {}

  /**
   * Analiza el sentimiento de una resena.
   *
   * @param \Drupal\Core\Entity\EntityInterface $reviewEntity
   *   La entidad de resena.
   *
   * @return array
   *   ['sentiment' => 'positive'|'neutral'|'negative',
   *    'confidence' => float 0-1, 'method' => string]
   */
  public function analyze(EntityInterface $reviewEntity): array {
    $body = $this->extractBody($reviewEntity);
    $rating = $this->extractRating($reviewEntity);

    // Combinar analisis de texto y rating (heuristic).
    $textSentiment = $this->analyzeText($body);
    $ratingSentiment = $this->analyzeRating($rating);

    // Ponderar: rating tiene mas peso que texto.
    $heuristicScore = ($textSentiment['score'] * 0.4) + ($ratingSentiment['score'] * 0.6);

    // Try AI sentiment analysis if available and body is substantial.
    $aiResult = NULL;
    if ($this->modelRouter !== NULL && $this->aiProvider !== NULL && mb_strlen($body) > 50) {
      $aiResult = $this->analyzeWithAi($body);
    }

    // Blend AI and heuristic scores.
    if ($aiResult !== NULL) {
      $finalScore = ($aiResult['score'] * 0.7) + ($heuristicScore * 0.3);
      $method = 'ai_blended';
      $aspects = $aiResult['aspects'] ?? [];
    }
    else {
      $finalScore = $heuristicScore;
      $method = 'heuristic';
      $aspects = [];
    }

    $sentiment = 'neutral';
    if ($finalScore > 0.2) {
      $sentiment = 'positive';
    }
    elseif ($finalScore < -0.2) {
      $sentiment = 'negative';
    }

    $result = [
      'sentiment' => $sentiment,
      'confidence' => round(abs($finalScore), 2),
      'method' => $method,
    ];

    if (!empty($aspects)) {
      $result['aspects'] = $aspects;
    }

    return $result;
  }

  /**
   * Analiza texto buscando palabras de sentimiento.
   */
  protected function analyzeText(string $body): array {
    if ($body === '') {
      return ['score' => 0.0];
    }

    $lower = mb_strtolower($body);
    $words = preg_split('/\s+/', $lower);
    $total = count($words);

    if ($total === 0) {
      return ['score' => 0.0];
    }

    $positive = 0;
    $negative = 0;

    foreach ($words as $word) {
      $clean = preg_replace('/[^a-záéíóúüñ]/', '', $word);
      if (in_array($clean, self::POSITIVE_WORDS, TRUE)) {
        $positive++;
      }
      if (in_array($clean, self::NEGATIVE_WORDS, TRUE)) {
        $negative++;
      }
    }

    $score = ($positive - $negative) / max(1, $total) * 10;
    return ['score' => max(-1.0, min(1.0, $score))];
  }

  /**
   * Analiza sentimiento por rating.
   */
  protected function analyzeRating(int $rating): array {
    if ($rating === 0) {
      return ['score' => 0.0];
    }

    // 1-2 = negativo, 3 = neutral, 4-5 = positivo.
    $map = [1 => -1.0, 2 => -0.5, 3 => 0.0, 4 => 0.5, 5 => 1.0];
    return ['score' => $map[$rating] ?? 0.0];
  }

  /**
   * Analyze sentiment using AI (fast tier).
   *
   * @return array|null
   *   ['score' => float (-1 to 1), 'aspects' => array] or NULL on failure.
   */
  protected function analyzeWithAi(string $body): ?array {
    try {
      $prompt = "Analyze the sentiment of this review. Respond ONLY with JSON:\n"
        . "{\"sentiment\": \"positive\"|\"neutral\"|\"negative\", \"score\": float (-1.0 to 1.0), \"aspects\": [{\"aspect\": string, \"sentiment\": string}]}\n\n"
        . "Review:\n" . mb_substr($body, 0, 1000);

      $modelConfig = $this->modelRouter->route('classification', $prompt, ['force_tier' => 'fast']);
      $modelId = $modelConfig['model_id'] ?? 'claude-haiku-4-5-20251001';

      $input = new \Drupal\ai\OperationType\Chat\ChatInput([
        new \Drupal\ai\OperationType\Chat\ChatMessage('user', $prompt),
      ]);

      $response = $this->aiProvider->chat($input, $modelId);
      $text = trim($response->getNormalized()->getText());

      // Extract JSON.
      if (preg_match('/\{.*\}/s', $text, $matches)) {
        $parsed = json_decode($matches[0], TRUE);
        if (is_array($parsed) && isset($parsed['score'])) {
          return [
            'score' => max(-1.0, min(1.0, (float) $parsed['score'])),
            'aspects' => is_array($parsed['aspects'] ?? NULL) ? $parsed['aspects'] : [],
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('AI sentiment analysis failed: @msg', ['@msg' => $e->getMessage()]);
    }

    return NULL;
  }

  /**
   * Extrae texto del body.
   */
  protected function extractBody(EntityInterface $entity): string {
    foreach (['body', 'comment', 'review_body'] as $field) {
      if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
        return (string) ($entity->get($field)->value ?? '');
      }
    }
    return '';
  }

  /**
   * Extrae rating numerico.
   */
  protected function extractRating(EntityInterface $entity): int {
    if ($entity->hasField('rating')) {
      return max(0, min(5, (int) ($entity->get('rating')->value ?? 0)));
    }
    if ($entity->hasField('overall_rating')) {
      return max(0, min(5, (int) ($entity->get('overall_rating')->value ?? 0)));
    }
    return 0;
  }

}
