<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_legal_cases\Entity\ClientCase;
use Psr\Log\LoggerInterface;

/**
 * Servicio de registro de actividades en expedientes.
 *
 * ESTRUCTURA:
 * Servicio append-only que crea entidades CaseActivity vinculadas
 * a expedientes. Cada actividad registra un tipo de evento, descripcion,
 * actor y metadatos opcionales.
 *
 * LOGICA:
 * Las actividades se crean de forma inmutable — una vez registradas,
 * no se modifican ni eliminan. El metodo log() es el punto de entrada
 * principal invocado desde hooks y servicios.
 */
class ActivityLoggerService {

  /**
   * Construye una nueva instancia de ActivityLoggerService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Registra una actividad en un expediente.
   *
   * @param \Drupal\jaraba_legal_cases\Entity\ClientCase $case
   *   El expediente al que asociar la actividad.
   * @param string $activity_type
   *   Tipo de actividad (note, status_change, case_created, etc.).
   * @param string $description
   *   Descripcion de la actividad.
   * @param array $metadata
   *   Metadatos adicionales opcionales.
   * @param bool $client_visible
   *   Si la actividad es visible para el cliente.
   */
  public function log(ClientCase $case, string $activity_type, string $description = '', array $metadata = [], bool $client_visible = FALSE): void {
    try {
      $storage = $this->entityTypeManager->getStorage('case_activity');
      $activity = $storage->create([
        'case_id' => $case->id(),
        'activity_type' => $activity_type,
        'description' => $description,
        'actor_uid' => $this->currentUser->id(),
        'metadata' => $metadata,
        'is_client_visible' => $client_visible,
        'uid' => $this->currentUser->id(),
      ]);
      $activity->save();

      $this->logger->info('ActivityLogger: Actividad @type registrada en expediente @id', [
        '@type' => $activity_type,
        '@id' => $case->id(),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('ActivityLogger: Error registrando actividad en expediente @id: @msg', [
        '@id' => $case->id(),
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Obtiene las actividades de un expediente.
   *
   * @param int $case_id
   *   ID del expediente.
   * @param int $limit
   *   Numero maximo de actividades.
   *
   * @return array
   *   Array de entidades CaseActivity.
   */
  public function getActivities(int $case_id, int $limit = 50): array {
    try {
      $storage = $this->entityTypeManager->getStorage('case_activity');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('case_id', $case_id)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      return !empty($ids) ? $storage->loadMultiple($ids) : [];
    }
    catch (\Exception $e) {
      $this->logger->error('ActivityLogger: Error obteniendo actividades del expediente @id: @msg', [
        '@id' => $case_id,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
