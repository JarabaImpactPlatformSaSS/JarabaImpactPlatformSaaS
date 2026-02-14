/**
 * @file
 * Jaraba Canvas Editor — Registro Centralizado de Iconos Inline SVG.
 *
 * PROPÓSITO:
 * Define iconos SVG inline para reemplazar los emojis usados dentro del
 * contenido HTML de los bloques del Canvas Editor. A diferencia de los
 * thumbnails (que se muestran en el panel de bloques), estos iconos se
 * renderizan DENTRO del canvas y las páginas publicadas.
 *
 * ESTILO SVG:
 * - Monocromo: stroke="currentColor" hereda el color del contexto.
 * - Dimensiones: 24×24 viewBox (se escala con font-size del padre).
 * - Los SVGs son strings para inyección directa en template literals.
 *
 * USO:
 *   const icon = Drupal.jarabaIcons.get('package');
 *   // Retorna el SVG string inline para ese icono.
 *
 * FASE 3: Reemplaza emojis (📦🎯✉️🖼️📅✨🔒🎁🚀👤⭐🎉📞🛒📖🎓💼👥📊)
 * con SVGs consistentes.
 *
 * @see grapesjs-jaraba-thumbnails.js (thumbnails para panel de bloques)
 * @see grapesjs-jaraba-blocks.js (bloques que consumen estos iconos)
 */

