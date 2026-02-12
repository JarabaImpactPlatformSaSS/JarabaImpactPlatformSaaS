<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar entidades Badge (Insignia).
 *
 * Organiza los campos en tres fieldsets:
 * - Información: nombre, descripción, icono, categoría
 * - Criterios: tipo de criterio, configuración JSON, puntos
 * - Estado: activa/inactiva
 */
class BadgeForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Fieldset: Información básica.
        $form['info'] = [
            '#type' => 'details',
            '#title' => $this->t('Información'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        if (isset($form['name'])) {
            $form['info']['name'] = $form['name'];
            unset($form['name']);
        }

        if (isset($form['description'])) {
            $form['info']['description'] = $form['description'];
            unset($form['description']);
        }

        if (isset($form['icon'])) {
            $form['info']['icon'] = $form['icon'];
            unset($form['icon']);
        }

        if (isset($form['category'])) {
            $form['info']['category'] = $form['category'];
            unset($form['category']);
        }

        // Fieldset: Criterios de otorgamiento.
        $form['criteria'] = [
            '#type' => 'details',
            '#title' => $this->t('Criterios'),
            '#open' => TRUE,
            '#weight' => 1,
        ];

        if (isset($form['criteria_type'])) {
            $form['criteria']['criteria_type'] = $form['criteria_type'];
            unset($form['criteria_type']);
        }

        if (isset($form['criteria_config'])) {
            $form['criteria']['criteria_config'] = $form['criteria_config'];
            // Forzar textarea para la configuración JSON.
            $form['criteria']['criteria_config']['widget'][0]['value']['#type'] = 'textarea';
            $form['criteria']['criteria_config']['widget'][0]['value']['#rows'] = 5;
            unset($form['criteria_config']);
        }

        if (isset($form['points'])) {
            $form['criteria']['points'] = $form['points'];
            unset($form['points']);
        }

        // Fieldset: Estado.
        $form['status_fieldset'] = [
            '#type' => 'details',
            '#title' => $this->t('Estado'),
            '#open' => TRUE,
            '#weight' => 2,
        ];

        if (isset($form['active'])) {
            $form['status_fieldset']['active'] = $form['active'];
            unset($form['active']);
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->entity;
        $status = parent::save($form, $form_state);

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Insignia %name creada.', [
                '%name' => $entity->label(),
            ]));
        }
        else {
            $this->messenger()->addStatus($this->t('Insignia %name actualizada.', [
                '%name' => $entity->label(),
            ]));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $status;
    }

}
