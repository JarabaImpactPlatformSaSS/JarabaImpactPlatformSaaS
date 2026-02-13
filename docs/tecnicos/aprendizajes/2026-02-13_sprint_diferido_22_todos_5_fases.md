# Aprendizaje: Sprint Diferido — 22/22 TODOs Backlog en 5 Fases (2026-02-13)

**Fecha:** 2026-02-13
**Categoria:** Implementacion / Deuda Tecnica / Arquitectura
**Impacto:** ALTO — 112/112 TODOs del Catalogo v1.2.0 completados (100% cobertura)

---

## Contexto

El Catalogo TODOs v1.2.0 identifico 112 TODOs unicos en la plataforma. El Sprint Inmediato resolvio 48 (commit `d2684dbd`), los Sprints S2-S7 planificaron 49 (commit `11924d5a`), quedando 22 TODOs diferidos sin implementar. Este sprint los resolvio en 5 fases ordenadas por dependencia y complejidad.

---

## Lecciones Aprendidas

### 1. Los SCSS existentes anticipan la implementacion HTML

**Situacion:** Al implementar el header SaaS en el canvas editor (FASE 2, F2-01), descubrimos que las clases CSS `.canvas-editor__saas-header`, `.canvas-editor__saas-branding`, etc. ya estaban definidas en `_canvas-editor.scss` (lineas 211-269) pero el HTML era un TODO.

**Aprendizaje:** En esta plataforma, el patron de desarrollo es SCSS-first: las clases BEM se definen antes que el markup. Esto permite verificar el diseno visual antes de la logica, pero genera un riesgo de drift si el HTML final no coincide con las clases pre-definidas.

**Regla:** Antes de crear nuevos estilos, verificar siempre si ya existen clases CSS preparadas para el componente. Usar `grep -r "nombre-componente" *.scss` antes de escribir nuevo SCSS.

---

### 2. Los componentes reutilizables necesitan overrides contextuales

**Situacion:** El selector i18n (`i18n-selector.html.twig`) usa estilos dark-theme por defecto (texto blanco, fondo oscuro). Al incluirlo en la toolbar del canvas editor (fondo claro), los colores eran ilegibles.

**Aprendizaje:** Los componentes compartidos necesitan overrides contextuales cuando se usan en contextos visuales diferentes. El patron correcto es scope CSS: `.contexto-padre .componente { /* overrides */ }`, nunca modificar los estilos base del componente.

**Patron aplicado:** `.canvas-editor__toolbar-right .i18n-selector { background: var(--ej-bg-body); color: var(--ej-color-body); }` con dropdown alineado a la derecha.

---

### 3. JSON Schema como fuente de verdad para formularios dinamicos

**Situacion:** Los templates de pagina tienen un campo `fields_schema` que usa JSON Schema con extensiones `ui:widget`. Necesitabamos generar formularios dinamicos en el section editor para editar campos de cada seccion.

**Aprendizaje:** JSON Schema es un estandar excelente para definir formularios dinamicos porque:
- Define tipos, restricciones y valores por defecto en un formato estandar.
- Se puede extender con `ui:widget` para controlar la representacion visual.
- Alpine.js puede iterar sobre `properties` con `x-for` y renderizar widgets condicionalmente.

**Patron implementado:**
```
JSON Schema (PHP) → API endpoint → Alpine.js getEditableFields() → inferWidget() → x-for rendering
```

Widgets soportados: text, textarea, url, email, number, slider, checkbox, select, color, image-upload. Fallback: si no hay schema, se muestra textarea JSON crudo.

---

### 4. Los closures no son serializables: usar service IDs para re-ejecucion

**Situacion:** El `AgentAutonomyService` almacena acciones pendientes de aprobacion en el State API de Drupal. Las acciones incluyen un `executor_class` pero no el callable directo, porque los closures no se pueden serializar.

**Aprendizaje:** Para re-ejecutar logica almacenada, el patron correcto en Drupal es:
1. Almacenar la clase del ejecutor (`executor_class`).
2. Cargar la entidad del agente y obtener su `service_id` via `getServiceId()`.
3. Obtener el servicio del contenedor y verificar `instanceof executor_class`.
4. Invocar el servicio con el contexto de la accion.

**Antipatron:** Nunca intentar serializar closures, callables o instancias de servicio en State/Cache.

---

### 5. Kernel tests con Group/Domain requieren BrowserTestBase

**Situacion:** `TenantProvisioningTest` (KernelTestBase) estaba permanentemente `markTestSkipped()` porque los modulos `group` y `domain` requieren un contenedor DI completo con todas las dependencias de contrib.

