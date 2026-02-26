<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\AIUsageLimitService;
use Drupal\ecosistema_jaraba_core\Service\RateLimiterService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_copilot_v2\Service\CopilotOrchestratorService;
use Drupal\jaraba_copilot_v2\Service\ModeDetectorService;
use Drupal\jaraba_copilot_v2\Service\StreamingOrchestratorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Endpoint SSE para streaming de respuestas del copiloto.
 *
 * FIX-003: Blindado con rate limiting, AI usage limits, token tracking
 * y tenant context. Replica el patrón de seguridad de CopilotApiController.
 *
 * GAP-01: Streaming real token-by-token cuando StreamingOrchestratorService
 * esta disponible. Consume Generator que yield-ea chunks y los convierte
 * en eventos SSE. Fallback automatico a buffered si streaming no disponible.
 */
class CopilotStreamController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected CopilotOrchestratorService $orchestrator,
    protected ModeDetectorService $modeDetector,
    protected RateLimiterService $rateLimiter,
    protected AIUsageLimitService $aiUsageLimit,
    protected TenantContextService $tenantContext,
    protected ?StreamingOrchestratorService $streamingOrchestrator = NULL,
    protected ?object $multiModalBridge = NULL,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_copilot_v2.copilot_orchestrator'),
      $container->get('jaraba_copilot_v2.mode_detector'),
      $container->get('ecosistema_jaraba_core.rate_limiter'),
      $container->get('ecosistema_jaraba_core.ai_usage_limit'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
      $container->has('jaraba_copilot_v2.streaming_orchestrator')
        ? $container->get('jaraba_copilot_v2.streaming_orchestrator')
        : NULL,
      $container->has('jaraba_ai_agents.multimodal_bridge')
        ? $container->get('jaraba_ai_agents.multimodal_bridge')
        : NULL,
    );
  }

  /**
   * POST /api/v1/copilot/chat/stream - Streaming SSE endpoint.
   *
   * FIX-003: Seguridad alineada con CopilotApiController::chat():
   * 1. Rate limiting por usuario (max N requests/minuto).
   * 2. AI usage limit por tenant (cuota mensual de tokens).
   * 3. Token tracking post-respuesta.
   * 4. Tenant context para aislamiento de datos.
   */
  public function stream(Request $request): StreamedResponse|JsonResponse {
    // 1. Rate limiting.
    $userId = (string) $this->currentUser()->id();
    $rateLimitResult = $this->rateLimiter->consume($userId, 'ai');

    if (!$rateLimitResult['allowed']) {
      $response = new JsonResponse([
        'success' => FALSE,
        'error' => (string) $this->t('Demasiadas solicitudes. Intenta de nuevo en un minuto.'),
      ], 429);
      foreach ($this->rateLimiter->getHeaders($rateLimitResult) as $header => $value) {
        $response->headers->set($header, (string) $value);
      }
      return $response;
    }

    // 2. Input validation — support both JSON and multipart/form-data (GAP-AUD-013).
    $contentType = $request->headers->get('Content-Type', '');
    $imageAnalysis = NULL;

    if (str_contains($contentType, 'multipart/form-data')) {
      // GAP-AUD-013: Multipart request with optional image attachment.
      $data = [
        'message' => $request->request->get('message', ''),
        'context' => json_decode($request->request->get('context', '{}'), TRUE) ?: [],
        'mode' => $request->request->get('mode'),
      ];
      /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile */
      $imageFile = $request->files->get('image');
      if ($imageFile instanceof UploadedFile && $imageFile->isValid() && $this->multiModalBridge !== NULL) {
        try {
          $imageData = file_get_contents($imageFile->getPathname());
          if ($imageData !== FALSE && strlen($imageData) <= 10 * 1024 * 1024) {
            $imageAnalysis = $this->multiModalBridge->analyzeImage($imageData, $data['message']);
          }
        }
        catch (\Exception $e) {
          // Log but don't block the message.
          \Drupal::logger('jaraba_copilot_v2')->warning('Image analysis failed: @error', [
            '@error' => $e->getMessage(),
          ]);
        }
      }
    }
    else {
      $data = json_decode($request->getContent(), TRUE);
    }

    if (empty($data['message'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => (string) $this->t('El campo message es obligatorio.'),
      ], 400);
    }

    $message = $data['message'];
    $context = $data['context'] ?? [];
    $mode = $data['mode'] ?? NULL;

    // GAP-AUD-013: Enrich context with image analysis if available.
    if ($imageAnalysis !== NULL) {
      $context['image_analysis'] = $imageAnalysis;
      $message .= "\n\n[Imagen adjunta: " . ($imageAnalysis['description'] ?? 'imagen analizada') . ']';
    }

    // 3. Resolve tenant y verificar AI usage limit.
    $tenant = $this->tenantContext->getCurrentTenant();

    if ($tenant) {
      $aiLimitCheck = $this->aiUsageLimit->checkLimit($tenant);
      if ($aiLimitCheck['status'] === 'blocked' && !$aiLimitCheck['can_use_ai']) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => (string) $this->t('Has alcanzado el límite de uso de IA de tu plan.'),
          'ai_limit_exceeded' => TRUE,
          'usage_percent' => $aiLimitCheck['usage_percent'],
          'upgrade_required' => TRUE,
          'plan_tier' => $aiLimitCheck['plan_tier'] ?? NULL,
        ], 429);
      }
    }

    // 4. Detectar modo si no se especifica.
    $modeDetection = [];
    if (!$mode) {
      $detection = $this->modeDetector->detectMode($message, $context);
      $mode = $detection['mode'];
      $modeDetection = [
        'mode' => $detection['mode'],
        'score' => $detection['score'],
        'confidence' => $detection['confidence'],
        'emotion_score' => $detection['emotion_score'] ?? 0,
      ];
    }

    $detectedMode = $mode;
    $detectedModeDetection = $modeDetection;

    // Capturar tenant para uso dentro del callback.
    $capturedTenant = $tenant;
    $capturedMessage = $message;
    $aiUsageLimitService = $this->aiUsageLimit;

    // GAP-01: Elegir streaming real o buffered.
    $useRealStreaming = $this->streamingOrchestrator !== NULL;

    return new StreamedResponse(function () use ($capturedMessage, $context, $detectedMode, $detectedModeDetection, $capturedTenant, $aiUsageLimitService, $useRealStreaming) {
      // Enviar evento de inicio con modo detectado.
      $this->sendSSEEvent('mode', [
        'mode' => $detectedMode,
        'detection' => $detectedModeDetection,
      ]);

      // Enviar evento de "pensando".
      $this->sendSSEEvent('thinking', ['status' => TRUE]);

      try {
        if ($useRealStreaming) {
          // GAP-01: Streaming real token-by-token via Generator.
          $this->handleRealStreaming(
            $capturedMessage,
            $context,
            $detectedMode,
            $capturedTenant,
            $aiUsageLimitService
          );
        }
        else {
          // FIX-024: Buffered fallback — respuesta completa split en paragrafos.
          $this->handleBufferedStreaming(
            $capturedMessage,
            $context,
            $detectedMode,
            $capturedTenant,
            $aiUsageLimitService
          );
        }
      }
      catch (\Exception $e) {
        $this->sendSSEEvent('error', [
          'message' => 'Error al procesar la consulta. Por favor, inténtalo de nuevo.',
        ]);
      }
    }, 200, [
      'Content-Type' => 'text/event-stream',
      'Cache-Control' => 'no-cache',
      'Connection' => 'keep-alive',
      'X-Accel-Buffering' => 'no',
    ]);
  }

  /**
   * GAP-01: Streaming real token-by-token via StreamingOrchestratorService.
   *
   * Consume el Generator de streamChat() y convierte cada yield en evento SSE.
   *
   * @param string $message
   *   Mensaje del usuario.
   * @param array $context
   *   Contexto adicional.
   * @param string $mode
   *   Modo del copilot.
   * @param mixed $tenant
   *   Tenant actual o NULL.
   * @param \Drupal\ecosistema_jaraba_core\Service\AIUsageLimitService $aiUsageLimitService
   *   Servicio de limites.
   */
  protected function handleRealStreaming(
    string $message,
    array $context,
    string $mode,
    mixed $tenant,
    AIUsageLimitService $aiUsageLimitService,
  ): void {
    $fullText = '';

    foreach ($this->streamingOrchestrator->streamChat($message, $context, $mode) as $event) {
      $type = $event['type'] ?? '';

      switch ($type) {
        case 'chunk':
          // Primer chunk desactiva el indicador de pensando.
          if ($fullText === '') {
            $this->sendSSEEvent('thinking', ['status' => FALSE]);
          }
          $fullText .= $event['text'];
          $this->sendSSEEvent('chunk', [
            'text' => $event['text'],
            'index' => $event['index'] ?? 0,
          ]);
          break;

        case 'cached':
          // Respuesta cacheada: emitir como chunks de paragrafos.
          $this->sendSSEEvent('thinking', ['status' => FALSE]);
          $cachedText = $event['response']['text'] ?? '';
          $chunks = $this->splitIntoParagraphs($cachedText);
          foreach ($chunks as $i => $chunk) {
            $this->sendSSEEvent('chunk', [
              'text' => $chunk,
              'index' => $i,
            ]);
          }
          $fullText = $cachedText;
          $this->sendSSEEvent('done', [
            'mode' => $event['response']['mode'] ?? $mode,
            'provider' => $event['response']['provider'] ?? 'cache',
            'model' => $event['response']['model'] ?? '',
            'suggestions' => $event['response']['suggestions'] ?? [],
            'streaming_mode' => 'cached',
          ]);
          break;

        case 'done':
          // Token tracking post-stream.
          if ($tenant) {
            $tokensIn = (int) ceil(mb_strlen($message) / 4);
            $tokensOut = (int) ceil(mb_strlen($fullText) / 4);
            $aiUsageLimitService->recordUsage($tenant, $tokensIn, $tokensOut);
          }
          $metadata = $event['metadata'] ?? [];
          $this->sendSSEEvent('done', [
            'mode' => $metadata['mode'] ?? $mode,
            'provider' => $metadata['provider'] ?? 'unknown',
            'model' => $metadata['model'] ?? '',
            'suggestions' => $metadata['suggestions'] ?? [],
            'streaming_mode' => $metadata['streaming_mode'] ?? 'real',
          ]);
          break;

        case 'error':
          $this->sendSSEEvent('error', [
            'message' => $event['message'] ?? 'Error inesperado.',
          ]);
          break;
      }
    }
  }

  /**
   * Buffered streaming — respuesta completa split en paragrafos (FIX-024).
   *
   * @param string $message
   *   Mensaje del usuario.
   * @param array $context
   *   Contexto adicional.
   * @param string $mode
   *   Modo del copilot.
   * @param mixed $tenant
   *   Tenant actual o NULL.
   * @param \Drupal\ecosistema_jaraba_core\Service\AIUsageLimitService $aiUsageLimitService
   *   Servicio de limites.
   */
  protected function handleBufferedStreaming(
    string $message,
    array $context,
    string $mode,
    mixed $tenant,
    AIUsageLimitService $aiUsageLimitService,
  ): void {
    $response = $this->orchestrator->chat($message, $context, $mode);

    $text = $response['text'] ?? '';
    $chunks = $this->splitIntoParagraphs($text);

    foreach ($chunks as $i => $chunk) {
      $this->sendSSEEvent('chunk', [
        'text' => $chunk,
        'index' => $i,
      ]);
    }

    // Token tracking post-respuesta.
    if ($tenant) {
      $tokensIn = (int) ceil(mb_strlen($message) / 4);
      $tokensOut = (int) ceil(mb_strlen($text) / 4);
      $aiUsageLimitService->recordUsage($tenant, $tokensIn, $tokensOut);
    }

    // FIX-034: Include log_id for feedback correlation.
    $this->sendSSEEvent('done', [
      'mode' => $response['mode'] ?? $mode,
      'provider' => $response['provider'] ?? 'unknown',
      'model' => $response['model'] ?? '',
      'suggestions' => $response['suggestions'] ?? [],
      'streaming_mode' => 'buffered',
      'log_id' => $response['log_id'] ?? NULL,
    ]);
  }

  /**
   * Envia un evento SSE formateado.
   */
  protected function sendSSEEvent(string $event, array $data): void {
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    if (ob_get_level() > 0) {
      ob_flush();
    }
    flush();
  }

  /**
   * Splits text into semantic chunks based on paragraph breaks.
   *
   * FIX-024: Replaces the old splitIntoChunks() which split at arbitrary
   * 80-char boundaries. This method splits on double newlines (paragraphs)
   * so that each SSE chunk is a meaningful unit of text. Single-paragraph
   * responses are sent as one chunk for instant delivery.
   *
   * @param string $text
   *   The full response text from the orchestrator.
   *
   * @return array
   *   An array of non-empty text segments.
   */
  protected function splitIntoParagraphs(string $text): array {
    if (trim($text) === '') {
      return [];
    }

    // Split on double newlines (paragraph boundaries).
    $segments = preg_split('/\n{2,}/', $text);

    // Filter out empty segments and preserve whitespace within each segment.
    $chunks = [];
    foreach ($segments as $segment) {
      $segment = trim($segment);
      if ($segment !== '') {
        $chunks[] = $segment;
      }
    }

    // If no paragraph breaks found, return the full text as a single chunk.
    return $chunks ?: [trim($text)];
  }

}
