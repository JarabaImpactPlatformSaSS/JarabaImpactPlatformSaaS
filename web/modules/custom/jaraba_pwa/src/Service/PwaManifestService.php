<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Dynamic PWA manifest generation service.
 *
 * Generates a W3C Web App Manifest (manifest.json) dynamically
 * per tenant, incorporating the tenant's theme configuration
 * (colors, name, icons) from TenantThemeConfig entities.
 *
 * References:
 * - W3C Web App Manifest: https://www.w3.org/TR/appmanifest/
 * - MDN: https://developer.mozilla.org/en-US/docs/Web/Manifest
 */
class PwaManifestService {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Generates a complete manifest for the given tenant.
   *
   * @param int|null $tenantId
   *   The tenant (group) ID. If NULL, returns platform defaults.
   *
   * @return array
   *   A manifest structure suitable for JSON encoding, containing
   *   name, short_name, icons, theme_color, background_color, etc.
   */
  public function generateManifest(?int $tenantId = NULL): array {
    try {
      $config = $this->configFactory->get('jaraba_pwa.settings');

      // Platform defaults.
      $manifest = [
        'name' => $config->get('app_name') ?? 'Jaraba Impact Platform',
        'short_name' => $config->get('app_short_name') ?? 'Jaraba',
        'description' => $config->get('app_description') ?? 'Plataforma de impacto social y ecosistemas de negocio.',
        'start_url' => '/?source=pwa',
        'display' => 'standalone',
        'orientation' => 'any',
        'theme_color' => '#1a73e8',
        'background_color' => '#ffffff',
        'lang' => 'es',
        'dir' => 'ltr',
        'categories' => ['business', 'productivity'],
        'icons' => $this->getDefaultIcons(),
        'scope' => '/',
        'prefer_related_applications' => FALSE,
      ];

      // Override with tenant-specific settings if available.
      if ($tenantId !== NULL) {
        $manifest = $this->applyTenantOverrides($manifest, $tenantId);
      }

      return $manifest;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to generate PWA manifest: @error', [
        '@error' => $e->getMessage(),
      ]);

      // Return minimal fallback manifest.
      return [
        'name' => 'Jaraba Impact Platform',
        'short_name' => 'Jaraba',
        'start_url' => '/',
        'display' => 'standalone',
        'theme_color' => '#1a73e8',
        'background_color' => '#ffffff',
        'icons' => $this->getDefaultIcons(),
      ];
    }
  }

  /**
   * Returns the default icon set for the manifest.
   *
   * @return array
   *   Array of icon objects with src, sizes, type, and purpose.
   */
  protected function getDefaultIcons(): array {
    return [
      [
        'src' => '/themes/custom/ecosistema_jaraba_theme/images/icons/icon-72x72.png',
        'sizes' => '72x72',
        'type' => 'image/png',
      ],
      [
        'src' => '/themes/custom/ecosistema_jaraba_theme/images/icons/icon-96x96.png',
        'sizes' => '96x96',
        'type' => 'image/png',
      ],
      [
        'src' => '/themes/custom/ecosistema_jaraba_theme/images/icons/icon-128x128.png',
        'sizes' => '128x128',
        'type' => 'image/png',
      ],
      [
        'src' => '/themes/custom/ecosistema_jaraba_theme/images/icons/icon-144x144.png',
        'sizes' => '144x144',
        'type' => 'image/png',
      ],
      [
        'src' => '/themes/custom/ecosistema_jaraba_theme/images/icons/icon-192x192.png',
        'sizes' => '192x192',
        'type' => 'image/png',
        'purpose' => 'any maskable',
      ],
      [
        'src' => '/themes/custom/ecosistema_jaraba_theme/images/icons/icon-512x512.png',
        'sizes' => '512x512',
        'type' => 'image/png',
        'purpose' => 'any maskable',
      ],
    ];
  }

  /**
   * Applies tenant-specific overrides to the manifest.
   *
   * @param array $manifest
   *   The base manifest array.
   * @param int $tenantId
   *   The tenant (group) ID.
   *
   * @return array
   *   The manifest with tenant overrides applied.
   */
  protected function applyTenantOverrides(array $manifest, int $tenantId): array {
    try {
      // Try to load tenant theme configuration.
      $themeStorage = $this->entityTypeManager->getStorage('tenant_theme_config');
      $themeConfigs = $themeStorage->loadByProperties([
        'tenant_id' => $tenantId,
      ]);

      if (empty($themeConfigs)) {
        return $manifest;
      }

      $themeConfig = reset($themeConfigs);

      // Override name from group entity.
      $groupStorage = $this->entityTypeManager->getStorage('group');
      $group = $groupStorage->load($tenantId);
      if ($group) {
        $manifest['name'] = $group->label() . ' - Jaraba';
        $manifest['short_name'] = $group->label();
      }

      // Override colors from theme config.
      if ($themeConfig->hasField('primary_color') && $themeConfig->get('primary_color')->value) {
        $manifest['theme_color'] = $themeConfig->get('primary_color')->value;
      }

      if ($themeConfig->hasField('background_color') && $themeConfig->get('background_color')->value) {
        $manifest['background_color'] = $themeConfig->get('background_color')->value;
      }

      return $manifest;
    }
    catch (\Exception $e) {
      $this->logger->warning('Failed to apply tenant @id overrides to manifest: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return $manifest;
    }
  }

}
