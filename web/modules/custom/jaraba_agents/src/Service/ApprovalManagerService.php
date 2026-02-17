<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de aprobaciones humanas para agentes autonomos.
 *
 * ESTRUCTURA:
 *   Gestiona el ciclo de vida de solicitudes de aprobacion humana
 *   que se generan cuando agentes L2+ intentan ejecutar acciones
 *   configuradas como requires_approval en sus guardrails.
 *
 * LOGICA:
 *   Las aprobaciones tienen estados: pending, approved, rejected, expired.
 *   Cada solicitud tiene un tiempo de expiracion configurable.
 *   Las aprobaciones pendientes se priorizan por nivel de riesgo
 *   (high > medium > low) y luego por fecha de creacion.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class ApprovalManagerService {

  /**
   * Horas por defecto hasta la expiracion de una aprobacion pendiente.
   */
  protected const DEFAULT_EXPIRATION_HOURS = 24;

  /**
   * Construye el servicio de gestion de aprobaciones.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_agents.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Crea una solicitud de aprobacion humana.
   *
   * Genera una entidad AgentApproval con estado 'pending' y calcula
   * la fecha de expiracion desde la configuracion del modulo.
   *
   * @param int $executionId
   *   ID de la ejecucion que requiere aprobacion.
   * @param int $agentId
   *   ID del agente autonomo que solicita aprobacion.
   * @param string $actionDescription
   *   Descripcion legible de la accion que se quiere ejecutar.
   * @param string $reasoning
   *   Razonamiento del agente para solicitar esta accion.
   * @param string $riskLevel
   *   Nivel de riesgo: 'low', 'medium' o 'high'.
   *
   * @return array
   *   Array con ['success' => true, 'approval_id' => int] o error.
   */
  public function requestApproval(int $executionId, int $agentId, string $actionDescription, string $reasoning = '', string $riskLevel = 'medium'): array {
    try {
      // Validar nivel de riesgo.
      $validRiskLevels = ['low', 'medium', 'high'];
      if (!in_array($riskLevel, $validRiskLevels, TRUE)) {
        $riskLevel = 'medium';
      }

      // Calcular fecha de expiracion.
      $expiresAt = date('Y-m-d\TH:i:s', strtotime('+' . self::DEFAULT_EXPIRATION_HOURS . ' hours'));

      // Obtener tenant_id del agente para asociar a la aprobacion.
      $agentStorage = $this->entityTypeManager->getStorage('autonomous_agent');
      $agent = $agentStorage->load($agentId);
      $tenantId = NULL;
      if ($agent && method_exists($agent, 'hasField') && $agent->hasField('tenant_id')) {
        // AUDIT-CONS-005: tenant_id como entity_reference a group.
        $tenantId = $agent->get('tenant_id')->target_id ?? NULL;
      }

      $approvalStorage = $this->entityTypeManager->getStorage('agent_approval');
      $approval = $approvalStorage->create([
        'execution_id' => $executionId,
        'agent_id' => $agentId,
        'status' => 'pending',
        'action_description' => $actionDescription,
        'reasoning' => $reasoning,
        'risk_level' => $riskLevel,
        'expires_at' => $expiresAt,
        // AUDIT-CONS-005: tenant_id como entity_reference a group.
        'tenant_id' => $tenantId,
      ]);
      $approval->save();

      $approvalId = (int) $approval->id();

      $this->logger->info('Solicitud de aprobacion @id creada para ejecucion @exec (agente @agent, riesgo: @risk).', [
        '@id' => $approvalId,
        '@exec' => $executionId,
        '@agent' => $agentId,
        '@risk' => $riskLevel,
      ]);

      return [
        'success' => TRUE,
        'approval_id' => $approvalId,
        'expires_at' => $expiresAt,
        'message' => (string) new TranslatableMarkup('Solicitud de aprobacion creada correctamente.'),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al crear solicitud de aprobacion: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error al crear la solicitud de aprobacion.'),
      ];
    }
  }

  /**
   * Aprueba una solicitud de aprobacion pendiente.
   *
   * Establece el estado a 'approved', registra el revisor y notas.
   *
   * @param int $approvalId
   *   ID de la aprobacion a aprobar.
   * @param int $reviewerId
   *   ID del usuario que aprueba la accion.
   * @param string $notes
   *   Notas opcionales del revisor.
   *
   * @return array
   *   Array con ['success' => true] o error.
   */
  public function approve(int $approvalId, int $reviewerId, string $notes = ''): array {
    return $this->processReview($approvalId, $reviewerId, 'approved', $notes);
  }

  /**
   * Rechaza una solicitud de aprobacion pendiente.
   *
   * Establece el estado a 'rejected', registra el revisor y notas.
   *
   * @param int $approvalId
   *   ID de la aprobacion a rechazar.
   * @param int $reviewerId
   *   ID del usuario que rechaza la accion.
   * @param string $notes
   *   Notas opcionales del revisor con el motivo de rechazo.
   *
   * @return array
   *   Array con ['success' => true] o error.
   */
  public function reject(int $approvalId, int $reviewerId, string $notes = ''): array {
    return $this->processReview($approvalId, $reviewerId, 'rejected', $notes);
  }

  /**
   * Expira aprobaciones pendientes que han superado su fecha limite.
   *
   * Busca todas las aprobaciones con status 'pending' cuya fecha
   * expires_at sea anterior al momento actual y las marca como 'expired'.
   *
   * @return int
   *   Numero de aprobaciones expiradas.
   */
  public function expireStale(): int {
    try {
      $approvalStorage = $this->entityTypeManager->getStorage('agent_approval');
      $now = date('Y-m-d\TH:i:s');

      $query = $approvalStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'pending')
        ->condition('expires_at', $now, '<');
      $ids = $query->execute();

      if (empty($ids)) {
        return 0;
      }

      $approvals = $approvalStorage->loadMultiple($ids);
      $expiredCount = 0;

      foreach ($approvals as $approval) {
        $approval->set('status', 'expired');
        $approval->save();
        $expiredCount++;

        $this->logger->info('Aprobacion @id expirada automaticamente.', [
          '@id' => $approval->id(),
        ]);
      }

      $this->logger->info('Total de aprobaciones expiradas: @count.', [
        '@count' => $expiredCount,
      ]);

      return $expiredCount;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al expirar aprobaciones: @message', [
        '@message' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Obtiene aprobaciones pendientes ordenadas por prioridad.
   *
   * Las aprobaciones se ordenan primero por nivel de riesgo
   * (high > medium > low) y luego por fecha de creacion ascendente.
   *
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return array
   *   Lista de aprobaciones pendientes con sus datos.
   */
  public function getPendingApprovals(int $limit = 50): array {
    try {
      $approvalStorage = $this->entityTypeManager->getStorage('agent_approval');
      $query = $approvalStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'pending')
        ->sort('created', 'ASC')
        ->range(0, $limit);
      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      $approvals = $approvalStorage->loadMultiple($ids);
      $results = [];

      // Mapeo de prioridad para ordenacion por riesgo.
      $riskPriority = ['high' => 0, 'medium' => 1, 'low' => 2];

      foreach ($approvals as $approval) {
        $results[] = [
          'approval_id' => (int) $approval->id(),
          'execution_id' => (int) ($approval->get('execution_id')->target_id ?? $approval->get('execution_id')->value ?? 0),
          'agent_id' => (int) ($approval->get('agent_id')->target_id ?? $approval->get('agent_id')->value ?? 0),
          'status' => $approval->get('status')->value,
          'action_description' => $approval->get('action_description')->value ?? '',
          'reasoning' => $approval->get('reasoning')->value ?? '',
          'risk_level' => $approval->get('risk_level')->value ?? 'medium',
          'risk_priority' => $riskPriority[$approval->get('risk_level')->value ?? 'medium'] ?? 1,
          'expires_at' => $approval->get('expires_at')->value ?? '',
          // AUDIT-CONS-005: tenant_id como entity_reference a group.
          'tenant_id' => $approval->get('tenant_id')->target_id ?? NULL,
        ];
      }

      // Ordenar por prioridad de riesgo (high primero), luego por ID.
      usort($results, function (array $a, array $b): int {
        $riskComparison = ($a['risk_priority'] ?? 1) <=> ($b['risk_priority'] ?? 1);
        if ($riskComparison !== 0) {
          return $riskComparison;
        }
        return ($a['approval_id'] ?? 0) <=> ($b['approval_id'] ?? 0);
      });

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener aprobaciones pendientes: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Carga una aprobacion individual por su ID.
   *
   * @param int $approvalId
   *   ID de la aprobacion a cargar.
   *
   * @return object|null
   *   Entidad AgentApproval o NULL si no existe.
   */
  public function getApproval(int $approvalId): ?object {
    try {
      $approvalStorage = $this->entityTypeManager->getStorage('agent_approval');
      $approval = $approvalStorage->load($approvalId);
      return $approval ?: NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al cargar aprobacion @id: @message', [
        '@id' => $approvalId,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Procesa una revision (aprobacion o rechazo) de una solicitud.
   *
   * Metodo interno compartido por approve() y reject().
   *
   * @param int $approvalId
   *   ID de la aprobacion a procesar.
   * @param int $reviewerId
   *   ID del usuario revisor.
   * @param string $newStatus
   *   Nuevo estado: 'approved' o 'rejected'.
   * @param string $notes
   *   Notas del revisor.
   *
   * @return array
   *   Array con ['success' => true] o error.
   */
  protected function processReview(int $approvalId, int $reviewerId, string $newStatus, string $notes): array {
    try {
      $approvalStorage = $this->entityTypeManager->getStorage('agent_approval');
      $approval = $approvalStorage->load($approvalId);

      if (!$approval) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup('Aprobacion con ID @id no encontrada.', ['@id' => $approvalId]),
        ];
      }

      $currentStatus = $approval->get('status')->value;
      if ($currentStatus !== 'pending') {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'La aprobacion ya fue procesada con estado "@status".',
            ['@status' => $currentStatus],
          ),
        ];
      }

      $approval->set('status', $newStatus);
      $approval->set('reviewed_by', $reviewerId);
      $approval->set('reviewed_at', date('Y-m-d\TH:i:s'));
      $approval->set('review_notes', $notes);
      $approval->save();

      $statusLabel = $newStatus === 'approved'
        ? (string) new TranslatableMarkup('aprobada')
        : (string) new TranslatableMarkup('rechazada');

      $this->logger->info('Aprobacion @id @status por usuario @reviewer.', [
        '@id' => $approvalId,
        '@status' => $statusLabel,
        '@reviewer' => $reviewerId,
      ]);

      return [
        'success' => TRUE,
        'approval_id' => $approvalId,
        'status' => $newStatus,
        'message' => (string) new TranslatableMarkup('Solicitud de aprobacion @status correctamente.', ['@status' => $statusLabel]),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al procesar revision de aprobacion @id: @message', [
        '@id' => $approvalId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup('Error al procesar la revision de la aprobacion.'),
      ];
    }
  }

}
