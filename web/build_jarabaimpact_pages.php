<?php
/**
 * @file
 * Build all 9 pages for pepejarabaimpact.com meta-site.
 * Creates/updates PageContent entities with full canvas_data (HTML + CSS).
 *
 * Execute: drush scr web/build_jarabaimpact_pages.php
 * DELETE after use.
 */

$TENANT_ID = 6; // Group ID for Jaraba Impact

echo "=== BUILD PAGES: PEPEJARABAIMPACT.COM ===\n\n";

// ─────────────────────────────────────────────────────
// SHARED CSS SYSTEM (ji- prefix)
// ─────────────────────────────────────────────────────
$shared_css = <<<'CSS'
/* === JARABA IMPACT DESIGN SYSTEM (ji-) === */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap');

:root {
  --ji-primary: #1B4F72;
  --ji-secondary: #17A589;
  --ji-accent: #E67E22;
  --ji-dark: #0E2F4A;
  --ji-text: #2D3748;
  --ji-muted: #718096;
  --ji-light: #F0F9FF;
  --ji-surface: #FFFFFF;
  --ji-border: #E2E8F0;
  --ji-success: #38A169;
  --ji-radius: 12px;
  --ji-radius-lg: 16px;
  --ji-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
  --ji-shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
  --ji-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

.ji-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.ji-text-center { text-align: center; }

/* SECTIONS */
.ji-section { padding: 80px 0; }
.ji-section--light { background: var(--ji-light); }
.ji-section--dark { background: var(--ji-dark); color: #fff; }
.ji-section__title {
  font-family: 'Montserrat', sans-serif;
  font-size: 2.25rem;
  font-weight: 700;
  color: var(--ji-primary);
  margin-bottom: 16px;
  line-height: 1.2;
}
.ji-section--dark .ji-section__title { color: #fff; }
.ji-section__subtitle {
  font-family: 'Inter', sans-serif;
  font-size: 1.125rem;
  color: var(--ji-muted);
  max-width: 700px;
  margin: 0 auto 48px;
  line-height: 1.7;
}
.ji-section--dark .ji-section__subtitle { color: rgba(255,255,255,0.8); }
.ji-section__label {
  font-family: 'Montserrat', sans-serif;
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--ji-secondary);
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-bottom: 8px;
}

/* HERO */
.ji-hero {
  background: linear-gradient(135deg, #1B4F72 0%, #0E2F4A 50%, #17A589 100%);
  min-height: 70vh;
  display: flex;
  align-items: center;
  position: relative;
  overflow: hidden;
  color: #fff;
  padding: 100px 0 80px;
}
.ji-hero::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: radial-gradient(circle at 20% 80%, rgba(23,165,137,0.15) 0%, transparent 50%),
              radial-gradient(circle at 80% 20%, rgba(230,126,34,0.1) 0%, transparent 50%);
  pointer-events: none;
}
.ji-hero__content { position: relative; z-index: 2; max-width: 800px; }
.ji-hero__title {
  font-family: 'Montserrat', sans-serif;
  font-size: 3rem;
  font-weight: 800;
  line-height: 1.15;
  margin-bottom: 24px;
  color: #fff;
}
.ji-hero__subtitle {
  font-family: 'Inter', sans-serif;
  font-size: 1.25rem;
  line-height: 1.7;
  color: rgba(255,255,255,0.9);
  margin-bottom: 40px;
  max-width: 650px;
}
.ji-hero__actions { display: flex; gap: 16px; flex-wrap: wrap; }

/* BUTTONS */
.ji-btn {
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  font-size: 1rem;
  padding: 14px 32px;
  border-radius: var(--ji-radius);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: var(--ji-transition);
  cursor: pointer;
  border: 2px solid transparent;
}
.ji-btn--primary {
  background: var(--ji-accent);
  color: #fff;
  border-color: var(--ji-accent);
}
.ji-btn--primary:hover { background: #D35400; border-color: #D35400; transform: translateY(-2px); box-shadow: var(--ji-shadow-lg); }
.ji-btn--outline {
  background: transparent;
  color: #fff;
  border-color: rgba(255,255,255,0.5);
}
.ji-btn--outline:hover { border-color: #fff; background: rgba(255,255,255,0.1); }
.ji-btn--secondary {
  background: var(--ji-secondary);
  color: #fff;
  border-color: var(--ji-secondary);
}
.ji-btn--secondary:hover { background: #138D75; transform: translateY(-2px); }
.ji-btn--lg { padding: 16px 40px; font-size: 1.125rem; }

/* GRID */
.ji-grid { display: grid; gap: 32px; }
.ji-grid--2 { grid-template-columns: repeat(2, 1fr); }
.ji-grid--3 { grid-template-columns: repeat(3, 1fr); }
.ji-grid--4 { grid-template-columns: repeat(4, 1fr); }
.ji-grid--5 { grid-template-columns: repeat(5, 1fr); }
@media (max-width: 1024px) {
  .ji-grid--5, .ji-grid--4 { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
  .ji-grid--2, .ji-grid--3, .ji-grid--4, .ji-grid--5 { grid-template-columns: 1fr; }
  .ji-hero__title { font-size: 2rem; }
  .ji-hero { min-height: 60vh; padding: 80px 0 60px; }
  .ji-section { padding: 48px 0; }
  .ji-section__title { font-size: 1.75rem; }
}

/* CARDS */
.ji-card {
  background: var(--ji-surface);
  border-radius: var(--ji-radius-lg);
  padding: 32px;
  box-shadow: var(--ji-shadow);
  transition: var(--ji-transition);
  border: 1px solid var(--ji-border);
}
.ji-card:hover { transform: translateY(-4px); box-shadow: var(--ji-shadow-lg); }
.ji-card__icon { margin-bottom: 16px; }
.ji-card__title {
  font-family: 'Montserrat', sans-serif;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--ji-primary);
  margin-bottom: 12px;
}
.ji-card__text {
  font-family: 'Inter', sans-serif;
  font-size: 0.9375rem;
  color: var(--ji-muted);
  line-height: 1.7;
}
.ji-card--accent { border-top: 4px solid var(--ji-accent); }
.ji-card--secondary { border-top: 4px solid var(--ji-secondary); }
.ji-card--primary { border-top: 4px solid var(--ji-primary); }

/* METRICS */
.ji-metric { text-align: center; padding: 32px 16px; }
.ji-metric__value {
  font-family: 'Montserrat', sans-serif;
  font-size: 3rem;
  font-weight: 800;
  color: var(--ji-accent);
  line-height: 1;
  margin-bottom: 8px;
}
.ji-section--dark .ji-metric__value { color: var(--ji-accent); }
.ji-metric__label {
  font-family: 'Inter', sans-serif;
  font-size: 0.9375rem;
  color: var(--ji-muted);
}
.ji-section--dark .ji-metric__label { color: rgba(255,255,255,0.7); }

/* BADGE */
.ji-badge {
  display: inline-block;
  padding: 6px 16px;
  border-radius: 20px;
  font-family: 'Montserrat', sans-serif;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
}
.ji-badge--accent { background: rgba(230,126,34,0.15); color: var(--ji-accent); }
.ji-badge--secondary { background: rgba(23,165,137,0.15); color: var(--ji-secondary); }
.ji-badge--primary { background: rgba(27,79,114,0.15); color: var(--ji-primary); }

/* CTA BLOCK */
.ji-cta-block {
  background: linear-gradient(135deg, #1B4F72 0%, #0E2F4A 100%);
  padding: 80px 0;
  text-align: center;
  color: #fff;
}
.ji-cta-block__title {
  font-family: 'Montserrat', sans-serif;
  font-size: 2.25rem;
  font-weight: 700;
  margin-bottom: 16px;
  color: #fff;
}
.ji-cta-block__text {
  font-family: 'Inter', sans-serif;
  font-size: 1.125rem;
  color: rgba(255,255,255,0.8);
  margin-bottom: 32px;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}
.ji-cta-block__actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }

/* CONTENT */
.ji-content { font-family: 'Inter', sans-serif; color: var(--ji-text); line-height: 1.8; }
.ji-content h2 { font-family: 'Montserrat', sans-serif; font-size: 1.75rem; font-weight: 700; color: var(--ji-primary); margin: 32px 0 16px; }
.ji-content h3 { font-family: 'Montserrat', sans-serif; font-size: 1.25rem; font-weight: 600; color: var(--ji-primary); margin: 24px 0 12px; }
.ji-content p { margin-bottom: 16px; }
.ji-content ul { margin: 16px 0; padding-left: 24px; }
.ji-content li { margin-bottom: 8px; }
.ji-content a { color: var(--ji-secondary); text-decoration: none; font-weight: 500; }
.ji-content a:hover { text-decoration: underline; }

/* CONTACT */
.ji-contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: start; }
.ji-contact-info { display: flex; flex-direction: column; gap: 24px; }
.ji-contact-info__item { display: flex; gap: 16px; align-items: flex-start; }
.ji-contact-info__icon { flex-shrink: 0; }
.ji-contact-info__label { font-family: 'Montserrat', sans-serif; font-weight: 600; color: var(--ji-primary); font-size: 0.875rem; margin-bottom: 4px; }
.ji-contact-info__value { font-family: 'Inter', sans-serif; color: var(--ji-text); font-size: 1rem; }
@media (max-width: 768px) { .ji-contact-grid { grid-template-columns: 1fr; } }

/* LEVEL CARDS (certification) */
.ji-level {
  position: relative;
  padding: 40px 32px;
  background: var(--ji-surface);
  border-radius: var(--ji-radius-lg);
  border: 2px solid var(--ji-border);
  box-shadow: var(--ji-shadow);
  transition: var(--ji-transition);
}
.ji-level:hover { transform: translateY(-4px); box-shadow: var(--ji-shadow-lg); }
.ji-level--1 { border-top: 4px solid var(--ji-secondary); }
.ji-level--2 { border-top: 4px solid var(--ji-primary); }
.ji-level--3 { border-top: 4px solid var(--ji-accent); }
.ji-level__number {
  font-family: 'Montserrat', sans-serif;
  font-size: 3rem;
  font-weight: 800;
  color: var(--ji-border);
  position: absolute;
  top: 16px;
  right: 24px;
  opacity: 0.5;
}
.ji-level__title {
  font-family: 'Montserrat', sans-serif;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--ji-primary);
  margin-bottom: 4px;
}
.ji-level__subtitle {
  font-family: 'Inter', sans-serif;
  font-size: 0.875rem;
  color: var(--ji-secondary);
  font-weight: 600;
  margin-bottom: 20px;
}
.ji-level__section-title {
  font-family: 'Montserrat', sans-serif;
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--ji-muted);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin: 20px 0 8px;
}
.ji-level__list { list-style: none; padding: 0; }
.ji-level__list li {
  font-family: 'Inter', sans-serif;
  font-size: 0.9375rem;
  color: var(--ji-text);
  padding: 6px 0;
  padding-left: 20px;
  position: relative;
}
.ji-level__list li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--ji-secondary);
}

