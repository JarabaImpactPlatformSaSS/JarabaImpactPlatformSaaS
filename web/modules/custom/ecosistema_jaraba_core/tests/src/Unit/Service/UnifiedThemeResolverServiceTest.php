<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Service\UnifiedThemeResolverService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests UnifiedThemeResolverService cascade logic.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\UnifiedThemeResolverService
 * @group ecosistema_jaraba_core
 */
class UnifiedThemeResolverServiceTest extends TestCase {

  /**
   * Tests resolveForCurrentRequest returns 'none' when no resolvers available.
   */
  public function testResolveWithNoResolversReturnsNone(): void {
    $service = $this->buildService();

    $result = $service->resolveForCurrentRequest();

    $this->assertSame('none', $result['resolved_via']);
    $this->assertNull($result['tenant_id']);
    $this->assertNull($result['meta_site']);
    $this->assertNull($result['tenant_theme_config']);
    $this->assertSame([], $result['theme_overrides']);
  }

  /**
   * Tests resolution via hostname (anonymous users).
   */
  public function testResolveByHostname(): void {
    $siteConfig = $this->createSiteConfigMock(tenantId: 42);
    $metaSite = [
      'site_config' => $siteConfig,
      'group_id' => 7,
      'tenant_name' => 'Mi Empresa',
      'nav_items_formatted' => ['Inicio|/', 'Blog|/blog'],
      'nav_items' => [
        ['title' => 'Inicio', 'url' => '/'],
        ['title' => 'Blog', 'url' => '/blog'],
      ],
      'footer_items' => [],
    ];

    $metaSiteResolver = $this->createMetaSiteResolverMock($metaSite);

    $themeConfig = $this->createThemeConfigMock([
      'header_cta_enabled' => TRUE,
      'header_cta_text' => 'Registrate',
      'header_cta_url' => '/registro',
      'header_variant' => 'classic',
      'header_sticky' => TRUE,
      'footer_variant' => 'mega',
      'footer_copyright' => '(c) [year] Mi Empresa',
    ]);

    $entityTypeManager = $this->createEntityTypeManagerMock($themeConfig, 42);
    $service = $this->buildService(
      entityTypeManager: $entityTypeManager,
      metaSiteResolver: $metaSiteResolver,
    );

    $result = $service->resolveForCurrentRequest();

    $this->assertSame('hostname', $result['resolved_via']);
    $this->assertSame(42, $result['tenant_id']);
    $this->assertSame(7, $result['group_id']);
    $this->assertNotEmpty($result['theme_overrides']);
    $this->assertTrue($result['theme_overrides']['enable_header_cta']);
    $this->assertSame('Registrate', $result['theme_overrides']['header_cta_text']);
    $this->assertSame('/registro', $result['theme_overrides']['header_cta_url']);
    $this->assertSame('classic', $result['theme_overrides']['header_layout']);
    $this->assertFalse($result['theme_overrides']['header_megamenu']);
    $this->assertFalse($result['theme_overrides']['footer_show_powered_by']);
  }

  /**
   * Tests fallback resolution via authenticated user.
   */
  public function testResolveByUserFallback(): void {
    $themeConfig = $this->createThemeConfigMock([
      'header_cta_enabled' => FALSE,
      'header_cta_text' => '',
      'header_cta_url' => '',
      'header_variant' => '',
      'header_sticky' => FALSE,
      'footer_variant' => '',
      'footer_copyright' => '',
    ]);

    $tenantContext = $this->createMock(TenantContextService::class);
    $tenantContext->method('getCurrentTenantId')->willReturn(99);

    $entityTypeManager = $this->createEntityTypeManagerMock($themeConfig, 99);
    $service = $this->buildService(
      entityTypeManager: $entityTypeManager,
      tenantContext: $tenantContext,
    );

    $result = $service->resolveForCurrentRequest();

    $this->assertSame('user', $result['resolved_via']);
    $this->assertSame(99, $result['tenant_id']);
    $this->assertSame($themeConfig, $result['tenant_theme_config']);
  }

