<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar metricas diarias de ads.
 */
class AdsMetricsDailyForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Campaign reference and date for these metrics.'),
        'fields' => ['tenant_id', 'campaign_id', 'metrics_date'],
      ],
      'engagement' => [
        'label' => $this->t('Engagement'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Impressions, clicks, conversions and reach.'),
        'fields' => ['impressions', 'clicks', 'conversions', 'reach', 'frequency'],
      ],
      'financial' => [
        'label' => $this->t('Financial'),
        'icon' => ['category' => 'commerce', 'name' => 'wallet'],
        'description' => $this->t('Spend, revenue and cost metrics.'),
        'fields' => ['spend', 'revenue'],
      ],
      'rates' => [
        'label' => $this->t('Rates'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Calculated rate metrics (CTR, CPC, CPA, ROAS).'),
        'fields' => ['ctr', 'cpc', 'cpa', 'roas'],
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
