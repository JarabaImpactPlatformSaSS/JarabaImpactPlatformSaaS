<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form handler for Email Sequence entities.
 */
class EmailSequenceForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'ui', 'name' => 'mail'],
        'description' => $this->t('Sequence name, description, category and vertical.'),
        'fields' => ['name', 'description', 'category', 'vertical'],
      ],
      'trigger' => [
        'label' => $this->t('Trigger'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Trigger type and configuration for the sequence.'),
        'fields' => ['trigger_type', 'trigger_config'],
      ],
      'settings' => [
        'label' => $this->t('Settings'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Activation and system settings.'),
        'fields' => ['is_active'],
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
