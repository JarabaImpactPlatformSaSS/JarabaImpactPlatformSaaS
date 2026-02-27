<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form controller for the ContentAuthor entity.
 *
 * Extends PremiumEntityFormBase to get glassmorphism sections, pill
 * navigation, character counters, dirty-state tracking, and progress bar.
 *
 * PREMIUM-FORMS-PATTERN-001: Implements getSectionDefinitions() + getFormIcon().
 */
class ContentAuthorForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'profile' => [
        'label' => $this->t('Profile'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Display name, slug, biography, and avatar.'),
        'fields' => ['display_name', 'slug', 'bio', 'avatar', 'user_id'],
      ],
      'social' => [
        'label' => $this->t('Social'),
        'icon' => ['category' => 'ui', 'name' => 'share'],
        'description' => $this->t('Social media profiles and website.'),
        'fields' => ['social_twitter', 'social_linkedin', 'social_website'],
      ],
      'settings' => [
        'label' => $this->t('Settings'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Active status and tenant.'),
        'fields' => ['is_active', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'user'];
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
