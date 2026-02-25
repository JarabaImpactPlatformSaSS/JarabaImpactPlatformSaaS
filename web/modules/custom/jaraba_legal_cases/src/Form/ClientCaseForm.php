<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing client cases.
 */
class ClientCaseForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'case_info' => [
        'label' => $this->t('Case Information'),
        'icon' => ['category' => 'ui', 'name' => 'scale'],
        'description' => $this->t('Case title, type, and description.'),
        'fields' => ['title', 'case_number', 'status', 'priority', 'case_type', 'description'],
      ],
      'client_info' => [
        'label' => $this->t('Client Data'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Client contact information.'),
        'fields' => ['client_name', 'client_email', 'client_phone', 'client_nif'],
      ],
      'judicial_info' => [
        'label' => $this->t('Judicial Data'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('Court, dates, and opposing party.'),
        'fields' => ['court_name', 'court_number', 'filing_date', 'next_deadline', 'opposing_party', 'estimated_value'],
      ],
      'assignment' => [
        'label' => $this->t('Assignment'),
        'icon' => ['category' => 'users', 'name' => 'group'],
        'description' => $this->t('Assigned lawyer, area, and tenant.'),
        'fields' => ['assigned_to', 'legal_area', 'tenant_id', 'notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'scale'];
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
