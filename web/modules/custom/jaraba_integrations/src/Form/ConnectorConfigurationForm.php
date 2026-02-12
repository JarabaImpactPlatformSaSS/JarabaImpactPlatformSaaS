<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_integrations\Entity\Connector;
use Drupal\jaraba_integrations\Service\ConnectorInstallerService;

/**
 * Formulario de configuración de un conector instalado.
 *
 * PROPÓSITO:
 * Permite al tenant configurar credenciales y opciones del conector
 * tras la instalación (ej: API key, webhook URL, opciones específicas).
 *
 * LÓGICA:
 * - Genera campos dinámicos basados en config_schema del conector.
 * - Guarda la configuración cifrada en ConnectorInstallation.
 * - Activa la instalación tras configuración exitosa.
 */
class ConnectorConfigurationForm extends FormBase {

  public function __construct(
    protected ConnectorInstallerService $connectorInstaller,
  ) {}

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
    return 'jaraba_integrations_connector_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?Connector $connector = NULL): array {
    if (!$connector) {
      $form['error'] = [
        '#markup' => '<p>' . $this->t('Conector no encontrado.') . '</p>',
      ];
      return $form;
    }

    $form['#connector'] = $connector;

    $form['info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['integrations-config__info']],
    ];

    $form['info']['title'] = [
      '#markup' => '<h2>' . $this->t('Configurar @name', ['@name' => $connector->getName()]) . '</h2>',
    ];

    $auth_type = $connector->getAuthType();

    // Campos según tipo de autenticación.
    switch ($auth_type) {
      case Connector::AUTH_API_KEY:
        $form['api_key'] = [
          '#type' => 'textfield',
          '#title' => $this->t('API Key'),
          '#description' => $this->t('Clave de API proporcionada por @provider.', [
            '@provider' => $connector->get('provider')->value ?? $connector->getName(),
          ]),
          '#required' => TRUE,
          '#attributes' => ['autocomplete' => 'off'],
        ];
        break;

      case Connector::AUTH_OAUTH2:
        $form['oauth_info'] = [
          '#markup' => '<p>' . $this->t('Este conector requiere autorización OAuth2. Haga clic en "Autorizar" para conectar su cuenta.') . '</p>',
        ];
        $form['authorize'] = [
          '#type' => 'link',
          '#title' => $this->t('Autorizar con @name', ['@name' => $connector->getName()]),
          '#url' => \Drupal\Core\Url::fromUri($connector->getApiBaseUrl() . '/oauth/authorize', [
            'query' => [
              'client_id' => 'jaraba_platform',
              'redirect_uri' => \Drupal\Core\Url::fromRoute('jaraba_integrations.oauth.callback', [], ['absolute' => TRUE])->toString(),
              'response_type' => 'code',
              'state' => base64_encode(json_encode(['connector_id' => $connector->id()])),
            ],
          ]),
          '#attributes' => ['class' => ['btn', 'btn--primary']],
        ];
        break;

      case Connector::AUTH_BEARER:
        $form['bearer_token'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Bearer Token'),
          '#description' => $this->t('Token de acceso para la API.'),
          '#required' => TRUE,
          '#rows' => 3,
          '#attributes' => ['autocomplete' => 'off'],
        ];
        break;

      case Connector::AUTH_BASIC:
        $form['username'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Usuario'),
          '#required' => TRUE,
        ];
        $form['password'] = [
          '#type' => 'password',
          '#title' => $this->t('Contraseña'),
          '#required' => TRUE,
        ];
        break;

      default:
        $form['no_auth'] = [
          '#markup' => '<p>' . $this->t('Este conector no requiere configuración adicional.') . '</p>',
        ];
        break;
    }

    // Campos adicionales del config_schema.
    $schema = $connector->getConfigSchema();
    if (!empty($schema['properties'])) {
      $form['custom_config'] = [
        '#type' => 'details',
        '#title' => $this->t('Configuración Adicional'),
        '#open' => TRUE,
      ];

      foreach ($schema['properties'] as $key => $property) {
        $form['custom_config'][$key] = [
          '#type' => $this->mapSchemaTypeToFormType($property['type'] ?? 'string'),
          '#title' => $property['title'] ?? $key,
          '#description' => $property['description'] ?? '',
          '#required' => in_array($key, $schema['required'] ?? [], TRUE),
          '#default_value' => $property['default'] ?? '',
        ];
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar Configuración'),
      '#attributes' => ['class' => ['btn', 'btn--primary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\jaraba_integrations\Entity\Connector $connector */
    $connector = $form['#connector'];

    $tenant_id = $this->getTenantId();
    if (!$tenant_id) {
      $this->messenger()->addError($this->t('No se pudo determinar el tenant actual.'));
      return;
    }

    $installation = $this->connectorInstaller->getInstallation($connector, $tenant_id);
    if (!$installation) {
      $this->messenger()->addError($this->t('No se encontró la instalación del conector.'));
      return;
    }

    // Recoger configuración según tipo de auth.
    $config = [];
    $auth_type = $connector->getAuthType();

    switch ($auth_type) {
      case Connector::AUTH_API_KEY:
        $config['api_key'] = $form_state->getValue('api_key');
        break;

      case Connector::AUTH_BEARER:
        $config['bearer_token'] = $form_state->getValue('bearer_token');
        break;

      case Connector::AUTH_BASIC:
        $config['username'] = $form_state->getValue('username');
        $config['password'] = $form_state->getValue('password');
        break;
    }

    // Campos custom del schema.
    $schema = $connector->getConfigSchema();
    if (!empty($schema['properties'])) {
      foreach (array_keys($schema['properties']) as $key) {
        $value = $form_state->getValue($key);
        if ($value !== NULL) {
          $config[$key] = $value;
        }
      }
    }

    $this->connectorInstaller->configure($installation, $config);

    $this->messenger()->addStatus($this->t('Conector %name configurado correctamente.', [
      '%name' => $connector->getName(),
    ]));

    $form_state->setRedirect('jaraba_integrations.frontend.dashboard');
  }

  /**
   * Mapea tipos de JSON Schema a tipos de Form API.
   */
  protected function mapSchemaTypeToFormType(string $schema_type): string {
    return match ($schema_type) {
      'integer', 'number' => 'number',
      'boolean' => 'checkbox',
      'array' => 'textarea',
      default => 'textfield',
    };
  }

  /**
   * Obtiene el tenant_id del usuario actual.
   */
  protected function getTenantId(): ?string {
    try {
      $tenant_context = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      $tenant = $tenant_context->getCurrentTenant();
      if ($tenant) {
        $group = $tenant->getGroup();
        return $group ? (string) $group->id() : NULL;
      }
    }
    catch (\Exception $e) {
      // Sin contexto de tenant.
    }
    return NULL;
  }

}
