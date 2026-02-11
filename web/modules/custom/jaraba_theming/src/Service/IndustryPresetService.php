<?php

declare(strict_types=1);

namespace Drupal\jaraba_theming\Service;

/**
 * Servicio para obtener presets de industria/sector.
 *
 * Proporciona configuraciones predefinidas de Design Tokens
 * para 15 sectores específicos que los tenants pueden elegir.
 */
class IndustryPresetService
{

    /**
     * Lista de todos los presets disponibles.
     *
     * Cada preset incluye:
     * - label: Nombre visible
     * - vertical: Vertical al que pertenece
     * - colors: Paleta de colores
     * - typography: Configuración tipográfica
     * - ui: Elementos de UI
     */
    protected const PRESETS = [
        // === AGROCONECTA (Agricultura) ===
        'agro_oliva' => [
            'label' => 'Olivar y Aceite',
            'vertical' => 'agroconecta',
            'colors' => [
                'primary' => '#556B2F',
                'secondary' => '#808000',
                'accent' => '#D4AF37',
                'background' => '#FEFDF8',
            ],
            'typography' => [
                'headings' => 'Playfair Display',
                'body' => 'Source Sans Pro',
            ],
            'ui' => [
                'border_radius' => '4px',
                'shadow' => 'soft',
            ],
        ],
        'agro_vinicola' => [
            'label' => 'Vinícola',
            'vertical' => 'agroconecta',
            'colors' => [
                'primary' => '#722F37',
                'secondary' => '#4A0E0E',
                'accent' => '#D4AF37',
                'background' => '#FDF9F6',
            ],
            'typography' => [
                'headings' => 'Cormorant Garamond',
                'body' => 'Lato',
            ],
            'ui' => [
                'border_radius' => '2px',
                'shadow' => 'elegant',
            ],
        ],
        'agro_horticultura' => [
            'label' => 'Horticultura',
            'vertical' => 'agroconecta',
            'colors' => [
                'primary' => '#228B22',
                'secondary' => '#8FBC8F',
                'accent' => '#FF6347',
                'background' => '#F0FFF0',
            ],
            'typography' => [
                'headings' => 'Outfit',
                'body' => 'Inter',
            ],
            'ui' => [
                'border_radius' => '8px',
                'shadow' => 'natural',
            ],
        ],

        // === COMERCIOCONECTA (Retail) ===
        'comercio_moda' => [
            'label' => 'Moda y Boutique',
            'vertical' => 'comercio',
            'colors' => [
                'primary' => '#1A1A1A',
                'secondary' => '#B8860B',
                'accent' => '#DC143C',
                'background' => '#FFFFFF',
            ],
            'typography' => [
                'headings' => 'Montserrat',
                'body' => 'Open Sans',
            ],
            'ui' => [
                'border_radius' => '0px',
                'shadow' => 'minimal',
            ],
        ],
        'comercio_electronica' => [
            'label' => 'Electrónica',
            'vertical' => 'comercio',
            'colors' => [
                'primary' => '#0066CC',
                'secondary' => '#00BFFF',
                'accent' => '#FF4500',
                'background' => '#F5F5F5',
            ],
            'typography' => [
                'headings' => 'Roboto',
                'body' => 'Roboto',
            ],
            'ui' => [
                'border_radius' => '8px',
                'shadow' => 'tech',
            ],
        ],
        'comercio_artesania' => [
            'label' => 'Artesanía Local',
            'vertical' => 'comercio',
            'colors' => [
                'primary' => '#8B4513',
                'secondary' => '#DEB887',
                'accent' => '#2E8B57',
                'background' => '#FFF8DC',
            ],
            'typography' => [
                'headings' => 'Merriweather',
                'body' => 'Source Serif Pro',
            ],
            'ui' => [
                'border_radius' => '6px',
                'shadow' => 'warm',
            ],
        ],

        // === SERVICIOSCONECTA (Servicios) ===
        'servicios_restaurante' => [
            'label' => 'Restaurante',
            'vertical' => 'servicios',
            'colors' => [
                'primary' => '#B22222',
                'secondary' => '#FFD700',
                'accent' => '#228B22',
                'background' => '#FFFAF0',
            ],
            'typography' => [
                'headings' => 'Playfair Display',
                'body' => 'Lato',
            ],
            'ui' => [
                'border_radius' => '4px',
                'shadow' => 'cozy',
            ],
        ],
        'servicios_salud' => [
            'label' => 'Salud y Bienestar',
            'vertical' => 'servicios',
            'colors' => [
                'primary' => '#20B2AA',
                'secondary' => '#87CEEB',
                'accent' => '#32CD32',
                'background' => '#F0FFFF',
            ],
            'typography' => [
                'headings' => 'Nunito',
                'body' => 'Open Sans',
            ],
            'ui' => [
                'border_radius' => '16px',
                'shadow' => 'soft',
            ],
        ],
        'servicios_belleza' => [
            'label' => 'Belleza y Peluquería',
            'vertical' => 'servicios',
            'colors' => [
                'primary' => '#FF69B4',
                'secondary' => '#DDA0DD',
                'accent' => '#9370DB',
                'background' => '#FFF0F5',
            ],
            'typography' => [
                'headings' => 'Poppins',
                'body' => 'Quicksand',
            ],
            'ui' => [
                'border_radius' => '20px',
                'shadow' => 'glamour',
            ],
        ],
        'servicios_legal' => [
            'label' => 'Asesoría Legal',
            'vertical' => 'servicios',
            'colors' => [
                'primary' => '#1C3A5F',
                'secondary' => '#4682B4',
                'accent' => '#8B0000',
                'background' => '#F5F5F5',
            ],
            'typography' => [
                'headings' => 'Libre Baskerville',
                'body' => 'Source Sans Pro',
            ],
            'ui' => [
                'border_radius' => '2px',
                'shadow' => 'formal',
            ],
        ],
        'servicios_consultor' => [
            'label' => 'Consultoría',
            'vertical' => 'servicios',
            'colors' => [
                'primary' => '#2C3E50',
                'secondary' => '#3498DB',
                'accent' => '#E74C3C',
                'background' => '#ECF0F1',
            ],
            'typography' => [
                'headings' => 'Inter',
                'body' => 'Inter',
            ],
            'ui' => [
                'border_radius' => '8px',
                'shadow' => 'corporate',
            ],
        ],

        // === EMPLEABILIDAD ===
        'empleabilidad_tech' => [
            'label' => 'Tecnología/IT',
            'vertical' => 'empleabilidad',
            'colors' => [
                'primary' => '#6366F1',
                'secondary' => '#8B5CF6',
                'accent' => '#06B6D4',
                'background' => '#F8FAFC',
            ],
            'typography' => [
                'headings' => 'JetBrains Mono',
                'body' => 'Inter',
            ],
            'ui' => [
                'border_radius' => '12px',
                'shadow' => 'modern',
            ],
        ],
        'empleabilidad_industrial' => [
            'label' => 'Industrial/Manufactura',
            'vertical' => 'empleabilidad',
            'colors' => [
                'primary' => '#475569',
                'secondary' => '#F59E0B',
                'accent' => '#EF4444',
                'background' => '#F1F5F9',
            ],
            'typography' => [
                'headings' => 'Oswald',
                'body' => 'Roboto',
            ],
            'ui' => [
                'border_radius' => '4px',
                'shadow' => 'industrial',
            ],
        ],

        // === EMPRENDIMIENTO ===
        'emprendimiento_startup' => [
            'label' => 'Startup Tech',
            'vertical' => 'emprendimiento',
            'colors' => [
                'primary' => '#7C3AED',
                'secondary' => '#EC4899',
                'accent' => '#10B981',
                'background' => '#FAFAF9',
            ],
            'typography' => [
                'headings' => 'Outfit',
                'body' => 'Inter',
            ],
            'ui' => [
                'border_radius' => '16px',
                'shadow' => 'playful',
            ],
        ],
        'emprendimiento_social' => [
            'label' => 'Emprendimiento Social',
            'vertical' => 'emprendimiento',
            'colors' => [
                'primary' => '#059669',
                'secondary' => '#0891B2',
                'accent' => '#F97316',
                'background' => '#F0FDF4',
            ],
            'typography' => [
                'headings' => 'Nunito',
                'body' => 'Open Sans',
            ],
            'ui' => [
                'border_radius' => '12px',
                'shadow' => 'friendly',
            ],
        ],
    ];

