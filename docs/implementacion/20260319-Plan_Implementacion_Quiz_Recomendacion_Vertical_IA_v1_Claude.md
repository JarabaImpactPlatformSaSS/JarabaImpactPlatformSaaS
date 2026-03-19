# Plan de Implementación: Quiz de Recomendación de Vertical con IA

**Versión:** 1.0
**Fecha:** 2026-03-19
**Autor:** Claude Opus 4.6 (arquitecto SaaS + UX + IA)
**Estado:** Aprobado para implementación
**Prioridad:** P0 — Pieza crítica del funnel de conversión
**Módulo principal:** `ecosistema_jaraba_core`
**Ruta frontend:** `/test-vertical`
**Dependencias cross-módulo:** `jaraba_crm` (opcional @?), `jaraba_analytics` (opcional @?), `jaraba_predictive` (opcional @?)

---

## TOC — Índice de Navegación

1. [Contexto y justificación estratégica](#1-contexto-y-justificación-estratégica)
2. [Análisis del gap de conversión actual](#2-análisis-del-gap-de-conversión-actual)
3. [Arquitectura de la solución](#3-arquitectura-de-la-solución)
4. [Diseño del quiz — Preguntas y lógica de scoring](#4-diseño-del-quiz--preguntas-y-lógica-de-scoring)
5. [Entity: QuizResult](#5-entity-quizresult)
6. [Service: VerticalQuizService](#6-service-verticalquizservice)
7. [Controller: VerticalQuizController](#7-controller-verticalquizcontroller)
8. [Frontend — Template Twig + SCSS + JS](#8-frontend--template-twig--scss--js)
9. [Integración CRM + Lead Attribution](#9-integración-crm--lead-attribution)
10. [Integración IA — Recomendación personalizada](#10-integración-ia--recomendación-personalizada)
11. [Setup Wizard + Daily Actions](#11-setup-wizard--daily-actions)
12. [Mega Menú — Promo card actualizada](#12-mega-menú--promo-card-actualizada)
13. [Mobile-first y accesibilidad](#13-mobile-first-y-accesibilidad)
14. [Tracking y analytics](#14-tracking-y-analytics)
15. [Safeguards y validación](#15-safeguards-y-validación)
16. [Auditoría de conversión homepage — Gap analysis 10/10](#16-auditoría-de-conversión-homepage--gap-analysis-1010)
17. [Tabla de correspondencia de directrices](#17-tabla-de-correspondencia-de-directrices)
18. [Verificación RUNTIME-VERIFY-001](#18-verificación-runtime-verify-001)
19. [Orden de ejecución (sprints)](#19-orden-de-ejecución-sprints)
20. [Safeguards propuestas para el futuro](#20-safeguards-propuestas-para-el-futuro)

---

## 1. Contexto y justificación estratégica

### 1.1 El problema

El mega menú del SaaS principal incluye un promo card "¿No sabes cuál elegir?" que promete ayudar al visitante a encontrar su vertical ideal. Sin embargo:

1. **No existe ningún test/quiz** — el CTA original enviaba al registro directo (`/user/register`), generando fricción alta sin ofrecer valor previo.
2. **El vertical selector del homepage es pasivo** — muestra 6 tarjetas pero no guía al usuario. Requiere que el visitante ya sepa qué necesita.
3. **El visitor-vertical-detection.js es automático pero invisible** — detecta el vertical por UTM/path/referrer pero el usuario no lo sabe ni participa.
4. **Gap en el CRM** — no existe entidad de cualificación pre-registro. El campo `Contact.source` se asigna manualmente. No hay auto-atribución.

### 1.2 La solución: Quiz de valor inmediato + cualificación CRM

Un quiz interactivo de 4 preguntas que:

- **Da valor inmediato**: El usuario recibe una recomendación personalizada con explicación ("Tu vertical ideal es JarabaLex porque tu perfil legal encaja con...")
- **Reduce fricción**: El usuario entiende QUÉ vertical le conviene ANTES de registrarse. La decisión ya está tomada → el registro es solo el paso lógico siguiente.
- **Cualifica al lead**: Cada respuesta genera datos trazables en el CRM (sector, tamaño, necesidad, urgencia). El equipo comercial sabe exactamente qué necesita cada lead.
- **Alimenta la IA**: Los datos del quiz enriquecen el contexto del copilot post-registro. La primera interacción con el copilot ya es personalizada.

### 1.3 Patrón de referencia

Inspirado en los mejores quizzes de cualificación SaaS:
- **HubSpot "Website Grader"**: Valor inmediato + captura de email al mostrar resultado
- **Typeform "Product Finder"**: UX progresiva, una pregunta por pantalla, transiciones fluidas
- **Intercom "Solutions Finder"**: Recomendación + explicación personalizada + CTA contextual

### 1.4 Métricas objetivo

| Métrica | Baseline (sin quiz) | Objetivo (con quiz) |
|---------|---------------------|---------------------|
| Bounce rate desde mega menú | ~70% (estimado) | < 40% |
| Tasa de registro desde visitantes mega menú | ~2% | > 8% |
| Lead cualificado con datos CRM | 0% | 100% de quienes completan el quiz |
| TTFV post-registro | ~120s (setup wizard) | < 30s (copilot personalizado) |

---

## 2. Análisis del gap de conversión actual

### 2.1 Funnel actual del homepage (11 secciones)

| # | Sección | CTAs | Estado |
|---|---------|------|--------|
| 1 | Hero | Registrarse / Ver demo | ✅ Diferenciado logged_in |
| 2 | Audience Selector (PED) | 4 audiencias | ✅ Cookie 30 días |
| 3 | Stats | Ninguno | ✅ Social proof puro |
| 4 | How It Works | Empieza gratis / Dashboard | ✅ Diferenciado logged_in |
| 5 | Vertical Highlights (Legal/B2G) | Prueba JarabaLex / Demo institucional | ✅ Audiencia-específico |
| 6 | Product Demo | Demo completa / Crear cuenta | ✅ Diferenciado logged_in |
| 7 | Testimonials | Ver todos los casos | ✅ |
| 8 | Trust Bar + Partners | Ninguno | ✅ Credibilidad |
| 9 | Lead Magnet | Descargar Kit Gratuito | ✅ Email capture + avatar |
| 10 | FAQ | Ninguno | ✅ Manejo de objeciones |
| 11 | Final CTA Banner | Crear cuenta / Demo | ✅ Diferenciado logged_in |

### 2.2 Gaps identificados para 10/10

| # | Gap | Impacto | Solución en este plan |
|---|-----|---------|----------------------|
| G1 | **No hay quiz/test de recomendación** | El mega menú promete algo que no existe | Quiz interactivo en `/test-vertical` |
| G2 | **Lead source no se auto-atribuye al CRM** | Equipo comercial no sabe de dónde viene el lead | Auto-crear Contact + Opportunity al completar quiz |
| G3 | **Vertical selector es pasivo** | El usuario debe saber qué necesita | Quiz activo que pregunta y recomienda |
| G4 | **No hay personalización post-registro** | El copilot arranca genérico | Quiz data → enriches first copilot interaction |
| G5 | **Demo lead gate no captura vertical intent** | Solo captura email + profile | Quiz antes de demo = lead mejor cualificado |
| G6 | **Mega menú promo card para logueados es genérica** | "Ver catálogo" sin contexto | Para logueados → quiz recomienda add-on verticals |
| G7 | **No hay safeguard para iconos** | Chinchetas recurrentes | ICON-INTEGRITY-001 (ya implementado) |
| G8 | **No hay safeguard para funnel quiz** | Quiz podría romperse sin detectar | QUIZ-FUNNEL-001 (nuevo) |

---

## 3. Arquitectura de la solución

### 3.1 Diagrama de flujo

```
                          ┌────────────────────┐
                          │  Mega Menú Promo    │
                          │  "¿No sabes cuál    │
                          │   elegir?"          │
                          └─────────┬──────────┘
                                    │
                          ┌─────────▼──────────┐
                          │  /test-vertical     │
                          │  (Quiz 4 preguntas) │
                          │                     │
                          │  Q1: ¿Quién eres?   │
                          │  Q2: ¿Tu sector?    │
                          │  Q3: ¿Qué necesitas?│
                          │  Q4: ¿Cuándo?       │
                          └─────────┬──────────┘
                                    │
                     ┌──────────────┼──────────────┐
                     │              │              │
              ┌──────▼──────┐ ┌────▼────┐  ┌──────▼──────┐
              │ Scoring     │ │ IA      │  │ CRM         │
              │ (reglas     │ │ (genera │  │ (Contact +  │
              │  estáticas) │ │  texto  │  │  Opportunity│
              │             │ │  pers.) │  │  + source)  │
              └──────┬──────┘ └────┬────┘  └──────┬──────┘
                     │             │              │
                     └──────┬──────┘              │
                            │                     │
                  ┌─────────▼──────────┐          │
                  │  Pantalla resultado │          │
                  │  "Tu vertical ideal│◄─────────┘
                  │   es JarabaLex"    │
                  │                    │
                  │  [Razón person.]   │
                  │  [3 beneficios]    │
                  │  [Precio desde]    │
                  │  [Social proof]    │
                  └────────┬───────────┘
                           │
              ┌────────────┼────────────┐
              │ Anónimo    │ Logueado   │
              ▼            ▼            │
    ┌─────────────┐ ┌──────────────┐   │
    │ Email gate  │ │ "Activar     │   │
    │ + CTA       │ │  vertical"   │   │
    │ "Empieza    │ │ → /addons    │   │
    │  gratis"    │ │              │   │
    │ → /registro │ └──────────────┘   │
    │  /{vertical}│                    │
    │  ?source=   │                    │
    │   quiz      │                    │
    └─────────────┘                    │
                                       │
              ┌────────────────────────┘
              │ Datos persisten:
              │ - QuizResult entity (UUID)
              │ - sessionStorage (browser)
              │ - CRM Contact.source = 'quiz_vertical'
              │ - Opportunity con BANT parcial
              │ - AnalyticsEvent con UTM
              └──────────────────────────
```

### 3.2 Decisiones de arquitectura

| Decisión | Opción elegida | Justificación |
|----------|---------------|---------------|
| Motor de scoring | **Reglas estáticas + IA para texto** | Reglas → resultado instantáneo (0ms). IA → genera explicación personalizada (async, no bloquea UX). Best of both worlds. |
| Persistencia pre-registro | **QuizResult entity con UUID** | Trazable incluso si no se registra. UUID en cookie → vincular al user al registrarse. Útil para retargeting y analytics de funnel. |
| Número de preguntas | **4 preguntas** | Trade-off: 3 = poco datos para cualificar. 5+ = drop-off alto. 4 = sweet spot (< 60 segundos, suficiente para BANT parcial). |
| UX | **Una pregunta por pantalla** | Patrón Typeform. Progress bar visual. Reduce cognitive load. Mobile-first. |
| Email capture | **En pantalla de resultado (no antes)** | Valor primero → email después. El usuario ya tiene la recomendación → dar email es el paso natural para "implementarla". |
| Para logueados | **Quiz completo + CTA "Activar vertical"** | Los logueados también pueden descubrir verticales nuevos → upsell de add-ons. |

### 3.3 Stack técnico

| Capa | Tecnología | Directriz |
|------|-----------|-----------|
| Backend entity | QuizResult (ContentEntity) | UPDATE-HOOK-REQUIRED-001, ENTITY-FK-001 |
| Service | VerticalQuizService | OPTIONAL-CROSSMODULE-001 (CRM @?) |
| Controller | VerticalQuizController | CONTROLLER-READONLY-001, ZERO-REGION-001 |
| Template | page--test-vertical.html.twig | Clean page, sin regiones, sin bloques |
| Parcial | _quiz-step.html.twig, _quiz-result.html.twig | TWIG-INCLUDE-ONLY-001 |
| SCSS | scss/routes/quiz.scss → css/routes/quiz.css | CSS-VAR-ALL-COLORS-001, SCSS-001, SCSS-COMPILE-VERIFY-001 |
| JS | js/vertical-quiz.js | Vanilla JS + Drupal.behaviors, CSRF-JS-CACHE-001 |
| IA | Claude API via ModelRouterService (tier: fast) | MODEL-ROUTING-CONFIG-001, AI-GUARDRAILS-PII-001 |
| CRM | Contact + Opportunity auto-creation | OPTIONAL-CROSSMODULE-001 |
| Analytics | AnalyticsEvent + funnel tracking | data-track-cta, data-track-position |
| Iconos | jaraba_icon() duotone | ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001 |
| i18n | t() en PHP, {% trans %} en Twig | Textos siempre traducibles |

---

## 4. Diseño del quiz — Preguntas y lógica de scoring

### 4.1 Las 4 preguntas

Cada pregunta tiene opciones visuales (tarjetas con icono duotone + texto corto). El usuario selecciona con un clic/tap → avanza automáticamente.

#### Pregunta 1: ¿Cuál es tu perfil? (Identidad)

| Opción | Icono | Valor | Verticales afectados |
|--------|-------|-------|---------------------|
| Busco empleo o quiero crecer profesionalmente | `verticals/empleabilidad` verde-innovacion | `perfil=persona` | empleabilidad +3, formacion +2 |
| Tengo una idea de negocio o soy emprendedor | `verticals/emprendimiento` naranja-impulso | `perfil=emprendedor` | emprendimiento +3, comercioconecta +1 |
| Tengo un negocio o empresa | `verticals/comercioconecta` naranja-impulso | `perfil=empresa` | comercioconecta +2, serviciosconecta +2, agroconecta +1 |
| Soy profesional del derecho | `verticals/jarabalex` azul-corporativo | `perfil=legal` | jarabalex +4 |
| Trabajo en una institución pública | `verticals/andalucia-ei` verde-innovacion | `perfil=institucion` | andalucia_ei +4 |
| Creo contenido o tengo un medio | `content/edit` azul-corporativo | `perfil=creador` | jaraba_content_hub +4 |

#### Pregunta 2: ¿En qué sector operas? (Industria)

| Opción | Icono | Valor | Verticales afectados |
|--------|-------|-------|---------------------|
| Agroalimentario / Campo | `verticals/agroconecta` verde-oliva | `sector=agro` | agroconecta +4 |
| Comercio / Retail | `commerce/cart` naranja-impulso | `sector=comercio` | comercioconecta +3 |
| Servicios profesionales | `business/briefcase` azul-corporativo | `sector=servicios` | serviciosconecta +3 |
| Legal / Jurídico | `verticals/jarabalex` azul-corporativo | `sector=legal` | jarabalex +3 |
| Educación / Formación | `verticals/formacion` verde-innovacion | `sector=educacion` | formacion +3 |
| Sector público / ONG | `verticals/andalucia-ei` verde-innovacion | `sector=publico` | andalucia_ei +3 |
| Tecnología / Digital | `business/target` naranja-impulso | `sector=tech` | emprendimiento +2, jaraba_content_hub +1 |
| Otro | `general/globe` azul-corporativo | `sector=otro` | (sin bonus, usa perfil) |

#### Pregunta 3: ¿Qué necesitas resolver primero? (Necesidad principal — mapea a BANT Need)

| Opción | Icono | Valor | Verticales afectados |
|--------|-------|-------|---------------------|
| Encontrar empleo o formación | `verticals/empleabilidad` verde-innovacion | `necesidad=empleo` | empleabilidad +3, formacion +2 |
| Vender online o digitalizar mi negocio | `commerce/cart` naranja-impulso | `necesidad=vender` | comercioconecta +3, agroconecta +2 |
| Gestionar clientes o proyectos | `business/clipboard` azul-corporativo | `necesidad=gestionar` | serviciosconecta +3, emprendimiento +1 |
| Automatizar con IA | `ai/brain` naranja-impulso | `necesidad=ia` | jarabalex +2, emprendimiento +2, jaraba_content_hub +1 |
| Gestionar programas de empleo / FSE | `verticals/andalucia-ei` verde-innovacion | `necesidad=programas` | andalucia_ei +4 |
| Crear contenido o presencia digital | `content/edit` azul-corporativo | `necesidad=contenido` | jaraba_content_hub +3, emprendimiento +1 |

#### Pregunta 4: ¿Cuándo quieres empezar? (Urgencia — mapea a BANT Timeline)

| Opción | Icono | Valor | BANT timeline |
|--------|-------|-------|---------------|
| Ahora mismo | `actions/check-circle` verde-innovacion | `urgencia=inmediata` | `immediate` |
| En las próximas semanas | `ui/clock` naranja-impulso | `urgencia=semanas` | `3mo` |
| Estoy explorando opciones | `general/globe` azul-corporativo | `urgencia=explorando` | `6mo` |

### 4.2 Algoritmo de scoring

```php
// Cada vertical empieza en 0 puntos.
// Las respuestas suman puntos según la tabla anterior.
// El vertical con más puntos gana.
// En caso de empate, prioridad por ARPU (jarabalex > comercio > emprendimiento > ...).

$scores = array_fill_keys([
    'empleabilidad', 'emprendimiento', 'comercioconecta', 'agroconecta',
    'jarabalex', 'serviciosconecta', 'formacion', 'andalucia_ei',
    'jaraba_content_hub',
], 0);

// Sumar puntos de cada respuesta...
// Resolver empate por prioridad ARPU...
// Devolver top 1 (recomendación principal) + top 2 y 3 (alternativas).
```

### 4.3 Datos que se capturan para CRM

| Campo quiz | Mapeo CRM (Contact/Opportunity) | Uso comercial |
|------------|--------------------------------|---------------|
| perfil | Contact.job_title (inferido) + Opportunity.bant_authority | Segmentación |
| sector | Company.industry | Vertical routing |
| necesidad | Opportunity.bant_need | Priorización |
| urgencia | Opportunity.bant_timeline | Sales velocity |
| vertical_recomendado | Opportunity.stage = 'mql' | Pipeline |
| email (si lo da) | Contact.email + Contact.source='quiz_vertical' | Contacto |

---

## 5. Entity: QuizResult

### 5.1 Definición

```php
/**
 * @ContentEntityType(
 *   id = "quiz_result",
 *   label = @Translation("Quiz Result"),
 *   base_table = "quiz_result",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "access" = "Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   admin_permission = "administer site configuration",
 * )
 */
```

### 5.2 Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | integer (auto) | PK |
| uuid | uuid | Identificador público (cookie + URL) |
| uid | entity_reference (user) | NULL si anónimo, se vincula al registrarse |
| tenant_id | entity_reference (tenant) | NULL si anónimo, se vincula al registrarse |
| answers | map | JSON con las 4 respuestas: `{perfil, sector, necesidad, urgencia}` |
| scores | map | JSON con scores calculados por vertical |
| recommended_vertical | string | El vertical ganador |
| alternative_verticals | map | JSON con top 2 y 3 |
| ai_explanation | string_long | Texto personalizado generado por IA |
| email | string | Email capturado (NULL si no lo dio) |
| source_url | string | URL de referencia (de dónde vino al quiz) |
| utm_source | string | UTM source |
| utm_medium | string | UTM medium |
| utm_campaign | string | UTM campaign |
| ip_hash | string | SHA256(IP + date + salt) para GDPR |
| converted | boolean | TRUE si el usuario se registró después |
| crm_contact_id | integer | FK al Contact del CRM (cross-módulo opcional) |
| created | created | Timestamp |
| changed | changed | Timestamp |

### 5.3 hook_update_N()

Obligatorio por UPDATE-HOOK-REQUIRED-001. Se creará `ecosistema_jaraba_core_update_100XX()` con `installEntityType()` para la entity QuizResult.

---

## 6. Service: VerticalQuizService

### 6.1 Responsabilidades

```php
class VerticalQuizService {

    // 1. Calcular scores basado en respuestas
    public function calculateScores(array $answers): array;

    // 2. Obtener recomendación (top 1 + alternativas)
    public function getRecommendation(array $scores): array;

    // 3. Generar explicación personalizada con IA (async)
    public function generateAiExplanation(array $answers, string $vertical): string;

    // 4. Persistir resultado
    public function saveResult(array $answers, array $scores, string $vertical, ?string $email): QuizResult;

    // 5. Vincular resultado a usuario post-registro
    public function linkResultToUser(string $uuid, int $uid, int $tenantId): void;

    // 6. Crear lead en CRM (si jaraba_crm disponible)
    public function createCrmLead(QuizResult $result): void;

    // 7. Obtener datos del resultado por UUID
    public function getResultByUuid(string $uuid): ?QuizResult;

    // 8. Obtener preguntas con opciones (estructura para frontend)
    public function getQuizQuestions(): array;

    // 9. Obtener datos de vertical para pantalla de resultado
    public function getVerticalPresentation(string $vertical): array;
}
```

### 6.2 Registro en services.yml

```yaml
ecosistema_jaraba_core.vertical_quiz:
    class: Drupal\ecosistema_jaraba_core\Service\VerticalQuizService
    arguments:
        - '@entity_type.manager'
        - '@current_user'
        - '@request_stack'
        - '@?jaraba_crm.contact'
        - '@?jaraba_crm.opportunity'
        - '@?jaraba_ai_agents.model_router'
        - '@logger.channel.ecosistema_jaraba_core'
    tags:
        - { name: ecosistema_jaraba_core.service }
```

Nota: `jaraba_crm.contact`, `jaraba_crm.opportunity` y `jaraba_ai_agents.model_router` son opcionales (`@?`) para cumplir OPTIONAL-CROSSMODULE-001.

---

## 7. Controller: VerticalQuizController

### 7.1 Rutas

```yaml
# En ecosistema_jaraba_core.routing.yml

ecosistema_jaraba_core.quiz_vertical:
    path: '/test-vertical'
    defaults:
        _controller: '\Drupal\ecosistema_jaraba_core\Controller\VerticalQuizController::quizPage'
        _title: 'Descubre tu vertical ideal'
    requirements:
        _access: 'TRUE'

ecosistema_jaraba_core.quiz_vertical.submit:
    path: '/api/v1/quiz/submit'
    defaults:
        _controller: '\Drupal\ecosistema_jaraba_core\Controller\VerticalQuizController::submitQuiz'
    methods: [POST]
    requirements:
        _access: 'TRUE'
    options:
        _csrf_request_header_token: 'TRUE'

ecosistema_jaraba_core.quiz_vertical.result:
    path: '/test-vertical/resultado/{uuid}'
    defaults:
        _controller: '\Drupal\ecosistema_jaraba_core\Controller\VerticalQuizController::resultPage'
        _title: 'Tu recomendación personalizada'
    requirements:
        _access: 'TRUE'
        uuid: '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'
```

### 7.2 Patrón ZERO-REGION-001

El controller devuelve markup mínimo. Los datos se inyectan en `hook_preprocess_page()` para la ruta, no en el render array del controller.

```php
public function quizPage(): array {
    return ['#type' => 'markup', '#markup' => ''];
}

public function resultPage(string $uuid): array {
    return ['#type' => 'markup', '#markup' => ''];
}
```

Todo el contenido viene de:
- `page--test-vertical.html.twig` (quiz)
- `page--test-vertical--resultado.html.twig` (resultado)
- Variables inyectadas desde `hook_preprocess_page()` → `drupalSettings` para JS

### 7.3 API submit (POST)

```php
public function submitQuiz(Request $request): JsonResponse {
    // 1. Validar input (4 respuestas obligatorias)
    // 2. Rate limit: 10 req/min per IP (FloodInterface)
    // 3. calculateScores() → scoring estático
    // 4. getRecommendation() → top 3 verticals
    // 5. saveResult() → QuizResult entity con UUID
    // 6. generateAiExplanation() → async si IA disponible
    // 7. createCrmLead() → si jaraba_crm disponible
    // 8. Return { uuid, vertical, alternatives, ai_explanation }
}
```

---

## 8. Frontend — Template Twig + SCSS + JS

### 8.1 Template: page--test-vertical.html.twig

Página frontend limpia (ZERO-REGION-001). Sin page.content, sin bloques heredados.

```twig
{# Estructura limpia: header, quiz container, footer #}
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with { ... } only %}

<main class="quiz-page" data-quiz-config="{{ quiz_config|json_encode }}">
    <div class="quiz-page__container">
        {# Progress bar #}
        <div class="quiz-page__progress" role="progressbar"
             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
            <div class="quiz-page__progress-bar"></div>
            <span class="quiz-page__progress-text">
                {% trans %}Pregunta 1 de 4{% endtrans %}
            </span>
        </div>

        {# Steps container — JS controla visibilidad #}
        <div class="quiz-page__steps" aria-live="polite">
            {% for question in quiz_questions %}
                {% include '@ecosistema_jaraba_theme/partials/_quiz-step.html.twig' with {
                    question: question,
                    step_index: loop.index0,
                    total_steps: quiz_questions|length,
                } only %}
            {% endfor %}
        </div>

        {# Loading state (entre submit y resultado) #}
        <div class="quiz-page__loading" hidden>
            <div class="quiz-page__spinner"></div>
            <p>{% trans %}Analizando tu perfil...{% endtrans %}</p>
        </div>
    </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with { ... } only %}
```

### 8.2 Parcial: _quiz-step.html.twig

```twig
<div class="quiz-step" data-step="{{ step_index }}" {% if step_index > 0 %}hidden{% endif %}>
    <h2 class="quiz-step__title">{{ question.title }}</h2>
    <p class="quiz-step__subtitle">{{ question.subtitle }}</p>

    <div class="quiz-step__options" role="radiogroup" aria-label="{{ question.title }}">
        {% for option in question.options %}
            <button class="quiz-step__option"
                    type="button"
                    data-value="{{ option.value }}"
                    data-field="{{ question.field }}"
                    role="radio"
                    aria-checked="false"
                    data-track-cta="quiz_{{ question.field }}_{{ option.value }}"
                    data-track-position="quiz_step_{{ step_index + 1 }}">
                <span class="quiz-step__option-icon">
                    {{ jaraba_icon(option.icon_cat, option.icon_name, {
                        variant: 'duotone', size: '40px', color: option.color
                    }) }}
                </span>
                <span class="quiz-step__option-text">{{ option.label }}</span>
            </button>
        {% endfor %}
    </div>

    {% if step_index > 0 %}
        <button class="quiz-step__back" type="button">
            ← {% trans %}Volver{% endtrans %}
        </button>
    {% endif %}
</div>
```

### 8.3 Template: page--test-vertical--resultado.html.twig

Página de resultado con la recomendación personalizada.

```twig
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with { ... } only %}

<main class="quiz-result">
    {% include '@ecosistema_jaraba_theme/partials/_quiz-result.html.twig' with {
        result: quiz_result,
        logged_in: logged_in,
        language_prefix: language_prefix,
    } only %}
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with { ... } only %}
```

### 8.4 Parcial: _quiz-result.html.twig

```twig
<div class="quiz-result__container">
    {# Resultado principal #}
    <div class="quiz-result__card quiz-result__card--primary">
        <div class="quiz-result__badge">{% trans %}Tu vertical ideal{% endtrans %}</div>
        <div class="quiz-result__icon">
            {{ jaraba_icon(
                result.icon_cat, result.icon_name,
                { variant: 'duotone', size: '64px', color: result.color }
            ) }}
        </div>
        <h1 class="quiz-result__title">{{ result.vertical_title }}</h1>
        <p class="quiz-result__explanation">{{ result.ai_explanation }}</p>

        {# 3 beneficios clave #}
        <ul class="quiz-result__benefits">
            {% for benefit in result.benefits %}
                <li>
                    {{ jaraba_icon('actions', 'check-circle', {
                        variant: 'duotone', size: '20px', color: 'verde-innovacion'
                    }) }}
                    {{ benefit }}
                </li>
            {% endfor %}
        </ul>

        {# Precio desde #}
        <div class="quiz-result__pricing">
            {% trans %}Desde{% endtrans %}
            <strong>{{ result.price_from }}</strong>/{% trans %}mes{% endtrans %}
            <span class="quiz-result__pricing-note">{% trans %}Plan gratuito disponible{% endtrans %}</span>
        </div>

        {# CTA principal — diferenciado logged_in #}
        {% if logged_in %}
            <a href="{{ language_prefix }}/addons" class="btn-primary btn-primary--glow quiz-result__cta"
               data-track-cta="quiz_result_addons" data-track-position="quiz_result">
                {% trans %}Activar este vertical{% endtrans %} →
            </a>
        {% else %}
            <a href="{{ language_prefix }}/registro/{{ result.vertical_id }}?source=quiz&quiz_uuid={{ result.uuid }}"
               class="btn-primary btn-primary--glow quiz-result__cta"
               data-track-cta="quiz_result_register" data-track-position="quiz_result">
                {% trans %}Empieza gratis con {{ result.vertical_title }}{% endtrans %} →
            </a>
        {% endif %}
    </div>

    {# Alternativas #}
    {% if result.alternatives|length > 0 %}
    <div class="quiz-result__alternatives">
        <h3>{% trans %}También podría interesarte{% endtrans %}</h3>
        <div class="quiz-result__alt-grid">
            {% for alt in result.alternatives %}
                <a href="{{ language_prefix }}/{{ alt.path }}" class="quiz-result__alt-card"
                   data-track-cta="quiz_result_alt_{{ alt.id }}" data-track-position="quiz_result">
                    {{ jaraba_icon(alt.icon_cat, alt.icon_name, {
                        variant: 'duotone', size: '32px', color: alt.color
                    }) }}
                    <strong>{{ alt.title }}</strong>
                    <small>{{ alt.match_pct }}% {% trans %}de coincidencia{% endtrans %}</small>
                </a>
            {% endfor %}
        </div>
    </div>
    {% endif %}

    {# Social proof #}
    <div class="quiz-result__social-proof">
        <p>{% trans %}+50.000 profesionales ya usan Jaraba Impact Platform{% endtrans %}</p>
    </div>
</div>
```

### 8.5 SCSS: scss/routes/quiz.scss

```scss
@use '../variables' as *;

// === QUIZ PAGE ===
.quiz-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--ej-spacing-xl, 2rem);
    background: var(--ej-bg-body, #F8FAFC);
}

.quiz-page__container {
    width: 100%;
    max-width: 640px;
}

// Progress bar
.quiz-page__progress {
    margin-bottom: var(--ej-spacing-xl, 2rem);
}

.quiz-page__progress-bar {
    height: 4px;
    background: color-mix(in srgb, var(--ej-color-impulse, #FF8C42) 20%, transparent);
    border-radius: 2px;
    overflow: hidden;

    &::after {
        content: '';
        display: block;
        height: 100%;
        width: 25%; // JS actualiza via style.width
        background: var(--ej-color-impulse, #FF8C42);
        border-radius: 2px;
        transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
}

// Step
.quiz-step__title {
    font-size: clamp(1.5rem, 4vw, 2rem);
    font-weight: 800;
    color: var(--ej-text-heading, var(--ej-text-primary, #1a1d29));
    margin-bottom: var(--ej-spacing-xs, 0.5rem);
    text-align: center;
}

.quiz-step__subtitle {
    font-size: var(--ej-font-size-base, 1rem);
    color: var(--ej-text-muted, #6b7280);
    text-align: center;
    margin-bottom: var(--ej-spacing-xl, 2rem);
}

// Option cards — 2 columnas en desktop, 1 en mobile
.quiz-step__options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--ej-spacing-sm, 0.75rem);

    @media (max-width: 480px) {
        grid-template-columns: 1fr;
    }
}

.quiz-step__option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--ej-spacing-sm, 0.75rem);
    padding: var(--ej-spacing-lg, 1.5rem) var(--ej-spacing-md, 1rem);
    background: var(--ej-bg-surface, #fff);
    border: 2px solid color-mix(in srgb, var(--ej-text-muted, #6b7280) 15%, transparent);
    border-radius: var(--ej-border-radius-lg, 16px);
    cursor: pointer;
    transition: border-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
    text-align: center;
    font: inherit;

    &:hover {
        border-color: var(--ej-color-impulse, #FF8C42);
        transform: translateY(-2px);
        box-shadow: 0 4px 16px color-mix(in srgb, var(--ej-color-impulse, #FF8C42) 15%, transparent);
    }

    &[aria-checked="true"] {
        border-color: var(--ej-color-impulse, #FF8C42);
        background: color-mix(in srgb, var(--ej-color-impulse, #FF8C42) 5%, transparent);
    }
}

.quiz-step__option-text {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--ej-text-primary, #1a1d29);
    line-height: 1.4;
}

.quiz-step__back {
    display: inline-flex;
    margin-top: var(--ej-spacing-lg, 1.5rem);
    padding: var(--ej-spacing-xs, 0.5rem) var(--ej-spacing-sm, 0.75rem);
    background: none;
    border: none;
    color: var(--ej-text-muted, #6b7280);
    cursor: pointer;
    font: inherit;
    font-size: 0.875rem;

    &:hover {
        color: var(--ej-text-primary, #1a1d29);
    }
}

// Loading
.quiz-page__loading {
    text-align: center;
    padding: var(--ej-spacing-xxl, 4rem) 0;
}

.quiz-page__spinner {
    width: 48px;
    height: 48px;
    border: 3px solid color-mix(in srgb, var(--ej-color-impulse, #FF8C42) 20%, transparent);
    border-top-color: var(--ej-color-impulse, #FF8C42);
    border-radius: 50%;
    margin: 0 auto var(--ej-spacing-md, 1rem);
    animation: quiz-spin 0.8s linear infinite;
}

@keyframes quiz-spin {
    to { transform: rotate(360deg); }
}

// === RESULT PAGE ===
.quiz-result {
    min-height: 100vh;
    padding: var(--ej-spacing-xl, 2rem);
    background: var(--ej-bg-body, #F8FAFC);
}

.quiz-result__container {
    max-width: 720px;
    margin: 0 auto;
}

.quiz-result__card--primary {
    background: var(--ej-bg-surface, #fff);
    border-radius: var(--ej-border-radius-xl, 24px);
    padding: var(--ej-spacing-xxl, 3rem);
    text-align: center;
    box-shadow: 0 8px 32px color-mix(in srgb, var(--ej-text-primary, #1a1d29) 8%, transparent);
}

.quiz-result__badge {
    display: inline-block;
    padding: var(--ej-spacing-xxs, 0.25rem) var(--ej-spacing-md, 1rem);
    background: color-mix(in srgb, var(--ej-color-innovation, #00A9A5) 10%, transparent);
    color: var(--ej-color-innovation, #00A9A5);
    border-radius: var(--ej-border-radius-full, 9999px);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--ej-spacing-lg, 1.5rem);
}

.quiz-result__title {
    font-size: clamp(1.75rem, 5vw, 2.5rem);
    font-weight: 800;
    margin: var(--ej-spacing-md, 1rem) 0;
}

.quiz-result__explanation {
    font-size: var(--ej-font-size-lg, 1.125rem);
    color: var(--ej-text-muted, #6b7280);
    line-height: 1.6;
    margin-bottom: var(--ej-spacing-xl, 2rem);
}

.quiz-result__benefits {
    list-style: none;
    padding: 0;
    text-align: left;
    margin-bottom: var(--ej-spacing-xl, 2rem);
}

.quiz-result__benefits li {
    display: flex;
    align-items: center;
    gap: var(--ej-spacing-sm, 0.75rem);
    padding: var(--ej-spacing-xs, 0.5rem) 0;
    font-size: var(--ej-font-size-base, 1rem);
}

.quiz-result__pricing {
    font-size: var(--ej-font-size-lg, 1.125rem);
    margin-bottom: var(--ej-spacing-xl, 2rem);
}

.quiz-result__pricing strong {
    font-size: 2rem;
    font-weight: 800;
    color: var(--ej-color-impulse, #FF8C42);
}

.quiz-result__pricing-note {
    display: block;
    font-size: 0.8125rem;
    color: var(--ej-text-muted, #6b7280);
}

.quiz-result__cta {
    display: inline-flex;
    font-size: var(--ej-font-size-lg, 1.125rem);
    padding: var(--ej-spacing-md, 1rem) var(--ej-spacing-xxl, 3rem);
}

// Alternativas
.quiz-result__alt-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--ej-spacing-md, 1rem);
    margin-top: var(--ej-spacing-lg, 1.5rem);

    @media (max-width: 480px) {
        grid-template-columns: 1fr;
    }
}

.quiz-result__alt-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--ej-spacing-xs, 0.5rem);
    padding: var(--ej-spacing-lg, 1.5rem);
    background: var(--ej-bg-surface, #fff);
    border-radius: var(--ej-border-radius-lg, 16px);
    border: 1px solid color-mix(in srgb, var(--ej-text-muted, #6b7280) 15%, transparent);
    text-decoration: none;
    color: var(--ej-text-primary, #1a1d29);
    transition: border-color 0.2s ease, transform 0.15s ease;

    &:hover {
        border-color: var(--ej-color-impulse, #FF8C42);
        transform: translateY(-2px);
    }
}
```

### 8.6 JS: js/vertical-quiz.js

```javascript
// Vanilla JS + Drupal.behaviors (NO React, NO Vue)
// - Una pregunta visible a la vez
// - Click en opción → animación salida → siguiente pregunta
// - Progress bar actualiza con cada paso
// - Al completar 4 preguntas → POST /api/v1/quiz/submit
// - Respuesta: redirect a /test-vertical/resultado/{uuid}
// - CSRF token cacheado (CSRF-JS-CACHE-001)
// - URLs desde drupalSettings (ROUTE-LANGPREFIX-001)
// - XSS: Drupal.checkPlain() para datos de API (INNERHTML-XSS-001)
```

### 8.7 Library registration

```yaml
# En ecosistema_jaraba_theme.libraries.yml
route-quiz:
    css:
        theme:
            css/routes/quiz.css: {}
    js:
        js/vertical-quiz.js: {}
    dependencies:
        - core/drupal
        - core/drupalSettings
```

Adjuntada via `hook_page_attachments_alter()` para la ruta `ecosistema_jaraba_core.quiz_vertical`.

---

## 9. Integración CRM + Lead Attribution

### 9.1 Auto-creación de Contact + Opportunity

Cuando el usuario completa el quiz Y proporciona su email (en la pantalla de resultado):

```php
// VerticalQuizService::createCrmLead()

// 1. Crear Contact (si jaraba_crm disponible)
$contact = Contact::create([
    'first_name' => '', // Se completará al registrarse
    'email' => $email,
    'source' => 'quiz_vertical', // Nuevo valor a añadir en allowed_values
    'engagement_score' => 40, // Quiz completado = engagement medio
    'tenant_id' => 0, // Se vinculará al registrarse
]);

// 2. Crear Opportunity
$opportunity = Opportunity::create([
    'title' => t('Lead Quiz - @vertical', ['@vertical' => $verticalLabel]),
    'contact_id' => $contact->id(),
    'stage' => 'mql', // Marketing Qualified Lead (completó quiz)
    'probability' => 30,
    'bant_need' => $answers['necesidad_bant'], // Mapeado desde respuesta Q3
    'bant_timeline' => $answers['urgencia_bant'], // Mapeado desde respuesta Q4
]);
```

### 9.2 Nuevo valor en Contact.source

Añadir `quiz_vertical` a `jaraba_crm.allowed_values.yml`:

```yaml
contact_source:
    # ... valores existentes ...
    quiz_vertical: 'Quiz recomendación vertical'
```

### 9.3 Vinculación post-registro

En `OnboardingController::processRegistration()`, después de crear el usuario:

```php
// Si hay quiz_uuid en query params o cookie
if ($quizUuid = $request->query->get('quiz_uuid')) {
    $quizService->linkResultToUser($quizUuid, $user->id(), $tenant->id());
}
```

---

## 10. Integración IA — Recomendación personalizada

### 10.1 Prompt para generación de explicación

```
Eres un asesor de negocios. El usuario ha completado un quiz y su vertical ideal es {vertical}.

Perfil: {perfil}
Sector: {sector}
Necesidad principal: {necesidad}
Urgencia: {urgencia}

Genera una explicación personalizada de 2-3 frases explicando POR QUÉ este vertical
es ideal para su perfil. Sé concreto, menciona funcionalidades específicas del vertical.
Tono: profesional pero cercano, sin tecnicismos.
Idioma: español de España.
Máximo: 200 caracteres.
```

### 10.2 Modelo y tier

- **Tier: fast** (Haiku 4.5) — latencia < 1s, suficiente para texto corto personalizado
- **Fallback**: Textos pre-escritos por vertical si IA no disponible
- **Cumple**: MODEL-ROUTING-CONFIG-001, AI-GUARDRAILS-PII-001 (no PII en prompt)

---

## 11. Setup Wizard + Daily Actions

### 11.1 Wizard Step: CompletarQuizStep (opcional, global)

Para usuarios que se registraron SIN pasar por el quiz → incentivo para completarlo.

```php
class CompletarQuizStep implements SetupWizardStepInterface {
    public function getId(): string { return '__global__.completar_quiz'; }
    public function getWizardId(): string { return '__global__'; }
    public function getWeight(): int { return 85; } // Antes de SubscriptionUpgrade (90)
    public function isOptional(): bool { return TRUE; }
    public function isComplete(int $tenantId): bool {
        // TRUE si el usuario tiene un QuizResult vinculado
    }
    public function getLabel(): TranslatableMarkup {
        return new TranslatableMarkup('Descubre más verticales');
    }
    public function getIcon(): array {
        return ['category' => 'general', 'name' => 'globe', 'variant' => 'duotone'];
    }
    public function getRoute(): string {
        return 'ecosistema_jaraba_core.quiz_vertical';
    }
}
```

### 11.2 Daily Action: ExplorarQuizAction

Para logueados que no han hecho el quiz → CTA en daily actions.

```php
class ExplorarQuizAction implements DailyActionInterface {
    public function getId(): string { return '__global__.explorar_quiz'; }
    public function getDashboardId(): string { return '__global__'; }
    public function getWeight(): int { return 80; }
    public function isPrimary(): bool { return FALSE; }
    public function getLabel(): TranslatableMarkup {
        return new TranslatableMarkup('Descubre tu vertical ideal');
    }
    public function getIcon(): array {
        return ['category' => 'general', 'name' => 'globe', 'variant' => 'duotone'];
    }
    public function getColor(): string { return 'naranja-impulso'; }
    public function getRoute(): string {
        return 'ecosistema_jaraba_core.quiz_vertical';
    }
    public function getContext(int $tenantId): array {
        // Solo visible si el usuario NO ha completado el quiz
        $hasQuiz = /* query QuizResult por uid */ ;
        return ['badge' => NULL, 'visible' => !$hasQuiz];
    }
}
```

---

## 12. Mega Menú — Promo card actualizada

La promo card del mega menú (columna 4) ya fue actualizada en el mega menú clase mundial para apuntar al quiz:

```twig
{% if logged_in %}
    {# Logueado: invitar a explorar más verticales #}
    <h4>{% trans %}Amplía tu ecosistema{% endtrans %}</h4>
    <p>{% trans %}Activa verticales adicionales...{% endtrans %}</p>
    <a href="{{ lp }}/addons">{% trans %}Ver catálogo{% endtrans %} →</a>
{% else %}
    {# Anónimo: quiz de recomendación #}
    <h4>{% trans %}¿No sabes cuál elegir?{% endtrans %}</h4>
    <p>{% trans %}Regístrate gratis y accede a todas las herramientas...{% endtrans %}</p>
    <a href="{{ lp }}/user/register">{% trans %}Empieza gratis{% endtrans %} →</a>
{% endif %}
```

**CAMBIO PROPUESTO**: Actualizar el CTA de anónimo para apuntar al quiz:

```twig
{% else %}
    <h4>{% trans %}¿No sabes cuál elegir?{% endtrans %}</h4>
    <p>{% trans %}Descubre en 30 segundos cuál es la solución perfecta para ti.{% endtrans %}</p>
    <a href="{{ lp }}/test-vertical">{% trans %}Hacer el test{% endtrans %} →</a>
{% endif %}
```

---

## 13. Mobile-first y accesibilidad

### 13.1 Mobile

- **Grid 2 columnas → 1 columna** en `max-width: 480px`
- **Opciones tipo tarjeta** con touch targets ≥ 44px (WCAG 2.5.5)
- **Sin scroll lateral** — cada paso cabe en una pantalla
- **Animaciones reducidas** si `prefers-reduced-motion: reduce`

### 13.2 Accesibilidad

| Requisito | Implementación |
|-----------|---------------|
| Roles ARIA | `role="radiogroup"` en opciones, `role="radio"` en cada tarjeta |
| aria-checked | Actualizado por JS al seleccionar |
| aria-live="polite" | En container de steps para anunciar cambios |
| Focus management | Focus al primer elemento al cambiar de step |
| Keyboard navigation | Tab entre opciones, Enter/Space para seleccionar |
| Screen reader | Progress text "Pregunta X de 4" actualizado |

---

## 14. Tracking y analytics

### 14.1 Eventos del quiz

| Evento | data-track-cta | data-track-position | Descripción |
|--------|---------------|---------------------|-------------|
| Inicio quiz | `quiz_start` | `quiz_page` | Usuario carga la página del quiz |
| Respuesta Q1 | `quiz_perfil_{value}` | `quiz_step_1` | Selecciona perfil |
| Respuesta Q2 | `quiz_sector_{value}` | `quiz_step_2` | Selecciona sector |
| Respuesta Q3 | `quiz_necesidad_{value}` | `quiz_step_3` | Selecciona necesidad |
| Respuesta Q4 | `quiz_urgencia_{value}` | `quiz_step_4` | Selecciona urgencia |
| Submit | `quiz_submit` | `quiz_page` | Envía respuestas |
| Resultado visto | `quiz_result_view` | `quiz_result` | Ve la recomendación |
| CTA registro | `quiz_result_register` | `quiz_result` | Clic en "Empieza gratis" |
| CTA alternativa | `quiz_result_alt_{vertical}` | `quiz_result` | Clic en alternativa |
| CTA add-ons | `quiz_result_addons` | `quiz_result` | Logueado → catálogo |

### 14.2 Funnel del quiz

```
quiz_start → quiz_step_1 → quiz_step_2 → quiz_step_3 → quiz_step_4
    → quiz_submit → quiz_result_view → quiz_result_register
```

Cada transición mide drop-off rate. Objetivo: > 70% completion rate (4 preguntas = baja fricción).

---

## 15. Safeguards y validación

### 15.1 ICON-INTEGRITY-001 (ya implementado)

Script: `scripts/validation/validate-icon-references.php`
- Escanea Twig + PHP para jaraba_icon() calls
- Verifica existencia del SVG en disco
- Detecta categoría incorrecta ("Found in category X instead!")
- Integrado en validate-all.sh

### 15.2 QUIZ-FUNNEL-001 (nuevo — propuesto)

Script: `scripts/validation/validate-quiz-funnel.php`

Verifica:
1. Ruta `/test-vertical` existe en routing.yml y apunta a controller válido
2. Ruta `/api/v1/quiz/submit` existe con CSRF protection
3. Template `page--test-vertical.html.twig` existe
4. Template `_quiz-step.html.twig` y `_quiz-result.html.twig` existen
5. JS `vertical-quiz.js` existe y está registrado en library
6. SCSS compilado contiene `.quiz-page`
7. QuizResult entity tiene hook_update_N()
8. VerticalQuizService registrado en services.yml
9. CRM dependencias son opcionales (`@?`)

---

## 16. Auditoría de conversión homepage — Gap analysis 10/10

### 16.1 Checklist clase mundial

| # | Criterio | Estado actual | Acción |
|---|----------|--------------|--------|
| 1 | Hero con CTA diferenciado logged_in | ✅ Implementado | — |
| 2 | Vertical selector con auto-detección | ✅ Implementado | — |
| 3 | Social proof cuantitativo | ✅ +50K personas, 100M EUR | — |
| 4 | Social proof cualitativo (testimonios reales) | ✅ 3 testimonios con resultados | — |
| 5 | Demo interactiva | ✅ 12 perfiles, TTFV tracking | — |
| 6 | Lead magnet (email capture con valor) | ✅ Kit Impulso Digital | — |
| 7 | FAQ (manejo objeciones) | ✅ 6 preguntas | — |
| 8 | CTA final con urgencia suave | ✅ "Sin compromiso, sin tarjeta" | — |
| 9 | Trust badges (RGPD, seguridad) | ✅ 3 badges | — |
| 10 | Pricing transparency | ✅ "Desde X€/mes" en vertical highlights | — |
| 11 | **Quiz de cualificación pre-registro** | ❌ NO EXISTE | **Este plan** |
| 12 | **Lead attribution automática CRM** | ❌ Fragmentada | **Este plan §9** |
| 13 | **Personalización post-registro (IA)** | ❌ Copilot genérico | **Este plan §10** |
| 14 | Cross-pollination (vertical cross-sell) | ✅ 8 tarjetas | — |
| 15 | Mobile-first responsive | ✅ Todos los parciales | — |
| 16 | A/B testing infrastructure | ✅ ab_variants support | — |
| 17 | Analytics 3 capas (GTM + beacon + form) | ✅ Completo | — |
| 18 | **Mega menú clase mundial** | ✅ Implementado (este sprint) | — |
| 19 | **Iconos sin chinchetas** | ✅ ICON-INTEGRITY-001 safeguard | — |
| 20 | Accessibility WCAG 2.1 AA | ⚠️ Parcial (keyboard nav mejorable) | Mejora incremental |

**Score actual: 17/20 (8.5/10)**
**Score con este plan implementado: 20/20 (10/10)**

---

## 17. Tabla de correspondencia de directrices

| Directriz | Cómo se cumple |
|-----------|----------------|
| ZERO-REGION-001 | Controller devuelve markup vacío, datos via preprocess |
| ZERO-REGION-003 | drupalSettings en preprocess, no en controller |
| CONTROLLER-READONLY-001 | Sin `protected readonly` en propiedades heredadas |
| UPDATE-HOOK-REQUIRED-001 | hook_update para QuizResult entity |
| ENTITY-FK-001 | uid = entity_reference, tenant_id = entity_reference, crm_contact_id = integer (cross-módulo) |
| AUDIT-CONS-001 | AccessControlHandler en anotación entity |
| OPTIONAL-CROSSMODULE-001 | jaraba_crm, jaraba_ai_agents, jaraba_analytics todos @? |
| ICON-CONVENTION-001 | jaraba_icon() con categoría + nombre |
| ICON-DUOTONE-001 | variant: 'duotone' en todos los iconos |
| ICON-COLOR-001 | Solo colores de paleta Jaraba |
| CSS-VAR-ALL-COLORS-001 | Todos los colores con var(--ej-*) |
| SCSS-001 | @use '../variables' as * en cada parcial |
| SCSS-COMPILE-VERIFY-001 | npm run build + timestamp check |
| SCSS-COLORMIX-001 | color-mix() en lugar de rgba() |
| ROUTE-LANGPREFIX-001 | URLs resueltas en PHP con language_prefix |
| TWIG-INCLUDE-ONLY-001 | Parciales con `only` keyword |
| NO-HARDCODE-PRICE-001 | Precios desde MetaSitePricingService |
| CTA-LOGGED-IN-001 | Diferenciación logged_in en resultado |
| CSRF-API-001 | _csrf_request_header_token: 'TRUE' en ruta API |
| INNERHTML-XSS-001 | Drupal.checkPlain() en JS para datos de API |
| AI-GUARDRAILS-PII-001 | No PII en prompt de IA |
| MODEL-ROUTING-CONFIG-001 | Tier fast (Haiku) para generación texto |
| PRESAVE-RESILIENCE-001 | hasService() + try-catch para CRM |
| SETUP-WIZARD-DAILY-001 | Wizard step + daily action para quiz |
| ZEIGARNIK-PRELOAD-001 | Quiz step opcional, no afecta auto-complete global |
| SLIDE-PANEL-RENDER-001 | No aplica (quiz es full-page, no modal) |
| PIPELINE-E2E-001 | L1: Service → L2: Controller → L3: hook_theme → L4: Template |

---

## 18. Verificación RUNTIME-VERIFY-001

| # | Check | Método |
|---|-------|--------|
| 1 | Ruta `/test-vertical` accesible (anónimo) | curl 200 |
| 2 | Ruta `/api/v1/quiz/submit` responde a POST | curl -X POST con CSRF |
| 3 | Quiz muestra 4 preguntas | grep quiz-step en HTML |
| 4 | Iconos SVG duotone renderizados | Visual / ICON-INTEGRITY-001 |
| 5 | Progress bar actualiza con cada paso | Browser test |
| 6 | Submit genera QuizResult con UUID | DB query |
| 7 | Resultado muestra vertical + explicación IA | Browser /test-vertical/resultado/{uuid} |
| 8 | CTA diferenciado logged_in | curl auth vs anon |
| 9 | CRM Contact creado con source=quiz_vertical | drush query |
| 10 | SCSS compilado contiene .quiz-page | grep en CSS |
| 11 | Mobile responsive (480px) | Chrome DevTools |
| 12 | Tracking events disparados | Network tab / dataLayer |
| 13 | validate-icon-references.php PASS | Script |
| 14 | validate-quiz-funnel.php PASS | Script (nuevo) |

---

## 19. Orden de ejecución (sprints)

| Sprint | Tarea | Archivos | Riesgo | Estimación |
|--------|-------|----------|--------|------------|
| S1 | QuizResult entity + hook_update + baseFieldDefinitions | Entity PHP | Bajo | |
| S2 | VerticalQuizService (scoring + persistence) | Service PHP | Bajo | |
| S3 | VerticalQuizController + rutas | Controller + routing.yml | Medio | |
| S4 | Templates Twig (page, parciales quiz + resultado) | Twig | Medio | |
| S5 | SCSS (quiz.scss) + compilación + library | SCSS/CSS | Bajo | |
| S6 | JS (vertical-quiz.js) + behaviors | JS | Medio | |
| S7 | hook_preprocess_page + hook_theme + template suggestions | .theme PHP | Medio | |
| S8 | Integración CRM (Contact + Opportunity auto-creation) | Service PHP | Bajo | |
| S9 | Integración IA (prompt + ModelRouterService) | Service PHP | Bajo | |
| S10 | Setup Wizard step + Daily Action | Step/Action PHP | Bajo | |
| S11 | Mega menú promo card → apuntar a /test-vertical | Twig | Bajo | |
| S12 | Safeguard validate-quiz-funnel.php | Script PHP | Bajo | |
| S13 | allowed_values CRM + vinculación post-registro | YAML + PHP | Bajo | |
| S14 | Analytics tracking (data-track-cta en todos los CTAs) | Twig/JS | Bajo | |
| S15 | RUNTIME-VERIFY-001 (14 checks) | Verificación | — | |

---

## 20. Safeguards propuestas para el futuro

Además de ICON-INTEGRITY-001 (ya implementado) y QUIZ-FUNNEL-001 (propuesto en §15.2), se proponen las siguientes safeguards:

### 20.1 LEAD-ATTRIBUTION-001

**Propósito**: Verificar que toda ruta de conversión (registro, demo, quiz) auto-atribuye la fuente al CRM Contact.

**Checks**:
1. OnboardingController captura `quiz_uuid`, `demo_session`, UTM params
2. Contact.source se asigna automáticamente (no manual)
3. AnalyticsEvent se crea con UTM params para cada conversión

### 20.2 CTA-DESTINATION-001

**Propósito**: Verificar que todos los CTAs apuntan a rutas existentes y accesibles.

**Checks**:
1. Escanea todos los `href` en parciales de conversión
2. Verifica que cada ruta existe en routing.yml
3. Detecta rutas hardcodeadas sin language_prefix

### 20.3 FUNNEL-COMPLETENESS-001

**Propósito**: Verificar que cada sección de conversión del homepage tiene tracking completo.

**Checks**:
1. Cada CTA tiene `data-track-cta` y `data-track-position`
2. Cada sección con logged_in condicional tiene ambas ramas (auth + anon)
3. No hay CTAs de registro visibles para usuarios logueados

### 20.4 VERTICAL-COVERAGE-001

**Propósito**: Verificar que los 9 verticales comercializables (10 canónicos - demo) están representados en todos los puntos de discovery.

**Checks**:
1. Mega menú: 9 verticales presentes
2. Vertical selector: ≥ 6 verticales
3. Cross-pollination: ≥ 8 verticales
4. Quiz scoring: 9 verticales tienen puntuación > 0

---

*Fin del documento — v1.0 — 2026-03-19*
