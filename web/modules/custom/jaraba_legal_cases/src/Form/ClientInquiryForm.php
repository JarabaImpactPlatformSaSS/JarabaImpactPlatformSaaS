<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing client inquiries.
 */
class ClientInquiryForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'inquiry' => [
        'label' => $this->t('Inquiry'),
        'icon' => ['category' => 'ui', 'name' => 'message'],
        'description' => $this->t('Subject and description.'),
        'fields' => ['subject', 'inquiry_number', 'description', 'case_type_requested', 'source'],
      ],
      'client' => [
        'label' => $this->t('Client'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Client contact information.'),
        'fields' => ['client_name', 'client_email', 'client_phone'],
      ],
      'management' => [
        'label' => $this->t('Management'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Status, assignment, and conversion.'),
        'fields' => ['status', 'priority', 'assigned_to', 'converted_to_case_id', 'tenant_id', 'notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'message'];
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
