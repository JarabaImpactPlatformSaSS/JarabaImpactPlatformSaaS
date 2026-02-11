<?php

/**
 * @file
 * Helper de iconos SVG para Ecosistema Jaraba Theme.
 *
 * Proporciona iconos inline SVG premium para el panel de configuración.
 * Inspirado en estilos Flaticon/Lucide.
 */

/**
 * Devuelve un icono SVG inline para usar en formularios admin.
 *
 * @param string $name
 *   Nombre del icono.
 * @param string $color
 *   Color hex del icono.
 * @param int $size
 *   Tamaño en píxeles.
 * @param string $variant
 *   Variante: 'outline' o 'duotone'.
 *
 * @return string
 *   HTML del icono SVG.
 */
function _ecosistema_jaraba_theme_icon(string $name, string $color = '#FF8C42', int $size = 18, string $variant = 'duotone'): string {
  // Iconos outline (trazo)
  $icons_outline = [
    'building' => '<path d="M3 21h18M6 21V4a1 1 0 011-1h10a1 1 0 011 1v17M6 8h.01M6 12h.01M6 16h.01M10 8h.01M10 12h.01M10 16h.01M14 8h.01M14 12h.01M14 16h.01" stroke-width="2" stroke-linecap="round"/>',
    'typography' => '<path d="M4 7V4h16v3M9 20h6M12 4v16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    'layout-navbar' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18" stroke-width="2"/>',
    'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21" stroke-width="2"/>',
    'layout-cards' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    'layout-footer' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 15h18" stroke-width="2"/>',
    'layout-sidebar' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18" stroke-width="2"/>',
    'puzzle' => '<path d="M12 2l2 4 4 2-4 2-2 4-2-4-4-2 4-2 2-4z" stroke-width="2" stroke-linejoin="round"/>',
    'shopping-cart' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 002 1.58h9.78a2 2 0 001.95-1.57l1.65-7.43H5.12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke-width="2"/>',
    'megaphone' => '<path d="M3 11l18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 11-5.8-1.6" stroke-width="2" stroke-linecap="round"/>',
    'palette' => '<circle cx="13.5" cy="6.5" r="1.5"/><circle cx="17.5" cy="10.5" r="1.5"/><circle cx="8.5" cy="7.5" r="1.5"/><circle cx="6.5" cy="12.5" r="1.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 011.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.555C21.965 6.012 17.461 2 12 2z" stroke-width="2"/>',
    'navigation' => '<polygon points="3 11 22 2 13 21 11 13 3 11" stroke-width="2" stroke-linejoin="round"/>',
    'sparkles' => '<path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z" stroke-width="2" stroke-linejoin="round"/><path d="M5 3v2M19 19v2M3 5h2M19 19h2" stroke-width="2" stroke-linecap="round"/>',
  ];

  // Iconos duotone (capa de fondo con opacidad + trazo)
  $icons_duotone = [
    'building' => '<rect x="7" y="3" width="10" height="18" rx="1" fill="currentColor" opacity="0.2"/><path d="M3 21h18M6 21V4a1 1 0 011-1h10a1 1 0 011 1v17M9 8h.01M9 12h.01M9 16h.01M12 8h.01M12 12h.01M12 16h.01M15 8h.01M15 12h.01M15 16h.01" stroke-width="2" stroke-linecap="round"/>',
    'typography' => '<rect x="8" y="4" width="8" height="16" fill="currentColor" opacity="0.2"/><path d="M4 7V4h16v3M9 20h6M12 4v16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    'layout-navbar' => '<rect x="3" y="3" width="18" height="6" rx="2" fill="currentColor" opacity="0.2"/><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18" stroke-width="2"/>',
    'image' => '<rect x="3" y="3" width="18" height="18" rx="2" fill="currentColor" opacity="0.2"/><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/><path d="M21 15l-5-5L5 21" stroke-width="2"/>',
    'layout-cards' => '<rect x="3" y="3" width="7" height="7" rx="1" fill="currentColor" opacity="0.2"/><rect x="14" y="14" width="7" height="7" rx="1" fill="currentColor" opacity="0.2"/><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    'layout-footer' => '<rect x="3" y="15" width="18" height="6" rx="0" fill="currentColor" opacity="0.2"/><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 15h18" stroke-width="2"/>',
    'layout-sidebar' => '<rect x="3" y="3" width="6" height="18" rx="0" fill="currentColor" opacity="0.2"/><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18" stroke-width="2"/>',
    'puzzle' => '<path d="M12 2l2 4 4 2-4 2-2 4-2-4-4-2 4-2 2-4z" fill="currentColor" opacity="0.2"/><path d="M12 2l2 4 4 2-4 2-2 4-2-4-4-2 4-2 2-4z" stroke-width="2" stroke-linejoin="round"/>',
    'shopping-cart' => '<path d="M5.12 7h16l-1.65 7.43a2 2 0 01-1.95 1.57H8.74a2 2 0 01-2-1.58L5.12 7z" fill="currentColor" opacity="0.2"/><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 002 1.58h9.78a2 2 0 001.95-1.57l1.65-7.43H5.12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    'settings' => '<circle cx="12" cy="12" r="3" fill="currentColor" opacity="0.2"/><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke-width="2"/>',
    'megaphone' => '<path d="M3 11l18-5v12L3 13v-2z" fill="currentColor" opacity="0.2"/><path d="M3 11l18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 11-5.8-1.6" stroke-width="2" stroke-linecap="round"/>',
    'palette' => '<path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 011.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.555C21.965 6.012 17.461 2 12 2z" fill="currentColor" opacity="0.2"/><circle cx="13.5" cy="6.5" r="1.5" fill="currentColor"/><circle cx="17.5" cy="10.5" r="1.5" fill="currentColor"/><circle cx="8.5" cy="7.5" r="1.5" fill="currentColor"/><circle cx="6.5" cy="12.5" r="1.5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 011.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.555C21.965 6.012 17.461 2 12 2z" stroke-width="2"/>',
    'navigation' => '<polygon points="3 11 22 2 13 21 11 13 3 11" fill="currentColor" opacity="0.2"/><polygon points="3 11 22 2 13 21 11 13 3 11" stroke-width="2" stroke-linejoin="round"/>',
    'sparkles' => '<path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z" fill="currentColor" opacity="0.2"/><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z" stroke-width="2" stroke-linejoin="round"/><path d="M5 3v2M19 19v2M3 5h2M19 19h2" stroke-width="2" stroke-linecap="round"/>',
  ];

  $icons = $variant === 'duotone' ? $icons_duotone : $icons_outline;

  if (!isset($icons[$name])) {
    // Fallback a outline si duotone no existe
    if ($variant === 'duotone' && isset($icons_outline[$name])) {
      $icons = $icons_outline;
    } else {
      return '';
    }
  }

  return sprintf(
    '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="%s" style="vertical-align: middle; margin-right: 8px;">%s</svg>',
    $size,
    $size,
    $color,
    $icons[$name]
  );
}

/**
 * Genera un título de tab con icono Unicode.
 *
 * Nota: Los vertical tabs de Drupal usan textContent para copiar labels,
 * por lo que SVG inline no funciona. Usamos caracteres Unicode/emoji.
 *
 * @param string $icon_name
 *   Nombre del icono (mapea a carácter Unicode).
 * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
 *   Texto del label.
 *
 * @return string
 *   Texto con icono + label.
 */
function _ecosistema_jaraba_theme_tab_title(string $icon_name, $label): string {
  // Mapeo de iconos a caracteres Unicode/emoji
  $icons = [
    'sparkles' => '✨',
    'building' => '🏢',
    'typography' => '🔤',
    'layout-navbar' => '📌',
    'image' => '🖼️',
    'layout-cards' => '🃏',
    'layout-footer' => '📋',
    'layout-sidebar' => '📐',
    'puzzle' => '🧩',
    'shopping-cart' => '🛒',
    'settings' => '⚙️',
    'megaphone' => '📢',
    'palette' => '🎨',
    'navigation' => '🧭',
  ];
  
  $icon = $icons[$icon_name] ?? '•';
  $label_string = is_object($label) ? (string) $label : $label;
  
  return $icon . ' ' . $label_string;
}
