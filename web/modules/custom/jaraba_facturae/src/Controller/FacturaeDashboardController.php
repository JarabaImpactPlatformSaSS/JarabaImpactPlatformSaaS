<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_facturae\Entity\FacturaeDocument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dashboard controller for Facturae admin pages.
 *
 * Provides the dashboard, document listing, FACe log, and settings pages.
 *
 * Spec: Doc 180, Seccion 7.
 * Plan: FASE 7, entregable F7-4.
 */
class FacturaeDashboardController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->entityTypeManager = $entityTypeManager;
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
   * Dashboard page with stats and recent documents.
   */
  public function dashboard(): array {
    $storage = $this->entityTypeManager->getStorage('facturae_document');

    $stats = $this->loadDashboardStats($storage);
    $recentDocuments = $this->loadRecentDocuments($storage, 6);

    return [
      '#theme' => 'facturae_dashboard',
      '#facturae_stats' => $stats,
      '#facturae_recent' => $recentDocuments,
      '#attached' => [
        'library' => ['jaraba_facturae/facturae-dashboard'],
      ],
    ];
  }

  /**
   * Documents listing page with filters.
   */
  public function documents(): array {
    return [
      '#theme' => 'facturae_documents',
      '#attached' => [
        'library' => ['jaraba_facturae/facturae-documents'],
      ],
    ];
  }

  /**
   * Document detail page.
   */
  public function documentDetail(FacturaeDocument $facturae_document): array {
    return [
      '#theme' => 'facturae_document_detail',
      '#document' => $facturae_document,
      '#attached' => [
        'library' => ['jaraba_facturae/global'],
      ],
    ];
  }

  /**
   * FACe communication log page.
   */
  public function faceLog(): array {
    return [
      '#type' => 'markup',
      '#markup' => '<div id="facturae-face-log"></div>',
      '#attached' => [
        'library' => ['jaraba_facturae/global'],
      ],
    ];
  }

  /**
   * Settings and configuration page.
   */
  public function settings(): array {
    return [
      '#type' => 'markup',
      '#markup' => '<div id="facturae-settings"></div>',
      '#attached' => [
        'library' => ['jaraba_facturae/global'],
      ],
    ];
  }

  /**
   * Loads dashboard statistics.
   */
  protected function loadDashboardStats($storage): array {
    $stats = [
      'total' => 0,
      'draft' => 0,
      'signed' => 0,
      'sent' => 0,
      'rejected' => 0,
      'paid' => 0,
    ];

    foreach (['draft', 'validated', 'signed', 'sent', 'error'] as $status) {
      $count = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', $status)
        ->count()
        ->execute();
      $stats[$status] = (int) $count;
      $stats['total'] += (int) $count;
    }

    // FACe-specific counts.
    foreach (['rejected', 'paid'] as $faceStatus) {
      $count = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('face_status', $faceStatus)
        ->count()
        ->execute();
      $stats[$faceStatus] = (int) $count;
    }

    return $stats;
  }

  /**
   * Loads recent documents.
   */
  protected function loadRecentDocuments($storage, int $limit): array {
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->execute();

    $documents = $storage->loadMultiple($ids);
    $data = [];

    foreach ($documents as $document) {
      $data[] = [
        'id' => $document->id(),
        'facturae_number' => $document->get('facturae_number')->value ?? '',
        'buyer_name' => $document->get('buyer_name')->value ?? '',
        'total_invoice_amount' => $document->get('total_invoice_amount')->value ?? '0.00',
        'status' => $document->get('status')->value ?? 'draft',
        'face_status' => $document->get('face_status')->value ?? 'not_sent',
        'created' => $document->get('created')->value ?? NULL,
      ];
    }

    return $data;
  }

}
