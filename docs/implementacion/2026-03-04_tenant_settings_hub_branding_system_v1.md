# Tenant Settings Hub + Branding System — Documento de Implementacion

**Fecha:** 2026-03-04
**Version:** 1.0.0
**Estado:** IMPLEMENTADO

## Resumen Ejecutivo

Implementacion del hub unificado de configuracion de tenants en `/my-settings` con 6 secciones extensibles via tagged services, correccion de 4 bugs criticos en la cascada CSS y seguridad de tenant.

## Bugs Corregidos

### 1. ThemeTokenService nunca resolvia CSS per-tenant
- **Archivo:** `jaraba_theming/src/Service/ThemeTokenService.php`
- **Problema:** `generateCss()` se llamaba sin `tenant_id`, fallback siempre devolvia config de plataforma.
- **Solucion:** Inyeccion de `TenantContextService` (opcional, `@?`), metodo `resolveTenantId()`, auto-resolucion en `generateCss()` y `getActiveConfig()`.

### 2. Conflicto cascada CSS
- **Archivo:** `ecosistema_jaraba_theme.theme` (preprocess_html)
- **Problema:** 3 sistemas inyectaban `--ej-*` vars y theme settings ganaba siempre.
- **Solucion:** Nuevo bloque `<style id="jaraba-tenant-tokens">` inyectado DESPUES de `jaraba_css_vars` y `jaraba_custom_css`. Cascada: Platform -> Custom -> Tenant (gana).

### 3. TenantThemeCustomizerForm creaba configs sin tenant_id
- **Archivo:** `jaraba_theming/src/Form/TenantThemeCustomizerForm.php`
- **Problema:** `create()` no establecia `tenant_id` en la entity.
- **Solucion:** Inyeccion de `TenantContextService`, `tenant_id` se establece al crear nueva config.

### 4. SiteConfigAccessControlHandler sin aislamiento de tenant
- **Archivo:** `jaraba_site_builder/src/SiteConfigAccessControlHandler.php`
- **Problema:** Leia `tenant_id` pero nunca lo comparaba.
- **Solucion:** Implementa `EntityHandlerInterface` con DI de `TenantContextService`, metodo `checkTenantIsolation()` para update/delete con comparacion estricta `===`.

## Arquitectura: Tenant Settings Registry

### Patron: Tagged Services + CompilerPass

```
TenantSettingsSectionInterface
    <- AbstractTenantSettingsSection (base con defaults)
        <- DomainSection (weight: 0)
        <- PlanSection (weight: 10)
        <- BrandingSection (weight: 20)
        <- DesignSection (weight: 30)
        <- ApiKeysSection (weight: 40)
        <- WebhooksSection (weight: 50)

TenantSettingsRegistry
    -> addSection() llamado por TenantSettingsSectionPass
    -> getAccessibleSections() filtra por isAccessible() y ordena por weight

TenantSettingsSectionPass (CompilerPass)
    -> Registrado en EcosistemaJarabaCoreServiceProvider::register()
    -> Procesa tag: ecosistema_jaraba_core.tenant_settings_section
```

### Extensibilidad
Cualquier modulo puede agregar secciones al hub registrando un servicio con el tag:
```yaml
my_module.tenant_settings_section.custom:
  class: Drupal\my_module\TenantSettings\CustomSection
  arguments: ['@current_user']
  tags:
    - { name: ecosistema_jaraba_core.tenant_settings_section }
```

## Archivos Nuevos

| Archivo | Proposito |
|---------|-----------|
| `ecosistema_jaraba_core/src/TenantSettings/TenantSettingsSectionInterface.php` | Contrato |
| `ecosistema_jaraba_core/src/TenantSettings/AbstractTenantSettingsSection.php` | Base class |
| `ecosistema_jaraba_core/src/TenantSettings/TenantSettingsRegistry.php` | Registry |
| `ecosistema_jaraba_core/src/DependencyInjection/Compiler/TenantSettingsSectionPass.php` | CompilerPass |
| `ecosistema_jaraba_core/src/TenantSettings/Section/{Domain,Plan,Branding,Design,ApiKeys,Webhooks}Section.php` | 6 secciones |
| `ecosistema_jaraba_core/src/Form/TenantBrandingSettingsForm.php` | Form de branding |
| `ecosistema_jaraba_core/js/tenant-settings.js` | Busqueda client-side |
| `ecosistema_jaraba_theme/scss/routes/tenant-settings.scss` | Route SCSS |
| `ecosistema_jaraba_theme/css/routes/tenant-settings.css` | CSS compilado |

## Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `jaraba_theming/src/Service/ThemeTokenService.php` | +TenantContextService, +resolveTenantId() |
| `jaraba_theming/jaraba_theming.services.yml` | +@?ecosistema_jaraba_core.tenant_context |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | +jaraba-tenant-tokens bloque, +route library map |
| `jaraba_site_builder/src/SiteConfigAccessControlHandler.php` | +EntityHandlerInterface, +checkTenantIsolation() |
| `jaraba_theming/src/Form/TenantThemeCustomizerForm.php` | +TenantContextService, +tenant_id on create |
| `ecosistema_jaraba_core/src/EcosistemaJarabaCoreServiceProvider.php` | +TenantSettingsSectionPass |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | +registry, +6 secciones |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml` | +branding route, +design route |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.module` | hook_theme variables actualizadas |
| `ecosistema_jaraba_core/src/Controller/TenantSelfServiceController.php` | +TenantSettingsRegistry DI |
| `ecosistema_jaraba_core/templates/tenant-self-service-settings.html.twig` | Reescrito: jaraba_icon(), {% trans %}, a11y |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.libraries.yml` | +route-tenant-settings |

## Directrices Aplicadas

| Directriz | Aplicacion |
|-----------|------------|
| TENANT-ISOLATION-ACCESS-001 | SiteConfigAccessControlHandler |
| TENANT-002 | TenantContextService en ThemeTokenService y forms |
| OPTIONAL-CROSSMODULE-001 | @? para TenantContextService en jaraba_theming |
| ACCESS-STRICT-001 | (int)===(int) en ACH |
| CSS-VAR-ALL-COLORS-001 | Todo SCSS usa var(--ej-*, fallback) |
| SCSS-COMPILE-VERIFY-001 | Timestamp verificado |
| ICON-CONVENTION-001 | jaraba_icon() en template y secciones |
| ICON-EMOJI-001 | Cero emojis en template |
| ZERO-REGION-001 | Template sin CSS inline ni regions |
| ROUTE-LANGPREFIX-001 | URLs via path() en Twig |
| i18n | {% trans %} bloques |
| SCSS-COLORMIX-001 | color-mix() en tenant-settings.scss |

## Rutas

| Ruta | Controlador/Form |
|------|-----------------|
| `/my-settings` | TenantSelfServiceController::settings() |
| `/my-settings/branding` | TenantBrandingSettingsForm |
| `/my-settings/design` | TenantThemeCustomizerForm |
| `/my-settings/domain` | TenantDomainSettingsForm |
| `/my-settings/api-keys` | TenantApiKeysForm |
| `/my-settings/webhooks` | TenantWebhooksForm |

## Cascada CSS Final

```
1. <style id="jaraba-design-tokens">    (jaraba_theming module, platform fallback)
2. <style> jaraba_css_vars             (theme settings, admin Apariencia)
3. <style> jaraba_custom_css           (campo libre custom CSS)
4. <style id="jaraba-tenant-tokens">   (tenant-specific, GANA por orden)
```
