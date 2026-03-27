<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración general del módulo Andalucía +ei.
 */
class AndaluciaEiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['jaraba_andalucia_ei.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_andalucia_ei_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('jaraba_andalucia_ei.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración General'),
      '#open' => TRUE,
    ];

    $form['general']['horas_minimas_orientacion'] = [
      '#type' => 'number',
      '#title' => $this->t('Horas Mínimas Orientación para Inserción'),
      '#description' => $this->t('Horas mínimas de orientación requeridas para transitar a fase Inserción.'),
      '#default_value' => $config->get('horas_minimas_orientacion') ?? 10,
      '#min' => 0,
      '#step' => 0.5,
    ];

    $form['general']['horas_minimas_formacion'] = [
      '#type' => 'number',
      '#title' => $this->t('Horas Mínimas Formación para Inserción'),
      '#description' => $this->t('Horas mínimas de formación requeridas para transitar a fase Inserción.'),
      '#default_value' => $config->get('horas_minimas_formacion') ?? 50,
      '#min' => 0,
      '#step' => 0.5,
    ];

    $form['ia_tracking'] = [
      '#type' => 'details',
      '#title' => $this->t('Tracking de Mentoría IA'),
      '#open' => TRUE,
    ];

    $form['ia_tracking']['horas_por_sesion_ia'] = [
      '#type' => 'number',
      '#title' => $this->t('Horas por Sesión con Tutor IA'),
      '#description' => $this->t('Horas a contabilizar por cada sesión con el Copiloto IA.'),
      '#default_value' => $config->get('horas_por_sesion_ia') ?? 0.25,
      '#min' => 0.1,
      '#max' => 2,
      '#step' => 0.05,
    ];

    $form['ia_tracking']['maximo_horas_ia_dia'] = [
      '#type' => 'number',
      '#title' => $this->t('Máximo Horas IA por Día'),
      '#description' => $this->t('Límite de horas IA contabilizables por día.'),
      '#default_value' => $config->get('maximo_horas_ia_dia') ?? 4,
      '#min' => 1,
      '#max' => 8,
      '#step' => 0.5,
    ];

    $form['programa'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos del Programa'),
      '#open' => TRUE,
    ];

    $form['programa']['plazas_totales'] = [
      '#type' => 'number',
      '#title' => $this->t('Plazas totales'),
      '#description' => $this->t('Número total de plazas del programa.'),
      '#default_value' => $config->get('plazas_totales') ?? 45,
      '#min' => 1,
    ];

    $form['programa']['plazas_restantes'] = [
      '#type' => 'number',
      '#title' => $this->t('Plazas restantes'),
      '#description' => $this->t('Plazas disponibles actualmente. Actualizar manualmente o se decrementa al aceptar solicitudes.'),
      '#default_value' => $config->get('plazas_restantes') ?? 45,
      '#min' => 0,
    ];

    $form['programa']['incentivo_euros'] = [
      '#type' => 'number',
      '#title' => $this->t('Incentivo (€)'),
      '#description' => $this->t('Incentivo económico al participante al completar itinerario.'),
      '#default_value' => $config->get('incentivo_euros') ?? 528,
      '#min' => 0,
    ];

    $form['programa']['tasa_insercion_objetivo'] = [
      '#type' => 'number',
      '#title' => $this->t('Tasa de inserción objetivo (%)'),
      '#default_value' => $config->get('tasa_insercion_objetivo') ?? 40,
      '#min' => 0,
      '#max' => 100,
    ];

    $form['programa']['expediente'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Número de expediente'),
      '#default_value' => $config->get('expediente') ?? 'SC/ICV/0111/2025',
    ];

    $form['programa']['subvencion_total'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subvención total'),
      '#default_value' => $config->get('subvencion_total') ?? '202.500',
    ];

    $form['programa']['fecha_inicio_programa'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha inicio programa'),
      '#default_value' => $config->get('fecha_inicio_programa') ?? '2025-12-29',
    ];

    $form['programa']['fecha_fin_programa'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha fin programa'),
      '#default_value' => $config->get('fecha_fin_programa') ?? '2027-06-28',
    ];

    $form['campana'] = [
      '#type' => 'details',
      '#title' => $this->t('Campaña de Reclutamiento'),
      '#open' => TRUE,
    ];

    $form['campana']['fecha_limite_solicitudes'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha límite para solicitudes'),
      '#description' => $this->t('Se muestra como countdown en la landing si "Mostrar countdown" está activo.'),
      '#default_value' => $config->get('fecha_limite_solicitudes') ?? '2026-06-30',
    ];

    $form['campana']['mostrar_countdown'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar countdown en landing de reclutamiento'),
      '#default_value' => $config->get('mostrar_countdown') ?? TRUE,
    ];

    $form['campana']['mostrar_popup_saas'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mostrar popup de reclutamiento en la home del SaaS principal'),
      '#description' => $this->t('Además de los meta-sitios corporativos. Activar durante campañas.'),
      '#default_value' => $config->get('mostrar_popup_saas') ?? FALSE,
    ];

    $form['campana']['popup_campaign_utm'] = [
      '#type' => 'textfield',
      '#title' => $this->t('UTM campaign para popup'),
      '#description' => $this->t('Valor de utm_campaign añadido a los enlaces del popup.'),
      '#default_value' => $config->get('popup_campaign_utm') ?? 'aei_reclutamiento_2026',
      '#states' => [
        'visible' => [
                  [':input[name="mostrar_popup_saas"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['campana']['popup_ttl_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Horas entre re-exposiciones del popup'),
      '#description' => $this->t('Tras cerrar el popup, no se vuelve a mostrar durante estas horas.'),
      '#default_value' => $config->get('popup_ttl_hours') ?? 48,
      '#min' => 1,
      '#max' => 720,
    ];

    $form['campana']['popup_delay_ms'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay de aparición del popup (milisegundos)'),
      '#description' => $this->t('Milisegundos que el visitante navega antes de que aparezca el popup. Recomendado: 3000.'),
      '#default_value' => $config->get('popup_delay_ms') ?? 3000,
      '#min' => 1000,
      '#max' => 15000,
      '#step' => 500,
    ];

    $form['campana']['tasa_insercion_1e'] = [
      '#type' => 'number',
      '#title' => $this->t('Tasa de inserción 1ª Edición (%)'),
      '#description' => $this->t('Dato de prueba social mostrado en el popup. 46% es el resultado real de la 1ª Edición.'),
      '#default_value' => $config->get('tasa_insercion_1e') ?? 46,
      '#min' => 0,
      '#max' => 100,
    ];

    $form['campana']['popup_negocio_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activar path de negocio piloto en el popup'),
      '#description' => $this->t('Muestra el selector dual participante/negocio. Si se desactiva, solo aparece el path de participante.'),
      '#default_value' => $config->get('popup_negocio_enabled') ?? TRUE,
    ];

    $form['campana']['popup_servicios_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Número de servicios gratuitos mostrados'),
      '#description' => $this->t('Cantidad de servicios de digitalización que se listan en el path de negocio piloto.'),
      '#default_value' => $config->get('popup_servicios_count') ?? 5,
      '#min' => 3,
      '#max' => 8,
      '#states' => [
        'visible' => [
          ':input[name="popup_negocio_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['campana']['popup_valor_mercado_anual'] = [
      '#type' => 'number',
      '#title' => $this->t('Valor de mercado anual de los servicios (EUR)'),
      '#description' => $this->t('Precio de mercado estimado que se muestra tachado como anclaje de precio. NO-HARDCODE-PRICE-001.'),
      '#default_value' => $config->get('popup_valor_mercado_anual') ?? 2400,
      '#min' => 500,
      '#max' => 10000,
      '#step' => 100,
      '#states' => [
        'visible' => [
          ':input[name="popup_negocio_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['sto'] = [
      '#type' => 'details',
      '#title' => $this->t('Integración STO'),
      '#open' => FALSE,
    ];

    $form['sto']['sto_sync_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar sincronización automática con STO'),
      '#default_value' => $config->get('sto_sync_enabled') ?? FALSE,
    ];

    $form['sto']['sto_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Endpoint STO'),
      '#description' => $this->t('URL del servicio web del STO.'),
      '#default_value' => $config->get('sto_endpoint') ?? '',
      '#states' => [
        'visible' => [
          ':input[name="sto_sync_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('jaraba_andalucia_ei.settings')
      ->set('horas_minimas_orientacion', $form_state->getValue('horas_minimas_orientacion'))
      ->set('horas_minimas_formacion', $form_state->getValue('horas_minimas_formacion'))
      ->set('horas_por_sesion_ia', $form_state->getValue('horas_por_sesion_ia'))
      ->set('maximo_horas_ia_dia', $form_state->getValue('maximo_horas_ia_dia'))
      ->set('plazas_totales', (int) $form_state->getValue('plazas_totales'))
      ->set('plazas_restantes', (int) $form_state->getValue('plazas_restantes'))
      ->set('incentivo_euros', (int) $form_state->getValue('incentivo_euros'))
      ->set('tasa_insercion_objetivo', (int) $form_state->getValue('tasa_insercion_objetivo'))
      ->set('expediente', $form_state->getValue('expediente'))
      ->set('subvencion_total', $form_state->getValue('subvencion_total'))
      ->set('fecha_inicio_programa', $form_state->getValue('fecha_inicio_programa'))
      ->set('fecha_fin_programa', $form_state->getValue('fecha_fin_programa'))
      ->set('fecha_limite_solicitudes', $form_state->getValue('fecha_limite_solicitudes'))
      ->set('mostrar_countdown', (bool) $form_state->getValue('mostrar_countdown'))
      ->set('mostrar_popup_saas', (bool) $form_state->getValue('mostrar_popup_saas'))
      ->set('popup_campaign_utm', $form_state->getValue('popup_campaign_utm'))
      ->set('popup_ttl_hours', (int) $form_state->getValue('popup_ttl_hours'))
      ->set('popup_delay_ms', (int) $form_state->getValue('popup_delay_ms'))
      ->set('tasa_insercion_1e', (int) $form_state->getValue('tasa_insercion_1e'))
      ->set('popup_negocio_enabled', (bool) $form_state->getValue('popup_negocio_enabled'))
      ->set('popup_servicios_count', (int) $form_state->getValue('popup_servicios_count'))
      ->set('popup_valor_mercado_anual', (int) $form_state->getValue('popup_valor_mercado_anual'))
      ->set('sto_sync_enabled', $form_state->getValue('sto_sync_enabled'))
      ->set('sto_endpoint', $form_state->getValue('sto_endpoint'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
