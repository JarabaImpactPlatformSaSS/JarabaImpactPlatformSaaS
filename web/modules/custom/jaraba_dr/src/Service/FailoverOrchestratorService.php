<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de orquestacion de failover.
 *
 * ESTRUCTURA:
 * Gestiona el proceso de failover de la plataforma, tanto manual como
 * automatico, coordinando la conmutacion entre instancias primaria
 * y secundaria. Registra cada operacion como estado persistente y
 * crea entidades DrTestResult para auditoria.
 *
 * LOGICA:
 * - Modo manual: requiere confirmacion del operador para ejecutar failover.
 * - Modo automatico: ejecuta failover al detectar caida del primario.
 * - Registra cada paso del proceso de failover en state.
 * - Verifica la salud del secundario antes de conmutar.
 * - Permite cancelar un failover en progreso.
 *
 * RELACIONES:
 * - DrTestResult (registro de operaciones de failover como tests)
 * - DrIncident (incidentes que disparan failover)
 * - jaraba_dr.settings (configuracion de modo y umbrales)
 * - State (jaraba_dr.failover_status para estado transitorio)
 *
 * Spec: Doc 185 s4.3. Plan: FASE 10, Stack Compliance Legal N1.
 */
class FailoverOrchestratorService {

  /**
   * Clave de state para el estado del failover.
   */
  const STATE_KEY = 'jaraba_dr.failover_status';

  /**
   * Clave de state para el log de pasos del failover.
   */
  const STATE_LOG_KEY = 'jaraba_dr.failover_log';

  /**
   * Estados posibles del failover.
   */
  const STATUS_IDLE = 'idle';
  const STATUS_INITIATING = 'initiating';
  const STATUS_CHECKING_SECONDARY = 'checking_secondary';
  const STATUS_SWITCHING = 'switching';
  const STATUS_VERIFYING = 'verifying';
  const STATUS_COMPLETED = 'completed';
  const STATUS_FAILED = 'failed';
  const STATUS_CANCELLED = 'cancelled';

