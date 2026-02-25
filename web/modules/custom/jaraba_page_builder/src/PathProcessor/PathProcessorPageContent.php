<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_site_builder\Service\MetaSiteResolverService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Procesa URLs amigables del Page Builder resolviendo path_alias → /page/{id}.
 *
 * PROPÓSITO (HAL-01):
 * El campo path_alias de PageContent almacena slugs como "/jarabaimpact",
 * "/plataforma", etc. pero estos nunca se registraban en el sistema de
 * routing de Drupal. Este PathProcessor intercepta peticiones entrantes
 * y las reescribe a la ruta canónica /page/{id} si coinciden con un
 * path_alias de una PageContent publicada.
 *
 * INTEGRACIÓN:
 * Se registra como servicio taggeado con 'path_processor_inbound'
 * en jaraba_page_builder.services.yml con prioridad 200 (antes que
 * path_alias de core que tiene prioridad 100).
 *
 * RENDIMIENTO:
 * Usa una caché estática por request para evitar queries repetidas.
 * Solo ejecuta query si el path no coincide con rutas conocidas del sistema.
 *
 * TENANT-ISOLATION: Filtra por tenant_id del usuario actual para evitar
 * que dos tenants con el mismo slug colisionen (el primero por ID ganaba).
 *
 * @package Drupal\jaraba_page_builder\PathProcessor
 */
