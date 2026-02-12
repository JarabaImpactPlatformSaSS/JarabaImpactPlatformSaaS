<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class QrCodeAgroForm extends ContentEntityForm
{
    public function save(array $form, FormStateInterface $form_state): int
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\QrCodeAgro $entity */
        $entity = $this->getEntity();

        // Generar short_code si es nuevo.
        if ($entity->isNew() && empty($entity->get('short_code')->value)) {
            $entity->set('short_code', substr(bin2hex(random_bytes(6)), 0, 12));
        }

        $status = parent::save($form, $form_state);
        $this->messenger()->addStatus($status === SAVED_NEW
            ? $this->t('Código QR "%label" creado.', ['%label' => $entity->label()])
            : $this->t('Código QR "%label" actualizado.', ['%label' => $entity->label()]));
        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $status;
    }
}
