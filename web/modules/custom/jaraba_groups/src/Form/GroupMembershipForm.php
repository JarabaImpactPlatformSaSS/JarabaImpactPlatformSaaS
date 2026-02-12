<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Group Membership forms.
 */
class GroupMembershipForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        switch ($result) {
            case SAVED_NEW:
                $this->messenger()->addStatus($this->t('Membresía creada.'));
                break;

            case SAVED_UPDATED:
                $this->messenger()->addStatus($this->t('Membresía actualizada.'));
                break;
        }

        $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
        return $result;
    }

}
