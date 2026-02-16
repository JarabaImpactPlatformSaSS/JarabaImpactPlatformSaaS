<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion del Legal Intelligence Hub.
 *
 * Permite editar URLs de servicios (Qdrant, Tika, NLP), thresholds de busqueda,
 * limites por plan SaaS, boosts de merge & rank, y prompts de IA.
 * DIRECTRIZ: No hardcodear configuracion — todo editable desde UI.
 */
class LegalSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_legal_intelligence.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_legal_intelligence_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_legal_intelligence.settings');

    // === Servicios externos ===
    $form['services'] = [
      '#type' => 'details',
      '#title' => $this->t('External services'),
      '#open' => TRUE,
    ];

    $form['services']['qdrant_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Qdrant URL'),
      '#description' => $this->t('Base URL of the Qdrant vector database.'),
      '#default_value' => $config->get('qdrant_url') ?: 'http://qdrant:6333',
      '#required' => TRUE,
    ];

    $form['services']['qdrant_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Qdrant API Key'),
      '#description' => $this->t('API key for Qdrant authentication. Leave empty for local development.'),
      '#default_value' => $config->get('qdrant_api_key') ?: '',
    ];

    $form['services']['nlp_service_url'] = [
      '#type' => 'url',
      '#title' => $this->t('NLP Service URL'),
      '#description' => $this->t('Base URL of the FastAPI NLP pipeline service.'),
      '#default_value' => $config->get('nlp_service_url') ?: 'http://legal-nlp:8001',
      '#required' => TRUE,
    ];

    $form['services']['tika_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Apache Tika URL'),
      '#description' => $this->t('Base URL of the Apache Tika text extraction service.'),
      '#default_value' => $config->get('tika_url') ?: 'http://tika:9998',
      '#required' => TRUE,
    ];

    // === Busqueda ===
    $form['search'] = [
      '#type' => 'details',
      '#title' => $this->t('Search settings'),
      '#open' => TRUE,
    ];

    $form['search']['score_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum score threshold'),
      '#description' => $this->t('Minimum Qdrant similarity score (0.0-1.0) for search results.'),
      '#default_value' => $config->get('score_threshold') ?: 0.65,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['search']['max_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum results'),
      '#description' => $this->t('Maximum number of search results to return.'),
      '#default_value' => $config->get('max_results') ?: 20,
      '#min' => 1,
      '#max' => 100,
      '#required' => TRUE,
    ];

    $form['search']['rate_limit_searches_per_hour'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate limit (searches/hour)'),
      '#description' => $this->t('Maximum searches per hour per user.'),
      '#default_value' => $config->get('rate_limit_searches_per_hour') ?: 100,
      '#min' => 1,
      '#required' => TRUE,
    ];

    // === Merge & Rank ===
    $form['merge_rank'] = [
      '#type' => 'details',
      '#title' => $this->t('Merge & Rank settings'),
    ];

    $form['merge_rank']['eu_primacy_boost'] = [
      '#type' => 'number',
      '#title' => $this->t('EU primacy boost'),
      '#description' => $this->t('Score boost for TJUE/TEDH results (EU law primacy).'),
      '#default_value' => $config->get('eu_primacy_boost') ?: 0.05,
      '#min' => 0,
      '#max' => 0.5,
      '#step' => 0.01,
    ];

    $form['merge_rank']['freshness_boost'] = [
      '#type' => 'number',
      '#title' => $this->t('Freshness boost'),
      '#description' => $this->t('Score boost for recent resolutions.'),
      '#default_value' => $config->get('freshness_boost') ?: 0.02,
      '#min' => 0,
      '#max' => 0.5,
      '#step' => 0.01,
    ];

    $form['merge_rank']['freshness_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Freshness window (days)'),
      '#description' => $this->t('Number of days to consider a resolution as recent.'),
      '#default_value' => $config->get('freshness_days') ?: 365,
      '#min' => 1,
    ];

    // === Prompts IA ===
    $form['prompts'] = [
      '#type' => 'details',
      '#title' => $this->t('AI Prompts'),
      '#description' => $this->t('Editable prompts for Gemini 2.0 Flash. Changes take effect immediately.'),
    ];

    $form['prompts']['classification_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Classification prompt'),
      '#description' => $this->t('System prompt for legal resolution classification (jurisdiction, topics, type, importance).'),
      '#default_value' => $config->get('classification_prompt') ?: '',
      '#rows' => 10,
    ];

    $form['prompts']['summary_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Summary prompt'),
      '#description' => $this->t('System prompt for generating abstract, key holdings and cited legislation.'),
      '#default_value' => $config->get('summary_prompt') ?: '',
      '#rows' => 10,
    ];

    $form['prompts']['eu_classification_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('EU classification prompt'),
      '#description' => $this->t('System prompt for classifying EU/CJEU/ECHR resolutions. If empty, uses national prompt.'),
      '#default_value' => $config->get('eu_classification_prompt') ?: '',
      '#rows' => 10,
    ];

    $form['prompts']['eu_summary_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('EU summary prompt'),
      '#description' => $this->t('System prompt for EU summaries. Should include impact_spain generation. If empty, uses national prompt.'),
      '#default_value' => $config->get('eu_summary_prompt') ?: '',
      '#rows' => 10,
    ];

    // === Pipeline NLP ===
    $form['nlp_pipeline'] = [
      '#type' => 'details',
      '#title' => $this->t('NLP Pipeline settings'),
      '#description' => $this->t('Configuration for the 9-stage NLP processing pipeline.'),
    ];

    $form['nlp_pipeline']['nlp_chunk_max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Chunk max tokens'),
      '#description' => $this->t('Maximum tokens per text chunk for embedding generation.'),
      '#default_value' => $config->get('nlp_chunk_max_tokens') ?: 512,
      '#min' => 64,
      '#max' => 2048,
    ];

    $form['nlp_pipeline']['nlp_chunk_overlap_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Chunk overlap tokens'),
      '#description' => $this->t('Token overlap between consecutive chunks for context continuity.'),
      '#default_value' => $config->get('nlp_chunk_overlap_tokens') ?: 50,
      '#min' => 0,
      '#max' => 512,
    ];

    $form['nlp_pipeline']['nlp_max_text_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Max text length (chars)'),
      '#description' => $this->t('Maximum characters sent to Python NLP service for segmentation and NER.'),
      '#default_value' => $config->get('nlp_max_text_length') ?: 50000,
      '#min' => 1000,
    ];

    $form['nlp_pipeline']['nlp_tika_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Tika timeout (seconds)'),
      '#default_value' => $config->get('nlp_tika_timeout') ?: 60,
      '#min' => 5,
    ];

    $form['nlp_pipeline']['nlp_python_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Python NLP timeout (seconds)'),
      '#default_value' => $config->get('nlp_python_timeout') ?: 120,
      '#min' => 10,
    ];

    $form['nlp_pipeline']['nlp_ai_temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('AI temperature'),
      '#description' => $this->t('Temperature for classification and summary generation (0.0-1.0). Lower = more deterministic.'),
      '#default_value' => $config->get('nlp_ai_temperature') ?: 0.2,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.05,
    ];

    $form['nlp_pipeline']['nlp_ai_max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('AI max output tokens'),
      '#default_value' => $config->get('nlp_ai_max_tokens') ?: 2000,
      '#min' => 100,
      '#max' => 8000,
    ];

    $form['nlp_pipeline']['nlp_classification_max_chars'] = [
      '#type' => 'number',
      '#title' => $this->t('Classification text limit (chars)'),
      '#description' => $this->t('Maximum characters sent to AI model for classification.'),
      '#default_value' => $config->get('nlp_classification_max_chars') ?: 8000,
      '#min' => 1000,
    ];

    $form['nlp_pipeline']['nlp_summary_max_chars'] = [
      '#type' => 'number',
      '#title' => $this->t('Summary text limit (chars)'),
      '#description' => $this->t('Maximum characters sent to AI model for summarization.'),
      '#default_value' => $config->get('nlp_summary_max_chars') ?: 12000,
      '#min' => 1000,
    ];

    // === Sincronizacion ===
    $form['sync'] = [
      '#type' => 'details',
      '#title' => $this->t('Sync settings'),
    ];

    $form['sync']['default_sync_interval_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Default sync interval (hours)'),
      '#description' => $this->t('Default interval between source synchronizations.'),
      '#default_value' => $config->get('default_sync_interval_hours') ?: 24,
      '#min' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_legal_intelligence.settings')
      ->set('qdrant_url', $form_state->getValue('qdrant_url'))
      ->set('qdrant_api_key', $form_state->getValue('qdrant_api_key'))
      ->set('nlp_service_url', $form_state->getValue('nlp_service_url'))
      ->set('tika_url', $form_state->getValue('tika_url'))
      ->set('score_threshold', (float) $form_state->getValue('score_threshold'))
      ->set('max_results', (int) $form_state->getValue('max_results'))
      ->set('rate_limit_searches_per_hour', (int) $form_state->getValue('rate_limit_searches_per_hour'))
      ->set('eu_primacy_boost', (float) $form_state->getValue('eu_primacy_boost'))
      ->set('freshness_boost', (float) $form_state->getValue('freshness_boost'))
      ->set('freshness_days', (int) $form_state->getValue('freshness_days'))
      ->set('classification_prompt', $form_state->getValue('classification_prompt'))
      ->set('summary_prompt', $form_state->getValue('summary_prompt'))
      ->set('eu_classification_prompt', $form_state->getValue('eu_classification_prompt'))
      ->set('eu_summary_prompt', $form_state->getValue('eu_summary_prompt'))
      ->set('nlp_chunk_max_tokens', (int) $form_state->getValue('nlp_chunk_max_tokens'))
      ->set('nlp_chunk_overlap_tokens', (int) $form_state->getValue('nlp_chunk_overlap_tokens'))
      ->set('nlp_max_text_length', (int) $form_state->getValue('nlp_max_text_length'))
      ->set('nlp_tika_timeout', (int) $form_state->getValue('nlp_tika_timeout'))
      ->set('nlp_python_timeout', (int) $form_state->getValue('nlp_python_timeout'))
      ->set('nlp_ai_temperature', (float) $form_state->getValue('nlp_ai_temperature'))
      ->set('nlp_ai_max_tokens', (int) $form_state->getValue('nlp_ai_max_tokens'))
      ->set('nlp_classification_max_chars', (int) $form_state->getValue('nlp_classification_max_chars'))
      ->set('nlp_summary_max_chars', (int) $form_state->getValue('nlp_summary_max_chars'))
      ->set('default_sync_interval_hours', (int) $form_state->getValue('default_sync_interval_hours'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
