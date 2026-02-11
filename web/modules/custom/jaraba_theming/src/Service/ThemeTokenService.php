<?php

declare(strict_types=1);

namespace Drupal\jaraba_theming\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_theming\Entity\TenantThemeConfig;

/**
 * Servicio para gestionar Design Tokens y configuración de tema.
 *
 * Implementa la cascada: Plataforma → Vertical → Tenant
 */
class ThemeTokenService
{

    /**
     * El gestor de tipos de entidad.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El usuario actual.
     */
    protected AccountInterface $currentUser;

    /**
     * Cache de configuración activa.
     */
    protected ?TenantThemeConfig $activeConfig = NULL;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountInterface $current_user
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
    }

    /**
     * Obtiene la configuración de tema activa para el contexto actual.
     *
     * @param int|null $tenant_id
     *   ID del tenant (opcional, se detecta automáticamente).
     *
     * @return \Drupal\jaraba_theming\Entity\TenantThemeConfig|null
     *   La configuración activa o NULL.
     */
    public function getActiveConfig(?int $tenant_id = NULL): ?TenantThemeConfig
    {
        if ($this->activeConfig !== NULL) {
            return $this->activeConfig;
        }

        // Buscar configuración por tenant.
        if ($tenant_id) {
            $configs = $this->entityTypeManager
                ->getStorage('tenant_theme_config')
                ->loadByProperties([
                    'tenant_id' => $tenant_id,
                    'is_active' => TRUE,
                ]);

            if (!empty($configs)) {
                $this->activeConfig = reset($configs);
                return $this->activeConfig;
            }
        }

        // Fallback: configuración de plataforma.
        $configs = $this->entityTypeManager
            ->getStorage('tenant_theme_config')
            ->loadByProperties([
                'vertical' => 'platform',
                'is_active' => TRUE,
            ]);

        if (!empty($configs)) {
            $this->activeConfig = reset($configs);
        }

        return $this->activeConfig;
    }

    /**
     * Genera el CSS de tokens para inyectar en el HTML.
     *
     * @param int|null $tenant_id
     *   ID del tenant.
     *
     * @return string
     *   CSS con variables :root.
     */
    public function generateCss(?int $tenant_id = NULL): string
    {
        $config = $this->getActiveConfig($tenant_id);

        if (!$config) {
            return $this->getDefaultCss();
        }

        return $config->generateCssVariables();
    }

    /**
     * Obtiene los tokens de vertical base.
     *
     * @param string $vertical
     *   Nombre del vertical.
     *
     * @return array
     *   Array de tokens con valores por defecto del vertical.
     */
    public function getVerticalTokens(string $vertical): array
    {
        $defaults = [
            'platform' => [
                'color_primary' => '#FF8C42',
                'color_secondary' => '#00A9A5',
                'color_accent' => '#233D63',
            ],
            'empleabilidad' => [
                'color_primary' => '#2563EB',
                'color_secondary' => '#00A9A5',
                'color_accent' => '#F59E0B',
            ],
            'emprendimiento' => [
                'color_primary' => '#8B5CF6',
                'color_secondary' => '#EC4899',
                'color_accent' => '#10B981',
            ],
            'agroconecta' => [
                'color_primary' => '#16A34A',
                'color_secondary' => '#CA8A04',
                'color_accent' => '#7C3AED',
            ],
            'comercio' => [
                'color_primary' => '#FF8C42',
                'color_secondary' => '#3B82F6',
                'color_accent' => '#EF4444',
            ],
            'servicios' => [
                'color_primary' => '#0891B2',
                'color_secondary' => '#6366F1',
                'color_accent' => '#F97316',
            ],
        ];

        return $defaults[$vertical] ?? $defaults['platform'];
    }

    /**
     * CSS por defecto cuando no hay configuración.
     */
    protected function getDefaultCss(): string
    {
        return <<<CSS
:root {
  --ej-color-primary: #FF8C42;
  --ej-color-secondary: #00A9A5;
  --ej-color-accent: #233D63;
  --ej-color-dark: #1a1a2e;
  --ej-font-family-headings: 'Outfit', sans-serif;
  --ej-font-family-body: 'Inter', sans-serif;
  --ej-border-radius: 8px;
}
CSS;
    }

    /**
     * Obtiene la variante de header activa.
     *
     * @return string
     *   Nombre de la variante (classic, transparent, etc.).
     */
    public function getHeaderVariant(): string
    {
        $config = $this->getActiveConfig();
        return $config ? ($config->get('header_variant')->value ?? 'classic') : 'classic';
    }

    /**
     * Obtiene la variante de hero activa.
     *
     * @return string
     *   Nombre de la variante.
     */
    public function getHeroVariant(): string
    {
        $config = $this->getActiveConfig();
        return $config ? ($config->get('hero_variant')->value ?? 'split') : 'split';
    }

}
