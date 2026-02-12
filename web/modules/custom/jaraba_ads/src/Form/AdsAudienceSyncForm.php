<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar audiencias sincronizadas.
 *
 * ESTRUCTURA: Extiende ContentEntityForm para operaciones CRUD
 *   sobre la entidad AdsAudienceSync.
 *
 * LÓGICA: Al guardar, muestra mensaje de estado (creada/actualizada)
 *   y redirige al listado de audiencias sincronizadas.
 *
 * RELACIONES:
 * - AdsAudienceSyncForm -> AdsAudienceSync entity (gestiona)
 * - AdsAudienceSyncForm <- AdminHtmlRouteProvider (invocado por)
 */
class AdsAudienceSyncForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $entity->label()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Audiencia sincronizada %label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Audiencia sincronizada %label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
