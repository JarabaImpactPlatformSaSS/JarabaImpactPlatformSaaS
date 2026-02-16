<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ecosistema_jaraba_core\Trait\ApiResponseTrait;
use Drupal\jaraba_verifactu\Service\VeriFactuEventLogService;
use Drupal\jaraba_verifactu\Service\VeriFactuHashService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for VeriFactu audit, integrity, and SIF event log endpoints.
 *
 * Provides 5 audit endpoints:
 * - Chain integrity status and full verification.
 * - SIF event log listing.
 * - Compliance statistics.
 * - SIF responsible declaration data.
 *
 * Also serves the admin audit log page (non-API).
 *
 * Spec: Doc 179, Seccion 6. Plan: FASE 3, entregable F3-5.
 */
class VeriFactuAuditController extends ControllerBase implements ContainerInjectionInterface {

  use ApiResponseTrait;

  public function __construct(
    protected VeriFactuHashService $hashService,
    protected VeriFactuEventLogService $eventLogService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_verifactu.hash_service'),
      $container->get('jaraba_verifactu.event_log_service'),
      $container->get('logger.channel.jaraba_verifactu'),
    );
  }

  // =========================================================================
  // API ENDPOINTS
  // =========================================================================

  /**
   * GET /api/v1/verifactu/audit/chain - Chain integrity status.
   */
  public function getChainStatus(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant not found.', 'TENANT_NOT_FOUND', 403);
      }

      $lastHash = $this->hashService->getLastChainHash($tenantId);

      $storage = $this->entityTypeManager()->getStorage('verifactu_invoice_record');
      $totalRecords = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      $pendingRecords = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->condition('aeat_status', 'pending')
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      return $this->apiSuccess([
        'tenant_id' => $tenantId,
        'total_records' => (int) $totalRecords,
        'pending_records' => (int) $pendingRecords,
        'last_chain_hash' => $lastHash,
        'chain_initialized' => $lastHash !== NULL,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('API getChainStatus error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/verifactu/audit/chain/verify - Full chain verification.
   */
  public function verifyChain(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant not found.', 'TENANT_NOT_FOUND', 403);
      }

      $result = $this->hashService->verifyChainIntegrity($tenantId);

      // Log the verification event.
      $this->eventLogService->logEvent('INTEGRITY_CHECK', $tenantId, NULL, [
        'description' => $result->isValid
          ? 'Chain integrity verified: ' . $result->totalRecords . ' records valid.'
          : 'Chain break detected at record ' . $result->breakAtRecordId,
        'severity' => $result->isValid ? 'info' : 'critical',
        'total_records' => $result->totalRecords,
        'valid_records' => $result->validRecords,
      ]);

      return $this->apiSuccess($result->toArray());
    }
    catch (\Exception $e) {
      $this->logger->error('API verifyChain error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Verification failed.', 'VERIFICATION_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/verifactu/audit/events - SIF event log listing.
   */
  public function listEvents(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant not found.', 'TENANT_NOT_FOUND', 403);
      }

      $limit = min(100, max(1, (int) ($request->query->get('limit', 50))));
      $offset = max(0, (int) ($request->query->get('offset', 0)));

      $storage = $this->entityTypeManager()->getStorage('verifactu_event_log');
      $query = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->sort('id', 'DESC')
        ->accessCheck(TRUE);

      if ($eventType = $request->query->get('event_type')) {
        $query->condition('event_type', $eventType);
      }
      if ($severity = $request->query->get('severity')) {
        $query->condition('severity', $severity);
      }

      $countQuery = clone $query;
      $total = $countQuery->count()->execute();

      $ids = $query->range($offset, $limit)->execute();
      $events = $storage->loadMultiple($ids);

      $items = [];
      foreach ($events as $event) {
        $items[] = [
          'id' => (int) $event->id(),
          'event_type' => $event->get('event_type')->value,
          'severity' => $event->get('severity')->value,
          'description' => $event->get('description')->value,
          'details' => $event->get('details')->value ? json_decode($event->get('details')->value, TRUE) : NULL,
          'record_id' => $event->get('record_id')->target_id,
          'actor_id' => $event->get('actor_id')->target_id,
          'ip_address' => $event->get('ip_address')->value,
          'hash_event' => $event->get('hash_event')->value,
          'created' => (int) $event->get('created')->value,
        ];
      }

      return $this->apiPaginated($items, (int) $total, $limit, $offset);
    }
    catch (\Exception $e) {
      $this->logger->error('API listEvents error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/verifactu/audit/stats - Compliance statistics.
   */
  public function getStats(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant not found.', 'TENANT_NOT_FOUND', 403);
      }

      $recordStorage = $this->entityTypeManager()->getStorage('verifactu_invoice_record');

      $stats = [];
      foreach (['pending', 'accepted', 'rejected', 'error'] as $status) {
        $stats['records_' . $status] = (int) $recordStorage->getQuery()
          ->condition('tenant_id', $tenantId)
          ->condition('aeat_status', $status)
          ->accessCheck(FALSE)
          ->count()
          ->execute();
      }

      $stats['records_total'] = array_sum($stats);

      // Batch stats.
      $batchStorage = $this->entityTypeManager()->getStorage('verifactu_remision_batch');
      foreach (['queued', 'sending', 'sent', 'partial_error', 'error'] as $status) {
        $stats['batches_' . $status] = (int) $batchStorage->getQuery()
          ->condition('tenant_id', $tenantId)
          ->condition('status', $status)
          ->accessCheck(FALSE)
          ->count()
          ->execute();
      }

      // Event log count.
      $eventStorage = $this->entityTypeManager()->getStorage('verifactu_event_log');
      $stats['events_total'] = (int) $eventStorage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      return $this->apiSuccess($stats);
    }
    catch (\Exception $e) {
      $this->logger->error('API getStats error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/verifactu/audit/declaration - SIF responsible declaration.
   */
  public function getDeclaration(Request $request): JsonResponse {
    try {
      $tenantId = $this->resolveTenantId($request);
      if (!$tenantId) {
        return $this->apiError('Tenant not found.', 'TENANT_NOT_FOUND', 403);
      }

      $settings = $this->config('jaraba_verifactu.settings');

      return $this->apiSuccess([
        'software_id' => $settings->get('software_id'),
        'software_version' => $settings->get('software_version'),
        'software_name' => $settings->get('software_name'),
        'software_developer_nif' => $settings->get('software_developer_nif'),
        'declaration' => 'El presente Sistema Informatico de Facturacion (SIF) cumple con los requisitos establecidos en el Real Decreto 1007/2023 y la Orden HAC/1177/2024.',
        'normative_reference' => 'RD 1007/2023, Orden HAC/1177/2024',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('API getDeclaration error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Internal error.', 'INTERNAL_ERROR', 500);
    }
  }

  // =========================================================================
  // ADMIN PAGES (NON-API)
  // =========================================================================

  /**
   * Admin page: VeriFactu audit log.
   */
  public function auditLog(): array {
    return [
      '#theme' => 'page__fiscal',
      '#attached' => [
        'library' => ['ecosistema_jaraba_core/fiscal-styles'],
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['fiscal-main']],
        'title' => [
          '#markup' => '<h1 class="fiscal-main__title">' . $this->t('VeriFactu Audit Log') . '</h1>',
        ],
        'list' => $this->entityTypeManager()->getListBuilder('verifactu_event_log')->render(),
      ],
    ];
  }

  /**
   * Resolves tenant ID from request context.
   */
  protected function resolveTenantId(Request $request): ?int {
    $tenantId = $request->query->get('tenant_id');
    if ($tenantId) {
      return (int) $tenantId;
    }

    $user = $this->currentUser();
    if ($user->isAuthenticated()) {
      $membershipLoader = \Drupal::service('group.membership_loader');
      $memberships = $membershipLoader->loadByUser($user);
      if (!empty($memberships)) {
        return (int) reset($memberships)->getGroup()->id();
      }
    }

    return NULL;
  }

}
