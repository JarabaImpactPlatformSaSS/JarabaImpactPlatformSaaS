<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creacion/edicion de informes programados.
 *
 * PROPOSITO:
 * Permite crear y editar informes programados con configuracion de query,
 * programacion temporal y lista de destinatarios.
 *
 * LOGICA:
 * - Grupo 1: Informacion basica (nombre, tenant, propietario, estado).
 * - Grupo 2: Configuracion del informe (query JSON, formato).
 * - Grupo 3: Programacion (tipo, configuracion de schedule).
 * - Grupo 4: Destinatarios (lista JSON de emails).
 */
class ScheduledReportForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Grupo: Informacion basica.
    $form['basic_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic Information'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    if (isset($form['name'])) {
      $form['name']['#group'] = 'basic_info';
    }
    if (isset($form['owner_id'])) {
      $form['owner_id']['#group'] = 'basic_info';
    }
    if (isset($form['tenant_id'])) {
      $form['tenant_id']['#group'] = 'basic_info';
    }
    if (isset($form['report_status'])) {
      $form['report_status']['#group'] = 'basic_info';
    }

    // Grupo: Configuracion del informe.
    $form['report_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Report Configuration'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    if (isset($form['report_config'])) {
      $form['report_config']['#group'] = 'report_group';
      $form['report_config']['widget'][0]['value']['#description'] = $this->t('JSON report query. Example: {"metric": "page_views", "filters": {"date_range": "last_30_days"}, "format": "csv"}');
    }

    // Grupo: Programacion.
    $form['schedule_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Schedule'),
      '#open' => TRUE,
      '#weight' => 20,
    ];

    if (isset($form['schedule_type'])) {
      $form['schedule_type']['#group'] = 'schedule_group';
    }
    if (isset($form['schedule_config'])) {
      $form['schedule_config']['#group'] = 'schedule_group';
      $form['schedule_config']['widget'][0]['value']['#description'] = $this->t('JSON schedule details. Example: {"day_of_week": "monday", "time": "08:00", "timezone": "Europe/Madrid"}');
    }

    // Grupo: Destinatarios.
    $form['recipients_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Recipients'),
      '#open' => TRUE,
      '#weight' => 30,
    ];

    if (isset($form['recipients'])) {
      $form['recipients']['#group'] = 'recipients_group';
      $form['recipients']['widget'][0]['value']['#description'] = $this->t('JSON array of email addresses. Example: ["admin@example.com", "team@example.com"]');
    }

    return $form;
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
    /** @var \Drupal\jaraba_analytics\Entity\ScheduledReport $entity */
    $entity = $this->getEntity();

    $result = parent::save($form, $form_state);

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Scheduled report %name created.', [
        '%name' => $entity->getName(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Scheduled report %name updated.', [
        '%name' => $entity->getName(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
