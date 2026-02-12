<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración del módulo AI Skills.
 */
class SkillsSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['jaraba_skills.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_skills_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config('jaraba_skills.settings');

        $form['general'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración General'),
            '#open' => TRUE,
        ];

        $form['general']['max_skills_per_prompt'] = [
            '#type' => 'number',
            '#title' => $this->t('Máximo de habilidades por prompt'),
            '#description' => $this->t('Límite de habilidades a incluir en cada prompt para optimizar tokens.'),
            '#default_value' => $config->get('max_skills_per_prompt') ?? 20,
            '#min' => 1,
            '#max' => 100,
        ];

        $form['general']['enable_caching'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar caché de resolución'),
            '#description' => $this->t('Cachea los resultados de resolución por contexto.'),
            '#default_value' => $config->get('enable_caching') ?? TRUE,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config('jaraba_skills.settings')
            ->set('max_skills_per_prompt', $form_state->getValue('max_skills_per_prompt'))
            ->set('enable_caching', $form_state->getValue('enable_caching'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
