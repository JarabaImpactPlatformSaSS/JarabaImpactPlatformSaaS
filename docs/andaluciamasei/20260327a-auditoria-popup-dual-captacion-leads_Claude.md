# Auditoría de Conversión: Popup Dual de Captación de Leads — Andalucía +ei 2ª Edición

> **Fecha:** 2026-03-27
> **Autor:** Claude Opus 4.6 (1M context)
> **Versión:** 1.0.0
> **Módulo afectado:** jaraba_andalucia_ei
> **Reglas nuevas propuestas:** POPUP-DUAL-SELECTOR-001, POPUP-SAFEGUARD-001, POPUP-ANON-ONLY-001, POPUP-SHARED-DISMISS-001, POPUP-NEGOCIO-PATH-001
> **Programa:** PIIL — Colectivos Vulnerables 2025, 2ª Edición (Orden 29/09/2023 mod. 23/07/2025)

---

## Tabla de Contenidos

1. [Contexto y Justificación Estratégica](#1-contexto-y-justificación-estratégica)
2. [Estado Actual del Popup (Baseline)](#2-estado-actual-del-popup-baseline)
3. [Auditoría de Conversión — Scoring 10/10](#3-auditoría-de-conversión--scoring-1010)
4. [Análisis de Audiencia Dual](#4-análisis-de-audiencia-dual)
5. [Gap Analysis — Código vs Experiencia de Usuario](#5-gap-analysis--código-vs-experiencia-de-usuario)
6. [Setup Wizard + Daily Actions — Cumplimiento](#6-setup-wizard--daily-actions--cumplimiento)
7. [Safeguards Necesarios](#7-safeguards-necesarios)
8. [Recomendaciones Finales](#8-recomendaciones-finales)
9. [Glosario](#9-glosario)

---

## 1. Contexto y Justificación Estratégica

### 1.1 El Programa

La 2ª Edición del Programa Andalucía +ei (Emprendimiento Aumentado con IA) tiene dos objetivos de captación simultáneos:

- **Track A — Participantes:** 45 personas desempleadas de colectivos vulnerables en Sevilla y Málaga, registradas en SAE, que recibirán 100 horas de formación + €528 de incentivo económico.
- **Track B — Negocios Piloto:** 30-40 microempresas (1-15 empleados) en Sevilla y Málaga con necesidades digitales visibles, que recibirán servicios gratuitos de digitalización como práctica real para los participantes.

### 1.2 Por Qué un Popup en la Homepage del SaaS

La homepage del SaaS principal (plataformadeecosistemas.com) concentra el mayor tráfico orgánico del ecosistema gracias a la estrategia SEO multi-dominio (4 dominios, hreflang activo). Cada visitante anónimo que abandona sin conocer el programa es una oportunidad de inserción laboral perdida y un negocio piloto menos en el pipeline de prospección.

**Datos clave:**
- El programa está cofinanciado con fondos públicos (FSE+ / Junta de Andalucía) — hay obligación moral y contractual de maximizar la difusión.
- La tasa de inserción de la 1ª Edición fue del 46%, un dato de prueba social extremadamente poderoso.
- Las 45 plazas tienen fecha límite configurable (actualmente 2026-06-30) — la escasez es real, no artificial.
- Los negocios piloto convertidos a clientes de pago computan como inserciones laborales para el programa.

### 1.3 Infraestructura Existente

Ya existe un popup de reclutamiento (`reclutamiento-popup.js`) implementado y en producción, pero con dos limitaciones:

1. **Solo apunta a participantes** — no existe path de negocio piloto.
2. **Solo se muestra en metasitios secundarios** — el SaaS principal lo tiene desactivado (`mostrar_popup_saas: false`).

La propuesta es evolucionar el popup existente a un **popup dual con selector inteligente** que bifurque la experiencia según el perfil del visitante.

---

## 2. Estado Actual del Popup (Baseline)

### 2.1 Arquitectura Técnica

| Componente | Ubicación | Estado |
|---|---|---|
| JS principal | `web/modules/custom/jaraba_andalucia_ei/js/reclutamiento-popup.js` | ✅ Producción |
| SCSS | `web/modules/custom/jaraba_andalucia_ei/scss/_reclutamiento-popup.scss` | ✅ Producción |
| Library | `jaraba_andalucia_ei/reclutamiento-popup` en `libraries.yml` | ✅ Definida |
| Hook attach | `_jaraba_andalucia_ei_popup_attachments()` en `.module` (líneas 2204-2272) | ✅ Producción |
| Config | `jaraba_andalucia_ei.settings.yml` — 6 keys popup | ✅ Instalada |
| Imágenes | `reclutamiento-popup-hero.png` + `.webp` | ✅ Disponibles |
| drupalSettings | `aeiRecPopup` con 7 keys | ✅ Inyectado |

### 2.2 Comportamiento Actual

- **Delay:** 3 segundos (hardcoded como `DELAY_MS` en JS).
- **Dismiss TTL:** 48 horas via localStorage (`aei_rec_popup_dismissed`), configurable desde admin.
- **Hosts permitidos:** 3 metasitios producción + 3 Lando + SaaS condicional.
- **Condición de ruta:** Solo `/`, `/es`, `/es/` (homepage root).
- **Exclusión:** Rutas propias del módulo (`jaraba_andalucia_ei.*`).
- **Accesibilidad:** `role="dialog"`, `aria-modal="true"`, focus trap en botón cerrar, Escape key.
- **Tracking:** `data-track-cta` en ambos CTAs con `data-track-position="popup_metasite"`.
- **Textos:** 100% `Drupal.t()` — traducibles.
- **Datos:** `NO-HARDCODE-PRICE-001` — plazas e incentivo desde drupalSettings/config.

### 2.3 Cumplimiento de Directrices

| Directriz | Estado | Nota |
|---|---|---|
| CSS-VAR-ALL-COLORS-001 | ✅ | Todos los colores via `var(--ej-*)` |
| SCSS-COLORMIX-001 | ✅ | Usa `color-mix()` para alpha |
| ICON-CONVENTION-001 | ⚠️ N/A | El popup actual NO usa iconos (solo imagen hero) |
| FUNNEL-COMPLETENESS-001 | ✅ | `data-track-cta` + `data-track-position` en ambos CTAs |
| NO-HARDCODE-PRICE-001 | ✅ | Datos desde config |
| INNERHTML-XSS-001 | ✅ | `Drupal.checkPlain()` en URLs y valores dinámicos |
| URL-PROTOCOL-VALIDATE-001 | ✅ | URLs generadas por `Url::fromRoute()` (server-side) |
| ROUTE-LANGPREFIX-001 | ✅ | URLs via `Url::fromRoute()` |
| TWIG-INCLUDE-ONLY-001 | N/A | No usa Twig (JS-rendered) |
| MARKETING-TRUTH-001 | ✅ | "100% gratuito" correcto (programa cofinanciado, sin coste para participante) |

---

## 3. Auditoría de Conversión — Scoring 10/10

### 3.1 Scoring del Popup Actual (Solo Participantes)

| # | Criterio | Puntuación | Justificación |
|---|---|---|---|
| 1 | **Propuesta de valor clara** | 9/10 | Título directo + descripción con incentivo + stats |
| 2 | **CTA primario visible** | 9/10 | Dos CTAs con contraste visual claro |
| 3 | **Prueba social** | 7/10 | Falta dato 46% inserción en el popup |
| 4 | **Urgencia real** | 8/10 | Plazas restantes mostradas, falta countdown |
| 5 | **Credibilidad institucional** | 8/10 | Badge "Programa oficial" + mención FSE+, faltan logos |
| 6 | **Respeto al usuario** | 10/10 | Dismiss fácil, no bloquea, TTL localStorage |
| 7 | **Segmentación** | 4/10 | Solo participantes — ignora negocios piloto |
| 8 | **Tracking** | 9/10 | CTAs trackeados, falta evento de impresión |
| 9 | **Accesibilidad** | 9/10 | ARIA compliant, falta `aria-describedby` |
| 10 | **Rendimiento** | 10/10 | Zero dependencies extra, CSS inline en bundle |
| 11 | **SEO impacto** | 10/10 | Popup no afecta LCP/CLS (aparece tras delay) |
| 12 | **Mobile UX** | 8/10 | Responsive, pero padding podría mejorar en <360px |
| 13 | **A/B testing** | 3/10 | Sin integración con ABExperiment |
| 14 | **Geolocalización** | 2/10 | No filtra por ubicación (solo Sevilla/Málaga) |
| 15 | **Retargeting** | 5/10 | Solo localStorage dismiss — sin cookie para analytics |

**Score actual: 7.4/10**

### 3.2 Gaps para 10/10

| Gap | Impacto | Prioridad |
|---|---|---|
| Sin path para negocios piloto | CRÍTICO — 50% de la captación ignorada | P0 |
| Sin prueba social (46% inserción) en el popup | ALTO — dato más poderoso del programa | P0 |
| Sin evento de impresión del popup | ALTO — sin dato no hay funnel medible | P0 |
| Sin A/B testing del delay y copy | MEDIO — optimización incremental | P1 |
| Sin countdown de urgencia | MEDIO — refuerza escasez | P1 |
| Sin logos institucionales miniaturizados | BAJO — credibilidad visual | P2 |
| Sin `aria-describedby` para screen readers | BAJO — accesibilidad avanzada | P2 |
| Delay hardcoded (no configurable desde admin) | BAJO — flexibilidad operativa | P2 |

---

## 4. Análisis de Audiencia Dual

### 4.1 Participantes vs Negocios Piloto — Perfiles Incompatibles

| Dimensión | Participante | Negocio Piloto |
|---|---|---|
| **Perfil demográfico** | Desempleado, vulnerable, SAE | Microempresario, 1-15 empleados |
| **Motivación primaria** | Empleo + incentivo €528 | Digitalización gratuita |
| **Emoción dominante** | Esperanza, necesidad | Curiosidad, escepticismo |
| **Barrera principal** | "¿Es real?" (desconfianza) | "¿Qué me va a costar?" (tiempo) |
| **CTA óptimo** | "Solicita tu plaza" → /solicitar | "Prueba gratuita" → /prueba-gratuita |
| **Landing destino** | Reclutamiento (emocional) | Prueba gratuita (racional, comparativa) |
| **Prueba social clave** | 46% inserción | Testimonios de negocios reales |
| **Dato numérico clave** | 45 plazas + €528 | 0€ coste + 5 servicios incluidos |

### 4.2 Estrategia Recomendada: Bifurcación con Selector

Un popup genérico que intente hablar a ambos públicos diluye el mensaje. La solución es un **selector inicial** (2 opciones) que transforma el contenido del popup según la elección:

**Paso 1 — Selector (misma card):**
```
"¿Qué te interesa?"
[👤 Busco empleo] [🏪 Tengo un negocio]
```

**Paso 2a — Si elige "Busco empleo":**
- Hero image (existente), stats (plazas/incentivo/gratuito), dato 46% inserción
- CTA: "Solicitar plaza" → /solicitar

**Paso 2b — Si elige "Tengo un negocio":**
- Listado 5 servicios con iconos, comparativa precio mercado vs 0€
- CTA: "Ver servicios gratuitos" → /prueba-gratuita

La transición entre selector y contenido se hace con CSS transition (no recarga), manteniendo la card abierta.

---

## 5. Gap Analysis — Código vs Experiencia de Usuario

### 5.1 Gaps Detectados

| # | El código dice... | El usuario experimenta... | Solución |
|---|---|---|---|
| 1 | `mostrar_popup_saas: false` en config | El popup NO se muestra en el SaaS principal | Activar config + añadir hostname producción |
| 2 | Solo existe path de participante | Un negocio interesado no tiene CTA relevante | Implementar path dual con selector |
| 3 | `DELAY_MS = 3000` hardcoded | No se puede optimizar delay sin deploy | Mover a drupalSettings configurable |
| 4 | No hay evento `popup_impression` | No sabemos cuántos usuarios ven el popup | Añadir sendBeacon en `_show()` |
| 5 | localStorage key compartida parcialmente | Si visita metasitio Y SaaS, ve popup dos veces | Unificar key `aei_popup_dismissed` |
| 6 | No filtra usuarios logueados | Un usuario autenticado (ya cliente) ve popup | Excluir en PHP si `$current_user->isAuthenticated()` |
| 7 | Sin countdown en popup | Pierde oportunidad de urgencia visual | Añadir countdown mini desde `fecha_limite_solicitudes` |

### 5.2 Setup Wizard + Daily Actions — Cumplimiento

| Componente | Estado | Nota |
|---|---|---|
| CaptacionLeadsAction (Daily Action) | ✅ | Ya existe para coordinadores |
| SetupWizard — paso captación | ✅ | Incluido en wizard del coordinador |
| ProspeccionPipelineService | ✅ | Pipeline 6 fases implementado |
| NegocioProspectadoEi entity | ✅ | Entidad completa con `estado_embudo` |
| PruebaGratuitaController | ✅ | Landing 16 secciones, form con honeypot |
| Popup → Pipeline integration | ⚠️ Parcial | El popup lleva a landing, pero no crea entidad directamente |

El patrón SETUP-WIZARD-DAILY-001 se cumple para el equipo interno (coordinador, orientador). El popup es la capa de captación pública que alimenta ese pipeline.

---

## 6. Setup Wizard + Daily Actions — Cumplimiento

El popup dual complementa el ciclo completo:

```
CAPTACIÓN PÚBLICA (popup dual)
  ├── Participante → /solicitar → SolicitudParticipanteEi entity
  └── Negocio → /prueba-gratuita → NegocioProspectadoEi entity
        │
        ▼
PIPELINE INTERNO (Daily Actions + Setup Wizard)
  ├── CaptacionLeadsAction → ProspeccionPipelineService
  ├── Kanban drag-drop en /coordinador/prospeccion
  └── Propuesta personalizada con IA Copilot
        │
        ▼
CONVERSIÓN
  ├── Participante → inserción laboral (46% objetivo)
  └── Negocio → cliente SaaS de pago (billingycle)
```

---

## 7. Safeguards Necesarios

### 7.1 Nuevos Safeguards Propuestos

| ID | Nombre | Tipo | Descripción |
|---|---|---|---|
| POPUP-DUAL-SELECTOR-001 | Popup debe tener ambos paths | `run_check` | Verifica que el JS del popup contiene secciones para participante Y negocio |
| POPUP-SAFEGUARD-001 | Integridad popup attachments | `run_check` | Verifica que `_jaraba_andalucia_ei_popup_attachments()` inyecta todas las keys requeridas en drupalSettings |
| POPUP-ANON-ONLY-001 | Popup solo para anónimos | `run_check` | Verifica que el hook excluye usuarios autenticados |
| POPUP-SHARED-DISMISS-001 | localStorage key unificada | `warn_check` | Verifica que todos los popups del módulo usan la misma key de dismiss |
| POPUP-NEGOCIO-PATH-001 | Path negocio lleva a prueba-gratuita | `run_check` | Verifica que el CTA del path negocio apunta a ruta `jaraba_andalucia_ei.prueba_gratuita` |

### 7.2 Safeguard Script Propuesto

Fichero: `scripts/validation/validate-popup-dual-integrity.php`
- Valida presencia de ambos paths en JS
- Valida config keys en settings.yml
- Valida exclusión de usuarios autenticados en hook PHP
- Valida data-track-cta en ambos CTAs de ambos paths
- Valida que URLs destino son rutas válidas del módulo

---

## 8. Recomendaciones Finales

### 8.1 Implementación Inmediata (P0)

1. **Evolucionar `reclutamiento-popup.js`** a popup dual con selector participante/negocio.
2. **Activar `mostrar_popup_saas: true`** y añadir hostname de producción.
3. **Excluir usuarios autenticados** en el hook PHP.
4. **Añadir evento `popup_impression`** via sendBeacon.
5. **Añadir dato 46% inserción** como prueba social en el popup.

### 8.2 Optimización (P1)

1. **Integrar con ABExperiment** — testear delay (3s vs 5s vs scroll 25%).
2. **Countdown mini** con fecha límite desde config.
3. **Delay configurable** desde drupalSettings (mover de hardcoded a config).

### 8.3 Métricas de Éxito

| Métrica | Objetivo | Medición |
|---|---|---|
| Impresiones popup | >500/semana | funnel-analytics |
| CTR popup → landing | >8% | data-track-cta events |
| Conversión popup → formulario | >2% | cross-reference solicitudes |
| Ratio participante vs negocio | 60/40 | selector click events |
| Bounce rate delta | <+2% | GA4 con/sin popup |

---

## 9. Glosario

| Sigla | Significado |
|---|---|
| CTA | Call To Action — llamada a la acción |
| CTR | Click-Through Rate — tasa de clics |
| FSE+ | Fondo Social Europeo Plus |
| LCP | Largest Contentful Paint — métrica Core Web Vitals |
| CLS | Cumulative Layout Shift — métrica Core Web Vitals |
| PIIL | Programa de Itinerarios Integrados Laborales |
| SAE | Servicio Andaluz de Empleo |
| SaaS | Software as a Service |
| SCSS | Sassy CSS — preprocesador CSS |
| TTL | Time To Live — tiempo de expiración |
| UTM | Urchin Tracking Module — parámetros de tracking |
| WCAG | Web Content Accessibility Guidelines |
| XSS | Cross-Site Scripting |
| ARIA | Accessible Rich Internet Applications |
| DNT | Do Not Track — señal de privacidad del navegador |
| HMAC | Hash-based Message Authentication Code |
| RGPD | Reglamento General de Protección de Datos |