/* STEPS */
.ji-step { display: flex; gap: 24px; align-items: flex-start; padding: 24px 0; border-bottom: 1px solid var(--ji-border); }
.ji-step:last-child { border-bottom: none; }
.ji-step__number {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--ji-primary);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Montserrat', sans-serif;
  font-weight: 700;
  font-size: 1.25rem;
}
.ji-step__title {
  font-family: 'Montserrat', sans-serif;
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--ji-primary);
  margin-bottom: 4px;
}
.ji-step__text {
  font-family: 'Inter', sans-serif;
  font-size: 0.9375rem;
  color: var(--ji-muted);
  line-height: 1.6;
}

/* PROGRAM CARD */
.ji-program {
  background: var(--ji-surface);
  border-radius: var(--ji-radius-lg);
  padding: 32px;
  border: 1px solid var(--ji-border);
  box-shadow: var(--ji-shadow);
  position: relative;
  overflow: hidden;
}
.ji-program__status {
  position: absolute;
  top: 16px;
  right: 16px;
  padding: 4px 12px;
  border-radius: 12px;
  font-family: 'Montserrat', sans-serif;
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
}
.ji-program__status--active { background: rgba(56,161,105,0.15); color: var(--ji-success); }
.ji-program__status--pilot { background: rgba(230,126,34,0.15); color: var(--ji-accent); }
.ji-program__status--planned { background: rgba(27,79,114,0.15); color: var(--ji-primary); }

/* LEGAL */
.ji-legal { font-family: 'Inter', sans-serif; color: var(--ji-text); line-height: 1.8; }
.ji-legal h1 { font-family: 'Montserrat', sans-serif; font-size: 2rem; font-weight: 700; color: var(--ji-primary); margin-bottom: 24px; }
.ji-legal h2 { font-family: 'Montserrat', sans-serif; font-size: 1.5rem; font-weight: 600; color: var(--ji-primary); margin: 32px 0 16px; }
.ji-legal p { margin-bottom: 16px; }
.ji-legal ul { margin: 16px 0; padding-left: 24px; }
.ji-legal li { margin-bottom: 8px; }

