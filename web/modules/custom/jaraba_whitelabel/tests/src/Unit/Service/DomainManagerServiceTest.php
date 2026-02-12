<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_whitelabel\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_whitelabel\Entity\CustomDomain;
use Drupal\jaraba_whitelabel\Service\DomainManagerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for DomainManagerService.
 *
 * @group jaraba_whitelabel
 * @coversDefaultClass \Drupal\jaraba_whitelabel\Service\DomainManagerService
 */
class DomainManagerServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected DomainManagerService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mocked entity storage.
   */
  protected EntityStorageInterface&MockObject $storage;

  /**
   * Mocked entity query.
   */
  protected QueryInterface&MockObject $query;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('custom_domain')
      ->willReturn($this->storage);

    $this->service = new DomainManagerService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests adding a domain successfully.
   *
   * @covers ::addDomain
   */
  public function testAddDomain(): void {
    // Expect duplicate check query.
    $countQuery = $this->createMock(QueryInterface::class);
    $countQuery->method('accessCheck')->willReturnSelf();
    $countQuery->method('condition')->willReturnSelf();
    $countQuery->method('count')->willReturnSelf();
    $countQuery->method('execute')->willReturn(0);

    $this->storage->method('getQuery')->willReturn($countQuery);

    // Create a mock entity that returns an ID.
    $entity = $this->createMock(CustomDomain::class);
    $entity->method('id')->willReturn(42);
    $entity->expects($this->once())->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['domain'] === 'app.example.com'
          && $values['tenant_id'] === 1
          && $values['ssl_status'] === CustomDomain::SSL_PENDING
          && $values['dns_verified'] === FALSE
          && $values['domain_status'] === CustomDomain::DOMAIN_PENDING
          && !empty($values['dns_verification_token']);
      }))
      ->willReturn($entity);

    $result = $this->service->addDomain(1, 'app.example.com');
    $this->assertSame(42, $result);
  }

  /**
   * Tests that adding a duplicate domain returns NULL.
   *
   * @covers ::addDomain
   */
  public function testAddDomainDuplicate(): void {
    $countQuery = $this->createMock(QueryInterface::class);
    $countQuery->method('accessCheck')->willReturnSelf();
    $countQuery->method('condition')->willReturnSelf();
    $countQuery->method('count')->willReturnSelf();
    $countQuery->method('execute')->willReturn(1);

    $this->storage->method('getQuery')->willReturn($countQuery);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('duplicate domain'),
        $this->callback(function (array $ctx): bool {
          return $ctx['@domain'] === 'existing.com';
        })
      );

    $result = $this->service->addDomain(5, 'existing.com');
    $this->assertNull($result);
  }

  /**
   * Tests DNS verification success.
   *
   * Note: This test cannot truly verify DNS in a unit test since
   * dns_get_record() makes real lookups. We test the path where
   * the entity is loaded and processed.
   *
   * @covers ::verifyDns
   */
  public function testVerifyDns(): void {
    $domainField = new \stdClass();
    $domainField->value = 'test.example.com';

    $tokenField = new \stdClass();
    $tokenField->value = 'abc123token';

    $entity = $this->createMock(CustomDomain::class);
    $entity->method('get')->willReturnMap([
      ['domain', $domainField],
      ['dns_verification_token', $tokenField],
    ]);

    // The entity will be saved regardless of verification outcome.
    $entity->expects($this->atLeastOnce())->method('set');
    $entity->expects($this->once())->method('save');

    $this->storage->method('load')
      ->with(10)
      ->willReturn($entity);

    // dns_get_record will likely return FALSE/empty in unit tests,
    // so verification should return FALSE.
    $result = $this->service->verifyDns(10);
    $this->assertFalse($result);
  }

  /**
   * Tests that verifyDns returns false for non-existent domain.
   *
   * @covers ::verifyDns
   */
  public function testVerifyDnsNonExistentDomain(): void {
    $this->storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->verifyDns(999);
    $this->assertFalse($result);
  }

  /**
   * Tests getting domains for a tenant.
   *
   * @covers ::getDomainsForTenant
   */
  public function testGetDomainsForTenant(): void {
    $this->query->method('accessCheck')->willReturnSelf();
    $this->query->method('condition')->willReturnSelf();
    $this->query->method('sort')->willReturnSelf();
    $this->query->method('execute')->willReturn([1 => 1, 2 => 2]);

    $this->storage->method('getQuery')->willReturn($this->query);

    // Build mock domain entities.
    $entity1 = $this->createMock(CustomDomain::class);
    $entity1->method('id')->willReturn(1);
    $entity1->method('get')->willReturnCallback(function (string $field) {
      $values = [
        'domain' => (object) ['value' => 'app.example.com'],
        'ssl_status' => (object) ['value' => 'active'],
        'dns_verified' => (object) ['value' => 1],
        'domain_status' => (object) ['value' => 'active'],
        'dns_verification_token' => (object) ['value' => 'token1'],
        'created' => (object) ['value' => 1707700000],
      ];
      return $values[$field] ?? (object) ['value' => NULL];
    });

    $entity2 = $this->createMock(CustomDomain::class);
    $entity2->method('id')->willReturn(2);
    $entity2->method('get')->willReturnCallback(function (string $field) {
      $values = [
        'domain' => (object) ['value' => 'shop.example.com'],
        'ssl_status' => (object) ['value' => 'pending'],
        'dns_verified' => (object) ['value' => 0],
        'domain_status' => (object) ['value' => 'pending'],
        'dns_verification_token' => (object) ['value' => 'token2'],
        'created' => (object) ['value' => 1707600000],
      ];
      return $values[$field] ?? (object) ['value' => NULL];
    });

    $this->storage->method('loadMultiple')
      ->with([1 => 1, 2 => 2])
      ->willReturn([1 => $entity1, 2 => $entity2]);

    $domains = $this->service->getDomainsForTenant(7);

    $this->assertCount(2, $domains);
    $this->assertSame('app.example.com', $domains[0]['domain']);
    $this->assertTrue($domains[0]['dns_verified']);
    $this->assertSame('active', $domains[0]['domain_status']);
    $this->assertSame('shop.example.com', $domains[1]['domain']);
    $this->assertFalse($domains[1]['dns_verified']);
    $this->assertSame('pending', $domains[1]['domain_status']);
  }

  /**
   * Tests getting domains for a tenant with no domains.
   *
   * @covers ::getDomainsForTenant
   */
  public function testGetDomainsForTenantEmpty(): void {
    $this->query->method('accessCheck')->willReturnSelf();
    $this->query->method('condition')->willReturnSelf();
    $this->query->method('sort')->willReturnSelf();
    $this->query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($this->query);

    $domains = $this->service->getDomainsForTenant(99);
    $this->assertSame([], $domains);
  }

  /**
   * Tests removing a domain successfully.
   *
   * @covers ::removeDomain
   */
  public function testRemoveDomain(): void {
    $domainField = new \stdClass();
    $domainField->value = 'old.example.com';

    $entity = $this->createMock(CustomDomain::class);
    $entity->method('get')->willReturnMap([
      ['domain', $domainField],
    ]);
    $entity->expects($this->once())->method('delete');

    $this->storage->method('load')
      ->with(15)
      ->willReturn($entity);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('removed'),
        $this->callback(function (array $ctx): bool {
          return $ctx['@domain'] === 'old.example.com'
            && $ctx['@id'] === 15;
        })
      );

    $result = $this->service->removeDomain(15);
    $this->assertTrue($result);
  }

  /**
   * Tests removing a non-existent domain returns false.
   *
   * @covers ::removeDomain
   */
  public function testRemoveDomainNotFound(): void {
    $this->storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->removeDomain(999);
    $this->assertFalse($result);
  }

  /**
   * Tests that addDomain catches exceptions and logs error.
   *
   * @covers ::addDomain
   */
  public function testAddDomainExceptionIsLogged(): void {
    $this->storage->method('getQuery')
      ->willThrowException(new \RuntimeException('DB down'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error adding domain'),
        $this->callback(function (array $ctx): bool {
          return $ctx['@message'] === 'DB down';
        })
      );

    $result = $this->service->addDomain(1, 'fail.example.com');
    $this->assertNull($result);
  }

}
