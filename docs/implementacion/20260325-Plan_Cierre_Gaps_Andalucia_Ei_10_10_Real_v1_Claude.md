# Plan de Cierre de Gaps: Andalucía +ei — De 10/10 Teórico a 10/10 Real

> **Versión:** 1.0.0
> **Fecha:** 2026-03-25
> **Autor:** Claude Opus 4.6 (1M context)
> **Estado:** Propuesta — pendiente de aprobación
> **Prioridad:** P0 (pre-lanzamiento campaña Semana Santa)
> **Score actual:** 6.5/10 (auditoría honesta: backend sólido, frontend con 7 gaps de experiencia)
> **Score objetivo:** 10/10 real y verificable en navegador
> **Módulos afectados:** `jaraba_andalucia_ei`, `ecosistema_jaraba_theme`, `jaraba_copilot_v2`
> **Directrices raíz:** ZERO-REGION-001, SLIDE-PANEL-RENDER-002, PIPELINE-E2E-001, RUNTIME-VERIFY-001, CSS-VAR-ALL-COLORS-001, ICON-CONVENTION-001, SETUP-WIZARD-DAILY-001

---

## Índice de Navegación (TOC)

1. [Diagnóstico: Por qué 6.5/10 y no 10/10](#1-diagnóstico)
2. [Principio: "El código existe" vs "El usuario lo experimenta"](#2-principio)
3. [Sprint E1 — Page Templates Zero-Region (4 rutas)](#3-sprint-e1)
4. [Sprint E2 — API Endpoint Kanban + Slide-Panel Entities](#4-sprint-e2)
5. [Sprint E3 — JS Consumers (Calculadora + Prompt Chips)](#5-sprint-e3)
6. [Sprint E4 — Compliance Normativa P1 (8 gaps)](#6-sprint-e4)
7. [Sprint E5 — Formularios SAE como Entities](#7-sprint-e5)
8. [Tabla de Correspondencia con Directrices](#8-directrices)
9. [Tabla de Correspondencia con Especificaciones Técnicas](#9-specs)
10. [Verificación RUNTIME-VERIFY-001 en Navegador](#10-verificacion)
11. [Salvaguardas Propuestas](#11-salvaguardas)
12. [Glosario de Siglas](#12-glosario)

---

## 1. Diagnóstico: Por qué 6.5/10 y no 10/10 {#1-diagnóstico}

### 1.1 La auditoría honesta

Una auditoría brutal de "código existe vs usuario lo experimenta" revela 7 gaps críticos que hacen que el score real sea 6.5/10 a pesar de tener backend, entities, servicios y templates implementados:

| # | Gap | Tipo | Impacto en usuario |
|---|-----|------|-------------------|
| GAP-E1 | 4 rutas sin page template en tema (prueba-gratuita, pipeline, portfolio, formador) | Frontend chrome | El usuario ve barra admin de Drupal, sidebar, y bloques heredados en páginas que deberían ser frontend limpio |
| GAP-E2 | Kanban pipeline sin endpoint PATCH API | API missing | Drag-and-drop mueve tarjetas visualmente pero NO persiste — al refrescar, vuelven a su posición original |
| GAP-E3 | 3 entities sin slide-panel (NegocioProspectadoEi, PackServicioEi, EntregableFormativoEi) | UX modal | El usuario tiene que navegar a /admin/content/* para crear/editar, abandonando su dashboard |
| GAP-E4 | Calculadora inyectada en drupalSettings pero sin JS consumer | Feature invisible | Los datos de punto de equilibrio están en console.log del navegador pero NUNCA se renderizan |
| GAP-E5 | 69 prompts inyectados pero sin chips en copilot UI | Feature invisible | Los prompts sugeridos por sesión existen en config pero el chat no los muestra como botones |
| GAP-E6 | 8 validaciones de compliance normativa P1 ausentes | Riesgo justificación | El SAE podría rechazar sesiones en fin de semana, grupos >25, inserciones en entidad propia |
| GAP-E7 | Formularios ODT del SAE no digitalizados | Proceso manual | Acuerdo Participación, DACI, Recibí Incentivo se firman en papel en vez de en la plataforma |

### 1.2 Análisis root-cause

La causa raíz es un patrón recurrente: **implementar backend-first y asumir que "el frontend se conecta solo"**. En Drupal esto es particularmente peligroso porque:

1. Un `#theme` sin page template se renderiza dentro del chrome admin (con sidebar, toolbar, blocks) — funciona pero no es la experiencia diseñada
2. Un `drupalSettings` inyectado sin JS consumer es datos muertos en el DOM — no genera error pero no produce valor
3. Un entity form que funciona en `/admin/content/*` pero no tiene slide-panel route está "accesible" pero no "experienciable" desde el dashboard

### 1.3 Tabla de scoring detallado

| Dimensión | Score actual | Score objetivo | Delta |
|-----------|-------------|----------------|-------|
| Backend (entities, services, DB) | 10/10 | 10/10 | 0 |
| API endpoints (routes accesibles) | 7/10 | 10/10 | -3 |
| Frontend templates (existen) | 9/10 | 10/10 | -1 |
| Frontend page chrome (zero-region) | 4/10 | 10/10 | -6 |
| JS consumers (datos → UI) | 3/10 | 10/10 | -7 |
| Slide-panel coverage | 5/10 | 10/10 | -5 |
| Compliance normativa | 7/10 | 10/10 | -3 |
| Setup Wizard + Daily Actions | 9/10 | 10/10 | -1 |
| **PROMEDIO** | **6.5/10** | **10/10** | **-3.5** |

---

## 2. Principio: "El código existe" vs "El usuario lo experimenta" {#2-principio}

### 2.1 Regla de oro #157 (ampliada)

> **TODA feature implementada DEBE verificarse en CADA capa de la cadena L1→L7:**
>
> L1: Servicio inyectado en controller (constructor + create())
> L2: Controller devuelve #theme render array con variables pobladas
> L3: hook_theme() declara el template con matching variables
> L4: Template Twig renderiza con {% trans %}, jaraba_icon(), `only`
> L5: Page template en TEMA (zero-region, clean_content, sin sidebar)
> L6: SCSS compilado → CSS cargado via library → timestamp verificado
> L7: JS consumer lee drupalSettings → renderiza UI interactiva
>
> Si CUALQUIER capa falta, la feature no existe para el usuario.

### 2.2 Checklist de verificación por feature

Para cada feature nueva o modificada, ANTES de considerar "terminado":

```
□ L1: ¿El servicio está registrado en services.yml Y consumido?
□ L2: ¿El controller devuelve #theme (NO markup vacío)?
□ L3: ¿hook_theme() declara variables que coinciden con el controller?
□ L4: ¿El template usa {% trans %}, jaraba_icon(), include ... only?
□ L5: ¿Existe page--{ruta}.html.twig en el TEMA con clean_content?
□ L6: ¿El SCSS está en main.scss, compilado, y library registrada?
□ L7: ¿Todo drupalSettings inyectado tiene JS que lo LEE y RENDERIZA?
□ L8: ¿Las acciones crear/editar usan slide-panel (no navegar fuera)?
□ L9: ¿El endpoint API que el JS llama EXISTE en routing.yml?
□ L10: ¿El usuario puede completar el flujo E2E sin abandonar la página?
```

---

## 3. Sprint E1 — Page Templates Zero-Region (4 rutas) {#3-sprint-e1}

### 3.1 Problema

Las 4 rutas nuevas se renderizan dentro del chrome admin de Drupal porque no tienen page template en el tema. El usuario ve:
- Barra de administración (toolbar)
- Sidebar con bloques
- Header/footer del tema mezclados con admin chrome

### 3.2 Solución

Crear page templates en `ecosistema_jaraba_theme/templates/` siguiendo el patrón exacto de `page--andalucia-ei.html.twig` (93 líneas, zero-region, clean_content, header/footer propios).

**4 ficheros a crear:**

| Fichero | Ruta | Tipo |
|---------|------|------|
| `page--andalucia-ei--prueba-gratuita.html.twig` | `/andalucia-ei/prueba-gratuita` | Público (sin auth, sin copilot FAB) |
| `page--andalucia-ei--coordinador--prospeccion-pipeline.html.twig` | `/andalucia-ei/coordinador/prospeccion-pipeline` | Auth (con copilot FAB) |
| `page--andalucia-ei--formador.html.twig` | `/andalucia-ei/formador` | Auth (con copilot FAB) |
| `page--portfolio.html.twig` | `/portfolio/{id}` | Público (sin auth, sin copilot FAB) |

**Patrón a seguir** (de `page--andalucia-ei.html.twig`):
```twig
{# Directrices: ZERO-REGION-001, CSS-VAR-ALL-COLORS-001 #}
{% extends "@ecosistema_jaraba_theme/layout/page-base.html.twig" %}
{% block page_content %}
  {{ clean_messages }}
  {{ clean_content }}
{% endblock %}
```

**hook_theme_suggestions_page_alter()** ya existe en el tema y genera sugerencias basadas en ruta — verificar que las rutas nuevas matchean.

**hook_preprocess_html()** ya añade body classes por ruta — verificar que las clases se añaden para las 4 rutas nuevas.

### 3.3 Correspondencia con directrices

| Directriz | Cómo se cumple |
|-----------|---------------|
| ZERO-REGION-001 | `{{ clean_content }}` en vez de `{{ page.content }}` |
| ZERO-REGION-002 | NUNCA entity objects como non-# keys |
| ZERO-REGION-003 | drupalSettings via preprocess, NO via controller #attached |
| TWIG-INCLUDE-ONLY-001 | Header/footer incluidos con `only` |

---

## 4. Sprint E2 — API Endpoint Kanban + Slide-Panel Entities {#4-sprint-e2}

### 4.1 API Endpoint Kanban PATCH

**Problema:** `prospeccion-pipeline.js` (línea ~95) espera `PATCH /api/v1/andalucia-ei/prospeccion/{id}/fase` pero la ruta NO existe.

**Solución:**

Crear ruta API + método en controller existente:

```yaml
# routing.yml
jaraba_andalucia_ei.api.prospeccion_mover_fase:
  path: '/api/v1/andalucia-ei/prospeccion/{negocio_prospectado_ei}/fase'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\ProspeccionPipelineController::moverFase'
  methods: [PATCH]
  requirements:
    _permission: 'register andalucia ei actuacion'
    _csrf_request_header_token: 'TRUE'
```

El método `moverFase(Request $request, $negocio_prospectado_ei)`:
1. Lee `nueva_fase` del body JSON
2. Valida que es una fase válida (6 valores)
3. Llama a `ProspeccionPipelineService::moverEstado()`
4. Retorna `JsonResponse` con éxito/error

**Directrices aplicables:**
- CSRF-API-001: `_csrf_request_header_token: 'TRUE'`
- API-WHITELIST-001: Validar que `nueva_fase` está en el set permitido
- ACCESS-STRICT-001: Verificar tenant ownership

### 4.2 Slide-Panel para 3 Entities

**Problema:** NegocioProspectadoEi, PackServicioEi y EntregableFormativoEi solo tienen rutas admin. El usuario debe abandonar su dashboard para crear/editar.

**Solución:** Crear rutas con `_controller:` (NO `_form:`) que detecten `isSlidePanelRequest()` y usen `renderPlain()` (SLIDE-PANEL-RENDER-002).

Para cada entity, crear 1 ruta:

| Entity | Ruta slide-panel | Controller |
|--------|-----------------|-----------|
| NegocioProspectadoEi | `/andalucia-ei/prospeccion/{id}/form` | `CoordinadorFormController::handleNegocioForm()` |
| PackServicioEi | `/andalucia-ei/pack/{id}/form` | `CoordinadorFormController::handlePackForm()` |
| EntregableFormativoEi | `/andalucia-ei/entregable/{id}/validar` | `CoordinadorFormController::handleEntregableValidacion()` |

**Patrón (de CoordinadorFormController existente):**
```php
public function handleNegocioForm(Request $request, string $id = 'add'): Response|array {
    if ($this->isSlidePanelRequest($request)) {
        $form['#action'] = $request->getRequestUri();
        return new Response($this->renderer->renderPlain($form), 200, [...]);
    }
    return $form;
}
```

---

## 5. Sprint E3 — JS Consumers (Calculadora + Prompt Chips) {#5-sprint-e3}

### 5.1 Calculadora Punto de Equilibrio (JS)

**Problema:** `drupalSettings.jarabaAndaluciaEi.calculadora` está inyectado pero NO hay JS que lo renderice.

**Solución:** Crear `js/calculadora-pe.js` con Drupal.behaviors que:
1. Lee `drupalSettings.jarabaAndaluciaEi.calculadora.packPricing` y `gastosFijos`
2. Renderiza un widget interactivo en `[data-calculadora-pe]` (selector en el template)
3. El usuario selecciona pack + modalidad → ve punto de equilibrio + 4 escenarios
4. Usa `Drupal.t()` para textos traducibles
5. Usa `Drupal.checkPlain()` para datos dinámicos (INNERHTML-XSS-001)

**Template:** Añadir `<div data-calculadora-pe></div>` en `participante-portal.html.twig`

**Library:** Nueva `jaraba_andalucia_ei/calculadora-pe` con dependency `core/drupalSettings`

### 5.2 Prompt Chips en Copilot (JS)

**Problema:** 69 prompts en `drupalSettings.jarabaAndaluciaEi.sessionPrompts` pero el copilot no los muestra.

**Solución:** Extender el JS del copilot (`copilot-chat-widget.js` o crear parcial `copilot-session-chips.js`) para:
1. Leer `drupalSettings.jarabaAndaluciaEi.sessionPrompts`
2. Detectar la sesión actual del participante (desde `drupalSettings.jarabaAndaluciaEi.currentSession`)
3. Renderizar 3 chips sugeridos debajo del input del chat
4. Al hacer clic, insertar el texto en el input y enviar automáticamente
5. Reemplazar variables `{pack_confirmado}`, `{sector}`, `{nombre}` con datos del contexto

**Inyección de sesión actual:** En `hook_page_attachments_alter`, añadir `currentSession` basado en el `estado_programa_2e` del participante autenticado.

---

## 6. Sprint E4 — Compliance Normativa P1 (8 gaps) {#6-sprint-e4}

### 6.1 Constantes normativas a añadir

Todas en `AsistenciaComplianceService.php` o servicios existentes:

```php
// ATT-06: Pautas §5.1.A, B
public const MAX_PERSONAS_GRUPO = 25;

// ATT-11: Pautas §5.1.B.1
public const COSTE_MAX_HORA_ALUMNO = 11.0; // EUR

// INS-04: BBRR §5.a.4.b
public const JORNADA_PARCIAL_MINIMA = 0.5; // media jornada

// PER-01: Pautas §3.4
public const RATIO_TECNICO_PROYECTOS = 60; // 1 técnico por 60 proyectos
```

### 6.2 Validaciones a implementar

| ID | Validación | Dónde implementar |
|----|-----------|-------------------|
| ATT-06 | `max_plazas <= 25` en SesionProgramadaEi presave | `.module` hook_presave |
| ATT-07 | No sesiones presenciales en fin de semana/festivos | `SesionProgramadaService::validateFecha()` |
| ATT-08 | No formación online en tardes/fines de semana/festivos | Mismo servicio |
| ATT-11 | Coste máximo 11€/alumno/hora | `JustificacionEconomicaService` |
| INS-04 | Cómputo proporcional jornada parcial | `InsercionValidatorService` nuevo método |
| INS-05 | Exclusión auto-contratación (entidad propia/vinculadas) | `InsercionValidatorService` |
| PER-01 | Validación ratio 1:60 técnico/proyectos | `AlertasNormativasService` |
| PER-03 | Validación jornada completa personal técnico | `StaffProfileEi` presave |

### 6.3 Correspondencia normativa

| Gap | Artículo normativo | Servicio | Método |
|-----|-------------------|---------|--------|
| ATT-06 | Pautas §5.1.A, §5.1.B, §5.1.B.1 | AsistenciaComplianceService | validateGrupoSize() |
| ATT-07/08 | Pautas §5.1.B.1, §5.1.B.2 | SesionProgramadaService | validateHorarioNormativo() |
| ATT-11 | Pautas §5.1.B.1 | JustificacionEconomicaService | validateCosteMaximo() |
| INS-04 | BBRR §5.a.4.b | InsercionValidatorService | computoProporcionalJornada() |
| INS-05 | BBRR §5.a.4.c | InsercionValidatorService | validateNoAutocontratacion() |
| PER-01 | Pautas §3.4 | AlertasNormativasService | checkRatioTecnico() |
| PER-03 | Pautas §3.4 | StaffProfileEi | presave validation |

---

## 7. Sprint E5 — Formularios SAE como Entities {#7-sprint-e5}

### 7.1 Entities de formularios normativos

3 ConfigEntities representando los formularios ODT del SAE:

| Entity | Tipo | Fuente ODT | Campos clave |
|--------|------|-----------|-------------|
| AcuerdoParticipacionEi | ContentEntity | Acuerdo_participacion_ICV25.odt | participante_id, fecha_firma, firmado_digital, pdf_url |
| DaciEi | ContentEntity | Anexo_DACI_ICV25.odt | staff_profile_id, fecha_firma, firmado_digital, pdf_url |
| ReciboIncentivEi | ContentEntity | Recibi_Incentivo_ICV25.odt + Renuncia_Incentivo_ICV25.odt | participante_id, tipo (recibo/renuncia), importe, fecha, firmado_digital |

**Nota:** Estos formularios YA existen parcialmente como servicios (`AcuerdoParticipacionService`, `DaciService`) pero sin entity backing ni firma digital integrada. La implementación completa los conecta con el flujo de firma electrónica ya existente (`FirmaWorkflowService`).

---

## 8. Tabla de Correspondencia con Directrices {#8-directrices}

| Directriz | Sprint | Cómo se cumple |
|-----------|--------|---------------|
| ZERO-REGION-001 | E1 | 4 page templates con clean_content |
| ZERO-REGION-003 | E3 | drupalSettings para calculadora + prompts |
| SLIDE-PANEL-RENDER-002 | E2 | _controller: + isSlidePanelRequest() + renderPlain() |
| CSRF-API-001 | E2 | _csrf_request_header_token: 'TRUE' en API PATCH |
| API-WHITELIST-001 | E2 | Validar nueva_fase contra set permitido |
| CSS-VAR-ALL-COLORS-001 | E3 | SCSS calculadora con var(--ej-*, fallback) |
| ICON-CONVENTION-001 | E1-E3 | jaraba_icon() duotone en todos los templates |
| TWIG-INCLUDE-ONLY-001 | E1 | Header/footer con `only` keyword |
| INNERHTML-XSS-001 | E3 | Drupal.checkPlain() en JS para datos API |
| ROUTE-LANGPREFIX-001 | E2 | URLs via Url::fromRoute() en JS via drupalSettings |
| NO-HARDCODE-PRICE-001 | E3 | Precios desde CalculadoraPuntoEquilibrioService constants |
| CONTROLLER-READONLY-001 | E2 | Sin readonly en props heredadas |
| PREMIUM-FORMS-PATTERN-001 | E2 | PremiumEntityFormBase para slide-panel forms |
| UPDATE-HOOK-CATCH-001 | E4 | try-catch \Throwable en presave hooks |
| PIPELINE-E2E-001 | Todos | Verificación L1→L7 por componente |
| RUNTIME-VERIFY-001 | Todos | 20 checks en navegador post-implementación |

---

## 9. Tabla de Correspondencia con Especificaciones Técnicas {#9-specs}

| SPEC ID | Título | Sprint | Ficheros nuevos | Ficheros modificados |
|---------|--------|--------|----------------|---------------------|
| SPEC-E1-001 | Page templates zero-region | E1 | 4 .html.twig en tema | .theme (preprocess) |
| SPEC-E2-001 | API endpoint Kanban PATCH | E2 | 0 | routing.yml + ProspeccionPipelineController |
| SPEC-E2-002 | Slide-panel NegocioProspectadoEi | E2 | 0 | routing.yml + CoordinadorFormController |
| SPEC-E2-003 | Slide-panel PackServicioEi | E2 | 0 | routing.yml + CoordinadorFormController |
| SPEC-E2-004 | Slide-panel EntregableFormativoEi | E2 | 0 | routing.yml + CoordinadorFormController |
| SPEC-E3-001 | JS calculadora punto equilibrio | E3 | calculadora-pe.js | libraries.yml + portal template |
| SPEC-E3-002 | JS prompt chips copilot | E3 | copilot-session-chips.js | libraries.yml + attachments |
| SPEC-E4-001 | Validación grupo max 25 | E4 | 0 | .module presave |
| SPEC-E4-002 | Validación horarios normativos | E4 | 0 | SesionProgramadaService |
| SPEC-E4-003 | Coste máximo 11€/alumno/hora | E4 | 0 | JustificacionEconomicaService |
| SPEC-E4-004 | Jornada parcial proporcional | E4 | 0 | InsercionValidatorService |
| SPEC-E4-005 | Exclusión auto-contratación | E4 | 0 | InsercionValidatorService |
| SPEC-E4-006 | Ratio técnico 1:60 | E4 | 0 | AlertasNormativasService |
| SPEC-E5-001 | AcuerdoParticipacionEi entity | E5 | Entity + ACH + Form | routing.yml + services.yml |
| SPEC-E5-002 | DaciEi entity | E5 | Entity + ACH + Form | routing.yml + services.yml |
| SPEC-E5-003 | ReciboIncentivoEi entity | E5 | Entity + ACH + Form | routing.yml + services.yml |

---

## 10. Verificación RUNTIME-VERIFY-001 en Navegador {#10-verificacion}

### 10.1 Checks por Sprint

**Sprint E1 (4 checks):**
1. Visitar `/andalucia-ei/prueba-gratuita` sin auth → página limpia sin toolbar/sidebar
2. Visitar `/andalucia-ei/coordinador/prospeccion-pipeline` con auth → Kanban sin sidebar admin
3. Visitar `/andalucia-ei/formador` con auth → dashboard formador sin sidebar
4. Visitar `/portfolio/1` sin auth → portfolio público limpio

**Sprint E2 (4 checks):**
5. Drag-and-drop tarjeta en Kanban → verificar que al refrescar la tarjeta mantiene su posición
6. Desde dashboard coordinador, click "Registrar negocio" → se abre slide-panel (no navega fuera)
7. Desde portal participante, click "Editar pack" → se abre slide-panel
8. Desde dashboard formador, click "Validar entregable" → se abre slide-panel

**Sprint E3 (4 checks):**
9. En portal participante, ver widget calculadora con selector de pack → mostrar escenarios
10. En chat copilot, ver 3 chips sugeridos debajo del input → al hacer clic, se insertan
11. Cambiar pack en calculadora → escenarios se actualizan dinámicamente
12. Los chips cambian según la fase del programa del participante

**Sprint E4 (4 checks):**
13. Intentar crear sesión presencial en sábado → validación impide guardar
14. Intentar crear grupo con 30 personas → validación impide guardar
15. Registrar inserción en PED S.L. → warning de auto-contratación
16. Dashboard coordinador muestra alerta si ratio técnico >60

**Sprint E5 (4 checks):**
17. Desde portal participante, firmar Acuerdo Participación digital
18. Desde dashboard coordinador, ver estado firmas pendientes
19. Generar PDF de Acuerdo con logos FSE+ institucionales
20. Recibo incentivo genera con importes correctos (528€ - 2% IRPF)

---

## 11. Salvaguardas Propuestas {#11-salvaguardas}

### 11.1 Validador: validate-frontend-chrome.php

**Propósito:** Para cada ruta en routing.yml de jaraba_andalucia_ei que NO sea `/admin/*` ni `/api/*`, verificar que existe un page template en el tema que matchea el patrón `page--{ruta}.html.twig`.

**Detección:** Rutas frontend sin page template = páginas con chrome admin visible.

### 11.2 Validador: validate-drupal-settings-consumers.php

**Propósito:** Para cada `drupalSettings['jarabaAndaluciaEi']` key inyectada en `hook_page_attachments_alter`, verificar que existe un fichero JS en el módulo que referencia esa key.

**Detección:** drupalSettings inyectados sin JS consumer = datos invisibles.

### 11.3 Validador: validate-api-endpoint-js-parity.php

**Propósito:** Para cada `fetch()` o `Drupal.ajax()` en ficheros JS del módulo, extraer la URL y verificar que existe una ruta correspondiente en routing.yml.

**Detección:** JS que llama a endpoints inexistentes = funcionalidad que falla silenciosamente.

### 11.4 Pre-commit hook mejorado

Añadir a `.lintstagedrc.json`:
- Para `*.routing.yml`: verificar que toda ruta frontend tiene page template en tema
- Para `*.js`: verificar que toda URL en fetch() existe en routing.yml

---

## 12. Glosario de Siglas {#12-glosario}

| Sigla | Significado |
|-------|------------|
| ACH | Access Control Handler — clase PHP que controla permisos |
| BBRR | Bases Reguladoras — normativa base del programa PIIL |
| BOJA | Boletín Oficial de la Junta de Andalucía |
| CSRF | Cross-Site Request Forgery — protección contra peticiones fraudulentas |
| DACI | Declaración de Ausencia de Conflicto de Interés |
| DOM | Document Object Model — representación del HTML en el navegador |
| E2E | End-to-End — verificación completa de extremo a extremo |
| FSE+ | Fondo Social Europeo Plus — instrumento UE que cofinancia al 85% |
| ICV | Itinerarios Comprensivos Vulnerables — tipo de programa PIIL |
| JS | JavaScript |
| L1-L7 | Layers 1-7 — capas de verificación del pipeline E2E |
| ODT | OpenDocument Text — formato de documento abierto |
| PATCH | Método HTTP para actualización parcial de recursos |
| PE | Punto de Equilibrio — análisis financiero break-even |
| PIIL | Programa Integral de Inserción Laboral |
| SAE | Servicio Andaluz de Empleo |
| SCSS | Sassy CSS — preprocesador CSS (Dart Sass moderno) |
| STO | Servicio Técnico de Orientación — sistema informático SAE |
| TOC | Table of Contents — índice de contenidos |
| VoBo | Visto Bueno — autorización previa del SAE |

---

## Estimación de esfuerzo

| Sprint | Ficheros nuevos | Ficheros modificados | Complejidad |
|--------|----------------|---------------------|-------------|
| E1 | 4 Twig | 2 (.theme, .module) | Baja |
| E2 | 0 | 3 (routing, controller ×2) | Media |
| E3 | 2 JS | 3 (libraries, templates, attachments) | Alta |
| E4 | 0 | 6 (servicios existentes) | Media |
| E5 | 9 PHP + 3 Twig | 4 (routing, services, .module, .install) | Alta |
| **Total** | **~18** | **~18** | |

**Orden de ejecución recomendado:** E1 → E2 → E4 → E3 → E5

E1 primero porque desbloquea la experiencia visual correcta.
E2 segundo porque desbloquea la funcionalidad interactiva del Kanban.
E4 tercero porque es compliance normativa (más urgente que UX polish).
E3 cuarto porque es UX enhancement (calculadora + chips).
E5 quinto porque depende de que todo lo anterior funcione.

---

*Plan generado por Claude Opus 4.6 (1M context) — 2026-03-25*
*Directriz raíz: "La diferencia entre 'el código existe' y 'el usuario lo experimenta' requiere verificación en CADA capa."*
