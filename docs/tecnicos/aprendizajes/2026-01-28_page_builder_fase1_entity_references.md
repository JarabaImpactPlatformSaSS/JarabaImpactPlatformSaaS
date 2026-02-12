# 📝 Aprendizaje: Page Builder Fase 1 - Entity References y Navegación

> **Fecha:** 2026-01-28
> **Contexto:** Planificación de migración de contenido hardcodeado homepage
> **Decisión arquitectónica:** Entity References vs JSON fields

---

## 🎯 Decisión: Entity References para Flexibilidad

**Pregunta:** ¿Cómo estructurar los datos de las secciones de homepage (features, stats, intentions)?

| Opción | Ventajas | Desventajas |
|--------|----------|-------------|
| **JSON fields** | Simple, menos código | Sin Field UI, sin Views |
| **Entity References** ✅ | Field UI, Views, traducciones | Más entidades |

**Decisión:** Entity References para máxima flexibilidad y cumplimiento de directrices.

---

## 📁 Estructura de Entidades

```
HomepageContent (Content Entity principal)
├── hero_* (campos simples: title, subtitle, CTAs)
├── features → entity_reference → FeatureCard
├── stats → entity_reference → StatItem
└── intentions → entity_reference → IntentionCard
```

### Entidades Auxiliares (Paragraphs-like)

| Entidad | Campos | Propósito |
|---------|--------|-----------|
| `FeatureCard` | title, description, badge, icon, weight | Tarjetas de características |
| `StatItem` | value, suffix, label, weight | Métricas numéricas |
| `IntentionCard` | title, description, icon, url, color_class | Tarjetas de avatar/vertical |

---

## 🔀 Navegación Admin Correcta

> De workflow `/drupal-custom-modules.md`

### Ubicación de Rutas

| Tipo | Path | Ejemplo |
|------|------|---------|
| **Content Entities** | `/admin/content/` | `/admin/content/homepage` |
| **Field UI** | `/admin/structure/` | `/admin/structure/homepage-content` |
| **Settings** | `/admin/config/` | `/admin/config/page-builder/settings` |

### 4 Archivos YAML Obligatorios

```
jaraba_page_builder/
├── *.routing.yml       # URLs de entidad
├── *.links.menu.yml    # Menú en /admin/structure
├── *.links.task.yml    # Tab en /admin/content
├── *.links.action.yml  # Botón "Añadir"
```

### Handler Checklist

```php
/**
 * @ContentEntityType(
 *   handlers = {
 *     "list_builder" = "...\HomepageContentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",  // ← Views
 *     "route_provider" = { "html" = "...AdminHtmlRouteProvider" },
 *   },
 *   field_ui_base_route = "entity.homepage_content.settings",
 * )
 */
```

---

## ✅ Checklist Cumplimiento

- [x] Entity References para flexibilidad
- [x] Field UI habilitado (`field_ui_base_route`)
- [x] Views integration (`views_data` handler)
- [x] Navegación `/admin/content` y `/admin/structure`
- [x] 4 archivos YAML definidos
- [x] Slide-panel modals para CRUD (ver workflow)
- [x] i18n con `$this->t()` y `{% trans %}`
- [x] Iconos con `jaraba_icon()` (no emojis)
- [x] SCSS con CSS variables `var(--ej-*)`

---

## 📚 Referencias

- [Implementation Plan](file:///C:/Users/Pepe%20Jaraba/.gemini/antigravity/brain/751fd5c1-8105-4e8d-a402-44ec223ff630/implementation_plan.md)
- [Workflow drupal-custom-modules.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/drupal-custom-modules.md)
- [Workflow slide-panel-modales.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/slide-panel-modales.md)
- [DIRECTRICES_DESARROLLO.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/DIRECTRICES_DESARROLLO.md)
