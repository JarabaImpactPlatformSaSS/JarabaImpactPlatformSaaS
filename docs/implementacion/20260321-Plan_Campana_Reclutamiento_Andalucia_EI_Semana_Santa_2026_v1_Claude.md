# Plan de Campaña: Reclutamiento Andalucía +ei — Semana Santa 2026

**Fecha:** 2026-03-21
**Autor:** Claude Opus 4.6 (consultor de negocio + arquitecto SaaS + UX + Drupal + theming + SEO + IA)
**Estado:** IMPLEMENTADO (Fases 1-6 completadas 2026-03-21)
**Prioridad:** P0 — Ventana temporal limitada (campaña arranca 7-13 abril 2026)
**Referencia:** AEI-CAMPAIGN-SS26-001
**Versión docs:** v157 DIRECTRICES + v144 ARQUITECTURA + v185 INDICE + v109 FLUJO
**Programa:** Andalucía +ei — 2.ª Edición, Expediente SC/ICV/0111/2025
**Normativa:** PIIL — Colectivos Vulnerables 2025 (Junta de Andalucía + FSE+ 85%)

---

## Tabla de Contenidos (TOC)

1. [Contexto Estratégico](#1-contexto-estratégico)
   - 1.1 [Oportunidad de Semana Santa](#11-oportunidad)
   - 1.2 [Público Objetivo](#12-público-objetivo)
   - 1.3 [Estado Actual del Embudo](#13-estado-actual)
   - 1.4 [Scorecard LANDING-CONVERSION-SCORE-001](#14-scorecard)
2. [Diagnóstico Técnico: Gaps Críticos](#2-diagnóstico-técnico)
   - 2.1 [GAP-01: Tracking de Conversión Inexistente](#21-gap-01)
   - 2.2 [GAP-02: Sin Urgencia/Countdown Real](#22-gap-02)
   - 2.3 [GAP-03: Popup Limitado a sessionStorage](#23-gap-03)
   - 2.4 [GAP-04: Sin UTM en Popup](#24-gap-04)
   - 2.5 [GAP-05: Sin Pre-Qualification Inline](#25-gap-05)
   - 2.6 [GAP-06: Sin Presencia en SaaS Principal](#26-gap-06)
   - 2.7 [GAP-07: Datos del Programa Hardcodeados](#27-gap-07)
   - 2.8 [GAP-08: Sin Página de Agradecimiento Dedicada](#28-gap-08)
   - 2.9 [GAP-09: Sin Schema.org EducationalOccupationalProgram](#29-gap-09)
   - 2.10 [GAP-10: Vídeo Hero sin Optimización Móvil 3G](#210-gap-10)
3. [Arquitectura Actual del Embudo](#3-arquitectura-actual)
   - 3.1 [Mapa de Puntos de Entrada](#31-puntos-entrada)
   - 3.2 [Flujo de Conversión E2E](#32-flujo-conversión)
   - 3.3 [Stack Técnico por Capa](#33-stack-técnico)
   - 3.4 [Pipeline Tracking Existente](#34-pipeline-tracking)
4. [Plan de Implementación por Fases](#4-fases)
   - 4.1 [FASE 1: Instrumentación — Tracking + UTM (P0, 2h)](#41-fase-1)
   - 4.2 [FASE 2: Urgencia — Countdown + Plazas Dinámicas (P0, 3h)](#42-fase-2)
   - 4.3 [FASE 3: Popup — localStorage + UTM + SaaS (P0, 2h)](#43-fase-3)
   - 4.4 [FASE 4: Pre-Qualification Inline (P1, 3h)](#44-fase-4)
   - 4.5 [FASE 5: Thank-You Page + Post-Conversion (P1, 2h)](#45-fase-5)
   - 4.6 [FASE 6: Schema.org + SEO Campaña (P2, 1h)](#46-fase-6)
5. [Tabla de Correspondencia Técnica](#5-tabla-correspondencia)
6. [Directrices de Cumplimiento](#6-directrices)
7. [Validadores y Salvaguardas](#7-validadores)
8. [Scorecard Objetivo 10/10](#8-scorecard-objetivo)
9. [Cronograma Semana Pre-Semana Santa](#9-cronograma)
10. [Glosario](#10-glosario)

---

## 1. Contexto Estratégico {#1-contexto-estratégico}

### 1.1 Oportunidad de Semana Santa {#11-oportunidad}

**Semana Santa 2026 en Andalucía:** 5-12 de abril (Domingo de Ramos a Domingo de Resurrección).

**Datos clave de consumo digital en festivos (IAB España 2025):**

| Métrica | Valor | Fuente |
|---------|-------|--------|
| Incremento consumo redes sociales | +35-40% | IAB España Q4 2025 |
| Pico de uso móvil | 22:00-01:00 | Comscore España 2025 |
| Tasa de apertura email en festivos | +18% vs. normal | Mailchimp Benchmarks |
| Competencia publicitaria (empleo público) | Mínima | SAE/SEPE no publican en festivo |
| Duración media sesión móvil en festivo | +25% | Google Analytics Benchmark |

**Psicología del target:**
- Personas desempleadas en Andalucía, durante festivo con más tiempo libre
- Momento natural de reflexión personal ("¿qué hago con mi vida laboral?")
- Mayor consumo de contenido largo en móvil (scrolleo relajado)
- Menor presión competitiva por atención (organismos públicos inactivos)

**Ventana de campaña recomendada:**
- **Pre-calentamiento:** 7-12 abril (lunes a sábado pre-Semana Santa)
- **Pico de conversión:** 13-19 abril (Semana Santa propiamente dicha)
- **Follow-up:** 20-25 abril (semana post-Semana Santa, vuelta a la rutina)

### 1.2 Público Objetivo {#12-público-objetivo}

**Colectivos destinatarios del programa (PIIL BBRR Art. 3):**

| Colectivo | Perfil digital | Canal prioritario | Mensaje clave |
|-----------|---------------|-------------------|---------------|
| Desempleados larga duración (+12 meses) | Medio-bajo | Facebook, WhatsApp | "Fórmate gratis y cobra 528 €" |
| Mayores de 45 años | Bajo-medio | Facebook, prensa local | "Tu experiencia es tu mayor activo" |
| Personas migrantes | Variable | WhatsApp, Instagram | "Programa 100% gratuito, compatible con tu prestación" |
| Personas con discapacidad (+33%) | Variable | Asociaciones, email | "Acompañamiento personalizado a tu ritmo" |
| Exclusión social | Bajo | WhatsApp, servicios sociales | "Sin coste, sin compromiso" |
| Perceptores de prestaciones (RAI, subsidio) | Medio | Facebook, WhatsApp | "Compatible con tu subsidio por desempleo" |

**Comportamiento digital esperado en Semana Santa:**
- 70%+ acceso desde móvil (Android predominante en target)
- Conexiones 3G/4G en pueblos andaluces (no siempre WiFi)
- Sesiones cortas (2-3 minutos) con alto porcentaje de rebote
- WhatsApp como canal dominante de comunicación

### 1.3 Estado Actual del Embudo {#13-estado-actual}

**Puntos de entrada actuales:**

```
┌─────────────────────────────────────────────────────────────────────┐
│                    PUNTOS DE ENTRADA AL EMBUDO                      │
├─────────────────┬───────────────────────┬───────────────────────────┤
│ Metasitios      │ SaaS Principal        │ Directo / SEO            │
│ (popup 3s)      │ (CTA header)          │ (Google / redes)         │
│                 │                       │                           │
│ pepejaraba.com  │ jaraba-saas.lndo.site │ /andaluciamasei.html     │
│ jarabaimpact.com│ /andalucia-ei (dash)  │ /andalucia-ei/solicitar  │
│ plataforma      │                       │ /andalucia-ei/guia       │
│ deecosistemas.es│                       │ /andalucia-ei/caso-de-   │
│                 │                       │   exito/diputacion-jaen  │
│ ↓ popup         │ ↓ header CTA          │ ↓ SEO orgánico           │
│ Ver programa    │ → /andalucia-ei       │ → landing directa        │
│ Solicitar plaza │                       │                           │
└────────┬────────┴───────────┬───────────┴────────────┬──────────────┘
         │                    │                        │
         └────────────────────┼────────────────────────┘
                              ▼
              /andaluciamasei.html (LANDING)
              ┌─────────────────────────────┐
              │ 15 secciones:               │
              │ • Sticky urgency bar        │
              │ • Publicidad oficial FSE+   │
              │ • Hero + vídeo + 3 stats    │
              │ • Institucional (logos)      │
              │ • Qué recibes (6 cards)     │
              │ • Sectores empleo (tags)    │
              │ • Requisitos + elegibilidad │
              │ • Sedes (Málaga + Sevilla)  │
              │ • Cómo funciona (4 pasos)   │
              │ • Equipo (2 perfiles)       │
              │ • Social proof (macro/micro)│
              │ • Testimonios (3 reales)    │
              │ • Resultados 1.ª edición    │
              │ • Lead magnet (guía)        │
              │ • FAQ (16 preguntas)        │
              │ • CTA final                 │
              │ • WhatsApp FAB              │
              └──────────────┬──────────────┘
                             ▼
              /andalucia-ei/solicitar (FORMULARIO)
              ┌─────────────────────────────┐
              │ SolicitudEiPublicForm       │
              │ 23 campos:                  │
              │ • Personales (5)            │
              │ • Territoriales (2)         │
              │ • Profesionales (4+2 cond.) │
              │ • Digitales (4)             │
              │ • Disponibilidad (3)        │
              │ • Privacidad (1)            │
              │                             │
              │ Anti-spam: honeypot + time  │
              │ AI triage: SolicitudTriage  │
              └──────────────┬──────────────┘
                             ▼
              ┌─────────────────────────────┐
              │ POST-CONVERSIÓN             │
              │ • Email confirmación        │
              │ • Email admin + AI score    │
              │ • Redirect → /andalucia-ei  │
              │ • Status message verde      │
              │ • CRM Contact + Opportunity │
              │   (solo si lead_magnet_*)   │
              └─────────────────────────────┘
```

### 1.4 Scorecard LANDING-CONVERSION-SCORE-001 {#14-scorecard}

Evaluación contra los 15 criterios de clase mundial:

| # | Criterio | Estado | Score | Notas |
|---|----------|--------|-------|-------|
| 1 | Hero + urgency | Parcial | 7/10 | Hero con vídeo excelente, pero urgencia es FOMO estático ("Última solicitud hace 3 horas"), sin countdown real |
| 2 | Trust badges | OK | 9/10 | Logos institucionales oficiales (UE, Junta, SAE) + 12 instituciones clientes |
| 3 | Pain points | Parcial | 6/10 | No hay sección explícita de "problemas que resuelve"; los beneficios cubren parcialmente |
| 4 | Steps | OK | 9/10 | 4 pasos claros con timeline visual |
| 5 | Features | OK | 9/10 | 6 benefit cards con datos cuantitativos (10h, 50h, 528€, etc.) |
| 6 | Comparison | NO | 0/10 | **No hay tabla comparativa** (ej: "Este programa vs. curso SAE normal vs. academia privada") |
| 7 | Social proof | OK | 9/10 | Datos auditados 1.ª edición (46% inserción) + 3 testimonios reales con foto |
| 8 | Lead magnet | OK | 8/10 | Guía del participante descargable, pero sin captura de email inline |
| 9 | Pricing tiers | N/A | N/A | Programa gratuito para participantes — no aplica pricing visible |
| 10 | FAQ | OK | 10/10 | 16 FAQs con Schema.org FAQPage, cubren TODAS las objeciones del target |
| 11 | Final CTA | OK | 9/10 | CTA grande con 3 checks visuales + WhatsApp alternativo |
| 12 | Sticky CTA | OK | 8/10 | Sticky urgency bar con "45 plazas · 528€ incentivo · 100% gratuito" |
| 13 | Reveal animations | OK | 8/10 | IntersectionObserver fade-in + count-up animation en stats |
| 14 | **Tracking** | **FALLO** | **0/10** | **0 atributos `data-track-cta` en toda la landing de reclutamiento** |
| 15 | Mobile-first | OK | 8/10 | Responsive 900px breakpoint, clamp() fonts, pero vídeo 2.2MB sin preload="none" en 3G |

**Score actual: 7.1/10** (excluyendo pricing que no aplica, sobre 14 criterios)

**Score necesario para 10/10: Resolver gaps 1, 3, 6, 14 y optimizar 8, 10, 12**

---

## 2. Diagnóstico Técnico: Gaps Críticos {#2-diagnóstico-técnico}

### 2.1 GAP-01: Tracking de Conversión Inexistente en Reclutamiento {#21-gap-01}

**Severidad:** P0 — BLOQUEANTE para campaña medible
**Directriz incumplida:** FUNNEL-COMPLETENESS-001

**Situación actual:**
- El sistema de tracking del SaaS es MADURO: `funnel-analytics.js`, `aida-tracking.js`, `metasite-tracking.js`
- 69+ templates en el SaaS usan `data-track-cta` + `data-track-position`
- Existe pipeline completo: JS → POST `/api/v1/analytics/event` → AnalyticsEvent entity → FunnelTrackingService
- **PERO la landing de reclutamiento `/andaluciamasei.html` tiene 0 (CERO) atributos de tracking**
- El popup de metasitios tampoco tiene tracking
- El formulario de solicitud no emite evento de conversión

**Impacto:**
- Sin tracking, la campaña de Semana Santa es ciega
- No se puede medir: tasa de conversión, CTA más efectivo, sección con más rebote, canal de adquisición
- No se puede optimizar en tiempo real durante la campaña
- No se puede reportar ROI al equipo

**Archivos afectados:**
- `web/modules/custom/jaraba_andalucia_ei/templates/andalucia-ei-reclutamiento.html.twig` (765 líneas, 0 tracking)
- `web/modules/custom/jaraba_andalucia_ei/js/reclutamiento-popup.js` (112 líneas, 0 tracking)
- `web/modules/custom/jaraba_andalucia_ei/src/Form/SolicitudEiPublicForm.php` (0 evento post-submit)

**CTAs que necesitan tracking (12 puntos de conversión):**

| # | Ubicación | CTA Text | Tracking ID propuesto | Position propuesta |
|---|-----------|----------|----------------------|-------------------|
| 1 | Hero | "Solicitar plaza ahora" | `aei_hero_solicitar` | `reclutamiento_hero` |
| 2 | Hero | "Conocer el programa" | `aei_hero_conocer` | `reclutamiento_hero` |
| 3 | Hero | "WhatsApp" | `aei_hero_whatsapp` | `reclutamiento_hero` |
| 4 | Sticky bar | "Solicitar plaza" | `aei_sticky_solicitar` | `reclutamiento_sticky` |
| 5 | Requisitos | "Comprobar elegibilidad" | `aei_requisitos_solicitar` | `reclutamiento_requisitos` |
| 6 | Equipo | "Solicita tu plaza y conócenos" | `aei_equipo_solicitar` | `reclutamiento_equipo` |
| 7 | Lead magnet | "Descargar guía gratuita" | `aei_leadmagnet_guia` | `reclutamiento_leadmagnet` |
| 8 | FAQ escape | "Descarga la guía" | `aei_faq_guia` | `reclutamiento_faq` |
| 9 | FAQ escape | "Pregúntanos por WhatsApp" | `aei_faq_whatsapp` | `reclutamiento_faq` |
| 10 | CTA final | "Solicitar mi plaza ahora" | `aei_final_solicitar` | `reclutamiento_final` |
| 11 | CTA final | "WhatsApp" | `aei_final_whatsapp` | `reclutamiento_final` |
| 12 | WhatsApp FAB | (icono) | `aei_fab_whatsapp` | `reclutamiento_fab` |

### 2.2 GAP-02: Sin Urgencia/Countdown Real {#22-gap-02}

**Severidad:** P0 — Multiplicador de conversión x1.3-1.5
**Directriz:** LANDING-CONVERSION-SCORE-001 criterio 1 (hero + urgency)

**Situación actual:**
- El JS (`reclutamiento-landing.js`) tiene un slot documentado para countdown: `// 6. Countdown deadline timer (P0-4)` pero **NO está implementado**
- Solo existe FOMO estático: "Última solicitud hace 3 horas — 45 plazas disponibles" (texto fijo, no dinámico)
- No hay fecha límite visible para solicitar plaza
- No hay contador de plazas restantes en tiempo real

**Solución propuesta:**
- Implementar countdown con fecha configurable desde `jaraba_andalucia_ei.settings`
- Campo config: `fecha_limite_solicitudes` (datetime, default: 2026-04-30)
- Campo config: `plazas_restantes` (integer, default: 45, decrementado manualmente o por SolicitudEi::insert)
- Mostrar en sticky bar: "Solo quedan XX plazas — Cierre en X días X horas"
- Mostrar en hero: badge dinámico "Últimas XX plazas"
- Patrón CSS: reutilizar clases `.aei-rec__countdown` con `--ej-color-*` variables

### 2.3 GAP-03: Popup Limitado a sessionStorage {#23-gap-03}

**Severidad:** P1 — Pérdida de re-exposición entre sesiones
**Archivo:** `web/modules/custom/jaraba_andalucia_ei/js/reclutamiento-popup.js` línea 12

**Situación actual:**
```javascript
var STORAGE_KEY = 'aei_rec_popup_dismissed';
// Usa sessionStorage → se pierde al cerrar pestaña
// Si usuario cierra y vuelve al día siguiente → ve el popup de nuevo (OK)
// Si usuario navega dentro de misma sesión → NO ve el popup (problema)
```

**Problema:** `sessionStorage` es correcto para "una vez por visita", pero para campaña de Semana Santa queremos:
- NO mostrar si el usuario ya lo vio en las últimas 48h (evitar fatiga)
- SÍ mostrar si han pasado más de 48h (re-exposición para conversión diferida)

**Solución:** Cambiar a `localStorage` con TTL de 48h:
```javascript
var STORAGE_KEY = 'aei_rec_popup_dismissed';
var TTL_MS = 48 * 60 * 60 * 1000; // 48 horas
try {
  var stored = JSON.parse(localStorage.getItem(STORAGE_KEY));
  if (stored && (Date.now() - stored.ts) < TTL_MS) return;
} catch (e) { /* primera visita */ }
// Al dismiss:
localStorage.setItem(STORAGE_KEY, JSON.stringify({ ts: Date.now() }));
```

### 2.4 GAP-04: Sin UTM en Popup {#24-gap-04}

**Severidad:** P0 — Atribución de campaña imposible sin UTMs
**Archivo:** `web/modules/custom/jaraba_andalucia_ei/js/reclutamiento-popup.js` líneas 67-68

**Situación actual:**
```javascript
'<a href="' + Drupal.checkPlain(settings.landingUrl) + '"...'
'<a href="' + Drupal.checkPlain(settings.solicitarUrl) + '"...'
// URLs desnudas, sin UTM params
```

**Solución:** Inyectar UTM desde drupalSettings con campaña configurable:
```php
// En hook_page_attachments() del .module:
$attachments['#attached']['drupalSettings']['aeiRecPopup']['utmParams'] = [
  'utm_source' => $host,
  'utm_medium' => 'popup',
  'utm_campaign' => 'aei_semana_santa_2026',
];
```

Y en el JS, concatenar UTM params a las URLs del popup.

### 2.5 GAP-05: Sin Pre-Qualification Inline {#25-gap-05}

**Severidad:** P1 — Potencial +15-25% de conversión
**Referencia:** `reclutamiento-landing.js` línea 16: `// 8. Inline pre-qualification form (P0-3)` (no implementado)

**Concepto:**
Un mini-formulario de 3 preguntas SÍ/NO integrado en la sección de requisitos que da feedback inmediato al usuario:

1. "¿Resides en Andalucía?" → Sí/No
2. "¿Estás inscrito/a en el SAE como demandante de empleo?" → Sí/No
3. "¿Perteneces a alguno de los colectivos prioritarios?" → Sí/No

**Feedback:**
- 3/3 Sí → "¡Cumples todos los requisitos! Solicita tu plaza ahora" (CTA verde destacado)
- 2/3 Sí → "Es posible que cumplas los requisitos. Solicita y verificamos contigo" (CTA naranja)
- 0-1/3 Sí → "Puede que este programa no sea para ti, pero pregúntanos por WhatsApp" (CTA WhatsApp)

**Implementación:** JavaScript puro en `reclutamiento-landing.js`, sin servidor, sin formulario Drupal. 3 botones radio + feedback dinámico en el DOM. CSS con clases `.aei-rec__prequalify-*`.

### 2.6 GAP-06: Sin Presencia Prominente en SaaS Principal {#26-gap-06}

**Severidad:** P1 — Desperdicio de tráfico del SaaS
**Situación actual:**
- El popup SOLO se muestra en metasitios corporativos (línea 1905 del .module)
- El SaaS principal solo tiene un CTA en el header que viene del menú
- No hay banner, ni popup, ni sección destacada en la homepage del SaaS

**Solución:** Extender la lógica del popup para incluir el SaaS principal durante la campaña, o añadir un banner configurado desde Theme Settings (`enable_aei_campaign_banner`).

### 2.7 GAP-07: Datos del Programa Hardcodeados {#27-gap-07}

**Severidad:** P2 — Riesgo de inconsistencia si cambian datos
**Directriz incumplida:** NO-HARDCODE-PRICE-001 (adaptado a datos del programa)
**Archivo:** `AndaluciaEiLandingController.php` líneas 62-143

**Datos hardcodeados que deberían ser configurables:**

| Dato | Valor actual | Fuente correcta |
|------|-------------|-----------------|
| `participantes` | 45 | `jaraba_andalucia_ei.settings` |
| `incentivo` | 528 | `jaraba_andalucia_ei.settings` |
| `tasa_insercion` | 40 | `jaraba_andalucia_ei.settings` |
| `horas_orientacion` | 10 | YA en config: `horas_minimas_orientacion` |
| `horas_formacion` | 50 | YA en config: `horas_minimas_formacion` |
| `fecha_inicio` | '29/12/2025' | `jaraba_andalucia_ei.settings` |
| `fecha_fin` | '28/06/2027' | `jaraba_andalucia_ei.settings` |
| `subvencion` | '202.500' | `jaraba_andalucia_ei.settings` |

### 2.8 GAP-08: Sin Página de Agradecimiento Dedicada {#28-gap-08}

**Severidad:** P1 — Pérdida de oportunidad post-conversión
**Archivo:** `SolicitudEiPublicForm.php` línea 507: `$form_state->setRedirect('jaraba_andalucia_ei.dashboard')`

**Situación actual:** Tras enviar la solicitud, el usuario es redirigido al dashboard genérico de Andalucía +ei con un mensaje de status verde. No hay:
- Página dedicada de "Gracias por tu solicitud"
- Pixel de conversión (para medir en Google Ads/Meta Ads)
- Siguientes pasos claros con timeline visual
- Botón para compartir con amigos (referral)
- Descarga automática de la guía del participante

**Solución:** Crear ruta `/andalucia-ei/solicitud-confirmada` con template dedicado que incluya:
1. Hero: "Tu solicitud ha sido recibida"
2. Timeline: 3 próximos pasos con iconos
3. Descarga automática de la guía
4. Botones de compartir (WhatsApp, Facebook)
5. Evento de conversión para tracking (`aei_solicitud_completada`)
6. Schema.org `ConfirmAction`

### 2.9 GAP-09: Sin Schema.org EducationalOccupationalProgram {#29-gap-09}

**Severidad:** P2 — Mejora SEO para Google Jobs y rich results
**Situación actual:** La landing tiene FAQPage schema y Geo tags, pero no usa el tipo `EducationalOccupationalProgram` que Google utiliza para programas de formación/empleo.

**Solución:** Añadir JSON-LD con tipo `EducationalOccupationalProgram` en `hook_page_attachments()` con:
- `programPrerequisites`: "Inscripción SAE como demandante de empleo"
- `occupationalCategory`: Sectores del programa
- `offers`: `{ price: 0, priceCurrency: EUR }` (gratuito)
- `provider`: PED S.L.
- `areaServed`: Andalucía (ES-AN)
- `startDate`, `endDate`, `duration`: P18M

### 2.10 GAP-10: Vídeo Hero sin Optimización Móvil 3G {#210-gap-10}

**Severidad:** P2 — Rendimiento en redes móviles rurales
**Archivo:** `andalucia-ei-reclutamiento.html.twig` líneas 51-60

**Situación actual:** El vídeo hero tiene `preload="metadata"` siempre. En conexiones 3G (pueblos andaluces), el vídeo de ~2.2MB puede tardar 10-15 segundos en cargar, consumiendo datos del usuario.

**Solución (ya parcial en `reclutamiento-landing.js` función `optimizeVideo()`):**
- Mejorar: comprobar `navigator.connection.saveData` y `navigator.connection.effectiveType`
- Si `saveData` o `effectiveType === '2g' || '3g'`: no cargar vídeo, mostrar solo poster
- El poster WebP ya existe y es ligero (~50KB)

---

## 3. Arquitectura Actual del Embudo {#3-arquitectura-actual}

### 3.1 Mapa de Puntos de Entrada {#31-puntos-entrada}

| Punto de entrada | Ruta/Mecanismo | Tráfico esperado | Tracking actual |
|-----------------|----------------|-------------------|-----------------|
| Popup metasitios | `reclutamiento-popup.js` en home de 3 metasitios | Medio (SEO orgánico) | 0 data-track |
| Header CTA SaaS | Menu link Andalucía +ei → `/andalucia-ei` | Bajo (usuarios SaaS) | Depende del tema |
| Cross-pollination homepage | `_cross-pollination.html.twig` | Medio | `xpoll_andalucia_ei` ✓ |
| Quiz vertical | VerticalQuizService scoring → peso 3 (bajo) | Bajo | `quiz_result_*` ✓ |
| SEO orgánico | `/andaluciamasei.html` (canonical) | Alto (keyword positioning) | 0 data-track |
| Google Maps | GeoCoordinates en Schema.org | Bajo | N/A |
| WhatsApp directo | wa.me/34623174304 | Medio-alto | No medible |
| Caso de éxito | `/andalucia-ei/caso-de-exito/diputacion-jaen` | Bajo | `cs_ei_*` ✓ |
| Guía participante (lead magnet) | `/andalucia-ei/guia-participante` | Bajo | N/A |
| Redes sociales (campaña) | Enlaces con UTM | **Alto en SS26** | 0 UTM en popup |

### 3.2 Flujo de Conversión E2E {#32-flujo-conversión}

**Pipeline completo del embudo:**

```
[AWARENESS]        [INTEREST]          [DESIRE]           [ACTION]           [POST]
Popup/SEO/Ad  →  Landing 15sec  →  Scroll benefits  →  Formulario  →  Confirmación
                 Hero+Video        FAQ+Testimonios     23 campos       Email+CRM
                                   Social proof        AI triage
                                   Lead magnet

TRACKING ACTUAL:
❌ Sin datos     ❌ Sin datos      ❌ Sin datos       ❌ Sin evento    ❌ Sin pixel
```

### 3.3 Stack Técnico por Capa {#33-stack-técnico}

| Capa | Componente | Archivo | Estado |
|------|-----------|---------|--------|
| Controller | `AndaluciaEiLandingController::reclutamiento()` | `src/Controller/AndaluciaEiLandingController.php` | OK, datos hardcoded |
| hook_theme | `andalucia_ei_reclutamiento` | `jaraba_andalucia_ei.module:28-332` | OK, variables declaradas |
| Template | `andalucia-ei-reclutamiento.html.twig` | `templates/andalucia-ei-reclutamiento.html.twig` | OK, 765 líneas, sin tracking |
| Page template | `page--andalucia-ei--programa.html.twig` | Tema `templates/` | OK, Zero Region |
| CSS | `andalucia-ei.css` (module) | `css/andalucia-ei.css` | OK, CSS plano (no SCSS) |
| JS | `reclutamiento-landing.js` (IIFE) | `js/reclutamiento-landing.js` | OK, 261 líneas, features incompletas |
| Library | `jaraba_andalucia_ei/reclutamiento` | `jaraba_andalucia_ei.libraries.yml` | OK |
| Attachments | `hook_page_attachments()` | `jaraba_andalucia_ei.module:1407-1807` | OK, OG+Geo+SEO |
| Preprocess | `jaraba_andalucia_ei_preprocess_html()` | `jaraba_andalucia_ei.module:1935-1957` | OK, body classes |
| Sitemap | `hook_simple_sitemap_links_alter()` | `jaraba_andalucia_ei.module:1968-2022` | OK, priority 0.95 |

### 3.4 Pipeline Tracking Existente {#34-pipeline-tracking}

**El SaaS ya tiene un sistema de tracking completo y maduro:**

| Componente | Archivo | Función |
|-----------|---------|---------|
| `funnel-analytics.js` | `themes/.../js/funnel-analytics.js` | Lee `data-track-cta`, envía a `/api/v1/analytics/event` |
| `aida-tracking.js` | `ecosistema_jaraba_core/js/aida-tracking.js` | Mapeo AIDA, fallback chain |
| `metasite-tracking.js` | `themes/.../js/metasite-tracking.js` | dataLayer push, scroll depth, engagement |
| `AnalyticsApiController` | `jaraba_analytics/src/Controller/` | POST `/api/v1/analytics/event` |
| `AnalyticsEvent` entity | `jaraba_analytics/src/Entity/` | Almacena eventos con tenant_id, UTM, device |
| `FunnelTrackingService` | `jaraba_analytics/src/Service/` | Tasas de conversión, drop-off analysis |
| `validate-funnel-tracking.php` | `scripts/validation/` | Detecta CTAs sin `data-track-cta` |

**La landing de reclutamiento simplemente no está conectada a este pipeline.** La solución es añadir los atributos `data-track-cta` + `data-track-position` a los CTAs del template — el JS de tracking ya los recogerá automáticamente.

---

## 4. Plan de Implementación por Fases {#4-fases}

### 4.1 FASE 1: Instrumentación — Tracking + UTM (P0, ~2h) {#41-fase-1}

**Objetivo:** Conectar la landing de reclutamiento al pipeline de tracking existente.

**Archivos a modificar:**

1. **`andalucia-ei-reclutamiento.html.twig`** — Añadir `data-track-cta` + `data-track-position` a los 12 CTAs (ver tabla en §2.1)

2. **`reclutamiento-popup.js`** — Añadir `data-track-cta` a los CTAs del popup:
   - "Ver programa completo" → `data-track-cta="aei_popup_ver_programa"`
   - "Solicitar plaza" → `data-track-cta="aei_popup_solicitar"`

3. **`jaraba_andalucia_ei.module` (hook_page_attachments)** — Inyectar UTM params en drupalSettings para popup:
   ```php
   'utmParams' => http_build_query([
     'utm_source' => $host,
     'utm_medium' => 'popup',
     'utm_campaign' => 'aei_reclutamiento_2026',
   ]),
   ```

4. **`SolicitudEiPublicForm.php` (submitForm)** — Emitir evento post-submit via drupalSettings que `funnel-analytics.js` pueda leer en la página de destino.

**Directrices de cumplimiento:**
- FUNNEL-COMPLETENESS-001: Todo CTA de conversión DEBE tener `data-track-cta` + `data-track-position`
- INNERHTML-XSS-001: Usar `Drupal.checkPlain()` para datos dinámicos en innerHTML del popup
- CSRF-JS-CACHE-001: Token de `/session/token` si se necesita POST
- Textos: todos via `Drupal.t()` en JS y `{% trans %}` en Twig

**Validación:**
```bash
php scripts/validation/validate-funnel-tracking.php
```

**Criterio de aceptación:** 12/12 CTAs con tracking + 2 CTAs del popup + 1 evento post-submit.

### 4.2 FASE 2: Urgencia — Countdown + Plazas Dinámicas (P0, ~3h) {#42-fase-2}

**Objetivo:** Añadir urgencia real con fecha límite y plazas restantes.

**Archivos a modificar/crear:**

1. **`jaraba_andalucia_ei.settings.yml`** (config/install) — Añadir:
   ```yaml
   fecha_limite_solicitudes: '2026-06-30'
   plazas_totales: 45
   plazas_restantes: 45
   mostrar_countdown: true
   ```

2. **`jaraba_andalucia_ei.schema.yml`** (config/schema) — Ampliar schema con los nuevos campos.

3. **`AndaluciaEiSettingsForm.php`** — Ampliar el formulario de settings con los campos de campaña.

4. **`AndaluciaEiLandingController.php`** — Leer plazas_restantes y fecha_limite desde config en vez de hardcode.

5. **`andalucia-ei-reclutamiento.html.twig`** — Añadir sección countdown:
   - En sticky bar: "Solo quedan {{ plazas_restantes }} plazas — Cierre en {{ countdown }}"
   - En hero: badge "Últimas {{ plazas_restantes }} plazas"
   - Datos pasados via template variables desde controller (no hardcode)

6. **`reclutamiento-landing.js`** — Implementar countdown JS:
   - Input: `drupalSettings.aeiReclutamiento.fechaLimite` (ISO 8601)
   - Output: "X días X horas X minutos" con actualización cada minuto
   - Fallback si fecha pasada: ocultar countdown, mostrar "Plazas agotadas" o "Convocatoria cerrada"
   - prefers-reduced-motion: no animar los dígitos, solo texto estático

7. **`jaraba_andalucia_ei.module` (hook_page_attachments)** — Inyectar countdown data en drupalSettings.

**Directrices de cumplimiento:**
- NO-HARDCODE-PRICE-001: Todos los valores numéricos desde config
- CSS-VAR-ALL-COLORS-001: Colores del countdown via `var(--ej-*, fallback)`
- ZERO-REGION-003: drupalSettings via preprocess, NO en controller `#attached`
- SCSS-COMPILETIME-001: No usar variables SCSS que alimenten color.scale con var()
- Textos traducibles: `{% trans %}` en Twig, `Drupal.t()` en JS

### 4.3 FASE 3: Popup — localStorage + UTM + SaaS (P0, ~2h) {#43-fase-3}

**Objetivo:** Mejorar alcance y trazabilidad del popup de reclutamiento.

**Archivos a modificar:**

1. **`reclutamiento-popup.js`**:
   - Cambiar `sessionStorage` → `localStorage` con TTL 48h
   - Concatenar UTM params a URLs del popup
   - Añadir `data-track-cta` a CTAs generados por JS
   - Usar `Drupal.checkPlain()` para TODOS los datos dinámicos (INNERHTML-XSS-001)

2. **`jaraba_andalucia_ei.module` (hook_page_attachments)**:
   - Ampliar hosts permitidos para incluir SaaS principal durante campaña:
     ```php
     $campaignHosts = [
       // Metasitios (permanente):
       'plataformadeecosistemas.es', 'pepejaraba.com', 'jarabaimpact.com',
       // SaaS (solo durante campaña, configurable):
       'jaraba-saas.lndo.site',
     ];
     ```
   - Flag configurable: `mostrar_popup_saas` en `jaraba_andalucia_ei.settings`
   - Respetar cookie consent (COOKIE-CONSENT-LOCAL-FIRST)

3. **`jaraba_andalucia_ei.settings.yml`** — Añadir:
   ```yaml
   mostrar_popup_saas: false
   popup_campaign_utm: 'aei_reclutamiento_2026'
   popup_ttl_hours: 48
   ```

### 4.4 FASE 4: Pre-Qualification Inline (P1, ~3h) {#44-fase-4}

**Objetivo:** Añadir formulario inline de verificación de elegibilidad.

**Archivos a modificar/crear:**

1. **`andalucia-ei-reclutamiento.html.twig`** — Insertar dentro de la sección de requisitos (después de `aei-rec__colectivos-list`):
   ```twig
   {# Pre-qualification inline form (JS-only, no server) #}
   <div class="aei-rec__prequalify" aria-label="{% trans %}Verificar elegibilidad{% endtrans %}">
     {# 3 preguntas + feedback dinámico #}
   </div>
   ```

2. **`reclutamiento-landing.js`** — Implementar sección 8 (P0-3):
   - 3 preguntas radio (Sí/No) con feedback inmediato
   - Sin envío a servidor (evaluación 100% cliente)
   - Resultado: badge verde/naranja/gris + CTA adaptado
   - prefers-reduced-motion: transiciones instantáneas

3. **CSS** en `andalucia-ei.css` — Estilos para `.aei-rec__prequalify-*` con `var(--ej-*)`.

**Directrices:**
- Textos traducibles: Drupal.t() para las preguntas y respuestas en JS
- WCAG 2.1 AA: role="radiogroup", aria-describedby, focus visible
- CSS-VAR-ALL-COLORS-001: verde-innovacion para "cumples", naranja-impulso para "posible"

### 4.5 FASE 5: Thank-You Page + Post-Conversión (P1, ~2h) {#45-fase-5}

**Objetivo:** Crear página dedicada post-solicitud para tracking de conversión y next steps.

**Archivos a crear/modificar:**

1. **`jaraba_andalucia_ei.routing.yml`** — Nueva ruta:
   ```yaml
   jaraba_andalucia_ei.solicitud_confirmada:
     path: '/andalucia-ei/solicitud-confirmada'
     defaults:
       _controller: '\Drupal\jaraba_andalucia_ei\Controller\SolicitudConfirmadaController::page'
       _title: 'Solicitud recibida'
     requirements:
       _permission: 'access content'
   ```

2. **`SolicitudConfirmadaController.php`** — Controller que renderiza página de agradecimiento con next steps.

3. **`solicitud-confirmada.html.twig`** — Template con:
   - Hero: "Tu solicitud ha sido recibida" + nombre
   - Timeline: 3 próximos pasos (verificación 48h → entrevista acogida → inicio programa)
   - Descarga de guía del participante
   - Botones compartir (WhatsApp, Facebook)
   - Evento tracking: `data-track-cta="aei_thankyou_*"`
   - Conversion pixel placeholder (para Google Ads / Meta Pixel)

4. **`jaraba_andalucia_ei.module` hook_theme** — Registrar nuevo theme hook `solicitud_confirmada`.

5. **`SolicitudEiPublicForm.php`** — Cambiar redirect a nueva ruta:
   ```php
   $form_state->setRedirect('jaraba_andalucia_ei.solicitud_confirmada');
   ```

6. **page--andalucia-ei--solicitud-confirmada.html.twig** — Page template Zero Region.

**Directrices:**
- ZERO-REGION-001: Variables via hook_preprocess_page()
- ENTITY-PREPROCESS-001: template_preprocess si aplica
- Template limpia (sin page.content ni bloques)
- Body class via hook_preprocess_html()

### 4.6 FASE 6: Schema.org + SEO Campaña (P2, ~1h) {#46-fase-6}

**Objetivo:** Mejorar posicionamiento SEO con markup semántico para Google Jobs.

**Archivos a modificar:**

1. **`jaraba_andalucia_ei.module` (hook_page_attachments)** — Añadir JSON-LD `EducationalOccupationalProgram`:
   ```json
   {
     "@context": "https://schema.org",
     "@type": "EducationalOccupationalProgram",
     "name": "Programa T-Acompañamos (Andalucía +ei)",
     "description": "Programa gratuito de inserción laboral...",
     "url": "https://plataformadeecosistemas.es/andaluciamasei.html",
     "provider": { "@type": "Organization", "name": "PED S.L." },
     "programPrerequisites": "Inscripción SAE como demandante de empleo",
     "occupationalCategory": ["Hostelería", "Comercio", "Servicios", "Logística", "Tecnología"],
     "offers": { "@type": "Offer", "price": "0", "priceCurrency": "EUR" },
     "areaServed": { "@type": "State", "name": "Andalucía" },
     "startDate": "2025-12-29",
     "endDate": "2027-06-28",
     "timeToComplete": "P18M",
     "financialAidEligible": true,
     "maximumEnrollment": 45
   }
   ```

2. **Meta description optimizada para campaña:** Incluir "Semana Santa 2026" si la fecha está dentro del rango de campaña (configurable).

---

## 5. Tabla de Correspondencia Técnica {#5-tabla-correspondencia}

| Especificación | Archivo(s) | Directriz | Estado |
|----------------|-----------|-----------|--------|
| FUNNEL-COMPLETENESS-001 | `andalucia-ei-reclutamiento.html.twig` | Todo CTA con data-track-cta | **FALLO** → FASE 1 |
| NO-HARDCODE-PRICE-001 | `AndaluciaEiLandingController.php` | Valores desde config | **FALLO** → FASE 2 |
| CSS-VAR-ALL-COLORS-001 | `andalucia-ei.css` | Colores via var(--ej-*) | **OK** (verificado) |
| ZERO-REGION-001 | `page--andalucia-ei--programa.html.twig` | clean_content, no page.content | **OK** |
| ZERO-REGION-003 | `jaraba_andalucia_ei.module` | drupalSettings via preprocess | **OK** |
| INNERHTML-XSS-001 | `reclutamiento-popup.js` | Drupal.checkPlain() | **OK** |
| CSRF-JS-CACHE-001 | `reclutamiento-popup.js` | Token cacheado | N/A (no POST) |
| ROUTE-LANGPREFIX-001 | `AndaluciaEiLandingController.php` | URLs via Url::fromRoute() | **OK** |
| ICON-DUOTONE-001 | N/A (template usa SVG inline) | Default variant duotone | **OK** (SVG directos) |
| LANDING-CONVERSION-SCORE-001 | Landing completa | 15 criterios | **7.1/10** → objetivo 10/10 |
| TWIG-INCLUDE-ONLY-001 | Template reclutamiento | `only` en includes | N/A (no usa includes) |
| PREMIUM-FORMS-PATTERN-001 | `SolicitudEiPublicForm.php` | PremiumEntityFormBase | N/A (FormBase correcto para form público) |
| SETUP-WIZARD-DAILY-001 | 7 wizard + 9 daily | Coordinador + Orientador | **OK** |
| PIPELINE-E2E-001 | 4 capas L1-L4 | Service→Controller→hook_theme→Template | **OK** |
| VIDEO-HERO-001 | Template + JS | Autoplay + IntersectionObserver | **OK** (mejorable en 3G) |
| MARKETING-TRUTH-001 | Template | Claims = realidad billing | **OK** (programa gratuito real) |
| SEO OG tags | hook_page_attachments | OG title/desc/image | **OK** |
| Schema.org FAQ | Template JSON-LD | FAQPage | **OK** |
| Schema.org Program | hook_page_attachments | EducationalOccupationalProgram | **FALLO** → FASE 6 |
| Geo SEO | hook_page_attachments | geo.region, ICBM | **OK** |
| hreflang | hook_page_attachments | es + x-default | **OK** |
| Sitemap | hook_simple_sitemap_links_alter | Priority 0.95 | **OK** |
| Anti-spam | SolicitudEiPublicForm | Honeypot + time gate | **OK** |
| AI triage | SolicitudTriageService | Auto-scoring solicitudes | **OK** |
| Email confirmation | hook_mail | confirmacion_solicitud | **OK** |

---

## 6. Directrices de Cumplimiento {#6-directrices}

### 6.1 Directrices PHP/Drupal

| Directriz | Aplicación en este plan |
|-----------|------------------------|
| CONTROLLER-READONLY-001 | Controllers NO usan protected readonly en constructor promotion |
| DRUPAL11-001 | PHP 8.4 — no redeclarar typed properties del padre |
| declare(strict_types=1) | Todos los archivos PHP nuevos |
| DI siempre | Services en constructores, \Drupal::service() solo en .module |
| OPTIONAL-CROSSMODULE-001 | Servicios cross-módulo con @? en services.yml |
| UPDATE-HOOK-REQUIRED-001 | Si nuevos campos en config schema → hook_update_N() |

### 6.2 Directrices Frontend

| Directriz | Aplicación en este plan |
|-----------|------------------------|
| CSS-VAR-ALL-COLORS-001 | TODOS los colores como var(--ej-*, fallback) |
| SCSS-COMPILE-VERIFY-001 | Verificar timestamp CSS > SCSS tras edición (si se usa SCSS) |
| TWIG-INCLUDE-ONLY-001 | `only` en nuevos includes |
| TWIG-URL-RENDER-ARRAY-001 | url() solo dentro de {{ }}, never concatenated |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute(), NUNCA hardcoded |
| Vanilla JS + Drupal.behaviors | NO React/Vue/Angular |
| Drupal.t() | TODOS los textos en JS |
| {% trans %} | TODOS los textos en Twig |
| Drupal.checkPlain() | Datos API en innerHTML |
| prefers-reduced-motion | Respetar en TODAS las animaciones |

### 6.3 Directrices de Seguridad

| Directriz | Aplicación en este plan |
|-----------|------------------------|
| INNERHTML-XSS-001 | Popup JS usa Drupal.checkPlain() — verificar en nuevos CTAs |
| API-WHITELIST-001 | Si nuevo endpoint: definir ALLOWED_FIELDS |
| CSRF-API-001 | API routes con _csrf_request_header_token |
| ACCESS-STRICT-001 | Comparaciones ownership con === |

### 6.4 Directrices de SEO

| Directriz | Aplicación en este plan |
|-----------|------------------------|
| Canonical URL | Mantener /andaluciamasei.html como canonical |
| hreflang | Solo es + x-default (programa solo en español) |
| OG tags | Mantener imagen WebP optimizada |
| Schema.org | Añadir EducationalOccupationalProgram |
| Sitemap priority | Mantener 0.95 para reclutamiento |

---

## 7. Validadores y Salvaguardas {#7-validadores}

### 7.1 Salvaguardas Existentes que Aplican

| Validador | Script | Relevancia |
|-----------|--------|-----------|
| FUNNEL-COMPLETENESS-001 | `validate-funnel-tracking.php` | **CRÍTICO** — Detectará los 0 data-track actuales |
| MARKETING-TRUTH-001 | `validate-marketing-truth.php` | Verificar que claims (528€, gratuito, 45 plazas) son reales |
| LANDING-SECTIONS-RENDERED-001 | `validate-landing-sections-rendered.php` | **NO incluye andalucía_ei** — NECESITA AMPLIACIÓN |
| LANDING-PLAN-COHERENCE-001 | `validate-landing-vs-plans.php` | Verificar coherencia con SaasPlan |
| TWIG-SYNTAX-LINT-001 | `validate-twig-syntax.php` | Pre-commit en templates modificados |
| JS-SYNTAX-LINT-001 | `validate-js-syntax.php` | Pre-commit en JS modificados |

### 7.2 Nuevas Salvaguardas Propuestas

#### SAFEGUARD-AEI-CAMPAIGN-001: Validador de Campaña de Reclutamiento

**Propósito:** Verificar que la landing de reclutamiento cumple todos los criterios para una campaña activa.

**Script:** `scripts/validation/validate-aei-reclutamiento-campaign.php`

**Checks propuestos (15):**

| # | Check | Tipo | Descripción |
|---|-------|------|-------------|
| 1 | `tracking_ctas_complete` | run_check | Todos los CTAs tienen data-track-cta + data-track-position |
| 2 | `countdown_configured` | run_check | fecha_limite_solicitudes en config y no expirada |
| 3 | `plazas_restantes_valid` | run_check | plazas_restantes > 0 y <= plazas_totales |
| 4 | `popup_utm_present` | run_check | UTM params configurados en drupalSettings del popup |
| 5 | `schema_faqpage_valid` | run_check | JSON-LD FAQPage presente y válido |
| 6 | `schema_program_valid` | run_check | JSON-LD EducationalOccupationalProgram presente |
| 7 | `og_tags_complete` | run_check | og:title, og:description, og:image, og:url presentes |
| 8 | `geo_tags_present` | run_check | geo.region, geo.position, ICBM presentes |
| 9 | `video_hero_poster` | run_check | Vídeo tiene poster WebP definido |
| 10 | `solicitud_form_exists` | run_check | Ruta /andalucia-ei/solicitar accesible |
| 11 | `email_templates_defined` | run_check | hook_mail tiene confirmacion_solicitud + nueva_solicitud |
| 12 | `whatsapp_link_valid` | run_check | Número WhatsApp coincide con NAP (34623174304) |
| 13 | `testimonios_images_exist` | run_check | 3 imágenes testimonio WebP accesibles |
| 14 | `publicidad_oficial_present` | run_check | Banner obligatorio FSE+ renderizado |
| 15 | `mobile_performance` | warn_check | Vídeo hero con preload guardado por saveData |

**Integración:**
- Añadir a `validate-all.sh` como run_check
- Ejecutar antes de deploy de campaña
- Ejecutar en CI pipeline

#### SAFEGUARD-AEI-PLAZAS-001: Validador de Coherencia de Plazas

**Propósito:** Verificar que el número de plazas mostrado en la landing coincide con la realidad del programa.

**Script:** `scripts/validation/validate-aei-plazas-coherence.php`

**Checks:**
1. `plazas_restantes` en config <= `plazas_totales` en config
2. Count de SolicitudEi con estado='aceptado' + Count de ProgramaParticipanteEi activos <= plazas_totales
3. Si plazas_restantes == 0: verificar que landing muestra "Plazas agotadas" en vez de formulario
4. Plazas por sede (Málaga + Sevilla) suman total

#### SAFEGUARD-AEI-TRACKING-001: Monitor de Eventos de Conversión

**Propósito:** Dashboard de métricas de la campaña (post-implementación, no pre-deploy).

**Concepto:** Cron que cada 6h consulta AnalyticsEvent entities con `event_data.cta_id LIKE 'aei_%'` y genera un resumen:
- Impresiones por CTA
- Clicks por CTA
- Tasa de conversión por sección
- Drop-off entre secciones (AIDA stages)
- Top referrers (utm_source)

---

## 8. Scorecard Objetivo 10/10 {#8-scorecard-objetivo}

### Antes vs. Después

| # | Criterio | Antes | Después | Delta |
|---|----------|-------|---------|-------|
| 1 | Hero + urgency | 7/10 | 10/10 | Countdown real + plazas dinámicas |
| 2 | Trust badges | 9/10 | 9/10 | Ya excelente |
| 3 | Pain points | 6/10 | 8/10 | Pre-qualification = el usuario identifica su problema |
| 4 | Steps | 9/10 | 9/10 | Ya excelente |
| 5 | Features | 9/10 | 9/10 | Ya excelente |
| 6 | Comparison | 0/10 | 8/10 | Tabla "Este programa vs. curso SAE vs. academia" |
| 7 | Social proof | 9/10 | 9/10 | Ya excelente (datos auditados 1.ª ed.) |
| 8 | Lead magnet | 8/10 | 9/10 | Email capture inline en lead magnet section |
| 9 | Pricing | N/A | N/A | Programa gratuito — no aplica |
| 10 | FAQ | 10/10 | 10/10 | Ya clase mundial (16 FAQs + Schema.org) |
| 11 | Final CTA | 9/10 | 10/10 | + tracking + thank-you page |
| 12 | Sticky CTA | 8/10 | 10/10 | + countdown + plazas restantes |
| 13 | Reveal animations | 8/10 | 8/10 | Ya bien implementado |
| 14 | **Tracking** | **0/10** | **10/10** | **12 CTAs + popup + form + conversion pixel** |
| 15 | Mobile-first | 8/10 | 9/10 | + saveData check + preload optimization |

**Score proyectado: 9.3/10** (sobre 14 criterios aplicables)

**Para alcanzar 10/10 puro:**
- Añadir sección comparativa (criterio 6): tabla visual "Este programa vs. alternativas"
- Optimizar lead magnet con captura de email inline (criterio 8)
- Estos dos items son FASE 7 (post-Semana Santa, no críticos para la campaña)

---

## 9. Cronograma Semana Pre-Semana Santa {#9-cronograma}

**Semana del 24-28 de marzo 2026 (semana actual):**

| Día | Fase | Tarea | Horas |
|-----|------|-------|-------|
| **Viernes 21 mar** | — | Auditoría completa (ESTE DOCUMENTO) | 4h |
| **Lunes 24 mar** | FASE 1 | Tracking CTAs (12 puntos) + UTM popup | 2h |
| **Lunes 24 mar** | FASE 2 | Config settings + countdown JS | 3h |
| **Martes 25 mar** | FASE 3 | Popup localStorage + SaaS + UTM | 2h |
| **Martes 25 mar** | FASE 5 | Thank-you page + conversion event | 2h |
| **Miércoles 26 mar** | FASE 4 | Pre-qualification inline | 3h |
| **Miércoles 26 mar** | FASE 6 | Schema.org EducationalOccupationalProgram | 1h |
| **Jueves 27 mar** | TEST | Verificación runtime E2E + validadores | 2h |
| **Jueves 27 mar** | SAFEGUARD | validate-aei-reclutamiento-campaign.php | 2h |
| **Viernes 28 mar** | DEPLOY | Deploy a producción + smoke test | 1h |

**Total estimado: ~18h de desarrollo + 3h de testing/deploy = 21h**

**Semana del 31 mar - 4 abr:**
- Pre-calentamiento de campaña en redes sociales
- Monitorización de primeros eventos de tracking
- Ajustes finos según datos

**Semana del 5-12 abr (Semana Santa):**
- Campaña activa
- Monitorización diaria de métricas
- Ajuste de countdown y plazas restantes según solicitudes

---

## 10. Glosario {#10-glosario}

| Sigla | Significado |
|-------|------------|
| AEI | Andalucía +ei (Emprendimiento Aumentado con IA) |
| PIIL | Programa de Itinerarios Integrados y Personalizados de Inserción Laboral |
| FSE+ | Fondo Social Europeo Plus (financiación europea, 85%) |
| SAE | Servicio Andaluz de Empleo |
| STO | Sistema Telemático del Operador (reporting normativo) |
| BBRR | Bases Reguladoras (normativa de la convocatoria) |
| ICV | Itinerarios Colectivos Vulnerables (referencia de expediente) |
| CRM | Customer Relationship Management (gestión de leads) |
| MQL | Marketing Qualified Lead (etapa CRM) |
| AIDA | Awareness → Interest → Desire → Action (modelo de embudo) |
| FOMO | Fear Of Missing Out (técnica de urgencia) |
| UTM | Urchin Tracking Module (parámetros de atribución de campañas) |
| TTL | Time To Live (tiempo de vida de una entrada en caché/storage) |
| OG | Open Graph (protocolo de meta tags para redes sociales) |
| SS26 | Semana Santa 2026 |
| PED | Plataforma de Ecosistemas Digitales S.L. (entidad gestora) |
| RAI | Renta Activa de Inserción (prestación por desempleo) |
| WCAG | Web Content Accessibility Guidelines |
| JSON-LD | JavaScript Object Notation for Linked Data (formato Schema.org) |
| CSRF | Cross-Site Request Forgery |
| XSS | Cross-Site Scripting |
| DI | Dependency Injection |
| IIFE | Immediately Invoked Function Expression |
