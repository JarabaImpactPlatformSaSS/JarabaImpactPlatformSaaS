<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar ProductAgro.
 */
class ProductAgroForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $messageArgs = ['%label' => $this->entity->label()];
        $message = $result === SAVED_NEW
            ? $this->t('Producto agro %label creado.', $messageArgs)
            : $this->t('Producto agro %label actualizado.', $messageArgs);

        $this->messenger()->addStatus($message);
        $form_state->setRedirectUrl($this->entity->toUrl('collection'));

        return $result;
    }

}
