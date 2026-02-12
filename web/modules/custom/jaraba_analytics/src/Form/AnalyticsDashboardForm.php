<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creacion/edicion de dashboards de analytics.
 *
 * PROPOSITO:
 * Permite crear y editar dashboards con nombre, descripcion, layout,
 * estado y configuracion de comparticion.
 *
 * LOGICA:
 * - Grupo 1: Informacion basica (nombre, descripcion, tenant, propietario).
 * - Grupo 2: Configuracion de layout (JSON del grid).
 * - Grupo 3: Opciones (estado, default, compartido).
 */
class AnalyticsDashboardForm extends ContentEntityForm {

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
    if (isset($form['description'])) {
      $form['description']['#group'] = 'basic_info';
    }
    if (isset($form['owner_id'])) {
      $form['owner_id']['#group'] = 'basic_info';
    }
    if (isset($form['tenant_id'])) {
      $form['tenant_id']['#group'] = 'basic_info';
    }

    // Grupo: Layout.
    $form['layout_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout Configuration'),
      '#open' => FALSE,
      '#weight' => 10,
    ];

    if (isset($form['layout_config'])) {
      $form['layout_config']['#group'] = 'layout_group';
      $form['layout_config']['widget'][0]['value']['#description'] = $this->t('JSON grid layout. Example: {"columns": 12, "row_height": 80, "gap": 16}');
    }

    // Grupo: Opciones.
    $form['options_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Options'),
      '#open' => TRUE,
      '#weight' => 20,
    ];

    if (isset($form['dashboard_status'])) {
      $form['dashboard_status']['#group'] = 'options_group';
    }
    if (isset($form['is_default'])) {
      $form['is_default']['#group'] = 'options_group';
    }
    if (isset($form['is_shared'])) {
      $form['is_shared']['#group'] = 'options_group';
    }

    return $form;
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
    /** @var \Drupal\jaraba_analytics\Entity\AnalyticsDashboard $entity */
    $entity = $this->getEntity();

    $result = parent::save($form, $form_state);

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Dashboard %name created.', [
        '%name' => $entity->getName(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Dashboard %name updated.', [
        '%name' => $entity->getName(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