**Aprendizaje:** En Drupal 11, los modulos contrib complejos (Group, Domain, Commerce) no pueden bootstrappearse en KernelTestBase porque sus service providers, event subscribers y entity handlers dependen de servicios que no se registran parcialmente. La solucion es migrar a `BrowserTestBase` (o `Functional`) que provee el contenedor completo.

**Regla KERNEL-001 reforzada:** Si un test requiere modulos contrib con service providers complejos, usar `BrowserTestBase` o `FunctionalJavascriptTestBase`, no `KernelTestBase`.

---

### 6. EventDispatcher para webhooks desacopla emisor de consumidores

**Situacion:** El `WebhookReceiverController` recibia webhooks pero no tenia mecanismo para notificar a otros modulos. Los modulos que necesitaban reaccionar a webhooks tenian que implementar sus propias rutas.

**Aprendizaje:** El patron Symfony EventDispatcher es ideal para webhooks entrantes:
- El controlador valida, parsea y despacha un `WebhookReceivedEvent`.
- Los modulos subscriptores filtran por `$event->provider` y `$event->eventType`.
- El controlador no necesita conocer los consumidores.
- Nuevos consumidores se registran con un tag `event_subscriber` en `services.yml`.

**Patron aplicado:** `WebhookReceivedEvent` con propiedades readonly (PHP 8.4 constructor promotion): `webhookId`, `provider`, `eventType`, `payload`, `tenantId`.

---

### 7. Verificacion de tokens requiere endpoints especificos por plataforma

**Situacion:** El `TokenVerificationService` solo comprobaba la fecha de expiracion pero no verificaba si el token seguia siendo valido en la plataforma remota. Tokens revocados o con permisos cambiados pasaban como validos.

**Aprendizaje:** Cada plataforma tiene un endpoint diferente para verificar tokens:

| Plataforma | Endpoint | Metodo |
|------------|----------|--------|
| Meta | `GET /v18.0/me` | Token introspection |
| Google | `POST /debug/mp/collect` | Validation messages |
| LinkedIn | `GET /v2/me` | Perfil del titular |
| TikTok | `GET /open_api/v1.3/pixel/list/` | Verifica acceso |

**Regla:** Tolerancia a fallos de red — si la verificacion falla por error de red (timeout, DNS), NO invalidar el token. Solo invalidar si la API responde explicitamente con 401/403.

---

### 8. Stock en Commerce requiere estrategia de fallback escalonada

**Situacion:** El stock estaba hardcodeado como `TRUE` en el Schema.org de productos. No existia el modulo `commerce_stock` ni campos de stock en las variaciones.

**Aprendizaje:** La deteccion de stock debe ser progresiva con 4 niveles de fallback:
1. **commerce_stock module** (StockServiceManager) — si esta instalado.
2. **field_stock_quantity en variaciones** — suma de cantidades.
3. **field_stock_quantity en producto** — cantidad directa.
4. **Fallback** — si esta publicado, asumir disponible.

**Patron:** `_jaraba_commerce_resolve_stock()` implementa la cascada completa. Si alguna variacion tiene el campo pero todas con stock 0, retorna `FALSE` (agotado). Si ninguna tiene el campo, fallback al estado de publicacion.

---

### 9. Configuracion inyectable via Drupal Config para URLs externas

**Situacion:** Las URLs de Wikidata y Crunchbase en Schema.org Organization estaban como TODO en el codigo. Hardcodearlas seria fragil; necesitaban ser configurables.

**Aprendizaje:** Para datos que cambian infrecuentemente pero deben ser editables sin deploy, usar `\Drupal::config('modulo.settings')->get('clave')` con un YAML en `config/install/`. Esto permite:
- Edicion via `drush cset` sin tocar codigo.
- Exportacion/importacion con `config:export/import`.
- Futuro formulario de admin sin cambiar la logica.

**Patron:** `array_filter()` para eliminar valores vacios del array `sameAs`, evitando URLs null en el JSON-LD.

---

### 10. El slide-panel es el patron correcto para resultados de auditoria

**Situacion:** El validador de accesibilidad volcaba resultados a `console.table()`, invisible para usuarios no-desarrolladores.

**Aprendizaje:** Para resultados de auditoria/validacion inline, el slide-panel lateral es superior a:
- **console.table** — invisible para no-desarrolladores.
- **alert/modal** — bloquea la interaccion.
- **toast** — demasiado efimero para datos complejos.

