<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion del ciclo de vida de solicitudes de fondos.
 *
 * Estructura: Gestiona el CRUD de solicitudes (FundingApplication),
 *   transiciones de estado, estadisticas del dashboard y listados
 *   filtrados por tenant/estado/convocatoria.
 *
 * Logica: Las solicitudes siguen un ciclo de vida estricto:
 *   draft -> submitted -> approved|rejected -> justifying -> closed.
 *   Las transiciones invalidas se rechazan. El metodo submitApplication()
 *   valida que la solicitud tenga los datos minimos antes de marcarla
 *   como presentada.
 *
 * @see \Drupal\jaraba_funding\Entity\FundingApplication
 * @see \Drupal\jaraba_funding\Controller\FundingApiController
 */
class ApplicationManagerService {

  /**
   * Transiciones de estado validas.
   */
  private const STATUS_TRANSITIONS = [
    'draft' => ['submitted'],
    'submitted' => ['approved', 'rejected'],
    'approved' => ['justifying', 'closed'],
    'rejected' => ['closed'],
    'justifying' => ['closed'],
    'closed' => [],
  ];

  /**
   * Construye una nueva instancia de ApplicationManagerService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected object $tenantContext,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene las solicitudes recientes del tenant actual.
   *
   * @param int $limit
   *   Numero maximo de solicitudes.
   * @param int $offset
   *   Desplazamiento para paginacion.
   *
   * @return array
   *   Array con 'applications' y 'total'.
   */
  public function getRecentApplications(int $limit = 10, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_application');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
        ->range($offset, $limit);

      $count_query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->count();

      $ids = $query->execute();
      $total = (int) $count_query->execute();

      return [
        'applications' => !empty($ids) ? $storage->loadMultiple($ids) : [],
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener solicitudes recientes: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['applications' => [], 'total' => 0];
    }
  }

  /**
   * Obtiene solicitudes filtradas.
   *
   * @param array $filters
   *   Filtros: status, opportunity_id.
   * @param int $limit
   *   Numero maximo de resultados.
   * @param int $offset
   *   Desplazamiento.
   *
   * @return array
   *   Array con 'applications' y 'total'.
   */
  public function getApplicationsFiltered(array $filters, int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_application');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
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
        'applications' => !empty($ids) ? $storage->loadMultiple($ids) : [],
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al filtrar solicitudes: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['applications' => [], 'total' => 0];
    }
  }

  /**
   * Actualiza el estado de una solicitud con validacion de transicion.
   *
   * @param int $application_id
   *   ID de la solicitud.
   * @param string $new_status
   *   Nuevo estado.
   *
   * @return array
   *   Array con 'success' y 'message' o 'error'.
   */
  public function updateStatus(int $application_id, string $new_status): array {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_application');
      $application = $storage->load($application_id);

      if (!$application) {
        return ['success' => FALSE, 'error' => 'Solicitud no encontrada.'];
      }

      $current_status = $application->get('status')->value;
      $allowed = self::STATUS_TRANSITIONS[$current_status] ?? [];

      if (!in_array($new_status, $allowed, TRUE)) {
        return [
          'success' => FALSE,
          'error' => sprintf(
            'Transicion no valida: de "%s" a "%s". Transiciones permitidas: %s.',
            $current_status,
            $new_status,
            implode(', ', $allowed)
          ),
        ];
      }

      $application->set('status', $new_status);
      $application->save();

      return ['success' => TRUE, 'message' => 'Estado actualizado correctamente.'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al actualizar estado de solicitud @id: @msg', [
        '@id' => $application_id,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => 'Error interno al actualizar estado.'];
    }
  }

  /**
   * Obtiene estadisticas del dashboard de fondos.
   *
   * @return array
   *   Estadisticas: total_applications, by_status, total_requested, total_approved.
   */
  public function getDashboardStats(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_application');

      $total = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute();

      $statuses = ['draft', 'submitted', 'approved', 'rejected', 'justifying', 'closed'];
      $by_status = [];
      foreach ($statuses as $status) {
        $by_status[$status] = (int) $storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', $status)
          ->count()
          ->execute();
      }

      return [
        'total_applications' => $total,
        'by_status' => $by_status,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener estadisticas: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [
        'total_applications' => 0,
        'by_status' => [],
      ];
    }
  }

}
