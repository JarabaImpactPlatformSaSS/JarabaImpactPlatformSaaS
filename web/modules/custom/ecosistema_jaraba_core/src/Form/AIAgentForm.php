<?php

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar entidades AIAgent.
 */
class AIAgentForm extends EntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state)
    {
        $form = parent::form($form, $form_state);

        /** @var \Drupal\ecosistema_jaraba_core\Entity\AIAgentInterface $agent */
        $agent = $this->entity;

        $form['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre'),
            '#maxlength' => 255,
            '#default_value' => $agent->label(),
            '#description' => $this->t('Nombre visible del agente IA.'),
            '#required' => TRUE,
        ];

        $form['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $agent->id(),
            '#machine_name' => [
                'exists' => '\Drupal\ecosistema_jaraba_core\Entity\AIAgent::load',
            ],
            '#disabled' => !$agent->isNew(),
        ];

        $form['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Descripción'),
            '#default_value' => $agent->getDescription(),
            '#description' => $this->t('Descripción de las capacidades del agente.'),
            '#rows' => 3,
        ];

        $form['service_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('ID del Servicio'),
            '#default_value' => $agent->getServiceId(),
            '#description' => $this->t('Nombre del servicio Drupal que implementa este agente (ej: ecosistema_jaraba_core.marketing_agent).'),
            '#maxlength' => 255,
        ];

        $form['icon'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Icono'),
            '#maxlength' => 100,
            '#default_value' => $agent->getIcon(),
            '#description' => $this->t('Nombre del icono (ej: robot, brain, sparkles).'),
        ];

        $form['color'] = [
            '#type' => 'color',
            '#title' => $this->t('Color'),
            '#default_value' => $agent->getColor(),
            '#description' => $this->t('Color del agente para la UI.'),
        ];

        $form['weight'] = [
            '#type' => 'number',
            '#title' => $this->t('Peso'),
            '#default_value' => $agent->getWeight(),
            '#description' => $this->t('Orden de aparición (menor = primero).'),
        ];

        $form['status'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitado'),
            '#default_value' => $agent->status(),
            '#description' => $this->t('Si está deshabilitado, no aparece como opción en las verticales.'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $agent = $this->entity;
        $status = $agent->save();

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Agente IA %label creado.', [
                '%label' => $agent->label(),
            ]));
        } else {
            $this->messenger()->addStatus($this->t('Agente IA %label actualizado.', [
                '%label' => $agent->label(),
            ]));
        }

        $form_state->setRedirectUrl($agent->toUrl('collection'));
    }

}
