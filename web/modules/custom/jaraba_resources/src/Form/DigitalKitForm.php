<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Digital Kit edit forms.
 */
class DigitalKitForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);
        $entity = $this->getEntity();
        $message_args = ['%label' => $entity->label()];

        switch ($result) {
            case SAVED_NEW:
                $this->messenger()->addStatus($this->t('Kit Digital %label creado.', $message_args));
                break;

            case SAVED_UPDATED:
                $this->messenger()->addStatus($this->t('Kit Digital %label actualizado.', $message_args));
                break;
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}
