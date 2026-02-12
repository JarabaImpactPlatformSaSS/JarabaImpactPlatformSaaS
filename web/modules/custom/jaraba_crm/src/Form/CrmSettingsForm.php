<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración global del CRM.
 */
class CrmSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['jaraba_crm.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_crm_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config('jaraba_crm.settings');

        $form['dashboard'] = [
            '#type' => 'details',
            '#title' => $this->t('Dashboard'),
            '#open' => TRUE,
        ];

        $form['dashboard']['show_pipeline'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Mostrar pipeline en el dashboard'),
            '#default_value' => $config->get('show_pipeline') ?? TRUE,
        ];

        $form['dashboard']['recent_activities_count'] = [
            '#type' => 'number',
            '#title' => $this->t('Número de actividades recientes'),
            '#default_value' => $config->get('recent_activities_count') ?? 10,
            '#min' => 1,
            '#max' => 50,
        ];

        $form['engagement'] = [
            '#type' => 'details',
            '#title' => $this->t('Engagement Scoring'),
            '#open' => FALSE,
        ];

        $form['engagement']['enable_auto_scoring'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar scoring automático'),
            '#description' => $this->t('Actualizar el engagement score de contactos automáticamente basado en actividades.'),
            '#default_value' => $config->get('enable_auto_scoring') ?? TRUE,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config('jaraba_crm.settings')
            ->set('show_pipeline', $form_state->getValue('show_pipeline'))
            ->set('recent_activities_count', $form_state->getValue('recent_activities_count'))
            ->set('enable_auto_scoring', $form_state->getValue('enable_auto_scoring'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
