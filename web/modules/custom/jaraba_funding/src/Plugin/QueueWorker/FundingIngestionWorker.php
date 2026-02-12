<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_funding\Service\Ingestion\FundingIngestionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa items de ingestion de convocatorias de subvenciones desde la cola.
 *
 * Los items son encolados por FundingIngestionService::queueBatchIngestion()
 * o por acciones manuales de administracion. Cada item contiene la fuente
 * ('bdns' o 'boja') y los datos crudos de una convocatoria para ser
 * normalizada e ingestada.
 *
 * @QueueWorker(
 *   id = "jaraba_funding_ingestion",
 *   title = @Translation("Funding Ingestion Worker"),
 *   cron = {"time" = 60}
 * )
 */
class FundingIngestionWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Configuracion del plugin.
   * @param string $plugin_id
   *   ID del plugin.
   * @param mixed $plugin_definition
   *   Definicion del plugin.
   * @param \Drupal\jaraba_funding\Service\Ingestion\FundingIngestionService $ingestionService
   *   Servicio de ingestion de convocatorias.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected FundingIngestionService $ingestionService,
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
      $container->get('jaraba_funding.ingestion'),
      $container->get('logger.channel.jaraba_funding'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Procesa un item de ingestion de convocatoria.
   *
   * El $data debe contener:
   * - source: (string) Fuente de datos: 'bdns' o 'boja'.
   * - data: (array) Datos crudos de la convocatoria.
   */
  public function processItem($data): void {
    $source = $data['source'] ?? '';
    $rawData = $data['data'] ?? [];

    if (empty($source) || empty($rawData)) {
      $this->logger->warning('Item de ingestion incompleto: falta source o data.');
      return;
    }

    try {
      $entityId = $this->ingestionService->ingestConvocatoria($rawData, $source);

      if ($entityId !== NULL) {
        $this->logger->info('Convocatoria ingestada via cola (source: @source, entity: @id).', [
          '@source' => $source,
          '@id' => $entityId,
        ]);
      }
      else {
        $this->logger->warning('Ingestion fallida via cola para source @source.', [
          '@source' => $source,
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando item de ingestion de subvencion: @error', [
        '@error' => $e->getMessage(),
      ]);
      // No relanzar la excepcion para evitar reencolar indefinidamente.
    }
  }

}
