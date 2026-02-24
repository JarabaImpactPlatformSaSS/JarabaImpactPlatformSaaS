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
 * POLÍTICA DEL TENANT - TenantPolicy
 *
 * PROPÓSITO:
 * Almacena políticas y procedimientos del negocio.
 * Ejemplos: política de devoluciones, envíos, privacidad, etc.
 * Cada política se indexa en Qdrant para retrieval semántico.
 *
 * VERSIONADO (G114-2):
 * Soporte completo de revisiones Drupal core. Cada guardado crea una
 * revisión automática con metadatos (usuario, fecha, mensaje de log).
 * Diff visual entre revisiones y rollback con un clic.
 * Se mantiene version_number para compatibilidad con lógica existente.
 *
 * MULTILINGÜE (G114-3):
 * Campos de contenido traducibles (title, content, summary, version_notes).
 * Campos de organización (policy_type, is_published, effective_date) no traducibles.
 * Integración con content_translation para UI de traducción estándar.
 *
 * TEMPLATES:
 * Políticas pueden partir de templates predefinidos
 * (returns, shipping, privacy, terms, etc.)
 *
 * @ContentEntityType(
 *   id = "tenant_policy",
 *   label = @Translation("Política del Tenant"),
 *   label_collection = @Translation("Políticas del Tenant"),
 *   label_singular = @Translation("política"),
 *   label_plural = @Translation("políticas"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_tenant_knowledge\TenantPolicyListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_tenant_knowledge\Form\TenantPolicyForm",
 *       "add" = "Drupal\jaraba_tenant_knowledge\Form\TenantPolicyForm",
 *       "edit" = "Drupal\jaraba_tenant_knowledge\Form\TenantPolicyForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_tenant_knowledge\TenantKnowledgeAccessControlHandler",
 *   },
 *   base_table = "tenant_policy",
 *   data_table = "tenant_policy_field_data",
 *   revision_table = "tenant_policy_revision",
 *   revision_data_table = "tenant_policy_field_revision",
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   admin_permission = "administer tenant knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/tenant-policies",
 *     "add-form" = "/knowledge/policies/add",
 *     "edit-form" = "/knowledge/policies/{tenant_policy}/edit",
 *     "delete-form" = "/knowledge/policies/{tenant_policy}/delete",
 *     "version-history" = "/knowledge/policies/{tenant_policy}/revisions",
 *   },
 *   field_ui_base_route = "entity.tenant_policy.settings",
 * )
 */
class TenantPolicy extends ContentEntityBase implements EntityChangedInterface, RevisionLogInterface
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
            ->setDescription(t('El tenant propietario de esta política.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setCardinality(1);

        // === CONTENIDO PRINCIPAL ===

        // Tipo de política.
        $fields['policy_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Política'))
            ->setDescription(t('Categoría de la política.'))
            ->setRequired(TRUE)
            ->setRevisionable(TRUE)
            ->setSettings([
                'allowed_values' => [
                    'returns' => 'Política de Devoluciones',
                    'shipping' => 'Política de Envíos',
                    'privacy' => 'Política de Privacidad',
                    'terms' => 'Términos y Condiciones',
                    'warranty' => 'Garantía',
                    'payment' => 'Métodos de Pago',
                    'cancellation' => 'Política de Cancelación',
                    'support' => 'Política de Soporte',
                    'custom' => 'Personalizada',
                ],
            ])
            ->setDefaultValue('custom')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 0,
            ]);

        // Título de la política.
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Título descriptivo de la política.'))
            ->setRequired(TRUE)
            ->setRevisionable(TRUE)
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
                    'placeholder' => 'Ej: Política de Devoluciones',
                ],
            ]);

        // Contenido completo de la política.
        $fields['content'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Contenido'))
            ->setDescription(t('Texto completo de la política.'))
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
                'weight' => 2,
                'settings' => [
                    'rows' => 15,
                ],
            ]);

        // Resumen corto (para mostrar en listados).
        $fields['summary'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Resumen'))
            ->setDescription(t('Resumen breve de la política (2-3 líneas).'))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 3,
                'settings' => [
                    'rows' => 3,
                ],
            ]);

        // === VERSIONADO ===

        // Número de versión (legacy, compatible con revision_log).
        $fields['version_number'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Versión'))
            ->setDescription(t('Número de versión de la política.'))
            ->setRevisionable(TRUE)
            ->setDefaultValue(1);

        // Notas de la versión (legacy, compatible con revision_log_message).
        $fields['version_notes'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Notas de Versión'))
            ->setDescription(t('Cambios realizados en esta versión.'))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 500)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
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

        // Fecha de vigencia.
        $fields['effective_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Vigencia'))
            ->setDescription(t('Fecha a partir de la cual aplica esta política.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 6,
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
     * Obtiene el título.
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * Obtiene el contenido.
     */
    public function getContent(): string
    {
        return $this->get('content')->value ?? '';
    }

    /**
     * Obtiene el tipo de política.
     */
    public function getPolicyType(): string
    {
        return $this->get('policy_type')->value ?? 'custom';
    }

    /**
     * Obtiene la etiqueta del tipo de política.
     */
    public function getPolicyTypeLabel(): string
    {
        $type = $this->getPolicyType();
        $allowedValues = $this->getFieldDefinition('policy_type')->getSetting('allowed_values');
        return $allowedValues[$type] ?? $type;
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
     * Obtiene el número de versión.
     */
    public function getVersionNumber(): int
    {
        return (int) ($this->get('version_number')->value ?? 1);
    }

    /**
     * Incrementa el número de versión.
     */
    public function incrementVersion(): void
    {
        $current = $this->getVersionNumber();
        $this->set('version_number', $current + 1);
    }

    /**
     * Genera texto combinado para embedding.
     *
     * @return string
     *   Texto combinado para embedding.
     */
    public function getEmbeddingText(): string
    {
        $parts = [];

        // Tipo y título.
        $parts[] = "Tipo: " . $this->getPolicyTypeLabel();
        $parts[] = "Título: " . $this->getTitle();

        // Resumen si existe.
        $summary = $this->get('summary')->value;
        if (!empty($summary)) {
            $parts[] = "Resumen: " . $summary;
        }

        // Contenido completo.
        $parts[] = "Contenido: " . $this->getContent();

        return implode("\n\n", $parts);
    }

    /**
     * Genera el hash del contenido actual.
     */
    public function generateContentHash(): string
    {
        return md5($this->getEmbeddingText());
    }

    /**
     * Verifica si el embedding necesita regenerarse.
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
