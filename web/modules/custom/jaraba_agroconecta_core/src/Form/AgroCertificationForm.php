<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar AgroCertification.
 */
class AgroCertificationForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $messageArgs = ['%label' => $this->entity->label()];
        $message = $result === SAVED_NEW
            ? $this->t('Certificación %label creada.', $messageArgs)
            : $this->t('Certificación %label actualizada.', $messageArgs);

        $this->messenger()->addStatus($message);
        $form_state->setRedirectUrl($this->entity->toUrl('collection'));

        return $result;
    }

}
