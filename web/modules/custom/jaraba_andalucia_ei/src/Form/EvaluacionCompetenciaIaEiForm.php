<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for EvaluacionCompetenciaIaEi entity.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 */
class EvaluacionCompetenciaIaEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'evaluacion' => [
        'label' => $this->t('Evaluación'),
        'icon' => ['category' => 'ai', 'name' => 'sparkles'],
        'description' => $this->t('Datos de la evaluación de competencia IA.'),
        'fields' => [
          'participante_id',
          'tipo',
          'nivel_global',
          'indicadores',
        ],
      ],
      'feedback' => [
        'label' => $this->t('Feedback'),
        'icon' => ['category' => 'communication', 'name' => 'star'],
        'description' => $this->t('Evaluador y observaciones.'),
        'fields' => [
          'evaluador',
          'notas',
          'tenant_id',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ai', 'name' => 'sparkles'];
  }

}
