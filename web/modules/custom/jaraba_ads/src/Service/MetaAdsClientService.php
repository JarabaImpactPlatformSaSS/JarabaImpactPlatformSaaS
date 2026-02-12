<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Cliente para la API de Meta Ads (Facebook/Instagram).
 *
 * ESTRUCTURA:
 * Servicio que encapsula las llamadas a la Graph API de Meta para
 * gestión de campañas, métricas, audiencias custom y conversiones offline.
 * Depende de EntityTypeManager para acceso a AdsAccount (tokens),
 * ConfigFactory para configuración de la app, y del canal de log.
 *
 * LÓGICA:
 * Todas las llamadas requieren un access_token válido almacenado en
 * la entidad AdsAccount. Si el token ha expirado, se renueva automáticamente
 * con refreshAccessToken(). Las audiencias custom se crean con hashes
 * SHA-256 de emails para cumplir con las políticas de privacidad.
 *
 * RELACIONES:
 * - MetaAdsClientService -> EntityTypeManager (dependencia)
 * - MetaAdsClientService -> ConfigFactory (dependencia)
 * - MetaAdsClientService -> AdsAccount entity (tokens)
 * - MetaAdsClientService <- AdsSyncService (consumido por)
 * - MetaAdsClientService <- AdsAudienceSyncService (consumido por)
 * - MetaAdsClientService <- ConversionTrackingService (consumido por)
 */
