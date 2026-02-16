<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Formulario para conectar y gestionar cuentas de Google Search Console.
 *
 * PROPOSITO:
 * Permite al administrador del tenant iniciar el flujo OAuth2 con Google
 * para autorizar el acceso a los datos de Search Console. Tambien muestra
 * las conexiones existentes y maneja el callback de OAuth2.
 *
 * FLUJO:
 * 1. El usuario ve sus conexiones actuales
 * 2. Hace clic en "Conectar cuenta de Google"
 * 3. Se redirige a Google con los parametros OAuth2
 * 4. Google redirige de vuelta con un authorization code
 * 5. El formulario intercambia el code por tokens y los almacena
 *
 * RUTA:
 * - /admin/config/services/insights-hub/connect
 *
 * @package Drupal\jaraba_insights_hub\Form
 */
class SearchConsoleConnectForm extends FormBase {

  /**
   * La factoria de configuracion.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * El servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * El stack de peticiones.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->configFactory = $container->get('config.factory');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jaraba_insights_hub_search_console_connect_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('jaraba_insights_hub.settings');
    $client_id = $config->get('search_console_client_id');
    $client_secret = $config->get('search_console_client_secret');

    // Verificar si hay credenciales OAuth2 configuradas.
    if (empty($client_id) || empty($client_secret)) {
      $form['no_credentials'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">' .
          $this->t('Debe configurar las credenciales OAuth2 de Google antes de conectar. <a href="@url">Ir a configuracion</a>.', [
            '@url' => Url::fromRoute('jaraba_insights_hub.settings')->toString(),
          ]) . '</div>',
      ];
      return $form;
    }

    // Verificar si hay un authorization code en la URL (callback de OAuth2).
    $request = $this->requestStack->getCurrentRequest();
    $auth_code = $request ? $request->query->get('code') : NULL;
    $auth_error = $request ? $request->query->get('error') : NULL;

    if ($auth_error) {
      $form['oauth_error'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--error">' .
          $this->t('Error en la autorizacion de Google: @error', [
            '@error' => $auth_error,
          ]) . '</div>',
      ];
    }

    if ($auth_code) {
      $form['oauth_success'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--status">' .
          $this->t('Codigo de autorizacion recibido. Complete el formulario para finalizar la conexion.') . '</div>',
      ];

      $form['auth_code'] = [
        '#type' => 'hidden',
        '#value' => $auth_code,
      ];
    }

    // Mostrar conexiones existentes.
    $form['existing_connections'] = [
      '#type' => 'details',
      '#title' => $this->t('Conexiones existentes'),
      '#open' => TRUE,
    ];

    $connections = $this->getExistingConnections();

    if (empty($connections)) {
      $form['existing_connections']['no_connections'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('No hay conexiones de Search Console configuradas.') . '</p>',
      ];
    }
    else {
      $header = [
        $this->t('URL del sitio'),
        $this->t('Estado'),
        $this->t('Ultima sincronizacion'),
        $this->t('Errores'),
      ];

      $rows = [];
      foreach ($connections as $connection) {
        $status_label = match ($connection->get('status')->value) {
          'active' => $this->t('Activa'),
          'expired' => $this->t('Expirada'),
          'revoked' => $this->t('Revocada'),
          default => $this->t('Desconocido'),
        };

        $last_sync = $connection->get('last_sync_at')->value;
        $last_sync_formatted = $last_sync
          ? \Drupal::service('date.formatter')->format((int) $last_sync, 'short')
          : $this->t('Nunca');

        $rows[] = [
          $connection->get('site_url')->value,
          $status_label,
          $last_sync_formatted,
          $connection->get('sync_errors')->value ?? 0,
        ];
      }

      $form['existing_connections']['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No hay conexiones.'),
      ];
    }

    // Seccion: Nueva conexion.
    $form['new_connection'] = [
      '#type' => 'details',
      '#title' => $this->t('Nueva conexion'),
      '#open' => !empty($auth_code) || empty($connections),
    ];

    if (!$auth_code) {
      // Boton para iniciar flujo OAuth2.
      $oauth_url = $this->buildOAuthUrl($client_id);

      $form['new_connection']['oauth_info'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('Haga clic en el boton para autorizar el acceso a Google Search Console. Sera redirigido a Google para aprobar los permisos necesarios.') . '</p>',
      ];

      $form['new_connection']['connect_button'] = [
        '#type' => 'link',
        '#title' => $this->t('Conectar cuenta de Google'),
        '#url' => Url::fromUri($oauth_url),
        '#attributes' => [
          'class' => ['button', 'button--primary'],
        ],
      ];
    }
    else {
      // Formulario para completar la conexion con el auth code.
      $form['new_connection']['site_url'] = [
        '#type' => 'url',
        '#title' => $this->t('URL del sitio en Search Console'),
        '#required' => TRUE,
        '#placeholder' => 'https://mi-sitio.com',
        '#description' => $this->t('La URL del sitio tal como aparece verificada en Google Search Console.'),
      ];

      $form['new_connection']['actions'] = [
        '#type' => 'actions',
      ];

      $form['new_connection']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Completar conexion'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $auth_code = $form_state->getValue('auth_code');
    if (empty($auth_code)) {
      // No hay auth code, no se esta completando la conexion.
      return;
    }

    $site_url = $form_state->getValue('site_url');
    if (empty($site_url)) {
      $form_state->setErrorByName('site_url', $this->t('Debe proporcionar la URL del sitio.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $auth_code = $form_state->getValue('auth_code');
    $site_url = $form_state->getValue('site_url');

    if (empty($auth_code) || empty($site_url)) {
      return;
    }

    $config = $this->configFactory->get('jaraba_insights_hub.settings');
    $client_id = $config->get('search_console_client_id');
    $client_secret = $config->get('search_console_client_secret');

    // Intercambiar el authorization code por tokens.
    $tokens = $this->exchangeAuthCode($auth_code, $client_id, $client_secret);

    if (!$tokens) {
      $this->messenger()->addError($this->t('Error al intercambiar el codigo de autorizacion con Google. Intente nuevamente.'));
      return;
    }

    // Obtener tenant actual.
    $tenant = $this->tenantContext->getCurrentTenant();
    $tenant_id = $tenant ? (int) $tenant->id() : 0;

    // Crear la entidad SearchConsoleConnection.
    try {
      $entity_type_manager = \Drupal::entityTypeManager();
      $connection = $entity_type_manager->getStorage('search_console_connection')->create([
        'tenant_id' => $tenant_id,
        'site_url' => $site_url,
        'access_token' => $tokens['access_token'] ?? '',
        'refresh_token' => $tokens['refresh_token'] ?? '',
        'token_expires_at' => time() + (int) ($tokens['expires_in'] ?? 3600),
        'status' => 'active',
        'sync_errors' => 0,
      ]);
      $connection->save();

      $this->messenger()->addStatus($this->t('Conexion con Google Search Console establecida correctamente para @url.', [
        '@url' => $site_url,
      ]));

      // Redirigir al formulario limpio (sin query params de OAuth).
      $form_state->setRedirectUrl(Url::fromRoute('jaraba_insights_hub.search_console_connect'));
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al guardar conexion de Search Console: @message', [
        '@message' => $e->getMessage(),
      ]);
      $this->messenger()->addError($this->t('Error al guardar la conexion. Revise los logs para mas detalles.'));
    }
  }

  /**
   * Construye la URL de autorizacion OAuth2 de Google.
   *
   * @param string $client_id
   *   Client ID de la aplicacion OAuth2.
   *
   * @return string
   *   URL completa para redirigir al usuario a Google.
   */
  protected function buildOAuthUrl(string $client_id): string {
    $redirect_uri = Url::fromRoute('jaraba_insights_hub.search_console_connect', [], ['absolute' => TRUE])->toString();

    $params = [
      'client_id' => $client_id,
      'redirect_uri' => $redirect_uri,
      'response_type' => 'code',
      'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
      'access_type' => 'offline',
      'prompt' => 'consent',
      'state' => \Drupal::csrfToken()->get('insights-search-console-oauth'),
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
  }

  /**
   * Intercambia un authorization code por tokens OAuth2.
   *
   * @param string $auth_code
   *   El authorization code recibido de Google.
   * @param string $client_id
   *   Client ID de OAuth2.
   * @param string $client_secret
   *   Client Secret de OAuth2.
   *
   * @return array|null
   *   Array con access_token, refresh_token, expires_in o NULL en caso de error.
   */
  protected function exchangeAuthCode(string $auth_code, string $client_id, string $client_secret): ?array {
    $redirect_uri = Url::fromRoute('jaraba_insights_hub.search_console_connect', [], ['absolute' => TRUE])->toString();

    try {
      /** @var \GuzzleHttp\ClientInterface $http_client */
      $http_client = \Drupal::httpClient();

      $response = $http_client->post('https://oauth2.googleapis.com/token', [
        'form_params' => [
          'code' => $auth_code,
          'client_id' => $client_id,
          'client_secret' => $client_secret,
          'redirect_uri' => $redirect_uri,
          'grant_type' => 'authorization_code',
        ],
        'timeout' => 30,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if (!empty($data['access_token'])) {
        return $data;
      }

      $this->getLogger('jaraba_insights_hub')->warning('Respuesta de token de Google sin access_token: @response', [
        '@response' => json_encode($data),
      ]);

      return NULL;
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al intercambiar auth code con Google: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene las conexiones existentes de Search Console para el tenant actual.
   *
   * @return array
   *   Array de entidades SearchConsoleConnection.
   */
  protected function getExistingConnections(): array {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;

      $storage = \Drupal::entityTypeManager()->getStorage('search_console_connection');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC');

      if ($tenant_id > 0) {
        $query->condition('tenant_id', $tenant_id);
      }

      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      return $storage->loadMultiple($ids);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al obtener conexiones de Search Console: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
