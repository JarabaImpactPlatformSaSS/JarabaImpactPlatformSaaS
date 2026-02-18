<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult;
use Drupal\jaraba_legal_intelligence\Service\LegalSearchService;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for LegalSearchService.
 *
 * @group jaraba_legal_intelligence
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\Service\LegalSearchService
 */
class LegalSearchServiceTest extends UnitTestCase {

  protected LegalSearchService $service;
  protected $aiProvider;
  protected TenantContextService $tenantContext;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerInterface $logger;
  protected JarabaLexFeatureGateService $featureGate;
  protected AccountProxyInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->aiProvider = $this->createMock(AiProviderPluginManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->featureGate = $this->createMock(JarabaLexFeatureGateService::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);

    $this->service = new LegalSearchService(
      $this->aiProvider,
      $this->tenantContext,
      $this->entityTypeManager,
      $this->httpClient,
      $this->configFactory,
      $this->logger,
      $this->featureGate,
      $this->currentUser
    );
  }

  /**
   * @covers ::search
   */
  public function testSearchWithEmptyQueryReturnsFalse(): void {
    // Mock config.
    $config = $this->createMock(\Drupal\Core\Config\ImmutableConfig::class);
    $this->configFactory->method('get')->willReturn($config);

    // Mock current user.
    $this->currentUser->method('id')->willReturn(1);

    // Use real Value Object instead of mock.
    $gateResult = FeatureGateResult::allowed('searches_per_month', 'starter');
    $this->featureGate->method('check')->willReturn($gateResult);

    $result = $this->service->search('');

    $this->assertFalse($result['success']);
    $this->assertEquals('Query is empty.', $result['error']);
  }

  /**
   * @covers ::search
   */
  public function testSearchReturnsExpectedStructure(): void {
    // Mock config.
    $config = $this->createMock(\Drupal\Core\Config\ImmutableConfig::class);
    $this->configFactory->method('get')->willReturn($config);

    // Mock current user.
    $this->currentUser->method('id')->willReturn(1);

    // Use real Value Object instead of mock.
    $gateResult = FeatureGateResult::denied('searches_per_month', 'free', 10, 10, 'Upgrade required.', 'starter');
    $this->featureGate->method('check')->willReturn($gateResult);

    $result = $this->service->search('test query');

    $this->assertIsArray($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertArrayHasKey('results', $result);
    $this->assertArrayHasKey('total', $result);
    $this->assertArrayHasKey('facets', $result);
  }

  /**
   * @covers ::search
   */
  public function testSearchValidatesQueryNotEmpty(): void {
    // Mock config.
    $config = $this->createMock(\Drupal\Core\Config\ImmutableConfig::class);
    $this->configFactory->method('get')->willReturn($config);

    // Mock current user.
    $this->currentUser->method('id')->willReturn(1);

    // Use real Value Object instead of mock.
    $gateResult = FeatureGateResult::allowed('searches_per_month', 'starter');
    $this->featureGate->method('check')->willReturn($gateResult);

    $result = $this->service->search('   ');
    $this->assertFalse($result['success']);
  }

  /**
   * @covers ::isExactReference
   * @dataProvider referencePatternProvider
   */
  public function testReferencePatternDetectionDgt(string $query, bool $expected): void {
    $method = new \ReflectionMethod(LegalSearchService::class, 'isExactReference');
    $method->setAccessible(TRUE);

    $this->assertSame($expected, $method->invoke($this->service, $query));
  }

  /**
   * Data provider for reference patterns.
   */
  public static function referencePatternProvider(): array {
    return [
      'DGT valid' => ['V0123-24', TRUE],
      'TS valid' => ['STS 123/2024', TRUE],
      'ECLI valid' => ['ECLI:EU:C:2013:164', TRUE],
      'General text' => ['responsabilidad civil coche', FALSE],
    ];
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
