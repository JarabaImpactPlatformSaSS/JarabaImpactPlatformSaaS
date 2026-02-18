<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario admin para gestionar solicitudes Andalucía +ei.
 */
class SolicitudEiAdminForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        /** @var \Drupal\jaraba_andalucia_ei\Entity\SolicitudEiInterface $entity */
        $entity = $this->getEntity();

        // Inferir colectivo automáticamente si no se ha asignado.
        if (!$entity->get('colectivo_inferido')->value) {
            $entity->setColectivoInferido($entity->inferirColectivo());
        }

        $result = parent::save($form, $form_state);

        $this->messenger()->addStatus($this->t('La solicitud de @name ha sido guardada.', [
            '@name' => $entity->getNombre(),
        ]));

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}
