<?php

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar entidades EcaFlowDefinition.
 */
class EcaFlowDefinitionForm extends EntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state)
    {
        $form = parent::form($form, $form_state);

        /** @var \Drupal\ecosistema_jaraba_core\Entity\EcaFlowDefinitionInterface $eca_flow */
        $eca_flow = $this->entity;

        $form['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre del flujo'),
            '#maxlength' => 255,
            '#default_value' => $eca_flow->label(),
            '#description' => $this->t('Nombre legible del flujo ECA (ej: Onboarding Usuario Nuevo).'),
            '#required' => TRUE,
        ];

        $form['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $eca_flow->id(),
            '#machine_name' => [
                'exists' => '\Drupal\ecosistema_jaraba_core\Entity\EcaFlowDefinition::load',
            ],
            '#disabled' => !$eca_flow->isNew(),
            '#description' => $this->t('ID del flujo siguiendo la convencion eca_{dominio}_{numero} (ej: eca_usr_001).'),
        ];

        $form['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Descripcion'),
            '#default_value' => $eca_flow->getDescription(),
            '#description' => $this->t('Descripcion detallada del flujo y las acciones que ejecuta.'),
            '#rows' => 3,
        ];

        $form['domain'] = [
            '#type' => 'select',
            '#title' => $this->t('Dominio'),
            '#options' => [
                'USR' => $this->t('USR - Usuarios'),
                'ORD' => $this->t('ORD - Pedidos/Commerce'),
                'FIN' => $this->t('FIN - FOC/Financiero'),
                'TEN' => $this->t('TEN - Tenants'),
                'AI' => $this->t('AI - Inteligencia Artificial'),
                'WH' => $this->t('WH - Webhooks'),
                'MKT' => $this->t('MKT - Marketing'),
                'LMS' => $this->t('LMS - Learning'),
                'JOB' => $this->t('JOB - Empleabilidad'),
                'BIZ' => $this->t('BIZ - Emprendimiento'),
            ],
            '#default_value' => $eca_flow->getDomain(),
            '#required' => TRUE,
            '#description' => $this->t('Dominio funcional del flujo segun la convencion ECA-{DOMINIO}-{NUMERO}.'),
        ];

        $form['trigger_event'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Evento trigger'),
            '#maxlength' => 255,
            '#default_value' => $eca_flow->getTriggerEvent(),
            '#description' => $this->t('Evento que dispara este flujo (ej: hook_user_insert, commerce_order_complete, Cron).'),
            '#required' => TRUE,
        ];

        $form['module'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Modulo'),
            '#maxlength' => 255,
            '#default_value' => $eca_flow->getModule(),
            '#description' => $this->t('Modulo Drupal que implementa este flujo (ej: ecosistema_jaraba_core, jaraba_billing).'),
            '#required' => TRUE,
        ];

        $form['hook_function'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Funcion hook'),
            '#maxlength' => 255,
            '#default_value' => $eca_flow->getHookFunction(),
            '#description' => $this->t('Funcion hook concreta que ejecuta la logica (ej: ecosistema_jaraba_core_user_insert).'),
        ];

        $form['spec_reference'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Referencia de especificacion'),
            '#maxlength' => 255,
            '#default_value' => $eca_flow->getSpecReference(),
            '#description' => $this->t('Documento de especificacion de referencia (ej: 06_Core, 13_FOC, 145_AC).'),
        ];

        $form['implementation_status'] = [
            '#type' => 'select',
            '#title' => $this->t('Estado de implementacion'),
            '#options' => [
                'implemented' => $this->t('Implementado'),
                'partial' => $this->t('Parcial'),
                'pending' => $this->t('Pendiente'),
                'deprecated' => $this->t('Obsoleto'),
            ],
            '#default_value' => $eca_flow->getImplementationStatus(),
            '#required' => TRUE,
            '#description' => $this->t('Estado actual de implementacion del flujo en el codigo.'),
        ];

        $form['weight'] = [
            '#type' => 'number',
            '#title' => $this->t('Peso'),
            '#default_value' => $eca_flow->getWeight(),
            '#description' => $this->t('Orden de aparicion en el listado (menor = primero).'),
        ];

        $form['status'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Activo'),
            '#default_value' => $eca_flow->status(),
            '#description' => $this->t('Si esta desactivado, el flujo se considera deshabilitado en el ecosistema.'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $eca_flow = $this->entity;
        $status = $eca_flow->save();

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Flujo ECA %label creado.', [
                '%label' => $eca_flow->label(),
            ]));
        }
        else {
            $this->messenger()->addStatus($this->t('Flujo ECA %label actualizado.', [
                '%label' => $eca_flow->label(),
            ]));
        }

        $form_state->setRedirectUrl($eca_flow->toUrl('collection'));
    }

    /**
     * {@inheritdoc}
     */
    protected function actions(array $form, FormStateInterface $form_state)
    {
        $actions = parent::actions($form, $form_state);

        $actions['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Cancelar'),
            '#url' => $this->entity->toUrl('collection'),
            '#attributes' => [
                'class' => ['button'],
            ],
            '#weight' => 10,
        ];

        if (!$this->entity->isNew()) {
            $actions['delete'] = [
                '#type' => 'link',
                '#title' => $this->t('Eliminar'),
                '#url' => $this->entity->toUrl('delete-form'),
                '#attributes' => [
                    'class' => ['button', 'button--danger'],
                ],
                '#weight' => 20,
            ];
        }

        return $actions;
    }

}
