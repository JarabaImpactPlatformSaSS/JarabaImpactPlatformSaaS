<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for CandidateSkill entities.
 */
class CandidateSkillForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $status = parent::save($form, $form_state);

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Candidate skill has been created.'));
        } else {
            $this->messenger()->addStatus($this->t('Candidate skill has been updated.'));
        }

        $form_state->setRedirectUrl($this->entity->toUrl('collection'));

        return $status;
    }

}
