<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form handler for Email Subscriber entities.
 */
class EmailSubscriberForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'contact' => [
        'label' => $this->t('Contact'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Subscriber email and personal information.'),
        'fields' => ['email', 'first_name', 'last_name'],
      ],
      'subscription' => [
        'label' => $this->t('Subscription'),
        'icon' => ['category' => 'ui', 'name' => 'mail'],
        'description' => $this->t('Status and source of the subscription.'),
        'fields' => ['status', 'source'],
      ],
      'gdpr' => [
        'label' => $this->t('GDPR'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('GDPR consent and compliance information.'),
        'fields' => ['gdpr_consent'],
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
