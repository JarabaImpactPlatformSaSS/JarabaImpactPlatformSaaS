<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad DiagnosticQuestion.
 *
 * Representa una pregunta individual dentro de una sección de diagnóstico.
 * Incluye texto de pregunta, tipo de respuesta, opciones y lógica condicional.
 *
 * SPEC: 25_Emprendimiento_Business_Diagnostic_Core_v1
 *
 * @ContentEntityType(
 *   id = "diagnostic_question",
 *   label = @Translation("Pregunta de Diagnóstico"),
 *   label_collection = @Translation("Preguntas de Diagnóstico"),
 *   label_singular = @Translation("pregunta"),
 *   label_plural = @Translation("preguntas"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_diagnostic\DiagnosticQuestionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_diagnostic\BusinessDiagnosticAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "diagnostic_question",
 *   admin_permission = "manage diagnostic sections",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "question_text",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/diagnostic-questions",
 *     "add-form" = "/admin/structure/diagnostic-questions/add",
 *     "canonical" = "/admin/structure/diagnostic-question/{diagnostic_question}",
 *     "edit-form" = "/admin/structure/diagnostic-question/{diagnostic_question}/edit",
 *     "delete-form" = "/admin/structure/diagnostic-question/{diagnostic_question}/delete",
 *   },
 *   field_ui_base_route = "entity.diagnostic_question.settings",
 * )
 */
class DiagnosticQuestion extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia a la sección padre
        $fields['section_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Sección'))
            ->setDescription(t('Sección a la que pertenece esta pregunta.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'diagnostic_section')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['question_text'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Texto de la Pregunta'))
            ->setDescription(t('La pregunta que se mostrará al usuario.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 500)
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', ['weight' => 1])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['help_text'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Texto de Ayuda'))
            ->setDescription(t('Explicación adicional para el usuario.'))
            ->setSetting('max_length', 500)
            ->setDisplayOptions('form', ['weight' => 2])
            ->setDisplayConfigurable('form', TRUE);

        $fields['question_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Pregunta'))
            ->setDescription(t('Formato de respuesta esperado.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'boolean' => 'Sí/No',
                'scale_5' => 'Escala 1-5',
                'scale_10' => 'Escala 1-10',
                'single_choice' => 'Opción Única',
                'multiple_choice' => 'Opción Múltiple',
                'numeric' => 'Numérico',
                'text' => 'Texto Libre',
            ])
            ->setDefaultValue('scale_5')
            ->setDisplayOptions('form', ['weight' => 3])
            ->setDisplayConfigurable('form', TRUE);

        $fields['options'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Opciones'))
            ->setDescription(t('JSON con opciones para preguntas de selección. Formato: [{"value": "a", "label": "Opción A", "score": 20}]'))
            ->setDisplayOptions('form', ['weight' => 4])
            ->setDisplayConfigurable('form', TRUE);

        $fields['max_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntuación Máxima'))
            ->setDescription(t('Máxima puntuación posible para esta pregunta.'))
            ->setDefaultValue(100)
            ->setDisplayOptions('form', ['weight' => 5])
            ->setDisplayConfigurable('form', TRUE);

        $fields['weight_in_section'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Peso en la Sección'))
            ->setDescription(t('Peso relativo de esta pregunta dentro de su sección (0.0 - 1.0).'))
            ->setSetting('precision', 3)
            ->setSetting('scale', 2)
            ->setDefaultValue(0.20)
            ->setDisplayOptions('form', ['weight' => 6])
            ->setDisplayConfigurable('form', TRUE);

        $fields['order'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Orden'))
            ->setDescription(t('Orden de visualización dentro de la sección.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', ['weight' => 7])
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_required'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Obligatoria'))
            ->setDescription(t('Si esta pregunta es obligatoria.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', ['weight' => 8])
            ->setDisplayConfigurable('form', TRUE);

        // Lógica condicional
        $fields['condition'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Condición de Visualización'))
            ->setDescription(t('JSON con condiciones para mostrar esta pregunta. Formato: {"question_id": X, "operator": "==", "value": "yes"}'))
            ->setDisplayOptions('form', ['weight' => 9])
            ->setDisplayConfigurable('form', TRUE);

        // Sectores aplicables
        $fields['applicable_sectors'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Sectores Aplicables'))
            ->setDescription(t('Lista de sectores separados por coma. Vacío = todos.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['weight' => 10])
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Obtiene el tipo de pregunta.
     */
    public function getQuestionType(): string
    {
        return $this->get('question_type')->value ?? 'scale_5';
    }

    /**
     * Obtiene las opciones como array.
     */
    public function getOptions(): array
    {
        $json = $this->get('options')->value ?? '[]';
        return json_decode($json, TRUE) ?: [];
    }

    /**
     * Verifica si la pregunta es aplicable a un sector.
     */
    public function isApplicableToSector(string $sector): bool
    {
        $applicable = $this->get('applicable_sectors')->value ?? '';
        if (empty($applicable)) {
            return TRUE; // Aplicable a todos
        }
        $sectors = array_map('trim', explode(',', $applicable));
        return in_array($sector, $sectors);
    }

}
