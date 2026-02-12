<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\file\FileInterface;

/**
 * DOCUMENTO DEL TENANT - TenantDocument
 *
 * PROPÓSITO:
 * Almacena documentos subidos por el tenant (PDFs, DOCs, etc.)
 * Los documentos se procesan, extraen texto y se indexan en Qdrant.
 *
 * FLUJO DE PROCESAMIENTO:
 * 1. Upload: El tenant sube un archivo
 * 2. Extract: DocumentProcessorService extrae texto
 * 3. Chunk: El texto se divide en chunks de ~500 tokens
 * 4. Index: Cada chunk se indexa individualmente en Qdrant
 *
 * CHUNKS:
 * Los documentos largos se dividen en chunks para mejor retrieval.
 * Cada chunk mantiene referencia al documento padre.
 *
 * MULTILINGÜE (G114-3):
 * Campos de contenido traducibles (title, description, extracted_text).
 * Campos de organización (category, processing_status, file, timestamps) no traducibles.
 * Integración con content_translation para UI de traducción estándar.
 *
 * @ContentEntityType(
 *   id = "tenant_document",
 *   label = @Translation("Documento del Tenant"),
 *   label_collection = @Translation("Documentos del Tenant"),
 *   label_singular = @Translation("documento"),
 *   label_plural = @Translation("documentos"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_tenant_knowledge\TenantDocumentListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_tenant_knowledge\Form\TenantDocumentForm",
 *       "add" = "Drupal\jaraba_tenant_knowledge\Form\TenantDocumentForm",
 *       "edit" = "Drupal\jaraba_tenant_knowledge\Form\TenantDocumentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_tenant_knowledge\TenantKnowledgeAccessControlHandler",
 *   },
 *   base_table = "tenant_document",
 *   data_table = "tenant_document_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer tenant knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "add-form" = "/knowledge/documents/add",
 *     "edit-form" = "/knowledge/documents/{tenant_document}/edit",
 *     "delete-form" = "/knowledge/documents/{tenant_document}/delete",
 *   },
 * )
 */
