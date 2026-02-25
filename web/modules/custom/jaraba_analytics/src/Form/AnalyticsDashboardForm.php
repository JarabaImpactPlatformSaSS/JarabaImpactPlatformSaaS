<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creacion/edicion de dashboards de analytics.
 */
class AnalyticsDashboardForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic_info' => [
        'label' => $this->t('Basic Information'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Dashboard name, description, owner and tenant.'),
        'fields' => ['name', 'description', 'owner_id', 'tenant_id'],
      ],
      'layout' => [
        'label' => $this->t('Layout Configuration'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('JSON grid layout configuration.'),
        'fields' => ['layout_config'],
      ],
      'options' => [
        'label' => $this->t('Options'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Dashboard status, default and sharing options.'),
        'fields' => ['dashboard_status', 'is_default', 'is_shared'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate layout_config JSON if provided.
    $layoutConfig = $form_state->getValue(['layout_config', 0, 'value']);
    if (!empty($layoutConfig)) {
      $decoded = json_decode($layoutConfig, TRUE);
      if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('layout_config', $this->t('Layout configuration must be valid JSON.'));
      }
    }
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
