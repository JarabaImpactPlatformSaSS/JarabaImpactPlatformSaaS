<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for email verification via HMAC tokens.
 *
 * Tokens are stored in State API with 24h TTL.
 * Uses Settings::getHashSalt() as HMAC key (SECRET-MGMT-001).
 */
class EmailVerificationService {

  use StringTranslationTrait;

  /**
   * Token TTL in seconds (24 hours).
   */
  private const TOKEN_TTL = 86400;

  /**
   * State key prefix for verification tokens.
   */
  private const STATE_PREFIX = 'email_verification:';

  /**
   * Max token generation per user per hour.
   */
  private const RATE_LIMIT = 3;

  public function __construct(
    protected StateInterface $state,
    protected LoggerInterface $logger,
    protected MailManagerInterface $mailManager,
    protected UserDataInterface $userData,
    protected FloodInterface $flood,
    protected Connection $database,
    protected mixed $templateLoader = NULL,
  ) {}

  /**
   * Generates a verification token for a user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   *
   * @return string
   *   The HMAC token.
   *
   * @throws \RuntimeException
   *   If rate limit exceeded.
   */
  public function generateToken(UserInterface $account): string {
    $uid = (int) $account->id();
    $email = $account->getEmail() ?? '';

    // Rate limit: max 3 tokens per user per hour.
    $identifier = 'email_verify:' . $uid;
    if (!$this->flood->isAllowed('email_verification_generate', self::RATE_LIMIT, 3600, $identifier)) {
      throw new \RuntimeException('Rate limit exceeded for email verification.');
    }
    $this->flood->register('email_verification_generate', 3600, $identifier);

    $timestamp = time();
    $payload = $uid . ':' . $email . ':' . $timestamp;
    $token = hash_hmac('sha256', $payload, Settings::getHashSalt());

    // Store token data in State API with TTL.
    $this->state->set(self::STATE_PREFIX . $token, [
      'uid' => $uid,
      'email' => $email,
      'created' => $timestamp,
      'expires' => $timestamp + self::TOKEN_TTL,
    ]);

    return $token;
  }

  /**
   * Validates a verification token.
   *
   * Returns uid + email for token binding verification.
   *
   * @param string $token
   *   The token to validate.
   *
   * @return array|null
   *   Array with 'uid' and 'email' if valid, NULL otherwise.
   */
  public function validateToken(string $token): ?array {
    $data = $this->state->get(self::STATE_PREFIX . $token);

    if (!$data || !is_array($data)) {
      return NULL;
    }

    // Check expiry.
    if (time() > ($data['expires'] ?? 0)) {
      $this->state->delete(self::STATE_PREFIX . $token);
      $this->logger->info('Email verification token expired for uid @uid.', [
        '@uid' => $data['uid'] ?? 'unknown',
      ]);
      return NULL;
    }

    return [
      'uid' => (int) $data['uid'],
      'email' => $data['email'] ?? '',
    ];
  }

  /**
   * Consumes a token after successful verification.
   *
   * @param string $token
   *   The token to consume.
   */
  public function consumeToken(string $token): void {
    $this->state->delete(self::STATE_PREFIX . $token);
  }

  /**
   * Sends a verification email to the user.
   *
   * Uses jaraba_email.template_loader (AUTH_001) if available via DI.
   * Follows PRESAVE-RESILIENCE-001: optional service check + try-catch.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   * @param string $token
   *   The verification token.
   */
  public function sendVerificationEmail(UserInterface $account, string $token): void {
    $email = $account->getEmail();
    if (!$email) {
      return;
    }

    try {
      $verificationUrl = Url::fromRoute(
        'ecosistema_jaraba_core.email_verify',
        ['token' => $token],
        ['absolute' => TRUE]
      )->toString();

      $variables = [
        'user_name' => $account->getDisplayName(),
        'user_email' => $email,
        'verification_url' => $verificationUrl,
      ];

      $subject = (string) $this->t('Verifica tu email — Jaraba');

      // Try compiled MJML template via jaraba_email (optional DI service).
      if ($this->templateLoader !== NULL) {
        $htmlContent = $this->templateLoader->load('AUTH_001', $variables);

        $this->mailManager->mail(
          'jaraba_email',
          'campaign',
          $email,
          'es',
          [
            'subject' => $subject,
            'body' => $htmlContent,
          ]
        );

        $this->logger->info('Verification email sent to @email (uid @uid).', [
          '@email' => $email,
          '@uid' => $account->id(),
        ]);
        return;
      }

      // Fallback: Drupal default mail with plain text.
      $this->mailManager->mail(
        'ecosistema_jaraba_core',
        'email_verification',
        $email,
        'es',
        [
          'subject' => $subject,
          'body' => (string) $this->t('Hola @name, por favor verifica tu email visitando: @url', [
            '@name' => $account->getDisplayName(),
            '@url' => $verificationUrl,
          ]),
        ]
      );
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to send verification email to @email: @error', [
        '@email' => $email,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Checks if a user's email is verified.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if email is verified.
   */
  public function isVerified(UserInterface $account): bool {
    return (bool) $this->userData->get('ecosistema_jaraba_core', (int) $account->id(), 'email_verified');
  }

  /**
   * Marks a user's email as verified.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   */
  public function markVerified(UserInterface $account): void {
    $this->userData->set('ecosistema_jaraba_core', (int) $account->id(), 'email_verified', time());

    $this->logger->info('Email verified for user @uid (@email).', [
      '@uid' => $account->id(),
      '@email' => $account->getEmail(),
    ]);
  }

  /**
   * Resets verification status (called when email changes).
   *
   * @param int $uid
   *   The user ID.
   */
  public function resetVerification(int $uid): void {
    $this->userData->delete('ecosistema_jaraba_core', $uid, 'email_verified');
  }

  /**
   * Cleans up expired tokens from State API.
   *
   * Called from hook_cron().
   *
   * @return int
   *   Number of tokens deleted.
   */
  public function cleanupExpiredTokens(): int {
    $deleted = 0;

    // Query key names from the key_value table, then read values
    // via State API to avoid calling unserialize() directly.
    $names = $this->database->select('key_value', 'kv')
      ->fields('kv', ['name'])
      ->condition('collection', 'state')
      ->condition('name', self::STATE_PREFIX . '%', 'LIKE')
      ->execute()
      ->fetchCol();

    if (empty($names)) {
      return 0;
    }

    $now = time();
    $values = $this->state->getMultiple($names);
    foreach ($values as $name => $data) {
      if (is_array($data) && isset($data['expires']) && $now > $data['expires']) {
        $this->state->delete($name);
        $deleted++;
      }
    }

    return $deleted;
  }

}
