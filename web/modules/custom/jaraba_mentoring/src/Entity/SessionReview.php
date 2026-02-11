<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Session Review.
 *
 * Evaluación bidireccional mentor-emprendedor post-sesión.
 *
 * SPEC: 32_Emprendimiento_Mentoring_Sessions_v1
 *
 * @ContentEntityType(
 *   id = "session_review",
 *   label = @Translation("Review de Sesión"),
 *   label_collection = @Translation("Reviews de Sesiones"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "session_review",
 *   admin_permission = "manage sessions",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class SessionReview extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['session_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Sesión'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'mentoring_session');

        $fields['reviewer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Revisor'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user');

        $fields['reviewee_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Evaluado'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user');

        $fields['review_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Review'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'mentor_to_mentee' => 'Mentor evalúa a Emprendedor',
                'mentee_to_mentor' => 'Emprendedor evalúa a Mentor',
            ]);

        $fields['overall_rating'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Valoración General'))
            ->setRequired(TRUE)
            ->setSetting('min', 1)
            ->setSetting('max', 5)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['punctuality_rating'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntualidad'))
            ->setSetting('min', 1)
            ->setSetting('max', 5)
            ->setDisplayConfigurable('form', TRUE);

        $fields['preparation_rating'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Preparación'))
            ->setSetting('min', 1)
            ->setSetting('max', 5)
            ->setDisplayConfigurable('form', TRUE);

        $fields['communication_rating'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Comunicación'))
            ->setSetting('min', 1)
            ->setSetting('max', 5)
            ->setDisplayConfigurable('form', TRUE);

        $fields['comment'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Comentario Público'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['private_feedback'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Feedback Privado'))
            ->setDescription(t('Solo visible para el administrador.'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['would_recommend'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Recomendaría'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        return $fields;
    }

}
