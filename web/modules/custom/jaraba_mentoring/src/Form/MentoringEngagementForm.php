<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for Mentoring Engagement entity.
 */
class MentoringEngagementForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $entity = $this->getEntity();
        $message_args = ['%id' => $entity->id()];

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('El engagement #%id ha sido creado.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('El engagement #%id ha sido actualizado.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
