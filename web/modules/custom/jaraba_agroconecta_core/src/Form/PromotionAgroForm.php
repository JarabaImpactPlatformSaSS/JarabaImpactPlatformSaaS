<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creación/edición de PromotionAgro.
 */
class PromotionAgroForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->getEntity();
        $status = parent::save($form, $form_state);

        $label = $entity->label();
        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Promoción "%label" creada.', ['%label' => $label]));
        } else {
            $this->messenger()->addStatus($this->t('Promoción "%label" actualizada.', ['%label' => $label]));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $status;
    }

}
