# Aprendizaje #149: Corrección Navegación Megamenu + Pricing Feature Labels

**Fecha:** 2026-02-27
**Contexto:** Sesión de corrección de 6 regresiones de navegación del ecosistema y mejora de la visualización de precios.

---

## Problema 1: Megamenu aplicado a todos los meta-sitios

**Síntoma:** La misma barra de navegación megamenu del SaaS principal (plataformadeempresas.com) se aplicaba incorrectamente a pepejaraba.com, jarabaimpact.com y plataformadeecosistemas.es. Los 3 meta-sitios discovery perdían su navegación personalizada.

**Causa raíz:** La variable `ts.header_megamenu|default(true)` en `_header-classic.html.twig` usaba `default(true)`, lo que activaba el megamenu por defecto ya que la variable NUNCA existía en ningún `theme_settings` ni `SiteConfig`. Resultado: megamenu siempre activo para todos los sitios.

**Solución (MEGAMENU-CONTROL-001):**
1. Cambiar `default(true)` → `default(false)` en `_header-classic.html.twig`
2. Inyectar `header_megamenu: TRUE` explícitamente desde `ecosistema_jaraba_theme.theme` en `preprocess_page()` cuando NO es meta-sitio (SaaS principal)
3. Condicionar la clase CSS `header--mega` al valor real de `use_megamenu`
4. Condicionar el mobile overlay (accordions vs nav plana) al mismo flag

**Archivos modificados:**
- `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_header-classic.html.twig`
- `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_header.html.twig`
- `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme`
- `web/themes/custom/ecosistema_jaraba_theme/scss/components/_header.scss`

---

## Problema 2: Megamenu transparente

**Síntoma:** El panel del megamenu aparecía totalmente transparente, sin fondo visible.

**Causa raíz:** `background: var(--header-bg)` sin fallback. La variable CSS `--header-bg` nunca se definía en el contexto del megamenu.

**Solución:** `background: var(--header-bg, #ffffff)` con fallback explícito blanco.

---

## Problema 3: Menú desalineado (7px)

**Síntoma:** Los items del menú principal tenían alturas diferentes — `Soluciones` era un `<button>`, mientras `Precios` y `Casos de Éxito` eran `<a>`, con diferentes baseline y padding.

**Solución:** Normalizar todos los `.header__menu-link` con `font: inherit; color: inherit; line-height: 1.5;` y padding unificado `var(--ej-spacing-xs) var(--ej-spacing-sm)`.

---

## Problema 4: Barra ecosistema invisible en footer

**Síntoma:** La barra de navegación transversal del ecosistema no aparecía en el footer de ningún sitio.

**Causa raíz:** `ecosystem_footer_enabled|default(false)` y `ecosystem_footer_links|default([])` — doble default falsy impedía la visualización.

**Solución:** Cambiar default a `true` y proporcionar links default con los 4 sitios del ecosistema.

---

## Problema 5: Pricing features con machine names

**Síntoma:** La página `/planes` mostraba nombres de máquina como `seo_advanced`, `ai_unlimited` en las listas de features de cada tier, en lugar de etiquetas legibles como "SEO Avanzado", "IA sin límites".

**Causa raíz:** `MetaSitePricingService::getPricingPreview()` devolvía los keys de features directamente del config YAML, que usa machine names para lookups programáticos (`hasFeature()`).

**Solución (PRICING-LABEL-001):**
1. Crear `formatFeatureLabels()` private method con mapa completo de 28 machine names → labels traductibles en español
2. Aplicar `formatFeatureLabels()` tanto a `features` en `getPricingPreview()` como a `features_highlights` en `getFromPrice()`
3. Conservar `features_raw` con los machine names para uso programático
4. Añadir features base (basic_profile, community, one_vertical, email_support) al plan Starter cuando su lista está vacía

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Service/MetaSitePricingService.php`

---

## Patrón: Almacenamiento dual machine names + labels

```php
// Almacenar machine names para lookups
$tier['features_raw'] = $features; // ['seo_advanced', 'ai_unlimited']

// Labels para display
$tier['features'] = $this->formatFeatureLabels($features); // ['SEO Avanzado', 'IA sin límites']
```

**Principio:** Los machine names son estables y útiles para logic checks (`in_array('seo_advanced', $features)`). Los labels son para presentación y DEBEN pasar por `$this->t()` para i18n.

---

## Reglas derivadas

| Regla | Prioridad | Descripción |
|-------|-----------|-------------|
| MEGAMENU-CONTROL-001 | P1 | Variables Twig de control de features DEBEN usar `default(false)` — opt-in explícito desde PHP, NUNCA opt-out implícito por ausencia de variable |
| PRICING-LABEL-001 | P1 | Features en pricing DEBEN tener almacenamiento dual: machine names para lógica, labels traductibles para display. NUNCA mostrar machine names al usuario |

---

## Reglas de oro

- **#82**: Toda variable Twig que controla la activación de features DEBE usar `|default(false)` para ser opt-in explícita. Si la variable no existe, la feature no se activa. La inyección se hace desde PHP (preprocess hooks) con lógica de negocio clara.
- **#83**: Nunca exponer machine names al usuario final. Crear mapas `machine_name → $this->t('Label')` y aplicarlos antes del render. Conservar los machine names en un campo `_raw` para uso programático.

---

## Cross-refs

- Directrices PROYECTO v99.0.0
- Arquitectura v89.0.0
- Índice General v128.0.0
- Flujo de Trabajo v52.0.0
- Aprendizaje #128 (Meta-Site Nav Fix)
- Aprendizaje #144 (Navegación Transversal Ecosistema)
