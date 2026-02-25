<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing mentor profiles.
 */
class MentorProfileForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'profile' => [
        'label' => $this->t('Profile'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Mentor identity and biography.'),
        'fields' => ['user_id', 'display_name', 'headline', 'bio', 'avatar'],
      ],
      'expertise' => [
        'label' => $this->t('Expertise'),
        'icon' => ['category' => 'education', 'name' => 'book'],
        'description' => $this->t('Specializations and certification.'),
        'fields' => ['specializations', 'sectors', 'business_stages', 'certification_level'],
      ],
      'pricing' => [
        'label' => $this->t('Pricing'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Hourly rate and currency.'),
        'fields' => ['hourly_rate', 'currency'],
      ],
      'settings' => [
        'label' => $this->t('Settings'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Availability and status settings.'),
        'fields' => ['is_available', 'status', 'tenant_id'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'education', 'name' => 'book'];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    if (!$this->currentUser()->hasPermission('administer mentoring')) {
      foreach (['stripe_account_id', 'stripe_onboarding_complete', 'platform_fee_percent'] as $field) {
        if (isset($form['premium_section_other'][$field])) {
          $form['premium_section_other'][$field]['#access'] = FALSE;
        }
      }
    }
    // Computed fields read-only.
    foreach (['average_rating', 'total_sessions', 'total_reviews'] as $field) {
      if (isset($form['premium_section_other'][$field])) {
        $form['premium_section_other'][$field]['#disabled'] = TRUE;
      }
    }
    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
