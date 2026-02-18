<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Psr\Log\LoggerInterface;

/**
 * Motor de Análisis de Sentimiento.
 *
 * Evalúa texto para determinar la polaridad emocional (Positiva, Neutra, Negativa).
 *
 * F194 — Sentiment AI.
 */
class SentimentEngineService {

  protected array $positiveWords = ['excelente', 'bueno', 'genial', 'feliz', 'gracias', 'rápido', 'eficaz', 'encanta'];
  protected array $negativeWords = ['malo', 'lento', 'error', 'fallo', 'terrible', 'odio', 'caro', 'inútil'];

  public function __construct(
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Analiza el sentimiento de un texto.
   *
   * @param string $text
   *   El texto a analizar.
   *
   * @return array
   *   [
   *     'score' => float (-1.0 a 1.0),
   *     'label' => string (positive, neutral, negative),
   *     'confidence' => float (0.0 a 1.0)
   *   ]
   */
  public function analyze(string $text): array {
    $text = mb_strtolower($text);
    $score = 0;
    $matches = 0;

    foreach ($this->positiveWords as $word) {
      if (str_contains($text, $word)) {
        $score += 1;
        $matches++;
      }
    }

    foreach ($this->negativeWords as $word) {
      if (str_contains($text, $word)) {
        $score -= 1;
        $matches++;
      }
    }

    // Normalizar score entre -1 y 1
    $normalizedScore = $matches > 0 ? $score / $matches : 0;

    $label = 'neutral';
    if ($normalizedScore > 0.3) $label = 'positive';
    if ($normalizedScore < -0.3) $label = 'negative';

    return [
      'score' => $normalizedScore,
      'label' => $label,
      'confidence' => $matches > 0 ? 0.8 : 0.1, // Baja confianza si no hay keywords.
    ];
  }

}
