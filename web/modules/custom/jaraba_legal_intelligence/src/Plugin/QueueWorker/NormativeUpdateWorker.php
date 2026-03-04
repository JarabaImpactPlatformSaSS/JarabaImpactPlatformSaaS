<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa actualizaciones de vigencia normativa.
 *
 * ESTRUCTURA:
 * Plugin QueueWorker que consume items de la cola
 * jaraba_legal_intelligence_normative_update. Se ejecuta durante cron
 * con un maximo de 60 segundos por ejecucion.
 *
 * LOGICA:
 * Cuando el pipeline de ingesta detecta una nueva norma que deroga o modifica
 * otra, encola un item para que este worker:
 * 1) Cargue la LegalNorm afectada por ID.
 * 2) Actualice su status (ej: vigente -> derogada).
 * 3) Cree o actualice la LegalNormRelation correspondiente.
 * 4) Actualice el campo superseded_by si aplica.
 *
 * RELACIONES:
 * - NormativeUpdateWorker -> EntityTypeManagerInterface: carga LegalNorm.
 * - NormativeUpdateWorker -> LegalNormRelation: crea relaciones.
 * - NormativeUpdateWorker <- QueueFactory: consume items encolados.
 * - NormativeUpdateWorker <- Drupal cron: ejecutado por sistema de colas.
 *
 * @QueueWorker(
 *   id = "jaraba_legal_intelligence_normative_update",
 *   title = @Translation("Normative Update Queue"),
 *   cron = {"time" = 60}
 * )
 */
class NormativeUpdateWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger del modulo Legal Intelligence.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Construye una nueva instancia de NormativeUpdateWorker.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_legal_intelligence'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Procesa un item de actualizacion normativa.
   *
   * @param mixed $data
   *   Datos del item de cola:
   *   - affected_norm_id: (int) ID de la LegalNorm afectada.
   *   - source_norm_id: (int) ID de la norma que causa el cambio.
   *   - relation_type: (string) Tipo de relacion (deroga_total, modifica, etc).
   *   - new_status: (string) Nuevo estado para la norma afectada.
   *   - affected_articles: (array|null) Articulos concretos afectados.
   *   - tenant_id: (int) ID del tenant.
   */
  public function processItem($data): void {
    $affectedNormId = $data['affected_norm_id'] ?? NULL;
    $sourceNormId = $data['source_norm_id'] ?? NULL;

    if ($affectedNormId === NULL || $sourceNormId === NULL) {
      $this->logger->warning('NormativeUpdateWorker: Item sin affected_norm_id o source_norm_id. Descartado.');
      return;
    }

    try {
      $normStorage = $this->entityTypeManager->getStorage('legal_norm');
    }
    catch (\Throwable $e) {
      $this->logger->error('NormativeUpdateWorker: Error accediendo al storage de legal_norm: @message', [
        '@message' => $e->getMessage(),
      ]);
      return;
    }

    $affectedNorm = $normStorage->load($affectedNormId);
    if ($affectedNorm === NULL) {
      $this->logger->warning('NormativeUpdateWorker: Norma afectada @id no encontrada.', [
        '@id' => $affectedNormId,
      ]);
      return;
    }

    $relationType = $data['relation_type'] ?? 'modifica';
    $newStatus = $data['new_status'] ?? NULL;
    $tenantId = $data['tenant_id'] ?? $affectedNorm->get('tenant_id')->target_id;

    try {
      // 1. Actualizar status de la norma afectada si se indica nuevo status.
      if ($newStatus) {
        $affectedNorm->set('status', $newStatus);

        // Si es derogacion total, establecer superseded_by.
        if ($relationType === 'deroga_total' || $relationType === 'sustituye') {
          $affectedNorm->set('superseded_by', $sourceNormId);
        }

        $affectedNorm->save();
      }

      // 2. Crear la relacion normativa (legal_norm_relation).
      if ($this->entityTypeManager->hasDefinition('legal_norm_relation')) {
        $relationStorage = $this->entityTypeManager->getStorage('legal_norm_relation');

        // Evitar duplicados: buscar relacion existente.
        $existing = $relationStorage->loadByProperties([
          'source_norm_id' => $sourceNormId,
          'target_norm_id' => $affectedNormId,
          'relation_type' => $relationType,
        ]);

        if (empty($existing)) {
          $relation = $relationStorage->create([
            'source_norm_id' => $sourceNormId,
            'target_norm_id' => $affectedNormId,
            'relation_type' => $relationType,
            'tenant_id' => $tenantId,
            'effective_date' => time(),
          ]);

          if (!empty($data['affected_articles'])) {
            $relation->set('affected_articles', json_encode($data['affected_articles']));
          }

          $relation->save();
        }
      }

      $this->logger->info('NormativeUpdateWorker: Norma @affected actualizada (@type) por norma @source.', [
        '@affected' => $affectedNormId,
        '@type' => $relationType,
        '@source' => $sourceNormId,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('NormativeUpdateWorker: Error procesando actualizacion de norma @id: @message', [
        '@id' => $affectedNormId,
        '@message' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

}
