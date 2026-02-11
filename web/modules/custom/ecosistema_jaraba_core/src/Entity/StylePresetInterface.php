<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interfaz para la entidad de configuración StylePreset.
 *
 * Un StylePreset es una plantilla visual inmutable que define tokens,
 * variantes de componentes, y directrices de contenido para un sector
 * específico. Se aplica a un tenant via PresetApplicatorService, que
 * crea un DesignTokenConfig scope=tenant a partir de estos datos.
 *
 * Taxonomía: Vertical → Sector → Mood (f-101/f-102).
 */
interface StylePresetInterface extends ConfigEntityInterface
{

    /**
     * Obtiene la descripción del preset.
     */
    public function getDescription(): string;

    /**
     * Establece la descripción.
     */
    public function setDescription(string $description): static;

    /**
     * Obtiene el vertical al que pertenece (agroconecta, comercioconecta, serviciosconecta).
     */
    public function getVertical(): string;

    /**
     * Establece el vertical.
     */
    public function setVertical(string $vertical): static;

    /**
     * Obtiene el sector (gourmet, legal, tech, barrio...).
     */
    public function getSector(): string;

    /**
     * Establece el sector.
     */
    public function setSector(string $sector): static;

    /**
     * Obtiene los mood tags como array (ej: ['luxury', 'craft', 'premium']).
     */
    public function getMood(): array;

    /**
     * Establece los mood tags.
     */
    public function setMood(array $mood): static;

    /**
     * Obtiene la descripción del público objetivo.
     */
    public function getTargetAudience(): string;

    /**
     * Establece la descripción del público objetivo.
     */
    public function setTargetAudience(string $audience): static;

    /**
     * Obtiene los tokens de color del preset.
     *
     * @return array
     *   ['primary' => '#hex', 'secondary' => '#hex', 'accent' => '#hex', ...]
     */
    public function getColorTokens(): array;

    /**
     * Establece los tokens de color.
     */
    public function setColorTokens(array $tokens): static;

    /**
     * Obtiene los tokens de tipografía del preset.
     *
     * @return array
     *   ['family-heading' => 'Font Name', 'family-body' => 'Font Name', ...]
     */
    public function getTypographyTokens(): array;

    /**
     * Establece los tokens de tipografía.
     */
    public function setTypographyTokens(array $tokens): static;

    /**
     * Obtiene los tokens de espaciado y bordes.
     *
     * @return array
     *   ['radius-md' => '8px', 'shadow-md' => '...', ...]
     */
    public function getSpacingTokens(): array;

    /**
     * Establece los tokens de espaciado.
     */
    public function setSpacingTokens(array $tokens): static;

    /**
     * Obtiene los tokens de efectos visuales (glassmorphism, gradients...).
     *
     * @return array
     *   ['glass-bg' => 'rgba(...)', 'gradient-primary' => '...', ...]
     */
    public function getEffectTokens(): array;

    /**
     * Establece los tokens de efectos.
     */
    public function setEffectTokens(array $tokens): static;

    /**
     * Obtiene las variantes de componentes seleccionadas.
     *
     * @return array
     *   ['header' => 'transparent', 'hero' => 'fullscreen', ...]
     */
    public function getComponentVariants(): array;

    /**
     * Establece las variantes de componentes.
     */
    public function setComponentVariants(array $variants): static;

    /**
     * Obtiene la configuración de animación del preset.
     *
     * @return array
     *   ['type' => 'glow'|'elegant'|'glassmorphism', 'speed' => '250ms', ...]
     */
    public function getAnimationConfig(): array;

    /**
     * Establece la configuración de animación.
     */
    public function setAnimationConfig(array $config): static;

    /**
     * Obtiene las directrices de contenido (fotografía, copywriting, iconografía).
     *
     * @return array
     *   ['photography' => '...', 'copywriting' => '...', 'iconography' => '...']
     */
    public function getContentGuidelines(): array;

    /**
     * Establece las directrices de contenido.
     */
    public function setContentGuidelines(array $guidelines): static;

    /**
     * Obtiene las URLs de importación de Google Fonts.
     *
     * @return array
     *   ['https://fonts.googleapis.com/css2?family=...', ...]
     */
    public function getFontImports(): array;

    /**
     * Establece las URLs de importación de fuentes.
     */
    public function setFontImports(array $imports): static;

    /**
     * Genera todas las CSS Custom Properties de este preset.
     *
     * @return string
     *   Bloque CSS completo con custom properties html:root { ... }.
     */
    public function generatePreviewCss(): string;

}
