<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for PlanFormativoEi entity.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 * Campos computed (horas y cumplimiento) se muestran con #disabled = TRUE.
 */
class PlanFormativoEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'datos_principales' => [
        'label' => $this->t('Datos Principales'),
        'icon' => ['category' => 'education', 'name' => 'clipboard-list'],
        'description' => $this->t('Titulo, descripcion, carril y estado del plan formativo.'),
        'fields' => [
          'titulo',
          'descripcion',
          'carril',
          'estado',
        ],
      ],
      'composicion' => [
        'label' => $this->t('Composicion'),
        'icon' => ['category' => 'education', 'name' => 'book-open'],
        'description' => $this->t('Acciones formativas incluidas y resumen de horas.'),
        'fields' => [
          'accion_formativa_ids',
          'horas_formacion_previstas',
          'horas_orientacion_previstas',
          'horas_totales_previstas',
        ],
      ],
      'cumplimiento' => [
        'label' => $this->t('Cumplimiento'),
        'icon' => ['category' => 'ui', 'name' => 'shield-check'],
        'description' => $this->t('Verificacion de minimos de horas segun normativa.'),
        'fields' => [
          'cumple_minimo_formacion',
          'cumple_minimo_orientacion',
        ],
      ],
      'calendario' => [
        'label' => $this->t('Calendario'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'description' => $this->t('Fechas de inicio y fin del plan.'),
        'fields' => [
          'fecha_inicio',
          'fecha_fin',
        ],
      ],
      'notas' => [
        'label' => $this->t('Notas'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Notas internas sobre el plan formativo.'),
        'fields' => [
          'notas',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'education', 'name' => 'clipboard-list'];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Computed fields: #disabled = TRUE (PREMIUM-FORMS-PATTERN-001).
    $computedFields = [
      'horas_formacion_previstas',
      'horas_orientacion_previstas',
      'horas_totales_previstas',
      'cumple_minimo_formacion',
      'cumple_minimo_orientacion',
    ];
    foreach ($computedFields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#disabled'] = TRUE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'titulo' => 255,
      'notas' => 2000,
    ];
  }

}
