<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar eventos de conversion offline.
 */
class AdsConversionEventForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Event identification and platform settings.'),
        'fields' => ['tenant_id', 'account_id', 'platform', 'event_name', 'event_time'],
      ],
      'matching' => [
        'label' => $this->t('User Matching'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Hashed user data for platform matching.'),
        'fields' => ['email_hash', 'phone_hash'],
      ],
      'conversion' => [
        'label' => $this->t('Conversion Data'),
        'icon' => ['category' => 'commerce', 'name' => 'wallet'],
        'description' => $this->t('Conversion value and order details.'),
        'fields' => ['conversion_value', 'currency', 'order_id'],
      ],
      'upload' => [
        'label' => $this->t('Upload Status'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Platform upload state and external references.'),
        'fields' => ['external_event_id', 'upload_status', 'upload_error'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart'];
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
