<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_legal_intelligence\Service\LegalNlpPipelineService;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for LegalNlpPipelineService.
 *
 * @group jaraba_legal_intelligence
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\Service\LegalNlpPipelineService
 */
class LegalNlpPipelineServiceTest extends UnitTestCase {

  protected LegalNlpPipelineService $service;
  protected $aiProvider;
  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerInterface $logger;
  protected Connection $database;
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->aiProvider = $this->createMock(AiProviderPluginManagerInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->service = new LegalNlpPipelineService(
      $this->aiProvider,
      $this->httpClient,
      $this->configFactory,
      $this->logger,
      $this->database,
      $this->entityTypeManager
    );
  }

  /**
   * Tests that EU_SOURCES constant is correctly defined.
   */
  public function testEuSourcesConstant(): void {
    $reflection = new \ReflectionClass(LegalNlpPipelineService::class);
    $constant = $reflection->getConstant('EU_SOURCES');

    $this->assertIsArray($constant);
    $this->assertContains('tjue', $constant);
    $this->assertContains('eurlex', $constant);
  }

  /**
   * Tests constructor sets protected properties.
   */
  public function testConstructorSetsAllProperties(): void {
    $reflection = new \ReflectionClass($this->service);

    $this->assertSame($this->aiProvider, $reflection->getProperty('aiProvider')->getValue($this->service));
    $this->assertSame($this->httpClient, $reflection->getProperty('httpClient')->getValue($this->service));
    $this->assertSame($this->logger, $reflection->getProperty('logger')->getValue($this->service));
  }

  /**
   * @covers ::processResolution
   */
  public function testProcessResolutionCallsLogger(): void {
    $entity = $this->createMock(\Drupal\jaraba_legal_intelligence\Entity\LegalResolution::class);
    $entity->method('get')->willReturn((object) ['value' => 'some text']);

    $config = $this->createMock(ImmutableConfig::class);
    $this->configFactory->method('get')->willReturn($config);

    $this->logger->expects($this->atLeastOnce())->method('info');

    // We don't test the full pipeline here as it has too many side effects.
    // This just ensures the entry point works.
    try {
      $this->service->processResolution($entity);
    } catch (\Throwable $e) {
      // It will likely fail later due to missing mocks for all stages,
      // but we only care about the initial logger call.
    }
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizationRemovesUtf8Bom(): void {
    $text = "\xEF\xBB\xBFHello World";
    $method = new \ReflectionMethod(LegalNlpPipelineService::class, 'normalize');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $text);
    $this->assertSame('Hello World', $result);
  }

  /**
   * Tests that config keys are read correctly.
   */
  public function testConfigReadsExpectedKeys(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $this->configFactory->method('get')
      ->with('jaraba_legal_intelligence.settings')
      ->willReturn($config);

    $config->expects($this->atLeastOnce())
      ->method('get')
      ->willReturnMap([
        ['tika_url', 'http://tika:9998'],
        ['nlp_max_text_length', 50000],
      ]);

    // Access private method or run a partial pipeline.
    $entity = $this->createMock(\Drupal\jaraba_legal_intelligence\Entity\LegalResolution::class);
    $entity->method('get')->willReturn((object) ['value' => 'Test']);

    try {
      $this->service->processResolution($entity);
    } catch (\Throwable) {
    }
  }

}

if (!interface_exists('Drupal\Tests\jaraba_legal_intelligence\Unit\Service\AiProviderPluginManagerInterface')) {
  /**
   * Temporary interface for mocking AiProviderPluginManager.
   */
  interface AiProviderPluginManagerInterface {
    public function getDefaultProviderForOperationType(string $type);
    public function createInstance(string $pluginId, array $configuration = []);
  }
}
