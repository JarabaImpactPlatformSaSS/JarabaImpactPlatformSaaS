<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_support\Entity\TicketAttachment;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Secure attachment URL generation and serving service.
 *
 * Generates time-limited signed URLs for private attachment downloads
 * and validates+serves the files with proper access checks. Ensures
 * only authorized users (ticket reporter, assigned agent, admins) can
 * access attachments.
 */
final class AttachmentUrlService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * URL token validity period in seconds (1 hour).
   */
  private const TOKEN_TTL = 3600;

  /**
   * Generates a signed, time-limited URL for an attachment download.
   *
   * The URL contains a base64-encoded JSON token with the attachment ID,
   * user ID, expiration timestamp, and HMAC signature. The token is
   * self-contained so validation does not require database lookups beyond
   * loading the attachment itself.
   *
   * @param \Drupal\jaraba_support\Entity\TicketAttachment $attachment
   *   The attachment entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user requesting the download.
   *
   * @return string
   *   A signed URL string for downloading the attachment.
   */
  public function generateSignedUrl(TicketAttachment $attachment, AccountInterface $account): string {
    $secret = $this->getHmacSecret();
    $attachmentId = (int) $attachment->id();
    $uid = (int) $account->id();
    $expires = time() + self::TOKEN_TTL;

    // Build HMAC payload: deterministic string binding attachment, user, and time.
    $payload = "{$attachmentId}:{$uid}:{$expires}";
    $hmac = hash_hmac('sha256', $payload, $secret);

    // Encode all parameters into a single opaque token.
    $tokenData = [
      'aid' => $attachmentId,
      'uid' => $uid,
      'exp' => $expires,
      'sig' => $hmac,
    ];
    $token = rtrim(base64_encode(json_encode($tokenData)), '=');

    $url = "/support/attachments/{$attachmentId}/download?token={$token}";

    $this->logger->info('Generated signed URL for attachment @id, user @uid, expires @exp.', [
      '@id' => $attachmentId,
      '@uid' => $uid,
      '@exp' => date('Y-m-d H:i:s', $expires),
    ]);

    return $url;
  }

  /**
   * Validates a signed URL token and serves the file.
   *
   * Verifies the token signature, expiration, user match, scan status,
   * and authorization before serving the private file as a binary response.
   *
   * @param string $token
   *   The base64-encoded JSON token from the signed URL.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user requesting the file.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|null
   *   The file response, or NULL if validation fails.
   */
  public function validateAndServe(string $token, AccountInterface $account): ?BinaryFileResponse {
    try {
      // Decode the token.
      $decoded = base64_decode($token, TRUE);
      if ($decoded === FALSE) {
        $this->logger->warning('Attachment download: invalid base64 token.');
        return NULL;
      }

      $data = json_decode($decoded, TRUE);
      if (!is_array($data) || !isset($data['aid'], $data['uid'], $data['exp'], $data['sig'])) {
        $this->logger->warning('Attachment download: malformed token structure.');
        return NULL;
      }

      $attachmentId = (int) $data['aid'];
      $tokenUid = (int) $data['uid'];
      $expires = (int) $data['exp'];
      $signature = (string) $data['sig'];

      // Check expiration.
      if ($expires < time()) {
        $this->logger->warning('Attachment download: token expired for attachment @id.', [
          '@id' => $attachmentId,
        ]);
        return NULL;
      }

      // Check user match: the requesting user must be the one the token was issued for.
      if ($tokenUid !== (int) $account->id()) {
        $this->logger->warning('Attachment download: UID mismatch. Token for @token_uid, request from @req_uid.', [
          '@token_uid' => $tokenUid,
          '@req_uid' => $account->id(),
        ]);
        return NULL;
      }

      // Verify HMAC signature.
      $secret = $this->getHmacSecret();
      $payload = "{$attachmentId}:{$tokenUid}:{$expires}";
      $expectedHmac = hash_hmac('sha256', $payload, $secret);

      if (!hash_equals($expectedHmac, $signature)) {
        $this->logger->warning('Attachment download: HMAC verification failed for attachment @id.', [
          '@id' => $attachmentId,
        ]);
        return NULL;
      }

      // Load the attachment entity.
      /** @var \Drupal\jaraba_support\Entity\TicketAttachment|null $attachment */
      $attachment = $this->entityTypeManager
        ->getStorage('ticket_attachment')
        ->load($attachmentId);

      if ($attachment === NULL) {
        $this->logger->warning('Attachment download: attachment @id not found.', [
          '@id' => $attachmentId,
        ]);
        return NULL;
      }

      // Only serve clean files. Pending/infected/error files must not be downloadable.
      $scanStatus = $attachment->get('scan_status')->value;
      if ($scanStatus !== 'clean') {
        $this->logger->warning('Attachment download denied: attachment @id has scan_status "@status".', [
          '@id' => $attachmentId,
          '@status' => $scanStatus,
        ]);
        return NULL;
      }

      // Authorization: user must be ticket reporter, assigned agent, or admin.
      if (!$this->isUserAuthorized($attachment, $account)) {
        $this->logger->warning('Attachment download: user @uid not authorized for attachment @id.', [
          '@uid' => $account->id(),
          '@id' => $attachmentId,
        ]);
        return NULL;
      }

      // Resolve the file path and verify the file exists on disk.
      $storagePath = $attachment->get('storage_path')->value;
      if (empty($storagePath)) {
        $this->logger->error('Attachment download: attachment @id has no storage_path.', [
          '@id' => $attachmentId,
        ]);
        return NULL;
      }

      $realPath = \Drupal::service('file_system')->realpath($storagePath);
      if ($realPath === FALSE || !file_exists($realPath) || !is_readable($realPath)) {
        $this->logger->error('Attachment download: file not found or not readable at @path for attachment @id.', [
          '@path' => $storagePath,
          '@id' => $attachmentId,
        ]);
        return NULL;
      }

      // Build and return the binary file response.
      $filename = $attachment->get('filename')->value ?? basename($realPath);
      $mimeType = $attachment->get('mime_type')->value ?? 'application/octet-stream';

      $headers = [
        'Content-Type' => $mimeType,
        'Content-Disposition' => 'attachment; filename="' . addcslashes($filename, '"\\') . '"',
        'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'X-Content-Type-Options' => 'nosniff',
      ];

      $this->logger->info('Serving attachment @id (@filename) to user @uid.', [
        '@id' => $attachmentId,
        '@filename' => $filename,
        '@uid' => $account->id(),
      ]);

      return new BinaryFileResponse($realPath, 200, $headers);
    }
    catch (\Throwable $e) {
      $this->logger->error('Exception in validateAndServe(): @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets the HMAC secret key from configuration.
   *
   * @return string
   *   The HMAC secret key.
   */
  private function getHmacSecret(): string {
    return $this->configFactory->get('jaraba_support.settings')
      ->get('attachment_hmac_secret') ?? 'jaraba_support_default_key';
  }

  /**
   * Checks if a user is authorized to access an attachment.
   *
   * Authorization is granted if the user is:
   * - The ticket reporter (owner).
   * - The assigned agent.
   * - A user with 'administer support system' permission.
   *
   * @param \Drupal\jaraba_support\Entity\TicketAttachment $attachment
   *   The attachment entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to check.
   *
   * @return bool
   *   TRUE if authorized.
   */
  private function isUserAuthorized(TicketAttachment $attachment, AccountInterface $account): bool {
    // Admin bypass.
    if ($account->hasPermission('administer support system')) {
      return TRUE;
    }

    // Load the parent ticket to check reporter and assignee.
    $ticketId = $attachment->get('ticket_id')->target_id;
    if (empty($ticketId)) {
      return FALSE;
    }

    /** @var \Drupal\jaraba_support\Entity\SupportTicket|null $ticket */
    $ticket = $this->entityTypeManager
      ->getStorage('support_ticket')
      ->load($ticketId);

    if ($ticket === NULL) {
      return FALSE;
    }

    $uid = (int) $account->id();

    // Check if user is the ticket reporter.
    $reporterUid = (int) ($ticket->get('reporter_uid')->target_id ?? 0);
    if ($reporterUid === $uid) {
      return TRUE;
    }

    // Check if user is the assigned agent.
    $assigneeUid = (int) ($ticket->get('assignee_uid')->target_id ?? 0);
    if ($assigneeUid > 0 && $assigneeUid === $uid) {
      return TRUE;
    }

    return FALSE;
  }

}
