<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

/**
 * Servicio centralizado de sanitización para canvas HTML/CSS.
 *
 * CANVAS-SANITIZER-EXTRACT-001: Extrae la lógica duplicada de sanitización
 * que existía en CanvasApiController (jaraba_page_builder) y
 * ArticleCanvasApiController (jaraba_content_hub).
 *
 * Cubre vectores XSS:
 * - <script> tags y contenido
 * - Event handlers on* (onclick, onerror, onload, etc.)
 * - Atributos javascript: en href/src/action/data/formaction
 * - <object> y <embed> (vectores Flash/plugin)
 * - Atributos residuales de GrapesJS editor (data-gjs-*, clases gjs-*)
 * - CSS injection (javascript:, expression(), @import, behavior, -moz-binding)
 *
 * @see AUDIT-SEC-003
 */
class CanvasSanitizationService {

  /**
   * Sanitiza HTML con lista blanca ampliada para Page Builder.
   *
   * Elimina vectores XSS preservando tags necesarios para bloques
   * premium (svg, form, input, button, video, iframe, canvas, picture).
   *
   * @param string $html
   *   HTML a sanitizar.
   *
   * @return string
   *   HTML sanitizado.
   */
  public function sanitizePageBuilderHtml(string $html): string {
    if ($html === '') {
      return '';
    }

    // Eliminar <script> tags y su contenido.
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

    // Eliminar event handlers on* (onclick, onerror, onload, etc.).
    $html = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

    // Eliminar atributos javascript: en href/src/action/data/formaction.
    $html = preg_replace('/\s+(href|src|action|data|formaction)\s*=\s*(?:"javascript:[^"]*"|\'javascript:[^\']*\')/i', '', $html);

    // Eliminar <object> y <embed> (vectores Flash/plugin).
    $html = preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $html);
    $html = preg_replace('/<embed\b[^>]*\/?>/i', '', $html);

    return $html;
  }

  /**
   * Sanitiza HTML para almacenamiento público.
   *
   * Aplica sanitizePageBuilderHtml() + limpieza de atributos
   * residuales del editor GrapesJS.
   *
   * @param string $html
   *   HTML a sanitizar.
   *
   * @return string
   *   HTML sanitizado.
   */
  public function sanitizeHtml(string $html): string {
    // Paso 1: Sanitización XSS con lista blanca ampliada para Page Builder.
    $html = $this->sanitizePageBuilderHtml($html);

    // Paso 2: Limpiar atributos residuales de GrapesJS editor.
    $html = preg_replace('/\s+data-gjs-[^=]+="[^"]*"/i', '', $html);

    // Paso 3: Solo eliminar clases gjs-*, preservando las demás.
    $html = preg_replace_callback(
      '/\sclass="([^"]*)"/i',
      function ($matches) {
        $classes = preg_split('/\s+/', $matches[1]);
        $filtered = array_filter($classes, fn($c) => !str_starts_with($c, 'gjs-'));
        if ($filtered === []) {
          return '';
        }
        return ' class="' . implode(' ', $filtered) . '"';
      },
      $html
    );

    return trim($html);
  }

  /**
   * Sanitiza CSS para prevenir inyección de código.
   *
   * @param string $css
   *   CSS a sanitizar.
   *
   * @return string
   *   CSS sanitizado.
   *
   * @see AUDIT-SEC-003
   */
  public function sanitizeCss(string $css): string {
    $css = preg_replace('/javascript\s*:/i', '', $css);
    $css = preg_replace('/expression\s*\(/i', '', $css);
    $css = preg_replace('/@import\b/i', '', $css);
    $css = preg_replace('/behavior\s*:/i', '', $css);
    $css = preg_replace('/-moz-binding\s*:/i', '', $css);

    return $css;
  }

}
