<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service\Ingestion;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\jaraba_funding\Service\Api\BdnsApiClient;
use Drupal\jaraba_funding\Service\Api\BojaApiClient;
use Psr\Log\LoggerInterface;

/**
 * Pipeline de ingestion de convocatorias de subvenciones.
 *
 * Orquesta la sincronizacion de convocatorias desde fuentes externas
 * (BDNS y BOJA), normaliza los datos y los persiste como entidades
 * FundingCall. Soporta ingestion sincrona y asincrona via colas.
 *
 * ARQUITECTURA:
 * - Sync desde BDNS y BOJA con deteccion de duplicados.
 * - Normalizacion via FundingNormalizerService.
 * - Procesamiento asincrono via cola jaraba_funding_ingestion.
 * - Estadisticas de ingestion (new, updated, errors).
 *
 * RELACIONES:
 * - FundingIngestionService -> BdnsApiClient (obtiene datos BDNS)
 * - FundingIngestionService -> BojaApiClient (obtiene datos BOJA)
 * - FundingIngestionService -> FundingNormalizerService (normaliza)
 * - FundingIngestionService -> QueueFactory (procesamiento asincrono)
 * - FundingIngestionService -> FundingCall entity (persiste)
 */
class FundingIngestionService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\jaraba_funding\Service\Api\BdnsApiClient $bdnsClient
   *   Cliente de la API de BDNS.
   * @param \Drupal\jaraba_funding\Service\Api\BojaApiClient $bojaClient
   *   Cliente de la API de BOJA.
   * @param \Drupal\jaraba_funding\Service\Ingestion\FundingNormalizerService $normalizer
   *   Servicio de normalizacion de datos.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   Factory de colas para procesamiento asincrono.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected BdnsApiClient $bdnsClient,
    protected BojaApiClient $bojaClient,
    protected FundingNormalizerService $normalizer,
    protected QueueFactory $queueFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sincroniza convocatorias desde BDNS.
   *
   * Obtiene convocatorias de la BDNS para el rango de fechas indicado,
   * las normaliza y las ingesta, detectando duplicados.
   *
   * @param string|null $dateFrom
   *   Fecha inicio en formato YYYY-MM-DD. NULL para ultimos 7 dias.
   * @param string|null $dateTo
   *   Fecha fin en formato YYYY-MM-DD. NULL para hoy.
   *
   * @return array
   *   Estadisticas de ingestion:
   *   - new: (int) Convocatorias nuevas creadas.
   *   - updated: (int) Convocatorias existentes actualizadas.
   *   - errors: (int) Errores durante la ingestion.
   */
  public function syncFromBdns(?string $dateFrom = NULL, ?string $dateTo = NULL): array {
    $stats = ['new' => 0, 'updated' => 0, 'errors' => 0];

    $dateFrom = $dateFrom ?: date('Y-m-d', strtotime('-7 days'));
    $dateTo = $dateTo ?: date('Y-m-d');

    try {
      $result = $this->bdnsClient->fetchConvocatorias([
        'fecha_desde' => $dateFrom,
        'fecha_hasta' => $dateTo,
      ]);

      foreach ($result['data'] ?? [] as $rawItem) {
        try {
          $normalized = $this->normalizer->normalize($rawItem, 'bdns');
          $sourceId = $normalized['source_id'] ?? '';

          if (empty($sourceId)) {
            $stats['errors']++;
            continue;
          }

          $existingId = $this->findExistingBySourceId($sourceId, 'bdns');

          if ($existingId !== NULL) {
            $updated = $this->updateConvocatoria($existingId, $normalized);
            if ($updated) {
              $stats['updated']++;
            }
            else {
              $stats['errors']++;
            }
          }
          else {
            $entityId = $this->ingestConvocatoria($normalized, 'bdns');
            if ($entityId !== NULL) {
              $stats['new']++;
            }
            else {
              $stats['errors']++;
            }
          }
        }
        catch (\Exception $e) {
          $stats['errors']++;
          $this->logger->warning('Error ingestando convocatoria BDNS: @error', [
            '@error' => $e->getMessage(),
          ]);
        }
      }

      $this->logger->info('Sync BDNS completado: @new nuevas, @updated actualizadas, @errors errores.', [
        '@new' => $stats['new'],
        '@updated' => $stats['updated'],
        '@errors' => $stats['errors'],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error en sincronizacion BDNS: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $stats;
  }

  /**
   * Sincroniza convocatorias desde BOJA.
   *
   * Obtiene convocatorias del BOJA para el rango de fechas indicado,
   * las normaliza y las ingesta, detectando duplicados.
   *
   * @param string|null $dateFrom
   *   Fecha inicio en formato YYYY-MM-DD. NULL para ultimos 7 dias.
   * @param string|null $dateTo
   *   Fecha fin en formato YYYY-MM-DD. NULL para hoy.
   *
   * @return array
   *   Estadisticas de ingestion:
   *   - new: (int) Convocatorias nuevas creadas.
   *   - updated: (int) Convocatorias existentes actualizadas.
   *   - errors: (int) Errores durante la ingestion.
   */
  public function syncFromBoja(?string $dateFrom = NULL, ?string $dateTo = NULL): array {
    $stats = ['new' => 0, 'updated' => 0, 'errors' => 0];

    $dateFrom = $dateFrom ?: date('Y-m-d', strtotime('-7 days'));
    $dateTo = $dateTo ?: date('Y-m-d');

    try {
      $result = $this->bojaClient->fetchConvocatorias([
        'fecha_desde' => $dateFrom,
        'fecha_hasta' => $dateTo,
      ]);

      foreach ($result['data'] ?? [] as $rawItem) {
        try {
          $normalized = $this->normalizer->normalize($rawItem, 'boja');
          $sourceId = $normalized['source_id'] ?? '';

          if (empty($sourceId)) {
            $stats['errors']++;
            continue;
          }

          $existingId = $this->findExistingBySourceId($sourceId, 'boja');

          if ($existingId !== NULL) {
            $updated = $this->updateConvocatoria($existingId, $normalized);
            if ($updated) {
              $stats['updated']++;
            }
            else {
              $stats['errors']++;
            }
          }
          else {
            $entityId = $this->ingestConvocatoria($normalized, 'boja');
            if ($entityId !== NULL) {
              $stats['new']++;
            }
            else {
              $stats['errors']++;
            }
          }
        }
        catch (\Exception $e) {
          $stats['errors']++;
          $this->logger->warning('Error ingestando convocatoria BOJA: @error', [
            '@error' => $e->getMessage(),
          ]);
        }
      }

      $this->logger->info('Sync BOJA completado: @new nuevas, @updated actualizadas, @errors errores.', [
        '@new' => $stats['new'],
        '@updated' => $stats['updated'],
        '@errors' => $stats['errors'],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error en sincronizacion BOJA: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $stats;
  }

  /**
   * Ingesta una convocatoria individual y la persiste como entidad.
   *
   * @param array $data
   *   Datos normalizados de la convocatoria.
   * @param string $source
   *   Fuente de datos: 'bdns' o 'boja'.
   *
   * @return int|null
   *   ID de la entidad creada o NULL si fallo.
   */
  public function ingestConvocatoria(array $data, string $source): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_call');

      $entity = $storage->create([
        'source_id' => $data['source_id'] ?? '',
        'source' => $source,
        'title' => $data['title'] ?? '',
        'description' => $data['description'] ?? '',
        'organism' => $data['organism'] ?? '',
        'region' => $data['region'] ?? '',
        'beneficiary_types' => $data['beneficiary_types'] ?? [],
        'sectors' => $data['sectors'] ?? [],
        'amount_min' => $data['amount_min'] ?? 0,
        'amount_max' => $data['amount_max'] ?? 0,
        'deadline' => $data['deadline'] ?? NULL,
        'publication_date' => $data['publication_date'] ?? NULL,
        'url' => $data['url'] ?? '',
        'status' => $data['status'] ?? 'abierta',
        'created' => \Drupal::time()->getRequestTime(),
      ]);
      $entity->save();

      $this->logger->info('Convocatoria ingestada: @title (source: @source, ID: @id).', [
        '@title' => $data['title'] ?? '',
        '@source' => $source,
        '@id' => $entity->id(),
      ]);

      return (int) $entity->id();
    }
    catch (\Exception $e) {
      $this->logger->error('Error ingestando convocatoria: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Actualiza una convocatoria existente con datos nuevos.
   *
   * @param int $entityId
   *   ID de la entidad FundingCall a actualizar.
   * @param array $data
   *   Datos normalizados de la convocatoria.
   *
   * @return bool
   *   TRUE si la actualizacion fue exitosa, FALSE en caso contrario.
   */
  public function updateConvocatoria(int $entityId, array $data): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_call');
      $entity = $storage->load($entityId);

      if (!$entity) {
        $this->logger->warning('Convocatoria @id no encontrada para actualizar.', [
          '@id' => $entityId,
        ]);
        return FALSE;
      }

      $fieldsToUpdate = [
        'title', 'description', 'organism', 'region',
        'beneficiary_types', 'sectors', 'amount_min', 'amount_max',
        'deadline', 'url', 'status',
      ];

      foreach ($fieldsToUpdate as $field) {
        if (isset($data[$field]) && $entity->hasField($field)) {
          $entity->set($field, $data[$field]);
        }
      }

      $entity->save();

      $this->logger->info('Convocatoria @id actualizada.', ['@id' => $entityId]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error actualizando convocatoria @id: @error', [
        '@id' => $entityId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Busca una convocatoria existente por su ID de fuente.
   *
   * @param string $sourceId
   *   Identificador de la fuente (BDNS ID o BOJA ID).
   * @param string $source
   *   Fuente de datos: 'bdns' o 'boja'.
   *
   * @return int|null
   *   ID de la entidad existente o NULL si no se encuentra.
   */
  public function findExistingBySourceId(string $sourceId, string $source): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_call');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('source_id', $sourceId)
        ->condition('source', $source)
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        return (int) reset($ids);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error buscando convocatoria existente @sourceId/@source: @error', [
        '@sourceId' => $sourceId,
        '@source' => $source,
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Encola items para procesamiento asincrono.
   *
   * @param array $items
   *   Array de datos crudos de convocatorias para encolar.
   * @param string $source
   *   Fuente de datos: 'bdns' o 'boja'.
   *
   * @return int
   *   Numero de items encolados.
   */
  public function queueBatchIngestion(array $items, string $source): int {
    $queue = $this->queueFactory->get('jaraba_funding_ingestion');
    $queued = 0;

    foreach ($items as $item) {
      $queue->createItem([
        'source' => $source,
        'data' => $item,
      ]);
      $queued++;
    }

    $this->logger->info('Encolados @count items de @source para ingestion.', [
      '@count' => $queued,
      '@source' => $source,
    ]);

    return $queued;
  }

}
