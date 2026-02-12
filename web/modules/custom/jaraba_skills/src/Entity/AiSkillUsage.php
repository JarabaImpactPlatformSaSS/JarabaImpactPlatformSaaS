<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad AiSkillUsage.
 *
 * Registra cada invocación de una habilidad IA para analytics y
 * control de costos. Incluye métricas de latencia, tokens y éxito.
 *
 * @ContentEntityType(
 *   id = "ai_skill_usage",
 *   label = @Translation("Uso de Habilidad IA"),
 *   label_collection = @Translation("Registros de Uso de Habilidades IA"),
 *   label_singular = @Translation("registro de uso"),
 *   label_plural = @Translation("registros de uso"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\jaraba_skills\AiSkillAccessControlHandler",
 *   },
 *   base_table = "ai_skill_usage",
 *   admin_permission = "administer ai skills",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai-skills/usage",
 *   },
 * )
 */
class AiSkillUsage extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al skill invocado.
        $fields['skill_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Habilidad'))
            ->setDescription(t('La habilidad que fue invocada.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'ai_skill')
            ->setDisplayConfigurable('view', TRUE);

        // Tenant que realizó la invocación.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant que invocó la habilidad.'))
            ->setSetting('target_type', 'tenant')
            ->setDisplayConfigurable('view', TRUE);

        // Vertical del contexto.
        $fields['vertical'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Vertical'))
            ->setDescription(t('Vertical del contexto de invocación.'))
            ->setSetting('max_length', 50)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de agente.
        $fields['agent_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo de Agente'))
            ->setDescription(t('Tipo de agente que usó la habilidad.'))
            ->setSetting('max_length', 100)
            ->setDisplayConfigurable('view', TRUE);

        // Latencia en milisegundos.
        $fields['latency_ms'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Latencia (ms)'))
            ->setDescription(t('Tiempo de respuesta en milisegundos.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        // Tokens de entrada.
        $fields['tokens_input'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tokens de Entrada'))
            ->setDescription(t('Número de tokens en el prompt.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        // Tokens de salida.
        $fields['tokens_output'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tokens de Salida'))
            ->setDescription(t('Número de tokens en la respuesta.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        // Éxito de la invocación.
        $fields['success'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Éxito'))
            ->setDescription(t('Si la invocación fue exitosa.'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Mensaje de error (si aplica).
        $fields['error_message'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Mensaje de Error'))
            ->setDescription(t('Mensaje de error si la invocación falló.'))
            ->setDisplayConfigurable('view', TRUE);

        // Modelo LLM usado.
        $fields['model'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Modelo'))
            ->setDescription(t('Modelo de IA usado (ej: gpt-4, claude-3).'))
            ->setSetting('max_length', 100)
            ->setDisplayConfigurable('view', TRUE);

        // Usuario que invocó (opcional, puede ser anónimo).
        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Usuario'))
            ->setDescription(t('Usuario que realizó la invocación.'))
            ->setSetting('target_type', 'user')
            ->setDisplayConfigurable('view', TRUE);

        // Timestamp de creación.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha y hora de la invocación.'))
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

    /**
     * Obtiene el ID del skill invocado.
     */
    public function getSkillId(): ?int
    {
        return $this->get('skill_id')->target_id ? (int) $this->get('skill_id')->target_id : NULL;
    }

    /**
     * Obtiene el ID del tenant.
     */
    public function getTenantId(): ?int
    {
        return $this->get('tenant_id')->target_id ? (int) $this->get('tenant_id')->target_id : NULL;
    }

    /**
     * Obtiene la vertical.
     */
    public function getVertical(): string
    {
        return $this->get('vertical')->value ?? '';
    }

    /**
     * Obtiene el tipo de agente.
     */
    public function getAgentType(): string
    {
        return $this->get('agent_type')->value ?? '';
    }

    /**
     * Obtiene la latencia en ms.
     */
    public function getLatencyMs(): int
    {
        return (int) ($this->get('latency_ms')->value ?? 0);
    }

    /**
     * Obtiene tokens de entrada.
     */
    public function getTokensInput(): int
    {
        return (int) ($this->get('tokens_input')->value ?? 0);
    }

    /**
     * Obtiene tokens de salida.
     */
    public function getTokensOutput(): int
    {
        return (int) ($this->get('tokens_output')->value ?? 0);
    }

    /**
     * Obtiene total de tokens.
     */
    public function getTotalTokens(): int
    {
        return $this->getTokensInput() + $this->getTokensOutput();
    }

    /**
     * Verifica si fue exitoso.
     */
    public function wasSuccessful(): bool
    {
        return (bool) ($this->get('success')->value ?? TRUE);
    }

    /**
     * Obtiene mensaje de error.
     */
    public function getErrorMessage(): string
    {
        return $this->get('error_message')->value ?? '';
    }

    /**
     * Obtiene el modelo usado.
     */
    public function getModel(): string
    {
        return $this->get('model')->value ?? '';
    }

    /**
     * Obtiene timestamp de creación.
     */
    public function getCreatedTime(): int
    {
        return (int) ($this->get('created')->value ?? 0);
    }

}
