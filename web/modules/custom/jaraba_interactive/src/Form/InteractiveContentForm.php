<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar contenido interactivo.
 */
class InteractiveContentForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        // Añadir clase para slide-panel styling.
        $form['#attributes']['class'][] = 'jaraba-premium-form';
        $form['#attributes']['class'][] = 'slide-panel__form';

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->getEntity();
        $status = parent::save($form, $form_state);

        $messenger = \Drupal::messenger();
        if ($status === SAVED_NEW) {
            $messenger->addStatus($this->t('Se ha creado el contenido interactivo %title.', [
                '%title' => $entity->label(),
            ]));
        } else {
            $messenger->addStatus($this->t('Se ha actualizado el contenido interactivo %title.', [
                '%title' => $entity->label(),
            ]));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $status;
    }

}
