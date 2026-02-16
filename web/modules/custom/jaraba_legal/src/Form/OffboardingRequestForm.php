<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para OffboardingRequest.
 *
 * Permite crear y gestionar solicitudes de baja de tenants.
 * El workflow completo es gestionado por OffboardingManagerService.
 */
class OffboardingRequestForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('tenant_name')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Solicitud de offboarding para %label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Solicitud de offboarding para %label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
