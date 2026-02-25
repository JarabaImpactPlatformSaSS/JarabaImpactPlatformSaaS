<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing candidate skills.
 */
class CandidateSkillForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'skill' => [
        'label' => $this->t('Skill'),
        'icon' => ['category' => 'ui', 'name' => 'award'],
        'description' => $this->t('Skill details and proficiency level.'),
        'fields' => ['user_id', 'skill_id', 'level', 'years_experience'],
      ],
      'verification' => [
        'label' => $this->t('Verification'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'description' => $this->t('Verification status and source.'),
        'fields' => ['is_verified', 'verified_at', 'source'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'award'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
