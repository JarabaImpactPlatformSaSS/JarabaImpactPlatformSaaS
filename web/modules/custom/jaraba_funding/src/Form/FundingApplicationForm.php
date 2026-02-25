<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for Funding Application entities.
 */
class FundingApplicationForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'reference' => [
        'label' => $this->t('Reference'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'fields' => ['opportunity_id', 'application_number'],
      ],
      'financial' => [
        'label' => $this->t('Financial'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'fields' => ['amount_requested', 'amount_approved'],
      ],
      'dates' => [
        'label' => $this->t('Dates'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'fields' => ['submission_date', 'resolution_date', 'next_deadline'],
      ],
      'details' => [
        'label' => $this->t('Details'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'fields' => ['budget_breakdown', 'impact_indicators', 'justification_notes', 'status'],
      ],
      'tenant' => [
        'label' => $this->t('Tenant'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'document'];
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
