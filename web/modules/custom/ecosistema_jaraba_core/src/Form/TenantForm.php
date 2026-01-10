<?php

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * Formulario para crear/editar entidades Tenant.
 */
class TenantForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildForm($form, $form_state);

        /** @var \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $entity */
        $entity = $this->entity;
        $is_new = $entity->isNew();

        // Información básica.
        $form['basic'] = [
            '#type' => 'details',
            '#title' => $this->t('Información del Tenant'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        $form['basic']['name'] = $form['name'];
        $form['basic']['vertical'] = $form['vertical'];
        $form['basic']['domain'] = $form['domain'];
        $form['basic']['admin_user'] = $form['admin_user'];
        unset($form['name'], $form['vertical'], $form['domain'], $form['admin_user']);

        // Suscripción.
        $form['subscription'] = [
            '#type' => 'details',
            '#title' => $this->t('Suscripción'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        $form['subscription']['subscription_plan'] = $form['subscription_plan'];
        $form['subscription']['subscription_status'] = $form['subscription_status'];
        $form['subscription']['trial_ends'] = $form['trial_ends'];
        $form['subscription']['current_period_end'] = $form['current_period_end'];
        unset($form['subscription_plan'], $form['subscription_status'], $form['trial_ends'], $form['current_period_end']);

        // Stripe (solo para admins).
        if ($this->currentUser()->hasPermission('administer tenants')) {
            $form['stripe'] = [
                '#type' => 'details',
                '#title' => $this->t('Configuración Stripe'),
                '#open' => FALSE,
                '#weight' => 10,
            ];

            $form['stripe']['stripe_customer_id'] = $form['stripe_customer_id'];
            $form['stripe']['stripe_subscription_id'] = $form['stripe_subscription_id'];
            $form['stripe']['stripe_connect_id'] = $form['stripe_connect_id'];
            unset($form['stripe_customer_id'], $form['stripe_subscription_id'], $form['stripe_connect_id']);
        } else {
            // Ocultar campos de Stripe para no admins.
            $form['stripe_customer_id']['#access'] = FALSE;
            $form['stripe_subscription_id']['#access'] = FALSE;
            $form['stripe_connect_id']['#access'] = FALSE;
        }

        // Personalización de tema.
        $form['theming'] = [
            '#type' => 'details',
            '#title' => $this->t('Personalización de Marca'),
            '#open' => FALSE,
            '#weight' => 20,
        ];

        if (isset($form['theme_overrides'])) {
            $form['theming']['theme_overrides'] = $form['theme_overrides'];
            $form['theming']['theme_overrides']['widget'][0]['value']['#description'] = $this->t('JSON con personalizaciones. Ejemplo: {"color_primary": "#10B981", "logo": "/path/to/logo.png"}');
            unset($form['theme_overrides']);
        }

        // Mostrar información de estado si no es nuevo.
        if (!$is_new) {
            $status = $entity->getSubscriptionStatus();
            $status_labels = [
                TenantInterface::STATUS_PENDING => $this->t('⏳ Pendiente de activación'),
                TenantInterface::STATUS_TRIAL => $this->t('🔵 En período de prueba'),
                TenantInterface::STATUS_ACTIVE => $this->t('✅ Activo'),
                TenantInterface::STATUS_PAST_DUE => $this->t('⚠️ Pago pendiente'),
                TenantInterface::STATUS_SUSPENDED => $this->t('🔴 Suspendido'),
                TenantInterface::STATUS_CANCELLED => $this->t('❌ Cancelado'),
            ];

            $form['status_info'] = [
                '#type' => 'container',
                '#weight' => -100,
                '#attributes' => [
                    'class' => ['messages', 'messages--status'],
                ],
                'content' => [
                    '#markup' => '<strong>' . $this->t('Estado actual:') . '</strong> ' . ($status_labels[$status] ?? $status),
                ],
            ];
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);

        // Validar que el dominio tenga formato correcto.
        $domain = $form_state->getValue(['domain', 0, 'value']);
        if ($domain) {
            // Permitir subdominios de jaraba.io o dominios propios.
            if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*\.[a-z]{2,}$/i', $domain)) {
                $form_state->setErrorByName('domain', $this->t('El dominio no tiene un formato válido.'));
            }
        }

        // Validar JSON de theme_overrides.
        $theme_overrides = $form_state->getValue(['theme_overrides', 0, 'value']);
        if ($theme_overrides && json_decode($theme_overrides) === NULL) {
            $form_state->setErrorByName('theme_overrides', $this->t('Las personalizaciones de tema deben ser un JSON válido.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $result = parent::save($form, $form_state);

        $entity = $this->entity;
        $message_arguments = ['%label' => $entity->label()];

        if ($result == SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Tenant %label creado correctamente.', $message_arguments));
            $this->logger('ecosistema_jaraba_core')->notice('Nuevo tenant creado: %label', $message_arguments);
        } else {
            $this->messenger()->addStatus($this->t('Tenant %label actualizado.', $message_arguments));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}
