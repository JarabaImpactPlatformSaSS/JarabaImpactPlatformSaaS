<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion global del modulo Legal Knowledge.
 *
 * PROPOSITO:
 * Permite configurar los parametros de la API del BOE, el modelo
 * de embeddings, la coleccion Qdrant, los parametros RAG,
 * el nivel de disclaimer y el intervalo de sincronizacion.
 *
 * CAMPOS:
 * - boe_api_base_url: URL base de la API de datos abiertos del BOE
 * - embedding_model: Modelo de embeddings (text-embedding-3-small/large)
 * - qdrant_collection: Nombre de la coleccion en Qdrant
 * - rag_max_chunks: Maximo de chunks por consulta RAG (1-20)
 * - rag_similarity_threshold: Umbral de similitud (0.0-1.0)
 * - disclaimer_level: Nivel de disclaimer (standard/enhanced/critical)
 * - sync_interval_hours: Intervalo de sincronizacion BOE en horas (1-168)
 *
 * RUTA:
 * - /admin/config/services/legal-knowledge
 *
 * CONFIG:
 * - jaraba_legal_knowledge.settings
 *
 * @package Drupal\jaraba_legal_knowledge\Form
 */
class LegalSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jaraba_legal_knowledge.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jaraba_legal_knowledge_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jaraba_legal_knowledge.settings');

    // --- Seccion: API del BOE ---
    $form['boe'] = [
      '#type' => 'details',
      '#title' => $this->t('API del BOE'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion de la conexion con la API de datos abiertos del Boletin Oficial del Estado.'),
    ];

    $form['boe']['boe_api_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL base de la API del BOE'),
      '#default_value' => $config->get('boe_api_base_url') ?? 'https://www.boe.es/datosabiertos/api',
      '#description' => $this->t('URL base del servicio de datos abiertos del BOE. No incluir barra final.'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#placeholder' => 'https://www.boe.es/datosabiertos/api',
    ];

    // --- Seccion: Modelo de Embeddings ---
    $form['embeddings'] = [
      '#type' => 'details',
      '#title' => $this->t('Modelo de Embeddings'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion del modelo de embeddings para la vectorizacion de normas y consultas en el RAG pipeline.'),
    ];

    $form['embeddings']['embedding_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Modelo de embeddings'),
      '#default_value' => $config->get('embedding_model') ?? 'text-embedding-3-small',
      '#options' => [
        'text-embedding-3-small' => $this->t('text-embedding-3-small (rapido, menor coste)'),
        'text-embedding-3-large' => $this->t('text-embedding-3-large (mayor precision, mayor coste)'),
      ],
      '#description' => $this->t('Modelo de OpenAI para generar los embeddings de las normas legales. El modelo small es suficiente para la mayoria de casos.'),
      '#required' => TRUE,
    ];

    $form['embeddings']['qdrant_collection'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Coleccion Qdrant'),
      '#default_value' => $config->get('qdrant_collection') ?? 'legal_norms',
      '#description' => $this->t('Nombre de la coleccion en Qdrant donde se almacenan los vectores de normas legales.'),
      '#maxlength' => 128,
      '#required' => TRUE,
      '#placeholder' => 'legal_norms',
    ];

    // --- Seccion: Parametros RAG ---
    $form['rag'] = [
      '#type' => 'details',
      '#title' => $this->t('Parametros RAG'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion del pipeline de Retrieval-Augmented Generation para consultas legales.'),
    ];

    $form['rag']['rag_max_chunks'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximo de chunks por consulta'),
      '#default_value' => $config->get('rag_max_chunks') ?? 5,
      '#min' => 1,
      '#max' => 20,
      '#step' => 1,
      '#description' => $this->t('Numero maximo de fragmentos de norma que se envian al LLM como contexto. Mas chunks = mas contexto pero mayor coste y latencia.'),
      '#required' => TRUE,
    ];

    $form['rag']['rag_similarity_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Umbral de similitud'),
      '#default_value' => $config->get('rag_similarity_threshold') ?? 0.7,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.05,
      '#description' => $this->t('Puntuacion minima de similitud coseno (0.0 a 1.0) para incluir un chunk como contexto relevante. Valores mas altos = resultados mas precisos pero menos exhaustivos.'),
      '#required' => TRUE,
    ];

    // --- Seccion: Disclaimer y Comunicacion ---
    $form['disclaimer'] = [
      '#type' => 'details',
      '#title' => $this->t('Disclaimer y Comunicacion'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion del nivel de advertencia legal que se muestra junto a las respuestas de consulta.'),
    ];

    $form['disclaimer']['disclaimer_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Nivel de disclaimer'),
      '#default_value' => $config->get('disclaimer_level') ?? 'standard',
      '#options' => [
        'standard' => $this->t('Estandar - Aviso basico de caracter orientativo'),
        'enhanced' => $this->t('Mejorado - Incluye recomendacion de consultar profesional'),
        'critical' => $this->t('Critico - Advertencia prominente con limitaciones explicitas'),
      ],
      '#description' => $this->t('Define la prominencia y detalle del disclaimer legal mostrado en las respuestas. Se recomienda "mejorado" o "critico" para entornos de produccion.'),
      '#required' => TRUE,
    ];

    // --- Seccion: Sincronizacion ---
    $form['sync'] = [
      '#type' => 'details',
      '#title' => $this->t('Sincronizacion BOE'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion del intervalo de sincronizacion automatica con la API del BOE via cron.'),
    ];

    $form['sync']['sync_interval_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Intervalo de sincronizacion (horas)'),
      '#default_value' => $config->get('sync_interval_hours') ?? 24,
      '#min' => 1,
      '#max' => 168,
      '#step' => 1,
      '#description' => $this->t('Cada cuantas horas se ejecuta la sincronizacion automatica con el BOE. Minimo 1 hora, maximo 168 horas (1 semana). La sincronizacion solo se ejecuta entre las 04:00 y 06:00 UTC.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validar que la URL del BOE sea una URL valida.
    $boe_url = $form_state->getValue('boe_api_base_url');
    if (!empty($boe_url) && !filter_var($boe_url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('boe_api_base_url', $this->t('La URL base del BOE no es valida.'));
    }

    // Validar que la URL no termine en barra.
    if (!empty($boe_url) && str_ends_with($boe_url, '/')) {
      $form_state->setErrorByName('boe_api_base_url', $this->t('La URL base del BOE no debe terminar en barra (/).'));
    }

    // Validar que el nombre de coleccion Qdrant sea alfanumerico con guiones bajos.
    $collection = $form_state->getValue('qdrant_collection');
    if (!empty($collection) && !preg_match('/^[a-zA-Z0-9_]+$/', $collection)) {
      $form_state->setErrorByName('qdrant_collection', $this->t('El nombre de la coleccion Qdrant solo puede contener letras, numeros y guiones bajos.'));
    }

    // Validar umbral de similitud como float.
    $threshold = (float) $form_state->getValue('rag_similarity_threshold');
    if ($threshold < 0.0 || $threshold > 1.0) {
      $form_state->setErrorByName('rag_similarity_threshold', $this->t('El umbral de similitud debe estar entre 0.0 y 1.0.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('jaraba_legal_knowledge.settings')
      // BOE.
      ->set('boe_api_base_url', $form_state->getValue('boe_api_base_url'))
      // Embeddings.
      ->set('embedding_model', $form_state->getValue('embedding_model'))
      ->set('qdrant_collection', $form_state->getValue('qdrant_collection'))
      // RAG.
      ->set('rag_max_chunks', (int) $form_state->getValue('rag_max_chunks'))
      ->set('rag_similarity_threshold', (float) $form_state->getValue('rag_similarity_threshold'))
      // Disclaimer.
      ->set('disclaimer_level', $form_state->getValue('disclaimer_level'))
      // Sync.
      ->set('sync_interval_hours', (int) $form_state->getValue('sync_interval_hours'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
