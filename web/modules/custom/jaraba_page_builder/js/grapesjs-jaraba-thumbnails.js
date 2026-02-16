/**
 * @file
 * Jaraba Canvas Editor — Registro Centralizado de Thumbnails SVG.
 *
 * PROPÓSITO:
 * Define thumbnails SVG premium (duotone) para todos los bloques y categorías
 * del Canvas Editor GrapesJS. Centralizando los SVGs aquí, evitamos duplicación
 * en grapesjs-jaraba-blocks.js y facilitamos el mantenimiento.
 *
 * ESTILO SVG:
 * - Duotone: Capa de fondo con opacity 0.2 + capa principal stroke sólido.
 * - Dimensiones: 40×40 viewBox 0 0 24 24 (consistente con Lucide).
 * - Colores: currentColor (heredan del contexto CSS).
 *
 * USO:
 *   const svg = Drupal.jarabaThumbnails.get('hero-simple');
 *   // Retorna el SVG string duotone para ese bloque.
 *
 * @see docs/planificacion/20260209-Plan_Mejoras_Page_Site_Builder_v3.md §4
 * @see /.agent/workflows/scss-estilos.md (directriz de iconografía SVG)
 */

(function (Drupal) {
    'use strict';

    /**
     * Wrapper SVG con dimensiones estándar.
     *
     * @param {string} inner Contenido SVG interno (paths).
     * @return {string} SVG completo.
     */
    function svg(inner) {
        return `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">${inner}</svg>`;
    }

    /**
     * Capa de fondo duotone (opacity 0.2).
     *
     * @param {string} d Path data.
     * @return {string} Path SVG con opacity duotone.
     */
    function bg(d) {
        return `<path d="${d}" fill="currentColor" opacity="0.2"/>`;
    }

    /**
     * Capa principal stroke.
     *
     * @param {string} d Path data.
     * @param {Object} opts Opciones adicionales.
     * @return {string} Path SVG con stroke.
     */
    function stroke(d, opts = {}) {
        const sw = opts.strokeWidth || 2;
        const lc = opts.linecap || 'round';
        const lj = opts.linejoin || 'round';
        return `<path d="${d}" stroke="currentColor" stroke-width="${sw}" stroke-linecap="${lc}" stroke-linejoin="${lj}" fill="none"/>`;
    }

    /**
     * Rectángulo: capa de fondo + stroke.
     */
    function rect(x, y, w, h, rx = 2) {
        return `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${rx}" fill="currentColor" opacity="0.2"/>`
            + `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${rx}" stroke="currentColor" stroke-width="2" fill="none"/>`;
    }

    /**
     * Círculo SVG.
     */
    function circle(cx, cy, r, filled = false) {
        if (filled) {
            return `<circle cx="${cx}" cy="${cy}" r="${r}" fill="currentColor" opacity="0.2"/>`
                + `<circle cx="${cx}" cy="${cy}" r="${r}" stroke="currentColor" stroke-width="2" fill="none"/>`;
        }
        return `<circle cx="${cx}" cy="${cy}" r="${r}" stroke="currentColor" stroke-width="2" fill="none"/>`;
    }

    // =========================================================================
    // REGISTRO DE THUMBNAILS
    // =========================================================================

    const thumbnails = {};

    // -------------------------------------------------------------------------
    // BASIC — Tipografía y elementos básicos
    // -------------------------------------------------------------------------

    thumbnails['heading-h1'] = svg(
        bg('M2 4h20v16H2z') +
        stroke('M6 12h4M6 16V8M10 16V8') +
        stroke('M15 8l2 2-2 2M17 16V8')
    );

    thumbnails['heading-h2'] = svg(
        bg('M2 4h20v16H2z') +
        stroke('M5 12h4M5 16V8M9 16V8') +
        stroke('M19 16h-4c0-4 4-3 4-6 0-1.5-2-2.5-4-1')
    );

    thumbnails['heading-h3'] = svg(
        bg('M2 4h20v16H2z') +
        stroke('M5 12h4M5 16V8M9 16V8') +
        stroke('M15.5 9.5c1.7-1 3.5 0 3.5 1.5a2 2 0 01-2 2m2 0a2 2 0 01-2 2c-1.7 0-3.5-1-3.5-2')
    );

    thumbnails['heading-h4'] = svg(
        bg('M2 4h20v16H2z') +
        stroke('M5 12h4M5 16V8M9 16V8') +
        stroke('M15 8v4h4M19 8v8')
    );

    thumbnails['paragraph'] = svg(
        bg('M2 4h20v16H2z') +
        stroke('M4 7h16M4 11h16M4 15h10')
    );

    thumbnails['text-block'] = svg(
        bg('M2 4h20v16H2z') +
        stroke('M5 7h14M5 11h14M5 15h8')
    );

    thumbnails['link'] = svg(
        bg('M2 4h20v16H2z') +
        stroke('M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71') +
        stroke('M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71')
    );

    thumbnails['image'] = svg(
        rect(3, 3, 18, 18) +
        circle(8.5, 8.5, 1.5) +
        stroke('M21 15l-5-5L5 21')
    );

    thumbnails['button'] = svg(
        `<rect x="3" y="8" width="18" height="8" rx="4" fill="currentColor" opacity="0.2"/>` +
        `<rect x="3" y="8" width="18" height="8" rx="4" stroke="currentColor" stroke-width="2" fill="none"/>` +
        stroke('M8 12h8')
    );

    thumbnails['divider'] = svg(
        stroke('M4 12h16')
    );

    thumbnails['spacer'] = svg(
        stroke('M12 4v16') +
        stroke('M8 8l4-4 4 4') +
        stroke('M8 16l4 4 4-4')
    );

    thumbnails['list'] = svg(
        bg('M2 4h20v16H2z') +
        stroke('M8 6h13M8 12h13M8 18h13') +
        circle(4, 6, 1, true) +
        circle(4, 12, 1, true) +
        circle(4, 18, 1, true)
    );

    thumbnails['blockquote'] = svg(
        bg('M3 3h18v18H3z') +
        stroke('M6 8h2v4H6zM12 8h2v4h-2z') +
        stroke('M6 16h12')
    );

    thumbnails['code-block'] = svg(
        rect(2, 2, 20, 20) +
        stroke('M8 8l-4 4 4 4') +
        stroke('M16 8l4 4-4 4')
    );

    // -------------------------------------------------------------------------
    // LAYOUT — Columnas y estructura
    // -------------------------------------------------------------------------

    thumbnails['columns-2'] = svg(
        rect(2, 3, 9, 18) +
        rect(13, 3, 9, 18)
    );

    thumbnails['columns-3'] = svg(
        rect(1, 3, 6, 18) +
        rect(9, 3, 6, 18) +
        rect(17, 3, 6, 18)
    );

    thumbnails['columns-asymmetric'] = svg(
        rect(2, 3, 13, 18) +
        rect(17, 3, 5, 18)
    );

    // -------------------------------------------------------------------------
    // HERO — Secciones principales
    // -------------------------------------------------------------------------

    thumbnails['hero-simple'] = svg(
        bg('M1 2h22v20H1z') +
        stroke('M5 8h14M5 12h10') +
        `<rect x="5" y="15" width="8" height="3" rx="1.5" fill="currentColor" opacity="0.3"/>` +
        `<rect x="5" y="15" width="8" height="3" rx="1.5" stroke="currentColor" stroke-width="1.5" fill="none"/>`
    );

    thumbnails['hero-split'] = svg(
        bg('M1 2h10v20H1z') +
        rect(13, 4, 9, 16) +
        stroke('M3 8h6M3 11h4') +
        `<rect x="3" y="14" width="5" height="2" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>`
    );

    thumbnails['hero-video'] = svg(
        bg('M1 2h22v20H1z') +
        `<polygon points="10,8 10,16 16,12" fill="currentColor" opacity="0.4"/>` +
        `<polygon points="10,8 10,16 16,12" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/>`
    );

    // -------------------------------------------------------------------------
    // FEATURES — Características y beneficios
    // -------------------------------------------------------------------------

    thumbnails['features-grid'] = svg(
        rect(2, 2, 9, 9) +
        rect(13, 2, 9, 9) +
        rect(2, 13, 9, 9) +
        rect(13, 13, 9, 9)
    );

    thumbnails['features-icon-box'] = svg(
        rect(3, 3, 18, 18) +
        circle(12, 9, 3, true) +
        stroke('M7 16h10M9 19h6')
    );

    // -------------------------------------------------------------------------
    // CTA — Llamadas a la acción
    // -------------------------------------------------------------------------

    thumbnails['cta-basic'] = svg(
        bg('M1 5h22v14H1z') +
        stroke('M5 10h14') +
        `<rect x="8" y="13" width="8" height="3" rx="1.5" fill="currentColor" opacity="0.3"/>` +
        `<rect x="8" y="13" width="8" height="3" rx="1.5" stroke="currentColor" stroke-width="1.5" fill="none"/>`
    );

    thumbnails['cta-gradient'] = svg(
        `<rect x="1" y="5" width="22" height="14" rx="3" fill="currentColor" opacity="0.15"/>` +
        `<rect x="1" y="5" width="22" height="14" rx="3" stroke="currentColor" stroke-width="2" fill="none"/>` +
        stroke('M6 10h12') +
        `<rect x="8" y="13" width="8" height="2.5" rx="1.25" fill="currentColor" opacity="0.4"/>` +
        `<rect x="8" y="13" width="8" height="2.5" rx="1.25" stroke="currentColor" stroke-width="1.5" fill="none"/>`
    );

    thumbnails['cta-split'] = svg(
        bg('M1 4h10v16H1z') +
        rect(13, 6, 9, 12) +
        stroke('M3 9h6M3 12h4') +
        `<rect x="3" y="15" width="5" height="2" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>`
    );

    // -------------------------------------------------------------------------
    // STATS — Estadísticas y contadores
    // -------------------------------------------------------------------------

    thumbnails['stats-counter'] = svg(
        bg('M1 6h22v12H1z') +
        stroke('M5 10h3M5 14h2') +
        stroke('M11 10h3M11 14h2') +
        stroke('M17 10h3M17 14h2')
    );

    thumbnails['stats-progress'] = svg(
        bg('M2 4h20v16H2z') +
        `<rect x="4" y="8" width="16" height="3" rx="1.5" fill="currentColor" opacity="0.15"/>` +
        `<rect x="4" y="8" width="10" height="3" rx="1.5" fill="currentColor" opacity="0.4"/>` +
        `<rect x="4" y="14" width="16" height="3" rx="1.5" fill="currentColor" opacity="0.15"/>` +
        `<rect x="4" y="14" width="14" height="3" rx="1.5" fill="currentColor" opacity="0.4"/>`
    );

    thumbnails['stats-chart'] = svg(
        bg('M2 2h20v20H2z') +
        `<rect x="4" y="12" width="3" height="8" rx="1" fill="currentColor" opacity="0.3"/>` +
        `<rect x="9" y="8" width="3" height="12" rx="1" fill="currentColor" opacity="0.3"/>` +
        `<rect x="14" y="5" width="3" height="15" rx="1" fill="currentColor" opacity="0.3"/>` +
        `<rect x="4" y="12" width="3" height="8" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>` +
        `<rect x="9" y="8" width="3" height="12" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>` +
        `<rect x="14" y="5" width="3" height="15" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>`
    );

    // -------------------------------------------------------------------------
    // TESTIMONIALS — Citas y reseñas
    // -------------------------------------------------------------------------

    thumbnails['testimonials-slider'] = svg(
        bg('M2 4h20v16H2z') +
        stroke('M7 8h2v2H7zM7 13h10') +
        circle(12, 19, 1.5, true) +
        stroke('M7 16h6')
    );

    // -------------------------------------------------------------------------
    // PRICING — Tablas de precios
    // -------------------------------------------------------------------------

    thumbnails['pricing-basic'] = svg(
        rect(2, 2, 9, 20) +
        rect(13, 2, 9, 20) +
        stroke('M5 6h3M16 6h3') +
        stroke('M5 10h3M16 10h3') +
        stroke('M5 14h3M16 14h3') +
        `<rect x="4" y="17" width="5" height="2" rx="1" fill="currentColor" opacity="0.3"/>` +
        `<rect x="15" y="17" width="5" height="2" rx="1" fill="currentColor" opacity="0.3"/>`
    );

    thumbnails['pricing-toggle'] = svg(
        `<rect x="7" y="3" width="10" height="5" rx="2.5" fill="currentColor" opacity="0.2"/>` +
        `<rect x="7" y="3" width="10" height="5" rx="2.5" stroke="currentColor" stroke-width="1.5" fill="none"/>` +
        circle(14, 5.5, 1.5) +
        rect(3, 10, 8, 12) +
        rect(13, 10, 8, 12)
    );

    thumbnails['pricing-highlighted'] = svg(
        rect(1, 4, 7, 18) +
        `<rect x="9" y="2" width="7" height="20" rx="2" fill="currentColor" opacity="0.25"/>` +
        `<rect x="9" y="2" width="7" height="20" rx="2" stroke="currentColor" stroke-width="2" fill="none"/>` +
        rect(17, 4, 6, 18)
    );

    thumbnails['pricing-comparison'] = svg(
        rect(2, 2, 20, 20) +
        stroke('M2 8h20') +
        stroke('M12 2v20') +
        stroke('M5 12h4M14 12h4') +
        stroke('M5 16h4M14 16h4')
    );

    // -------------------------------------------------------------------------
    // TEAM — Equipo
    // -------------------------------------------------------------------------

    thumbnails['team-grid'] = svg(
        circle(7, 7, 3, true) +
        circle(17, 7, 3, true) +
        circle(12, 17, 3, true) +
        stroke('M5 13h4M15 13h4') +
        stroke('M10 21h4')
    );

    // -------------------------------------------------------------------------
    // FAQ — Preguntas frecuentes
    // -------------------------------------------------------------------------

    thumbnails['faq-accordion'] = svg(
        rect(3, 3, 18, 5) +
        stroke('M17 5.5l-2 1.5M17 7l2-1.5') +
        rect(3, 10, 18, 5) +
        rect(3, 17, 18, 5)
    );

    // -------------------------------------------------------------------------
    // TABS — Pestañas de contenido
    // -------------------------------------------------------------------------

    thumbnails['tabs-content'] = svg(
        `<rect x="2" y="5" width="6" height="3" rx="1" fill="currentColor" opacity="0.35"/>` +
        `<rect x="9" y="5" width="6" height="3" rx="1" fill="currentColor" opacity="0.15"/>` +
        `<rect x="16" y="5" width="6" height="3" rx="1" fill="currentColor" opacity="0.15"/>` +
        rect(2, 8, 20, 14) +
        stroke('M5 12h14M5 16h10')
    );

    // -------------------------------------------------------------------------
    // COUNTDOWN — Temporizadores
    // -------------------------------------------------------------------------

    thumbnails['countdown-timer'] = svg(
        bg('M1 5h22v14H1z') +
        rect(3, 7, 4, 6) +
        rect(8.5, 7, 4, 6) +
        rect(14, 7, 4, 6) +
        stroke('M5 10h0.01M10.5 10h0.01M16 10h0.01') +
        stroke('M5 16h2M10.5 16h2M16 16h2')
    );

    // -------------------------------------------------------------------------
    // TIMELINE — Líneas de tiempo
    // -------------------------------------------------------------------------

    thumbnails['timeline'] = svg(
        stroke('M12 2v20') +
        circle(12, 6, 2, true) +
        circle(12, 12, 2, true) +
        circle(12, 18, 2, true) +
        stroke('M14 6h6M4 12h6M14 18h6')
    );

    // -------------------------------------------------------------------------
    // CONTACT — Formularios de contacto
    // -------------------------------------------------------------------------

    thumbnails['contact-form'] = svg(
        rect(3, 3, 18, 18) +
        stroke('M6 7h12') +
        `<rect x="6" y="10" width="12" height="5" rx="1" fill="currentColor" opacity="0.1"/>` +
        `<rect x="6" y="10" width="12" height="5" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>` +
        `<rect x="13" y="17" width="5" height="2" rx="1" fill="currentColor" opacity="0.3"/>`
    );

    thumbnails['contact-map'] = svg(
        bg('M1 3h22v18H1z') +
        stroke('M12 11a3 3 0 100-6 3 3 0 000 6z') +
        stroke('M12 11v4') +
        stroke('M4 18h16')
    );

    thumbnails['contact-chat'] = svg(
        bg('M3 3h14v10H9l-4 4v-4H3z') +
        stroke('M3 3h14v10H9l-4 4v-4H3z') +
        stroke('M7 7h6M7 10h3')
    );

    // -------------------------------------------------------------------------
    // MEDIA — Multimedia
    // -------------------------------------------------------------------------

    thumbnails['media-gallery'] = svg(
        rect(2, 2, 9, 9) +
        rect(13, 2, 9, 9) +
        rect(2, 13, 9, 9) +
        rect(13, 13, 9, 9) +
        stroke('M15 6l2 2 4-4')
    );

    thumbnails['media-video'] = svg(
        rect(2, 4, 20, 16) +
        `<polygon points="10,9 10,15 15,12" fill="currentColor" opacity="0.3"/>` +
        `<polygon points="10,9 10,15 15,12" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linejoin="round"/>`
    );

    thumbnails['media-carousel'] = svg(
        rect(4, 4, 16, 12) +
        stroke('M2 10l2 2-2 2') +
        stroke('M22 10l-2 2 2 2') +
        circle(10, 19, 1) +
        circle(12, 19, 1, true) +
        circle(14, 19, 1)
    );

    // -------------------------------------------------------------------------
    // COMMERCE — Comercio
    // -------------------------------------------------------------------------

    thumbnails['product-card'] = svg(
        rect(3, 2, 18, 20) +
        `<rect x="5" y="4" width="14" height="8" rx="1" fill="currentColor" opacity="0.15"/>` +
        stroke('M6 15h8') +
        stroke('M6 18h4') +
        `<rect x="15" y="16" width="4" height="2" rx="1" fill="currentColor" opacity="0.3"/>`
    );

    thumbnails['product-grid'] = svg(
        rect(2, 2, 9, 9) +
        rect(13, 2, 9, 9) +
        rect(2, 13, 9, 9) +
        rect(13, 13, 9, 9)
    );

    thumbnails['shopping-cart'] = svg(
        circle(9, 20, 1.5, true) +
        circle(18, 20, 1.5, true) +
        stroke('M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6')
    );

    thumbnails['checkout'] = svg(
        rect(2, 2, 20, 20) +
        stroke('M5 6h14') +
        stroke('M5 10h14') +
        stroke('M5 14h8') +
        `<rect x="14" y="16" width="6" height="3" rx="1.5" fill="currentColor" opacity="0.3"/>`
    );

    // -------------------------------------------------------------------------
    // SOCIAL — Redes y compartir
    // -------------------------------------------------------------------------

    thumbnails['social-icons'] = svg(
        circle(6, 12, 3, true) +
        circle(12, 12, 3, true) +
        circle(18, 12, 3, true)
    );

    thumbnails['social-feed'] = svg(
        rect(2, 2, 20, 20) +
        circle(7, 7, 2, true) +
        stroke('M11 6h8') +
        stroke('M11 9h5') +
        stroke('M5 13h14') +
        stroke('M5 17h10')
    );

    thumbnails['social-share'] = svg(
        circle(18, 5, 3, true) +
        circle(6, 12, 3, true) +
        circle(18, 19, 3, true) +
        stroke('M8.59 13.51l6.83 3.98') +
        stroke('M15.41 6.51l-6.82 3.98')
    );

    // -------------------------------------------------------------------------
    // ADVANCED — Bloques avanzados
    // -------------------------------------------------------------------------

    thumbnails['custom-html'] = svg(
        rect(2, 2, 20, 20) +
        stroke('M7 7l-3 5 3 5') +
        stroke('M17 7l3 5-3 5') +
        stroke('M14 4l-4 16')
    );

    thumbnails['map-embed'] = svg(
        bg('M1 3h22v18H1z') +
        stroke('M12 11a3 3 0 100-6 3 3 0 000 6z') +
        stroke('M12 11l0 4')
    );

    // -------------------------------------------------------------------------
    // UTILITIES — Utilidades
    // -------------------------------------------------------------------------

    thumbnails['alert-banner'] = svg(
        `<rect x="1" y="6" width="22" height="12" rx="2" fill="currentColor" opacity="0.15"/>` +
        `<rect x="1" y="6" width="22" height="12" rx="2" stroke="currentColor" stroke-width="2" fill="none"/>` +
        circle(6, 12, 2, true) +
        stroke('M10 10h10M10 14h7')
    );

    thumbnails['breadcrumb'] = svg(
        stroke('M3 12h2') +
        stroke('M7 12l2-2v4z') +
        stroke('M11 12h2') +
        stroke('M15 12l2-2v4z') +
        stroke('M19 12h2')
    );

    // -------------------------------------------------------------------------
    // PREMIUM — Bloques premium (Aceternity/Magic UI)
    // -------------------------------------------------------------------------

    thumbnails['premium-block'] = svg(
        `<rect x="2" y="2" width="20" height="20" rx="3" fill="currentColor" opacity="0.1"/>` +
        `<rect x="2" y="2" width="20" height="20" rx="3" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="4 2"/>` +
        stroke('M8 12l3 3 5-6')
    );

    // -------------------------------------------------------------------------
    // CATEGORY ICONS — Iconos para la cabecera de cada categoría
    // -------------------------------------------------------------------------

    thumbnails['_cat_basic'] = svg(
        bg('M3 3h18v18H3z') +
        stroke('M7 8h10M7 12h10M7 16h6')
    );

    thumbnails['_cat_hero'] = svg(
        bg('M1 2h22v20H1z') +
        stroke('M5 8h14M5 12h8') +
        `<rect x="5" y="15" width="6" height="2.5" rx="1.25" fill="currentColor" opacity="0.35"/>`
    );

    thumbnails['_cat_features'] = svg(
        rect(2, 2, 9, 9) + rect(13, 2, 9, 9) +
        rect(2, 13, 9, 9) + rect(13, 13, 9, 9)
    );

    thumbnails['_cat_cta'] = svg(
        bg('M2 6h20v12H2z') +
        `<rect x="7" y="10" width="10" height="4" rx="2" fill="currentColor" opacity="0.3"/>` +
        `<rect x="7" y="10" width="10" height="4" rx="2" stroke="currentColor" stroke-width="2" fill="none"/>`
    );

    thumbnails['_cat_stats'] = svg(
        `<rect x="3" y="14" width="4" height="6" rx="1" fill="currentColor" opacity="0.2"/>` +
        `<rect x="10" y="10" width="4" height="10" rx="1" fill="currentColor" opacity="0.2"/>` +
        `<rect x="17" y="6" width="4" height="14" rx="1" fill="currentColor" opacity="0.2"/>` +
        stroke('M3 4v16h18')
    );

    thumbnails['_cat_testimonials'] = svg(
        bg('M3 3h14v10H9l-4 4v-4H3z') +
        stroke('M3 3h14v10H9l-4 4v-4H3z') +
        stroke('M7 7h6M7 10h3')
    );

    thumbnails['_cat_pricing'] = svg(
        rect(2, 3, 9, 18) + rect(13, 3, 9, 18) +
        stroke('M6 7h2M17 7h2') +
        stroke('M6 11h2M17 11h2')
    );

    thumbnails['_cat_team'] = svg(
        circle(8, 8, 3, true) + circle(16, 8, 3, true) +
        stroke('M4 16c0-2 2-4 4-4s4 2 4 4') +
        stroke('M12 16c0-2 2-4 4-4s4 2 4 4')
    );

    thumbnails['_cat_faq'] = svg(
        circle(12, 12, 10, true) +
        stroke('M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3') +
        stroke('M12 17h.01')
    );

    thumbnails['_cat_tabs'] = svg(
        `<rect x="2" y="4" width="6" height="3" rx="1" fill="currentColor" opacity="0.35"/>` +
        `<rect x="9" y="4" width="6" height="3" rx="1" fill="currentColor" opacity="0.15"/>` +
        rect(2, 7, 20, 15)
    );

    thumbnails['_cat_countdown'] = svg(
        circle(12, 12, 10, true) +
        stroke('M12 6v6l4 2')
    );

    thumbnails['_cat_timeline'] = svg(
        stroke('M12 2v20') +
        circle(12, 6, 2, true) + circle(12, 12, 2, true) + circle(12, 18, 2, true)
    );

    thumbnails['_cat_contact'] = svg(
        rect(2, 4, 20, 16) +
        stroke('M22 4l-10 7L2 4')
    );

    thumbnails['_cat_content'] = svg(
        rect(3, 3, 18, 18) +
        stroke('M7 7h10M7 11h10M7 15h6')
    );

    thumbnails['_cat_media'] = svg(
        rect(3, 3, 18, 18) +
        circle(8.5, 8.5, 1.5) +
        stroke('M21 15l-5-5L5 21')
    );

    thumbnails['_cat_premium'] = svg(
        stroke('M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z') +
        bg('M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z')
    );

    thumbnails['_cat_commerce'] = svg(
        stroke('M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z') +
        bg('M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z') +
        stroke('M3 6h18') +
        stroke('M16 10a4 4 0 01-8 0')
    );

    thumbnails['_cat_social'] = svg(
        circle(18, 5, 3, true) + circle(6, 12, 3, true) + circle(18, 19, 3, true) +
        stroke('M8.59 13.51l6.83 3.98') +
        stroke('M15.41 6.51l-6.82 3.98')
    );

    thumbnails['_cat_advanced'] = svg(
        stroke('M12 2L2 7l10 5 10-5-10-5z') +
        bg('M12 2L2 7l10 5 10-5-10-5z') +
        stroke('M2 17l10 5 10-5') +
        stroke('M2 12l10 5 10-5')
    );

    thumbnails['_cat_utilities'] = svg(
        circle(12, 12, 3) +
        stroke('M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42')
    );

    thumbnails['_cat_layout'] = svg(
        rect(2, 2, 20, 5) +
        rect(2, 9, 9, 13) +
        rect(13, 9, 9, 13)
    );

    thumbnails['_cat_maps'] = svg(
        stroke('M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z') +
        bg('M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z') +
        stroke('M8 2v16') +
        stroke('M16 6v16')
    );

    thumbnails['_cat_trust'] = svg(
        stroke('M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z') +
        bg('M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z') +
        stroke('M9 12l2 2 4-4')
    );

    thumbnails['_cat_events'] = svg(
        rect(3, 4, 18, 18) +
        stroke('M16 2v4M8 2v4M3 10h18') +
        `<circle cx="8" cy="15" r="1.5" fill="currentColor" opacity="0.35"/>` +
        `<circle cx="12" cy="15" r="1.5" fill="currentColor" opacity="0.35"/>`
    );

    thumbnails['_cat_forms'] = svg(
        rect(3, 3, 18, 18) +
        stroke('M7 8h4') +
        `<rect x="7" y="11" width="10" height="2.5" rx="1" fill="currentColor" opacity="0.15"/>` +
        `<rect x="7" y="11" width="10" height="2.5" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>` +
        `<rect x="7" y="15.5" width="10" height="2.5" rx="1" fill="currentColor" opacity="0.15"/>` +
        `<rect x="7" y="15.5" width="10" height="2.5" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>`
    );

    thumbnails['_cat_social_proof'] = svg(
        circle(12, 8, 4, true) +
        stroke('M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6') +
        stroke('M17 4l1 2 2-.5') +
        stroke('M19 7l-1-2')
    );

    thumbnails['_cat_conversion'] = svg(
        stroke('M22 12h-4l-3 9L9 3l-3 9H2') +
        bg('M22 12h-4l-3 9L9 3l-3 9H2')
    );

    // -------------------------------------------------------------------------
    // AGROCONECTA — Bloques del Vertical Agricultura + Comercio Rural
    // Estilo PREMIUM: SVGs únicos con motivos agrícolas, paths orgánicos y
    // fills decorativos al nivel del Testimonios 3D Carousel.
    // -------------------------------------------------------------------------

    // 1. Hero — Escaparate del Productor
    //    Icono: Horizonte con sol + plántula brotando
    thumbnails['agroconecta_hero'] = svg(
        // Sol naciente — semicírculo con rayos
        `<circle cx="12" cy="18" r="6" fill="currentColor" opacity="0.15"/>` +
        `<path d="M6 18a6 6 0 0112 0" fill="currentColor" opacity="0.25"/>` +
        `<path d="M6 18a6 6 0 0112 0" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>` +
        // Rayos solares
        stroke('M12 10v-2') +
        stroke('M7.76 13.76l-1.42-1.42') +
        stroke('M16.24 13.76l1.42-1.42') +
        // Plántula central
        `<path d="M12 22v-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>` +
        `<path d="M9 17c0-2 3-3 3-5" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/>` +
        `<path d="M15 17c0-2-3-3-3-5" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/>` +
        // Línea de tierra
        stroke('M4 22h16')
    );
    thumbnails['template-agroconecta_hero'] = thumbnails['agroconecta_hero'];

    // 2. Features — Productos Orgánicos
    //    Icono: Hoja orgánica con check de certificación
    thumbnails['agroconecta_features'] = svg(
        // Hoja grande
        `<path d="M17 2c0 8-5 13-13 13" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>` +
        `<path d="M17 2C17 10 12 15 4 15c0-8 5-13 13-13z" fill="currentColor" opacity="0.2"/>` +
        `<path d="M17 2C17 10 12 15 4 15c0-8 5-13 13-13z" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>` +
        // Nervadura de la hoja
        `<path d="M4 15C8 11 12 6 17 2" stroke="currentColor" stroke-width="1" fill="none" opacity="0.4" stroke-linecap="round"/>` +
        // Grid de 3 mini-checks
        `<path d="M14 14l1.5 1.5 3-3" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>` +
        `<path d="M14 19l1.5 1.5 3-3" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>` +
        // Tallo base
        `<path d="M4 15l2 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>`
    );
    thumbnails['template-agroconecta_features'] = thumbnails['agroconecta_features'];

    // 3. Content — Certificación y Origen
    //    Icono: Sello con estrella y cinta
    thumbnails['agroconecta_content'] = svg(
        // Sello circular
        `<circle cx="12" cy="10" r="7" fill="currentColor" opacity="0.15"/>` +
        `<circle cx="12" cy="10" r="7" stroke="currentColor" stroke-width="2" fill="none"/>` +
        `<circle cx="12" cy="10" r="4.5" stroke="currentColor" stroke-width="1" fill="none" opacity="0.5" stroke-dasharray="2 2"/>` +
        // Estrella de certificación
        `<path d="M12 6.5l1.12 2.27 2.5.37-1.81 1.76.43 2.5L12 12.38l-2.24 1.02.43-2.5-1.81-1.76 2.5-.37z" fill="currentColor" opacity="0.35"/>` +
        `<path d="M12 6.5l1.12 2.27 2.5.37-1.81 1.76.43 2.5L12 12.38l-2.24 1.02.43-2.5-1.81-1.76 2.5-.37z" stroke="currentColor" stroke-width="1" fill="none"/>` +
        // Cintas colgantes
        `<path d="M9 17l-2 5 3-2 3 2-2-5" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>` +
        `<path d="M15 17l-2 5 3-2 3 2-2-5" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>`
    );
    thumbnails['template-agroconecta_content'] = thumbnails['agroconecta_content'];

    // 4. CTA — Pedido Directo
    //    Icono: Canasta/cesta con productos + mano
    thumbnails['agroconecta_cta'] = svg(
        // Cesta
        `<path d="M5 10h14l-1.5 9H6.5z" fill="currentColor" opacity="0.2"/>` +
        `<path d="M5 10h14l-1.5 9H6.5z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/>` +
        // Asa de la cesta
        `<path d="M8 10V7a4 4 0 018 0v3" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>` +
        // Productos asomando (formas orgánicas)
        `<circle cx="10" cy="8" r="1.5" fill="currentColor" opacity="0.3"/>` +
        `<circle cx="14" cy="7.5" r="1" fill="currentColor" opacity="0.3"/>` +
        // Líneas de la cesta
        `<path d="M7 14h10M7.5 17h9" stroke="currentColor" stroke-width="1" fill="none" opacity="0.4" stroke-linecap="round"/>` +
        // Flecha apuntando → acción directa
        `<path d="M17 22l3-2-3-2" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>` +
        `<path d="M20 20H14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>`
    );
    thumbnails['template-agroconecta_cta'] = thumbnails['agroconecta_cta'];

    // 5. FAQ — Certificación Orgánica
    //    Icono: Burbuja de pregunta con hoja
    thumbnails['agroconecta_faq'] = svg(
        // Burbuja de diálogo grande
        `<path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z" fill="currentColor" opacity="0.15"/>` +
        `<path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z" stroke="currentColor" stroke-width="2" fill="none"/>` +
        // Signo de interrogación orgánico
        `<path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>` +
        `<circle cx="12" cy="17" r="0.5" fill="currentColor"/>` +
        // Mini hoja decorativa
        `<path d="M18 3c0 2-1.5 3.5-3.5 3.5 0-2 1.5-3.5 3.5-3.5z" fill="currentColor" opacity="0.3"/>` +
        `<path d="M18 3c0 2-1.5 3.5-3.5 3.5 0-2 1.5-3.5 3.5-3.5z" stroke="currentColor" stroke-width="1" fill="none"/>`
    );
    thumbnails['template-agroconecta_faq'] = thumbnails['agroconecta_faq'];

    // 6. Gallery — Galería de Cosechas
    //    Icono: Cámara/lente con hoja
    thumbnails['agroconecta_gallery'] = svg(
        // Cuerpo de cámara
        `<path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" fill="currentColor" opacity="0.15"/>` +
        `<path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" stroke="currentColor" stroke-width="2" fill="none"/>` +
        // Lente
        `<circle cx="12" cy="13" r="4" fill="currentColor" opacity="0.2"/>` +
        `<circle cx="12" cy="13" r="4" stroke="currentColor" stroke-width="2" fill="none"/>` +
        // Mini hoja dentro de la lente
        `<path d="M14 11c0 2-1 3-3 3 0-2 1-3 3-3z" fill="currentColor" opacity="0.4"/>` +
        `<path d="M14 11c0 2-1 3-3 3 0-2 1-3 3-3z" stroke="currentColor" stroke-width="0.75" fill="none"/>`
    );
    thumbnails['template-agroconecta_gallery'] = thumbnails['agroconecta_gallery'];

    // 7. Map — Ubicación de la Finca
    //    Icono: Pin de mapa con planta dentro
    thumbnails['agroconecta_map'] = svg(
        // Pin grande
        `<path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z" fill="currentColor" opacity="0.15"/>` +
        `<path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z" stroke="currentColor" stroke-width="2" fill="none"/>` +
        // Círculo interior del pin
        `<circle cx="12" cy="10" r="3.5" fill="currentColor" opacity="0.2"/>` +
        `<circle cx="12" cy="10" r="3.5" stroke="currentColor" stroke-width="1.5" fill="none"/>` +
        // Plántula dentro del pin
        `<path d="M12 12v-3" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/>` +
        `<path d="M10.5 9.5c0-1 1.5-1.5 1.5-2.5" stroke="currentColor" stroke-width="1" fill="none" stroke-linecap="round"/>` +
        `<path d="M13.5 9.5c0-1-1.5-1.5-1.5-2.5" stroke="currentColor" stroke-width="1" fill="none" stroke-linecap="round"/>`
    );
    thumbnails['template-agroconecta_map'] = thumbnails['agroconecta_map'];

    // 8. Pricing — Cajas de Suscripción
    //    Icono: Cajón/crate de madera con productos
    thumbnails['agroconecta_pricing'] = svg(
        // Caja principal con perspectiva
        `<path d="M21 8v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8" fill="currentColor" opacity="0.15"/>` +
        `<path d="M21 8v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8" stroke="currentColor" stroke-width="2" fill="none"/>` +
        // Tapa de la caja
        `<rect x="1" y="5" width="22" height="5" rx="1" fill="currentColor" opacity="0.25"/>` +
        `<rect x="1" y="5" width="22" height="5" rx="1" stroke="currentColor" stroke-width="2" fill="none"/>` +
        // Línea central del lazo
        `<path d="M12 10v8" stroke="currentColor" stroke-width="1.5" fill="none" opacity="0.5"/>` +
        // Productos asomando arriba
        `<circle cx="8" cy="4" r="2" fill="currentColor" opacity="0.25"/>` +
        `<circle cx="8" cy="4" r="2" stroke="currentColor" stroke-width="1.5" fill="none"/>` +
        `<circle cx="14" cy="3.5" r="1.5" fill="currentColor" opacity="0.25"/>` +
        `<circle cx="14" cy="3.5" r="1.5" stroke="currentColor" stroke-width="1.5" fill="none"/>` +
        // Etiqueta de precio
        `<path d="M16 14h3v3h-3z" fill="currentColor" opacity="0.3"/>` +
        `<path d="M16 14h3v3h-3z" stroke="currentColor" stroke-width="1" fill="none"/>`
    );
    thumbnails['template-agroconecta_pricing'] = thumbnails['agroconecta_pricing'];

    // 9. Social Proof — Reseñas de Compradores
    //    Icono: Manos estrechándose con espigas de trigo
    thumbnails['agroconecta_social_proof'] = svg(
        // Espiga de trigo izquierda
        `<path d="M4 2c0 2 1 3 2 4-1 0-2 1-2 3 0-2 1-3 2-4-1 0-2-1-2-3z" fill="currentColor" opacity="0.3"/>` +
        `<path d="M3 5v6" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/>` +
        // Espiga de trigo derecha
        `<path d="M20 2c0 2-1 3-2 4 1 0 2 1 2 3 0-2-1-3-2-4 1 0 2-1 2-3z" fill="currentColor" opacity="0.3"/>` +
        `<path d="M21 5v6" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/>` +
        // Escudo central
        `<path d="M12 22s7-3.5 7-8.5V6l-7-3-7 3v7.5c0 5 7 8.5 7 8.5z" fill="currentColor" opacity="0.15"/>` +
        `<path d="M12 22s7-3.5 7-8.5V6l-7-3-7 3v7.5c0 5 7 8.5 7 8.5z" stroke="currentColor" stroke-width="2" fill="none"/>` +
        // Check de confianza dentro del escudo
        `<path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>`
    );
    thumbnails['template-agroconecta_social_proof'] = thumbnails['agroconecta_social_proof'];

    // 10. Stats — Métricas de Impacto
    //    Icono: Gráfico de barras con flecha ascendente y plántula
    thumbnails['agroconecta_stats'] = svg(
        // Barras del gráfico
        `<rect x="3" y="14" width="4" height="7" rx="1" fill="currentColor" opacity="0.2"/>` +
        `<rect x="3" y="14" width="4" height="7" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>` +
        `<rect x="10" y="10" width="4" height="11" rx="1" fill="currentColor" opacity="0.2"/>` +
        `<rect x="10" y="10" width="4" height="11" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>` +
        `<rect x="17" y="6" width="4" height="15" rx="1" fill="currentColor" opacity="0.2"/>` +
        `<rect x="17" y="6" width="4" height="15" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>` +
        // Flecha ascendente cruzando las barras
        `<path d="M2 18L8 12l4 2 8-8" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>` +
        `<path d="M17 4h3v3" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>`
    );
    thumbnails['template-agroconecta_stats'] = thumbnails['agroconecta_stats'];

    // 11. Testimonials — Historia del Productor
    //    Icono: Comillas de cita grandes con hoja decorativa (≈ Testimonios 3D)
    thumbnails['agroconecta_testimonials'] = svg(
        // Comilla izquierda grande, estilo orgánico
        `<path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21z" fill="currentColor" opacity="0.2"/>` +
        `<path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21z" stroke="currentColor" stroke-width="2" fill="none"/>` +
        // Comilla derecha
        `<path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z" fill="currentColor" opacity="0.2"/>` +
        `<path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z" stroke="currentColor" stroke-width="2" fill="none"/>` +
        // Hoja decorativa superior
        `<path d="M20 1c0 2-1.5 3.5-3.5 3.5 0-2 1.5-3.5 3.5-3.5z" fill="currentColor" opacity="0.35"/>` +
        `<path d="M20 1c0 2-1.5 3.5-3.5 3.5 0-2 1.5-3.5 3.5-3.5z" stroke="currentColor" stroke-width="1" fill="none"/>`
    );
    thumbnails['template-agroconecta_testimonials'] = thumbnails['agroconecta_testimonials'];

    thumbnails['_cat_agroconecta'] = svg(
        stroke('M12 22V8') +
        stroke('M8 12l4-4 4 4') +
        bg('M7 18c-2 0-4-1-4-3s2-4 5-4c.5-3 3-5 5-5s4 2 5 5c3 0 5 2 5 4s-2 3-4 3') +
        stroke('M7 18c-2 0-4-1-4-3s2-4 5-4c.5-3 3-5 5-5s4 2 5 5c3 0 5 2 5 4s-2 3-4 3') +
        `<path d="M12 22c-1 0-3-.5-3-2s2-2 3-2 3 0 3 2-2 2-3 2z" fill="currentColor" opacity="0.3"/>`
    );

    thumbnails['_cat_comercioconecta'] = svg(
        stroke('M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z') +
        bg('M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z') +
        rect(9, 14, 6, 8)
    );

    thumbnails['_cat_serviciosconecta'] = svg(
        stroke('M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91A6 6 0 016.73 2.67l3.77 3.77') +
        bg('M12 12l-6.5 6.5')
    );

    thumbnails['_cat_empleabilidad'] = svg(
        rect(2, 7, 20, 14) +
        stroke('M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2') +
        stroke('M12 12h.01') +
        stroke('M2 12h20')
    );

    thumbnails['_cat_emprendimiento'] = svg(
        stroke('M12 2L2 7l10 5 10-5-10-5z') +
        bg('M12 2L2 7l10 5 10-5-10-5z') +
        stroke('M2 12l10 5 10-5') +
        stroke('M12 22V12') +
        `<circle cx="12" cy="17" r="2" fill="currentColor" opacity="0.35"/>`
    );

    // =========================================================================
    // API PÚBLICA
    // =========================================================================

    Drupal.jarabaThumbnails = {
        /**
         * Obtiene el SVG thumbnail de un bloque.
         *
         * @param {string} blockId ID del bloque o categoría (_cat_xxx).
         * @return {string|null} SVG string o null si no existe.
         */
        get: function (blockId) {
            return thumbnails[blockId] || null;
        },

        /**
         * Obtiene todos los thumbnails.
         *
         * @return {Object} Mapa blockId → SVG string.
         */
        getAll: function () {
            return { ...thumbnails };
        },

        /**
         * Obtiene el thumbnail de una categoría.
         *
         * @param {string} categoryId ID de la categoría (sin prefijo _cat_).
         * @return {string|null} SVG string.
         */
        getCategory: function (categoryId) {
            return thumbnails['_cat_' + categoryId] || null;
        },

        /**
         * Registra un nuevo thumbnail (extensible).
         *
         * @param {string} blockId ID del bloque.
         * @param {string} svgString SVG completo.
         */
        register: function (blockId, svgString) {
            thumbnails[blockId] = svgString;
        },
    };

    // =========================================================================
    // AUTO-INYECCIÓN: Iconos de categoría + upgrade de thumbnails
    // =========================================================================

    /**
     * Mapa de etiquetas traducidas del sidebar → IDs de categoría del registro.
     */
    const categoryLabelToId = {
        'Básico': 'basic',
        'Hero': 'hero',
        'Características': 'features',
        'Llamadas a Acción': 'cta',
        'Llamada a la Acción': 'cta',
        'Estadísticas': 'stats',
        'Testimonios': 'testimonials',
        'Precios': 'pricing',
        'Equipo': 'team',
        'FAQ': 'faq',
        'Pestañas': 'tabs',
        'Cuenta Regresiva': 'countdown',
        'Timeline': 'timeline',
        'Contacto': 'contact',
        'Contenido': 'content',
        'Multimedia': 'media',
        'Premium': 'premium',
        'Comercio': 'commerce',
        'Social': 'social',
        'Avanzado': 'advanced',
        'Utilidades': 'utilities',
        'Diseño': 'layout',
        'Layout': 'layout',
        'Confianza': 'trust',
        'Línea de Tiempo': 'timeline',
        'Formación': 'lms',
        'Capacitación': 'training',
        'Preguntas frecuentes': 'faq',
        'maps': 'maps',
        'Mapas': 'maps',
        'events': 'events',
        'Eventos': 'events',
        'Formularios': 'forms',
        'Prueba Social': 'social_proof',
        'Conversión': 'conversion',
        'agroconecta': 'agroconecta',
        'AgroConecta': 'agroconecta',
        'comercioconecta': 'comercioconecta',
        'ComercioConecta': 'comercioconecta',
        'serviciosconecta': 'serviciosconecta',
        'ServiciosConecta': 'serviciosconecta',
        'empleabilidad': 'empleabilidad',
        'Empleabilidad': 'empleabilidad',
        'emprendimiento': 'emprendimiento',
        'Emprendimiento': 'emprendimiento',
    };

    // =========================================================================
    // ISSUE 6: Flechas chevron en categorías (reemplazo FontAwesome)
    // =========================================================================

    /** SVG chevron para indicar open/close en categorías. */
    const chevronSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';

    /**
     * Reemplaza los iconos FontAwesome (fa-caret-right) con SVG chevrons
     * y los posiciona a la derecha del título.
     */
    function fixCategoryCarets() {
        var container = document.getElementById('gjs-blocks-container');
        if (!container) return 0;

        var carets = container.querySelectorAll('.gjs-caret-icon');
        var fixed = 0;

        carets.forEach(function (caret) {
            // No duplicar si ya fue reemplazado.
            if (caret.getAttribute('data-jaraba-chevron')) return;
            caret.setAttribute('data-jaraba-chevron', 'true');

            // Limpiar clases FA y contenido.
            caret.className = 'gjs-caret-icon jaraba-chevron';
            caret.innerHTML = chevronSvg;
            fixed++;
        });

        return fixed;
    }

    // =========================================================================
    // ISSUE 3: Buscador/filtro de bloques
    // =========================================================================

    /**
     * Inicializa el buscador de bloques.
     * Filtra bloques y categorías en tiempo real según el texto introducido.
     */
    function initBlockSearch() {
        var searchInput = document.getElementById('gjs-blocks-search');
        var container = document.getElementById('gjs-blocks-container');
        if (!searchInput || !container) return;

        // No duplicar listener.
        if (searchInput.getAttribute('data-jaraba-search')) return;
        searchInput.setAttribute('data-jaraba-search', 'true');

        var debounceTimer = null;

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                filterBlocks(searchInput.value.trim().toLowerCase(), container);
            }, 150);
        });

        // Limpiar con Escape.
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                searchInput.value = '';
                filterBlocks('', container);
            }
        });
    }

    /**
     * Filtra bloques y categorías según query.
     *
     * @param {string} query Texto de búsqueda (lowercase).
     * @param {HTMLElement} container Contenedor de bloques GrapesJS.
     */
    function filterBlocks(query, container) {
        var categories = container.querySelectorAll('.gjs-block-category');
        var standalone = container.querySelectorAll(':scope > .gjs-block');

        // Si no hay query, mostrar todo.
        if (!query) {
            categories.forEach(function (cat) {
                cat.style.display = '';
                cat.querySelectorAll('.gjs-block').forEach(function (block) {
                    block.style.display = '';
                });
            });
            standalone.forEach(function (block) {
                block.style.display = '';
            });
            return;
        }

        // Normalizar query para búsqueda con diacríticos.
        var normalizedQuery = removeDiacritics(query);

        categories.forEach(function (cat) {
            var titleEl = cat.querySelector('.gjs-title');
            var catText = titleEl ? extractCategoryText(titleEl) : '';
            var normalizedCatText = removeDiacritics(catText.toLowerCase());
            var catMatches = normalizedCatText.indexOf(normalizedQuery) !== -1;

            var blocks = cat.querySelectorAll('.gjs-block');
            var visibleBlocks = 0;

            blocks.forEach(function (block) {
                var label = block.querySelector('.gjs-block-label');
                var blockText = label ? label.textContent.trim().toLowerCase() : '';
                var normalizedBlockText = removeDiacritics(blockText);
                var blockMatches = normalizedBlockText.indexOf(normalizedQuery) !== -1;

                if (catMatches || blockMatches) {
                    block.style.display = '';
                    visibleBlocks++;
                } else {
                    block.style.display = 'none';
                }
            });

            // Mostrar categoría si ella misma o algún bloque coincide.
            cat.style.display = (catMatches || visibleBlocks > 0) ? '' : 'none';

            // Si hay match, abrir la categoría automáticamente.
            if ((catMatches || visibleBlocks > 0) && query.length >= 2) {
                cat.classList.add('gjs-open');
            }
        });

        // Filtrar bloques sueltos (sin categoría).
        standalone.forEach(function (block) {
            var label = block.querySelector('.gjs-block-label');
            var blockText = label ? label.textContent.trim().toLowerCase() : '';
            block.style.display = removeDiacritics(blockText).indexOf(normalizedQuery) !== -1 ? '' : 'none';
        });
    }

    /**
     * Extrae solo el texto de categoría del título (excluye caret e iconos).
     */
    function extractCategoryText(titleEl) {
        var text = '';
        titleEl.childNodes.forEach(function (node) {
            if (node.nodeType === Node.TEXT_NODE) {
                var t = node.textContent.trim();
                if (t) text = t;
            }
        });
        return text;
    }

    /**
     * Elimina diacríticos para búsqueda tolerante (á→a, ñ→n, etc).
     *
     * @param {string} str Texto a normalizar.
     * @return {string} Texto sin diacríticos.
     */
    function removeDiacritics(str) {
        return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    // =========================================================================
    // INYECCIÓN DE ICONOS DE CATEGORÍA
    // =========================================================================

    /**
     * Inyecta iconos SVG duotone en los encabezados de categoría del sidebar.
     *
     * Estructura DOM de GrapesJS:
     *   .gjs-block-category
     *     .gjs-title
     *       i.gjs-caret-icon.fa.fa-caret-right
     *       "Básico"  ← text node
     */
    function injectCategoryIcons() {
        var container = document.getElementById('gjs-blocks-container');
        if (!container) return 0;

        var categories = container.querySelectorAll('.gjs-block-category');
        var injected = 0;

        categories.forEach(function (catEl) {
            var titleEl = catEl.querySelector('.gjs-title');
            if (!titleEl) return;

            // Extraer solo el texto del nodo hijo texto (no el del <i> caret).
            var text = extractCategoryText(titleEl);
            if (!text) return;

            var catId = categoryLabelToId[text];
            if (!catId) return;

            var catSvg = thumbnails['_cat_' + catId];
            if (!catSvg) return;

            // No duplicar iconos si ya inyectado.
            if (titleEl.querySelector('.jaraba-cat-icon')) return;

            // Crear wrapper del icono.
            var iconWrapper = document.createElement('span');
            iconWrapper.className = 'jaraba-cat-icon';
            iconWrapper.style.cssText = 'display:inline-flex;align-items:center;margin-right:6px;vertical-align:middle;';
            iconWrapper.innerHTML = catSvg
                .replace('width="40"', 'width="18"')
                .replace('height="40"', 'height="18"');

            // Insertar después del <i> caret y antes del texto.
            var caretIcon = titleEl.querySelector('.gjs-caret-icon');
            if (caretIcon && caretIcon.nextSibling) {
                titleEl.insertBefore(iconWrapper, caretIcon.nextSibling);
            } else {
                titleEl.insertBefore(iconWrapper, titleEl.firstChild);
            }

            injected++;
        });

        return injected;
    }

    /**
     * Actualiza todos los bloques de GrapesJS al estilo duotone.
     * Busca el editor GrapesJS activo y reemplaza los thumbnails.
     * Preserva bloques que ya tengan PNG thumbnails (<img> tags).
     */
    function upgradeBlockThumbnails() {
        // Buscar editor GrapesJS activo.
        if (typeof grapesjs === 'undefined' || !grapesjs.editors || !grapesjs.editors.length) {
            return 0;
        }

        var editor = grapesjs.editors[0];
        var bm = editor.BlockManager;
        if (!bm) return 0;

        var allBlocks = bm.getAll();
        var upgraded = 0;
        var skippedPng = 0;

        allBlocks.forEach(function (block) {
            var blockId = block.getId();
            var currentMedia = block.get('media') || '';

            // Preservar bloques que ya tienen PNG thumbnail (<img>).
            if (typeof currentMedia === 'string' && currentMedia.indexOf('<img') !== -1) {
                skippedPng++;
                return;
            }

            if (thumbnails[blockId]) {
                block.set('media', thumbnails[blockId]);
                upgraded++;
            }
        });

        if (skippedPng > 0) {
            console.log('🖼️ Thumbnails: ' + skippedPng + ' bloques con PNG preservados.');
        }

        return upgraded;
    }

    // =========================================================================
    // CSS INJECTION: Estilos para chevrons, buscador e iconos de categoría
    // =========================================================================

    function injectStyles() {
        if (document.getElementById('jaraba-thumbnails-styles')) return;

        var style = document.createElement('style');
        style.id = 'jaraba-thumbnails-styles';
        style.textContent = `
            /* Issue 6: Chevron arrows en categorías */
            .gjs-caret-icon.jaraba-chevron {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                position: absolute !important;
                right: 10px !important;
                left: auto !important;
                top: 50%;
                transform: translateY(-50%) rotate(-90deg);
                transition: transform 0.25s ease;
                opacity: 0.6;
                margin: 0 !important;
                width: 14px;
                height: 14px;
            }

            .gjs-caret-icon.jaraba-chevron svg {
                fill: none;
                stroke: currentColor;
            }

            .gjs-block-category.gjs-open .gjs-caret-icon.jaraba-chevron {
                transform: translateY(-50%) rotate(0deg);
                opacity: 0.9;
            }

            /* Asegurar título tiene position relative para el chevron absolute */
            .gjs-block-category .gjs-title {
                position: relative !important;
                padding-right: 30px !important;
                display: flex;
                align-items: center;
            }

            /* Issue 3: Buscador de bloques */
            .jaraba-grapesjs-panel__search {
                position: relative;
                padding: 8px 12px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            }

            .jaraba-grapesjs-panel__search-icon {
                position: absolute;
                left: 20px;
                top: 50%;
                transform: translateY(-50%);
                color: rgba(255, 255, 255, 0.4);
                pointer-events: none;
                z-index: 1;
            }

            .jaraba-grapesjs-panel__search-input {
                width: 100%;
                padding: 8px 12px 8px 32px;
                background: rgba(255, 255, 255, 0.07);
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 6px;
                color: rgba(255, 255, 255, 0.85);
                font-size: 12px;
                outline: none;
                transition: border-color 0.2s, background 0.2s;
                box-sizing: border-box;
            }

            .jaraba-grapesjs-panel__search-input::placeholder {
                color: rgba(255, 255, 255, 0.35);
            }

            .jaraba-grapesjs-panel__search-input:focus {
                border-color: rgba(255, 152, 0, 0.5);
                background: rgba(255, 255, 255, 0.1);
            }

            /* Ocultar input nativo clear en WebKit */
            .jaraba-grapesjs-panel__search-input::-webkit-search-cancel-button {
                -webkit-appearance: none;
                height: 14px;
                width: 14px;
                cursor: pointer;
                background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.5)' stroke-width='2' stroke-linecap='round'%3E%3Cline x1='18' y1='6' x2='6' y2='18'%3E%3C/line%3E%3Cline x1='6' y1='6' x2='18' y2='18'%3E%3C/line%3E%3C/svg%3E") center no-repeat;
            }

            /* Categoría con icono inyectado */
            .jaraba-cat-icon svg {
                display: block;
            }
        `;
        document.head.appendChild(style);
    }

    // =========================================================================
    // OBSERVADOR PRINCIPAL: Detecta panel de bloques y aplica mejoras
    // =========================================================================

    /**
     * Observa el DOM para detectar cuándo GrapesJS crea el panel de bloques.
     * Auto-inyecta iconos, chevrons, buscador y actualiza thumbnails.
     */
    function watchForBlocksPanel() {
        // Inyectar estilos CSS.
        injectStyles();

        // Intentar inmediatamente (por si ya existe).
        var icons = injectCategoryIcons();
        var blocks = upgradeBlockThumbnails();
        var carets = fixCategoryCarets();
        initBlockSearch();

        if (icons > 0 || blocks > 0) {
            console.log('[Jaraba Thumbnails] Inicial: ' + blocks + ' bloques duotone, ' + icons + ' iconos categoría, ' + carets + ' chevrons.');
        }

        // Observar mutaciones del DOM para nuevas categorías.
        var observer = new MutationObserver(function () {
            var newIcons = injectCategoryIcons();
            var newCarets = fixCategoryCarets();
            initBlockSearch();
            if (newIcons > 0) {
                console.log('[Jaraba Thumbnails] +' + newIcons + ' iconos de categoría inyectados.');
            }
        });

        // Observar el body para cambios en el panel de bloques.
        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });

        // Auto-desconectar tras 30s para no impactar rendimiento.
        setTimeout(function () {
            observer.disconnect();
        }, 30000);

        // Reintentar la inyección con delays escalonados.
        [500, 1500, 3000, 5000].forEach(function (delay) {
            setTimeout(function () {
                injectCategoryIcons();
                upgradeBlockThumbnails();
                fixCategoryCarets();
                initBlockSearch();
            }, delay);
        });
    }

    // Iniciar observación cuando el DOM esté listo.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', watchForBlocksPanel);
    } else {
        // DOMContentLoaded ya pasó, usar delay para esperar GrapesJS.
        setTimeout(watchForBlocksPanel, 500);
    }

})(Drupal);

