<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form para crear/editar evaluaciones de Rueda de la Vida.
 *
 * PROPÓSITO:
 * Formulario administrativo con sliders 1-10 para las 8 áreas.
 */
class LifeWheelAssessmentForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        // Añadir información de ayuda.
        $form['help'] = [
            '#type' => 'markup',
            '#markup' => '<p class="form-help">' . $this->t('Evalúa cada área de tu vida del 1 (muy insatisfecho) al 10 (muy satisfecho).') . '</p>',
            '#weight' => -100,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->getEntity();

        // Si es nuevo, asignar usuario actual.
        if ($entity->isNew()) {
            $entity->set('user_id', \Drupal::currentUser()->id());
        }

        $status = parent::save($form, $form_state);

        // Mensaje de confirmación traducible.
        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Evaluación de Rueda de la Vida creada correctamente.'));
        } else {
            $this->messenger()->addStatus($this->t('Evaluación de Rueda de la Vida actualizada.'));
        }

        // Redirigir a la colección.
        $form_state->setRedirect('entity.life_wheel_assessment.collection');

        return $status;
    }

}
