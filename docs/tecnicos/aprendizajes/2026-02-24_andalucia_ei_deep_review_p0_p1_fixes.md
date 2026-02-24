# Aprendizaje #111: Andalucia +ei — Auditoria Profunda de Flujo y Correccion P0/P1

**Fecha:** 2026-02-24
**Contexto:** Revision exhaustiva del flujo completo de Andalucia +ei desde 9 perspectivas senior (arquitectura SaaS, ingenieria de software, UX, Drupal, theming, GrapesJS, SEO/GEO, IA) revelo 2 hallazgos P0, 6 P1 y 4 P2.
**Impacto:** Vertical Andalucia +ei — Seguridad, aislamiento multi-tenant, calidad de codigo, compliance con directrices

---

## 1. Problema

Una auditoria profunda del flujo Andalucia +ei (dashboard, formulario publico de solicitud, APIs slide-panel, entidades, controladores, SCSS, emails) revelo deficiencias de seguridad y calidad:

| # | Severidad | Hallazgo |
|---|-----------|----------|
| P0-1 | Critica | Ruta DELETE de API sin proteccion CSRF (`_csrf_request_header_token`) |
| P0-2 | Critica | Entidad `SolicitudEi` sin campo `tenant_id` — viola aislamiento multi-tenant (Regla de Oro #4) |
| P1-3 | Alta | Emojis Unicode en email de confirmacion (subject y body) — viola directriz de iconos |
| P1-4 | Alta | Literal Unicode `⚠` en SCSS en vez de escape `\26A0` — viola directriz de iconos |
| P1-5 | Alta | `\Drupal::service('renderer')` en `AndaluciaEiApiController` — viola DI |
| P1-6 | Alta | `\Drupal::hasService()` + `\Drupal::service()` x3 en `AndaluciaEiController` — viola DI |
| P1-7 | Alta | Duplicacion `user_id` / `uid` en `ProgramaParticipanteEi` — EntityOwnerTrait ya provee `uid` |
| P1-8 | Alta | 3 rutas API GET sin `_format: 'json'` — permite respuestas HTML en endpoints JSON |

---

## 2. Diagnostico

### P0-1 — CSRF en ruta DELETE

**Causa raiz:** La ruta `jaraba_andalucia_ei.api.participant.delete` (metodo DELETE) no incluia `_csrf_request_header_token: 'TRUE'` en sus requirements. Esto permite que un atacante construya una peticion DELETE desde un sitio externo (CSRF) sin necesidad de token.

**Regla violada:** CSRF-API-001 — Toda ruta API consumida via `fetch()` con metodos destructivos (POST, PUT, DELETE) DEBE exigir `_csrf_request_header_token: 'TRUE'` y el JS debe enviar `X-CSRF-Token`.

### P0-2 — SolicitudEi sin tenant_id

**Causa raiz:** Al crear la entidad `SolicitudEi` se omitio el campo `tenant_id`, presente en todas las demas entidades de negocio del SaaS. Las solicitudes creadas desde el formulario publico no quedan asociadas a ningun tenant, rompiendo el aislamiento multi-tenant.

**Regla violada:** Regla de Oro #4 — Todo Content Entity de negocio DEBE tener `tenant_id` como entity_reference a la entidad `tenant`.

### P1-3 — Emojis en email

**Causa raiz:** El template de email `confirmacion_solicitud` en `hook_mail()` usaba emojis Unicode directos: `✅` en subject, `📋` y `📌` en body. Los emojis en emails transaccionales causan problemas de rendering en clientes de correo que no soportan Unicode avanzado y violan la directriz de iconos.

### P1-4 — Literal Unicode en SCSS

**Causa raiz:** `_solicitud-form.scss` usaba `content: '⚠'` (literal Unicode U+26A0) en dos selectores CSS (`::before`). Dart Sass procesa correctamente el literal, pero la directriz del proyecto exige escapes CSS (`\26A0`) para evitar problemas de encoding en la cadena de compilacion y para consistencia con el sistema de iconos.

### P1-5 y P1-6 — Llamadas estaticas a \Drupal::service()

**Causa raiz:** Dos controladores usaban el patron anti-pattern de Drupal `\Drupal::service()` en vez de inyeccion de dependencias via constructor:

- `AndaluciaEiApiController`: `\Drupal::service('renderer')` en `addForm()` y `editForm()`
- `AndaluciaEiController`: `\Drupal::hasService()` + `\Drupal::service()` para 3 servicios opcionales (health_score, bridge, journey)

En controladores que ya extienden `ControllerBase` y tienen metodo `create()`, no hay excusa para usar el service locator estatico.

### P1-7 — Duplicacion user_id / uid

**Causa raiz:** `ProgramaParticipanteEi` usa `EntityOwnerTrait` que provee el campo `uid` (declarado como `"owner" = "uid"` en entity_keys). Adicionalmente, el entity definia un campo `user_id` (entity_reference a user) separado con form display. Esto creaba confusion sobre cual campo usar para buscar participantes por usuario, y potencialmente datos duplicados o inconsistentes entre ambos campos.

### P1-8 — _format faltante en rutas API

**Causa raiz:** Las 3 rutas API GET (`list`, `get`, `delete`) no declaraban `_format: 'json'` en requirements. Sin esta restriccion, Drupal puede servir respuestas HTML (incluyendo paginas de error con markup completo) cuando el content negotiation falla, en vez de JSON limpio.

---

## 3. Solucion Implementada

### 3.1 CSRF + _format en rutas (P0-1, P1-8)

```yaml
# jaraba_andalucia_ei.routing.yml — ruta DELETE
jaraba_andalucia_ei.api.participant.delete:
  requirements:
    _permission: 'delete programa participante ei'
    _csrf_request_header_token: 'TRUE'  # NUEVO
    _format: 'json'                      # NUEVO
    id: '\d+'

# Rutas GET — anadido _format: 'json'
# jaraba_andalucia_ei.api.participants.list
# jaraba_andalucia_ei.api.participant.get
```

### 3.2 tenant_id en SolicitudEi (P0-2)

**3 ficheros modificados:**

```php
// SolicitudEi.php — baseFieldDefinitions()
$fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Tenant'))
    ->setDescription(t('Tenant al que pertenece esta solicitud.'))
    ->setSetting('target_type', 'tenant')
    ->setRequired(FALSE)
    ->setDisplayConfigurable('view', TRUE);

// SolicitudEiPublicForm.php — submitForm()
$tenantId = NULL;
if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
    try {
        $tenant = \Drupal::service('ecosistema_jaraba_core.tenant_context')
            ->getCurrentTenant();
        $tenantId = $tenant?->id();
    }
    catch (\Exception $e) {
        // Non-critical.
    }
}
// ... tenant_id incluido en $storage->create([...])

// jaraba_andalucia_ei.install — update hook
function jaraba_andalucia_ei_update_10001(): void {
    // installFieldStorageDefinition() para tenant_id en solicitud_ei
}
```

### 3.3 Emojis eliminados de email (P1-3)

```php
// ANTES:
$message['subject'] = t('Tu solicitud Andalucía +ei ha sido recibida ✅');
// "📋 RESUMEN DE TU SOLICITUD:"
// "📌 PRÓXIMOS PASOS:"

// DESPUES:
$message['subject'] = t('Tu solicitud Andalucía +ei ha sido recibida');
// "RESUMEN DE TU SOLICITUD:"
// "PRÓXIMOS PASOS:"
```

### 3.4 SCSS Unicode escape (P1-4)

```scss
// ANTES (2 ocurrencias):
content: '⚠';
content: '⚠ ';

// DESPUES:
content: '\26A0';
content: '\26A0  ';
```

### 3.5 DI en AndaluciaEiApiController (P1-5)

```php
// Constructor con RendererInterface inyectado
public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected readonly RendererInterface $renderer,
) { ... }

// create() actualizado
public static function create(ContainerInterface $container): static {
    return new static(
        $container->get('entity_type.manager'),
        $container->get('renderer'),
    );
}

// Uso: $this->renderer->renderRoot($form) en vez de \Drupal::service('renderer')
```

### 3.6 DI servicios opcionales en AndaluciaEiController (P1-6)

```php
// Constructor con servicios opcionales nullable
public function __construct(
    protected readonly ?object $healthScoreService,
    protected readonly ?object $bridgeService,
    protected readonly ?object $journeyService,
) {}

// create() con $container->has() para servicios opcionales
public static function create(ContainerInterface $container): static {
    return new static(
        $container->has('ecosistema_jaraba_core.andalucia_ei_health_score')
            ? $container->get('ecosistema_jaraba_core.andalucia_ei_health_score') : NULL,
        $container->has('ecosistema_jaraba_core.andalucia_ei_cross_vertical_bridge')
            ? $container->get('ecosistema_jaraba_core.andalucia_ei_cross_vertical_bridge') : NULL,
        $container->has('ecosistema_jaraba_core.andalucia_ei_journey_progression')
            ? $container->get('ecosistema_jaraba_core.andalucia_ei_journey_progression') : NULL,
    );
}

// Uso: if ($this->healthScoreService) { ... } en vez de \Drupal::hasService()
```

### 3.7 Unificacion user_id → uid (P1-7)

```php
// ProgramaParticipanteEi.php — campo user_id eliminado, uid configurado
$fields['uid']
    ->setLabel(t('Usuario Drupal'))
    ->setDescription(t('Usuario vinculado al participante.'))
    ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

// Queries actualizadas:
// ANTES: loadByProperties(['user_id' => $userId])
// DESPUES: loadByProperties(['uid' => $userId])

// Update hook 10002: migra datos user_id → uid y desinstala campo redundante
```

---

## 4. Reglas Derivadas / Reforzadas

### CSRF-API-001 (reforzada)
Toda ruta API con metodos destructivos (POST, PUT, DELETE) consumida via `fetch()` DEBE incluir `_csrf_request_header_token: 'TRUE'`. El JS debe obtener el token de `/session/token` y enviarlo como header `X-CSRF-Token`. **Checklist pre-commit: verificar TODAS las rutas con methods: [DELETE] o [POST].**

### API-FORMAT-001 (nueva)
Toda ruta API que devuelve JSON DEBE declarar `_format: 'json'` en requirements. Esto garantiza que Drupal devuelve errores en JSON y no en HTML, y previene content negotiation inesperada.

### DI-CONTROLLER-001 (reforzada)
Controladores que extienden `ControllerBase` DEBEN inyectar dependencias via constructor + `create()`. NUNCA usar `\Drupal::service()` ni `\Drupal::hasService()` en metodos de accion. Para servicios opcionales de modulos que pueden no estar instalados, usar `$container->has()` en `create()` y pasar `NULL` al constructor con tipo nullable.

### ENTITY-OWNER-001 (nueva)
Entidades que usan `EntityOwnerTrait` NO DEBEN definir un campo `user_id` separado. El trait provee `uid` como campo owner. Configurar display options directamente sobre `$fields['uid']` en `baseFieldDefinitions()`. Todas las queries deben usar `uid`, no `user_id`.

---

## 5. Ficheros Modificados (9 ficheros)

| Fichero | Accion | Hallazgo |
|---------|--------|----------|
| `jaraba_andalucia_ei/jaraba_andalucia_ei.routing.yml` | CSRF + _format en 4 rutas | P0-1, P1-8 |
| `jaraba_andalucia_ei/src/Entity/SolicitudEi.php` | Campo tenant_id anadido | P0-2 |
| `jaraba_andalucia_ei/src/Form/SolicitudEiPublicForm.php` | Resolucion tenant_id en submit | P0-2 |
| `jaraba_andalucia_ei/jaraba_andalucia_ei.install` | Update hooks 10001 (tenant_id) y 10002 (user_id→uid) | P0-2, P1-7 |
| `jaraba_andalucia_ei/jaraba_andalucia_ei.module` | Emojis eliminados de email + query uid | P1-3, P1-7 |
| `jaraba_andalucia_ei/scss/_solicitud-form.scss` | Unicode escape \26A0 (2 ocurrencias) | P1-4 |
| `jaraba_andalucia_ei/src/Controller/AndaluciaEiApiController.php` | DI RendererInterface | P1-5 |
| `jaraba_andalucia_ei/src/Controller/AndaluciaEiController.php` | DI 3 servicios opcionales + query uid | P1-6, P1-7 |
| `jaraba_andalucia_ei/src/Entity/ProgramaParticipanteEi.php` | Eliminado user_id, configurado uid | P1-7 |

---

## 6. Hallazgos P2 (No corregidos — backlog)

Documentados para futura correccion:

| # | Hallazgo | Detalles |
|---|----------|----------|
| P2-1 | Links hardcoded en dashboard template | `/copilot`, `/mentoring`, `/lms` deberian usar `{{ url('route.name') }}` |
| P2-2 | Provincias inconsistentes entre entidades | SolicitudEi tiene 8 provincias, ProgramaParticipanteEi solo 4 |
| P2-3 | Llamadas estaticas en SolicitudEiPublicForm | `\Drupal::service()` para servicios opcionales en `submitForm()` |
| P2-4 | Colectivos inconsistentes entre entidades | SolicitudEi tiene 5 colectivos, ProgramaParticipanteEi solo 3 |

---

## 7. Leccion Clave

**Las auditorias de flujo completo revelan deficiencias que las revisiones de fichero individual no detectan.** Los hallazgos P0 (CSRF, tenant_id) solo son visibles cuando se traza el flujo end-to-end: desde la ruta YAML, pasando por el controlador, hasta la entidad y el formulario. Una revision de solo el controlador o solo la entidad no habria detectado que:

1. La ruta DELETE carecia de CSRF porque el JS que la consume (slide-panel.js) SI envia el token, pero la ruta no lo exigia — un atacante externo podria explotarlo
2. La entidad SolicitudEi no tenia tenant_id porque fue creada antes de que se estableciera la regla de aislamiento multi-tenant, y al no participar en queries filtradas por tenant, el gap paso desapercibido

**Patron recomendado:** Al auditar un vertical, trazar el flujo completo (ruta → controlador → formulario → entidad → email → JS) para cada accion de usuario (crear, leer, editar, eliminar). Verificar cada capa contra las directrices del proyecto.
