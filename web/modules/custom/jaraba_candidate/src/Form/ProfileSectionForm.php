<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing profile sections.
 *
 * Handles candidate_education, candidate_experience, and candidate_language entities.
 */
class ProfileSectionForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'section' => [
        'label' => $this->t('Section'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Profile section details.'),
        'fields' => ['user_id', 'institution', 'degree', 'field_of_study', 'start_date', 'end_date'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'user'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    if ($entity->isNew() && $entity->hasField('user_id') && empty($entity->get('user_id')->target_id)) {
      $entity->set('user_id', $this->currentUser()->id());
    }
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
