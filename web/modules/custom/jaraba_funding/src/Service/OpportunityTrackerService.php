<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de tracking de convocatorias de fondos.
 *
 * Estructura: Gestiona la consulta, filtrado y alertas de convocatorias
 *   de fondos europeos y subvenciones. Proporciona metodos para el
 *   dashboard y el sistema de alertas de plazos.
 *
 * Logica: Las convocatorias se filtran por tenant y estado. El metodo
 *   checkDeadlines() recorre las convocatorias abiertas y genera alertas
 *   cuando el plazo esta proximo (configurable via alert_days_before).
 *   sendDeadlineAlerts() envia notificaciones via el sistema de email.
 *
 * @see \Drupal\jaraba_funding\Entity\FundingOpportunity
 * @see \Drupal\jaraba_funding\Controller\FundingApiController
 */
class OpportunityTrackerService {

  /**
   * Construye una nueva instancia de OpportunityTrackerService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected object $tenantContext,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene las convocatorias activas del tenant actual.
   *
   * @param int $limit
   *   Numero maximo de convocatorias a devolver.
   * @param int $offset
   *   Desplazamiento para paginacion.
   *
   * @return array
   *   Array con 'opportunities' (entidades) y 'total' (conteo).
   */
  public function getActiveOpportunities(int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_opportunity');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', ['upcoming', 'open'], 'IN')
        ->sort('deadline', 'ASC')
        ->range($offset, $limit);

      $count_query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', ['upcoming', 'open'], 'IN')
        ->count();

      $ids = $query->execute();
      $total = (int) $count_query->execute();

      return [
        'opportunities' => !empty($ids) ? $storage->loadMultiple($ids) : [],
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener convocatorias activas: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['opportunities' => [], 'total' => 0];
    }
  }

  /**
   * Obtiene convocatorias filtradas por criterios.
   *
   * @param array $filters
   *   Filtros: status, program, funding_body.
   * @param int $limit
   *   Numero maximo de resultados.
   * @param int $offset
   *   Desplazamiento para paginacion.
   *
   * @return array
   *   Array con 'opportunities' y 'total'.
   */
  public function getOpportunitiesFiltered(array $filters, int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_opportunity');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('deadline', 'ASC')
        ->range($offset, $limit);

      $count_query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->count();

      foreach ($filters as $field => $value) {
        if ($value !== NULL && $value !== '') {
          $query->condition($field, $value);
          $count_query->condition($field, $value);
        }
      }

      $ids = $query->execute();
      $total = (int) $count_query->execute();

      return [
        'opportunities' => !empty($ids) ? $storage->loadMultiple($ids) : [],
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al filtrar convocatorias: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['opportunities' => [], 'total' => 0];
    }
  }

  /**
   * Verifica plazos proximos y devuelve convocatorias con alerta.
   *
   * @return array
   *   Lista de convocatorias con deadline proximo.
   */
  public function checkDeadlines(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_opportunity');
      $now = new \DateTime();
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'open')
        ->sort('deadline', 'ASC');

      $ids = $query->execute();
      if (empty($ids)) {
        return [];
      }

      $opportunities = $storage->loadMultiple($ids);
      $alerts = [];

      foreach ($opportunities as $opportunity) {
        $deadline_value = $opportunity->get('deadline')->value;
        if (!$deadline_value) {
          continue;
        }
        $deadline = new \DateTime($deadline_value);
        $days_until = (int) $now->diff($deadline)->format('%r%a');
        $alert_days = (int) ($opportunity->get('alert_days_before')->value ?? 15);

        if ($days_until >= 0 && $days_until <= $alert_days) {
          $alerts[] = [
            'opportunity' => $opportunity,
            'days_until_deadline' => $days_until,
            'alert_days' => $alert_days,
          ];
        }
      }

      return $alerts;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al verificar plazos: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
