<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing secure documents.
 */
class SecureDocumentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'document' => [
        'label' => $this->t('Document'),
        'icon' => ['category' => 'ui', 'name' => 'lock'],
        'description' => $this->t('Title, description, and file.'),
        'fields' => ['title', 'description', 'original_filename', 'mime_type', 'file_size', 'storage_path'],
      ],
      'classification' => [
        'label' => $this->t('Classification'),
        'icon' => ['category' => 'ui', 'name' => 'tag'],
        'description' => $this->t('Category, case, and ownership.'),
        'fields' => ['category_tid', 'case_id', 'owner_id', 'tenant_id'],
      ],
      'encryption' => [
        'label' => $this->t('Encryption'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Encryption keys and hash.'),
        'fields' => ['content_hash', 'encrypted_dek', 'encryption_iv', 'encryption_tag'],
      ],
      'versioning' => [
        'label' => $this->t('Versioning'),
        'icon' => ['category' => 'ui', 'name' => 'layers'],
        'description' => $this->t('Version tracking.'),
        'fields' => ['version', 'parent_version_id', 'is_signed', 'expires_at', 'status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'lock'];
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
