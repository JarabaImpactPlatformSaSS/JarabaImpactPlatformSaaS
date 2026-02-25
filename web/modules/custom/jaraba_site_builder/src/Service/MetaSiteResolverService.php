<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_page_builder\Entity\PageContent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resuelve el contexto de meta-sitio (SiteConfig, navegación, tenant) a partir
 * de un PageContent, un hostname o un Request.
 *
 * Servicio compartido consumido por:
 * - PathProcessorPageContent (Fase 1: homepage por dominio)
 * - ecosistema_jaraba_theme preprocess (Fase 2: header/footer/nav)
 * - SEO injection hooks (Fase 4: og:site_name, Schema.org)
 *
 * RESOLUCIÓN DE DOMINIO (3 estrategias):
 * 1. Domain Access entity → hostname exacto → Tenant.domain_id match
 * 2. Tenant.domain field → hostname exacto
 * 3. Subdomain prefix → "pepejaraba.jaraba-saas.lndo.site" → buscar Tenant
 *    cuyo domain empiece por "pepejaraba" (resuelve H2 sin crear entidades extra)
 *
 * @package Drupal\jaraba_site_builder\Service
 */
class MetaSiteResolverService {

  /**
   * Cache estática por request para evitar queries repetidas.
   *
   * @var array
   */
  protected static array $cache = [];

  /**
   * Constructs a MetaSiteResolverService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Resuelve el contexto de meta-sitio a partir de un PageContent.
   *
   * @param \Drupal\jaraba_page_builder\Entity\PageContent $page
   *   La entidad PageContent.
   *
   * @return array|null
   *   Array con site_config, nav_items, footer_items, nav_items_formatted,
   *   tenant_name, group_id. NULL si no hay meta-sitio configurado.
   */
  public function resolveFromPageContent(PageContent $page): ?array {
    $tenantId = $page->get('tenant_id')->target_id ?? NULL;
    if (!$tenantId) {
      return NULL;
    }

    $cacheKey = 'tenant:' . $tenantId;
    if (isset(self::$cache[$cacheKey])) {
      return self::$cache[$cacheKey];
    }

    $result = $this->buildMetaSiteContext((int) $tenantId);
    self::$cache[$cacheKey] = $result;
    return $result;
  }

  /**
   * Resuelve el contexto de meta-sitio a partir de un Request HTTP.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   El request HTTP actual.
   *
   * @return array|null
   *   Array de contexto del meta-sitio o NULL.
   */
  public function resolveFromRequest(Request $request): ?array {
    $hostname = $request->getHost();
    return $this->resolveFromDomain($hostname);
  }

  /**
   * Resuelve el contexto de meta-sitio a partir de un hostname.
   *
   * Usa 3 estrategias de resolución en orden de prioridad:
   * 1. Domain Access entity (hostname exacto)
   * 2. Tenant.domain field (hostname exacto)
   * 3. Subdomain prefix (primer segmento del hostname)
   *
   * @param string $hostname
   *   Hostname completo (ej: "pepejaraba.com" o "pepejaraba.jaraba-saas.lndo.site").
   *
   * @return array|null
   *   Array de contexto del meta-sitio o NULL.
   */
  public function resolveFromDomain(string $hostname): ?array {
    $cacheKey = 'domain:' . $hostname;
    if (isset(self::$cache[$cacheKey])) {
      return self::$cache[$cacheKey];
    }

    $groupId = $this->resolveGroupIdFromHostname($hostname);
    if (!$groupId) {
      self::$cache[$cacheKey] = NULL;
      return NULL;
    }

    $result = $this->buildMetaSiteContext($groupId);
    self::$cache[$cacheKey] = $result;
    return $result;
  }

