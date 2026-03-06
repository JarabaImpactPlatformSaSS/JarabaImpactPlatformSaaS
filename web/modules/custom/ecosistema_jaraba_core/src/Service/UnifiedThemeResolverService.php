<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * THEMING-UNIFY-001: Resuelve la configuracion visual completa para el request.
 *
 * Implementa una cascada de 5 niveles:
 * 1. Plataforma (ecosistema_jaraba_theme.settings — defaults globales)
 * 2. Vertical (paleta por sector) [delegado a IndustryPresetService]
 * 3. Plan (features por plan) [reservado]
 * 4. Tenant (TenantThemeConfig — personalizacion del tenant)
 * 5. Meta-sitio (SiteConfig — override por dominio/pagina especifica)
 *
 * Funciona tanto para usuarios anonimos (resolucion por hostname via
 * MetaSiteResolverService) como para usuarios autenticados (resolucion
 * via TenantContextService).
 *
 * SSOT-THEME-001:
 * - TenantThemeConfig = fuente de verdad para TODO lo visual (colores,
 *   fuentes, layout, CTA, footer, redes sociales).
 * - SiteConfig = fuente de verdad para TODO lo estructural (nombre,
 *   logo, navegacion, paginas legales, SEO).
 * - Para campos visuales (CTA), TenantThemeConfig gana (SSOT visual).
 * - Para campos estructurales (layout, footer), SiteConfig gana como Nivel 5.
 *
 * @package Drupal\ecosistema_jaraba_core\Service
 */
class UnifiedThemeResolverService {

  /**
   * Cache estatica por request para evitar queries repetidas.
   *
   * Se resetea automaticamente en cada nuevo request HTTP.
   */
  protected ?array $resolvedContext = NULL;

  /**
   * Constructs UnifiedThemeResolverService.
   *
   * OPTIONAL-CROSSMODULE-001: MetaSiteResolverService y TenantContextService
   * son inyectados como opcionales (@?) porque pertenecen a otros modulos.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager para cargar TenantThemeConfig.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack para obtener el hostname actual.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger channel.
   * @param object|null $metaSiteResolver
   *   MetaSiteResolverService (jaraba_site_builder) — opcional.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null $tenantContext
   *   TenantContextService — opcional (dentro del mismo modulo, pero
   *   nullable para tolerancia a fallos).
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RequestStack $requestStack,
    protected LoggerInterface $logger,
    protected ?object $metaSiteResolver = NULL,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   * Resuelve el contexto de tema completo para el request actual.
   *
   * Combina resolucion por hostname (anonimos) y por usuario (autenticados).
   * Aplica cascada TenantThemeConfig (Nivel 4) -> SiteConfig (Nivel 5).
   *
   * El resultado se cachea por request (static property) para evitar
   * multiples queries si se llama desde varios hooks en el mismo request.
   *
   * @return array
   *   Array con claves:
   *   - 'tenant_theme_config': TenantThemeConfig entity o NULL.
   *   - 'site_config': SiteConfig entity o NULL.
   *   - 'meta_site': array de MetaSiteResolverService o NULL.
   *   - 'theme_overrides': array key=>value con overrides para theme_settings.
   *   - 'tenant_id': int o NULL (tenant_id resuelto = Tenant entity ID).
   *   - 'group_id': int o NULL (Group entity ID del meta-sitio).
   *   - 'resolved_via': string ('hostname'|'user'|'none').
   */
  public function resolveForCurrentRequest(): array {
    if ($this->resolvedContext !== NULL) {
      return $this->resolvedContext;
    }

    $result = [
      'tenant_theme_config' => NULL,
      'site_config' => NULL,
      'meta_site' => NULL,
      'theme_overrides' => [],
      'tenant_id' => NULL,
      'group_id' => NULL,
      'resolved_via' => 'none',
    ];

    // Estrategia A: Resolver por hostname (funciona para anonimos).
    // MetaSiteResolverService usa 3 subestrategias:
    // 1. Domain Access entity (hostname exacto)
    // 2. Tenant.domain field (hostname exacto)
    // 3. Subdomain prefix (primer segmento del hostname)
    $metaSite = $this->resolveByHostname();
    if ($metaSite !== NULL) {
      $result['meta_site'] = $metaSite;
      $result['site_config'] = $metaSite['site_config'] ?? NULL;
      $result['group_id'] = $metaSite['group_id'] ?? NULL;

      // Resolver tenant_id desde el group_id via TenantBridgeService pattern.
      // El SiteConfig tiene tenant_id como entity_reference a group.
      if ($result['site_config'] !== NULL) {
        $tenantRef = $result['site_config']->get('tenant_id')->entity ?? NULL;
        if ($tenantRef !== NULL) {
          $result['tenant_id'] = (int) $tenantRef->id();
        }
      }
      $result['resolved_via'] = 'hostname';
    }

    // Estrategia B: Resolver por usuario autenticado (fallback).
    // Solo se usa si la resolucion por hostname no encontro tenant.
    if ($result['tenant_id'] === NULL) {
      $tenantId = $this->resolveByUser();
      if ($tenantId !== NULL) {
        $result['tenant_id'] = $tenantId;
        $result['resolved_via'] = 'user';
      }
    }

    // Cargar TenantThemeConfig activa si hay tenant_id resuelto.
    if ($result['tenant_id'] !== NULL) {
      $result['tenant_theme_config'] = $this->loadTenantThemeConfig(
        $result['tenant_id']
      );
    }

    // Construir theme_overrides aplicando cascada Nivel 4 -> Nivel 5.
    $result['theme_overrides'] = $this->buildThemeOverrides(
      $result['tenant_theme_config'],
      $result['site_config'],
      $result['meta_site']
    );

    $this->resolvedContext = $result;
    return $result;
  }

