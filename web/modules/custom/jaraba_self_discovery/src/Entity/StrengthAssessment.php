<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad StrengthAssessment para evaluaciones de fortalezas VIA.
 *
 * Almacena los resultados del test de fortalezas (VIA-inspired) como
 * Content Entity, permitiendo Field UI, Views y consultas estructuradas.
 *
 * @ContentEntityType(
 *   id = "strength_assessment",
 *   label = @Translation("Evaluacion de Fortalezas"),
 *   label_collection = @Translation("Evaluaciones de Fortalezas"),
 *   label_singular = @Translation("evaluacion de fortalezas"),
 *   label_plural = @Translation("evaluaciones de fortalezas"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_self_discovery\ListBuilder\StrengthAssessmentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_self_discovery\Access\StrengthAssessmentAccessControlHandler",
 *   },
 *   base_table = "strength_assessment",
 *   admin_permission = "administer self discovery",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.strength_assessment.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *     "label" = "id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/strength-assessment/{strength_assessment}",
 *     "add-form" = "/admin/content/strength-assessments/add",
 *     "edit-form" = "/admin/content/strength-assessment/{strength_assessment}/edit",
 *     "delete-form" = "/admin/content/strength-assessment/{strength_assessment}/delete",
 *     "collection" = "/admin/content/strength-assessments",
 *   },
 * )
 */
class StrengthAssessment extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Campo owner (usuario propietario).
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Usuario'))
            ->setDescription(t('Usuario al que pertenece esta evaluacion.'))
            ->setSetting('target_type', 'user')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Top 5 fortalezas (JSON array).
        $fields['top_strengths'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Top 5 Fortalezas'))
            ->setDescription(t('JSON array con las 5 fortalezas principales.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'basic_string',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Todas las puntuaciones (JSON con scores de 24 fortalezas).
        $fields['all_scores'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Todas las puntuaciones'))
            ->setDescription(t('JSON con scores de las 24 fortalezas.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'basic_string',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Respuestas (JSON de las 20 selecciones).
        $fields['answers'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Respuestas'))
            ->setDescription(t('JSON con las 20 selecciones de pares.'))
            ->setDisplayConfigurable('view', TRUE);

        // Timestamp de creacion.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de creacion'))
            ->setDescription(t('Fecha en que se completo el test.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'timestamp',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Timestamp de actualizacion.
        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Ultima actualizacion'))
            ->setDescription(t('Fecha de ultima modificacion.'))
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

    /**
     * Obtiene el top 5 de fortalezas.
     */
    public function getTopStrengths(): array
    {
        $raw = $this->get('top_strengths')->value;
        return $raw ? (json_decode($raw, TRUE) ?? []) : [];
    }

    /**
     * Obtiene todas las puntuaciones.
     */
    public function getAllScores(): array
    {
        $raw = $this->get('all_scores')->value;
        return $raw ? (json_decode($raw, TRUE) ?? []) : [];
    }

    /**
     * Obtiene la fortaleza principal (primera del top 5).
     */
    public function getTopStrength(): ?array
    {
        $top = $this->getTopStrengths();
        return !empty($top) ? reset($top) : NULL;
    }

}
