<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\CommandBar\CommandProviderInterface;
use Drupal\ecosistema_jaraba_core\Service\CommandRegistryService;
use PHPUnit\Framework\TestCase;

/**
 * Tests CommandRegistryService search and provider management.
 *
 * @group ecosistema_jaraba_core
 * @covers \Drupal\ecosistema_jaraba_core\Service\CommandRegistryService
 */
class CommandRegistryServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected CommandRegistryService $registry;

  /**
   * The mocked account.
   */
  protected AccountInterface $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->registry = new CommandRegistryService();
    $this->account = $this->createMock(AccountInterface::class);
    $this->account->method('hasPermission')->willReturnCallback(
      fn(string $permission) => $permission === 'access content'
    );
  }

  /**
   * Tests that an empty query returns empty results.
   */
  public function testSearchEmptyQuery(): void {
    $provider = $this->createAccessibleProvider([
      ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'home', 'category' => 'Nav', 'score' => 80],
    ]);
    $this->registry->addProvider($provider);

    $results = $this->registry->search('', $this->account);
    $this->assertSame([], $results);
  }

  /**
   * Tests that searching with no registered providers returns empty.
   */
  public function testSearchWithNoProviders(): void {
    $results = $this->registry->search('test', $this->account);
    $this->assertSame([], $results);
  }

  /**
   * Tests that results from multiple providers are merged and sorted by score.
   */
  public function testSearchMergesProviderResults(): void {
    $provider1 = $this->createAccessibleProvider([
      ['label' => 'Blog', 'url' => '/blog', 'icon' => 'article', 'category' => 'Nav', 'score' => 70],
    ]);
    $provider2 = $this->createAccessibleProvider([
      ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'home', 'category' => 'Nav', 'score' => 90],
    ]);

    $this->registry->addProvider($provider1);
    $this->registry->addProvider($provider2);

    $results = $this->registry->search('test', $this->account);

    $this->assertCount(2, $results);
    // Highest score first.
    $this->assertSame('Dashboard', $results[0]['label']);
    $this->assertSame('Blog', $results[1]['label']);
  }

  /**
   * Tests that inaccessible providers are skipped.
   */
  public function testSearchFiltersInaccessibleProviders(): void {
    $accessible = $this->createAccessibleProvider([
      ['label' => 'Visible', 'url' => '/v', 'icon' => 'check', 'category' => 'Nav', 'score' => 80],
    ]);

    $inaccessible = $this->createMock(CommandProviderInterface::class);
    $inaccessible->method('isAccessible')->willReturn(FALSE);
    $inaccessible->expects($this->never())->method('search');

    $this->registry->addProvider($inaccessible);
    $this->registry->addProvider($accessible);

    $results = $this->registry->search('test', $this->account);

    $this->assertCount(1, $results);
    $this->assertSame('Visible', $results[0]['label']);
  }

  /**
   * Tests that the limit parameter caps the number of results.
   */
  public function testSearchRespectsLimit(): void {
    $items = [];
    for ($i = 0; $i < 10; $i++) {
      $items[] = [
        'label' => "Item $i",
        'url' => "/item/$i",
        'icon' => 'star',
        'category' => 'Test',
        'score' => 50 + $i,
      ];
    }

    $provider = $this->createAccessibleProvider($items);
    $this->registry->addProvider($provider);

    $results = $this->registry->search('test', $this->account, 3);

    $this->assertCount(3, $results);
    // Top 3 by score (59, 58, 57).
    $this->assertSame('Item 9', $results[0]['label']);
    $this->assertSame('Item 8', $results[1]['label']);
    $this->assertSame('Item 7', $results[2]['label']);
  }

  /**
   * Tests that results requiring a permission the user lacks are filtered out.
   */
  public function testSearchFiltersByPermission(): void {
    $provider = $this->createAccessibleProvider([
      [
        'label' => 'Public Item',
        'url' => '/public',
        'icon' => 'public',
        'category' => 'Test',
        'score' => 80,
        'permission' => 'access content',
      ],
      [
        'label' => 'Admin Item',
        'url' => '/admin',
        'icon' => 'admin',
        'category' => 'Test',
        'score' => 90,
        'permission' => 'administer site',
      ],
    ]);

    $this->registry->addProvider($provider);

    $results = $this->registry->search('test', $this->account);

    $this->assertCount(1, $results);
    $this->assertSame('Public Item', $results[0]['label']);
    // The permission key should be stripped from the result.
    $this->assertArrayNotHasKey('permission', $results[0]);
  }

  /**
   * Tests that a provider throwing an exception is skipped gracefully.
   */
  public function testSearchHandlesProviderException(): void {
    $failingProvider = $this->createMock(CommandProviderInterface::class);
    $failingProvider->method('isAccessible')->willReturn(TRUE);
    $failingProvider->method('search')->willThrowException(new \RuntimeException('Provider failure'));

    $workingProvider = $this->createAccessibleProvider([
      ['label' => 'Working', 'url' => '/ok', 'icon' => 'check', 'category' => 'Nav', 'score' => 80],
    ]);

    $this->registry->addProvider($failingProvider);
    $this->registry->addProvider($workingProvider);

    $results = $this->registry->search('test', $this->account);

    $this->assertCount(1, $results);
    $this->assertSame('Working', $results[0]['label']);
  }

  /**
   * Tests that whitespace-only query returns empty.
   */
  public function testSearchWhitespaceQuery(): void {
    $provider = $this->createAccessibleProvider([
      ['label' => 'Item', 'url' => '/item', 'icon' => 'star', 'category' => 'Nav', 'score' => 80],
    ]);
    $this->registry->addProvider($provider);

    $results = $this->registry->search('   ', $this->account);
    $this->assertSame([], $results);
  }

  /**
   * Creates a mock CommandProviderInterface that is accessible and returns given results.
   *
   * @param array $results
   *   The results the provider should return from search().
   *
   * @return \Drupal\ecosistema_jaraba_core\CommandBar\CommandProviderInterface
   *   The mocked provider.
   */
  protected function createAccessibleProvider(array $results): CommandProviderInterface {
    $provider = $this->createMock(CommandProviderInterface::class);
    $provider->method('isAccessible')->willReturn(TRUE);
    $provider->method('search')->willReturn($results);
    return $provider;
  }

}
