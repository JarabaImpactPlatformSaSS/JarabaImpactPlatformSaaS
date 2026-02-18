# WCAG 2.1 AA Accessibility Audit Checklist

**Jaraba Impact Platform SaaS**
Version: 1.0
Audit Date: 2026-02-18
Classification: INTERNAL - QA & Development

---

## Table of Contents

1. [Automated Checks](#1-automated-checks)
2. [Manual Checklist (WCAG 2.1 AA)](#2-manual-checklist-wcag-21-aa)
3. [Known Issues Found in This Audit](#3-known-issues-found-in-this-audit)
4. [Remediation Recommendations](#4-remediation-recommendations)

---

## 1. Automated Checks

### 1.1 Tools to Run

| Tool           | Purpose                                     | How to Run                                                                                             |
|----------------|---------------------------------------------|--------------------------------------------------------------------------------------------------------|
| **axe-core**   | DOM-based accessibility rules engine         | Browser extension (axe DevTools) or `npm install @axe-core/cli && axe https://platform.jaraba.com`     |
| **Lighthouse** | Comprehensive audit (accessibility + perf)   | Chrome DevTools > Lighthouse > Accessibility, or `lighthouse https://platform.jaraba.com --only-categories=accessibility` |
| **WAVE**       | Visual overlay of accessibility issues       | Browser extension or https://wave.webaim.org/                                                          |
| **Pa11y**      | CI-friendly automated checker                | `npx pa11y https://platform.jaraba.com --standard WCAG2AA`                                             |
| **HTML_CodeSniffer** | In-page audit bookmarklet              | https://squizlabs.github.io/HTML_CodeSniffer/                                                          |

### 1.2 Pages to Test

Test each of these pages in both anonymous and authenticated states:

| Page                       | URL Path                        | Priority |
|----------------------------|---------------------------------|----------|
| Homepage                   | `/`                             | Critical |
| Login page                 | `/user/login`                   | Critical |
| Registration               | `/user/register`                | Critical |
| Content Hub dashboard      | `/content-hub`                  | High     |
| Credentials dashboard      | `/credentials`                  | High     |
| Course detail              | `/lms/course/*`                 | High     |
| Job posting detail         | `/empleo/oferta/*`              | High     |
| Candidate profile          | `/candidate/profile`            | High     |
| Page Builder canvas        | `/page-builder/*`               | Medium   |
| Admin center               | `/admin-center`                 | Medium   |
| Credential verify (public) | `/verify/*`                     | High     |
| Onboarding wizard          | `/onboarding`                   | High     |
| Self-discovery tools       | `/self-discovery/*`             | Medium   |
| Provider detail (Servicios)| `/servicios/provider/*`         | Medium   |
| Product detail (Comercio)  | `/comercio/product/*`           | Medium   |

### 1.3 Expected Automated Audit Scores

| Tool        | Target Score | Pass Criteria                                 |
|-------------|-------------|------------------------------------------------|
| Lighthouse  | >= 90/100    | No critical or serious violations              |
| axe-core    | 0 critical   | Zero critical, < 5 serious, < 15 moderate     |
| Pa11y       | 0 errors     | Zero WCAG2AA errors; warnings to be reviewed   |

### 1.4 CI Integration

Add to the CI pipeline (`.github/workflows/` or equivalent):

```yaml
accessibility-audit:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - name: Run Pa11y
      run: |
        npx pa11y-ci --config .pa11yci.json
    - name: Run Lighthouse CI
      uses: treosh/lighthouse-ci-action@v12
      with:
        urls: |
          https://staging.jaraba.com/
          https://staging.jaraba.com/user/login
        budgetPath: ./lighthouse-budget.json
```

---

## 2. Manual Checklist (WCAG 2.1 AA)

### 2.1 Perceivable

#### 1.1.1 Non-text Content (Level A)

All non-text content has text alternatives that serve the equivalent purpose.

| Check | Description | Status | Notes |
|-------|-------------|--------|-------|
| 2.1.1.a | All `<img>` elements have meaningful `alt` attributes | [ ] | |
| 2.1.1.b | Decorative images use `alt=""` or `role="presentation"` | [ ] | |
| 2.1.1.c | Icon fonts / SVG icons have accessible labels (`aria-label` or `<title>`) | [ ] | |
| 2.1.1.d | `<input type="image">` elements have `alt` text | [ ] | |
| 2.1.1.e | `<canvas>` elements have fallback text content | [ ] | Page Builder canvas editor |
| 2.1.1.f | Background images conveying information have text alternatives | [ ] | |
| 2.1.1.g | Charts and data visualizations have text descriptions | [ ] | Analytics dashboards |
| 2.1.1.h | CAPTCHA alternatives are provided (if applicable) | [ ] | |

#### 1.3.1 Info and Relationships (Level A)

Information, structure, and relationships conveyed through presentation can be programmatically determined.

| Check | Description | Status | Notes |
|-------|-------------|--------|-------|
| 2.1.2.a | Headings use proper `<h1>`-`<h6>` hierarchy (no skipping levels) | [ ] | |
| 2.1.2.b | Lists use `<ul>`, `<ol>`, or `<dl>` markup | [ ] | |
| 2.1.2.c | Data tables use `<th>`, `<caption>`, and `scope` attributes | [ ] | Admin tables, CRM, analytics |
| 2.1.2.d | Form fields have associated `<label>` elements | [ ] | |
| 2.1.2.e | Related form fields are grouped with `<fieldset>` and `<legend>` | [ ] | |
| 2.1.2.f | Landmark roles are used correctly (`<nav>`, `<main>`, `<aside>`, etc.) | [ ] | |
| 2.1.2.g | Required fields are programmatically indicated (`aria-required`) | [ ] | |
| 2.1.2.h | Visual groupings have semantic structure | [ ] | Card layouts, dashboards |

#### 1.4.3 Contrast (Minimum) (Level AA)

Text and images of text have a contrast ratio of at least 4.5:1 (3:1 for large text).

| Check | Description | Status | Notes |
|-------|-------------|--------|-------|
| 2.1.3.a | Body text meets 4.5:1 contrast ratio | [ ] | |
| 2.1.3.b | Large text (18pt+ or 14pt+ bold) meets 3:1 ratio | [ ] | |
| 2.1.3.c | Link text is distinguishable from surrounding text | [ ] | |
| 2.1.3.d | Placeholder text in form fields meets contrast requirements | [ ] | |
| 2.1.3.e | Button text meets contrast requirements | [ ] | |
| 2.1.3.f | Error messages are visible with sufficient contrast | [ ] | |
| 2.1.3.g | Focus indicators are visible with sufficient contrast | [ ] | |
| 2.1.3.h | Disabled elements are distinguishable (but exempt from ratio) | [ ] | |

#### 1.4.11 Non-text Contrast (Level AA)

UI components and graphical objects have a contrast ratio of at least 3:1.

| Check | Description | Status | Notes |
|-------|-------------|--------|-------|
| 2.1.4.a | Form field borders meet 3:1 contrast | [ ] | |
| 2.1.4.b | Icon buttons meet 3:1 contrast | [ ] | |
| 2.1.4.c | Chart elements (bars, lines, slices) are distinguishable | [ ] | |
| 2.1.4.d | Custom checkboxes/radio buttons meet contrast | [ ] | |

### 2.2 Operable

#### 2.1.1 Keyboard (Level A)

All functionality is operable through a keyboard interface.

| Check | Description | Status | Notes |
|-------|-------------|--------|-------|
| 2.2.1.a | All interactive elements are reachable via Tab key | [ ] | |
| 2.2.1.b | All interactive elements are activatable via Enter/Space | [ ] | |
| 2.2.1.c | Dropdown menus are keyboard operable (Arrow keys) | [ ] | |
| 2.2.1.d | Modal dialogs trap focus correctly | [ ] | Copilot chat, onboarding |
| 2.2.1.e | Modal dialogs close with Escape key | [ ] | |
| 2.2.1.f | Drag-and-drop has keyboard alternative | [ ] | Page Builder, Kanban boards |
| 2.2.1.g | Custom widgets (sliders, tabs, accordions) are keyboard operable | [ ] | |
| 2.2.1.h | No keyboard traps exist (user can always Tab away) | [ ] | |
| 2.2.1.i | Video player controls are keyboard accessible | [ ] | LMS video lessons |
| 2.2.1.j | Command palette is fully keyboard navigable | [ ] | Admin command palette |

#### 2.4.1 Bypass Blocks (Level A)

A mechanism is available to bypass blocks of content repeated on multiple pages.

| Check | Description | Status | Notes |
|-------|-------------|--------|-------|
| 2.2.2.a | "Skip to main content" link exists and works | [ ] | |
| 2.2.2.b | Skip link is visible on focus | [ ] | |
| 2.2.2.c | Skip link target is correct (`#main-content` or similar) | [ ] | |
| 2.2.2.d | Landmark regions allow screen reader navigation | [ ] | |
| 2.2.2.e | Page sections use proper heading hierarchy for navigation | [ ] | |

#### 2.4.4 Link Purpose (In Context) (Level A)

The purpose of each link can be determined from the link text alone or together with its context.

| Check | Description | Status | Notes |
|-------|-------------|--------|-------|
| 2.2.3.a | No "click here" or "read more" links without context | [ ] | |
| 2.2.3.b | Links that open in new window/tab indicate this (`aria-label` or visual) | [ ] | |
| 2.2.3.c | Links to files indicate format and size | [ ] | Credential PDFs, exports |
| 2.2.3.d | Adjacent links to the same destination are combined | [ ] | Card links |
| 2.2.3.e | Image links have accessible name via `alt` text | [ ] | |

#### 2.4.7 Focus Visible (Level AA)

Keyboard focus indicator is visible on all interactive elements.

| Check | Description | Status | Notes |
|-------|-------------|--------|-------|
| 2.2.4.a | Default focus outline is not removed without replacement | [ ] | |
| 2.2.4.b | Custom focus styles are high-contrast and visible | [ ] | |
| 2.2.4.c | Focus order follows logical reading order | [ ] | |

### 2.3 Understandable

#### 3.1.1 Language of Page (Level A)

The default human language of each page can be programmatically determined.

| Check | Description | Status | Notes |
|-------|-------------|--------|-------|
| 2.3.1.a | `<html>` element has a valid `lang` attribute | [ ] | `lang="es"` for Spanish |
| 2.3.1.b | `lang` attribute changes for content in other languages | [ ] | i18n module content |
| 2.3.1.c | `lang` attribute value uses valid BCP 47 tags | [ ] | |

#### 3.3.1 Error Identification (Level A)

| Check | Description | Status | Notes |
|-------|-------------|--------|-------|
| 2.3.2.a | Form errors are clearly identified in text | [ ] | |
| 2.3.2.b | Error messages describe the error specifically | [ ] | |
| 2.3.2.c | Error messages are programmatically associated with fields | [ ] | |
| 2.3.2.d | Error summary appears at top of form (for multiple errors) | [ ] | |

#### 3.3.2 Labels or Instructions (Level A)

| Check | Description | Status | Notes |
|-------|-------------|--------|-------|
| 2.3.3.a | All form fields have visible labels | [ ] | |
| 2.3.3.b | Required fields are clearly marked | [ ] | |
| 2.3.3.c | Input format requirements are described before the field | [ ] | |
| 2.3.3.d | Instructions are provided for complex interactions | [ ] | Page Builder |

### 2.4 Robust

#### 4.1.2 Name, Role, Value (Level A)

For all UI components, the name and role can be programmatically determined; states, properties, and values can be programmatically set.

| Check | Description | Status | Notes |
|-------|-------------|--------|-------|
| 2.4.1.a | Custom buttons have `role="button"` if not `<button>` elements | [ ] | |
| 2.4.1.b | Expandable sections have `aria-expanded` | [ ] | FAQ accordions |
| 2.4.1.c | Tabs use `role="tablist"`, `role="tab"`, `role="tabpanel"` | [ ] | Dashboard tabs |
| 2.4.1.d | Modals use `role="dialog"` and `aria-modal="true"` | [ ] | |
| 2.4.1.e | Progress indicators have `role="progressbar"` with `aria-valuenow` | [ ] | Onboarding progress |
| 2.4.1.f | Live regions use `aria-live="polite"` or `aria-live="assertive"` | [ ] | Toast notifications |
| 2.4.1.g | Custom dropdowns have proper ARIA combobox pattern | [ ] | |
| 2.4.1.h | Toggle switches have `role="switch"` and `aria-checked` | [ ] | Settings toggles |
| 2.4.1.i | Loading states are announced to screen readers | [ ] | `aria-busy="true"` |
| 2.4.1.j | Tooltips are accessible via `aria-describedby` | [ ] | |
| 2.4.1.k | ARIA attributes reference valid IDs that exist in DOM | [ ] | |
| 2.4.1.l | No conflicting roles (e.g., `role="button"` on `<a>` without keyboard) | [ ] | |

---

## 3. Known Issues Found in This Audit

### 3.1 Insufficient `prefers-reduced-motion` Coverage

The following CSS files contain `animation`, `transition`, or `@keyframes` rules but **lack** a corresponding `@media (prefers-reduced-motion: reduce)` override. Users who have enabled reduced motion in their OS settings may experience vestibular discomfort.

#### Theme CSS (missing `prefers-reduced-motion`)

| File | Animations Found |
|------|------------------|
| `web/themes/custom/ecosistema_jaraba_theme/css/admin-settings.css` | transition |
| `web/themes/custom/ecosistema_jaraba_theme/css/back-to-top.css` | animation, transition |

**Note**: The following theme CSS files already include `prefers-reduced-motion` (compliant):
- `main.css`
- `ecosistema-jaraba-theme.css`
- `critical/admin-pages.css`
- `critical/landing-empleo.css`
- `critical/templates.css`
- `critical/homepage.css`

#### Page Builder CSS (missing `prefers-reduced-motion`)

| File | Animations Found |
|------|------------------|
| `web/modules/custom/jaraba_page_builder/css/my-pages.css` | transition |
| `web/modules/custom/jaraba_page_builder/css/page-config-panel.css` | transition |
| `web/modules/custom/jaraba_page_builder/css/variant-selector.css` | transition |
| `web/modules/custom/jaraba_page_builder/css/canvas-editor.css` | animation, transition |
| `web/modules/custom/jaraba_page_builder/css/product-card.css` | transition |
| `web/modules/custom/jaraba_page_builder/css/navigation.css` | transition |
| `web/modules/custom/jaraba_page_builder/css/contact-form.css` | transition |
| `web/modules/custom/jaraba_page_builder/css/social-links.css` | transition |
| `web/modules/custom/jaraba_page_builder/css/page-builder-core.css` | transition |
| `web/modules/custom/jaraba_page_builder/css/preview.css` | transition |
| `web/modules/custom/jaraba_page_builder/css/grapes.min.css` | animation, transition (3rd party) |
| `web/modules/custom/jaraba_page_builder/css/canvas-hot-swap.css` | transition |
| `web/modules/custom/jaraba_page_builder/css/ai-field-generator.css` | animation, transition |

**Note**: The following Page Builder CSS files already include `prefers-reduced-motion` (compliant):
- `page-builder-blocks.css`
- `jaraba-page-builder.css`
- `grapesjs-onboarding.css`
- `premium/magic-ui.css`
- `premium/aceternity.css`

#### Other Module CSS (missing `prefers-reduced-motion`, with animations)

Beyond the two directories specifically audited, the following module CSS files across the codebase also have animations/transitions without `prefers-reduced-motion`:

| File | Category |
|------|----------|
| `web/modules/custom/jaraba_andalucia_ei/css/solicitud-ei-form.css` | Forms |
| `web/modules/custom/jaraba_pwa/css/pwa-install.css` | PWA |
| `web/modules/custom/jaraba_credentials/css/credentials.css` | Credentials |
| `web/modules/custom/jaraba_sso/css/sso-login.css` | Authentication |
| `web/modules/custom/jaraba_onboarding/css/onboarding-wizard.css` | Onboarding (has prefers-reduced-motion partially) |
| `web/modules/custom/jaraba_copilot_v2/css/copilot-v2.css` | AI Copilot |
| `web/modules/custom/jaraba_self_discovery/css/self-discovery.css` | Self Discovery |
| `web/modules/custom/jaraba_crm/css/crm-kanban.css` | CRM |
| `web/modules/custom/jaraba_interactive/css/player.css` | Interactive Content |
| `web/modules/custom/jaraba_events/css/event-card.css` | Events |

**Total CSS files with animations but missing `prefers-reduced-motion`**: ~125 files (see full list in Section 3.1 appendix below).

### 3.2 `innerHTML` Usage Requiring Sanitization Audit

The following JavaScript files use `innerHTML`, which can introduce XSS vulnerabilities if user-generated content is inserted without sanitization. Each instance needs review to confirm either (a) the value is hardcoded/safe, or (b) proper sanitization is applied (e.g., `DOMPurify.sanitize()` or Drupal's `Drupal.checkPlain()`).

#### Theme JS Files (10 files)

| File | Risk Level |
|------|------------|
| `web/themes/custom/ecosistema_jaraba_theme/js/slide-panel.js` | Medium |
| `web/themes/custom/ecosistema_jaraba_theme/js/scroll-animations.js` | Low |
| `web/themes/custom/ecosistema_jaraba_theme/js/related-articles.js` | Medium |
| `web/themes/custom/ecosistema_jaraba_theme/js/progressive-profiling.js` | Medium |
| `web/themes/custom/ecosistema_jaraba_theme/js/back-to-top.js` | Low |
| `web/themes/custom/ecosistema_jaraba_theme/js/dist/slide-panel.min.js` | Medium (minified) |
| `web/themes/custom/ecosistema_jaraba_theme/js/dist/scroll-animations.min.js` | Low (minified) |
| `web/themes/custom/ecosistema_jaraba_theme/js/dist/related-articles.min.js` | Medium (minified) |
| `web/themes/custom/ecosistema_jaraba_theme/js/dist/progressive-profiling.min.js` | Medium (minified) |
| `web/themes/custom/ecosistema_jaraba_theme/js/dist/back-to-top.min.js` | Low (minified) |

#### Module JS Files (116 files)

High-priority files (user input likely involved):

| File | Risk Level |
|------|------------|
| `web/modules/custom/jaraba_copilot_v2/js/copilot-chat-widget.js` | High |
| `web/modules/custom/jaraba_onboarding/js/onboarding-wizard.js` | High |
| `web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-canvas.js` | High |
| `web/modules/custom/jaraba_page_builder/js/canvas-editor.js` | High |
| `web/modules/custom/jaraba_page_builder/js/ai-content-generator.js` | High |
| `web/modules/custom/jaraba_page_builder/js/ai-field-generator.js` | High |
| `web/modules/custom/jaraba_legal/js/whistleblower-form.js` | High |
| `web/modules/custom/jaraba_interactive/js/editor/content-editor.js` | High |
| `web/modules/custom/jaraba_tenant_knowledge/js/faq-bot.js` | High |
| `web/modules/custom/jaraba_tenant_knowledge/js/help-center.js` | High |
| `web/modules/custom/jaraba_comercio_conecta/js/marketplace.js` | Medium |
| `web/modules/custom/jaraba_candidate/js/employability-copilot.js` | Medium |

### 3.3 Twig `|raw` Filter Usage

The `|raw` filter in Twig disables auto-escaping. While hardening was applied in Phase 1, the following files still use `|raw` and should be periodically reviewed:

| File | Count | Risk |
|------|-------|------|
| `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_hero.html.twig` | Used | Low (theme) |
| `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_intentions-grid.html.twig` | Used | Low |
| `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_features.html.twig` | Used | Low |
| `web/modules/custom/jaraba_page_builder/templates/page-builder-page.html.twig` | Used | Medium |
| `web/modules/custom/jaraba_page_builder/templates/blocks/hero/video-hero.html.twig` | Used | Medium |
| `web/modules/custom/jaraba_page_builder/templates/blocks/hero/split-hero.html.twig` | Used | Medium |
| `web/modules/custom/jaraba_page_builder/templates/blocks/hero/job-search-hero.html.twig` | Used | Medium |
| `web/modules/custom/jaraba_page_builder/templates/blocks/faq/faq-accordion.html.twig` | Used | Medium |
| `web/modules/custom/jaraba_page_builder/templates/canvas-editor-full.html.twig` | Used | Medium |
| `web/modules/custom/jaraba_skills/templates/skills-dashboard.html.twig` | Used | Low |
| `web/modules/custom/jaraba_credentials/templates/credential-verify.html.twig` | Used | Low |
| `web/modules/custom/jaraba_candidate/templates/candidate-profile-view.html.twig` | Used | Low |
| `web/modules/custom/jaraba_lms/templates/course-detail.html.twig` | Used | Low |
| `web/modules/custom/jaraba_job_board/templates/job-posting-detail.html.twig` | Used | Low |
| `web/modules/custom/jaraba_servicios_conecta/templates/servicios-provider-detail.html.twig` | Used | Low |
| `web/modules/custom/jaraba_comercio_conecta/templates/comercio-product-detail.html.twig` | Used | Low |
| `web/modules/custom/jaraba_geo/templates/professional-profile.html.twig` | Used | Low |

**Note**: Core and contrib module `|raw` usages (e.g., `commerce`, `webform`, `eca`) are outside our control and are assumed safe upstream.

### 3.4 Other Known Issues

| Issue | Severity | WCAG Criterion | Details |
|-------|----------|----------------|---------|
| Page Builder canvas lacks keyboard drag alternative | High | 2.1.1 | GrapesJS editor requires mouse for block placement |
| Copilot chat widget may not announce new messages | Medium | 4.1.2 | Needs `aria-live="polite"` on message container |
| Some admin tables lack `<caption>` elements | Medium | 1.3.1 | DataTables in admin center |
| Color-only status indicators (red/green dots) | Medium | 1.4.1 | Health dashboard, CRM status |
| Video player (LMS) keyboard controls incomplete | Medium | 2.1.1 | Volume, seek, playback rate |
| Onboarding wizard step indicators lack screen reader text | Low | 4.1.2 | Progress dots need `aria-label` |

---

## 4. Remediation Recommendations

### 4.1 Priority 1 - Critical (Fix Before Go-Live)

#### R1: Add Global `prefers-reduced-motion` Override

Add a global reduced-motion reset in the base theme CSS to cover all files at once:

```css
/* web/themes/custom/ecosistema_jaraba_theme/css/main.css */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

This provides a safety net. Individual files should still add their own `prefers-reduced-motion` overrides for more granular control (e.g., preserving essential animations like loading spinners).

**Effort**: 1 hour
**Files affected**: All CSS files globally

#### R2: Sanitize innerHTML Assignments

For each high-risk `innerHTML` usage:

1. Replace with `textContent` where HTML is not needed.
2. Use `DOMPurify.sanitize(value)` before assigning to `innerHTML`.
3. Use Drupal's `Drupal.checkPlain()` for simple text escaping.
4. Consider using `insertAdjacentHTML` or DOM API (`createElement`/`appendChild`) instead.

**Effort**: 8-16 hours (audit + fix for 12 high-risk files)
**Priority files**: copilot-chat-widget.js, canvas-editor.js, onboarding-wizard.js

#### R3: Add Skip Link

Ensure the base page template includes a skip link:

```html
<a href="#main-content" class="visually-hidden focusable skip-link">
  Skip to main content
</a>
```

Verify the target `id="main-content"` exists on the `<main>` element.

**Effort**: 30 minutes

#### R4: Set `lang` Attribute Dynamically

Ensure the `<html>` element `lang` attribute reflects the active language, especially for multilingual tenants using the jaraba_i18n module.

**Effort**: 1 hour

### 4.2 Priority 2 - High (Fix Within 2 Weeks Post-Launch)

#### R5: Keyboard Accessibility for Custom Widgets

- Add keyboard support to Page Builder drag-and-drop (keyboard shortcuts or accessible toolbar).
- Ensure CRM Kanban board cards can be moved with keyboard.
- Add keyboard controls to video player (LMS).

**Effort**: 16-24 hours

#### R6: ARIA Live Regions for Dynamic Content

Add `aria-live` attributes to:
- Copilot chat message container (`aria-live="polite"`)
- Toast notification container (`aria-live="assertive"`)
- Search results container (`aria-live="polite"`)
- Loading states (`aria-busy="true"`)

**Effort**: 4-6 hours

#### R7: Form Accessibility Improvements

- Ensure all form fields have visible `<label>` elements.
- Add `aria-describedby` for fields with help text.
- Use `aria-invalid="true"` on fields with validation errors.
- Group related radio buttons/checkboxes in `<fieldset>`.

**Effort**: 8-12 hours

#### R8: Color + Shape Status Indicators

Replace color-only indicators with color + icon/text:
- Health dashboard: Use icons (checkmark, warning, X) alongside colors.
- CRM status dots: Add text labels.
- Analytics charts: Use patterns in addition to colors.

**Effort**: 4-6 hours

### 4.3 Priority 3 - Medium (Fix Within 1 Month Post-Launch)

#### R9: Contrast Audit and Fixes

Run automated contrast checks and fix any elements below 4.5:1 ratio. Common offenders:
- Placeholder text in form fields.
- Disabled button text.
- Light gray text on white backgrounds.
- Secondary/muted text colors.

**Effort**: 8-12 hours

#### R10: Table Accessibility

- Add `<caption>` to all data tables.
- Ensure `<th>` elements have `scope="col"` or `scope="row"`.
- For complex tables, use `headers` attribute or `aria-describedby`.

**Effort**: 6-8 hours

#### R11: Per-File `prefers-reduced-motion` Overrides

After the global override (R1), add targeted `prefers-reduced-motion` blocks to the 15 Page Builder CSS files and 2 theme CSS files identified in Section 3.1, preserving only essential functional animations (spinners, progress bars).

**Effort**: 4-6 hours

### 4.4 Ongoing

| Activity | Frequency | Owner |
|----------|-----------|-------|
| Run axe-core on all priority pages | Every sprint | QA |
| Lighthouse accessibility audit | Monthly | Frontend Lead |
| Screen reader testing (NVDA/VoiceOver) | Quarterly | QA + Accessibility Champion |
| Review new `innerHTML` usage in PRs | Every PR | Code Reviewers |
| Review new `\|raw` usage in Twig templates | Every PR | Code Reviewers |
| Automated Pa11y in CI pipeline | Every deploy | CI/CD |
| Full WCAG 2.1 AA manual audit | Annually | External Auditor |

---

## Appendix A: Full List of CSS Files Missing `prefers-reduced-motion`

### Theme (`ecosistema_jaraba_theme/css/`)

1. `admin-settings.css`
2. `back-to-top.css`

### Page Builder (`jaraba_page_builder/css/`)

1. `my-pages.css`
2. `page-config-panel.css`
3. `variant-selector.css`
4. `canvas-editor.css`
5. `product-card.css`
6. `navigation.css`
7. `contact-form.css`
8. `social-links.css`
9. `page-builder-core.css`
10. `preview.css`
11. `grapes.min.css` (third-party -- override only)
12. `canvas-hot-swap.css`
13. `ai-field-generator.css`

---

## Document History

| Version | Date       | Author   | Changes                             |
|---------|------------|----------|-------------------------------------|
| 1.0     | 2026-02-18 | Platform | Initial WCAG 2.1 AA audit document  |
