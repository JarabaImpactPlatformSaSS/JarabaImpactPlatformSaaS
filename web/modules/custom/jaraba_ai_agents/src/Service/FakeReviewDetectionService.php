<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de deteccion de resenas falsas.
 *
 * Analiza patrones textuales, metadata y comportamiento del usuario
 * para scoring de autenticidad. Usa ModelRouterService (fast tier)
 * para analisis via IA cuando esta disponible.
 *
 * B-07: Fake Review Detection.
 */
class FakeReviewDetectionService {

  /**
   * Threshold por defecto para flag automatico.
   */
  private const DEFAULT_THRESHOLD = 0.7;

  /**
   * Patrones sospechosos en texto (regex).
   */
  private const SUSPICIOUS_PATTERNS = [
    '/\b(best|amazing|incredible|perfect|worst|terrible|horrible)\b/i',
    '/(.)\1{4,}/',
    '/\b(buy|click|visit|www\.|https?:\/\/)\b/i',
    '/[A-Z]{10,}/',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly ?ModelRouterService $modelRouter = NULL,
  ) {}

  /**
   * Analiza una resena y retorna un score de autenticidad.
   *
   * @param \Drupal\Core\Entity\EntityInterface $reviewEntity
   *   La entidad de resena.
   *
   * @return array
   *   ['score' => float 0-1, 'flags' => string[], 'should_flag' => bool,
   *    'confidence' => string]
   */
  public function analyze(EntityInterface $reviewEntity): array {
    $flags = [];
    $score = 0.0;

    // 1. Analisis de texto.
    $body = $this->extractBody($reviewEntity);
    $textFlags = $this->analyzeText($body);
    $flags = array_merge($flags, $textFlags);
    $score += count($textFlags) * 0.15;

    // 2. Analisis de metadata del usuario.
    $metaFlags = $this->analyzeUserMetadata($reviewEntity);
    $flags = array_merge($flags, $metaFlags);
    $score += count($metaFlags) * 0.2;

    // 3. Analisis de rating extremo.
    $ratingFlags = $this->analyzeRatingPattern($reviewEntity);
    $flags = array_merge($flags, $ratingFlags);
    $score += count($ratingFlags) * 0.1;

    // 4. Duplicacion.
    $dupFlags = $this->checkDuplication($reviewEntity, $body);
    $flags = array_merge($flags, $dupFlags);
    $score += count($dupFlags) * 0.25;

    // 5. Analisis IA si disponible.
    if ($this->modelRouter !== NULL && $body !== '') {
      $aiResult = $this->analyzeWithAi($body);
      if ($aiResult !== NULL) {
        $score = ($score + $aiResult['score']) / 2;
        $flags = array_merge($flags, $aiResult['flags'] ?? []);
      }
    }

    $score = min(1.0, $score);

    return [
      'score' => round($score, 3),
      'flags' => array_unique($flags),
      'should_flag' => $score >= self::DEFAULT_THRESHOLD,
      'confidence' => $this->modelRouter !== NULL ? 'ai_enhanced' : 'heuristic_only',
    ];
  }

  /**
   * Analiza texto buscando patrones sospechosos.
   */
  protected function analyzeText(string $body): array {
    $flags = [];
    if ($body === '') {
      return $flags;
    }

    // Texto muy corto (<10 chars) o muy largo (>5000 chars).
    if (strlen($body) < 10) {
      $flags[] = 'text_too_short';
    }
    if (strlen($body) > 5000) {
      $flags[] = 'text_too_long';
    }

    // Patrones sospechosos.
    foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
      if (preg_match($pattern, $body)) {
        $flags[] = 'suspicious_pattern';
        break;
      }
    }

    // Repeticion excesiva de caracteres.
    if (preg_match('/(.{3,})\1{2,}/', $body)) {
      $flags[] = 'repetitive_text';
    }

    // Solo mayusculas.
    $upperRatio = strlen(preg_replace('/[^A-Z]/', '', $body)) / max(1, strlen($body));
    if ($upperRatio > 0.5 && strlen($body) > 20) {
      $flags[] = 'excessive_caps';
    }

