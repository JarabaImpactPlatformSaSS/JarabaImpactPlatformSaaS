<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de tracking de referidos: clicks, registros y conversiones.
 *
 * ESTRUCTURA:
 * Servicio que gestiona el seguimiento de eventos del programa de referidos.
 * Incrementa contadores en la entidad ReferralCode para cada tipo de evento
 * y proporciona estadísticas agregadas por tenant.
 *
 * LÓGICA:
 * El tracking sigue tres tipos de eventos:
 * 1. Click: un visitante hace click en un enlace de referido
 * 2. Signup: un visitante se registra usando un código de referido
 * 3. Conversion: un usuario referido completa una acción de valor (compra, etc.)
 * Cada evento incrementa el contador correspondiente en ReferralCode.
 *
 * RELACIONES:
 * - ReferralTrackingService -> EntityTypeManager (dependencia)
 * - ReferralTrackingService -> ReferralCode entity (actualiza contadores)
 * - ReferralTrackingService <- ReferralApiController (consumido por)
 */
class ReferralTrackingService {

  /**
   * Constructor del servicio de tracking de referidos.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a storage de entidades.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para trazar eventos de tracking.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Registra un click en un enlace de referido.
   *
   * ESTRUCTURA: Método público para tracking de clicks.
   *
   * LÓGICA: Busca el código de referido, verifica que sea válido y activo,
   *   e incrementa el contador total_clicks. El array $context puede contener
   *   metadatos adicionales (IP, user agent, referer, etc.) para logging.
   *
   * RELACIONES: Consume ReferralCode storage.
   *
   * @param string $code
   *   Código alfanumérico del referido.
   * @param array $context
   *   Contexto adicional del click (ip, user_agent, referer, etc.).
   *
   * @return bool
   *   TRUE si el click fue registrado correctamente.
   */
  public function trackClick(string $code, array $context = []): bool {
    try {
      $codeEntity = $this->getCodeByString($code);

      if (!$codeEntity) {
        $this->logger->warning('Click en código de referido inexistente: @code', ['@code' => $code]);
        return FALSE;
      }

      if (!$codeEntity->get('is_active')->value) {
        $this->logger->info('Click en código de referido inactivo: @code', ['@code' => $code]);
        return FALSE;
      }

      // Verificar expiración.
      $expiresAt = $codeEntity->get('expires_at')->value;
      if ($expiresAt && new \DateTime($expiresAt) < new \DateTime()) {
        $this->logger->info('Click en código de referido expirado: @code', ['@code' => $code]);
        return FALSE;
      }

      // Incrementar contador de clicks.
      $currentClicks = (int) ($codeEntity->get('total_clicks')->value ?? 0);
      $codeEntity->set('total_clicks', $currentClicks + 1);
      $codeEntity->save();

      $this->logger->info('Click registrado para código @code (total: @total)', [
        '@code' => $code,
        '@total' => $currentClicks + 1,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando click para código @code: @error', [
        '@code' => $code,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Registra un signup (registro) mediante un código de referido.
   *
   * ESTRUCTURA: Método público para tracking de registros.
   *
   * LÓGICA: Busca el código de referido, verifica validez e incrementa
   *   el contador total_signups. Asocia el nuevo usuario al tracking.
   *
   * RELACIONES: Consume ReferralCode storage.
   *
   * @param string $code
   *   Código alfanumérico del referido.
   * @param int $newUserId
   *   ID del nuevo usuario que se registró con el código.
   *
   * @return bool
   *   TRUE si el signup fue registrado correctamente.
   */
  public function trackSignup(string $code, int $newUserId): bool {
    try {
      $codeEntity = $this->getCodeByString($code);

      if (!$codeEntity) {
        $this->logger->warning('Signup con código de referido inexistente: @code', ['@code' => $code]);
        return FALSE;
      }

      if (!$codeEntity->get('is_active')->value) {
        $this->logger->info('Signup con código de referido inactivo: @code', ['@code' => $code]);
        return FALSE;
      }

      // Incrementar contador de signups.
      $currentSignups = (int) ($codeEntity->get('total_signups')->value ?? 0);
      $codeEntity->set('total_signups', $currentSignups + 1);
      $codeEntity->save();

      $this->logger->info('Signup registrado para código @code por usuario #@uid (total: @total)', [
        '@code' => $code,
        '@uid' => $newUserId,
        '@total' => $currentSignups + 1,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando signup para código @code: @error', [
        '@code' => $code,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Registra una conversión (compra/suscripción) con un código de referido.
   *
   * ESTRUCTURA: Método público para tracking de conversiones.
   *
   * LÓGICA: Busca el código de referido, verifica validez, incrementa
   *   el contador total_conversions y suma el valor a total_revenue.
   *
   * RELACIONES: Consume ReferralCode storage.
   *
   * @param string $code
   *   Código alfanumérico del referido.
   * @param int $userId
   *   ID del usuario que realizó la conversión.
   * @param float $value
   *   Valor monetario de la conversión (en EUR).
   *
   * @return bool
   *   TRUE si la conversión fue registrada correctamente.
   */
  public function trackConversion(string $code, int $userId, float $value): bool {
    try {
      $codeEntity = $this->getCodeByString($code);

      if (!$codeEntity) {
        $this->logger->warning('Conversión con código de referido inexistente: @code', ['@code' => $code]);
        return FALSE;
      }

      if (!$codeEntity->get('is_active')->value) {
        $this->logger->info('Conversión con código de referido inactivo: @code', ['@code' => $code]);
        return FALSE;
      }

      // Incrementar contador de conversiones y revenue.
      $currentConversions = (int) ($codeEntity->get('total_conversions')->value ?? 0);
      $currentRevenue = (float) ($codeEntity->get('total_revenue')->value ?? 0);

      $codeEntity->set('total_conversions', $currentConversions + 1);
      $codeEntity->set('total_revenue', $currentRevenue + $value);
      $codeEntity->save();

      $this->logger->info('Conversión registrada para código @code por usuario #@uid: @value EUR (total conversiones: @total)', [
        '@code' => $code,
        '@uid' => $userId,
        '@value' => $value,
        '@total' => $currentConversions + 1,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando conversión para código @code: @error', [
        '@code' => $code,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Busca una entidad ReferralCode por su código alfanumérico.
   *
   * ESTRUCTURA: Método público de consulta por código string.
   *
   * LÓGICA: Consulta la entidad ReferralCode por el campo 'code' único.
   *   Devuelve la primera coincidencia o NULL si no existe.
   *
   * RELACIONES: Consume ReferralCode storage.
   *
   * @param string $code
   *   Código alfanumérico a buscar.
   *
   * @return object|null
   *   La entidad ReferralCode si existe, o NULL.
   */
  public function getCodeByString(string $code): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('referral_code');
      $codes = $storage->loadByProperties(['code' => $code]);

      if (empty($codes)) {
        return NULL;
      }

      return reset($codes);
    }
    catch (\Exception $e) {
      $this->logger->error('Error buscando código de referido @code: @error', [
        '@code' => $code,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene estadísticas de tracking agregadas por tenant.
   *
   * ESTRUCTURA: Método público de estadísticas agregadas.
   *
   * LÓGICA: Consulta todos los códigos del tenant y suma los contadores
   *   de clicks, signups, conversiones y revenue. Calcula tasas de
   *   conversión de click-to-signup y signup-to-conversion.
   *
   * RELACIONES: Consume ReferralCode storage.
   *
   * @param int $tenantId
   *   ID del tenant para filtrar estadísticas.
   *
   * @return array
   *   Array con:
   *   - 'total_codes' (int): Total de códigos activos.
   *   - 'total_clicks' (int): Suma de clicks.
   *   - 'total_signups' (int): Suma de signups.
   *   - 'total_conversions' (int): Suma de conversiones.
   *   - 'total_revenue' (float): Revenue total.
   *   - 'click_to_signup_rate' (float): Tasa de conversión click->signup.
   *   - 'signup_to_conversion_rate' (float): Tasa signup->conversión.
   */
  public function getTrackingStats(int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('referral_code');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->execute();

      $stats = [
        'total_codes' => 0,
        'total_clicks' => 0,
        'total_signups' => 0,
        'total_conversions' => 0,
        'total_revenue' => 0.0,
        'click_to_signup_rate' => 0.0,
        'signup_to_conversion_rate' => 0.0,
      ];

      if (empty($ids)) {
        return $stats;
      }

      $codes = $storage->loadMultiple($ids);
      $stats['total_codes'] = count($codes);

      foreach ($codes as $code) {
        $stats['total_clicks'] += (int) ($code->get('total_clicks')->value ?? 0);
        $stats['total_signups'] += (int) ($code->get('total_signups')->value ?? 0);
        $stats['total_conversions'] += (int) ($code->get('total_conversions')->value ?? 0);
        $stats['total_revenue'] += (float) ($code->get('total_revenue')->value ?? 0);
      }

      // Calcular tasas de conversión.
      if ($stats['total_clicks'] > 0) {
        $stats['click_to_signup_rate'] = round(
          ($stats['total_signups'] / $stats['total_clicks']) * 100,
          1
        );
      }
      if ($stats['total_signups'] > 0) {
        $stats['signup_to_conversion_rate'] = round(
          ($stats['total_conversions'] / $stats['total_signups']) * 100,
          1
        );
      }

      $stats['total_revenue'] = round($stats['total_revenue'], 2);

      return $stats;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estadísticas de tracking para tenant #@id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'total_codes' => 0,
        'total_clicks' => 0,
        'total_signups' => 0,
        'total_conversions' => 0,
        'total_revenue' => 0.0,
        'click_to_signup_rate' => 0.0,
        'signup_to_conversion_rate' => 0.0,
      ];
    }
  }

}
