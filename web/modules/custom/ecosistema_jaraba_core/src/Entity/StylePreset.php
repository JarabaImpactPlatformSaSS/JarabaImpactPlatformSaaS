<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuración StylePreset.
 *
 * Un StylePreset es una plantilla visual inmutable que representa un
 * "punto de partida experto" para un sector de negocio específico.
 * Contiene tokens de diseño, variantes de componentes, configuración
 * de animaciones y directrices de contenido.
 *
 * Taxonomía (f-101): Vertical → Sector → Mood
 * - Verticales: agroconecta, comercioconecta, serviciosconecta
 * - Sectores: gourmet, legal, tech, barrio, organic, etc.
 * - Moods: luxury, professional, friendly, bold, zen, etc.
 *
 * FLUJO DE APLICACIÓN:
 * 1. Tenant selecciona un StylePreset durante onboarding
 * 2. PresetApplicatorService copia los tokens del preset
 * 3. Se crea un DesignTokenConfig scope=tenant con preset_id=X
 * 4. El tenant puede personalizar sobre esa base
 *
 * @ConfigEntityType(
 *   id = "style_preset",
 *   label = @Translation("Style Preset"),
 *   label_collection = @Translation("Style Presets"),
 *   label_singular = @Translation("preset de estilo"),
 *   label_plural = @Translation("presets de estilo"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\StylePresetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\StylePresetForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\StylePresetForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "style_preset",
 *   admin_permission = "administer style presets",
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
 *     "vertical",
 *     "sector",
 *     "mood",
 *     "target_audience",
 *     "color_tokens",
 *     "typography_tokens",
 *     "spacing_tokens",
 *     "effect_tokens",
 *     "component_variants",
 *     "animation_config",
 *     "content_guidelines",
 *     "font_imports",
 *     "weight",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/style-presets",
 *     "add-form" = "/admin/structure/style-presets/add",
 *     "edit-form" = "/admin/structure/style-presets/{style_preset}/edit",
 *     "delete-form" = "/admin/structure/style-presets/{style_preset}/delete",
 *   },
 * )
 */
class StylePreset extends ConfigEntityBase implements StylePresetInterface
{

    /**
     * ID del preset (machine name): agro_gourmet, servicios_legal, etc.
     *
     * @var string
     */
    protected $id;

    /**
     * Nombre visible: "Gourmet Artesanal", "Legal & Jurídico", etc.
     *
     * @var string
     */
    protected $label;

    /**
     * Descripción del preset y su público objetivo.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Vertical: agroconecta, comercioconecta, serviciosconecta.
     *
     * @var string
     */
    protected $vertical = '';

    /**
     * Sector de negocio: gourmet, legal, tech, barrio, etc.
     *
     * @var string
     */
    protected $sector = '';

    /**
     * Mood tags como JSON: ["luxury", "craft", "premium"].
     *
     * @var string
     */
    protected $mood = '[]';

    /**
     * Descripción del público objetivo.
     *
     * @var string
     */
    protected $target_audience = '';

    /**
     * Tokens de color como JSON serializado.
     *
     * @var string
     */
    protected $color_tokens = '{}';

    /**
     * Tokens de tipografía como JSON serializado.
     *
     * @var string
     */
    protected $typography_tokens = '{}';

    /**
     * Tokens de espaciado como JSON serializado.
     *
     * @var string
     */
    protected $spacing_tokens = '{}';

    /**
     * Tokens de efectos visuales como JSON serializado.
     *
     * @var string
     */
    protected $effect_tokens = '{}';

    /**
     * Variantes de componentes como JSON serializado.
     *
     * @var string
     */
    protected $component_variants = '{}';

    /**
     * Configuración de animaciones como JSON serializado.
     *
     * @var string
     */
    protected $animation_config = '{}';

    /**
     * Directrices de contenido como JSON serializado.
     *
     * @var string
     */
    protected $content_guidelines = '{}';

    /**
     * URLs de importación de Google Fonts como JSON serializado.
     *
     * @var string
     */
    protected $font_imports = '[]';

