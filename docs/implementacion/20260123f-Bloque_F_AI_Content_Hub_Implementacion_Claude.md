# Bloque F: AI Content Hub - Documento de Implementación
## Blog, Newsletter y Contenido Asistido por IA

**Fecha de creación:** 2026-01-23 16:00  
**Última actualización:** 2026-01-23 16:00  
**Autor:** IA Asistente (Claude)  
**Versión:** 1.0.0

---

## 📑 Tabla de Contenidos (TOC)

1. [Matriz de Especificaciones](#1-matriz-de-especificaciones)
2. [Checklist Multidisciplinar](#2-checklist-multidisciplinar-8-expertos)
3. [F.1 Core Blog](#3-f1-core-blog)
4. [F.2 AI Writing Assistant](#4-f2-ai-writing-assistant)
5. [F.3 Newsletter Engine](#5-f3-newsletter-engine)
6. [F.4 Recommendations & Analytics](#6-f4-recommendations--analytics)
7. [F.5 Frontend Components](#7-f5-frontend-components)
8. [Checklist Directrices Obligatorias](#8-checklist-directrices-obligatorias)
9. [Registro de Cambios](#9-registro-de-cambios)

---

## 1. Matriz de Especificaciones

### 1.1 Documentos de Referencia

| Doc | Archivo | Contenido Clave |
|-----|---------|-----------------|
| 128_v1 | [20260118g-128_Platform_AI_Content_Hub_v1_Claude.md](../tecnicos/20260118g-128_Platform_AI_Content_Hub_v1_Claude.md) | Arquitectura alto nivel, justificación |
| 128_v2 | [20260118g-128_Platform_AI_Content_Hub_v2_Claude.md](../tecnicos/20260118g-128_Platform_AI_Content_Hub_v2_Claude.md) | **Spec completa**: 6 entidades, 12 APIs, 8 ECA |
| 128b | [20260118g-128b_Platform_AI_Content_Hub_Frontend_v1_Claude.md](../tecnicos/20260118g-128b_Platform_AI_Content_Hub_Frontend_v1_Claude.md) | UI/UX: Blog, Article page, Components |
| 128c | [20260118g-128c_Platform_AI_Content_Hub_Editor_v1_Claude.md](../tecnicos/20260118g-128c_Platform_AI_Content_Hub_Editor_v1_Claude.md) | Editor Dashboard, AI Assistant UI, Newsletter Builder |

### 1.2 Stack Tecnológico

| Componente | Tecnología | Justificación |
|------------|------------|---------------|
| Core CMS | Drupal 11 + módulo `jaraba_content_hub` | Entidades estructuradas |
| AI Generation | Claude API (`claude-sonnet-4-5`) | Calidad escritura |
| Vector Search | Qdrant Cloud | Ya presupuestado en KB |
| Newsletter | `jaraba_email` nativo | Sistema propio (doc 151) - reemplaza ActiveCampaign |
| Editor | CKEditor 5 + React | UX familiar |
| Embeddings | `text-embedding-3-small` | 1536 dims |

### 1.3 Entidades del Módulo

| Entidad | Tipo | Campos Clave |
|---------|------|--------------|
| `content_article` | Content Entity | title, body, answer_capsule, category, SEO |
| `content_category` | Content Entity | name, slug, parent, color, icon |
| `newsletter_campaign` | Content Entity | subject, template, content_blocks, stats |
| `newsletter_subscriber` | Content Entity | email, status, engagement_score |
| `content_recommendation` | Content Entity | source, target, score, type |
| `ai_generation_log` | Content Entity | prompt, tokens, latency, status |

### 1.4 🔄 Estrategia de Reuso

> ⚠️ **VERIFICACIÓN PREVIA**: Antes de cada paso, ejecutar análisis de reuso.

#### A. Reuso de Módulos Existentes

| Componente Reutilizable | Módulo Origen | Acción |
|-------------------------|---------------|--------|
| Claude API Client | `jaraba_copilot_v2` | Extender/Refactorizar |
| Qdrant Integration | `jaraba_kb` | Referenciar patrones |
| ECA Hooks Pattern | `jaraba_eca_hooks` | Aplicar workflow |
| Design Tokens | `jaraba_theming` | Integrar CSS variables |

#### B. Integraciones Externas

| Servicio | Ya existe | Acción |
|----------|-----------|--------|
| `jaraba_email` | Especificado (doc 151) | Usar módulo nativo - 115-155h |
| `jaraba_crm` | Especificado (doc 150) | Integrar para leads - 40-50h |
| Qdrant | Sí (KB) | Namespace `content_hub_{tenant}` |
| Claude API | Sí (Copilot) | Refactorizar a servicio compartido |

---

## 2. Checklist Multidisciplinar (8 Expertos)

### 2.1 Consultor de Negocio Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Blog genera tráfico orgánico? | [ ] | Target: +30% en 6 meses |
| ¿Newsletter nurturing definido? | [ ] | Welcome series, digest |
| ¿ROI de contenido IA medible? | [ ] | Costo/artículo vs manual |

### 2.2 Analista Financiero Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Costos API Claude controlados? | [ ] | Rate limits por tenant |
| ¿Token budgets por plan? | [ ] | Basic: 1K/mes, Pro: 10K |
| ¿ActiveCampaign pricing? | [ ] | Por contacto/mes |

### 2.3 Experto Producto Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿MVP scope (Sprint 1-2)? | [ ] | Core Blog only |
| ¿User stories editor? | [ ] | Docs 128b, 128c |
| ¿Roadmap 6 sprints definido? | [ ] | Ver sección 7 |

### 2.4 Arquitecto SaaS Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Aislamiento contenido por tenant? | [ ] | Group Module |
| ¿Namespace Qdrant por tenant? | [ ] | `content_hub_{id}` |
| ¿Newsletter lists aisladas? | [ ] | AC tags por tenant |

### 2.5 Ingeniero Software Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿PHPUnit para servicios IA? | [ ] | ClaudeApiClient |
| ¿E2E Cypress para editor? | [ ] | Article creation flow |
| ¿Rate limiting implementado? | [ ] | Por user y tenant |

### 2.6 Ingeniero UX Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Editor dashboard UX? | [ ] | Doc 128c wireframes |
| ¿Reading progress bar? | [ ] | Doc 128b spec |
| ¿Card variants blog? | [ ] | 4 variantes mínimo |

### 2.7 Ingeniero SEO/GEO Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Answer Capsule en cada artículo? | [ ] | Primeros 150 chars |
| ¿Schema.org Article? | [ ] | Auto-generado |
| ¿llms.txt dinámico? | [ ] | Por tenant |

### 2.8 Ingeniero IA Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿System prompts por tenant? | [ ] | Brand voice |
| ¿Taboo terms configurable? | [ ] | Lista negra |
| ¿Feedback loop para IA? | [ ] | Rating 1-5 |

---

## 3. F.1 Core Blog

> **Referencia:** Doc 128_v2 secciones 2-3

### 3.1 Módulo Base (Sprint 1-2: 80-100h)

#### Paso 1: Crear módulo (10h)
- [ ] `modules/custom/jaraba_content_hub/`
- [ ] `jaraba_content_hub.info.yml`
- [ ] `jaraba_content_hub.module`
- [ ] `jaraba_content_hub.services.yml`

#### Paso 2: Entidad content_article (30h)

```php
/**
 * @ContentEntityType(
 *   id = "content_article",
 *   label = @Translation("Artículo"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_content_hub\Entity\ContentArticleListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\jaraba_content_hub\Form\ContentArticleForm",
 *       "edit" = "Drupal\jaraba_content_hub\Form\ContentArticleForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "content_article",
 *   admin_permission = "administer content articles",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/blog/{content_article}",
 *     "add-form" = "/admin/content/articles/add",
 *     "edit-form" = "/admin/content/articles/{content_article}/edit",
 *     "delete-form" = "/admin/content/articles/{content_article}/delete",
 *     "collection" = "/admin/content/articles",
 *   },
 *   field_ui_base_route = "entity.content_article.settings",
 * )
 */
```

Campos obligatorios:
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `title` | string(255) | Título del artículo |
| `slug` | string(255) | URL amigable |
| `excerpt` | text(500) | Resumen para listados |
| `body` | text_long | Contenido principal |
| `answer_capsule` | text(200) | Para GEO optimization |
| `featured_image` | entity_reference | FK file_managed |
| `category_id` | entity_reference | FK content_category |
| `author_id` | entity_reference | FK users |
| `reading_time` | integer | Minutos (computed) |
| `status` | list_string | draft/review/scheduled/published/archived |
| `publish_date` | datetime | Fecha publicación |
| `seo_title` | string(70) | Meta title |
| `seo_description` | text(160) | Meta description |
| `schema_json` | json | Schema.org auto-generated |
| `ai_generated` | boolean | Flag IA |
| `engagement_score` | decimal | Score 0-1 |

#### Paso 3: Entidad content_category (10h)
- [ ] Entity con: name, slug, parent, color, icon, weight

#### Paso 4: REST APIs (20h)
| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/v1/content/articles` | GET | Listado paginado |
| `/api/v1/content/articles/{uuid}` | GET | Detalle |
| `/api/v1/content/articles` | POST | Crear |
| `/api/v1/content/articles/{uuid}` | PATCH | Actualizar |
| `/api/v1/content/articles/{uuid}/publish` | POST | Publicar |

#### Paso 5: Views y Templates (10h)
- [ ] Vista `blog_homepage` (masonry grid)
- [ ] Vista `blog_category` (filtro por categoría)
- [ ] Template `content-article--full.html.twig`
- [ ] Template `content-article--teaser.html.twig`

---

## 4. F.2 AI Writing Assistant

> **Referencia:** Doc 128_v2 sección 5, Doc 128c sección 1-2

### 4.1 Claude API Service (Sprint 3-4: 60-70h)

#### Paso 1: Refactorizar ClaudeApiClient (20h)

> ⚠️ **REUSO**: Extraer de `jaraba_copilot_v2` a servicio compartido.

```php
// modules/custom/jaraba_ai_core/src/Service/ClaudeApiClient.php
class ClaudeApiClient {
    public function generateContent(string $prompt, array $options = []): ClaudeResponse;
    public function streamContent(string $prompt, callable $onChunk): void;
}
```

#### Paso 2: Servicios de generación (30h)

| Servicio | Endpoint | Descripción |
|----------|----------|-------------|
| `OutlineGeneratorService` | `/generate/outline` | Estructura H2/H3 |
| `ArticleGeneratorService` | `/generate/article` | Artículo completo |
| `SectionExpanderService` | `/generate/section` | Expandir párrafos |
| `HeadlineGeneratorService` | `/generate/headline` | 5 variantes título |
| `SummaryGeneratorService` | `/generate/summary` | Excerpt + Answer Capsule |
| `SEOAnalyzerService` | `/analyze/seo` | Score + sugerencias |

#### Paso 3: Rate Limiting (10h)
| Operación | Límite Usuario | Límite Tenant/día |
|-----------|----------------|-------------------|
| outline | 10/min | 500 |
| article | 5/min | 100 |
| section | 20/min | 1000 |
| headline | 30/min | 2000 |

#### Paso 4: Entidad ai_generation_log (10h)
- [ ] Registrar cada generación para auditoría
- [ ] Campos: prompt_hash, tokens_in, tokens_out, latency_ms, user_rating

---

## 5. F.3 Newsletter Engine

> **Referencia:** Doc 128_v2 sección 3.3, Doc 128c sección 3-4

### 5.1 Newsletter Core (Sprint 5-6: 50-60h)

#### Paso 1: Entidad newsletter_campaign (15h)
- [ ] Campos: subject, preheader, template_id, content_blocks (JSON), stats

#### Paso 2: Entidad newsletter_subscriber (10h)
- [ ] Campos: email, status, source, engagement_score, interests (JSON)

#### Paso 3: Integración con jaraba_email Nativo (15h)

> ⚠️ **ACTUALIZACIÓN**: Se usa `jaraba_email` nativo en lugar de ActiveCampaign (ver doc 151_Marketing_jaraba_email_v1)

- [ ] Integrar con `email_list` y `email_subscriber` de jaraba_email
- [ ] Usar `email_sequence` para flujos de newsletter
- [ ] Templates MJML compartidos con jaraba_email

#### Paso 4: Email Templates MJML (15h)
- [ ] Template `weekly_digest`
- [ ] Template `new_article`
- [ ] Template `re_engagement`
- [ ] MJML → HTML pipeline

### 5.2 ECA Automations (20h)

| Flujo | Trigger | Acción |
|-------|---------|--------|
| ECA-CH-004 | Cron Lunes 07:00 | Generar Weekly Digest |
| ECA-CH-005 | campaign.scheduled_at <= NOW | Enviar newsletter |
| ECA-CH-006 | article.published + flag | Notificar subscribers |
| ECA-CH-008 | Webhook AC | Actualizar engagement |

---

## 6. F.4 Recommendations & Analytics

> **Referencia:** Doc 128_v2 secciones 6, Doc 128c sección 5

### 6.1 Motor Recomendaciones (Sprint 7-8: 50-60h)

#### Paso 1: Integración Qdrant (20h)
- [ ] Namespace `content_hub_{tenant_id}`
- [ ] Pipeline indexación artículos
- [ ] Embedding con `text-embedding-3-small`

#### Paso 2: Entidad content_recommendation (10h)
- [ ] source_article_id, target_article_id, score, type, expires_at

#### Paso 3: Servicio RecommendationEngine (20h)
```php
class RecommendationEngine {
    public function getSimilar(Article $article, int $limit = 4): array;
    public function getTrending(int $tenantId, int $days = 7): array;
    public function refreshRecommendations(): void;
}
```

#### Paso 4: Widget contenido relacionado (10h)
- [ ] Block plugin "Related Articles"
- [ ] 3-4 cards horizontales

### 6.2 Analytics Dashboard (30h)

| Métrica | Cálculo | Widget |
|---------|---------|--------|
| Total Views | SUM(views) | KPI Card |
| Unique Visitors | COUNT(DISTINCT session) | KPI Card |
| Avg Time on Page | AVG(time_on_page) | KPI Card |
| Top Articles | ORDER BY views DESC LIMIT 10 | Table |
| Category Distribution | GROUP BY category | Pie Chart |
| Newsletter Open Rate | opens/sent * 100 | Gauge |

---

## 7. F.5 Frontend Components

> **Referencia:** Doc 128b completo

### 7.1 Blog Homepage (40h)

#### Componentes a crear:
| Componente | Variantes | Archivo |
|------------|-----------|---------|
| ArticleCard | standard, featured, horizontal, minimal | `article-card.html.twig` |
| CategoryFilter | pills, sidebar, dropdown | `category-filter.html.twig` |
| NewsletterWidget | compact, inline, fullwidth | `newsletter-widget.html.twig` |
| TrendingWidget | numbered list | `trending-widget.html.twig` |

### 7.2 Article Page (50h)

| Componente | Descripción |
|------------|-------------|
| ReadingProgressBar | Barra de progreso fixed top |
| TableOfContents | Sticky sidebar con H2/H3 |
| SocialShareSidebar | Botones compartir |
| AnswerCapsule | Bloque destacado GEO |
| AuthorBioCard | Card autor al final |
| RelatedArticles | Grid 3-4 cards |

### 7.3 Editor Dashboard (60h)

> **Referencia:** Doc 128c sección 1

- [ ] Layout 3 columnas: AI Panel | Editor | Metadata
- [ ] CKEditor 5 con plugin AI Assistant
- [ ] SEO Score Widget tiempo real
- [ ] Toolbar: Guardar, Preview, Programar, Publicar

---

## 8. Checklist Directrices Obligatorias

> ⚠️ **VERIFICAR ANTES DE CADA COMMIT**

### 8.1 SCSS y Variables Inyectables

| Verificación | Estado |
|--------------|--------|
| ¿Archivos SCSS, no CSS directo? | [ ] |
| ¿Usa `var(--ej-*)` para colores? | [ ] |
| ¿Article cards usan tokens tema? | [ ] |
| ¿Compilado con `npm run build`? | [ ] |

### 8.2 Internacionalización (i18n)

| Verificación | Estado |
|--------------|--------|
| ¿Textos PHP con `$this->t()`? | [ ] |
| ¿Textos Twig con `{% trans %}`? | [ ] |
| ¿Labels API traducibles? | [ ] |

### 8.3 Content Entities

| Verificación | Estado |
|--------------|--------|
| ¿Handler `views_data` en annotation? | [ ] |
| ¿Handler `list_builder` definido? | [ ] |
| ¿`field_ui_base_route` configurado? | [ ] |
| ¿4 archivos YAML creados? | [ ] |
| - *.routing.yml | [ ] |
| - *.links.menu.yml | [ ] |
| - *.links.task.yml | [ ] |
| - *.links.action.yml | [ ] |

### 8.4 APIs REST

| Verificación | Estado |
|--------------|--------|
| ¿OAuth2 requerido? | [ ] |
| ¿X-Tenant-ID header? | [ ] |
| ¿Rate limiting implementado? | [ ] |
| ¿Respuestas paginadas? | [ ] |

### 8.5 Integración IA

| Verificación | Estado |
|--------------|--------|
| ¿Token budgets por plan? | [ ] |
| ¿Logs de generación? | [ ] |
| ¿Error handling (rate limit, timeout)? | [ ] |
| ¿Feedback loop user rating? | [ ] |

---

## 9. Resumen de Inversión

| Sprint | Semanas | Horas | Costo (€80/h) | Entregable |
|--------|---------|-------|---------------|------------|
| F.1 Core Blog | 1-2 | 80-100h | €6,400-8,000 | Blog multi-tenant |
| F.2 AI Assistant | 3-4 | 60-70h | €4,800-5,600 | Generación IA |
| F.3 Newsletter | 5-6 | 50-60h | €4,000-4,800 | Newsletter automático |
| F.4 Recommendations | 7-8 | 50-60h | €4,000-4,800 | Recomendaciones |
| F.5 Frontend | 9-10 | 100-120h | €8,000-9,600 | UI completa |
| **TOTAL** | **10 semanas** | **340-410h** | **€27,200-32,800** | **Sistema completo** |

---

## 10. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-23 | 1.0.0 | Creación inicial - Documento de implementación Bloque F |
