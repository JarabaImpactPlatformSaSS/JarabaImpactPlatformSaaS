<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_legal_knowledge\Service\LegalIngestionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa items de ingestion de normas legales desde la cola.
 *
 * Los items son encolados por el proceso de sincronizacion con el BOE
 * o por acciones manuales de administracion. Cada item contiene los
 * datos de una norma para ser ingestada (entidad + chunks + embeddings).
 *
 * @QueueWorker(
 *   id = "jaraba_legal_knowledge_norm_ingestion",
 *   title = @Translation("Legal Norm Ingestion Worker"),
 *   cron = {"time" = 60}
 * )
 */
class LegalNormIngestionWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Configuracion del plugin.
   * @param string $plugin_id
   *   ID del plugin.
   * @param mixed $plugin_definition
   *   Definicion del plugin.
   * @param \Drupal\jaraba_legal_knowledge\Service\LegalIngestionService $ingestionService
   *   Servicio de ingestion de normas legales.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LegalIngestionService $ingestionService,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jaraba_legal_knowledge.ingestion'),
      $container->get('logger.channel.jaraba_legal_knowledge'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Procesa un item de ingestion de norma legal.
   *
   * El $data debe contener las claves necesarias para LegalIngestionService:
   * - boe_id: (string) Identificador BOE.
   * - title: (string) Titulo de la norma.
   * - norm_type: (string) Tipo de norma.
   * - publication_date: (string) Fecha de publicacion.
   * - department: (string) Departamento emisor.
   * - subject_areas: (array) Areas tematicas.
   * - scope: (string) Ambito.
   * - status: (string) Estado.
   *
   * Si se incluye 'action' => 'reindex' y 'norm_id', se re-indexa
   * la norma existente en lugar de ingestar una nueva.
   */
  public function processItem($data): void {
    $action = $data['action'] ?? 'ingest';

    try {
      if ($action === 'reindex' && !empty($data['norm_id'])) {
        $success = $this->ingestionService->reindexNorm((int) $data['norm_id']);
        if ($success) {
          $this->logger->info('Norma @id re-indexada via cola.', [
            '@id' => $data['norm_id'],
          ]);
        }
        else {
          $this->logger->warning('Re-indexacion fallida para norma @id.', [
            '@id' => $data['norm_id'],
          ]);
        }
      }
      else {
        $entityId = $this->ingestionService->ingestNorm($data);
        if ($entityId !== NULL) {
          $this->logger->info('Norma @boe_id ingestada via cola (entity @id).', [
            '@boe_id' => $data['boe_id'] ?? 'unknown',
            '@id' => $entityId,
          ]);
        }
        else {
          $this->logger->warning('Ingestion fallida para norma @boe_id via cola.', [
            '@boe_id' => $data['boe_id'] ?? 'unknown',
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando item de ingestion: @error', [
        '@error' => $e->getMessage(),
      ]);
      // No relanzar la excepcion para evitar reencolar indefinidamente.
    }
  }

}
