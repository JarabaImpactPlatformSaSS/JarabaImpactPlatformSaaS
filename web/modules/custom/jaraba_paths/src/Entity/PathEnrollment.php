<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad PathEnrollment.
 *
 * Registra la inscripción de un usuario en un itinerario
 * y trackea su progreso a lo largo del tiempo.
 *
 * Equivalente a Enrollment en jaraba_lms.
 *
 * SPEC: 28_Emprendimiento_Digitalization_Paths_v1
 *
 * @ContentEntityType(
 *   id = "path_enrollment",
 *   label = @Translation("Inscripción en Itinerario"),
 *   label_collection = @Translation("Inscripciones"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "path_enrollment",
 *   admin_permission = "view any enrollment",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 * )
 */
class PathEnrollment extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['path_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Itinerario'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'digitalization_path');

        // Referencia al diagnóstico que originó la inscripción
        $fields['diagnostic_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Diagnóstico Origen'))
            ->setSetting('target_type', 'business_diagnostic');

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setSetting('allowed_values', [
                'active' => 'Activo',
                'paused' => 'Pausado',
                'completed' => 'Completado',
                'abandoned' => 'Abandonado',
            ])
            ->setDefaultValue('active');

        // Progreso
        $fields['progress_percent'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Progreso (%)'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDefaultValue(0);

        $fields['steps_completed'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Pasos Completados'))
            ->setDefaultValue(0);

        $fields['current_phase_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Fase Actual'))
            ->setSetting('target_type', 'path_phase');

        $fields['current_module_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Módulo Actual'))
            ->setSetting('target_type', 'path_module');

        $fields['current_step_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Paso Actual'))
            ->setSetting('target_type', 'path_step');

        // Fechas
        $fields['enrolled_at'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de Inscripción'));

        $fields['started_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Fecha de Inicio'));

        $fields['completed_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Fecha de Completitud'));

        $fields['last_activity_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Última Actividad'));

        // Gamificación
        $fields['xp_earned'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('XP Ganados'))
            ->setDefaultValue(0);

        $fields['streak_days'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Días de Racha'))
            ->setDefaultValue(0);

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Obtiene el porcentaje de progreso.
     */
    public function getProgressPercent(): float
    {
        return (float) ($this->get('progress_percent')->value ?? 0);
    }

    /**
     * Verifica si está completado.
     */
    public function isCompleted(): bool
    {
        return $this->get('status')->value === 'completed';
    }

    /**
     * Marca un step como completado.
     */
    public function recordStepCompletion(int $stepId, int $xp = 0): void
    {
        $this->set('steps_completed', $this->get('steps_completed')->value + 1);
        $this->set('xp_earned', $this->get('xp_earned')->value + $xp);
        $this->set('last_activity_at', time());
    }

}
