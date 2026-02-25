<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creacion/edicion de informes programados.
 *
 * PROPOSITO:
 * Permite crear y editar informes programados con configuracion de query,
 * programacion temporal y lista de destinatarios.
 */
class ScheduledReportForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic_info' => [
        'label' => $this->t('Basic Information'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Report name, owner, tenant, and status.'),
        'fields' => ['name', 'owner_id', 'tenant_id', 'report_status'],
      ],
      'report_config' => [
        'label' => $this->t('Report Configuration'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('JSON query and format configuration for the report.'),
        'fields' => ['report_config'],
      ],
      'schedule' => [
        'label' => $this->t('Schedule'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Schedule type and timing configuration.'),
        'fields' => ['schedule_type', 'schedule_config'],
      ],
      'recipients' => [
        'label' => $this->t('Recipients'),
        'icon' => ['category' => 'users', 'name' => 'users'],
        'description' => $this->t('List of email recipients for the scheduled report.'),
        'fields' => ['recipients'],
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

    // Validate report_config JSON.
    $reportConfig = $form_state->getValue(['report_config', 0, 'value']);
    if (!empty($reportConfig)) {
      $decoded = json_decode($reportConfig, TRUE);
      if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('report_config', $this->t('Report configuration must be valid JSON.'));
      }
    }

    // Validate schedule_config JSON.
    $scheduleConfig = $form_state->getValue(['schedule_config', 0, 'value']);
    if (!empty($scheduleConfig)) {
      $decoded = json_decode($scheduleConfig, TRUE);
      if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('schedule_config', $this->t('Schedule configuration must be valid JSON.'));
      }
    }

    // Validate recipients JSON.
    $recipients = $form_state->getValue(['recipients', 0, 'value']);
    if (!empty($recipients)) {
      $decoded = json_decode($recipients, TRUE);
      if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('recipients', $this->t('Recipients must be a valid JSON array.'));
      }
      elseif (!is_array($decoded)) {
        $form_state->setErrorByName('recipients', $this->t('Recipients must be a JSON array of email addresses.'));
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
