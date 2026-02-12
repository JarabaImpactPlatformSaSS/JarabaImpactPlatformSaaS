<?php

declare(strict_types=1);

namespace Drupal\ai_provider_google_gemini\Form;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\Service\AiProviderFormHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Google Gemini (AI Studio) provider.
 */
class GoogleGeminiConfigForm extends ConfigFormBase
{

    /**
     * The AI provider plugin manager.
     */
    protected AiProviderPluginManager $aiProviderManager;

    /**
     * The AI provider form helper.
     */
    protected AiProviderFormHelper $formHelper;

    /**
     * The key repository.
     */
    protected KeyRepositoryInterface $keyRepository;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = parent::create($container);
        $instance->aiProviderManager = $container->get('ai.provider');
        $instance->formHelper = $container->get('ai.form_helper');
        $instance->keyRepository = $container->get('key.repository');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['ai_provider_google_gemini.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'ai_provider_google_gemini_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config('ai_provider_google_gemini.settings');

        $form['api_key'] = [
            '#type' => 'key_select',
            '#title' => $this->t('Google AI Studio API Key'),
            '#description' => $this->t('Select the API key from the Key module. <a href="/admin/config/system/keys/add">Add a new key</a> if needed.'),
            '#default_value' => $config->get('api_key'),
            '#required' => TRUE,
        ];

        $form['info'] = [
            '#type' => 'details',
            '#title' => $this->t('API Information'),
            '#open' => TRUE,
        ];

        $form['info']['models'] = [
            '#type' => 'item',
            '#title' => $this->t('Available Models'),
            '#markup' => '
<table class="table">
  <thead><tr><th>Modelo</th><th>Descripción</th></tr></thead>
  <tbody>
    <tr><td><strong>gemini-3-pro-preview</strong></td><td>Most Powerful - Preview (1M tokens)</td></tr>
    <tr><td><strong>gemini-2.5-pro</strong></td><td>Reasoning & Complex Tasks (Stable)</td></tr>
    <tr><td><strong>gemini-2.5-flash</strong></td><td>Best Price/Performance</td></tr>
    <tr><td><strong>gemini-2.5-flash-lite</strong></td><td>Cost Optimized</td></tr>
    <tr><td><strong>gemini-2.0-flash</strong></td><td>Previous Generation</td></tr>
    <tr><td><strong>gemini-2.0-flash-lite</strong></td><td>Previous Generation Lite</td></tr>
  </tbody>
</table>',
        ];

        $form['info']['endpoint'] = [
            '#type' => 'item',
            '#title' => $this->t('API Endpoint'),
            '#markup' => '<code>https://generativelanguage.googleapis.com/v1beta/models</code>',
        ];

        // Status section.
        $form['status'] = [
            '#type' => 'details',
            '#title' => $this->t('Connection Status'),
            '#open' => TRUE,
        ];

        $apiKey = $config->get('api_key');
        if ($apiKey) {
            $key = $this->keyRepository->getKey($apiKey);
            if ($key && $key->getKeyValue()) {
                $form['status']['connection'] = [
                    '#markup' => '<p>✅ ' . $this->t('API Key configured') . '</p>',
                ];
            } else {
                $form['status']['connection'] = [
                    '#markup' => '<p>❌ ' . $this->t('API Key not found or empty') . '</p>',
                ];
            }
        } else {
            $form['status']['connection'] = [
                '#markup' => '<p>⚠️ ' . $this->t('No API Key selected') . '</p>',
            ];
        }

        // Load provider and show models form if available.
        try {
            $provider = $this->aiProviderManager->createInstance('google_gemini');
            if ($provider && method_exists($this->formHelper, 'getModelsTable')) {
                $form['models_config'] = $this->formHelper->getModelsTable($form, $form_state, $provider);
            }
        } catch (\Exception $e) {
            // Provider not yet available, that's fine.
        }

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config('ai_provider_google_gemini.settings')
            ->set('api_key', $form_state->getValue('api_key'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
