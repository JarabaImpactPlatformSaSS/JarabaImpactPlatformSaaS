<?php

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar entidades FreemiumVerticalLimit.
 *
 * Permite configurar los limites freemium especificos por vertical,
 * plan y feature del ecosistema SaaS. Cada registro define cuanto
 * puede usar un tenant en una combinacion vertical+plan+feature
 * antes de que se dispare un trigger de upgrade.
 */
class FreemiumVerticalLimitForm extends EntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state)
    {
        $form = parent::form($form, $form_state);

        /** @var \Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimitInterface $limit */
        $limit = $this->entity;

        $form['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre del limite'),
            '#maxlength' => 255,
            '#default_value' => $limit->label(),
            '#description' => $this->t('Nombre legible del limite (ej: AgroConecta Free - Productos).'),
            '#required' => TRUE,
        ];

        $form['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $limit->id(),
            '#machine_name' => [
                'exists' => '\Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimit::load',
            ],
            '#disabled' => !$limit->isNew(),
            '#description' => $this->t('ID unico siguiendo la convencion {vertical}_{plan}_{feature_key} (ej: agroconecta_free_products).'),
        ];

        // =====================================================================
        // IDENTIFICACION: Vertical + Plan + Feature Key
        // =====================================================================
        $form['identification'] = [
            '#type' => 'details',
            '#title' => $this->t('Identificacion'),
            '#open' => TRUE,
            '#description' => $this->t('Define la combinacion unica de vertical, plan y recurso limitado.'),
        ];

        $form['identification']['vertical'] = [
            '#type' => 'select',
            '#title' => $this->t('Vertical'),
            '#options' => [
                'agroconecta' => $this->t('AgroConecta'),
                'comercioconecta' => $this->t('ComercioConecta'),
                'serviciosconecta' => $this->t('ServiciosConecta'),
                'empleabilidad' => $this->t('Empleabilidad'),
                'emprendimiento' => $this->t('Emprendimiento'),
            ],
            '#default_value' => $limit->getVertical(),
            '#required' => TRUE,
            '#description' => $this->t('Vertical de negocio a la que aplica este limite.'),
        ];

        $form['identification']['plan'] = [
            '#type' => 'select',
            '#title' => $this->t('Plan'),
            '#options' => [
                'free' => $this->t('Free'),
                'starter' => $this->t('Starter'),
                'profesional' => $this->t('Profesional'),
                'business' => $this->t('Business'),
                'enterprise' => $this->t('Enterprise'),
            ],
            '#default_value' => $limit->getPlan(),
            '#required' => TRUE,
            '#description' => $this->t('Plan SaaS al que aplica este limite.'),
        ];

        $form['identification']['feature_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Clave del recurso'),
            '#maxlength' => 128,
            '#default_value' => $limit->getFeatureKey(),
            '#required' => TRUE,
            '#description' => $this->t('Clave del recurso limitado (ej: products, orders_per_month, copilot_uses_per_month, job_applications_per_month).'),
        ];

        // =====================================================================
        // CONFIGURACION DEL LIMITE
        // =====================================================================
        $form['limit_config'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuracion del limite'),
            '#open' => TRUE,
        ];

        $form['limit_config']['limit_value'] = [
            '#type' => 'number',
            '#title' => $this->t('Valor del limite'),
            '#default_value' => $limit->getLimitValue(),
            '#required' => TRUE,
            '#description' => $this->t('Cantidad maxima permitida. Usa -1 para ilimitado, 0 para no incluido en el plan.'),
            '#min' => -1,
        ];

        $form['limit_config']['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Descripcion'),
            '#default_value' => $limit->getDescription(),
            '#description' => $this->t('Descripcion interna del limite para administradores.'),
            '#rows' => 2,
        ];

        // =====================================================================
        // UPGRADE TRIGGER
        // =====================================================================
        $form['upgrade'] = [
            '#type' => 'details',
            '#title' => $this->t('Trigger de upgrade'),
            '#open' => FALSE,
            '#description' => $this->t('Configuracion del mensaje y conversion esperada al alcanzar el limite.'),
        ];

        $form['upgrade']['upgrade_message'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Mensaje de upgrade'),
            '#default_value' => $limit->getUpgradeMessage(),
            '#description' => $this->t('Mensaje mostrado al usuario cuando alcanza el limite. Soporta tokens: @vertical, @plan, @limit.'),
            '#rows' => 3,
        ];

        $form['upgrade']['expected_conversion'] = [
            '#type' => 'number',
            '#title' => $this->t('Tasa de conversion esperada'),
            '#default_value' => $limit->getExpectedConversion(),
            '#description' => $this->t('Tasa de conversion esperada entre 0.00 y 1.00 (ej: 0.12 = 12%).'),
            '#min' => 0,
            '#max' => 1,
            '#step' => 0.01,
        ];

        // =====================================================================
        // ORDEN Y ESTADO
        // =====================================================================
        $form['weight'] = [
            '#type' => 'number',
            '#title' => $this->t('Peso'),
            '#default_value' => $limit->getWeight(),
            '#description' => $this->t('Orden de aparicion en el listado (menor = primero).'),
        ];

        $form['status'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Activo'),
            '#default_value' => $limit->status(),
            '#description' => $this->t('Si esta desactivado, este limite no se aplica y el recurso queda sin restriccion.'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $limit = $this->entity;
        $status = $limit->save();

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Limite freemium %label creado.', [
                '%label' => $limit->label(),
            ]));
        }
        else {
            $this->messenger()->addStatus($this->t('Limite freemium %label actualizado.', [
                '%label' => $limit->label(),
            ]));
        }

        $form_state->setRedirectUrl($limit->toUrl('collection'));
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
