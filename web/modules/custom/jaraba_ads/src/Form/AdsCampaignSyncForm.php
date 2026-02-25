<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar campanas sincronizadas.
 */
class AdsCampaignSyncForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Campaign identification and account settings.'),
        'fields' => ['tenant_id', 'account_id', 'external_campaign_id', 'campaign_name', 'campaign_type', 'status'],
      ],
      'budget' => [
        'label' => $this->t('Budget'),
        'icon' => ['category' => 'commerce', 'name' => 'wallet'],
        'description' => $this->t('Budget configuration and currency settings.'),
        'fields' => ['daily_budget', 'lifetime_budget', 'currency'],
      ],
      'schedule' => [
        'label' => $this->t('Schedule'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Campaign schedule and targeting configuration.'),
        'fields' => ['start_date', 'end_date', 'objective', 'targeting_summary'],
      ],
      'sync' => [
        'label' => $this->t('Synchronization'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Last synchronization details.'),
        'fields' => ['last_synced_at'],
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
