<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\VoicePipelineService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Voice Copilot API controller.
 *
 * Handles voice-based AI interactions:
 * - POST /api/v1/voice/transcribe — Audio → Text (STT).
 * - POST /api/v1/voice/synthesize — Text → Audio (TTS).
 * - GET /api/v1/voice/capabilities — Feature availability check.
 */
class VoiceCopilotController extends ControllerBase {

  public function __construct(
    protected readonly VoicePipelineService $voicePipeline,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_ai_agents.voice_pipeline'),
    );
  }

  /**
   * Returns voice pipeline capabilities.
   */
  public function capabilities(): JsonResponse {
    return new JsonResponse([
      'enabled' => $this->voicePipeline->isEnabled(),
      'stt_available' => $this->voicePipeline->isEnabled(),
      'tts_available' => $this->voicePipeline->isEnabled(),
      'voices' => $this->voicePipeline->getAvailableVoices(),
      'supported_audio_formats' => ['audio/webm', 'audio/wav', 'audio/mp3'],
      'max_audio_duration_seconds' => 120,
    ]);
  }

  /**
   * Transcribes audio to text.
   */
  public function transcribe(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['audio_data'])) {
      return new JsonResponse(['error' => 'Missing audio_data'], 400);
    }

    $result = $this->voicePipeline->transcribe(
      $data['audio_data'],
      $data['mime_type'] ?? 'audio/webm',
      $data['language'] ?? 'es',
    );

    return new JsonResponse($result, $result['success'] ? 200 : 503);
  }

  /**
   * Synthesizes text to speech.
   */
  public function synthesize(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['text'])) {
      return new JsonResponse(['error' => 'Missing text'], 400);
    }

    $result = $this->voicePipeline->synthesize(
      $data['text'],
      $data['language'] ?? 'es',
      $data['voice'] ?? 'default',
    );

    return new JsonResponse($result, $result['success'] ? 200 : 503);
  }

}
