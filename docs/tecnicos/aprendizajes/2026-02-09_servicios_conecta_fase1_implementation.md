# Aprendizaje #53: ServiciosConecta Fase 1 — Marketplace Profesional

**Fecha:** 2026-02-09
**Módulo:** `jaraba_servicios_conecta`
**Contexto:** Implementación completa de la Fase 1 del vertical ServiciosConecta, siguiendo los patrones establecidos por AgroConecta y ComercioConecta.

---

## Resumen

Implementación del módulo `jaraba_servicios_conecta` como nuevo vertical de la plataforma SaaS, proporcionando un marketplace de servicios profesionales con sistema de reservas, perfiles de proveedores, y portal de gestión.

---

## Lecciones Aprendidas

### 1. Dart Sass `@use` Module System — Cada Partial Necesita Sus Imports

**Problema:** El CSS no compilaba con error `Undefined variable: $container-max` en `_marketplace.scss`.

**Causa raíz:** Dart Sass con `@use` no hereda variables del archivo principal (`main.scss`) hacia los parciales cargados con `@use`. Cada archivo parcial es un módulo independiente.

**Solución:** Añadir `@use 'sass:color';` y `@use 'variables' as *;` al inicio de cada parcial SCSS que necesite variables o funciones de color.

**Regla:** En Dart Sass, SIEMPRE declarar `@use 'variables' as *;` en cada parcial. NO confiar en que `main.scss` propague las variables.

### 2. Patrón de Replicación Vertical — Checklist Verificado

El patrón establecido en AgroConecta se replica eficazmente para nuevos verticales:

| Componente | Patrón | Verificado |
|------------|--------|------------|
| Content Entities | `@ContentEntityType` + handlers + Field UI + Views | ✅ |
| Taxonomías | `config/install/*.yml` + `hook_install()` para términos | ✅ |
| Controllers | Marketplace + Detail + Dashboard + API REST | ✅ |
| Services | Business logic separada en `src/Service/` | ✅ |
| SCSS | `_variables.scss` → parciales → `main.scss` | ✅ |
| Twig | Clean templates + `{% include ... only %}` | ✅ |
| Routing | Frontend (público) + Portal (autenticado) + Admin | ✅ |
| Permisos | `*.permissions.yml` granulares por rol | ✅ |

**Ahorro estimado:** ~30% del tiempo respecto a la primera implementación (AgroConecta), gracias a patrones establecidos.

### 3. BEM + CSS Custom Properties — Patrón Federated Tokens

El patrón `var(--ej-primary, $fallback)` funciona correctamente para:
- **Compilación estática:** El fallback `$servicios-primary` se usa si no hay custom properties
- **Runtime dinámico:** Los tenants pueden sobreescribir `--ej-primary` via CSS

Cada vertical define sus propios fallbacks en `_variables.scss`:
```scss
$servicios-primary: #2563EB;    // Azul profesional
$servicios-accent: #7C3AED;     // Violeta premium
```

### 4. Entity Handlers Completos — AdminHtmlRouteProvider

Para que las entidades tengan rutas admin completas (CRUD + Field UI + Views), se necesita:

```php
handlers = {
  "list_builder" = "...",
  "views_data" = "...",
  "form" = {
    "default" = "...",
    "add" = "...",
    "edit" = "...",
    "delete" = "...",
  },
  "access" = "...",
  "route_provider" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
}
```

**Clave:** `field_ui_base_route` debe apuntar al formulario de settings del módulo para que las pestañas de Field UI aparezcan correctamente.

### 5. `color.scale()` vs `darken()`/`lighten()` — Dart Sass Moderno

**Regla establecida:** NUNCA usar `darken()` o `lighten()` (deprecated). SIEMPRE usar `color.scale()`:

```scss
@use 'sass:color';

// Correcto
background: color.scale($servicios-online, $lightness: 85%);

// Incorrecto (deprecated)
background: lighten($servicios-online, 85%);
```

### 6. hook_preprocess_html() para Body Classes — NO attributes.addClass()

