<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creación/edición de Informes Personalizados.
 *
 * PROPÓSITO:
 * Permite crear y editar informes personalizados de analytics con
 * configuración de métricas, filtros, rango de fechas y programación.
 *
 * LÓGICA:
 * - Grupo 1: Configuración General (nombre, tenant, tipo de informe).
 * - Grupo 2: Datos (métricas, filtros, rango de fechas).
 * - Grupo 3: Programación (schedule, recipients).
 */
class CustomReportForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Grupo: Configuración General.
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración General'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    if (isset($form['name'])) {
      $form['name']['#group'] = 'general';
    }
    if (isset($form['tenant_id'])) {
      $form['tenant_id']['#group'] = 'general';
    }
    if (isset($form['report_type'])) {
      $form['report_type']['#group'] = 'general';
    }

    // Grupo: Datos.
    $form['data'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    if (isset($form['metrics'])) {
      $form['metrics']['#group'] = 'data';
      $form['metrics']['widget'][0]['value']['#description'] = $this->t(
        'JSON array de métricas a incluir. Ejemplo: ["pageviews", "sessions", "bounce_rate", "avg_duration"]'
      );
    }
    if (isset($form['filters'])) {
      $form['filters']['#group'] = 'data';
      $form['filters']['widget'][0]['value']['#description'] = $this->t(
        'JSON con filtros del informe. Ejemplo: {"event_type": "page_view", "country": "ES"}'
      );
    }
    if (isset($form['date_range'])) {
      $form['date_range']['#group'] = 'data';
    }

    // Grupo: Programación.
    $form['scheduling'] = [
      '#type' => 'details',
      '#title' => $this->t('Programación'),
      '#open' => TRUE,
      '#weight' => 20,
    ];

    if (isset($form['schedule'])) {
      $form['schedule']['#group'] = 'scheduling';
    }
    if (isset($form['recipients'])) {
      $form['recipients']['#group'] = 'scheduling';
      $form['recipients']['widget'][0]['value']['#description'] = $this->t(
        'Direcciones de email separadas por coma para recibir el informe programado.'
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\jaraba_analytics\Entity\CustomReport $entity */
    $entity = $this->getEntity();

    $result = parent::save($form, $form_state);

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Informe "%name" creado correctamente.', [
        '%name' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Informe "%name" actualizado correctamente.', [
        '%name' => $entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
