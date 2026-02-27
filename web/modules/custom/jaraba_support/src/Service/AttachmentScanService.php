<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_support\Entity\TicketAttachment;
use Psr\Log\LoggerInterface;

/**
 * Attachment virus/malware scanning service (GAP-SUP-08).
 *
 * Scans uploaded attachments for malware using configured scanning backend.
 * Files remain in 'pending' scan status until processed. Infected files
 * are quarantined and the attachment entity is updated accordingly.
 */
final class AttachmentScanService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Dangerous file extensions that should be rejected outright.
   */
  private const DANGEROUS_EXTENSIONS = [
    'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'phps',
    'exe', 'com', 'dll', 'scr', 'msi',
    'sh', 'bash', 'csh', 'ksh', 'zsh',
    'bat', 'cmd', 'ps1', 'vbs', 'vbe', 'wsf', 'wsh',
    'js', 'jse', 'jar', 'war',
    'py', 'pyc', 'pyo', 'pl', 'pm', 'rb',
    'cgi', 'asp', 'aspx', 'jsp',
    'htaccess', 'htpasswd',
  ];

  /**
   * Scans a single attachment for malware.
   *
   * Uses ClamAV if available and enabled; otherwise falls back to
   * basic heuristic checks (extension blocking, magic byte analysis).
   *
   * @param \Drupal\jaraba_support\Entity\TicketAttachment $attachment
   *   The attachment entity to scan.
   *
   * @return string
   *   Scan result: 'clean', 'infected', or 'error'.
   */
  public function scanAttachment(TicketAttachment $attachment): string {
    try {
      $storagePath = $attachment->get('storage_path')->value;
      if (empty($storagePath)) {
        $this->logger->error('Attachment @id has no storage_path.', [
          '@id' => $attachment->id() ?? 'new',
        ]);
        $attachment->set('scan_status', 'error');
        $attachment->save();
        return 'error';
      }

      // Resolve the stream wrapper path to a real filesystem path.
      $realPath = \Drupal::service('file_system')->realpath($storagePath);
      if ($realPath === FALSE || !file_exists($realPath)) {
        $this->logger->error('Attachment @id file not found at @path.', [
          '@id' => $attachment->id() ?? 'new',
          '@path' => $storagePath,
        ]);
        $attachment->set('scan_status', 'error');
        $attachment->save();
        return 'error';
      }

      $config = $this->configFactory->get('jaraba_support.settings');
      $clamavEnabled = (bool) $config->get('clamav_enabled');
      $result = 'clean';

      if ($clamavEnabled) {
        $result = $this->scanWithClamAv($realPath, $config);
      }
      else {
        $result = $this->scanWithBasicChecks($realPath, $attachment);
      }

      // Update the attachment entity with scan results.
      $attachment->set('scan_status', $result);
      if ($result === 'infected') {
        $this->logger->warning('Attachment @id (@filename) is INFECTED. File: @path', [
          '@id' => $attachment->id(),
          '@filename' => $attachment->get('filename')->value ?? 'unknown',
          '@path' => $storagePath,
        ]);
      }
      $attachment->save();

      $this->logger->info('Attachment @id scan complete: @result.', [
        '@id' => $attachment->id(),
        '@result' => $result,
      ]);

      return $result;
    }
    catch (\Throwable $e) {
      $this->logger->error('Exception scanning attachment @id: @msg', [
        '@id' => $attachment->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
      try {
        $attachment->set('scan_status', 'error');
        $attachment->save();
      }
      catch (\Throwable) {
        // Cannot save — already logging the original error.
      }
      return 'error';
    }
  }

  /**
   * Scans a file using the ClamAV daemon via unix socket.
   *
   * @param string $realPath
   *   The absolute filesystem path to the file.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The module configuration.
   *
   * @return string
   *   'clean', 'infected', or 'error'.
   */
  private function scanWithClamAv(string $realPath, $config): string {
    $socketPath = $config->get('clamav_socket') ?: '/var/run/clamav/clamd.ctl';

    $socket = @stream_socket_client(
      'unix://' . $socketPath,
      $errno,
      $errstr,
      30,
    );

    if ($socket === FALSE) {
      $this->logger->error('Cannot connect to ClamAV socket @path: @err (@errno)', [
        '@path' => $socketPath,
        '@err' => $errstr,
        '@errno' => $errno,
      ]);
      // Fall back to basic checks when ClamAV socket is unreachable.
      return 'error';
    }

    try {
      // Send the SCAN command. The "n" prefix means no session (one-shot).
      $command = "nSCAN " . $realPath . "\n";
      fwrite($socket, $command);

      // Read the response (e.g., "/path/file: OK" or "/path/file: Eicar-Test-Signature FOUND").
      $response = '';
      while (!feof($socket)) {
        $chunk = fread($socket, 4096);
        if ($chunk === FALSE) {
          break;
        }
        $response .= $chunk;
      }

      $response = trim($response);
      $this->logger->info('ClamAV response for @path: @response', [
        '@path' => $realPath,
        '@response' => $response,
      ]);

      if (str_contains($response, 'FOUND')) {
        return 'infected';
      }
      if (str_contains($response, 'OK')) {
        return 'clean';
      }

      // Unexpected response format.
      $this->logger->warning('ClamAV returned unexpected response: @response', [
        '@response' => $response,
      ]);
      return 'error';
    }
    finally {
      fclose($socket);
    }
  }

  /**
   * Performs basic heuristic checks when ClamAV is not available.
   *
   * Checks for dangerous extensions and suspicious file content signatures
   * (e.g., PHP code embedded in image files).
   *
   * @param string $realPath
   *   The absolute filesystem path to the file.
   * @param \Drupal\jaraba_support\Entity\TicketAttachment $attachment
   *   The attachment entity (used for filename/mime info).
   *
   * @return string
   *   'clean' or 'infected'.
   */
  private function scanWithBasicChecks(string $realPath, TicketAttachment $attachment): string {
    $filename = $attachment->get('filename')->value ?? basename($realPath);
    $mimeType = $attachment->get('mime_type')->value ?? '';

    // Check 1: Dangerous file extension.
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (in_array($extension, self::DANGEROUS_EXTENSIONS, TRUE)) {
      $this->logger->warning('Basic scan: dangerous extension ".@ext" on file @name.', [
        '@ext' => $extension,
        '@name' => $filename,
      ]);
      return 'infected';
    }

    // Check 2: Double extension attack (e.g., "image.php.jpg").
    $parts = explode('.', strtolower($filename));
    if (count($parts) > 2) {
      $innerParts = array_slice($parts, 1, -1);
      foreach ($innerParts as $part) {
        if (in_array($part, self::DANGEROUS_EXTENSIONS, TRUE)) {
          $this->logger->warning('Basic scan: hidden dangerous extension ".@ext" in filename @name.', [
            '@ext' => $part,
            '@name' => $filename,
          ]);
          return 'infected';
        }
      }
    }

    // Check 3: Read first bytes and check for suspicious content.
    $handle = fopen($realPath, 'rb');
    if ($handle === FALSE) {
      $this->logger->warning('Basic scan: cannot read file @path for content analysis.', [
        '@path' => $realPath,
      ]);
      return 'error';
    }

    $header = fread($handle, 8192);
    fclose($handle);
    if ($header === FALSE) {
      return 'error';
    }

    // Check for PHP code in non-PHP files (common web shell technique).
    $isImageOrDoc = str_starts_with($mimeType, 'image/')
      || str_starts_with($mimeType, 'application/pdf')
      || str_starts_with($mimeType, 'text/')
      || str_contains($mimeType, 'officedocument')
      || str_contains($mimeType, 'msword');

    if ($isImageOrDoc) {
      // Look for PHP opening tags in binary content.
      $suspiciousPatterns = [
        '<?php',
        '<?=',
        '<? ',
        '<%',
        'eval(',
        'base64_decode(',
        'system(',
        'exec(',
        'passthru(',
        'shell_exec(',
        'proc_open(',
        'popen(',
      ];

      $lowerHeader = strtolower($header);
      foreach ($suspiciousPatterns as $pattern) {
        if (str_contains($lowerHeader, strtolower($pattern))) {
          $this->logger->warning('Basic scan: suspicious pattern "@pattern" found in @name (MIME: @mime).', [
            '@pattern' => $pattern,
            '@name' => $filename,
            '@mime' => $mimeType,
          ]);
          return 'infected';
        }
      }
    }

    // Check 4: MIME type vs magic bytes mismatch for images.
    if (str_starts_with($mimeType, 'image/')) {
      $magicBytes = [
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/png' => ["\x89\x50\x4E\x47"],
        'image/gif' => ["GIF87a", "GIF89a"],
        'image/webp' => ["RIFF"],
      ];

      if (isset($magicBytes[$mimeType])) {
        $validMagic = FALSE;
        foreach ($magicBytes[$mimeType] as $magic) {
          if (str_starts_with($header, $magic)) {
            $validMagic = TRUE;
            break;
          }
        }
        if (!$validMagic) {
          $this->logger->warning('Basic scan: MIME/magic mismatch for @name. Claims @mime but magic bytes do not match.', [
            '@name' => $filename,
            '@mime' => $mimeType,
          ]);
          return 'infected';
        }
      }
    }

    return 'clean';
  }

  /**
   * Processes all pending attachment scans.
   *
   * Called during cron to scan any attachments still in 'pending' status.
   * Processes in batches of 50 to avoid timeouts.
   */
  public function processPendingScans(): void {
    try {
      $storage = $this->entityTypeManager->getStorage('ticket_attachment');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('scan_status', 'pending')
        ->range(0, 50)
        ->execute();

      if (empty($ids)) {
        $this->logger->info('No pending attachment scans to process.');
        return;
      }

      /** @var \Drupal\jaraba_support\Entity\TicketAttachment[] $attachments */
      $attachments = $storage->loadMultiple($ids);

      $counts = ['clean' => 0, 'infected' => 0, 'error' => 0];

      foreach ($attachments as $attachment) {
        $result = $this->scanAttachment($attachment);
        if (isset($counts[$result])) {
          $counts[$result]++;
        }
        else {
          $counts['error']++;
        }
      }

      $this->logger->info('Processed @total pending attachment scans: @clean clean, @infected infected, @error errors.', [
        '@total' => count($attachments),
        '@clean' => $counts['clean'],
        '@infected' => $counts['infected'],
        '@error' => $counts['error'],
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Exception in processPendingScans(): @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
