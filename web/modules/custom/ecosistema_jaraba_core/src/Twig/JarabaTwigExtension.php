<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Twig;

use Drupal\Component\Utility\Xss;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Jaraba SaaS Twig Extension.
 *
 * Provides custom Twig functions for the Jaraba Impact Platform.
 */
class JarabaTwigExtension extends AbstractExtension
{

    /**
     * Brand color palette - Official Jaraba colors.
     */
    public const COLORS = [
        // === PALETA DE MARCA JARABA ===
        'azul-profundo' => '#003366',  // Azul profundo - Autoridad, profundidad
        'azul-verdoso' => '#2B7A78',  // Azul verdoso - Conexión, equilibrio
        'azul-corporativo' => '#233D63',  // Azul Corporativo - La "J", confianza, base
        'naranja-impulso' => '#FF8C42',  // Naranja Impulso - Empresas, emprendimiento, acción
        'verde-innovacion' => '#00A9A5',  // Verde/Turquesa Innovación - Talento, empleabilidad
        'verde-oliva' => '#556B2F',  // Verde Oliva - AgroConecta, naturaleza
        'verde-oliva-oscuro' => '#3E4E23',  // Verde Oliva Oscuro - AgroConecta intenso

        // Aliases semánticos (para uso más fácil)
        'corporate' => '#233D63',  // Alias: azul-corporativo
        'innovation' => '#00A9A5',  // Alias: verde-innovacion
        'impulse' => '#FF8C42',  // Alias: naranja-impulso
        'agro' => '#556B2F',  // Alias: verde-oliva

        // === PALETA UI EXTENDIDA ===
        'primary' => '#4F46E5',  // Indigo - Acciones primarias UI
        'secondary' => '#7C3AED',  // Violeta - IA, features premium
        'success' => '#10B981',  // Esmeralda - Estados positivos
        'warning' => '#F59E0B',  // Ámbar - Alertas
        'danger' => '#EF4444',  // Rojo - Errores, destructivo
        'neutral' => '#64748B',  // Slate - Muted, disabled
    ];

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'jaraba_twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('safe_html', [$this, 'filterSafeHtml'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    /**
     * AUDIT-SEC-N04: Sanitiza HTML para prevenir XSS almacenado.
     *
     * Permite tags HTML seguros (div, span, p, h1-h6, ul, ol, li, a, img,
     * table, strong, em, etc.) pero elimina <script>, <iframe>, event handlers
     * (onclick, onerror), y otros vectores XSS.
     *
     * Uso en Twig: {{ content|safe_html }} en vez de {{ content|raw }}
     *
     * @param string|null $html
     *   HTML potencialmente inseguro.
     *
     * @return string
     *   HTML sanitizado.
     */
    public function filterSafeHtml(?string $html): string
    {
        if ($html === NULL || $html === '') {
            return '';
        }
        return Xss::filterAdmin($html);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('jaraba_icon', [$this, 'renderIcon'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('jaraba_icon_path', [$this, 'getIconPath']),
            new TwigFunction('jaraba_color', [$this, 'getColor']),
        ];
    }

    /**
     * Gets a brand color by name.
     *
     * @param string $name
     *   Color name: corporate, innovation, impulse, primary, secondary, etc.
     *
     * @return string
     *   Hex color value.
     */
    public function getColor(string $name): string
    {
        return self::COLORS[$name] ?? self::COLORS['neutral'];
    }

    /**
     * Renders an SVG icon from the Jaraba icon library.
     *
     * @param string $category
     *   Icon category: business, analytics, actions, ai, ui, verticals.
     * @param string $name
     *   Icon name (e.g., 'diagnostic', 'gauge', 'check').
     * @param array $options
     *   Optional settings:
     *   - variant: 'outline', 'outline-bold', 'filled', 'duotone' (default: 'outline')
     *   - size: CSS size string (default: '24px')
     *   - class: Additional CSS classes.
     *   - color: Brand color name OR hex value (default: current color)
     *            Semantic colors: corporate, innovation, impulse
     *            UI colors: primary, secondary, success, warning, danger, neutral
     *
     * @return string
     *   HTML for the icon with inline SVG styles.
     */
    public function renderIcon(string $category, string $name, array $options = []): string
    {
        $variant = $options['variant'] ?? 'outline';
        $size = (string) ($options['size'] ?? '24px');
        $class = $options['class'] ?? '';
        $colorInput = $options['color'] ?? '';

        // Resolve color: name → hex or use as-is if already hex
        $color = '';
        if ($colorInput) {
            $color = self::COLORS[$colorInput] ?? $colorInput;
        }

        $path = $this->getIconPath($category, $name, $variant);

        // Check if the SVG file exists, otherwise return fallback
        $modulePath = \Drupal::service('extension.list.module')->getPath('ecosistema_jaraba_core');
        $fullPath = DRUPAL_ROOT . "/{$modulePath}/images/icons/{$category}/{$name}.svg";

        if (!file_exists($fullPath)) {
            // Return fallback emoji based on category/name
            $fallback = $this->getFallbackEmoji($category, $name);
            return sprintf(
                '<span class="jaraba-icon jaraba-icon--fallback jaraba-icon--%s jaraba-icon--%s" style="font-size: %s; display: inline-block; vertical-align: middle;">%s</span>',
                htmlspecialchars((string) $category),
                htmlspecialchars((string) $name),
                htmlspecialchars($size),
                $fallback
            );
        }

        // Build inline styles
        $styles = [];
        $styles[] = "width: {$size}";
        $styles[] = "height: {$size}";
        $styles[] = "display: inline-block";
        $styles[] = "vertical-align: middle";

        // Apply color filter for SVG recoloring
        if ($color) {
            // Use CSS filter for color transformation
            $styles[] = "filter: " . $this->getColorFilter($color);
        }

        $styleAttr = implode('; ', $styles);

        // Build CSS classes
        $classes = ['jaraba-icon', "jaraba-icon--{$category}", "jaraba-icon--{$name}"];
        if ($class) {
            $classes[] = $class;
        }
        if ($variant !== 'outline') {
            $classes[] = "jaraba-icon--{$variant}";
        }
        if ($colorInput && isset(self::COLORS[$colorInput])) {
            $classes[] = "jaraba-icon--color-{$colorInput}";
        }
        $classAttr = implode(' ', $classes);

        return sprintf(
            '<img src="%s" alt="%s" class="%s" style="%s" loading="lazy" aria-hidden="true" />',
            $path,
            htmlspecialchars((string) $name),
            htmlspecialchars((string) $classAttr),
            htmlspecialchars((string) $styleAttr)
        );
    }

    /**
     * Gets fallback emoji for icons when SVG doesn't exist.
     */
    private function getFallbackEmoji(string $category, string $name): string
    {
        $fallbacks = [
            // Analytics
            'analytics' => ['gauge' => '📊', 'chart-bar' => '📊', 'chart-line' => '📈', 'trend-down' => '📉', 'radar' => '📊'],
            // Business
            'business' => ['target' => '🎯', 'company' => '🏢', 'diagnostic' => '📋', 'achievement' => '⭐', 'shield' => '🛡️', 'pathway' => '🛤️', 'progress' => '📈', 'job' => '👔', 'cart' => '🛒', 'megaphone' => '📣'],
            // Actions
            'actions' => ['check' => '✅', 'close' => '❌', 'refresh' => '🔄', 'download' => '⬇️', 'plus' => '➕', 'edit' => '✏️', 'delete' => '🗑️'],
            // AI
            'ai' => ['copilot' => '🤖', 'automation' => '⚡', 'lightbulb' => '💡', 'brain' => '🧠', 'sparkle' => '✨'],
            // UI
            'ui' => ['home' => '🏠', 'search' => '🔍', 'menu' => '☰', 'settings' => '⚙️', 'user' => '👤', 'bell' => '🔔', 'filter' => '🔍', 'clock' => '⏱️', 'calendar' => '📆', 'book' => '📚', 'wrench' => '🔧', 'database' => '💾', 'globe' => '🌐', 'heartbeat' => '💚', 'hospital' => '🏥', 'users' => '👥', 'map' => '🗺️', 'tools' => '🛠️', 'eye' => '👁️', 'warning' => '⚠️', 'lock' => '🔒', 'bolt' => '⚡', 'shield' => '🛡️', 'chart-bar' => '📊', 'arrow-right' => '→', 'chevron-left' => '‹', 'chevron-right' => '›', 'star' => '⭐', 'play' => '▶️', 'arrows-horizontal' => '↔️', 'info' => 'ℹ️'],
            // Verticals
            'verticals' => ['rocket' => '🚀', 'leaf' => '🌱', 'briefcase' => '💼'],
        ];

        return $fallbacks[$category][$name] ?? '📌';
    }

    /**
     * Gets the path to an icon file.
     */
    public function getIconPath(string $category, string $name, string $variant = 'outline'): string
    {
        $modulePath = \Drupal::service('extension.list.module')->getPath('ecosistema_jaraba_core');

        $filename = match ($variant) {
            'outline-bold' => "{$name}-bold.svg",
            'filled' => "{$name}-filled.svg",
            'duotone' => "{$name}-duotone.svg",
            default => "{$name}.svg",
        };

        return "/{$modulePath}/images/icons/{$category}/{$filename}";
    }

    /**
     * Generates CSS filter for SVG color transformation.
     *
     * @param string $hex
     *   Target hex color.
     *
     * @return string
     *   CSS filter value.
     */
    private function getColorFilter(string $hex): string
    {
        // For now, use simple brightness/hue rotation
        // In production, use a proper hex-to-filter library
        return match ($hex) {
            '#233D63' => 'brightness(0) saturate(100%) invert(19%) sepia(25%) saturate(1500%) hue-rotate(190deg) brightness(95%)',
            '#00A9A5' => 'brightness(0) saturate(100%) invert(55%) sepia(50%) saturate(700%) hue-rotate(130deg) brightness(95%)',
            '#FF8C42' => 'brightness(0) saturate(100%) invert(60%) sepia(80%) saturate(500%) hue-rotate(350deg) brightness(100%)',
            '#4F46E5' => 'brightness(0) saturate(100%) invert(30%) sepia(90%) saturate(2000%) hue-rotate(230deg) brightness(90%)',
            '#7C3AED' => 'brightness(0) saturate(100%) invert(30%) sepia(90%) saturate(3000%) hue-rotate(260deg) brightness(90%)',
            '#10B981' => 'brightness(0) saturate(100%) invert(60%) sepia(50%) saturate(500%) hue-rotate(100deg) brightness(95%)',
            '#EF4444' => 'brightness(0) saturate(100%) invert(40%) sepia(90%) saturate(2000%) hue-rotate(340deg) brightness(95%)',
            // White colors for dark backgrounds
            'white', '#FFFFFF', '#ffffff', '#FFF', '#fff' => 'brightness(0) invert(1)',
            default => 'none',
        };
    }

}
