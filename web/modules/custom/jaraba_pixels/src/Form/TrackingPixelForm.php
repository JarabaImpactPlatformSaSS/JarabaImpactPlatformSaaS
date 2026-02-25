<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing tracking pixels.
 */
class TrackingPixelForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'pixel' => [
        'label' => $this->t('Pixel'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Configure the tracking pixel details.'),
        'fields' => ['name', 'platform', 'pixel_id', 'pixel_config', 'conversion_events'],
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
    return ['category' => 'analytics', 'name' => 'chart'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
