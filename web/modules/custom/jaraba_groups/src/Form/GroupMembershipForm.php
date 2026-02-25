<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form controller for Group Membership forms.
 */
class GroupMembershipForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'membership' => [
        'label' => $this->t('Membership'),
        'icon' => ['category' => 'users', 'name' => 'group'],
        'description' => $this->t('Group, role, and membership status.'),
        'fields' => ['group_id', 'user_id', 'role', 'status'],
      ],
      'details' => [
        'label' => $this->t('Details'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Additional membership information.'),
        'fields' => ['invited_by', 'notes'],
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
