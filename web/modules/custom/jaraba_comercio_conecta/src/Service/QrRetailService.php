<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

class QrRetailService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Crea un codigo QR con short_code auto-generado.
   *
   * Logica: Recibe datos del QR, genera un short_code unico si no se
   *   proporciona, crea y guarda la entidad comercio_qr_code.
   *
   * @param array $data
   *   Datos del QR: name, merchant_id, qr_type, target_url, etc.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   Entidad QR creada, o null si fallo.
   */
  public function createQrCode(array $data): ?ContentEntityInterface {
    $storage = $this->entityTypeManager->getStorage('comercio_qr_code');

    try {
      if (empty($data['short_code'])) {
        $data['short_code'] = $this->generateShortCode();
      }

      $qr = $storage->create($data);
      $qr->save();

      $this->logger->info('Codigo QR creado: @name (short_code: @code)', [
        '@name' => $data['name'] ?? '',
        '@code' => $data['short_code'],
      ]);

      return $qr;
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando codigo QR: @e', ['@e' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Resuelve un short_code a la URL destino.
   *
   * Logica: Busca el QR por short_code activo. Si tiene A/B testing,
   *   alterna entre target_url y ab_target_url segun ab_variant.
   *
   * @param string $shortCode
   *   Codigo corto del QR.
   *
   * @return array|null
   *   Array con qr_id, target_url, ab_variant, o null si no encontrado.
   */
  public function resolveShortCode(string $shortCode): ?array {
    $storage = $this->entityTypeManager->getStorage('comercio_qr_code');

    try {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('short_code', $shortCode)
        ->condition('is_active', 1)
        ->range(0, 1)
        ->execute();

      if (!$ids) {
        return NULL;
      }

      $qr = $storage->load(reset($ids));
      if (!$qr) {
        return NULL;
      }

      $target_url = $qr->get('target_url')->value;
      $ab_variant = $qr->get('ab_variant')->value;
      $ab_target_url = $qr->get('ab_target_url')->value;

      if ($ab_variant && $ab_target_url) {
        $use_variant_b = (random_int(0, 1) === 1);
        $resolved_url = $use_variant_b ? $ab_target_url : $target_url;
        $resolved_variant = $use_variant_b ? 'B' : 'A';
      }
      else {
        $resolved_url = $target_url;
        $resolved_variant = NULL;
      }

      return [
        'qr_id' => (int) $qr->id(),
        'target_url' => $resolved_url,
        'ab_variant' => $resolved_variant,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error resolviendo short_code @code: @e', [
        '@code' => $shortCode,
        '@e' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Registra un evento de escaneo de QR.
   *
   * Logica: Crea entidad comercio_qr_scan_event con datos del escaneo
   *   e incrementa el scan_count del QR.
   *
   * @param int $qrCodeId
   *   ID del QR escaneado.
   * @param int|null $userId
   *   ID del usuario (null si anonimo).
   * @param string $sessionId
   *   ID de sesion del navegador.
   * @param string $userAgent
   *   User-Agent del navegador.
   * @param float|null $lat
   *   Latitud del escaneo.
   * @param float|null $lng
   *   Longitud del escaneo.
   */
  public function recordScan(int $qrCodeId, ?int $userId, string $sessionId, string $userAgent, ?float $lat, ?float $lng): void {
    $scan_storage = $this->entityTypeManager->getStorage('comercio_qr_scan_event');
    $qr_storage = $this->entityTypeManager->getStorage('comercio_qr_code');

    try {
      $scan = $scan_storage->create([
        'qr_code_id' => $qrCodeId,
        'user_id' => $userId,
        'session_id' => $sessionId,
        'user_agent' => $userAgent,
        'scan_lat' => $lat,
        'scan_lng' => $lng,
        'scanned_at' => \Drupal::time()->getRequestTime(),
      ]);
      $scan->save();

      $qr = $qr_storage->load($qrCodeId);
      if ($qr) {
        $current_count = (int) $qr->get('scan_count')->value;
        $qr->set('scan_count', $current_count + 1);
        $qr->save();
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando escaneo QR @id: @e', [
        '@id' => $qrCodeId,
        '@e' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Captura un lead desde un escaneo QR.
   *
   * Logica: Crea entidad comercio_qr_lead_capture con los datos del
   *   formulario del lead (nombre, email, telefono, etc.).
   *
   * @param int $qrCodeId
   *   ID del QR de origen.
   * @param int $scanEventId
   *   ID del evento de escaneo asociado.
   * @param array $data
   *   Datos del lead: name, email, phone, etc.
   *
   * @return bool
   *   TRUE si se guardo el lead correctamente.
   */
  public function captureLead(int $qrCodeId, int $scanEventId, array $data): bool {
    $storage = $this->entityTypeManager->getStorage('comercio_qr_lead_capture');

    try {
      $lead = $storage->create(array_merge($data, [
        'qr_code_id' => $qrCodeId,
        'scan_event_id' => $scanEventId,
        'captured_at' => \Drupal::time()->getRequestTime(),
      ]));
      $lead->save();

      $this->logger->info('Lead capturado desde QR @qr, escaneo @scan', [
        '@qr' => $qrCodeId,
        '@scan' => $scanEventId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error capturando lead QR @id: @e', [
        '@id' => $qrCodeId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene estadisticas de un codigo QR.
   *
   * @param int $qrCodeId
   *   ID del QR.
   *
   * @return array
   *   Array con total_scans, unique_users, total_leads, ab_performance.
   */
  public function getQrStats(int $qrCodeId): array {
    $scan_storage = $this->entityTypeManager->getStorage('comercio_qr_scan_event');
    $lead_storage = $this->entityTypeManager->getStorage('comercio_qr_lead_capture');

    try {
      $total_scans = (int) $scan_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('qr_code_id', $qrCodeId)
        ->count()
        ->execute();

      $unique_user_scans = $scan_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('qr_code_id', $qrCodeId)
        ->condition('user_id', 0, '>')
        ->execute();

      $unique_users = count(array_unique(array_map(function ($id) use ($scan_storage) {
        $scan = $scan_storage->load($id);
        return $scan ? $scan->get('user_id')->target_id : NULL;
      }, $unique_user_scans)));

      $total_leads = (int) $lead_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('qr_code_id', $qrCodeId)
        ->count()
        ->execute();

      return [
        'total_scans' => $total_scans,
        'unique_users' => $unique_users,
        'total_leads' => $total_leads,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo stats QR @id: @e', [
        '@id' => $qrCodeId,
        '@e' => $e->getMessage(),
      ]);
      return [
        'total_scans' => 0,
        'unique_users' => 0,
        'total_leads' => 0,
      ];
    }
  }

  /**
   * Genera un codigo corto alfanumerico unico.
   *
   * @param int $length
   *   Longitud del codigo.
   *
   * @return string
   *   Codigo corto unico.
   */
  public function generateShortCode(int $length = 8): string {
    $storage = $this->entityTypeManager->getStorage('comercio_qr_code');
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $max_attempts = 10;

    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
      $code = '';
      for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
      }

      $existing = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('short_code', $code)
        ->count()
        ->execute();

      if ((int) $existing === 0) {
        return $code;
      }
    }

    return $code . random_int(100, 999);
  }

}
