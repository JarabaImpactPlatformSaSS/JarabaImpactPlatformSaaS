<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for Funding Opportunity entities.
 */
class FundingOpportunityForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'fields' => ['name', 'funding_body', 'program'],
      ],
      'financial' => [
        'label' => $this->t('Financial'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'fields' => ['max_amount'],
      ],
      'dates' => [
        'label' => $this->t('Dates'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'fields' => ['deadline', 'alert_days_before'],
      ],
      'requirements' => [
        'label' => $this->t('Requirements'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'fields' => ['requirements', 'documentation_required', 'url'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'fields' => ['status', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'fiscal', 'name' => 'coins'];
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
