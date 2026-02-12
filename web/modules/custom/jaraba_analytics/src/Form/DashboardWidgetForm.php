<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creacion/edicion de widgets de dashboard.
 *
 * PROPOSITO:
 * Permite crear y editar widgets con tipo de visualizacion, fuente de datos,
 * configuracion de consulta, posicion y configuracion de display.
 *
 * LOGICA:
 * - Grupo 1: Informacion basica (nombre, tipo, fuente de datos, dashboard).
 * - Grupo 2: Configuracion de datos (query JSON, display JSON).
 * - Grupo 3: Layout (posicion, estado).
 */
class DashboardWidgetForm extends ContentEntityForm {

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
    if (isset($form['widget_type'])) {
      $form['widget_type']['#group'] = 'basic_info';
    }
    if (isset($form['data_source'])) {
      $form['data_source']['#group'] = 'basic_info';
    }
    if (isset($form['dashboard_id'])) {
      $form['dashboard_id']['#group'] = 'basic_info';
    }

    // Grupo: Configuracion de datos.
    $form['data_config_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Data Configuration'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    if (isset($form['query_config'])) {
      $form['query_config']['#group'] = 'data_config_group';
      $form['query_config']['widget'][0]['value']['#description'] = $this->t('JSON query config. Example: {"metric": "page_views", "dimensions": ["date"], "filters": {}, "date_range": "last_30_days"}');
    }
    if (isset($form['display_config'])) {
      $form['display_config']['#group'] = 'data_config_group';
      $form['display_config']['widget'][0]['value']['#description'] = $this->t('JSON display config. Example: {"colors": ["#0d6efd", "#198754"], "labels": true, "format": "number"}');
    }

    // Grupo: Layout.
    $form['layout_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout'),
      '#open' => TRUE,
      '#weight' => 20,
    ];

    if (isset($form['position'])) {
      $form['position']['#group'] = 'layout_group';
    }
    if (isset($form['widget_status'])) {
      $form['widget_status']['#group'] = 'layout_group';
    }

    return $form;
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
    /** @var \Drupal\jaraba_analytics\Entity\DashboardWidget $entity */
    $entity = $this->getEntity();

    $result = parent::save($form, $form_state);

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Widget %name created.', [
        '%name' => $entity->getName(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Widget %name updated.', [
        '%name' => $entity->getName(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
