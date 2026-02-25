<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing LexNET submissions.
 */
class LexnetSubmissionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'submission' => [
        'label' => $this->t('Submission'),
        'icon' => ['category' => 'ui', 'name' => 'send'],
        'description' => $this->t('Submission type, court, and subject.'),
        'fields' => ['submission_type', 'court', 'procedure_number', 'subject', 'case_id'],
      ],
      'documents' => [
        'label' => $this->t('Documents'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Attached documents.'),
        'fields' => ['document_ids'],
      ],
      'response' => [
        'label' => $this->t('Response'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'description' => $this->t('Submission confirmation and errors.'),
        'fields' => ['submitted_at', 'confirmation_id', 'error_message', 'raw_response'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Submission status and tenant.'),
        'fields' => ['status', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'send'];
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
