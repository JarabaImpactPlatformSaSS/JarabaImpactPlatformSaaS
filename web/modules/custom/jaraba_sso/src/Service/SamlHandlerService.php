<?php

declare(strict_types=1);

namespace Drupal\jaraba_sso\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_sso\Entity\SsoConfigurationInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * SAML 2.0 Handler Service.
 *
 * Manages the full SAML 2.0 lifecycle: AuthnRequest generation,
 * IdP response validation, SP metadata generation, and logout.
 *
 * SECURITY:
 * - Validates IdP signatures using X.509 certificates.
 * - Enforces NotBefore / NotOnOrAfter assertion conditions.
 * - Deflates + Base64 encodes AuthnRequests per SAML HTTP-Redirect binding.
 *
 * FLOW:
 * 1. initiateLogin() -> Redirect user to IdP SSO URL with AuthnRequest.
 * 2. processResponse() -> Validate IdP POST to /sso/acs, extract attributes.
 * 3. generateMetadata() -> Provide SP metadata XML for IdP configuration.
 * 4. initiateLogout() -> Redirect user to IdP SLO URL with LogoutRequest.
 */
class SamlHandlerService {

  /**
   * Constructor with property promotion.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly ClientInterface $httpClient,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Builds a SAML AuthnRequest and returns the redirect URL to the IdP.
   *
   * Constructs a SAML 2.0 AuthnRequest XML document, deflates it,
   * Base64-encodes it, and appends it as a query parameter to the IdP SSO URL.
   *
   * @param \Drupal\jaraba_sso\Entity\SsoConfigurationInterface $config
   *   The SSO configuration for the provider.
   *
   * @return string
   *   The full redirect URL to the IdP.
   */
  public function initiateLogin(SsoConfigurationInterface $config): string {
    $requestId = '_' . bin2hex(random_bytes(16));
    $issueInstant = gmdate('Y-m-d\TH:i:s\Z');
    $acsUrl = $this->getAcsUrl();
    $entityId = $this->getSpEntityId($config);

    $authnRequest = <<<XML
<samlp:AuthnRequest
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="{$requestId}"
    Version="2.0"
    IssueInstant="{$issueInstant}"
    Destination="{$config->getSsoUrl()}"
    AssertionConsumerServiceURL="{$acsUrl}"
    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
    <saml:Issuer>{$entityId}</saml:Issuer>
    <samlp:NameIDPolicy
        Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress"
        AllowCreate="true"/>
</samlp:AuthnRequest>
XML;

    // Deflate + Base64 encode per HTTP-Redirect binding.
    $deflated = gzdeflate($authnRequest);
    $encoded = base64_encode($deflated);
    $urlEncoded = urlencode($encoded);

    $separator = str_contains($config->getSsoUrl(), '?') ? '&' : '?';

    $this->logger->info('SAML AuthnRequest initiated for provider @name (request_id: @id)', [
      '@name' => $config->getProviderName(),
      '@id' => $requestId,
    ]);

    return $config->getSsoUrl() . $separator . 'SAMLRequest=' . $urlEncoded;
  }

  /**
   * Validates the IdP SAML Response and extracts user attributes.
   *
   * @param string $samlResponse
   *   The Base64-encoded SAML Response from the IdP POST.
   * @param \Drupal\jaraba_sso\Entity\SsoConfigurationInterface $config
   *   The SSO configuration for signature validation.
   *
   * @return array
   *   Associative array of user attributes extracted from the assertion:
   *   - email: string
   *   - name_id: string
   *   - first_name: string
   *   - last_name: string
   *   - groups: string[]
   *   - raw_attributes: array
   *
   * @throws \RuntimeException
   *   If signature validation fails or the assertion is expired.
   */
  public function processResponse(string $samlResponse, SsoConfigurationInterface $config): array {
    $xml = base64_decode($samlResponse, TRUE);
    if ($xml === FALSE) {
      throw new \RuntimeException('Invalid Base64 in SAML Response.');
    }

    $doc = new \DOMDocument();
    $previousValue = libxml_use_internal_errors(TRUE);
    $loaded = $doc->loadXML($xml);
    libxml_use_internal_errors($previousValue);

    if (!$loaded) {
      throw new \RuntimeException('Failed to parse SAML Response XML.');
    }

    // Validate signature using IdP certificate.
    $this->validateSignature($doc, $config->getCertificate());

    // Validate time conditions.
    $this->validateConditions($doc);

    // Extract attributes from the assertion.
    $xpath = new \DOMXPath($doc);
    $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
    $xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');

    // Extract NameID.
    $nameIdNodes = $xpath->query('//saml:Subject/saml:NameID');
    $nameId = $nameIdNodes->length > 0 ? $nameIdNodes->item(0)->textContent : '';

    // Extract all attributes.
    $rawAttributes = [];
    $attributeNodes = $xpath->query('//saml:AttributeStatement/saml:Attribute');
    foreach ($attributeNodes as $attrNode) {
      $attrName = $attrNode->getAttribute('Name');
      $values = [];
      $valueNodes = $xpath->query('saml:AttributeValue', $attrNode);
      foreach ($valueNodes as $valueNode) {
        $values[] = $valueNode->textContent;
      }
      $rawAttributes[$attrName] = count($values) === 1 ? $values[0] : $values;
    }

    // Map common attributes.
    $mapping = $config->getAttributeMapping();
    $email = $this->resolveAttribute($rawAttributes, $mapping, 'email', $nameId);
    $firstName = $this->resolveAttribute($rawAttributes, $mapping, 'first_name', '');
    $lastName = $this->resolveAttribute($rawAttributes, $mapping, 'last_name', '');
    $groups = $this->resolveAttribute($rawAttributes, $mapping, 'groups', []);
    if (is_string($groups)) {
      $groups = [$groups];
    }

    $this->logger->info('SAML Response processed for @email via provider @name', [
      '@email' => $email,
      '@name' => $config->getProviderName(),
    ]);

    return [
      'email' => $email,
      'name_id' => $nameId,
      'first_name' => $firstName,
      'last_name' => $lastName,
      'groups' => $groups,
      'raw_attributes' => $rawAttributes,
    ];
  }

