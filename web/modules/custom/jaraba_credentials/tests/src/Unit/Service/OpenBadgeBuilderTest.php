<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_credentials\Unit\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\jaraba_credentials\Service\OpenBadgeBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests para OpenBadgeBuilder.
 *
 * Verifica la construccion de credenciales OB3 JSON-LD conformes al
 * estandar Open Badges 3.0.
 *
 * @group jaraba_credentials
 * @coversDefaultClass \Drupal\jaraba_credentials\Service\OpenBadgeBuilder
 */
class OpenBadgeBuilderTest extends TestCase {

  /**
   * El servicio bajo test.
   */
  protected OpenBadgeBuilder $builder;

  /**
   * Mock del request stack.
   */
  protected RequestStack $requestStack;

  /**
   * Mock del servicio de tiempo.
   */
  protected TimeInterface $time;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $request = $this->createMock(Request::class);
    $request->method('getSchemeAndHttpHost')->willReturn('https://test.jaraba.es');

    $this->requestStack = $this->createMock(RequestStack::class);
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    $this->time = $this->createMock(TimeInterface::class);
    $this->time->method('getRequestTime')->willReturn(1700000000);

    $this->builder = new OpenBadgeBuilder($this->requestStack, $this->time);
  }

  /**
   * Retorna parametros de credencial de ejemplo.
   */
  protected function getSampleParams(): array {
    return [
      'uuid' => '550e8400-e29b-41d4-a716-446655440000',
      'issuer' => [
        'name' => 'Jaraba Impact',
        'url' => 'https://test.jaraba.es',
        'email' => 'creds@jaraba.es',
        'image' => 'https://test.jaraba.es/logo.png',
      ],
      'template' => [
        'name' => 'Certificado PHP Avanzado',
        'machine_name' => 'php_avanzado',
        'description' => 'Certificacion en PHP avanzado',
        'criteria' => 'Completar todos los modulos del curso',
        'image' => 'https://test.jaraba.es/badge.png',
        'credential_type' => 'path_certificate',
      ],
      'recipient' => [
        'name' => 'Ana Garcia',
        'email' => 'ana@example.com',
      ],
      'issued_on' => 1700000000,
    ];
  }

  /**
   * Tests que la credencial tiene los contextos JSON-LD correctos.
   *
   * @covers ::buildCredential
   */
  public function testCredentialHasCorrectContexts(): void {
    $credential = $this->builder->buildCredential($this->getSampleParams());

    $this->assertArrayHasKey('@context', $credential);
    $this->assertContains('https://www.w3.org/2018/credentials/v1', $credential['@context']);
    $this->assertContains('https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.2.json', $credential['@context']);
  }

  /**
   * Tests que la credencial tiene los tipos OB3 correctos.
   *
   * @covers ::buildCredential
   */
  public function testCredentialHasCorrectTypes(): void {
    $credential = $this->builder->buildCredential($this->getSampleParams());

    $this->assertArrayHasKey('type', $credential);
    $this->assertContains('VerifiableCredential', $credential['type']);
    $this->assertContains('OpenBadgeCredential', $credential['type']);
  }

  /**
   * Tests que el ID de la credencial usa el UUID.
   *
   * @covers ::buildCredential
   */
  public function testCredentialIdContainsUuid(): void {
    $params = $this->getSampleParams();
    $credential = $this->builder->buildCredential($params);

    $this->assertStringContainsString($params['uuid'], $credential['id']);
    $this->assertStringContainsString('https://test.jaraba.es/verify/', $credential['id']);
  }

  /**
   * Tests la estructura del issuer OB3.
   *
   * @covers ::buildCredential
   */
  public function testIssuerStructure(): void {
    $credential = $this->builder->buildCredential($this->getSampleParams());
    $issuer = $credential['issuer'];

    $this->assertSame('Profile', $issuer['type']);
    $this->assertSame('Jaraba Impact', $issuer['name']);
    $this->assertSame('creds@jaraba.es', $issuer['email']);
    $this->assertSame('https://test.jaraba.es', $issuer['url']);
  }

  /**
   * Tests la estructura del credentialSubject.
   *
   * @covers ::buildCredential
   */
  public function testCredentialSubjectStructure(): void {
    $credential = $this->builder->buildCredential($this->getSampleParams());
    $subject = $credential['credentialSubject'];

    $this->assertSame('AchievementSubject', $subject['type']);
    $this->assertSame('Ana Garcia', $subject['name']);
    $this->assertStringStartsWith('did:email:', $subject['id']);
    $this->assertStringContainsString('ana@example.com', $subject['id']);
  }

  /**
   * Tests la estructura del achievement.
   *
   * @covers ::buildCredential
   */
  public function testAchievementStructure(): void {
    $credential = $this->builder->buildCredential($this->getSampleParams());
    $achievement = $credential['credentialSubject']['achievement'];

    $this->assertSame('Achievement', $achievement['type']);
    $this->assertSame('Certificado PHP Avanzado', $achievement['name']);
    $this->assertSame('Certificacion en PHP avanzado', $achievement['description']);
    $this->assertStringContainsString('php_avanzado', $achievement['id']);
    $this->assertSame('Certificate', $achievement['achievementType']);
  }

  /**
   * Tests el formato de fecha ISO 8601.
   *
   * @covers ::buildCredential
   */
  public function testIssuanceDateFormat(): void {
    $credential = $this->builder->buildCredential($this->getSampleParams());

    $this->assertArrayHasKey('issuanceDate', $credential);
    $this->assertMatchesRegularExpression(
      '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',
      $credential['issuanceDate']
    );
  }

  /**
   * Tests que expirationDate no aparece si no se proporciona.
   *
   * @covers ::buildCredential
   */
  public function testNoExpirationDateByDefault(): void {
    $credential = $this->builder->buildCredential($this->getSampleParams());
    $this->assertArrayNotHasKey('expirationDate', $credential);
  }

  /**
   * Tests que expirationDate aparece cuando se proporciona.
   *
   * @covers ::buildCredential
   */
  public function testExpirationDateIncluded(): void {
    $params = $this->getSampleParams();
    $params['expires_on'] = 1731536000;
    $credential = $this->builder->buildCredential($params);

    $this->assertArrayHasKey('expirationDate', $credential);
    $this->assertMatchesRegularExpression(
      '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',
      $credential['expirationDate']
    );
  }

  /**
   * Tests que evidence se incluye cuando se proporciona.
   *
   * @covers ::buildCredential
   */
  public function testEvidenceIncluded(): void {
    $params = $this->getSampleParams();
    $params['evidence'] = [
      [
        'url' => 'https://test.jaraba.es/evidence/1',
        'name' => 'Proyecto Final',
        'description' => 'Entrega del proyecto',
        'genre' => 'Assignment',
      ],
    ];
    $credential = $this->builder->buildCredential($params);

    $this->assertArrayHasKey('evidence', $credential);
    $this->assertCount(1, $credential['evidence']);
    $this->assertSame('Evidence', $credential['evidence'][0]['type']);
    $this->assertSame('Proyecto Final', $credential['evidence'][0]['name']);
  }

  /**
   * Tests la estructura del proof Ed25519.
   *
   * @covers ::addProof
   */
  public function testAddProofStructure(): void {
    $credential = $this->builder->buildCredential($this->getSampleParams());
    $signed = $this->builder->addProof($credential, 'base64signature==', 'https://test.jaraba.es/keys/1');

    $this->assertArrayHasKey('proof', $signed);
    $this->assertSame('Ed25519Signature2020', $signed['proof']['type']);
    $this->assertSame('assertionMethod', $signed['proof']['proofPurpose']);
    $this->assertSame('base64signature==', $signed['proof']['proofValue']);
    $this->assertSame('https://test.jaraba.es/keys/1', $signed['proof']['verificationMethod']);
    $this->assertArrayHasKey('created', $signed['proof']);
  }

  /**
   * Tests que serializeForSigning elimina el proof.
   *
   * @covers ::serializeForSigning
   */
  public function testSerializeForSigningRemovesProof(): void {
    $credential = $this->builder->buildCredential($this->getSampleParams());
    $signed = $this->builder->addProof($credential, 'sig', 'key');

    $serialized = $this->builder->serializeForSigning($signed);

    $this->assertStringNotContainsString('proof', $serialized);
    $this->assertStringNotContainsString('proofValue', $serialized);
  }

  /**
   * Tests que serializeForSigning produce JSON determinista.
   *
   * @covers ::serializeForSigning
   */
  public function testSerializeForSigningIsDeterministic(): void {
    $credential = $this->builder->buildCredential($this->getSampleParams());

    $serialized1 = $this->builder->serializeForSigning($credential);
    $serialized2 = $this->builder->serializeForSigning($credential);

    $this->assertSame($serialized1, $serialized2);
  }

  /**
   * Tests mapeo de tipos de credencial a achievementType OB3.
   *
   * @covers ::buildCredential
   * @dataProvider credentialTypeProvider
   */
  public function testCredentialTypeMapping(string $inputType, string $expectedOb3Type): void {
    $params = $this->getSampleParams();
    $params['template']['credential_type'] = $inputType;
    $credential = $this->builder->buildCredential($params);

    $this->assertSame(
      $expectedOb3Type,
      $credential['credentialSubject']['achievement']['achievementType']
    );
  }

  /**
   * Data provider para tipos de credencial.
   */
  public static function credentialTypeProvider(): array {
    return [
      'course_badge' => ['course_badge', 'CourseBadge'],
      'path_certificate' => ['path_certificate', 'Certificate'],
      'skill_endorsement' => ['skill_endorsement', 'Endorsement'],
      'achievement' => ['achievement', 'Achievement'],
      'diploma' => ['diploma', 'Diploma'],
      'unknown_defaults_to_badge' => ['unknown_type', 'Badge'],
    ];
  }

  /**
   * Tests URL base fallback cuando no hay request.
   *
   * @covers ::buildCredential
   */
  public function testBaseUrlFallback(): void {
    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')->willReturn(NULL);

    $builder = new OpenBadgeBuilder($requestStack, $this->time);
    $credential = $builder->buildCredential($this->getSampleParams());

    $this->assertStringContainsString('jaraba.es', $credential['id']);
  }

}