En Drupal 11, las body classes deben añadirse via `hook_preprocess_html()`:

```php
function jaraba_servicios_conecta_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  if (str_starts_with($route, 'jaraba_servicios_conecta.')) {
    $variables['attributes']['class'][] = 'vertical-servicios';
  }
}
```

**NO funciona:** `$variables['attributes']->addClass()` porque `attributes` es un array en `html.html.twig`, no un objeto Attribute.

### 7. Taxonomías via Config Install + hook_install()

- **Vocabularios:** Se crean via `config/install/taxonomy.vocabulary.*.yml` (se instalan automáticamente al habilitar el módulo)
- **Términos:** Se crean via `hook_install()` en PHP (no se pueden crear como config porque son content entities)

```php
function jaraba_servicios_conecta_install() {
  $terms = ['Abogado', 'Arquitecto', 'Consultor', ...];
  foreach ($terms as $name) {
    Term::create([
      'vid' => 'servicios_category',
      'name' => $name,
    ])->save();
  }
}
```

---

## Arquitectura del Módulo

```
jaraba_servicios_conecta/
├── jaraba_servicios_conecta.info.yml
├── jaraba_servicios_conecta.module
├── jaraba_servicios_conecta.install
├── jaraba_servicios_conecta.routing.yml
├── jaraba_servicios_conecta.permissions.yml
├── jaraba_servicios_conecta.services.yml
├── jaraba_servicios_conecta.libraries.yml
├── config/install/
│   ├── taxonomy.vocabulary.servicios_category.yml
│   └── taxonomy.vocabulary.servicios_modality.yml
├── src/
│   ├── Entity/
│   │   ├── ProviderProfile.php
│   │   ├── ServiceOffering.php
│   │   ├── Booking.php
│   │   ├── AvailabilitySlot.php
│   │   └── ServicePackage.php
│   ├── Controller/
│   │   ├── MarketplaceController.php
│   │   ├── ProviderDetailController.php
│   │   └── ProviderDashboardController.php
│   ├── Service/
│   │   ├── BookingService.php
│   │   ├── SearchService.php
│   │   ├── AvailabilityService.php
│   │   └── StatisticsService.php
│   └── Form/
│       └── ServiciosSettingsForm.php
├── templates/
│   ├── servicios-marketplace.html.twig
│   ├── servicios-provider-detail.html.twig
│   └── servicios-provider-dashboard.html.twig
├── scss/
│   ├── _variables.scss
│   ├── _marketplace.scss
│   ├── _provider-detail.scss
│   ├── _provider-dashboard.scss
│   ├── _components.scss
│   └── main.scss
├── css/
│   └── jaraba-servicios-conecta.css
├── package.json
└── node_modules/ (dev)
```

---

## Verificación de Instalación

| Verificación | Resultado |
|-------------|-----------|
| `lando drush en jaraba_servicios_conecta -y` | ✅ OK |
| `lando drush entity-updates -y` | ✅ Tablas creadas |
| 5 tablas en BD (provider_profile, service_offering, booking, availability_slot, service_package) | ✅ Verificado |
| 2 taxonomías (servicios_category, servicios_modality) | ✅ Verificado |
| ~170 rutas registradas (entity CRUD, Field UI, Layout Builder, frontend, API, portal) | ✅ Verificado |
| SCSS compilado sin errores (Dart Sass) | ✅ Verificado |

---

## Pendiente para Fases Siguientes

1. **Fase 2:** Calendar Sync (FullCalendar), Video Conferencing (Jitsi Meet)
2. **Fase 3:** Buzón de Confianza, Firma Digital PAdES, Portal Cliente Documental
3. **Fase 4:** AI Triaje de Casos, Presupuestador Automático, Copilot Servicios
4. **Frontend:** AJAX filtering en marketplace, Schema.org JSON-LD, reviews
5. **Stripe Connect:** Pagos split para profesionales

---

> **Versión:** 1.0.0 | **Fecha:** 2026-02-09 | **Autor:** IA Asistente
