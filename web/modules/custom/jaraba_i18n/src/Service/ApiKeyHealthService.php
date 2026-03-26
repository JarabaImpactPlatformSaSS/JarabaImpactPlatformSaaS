<?php

declare(strict_types=1);

namespace Drupal\jaraba_i18n\Service;

use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * API-KEY-ROTATION-001: Monitorea salud de API keys de traduccion.
 *
 * Registra fallos consecutivos de la API de Anthropic durante traducciones.
 * Cuando se alcanzan 3+ fallos consecutivos, emite alerta critical en watchdog
 * para que StatusReportMonitorService lo capture y envie email.
 *
 * Patron: circuit breaker basico — no intenta usar la API cuando esta en
 * estado "abierto" (3+ fallos), ahorrando coste y latencia.
 *
 * Consumido por:
 * - AITranslationService (reporta fallos y exitos)
 * - jaraba_i18n_cron (verifica estado periodicamente)
 */
class ApiKeyHealthService {

  /**
   * Umbral de fallos consecutivos para alerta critical.
   */
  protected const FAILURE_THRESHOLD = 3;

  /**
   * Tiempo de cooldown en segundos antes de reintentar tras circuit open.
   */
  protected const COOLDOWN_SECONDS = 3600;

  /**
   * Claves de estado en Drupal State.
   */
  protected const STATE_FAILURES = 'jaraba_i18n.api_key_consecutive_failures';
  protected const STATE_LAST_FAILURE = 'jaraba_i18n.api_key_last_failure_time';
  protected const STATE_LAST_ERROR = 'jaraba_i18n.api_key_last_error_message';
  protected const STATE_ALERTED = 'jaraba_i18n.api_key_alert_sent';

  public function __construct(
    protected StateInterface $state,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Registra un fallo de API key.
   *
   * Llamado por AITranslationService cuando la traduccion falla por API key.
   */
  public function recordFailure(string $errorMessage): void {
    $failures = (int) $this->state->get(self::STATE_FAILURES, 0);
    $failures++;
    $this->state->set(self::STATE_FAILURES, $failures);
    $this->state->set(self::STATE_LAST_FAILURE, time());
    $this->state->set(self::STATE_LAST_ERROR, mb_substr($errorMessage, 0, 500));

    $this->logger->warning('API key failure #{count}: {error}', [
      'count' => $failures,
      'error' => mb_substr($errorMessage, 0, 200),
    ]);

    // Emitir alerta critical al alcanzar umbral (solo una vez).
    if ($failures >= self::FAILURE_THRESHOLD && (bool) $this->state->get(self::STATE_ALERTED, FALSE) === FALSE) {
      $this->logger->critical(
        'API-KEY-ROTATION-001: La API key de Anthropic ha fallado @count veces consecutivas. '
        . 'El sistema de traduccion automatica esta INOPERATIVO. '
        . 'Ultimo error: @error. '
        . 'Accion requerida: verificar/renovar ANTHROPIC_API_KEY en settings.env.php.',
        [
          '@count' => $failures,
          '@error' => mb_substr($errorMessage, 0, 200),
        ]
      );
      $this->state->set(self::STATE_ALERTED, TRUE);
    }
  }

  /**
   * Registra un exito de API key — resetea el contador.
   *
   * Llamado por AITranslationService cuando la traduccion tiene exito.
   */
  public function recordSuccess(): void {
    $wasInFailure = (int) $this->state->get(self::STATE_FAILURES, 0) > 0;

    if ($wasInFailure) {
      $this->state->set(self::STATE_FAILURES, 0);
      $this->state->set(self::STATE_ALERTED, FALSE);
      $this->logger->info('API-KEY-ROTATION-001: API key de Anthropic recuperada. Traducciones operativas.');
    }
  }

  /**
   * Verifica si el circuit breaker esta abierto (API no disponible).
   *
   * @return bool
   *   TRUE si la API no debe usarse (circuit open).
   */
  public function isCircuitOpen(): bool {
    $failures = (int) $this->state->get(self::STATE_FAILURES, 0);
    if ($failures < self::FAILURE_THRESHOLD) {
      return FALSE;
    }

    // Permitir reintento despues del cooldown.
    $lastFailure = (int) $this->state->get(self::STATE_LAST_FAILURE, 0);
    if ((time() - $lastFailure) > self::COOLDOWN_SECONDS) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Devuelve el estado de salud para diagnostico.
   *
   * @return array{failures: int, last_failure: int, last_error: string, circuit_open: bool}
   */
  public function getHealthStatus(): array {
    return [
      'failures' => (int) $this->state->get(self::STATE_FAILURES, 0),
      'last_failure' => (int) $this->state->get(self::STATE_LAST_FAILURE, 0),
      'last_error' => (string) $this->state->get(self::STATE_LAST_ERROR, ''),
      'circuit_open' => $this->isCircuitOpen(),
    ];
  }

}
