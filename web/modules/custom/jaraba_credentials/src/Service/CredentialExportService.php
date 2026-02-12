<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_credentials\Entity\IssuedCredential;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio de exportacion de credenciales en formatos portables.
 *
 * Soporta: Open Badge 3.0 JSON-LD, LinkedIn Certificate URL,
 * Europass Digital Credential XML.
 */
class CredentialExportService {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;
  protected RequestStack $requestStack;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
    RequestStack $requestStack,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerFactory->get('jaraba_credentials');
    $this->requestStack = $requestStack;
  }

  /**
   * Carga credencial por UUID validando que pertenece al usuario.
   *
   * @param string $uuid
   *   UUID de la credencial.
   * @param int|null $uid
   *   ID del usuario propietario (NULL = sin validacion).
   *
   * @return \Drupal\jaraba_credentials\Entity\IssuedCredential|null
   *   La credencial o NULL.
   */
  protected function loadCredential(string $uuid, ?int $uid = NULL): ?IssuedCredential {
    $credentials = $this->entityTypeManager->getStorage('issued_credential')
      ->loadByProperties(['uuid' => $uuid]);

    if (empty($credentials)) {
      return NULL;
    }

    $credential = reset($credentials);

    if ($uid !== NULL && (int) ($credential->get('recipient_id')->target_id ?? 0) !== $uid) {
      return NULL;
    }

    return $credential;
  }

  /**
   * Exporta credencial como Open Badge 3.0 JSON-LD.
   *
   * @param string $uuid
   *   UUID de la credencial.
   *
   * @return array|null
   *   Estructura OB3 JSON-LD completa o NULL.
   */
  public function exportOpenBadge(string $uuid): ?array {
    $credential = $this->loadCredential($uuid);
    if (!$credential) {
      return NULL;
    }

    $ob3Json = $credential->get('ob3_json')->value ?? '';
    if (empty($ob3Json)) {
      return NULL;
    }

    $ob3Data = json_decode($ob3Json, TRUE);
    if (!$ob3Data) {
      return NULL;
    }

    return $ob3Data;
  }

  /**
   * Genera URL para agregar credencial al perfil de LinkedIn.
   *
   * @param string $uuid
   *   UUID de la credencial.
   *
   * @return array|null
   *   Array con 'url' de LinkedIn y metadatos, o NULL.
   *
   * @see https://learn.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/add-to-profile
   */
  public function getLinkedInCertificateUrl(string $uuid): ?array {
    $credential = $this->loadCredential($uuid);
    if (!$credential) {
      return NULL;
    }

    $template = $credential->getTemplate();
    if (!$template) {
      return NULL;
    }

    $baseUrl = $this->getBaseUrl();
    $verificationUrl = "{$baseUrl}/verify/{$uuid}";

    // Datos del certificado.
    $name = $template->get('name')->value ?? '';
    $issuedOn = $credential->get('issued_on')->value ?? '';
    $expiresOn = $credential->get('expires_on')->value ?? NULL;

    // Datos del emisor.
    $issuer = $template->getIssuer();
    $organizationName = $issuer ? ($issuer->get('name')->value ?? 'Jaraba Impact Platform') : 'Jaraba Impact Platform';

    // Parsear fechas para LinkedIn.
    $issueDate = $issuedOn ? strtotime($issuedOn) : NULL;
    $expirationDate = $expiresOn ? strtotime($expiresOn) : NULL;

    // LinkedIn Add to Profile URL params.
    $params = [
      'pfCertificationName' => $name,
      'pfCertificationUrl' => $verificationUrl,
      'pfLicenseNo' => $credential->uuid(),
      'pfCertStartDate' => $issueDate ? date('Ym01', $issueDate) : '',
    ];

    if ($expirationDate) {
      $params['pfCertEndDate'] = date('Ym01', $expirationDate);
    }

    // LinkedIn Add to Profile URL.
    $linkedInUrl = 'https://www.linkedin.com/profile/add?' . http_build_query(array_filter($params));

    return [
      'url' => $linkedInUrl,
      'certificate_name' => $name,
      'organization_name' => $organizationName,
      'license_number' => $credential->uuid(),
      'verification_url' => $verificationUrl,
      'issued_date' => $issueDate ? date('Y-m-d', $issueDate) : NULL,
      'expiration_date' => $expirationDate ? date('Y-m-d', $expirationDate) : NULL,
    ];
  }

  /**
   * Exporta credencial como Europass Digital Credential XML.
   *
   * Genera XML conforme al estandar European Digital Credentials
   * for Learning (EDCI).
   *
   * @param string $uuid
   *   UUID de la credencial.
   *
   * @return string|null
   *   XML string o NULL.
   *
   * @see https://europa.eu/europass/digital-credentials
   */
  public function exportEuropass(string $uuid): ?string {
    $credential = $this->loadCredential($uuid);
    if (!$credential) {
      return NULL;
    }

    $template = $credential->getTemplate();
    if (!$template) {
      return NULL;
    }

    $issuer = $template->getIssuer();
    $issuedOn = $credential->get('issued_on')->value ?? '';
    $expiresOn = $credential->get('expires_on')->value ?? NULL;
    $recipientName = $credential->get('recipient_name')->value ?? '';
    $recipientEmail = $credential->get('recipient_email')->value ?? '';
    $credentialName = $template->get('name')->value ?? '';
    $credentialDesc = $template->get('description')->value ?? '';
    $credentialType = $template->get('credential_type')->value ?? 'achievement';
    $level = $template->get('level')->value ?? '';
    $verificationUrl = "{$this->getBaseUrl()}/verify/{$uuid}";

    // Mapeo de nivel a EQF.
    $eqfMap = [
      'beginner' => '3',
      'intermediate' => '4',
      'advanced' => '5',
      'expert' => '6',
    ];
    $eqfLevel = $eqfMap[$level] ?? '';

    // Issuer data.
    $issuerName = $issuer ? ($issuer->get('name')->value ?? '') : 'Jaraba Impact Platform';
    $issuerUrl = $issuer ? ($issuer->get('url')->value ?? '') : $this->getBaseUrl();

    // Build XML.
    $xml = new \DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = TRUE;

    // Root element: europassCredential.
    $root = $xml->createElementNS(
      'http://data.europa.eu/snb',
      'europassCredential'
    );
    $root->setAttribute('xsdVersion', '0.10.0');
    $xml->appendChild($root);

    // Credential ID.
    $idEl = $xml->createElement('id', 'urn:credential:' . $credential->uuid());
    $root->appendChild($idEl);

    // Title.
    $titleEl = $xml->createElement('title');
    $titleText = $xml->createElement('text', htmlspecialchars($credentialName));
    $titleText->setAttribute('lang', 'es');
    $titleEl->appendChild($titleText);
    $root->appendChild($titleEl);

    // Description.
    if (!empty($credentialDesc)) {
      $descEl = $xml->createElement('description');
      $descText = $xml->createElement('text', htmlspecialchars(strip_tags($credentialDesc)));
      $descText->setAttribute('lang', 'es');
      $descEl->appendChild($descText);
      $root->appendChild($descEl);
    }

    // Issuance date.
    if ($issuedOn) {
      $issuanceDateEl = $xml->createElement('issuanceDate', date('Y-m-d\TH:i:sP', strtotime($issuedOn)));
      $root->appendChild($issuanceDateEl);
    }

    // Expiration date.
    if ($expiresOn) {
      $expirationDateEl = $xml->createElement('expirationDate', date('Y-m-d\TH:i:sP', strtotime($expiresOn)));
      $root->appendChild($expirationDateEl);
    }

    // Issuing authority.
    $issuerEl = $xml->createElement('issuingAuthority');
    $issuerIdEl = $xml->createElement('id', 'urn:epass:org:' . md5($issuerName));
    $issuerEl->appendChild($issuerIdEl);

    $prefLabelEl = $xml->createElement('prefLabel');
    $prefLabelText = $xml->createElement('text', htmlspecialchars($issuerName));
    $prefLabelText->setAttribute('lang', 'es');
    $prefLabelEl->appendChild($prefLabelText);
    $issuerEl->appendChild($prefLabelEl);

    if ($issuerUrl) {
      $homepageEl = $xml->createElement('homepage', htmlspecialchars($issuerUrl));
      $issuerEl->appendChild($homepageEl);
    }

    $root->appendChild($issuerEl);

    // Credential subject (recipient).
    $subjectEl = $xml->createElement('credentialSubject');
    $subjectIdEl = $xml->createElement('id', 'urn:epass:person:' . md5($recipientEmail ?: $recipientName));
    $subjectEl->appendChild($subjectIdEl);

    if ($recipientName) {
      $fullNameEl = $xml->createElement('fullName');
      $fullNameText = $xml->createElement('text', htmlspecialchars($recipientName));
      $fullNameText->setAttribute('lang', 'es');
      $fullNameEl->appendChild($fullNameText);
      $subjectEl->appendChild($fullNameEl);
    }

    $root->appendChild($subjectEl);

    // Learning achievement.
    $achievementEl = $xml->createElement('learningAchievement');
    $achievementIdEl = $xml->createElement('id', 'urn:epass:learningAchievement:' . $credential->uuid());
    $achievementEl->appendChild($achievementIdEl);

    $achTitleEl = $xml->createElement('title');
    $achTitleText = $xml->createElement('text', htmlspecialchars($credentialName));
    $achTitleText->setAttribute('lang', 'es');
    $achTitleEl->appendChild($achTitleText);
    $achievementEl->appendChild($achTitleEl);

    // EQF level.
    if ($eqfLevel) {
      $eqfEl = $xml->createElement('eqfLevel', $eqfLevel);
      $achievementEl->appendChild($eqfEl);
    }

    // Credential type mapping.
    $typeMap = [
      'course_badge' => 'http://data.europa.eu/snb/credential/e34929035b',
      'path_certificate' => 'http://data.europa.eu/snb/credential/6f22e07add',
      'skill_endorsement' => 'http://data.europa.eu/snb/credential/e34929035b',
      'achievement' => 'http://data.europa.eu/snb/credential/e34929035b',
      'diploma' => 'http://data.europa.eu/snb/credential/25831c2',
    ];
    $europassType = $typeMap[$credentialType] ?? $typeMap['achievement'];
    $typeEl = $xml->createElement('type', $europassType);
    $achievementEl->appendChild($typeEl);

    $root->appendChild($achievementEl);

    // Verification URL as supplementary document.
    $suppDocEl = $xml->createElement('supplementaryDocument');
    $suppDocIdEl = $xml->createElement('id', $verificationUrl);
    $suppDocEl->appendChild($suppDocIdEl);
    $suppDocTitleEl = $xml->createElement('title');
    $suppDocTitleText = $xml->createElement('text', 'Verificacion en linea');
    $suppDocTitleText->setAttribute('lang', 'es');
    $suppDocTitleEl->appendChild($suppDocTitleText);
    $suppDocEl->appendChild($suppDocTitleEl);
    $root->appendChild($suppDocEl);

    return $xml->saveXML();
  }

  /**
   * Obtiene la URL base del sitio.
   */
  protected function getBaseUrl(): string {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      return $request->getSchemeAndHttpHost();
    }
    return 'https://jaraba.es';
  }

}
