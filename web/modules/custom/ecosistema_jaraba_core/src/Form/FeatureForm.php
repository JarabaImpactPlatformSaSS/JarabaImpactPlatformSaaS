<?php

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar entidades Feature.
 */
class FeatureForm extends EntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state)
    {
        $form = parent::form($form, $form_state);

        /** @var \Drupal\ecosistema_jaraba_core\Entity\FeatureInterface $feature */
        $feature = $this->entity;

        $form['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre'),
            '#maxlength' => 255,
            '#default_value' => $feature->label(),
            '#description' => $this->t('Nombre visible de la feature.'),
            '#required' => TRUE,
        ];

        $form['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $feature->id(),
            '#machine_name' => [
                'exists' => '\Drupal\ecosistema_jaraba_core\Entity\Feature::load',
            ],
            '#disabled' => !$feature->isNew(),
        ];

        $form['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Descripción'),
            '#default_value' => $feature->getDescription(),
            '#description' => $this->t('Descripción de lo que hace esta feature.'),
            '#rows' => 3,
        ];

        $form['category'] = [
            '#type' => 'select',
            '#title' => $this->t('Categoría'),
            '#options' => [
                'general' => $this->t('General'),
                'integraciones' => $this->t('Integraciones'),
                'ia' => $this->t('Inteligencia Artificial'),
                'comercio' => $this->t('Comercio'),
                'seguridad' => $this->t('Seguridad y Certificación'),
            ],
            '#default_value' => $feature->getCategory(),
        ];

        $form['icon'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Icono'),
            '#maxlength' => 100,
            '#default_value' => $feature->getIcon(),
            '#description' => $this->t('Nombre del icono (ej: star, check-circle).'),
        ];

        $form['weight'] = [
            '#type' => 'number',
            '#title' => $this->t('Peso'),
            '#default_value' => $feature->getWeight(),
            '#description' => $this->t('Orden de aparición (menor = primero).'),
        ];

        $form['status'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitada'),
            '#default_value' => $feature->status(),
            '#description' => $this->t('Si está deshabilitada, no aparece como opción en las verticales.'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $feature = $this->entity;
        $status = $feature->save();

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Feature %label creada.', [
                '%label' => $feature->label(),
            ]));
        } else {
            $this->messenger()->addStatus($this->t('Feature %label actualizada.', [
                '%label' => $feature->label(),
            ]));
        }

        $form_state->setRedirectUrl($feature->toUrl('collection'));
    }

}
