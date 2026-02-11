<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_analytics\Service\ConsentService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit tests for the ConsentService.
 *
 * @group jaraba_analytics
 * @coversDefaultClass \Drupal\jaraba_analytics\Service\ConsentService
 */
class ConsentServiceTest extends TestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $requestStack;

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The mocked entity storage for consent_record.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_analytics\Service\ConsentService
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->currentUser = $this->createMock(AccountInterface::class);

    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('consent_record')
      ->willReturn($this->storage);

    $this->service = new ConsentService(
      $this->entityTypeManager,
      $this->requestStack,
      $this->currentUser,
    );
  }

  /**
   * Tests getConsent returns NULL when no record is found.
   *
   * @covers ::getConsent
   */
  public function testGetConsentReturnsNullWhenNotFound(): void {
    $this->storage->method('loadByProperties')
      ->with(['visitor_id' => 'visitor-xyz'])
      ->willReturn([]);

    $result = $this->service->getConsent('visitor-xyz');

    $this->assertNull($result);
  }

  /**
   * Tests getConsent returns the existing record.
   *
   * @covers ::getConsent
   */
  public function testGetConsentReturnsRecord(): void {
    $record = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['id', 'hasConsent'])
      ->getMock();
    $record->method('id')->willReturn(10);

    $this->storage->method('loadByProperties')
      ->with(['visitor_id' => 'visitor-abc'])
      ->willReturn([10 => $record]);

    $result = $this->service->getConsent('visitor-abc');

    $this->assertNotNull($result);
    $this->assertSame(10, $result->id());
  }

  /**
   * Tests that grantConsent creates a new record when none exists.
   *
   * @covers ::grantConsent
   */
  public function testGrantConsentCreatesNewRecord(): void {
    // getConsent returns NULL (no existing record).
    $this->storage->method('loadByProperties')
      ->with(['visitor_id' => 'new-visitor'])
      ->willReturn([]);

    // Mock the request so hashIp and truncateUserAgent have data.
    $this->requestStack->method('getCurrentRequest')->willReturn(NULL);

    $newRecord = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['save'])
      ->getMock();
    $newRecord->expects($this->once())->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) {
        return $values['visitor_id'] === 'new-visitor'
          && $values['consent_analytics'] === TRUE
          && $values['consent_marketing'] === FALSE
          && $values['consent_functional'] === TRUE
          && $values['consent_necessary'] === TRUE
          && $values['policy_version'] === '1.0';
      }))
      ->willReturn($newRecord);

    $result = $this->service->grantConsent(
      ['analytics' => TRUE, 'marketing' => FALSE, 'functional' => TRUE],
      'new-visitor',
      5
    );

    $this->assertSame($newRecord, $result);
  }

  /**
   * Tests that grantConsent updates an existing record.
   *
   * @covers ::grantConsent
   */
  public function testGrantConsentUpdatesExisting(): void {
    $existing = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['set', 'save'])
      ->getMock();

    // Expect set() to be called for each consent category.
    $existing->expects($this->exactly(3))
      ->method('set')
      ->willReturnCallback(function (string $field, $value) use ($existing) {
        // Verify the expected fields and values.
        static $callCount = 0;
        $callCount++;
        switch ($callCount) {
          case 1:
            $this->assertSame('consent_analytics', $field);
            $this->assertTrue($value);
            break;

          case 2:
            $this->assertSame('consent_marketing', $field);
            $this->assertTrue($value);
            break;

          case 3:
            $this->assertSame('consent_functional', $field);
            $this->assertTrue($value);
            break;
        }
        return $existing;
      });

    $existing->expects($this->once())->method('save');

    $this->storage->method('loadByProperties')
      ->with(['visitor_id' => 'returning-visitor'])
      ->willReturn([5 => $existing]);

    $this->requestStack->method('getCurrentRequest')->willReturn(NULL);

    $result = $this->service->grantConsent(
      ['analytics' => TRUE, 'marketing' => TRUE, 'functional' => TRUE],
      'returning-visitor'
    );

    $this->assertSame($existing, $result);
  }

  /**
   * Tests that revokeConsent sets all consent fields to FALSE.
   *
   * @covers ::revokeConsent
   */
  public function testRevokeConsentSetsAllFalse(): void {
    $existing = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['set', 'save'])
      ->getMock();

    $setCallArgs = [];
    $existing->method('set')
      ->willReturnCallback(function (string $field, $value) use (&$setCallArgs, $existing) {
        $setCallArgs[] = [$field, $value];
        return $existing;
      });

    $existing->expects($this->once())->method('save');

    $this->storage->method('loadByProperties')
      ->with(['visitor_id' => 'revoke-visitor'])
      ->willReturn([1 => $existing]);

    $this->service->revokeConsent('revoke-visitor');

    // Verify that all three consent fields were set to FALSE.
    $this->assertCount(3, $setCallArgs);
    $this->assertSame(['consent_analytics', FALSE], $setCallArgs[0]);
    $this->assertSame(['consent_marketing', FALSE], $setCallArgs[1]);
    $this->assertSame(['consent_functional', FALSE], $setCallArgs[2]);
  }

  /**
   * Tests that hasConsent returns TRUE for 'necessary' when no record exists.
   *
   * GDPR requires necessary cookies to always be active.
   *
   * @covers ::hasConsent
   */
  public function testHasConsentReturnsTrueForNecessaryWithoutRecord(): void {
    $this->storage->method('loadByProperties')
      ->with(['visitor_id' => 'unknown-visitor'])
      ->willReturn([]);

    $result = $this->service->hasConsent('unknown-visitor', 'necessary');

    $this->assertTrue($result);
  }

  /**
   * Tests that hasConsent delegates to the record's hasConsent method.
   *
   * @covers ::hasConsent
   */
  public function testHasConsentDelegatesToRecord(): void {
    $record = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['hasConsent'])
      ->getMock();
    $record->expects($this->once())
      ->method('hasConsent')
      ->with('analytics')
      ->willReturn(TRUE);

    $this->storage->method('loadByProperties')
      ->with(['visitor_id' => 'consented-visitor'])
      ->willReturn([1 => $record]);

    $result = $this->service->hasConsent('consented-visitor', 'analytics');

    $this->assertTrue($result);
  }

  /**
   * Tests that hashIp uses SHA-256 with the GDPR salt.
   *
   * @covers ::hashIp
   */
  public function testHashIpUsesSha256WithSalt(): void {
    $reflection = new \ReflectionMethod($this->service, 'hashIp');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service, '127.0.0.1');

    $expected = hash('sha256', '127.0.0.1' . 'jaraba_salt_gdpr');

    $this->assertSame($expected, $result);
  }

  /**
   * Tests that truncateUserAgent limits output to 100 characters.
   *
   * @covers ::truncateUserAgent
   */
  public function testTruncateUserAgentTo100Chars(): void {
    $reflection = new \ReflectionMethod($this->service, 'truncateUserAgent');
    $reflection->setAccessible(TRUE);

    // Create a string longer than 100 characters.
    $longUserAgent = str_repeat('A', 200);

    $result = $reflection->invoke($this->service, $longUserAgent);

    $this->assertSame(100, strlen($result));
    $this->assertSame(str_repeat('A', 100), $result);
  }

}
