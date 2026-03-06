/**
 * @file
 * Visual Customizer JS for Tenant Theme Form.
 *
 * Live preview via CSS custom properties + interactive site mockup modal
 * with real-time form synchronization, hover effects, entrance animations,
 * device switching and color palette bar.
 */

(function (Drupal, once) {
  'use strict';

  var colorMap = {
    color_primary: '--ej-color-primary',
    color_secondary: '--ej-color-secondary',
    color_accent: '--ej-color-accent',
    color_success: '--ej-color-success',
    color_warning: '--ej-color-warning',
    color_error: '--ej-color-error',
    color_bg_body: '--ej-bg-body',
    color_bg_surface: '--ej-bg-surface',
    color_text: '--ej-color-body'
  };

  var typographyMap = {
    font_headings: '--ej-font-headings',
    font_body: '--ej-font-body'
  };

  var sizeMap = {
    card_border_radius: '--ej-border-radius',
    button_border_radius: '--ej-btn-radius'
  };

  // Currently open modal reference for live-update.
  var activeModal = null;
  var activeForm = null;

  Drupal.behaviors.visualCustomizer = {
    attach: function (context) {
      once('visual-customizer-init', '.jaraba-theme-customizer', context).forEach(function (form) {
        // Live preview on color input change.
        form.querySelectorAll('input[type="color"]').forEach(function (input) {
          input.addEventListener('input', function () {
            var cssVar = colorMap[input.name];
            if (cssVar) {
              document.documentElement.style.setProperty(cssVar, input.value);
            }
            // Live-update open modal.
            if (activeModal) {
              refreshMockup();
            }
          });
        });

        // Live-update on ANY form field change.
        form.addEventListener('change', function () {
          if (activeModal) {
            refreshMockup();
          }
        });
        form.addEventListener('input', function (e) {
          if (activeModal && e.target.type !== 'color') {
            refreshMockup();
          }
        });

        // Preview button opens modal.
        var previewBtn = form.querySelector('[data-action="preview"]');
        if (previewBtn) {
          previewBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openPreviewModal(form);
          });
        }
      });
    }
  };

  // ─────────────────────────────────────────────────────────────────────────
  // HELPERS
  // ─────────────────────────────────────────────────────────────────────────

  function val(form, name, fallback) {
    var el = form.querySelector('[name="' + name + '"]');
    if (!el) { return fallback || ''; }
    if (el.type === 'checkbox') { return el.checked; }
    return el.value || fallback || '';
  }

  function radioVal(form, name, fallback) {
    var checked = form.querySelector('input[name="' + name + '"]:checked');
    return checked ? checked.value : (fallback || '');
  }

  function esc(str) {
    return Drupal.checkPlain(str);
  }

  // ─────────────────────────────────────────────────────────────────────────
  // MOCKUP BUILDER — Generates interactive site mockup HTML
  // ─────────────────────────────────────────────────────────────────────────

  function collectValues(form) {
    var primary = val(form, 'color_primary', '#FF8C42');
    var secondary = val(form, 'color_secondary', '#00A9A5');
    var accent = val(form, 'color_accent', '#233D63');
    return {
      primary: primary,
      secondary: secondary,
      accent: accent,
      bgBody: val(form, 'color_bg_body', '#F8FAFC'),
      bgSurface: val(form, 'color_bg_surface', '#FFFFFF'),
      textColor: val(form, 'color_text', '#1A1A2E'),
      fontHeadings: val(form, 'font_headings', 'Outfit'),
      fontBody: val(form, 'font_body', 'Inter'),
      cardRadius: val(form, 'card_border_radius', '12'),
      btnRadius: val(form, 'button_border_radius', '8'),
      ctaText: val(form, 'header_cta_text', 'Empezar'),
      ctaEnabled: val(form, 'header_cta_enabled', true),
      siteName: val(form, 'site_name', '') ||
        (document.querySelector('.site-branding__name a, .header__logo-text') || {}).textContent ||
        'Mi Sitio',
      siteSlogan: val(form, 'site_slogan', ''),
      headerVariant: radioVal(form, 'header_variant', 'classic'),
      heroVariant: radioVal(form, 'hero_variant', 'split'),
      cardStyle: radioVal(form, 'card_style', 'elevated'),
      btnStyle: radioVal(form, 'button_style', 'solid'),
      footerVariant: radioVal(form, 'footer_variant', 'standard'),
      footerCopyright: val(form, 'footer_copyright', '\u00a9 2026 Mi Empresa'),
      success: val(form, 'color_success', '#10B981'),
      warning: val(form, 'color_warning', '#F59E0B'),
      error: val(form, 'color_error', '#EF4444')
    };
  }

  function buildMockupHtml(v) {
    var textMuted = v.textColor + 'AA';
    var year = new Date().getFullYear();

    // Card shadow.
    var cardShadow = '0 1px 3px rgba(0,0,0,0.08)';
    if (v.cardStyle === 'elevated') { cardShadow = '0 4px 16px rgba(0,0,0,0.10)'; }
    else if (v.cardStyle === 'bordered') { cardShadow = 'none'; }
    var cardBorder = v.cardStyle === 'bordered' ? '1px solid ' + v.textColor + '15' : 'none';

    // Button styles.
    var btnBg = v.primary, btnColor = '#fff', btnBorder = 'none';
    if (v.btnStyle === 'outline') {
      btnBg = 'transparent'; btnColor = v.primary; btnBorder = '2px solid ' + v.primary;
    } else if (v.btnStyle === 'soft') {
      btnBg = v.primary + '18'; btnColor = v.primary;
    }

    // Header direction.
    var hDir = 'row', hJust = 'space-between';
    if (v.headerVariant === 'centered') { hDir = 'column'; hJust = 'center'; }

    // Hero direction.
    var heroDir = v.heroVariant === 'split' ? 'row' : 'column';
    var heroAlign = v.heroVariant === 'split' ? 'left' : 'center';

    return '' +
      // ── Scoped styles for hover/animations ──
      '<style>' +
        '.pm-nav-link{position:relative;cursor:pointer;transition:color .2s}' +
        '.pm-nav-link::after{content:"";position:absolute;bottom:-2px;left:0;width:0;height:2px;' +
          'background:' + v.primary + ';transition:width .25s ease}' +
        '.pm-nav-link:hover{color:' + v.primary + ' !important}' +
        '.pm-nav-link:hover::after{width:100%}' +
        '.pm-card{transition:transform .25s ease,box-shadow .25s ease}' +
        '.pm-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,0.14) !important}' +
        '.pm-btn-interactive{transition:transform .15s ease,filter .15s ease,box-shadow .15s ease}' +
        '.pm-btn-interactive:hover{transform:translateY(-1px);filter:brightness(1.1);' +
          'box-shadow:0 4px 12px ' + v.primary + '40}' +
        '.pm-social-dot{transition:transform .2s ease,background .2s ease}' +
        '.pm-social-dot:hover{transform:scale(1.3);background:rgba(255,255,255,0.5) !important}' +
        '.pm-section{opacity:0;transform:translateY(16px);animation:pmReveal .5s ease forwards}' +
        '.pm-section:nth-child(1){animation-delay:.05s}' +
        '.pm-section:nth-child(2){animation-delay:.15s}' +
        '.pm-section:nth-child(3){animation-delay:.3s}' +
        '.pm-section:nth-child(4){animation-delay:.45s}' +
        '.pm-section:nth-child(5){animation-delay:.55s}' +
        '@keyframes pmReveal{to{opacity:1;transform:translateY(0)}}' +
        '.pm-hero{animation:pmReveal .5s ease .3s forwards,pmGradient 8s ease .8s infinite !important}' +
        '@keyframes pmGradient{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}' +
        '@keyframes pmPulse{0%,100%{opacity:1}50%{opacity:.7}}' +
        '.pm-site-scroll::-webkit-scrollbar{width:6px}' +
        '.pm-site-scroll::-webkit-scrollbar-track{background:transparent}' +
        '.pm-site-scroll::-webkit-scrollbar-thumb{background:' + v.primary + '40;border-radius:3px}' +
      '</style>' +

      // ── COLOR PALETTE BAR ──
      '<div class="pm-section" style="' +
        'display:flex;align-items:center;gap:0.375rem;padding:0.5rem 0.75rem;' +
        'background:' + v.bgSurface + ';border-bottom:1px solid ' + v.textColor + '08;' +
        'flex-wrap:wrap;' +
      '">' +
        '<span style="font-size:0.625rem;font-weight:600;color:' + textMuted + ';margin-right:0.125rem;">' +
          'Paleta' +
        '</span>' +
        buildSwatch(v.primary, 'Primario') +
        buildSwatch(v.secondary, 'Secundario') +
        buildSwatch(v.accent, 'Acento') +
        buildSwatch(v.bgBody, 'Fondo') +
        buildSwatch(v.bgSurface, 'Superficie') +
        buildSwatch(v.textColor, 'Texto') +
        '<span style="margin-left:auto;font-size:0.5625rem;color:' + textMuted + ';">' +
          esc(v.fontHeadings) + ' / ' + esc(v.fontBody) +
        '</span>' +
      '</div>' +

      // ── HEADER ──
      '<div class="pm-section" style="' +
        'display:flex;flex-direction:' + hDir + ';align-items:center;' +
        'justify-content:' + hJust + ';gap:0.5rem;' +
        'padding:0.5rem 0.75rem;background:' + v.bgSurface + ';' +
        'border-bottom:1px solid ' + v.textColor + '10;flex-wrap:wrap;' +
      '">' +
        '<div style="display:flex;align-items:center;gap:0.375rem;">' +
          '<div style="width:22px;height:22px;border-radius:5px;background:' + v.primary + ';' +
            'animation:pmPulse 3s ease-in-out infinite;flex-shrink:0;"></div>' +
          '<span style="font-family:\'' + v.fontHeadings + '\',sans-serif;font-weight:700;font-size:0.8125rem;">' +
            esc(v.siteName.trim()) +
          '</span>' +
        '</div>' +
        '<nav style="display:flex;align-items:center;gap:0.75rem;font-size:0.6875rem;color:' + textMuted + ';flex-wrap:wrap;">' +
          '<span class="pm-nav-link">Inicio</span>' +
          '<span class="pm-nav-link">Servicios</span>' +
          '<span class="pm-nav-link">Blog</span>' +
          '<span class="pm-nav-link">Contacto</span>' +
          (v.ctaEnabled ?
            '<a class="pm-btn-interactive" style="' +
              'display:inline-block;background:' + btnBg + ';color:' + btnColor + ';border:' + btnBorder + ';' +
              'padding:0.375rem 0.875rem;border-radius:' + v.btnRadius + 'px;font-size:0.6875rem;' +
              'font-weight:600;text-decoration:none;white-space:nowrap;cursor:pointer;' +
            '">' + esc(v.ctaText) + ' \u2192</a>' : '') +
        '</nav>' +
      '</div>' +

      // ── HERO ──
      // NOTE: Do NOT set animation in inline style — it overrides the .pm-section
      // pmReveal animation (opacity:0→1), making the hero permanently invisible.
      // The gradient animation is applied via .pm-hero class in the <style> block.
      '<div class="pm-section pm-hero" style="' +
        'display:flex;flex-direction:' + heroDir + ';text-align:' + heroAlign + ';' +
        'padding:1.5rem 1rem;gap:1rem;color:#fff;' +
        'background-color:' + v.accent + ';' +
        'background-image:linear-gradient(135deg,' + v.accent + ' 0%,' + v.primary + ' 50%,' + v.secondary + ' 100%);' +
        'background-size:200% 200%;' +
        'min-height:140px;' +
      '">' +
        '<div style="flex:1;max-width:' + (v.heroVariant === 'split' ? '60%' : '100%') + ';">' +
          '<h1 style="font-family:\'' + v.fontHeadings + '\',sans-serif;font-size:1.25rem;font-weight:800;' +
            'margin:0 0 0.5rem;line-height:1.2;">' +
            esc(v.siteSlogan || 'Tu titulo principal va aqui') +
          '</h1>' +
          '<p style="font-size:0.75rem;opacity:0.85;margin:0 0 0.75rem;line-height:1.4;">' +
            'Una descripcion atractiva de tu proyecto o negocio que conecta con tu audiencia.' +
          '</p>' +
          '<a class="pm-btn-interactive" style="' +
            'display:inline-block;background:' + v.primary + ';color:#fff;cursor:pointer;' +
            'padding:0.5rem 1rem;border-radius:' + v.btnRadius + 'px;font-size:0.75rem;' +
            'font-weight:600;text-decoration:none;' +
          '">' + esc(v.ctaText || 'Empezar') + ' \u2192</a>' +
        '</div>' +
        (v.heroVariant === 'split' ?
          '<div style="flex:1;display:flex;align-items:center;justify-content:center;">' +
            '<div style="width:100%;max-width:240px;height:150px;border-radius:' + v.cardRadius + 'px;' +
              'background:rgba(255,255,255,0.10);border:1px dashed rgba(255,255,255,0.25);' +
              'display:flex;align-items:center;justify-content:center;font-size:0.75rem;opacity:0.5;">' +
              'Imagen hero' +
            '</div>' +
          '</div>' : '') +
      '</div>' +

      // ── CONTENT CARDS ──
      '<div class="pm-section" style="padding:1rem 0.75rem;">' +
        '<h2 style="font-family:\'' + v.fontHeadings + '\',sans-serif;font-size:0.9375rem;font-weight:700;' +
          'margin:0 0 0.75rem;text-align:center;">' +
          'Nuestros Servicios' +
        '</h2>' +
        '<div class="pm-cards-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;">' +
          buildCard('Servicio Premium', 'Descripcion breve del servicio que ofreces a tus clientes.', v.primary, v, cardShadow, cardBorder, textMuted) +
          buildCard('Consultoria', 'Asesoramiento personalizado para alcanzar tus objetivos.', v.secondary, v, cardShadow, cardBorder, textMuted) +
          buildCard('Formacion', 'Cursos y talleres para impulsar tu crecimiento profesional.', v.primary, v, cardShadow, cardBorder, textMuted) +
        '</div>' +
      '</div>' +

      // ── TESTIMONIAL STRIP ──
      '<div class="pm-section" style="padding:0.75rem;text-align:center;background:' + v.primary + '08;">' +
        '<p style="font-family:\'' + v.fontHeadings + '\',sans-serif;font-style:italic;font-size:0.75rem;' +
          'color:' + v.textColor + ';margin:0 0 0.375rem;line-height:1.4;">' +
          '\u201CUna experiencia que ha transformado nuestro negocio.\u201D' +
        '</p>' +
        '<div style="display:flex;align-items:center;justify-content:center;gap:0.375rem;">' +
          '<div style="width:18px;height:18px;border-radius:50%;background:' + v.secondary + '30;"></div>' +
          '<span style="font-size:0.625rem;color:' + textMuted + ';">Cliente Satisfecho</span>' +
          '<span style="color:' + v.warning + ';font-size:0.625rem;">\u2605\u2605\u2605\u2605\u2605</span>' +
        '</div>' +
      '</div>' +

      // ── FOOTER ──
      '<div class="pm-section" style="' +
        'padding:0.75rem;background:' + v.accent + ';color:rgba(255,255,255,0.75);font-size:0.625rem;' +
      '">' +
        '<div style="display:flex;justify-content:space-between;align-items:center;gap:0.5rem;flex-wrap:wrap;">' +
          '<span>' + esc(v.footerCopyright.replace(/\[year\]|\{year\}/g, year)) + '</span>' +
          '<div style="display:flex;gap:0.375rem;">' +
            '<span class="pm-social-dot" style="width:16px;height:16px;border-radius:50%;background:rgba(255,255,255,0.2);display:inline-block;cursor:pointer;"></span>' +
            '<span class="pm-social-dot" style="width:16px;height:16px;border-radius:50%;background:rgba(255,255,255,0.2);display:inline-block;cursor:pointer;"></span>' +
            '<span class="pm-social-dot" style="width:16px;height:16px;border-radius:50%;background:rgba(255,255,255,0.2);display:inline-block;cursor:pointer;"></span>' +
            '<span class="pm-social-dot" style="width:16px;height:16px;border-radius:50%;background:rgba(255,255,255,0.2);display:inline-block;cursor:pointer;"></span>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  function buildSwatch(color, label) {
    return '<div title="' + label + ': ' + color + '" style="' +
      'width:18px;height:18px;border-radius:50%;background:' + color + ';' +
      'border:2px solid rgba(0,0,0,0.08);cursor:help;' +
      'transition:transform .2s ease;' +
    '" onmouseover="this.style.transform=\'scale(1.3)\'" onmouseout="this.style.transform=\'scale(1)\'"></div>';
  }

  function buildCard(title, desc, iconColor, v, shadow, border, textMuted) {
    return '<div class="pm-card" style="' +
      'background:' + v.bgSurface + ';border-radius:' + v.cardRadius + 'px;' +
      'padding:0.75rem;box-shadow:' + shadow + ';border:' + border + ';cursor:pointer;' +
      'display:flex;flex-direction:column;gap:0.375rem;overflow:hidden;' +
    '">' +
      '<div style="width:26px;height:26px;border-radius:6px;background:' + iconColor + '18;' +
        'display:flex;align-items:center;justify-content:center;transition:background .3s;flex-shrink:0;">' +
        '<div style="width:11px;height:11px;border-radius:3px;background:' + iconColor + ';"></div>' +
      '</div>' +
      '<h3 style="font-family:\'' + v.fontHeadings + '\',sans-serif;font-size:0.6875rem;font-weight:700;margin:0;color:' + v.textColor + ';">' +
        esc(title) +
      '</h3>' +
      '<p style="font-size:0.625rem;color:' + textMuted + ';margin:0;line-height:1.3;">' +
        esc(desc) +
      '</p>' +
    '</div>';
  }

  // ─────────────────────────────────────────────────────────────────────────
  // SLIDE PANEL — Side panel with live refresh
  // ─────────────────────────────────────────────────────────────────────────

  var mockupContainer = null;
  var mockupWrapper = null;
  var paletteUpdateTimer = null;

  function refreshMockup() {
    if (!activeForm || !mockupContainer) { return; }
    clearTimeout(paletteUpdateTimer);
    paletteUpdateTimer = setTimeout(function () {
      var v = collectValues(activeForm);
      mockupContainer.innerHTML = buildMockupHtml(v);
      // Update device button accent color.
      var panel = document.getElementById('theme-preview-panel');
      if (panel) {
        panel.querySelectorAll('.pm-device-btn').forEach(function (btn) {
          if (btn.classList.contains('is-active')) {
            btn.style.background = v.primary;
            btn.style.borderColor = v.primary;
          }
        });
      }
    }, 80);
  }

  function openPreviewModal(form) {
    var existing = document.getElementById('theme-preview-panel');
    if (existing) {
      // Toggle: close if already open.
      closePanel();
      return;
    }

    activeForm = form;
    var v = collectValues(form);

    // ── Slide panel shell ──
    var panel = document.createElement('div');
    panel.id = 'theme-preview-panel';
    panel.setAttribute('role', 'complementary');
    panel.setAttribute('aria-label', Drupal.t('Vista previa del sitio'));
    activeModal = panel;

    // Inject animations.
    var styleTag = document.createElement('style');
    styleTag.textContent =
      '@keyframes pmSlideInRight{from{transform:translateX(100%)}to{transform:translateX(0)}}' +
      '@keyframes pmSlideOutRight{from{transform:translateX(0)}to{transform:translateX(100%)}}';
    panel.appendChild(styleTag);

    // Panel — fixed right side.
    panel.style.cssText = 'position:fixed;top:0;right:0;bottom:0;width:480px;max-width:45vw;' +
      'z-index:1050;background:#fff;display:flex;flex-direction:column;' +
      'box-shadow:-8px 0 32px rgba(0,0,0,0.15);animation:pmSlideInRight .3s ease;';

    // ── Panel header ──
    var panelHeader = document.createElement('div');
    panelHeader.style.cssText = 'display:flex;align-items:center;justify-content:space-between;' +
      'padding:0.75rem 1rem;border-bottom:1px solid #eee;flex-shrink:0;gap:0.5rem;';

    var titleWrap = document.createElement('div');
    titleWrap.style.cssText = 'display:flex;align-items:center;gap:0.5rem;min-width:0;';

    var titleDot = document.createElement('div');
    titleDot.style.cssText = 'width:8px;height:8px;border-radius:50%;background:' + v.primary +
      ';flex-shrink:0;';

    var titleEl = document.createElement('span');
    titleEl.style.cssText = 'font-weight:700;font-size:0.8125rem;color:#1A1A2E;white-space:nowrap;';
    titleEl.textContent = Drupal.t('Vista Previa');

    var liveBadge = document.createElement('span');
    liveBadge.style.cssText = 'font-size:0.5625rem;background:#10B981;color:#fff;' +
      'padding:0.125rem 0.375rem;border-radius:999px;font-weight:600;letter-spacing:0.5px;' +
      'animation:pmPulse 2s ease-in-out infinite;flex-shrink:0;';
    liveBadge.textContent = 'LIVE';

    titleWrap.appendChild(titleDot);
    titleWrap.appendChild(titleEl);
    titleWrap.appendChild(liveBadge);

    // Device buttons.
    var deviceBar = document.createElement('div');
    deviceBar.style.cssText = 'display:flex;gap:0.25rem;align-items:center;';

    var devices = [
      { label: 'Desktop', width: '100%', icon: '\u25A1' },
      { label: 'Tablet', width: '420px', icon: '\u25AB' },
      { label: 'Mobile', width: '320px', icon: '\u25AA' }
    ];

    devices.forEach(function (device, i) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'pm-device-btn';
      btn.textContent = device.icon;
      btn.title = device.label;
      btn.style.cssText = 'padding:0.25rem 0.5rem;border-radius:6px;border:1px solid #ddd;' +
        'background:#f5f5f5;font-size:0.75rem;cursor:pointer;font-weight:500;color:#888;' +
        'transition:all .2s ease;line-height:1;';
      if (i === 0) {
        btn.classList.add('is-active');
        btn.style.background = v.primary;
        btn.style.color = '#fff';
        btn.style.borderColor = v.primary;
      }
      btn.addEventListener('click', function () {
        if (mockupWrapper) {
          mockupWrapper.style.maxWidth = device.width;
        }
        deviceBar.querySelectorAll('.pm-device-btn').forEach(function (b) {
          b.classList.remove('is-active');
          b.style.background = '#f5f5f5';
          b.style.color = '#888';
          b.style.borderColor = '#ddd';
        });
        btn.classList.add('is-active');
        btn.style.background = v.primary;
        btn.style.color = '#fff';
        btn.style.borderColor = v.primary;
      });
      deviceBar.appendChild(btn);
    });

    // Close button.
    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.textContent = '\u00D7';
    closeBtn.setAttribute('aria-label', Drupal.t('Cerrar vista previa'));
    closeBtn.style.cssText = 'width:28px;height:28px;border-radius:6px;border:none;background:#f0f0f0;' +
      'font-size:1.125rem;cursor:pointer;display:flex;align-items:center;justify-content:center;' +
      'color:#666;transition:all .2s;flex-shrink:0;';
    closeBtn.addEventListener('mouseenter', function () {
      closeBtn.style.background = '#e0e0e0'; closeBtn.style.color = '#333';
    });
    closeBtn.addEventListener('mouseleave', function () {
      closeBtn.style.background = '#f0f0f0'; closeBtn.style.color = '#666';
    });

    panelHeader.appendChild(titleWrap);
    panelHeader.appendChild(deviceBar);
    panelHeader.appendChild(closeBtn);

    // ── Mockup area ──
    mockupWrapper = document.createElement('div');
    mockupWrapper.style.cssText = 'padding:0.625rem;background:#e4e4e7;' +
      'transition:max-width .4s cubic-bezier(.4,0,.2,1);max-width:100%;margin:0 auto;' +
      'flex:1;overflow-y:auto;overflow-x:hidden;';

    mockupContainer = document.createElement('div');
    mockupContainer.className = 'pm-site-scroll';
    mockupContainer.style.cssText = 'border-radius:10px;overflow:hidden;' +
      'box-shadow:0 4px 20px rgba(0,0,0,0.12);' +
      'font-family:\'' + v.fontBody + '\',sans-serif;color:' + v.textColor + ';' +
      'background:' + v.bgBody + ';';
    mockupContainer.innerHTML = buildMockupHtml(v);
    mockupWrapper.appendChild(mockupContainer);

    // ── Footer hint ──
    var hint = document.createElement('div');
    hint.style.cssText = 'padding:0.5rem 1rem;text-align:center;font-size:0.625rem;' +
      'color:#999;border-top:1px solid #eee;flex-shrink:0;';
    hint.textContent = Drupal.t('Los cambios del formulario se reflejan aqui en tiempo real.');

    // Assemble.
    panel.appendChild(panelHeader);
    panel.appendChild(mockupWrapper);
    panel.appendChild(hint);
    document.body.appendChild(panel);

    // ── Close handlers ──
    closeBtn.addEventListener('click', closePanel);
    document.addEventListener('keydown', onPanelEsc);

    // Update preview button text to indicate it can toggle.
    var previewBtn = form.querySelector('[data-action="preview"]');
    if (previewBtn) {
      previewBtn.value = Drupal.t('Cerrar Vista Previa');
    }

    closeBtn.focus();
  }

  function onPanelEsc(e) {
    if (e.key === 'Escape') { closePanel(); }
  }

  function closePanel() {
    var panel = document.getElementById('theme-preview-panel');
    if (panel) {
      panel.style.animation = 'pmSlideOutRight .25s ease forwards';
      setTimeout(function () { panel.remove(); }, 250);
    }
    // Restore preview button text.
    if (activeForm) {
      var previewBtn = activeForm.querySelector('[data-action="preview"]');
      if (previewBtn) {
        previewBtn.value = Drupal.t('Vista Previa del Sitio');
      }
    }
    activeModal = null;
    activeForm = null;
    mockupContainer = null;
    mockupWrapper = null;
    document.removeEventListener('keydown', onPanelEsc);
  }

  // ─────────────────────────────────────────────────────────────────────────
  // PRESET PICKER — Filter pills + card selection.
  // ─────────────────────────────────────────────────────────────────────────

  Drupal.behaviors.presetPicker = {
    attach: function (context) {
      once('preset-filter-init', '.preset-filter-bar', context).forEach(function (bar) {
        var pills = bar.querySelectorAll('.preset-filter-pill');
        pills.forEach(function (pill) {
          pill.addEventListener('click', function () {
            var vertical = pill.getAttribute('data-vertical');
            pills.forEach(function (p) { p.classList.remove('is-active'); });
            pill.classList.add('is-active');
            var cards = document.querySelectorAll('.preset-picker-card');
            cards.forEach(function (card) {
              var wrapper = card.closest('.form-type-radio') || card.parentElement;
              if (vertical === 'todos' || card.getAttribute('data-vertical') === vertical) {
                wrapper.style.display = '';
                card.removeAttribute('data-hidden');
              } else {
                wrapper.style.display = 'none';
                card.setAttribute('data-hidden', 'true');
              }
            });
          });
        });
      });

      once('preset-card-init', '.preset-picker-card', context).forEach(function (card) {
        card.addEventListener('click', function () {
          var wrapper = card.closest('.form-type-radio');
          if (wrapper) {
            var radio = wrapper.querySelector('input[type="radio"]');
            if (radio) {
              radio.checked = true;
              radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
          }
          document.querySelectorAll('.preset-picker-card').forEach(function (c) {
            c.classList.remove('is-selected');
          });
          card.classList.add('is-selected');
        });
      });

      once('preset-selected-init', '.jaraba-preset-picker', context).forEach(function (container) {
        var checked = container.querySelector('input[type="radio"]:checked');
        if (checked) {
          var wrapper = checked.closest('.form-type-radio');
          if (wrapper) {
            var card = wrapper.querySelector('.preset-picker-card');
            if (card) {
              card.classList.add('is-selected');
            }
          }
        }
      });
    }
  };

})(Drupal, once);
