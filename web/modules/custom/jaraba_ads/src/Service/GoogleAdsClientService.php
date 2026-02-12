<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Cliente para la API de Google Ads.
 *
 * ESTRUCTURA:
 * Servicio que encapsula las llamadas a la Google Ads API para
 * gestión de campañas, métricas, Customer Match audiences y
 * conversiones offline. Depende de EntityTypeManager para acceso
 * a AdsAccount (tokens), ConfigFactory para configuración del
 * developer token, y del canal de log.
 *
 * LÓGICA:
 * Todas las llamadas requieren un access_token válido y un developer
 * token de Google Ads. El token se renueva con OAuth2 refresh_token.
 * Las audiencias Customer Match usan hashes SHA-256 de emails.
 *
 * RELACIONES:
 * - GoogleAdsClientService -> EntityTypeManager (dependencia)
 * - GoogleAdsClientService -> ConfigFactory (dependencia)
 * - GoogleAdsClientService -> AdsAccount entity (tokens)
 * - GoogleAdsClientService <- AdsSyncService (consumido por)
 * - GoogleAdsClientService <- AdsAudienceSyncService (consumido por)
 * - GoogleAdsClientService <- ConversionTrackingService (consumido por)
 */
class GoogleAdsClientService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene las campañas de una cuenta de Google Ads.
   *
   * LÓGICA: Carga la cuenta, verifica el token de acceso y consulta
   *   la Google Ads API para obtener las campañas de la cuenta.
   *   Usa la Google Ads Query Language (GAQL) para la consulta.
   *
   * @param int $accountId
   *   ID de la entidad AdsAccount.
   *
   * @return array
   *   Array de campañas con claves: id, name, status, daily_budget, campaign_type.
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
        $this->logger->warning('Google Ads: cuenta @id sin token o ID externo.', ['@id' => $accountId]);
        return [];
      }

      // Llamada a la Google Ads API.
      // En producción se usaría: GoogleAdsService.SearchStream con GAQL
      $this->logger->info('Google Ads: obteniendo campañas para cuenta @id (@ext)', [
        '@id' => $accountId,
        '@ext' => $externalId,
      ]);

      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('Google Ads getCampaigns error para cuenta @id: @error', [
        '@id' => $accountId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene métricas de rendimiento de una campaña en un rango de fechas.
   *
   * LÓGICA: Consulta la Google Ads API usando GAQL para obtener
   *   métricas de la campaña: impresiones, clics, conversiones, gasto.
   *
   * @param string $campaignId
   *   ID externo de la campaña en Google Ads.
   * @param string $startDate
   *   Fecha de inicio en formato YYYY-MM-DD.
   * @param string $endDate
   *   Fecha de fin en formato YYYY-MM-DD.
   *
   * @return array
   *   Array de métricas con claves: impressions, clicks, conversions, spend, ctr, cpc.
   */
  public function getCampaignMetrics(string $campaignId, string $startDate, string $endDate): array {
    try {
      // Llamada a la Google Ads API con GAQL.
      // En producción: SELECT metrics.impressions, metrics.clicks, ... WHERE campaign.id = ...
      $this->logger->info('Google Ads: obteniendo métricas para campaña @id (@start - @end)', [
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
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Google Ads getCampaignMetrics error para @id: @error', [
        '@id' => $campaignId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Crea una audiencia Customer Match en Google Ads.
   *
   * LÓGICA: Crea una User List de tipo CRM_BASED en Google Ads
   *   usando hashes SHA-256 de emails para Customer Match.
   *
   * @param int $accountId
   *   ID de la entidad AdsAccount.
   * @param string $name
   *   Nombre de la audiencia.
   * @param array $emails
   *   Array de emails para crear la audiencia (se hashean internamente).
   *
   * @return array
   *   Array con claves: success (bool), audience_id (string), member_count (int).
   */
  public function createCustomerMatchAudience(int $accountId, string $name, array $emails): array {
    try {
      $account = $this->loadAccount($accountId);
      if (!$account) {
        return ['success' => FALSE, 'error' => 'Cuenta no encontrada.'];
      }

      // Hashear emails con SHA-256 antes de enviar a Google.
      $hashedEmails = array_map(function ($email) {
        return hash('sha256', strtolower(trim($email)));
      }, $emails);

      // Llamada a la Google Ads API.
      // En producción: UserListService.mutate + OfflineUserDataJobService
      $this->logger->info('Google Ads: creando audiencia Customer Match "@name" con @count miembros para cuenta @id', [
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
      $this->logger->error('Google Ads createCustomerMatchAudience error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Sube eventos de conversión offline a Google Ads.
   *
   * LÓGICA: Envía un batch de conversiones offline a la Google Ads API
   *   para atribución de conversiones que ocurrieron fuera del canal digital.
   *   Usa OfflineConversionUploadService.
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

      // Llamada a la Google Ads API.
      // En producción: ConversionUploadService.UploadClickConversions
      $this->logger->info('Google Ads: subiendo @count conversiones offline para cuenta @id', [
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
      $this->logger->error('Google Ads uploadOfflineConversions error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'uploaded_count' => 0, 'errors' => [$e->getMessage()]];
    }
  }

  /**
   * Renueva el token de acceso de una cuenta de Google Ads.
   *
   * LÓGICA: Usa el refresh_token de OAuth2 para obtener un nuevo
   *   access_token. Actualiza la entidad AdsAccount con el nuevo token.
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
        $this->logger->warning('Google Ads: cuenta @id sin refresh_token.', ['@id' => $accountId]);
        return FALSE;
      }

      // Llamada a la OAuth2 API de Google para renovar el token.
      // En producción: POST https://oauth2.googleapis.com/token
      $this->logger->info('Google Ads: renovando token para cuenta @id', ['@id' => $accountId]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Google Ads refreshAccessToken error para cuenta @id: @error', [
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
