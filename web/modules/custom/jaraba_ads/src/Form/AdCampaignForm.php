<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar campañas publicitarias.
 */
class AdCampaignForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Campaign name, platform and external identifier.'),
        'fields' => ['label', 'platform', 'campaign_id_external', 'status'],
      ],
      'budget' => [
        'label' => $this->t('Budget'),
        'icon' => ['category' => 'commerce', 'name' => 'wallet'],
        'description' => $this->t('Daily budget, total budget and accumulated spend.'),
        'fields' => ['budget_daily', 'budget_total', 'spend_to_date'],
      ],
      'metrics' => [
        'label' => $this->t('Performance Metrics'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Impressions, clicks, conversions and derived metrics.'),
        'fields' => ['impressions', 'clicks', 'conversions', 'ctr', 'cpc', 'roas'],
      ],
      'schedule' => [
        'label' => $this->t('Schedule'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'description' => $this->t('Campaign start and end dates.'),
        'fields' => ['start_date', 'end_date'],
      ],
      'ownership' => [
        'label' => $this->t('Ownership'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Owner and tenant assignment.'),
        'fields' => ['uid', 'tenant_id'],
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
    /** @var \Drupal\jaraba_ads\Entity\AdCampaign $entity */
    $entity = $this->entity;

    // Recalcular métricas derivadas si hay datos de rendimiento.
    $impressions = (int) $entity->get('impressions')->value;
    $clicks = (int) $entity->get('clicks')->value;
    if ($impressions > 0 || $clicks > 0) {
      $entity->recalculateMetrics();
    }

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
