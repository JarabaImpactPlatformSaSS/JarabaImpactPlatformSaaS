<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for Email List entities.
 */
class EmailListForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $status = parent::save($form, $form_state);

        $entity = $this->entity;
        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Created email list %name.', [
                '%name' => $entity->label(),
            ]));
        } else {
            $this->messenger()->addStatus($this->t('Updated email list %name.', [
                '%name' => $entity->label(),
            ]));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $status;
    }

}
