<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de retención de datos y anonimización GDPR.
 *
 * Gestiona la eliminación automática de registros antiguos conforme
 * a las políticas de retención configuradas, y la anonimización
 * de datos personales de usuarios dados de baja.
 */
class DataRetentionService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\Core\Database\Connection $database
   *   La conexión a base de datos.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   La factoría de configuración.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del módulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Aplica las políticas de retención de datos.
   *
   * Elimina registros de auditoría y evaluaciones que exceden
   * el periodo de retención configurado.
   *
   * @return int
   *   Número total de registros eliminados.
   */
  public function applyRetentionPolicies(): int {
    $totalDeleted = 0;
    $config = $this->getRetentionConfig();

    try {
      // Eliminar audit logs antiguos.
      $auditLogDays = $config['audit_log_days'];
      $auditCutoff = time() - ($auditLogDays * 86400);
      $totalDeleted += $this->deleteOldEntities('security_audit_log', $auditCutoff);

      // Eliminar evaluaciones antiguas.
      $assessmentDays = $config['compliance_assessment_days'];
      $assessmentCutoff = time() - ($assessmentDays * 86400);
      $totalDeleted += $this->deleteOldEntities('compliance_assessment_v2', $assessmentCutoff);

      $this->logger->info('Data retention policies applied: @count records deleted.', [
        '@count' => $totalDeleted,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to apply retention policies: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $totalDeleted;
  }

  /**
   * Obtiene la configuración de retención de datos.
   *
   * @return array
   *   Array con:
   *   - audit_log_days (int): Días de retención para audit logs.
   *   - compliance_assessment_days (int): Días de retención para assessments.
   *   - anonymize_after_days (int): Días para anonimizar datos de usuario.
   */
  public function getRetentionConfig(): array {
    $config = $this->configFactory->get('jaraba_security_compliance.settings');

    return [
      'audit_log_days' => (int) ($config->get('audit_log_retention_days') ?? 365),
      'compliance_assessment_days' => (int) ($config->get('assessment_retention_days') ?? 730),
      'anonymize_after_days' => (int) ($config->get('anonymize_after_days') ?? 90),
    ];
  }

  /**
   * Anonimiza los datos de un usuario en los registros de auditoría.
   *
   * Reemplaza la referencia al usuario con NULL y limpia datos
   * personales en el campo details de los audit logs.
   *
   * @param int $userId
   *   El ID del usuario a anonimizar.
   *
   * @return bool
   *   TRUE si la anonimización se completó con éxito.
   */
  public function anonymizeUser(int $userId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('security_audit_log');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('actor_id', $userId);
      $ids = $query->execute();

      if (empty($ids)) {
        return TRUE;
      }

      $entities = $storage->loadMultiple($ids);
      $anonymizedCount = 0;

      foreach ($entities as $entity) {
        // Anonimizar actor_id estableciendo a NULL.
        $entity->set('actor_id', NULL);

        // Limpiar IP.
        $entity->set('ip_address', '0.0.0.0');

        // Limpiar datos personales del campo details.
        $details = $entity->get('details')->value;
        if (!empty($details)) {
          $decoded = json_decode($details, TRUE);
          if (is_array($decoded)) {
            // Eliminar campos que podrían contener datos personales.
            $personalFields = ['email', 'name', 'username', 'phone', 'ip', 'user_agent'];
            foreach ($personalFields as $field) {
              unset($decoded[$field]);
            }
            $entity->set('details', json_encode($decoded, JSON_THROW_ON_ERROR));
          }
        }

        $entity->save();
        $anonymizedCount++;
      }

      $this->logger->info('Anonymized @count audit log records for user @uid.', [
        '@count' => $anonymizedCount,
        '@uid' => $userId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to anonymize user @uid: @message', [
        '@uid' => $userId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Elimina entidades creadas antes de un timestamp.
   *
   * @param string $entityTypeId
   *   El tipo de entidad.
   * @param int $cutoffTimestamp
   *   El timestamp de corte.
   *
   * @return int
   *   Número de entidades eliminadas.
   */
  protected function deleteOldEntities(string $entityTypeId, int $cutoffTimestamp): int {
    try {
      $storage = $this->entityTypeManager->getStorage($entityTypeId);
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $cutoffTimestamp, '<');
      $ids = $query->execute();

      if (empty($ids)) {
        return 0;
      }

      // Delete in batches of 100.
      $chunks = array_chunk($ids, 100);
      $deleted = 0;

      foreach ($chunks as $chunk) {
        $entities = $storage->loadMultiple($chunk);
        $storage->delete($entities);
        $deleted += count($entities);
      }

      return $deleted;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to delete old @type entities: @message', [
        '@type' => $entityTypeId,
        '@message' => $e->getMessage(),
      ]);
      return 0;
    }
  }

}
