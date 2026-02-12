<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for MVP Hypothesis edit forms.
 */
class MvpHypothesisForm extends ContentEntityForm
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
                $this->messenger()->addStatus($this->t('Hipótesis MVP creada.'));
                break;

            case SAVED_UPDATED:
                $this->messenger()->addStatus($this->t('Hipótesis MVP actualizada.'));
                break;
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}