/* SOCIAL */
.ji-social-links { display: flex; gap: 16px; flex-wrap: wrap; }
.ji-social-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: var(--ji-radius);
  background: var(--ji-light);
  color: var(--ji-primary);
  text-decoration: none;
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  font-size: 0.875rem;
  transition: var(--ji-transition);
}
.ji-social-link:hover { background: var(--ji-primary); color: #fff; }
CSS;

// ─────────────────────────────────────────────────────
// SVG ICON HELPERS (hex colors, not currentColor)
// ─────────────────────────────────────────────────────

function ji_icon($name, $size = 48, $color = '#1B4F72') {
  $icons = [
    'institution' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-4h6v4"/><path d="M9 10h0.01"/><path d="M15 10h0.01"/></svg>',
    'rocket' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>',
    'certificate' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
    'chart-bar' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>',
    'team' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'handshake' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88"/><path d="m2 12 5.56-5.56a2 2 0 0 1 2.83 0L12 8"/><path d="m7 12 5.56 5.56a2 2 0 0 0 2.83 0L22 12"/></svg>',
    'mail' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
    'phone' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
    'map-pin' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    'target' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    'award' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>',
    'shield' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>',
    'briefcase' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>',
    'leaf' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 5.5-1 11.5c-.5 1.5-1 2.5-2 3.5l-3 1.5z"/><path d="M4 22l3-3"/></svg>',
    'shopping-bag' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
    'users' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'lightbulb' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><line x1="9" y1="18" x2="15" y2="18"/><line x1="10" y1="22" x2="14" y2="22"/></svg>',
  ];
  return $icons[$name] ?? '';
}

// ─────────────────────────────────────────────────────
// PAGE DEFINITIONS
// ─────────────────────────────────────────────────────

$pages = [];

// =====================================================
// PAGE 1: INICIO (Homepage)
// =====================================================
$pages[] = [
  'title' => 'Inicio',
  'path_alias' => '/inicio',
  'template_id' => 'rich_text',
  'meta_title' => 'Jaraba Impact | Infraestructura Digital para la Transformación',
  'meta_description' => 'Plataforma SaaS para transformación digital de empleo, emprendimiento y comercio local. +30 años experiencia, +100M€ gestionados.',
  'html' => '
<!-- HERO -->
<section class="ji-hero">
  <div class="ji-container">
    <div class="ji-hero__content">
      <div class="ji-section__label">Ecosistema Digital de Impacto</div>
      <h1 class="ji-hero__title">Infraestructura Digital para la Transformación que Importa</h1>
      <p class="ji-hero__subtitle">Plataforma SaaS multi-tenant que sincroniza empleo, emprendimiento y comercio local con tecnología de impacto. Programas avanzados y certificación oficial para consultores y entidades que lideran el cambio.</p>
      <div class="ji-hero__actions">
        <a href="/contacto" class="ji-btn ji-btn--primary ji-btn--lg">Solicita una Demo</a>
        <a href="/plataforma" class="ji-btn ji-btn--outline ji-btn--lg">Conoce la Plataforma</a>
      </div>
    </div>
  </div>
</section>

<!-- TRIPLE MOTOR -->
<section class="ji-section ji-text-center">
  <div class="ji-container">
    <div class="ji-section__label">Modelo de Negocio</div>
    <h2 class="ji-section__title">Triple Motor Económico</h2>
    <p class="ji-section__subtitle">Un modelo híbrido que sincroniza fondos públicos, productos SaaS y certificación profesional para generar impacto sostenible.</p>
    <div class="ji-grid ji-grid--3">
      <div class="ji-card ji-card--primary">
        <div class="ji-card__icon">' . ji_icon('institution', 48, '#1B4F72') . '</div>
        <h3 class="ji-card__title">Motor Institucional</h3>
        <p class="ji-card__text">Fondos públicos y programas subvencionados. Andalucía +ei, PIIL, NextGen EU. Colaboración directa con Junta de Andalucía, SEPE y universidades.</p>
      </div>
      <div class="ji-card ji-card--secondary">
        <div class="ji-card__icon">' . ji_icon('rocket', 48, '#17A589') . '</div>
        <h3 class="ji-card__title">Motor Privado</h3>
        <p class="ji-card__text">Productos digitales y servicios SaaS. Cinco verticales operativas que generan ingresos recurrentes: AgroConecta, ComercioConecta, ServiciosConecta y más.</p>
      </div>
      <div class="ji-card ji-card--accent">
        <div class="ji-card__icon">' . ji_icon('certificate', 48, '#E67E22') . '</div>
        <h3 class="ji-card__title">Motor de Licencias</h3>
        <p class="ji-card__text">Certificación del Método Jaraba para consultores. Franquicias territoriales, royalties y una red creciente de profesionales acreditados.</p>
      </div>
    </div>
  </div>
</section>

<!-- VERTICALES -->
<section class="ji-section ji-section--light ji-text-center">
  <div class="ji-container">
    <div class="ji-section__label">Verticales SaaS</div>
    <h2 class="ji-section__title">Soluciones para Cada Sector</h2>
    <p class="ji-section__subtitle">Cinco verticales especializadas que cubren el ecosistema completo de transformación digital territorial.</p>
    <div class="ji-grid ji-grid--5">
      <div class="ji-card">
        <div class="ji-card__icon">' . ji_icon('leaf', 40, '#17A589') . '</div>
        <h3 class="ji-card__title">AgroConecta</h3>
        <p class="ji-card__text">Del campo a tu mesa. Trazabilidad blockchain para productores locales.</p>
      </div>
      <div class="ji-card">
        <div class="ji-card__icon">' . ji_icon('shopping-bag', 40, '#1B4F72') . '</div>
        <h3 class="ji-card__title">ComercioConecta</h3>
        <p class="ji-card__text">Impulsa tu comercio local. QR dinámicos y ofertas flash geolocalizadas.</p>
      </div>
      <div class="ji-card">
        <div class="ji-card__icon">' . ji_icon('briefcase', 40, '#E67E22') . '</div>
        <h3 class="ji-card__title">ServiciosConecta</h3>
        <p class="ji-card__text">Profesionales de confianza. Agenda, firma digital y buzón seguro.</p>
      </div>
      <div class="ji-card">
        <div class="ji-card__icon">' . ji_icon('users', 40, '#17A589') . '</div>
        <h3 class="ji-card__title">Empleabilidad</h3>
        <p class="ji-card__text">Conectamos talento con oportunidades. LMS + Job Board integrado.</p>
      </div>
      <div class="ji-card">
        <div class="ji-card__icon">' . ji_icon('lightbulb', 40, '#1B4F72') . '</div>
        <h3 class="ji-card__title">Emprendimiento</h3>
        <p class="ji-card__text">De la idea al negocio. Diagnóstico, mentoring y financiación.</p>
      </div>
    </div>
  </div>
</section>

<!-- IMPACTO METRICS -->
<section class="ji-section ji-section--dark ji-text-center">
  <div class="ji-container">
    <div class="ji-section__label" style="color:#E67E22;">Impacto en Cifras</div>
    <h2 class="ji-section__title">Resultados que Hablan por Sí Solos</h2>
    <div class="ji-grid ji-grid--4" style="margin-top:48px;">
      <div class="ji-metric">
        <div class="ji-metric__value">+30</div>
        <div class="ji-metric__label">Años de experiencia en transformación digital</div>
      </div>
      <div class="ji-metric">
        <div class="ji-metric__value">+100M€</div>
        <div class="ji-metric__label">Fondos europeos gestionados</div>
      </div>
      <div class="ji-metric">
        <div class="ji-metric__value">+50.000</div>
        <div class="ji-metric__label">Beneficiarios formados y acompañados</div>
      </div>
      <div class="ji-metric">
        <div class="ji-metric__value">5</div>
        <div class="ji-metric__label">Verticales SaaS operativas</div>
      </div>
    </div>
  </div>
</section>

<!-- CASOS DE ÉXITO -->
<section class="ji-section ji-text-center">
  <div class="ji-container">
    <div class="ji-section__label">Historias Reales</div>
    <h2 class="ji-section__title">Historias de Transformación y Resultados</h2>
    <p class="ji-section__subtitle">Personas reales que han transformado su vida profesional con el ecosistema Jaraba.</p>
    <div class="ji-grid ji-grid--3">
      <div class="ji-card">
        <span class="ji-badge ji-badge--secondary">Emprendimiento</span>
        <h3 class="ji-card__title" style="margin-top:16px;">De la Incertidumbre a Crear mi Propio Camino</h3>
        <p class="ji-card__text">Marcela Calabia, escritora y coach de comunicación estratégica, lanzó su proyecto como autónoma sin experiencia previa gracias al programa Andalucía +ei.</p>
        <a href="/impacto" class="ji-btn ji-btn--secondary" style="margin-top:16px;">Leer su Historia</a>
      </div>
      <div class="ji-card">
        <span class="ji-badge ji-badge--accent">Pymes</span>
        <h3 class="ji-card__title" style="margin-top:16px;">Del Estrés Corporativo al Éxito Rural</h3>
        <p class="ji-card__text">Ángel Martínez dejó el mundo corporativo para crear Camino Viejo, experiencias comunitarias en la Sierra Norte de Sevilla.</p>
        <a href="/impacto" class="ji-btn ji-btn--secondary" style="margin-top:16px;">Leer su Historia</a>
      </div>
      <div class="ji-card">
        <span class="ji-badge ji-badge--primary">Empleabilidad</span>
        <h3 class="ji-card__title" style="margin-top:16px;">De la Parálisis Administrativa a la Acción</h3>
        <p class="ji-card__text">Luis Miguel Criado, terapeuta manual, superó la barrera administrativa para darse de alta como autónomo y obtener financiación pública.</p>
        <a href="/impacto" class="ji-btn ji-btn--secondary" style="margin-top:16px;">Leer su Historia</a>
      </div>
    </div>
  </div>
</section>

<!-- CTA FINAL -->
<section class="ji-cta-block">
  <div class="ji-container">
    <h2 class="ji-cta-block__title">Impulsa tu Próximo Gran Proyecto</h2>
    <p class="ji-cta-block__text">Ya seas consultor buscando certificación o una entidad que necesita soluciones de transformación digital, tenemos la infraestructura para ti.</p>
    <div class="ji-cta-block__actions">
      <a href="/certificacion" class="ji-btn ji-btn--primary ji-btn--lg">Certificación para Consultores</a>
      <a href="/contacto" class="ji-btn ji-btn--outline ji-btn--lg">Soluciones para Entidades</a>
    </div>
  </div>
</section>',
];

// =====================================================
// PAGE 2: CERTIFICACIÓN
// =====================================================
$pages[] = [
  'title' => 'Certificación de Consultores',
  'path_alias' => '/certificacion',
  'template_id' => 'rich_text',
  'meta_title' => 'Certificación Consultores | Jaraba Impact',
  'meta_description' => 'Programa oficial de certificación en 3 niveles para consultores de transformación digital. Metodología probada, red profesional exclusiva.',
  'html' => '
<!-- HERO -->
<section class="ji-hero" style="min-height:50vh;">
  <div class="ji-container">
    <div class="ji-hero__content">
      <span class="ji-badge ji-badge--accent" style="margin-bottom:16px;">Programa Oficial Jaraba Impact</span>
      <h1 class="ji-hero__title">Lidera la Transformación Digital con Impacto Real</h1>
      <p class="ji-hero__subtitle">Obtén el reconocimiento y la metodología para multiplicar el valor que aportas a tus clientes y acelerar tu carrera como consultor certificado.</p>
      <div class="ji-hero__actions">
        <a href="/contacto" class="ji-btn ji-btn--primary ji-btn--lg">Solicitar Información</a>
      </div>
    </div>
  </div>
</section>

<!-- PÚBLICO OBJETIVO -->
<section class="ji-section ji-text-center">
  <div class="ji-container">
    <h2 class="ji-section__title">¿Para Quién es Esta Certificación?</h2>
    <div class="ji-grid ji-grid--3" style="margin-top:40px;">
      <div class="ji-card ji-card--primary">
        <h3 class="ji-card__title">Consultores Independientes</h3>
        <p class="ji-card__text">Quieres estandarizar tu metodología, aumentar tu credibilidad y escalar tu negocio de consultoría con un sistema probado.</p>
      </div>
      <div class="ji-card ji-card--secondary">
        <h3 class="ji-card__title">Consultores Senior / Managers</h3>
        <p class="ji-card__text">Buscas liderar proyectos de transformación con mayor impacto y diferenciarte dentro de tu organización o en tu carrera.</p>
      </div>
      <div class="ji-card ji-card--accent">
        <h3 class="ji-card__title">Directivos y Líderes de Proyecto</h3>
        <p class="ji-card__text">Necesitas un marco estratégico para implementar cambios efectivos y gestionar la complejidad en tu equipo o empresa.</p>
      </div>
    </div>
  </div>
</section>

<!-- LA SENDA DEL CONSULTOR: 3 NIVELES -->
<section class="ji-section ji-section--light">
  <div class="ji-container ji-text-center">
    <div class="ji-section__label">Programa de Certificación</div>
    <h2 class="ji-section__title">La Senda del Consultor de Impacto</h2>
    <p class="ji-section__subtitle">Tres niveles progresivos que te llevan desde los fundamentos hasta el liderazgo en transformación de ecosistemas digitales.</p>
    <div class="ji-grid ji-grid--3" style="text-align:left;">
      <div class="ji-level ji-level--1">
        <div class="ji-level__number">1</div>
        <span class="ji-badge ji-badge--secondary">Nivel Asociado</span>
        <h3 class="ji-level__title" style="margin-top:12px;">Domina los Fundamentos</h3>
        <p class="ji-level__subtitle">En Ecosistemas Digitales</p>
        <div class="ji-level__section-title">Requisitos</div>
        <ul class="ji-level__list">
          <li>Completar curso formativo base</li>
          <li>Superar examen de conocimientos</li>
          <li>Adhesión al código ético del ecosistema</li>
        </ul>
        <div class="ji-level__section-title">Beneficios</div>
        <ul class="ji-level__list">
          <li>Certificación oficial de Nivel Asociado</li>
          <li>Perfil básico en el marketplace</li>
          <li>Acceso a comunidad de consultores</li>
        </ul>
      </div>
      <div class="ji-level ji-level--2">
        <div class="ji-level__number">2</div>
        <span class="ji-badge ji-badge--primary">Nivel Certificado</span>
        <h3 class="ji-level__title" style="margin-top:12px;">Aplica la Estrategia</h3>
        <p class="ji-level__subtitle">En Estrategia Digital</p>
        <div class="ji-level__section-title">Requisitos</div>
        <ul class="ji-level__list">
          <li>Certificación de Nivel Asociado</li>
          <li>Presentación de 1 caso de éxito auditado</li>
          <li>Formación avanzada en estrategia y gestión</li>
        </ul>
        <div class="ji-level__section-title">Beneficios</div>
        <ul class="ji-level__list">
          <li>Sello "Consultor Certificado"</li>
          <li>Perfil destacado y verificado en marketplace</li>
          <li>Acceso a leads pre-cualificados</li>
        </ul>
      </div>
      <div class="ji-level ji-level--3">
        <div class="ji-level__number">3</div>
        <span class="ji-badge ji-badge--accent">Nivel Master</span>
        <h3 class="ji-level__title" style="margin-top:12px;">Lidera la Transformación</h3>
        <p class="ji-level__subtitle">En Transformación de Ecosistemas</p>
        <div class="ji-level__section-title">Requisitos</div>
        <ul class="ji-level__list">
          <li>Certificación de Nivel Certificado</li>
          <li>Mínimo 3 años de experiencia demostrable</li>
          <li>Contribución: artículo, webinar o caso de estudio</li>
          <li>Entrevista con el comité directivo</li>
        </ul>
        <div class="ji-level__section-title">Beneficios</div>
        <ul class="ji-level__list">
          <li>Máximo reconocimiento en el ecosistema</li>
          <li>Participación en proyectos estratégicos</li>
          <li>Oportunidades de co-autoría y ponencias</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- BENEFICIOS -->
<section class="ji-section ji-text-center">
  <div class="ji-container">
    <h2 class="ji-section__title">¿Qué Obtendrás con la Certificación?</h2>
    <div class="ji-grid ji-grid--3" style="margin-top:40px;">
      <div class="ji-card">
        <div class="ji-card__icon">' . ji_icon('award', 40, '#E67E22') . '</div>
        <h3 class="ji-card__title">Reconocimiento Oficial</h3>
        <p class="ji-card__text">Conviértete en consultor certificado con el aval de Jaraba Impact y una metodología reconocida.</p>
      </div>
      <div class="ji-card">
        <div class="ji-card__icon">' . ji_icon('shield', 40, '#17A589') . '</div>
        <h3 class="ji-card__title">Metodología Probada</h3>
        <p class="ji-card__text">Aplica un framework estratégico validado con más de 30 años de resultados medibles.</p>
      </div>
      <div class="ji-card">
        <div class="ji-card__icon">' . ji_icon('target', 40, '#1B4F72') . '</div>
        <h3 class="ji-card__title">Diferenciación en el Mercado</h3>
        <p class="ji-card__text">Destaca de la competencia y atrae clientes de mayor valor con credenciales verificables.</p>
      </div>
      <div class="ji-card">
        <div class="ji-card__icon">' . ji_icon('team', 40, '#E67E22') . '</div>
        <h3 class="ji-card__title">Red de Contactos Exclusiva</h3>
        <p class="ji-card__text">Accede a una comunidad de profesionales de alto nivel en transformación digital.</p>
      </div>
      <div class="ji-card">
        <div class="ji-card__icon">' . ji_icon('chart-bar', 40, '#17A589') . '</div>
        <h3 class="ji-card__title">Mayor Rentabilidad</h3>
        <p class="ji-card__text">Posiciónate para proyectos de mayor envergadura y honorarios profesionales superiores.</p>
      </div>
      <div class="ji-card">
        <div class="ji-card__icon">' . ji_icon('handshake', 40, '#1B4F72') . '</div>
        <h3 class="ji-card__title">Confianza y Solidez</h3>
        <p class="ji-card__text">Presenta propuestas con el respaldo de una metodología y un ecosistema de éxito demostrado.</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="ji-cta-block">
  <div class="ji-container">
    <h2 class="ji-cta-block__title">¿Listo para Generar un Impacto Real?</h2>
    <p class="ji-cta-block__text">Conviértete en un consultor de referencia en el mercado digital. Solicita información sobre el programa de certificación.</p>
    <a href="/contacto" class="ji-btn ji-btn--primary ji-btn--lg">Solicitar Información</a>
  </div>
</section>',
];

// =====================================================
// PAGE 3: CONTACTO (update existing page 71)
// =====================================================
$pages[] = [
  'update_id' => 71,
  'title' => 'Contacto',
  'path_alias' => '/contacto-institucional',
  'template_id' => 'contact_form',
  'meta_title' => 'Contacto | Jaraba Impact',
  'meta_description' => 'Contacta con Jaraba Impact para certificación de consultores, soluciones institucionales o colaboraciones estratégicas.',
  'html' => '
<section class="ji-hero" style="min-height:40vh;">
  <div class="ji-container">
    <div class="ji-hero__content">
      <h1 class="ji-hero__title">Hablemos de tu Proyecto</h1>
      <p class="ji-hero__subtitle">Estamos aquí para ayudarte a impulsar la transformación digital de tu organización o tu carrera profesional.</p>
    </div>
  </div>
</section>

<section class="ji-section">
  <div class="ji-container">
    <div class="ji-contact-grid">
      <div class="ji-contact-info">
        <div class="ji-contact-info__item">
          <div class="ji-contact-info__icon">' . ji_icon('mail', 32, '#1B4F72') . '</div>
          <div>
            <div class="ji-contact-info__label">Contacto</div>
            <div class="ji-contact-info__value"><a href="https://wa.me/' . (theme_get_setting('whatsapp_number', 'ecosistema_jaraba_theme') ?: '') . '" target="_blank" rel="noopener">WhatsApp</a></div>
          </div>
        </div>
        <div class="ji-contact-info__item">
          <div class="ji-contact-info__icon">' . ji_icon('phone', 32, '#1B4F72') . '</div>
          <div>
            <div class="ji-contact-info__label">Formulario</div>
            <div class="ji-contact-info__value"><a href="/es/contacto">Contacto</a></div>
          </div>
        </div>
        <div class="ji-contact-info__item">
          <div class="ji-contact-info__icon">' . ji_icon('map-pin', 32, '#1B4F72') . '</div>
          <div>
            <div class="ji-contact-info__label">Sede Central</div>
            <div class="ji-contact-info__value">Sevilla, Andalucía, España</div>
          </div>
        </div>
        <div style="margin-top:16px;">
          <div class="ji-contact-info__label" style="margin-bottom:12px;">Síguenos</div>
          <div class="ji-social-links">
            <a href="https://www.linkedin.com/in/pepejaraba/" class="ji-social-link" target="_blank">LinkedIn</a>
            <a href="https://www.youtube.com/@PepeJaraba" class="ji-social-link" target="_blank">YouTube</a>
            <a href="https://wa.me/' . (theme_get_setting('whatsapp_number', 'ecosistema_jaraba_theme') ?: '') . '" class="ji-social-link" target="_blank">WhatsApp</a>
          </div>
        </div>
      </div>
      <div class="ji-card" style="padding:40px;">
        <h3 class="ji-card__title" style="margin-bottom:24px;">Formulario de Contacto</h3>
        <p class="ji-card__text" style="margin-bottom:24px;">Completa el formulario y nos pondremos en contacto contigo en menos de 24 horas laborables.</p>
        <p class="ji-card__text" style="font-style:italic; color: var(--ji-muted);">Para enviar tu consulta, usa nuestro <a href="/es/contacto" style="color:var(--ji-secondary); font-weight:600;">formulario de contacto</a> o escríbenos por <a href="https://wa.me/' . (theme_get_setting('whatsapp_number', 'ecosistema_jaraba_theme') ?: '') . '" style="color:#25d366; font-weight:600;" target="_blank">WhatsApp</a>.</p>
      </div>
    </div>
  </div>
</section>',
];

// =====================================================
// PAGE 4: AVISO LEGAL
// =====================================================
$pages[] = [
  'title' => 'Aviso Legal',
  'path_alias' => '/aviso-legal-ji',
  'template_id' => 'rich_text',
  'meta_title' => 'Aviso Legal | Jaraba Impact',
  'meta_description' => 'Aviso legal y condiciones de uso de Jaraba Impact, propiedad de Plataforma de Ecosistemas Digitales S.L.',
  'html' => '
<section class="ji-section">
  <div class="ji-container">
    <div class="ji-legal">
      <h1>Aviso Legal</h1>
      <h2>1. Datos Identificativos</h2>
      <p>En cumplimiento del artículo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Información y del Comercio Electrónico, se informa que el titular de este sitio web es:</p>
      <ul>
        <li><strong>Razón Social:</strong> Plataforma de Ecosistemas Digitales S.L.</li>
        <li><strong>Domicilio Social:</strong> Sevilla, Andalucía, España</li>
        <li><strong>Email:</strong> contacto@jarabaimpact.com</li>
        <li><strong>Sitio Web:</strong> pepejarabaimpact.com</li>
      </ul>
      <h2>2. Objeto</h2>
      <p>El presente sitio web tiene como objeto facilitar información sobre los servicios de consultoría, certificación profesional y transformación digital que ofrece Jaraba Impact, marca comercial de Plataforma de Ecosistemas Digitales S.L.</p>
      <h2>3. Propiedad Intelectual</h2>
      <p>Todos los contenidos del sitio web, incluyendo textos, imágenes, logotipos, iconos, software y demás material, están protegidos por las leyes de propiedad intelectual e industrial. Queda prohibida su reproducción, distribución o transformación sin autorización expresa.</p>
      <h2>4. Responsabilidad</h2>
      <p>Jaraba Impact no se hace responsable de los daños o perjuicios que pudieran derivarse del acceso o uso de este sitio web. Nos reservamos el derecho de modificar, suspender o eliminar cualquier contenido o servicio sin previo aviso.</p>
      <h2>5. Legislación Aplicable</h2>
      <p>El presente aviso legal se rige por la legislación española. Para cualquier controversia que pudiera derivarse del acceso o uso de este sitio web, las partes se someten a la jurisdicción de los Juzgados y Tribunales de Sevilla.</p>
    </div>
  </div>
</section>',
];

// =====================================================
// PAGE 5: POLÍTICA DE PRIVACIDAD
// =====================================================
$pages[] = [
  'title' => 'Política de Privacidad',
  'path_alias' => '/privacidad-ji',
  'template_id' => 'rich_text',
  'meta_title' => 'Política de Privacidad | Jaraba Impact',
  'meta_description' => 'Política de privacidad y protección de datos de Jaraba Impact conforme al RGPD.',
  'html' => '
<section class="ji-section">
  <div class="ji-container">
    <div class="ji-legal">
      <h1>Política de Privacidad</h1>
      <p><strong>Última actualización:</strong> Febrero 2026</p>
      <h2>1. Responsable del Tratamiento</h2>
      <p>Plataforma de Ecosistemas Digitales S.L. es responsable del tratamiento de los datos personales recogidos a través de este sitio web, en cumplimiento del Reglamento General de Protección de Datos (RGPD) y la Ley Orgánica 3/2018 de Protección de Datos Personales.</p>
      <h2>2. Datos Recogidos</h2>
      <p>Recogemos únicamente los datos que nos facilitas voluntariamente a través de formularios de contacto: nombre, email, organización y mensaje. No recogemos datos sensibles ni realizamos perfilado automatizado.</p>
      <h2>3. Finalidad</h2>
      <p>Los datos se utilizan exclusivamente para responder a tus consultas, gestionar solicitudes de información sobre programas de certificación y soluciones institucionales, y mantener comunicaciones comerciales si has dado tu consentimiento expreso.</p>
      <h2>4. Derechos</h2>
      <p>Puedes ejercer tus derechos de acceso, rectificación, supresión, portabilidad, limitación y oposición escribiendo a <strong>contacto@jarabaimpact.com</strong>.</p>
      <h2>5. Conservación</h2>
      <p>Los datos se conservan durante el tiempo necesario para cumplir con la finalidad para la que fueron recogidos y para determinar posibles responsabilidades derivadas de dicha finalidad.</p>
    </div>
  </div>
</section>',
];

// =====================================================
// PAGE 6: POLÍTICA DE COOKIES
// =====================================================
$pages[] = [
  'title' => 'Política de Cookies',
  'path_alias' => '/cookies-ji',
  'template_id' => 'rich_text',
  'meta_title' => 'Política de Cookies | Jaraba Impact',
  'meta_description' => 'Información sobre el uso de cookies en el sitio web de Jaraba Impact.',
  'html' => '
<section class="ji-section">
  <div class="ji-container">
    <div class="ji-legal">
      <h1>Política de Cookies</h1>
      <p><strong>Última actualización:</strong> Febrero 2026</p>
      <h2>1. ¿Qué son las Cookies?</h2>
      <p>Las cookies son pequeños archivos de texto que se almacenan en tu dispositivo cuando visitas un sitio web. Permiten que el sitio recuerde tus preferencias y mejore tu experiencia de navegación.</p>
      <h2>2. Cookies Utilizadas</h2>
      <p>Este sitio web utiliza exclusivamente cookies técnicas necesarias para el funcionamiento del sitio. No utilizamos cookies de seguimiento publicitario ni compartimos datos con terceros a través de cookies.</p>
      <h2>3. Gestión de Cookies</h2>
      <p>Puedes configurar tu navegador para rechazar cookies o para que te avise cuando se envíen. Ten en cuenta que si desactivas las cookies, algunas funcionalidades del sitio podrían verse afectadas.</p>
      <h2>4. Contacto</h2>
      <p>Para cualquier consulta relacionada con nuestra política de cookies, contacta con nosotros en <strong>contacto@jarabaimpact.com</strong>.</p>
    </div>
  </div>
</section>',
];

// =====================================================
// UPDATE: PAGE 66 PLATAFORMA (overwrite content)
// =====================================================
$pages[] = [
  'update_id' => 66,
  'title' => 'Plataforma',
  'path_alias' => '/plataforma',
  'template_id' => 'icon_cards',
  'meta_title' => 'Plataforma Jaraba Impact | Triple Motor Económico',
  'meta_description' => 'Modelo híbrido SaaS que sincroniza fondos públicos, productos digitales y certificación profesional para impacto territorial real.',
  'html' => '
<section class="ji-hero" style="min-height:50vh;">
  <div class="ji-container">
    <div class="ji-hero__content">
      <div class="ji-section__label">La Plataforma</div>
      <h1 class="ji-hero__title">El Modelo que Conecta Ecosistemas</h1>
      <p class="ji-hero__subtitle">Infraestructura SaaS multi-tenant diseñada para escalar el impacto social y económico en territorios rurales y urbanos.</p>
    </div>
  </div>
</section>

<section class="ji-section ji-text-center">
  <div class="ji-container">
    <h2 class="ji-section__title">Triple Motor Económico</h2>
    <p class="ji-section__subtitle">Tres fuentes de ingresos complementarias que garantizan sostenibilidad financiera mientras maximizan el impacto social.</p>
    <div class="ji-grid ji-grid--3">
      <div class="ji-card ji-card--primary" style="text-align:left;">
        <div class="ji-card__icon">' . ji_icon('institution', 48, '#1B4F72') . '</div>
        <h3 class="ji-card__title">Motor Institucional</h3>
        <p class="ji-card__text"><strong>Fondos públicos y programas subvencionados.</strong> Colaboración directa con la Junta de Andalucía, SEPE, universidades y entidades locales. Programas como Andalucía +ei, PIIL y NextGen EU financian la adopción masiva de la plataforma.</p>
      </div>
      <div class="ji-card ji-card--secondary" style="text-align:left;">
        <div class="ji-card__icon">' . ji_icon('rocket', 48, '#17A589') . '</div>
        <h3 class="ji-card__title">Motor Privado</h3>
        <p class="ji-card__text"><strong>Productos digitales y servicios SaaS.</strong> Cinco verticales especializadas (AgroConecta, ComercioConecta, ServiciosConecta, Empleabilidad, Emprendimiento) que generan ingresos recurrentes por suscripción y transacción.</p>
      </div>
      <div class="ji-card ji-card--accent" style="text-align:left;">
        <div class="ji-card__icon">' . ji_icon('certificate', 48, '#E67E22') . '</div>
        <h3 class="ji-card__title">Motor de Licencias</h3>
        <p class="ji-card__text"><strong>Certificación del Método Jaraba.</strong> Red de consultores certificados en 3 niveles, franquicias territoriales con royalties, y un marketplace de expertos verificados que multiplica el alcance del ecosistema.</p>
      </div>
    </div>
  </div>
</section>

<section class="ji-section ji-section--light">
  <div class="ji-container ji-text-center">
    <h2 class="ji-section__title">Arquitectura Tecnológica</h2>
    <p class="ji-section__subtitle">Construida sobre estándares enterprise con foco en escalabilidad, seguridad y rendimiento.</p>
    <div class="ji-grid ji-grid--3">
      <div class="ji-card">
        <h3 class="ji-card__title">Drupal 11 Multi-Tenant</h3>
        <p class="ji-card__text">CMS enterprise con arquitectura multi-tenant basada en Group Module. Cada tenant opera en aislamiento completo con su propio dominio, datos y configuración.</p>
      </div>
      <div class="ji-card">
        <h3 class="ji-card__title">AI-Native</h3>
        <p class="ji-card__text">Inteligencia artificial integrada en el core: generación de contenido, matching inteligente de empleo, diagnóstico empresarial automatizado y asistentes conversacionales.</p>
      </div>
      <div class="ji-card">
        <h3 class="ji-card__title">API-First + SSR</h3>
        <p class="ji-card__text">APIs RESTful para integraciones, Server-Side Rendering para SEO/GEO óptimo, y webhooks para automatizaciones con terceros.</p>
      </div>
    </div>
  </div>
</section>

<section class="ji-cta-block">
  <div class="ji-container">
    <h2 class="ji-cta-block__title">¿Quieres Ver la Plataforma en Acción?</h2>
    <p class="ji-cta-block__text">Agenda una demo personalizada y descubre cómo la infraestructura Jaraba puede transformar tu territorio.</p>
    <a href="/contacto-institucional" class="ji-btn ji-btn--primary ji-btn--lg">Solicita una Demo</a>
  </div>
</section>',
];

// =====================================================
// UPDATE: PAGE 68 IMPACTO (overwrite content)
// =====================================================
$pages[] = [
  'update_id' => 68,
  'title' => 'Impacto',
  'path_alias' => '/impacto',
  'template_id' => 'stats_counter',
  'meta_title' => 'Impacto | +100M€ Gestionados en Transformación Digital',
  'meta_description' => 'Métricas verificables, casos de éxito reales y certificaciones. Descubre los resultados del ecosistema Jaraba.',
  'html' => '
<section class="ji-hero" style="min-height:50vh;">
  <div class="ji-container">
    <div class="ji-hero__content">
      <div class="ji-section__label">Nuestro Impacto</div>
      <h1 class="ji-hero__title">Resultados Medibles, Historias Reales</h1>
      <p class="ji-hero__subtitle">Más de tres décadas construyendo ecosistemas digitales que transforman vidas y territorios. Estos son nuestros números.</p>
    </div>
  </div>
</section>

<section class="ji-section ji-text-center">
  <div class="ji-container">
    <div class="ji-grid ji-grid--4">
      <div class="ji-metric"><div class="ji-metric__value">+30</div><div class="ji-metric__label">Años liderando transformación digital</div></div>
      <div class="ji-metric"><div class="ji-metric__value">+100M€</div><div class="ji-metric__label">Fondos europeos gestionados</div></div>
      <div class="ji-metric"><div class="ji-metric__value">+50.000</div><div class="ji-metric__label">Beneficiarios directos</div></div>
      <div class="ji-metric"><div class="ji-metric__value">98%</div><div class="ji-metric__label">Tasa de éxito en proyectos</div></div>
    </div>
  </div>
</section>

<section class="ji-section ji-section--light">
  <div class="ji-container ji-text-center">
    <h2 class="ji-section__title">Casos de Éxito</h2>
    <p class="ji-section__subtitle">Personas y organizaciones reales que han transformado su realidad con el apoyo del ecosistema Jaraba.</p>
    <div class="ji-grid ji-grid--3" style="text-align:left;">
      <div class="ji-card">
        <span class="ji-badge ji-badge--secondary">Emprendimiento</span>
        <h3 class="ji-card__title" style="margin-top:12px;">Marcela Calabia</h3>
        <p class="ji-card__text"><strong>El reto:</strong> Reinventarse profesionalmente sin experiencia como autónoma.</p>
        <p class="ji-card__text"><strong>La solución:</strong> Programa Andalucía +ei con el Método Jaraba "sin humo".</p>
        <p class="ji-card__text"><strong>El resultado:</strong> Creó su propia web, dominó el marketing digital y lanzó su actividad como coach de comunicación estratégica.</p>
      </div>
      <div class="ji-card">
        <span class="ji-badge ji-badge--accent">Pymes</span>
        <h3 class="ji-card__title" style="margin-top:12px;">Ángel Martínez — Camino Viejo</h3>
        <p class="ji-card__text"><strong>El reto:</strong> Salir del estrés corporativo y emprender en el mundo rural.</p>
        <p class="ji-card__text"><strong>La solución:</strong> Lean finance y acompañamiento para minimizar inversión inicial.</p>
        <p class="ji-card__text"><strong>El resultado:</strong> Creó Camino Viejo, experiencias comunitarias en la Sierra Norte de Sevilla, con un modelo sostenible.</p>
      </div>
      <div class="ji-card">
        <span class="ji-badge ji-badge--primary">Empleabilidad</span>
        <h3 class="ji-card__title" style="margin-top:12px;">Luis Miguel Criado</h3>
        <p class="ji-card__text"><strong>El reto:</strong> Parálisis administrativa como barrera principal para emprender.</p>
        <p class="ji-card__text"><strong>La solución:</strong> Desmitificación de trámites y apoyo paso a paso.</p>
        <p class="ji-card__text"><strong>El resultado:</strong> Se dio de alta como autónomo, obtuvo financiación de la Junta y gestiona sus cuotas de forma independiente.</p>
      </div>
    </div>
  </div>
</section>

<section class="ji-cta-block">
  <div class="ji-container">
    <h2 class="ji-cta-block__title">Únete al Ecosistema de Impacto</h2>
    <p class="ji-cta-block__text">Forma parte de una comunidad que genera resultados reales para personas y territorios.</p>
    <a href="/contacto-institucional" class="ji-btn ji-btn--primary ji-btn--lg">Contactar</a>
  </div>
</section>',
];

// =====================================================
// UPDATE: PAGE 69 PROGRAMAS (overwrite content)
// =====================================================
$pages[] = [
  'update_id' => 69,
  'title' => 'Programas Institucionales',
  'path_alias' => '/programas',
  'template_id' => 'cards_grid',
  'meta_title' => 'Programas Institucionales | Jaraba Impact',
  'meta_description' => 'Andalucía +ei, Kit Digital y más. Programas de transformación digital para entidades públicas y privadas con fondos europeos.',
  'html' => '
<section class="ji-hero" style="min-height:50vh;">
  <div class="ji-container">
    <div class="ji-hero__content">
      <div class="ji-section__label">Programas</div>
      <h1 class="ji-hero__title">Programas Institucionales de Impacto</h1>
      <p class="ji-hero__subtitle">Colaboramos con instituciones públicas y privadas para implementar programas de transformación digital que generan resultados medibles.</p>
    </div>
  </div>
</section>

<section class="ji-section">
  <div class="ji-container">
    <div class="ji-grid ji-grid--2">
      <div class="ji-program">
        <div class="ji-program__status ji-program__status--active">Activo</div>
        <div class="ji-card__icon" style="margin-bottom:16px;">' . ji_icon('team', 40, '#17A589') . '</div>
        <h3 class="ji-card__title">Andalucía +ei</h3>
        <p class="ji-card__text">Programa de orientación profesional e inserción laboral para personas desempleadas en Andalucía. Incluye itinerarios personalizados de empleo, emprendimiento y competencias digitales.</p>
        <p class="ji-card__text" style="margin-top:12px;"><strong>Colaboradores:</strong> Junta de Andalucía, SAE, SEPE</p>
      </div>
      <div class="ji-program">
        <div class="ji-program__status ji-program__status--active">Activo</div>
        <div class="ji-card__icon" style="margin-bottom:16px;">' . ji_icon('rocket', 40, '#E67E22') . '</div>
        <h3 class="ji-card__title">Kit Digital</h3>
        <p class="ji-card__text">Digitalización acelerada de PYMEs con fondos NextGen EU. Implementación de soluciones digitales integrales: web, e-commerce, gestión, facturación electrónica y presencia en internet.</p>
        <p class="ji-card__text" style="margin-top:12px;"><strong>Financiación:</strong> Fondos Next Generation EU</p>
      </div>
      <div class="ji-program">
        <div class="ji-program__status ji-program__status--planned">En preparación</div>
        <div class="ji-card__icon" style="margin-bottom:16px;">' . ji_icon('briefcase', 40, '#1B4F72') . '</div>
        <h3 class="ji-card__title">PIIL — Proyectos Integrales de Inserción Laboral</h3>
        <p class="ji-card__text">Programa integral que combina formación, orientación y acompañamiento para la inserción laboral de colectivos vulnerables en zonas rurales con alta desestacionalización.</p>
      </div>
      <div class="ji-program">
        <div class="ji-program__status ji-program__status--pilot">Piloto</div>
        <div class="ji-card__icon" style="margin-bottom:16px;">' . ji_icon('lightbulb', 40, '#17A589') . '</div>
        <h3 class="ji-card__title">Autodigitales</h3>
        <p class="ji-card__text">Formación digital práctica para autónomos rurales. Programa piloto que combina sesiones presenciales y online para dotar de competencias digitales avanzadas a profesionales independientes.</p>
      </div>
    </div>
  </div>
</section>

<section class="ji-cta-block">
  <div class="ji-container">
    <h2 class="ji-cta-block__title">¿Tu Institución Busca Colaborar?</h2>
    <p class="ji-cta-block__text">Diseñamos programas a medida para entidades públicas, universidades, cámaras de comercio y organizaciones del tercer sector.</p>
    <a href="/contacto-institucional" class="ji-btn ji-btn--primary ji-btn--lg">Contactar para Colaborar</a>
  </div>
</section>',
];

// ─────────────────────────────────────────────────────
// CREATE/UPDATE PAGES
// ─────────────────────────────────────────────────────

$page_storage = \Drupal::entityTypeManager()->getStorage('page_content');
$created_pages = [];

foreach ($pages as $i => $def) {
  $page_num = $i + 1;

  if (!empty($def['update_id'])) {
    // Update existing page
    $page = $page_storage->load($def['update_id']);
    if (!$page) {
      echo "ERROR: Page {$def['update_id']} not found!\n";
      continue;
    }
    echo "[UPDATE] Page {$def['update_id']}: {$def['title']}\n";
  } else {
    // Create new page
    $page = $page_storage->create([
      'template_id' => $def['template_id'],
      'tenant_id' => ['target_id' => $TENANT_ID],
      'uid' => 1,
    ]);
    echo "[CREATE] Page: {$def['title']}\n";
  }

  $page->set('title', $def['title']);
  $page->set('path_alias', $def['path_alias']);
  $page->set('meta_title', $def['meta_title']);
  $page->set('meta_description', $def['meta_description']);
  $page->set('status', TRUE);

  // Build canvas_data
  $canvas_data = json_encode([
    'components' => [],
    'styles' => [],
    'html' => trim($def['html']),
    'css' => $shared_css,
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

  $page->set('canvas_data', $canvas_data);
  $page->set('rendered_html', trim($def['html']));

  // Ensure tenant is set
  if (empty($page->get('tenant_id')->target_id)) {
    $page->set('tenant_id', ['target_id' => $TENANT_ID]);
  }

  $page->save();

  $pid = $page->id();
  $created_pages[$def['path_alias']] = $pid;
  echo "  → Saved as page {$pid} | path={$def['path_alias']}\n";
}

echo "\n=== PAGES SUMMARY ===\n";
foreach ($created_pages as $path => $pid) {
  echo "  Page {$pid}: {$path}\n";
}

echo "\n=== BUILD COMPLETE ===\n";
echo "Created/updated " . count($pages) . " pages for tenant {$TENANT_ID}\n";
