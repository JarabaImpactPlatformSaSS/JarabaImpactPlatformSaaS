# Matriz de Contenido Hardcodeado - Frontend SaaS

> **Fecha**: 2026-01-26
> **Versión**: 1.0.0
> **Autor**: EDI Google Antigravity
> **Estado**: Auditoría completada

---

## Resumen Ejecutivo

Este documento identifica **todo el contenido hardcodeado** en los templates Twig del frontend del Ecosistema Jaraba. El objetivo es migrar este contenido a **Content Entities** gestionables desde la UI de Drupal, cumpliendo con la directriz del proyecto.

> [!IMPORTANT]
> **Directriz de Proyecto (Sección 5.3)**: "Toda configuración de negocio debe ser editable desde la interfaz de Drupal mediante Content Entities con campos configurables. NO se permiten valores hardcodeados en el código."

---

## 1. Homepage (`page--front.html.twig`)

La homepage principal utiliza 5 partials con contenido hardcodeado significativo.

### 1.1 Hero Section (`_hero.html.twig`)

| Línea | Contenido Hardcodeado | Campo Sugerido | Tipo Campo |
|-------|----------------------|----------------|------------|
| 30 | `"Plataforma SaaS para Ecosistemas de Impacto"` | `hero_eyebrow` | string(100) |
| 33 | `"Impulsa tu ecosistema digital"` | `hero_title` | string(150) |
| 37 | `"La plataforma que conecta talento, negocios y productores con inteligencia artificial"` | `hero_subtitle` | text(300) |
| 46 | `/user/register` | `hero_cta_primary_url` | link |
| 50 | `"Empezar gratis"` | `hero_cta_primary_text` | string(50) |
| 53 | `/user/login` | `hero_cta_secondary_url` | link |
| 54 | `"Ya tengo cuenta"` | `hero_cta_secondary_text` | string(50) |
| 63 | `"Descubre más"` | `hero_scroll_text` | string(50) |

**Código Actual:**
```twig
<span class="hero-landing__eyebrow">{% trans %}Plataforma SaaS para Ecosistemas de Impacto{% endtrans %}</span>

<h1 id="hero-title" class="hero-landing__title">
  {% trans %}Impulsa tu ecosistema digital{% endtrans %}
</h1>

<p class="hero-landing__subtitle">
  {% trans %}La plataforma que conecta talento, negocios y productores con inteligencia artificial{% endtrans %}
</p>
```

**Propuesta de Migración:**
```twig
<span class="hero-landing__eyebrow">{{ homepage.hero_eyebrow }}</span>
<h1 id="hero-title" class="hero-landing__title">{{ homepage.hero_title }}</h1>
<p class="hero-landing__subtitle">{{ homepage.hero_subtitle }}</p>
```

---

### 1.2 Features Section (`_features.html.twig`)

| Línea | Elemento | Contenido Hardcodeado | Campo Sugerido |
|-------|----------|----------------------|----------------|
| 15 | Título sección | `"¿Por qué Jaraba Impact Platform?"` | `features_section_title` |
| 31 | Card 1 título | `"Configuración en minutos"` | `features[0].title` |
| 32 | Card 1 descripción | `"Tu ecosistema digital funcionando desde el día uno, sin código"` | `features[0].description` |
| 37 | Card 1 badge | `"Rápido"` | `features[0].badge` |
| 56 | Card 2 título | `"IA integrada"` | `features[1].title` |
| 57 | Card 2 descripción | `"Copiloto de carrera, matching inteligente..."` | `features[1].description` |
| 65 | Card 2 badge | `"Con IA"` | `features[1].badge` |
| 84 | Card 3 título | `"Multi-vertical"` | `features[2].title` |
| 85 | Card 3 descripción | `"Empleabilidad, emprendimiento, comercio..."` | `features[2].description` |
| 91 | Card 3 badge | `"4 verticales"` | `features[2].badge` |

**Estructura de Datos Propuesta:**
```json
{
  "features_section_title": "¿Por qué Jaraba Impact Platform?",
  "features": [
    {
      "icon_type": "clock",
      "title": "Configuración en minutos",
      "description": "Tu ecosistema digital funcionando desde el día uno, sin código",
      "badge": "Rápido",
      "badge_variant": "primary"
    },
    {
      "icon_type": "brain",
      "title": "IA integrada",
      "description": "Copiloto de carrera, matching inteligente, asistente de emprendimiento y recomendaciones personalizadas",
      "badge": "Con IA",
      "badge_variant": "secondary"
    },
    {
      "icon_type": "cube",
      "title": "Multi-vertical",
      "description": "Empleabilidad, emprendimiento, comercio y servicios profesionales y públicos",
      "badge": "4 verticales",
      "badge_variant": "corporate"
    }
  ]
}
```

---

### 1.3 Stats Section (`_stats.html.twig`)

| Línea | Métrica | Valor | Campo Sugerido |
|-------|---------|-------|----------------|
| 18 | Candidatos | `1500` | `stats[0].value` |
| 19 | Label candidatos | `"Candidatos activos"` | `stats[0].label` |
| 22 | Empresas | `120` | `stats[1].value` |
| 23 | Label empresas | `"Empresas"` | `stats[1].label` |
| 26 | Emprendimientos | `85` | `stats[2].value` |
| 27 | Label emprendimientos | `"Emprendimientos"` | `stats[2].label` |
| 30 | Satisfacción | `98` | `stats[3].value` |
| 31 | Sufijo satisfacción | `%` | `stats[3].suffix` |
| 32 | Label satisfacción | `"Satisfacción"` | `stats[3].label` |