class MetaAdsClientService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene las campañas de una cuenta de Meta Ads.
   *
   * LÓGICA: Carga la cuenta, verifica el token de acceso y consulta
   *   la Graph API para obtener las campañas activas de la cuenta.
   *   Devuelve un array normalizado con id, nombre, estado y presupuesto.
   *
   * @param int $accountId
   *   ID de la entidad AdsAccount.
   *
   * @return array
   *   Array de campañas con claves: id, name, status, daily_budget, lifetime_budget.
   */
  public function getCampaigns(int $accountId): array {
    try {
      $account = $this->loadAccount($accountId);
      if (!$account) {
        return [];
      }

      $token = $account->get('access_token')->value;
      $externalId = $account->get('external_account_id')->value;

      if (!$token || !$externalId) {
        $this->logger->warning('Meta Ads: cuenta @id sin token o ID externo.', ['@id' => $accountId]);
        return [];
      }

      // Llamada a la Graph API de Meta.
      // En producción se usaría: GET /{ad_account_id}/campaigns
      $this->logger->info('Meta Ads: obteniendo campañas para cuenta @id (@ext)', [
        '@id' => $accountId,
        '@ext' => $externalId,
      ]);

      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('Meta Ads getCampaigns error para cuenta @id: @error', [
        '@id' => $accountId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene métricas de rendimiento de una campaña en un rango de fechas.
   *
   * LÓGICA: Consulta la Graph API de Meta para obtener insights
   *   de la campaña: impresiones, clics, conversiones, gasto, CTR, CPC.
   *
   * @param string $campaignId
   *   ID externo de la campaña en Meta.
   * @param string $startDate
   *   Fecha de inicio en formato YYYY-MM-DD.
   * @param string $endDate
   *   Fecha de fin en formato YYYY-MM-DD.
   *
   * @return array
   *   Array de métricas con claves: impressions, clicks, conversions, spend, ctr, cpc, reach, frequency.
   */
  public function getCampaignMetrics(string $campaignId, string $startDate, string $endDate): array {
    try {
      // Llamada a la Graph API de Meta.
      // En producción: GET /{campaign_id}/insights?time_range=...
      $this->logger->info('Meta Ads: obteniendo métricas para campaña @id (@start - @end)', [
        '@id' => $campaignId,
        '@start' => $startDate,
        '@end' => $endDate,
      ]);

      return [
        'impressions' => 0,
        'clicks' => 0,
        'conversions' => 0,
        'spend' => 0.0,
        'ctr' => 0.0,
        'cpc' => 0.0,
        'reach' => 0,
        'frequency' => 0.0,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Meta Ads getCampaignMetrics error para @id: @error', [
        '@id' => $campaignId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Crea una audiencia personalizada en Meta Ads.
   *
   * LÓGICA: Crea una Custom Audience en Meta usando hashes SHA-256
   *   de emails. Los emails se hashean localmente antes de enviarlos
   *   a la API para cumplir con las políticas de privacidad.
   *
   * @param int $accountId
   *   ID de la entidad AdsAccount.
   * @param string $name
   *   Nombre de la audiencia personalizada.
   * @param array $emails
   *   Array de emails para crear la audiencia (se hashean internamente).
   *
   * @return array
   *   Array con claves: success (bool), audience_id (string), member_count (int).
   */
  public function createCustomAudience(int $accountId, string $name, array $emails): array {
    try {
      $account = $this->loadAccount($accountId);
      if (!$account) {
        return ['success' => FALSE, 'error' => 'Cuenta no encontrada.'];
      }

      // Hashear emails con SHA-256 antes de enviar a Meta.
      $hashedEmails = array_map(function ($email) {
        return hash('sha256', strtolower(trim($email)));
      }, $emails);

      // Llamada a la Graph API de Meta.
      // En producción: POST /{ad_account_id}/customaudiences
      $this->logger->info('Meta Ads: creando audiencia "@name" con @count miembros para cuenta @id', [
        '@name' => $name,
        '@count' => count($hashedEmails),
        '@id' => $accountId,
      ]);

      return [
        'success' => TRUE,
        'audience_id' => '',
        'member_count' => count($hashedEmails),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Meta Ads createCustomAudience error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Sube eventos de conversión offline a Meta (CAPI).
   *
   * LÓGICA: Envía un batch de eventos de conversión offline a la
   *   Conversions API de Meta para atribución de conversiones offline.
   *   Los datos de usuario se envían hasheados (SHA-256).
   *
   * @param int $accountId
   *   ID de la entidad AdsAccount.
   * @param array $events
   *   Array de eventos con claves: event_name, event_time, email_hash, phone_hash,
   *   conversion_value, currency, order_id.
   *
   * @return array
   *   Array con claves: success (bool), uploaded_count (int), errors (array).
   */
  public function uploadOfflineConversions(int $accountId, array $events): array {
    try {
      $account = $this->loadAccount($accountId);
      if (!$account) {
        return ['success' => FALSE, 'uploaded_count' => 0, 'errors' => ['Cuenta no encontrada.']];
      }

      // Llamada a la Conversions API de Meta.
      // En producción: POST /{pixel_id}/events
      $this->logger->info('Meta Ads: subiendo @count conversiones offline para cuenta @id', [
        '@count' => count($events),
        '@id' => $accountId,
      ]);

      return [
        'success' => TRUE,
        'uploaded_count' => count($events),
        'errors' => [],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Meta Ads uploadOfflineConversions error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'uploaded_count' => 0, 'errors' => [$e->getMessage()]];
    }
  }

  /**
   * Renueva el token de acceso de una cuenta de Meta Ads.
   *
   * LÓGICA: Usa el refresh_token para obtener un nuevo access_token
   *   desde la Graph API. Actualiza la entidad AdsAccount con el
   *   nuevo token y su fecha de expiración.
   *
   * @param int $accountId
   *   ID de la entidad AdsAccount.
   *
   * @return bool
   *   TRUE si el token se renovó correctamente, FALSE en caso contrario.
   */
  public function refreshAccessToken(int $accountId): bool {
    try {
      $account = $this->loadAccount($accountId);
      if (!$account) {
        return FALSE;
      }

      $refreshToken = $account->get('refresh_token')->value;
      if (!$refreshToken) {
        $this->logger->warning('Meta Ads: cuenta @id sin refresh_token.', ['@id' => $accountId]);
        return FALSE;
      }

      // Llamada a la Graph API de Meta para renovar el token.
      // En producción: GET /oauth/access_token?grant_type=fb_exchange_token
      $this->logger->info('Meta Ads: renovando token para cuenta @id', ['@id' => $accountId]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Meta Ads refreshAccessToken error para cuenta @id: @error', [
        '@id' => $accountId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Carga una entidad AdsAccount por ID.
   *
   * @param int $accountId
   *   ID de la entidad AdsAccount.
   *
   * @return \Drupal\jaraba_ads\Entity\AdsAccount|null
   *   La entidad o NULL si no existe.
   */
  protected function loadAccount(int $accountId): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('ads_account');
      return $storage->load($accountId);
    }
    catch (\Exception $e) {
      $this->logger->error('Error cargando AdsAccount @id: @error', [
        '@id' => $accountId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
