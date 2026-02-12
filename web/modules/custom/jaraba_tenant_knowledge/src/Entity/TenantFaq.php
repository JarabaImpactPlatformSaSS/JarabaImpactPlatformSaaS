<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * FAQ DEL TENANT - TenantFaq
 *
 * PROPÓSITO:
 * Almacena preguntas frecuentes personalizadas del negocio.
 * Cada FAQ se indexa en Qdrant para retrieval semántico
 * durante las conversaciones con el copiloto.
 *
 * ESTRUCTURA:
 * - question: La pregunta del cliente (para el retrieval)
 * - answer: La respuesta que debe dar el copiloto
 * - category: Categoría opcional para organización
 * - is_published: Control de visibilidad
 *
 * MULTI-TENANCY:
 * Campo tenant_id obligatorio. Aislamiento completo por tenant.
 *
 * INTEGRACIÓN RAG:
 * El campo content_hash detecta cambios para regenerar embeddings.
 * El qdrant_point_id almacena la referencia para borrado.
 *
 * VERSIONADO (G114-2):
 * Soporte completo de revisiones Drupal. Cada guardado crea una revisión
 * automática. Diff visual entre revisiones y rollback con un clic.
 *
 * MULTILINGÜE (G114-3):
 * Campos de contenido traducibles (question, answer, question_variants).
 * Campos de organización (category, priority, is_published) no traducibles.
 * Integración con content_translation para UI de traducción estándar.
 *
 * @ContentEntityType(
 *   id = "tenant_faq",
 *   label = @Translation("FAQ del Tenant"),
 *   label_collection = @Translation("FAQs del Tenant"),
 *   label_singular = @Translation("FAQ"),
 *   label_plural = @Translation("FAQs"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_tenant_knowledge\TenantFaqListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_tenant_knowledge\Form\TenantFaqForm",
 *       "add" = "Drupal\jaraba_tenant_knowledge\Form\TenantFaqForm",
 *       "edit" = "Drupal\jaraba_tenant_knowledge\Form\TenantFaqForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_tenant_knowledge\TenantKnowledgeAccessControlHandler",
 *   },
 *   base_table = "tenant_faq",
 *   data_table = "tenant_faq_field_data",
 *   revision_table = "tenant_faq_revision",
 *   revision_data_table = "tenant_faq_field_revision",
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   admin_permission = "administer tenant knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "label" = "question",
 *     "langcode" = "langcode",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "add-form" = "/knowledge/faqs/add",
 *     "edit-form" = "/knowledge/faqs/{tenant_faq}/edit",
 *     "delete-form" = "/knowledge/faqs/{tenant_faq}/delete",
 *     "version-history" = "/knowledge/faqs/{tenant_faq}/revisions",
 *   },
 * )
 */
class TenantFaq extends ContentEntityBase implements EntityChangedInterface, RevisionLogInterface
{

    use EntityChangedTrait;
    use RevisionLogEntityTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Campos de metadatos de revisión (revision_uid, revision_timestamp, revision_log).
        $fields += static::revisionLogBaseFieldDefinitions($entity_type);

        // Referencia al tenant propietario (OBLIGATORIO).
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant propietario de esta FAQ.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setCardinality(1);

        // === CONTENIDO PRINCIPAL ===

