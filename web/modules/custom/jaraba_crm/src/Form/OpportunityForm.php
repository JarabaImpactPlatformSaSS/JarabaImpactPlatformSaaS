<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar oportunidades.
 */
class OpportunityForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $entity = $this->getEntity();
        $message_args = ['%label' => $entity->toLink()->toString()];

        switch ($result) {
            case SAVED_NEW:
                $this->messenger()->addStatus($this->t('Oportunidad %label creada correctamente.', $message_args));
                break;

            case SAVED_UPDATED:
                $this->messenger()->addStatus($this->t('Oportunidad %label actualizada correctamente.', $message_args));
                break;
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}
