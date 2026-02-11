<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad LifeWheelAssessment para evaluaciones de la Rueda de la Vida.
 *
 * PROPÓSITO:
 * Almacena las puntuaciones (1-10) del usuario en las 8 áreas de la vida
 * según metodología Osterwalder "Tu Modelo de Negocio Personal".
 *
 * ÁREAS:
 * 1. Trabajo/Carrera
 * 2. Finanzas
 * 3. Salud
 * 4. Familia
 * 5. Social/Amistades
 * 6. Desarrollo Personal
 * 7. Ocio
 * 8. Entorno Físico
 *
 * @ContentEntityType(
 *   id = "life_wheel_assessment",
 *   label = @Translation("Evaluación Rueda de la Vida"),
 *   label_collection = @Translation("Evaluaciones Rueda de la Vida"),
 *   label_singular = @Translation("evaluación"),
 *   label_plural = @Translation("evaluaciones"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_self_discovery\LifeWheelAssessmentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_self_discovery\Form\LifeWheelAssessmentForm",
 *       "add" = "Drupal\jaraba_self_discovery\Form\LifeWheelAssessmentForm",
 *       "edit" = "Drupal\jaraba_self_discovery\Form\LifeWheelAssessmentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_self_discovery\LifeWheelAssessmentAccessControlHandler",
 *   },
 *   base_table = "life_wheel_assessment",
 *   admin_permission = "administer self discovery",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.life_wheel_assessment.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *     "label" = "id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/life-wheel-assessment/{life_wheel_assessment}",
 *     "add-form" = "/admin/content/life-wheel-assessments/add",
 *     "edit-form" = "/admin/content/life-wheel-assessment/{life_wheel_assessment}/edit",
 *     "delete-form" = "/admin/content/life-wheel-assessment/{life_wheel_assessment}/delete",
 *     "collection" = "/admin/content/life-wheel-assessments",
 *   },
 * )
 */
class LifeWheelAssessment extends ContentEntityBase implements EntityOwnerInterface
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
            ->setDescription(t('Usuario al que pertenece esta evaluación.'))
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

        // ========================================
        // PUNTUACIONES POR ÁREA (1-10)
        // ========================================

        $areas = [
            'career' => t('Trabajo/Carrera'),
            'finance' => t('Finanzas'),
            'health' => t('Salud'),
            'family' => t('Familia'),
            'social' => t('Social/Amistades'),
            'growth' => t('Desarrollo Personal'),
            'leisure' => t('Ocio'),
            'environment' => t('Entorno Físico'),
        ];

        $weight = 0;
        foreach ($areas as $key => $label) {
            $fields["score_$key"] = BaseFieldDefinition::create('integer')
                ->setLabel($label)
                ->setDescription(t('Puntuación de 1 a 10 para @area.', ['@area' => $label]))
                ->setSettings([
                    'min' => 1,
                    'max' => 10,
                ])
                ->setDefaultValue(5)
                ->setDisplayOptions('view', [
                    'label' => 'inline',
                    'type' => 'number_integer',
                    'weight' => $weight,
                ])
                ->setDisplayOptions('form', [
                    'type' => 'number',
                    'weight' => $weight,
                ])
                ->setDisplayConfigurable('form', TRUE)
                ->setDisplayConfigurable('view', TRUE);

            $weight++;
        }

        // Notas/Reflexiones.
        $fields['notes'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Notas y Reflexiones'))
            ->setDescription(t('Observaciones personales sobre esta evaluación.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'text_default',
                'weight' => 10,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Timestamp de creación.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de creación'))
            ->setDescription(t('Fecha en que se realizó la evaluación.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'timestamp',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Timestamp de actualización.
        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Última actualización'))
            ->setDescription(t('Fecha de última modificación.'))
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

    /**
     * Obtiene la puntuación promedio de todas las áreas.
     *
     * @return float
     *   Promedio de las 8 áreas.
     */
    public function getAverageScore(): float
    {
        $areas = ['career', 'finance', 'health', 'family', 'social', 'growth', 'leisure', 'environment'];
        $total = 0;
        $count = 0;

        foreach ($areas as $area) {
            $value = $this->get("score_$area")->value;
            if ($value) {
                $total += (int) $value;
                $count++;
            }
        }

        return $count > 0 ? round($total / $count, 1) : 0.0;
    }

    /**
     * Obtiene todas las puntuaciones como array.
     *
     * @return array
     *   Array asociativo área => puntuación.
     */
    public function getAllScores(): array
    {
        return [
            'career' => (int) $this->get('score_career')->value,
            'finance' => (int) $this->get('score_finance')->value,
            'health' => (int) $this->get('score_health')->value,
            'family' => (int) $this->get('score_family')->value,
            'social' => (int) $this->get('score_social')->value,
            'growth' => (int) $this->get('score_growth')->value,
            'leisure' => (int) $this->get('score_leisure')->value,
            'environment' => (int) $this->get('score_environment')->value,
        ];
    }

}
