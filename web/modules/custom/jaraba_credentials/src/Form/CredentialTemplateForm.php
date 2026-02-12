<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar CredentialTemplate.
 */
class CredentialTemplateForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $messageArgs = ['%label' => $this->entity->label()];
        $message = $result === SAVED_NEW
            ? $this->t('Template de credencial %label creado.', $messageArgs)
            : $this->t('Template de credencial %label actualizado.', $messageArgs);

        $this->messenger()->addStatus($message);

        $form_state->setRedirectUrl($this->entity->toUrl('collection'));

        return $result;
    }

}
