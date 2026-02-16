<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar registros de exportación de tenant.
 */
class TenantExportRecordForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Exportación #@id creada.', ['@id' => $entity->id()]));
    }
    else {
      $this->messenger()->addStatus($this->t('Exportación #@id actualizada.', ['@id' => $entity->id()]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
