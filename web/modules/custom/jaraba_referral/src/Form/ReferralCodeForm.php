<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar códigos de referido.
 *
 * ESTRUCTURA: Extiende ContentEntityForm para operaciones CRUD
 *   sobre la entidad ReferralCode.
 *
 * LÓGICA: Al guardar, muestra mensaje de estado (creado/actualizado)
 *   y redirige al listado de códigos de referido.
 *
 * RELACIONES:
 * - ReferralCodeForm -> ReferralCode entity (gestiona)
 * - ReferralCodeForm <- AdminHtmlRouteProvider (invocado por)
 */
class ReferralCodeForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $result = parent::save($form, $form_state);
    $message_args = ['%code' => $entity->get('code')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Código de referido %code creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Código de referido %code actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
