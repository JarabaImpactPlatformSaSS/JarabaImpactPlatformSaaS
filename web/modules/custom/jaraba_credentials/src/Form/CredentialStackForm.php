<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar CredentialStack.
 */
class CredentialStackForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'ui', 'name' => 'award'],
        'description' => $this->t('Stack name, machine name and description.'),
        'fields' => ['name', 'machine_name', 'description'],
      ],
      'templates' => [
        'label' => $this->t('Templates'),
        'icon' => ['category' => 'education', 'name' => 'course'],
        'description' => $this->t('Required and optional credential templates.'),
        'fields' => ['result_template_id', 'required_templates', 'min_required', 'optional_templates'],
      ],
      'rewards' => [
        'label' => $this->t('Rewards'),
        'icon' => ['category' => 'ui', 'name' => 'award'],
        'description' => $this->t('Bonus credits, XP, EQF level and ECTS credits.'),
        'fields' => ['bonus_credits', 'bonus_xp', 'eqf_level', 'ects_credits'],
      ],
      'settings' => [
        'label' => $this->t('Settings'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Activation status.'),
        'fields' => ['status'],
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
