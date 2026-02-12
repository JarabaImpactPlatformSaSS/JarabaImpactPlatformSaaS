<?php

/**
 * Script para crear DesignTokenConfig entities por vertical.
 * Ejecutar con: lando drush scr scripts/create_vertical_tokens.php
 */

$storage = \Drupal::entityTypeManager()->getStorage('design_token_config');

// AgroConecta - Naturaleza Green.
$storage->create([
    'id' => 'vertical_agroconecta',
    'label' => 'AgroConecta - Naturaleza Green',
    'description' => 'Paleta verde orgánica para la vertical AgroConecta',
    'scope' => 'vertical',
    'vertical_id' => 'agroconecta',
    'status' => TRUE,
    'weight' => 0,
    'preset_id' => 'nature_green',
    'color_tokens' => json_encode([
        'primary' => '#2E7D32',
        'secondary' => '#556B2F',
        'accent' => '#4CAF50',
        'background' => '#FAFFF5',
        'surface' => '#F1F8E9',
        'text' => '#1B2E1B',
        'text-muted' => '#4A6741',
        'border' => '#C8E6C9',
        'success' => '#43A047',
        'warning' => '#FFA000',
        'error' => '#E53935',
    ]),
    'typography_tokens' => json_encode([
        'family-heading' => 'Outfit, system-ui, sans-serif',
        'family-body' => 'Inter, system-ui, sans-serif',
        'size-base' => '1rem',
        'weight-heading' => '700',
    ]),
    'spacing_tokens' => json_encode([
        'unit' => '0.25rem',
        'radius-base' => '10px',
        'radius-xl' => '20px',
    ]),
    'effect_tokens' => json_encode([
        'glass-bg' => 'rgba(241, 248, 233, 0.92)',
        'glass-blur' => '12px',
        'shadow-card' => '0 4px 20px rgba(46, 125, 50, 0.08)',
        'gradient-primary' => 'linear-gradient(135deg, #2E7D32, #4CAF50)',
        'animation-speed' => '0.3s',
    ]),
    'component_variants' => json_encode([
        'header' => 'sticky_glass',
        'card' => 'elevated',
        'hero' => 'split',
        'footer' => 'dark',
    ]),
])->save();
echo "AgroConecta OK\n";

// Empleabilidad - Professional Blue.
$storage->create([
    'id' => 'vertical_empleabilidad',
    'label' => 'Empleabilidad - Professional Blue',
    'description' => 'Paleta azul profesional para la vertical de Empleabilidad Digital',
    'scope' => 'vertical',
    'vertical_id' => 'empleabilidad',
    'status' => TRUE,
    'weight' => 0,
    'preset_id' => 'professional_blue',
    'color_tokens' => json_encode([
        'primary' => '#1565C0',
        'secondary' => '#003366',
        'accent' => '#42A5F5',
        'background' => '#FAFBFF',
        'surface' => '#E3F2FD',
        'text' => '#0D1B2A',
        'text-muted' => '#546E7A',
        'border' => '#BBDEFB',
        'success' => '#2E7D32',
        'warning' => '#F9A825',
        'error' => '#C62828',
    ]),
    'typography_tokens' => json_encode([
        'family-heading' => 'Inter, system-ui, sans-serif',
        'family-body' => 'Inter, system-ui, sans-serif',
        'size-base' => '1rem',
        'weight-heading' => '600',
    ]),
    'spacing_tokens' => json_encode([
        'unit' => '0.25rem',
        'radius-base' => '8px',
        'radius-xl' => '16px',
    ]),
    'effect_tokens' => json_encode([
        'glass-bg' => 'rgba(227, 242, 253, 0.93)',
        'glass-blur' => '10px',
        'shadow-card' => '0 4px 20px rgba(21, 101, 192, 0.06)',
        'gradient-primary' => 'linear-gradient(135deg, #1565C0, #42A5F5)',
        'animation-speed' => '0.25s',
    ]),
    'component_variants' => json_encode([
        'header' => 'minimal',
        'card' => 'outlined',
        'hero' => 'split',
        'footer' => 'columns',
    ]),
])->save();
echo "Empleabilidad OK\n";

// Emprendimiento - Innovation Teal.
$storage->create([
    'id' => 'vertical_emprendimiento',
    'label' => 'Emprendimiento - Innovation Teal',
    'description' => 'Paleta teal/naranja vibrante para la vertical de Emprendimiento',
    'scope' => 'vertical',
    'vertical_id' => 'emprendimiento',
    'status' => TRUE,
    'weight' => 0,
    'preset_id' => 'innovation_teal',
    'color_tokens' => json_encode([
        'primary' => '#00897B',
        'secondary' => '#00695C',
        'accent' => '#FF8C42',
        'background' => '#FAFFFD',
        'surface' => '#E0F2F1',
        'text' => '#1A2E2B',
        'text-muted' => '#4A6963',
        'border' => '#B2DFDB',
        'success' => '#00C853',
        'warning' => '#FFB300',
        'error' => '#D32F2F',
    ]),
    'typography_tokens' => json_encode([
        'family-heading' => 'Outfit, system-ui, sans-serif',
        'family-body' => 'Inter, system-ui, sans-serif',
        'size-base' => '1rem',
        'weight-heading' => '700',
    ]),
    'spacing_tokens' => json_encode([
        'unit' => '0.25rem',
        'radius-base' => '12px',
        'radius-xl' => '24px',
    ]),
    'effect_tokens' => json_encode([
        'glass-bg' => 'rgba(224, 242, 241, 0.94)',
        'glass-blur' => '14px',
        'shadow-card' => '0 6px 28px rgba(0, 137, 123, 0.08)',
        'gradient-primary' => 'linear-gradient(135deg, #00897B, #FF8C42)',
        'animation-speed' => '0.3s',
    ]),
    'component_variants' => json_encode([
        'header' => 'mega',
        'card' => 'glass',
        'hero' => 'particles',
        'footer' => 'dark',
    ]),
])->save();
echo "Emprendimiento OK\n";

echo "--- ALL VERTICAL CONFIGS CREATED ---\n";
