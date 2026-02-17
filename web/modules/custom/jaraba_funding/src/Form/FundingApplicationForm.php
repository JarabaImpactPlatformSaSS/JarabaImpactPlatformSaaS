<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de creacion/edicion de solicitudes de fondos.
 *
 * Estructura: ContentEntityForm con campos agrupados en fieldsets.
 *
 * Logica: El numero de solicitud (SOL-YYYY-NNNN) se auto-genera en
 *   preSave(), por lo que no se muestra en el formulario de creacion.
 *   Al guardar, redirige a la coleccion de solicitudes.
 */
class FundingApplicationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['reference'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Referencia'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    if (isset($form['opportunity_id'])) {
      $form['reference']['opportunity_id'] = $form['opportunity_id'];
      unset($form['opportunity_id']);
    }

    $form['financial'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Datos financieros'),
      '#open' => TRUE,
      '#weight' => 1,
    ];

    if (isset($form['amount_requested'])) {
      $form['financial']['amount_requested'] = $form['amount_requested'];
      unset($form['amount_requested']);
    }
    if (isset($form['amount_approved'])) {
      $form['financial']['amount_approved'] = $form['amount_approved'];
      unset($form['amount_approved']);
    }

    $form['dates'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Fechas'),
      '#open' => TRUE,
      '#weight' => 2,
    ];

    if (isset($form['submission_date'])) {
      $form['dates']['submission_date'] = $form['submission_date'];
      unset($form['submission_date']);
    }
    if (isset($form['resolution_date'])) {
      $form['dates']['resolution_date'] = $form['resolution_date'];
      unset($form['resolution_date']);
    }
    if (isset($form['next_deadline'])) {
      $form['dates']['next_deadline'] = $form['next_deadline'];
      unset($form['next_deadline']);
    }

    $form['details_group'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Detalles'),
      '#open' => FALSE,
      '#weight' => 3,
    ];

    if (isset($form['budget_breakdown'])) {
      $form['details_group']['budget_breakdown'] = $form['budget_breakdown'];
      unset($form['budget_breakdown']);
    }
    if (isset($form['impact_indicators'])) {
      $form['details_group']['impact_indicators'] = $form['impact_indicators'];
      unset($form['impact_indicators']);
    }
    if (isset($form['justification_notes'])) {
      $form['details_group']['justification_notes'] = $form['justification_notes'];
      unset($form['justification_notes']);
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
      $this->messenger()->addStatus(new TranslatableMarkup('Solicitud "%number" creada correctamente.', [
        '%number' => $entity->get('application_number')->value ?? $entity->id(),
      ]));
    }
    else {
      $this->messenger()->addStatus(new TranslatableMarkup('Solicitud "%number" actualizada.', [
        '%number' => $entity->get('application_number')->value ?? $entity->id(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
