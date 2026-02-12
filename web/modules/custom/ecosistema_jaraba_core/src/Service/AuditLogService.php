<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio para registrar eventos en el Audit Log.
 *
 * Proporciona un punto centralizado para crear registros de auditoría
 * de seguridad. Captura automáticamente el actor (usuario actual),
 * la dirección IP, y permite adjuntar contexto adicional.
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
   * Registra un evento en el Audit Log.
   *
   * @param string $eventType
   *   Identificador del tipo de evento (e.g. 'user.login', 'tenant.created').
   * @param array $context
   *   Contexto adicional del evento. Claves opcionales:
   *   - tenant_id (int): ID del grupo/tenant relacionado.
   *   - target_type (string): Tipo de entidad objetivo.
   *   - target_id (int): ID de la entidad objetivo.
   *   - details (array): Datos adicionales en formato estructurado.
   *   - severity (string): 'info' (default), 'warning', o 'critical'.
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
        $values['details'] = $context['details'];
      }

      $entity = $this->entityTypeManager
        ->getStorage('audit_log')
        ->create($values);
      $entity->save();
    }
    catch (\Exception $e) {
      // El audit log nunca debe romper el flujo de la aplicación.
      $this->logger->error('Failed to write audit log for event @event: @message', [
        '@event' => $eventType,
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
