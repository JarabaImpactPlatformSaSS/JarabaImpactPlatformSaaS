<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class IntegrityProofAgroForm extends ContentEntityForm
{
    public function save(array $form, FormStateInterface $form_state): int
    {
        $status = parent::save($form, $form_state);
        $this->messenger()->addStatus($this->t('Prueba de integridad guardada.'));
        $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
        return $status;
    }
}
