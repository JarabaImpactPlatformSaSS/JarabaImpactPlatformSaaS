<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Content Hub module.
 */
class ContentHubSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['jaraba_content_hub.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_content_hub_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config('jaraba_content_hub.settings');

        $form['general'] = [
            '#type' => 'details',
            '#title' => $this->t('General Settings'),
            '#open' => TRUE,
        ];

        $form['general']['blog_title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Blog Title'),
            '#default_value' => $config->get('blog_title') ?? 'Blog',
            '#description' => $this->t('The title displayed on the blog homepage.'),
        ];

        $form['general']['articles_per_page'] = [
            '#type' => 'number',
            '#title' => $this->t('Articles per page'),
            '#default_value' => $config->get('articles_per_page') ?? 12,
            '#min' => 1,
            '#max' => 50,
        ];

        $form['general']['show_reading_time'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Show reading time'),
            '#default_value' => $config->get('show_reading_time') ?? TRUE,
        ];

        $form['seo'] = [
            '#type' => 'details',
            '#title' => $this->t('SEO Settings'),
            '#open' => TRUE,
        ];

        $form['seo']['default_og_image'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Default OG Image'),
            '#default_value' => $config->get('default_og_image') ?? '',
            '#description' => $this->t('Default Open Graph image URL for articles without featured image.'),
        ];

        $form['seo']['schema_org_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable Schema.org markup'),
            '#default_value' => $config->get('schema_org_enabled') ?? TRUE,
        ];

        $form['ai'] = [
            '#type' => 'details',
            '#title' => $this->t('AI Settings'),
            '#open' => TRUE,
        ];

        $form['ai']['ai_generation_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable AI content generation'),
            '#default_value' => $config->get('ai_generation_enabled') ?? TRUE,
        ];

        $form['ai']['max_tokens_per_article'] = [
            '#type' => 'number',
            '#title' => $this->t('Max tokens per article generation'),
            '#default_value' => $config->get('max_tokens_per_article') ?? 4000,
            '#min' => 500,
            '#max' => 16000,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config('jaraba_content_hub.settings')
            ->set('blog_title', $form_state->getValue('blog_title'))
            ->set('articles_per_page', $form_state->getValue('articles_per_page'))
            ->set('show_reading_time', $form_state->getValue('show_reading_time'))
            ->set('default_og_image', $form_state->getValue('default_og_image'))
            ->set('schema_org_enabled', $form_state->getValue('schema_org_enabled'))
            ->set('ai_generation_enabled', $form_state->getValue('ai_generation_enabled'))
            ->set('max_tokens_per_article', $form_state->getValue('max_tokens_per_article'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
