<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_email\Service\UnsubscribeTokenService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for public email unsubscribe pages.
 *
 * CONTROLLER-READONLY-001: No readonly on inherited $entityTypeManager.
 */
class PublicUnsubscribeController extends ControllerBase {

  public function __construct(
    protected UnsubscribeTokenService $unsubscribeToken,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_email.unsubscribe_token'),
    );
  }

  /**
   * Confirmation page — GET /email/unsubscribe/{email}/{token}.
   *
   * Shows a confirmation page before actually unsubscribing.
   *
   * @param string $email
   *   The subscriber's email address.
   * @param string $token
   *   The HMAC token.
   *
   * @return array
   *   Render array with confirmation form.
   */
  public function confirm(string $email, string $token): array {
    if (!$this->unsubscribeToken->validateToken($email, $token)) {
      $this->messenger()->addError($this->t('El enlace de baja no es válido.'));
      return [
        '#markup' => '<p>' . $this->t('El enlace de baja no es válido o ha sido modificado.') . '</p>',
      ];
    }

    $processUrl = \Drupal\Core\Url::fromRoute(
      'jaraba_email.public_unsubscribe.process',
      ['email' => $email, 'token' => $token]
    )->toString();

    return [
      '#theme' => 'jaraba_email_unsubscribe_confirm',
      '#email' => $email,
      '#process_url' => $processUrl,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Processes unsubscription — POST /email/unsubscribe/{email}/{token}.
   *
   * RFC 8058 One-Click: Also handles POST with List-Unsubscribe=One-Click.
   *
   * @param string $email
   *   The subscriber's email address.
   * @param string $token
   *   The HMAC token.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   Render array with confirmation message.
   */
  public function process(string $email, string $token, Request $request): array {
    if (!$this->unsubscribeToken->validateToken($email, $token)) {
      $this->messenger()->addError($this->t('El enlace de baja no es válido.'));
      return ['#markup' => ''];
    }

    // Unsubscribe: find subscriber entity and set status to unsubscribed.
    try {
      $subscriberStorage = $this->entityTypeManager()->getStorage('email_subscriber');
      $subscribers = $subscriberStorage->loadByProperties([
        'email' => strtolower($email),
      ]);

      if (!empty($subscribers)) {
        foreach ($subscribers as $subscriber) {
          $subscriber->set('status', 'unsubscribed');
          $subscriber->set('unsubscribed_at', date('Y-m-d\TH:i:s'));
          $subscriber->save();
        }
      }

      $this->getLogger('jaraba_email')->info('Unsubscribed: @email via public link.', [
        '@email' => $email,
      ]);
    }
    catch (\Throwable $e) {
      $this->getLogger('jaraba_email')->error('Unsubscribe error for @email: @error', [
        '@email' => $email,
        '@error' => $e->getMessage(),
      ]);
    }

    $this->messenger()->addStatus($this->t('Te has dado de baja correctamente. Ya no recibirás más emails.'));

    return [
      '#markup' => '<div class="ej-unsubscribe-success"><p>' . $this->t('Has sido dado de baja correctamente de nuestras comunicaciones.') . '</p><p>' . $this->t('Si fue un error, puedes volver a suscribirte desde la plataforma.') . '</p></div>',
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
