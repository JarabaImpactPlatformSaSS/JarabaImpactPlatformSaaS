<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing mentoring packages.
 */
class MentoringPackageForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'package' => [
        'label' => $this->t('Package'),
        'icon' => ['category' => 'ui', 'name' => 'package'],
        'description' => $this->t('Package identity and type.'),
        'fields' => ['mentor_id', 'title', 'description', 'package_type'],
      ],
      'sessions' => [
        'label' => $this->t('Sessions'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Session configuration and async support.'),
        'fields' => ['sessions_included', 'session_duration_minutes', 'includes_async_support', 'async_response_hours'],
      ],
      'pricing' => [
        'label' => $this->t('Pricing'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Price and discount settings.'),
        'fields' => ['price', 'discount_percent'],
      ],
      'publishing' => [
        'label' => $this->t('Publishing'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Publication and featured status.'),
        'fields' => ['is_published', 'is_featured', 'total_sold'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'package'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
