<?php

namespace Drupal\jaraba_candidate\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\ecosistema_jaraba_core\Interface\CopilotConversationInterface;
use Drupal\ecosistema_jaraba_core\Trait\CopilotConversationTrait;

/**
 * Define la entidad CopilotConversation para persistir conversaciones con copilotos.
 *
 * PROPÓSITO:
 * Almacena sesiones de conversación entre usuarios y los diferentes copilotos
 * (CareerCoach, RecruiterAssistant, LearningTutor) para análisis de intents,
 * detección de gaps de conocimiento y mejora continua del sistema.
 *
 * BENEFICIOS:
 * - Permite analizar qué preguntan los usuarios frecuentemente
 * - Detecta intents más comunes para optimizar respuestas
 * - Identifica queries sin resolver (gaps de KB)
 * - Genera insights para admins y tenants
 *
 * @ContentEntityType(
 *   id = "copilot_conversation",
 *   label = @Translation("Copilot Conversation"),
 *   label_collection = @Translation("Copilot Conversations"),
 *   label_singular = @Translation("copilot conversation"),
 *   label_plural = @Translation("copilot conversations"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "copilot_conversation",
 *   admin_permission = "administer copilot conversations",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/copilot-conversations",
 *     "canonical" = "/admin/content/copilot-conversations/{copilot_conversation}",
 *     "delete-form" = "/admin/content/copilot-conversations/{copilot_conversation}/delete",
 *   },
 * )
 */
class CopilotConversation extends ContentEntityBase implements CopilotConversationInterface
{

    use EntityChangedTrait;
    use CopilotConversationTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // === Identificación ===

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User'))
            ->setDescription(t('The user who initiated this conversation.'))
            ->setSetting('target_type', 'user')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayConfigurable('view', TRUE);

