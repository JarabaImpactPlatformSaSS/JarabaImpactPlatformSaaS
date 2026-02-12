<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para la entidad ProductDocument.
 */
class ProductDocumentForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\ProductDocument $entity */
        $entity = $this->getEntity();

        $status = parent::save($form, $form_state);
        $this->messenger()->addStatus($status === SAVED_NEW
            ? $this->t('Documento "%label" creado.', ['%label' => $entity->label()])
            : $this->t('Documento "%label" actualizado.', ['%label' => $entity->label()]));
        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $status;
    }

}
