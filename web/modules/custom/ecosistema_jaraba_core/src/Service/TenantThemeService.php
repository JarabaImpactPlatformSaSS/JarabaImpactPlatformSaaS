<?php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * BE-03: Servicio de gestión de temas por tenant.
 *
 * Refactorizado para delegar la resolución de tokens a StylePresetService,
 * manteniendo compatibilidad con la API existente.
 *
 * CASCADA DE RESOLUCIÓN:
 * 1. DesignTokenConfig entities (via StylePresetService) — 4 niveles
 * 2. Tenant.getThemeOverrides() — legacy, compatibilidad
 * 3. Platform defaults
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\StylePresetService
 */
class TenantThemeService
{

    public function __construct(
        protected TenantManager $tenantManager,
        protected StylePresetService $stylePreset,
    ) {
    }

    /**
     * Obtiene la configuración visual para el tenant actual.
     *
     * Resolución:
     * 1. StylePresetService (DesignTokenConfig entities)
     * 2. Tenant-specific overrides (legacy)
     * 3. Platform defaults
     *
     * @return array
     *   Array con claves color_primary, color_secondary, font_family.
     */
    public function getCurrentThemeSettings(): array
    {
        $tenant = $this->tenantManager->getCurrentTenant();

        if ($tenant) {
            return $this->getThemeSettingsForTenant($tenant);
        }

        return $this->getDefaultThemeSettings();
    }

    /**
     * Obtiene la configuración visual para un tenant específico.
     *
     * Prioridad: DesignTokenConfig → Tenant overrides → defaults.
     */
    public function getThemeSettingsForTenant(TenantInterface $tenant): array
    {
        // Intentar resolver desde DesignTokenConfig (nueva cascada).
        $tokens = $this->stylePreset->resolveTokensForTenant($tenant);

        if (!empty($tokens['colors'])) {
            return [
                'color_primary' => $tokens['colors']['primary'] ?? '#FF8C42',
                'color_secondary' => $tokens['colors']['secondary'] ?? '#2D3436',
                'font_family' => $tokens['typography']['family-heading'] ?? 'Inter',
            ];
        }

        // Fallback a legacy Tenant overrides.
        $overrides = $tenant->getThemeOverrides();
        if (!empty($overrides)) {
            return $overrides;
        }

        // Fallback a vertical defaults (legacy).
        $vertical = $tenant->getVertical();
        if ($vertical) {
            return $vertical->getThemeSettings();
        }

        return $this->getDefaultThemeSettings();
    }

    /**
     * Devuelve las configuraciones por defecto de la plataforma.
     *
     * Estos valores coinciden con platform_defaults DesignTokenConfig.
     */
    public function getDefaultThemeSettings(): array
    {
        return [
            'color_primary' => '#FF8C42',
            'color_secondary' => '#2D3436',
            'font_family' => 'Inter',
        ];
    }

}
