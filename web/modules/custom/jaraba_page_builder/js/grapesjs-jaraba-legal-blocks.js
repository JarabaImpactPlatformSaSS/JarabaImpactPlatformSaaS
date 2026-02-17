/**
 * @file
 * grapesjs-jaraba-legal-blocks.js — 11 Bloques JarabaLex para Canvas Editor.
 *
 * Estructura: Plugin GrapesJS que registra 11 bloques verticales para
 *   landing pages de despachos juridicos / JarabaLex.
 * Logica: Cada bloque tiene HTML semantico, SVG icon duotone, y usa
 *   design tokens var(--ej-legal-*) para tematizacion.
 *
 * Bloques:
 *  1. legal-hero         — Hero seccion despacho (gradient + dual CTA)
 *  2. legal-services      — Grid de areas de practica (6 cards)
 *  3. legal-team          — Equipo del despacho (cards de abogados)
 *  4. legal-expertise     — Features/capacidades IA (6 features)
 *  5. legal-stats         — Estadisticas animadas (4 KPIs)
 *  6. legal-testimonials  — Testimonios de clientes
 *  7. legal-pricing       — Tabla de precios (3 planes)
 *  8. legal-faq           — FAQ accordion (preguntas juridicas)
 *  9. legal-lead-magnet   — Diagnostico Legal gratuito CTA
 * 10. legal-contact       — Formulario de contacto despacho
 * 11. legal-cta           — CTA final de conversion
 *
 * @see grapesjs-jaraba-blocks.js (patron de referencia)
 * @see grapesjs-jaraba-canvas.js (loadPlugin)
 */

