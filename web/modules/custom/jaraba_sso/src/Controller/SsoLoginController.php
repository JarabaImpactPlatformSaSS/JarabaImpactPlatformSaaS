<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_sso\Service\JitProvisionerService;
use Drupal\jaraba_sso\Service\OidcHandlerService;
use Drupal\jaraba_sso\Service\SamlHandlerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SSO Login Flow Controller.
 *
 * Handles the full SSO authentication lifecycle:
 * - SAML 2.0: AuthnRequest initiation, ACS (assertion consumer), SLO, metadata.
 * - OIDC: Authorization redirect, callback with code exchange.
 * - JIT provisioning for new users during SSO authentication.
 *
 * ENDPOINTS:
 * - GET  /sso/login/{provider_id}            — Initiate SSO login
 * - POST /sso/acs                            — SAML Assertion Consumer Service
 * - GET  /sso/sls                            — SAML Single Logout Service
 * - GET  /sso/oidc/callback                  — OIDC callback handler
 * - GET  /api/v1/sso/metadata/{provider_id}  — SP metadata XML
 */
class SsoLoginController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly SamlHandlerService $samlHandler,
    protected readonly OidcHandlerService $oidcHandler,
    protected readonly JitProvisionerService $jitProvisioner,
    protected readonly TenantContextService $tenantContext,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_sso.saml_handler'),
      $container->get('jaraba_sso.oidc_handler'),
      $container->get('jaraba_sso.jit_provisioner'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * Initiates SSO login by redirecting to the IdP.
   *
   * GET /sso/login/{provider_id}
   *
   * @param int $provider_id
   *   The SSO configuration entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the IdP SSO URL.
   */
  public function login(int $provider_id): RedirectResponse|Response {
    try {
      $config = $this->loadProviderConfig($provider_id);
      if (!$config) {
        return new Response('SSO provider not found or inactive.', 404);
      }

      $redirectUrl = match ($config->getProviderType()) {
        'saml' => $this->samlHandler->initiateLogin($config),
        'oidc' => $this->oidcHandler->initiateLogin($config),
        default => throw new \RuntimeException('Unsupported provider type: ' . $config->getProviderType()),
      };

      return new RedirectResponse($redirectUrl);
    }
    catch (\Exception $e) {
      \Drupal::logger('jaraba_sso')->error('SSO login initiation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new Response('SSO login failed. Please try again.', 500);
    }
  }

  /**
   * SAML Assertion Consumer Service (ACS).
   *
   * POST /sso/acs
   *
   * Receives the SAML Response from the IdP, validates it,
   * provisions or updates the user, and logs them in.
   */
  public function acs(Request $request): RedirectResponse|Response {
    try {
      $samlResponse = $request->request->get('SAMLResponse');
      if (empty($samlResponse)) {
        return new Response('Missing SAMLResponse parameter.', 400);
      }

      $relayState = $request->request->get('RelayState', '');

      // Find the matching SSO configuration.
      // Decode the response to extract the issuer for provider matching.
      $xml = base64_decode($samlResponse, TRUE);
      $config = $this->findConfigByResponse($xml);

      if (!$config) {
        return new Response('No matching SSO provider found for this response.', 400);
      }

      // Process and validate the SAML response.
      $attributes = $this->samlHandler->processResponse($samlResponse, $config);

      // JIT provision or update user.
      $user = $this->jitProvisioner->findExistingUser($attributes);
      if (!$user && $config->isAutoProvision()) {
        $user = $this->jitProvisioner->provisionUser($attributes, $config);
      }
      elseif ($user) {
        $user = $this->jitProvisioner->updateUser($user, $attributes, $config);
      }

      if (!$user) {
        return new Response('User could not be provisioned. Contact your administrator.', 403);
      }

      // Log the user in.
      $this->loginUser($user);

      // Redirect to relay state or dashboard.
      $destination = !empty($relayState) ? $relayState : '/';

      return new RedirectResponse($destination);
    }
    catch (\Exception $e) {
      \Drupal::logger('jaraba_sso')->error('SAML ACS error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new Response('SSO authentication failed: ' . $e->getMessage(), 500);
    }
  }

  /**
   * SAML Single Logout Service (SLS).
   *
   * GET /sso/sls
   *
   * Handles IdP-initiated or SP-initiated logout.
   */
  public function sls(Request $request): RedirectResponse|Response {
    try {
      // Log out the current Drupal user.
      if ($this->currentUser()->isAuthenticated()) {
        user_logout();
      }

      // If there's a SAMLResponse (IdP confirming logout), just redirect.
      $samlResponse = $request->query->get('SAMLResponse');
      if (!empty($samlResponse)) {
        return new RedirectResponse('/');
      }

      // If there's a SAMLRequest (IdP-initiated logout), process and respond.
      $samlRequest = $request->query->get('SAMLRequest');
      if (!empty($samlRequest)) {
        // For now, simply log out and redirect.
        return new RedirectResponse('/');
      }

      return new RedirectResponse('/');
    }
    catch (\Exception $e) {
      \Drupal::logger('jaraba_sso')->error('SAML SLS error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new RedirectResponse('/');
    }
  }

  /**
   * OIDC Callback Handler.
   *
   * GET /sso/oidc/callback
   *
   * Handles the authorization code callback from the OIDC IdP.
   */
  public function oidcCallback(Request $request): RedirectResponse|Response {
    try {
      $code = $request->query->get('code');
      $state = $request->query->get('state');
      $error = $request->query->get('error');

      if (!empty($error)) {
        $errorDescription = $request->query->get('error_description', 'Unknown error');
        return new Response('OIDC authentication denied: ' . $errorDescription, 403);
      }

      if (empty($code) || empty($state)) {
        return new Response('Missing code or state parameter.', 400);
      }

      // Retrieve provider ID from session.
      $session = $request->getSession();
      $providerId = $session->get('jaraba_sso_oidc_provider_id');

      if (empty($providerId)) {
        return new Response('Session expired. Please try SSO login again.', 400);
      }

      $config = $this->loadProviderConfig($providerId);
      if (!$config) {
        return new Response('SSO provider not found.', 404);
      }

      // Exchange code for tokens and get user data.
      $attributes = $this->oidcHandler->handleCallback($code, $state, $config);

      // JIT provision or update user.
      $user = $this->jitProvisioner->findExistingUser($attributes);
      if (!$user && $config->isAutoProvision()) {
        $user = $this->jitProvisioner->provisionUser($attributes, $config);
      }
      elseif ($user) {
        $user = $this->jitProvisioner->updateUser($user, $attributes, $config);
      }

      if (!$user) {
        return new Response('User could not be provisioned. Contact your administrator.', 403);
      }

      // Log the user in.
      $this->loginUser($user);

      return new RedirectResponse('/');
    }
    catch (\Exception $e) {
      \Drupal::logger('jaraba_sso')->error('OIDC callback error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new Response('OIDC authentication failed: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Returns SP metadata XML for IdP configuration.
   *
   * GET /api/v1/sso/metadata/{provider_id}
   */
  public function metadata(int $provider_id): Response {
    try {
      $config = $this->loadProviderConfig($provider_id);
      if (!$config) {
        return new Response('SSO provider not found.', 404);
      }

      $metadataXml = $this->samlHandler->generateMetadata($config);

      return new Response($metadataXml, 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
        'Cache-Control' => 'no-store',
      ]);
    }
    catch (\Exception $e) {
      return new Response('Failed to generate metadata: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Loads and validates an SSO configuration entity.
   */
  protected function loadProviderConfig(int $providerId): ?object {
    $storage = $this->entityTypeManager()->getStorage('sso_configuration');
    $entity = $storage->load($providerId);

    if (!$entity || !$entity->isActive()) {
      return NULL;
    }

    return $entity;
  }

  /**
   * Finds an SSO configuration that matches a SAML response by issuer.
   */
  protected function findConfigByResponse(string $xml): ?object {
    // Extract Issuer from the SAML Response.
    $doc = new \DOMDocument();
    $previousValue = libxml_use_internal_errors(TRUE);
    $doc->loadXML($xml);
    libxml_use_internal_errors($previousValue);

    $xpath = new \DOMXPath($doc);
    $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
    $issuerNodes = $xpath->query('//saml:Issuer');

    if ($issuerNodes->length === 0) {
      return NULL;
    }

    $issuer = $issuerNodes->item(0)->textContent;

    // Find SSO configuration matching this issuer.
    $storage = $this->entityTypeManager()->getStorage('sso_configuration');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_id', $issuer)
      ->condition('provider_type', 'saml')
      ->condition('is_active', 1)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Logs a user into Drupal programmatically.
   */
  protected function loginUser($user): void {
    user_login_finalize($user);

    \Drupal::logger('jaraba_sso')->info('SSO user logged in: @email (uid: @uid)', [
      '@email' => $user->getEmail(),
      '@uid' => $user->id(),
    ]);
  }

}
