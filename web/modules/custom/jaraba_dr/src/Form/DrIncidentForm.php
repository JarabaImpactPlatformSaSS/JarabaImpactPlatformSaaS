<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para DrIncident.
 *
 * Los incidentes pueden crearse manualmente o via IncidentCommunicatorService.
 * Este formulario permite la gestion completa del ciclo de vida del incidente.
 */
class DrIncidentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('title')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Incidente DR %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Incidente DR %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
