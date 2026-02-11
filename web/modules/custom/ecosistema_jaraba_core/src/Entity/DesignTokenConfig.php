<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuración DesignTokenConfig.
 *
 * Almacena configuraciones de Design Tokens para la personalización
 * visual multi-tenant de la plataforma. Implementa la cascada de 4 niveles:
 * Platform → Vertical → Plan → Tenant.
 *
 * Cada instancia representa una capa de la cascada y contiene tokens
 * de color, tipografía, espaciado, efectos y variantes de componentes.
 * Los tokens usan el namespace `--ej-*` compatible con el sistema
 * de inyección CSS existente.
 *
 * @ConfigEntityType(
 *   id = "design_token_config",
 *   label = @Translation("Design Token Configuration"),
 *   label_collection = @Translation("Design Tokens"),
 *   label_singular = @Translation("configuración de design token"),
 *   label_plural = @Translation("configuraciones de design tokens"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\DesignTokenConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\DesignTokenConfigForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\DesignTokenConfigForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "design_token_config",
 *   admin_permission = "administer design tokens",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "scope",
 *     "vertical_id",
 *     "plan_id",
 *     "tenant_id",
 *     "preset_id",
 *     "color_tokens",
 *     "typography_tokens",
 *     "spacing_tokens",
 *     "effect_tokens",
 *     "component_variants",
 *     "weight",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/design-tokens",
 *     "add-form" = "/admin/structure/design-tokens/add",
 *     "edit-form" = "/admin/structure/design-tokens/{design_token_config}/edit",
 *     "delete-form" = "/admin/structure/design-tokens/{design_token_config}/delete",
 *   },
 * )
 */
class DesignTokenConfig extends ConfigEntityBase implements DesignTokenConfigInterface
{

    /**
     * El ID de la configuración (machine name).
     *
     * @var string
     */
    protected $id;

    /**
     * Nombre visible de la configuración.
     *
     * @var string
     */
    protected $label;

    /**
     * Descripción de la configuración de tokens.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Scope de la cascada: platform, vertical, plan, tenant.
     *
     * @var string
     */
    protected $scope = 'platform';

    /**
     * ID de la vertical asociada.
     *
     * @var string
     */
    protected $vertical_id = '';

    /**
     * ID del plan SaaS asociado.
     *
     * @var string
     */
    protected $plan_id = '';

    /**
     * ID del tenant asociado.
     *
     * @var string
     */
    protected $tenant_id = '';

    /**
     * ID del preset de estilo aplicado.
     *
     * @var string
     */
    protected $preset_id = '';

    /**
     * Tokens de color como JSON serializado.
     *
     * @var string
     */
    protected $color_tokens = '';

    /**
     * Tokens de tipografía como JSON serializado.
     *
     * @var string
     */
    protected $typography_tokens = '';

    /**
     * Tokens de espaciado como JSON serializado.
     *
     * @var string
     */
    protected $spacing_tokens = '';

    /**
     * Tokens de efectos visuales como JSON serializado.
     *
     * @var string
     */
    protected $effect_tokens = '';

    /**
     * Variantes de componentes como JSON serializado.
     *
     * @var string
     */
    protected $component_variants = '';

    /**
     * Peso para ordenación.
     *
     * @var int
     */
    protected $weight = 0;

    // =========================================================================
    // BASIC PROPERTIES
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription(string $description): DesignTokenConfigInterface
    {
        $this->description = $description;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getScope(): string
    {
        return $this->scope ?? 'platform';
    }

    /**
     * {@inheritdoc}
     */
    public function setScope(string $scope): DesignTokenConfigInterface
    {
        $allowed = ['platform', 'vertical', 'plan', 'tenant'];
        $this->scope = in_array($scope, $allowed) ? $scope : 'platform';
        return $this;
    }

    // =========================================================================
    // SCOPE REFERENCES (Vertical, Plan, Tenant)
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function getVerticalId(): string
    {
        return $this->vertical_id ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setVerticalId(string $verticalId): DesignTokenConfigInterface
    {
        $this->vertical_id = $verticalId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlanId(): string
    {
        return $this->plan_id ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setPlanId(string $planId): DesignTokenConfigInterface
    {
        $this->plan_id = $planId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantId(): string
    {
        return $this->tenant_id ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setTenantId(string $tenantId): DesignTokenConfigInterface
    {
        $this->tenant_id = $tenantId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPresetId(): string
    {
        return $this->preset_id ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setPresetId(string $presetId): DesignTokenConfigInterface
    {
        $this->preset_id = $presetId;
        return $this;
    }

    // =========================================================================
    // DESIGN TOKENS (Color, Typography, Spacing, Effects)
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function getColorTokens(): array
    {
        if (empty($this->color_tokens)) {
            return [];
        }
        return json_decode($this->color_tokens, TRUE) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setColorTokens(array $tokens): DesignTokenConfigInterface
    {
        $this->color_tokens = json_encode($tokens);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypographyTokens(): array
    {
        if (empty($this->typography_tokens)) {
            return [];
        }
        return json_decode($this->typography_tokens, TRUE) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setTypographyTokens(array $tokens): DesignTokenConfigInterface
    {
        $this->typography_tokens = json_encode($tokens);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSpacingTokens(): array
    {
        if (empty($this->spacing_tokens)) {
            return [];
        }
        return json_decode($this->spacing_tokens, TRUE) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setSpacingTokens(array $tokens): DesignTokenConfigInterface
    {
        $this->spacing_tokens = json_encode($tokens);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEffectTokens(): array
    {
        if (empty($this->effect_tokens)) {
            return [];
        }
        return json_decode($this->effect_tokens, TRUE) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setEffectTokens(array $tokens): DesignTokenConfigInterface
    {
        $this->effect_tokens = json_encode($tokens);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getComponentVariants(): array
    {
        if (empty($this->component_variants)) {
            return [];
        }
        return json_decode($this->component_variants, TRUE) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setComponentVariants(array $variants): DesignTokenConfigInterface
    {
        $this->component_variants = json_encode($variants);
        return $this;
    }

    // =========================================================================
    // CSS GENERATION
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function generateCssCustomProperties(): string
    {
        $lines = [];

        // Color tokens → --ej-color-*
        foreach ($this->getColorTokens() as $key => $value) {
            $lines[] = "  --ej-color-{$key}: {$value};";
        }

        // Typography tokens → --ej-font-*
        foreach ($this->getTypographyTokens() as $key => $value) {
            $lines[] = "  --ej-font-{$key}: {$value};";
        }

        // Spacing tokens → --ej-spacing-*
        foreach ($this->getSpacingTokens() as $key => $value) {
            $lines[] = "  --ej-spacing-{$key}: {$value};";
        }

        // Effect tokens → --ej-* (glass, gradient, animation, etc.)
        foreach ($this->getEffectTokens() as $key => $value) {
            $lines[] = "  --ej-{$key}: {$value};";
        }

        if (empty($lines)) {
            return '';
        }

        return ":root {\n" . implode("\n", $lines) . "\n}";
    }

}
