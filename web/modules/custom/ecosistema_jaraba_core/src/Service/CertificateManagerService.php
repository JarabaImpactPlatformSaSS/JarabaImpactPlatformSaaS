<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Psr\Log\LoggerInterface;
use Drupal\ecosistema_jaraba_core\ValueObject\CertificateValidationResult;

/**
 * Gestiona certificados digitales PKCS#12 para firma electronica y autenticacion.
 *
 * Servicio centralizado compartido por jaraba_verifactu, jaraba_facturae y
 * jaraba_einvoice_b2b. Responsabilidades:
 *
 * - Almacenamiento seguro en private://certificates/{tenant_id}/ (NUNCA public://)
 * - Lectura y parseo de archivos PKCS#12 (.p12/.pfx)
 * - Validacion de vigencia con alertas de expiracion configurable
 * - Extraccion de clave privada OpenSSL para firma XAdES/SOAP
 * - Extraccion de certificado X.509 en formato PEM
 * - Extraccion del NIF/CIF del titular desde el certificado
 * - Listado de certificados proximos a expirar (alerta cron)
 *
 * SEGURIDAD:
 * - Las contrasenas de certificados se almacenan cifradas en la entidad
 *   *_tenant_config de cada modulo fiscal. Este servicio NO almacena
 *   contrasenas: las recibe como parametro en cada operacion.
 * - Los archivos .p12 se guardan con permisos 0600 en private://.
 * - Se usa LockBackendInterface para operaciones de escritura concurrente.
 *
 * REGLAS:
 * - TENANT-001: Aislamiento por tenant (private://certificates/{tenant_id}/).
 * - AUDIT-PERF-002: Lock para escritura concurrente de certificados.
 *
 * Plan Implementacion Stack Cumplimiento Fiscal v1 — FASE 0, entregable F0-1.
 *
 * @package Drupal\ecosistema_jaraba_core\Service
 */
class CertificateManagerService {

  /**
   * Directorio base para certificados dentro de private://.
   */
  const CERTIFICATE_BASE_DIR = 'private://certificates';

  /**
   * Dias de umbral por defecto para alerta de expiracion.
   */
  const DEFAULT_EXPIRY_THRESHOLD_DAYS = 30;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   El sistema de archivos de Drupal.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del modulo.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   El backend de locks para operaciones concurrentes.
   */
  public function __construct(
    protected FileSystemInterface $fileSystem,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected LockBackendInterface $lock,
  ) {
  }

