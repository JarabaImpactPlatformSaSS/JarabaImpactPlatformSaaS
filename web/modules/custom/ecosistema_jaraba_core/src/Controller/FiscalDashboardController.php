<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ecosistema_jaraba_core\Service\FiscalComplianceService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Controller for the unified Fiscal Compliance Dashboard.
 *
 * Aggregates compliance data from jaraba_verifactu, jaraba_facturae
 * and jaraba_einvoice_b2b into a single dashboard view at
 * /admin/jaraba/fiscal. Uses optional DI for modules not installed.
 *
 * Plan Implementacion Stack Cumplimiento Fiscal v1 — FASE 11, F11-1.
 */
class FiscalDashboardController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected FiscalComplianceService $complianceService,
    protected LoggerInterface $logger,
    protected readonly TenantContextService $tenantContext,
    protected ?object $hashService = NULL,
    protected ?object $faceClient = NULL,
    protected ?object $paymentStatusService = NULL,
    protected ?object $certificateManager = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $hashService = NULL;
    $faceClient = NULL;
    $paymentStatusService = NULL;

    if ($container->has('jaraba_verifactu.hash_service')) {
      $hashService = $container->get('jaraba_verifactu.hash_service');
    }
    if ($container->has('jaraba_facturae.face_client')) {
      $faceClient = $container->get('jaraba_facturae.face_client');
    }
    if ($container->has('jaraba_einvoice_b2b.payment_status_service')) {
      $paymentStatusService = $container->get('jaraba_einvoice_b2b.payment_status_service');
    }

    $certificateManager = NULL;
    if ($container->has('ecosistema_jaraba_core.certificate_manager')) {
      $certificateManager = $container->get('ecosistema_jaraba_core.certificate_manager');
    }

    return new static(
      $container->get('ecosistema_jaraba_core.fiscal_compliance'),
      $container->get('logger.channel.ecosistema_jaraba_core'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
      $hashService,
      $faceClient,
      $paymentStatusService,
      $certificateManager,
    );
  }

  /**
   * Unified Fiscal Dashboard page.
   *
   * Renders compliance score, per-module stats, alerts, and
   * certificate status for the current tenant context.
   */
  public function dashboard(): array {
    $tenantId = $this->resolveCurrentTenantId();
    $compliance = $this->complianceService->calculateScore($tenantId);
    $installedModules = $this->complianceService->getInstalledModules();

    $verifactuStats = $this->loadVerifactuStats();
    $facturaeStats = $this->loadFacturaeStats();
    $einvoiceStats = $this->loadEinvoiceStats();
    $certificateStatus = $this->loadCertificateStatus($tenantId);

    return [
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/fiscal-styles',
        ],
        'drupalSettings' => [
          'fiscalDashboard' => [
            'compliance' => $compliance,
            'installedModules' => $installedModules,
            'verifactuStats' => $verifactuStats,
            'facturaeStats' => $facturaeStats,
            'einvoiceStats' => $einvoiceStats,
            'certificateStatus' => $certificateStatus,
          ],
        ],
      ],
      '#fiscal_compliance' => $compliance,
      '#fiscal_installed_modules' => $installedModules,
      '#fiscal_verifactu_stats' => $verifactuStats,
      '#fiscal_facturae_stats' => $facturaeStats,
      '#fiscal_einvoice_stats' => $einvoiceStats,
      '#fiscal_certificate_status' => $certificateStatus,
      '#fiscal_tenant_id' => $tenantId,
      'content' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['fiscal-main', 'fiscal-dashboard']],
        'title' => [
          '#markup' => '<h1 class="fiscal-main__title">' . $this->t('Fiscal Compliance Dashboard') . '</h1>',
        ],
        'description' => [
          '#markup' => '<p class="fiscal-main__description">' . $this->t('Unified compliance monitoring: VeriFactu, Facturae B2G, E-Factura B2B, and digital certificates.') . '</p>',
        ],
      ],
    ];
  }

  /**
   * Loads VeriFactu module statistics.
   *
   * @return array
   *   Stats array or empty if module not installed.
   */
  protected function loadVerifactuStats(): array {
    if ($this->hashService === NULL) {
      return ['installed' => FALSE];
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('verifactu_invoice_record');

      $total = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute();

      $pending = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('aeat_status', 'pending')
        ->count()
        ->execute();

      $accepted = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('aeat_status', 'accepted')
        ->count()
        ->execute();

      $rejected = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('aeat_status', 'rejected')
        ->count()
        ->execute();

      return [
        'installed' => TRUE,
        'total_records' => $total,
        'pending_records' => $pending,
        'accepted_records' => $accepted,
        'rejected_records' => $rejected,
        'dashboard_url' => '/admin/jaraba/verifactu',
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('FiscalDashboard: Error loading VeriFactu stats: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['installed' => TRUE, 'error' => TRUE];
    }
  }

  /**
   * Loads Facturae B2G module statistics.
   *
   * @return array
   *   Stats array or empty if module not installed.
   */
  protected function loadFacturaeStats(): array {
    if ($this->faceClient === NULL) {
      return ['installed' => FALSE];
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('facturae_invoice');

      $total = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute();

      $submitted = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('face_status', ['submitted', 'accepted', 'paid'], 'IN')
        ->count()
        ->execute();

      $rejected = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('face_status', 'rejected')
        ->count()
        ->execute();

      $draft = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('face_status', 'draft')
        ->count()
        ->execute();

      return [
        'installed' => TRUE,
        'total_invoices' => $total,
        'submitted_invoices' => $submitted,
        'rejected_invoices' => $rejected,
        'draft_invoices' => $draft,
        'dashboard_url' => '/admin/jaraba/facturae',
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('FiscalDashboard: Error loading Facturae stats: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['installed' => TRUE, 'error' => TRUE];
    }
  }

  /**
   * Loads E-Invoice B2B module statistics.
   *
   * @return array
   *   Stats array or empty if module not installed.
   */
  protected function loadEinvoiceStats(): array {
    if ($this->paymentStatusService === NULL) {
      return ['installed' => FALSE];
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('einvoice_document');

      $total = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute();

      $outbound = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('direction', 'outbound')
        ->count()
        ->execute();

      $inbound = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('direction', 'inbound')
        ->count()
        ->execute();

      $overdue = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('payment_status', 'overdue')
        ->count()
        ->execute();

      return [
        'installed' => TRUE,
        'total_documents' => $total,
        'outbound_documents' => $outbound,
        'inbound_documents' => $inbound,
        'overdue_documents' => $overdue,
        'dashboard_url' => '/admin/jaraba/einvoice',
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('FiscalDashboard: Error loading E-Invoice stats: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['installed' => TRUE, 'error' => TRUE];
    }
  }

  /**
   * Loads certificate status for the tenant.
   *
   * @param string $tenantId
   *   The tenant ID.
   *
   * @return array
   *   Certificate status data.
   */
  protected function loadCertificateStatus(string $tenantId): array {
    if ($this->certificateManager === NULL) {
      return ['available' => FALSE];
    }

    try {
      $validation = $this->certificateManager->validateTenantCertificate($tenantId);
      $data = method_exists($validation, 'toArray') ? $validation->toArray() : (array) $validation;

      return [
        'available' => TRUE,
        'is_valid' => $data['is_valid'] ?? FALSE,
        'days_remaining' => $data['days_remaining'] ?? 0,
        'subject' => $data['subject'] ?? '',
        'issuer' => $data['issuer'] ?? '',
        'not_after' => $data['not_after'] ?? '',
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('FiscalDashboard: Error loading certificate status: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['available' => TRUE, 'error' => TRUE];
    }
  }

  /**
   * Resolves the current tenant ID from context.
   *
   * @return string
   *   The tenant ID, or '0' if no tenant context available.
   */
  protected function resolveCurrentTenantId(): string {
    try {
      $tenantContext = $this->tenantContext;
      $tenant = $tenantContext->getCurrentTenant();
      if ($tenant) {
        return (string) $tenant->id();
      }
    }
    catch (\Throwable) {
      // Tenant context may not be available in all scenarios.
    }
    return '0';
  }

}
