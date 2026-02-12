<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar reseñas AgroConecta.
 *
 * Incluye validación de rating (1-5) y controles de estado de moderación.
 */
class ReviewAgroForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Agrupar campos principales.
        $form['review_details'] = [
            '#type' => 'details',
            '#title' => $this->t('Detalles de la reseña'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        // Mover campos al grupo.
        $fields_in_group = ['type', 'target_entity_type', 'target_entity_id', 'rating', 'title', 'body'];
        foreach ($fields_in_group as $field_name) {
            if (isset($form[$field_name])) {
                $form['review_details'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // Grupo de moderación.
        $form['moderation'] = [
            '#type' => 'details',
            '#title' => $this->t('Moderación'),
            '#open' => FALSE,
            '#weight' => 0,
        ];

        $moderation_fields = ['state', 'verified_purchase'];
        foreach ($moderation_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['moderation'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // Grupo de respuesta del productor.
        $form['producer_response'] = [
            '#type' => 'details',
            '#title' => $this->t('Respuesta del productor'),
            '#open' => FALSE,
            '#weight' => 5,
        ];

        $response_fields = ['response', 'response_by'];
        foreach ($response_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['producer_response'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        parent::validateForm($form, $form_state);

        // Validar que el rating está entre 1 y 5.
        $rating = $form_state->getValue(['rating', 0, 'value']);
        if ($rating !== NULL && ($rating < 1 || $rating > 5)) {
            $form_state->setErrorByName('rating', $this->t('La valoración debe estar entre 1 y 5 estrellas.'));
        }

        // Validar tipo válido.
        $valid_types = ['product', 'producer', 'order'];
        $type = $form_state->getValue(['type', 0, 'value']);
        if ($type && !in_array($type, $valid_types)) {
            $form_state->setErrorByName('type', $this->t('Tipo de reseña no válido. Tipos permitidos: @types.', [
                '@types' => implode(', ', $valid_types),
            ]));
        }

        // Validar estado válido.
        $valid_states = ['pending', 'approved', 'rejected', 'flagged'];
        $state = $form_state->getValue(['state', 0, 'value']);
        if ($state && !in_array($state, $valid_states)) {
            $form_state->setErrorByName('state', $this->t('Estado no válido.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $entity = $this->getEntity();
        $message_args = ['%title' => $entity->label() ?: $this->t('Reseña #@id', ['@id' => $entity->id()])];

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Reseña %title creada correctamente.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('Reseña %title actualizada correctamente.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
