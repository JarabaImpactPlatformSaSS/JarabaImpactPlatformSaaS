<?php

namespace Drupal\jaraba_addons\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar add-ons.
 *
 * ESTRUCTURA: Extiende ContentEntityForm para operaciones CRUD
 *   sobre la entidad Addon.
 *
 * LÓGICA: Al guardar, muestra mensaje de estado (creado/actualizado)
 *   y redirige al listado de add-ons. Valida que machine_name solo
 *   contenga caracteres alfanuméricos y guiones bajos.
 *
 * RELACIONES:
 * - AddonForm -> Addon entity (gestiona)
 * - AddonForm <- AdminHtmlRouteProvider (invocado por)
 */
class AddonForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $entity->label()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Add-on %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Add-on %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
