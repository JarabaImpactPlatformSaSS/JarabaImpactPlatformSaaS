<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad PendingApproval para cola de aprobaciones.
 *
 * PROPÓSITO:
 * Almacena acciones de workflow que requieren aprobación humana
 * antes de ejecutarse (ej: envío de emails, publicaciones).
 *
 * FLUJO:
 * 1. WorkflowExecutor detecta tool con requiresApproval()
 * 2. Crea PendingApproval con estado 'pending'
 * 3. Admin aprueba/rechaza desde UI o API
 * 4. Si aprobado, ejecuta la tool y marca 'approved'
 *
 * @ContentEntityType(
 *   id = "pending_approval",
 *   label = @Translation("Pending Approval"),
 *   label_collection = @Translation("Pending Approvals"),
 *   label_singular = @Translation("pending approval"),
 *   label_plural = @Translation("pending approvals"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "pending_approval",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/pending-approvals",
 *   },
 *   field_ui_base_route = "entity.pending_approval.settings",
 * )
 */
class PendingApproval extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * Estados posibles.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['workflow_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Workflow ID'))
            ->setDescription(t('ID del workflow que generó esta aprobación.'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 128]);

        $fields['step_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Step ID'))
            ->setDescription(t('ID del paso dentro del workflow.'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 128]);

        $fields['tool_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tool ID'))
            ->setDescription(t('ID de la herramienta a ejecutar.'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 128]);

        $fields['params'] = BaseFieldDefinition::create('map')
            ->setLabel(t('Parameters'))
            ->setDescription(t('Parámetros serializados para la herramienta.'));

        $fields['context'] = BaseFieldDefinition::create('map')
            ->setLabel(t('Context'))
            ->setDescription(t('Contexto de ejecución del workflow.'));

        $fields['status'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Status'))
            ->setDescription(t('Estado: pending, approved, rejected, expired.'))
            ->setDefaultValue(self::STATUS_PENDING)
            ->setSettings(['max_length' => 32]);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Tenant asociado.'))
            ->setSetting('target_type', 'group');

        $fields['requested_by'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Requested By'))
            ->setDescription(t('Usuario o proceso que solicitó la acción.'))
            ->setSetting('target_type', 'user');

        $fields['decided_by'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Decided By'))
            ->setDescription(t('Usuario que aprobó/rechazó.'))
            ->setSetting('target_type', 'user');

        $fields['decision_notes'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Decision Notes'))
            ->setDescription(t('Notas del aprobador.'));

        $fields['expires_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Expires At'))
            ->setDescription(t('Timestamp de expiración automática.'));

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('Timestamp de creación.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('Timestamp de última actualización.'));

        return $fields;
    }

    /**
     * Getters.
     */
    public function getWorkflowId(): string
    {
        return $this->get('workflow_id')->value ?? '';
    }

    public function getStepId(): string
    {
        return $this->get('step_id')->value ?? '';
    }

    public function getToolId(): string
    {
        return $this->get('tool_id')->value ?? '';
    }

    public function getParams(): array
    {
        return $this->get('params')->first()?->getValue() ?? [];
    }

    public function getContext(): array
    {
        return $this->get('context')->first()?->getValue() ?? [];
    }

    public function getStatus(): string
    {
        return $this->get('status')->value ?? self::STATUS_PENDING;
    }

    public function isPending(): bool
    {
        return $this->getStatus() === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->getStatus() === self::STATUS_APPROVED;
    }

    /**
     * Aprobar la solicitud.
     */
    public function approve(int $userId, string $notes = ''): self
    {
        $this->set('status', self::STATUS_APPROVED);
        $this->set('decided_by', $userId);
        $this->set('decision_notes', $notes);
        return $this;
    }

    /**
     * Rechazar la solicitud.
     */
    public function reject(int $userId, string $notes = ''): self
    {
        $this->set('status', self::STATUS_REJECTED);
        $this->set('decided_by', $userId);
        $this->set('decision_notes', $notes);
        return $this;
    }

}
