<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_ai_agents\Entity\PendingApproval;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Psr\Log\LoggerInterface;

/**
 * Gestiona la cola de aprobaciones de workflows.
 *
 * PROPÓSITO:
 * Centraliza la creación, consulta y resolución de solicitudes
 * de aprobación para herramientas con requiresApproval().
 */
class PendingApprovalService
{

    /**
     * Tiempo de expiración por defecto (24 horas).
     */
    protected const DEFAULT_EXPIRY_HOURS = 24;

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected ToolRegistry $toolRegistry,
        protected AccountProxyInterface $currentUser,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Crea una solicitud de aprobación.
     *
     * @param string $workflowId
     *   ID del workflow.
     * @param string $stepId
     *   ID del paso.
     * @param string $toolId
     *   ID de la herramienta.
     * @param array $params
     *   Parámetros para la herramienta.
     * @param array $context
     *   Contexto de ejecución.
     *
     * @return \Drupal\jaraba_ai_agents\Entity\PendingApproval
     *   La solicitud creada.
     */
    public function create(
        string $workflowId,
        string $stepId,
        string $toolId,
        array $params,
        array $context = [],
    ): PendingApproval {
        $storage = $this->entityTypeManager->getStorage('pending_approval');

        $values = [
            'workflow_id' => $workflowId,
            'step_id' => $stepId,
            'tool_id' => $toolId,
            'params' => $params,
            'context' => $context,
            'status' => PendingApproval::STATUS_PENDING,
            'tenant_id' => $context['tenant_id'] ?? NULL,
            'requested_by' => $this->currentUser->id(),
            'expires_at' => time() + (self::DEFAULT_EXPIRY_HOURS * 3600),
        ];

        /** @var \Drupal\jaraba_ai_agents\Entity\PendingApproval $approval */
        $approval = $storage->create($values);
        $approval->save();

        $this->logger->info('Created pending approval @id for tool @tool', [
            '@id' => $approval->id(),
            '@tool' => $toolId,
        ]);

        return $approval;
    }

    /**
     * Obtiene solicitudes pendientes.
     *
     * @param int|null $tenantId
     *   Filtrar por tenant (opcional).
     *
     * @return array
     *   Array de PendingApproval.
     */
    public function getPending(?int $tenantId = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('pending_approval');
        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', PendingApproval::STATUS_PENDING)
            ->sort('created', 'DESC');

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();

        return $ids ? $storage->loadMultiple($ids) : [];
    }

    /**
     * Aprueba una solicitud y ejecuta la herramienta.
     *
     * @param int $approvalId
     *   ID de la solicitud.
     * @param string $notes
     *   Notas del aprobador.
     *
     * @return array
     *   Resultado de la ejecución.
     */
    public function approve(int $approvalId, string $notes = ''): array
    {
        $approval = $this->load($approvalId);

        if (!$approval) {
            return ['success' => FALSE, 'error' => 'Approval not found.'];
        }

        if (!$approval->isPending()) {
            return ['success' => FALSE, 'error' => 'Approval is not pending.'];
        }

        // Marcar como aprobado.
        $approval->approve((int) $this->currentUser->id(), $notes);
        $approval->save();

        // Ejecutar la herramienta.
        // HAL-AI-18: Mark context as pre_approved so ToolRegistry doesn't
        // create another approval request (it has been approved by a human).
        $executionContext = $approval->getContext();
        $executionContext['pre_approved'] = TRUE;

        $result = $this->toolRegistry->execute(
            $approval->getToolId(),
            $approval->getParams(),
            $executionContext
        );

        $this->logger->info('Approval @id approved and executed: success=@success', [
            '@id' => $approvalId,
            '@success' => $result['success'] ? 'true' : 'false',
        ]);

        return [
            'success' => TRUE,
            'approval_id' => $approvalId,
            'execution_result' => $result,
        ];
    }

    /**
     * Rechaza una solicitud.
     *
     * @param int $approvalId
     *   ID de la solicitud.
     * @param string $notes
     *   Motivo del rechazo.
     *
     * @return array
     *   Resultado.
     */
    public function reject(int $approvalId, string $notes = ''): array
    {
        $approval = $this->load($approvalId);

        if (!$approval) {
            return ['success' => FALSE, 'error' => 'Approval not found.'];
        }

        if (!$approval->isPending()) {
            return ['success' => FALSE, 'error' => 'Approval is not pending.'];
        }

        $approval->reject((int) $this->currentUser->id(), $notes);
        $approval->save();

        $this->logger->info('Approval @id rejected', ['@id' => $approvalId]);

        return [
            'success' => TRUE,
            'approval_id' => $approvalId,
        ];
    }

    /**
     * Carga una solicitud por ID.
     */
    protected function load(int $id): ?PendingApproval
    {
        $storage = $this->entityTypeManager->getStorage('pending_approval');
        /** @var \Drupal\jaraba_ai_agents\Entity\PendingApproval|null $entity */
        $entity = $storage->load($id);
        return $entity;
    }

    /**
     * Expira solicitudes antiguas.
     *
     * @return int
     *   Número de solicitudes expiradas.
     */
    public function expireOld(): int
    {
        $storage = $this->entityTypeManager->getStorage('pending_approval');
        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', PendingApproval::STATUS_PENDING)
            ->condition('expires_at', time(), '<');

        $ids = $query->execute();

        if (empty($ids)) {
            return 0;
        }

        $approvals = $storage->loadMultiple($ids);
        /** @var \Drupal\jaraba_ai_agents\Entity\PendingApproval $approval */
        foreach ($approvals as $approval) {
            $approval->set('status', PendingApproval::STATUS_EXPIRED);
            $approval->save();
        }

        $this->logger->info('Expired @count pending approvals', ['@count' => count($ids)]);

        return count($ids);
    }

}