  /**
   * Almacena un certificado PKCS#12 para un tenant.
   *
   * Valida el certificado antes de guardarlo. Si ya existe un certificado
   * para el tenant, lo reemplaza (manteniendo backup del anterior).
   *
   * @param int $tenantId
   *   ID del grupo/tenant.
   * @param string $fileContent
   *   Contenido binario del archivo .p12/.pfx.
   * @param string $password
   *   Contrasena del PKCS#12.
   * @param string $originalFilename
   *   Nombre original del archivo subido.
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\CertificateValidationResult
   *   Resultado de la validacion. Si isValid=true, el certificado fue guardado.
   */
  public function storeCertificate(int $tenantId, string $fileContent, string $password, string $originalFilename = 'certificate.p12'): CertificateValidationResult {
    $lockName = 'certificate_store_' . $tenantId;

    if (!$this->lock->acquire($lockName, 30)) {
      return CertificateValidationResult::error(
        'Could not acquire lock for certificate storage. Another operation is in progress.',
        'CERT_LOCK_FAILED',
      );
    }

    try {
      // Validar que el contenido es un PKCS#12 valido.
      $certs = [];
      if (!openssl_pkcs12_read($fileContent, $certs, $password)) {
        return CertificateValidationResult::error(
          'Invalid PKCS#12 file or incorrect password.',
          'CERT_INVALID_PKCS12',
        );
      }

      // Parsear informacion del certificado X.509.
      $certInfo = openssl_x509_parse($certs['cert']);
      if ($certInfo === FALSE) {
        return CertificateValidationResult::error(
          'Could not parse X.509 certificate from PKCS#12.',
          'CERT_PARSE_FAILED',
        );
      }

      // Verificar vigencia.
      $now = time();
      $validFrom = $certInfo['validFrom_time_t'] ?? 0;
      $validTo = $certInfo['validTo_time_t'] ?? 0;

      if ($now < $validFrom) {
        return CertificateValidationResult::notYetValid($certInfo);
      }

      if ($now > $validTo) {
        return CertificateValidationResult::expired($certInfo);
      }

      // Extraer NIF del certificado.
      $nif = $this->extractNifFromCertificate($certInfo);

      // Asegurar directorio del tenant.
      $tenantDir = self::CERTIFICATE_BASE_DIR . '/' . $tenantId;
      $this->fileSystem->prepareDirectory($tenantDir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

      // Si existe certificado previo, crear backup.
      $targetPath = $tenantDir . '/certificate.p12';
      $realPath = $this->fileSystem->realpath($targetPath);
      if ($realPath && file_exists($realPath)) {
        $backupPath = $tenantDir . '/certificate_backup_' . date('Ymd_His') . '.p12';
        $this->fileSystem->copy($targetPath, $backupPath, FileSystemInterface::EXISTS_RENAME);
        $this->logger->info('Certificate backup created for tenant @tid: @path', [
          '@tid' => $tenantId,
          '@path' => $backupPath,
        ]);
      }

      // Guardar el nuevo certificado.
      $savedUri = $this->fileSystem->saveData(
        $fileContent,
        $targetPath,
        FileSystemInterface::EXISTS_REPLACE,
      );

      if (!$savedUri) {
        return CertificateValidationResult::error(
          'Failed to save certificate file to private storage.',
          'CERT_SAVE_FAILED',
        );
      }

      // Establecer permisos restrictivos.
      $savedRealPath = $this->fileSystem->realpath($savedUri);
      if ($savedRealPath) {
        chmod($savedRealPath, 0600);
      }

      $this->logger->info('Certificate stored for tenant @tid. Subject: @cn, NIF: @nif, Expires: @exp', [
        '@tid' => $tenantId,
        '@cn' => $certInfo['subject']['CN'] ?? 'Unknown',
        '@nif' => $nif ?: 'N/A',
        '@exp' => date('Y-m-d', $validTo),
      ]);

      return CertificateValidationResult::valid($certInfo, $nif);
    }
    finally {
      $this->lock->release($lockName);
    }
  }

  /**
   * Carga el contenido del certificado PKCS#12 de un tenant.
   *
   * @param int $tenantId
   *   ID del grupo/tenant.
   *
   * @return string|null
   *   Contenido binario del .p12, o NULL si no existe.
   */
  public function loadCertificateFile(int $tenantId): ?string {
    $targetPath = self::CERTIFICATE_BASE_DIR . '/' . $tenantId . '/certificate.p12';
    $realPath = $this->fileSystem->realpath($targetPath);

    if (!$realPath || !file_exists($realPath)) {
      $this->logger->warning('Certificate file not found for tenant @tid: @path', [
        '@tid' => $tenantId,
        '@path' => $targetPath,
      ]);
      return NULL;
    }

    $content = file_get_contents($realPath);
    return $content !== FALSE ? $content : NULL;
  }

  /**
   * Valida el certificado almacenado de un tenant.
   *
   * @param int $tenantId
   *   ID del grupo/tenant.
   * @param string $password
   *   Contrasena del PKCS#12.
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\CertificateValidationResult
   *   Resultado de la validacion.
   */
  public function validateCertificate(int $tenantId, string $password): CertificateValidationResult {
    $fileContent = $this->loadCertificateFile($tenantId);

    if ($fileContent === NULL) {
      return CertificateValidationResult::error(
        'No certificate file found for tenant ' . $tenantId . '.',
        'CERT_NOT_FOUND',
      );
    }

    $certs = [];
    if (!openssl_pkcs12_read($fileContent, $certs, $password)) {
      return CertificateValidationResult::error(
        'Could not read PKCS#12 file. The password may be incorrect or the file corrupted.',
        'CERT_READ_FAILED',
      );
    }

    $certInfo = openssl_x509_parse($certs['cert']);
    if ($certInfo === FALSE) {
      return CertificateValidationResult::error(
        'Could not parse X.509 certificate.',
        'CERT_PARSE_FAILED',
      );
    }

    $now = time();
    $validFrom = $certInfo['validFrom_time_t'] ?? 0;
    $validTo = $certInfo['validTo_time_t'] ?? 0;

    if ($now < $validFrom) {
      return CertificateValidationResult::notYetValid($certInfo);
    }

    if ($now > $validTo) {
      return CertificateValidationResult::expired($certInfo);
    }

    $nif = $this->extractNifFromCertificate($certInfo);

    return CertificateValidationResult::valid($certInfo, $nif);
  }

  /**
   * Obtiene la clave privada del certificado de un tenant.
   *
   * @param int $tenantId
   *   ID del grupo/tenant.
   * @param string $password
   *   Contrasena del PKCS#12.
   *
   * @return \OpenSSLAsymmetricKey|null
   *   Clave privada OpenSSL, o NULL si falla.
   */
  public function getPrivateKey(int $tenantId, string $password): ?\OpenSSLAsymmetricKey {
    $fileContent = $this->loadCertificateFile($tenantId);
    if ($fileContent === NULL) {
      return NULL;
    }

    $certs = [];
    if (!openssl_pkcs12_read($fileContent, $certs, $password)) {
      $this->logger->error('Failed to read PKCS#12 for private key extraction. Tenant: @tid', [
        '@tid' => $tenantId,
      ]);
      return NULL;
    }

    $privateKey = openssl_pkey_get_private($certs['pkey']);
    if ($privateKey === FALSE) {
      $this->logger->error('Failed to extract private key from PKCS#12. Tenant: @tid', [
        '@tid' => $tenantId,
      ]);
      return NULL;
    }

    return $privateKey;
  }

  /**
   * Obtiene el certificado X.509 en formato PEM de un tenant.
   *
   * @param int $tenantId
   *   ID del grupo/tenant.
   * @param string $password
   *   Contrasena del PKCS#12.
   *
   * @return string|null
   *   Certificado X.509 en formato PEM, o NULL si falla.
   */
  public function getX509Certificate(int $tenantId, string $password): ?string {
    $fileContent = $this->loadCertificateFile($tenantId);
    if ($fileContent === NULL) {
      return NULL;
    }

    $certs = [];
    if (!openssl_pkcs12_read($fileContent, $certs, $password)) {
      $this->logger->error('Failed to read PKCS#12 for X.509 extraction. Tenant: @tid', [
        '@tid' => $tenantId,
      ]);
      return NULL;
    }

    return $certs['cert'] ?? NULL;
  }

  /**
   * Obtiene la cadena de certificados intermedios (CA chain) en formato PEM.
   *
   * @param int $tenantId
   *   ID del grupo/tenant.
   * @param string $password
   *   Contrasena del PKCS#12.
   *
   * @return array
   *   Array de certificados intermedios en formato PEM. Vacio si no hay.
   */
  public function getCertificateChain(int $tenantId, string $password): array {
    $fileContent = $this->loadCertificateFile($tenantId);
    if ($fileContent === NULL) {
      return [];
    }

    $certs = [];
    if (!openssl_pkcs12_read($fileContent, $certs, $password)) {
      return [];
    }

    return $certs['extracerts'] ?? [];
  }

  /**
   * Lista los tenants con certificados proximos a expirar.
   *
   * Recorre todos los directorios de tenant en private://certificates/
   * y verifica la fecha de expiracion de cada certificado. Usado por
   * hook_cron para generar alertas.
   *
   * @param int $daysThreshold
   *   Dias de umbral para considerar un certificado proximo a expirar.
   * @param array $tenantPasswords
   *   Mapa asociativo de tenant_id => password para descifrar los PKCS#12.
   *   Los modulos fiscales pasan este mapa desde sus *_tenant_config entities.
   *
   * @return array
   *   Array de CertificateValidationResult indexado por tenant_id.
   *   Solo incluye certificados validos pero proximos a expirar.
   */
  public function getExpiringCertificates(int $daysThreshold = self::DEFAULT_EXPIRY_THRESHOLD_DAYS, array $tenantPasswords = []): array {
    $expiring = [];

    foreach ($tenantPasswords as $tenantId => $password) {
      $result = $this->validateCertificate((int) $tenantId, $password);

      if ($result->isValid && $result->isExpiringSoon($daysThreshold)) {
        $expiring[$tenantId] = $result;
      }
    }

    return $expiring;
  }

  /**
   * Verifica si un tenant tiene un certificado almacenado.
   *
   * @param int $tenantId
   *   ID del grupo/tenant.
   *
   * @return bool
   *   TRUE si existe un archivo de certificado para el tenant.
   */
  public function hasCertificate(int $tenantId): bool {
    $targetPath = self::CERTIFICATE_BASE_DIR . '/' . $tenantId . '/certificate.p12';
    $realPath = $this->fileSystem->realpath($targetPath);
    return $realPath !== FALSE && file_exists($realPath);
  }

  /**
   * Elimina el certificado de un tenant.
   *
   * Crea un backup antes de eliminar por seguridad.
   *
   * @param int $tenantId
   *   ID del grupo/tenant.
   *
   * @return bool
   *   TRUE si se elimino correctamente.
   */
  public function removeCertificate(int $tenantId): bool {
    $lockName = 'certificate_store_' . $tenantId;

    if (!$this->lock->acquire($lockName, 10)) {
      $this->logger->warning('Could not acquire lock to remove certificate for tenant @tid.', [
        '@tid' => $tenantId,
      ]);
      return FALSE;
    }

    try {
      $targetPath = self::CERTIFICATE_BASE_DIR . '/' . $tenantId . '/certificate.p12';
      $realPath = $this->fileSystem->realpath($targetPath);

      if (!$realPath || !file_exists($realPath)) {
        return TRUE;
      }

      // Backup antes de eliminar.
      $backupPath = self::CERTIFICATE_BASE_DIR . '/' . $tenantId . '/certificate_removed_' . date('Ymd_His') . '.p12';
      $this->fileSystem->copy($targetPath, $backupPath, FileSystemInterface::EXISTS_RENAME);

      $this->fileSystem->delete($targetPath);

      $this->logger->info('Certificate removed for tenant @tid. Backup at @backup.', [
        '@tid' => $tenantId,
        '@backup' => $backupPath,
      ]);

      return TRUE;
    }
    finally {
      $this->lock->release($lockName);
    }
  }

  /**
   * Extrae el NIF/CIF del certificado X.509.
   *
   * Los certificados espanoles de persona juridica incluyen el CIF
   * en el campo serialNumber del subject (OID 2.5.4.5) o en el
   * campo organizationIdentifier (OID 2.5.4.97).
   *
   * @param array $certInfo
   *   Array devuelto por openssl_x509_parse().
   *
   * @return string
   *   NIF/CIF extraido, o string vacio si no se encuentra.
   */
  protected function extractNifFromCertificate(array $certInfo): string {
    // Prioridad 1: Campo serialNumber del subject (formato IDCES-XXXXXXXX o VATES-XXXXXXXX).
    $serialNumber = $certInfo['subject']['serialNumber'] ?? '';
    if (preg_match('/(?:IDCES-|VATES-)?([A-Z]\d{7}[A-Z0-9]|\d{8}[A-Z])/', $serialNumber, $matches)) {
      return $matches[1];
    }

    // Prioridad 2: organizationIdentifier (OID 2.5.4.97).
    $orgId = $certInfo['subject']['organizationIdentifier'] ?? '';
    if (preg_match('/(?:VATES)?([A-Z]\d{7}[A-Z0-9])/', $orgId, $matches)) {
      return $matches[1];
    }

    // Prioridad 3: NIF de persona fisica en serialNumber directo.
    if (preg_match('/^(\d{8}[A-Z])$/', $serialNumber, $matches)) {
      return $matches[1];
    }

    return '';
  }

}