  /**
   * Resuelve meta-sitio por hostname del request actual.
   *
   * Delega a MetaSiteResolverService::resolveFromRequest() que internamente
   * usa resolveFromDomain() con las 3 estrategias de resolucion.
   *
   * @return array|null
   *   Array de contexto del meta-sitio o NULL si no se resuelve.
   */
  protected function resolveByHostname(): ?array {
    if ($this->metaSiteResolver === NULL) {
      return NULL;
    }

    $request = $this->requestStack->getCurrentRequest();
    if ($request === NULL) {
      return NULL;
    }

    try {
      return $this->metaSiteResolver->resolveFromRequest($request);
    }
    catch (\Throwable $e) {
      $this->logger->debug('UnifiedThemeResolver: hostname resolution failed: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Resuelve tenant ID por usuario autenticado.
   *
   * @return int|null
   *   Tenant entity ID o NULL si el usuario no tiene tenant.
   */
  protected function resolveByUser(): ?int {
    if ($this->tenantContext === NULL) {
      return NULL;
    }

    try {
      return $this->tenantContext->getCurrentTenantId();
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Carga la TenantThemeConfig activa para un tenant.
   *
   * Busca por tenant_id + is_active=TRUE. Solo deberia haber una config
   * activa por tenant.
   *
   * @param int $tenantId
   *   ID del Tenant entity.
   *
   * @return object|null
   *   TenantThemeConfig entity o NULL si no existe.
   */
  protected function loadTenantThemeConfig(int $tenantId): ?object {
    try {
      if (!$this->entityTypeManager->hasDefinition('tenant_theme_config')) {
        return NULL;
      }

      $configs = $this->entityTypeManager
        ->getStorage('tenant_theme_config')
        ->loadByProperties([
          'tenant_id' => $tenantId,
          'is_active' => TRUE,
        ]);

      return !empty($configs) ? reset($configs) : NULL;
    }
    catch (\Throwable $e) {
      $this->logger->debug('UnifiedThemeResolver: failed to load TenantThemeConfig for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Construye el array de overrides aplicando cascada Nivel 4 -> Nivel 5.
   *
   * Nivel 4 (TenantThemeConfig) define la base visual del tenant:
   * - CTA (header_cta_enabled, header_cta_text, header_cta_url)
   * - Layout (header_variant, header_sticky, footer_variant)
   * - Footer (footer_copyright, social_*)
   * - Desactiva megamenu SaaS y powered_by para meta-sitios.
   *
   * Nivel 5 (SiteConfig) puede sobrescribir campos ESTRUCTURALES:
   * - Layout override (header_type, footer_type)
   * - Footer copyright override
   * - Auth visibility, ecosystem footer band
   * - CTA SOLO como fallback (si Nivel 4 no definio CTA)
   *
   * Navegacion (nav_items, footer columns) viene del meta_site context
   * (SitePageTree) y no de ninguna entity de tema.
   *
   * @param object|null $themeConfig
   *   TenantThemeConfig entity (Nivel 4) o NULL.
   * @param object|null $siteConfig
   *   SiteConfig entity (Nivel 5) o NULL.
   * @param array|null $metaSite
   *   Contexto de meta-sitio (nav_items, footer_items, tenant_name) o NULL.
   *
   * @return array
   *   Array key=>value con overrides para theme_settings.
   *   Claves posibles: enable_header_cta, header_cta_text, header_cta_url,
   *   header_layout, header_sticky, footer_layout, footer_copyright,
   *   footer_social_*, header_megamenu, footer_show_powered_by,
   *   navigation_items, footer_nav_col*_title, footer_nav_col*_links,
   *   header_show_auth, ecosystem_footer_enabled, ecosystem_footer_links.
   */
  protected function buildThemeOverrides(
    ?object $themeConfig,
    ?object $siteConfig,
    ?array $metaSite,
  ): array {
    $overrides = [];

    // --- Nivel 4: TenantThemeConfig ---
    if ($themeConfig !== NULL) {
      $this->applyTenantThemeOverrides($themeConfig, $overrides);
    }

    // --- Nivel 5: SiteConfig (override de meta-sitio) ---
    if ($siteConfig !== NULL) {
      $this->applySiteConfigOverrides($siteConfig, $overrides);
    }

    // --- Navegacion desde meta-sitio context ---
    if ($metaSite !== NULL) {
      $this->applyMetaSiteNavigation($metaSite, $siteConfig, $overrides);
    }

    return $overrides;
  }

  /**
   * Aplica overrides de TenantThemeConfig (Nivel 4) al array de overrides.
   *
   * @param object $themeConfig
   *   TenantThemeConfig entity.
   * @param array &$overrides
   *   Array de overrides a modificar.
   */
  protected function applyTenantThemeOverrides(object $themeConfig, array &$overrides): void {
    // Header CTA.
    $ctaEnabled = (bool) ($themeConfig->get('header_cta_enabled')->value ?? TRUE);
    $ctaText = $themeConfig->get('header_cta_text')->value ?? '';
    $ctaUrl = $themeConfig->get('header_cta_url')->value ?? '/registro';

    if ($ctaEnabled && !empty($ctaText)) {
      $overrides['enable_header_cta'] = TRUE;
      $overrides['header_cta_text'] = $ctaText;
      $overrides['header_cta_url'] = $ctaUrl;
    }

    // Header layout y sticky.
    $headerVariant = $themeConfig->get('header_variant')->value ?? '';
    if (!empty($headerVariant)) {
      $overrides['header_layout'] = $headerVariant;
    }
    $overrides['header_sticky'] = (bool) ($themeConfig->get('header_sticky')->value ?? TRUE);

    // Footer.
    $footerVariant = $themeConfig->get('footer_variant')->value ?? '';
    if (!empty($footerVariant)) {
      $overrides['footer_layout'] = $footerVariant;
    }
    $footerCopyright = $themeConfig->get('footer_copyright')->value ?? '';
    if (!empty($footerCopyright)) {
      $overrides['footer_copyright'] = str_replace(
        ['[year]', '{year}'],
        date('Y'),
        $footerCopyright
      );
    }

    // Redes sociales.
    foreach (['facebook', 'twitter', 'linkedin', 'instagram', 'youtube'] as $network) {
      $field = 'social_' . $network;
      if ($themeConfig->hasField($field)) {
        $value = $themeConfig->get($field)->value ?? '';
        if (!empty($value)) {
          $overrides['footer_social_' . $network] = $value;
        }
      }
    }

    // Desactivar megamenu SaaS y powered_by en meta-sitios de tenants.
    $overrides['header_megamenu'] = FALSE;
    $overrides['footer_show_powered_by'] = FALSE;
  }

  /**
   * Aplica overrides de SiteConfig (Nivel 5) al array de overrides.
   *
   * SiteConfig gana sobre TenantThemeConfig para campos ESTRUCTURALES
   * (layout, footer, auth visibility). Para campos VISUALES como CTA,
   * TenantThemeConfig (Nivel 4) es SSOT y SiteConfig solo actua como
   * fallback (SSOT-THEME-001).
   *
   * @param object $siteConfig
   *   SiteConfig entity.
   * @param array &$overrides
   *   Array de overrides a modificar.
   */
  protected function applySiteConfigOverrides(object $siteConfig, array &$overrides): void {
    // Resolver traduccion del SiteConfig para idioma actual.
    try {
      $currentLangcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
      if ($siteConfig->isTranslatable() && $siteConfig->hasTranslation($currentLangcode)) {
        $siteConfig = $siteConfig->getTranslation($currentLangcode);
      }
    }
    catch (\Throwable) {
      // Continuar con el idioma por defecto.
    }

    // CTA fallback: SiteConfig solo aplica si TenantThemeConfig NO definio CTA.
    // SSOT-THEME-001: CTA es asunto visual -> TenantThemeConfig (Nivel 4) es SSOT.
    // SiteConfig (Nivel 5) solo actua como fallback para tenants sin CTA propio.
    if (empty($overrides['header_cta_text']) && method_exists($siteConfig, 'getHeaderCtaText')) {
      $scCtaText = $siteConfig->getHeaderCtaText() ?? '';
      if (!empty($scCtaText)) {
        $overrides['enable_header_cta'] = TRUE;
        $overrides['header_cta_text'] = $scCtaText;
        $overrides['header_cta_url'] = $siteConfig->getHeaderCtaUrl() ?: '#';
      }
    }

    // Header layout override.
    if (method_exists($siteConfig, 'getHeaderType')) {
      $scHeaderType = $siteConfig->getHeaderType() ?? '';
      if (!empty($scHeaderType)) {
        $overrides['header_layout'] = $scHeaderType;
      }
    }

    // Header sticky override.
    if (method_exists($siteConfig, 'isHeaderSticky')) {
      $overrides['header_sticky'] = $siteConfig->isHeaderSticky();
    }

    // Footer layout override.
    if (method_exists($siteConfig, 'getFooterType')) {
      $scFooterType = $siteConfig->getFooterType() ?? '';
      if (!empty($scFooterType)) {
        $overrides['footer_layout'] = $scFooterType;
      }
    }

    // Footer copyright override.
    if (method_exists($siteConfig, 'getFooterCopyright')) {
      $scFooterCopyright = $siteConfig->getFooterCopyright() ?? '';
      if (!empty($scFooterCopyright)) {
        $overrides['footer_copyright'] = str_replace(
          ['[year]', '{year}'],
          date('Y'),
          $scFooterCopyright
        );
      }
    }

    // Auth visibility (ocultar login/register en sitios brochure).
    if (method_exists($siteConfig, 'isHeaderShowAuth')) {
      $overrides['header_show_auth'] = $siteConfig->isHeaderShowAuth();
    }

    // Ecosystem footer band (navegacion transversal entre dominios).
    if (method_exists($siteConfig, 'isEcosystemFooterEnabled')) {
      $overrides['ecosystem_footer_enabled'] = $siteConfig->isEcosystemFooterEnabled();
      if ($overrides['ecosystem_footer_enabled'] && method_exists($siteConfig, 'getEcosystemFooterLinks')) {
        $overrides['ecosystem_footer_links'] = $siteConfig->getEcosystemFooterLinks();
      }
    }
    else {
      $overrides['ecosystem_footer_enabled'] = FALSE;
    }
  }

  /**
   * Aplica navegacion desde el contexto del meta-sitio a los overrides.
   *
   * La navegacion (nav_items) y footer columns vienen del SitePageTree
   * resuelto por MetaSiteResolverService, no de las entities de tema.
   *
   * Las columnas del footer se distribuyen automaticamente:
   * - Col 1: Primera mitad de nav_items
   * - Col 2: Segunda mitad de nav_items
   * - Col 3: Items marcados show_in_footer (legales)
   *
   * Los titulos de columna pueden configurarse desde SiteConfig.
   *
   * @param array $metaSite
   *   Contexto del meta-sitio (nav_items, footer_items, tenant_name).
   * @param object|null $siteConfig
   *   SiteConfig entity para titulos de columna, o NULL.
   * @param array &$overrides
   *   Array de overrides a modificar.
   */
  protected function applyMetaSiteNavigation(array $metaSite, ?object $siteConfig, array &$overrides): void {
    // Override items de navegacion del header (formato "Texto|URL" por linea).
    if (!empty($metaSite['nav_items_formatted'])) {
      $overrides['navigation_items'] = implode("\n", $metaSite['nav_items_formatted']);
    }

    // Footer columns desde nav_items y footer_items.
    $navItems = $metaSite['nav_items'] ?? [];
    $footerLegalItems = $metaSite['footer_items'] ?? [];

    if (empty($navItems) && empty($footerLegalItems)) {
      return;
    }

    $splitPoint = (int) ceil(count($navItems) / 2);

    // Col 1: Primeros items de nav (mitad superior).
    $col1Items = array_slice($navItems, 0, $splitPoint);
    if (!empty($col1Items)) {
      $col1Links = array_map(
        fn(array $i): string => $i['title'] . '|' . $i['url'],
        $col1Items
      );
      $col1Title = '';
      if ($siteConfig !== NULL && $siteConfig->hasField('footer_col1_title')) {
        $col1Title = $siteConfig->get('footer_col1_title')->value ?? '';
      }
      $overrides['footer_nav_col1_title'] = $col1Title ?: ($metaSite['tenant_name'] ?? '');
      $overrides['footer_nav_col1_links'] = implode("\n", $col1Links);
    }

    // Col 2: Restantes items de nav (mitad inferior).
    $col2Items = array_slice($navItems, $splitPoint);
    if (!empty($col2Items)) {
      $col2Links = array_map(
        fn(array $i): string => $i['title'] . '|' . $i['url'],
        $col2Items
      );
      $col2Title = '';
      if ($siteConfig !== NULL && $siteConfig->hasField('footer_col2_title')) {
        $col2Title = $siteConfig->get('footer_col2_title')->value ?? '';
      }
      $overrides['footer_nav_col2_title'] = $col2Title ?: 'Empresa';
      $overrides['footer_nav_col2_links'] = implode("\n", $col2Links);
    }

    // Col 3: Items marcados show_in_footer (legales).
    if (!empty($footerLegalItems)) {
      $col3Links = array_map(
        fn(array $i): string => $i['title'] . '|' . $i['url'],
        $footerLegalItems
      );
      $col3Title = '';
      if ($siteConfig !== NULL && $siteConfig->hasField('footer_col3_title')) {
        $col3Title = $siteConfig->get('footer_col3_title')->value ?? '';
      }
      $overrides['footer_nav_col3_title'] = $col3Title ?: 'Legal';
      $overrides['footer_nav_col3_links'] = implode("\n", $col3Links);
    }
  }

}
