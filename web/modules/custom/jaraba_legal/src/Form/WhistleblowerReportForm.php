<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para WhistleblowerReport.
 *
 * Permite crear reportes de denuncia. Una vez creados, los reportes
 * son de solo lectura (excepto estado, asignación y resolución).
 * Gestionado por WhistleblowerChannelService.
 */
class WhistleblowerReportForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%code' => $entity->get('tracking_code')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Reporte de denuncia %code creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Reporte de denuncia %code actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
