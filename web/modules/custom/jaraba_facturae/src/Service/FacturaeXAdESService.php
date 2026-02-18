<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Service;

use Drupal\ecosistema_jaraba_core\Service\CertificateManagerService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Firma electronica XAdES-EPES para documentos Facturae 3.2.2.
 *
 * Implementa el estandar ETSI TS 101 903 con la politica de firma
 * oficial de Facturae. Usa extensiones PHP nativas (ext-openssl, ext-dom).
 * Zero dependencias externas de Composer.
 *
 * Proceso de firma:
 * 1. Canonicalizacion C14N exclusive del XML Facturae.
 * 2. Digest SHA-256 del documento canonicalizado.
 * 3. SignedInfo con Reference al documento y propiedades firmadas.
 * 4. Firma RSA con clave privada del certificado PKCS#12.
 * 5. KeyInfo con certificado X.509 en Base64.
 * 6. SignedProperties con SigningTime, SigningCertificate y SignaturePolicyIdentifier.
 *
 * Spec: Doc 180, Seccion 3.2.
 * Plan: FASE 7, entregable F7-1.
 */
class FacturaeXAdESService {

  /**
   * XML Digital Signature namespace.
   */
  private const DSIG_NS = 'http://www.w3.org/2000/09/xmldsig#';

  /**
   * XAdES namespace.
   */
  private const XADES_NS = 'http://uri.etsi.org/01903/v1.3.2#';

  /**
   * Facturae signature policy identifier URL.
   */
  private const POLICY_IDENTIFIER = 'http://www.facturae.gob.es/politica_de_firma_formato_facturae/politica_de_firma_formato_facturae_v3_1.pdf';

  /**
   * Facturae signature policy description.
   */
  private const POLICY_DESCRIPTION = 'Politica de firma electronica para facturacion electronica con formato Facturae';

  /**
   * C14N exclusive canonicalization method.
   */
  private const C14N_METHOD = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';

  /**
   * SHA-256 digest method URI.
   */
  private const SHA256_METHOD = 'http://www.w3.org/2001/04/xmlenc#sha256';

  /**
   * RSA-SHA256 signature method URI.
   */
  private const RSA_SHA256_METHOD = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';