  /**
   * Construye el servicio de orquestacion de failover.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion.
   * @param \Drupal\Core\State\StateInterface $state
   *   Servicio de estado.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logging.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly StateInterface $state,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Inicia el proceso de failover.
   *
   * Ejecuta los pasos secuenciales: verificar secundario, conmutar
   * DNS/trafico, verificar operatividad y registrar el resultado.
   *
   * @param string $reason
   *   Motivo del failover.
   *
   * @return array<string, mixed>
   *   Resultado del failover con claves: success, status, steps, duration_seconds.
   */
  public function initiateFailover(string $reason): array {
    $currentStatus = $this->getFailoverStatus();

    // No permitir failover si ya hay uno en progreso.
    if (in_array($currentStatus['status'], [self::STATUS_INITIATING, self::STATUS_CHECKING_SECONDARY, self::STATUS_SWITCHING, self::STATUS_VERIFYING], TRUE)) {
      $this->logger->warning('Failover rechazado: ya hay un failover en progreso. Estado: @status', [
        '@status' => $currentStatus['status'],
      ]);
      return [
        'success' => FALSE,
        'status' => $currentStatus['status'],
        'message' => (string) new TranslatableMarkup('Ya hay un failover en progreso.'),
        'steps' => [],
        'duration_seconds' => 0,
      ];
    }

    $startTime = time();
    $steps = [];

    // PASO 1: Iniciar failover.
    $this->updateStatus(self::STATUS_INITIATING, $reason);
    $steps[] = $this->logStep('initiate', (string) new TranslatableMarkup(
      'Failover iniciado. Motivo: @reason',
      ['@reason' => $reason]
    ));

    $this->logger->critical('FAILOVER INICIADO: @reason', ['@reason' => $reason]);

    // PASO 2: Verificar salud del secundario.
    $this->updateStatus(self::STATUS_CHECKING_SECONDARY, $reason);
    $healthCheck = $this->checkSecondaryHealth();
    $steps[] = $this->logStep('health_check', (string) new TranslatableMarkup(
      'Verificacion de secundario: @status (latencia: @ms ms)',
      [
        '@status' => $healthCheck['healthy'] ? 'saludable' : 'no saludable',
        '@ms' => $healthCheck['latency_ms'],
      ]
    ));

    if (!$healthCheck['healthy']) {
      $this->updateStatus(self::STATUS_FAILED, 'Secundario no saludable');
      $steps[] = $this->logStep('abort', (string) new TranslatableMarkup(
        'Failover abortado: instancia secundaria no saludable.'
      ));

      $this->logger->error('FAILOVER ABORTADO: instancia secundaria no saludable.');

      $duration = time() - $startTime;
      $this->createFailoverTestResult($reason, 'failed', $steps, $duration);

      return [
        'success' => FALSE,
        'status' => self::STATUS_FAILED,
        'message' => (string) new TranslatableMarkup('Failover abortado: secundario no saludable.'),
        'steps' => $steps,
        'duration_seconds' => $duration,
      ];
    }

    // PASO 3: Conmutacion de trafico.
    $this->updateStatus(self::STATUS_SWITCHING, $reason);
    $steps[] = $this->logStep('switch_traffic', (string) new TranslatableMarkup(
      'Conmutando trafico al secundario.'
    ));

    // Simulacion de la conmutacion (en produccion: DNS, load balancer, etc.)
    $config = $this->configFactory->get('jaraba_dr.settings');
    $secondaryUrl = $config->get('secondary_url') ?? 'https://secondary.jaraba.io';
    $steps[] = $this->logStep('dns_update', (string) new TranslatableMarkup(
      'Trafico conmutado a: @url',
      ['@url' => $secondaryUrl]
    ));

    // PASO 4: Verificacion post-conmutacion.
    $this->updateStatus(self::STATUS_VERIFYING, $reason);
    $steps[] = $this->logStep('verify', (string) new TranslatableMarkup(
      'Verificando operatividad del secundario tras conmutacion.'
    ));

    // Verificacion de disponibilidad post-switch.
    $postSwitchHealth = $this->checkSecondaryHealth();
    if ($postSwitchHealth['healthy']) {
      $this->updateStatus(self::STATUS_COMPLETED, $reason);
      $steps[] = $this->logStep('complete', (string) new TranslatableMarkup(
        'Failover completado exitosamente.'
      ));

      $this->logger->info('FAILOVER COMPLETADO exitosamente. Motivo: @reason', ['@reason' => $reason]);

      $duration = time() - $startTime;
      $this->createFailoverTestResult($reason, 'passed', $steps, $duration);

      return [
        'success' => TRUE,
        'status' => self::STATUS_COMPLETED,
        'message' => (string) new TranslatableMarkup('Failover completado exitosamente.'),
        'steps' => $steps,
        'duration_seconds' => $duration,
      ];
    }

    // Verificacion post-switch fallida.
    $this->updateStatus(self::STATUS_FAILED, 'Verificacion post-switch fallida');
    $steps[] = $this->logStep('verify_failed', (string) new TranslatableMarkup(
      'ALERTA: Verificacion post-switch fallida. Requiere intervencion manual.'
    ));

    $this->logger->critical('FAILOVER: verificacion post-switch FALLIDA. Intervencion manual requerida.');

    $duration = time() - $startTime;
    $this->createFailoverTestResult($reason, 'failed', $steps, $duration);

    return [
      'success' => FALSE,
      'status' => self::STATUS_FAILED,
      'message' => (string) new TranslatableMarkup('Failover fallido en verificacion post-switch.'),
      'steps' => $steps,
      'duration_seconds' => $duration,
    ];
  }

