<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for managing course certifications.
 *
 * Gestiona la emisión, verificación y revocación de certificados
 * vinculados a la finalización de learning paths y cursos.
 */
class CertificationService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EnrollmentService $enrollmentService,
  ) {}

  /**
   * Issues a certificate for completing a learning path.
   *
   * @param int $userId
   *   The user ID.
   * @param int $pathId
   *   The learning path ID.
   *
   * @return array
   *   Certificate data with 'certificate_code', 'issued_at', 'path_title'.
   */
  public function issueCertificate(int $userId, int $pathId): array {
    $storage = $this->entityTypeManager->getStorage('lms_learning_path');
    $path = $storage->load($pathId);

    if (!$path) {
      return ['error' => 'Learning path not found'];
    }

    // Check if user has completed the path.
    $enrollment = $this->enrollmentService->getEnrollment($userId, $pathId);
    if (!$enrollment || ($enrollment['progress'] ?? 0) < 100) {
      return ['error' => 'Learning path not completed'];
    }

    // Check for existing certificate.
    $existing = $this->findCertificate($userId, $pathId);
    if ($existing) {
      return $existing;
    }

    // Generate unique certificate code.
    $code = $this->generateCertificateCode($userId, $pathId);

    $certStorage = $this->entityTypeManager->getStorage('lms_certificate');
    $certificate = $certStorage->create([
      'user_id' => $userId,
      'path_id' => $pathId,
      'certificate_code' => $code,
      'path_title' => $path->label() ?? '',
      'status' => 'active',
      'issued_at' => date('Y-m-d\TH:i:s'),
    ]);

    try {
      $certificate->save();
    }
    catch (\Exception $e) {
      return ['error' => 'Failed to issue certificate: ' . $e->getMessage()];
    }

    return [
      'certificate_id' => (int) $certificate->id(),
      'certificate_code' => $code,
      'user_id' => $userId,
      'path_id' => $pathId,
      'path_title' => $path->label() ?? '',
      'issued_at' => date('Y-m-d\TH:i:s'),
      'status' => 'active',
      'verify_url' => '/verify/certificate/' . $code,
    ];
  }

  /**
   * Verifies a certificate by its unique code.
   *
   * @param string $certificateCode
   *   The certificate code.
   *
   * @return array|null
   *   Certificate data or NULL if not found/revoked.
   */
  public function verifyCertificate(string $certificateCode): ?array {
    $storage = $this->entityTypeManager->getStorage('lms_certificate');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('certificate_code', $certificateCode)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    $certificate = $storage->load(reset($ids));
    if (!$certificate) {
      return NULL;
    }

    $status = $certificate->get('status')->value ?? 'active';

    return [
      'certificate_code' => $certificateCode,
      'user_id' => (int) ($certificate->get('user_id')->target_id ?? $certificate->get('user_id')->value ?? 0),
      'path_title' => $certificate->get('path_title')->value ?? '',
      'issued_at' => $certificate->get('issued_at')->value ?? '',
      'status' => $status,
      'valid' => $status === 'active',
    ];
  }

  /**
   * Gets all certificates for a user.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array
   *   List of certificate data arrays.
   */
  public function getCertificates(int $userId): array {
    $storage = $this->entityTypeManager->getStorage('lms_certificate');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $userId)
      ->sort('issued_at', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $certificates = $storage->loadMultiple($ids);
    $result = [];

    foreach ($certificates as $cert) {
      $result[] = [
        'certificate_id' => (int) $cert->id(),
        'certificate_code' => $cert->get('certificate_code')->value ?? '',
        'path_title' => $cert->get('path_title')->value ?? '',
        'issued_at' => $cert->get('issued_at')->value ?? '',
        'status' => $cert->get('status')->value ?? 'active',
      ];
    }

    return $result;
  }

  /**
   * Revokes a certificate.
   *
   * @param string $certificateCode
   *   The certificate code.
   * @param string $reason
   *   The revocation reason.
   *
   * @return bool
   *   TRUE if revoked successfully.
   */
  public function revokeCertificate(string $certificateCode, string $reason): bool {
    $storage = $this->entityTypeManager->getStorage('lms_certificate');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('certificate_code', $certificateCode)
      ->condition('status', 'active')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return FALSE;
    }

    $certificate = $storage->load(reset($ids));
    if (!$certificate) {
      return FALSE;
    }

    try {
      $certificate->set('status', 'revoked');
      $certificate->set('revocation_reason', $reason);
      $certificate->set('revoked_at', date('Y-m-d\TH:i:s'));
      $certificate->save();
      return TRUE;
    }
    catch (\Exception) {
      return FALSE;
    }
  }

  /**
   * Finds existing certificate for a user+path combination.
   */
  protected function findCertificate(int $userId, int $pathId): ?array {
    $storage = $this->entityTypeManager->getStorage('lms_certificate');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $userId)
      ->condition('path_id', $pathId)
      ->condition('status', 'active')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    $cert = $storage->load(reset($ids));
    if (!$cert) {
      return NULL;
    }

    return [
      'certificate_id' => (int) $cert->id(),
      'certificate_code' => $cert->get('certificate_code')->value ?? '',
      'path_title' => $cert->get('path_title')->value ?? '',
      'issued_at' => $cert->get('issued_at')->value ?? '',
      'status' => 'active',
    ];
  }

  /**
   * Generates a unique certificate code.
   */
  protected function generateCertificateCode(int $userId, int $pathId): string {
    $prefix = 'JIP';
    $hash = strtoupper(substr(hash('sha256', $userId . '-' . $pathId . '-' . microtime(TRUE)), 0, 8));
    return sprintf('%s-%04d-%s', $prefix, $pathId, $hash);
  }

}
