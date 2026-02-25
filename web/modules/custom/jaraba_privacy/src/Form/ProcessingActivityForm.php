<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para ProcessingActivity (RAT).
 *
 * Permite crear y editar actividades de tratamiento del Registro
 * de Actividades de Tratamiento (RGPD Art. 30).
 */
class ProcessingActivityForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Activity name, purpose, and legal basis.'),
        'fields' => ['tenant_id', 'activity_name', 'purpose', 'legal_basis'],
      ],
      'data' => [
        'label' => $this->t('Data Treated'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Data categories, subjects, and recipients.'),
        'fields' => ['data_categories', 'data_subjects', 'recipients'],
      ],
      'transfers' => [
        'label' => $this->t('Transfers & Retention'),
        'icon' => ['category' => 'business', 'name' => 'transfer'],
        'description' => $this->t('International transfers, retention, and security.'),
        'fields' => ['international_transfers', 'retention_period', 'security_measures'],
      ],
      'dpia' => [
        'label' => $this->t('DPIA'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Data Protection Impact Assessment.'),
        'fields' => ['dpia_required', 'dpia_reference'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Vertical and active status.'),
        'fields' => ['vertical', 'is_active'],
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
