<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the Mentor Profile entity.
 */
class MentorProfileForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        /** @var \Drupal\jaraba_mentoring\Entity\MentorProfile $entity */
        $entity = $this->entity;

        // === Header visual ===
        $form['header'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['mentor-form-header']],
            '#weight' => -100,
        ];

        if (!$entity->isNew()) {
            $form['header']['status_badge'] = [
                '#markup' => '<span class="status-badge status-' . $entity->get('status')->value . '">'
                    . $this->t('Estado: @status', ['@status' => $entity->get('status')->value])
                    . '</span>',
            ];
        }

        // === Agrupar campos en fieldsets ===
        $form['profile_info'] = [
            '#type' => 'details',
            '#title' => $this->t('Información del Perfil'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        // Mover campos al fieldset.
        $profile_fields = ['display_name', 'headline', 'bio', 'avatar'];
        foreach ($profile_fields as $field) {
            if (isset($form[$field])) {
                $form['profile_info'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        $form['expertise'] = [
            '#type' => 'details',
            '#title' => $this->t('Experiencia y Especialización'),
            '#open' => TRUE,
            '#weight' => 5,
        ];

        $expertise_fields = ['specializations', 'sectors', 'business_stages'];
        foreach ($expertise_fields as $field) {
            if (isset($form[$field])) {
                $form['expertise'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        $form['pricing'] = [
            '#type' => 'details',
            '#title' => $this->t('Tarifas'),
            '#open' => TRUE,
            '#weight' => 10,
        ];

        $pricing_fields = ['hourly_rate', 'currency', 'platform_fee_percent'];
        foreach ($pricing_fields as $field) {
            if (isset($form[$field])) {
                $form['pricing'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        $form['settings'] = [
            '#type' => 'details',
            '#title' => $this->t('Estado y Configuración'),
            '#open' => FALSE,
            '#weight' => 20,
        ];

        $settings_fields = ['certification_level', 'is_available', 'status', 'tenant_id'];
        foreach ($settings_fields as $field) {
            if (isset($form[$field])) {
                $form['settings'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Ocultar campos de Stripe para edición normal (solo admin).
        if (!$this->currentUser()->hasPermission('administer mentor profiles')) {
            $form['stripe_account_id']['#access'] = FALSE;
            $form['stripe_onboarding_complete']['#access'] = FALSE;
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $entity = $this->entity;
        $message_args = ['%label' => $entity->toLink($entity->label())->toString()];

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Perfil de mentor %label creado.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('Perfil de mentor %label actualizado.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}
