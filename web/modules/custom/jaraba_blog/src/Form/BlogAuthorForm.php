<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing blog authors.
 */
class BlogAuthorForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'profile' => [
        'label' => $this->t('Profile'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Author profile information.'),
        'fields' => ['user_id', 'display_name', 'slug', 'bio', 'avatar'],
      ],
      'social' => [
        'label' => $this->t('Social'),
        'icon' => ['category' => 'social', 'name' => 'share'],
        'description' => $this->t('Social media and web presence.'),
        'fields' => ['social_twitter', 'social_linkedin', 'social_website'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Activation and tenant settings.'),
        'fields' => ['is_active', 'tenant_id'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'user'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