**Código Actual:**
```twig
<div class="stat-item">
  <span class="stat-item__number" data-count="1500">0</span>
  <span class="stat-item__label">{% trans %}Candidatos activos{% endtrans %}</span>
</div>
```

**Propuesta de Migración:**
```twig
{% for stat in homepage.stats %}
  <div class="stat-item">
    <span class="stat-item__number" data-count="{{ stat.value }}">0</span>
    {% if stat.suffix %}<span class="stat-item__suffix">{{ stat.suffix }}</span>{% endif %}
    <span class="stat-item__label">{{ stat.label }}</span>
  </div>
{% endfor %}
```

---

### 1.4 Intentions Grid (`_intentions-grid.html.twig`)

| Línea | Intención | Título | Descripción | URL |
|-------|-----------|--------|-------------|-----|
| 15-26 | Empleo | `"Busco empleo"` | `"Encuentra ofertas que encajan contigo"` | `/empleo` |
| 29-42 | Talento | `"Busco talento"` | `"Conecta con candidatos cualificados"` | `/talento` |
| 45-54 | Emprender | `"Quiero emprender"` | `"Valida tu idea con metodología"` | `/emprender` |
| 57-70 | Comercio | `"Tengo un negocio"` | `"Vende tus productos o servicios online"` | `/comercio` |
| 73-86 | B2G | `"Soy institución"` | `"Impulsa programas de desarrollo territorial"` | `/instituciones` |

**Estructura de Datos Propuesta:**
```json
{
  "intentions": [
    {
      "key": "empleo",
      "icon": "person-search",
      "title": "Busco empleo",
      "description": "Encuentra ofertas que encajan contigo",
      "url": "/empleo",
      "color_variant": "empleo"
    },
    {
      "key": "talento",
      "icon": "people-group",
      "title": "Busco talento",
      "description": "Conecta con candidatos cualificados",
      "url": "/talento",
      "color_variant": "talento"
    }
    // ... más intenciones
  ]
}
```

---

### 1.5 Footer Section (`_footer.html.twig`)

El footer es **parcialmente configurable** vía `theme_settings`:

| Elemento | Estado | Configurable Desde |
|----------|--------|-------------------|
| Logo | ✅ Configurable | `theme_settings` |
| Copyright | ✅ Configurable | `footer_copyright` |
| Redes sociales | ✅ Configurable | `footer_social_*` |
| Links navegación (standard) | ❌ Hardcodeado | N/A |
| Powered by | ✅ Configurable | `footer_show_powered_by` |

**Links Hardcodeados (líneas 77-102):**
```twig
<nav class="landing-footer__nav">
  <h3>{% trans %}Plataforma{% endtrans %}</h3>
  <ul>
    <li><a href="/empleo">{% trans %}Empleabilidad{% endtrans %}</a></li>
    <li><a href="/emprender">{% trans %}Emprendimiento{% endtrans %}</a></li>
    <li><a href="/productos">{% trans %}Comercio{% endtrans %}</a></li>
    <li><a href="/instituciones">{% trans %}Instituciones{% endtrans %}</a></li>
  </ul>
</nav>
```

---

## 2. Copilot FAB (`_copilot-fab.html.twig`)

| Elemento | Valor Hardcodeado | Campo Sugerido |
|----------|------------------|----------------|
| Greeting (page--front) | `"¡Hola! Soy tu asesor de Jaraba..."` | `copilot.greeting` |
| Agent context | `'landing_copilot'` | `copilot.agent_context` |
| FAB color | `'var(--ej-color-impulse, #FF8C42)'` | `copilot.fab_color` |

---

## 3. Resumen de Migración

### 3.1 Prioridades

| Prioridad | Template | Contenido | Impacto |
|-----------|----------|-----------|---------|
| **P0** | `_hero.html.twig` | Todo el bloque | ALTO |
| **P0** | `_features.html.twig` | 3 cards completas | ALTO |
| **P0** | `_stats.html.twig` | 4 métricas | ALTO |
| **P1** | `_intentions-grid.html.twig` | 5 intenciones | MEDIO |
| **P2** | `_footer.html.twig` | Enlaces navegación | BAJO |

### 3.2 Entidad de Contenido Propuesta

**Nombre**: `homepage_content`  
**Tipo**: Content Entity  
**Admin URL**: `/admin/content/homepage`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `hero_eyebrow` | string | Texto eyebrow |
| `hero_title` | string | Título principal |
| `hero_subtitle` | text_long | Subtítulo |
| `hero_cta_primary` | link | CTA primario (text + url) |
| `hero_cta_secondary` | link | CTA secundario |
| `hero_scroll_text` | string | Texto scroll indicator |
| `features_title` | string | Título sección features |
| `features` | entity_ref (multiple) | Referencia a Feature Cards |
| `stats` | entity_ref (multiple) | Referencia a Stats Items |
| `intentions` | entity_ref (multiple) | Referencia a Intention Cards |

### 3.3 Entidades Auxiliares

| Entidad | Campos Base |
|---------|-------------|
| `feature_card` | icon, title, description, badge, badge_variant |
| `stat_item` | value, suffix, label, animate |
| `intention_card` | icon, title, description, url, color_variant |

---

## 4. Beneficios de la Migración

1. **Edición sin código**: Administradores modifican contenido desde UI
2. **Traducciones nativas**: Soporte i18n automático via Content Entities
3. **Versionado**: Historial de cambios con revisiones Drupal
4. **A/B Testing**: Posibilidad de variantes de contenido
5. **Multi-tenant**: Cada tenant puede personalizar su homepage

---

## Referencias

- [Directrices del Proyecto - Sección 5](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/00_DIRECTRICES_PROYECTO.md)
- [Template _hero.html.twig](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_hero.html.twig)
- [Template _features.html.twig](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_features.html.twig)
