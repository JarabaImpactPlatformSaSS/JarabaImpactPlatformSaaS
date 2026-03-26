<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_crm\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_crm\Service\ContactService;
use Drupal\jaraba_crm\Service\CrmCopilotBridgeService;
use Drupal\jaraba_crm\Service\CrmForecastingService;
use Drupal\jaraba_crm\Service\OpportunityService;
use Drupal\jaraba_crm\Service\SalesPlaybookService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\jaraba_crm\Service\CrmCopilotBridgeService
 * @group jaraba_crm
 */
class CrmCopilotBridgeServiceTest extends UnitTestCase {

  protected CrmCopilotBridgeService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected OpportunityService $opportunityService;
  protected ContactService $contactService;
  protected CrmForecastingService $forecastingService;
  protected SalesPlaybookService $salesPlaybook;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $this->opportunityService = $this->createMock(OpportunityService::class);
    $this->contactService = $this->createMock(ContactService::class);
    $this->forecastingService = $this->createMock(CrmForecastingService::class);
    $this->salesPlaybook = $this->createMock(SalesPlaybookService::class);

    $this->service = new CrmCopilotBridgeService(
      $this->entityTypeManager,
      $logger,
      $this->opportunityService,
      $this->contactService,
      $this->forecastingService,
      $this->salesPlaybook,
    );
  }

  /**
   * @covers ::getVerticalKey
   */
  public function testGetVerticalKeyReturnsGlobal(): void {
    $this->assertSame('__global__', $this->service->getVerticalKey());
  }

  /**
   * @covers ::getSoftSuggestion
   */
  public function testSoftSuggestionReturnsNullWhenTenantNotResolved(): void {
    // User storage returns NULL -> resolveTenantId returns 0.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);

    $result = $this->service->getSoftSuggestion(999);
    $this->assertNull($result);
  }

  /**
   * @covers ::getRelevantContext
   */
  public function testGetRelevantContextReturnsDefaultOnNoTenant(): void {
    // User storage returns NULL -> tenant = 0 -> early return default context.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);

    $context = $this->service->getRelevantContext(999);

    $this->assertSame('crm', $context['vertical']);
    $this->assertFalse($context['has_crm_data']);
    $this->assertIsArray($context['pipeline_summary']);
    $this->assertSame([], $context['top_opportunities']);
    $this->assertSame([], $context['recent_activities']);
    $this->assertSame([], $context['forecast_snapshot']);
  }

}
