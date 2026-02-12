<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de sincronización de cuentas y campañas de ads.
 *
 * ESTRUCTURA:
 * Servicio orquestador que coordina la sincronización de cuentas,
 * campañas y métricas con las plataformas de publicidad externas
 * (Meta, Google). Depende de MetaAdsClientService y GoogleAdsClientService
 * para las llamadas API específicas de cada plataforma.
 *
 * LÓGICA:
 * El flujo de sincronización sigue estos pasos:
 * 1. syncAllAccounts(): Obtiene todas las cuentas activas del tenant.
 * 2. syncAccount(): Para cada cuenta, sincroniza campañas y métricas.
 * 3. syncCampaignMetrics(): Para cada campaña, obtiene métricas diarias.
 * 4. Actualiza entidades AdsCampaignSync y AdsMetricsDaily.
 *
 * RELACIONES:
 * - AdsSyncService -> EntityTypeManager (dependencia)
 * - AdsSyncService -> MetaAdsClientService (dependencia)
 * - AdsSyncService -> GoogleAdsClientService (dependencia)
 * - AdsSyncService -> AdsAccount entity (consulta)
 * - AdsSyncService -> AdsCampaignSync entity (gestiona)
 * - AdsSyncService -> AdsMetricsDaily entity (gestiona)
 * - AdsSyncService <- AdsApiController (consumido por)
 */
