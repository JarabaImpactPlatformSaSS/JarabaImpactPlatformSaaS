<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de seguimiento de errores para el Insights Hub.
 *
 * Captura, deduplica y gestiona errores de JavaScript, PHP y APIs
 * por tenant. Usa hashing SHA-256 para deduplicacion: si un error
 * ya existe con el mismo hash, se incrementa el contador de ocurrencias
 * en lugar de crear un registro nuevo.
 *
 * ARQUITECTURA:
 * - Deduplicacion por error_hash (SHA-256 de type+message+stackTrace).
 * - Ciclo de vida: open -> acknowledged -> resolved / ignored.
 * - Multi-tenant: cada error se asocia al tenant_id actual.
 * - Estadisticas en tiempo real para el dashboard.
 */
class ErrorTrackingService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   Servicio de contexto del tenant actual.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Registra o incrementa un error capturado.
   *
   * Si ya existe un error con el mismo hash y tenant, incrementa
   * el contador de ocurrencias y actualiza last_seen_at. Si no
   * existe, crea un nuevo registro InsightsErrorLog.
   *
   * @param array $data
   *   Datos del error. Claves esperadas:
   *   - error_type: (string) 'js', 'php' o 'api'.
   *   - message: (string) Mensaje descriptivo del error.
   *   - severity: (string) 'error', 'warning' o 'info'. Default: 'error'.
   *   - stack_trace: (string|null) Stack trace completo.
   *   - file_path: (string|null) Archivo donde ocurrio.
   *   - line_number: (int|null) Linea del error.
   *   - url: (string|null) URL donde se produjo el error.
   *
   * @return bool
   *   TRUE si el error se registro correctamente.
   */
  public function trackError(array $data): bool {
    if (empty($data['error_type']) || empty($data['message'])) {
      $this->logger->debug('Error tracking: faltan campos requeridos (error_type, message).');
      return FALSE;
    }

    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      $this->logger->warning('Error tracking: no se pudo resolver el tenant actual.');
      return FALSE;
    }

    $tenantId = (int) $tenant->id();
    $errorHash = $this->generateHash(
      $data['error_type'],
      $data['message'],
      $data['stack_trace'] ?? NULL
    );

    try {
      $storage = $this->entityTypeManager->getStorage('insights_error_log');
      $now = \Drupal::time()->getRequestTime();

      // Buscar error existente con el mismo hash para este tenant.
      $existing = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('error_hash', $errorHash)
        ->range(0, 1)
        ->execute();

      if (!empty($existing)) {
        // Incrementar ocurrencias del error existente.
        $entity = $storage->load(reset($existing));
        $occurrences = (int) $entity->get('occurrences')->value;
        $entity->set('occurrences', $occurrences + 1);
        $entity->set('last_seen_at', $now);

        // Si estaba resuelto o ignorado y vuelve a aparecer, reabrirlo.
        $currentStatus = $entity->get('status')->value;
        if (in_array($currentStatus, ['resolved', 'ignored'], TRUE)) {
          $entity->set('status', 'open');
          $this->logger->notice('Error @hash reabierto por nueva ocurrencia (tenant @tenant).', [
            '@hash' => substr($errorHash, 0, 12),
            '@tenant' => $tenantId,
          ]);
        }

        $entity->save();
        return TRUE;
      }

      // Crear nuevo registro de error.
      $entity = $storage->create([
        'tenant_id' => $tenantId,
        'error_hash' => $errorHash,
        'error_type' => $data['error_type'],
        'severity' => $data['severity'] ?? 'error',
        'message' => $data['message'],
        'stack_trace' => $data['stack_trace'] ?? NULL,
        'file_path' => $data['file_path'] ?? NULL,
        'line_number' => $data['line_number'] ?? NULL,
        'url' => $data['url'] ?? NULL,
        'occurrences' => 1,
        'first_seen_at' => $now,
        'last_seen_at' => $now,
        'status' => 'open',
      ]);
      $entity->save();

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error almacenando error log para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Genera un hash determinista para deduplicacion de errores.
   *
   * El hash se calcula a partir del tipo, mensaje y (opcionalmente)
   * el stack trace del error. Esto permite agrupar errores identicos.
   *
   * @param string $type
   *   Tipo de error ('js', 'php', 'api').
   * @param string $message
   *   Mensaje descriptivo del error.
   * @param string|null $stackTrace
   *   Stack trace completo o NULL.
   *
   * @return string
   *   Hash SHA-256 de 64 caracteres.
   */
  public function generateHash(string $type, string $message, ?string $stackTrace): string {
    $input = $type . '|' . $message;

    if (!empty($stackTrace)) {
      // Usar solo las primeras 5 lineas del stack trace para el hash,
      // ya que las lineas inferiores pueden variar entre ocurrencias.
      $lines = array_slice(explode("\n", trim($stackTrace)), 0, 5);
      $input .= '|' . implode("\n", $lines);
    }

    return hash('sha256', $input);
  }

  /**
   * Obtiene errores para un tenant con filtro por estado.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $status
   *   Estado de los errores a filtrar ('open', 'acknowledged', 'resolved', 'ignored').
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return array
   *   Array de errores con todas sus propiedades.
   */
  public function getErrorsForTenant(int $tenantId, string $status = 'open', int $limit = 50): array {
    try {
      $storage = $this->entityTypeManager->getStorage('insights_error_log');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', $status)
        ->sort('last_seen_at', 'DESC')
        ->range(0, $limit);

      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      $results = [];

      foreach ($entities as $entity) {
        $results[] = [
          'id' => (int) $entity->id(),
          'error_hash' => $entity->get('error_hash')->value,
          'error_type' => $entity->get('error_type')->value,
          'severity' => $entity->get('severity')->value,
          'message' => $entity->get('message')->value,
          'stack_trace' => $entity->get('stack_trace')->value,
          'file_path' => $entity->get('file_path')->value,
          'line_number' => $entity->get('line_number')->value ? (int) $entity->get('line_number')->value : NULL,
          'url' => $entity->get('url')->value,
          'occurrences' => (int) $entity->get('occurrences')->value,
          'first_seen_at' => (int) $entity->get('first_seen_at')->value,
          'last_seen_at' => (int) $entity->get('last_seen_at')->value,
          'status' => $entity->get('status')->value,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo errores para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene estadisticas de errores para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array con claves:
   *   - total_open: (int) Total de errores abiertos.
   *   - total_today: (int) Errores vistos hoy.
   *   - by_type: (array) Conteo por tipo (js, php, api).
   *   - by_severity: (array) Conteo por severidad (error, warning, info).
   */
  public function getErrorStats(int $tenantId): array {
    $stats = [
      'total_open' => 0,
      'total_today' => 0,
      'by_type' => ['js' => 0, 'php' => 0, 'api' => 0],
      'by_severity' => ['error' => 0, 'warning' => 0, 'info' => 0],
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('insights_error_log');

      // Total de errores abiertos.
      $openIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'open')
        ->execute();

      $stats['total_open'] = count($openIds);

      if (empty($openIds)) {
        return $stats;
      }

      $entities = $storage->loadMultiple($openIds);
      $todayStart = strtotime('today');

      foreach ($entities as $entity) {
        // Conteo por tipo.
        $type = $entity->get('error_type')->value;
        if (isset($stats['by_type'][$type])) {
          $stats['by_type'][$type]++;
        }

        // Conteo por severidad.
        $severity = $entity->get('severity')->value;
        if (isset($stats['by_severity'][$severity])) {
          $stats['by_severity'][$severity]++;
        }

        // Errores vistos hoy.
        $lastSeen = (int) $entity->get('last_seen_at')->value;
        if ($lastSeen >= $todayStart) {
          $stats['total_today']++;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estadisticas de errores para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $stats;
  }

  /**
   * Marca un error como resuelto.
   *
   * @param int $errorId
   *   ID de la entidad InsightsErrorLog.
   *
   * @return bool
   *   TRUE si se actualizo correctamente.
   */
  public function resolveError(int $errorId): bool {
    return $this->updateErrorStatus($errorId, 'resolved');
  }

  /**
   * Marca un error como ignorado.
   *
   * @param int $errorId
   *   ID de la entidad InsightsErrorLog.
   *
   * @return bool
   *   TRUE si se actualizo correctamente.
   */
  public function ignoreError(int $errorId): bool {
    return $this->updateErrorStatus($errorId, 'ignored');
  }

  /**
   * Actualiza el estado de un error.
   *
   * @param int $errorId
   *   ID de la entidad InsightsErrorLog.
   * @param string $newStatus
   *   Nuevo estado ('open', 'acknowledged', 'resolved', 'ignored').
   *
   * @return bool
   *   TRUE si se actualizo correctamente.
   */
  protected function updateErrorStatus(int $errorId, string $newStatus): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('insights_error_log');
      $entity = $storage->load($errorId);

      if (!$entity) {
        $this->logger->warning('Error tracking: error @id no encontrado.', [
          '@id' => $errorId,
        ]);
        return FALSE;
      }

      $previousStatus = $entity->get('status')->value;
      $entity->set('status', $newStatus);
      $entity->save();

      $this->logger->info('Error @id cambiado de @prev a @new.', [
        '@id' => $errorId,
        '@prev' => $previousStatus,
        '@new' => $newStatus,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error actualizando estado del error @id: @error', [
        '@id' => $errorId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
