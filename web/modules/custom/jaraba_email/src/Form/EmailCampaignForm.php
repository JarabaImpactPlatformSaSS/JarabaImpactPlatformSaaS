<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form handler for Email Campaign entities.
 */
class EmailCampaignForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'ui', 'name' => 'mail'],
        'description' => $this->t('Campaign name, type, status and template.'),
        'fields' => ['name', 'type', 'status', 'template_id'],
      ],
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Email subject, preview text and body content.'),
        'fields' => ['subject_line', 'preview_text', 'body_html'],
      ],
      'sender' => [
        'label' => $this->t('Sender'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Sender name, email address and reply-to.'),
        'fields' => ['from_name', 'from_email', 'reply_to'],
      ],
      'audience' => [
        'label' => $this->t('Audience'),
        'icon' => ['category' => 'social', 'name' => 'group'],
        'description' => $this->t('Target lists and scheduling.'),
        'fields' => ['list_ids', 'scheduled_at'],
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
