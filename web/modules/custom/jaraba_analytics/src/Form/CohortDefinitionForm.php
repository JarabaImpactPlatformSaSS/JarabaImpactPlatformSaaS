<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creación/edición de definiciones de cohorte.
 *
 * PROPÓSITO:
 * Permite crear y editar definiciones de cohortes para análisis de retención.
 * Organiza campos en grupos lógicos: información básica, rango temporal y filtros.
 *
 * LÓGICA:
 * - Grupo 1: Información básica (nombre, tenant, tipo de cohorte).
 * - Grupo 2: Rango temporal (fecha inicio, fecha fin).
 * - Grupo 3: Filtros avanzados (JSON con criterios adicionales).
 */
class CohortDefinitionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\jaraba_analytics\Entity\CohortDefinition $entity */
    $entity = $this->entity;

    // Grupo: Información básica.
    $form['basic_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic Information'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    if (isset($form['name'])) {
      $form['name']['#group'] = 'basic_info';
    }
    if (isset($form['tenant_id'])) {
      $form['tenant_id']['#group'] = 'basic_info';
    }
    if (isset($form['cohort_type'])) {
      $form['cohort_type']['#group'] = 'basic_info';
    }

    // Grupo: Rango temporal.
    $form['date_range_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Date Range'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    if (isset($form['date_range_start'])) {
      $form['date_range_start']['#group'] = 'date_range_group';
    }
    if (isset($form['date_range_end'])) {
      $form['date_range_end']['#group'] = 'date_range_group';
    }

    // Grupo: Filtros avanzados.
    $form['filters_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Filters'),
      '#open' => FALSE,
      '#weight' => 20,
    ];

    // Filters field: override the map widget with a textarea for JSON input.
    $currentFilters = $entity->getFilters();
    $filtersJson = !empty($currentFilters) ? json_encode($currentFilters, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '';

    // Remove auto-generated map widget if present.
    unset($form['filters']);

    $form['filters_json'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Filters (JSON)'),
      '#description' => $this->t('Optional JSON object with additional filter criteria. Example: {"vertical": "empleabilidad", "plan": "profesional"}'),
      '#default_value' => $filtersJson,
      '#rows' => 5,
      '#group' => 'filters_group',
      '#weight' => 25,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate JSON filters.
    $filtersJson = $form_state->getValue('filters_json');
    if (!empty($filtersJson)) {
      $decoded = json_decode($filtersJson, TRUE);
      if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('filters_json', $this->t('Filters must be valid JSON.'));
      }
      elseif (!is_array($decoded)) {
        $form_state->setErrorByName('filters_json', $this->t('Filters must be a JSON object.'));
      }
    }

    // Validate date range consistency.
    $startDate = $form_state->getValue(['date_range_start', 0, 'value']);
    $endDate = $form_state->getValue(['date_range_end', 0, 'value']);
    if ($startDate && $endDate && $startDate > $endDate) {
      $form_state->setErrorByName('date_range_end', $this->t('End date must be after start date.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\jaraba_analytics\Entity\CohortDefinition $entity */
    $entity = $this->getEntity();

    // Process JSON filters into the map field.
    $filtersJson = $form_state->getValue('filters_json');
    if (!empty($filtersJson)) {
      $decoded = json_decode($filtersJson, TRUE);
      if (is_array($decoded)) {
        $entity->set('filters', $decoded);
      }
    }
    else {
      $entity->set('filters', []);
    }

    $result = parent::save($form, $form_state);

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Cohort %name created.', [
        '%name' => $entity->getName(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Cohort %name updated.', [
        '%name' => $entity->getName(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
