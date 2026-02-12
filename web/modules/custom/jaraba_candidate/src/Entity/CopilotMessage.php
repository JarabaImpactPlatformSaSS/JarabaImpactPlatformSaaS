<?php

namespace Drupal\jaraba_candidate\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad CopilotMessage para persistir mensajes individuales.
 *
 * PROPÓSITO:
 * Almacena cada mensaje intercambiado entre usuario y copilot, incluyendo
 * detección de intent, entidades extraídas, acciones sugeridas y feedback.
 * Permite análisis granular de conversaciones para mejora del sistema.
 *
 * BENEFICIOS:
 * - Intent detection para categorizar preguntas frecuentes
 * - Entity extraction para análisis de skills, empresas, etc.
 * - Feedback loop para mejorar respuestas
 * - Tracking de tokens para FinOps
 *
 * @ContentEntityType(
 *   id = "copilot_message",
 *   label = @Translation("Copilot Message"),
 *   label_collection = @Translation("Copilot Messages"),
 *   label_singular = @Translation("copilot message"),
 *   label_plural = @Translation("copilot messages"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "copilot_message",
 *   admin_permission = "administer copilot conversations",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/copilot-messages",
 *     "canonical" = "/admin/content/copilot-messages/{copilot_message}",
 *   },
 * )
 */
class CopilotMessage extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // === Relación con Conversación ===

        $fields['conversation_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Conversation'))
            ->setDescription(t('The parent conversation this message belongs to.'))
            ->setSetting('target_type', 'copilot_conversation')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayConfigurable('view', TRUE);

        // === Contenido del Mensaje ===

        $fields['role'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Role'))
            ->setDescription(t('Who sent this message.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'user' => 'User',
                'assistant' => 'Assistant',
                'system' => 'System',
            ])
            ->setDefaultValue('user')
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayConfigurable('view', TRUE);

        $fields['content'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Content'))
            ->setDescription(t('The message content.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayConfigurable('view', TRUE);

        // === Análisis de Intent ===

        $fields['intent_detected'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Intent Detected'))
            ->setDescription(t('The primary intent detected in this message.'))
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
                'greeting' => 'Greeting/Smalltalk',
                'unknown' => 'Unknown/Other',
            ])
            ->setDisplayOptions('view', ['weight' => 3])
            ->setDisplayConfigurable('view', TRUE);

        $fields['intent_confidence'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Intent Confidence'))
            ->setDescription(t('Confidence score 0-1 for the detected intent.'))
            ->setSetting('precision', 3)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', ['weight' => 4])
            ->setDisplayConfigurable('view', TRUE);

        // === Entidades Extraídas ===

        $fields['entities_extracted'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Entities Extracted'))
            ->setDescription(t('JSON object with extracted entities: skills, companies, locations, etc.'))
            ->setDisplayOptions('view', ['weight' => 5])
            ->setDisplayConfigurable('view', TRUE);

        // === Conocimiento Usado ===

        $fields['knowledge_used'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Knowledge Used'))
            ->setDescription(t('JSON array of KB document IDs used for RAG response.'))
            ->setDisplayOptions('view', ['weight' => 6])
            ->setDisplayConfigurable('view', TRUE);

        $fields['apis_called'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('APIs Called'))
            ->setDescription(t('JSON array of internal APIs called to answer this query.'))
            ->setDisplayOptions('view', ['weight' => 7])
            ->setDisplayConfigurable('view', TRUE);

        // === Acciones Sugeridas ===

        $fields['actions_in_response'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Actions In Response'))
            ->setDescription(t('JSON array of actionable CTAs included in the response.'))
            ->setDisplayOptions('view', ['weight' => 8])
            ->setDisplayConfigurable('view', TRUE);

        // === Tokens y Costos ===

        $fields['tokens_input'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Input Tokens'))
            ->setDescription(t('Number of input tokens for this message.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 9])
            ->setDisplayConfigurable('view', TRUE);

        $fields['tokens_output'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Output Tokens'))
            ->setDescription(t('Number of output tokens for the response.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 10])
            ->setDisplayConfigurable('view', TRUE);

        $fields['latency_ms'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Latency (ms)'))
            ->setDescription(t('Response latency in milliseconds.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 11])
            ->setDisplayConfigurable('view', TRUE);

        $fields['model_used'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Model Used'))
            ->setDescription(t('The LLM model used for this response.'))
            ->setSettings(['max_length' => 64])
            ->setDisplayOptions('view', ['weight' => 12])
            ->setDisplayConfigurable('view', TRUE);

        // === Feedback ===

        $fields['was_helpful'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Was Helpful'))
            ->setDescription(t('User feedback: was this response helpful?'))
            ->setDisplayOptions('view', ['weight' => 13])
            ->setDisplayConfigurable('view', TRUE);

        $fields['feedback_reason'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Feedback Reason'))
            ->setDescription(t('Reason if user marked as not helpful.'))
            ->setSettings(['max_length' => 128])
            ->setDisplayOptions('view', ['weight' => 14])
            ->setDisplayConfigurable('view', TRUE);

        // === Timestamps ===

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('When the message was sent.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('When the message was last updated.'));

        return $fields;
    }

    /**
     * Obtiene las entidades extraídas como array.
     */
    public function getEntities(): array
    {
        $json = $this->get('entities_extracted')->value;
        return $json ? json_decode($json, TRUE) : [];
    }

    /**
     * Establece las entidades extraídas.
     */
    public function setEntities(array $entities): self
    {
        $this->set('entities_extracted', json_encode($entities));
        return $this;
    }

    /**
     * Obtiene los IDs de documentos KB usados.
     */
    public function getKnowledgeUsed(): array
    {
        $json = $this->get('knowledge_used')->value;
        return $json ? json_decode($json, TRUE) : [];
    }

    /**
     * Establece los documentos KB usados.
     */
    public function setKnowledgeUsed(array $docIds): self
    {
        $this->set('knowledge_used', json_encode($docIds));
        return $this;
    }

    /**
     * Obtiene las acciones sugeridas.
     */
    public function getActions(): array
    {
        $json = $this->get('actions_in_response')->value;
        return $json ? json_decode($json, TRUE) : [];
    }

    /**
     * Establece las acciones sugeridas.
     */
    public function setActions(array $actions): self
    {
        $this->set('actions_in_response', json_encode($actions));
        return $this;
    }

    /**
     * Marca el mensaje con feedback del usuario.
     */
    public function setFeedback(bool $helpful, ?string $reason = NULL): self
    {
        $this->set('was_helpful', $helpful);
        if ($reason) {
            $this->set('feedback_reason', $reason);
        }
        return $this;
    }

}
