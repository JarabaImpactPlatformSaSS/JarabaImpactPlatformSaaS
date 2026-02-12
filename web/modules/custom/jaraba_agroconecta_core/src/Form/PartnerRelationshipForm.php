<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para la entidad PartnerRelationship.
 *
 * Genera token de acceso automáticamente al crear una nueva relación.
 */
class PartnerRelationshipForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\PartnerRelationship $entity */
        $entity = $this->getEntity();

        // Generar access_token securizado si es nueva relación.
        if ($entity->isNew() && empty($entity->get('access_token')->value)) {
            $entity->set('access_token', bin2hex(random_bytes(32)));
        }

        $status = parent::save($form, $form_state);
        $this->messenger()->addStatus($status === SAVED_NEW
            ? $this->t('Relación con "%label" creada.', ['%label' => $entity->label()])
            : $this->t('Relación con "%label" actualizada.', ['%label' => $entity->label()]));
        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $status;
    }

}
