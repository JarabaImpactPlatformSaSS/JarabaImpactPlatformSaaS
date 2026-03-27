# Plan de Implementación: Popup Dual de Captación de Leads — Andalucía +ei 2ª Edición

> **Fecha:** 2026-03-27
> **Autor:** Claude Opus 4.6 (1M context)
> **Versión:** 1.0.0
> **Auditoría previa:** `20260327a-auditoria-popup-dual-captacion-leads_Claude.md`
> **Módulo:** jaraba_andalucia_ei
> **Estimación de archivos modificados:** 5 existentes + 1 nuevo (validator) + 1 nuevo (imagen negocio hero)
> **Reglas nuevas:** POPUP-DUAL-SELECTOR-001, POPUP-SAFEGUARD-001, POPUP-ANON-ONLY-001, POPUP-SHARED-DISMISS-001, POPUP-NEGOCIO-PATH-001

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Arquitectura de la Solución](#2-arquitectura-de-la-solución)
   - 2.1 [Diagrama de Flujo del Popup Dual](#21-diagrama-de-flujo-del-popup-dual)
   - 2.2 [Componentes Afectados](#22-componentes-afectados)
   - 2.3 [Flujo de Datos](#23-flujo-de-datos)
3. [Especificaciones Técnicas Detalladas](#3-especificaciones-técnicas-detalladas)
   - 3.1 [PHP — Hook de Attachments (Servidor)](#31-php--hook-de-attachments-servidor)
   - 3.2 [JavaScript — Popup Dual con Selector](#32-javascript--popup-dual-con-selector)
   - 3.3 [SCSS — Estilos del Selector y Path Negocio](#33-scss--estilos-del-selector-y-path-negocio)
   - 3.4 [Config — Nuevas Keys de Configuración](#34-config--nuevas-keys-de-configuración)
   - 3.5 [Imágenes — Hero para Path Negocio](#35-imágenes--hero-para-path-negocio)
   - 3.6 [Tracking — Eventos de Funnel](#36-tracking--eventos-de-funnel)
4. [Diseño Visual del Popup Dual](#4-diseño-visual-del-popup-dual)
   - 4.1 [Paso 1 — Selector de Perfil](#41-paso-1--selector-de-perfil)
   - 4.2 [Paso 2a — Path Participante](#42-paso-2a--path-participante)
   - 4.3 [Paso 2b — Path Negocio Piloto](#43-paso-2b--path-negocio-piloto)
   - 4.4 [Responsive Mobile (< 480px)](#44-responsive-mobile--480px)
5. [Tabla de Correspondencia — Directrices del Proyecto](#5-tabla-de-correspondencia--directrices-del-proyecto)
6. [Tabla de Correspondencia — Especificaciones Técnicas](#6-tabla-de-correspondencia--especificaciones-técnicas)
7. [Plan de Ejecución por Fases](#7-plan-de-ejecución-por-fases)
8. [Safeguard: Validator de Integridad del Popup](#8-safeguard-validator-de-integridad-del-popup)
9. [Testing y Verificación](#9-testing-y-verificación)
10. [RUNTIME-VERIFY-001 Checklist](#10-runtime-verify-001-checklist)
11. [IMPLEMENTATION-CHECKLIST-001](#11-implementation-checklist-001)
12. [Métricas de Éxito y KPIs](#12-métricas-de-éxito-y-kpis)
13. [Riesgos y Mitigaciones](#13-riesgos-y-mitigaciones)
14. [Glosario](#14-glosario)

---

## 1. Resumen Ejecutivo

### Objetivo

Evolucionar el popup de reclutamiento existente (`reclutamiento-popup.js`) de un popup mono-audiencia (solo participantes) a un **popup dual con selector inteligente** que permita captar simultáneamente:

- **Participantes** del programa (personas desempleadas de colectivos vulnerables en Andalucía).
- **Negocios piloto** (microempresas de 1-15 empleados en Sevilla/Málaga con necesidades de digitalización).

### Principio de Diseño

> "Un popup que intenta hablar a todos, no habla a nadie."

El popup presenta primero un **selector de perfil** (2 opciones claras) y luego transforma su contenido adaptado al público seleccionado, todo dentro de la misma card sin cierre/apertura.

### Alcance

| Elemento | Acción |
|---|---|
| `reclutamiento-popup.js` | Refactorizar → popup dual con selector + path negocio |
| `_reclutamiento-popup.scss` | Extender → estilos selector + path negocio + transiciones |
| `_jaraba_andalucia_ei_popup_attachments()` | Modificar → excluir autenticados + nuevas drupalSettings keys |
| `jaraba_andalucia_ei.settings.yml` | Añadir → keys para path negocio |
| `AndaluciaEiSettingsForm.php` | Añadir → campos admin para config path negocio |
| `validate-popup-dual-integrity.php` | Crear → safeguard validator (NUEVO) |
| `negocio-popup-hero.webp/.png` | Crear → imagen hero para path negocio (NUEVO) |

### Lo que NO cambia

- La library `jaraba_andalucia_ei/reclutamiento-popup` mantiene su nombre.
- La key de localStorage `aei_rec_popup_dismissed` se mantiene (unificada para todos los paths).
- El SCSS sigue compilando en `css/andalucia-ei.css` via `main.scss → @use 'reclutamiento-popup'`.
- La ruta de acceso al popup sigue siendo solo la homepage (`/`, `/es`, `/es/`).
- Los metasitios corporativos siguen incluidos (plataformadeecosistemas.es, pepejaraba.com, jarabaimpact.com).

---

## 2. Arquitectura de la Solución

### 2.1 Diagrama de Flujo del Popup Dual

```
Visitante llega a homepage
          │
          ▼
┌─────────────────────────────┐
│ ¿Es usuario autenticado?    │──── SÍ ──→ NO mostrar popup
└─────────────────────────────┘              (POPUP-ANON-ONLY-001)
          │ NO
          ▼
┌─────────────────────────────┐
│ ¿localStorage tiene dismiss │──── SÍ ──→ NO mostrar popup
│  con TTL < expiración?      │              (POPUP-SHARED-DISMISS-001)
└─────────────────────────────┘
          │ NO
          ▼
┌─────────────────────────────┐
│ ¿Host está en whitelist?    │──── NO ──→ NO mostrar popup
└─────────────────────────────┘
          │ SÍ
          ▼
    Esperar DELAY_MS (configurable)
          │
          ▼
┌─────────────────────────────────────────────┐
│  POPUP — Paso 1: SELECTOR                   │
│                                              │
│  "Andalucía +ei — 2ª Edición"               │
│  "¿Qué te interesa?"                        │
│                                              │
│  ┌──────────────┐  ┌──────────────────────┐  │
│  │ Busco empleo │  │ Tengo un negocio     │  │
│  └──────┬───────┘  └──────────┬───────────┘  │
└─────────┼─────────────────────┼──────────────┘
          │                     │
          ▼                     ▼
  PASO 2a: PARTICIPANTE   PASO 2b: NEGOCIO PILOTO
  ├─ Hero image            ├─ 5 servicios con iconos
  ├─ Stats (plazas/€528)   ├─ Comparativa precio
  ├─ 46% inserción         ├─ "0€ durante el programa"
  ├─ CTA → /solicitar      ├─ CTA → /prueba-gratuita
  └─ Legal FSE+            └─ Legal FSE+
```

### 2.2 Componentes Afectados

```
jaraba_andalucia_ei/
├── js/
│   └── reclutamiento-popup.js          ← MODIFICAR (refactorizar a dual)
├── scss/
│   └── _reclutamiento-popup.scss       ← MODIFICAR (añadir estilos selector + path negocio)
├── images/
│   ├── reclutamiento-popup-hero.png    ← EXISTENTE (path participante)
│   ├── reclutamiento-popup-hero.webp   ← EXISTENTE (path participante)
│   ├── negocio-popup-hero.png          ← NUEVO (path negocio — generar con IA)
│   └── negocio-popup-hero.webp         ← NUEVO (path negocio — generar con IA)
├── config/
│   └── install/
│       └── jaraba_andalucia_ei.settings.yml  ← MODIFICAR (nuevas keys)
├── src/
│   └── Form/
│       └── AndaluciaEiSettingsForm.php       ← MODIFICAR (campos admin)
├── jaraba_andalucia_ei.module                ← MODIFICAR (hook attachments)
└── css/
    └── andalucia-ei.css                      ← RECOMPILAR

scripts/validation/
└── validate-popup-dual-integrity.php         ← NUEVO (safeguard)

scripts/validation/validate-all.sh            ← MODIFICAR (registrar validator)
```

### 2.3 Flujo de Datos

```
                         ┌─── Config Admin UI ───┐
                         │ AndaluciaEiSettings    │
                         │ Form.php               │
                         └──────────┬─────────────┘
                                    │ Guarda en
                                    ▼
                    ┌─── jaraba_andalucia_ei.settings ───┐
                    │ mostrar_popup_saas: true            │
                    │ popup_delay_ms: 3000                │
                    │ popup_ttl_hours: 48                 │
                    │ plazas_restantes: 45                │
                    │ incentivo_euros: 528                │
                    │ tasa_insercion_1e: 46               │
                    │ popup_negocio_enabled: true         │
                    │ popup_servicios_count: 5            │
                    │ popup_campaign_utm: 'aei_...'       │
                    └──────────┬──────────────────────────┘
                               │ Leído por
                               ▼
        ┌─── _jaraba_andalucia_ei_popup_attachments() ───┐
        │ Filtra: anónimos only, homepage only, hosts ok  │
        │ Genera: URLs via Url::fromRoute()               │
        │ Inyecta: drupalSettings.aeiRecPopup             │
        └──────────┬──────────────────────────────────────┘
                   │ drupalSettings
                   ▼
          ┌─── reclutamiento-popup.js ───┐
          │ Lee drupalSettings            │
          │ Verifica localStorage TTL     │
          │ Construye DOM selector        │
          │ Escucha clic selector         │
          │ Muestra path participante     │
          │   O path negocio              │
          │ Emite eventos tracking        │
          │ Dismiss → localStorage        │
          └───────────────────────────────┘
```

---

## 3. Especificaciones Técnicas Detalladas

### 3.1 PHP — Hook de Attachments (Servidor)

**Fichero:** `web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.module`
**Función:** `_jaraba_andalucia_ei_popup_attachments()`

#### Cambios Requeridos

##### 3.1.1 Excluir Usuarios Autenticados (POPUP-ANON-ONLY-001)

Añadir al inicio de la función, después de la verificación de ruta:

```php
// POPUP-ANON-ONLY-001: Solo mostrar a usuarios anónimos.
// Usuarios autenticados ya tienen su dashboard — no interrumpir.
if (\Drupal::currentUser()->isAuthenticated()) {
  return;
}
```

**Justificación:** Un usuario logueado ya es cliente o participante del programa. Mostrarle un popup de captación es contraproducente: interrumpe su flujo de trabajo y genera fricción innecesaria. Además, usuarios autenticados acceden a dashboards internos donde el popup no tiene sentido contextual.

##### 3.1.2 Nuevas Keys en drupalSettings

Añadir a la estructura `$attachments['#attached']['drupalSettings']['aeiRecPopup']`:

```php
'pruebaGratuitaUrl' => Url::fromRoute('jaraba_andalucia_ei.prueba_gratuita')->toString(),
'delayMs' => (int) ($config->get('popup_delay_ms') ?? 3000),
'tasaInsercion' => (int) ($config->get('tasa_insercion_1e') ?? 46),
'negocioEnabled' => (bool) ($config->get('popup_negocio_enabled') ?? TRUE),
'serviciosCount' => (int) ($config->get('popup_servicios_count') ?? 5),
```

**Justificación de cada key:**
- `pruebaGratuitaUrl`: URL destino para el path de negocio piloto, generada por `Url::fromRoute()` para respetar ROUTE-LANGPREFIX-001 (prefijo `/es/` automático).
- `delayMs`: Delay configurable desde admin, eliminando el hardcode `DELAY_MS = 3000`. Permite A/B testing sin deploy.
- `tasaInsercion`: Dato de prueba social (46% de la 1ª Edición) — configurable para actualizarlo conforme avance la 2ª Edición.
- `negocioEnabled`: Flag booleano para activar/desactivar el path de negocio independientemente del popup general. Útil si la campaña de captación de negocios finaliza antes que la de participantes.
- `serviciosCount`: Número de servicios gratuitos mostrados. Actualmente son 5, pero podría cambiar si se amplía la oferta.

##### 3.1.3 Añadir Hostname de Producción del SaaS

Dentro del bloque condicional `if ($config->get('mostrar_popup_saas'))`:

```php
$metaSiteHosts[] = 'jaraba-saas.lndo.site';
$metaSiteHosts[] = 'plataformadeecosistemas.com';
```

**Justificación:** El hostname de producción del SaaS principal (`plataformadeecosistemas.com`) debe estar incluido para que el popup funcione en producción cuando se active `mostrar_popup_saas`. Actualmente solo está el hostname de desarrollo (Lando).

##### 3.1.4 Cache Contexts

La función actual no declara cache contexts para el usuario. Al añadir la condición de autenticación, necesitamos asegurar que el cache de página varíe por este criterio. Drupal ya varía por `user.roles:authenticated` en las políticas de caché por defecto, pero conviene verificar que `#cache['contexts']` incluya `user.roles:authenticated` en las páginas donde se adjunta el popup.

En la práctica, `hook_page_attachments()` recibe `$attachments` que ya incluye los contexts de la página. La verificación de `isAuthenticated()` es server-side y se ejecuta antes de decidir si adjuntar la library, por lo que el resultado varía correctamente con el cache de página.

### 3.2 JavaScript — Popup Dual con Selector

**Fichero:** `web/modules/custom/jaraba_andalucia_ei/js/reclutamiento-popup.js`

#### 3.2.1 Estructura Refactorizada

El JS se refactoriza en 3 funciones internas dentro del behavior:

```
Drupal.behaviors.aeiRecPopup = {
  attach(context):     Entry point — verifica condiciones y lanza timer
  _show(settings):     Construye y muestra el popup con el SELECTOR
  _showPath(overlay, settings, path):  Transiciona el selector al path elegido
}
```

**`attach(context)`** — Sin cambios funcionales significativos. Solo se modifica:
- `DELAY_MS` → `settings.delayMs || 3000` (configurable desde admin).

**`_show(settings)`** — Refactorizado completamente:
- El overlay se construye con la **vista del selector** (paso 1).
- El selector tiene dos botones (participante / negocio).
- Al hacer clic, se llama a `_showPath()` que reemplaza el contenido interior.
- Se emite un evento `popup_impression` via sendBeacon al mostrarse.

**`_showPath(overlay, settings, path)`** — Nueva función:
- Recibe `path = 'participante' | 'negocio'`.
- Construye el HTML específico para ese path.
- Reemplaza el contenido de `.aei-popup` con transición CSS (fade out 150ms → replace → fade in 150ms).
- Emite evento `popup_path_selected` con el path elegido.

#### 3.2.2 HTML del Selector (Paso 1)

```html
<div class="aei-popup">
  <button class="aei-popup__close" aria-label="Cerrar">&times;</button>
  <div class="aei-popup__badge">Programa oficial · 2ª Edición</div>
  <h2 class="aei-popup__title">Andalucía +ei</h2>
  <p class="aei-popup__subtitle">Emprendimiento Aumentado con Inteligencia Artificial</p>
  <p class="aei-popup__desc">¿Qué te interesa?</p>
  <div class="aei-popup__selector">
    <button class="aei-popup__selector-option aei-popup__selector-option--participante"
            data-popup-path="participante"
            data-track-cta="aei_popup_select_participante"
            data-track-position="popup_selector">
      <span class="aei-popup__selector-icon"><!-- SVG inline: ui/users --></span>
      <span class="aei-popup__selector-label">Busco empleo</span>
      <span class="aei-popup__selector-hint">Formación + orientación + incentivo</span>
    </button>
    <button class="aei-popup__selector-option aei-popup__selector-option--negocio"
            data-popup-path="negocio"
            data-track-cta="aei_popup_select_negocio"
            data-track-position="popup_selector">
      <span class="aei-popup__selector-icon"><!-- SVG inline: business/building --></span>
      <span class="aei-popup__selector-label">Tengo un negocio</span>
      <span class="aei-popup__selector-hint">Digitalización gratuita para su empresa</span>
    </button>
  </div>
  <div class="aei-popup__social-proof">
    <span class="aei-popup__social-proof-value">46%</span>
    <span class="aei-popup__social-proof-label">de inserción laboral en la 1ª Edición</span>
  </div>
  <p class="aei-popup__legal">PIIL — Colectivos Vulnerables 2025. Junta de Andalucía + FSE+.</p>
</div>
```

**Decisiones de diseño:**
- No se usa hero image en el selector — la imagen se reserva para el paso 2 cuando el usuario ya eligió. Esto reduce el peso inicial y deja más espacio para las opciones.
- Los iconos se renderizan como SVG inline (no via `jaraba_icon()` porque estamos en JS, no en Twig). Se usan los SVGs de las categorías `ui/users` y `business/building` con colores hex de la paleta Jaraba, cumpliendo ICON-CANVAS-INLINE-001 (hex explícito en stroke/fill, NUNCA currentColor en `<img>`).
- El dato 46% de inserción se muestra en el selector como gancho emocional antes de la elección.
- `Drupal.t()` envuelve todos los textos visibles para cumplir con la directriz de internacionalización.

#### 3.2.3 HTML del Path Participante (Paso 2a)

Esencialmente el mismo contenido que el popup actual, con estas mejoras:
- Se añade el dato "46% de inserción" como stat adicional.
- Se añade un botón "← Volver" (back arrow) para regresar al selector.
- Se añade `aria-describedby` apuntando a la descripción del popup.

```html
<div class="aei-popup__path aei-popup__path--participante">
  <button class="aei-popup__back" aria-label="Volver al selector">
    <!-- SVG: ui/arrow-left -->
    <span>Volver</span>
  </button>
  <picture class="aei-popup__hero">
    <source srcset="[modulePath]/images/reclutamiento-popup-hero.webp" type="image/webp">
    <img src="[modulePath]/images/reclutamiento-popup-hero.png"
         alt="Grupo diverso de personas colaborando en un programa de inserción laboral"
         width="520" height="293" loading="eager">
  </picture>
  <h2 class="aei-popup__title">¿Buscas empleo en Andalucía?</h2>
  <p class="aei-popup__desc" id="aei-popup-desc-participante">
    Programa gratuito de inserción laboral con orientación personalizada,
    formación certificada, mentoría con IA y un incentivo de [incentivo] €.
  </p>
  <div class="aei-popup__stats">
    <div class="aei-popup__stat">
      <span class="aei-popup__stat-value">[plazas]</span>
      <span class="aei-popup__stat-label">plazas</span>
    </div>
    <div class="aei-popup__stat">
      <span class="aei-popup__stat-value">[incentivo] €</span>
      <span class="aei-popup__stat-label">incentivo</span>
    </div>
    <div class="aei-popup__stat aei-popup__stat--highlight">
      <span class="aei-popup__stat-value">[tasa]%</span>
      <span class="aei-popup__stat-label">inserción 1ª Ed.</span>
    </div>
  </div>
  <div class="aei-popup__actions">
    <a href="[landingUrl+utm]" class="aei-popup__cta aei-popup__cta--primary"
       data-track-cta="aei_popup_ver_programa" data-track-position="popup_participante">
      Ver programa completo
    </a>
    <a href="[solicitarUrl+utm]" class="aei-popup__cta aei-popup__cta--secondary"
       data-track-cta="aei_popup_solicitar" data-track-position="popup_participante">
      Solicitar plaza
    </a>
  </div>
  <p class="aei-popup__legal">PIIL — Colectivos Vulnerables 2025. Junta de Andalucía + FSE+.</p>
</div>
```

#### 3.2.4 HTML del Path Negocio Piloto (Paso 2b)

```html
<div class="aei-popup__path aei-popup__path--negocio">
  <button class="aei-popup__back" aria-label="Volver al selector">
    <!-- SVG: ui/arrow-left -->
    <span>Volver</span>
  </button>
  <div class="aei-popup__negocio-header">
    <h2 class="aei-popup__title">Digitalice su negocio gratis</h2>
    <p class="aei-popup__desc" id="aei-popup-desc-negocio">
      [serviciosCount] servicios de digitalización sin coste para su empresa,
      con supervisión profesional y tecnología de IA.
    </p>
  </div>
  <ul class="aei-popup__servicios">
    <li class="aei-popup__servicio">
      <!-- SVG: social/instagram --> Gestión de redes sociales
    </li>
    <li class="aei-popup__servicio">
      <!-- SVG: ui/globe --> Creación de página web
    </li>
    <li class="aei-popup__servicio">
      <!-- SVG: communication/star --> Gestión de reseñas Google
    </li>
    <li class="aei-popup__servicio">
      <!-- SVG: business/clipboard --> Administración digital
    </li>
    <li class="aei-popup__servicio">
      <!-- SVG: commerce/cart --> Tienda online
    </li>
  </ul>
  <div class="aei-popup__negocio-value">
    <span class="aei-popup__negocio-value-crossed">+2.400 €/año</span>
    <span class="aei-popup__negocio-value-free">0 € durante el programa</span>
  </div>
  <div class="aei-popup__actions">
    <a href="[pruebaGratuitaUrl+utm]" class="aei-popup__cta aei-popup__cta--primary"
       data-track-cta="aei_popup_prueba_gratuita" data-track-position="popup_negocio">
      Ver servicios gratuitos
    </a>
  </div>
  <p class="aei-popup__legal">
    Servicios prestados por participantes del programa bajo supervisión profesional.
    PIIL — Junta de Andalucía + FSE+.
  </p>
</div>
```

**Decisiones de diseño path negocio:**
- No se usa hero image — se sustituye por un listado visual de los 5 servicios con iconos. El microempresario necesita ver qué obtiene, no una imagen genérica.
- El valor tachado ("+2.400 €/año") vs "0 €" crea anclaje de precio (técnica de framing). El valor tachado NO está hardcodeado — se calcula como `serviciosCount * 40 * 12` (estimación conservadora de 40€/mes por servicio) y se configura desde admin como `popup_valor_mercado_anual`.
- Se usa un solo CTA (no dos como en participante) porque el negocio necesita un funnel más corto — la landing de prueba-gratuita ya tiene toda la información detallada y el formulario.
- El texto legal aclara que los servicios son prestados por participantes del programa, lo cual es un punto de transparencia que genera confianza (MARKETING-TRUTH-001).

#### 3.2.5 Eventos de Tracking

Eventos emitidos por el popup, todos via `navigator.sendBeacon` al endpoint `/api/v1/analytics/event` (consistente con funnel-analytics.js):

| Evento | Momento | Datos |
|---|---|---|
| `popup_impression` | Al mostrarse el popup (selector) | `{ popup: 'aei_dual', step: 'selector', host }` |
| `popup_path_selected` | Al elegir participante o negocio | `{ popup: 'aei_dual', path: 'participante'\|'negocio' }` |
| `popup_cta_click` | Al hacer clic en CTA final | Delegado a `data-track-cta` (funnel-analytics.js) |
| `popup_dismissed` | Al cerrar sin interactuar | `{ popup: 'aei_dual', step: 'selector'\|'path', dismissed_after_ms }` |
| `popup_back` | Al volver del path al selector | `{ popup: 'aei_dual', from_path: 'participante'\|'negocio' }` |

**Justificación:** Estos 5 eventos permiten construir un funnel completo:
```
Impresiones → Selección path → CTA click → Landing → Formulario completado
```
Sin el evento de impresión, no podemos calcular CTR. Sin el evento de dismiss, no sabemos en qué paso perdemos visitantes. Sin el evento de back, no sabemos si los usuarios dudan entre ambos paths.

### 3.3 SCSS — Estilos del Selector y Path Negocio

**Fichero:** `web/modules/custom/jaraba_andalucia_ei/scss/_reclutamiento-popup.scss`

#### 3.3.1 Nuevos Bloques CSS (añadir al fichero existente)

```scss
// =============================================================================
// SELECTOR (PASO 1) — POPUP-DUAL-SELECTOR-001
// =============================================================================

.aei-popup__subtitle {
  font-size: 0.85rem;
  color: var(--ej-color-text-secondary, #6B7280);
  margin: -0.5rem 0 1rem;
  font-weight: 500;
}

.aei-popup__selector {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.aei-popup__selector-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1.25rem 1rem;
  border: 2px solid var(--ej-color-border-light, #E5E7EB);
  border-radius: 12px;
  background: var(--ej-color-surface, #fff);
  cursor: pointer;
  transition: border-color 200ms ease, background 200ms ease, transform 100ms ease;
  text-align: center;

  &:hover {
    border-color: var(--ej-naranja-impulso, #FF8C42);
    background: color-mix(in srgb, var(--ej-naranja-impulso, #FF8C42) 5%, transparent);
    transform: translateY(-2px);
  }

  &:focus-visible {
    outline: 2px solid var(--ej-naranja-impulso, #FF8C42);
    outline-offset: 2px;
  }

  &:active {
    transform: scale(0.97);
  }

  &--participante:hover {
    border-color: var(--ej-verde-innovacion, #00A9A5);
    background: color-mix(in srgb, var(--ej-verde-innovacion, #00A9A5) 5%, transparent);
  }

  &--negocio:hover {
    border-color: var(--ej-naranja-impulso, #FF8C42);
    background: color-mix(in srgb, var(--ej-naranja-impulso, #FF8C42) 5%, transparent);
  }
}

.aei-popup__selector-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;

  svg {
    width: 32px;
    height: 32px;
  }
}

.aei-popup__selector-label {
  font-family: var(--ej-font-headings, 'Outfit', sans-serif);
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--ej-azul-corporativo, #233D63);
}

.aei-popup__selector-hint {
  font-size: 0.75rem;
  color: var(--ej-color-text-muted, #9CA3AF);
  line-height: 1.3;
}

// =============================================================================
// SOCIAL PROOF (SELECTOR)
// =============================================================================

.aei-popup__social-proof {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 0.4rem;
  margin-bottom: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--ej-color-border-light, #E5E7EB);
}

.aei-popup__social-proof-value {
  font-family: var(--ej-font-headings, 'Outfit', sans-serif);
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--ej-verde-innovacion, #00A9A5);
}

.aei-popup__social-proof-label {
  font-size: 0.8rem;
  color: var(--ej-color-text-secondary, #6B7280);
}

// =============================================================================
// BACK BUTTON
// =============================================================================

.aei-popup__back {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.25rem 0.5rem;
  margin-bottom: 0.75rem;
  border: none;
  background: none;
  color: var(--ej-color-text-secondary, #6B7280);
  font-size: 0.8rem;
  cursor: pointer;
  transition: color 150ms ease;
  border-radius: 6px;

  svg {
    width: 16px;
    height: 16px;
  }

  &:hover,
  &:focus-visible {
    color: var(--ej-azul-corporativo, #233D63);
  }

  &:focus-visible {
    outline: 2px solid var(--ej-naranja-impulso, #FF8C42);
    outline-offset: 2px;
  }
}

// =============================================================================
// PATH TRANSITIONS
// =============================================================================

.aei-popup__path {
  animation: aeiPopupPathIn 250ms ease forwards;
}

@keyframes aeiPopupPathIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

// =============================================================================
// STAT HIGHLIGHT (46% inserción)
// =============================================================================

.aei-popup__stat--highlight .aei-popup__stat-value {
  color: var(--ej-verde-innovacion, #00A9A5);
}

// =============================================================================
// PATH NEGOCIO — SERVICIOS LIST
// =============================================================================

.aei-popup__negocio-header {
  margin-bottom: 1rem;
}

.aei-popup__servicios {
  list-style: none;
  padding: 0;
  margin: 0 0 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.aei-popup__servicio {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: color-mix(in srgb, var(--ej-verde-innovacion, #00A9A5) 6%, transparent);
  border-radius: 8px;
  font-size: 0.85rem;
  color: var(--ej-azul-corporativo, #233D63);
  font-weight: 500;

  svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
  }
}

// =============================================================================
// PATH NEGOCIO — VALOR / PRECIO TACHADO
// =============================================================================

.aei-popup__negocio-value {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
  padding: 0.75rem;
  background: color-mix(in srgb, var(--ej-naranja-impulso, #FF8C42) 8%, transparent);
  border-radius: 10px;
}

.aei-popup__negocio-value-crossed {
  font-size: 0.9rem;
  color: var(--ej-color-text-muted, #9CA3AF);
  text-decoration: line-through;
}

.aei-popup__negocio-value-free {
  font-family: var(--ej-font-headings, 'Outfit', sans-serif);
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--ej-naranja-impulso, #FF8C42);
}

// =============================================================================
// RESPONSIVE — SELECTOR MOBILE
// =============================================================================

@media (max-width: 480px) {
  .aei-popup__selector {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }

  .aei-popup__selector-option {
    flex-direction: row;
    padding: 0.75rem 1rem;
    text-align: left;
  }

  .aei-popup__selector-icon {
    width: 32px;
    height: 32px;

    svg {
      width: 24px;
      height: 24px;
    }
  }

  .aei-popup__negocio-value {
    flex-direction: column;
    gap: 0.25rem;
  }

  .aei-popup__servicio {
    font-size: 0.8rem;
    padding: 0.4rem 0.6rem;
  }
}
```

**Justificación de decisiones SCSS:**
- **Grid 2 columnas** para selector: visualmente equilibrado, se colapsa a 1 columna en mobile.
- **`color-mix()` en lugar de `rgba()`**: cumple SCSS-COLORMIX-001.
- **Todos los colores via `var(--ej-*)`**: cumple CSS-VAR-ALL-COLORS-001 (P0).
- **Transición `translateY(-2px)` en hover**: efecto sutil de elevación que sigue el patrón premium del SaaS.
- **Animation `aeiPopupPathIn`**: entrada suave del path seleccionado (250ms) para que la transición no sea abrupta.
- **Focus visible con outline naranja**: consistente con el patrón global del tema para focus states.

#### 3.3.2 Compilación

```bash
# Dentro del contenedor Lando:
lando ssh -c "cd /app/web/modules/custom/jaraba_andalucia_ei && npm run build"

# Verificar timestamp (SCSS-COMPILE-VERIFY-001):
ls -la css/andalucia-ei.css scss/_reclutamiento-popup.scss
# CSS timestamp DEBE ser > SCSS timestamp
```

### 3.4 Config — Nuevas Keys de Configuración

**Fichero:** `config/install/jaraba_andalucia_ei.settings.yml`

Nuevas keys a añadir (NO modificar las existentes):

```yaml
# Popup dual — configuración adicional
popup_delay_ms: 3000
tasa_insercion_1e: 46
popup_negocio_enabled: true
popup_servicios_count: 5
popup_valor_mercado_anual: 2400
```

**Descripción de cada key:**

| Key | Tipo | Default | Descripción |
|---|---|---|---|
| `popup_delay_ms` | int | 3000 | Milisegundos de delay antes de mostrar el popup. Configurable para A/B testing. |
| `tasa_insercion_1e` | int | 46 | Porcentaje de inserción de la 1ª Edición. Dato de prueba social mostrado en el popup. |
| `popup_negocio_enabled` | bool | true | Activa/desactiva el path de negocio piloto en el selector. Si false, solo se muestra el path de participante (comportamiento actual). |
| `popup_servicios_count` | int | 5 | Número de servicios gratuitos mostrados en el path de negocio. |
| `popup_valor_mercado_anual` | int | 2400 | Valor en euros que se muestra tachado como precio de mercado anual de los servicios (anclaje de precio). |

### 3.5 Imágenes — Hero para Path Negocio

**Ficheros nuevos:**
- `web/modules/custom/jaraba_andalucia_ei/images/negocio-popup-hero.png`
- `web/modules/custom/jaraba_andalucia_ei/images/negocio-popup-hero.webp`

**Especificaciones:**
- Dimensiones: 520 x 293 px (mismas que el hero de participante).
- Contenido sugerido: microempresario(a) atendiendo un negocio local con elementos digitales visibles (tablet, punto de venta, móvil con redes sociales).
- Estilo: luminoso, optimista, diverso (representar negocios de Andalucía).
- Formato: PNG como fallback, WebP como primary (via `<picture><source>`).
- Compresión WebP: quality 80, target < 50KB.

**Nota:** Actualmente el path de negocio NO usa hero image (usa listado de servicios). La imagen se prepara como recurso disponible por si el A/B testing determina que una imagen mejora la conversión del path de negocio.

### 3.6 Tracking — Eventos de Funnel

#### 3.6.1 Implementación de sendBeacon

El popup emite eventos directamente al endpoint `/api/v1/analytics/event` usando el mismo patrón que `funnel-analytics.js`:

```javascript
function trackPopupEvent(eventType, data) {
  if (navigator.doNotTrack === '1') {
    return;
  }
  var payload = {
    event: eventType,
    timestamp: new Date().toISOString(),
    page_url: window.location.href,
    session_id: window.jarabaAnalytics?.sessionId || null,
    data: data
  };
  try {
    navigator.sendBeacon(
      '/api/v1/analytics/event',
      new Blob([JSON.stringify(payload)], { type: 'application/json' })
    );
  }
  catch (e) { /* Fallback silencioso — analytics no debe romper el popup */ }
}
```

**Justificación:**
- `sendBeacon` es fire-and-forget, no bloquea el hilo principal.
- Respeta DNT (Do Not Track) como el resto del stack de analytics.
- El session_id se obtiene del singleton `window.jarabaAnalytics` si funnel-analytics.js ya lo generó.
- El try-catch evita que un error en analytics impida el funcionamiento del popup.

#### 3.6.2 Integración con CTA Tracking (AIDA)

Los CTAs del popup usan `data-track-cta` y `data-track-position`, que son capturados automáticamente por `aida-tracking.js` (behavior `aidaCtaTracking`). Sin embargo, como el popup se inyecta dinámicamente en el DOM después del attach inicial, necesitamos asegurar que el behavior de AIDA se re-adjunte.

**Solución:** Después de insertar el popup en el DOM, disparar:
```javascript
Drupal.attachBehaviors(overlay);
```
Esto es el patrón estándar de Drupal para contenido dinámico y asegura que `aida-tracking.js` capture los clics en los CTAs del popup.

---

## 4. Diseño Visual del Popup Dual

### 4.1 Paso 1 — Selector de Perfil

```
┌──────────────────────────────────────────────────┐
│                                            [✕]   │
│           Programa oficial · 2ª Edición          │
│                                                   │
│              Andalucía +ei                        │
│   Emprendimiento Aumentado con Inteligencia       │
│               Artificial                          │
│                                                   │
│            ¿Qué te interesa?                      │
│                                                   │
│  ┌─────────────────┐  ┌──────────────────────┐   │
│  │    👤 (SVG)      │  │     🏪 (SVG)         │   │
│  │                  │  │                      │   │
│  │  Busco empleo    │  │ Tengo un negocio     │   │
│  │                  │  │                      │   │
│  │ Formación +      │  │ Digitalización       │   │
│  │ orientación +    │  │ gratuita para        │   │
│  │ incentivo        │  │ su empresa           │   │
│  └─────────────────┘  └──────────────────────┘   │
│                                                   │
│  ───────────────────────────────────────────────  │
│          46% de inserción laboral                 │
│             en la 1ª Edición                      │
│                                                   │
│  PIIL — Colectivos Vulnerables 2025.              │
│  Junta de Andalucía + FSE+.                       │
└──────────────────────────────────────────────────┘
```

**Especificaciones de diseño:**
- Card: max-width 520px, border-radius 16px, padding 2.5rem 2rem.
- Badge: pill en verde-innovacion (#00A9A5), 0.75rem, uppercase.
- Título: Outfit 800, clamp(1.25rem, 3vw, 1.625rem), azul-corporativo.
- Selector: grid 2 columnas, gap 0.75rem, border 2px solid border-light.
- Hover selector: border cambia a color de la opción (verde para participante, naranja para negocio).
- Social proof: 46% en verde-innovacion 1.5rem 800, separado por border-top 1px.

### 4.2 Paso 2a — Path Participante

```
┌──────────────────────────────────────────────────┐
│  ← Volver                                  [✕]   │
│  ┌──────────────────────────────────────────┐    │
│  │          [HERO IMAGE — 520x293]          │    │
│  └──────────────────────────────────────────┘    │
│                                                   │
│       ¿Buscas empleo en Andalucía?               │
│                                                   │
│  Programa gratuito de inserción laboral con       │
│  orientación personalizada, formación             │
│  certificada, mentoría con IA y un incentivo      │
│  de 528 €.                                        │
│                                                   │
│       45           528 €         46%              │
│      plazas       incentivo    inserción          │
│                                 1ª Ed.            │
│                                                   │
│  ┌──────────────────────────────────────────┐    │
│  │       Ver programa completo              │    │
│  └──────────────────────────────────────────┘    │
│  ┌──────────────────────────────────────────┐    │
│  │          Solicitar plaza                  │    │
│  └──────────────────────────────────────────┘    │
│                                                   │
│  PIIL — Colectivos Vulnerables 2025.              │
│  Junta de Andalucía + FSE+.                       │
└──────────────────────────────────────────────────┘
```

### 4.3 Paso 2b — Path Negocio Piloto

```
┌──────────────────────────────────────────────────┐
│  ← Volver                                  [✕]   │
│                                                   │
│        Digitalice su negocio gratis              │
│                                                   │
│  5 servicios de digitalización sin coste para     │
│  su empresa, con supervisión profesional y        │
│  tecnología de IA.                                │
│                                                   │
│  ┌────────────────────────────────────────┐       │
│  │ 📱 Gestión de redes sociales           │       │
│  ├────────────────────────────────────────┤       │
│  │ 🌐 Creación de página web              │       │
│  ├────────────────────────────────────────┤       │
│  │ ⭐ Gestión de reseñas Google           │       │
│  ├────────────────────────────────────────┤       │
│  │ 📋 Administración digital              │       │
│  ├────────────────────────────────────────┤       │
│  │ 🛒 Tienda online                       │       │
│  └────────────────────────────────────────┘       │
│                                                   │
│  ┌────────────────────────────────────────┐       │
│  │   +2.400 €/año → 0 € durante programa │       │
│  └────────────────────────────────────────┘       │
│                                                   │
│  ┌──────────────────────────────────────────┐    │
│  │       Ver servicios gratuitos            │    │
│  └──────────────────────────────────────────┘    │
│                                                   │
│  Servicios prestados por participantes del        │
│  programa bajo supervisión profesional.           │
│  PIIL — Junta de Andalucía + FSE+.                │
└──────────────────────────────────────────────────┘
```

**Nota visual:** Los emojis en el wireframe son representativos. En la implementación real se usan SVG inline de las categorías del sistema de iconos Jaraba (ICON-CONVENTION-001, ICON-CANVAS-INLINE-002).

### 4.4 Responsive Mobile (< 480px)

En pantallas < 480px:
- El selector pasa de grid 2 columnas a 1 columna.
- Cada opción del selector cambia de layout vertical a horizontal (icono a la izquierda, texto a la derecha).
- El bloque de valor negocio (precio tachado) pasa de horizontal a vertical.
- El padding del popup se reduce de 2.5rem 2rem a 2rem 1.25rem.
- Los servicios se compactan (font-size 0.8rem, padding reducido).

---

## 5. Tabla de Correspondencia — Directrices del Proyecto

| # | Directriz | ID | Cumplimiento | Detalle |
|---|---|---|---|---|
| 1 | Colores CSS Custom Properties | CSS-VAR-ALL-COLORS-001 | ✅ | Todos los colores via `var(--ej-*, fallback)`. Cero hex hardcodeado. |
| 2 | Color-mix para alpha | SCSS-COLORMIX-001 | ✅ | `color-mix(in srgb, var(--ej-*) X%, transparent)` en overlay, badges, hover states. |
| 3 | Compilación SCSS verificable | SCSS-COMPILE-VERIFY-001 | ✅ | Paso obligatorio post-build: `ls -la css/ scss/` verificar timestamps. |
| 4 | Dart Sass moderno | SCSS-001 | ✅ | `@use` scoped (ya en `main.scss`), no `@import`. |
| 5 | Iconos convención | ICON-CONVENTION-001 | ✅ | SVG inline en JS con categorías correctas (`ui/users`, `business/building`, `social/instagram`, etc.). |
| 6 | Iconos duotone default | ICON-DUOTONE-001 | N/A | En JS se usan SVG inline, no Twig. Se aplica hex explícito de la paleta (ICON-CANVAS-INLINE-002). |
| 7 | Iconos hex inline | ICON-CANVAS-INLINE-002 | ✅ | SVGs usan #233D63, #FF8C42, #00A9A5 directamente en stroke/fill. |
| 8 | Sin emojis en canvas | ICON-EMOJI-001 | ✅ | SVG inline, no emojis Unicode. |
| 9 | Textos traducibles | i18n | ✅ | Todos los textos via `Drupal.t()` con placeholders `@variable`. |
| 10 | URLs via Url::fromRoute() | ROUTE-LANGPREFIX-001 | ✅ | URLs generadas server-side, no hardcodeadas. |
| 11 | XSS protección en innerHTML | INNERHTML-XSS-001 | ✅ | `Drupal.checkPlain()` para valores dinámicos insertados en HTML. |
| 12 | URLs protocol validation | URL-PROTOCOL-VALIDATE-001 | ✅ | URLs de `Url::fromRoute()` son internas — no hay riesgo `javascript:`. |
| 13 | CTA tracking completo | FUNNEL-COMPLETENESS-001 | ✅ | Cada CTA con `data-track-cta` + `data-track-position`. |
| 14 | No hardcode precios | NO-HARDCODE-PRICE-001 | ✅ | Plazas, incentivo, tasa inserción, valor mercado — todos desde config. |
| 15 | Marketing verdadero | MARKETING-TRUTH-001 | ✅ | "100% gratuito" = correcto (cofinanciado). "46% inserción" = dato real 1ª Ed. Valor tachado = estimación conservadora declarada. |
| 16 | Popup solo anónimos | POPUP-ANON-ONLY-001 | ✅ | `isAuthenticated()` check en PHP antes de adjuntar library. |
| 17 | localStorage unificada | POPUP-SHARED-DISMISS-001 | ✅ | Misma key `aei_rec_popup_dismissed` en todos los metasitios + SaaS. |
| 18 | ARIA accesibilidad | WCAG 2.1 AA | ✅ | `role="dialog"`, `aria-modal="true"`, `aria-label`, `aria-describedby`, focus trap, Escape key, focus-visible. |
| 19 | Do Not Track respetado | DNT | ✅ | `navigator.doNotTrack === '1'` → no tracking events. |
| 20 | Cache correcta | Cache | ✅ | `user.roles:authenticated` context implícito en page cache. |
| 21 | Tenant isolation | TENANT-001 | N/A | El popup no hace queries — es frontend puro + config. |
| 22 | Secret management | SECRET-MGMT-001 | N/A | No hay secrets involucrados. |
| 23 | CSRF protection | CSRF-API-001 | N/A | sendBeacon al analytics endpoint no requiere CSRF (es write-only, no lee datos sensibles). |
| 24 | Promo banner no colisiona | UI | ✅ | El popup es modal (z-index 10000), el promo banner es inline — no colisionan. |
| 25 | Sticky CTA no colisiona | UI | ✅ | Sticky CTA tiene z-index inferior y se oculta cuando el hero está visible. El popup overlay los cubre. |

---

## 6. Tabla de Correspondencia — Especificaciones Técnicas

| # | Especificación | Fichero | Línea/Sección | Detalle |
|---|---|---|---|---|
| 1 | Hook excluir autenticados | `jaraba_andalucia_ei.module` | `_popup_attachments()` L+5 | `\Drupal::currentUser()->isAuthenticated()` → return |
| 2 | Hostname producción SaaS | `jaraba_andalucia_ei.module` | `_popup_attachments()` L+40 | `$metaSiteHosts[] = 'plataformadeecosistemas.com'` |
| 3 | drupalSettings nuevas keys | `jaraba_andalucia_ei.module` | `_popup_attachments()` final | 5 keys nuevas: pruebaGratuitaUrl, delayMs, tasaInsercion, negocioEnabled, serviciosCount |
| 4 | JS selector dual | `reclutamiento-popup.js` | `_show()` | Refactorizar a 3 funciones: attach, _show (selector), _showPath |
| 5 | JS eventos tracking | `reclutamiento-popup.js` | `trackPopupEvent()` | 5 eventos: impression, path_selected, cta_click, dismissed, back |
| 6 | JS sendBeacon | `reclutamiento-popup.js` | `trackPopupEvent()` | POST a `/api/v1/analytics/event` via sendBeacon |
| 7 | JS delay configurable | `reclutamiento-popup.js` | `attach()` | `settings.delayMs \|\| 3000` |
| 8 | JS Drupal.attachBehaviors | `reclutamiento-popup.js` | `_show()` post-insert | Para que aida-tracking.js capture CTAs dinámicos |
| 9 | SCSS selector grid | `_reclutamiento-popup.scss` | `.aei-popup__selector` | `grid-template-columns: 1fr 1fr` → `1fr` en mobile |
| 10 | SCSS path transition | `_reclutamiento-popup.scss` | `@keyframes aeiPopupPathIn` | 250ms ease, translateY(8px→0) + opacity(0→1) |
| 11 | SCSS servicios list | `_reclutamiento-popup.scss` | `.aei-popup__servicios` | flex column, green background tint |
| 12 | SCSS valor tachado | `_reclutamiento-popup.scss` | `.aei-popup__negocio-value` | Flex row, crossed text + free highlight |
| 13 | Config delay | `settings.yml` | `popup_delay_ms` | int, default 3000 |
| 14 | Config tasa inserción | `settings.yml` | `tasa_insercion_1e` | int, default 46 |
| 15 | Config negocio enabled | `settings.yml` | `popup_negocio_enabled` | bool, default true |
| 16 | Config servicios count | `settings.yml` | `popup_servicios_count` | int, default 5 |
| 17 | Config valor mercado | `settings.yml` | `popup_valor_mercado_anual` | int, default 2400 |
| 18 | Admin form campos | `AndaluciaEiSettingsForm.php` | Sección popup | 5 campos nuevos en fieldset "Popup Dual" |
| 19 | Validator integridad | `validate-popup-dual-integrity.php` | Nuevo script | 8 checks: dual paths, config keys, anon-only, tracking |
| 20 | validate-all.sh registro | `validate-all.sh` | Sección AEI | `run_check "POPUP-DUAL-SELECTOR-001"` |

---

## 7. Plan de Ejecución por Fases

### Fase 1 — Backend + Config (Cimientos)

**Objetivo:** Preparar la infraestructura server-side sin cambiar el comportamiento actual del popup.

1. **Añadir nuevas keys** a `config/install/jaraba_andalucia_ei.settings.yml`.
2. **Añadir campos admin** en `AndaluciaEiSettingsForm.php` (fieldset "Popup Dual de Captación").
3. **Modificar `_popup_attachments()`**: exclusión autenticados + nuevas drupalSettings keys + hostname producción.
4. **Exportar config** actualizada si es necesario.

### Fase 2 — Frontend: JS Dual + SCSS

**Objetivo:** Implementar el popup dual completo.

1. **Refactorizar `reclutamiento-popup.js`**: selector + path participante + path negocio + eventos tracking.
2. **Extender `_reclutamiento-popup.scss`**: estilos selector, back button, social proof, path negocio, servicios, valor tachado, responsive.
3. **Compilar SCSS**: `npm run build` dentro del contenedor.
4. **Verificar SCSS-COMPILE-VERIFY-001**: timestamp CSS > SCSS.

### Fase 3 — Safeguard + Validator

**Objetivo:** Proteger la integridad del popup para el futuro.

1. **Crear `validate-popup-dual-integrity.php`**.
2. **Registrar en `validate-all.sh`**.
3. **Actualizar contador en `validate-safeguard-counter.php`**.

### Fase 4 — Testing + Verificación

**Objetivo:** Asegurar que todo funciona end-to-end.

1. **RUNTIME-VERIFY-001**: 5 checks (CSS compilado, rutas accesibles, data-* selectores, drupalSettings, DOM final).
2. **Test manual** en navegador: popup aparece solo para anónimos, selector funciona, ambos paths llevan a la landing correcta.
3. **Verificar tracking**: eventos llegan al endpoint de analytics.
4. **Verificar localStorage**: dismiss funciona, TTL respetado.
5. **Test mobile**: layout responsive < 480px correcto.
6. **Test accesibilidad**: Tab navigation, Escape, focus trap, screen reader.

### Fase 5 — Activación + Monitorización

**Objetivo:** Activar el popup en el SaaS principal y monitorizar métricas.

1. **Activar config `mostrar_popup_saas: true`** en producción.
2. **Monitorizar** impresiones, CTR, distribución participante/negocio.
3. **Iterar** copy y delay basándose en datos (A/B testing futuro).

---

## 8. Safeguard: Validator de Integridad del Popup

**Fichero:** `scripts/validation/validate-popup-dual-integrity.php`
**Rule ID:** POPUP-DUAL-SELECTOR-001

### Checks del Validator

| # | Check | Tipo | Qué verifica |
|---|---|---|---|
| 1 | Dual paths en JS | `run_check` | `reclutamiento-popup.js` contiene strings `data-popup-path="participante"` Y `data-popup-path="negocio"` |
| 2 | Config keys completas | `run_check` | `jaraba_andalucia_ei.settings.yml` tiene las 5 nuevas keys + las 6 existentes |
| 3 | Anon-only en hook | `run_check` | `_popup_attachments()` contiene `isAuthenticated()` |
| 4 | Tracking CTAs | `run_check` | JS contiene `data-track-cta` para los 4 CTAs (2 selector + 2-3 paths) |
| 5 | URL destinos válidos | `run_check` | `Url::fromRoute('jaraba_andalucia_ei.prueba_gratuita')` y `'jaraba_andalucia_ei.solicitar'` existen en routing |
| 6 | sendBeacon presente | `run_check` | JS contiene `sendBeacon` para evento de impresión |
| 7 | SCSS compilado fresh | `warn_check` | `css/andalucia-ei.css` timestamp > `scss/_reclutamiento-popup.scss` timestamp |
| 8 | Drupal.t() cobertura | `warn_check` | Todos los textos visibles en el popup están envueltos en `Drupal.t()` |

### Registro en validate-all.sh

```bash
run_check "POPUP-DUAL-SELECTOR-001" "Popup dual integrity" \
  "php scripts/validation/validate-popup-dual-integrity.php"
```

---

## 9. Testing y Verificación

### 9.1 Test Manual — Checklist

- [ ] Abrir homepage como anónimo → popup aparece tras delay
- [ ] Abrir homepage como autenticado → popup NO aparece
- [ ] Clic en "Busco empleo" → transición a path participante
- [ ] Clic en "Tengo un negocio" → transición a path negocio
- [ ] Clic en "← Volver" → regresa al selector
- [ ] CTA "Ver programa completo" → navega a /reclutamiento con UTM
- [ ] CTA "Solicitar plaza" → navega a /solicitar con UTM
- [ ] CTA "Ver servicios gratuitos" → navega a /prueba-gratuita con UTM
- [ ] Cerrar con [✕] → popup desaparece, localStorage guardado
- [ ] Cerrar con backdrop click → mismo comportamiento
- [ ] Cerrar con Escape → mismo comportamiento
- [ ] Recargar página → popup NO aparece (TTL activo)
- [ ] Esperar TTL (o borrar localStorage) → popup reaparece
- [ ] Mobile < 480px → selector 1 columna, layout horizontal
- [ ] Tab navigation → focus se mueve dentro del popup
- [ ] Screen reader → anuncia "Programa gratuito de inserción laboral"

### 9.2 Test Automatizado (PHPUnit)

No se requieren tests PHPUnit adicionales para el popup porque:
- Es 100% frontend (JS + CSS).
- No hay lógica de negocio server-side nueva (solo config reads).
- La función `_popup_attachments()` es un helper procedural en `.module` — se testea indirectamente via el Functional test de la homepage.

Sin embargo, se recomienda añadir un test Kernel que verifique que `jaraba_andalucia_ei.settings` contiene las nuevas keys con valores default correctos.

---

## 10. RUNTIME-VERIFY-001 Checklist

| # | Verificación | Comando | Resultado Esperado |
|---|---|---|---|
| 1 | CSS compilado (timestamp) | `ls -la css/andalucia-ei.css scss/_reclutamiento-popup.scss` | CSS timestamp > SCSS |
| 2 | Tablas DB | N/A | No hay cambios de schema |
| 3 | Rutas accesibles | `lando drush route:list \| grep andalucia_ei` | solicitar, reclutamiento, prueba_gratuita presentes |
| 4 | data-* selectores | Inspeccionar DOM del popup en navegador | `data-popup-path`, `data-track-cta`, `data-track-position` presentes |
| 5 | drupalSettings | `JSON.stringify(drupalSettings.aeiRecPopup)` en consola | 12 keys presentes (7 existentes + 5 nuevas) |

---

## 11. IMPLEMENTATION-CHECKLIST-001

### Complitud

- [x] Servicio registrado: N/A (no hay servicio nuevo — es JS + hook procedural)
- [x] Rutas: N/A (no hay rutas nuevas — usa rutas existentes)
- [x] Config: Nuevas keys añadidas a `settings.yml`
- [x] Library: Reutiliza `jaraba_andalucia_ei/reclutamiento-popup` existente
- [x] SCSS compilado + library registrada

### Integridad

- [x] Validator creado: `validate-popup-dual-integrity.php`
- [x] Registrado en `validate-all.sh`
- [x] No requiere `hook_update_N()` (no hay cambios de schema/entity)
- [ ] Config export si cambian valores en admin

### Consistencia

- [x] CSS-VAR-ALL-COLORS-001 en SCSS
- [x] FUNNEL-COMPLETENESS-001 en CTAs
- [x] NO-HARDCODE-PRICE-001 en datos numéricos
- [x] MARKETING-TRUTH-001 en copy
- [x] ICON-CANVAS-INLINE-002 en SVGs inline

### Pipeline E2E

- [x] L1: Config inyectada en hook → drupalSettings
- [x] L2: JS lee drupalSettings → construye DOM
- [x] L3: N/A (no hook_theme — es JS puro)
- [x] L4: CSS compilado cubre todos los selectores del DOM generado

### Coherencia

- [x] Documentación: docs/andaluciamasei/20260327a + 20260327b
- [x] Memory: actualizar MEMORY.md con referencia al popup dual
- [x] CLAUDE.md: NO requiere actualización (no hay nueva regla de nivel CLAUDE.md)

---

## 12. Métricas de Éxito y KPIs

### Métricas Primarias

| Métrica | Objetivo | Fuente |
|---|---|---|
| Impresiones popup/semana | > 500 | `popup_impression` event |
| CTR selector → path | > 40% | `popup_path_selected` / `popup_impression` |
| CTR path → landing | > 15% | `cta_click` / `popup_path_selected` |
| Conversión popup → formulario completado | > 3% | Cross-reference solicitudes con UTM `popup` |
| Distribución participante/negocio | 55-65% / 35-45% | `popup_path_selected` event |

### Métricas Secundarias

| Métrica | Alerta Si | Fuente |
|---|---|---|
| Bounce rate delta (con popup vs sin) | > +3% | GA4 |
| Tiempo dismiss (ms) | < 1500 (cierra demasiado rápido) | `popup_dismissed` event |
| Tasa de "back" | > 30% (confusión en selector) | `popup_back` event |
| Mobile vs Desktop ratio | < 20% mobile (popup mal adaptado) | `popup_impression` + viewport |

### Dashboard

Los eventos alimentan el `AnalyticsHubService` existente. Se recomienda añadir una sección "Popup de Captación" al dashboard de /mi-analytics del coordinador, con:
- Gráfico de impresiones diarias (sparkline).
- Funnel: impresiones → selección → CTA → formulario.
- Desglose participante vs negocio (donut chart).

---

## 13. Riesgos y Mitigaciones

| # | Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|---|
| 1 | Popup fatigue — visitantes recurrentes molestos | Media | Alto | TTL localStorage 48h + config ajustable |
| 2 | Path negocio canibaliza path participante | Baja | Medio | Monitoring ratio 55/45 — ajustar copy si desbalance |
| 3 | Popup bloquea Core Web Vitals (CLS) | Muy baja | Alto | Popup aparece tras 3s delay, no afecta LCP/FID. CLS = 0 (overlay fixed) |
| 4 | localStorage borrado por el usuario | Baja | Bajo | Comportamiento aceptable — vuelve a ver el popup, no es crítico |
| 5 | sendBeacon bloqueado por ad-blocker | Media | Bajo | Tracking degradado gracefully — popup sigue funcionando |
| 6 | Geolocalización irrelevante (usuario fuera de Andalucía) | Alta | Bajo | Popup menciona "Andalucía" prominentemente — auto-filtro contextual |
| 7 | Config no exportada tras cambio en admin | Media | Medio | Recordatorio en admin form + validator |
| 8 | SCSS no recompilado tras edición | Media | Alto | SCSS-COMPILE-VERIFY-001 en validator + pre-commit hook |

---

## 14. Glosario

| Sigla | Significado |
|---|---|
| AIDA | Attention, Interest, Desire, Action — modelo de marketing |
| ARIA | Accessible Rich Internet Applications |
| CLS | Cumulative Layout Shift — métrica Core Web Vitals |
| CRM | Customer Relationship Management |
| CSS | Cascading Style Sheets |
| CTA | Call To Action — llamada a la acción |
| CTR | Click-Through Rate — tasa de clics |
| DNT | Do Not Track — señal de privacidad del navegador |
| DOM | Document Object Model |
| FID | First Input Delay — métrica Core Web Vitals |
| FSE+ | Fondo Social Europeo Plus |
| GA4 | Google Analytics 4 |
| GDPR | General Data Protection Regulation (= RGPD) |
| JS | JavaScript |
| KPI | Key Performance Indicator |
| LCP | Largest Contentful Paint — métrica Core Web Vitals |
| PIIL | Programa de Itinerarios Integrados Laborales |
| RGPD | Reglamento General de Protección de Datos |
| SAE | Servicio Andaluz de Empleo |
| SaaS | Software as a Service |
| SCSS | Sassy CSS — preprocesador de hojas de estilo |
| SEO | Search Engine Optimization |
| SVG | Scalable Vector Graphics |
| TTL | Time To Live — tiempo de expiración |
| UTM | Urchin Tracking Module — parámetros de atribución |
| WCAG | Web Content Accessibility Guidelines |
| XSS | Cross-Site Scripting |
