<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar conectores del marketplace.
 *
 * PROPÓSITO:
 * Formulario administrativo para gestionar conectores.
 * Organizado en grupos lógicos: información básica, autenticación,
 * configuración técnica y publicación.
 *
 * DIRECTRICES:
 * - i18n: todos los labels con $this->t().
 * - Grupos verticales para organización visual.
 */
class ConnectorForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Grupo: Información Básica.
    $form['basic_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Información Básica'),
      '#open' => TRUE,
      '#weight' => -10,
    ];

    // Mover campos al grupo.
    $basic_fields = ['name', 'machine_name', 'description', 'category', 'icon', 'logo_url', 'provider', 'version'];
    foreach ($basic_fields as $field_name) {
      if (isset($form[$field_name])) {
        $form['basic_info'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    // Grupo: Autenticación y API.
    $form['auth_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Autenticación y API'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    $auth_fields = ['auth_type', 'api_base_url', 'docs_url'];
    foreach ($auth_fields as $field_name) {
      if (isset($form[$field_name])) {
        $form['auth_settings'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    // Grupo: Configuración Técnica.
    $form['technical'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración Técnica'),
      '#open' => FALSE,
      '#weight' => 5,
    ];

    $tech_fields = ['config_schema', 'supported_events', 'required_plans'];
    foreach ($tech_fields as $field_name) {
      if (isset($form[$field_name])) {
        $form['technical'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    // Grupo: Publicación.
    $form['publishing'] = [
      '#type' => 'details',
      '#title' => $this->t('Publicación'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    if (isset($form['publish_status'])) {
      $form['publishing']['publish_status'] = $form['publish_status'];
      unset($form['publish_status']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    $is_new = $entity->isNew();
    $result = parent::save($form, $form_state);

    $label = $entity->label();
    if ($is_new) {
      $this->messenger()->addStatus($this->t('Conector %label creado.', ['%label' => $label]));
    }
    else {
      $this->messenger()->addStatus($this->t('Conector %label actualizado.', ['%label' => $label]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