    /**
     * Obtiene todos los presets disponibles.
     *
     * @return array
     *   Array de presets indexado por machine_name.
     */
    public function getAllPresets(): array
    {
        return self::PRESETS;
    }

    /**
     * Obtiene un preset específico.
     *
     * @param string $preset_id
     *   ID del preset.
     *
     * @return array|null
     *   Preset o NULL si no existe.
     */
    public function getPreset(string $preset_id): ?array
    {
        return self::PRESETS[$preset_id] ?? NULL;
    }

    /**
     * Obtiene presets filtrados por vertical.
     *
     * @param string $vertical
     *   Nombre del vertical.
     *
     * @return array
     *   Presets del vertical.
     */
    public function getPresetsByVertical(string $vertical): array
    {
        return array_filter(self::PRESETS, fn($p) => $p['vertical'] === $vertical);
    }

    /**
     * Obtiene opciones para un select.
     *
     * @return array
     *   Array label => id para form options.
     */
    public function getPresetsAsOptions(): array
    {
        $options = [];
        foreach (self::PRESETS as $id => $preset) {
            $options[$id] = $preset['label'] . ' (' . ucfirst($preset['vertical']) . ')';
        }
        return $options;
    }

    /**
     * Genera CSS variables para un preset.
     *
     * @param string $preset_id
     *   ID del preset.
     *
     * @return string
     *   Bloque CSS con :root variables.
     */
    public function generateCss(string $preset_id): string
    {
        $preset = $this->getPreset($preset_id);
        if (!$preset) {
            return '';
        }

        $css = ":root {\n";
        $css .= "  --ej-color-primary: {$preset['colors']['primary']};\n";
        $css .= "  --ej-color-secondary: {$preset['colors']['secondary']};\n";
        $css .= "  --ej-color-accent: {$preset['colors']['accent']};\n";
        $css .= "  --ej-surface-bg: {$preset['colors']['background']};\n";
        $css .= "  --ej-font-family-headings: '{$preset['typography']['headings']}', sans-serif;\n";
        $css .= "  --ej-font-family-body: '{$preset['typography']['body']}', sans-serif;\n";
        $css .= "  --ej-border-radius: {$preset['ui']['border_radius']};\n";
        $css .= "}\n";

        return $css;
    }

    /**
     * Lista de sectores disponibles para UI.
     *
     * @return array
     *   Array con metadata de cada sector.
     */
    public function getSectorsList(): array
    {
        $sectors = [];
        foreach (self::PRESETS as $id => $preset) {
            $sectors[] = [
                'id' => $id,
                'label' => $preset['label'],
                'vertical' => $preset['vertical'],
                'preview_colors' => [
                    $preset['colors']['primary'],
                    $preset['colors']['secondary'],
                    $preset['colors']['accent'],
                ],
            ];
        }
        return $sectors;
    }

}
