<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\Reseller;
use Psr\Log\LoggerInterface;

/**
 * Servicio de calculo de comisiones para resellers.
 *
 * PROPOSITO:
 * Centraliza la logica de calculo de comisiones de los resellers
 * basandose en los ingresos de los tenants que gestionan.
 *
 * LOGICA:
 * - calculateCommissions(): Calcula un resumen de comisiones para un reseller
 * - getCommissionReport(): Genera un informe detallado linea por linea
 * - getResellerByUser(): Encuentra el reseller asociado a un usuario por email
 *
 * DEPENDENCIAS:
 * - entity_type.manager: Para acceder a resellers y tenants
 * - database: Para consultas directas de facturacion
 * - logger: Para registrar errores
 */
class ResellerCommissionService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\Core\Database\Connection $database
   *   La conexion a la base de datos.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Calcula comisiones para un reseller dado.
   *
   * Consulta los datos de facturacion de los tenants gestionados,
   * aplica la tasa de comision y devuelve un resumen.
   *
   * @param int $resellerId
   *   ID de la entidad reseller.
   * @param string|null $period
   *   Periodo de calculo (formato Y-m). NULL para el mes actual.
   *
   * @return array
   *   Resumen de comisiones con claves:
   *   - total_tenants: numero de tenants gestionados
   *   - total_revenue: ingresos totales de los tenants
   *   - commission_earned: comision calculada
   *   - pending_payout: pago pendiente de liquidar
   */
  public function calculateCommissions(int $resellerId, ?string $period = NULL): array {
    $summary = [
      'total_tenants' => 0,
      'total_revenue' => 0.0,
      'commission_earned' => 0.0,
      'pending_payout' => 0.0,
    ];

    try {
      $reseller = $this->entityTypeManager->getStorage('reseller')->load($resellerId);
      if (!$reseller instanceof Reseller) {
        return $summary;
      }

      $commissionRate = (float) ($reseller->get('commission_rate')->value ?? 0);
      $tenantRefs = $reseller->get('managed_tenant_ids')->referencedEntities();
      $summary['total_tenants'] = count($tenantRefs);

      if (empty($tenantRefs)) {
        return $summary;
      }

      // Obtener IDs de los grupos gestionados.
      $groupIds = [];
      foreach ($tenantRefs as $group) {
        $groupIds[] = $group->id();
      }

      // Consultar tenants asociados a esos grupos.
      $tenantStorage = $this->entityTypeManager->getStorage('tenant');
      $tenantIds = $tenantStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('group_id', $groupIds, 'IN')
        ->execute();

      if (empty($tenantIds)) {
        return $summary;
      }

      // Calcular ingresos de los tenants (a partir de datos de suscripcion).
      $tenants = $tenantStorage->loadMultiple($tenantIds);
      $totalRevenue = 0.0;

      foreach ($tenants as $tenant) {
        // Obtener el plan del tenant para estimar ingresos mensuales.
        $plan = $tenant->get('plan_id')->entity ?? NULL;
        if ($plan) {
          $monthlyPrice = (float) ($plan->get('monthly_price')->value ?? 0);
          $totalRevenue += $monthlyPrice;
        }
      }

      $summary['total_revenue'] = round($totalRevenue, 2);
      $summary['commission_earned'] = round($totalRevenue * ($commissionRate / 100), 2);
      $summary['pending_payout'] = $summary['commission_earned'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error calculando comisiones para reseller @id: @error', [
        '@id' => $resellerId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $summary;
  }

  /**
   * Genera un informe detallado de comisiones por tenant.
   *
   * @param int $resellerId
   *   ID de la entidad reseller.
   *
   * @return array
   *   Array de lineas de comision, cada una con:
   *   - tenant_name: nombre del tenant
   *   - plan: nombre del plan
   *   - monthly_revenue: ingreso mensual
   *   - commission: comision calculada
   *   - status: estado del tenant
   */
  public function getCommissionReport(int $resellerId): array {
    $report = [];

    try {
      $reseller = $this->entityTypeManager->getStorage('reseller')->load($resellerId);
      if (!$reseller instanceof Reseller) {
        return $report;
      }

      $commissionRate = (float) ($reseller->get('commission_rate')->value ?? 0);
      $tenantRefs = $reseller->get('managed_tenant_ids')->referencedEntities();

      foreach ($tenantRefs as $group) {
        $lineItem = [
          'tenant_name' => $group->label(),
          'plan' => '-',
          'monthly_revenue' => 0.0,
          'commission' => 0.0,
          'status' => 'unknown',
        ];

        // Buscar tenant entity asociado al grupo.
        $tenantStorage = $this->entityTypeManager->getStorage('tenant');
        $tenantIds = $tenantStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('group_id', $group->id())
          ->range(0, 1)
          ->execute();

        if (!empty($tenantIds)) {
          $tenant = $tenantStorage->load(reset($tenantIds));
          if ($tenant) {
            $lineItem['status'] = $tenant->get('subscription_status')->value ?? 'unknown';

            $plan = $tenant->get('plan_id')->entity ?? NULL;
            if ($plan) {
              $monthlyPrice = (float) ($plan->get('monthly_price')->value ?? 0);
              $lineItem['plan'] = $plan->label();
              $lineItem['monthly_revenue'] = round($monthlyPrice, 2);
              $lineItem['commission'] = round($monthlyPrice * ($commissionRate / 100), 2);
            }
          }
        }

        $report[] = $lineItem;
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error generando reporte de comisiones para reseller @id: @error', [
        '@id' => $resellerId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $report;
  }

  /**
   * Encuentra el reseller asociado a un usuario por email.
   *
   * Busca en la tabla de resellers una entidad cuyo contact_email
   * coincida con el email del usuario proporcionado.
   *
   * @param int $userId
   *   ID del usuario de Drupal.
   *
   * @return \Drupal\ecosistema_jaraba_core\Entity\Reseller|null
   *   La entidad Reseller si se encuentra, NULL en caso contrario.
   */
  public function getResellerByUser(int $userId): ?Reseller {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $user = $userStorage->load($userId);
      if (!$user) {
        return NULL;
      }

      $email = $user->getEmail();
      if (empty($email)) {
        return NULL;
      }

      $resellerStorage = $this->entityTypeManager->getStorage('reseller');
      $ids = $resellerStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('contact_email', $email)
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return NULL;
      }

      $reseller = $resellerStorage->load(reset($ids));
      return $reseller instanceof Reseller ? $reseller : NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error buscando reseller para usuario @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
