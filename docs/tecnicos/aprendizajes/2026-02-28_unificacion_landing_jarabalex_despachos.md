# Aprendizaje #152 — Unificacion Landing JarabaLex + Despachos

**Fecha:** 2026-02-28
**Contexto:** Plan de unificacion de la landing `/despachos` en `/jarabalex` — 6 fases, 21 hallazgos de auditoria (6 P0, 8 P1, 6 P2).
**Documentos referencia:**
- Auditoria: `docs/analisis/2026-02-28_Auditoria_Landing_JarabaLex_Despachos_Clase_Mundial_v1.md`
- Plan: `docs/implementacion/2026-02-28_Plan_Unificacion_Landing_JarabaLex_Despachos_Clase_Mundial_v1.md`

---

## Problema

La landing `/despachos` advertia features respaldadas por modulos deshabilitados y sin FreemiumVerticalLimit configs. "Despachos" no era un vertical canonico (ausente de `BaseAgent::VERTICALS`), causando cascadas de pricing rotas. LexNET — el diferenciador clave para abogados espanoles — estaba completamente ausente de la landing.

## Hallazgos Clave

### 1. PHP 8.4 + Drupal 11 ControllerBase Readonly Property Conflict (CONTROLLER-READONLY-001)

**Sintoma:** `PHP Fatal error: Cannot redeclare non-readonly property ControllerBase::$entityTypeManager as readonly`

**Causa raiz:** `ControllerBase::$entityTypeManager` tiene declaracion `protected` sin tipo en Drupal 11. Cuando un subclass usa constructor promotion con `protected readonly EntityTypeManagerInterface $entityTypeManager`, PHP 8.4 rechaza redeclarar una propiedad non-readonly como readonly.

**Solucion:** Quitar la propiedad del constructor promotion y asignar manualmente:
```php
public function __construct(
  protected readonly MyService $myService,
  EntityTypeManagerInterface $entityTypeManager, // Sin promotion
  protected readonly LoggerInterface $logger,
) {
  $this->entityTypeManager = $entityTypeManager; // Asignar manualmente
}
```

**Impacto:** Afecto a 5 controllers legales (BillingApiController, QuoteApiController, LexnetApiController, TemplatesApiController, SlaApiController). Tambien afecta a CUALQUIER controller que inyecte `$entityTypeManager` via constructor promotion con `readonly`.

**Deteccion proactiva:** `grep -rn "readonly EntityTypeManagerInterface" web/modules/custom/`

### 2. Unificacion de Landings Verticales via 301 Redirect

**Patron:** Cuando dos landings de un mismo vertical tienen features solapadas:
1. Unificar en la landing principal con 301 redirect via `Url::fromRoute()` (NUNCA hardcodear paths — ROUTE-LANGPREFIX-001)
2. Actualizar TODAS las referencias en un cambio coordinado:
   - Megamenu (Twig template)
   - Redirects relacionados (ej: `legalRedirect()`)
   - SEO meta + Schema.org (PageAttachmentsHooks)
   - Theme `$vertical_routes` y `$landing_routes`
3. Mover features de la landing absorbida a la principal
4. Verificar que el vertical es canonico (`BaseAgent::VERTICALS`)

### 3. FreemiumVerticalLimit Batch Creation

**Patron ID:** `{vertical}_{plan}_{feature_key}` (ej: `jarabalex_free_max_cases`)
**Valores especiales:** `-1` = ilimitado, `0` = bloqueado (trigger upgrade)
**Sincronizacion obligatoria en 4 puntos:**
1. `FeatureGateService::FEATURE_TRIGGER_MAP` — mapea feature_key → trigger_type
2. `UpgradeTriggerService::TRIGGER_TYPES` — conversion rates
3. `UpgradeTriggerService::getTitleAndMessage()` — titulos y mensajes traducibles
4. `UpgradeTriggerService::getIconForType()` — iconos
5. `UpgradeTriggerService::buildTriggerResponse()` — `$triggerTypesWithLimits`

Si falta CUALQUIERA de los 5, el trigger no funciona o causa errores.

### 4. PageAttachmentsHooks DI Pattern

Para inyectar dependencias en hook classes OOP (Drupal 11), registrar como servicio en `services.yml` con argumentos explicitos:
```yaml
ecosistema_jaraba_core.page_attachments_hooks:
  class: Drupal\ecosistema_jaraba_core\Hook\PageAttachmentsHooks
  arguments:
    - '@config.factory'
    - '@module_handler'
    - '@router.admin_context'
    - '@current_route_match'  # Nuevo argumento
```

### 5. VaultApiController Syntax Error

**Sintoma:** `PHP Parse error: syntax error, unexpected ','`
**Causa raiz:** Brackets mal cerrados en JsonResponse anidado:
```php
// MAL:
$this->serializeDocument($result['document', 'meta' => ...])
// BIEN:
$this->serializeDocument($result['document']), 'meta' => [...]
```
**Leccion:** Siempre lint PHP antes de habilitar modulos con `php -l`.

## Metricas

- **Modulos habilitados:** 5 (calendar, vault, billing, lexnet, templates)
- **FreemiumVerticalLimit nuevos:** 18 (6 feature_keys × 3 planes)
- **FreemiumVerticalLimit totales:** 36
- **UpgradeTrigger types totales:** 11
- **Features en landing /jarabalex:** 8 (antes 6)
- **FAQs en landing:** 10 (antes 8)
- **Controllers PHP corregidos:** 5
- **Tests passing:** 128 unit + 17 controller
- **Ficheros PHP modificados:** 10
- **Ficheros YAML creados/modificados:** 21

## Reglas Establecidas

- **LEGAL-LANDING-UNIFIED-001 (P0):** `/despachos` DEBE redirigir 301 a `/jarabalex`. NUNCA mantener landings duplicadas para el mismo vertical.
- **CONTROLLER-READONLY-001 (P0):** NUNCA usar constructor promotion con `readonly` para propiedades heredadas de `ControllerBase` (`$entityTypeManager`, `$formBuilder`, etc.). Asignar manualmente en el body del constructor.

## Documentacion Actualizada

| Documento | Version | Cambio |
|-----------|---------|--------|
| DIRECTRICES | v102.0.0 | +2 reglas (LEGAL-LANDING-UNIFIED-001, CONTROLLER-READONLY-001), modulo inventory actualizado |
| ARQUITECTURA | v91.0.0 | +1 changelog, seccion 12.6 actualizada, FreemiumVerticalLimit 36, trigger types 11 |
| INDICE | v131.0.0 | +1 changelog entry |
| FLUJO | v55.0.0 | +3 patrones, reglas de oro #88-#89 |