    /**
     * Peso para ordenación en la galería.
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
    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getVertical(): string
    {
        return $this->vertical ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setVertical(string $vertical): static
    {
        $allowed = ['agroconecta', 'comercioconecta', 'serviciosconecta'];
        $this->vertical = in_array($vertical, $allowed) ? $vertical : '';
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSector(): string
    {
        return $this->sector ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setSector(string $sector): static
    {
        $this->sector = $sector;
        return $this;
    }

    // =========================================================================
    // MOOD & TARGET AUDIENCE
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function getMood(): array
    {
        if (empty($this->mood) || $this->mood === '[]') {
            return [];
        }
        return json_decode($this->mood, TRUE) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setMood(array $mood): static
    {
        $this->mood = json_encode(array_values($mood));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetAudience(): string
    {
        return $this->target_audience ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setTargetAudience(string $audience): static
    {
        $this->target_audience = $audience;
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
        return $this->decodeJson($this->color_tokens);
    }

    /**
     * {@inheritdoc}
     */
    public function setColorTokens(array $tokens): static
    {
        $this->color_tokens = json_encode($tokens);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypographyTokens(): array
    {
        return $this->decodeJson($this->typography_tokens);
    }

    /**
     * {@inheritdoc}
     */
    public function setTypographyTokens(array $tokens): static
    {
        $this->typography_tokens = json_encode($tokens);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSpacingTokens(): array
    {
        return $this->decodeJson($this->spacing_tokens);
    }

    /**
     * {@inheritdoc}
     */
    public function setSpacingTokens(array $tokens): static
    {
        $this->spacing_tokens = json_encode($tokens);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEffectTokens(): array
    {
        return $this->decodeJson($this->effect_tokens);
    }

    /**
     * {@inheritdoc}
     */
    public function setEffectTokens(array $tokens): static
    {
        $this->effect_tokens = json_encode($tokens);
        return $this;
    }

    // =========================================================================
    // COMPONENT VARIANTS & ANIMATION
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function getComponentVariants(): array
    {
        return $this->decodeJson($this->component_variants);
    }

    /**
     * {@inheritdoc}
     */
    public function setComponentVariants(array $variants): static
    {
        $this->component_variants = json_encode($variants);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAnimationConfig(): array
    {
        return $this->decodeJson($this->animation_config);
    }

    /**
     * {@inheritdoc}
     */
    public function setAnimationConfig(array $config): static
    {
        $this->animation_config = json_encode($config);
        return $this;
    }

    // =========================================================================
    // CONTENT GUIDELINES & FONTS
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function getContentGuidelines(): array
    {
        return $this->decodeJson($this->content_guidelines);
    }

    /**
     * {@inheritdoc}
     */
    public function setContentGuidelines(array $guidelines): static
    {
        $this->content_guidelines = json_encode($guidelines);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFontImports(): array
    {
        if (empty($this->font_imports) || $this->font_imports === '[]') {
            return [];
        }
        return json_decode($this->font_imports, TRUE) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setFontImports(array $imports): static
    {
        $this->font_imports = json_encode(array_values($imports));
        return $this;
    }

    // =========================================================================
    // UTILITY
    // =========================================================================

    /**
     * Decodifica un campo JSON, devolviendo array vacío si inválido.
     *
     * @param string|null $json
     *   La cadena JSON.
     *
     * @return array
     *   El array decodificado.
     */
    protected function decodeJson(?string $json): array
    {
        if (empty($json) || $json === '{}' || $json === '[]') {
            return [];
        }
        return json_decode($json, TRUE) ?? [];
    }

    /**
     * Genera todas las CSS Custom Properties de este preset.
     *
     * Combina todos los token groups en un bloque html:root { ... }
     * para preview o para inyección directa.
     *
     * @return string
     *   Bloque CSS completo con custom properties.
     */
    public function generatePreviewCss(): string
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

        // Spacing tokens → --ej-spacing-* / --ej-radius-*
        foreach ($this->getSpacingTokens() as $key => $value) {
            $lines[] = "  --ej-{$key}: {$value};";
        }

        // Effect tokens → --ej-* (glass, gradient, etc.)
        foreach ($this->getEffectTokens() as $key => $value) {
            $lines[] = "  --ej-{$key}: {$value};";
        }

        if (empty($lines)) {
            return '';
        }

        return "html:root {\n" . implode("\n", $lines) . "\n}";
    }

}
