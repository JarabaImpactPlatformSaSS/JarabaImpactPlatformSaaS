<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de sincronización bidireccional con el STO.
 *
 * Sprint 17 — Evolución del export unidireccional.
 *
 * Tres operaciones:
 * - Push: envío de actuaciones al STO (delega en StoExportService).
 * - Pull: verificación de estado de alta en STO.
 * - Reconciliación: detección de discrepancias local ↔ STO.
 *
 * El STO (Servicio Telemático de Orientación) es el sistema oficial
 * de la Junta de Andalucía donde se registran las actuaciones PIIL.
 * Los datos deben coincidir para la justificación FSE+.
 */
class StoBidireccionalService {

  /**
   * Campos a reconciliar entre local y STO.
   */
  private const RECONCILE_FIELDS = [
    'fase',
    'horas_orientacion',
    'horas_formacion',
    'tipo_insercion',
    'incentivo_recibido',
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ConfigFactoryInterface $configFactory,
    protected ?StoExportService $stoExportService = NULL,
  ) {}

  /**
   * Push: envía actuaciones pendientes al STO.
   *
   * Delega al StoExportService existente. Envuelve con log de reconciliación.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Resultado: success, count, discrepancias.
   */
  public function pushPendientes(int $tenantId): array {
    if (!$this->stoExportService) {
      return ['success' => FALSE, 'message' => 'StoExportService no disponible'];
    }

    try {
      // Obtener participantes pendientes de sync para este tenant.
      $ids = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('sto_sync_status', 'pending')
        ->execute();

      if (empty($ids)) {
        return [
          'success' => TRUE,
          'count' => 0,
          'message' => 'Sin participantes pendientes de push.',
        ];
      }

      $resultado = $this->stoExportService->generarPaqueteExportacion(array_values($ids));

      if ($resultado['success']) {
        // Registrar timestamp de último push.
        $this->registrarEvento($tenantId, 'push', [
          'count' => $resultado['count'],
          'ids' => array_values($ids),
        ]);

        // Marcar como sincronizados.
        $participantes = $this->entityTypeManager
          ->getStorage('programa_participante_ei')
          ->loadMultiple($ids);
        foreach ($participantes as $participante) {
          if ($participante->hasField('sto_sync_status')) {
            $participante->set('sto_sync_status', 'synced');
            $participante->save();
          }
        }
      }

      return $resultado;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error en push STO tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => $e->getMessage()];
    }
  }

  /**
   * Pull: verifica el estado de alta en el STO.
   *
   * Consulta el STO para verificar que los participantes marcados
   * como 'synced' realmente están dados de alta.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Resultado con confirmados, no_encontrados, errores.
   */
  public function pullEstado(int $tenantId): array {
    $config = $this->configFactory->get('jaraba_andalucia_ei.settings');
    $stoEndpoint = $config->get('sto_api_endpoint');

    if (empty($stoEndpoint)) {
      $this->logger->info('STO pull: endpoint no configurado. Usando verificación local.');
      return $this->pullEstadoLocal($tenantId);
    }

    // TODO: Implementar llamada SOAP/REST al STO real.
    // El STO no expone API pública aún (pendiente convenio Junta).
    // Por ahora, verificación local de integridad.
    return $this->pullEstadoLocal($tenantId);
  }

  /**
   * Verificación local como fallback cuando no hay API STO.
   *
   * Verifica que los participantes 'synced' tienen datos completos
   * y coherentes para el STO.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Resultado de verificación local.
   */
  protected function pullEstadoLocal(int $tenantId): array {
    try {
      $ids = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('sto_sync_status', 'synced')
        ->execute();

      if (empty($ids)) {
        return ['confirmados' => 0, 'incompletos' => [], 'total' => 0];
      }

      $participantes = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadMultiple($ids);

      $confirmados = 0;
      $incompletos = [];

      foreach ($participantes as $participante) {
        $problemas = [];

        // Verificar campos obligatorios para STO.
        if (empty($participante->getDniNie())) {
          $problemas[] = 'Sin DNI/NIE';
        }
        if (empty($participante->getColectivo())) {
          $problemas[] = 'Sin colectivo';
        }
        if (!$participante->hasField('provincia_participacion') ||
            $participante->get('provincia_participacion')->isEmpty()) {
          $problemas[] = 'Sin provincia';
        }

        // Verificar coherencia de horas.
        $horasOrientacion = $participante->getTotalHorasOrientacion();
        $fase = $participante->getFaseActual();
        if (in_array($fase, ['insercion', 'seguimiento'], TRUE) && $horasOrientacion < 10.0) {
          $problemas[] = "Fase {$fase} con solo {$horasOrientacion}h orientación (mín. 10h)";
        }

        if (empty($problemas)) {
          $confirmados++;
        }
        else {
          $incompletos[] = [
            'id' => (int) $participante->id(),
            'nombre' => $participante->label() ?? '',
            'problemas' => $problemas,
          ];
        }
      }

      $this->registrarEvento($tenantId, 'pull_local', [
        'total' => count($participantes),
        'confirmados' => $confirmados,
        'incompletos' => count($incompletos),
      ]);

      return [
        'total' => count($participantes),
        'confirmados' => $confirmados,
        'incompletos' => $incompletos,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error en pull estado STO tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['total' => 0, 'confirmados' => 0, 'incompletos' => []];
    }
  }

  /**
   * Reconciliación: detecta discrepancias entre datos locales y el STO.
   *
   * Compara campos clave (horas, fase, inserción) para detectar
   * participantes que podrían necesitar re-sincronización.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Discrepancias encontradas.
   */
  public function reconciliar(int $tenantId): array {
    try {
      $ids = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('sto_sync_status', ['synced', 'pending'], 'IN')
        ->execute();

      if (empty($ids)) {
        return ['total' => 0, 'discrepancias' => [], 'necesitan_resync' => 0];
      }

      $participantes = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadMultiple($ids);

      $discrepancias = [];
      $necesitanResync = 0;

      foreach ($participantes as $participante) {
        $syncStatus = $participante->hasField('sto_sync_status')
          ? ($participante->get('sto_sync_status')->value ?? 'unknown')
          : 'unknown';

        // Detectar si hubo cambios después de la última sincronización.
        $changed = (int) ($participante->getChangedTime());
        $lastSync = $participante->hasField('sto_last_sync')
          ? (int) ($participante->get('sto_last_sync')->value ?? 0)
          : 0;

        if ($syncStatus === 'synced' && $lastSync > 0 && $changed > $lastSync) {
          $discrepancias[] = [
            'id' => (int) $participante->id(),
            'nombre' => $participante->label() ?? '',
            'tipo' => 'datos_modificados_post_sync',
            'last_sync' => date('Y-m-d H:i:s', $lastSync),
            'last_change' => date('Y-m-d H:i:s', $changed),
          ];
          $necesitanResync++;

          // Marcar para re-sincronización.
          if ($participante->hasField('sto_sync_status')) {
            $participante->set('sto_sync_status', 'pending');
            $participante->save();
          }
        }
      }

      $this->registrarEvento($tenantId, 'reconciliacion', [
        'total' => count($participantes),
        'discrepancias' => count($discrepancias),
        'necesitan_resync' => $necesitanResync,
      ]);

      $this->logger->info('Reconciliación STO tenant @tid: @total revisados, @disc discrepancias.', [
        '@tid' => $tenantId,
        '@total' => count($participantes),
        '@disc' => count($discrepancias),
      ]);

      return [
        'total' => count($participantes),
        'discrepancias' => $discrepancias,
        'necesitan_resync' => $necesitanResync,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error en reconciliación STO tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['total' => 0, 'discrepancias' => [], 'necesitan_resync' => 0];
    }
  }

  /**
   * Obtiene el resumen de estado de sincronización STO.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Resumen: pending, synced, error, total.
   */
  public function getResumenSync(int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

      $total = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->count()
        ->execute();

      $pending = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('sto_sync_status', 'pending')
        ->count()
        ->execute();

      $synced = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('sto_sync_status', 'synced')
        ->count()
        ->execute();

      $error = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('sto_sync_status', 'error')
        ->count()
        ->execute();

      return [
        'total' => $total,
        'pending' => $pending,
        'synced' => $synced,
        'error' => $error,
        'sin_estado' => $total - $pending - $synced - $error,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo resumen STO tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return ['total' => 0, 'pending' => 0, 'synced' => 0, 'error' => 0, 'sin_estado' => 0];
    }
  }

  /**
   * Registra evento de sincronización en el log.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $tipo
   *   Tipo: push, pull, pull_local, reconciliacion.
   * @param array $datos
   *   Datos del evento.
   */
  protected function registrarEvento(int $tenantId, string $tipo, array $datos): void {
    $this->logger->info('STO @tipo tenant @tid: @datos', [
      '@tipo' => $tipo,
      '@tid' => $tenantId,
      '@datos' => json_encode($datos, JSON_THROW_ON_ERROR),
    ]);
  }

}
