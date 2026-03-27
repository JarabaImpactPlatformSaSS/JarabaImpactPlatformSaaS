<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de emisión de certificados del Método Jaraba.
 *
 * CERT-10: Genera PDF con CredentialPdfService + firma con FirmaDigitalService.
 * CERT-11: Genera código de verificación formato MJ-{YYYY}-{NNNNN}.
 *
 * Reutiliza infraestructura existente:
 * - jaraba_credentials: CredentialPdfService, IssuedCredential entity
 * - ecosistema_jaraba_core: FirmaDigitalService, CertificadoPdfService
 */
class CertificateIssuanceService {

  /**
   * Servicio de PDF de credenciales (optional cross-module @?).
   */
  protected ?object $credentialPdfService = NULL;

  /**
   * Servicio de firma digital (optional cross-module @?).
   */
  protected ?object $firmaService = NULL;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    ?object $credentialPdfService = NULL,
    ?object $firmaService = NULL,
  ) {
    $this->credentialPdfService = $credentialPdfService;
    $this->firmaService = $firmaService;
  }

  /**
   * Genera un código de verificación único para un certificado.
   *
   * Formato: MJ-{YYYY}-{NNNNN}
   * Ejemplo: MJ-2026-00042
   *
   * @return string
   *   Código de verificación único.
   */
  public function generateVerificationCode(): string {
    $year = date('Y');
    $sequence = $this->getNextSequence($year);
    return sprintf('MJ-%s-%05d', $year, $sequence);
  }

  /**
   * Emite un certificado para una UserCertification aprobada.
   *
   * 1. Genera código de verificación.
   * 2. Genera PDF via CredentialPdfService (si disponible).
   * 3. Firma el PDF via FirmaDigitalService (si disponible).
   * 4. Almacena el certificado.
   *
   * @param int $certificationId
   *   ID de la UserCertification.
   *
   * @return array{code: string, pdf_path: string|null, signed: bool}
   *   Resultado de la emisión.
   */
  public function issueCertificate(int $certificationId): array {
    $result = [
      'code' => '',
      'pdf_path' => NULL,
      'signed' => FALSE,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('user_certification');
      /** @var \Drupal\Core\Entity\ContentEntityInterface|null $cert */
      $cert = $storage->load($certificationId);
      if ($cert === NULL) {
        $this->logger->error('Certificate issuance: certification @id not found', ['@id' => $certificationId]);
        return $result;
      }

      // 1. Generar código.
      $code = $this->generateVerificationCode();
      $cert->set('certificate_number', $code);
      $result['code'] = $code;

      // 2. Generar PDF (si el servicio está disponible).
      if ($this->credentialPdfService !== NULL && method_exists($this->credentialPdfService, 'generatePdf')) {
        try {
          $userId = $cert->get('user_id')->target_id;
          $user = ($userId !== NULL && $userId !== '') ? $this->entityTypeManager->getStorage('user')->load($userId) : NULL;
          $pdfPath = $this->credentialPdfService->generatePdf([
            'recipient_name' => ($user !== NULL) ? $user->getDisplayName() : 'Participante',
            'credential_title' => 'Certificación Método Jaraba',
            'level' => $cert->get('overall_level')->value ?? 0,
            'code' => $code,
            'date' => date('Y-m-d'),
          ]);
          if (is_string($pdfPath) && $pdfPath !== '') {
            $result['pdf_path'] = $pdfPath;
          }
        }
        catch (\Throwable $e) {
          $this->logger->warning('PDF generation failed: @e', ['@e' => $e->getMessage()]);
        }
      }

      // 3. Firmar PDF (si disponible y hay PDF).
      if ($result['pdf_path'] !== NULL && $this->firmaService !== NULL && method_exists($this->firmaService, 'signPdf')) {
        try {
          $this->firmaService->signPdf($result['pdf_path']);
          $result['signed'] = TRUE;
        }
        catch (\Throwable $e) {
          $this->logger->warning('PDF signing failed: @e', ['@e' => $e->getMessage()]);
        }
      }

      // 4. Guardar entity con código y path.
      if ($result['pdf_path'] !== NULL) {
        $cert->set('certificate_pdf_path', $result['pdf_path']);
      }
      $cert->set('valid_from', date('Y-m-d'));
      $cert->set('valid_until', date('Y-m-d', strtotime('+2 years')));
      $cert->save();

      $this->logger->info('Certificate issued: @code for certification @id', [
        '@code' => $code,
        '@id' => $certificationId,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Certificate issuance failed: @e', ['@e' => $e->getMessage()]);
    }

    return $result;
  }

  /**
   * Obtiene el siguiente número de secuencia para el año dado.
   *
   * @param string $year
   *   Año (ej: '2026').
   *
   * @return int
   *   Siguiente número secuencial.
   */
  protected function getNextSequence(string $year): int {
    try {
      $count = $this->entityTypeManager->getStorage('user_certification')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('certificate_number', 'MJ-' . $year . '-', 'STARTS_WITH')
        ->count()
        ->execute();
      return $count + 1;
    }
    catch (\Throwable) {
      return 1;
    }
  }

}