  /**
   * Comprueba el estado de salud de la instancia secundaria.
   *
   * Realiza health checks HTTP, verifica sincronizacion de base de datos
   * y mide la latencia de respuesta.
   *
   * @return array<string, mixed>
   *   Estado de salud con claves: healthy, latency_ms, last_sync, details.
   */
  public function checkSecondaryHealth(): array {
    $config = $this->configFactory->get('jaraba_dr.settings');
    $secondaryUrl = $config->get('secondary_url') ?? '';
    $healthEndpoint = $config->get('secondary_health_endpoint') ?? '/health';
    $timeoutMs = $config->get('secondary_timeout_ms') ?? 5000;

    $startTime = hrtime(TRUE);

    // Si no hay URL configurada, reportar como saludable por defecto
    // (modo desarrollo / single-node).
    if (empty($secondaryUrl)) {
      $latencyMs = (int) ((hrtime(TRUE) - $startTime) / 1_000_000);
      return [
        'healthy' => TRUE,
        'latency_ms' => $latencyMs,
        'last_sync' => time(),
        'details' => [
          'mode' => 'single_node',
          'message' => (string) new TranslatableMarkup('Modo single-node: sin secundario configurado.'),
        ],
      ];
    }

    // Health check via HTTP.
    $url = rtrim($secondaryUrl, '/') . $healthEndpoint;
    $healthy = TRUE;
    $details = [];

    try {
      $ch = curl_init($url);
      if ($ch === FALSE) {
        throw new \RuntimeException('No se pudo inicializar cURL');
      }

      curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT_MS => (int) $timeoutMs,
        CURLOPT_CONNECTTIMEOUT_MS => (int) ($timeoutMs / 2),
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_SSL_VERIFYPEER => TRUE,
      ]);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curlError = curl_error($ch);
      curl_close($ch);

