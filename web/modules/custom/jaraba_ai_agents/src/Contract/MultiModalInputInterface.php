<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Contract;

/**
 * Interface for multi-modal input processing (F11).
 *
 * Defines the contract for voice transcription and image analysis
 * capabilities. Implementations will connect to providers like
 * OpenAI Whisper, Google Speech-to-Text, or Claude Vision.
 */
interface MultiModalInputInterface {

  /**
   * Transcribes audio to text.
   *
   * @param string $audioUri
   *   URI of the audio file (public:// or private://).
   * @param string $language
   *   ISO 639-1 language code (default: 'es').
   * @param array $options
   *   Provider-specific options (e.g. model, temperature).
   *
   * @return array
   *   Result with: text (string), confidence (float 0-1), language, duration_ms.
   *
   * @throws \Drupal\jaraba_ai_agents\Exception\MultiModalNotAvailableException
   *   If the provider is not configured.
   */
  public function transcribeAudio(string $audioUri, string $language = 'es', array $options = []): array;

  /**
   * Analyzes an image and returns structured description.
   *
   * @param string $imageUri
   *   URI of the image file.
   * @param string $prompt
   *   Analysis prompt (e.g. 'Describe this product for an online store').
   * @param array $options
   *   Provider-specific options (e.g. detail level).
   *
   * @return array
   *   Result with: description (string), tags (array), objects (array),
   *   colors (array), confidence (float 0-1).
   *
   * @throws \Drupal\jaraba_ai_agents\Exception\MultiModalNotAvailableException
   *   If the provider is not configured.
   */
  public function analyzeImage(string $imageUri, string $prompt = '', array $options = []): array;

  /**
   * Checks if multi-modal input is available.
   *
   * @return array
   *   Status with: audio (bool), image (bool), provider (string).
   */
  public function getInputCapabilities(): array;

}
