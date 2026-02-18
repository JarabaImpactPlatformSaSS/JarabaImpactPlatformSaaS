<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\jaraba_integrations\Service\OauthServerService;

/**
 * Controlador OAuth2 para el flujo Authorization Code.
 *
 * FLUJO:
 * 1. App redirige usuario a GET /oauth/authorize?client_id=X&redirect_uri=Y&scope=Z
 * 2. Usuario ve pantalla de consentimiento y acepta.
 * 3. Redirige a redirect_uri con ?code=ABC
 * 4. App intercambia code en POST /oauth/token
 * 5. Recibe access_token + refresh_token
 *
 * SEGURIDAD:
 * - Validación de client_id + redirect_uri en authorize.
 * - Authorization code one-time-use, expira en 10 min.
 * - PKCE (RFC 7636) obligatorio para clientes públicos (SPA/mobile).
 */
class OauthController extends ControllerBase {

  public function __construct(
    protected OauthServerService $oauthServer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_integrations.oauth_server'),
    );
  }

  /**
   * Pantalla de autorización OAuth2.
   *
   * GET /oauth/authorize?client_id=X&redirect_uri=Y&response_type=code&scope=read
   */
  public function authorize(Request $request): array|RedirectResponse {
    $client_id = $request->query->get('client_id', '');
    $redirect_uri = $request->query->get('redirect_uri', '');
    $response_type = $request->query->get('response_type', '');
    $scope = $request->query->get('scope', 'read');
    $state = $request->query->get('state', '');

    // AUDIT-SEC-N12: PKCE parameters (RFC 7636).
    $code_challenge = $request->query->get('code_challenge', '');
    $code_challenge_method = $request->query->get('code_challenge_method', 'S256');

    // Validar parámetros.
    if ($response_type !== 'code') {
      return [
        '#markup' => '<p>' . $this->t('Tipo de respuesta no soportado. Use response_type=code.') . '</p>',
      ];
    }

    // Validar cliente.
    $client = $this->oauthServer->validateClient($client_id, $redirect_uri);
    if (!$client) {
      return [
        '#markup' => '<p>' . $this->t('Cliente OAuth no válido o URI de redirección no autorizada.') . '</p>',
      ];
    }

    // AUDIT-SEC-N12: PKCE obligatorio para clientes públicos (sin secret).
    $isPublicClient = method_exists($client, 'isPublic') && $client->isPublic();
    if ($isPublicClient && empty($code_challenge)) {
      return new JsonResponse([
        'error' => 'invalid_request',
        'error_description' => 'PKCE code_challenge is required for public clients (RFC 7636).',
      ], 400);
    }

    if (!empty($code_challenge) && !in_array($code_challenge_method, ['S256', 'plain'], TRUE)) {
      return new JsonResponse([
        'error' => 'invalid_request',
        'error_description' => 'Unsupported code_challenge_method. Use S256 or plain.',
      ], 400);
    }

    $scopes = array_map('trim', explode(' ', $scope));

    // Si el usuario ya consintió (POST), generar code y redirigir.
    if ($request->isMethod('POST') && $request->request->get('consent') === 'allow') {
      $code = $this->oauthServer->generateAuthorizationCode(
        $client,
        (int) $this->currentUser()->id(),
        $scopes,
        $code_challenge,
        $code_challenge_method
      );

      $callback = $redirect_uri . '?code=' . urlencode($code);
      if (!empty($state)) {
        $callback .= '&state=' . urlencode($state);
      }

      return new RedirectResponse($callback);
    }

    // Mostrar pantalla de consentimiento.
    return [
      '#theme' => 'jaraba_oauth_authorize',
      '#client_name' => $client->getName(),
      '#scopes' => $scopes,
      '#client_id' => $client_id,
      '#redirect_uri' => $redirect_uri,
      '#scope' => $scope,
      '#state' => $state,
    ];
  }

  /**
   * Intercambio de authorization code por tokens.
   *
   * POST /oauth/token
   * Body: grant_type=authorization_code&code=X&client_id=Y&client_secret=Z
   */
  public function token(Request $request): JsonResponse {
    $grant_type = $request->request->get('grant_type', '');

    if ($grant_type !== 'authorization_code') {
      return new JsonResponse([
        'error' => 'unsupported_grant_type',
        'error_description' => 'Only authorization_code grant type is supported.',
      ], 400);
    }

    $code = $request->request->get('code', '');
    $client_id = $request->request->get('client_id', '');
    $client_secret = $request->request->get('client_secret', '');
    $code_verifier = $request->request->get('code_verifier', '');

    // AUDIT-SEC-N12: client_secret es opcional cuando code_verifier presente (PKCE).
    if (empty($code) || empty($client_id)) {
      return new JsonResponse([
        'error' => 'invalid_request',
        'error_description' => 'Missing required parameters: code, client_id.',
      ], 400);
    }

    if (empty($client_secret) && empty($code_verifier)) {
      return new JsonResponse([
        'error' => 'invalid_request',
        'error_description' => 'Either client_secret or code_verifier (PKCE) is required.',
      ], 400);
    }

    $tokens = $this->oauthServer->exchangeCode($code, $client_id, $client_secret, $code_verifier);

    if (!$tokens) {
      return new JsonResponse([
        'error' => 'invalid_grant',
        'error_description' => 'Invalid or expired authorization code.',
      ], 400);
    }

    return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => TRUE, 'data' => $tokens, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Callback OAuth para conectores externos.
   *
   * GET /oauth/callback?code=X&state=Y
   */
  public function callback(Request $request): RedirectResponse {
    $code = $request->query->get('code', '');
    $state = $request->query->get('state', '');
    $error = $request->query->get('error', '');

    if (!empty($error)) {
      $this->messenger()->addError($this->t('Error de autorización: @error', [
        '@error' => $request->query->get('error_description', $error),
      ]));
      return new RedirectResponse('/integraciones');
    }

    if (empty($code)) {
      $this->messenger()->addError($this->t('No se recibió código de autorización.'));
      return new RedirectResponse('/integraciones');
    }

    // El state contiene el connector_id para completar la configuración.
    if (!empty($state)) {
      $state_data = json_decode(base64_decode($state), TRUE);
      if (isset($state_data['connector_id'])) {
        // Almacenar el code en la sesión para que el formulario de config lo use.
        $request->getSession()->set('jaraba_oauth_code', $code);
        $request->getSession()->set('jaraba_oauth_connector', $state_data['connector_id']);

        $this->messenger()->addStatus($this->t('Autorización completada. Configure las opciones restantes.'));
        return new RedirectResponse('/integraciones/' . $state_data['connector_id'] . '/configurar');
      }
    }

    $this->messenger()->addStatus($this->t('Autorización completada.'));
    return new RedirectResponse('/integraciones');
  }

}