        $fields['copilot_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Copilot Type'))
            ->setDescription(t('Which copilot was used in this conversation.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'career_coach' => 'Career Coach (Candidato)',
                'recruiter_assistant' => 'Recruiter Assistant (Empleador)',
                'learning_tutor' => 'Learning Tutor (Estudiante)',
                'producer_copilot' => 'Producer Copilot (AgroConecta)',
                'generic' => 'Generic Assistant',
            ])
            ->setDefaultValue('generic')
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', ['weight' => 1])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('The tenant context for multi-tenancy filtering.'))
            ->setSetting('target_type', 'tenant')
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayConfigurable('view', TRUE);

        // === Métricas de Sesión ===

        $fields['started_at'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Started At'))
            ->setDescription(t('When the conversation started.'));

        $fields['ended_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Ended At'))
            ->setDescription(t('When the conversation ended (null if still active).'))
            ->setDisplayOptions('view', ['weight' => 3])
            ->setDisplayConfigurable('view', TRUE);

        $fields['message_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Message Count'))
            ->setDescription(t('Total messages in this conversation.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 4])
            ->setDisplayConfigurable('view', TRUE);

        // === Análisis de Contenido ===

        $fields['topics_discussed'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Topics Discussed'))
            ->setDescription(t('JSON array of detected topics/intents.'))
            ->setDisplayOptions('view', ['weight' => 5])
            ->setDisplayConfigurable('view', TRUE);

        $fields['primary_intent'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Primary Intent'))
            ->setDescription(t('The main intent detected in this conversation.'))
            ->setSetting('allowed_values', [
                'job_search' => 'Job Search',
                'cv_help' => 'CV/Resume Help',
                'interview_prep' => 'Interview Preparation',
                'profile_improve' => 'Profile Improvement',
                'learning_path' => 'Learning Path',
                'application_status' => 'Application Status',
                'cover_letter' => 'Cover Letter',
                'platform_help' => 'Platform Help',
                'emotional_support' => 'Emotional Support',
                'recruiter_screening' => 'Candidate Screening',
                'job_posting' => 'Job Posting Help',
                'employer_branding' => 'Employer Branding',
                'unknown' => 'Unknown/Other',
            ])
            ->setDefaultValue('unknown')
            ->setDisplayOptions('view', ['weight' => 6])
            ->setDisplayOptions('form', ['weight' => 6])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Efectividad ===

        $fields['actions_suggested'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Actions Suggested'))
            ->setDescription(t('Number of actionable suggestions provided.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 7])
            ->setDisplayConfigurable('view', TRUE);

        $fields['actions_taken'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Actions Taken'))
            ->setDescription(t('Number of suggested actions the user actually executed.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 8])
            ->setDisplayConfigurable('view', TRUE);

        $fields['was_resolved'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Was Resolved'))
            ->setDescription(t('Whether the user query was successfully resolved.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('view', ['weight' => 9])
            ->setDisplayConfigurable('view', TRUE);

        // === Feedback ===

        $fields['satisfaction_rating'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Satisfaction Rating'))
            ->setDescription(t('User satisfaction rating 1-5.'))
            ->setSetting('min', 1)
            ->setSetting('max', 5)
            ->setDisplayOptions('view', ['weight' => 10])
            ->setDisplayConfigurable('view', TRUE);

        $fields['feedback_text'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Feedback Text'))
            ->setDescription(t('Optional qualitative feedback from user.'))
            ->setDisplayOptions('view', ['weight' => 11])
            ->setDisplayConfigurable('view', TRUE);

        // === Costos y Tokens ===

        $fields['total_tokens_input'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total Input Tokens'))
            ->setDescription(t('Total input tokens used for cost tracking.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 12])
            ->setDisplayConfigurable('view', TRUE);

        $fields['total_tokens_output'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total Output Tokens'))
            ->setDescription(t('Total output tokens used for cost tracking.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 13])
            ->setDisplayConfigurable('view', TRUE);

        $fields['estimated_cost'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Estimated Cost'))
            ->setDescription(t('Estimated cost in USD for this conversation.'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 6)
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 14])
            ->setDisplayConfigurable('view', TRUE);

        // === Estado ===

        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Is Active'))
            ->setDescription(t('Whether the conversation is still active.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('view', ['weight' => 15])
            ->setDisplayConfigurable('view', TRUE);

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('The time the conversation was last updated.'));

        return $fields;
    }

    /**
     * Incrementa el contador de mensajes.
     */
    public function incrementMessageCount(): self
    {
        $current = (int) $this->get('message_count')->value;
        $this->set('message_count', $current + 1);
        return $this;
    }

    /**
     * Añade un topic al JSON array de topics.
     */
    public function addTopic(string $topic): self
    {
        $topicsJson = $this->get('topics_discussed')->value;
        $topics = $topicsJson ? json_decode($topicsJson, TRUE) : [];

        if (!in_array($topic, $topics)) {
            $topics[] = $topic;
            $this->set('topics_discussed', json_encode($topics));
        }

        return $this;
    }

    /**
     * Obtiene los topics como array.
     */
    public function getTopics(): array
    {
        $topicsJson = $this->get('topics_discussed')->value;
        return $topicsJson ? json_decode($topicsJson, TRUE) : [];
    }

    /**
     * Marca la conversación como finalizada.
     */
    public function endConversation(): self
    {
        $this->set('ended_at', \Drupal::time()->getRequestTime());
        $this->set('is_active', FALSE);
        return $this;
    }

    /**
     * Registra feedback del usuario.
     */
    public function setFeedback(int $rating, ?string $text = NULL): self
    {
        $this->set('satisfaction_rating', $rating);
        if ($text) {
            $this->set('feedback_text', $text);
        }
        return $this;
    }

    /**
     * Añade tokens al total.
     */
    public function addTokens(int $input, int $output): self
    {
        $currentInput = (int) $this->get('total_tokens_input')->value;
        $currentOutput = (int) $this->get('total_tokens_output')->value;

        $this->set('total_tokens_input', $currentInput + $input);
        $this->set('total_tokens_output', $currentOutput + $output);

        // Estimar costo (Claude pricing aprox: $3/M input, $15/M output)
        $inputCost = ($currentInput + $input) * 0.000003;
        $outputCost = ($currentOutput + $output) * 0.000015;
        $this->set('estimated_cost', $inputCost + $outputCost);

        return $this;
    }

}
