<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar recompensas de referido.
 *
 * ESTRUCTURA: Extiende ContentEntityForm para operaciones CRUD
 *   sobre la entidad ReferralReward.
 *
 * LÓGICA: Al guardar, muestra mensaje de estado (creado/actualizado)
 *   y redirige al listado de recompensas de referido.
 *
 * RELACIONES:
 * - ReferralRewardForm -> ReferralReward entity (gestiona)
 * - ReferralRewardForm <- AdminHtmlRouteProvider (invocado por)
 */
class ReferralRewardForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $result = parent::save($form, $form_state);
    $message_args = ['%id' => $entity->id()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Recompensa de referido #%id creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Recompensa de referido #%id actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
