<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de creacion/edicion de convocatorias de fondos.
 *
 * Estructura: Extiende ContentEntityForm para heredar el manejo
 *   automatico de campos base. Agrupa campos en fieldsets #type=>'details'.
 *
 * Logica: Al guardar, redirige a la coleccion de convocatorias.
 *   Los campos se organizan en grupos logicos para mejor UX.
 */
class FundingOpportunityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['identification'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Identificacion'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    if (isset($form['name'])) {
      $form['identification']['name'] = $form['name'];
      unset($form['name']);
    }
    if (isset($form['funding_body'])) {
      $form['identification']['funding_body'] = $form['funding_body'];
      unset($form['funding_body']);
    }
    if (isset($form['program'])) {
      $form['identification']['program'] = $form['program'];
      unset($form['program']);
    }

    $form['financial'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Datos financieros'),
      '#open' => TRUE,
      '#weight' => 1,
    ];

    if (isset($form['max_amount'])) {
      $form['financial']['max_amount'] = $form['max_amount'];
      unset($form['max_amount']);
    }

    $form['dates'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Plazos'),
      '#open' => TRUE,
      '#weight' => 2,
    ];

    if (isset($form['deadline'])) {
      $form['dates']['deadline'] = $form['deadline'];
      unset($form['deadline']);
    }
    if (isset($form['alert_days_before'])) {
      $form['dates']['alert_days_before'] = $form['alert_days_before'];
      unset($form['alert_days_before']);
    }

    $form['requirements_group'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Requisitos y documentacion'),
      '#open' => FALSE,
      '#weight' => 3,
    ];

    if (isset($form['requirements'])) {
      $form['requirements_group']['requirements'] = $form['requirements'];
      unset($form['requirements']);
    }
    if (isset($form['documentation_required'])) {
      $form['requirements_group']['documentation_required'] = $form['documentation_required'];
      unset($form['documentation_required']);
    }

    $form['classification'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Estado y clasificacion'),
      '#open' => TRUE,
      '#weight' => 4,
    ];

    if (isset($form['status'])) {
      $form['classification']['status'] = $form['status'];
      unset($form['status']);
    }
    if (isset($form['url'])) {
      $form['classification']['url'] = $form['url'];
      unset($form['url']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus(new TranslatableMarkup('Convocatoria "%name" creada correctamente.', [
        '%name' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus(new TranslatableMarkup('Convocatoria "%name" actualizada.', [
        '%name' => $entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
