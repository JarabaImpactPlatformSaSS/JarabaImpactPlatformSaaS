<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing social post variants.
 */
class SocialPostVariantForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'social', 'name' => 'share'],
        'description' => $this->t('Variant content and media.'),
        'fields' => ['post_id', 'variant_name', 'content', 'media_urls', 'hashtags', 'call_to_action'],
      ],
      'metrics' => [
        'label' => $this->t('Metrics'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Performance metrics.'),
        'fields' => ['impressions', 'engagements', 'clicks', 'shares', 'engagement_rate', 'is_winner'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Tenant assignment.'),
        'fields' => ['tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'social', 'name' => 'share'];
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
