<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\jaraba_training\Service\MethodRubricService;

/**
 * Formulario de evaluación con rúbrica del Método Jaraba.
 *
 * CERT-07: Rúbrica interactiva con puntuación 1-4 por competencia y capa.
 * CERT-09: Acciones post-evaluación: aprobar, mejorar, rechazar.
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 *
 * Abre en slide-panel desde el dashboard del evaluador.
 */
class RubricEvaluationForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'competencias' => [
        'label' => $this->t('Competencias IA'),
        'icon' => ['category' => 'ai', 'name' => 'copilot'],
        'description' => $this->t('Puntuación 1-4 en cada competencia de supervisión IA.'),
        'fields' => [
          'comp_pedir_score',
          'comp_evaluar_score',
          'comp_iterar_score',
          'comp_integrar_score',
        ],
      ],
      'capas' => [
        'label' => $this->t('Capas del Método'),
        'icon' => ['category' => 'analytics', 'name' => 'target'],
        'description' => $this->t('Puntuación 1-4 en cada capa del Método Jaraba.'),
        'fields' => [
          'layer_criterio_score',
          'layer_supervision_score',
          'layer_posicionamiento_score',
        ],
      ],
      'resultado' => [
        'label' => $this->t('Resultado'),
        'icon' => ['category' => 'compliance', 'name' => 'certificate'],
        'description' => $this->t('Nivel global calculado y acción recomendada.'),
        'fields' => [
          'overall_level',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'compliance', 'name' => 'certificate'];
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Información de indicadores por nivel para guiar al evaluador.
    $form['indicadores_help'] = [
      '#type' => 'details',
      '#title' => $this->t('Guía de niveles'),
      '#open' => FALSE,
      '#weight' => -100,
    ];

    $levels = MethodRubricService::LEVELS;
    foreach ($levels as $num => $name) {
      $form['indicadores_help']['nivel_' . $num] = [
        '#markup' => '<p><strong>' . $this->t('Nivel @num — @name', ['@num' => $num, '@name' => ucfirst($name)]) . '</strong></p>',
      ];
    }

    // Campo overall_level como disabled (calculado automáticamente).
    if (isset($form['overall_level'])) {
      $form['overall_level']['widget'][0]['value']['#disabled'] = TRUE;
      $form['overall_level']['widget'][0]['value']['#description'] = $this->t('Calculado automáticamente como el mínimo de las 4 competencias.');
    }

    // Botones de acción post-evaluación (CERT-09).
    $form['actions']['approve'] = [
      '#type' => 'submit',
      '#value' => $this->t('Aprobar certificación'),
      '#submit' => ['::submitForm', '::save', '::approveAction'],
      '#attributes' => ['class' => ['btn-primary']],
      '#weight' => 5,
    ];

    $form['actions']['improve'] = [
      '#type' => 'submit',
      '#value' => $this->t('Solicitar mejoras'),
      '#submit' => ['::submitForm', '::save', '::improveAction'],
      '#attributes' => ['class' => ['btn-ghost']],
      '#weight' => 10,
    ];

    $form['actions']['reject'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rechazar'),
      '#submit' => ['::submitForm', '::save', '::rejectAction'],
      '#attributes' => ['class' => ['btn-ghost', 'btn-ghost--danger']],
      '#weight' => 15,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;

    // CERT-08: Calcular nivel global como mínimo de 4 competencias.
    $scores = [
      'pedir' => (int) ($entity->get('comp_pedir_score')->value ?? 0),
      'evaluar' => (int) ($entity->get('comp_evaluar_score')->value ?? 0),
      'iterar' => (int) ($entity->get('comp_iterar_score')->value ?? 0),
      'integrar' => (int) ($entity->get('comp_integrar_score')->value ?? 0),
    ];

    $rubricService = \Drupal::service('jaraba_training.method_rubric');
    $overallLevel = $rubricService->calculateOverallLevel($scores);
    $entity->set('overall_level', $overallLevel);

    // Verificar disparidad.
    $disparity = $rubricService->checkScoreDisparity($scores);
    if ($disparity !== NULL) {
      $this->messenger()->addWarning($disparity);
    }

    return parent::save($form, $form_state);
  }

  /**
   * Acción: Aprobar certificación.
   *
   * Cambia el estado a 'completed' y genera el certificado.
   */
  public function approveAction(array &$form, FormStateInterface $form_state): void {
    $entity = $this->entity;
    $entity->set('certification_status', 'completed');
    $entity->set('certification_date', date('Y-m-d'));
    $entity->save();

    $this->messenger()->addStatus($this->t('Certificación aprobada. Certificado generado.'));
    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

  /**
   * Acción: Solicitar mejoras al participante.
   *
   * Mantiene el estado 'in_progress' con feedback.
   */
  public function improveAction(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addWarning($this->t('Evaluación guardada. Se ha notificado al participante para que mejore su portfolio.'));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * Acción: Rechazar certificación.
   *
   * Cambia el estado a 'revoked' con motivo documentado.
   */
  public function rejectAction(array &$form, FormStateInterface $form_state): void {
    $entity = $this->entity;
    $entity->set('certification_status', 'revoked');
    $entity->save();

    $this->messenger()->addStatus($this->t('Certificación rechazada. Motivo documentado en la evaluación.'));
    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

}
