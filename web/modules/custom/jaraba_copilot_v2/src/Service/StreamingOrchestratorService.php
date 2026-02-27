<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;

/**
 * Servicio de orquestacion con streaming real token-by-token (GAP-01).
 *
 * ESTRUCTURA:
 *   Extiende CopilotOrchestratorService para reutilizar todo el setup
 *   (system prompt, normative knowledge, circuit breaker, cache, etc.)
 *   pero implementa la llamada LLM con streaming real via
 *   ChatInput::setStreamedOutput(TRUE).
 *
 * LOGICA:
 *   - streamChat() retorna un Generator PHP que yield-ea chunks de texto.
 *   - Buffer inteligente: acumula tokens hasta encontrar un limite de frase
 *     (. ! ? \n) o alcanzar 80 caracteres, luego yield.
 *   - PII masking: se aplica al buffer acumulado cada 500 caracteres.
 *   - Token tracking y cache: al finalizar el stream, con texto completo.
 *   - Fallback: si streaming no disponible, degrada a buffered.
 *
 * PATRON: Herencia de CopilotOrchestratorService para acceso a metodos
 *         protected (buildSystemPrompt, enrichWithNormativeKnowledge,
 *         getProvidersForMode, callProvider, etc.) sin duplicar codigo.
 *
 * ESPECIFICACION: Plan Elevacion IA v2, GAP-01 §3.1.
 */
class StreamingOrchestratorService extends CopilotOrchestratorService {

  /**
   * Ejecuta chat con streaming real token-by-token (GAP-01).
   *
   * Retorna un Generator que yield-ea arrays con estructura:
   *   ['type' => 'chunk', 'text' => '...']
   *   ['type' => 'done', 'metadata' => [...]]
   *   ['type' => 'error', 'message' => '...']
   *
   * @param string $message
   *   Mensaje del usuario.
   * @param array $context
   *   Contexto adicional.
   * @param string $mode
   *   Modo del copilot (coach, consultor, etc.).
   *
   * @return \Generator
   *   Generator que yield-ea chunks de respuesta.
   */
  public function streamChat(string $message, array $context, string $mode): \Generator {
    // S5-04: Check semantic cache first (fuzzy matching via Qdrant).
    if ($this->semanticCache) {
      try {
        $tenantId = $this->tenantContext ? (string) ($this->tenantContext->getCurrentTenantId() ?? '0') : '0';
        $semanticHit = $this->semanticCache->get($message, $mode, $tenantId);
        if ($semanticHit) {
          $this->logger->debug('GAP-01/S5-04: Respuesta servida desde semantic cache (no-stream).');
          $semanticHit['cached'] = TRUE;
          $semanticHit['cache_type'] = 'semantic';
          yield ['type' => 'cached', 'response' => $semanticHit];
          return;
        }
      }
      catch (\Exception $e) {
        $this->logger->notice('Semantic cache lookup failed in streaming: @error', ['@error' => $e->getMessage()]);
      }
    }

    // 1. Check exact cache (respuestas cacheadas no necesitan streaming).
    if ($this->cacheService) {
      $cachedResponse = $this->cacheService->get($message, $mode, $context);
      if ($cachedResponse) {
        $this->logger->debug('GAP-01: Respuesta servida desde cache (no-stream).');
        yield ['type' => 'cached', 'response' => $cachedResponse];
        return;
      }
    }

    // 2. Setup: providers, model, system prompt, normative knowledge.
    $providers = $this->getProvidersForMode($mode);
    $model = $this->getModelForMode($mode);
    $systemPrompt = $this->buildSystemPrompt($context, $mode);
    $enrichedMessage = $this->enrichWithNormativeKnowledge($mode, $message);

    // 3. Intentar streaming con cada provider.
    foreach ($providers as $providerId) {
      if ($this->isCircuitOpen($providerId)) {
        continue;
      }

      try {
        $startTime = microtime(TRUE);

        // Obtener el provider.
        $provider = $this->aiProvider->createInstance($providerId);

        // Adaptar modelo para Google Gemini.
        $actualModel = $model;
        if ($providerId === 'google_gemini') {
          $actualModel = $this->getGeminiModelForContext($model);
        }

        // Configurar system prompt.
        if (method_exists($provider, 'setChatSystemRole')) {
          $provider->setChatSystemRole($systemPrompt);
        }

        // Habilitar streaming en el provider.
        if (method_exists($provider, 'streamedOutput')) {
          $provider->streamedOutput(TRUE);
        }

        // Crear ChatInput con streaming habilitado.
        $chatMessage = new ChatMessage('user', $enrichedMessage, []);
        $chatInput = new ChatInput([$chatMessage]);

        if (method_exists($chatInput, 'setStreamedOutput')) {
          $chatInput->setStreamedOutput(TRUE);
        }

        // Llamada streaming al LLM.
        $response = $provider->chat($chatInput, $actualModel, [
          'max_tokens' => $this->getMaxTokens(),
          'temperature' => 0.7,
        ]);

        // Verificar si la respuesta es un iterador streamed.
        $normalized = $response->getNormalized();

        if (is_iterable($normalized) && !is_string($normalized)) {
          // Streaming real: iterar sobre chunks.
          yield from $this->processStreamedResponse(
            $normalized,
            $message,
            $mode,
            $context,
            $providerId,
            $actualModel,
            $startTime
          );
        }
        else {
          // Fallback: el provider no soporta streaming real.
          // Procesar como buffered.
          $text = method_exists($normalized, 'getText')
            ? $normalized->getText()
            : (string) $normalized;

          yield from $this->processBufferedFallback(
            $text,
            $message,
            $mode,
            $context,
            $providerId,
            $actualModel,
            $startTime
          );
        }

        // Exito — reset circuit breaker y salir.
        $this->resetCircuitBreaker($providerId);
        return;
      }
      catch (\Exception $e) {
        $this->recordCircuitBreakerFailure($providerId);
        $this->logger->warning('GAP-01: Streaming fallo con provider @id: @msg', [
          '@id' => $providerId,
          '@msg' => $e->getMessage(),
        ]);
        // Continuar con siguiente provider.
      }
    }

    // Todos los providers fallaron.
    yield [
      'type' => 'error',
      'message' => 'Error al procesar la consulta. Por favor, inténtalo de nuevo.',
    ];
  }

