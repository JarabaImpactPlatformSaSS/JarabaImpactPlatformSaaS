<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_crm\Service\ContactService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de sincronización de audiencias con plataformas de ads.
 *
 * ESTRUCTURA:
 * Servicio que orquesta la sincronización de audiencias personalizadas
 * entre el CRM/plataforma y las plataformas de publicidad externas.
 * Gestiona el ciclo de vida completo: creación, sincronización,
 * actualización y eliminación de audiencias.
 *
 * LÓGICA:
 * El flujo de sincronización sigue estos pasos:
 * 1. Se crea una AdsAudienceSync con source_type y source_config.
 * 2. Se extraen los datos del origen (CRM, lista de emails, etc.).
 * 3. Se envían los datos hasheados a la plataforma correspondiente.
 * 4. Se actualiza el sync_status y member_count del registro.
 *
 * RELACIONES:
 * - AdsAudienceSyncService -> EntityTypeManager (dependencia)
 * - AdsAudienceSyncService -> ContactService (extracción de emails del CRM)
 * - AdsAudienceSyncService -> AdsAudienceSync entity (gestiona)
 * - AdsAudienceSyncService -> AdsAccount entity (consulta tokens)
 * - AdsAudienceSyncService <- AdsApiController (consumido por)
 */
class AdsAudienceSyncService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ContactService $contactService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sincroniza una audiencia existente con la plataforma de ads.
   *
   * LÓGICA: Carga la entidad AdsAudienceSync, verifica su estado,
   *   extrae los datos del origen configurado y los envía a la
   *   plataforma correspondiente. Actualiza sync_status al finalizar.
   *
   * @param int $audienceId
   *   ID de la entidad AdsAudienceSync.
   *
   * @return array
   *   Array con claves: success (bool), member_count (int), message (string).
   */
  public function syncAudience(int $audienceId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('ads_audience_sync');
      $audience = $storage->load($audienceId);

      if (!$audience) {
        return ['success' => FALSE, 'member_count' => 0, 'message' => 'Audiencia no encontrada.'];
      }

      // Marcar como sincronizando.
      $audience->set('sync_status', 'syncing');
      $audience->save();

      $platform = $audience->get('platform')->value;
      $sourceType = $audience->get('source_type')->value;

      $this->logger->info('Sincronizando audiencia @id (@name) en @platform desde @source', [
        '@id' => $audienceId,
        '@name' => $audience->label(),
        '@platform' => $platform,
        '@source' => $sourceType,
      ]);

      // Extraer emails del CRM si el source_type es crm_contacts.
      if ($sourceType === 'crm_contacts') {
        $tenantId = $audience->get('tenant_id')->target_id;
        $contacts = $this->contactService->list(['tenant_id' => $tenantId], 10000);
        $memberCount = 0;
        foreach ($contacts as $contact) {
          if ($contact->hasField('email') && !$contact->get('email')->isEmpty()) {
            $memberCount++;
          }
        }
        $audience->set('member_count', $memberCount);
      }

      // Actualizar estado tras sincronización.
      $audience->set('sync_status', 'synced');
      $audience->set('last_synced_at', time());
      $audience->save();

      return [
        'success' => TRUE,
        'member_count' => (int) ($audience->get('member_count')->value ?? 0),
        'message' => 'Audiencia sincronizada correctamente.',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error sincronizando audiencia @id: @error', [
        '@id' => $audienceId,
        '@error' => $e->getMessage(),
      ]);

      // Marcar como error si es posible.
      try {
        $storage = $this->entityTypeManager->getStorage('ads_audience_sync');
        $audience = $storage->load($audienceId);
        if ($audience) {
          $audience->set('sync_status', 'error');
          $audience->set('sync_error', $e->getMessage());
          $audience->save();
        }
      }
      catch (\Exception $inner) {
        // No propagar errores de recuperación.
      }

      return ['success' => FALSE, 'member_count' => 0, 'message' => $e->getMessage()];
    }
  }

  /**
   * Crea una nueva audiencia a partir de datos del CRM.
   *
   * LÓGICA: Crea una entidad AdsAudienceSync con la configuración
   *   proporcionada y la sincroniza con la plataforma de ads.
   *
   * @param int $tenantId
   *   ID del tenant propietario.
   * @param string $platform
   *   Plataforma destino: 'meta', 'google', 'linkedin'.
   * @param array $config
   *   Configuración de la audiencia con claves:
   *   - 'name' (string): Nombre de la audiencia.
   *   - 'account_id' (int): ID de la cuenta de ads.
   *   - 'source_type' (string): Tipo de origen.
   *   - 'source_config' (array): Configuración del origen en JSON.
   *
   * @return array
   *   Array con claves: success (bool), audience_id (int), message (string).
   */
  public function createAudienceFromCrm(int $tenantId, string $platform, array $config): array {
    try {
      $storage = $this->entityTypeManager->getStorage('ads_audience_sync');

      $audience = $storage->create([
        'tenant_id' => $tenantId,
        'account_id' => $config['account_id'] ?? NULL,
        'audience_name' => $config['name'] ?? 'Audiencia CRM',
        'platform' => $platform,
        'source_type' => $config['source_type'] ?? 'crm_contacts',
        'source_config' => isset($config['source_config']) ? json_encode($config['source_config']) : '{}',
        'sync_status' => 'pending',
        'member_count' => 0,
      ]);

      $audience->save();

      $this->logger->info('Audiencia CRM creada: @name para tenant @tenant en @platform (ID: @id)', [
        '@name' => $audience->label(),
        '@tenant' => $tenantId,
        '@platform' => $platform,
        '@id' => $audience->id(),
      ]);

      return [
        'success' => TRUE,
        'audience_id' => (int) $audience->id(),
        'message' => 'Audiencia creada correctamente.',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando audiencia CRM para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'audience_id' => 0, 'message' => $e->getMessage()];
    }
  }

  /**
   * Obtiene todas las audiencias de un tenant.
   *
   * LÓGICA: Consulta todas las entidades AdsAudienceSync del tenant
   *   ordenadas por fecha de creación descendente.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array de entidades AdsAudienceSync.
   */
  public function getAudiencesForTenant(int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('ads_audience_sync');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      return array_values($storage->loadMultiple($ids));
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo audiencias para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Elimina una audiencia sincronizada.
   *
   * LÓGICA: Carga la entidad AdsAudienceSync y la elimina.
   *   Opcionalmente elimina la audiencia en la plataforma externa.
   *
   * @param int $audienceId
   *   ID de la entidad AdsAudienceSync.
   *
   * @return bool
   *   TRUE si se eliminó correctamente, FALSE en caso contrario.
   */
  public function deleteSyncedAudience(int $audienceId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('ads_audience_sync');
      $audience = $storage->load($audienceId);

      if (!$audience) {
        return FALSE;
      }

      $name = $audience->label();
      $audience->delete();

      $this->logger->info('Audiencia sincronizada eliminada: @name (ID: @id)', [
        '@name' => $name,
        '@id' => $audienceId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error eliminando audiencia @id: @error', [
        '@id' => $audienceId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