  /**
   * Generates SP metadata XML for IdP configuration.
   *
   * @param \Drupal\jaraba_sso\Entity\SsoConfigurationInterface $config
   *   The SSO configuration.
   *
   * @return string
   *   The SP metadata XML document.
   */
  public function generateMetadata(SsoConfigurationInterface $config): string {
    $entityId = $this->getSpEntityId($config);
    $acsUrl = $this->getAcsUrl();
    $slsUrl = $this->getSlsUrl();

    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<md:EntityDescriptor
    xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
    entityID="{$entityId}">
    <md:SPSSODescriptor
        AuthnRequestsSigned="false"
        WantAssertionsSigned="true"
        protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</md:NameIDFormat>
        <md:AssertionConsumerService
            Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
            Location="{$acsUrl}"
            index="1"
            isDefault="true"/>
        <md:SingleLogoutService
            Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
            Location="{$slsUrl}"/>
    </md:SPSSODescriptor>
</md:EntityDescriptor>
XML;
  }

  /**
   * Builds a SAML LogoutRequest and returns the redirect URL to the IdP.
   *
   * @param \Drupal\jaraba_sso\Entity\SsoConfigurationInterface $config
   *   The SSO configuration.
   *
   * @return string
   *   The redirect URL to the IdP SLO endpoint.
   */
  public function initiateLogout(SsoConfigurationInterface $config): string {
    $requestId = '_' . bin2hex(random_bytes(16));
    $issueInstant = gmdate('Y-m-d\TH:i:s\Z');
    $entityId = $this->getSpEntityId($config);

    $logoutRequest = <<<XML
<samlp:LogoutRequest
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="{$requestId}"
    Version="2.0"
    IssueInstant="{$issueInstant}"
    Destination="{$config->getSloUrl()}">
    <saml:Issuer>{$entityId}</saml:Issuer>
</samlp:LogoutRequest>
XML;

    $deflated = gzdeflate($logoutRequest);
    $encoded = base64_encode($deflated);
    $urlEncoded = urlencode($encoded);

    $separator = str_contains($config->getSloUrl(), '?') ? '&' : '?';

    $this->logger->info('SAML LogoutRequest initiated for provider @name', [
      '@name' => $config->getProviderName(),
    ]);

    return $config->getSloUrl() . $separator . 'SAMLRequest=' . $urlEncoded;
  }

  /**
   * Validates the XML signature using the IdP X.509 certificate.
   *
   * @param \DOMDocument $doc
   *   The SAML Response XML document.
   * @param string $certificate
   *   The PEM-encoded X.509 certificate.
   *
   * @throws \RuntimeException
   *   If the signature is invalid or missing.
   */
  protected function validateSignature(\DOMDocument $doc, string $certificate): void {
    if (empty($certificate)) {
      throw new \RuntimeException('No IdP certificate configured for signature validation.');
    }

    // Normalize certificate PEM.
    $cert = trim($certificate);
    if (!str_starts_with($cert, '-----BEGIN CERTIFICATE-----')) {
      $cert = "-----BEGIN CERTIFICATE-----\n" . $cert . "\n-----END CERTIFICATE-----";
    }

    $publicKey = openssl_pkey_get_public($cert);
    if ($publicKey === FALSE) {
      throw new \RuntimeException('Failed to parse IdP X.509 certificate.');
    }

    // Find Signature element.
    $xpath = new \DOMXPath($doc);
    $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    $signatureNodes = $xpath->query('//ds:Signature');

    if ($signatureNodes->length === 0) {
      throw new \RuntimeException('No XML Signature found in SAML Response.');
    }

    // Extract SignatureValue and SignedInfo for verification.
    $signatureValueNodes = $xpath->query('//ds:Signature/ds:SignatureValue');
    if ($signatureValueNodes->length === 0) {
      throw new \RuntimeException('No SignatureValue found in SAML Response.');
    }

    $signatureValue = base64_decode(
      preg_replace('/\s+/', '', $signatureValueNodes->item(0)->textContent)
    );

    // Canonicalize SignedInfo for verification.
    $signedInfoNodes = $xpath->query('//ds:Signature/ds:SignedInfo');
    if ($signedInfoNodes->length === 0) {
      throw new \RuntimeException('No SignedInfo found in SAML Response.');
    }

    $canonicalSignedInfo = $signedInfoNodes->item(0)->C14N(TRUE, FALSE);

    $verified = openssl_verify($canonicalSignedInfo, $signatureValue, $publicKey, OPENSSL_ALGO_SHA256);

    if ($verified !== 1) {
      // Fallback to SHA1 for legacy IdPs.
      $verified = openssl_verify($canonicalSignedInfo, $signatureValue, $publicKey, OPENSSL_ALGO_SHA1);
    }

    if ($verified !== 1) {
      throw new \RuntimeException('SAML Response signature validation failed.');
    }
  }

