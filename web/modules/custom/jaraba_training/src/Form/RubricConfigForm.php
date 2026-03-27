<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jaraba_training\Service\MethodRubricService;

/**
 * Formulario de configuración de la rúbrica del Método Jaraba.
 *
 * CERT-15: Panel admin para editar indicadores de cada nivel de cada
 * competencia sin tocar código. Almacenados en Drupal config.
 *
 * Ruta: /admin/structure/training/rubric
 */
class RubricConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_training.rubric_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_training_rubric_config';
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_training.rubric_config');

    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configure los indicadores observables para cada competencia y nivel de la rúbrica del Método Jaraba. Estos indicadores se muestran al evaluador durante la evaluación del portfolio.') . '</p>',
    ];

    foreach (MethodRubricService::COMPETENCIES as $comp) {
      $form[$comp] = [
        '#type' => 'details',
        '#title' => $this->t('Competencia: @comp', ['@comp' => ucfirst($comp)]),
        '#open' => FALSE,
      ];

      foreach (MethodRubricService::LEVELS as $level => $levelName) {
        $key = "indicators.$comp.$level";
        $current = $config->get($key);
        $default = is_array($current) ? implode("\n", $current) : '';

        $form[$comp][$comp . '_level_' . $level] = [
          '#type' => 'textarea',
          '#title' => $this->t('Nivel @level — @name', [
            '@level' => $level,
            '@name' => ucfirst($levelName),
          ]),
          '#default_value' => $default,
          '#description' => $this->t('Un indicador por línea.'),
          '#rows' => 4,
        ];
      }
    }

    // Configuración de pricing (CERT-16).
    $form['pricing'] = [
      '#type' => 'details',
      '#title' => $this->t('Precios de certificación'),
      '#open' => FALSE,
    ];

    $form['pricing']['free_for_public_programs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Gratuito para participantes de programas públicos (FSE+, PIIL)'),
      '#default_value' => $config->get('pricing.free_for_public_programs') ?? TRUE,
    ];

    $form['pricing']['profesional_price'] = [
      '#type' => 'number',
      '#title' => $this->t('Precio certificación Profesional (€)'),
      '#default_value' => $config->get('pricing.profesional_price') ?? 0,
      '#min' => 0,
      '#step' => 1,
    ];

    $form['pricing']['especialista_price'] = [
      '#type' => 'number',
      '#title' => $this->t('Precio certificación Especialista (€)'),
      '#default_value' => $config->get('pricing.especialista_price') ?? 0,
      '#min' => 0,
      '#step' => 1,
    ];

    $form['pricing']['formador_price'] = [
      '#type' => 'number',
      '#title' => $this->t('Precio certificación Formador (€)'),
      '#default_value' => $config->get('pricing.formador_price') ?? 0,
      '#min' => 0,
      '#step' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('jaraba_training.rubric_config');

    // Guardar indicadores por competencia y nivel.
    foreach (MethodRubricService::COMPETENCIES as $comp) {
      foreach (MethodRubricService::LEVELS as $level => $levelName) {
        $value = $form_state->getValue($comp . '_level_' . $level) ?? '';
        $indicators = array_filter(array_map('trim', explode("\n", $value)), static fn(string $v): bool => $v !== '');
        $config->set("indicators.$comp.$level", array_values($indicators));
      }
    }

    // Guardar pricing.
    $config->set('pricing.free_for_public_programs', (bool) $form_state->getValue('free_for_public_programs'));
    $config->set('pricing.profesional_price', (int) $form_state->getValue('profesional_price'));
    $config->set('pricing.especialista_price', (int) $form_state->getValue('especialista_price'));
    $config->set('pricing.formador_price', (int) $form_state->getValue('formador_price'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