  /**
   * Resuelve el Group ID a partir de un hostname usando 3 estrategias.
   *
   * @param string $hostname
   *   El hostname a resolver.
   *
   * @return int|null
   *   El Group ID del tenant, o NULL si no se encuentra.
   */
  protected function resolveGroupIdFromHostname(string $hostname): ?int {
    // Estrategia 1: Domain Access entity → Tenant.domain_id.
    // Funciona cuando el hostname coincide exactamente con un Domain Access
    // entity y hay un Tenant que lo referencia en domain_id.
    try {
      if ($this->entityTypeManager->hasDefinition('domain')) {
        $domains = $this->entityTypeManager->getStorage('domain')
          ->loadByProperties(['hostname' => $hostname]);

        if (!empty($domains)) {
          $domain = reset($domains);
          $tenants = $this->entityTypeManager->getStorage('tenant')
            ->loadByProperties(['domain_id' => $domain->id()]);

          if (!empty($tenants)) {
            $tenant = reset($tenants);
            $groupId = $tenant->get('group_id')->target_id ?? NULL;
            if ($groupId) {
              return (int) $groupId;
            }
          }
        }
      }
    }
    catch (\Exception $e) {
      // Domain Access module puede no estar instalado.
    }

    // Estrategia 2: Match directo en Tenant.domain field.
    // Funciona para dominios de producción almacenados literalmente.
    try {
      $tenants = $this->entityTypeManager->getStorage('tenant')
        ->loadByProperties(['domain' => $hostname]);

      if (!empty($tenants)) {
        $tenant = reset($tenants);
        $groupId = $tenant->get('group_id')->target_id ?? NULL;
        if ($groupId) {
          return (int) $groupId;
        }
      }
    }
    catch (\Exception $e) {
      // Fallthrough.
    }

    // Estrategia 3: Subdomain prefix match.
    // Extrae el primer segmento del hostname y busca Tenants cuyo domain
    // empiece con ese prefijo. Resuelve H2: "pepejaraba.jaraba-saas.lndo.site"
    // matchea Tenant con domain "pepejaraba.com" via prefijo "pepejaraba".
    $parts = explode('.', $hostname);
    if (count($parts) >= 3) {
      $prefix = $parts[0];
      // Evitar false positives con prefijos genéricos.
      if (mb_strlen($prefix) >= 3 && $prefix !== 'www' && $prefix !== 'dev' && $prefix !== 'api') {
        try {
          $query = $this->entityTypeManager->getStorage('tenant')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('domain', $prefix, 'STARTS_WITH')
            ->range(0, 1);
          $ids = $query->execute();

          if (!empty($ids)) {
            $tenant = $this->entityTypeManager->getStorage('tenant')
              ->load(reset($ids));
            if ($tenant) {
              $groupId = $tenant->get('group_id')->target_id ?? NULL;
              if ($groupId) {
                return (int) $groupId;
              }
            }
          }
        }
        catch (\Exception $e) {
          // Fallthrough.
        }
      }
    }

    return NULL;
  }

  /**
   * Construye el array completo de contexto del meta-sitio dado un Group ID.
   *
   * Carga SiteConfig y SitePageTree del tenant, construye arrays de navegación
   * y footer, y formatea items para el sistema de templates del tema.
   *
   * @param int $groupId
   *   El Group ID del tenant.
   *
   * @return array|null
   *   Array con:
   *   - site_config: SiteConfig entity
   *   - nav_items: array de items de navegación [{title, url, weight, icon, ...}]
   *   - footer_items: array de items de footer [{title, url, weight}]
   *   - nav_items_formatted: array de strings "Título|URL" para el tema
   *   - tenant_name: nombre del sitio
   *   - group_id: Group ID
   *   O NULL si no hay SiteConfig para este tenant.
   */
  protected function buildMetaSiteContext(int $groupId): ?array {
    try {
      // Cargar SiteConfig del tenant.
      $configs = $this->entityTypeManager->getStorage('site_config')
        ->loadByProperties(['tenant_id' => $groupId]);

      if (empty($configs)) {
        return NULL;
      }

      $siteConfig = reset($configs);

      // Cargar SitePageTree items publicados.
      $treeItems = $this->entityTypeManager->getStorage('site_page_tree')
        ->loadByProperties([
          'tenant_id' => $groupId,
          'status' => 'published',
        ]);

      // Construir arrays de navegación y footer.
      $navItems = [];
      $footerItems = [];

      foreach ($treeItems as $item) {
        $page = $item->getPage();
        if (!$page) {
          continue;
        }

        $title = $item->getNavTitle();
        $pathAlias = $page->get('path_alias')->value ?? '/page/' . $page->id();
        $weight = (int) ($item->get('weight')->value ?? 0);

        if ($item->showInNavigation()) {
          $navItems[] = [
            'title' => $title,
            'url' => $item->get('nav_external_url')->value ?: $pathAlias,
            'weight' => $weight,
            'icon' => $item->get('nav_icon')->value ?? '',
            'highlight' => (bool) ($item->get('nav_highlight')->value ?? FALSE),
          ];
        }

        if ((bool) ($item->get('show_in_footer')->value ?? FALSE)) {
          $footerItems[] = [
            'title' => $title,
            'url' => $pathAlias,
            'weight' => $weight,
          ];
        }
      }

      // Ordenar por peso.
      usort($navItems, fn($a, $b) => $a['weight'] <=> $b['weight']);
      usort($footerItems, fn($a, $b) => $a['weight'] <=> $b['weight']);

      // Formatear para theme navigation_items ("Título|URL").
      $navFormatted = array_map(
        fn($item) => $item['title'] . '|' . $item['url'],
        $navItems
      );

      return [
        'site_config' => $siteConfig,
        'nav_items' => $navItems,
        'footer_items' => $footerItems,
        'nav_items_formatted' => $navFormatted,
        'tenant_name' => $siteConfig->getSiteName(),
        'group_id' => $groupId,
      ];
    }
    catch (\Exception $e) {
      $this->logger->warning(
        'MetaSiteResolver: Error construyendo contexto para group @gid: @error',
        ['@gid' => $groupId, '@error' => $e->getMessage()]
      );
      return NULL;
    }
  }

}
