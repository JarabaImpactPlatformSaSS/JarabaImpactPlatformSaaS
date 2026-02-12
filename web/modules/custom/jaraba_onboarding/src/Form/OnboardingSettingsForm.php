<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion general del sistema de onboarding.
 */
class OnboardingSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_onboarding.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_onboarding_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_onboarding.settings');

    $form['enable_onboarding'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar onboarding'),
      '#description' => $this->t('Activa el sistema de onboarding guiado para nuevos usuarios.'),
      '#default_value' => $config->get('enable_onboarding') ?? TRUE,
    ];

    $form['enable_gamification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar gamificacion'),
      '#description' => $this->t('Activa recompensas y logros al completar pasos de onboarding.'),
      '#default_value' => $config->get('enable_gamification') ?? TRUE,
    ];

    $form['enable_tours'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar tours guiados'),
      '#description' => $this->t('Activa los tours contextuales en las paginas del frontend.'),
      '#default_value' => $config->get('enable_tours') ?? TRUE,
    ];

    $form['auto_assign_template'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-asignar template por vertical'),
      '#description' => $this->t('Asigna automaticamente el template de onboarding basado en la vertical del tenant.'),
      '#default_value' => $config->get('auto_assign_template') ?? TRUE,
    ];

    $form['celebration_confetti'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Celebracion con confeti'),
      '#description' => $this->t('Muestra animacion de confeti al completar un paso de onboarding.'),
      '#default_value' => $config->get('celebration_confetti') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_onboarding.settings')
      ->set('enable_onboarding', (bool) $form_state->getValue('enable_onboarding'))
      ->set('enable_gamification', (bool) $form_state->getValue('enable_gamification'))
      ->set('enable_tours', (bool) $form_state->getValue('enable_tours'))
      ->set('auto_assign_template', (bool) $form_state->getValue('auto_assign_template'))
      ->set('celebration_confetti', (bool) $form_state->getValue('celebration_confetti'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
