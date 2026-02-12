<?php

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración del Page Builder.
 *
 * Permite configurar límites por defecto, opciones de plantillas,
 * y configuración general del constructor de páginas.
 */
class PageBuilderSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['jaraba_page_builder.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'jaraba_page_builder_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('jaraba_page_builder.settings');

        $form['general'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración General'),
            '#open' => TRUE,
        ];

        $form['general']['enable_page_builder'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar Page Builder'),
            '#default_value' => $config->get('enable_page_builder') ?? TRUE,
            '#description' => $this->t('Permite a los tenants crear páginas personalizadas.'),
        ];

        $form['limits'] = [
            '#type' => 'details',
            '#title' => $this->t('Límites por Defecto'),
            '#open' => TRUE,
            '#description' => $this->t('Estos límites se aplican cuando no hay configuración específica en SaasPlan o Vertical.'),
        ];

        $form['limits']['default_max_pages'] = [
            '#type' => 'number',
            '#title' => $this->t('Páginas máximas por tenant'),
            '#default_value' => $config->get('default_max_pages') ?? 5,
            '#min' => 1,
            '#max' => 100,
        ];

        $form['limits']['default_max_blocks_per_page'] = [
            '#type' => 'number',
            '#title' => $this->t('Bloques máximos por página'),
            '#default_value' => $config->get('default_max_blocks_per_page') ?? 10,
            '#min' => 1,
            '#max' => 50,
        ];

        $form['templates'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración de Plantillas'),
            '#open' => TRUE,
        ];

        $form['templates']['show_premium_badge'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Mostrar badge premium en plantillas'),
            '#default_value' => $config->get('show_premium_badge') ?? TRUE,
            '#description' => $this->t('Muestra un indicador en plantillas premium.'),
        ];

        $form['templates']['enable_template_preview'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Habilitar vista previa de plantillas'),
            '#default_value' => $config->get('enable_template_preview') ?? TRUE,
        ];

        $form['templates']['templates_per_page'] = [
            '#type' => 'number',
            '#title' => $this->t('Plantillas por página'),
            '#default_value' => $config->get('templates_per_page') ?? 12,
            '#min' => 6,
            '#max' => 48,
            '#description' => $this->t('Número de plantillas a mostrar en el selector.'),
        ];

        $form['seo'] = [
            '#type' => 'details',
            '#title' => $this->t('SEO y Metadatos'),
            '#open' => FALSE,
        ];

        $form['seo']['auto_generate_meta'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Generar meta tags automáticamente'),
            '#default_value' => $config->get('auto_generate_meta') ?? TRUE,
        ];

        $form['seo']['enable_schema_org'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Generar datos estructurados (Schema.org)'),
            '#default_value' => $config->get('enable_schema_org') ?? TRUE,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config('jaraba_page_builder.settings')
            ->set('enable_page_builder', $form_state->getValue('enable_page_builder'))
            ->set('default_max_pages', $form_state->getValue('default_max_pages'))
            ->set('default_max_blocks_per_page', $form_state->getValue('default_max_blocks_per_page'))
            ->set('show_premium_badge', $form_state->getValue('show_premium_badge'))
            ->set('enable_template_preview', $form_state->getValue('enable_template_preview'))
            ->set('templates_per_page', $form_state->getValue('templates_per_page'))
            ->set('auto_generate_meta', $form_state->getValue('auto_generate_meta'))
            ->set('enable_schema_org', $form_state->getValue('enable_schema_org'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
