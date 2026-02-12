<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de aplicación de políticas de seguridad.
 *
 * Proporciona métodos para consultar políticas activas,
 * verificar cumplimiento y detectar violaciones.
 */
class PolicyEnforcerService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del módulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Obtiene las políticas activas, opcionalmente filtradas por tenant.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todas las políticas globales.
   *
   * @return array
   *   Array de entidades SecurityPolicy con estado 'active'.
   */
  public function getActivePolicies(?int $tenantId = NULL): array {
    try {
      $storage = $this->entityTypeManager->getStorage('security_policy_v2');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('policy_status', 'active');

      if ($tenantId !== NULL) {
        // Include both tenant-specific and global policies.
        $or = $query->orConditionGroup()
          ->condition('tenant_id', $tenantId)
          ->notExists('tenant_id');
        $query->condition($or);
      }
      else {
        // Only global policies (no tenant).
        $query->notExists('tenant_id');
      }

      $query->sort('policy_type');
      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      return $storage->loadMultiple($ids);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load active policies: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Verifica si existe una política activa y válida para un tipo dado.
   *
   * @param string $policyType
   *   El tipo de política (access_control, data_protection, etc.).
   * @param int|null $tenantId
   *   ID del tenant, o NULL para verificar solo políticas globales.
   *
   * @return bool
   *   TRUE si existe al menos una política activa del tipo especificado.
   */
  public function isPolicyCompliant(string $policyType, ?int $tenantId = NULL): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('security_policy_v2');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('policy_status', 'active')
        ->condition('policy_type', $policyType);

      if ($tenantId !== NULL) {
        $or = $query->orConditionGroup()
          ->condition('tenant_id', $tenantId)
          ->notExists('tenant_id');
        $query->condition($or);
      }

      $count = (int) $query->count()->execute();
      return $count > 0;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to check policy compliance for type @type: @message', [
        '@type' => $policyType,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Detecta violaciones de políticas para un tenant.
   *
   * Verifica que existan políticas activas para cada tipo requerido.
   * Los tipos requeridos son: access_control, data_protection,
   * incident_response, encryption, retention.
   *
   * @param int|null $tenantId
   *   ID del tenant, o NULL para verificar a nivel global.
   *
   * @return array
   *   Array de violaciones, cada una con:
   *   - policy_type (string): Tipo de política faltante.
   *   - message (string): Descripción de la violación.
   *   - severity (string): Nivel de severidad (warning, critical).
   */
  public function getViolations(?int $tenantId = NULL): array {
    $violations = [];
    $requiredTypes = [
      'access_control' => 'critical',
      'data_protection' => 'critical',
      'incident_response' => 'warning',
      'encryption' => 'warning',
      'retention' => 'warning',
    ];

    try {
      foreach ($requiredTypes as $type => $severity) {
        if (!$this->isPolicyCompliant($type, $tenantId)) {
          $violations[] = [
            'policy_type' => $type,
            'message' => sprintf('No active policy found for type: %s', $type),
            'severity' => $severity,
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to detect policy violations: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $violations;
  }

}