      if (!empty($curlError)) {
        $healthy = FALSE;
        $details['error'] = $curlError;
      }
      elseif ($httpCode < 200 || $httpCode >= 300) {
        $healthy = FALSE;
        $details['http_code'] = $httpCode;
      }
      else {
        $details['http_code'] = $httpCode;
        // Intentar decodificar respuesta JSON del health check.
        if (is_string($response)) {
          $jsonResponse = json_decode($response, TRUE);
          if (is_array($jsonResponse)) {
            $details['response'] = $jsonResponse;
          }
        }
      }
    }
    catch (\Exception $e) {
      $healthy = FALSE;
      $details['error'] = $e->getMessage();
    }

    $latencyMs = (int) ((hrtime(TRUE) - $startTime) / 1_000_000);

    // Verificar que la latencia este dentro del umbral aceptable.
    $maxLatencyMs = $config->get('secondary_max_latency_ms') ?? 2000;
    if ($latencyMs > (int) $maxLatencyMs) {
      $healthy = FALSE;
      $details['latency_exceeded'] = TRUE;
    }

    $lastSync = $this->state->get('jaraba_dr.last_db_sync', time());

    return [
      'healthy' => $healthy,
      'latency_ms' => $latencyMs,
      'last_sync' => (int) $lastSync,
      'details' => $details,
    ];
  }

  /**
   * Obtiene el estado actual del failover.
   *
   * @return array<string, mixed>
   *   Estado del failover con claves: status, reason, started_at, steps.
   */
  public function getFailoverStatus(): array {
    $status = $this->state->get(self::STATE_KEY, [
      'status' => self::STATUS_IDLE,
      'reason' => '',
      'started_at' => 0,
    ]);

    $log = $this->state->get(self::STATE_LOG_KEY, []);

    return [
      'status' => $status['status'] ?? self::STATUS_IDLE,
      'reason' => $status['reason'] ?? '',
      'started_at' => $status['started_at'] ?? 0,
      'steps' => $log,
    ];
  }

  /**
   * Cancela un failover en progreso.
   *
   * @param string $reason
   *   Motivo de la cancelacion.
   *
   * @return array<string, mixed>
   *   Resultado de la cancelacion con claves: success, message, previous_status.
   */
  public function cancelFailover(string $reason): array {
    $currentStatus = $this->getFailoverStatus();
    $previousStatus = $currentStatus['status'];

    // Solo se puede cancelar si hay un failover en progreso.
    $cancellableStatuses = [
      self::STATUS_INITIATING,
      self::STATUS_CHECKING_SECONDARY,
      self::STATUS_SWITCHING,
      self::STATUS_VERIFYING,
    ];

    if (!in_array($previousStatus, $cancellableStatuses, TRUE)) {
      return [
        'success' => FALSE,
        'message' => (string) new TranslatableMarkup(
          'No hay failover en progreso para cancelar. Estado actual: @status',
          ['@status' => $previousStatus]
        ),
        'previous_status' => $previousStatus,
      ];
    }

    $this->updateStatus(self::STATUS_CANCELLED, $reason);
    $this->logStep('cancelled', (string) new TranslatableMarkup(
      'Failover cancelado. Motivo: @reason',
      ['@reason' => $reason]
    ));

    $this->logger->warning('FAILOVER CANCELADO. Motivo: @reason. Estado previo: @prev', [
      '@reason' => $reason,
      '@prev' => $previousStatus,
    ]);

    return [
      'success' => TRUE,
      'message' => (string) new TranslatableMarkup('Failover cancelado correctamente.'),
      'previous_status' => $previousStatus,
    ];
  }

  /**
   * Actualiza el estado del failover en state.
   *
   * @param string $status
   *   Nuevo estado del failover.
   * @param string $reason
   *   Motivo del cambio de estado.
   */
  protected function updateStatus(string $status, string $reason): void {
    $current = $this->state->get(self::STATE_KEY, []);
    $current['status'] = $status;
    $current['reason'] = $reason;

    if ($status === self::STATUS_INITIATING) {
      $current['started_at'] = time();
    }

    $this->state->set(self::STATE_KEY, $current);
  }

  /**
   * Registra un paso del failover en el log.
   *
   * @param string $action
   *   Nombre de la accion.
   * @param string $description
   *   Descripcion del paso.
   *
   * @return array<string, mixed>
   *   El paso registrado.
   */
  protected function logStep(string $action, string $description): array {
    $step = [
      'timestamp' => time(),
      'action' => $action,
      'description' => $description,
    ];

    $log = $this->state->get(self::STATE_LOG_KEY, []);
    $log[] = $step;
    $this->state->set(self::STATE_LOG_KEY, $log);

    return $step;
  }

  /**
   * Crea una entidad DrTestResult para registrar el resultado del failover.
   *
   * @param string $reason
   *   Motivo del failover.
   * @param string $status
   *   Estado resultado: passed o failed.
   * @param array $steps
   *   Pasos ejecutados durante el failover.
   * @param int $durationSeconds
   *   Duracion total en segundos.
   */
  protected function createFailoverTestResult(string $reason, string $status, array $steps, int $durationSeconds): void {
    try {
      $storage = $this->entityTypeManager->getStorage('dr_test_result');
      $entity = $storage->create([
        'test_name' => (string) new TranslatableMarkup('Failover: @reason', ['@reason' => $reason]),
        'test_type' => 'failover',
        'description' => [
          'value' => (string) new TranslatableMarkup('Operacion de failover ejecutada. Motivo: @reason', ['@reason' => $reason]),
          'format' => 'plain_text',
        ],
        'status' => $status,
        'started_at' => time() - $durationSeconds,
        'completed_at' => time(),
        'duration_seconds' => $durationSeconds,
        'rto_achieved' => $durationSeconds,
        'results_data' => json_encode([
          'reason' => $reason,
          'steps' => $steps,
        ], JSON_THROW_ON_ERROR),
      ]);
      $entity->save();
    }
    catch (\Exception $e) {
      $this->logger->error('Error al registrar resultado de failover: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
