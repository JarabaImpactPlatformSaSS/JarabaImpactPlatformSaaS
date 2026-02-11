<?php

namespace Drupal\jaraba_addons\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar suscripciones a add-ons.
 *
 * ESTRUCTURA: Extiende ContentEntityForm para operaciones CRUD
 *   sobre la entidad AddonSubscription.
 *
 * LÓGICA: Al guardar, muestra mensaje de estado (creada/actualizada)
 *   y redirige al listado de suscripciones. Si es nueva suscripción,
 *   establece la fecha de inicio automáticamente.
 *
 * RELACIONES:
 * - AddonSubscriptionForm -> AddonSubscription entity (gestiona)
 * - AddonSubscriptionForm <- AdminHtmlRouteProvider (invocado por)
 */
class AddonSubscriptionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;

    // Auto-establecer fecha de inicio si es nueva suscripción y no tiene.
    if ($entity->isNew() && empty($entity->get('start_date')->value)) {
      $entity->set('start_date', date('Y-m-d\TH:i:s'));
    }

    $result = parent::save($form, $form_state);

    // Obtener nombre del add-on para el mensaje.
    $addon = $entity->get('addon_id')->entity;
    $addon_label = $addon ? $addon->label() : $this->t('Desconocido');
    $message_args = ['%addon' => $addon_label];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Suscripción al add-on %addon creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Suscripción al add-on %addon actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
