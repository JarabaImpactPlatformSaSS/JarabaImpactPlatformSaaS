<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller para exportar logs de auditoría en formato CSV.
 *
 * GET /api/v1/security/audit/export
 * Parámetros opcionales: start_date, end_date, severity.
 */
class AuditExportController extends ControllerBase {

  /**
   * Constructor con inyección de dependencias.
   */
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
   * Exports audit log entries as CSV.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   A streamed CSV response.
   */
  public function export(Request $request): StreamedResponse {
    $startDate = $request->query->get('start_date');
    $endDate = $request->query->get('end_date');
    $severity = $request->query->get('severity');

    $response = new StreamedResponse(function () use ($startDate, $endDate, $severity) {
      $handle = fopen('php://output', 'w');

      // CSV header.
      fputcsv($handle, [
        'ID',
        'Event Type',
        'Severity',
        'Actor ID',
        'IP Address',
        'Target Type',
        'Target ID',
        'Tenant ID',
        'Details',
        'Created',
      ]);

      try {
        $storage = $this->entityTypeManager->getStorage('security_audit_log');
        $query = $storage->getQuery()
          ->accessCheck(TRUE)
          ->sort('created', 'DESC');

        // Apply date filters.
        if ($startDate) {
          $startTimestamp = strtotime($startDate);
          if ($startTimestamp) {
            $query->condition('created', $startTimestamp, '>=');
          }
        }

        if ($endDate) {
          $endTimestamp = strtotime($endDate . ' 23:59:59');
          if ($endTimestamp) {
            $query->condition('created', $endTimestamp, '<=');
          }
        }

        // Apply severity filter.
        if ($severity && in_array($severity, ['info', 'notice', 'warning', 'critical'], TRUE)) {
          $query->condition('severity', $severity);
        }

        // Limit to 10000 records for export.
        $query->range(0, 10000);
        $ids = $query->execute();

        if (!empty($ids)) {
          $entities = $storage->loadMultiple($ids);
          foreach ($entities as $entity) {
            /** @var \Drupal\jaraba_security_compliance\Entity\SecurityAuditLog $entity */
            $created = (int) $entity->get('created')->value;
            fputcsv($handle, [
              $entity->id(),
              $entity->getEventType(),
              $entity->getSeverity(),
              $entity->getActorId() ?? '',
              $entity->getIpAddress(),
              $entity->get('target_type')->value ?? '',
              $entity->get('target_id')->value ?? '',
              $entity->getTenantId() ?? '',
              $entity->get('details')->value ?? '',
              $created ? date('Y-m-d H:i:s', $created) : '',
            ]);
          }
        }
      }
      catch (\Exception $e) {
        fputcsv($handle, ['Error', $e->getMessage()]);
      }

      fclose($handle);
    });

    $filename = 'security-audit-log-' . date('Y-m-d-His') . '.csv';
    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

    return $response;
  }

}