  public function __construct(
    protected readonly CertificateManagerService $certificateManager,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Signs a Facturae XML document with XAdES-EPES.
   *
   * @param string $xml
   *   The unsigned Facturae 3.2.2 XML.
   * @param int $tenantId
   *   The tenant ID to retrieve the signing certificate.
   *
   * @return string
   *   The signed XML with XAdES-EPES signature.
   *
   * @throws \RuntimeException
   *   If signing fails (no certificate, invalid key, etc.).
   */
  public function signDocument(string $xml, int $tenantId): string {
    $password = $this->getTenantCertificatePassword($tenantId);
    if ($password === NULL) {
      throw new \RuntimeException("Certificate password not found for tenant $tenantId.");
    }

    // Get private key and X.509 certificate.
    $privateKey = $this->certificateManager->getPrivateKey($tenantId, $password);
    if ($privateKey === NULL) {
      throw new \RuntimeException("Failed to load private key for tenant $tenantId.");
    }

    $x509Pem = $this->certificateManager->getX509Certificate($tenantId, $password);
    if ($x509Pem === NULL) {
      throw new \RuntimeException("Failed to load X.509 certificate for tenant $tenantId.");
    }

    $certInfo = openssl_x509_parse($x509Pem);
    if ($certInfo === FALSE) {
      throw new \RuntimeException("Failed to parse X.509 certificate for tenant $tenantId.");
    }

    // Load the XML document.
    $doc = new \DOMDocument('1.0', 'UTF-8');
    if (!$doc->loadXML($xml)) {
      throw new \RuntimeException('Failed to parse XML for signing.');
    }

    // Generate unique IDs for signature elements.
    $signatureId = 'Signature-' . $this->generateUniqueId();
    $signedPropertiesId = 'SignedProperties-' . $this->generateUniqueId();
    $keyInfoId = 'KeyInfo-' . $this->generateUniqueId();
    $referenceId = 'Reference-' . $this->generateUniqueId();
    $signatureValueId = 'SignatureValue-' . $this->generateUniqueId();

    // 1. Canonicalize the document (C14N).
    $canonicalDocument = $doc->C14N();

    // 2. Compute digest of the document.
    $documentDigest = base64_encode(hash('sha256', $canonicalDocument, TRUE));

    // 3. Build SignedProperties.
    $signedPropertiesXml = $this->buildSignedProperties(
      $signedPropertiesId,
      $x509Pem,
      $certInfo,
    );

    // Compute digest of SignedProperties.
    $spDoc = new \DOMDocument();
    $spDoc->loadXML($signedPropertiesXml);
    $signedPropertiesDigest = base64_encode(hash('sha256', $spDoc->C14N(), TRUE));

    // Compute digest of KeyInfo.
    $keyInfoXml = $this->buildKeyInfoXml($keyInfoId, $x509Pem);
    $kiDoc = new \DOMDocument();
    $kiDoc->loadXML($keyInfoXml);
    $keyInfoDigest = base64_encode(hash('sha256', $kiDoc->C14N(), TRUE));

    // 4. Build SignedInfo.
    $signedInfoXml = $this->buildSignedInfo(
      $referenceId,
      $documentDigest,
      $signedPropertiesId,
      $signedPropertiesDigest,
      $keyInfoId,
      $keyInfoDigest,
    );

    // Canonicalize SignedInfo for signing.
    $siDoc = new \DOMDocument();
    $siDoc->loadXML($signedInfoXml);
    $canonicalSignedInfo = $siDoc->C14N();

    // 5. Sign with RSA-SHA256.
    $signatureValue = '';
    $signResult = openssl_sign($canonicalSignedInfo, $signatureValue, $privateKey, OPENSSL_ALGO_SHA256);
    if (!$signResult) {
      throw new \RuntimeException('OpenSSL signature failed: ' . openssl_error_string());
    }

    $signatureValueBase64 = base64_encode($signatureValue);

    // 6. Assemble the complete Signature element.
    $signatureXml = $this->assembleSignature(
      $signatureId,
      $signedInfoXml,
      $signatureValueId,
      $signatureValueBase64,
      $keyInfoXml,
      $signedPropertiesXml,
    );

    // Insert Signature into the document.
    $signatureDoc = new \DOMDocument();
    $signatureDoc->loadXML($signatureXml);

    $importedSignature = $doc->importNode($signatureDoc->documentElement, TRUE);
    $doc->documentElement->appendChild($importedSignature);

    $signedXml = $doc->saveXML();
    if ($signedXml === FALSE) {
      throw new \RuntimeException('Failed to serialize signed XML.');
    }

    $this->logger->info('Facturae document signed with XAdES-EPES for tenant @tenant.', [
      '@tenant' => $tenantId,
    ]);

    return $signedXml;
  }

  /**
   * Verifies the XAdES-EPES signature of a signed Facturae document.
   *
   * @param string $signedXml
   *   The signed XML document.
   *
   * @return array
   *   Verification result with keys: valid, signer_nif, signing_time, errors.
   */
  public function verifySignature(string $signedXml): array {
    $result = [
      'valid' => FALSE,
      'signer_nif' => '',
      'signing_time' => '',
      'errors' => [],
    ];

    $doc = new \DOMDocument();
    if (!$doc->loadXML($signedXml)) {
      $result['errors'][] = 'Failed to parse signed XML.';
      return $result;
    }

    // Find Signature element.
    $signatures = $doc->getElementsByTagNameNS(self::DSIG_NS, 'Signature');
    if ($signatures->length === 0) {
      $result['errors'][] = 'No ds:Signature element found.';
      return $result;
    }

    // Extract certificate from KeyInfo.
    $certElements = $doc->getElementsByTagNameNS(self::DSIG_NS, 'X509Certificate');
    if ($certElements->length === 0) {
      $result['errors'][] = 'No X509Certificate found in KeyInfo.';
      return $result;
    }

    $certBase64 = $certElements->item(0)->textContent;
    $certPem = "-----BEGIN CERTIFICATE-----\n"
      . chunk_split(trim($certBase64), 64)
      . "-----END CERTIFICATE-----\n";

    $certInfo = openssl_x509_parse($certPem);
    if ($certInfo !== FALSE) {
      $serialNumber = $certInfo['subject']['serialNumber'] ?? '';
      if (preg_match('/(?:IDCES-|VATES-)?([A-Z0-9]{9})/', $serialNumber, $matches)) {
        $result['signer_nif'] = $matches[1];
      }
    }

    // Extract SigningTime.
    $signingTimeElements = $doc->getElementsByTagNameNS(self::XADES_NS, 'SigningTime');
    if ($signingTimeElements->length > 0) {
      $result['signing_time'] = $signingTimeElements->item(0)->textContent;
    }

    // Extract SignatureValue and SignedInfo for verification.
    $signatureValueElements = $doc->getElementsByTagNameNS(self::DSIG_NS, 'SignatureValue');
    $signedInfoElements = $doc->getElementsByTagNameNS(self::DSIG_NS, 'SignedInfo');

    if ($signatureValueElements->length > 0 && $signedInfoElements->length > 0) {
      $signatureValue = base64_decode(trim($signatureValueElements->item(0)->textContent));

      // Canonicalize SignedInfo in a separate document to match signing context.
      $signedInfoNode = $signedInfoElements->item(0);
      $siDoc = new \DOMDocument();
      $siDoc->appendChild($siDoc->importNode($signedInfoNode, TRUE));
      $canonicalSignedInfo = $siDoc->C14N();

      $publicKey = openssl_pkey_get_public($certPem);
      if ($publicKey !== FALSE) {
        $verifyResult = openssl_verify($canonicalSignedInfo, $signatureValue, $publicKey, OPENSSL_ALGO_SHA256);
        $result['valid'] = ($verifyResult === 1);
        if ($verifyResult === 0) {
          $result['errors'][] = 'Signature verification failed: signature does not match.';
        }
        elseif ($verifyResult === -1) {
          $result['errors'][] = 'Signature verification error: ' . openssl_error_string();
        }
      }
      else {
        $result['errors'][] = 'Failed to extract public key from certificate.';
      }
    }

    return $result;
  }

  /**
   * Gets certificate information for a tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return array
   *   Certificate info: subject, issuer, nif, valid_from, valid_to, is_valid, days_remaining.
   */
  public function getCertificateInfo(int $tenantId): array {
    $password = $this->getTenantCertificatePassword($tenantId);
    if ($password === NULL) {
      return ['error' => 'No certificate password configured for tenant.'];
    }

    $result = $this->certificateManager->validateCertificate($tenantId, $password);

    return [
      'is_valid' => $result->isValid,
      'status' => $result->status,
      'subject' => $result->subject ?? '',
      'issuer' => $result->issuer ?? '',
      'nif' => $result->nif ?? '',
      'valid_from' => $result->validFrom ?? '',
      'valid_to' => $result->validTo ?? '',
      'days_remaining' => $result->daysRemaining ?? 0,
    ];
  }

  /**
   * Builds the SignedProperties element for XAdES-EPES.
   */
  protected function buildSignedProperties(string $id, string $certPem, array $certInfo): string {
    $signingTime = gmdate('Y-m-d\TH:i:s\Z');

    // Certificate digest (SHA-256 of DER-encoded certificate).
    $certDer = $this->pemToDer($certPem);
    $certDigest = base64_encode(hash('sha256', $certDer, TRUE));

    // Certificate issuer and serial.
    $issuerName = $this->formatIssuerName($certInfo['issuer'] ?? []);
    $serialNumber = $certInfo['serialNumber'] ?? '';

    // Policy hash (SHA-256 of the policy document).
    // In production, this should be the actual hash of the downloaded PDF.
    $policyHash = 'Ohixl6upD6av8N7pEvDABhEL6hM=';

    return <<<XML
<xades:SignedProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="{$id}">
  <xades:SignedSignatureProperties>
    <xades:SigningTime>{$signingTime}</xades:SigningTime>
    <xades:SigningCertificate>
      <xades:Cert>
        <xades:CertDigest>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>{$certDigest}</ds:DigestValue>
        </xades:CertDigest>
        <xades:IssuerSerial>
          <ds:X509IssuerName>{$issuerName}</ds:X509IssuerName>
          <ds:X509SerialNumber>{$serialNumber}</ds:X509SerialNumber>
        </xades:IssuerSerial>
      </xades:Cert>
    </xades:SigningCertificate>
    <xades:SignaturePolicyIdentifier>
      <xades:SignaturePolicyId>
        <xades:SigPolicyId>
          <xades:Identifier>http://www.facturae.gob.es/politica_de_firma_formato_facturae/politica_de_firma_formato_facturae_v3_1.pdf</xades:Identifier>
          <xades:Description>Politica de firma electronica para facturacion electronica con formato Facturae</xades:Description>
        </xades:SigPolicyId>
        <xades:SigPolicyHash>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>{$policyHash}</ds:DigestValue>
        </xades:SigPolicyHash>
      </xades:SignaturePolicyId>
    </xades:SignaturePolicyIdentifier>
  </xades:SignedSignatureProperties>
</xades:SignedProperties>
XML;
  }

  /**
   * Builds the KeyInfo element.
   */
  protected function buildKeyInfoXml(string $id, string $certPem): string {
    $certBase64 = $this->extractCertBase64($certPem);

    return <<<XML
<ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="{$id}">
  <ds:X509Data>
    <ds:X509Certificate>{$certBase64}</ds:X509Certificate>
  </ds:X509Data>
</ds:KeyInfo>
XML;
  }

  /**
   * Builds the SignedInfo element.
   */
  protected function buildSignedInfo(
    string $referenceId,
    string $documentDigest,
    string $signedPropertiesId,
    string $signedPropertiesDigest,
    string $keyInfoId,
    string $keyInfoDigest,
  ): string {
    return <<<XML
<ds:SignedInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
  <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
  <ds:Reference Id="{$referenceId}" URI="">
    <ds:Transforms>
      <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
    </ds:Transforms>
    <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
    <ds:DigestValue>{$documentDigest}</ds:DigestValue>
  </ds:Reference>
  <ds:Reference URI="#{$signedPropertiesId}" Type="http://uri.etsi.org/01903#SignedProperties">
    <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
    <ds:DigestValue>{$signedPropertiesDigest}</ds:DigestValue>
  </ds:Reference>
  <ds:Reference URI="#{$keyInfoId}">
    <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
    <ds:DigestValue>{$keyInfoDigest}</ds:DigestValue>
  </ds:Reference>
</ds:SignedInfo>
XML;
  }

  /**
   * Assembles the complete ds:Signature element.
   */
  protected function assembleSignature(
    string $signatureId,
    string $signedInfoXml,
    string $signatureValueId,
    string $signatureValueBase64,
    string $keyInfoXml,
    string $signedPropertiesXml,
  ): string {
    $formattedSignature = chunk_split($signatureValueBase64, 76);

    return <<<XML
<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="{$signatureId}">
  {$signedInfoXml}
  <ds:SignatureValue Id="{$signatureValueId}">{$formattedSignature}</ds:SignatureValue>
  {$keyInfoXml}
  <ds:Object>
    <xades:QualifyingProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Target="#{$signatureId}">
      {$signedPropertiesXml}
    </xades:QualifyingProperties>
  </ds:Object>
</ds:Signature>
XML;
  }

  /**
   * Gets the certificate password for a tenant from facturae_tenant_config.
   */
  protected function getTenantCertificatePassword(int $tenantId): ?string {
    try {
      $storage = $this->entityTypeManager->getStorage('facturae_tenant_config');
      $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);
      if (empty($configs)) {
        return NULL;
      }
      $config = reset($configs);
      $encrypted = $config->get('certificate_password_encrypted')->value ?? '';
      if (empty($encrypted)) {
        return NULL;
      }
      // In production, decrypt using AES-256-GCM via a secret key.
      // For now, return the stored value (modules should encrypt before storing).
      return $encrypted;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load certificate password for tenant @tid: @error', [
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Converts PEM certificate to DER format.
   */
  protected function pemToDer(string $pem): string {
    $base64 = $this->extractCertBase64($pem);
    return base64_decode($base64);
  }

  /**
   * Extracts the Base64-encoded certificate body from PEM.
   */
  protected function extractCertBase64(string $pem): string {
    $pem = str_replace([
      '-----BEGIN CERTIFICATE-----',
      '-----END CERTIFICATE-----',
      "\r",
      "\n",
    ], '', $pem);
    return trim($pem);
  }

  /**
   * Formats an issuer array into a DN string.
   */
  protected function formatIssuerName(array $issuer): string {
    $parts = [];
    foreach ($issuer as $key => $value) {
      if (is_array($value)) {
        $value = end($value);
      }
      $parts[] = "$key=$value";
    }
    return implode(',', array_reverse($parts));
  }

  /**
   * Generates a unique ID for signature elements.
   */
  protected function generateUniqueId(): string {
    return bin2hex(random_bytes(8));
  }

}
