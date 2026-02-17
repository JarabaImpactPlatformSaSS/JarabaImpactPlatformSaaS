<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creacion/edicion de Presupuestos.
 *
 * Estructura: Extiende ContentEntityForm para aprovechar Field UI.
 * Logica: Formulario admin para gestion directa de presupuestos.
 */
class QuoteForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();

    $this->messenger()->addStatus($this->t('Presupuesto "%title" guardado.', [
      '%title' => $entity->get('title')->value,
    ]));

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
