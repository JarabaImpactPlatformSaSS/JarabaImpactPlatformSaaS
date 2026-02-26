<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * GAP-AUD-023: Design System Documentation controller.
 *
 * Renders a living design system page at /admin/design-system that displays
 * the official Jaraba color palette, typography specimens, spacing scale,
 * icon gallery, and component previews.
 */
class ComponentDocumentationController extends ControllerBase {

  /**
   * Renders the design system documentation page.
   *
   * @return array
   *   Render array for the design system page.
   */
  public function page(): array {
    $colors = $this->getColorPalette();
    $typography = $this->getTypography();
    $spacing = $this->getSpacingScale();
    $shadows = $this->getShadows();
    $breakpoints = $this->getBreakpoints();
    $icons = $this->getIconCategories();
    $components = $this->getComponentList();

    return [
      '#theme' => 'design_system_page',
      '#colors' => $colors,
      '#typography' => $typography,
      '#spacing' => $spacing,
      '#shadows' => $shadows,
      '#breakpoints' => $breakpoints,
      '#icons' => $icons,
      '#components' => $components,
      '#attached' => [
        'library' => ['ecosistema_jaraba_theme/design-system'],
      ],
    ];
  }

  /**
   * Returns the official Jaraba color palette.
   */
  protected function getColorPalette(): array {
    return [
      'brand' => [
        ['name' => 'Corporate', 'var' => '$ej-color-corporate', 'hex' => '#233D63', 'usage' => 'Azul corporativo — headers, nav, logos'],
        ['name' => 'Impulse', 'var' => '$ej-color-impulse', 'hex' => '#FF8C42', 'usage' => 'Naranja — CTAs, botones primarios, highlights'],
        ['name' => 'Innovation', 'var' => '$ej-color-innovation', 'hex' => '#00A9A5', 'usage' => 'Turquesa — talento, innovacion, secondary actions'],
        ['name' => 'Agro', 'var' => '$ej-color-agro', 'hex' => '#556B2F', 'usage' => 'Verde oliva — AgroConecta vertical'],
      ],
      'ui' => [
        ['name' => 'Success', 'var' => '$ej-color-success', 'hex' => '#10B981', 'usage' => 'Confirmaciones, estados completados'],
        ['name' => 'Warning', 'var' => '$ej-color-warning', 'hex' => '#F59E0B', 'usage' => 'Alertas, estados pendientes'],
        ['name' => 'Danger', 'var' => '$ej-color-danger', 'hex' => '#EF4444', 'usage' => 'Errores, acciones destructivas'],
        ['name' => 'Neutral', 'var' => '$ej-color-neutral', 'hex' => '#64748B', 'usage' => 'Texto secundario, bordes, iconos'],
      ],
      'backgrounds' => [
        ['name' => 'Body', 'var' => '$ej-bg-body', 'hex' => '#F8FAFC', 'usage' => 'Fondo de pagina'],
        ['name' => 'Surface', 'var' => '$ej-bg-surface', 'hex' => '#FFFFFF', 'usage' => 'Cards, modales, paneles'],
        ['name' => 'Dark', 'var' => '$ej-bg-dark', 'hex' => '#1A1A2E', 'usage' => 'Fondos oscuros, dark mode base'],
      ],
    ];
  }

  /**
   * Returns typography tokens.
   */
  protected function getTypography(): array {
    return [
      'families' => [
        ['name' => 'Headings', 'var' => '$ej-font-headings', 'value' => 'Outfit, sans-serif'],
        ['name' => 'Body', 'var' => '$ej-font-body', 'value' => 'Outfit, sans-serif'],
      ],
      'colors' => [
        ['name' => 'Headings', 'var' => '$ej-color-headings', 'hex' => '#1A1A2E'],
        ['name' => 'Body', 'var' => '$ej-color-body', 'hex' => '#334155'],
        ['name' => 'Muted', 'var' => '$ej-color-muted', 'hex' => '#64748B'],
      ],
      'sizes' => [
        ['label' => 'H1', 'size' => '2.5rem', 'weight' => '700'],
        ['label' => 'H2', 'size' => '2rem', 'weight' => '600'],
        ['label' => 'H3', 'size' => '1.5rem', 'weight' => '600'],
        ['label' => 'H4', 'size' => '1.25rem', 'weight' => '600'],
        ['label' => 'Body', 'size' => '1rem (16px)', 'weight' => '400'],
        ['label' => 'Small', 'size' => '0.875rem', 'weight' => '400'],
        ['label' => 'Caption', 'size' => '0.75rem', 'weight' => '400'],
      ],
    ];
  }

