<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * B-12: Auto-translate reviews.
 *
 * Translates review body text to the site's configured languages
 * using optional AI translation service. Falls back to storing
 * the original language.
 */
class ReviewTranslationService {

  /**
   * Supported target languages.
   */
  private const SUPPORTED_LANGUAGES = ['es', 'en', 'pt-br'];

  /**
   * Body fields by entity type.
   */
  private const BODY_FIELDS = ['body', 'comment', 'review_body'];

  /**
   * Language labels for prompts.
   */
  private const LANGUAGE_LABELS = [
    'es' => 'Spanish',
    'en' => 'English',
    'pt-br' => 'Brazilian Portuguese',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly mixed $modelRouter = NULL,
    protected readonly mixed $aiProvider = NULL,
  ) {}

  /**
   * Translate a review to the target language.
   *
   * @param \Drupal\Core\Entity\EntityInterface $reviewEntity
   *   The review entity.
   * @param string $targetLanguage
   *   Target language code.
   *
   * @return array
   *   ['translated_text' => string, 'source_language' => string,
   *    'target_language' => string, 'method' => string]
   */
  public function translate(EntityInterface $reviewEntity, string $targetLanguage): array {
    $body = $this->extractBody($reviewEntity);
    if ($body === '') {
      return [
        'translated_text' => '',
        'source_language' => 'unknown',
        'target_language' => $targetLanguage,
        'method' => 'empty',
      ];
    }

    $sourceLanguage = $this->detectLanguage($body);

    // Skip if already in target language.
    if ($sourceLanguage === $targetLanguage) {
      return [
        'translated_text' => $body,
        'source_language' => $sourceLanguage,
        'target_language' => $targetLanguage,
        'method' => 'same_language',
      ];
    }

    // Try AI translation if available (PRESAVE-RESILIENCE-001).
    if ($this->modelRouter !== NULL && $this->aiProvider !== NULL) {
      try {
        $translated = $this->translateWithAi($body, $sourceLanguage, $targetLanguage);
        if ($translated !== NULL) {
          return [
            'translated_text' => $translated,
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'method' => 'ai',
          ];
        }
      }
      catch (\Exception $e) {
        $this->logger->warning('AI translation failed: @msg', ['@msg' => $e->getMessage()]);
      }
    }

    // Fallback: return original with language tag.
    return [
      'translated_text' => $body,
      'source_language' => $sourceLanguage,
      'target_language' => $targetLanguage,
      'method' => 'fallback',
    ];
  }

  /**
   * Detect the language of the text.
   *
   * Simple heuristic based on common words per language.
   */
  public function detectLanguage(string $text): string {
    $lower = mb_strtolower($text);

    $esWords = ['el', 'la', 'los', 'las', 'de', 'en', 'es', 'un', 'una', 'que', 'muy', 'pero', 'como', 'por', 'para'];
    $enWords = ['the', 'is', 'are', 'was', 'were', 'and', 'but', 'for', 'not', 'you', 'all', 'can', 'have', 'this', 'with'];
    $ptWords = ['o', 'a', 'os', 'as', 'de', 'em', 'um', 'uma', 'que', 'muito', 'mas', 'como', 'por', 'para', 'nao'];

    $esCount = $this->countWordMatches($lower, $esWords);
    $enCount = $this->countWordMatches($lower, $enWords);
    $ptCount = $this->countWordMatches($lower, $ptWords);

    $max = max($esCount, $enCount, $ptCount);

    if ($max === 0) {
      return 'es';
    }

    if ($esCount === $max) {
      return 'es';
    }
    if ($enCount === $max) {
      return 'en';
    }
    return 'pt-br';
  }

  /**
   * Count word matches.
   */
  protected function countWordMatches(string $text, array $words): int {
    $count = 0;
    foreach ($words as $word) {
      $count += substr_count($text, ' ' . $word . ' ');
    }
    return $count;
  }

  /**
   * Translate using AI via ModelRouterService + AI provider.
   */
  protected function translateWithAi(string $text, string $from, string $to): ?string {
    if ($this->modelRouter === NULL || $this->aiProvider === NULL) {
      return NULL;
    }

    try {
      $fromLabel = self::LANGUAGE_LABELS[$from] ?? $from;
      $toLabel = self::LANGUAGE_LABELS[$to] ?? $to;
      $prompt = "Translate this review from {$fromLabel} to {$toLabel}. Output ONLY the translated text, nothing else:\n\n{$text}";

      $modelConfig = $this->modelRouter->route('translation', $prompt, ['force_tier' => 'fast']);
      $modelId = $modelConfig['model_id'] ?? 'claude-haiku-4-5-20251001';

      $input = new \Drupal\ai\OperationType\Chat\ChatInput([
        new \Drupal\ai\OperationType\Chat\ChatMessage('user', $prompt),
      ]);

      $response = $this->aiProvider->chat($input, $modelId);
      $translated = trim($response->getNormalized()->getText());

      return $translated !== '' ? $translated : NULL;
    }
    catch (\Exception $e) {
      $this->logger->warning('AI translation failed from @from to @to: @msg', [
        '@from' => $from,
        '@to' => $to,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Extract body text from a review entity.
   */
  protected function extractBody(EntityInterface $entity): string {
    foreach (self::BODY_FIELDS as $field) {
      if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
        return (string) ($entity->get($field)->value ?? '');
      }
    }
    return '';
  }

}
