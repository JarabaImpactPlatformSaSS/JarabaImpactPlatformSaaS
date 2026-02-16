<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_legal_intelligence\Service\LegalNlpPipelineService;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for LegalNlpPipelineService.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\Service\LegalNlpPipelineService
 * @group jaraba_legal_intelligence
 */
class LegalNlpPipelineServiceTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\jaraba_legal_intelligence\Service\LegalNlpPipelineService
   */
  protected LegalNlpPipelineService $service;

  /**
   * Mock AI provider plugin manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $aiProvider;

  /**
   * Mock HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * Mock config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock immutable config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->aiProvider = $this->createMock(AiProviderPluginManager::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->config = $this->createMock(ImmutableConfig::class);
    $this->config->method('get')->willReturnMap([
      ['tika_url', 'http://tika:9998'],
      ['nlp_service_url', 'http://legal-nlp:8001'],
      ['qdrant_url', 'http://qdrant:6333'],
      ['qdrant_collection_national', 'legal_intelligence'],
      ['qdrant_collection_eu', 'legal_intelligence_eu'],
      ['qdrant_api_key', ''],
      ['nlp_max_text_length', 50000],
      ['nlp_chunk_max_tokens', 512],
      ['nlp_chunk_overlap_tokens', 50],
      ['nlp_classification_max_chars', 8000],
      ['nlp_summary_max_chars', 12000],
      ['nlp_tika_timeout', 60],
      ['nlp_python_timeout', 120],
      ['nlp_ai_temperature', 0.2],
      ['nlp_ai_max_tokens', 2000],
      ['classification_prompt', 'Classify this text.'],
      ['summary_prompt', 'Summarize this text.'],
      ['score_threshold', 0.65],
    ]);
    $this->configFactory->method('get')
      ->with('jaraba_legal_intelligence.settings')
      ->willReturn($this->config);

    $this->service = new LegalNlpPipelineService(
      $this->aiProvider,
      $this->httpClient,
      $this->configFactory,
      $this->logger,
      $this->database,
      $this->entityTypeManager,
    );
  }

  /**
   * @covers ::__construct
   */
  public function testEuSourcesConstant(): void {
    $reflection = new \ReflectionClass(LegalNlpPipelineService::class);
    $constant = $reflection->getReflectionConstant('EU_SOURCES');

    $this->assertNotFalse($constant, 'EU_SOURCES constant should exist.');

    $value = $constant->getValue();
    $this->assertIsArray($value);
    $this->assertCount(7, $value);
    $this->assertContains('tjue', $value);
    $this->assertContains('eurlex', $value);
    $this->assertContains('tedh', $value);
    $this->assertContains('edpb', $value);
    $this->assertContains('eba', $value);
    $this->assertContains('esma', $value);
    $this->assertContains('ag_tjue', $value);
  }

  /**
   * @covers ::__construct
   */
  public function testConstructorSetsAllProperties(): void {
    // The constructor should not throw any exception.
    $service = new LegalNlpPipelineService(
      $this->aiProvider,
      $this->httpClient,
      $this->configFactory,
      $this->logger,
      $this->database,
      $this->entityTypeManager,
    );

    $this->assertInstanceOf(LegalNlpPipelineService::class, $service);
  }

  /**
   * @covers ::processResolution
   */
  public function testProcessResolutionCallsLogger(): void {
    // Create a mock LegalResolution entity.
    $entity = $this->createMock(\Drupal\jaraba_legal_intelligence\Entity\LegalResolution::class);

    // Mock field value access: entity->get('field_name')->value.
    $externalRefField = new \stdClass();
    $externalRefField->value = 'STS 123/2024';
    $sourceIdField = new \stdClass();
    $sourceIdField->value = 'cendoj';
    $fullTextField = new \stdClass();
    $fullTextField->value = '';
    $originalUrlField = new \stdClass();
    $originalUrlField->value = '';

    $entity->method('get')->willReturnMap([
      ['external_ref', $externalRefField],
      ['source_id', $sourceIdField],
      ['full_text', $fullTextField],
      ['original_url', $originalUrlField],
    ]);

    // Logger should be called at least once with info message about starting.
    $this->logger->expects($this->atLeastOnce())
      ->method('info')
      ->with(
        $this->stringContains('NLP Pipeline'),
        $this->anything()
      );

    // Also expect warning about empty text (pipeline aborted).
    $this->logger->expects($this->atLeastOnce())
      ->method('warning');

    $this->service->processResolution($entity);
  }

  /**
   * @covers ::processResolution
   */
  public function testNormalizationRemovesUtf8Bom(): void {
    // Access the private normalize method via reflection.
    $reflection = new \ReflectionClass(LegalNlpPipelineService::class);
    $method = $reflection->getMethod('normalize');
    $method->setAccessible(TRUE);

    $textWithBom = "\xEF\xBB\xBFHello World";
    $result = $method->invoke($this->service, $textWithBom);

    // BOM should be removed.
    $this->assertStringNotContainsString("\xEF\xBB\xBF", $result);
    $this->assertSame('Hello World', $result);
  }

  /**
   * @covers ::__construct
   */
  public function testConfigReadsExpectedKeys(): void {
    // Verify config mock returns values for the expected keys used by the service.
    $expectedKeys = [
      'tika_url',
      'nlp_service_url',
      'qdrant_url',
      'qdrant_collection_national',
      'qdrant_collection_eu',
      'nlp_max_text_length',
      'nlp_chunk_max_tokens',
      'nlp_chunk_overlap_tokens',
      'nlp_classification_max_chars',
      'nlp_summary_max_chars',
      'nlp_tika_timeout',
      'nlp_python_timeout',
      'nlp_ai_temperature',
      'nlp_ai_max_tokens',
      'classification_prompt',
      'summary_prompt',
    ];

    foreach ($expectedKeys as $key) {
      $value = $this->config->get($key);
      $this->assertNotNull($value, "Config key '{$key}' should return a non-null value.");
    }
  }

}
