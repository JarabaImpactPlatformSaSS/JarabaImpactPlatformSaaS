<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creación/edición de ShippingMethodAgro.
 */
class ShippingMethodAgroForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $status = parent::save($form, $form_state);
        $label = $this->getEntity()->label();

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Método de envío "%label" creado.', ['%label' => $label]));
        } else {
            $this->messenger()->addStatus($this->t('Método de envío "%label" actualizado.', ['%label' => $label]));
        }

        $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
        return $status;
    }

}
