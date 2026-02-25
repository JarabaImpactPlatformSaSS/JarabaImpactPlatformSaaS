<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creación/edición de definiciones de cohorte.
 *
 * PROPÓSITO:
 * Permite crear y editar definiciones de cohortes para análisis de retención.
 * Organiza campos en secciones: información básica, rango temporal y filtros.
 */
class CohortDefinitionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic_info' => [
        'label' => $this->t('Basic Information'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Name, tenant, and cohort type.'),
        'fields' => ['name', 'tenant_id', 'cohort_type'],
      ],
      'date_range' => [
        'label' => $this->t('Date Range'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Start and end dates for the cohort analysis period.'),
        'fields' => ['date_range_start', 'date_range_end'],
      ],
      'filters' => [
        'label' => $this->t('Advanced Filters'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Additional JSON filter criteria for cohort member selection.'),
        'fields' => ['filters_json'],
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\jaraba_analytics\Entity\CohortDefinition $entity */
    $entity = $this->entity;

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
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
