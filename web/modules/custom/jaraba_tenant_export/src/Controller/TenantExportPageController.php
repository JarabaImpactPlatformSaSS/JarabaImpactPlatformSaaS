<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_tenant_export\Service\TenantExportService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador de la página frontend de exportación de datos.
 *
 * Directriz 3.4: Template zero-region, sin bloques, sin sidebar admin.
 */
class TenantExportPageController extends ControllerBase {

  public function __construct(
    protected TenantContextService $tenantContext,
    protected TenantExportService $exportService,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.tenant_context'),
      $container->get('jaraba_tenant_export.export_service'),
    );
  }

  /**
   * Página principal de exportación de datos del tenant.
   */
  public function page(): array {
    $tenant = $this->tenantContext->getCurrentTenant();
    $groupId = $tenant ? (int) $tenant->id() : 0;
    $tenantEntityId = $tenant ? (int) ($tenant->get('tenant_entity_id')->target_id ?? $tenant->id()) : 0;

    $canRequest = $groupId ? $this->exportService->canRequestExport($groupId) : ['allowed' => FALSE, 'retry_after' => 0, 'retry_after_formatted' => ''];
    $exports = $groupId ? $this->exportService->getExportHistory($groupId) : [];
    $dataCollector = \Drupal::service('jaraba_tenant_export.data_collector');
    $sections = $dataCollector->getAvailableSections();

    return [
      '#theme' => 'page__tenant_export',
      '#attached' => [
        'library' => ['jaraba_tenant_export/tenant-export-dashboard'],
        'drupalSettings' => [
          'jarabaTenantExport' => [
            'apiBase' => '/api/v1/tenant-export',
            'tenantId' => $groupId,
            'canRequest' => $canRequest['allowed'],
            'rateLimitInfo' => $canRequest,
            'sections' => $sections,
            'exports' => $exports,
          ],
        ],
      ],
      'tenant' => $tenant,
      'exports' => $exports,
      'can_request' => $canRequest['allowed'],
      'sections' => $sections,
      'rate_limit_info' => $canRequest,
    ];
  }

}