  /**
   * Procesa una respuesta streamed real del LLM (GAP-01).
   *
   * Buffer inteligente: acumula tokens hasta encontrar un limite
   * de frase o alcanzar 80 caracteres.
   *
   * @param iterable $streamedResponse
   *   Iterador de chunks del LLM.
   * @param string $originalMessage
   *   Mensaje original del usuario.
   * @param string $mode
   *   Modo del copilot.
   * @param array $context
   *   Contexto.
   * @param string $providerId
   *   ID del provider.
   * @param string $model
   *   Modelo usado.
   * @param float $startTime
   *   Timestamp de inicio.
   *
   * @return \Generator
   *   Generator que yield-ea chunks procesados.
   */
  protected function processStreamedResponse(
    iterable $streamedResponse,
    string $originalMessage,
    string $mode,
    array $context,
    string $providerId,
    string $model,
    float $startTime,
  ): \Generator {
    $buffer = '';
    $fullText = '';
    $chunkIndex = 0;

    foreach ($streamedResponse as $chunk) {
      $text = method_exists($chunk, 'getText') ? $chunk->getText() : (string) $chunk;
      $buffer .= $text;
      $fullText .= $text;

      // Buffer inteligente: yield cuando hay un limite de frase o 80+ chars.
      if ($this->shouldFlushBuffer($buffer)) {
        // GAP-03: Aplicar PII masking al buffer antes de emitir.
        $sanitizedBuffer = $this->maskBufferPII($buffer);

        yield [
          'type' => 'chunk',
          'text' => $sanitizedBuffer,
          'index' => $chunkIndex++,
        ];
        $buffer = '';
      }
    }

    // Flush buffer restante.
    if ($buffer !== '') {
      $sanitizedBuffer = $this->maskBufferPII($buffer);
      yield [
        'type' => 'chunk',
        'text' => $sanitizedBuffer,
        'index' => $chunkIndex,
      ];
    }

    // Post-processing con texto completo.
    $latency = microtime(TRUE) - $startTime;
    $this->recordLatencySample($latency);

    // Token tracking.
    $this->trackStreamingUsage($providerId, $model, $originalMessage, $fullText);

    // Cache.
    $formattedResponse = [
      'text' => $fullText,
      'mode' => $mode,
      'provider' => $providerId,
      'model' => $model,
      'suggestions' => [],
    ];

    if ($this->cacheService) {
      $this->cacheService->set($originalMessage, $mode, $context, $formattedResponse);
    }

    // S5-04: Store in semantic cache for future fuzzy matching.
    if ($this->semanticCache) {
      try {
        $tenantId = $this->tenantContext ? (string) ($this->tenantContext->getCurrentTenantId() ?? '0') : '0';
        $this->semanticCache->set($originalMessage, $fullText, $mode, $tenantId);
      }
      catch (\Exception $e) {
        $this->logger->notice('Semantic cache store failed in streaming: @error', ['@error' => $e->getMessage()]);
      }
    }

    // Evento done.
    yield [
      'type' => 'done',
      'metadata' => [
        'mode' => $mode,
        'provider' => $providerId,
        'model' => $model,
        'streaming_mode' => 'real',
        'suggestions' => [],
      ],
    ];
  }

