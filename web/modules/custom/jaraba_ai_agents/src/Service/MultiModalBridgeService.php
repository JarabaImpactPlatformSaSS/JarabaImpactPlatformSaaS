<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\jaraba_ai_agents\Contract\MultiModalInputInterface;
use Drupal\jaraba_ai_agents\Contract\MultiModalOutputInterface;
use Drupal\jaraba_ai_agents\Exception\MultiModalNotAvailableException;
use Psr\Log\LoggerInterface;

/**
 * Multi-Modal Bridge — Stub service for future voice/image (F11).
 *
 * Implements both input and output interfaces with informative
 * placeholders. When providers are configured, this service will
 * delegate to the appropriate backend (OpenAI, ElevenLabs, etc.).
 */
class MultiModalBridgeService implements MultiModalInputInterface, MultiModalOutputInterface {

  /**
   * Constructor.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function transcribeAudio(string $audioUri, string $language = 'es', array $options = []): array {
    $this->logger->notice('Multi-modal transcription requested but not yet configured.');
    throw new MultiModalNotAvailableException('audio_transcription');
  }

  /**
   * {@inheritdoc}
   */
  public function analyzeImage(string $imageUri, string $prompt = '', array $options = []): array {
    $this->logger->notice('Multi-modal image analysis requested but not yet configured.');
    throw new MultiModalNotAvailableException('image_analysis');
  }

  /**
   * {@inheritdoc}
   */
  public function getInputCapabilities(): array {
    return [
      'audio' => FALSE,
      'image' => FALSE,
      'provider' => 'none',
      'status' => 'placeholder',
      'message' => 'Multi-modal input will be available in a future release. Planned providers: OpenAI Whisper (audio), Claude Vision / GPT-4o (image).',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function synthesizeSpeech(string $text, string $voice = 'default', array $options = []): array {
    $this->logger->notice('Multi-modal speech synthesis requested but not yet configured.');
    throw new MultiModalNotAvailableException('speech_synthesis');
  }

  /**
   * {@inheritdoc}
   */
  public function generateImage(string $prompt, array $options = []): array {
    $this->logger->notice('Multi-modal image generation requested but not yet configured.');
    throw new MultiModalNotAvailableException('image_generation');
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputCapabilities(): array {
    return [
      'speech' => FALSE,
      'image' => FALSE,
      'provider' => 'none',
      'status' => 'placeholder',
      'message' => 'Multi-modal output will be available in a future release. Planned providers: OpenAI TTS / ElevenLabs (speech), DALL-E 3 / Stable Diffusion (image).',
    ];
  }

}