class TenantDocument extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * Estados de procesamiento.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al tenant propietario (OBLIGATORIO).
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant propietario de este documento.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setCardinality(1);

        // === ARCHIVO ===

        // Archivo subido.
        $fields['file'] = BaseFieldDefinition::create('file')
            ->setLabel(t('Archivo'))
            ->setDescription(t('Archivo PDF, DOC, DOCX o TXT a procesar.'))
            ->setRequired(TRUE)
            ->setCardinality(1)
            ->setSettings([
                'file_directory' => 'tenant_documents/[date:custom:Y-m]',
                'file_extensions' => 'pdf doc docx txt md',
                'max_filesize' => '10 MB',
                'uri_scheme' => 'private',
            ])
            ->setDisplayOptions('form', [
                'type' => 'file_generic',
                'weight' => 0,
            ]);

        // === METADATOS ===

        // Título descriptivo.
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Nombre descriptivo del documento.'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
                'settings' => [
                    'placeholder' => 'Ej: Manual de Procedimientos 2024',
                ],
            ]);

        // Descripción breve.
        $fields['description'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Breve descripción del contenido del documento.'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 2,
                'settings' => [
                    'rows' => 3,
                ],
            ]);

        // Categoría.
        $fields['category'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Categoría'))
            ->setDescription(t('Tipo de documento.'))
            ->setSettings([
                'allowed_values' => [
                    'manual' => 'Manual de Procedimientos',
                    'catalog' => 'Catálogo de Productos',
                    'guide' => 'Guía de Usuario',
                    'contract' => 'Contrato/Términos',
                    'training' => 'Material de Formación',
                    'technical' => 'Documentación Técnica',
                    'general' => 'Documento General',
                ],
            ])
            ->setDefaultValue('general')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 3,
            ]);

        // === PROCESAMIENTO ===

        // Estado del procesamiento.
        $fields['processing_status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado del procesamiento del documento.'))
            ->setSettings([
                'allowed_values' => [
                    'pending' => 'Pendiente',
                    'processing' => 'Procesando',
                    'completed' => 'Completado',
                    'failed' => 'Error',
                ],
            ])
            ->setDefaultValue('pending');

        // Texto extraído (almacenado para referencia).
        $fields['extracted_text'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Texto Extraído'))
            ->setDescription(t('Texto completo extraído del documento.'))
            ->setTranslatable(TRUE);

        // Número de chunks generados.
        $fields['chunk_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Número de Chunks'))
            ->setDescription(t('Cantidad de fragmentos indexados.'))
            ->setDefaultValue(0);

        // Mensaje de error si falló.
        $fields['error_message'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Mensaje de Error'))
            ->setSetting('max_length', 500);

        // Fecha de último procesamiento.
        $fields['processed_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Fecha de Procesamiento'));

        // === ESTADO ===

        // Publicado o borrador.
        $fields['is_published'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Publicado'))
            ->setDescription(t('Si está publicado, el copiloto puede usarlo.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 4,
            ]);

        // === METADATOS RAG ===

        // Hash del contenido para detectar cambios.
        $fields['content_hash'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hash del Contenido'))
            ->setSetting('max_length', 32);

        // === TIMESTAMPS ===

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de Creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Fecha de Modificación'));

        return $fields;
    }

    /**
     * Obtiene el título.
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * Obtiene la descripción.
     */
    public function getDescription(): string
    {
        return $this->get('description')->value ?? '';
    }

    /**
     * Obtiene la categoría.
     */
    public function getCategory(): string
    {
        return $this->get('category')->value ?? 'general';
    }

    /**
     * Obtiene la etiqueta de la categoría.
     */
    public function getCategoryLabel(): string
    {
        $category = $this->getCategory();
        $allowedValues = $this->getFieldDefinition('category')->getSetting('allowed_values');
        return $allowedValues[$category] ?? $category;
    }

    /**
     * Obtiene el estado de procesamiento.
     */
    public function getProcessingStatus(): string
    {
        return $this->get('processing_status')->value ?? self::STATUS_PENDING;
    }

    /**
     * Establece el estado de procesamiento.
     */
    public function setProcessingStatus(string $status): static
    {
        $this->set('processing_status', $status);
        return $this;
    }

    /**
     * Verifica si está publicado.
     */
    public function isPublished(): bool
    {
        return (bool) $this->get('is_published')->value;
    }

    /**
     * Verifica si el procesamiento está completado.
     */
    public function isProcessed(): bool
    {
        return $this->getProcessingStatus() === self::STATUS_COMPLETED;
    }

    /**
     * Verifica si el procesamiento falló.
     */
    public function hasFailed(): bool
    {
        return $this->getProcessingStatus() === self::STATUS_FAILED;
    }

    /**
     * Obtiene el tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->get('tenant_id')->target_id ? (int) $this->get('tenant_id')->target_id : NULL;
    }

    /**
     * Obtiene el archivo asociado.
     *
     * @return \Drupal\file\FileInterface|null
     *   El archivo o NULL si no existe.
     */
    public function getFile(): ?FileInterface
    {
        $fileEntity = $this->get('file')->entity;
        return $fileEntity instanceof FileInterface ? $fileEntity : NULL;
    }

    /**
     * Obtiene el número de chunks.
     */
    public function getChunkCount(): int
    {
        return (int) ($this->get('chunk_count')->value ?? 0);
    }

    /**
     * Establece el número de chunks.
     */
    public function setChunkCount(int $count): static
    {
        $this->set('chunk_count', $count);
        return $this;
    }

    /**
     * Obtiene el texto extraído.
     */
    public function getExtractedText(): string
    {
        return $this->get('extracted_text')->value ?? '';
    }

    /**
     * Establece el texto extraído.
     */
    public function setExtractedText(string $text): static
    {
        $this->set('extracted_text', $text);
        return $this;
    }

    /**
     * Establece el mensaje de error.
     */
    public function setErrorMessage(string $message): static
    {
        $this->set('error_message', $message);
        return $this;
    }

    /**
     * Genera texto para embedding (metadatos + descripción).
     *
     * @return string
     *   Texto combinado para embedding del documento.
     */
    public function getEmbeddingText(): string
    {
        $parts = [];

        $parts[] = "Título: " . $this->getTitle();
        $parts[] = "Categoría: " . $this->getCategoryLabel();

        $description = $this->getDescription();
        if (!empty($description)) {
            $parts[] = "Descripción: " . $description;
        }

        return implode("\n", $parts);
    }

    /**
     * Genera el hash del contenido actual.
     */
    public function generateContentHash(): string
    {
        $file = $this->getFile();
        $fileHash = $file ? md5_file($file->getFileUri()) : '';
        return md5($this->getTitle() . $this->getDescription() . $fileHash);
    }

    /**
     * Verifica si el contenido necesita reprocesarse.
     */
    public function needsReprocessing(): bool
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

    /**
     * Marca como procesamiento iniciado.
     */
    public function markProcessingStarted(): void
    {
        $this->set('processing_status', self::STATUS_PROCESSING);
        $this->set('error_message', NULL);
        $this->save();
    }

    /**
     * Marca como procesamiento completado.
     */
    public function markProcessingCompleted(int $chunkCount): void
    {
        $this->set('processing_status', self::STATUS_COMPLETED);
        $this->set('chunk_count', $chunkCount);
        $this->set('processed_at', \Drupal::time()->getRequestTime());
        $this->updateContentHash();
        $this->save();
    }

    /**
     * Marca como procesamiento fallido.
     */
    public function markProcessingFailed(string $error): void
    {
        $this->set('processing_status', self::STATUS_FAILED);
        $this->set('error_message', substr($error, 0, 500));
        $this->save();
    }

}
