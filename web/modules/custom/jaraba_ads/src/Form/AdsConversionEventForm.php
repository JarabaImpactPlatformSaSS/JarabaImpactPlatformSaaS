<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar eventos de conversión offline.
 *
 * ESTRUCTURA: Extiende ContentEntityForm para operaciones CRUD
 *   sobre la entidad AdsConversionEvent.
 *
 * LÓGICA: Al guardar, muestra mensaje de estado (creado/actualizado)
 *   y redirige al listado de eventos de conversión.
 *
 * RELACIONES:
 * - AdsConversionEventForm -> AdsConversionEvent entity (gestiona)
 * - AdsConversionEventForm <- AdminHtmlRouteProvider (invocado por)
 */
class AdsConversionEventForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $result = parent::save($form, $form_state);

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Evento de conversión offline creado.'));
    }
    else {
      $this->messenger()->addStatus($this->t('Evento de conversión offline actualizado.'));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