  /**
   * Returns spacing scale tokens.
   */
  protected function getSpacingScale(): array {
    return [
      ['name' => 'xs', 'var' => '$ej-spacing-xs', 'value' => '0.25rem (4px)'],
      ['name' => 'sm', 'var' => '$ej-spacing-sm', 'value' => '0.5rem (8px)'],
      ['name' => 'md', 'var' => '$ej-spacing-md', 'value' => '1rem (16px)'],
      ['name' => 'lg', 'var' => '$ej-spacing-lg', 'value' => '1.5rem (24px)'],
      ['name' => 'xl', 'var' => '$ej-spacing-xl', 'value' => '2rem (32px)'],
      ['name' => '2xl', 'var' => '$ej-spacing-2xl', 'value' => '3rem (48px)'],
    ];
  }

  /**
   * Returns shadow tokens.
   */
  protected function getShadows(): array {
    return [
      ['name' => 'Small', 'var' => '$ej-shadow-sm', 'value' => '0 1px 2px rgba(0,0,0,0.05)'],
      ['name' => 'Medium', 'var' => '$ej-shadow-md', 'value' => '0 4px 6px rgba(0,0,0,0.07)'],
      ['name' => 'Large', 'var' => '$ej-shadow-lg', 'value' => '0 10px 15px rgba(0,0,0,0.1)'],
      ['name' => 'Hover', 'var' => '$ej-hover-shadow', 'value' => '0 4px 12px rgba(0,0,0,0.12)'],
    ];
  }

  /**
   * Returns responsive breakpoints.
   */
  protected function getBreakpoints(): array {
    return [
      ['name' => 'xs', 'var' => '$ej-breakpoint-xs', 'value' => '480px', 'usage' => 'Small phones'],
      ['name' => 'sm', 'var' => '$ej-breakpoint-sm', 'value' => '640px', 'usage' => 'Large phones'],
      ['name' => 'md', 'var' => '$ej-breakpoint-md', 'value' => '768px', 'usage' => 'Tablets'],
      ['name' => 'lg', 'var' => '$ej-breakpoint-lg', 'value' => '992px', 'usage' => 'Small desktops'],
      ['name' => 'xl', 'var' => '$ej-breakpoint-xl', 'value' => '1200px', 'usage' => 'Large desktops'],
      ['name' => '2xl', 'var' => '$ej-breakpoint-2xl', 'value' => '1440px', 'usage' => 'Wide screens'],
    ];
  }

  /**
   * Returns icon categories from jaraba_icon().
   */
  protected function getIconCategories(): array {
    return [
      'ui' => ['search', 'close', 'menu', 'arrow-right', 'arrow-left', 'check', 'plus', 'minus', 'edit', 'delete', 'copy', 'download', 'upload', 'link', 'external', 'sparkles', 'bell', 'settings', 'filter'],
      'social' => ['linkedin', 'twitter', 'facebook', 'instagram', 'whatsapp', 'email', 'phone'],
      'status' => ['success', 'warning', 'error', 'info', 'pending'],
      'navigation' => ['home', 'dashboard', 'user', 'users', 'calendar', 'chart', 'document', 'folder'],
      'commerce' => ['cart', 'payment', 'shipping', 'product', 'store', 'receipt'],
    ];
  }

  /**
   * Returns list of theme components with their SCSS file.
   */
  protected function getComponentList(): array {
    $componentsDir = DRUPAL_ROOT . '/themes/custom/ecosistema_jaraba_theme/scss/components';
    $components = [];

    if (is_dir($componentsDir)) {
      $files = glob($componentsDir . '/_*.scss');
      foreach ($files as $file) {
        $name = str_replace(['_', '.scss'], ['', ''], basename($file));
        $components[] = [
          'name' => $name,
          'file' => 'scss/components/_' . $name . '.scss',
        ];
      }
      sort($components);
    }

    return $components;
  }

}
