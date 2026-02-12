<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para DiagnosticQuestion.
 */
class DiagnosticQuestionListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['question'] = $this->t('Pregunta');
        $header['section'] = $this->t('Sección');
        $header['type'] = $this->t('Tipo');
        $header['required'] = $this->t('Obligatoria');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_diagnostic\Entity\DiagnosticQuestion $entity */

        // Truncar pregunta
        $question = $entity->label();
        if (strlen($question) > 60) {
            $question = substr($question, 0, 57) . '...';
        }
        $row['question'] = $question;

        // Obtener nombre de sección
        $section = $entity->get('section_id')->entity;
        $row['section'] = $section ? $section->label() : '-';

        // Tipo con label
        $typeLabels = [
            'boolean' => 'Sí/No',
            'scale_5' => 'Escala 1-5',
            'scale_10' => 'Escala 1-10',
            'single_choice' => 'Opción Única',
            'multiple_choice' => 'Múltiple',
            'numeric' => 'Numérico',
            'text' => 'Texto',
        ];
        $type = $entity->getQuestionType();
        $row['type'] = $typeLabels[$type] ?? $type;

        // Obligatoria
        $isRequired = $entity->get('is_required')->value;
        $row['required'] = $isRequired ? '✓' : '-';

        return $row + parent::buildRow($entity);
    }

}
