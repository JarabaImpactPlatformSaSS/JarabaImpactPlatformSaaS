<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Voice pipeline: STT → AI → TTS.
 *
 * Orchestrates the full voice interaction cycle:
 * 1. Speech-to-Text (Whisper API or provider STT).
 * 2. AI agent execution via CopilotOrchestrator.
 * 3. Text-to-Speech for audio response.
 *
 * Feature-flagged: requires `jaraba_ai_agents.voice.enabled = TRUE`.
 * Graceful degradation: if voice APIs are unavailable, returns text-only.
 *
 * @see \Drupal\jaraba_ai_agents\Controller\VoiceCopilotController
 */
class VoicePipelineService {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?object $aiProvider = NULL,
    protected readonly ?ModelRouterService $modelRouter = NULL,
    protected readonly ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Checks if voice pipeline is enabled and configured.
   *
   * @return bool
   *   TRUE if voice features are available.
   */
  public function isEnabled(): bool {
    $config = $this->configFactory->get('jaraba_ai_agents.voice');
    return (bool) ($config->get('enabled') ?? FALSE);
  }

  /**
   * Transcribes audio to text (Speech-to-Text).
   *
   * @param string $audioData
   *   Base64-encoded audio data.
   * @param string $mimeType
   *   Audio MIME type (audio/webm, audio/wav, etc.).
   * @param string $language
   *   Language hint (es, en, etc.).
   *
   * @return array{success: bool, text: string, language: string, duration_ms: int, error: string}
   *   Transcription result.
   */
  public function transcribe(string $audioData, string $mimeType = 'audio/webm', string $language = 'es'): array {
    if (!$this->isEnabled()) {
      return [
        'success' => FALSE,
        'text' => '',
        'language' => $language,
        'duration_ms' => 0,
        'error' => 'Voice pipeline is not enabled.',
      ];
    }

    $startTime = microtime(TRUE);

    try {
      $config = $this->configFactory->get('jaraba_ai_agents.voice');
      $sttProvider = $config->get('stt_provider') ?? 'openai_whisper';

      $text = $this->executeSTT($audioData, $mimeType, $language, $sttProvider);
      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      $this->observability?->log([
        'agent_id' => 'voice_pipeline',
        'action' => 'stt',
        'tier' => 'fast',
        'provider_id' => $sttProvider,
        'duration_ms' => $durationMs,
        'success' => TRUE,
      ]);

      return [
        'success' => TRUE,
        'text' => $text,
        'language' => $language,
        'duration_ms' => $durationMs,
        'error' => '',
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('STT failed: @error', ['@error' => $e->getMessage()]);
      return [
        'success' => FALSE,
        'text' => '',
        'language' => $language,
        'duration_ms' => (int) ((microtime(TRUE) - $startTime) * 1000),
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Synthesizes text to speech (Text-to-Speech).
   *
   * @param string $text
   *   Text to synthesize.
   * @param string $language
   *   Language code.
   * @param string $voice
   *   Voice identifier (provider-specific).
   *
   * @return array{success: bool, audio_data: string, mime_type: string, duration_ms: int, error: string}
   *   TTS result with base64-encoded audio.
   */
  public function synthesize(string $text, string $language = 'es', string $voice = 'default'): array {
    if (!$this->isEnabled()) {
      return [
        'success' => FALSE,
        'audio_data' => '',
        'mime_type' => '',
        'duration_ms' => 0,
        'error' => 'Voice pipeline is not enabled.',
      ];
    }

    $startTime = microtime(TRUE);

    try {
      $config = $this->configFactory->get('jaraba_ai_agents.voice');
      $ttsProvider = $config->get('tts_provider') ?? 'openai_tts';
      $ttsVoice = ($voice !== 'default') ? $voice : ($config->get('tts_voice') ?? 'alloy');

      $audioData = $this->executeTTS($text, $language, $ttsVoice, $ttsProvider);
      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      $this->observability?->log([
        'agent_id' => 'voice_pipeline',
        'action' => 'tts',
        'tier' => 'fast',
        'provider_id' => $ttsProvider,
        'duration_ms' => $durationMs,
        'success' => TRUE,
      ]);

      return [
        'success' => TRUE,
        'audio_data' => $audioData,
        'mime_type' => 'audio/mpeg',
        'duration_ms' => $durationMs,
        'error' => '',
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('TTS failed: @error', ['@error' => $e->getMessage()]);
      return [
        'success' => FALSE,
        'audio_data' => '',
        'mime_type' => '',
        'duration_ms' => (int) ((microtime(TRUE) - $startTime) * 1000),
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Gets supported voice options for the configured TTS provider.
   *
   * @return array
   *   Available voices: id => label.
   */
  public function getAvailableVoices(): array {
    return [
      'alloy' => 'Alloy (Neutral)',
      'echo' => 'Echo (Male)',
      'fable' => 'Fable (Narrator)',
      'onyx' => 'Onyx (Deep Male)',
      'nova' => 'Nova (Female)',
      'shimmer' => 'Shimmer (Soft Female)',
    ];
  }

  /**
   * Executes Speech-to-Text via configured provider.
   *
   * @throws \RuntimeException
   *   If STT is not configured or fails.
   */
  protected function executeSTT(string $audioData, string $mimeType, string $language, string $provider): string {
    if (!$this->aiProvider) {
      throw new \RuntimeException('AI provider not available for STT.');
    }

    // Delegate to Drupal AI module's STT operation type if available.
    // Fallback: use chat model with audio description (limited STT).
    throw new \RuntimeException('STT provider "' . $provider . '" not yet configured. Enable Whisper API integration.');
  }

  /**
   * Executes Text-to-Speech via configured provider.
   *
   * @throws \RuntimeException
   *   If TTS is not configured or fails.
   */
  protected function executeTTS(string $text, string $language, string $voice, string $provider): string {
    if (!$this->aiProvider) {
      throw new \RuntimeException('AI provider not available for TTS.');
    }

    // Delegate to Drupal AI module's TTS operation type if available.
    throw new \RuntimeException('TTS provider "' . $provider . '" not yet configured. Enable TTS API integration.');
  }

}
