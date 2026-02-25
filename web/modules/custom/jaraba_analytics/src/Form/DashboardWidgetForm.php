<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creacion/edicion de widgets de dashboard.
 */
class DashboardWidgetForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic_info' => [
        'label' => $this->t('Basic Information'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Widget name, type, data source and parent dashboard.'),
        'fields' => ['name', 'widget_type', 'data_source', 'dashboard_id'],
      ],
      'data_config' => [
        'label' => $this->t('Data Configuration'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Query and display configuration in JSON format.'),
        'fields' => ['query_config', 'display_config'],
      ],
      'layout' => [
        'label' => $this->t('Layout'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Grid position and visibility status.'),
        'fields' => ['position', 'widget_status'],
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

    // Validate query_config JSON.
    $queryConfig = $form_state->getValue(['query_config', 0, 'value']);
    if (!empty($queryConfig)) {
      $decoded = json_decode($queryConfig, TRUE);
      if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('query_config', $this->t('Query configuration must be valid JSON.'));
      }
    }

    // Validate display_config JSON.
    $displayConfig = $form_state->getValue(['display_config', 0, 'value']);
    if (!empty($displayConfig)) {
      $decoded = json_decode($displayConfig, TRUE);
      if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('display_config', $this->t('Display configuration must be valid JSON.'));
      }
    }

    // Validate position format (row:col:width:height).
    $position = $form_state->getValue(['position', 0, 'value']);
    if (!empty($position)) {
      $parts = explode(':', $position);
      if (count($parts) !== 4) {
        $form_state->setErrorByName('position', $this->t('Position must follow the format "row:col:width:height".'));
      }
      else {
        foreach ($parts as $part) {
          if (!is_numeric($part) || (int) $part < 1) {
            $form_state->setErrorByName('position', $this->t('Each position value must be a positive integer.'));
            break;
          }
        }
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