class AdsSyncService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MetaAdsClientService $metaAdsClient,
    protected GoogleAdsClientService $googleAdsClient,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sincroniza todas las cuentas activas de un tenant.
   *
   * LÓGICA: Consulta todas las cuentas con status = 'active' del tenant
   *   y ejecuta syncAccount() para cada una. Retorna un resumen de
   *   la sincronización con cuentas procesadas, campañas y errores.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array con claves: accounts_synced (int), campaigns_found (int),
   *   errors (array), timestamp (int).
   */
  public function syncAllAccounts(int $tenantId): array {
    $result = [
      'accounts_synced' => 0,
      'campaigns_found' => 0,
      'errors' => [],
      'timestamp' => time(),
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('ads_account');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'active')
        ->execute();

      if (empty($ids)) {
        $this->logger->info('AdsSyncService: no hay cuentas activas para tenant @tenant', [
          '@tenant' => $tenantId,
        ]);
        return $result;
      }

      $accounts = $storage->loadMultiple($ids);

      foreach ($accounts as $account) {
        try {
          $accountResult = $this->syncAccount((int) $account->id());
          $result['accounts_synced']++;
          $result['campaigns_found'] += $accountResult['campaigns_synced'] ?? 0;
        }
        catch (\Exception $e) {
          $result['errors'][] = [
            'account_id' => $account->id(),
            'error' => $e->getMessage(),
          ];
          $this->logger->error('Error sincronizando cuenta @id: @error', [
            '@id' => $account->id(),
            '@error' => $e->getMessage(),
          ]);
        }
      }

      $this->logger->info('Sincronización completa para tenant @tenant: @accounts cuentas, @campaigns campañas', [
        '@tenant' => $tenantId,
        '@accounts' => $result['accounts_synced'],
        '@campaigns' => $result['campaigns_found'],
      ]);
    }
    catch (\Exception $e) {
      $result['errors'][] = ['error' => $e->getMessage()];
      $this->logger->error('Error en syncAllAccounts para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Sincroniza una cuenta de ads específica.
   *
   * LÓGICA: Carga la cuenta, determina la plataforma, obtiene las
   *   campañas del cliente API correspondiente y crea/actualiza las
   *   entidades AdsCampaignSync. Actualiza last_synced_at de la cuenta.
   *
   * @param int $accountId
   *   ID de la entidad AdsAccount.
   *
   * @return array
   *   Array con claves: campaigns_synced (int), campaigns_updated (int),
   *   campaigns_created (int).
   */
  public function syncAccount(int $accountId): array {
    $result = [
      'campaigns_synced' => 0,
      'campaigns_updated' => 0,
      'campaigns_created' => 0,
    ];

    try {
      $accountStorage = $this->entityTypeManager->getStorage('ads_account');
      $account = $accountStorage->load($accountId);

      if (!$account) {
        $this->logger->warning('AdsSyncService: cuenta @id no encontrada.', ['@id' => $accountId]);
        return $result;
      }

      $platform = $account->get('platform')->value;

      // Obtener campañas de la plataforma.
      $campaigns = match ($platform) {
        'meta' => $this->metaAdsClient->getCampaigns($accountId),
        'google' => $this->googleAdsClient->getCampaigns($accountId),
        default => [],
      };

      $campaignStorage = $this->entityTypeManager->getStorage('ads_campaign_sync');

      foreach ($campaigns as $campaignData) {
        $externalId = $campaignData['id'] ?? '';
        if (!$externalId) {
          continue;
        }

        // Buscar campaña existente por external_campaign_id.
        $existing = $campaignStorage->getQuery()
          ->accessCheck(TRUE)
          ->condition('account_id', $accountId)
          ->condition('external_campaign_id', $externalId)
          ->execute();

        if (!empty($existing)) {
          // Actualizar campaña existente.
          $campaign = $campaignStorage->load(reset($existing));
          if ($campaign) {
            $campaign->set('campaign_name', $campaignData['name'] ?? $campaign->label());
            $campaign->set('status', $campaignData['status'] ?? 'active');
            $campaign->set('last_synced_at', time());
            $campaign->save();
            $result['campaigns_updated']++;
          }
        }
        else {
          // Crear nueva campaña sincronizada.
          $campaign = $campaignStorage->create([
            'tenant_id' => $account->get('tenant_id')->target_id,
            'account_id' => $accountId,
            'external_campaign_id' => $externalId,
            'campaign_name' => $campaignData['name'] ?? 'Campaña sin nombre',
            'campaign_type' => $campaignData['type'] ?? NULL,
            'status' => $campaignData['status'] ?? 'active',
            'daily_budget' => $campaignData['daily_budget'] ?? NULL,
            'lifetime_budget' => $campaignData['lifetime_budget'] ?? NULL,
            'currency' => $campaignData['currency'] ?? 'EUR',
            'last_synced_at' => time(),
          ]);
          $campaign->save();
          $result['campaigns_created']++;
        }

        $result['campaigns_synced']++;
      }

      // Actualizar last_synced_at de la cuenta.
      $account->set('last_synced_at', time());
      $account->set('sync_error', NULL);
      $account->save();

      $this->logger->info('Cuenta @id sincronizada: @total campañas (@new nuevas, @updated actualizadas)', [
        '@id' => $accountId,
        '@total' => $result['campaigns_synced'],
        '@new' => $result['campaigns_created'],
        '@updated' => $result['campaigns_updated'],
      ]);
    }
    catch (\Exception $e) {
      // Marcar error en la cuenta.
      try {
        $accountStorage = $this->entityTypeManager->getStorage('ads_account');
        $account = $accountStorage->load($accountId);
        if ($account) {
          $account->set('sync_error', $e->getMessage());
          $account->set('status', 'error');
          $account->save();
        }
      }
      catch (\Exception $inner) {
        // No propagar errores de recuperación.
      }

      $this->logger->error('Error sincronizando cuenta @id: @error', [
        '@id' => $accountId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Sincroniza métricas de campañas de una cuenta para una fecha.
   *
   * LÓGICA: Carga todas las campañas sincronizadas de la cuenta,
   *   obtiene las métricas del día para cada una y crea/actualiza
   *   entidades AdsMetricsDaily.
   *
   * @param int $accountId
   *   ID de la entidad AdsAccount.
   * @param string $date
   *   Fecha en formato YYYY-MM-DD.
   *
   * @return array
   *   Array con claves: metrics_synced (int), errors (array).
   */
  public function syncCampaignMetrics(int $accountId, string $date): array {
    $result = [
      'metrics_synced' => 0,
      'errors' => [],
    ];

    try {
      $accountStorage = $this->entityTypeManager->getStorage('ads_account');
      $account = $accountStorage->load($accountId);

      if (!$account) {
        return $result;
      }

      $platform = $account->get('platform')->value;

      // Obtener campañas de la cuenta.
      $campaignStorage = $this->entityTypeManager->getStorage('ads_campaign_sync');
      $campaignIds = $campaignStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('account_id', $accountId)
        ->execute();

      if (empty($campaignIds)) {
        return $result;
      }

      $campaigns = $campaignStorage->loadMultiple($campaignIds);
      $metricsStorage = $this->entityTypeManager->getStorage('ads_metrics_daily');

      foreach ($campaigns as $campaign) {
        try {
          $externalId = $campaign->get('external_campaign_id')->value;
          if (!$externalId) {
            continue;
          }

          // Obtener métricas de la plataforma.
          $metrics = match ($platform) {
            'meta' => $this->metaAdsClient->getCampaignMetrics($externalId, $date, $date),
            'google' => $this->googleAdsClient->getCampaignMetrics($externalId, $date, $date),
            default => [],
          };

          if (empty($metrics)) {
            continue;
          }

          // Buscar métrica existente para este día y campaña.
          $existing = $metricsStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('campaign_id', $campaign->id())
            ->condition('metrics_date', $date)
            ->execute();

          if (!empty($existing)) {
            // Actualizar métrica existente.
            $metricsEntity = $metricsStorage->load(reset($existing));
          }
          else {
            // Crear nueva métrica.
            $metricsEntity = $metricsStorage->create([
              'tenant_id' => $campaign->get('tenant_id')->target_id,
              'campaign_id' => $campaign->id(),
              'metrics_date' => $date,
            ]);
          }

          $metricsEntity->set('impressions', $metrics['impressions'] ?? 0);
          $metricsEntity->set('clicks', $metrics['clicks'] ?? 0);
          $metricsEntity->set('conversions', $metrics['conversions'] ?? 0);
          $metricsEntity->set('spend', $metrics['spend'] ?? 0);
          $metricsEntity->set('revenue', $metrics['revenue'] ?? 0);
          $metricsEntity->set('ctr', $metrics['ctr'] ?? 0);
          $metricsEntity->set('cpc', $metrics['cpc'] ?? 0);
          $metricsEntity->set('cpa', $metrics['cpa'] ?? 0);
          $metricsEntity->set('roas', $metrics['roas'] ?? 0);
          $metricsEntity->set('reach', $metrics['reach'] ?? 0);
          $metricsEntity->set('frequency', $metrics['frequency'] ?? 0);
          $metricsEntity->save();

          $result['metrics_synced']++;
        }
        catch (\Exception $e) {
          $result['errors'][] = [
            'campaign_id' => $campaign->id(),
            'error' => $e->getMessage(),
          ];
        }
      }

      $this->logger->info('Métricas sincronizadas para cuenta @id en @date: @count métricas', [
        '@id' => $accountId,
        '@date' => $date,
        '@count' => $result['metrics_synced'],
      ]);
    }
    catch (\Exception $e) {
      $result['errors'][] = ['error' => $e->getMessage()];
      $this->logger->error('Error sincronizando métricas para cuenta @id: @error', [
        '@id' => $accountId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Obtiene el estado de la última sincronización de un tenant.
   *
   * LÓGICA: Consulta las cuentas del tenant y devuelve un resumen
   *   del estado de sincronización: última fecha, errores, cuentas activas.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array con claves: total_accounts (int), active_accounts (int),
   *   last_synced_at (int|null), accounts_with_errors (int), details (array).
   */
  public function getLastSyncStatus(int $tenantId): array {
    $result = [
      'total_accounts' => 0,
      'active_accounts' => 0,
      'last_synced_at' => NULL,
      'accounts_with_errors' => 0,
      'details' => [],
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('ads_account');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->execute();

      if (empty($ids)) {
        return $result;
      }

      $accounts = $storage->loadMultiple($ids);
      $result['total_accounts'] = count($accounts);

      foreach ($accounts as $account) {
        $status = $account->get('status')->value;
        $lastSynced = $account->get('last_synced_at')->value;
        $syncError = $account->get('sync_error')->value;

        if ($status === 'active') {
          $result['active_accounts']++;
        }

        if ($syncError) {
          $result['accounts_with_errors']++;
        }

        if ($lastSynced && (!$result['last_synced_at'] || (int) $lastSynced > $result['last_synced_at'])) {
          $result['last_synced_at'] = (int) $lastSynced;
        }

        $result['details'][] = [
          'account_id' => (int) $account->id(),
          'account_name' => $account->label(),
          'platform' => $account->get('platform')->value,
          'status' => $status,
          'last_synced_at' => $lastSynced ? (int) $lastSynced : NULL,
          'sync_error' => $syncError,
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estado de sincronización para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

}
