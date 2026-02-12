<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion global del modulo Funding Intelligence.
 *
 * PROPOSITO:
 * Permite configurar los parametros de las APIs de BDNS y BOJA, el umbral
 * de matching, el maximo de matches por tenant, la frecuencia de alertas,
 * la coleccion Qdrant, el intervalo de sincronizacion y el copilot.
 *
 * CAMPOS:
 * - bdns_api_url: URL base de la API de la BDNS
 * - boja_api_url: URL base de la API del BOJA
 * - matching_threshold: Umbral de matching (0-100)
 * - max_matches_per_tenant: Maximo de matches por tenant (1-500)
 * - alert_frequency: Frecuencia de alertas (immediate, daily, weekly)
 * - qdrant_collection: Nombre de la coleccion en Qdrant
 * - sync_interval_hours: Intervalo de sincronizacion BDNS en horas (1-72)
 * - copilot_enabled: Habilitar copilot de subvenciones
 *
 * RUTA:
 * - /admin/config/services/jaraba-funding
 *
 * CONFIG:
 * - jaraba_funding.settings
 *
 * @package Drupal\jaraba_funding\Form
 */
class FundingSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jaraba_funding.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jaraba_funding_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jaraba_funding.settings');

    // --- Seccion: APIs de Datos ---
    $form['apis'] = [
      '#type' => 'details',
      '#title' => $this->t('APIs de Datos'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion de las conexiones con las APIs de la BDNS y el BOJA para la sincronizacion de convocatorias.'),
    ];

    $form['apis']['bdns_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL base de la API de la BDNS'),
      '#default_value' => $config->get('bdns_api_url') ?? 'https://www.pap.hacienda.gob.es/bdnstrans/api/',
      '#description' => $this->t('URL base del servicio de datos de la Base de Datos Nacional de Subvenciones.'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#placeholder' => 'https://www.pap.hacienda.gob.es/bdnstrans/api/',
    ];

    $form['apis']['boja_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL base de la API del BOJA'),
      '#default_value' => $config->get('boja_api_url') ?? 'https://www.juntadeandalucia.es/boja/api/',
      '#description' => $this->t('URL base del servicio de datos del Boletin Oficial de la Junta de Andalucia.'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#placeholder' => 'https://www.juntadeandalucia.es/boja/api/',
    ];

    // --- Seccion: Motor de Matching ---
    $form['matching'] = [
      '#type' => 'details',
      '#title' => $this->t('Motor de Matching'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion del motor de matching IA que empareja convocatorias con perfiles de beneficiarios.'),
    ];

    $form['matching']['matching_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Umbral de matching (0-100)'),
      '#default_value' => $config->get('matching_threshold') ?? 60.0,
      '#min' => 0,
      '#max' => 100,
      '#step' => 0.1,
      '#description' => $this->t('Puntuacion minima de matching (0 a 100) para considerar una convocatoria como relevante para un beneficiario. Valores mas altos = matches mas precisos pero menos numerosos.'),
      '#required' => TRUE,
    ];

    $form['matching']['max_matches_per_tenant'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximo de matches por tenant'),
      '#default_value' => $config->get('max_matches_per_tenant') ?? 50,
      '#min' => 1,
      '#max' => 500,
      '#step' => 1,
      '#description' => $this->t('Numero maximo de matches activos que se almacenan por tenant. Limita el consumo de recursos y la saturacion de alertas.'),
      '#required' => TRUE,
    ];

    // --- Seccion: Alertas ---
    $form['alerts'] = [
      '#type' => 'details',
      '#title' => $this->t('Alertas'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion de la frecuencia por defecto de las alertas de matching de subvenciones.'),
    ];

    $form['alerts']['alert_frequency'] = [
      '#type' => 'select',
      '#title' => $this->t('Frecuencia de alertas por defecto'),
      '#default_value' => $config->get('alert_frequency') ?? 'daily',
      '#options' => [
        'immediate' => $this->t('Inmediata - Notificacion al detectar un match'),
        'daily' => $this->t('Diaria - Resumen cada 24 horas'),
        'weekly' => $this->t('Semanal - Resumen cada 7 dias'),
      ],
      '#description' => $this->t('Frecuencia por defecto para las alertas de nuevos matches. Los usuarios pueden personalizar su frecuencia individual.'),
      '#required' => TRUE,
    ];

    // --- Seccion: Vector Store ---
    $form['vector'] = [
      '#type' => 'details',
      '#title' => $this->t('Vector Store (Qdrant)'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion de la coleccion Qdrant donde se almacenan los embeddings de las convocatorias para el matching semantico.'),
    ];

    $form['vector']['qdrant_collection'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Coleccion Qdrant'),
      '#default_value' => $config->get('qdrant_collection') ?? 'funding_calls',
      '#description' => $this->t('Nombre de la coleccion en Qdrant donde se almacenan los vectores de convocatorias.'),
      '#maxlength' => 128,
      '#required' => TRUE,
      '#placeholder' => 'funding_calls',
    ];

    // --- Seccion: Sincronizacion ---
    $form['sync'] = [
      '#type' => 'details',
      '#title' => $this->t('Sincronizacion BDNS'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion del intervalo de sincronizacion automatica con la BDNS via cron.'),
    ];

    $form['sync']['sync_interval_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Intervalo de sincronizacion (horas)'),
      '#default_value' => $config->get('sync_interval_hours') ?? 12,
      '#min' => 1,
      '#max' => 72,
      '#step' => 1,
      '#description' => $this->t('Cada cuantas horas se ejecuta la sincronizacion automatica con la BDNS. Minimo 1 hora, maximo 72 horas (3 dias). La sincronizacion solo se ejecuta entre las 03:00 y 05:00 UTC.'),
      '#required' => TRUE,
    ];

    // --- Seccion: Copilot ---
    $form['copilot'] = [
      '#type' => 'details',
      '#title' => $this->t('Copilot de Subvenciones'),
      '#open' => TRUE,
      '#description' => $this->t('Configuracion del copilot conversacional de subvenciones.'),
    ];

    $form['copilot']['copilot_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar copilot de subvenciones'),
      '#default_value' => $config->get('copilot_enabled') ?? TRUE,
      '#description' => $this->t('Activa o desactiva el asistente conversacional de subvenciones. Cuando esta deshabilitado, la ruta del copilot devuelve un mensaje informativo.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validar que la URL de la BDNS sea valida.
    $bdns_url = $form_state->getValue('bdns_api_url');
    if (!empty($bdns_url) && !filter_var($bdns_url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('bdns_api_url', $this->t('La URL base de la BDNS no es valida.'));
    }

    // Validar que la URL del BOJA sea valida.
    $boja_url = $form_state->getValue('boja_api_url');
    if (!empty($boja_url) && !filter_var($boja_url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('boja_api_url', $this->t('La URL base del BOJA no es valida.'));
    }

    // Validar que el nombre de coleccion Qdrant sea alfanumerico con guiones bajos.
    $collection = $form_state->getValue('qdrant_collection');
    if (!empty($collection) && !preg_match('/^[a-zA-Z0-9_]+$/', $collection)) {
      $form_state->setErrorByName('qdrant_collection', $this->t('El nombre de la coleccion Qdrant solo puede contener letras, numeros y guiones bajos.'));
    }

    // Validar umbral de matching como float.
    $threshold = (float) $form_state->getValue('matching_threshold');
    if ($threshold < 0.0 || $threshold > 100.0) {
      $form_state->setErrorByName('matching_threshold', $this->t('El umbral de matching debe estar entre 0 y 100.'));
    }

    // Validar max matches como entero positivo.
    $max_matches = (int) $form_state->getValue('max_matches_per_tenant');
    if ($max_matches < 1 || $max_matches > 500) {
      $form_state->setErrorByName('max_matches_per_tenant', $this->t('El maximo de matches por tenant debe estar entre 1 y 500.'));
    }

    // Validar intervalo de sincronizacion.
    $sync_interval = (int) $form_state->getValue('sync_interval_hours');
    if ($sync_interval < 1 || $sync_interval > 72) {
      $form_state->setErrorByName('sync_interval_hours', $this->t('El intervalo de sincronizacion debe estar entre 1 y 72 horas.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('jaraba_funding.settings')
      // APIs.
      ->set('bdns_api_url', $form_state->getValue('bdns_api_url'))
      ->set('boja_api_url', $form_state->getValue('boja_api_url'))
      // Matching.
      ->set('matching_threshold', (float) $form_state->getValue('matching_threshold'))
      ->set('max_matches_per_tenant', (int) $form_state->getValue('max_matches_per_tenant'))
      // Alertas.
      ->set('alert_frequency', $form_state->getValue('alert_frequency'))
      // Vector Store.
      ->set('qdrant_collection', $form_state->getValue('qdrant_collection'))
      // Sincronizacion.
      ->set('sync_interval_hours', (int) $form_state->getValue('sync_interval_hours'))
      // Copilot.
      ->set('copilot_enabled', (bool) $form_state->getValue('copilot_enabled'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