  /**
   * Fallback a buffered cuando el provider no soporta streaming (GAP-01).
   *
   * @return \Generator
   *   Generator que yield-ea el texto completo como chunks de parrafos.
   */
  protected function processBufferedFallback(
    string $text,
    string $originalMessage,
    string $mode,
    array $context,
    string $providerId,
    string $model,
    float $startTime,
  ): \Generator {
    $this->logger->info('GAP-01: Provider @id no soporta streaming, usando buffered fallback.', [
      '@id' => $providerId,
    ]);

    // PII masking al texto completo.
    $sanitizedText = $this->maskBufferPII($text);

    // Split en parrafos como el controller original.
    $segments = preg_split('/\n{2,}/', $sanitizedText);
    $chunkIndex = 0;

    foreach ($segments as $segment) {
      $segment = trim($segment);
      if ($segment !== '') {
        yield [
          'type' => 'chunk',
          'text' => $segment,
          'index' => $chunkIndex++,
        ];
      }
    }

    $latency = microtime(TRUE) - $startTime;
    $this->recordLatencySample($latency);
    $this->trackStreamingUsage($providerId, $model, $originalMessage, $text);

    $formattedResponse = [
      'text' => $text,
      'mode' => $mode,
      'provider' => $providerId,
      'model' => $model,
      'suggestions' => [],
    ];

    if ($this->cacheService) {
      $this->cacheService->set($originalMessage, $mode, $context, $formattedResponse);
    }

    yield [
      'type' => 'done',
      'metadata' => [
        'mode' => $mode,
        'provider' => $providerId,
        'model' => $model,
        'streaming_mode' => 'buffered',
        'suggestions' => [],
      ],
    ];
  }

  /**
   * Determina si el buffer debe flushed (GAP-01).
   *
   * @param string $buffer
   *   Buffer acumulado.
   *
   * @return bool
   *   TRUE si debe yield-ear.
   */
  protected function shouldFlushBuffer(string $buffer): bool {
    $length = mb_strlen($buffer);

    // Flush si supera 80 caracteres.
    if ($length >= 80) {
      return TRUE;
    }

    // Flush en limites de frase (solo si hay contenido sustancial).
    if ($length >= 15) {
      $lastChar = mb_substr($buffer, -1);
      if (in_array($lastChar, ['.', '!', '?', "\n", ':'], TRUE)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Aplica PII masking al buffer si el servicio esta disponible (GAP-01+03).
   *
   * @param string $buffer
   *   Texto a sanitizar.
   *
   * @return string
   *   Texto con PII enmascarado.
   */
  protected function maskBufferPII(string $buffer): string {
    if (!\Drupal::hasService('ecosistema_jaraba_core.ai_guardrails')) {
      return $buffer;
    }

    try {
      $guardrails = \Drupal::service('ecosistema_jaraba_core.ai_guardrails');
      if (method_exists($guardrails, 'maskOutputPII')) {
        return $guardrails->maskOutputPII($buffer);
      }
    }
    catch (\Exception $e) {
      // Non-blocking — PII masking es best-effort en streaming.
    }

    return $buffer;
  }

  /**
   * Registra uso de tokens para una llamada streaming (GAP-01).
   *
   * @param string $providerId
   *   ID del provider.
   * @param string $model
   *   Modelo usado.
   * @param string $inputText
   *   Texto de entrada.
   * @param string $outputText
   *   Texto de salida completo.
   */
  protected function trackStreamingUsage(string $providerId, string $model, string $inputText, string $outputText): void {
    try {
      $tokensIn = (int) ceil(mb_strlen($inputText) / 4);
      $tokensOut = (int) ceil(mb_strlen($outputText) / 4);

      if (\Drupal::hasService('jaraba_ai_agents.observability')) {
        /** @var \Drupal\jaraba_ai_agents\Service\AIObservabilityService $observability */
        $observability = \Drupal::service('jaraba_ai_agents.observability');
        $observability->log([
          'agent_id' => 'copilot_v2',
          'action' => 'chat_streaming',
          'tier' => 'balanced',
          'model_id' => $model,
          'provider_id' => $providerId,
          'input_tokens' => $tokensIn,
          'output_tokens' => $tokensOut,
          'success' => TRUE,
          'operation_name' => 'CopilotStreamController.streamRealtime',
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('GAP-01: Error en tracking de uso streaming: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
