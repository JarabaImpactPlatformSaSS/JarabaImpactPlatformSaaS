<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar programas de referidos.
 *
 * ESTRUCTURA: Extiende ContentEntityForm para operaciones CRUD
 *   sobre la entidad ReferralProgram.
 *
 * LÓGICA: Al guardar, muestra mensaje de estado (creado/actualizado)
 *   y redirige al listado de programas de referidos.
 *
 * RELACIONES:
 * - ReferralProgramForm -> ReferralProgram entity (gestiona)
 * - ReferralProgramForm <- AdminHtmlRouteProvider (invocado por)
 */
class ReferralProgramForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $result = parent::save($form, $form_state);
    $message_args = ['%name' => $entity->get('name')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Programa de referidos %name creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Programa de referidos %name actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
