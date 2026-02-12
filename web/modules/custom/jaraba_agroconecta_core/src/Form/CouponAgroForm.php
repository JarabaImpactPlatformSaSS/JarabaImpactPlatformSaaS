<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creación/edición de CouponAgro.
 */
class CouponAgroForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\CouponAgro $entity */
        $entity = $this->getEntity();

        // Normalizar código a mayúsculas.
        $code = strtoupper(trim($entity->get('code')->value ?? ''));
        $entity->set('code', $code);

        $status = parent::save($form, $form_state);

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Cupón "%code" creado.', ['%code' => $code]));
        } else {
            $this->messenger()->addStatus($this->t('Cupón "%code" actualizado.', ['%code' => $code]));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $status;
    }

}
