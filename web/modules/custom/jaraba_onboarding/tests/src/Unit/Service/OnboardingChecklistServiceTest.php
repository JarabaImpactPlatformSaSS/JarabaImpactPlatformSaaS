<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_onboarding\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_onboarding\Entity\OnboardingTemplate;
use Drupal\jaraba_onboarding\Entity\UserOnboardingProgress;
use Drupal\jaraba_onboarding\Service\OnboardingChecklistService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para OnboardingChecklistService.
 *
 * @covers \Drupal\jaraba_onboarding\Service\OnboardingChecklistService
 * @group jaraba_onboarding
 */
class OnboardingChecklistServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;
  protected OnboardingChecklistService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new OnboardingChecklistService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests getChecklist returns empty when no progress exists.
   */
  public function testGetChecklistReturnsEmptyNoProgress(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $progressStorage = $this->createMock(EntityStorageInterface::class);
    $progressStorage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('user_onboarding_progress')
      ->willReturn($progressStorage);

    $result = $this->service->getChecklist(42);
    $this->assertEmpty($result);
  }

  /**
   * Tests getChecklist returns items from template config.
   */
  public function testGetChecklistReturnsItems(): void {
    $stepsConfig = [
      ['id' => 'first_login', 'label' => 'Primer inicio', 'description' => 'Inicia sesion', 'order' => 1],
      ['id' => 'profile_complete', 'label' => 'Perfil completo', 'description' => 'Completa tu perfil', 'order' => 2],
      ['id' => 'tour_complete', 'label' => 'Tour completado', 'description' => 'Haz el tour', 'order' => 3],
    ];

    // Progress mock.
    $templateIdField = $this->createMock(FieldItemListInterface::class);
    $templateIdField->target_id = 10;

    $progress = $this->createMock(UserOnboardingProgress::class);
    $progress->method('getCompletedSteps')->willReturn(['first_login']);
    $progress->method('get')->willReturnCallback(function (string $field) use ($templateIdField) {
      if ($field === 'template_id') {
        return $templateIdField;
      }
      $mock = $this->createMock(FieldItemListInterface::class);
      $mock->target_id = NULL;
      return $mock;
    });

    // Template mock.
    $template = $this->createMock(OnboardingTemplate::class);
    $template->method('getStepsConfig')->willReturn($stepsConfig);

    // Progress storage.
    $progressQuery = $this->createMock(QueryInterface::class);
    $progressQuery->method('accessCheck')->willReturnSelf();
    $progressQuery->method('condition')->willReturnSelf();
    $progressQuery->method('sort')->willReturnSelf();
    $progressQuery->method('range')->willReturnSelf();
    $progressQuery->method('execute')->willReturn([1 => 1]);

    $progressStorage = $this->createMock(EntityStorageInterface::class);
    $progressStorage->method('getQuery')->willReturn($progressQuery);
    $progressStorage->method('load')->with(1)->willReturn($progress);

    // Template storage.
    $templateStorage = $this->createMock(EntityStorageInterface::class);
    $templateStorage->method('load')->with(10)->willReturn($template);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($progressStorage, $templateStorage) {
        return match ($entityType) {
          'user_onboarding_progress' => $progressStorage,
          'onboarding_template' => $templateStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $result = $this->service->getChecklist(42);

    $this->assertCount(3, $result);
    $this->assertEquals('first_login', $result[0]['id']);
    $this->assertTrue($result[0]['completed']);
    $this->assertEquals('profile_complete', $result[1]['id']);
    $this->assertFalse($result[1]['completed']);
    $this->assertEquals('tour_complete', $result[2]['id']);
    $this->assertFalse($result[2]['completed']);
  }

  /**
   * Tests isChecklistComplete returns FALSE when not all items are done.
   */
  public function testIsChecklistCompleteReturnsFalse(): void {
    $stepsConfig = [
      ['id' => 'first_login', 'label' => 'Login', 'description' => '', 'order' => 1],
      ['id' => 'profile_complete', 'label' => 'Perfil', 'description' => '', 'order' => 2],
    ];

    $templateIdField = $this->createMock(FieldItemListInterface::class);
    $templateIdField->target_id = 10;

    $progress = $this->createMock(UserOnboardingProgress::class);
    $progress->method('getCompletedSteps')->willReturn(['first_login']);
    $progress->method('get')->willReturnCallback(function (string $field) use ($templateIdField) {
      if ($field === 'template_id') {
        return $templateIdField;
      }
      $mock = $this->createMock(FieldItemListInterface::class);
      $mock->target_id = NULL;
      return $mock;
    });

    $template = $this->createMock(OnboardingTemplate::class);
    $template->method('getStepsConfig')->willReturn($stepsConfig);

    $progressQuery = $this->createMock(QueryInterface::class);
    $progressQuery->method('accessCheck')->willReturnSelf();
    $progressQuery->method('condition')->willReturnSelf();
    $progressQuery->method('sort')->willReturnSelf();
    $progressQuery->method('range')->willReturnSelf();
    $progressQuery->method('execute')->willReturn([1 => 1]);

    $progressStorage = $this->createMock(EntityStorageInterface::class);
    $progressStorage->method('getQuery')->willReturn($progressQuery);
    $progressStorage->method('load')->willReturn($progress);

    $templateStorage = $this->createMock(EntityStorageInterface::class);
    $templateStorage->method('load')->willReturn($template);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($progressStorage, $templateStorage) {
        return match ($entityType) {
          'user_onboarding_progress' => $progressStorage,
          'onboarding_template' => $templateStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $this->assertFalse($this->service->isChecklistComplete(42));
  }

  /**
   * Tests isChecklistComplete returns TRUE when all items are done.
   */
  public function testIsChecklistCompleteReturnsTrue(): void {
    $stepsConfig = [
      ['id' => 'first_login', 'label' => 'Login', 'description' => '', 'order' => 1],
      ['id' => 'profile_complete', 'label' => 'Perfil', 'description' => '', 'order' => 2],
    ];

    $templateIdField = $this->createMock(FieldItemListInterface::class);
    $templateIdField->target_id = 10;

    $progress = $this->createMock(UserOnboardingProgress::class);
    $progress->method('getCompletedSteps')->willReturn(['first_login', 'profile_complete']);
    $progress->method('get')->willReturnCallback(function (string $field) use ($templateIdField) {
      if ($field === 'template_id') {
        return $templateIdField;
      }
      $mock = $this->createMock(FieldItemListInterface::class);
      $mock->target_id = NULL;
      return $mock;
    });

    $template = $this->createMock(OnboardingTemplate::class);
    $template->method('getStepsConfig')->willReturn($stepsConfig);

    $progressQuery = $this->createMock(QueryInterface::class);
    $progressQuery->method('accessCheck')->willReturnSelf();
    $progressQuery->method('condition')->willReturnSelf();
    $progressQuery->method('sort')->willReturnSelf();
    $progressQuery->method('range')->willReturnSelf();
    $progressQuery->method('execute')->willReturn([1 => 1]);

    $progressStorage = $this->createMock(EntityStorageInterface::class);
    $progressStorage->method('getQuery')->willReturn($progressQuery);
    $progressStorage->method('load')->willReturn($progress);

    $templateStorage = $this->createMock(EntityStorageInterface::class);
    $templateStorage->method('load')->willReturn($template);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($progressStorage, $templateStorage) {
        return match ($entityType) {
          'user_onboarding_progress' => $progressStorage,
          'onboarding_template' => $templateStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $this->assertTrue($this->service->isChecklistComplete(42));
  }

  /**
   * Tests getChecklist handles exceptions gracefully.
   */
  public function testGetChecklistHandlesException(): void {
    $progressStorage = $this->createMock(EntityStorageInterface::class);
    $progressStorage->method('getQuery')
      ->willThrowException(new \RuntimeException('DB error'));

    $this->entityTypeManager->method('getStorage')
      ->with('user_onboarding_progress')
      ->willReturn($progressStorage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getChecklist(42);
    $this->assertEmpty($result);
  }

  /**
   * Tests isChecklistComplete returns FALSE when checklist is empty.
   */
  public function testIsChecklistCompleteReturnsFalseWhenEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $progressStorage = $this->createMock(EntityStorageInterface::class);
    $progressStorage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('user_onboarding_progress')
      ->willReturn($progressStorage);

    $this->assertFalse($this->service->isChecklistComplete(42));
  }

}
