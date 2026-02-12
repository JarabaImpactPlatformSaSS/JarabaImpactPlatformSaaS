<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class TraceEventAgroForm extends ContentEntityForm
{
    public function save(array $form, FormStateInterface $form_state): int
    {
        $status = parent::save($form, $form_state);
        $this->messenger()->addStatus($status === SAVED_NEW
            ? $this->t('Evento de trazabilidad creado.')
            : $this->t('Evento de trazabilidad actualizado.'));
        $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
        return $status;
    }
}
