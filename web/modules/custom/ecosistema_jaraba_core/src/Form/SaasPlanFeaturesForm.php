<?php

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar entidades SaasPlanFeatures.
 *
 * Permite configurar las features y limites por combinacion
 * vertical+tier del ecosistema SaaS.
 */
class SaasPlanFeaturesForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\ecosistema_jaraba_core\Entity\SaasPlanFeatures $features */
    $features = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre'),
      '#maxlength' => 255,
      '#default_value' => $features->label(),
      '#description' => $this->t('Nombre legible (ej: Empleabilidad - Starter, Default - Enterprise).'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $features->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ecosistema_jaraba_core\Entity\SaasPlanFeatures::load',
      ],
      '#disabled' => !$features->isNew(),
      '#description' => $this->t('ID unico siguiendo la convencion {vertical}_{tier} (ej: empleabilidad_starter, _default_enterprise).'),
    ];

    // =====================================================================
    // IDENTIFICACION
    // =====================================================================
    $form['identification'] = [
      '#type' => 'details',
      '#title' => $this->t('Identificacion'),
      '#open' => TRUE,
    ];

    $form['identification']['vertical'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical'),
      '#options' => [
        '_default' => $this->t('Default (fallback global)'),
        'agroconecta' => $this->t('AgroConecta'),
        'comercioconecta' => $this->t('ComercioConecta'),
        'serviciosconecta' => $this->t('ServiciosConecta'),
        'empleabilidad' => $this->t('Empleabilidad'),
        'emprendimiento' => $this->t('Emprendimiento'),
      ],
      '#default_value' => $features->getVertical(),
      '#required' => TRUE,
      '#description' => $this->t('Vertical a la que aplica. _default se usa como fallback cuando no hay config especifica.'),
    ];

    $form['identification']['tier'] = [
      '#type' => 'select',
      '#title' => $this->t('Tier'),
      '#options' => [
        'starter' => $this->t('Starter'),
        'professional' => $this->t('Professional'),
        'enterprise' => $this->t('Enterprise'),
      ],
      '#default_value' => $features->getTier(),
      '#required' => TRUE,
      '#description' => $this->t('Tier del plan al que aplica esta configuracion.'),
    ];

    $form['identification']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Descripcion'),
      '#default_value' => $features->getDescription(),
      '#description' => $this->t('Descripcion interna para administradores.'),
      '#rows' => 2,
    ];

    // =====================================================================
    // FEATURES
    // =====================================================================
    $form['features_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Features habilitadas'),
      '#open' => TRUE,
      '#description' => $this->t('Lista de features disponibles en esta combinacion vertical+tier. Una por linea.'),
    ];

    $form['features_section']['features'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Features'),
      '#default_value' => implode("\n", $features->getFeatures()),
      '#description' => $this->t('Una feature por linea (ej: seo_advanced, ab_testing, analytics, schema_org, premium_blocks).'),
      '#rows' => 8,
    ];

    // =====================================================================
    // LIMITES
    // =====================================================================
    $form['limits_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Limites numericos'),
      '#open' => TRUE,
      '#description' => $this->t('Limites por recurso. Formato: key|valor (una por linea). -1 = ilimitado, 0 = no incluido.'),
    ];

    $limits = $features->getLimits();
    $limits_text = '';
    foreach ($limits as $key => $value) {
      $limits_text .= $key . '|' . $value . "\n";
    }

    $form['limits_section']['limits'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Limites'),
      '#default_value' => trim($limits_text),
      '#description' => $this->t('Formato: clave|valor (ej: max_pages|25, basic_templates|25, premium_blocks|10). -1 = ilimitado.'),
      '#rows' => 10,
    ];

    // =====================================================================
    // ESTADO
    // =====================================================================
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activo'),
      '#default_value' => $features->status(),
      '#description' => $this->t('Si esta desactivado, esta configuracion no se usa en la resolucion de features.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $features = $this->entity;

    // Convertir features de textarea a array.
    $features_raw = $form_state->getValue('features') ?? '';
    $features_list = array_filter(array_map('trim', explode("\n", $features_raw)));
    $features->set('features', array_values($features_list));

    // Convertir limits de textarea a array key=>value.
    $limits_raw = $form_state->getValue('limits') ?? '';
    $limits_lines = array_filter(array_map('trim', explode("\n", $limits_raw)));
    $limits = [];
    foreach ($limits_lines as $line) {
      $parts = explode('|', $line, 2);
      if (count($parts) === 2) {
        $limits[trim($parts[0])] = (int) trim($parts[1]);
      }
    }
    $features->set('limits', $limits);

    $status = $features->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Configuracion de features %label creada.', [
        '%label' => $features->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Configuracion de features %label actualizada.', [
        '%label' => $features->label(),
      ]));
    }

    $form_state->setRedirectUrl($features->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancelar'),
      '#url' => $this->entity->toUrl('collection'),
      '#attributes' => [
        'class' => ['button'],
      ],
      '#weight' => 10,
    ];

    if (!$this->entity->isNew()) {
      $actions['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Eliminar'),
        '#url' => $this->entity->toUrl('delete-form'),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
        '#weight' => 20,
      ];
    }

    return $actions;
  }

}
