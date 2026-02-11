<?php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para aplicar un StylePreset a un tenant.
 *
 * FLUJO:
 * 1. Recibe un style_preset_id y un tenant_id
 * 2. Carga el StylePreset y extrae todos sus tokens
 * 3. Busca si existe un DesignTokenConfig scope=tenant para ese tenant
 * 4. Si existe, actualiza sus tokens con los del preset; si no, crea uno nuevo
 * 5. Los tokens del preset se convierten en CSS custom properties via
 *    StylePresetService en el render pipeline
 *
 * ARQUITECTURA (Clean Separation):
 * - StylePreset: Plantilla inmutable (catálogo de diseños)
 * - DesignTokenConfig: Configuración activa del tenant (derivada del preset)
 * - PresetApplicatorService: Puente entre plantilla y configuración activa
 * - StylePresetService: Resuelve tokens cascada y genera CSS
 */
class PresetApplicatorService
{

    /**
     * Construye el servicio.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Aplica un StylePreset a un tenant.
     *
     * Crea o actualiza el DesignTokenConfig scope=tenant con los tokens
     * del preset seleccionado.
     *
     * @param string $presetId
     *   El ID del StylePreset a aplicar (ej: 'agro_gourmet').
     * @param string $tenantId
     *   El ID del tenant al que aplicar el preset.
     *
     * @return bool
     *   TRUE si se aplicó correctamente, FALSE en caso de error.
     */
    public function applyPresetToTenant(string $presetId, string $tenantId): bool
    {
        try {
            // 1. Cargar el StylePreset.
            /** @var \Drupal\ecosistema_jaraba_core\Entity\StylePresetInterface $preset */
            $preset = $this->entityTypeManager
                ->getStorage('style_preset')
                ->load($presetId);

            if (!$preset) {
                $this->logger->error('StylePreset @id no encontrado.', [
                    '@id' => $presetId,
                ]);
                return FALSE;
            }

            // 2. Buscar DesignTokenConfig existente para este tenant.
            $tokenConfig = $this->findTenantConfig($tenantId);

            // 3. Si no existe, crear uno nuevo.
            if (!$tokenConfig) {
                $tokenConfig = $this->createTenantConfig($tenantId, $preset);
            } else {
                // 4. Si existe, actualizar con tokens del preset.
                $this->updateConfigFromPreset($tokenConfig, $preset);
            }

            // 5. Guardar.
            $tokenConfig->save();

            $this->logger->notice('Preset @preset aplicado al tenant @tenant.', [
                '@preset' => $preset->label(),
                '@tenant' => $tenantId,
            ]);

            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error aplicando preset @preset a tenant @tenant: @error', [
                '@preset' => $presetId,
                '@tenant' => $tenantId,
                '@error' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Lista los presets disponibles, agrupados por vertical.
     *
     * @return array
     *   Array indexado por vertical con info de cada preset.
     */
    public function getPresetsGroupedByVertical(): array
    {
        $storage = $this->entityTypeManager->getStorage('style_preset');
        /** @var \Drupal\ecosistema_jaraba_core\Entity\StylePresetInterface[] $presets */
        $presets = $storage->loadMultiple();

        $grouped = [];
        foreach ($presets as $preset) {
            if (!$preset->status()) {
                continue;
            }
            $vertical = $preset->getVertical();
            $grouped[$vertical][] = [
                'id' => $preset->id(),
                'label' => $preset->label(),
                'description' => $preset->getDescription(),
                'sector' => $preset->getSector(),
                'mood' => $preset->getMood(),
                'colors' => $preset->getColorTokens(),
                'typography' => $preset->getTypographyTokens(),
                'preview_css' => $preset->generatePreviewCss(),
            ];
        }

        return $grouped;
    }

    /**
     * Obtiene los detalles completos de un preset para preview.
     *
     * @param string $presetId
     *   El ID del preset.
     *
     * @return array|null
     *   Array con todos los datos del preset, o NULL si no existe.
     */
    public function getPresetDetails(string $presetId): ?array
    {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\StylePresetInterface $preset */
        $preset = $this->entityTypeManager
            ->getStorage('style_preset')
            ->load($presetId);

        if (!$preset) {
            return NULL;
        }

        return [
            'id' => $preset->id(),
            'label' => $preset->label(),
            'description' => $preset->getDescription(),
            'vertical' => $preset->getVertical(),
            'sector' => $preset->getSector(),
            'mood' => $preset->getMood(),
            'target_audience' => $preset->getTargetAudience(),
            'color_tokens' => $preset->getColorTokens(),
            'typography_tokens' => $preset->getTypographyTokens(),
            'spacing_tokens' => $preset->getSpacingTokens(),
            'effect_tokens' => $preset->getEffectTokens(),
            'component_variants' => $preset->getComponentVariants(),
            'animation_config' => $preset->getAnimationConfig(),
            'content_guidelines' => $preset->getContentGuidelines(),
            'font_imports' => $preset->getFontImports(),
            'preview_css' => $preset->generatePreviewCss(),
        ];
    }

    /**
     * Busca un DesignTokenConfig scope=tenant para el tenant dado.
     *
     * @param string $tenantId
     *   ID del tenant.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\DesignTokenConfigInterface|null
     *   La configuración encontrada o NULL.
     */
    protected function findTenantConfig(string $tenantId)
    {
        $storage = $this->entityTypeManager->getStorage('design_token_config');
        $configs = $storage->loadByProperties([
            'scope' => 'tenant',
            'tenant_id' => $tenantId,
        ]);

        return $configs ? reset($configs) : NULL;
    }

    /**
     * Crea un nuevo DesignTokenConfig scope=tenant a partir de un preset.
     *
     * @param string $tenantId
     *   ID del tenant.
     * @param \Drupal\ecosistema_jaraba_core\Entity\StylePresetInterface $preset
     *   El preset a aplicar.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\DesignTokenConfigInterface
     *   La nueva configuración creada (no guardada).
     */
    protected function createTenantConfig(string $tenantId, $preset)
    {
        $storage = $this->entityTypeManager->getStorage('design_token_config');
        $machineId = 'tenant_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($tenantId));

        /** @var \Drupal\ecosistema_jaraba_core\Entity\DesignTokenConfigInterface $config */
        $config = $storage->create([
            'id' => $machineId,
            'label' => 'Tenant ' . $tenantId . ' (via ' . $preset->label() . ')',
            'description' => 'Configuración generada automáticamente desde preset: ' . $preset->label(),
            'scope' => 'tenant',
            'tenant_id' => $tenantId,
            'preset_id' => $preset->id(),
            'weight' => 100,
            'status' => TRUE,
        ]);

        $this->updateConfigFromPreset($config, $preset);

        return $config;
    }

    /**
     * Actualiza un DesignTokenConfig existente con tokens de un preset.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\DesignTokenConfigInterface $config
     *   La configuración a actualizar.
     * @param \Drupal\ecosistema_jaraba_core\Entity\StylePresetInterface $preset
     *   El preset origen.
     */
    protected function updateConfigFromPreset($config, $preset): void
    {
        $config->setColorTokens($preset->getColorTokens());
        $config->setTypographyTokens($preset->getTypographyTokens());
        $config->setSpacingTokens($preset->getSpacingTokens());
        $config->setEffectTokens($preset->getEffectTokens());
        $config->setComponentVariants($preset->getComponentVariants());

        // Actualizar referencia al preset.
        $config->set('preset_id', $preset->id());
        $config->set('label', 'Tenant ' . $config->get('tenant_id') . ' (via ' . $preset->label() . ')');
    }

}
