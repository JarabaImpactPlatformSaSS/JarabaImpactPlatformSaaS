<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\EmailVerificationService;
use Drupal\ecosistema_jaraba_core\Service\GoogleOAuthService;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * P2-05: Google OAuth login/register controller.
 *
 * CONTROLLER-READONLY-001: No readonly on inherited $entityTypeManager.
 * ROUTE-LANGPREFIX-001: All URLs via Url::fromRoute().
 */
class GoogleOAuthController extends ControllerBase
{

  protected LoggerInterface $logger;

  public function __construct(
    protected GoogleOAuthService $googleOAuth,
    protected ?EmailVerificationService $emailVerification,
    LoggerInterface $logger,
  ) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('ecosistema_jaraba_core.google_oauth'),
      $container->has('ecosistema_jaraba_core.email_verification')
      ? $container->get('ecosistema_jaraba_core.email_verification')
      : NULL,
      $container->get('logger.channel.ecosistema_jaraba_core'),
    );
  }

  /**
   * Redirects user to Google OAuth consent screen.
   *
   * GET /user/login/google
   */
  public function redirectToGoogle(Request $request): TrustedRedirectResponse|RedirectResponse
  {
    if (!$this->googleOAuth->isConfigured()) {
      $this->messenger()->addError($this->t('El inicio de sesión con Google no está disponible en este momento.'));
      return new RedirectResponse(Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString());
    }

    // Generate CSRF state token.
    $state = bin2hex(random_bytes(16));
    $request->getSession()->set('google_oauth_state', $state);

    // Store return URL if coming from registration.
    $destination = $request->query->get('destination', '');
    if ($destination) {
      $request->getSession()->set('google_oauth_destination', $destination);
    }

    $authUrl = $this->googleOAuth->getAuthorizationUrl($state);

    return new TrustedRedirectResponse($authUrl);
  }

  /**
   * Handles Google OAuth callback.
   *
   * GET /user/login/google/callback
   *
   * Scenarios:
   * 1. User exists with same email → log in
   * 2. User doesn't exist → create account and log in
   * 3. Error from Google → show message and redirect
   */
  public function callback(Request $request): RedirectResponse
  {
    // Validate state (CSRF protection).
    $state = $request->query->get('state', '');
    $sessionState = $request->getSession()->get('google_oauth_state', '');
    $request->getSession()->remove('google_oauth_state');

    if (!$state || !hash_equals($sessionState, $state)) {
      $this->logger->warning('Google OAuth: state mismatch (possible CSRF).');
      throw new AccessDeniedHttpException('Invalid state parameter.');
    }

    // Check for errors from Google.
    $error = $request->query->get('error', '');
    if ($error) {
      $this->logger->info('Google OAuth cancelled or denied: @error', ['@error' => $error]);
      $this->messenger()->addWarning($this->t('Se ha cancelado el inicio de sesión con Google.'));
      return new RedirectResponse(Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString());
    }

    // Exchange code for user info.
    $code = $request->query->get('code', '');
    if (!$code) {
      $this->messenger()->addError($this->t('Error en la respuesta de Google. Por favor, inténtalo de nuevo.'));
      return new RedirectResponse(Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString());
    }

    $userInfo = $this->googleOAuth->exchangeCodeForUserInfo($code);
    if (!$userInfo) {
      $this->messenger()->addError($this->t('No se pudo obtener la información de tu cuenta de Google.'));
      return new RedirectResponse(Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString());
    }

    $email = $userInfo['email'];
    $name = $userInfo['name'];

    // Check if user exists.
    $existingUsers = $this->entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['mail' => $email]);

    if (!empty($existingUsers)) {
      // Scenario 1: User exists — log in.
      $account = reset($existingUsers);

      if ($account->isBlocked()) {
        $this->messenger()->addError($this->t('Tu cuenta está bloqueada. Contacta con el administrador.'));
        return new RedirectResponse(Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString());
      }

      user_login_finalize($account);

      $this->logger->info('Google OAuth login for existing user @uid (@email).', [
        '@uid' => $account->id(),
        '@email' => $email,
      ]);

      $this->messenger()->addStatus($this->t('¡Bienvenido de nuevo, @name!', ['@name' => $account->getDisplayName()]));
    } else {
      // Scenario 2: New user — create account.
      $username = $this->generateUniqueUsername($name, $email);

      $account = User::create([
        'name' => $username,
        'mail' => $email,
        'pass' => \Drupal::service('password_generator')->generate(32),
        'status' => 1,
        'init' => $email,
      ]);
      $account->save();

      // Mark email as verified (Google already verified it).
      if ($this->emailVerification && ($userInfo['email_verified'] ?? FALSE)) {
        $this->emailVerification->markVerified($account);
      }

      user_login_finalize($account);

      $this->logger->info('Google OAuth: new user @uid created for @email.', [
        '@uid' => $account->id(),
        '@email' => $email,
      ]);

      $this->messenger()->addStatus($this->t('¡Bienvenido, @name! Tu cuenta ha sido creada.', ['@name' => $name]));
    }

    // Redirect to stored destination or user profile (SaaS entry point).
    $destination = $request->getSession()->get('google_oauth_destination', '');
    $request->getSession()->remove('google_oauth_destination');

    if ($destination) {
      return new RedirectResponse($destination);
    }

    // The user profile is the unified entry point to the entire SaaS —
    // consistent with core UserLoginForm::submitForm() behavior.
    $redirectUrl = Url::fromRoute('entity.user.canonical', ['user' => $account->id()], ['absolute' => TRUE])->toString();
    return new RedirectResponse($redirectUrl);
  }

  /**
   * Generates a unique Drupal username from Google name/email.
   */
  protected function generateUniqueUsername(string $name, string $email): string
  {
    // Start with Google display name.
    $base = !empty($name) ? $name : strtok($email, '@');
    // Sanitize.
    $base = preg_replace('/[^\w\s.-]/', '', $base);
    $base = trim($base);

    if (empty($base)) {
      $base = 'user';
    }

    $username = $base;
    $suffix = 0;
    $userStorage = $this->entityTypeManager()->getStorage('user');

    while (!empty($userStorage->loadByProperties(['name' => $username]))) {
      $suffix++;
      $username = $base . $suffix;
    }

    return $username;
  }

}
