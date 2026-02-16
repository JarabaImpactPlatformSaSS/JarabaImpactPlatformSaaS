<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar la configuracion VeriFactu de un tenant.
 *
 * Permite configurar: NIF, nombre fiscal, serie de facturacion,
 * entorno AEAT y estado activo. La gestion de certificados PKCS#12
 * se realiza via el CertificateManagerService compartido.
 */
class VeriFactuTenantConfigForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('nombre_fiscal')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('VeriFactu configuration for %label created.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('VeriFactu configuration for %label updated.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
