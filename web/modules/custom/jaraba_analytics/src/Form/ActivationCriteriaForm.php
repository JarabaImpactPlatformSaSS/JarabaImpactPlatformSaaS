<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form para ActivationCriteriaConfig.
 */
class ActivationCriteriaForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\jaraba_analytics\Entity\ActivationCriteriaConfig $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\jaraba_analytics\Entity\ActivationCriteriaConfig::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $verticals = [
      'empleabilidad' => 'Empleabilidad',
      'emprendimiento' => 'Emprendimiento',
      'comercioconecta' => 'ComercioConecta',
      'agroconecta' => 'AgroConecta',
      'jarabalex' => 'JarabaLex',
      'serviciosconecta' => 'ServiciosConecta',
      'andalucia_ei' => 'Andalucia EI',
      'jaraba_content_hub' => 'Content Hub',
      'formacion' => 'Formacion',
      'demo' => 'Demo',
    ];

    $form['vertical'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical'),
      '#options' => $verticals,
      '#default_value' => $entity->getVertical(),
      '#required' => TRUE,
    ];

    $form['thresholds'] = [
      '#type' => 'details',
      '#title' => $this->t('Umbrales'),
      '#open' => TRUE,
    ];

    $form['thresholds']['activation_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Umbral de Activacion'),
      '#description' => $this->t('Porcentaje objetivo (0-1). Default: 0.40 (40%).'),
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#default_value' => $entity->getActivationThreshold(),
    ];

    $form['thresholds']['retention_d30_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Umbral Retencion D30'),
      '#description' => $this->t('Porcentaje objetivo (0-1). Default: 0.25 (25%).'),
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#default_value' => $entity->getRetentionD30Threshold(),
    ];

    $form['thresholds']['nps_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Umbral NPS'),
      '#description' => $this->t('NPS minimo objetivo (-100 a 100). Default: 40.'),
      '#min' => -100,
      '#max' => 100,
      '#default_value' => $entity->getNpsThreshold(),
    ];

    $form['thresholds']['churn_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Umbral Churn'),
      '#description' => $this->t('Maximo churn mensual (0-1). Default: 0.05 (5%).'),
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#default_value' => $entity->getChurnThreshold(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $status = $entity->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Criterios de activacion %label creados.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Criterios de activacion %label actualizados.', [
        '%label' => $entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $status;
  }

}
