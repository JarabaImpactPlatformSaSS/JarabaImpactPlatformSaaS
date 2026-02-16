<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de consultas juridicas pre-expediente.
 *
 * ESTRUCTURA:
 * Servicio que orquesta el ciclo de vida de consultas: recepcion,
 * triaje, asignacion y conversion a expedientes ClientCase.
 *
 * LOGICA:
 * Gestiona la transicion de consultas a traves de sus estados
 * (pending -> triaged -> assigned -> converted/rejected). La conversion
 * a expediente crea un nuevo ClientCase y registra la actividad.
 *
 * RELACIONES:
 * - InquiryManagerService -> EntityTypeManagerInterface: carga entidades.
 * - InquiryManagerService -> CaseManagerService: crea expedientes.
 * - InquiryManagerService -> ActivityLoggerService: registra actividades.
 * - InquiryManagerService <- CasesApiController: invocado desde API.
 */
class InquiryManagerService {

  /**
   * Construye una nueva instancia de InquiryManagerService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected CaseManagerService $caseManager,
    protected ActivityLoggerService $activityLogger,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Convierte una consulta en un expediente.
   *
   * @param int $inquiry_id
   *   ID de la consulta a convertir.
   *
   * @return array
   *   Resultado con success, case_id, case_number.
   */
  public function convertToCase(int $inquiry_id): array {
    try {
      $inquiry_storage = $this->entityTypeManager->getStorage('client_inquiry');
      $inquiry = $inquiry_storage->load($inquiry_id);

      if (!$inquiry) {
        return ['success' => FALSE, 'error' => 'Consulta no encontrada.'];
      }

      if ($inquiry->get('status')->value === 'converted') {
        return [
          'success' => FALSE,
          'error' => 'La consulta ya fue convertida a expediente.',
        ];
      }

      // Crear el expediente a partir de la consulta.
      $case_storage = $this->entityTypeManager->getStorage('client_case');
      $case = $case_storage->create([
        'title' => $inquiry->get('subject')->value ?? 'Expediente desde consulta',
        'status' => 'active',
        'priority' => $inquiry->get('priority')->value ?? 'medium',
        'client_name' => $inquiry->get('client_name')->value ?? '',
        'client_email' => $inquiry->get('client_email')->value ?? '',
        'client_phone' => $inquiry->get('client_phone')->value ?? '',
        'description' => $inquiry->get('description')->value ?? '',
        'tenant_id' => $inquiry->get('tenant_id')->target_id,
        'assigned_to' => $inquiry->get('assigned_to')->target_id,
        'uid' => $this->currentUser->id(),
      ]);
      $case->save();

      // Actualizar la consulta.
      $inquiry->set('status', 'converted');
      $inquiry->set('converted_to_case_id', $case->id());
      $inquiry->save();

      $this->logger->info('InquiryManager: Consulta @iid convertida a expediente @cid', [
        '@iid' => $inquiry_id,
        '@cid' => $case->id(),
      ]);

      return [
        'success' => TRUE,
        'case_id' => (int) $case->id(),
        'case_number' => $case->get('case_number')->value,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('InquiryManager: Error convirtiendo consulta @id: @msg', [
        '@id' => $inquiry_id,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => 'Error interno al convertir la consulta.'];
    }
  }

  /**
   * Asigna un abogado a una consulta.
   *
   * @param int $inquiry_id
   *   ID de la consulta.
   * @param int $user_id
   *   ID del usuario a asignar.
   *
   * @return array
   *   Resultado con success y datos actualizados.
   */
  public function assignInquiry(int $inquiry_id, int $user_id): array {
    try {
      $inquiry_storage = $this->entityTypeManager->getStorage('client_inquiry');
      $inquiry = $inquiry_storage->load($inquiry_id);

      if (!$inquiry) {
        return ['success' => FALSE, 'error' => 'Consulta no encontrada.'];
      }

      $inquiry->set('assigned_to', $user_id);
      $inquiry->set('status', 'assigned');
      $inquiry->save();

      $this->logger->info('InquiryManager: Consulta @id asignada a usuario @uid', [
        '@id' => $inquiry_id,
        '@uid' => $user_id,
      ]);

      return ['success' => TRUE, 'inquiry_id' => $inquiry_id, 'assigned_to' => $user_id];
    }
    catch (\Exception $e) {
      $this->logger->error('InquiryManager: Error asignando consulta @id: @msg', [
        '@id' => $inquiry_id,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => 'Error interno al asignar la consulta.'];
    }
  }

  /**
   * Obtiene consultas con filtros para la API.
   *
   * @param array $filters
   *   Filtros opcionales: status, source.
   * @param int $limit
   *   Numero maximo.
   * @param int $offset
   *   Desplazamiento.
   *
   * @return array
   *   Array con 'inquiries' y 'total'.
   */
  public function getInquiriesFiltered(array $filters, int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('client_inquiry');
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
      if (!empty($filters['source'])) {
        $query->condition('source', $filters['source']);
        $count_query->condition('source', $filters['source']);
      }

      $ids = $query->execute();
      $total = (int) $count_query->execute();

      return [
        'inquiries' => !empty($ids) ? $storage->loadMultiple($ids) : [],
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('InquiryManager: Error filtrando consultas: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['inquiries' => [], 'total' => 0];
    }
  }

}
