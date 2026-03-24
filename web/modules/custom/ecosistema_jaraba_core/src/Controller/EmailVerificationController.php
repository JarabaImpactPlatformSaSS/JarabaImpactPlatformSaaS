<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\EmailVerificationService;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for email verification via token link.
 *
 * After successful verification, generates a one-time login URL and redirects
 * the user to set their password. This provides a single-email registration
 * flow: verify email → set password → done.
 *
 * CONTROLLER-READONLY-001: No readonly on inherited $entityTypeManager.
 */
class EmailVerificationController extends ControllerBase {

  public function __construct(
    protected EmailVerificationService $emailVerification,
    protected TimeInterface $time,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.email_verification'),
      $container->get('datetime.time'),
    );
  }

  /**
   * Verifies email via token — GET /verificar-email/{token}.
   *
   * Token binding: validates that the token's bound email still matches
   * the user's current email, preventing stale token reuse after email change.
   *
   * After successful verification, redirects to one-time login URL so the user
   * can set their password in the same flow (no second email needed).
   *
   * @param string $token
   *   The HMAC verification token.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to one-time login or login page with status message.
   */
  public function verify(string $token): RedirectResponse {
    $tokenData = $this->emailVerification->validateToken($token);

    if ($tokenData === NULL) {
      $this->messenger()->addError($this->t('El enlace de verificación ha expirado o no es válido. Por favor, solicita uno nuevo.'));
      return new RedirectResponse(Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString());
    }

    $uid = $tokenData['uid'];
    $boundEmail = $tokenData['email'];

    $userStorage = $this->entityTypeManager()->getStorage('user');
    /** @var \Drupal\user\UserInterface|null $account */
    $account = $userStorage->load($uid);

    if ($account === NULL) {
      $this->emailVerification->consumeToken($token);
      $this->messenger()->addError($this->t('No se encontró la cuenta de usuario.'));
      return new RedirectResponse(Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString());
    }

    // Token binding check: ensure bound email matches current email.
    $currentEmail = $account->getEmail() ?? '';
    if ($boundEmail !== $currentEmail) {
      $this->emailVerification->consumeToken($token);
      $this->messenger()->addError($this->t('Tu email ha cambiado desde que se generó este enlace. Por favor, solicita uno nuevo.'));
      return new RedirectResponse(Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString());
    }

    // Mark as verified and consume token.
    $this->emailVerification->markVerified($account);
    $this->emailVerification->consumeToken($token);

    // If user is already authenticated, redirect to profile.
    if ($this->currentUser()->isAuthenticated()) {
      $this->messenger()->addStatus($this->t('¡Tu email ha sido verificado correctamente!'));
      $redirectUrl = Url::fromRoute('entity.user.canonical', ['user' => $this->currentUser()->id()], ['absolute' => TRUE])->toString();
      return new RedirectResponse($redirectUrl);
    }

    // User is anonymous (typical for new registration): generate one-time
    // login URL so they can set their password in the same flow.
    $redirectUrl = $this->generateOneTimeLoginUrl($account);
    if ($redirectUrl !== NULL) {
      $this->messenger()->addStatus($this->t('¡Email verificado! Ahora establece tu contraseña para completar tu registro.'));
      return new RedirectResponse($redirectUrl);
    }

    // Fallback: redirect to login page.
    $this->messenger()->addStatus($this->t('¡Tu email ha sido verificado correctamente! Inicia sesión para continuar.'));
    return new RedirectResponse(Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString());
  }

  /**
   * Generates a one-time login URL for the user.
   *
   * Uses user_pass_rehash() to create the same hash that Drupal core uses
   * for password reset links. The URL redirects to the password setup form.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   *
   * @return string|null
   *   The absolute one-time login URL, or NULL on failure.
   */
  protected function generateOneTimeLoginUrl(UserInterface $account): ?string {
    try {
      $timestamp = $this->time->getRequestTime();
      $hash = user_pass_rehash($account, $timestamp);

      return Url::fromRoute('user.reset.login', [
        'uid' => $account->id(),
        'timestamp' => $timestamp,
        'hash' => $hash,
      ], ['absolute' => TRUE])->toString();
    }
    catch (\Throwable $e) {
      return NULL;
    }
  }

}
