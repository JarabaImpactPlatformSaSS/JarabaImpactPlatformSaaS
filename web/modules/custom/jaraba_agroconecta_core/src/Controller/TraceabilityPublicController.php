<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agroconecta_core\Entity\AgroBatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controlador para la página pública de trazabilidad.
 */
class TraceabilityPublicController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Renderiza la historia del lote para el consumidor.
   *
   * GET /agroconecta/trace/{batch_code}
   */
  public function viewHistory(string $batch_code): array {
    $batch = $this->loadBatchByCode($batch_code);
    if (!$batch) {
      throw new NotFoundHttpException();
    }

    $events = $this->loadEvents($batch->id());
    $producer = $batch->get('producer_id')->entity;
    $product = $batch->get('product_id')->entity;

    // Registrar escaneo (anónimo).
    \Drupal::service('jaraba_agroconecta_core.qr_service')->recordScan(0, [
      'ip' => \Drupal::request()->getClientIp(),
      'user_agent' => \Drupal::request()->headers->get('User-Agent'),
    ]);

    return [
      '#theme' => 'agro_traceability_public',
      '#batch' => $batch,
      '#producer' => $producer,
      '#product' => $product,
      '#events' => $events,
      '#attached' => [
        'library' => ['jaraba_agroconecta_core/traceability-public'],
      ],
    ];
  }

  protected function loadBatchByCode(string $code): ?AgroBatch {
    $storage = $this->entityTypeManager->getStorage('agro_batch');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('batch_code', $code)
      ->range(0, 1)
      ->execute();

    return !empty($ids) ? $storage->load(reset($ids)) : NULL;
  }

  protected function loadEvents(int $batch_id): array {
    return $this->entityTypeManager->getStorage('trace_event_agro')->loadByProperties([
      'batch_id' => $batch_id,
    ], ['sequence' => 'ASC']);
  }

}
