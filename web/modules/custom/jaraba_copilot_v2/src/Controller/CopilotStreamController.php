<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\AIUsageLimitService;
use Drupal\ecosistema_jaraba_core\Service\RateLimiterService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_copilot_v2\Service\CopilotOrchestratorService;
use Drupal\jaraba_copilot_v2\Service\ModeDetectorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Endpoint SSE para streaming de respuestas del copiloto.
 *
 * FIX-003: Blindado con rate limiting, AI usage limits, token tracking
 * y tenant context. Replica el patrón de seguridad de CopilotApiController.
 *
 * FIX-024: Buffered streaming — the orchestrator returns the full response
 * synchronously, then we split it into semantic chunks (paragraphs/sentences)
 * and send them as SSE events without artificial delays. Real token-by-token
 * streaming requires ai.provider streaming support (Anthropic SDK stream:true)
 * which is planned for a future iteration.
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

    // 2. Input validation.
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['message'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => (string) $this->t('El campo message es obligatorio.'),
      ], 400);
    }

    $message = $data['message'];
    $context = $data['context'] ?? [];
    $mode = $data['mode'] ?? NULL;

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

    return new StreamedResponse(function () use ($capturedMessage, $context, $detectedMode, $detectedModeDetection, $capturedTenant, $aiUsageLimitService) {
      // Enviar evento de inicio con modo detectado.
      $this->sendSSEEvent('mode', [
        'mode' => $detectedMode,
        'detection' => $detectedModeDetection,
      ]);

      // Enviar evento de "pensando".
      $this->sendSSEEvent('thinking', ['status' => TRUE]);

      // Obtener respuesta completa del orquestador.
      // FIX-024: No artificial delays — send semantic chunks immediately.
      try {
        $response = $this->orchestrator->chat($capturedMessage, $context, $detectedMode);

        $text = $response['text'] ?? '';
        $chunks = $this->splitIntoParagraphs($text);

        foreach ($chunks as $i => $chunk) {
          $this->sendSSEEvent('chunk', [
            'text' => $chunk,
            'index' => $i,
          ]);
        }

        // 5. Token tracking post-respuesta.
        if ($capturedTenant) {
          $tokensIn = (int) ceil(mb_strlen($capturedMessage) / 4);
          $tokensOut = (int) ceil(mb_strlen($text) / 4);
          $aiUsageLimitService->recordUsage($capturedTenant, $tokensIn, $tokensOut);
        }

        // Enviar evento de finalizacion con metadata.
        // FIX-034: Include log_id for feedback correlation.
        $this->sendSSEEvent('done', [
          'mode' => $response['mode'] ?? $detectedMode,
          'provider' => $response['provider'] ?? 'unknown',
          'model' => $response['model'] ?? '',
          'suggestions' => $response['suggestions'] ?? [],
          'streaming_mode' => 'buffered',
          'log_id' => $response['log_id'] ?? NULL,
        ]);
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
