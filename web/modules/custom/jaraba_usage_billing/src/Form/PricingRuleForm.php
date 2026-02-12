<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar reglas de pricing.
 */
class PricingRuleForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();

    try {
      $status = parent::save($form, $form_state);

      if ($status === SAVED_NEW) {
        $this->messenger()->addStatus($this->t('Regla de pricing %label creada.', [
          '%label' => $entity->label(),
        ]));
      }
      else {
        $this->messenger()->addStatus($this->t('Regla de pricing %label actualizada.', [
          '%label' => $entity->label(),
        ]));
      }

      $form_state->setRedirectUrl($entity->toUrl('collection'));

      return $status;
    }
    catch (\Exception $e) {
      $this->logger('jaraba_usage_billing')->error('Error guardando regla de pricing: @error', [
        '@error' => $e->getMessage(),
      ]);
      $this->messenger()->addError($this->t('Error al guardar la regla de pricing. Por favor, inténtelo de nuevo.'));

      return SAVED_UPDATED;
    }
  }

}