  /**
   * Tests that request-scoped cache returns same result on second call.
   */
  public function testRequestScopedCache(): void {
    $tenantContext = $this->createMock(TenantContextService::class);
    $tenantContext->expects($this->once())
      ->method('getCurrentTenantId')
      ->willReturn(10);

    $service = $this->buildService(tenantContext: $tenantContext);

    $first = $service->resolveForCurrentRequest();
    $second = $service->resolveForCurrentRequest();

    $this->assertSame($first, $second);
  }

  /**
   * Tests SiteConfig overrides TenantThemeConfig (Level 5 > Level 4).
   */
  public function testSiteConfigOverridesTenantThemeConfig(): void {
    $siteConfig = $this->createSiteConfigMock(
      tenantId: 42,
      headerCtaText: 'Contacto',
      headerCtaUrl: '/contacto',
      headerType: 'minimal',
    );
    $metaSite = [
      'site_config' => $siteConfig,
      'group_id' => 3,
      'tenant_name' => 'Test',
      'nav_items_formatted' => [],
      'nav_items' => [],
      'footer_items' => [],
    ];
    $metaSiteResolver = $this->createMetaSiteResolverMock($metaSite);

    $themeConfig = $this->createThemeConfigMock([
      'header_cta_enabled' => TRUE,
      'header_cta_text' => 'Registrate',
      'header_cta_url' => '/registro',
      'header_variant' => 'classic',
      'header_sticky' => TRUE,
      'footer_variant' => '',
      'footer_copyright' => '',
    ]);

    $entityTypeManager = $this->createEntityTypeManagerMock($themeConfig, 42);
    $service = $this->buildService(
      entityTypeManager: $entityTypeManager,
      metaSiteResolver: $metaSiteResolver,
    );

    $result = $service->resolveForCurrentRequest();

    // SiteConfig (Level 5) should override TenantThemeConfig (Level 4).
    $this->assertSame('Contacto', $result['theme_overrides']['header_cta_text']);
    $this->assertSame('/contacto', $result['theme_overrides']['header_cta_url']);
    $this->assertSame('minimal', $result['theme_overrides']['header_layout']);
  }

  /**
   * Tests that MetaSiteResolver exceptions are caught gracefully.
   */
  public function testMetaSiteResolverExceptionIsCaught(): void {
    $resolver = new class {

      /**
       *
       */
      public function resolveFromRequest(Request $request): ?array {
        throw new \RuntimeException('DNS lookup failed');
      }

    };

    $service = $this->buildService(metaSiteResolver: $resolver);
    $result = $service->resolveForCurrentRequest();

    $this->assertSame('none', $result['resolved_via']);
    $this->assertNull($result['meta_site']);
  }

  /**
   * Builds a UnifiedThemeResolverService instance with optional dependencies.
   */
  protected function buildService(
    ?EntityTypeManagerInterface $entityTypeManager = NULL,
    ?object $metaSiteResolver = NULL,
    ?TenantContextService $tenantContext = NULL,
  ): UnifiedThemeResolverService {
    if ($entityTypeManager === NULL) {
      $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
      $entityTypeManager->method('hasDefinition')->willReturn(FALSE);
    }

    $request = Request::create('https://test.example.com/');
    $requestStack = new RequestStack();
    $requestStack->push($request);

    $logger = $this->createMock(LoggerInterface::class);

    return new UnifiedThemeResolverService(
      $entityTypeManager,
      $requestStack,
      $logger,
      $metaSiteResolver,
      $tenantContext,
    );
  }

