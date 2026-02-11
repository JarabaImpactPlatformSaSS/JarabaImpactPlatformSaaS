<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for Mentoring Package entity.
 */
class MentoringPackageForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $entity = $this->getEntity();
        $message_args = ['%title' => $entity->get('title')->value];

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('El paquete %title ha sido creado.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('El paquete %title ha sido actualizado.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
