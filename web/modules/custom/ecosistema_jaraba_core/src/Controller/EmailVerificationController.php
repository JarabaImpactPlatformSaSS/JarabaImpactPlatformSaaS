<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\EmailVerificationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for email verification via token link.
 *
 * CONTROLLER-READONLY-001: No readonly on inherited $entityTypeManager.
 */
class EmailVerificationController extends ControllerBase {

  public function __construct(
    protected EmailVerificationService $emailVerification,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.email_verification'),
    );
  }

  /**
   * Verifies email via token — GET /verificar-email/{token}.
   *
   * Token binding: validates that the token's bound email still matches
   * the user's current email, preventing stale token reuse after email change.
   *
   * @param string $token
   *   The HMAC verification token.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to homepage with status message.
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
    $account = $userStorage->load($uid);

    if (!$account) {
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

    $this->messenger()->addStatus($this->t('¡Tu email ha sido verificado correctamente! Gracias.'));

    // Redirect to dashboard if logged in, login page if not.
    if ($this->currentUser()->isAuthenticated()) {
      $redirectUrl = Url::fromRoute('ecosistema_jaraba_core.tenant.dashboard', [], ['absolute' => TRUE])->toString();
    }
    else {
      $redirectUrl = Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString();
    }

    return new RedirectResponse($redirectUrl);
  }

}
