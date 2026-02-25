<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar audiencias sincronizadas.
 */
class AdsAudienceSyncForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Basic audience identification and platform settings.'),
        'fields' => ['tenant_id', 'account_id', 'audience_name', 'platform'],
      ],
      'source' => [
        'label' => $this->t('Data Source'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Configure the data source for audience synchronization.'),
        'fields' => ['source_type', 'source_config', 'member_count'],
      ],
      'sync_status' => [
        'label' => $this->t('Sync Status'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Synchronization state and external identifiers.'),
        'fields' => ['external_audience_id', 'sync_status', 'last_synced_at', 'sync_error'],
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
