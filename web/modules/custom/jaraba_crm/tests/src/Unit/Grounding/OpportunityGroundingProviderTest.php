<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_crm\Unit\Grounding;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_crm\Grounding\OpportunityGroundingProvider;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\jaraba_crm\Grounding\OpportunityGroundingProvider
 * @group jaraba_crm
 */
class OpportunityGroundingProviderTest extends UnitTestCase {

  protected OpportunityGroundingProvider $provider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->provider = new OpportunityGroundingProvider($entityTypeManager);
  }

  /**
   * @covers ::getVerticalKey
   */
  public function testGetVerticalKeyReturnsGlobal(): void {
    $this->assertSame('__global__', $this->provider->getVerticalKey());
  }

  /**
   * @covers ::getPriority
   */
  public function testGetPriorityReturns60(): void {
    $this->assertSame(60, $this->provider->getPriority());
  }

  /**
   * @covers ::search
   */
  public function testSearchReturnsEmptyWhenStorageThrows(): void {
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')
      ->willThrowException(new \Exception('Storage not found'));
    $provider = new OpportunityGroundingProvider($entityTypeManager);

    $results = $provider->search(['test'], 3);
    $this->assertSame([], $results);
  }

}
