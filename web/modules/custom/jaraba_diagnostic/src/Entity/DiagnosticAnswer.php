<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad DiagnosticAnswer.
 *
 * Almacena las respuestas de un usuario a las preguntas del diagnóstico.
 * Cada answer vincula un diagnóstico con una pregunta y su respuesta.
 *
 * SPEC: 25_Emprendimiento_Business_Diagnostic_Core_v1
 *
 * @ContentEntityType(
 *   id = "diagnostic_answer",
 *   label = @Translation("Respuesta de Diagnóstico"),
 *   label_collection = @Translation("Respuestas de Diagnóstico"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_diagnostic\BusinessDiagnosticAccessControlHandler",
 *   },
 *   links = {
 *     "collection" = "/admin/content/diagnostic-answers",
 *   },
 *   field_ui_base_route = "entity.diagnostic_answer.settings",
 *   base_table = "diagnostic_answer",
 *   admin_permission = "administer business diagnostics",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class DiagnosticAnswer extends ContentEntityBase
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

        // Referencia a la pregunta
        $fields['question_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Pregunta'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'diagnostic_question');

        // Valor de la respuesta (flexible para distintos tipos)
        $fields['answer_value'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Valor'))
            ->setDescription(t('Valor de la respuesta (string para flexibilidad).'))
            ->setSetting('max_length', 255);

        // Para respuestas múltiples o texto largo
        $fields['answer_data'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Datos Adicionales'))
            ->setDescription(t('JSON para respuestas complejas o texto largo.'));

        // Score calculado de esta respuesta
        $fields['calculated_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Score Calculado'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDefaultValue(0);

        // Timestamp
        $fields['answered_at'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Respondido'));

        return $fields;
    }

    /**
     * Obtiene el valor de la respuesta.
     */
    public function getAnswerValue(): string
    {
        return $this->get('answer_value')->value ?? '';
    }

    /**
     * Obtiene el score calculado.
     */
    public function getCalculatedScore(): float
    {
        return (float) ($this->get('calculated_score')->value ?? 0);
    }

}
