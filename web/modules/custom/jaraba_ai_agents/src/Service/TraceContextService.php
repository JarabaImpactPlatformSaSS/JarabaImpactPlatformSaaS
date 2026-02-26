<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Component\Uuid\UuidInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de contexto de tracing para observabilidad distribuida (GAP-02).
 *
 * ESTRUCTURA:
 *   Request-scoped service que genera y propaga trace_id y span_id
 *   a traves de toda la cadena de ejecucion IA. Permite reconstruir
 *   la jerarquia de llamadas: Copilot -> Agent -> RAG -> Tool -> etc.
 *
 * LOGICA:
 *   - startTrace(): Genera un UUID v4 como trace_id y lo almacena
 *     en la instancia del servicio (scope de request).
 *   - startSpan(): Genera un span_id para una operacion individual,
 *     con parent_span_id opcional para jerarquia.
 *   - endSpan(): Registra la finalizacion de un span con duracion.
 *   - El trace_id se propaga a todos los servicios que reciben este
 *     servicio inyectado, sin necesidad de pasar parametros extra.
 *
 * PATRON: Request-scoped via Drupal DI (shared: true por defecto).
 *         El estado se resetea en cada nuevo request HTTP.
 *
 * ESPECIFICACION: Plan Elevacion IA v2, GAP-02 §3.2.2.
 */
class TraceContextService {

  /**
   * Trace ID del request actual.
   */
  protected ?string $traceId = NULL;

  /**
   * Span activo actual (ultimo span iniciado sin cerrar).
   */
  protected ?string $activeSpanId = NULL;

  /**
   * Stack de spans para auto-parenting.
   *
   * @var array<string, array{operation: string, parent: ?string, start: float}>
   */
  protected array $spans = [];

  /**
   * Construye el servicio de TraceContext.
   *
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   Generador UUID de Drupal.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_ai_agents.
   */
  public function __construct(
    protected readonly UuidInterface $uuid,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Inicia un nuevo trace para el request actual.
   *
   * Un trace agrupa TODAS las operaciones IA desencadenadas por un
   * unico request de usuario (ej. un mensaje en el copilot).
   *
   * @return string
   *   El trace_id generado (UUID v4).
   */
  public function startTrace(): string {
    $this->traceId = $this->uuid->generate();
    $this->spans = [];
    $this->activeSpanId = NULL;

    $this->logger->debug('GAP-02: Trace iniciado — trace_id=@trace_id.', [
      '@trace_id' => $this->traceId,
    ]);

    return $this->traceId;
  }

  /**
   * Devuelve el trace_id del request actual.
   *
   * @return string|null
   *   El trace_id o NULL si no se ha iniciado trace.
   */
  public function getCurrentTraceId(): ?string {
    return $this->traceId;
  }

  /**
   * Inicia un nuevo span dentro del trace actual.
   *
   * Un span representa una operacion individual: llamada a agente,
   * query RAG, ejecucion de tool, evaluacion de calidad, etc.
   *
   * @param string $operationName
   *   Nombre de la operacion (ej. 'SmartBaseAgent.execute',
   *   'JarabaRagService.query', 'ToolRegistry.execute.SendEmail').
   * @param string|null $parentSpanId
   *   ID del span padre. Si NULL, usa el span activo actual.
   *   Si no hay span activo, este sera un span raiz del trace.
   *
   * @return string
   *   El span_id generado (UUID v4).
   */
  public function startSpan(string $operationName, ?string $parentSpanId = NULL): string {
    // Auto-trace: si no hay trace activo, crear uno.
    if ($this->traceId === NULL) {
      $this->startTrace();
    }

    $spanId = $this->uuid->generate();
    $parentId = $parentSpanId ?? $this->activeSpanId;

    $this->spans[$spanId] = [
      'operation' => $operationName,
      'parent' => $parentId,
      'start' => microtime(TRUE),
    ];

    // El nuevo span se convierte en el activo (para auto-parenting).
    $this->activeSpanId = $spanId;

    return $spanId;
  }

  /**
   * Finaliza un span y calcula su duracion.
   *
   * @param string $spanId
   *   ID del span a cerrar.
   *
   * @return int
   *   Duracion del span en milisegundos.
   */
  public function endSpan(string $spanId): int {
    if (!isset($this->spans[$spanId])) {
      $this->logger->warning('GAP-02: Intento de cerrar span no existente: @span_id.', [
        '@span_id' => $spanId,
      ]);
      return 0;
    }

    $startTime = $this->spans[$spanId]['start'];
    $durationMs = (int) round((microtime(TRUE) - $startTime) * 1000);

    // Si cerramos el span activo, restaurar el padre como activo.
    if ($this->activeSpanId === $spanId) {
      $this->activeSpanId = $this->spans[$spanId]['parent'];
    }

    return $durationMs;
  }

  /**
   * Devuelve el span_id activo actual.
   *
   * @return string|null
   *   El span_id del span abierto mas reciente, o NULL.
   */
  public function getActiveSpanId(): ?string {
    return $this->activeSpanId;
  }

  /**
   * Devuelve el parent_span_id de un span dado.
   *
   * @param string $spanId
   *   ID del span.
   *
   * @return string|null
   *   El parent_span_id o NULL si es raiz.
   */
  public function getParentSpanId(string $spanId): ?string {
    return $this->spans[$spanId]['parent'] ?? NULL;
  }

  /**
   * Devuelve la operacion de un span dado.
   *
   * @param string $spanId
   *   ID del span.
   *
   * @return string
   *   El nombre de la operacion.
   */
  public function getOperationName(string $spanId): string {
    return $this->spans[$spanId]['operation'] ?? 'unknown';
  }

  /**
   * Genera datos de contexto de tracing para pasar a log().
   *
   * Metodo de conveniencia que devuelve el array con los campos
   * de tracing para merge con los datos de log de observabilidad.
   *
   * @param string|null $spanId
   *   Span actual. Si NULL, usa el activo.
   *
   * @return array
   *   Array con trace_id, span_id, parent_span_id, operation_name.
   */
  public function getSpanContext(?string $spanId = NULL): array {
    $targetSpanId = $spanId ?? $this->activeSpanId;

    if ($targetSpanId === NULL || !isset($this->spans[$targetSpanId])) {
      return [
        'trace_id' => $this->traceId,
        'span_id' => NULL,
        'parent_span_id' => NULL,
        'operation_name' => NULL,
      ];
    }

    return [
      'trace_id' => $this->traceId,
      'span_id' => $targetSpanId,
      'parent_span_id' => $this->spans[$targetSpanId]['parent'],
      'operation_name' => $this->spans[$targetSpanId]['operation'],
    ];
  }

}
