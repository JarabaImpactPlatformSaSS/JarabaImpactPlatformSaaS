<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creacion/edicion de Solicitudes de Documentos.
 *
 * Estructura: Extiende ContentEntityForm para aprovechar Field UI.
 * Logica: Formulario admin para solicitudes del portal de cliente (FASE B2).
 */
class DocumentRequestForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();

    $this->messenger()->addStatus($this->t('Solicitud de documento "%title" guardada.', [
      '%title' => $entity->get('title')->value,
    ]));

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
