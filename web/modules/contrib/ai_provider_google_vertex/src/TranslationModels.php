<?php

namespace Drupal\ai_provider_google_vertex;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Models that can be used by Vertex API for the text translation.
 *
 * There are 2 models currently: Translation LLM (TLLM) and
 * Neural Machine Translation (NMT).
 *
 * They are more than just "model_id" strings in generic provider meaning,
 * because they use different endpoints, payload and response structure,
 * but from the other side, do not contain any "sophisticated" logic.
 *
 * Enum seemed like an appropriate language construct for only 2 models.
 * In the future, if the number of different logics supported by Vertex API
 * grows, consider splitting a single Enum file into per-logic plugin classes.
 */
enum TranslationModels: string {

  // Translation LLM model.
  case Tllm = 'TLLM';

  // Neural machine translation model.
  case Nmt = 'NMT';

  /**
   * Get label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label.
   */
  public function getLabel() : TranslatableMarkup {
    return match ($this) {
      self::Tllm => new TranslatableMarkup('Translation LLM'),
      self::Nmt => new TranslatableMarkup('Neural Machine Translation'),
    };
  }

  /**
   * Get translation endpoint URL.
   *
   * @param array $info
   *   Model information.
   *
   * @return string
   *   Endpoint URL.
   */
  public function getUrl(array $info) : string {
    return match($this) {
      self::Tllm => sprintf('https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/'
        . 'google/models/cloud-translate-text:predict', $info['location'], $info['project_id'], $info['location']),
      self::Nmt => sprintf('https://translation.googleapis.com/v3/projects/%s:translateText',
        $info['project_id']),
    };
  }

  /**
   * Build a payload for language translation requests.
   *
   * @param array $info
   *   Model information. Allowed keys:
   *   - 'project_id': The project identifier as a string.
   *   - 'location': The location of the resource (ex: europe-west4).
   *   - 'text': The text to be translated.
   *   - 'source_language': The source language code.
   *   - 'target_language': The target language code.
   *
   * @return array
   *   The formatted payload for translation.
   */
  public function buildPayload(array $info) : array {
    if ($this === self::Nmt) {
      return [
        'parent' => sprintf('projects/%s', $info['project_id']),
        'contents' => [$info['text']],
        'mimeType' => 'text/html',
        'sourceLanguageCode' => $info['source_language'],
        'targetLanguageCode' => $info['target_language'],
      ];
    }
    return [
      'instances' => [
        [
          'source_language_code' => $info['source_language'],
          'target_language_code' => $info['target_language'],
          'contents' => [$info['text']],
          'mimeType' => 'text/html',
          'model' => sprintf('projects/%s/locations/%s/models/general/translation-llm',
            $info['project_id'], $info['location']),
        ],
      ],
    ];
  }

  /**
   * Get translation text from the response.
   *
   * @param array $json
   *   Response from the translation endpoint.
   *
   * @return string
   *   Translated string.
   */
  public function extractTranslation(array $json) : string {
    if ($this === self::Nmt) {
      return $json['translations'][0]['translatedText'];
    }
    // Process the response.
    if (!empty($json['predictions']) && is_array($json['predictions'])) {
      foreach ($json['predictions'] as $prediction) {
        if (isset($prediction['translations'][0]['translatedText'])) {
          return $prediction['translations'][0]['translatedText'];
        }
      }
    }
    return '';
  }

}
