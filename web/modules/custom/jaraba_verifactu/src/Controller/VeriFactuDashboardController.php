<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord;
use Drupal\jaraba_verifactu\Service\VeriFactuHashService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for VeriFactu admin dashboard pages.
 *
 * Renders the dashboard, records list, record detail, and remision
 * admin pages. Injects Twig variables for zero-region templates and
 * provides fallback render arrays for standard Drupal page rendering.
 *
 * Spec: Doc 179. Plan: FASE 3-4, entregables F3-5, F4-3.
 */
class VeriFactuDashboardController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected VeriFactuHashService $hashService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_verifactu.hash_service'),
      $container->get('logger.channel.jaraba_verifactu'),
    );
  }

  /**
   * VeriFactu dashboard page.
   *
   * Prepares stats, recent records, chain status, and certificate info
   * as drupalSettings for the zero-region page template.
   */
  public function dashboard(): array {
    $stats = $this->loadDashboardStats();
    $recentRecords = $this->loadRecentRecords(6);
    $chainStatus = $this->loadChainStatus();

    return [
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/fiscal-styles',
          'jaraba_verifactu/verifactu-dashboard',
        ],
        'drupalSettings' => [
          'verifactu' => [
            'stats' => $stats,
            'chainStatus' => $chainStatus,
          ],
        ],
      ],
      '#verifactu_stats' => $stats,
      '#verifactu_records' => $recentRecords,
      '#verifactu_chain' => $chainStatus,
      'content' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['fiscal-main', 'verifactu-dashboard']],
        'title' => [
          '#markup' => '<h1 class="fiscal-main__title">' . $this->t('VeriFactu Dashboard') . '</h1>',
        ],
        'description' => [
          '#markup' => '<p class="fiscal-main__description">' . $this->t('VeriFactu compliance management: invoice records, AEAT remision, and audit trail.') . '</p>',
        ],
      ],
    ];
  }

  /**
   * VeriFactu records list page with server-side filtering.
   */
  public function records(Request $request): array {
    $filters = [
      'status' => $request->query->get('status', ''),
      'record_type' => $request->query->get('record_type', ''),
      'date_from' => $request->query->get('date_from', ''),
      'date_to' => $request->query->get('date_to', ''),
    ];
    $page = max(1, (int) $request->query->get('page', '1'));
    $perPage = 25;

    $records = $this->loadFilteredRecords($filters, $page, $perPage);
    $total = $this->countFilteredRecords($filters);
    $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

    return [
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/fiscal-styles',
          'jaraba_verifactu/verifactu-records',
        ],
      ],
      '#verifactu_records' => $records,
      '#verifactu_pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
      ],
      '#verifactu_filters' => $filters,
      'content' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['fiscal-main']],
        'title' => [
          '#markup' => '<h1 class="fiscal-main__title">' . $this->t('VeriFactu Invoice Records') . '</h1>',
        ],
        'list' => $this->entityTypeManager()->getListBuilder('verifactu_invoice_record')->render(),
      ],
    ];
  }

  /**
   * VeriFactu record detail page.
   */
  public function recordDetail(VeriFactuInvoiceRecord $verifactu_invoice_record): array {
    $record = $verifactu_invoice_record;

    return [
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/fiscal-styles',
          'jaraba_verifactu/verifactu-dashboard',
        ],
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['fiscal-main', 'verifactu-record-detail']],
        'title' => [
          '#markup' => '<h1 class="fiscal-main__title">' . $this->t('Record: @number', ['@number' => $record->get('numero_factura')->value]) . '</h1>',
        ],
        'view' => $this->entityTypeManager()->getViewBuilder('verifactu_invoice_record')->view($record, 'full'),
      ],
    ];
  }

  /**
   * AEAT remision batches page.
   */
  public function remision(): array {
    return [
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/fiscal-styles',
          'jaraba_verifactu/verifactu-dashboard',
        ],
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['fiscal-main']],
        'title' => [
          '#markup' => '<h1 class="fiscal-main__title">' . $this->t('AEAT Remision Batches') . '</h1>',
        ],
        'list' => $this->entityTypeManager()->getListBuilder('verifactu_remision_batch')->render(),
      ],
    ];
  }

  /**
   * Loads dashboard statistics.
   *
   * @return array
   *   Stats array with record and batch counts by status.
   */
  protected function loadDashboardStats(): array {
    try {
      $recordStorage = $this->entityTypeManager()->getStorage('verifactu_invoice_record');
      $batchStorage = $this->entityTypeManager()->getStorage('verifactu_remision_batch');

      $totalRecords = (int) $recordStorage->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute();

      $pendingRecords = (int) $recordStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('aeat_status', 'pending')
        ->count()
        ->execute();

      $acceptedRecords = (int) $recordStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('aeat_status', 'accepted')
        ->count()
        ->execute();

      $rejectedRecords = (int) $recordStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('aeat_status', 'rejected')
        ->count()
        ->execute();

      $totalBatches = (int) $batchStorage->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute();

      $sentBatches = (int) $batchStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'sent')
        ->count()
        ->execute();

      $pendingBatches = (int) $batchStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', ['queued', 'sending'], 'IN')
        ->count()
        ->execute();

      $failedBatches = (int) $batchStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'failed')
        ->count()
        ->execute();

      return [
        'total_records' => $totalRecords,
        'pending_records' => $pendingRecords,
        'accepted_records' => $acceptedRecords,
        'rejected_records' => $rejectedRecords,
        'total_batches' => $totalBatches,
        'sent_batches' => $sentBatches,
        'pending_batches' => $pendingBatches,
        'failed_batches' => $failedBatches,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to load dashboard stats: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Loads recent invoice records for the dashboard.
   *
   * @param int $limit
   *   Maximum number of records to return.
   *
   * @return array
   *   Array of serialized record data.
   */
  protected function loadRecentRecords(int $limit): array {
    try {
      $storage = $this->entityTypeManager()->getStorage('verifactu_invoice_record');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $records = [];
      foreach ($storage->loadMultiple($ids) as $entity) {
        $records[] = $this->serializeRecord($entity);
      }
      return $records;
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to load recent records: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Loads chain integrity status.
   *
   * @return array
   *   Chain status data for the template.
   */
  protected function loadChainStatus(): array {
    try {
      $result = $this->hashService->verifyChainIntegrity();
      return $result->toArray();
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to load chain status: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['is_valid' => FALSE, 'error_message' => 'Unable to verify chain integrity.'];
    }
  }

  /**
   * Loads filtered records for the records listing page.
   *
   * @param array $filters
   *   Filter values (status, record_type, date_from, date_to).
   * @param int $page
   *   Current page number (1-indexed).
   * @param int $perPage
   *   Records per page.
   *
   * @return array
   *   Array of serialized record data.
   */
  protected function loadFilteredRecords(array $filters, int $page, int $perPage): array {
    try {
      $storage = $this->entityTypeManager()->getStorage('verifactu_invoice_record');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
        ->range(($page - 1) * $perPage, $perPage);

      $this->applyFilters($query, $filters);

      $ids = $query->execute();
      if (empty($ids)) {
        return [];
      }

      $records = [];
      foreach ($storage->loadMultiple($ids) as $entity) {
        $records[] = $this->serializeRecord($entity);
      }
      return $records;
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to load filtered records: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Counts filtered records for pagination.
   *
   * @param array $filters
   *   Filter values.
   *
   * @return int
   *   Total count of matching records.
   */
  protected function countFilteredRecords(array $filters): int {
    try {
      $storage = $this->entityTypeManager()->getStorage('verifactu_invoice_record');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->count();

      $this->applyFilters($query, $filters);

      return (int) $query->execute();
    }
    catch (\Throwable $e) {
      return 0;
    }
  }

  /**
   * Applies filter conditions to an entity query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query.
   * @param array $filters
   *   Filter values.
   */
  protected function applyFilters($query, array $filters): void {
    if (!empty($filters['status'])) {
      $query->condition('aeat_status', $filters['status']);
    }
    if (!empty($filters['record_type'])) {
      $query->condition('record_type', $filters['record_type']);
    }
    if (!empty($filters['date_from'])) {
      $query->condition('fecha_expedicion', $filters['date_from'], '>=');
    }
    if (!empty($filters['date_to'])) {
      $query->condition('fecha_expedicion', $filters['date_to'], '<=');
    }
  }

  /**
   * Serializes a VeriFactuInvoiceRecord entity to an array for templates.
   *
   * @param \Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord $entity
   *   The invoice record entity.
   *
   * @return array
   *   Serialized record data.
   */
  protected function serializeRecord(VeriFactuInvoiceRecord $entity): array {
    return [
      'id' => $entity->id(),
      'numero_factura' => $entity->get('numero_factura')->value ?? '',
      'record_type' => $entity->get('record_type')->value ?? 'alta',
      'tipo_factura' => $entity->get('tipo_factura')->value ?? '',
      'nif_emisor' => $entity->get('nif_emisor')->value ?? '',
      'nombre_emisor' => $entity->get('nombre_emisor')->value ?? '',
      'fecha_expedicion' => $entity->get('fecha_expedicion')->value ?? '',
      'importe_total' => $entity->get('importe_total')->value ?? '0.00',
      'aeat_status' => $entity->get('aeat_status')->value ?? 'draft',
      'hash_record' => $entity->get('hash_record')->value ?? '',
      'created' => date('Y-m-d H:i', (int) $entity->get('created')->value),
    ];
  }

}
