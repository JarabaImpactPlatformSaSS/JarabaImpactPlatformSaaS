<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_onboarding\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantOnboardingService;
use Drupal\jaraba_onboarding\Entity\OnboardingTemplate;
use Drupal\jaraba_onboarding\Entity\UserOnboardingProgress;
use Drupal\jaraba_onboarding\Service\OnboardingOrchestratorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para OnboardingOrchestratorService.
 *
 * @covers \Drupal\jaraba_onboarding\Service\OnboardingOrchestratorService
 * @group jaraba_onboarding
 */
class OnboardingOrchestratorServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected TenantOnboardingService $tenantOnboarding;
  protected LoggerInterface $logger;
  protected OnboardingOrchestratorService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantOnboarding = $this->createMock(TenantOnboardingService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new OnboardingOrchestratorService(
      $this->entityTypeManager,
      $this->tenantOnboarding,
      $this->logger,
    );
  }

  /**
   * Tests startOnboarding returns NULL when template not found.
   */
  public function testStartOnboardingTemplateNotFound(): void {
    $templateStorage = $this->createMock(EntityStorageInterface::class);
    $templateStorage->expects($this->once())
      ->method('load')
      ->with(99)
      ->willReturn(NULL);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('onboarding_template')
      ->willReturn($templateStorage);

    $result = $this->service->startOnboarding(1, 99);
    $this->assertNull($result);
  }

  /**
   * Tests startOnboarding returns existing progress ID when already started.
   */
  public function testStartOnboardingAlreadyExists(): void {
    $template = $this->createMock(OnboardingTemplate::class);

    $templateStorage = $this->createMock(EntityStorageInterface::class);
    $templateStorage->method('load')->with(10)->willReturn($template);

    $progressQuery = $this->createMock(QueryInterface::class);
    $progressQuery->method('accessCheck')->willReturnSelf();
    $progressQuery->method('condition')->willReturnSelf();
    $progressQuery->method('execute')->willReturn([42 => 42]);

    $progressStorage = $this->createMock(EntityStorageInterface::class);
    $progressStorage->method('getQuery')->willReturn($progressQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($templateStorage, $progressStorage) {
        return match ($entityType) {
          'onboarding_template' => $templateStorage,
          'user_onboarding_progress' => $progressStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $result = $this->service->startOnboarding(1, 10);
    $this->assertEquals(42, $result);
  }

  /**
   * Tests startOnboarding creates new progress successfully.
   */
  public function testStartOnboardingCreatesProgress(): void {
    $template = $this->createMock(OnboardingTemplate::class);

    $templateStorage = $this->createMock(EntityStorageInterface::class);
    $templateStorage->method('load')->with(10)->willReturn($template);

    $progressQuery = $this->createMock(QueryInterface::class);
    $progressQuery->method('accessCheck')->willReturnSelf();
    $progressQuery->method('condition')->willReturnSelf();
    $progressQuery->method('execute')->willReturn([]);

    $newProgress = $this->createMock(UserOnboardingProgress::class);
    $newProgress->method('id')->willReturn(77);
    $newProgress->method('save')->willReturn(SAVED_NEW);

    $progressStorage = $this->createMock(EntityStorageInterface::class);
    $progressStorage->method('getQuery')->willReturn($progressQuery);
    $progressStorage->method('create')->willReturn($newProgress);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($templateStorage, $progressStorage) {
        return match ($entityType) {
          'onboarding_template' => $templateStorage,
          'user_onboarding_progress' => $progressStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $result = $this->service->startOnboarding(5, 10);
    $this->assertEquals(77, $result);
  }

  /**
   * Tests completeStep returns FALSE when progress not found.
   */
  public function testCompleteStepProgressNotFound(): void {
    $progressStorage = $this->createMock(EntityStorageInterface::class);
    $progressStorage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('user_onboarding_progress')
      ->willReturn($progressStorage);

    $result = $this->service->completeStep(999, 'first_login');
    $this->assertFalse($result);
  }

  /**
   * Tests completeStep returns TRUE when step already completed.
   */
  public function testCompleteStepAlreadyCompleted(): void {
    $progress = $this->createMock(UserOnboardingProgress::class);
    $progress->method('getCompletedSteps')->willReturn(['first_login', 'profile_complete']);

    $progressStorage = $this->createMock(EntityStorageInterface::class);
    $progressStorage->method('load')->with(1)->willReturn($progress);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('user_onboarding_progress')
      ->willReturn($progressStorage);

    $result = $this->service->completeStep(1, 'first_login');
    $this->assertTrue($result);
  }

  /**
   * Tests getProgress returns empty array when no progress exists.
   */
  public function testGetProgressReturnsEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('user_onboarding_progress')
      ->willReturn($storage);

    $result = $this->service->getProgress(123);
    $this->assertEmpty($result);
  }

  /**
   * Tests getProgress returns progress data.
   */
  public function testGetProgressReturnsData(): void {
    $fieldMock = function ($value) {
      $field = $this->createMock(FieldItemListInterface::class);
      $field->value = $value;
      $field->target_id = $value;
      return $field;
    };

    $progress = $this->createMock(UserOnboardingProgress::class);
    $progress->method('id')->willReturn(1);
    $progress->method('getCompletedSteps')->willReturn(['first_login']);
    $progress->method('isComplete')->willReturn(FALSE);
    $progress->method('get')->willReturnCallback(function (string $field) use ($fieldMock) {
      return match ($field) {
        'template_id' => $fieldMock(10),
        'current_step' => $fieldMock(1),
        'progress_percentage' => $fieldMock(25),
        'started_at' => $fieldMock(1700000000),
        'completed_at' => $fieldMock(NULL),
        default => $fieldMock(NULL),
      };
    });

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->with([1 => 1])->willReturn([1 => $progress]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('user_onboarding_progress')
      ->willReturn($storage);

    $result = $this->service->getProgress(5);
    $this->assertNotEmpty($result);
    $this->assertEquals(1, $result[0]['id']);
    $this->assertEquals(10, $result[0]['template_id']);
    $this->assertEquals(25, $result[0]['progress_percentage']);
    $this->assertFalse($result[0]['is_complete']);
  }

  /**
   * Tests startOnboarding handles exceptions gracefully.
   */
  public function testStartOnboardingHandlesException(): void {
    $templateStorage = $this->createMock(EntityStorageInterface::class);
    $templateStorage->method('load')->willThrowException(new \RuntimeException('DB error'));

    $this->entityTypeManager->method('getStorage')
      ->with('onboarding_template')
      ->willReturn($templateStorage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->startOnboarding(1, 10);
    $this->assertNull($result);
  }

}
