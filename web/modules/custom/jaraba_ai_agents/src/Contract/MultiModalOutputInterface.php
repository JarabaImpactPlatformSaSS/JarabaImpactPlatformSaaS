<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Contract;

/**
 * Interface for multi-modal output generation (F11).
 *
 * Defines the contract for voice synthesis and image generation
 * capabilities. Implementations will connect to providers like
 * OpenAI TTS, ElevenLabs, DALL-E, or Stable Diffusion.
 */
interface MultiModalOutputInterface {

  /**
   * Synthesizes speech from text.
   *
   * @param string $text
   *   Text to synthesize.
   * @param string $voice
   *   Voice identifier (provider-specific).
   * @param array $options
   *   Provider-specific options (e.g. speed, format, language).
   *
   * @return array
   *   Result with: audio_uri (string), format (string), duration_ms (int),
   *   size_bytes (int).
   *
   * @throws \Drupal\jaraba_ai_agents\Exception\MultiModalNotAvailableException
   *   If the provider is not configured.
   */
  public function synthesizeSpeech(string $text, string $voice = 'default', array $options = []): array;

  /**
   * Generates an image from a text prompt.
   *
   * @param string $prompt
   *   Description of the image to generate.
   * @param array $options
   *   Options: size ('1024x1024'), style ('natural'|'vivid'),
   *   quality ('standard'|'hd'), n (1-4).
   *
   * @return array
   *   Result with: image_uri (string), revised_prompt (string),
   *   size (string), format (string).
   *
   * @throws \Drupal\jaraba_ai_agents\Exception\MultiModalNotAvailableException
   *   If the provider is not configured.
   */
  public function generateImage(string $prompt, array $options = []): array;

  /**
   * Checks if multi-modal output is available.
   *
   * @return array
   *   Status with: speech (bool), image (bool), provider (string).
   */
  public function getOutputCapabilities(): array;

}
