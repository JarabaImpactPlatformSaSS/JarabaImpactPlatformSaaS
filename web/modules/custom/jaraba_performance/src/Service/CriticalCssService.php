<?php

declare(strict_types=1);

namespace Drupal\jaraba_performance\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Servicio de gestión de CSS crítico para optimización de Core Web Vitals.
 *
 * ¿Qué problema resuelve?
 * -----------------------
 * Los archivos CSS del theme (~778KB) bloquean el renderizado de la página,
 * causando métricas LCP/FCP pobres. Este servicio implementa la técnica de
 * "CSS crítico" que:
 *
 * 1. Identifica qué CSS es necesario para el contenido "above the fold"
 *    (visible sin hacer scroll).
 * 2. Inyecta ese CSS directamente en el <head> como <style> inline.
 * 3. Permite cargar el resto del CSS de forma asíncrona sin bloquear.
 *
 * ¿Cómo funciona?
 * ---------------
 * 1. Un script NPM (generate-critical.js) analiza las páginas clave y extrae
 *    el CSS necesario para el viewport inicial (1300x900px).
 * 2. Estos archivos se guardan en /themes/custom/ecosistema_jaraba_theme/css/critical/
 * 3. Este servicio mapea la ruta actual a su archivo CSS crítico correspondiente.
 * 4. El hook_page_attachments_alter() inyecta el CSS inline.
 *
 * Archivos CSS críticos generados:
 * - homepage.css: Página de inicio y landings genéricas
 * - templates.css: Selector de plantillas del Page Builder
 * - landing-empleo.css: Landings de verticales (empleo, talento, emprender)
 * - admin-pages.css: Dashboards administrativos de Jaraba
 *
 * Métricas objetivo (Gap F del Plan de Elevación):
 * - LCP: De 2.5s a <2.0s (-20%)
 * - FCP: De 1.8s a <1.2s (-33%)
 * - CSS bloqueante: De 778KB a <50KB inline (-94%)
 *
 * @see docs/planificacion/20260202-Auditoria_Plan_Elevacion_Clase_Mundial_v1.md
 * @see https://web.dev/critical-rendering-path/
 */
class CriticalCssService
{

    /**
     * Nombre del theme donde se almacenan los CSS críticos.
     *
     * Los archivos CSS críticos se generan específicamente para este theme
     * ya que contienen reglas extraídas de sus stylesheets compilados.
     */
    private const THEME_NAME = 'ecosistema_jaraba_theme';

    /**
     * Directorio relativo dentro del theme para CSS críticos.
     */
    private const CRITICAL_CSS_DIR = 'css/critical';

    /**
     * Mapa de rutas a archivos CSS críticos.
     *
     * Las rutas se mapean a nombres de archivo (sin extensión).
     * El archivo final será: css/critical/{nombre}.css
     */
    private const ROUTE_CSS_MAP = [
        // Homepage y landings principales
        '<front>' => 'homepage',

        // Page Builder
        'jaraba_page_builder.templates' => 'templates',
        'jaraba_page_builder.template_preview' => 'templates',

        // Site Builder
        'jaraba_site_builder.pages' => 'admin-pages',
        'jaraba_site_builder.homepage' => 'admin-pages',

        // Landings verticales
        'jaraba_landing.empleo' => 'landing-empleo',
        'jaraba_landing.talento' => 'landing-empleo',
        'jaraba_landing.emprender' => 'landing-empleo',
        'jaraba_landing.comercio' => 'landing-empleo',
        'jaraba_landing.instituciones' => 'landing-empleo',
    ];

    /**
     * Caché del path del theme.
     */
    private ?string $themePath = NULL;

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
     *   El servicio de coincidencia de rutas.
     * @param \Drupal\Core\Extension\ThemeExtensionList $themeList
     *   Lista de extensiones de temas.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   Fábrica de configuración.
     */
    public function __construct(
        private RouteMatchInterface $routeMatch,
        private ThemeExtensionList $themeList,
        private ConfigFactoryInterface $configFactory,
    ) {
    }

    /**
     * Determina si el CSS crítico está habilitado globalmente.
     *
     * Por defecto está habilitado (TRUE). Se puede deshabilitar configurando
     * 'critical_css_enabled' a FALSE en jaraba_performance.settings.
     *
     * @return bool
     *   TRUE si está habilitado, FALSE en caso contrario.
     */
    public function isEnabled(): bool
    {
        // Obtener el valor de configuración. Si no existe, usar TRUE por defecto.
        // Nota: Usamos paréntesis para asegurar precedencia correcta del operador ??.
        $configValue = $this->configFactory
            ->get('jaraba_performance.settings')
            ->get('critical_css_enabled');

        // Si no hay configuración (NULL), habilitado por defecto.
        return $configValue ?? TRUE;
    }

    /**
     * Obtiene el nombre del archivo CSS crítico para la ruta actual.
     *
     * @return string|null
     *   Nombre del archivo (sin extensión) o NULL si no hay mapeo.
     */
    public function getCriticalCssFile(): ?string
    {
        $routeName = $this->routeMatch->getRouteName();

        // Coincidir ruta exacta.
        if (isset(self::ROUTE_CSS_MAP[$routeName])) {
            return self::ROUTE_CSS_MAP[$routeName];
        }

        // Patrón para rutas admin genéricas.
        if (str_starts_with($routeName ?? '', 'jaraba_') && str_contains($routeName ?? '', 'admin')) {
            return 'admin-pages';
        }

        // Fallback a homepage para páginas públicas.
        return 'homepage';
    }

    /**
     * Obtiene la ruta completa al archivo CSS crítico.
     *
     * @param string $filename
     *   Nombre del archivo (sin extensión .css).
     *
     * @return string
     *   Ruta absoluta al archivo CSS crítico.
     */
    public function getCriticalCssPath(string $filename): string
    {
        return $this->getThemePath() . '/' . self::CRITICAL_CSS_DIR . '/' . $filename . '.css';
    }

    /**
     * Verifica si existe el archivo CSS crítico para la ruta actual.
     *
     * @return bool
     *   TRUE si el archivo existe, FALSE en caso contrario.
     */
    public function hasCriticalCss(): bool
    {
        $file = $this->getCriticalCssFile();
        if (!$file) {
            return FALSE;
        }

        return file_exists($this->getCriticalCssPath($file));
    }

    /**
     * Obtiene el contenido del CSS crítico para la ruta actual.
     *
     * @return string|null
     *   Contenido del CSS crítico o NULL si no existe.
     */
    public function getCriticalCssContent(): ?string
    {
        if (!$this->isEnabled() || !$this->hasCriticalCss()) {
            return NULL;
        }

        $file = $this->getCriticalCssFile();
        $path = $this->getCriticalCssPath($file);

        $content = file_get_contents($path);
        return $content !== FALSE ? $content : NULL;
    }

    /**
     * Obtiene la ruta del theme activo.
     *
     * @return string
     *   Ruta absoluta al directorio del theme.
     */
    private function getThemePath(): string
    {
        if ($this->themePath === NULL) {
            $this->themePath = $this->themeList->getPath(self::THEME_NAME);
        }

        return $this->themePath;
    }

    /**
     * Obtiene la lista de rutas mapeadas (para debugging/admin).
     *
     * @return array<string, string>
     *   Array asociativo de ruta => archivo CSS.
     */
    public function getMappedRoutes(): array
    {
        return self::ROUTE_CSS_MAP;
    }

}