        // Pregunta del cliente.
        $fields['question'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Pregunta'))
            ->setDescription(t('La pregunta como la haría un cliente.'))
            ->setRequired(TRUE)
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 500)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
                'settings' => [
                    'placeholder' => '¿Cuáles son los horarios de atención?',
                ],
            ]);

        // Respuesta del copiloto.
        $fields['answer'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Respuesta'))
            ->setDescription(t('La respuesta que debe dar el copiloto.'))
            ->setRequired(TRUE)
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'text_default',
                'weight' => 1,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
                'settings' => [
                    'rows' => 5,
                    'placeholder' => 'Nuestro horario de atención es de lunes a viernes de 9:00 a 18:00.',
                ],
            ]);

        // Variantes de la pregunta (para mejorar retrieval).
        $fields['question_variants'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Variantes de la Pregunta'))
            ->setDescription(t('Otras formas de hacer la misma pregunta, separadas por nueva línea.'))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 2,
            ]);

        // === ORGANIZACIÓN ===

        // Categoría de la FAQ.
        $fields['category'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Categoría'))
            ->setDescription(t('Categoría para organizar las FAQs.'))
            ->setRevisionable(TRUE)
            ->setSettings([
                'allowed_values' => [
                    'general' => 'General',
                    'products' => 'Productos/Servicios',
                    'shipping' => 'Envíos',
                    'returns' => 'Devoluciones',
                    'payment' => 'Pagos',
                    'support' => 'Soporte',
                    'promotions' => 'Promociones',
                    'other' => 'Otro',
                ],
            ])
            ->setDefaultValue('general')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 3,
            ]);

        // Prioridad (para ordenación).
        $fields['priority'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Prioridad'))
            ->setDescription(t('Mayor número = mayor prioridad. Afecta al orden en listados.'))
            ->setRevisionable(TRUE)
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 4,
            ]);

        // === ESTADO ===

        // Publicada o borrador.
        $fields['is_published'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Publicada'))
            ->setDescription(t('Si está publicada, el copiloto puede usarla.'))
            ->setRevisionable(TRUE)
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 5,
            ]);

        // === METADATOS RAG ===

        // Hash del contenido para detectar cambios.
        $fields['content_hash'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hash del Contenido'))
            ->setSetting('max_length', 32);

        // ID del punto en Qdrant.
        $fields['qdrant_point_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('ID en Qdrant'))
            ->setSetting('max_length', 36);

        // === TIMESTAMPS ===

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de Creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Fecha de Modificación'));

        return $fields;
    }

    /**
     * Obtiene la pregunta.
     */
    public function getQuestion(): string
    {
        return $this->get('question')->value ?? '';
    }

    /**
     * Obtiene la respuesta.
     */
    public function getAnswer(): string
    {
        return $this->get('answer')->value ?? '';
    }

    /**
     * Obtiene la categoría.
     */
    public function getCategory(): string
    {
        return $this->get('category')->value ?? 'general';
    }

    /**
     * Verifica si está publicada.
     */
    public function isPublished(): bool
    {
        return (bool) $this->get('is_published')->value;
    }

    /**
     * Obtiene el tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->get('tenant_id')->target_id ? (int) $this->get('tenant_id')->target_id : NULL;
    }

    /**
     * Genera texto combinado para embedding.
     *
     * Combina pregunta, variantes y respuesta para el vector.
     *
     * @return string
     *   Texto combinado para embedding.
     */
    public function getEmbeddingText(): string
    {
        $parts = [];

        // Pregunta principal.
        $parts[] = "Pregunta: " . $this->getQuestion();

        // Variantes si existen.
        $variants = $this->get('question_variants')->getValue();
        if (!empty($variants)) {
            $variantTexts = array_map(fn($v) => $v['value'], $variants);
            $parts[] = "Variantes: " . implode(', ', $variantTexts);
        }

        // Respuesta.
        $parts[] = "Respuesta: " . $this->getAnswer();

        // Categoría para contexto.
        $category = $this->getCategory();
        $allowedValues = $this->getFieldDefinition('category')->getSetting('allowed_values');
        $categoryLabel = $allowedValues[$category] ?? $category;
        $parts[] = "Categoría: " . $categoryLabel;

        return implode("\n\n", $parts);
    }

    /**
     * Genera el hash del contenido actual.
     *
     * @return string
     *   Hash MD5 del contenido.
     */
    public function generateContentHash(): string
    {
        return md5($this->getEmbeddingText());
    }

    /**
     * Verifica si el embedding necesita regenerarse.
     *
     * @return bool
     *   TRUE si el contenido ha cambiado.
     */
    public function needsRegeneration(): bool
    {
        $storedHash = $this->get('content_hash')->value;
        return $storedHash !== $this->generateContentHash();
    }

    /**
     * Actualiza el hash del contenido.
     */
    public function updateContentHash(): void
    {
        $this->set('content_hash', $this->generateContentHash());
    }

}
