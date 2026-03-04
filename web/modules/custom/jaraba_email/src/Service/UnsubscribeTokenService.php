<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Service;

use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Service for generating and validating HMAC unsubscribe tokens.
 *
 * Tokens are stateless HMAC signatures — no DB storage needed.
 * Uses Settings::getHashSalt() as key (SECRET-MGMT-001).
 *
 * RFC 8058: One-Click Unsubscribe for Email Messages.
 */
class UnsubscribeTokenService {

  /**
   * HMAC algorithm.
   */
  private const ALGO = 'sha256';

  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Generates an unsubscribe token for an email address.
   *
   * @param string $email
   *   The subscriber's email.
   *
   * @return string
   *   The HMAC token.
   */
  public function generateToken(string $email): string {
    return hash_hmac(self::ALGO, 'unsubscribe:' . strtolower($email), Settings::getHashSalt());
  }

  /**
   * Validates an unsubscribe token.
   *
   * @param string $email
   *   The subscriber's email.
   * @param string $token
   *   The token to validate.
   *
   * @return bool
   *   TRUE if the token is valid.
   */
  public function validateToken(string $email, string $token): bool {
    $expected = $this->generateToken($email);
    return hash_equals($expected, $token);
  }

  /**
   * Generates a full unsubscribe URL for an email address.
   *
   * ROUTE-LANGPREFIX-001: Uses Url::fromRoute() for proper /es/ prefix.
   *
   * @param string $email
   *   The subscriber's email.
   *
   * @return string
   *   Absolute URL for the unsubscribe page.
   */
  public function generateUnsubscribeUrl(string $email): string {
    $token = $this->generateToken($email);

    return Url::fromRoute(
      'jaraba_email.public_unsubscribe',
      [
        'email' => $email,
        'token' => $token,
      ],
      ['absolute' => TRUE]
    )->toString();
  }

  /**
   * Generates List-Unsubscribe headers per RFC 8058.
   *
   * @param string $email
   *   The subscriber's email.
   *
   * @return array
   *   Headers array with List-Unsubscribe and List-Unsubscribe-Post.
   */
  public function generateListUnsubscribeHeaders(string $email): array {
    $url = $this->generateUnsubscribeUrl($email);

    return [
      'List-Unsubscribe' => '<' . $url . '>',
      'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
    ];
  }

}
