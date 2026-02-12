<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for User Subscription forms.
 */
class UserSubscriptionForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        switch ($result) {
            case SAVED_NEW:
                $this->messenger()->addStatus($this->t('Suscripción creada.'));
                break;

            case SAVED_UPDATED:
                $this->messenger()->addStatus($this->t('Suscripción actualizada.'));
                break;
        }

        $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
        return $result;
    }

}