  /**
   * Validates time conditions (NotBefore, NotOnOrAfter) in the assertion.
   *
   * @param \DOMDocument $doc
   *   The SAML Response XML document.
   *
   * @throws \RuntimeException
   *   If the assertion has expired or is not yet valid.
   */
  protected function validateConditions(\DOMDocument $doc): void {
    $xpath = new \DOMXPath($doc);
    $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

    $conditionsNodes = $xpath->query('//saml:Conditions');
    if ($conditionsNodes->length === 0) {
      return;
    }

    $conditions = $conditionsNodes->item(0);
    $now = time();
    // Allow 5 minutes clock skew.
    $skew = 300;

    $notBefore = $conditions->getAttribute('NotBefore');
    if (!empty($notBefore)) {
      $notBeforeTime = strtotime($notBefore);
      if ($notBeforeTime !== FALSE && ($now + $skew) < $notBeforeTime) {
        throw new \RuntimeException('SAML assertion is not yet valid (NotBefore).');
      }
    }

    $notOnOrAfter = $conditions->getAttribute('NotOnOrAfter');
    if (!empty($notOnOrAfter)) {
      $notOnOrAfterTime = strtotime($notOnOrAfter);
      if ($notOnOrAfterTime !== FALSE && ($now - $skew) >= $notOnOrAfterTime) {
        throw new \RuntimeException('SAML assertion has expired (NotOnOrAfter).');
      }
    }
  }

  /**
   * Resolves an attribute value using the mapping configuration.
   *
   * @param array $rawAttributes
   *   The raw attributes from the assertion.
   * @param array $mapping
   *   The attribute mapping configuration.
   * @param string $field
   *   The target field name.
   * @param mixed $default
   *   The default value if not found.
   *
   * @return mixed
   *   The resolved attribute value.
   */
  protected function resolveAttribute(array $rawAttributes, array $mapping, string $field, mixed $default): mixed {
    // Check if mapping defines a source attribute for this field.
    $sourceAttr = $mapping[$field] ?? NULL;
    if ($sourceAttr !== NULL && isset($rawAttributes[$sourceAttr])) {
      return $rawAttributes[$sourceAttr];
    }

    // Common SAML attribute names fallback.
    $commonNames = [
      'email' => [
        'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
        'urn:oid:0.9.2342.19200300.100.1.3',
        'mail',
        'email',
      ],
      'first_name' => [
        'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname',
        'urn:oid:2.5.4.42',
        'givenName',
        'first_name',
      ],
      'last_name' => [
        'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname',
        'urn:oid:2.5.4.4',
        'sn',
        'last_name',
      ],
      'groups' => [
        'http://schemas.xmlsoap.org/claims/Group',
        'memberOf',
        'groups',
      ],
    ];

    if (isset($commonNames[$field])) {
      foreach ($commonNames[$field] as $attrName) {
        if (isset($rawAttributes[$attrName])) {
          return $rawAttributes[$attrName];
        }
      }
    }

    return $default;
  }

  /**
   * Gets the SP Entity ID for a given SSO configuration.
   */
  protected function getSpEntityId(SsoConfigurationInterface $config): string {
    $baseUrl = $this->configFactory->get('jaraba_sso.settings')->get('sp_entity_id');
    if (!empty($baseUrl)) {
      return $baseUrl;
    }

    // Fallback: use the site base URL + module path.
    $request = \Drupal::request();
    return $request->getSchemeAndHttpHost() . '/sso/metadata/' . $config->id();
  }

  /**
   * Gets the Assertion Consumer Service URL.
   */
  protected function getAcsUrl(): string {
    $request = \Drupal::request();
    return $request->getSchemeAndHttpHost() . '/sso/acs';
  }

  /**
   * Gets the Single Logout Service URL.
   */
  protected function getSlsUrl(): string {
    $request = \Drupal::request();
    return $request->getSchemeAndHttpHost() . '/sso/sls';
  }

}
