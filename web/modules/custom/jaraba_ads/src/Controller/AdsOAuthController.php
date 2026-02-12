<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller para flujos OAuth con plataformas de ads.
 *
 * ESTRUCTURA:
 * Controller que gestiona el flujo OAuth2 de autorización para
 * conectar cuentas de publicidad en Meta y Google. Maneja tanto
 * el inicio del flujo (redirect al proveedor OAuth) como el
 * callback de retorno con el código de autorización.
 *
 * LÓGICA:
 * El flujo OAuth sigue estos pasos:
 * 1. startOAuth(): Genera la URL de autorización y redirige al usuario.
 * 2. handleCallback(): Recibe el código, lo intercambia por tokens y
 *    crea/actualiza la entidad AdsAccount con los tokens obtenidos.
 *
 * RELACIONES:
 * - AdsOAuthController -> EntityTypeManager (dependencia)
 * - AdsOAuthController -> AdsAccount entity (crea/actualiza)
 * - AdsOAuthController -> TenantContextService (contexto tenant)
 * - AdsOAuthController <- jaraba_ads.routing.yml (rutas /ads/oauth/*)
 */
class AdsOAuthController extends ControllerBase {

  /**
   * Servicio de contexto de tenant.
   *
   * @var object|null
   */
  protected $tenantContext = NULL;

  /**
   * Canal de log dedicado.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected ?LoggerInterface $logger = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);

    try {
      $instance->entityTypeManager = $container->get('entity_type.manager');
    }
    catch (\Exception $e) {
      // EntityTypeManager may not be available yet.
    }

    try {
      $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    }
    catch (\Exception $e) {
      // TenantContextService may not be available yet.
    }

    try {
      $instance->logger = $container->get('logger.channel.jaraba_ads');
    }
    catch (\Exception $e) {
      // Logger channel may not be available yet.
    }

    return $instance;
  }

  /**
   * Inicia el flujo OAuth con una plataforma de ads.
   *
   * LÓGICA: Genera la URL de autorización OAuth2 para la plataforma
   *   especificada y redirige al usuario al proveedor OAuth.
   *   El state parameter incluye el tenant_id para recuperarlo en el callback.
   *
   * @param string $platform
   *   Plataforma de ads: 'meta' o 'google'.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   RedirectResponse a la URL de autorización OAuth.
   */
  public function startOAuth(string $platform): Response {
    $supportedPlatforms = ['meta', 'google'];
    if (!in_array($platform, $supportedPlatforms)) {
      $this->messenger()->addError($this->t('Plataforma "@platform" no soportada para OAuth.', ['@platform' => $platform]));
      return new RedirectResponse('/admin/content/ads-accounts');
    }

    $tenantId = $this->getCurrentTenantId();

    // Generar state token con tenant_id para el callback.
    $state = base64_encode(json_encode([
      'tenant_id' => $tenantId,
      'platform' => $platform,
      'timestamp' => time(),
    ]));

    // Construir URL de autorización según la plataforma.
    $authUrl = match ($platform) {
      'meta' => $this->buildMetaAuthUrl($state),
      'google' => $this->buildGoogleAuthUrl($state),
      default => '',
    };

    if (!$authUrl) {
      $this->messenger()->addError($this->t('No se pudo generar la URL de autorización para @platform.', ['@platform' => $platform]));
      return new RedirectResponse('/admin/content/ads-accounts');
    }

    if ($this->logger) {
      $this->logger->info('OAuth iniciado para @platform (tenant @tenant)', [
        '@platform' => $platform,
        '@tenant' => $tenantId,
      ]);
    }

    return new RedirectResponse($authUrl);
  }

  /**
   * Maneja el callback OAuth de una plataforma de ads.
   *
   * LÓGICA: Recibe el código de autorización y el state parameter,
   *   intercambia el código por tokens de acceso y refresco,
   *   y crea/actualiza la entidad AdsAccount con los tokens.
   *
   * @param string $platform
   *   Plataforma de ads: 'meta' o 'google'.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP con los parámetros del callback.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   RedirectResponse al listado de cuentas con mensaje de éxito/error.
   */
  public function handleCallback(string $platform, Request $request): Response {
    $code = $request->query->get('code');
    $state = $request->query->get('state');
    $error = $request->query->get('error');

    // Verificar si hubo error en la autorización.
    if ($error) {
      $errorDescription = $request->query->get('error_description', 'Error desconocido');
      $this->messenger()->addError($this->t('Error de autorización @platform: @error', [
        '@platform' => $platform,
        '@error' => $errorDescription,
      ]));

      if ($this->logger) {
        $this->logger->error('OAuth callback error para @platform: @error', [
          '@platform' => $platform,
          '@error' => $errorDescription,
        ]);
      }

      return new RedirectResponse('/admin/content/ads-accounts');
    }

    // Verificar código de autorización.
    if (!$code) {
      $this->messenger()->addError($this->t('No se recibió código de autorización de @platform.', ['@platform' => $platform]));
      return new RedirectResponse('/admin/content/ads-accounts');
    }

    // Decodificar state para recuperar tenant_id.
    $stateData = [];
    if ($state) {
      $decoded = base64_decode($state);
      if ($decoded) {
        $stateData = json_decode($decoded, TRUE) ?? [];
      }
    }

    $tenantId = $stateData['tenant_id'] ?? $this->getCurrentTenantId();

    try {
      // Intercambiar código por tokens (placeholder para implementación real).
      $tokens = $this->exchangeCodeForTokens($platform, $code);

      if (empty($tokens['access_token'])) {
        $this->messenger()->addError($this->t('No se pudieron obtener los tokens de @platform.', ['@platform' => $platform]));
        return new RedirectResponse('/admin/content/ads-accounts');
      }

      // Crear entidad AdsAccount con los tokens.
      if ($this->entityTypeManager) {
        $storage = $this->entityTypeManager->getStorage('ads_account');
        $account = $storage->create([
          'tenant_id' => $tenantId,
          'platform' => $platform,
          'account_name' => $tokens['account_name'] ?? ucfirst($platform) . ' Ads',
          'external_account_id' => $tokens['account_id'] ?? '',
          'access_token' => $tokens['access_token'],
          'refresh_token' => $tokens['refresh_token'] ?? '',
          'token_expires_at' => $tokens['expires_at'] ?? NULL,
          'oauth_scopes' => $tokens['scopes'] ?? '',
          'status' => 'active',
        ]);

        $account->save();

        $this->messenger()->addStatus($this->t('Cuenta de @platform conectada correctamente.', ['@platform' => $platform]));

        if ($this->logger) {
          $this->logger->info('OAuth completado para @platform (tenant @tenant, cuenta @id)', [
            '@platform' => $platform,
            '@tenant' => $tenantId,
            '@id' => $account->id(),
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error conectando cuenta de @platform: @error', [
        '@platform' => $platform,
        '@error' => $e->getMessage(),
      ]));

      if ($this->logger) {
        $this->logger->error('OAuth callback error procesando tokens de @platform: @error', [
          '@platform' => $platform,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return new RedirectResponse('/admin/content/ads-accounts');
  }

  /**
   * Construye la URL de autorización de Meta OAuth.
   *
   * @param string $state
   *   Token state para CSRF protection.
   *
   * @return string
   *   URL de autorización.
   */
  protected function buildMetaAuthUrl(string $state): string {
    // En producción se obtendría de configuración del módulo.
    $clientId = '';
    $redirectUri = '/ads/oauth/meta/callback';
    $scopes = 'ads_management,ads_read,business_management';

    return sprintf(
      'https://www.facebook.com/v18.0/dialog/oauth?client_id=%s&redirect_uri=%s&scope=%s&state=%s&response_type=code',
      urlencode($clientId),
      urlencode($redirectUri),
      urlencode($scopes),
      urlencode($state)
    );
  }

  /**
   * Construye la URL de autorización de Google OAuth.
   *
   * @param string $state
   *   Token state para CSRF protection.
   *
   * @return string
   *   URL de autorización.
   */
  protected function buildGoogleAuthUrl(string $state): string {
    // En producción se obtendría de configuración del módulo.
    $clientId = '';
    $redirectUri = '/ads/oauth/google/callback';
    $scopes = 'https://www.googleapis.com/auth/adwords';

    return sprintf(
      'https://accounts.google.com/o/oauth2/v2/auth?client_id=%s&redirect_uri=%s&scope=%s&state=%s&response_type=code&access_type=offline&prompt=consent',
      urlencode($clientId),
      urlencode($redirectUri),
      urlencode($scopes),
      urlencode($state)
    );
  }

  /**
   * Intercambia un código de autorización por tokens de acceso.
   *
   * @param string $platform
   *   Plataforma de ads.
   * @param string $code
   *   Código de autorización.
   *
   * @return array
   *   Array con claves: access_token, refresh_token, expires_at, scopes, account_id, account_name.
   */
  protected function exchangeCodeForTokens(string $platform, string $code): array {
    // Placeholder: en producción se haría la llamada HTTP al endpoint de tokens.
    return [
      'access_token' => '',
      'refresh_token' => '',
      'expires_at' => NULL,
      'scopes' => '',
      'account_id' => '',
      'account_name' => '',
    ];
  }

  /**
   * Obtiene el ID del tenant actual.
   *
   * @return int
   *   ID del tenant actual o 0 como fallback.
   */
  protected function getCurrentTenantId(): int {
    if (!$this->tenantContext) {
      return 0;
    }

    try {
      if (method_exists($this->tenantContext, 'getCurrentTenantId')) {
        return (int) ($this->tenantContext->getCurrentTenantId() ?? 0);
      }
    }
    catch (\Exception $e) {
      if ($this->logger) {
        $this->logger->warning('OAuth: no se pudo obtener tenant ID: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return 0;
  }

}
