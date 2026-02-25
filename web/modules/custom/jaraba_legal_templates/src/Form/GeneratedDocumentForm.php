<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing generated documents.
 */
class GeneratedDocumentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'document' => [
        'label' => $this->t('Document'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Document title and content.'),
        'fields' => ['title', 'template_id', 'case_id', 'content_html'],
      ],
      'generation' => [
        'label' => $this->t('Generation'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'description' => $this->t('Generation details and AI data.'),
        'fields' => ['generated_by', 'generation_mode', 'ai_model_version', 'merge_data', 'citations_used'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Document status and vault link.'),
        'fields' => ['status', 'vault_document_id', 'tenant_id'],
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
