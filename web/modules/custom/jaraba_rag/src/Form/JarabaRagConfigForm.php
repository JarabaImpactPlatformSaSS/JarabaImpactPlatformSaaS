<?php

namespace Drupal\jaraba_rag\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Jaraba RAG Knowledge Base.
 *
 * Follows Drupal 11 ConfigFormBase pattern with TypedConfigManager.
 */
class JarabaRagConfigForm extends ConfigFormBase
{

    /**
     * Config settings name.
     */
    const CONFIG_NAME = 'jaraba_rag.settings';

    /**
     * The key repository.
     *
     * @var \Drupal\key\KeyRepositoryInterface
     */
    protected KeyRepositoryInterface $keyRepository;

    /**
     * Constructs a JarabaRagConfigForm object.
     *
     * Following official Drupal 11 ConfigFormBase pattern.
     */
    public function __construct(
        ConfigFactoryInterface $config_factory,
        TypedConfigManagerInterface $typedConfigManager,
        KeyRepositoryInterface $key_repository,
    ) {
        parent::__construct($config_factory, $typedConfigManager);
        $this->keyRepository = $key_repository;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('config.factory'),
            $container->get('config.typed'),
            $container->get('key.repository'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_rag_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return [static::CONFIG_NAME];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config(static::CONFIG_NAME);

        // =========================================================================
        // Vector Database (Qdrant) Section
        // =========================================================================
        $form['vector_db'] = [
            '#type' => 'details',
            '#title' => $this->t('Vector Database (Qdrant)'),
            '#open' => TRUE,
        ];

        $form['vector_db']['host'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Qdrant Host'),
            '#description' => $this->t('URL del servidor Qdrant.<br>• <strong>Local (Lando)</strong>: http://qdrant:6333<br>• <strong>Qdrant Cloud</strong>: https://[cluster-id].europe-west3-0.gcp.cloud.qdrant.io'),
            '#default_value' => $config->get('vector_db.host') ?? 'http://qdrant:6333',
            '#required' => TRUE,
        ];

        $form['vector_db']['api_key'] = [
            '#type' => 'key_select',
            '#title' => $this->t('Qdrant API Key'),
            '#description' => $this->t('Clave API de Qdrant (opcional para instalaciones locales).'),
            '#default_value' => $config->get('vector_db.api_key'),
            '#required' => FALSE,
        ];

        $form['vector_db']['collection'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre de Colección'),
            '#description' => $this->t('Nombre de la colección de vectores en Qdrant.'),
            '#default_value' => $config->get('vector_db.collection') ?? 'jaraba_kb',
            '#required' => TRUE,
        ];

        $form['vector_db']['vector_dimensions'] = [
            '#type' => 'number',
            '#title' => $this->t('Dimensiones del Vector'),
            '#description' => $this->t('Dimensiones del embedding. OpenAI text-embedding-3-small = 1536.'),
            '#default_value' => $config->get('vector_db.vector_dimensions') ?? 1536,
            '#min' => 256,
            '#max' => 4096,
        ];

        // =========================================================================
        // Embeddings Section
        // =========================================================================
        $form['embeddings'] = [
            '#type' => 'details',
            '#title' => $this->t('Embeddings'),
            '#open' => FALSE,
        ];

        $form['embeddings']['model'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Modelo de Embeddings'),
            '#default_value' => $config->get('embeddings.model') ?? 'text-embedding-3-small',
        ];

        $form['embeddings']['chunk_size'] = [
            '#type' => 'number',
            '#title' => $this->t('Tamaño de Chunk'),
            '#description' => $this->t('Número de tokens por chunk de texto.'),
            '#default_value' => $config->get('embeddings.chunk_size') ?? 500,
            '#min' => 100,
            '#max' => 2000,
        ];

        $form['embeddings']['chunk_overlap'] = [
            '#type' => 'number',
            '#title' => $this->t('Overlap de Chunk'),
            '#default_value' => $config->get('embeddings.chunk_overlap') ?? 50,
            '#min' => 0,
            '#max' => 500,
        ];

        // =========================================================================
        // Search Section
        // =========================================================================
        $form['search'] = [
            '#type' => 'details',
            '#title' => $this->t('Búsqueda'),
            '#open' => FALSE,
        ];

        $form['search']['top_k'] = [
            '#type' => 'number',
            '#title' => $this->t('Top K Resultados'),
            '#description' => $this->t('Número de chunks más relevantes a recuperar.'),
            '#default_value' => $config->get('search.top_k') ?? 5,
            '#min' => 1,
            '#max' => 20,
        ];

        $form['search']['score_threshold'] = [
            '#type' => 'number',
            '#title' => $this->t('Puntuación Mínima'),
            '#description' => $this->t('Puntuación mínima de similitud (0-1).'),
            '#default_value' => $config->get('search.score_threshold') ?? 0.7,
            '#min' => 0,
            '#max' => 1,
            '#step' => 0.05,
        ];

        // =========================================================================
        // Grounding Section
        // =========================================================================
        $form['grounding'] = [
            '#type' => 'details',
            '#title' => $this->t('Grounding (Anti-Alucinaciones)'),
            '#open' => FALSE,
        ];

        $form['grounding']['enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Activar validación de grounding'),
            '#default_value' => $config->get('grounding.enabled') ?? TRUE,
        ];

        $form['grounding']['entailment_threshold'] = [
            '#type' => 'number',
            '#title' => $this->t('Umbral de Entailment'),
            '#default_value' => $config->get('grounding.entailment_threshold') ?? 0.8,
            '#min' => 0,
            '#max' => 1,
            '#step' => 0.05,
        ];

        $form['grounding']['fallback_message'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Mensaje Fallback'),
            '#description' => $this->t('Mensaje cuando no hay datos suficientes para responder.'),
            '#default_value' => $config->get('grounding.fallback_message') ?? 'No tengo suficiente información para responder a esa pregunta con precisión.',
            '#rows' => 2,
        ];

        // =========================================================================
        // LLM Section
        // =========================================================================
        $form['llm'] = [
            '#type' => 'details',
            '#title' => $this->t('LLM (Generación de Respuestas)'),
            '#open' => FALSE,
        ];

        $form['llm']['model'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Modelo LLM'),
            '#default_value' => $config->get('llm.model') ?? 'gpt-4o-mini',
        ];

        $form['llm']['temperature'] = [
            '#type' => 'number',
            '#title' => $this->t('Temperatura'),
            '#default_value' => $config->get('llm.temperature') ?? 0.3,
            '#min' => 0,
            '#max' => 2,
            '#step' => 0.1,
        ];

        $form['llm']['max_tokens'] = [
            '#type' => 'number',
            '#title' => $this->t('Máximo de Tokens'),
            '#default_value' => $config->get('llm.max_tokens') ?? 1000,
            '#min' => 100,
            '#max' => 4096,
        ];

        // =========================================================================
        // Analytics Section
        // =========================================================================
        $form['analytics'] = [
            '#type' => 'details',
            '#title' => $this->t('Analytics'),
            '#open' => FALSE,
        ];

        $form['analytics']['enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Activar analytics de queries'),
            '#default_value' => $config->get('analytics.enabled') ?? TRUE,
        ];