  /**
   * Creates a mock TenantThemeConfig with specified field values.
   */
  protected function createThemeConfigMock(array $fieldValues): object {
    $config = new class ($fieldValues) {

      protected array $fields;

      public function __construct(array $fields) {
        $this->fields = $fields;
      }

      /**
       *
       */
      public function hasField(string $name): bool {
        return array_key_exists($name, $this->fields)
          || str_starts_with($name, 'social_');
      }

      /**
       *
       */
      public function get(string $name): object {
        $value = $this->fields[$name] ?? NULL;
        return new class ($value) {

          public mixed $value;

          public function __construct(mixed $v) {
            $this->value = $v;
          }

        };
      }

    };
    return $config;
  }

  /**
   * Creates a mock SiteConfig entity.
   */
  protected function createSiteConfigMock(
    int $tenantId,
    string $headerCtaText = '',
    string $headerCtaUrl = '',
    string $headerType = '',
  ): object {
    return new class ($tenantId, $headerCtaText, $headerCtaUrl, $headerType) {

      protected int $tenantId;
      protected string $ctaText;
      protected string $ctaUrl;
      protected string $headerType;

      public function __construct(int $tid, string $ct, string $cu, string $ht) {
        $this->tenantId = $tid;
        $this->ctaText = $ct;
        $this->ctaUrl = $cu;
        $this->headerType = $ht;
      }

      /**
       *
       */
      public function isTranslatable(): bool {
        return FALSE;
      }

      /**
       *
       */
      public function hasTranslation(string $langcode): bool {
        return FALSE;
      }

      /**
       *
       */
      public function hasField(string $name): bool {
        return in_array($name, ['tenant_id', 'site_logo', 'footer_col1_title', 'footer_col2_title', 'footer_col3_title']);
      }

      /**
       *
       */
      public function get(string $name): object {
        if ($name === 'tenant_id') {
          $entity = new class ($this->tenantId) {

            protected int $id;

            public function __construct(int $id) {
              $this->id = $id;
            }

            /**
             *
             */
            public function id(): int {
              return $this->id;
            }

          };
          return new class ($entity) {

            public ?object $entity;

            public function __construct(?object $e) {
              $this->entity = $e;
            }

          };
        }
        return new class () {

          public ?string $value = NULL;
          public ?object $entity = NULL;

        };
      }

      /**
       *
       */
      public function getHeaderCtaText(): string {
        return $this->ctaText;
      }

      /**
       *
       */
      public function getHeaderCtaUrl(): string {
        return $this->ctaUrl;
      }

      /**
       *
       */
      public function getHeaderType(): string {
        return $this->headerType;
      }

      /**
       *
       */
      public function isHeaderSticky(): bool {
        return FALSE;
      }

      /**
       *
       */
      public function getFooterType(): string {
        return '';
      }

      /**
       *
       */
      public function getFooterCopyright(): string {
        return '';
      }

      /**
       *
       */
      public function isHeaderShowAuth(): bool {
        return TRUE;
      }

      /**
       *
       */
      public function isEcosystemFooterEnabled(): bool {
        return FALSE;
      }

    };
  }

  /**
   * Creates a mock MetaSiteResolverService.
   */
  protected function createMetaSiteResolverMock(?array $metaSite): object {
    return new class ($metaSite) {

      protected ?array $metaSite;

      public function __construct(?array $ms) {
        $this->metaSite = $ms;
      }

      /**
       *
       */
      public function resolveFromRequest(Request $request): ?array {
        return $this->metaSite;
      }

    };
  }

  /**
   * Creates EntityTypeManager mock that returns a TenantThemeConfig.
   */
  protected function createEntityTypeManagerMock(
    object $themeConfig,
    int $tenantId,
  ): EntityTypeManagerInterface {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['tenant_id' => $tenantId, 'is_active' => TRUE])
      ->willReturn([$themeConfig]);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('hasDefinition')
      ->with('tenant_theme_config')
      ->willReturn(TRUE);
    $entityTypeManager->method('getStorage')
      ->with('tenant_theme_config')
      ->willReturn($storage);

    return $entityTypeManager;
  }

}
