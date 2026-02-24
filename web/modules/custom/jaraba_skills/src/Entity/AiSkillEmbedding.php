<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * EMBEDDING DE HABILIDAD IA - AiSkillEmbedding
 *
 * PROPÓSITO:
 * Almacena el vector embedding (1536 dimensiones) del contenido de una skill
 * para permitir búsqueda semántica en Qdrant.
 *
 * ESTRUCTURA:
 * Esta entidad es auxiliar a AiSkill. Cada AiSkill puede tener un único
 * AiSkillEmbedding asociado que se actualiza cuando el content cambia.
 *
 * LÓGICA:
 * - Se regenera automáticamente cuando skill.content cambia (detectado por hash MD5)
 * - Se indexa en colección 'jaraba_skills' de Qdrant
 * - Permite match semántico de queries de usuario a skills relevantes
 * - El embedding usa OpenAI text-embedding-3-small (1536D)
 *
 * RELACIONES:
 * - AiSkillEmbedding -> AiSkill (entity_reference, 1:1)
 *
 * @ContentEntityType(
 *   id = "ai_skill_embedding",
 *   label = @Translation("Embedding de Habilidad IA"),
 *   label_collection = @Translation("Embeddings de Habilidades IA"),
 *   label_singular = @Translation("embedding de habilidad IA"),
 *   label_plural = @Translation("embeddings de habilidades IA"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\jaraba_skills\AiSkillAccessControlHandler",
 *   },
 *   base_table = "ai_skill_embedding",
 *   admin_permission = "administer ai skills",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai-skill-embeddings",
 *   },
 *   field_ui_base_route = "entity.ai_skill_embedding.settings",
 * )
 */
class AiSkillEmbedding extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia a la skill padre.
        // Esta relación es 1:1: cada skill tiene un único embedding.
        $fields['skill_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Habilidad'))
            ->setDescription(t('La habilidad IA asociada a este embedding.'))
            ->setSetting('target_type', 'ai_skill')
            ->setRequired(TRUE)
            ->setCardinality(1);

        // Vector embedding serializado como JSON.
        // Usamos string_long porque el vector tiene 1536 dimensiones (floats).
        // Ejemplo: "[0.0123, -0.0456, 0.0789, ...]"
        $fields['vector'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Vector Embedding'))
            ->setDescription(t('Vector de 1536 dimensiones serializado como JSON.'));

        // Hash MD5 del contenido de la skill.
        // Permite detectar si el contenido ha cambiado y necesita regenerar embedding.
        $fields['content_hash'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hash del Contenido'))
            ->setDescription(t('Hash MD5 del campo content de la skill para detectar cambios.'))
            ->setSetting('max_length', 32);

        // ID del punto en Qdrant para facilitar actualizaciones.
        // Formato UUID generado desde "ai_skill_{skill_id}".
        $fields['qdrant_point_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('ID del Punto en Qdrant'))
            ->setDescription(t('UUID del punto en la colección Qdrant.'))
            ->setSetting('max_length', 36);

        // Modelo usado para generar el embedding.
        // Útil si en el futuro cambiamos de modelo y necesitamos regenerar.
        $fields['embedding_model'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Modelo de Embedding'))
            ->setDescription(t('Modelo usado para generar el embedding.'))
            ->setDefaultValue('text-embedding-3-small')
            ->setSetting('max_length', 100);

        // Dimensiones del vector (para validación).
        $fields['dimensions'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Dimensiones'))
            ->setDescription(t('Número de dimensiones del vector.'))
            ->setDefaultValue(1536);

        // Timestamp de generación.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de Creación'));

        // Timestamp de última actualización.
        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Fecha de Modificación'));

        return $fields;
    }

    /**
     * Obtiene la skill asociada.
     *
     * @return \Drupal\jaraba_skills\Entity\AiSkill|null
     *   La entidad AiSkill o NULL si no existe.
     */
    public function getSkill(): ?AiSkill
    {
        return $this->get('skill_id')->entity;
    }

    /**
     * Obtiene el vector embedding como array de floats.
     *
     * @return array
     *   Array de floats con las 1536 dimensiones, o vacío si no hay vector.
     */
    public function getVector(): array
    {
        $vectorJson = $this->get('vector')->value;
        if (empty($vectorJson)) {
            return [];
        }

        $vector = json_decode($vectorJson, TRUE);
        return is_array($vector) ? $vector : [];
    }

    /**
     * Establece el vector embedding.
     *
     * @param array $vector
     *   Array de floats con las dimensiones del embedding.
     *
     * @return $this
     */
    public function setVector(array $vector): self
    {
        $this->set('vector', json_encode($vector));
        $this->set('dimensions', count($vector));
        return $this;
    }

    /**
     * Verifica si el embedding necesita regenerarse.
     *
     * Compara el hash almacenado con el hash actual del contenido de la skill.
     *
     * @return bool
     *   TRUE si el contenido ha cambiado y necesita regenerar embedding.
     */
    public function needsRegeneration(): bool
    {
        $skill = $this->getSkill();
        if (!$skill) {
            return FALSE;
        }

        $currentHash = md5($skill->getContent());
        $storedHash = $this->get('content_hash')->value ?? '';

        return $currentHash !== $storedHash;
    }

    /**
     * Actualiza el hash del contenido.
     *
     * @return $this
     */
    public function updateContentHash(): self
    {
        $skill = $this->getSkill();
        if ($skill) {
            $this->set('content_hash', md5($skill->getContent()));
        }
        return $this;
    }

}