        $form['analytics']['detect_gaps'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Detectar gaps de contenido'),
            '#description' => $this->t('Identificar queries sin respuesta adecuada.'),
            '#default_value' => $config->get('analytics.detect_gaps') ?? TRUE,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        parent::validateForm($form, $form_state);
        // Host validation removed - configuration comes from settings.php
        // according to the dual architecture (Lando/IONOS) design.
        // See: docs/tecnicos/20260111a-Anexo_A1_Integracion_Qdrant_Dual_v2_Claude.md
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config(static::CONFIG_NAME)
            // Vector DB.
            ->set('vector_db.host', rtrim($form_state->getValue(['vector_db', 'host']), '/'))
            ->set('vector_db.api_key', $form_state->getValue(['vector_db', 'api_key']))
            ->set('vector_db.collection', $form_state->getValue(['vector_db', 'collection']))
            ->set('vector_db.vector_dimensions', (int) $form_state->getValue(['vector_db', 'vector_dimensions']))
            // Embeddings.
            ->set('embeddings.model', $form_state->getValue(['embeddings', 'model']))
            ->set('embeddings.chunk_size', (int) $form_state->getValue(['embeddings', 'chunk_size']))
            ->set('embeddings.chunk_overlap', (int) $form_state->getValue(['embeddings', 'chunk_overlap']))
            // Search.
            ->set('search.top_k', (int) $form_state->getValue(['search', 'top_k']))
            ->set('search.score_threshold', (float) $form_state->getValue(['search', 'score_threshold']))
            // Grounding.
            ->set('grounding.enabled', (bool) $form_state->getValue(['grounding', 'enabled']))
            ->set('grounding.entailment_threshold', (float) $form_state->getValue(['grounding', 'entailment_threshold']))
            ->set('grounding.fallback_message', $form_state->getValue(['grounding', 'fallback_message']))
            // LLM.
            ->set('llm.model', $form_state->getValue(['llm', 'model']))
            ->set('llm.temperature', (float) $form_state->getValue(['llm', 'temperature']))
            ->set('llm.max_tokens', (int) $form_state->getValue(['llm', 'max_tokens']))
            // Analytics.
            ->set('analytics.enabled', (bool) $form_state->getValue(['analytics', 'enabled']))
            ->set('analytics.detect_gaps', (bool) $form_state->getValue(['analytics', 'detect_gaps']))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
