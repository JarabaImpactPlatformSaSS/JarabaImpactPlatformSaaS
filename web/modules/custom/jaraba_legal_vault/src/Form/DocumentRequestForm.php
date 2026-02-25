<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing document requests.
 */
class DocumentRequestForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'request' => [
        'label' => $this->t('Request'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Request title and instructions.'),
        'fields' => ['title', 'document_type_tid', 'case_id', 'instructions', 'is_required', 'deadline'],
      ],
      'review' => [
        'label' => $this->t('Review'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'description' => $this->t('Upload and review status.'),
        'fields' => ['uploaded_document_id', 'reviewed_by', 'rejection_reason', 'reminder_count'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Request status and tenant.'),
        'fields' => ['status', 'tenant_id'],
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
