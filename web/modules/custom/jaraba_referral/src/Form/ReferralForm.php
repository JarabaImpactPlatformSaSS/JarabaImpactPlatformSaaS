<?php

namespace Drupal\jaraba_referral\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar referidos.
 *
 * ESTRUCTURA: Extiende ContentEntityForm para operaciones CRUD
 *   sobre la entidad Referral.
 *
 * LÓGICA: Al guardar, muestra mensaje de estado (creado/actualizado)
 *   y redirige al listado de referidos. No genera código automáticamente
 *   desde el formulario admin; eso lo hace ReferralManagerService.
 *
 * RELACIONES:
 * - ReferralForm -> Referral entity (gestiona)
 * - ReferralForm <- AdminHtmlRouteProvider (invocado por)
 */
class ReferralForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $result = parent::save($form, $form_state);
    $message_args = ['%code' => $entity->get('referral_code')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Referido con código %code creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Referido con código %code actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
