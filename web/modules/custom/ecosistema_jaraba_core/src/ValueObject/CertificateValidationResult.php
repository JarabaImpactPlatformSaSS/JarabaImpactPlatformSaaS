<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\ValueObject;

/**
 * Value Object inmutable para resultado de validacion de certificado PKCS#12.
 *
 * Encapsula el resultado de validar un certificado digital con metadatos
 * completos: vigencia, identidad del titular, emisor y estado. Compartido
 * por los tres modulos del stack fiscal (jaraba_verifactu, jaraba_facturae,
 * jaraba_einvoice_b2b).
 *
 * Plan Implementacion Stack Cumplimiento Fiscal v1 — FASE 0, entregable F0-2.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\CertificateManagerService
 */
final class CertificateValidationResult {

  /**
   * Construye un CertificateValidationResult.
   *
   * @param bool $isValid
   *   Si el certificado es valido y vigente.
   * @param \DateTimeImmutable|null $expiresAt
   *   Fecha de expiracion del certificado (NULL si no se pudo parsear).
   * @param int $daysUntilExpiry
   *   Dias restantes hasta expiracion (-1 si expirado o desconocido).
   * @param string $subject
   *   CN del titular del certificado (razon social o nombre).
   * @param string $issuer
   *   CN del emisor del certificado (CA).
   * @param string $serialNumber
   *   Numero de serie del certificado.
   * @param string $nif
   *   NIF/CIF extraido del certificado (campo serialNumber o OID 2.5.4.5).
   * @param string $errorMessage
   *   Mensaje de error si la validacion fallo (vacio si isValid=true).
   * @param string $errorCode
   *   Codigo de error estructurado (vacio si isValid=true).
   */
  public function __construct(
    public readonly bool $isValid,
    public readonly ?\DateTimeImmutable $expiresAt,
    public readonly int $daysUntilExpiry,
    public readonly string $subject,
    public readonly string $issuer,
    public readonly string $serialNumber,
    public readonly string $nif = '',
    public readonly string $errorMessage = '',
    public readonly string $errorCode = '',
  ) {
  }

  /**
   * Factory: crea un resultado valido con datos completos del certificado.
   *
   * @param array $certInfo
   *   Array devuelto por openssl_x509_parse().
   * @param string $nif
   *   NIF extraido del certificado.
   *
   * @return self
   *   Resultado con isValid=true.
   */
  public static function valid(array $certInfo, string $nif = ''): self {
    $expiresTimestamp = $certInfo['validTo_time_t'] ?? 0;
    $expiresAt = new \DateTimeImmutable('@' . $expiresTimestamp);
    $now = new \DateTimeImmutable();
    $daysUntilExpiry = (int) $now->diff($expiresAt)->format('%r%a');

    return new self(
      isValid: TRUE,
      expiresAt: $expiresAt,
      daysUntilExpiry: max($daysUntilExpiry, 0),
      subject: $certInfo['subject']['CN'] ?? '',
      issuer: $certInfo['issuer']['CN'] ?? '',
      serialNumber: $certInfo['serialNumber'] ?? '',
      nif: $nif,
    );
  }

  /**
   * Factory: crea un resultado de certificado expirado.
   *
   * @param array $certInfo
   *   Array devuelto por openssl_x509_parse().
   *
   * @return self
   *   Resultado con isValid=false, errorCode='CERT_EXPIRED'.
   */
  public static function expired(array $certInfo): self {
    $expiresTimestamp = $certInfo['validTo_time_t'] ?? 0;
    $expiresAt = new \DateTimeImmutable('@' . $expiresTimestamp);

    return new self(
      isValid: FALSE,
      expiresAt: $expiresAt,
      daysUntilExpiry: -1,
      subject: $certInfo['subject']['CN'] ?? '',
      issuer: $certInfo['issuer']['CN'] ?? '',
      serialNumber: $certInfo['serialNumber'] ?? '',
      errorMessage: 'Certificate has expired on ' . $expiresAt->format('Y-m-d'),
      errorCode: 'CERT_EXPIRED',
    );
  }

  /**
   * Factory: crea un resultado de certificado aun no vigente.
   *
   * @param array $certInfo
   *   Array devuelto por openssl_x509_parse().
   *
   * @return self
   *   Resultado con isValid=false, errorCode='CERT_NOT_YET_VALID'.
   */
  public static function notYetValid(array $certInfo): self {
    $validFromTimestamp = $certInfo['validFrom_time_t'] ?? 0;
    $validFrom = new \DateTimeImmutable('@' . $validFromTimestamp);

    return new self(
      isValid: FALSE,
      expiresAt: NULL,
      daysUntilExpiry: -1,
      subject: $certInfo['subject']['CN'] ?? '',
      issuer: $certInfo['issuer']['CN'] ?? '',
      serialNumber: $certInfo['serialNumber'] ?? '',
      errorMessage: 'Certificate is not valid until ' . $validFrom->format('Y-m-d'),
      errorCode: 'CERT_NOT_YET_VALID',
    );
  }

  /**
   * Factory: crea un resultado de error generico (archivo no encontrado, etc.).
   *
   * @param string $errorMessage
   *   Descripcion del error.
   * @param string $errorCode
   *   Codigo estructurado del error.
   *
   * @return self
   *   Resultado con isValid=false y campos vacios.
   */
  public static function error(string $errorMessage, string $errorCode): self {
    return new self(
      isValid: FALSE,
      expiresAt: NULL,
      daysUntilExpiry: -1,
      subject: '',
      issuer: '',
      serialNumber: '',
      errorMessage: $errorMessage,
      errorCode: $errorCode,
    );
  }

  /**
   * Indica si el certificado esta proximo a expirar.
   *
   * @param int $thresholdDays
   *   Numero de dias de umbral (default 30).
   *
   * @return bool
   *   TRUE si el certificado expira dentro del umbral.
   */
  public function isExpiringSoon(int $thresholdDays = 30): bool {
    if (!$this->isValid) {
      return FALSE;
    }
    return $this->daysUntilExpiry <= $thresholdDays;
  }

  /**
   * Convierte a array para respuestas JSON API y serializacion.
   *
   * @return array
   *   Representacion en array del resultado.
   */
  public function toArray(): array {
    return [
      'is_valid' => $this->isValid,
      'expires_at' => $this->expiresAt?->format('c'),
      'days_until_expiry' => $this->daysUntilExpiry,
      'subject' => $this->subject,
      'issuer' => $this->issuer,
      'serial_number' => $this->serialNumber,
      'nif' => $this->nif,
      'error_message' => $this->errorMessage,
      'error_code' => $this->errorCode,
    ];
  }

}
