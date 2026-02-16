<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para VeriFactuInvoiceRecord.
 *
 * En produccion, los registros se crean programaticamente via
 * VeriFactuRecordService. Este formulario existe para desarrollo
 * y testing, y es de solo lectura (view) en la practica ya que
 * el AccessControlHandler deniega update para todos los roles.
 */
class VeriFactuInvoiceRecordForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('numero_factura')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('VeriFactu record %label created.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
