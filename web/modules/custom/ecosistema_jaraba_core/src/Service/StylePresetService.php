<?php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ecosistema_jaraba_core\Entity\DesignTokenConfigInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * Servicio de resolución y aplicación de Design Tokens.
 *
 * PROPÓSITO:
 * Implementa la cascada de 4 niveles (Platform → Vertical → Plan → Tenant)
 * para resolver los tokens visuales que se aplican a cada contexto.
 * Genera CSS custom properties con namespace --ej-* para inyección en runtime.
 *
 * ARQUITECTURA:
 * - Lee entidades DesignTokenConfig filtradas por scope/vertical/plan/tenant.
 * - Merge en cascada: cada nivel sobrescribe al anterior.
 * - Genera string CSS con :root { --ej-*: value; }.
 *
 * @see \Drupal\ecosistema_jaraba_core\Entity\DesignTokenConfig
 * @see \Drupal\ecosistema_jaraba_core\Service\TenantThemeService
 */
class StylePresetService
{

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerChannelInterface $logger,
    ) {
    }

    /**
     * Resuelve los tokens aplicables para un contexto dado.
     *
     * Ejecuta la cascada: Platform → Vertical → Plan → Tenant.
     * Cada nivel sobrescribe los tokens del anterior.
     *
     * @param string|null $vertical_id
     *   Machine name de la vertical (ej: agroconecta).
     * @param string|null $plan_id
     *   Machine name del plan (ej: professional).
     * @param string|null $tenant_id
     *   ID del tenant.
     *
     * @return array
     *   Array con todos los tokens resueltos, agrupados por categoría:
     *   ['colors' => [...], 'typography' => [...], 'spacing' => [...],
     *    'effects' => [...], 'variants' => [...]].
     */
    public function resolveTokensForContext(
        ?string $vertical_id = NULL,
        ?string $plan_id = NULL,
        ?string $tenant_id = NULL,
    ): array {
        $resolved = [
            'colors' => [],
            'typography' => [],
            'spacing' => [],
            'effects' => [],
            'variants' => [],
        ];

        // Nivel 1: Platform defaults (scope = 'platform').
        $platformConfigs = $this->loadConfigsByScope('platform');
        foreach ($platformConfigs as $config) {
            $resolved = $this->mergeTokens($resolved, $config);
        }

        // Nivel 2: Vertical (scope = 'vertical' + vertical_id).
        if ($vertical_id) {
            $verticalConfigs = $this->loadConfigsByScope('vertical', $vertical_id);
            foreach ($verticalConfigs as $config) {
                $resolved = $this->mergeTokens($resolved, $config);
            }
        }

        // Nivel 3: Plan (scope = 'plan' + plan_id).
        if ($plan_id) {
            $planConfigs = $this->loadConfigsByScope('plan', $vertical_id, $plan_id);
            foreach ($planConfigs as $config) {
                $resolved = $this->mergeTokens($resolved, $config);
            }
        }

        // Nivel 4: Tenant (scope = 'tenant' + tenant_id).
        if ($tenant_id) {
            $tenantConfigs = $this->loadConfigsByScope('tenant', $vertical_id, $plan_id, $tenant_id);
            foreach ($tenantConfigs as $config) {
                $resolved = $this->mergeTokens($resolved, $config);
            }
        }

        return $resolved;
    }

    /**
     * Resuelve tokens para el tenant actual usando TenantInterface.
     *
     * Conveniencia: extrae vertical_id, plan_id y tenant_id del Tenant entity.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null $tenant
     *   El tenant actual, o NULL para defaults de plataforma.
     *
     * @return array
     *   Tokens resueltos.
     */
    public function resolveTokensForTenant(?TenantInterface $tenant = NULL): array
    {
        if (!$tenant) {
            return $this->resolveTokensForContext();
        }

        $vertical = $tenant->getVertical();
        $plan = $tenant->getSubscriptionPlan();

        return $this->resolveTokensForContext(
            $vertical ? $vertical->id() : NULL,
            $plan ? $plan->id() : NULL,
            (string) $tenant->id(),
        );
    }

    /**
     * Genera CSS inline con custom properties para inyección en <style>.
     *
     * @param array $tokens
     *   Tokens resueltos (output de resolveTokensForContext).
     *
     * @return string
     *   CSS válido con :root { --ej-*: value; }.
     */
    public function generateInlineCss(array $tokens): string
    {
        $properties = [];

        // Color tokens → --ej-color-{key}.
        foreach ($tokens['colors'] ?? [] as $key => $value) {
            $properties[] = "  --ej-color-{$key}: {$value};";
        }

        // Typography tokens → --ej-font-{key}.
        foreach ($tokens['typography'] ?? [] as $key => $value) {
            $properties[] = "  --ej-font-{$key}: {$value};";
        }

        // Spacing tokens → --ej-spacing-{key}.
        foreach ($tokens['spacing'] ?? [] as $key => $value) {
            $properties[] = "  --ej-spacing-{$key}: {$value};";
        }

        // Effect tokens → --ej-{key}.
        foreach ($tokens['effects'] ?? [] as $key => $value) {
            $properties[] = "  --ej-{$key}: {$value};";
        }

        if (empty($properties)) {
            return '';
        }

        // Usar html:root (specificity 0,0,1,1) en lugar de :root (0,0,1,0)
        // para garantizar que los tokens inyectados siempre ganen sobre
        // cualquier :root en CSS compilado (core, theme, critical CSS).
        return "html:root {\n" . implode("\n", $properties) . "\n}";
    }

    /**
     * Carga DesignTokenConfig entities por scope y filtros.
     *
     * @param string $scope
     *   El scope a buscar (platform, vertical, plan, tenant).
     * @param string|null $vertical_id
     *   Filtro por vertical_id.
     * @param string|null $plan_id
     *   Filtro por plan_id.
     * @param string|null $tenant_id
     *   Filtro por tenant_id.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\DesignTokenConfigInterface[]
     *   Configs encontradas, ordenadas por weight.
     */
    protected function loadConfigsByScope(
        string $scope,
        ?string $vertical_id = NULL,
        ?string $plan_id = NULL,
        ?string $tenant_id = NULL,
    ): array {
        try {
            $storage = $this->entityTypeManager->getStorage('design_token_config');
            $allConfigs = $storage->loadMultiple();

            $filtered = [];
            foreach ($allConfigs as $config) {
                if (!$config instanceof DesignTokenConfigInterface) {
                    continue;
                }
                if (!$config->status()) {
                    continue;
                }
                if ($config->getScope() !== $scope) {
                    continue;
                }

                // Filtrar por vertical/plan/tenant según scope.
                if ($scope !== 'platform' && $vertical_id && $config->getVerticalId() !== $vertical_id) {
                    continue;
                }
                if (in_array($scope, ['plan', 'tenant']) && $plan_id && $config->getPlanId() !== $plan_id) {
                    continue;
                }
                if ($scope === 'tenant' && $tenant_id && $config->getTenantId() !== $tenant_id) {
                    continue;
                }

                $filtered[] = $config;
            }

            // Ordenar por weight.
            usort($filtered, fn($a, $b) => ($a->get('weight') ?? 0) <=> ($b->get('weight') ?? 0));

            return $filtered;
        } catch (\Exception $e) {
            $this->logger->error('Error cargando DesignTokenConfig de scope @scope: @message', [
                '@scope' => $scope,
                '@message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Merge tokens de un DesignTokenConfig sobre los tokens acumulados.
     *
     * Solo sobrescribe claves que tienen valor — permite herencia selectiva.
     *
     * @param array $accumulated
     *   Tokens acumulados hasta este punto.
     * @param \Drupal\ecosistema_jaraba_core\Entity\DesignTokenConfigInterface $config
     *   La configuración cuyos tokens se aplican.
     *
     * @return array
     *   Tokens actualizados.
     */
    protected function mergeTokens(array $accumulated, DesignTokenConfigInterface $config): array
    {
        $colorTokens = $config->getColorTokens();
        if (!empty($colorTokens)) {
            $accumulated['colors'] = array_merge($accumulated['colors'], $colorTokens);
        }

        $typoTokens = $config->getTypographyTokens();
        if (!empty($typoTokens)) {
            $accumulated['typography'] = array_merge($accumulated['typography'], $typoTokens);
        }

        $spacingTokens = $config->getSpacingTokens();
        if (!empty($spacingTokens)) {
            $accumulated['spacing'] = array_merge($accumulated['spacing'], $spacingTokens);
        }

        $effectTokens = $config->getEffectTokens();
        if (!empty($effectTokens)) {
            $accumulated['effects'] = array_merge($accumulated['effects'], $effectTokens);
        }

        $variants = $config->getComponentVariants();
        if (!empty($variants)) {
            $accumulated['variants'] = array_merge($accumulated['variants'], $variants);
        }

        return $accumulated;
    }

}
