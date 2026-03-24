# Plan: Configuración Per-Metasitio en Theme Settings — Clase Mundial

## Contexto

La pestaña "Meta-Sitio" (TAB 15) en `/admin/appearance/settings/ecosistema_jaraba_theme` tiene 18 campos muertos (hero_eyebrow, hero_headline, hero_subtitle, CTAs, 4 métricas) que se guardan en config pero **nunca se leen en ningún preprocess, controller ni template**. La fuente real del hero es `HomepageDataService` (dominio SaaS principal) y contenido hardcodeado inline en `page--front.html.twig` (metasitios pepejaraba, jarabaimpact, pde).

El admin del ecosistema Jaraba necesita un punto de control centralizado en theme settings para configurar el contenido diferenciado de cada metasitio (hero, estadísticas, CTAs, SEO), dado que los 4 metasitios son conocidos y gestionados por el mismo admin.

TAB 17 (SEO Multi-Dominio) ya implementa el patrón correcto per-metasite: `seo_{variant}_title/description` × 4 variantes. Este patrón se extiende a todos los campos de contenido per-metasitio.

---

## TOC — Índice de Navegación

1. [Diagnóstico completo](#1-diagnóstico-completo)
2. [Arquitectura de la solución](#2-arquitectura-de-la-solución)
3. [Fase 1 — SSOT Variant Map](#fase-1-ssot-variant-map)
4. [Fase 2 — Formulario per-metasitio + Schema](#fase-2-formulario-per-metasitio--schema)
5. [Fase 3 — Pipeline de inyección (preprocess)](#fase-3-pipeline-de-inyección-preprocess)
6. [Fase 4 — Templates: consumir metasite_content](#fase-4-templates-consumir-metasite_content)
7. [Fase 5 — Setup Wizard + Daily Actions](#fase-5-setup-wizard--daily-actions)
8. [Fase 6 — Validators + Safeguards](#fase-6-validators--safeguards)
9. [Fase 7 — Cleanup dead code + consolidación SEO](#fase-7-cleanup-dead-code--consolidación-seo)
10. [Tabla de correspondencia de especificaciones](#tabla-de-correspondencia)
11. [Ficheros a modificar/crear](#ficheros-a-modificarcreate)
12. [Plan de verificación](#plan-de-verificación)
13. [Salvaguardas futuras](#salvaguardas-futuras)

---

## 1. Diagnóstico completo

### 1.1 Dead code en TAB 15

Los 18 campos del TAB 15 actual (líneas 1035-1124 del .theme):
- `hero_eyebrow`, `hero_headline`, `hero_subtitle`, `hero_cta_primary_text`, `hero_cta_secondary_text`, `hero_cta_secondary_url`
- `stat_value_{1-4}`, `stat_suffix_{1-4}`, `stat_label_{1-4}`

Se guardan en `ecosistema_jaraba_theme.settings` y tienen schema (líneas 380-436), pero:
- `_ecosistema_jaraba_theme_get_base_settings()` NO los incluye en `$variables['theme_settings']`
- Ningún preprocess hook los inyecta en templates
- `_hero.html.twig` consume `hero` (de HomepageDataService), no `theme_settings.hero_*`
- Las ramas de `page--front.html.twig` para pepejaraba/jarabaimpact/pde tienen hero y stats **hardcodeados inline**

### 1.2 Contenido hardcodeado en page--front.html.twig (623 líneas)

| Variante | Líneas | Hero | Stats | Pain Points | CTA Final |
|----------|--------|------|-------|-------------|-----------|
| pepejaraba | 65-189 | Inline L76-78 | Inline L120-125 | Inline L86-115 | Inline L183-188 |
| jarabaimpact | 191-346 | Inline L202-206 | Inline L248-253 | Inline L214-243 | Inline L337-342 |
| pde | 347+ | Via HomepageDataService + audience | Via homepage.stats | Inline | Via HomepageDataService |
| generic | ~477+ | Via HomepageDataService | Via homepage.stats | Inline fallback | Via homepage.hero |

### 1.3 Variant Map triplicado

`[5 => 'pepejaraba', 6 => 'jarabaimpact', 7 => 'pde']` hardcodeado en:
- Línea 1472 (hook_preprocess_html)
- Línea 3276 (hook_preprocess_page)
- Línea 5415 (hook_page_attachments_alter)

### 1.4 Gaps en Setup Wizard / Daily Actions / Validators

- **0** Setup Wizard steps para theming/branding (de 63 steps totales)
- **0** Daily Actions para configuración visual (de 73 actions totales)
- **0** validators específicos para TenantThemeConfig o theme settings coherence

---

## 2. Arquitectura de la solución

### 2.1 Principio: contenido per-metasite en Theme Settings, diseño en TenantThemeConfig

| Tipo de config | Dónde | Quién lo gestiona | Por qué |
|---|---|---|---|
| **Contenido** (hero text, stats, CTAs, SEO) | Theme Settings per-variant tabs | Admin plataforma | Varía por discurso de marca, no por diseño |
| **Diseño visual** (colores, tipografía, layout) | TenantThemeConfig entity | Admin + tenant self-service | Cascada 5 niveles, L4 |
| **Estructura** (nav, páginas legales, logo) | SiteConfig entity | Admin via Site Builder | L5, override estructural |

### 2.2 Campos por pestaña de metasitio (24 campos × 4 variantes = 96 config keys)

Cada tab `metasite_{variant}` contiene:

**Fieldset: Hero** (7 campos)
- `{variant}_hero_eyebrow` — textfield, max 200
- `{variant}_hero_headline` — textfield, max 120 (H1)
- `{variant}_hero_subtitle` — textarea, 3 rows
- `{variant}_hero_cta_primary_text` — textfield
- `{variant}_hero_cta_primary_url` — textfield (default `/user/register`)
- `{variant}_hero_cta_secondary_text` — textfield
- `{variant}_hero_cta_secondary_url` — textfield

**Fieldset: Estadísticas / Social Proof** (12 campos: 4 métricas × 3)
- `{variant}_stat_value_{1-4}` — textfield, size 10
- `{variant}_stat_suffix_{1-4}` — textfield, size 5
- `{variant}_stat_label_{1-4}` — textfield, size 30

**Fieldset: CTA Final** (3 campos)
- `{variant}_cta_headline` — textfield, max 120
- `{variant}_cta_subtitle` — textarea
- `{variant}_cta_primary_text` — textfield

**Fieldset: SEO (migrado desde TAB 17)** (2 campos)
- `{variant}_seo_title` — textfield, max 120
- `{variant}_seo_description` — textarea

**Total: 24 campos × 4 variantes = 96 config keys**

### 2.3 Defaults por variante

Cada variante tiene defaults distintos extraídos del contenido actualmente hardcodeado en `page--front.html.twig`. Estos defaults viven como array constante en una función helper `_ecosistema_jaraba_theme_get_metasite_defaults()` que sirve tanto al formulario (placeholders) como al preprocess (fallbacks).

### 2.4 Convención de naming

Patrón: `{variant}_{section}_{field}` — sigue el patrón probado de TAB 17 (`seo_{variant}_*`) pero agrupando por variante primero para que cada tab sea autónomo.

Variantes: `generic`, `pde`, `jarabaimpact`, `pepejaraba` — idénticas a las de TAB 17.

---

## Fase 1 — SSOT Variant Map

### Qué
Extraer el mapa group_id → variant a un lugar único.

### Cómo
Añadir constantes a `MetaSiteResolverService`:

```php
public const VARIANT_MAP = [
    5 => 'pepejaraba',
    6 => 'jarabaimpact',
    7 => 'pde',
];
public const KNOWN_VARIANTS = ['generic', 'pde', 'jarabaimpact', 'pepejaraba'];
public const VARIANT_LABELS = [
    'generic' => 'SaaS Hub (plataformadeecosistemas.com)',
    'pde' => 'PED Corporativo (plataformadeecosistemas.es)',
    'jarabaimpact' => 'Franquicia (jarabaimpact.com)',
    'pepejaraba' => 'Marca Personal (pepejaraba.com)',
];
```

Actualizar las 3 referencias en `.theme` (líneas 1472, 3276, 5415) para usar:
```php
use Drupal\jaraba_site_builder\Service\MetaSiteResolverService;
// ...
$variantMap = MetaSiteResolverService::VARIANT_MAP;
```

### Fichero
- `web/modules/custom/jaraba_site_builder/src/Service/MetaSiteResolverService.php` — añadir 3 constantes

---

## Fase 2 — Formulario per-metasitio + Schema

### Qué
Reemplazar TAB 15 (18 campos genéricos muertos) por 4 tabs per-metasitio.

### Cómo

En `ecosistema_jaraba_theme.theme`, reemplazar líneas 1035-1124 con un bucle que genera 4 tabs:

```php
// TAB 15-18: METASITIOS (1 tab por dominio conocido)
$variants = MetaSiteResolverService::KNOWN_VARIANTS;
$variantLabels = MetaSiteResolverService::VARIANT_LABELS;
$defaults = _ecosistema_jaraba_theme_get_metasite_defaults();
$variantIcons = [
    'generic' => 'globe', 'pde' => 'building',
    'jarabaimpact' => 'franchise', 'pepejaraba' => 'user-profile',
];
$weight = -18;
foreach ($variants as $variant) {
    $form["metasite_{$variant}"] = [
        '#type' => 'details',
        '#title' => _ecosistema_jaraba_theme_tab_title(
            $variantIcons[$variant], t('Meta: @label', ['@label' => $variantLabels[$variant]]), '#FF8C42'
        ),
        '#group' => 'jaraba_tabs',
        '#weight' => $weight++,
    ];
    // ... Hero fieldset, Stats fieldset, CTA Final fieldset, SEO fieldset
    // Cada campo usa: '#default_value' => $config->get("{$variant}_hero_eyebrow") ?: ''
    // Placeholder: $defaults[$variant]['hero_eyebrow']
}
```

### Schema
Reemplazar los 18 campos muertos (líneas 380-436 de schema.yml) con 96 campos per-variant:

```yaml
# ── Metasite: generic ──
generic_hero_eyebrow:
  type: string
  label: 'Eyebrow hero — SaaS Hub'
generic_hero_headline:
  type: string
  label: 'Headline hero — SaaS Hub'
# ... (24 campos × 4 variantes)
```

### Migración SEO
Los 8 campos `seo_{variant}_title/description` de TAB 17 se renombran a `{variant}_seo_title/description`. En el formulario, añadir lógica de backward-compatibility:

```php
'#default_value' => $config->get("{$variant}_seo_title")
    ?: $config->get("seo_{$variant}_title")  // backward compat
    ?: '',
```

TAB 17 retiene SOLO `seo_active_languages` (campo global).

### Hook de lectura SEO
Actualizar `hook_preprocess_html()` y `hook_page_attachments_alter()` (las líneas que leen `seo_{variant}_title`) para leer primero `{variant}_seo_title` con fallback al nombre antiguo.

---

## Fase 3 — Pipeline de inyección (preprocess)

### Qué
Inyectar `$variables['metasite_content']` en `hook_preprocess_page()` con los datos per-variant desde theme settings.

### Cómo

Después de resolver `homepage_variant` (línea 3277), añadir:

```php
// METASITE-CONTENT-001: Inyectar contenido per-metasitio desde theme settings.
$variant = $variables['homepage_variant'];
$config = \Drupal::config('ecosistema_jaraba_theme.settings');
$defaults = _ecosistema_jaraba_theme_get_metasite_defaults();
$d = $defaults[$variant] ?? $defaults['generic'];

$variables['metasite_content'] = [
    'hero' => [
        'eyebrow' => $config->get("{$variant}_hero_eyebrow") ?: $d['hero_eyebrow'],
        'title' => $config->get("{$variant}_hero_headline") ?: $d['hero_headline'],
        'subtitle' => $config->get("{$variant}_hero_subtitle") ?: $d['hero_subtitle'],
        'cta_primary' => [
            'text' => $config->get("{$variant}_hero_cta_primary_text") ?: $d['hero_cta_primary_text'],
            'url' => $config->get("{$variant}_hero_cta_primary_url") ?: ($ped_urls['register'] ?? '/user/register'),
        ],
        'cta_secondary' => [
            'text' => $config->get("{$variant}_hero_cta_secondary_text") ?: $d['hero_cta_secondary_text'],
            'url' => $config->get("{$variant}_hero_cta_secondary_url") ?: ($ped_urls['metodo'] ?? '/metodo'),
        ],
    ],
    'stats' => [],
    'cta_final' => [
        'headline' => $config->get("{$variant}_cta_headline") ?: $d['cta_headline'],
        'subtitle' => $config->get("{$variant}_cta_subtitle") ?: $d['cta_subtitle'],
        'primary_text' => $config->get("{$variant}_cta_primary_text") ?: $d['cta_primary_text'],
    ],
];
// Stats 1-4.
for ($i = 1; $i <= 4; $i++) {
    $val = $config->get("{$variant}_stat_value_{$i}") ?: ($d["stat_value_{$i}"] ?? '');
    if ($val !== '') {
        $variables['metasite_content']['stats'][] = [
            'value' => $val,
            'suffix' => $config->get("{$variant}_stat_suffix_{$i}") ?: ($d["stat_suffix_{$i}"] ?? ''),
            'label' => $config->get("{$variant}_stat_label_{$i}") ?: ($d["stat_label_{$i}"] ?? ''),
        ];
    }
}
```

### Regla MEGAMENU-INJECT-001
`metasite_content` se inyecta como variable de preprocess — NO en `theme_settings` (que viaja via canal secundario). Es una variable de primer nivel que se pasa explícitamente a cada `{% include %}` con `only`.

---

## Fase 4 — Templates: consumir metasite_content

### Qué
Reemplazar el contenido hardcodeado inline en `page--front.html.twig` con referencias a `metasite_content`.

### Cómo

**Antes (pepejaraba, línea 75-78):**
```twig
hero: {
    eyebrow: '+30 anos de experiencia...'|t,
    title: 'Jose Jaraba — La experiencia...'|t,
    subtitle: 'Emprendedor serial...'|t,
}
```

**Después:**
```twig
hero: metasite_content.hero|default({}),
```

**Antes (stats pepejaraba, línea 120-125):**
```twig
stats: [
    { value: 30, suffix: '+', label: 'anos de experiencia'|t },
    ...
]
```

**Después:**
```twig
stats: metasite_content.stats|default([]),
```

**Antes (CTA final pepejaraba, línea 183-188):**
```twig
cta_headline: 'Hablemos...'|t,
cta_subtitle: '30+ anos...'|t,
```

**Después:**
```twig
cta_headline: metasite_content.cta_final.headline|default(''),
cta_subtitle: metasite_content.cta_final.subtitle|default(''),
cta_primary_text: metasite_content.cta_final.primary_text|default(''),
```

### Misma lógica para las 4 variantes

Los pain points y features NO migran a theme settings (son arrays complejos con iconos, demasiado granulares para un formulario). Se mantienen inline en Twig con `{% trans %}` para traducibilidad.

### Variante `generic` / `pde`

Para `generic` y `pde`: `HomepageDataService` sigue siendo la fuente primaria cuando hay HomepageContent entity. `metasite_content` actúa como fallback. Lógica en template:

```twig
{% set hero_data = homepage.hero|default(metasite_content.hero|default({})) %}
```

---

## Fase 5 — Setup Wizard + Daily Actions

### 5.1 Setup Wizard Step: ConfigurarMetaSiteContentStep

- **Fichero**: `web/modules/custom/ecosistema_jaraba_core/src/SetupWizard/ConfigurarMetaSiteContentStep.php`
- **Wizard ID**: `__global__`
- **Step ID**: `__global__.configurar_metasite_content`
- **Weight**: 95 (después de ConfigurarPromocionesStep en 90)
- **Label**: `t('Configurar contenido de metasitios')`
- **Description**: `t('Personaliza hero, estadísticas y CTA de cada dominio del ecosistema (PED, Pepe Jaraba, Jaraba Impact).')`
- **Icon**: `{ category: 'ui', name: 'globe', variant: 'duotone' }`
- **Route**: `system.theme_settings_theme` con parámetro `theme: ecosistema_jaraba_theme`
- **isComplete()**: Verifica que al menos 2 de las 4 variantes tienen `{variant}_hero_headline` no vacío
- **Patrón**: Idéntico a `ConfigurarPromocionesStep` (lee config directamente)

### 5.2 Daily Action: RevisarContenidoMetaSitesAction

- **Fichero**: `web/modules/custom/ecosistema_jaraba_core/src/DailyActions/RevisarContenidoMetaSitesAction.php`
- **Dashboard ID**: `__global__`
- **Action ID**: `__global__.revisar_contenido_metasites`
- **Weight**: 85
- **Label**: `t('Revisar contenido de metasitios')`
- **Description**: `t('Verifica que los 4 dominios del ecosistema tienen hero, estadísticas y CTA final configurados.')`
- **Icon**: `{ category: 'marketing', name: 'palette', variant: 'duotone' }`
- **Color**: `azul-corporativo`
- **Route**: `system.theme_settings_theme` con parámetro
- **Badge**: Cuenta variantes con hero_headline vacío. `badge_type: warning` si > 0

### 5.3 Tagged services

En `ecosistema_jaraba_core.services.yml`:
```yaml
ecosistema_jaraba_core.setup_wizard.configurar_metasite_content:
    class: Drupal\ecosistema_jaraba_core\SetupWizard\ConfigurarMetaSiteContentStep
    tags:
        - { name: ecosistema_jaraba_core.setup_wizard_step }

ecosistema_jaraba_core.daily_actions.revisar_contenido_metasites:
    class: Drupal\ecosistema_jaraba_core\DailyActions\RevisarContenidoMetaSitesAction
    tags:
        - { name: ecosistema_jaraba_core.daily_action }
```

---

## Fase 6 — Validators + Safeguards

### 6.1 validate-metasite-content-completeness.php (run_check)

Verifica que cada variante tiene configurados al menos:
- `{variant}_hero_headline` no vacío
- Al menos 2 stats de las 4
- `{variant}_seo_title` no vacío

Lee directamente `ecosistema_jaraba_theme.settings` via drush bootstrap.

### 6.2 validate-metasite-variant-map-ssot.php (run_check)

Grep del .theme file para detectar mapas hardcodeados `[5 =>` que no sean la referencia a la constante. Falla si encuentra mapas inline.

### 6.3 validate-metasite-dead-fields.php (run_check)

Verifica que los 18 campos muertos antiguos (`hero_eyebrow` sin prefijo de variante, `stat_value_1` sin prefijo) NO aparecen en el schema yml ni en el formulario.

### 6.4 Registro en validate-all.sh

```bash
run_check "METASITE-CONTENT-COMPLETENESS-001" "php scripts/validation/validate-metasite-content-completeness.php"
run_check "METASITE-VARIANT-MAP-SSOT-001" "php scripts/validation/validate-metasite-variant-map-ssot.php"
run_check "METASITE-DEAD-FIELDS-001" "php scripts/validation/validate-metasite-dead-fields.php"
```

---

## Fase 7 — Cleanup dead code + consolidación SEO

### 7.1 Eliminar campos muertos

- Schema: eliminar líneas 380-436 (18 campos `hero_*`, `stat_*`)
- Formulario: eliminar líneas 1035-1124 (TAB 15 genérico)

### 7.2 Consolidar SEO

- Schema: renombrar `seo_generic_title` → `generic_seo_title` (y los otros 7)
- TAB 17: eliminar el fieldset `seo_metasite_section` (migrado a tabs per-metasite)
- TAB 17: retener SOLO `seo_active_languages`
- Hook lecturas SEO: actualizar para leer nuevo naming con fallback al antiguo

### 7.3 Actualizar documentación

- `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`: sección sobre Capa 3 per-metasite
- Aprendizaje nuevo: METASITE-CONTENT-001

---

## Tabla de correspondencia

| Directriz | Cumplimiento en este plan |
|---|---|
| THEMING-UNIFY-001 | `metasite_content` se inyecta en preprocess_page, no bypasea UnifiedThemeResolverService |
| SSOT-THEME-001 | Contenido en theme settings (admin), diseño visual en TenantThemeConfig (SSOT visual) |
| CSS-VAR-ALL-COLORS-001 | No se tocan colores CSS. Los tabs son de contenido, no de diseño |
| SCSS-COMPILE-VERIFY-001 | No hay cambios SCSS (formulario usa admin theme de Drupal) |
| TWIG-INCLUDE-ONLY-001 | `metasite_content` se pasa explícitamente en cada `{% include ... only %}` |
| MEGAMENU-INJECT-001 | `metasite_content` NO viaja por `theme_settings` — es variable de primer nivel |
| ZERO-REGION-001 | Variables via `hook_preprocess_page()`, no controller |
| ICON-CONVENTION-001 | Wizard step usa `jaraba_icon('ui', 'globe', {variant:'duotone'})` |
| ICON-DUOTONE-001 | `variant: 'duotone'` en ambos (step + action) |
| ICON-COLOR-001 | `azul-corporativo` para Daily Action |
| Textos traducibles | `$this->t()` en PHP, `{% trans %}` no aplica (defaults son strings PHP) |
| SCSS model | Sin cambios SCSS. Variables inyectables desde UI Drupal via theme settings |
| ROUTE-LANGPREFIX-001 | CTAs usan `ped_urls` (resueltas via `Url::fromRoute/fromUserInput`) |
| NO-HARDCODE-PRICE-001 | Precios siguen en MetaSitePricingService, no en theme settings |
| SETUP-WIZARD-DAILY-001 | Nuevo step + nueva action, tagged services + CompilerPass |
| IMPLEMENTATION-CHECKLIST-001 | Fase verificación con checklist completo |
| RUNTIME-VERIFY-001 | 5 checks post-implementación documentados |
| CONTROLLER-READONLY-001 | No hay controllers afectados |
| PHANTOM-ARG-001 | services.yml sin args (step/action usan DI mínima) |
| OPTIONAL-CROSSMODULE-001 | No hay refs cross-modulo en los nuevos services |
| CONTENT-SEED-PIPELINE-001 | Theme settings NO afectan content seed (son config, no content entities) |
| DOC-GUARD-001 | Docs actualizados via Edit incremental, commit separado con prefijo `docs:` |
| HOMEPAGE-ELEVATION-001 | Score 15/15 preservado: mismo contenido, diferente fuente |
| LANDING-CONVERSION-SCORE-001 | Los 15 criterios no cambian: mismos partials, mismos includes |

---

## Ficheros a modificar/crear

### MODIFICAR

| Fichero | Cambios |
|---|---|
| `web/modules/custom/jaraba_site_builder/src/Service/MetaSiteResolverService.php` | +3 constantes (VARIANT_MAP, KNOWN_VARIANTS, VARIANT_LABELS) |
| `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | Reemplazar TAB 15 (L1035-1124) con 4 tabs per-variant, añadir helper `_get_metasite_defaults()`, actualizar 3 variant maps, inyectar `metasite_content` en preprocess_page, actualizar lectura SEO |
| `web/themes/custom/ecosistema_jaraba_theme/config/schema/ecosistema_jaraba_theme.schema.yml` | -18 campos muertos, +96 campos per-variant, renombrar 8 campos SEO |
| `web/themes/custom/ecosistema_jaraba_theme/templates/page--front.html.twig` | Reemplazar contenido inline de hero/stats/cta_final con `metasite_content.*` en las 4 ramas |
| `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | +2 tagged services |
| `scripts/validation/validate-all.sh` | +3 run_check entries |

### CREAR

| Fichero | Propósito |
|---|---|
| `web/modules/custom/ecosistema_jaraba_core/src/SetupWizard/ConfigurarMetaSiteContentStep.php` | Setup wizard step |
| `web/modules/custom/ecosistema_jaraba_core/src/DailyActions/RevisarContenidoMetaSitesAction.php` | Daily action |
| `scripts/validation/validate-metasite-content-completeness.php` | Validator contenido |
| `scripts/validation/validate-metasite-variant-map-ssot.php` | Validator SSOT map |
| `scripts/validation/validate-metasite-dead-fields.php` | Validator dead code |

---

## Plan de verificación

### RUNTIME-VERIFY-001

1. **CSS compilado**: N/A — no hay cambios SCSS (formulario usa admin theme)
2. **Tablas DB**: N/A — config schema, no tablas. Verificar `drush config:status`
3. **Rutas accesibles**: `/admin/appearance/settings/ecosistema_jaraba_theme` — verificar 4 nuevos tabs visibles
4. **data-* selectores**: N/A — sin cambios JS
5. **drupalSettings**: N/A — `metasite_content` via preprocess, no drupalSettings

### Test End-to-End

1. Visitar `/admin/appearance/settings/ecosistema_jaraba_theme` → ver 4 tabs "Meta: ..."
2. Rellenar hero de pepejaraba → Guardar → Visitar `pepejaraba.jaraba-saas.lndo.site` → Verificar hero refleja los valores
3. Dejar jarabaimpact vacío → Visitar jarabaimpact domain → Verificar defaults funcionan
4. Verificar `generic` sigue usando HomepageDataService cuando hay entity
5. Verificar SEO: `{variant}_seo_title` se refleja en `<title>` de homepage
6. `lando drush cr` + revisitar → contenido persiste
7. Ejecutar 3 nuevos validators: todos PASS
8. Ejecutar `validate-homepage-completeness.php`: sigue 15/15
9. Verificar Setup Wizard muestra el nuevo step
10. Verificar Daily Actions muestra la nueva action

### PIPELINE-E2E-001

- L1: Sin nuevo servicio en controller — todo en preprocess
- L2: `metasite_content` en `$variables` de preprocess_page
- L3: `page--front.html.twig` es page template — no requiere hook_theme()
- L4: Templates consumen con `|default()` fallbacks

---

## Salvaguardas futuras

1. **METASITE-CONTENT-COMPLETENESS-001**: Validator permanente que alerta si algún metasitio tiene campos vacíos
2. **METASITE-VARIANT-MAP-SSOT-001**: Impide que el mapa se duplique de nuevo en el futuro
3. **METASITE-DEAD-FIELDS-001**: Impide que campos sin consumidor se acumulen en schema
4. **Backward-compat SEO**: Lectura dual `{variant}_seo_title` → fallback `seo_{variant}_title` durante 2 meses, después eliminar fallback
5. **TenantThemeConfig inert fields**: Tarea separada para conectar los 7 campos inertes (shadow_intensity, hero_variant, etc.) — fuera de scope de este plan
6. **Pain Points configurable**: Futura evolución si se necesita configurar pain points per-metasite — actualmente son arrays complejos con iconos, no aptos para theme settings simples. Candidato a ConfigEntity o Page Builder
