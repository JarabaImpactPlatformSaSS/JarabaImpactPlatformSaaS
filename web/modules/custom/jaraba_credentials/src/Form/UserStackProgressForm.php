<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar UserStackProgress.
 */
class UserStackProgressForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'ui', 'name' => 'award'],
        'description' => $this->t('Stack and user references.'),
        'fields' => ['stack_id', 'user_id'],
      ],
      'progress' => [
        'label' => $this->t('Progress'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Completed templates and progress percentage.'),
        'fields' => ['completed_templates', 'progress_percent', 'status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'award'];
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
