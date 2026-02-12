<?php

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para IntentionCard.
 */
class IntentionCardForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $entity = $this->getEntity();
        $message_args = ['%label' => $entity->label()];

        $this->messenger()->addStatus(
            $result === SAVED_NEW
            ? $this->t('Tarjeta de intención %label creada.', $message_args)
            : $this->t('Tarjeta de intención %label actualizada.', $message_args)
        );

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
