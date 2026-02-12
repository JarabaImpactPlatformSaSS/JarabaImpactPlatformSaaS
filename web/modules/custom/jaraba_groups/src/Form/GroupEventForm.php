<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Group Event forms.
 */
class GroupEventForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);
        $entity = $this->getEntity();

        switch ($result) {
            case SAVED_NEW:
                $this->messenger()->addStatus($this->t('Evento "@title" creado.', ['@title' => $entity->label()]));
                break;

            case SAVED_UPDATED:
                $this->messenger()->addStatus($this->t('Evento actualizado.'));
                break;
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}
