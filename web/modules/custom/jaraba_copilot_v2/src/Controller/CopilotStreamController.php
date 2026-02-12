<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_copilot_v2\Service\CopilotOrchestratorService;
use Drupal\jaraba_copilot_v2\Service\ModeDetectorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Endpoint SSE para streaming de respuestas del copiloto.
 */
class CopilotStreamController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected CopilotOrchestratorService $orchestrator,
    protected ModeDetectorService $modeDetector,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_copilot_v2.copilot_orchestrator'),
      $container->get('jaraba_copilot_v2.mode_detector'),
    );
  }

  /**
   * POST /api/copilot/chat/stream - Streaming SSE endpoint.
   */
  public function stream(Request $request): StreamedResponse|JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['message'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'El campo message es obligatorio.',
      ], 400);
    }

    $message = $data['message'];
    $context = $data['context'] ?? [];
    $mode = $data['mode'] ?? NULL;

    // Detectar modo si no se especifica.
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

    return new StreamedResponse(function () use ($message, $context, $detectedMode, $detectedModeDetection) {
      // Enviar evento de inicio con modo detectado.
      $this->sendSSEEvent('mode', [
        'mode' => $detectedMode,
        'detection' => $detectedModeDetection,
      ]);

      // Enviar evento de "pensando".
      $this->sendSSEEvent('thinking', ['status' => TRUE]);

      // Obtener respuesta completa del orquestador.
      try {
        $response = $this->orchestrator->chat($message, $context, $detectedMode);

        // Simular streaming dividiendo la respuesta en chunks.
        $text = $response['text'] ?? '';
        $chunks = $this->splitIntoChunks($text);

        foreach ($chunks as $i => $chunk) {
          $this->sendSSEEvent('chunk', [
            'text' => $chunk,
            'index' => $i,
          ]);
          // Flush para enviar inmediatamente al cliente.
          if (ob_get_level() > 0) {
            ob_flush();
          }
          flush();
          // Simular latencia de generacion.
          usleep(30000);
        }

        // Enviar evento de finalizacion con metadata.
        $this->sendSSEEvent('done', [
          'mode' => $response['mode'] ?? $detectedMode,
          'provider' => $response['provider'] ?? 'unknown',
          'model' => $response['model'] ?? '',
          'suggestions' => $response['suggestions'] ?? [],
        ]);
      }
      catch (\Exception $e) {
        $this->sendSSEEvent('error', [
          'message' => 'Error al procesar la consulta. Por favor, intentalo de nuevo.',
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
   * Divide texto en chunks para streaming.
   */
  protected function splitIntoChunks(string $text, int $chunkSize = 80): array {
    if (empty($text)) {
      return [];
    }

    $chunks = [];
    $words = explode(' ', $text);
    $current = '';

    foreach ($words as $word) {
      if (mb_strlen($current . ' ' . $word) > $chunkSize && $current !== '') {
        $chunks[] = trim($current);
        $current = $word;
      }
      else {
        $current .= ($current !== '' ? ' ' : '') . $word;
      }
    }

    if ($current !== '') {
      $chunks[] = trim($current);
    }

    return $chunks;
  }

}
