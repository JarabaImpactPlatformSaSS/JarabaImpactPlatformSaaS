<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de tracking de exposiciones para experimentos A/B.
 *
 * PROPOSITO:
 * Gestiona el registro de exposiciones de visitantes a variantes de
 * experimentos y el tracking de conversiones asociadas.
 *
 * LOGICA:
 * - recordExposure(): crea una entidad ExperimentExposure con los datos
 *   del visitante, variante y contexto (dispositivo, browser, UTM, etc.).
 * - recordConversion(): marca una exposicion existente como convertida
 *   actualizando los campos converted y conversion_value.
 * - getExposuresForExperiment(): obtiene todas las exposiciones de un
 *   experimento para analisis posterior.
 *
 * RELACIONES:
 * - Consume ExperimentExposure entity via EntityTypeManager.
 * - Consumido por controladores API y el servicio de calculo de resultados.
 */
class ExposureTrackingService {

  /**
   * Gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Canal de log.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor del servicio de tracking de exposiciones.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para A/B testing.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Registra una exposicion de un visitante a una variante.
   *
   * Crea una entidad ExperimentExposure con los datos proporcionados.
   * El array de contexto puede contener: user_id, device_type, browser,
   * country, utm_source, utm_campaign, tenant_id.
   *
   * @param int $experimentId
   *   ID del experimento A/B.
   * @param string $variantId
   *   Clave de la variante asignada.
   * @param string $visitorId
   *   Identificador unico del visitante.
   * @param array $context
   *   Datos de contexto opcionales (user_id, device_type, browser, etc.).
   *
   * @return array
   *   Array con los datos de la exposicion creada, incluyendo 'id',
   *   'experiment_id', 'variant_id', 'visitor_id' y 'exposed_at'.
   *   Array vacio si hubo un error.
   */
  public function recordExposure(int $experimentId, string $variantId, string $visitorId, array $context = []): array {
    try {
      $storage = $this->entityTypeManager->getStorage('experiment_exposure');

      $values = [
        'experiment_id' => $experimentId,
        'variant_id' => $variantId,
        'visitor_id' => $visitorId,
        'exposed_at' => \Drupal::time()->getRequestTime(),
        'converted' => FALSE,
      ];

      // Agregar campos de contexto opcionales.
      $optional_fields = [
        'user_id',
        'device_type',
        'browser',
        'country',
        'utm_source',
        'utm_campaign',
        'tenant_id',
      ];

      foreach ($optional_fields as $field) {
        if (!empty($context[$field])) {
          $values[$field] = $context[$field];
        }
      }

      $entity = $storage->create($values);
      $entity->save();

      $this->logger->info('Exposicion registrada: visitante @visitor, experimento @experiment, variante @variant.', [
        '@visitor' => $visitorId,
        '@experiment' => $experimentId,
        '@variant' => $variantId,
      ]);

      return [
        'id' => (int) $entity->id(),
        'experiment_id' => $experimentId,
        'variant_id' => $variantId,
        'visitor_id' => $visitorId,
        'exposed_at' => $values['exposed_at'],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando exposicion para visitante @visitor: @error', [
        '@visitor' => $visitorId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Registra una conversion para un visitante en un experimento.
   *
   * Busca la exposicion mas reciente del visitante en el experimento
   * y marca los campos converted y conversion_value.
   *
   * @param string $visitorId
   *   Identificador unico del visitante.
   * @param int $experimentId
   *   ID del experimento A/B.
   * @param float $value
   *   Valor numerico o monetario de la conversion.
   *
   * @return bool
   *   TRUE si la conversion se registro correctamente, FALSE en caso contrario.
   */
  public function recordConversion(string $visitorId, int $experimentId, float $value = 0): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('experiment_exposure');

      // Buscar la exposicion mas reciente del visitante en el experimento.
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('visitor_id', $visitorId)
        ->condition('experiment_id', $experimentId)
        ->condition('converted', FALSE)
        ->sort('exposed_at', 'DESC')
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        $this->logger->warning('No se encontro exposicion sin convertir para visitante @visitor en experimento @experiment.', [
          '@visitor' => $visitorId,
          '@experiment' => $experimentId,
        ]);
        return FALSE;
      }

      $id = reset($ids);
      $entity = $storage->load($id);

      if (!$entity) {
        return FALSE;
      }

      $entity->set('converted', TRUE);
      if ($value > 0) {
        $entity->set('conversion_value', $value);
      }
      $entity->save();

      $this->logger->info('Conversion registrada: visitante @visitor, experimento @experiment, valor @value.', [
        '@visitor' => $visitorId,
        '@experiment' => $experimentId,
        '@value' => $value,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando conversion para visitante @visitor: @error', [
        '@visitor' => $visitorId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene todas las exposiciones de un experimento.
   *
   * Carga todas las entidades ExperimentExposure asociadas a un
   * experimento especifico, ordenadas por fecha de exposicion.
   *
   * @param int $experimentId
   *   ID del experimento A/B.
   *
   * @return array
   *   Array de entidades ExperimentExposure. Array vacio si no hay
   *   exposiciones o si ocurre un error.
   */
  public function getExposuresForExperiment(int $experimentId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('experiment_exposure');

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('experiment_id', $experimentId)
        ->sort('exposed_at', 'DESC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      return array_values($storage->loadMultiple($ids));
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo exposiciones del experimento @experiment: @error', [
        '@experiment' => $experimentId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
