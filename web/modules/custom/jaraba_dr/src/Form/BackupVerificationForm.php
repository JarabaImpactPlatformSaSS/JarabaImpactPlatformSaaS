<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for Backup Verification entities.
 */
class BackupVerificationForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'backup' => [
        'label' => $this->t('Backup'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'fields' => ['backup_type', 'backup_path', 'file_size_bytes'],
      ],
      'verification' => [
        'label' => $this->t('Verification'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'fields' => ['checksum_expected', 'checksum_actual', 'verified_at', 'verification_duration_ms'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'fields' => ['status', 'error_message'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'shield'];
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
