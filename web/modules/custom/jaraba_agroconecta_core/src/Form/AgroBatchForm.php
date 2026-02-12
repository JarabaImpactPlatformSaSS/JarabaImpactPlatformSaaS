<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class AgroBatchForm extends ContentEntityForm
{
    public function save(array $form, FormStateInterface $form_state): int
    {
        $status = parent::save($form, $form_state);
        $label = $this->getEntity()->label();
        $this->messenger()->addStatus($status === SAVED_NEW
            ? $this->t('Lote "%label" creado.', ['%label' => $label])
            : $this->t('Lote "%label" actualizado.', ['%label' => $label]));
        $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
        return $status;
    }
}
