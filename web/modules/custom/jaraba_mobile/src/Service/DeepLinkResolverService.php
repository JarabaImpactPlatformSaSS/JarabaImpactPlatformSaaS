<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Service;

use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for resolving and generating deep links.
 *
 * Handles conversion between custom URI schemes (e.g., jaraba://jobs/123),
 * Drupal routes, and universal links (HTTPS URLs). Supports both
 * Android App Links (.well-known/assetlinks.json) and iOS Universal Links
 * (apple-app-site-association).
 */
class DeepLinkResolverService {

  /**
   * Mapping of deep link path prefixes to Drupal routes.
   *
   * @var array<string, array{route: string, param: string}>
   */
  protected const ROUTE_MAP = [
    'jobs' => ['route' => 'jaraba_job_board.job_detail', 'param' => 'job_posting'],
    'orders' => ['route' => 'jaraba_agroconecta_core.order_detail', 'param' => 'order_agro'],
    'courses' => ['route' => 'jaraba_lms.course_detail', 'param' => 'course'],
    'products' => ['route' => 'jaraba_agroconecta_core.product_detail', 'param' => 'product_agro'],
    'notifications' => ['route' => 'jaraba_mobile.push_history', 'param' => ''],
    'profile' => ['route' => 'entity.user.canonical', 'param' => 'user'],
    'dashboard' => ['route' => 'ecosistema_jaraba_core.tenant_dashboard', 'param' => ''],
  ];

  /**
   * The deep link URI scheme.
   */
  protected const SCHEME = 'jaraba';

  /**
   * Constructs a DeepLinkResolverService.
   *
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   The tenant context service.
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected readonly TenantContextService $tenantContext,
    protected readonly RouteProviderInterface $routeProvider,
    protected readonly RequestStack $requestStack,
  ) {}

  /**
   * Parses a deep link URI and resolves it to a Drupal route.
   *
   * @param string $deepLink
   *   The deep link URI (e.g., 'jaraba://jobs/123').
   *
   * @return array
   *   Associative array with:
   *   - 'route' (string): The Drupal route name.
   *   - 'params' (array): Route parameters.
   *   - 'url' (string): The fully qualified HTTPS URL.
   *   - 'valid' (bool): Whether the deep link could be resolved.
   */
  public function resolve(string $deepLink): array {
    $parsed = $this->parseDeepLink($deepLink);

    if (empty($parsed['path'])) {
      return [
        'route' => '',
        'params' => [],
        'url' => $this->getBaseUrl(),
        'valid' => FALSE,
      ];
    }

    $segments = explode('/', trim($parsed['path'], '/'));
    $resource = $segments[0] ?? '';
    $id = $segments[1] ?? NULL;

    if (!isset(self::ROUTE_MAP[$resource])) {
      return [
        'route' => '',
        'params' => [],
        'url' => $this->getBaseUrl(),
        'valid' => FALSE,
      ];
    }

    $mapping = self::ROUTE_MAP[$resource];
    $params = [];
    if (!empty($mapping['param']) && $id !== NULL) {
      $params[$mapping['param']] = $id;
    }

    // Build the web URL.
    $webPath = '/' . $resource;
    if ($id !== NULL) {
      $webPath .= '/' . $id;
    }

    return [
      'route' => $mapping['route'],
      'params' => $params,
      'url' => $this->getBaseUrl() . $webPath,
      'valid' => TRUE,
    ];
  }

  /**
   * Generates a deep link URI from a Drupal route name and parameters.
   *
   * @param string $routeName
   *   The Drupal route name.
   * @param array $params
   *   The route parameters.
   *
   * @return string
   *   The deep link URI (e.g., 'jaraba://jobs/123').
   */
  public function generateLink(string $routeName, array $params = []): string {
    // Reverse lookup: find the resource key from route name.
    foreach (self::ROUTE_MAP as $resource => $mapping) {
      if ($mapping['route'] === $routeName) {
        $id = '';
        if (!empty($mapping['param']) && isset($params[$mapping['param']])) {
          $id = '/' . $params[$mapping['param']];
        }
        return self::SCHEME . '://' . $resource . $id;
      }
    }

    // Fallback: use route name as path.
    $path = str_replace('.', '/', $routeName);
    return self::SCHEME . '://' . $path;
  }

  /**
   * Converts a deep link to a universal link (HTTPS URL).
   *
   * Universal links are standard HTTPS URLs that can be intercepted
   * by native apps via .well-known/assetlinks.json (Android) or
   * apple-app-site-association (iOS).
   *
   * @param string $deepLink
   *   The deep link URI (e.g., 'jaraba://jobs/123').
   *
   * @return string
   *   The universal link (HTTPS URL).
   */
  public function getUniversalLink(string $deepLink): string {
    $resolved = $this->resolve($deepLink);
    return $resolved['url'];
  }

  /**
   * Parses a deep link URI into components.
   *
   * @param string $deepLink
   *   The deep link URI.
   *
   * @return array
   *   Associative array with 'scheme', 'host', 'path', 'query'.
   */
  protected function parseDeepLink(string $deepLink): array {
    // Handle jaraba://path format.
    $parts = parse_url($deepLink);

    return [
      'scheme' => $parts['scheme'] ?? self::SCHEME,
      'host' => $parts['host'] ?? '',
      'path' => ($parts['host'] ?? '') . ($parts['path'] ?? ''),
      'query' => $parts['query'] ?? '',
    ];
  }

  /**
   * Gets the base URL for constructing universal links.
   *
   * Uses configured deep_link_base_url, falls back to current request host.
   *
   * @return string
   *   The base URL (e.g., 'https://app.pepejaraba.com').
   */
  protected function getBaseUrl(): string {
    $config = \Drupal::config('jaraba_mobile.settings');
    $baseUrl = $config->get('deep_link_base_url');

    if (!empty($baseUrl)) {
      return rtrim($baseUrl, '/');
    }

    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      return $request->getSchemeAndHttpHost();
    }

    return 'https://localhost';
  }

}
