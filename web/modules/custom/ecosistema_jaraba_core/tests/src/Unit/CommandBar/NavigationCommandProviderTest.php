<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\CommandBar;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ecosistema_jaraba_core\CommandBar\NavigationCommandProvider;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass that avoids Url::fromRoute() and $this->t().
 *
 * Overrides getNavigationRoutes() to return static data without
 * Drupal service dependencies.
 */
class TestableNavigationCommandProvider extends NavigationCommandProvider {

  /**
   * {@inheritdoc}
   */
  protected function getNavigationRoutes(): array {
    return [
      [
        'label' => 'Dashboard',
        'url' => '/dashboard',
        'icon' => 'dashboard',
        'keywords' => 'dashboard panel inicio home',
      ],
      [
        'label' => 'Blog',
        'url' => '/blog',
        'icon' => 'article',
        'keywords' => 'blog articulos posts noticias',
      ],
      [
        'label' => 'Content Hub',
        'url' => '/content-hub',
        'icon' => 'hub',
        'keywords' => 'content hub contenido articulos categorias',
      ],
      [
        'label' => 'Plans & Pricing',
        'url' => '/planes',
        'icon' => 'payments',
        'keywords' => 'planes precios pricing suscripcion',
      ],
      [
        'label' => 'My Account',
        'url' => '/mi-cuenta',
        'icon' => 'account_circle',
        'keywords' => 'cuenta perfil profile settings configuracion',
      ],
      [
        'label' => 'Usage',
        'url' => '/mi-cuenta/uso',
        'icon' => 'analytics',
        'keywords' => 'uso consumo usage metricas tokens',
      ],
      [
        'label' => 'AI Playground',
        'url' => '/demo/ai-playground',
        'icon' => 'smart_toy',
        'keywords' => 'ai inteligencia artificial playground demo copilot',
      ],
      [
        'label' => 'Contact',
        'url' => '/contacto',
        'icon' => 'contact_mail',
        'keywords' => 'contacto soporte help ayuda',
      ],
    ];
  }

}

/**
 * Tests NavigationCommandProvider search logic.
 *
 * @group ecosistema_jaraba_core
 * @covers \Drupal\ecosistema_jaraba_core\CommandBar\NavigationCommandProvider
 */
class NavigationCommandProviderTest extends TestCase {

  /**
   * The provider under test.
   */
  protected TestableNavigationCommandProvider $provider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->provider = new TestableNavigationCommandProvider();

    // Inject a mock TranslationInterface so that $this->t() works
    // without bootstrapping the Drupal container.
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(fn(TranslatableMarkup $markup) => $markup->getUntranslatedString());
    $this->provider->setStringTranslation($translation);
  }

  /**
   * Tests that searching by label substring finds the matching route.
   */
  public function testSearchMatchesLabel(): void {
    $results = $this->provider->search('dash');

    $this->assertNotEmpty($results);
    $this->assertSame('Dashboard', $results[0]['label']);
  }

  /**
   * Tests that searching by keyword finds matching routes.
   */
  public function testSearchMatchesKeywords(): void {
    // 'inicio' is a keyword for Dashboard.
    $results = $this->provider->search('inicio');

    $this->assertNotEmpty($results);
    $this->assertSame('Dashboard', $results[0]['label']);
  }

  /**
   * Tests that a label starting with the query gets score 90.
   */
  public function testSearchStartsWithHigherScore(): void {
    $results = $this->provider->search('das');

    $this->assertNotEmpty($results);
    $this->assertSame('Dashboard', $results[0]['label']);
    $this->assertSame(90, $results[0]['score']);
  }

  /**
   * Tests that a substring match (not starts_with) gets score 70.
   */
  public function testSearchContainsLowerScore(): void {
    // 'anel' is contained in 'panel' (keyword for Dashboard) but Dashboard label
    // does not start with 'anel'.
    $results = $this->provider->search('anel');

    $this->assertNotEmpty($results);
    $this->assertSame(70, $results[0]['score']);
  }

  /**
   * Tests that a query matching nothing returns empty.
   */
  public function testSearchNoMatch(): void {
    $results = $this->provider->search('zzzzz');

    $this->assertSame([], $results);
  }

  /**
   * Tests that the limit parameter caps results.
   */
  public function testSearchRespectsLimit(): void {
    // 'a' matches many labels/keywords (Dashboard, Plans, Account, Usage, AI Playground, Contact).
    $results = $this->provider->search('a', 2);

    $this->assertCount(2, $results);
  }

  /**
   * Tests that isAccessible always returns TRUE.
   */
  public function testIsAccessible(): void {
    $account = $this->createMock(AccountInterface::class);
    $this->assertTrue($this->provider->isAccessible($account));
  }

  /**
   * Tests that results include expected structure keys.
   */
  public function testResultStructure(): void {
    $results = $this->provider->search('blog');

    $this->assertNotEmpty($results);
    $result = $results[0];

    $this->assertArrayHasKey('label', $result);
    $this->assertArrayHasKey('url', $result);
    $this->assertArrayHasKey('icon', $result);
    $this->assertArrayHasKey('category', $result);
    $this->assertArrayHasKey('score', $result);
  }

  /**
   * Tests case-insensitive matching.
   */
  public function testSearchCaseInsensitive(): void {
    $results = $this->provider->search('DASHBOARD');

    $this->assertNotEmpty($results);
    $this->assertSame('Dashboard', $results[0]['label']);
  }

  /**
   * Tests that the category field is populated in results.
   */
  public function testResultCategoryIsPopulated(): void {
    $results = $this->provider->search('blog');

    $this->assertNotEmpty($results);
    $this->assertNotEmpty((string) $results[0]['category']);
  }

  /**
   * Tests that the URL is correctly set from the route definition.
   */
  public function testResultUrl(): void {
    $results = $this->provider->search('blog');

    $this->assertNotEmpty($results);
    $this->assertSame('/blog', $results[0]['url']);
  }

}
