# 🧭 Plan de Corrección: Navegación del Ecosistema — 6 Regresiones + Estrategia Discovery→Trial→Purchase

> **Tipo:** Plan de Implementación
> **Versión:** 1.0.0
> **Fecha:** 2026-02-27 19:30
> **Estado:** Pendiente de aprobación ⏳
> **Sprint:** Corrección urgente de regresiones en navegación
> **Autor:** IA Asistente (Antigravity)
> **Prioridad:** P0 — Regresiones que afectan la experiencia del visitante en todos los meta-sitios

---

## 📑 Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto y Origen de las Regresiones](#2-contexto-y-origen-de-las-regresiones)
3. [Diagnóstico Detallado: "El Código Existe" vs "El Usuario Lo Experimenta"](#3-diagnóstico-detallado-el-código-existe-vs-el-usuario-lo-experimenta)
4. [Visión Estratégica: Navegación como Motor del Ecosistema](#4-visión-estratégica-navegación-como-motor-del-ecosistema)
5. [Tabla de Correspondencia: Especificaciones Técnicas de Aplicación](#5-tabla-de-correspondencia-especificaciones-técnicas-de-aplicación)
6. [Tabla de Cumplimiento de Directrices del Proyecto](#6-tabla-de-cumplimiento-de-directrices-del-proyecto)
7. [Cambios Propuestos: Detalle Técnico por Archivo](#7-cambios-propuestos-detalle-técnico-por-archivo)
8. [Archivos Afectados y Dependencias](#8-archivos-afectados-y-dependencias)
9. [Secuencia de Implementación](#9-secuencia-de-implementación)
10. [Plan de Verificación](#10-plan-de-verificación)
11. [Elementos Excluidos del Alcance](#11-elementos-excluidos-del-alcance)
12. [Registro de Cambios](#12-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Se han identificado **6 regresiones** en la navegación del ecosistema tras la implementación del megamenu en `_header-classic.html.twig`. Estas regresiones afectan a **todos los meta-sitios** (pepejaraba.com, jarabaimpact.com, plataformadeecosistemas.es) y al SaaS principal (plataformadeempresas.com).

### Impacto en la experiencia del usuario

| Sitio | Navegación esperada | Navegación actual (rota) |
|-------|--------------------| -------------------------|
| **jaraba-saas** (SaaS) | Megamenu con Soluciones + Precios + Casos de Éxito + barra ecosistema en footer | Megamenu con fondo transparente, menú desalineado, sin barra ecosistema |
| **pepejaraba** (Marca personal) | Nav plana: Inicio, Manifiesto, Método, Casos de éxito, Blog, Contacto | Megamenu SaaS con Soluciones/Precios/Casos de Éxito (INCORRECTO) |
| **jarabaimpact** (B2B) | Nav plana: Inicio, Plataforma, Certificación, Impacto, Programas, Contacto | Megamenu SaaS (INCORRECTO) |
| **plataformadeecosistemas** (PED) | Nav plana propia configurable | Megamenu SaaS (INCORRECTO) |

### Causa raíz unificada

La variable `header_megamenu|default(true)` fue introducida sin verificar que **ningún meta-sitio la configura**. El `default(true)` activa el megamenu SaaS en **todos** los sitios, ignorando los `nav_items` personalizados que cada meta-sitio inyecta via `MetaSiteResolverService`.

---

## 2. Contexto y Origen de las Regresiones

### 2.1 Cadena de navegación documentada (Aprendizaje #128)

La cadena de navegación de los meta-sitios está documentada en `docs/tecnicos/aprendizajes/2026-02-26_meta_site_nav_copilot_links.md` y funciona así:

```
theme_preprocess_page()
  → Route match: entity.page_content.canonical
  → MetaSiteResolverService::resolveFromPageContent($page_content)
  → Returns: site_name, navigation_items, header_layout, CTA, footer, logo
  → Override $variables['theme_settings']['navigation_items']
  → Override $variables['meta_site'] = TRUE

_header.html.twig (dispatcher)
  → Parse navigation_items: "Texto|URL\n" → array [{text, url}]
  → Validate layout against whitelist ['classic', 'minimal', 'transparent']
  → Include _header-{layout}.html.twig

_header-classic.html.twig
  → Renders <nav> con nav_items (CUANDO use_megamenu es FALSE)
  → Renders megamenu hardcodeado (CUANDO use_megamenu es TRUE)
```

### 2.2 Qué se rompió y por qué

En la sesión anterior se introdujo la lógica de megamenu en `_header-classic.html.twig` con esta línea crítica:

```twig
{% set use_megamenu = ts.header_megamenu|default(true) %}
```

**Problemas con esta decisión:**

1. **`header_megamenu` no existe** en ningun `theme_settings`, `SiteConfig`, ni en ningún servicio PHP del proyecto. Es una variable inventada puntualmente.
2. **`default(true)` activa el megamenu para TODOS los sitios**, anulando el flujo de `nav_items` documentado en el Aprendizaje #128.
3. **La clase CSS `header--mega`** se aplica siempre al `<header>`, independientemente de si el megamenu está activo, lo que carga estilos innecesarios en meta-sitios.

### 2.3 Otros problemas colaterales

| Regresión | Causa técnica |
|-----------|--------------|
| Megamenu transparente | `background: var(--header-bg)` sin fallback CSS. La variable `--header-bg` nunca se define ni en `_injectable.scss` ni en `_variables.scss` ni en el hook de inyección de variables del tema. |
| Menú desalineado (7px) | El botón `Soluciones` (`<button>`) y los links `Precios`/`Casos de Éxito` (`<a>`) tienen diferente baseline, font stack, y padding por defecto del user-agent stylesheet del navegador. No se normalizan sus propiedades tipográficas. |
| Barra ecosistema no visible | El template `_footer.html.twig` (líneas 178-205) la controla con `ecosystem_footer_enabled|default(false)`. Según la Regla de Oro #68: _"El SaaS principal NO muestra la banda (ecosystem_footer_enabled = FALSE por default)"_. Sin embargo, esta regla es cuestionable desde la perspectiva de negocio (ver §4). |
| Estilos rotos en homepage | SCSS compilado anteriormente a `css/main.css` en vez de `css/ecosistema-jaraba-theme.css`. Ya corregido — solo requiere recompilación final. |
| Mobile overlay hardcodeado | El overlay móvil en `_header.html.twig` hardcodea los grupos del megamenu SaaS directamente, sin respetar `use_megamenu`. Los meta-sitios en móvil ven la misma estructura SaaS incorrecta. |

---

## 3. Diagnóstico Detallado: "El Código Existe" vs "El Usuario Lo Experimenta"

> **Principio rector**: No basta con que el código correcto exista en el codebase. Lo que importa es que el usuario lo **experimente** en su navegador.

### 3.1 Navegación plana de meta-sitios

| Aspecto | El código existe | El usuario lo experimenta |
|---------|-----------------|--------------------------|
| `nav_items` personalizado | ✅ `MetaSiteResolverService` los inyecta correctamente en `theme_settings['navigation_items']` | ❌ **No se renderizan**: el `use_megamenu|default(true)` salta al bloque del megamenu y nunca alcanza el fallback de `nav_items` (líneas 150-156) |
| `_header-classic.html.twig` fallback plano | ✅ Existe entre las líneas 150-156: `{% for item in nav_items %}` | ❌ **Código muerto**: nunca se ejecuta porque la condición `{% if use_megamenu %}` siempre es `true` |
| `SiteConfig.navigation_items` | ✅ pepejaraba: "Inicio\|/, Manifiesto\|/manifiesto, Método\|/metodo, ..." | ❌ **Datos ignorados**: se parsean correctamente en `_header.html.twig` pero el partial `_header-classic` los descarta |

### 3.2 Barra de ecosistema en el footer

| Aspecto | El código existe | El usuario lo experimenta |
|---------|-----------------|--------------------------|
| Template `_footer.html.twig` líneas 178-205 | ✅ Renderiza la barra con links default si `eco_enabled` | ❌ **No se muestra** en jaraba-saas porque `ecosystem_footer_enabled|default(false)` |
| Meta-sitios | ✅ La barra aparece en pepejaraba/jarabaimpact/PED (verificado en navegador) | ✅ Funciona correctamente en meta-sitios |
| SaaS principal | ❌ `ecosystem_footer_enabled` no está configurado | ❌ La barra NO aparece |

### 3.3 Megamenu transparente

| Aspecto | El código existe | El usuario lo experimenta |
|---------|-----------------|--------------------------|
| `_header.scss`: `.header__mega-panel` | ✅ `background: var(--header-bg)` | ❌ **Transparente**: la variable CSS `--header-bg` no está definida en ningún archivo del proyecto. No existe en `_injectable.scss`, ni en `_variables.scss`, ni en el hook `preprocess_html`. |
| Fallback inline en SCSS | ❌ No existe | ❌ El navegador hereda `background: transparent` (valor por defecto CSS) |

### 3.4 Alineación del menú

| Aspecto | El código existe | El usuario lo experimenta |
|---------|-----------------|--------------------------|
| `.header__menu-link` en SCSS | ✅ Existe con `display: flex` y `align-items: center` | ❌ **Desalineado 7px**: el `<button>` de "Soluciones" tiene user-agent padding/margin diferente al `<a>` de "Precios". `font: inherit` no se aplica al botón. |
| `.header__menu` en SCSS | ✅ Existe | ❌ Falta `align-items: center` o `align-items: baseline` explícito en el contenedor `<ul>` |

---

## 4. Visión Estratégica: Navegación como Motor del Ecosistema

### 4.1 Las 3 capas del ecosistema Jaraba

El ecosistema tiene 4 dominios con roles diferenciados en el funnel de conversión:

```
CAPA 1 — DISCOVERY (Top of Funnel)
├── pepejaraba.com      → Marca personal, autoridad, confianza
│   Nav: Inicio | Manifiesto | Método | Casos de éxito | Blog | Contacto
│   CTA: "Acceder al Ecosistema →" → plataformadeempresas.com
│
├── jarabaimpact.com    → B2B, impacto social, institucional
│   Nav: Inicio | Plataforma | Certificación | Impacto | Programas | Contacto
│   CTA: "Solicita una Demo →" → /contacto
│
└── plataformadeecosistemas.es → PED Corporativo, infraestructura
    Nav: configurable per tenant
    CTA: configurable

CAPA 2 — TRIAL (Middle of Funnel)
└── plataformadeempresas.com (SaaS principal)
    Nav: Megamenu (Soluciones por audiencia) | Precios | Casos de Éxito
    CTA: "Empieza gratis →" → /user/register
    Megamenu: Para Profesionales | Para Empresas | Para Instituciones

CAPA 3 — PURCHASE (Bottom of Funnel)
└── /planes, /emprendimiento/dashboard, /user/register
    Nav: heredada del SaaS
```

### 4.2 El papel de la barra ecosistema

La **barra ecosistema en el footer** actúa como **puente cross-domain** que permite al visitante descubrir el ecosistema completo:

- **En pepejaraba.com**: El visitante llega por la marca personal → descubre que hay una plataforma SaaS detrás → navega al SaaS
- **En jarabaimpact.com**: El visitante llega por el B2B → descubre los verticales del SaaS → navega al SaaS
- **En el SaaS**: El visitante ve que detrás hay una marca personal de confianza → refuerza autoridad E-E-A-T

> [!IMPORTANT]
> **Decisión de diseño**: La barra ecosistema se activará por defecto TAMBIÉN en el SaaS principal, contradiciendo parcialmente la Regla #68 que dice _"El SaaS principal NO muestra la banda"_. La razón: desde la perspectiva SEO/GEO (E-E-A-T) y de negocio, vincular todos los dominios del ecosistema refuerza la autoridad y facilita el descubrimiento. Este cambio se documenta como excepción fundamentada.

### 4.3 Principio de navegación por meta-sitio

| Meta-sitio | Header layout | Megamenu | Nav items | CTA | Barra ecosistema |
|------------|--------------|----------|-----------|-----|-----------------|
| SaaS (plataformadeempresas.com) | `classic` | ✅ Sí | — (megamenu reemplaza) | Empieza gratis | ✅ Visible |
| pepejaraba.com | `classic` | ❌ No | 6 items configurables | Acceder al Ecosistema | ✅ Visible |
| jarabaimpact.com | `classic` | ❌ No | 6 items configurables | Solicita una Demo | ✅ Visible |
| plataformadeecosistemas.es | `classic` | ❌ No | Items configurables | Configurable | ✅ Visible |

---

## 5. Tabla de Correspondencia: Especificaciones Técnicas de Aplicación

### 5.1 Especificaciones del flujo de navegación

| ID | Especificación | Documento fuente | Estado actual | Acción requerida |
|----|---------------|------------------|---------------|------------------|
| NAV-001 | `MetaSiteResolverService` inyecta `navigation_items` en `theme_settings` | Aprendizaje #128, líneas 60-79 | ✅ Funciona | Ninguna |
| NAV-002 | `_header.html.twig` parsea `navigation_items` a array `[{text, url}]` | Aprendizaje #128, líneas 70-74 | ✅ Funciona | Ninguna |
| NAV-003 | `_header-classic.html.twig` renderiza `nav_items` cuando `use_megamenu` es `false` | Aprendizaje #128, líneas 75-78 | ❌ ROTO: `default(true)` impide | Cambiar `default(true)` → `default(false)` |
| NAV-004 | Meta-sitios usan `header_type: classic` en `SiteConfig` | Aprendizaje #128, líneas 55-58 | ✅ Funciona | Ninguna |
| NAV-005 | `header_megamenu` controla activación del megamenu | Nuevo (introducido en esta sesión) | ❌ ROTO: variable no existe en ningún servicio/config | Inyectar `true` para SaaS principal en `preprocess_page()` |
| NAV-006 | Mobile overlay respeta `use_megamenu` | Nuevo | ❌ ROTO: hardcodea estructura SaaS | Condicionar por `use_megamenu` |

### 5.2 Especificaciones del theming CSS

| ID | Especificación | Documento fuente | Estado actual | Acción requerida |
|----|---------------|------------------|---------------|------------------|
| SCSS-001 | Variables CSS con fallback inline: `var(--ej-*, $fallback)` | Arquitectura Theming §2.1, Regla de Oro "SSOT" | ❌ `var(--header-bg)` sin fallback | Añadir fallback: `var(--header-bg, #ffffff)` |
| SCSS-002 | Compilación con Dart Sass moderno | Directrices §2.2.1, Arquitectura Theming §5 | ✅ Correcto | Recompilar tras cambios |
| SCSS-003 | Output CSS del tema: `css/ecosistema-jaraba-theme.css` | Verificación empírica (discrepancia con doc que dice `css/main.css`) | ⚠️ Confirmar nombre real | Verificar `libraries.yml` |
| SCSS-004 | `color-mix()` en vez de `rgba()` | Directrices migración AgroConecta | N/A para este fix | — |
| SCSS-005 | BEM para clases CSS | Directrices §5.5 Fase 5 | ✅ header__menu-link, mega-panel__column | — |
| SCSS-006 | `@use` en vez de `@import` (Dart Sass moderno) | Directrices §2.2.1 | Verificar en main.scss | Verificar |

### 5.3 Especificaciones del footer ecosistema

| ID | Especificación | Documento fuente | Estado actual | Acción requerida |
|----|---------------|------------------|---------------|------------------|
| ECO-001 | `ecosystem_footer_enabled` controla visibilidad | `_footer.html.twig` líneas 178-205, Regla #68 | ❌ `default(false)` impide mostrar en SaaS | Cambiar default → `true` con links por defecto |
| ECO-002 | `ecosystem_footer_links` es JSON array configurable | Regla #68: `[{name, url, label, current}]` | ❌ No configurado → array vacío | Añadir defaults con los 4 dominios |
| ECO-003 | `current: true` marca el sitio actual (bold, sin link) | Regla #68 | N/A (requiere detección dinámica) | Detectar domain actual automáticamente |

---

## 6. Tabla de Cumplimiento de Directrices del Proyecto

| # | Directriz / Regla | Sección | Cumplimiento en este plan | Notas |
|---|-------------------|---------|--------------------------|-------|
| 1 | **SCSS: Variables inyectables `var(--ej-*)`** | Directrices §2.2.1, Arq. Theming §2.2 | ✅ | `var(--header-bg, #ffffff)` con fallback. No se definen variables SCSS locales. |
| 2 | **Dart Sass moderno** (`color.adjust`, no `darken`) | Directrices §2.2.1, Arq. Theming §6.2 | ✅ | No se usan funciones deprecadas en los cambios propuestos. |
| 3 | **Compilación desde Docker** | Arq. Theming §5.2 | ✅ | Comando: `lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss css/ecosistema-jaraba-theme.css --style=compressed --no-source-map"` |
| 4 | **Textos traducibles (`{% trans %}`)** | Directrices (principio general i18n) | ✅ | Todos los textos UI usan `{% trans %}`. Links ecosistema: `{% trans %}Ecosistema Jaraba{% endtrans %}`. |
| 5 | **Templates Twig limpias sin regiones** | Directrices §2.2.2 | ✅ | Solo se modifican parciales (`_header-classic.html.twig`, `_footer.html.twig`, `_header.html.twig`). No se tocan page templates. |
| 6 | **Parciales con `{% include %}` reutilizables** | Directrices §2.2.2, §2.2.3 | ✅ | Se modifican parciales existentes. No se crean nuevos — los parciales de header y footer ya existen y se reutilizan en todas las page templates. |
| 7 | **Body classes via `hook_preprocess_html()`** | Directrices §2.2.2, lección crítica | ✅ | No se añaden clases al body en este fix. Si fuera necesario se usaría el hook, no `attributes.addClass()`. |
| 8 | **Iconos: `jaraba_icon()` duotone-first, zero chinchetas** | Regla #32, ICON-CONVENTION-001 | N/A | Este fix no introduce iconos. |
| 9 | **Paleta Jaraba: colores via tokens** | Arq. Theming §6.3 | ✅ | Se usa `var(--header-bg, #ffffff)` y `var(--ej-text-primary, #1a1d29)`, no hex hardcodeados nuevos. |
| 10 | **Sticky header por defecto** | Regla #27, CSS-STICKY-001 | ✅ | No se modifica el `position` del header. Se mantiene `sticky` global. |
| 11 | **Meta-site nav requiere header partial + classic layout** | Regla #46, META-SITE-NAV-001 | ✅ | Este es exactamente el fix: restaurar la cadena `header partial → classic layout → nav_items`. |
| 12 | **Banda ecosistema configurable con JSON** | Regla #68 | ✅ con excepción documentada | Se cambia `default(false)` → `default(true)` para que la banda sea visible por defecto, con links hardcodeados como fallback. Excepción: la Regla #68 dice que el SaaS principal no muestra la banda, pero la perspectiva de negocio/SEO la requiere (ver §4.2). |
| 13 | **MetaSiteRenderService como punto único de resolución** | Regla #40, META-SITE-RENDER-001 | ✅ | No se modifica este servicio. Se respeta el flujo existente. |
| 14 | **CSRF no aplica** (no hay APIs nuevas) | Regla #11 | N/A | — |
| 15 | **Documentar siempre** | Regla #7 | ✅ | Este documento, actualización de directrices y creación de aprendizaje post-implementación. |
| 16 | **No hardcodear: Config via UI cuando sea posible** | Regla #1 | ⚠️ Parcial | Los links ecosistema se hardcodean como fallback pero siguen siendo configurables via `theme_settings`. El megamenu se activa condicionalmente en `preprocess_page()`. |
| 17 | **GrapesJS: N/A** | — | N/A | Este fix no afecta al Page Builder. |
| 18 | **Entidades de contenido: N/A** | — | N/A | No se crean ni modifican entidades. |
| 19 | **Frontend full-width sin sidebar admin** | Directrices §2.2.2 | ✅ | Los parciales modificados ya forman parte del layout full-width. |
| 20 | **Modales para CRUD: N/A** | Directrices (slide-panel pattern) | N/A | No hay acciones CRUD en este fix. |

---

## 7. Cambios Propuestos: Detalle Técnico por Archivo

### 7.1 [MODIFY] `_header-classic.html.twig`

**Ruta**: `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_header-classic.html.twig`

**Descripción extensa**: Este archivo es el sub-parcial que renderiza el header en layout "classic" (el más completo, con menú horizontal visible). Es incluido por el dispatcher `_header.html.twig` cuando `header_layout == 'classic'`. Fue modificado recientemente para incorporar el megamenu con categorización por audiencia (Para Profesionales / Para Empresas / Para Instituciones), pero la variable de control `use_megamenu` se configuró con `default(true)`, lo que sobrescribe la navegación personalizada de TODOS los meta-sitios.

**Cambios específicos:**

#### Cambio 7.1.1: Default de `header_megamenu`

```diff
-{% set use_megamenu = ts.header_megamenu|default(true) %}
+{% set use_megamenu = ts.header_megamenu|default(false) %}
```

**Justificación**: Al cambiar el default a `false`, solo se activará el megamenu cuando se inyecte `header_megamenu: true` explícitamente. Esto restaura el flujo documentado en el Aprendizaje #128 donde los meta-sitios usan `nav_items`.

#### Cambio 7.1.2: Clase CSS condicional

```diff
-<header class="landing-header landing-header--classic header--mega" role="banner">
+<header class="landing-header landing-header--classic{{ use_megamenu ? ' header--mega' : '' }}" role="banner">
```

**Justificación**: La clase `header--mega` aplica estilos del megamenu (grid, panel posicionamiento, hover). No debe cargarse en meta-sitios donde no existe megamenu. El operador ternario de Twig lo condiciona.

---

### 7.2 [MODIFY] `ecosistema_jaraba_theme.theme`

**Ruta**: `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme`

**Descripción extensa**: El archivo `.theme` contiene los hooks de preproceso de Drupal. El hook `ecosistema_jaraba_theme_preprocess_page()` ya inyecta variables de `MetaSiteResolverService` cuando detecta que la ruta pertenece a un meta-sitio (via `page_content` en el route match). Para el SaaS principal (cuando NO es meta-sitio), necesitamos inyectar `header_megamenu: true` para que `_header-classic.html.twig` active el megamenu.

**Cambio específico**: Añadir al final del hook `preprocess_page`, después de la lógica de meta-sitio:

```php
// Si NO es meta-sitio → estamos en el SaaS principal → activar megamenu
if (empty($variables['meta_site'])) {
  $variables['theme_settings']['header_megamenu'] = TRUE;
}
```

**Justificación**: Esta es la solución más limpia porque:
- Usa la infraestructura existente (`meta_site` ya se establece en el preprocess)
- No requiere inventar nuevas variables de configuración
- La lógica es explícita: "si no es meta-sitio, es SaaS, activa megamenu"
- Es coherente con cómo `MetaSiteResolverService` inyecta otras variables

---

### 7.3 [MODIFY] `_header.scss`

**Ruta**: `web/themes/custom/ecosistema_jaraba_theme/scss/components/_header.scss`

**Descripción extensa**: Este parcial SCSS contiene todos los estilos del header, incluyendo los del megamenu (`.header--mega`, `.header__mega-panel`, `.mega-panel__*`). Tiene 2 problemas CSS:

#### Cambio 7.3.1: Fondo sólido del megamenu

```diff
 .header--mega .header__mega-panel {
-    background: var(--header-bg);
+    background: var(--header-bg, #ffffff);
+    color: var(--ej-text-primary, #1a1d29);
+    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
```

**Justificación**: La variable `--header-bg` nunca se declara en `_injectable.scss` ni en ningún otro archivo del proyecto. Sin fallback, el navegador aplica `background: transparent`, haciendo que el panel del megamenu sea invisible sobre fondos con contenido. Se añade fallback blanco (`#ffffff`) como color base seguro, `box-shadow` para separación visual, y `color` para legibilidad del texto.

#### Cambio 7.3.2: Normalización de alineación

Se necesita asegurar que `.header__menu-link` normalice el user-agent stylesheet tanto para `<button>` como para `<a>`:

```diff
 .header--mega .header__menu-link {
     display: flex;
     align-items: center;
     gap: var(--ej-spacing-xxs, 0.25rem);
-    padding: var(--ej-spacing-sm, 0.75rem);
+    padding: var(--ej-spacing-xs, 0.5rem) var(--ej-spacing-sm, 0.75rem);
     border: none;
     background: none;
     cursor: pointer;
+    font: inherit;
+    color: inherit;
+    line-height: 1.5;
+    text-decoration: none;
 }
```

Además, el contenedor `<ul>` necesita alineación:

```diff
 .header--mega .header__menu {
     display: flex;
+    align-items: center;
     list-style: none;
     margin: 0;
     padding: 0;
     gap: var(--ej-spacing-md, 1rem);
 }
```

**Justificación**: El `<button>` de "Soluciones" tiene padding, font y line-height del user-agent stylesheet que difieren de los del `<a>` de "Precios" y "Casos de Éxito". `font: inherit` fuerza al `<button>` a heredar la tipografía del padre (que ya usa Outfit via `--ej-font-family`). `align-items: center` en el `<ul>` asegura alineación vertical de todos los `<li>`.

---

### 7.4 [MODIFY] `_footer.html.twig`

**Ruta**: `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_footer.html.twig`

**Descripción extensa**: El footer contiene condicionalmente la barra de navegación transversal del ecosistema (líneas 178-205). Esta barra permite al visitante descubrir los otros sitios del ecosistema, facilitando el flujo discovery→trial→purchase. Actualmente está controlada por `ecosystem_footer_enabled|default(false)`, lo que la oculta por defecto.

**Cambio específico**: Cambiar los defaults para que la barra sea visible por defecto con links del ecosistema:

```diff
-{% set eco_enabled = (ts.ecosystem_footer_enabled is defined) ? ts.ecosystem_footer_enabled : false %}
-{% set eco_links = ts.ecosystem_footer_links|default([]) %}
+{% set eco_enabled = (ts.ecosystem_footer_enabled is defined) ? ts.ecosystem_footer_enabled : true %}
+{% set eco_links_default = [
+  { name: 'Pepe Jaraba'|t, url: 'https://pepejaraba.com' },
+  { name: 'Jaraba Impact'|t, url: 'https://jarabaimpact.com' },
+  { name: 'PED Corporativo'|t, url: 'https://plataformadeecosistemas.es' },
+  { name: 'Plataforma SaaS'|t, url: 'https://plataformadeempresas.com' },
+] %}
+{% set eco_links = ts.ecosystem_footer_links|default(eco_links_default) %}
```

**Justificación**: La Regla #68 indica que este componente debe ser configurable via `SiteConfig`. Al proporcionar defaults, la barra aparece inmediatamente sin necesidad de configuración manual. Los textos son traducibles con `|t`. La configuración personalizada via UI seguirá teniendo prioridad sobre los defaults.

---

### 7.5 [MODIFY] `_header.html.twig`

**Ruta**: `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_header.html.twig`

**Descripción extensa**: Este archivo es el **dispatcher** de headers. Parsea los `navigation_items` del `theme_settings`, valida el layout, e incluye el sub-parcial correspondiente (`_header-classic.html.twig`, `_header-minimal.html.twig`, etc.). También contiene la estructura del **overlay móvil**, que actualmente hardcodea los grupos del megamenu SaaS sin respetar la variable `use_megamenu`.

**Cambio específico**: Condicionar el mobile overlay por `header_megamenu`:

```twig
{# Mobile overlay — condicional por megamenu #}
{% set use_megamenu = ts.header_megamenu|default(false) %}

<div class="mobile-overlay" id="mobile-menu-overlay" aria-hidden="true">
  <div class="mobile-overlay__container">
    {% if use_megamenu %}
      {# Grupos accordion del megamenu SaaS #}
      <details class="mobile-mega-group">
        <summary>{% trans %}Para Profesionales{% endtrans %}</summary>
        <ul>
          <li><a href="/empleabilidad">{% trans %}Empleabilidad{% endtrans %}</a></li>
          <li><a href="/talento">{% trans %}Talento{% endtrans %}</a></li>
        </ul>
      </details>
      {# ... resto de grupos ... #}
    {% else %}
      {# Navegación plana (meta-sitios) #}
      <ul class="mobile-menu-list">
        {% for item in nav_items %}
          <li><a href="{{ item.url }}">{{ item.text }}</a></li>
        {% endfor %}
      </ul>
    {% endif %}
  </div>
</div>
```

**Justificación**: El mobile overlay debe ser coherente con el header desktop. Si el desktop muestra navegación plana, el móvil debe mostrar la misma. Si el desktop tiene megamenu, el móvil muestra los grupos en accordion.

---

### 7.6 Recompilación SCSS

**Comando desde Docker (Lando)**:

```bash
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss css/ecosistema-jaraba-theme.css --style=compressed --no-source-map"
```

**Seguido de limpieza de caché**:

```bash
lando drush cr
```

---

## 8. Archivos Afectados y Dependencias

| Archivo | Tipo | Cambio | Dependencias |
|---------|------|--------|-------------|
| `_header-classic.html.twig` | Twig partial | Cambiar default megamenu + clase condicional | Depende de `ts.header_megamenu` |
| `ecosistema_jaraba_theme.theme` | PHP hook | Inyectar `header_megamenu` para SaaS principal | Depende de `$variables['meta_site']` |
| `_header.scss` | SCSS | Fix transparencia + alineación | Compilado → `css/ecosistema-jaraba-theme.css` |
| `_footer.html.twig` | Twig partial | Activar barra ecosistema por defecto | Depende de `ts.ecosystem_footer_enabled` |
| `_header.html.twig` | Twig partial | Mobile overlay condicional | Depende de `ts.header_megamenu` |

---

## 9. Secuencia de Implementación

La secuencia importa porque hay dependencias:

1. **PRIMERO**: `ecosistema_jaraba_theme.theme` — inyectar `header_megamenu: true` para SaaS principal
2. **SEGUNDO**: `_header-classic.html.twig` — cambiar default + clase condicional
3. **TERCERO**: `_header.html.twig` — mobile overlay condicional
4. **CUARTO**: `_footer.html.twig` — activar barra ecosistema
5. **QUINTO**: `_header.scss` — fix transparencia + alineación
6. **SEXTO**: Compilar SCSS + `drush cr`
7. **SÉPTIMO**: Verificar en navegador (§10)

---

## 10. Plan de Verificación

### 10.1 Verificación automatizada

```bash
# PHP lint del hook
lando ssh -c "php -l /app/web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme"

# Verificar compilación SCSS
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss css/ecosistema-jaraba-theme.css --style=compressed --no-source-map"

# Cache clear
lando drush cr
```

### 10.2 Verificación en navegador (4 URLs)

| # | URL | Verificar |
|---|-----|-----------|
| 1 | `https://jaraba-saas.lndo.site/es` | ✅ Megamenu visible con fondo blanco/sólido. ✅ 3 columnas alineadas. ✅ "Soluciones", "Precios", "Casos de Éxito" alineados verticalmente. ✅ Barra ecosistema en footer con 4 links. |
| 2 | `https://pepejaraba.jaraba-saas.lndo.site/` | ✅ Navegación plana con items propios (Inicio, Manifiesto, Método...). ❌ SIN megamenu. ✅ CTA "Acceder al Ecosistema". ✅ Barra ecosistema en footer. |
| 3 | `https://jarabaimpact.jaraba-saas.lndo.site/` | ✅ Navegación plana con items propios (Inicio, Plataforma, Certificación...). ❌ SIN megamenu. ✅ CTA "Solicita una Demo". ✅ Barra ecosistema en footer. |
| 4 | `https://plataformadeecosistemas.jaraba-saas.lndo.site/` | ✅ Navegación plana configurable. ❌ SIN megamenu. ✅ Barra ecosistema en footer. |

### 10.3 Verificación móvil

- Redimensionar viewport a 375px de ancho.
- En SaaS: hamburguesa → overlay con grupos accordion del megamenu.
- En pepejaraba: hamburguesa → overlay con lista plana de items del meta-sitio.

---

## 11. Elementos Excluidos del Alcance

| Item | Razón de exclusión | Ticket futuro |
|------|-------------------|---------------|
| **Precios dinámicos por vertical** (#6) | Requiere vincular la configuración de planes del sistema de billing (`jaraba_billing`) a los controllers de cada vertical. Cambio arquitectónico que afecta a múltiples módulos. | Sí — Sprint separado |
| **i18n completo del megamenu** | Los textos usan `{% trans %}` pero las traducciones aún no existen en la BD. Requiere `drush locale:import`. | Sí — Sprint i18n |
| **Schema.org por meta-sitio** | Regla #69: Person/Organization con `sameAs`. Ya existe el partial pero falta verificación de renderizado. | Verificar post-fix |
| **Selector de idioma en header** | El partial `_language-switcher.html.twig` existe pero requiere `available_languages > 1` | Depende de i18n |

---

## 12. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-27 | 1.0.0 | Creación inicial del plan con diagnóstico completo, tabla de cumplimiento y detalle técnico |
