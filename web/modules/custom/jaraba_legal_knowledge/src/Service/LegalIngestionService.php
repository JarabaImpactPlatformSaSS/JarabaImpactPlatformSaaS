<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de ingestion de normas legales desde el BOE.
 *
 * Orquesta el pipeline completo de ingestion:
 * 1. Obtener datos de la norma desde la API del BOE.
 * 2. Crear/actualizar la entidad LegalNorm en Drupal.
 * 3. Segmentar el texto en chunks con LegalChunkingService.
 * 4. Generar embeddings y almacenar en Qdrant.
 *
 * ARQUITECTURA:
 * - Pipeline: BOE API -> Entity -> Chunking -> Embedding -> Qdrant.
 * - Upsert por boe_id: si la norma ya existe, se actualiza.
 * - Re-indexacion: elimina chunks/embeddings antiguos y regenera.
 * - Multi-tenant: las normas son compartidas (scope nacional).
 */
class LegalIngestionService {

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_legal_knowledge\Service\BoeApiClient $boeClient
   *   Cliente de la API del BOE.
   * @param \Drupal\jaraba_legal_knowledge\Service\LegalChunkingService $chunkingService
   *   Servicio de segmentacion de textos legales.
   * @param \Drupal\jaraba_legal_knowledge\Service\LegalEmbeddingService $embeddingService
   *   Servicio de embeddings y almacenamiento vectorial.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected BoeApiClient $boeClient,
    protected LegalChunkingService $chunkingService,
    protected LegalEmbeddingService $embeddingService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Ingesta una norma completa: entidad + chunks + embeddings.
   *
   * @param array $boeData
   *   Datos de la norma obtenidos del BOE. Claves esperadas:
   *   - boe_id: (string) Identificador BOE (e.g., "BOE-A-2006-20764").
   *   - title: (string) Titulo de la norma.
   *   - norm_type: (string) Tipo de norma (ley, real_decreto, etc.).
   *   - publication_date: (string) Fecha de publicacion YYYY-MM-DD.
   *   - department: (string) Departamento emisor.
   *   - subject_areas: (array) Areas tematicas.
   *   - scope: (string) Ambito (estatal, autonomico, local).
   *   - status: (string) Estado (vigente, derogada, modificada).
   *
   * @return int|null
   *   ID de la entidad LegalNorm creada/actualizada, o NULL si falla.
   */
  public function ingestNorm(array $boeData): ?int {
    $boeId = $boeData['boe_id'] ?? '';
    if (empty($boeId)) {
      $this->logger->error('Ingestion fallida: boe_id vacio.');
      return NULL;
    }

    try {
      // 1. Obtener texto completo desde BOE.
      $fullText = $this->boeClient->getNormFullText($boeId);
      if (empty($fullText)) {
        $this->logger->warning('No se pudo obtener texto completo para norma @id.', [
          '@id' => $boeId,
        ]);
        // Continuar sin texto completo; la entidad se crea con metadatos.
      }

      // 2. Crear o actualizar la entidad LegalNorm.
      $norm = $this->createOrUpdateNorm(array_merge($boeData, [
        'full_text' => $fullText ?? '',
      ]));
      $normId = (int) $norm->id();

      // 3. Si hay texto, segmentar y generar embeddings.
      if (!empty($fullText)) {
        $title = $boeData['title'] ?? $boeId;
        $chunks = $this->chunkingService->chunkNorm($fullText, $title);

        if (!empty($chunks)) {
          $processedChunks = $this->processChunks($norm, $chunks);
          $this->logger->info('Norma @id ingestada: @chunks chunks procesados.', [
            '@id' => $boeId,
            '@chunks' => $processedChunks,
          ]);
        }
      }

      return $normId;
    }
    catch (\Exception $e) {
      $this->logger->error('Error ingestando norma @id: @error', [
        '@id' => $boeId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Sincroniza normas desde el BOE para un rango de fechas.
   *
   * @param string $dateFrom
   *   Fecha de inicio en formato YYYYMMDD.
   * @param string|null $dateTo
   *   Fecha de fin en formato YYYYMMDD. Si es NULL, usa la fecha actual.
   *
   * @return array
   *   Resumen con claves:
   *   - ingested: (int) Normas ingestadas correctamente.
   *   - updated: (int) Normas actualizadas.
   *   - errors: (int) Errores de ingestion.
   */
  public function syncFromBoe(string $dateFrom, ?string $dateTo = null): array {
    $summary = ['ingested' => 0, 'updated' => 0, 'errors' => 0];

    $filters = [
      'fecha_desde' => $dateFrom,
      'fecha_hasta' => $dateTo ?? date('Ymd'),
    ];

    try {
      $page = 1;
      $hasMore = TRUE;

      while ($hasMore) {
        $result = $this->boeClient->searchNorms($filters, $page);
        $norms = $result['data'] ?? [];

        if (empty($norms)) {
          $hasMore = FALSE;
          break;
        }

        foreach ($norms as $normData) {
          $boeId = $normData['boe_id'] ?? $normData['id'] ?? '';
          if (empty($boeId)) {
            $summary['errors']++;
            continue;
          }

          // Verificar si la norma ya existe.
          $existing = $this->findNormByBoeId($boeId);

          try {
            $entityId = $this->ingestNorm($normData);
            if ($entityId !== NULL) {
              if ($existing) {
                $summary['updated']++;
              }
              else {
                $summary['ingested']++;
              }
            }
            else {
              $summary['errors']++;
            }
          }
          catch (\Exception $e) {
            $summary['errors']++;
            $this->logger->error('Error sincronizando norma @id: @error', [
              '@id' => $boeId,
              '@error' => $e->getMessage(),
            ]);
          }
        }

        // Verificar si hay mas paginas.
        $total = $result['total'] ?? 0;
        $processedSoFar = $page * count($norms);
        $hasMore = $processedSoFar < $total;
        $page++;
      }

      $this->logger->info('Sincronizacion BOE completada: @ingested nuevas, @updated actualizadas, @errors errores.', [
        '@ingested' => $summary['ingested'],
        '@updated' => $summary['updated'],
        '@errors' => $summary['errors'],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error general en sincronizacion BOE: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $summary;
  }

  /**
   * Re-indexa una norma: elimina embeddings antiguos y regenera.
   *
   * @param int $normId
   *   ID de la entidad LegalNorm a re-indexar.
   *
   * @return bool
   *   TRUE si la re-indexacion fue exitosa.
   */
  public function reindexNorm(int $normId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_norm');
      $norm = $storage->load($normId);

      if (!$norm) {
        $this->logger->warning('Re-indexacion fallida: norma @id no encontrada.', [
          '@id' => $normId,
        ]);
        return FALSE;
      }

      $boeId = $norm->get('boe_id')->value;
      $title = $norm->label() ?: $boeId;
      $fullText = $norm->get('full_text')->value ?? '';

      // 1. Eliminar chunks y embeddings existentes.
      $chunkStorage = $this->entityTypeManager->getStorage('legal_chunk');
      $existingChunkIds = $chunkStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('norm_id', $normId)
        ->execute();

      if (!empty($existingChunkIds)) {
        foreach ($existingChunkIds as $chunkId) {
          $pointId = 'legal_norm_' . $normId . '_chunk_' . $chunkId;
          $this->embeddingService->deleteEmbedding($pointId);
        }

        $existingChunks = $chunkStorage->loadMultiple($existingChunkIds);
        $chunkStorage->delete($existingChunks);
      }

      // 2. Re-chunking y re-embedding.
      if (empty($fullText)) {
        $fullText = $this->boeClient->getNormFullText($boeId);
        if (!empty($fullText)) {
          $norm->set('full_text', $fullText);
          $norm->save();
        }
      }

      if (!empty($fullText)) {
        $chunks = $this->chunkingService->chunkNorm($fullText, $title);
        $processed = $this->processChunks($norm, $chunks);

        $this->logger->info('Norma @id re-indexada: @chunks chunks.', [
          '@id' => $normId,
          '@chunks' => $processed,
        ]);
      }

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error re-indexando norma @id: @error', [
        '@id' => $normId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Crea o actualiza una entidad LegalNorm por boe_id (upsert).
   *
   * @param array $data
   *   Datos de la norma con claves de campo.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   La entidad LegalNorm creada o actualizada.
   */
  protected function createOrUpdateNorm(array $data) {
    $storage = $this->entityTypeManager->getStorage('legal_norm');
    $boeId = $data['boe_id'];

    // Buscar norma existente por boe_id.
    $existing = $this->findNormByBoeId($boeId);

    if ($existing) {
      $norm = $storage->load($existing);
      // Actualizar campos.
      if (!empty($data['title'])) {
        $norm->set('title', $data['title']);
      }
      if (!empty($data['norm_type'])) {
        $norm->set('norm_type', $data['norm_type']);
      }
      if (!empty($data['department'])) {
        $norm->set('department', $data['department']);
      }
      if (!empty($data['subject_areas'])) {
        $norm->set('subject_areas', $data['subject_areas']);
      }
      if (!empty($data['scope'])) {
        $norm->set('scope', $data['scope']);
      }
      if (!empty($data['status'])) {
        $norm->set('status', $data['status']);
      }
      if (!empty($data['full_text'])) {
        $norm->set('full_text', $data['full_text']);
      }
      $norm->set('last_synced', \Drupal::time()->getRequestTime());
      $norm->save();

      return $norm;
    }

    // Crear nueva norma.
    $norm = $storage->create([
      'boe_id' => $boeId,
      'title' => $data['title'] ?? $boeId,
      'norm_type' => $data['norm_type'] ?? 'other',
      'publication_date' => $data['publication_date'] ?? date('Y-m-d'),
      'department' => $data['department'] ?? '',
      'subject_areas' => $data['subject_areas'] ?? [],
      'scope' => $data['scope'] ?? 'estatal',
      'status' => $data['status'] ?? 'vigente',
      'full_text' => $data['full_text'] ?? '',
      'last_synced' => \Drupal::time()->getRequestTime(),
    ]);
    $norm->save();

    return $norm;
  }

  /**
   * Procesa chunks: crea entidades LegalChunk y genera embeddings.
   *
   * @param \Drupal\Core\Entity\EntityInterface $norm
   *   Entidad LegalNorm padre.
   * @param array $chunks
   *   Array de chunks generados por LegalChunkingService.
   *
   * @return int
   *   Numero de chunks procesados exitosamente.
   */
  protected function processChunks($norm, array $chunks): int {
    $chunkStorage = $this->entityTypeManager->getStorage('legal_chunk');
    $normId = (int) $norm->id();
    $boeId = $norm->get('boe_id')->value;
    $processed = 0;

    foreach ($chunks as $chunk) {
      try {
        // Crear entidad LegalChunk.
        $chunkEntity = $chunkStorage->create([
          'norm_id' => $normId,
          'content' => $chunk['content'],
          'section_title' => $chunk['section_title'] ?? '',
          'article_number' => $chunk['article_number'] ?? NULL,
          'chapter' => $chunk['chapter'] ?? NULL,
          'chunk_index' => $chunk['chunk_index'],
          'token_count' => $chunk['token_count'],
        ]);
        $chunkEntity->save();

        // Generar embedding y almacenar en Qdrant.
        $embedding = $this->embeddingService->generateEmbedding($chunk['content']);
        if ($embedding) {
          $pointId = 'legal_norm_' . $normId . '_chunk_' . $chunkEntity->id();
          $payload = [
            'norm_id' => $normId,
            'chunk_id' => (int) $chunkEntity->id(),
            'boe_id' => $boeId,
            'text' => $chunk['content'],
            'section_title' => $chunk['section_title'] ?? '',
            'article_number' => $chunk['article_number'] ?? NULL,
            'chapter' => $chunk['chapter'] ?? NULL,
            'chunk_index' => $chunk['chunk_index'],
            'norm_title' => $norm->label() ?? '',
            'indexed_at' => date('c'),
          ];

          $this->embeddingService->storeEmbedding($pointId, $embedding, $payload);
        }

        $processed++;
      }
      catch (\Exception $e) {
        $this->logger->error('Error procesando chunk @index de norma @norm: @error', [
          '@index' => $chunk['chunk_index'],
          '@norm' => $normId,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return $processed;
  }

  /**
   * Busca una norma existente por boe_id.
   *
   * @param string $boeId
   *   Identificador BOE.
   *
   * @return int|null
   *   ID de la entidad si existe, o NULL.
   */
  protected function findNormByBoeId(string $boeId): ?int {
    $storage = $this->entityTypeManager->getStorage('legal_norm');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('boe_id', $boeId)
      ->range(0, 1)
      ->execute();

    if (!empty($ids)) {
      return (int) reset($ids);
    }

    return NULL;
  }

}
