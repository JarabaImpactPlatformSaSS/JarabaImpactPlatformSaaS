<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form controller for Collaboration Group edit forms.
 */
class CollaborationGroupForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identity' => [
        'label' => $this->t('Identity'),
        'icon' => ['category' => 'users', 'name' => 'group'],
        'description' => $this->t('Group name, type, and cover image.'),
        'fields' => ['name', 'description', 'group_type', 'image'],
      ],
      'classification' => [
        'label' => $this->t('Classification'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Sector, territory, and program edition.'),
        'fields' => ['sector', 'territory', 'program_edition_id'],
      ],
      'access' => [
        'label' => $this->t('Access'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Visibility, join policy, and member limits.'),
        'fields' => ['visibility', 'join_policy', 'max_members'],
      ],
      'schedule' => [
        'label' => $this->t('Schedule'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Start and end dates for the group.'),
        'fields' => ['start_date', 'end_date'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Group status and featured flag.'),
        'fields' => ['status', 'is_featured', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'users', 'name' => 'group'];
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
