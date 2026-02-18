<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_multiregion\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_multiregion\Service\ViesValidatorService;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for ViesValidatorService.
 *
 * @coversDefaultClass \Drupal\jaraba_multiregion\Service\ViesValidatorService
 * @group jaraba_multiregion
 */
class ViesValidatorServiceTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\jaraba_multiregion\Service\ViesValidatorService
   */
  protected ViesValidatorService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock entity storage for vies_validation.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for TranslatableMarkup::__toString().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('vies_validation')
      ->willReturn($this->storage);

    $this->service = new ViesValidatorService(
      $this->entityTypeManager,
      $this->httpClient,
      $this->logger,
    );
  }

  /**
   * Helper: builds a VIES SOAP XML response body.
   *
   * @param bool $valid
   *   Whether the VAT is valid.
   * @param string $name
   *   Company name in the response.
   * @param string $address
   *   Company address in the response.
   *
   * @return string
   *   A valid VIES SOAP XML response string.
   */
  protected function buildViesSoapResponse(bool $valid, string $name = '', string $address = ''): string {
    $validStr = $valid ? 'true' : 'false';
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <checkVatResponse xmlns="urn:ec.europa.eu:taxud:vies:services:checkVat:types">
      <countryCode>ES</countryCode>
      <vatNumber>12345678A</vatNumber>
      <requestDate>2026-02-18</requestDate>
      <valid>{$validStr}</valid>
      <name>{$name}</name>
      <address>{$address}</address>
    </checkVatResponse>
  </soap:Body>
</soap:Envelope>
XML;
  }

  /**
   * Helper: creates a mock HTTP response with a given status code and body.
   *
   * @param int $statusCode
   *   The HTTP status code.
   * @param string $body
   *   The response body content.
   *
   * @return \Psr\Http\Message\ResponseInterface|\PHPUnit\Framework\MockObject\MockObject
   *   A mock response.
   */
  protected function createMockHttpResponse(int $statusCode, string $body): ResponseInterface {
    $stream = $this->createMock(StreamInterface::class);
    $stream->method('__toString')->willReturn($body);

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getStatusCode')->willReturn($statusCode);
    $response->method('getBody')->willReturn($stream);

    return $response;
  }

  /**
   * Helper: configures storage to accept entity creation and save.
   */
  protected function setupStorageForSave(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('save')->willReturn(1);
    $this->storage->method('create')->willReturn($entity);
  }

  /**
   * @covers ::validate
   */
  public function testValidateReturnsValidForKnownVat(): void {
    $soapResponse = $this->buildViesSoapResponse(TRUE, 'EMPRESA TEST SL', 'Calle Mayor 1, Madrid');
    $httpResponse = $this->createMockHttpResponse(200, $soapResponse);

    $this->httpClient
      ->expects($this->once())
      ->method('request')
      ->with('POST', $this->anything(), $this->anything())
      ->willReturn($httpResponse);

    $this->setupStorageForSave();

    $result = $this->service->validate('ES12345678A');

    $this->assertTrue($result['is_valid']);
    $this->assertSame('EMPRESA TEST SL', $result['company_name']);
    $this->assertSame('Calle Mayor 1, Madrid', $result['company_address']);
    $this->assertSame('ES', $result['country_code']);
    $this->assertSame('12345678A', $result['vat_body']);
    $this->assertNull($result['error']);
    $this->assertNotEmpty($result['request_id']);
    $this->assertNotEmpty($result['validated_at']);
  }

  /**
   * @covers ::validate
   */
  public function testValidateReturnsInvalidForUnknownVat(): void {
    $soapResponse = $this->buildViesSoapResponse(FALSE);
    $httpResponse = $this->createMockHttpResponse(200, $soapResponse);

    $this->httpClient
      ->method('request')
      ->willReturn($httpResponse);

    $this->setupStorageForSave();

    $result = $this->service->validate('ES00000000X');

    $this->assertFalse($result['is_valid']);
    $this->assertSame('', $result['company_name']);
    $this->assertNull($result['error']);
  }

  /**
   * @covers ::validate
   */
  public function testValidateReturnsFalseForTooShortVat(): void {
    $result = $this->service->validate('ES');

    $this->assertFalse($result['is_valid']);
    $this->assertSame('', $result['country_code']);
    $this->assertSame('', $result['vat_body']);
    $this->assertStringContainsString('demasiado corto', $result['error']);
  }

  /**
   * @covers ::validate
   */
  public function testValidateSanitizesInputWhitespaceAndDots(): void {
    $soapResponse = $this->buildViesSoapResponse(TRUE, 'CLEAN SL', '');
    $httpResponse = $this->createMockHttpResponse(200, $soapResponse);

    $this->httpClient
      ->method('request')
      ->willReturn($httpResponse);

    $this->setupStorageForSave();

    // Input with spaces, dots and dashes that should be stripped.
    $result = $this->service->validate(' es.123-456 78A ');

    $this->assertTrue($result['is_valid']);
    $this->assertSame('ES', $result['country_code']);
    $this->assertSame('12345678A', $result['vat_body']);
  }

  /**
   * @covers ::validate
   */
  public function testValidateHandlesHttpErrorStatus(): void {
    $httpResponse = $this->createMockHttpResponse(500, '');

    $this->httpClient
      ->method('request')
      ->willReturn($httpResponse);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('Respuesta HTTP'),
        $this->anything(),
      );

    $result = $this->service->validate('ES12345678A');

    $this->assertFalse($result['is_valid']);
    $this->assertStringContainsString('HTTP', $result['error']);
  }

  /**
   * @covers ::validate
   */
  public function testValidateHandlesConnectionException(): void {
    $this->httpClient
      ->method('request')
      ->willThrowException(new \RuntimeException('Connection timed out'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error validando VAT'),
        $this->anything(),
      );

    $result = $this->service->validate('DE123456789');

    $this->assertFalse($result['is_valid']);
    $this->assertSame('DE', $result['country_code']);
    $this->assertSame('123456789', $result['vat_body']);
    $this->assertStringContainsString('no disponible', $result['error']);
  }

  /**
   * @covers ::getLastValidation
   */
  public function testGetLastValidationReturnsEntityWhenExists(): void {
    $entity = $this->createMock(EntityInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([42]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('load')->with(42)->willReturn($entity);

    $result = $this->service->getLastValidation('ES12345678A');

    $this->assertSame($entity, $result);
  }

  /**
   * @covers ::getLastValidation
   */
  public function testGetLastValidationReturnsNullWhenEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->getLastValidation('UNKNOWN999');

    $this->assertNull($result);
  }

  /**
   * @covers ::isExpired
   */
  public function testIsExpiredReturnsTrueWhenNoValidationExists(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $this->assertTrue($this->service->isExpired('ES12345678A'));
  }

  /**
   * @covers ::isExpired
   */
  public function testIsExpiredReturnsFalseWhenValidationIsRecent(): void {
    $entity = $this->createMock(ContentEntityInterface::class);

    // Created 1 hour ago -- well within the default 24-hour window.
    $createdField = (object) ['value' => time() - 3600];
    $entity->method('get')
      ->with('created')
      ->willReturn($createdField);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([10]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('load')->with(10)->willReturn($entity);

    $this->assertFalse($this->service->isExpired('ES12345678A'));
  }

  /**
   * @covers ::isExpired
   */
  public function testIsExpiredReturnsTrueWhenValidationIsOld(): void {
    $entity = $this->createMock(ContentEntityInterface::class);

    // Created 48 hours ago -- beyond the default 24-hour window.
    $createdField = (object) ['value' => time() - (48 * 3600)];
    $entity->method('get')
      ->with('created')
      ->willReturn($createdField);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([11]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('load')->with(11)->willReturn($entity);

    $this->assertTrue($this->service->isExpired('ES12345678A'));
  }

  /**
   * @covers ::isExpired
   */
  public function testIsExpiredRespectsCustomCacheHours(): void {
    $entity = $this->createMock(ContentEntityInterface::class);

    // Created 2 hours ago. With a 1-hour cache window, this should be expired.
    $createdField = (object) ['value' => time() - (2 * 3600)];
    $entity->method('get')
      ->with('created')
      ->willReturn($createdField);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([12]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('load')->with(12)->willReturn($entity);

    // With cacheHours=1, a 2-hour-old validation is expired.
    $this->assertTrue($this->service->isExpired('ES12345678A', 1));
  }

}
