<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\ResellerCommissionService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador del portal de partner/reseller (G117-4).
 *
 * Proporciona un dashboard dedicado para los resellers en /partner-portal
 * donde pueden ver sus tenants gestionados, comisiones y metricas
 * de salud de su cartera.
 *
 * Ruta: /partner-portal
 */
class ResellerPortalController extends ControllerBase {

  /**
   * Constructor con inyeccion de dependencias.
   *
   * @param \Drupal\ecosistema_jaraba_core\Service\ResellerCommissionService $commissionService
   *   Servicio de comisiones del reseller.
   */
  public function __construct(
    protected ResellerCommissionService $commissionService,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.reseller_commission'),
    );
  }

  /**
   * Renderiza el dashboard del portal de partner.
   *
   * Identifica al reseller por el email del usuario actual y muestra
   * sus tenants gestionados, comisiones y metricas de cartera.
   *
   * @return array
   *   Render array del portal de partner.
   */
  public function dashboard(): array {
    $currentUser = $this->currentUser();
    $reseller = $this->commissionService->getResellerByUser((int) $currentUser->id());

    // Si no se encuentra reseller para este usuario, mostrar mensaje.
    if (!$reseller) {
      return [
        '#markup' => '<div class="reseller-portal__access-denied"><h2>' . $this->t('Acceso no disponible') . '</h2><p>' . $this->t('No se encontro un perfil de reseller asociado a tu cuenta. Contacta con el administrador de la plataforma.') . '</p></div>',
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }

    $resellerId = (int) $reseller->id();

    // Obtener tenants gestionados.
    $managedTenants = $this->getManagedTenantsData($reseller);

    // Calcular comisiones.
    $commissions = $this->commissionService->calculateCommissions($resellerId);

    // Calcular metricas de salud.
    $metrics = $this->getHealthMetrics($managedTenants);

    return [
      '#theme' => 'reseller_portal',
      '#reseller' => $reseller,
      '#managed_tenants' => $managedTenants,
      '#commissions' => $commissions,
      '#metrics' => $metrics,
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/reseller-portal',
        ],
      ],
      '#cache' => [
        'max-age' => 60,
        'contexts' => ['user'],
      ],
    ];
  }

  /**
   * Obtiene datos de los tenants gestionados por el reseller.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\Reseller $reseller
   *   La entidad reseller.
   *
   * @return array
   *   Array de datos de tenant: name, plan, status, created.
   */
  protected function getManagedTenantsData($reseller): array {
    $tenants = [];
    $tenantRefs = $reseller->get('managed_tenant_ids')->referencedEntities();

    foreach ($tenantRefs as $group) {
      $tenantData = [
        'name' => $group->label(),
        'plan' => '',
        'status' => 'active',
        'created' => '',
      ];

      // Intentar obtener datos del tenant entity si existe.
      try {
        $tenantStorage = $this->entityTypeManager()->getStorage('tenant');
        $tenantEntities = $tenantStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('group_id', $group->id())
          ->range(0, 1)
          ->execute();

        if (!empty($tenantEntities)) {
          $tenantEntity = $tenantStorage->load(reset($tenantEntities));
          if ($tenantEntity) {
            $tenantData['status'] = $tenantEntity->get('subscription_status')->value ?? 'active';
            $tenantData['created'] = $tenantEntity->get('created')->value
              ? \Drupal::service('date.formatter')->format((int) $tenantEntity->get('created')->value, 'short')
              : '';

            // Obtener nombre del plan.
            $plan = $tenantEntity->get('plan_id')->entity ?? NULL;
            $tenantData['plan'] = $plan ? $plan->label() : $this->t('Sin plan');
          }
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('ecosistema_jaraba_core')->warning(
          'Error obteniendo datos de tenant para reseller portal: @error',
          ['@error' => $e->getMessage()],
        );
      }

      $tenants[] = $tenantData;
    }

    return $tenants;
  }

  /**
   * Calcula metricas de salud de la cartera del reseller.
   *
   * @param array $managedTenants
   *   Datos de los tenants gestionados.
   *
   * @return array
   *   Metricas: active_tenants, churned_tenants, avg_health_score.
   */
  protected function getHealthMetrics(array $managedTenants): array {
    $activeTenants = 0;
    $churnedTenants = 0;

    foreach ($managedTenants as $tenant) {
      $status = $tenant['status'] ?? '';
      if (in_array($status, ['active', 'trial'])) {
        $activeTenants++;
      }
      elseif (in_array($status, ['cancelled', 'churned', 'suspended'])) {
        $churnedTenants++;
      }
    }

    $totalTenants = count($managedTenants);
    $avgHealthScore = $totalTenants > 0
      ? round(($activeTenants / $totalTenants) * 100)
      : 0;

    return [
      'active_tenants' => $activeTenants,
      'churned_tenants' => $churnedTenants,
      'avg_health_score' => $avgHealthScore,
    ];
  }

}
