<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing tenant export records.
 */
class TenantExportRecordForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'export' => [
        'label' => $this->t('Export'),
        'icon' => ['category' => 'actions', 'name' => 'download'],
        'description' => $this->t('Export configuration and target.'),
        'fields' => ['tenant_id', 'tenant_entity_id', 'requested_by', 'export_type', 'requested_sections'],
      ],
      'result' => [
        'label' => $this->t('Result'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Export output and file details.'),
        'fields' => ['file_path', 'file_size', 'file_hash', 'section_counts', 'error_message'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Progress and download info.'),
        'fields' => ['status', 'progress', 'current_phase', 'expires_at', 'download_token', 'download_count', 'completed_at'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'actions', 'name' => 'download'];
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
