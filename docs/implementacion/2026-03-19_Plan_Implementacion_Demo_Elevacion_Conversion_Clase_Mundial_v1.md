# Plan de Implementacion: Demo — Elevacion a 10/10 Conversion Clase Mundial

**Fecha de creacion:** 2026-03-19 10:00
**Ultima actualizacion:** 2026-03-19 11:30
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.1.0
**Categoria:** Implementacion
**Documentos fuente:**
- `docs/implementacion/2026-02-27_Plan_Implementacion_Demo_100_Clase_Mundial_v1.md` (S5-S8, 67 hallazgos tecnicos)
- `docs/implementacion/2026-02-27_Plan_Implementacion_Demo_100_Post_Verificacion_v1.md` (18 hallazgos v3)
- `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` (5 capas CSS tokens)
- `docs/arquitectura/2026-02-05_especificacion_grapesjs_saas.md` (Page Builder spec)
**Planes previos superados:** S5-S8 (tecnico) completados ~80-100%. Este plan aborda la **capa de negocio, conversion y experiencia de usuario**.
**Hallazgos nuevos:** 14 (3 criticos, 5 altos, 4 medios, 2 bajos)
**Objetivo:** Elevar la Demo de "tecnicamente correcta" a "clase mundial en conversion PLG"

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto Estrategico](#2-contexto-estrategico)
3. [Objetivos y Metricas de Exito](#3-objetivos-y-metricas-de-exito)
4. [Inventario de Hallazgos](#4-inventario-de-hallazgos)
5. [Sprint 9: Fundamentos de Conversion (P0)](#5-sprint-9-fundamentos-de-conversion-p0)
   - 5.1 [S9-01: Eliminar anglicismo "Time-to-Value"](#51-s9-01-eliminar-anglicismo-time-to-value)
   - 5.2 [S9-02: Migrar iconos SVG inline a jaraba_icon() en homepage](#52-s9-02-migrar-iconos-svg-inline)
   - 5.3 [S9-03: Fix URL hardcoded en _product-demo.html.twig](#53-s9-03-fix-url-hardcoded)
   - 5.4 [S9-04: Dual CTA en seccion "Mira la plataforma"](#54-s9-04-dual-cta-en-seccion-mira-la-plataforma)
   - 5.5 [S9-05: Reordenar perfiles demo por potencialidad de mercado](#55-s9-05-reordenar-perfiles-demo)
6. [Sprint 10: Soft Gate + CRM Pipeline (P0)](#6-sprint-10-soft-gate-crm-pipeline-p0)
   - 6.1 [S10-01: Formulario de captura de lead pre-demo](#61-s10-01-formulario-de-captura-de-lead-pre-demo)
   - 6.2 [S10-02: Integracion con jaraba_crm — crear Oportunidad automaticamente](#62-s10-02-integracion-con-jaraba_crm)
   - 6.3 [S10-03: Analytics de funnel demo → registro → conversion](#63-s10-03-analytics-de-funnel)
7. [Sprint 11: Setup Wizard + Daily Actions para Demo (P1)](#7-sprint-11-setup-wizard-daily-actions-p1)
   - 7.1 [S11-01: Crear DemoExplorarDashboardStep](#71-s11-01-crear-demoexplorardashboardstep)
   - 7.2 [S11-02: Crear DemoGenerarContenidoIAStep](#72-s11-02-crear-demogenerarcontenidoiastep)
   - 7.3 [S11-03: Crear DemoConvertirCuentaRealStep](#73-s11-03-crear-democonvertircuentarealstep)
   - 7.4 [S11-04: Crear ExplorarVerticalDemoAction (Daily Action)](#74-s11-04-crear-explorarverticaldemoadtion)
   - 7.5 [S11-05: Crear ChatCopilotDemoAction (Daily Action)](#75-s11-05-crear-chatcopilotdemoaction)
   - 7.6 [S11-06: Crear ConvertirCuentaDemoAction (Daily Action)](#76-s11-06-crear-convertircuentademoaccion)
   - 7.7 [S11-07: Inyectar wizard y daily actions en dashboard demo](#77-s11-07-inyectar-wizard-y-daily-actions)
8. [Sprint 12: Assets Visuales de Clase Mundial (P1)](#8-sprint-12-assets-visuales-p1)
   - 8.1 [S12-01: Generar imagenes de producto con Nano Banana](#81-s12-01-generar-imagenes-de-producto)
   - 8.2 [S12-02: Generar video showcase con Veo](#82-s12-02-generar-video-showcase)
   - 8.3 [S12-03: Integrar assets en templates demo y homepage](#83-s12-03-integrar-assets)
9. [Tabla de Correspondencia con Especificaciones Tecnicas](#9-tabla-de-correspondencia)
10. [Tabla de Cumplimiento con Directrices del Proyecto](#10-tabla-de-cumplimiento-directrices)
11. [Estructura de Archivos](#11-estructura-de-archivos)
12. [Verificacion y Testing](#12-verificacion-y-testing)
13. [Riesgos y Mitigaciones](#13-riesgos-y-mitigaciones)
14. [Registro de Cambios](#14-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Problema

Los planes previos (S5-S8, post-verificacion) elevaron la vertical Demo de ~60% a ~95% en calidad **tecnica** (seguridad, tests, i18n, arquitectura). Sin embargo, la evaluacion de conversion revela que la Demo aun no alcanza el nivel de clase mundial en su **funcion primaria: convertir visitantes en usuarios registrados**.

### 1.2 Diagnostico (19 marzo 2026)

Hallazgos criticos de conversion:

| Dimension | Score Actual | Target 10/10 | Gap |
|-----------|-------------|---------------|-----|
| Iconografia consistente (ICON-CONVENTION-001) | 85% | 100% | 3 SVGs inline en homepage |
| Lenguaje "Sin Humo" (filosofia proyecto) | 90% | 100% | "Time-to-Value" anglicismo |
| CRM tracking de leads demo | 0% | 100% | Demo publica sin captura |
| CTA especifico demo en homepage | 0% | 100% | Solo CTA generico "Pruebalo gratis" |
| Setup Wizard para demo | 0% | 100% | 0 wizard steps propios |
| Daily Actions para demo | 0% | 100% | 0 daily actions propias |
| Assets visuales de producto | 20% | 100% | Solo SVG placeholders con iniciales |
| Priorizacion por mercado | 50% | 100% | Orden alfabetico, no por conversion |
| Video showcase | 0% | 100% | Sin video de plataforma |

### 1.3 Estrategia

4 sprints de conversion (S9-S12) que complementan los 8 sprints tecnicos previos:

| Sprint | Foco | Items | Score objetivo |
|--------|------|-------|---------------|
| S9 | Fundamentos de Conversion | 5 | Quick wins: textos + iconos + CTA |
| S10 | Soft Gate + CRM Pipeline | 3 | Captura 100% de leads demo |
| S11 | Setup Wizard + Daily Actions | 7 | Patron premium completo |
| S12 | Assets Visuales Clase Mundial | 3 | Imagenes IA + video |

### 1.4 Filosofia

Este plan sigue la filosofia **"Sin Humo"** del proyecto:
- Textos en espanol claro, sin anglicismos tecnicos
- Conversion mediante valor demostrado, no agresividad comercial
- Soft gate (no hard gate) que respeta la experiencia del visitante
- Priorizar verticales por potencialidad real de mercado andaluz/espanol

---

## 2. Contexto Estrategico

### 2.1 Diferencia entre planes previos y este plan

| Aspecto | Planes S5-S8 | Este Plan S9-S12 |
|---------|-------------|-----------------|
| Foco | Tecnico (seguridad, tests, i18n) | Negocio (conversion, CRM, UX) |
| Audiencia | Desarrolladores | Usuarios finales + equipo comercial |
| Metricas | Code coverage, ARIA violations | TTFV, conversion rate, MQLs |
| Resultado | "El codigo existe" | "El usuario lo experimenta" |

### 2.2 Funnel de conversion actual vs. deseado

**Actual:**
```
Homepage → /demo (publica) → Dashboard → Conversion Modal → /user/register
                                                              ↓
                                                   (Lead NO capturado en CRM)
```

**Deseado (con soft gate):**
```
Homepage → CTA "Demo completa" → /demo (soft gate: nombre+email) → CRM Lead
                                                                     ↓
                                  Dashboard (con wizard+daily actions) → AI Storytelling
                                                                     ↓
                                  Conversion Modal → /registro/{vertical} (prefill)
                                                                     ↓
                                                   CRM Oportunidad (tracking completo)
```

### 2.3 Priorizacion de verticales por potencialidad de mercado

Analisis basado en: ticket medio, TAM espanol/andaluz, urgencia de digitalizacion, competencia.

| Posicion | Vertical | Perfil Demo | Justificacion |
|----------|----------|-------------|---------------|
| 1 | JarabaLex | Despacho de Abogados | Ticket medio alto (200-350 EUR/mes), 147K despachos en Espana, digitalizacion urgente |
| 2 | Emprendimiento | Startup / Emprendedor | Ticket medio-alto, ecosistema en expansion, alta viralidad |
| 3 | Formacion | Academia de Formacion | LMS en auge, ingresos recurrentes alumnos x cursos |
| 4 | ServiciosConecta | Profesional de Servicios | Alto volumen de profesionales autonomos en Andalucia |
| 5 | AgroConecta — Bodega | Bodega de Vinos | D.O. andaluzas, ticket medio, valor cultural |
| 6 | AgroConecta — Aceite | Productor de Aceite | Jaen = lider mundial, base instalada amplia |
| 7 | Empleabilidad | Buscador de Empleo | Alto volumen pero ticket bajo, freemium |
| 8 | ComercioConecta | Comprador | Ecosistema marketplace, conversion indirecta |
| 9 | Andalucia EI | Empresa de Impacto Social | Nicho institucional, funding publico |
| 10 | Content Hub | Creador de Contenido | Soporte transversal, conversion baja |
| 11 | AgroConecta — Queso | Queseria Artesanal | Nicho, pocas queserias vs. miles de olivareros |

---

## 3. Objetivos y Metricas de Exito

### 3.1 Metricas de conversion

| Metrica | Baseline (pre-S9) | Target post-S12 |
|---------|-------------------|-----------------|
| Leads capturados por demo | 0% (sin gate) | 80%+ (soft gate) |
| TTFV (mediana) | ~45s (estimado) | < 30s |
| Tasa Landing → Perfil seleccionado | Desconocida | > 60% |
| Tasa Demo → Formulario conversion | ~5% (estimado) | > 15% |
| Tasa Demo → Registro completado | ~2% (estimado) | > 8% |
| MQLs generados por demo/mes | 0 | > 50 |

### 3.2 Metricas tecnicas

| Metrica | Baseline | Target |
|---------|----------|--------|
| ICON-CONVENTION-001 compliance | 97% | 100% |
| ROUTE-LANGPREFIX-001 compliance | 99% | 100% |
| Setup Wizard steps (demo) | 0 | 3+ |
| Daily Actions (demo) | 0 | 3+ |
| Assets visuales reales | 0% (SVG placeholders) | 80%+ (imagenes IA) |
| Video showcase | 0 | 1 (30-60s) |

---

## 4. Inventario de Hallazgos

| ID | Severidad | Sprint | Titulo | Estado |
|----|-----------|--------|--------|--------|
| HAL-DEMO-CONV-001 | CRITICO | S9 | Anglicismo "Time-to-Value" visible al usuario | COMPLETADO |
| HAL-DEMO-CONV-002 | ALTO | S9 | 3 SVGs inline en homepage violan ICON-CONVENTION-001 | COMPLETADO |
| HAL-DEMO-CONV-003 | ALTO | S9 | URL hardcoded /user/register viola ROUTE-LANGPREFIX-001 | COMPLETADO |
| HAL-DEMO-CONV-004 | ALTO | S9 | Sin CTA de demo en seccion "Mira la plataforma" | COMPLETADO |
| HAL-DEMO-CONV-005 | MEDIO | S9 | Perfiles en orden alfabetico, no por conversion | COMPLETADO |
| HAL-DEMO-CONV-006 | CRITICO | S10 | Demo 100% publica sin captura de leads para CRM | COMPLETADO |
| HAL-DEMO-CONV-007 | ALTO | S10 | Sin integracion jaraba_crm para oportunidades | COMPLETADO |
| HAL-DEMO-CONV-008 | MEDIO | S10 | Analytics de funnel incompleto (falta landing views) | COMPLETADO |
| HAL-DEMO-CONV-009 | CRITICO | S11 | Vertical demo sin Setup Wizard (0 steps propios) | COMPLETADO |
| HAL-DEMO-CONV-010 | ALTO | S11 | Vertical demo sin Daily Actions (0 acciones propias) | COMPLETADO |
| HAL-DEMO-CONV-011 | MEDIO | S11 | Wizard no integrado en dashboard demo | COMPLETADO |
| HAL-DEMO-CONV-012 | MEDIO | S12 | Imagenes de producto son SVG placeholder con iniciales | COMPLETADO (5 WebP) |
| HAL-DEMO-CONV-013 | BAJO | S12 | Sin video showcase de la plataforma | COMPLETADO (Veo 2.0, 2.4MB) |
| HAL-DEMO-CONV-014 | BAJO | S12 | Assets no integrados en templates | COMPLETADO |

---

## 5. Sprint 9: Fundamentos de Conversion (P0)

**Prioridad:** P0 (inmediato)
**Hallazgos resueltos:** HAL-DEMO-CONV-001, 002, 003, 004, 005
**Estado parcial:** CONV-001 a 004 ya completados en esta sesion.

### 5.1 S9-01: Eliminar anglicismo "Time-to-Value"

**Hallazgo:** HAL-DEMO-CONV-001
**Archivos:** `web/modules/custom/ecosistema_jaraba_core/templates/demo-landing.html.twig`
**Directrices aplicables:** Filosofia "Sin Humo"

**Descripcion del problema:**
La expresion "Time-to-Value garantizado: < 60s" en la landing de demo es un anglicismo tecnico del mundo PLG (Product-Led Growth) que resulta incomprensible para los avatares objetivo de la plataforma: productores de aceite, abogados, emprendedores andaluces. La filosofia "Sin Humo" del proyecto exige comunicar en el idioma del usuario, no en jerga de consultoria.

**Implementacion:**
Reemplazar `{% trans %}Time-to-Value garantizado: <strong>&lt; 60s</strong>{% endtrans %}` por `{% trans %}Resultados en menos de <strong>60 segundos</strong>{% endtrans %}`.

Mantener referencia tecnica interna "TTFV" en comentarios PHP para el equipo de desarrollo.

**Criterio de aceptacion:**
- Texto "Time-to-Value" no aparece en ningun template Twig visible al usuario.
- Busqueda: `grep -rn "Time-to-Value" web/modules/custom/*/templates/` devuelve 0 resultados.
- Comentarios internos PHP pueden usar "TTFV" como referencia tecnica.

**Estado:** COMPLETADO (2026-03-19)

---

### 5.2 S9-02: Migrar iconos SVG inline a jaraba_icon() en homepage

**Hallazgo:** HAL-DEMO-CONV-002
**Archivos:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_product-demo.html.twig`
**Directrices aplicables:** ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001

**Descripcion del problema:**
La seccion "Mira la plataforma" del homepage usa 3 iconos SVG inline crudos para los tabs (Dashboard, Copiloto IA, Analytics). Esto viola ICON-CONVENTION-001 que exige `jaraba_icon()` para TODOS los iconos. Ademas, el tab "Analytics" es un anglicismo.

**Implementacion:**
Reemplazar los 3 bloques `<svg>...</svg>` por:
```twig
{{ jaraba_icon('dashboard', 'chart-bar', { size: '20px', variant: 'duotone', color: 'azul-corporativo' }) }}
{{ jaraba_icon('ai', 'brain', { size: '20px', variant: 'duotone', color: 'azul-corporativo' }) }}
{{ jaraba_icon('analytics', 'chart-line', { size: '20px', variant: 'duotone', color: 'azul-corporativo' }) }}
```

Traducir "Analytics" → "Analiticas".

**Nota tecnica:** El SVG del grafico de actividad (linea 93-97) se mantiene como inline porque es una visualizacion de datos, no un icono. Usa `var(--ej-color-primary)` correctamente.

**Criterio de aceptacion:**
- `grep -c "<svg" _product-demo.html.twig` devuelve 1 (solo el grafico de datos, no iconos).
- Los 3 tabs usan `jaraba_icon()` con variante `duotone`.
- Tab "Analytics" reemplazado por "Analiticas" con `{% trans %}`.

**Estado:** COMPLETADO (2026-03-19)

---

### 5.3 S9-03: Fix URL hardcoded en _product-demo.html.twig

**Hallazgo:** HAL-DEMO-CONV-003
**Archivos:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_product-demo.html.twig`
**Directrices aplicables:** ROUTE-LANGPREFIX-001

**Descripcion del problema:**
La linea `href="/user/register"` es una URL hardcoded que causa 404 en produccion (el sitio usa prefijo `/es/`). ROUTE-LANGPREFIX-001 exige SIEMPRE `path()` en Twig.

**Implementacion:**
Reemplazar `href="/user/register"` por `href="{{ path('user.register') }}"`.

**Criterio de aceptacion:**
- `grep -n '"/user/register"' _product-demo.html.twig` devuelve 0 resultados.
- CTA usa `path()` de Twig.

**Estado:** COMPLETADO (2026-03-19)

---

### 5.4 S9-04: Dual CTA en seccion "Mira la plataforma"

**Hallazgo:** HAL-DEMO-CONV-004
**Archivos:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_product-demo.html.twig`
**Directrices aplicables:** ROUTE-LANGPREFIX-001, data-track-cta para analytics

**Descripcion del problema:**
La seccion "Mira la plataforma" del homepage solo tenia un CTA generico "Pruebalo gratis" que enviaba al registro. Falta un CTA especifico que dirija a la demo completa, maximizando la conversion de visitantes que prefieren "ver antes de comprar".

**Implementacion:**
Reemplazar el CTA unico por dos CTAs complementarios:

```twig
<div class="product-demo__cta">
  <a href="{{ path('ecosistema_jaraba_core.demo_landing') }}"
     class="btn btn--primary btn--lg"
     data-track-cta="product_demo_full_demo">
    {% trans %}Accede a la demo completa{% endtrans %}
  </a>
  <a href="{{ path('user.register') }}"
     class="btn btn--outline btn--lg"
     data-track-cta="product_demo_register">
    {% trans %}Crea tu cuenta gratis{% endtrans %}
  </a>
</div>
```

**Logica de negocio:** El CTA primario (`.btn--primary`) dirige a la demo porque el visitante de homepage busca "ver" antes de comprometerse. El CTA secundario (`.btn--outline`) ofrece la via directa de registro para quienes ya estan convencidos.

**Criterio de aceptacion:**
- Seccion "Mira la plataforma" muestra 2 CTAs visibles.
- CTA primario dirige a `/demo` via `path()`.
- CTA secundario dirige a registro via `path()`.
- Ambos tienen `data-track-cta` para funnel-analytics.

**Estado:** COMPLETADO (2026-03-19)

---

### 5.5 S9-05: Reordenar perfiles demo por potencialidad de mercado

**Hallazgo:** HAL-DEMO-CONV-005
**Archivos:** `web/modules/custom/ecosistema_jaraba_core/src/Service/DemoInteractiveService.php`

**Descripcion del problema:**
Los 11 perfiles de demo estan ordenados internamente por vertical (agroconecta primero por orden alfabetico). Esto no refleja la priorizacion por potencialidad de mercado y conversion. JarabaLex (despachos de abogados) y Emprendimiento (startups) tienen mayor ticket medio y deberian aparecer primero.

**Implementacion:**
Reordenar la constante `DEMO_PROFILES` en `DemoInteractiveService.php` segun la tabla de priorizacion de la Seccion 2.3:

1. `lawfirm` (JarabaLex — ticket alto)
2. `startup` (Emprendimiento — viralidad alta)
3. `academy` (Formacion — ingresos recurrentes)
4. `servicepro` (ServiciosConecta — volumen autonomos)
5. `winery` (AgroConecta — D.O. andaluzas)
6. `producer` (AgroConecta — lider mundial aceite)
7. `jobseeker` (Empleabilidad — alto volumen)
8. `buyer` (ComercioConecta — marketplace)
9. `socialimpact` (Andalucia EI — nicho institucional)
10. `creator` (Content Hub — soporte transversal)
11. `cheese` (AgroConecta — nicho)

Tambien actualizar `getTranslatableStrings()` para mantener consistencia PO.

**Criterio de aceptacion:**
- `getDemoProfiles()` devuelve 'lawfirm' como primer perfil.
- La landing de demo muestra Despacho de Abogados en posicion superior-izquierda del grid.
- `getTranslatableStrings()` refleja el nuevo orden.

**Estado:** PENDIENTE

---

## 6. Sprint 10: Soft Gate + CRM Pipeline (P0)

**Prioridad:** P0 (inmediato)
**Hallazgos resueltos:** HAL-DEMO-CONV-006, 007, 008
**Dependencia:** Sprint 9 completado

### 6.1 S10-01: Formulario de captura de lead pre-demo

**Hallazgo:** HAL-DEMO-CONV-006
**Archivos nuevos:**
- `web/modules/custom/ecosistema_jaraba_core/templates/partials/_demo-lead-gate.html.twig`
- `web/modules/custom/ecosistema_jaraba_core/js/demo-lead-gate.js`
**Archivos modificados:**
- `web/modules/custom/ecosistema_jaraba_core/templates/demo-landing.html.twig`
- `web/modules/custom/ecosistema_jaraba_core/src/Controller/DemoController.php`
- `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml`
- `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.libraries.yml`
**Directrices aplicables:**
- CSRF-API-001, ROUTE-LANGPREFIX-001, SLIDE-PANEL-RENDER-001
- ICON-CONVENTION-001 (iconos en formulario)
- AI-GUARDRAILS-PII-001 (email es PII — almacenar hasheado en analytics)

**Descripcion detallada:**

La estrategia de **soft gate** captura leads sin bloquear la experiencia:

1. La landing `/demo` se mantiene publica (SEO + social proof)
2. Al hacer clic en "Probar Ahora" de un perfil, en lugar de navegar directamente a `/demo/start/{profileId}`, se muestra un **overlay modal minimo** con:
   - Campo "Tu nombre" (input text, requerido)
   - Campo "Tu email" (input email, requerido)
   - Checkbox "Acepto la politica de privacidad" (con link)
   - Boton "Acceder a la demo" (primario)
   - Link "Saltar, quiero explorar sin datos" (texto secundario, discreto)
3. Al enviar el formulario:
   - Se crea un lead en `jaraba_crm` (ver S10-02)
   - Se inicia la sesion demo normal (`generateDemoSession`)
   - Se almacena `lead_id` en la sesion demo para tracking posterior
4. Si el usuario elige "Saltar":
   - Se inicia la demo sin captura (como actualmente)
   - Se registra un evento `demo_gate_skipped` en analytics

**Implementacion tecnica del modal:**

El modal reutiliza el patron de `_demo-convert-modal.html.twig` (ya existente y ARIA-compliant):

```twig
{# _demo-lead-gate.html.twig #}
<div class="demo-lead-gate" role="dialog" aria-modal="true"
     aria-labelledby="demo-lead-gate-title" data-demo-lead-gate hidden>
  <div class="demo-lead-gate__backdrop" data-demo-lead-gate-close></div>
  <div class="demo-lead-gate__panel">
    <h2 id="demo-lead-gate-title" class="demo-lead-gate__title">
      {{ jaraba_icon('business', 'handshake', { size: '24px', variant: 'duotone', color: 'naranja-impulso' }) }}
      {% trans %}Un paso mas para acceder a tu demo{% endtrans %}
    </h2>
    <p class="demo-lead-gate__subtitle">
      {% trans %}Te enviaremos un resumen de tu experiencia al finalizar.{% endtrans %}
    </p>
    <form class="demo-lead-gate__form" data-demo-lead-form>
      <div class="demo-lead-gate__field">
        <label for="demo-lead-name">{% trans %}Tu nombre{% endtrans %}</label>
        <input type="text" id="demo-lead-name" name="name" required
               placeholder="{{ 'Ej: Maria Garcia'|t }}" autocomplete="name">
      </div>
      <div class="demo-lead-gate__field">
        <label for="demo-lead-email">{% trans %}Tu email profesional{% endtrans %}</label>
        <input type="email" id="demo-lead-email" name="email" required
               placeholder="{{ 'maria@tuempresa.com'|t }}" autocomplete="email">
      </div>
      <div class="demo-lead-gate__consent">
        <label>
          <input type="checkbox" name="privacy_consent" required>
          {% trans %}Acepto la <a href="{{ path('ecosistema_jaraba_core.static.privacidad') }}" target="_blank">politica de privacidad</a>{% endtrans %}
        </label>
      </div>
      <input type="hidden" name="profile_id" data-demo-lead-profile>
      <button type="submit" class="btn btn--primary btn--lg demo-lead-gate__submit">
        {% trans %}Acceder a la demo{% endtrans %}
      </button>
    </form>
    <button type="button" class="demo-lead-gate__skip" data-demo-lead-skip>
      {% trans %}Explorar sin datos de contacto{% endtrans %}
    </button>
  </div>
</div>
```

**JavaScript (`demo-lead-gate.js`):**
- Intercepta clics en `.demo-start-btn` con `preventDefault()`
- Abre modal con `profile_id` inyectado
- Al submit: `fetch('/api/v1/demo/lead-gate', { method: 'POST' })` con CSRF token
- Respuesta: `{ success: true, redirect_url: '/demo/start/{profileId}?lead_id={id}' }`
- Redirige al usuario a la demo con `lead_id` en query

**Nuevo endpoint API:**
```yaml
ecosistema_jaraba_core.demo_api_lead_gate:
  path: '/api/v1/demo/lead-gate'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\DemoController::leadGate'
  methods: [POST]
  requirements:
    _access: 'TRUE'
    _csrf_request_header_token: 'TRUE'
```

**Criterio de aceptacion:**
- Al clic en "Probar Ahora" se muestra modal de captura.
- Formulario valida nombre + email + consentimiento.
- Skip funciona y carga demo sin datos.
- Lead creado en jaraba_crm con vertical + perfil + email.
- CSRF token validado en POST.
- Modal cumple ARIA (role="dialog", aria-modal, focus trap).
- Email NO almacenado en texto plano en demo_sessions (GDPR).

---

### 6.2 S10-02: Integracion con jaraba_crm — crear Oportunidad automaticamente

**Hallazgo:** HAL-DEMO-CONV-007
**Archivos modificados:**
- `web/modules/custom/ecosistema_jaraba_core/src/Controller/DemoController.php`
- `web/modules/custom/ecosistema_jaraba_core/src/Service/DemoInteractiveService.php`
- `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml`
**Directrices aplicables:**
- OPTIONAL-CROSSMODULE-001 (`@?jaraba_crm.lead_service`)
- PRESAVE-RESILIENCE-001 (try-catch para servicio opcional)

**Descripcion detallada:**

Al capturar un lead en el soft gate (S10-01), crear automaticamente:
1. Un **Lead** en `jaraba_crm` con:
   - `name`: nombre del formulario
   - `email`: email del formulario
   - `source`: 'demo_interactive'
   - `vertical`: vertical del perfil seleccionado
   - `profile_id`: ID del perfil demo
   - `status`: 'new'
2. Una **Oportunidad** vinculada al lead con:
   - `stage`: 'demo_started'
   - `estimated_value`: segun vertical (de MetaSitePricingService)
   - `vertical`: vertical del perfil

Cuando el usuario convierte (usa el modal de conversion existente), actualizar la oportunidad a stage 'registered'.

**Inyeccion de dependencias:**
```yaml
# En ecosistema_jaraba_core.services.yml
ecosistema_jaraba_core.demo_interactive:
  class: ...
  arguments:
    # ... existing args ...
    - '@?jaraba_crm.lead_service'      # OPTIONAL-CROSSMODULE-001
    - '@?jaraba_crm.opportunity_service' # OPTIONAL-CROSSMODULE-001
```

**Criterio de aceptacion:**
- Lead creado en CRM al enviar formulario soft gate.
- Si jaraba_crm no esta instalado, demo funciona normalmente (servicio opcional).
- Oportunidad actualizada a 'registered' cuando usuario convierte.
- Panel de Oportunidades en CRM muestra leads de demo con fuente 'demo_interactive'.

---

### 6.3 S10-03: Analytics de funnel demo → registro → conversion

**Hallazgo:** HAL-DEMO-CONV-008
**Archivos modificados:**
- `web/modules/custom/ecosistema_jaraba_core/src/Event/DemoSessionEvent.php`
- `web/modules/custom/ecosistema_jaraba_core/src/EventSubscriber/DemoAnalyticsEventSubscriber.php`
**Directrices aplicables:** Tabla `demo_analytics` existente

**Descripcion detallada:**

Ampliar el evento `DemoSessionEvent` con nuevas etapas del funnel:
- `LANDING_VIEW`: visitante ve /demo (nuevo)
- `LEAD_CAPTURED`: formulario soft gate enviado (nuevo)
- `LEAD_SKIPPED`: usuario salto el gate (nuevo)

Actualizar `demo_analytics` para incluir nuevas columnas si necesario, o reutilizar `funnel_landing` y `funnel_profile_select` existentes.

**Criterio de aceptacion:**
- Tabla demo_analytics registra las 3 nuevas etapas.
- Dashboard admin puede consultar conversion por etapa.
- Funnel completo: landing → lead_captured → profile_select → dashboard_view → value_action → conversion.

---

## 7. Sprint 11: Setup Wizard + Daily Actions para Demo (P1)

**Prioridad:** P1 (alto)
**Hallazgos resueltos:** HAL-DEMO-CONV-009, 010, 011
**Directrices aplicables:** SETUP-WIZARD-DAILY-001, ZEIGARNIK-PRELOAD-001

**Contexto:**
El vertical demo es el UNICO de los 10 verticales canonicos que no tiene wizard steps ni daily actions propios. Esto viola SETUP-WIZARD-DAILY-001. Los pasos globales (`__global__`) se inyectan pero no hay pasos especificos de demo que guien al usuario hacia la conversion.

### 7.1 S11-01: Crear DemoExplorarDashboardStep

**Archivo nuevo:** `web/modules/custom/ecosistema_jaraba_core/src/SetupWizard/DemoExplorarDashboardStep.php`

**Contrato SetupWizardStepInterface:**
```php
getId(): 'demo_visitor.explorar_dashboard'
getWizardId(): 'demo_visitor'
getLabel(): t('Explora tu dashboard')
getDescription(): t('Visualiza las metricas de tu negocio simulado')
getWeight(): 10
getIcon(): ['dashboard', 'chart-bar', 'duotone']
getRoute(): 'ecosistema_jaraba_core.demo_dashboard'
getRouteParameters(): ['sessionId' => $currentSessionId]
useSlidePanel(): FALSE
isComplete($tenantId): // TRUE si accion 'view_dashboard' existe en sesion
isOptional(): FALSE
```

**Registro en services.yml:**
```yaml
ecosistema_jaraba_core.setup_wizard.demo_explorar_dashboard:
  class: Drupal\ecosistema_jaraba_core\SetupWizard\DemoExplorarDashboardStep
  arguments:
    - '@ecosistema_jaraba_core.demo_interactive'
    - '@request_stack'
  tags:
    - { name: ecosistema_jaraba_core.setup_wizard_step }
```

---

### 7.2 S11-02: Crear DemoGenerarContenidoIAStep

**Archivo nuevo:** `web/modules/custom/ecosistema_jaraba_core/src/SetupWizard/DemoGenerarContenidoIAStep.php`

```php
getId(): 'demo_visitor.generar_contenido_ia'
getWizardId(): 'demo_visitor'
getLabel(): t('Genera contenido con IA')
getDescription(): t('Descubre como la IA crea contenido para tu negocio')
getWeight(): 20
getIcon(): ['ai', 'sparkles', 'duotone']
getRoute(): 'ecosistema_jaraba_core.demo_storytelling'
isComplete($tenantId): // TRUE si accion 'generate_story' existe en sesion
```

---

### 7.3 S11-03: Crear DemoConvertirCuentaRealStep

**Archivo nuevo:** `web/modules/custom/ecosistema_jaraba_core/src/SetupWizard/DemoConvertirCuentaRealStep.php`

```php
getId(): 'demo_visitor.convertir_cuenta'
getWizardId(): 'demo_visitor'
getLabel(): t('Crea tu cuenta real')
getDescription(): t('Convierte tu demo en una cuenta con todas las funcionalidades')
getWeight(): 30
getIcon(): ['business', 'achievement', 'duotone']
getRoute(): 'ecosistema_jaraba_core.onboarding.register'
getRouteParameters(): ['vertical' => $profileVertical]
isComplete($tenantId): // FALSE siempre (objetivo final)
```

**Nota ZEIGARNIK-PRELOAD-001:** Con los 2 global steps (+2) y el primer demo step completable inmediatamente (view_dashboard), el wizard arranca al 60% (3 de 5 pasos). El efecto Zeigarnik es potente: "solo faltan 2 pasos para completar tu configuracion".

---

### 7.4 S11-04: Crear ExplorarVerticalDemoAction (Daily Action)

**Archivo nuevo:** `web/modules/custom/ecosistema_jaraba_core/src/DailyActions/ExplorarVerticalDemoAction.php`

```php
getId(): 'demo_visitor.explorar_vertical'
getDashboardId(): 'demo_visitor'
getLabel(): t('Explorar tu vertical')
getDescription(): t('Descubre las herramientas de tu sector')
getIcon(): ['verticals', $verticalIcon, 'duotone']
getColor(): 'azul-corporativo'
getRoute(): 'ecosistema_jaraba_core.demo_dashboard'
getWeight(): 10
isPrimary(): TRUE
getContext($tenantId): ['badge' => $actionsRemaining, 'badge_type' => 'info', 'visible' => TRUE]
```

---

### 7.5 S11-05: Crear ChatCopilotDemoAction (Daily Action)

**Archivo nuevo:** `web/modules/custom/ecosistema_jaraba_core/src/DailyActions/ChatCopilotDemoAction.php`

```php
getId(): 'demo_visitor.chat_copilot'
getDashboardId(): 'demo_visitor'
getLabel(): t('Hablar con el Copiloto IA')
getDescription(): t('Preguntale lo que necesites sobre tu negocio')
getIcon(): ['ai', 'brain', 'duotone']
getColor(): 'naranja-impulso'
getRoute(): 'ecosistema_jaraba_core.demo_ai_playground'
getWeight(): 20
isPrimary(): FALSE
```

---

### 7.6 S11-06: Crear ConvertirCuentaDemoAction (Daily Action)

**Archivo nuevo:** `web/modules/custom/ecosistema_jaraba_core/src/DailyActions/ConvertirCuentaDemoAction.php`

```php
getId(): 'demo_visitor.convertir_cuenta'
getDashboardId(): 'demo_visitor'
getLabel(): t('Crear mi cuenta real')
getDescription(): t('Activa todas las funcionalidades con tu propia cuenta')
getIcon(): ['business', 'achievement', 'duotone']
getColor(): 'verde-innovacion'
getRoute(): 'ecosistema_jaraba_core.onboarding.register'
getWeight(): 30
isPrimary(): FALSE
getContext($tenantId): ['badge' => '!', 'badge_type' => 'warning', 'visible' => TRUE]
```

---

### 7.7 S11-07: Inyectar wizard y daily actions en dashboard demo

**Archivos modificados:**
- `web/modules/custom/ecosistema_jaraba_core/src/Controller/DemoController.php` (metodos `startDemo`, `demoDashboard`)
- `web/modules/custom/ecosistema_jaraba_core/templates/demo-dashboard.html.twig`
- `web/modules/custom/ecosistema_jaraba_core/templates/demo-dashboard-view.html.twig`
- `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module` (`hook_theme` — anadir variables wizard/daily_actions)

**Implementacion:**
1. En `DemoController::startDemo()` y `demoDashboard()`:
   - Obtener wizard via `SetupWizardRegistry::getStepsForWizard('demo_visitor', 0)`
   - Obtener daily actions via `DailyActionsRegistry::getActionsForDashboard('demo_visitor', 0)`
   - Pasar al render array: `#wizard` y `#daily_actions`

2. En `hook_theme()` de `ecosistema_jaraba_core.module`:
   - Anadir variables `wizard` y `daily_actions` a `demo_dashboard` y `demo_dashboard_view`

3. En templates dashboard:
   - Incluir parciales existentes:
   ```twig
   {% if wizard %}
     {% include '@ecosistema_jaraba_theme/partials/_setup-wizard.html.twig' with {
       wizard: wizard,
       wizard_title: 'Completa tu demo'|t,
       wizard_subtitle: 'Descubre todo el potencial de la plataforma'|t,
     } only %}
   {% endif %}

   {% if daily_actions %}
     {% include '@ecosistema_jaraba_theme/partials/_daily-actions.html.twig' with {
       daily_actions: daily_actions,
       actions_title: 'Acciones rapidas'|t,
     } only %}
   {% endif %}
   ```

**Nota PIPELINE-E2E-001:** Verificar las 4 capas:
- L1: `SetupWizardRegistry` inyectado en `DemoController` (constructor + create())
- L2: Controller pasa `#wizard` al render array
- L3: `hook_theme()` declara variable 'wizard' en array 'variables'
- L4: Template incluye parcial `_setup-wizard.html.twig` con `only`

**Criterio de aceptacion:**
- Dashboard demo muestra Setup Wizard con 5 steps (2 global + 3 demo).
- Progreso empieza en ~40% (efecto Zeigarnik).
- Daily Actions grid muestra 3 acciones con icono duotone.
- Al completar acciones, wizard se actualiza en tiempo real.

---

## 8. Sprint 12: Assets Visuales de Clase Mundial (P1)

**Prioridad:** P1 (alto)
**Hallazgos resueltos:** HAL-DEMO-CONV-012, 013, 014

### 8.1 S12-01: Generar imagenes de producto con Nano Banana

**Hallazgo:** HAL-DEMO-CONV-012
**Herramienta:** Google Nano Banana (MCP tool `mcp__nano-banana__generate_image`)
**Archivos destino:** `web/themes/custom/ecosistema_jaraba_theme/images/demo/`

**Imagenes a generar (priorizadas por perfil de mayor conversion):**

| Perfil | Imagen | Prompt sugerido | Formato |
|--------|--------|----------------|---------|
| lawfirm | Despacho moderno | "Modern Spanish law office interior, warm wood desk, legal books, laptop showing analytics dashboard, professional lighting, 4:3" | WebP 800x600 |
| startup | Espacio coworking | "Bright coworking space in Malaga Spain, startup team working, modern tech setup, Mediterranean light, 4:3" | WebP 800x600 |
| academy | Aula virtual | "Online learning platform on laptop, student at home desk, course progress indicators, warm lighting, 4:3" | WebP 800x600 |
| producer | Olivar andaluz | "Andalusian olive oil production, premium extra virgin olive oil bottles, olive grove background, golden hour, 4:3" | WebP 800x600 |
| winery | Bodega andaluza | "Spanish wine cellar with oak barrels, wine tasting setup, rustic elegant, warm ambient lighting, 4:3" | WebP 800x600 |

**Criterio de aceptacion:**
- Minimo 5 imagenes generadas y almacenadas en `images/demo/`.
- Formato WebP para rendimiento (< 100KB cada una).
- Referencian colores de marca Jaraba (tonos calidos, mediterraneos).
- Reemplazan SVG placeholders en `getPlaceholderSvg()` para perfiles prioritarios.

---

### 8.2 S12-02: Generar video showcase con Veo

**Hallazgo:** HAL-DEMO-CONV-013
**Herramienta:** Google Veo (MCP tool `mcp__veo__generate_video`)
**Archivo destino:** `web/themes/custom/ecosistema_jaraba_theme/videos/demo-showcase.mp4`

**Concepto:** Video de 30-60 segundos que muestra:
1. Landing de la plataforma → transicion suave
2. Dashboard con metricas animadas → zoom a grafico
3. Copiloto IA respondiendo → typing animation
4. Marketplace con productos → scroll
5. CTA "Crea tu cuenta gratis"

**Prompt sugerido:** "Professional SaaS platform showcase video, clean UI dashboard with analytics charts animating, AI chatbot responding, product marketplace scrolling, warm Mediterranean color palette (#233D63 blue, #FF8C42 orange, #00A9A5 teal), modern typography, 16:9, 30fps, smooth transitions"

**Criterio de aceptacion:**
- Video de 30-60s en formato MP4 (H.264, < 5MB).
- Muestra al menos 3 de las 5 funcionalidades clave.
- Colores alineados con paleta Jaraba.
- Integrado en `_product-demo.html.twig` como alternativa/complemento al mockup estatico.

---

### 8.3 S12-03: Integrar assets en templates demo y homepage

**Hallazgo:** HAL-DEMO-CONV-014
**Archivos modificados:**
- `_product-demo.html.twig` (homepage)
- `demo-landing.html.twig` (landing de demo)
- `DemoInteractiveService.php` (reemplazar getPlaceholderSvg para perfiles con imagen real)

**Implementacion:**
1. En `_product-demo.html.twig`: anadir video showcase como fondo/overlay del mockup browser (progressive enhancement — video si disponible, SVG mockup como fallback).
2. En `demo-landing.html.twig`: iconos de perfil con imagen de fondo si existe asset.
3. En `DemoInteractiveService::getPlaceholderSvg()`: verificar si existe imagen real en `images/demo/{profile}.webp` y retornar esa URL en vez del SVG generado.

---

## 9. Tabla de Correspondencia con Especificaciones Tecnicas

| Especificacion | Descripcion | Sprints Aplicables |
|----------------|-------------|-------------------|
| ICON-CONVENTION-001 | Usar `jaraba_icon()` para todos los iconos | S9 (tabs homepage), S11 (wizard/daily icons) |
| ICON-DUOTONE-001 | Variante duotone por defecto | S9, S11, S12 |
| ICON-COLOR-001 | Solo colores de paleta Jaraba | S9, S11 |
| ROUTE-LANGPREFIX-001 | URLs via `path()` / `Url::fromRoute()` | S9 (CTA fix), S10 (lead gate API) |
| CSS-VAR-ALL-COLORS-001 | Colores via `var(--ej-*)` | S10 (modal lead gate SCSS), S11 (wizard) |
| SETUP-WIZARD-DAILY-001 | Patron premium wizard + daily actions | S11 (completo) |
| ZEIGARNIK-PRELOAD-001 | Pre-carga de progreso 25-50% | S11 (2 global + 1 auto = ~60%) |
| PIPELINE-E2E-001 | Verificar 4 capas L1-L4 | S11 (wizard en dashboard) |
| OPTIONAL-CROSSMODULE-001 | `@?` para dependencias cross-modulo | S10 (jaraba_crm servicios) |
| PRESAVE-RESILIENCE-001 | try-catch para servicios opcionales | S10 (CRM integration) |
| CSRF-API-001 | `_csrf_request_header_token: 'TRUE'` en POST | S10 (lead-gate endpoint) |
| AI-GUARDRAILS-PII-001 | Email = PII, hash en analytics | S10 (soft gate) |
| TENANT-001 | Filtrar por tenant en queries | S11 (wizard completion queries) |
| PREMIUM-FORMS-PATTERN-001 | Entity forms extienden PremiumEntityFormBase | N/A (no hay entity forms nuevas) |
| ENTITY-PREPROCESS-001 | preprocess para entities con view mode | N/A |
| SLIDE-PANEL-RENDER-001 | renderPlain() en slide panels | S11 (si wizard usa slide panel) |
| FORM-CACHE-001 | No setCached(TRUE) incondicional | S10 (lead gate form) |
| SCSS-COMPILETIME-001 | Hex estatico en color.scale/adjust | S10 (modal SCSS) |
| SCSS-COLORMIX-001 | color-mix() para runtime alpha | S10 (backdrop overlay) |
| TWIG-INCLUDE-ONLY-001 | `only` en includes de parciales | S11 (wizard/daily includes) |
| INNERHTML-XSS-001 | Drupal.checkPlain() para datos API | S10 (lead gate JS) |
| CSRF-JS-CACHE-001 | Token /session/token cacheado en variable | S10 (lead gate JS) |

---

## 10. Tabla de Cumplimiento con Directrices del Proyecto

| Directriz | Cumplimiento | Notas |
|-----------|-------------|-------|
| Filosofia "Sin Humo" | SI | "Time-to-Value" eliminado, textos en espanol claro |
| VERTICAL-CANONICAL-001 | SI | Demo es 1 de 10 verticales canonicos |
| Multi-tenancy (TENANT-001) | SI | Wizard queries filtran por tenantId |
| Prefijo jaraba_* | SI | Todos servicios en ecosistema_jaraba_core |
| PHP 8.4 strict_types | SI | Todos archivos nuevos |
| Dart Sass moderno | SI | @use (no @import), color-mix() |
| i18n {% trans %} bloque | SI | Todos textos en templates |
| Variables inyectables UI | SI | CSS via --ej-* tokens |
| Templates limpias sin regiones | SI | page--demo.html.twig zero-region |
| Modales/slide-panel | SI | Lead gate en modal, acciones en slide-panel |
| Mobile-first | SI | Grid responsive, min-width breakpoints |
| hook_preprocess_html() | SI | Body classes via hook, no attributes.addClass() |
| Field UI + Views integration | N/A | No hay entities nuevas en este plan |
| /admin/structure + /admin/content | N/A | No hay entities nuevas |
| Ejecucion en contenedor Docker | SI | Comandos via `lando` |

---

## 11. Estructura de Archivos

### 11.1 Archivos nuevos

```
web/modules/custom/ecosistema_jaraba_core/
├── src/
│   ├── SetupWizard/
│   │   ├── DemoExplorarDashboardStep.php        (S11-01)
│   │   ├── DemoGenerarContenidoIAStep.php        (S11-02)
│   │   └── DemoConvertirCuentaRealStep.php       (S11-03)
│   └── DailyActions/
│       ├── ExplorarVerticalDemoAction.php         (S11-04)
│       ├── ChatCopilotDemoAction.php              (S11-05)
│       └── ConvertirCuentaDemoAction.php          (S11-06)
├── templates/
│   └── partials/
│       └── _demo-lead-gate.html.twig             (S10-01)
└── js/
    └── demo-lead-gate.js                         (S10-01)

web/themes/custom/ecosistema_jaraba_theme/
├── images/
│   └── demo/
│       ├── lawfirm.webp                          (S12-01)
│       ├── startup.webp                          (S12-01)
│       ├── academy.webp                          (S12-01)
│       ├── producer.webp                         (S12-01)
│       └── winery.webp                           (S12-01)
└── videos/
    └── demo-showcase.mp4                         (S12-02)
```

### 11.2 Archivos modificados

| Archivo | Sprint | Cambio |
|---------|--------|--------|
| `demo-landing.html.twig` | S9, S10 | Texto TTFV + include lead gate |
| `_product-demo.html.twig` | S9, S12 | Iconos + CTA + video |
| `DemoInteractiveService.php` | S9, S12 | Orden perfiles + images |
| `DemoController.php` | S10, S11 | Lead gate + wizard injection |
| `ecosistema_jaraba_core.routing.yml` | S10 | Nuevo endpoint lead-gate |
| `ecosistema_jaraba_core.services.yml` | S10, S11 | CRM deps + wizard/daily tags |
| `ecosistema_jaraba_core.libraries.yml` | S10 | Library demo-lead-gate |
| `ecosistema_jaraba_core.module` | S11 | hook_theme variables wizard/daily |
| `demo-dashboard.html.twig` | S11 | Include wizard + daily partials |
| `demo-dashboard-view.html.twig` | S11 | Include wizard + daily partials |
| `DemoSessionEvent.php` | S10 | Nuevos eventos funnel |

---

## 12. Verificacion y Testing

### 12.1 Tests automatizados requeridos

| Test | Tipo | Sprint | Fichero |
|------|------|--------|---------|
| DemoLeadGateTest | Unit | S10 | tests/src/Unit/Controller/DemoLeadGateTest.php |
| DemoExplorarDashboardStepTest | Unit | S11 | tests/src/Unit/SetupWizard/DemoExplorarDashboardStepTest.php |
| DemoGenerarContenidoIAStepTest | Unit | S11 | tests/src/Unit/SetupWizard/DemoGenerarContenidoIAStepTest.php |
| DemoConvertirCuentaRealStepTest | Unit | S11 | tests/src/Unit/SetupWizard/DemoConvertirCuentaRealStepTest.php |
| ExplorarVerticalDemoActionTest | Unit | S11 | tests/src/Unit/DailyActions/ExplorarVerticalDemoActionTest.php |

### 12.2 Verificacion RUNTIME-VERIFY-001

| Check | Comando | Sprint |
|-------|---------|--------|
| CSS compilado | `npm run build` + timestamp check | S10 (si hay SCSS nuevo) |
| Rutas accesibles | `lando drush router:list --path=/api/v1/demo/lead-gate` | S10 |
| data-* selectores | Verificar `data-demo-lead-gate` en HTML renderizado | S10 |
| drupalSettings | Verificar inyeccion en preprocess | S11 |
| DOM final | Verificar wizard visible en `/demo/start/lawfirm` | S11 |

### 12.3 Checklist IMPLEMENTATION-CHECKLIST-001

- [ ] Servicios registrados en services.yml Y consumidos
- [ ] Rutas en routing.yml apuntan a clases/metodos existentes
- [ ] hook_theme() declara variables wizard y daily_actions
- [ ] Templates incluyen parciales con `only`
- [ ] Tests existen para cada servicio nuevo
- [ ] Config export si nueva config
- [ ] PHPStan Level 6 sin errores nuevos
- [ ] `bash scripts/validation/validate-all.sh --checklist web/modules/custom/ecosistema_jaraba_core`

---

## 13. Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| jaraba_crm no instalado en dev | Media | Bajo | OPTIONAL-CROSSMODULE-001: `@?` + null checks |
| Nano Banana API no disponible | Baja | Medio | Fallback: mantener SVG placeholders |
| Veo genera video off-brand | Media | Bajo | Iteraciones con prompts ajustados |
| Soft gate reduce tasa de demo starts | Media | Alto | Link "Saltar" prominente + A/B test |
| SetupWizardRegistry no tiene datos de sesion demo | Baja | Alto | Pasar sessionId via request_stack |

---

## 14. Registro de Cambios

| Fecha | Version | Autor | Descripcion |
|-------|---------|-------|-------------|
| 2026-03-19 | 1.0.0 | IA Asistente (Claude Opus 4.6) | Creacion inicial. S9-S12 planificados. S9 items 01-04 completados. |
| 2026-03-19 | 1.1.0 | IA Asistente (Claude Opus 4.6) | 12/14 hallazgos completados. S9 completo, S10 (soft gate + CRM), S11 (3 wizard + 3 daily actions), S12 (5 imagenes WebP). |
| 2026-03-19 | 1.2.0 | IA Asistente (Claude Opus 4.6) | **14/14 hallazgos COMPLETADOS**. S10-03 eventos funnel (LANDING_VIEW, LEAD_CAPTURED, LEAD_SKIPPED). S12-02 video showcase Veo (2.4MB MP4, progressive enhancement). SCSS compilado. Plan 100% ejecutado. |