(function (Drupal) {
    'use strict';

    /**
     * Wrapper SVG inline estándar.
     * width/height=1em hace que escale con font-size del contenedor.
     *
     * @param {string} inner Contenido SVG interno (paths).
     * @return {string} SVG completo.
     */
    function icon(inner) {
        return `<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${inner}</svg>`;
    }

    const icons = {};

    // =========================================================================
    // OBJECT & COMMERCE ICONS
    // =========================================================================

    // 📦 Package / Producto
    icons['package'] = icon(
        '<path d="M16.5 9.4l-9-5.19"/>' +
        '<path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>' +
        '<polyline points="3.27 6.96 12 12.01 20.73 6.96"/>' +
        '<line x1="12" y1="22.08" x2="12" y2="12"/>'
    );

    // 🛒 Shopping Cart
    icons['shopping-cart'] = icon(
        '<circle cx="9" cy="21" r="1"/>' +
        '<circle cx="20" cy="21" r="1"/>' +
        '<path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>'
    );

    // 🎁 Gift / Regalo
    icons['gift'] = icon(
        '<polyline points="20 12 20 22 4 22 4 12"/>' +
        '<rect x="2" y="7" width="20" height="5"/>' +
        '<line x1="12" y1="22" x2="12" y2="7"/>' +
        '<path d="M12 7H7.5a2.5 2.5 0 010-5C11 2 12 7 12 7z"/>' +
        '<path d="M12 7h4.5a2.5 2.5 0 000-5C13 2 12 7 12 7z"/>'
    );

    // =========================================================================
    // COMMUNICATION ICONS
    // =========================================================================

    // ✉️ Mail / Correo
    icons['mail'] = icon(
        '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>' +
        '<polyline points="22,6 12,13 2,6"/>'
    );

    // 📞 Phone / Teléfono
    icons['phone'] = icon(
        '<path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>'
    );

    // =========================================================================
    // UI & ACTION ICONS
    // =========================================================================

    // 🎯 Target / Objetivo
    icons['target'] = icon(
        '<circle cx="12" cy="12" r="10"/>' +
        '<circle cx="12" cy="12" r="6"/>' +
        '<circle cx="12" cy="12" r="2"/>'
    );

    // ⭐ Star / Estrella
    icons['star'] = icon(
        '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'
    );

    // ⭐ Star filled (for ratings)
    icons['star-filled'] = `<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`;

    // 🔒 Lock / Seguridad
    icons['lock'] = icon(
        '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>' +
        '<path d="M7 11V7a5 5 0 0110 0v4"/>'
    );

    // ✨ Sparkles / Destacado
    icons['sparkles'] = icon(
        '<path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/>' +
        '<path d="M19 13l.75 2.25L22 16l-2.25.75L19 19l-.75-2.25L16 16l2.25-.75L19 13z"/>' +
        '<path d="M5 17l.5 1.5L7 19l-1.5.5L5 21l-.5-1.5L3 19l1.5-.5L5 17z"/>'
    );

    // 🎉 Celebration / Party
    icons['celebration'] = icon(
        '<path d="M5.8 11.3L2 22l10.7-3.79"/>' +
        '<path d="M4 3h.01"/>' +
        '<path d="M22 8h.01"/>' +
        '<path d="M15 2h.01"/>' +
        '<path d="M22 20h.01"/>' +
        '<path d="M22 2l-2.24.75a2.9 2.9 0 00-1.96 3.12v0L18.25 8l2.25-.75a2.9 2.9 0 001.21-4.54"/>' +
        '<path d="M13.21 7.48l-1.52 3.28a2 2 0 01-2.65 1l-3.05-1.39A2 2 0 015 7.73L6.39 4.1a2 2 0 012.65-1l4.17 1.9"/>'
    );

    // 🚀 Rocket / Lanzamiento
    icons['rocket'] = icon(
        '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 00-2.91-.09z"/>' +
        '<path d="M12 15l-3-3a22 22 0 012-3.95A12.88 12.88 0 0122 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 01-4 2z"/>' +
        '<path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/>' +
        '<path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>'
    );

    // =========================================================================
    // PEOPLE ICONS
    // =========================================================================

    // 👤 User / Persona
    icons['user'] = icon(
        '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>' +
        '<circle cx="12" cy="7" r="4"/>'
    );

    // 👥 Users / Equipo
    icons['users'] = icon(
        '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>' +
        '<circle cx="9" cy="7" r="4"/>' +
        '<path d="M23 21v-2a4 4 0 00-3-3.87"/>' +
        '<path d="M16 3.13a4 4 0 010 7.75"/>'
    );

    // =========================================================================
    // CONTENT & MEDIA ICONS
    // =========================================================================

    // 🖼️ Image / Imagen
    icons['image'] = icon(
        '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>' +
        '<circle cx="8.5" cy="8.5" r="1.5"/>' +
        '<polyline points="21 15 16 10 5 21"/>'
    );

    // 📅 Calendar / Calendario
    icons['calendar'] = icon(
        '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>' +
        '<line x1="16" y1="2" x2="16" y2="6"/>' +
        '<line x1="8" y1="2" x2="8" y2="6"/>' +
        '<line x1="3" y1="10" x2="21" y2="10"/>'
    );

    // 📖 Book / Libro
    icons['book'] = icon(
        '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>' +
        '<path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>'
    );

    // 📊 Chart / Estadísticas
    icons['chart'] = icon(
        '<line x1="18" y1="20" x2="18" y2="10"/>' +
        '<line x1="12" y1="20" x2="12" y2="4"/>' +
        '<line x1="6" y1="20" x2="6" y2="14"/>'
    );

    // =========================================================================
    // PROFESSIONAL ICONS
    // =========================================================================

    // 💼 Briefcase / Maletín
    icons['briefcase'] = icon(
        '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>' +
        '<path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>'
    );

    // 🎓 Graduation / Formación
    icons['graduation'] = icon(
        '<path d="M22 10l-10-5-10 5 10 5 10-5z"/>' +
        '<path d="M6 12v5c3 3 9 3 12 0v-5"/>' +
        '<line x1="22" y1="10" x2="22" y2="16"/>'
    );

    // =========================================================================
    // REGISTRY API
    // =========================================================================

    /**
     * @namespace Drupal.jarabaIcons
     */
    Drupal.jarabaIcons = {
        /**
         * Obtiene el SVG inline de un icono.
         *
         * @param {string} name Nombre del icono.
         * @param {string} [fallback=''] SVG fallback si no existe.
         * @return {string} SVG string.
         */
        get: function (name, fallback) {
            return icons[name] || fallback || '';
        },

        /**
         * Genera N estrellas filled (para ratings).
         *
         * @param {number} count Número de estrellas.
         * @return {string} String con N SVG estrellas.
         */
        stars: function (count) {
            var result = '';
            for (var i = 0; i < count; i++) {
                result += icons['star-filled'];
            }
            return result;
        },

        /**
         * Lista todos los nombres de iconos disponibles.
         *
         * @return {string[]}
         */
        list: function () {
            return Object.keys(icons);
        },
    };

})(Drupal);
