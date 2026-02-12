<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad DiagnosticRecommendation.
 *
 * Almacena recomendaciones generadas para un diagnóstico completado.
 * Cada recomendación incluye área, prioridad, acciones y quick wins.
 *
 * SPEC: 25_Emprendimiento_Business_Diagnostic_Core_v1
 *
 * @ContentEntityType(
 *   id = "diagnostic_recommendation",
 *   label = @Translation("Recomendación de Diagnóstico"),
 *   label_collection = @Translation("Recomendaciones"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_diagnostic\BusinessDiagnosticAccessControlHandler",
 *   },
 *   base_table = "diagnostic_recommendation",
 *   admin_permission = "administer business diagnostics",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 * )
 */
class DiagnosticRecommendation extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al diagnóstico padre
        $fields['diagnostic_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Diagnóstico'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'business_diagnostic');

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255);

        $fields['area'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Área'))
            ->setSetting('allowed_values', [
                'online_presence' => 'Presencia Online',
                'digital_sales' => 'Ventas Digitales',
                'digital_marketing' => 'Marketing Digital',
                'digital_operations' => 'Operaciones Digitales',
                'automation_ai' => 'Automatización e IA',
            ])
            ->setRequired(TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción detallada de la recomendación.'));

        $fields['action'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Acción Principal'))
            ->setSetting('max_length', 500);

        $fields['quick_wins'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Quick Wins'))
            ->setDescription(t('JSON array de acciones rápidas.'));

        $fields['priority'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Prioridad'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDefaultValue(0);

        $fields['estimated_impact'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Impacto Estimado'))
            ->setSetting('allowed_values', [
                'low' => 'Bajo',
                'medium' => 'Medio',
                'high' => 'Alto',
                'critical' => 'Crítico',
            ]);

        $fields['estimated_effort'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Esfuerzo Estimado'))
            ->setSetting('allowed_values', [
                'quick' => 'Quick Win (< 1 día)',
                'short' => 'Corto (1-7 días)',
                'medium' => 'Medio (1-4 semanas)',
                'long' => 'Largo (> 1 mes)',
            ]);

        // Referencia a itinerario relacionado
        $fields['related_path_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Itinerario Relacionado'))
            ->setSetting('target_type', 'digitalization_path');

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        return $fields;
    }

    /**
     * Obtiene los quick wins como array.
     */
    public function getQuickWins(): array
    {
        $json = $this->get('quick_wins')->value ?? '[]';
        return json_decode($json, TRUE) ?: [];
    }

}
