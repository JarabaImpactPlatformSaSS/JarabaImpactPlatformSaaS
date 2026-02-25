<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar pasos de secuencia de email.
 */
class EmailSequenceStepForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'ui', 'name' => 'mail'],
        'description' => $this->t('Sequence, position and step type.'),
        'fields' => ['sequence_id', 'position', 'step_type'],
      ],
      'email' => [
        'label' => $this->t('Email Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Template and subject line for email steps.'),
        'fields' => ['template_id', 'subject_line'],
      ],
      'timing' => [
        'label' => $this->t('Timing'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Delay configuration for wait steps.'),
        'fields' => ['delay_value', 'delay_unit'],
      ],
      'advanced' => [
        'label' => $this->t('Advanced'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Condition and action configuration.'),
        'fields' => ['condition_config', 'action_config', 'is_active'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'mail'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