class PathProcessorPageContent implements InboundPathProcessorInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Tenant context service (optional).
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null
   */
  protected ?TenantContextService $tenantContext;

  /**
   * Meta-site resolver service (optional).
   *
   * @var \Drupal\jaraba_site_builder\Service\MetaSiteResolverService|null
   */
  protected ?MetaSiteResolverService $metaSiteResolver;

  /**
   * Static cache de aliases resueltos en este request.
   *
   * @var array
   */
  protected static array $resolvedAliases = [];

  /**
   * Prefijos de sistema que NO deben resolverse como page_content alias.
   *
   * Evita queries innecesarias para rutas que sabemos que son del sistema.
   *
   * @var string[]
   */
  protected const SYSTEM_PREFIXES = [
    '/admin',
    '/user',
    '/node',
    '/api',
    '/media',
    '/batch',
    '/system',
    '/devel',
    '/modules',
    '/themes',
    '/sites',
    '/core',
    '/page-builder',
    '/my-pages',
    '/editor',
    '/session',
    '/contextual',
    '/quickedit',
    '/entity_reference_autocomplete',
    '/taxonomy',
    '/search',
    '/filter',
    '/file',
    '/ajax',
  ];

  /**
   * Constructs a PathProcessorPageContent.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null $tenant_context
   *   The tenant context service (optional).
   * @param \Drupal\jaraba_site_builder\Service\MetaSiteResolverService|null $meta_site_resolver
   *   The meta-site resolver service (optional).
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    ?TenantContextService $tenant_context = NULL,
    ?MetaSiteResolverService $meta_site_resolver = NULL,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->tenantContext = $tenant_context;
    $this->metaSiteResolver = $meta_site_resolver;
  }

  /**
   * {@inheritdoc}
   *
   * Intercepta peticiones entrantes y reescribe path_alias de PageContent
   * a su ruta canónica /page/{id}.
   *
   * Ejemplo:
   *   /jarabaimpact → /page/56
   *   /plataforma → /page/66
   */
  public function processInbound($path, Request $request) {
    // 1. No procesar paths vacíos.
    if (empty($path)) {
      return $path;
    }

    // 1b. HOMEPAGE POR DOMINIO (H1): Si el path es la raíz "/", intentar
    // resolver la homepage del meta-sitio del tenant via hostname del request.
    if ($path === '/') {
      return $this->resolveHomepage($request) ?? $path;
    }

    // 2. Saltar rutas del sistema conocidas (rendimiento).
    $lowerPath = mb_strtolower($path);
    foreach (self::SYSTEM_PREFIXES as $prefix) {
      if (str_starts_with($lowerPath, $prefix)) {
        return $path;
      }
    }

    // 3. Saltar la ruta canónica de page_content (evitar loop).
    if (preg_match('#^/page/\d+#', $path)) {
      return $path;
    }

    // 4. Resolve tenant ID (as Group ID) for cache key isolation.
    // PRIORITY: Domain-based resolution via MetaSiteResolverService takes
    // precedence over user-based TenantContextService. This is critical for
    // multi-tenant admins who own multiple tenants — the domain they are
    // visiting determines which tenant's content to serve.
    $tenantId = NULL;
    if ($this->metaSiteResolver) {
      $metaSite = $this->metaSiteResolver->resolveFromRequest($request);
      if ($metaSite) {
        $tenantId = $metaSite['group_id'];
      }
    }
    // Fallback to user-based tenant context if no domain match.
    if ($tenantId === NULL && $this->tenantContext) {
      $tenant = $this->tenantContext->getCurrentTenant();
      if ($tenant && $tenant->hasField('group_id')) {
        $tenantId = (int) ($tenant->get('group_id')->target_id ?? 0) ?: NULL;
      }
    }
    $cacheKey = $path . ':' . ($tenantId ?? 'global');

    // 5. Buscar en caché estática (mismo request).
    if (isset(self::$resolvedAliases[$cacheKey])) {
      return self::$resolvedAliases[$cacheKey];
    }

    // 6. Buscar el path_alias en la tabla page_content_field_data.
    //    El path_alias se almacena con / al inicio (ej: "/jarabaimpact").
    try {
      $storage = $this->entityTypeManager->getStorage('page_content');

      // Buscar coincidencia exacta del path_alias.
      // El path ya viene con / al inicio desde Drupal.
      // NOTA: No filtramos por status aquí — el control de acceso se maneja
      // en el entity access handler (PageContentAccessControlHandler).
      // Esto permite que admins/autores accedan a borradores por URL amigable.
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('path_alias', $path)
        ->range(0, 1);

      // TENANT-ISOLATION: Filtrar por tenant_id para evitar colisiones
      // de path_alias entre tenants diferentes.
      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      // Filtrar por idioma actual si hay más de uno disponible.
      $currentLangcode = $this->languageManager->getCurrentLanguage()->getId();
      if (count($this->languageManager->getLanguages()) > 1) {
        $query->condition('langcode', [$currentLangcode, 'und'], 'IN');
      }

      $ids = $query->execute();

      if (!empty($ids)) {
        $id = reset($ids);
        $resolvedPath = '/page/' . $id;

        // Cachear para evitar queries repetidas en el mismo request.
        self::$resolvedAliases[$cacheKey] = $resolvedPath;

        return $resolvedPath;
      }
    }
    catch (\Exception $e) {
      // Log silencioso — no bloquear la petición por un error de BD.
      \Drupal::logger('jaraba_page_builder')->warning(
        'PathProcessor: Error resolviendo alias @path: @error',
        ['@path' => $path, '@error' => $e->getMessage()]
      );
    }

    // 7. No match: devolver el path original sin modificar.
    self::$resolvedAliases[$cacheKey] = $path;
    return $path;
  }

  /**
   * Resuelve la homepage de un meta-sitio a partir del hostname del request.
   *
   * Usa MetaSiteResolverService para buscar SiteConfig del tenant → homepage_id.
   * Retorna /page/{id} si hay homepage configurada, NULL si no hay meta-sitio.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   El request HTTP actual.
   *
   * @return string|null
   *   Ruta canónica /page/{id} o NULL si no aplica.
   */
  protected function resolveHomepage(Request $request): ?string {
    if (!$this->metaSiteResolver) {
      return NULL;
    }

    $hostname = $request->getHost();
    $cacheKey = 'homepage:' . $hostname;

    if (isset(self::$resolvedAliases[$cacheKey])) {
      $cached = self::$resolvedAliases[$cacheKey];
      return $cached === '/' ? NULL : $cached;
    }

    try {
      $metaSite = $this->metaSiteResolver->resolveFromRequest($request);
      if ($metaSite && $metaSite['site_config']) {
        $homepageId = $metaSite['site_config']->get('homepage_id')->target_id ?? NULL;
        if ($homepageId) {
          $resolvedPath = '/page/' . $homepageId;
          self::$resolvedAliases[$cacheKey] = $resolvedPath;
          return $resolvedPath;
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('jaraba_page_builder')->warning(
        'PathProcessor: Error resolviendo homepage para @host: @error',
        ['@host' => $hostname, '@error' => $e->getMessage()]
      );
    }

    // No hay meta-sitio: cachear y devolver NULL.
    self::$resolvedAliases[$cacheKey] = '/';
    return NULL;
  }

}
