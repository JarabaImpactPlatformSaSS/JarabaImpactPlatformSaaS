<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form controller for Group Discussion forms.
 */
class GroupDiscussionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'message'],
        'description' => $this->t('Discussion title, body, and category.'),
        'fields' => ['group_id', 'title', 'body', 'category'],
      ],
      'moderation' => [
        'label' => $this->t('Moderation'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Pinned, locked, and status settings.'),
        'fields' => ['is_pinned', 'is_locked', 'status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'message'];
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