El slide-panel permite: ver resultados sin abandonar el editor, cerrar con toggle, agrupar por severidad, mostrar selectores CSS de elementos afectados.

---

## Archivos Clave Modificados/Creados

### FASE 1
| Archivo | Accion |
|---------|--------|
| `jaraba_lms/jaraba_lms.module` | Editado: hook_preprocess + ratings |
| `jaraba_page_builder/js/canvas-editor.js` | Editado: save/publish |
| `ecosistema_jaraba_theme/scss/_pricing.scss` | Editado: tabla comparativa |

### FASE 2
| Archivo | Accion |
|---------|--------|
| `jaraba_page_builder/templates/canvas-editor.html.twig` | Editado x2: header SaaS + i18n selector |
| `jaraba_page_builder/scss/_canvas-editor.scss` | Editado x3: i18n overrides + dynamic fields + a11y panel |
| `jaraba_page_builder/templates/section-editor.html.twig` | Editado: dynamic fields Alpine.js |
| `jaraba_page_builder/js/section-editor.js` | Editado: 4 metodos nuevos |
| `jaraba_page_builder/js/accessibility-validator.js` | Editado: renderA11yPanel() |
| `jaraba_page_builder/src/Controller/SectionApiController.php` | Editado: fields_schema en response |

### FASE 3
| Archivo | Accion |
|---------|--------|
| `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | Editado: 4 metodos CRUD |
| `jaraba_tenant_knowledge/jaraba_tenant_knowledge.module` | Editado: 3 hook_theme() |
| `jaraba_tenant_knowledge/templates/knowledge-faqs.html.twig` | Creado |
| `jaraba_tenant_knowledge/templates/knowledge-policies.html.twig` | Creado |
| `jaraba_tenant_knowledge/templates/knowledge-documents.html.twig` | Creado |

### FASE 4
| Archivo | Accion |
|---------|--------|
| `ecosistema_jaraba_core/src/Service/AgentAutonomyService.php` | Editado: re-execution |
| `ecosistema_jaraba_core/tests/src/Functional/TenantProvisioningFunctionalTest.php` | Creado |
| `ecosistema_jaraba_core/tests/src/Kernel/TenantProvisioningTest.php` | Editado: skip msg |
| `jaraba_integrations/src/Event/WebhookEvents.php` | Creado |
| `jaraba_integrations/src/Event/WebhookReceivedEvent.php` | Creado |
| `jaraba_integrations/src/Controller/WebhookReceiverController.php` | Editado: DI + dispatch |
| `jaraba_lms/src/Entity/Course.php` | Editado: field_category |

### FASE 5
| Archivo | Accion |
|---------|--------|
| `jaraba_pixels/src/Service/TokenVerificationService.php` | Editado: verificacion V2.1 |
| `jaraba_pixels/src/Service/PixelDispatcherService.php` | Editado: dispatchFromData() |
| `jaraba_pixels/src/Service/BatchProcessorService.php` | Editado: dispatch real |
| `jaraba_pixels/jaraba_pixels.services.yml` | Editado: +@http_client |
| `jaraba_commerce/jaraba_commerce.module` | Editado: stock dinamico |
| `jaraba_geo/jaraba_geo.module` | Editado: sameAs configurable |
| `jaraba_geo/config/install/jaraba_geo.settings.yml` | Creado |

---

## Metricas del Sprint

| Metrica | Valor |
|---------|-------|
| TODOs resueltos | 22/22 (100%) |
| Fases completadas | 5/5 |
| Archivos editados | ~25 |
| Archivos creados | ~8 |
| Modulos tocados | 8 (jaraba_lms, jaraba_page_builder, jaraba_tenant_knowledge, ecosistema_jaraba_core, jaraba_integrations, jaraba_pixels, jaraba_commerce, jaraba_geo) |
| Directrices aplicadas | TENANT-001, DRUPAL11-001, PHP-STRICT, BEM, MODAL-CRUD, ALPINE-JS |
| Cobertura catalogo v1.2.0 | 112/112 (100%) |

---

## Relacion con Documentos

| Documento | Ubicacion |
|-----------|-----------|
| Plan Implementacion Sprint Diferido v2.0 | `docs/implementacion/20260213-Plan_Implementacion_Sprint_Diferido_v1.md` |
| Directrices v21.0.0 | `docs/00_DIRECTRICES_PROYECTO.md` |
| Arquitectura v20.0.0 | `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` |
| Indice General v29.0.0 | `docs/00_INDICE_GENERAL.md` |
