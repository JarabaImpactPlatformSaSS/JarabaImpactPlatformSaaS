<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio centralizado para registrar eventos en el Security Audit Log.
 *
 * Proporciona un punto centralizado para crear registros de auditoría
 * de seguridad. Captura automáticamente el actor (usuario actual),
 * la dirección IP, y permite adjuntar contexto adicional.
 *
 * Migrado y ampliado desde ecosistema_jaraba_core\Service\AuditLogService.
 * Usa la entidad security_audit_log en lugar de audit_log.
 *
 * USO:
 * @code
 * $this->auditLogService->log('user.login', [
 *   'severity' => 'info',
 *   'details' => ['method' => 'password'],
 * ]);
 *
 * $this->auditLogService->log('permission.changed', [
 *   'tenant_id' => 42,
 *   'target_type' => 'user',
 *   'target_id' => 15,
 *   'severity' => 'warning',
 *   'details' => ['role_added' => 'editor'],
 * ]);
 * @endcode
 */
class AuditLogService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   El usuario actual.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   La pila de requests para obtener IP.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del módulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected RequestStack $requestStack,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Registra un evento en el Security Audit Log.
   *
   * @param string $eventType
   *   Identificador del tipo de evento (e.g. 'user.login', 'tenant.created').
   * @param array $context
   *   Contexto adicional del evento. Claves opcionales:
   *   - tenant_id (int): ID del grupo/tenant relacionado.
   *   - target_type (string): Tipo de entidad objetivo.
   *   - target_id (int): ID de la entidad objetivo.
   *   - details (array): Datos adicionales en formato estructurado.
   *   - severity (string): 'info' (default), 'notice', 'warning', o 'critical'.
   */
  public function log(string $eventType, array $context = []): void {
    try {
      $request = $this->requestStack->getCurrentRequest();

      $values = [
        'event_type' => $eventType,
        'actor_id' => (int) $this->currentUser->id() ?: NULL,
        'ip_address' => $request ? $request->getClientIp() : '',
        'severity' => $context['severity'] ?? 'info',
      ];

      // Campos opcionales del contexto.
      if (isset($context['tenant_id'])) {
        $values['tenant_id'] = (int) $context['tenant_id'];
      }

      if (isset($context['target_type'])) {
        $values['target_type'] = (string) $context['target_type'];
      }

      if (isset($context['target_id'])) {
        $values['target_id'] = (int) $context['target_id'];
      }

      if (isset($context['details']) && is_array($context['details'])) {
        $values['details'] = json_encode($context['details'], JSON_THROW_ON_ERROR);
      }

      $entity = $this->entityTypeManager
        ->getStorage('security_audit_log')
        ->create($values);
      $entity->save();
    }
    catch (\Exception $e) {
      // El audit log nunca debe romper el flujo de la aplicación.
      $this->logger->error('Failed to write security audit log for event @event: @message', [
        '@event' => $eventType,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Verifica si el servicio de audit log está operativo.
   *
   * Comprueba que la tabla de la entidad existe y es accesible.
   *
   * @return bool
   *   TRUE si el servicio está operativo.
   */
  public function isOperational(): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('security_audit_log');
      $storage->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->range(0, 1)
        ->execute();
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->warning('Security audit log service is not operational: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