(function (grapesjs, Drupal) {
  'use strict';

  function jarabaLegalBlocksPlugin(editor) {
    var blockManager = editor.BlockManager;

    // Registrar categoria 'legal'.
    blockManager.getCategories().add({
      id: 'legal',
      label: Drupal.t('JarabaLex'),
      open: false,
    });

    // =========================================================================
    // SVG Icons (40x40, duotone: fondo opacity 0.15 + stroke solido)
    // =========================================================================

    var icons = {
      hero: '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="36" height="36" rx="4" fill="#1E3A5F" opacity="0.15"/><path d="M20 8l8 6v12H12V14l8-6z" stroke="#1E3A5F" stroke-width="2" fill="none"/><rect x="17" y="20" width="6" height="6" stroke="#C8A96E" stroke-width="1.5" fill="none"/></svg>',
      services: '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="36" height="36" rx="4" fill="#1E3A5F" opacity="0.15"/><rect x="5" y="5" width="12" height="12" rx="2" stroke="#1E3A5F" stroke-width="1.5" fill="none"/><rect x="23" y="5" width="12" height="12" rx="2" stroke="#1E3A5F" stroke-width="1.5" fill="none"/><rect x="5" y="23" width="12" height="12" rx="2" stroke="#C8A96E" stroke-width="1.5" fill="none"/><rect x="23" y="23" width="12" height="12" rx="2" stroke="#C8A96E" stroke-width="1.5" fill="none"/></svg>',
      team: '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="36" height="36" rx="4" fill="#1E3A5F" opacity="0.15"/><circle cx="14" cy="15" r="4" stroke="#1E3A5F" stroke-width="1.5" fill="none"/><circle cx="26" cy="15" r="4" stroke="#1E3A5F" stroke-width="1.5" fill="none"/><path d="M8 30c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="#C8A96E" stroke-width="1.5" fill="none"/><path d="M20 30c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="#C8A96E" stroke-width="1.5" fill="none"/></svg>',
      expertise: '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="36" height="36" rx="4" fill="#1E3A5F" opacity="0.15"/><path d="M20 6l3 6h6l-5 4 2 6-6-4-6 4 2-6-5-4h6l3-6z" stroke="#C8A96E" stroke-width="1.5" fill="none"/><line x1="10" y1="30" x2="30" y2="30" stroke="#1E3A5F" stroke-width="1.5"/><line x1="12" y1="34" x2="28" y2="34" stroke="#1E3A5F" stroke-width="1.5"/></svg>',
      stats: '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="36" height="36" rx="4" fill="#1E3A5F" opacity="0.15"/><rect x="7" y="20" width="6" height="14" rx="1" stroke="#1E3A5F" stroke-width="1.5" fill="none"/><rect x="17" y="12" width="6" height="22" rx="1" stroke="#C8A96E" stroke-width="1.5" fill="none"/><rect x="27" y="6" width="6" height="28" rx="1" stroke="#1E3A5F" stroke-width="1.5" fill="none"/></svg>',
      testimonials: '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="36" height="36" rx="4" fill="#1E3A5F" opacity="0.15"/><path d="M8 10h24v16H20l-6 4v-4H8V10z" stroke="#1E3A5F" stroke-width="1.5" fill="none"/><text x="14" y="22" font-size="14" fill="#C8A96E" font-family="serif">&ldquo;</text></svg>',
      pricing: '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="36" height="36" rx="4" fill="#1E3A5F" opacity="0.15"/><rect x="4" y="8" width="9" height="24" rx="2" stroke="#1E3A5F" stroke-width="1.5" fill="none"/><rect x="15.5" y="5" width="9" height="27" rx="2" stroke="#C8A96E" stroke-width="1.5" fill="none"/><rect x="27" y="8" width="9" height="24" rx="2" stroke="#1E3A5F" stroke-width="1.5" fill="none"/></svg>',
      faq: '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="36" height="36" rx="4" fill="#1E3A5F" opacity="0.15"/><circle cx="20" cy="18" r="10" stroke="#1E3A5F" stroke-width="1.5" fill="none"/><text x="16.5" y="23" font-size="14" fill="#C8A96E" font-weight="bold" font-family="sans-serif">?</text><circle cx="20" cy="32" r="1.5" fill="#1E3A5F"/></svg>',
      leadMagnet: '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="36" height="36" rx="4" fill="#1E3A5F" opacity="0.15"/><path d="M20 6v20M14 20l6 6 6-6" stroke="#C8A96E" stroke-width="2" fill="none"/><rect x="8" y="30" width="24" height="4" rx="1" stroke="#1E3A5F" stroke-width="1.5" fill="none"/></svg>',
      contact: '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="36" height="36" rx="4" fill="#1E3A5F" opacity="0.15"/><rect x="6" y="10" width="28" height="20" rx="2" stroke="#1E3A5F" stroke-width="1.5" fill="none"/><polyline points="6,10 20,22 34,10" stroke="#C8A96E" stroke-width="1.5" fill="none"/></svg>',
      cta: '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="36" height="36" rx="4" fill="#1E3A5F" opacity="0.15"/><rect x="8" y="14" width="24" height="12" rx="6" stroke="#C8A96E" stroke-width="2" fill="none"/><path d="M16 20h8M22 17l3 3-3 3" stroke="#1E3A5F" stroke-width="1.5" fill="none"/></svg>',
    };

    // =========================================================================
    // Block definitions (11 bloques)
    // =========================================================================

    var legalBlocks = [

      // 1. LEGAL HERO
      {
        id: 'legal-hero',
        label: Drupal.t('Hero Despacho'),
        media: icons.hero,
        content: [
          '<section class="jaraba-legal-hero" style="background: linear-gradient(135deg, var(--ej-legal-primary, #1E3A5F) 0%, #0F1F33 100%); padding: 80px 24px; text-align: center; color: #fff;">',
          '  <div class="jaraba-legal-hero__container" style="max-width: 800px; margin: 0 auto;">',
          '    <h1 class="jaraba-legal-hero__title" style="font-size: 2.75rem; font-weight: 800; margin: 0 0 16px; line-height: 1.15;">Inteligencia Legal con IA</h1>',
          '    <p class="jaraba-legal-hero__subtitle" style="font-size: 1.25rem; opacity: 0.9; margin: 0 0 32px; line-height: 1.6;">Busqueda de jurisprudencia, analisis y redaccion de escritos con la precision de la inteligencia artificial.</p>',
          '    <div class="jaraba-legal-hero__actions" style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">',
          '      <a href="#" class="jaraba-legal-hero__btn jaraba-legal-hero__btn--primary" style="display: inline-block; padding: 14px 32px; background: var(--ej-legal-accent, #C8A96E); color: #1E3A5F; font-weight: 700; border-radius: 8px; text-decoration: none; font-size: 1rem;">Comenzar Gratis</a>',
          '      <a href="#" class="jaraba-legal-hero__btn jaraba-legal-hero__btn--outline" style="display: inline-block; padding: 14px 32px; border: 2px solid rgba(255,255,255,0.5); color: #fff; font-weight: 600; border-radius: 8px; text-decoration: none; font-size: 1rem;">Ver Demo</a>',
          '    </div>',
          '  </div>',
          '</section>',
        ].join('\n'),
      },

      // 2. LEGAL SERVICES
      {
        id: 'legal-services',
        label: Drupal.t('Areas de Practica'),
        media: icons.services,
        content: [
          '<section class="jaraba-legal-services" style="padding: 64px 24px; background: var(--ej-legal-surface, #F5F3EF);">',
          '  <div style="max-width: 1100px; margin: 0 auto;">',
          '    <h2 style="text-align: center; font-size: 2rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 12px;">Areas de Practica</h2>',
          '    <p style="text-align: center; color: var(--ej-legal-text-light, #6B7280); margin: 0 0 40px; font-size: 1.05rem;">Cobertura integral en todas las jurisdicciones.</p>',
          '    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">',
          '      <div class="jaraba-legal-services__card" style="background: #fff; border-radius: 12px; padding: 28px; border: 1px solid rgba(30,58,95,0.08);">',
          '        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Derecho Civil</h3>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); margin: 0; font-size: 0.9375rem; line-height: 1.6;">Contratos, obligaciones, responsabilidad y derechos reales.</p>',
          '      </div>',
          '      <div class="jaraba-legal-services__card" style="background: #fff; border-radius: 12px; padding: 28px; border: 1px solid rgba(30,58,95,0.08);">',
          '        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Derecho Penal</h3>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); margin: 0; font-size: 0.9375rem; line-height: 1.6;">Defensa penal, delitos economicos y compliance.</p>',
          '      </div>',
          '      <div class="jaraba-legal-services__card" style="background: #fff; border-radius: 12px; padding: 28px; border: 1px solid rgba(30,58,95,0.08);">',
          '        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Derecho Laboral</h3>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); margin: 0; font-size: 0.9375rem; line-height: 1.6;">Despidos, ERE, negociacion colectiva y seguridad social.</p>',
          '      </div>',
          '      <div class="jaraba-legal-services__card" style="background: #fff; border-radius: 12px; padding: 28px; border: 1px solid rgba(30,58,95,0.08);">',
          '        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Derecho Mercantil</h3>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); margin: 0; font-size: 0.9375rem; line-height: 1.6;">Sociedades, M&A, concursal y propiedad industrial.</p>',
          '      </div>',
          '      <div class="jaraba-legal-services__card" style="background: #fff; border-radius: 12px; padding: 28px; border: 1px solid rgba(30,58,95,0.08);">',
          '        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Derecho Tributario</h3>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); margin: 0; font-size: 0.9375rem; line-height: 1.6;">Planificacion fiscal, inspecciones y recursos tributarios.</p>',
          '      </div>',
          '      <div class="jaraba-legal-services__card" style="background: #fff; border-radius: 12px; padding: 28px; border: 1px solid rgba(30,58,95,0.08);">',
          '        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Derecho Administrativo</h3>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); margin: 0; font-size: 0.9375rem; line-height: 1.6;">Contratacion publica, urbanismo y responsabilidad patrimonial.</p>',
          '      </div>',
          '    </div>',
          '  </div>',
          '</section>',
        ].join('\n'),
      },

      // 3. LEGAL TEAM
      {
        id: 'legal-team',
        label: Drupal.t('Equipo del Despacho'),
        media: icons.team,
        content: [
          '<section class="jaraba-legal-team" style="padding: 64px 24px; background: #fff;">',
          '  <div style="max-width: 1100px; margin: 0 auto;">',
          '    <h2 style="text-align: center; font-size: 2rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 12px;">Nuestro Equipo</h2>',
          '    <p style="text-align: center; color: var(--ej-legal-text-light, #6B7280); margin: 0 0 40px; font-size: 1.05rem;">Profesionales comprometidos con la excelencia juridica.</p>',
          '    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 24px;">',
          '      <div class="jaraba-legal-team__card" style="text-align: center; padding: 28px 20px; border-radius: 12px; border: 1px solid rgba(30,58,95,0.08);">',
          '        <div style="width: 96px; height: 96px; border-radius: 50%; background: var(--ej-legal-surface, #F5F3EF); margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--ej-legal-primary, #1E3A5F);">AG</div>',
          '        <h3 style="font-size: 1.0625rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 4px;">Ana Garcia Lopez</h3>',
          '        <p style="color: var(--ej-legal-accent, #C8A96E); font-size: 0.875rem; margin: 0 0 8px; font-weight: 600;">Socia Directora</p>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); font-size: 0.8125rem; margin: 0;">Especialista en Derecho Civil y Mercantil. 20 anos de experiencia.</p>',
          '      </div>',
          '      <div class="jaraba-legal-team__card" style="text-align: center; padding: 28px 20px; border-radius: 12px; border: 1px solid rgba(30,58,95,0.08);">',
          '        <div style="width: 96px; height: 96px; border-radius: 50%; background: var(--ej-legal-surface, #F5F3EF); margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--ej-legal-primary, #1E3A5F);">CM</div>',
          '        <h3 style="font-size: 1.0625rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 4px;">Carlos Martinez Ruiz</h3>',
          '        <p style="color: var(--ej-legal-accent, #C8A96E); font-size: 0.875rem; margin: 0 0 8px; font-weight: 600;">Socio</p>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); font-size: 0.8125rem; margin: 0;">Experto en Derecho Penal Economico y Compliance.</p>',
          '      </div>',
          '      <div class="jaraba-legal-team__card" style="text-align: center; padding: 28px 20px; border-radius: 12px; border: 1px solid rgba(30,58,95,0.08);">',
          '        <div style="width: 96px; height: 96px; border-radius: 50%; background: var(--ej-legal-surface, #F5F3EF); margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--ej-legal-primary, #1E3A5F);">LS</div>',
          '        <h3 style="font-size: 1.0625rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 4px;">Laura Sanchez Torres</h3>',
          '        <p style="color: var(--ej-legal-accent, #C8A96E); font-size: 0.875rem; margin: 0 0 8px; font-weight: 600;">Asociada Senior</p>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); font-size: 0.8125rem; margin: 0;">Derecho Laboral y Seguridad Social.</p>',
          '      </div>',
          '    </div>',
          '  </div>',
          '</section>',
        ].join('\n'),
      },

      // 4. LEGAL EXPERTISE (Features IA)
      {
        id: 'legal-expertise',
        label: Drupal.t('Capacidades IA'),
        media: icons.expertise,
        content: [
          '<section class="jaraba-legal-expertise" style="padding: 64px 24px; background: var(--ej-legal-surface, #F5F3EF);">',
          '  <div style="max-width: 1100px; margin: 0 auto;">',
          '    <h2 style="text-align: center; font-size: 2rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 12px;">Inteligencia Artificial Juridica</h2>',
          '    <p style="text-align: center; color: var(--ej-legal-text-light, #6B7280); margin: 0 0 40px; font-size: 1.05rem;">Tecnologia de vanguardia al servicio del Derecho.</p>',
          '    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px;">',
          '      <div style="background: #fff; border-radius: 12px; padding: 24px; border-left: 4px solid var(--ej-legal-accent, #C8A96E);">',
          '        <h3 style="font-size: 1rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Busqueda Semantica</h3>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); margin: 0; font-size: 0.9375rem; line-height: 1.6;">Encuentra jurisprudencia relevante por significado, no solo por palabras clave.</p>',
          '      </div>',
          '      <div style="background: #fff; border-radius: 12px; padding: 24px; border-left: 4px solid var(--ej-legal-accent, #C8A96E);">',
          '        <h3 style="font-size: 1rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">8 Fuentes Oficiales</h3>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); margin: 0; font-size: 0.9375rem; line-height: 1.6;">CENDOJ, BOE, EUR-Lex, CURIA, HUDOC, EDPB, DGT y mas.</p>',
          '      </div>',
          '      <div style="background: #fff; border-radius: 12px; padding: 24px; border-left: 4px solid var(--ej-legal-accent, #C8A96E);">',
          '        <h3 style="font-size: 1rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Alertas Inteligentes</h3>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); margin: 0; font-size: 0.9375rem; line-height: 1.6;">Recibe notificaciones cuando cambie la doctrina en tus temas de interes.</p>',
          '      </div>',
          '      <div style="background: #fff; border-radius: 12px; padding: 24px; border-left: 4px solid var(--ej-legal-accent, #C8A96E);">',
          '        <h3 style="font-size: 1rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Citaciones Automaticas</h3>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); margin: 0; font-size: 0.9375rem; line-height: 1.6;">Genera citas en 4 formatos: formal, resumida, bibliografica y nota al pie.</p>',
          '      </div>',
          '      <div style="background: #fff; border-radius: 12px; padding: 24px; border-left: 4px solid var(--ej-legal-accent, #C8A96E);">',
          '        <h3 style="font-size: 1rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Digest Semanal</h3>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); margin: 0; font-size: 0.9375rem; line-height: 1.6;">Resumen personalizado de novedades jurisprudenciales y normativas.</p>',
          '      </div>',
          '      <div style="background: #fff; border-radius: 12px; padding: 24px; border-left: 4px solid var(--ej-legal-accent, #C8A96E);">',
          '        <h3 style="font-size: 1rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Redaccion con IA</h3>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); margin: 0; font-size: 0.9375rem; line-height: 1.6;">Genera borradores de escritos procesales con citas y formato legal profesional.</p>',
          '      </div>',
          '    </div>',
          '  </div>',
          '</section>',
        ].join('\n'),
      },

      // 5. LEGAL STATS
      {
        id: 'legal-stats',
        label: Drupal.t('Estadisticas Legales'),
        media: icons.stats,
        content: [
          '<section class="jaraba-legal-stats" style="padding: 64px 24px; background: var(--ej-legal-primary, #1E3A5F); color: #fff;">',
          '  <div style="max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 32px; text-align: center;">',
          '    <div class="jaraba-legal-stats__item">',
          '      <div style="font-size: 3rem; font-weight: 800; color: var(--ej-legal-accent, #C8A96E); line-height: 1;">+2.500</div>',
          '      <div style="font-size: 0.9375rem; opacity: 0.85; margin-top: 8px;">Expedientes gestionados</div>',
          '    </div>',
          '    <div class="jaraba-legal-stats__item">',
          '      <div style="font-size: 3rem; font-weight: 800; color: var(--ej-legal-accent, #C8A96E); line-height: 1;">25</div>',
          '      <div style="font-size: 0.9375rem; opacity: 0.85; margin-top: 8px;">Anos de experiencia</div>',
          '    </div>',
          '    <div class="jaraba-legal-stats__item">',
          '      <div style="font-size: 3rem; font-weight: 800; color: var(--ej-legal-accent, #C8A96E); line-height: 1;">98%</div>',
          '      <div style="font-size: 0.9375rem; opacity: 0.85; margin-top: 8px;">Satisfaccion de clientes</div>',
          '    </div>',
          '    <div class="jaraba-legal-stats__item">',
          '      <div style="font-size: 3rem; font-weight: 800; color: var(--ej-legal-accent, #C8A96E); line-height: 1;">8</div>',
          '      <div style="font-size: 0.9375rem; opacity: 0.85; margin-top: 8px;">Fuentes oficiales integradas</div>',
          '    </div>',
          '  </div>',
          '</section>',
        ].join('\n'),
      },

      // 6. LEGAL TESTIMONIALS
      {
        id: 'legal-testimonials',
        label: Drupal.t('Testimonios Clientes'),
        media: icons.testimonials,
        content: [
          '<section class="jaraba-legal-testimonials" style="padding: 64px 24px; background: #fff;">',
          '  <div style="max-width: 1100px; margin: 0 auto;">',
          '    <h2 style="text-align: center; font-size: 2rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 40px;">Lo que dicen nuestros clientes</h2>',
          '    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px;">',
          '      <div class="jaraba-legal-testimonials__card" style="background: var(--ej-legal-surface, #F5F3EF); border-radius: 12px; padding: 28px; position: relative;">',
          '        <div style="font-size: 3rem; color: var(--ej-legal-accent, #C8A96E); line-height: 1; margin-bottom: 12px; font-family: serif;">&ldquo;</div>',
          '        <p style="color: var(--ej-legal-text, #1A1A2E); font-size: 0.9375rem; line-height: 1.7; margin: 0 0 16px; font-style: italic;">Gracias a JarabaLex encontramos la jurisprudencia clave para nuestro recurso en minutos. Antes tardabamos horas.</p>',
          '        <div style="display: flex; align-items: center; gap: 12px;">',
          '          <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--ej-legal-primary, #1E3A5F); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8125rem;">MR</div>',
          '          <div>',
          '            <div style="font-weight: 600; font-size: 0.875rem; color: var(--ej-legal-primary, #1E3A5F);">Maria Rodriguez</div>',
          '            <div style="font-size: 0.75rem; color: var(--ej-legal-text-light, #6B7280);">Abogada, Bufete Rodriguez & Asociados</div>',
          '          </div>',
          '        </div>',
          '      </div>',
          '      <div class="jaraba-legal-testimonials__card" style="background: var(--ej-legal-surface, #F5F3EF); border-radius: 12px; padding: 28px; position: relative;">',
          '        <div style="font-size: 3rem; color: var(--ej-legal-accent, #C8A96E); line-height: 1; margin-bottom: 12px; font-family: serif;">&ldquo;</div>',
          '        <p style="color: var(--ej-legal-text, #1A1A2E); font-size: 0.9375rem; line-height: 1.7; margin: 0 0 16px; font-style: italic;">Las alertas inteligentes nos avisaron de un cambio doctrinal que afectaba a 15 expedientes abiertos. Inestimable.</p>',
          '        <div style="display: flex; align-items: center; gap: 12px;">',
          '          <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--ej-legal-primary, #1E3A5F); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8125rem;">JP</div>',
          '          <div>',
          '            <div style="font-weight: 600; font-size: 0.875rem; color: var(--ej-legal-primary, #1E3A5F);">Javier Perez</div>',
          '            <div style="font-size: 0.75rem; color: var(--ej-legal-text-light, #6B7280);">Socio Director, Despacho Perez Legal</div>',
          '          </div>',
          '        </div>',
          '      </div>',
          '    </div>',
          '  </div>',
          '</section>',
        ].join('\n'),
      },

      // 7. LEGAL PRICING
      {
        id: 'legal-pricing',
        label: Drupal.t('Precios JarabaLex'),
        media: icons.pricing,
        content: [
          '<section class="jaraba-legal-pricing" style="padding: 64px 24px; background: var(--ej-legal-surface, #F5F3EF);">',
          '  <div style="max-width: 1100px; margin: 0 auto;">',
          '    <h2 style="text-align: center; font-size: 2rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 12px;">Planes y Precios</h2>',
          '    <p style="text-align: center; color: var(--ej-legal-text-light, #6B7280); margin: 0 0 40px; font-size: 1.05rem;">Elige el plan que mejor se adapte a tu practica.</p>',
          '    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; align-items: start;">',
          // Free
          '      <div class="jaraba-legal-pricing__plan" style="background: #fff; border-radius: 12px; padding: 32px 24px; border: 1px solid rgba(30,58,95,0.08); text-align: center;">',
          '        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Free</h3>',
          '        <div style="font-size: 2.5rem; font-weight: 800; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 4px;">0&euro;</div>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); font-size: 0.875rem; margin: 0 0 24px;">/mes</p>',
          '        <ul style="text-align: left; list-style: none; padding: 0; margin: 0 0 24px; font-size: 0.9375rem; color: var(--ej-legal-text, #1A1A2E);">',
          '          <li style="padding: 6px 0; border-bottom: 1px solid rgba(30,58,95,0.06);">10 busquedas / mes</li>',
          '          <li style="padding: 6px 0; border-bottom: 1px solid rgba(30,58,95,0.06);">3 alertas activas</li>',
          '          <li style="padding: 6px 0;">Copiloto basico</li>',
          '        </ul>',
          '        <a href="#" style="display: block; padding: 12px; background: var(--ej-legal-surface, #F5F3EF); color: var(--ej-legal-primary, #1E3A5F); border-radius: 8px; text-decoration: none; font-weight: 600;">Comenzar Gratis</a>',
          '      </div>',
          // Starter
          '      <div class="jaraba-legal-pricing__plan jaraba-legal-pricing__plan--featured" style="background: var(--ej-legal-primary, #1E3A5F); border-radius: 12px; padding: 32px 24px; text-align: center; color: #fff; transform: scale(1.03); box-shadow: 0 8px 32px rgba(30,58,95,0.25);">',
          '        <div style="font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.1em; background: var(--ej-legal-accent, #C8A96E); color: #1E3A5F; display: inline-block; padding: 4px 12px; border-radius: 9999px; font-weight: 700; margin-bottom: 12px;">Mas Popular</div>',
          '        <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0 0 8px;">Starter</h3>',
          '        <div style="font-size: 2.5rem; font-weight: 800; margin: 0 0 4px;">49&euro;</div>',
          '        <p style="opacity: 0.8; font-size: 0.875rem; margin: 0 0 24px;">/mes</p>',
          '        <ul style="text-align: left; list-style: none; padding: 0; margin: 0 0 24px; font-size: 0.9375rem;">',
          '          <li style="padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.15);">Busquedas ilimitadas</li>',
          '          <li style="padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.15);">Alertas ilimitadas</li>',
          '          <li style="padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.15);">Digest semanal</li>',
          '          <li style="padding: 6px 0;">Copiloto avanzado + IA</li>',
          '        </ul>',
          '        <a href="#" style="display: block; padding: 12px; background: var(--ej-legal-accent, #C8A96E); color: #1E3A5F; border-radius: 8px; text-decoration: none; font-weight: 700;">Elegir Starter</a>',
          '      </div>',
          // Pro
          '      <div class="jaraba-legal-pricing__plan" style="background: #fff; border-radius: 12px; padding: 32px 24px; border: 1px solid rgba(30,58,95,0.08); text-align: center;">',
          '        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 8px;">Pro</h3>',
          '        <div style="font-size: 2.5rem; font-weight: 800; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 4px;">99&euro;</div>',
          '        <p style="color: var(--ej-legal-text-light, #6B7280); font-size: 0.875rem; margin: 0 0 24px;">/mes</p>',
          '        <ul style="text-align: left; list-style: none; padding: 0; margin: 0 0 24px; font-size: 0.9375rem; color: var(--ej-legal-text, #1A1A2E);">',
          '          <li style="padding: 6px 0; border-bottom: 1px solid rgba(30,58,95,0.06);">Todo de Starter</li>',
          '          <li style="padding: 6px 0; border-bottom: 1px solid rgba(30,58,95,0.06);">Analisis jurisprudencial</li>',
          '          <li style="padding: 6px 0; border-bottom: 1px solid rgba(30,58,95,0.06);">Grafo de resoluciones</li>',
          '          <li style="padding: 6px 0;">API + integraciones</li>',
          '        </ul>',
          '        <a href="#" style="display: block; padding: 12px; background: var(--ej-legal-surface, #F5F3EF); color: var(--ej-legal-primary, #1E3A5F); border-radius: 8px; text-decoration: none; font-weight: 600;">Elegir Pro</a>',
          '      </div>',
          '    </div>',
          '  </div>',
          '</section>',
        ].join('\n'),
      },

      // 8. LEGAL FAQ
      {
        id: 'legal-faq',
        label: Drupal.t('FAQ Legal'),
        media: icons.faq,
        content: [
          '<section class="jaraba-legal-faq" style="padding: 64px 24px; background: #fff;">',
          '  <div style="max-width: 740px; margin: 0 auto;">',
          '    <h2 style="text-align: center; font-size: 2rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 40px;">Preguntas Frecuentes</h2>',
          '    <div class="jaraba-legal-faq__list" style="display: flex; flex-direction: column; gap: 12px;">',
          '      <details class="jaraba-legal-faq__item" style="border: 1px solid rgba(30,58,95,0.1); border-radius: 8px; padding: 16px 20px;">',
          '        <summary style="cursor: pointer; font-weight: 600; color: var(--ej-legal-primary, #1E3A5F); font-size: 0.9375rem;">Que fuentes juridicas estan disponibles?</summary>',
          '        <p style="margin: 12px 0 0; color: var(--ej-legal-text-light, #6B7280); font-size: 0.9375rem; line-height: 1.6;">JarabaLex integra 8 fuentes oficiales: CENDOJ, BOE, EUR-Lex, CURIA (TJUE), HUDOC (TEDH), EDPB, DGT y DGRN.</p>',
          '      </details>',
          '      <details class="jaraba-legal-faq__item" style="border: 1px solid rgba(30,58,95,0.1); border-radius: 8px; padding: 16px 20px;">',
          '        <summary style="cursor: pointer; font-weight: 600; color: var(--ej-legal-primary, #1E3A5F); font-size: 0.9375rem;">Como funcionan las alertas inteligentes?</summary>',
          '        <p style="margin: 12px 0 0; color: var(--ej-legal-text-light, #6B7280); font-size: 0.9375rem; line-height: 1.6;">Configuras temas, jurisdicciones y fuentes de interes. La IA monitoriza nuevas resoluciones y te notifica cuando detecta cambios relevantes en la doctrina.</p>',
          '      </details>',
          '      <details class="jaraba-legal-faq__item" style="border: 1px solid rgba(30,58,95,0.1); border-radius: 8px; padding: 16px 20px;">',
          '        <summary style="cursor: pointer; font-weight: 600; color: var(--ej-legal-primary, #1E3A5F); font-size: 0.9375rem;">Puedo citar directamente en mis escritos?</summary>',
          '        <p style="margin: 12px 0 0; color: var(--ej-legal-text-light, #6B7280); font-size: 0.9375rem; line-height: 1.6;">Si. JarabaLex genera citas en 4 formatos (formal, resumida, bibliografica y nota al pie) y las inserta directamente en tus expedientes.</p>',
          '      </details>',
          '      <details class="jaraba-legal-faq__item" style="border: 1px solid rgba(30,58,95,0.1); border-radius: 8px; padding: 16px 20px;">',
          '        <summary style="cursor: pointer; font-weight: 600; color: var(--ej-legal-primary, #1E3A5F); font-size: 0.9375rem;">Los datos de mi despacho estan seguros?</summary>',
          '        <p style="margin: 12px 0 0; color: var(--ej-legal-text-light, #6B7280); font-size: 0.9375rem; line-height: 1.6;">Absolutamente. Usamos cifrado AES-256-GCM, multi-tenancy estricto y cumplimos RGPD. Tus datos nunca se comparten entre cuentas.</p>',
          '      </details>',
          '      <details class="jaraba-legal-faq__item" style="border: 1px solid rgba(30,58,95,0.1); border-radius: 8px; padding: 16px 20px;">',
          '        <summary style="cursor: pointer; font-weight: 600; color: var(--ej-legal-primary, #1E3A5F); font-size: 0.9375rem;">Puedo integrar JarabaLex con LexNET?</summary>',
          '        <p style="margin: 12px 0 0; color: var(--ej-legal-text-light, #6B7280); font-size: 0.9375rem; line-height: 1.6;">Si. JarabaLex se integra con LexNET para recibir notificaciones judiciales y presentar escritos directamente desde la plataforma.</p>',
          '      </details>',
          '    </div>',
          '  </div>',
          '</section>',
        ].join('\n'),
      },

      // 9. LEGAL LEAD MAGNET
      {
        id: 'legal-lead-magnet',
        label: Drupal.t('Diagnostico Legal'),
        media: icons.leadMagnet,
        content: [
          '<section class="jaraba-legal-lead-magnet" style="padding: 64px 24px; background: linear-gradient(135deg, var(--ej-legal-primary, #1E3A5F) 0%, #0F1F33 100%); color: #fff;">',
          '  <div style="max-width: 700px; margin: 0 auto; text-align: center;">',
          '    <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: rgba(200,169,110,0.2); margin-bottom: 20px;">',
          '      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#C8A96E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>',
          '    </div>',
          '    <h2 style="font-size: 2rem; font-weight: 800; margin: 0 0 12px;">Diagnostico Legal Gratuito</h2>',
          '    <p style="font-size: 1.125rem; opacity: 0.9; margin: 0 0 32px; line-height: 1.6;">Analiza la salud digital de tu practica juridica. Evaluamos tu flujo de trabajo, busqueda de jurisprudencia y gestion documental.</p>',
          '    <a href="/jarabalex/diagnostico-legal" style="display: inline-block; padding: 16px 40px; background: var(--ej-legal-accent, #C8A96E); color: #1E3A5F; font-weight: 700; border-radius: 8px; text-decoration: none; font-size: 1.0625rem;">Solicitar Diagnostico Gratis</a>',
          '    <p style="font-size: 0.8125rem; opacity: 0.6; margin-top: 16px;">Sin compromiso. Resultados en 48 horas.</p>',
          '  </div>',
          '</section>',
        ].join('\n'),
      },

      // 10. LEGAL CONTACT
      {
        id: 'legal-contact',
        label: Drupal.t('Contacto Despacho'),
        media: icons.contact,
        content: [
          '<section class="jaraba-legal-contact" style="padding: 64px 24px; background: var(--ej-legal-surface, #F5F3EF);">',
          '  <div style="max-width: 700px; margin: 0 auto;">',
          '    <h2 style="text-align: center; font-size: 2rem; font-weight: 700; color: var(--ej-legal-primary, #1E3A5F); margin: 0 0 12px;">Contacte con Nosotros</h2>',
          '    <p style="text-align: center; color: var(--ej-legal-text-light, #6B7280); margin: 0 0 32px; font-size: 1.05rem;">Primera consulta sin compromiso.</p>',
          '    <form class="jaraba-legal-contact__form" style="display: flex; flex-direction: column; gap: 16px; background: #fff; padding: 32px; border-radius: 12px; border: 1px solid rgba(30,58,95,0.08);">',
          '      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">',
          '        <input type="text" placeholder="Nombre" style="padding: 12px 16px; border: 1px solid rgba(30,58,95,0.15); border-radius: 8px; font-size: 0.9375rem; outline: none;"/>',
          '        <input type="text" placeholder="Apellidos" style="padding: 12px 16px; border: 1px solid rgba(30,58,95,0.15); border-radius: 8px; font-size: 0.9375rem; outline: none;"/>',
          '      </div>',
          '      <input type="email" placeholder="Email profesional" style="padding: 12px 16px; border: 1px solid rgba(30,58,95,0.15); border-radius: 8px; font-size: 0.9375rem; outline: none;"/>',
          '      <input type="tel" placeholder="Telefono" style="padding: 12px 16px; border: 1px solid rgba(30,58,95,0.15); border-radius: 8px; font-size: 0.9375rem; outline: none;"/>',
          '      <select style="padding: 12px 16px; border: 1px solid rgba(30,58,95,0.15); border-radius: 8px; font-size: 0.9375rem; outline: none; color: var(--ej-legal-text-light, #6B7280);">',
          '        <option value="">Area de interes...</option>',
          '        <option>Derecho Civil</option>',
          '        <option>Derecho Penal</option>',
          '        <option>Derecho Laboral</option>',
          '        <option>Derecho Mercantil</option>',
          '        <option>Derecho Tributario</option>',
          '        <option>Derecho Administrativo</option>',
          '        <option>Otro</option>',
          '      </select>',
          '      <textarea placeholder="Describa brevemente su consulta..." rows="4" style="padding: 12px 16px; border: 1px solid rgba(30,58,95,0.15); border-radius: 8px; font-size: 0.9375rem; outline: none; resize: vertical;"></textarea>',
          '      <button type="submit" style="padding: 14px; background: var(--ej-legal-primary, #1E3A5F); color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer;">Enviar Consulta</button>',
          '      <p style="text-align: center; font-size: 0.75rem; color: var(--ej-legal-text-light, #6B7280); margin: 0;">Respuesta garantizada en 24 horas laborables.</p>',
          '    </form>',
          '  </div>',
          '</section>',
        ].join('\n'),
      },

      // 11. LEGAL CTA
      {
        id: 'legal-cta',
        label: Drupal.t('CTA Final Legal'),
        media: icons.cta,
        content: [
          '<section class="jaraba-legal-cta" style="padding: 80px 24px; background: var(--ej-legal-primary, #1E3A5F); text-align: center; color: #fff;">',
          '  <div style="max-width: 700px; margin: 0 auto;">',
          '    <h2 style="font-size: 2.25rem; font-weight: 800; margin: 0 0 16px; line-height: 1.2;">Transforma tu practica juridica hoy</h2>',
          '    <p style="font-size: 1.125rem; opacity: 0.9; margin: 0 0 32px; line-height: 1.6;">Unete a cientos de despachos que ya usan inteligencia artificial para ser mas eficientes, precisos y competitivos.</p>',
          '    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">',
          '      <a href="#" style="display: inline-block; padding: 16px 40px; background: var(--ej-legal-accent, #C8A96E); color: #1E3A5F; font-weight: 700; border-radius: 8px; text-decoration: none; font-size: 1.0625rem;">Comenzar Ahora</a>',
          '      <a href="#" style="display: inline-block; padding: 16px 40px; border: 2px solid rgba(255,255,255,0.4); color: #fff; font-weight: 600; border-radius: 8px; text-decoration: none; font-size: 1.0625rem;">Hablar con Ventas</a>',
          '    </div>',
          '    <p style="font-size: 0.8125rem; opacity: 0.6; margin-top: 20px;">Plan Free disponible. Sin tarjeta de credito.</p>',
          '  </div>',
          '</section>',
        ].join('\n'),
      },

    ];

    // =========================================================================
    // Register all blocks
    // =========================================================================

    legalBlocks.forEach(function (block) {
      blockManager.add(block.id, {
        label: block.label,
        category: 'legal',
        content: block.content,
        media: block.media,
        attributes: { class: 'gjs-block-legal' },
      });
    });
  }

  // Register plugin in GrapesJS.
  if (typeof grapesjs !== 'undefined') {
    grapesjs.plugins.add('jaraba-legal-blocks', jarabaLegalBlocksPlugin);
  }

})(typeof grapesjs !== 'undefined' ? grapesjs : {}, Drupal);
