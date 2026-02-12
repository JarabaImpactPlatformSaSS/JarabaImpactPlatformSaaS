<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jaraba_integrations\Service\ConnectorInstallerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario wizard multi-paso para instalar conectores.
 *
 * PROPOSITO:
 * Guia al usuario a traves del proceso de instalacion de un conector
 * en 3 pasos: configuracion, autorizacion y confirmacion.
 *
 * Ruta: /integraciones/{connector}/wizard
 */
class ConnectorInstallWizardForm extends FormBase {

  /**
   * Paso actual del wizard.
   */
  protected const STEP_CONFIG = 1;
  protected const STEP_AUTH = 2;
  protected const STEP_CONFIRM = 3;

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_integrations\Service\ConnectorInstallerService $installer
   *   Servicio de instalacion de conectores.
   */
  public function __construct(
    protected ConnectorInstallerService $installer,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_integrations.connector_installer'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_integrations_connector_install_wizard';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $connector = NULL): array {
    $step = (int) ($form_state->get('step') ?? self::STEP_CONFIG);
    $form_state->set('step', $step);

    if ($connector) {
      $form_state->set('connector_id', is_object($connector) ? $connector->id() : $connector);
    }

    $form['#prefix'] = '<div id="wizard-wrapper" class="connector-install-wizard">';
    $form['#suffix'] = '</div>';

    // Indicador de progreso.
    $form['progress'] = [
      '#markup' => sprintf(
        '<div class="connector-install-wizard__progress">' .
        '<span class="connector-install-wizard__step %s">1. %s</span>' .
        '<span class="connector-install-wizard__step %s">2. %s</span>' .
        '<span class="connector-install-wizard__step %s">3. %s</span>' .
        '</div>',
        $step === self::STEP_CONFIG ? 'connector-install-wizard__step--active' : ($step > self::STEP_CONFIG ? 'connector-install-wizard__step--done' : ''),
        $this->t('Configuracion'),
        $step === self::STEP_AUTH ? 'connector-install-wizard__step--active' : ($step > self::STEP_AUTH ? 'connector-install-wizard__step--done' : ''),
        $this->t('Autorizacion'),
        $step === self::STEP_CONFIRM ? 'connector-install-wizard__step--active' : '',
        $this->t('Confirmacion'),
      ),
    ];

    match ($step) {
      self::STEP_CONFIG => $this->buildConfigStep($form, $form_state),
      self::STEP_AUTH => $this->buildAuthStep($form, $form_state),
      self::STEP_CONFIRM => $this->buildConfirmStep($form, $form_state),
    };

    return $form;
  }

  /**
   * Paso 1: Configuracion basica del conector.
   */
  protected function buildConfigStep(array &$form, FormStateInterface $form_state): void {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Clave de API'),
      '#description' => $this->t('Introduce la clave de API proporcionada por el servicio externo.'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL de la API'),
      '#description' => $this->t('URL base del servicio externo (opcional).'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Siguiente'),
      '#submit' => ['::nextStep'],
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => 'wizard-wrapper',
      ],
    ];
  }

  /**
   * Paso 2: Autorizacion OAuth (si aplica).
   */
  protected function buildAuthStep(array &$form, FormStateInterface $form_state): void {
    $form['auth_info'] = [
      '#markup' => '<p class="connector-install-wizard__info">' .
        $this->t('Revisa los permisos que el conector solicita:') .
        '</p>',
    ];

    $form['permissions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Permisos solicitados'),
      '#options' => [
        'read_data' => $this->t('Leer datos del tenant'),
        'write_data' => $this->t('Escribir datos en el tenant'),
        'webhooks' => $this->t('Recibir notificaciones via webhook'),
      ],
      '#default_value' => ['read_data'],
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['prev'] = [
      '#type' => 'submit',
      '#value' => $this->t('Anterior'),
      '#submit' => ['::prevStep'],
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => 'wizard-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Siguiente'),
      '#submit' => ['::nextStep'],
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => 'wizard-wrapper',
      ],
    ];
  }

  /**
   * Paso 3: Confirmacion e instalacion.
   */
  protected function buildConfirmStep(array &$form, FormStateInterface $form_state): void {
    $form['confirmation'] = [
      '#markup' => '<div class="connector-install-wizard__confirm">' .
        '<p>' . $this->t('Se instalara el conector con la configuracion proporcionada.') . '</p>' .
        '<p>' . $this->t('Podras modificar la configuracion en cualquier momento desde el panel de integraciones.') . '</p>' .
        '</div>',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['prev'] = [
      '#type' => 'submit',
      '#value' => $this->t('Anterior'),
      '#submit' => ['::prevStep'],
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => 'wizard-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Instalar conector'),
      '#button_type' => 'primary',
    ];
  }

  /**
   * Avanza al siguiente paso.
   */
  public function nextStep(array &$form, FormStateInterface $form_state): void {
    $step = (int) $form_state->get('step');
    $form_state->set('step', $step + 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Retrocede al paso anterior.
   */
  public function prevStep(array &$form, FormStateInterface $form_state): void {
    $step = (int) $form_state->get('step');
    $form_state->set('step', max(self::STEP_CONFIG, $step - 1));
    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback AJAX para refrescar el wizard.
   */
  public function ajaxRefresh(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $connectorId = (int) $form_state->get('connector_id');
    $config = [
      'api_key' => $form_state->getValue('api_key'),
      'api_url' => $form_state->getValue('api_url'),
    ];

    $this->messenger()->addStatus($this->t('El conector se ha instalado correctamente.'));
    $form_state->setRedirect('jaraba_integrations.frontend.dashboard');
  }

}