    return $flags;
  }

  /**
   * Analiza metadata del usuario.
   */
  protected function analyzeUserMetadata(EntityInterface $reviewEntity): array {
    $flags = [];

    if (!$reviewEntity->hasField('uid')) {
      return $flags;
    }

    try {
      $uid = (int) ($reviewEntity->get('uid')->target_id ?? 0);
      if ($uid === 0) {
        $flags[] = 'anonymous_review';
        return $flags;
      }

      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      if ($user === NULL) {
        $flags[] = 'deleted_user';
        return $flags;
      }

      // Cuenta nueva (< 24 horas).
      $accountAge = time() - (int) $user->getCreatedTime();
      if ($accountAge < 86400) {
        $flags[] = 'new_account';
      }

      // Verificar si tiene email verificado (activo).
      if (!$user->isActive()) {
        $flags[] = 'inactive_account';
      }
    }
    catch (\Exception) {
      // Silenciar errores de carga.
    }

    return $flags;
  }

  /**
   * Analiza patron de rating extremo.
   */
  protected function analyzeRatingPattern(EntityInterface $reviewEntity): array {
    $flags = [];
    $rating = 0;

    if ($reviewEntity->hasField('rating')) {
      $rating = (int) ($reviewEntity->get('rating')->value ?? 0);
    }
    elseif ($reviewEntity->hasField('overall_rating')) {
      $rating = (int) ($reviewEntity->get('overall_rating')->value ?? 0);
    }

    // Rating extremo (1 o 5) con texto corto.
    if (($rating === 1 || $rating === 5)) {
      $body = $this->extractBody($reviewEntity);
      if (strlen($body) < 20) {
        $flags[] = 'extreme_rating_short_text';
      }
    }

    return $flags;
  }

  /**
   * Verifica duplicacion de contenido.
   */
  protected function checkDuplication(EntityInterface $reviewEntity, string $body): array {
    if ($body === '' || strlen($body) < 20) {
      return [];
    }

    $bodyHash = md5(strtolower(trim($body)));
    $entityType = $reviewEntity->getEntityTypeId();

    try {
      $storage = $this->entityTypeManager->getStorage($entityType);
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('id', $reviewEntity->id() ?? 0, '<>')
        ->range(0, 100)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $other) {
        $otherBody = $this->extractBody($other);
        if ($otherBody !== '' && md5(strtolower(trim($otherBody))) === $bodyHash) {
          return ['duplicate_content'];
        }
      }
    }
    catch (\Exception) {
      // Silenciar.
    }

    return [];
  }

  /**
   * Analisis via IA (fast tier).
   *
   * Usa ModelRouterService para obtener config de modelo y luego
   * llama al AI provider con prompt de clasificacion.
   */
  protected function analyzeWithAi(string $body): ?array {
    if ($this->modelRouter === NULL) {
      return NULL;
    }

    try {
      $prompt = "Analyze this review for authenticity. Rate from 0.0 (genuine) to 1.0 (likely fake). Consider: unnatural language, excessive superlatives, promotional content, repetitive patterns, suspicious brevity. Respond ONLY with JSON: {\"score\": float, \"flags\": [string]}. Review:\n\n" . mb_substr($body, 0, 500);

      $modelConfig = $this->modelRouter->route('classification', $prompt, ['force_tier' => 'fast']);
      $modelId = $modelConfig['model_id'] ?? 'claude-haiku-4-5-20251001';

      // Use Drupal AI provider if available.
      if (!\Drupal::hasService('ai.provider')) {
        return NULL;
      }

      $aiProvider = \Drupal::service('ai.provider');
      $input = new \Drupal\ai\OperationType\Chat\ChatInput([
        new \Drupal\ai\OperationType\Chat\ChatMessage('user', $prompt),
      ]);

      $response = $aiProvider->chat($input, $modelId);
      $text = trim($response->getNormalized()->getText());

      // Extract JSON from response (may contain markdown fences).
      if (preg_match('/\{[^}]+\}/', $text, $matches)) {
        $parsed = json_decode($matches[0], TRUE);
        if (is_array($parsed) && isset($parsed['score'])) {
          return [
            'score' => max(0.0, min(1.0, (float) $parsed['score'])),
            'flags' => is_array($parsed['flags'] ?? NULL) ? $parsed['flags'] : [],
          ];
        }
      }

      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->warning('AI fake review analysis failed: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Extrae el texto del body de una resena.
   */
  protected function extractBody(EntityInterface $entity): string {
    foreach (['body', 'comment', 'review_body'] as $field) {
      if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
        return (string) ($entity->get($field)->value ?? '');
      }
    }
    return '';
  }

}
