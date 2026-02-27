<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\ai\OperationType\TextToImage\TextToImageInput;
use Drupal\ai\OperationType\TextToSpeech\TextToSpeechInput;
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
   *
   * HAL-AI-24: Synthesizes speech via OpenAI TTS API.
   */
  public function synthesizeSpeech(string $text, string $voice = 'alloy', array $options = []): array {
    if ($this->aiProvider === NULL) {
      $this->logger->notice('Speech synthesis requested but AI provider not configured.');
      throw new MultiModalNotAvailableException('speech_synthesis');
    }

    try {
      // Validate input text.
      if (empty(trim($text))) {
        throw new \InvalidArgumentException('Text for speech synthesis cannot be empty.');
      }

      // PII masking on input text before sending to TTS provider.
      if ($this->guardrailsService !== NULL && method_exists($this->guardrailsService, 'maskOutputPII')) {
        $text = $this->guardrailsService->maskOutputPII($text);
      }

      // Apply identity rule to text (short version for token-constrained TTS).
      $processedText = AIIdentityRule::apply($text, TRUE);

      // Resolve model: tts-1-hd if requested, otherwise tts-1.
      $modelId = ($options['hd'] ?? FALSE) ? 'tts-1-hd' : ($options['model'] ?? 'tts-1');

      // Resolve the TTS provider instance from the plugin manager.
      $provider = $this->resolveProviderForOperation('text_to_speech');

      // Set voice configuration on the provider.
      $provider->setConfiguration(['voice' => $voice] + ($options['provider_config'] ?? []));

      // Build input.
      $input = new TextToSpeechInput($processedText);

      $startTime = microtime(TRUE);

      /** @var \Drupal\ai\OperationType\TextToSpeech\TextToSpeechOutput $result */
      $result = $provider->textToSpeech($input, $modelId, ['jaraba_multimodal']);

      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      // Extract audio from normalized output.
      $audioFiles = $result->getNormalized();
      if (empty($audioFiles) || !isset($audioFiles[0])) {
        throw new \RuntimeException('No audio data returned from TTS provider.');
      }

      /** @var \Drupal\ai\OperationType\GenericType\AudioFile $audioFile */
      $audioFile = $audioFiles[0];
      $audioData = $audioFile->getBinary();

      if (empty($audioData)) {
        throw new \RuntimeException('Empty audio data returned from TTS provider.');
      }

      // Save audio to temporary file.
      $fileSystem = \Drupal::service('file_system');
      $destination = 'temporary://jaraba-tts/';
      $fileSystem->prepareDirectory($destination, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
      $filename = 'tts_' . md5($text . $voice . microtime()) . '.mp3';
      $uri = $fileSystem->saveData($audioData, $destination . $filename);

      if ($uri === FALSE) {
        throw new \RuntimeException('Failed to save synthesized speech audio file.');
      }

      $this->logger->info('Speech synthesized: @bytes bytes, voice=@voice, model=@model in @ms ms', [
        '@bytes' => strlen($audioData),
        '@voice' => $voice,
        '@model' => $modelId,
        '@ms' => $durationMs,
      ]);

      return [
        'audio_uri' => $uri,
        'format' => 'mp3',
        'voice' => $voice,
        'duration_ms' => $durationMs,
        'size_bytes' => strlen($audioData),
        'model' => $modelId,
      ];
    }
    catch (MultiModalNotAvailableException $e) {
      throw $e;
    }
    catch (\Exception $e) {
      $this->logger->error('Speech synthesis failed: @error', ['@error' => $e->getMessage()]);
      throw new MultiModalNotAvailableException('speech_synthesis', $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   *
   * HAL-AI-24: Generates images via OpenAI DALL-E / GPT Image API.
   */
  public function generateImage(string $prompt, array $options = []): array {
    if ($this->aiProvider === NULL) {
      $this->logger->notice('Image generation requested but AI provider not configured.');
      throw new MultiModalNotAvailableException('image_generation');
    }

    try {
      // Validate prompt.
      if (empty(trim($prompt))) {
        throw new \InvalidArgumentException('Image generation prompt cannot be empty.');
      }

      // Apply guardrails: check for PII and jailbreak attempts in prompt.
      if ($this->guardrailsService !== NULL) {
        if (method_exists($this->guardrailsService, 'checkPII')) {
          $piiResult = $this->guardrailsService->checkPII($prompt);
          if (isset($piiResult['action']) && $piiResult['action'] === 'block') {
            throw new \RuntimeException('Image generation prompt contains sensitive PII data.');
          }
        }
        if (method_exists($this->guardrailsService, 'checkJailbreak')) {
          $jailbreakResult = $this->guardrailsService->checkJailbreak($prompt);
          if (isset($jailbreakResult['action']) && $jailbreakResult['action'] === 'block') {
            throw new \RuntimeException('Image generation prompt was flagged by safety guardrails.');
          }
        }
      }

      // Apply identity rule to prompt.
      $processedPrompt = AIIdentityRule::apply($prompt, TRUE);

      // Resolve model.
      $modelId = $options['model'] ?? 'dall-e-3';

      // Resolve the image generation provider instance from the plugin manager.
      $provider = $this->resolveProviderForOperation('text_to_image');

      // Set generation configuration (size, quality, style).
      $providerConfig = [];
      if (isset($options['size'])) {
        $providerConfig['size'] = $options['size'];
      }
      if (isset($options['quality'])) {
        $providerConfig['quality'] = $options['quality'];
      }
      if (isset($options['style'])) {
        $providerConfig['style'] = $options['style'];
      }
      // DALL-E 3 returns b64_json by default when response_format is set.
      if ($modelId === 'dall-e-3') {
        $providerConfig['response_format'] = $options['response_format'] ?? 'b64_json';
      }
      if (!empty($providerConfig)) {
        $provider->setConfiguration($providerConfig);
      }

      // Build input.
      $input = new TextToImageInput($processedPrompt);

      $startTime = microtime(TRUE);

      /** @var \Drupal\ai\OperationType\TextToImage\TextToImageOutput $result */
      $result = $provider->textToImage($input, $modelId, ['jaraba_multimodal']);

      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      // Extract images from normalized output.
      $imageFiles = $result->getNormalized();
      if (empty($imageFiles)) {
        throw new \RuntimeException('No image data returned from image generation provider.');
      }

      // Save each image to temp file.
      $fileSystem = \Drupal::service('file_system');
      $destination = 'temporary://jaraba-imagegen/';
      $fileSystem->prepareDirectory($destination, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

      $images = [];
      foreach ($imageFiles as $index => $imageFile) {
        /** @var \Drupal\ai\OperationType\GenericType\ImageFile $imageFile */
        $imageData = $imageFile->getBinary();
        if (empty($imageData)) {
          $this->logger->warning('Empty image data at index @index, skipping.', ['@index' => $index]);
          continue;
        }

        $mimeType = $imageFile->getMimeType() ?: 'image/png';
        $extension = match ($mimeType) {
          'image/jpeg' => 'jpg',
          'image/webp' => 'webp',
          'image/gif' => 'gif',
          default => 'png',
        };

        $filename = 'imagegen_' . md5($prompt . $index . microtime()) . '.' . $extension;
        $uri = $fileSystem->saveData($imageData, $destination . $filename);

        if ($uri === FALSE) {
          $this->logger->warning('Failed to save generated image at index @index.', ['@index' => $index]);
          continue;
        }

        $images[] = [
          'uri' => $uri,
          'mime_type' => $mimeType,
          'size_bytes' => strlen($imageData),
        ];
      }

      if (empty($images)) {
        throw new \RuntimeException('Failed to save any generated images.');
      }

      $this->logger->info('Image generated: @count images, model=@model in @ms ms', [
        '@count' => count($images),
        '@model' => $modelId,
        '@ms' => $durationMs,
      ]);

      return [
        'images' => $images,
        'model' => $modelId,
        'duration_ms' => $durationMs,
      ];
    }
    catch (MultiModalNotAvailableException $e) {
      throw $e;
    }
    catch (\Exception $e) {
      $this->logger->error('Image generation failed: @error', ['@error' => $e->getMessage()]);
      throw new MultiModalNotAvailableException('image_generation', $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputCapabilities(): array {
    if ($this->aiProvider === NULL) {
      return [
        'speech' => FALSE,
        'image' => FALSE,
        'provider' => 'none',
        'status' => 'unavailable',
      ];
    }

    // Check if TTS and image generation providers are available.
    $speechAvailable = FALSE;
    $imageAvailable = FALSE;

    try {
      if (method_exists($this->aiProvider, 'getDefaultProviderForOperationType')) {
        $ttsProvider = $this->aiProvider->getDefaultProviderForOperationType('text_to_speech');
        $speechAvailable = !empty($ttsProvider['provider_id']);

        $imageProvider = $this->aiProvider->getDefaultProviderForOperationType('text_to_image');
        $imageAvailable = !empty($imageProvider['provider_id']);
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error checking output capabilities: @error', ['@error' => $e->getMessage()]);
    }

    return [
      'speech' => $speechAvailable,
      'image' => $imageAvailable,
      'provider' => 'openai',
      'status' => ($speechAvailable || $imageAvailable) ? 'active' : 'unavailable',
    ];
  }

  /**
   * Resolves a provider instance for a given operation type.
   *
   * Uses the AiProviderPluginManager to get the default provider configured
   * for the specified operation type (e.g. 'text_to_speech', 'text_to_image').
   *
   * @param string $operationType
   *   The AI operation type identifier.
   *
   * @return object
   *   The provider proxy instance.
   *
   * @throws \Drupal\jaraba_ai_agents\Exception\MultiModalNotAvailableException
   *   If no provider is configured for the operation type.
   */
  protected function resolveProviderForOperation(string $operationType): object {
    // If the aiProvider is the plugin manager, resolve through it.
    if (method_exists($this->aiProvider, 'getDefaultProviderForOperationType')) {
      $defaultProvider = $this->aiProvider->getDefaultProviderForOperationType($operationType);
      if (empty($defaultProvider['provider_id'])) {
        throw new MultiModalNotAvailableException(
          $operationType,
          "No default provider configured for operation type '$operationType'."
        );
      }
      try {
        return $this->aiProvider->createInstance($defaultProvider['provider_id']);
      }
      catch (\Exception $e) {
        throw new MultiModalNotAvailableException(
          $operationType,
          'Failed to create provider instance: ' . $e->getMessage()
        );
      }
    }

    // Fallback: if aiProvider is already a direct provider instance
    // (e.g. in tests or alternative wiring), use it directly.
    return $this->aiProvider;
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
