<?php

declare(strict_types=1);

namespace Drupal\jaraba_theming\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_theming\Service\ThemeTokenService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de vista previa de tema.
 *
 * PROPÓSITO:
 * Renderiza una página de vista previa que muestra componentes de ejemplo
 * (tarjetas, botones, tipografía, encabezados) con los tokens de tema
 * aplicados vía variables CSS inline.
 *
 * LÓGICA:
 * - Acepta parámetros de consulta para overrides de color (color_primary,
 *   color_secondary, etc.) o un config_id para cargar una configuración
 *   guardada de TenantThemeConfig.
 * - Genera CSS con las variables del tema y lo inyecta en la página.
 * - Renderiza componentes de muestra para previsualizar el resultado.
 *
 * RELACIONES:
 * - Consume ThemeTokenService para obtener tokens activos.
 * - Consume TenantThemeConfig entity cuando se proporciona config_id.
 */
class ThemePreviewController extends ControllerBase {

  /**
   * Servicio de tokens de tema.
   *
   * @var \Drupal\jaraba_theming\Service\ThemeTokenService
   */
  protected ThemeTokenService $themeTokenService;

  /**
   * Constructor del controlador.
   *
   * @param \Drupal\jaraba_theming\Service\ThemeTokenService $theme_token_service
   *   Servicio de tokens de tema.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad.
   */
  public function __construct(
    ThemeTokenService $theme_token_service,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->themeTokenService = $theme_token_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_theming.token_service'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Renderiza la vista previa del tema.
   *
   * Acepta los siguientes query parameters:
   * - config_id: ID de una TenantThemeConfig guardada.
   * - color_primary: Override del color primario.
   * - color_secondary: Override del color secundario.
   * - color_accent: Override del color de acento.
   * - color_dark: Override del color oscuro.
   * - font_headings: Override de la familia tipográfica de títulos.
   * - font_body: Override de la familia tipográfica de cuerpo.
   * - border_radius: Override del radio de bordes.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto de petición HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Respuesta HTML con la vista previa del tema.
   */
  public function preview(Request $request): Response {
    $configId = $request->query->get('config_id');
    $css = '';

    if ($configId) {
      // Cargar configuración guardada.
      $config = $this->entityTypeManager
        ->getStorage('tenant_theme_config')
        ->load((int) $configId);

      if ($config) {
        $css = $config->generateCssVariables();
      }
    }

    // Aplicar overrides desde query parameters.
    $overrides = $this->extractOverrides($request);

    if (!empty($overrides)) {
      $css = $this->buildOverrideCss($overrides, $css);
    }

    // Si no hay CSS aún, usar el CSS por defecto del servicio.
    if (empty($css)) {
      $css = $this->themeTokenService->generateCss();
    }

    $html = $this->buildPreviewHtml($css);

    return new Response($html, 200, [
      'Content-Type' => 'text/html; charset=utf-8',
      'Cache-Control' => 'no-cache, no-store, must-revalidate',
    ]);
  }

  /**
   * Extrae los overrides de color/fuente desde los query parameters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto de petición HTTP.
   *
   * @return array
   *   Array asociativo con los overrides encontrados.
   */
  protected function extractOverrides(Request $request): array {
    $overrides = [];
    $allowedParams = [
      'color_primary' => '--ej-color-primary',
      'color_secondary' => '--ej-color-secondary',
      'color_accent' => '--ej-color-accent',
      'color_dark' => '--ej-color-dark',
      'font_headings' => '--ej-font-family-headings',
      'font_body' => '--ej-font-family-body',
      'border_radius' => '--ej-border-radius',
    ];

    foreach ($allowedParams as $param => $cssVar) {
      $value = $request->query->get($param);
      if ($value !== NULL && $value !== '') {
        $overrides[$cssVar] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
      }
    }

    return $overrides;
  }

  /**
   * Construye CSS con los overrides aplicados.
   *
   * @param array $overrides
   *   Array asociativo de variable CSS => valor.
   * @param string $baseCss
   *   CSS base sobre el que aplicar los overrides.
   *
   * @return string
   *   CSS resultante con los overrides incluidos.
   */
  protected function buildOverrideCss(array $overrides, string $baseCss): string {
    $overrideLines = [];
    foreach ($overrides as $cssVar => $value) {
      // Envolver fuentes entre comillas si contienen espacios.
      if (str_starts_with($cssVar, '--ej-font-family') && !str_starts_with($value, "'")) {
        $value = "'" . $value . "', sans-serif";
      }
      // Añadir 'px' al border-radius si es solo un número.
      if ($cssVar === '--ej-border-radius' && is_numeric($value)) {
        $value .= 'px';
      }
      $overrideLines[] = "  {$cssVar}: {$value};";
    }

    $overrideCss = ":root {\n" . implode("\n", $overrideLines) . "\n}";

    if (!empty($baseCss)) {
      return $baseCss . "\n" . $overrideCss;
    }

    return $overrideCss;
  }

  /**
   * Construye la página HTML completa de vista previa.
   *
   * Genera una página HTML autónoma con componentes de muestra
   * (tarjetas, botones, tipografía, encabezados) estilizados con
   * las variables CSS del tema.
   *
   * @param string $css
   *   CSS con las variables de tema a aplicar.
   *
   * @return string
   *   HTML completo de la página de vista previa.
   */
  protected function buildPreviewHtml(string $css): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vista Previa del Tema</title>
  <style>
    {$css}

    /* Estilos base de la vista previa */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: var(--ej-font-family-body, 'Inter', sans-serif);
      background: #f5f5f5;
      color: var(--ej-color-dark, #1a1a2e);
      padding: 2rem;
      line-height: 1.6;
    }
    h1, h2, h3, h4 {
      font-family: var(--ej-font-family-headings, 'Outfit', sans-serif);
    }

    /* Contenedor */
    .preview-container { max-width: 1200px; margin: 0 auto; }

    /* Header de vista previa */
    .preview-header {
      background: var(--ej-color-primary, #FF8C42);
      color: white;
      padding: 2rem;
      border-radius: var(--ej-border-radius, 8px);
      margin-bottom: 2rem;
    }
    .preview-header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
    .preview-header p { opacity: 0.9; }

    /* Secciones */
    .preview-section {
      background: white;
      padding: 2rem;
      border-radius: var(--ej-border-radius, 8px);
      margin-bottom: 1.5rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .preview-section h2 {
      font-size: 1.5rem;
      margin-bottom: 1rem;
      color: var(--ej-color-dark, #1a1a2e);
      border-bottom: 2px solid var(--ej-color-primary, #FF8C42);
      padding-bottom: 0.5rem;
    }

    /* Paleta de colores */
    .color-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
    .color-swatch {
      border-radius: var(--ej-border-radius, 8px);
      padding: 2rem 1rem 1rem;
      color: white;
      text-align: center;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .color-primary { background: var(--ej-color-primary, #FF8C42); }
    .color-secondary { background: var(--ej-color-secondary, #00A9A5); }
    .color-accent { background: var(--ej-color-accent, #233D63); }
    .color-dark { background: var(--ej-color-dark, #1a1a2e); }

    /* Tipografia */
    .typography-sample h3 { font-size: 1.25rem; margin-bottom: 0.75rem; }
    .typography-sample p { margin-bottom: 0.5rem; }
    .font-heading { font-family: var(--ej-font-family-headings, 'Outfit', sans-serif); font-size: 1.75rem; }
    .font-body { font-family: var(--ej-font-family-body, 'Inter', sans-serif); }

    /* Botones */
    .button-grid { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; }
    .btn {
      display: inline-block;
      padding: 0.75rem 1.5rem;
      border-radius: var(--ej-border-radius, 8px);
      font-weight: 600;
      font-size: 0.9rem;
      text-decoration: none;
      cursor: pointer;
      border: 2px solid transparent;
      transition: opacity 0.2s;
    }
    .btn:hover { opacity: 0.85; }
    .btn-primary {
      background: var(--ej-color-primary, #FF8C42);
      color: white;
    }
    .btn-secondary {
      background: var(--ej-color-secondary, #00A9A5);
      color: white;
    }
    .btn-accent {
      background: var(--ej-color-accent, #233D63);
      color: white;
    }
    .btn-outline {
      background: transparent;
      border-color: var(--ej-color-primary, #FF8C42);
      color: var(--ej-color-primary, #FF8C42);
    }

    /* Tarjetas */
    .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
    .card {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: var(--ej-border-radius, 8px);
      overflow: hidden;
      transition: box-shadow 0.2s;
    }
    .card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .card-header {
      padding: 1.25rem;
      color: white;
    }
    .card-header-primary { background: var(--ej-color-primary, #FF8C42); }
    .card-header-secondary { background: var(--ej-color-secondary, #00A9A5); }
    .card-header-accent { background: var(--ej-color-accent, #233D63); }
    .card-body { padding: 1.25rem; }
    .card-body h3 { margin-bottom: 0.5rem; }
    .card-body p { color: #6b7280; font-size: 0.9rem; }

    /* Alertas */
    .alert {
      padding: 1rem 1.25rem;
      border-radius: var(--ej-border-radius, 8px);
      margin-bottom: 0.75rem;
      font-size: 0.9rem;
    }
    .alert-primary {
      background: color-mix(in srgb, var(--ej-color-primary, #FF8C42) 15%, white);
      border-left: 4px solid var(--ej-color-primary, #FF8C42);
      color: var(--ej-color-dark, #1a1a2e);
    }
    .alert-secondary {
      background: color-mix(in srgb, var(--ej-color-secondary, #00A9A5) 15%, white);
      border-left: 4px solid var(--ej-color-secondary, #00A9A5);
      color: var(--ej-color-dark, #1a1a2e);
    }
  </style>
</head>
<body>
  <div class="preview-container">
    <!-- Header -->
    <div class="preview-header">
      <h1>Vista Previa del Tema</h1>
      <p>Previsualiza los colores, tipografias y componentes del tema configurado.</p>
    </div>

    <!-- Paleta de Colores -->
    <div class="preview-section">
      <h2>Paleta de Colores</h2>
      <div class="color-grid">
        <div class="color-swatch color-primary">Primary</div>
        <div class="color-swatch color-secondary">Secondary</div>
        <div class="color-swatch color-accent">Accent</div>
        <div class="color-swatch color-dark">Dark</div>
      </div>
    </div>

    <!-- Tipografia -->
    <div class="preview-section">
      <h2>Tipografia</h2>
      <div class="typography-sample">
        <p class="font-heading">Fuente de Titulos (Headings)</p>
        <p class="font-body">Fuente de Cuerpo (Body): Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
        <h3>Encabezado H3</h3>
        <h4>Encabezado H4</h4>
        <p>Texto normal con <strong>negrita</strong> y <em>cursiva</em>.</p>
      </div>
    </div>

    <!-- Botones -->
    <div class="preview-section">
      <h2>Botones</h2>
      <div class="button-grid">
        <span class="btn btn-primary">Primario</span>
        <span class="btn btn-secondary">Secundario</span>
        <span class="btn btn-accent">Acento</span>
        <span class="btn btn-outline">Outline</span>
      </div>
    </div>

    <!-- Tarjetas -->
    <div class="preview-section">
      <h2>Tarjetas</h2>
      <div class="card-grid">
        <div class="card">
          <div class="card-header card-header-primary">
            <h3>Servicio Premium</h3>
          </div>
          <div class="card-body">
            <h3>Plan Profesional</h3>
            <p>Acceso completo a todas las funcionalidades de la plataforma con soporte prioritario.</p>
          </div>
        </div>
        <div class="card">
          <div class="card-header card-header-secondary">
            <h3>Formacion Online</h3>
          </div>
          <div class="card-body">
            <h3>Cursos Disponibles</h3>
            <p>Catalogo de cursos con certificacion oficial y seguimiento personalizado.</p>
          </div>
        </div>
        <div class="card">
          <div class="card-header card-header-accent">
            <h3>Comunidad</h3>
          </div>
          <div class="card-body">
            <h3>Red de Contactos</h3>
            <p>Conecta con otros profesionales y empresas de tu sector.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Alertas -->
    <div class="preview-section">
      <h2>Alertas y Notificaciones</h2>
      <div class="alert alert-primary">
        <strong>Informacion:</strong> Este es un mensaje informativo con el color primario del tema.
      </div>
      <div class="alert alert-secondary">
        <strong>Exito:</strong> Este es un mensaje de exito con el color secundario del tema.
      </div>
    </div>
  </div>
</body>
</html>
HTML;
  }

}
