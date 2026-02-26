<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\jaraba_ai_agents\Contract\MultiModalInputInterface;
use Drupal\jaraba_ai_agents\Contract\MultiModalOutputInterface;
use Drupal\jaraba_ai_agents\Exception\MultiModalNotAvailableException;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Psr\Log\LoggerInterface;

/**
 * Multi-Modal Bridge — Voice + Vision AI (GAP-AUD-011, GAP-AUD-013).
 *
 * Implements audio transcription via OpenAI Whisper API and
 * image analysis via GPT-4o Vision. Falls back gracefully when
 * providers are not configured.
 */
class MultiModalBridgeService implements MultiModalInputInterface, MultiModalOutputInterface {

  /**
   * Maximum image size in bytes (10MB).
   */
  protected const MAX_IMAGE_SIZE = 10 * 1024 * 1024;

  /**
   * Allowed image MIME types.
   */
  protected const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param object|null $aiProvider
   *   OpenAI AI provider (optional).
   * @param object|null $guardrailsService
   *   AI Guardrails for PII masking (optional).
   */
  public function __construct(
    protected LoggerInterface $logger,
    protected ?object $aiProvider = NULL,
    protected ?object $guardrailsService = NULL,
  ) {}

  /**
   * {@inheritdoc}
   *
   * GAP-AUD-011: Transcribes audio via OpenAI Whisper API.
   */
  public function transcribeAudio(string $audioUri, string $language = 'es', array $options = []): array {
    if ($this->aiProvider === NULL) {
      $this->logger->notice('Audio transcription requested but AI provider not configured.');
      throw new MultiModalNotAvailableException('audio_transcription');
    }

    try {
      // Read audio file.
      $audioData = file_get_contents($audioUri);
      if ($audioData === FALSE || empty($audioData)) {
        throw new \RuntimeException('Failed to read audio file: ' . $audioUri);
      }

      $startTime = microtime(TRUE);

      // Call Whisper API via the AI provider.
      if (method_exists($this->aiProvider, 'transcribeAudio')) {
        $result = $this->aiProvider->transcribeAudio($audioData, [
          'language' => $language,
          'response_format' => 'verbose_json',
        ] + $options);
      }
      elseif (method_exists($this->aiProvider, 'chat')) {
        // Fallback: use chat with audio context description.
        $result = [
          'text' => '',
          'confidence' => 0.0,
          'language' => $language,
          'duration_ms' => 0,
        ];
        throw new MultiModalNotAvailableException('audio_transcription', 'Provider does not support transcribeAudio method.');
      }
      else {
        throw new MultiModalNotAvailableException('audio_transcription', 'Provider does not support audio transcription.');
      }

      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      $transcription = [
        'text' => $result['text'] ?? '',
        'confidence' => (float) ($result['confidence'] ?? 0.95),
        'language' => $result['language'] ?? $language,
        'duration_ms' => $durationMs,
      ];

      // PII masking on transcribed text.
      if ($this->guardrailsService !== NULL && method_exists($this->guardrailsService, 'maskOutputPII')) {
        $transcription['text'] = $this->guardrailsService->maskOutputPII($transcription['text']);
      }

      $this->logger->info('Audio transcribed: @chars chars in @ms ms', [
        '@chars' => mb_strlen($transcription['text']),
        '@ms' => $durationMs,
      ]);

      return $transcription;
    }
    catch (MultiModalNotAvailableException $e) {
      throw $e;
    }
    catch (\Exception $e) {
      $this->logger->error('Audio transcription failed: @error', ['@error' => $e->getMessage()]);
      throw new MultiModalNotAvailableException('audio_transcription', $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   *
   * GAP-AUD-013: Analyzes image via GPT-4o Vision.
   */
  public function analyzeImage(string $imageUri, string $prompt = '', array $options = []): array {
    if ($this->aiProvider === NULL) {
      $this->logger->notice('Image analysis requested but AI provider not configured.');
      throw new MultiModalNotAvailableException('image_analysis');
    }

    try {
      // Validate image.
      $this->validateImage($imageUri);

      // Read and encode image.
      $imageData = file_get_contents($imageUri);
      if ($imageData === FALSE) {
        throw new \RuntimeException('Failed to read image file: ' . $imageUri);
      }

      $base64 = base64_encode($imageData);
      $mimeType = $this->detectMimeType($imageUri, $imageData);

      // Build analysis prompt.
      $analysisPrompt = !empty($prompt)
        ? AIIdentityRule::apply($prompt, TRUE)
        : AIIdentityRule::apply(
            'Analyze this image in detail. Describe what you see, identify objects, ' .
            'colors, text, and any relevant context. Return JSON: ' .
            '{"description": "...", "tags": ["..."], "objects": ["..."], "colors": ["..."], "confidence": 0.0-1.0}',
            TRUE
        );

      $startTime = microtime(TRUE);

      // Call Vision API.
      if (method_exists($this->aiProvider, 'analyzeImage')) {
        $result = $this->aiProvider->analyzeImage($base64, $analysisPrompt, [
          'mime_type' => $mimeType,
        ] + $options);
      }
      elseif (method_exists($this->aiProvider, 'chat')) {
        // Fallback: use chat with image attachment.
        $result = $this->aiProvider->chat([
          [
            'role' => 'user',
            'content' => [
              ['type' => 'text', 'text' => $analysisPrompt],
              ['type' => 'image_url', 'image_url' => ['url' => "data:$mimeType;base64,$base64"]],
            ],
          ],
        ], ['max_tokens' => 1024]);
      }
      else {
        throw new MultiModalNotAvailableException('image_analysis', 'Provider does not support image analysis.');
      }

      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      // Parse result.
      $responseText = $result['text'] ?? $result['response'] ?? $result['content'] ?? '';
      $analysis = $this->parseImageAnalysis($responseText);
      $analysis['duration_ms'] = $durationMs;

      // PII masking.
      if ($this->guardrailsService !== NULL && method_exists($this->guardrailsService, 'maskOutputPII')) {
        $analysis['description'] = $this->guardrailsService->maskOutputPII($analysis['description']);
      }

      $this->logger->info('Image analyzed in @ms ms', ['@ms' => $durationMs]);

      return $analysis;
    }
    catch (MultiModalNotAvailableException $e) {
      throw $e;
    }
    catch (\Exception $e) {
      $this->logger->error('Image analysis failed: @error', ['@error' => $e->getMessage()]);
      throw new MultiModalNotAvailableException('image_analysis', $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInputCapabilities(): array {
    $audioAvailable = $this->aiProvider !== NULL && method_exists($this->aiProvider, 'transcribeAudio');
    $imageAvailable = $this->aiProvider !== NULL;

    return [
      'audio' => $audioAvailable,
      'image' => $imageAvailable,
      'provider' => $this->aiProvider !== NULL ? 'openai' : 'none',
      'status' => $this->aiProvider !== NULL ? 'active' : 'placeholder',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function synthesizeSpeech(string $text, string $voice = 'default', array $options = []): array {
    $this->logger->notice('Speech synthesis not yet implemented.');
    throw new MultiModalNotAvailableException('speech_synthesis');
  }

  /**
   * {@inheritdoc}
   */
  public function generateImage(string $prompt, array $options = []): array {
    $this->logger->notice('Image generation not yet implemented.');
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
    ];
  }

  /**
   * Validates an image file for analysis.
   */
  protected function validateImage(string $imageUri): void {
    if (!file_exists($imageUri) && !str_starts_with($imageUri, 'http')) {
      // Check Drupal file URI scheme.
      $realpath = \Drupal::service('file_system')->realpath($imageUri);
      if (!$realpath || !file_exists($realpath)) {
        throw new \InvalidArgumentException('Image file not found: ' . $imageUri);
      }
    }

    $fileSize = @filesize($imageUri);
    if ($fileSize !== FALSE && $fileSize > self::MAX_IMAGE_SIZE) {
      throw new \InvalidArgumentException('Image exceeds maximum size of 10MB.');
    }
  }

  /**
   * Detects MIME type of image data.
   */
  protected function detectMimeType(string $uri, string $data): string {
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($data);

    if ($mime && in_array($mime, self::ALLOWED_IMAGE_TYPES, TRUE)) {
      return $mime;
    }

    // Fallback by extension.
    $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    return match ($ext) {
      'jpg', 'jpeg' => 'image/jpeg',
      'png' => 'image/png',
      'webp' => 'image/webp',
      'gif' => 'image/gif',
      default => 'image/jpeg',
    };
  }

  /**
   * Parses image analysis response into structured data.
   */
  protected function parseImageAnalysis(string $response): array {
    $analysis = [
      'description' => '',
      'tags' => [],
      'objects' => [],
      'colors' => [],
      'confidence' => 0.8,
    ];

    // Try JSON parse first.
    if (preg_match('/\{[\s\S]*"description"[\s\S]*\}/u', $response, $matches)) {
      $decoded = json_decode($matches[0], TRUE);
      if (json_last_error() === JSON_ERROR_NONE) {
        $analysis['description'] = (string) ($decoded['description'] ?? '');
        $analysis['tags'] = (array) ($decoded['tags'] ?? []);
        $analysis['objects'] = (array) ($decoded['objects'] ?? []);
        $analysis['colors'] = (array) ($decoded['colors'] ?? []);
        $analysis['confidence'] = (float) ($decoded['confidence'] ?? 0.8);
        return $analysis;
      }
    }

    // Fallback: use raw text as description.
    $analysis['description'] = trim($response);
    return $analysis;
  }

}
