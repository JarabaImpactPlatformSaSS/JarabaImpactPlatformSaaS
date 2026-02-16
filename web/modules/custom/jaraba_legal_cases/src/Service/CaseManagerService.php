<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de expedientes juridicos.
 *
 * ESTRUCTURA:
 * Servicio principal que orquesta el CRUD de expedientes ClientCase,
 * listados filtrados por estado/tenant/asignacion y logica de negocio
 * para cambios de estado y asignaciones.
 *
 * LOGICA:
 * Proporciona metodos para obtener expedientes activos, filtrar por
 * estado/prioridad, obtener estadisticas de KPIs del dashboard y
 * gestionar transiciones de estado con validacion.
 *
 * RELACIONES:
 * - CaseManagerService -> EntityTypeManagerInterface: carga entidades ClientCase.
 * - CaseManagerService -> AccountProxyInterface: usuario actual para filtros.
 * - CaseManagerService -> LoggerInterface: registro de operaciones.
 * - CaseManagerService <- CasesDashboardController: invocado desde dashboard.
 * - CaseManagerService <- CasesApiController: invocado desde API REST.
 * - CaseManagerService <- InquiryManagerService: invocado en conversion consulta->expediente.
 */
class CaseManagerService {

  /**
   * Construye una nueva instancia de CaseManagerService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene los expedientes activos del usuario actual o todos si es admin.
   *
   * @param int $limit
   *   Numero maximo de expedientes a devolver.
   * @param int $offset
   *   Desplazamiento para paginacion.
   *
   * @return array
   *   Array con 'cases' (entidades) y 'total' (conteo).
   */
  public function getActiveCases(int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('client_case');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', ['active', 'on_hold'], 'IN')
        ->sort('created', 'DESC')
        ->range($offset, $limit);

      $count_query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', ['active', 'on_hold'], 'IN')
        ->count();

      $ids = $query->execute();
      $total = (int) $count_query->execute();

      return [
        'cases' => !empty($ids) ? $storage->loadMultiple($ids) : [],
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('CaseManager: Error obteniendo expedientes activos: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['cases' => [], 'total' => 0];
    }
  }

  /**
   * Obtiene un expediente por UUID.
   *
   * @param string $uuid
   *   UUID del expediente.
   *
   * @return \Drupal\jaraba_legal_cases\Entity\ClientCase|null
   *   El expediente o NULL si no existe.
   */
  public function getCaseByUuid(string $uuid): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('client_case');
      $entities = $storage->loadByProperties(['uuid' => $uuid]);
      return !empty($entities) ? reset($entities) : NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('CaseManager: Error cargando expediente UUID @uuid: @msg', [
        '@uuid' => $uuid,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene las estadisticas de expedientes para el dashboard.
   *
   * @return array
   *   KPIs: total_active, total_on_hold, total_completed, total_this_month.
   */
  public function getDashboardStats(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('client_case');

      $active = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'active')
        ->count()
        ->execute();

      $on_hold = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'on_hold')
        ->count()
        ->execute();

      $completed = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'completed')
        ->count()
        ->execute();

      $month_start = strtotime('first day of this month midnight');
      $this_month = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('created', $month_start, '>=')
        ->count()
        ->execute();

      return [
        'total_active' => $active,
        'total_on_hold' => $on_hold,
        'total_completed' => $completed,
        'total_this_month' => $this_month,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('CaseManager: Error obteniendo stats: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [
        'total_active' => 0,
        'total_on_hold' => 0,
        'total_completed' => 0,
        'total_this_month' => 0,
      ];
    }
  }

  /**
   * Obtiene los expedientes con filtros para la API.
   *
   * @param array $filters
   *   Filtros: status, priority, case_type, search.
   * @param int $limit
   *   Numero maximo de resultados.
   * @param int $offset
   *   Desplazamiento.
   *
   * @return array
   *   Array con 'cases' y 'total'.
   */
  public function getCasesFiltered(array $filters, int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('client_case');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
        ->range($offset, $limit);

      $count_query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->count();

      if (!empty($filters['status'])) {
        $query->condition('status', $filters['status']);
        $count_query->condition('status', $filters['status']);
      }
      if (!empty($filters['priority'])) {
        $query->condition('priority', $filters['priority']);
        $count_query->condition('priority', $filters['priority']);
      }
      if (!empty($filters['case_type'])) {
        $query->condition('case_type', $filters['case_type']);
        $count_query->condition('case_type', $filters['case_type']);
      }

      $ids = $query->execute();
      $total = (int) $count_query->execute();

      return [
        'cases' => !empty($ids) ? $storage->loadMultiple($ids) : [],
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('CaseManager: Error filtrando expedientes: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['cases' => [], 'total' => 0];
    }
  }

}
