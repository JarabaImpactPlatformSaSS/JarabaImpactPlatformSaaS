# Aprendizaje #120: Page Builder PathProcessor + Meta-Sitio jarabaimpact.com

**Fecha:** 2026-02-24
**Módulo:** `jaraba_page_builder`
**Contexto:** Auditoría y construcción del meta-sitio institucional `jarabaimpact.com` usando exclusivamente el Page Builder.

---

## Problema

Las entidades `PageContent` tienen un campo `path_alias` que almacena slugs amigables (ej. `/jarabaimpact`, `/plataforma`).
Sin embargo, Drupal no registra estos alias como rutas navegables porque no pasan por el sistema de `path_alias` del core.
Resultado: los slugs devolvían 404 a pesar de estar correctamente almacenados en `page_content_field_data`.

## Solución: PathProcessorPageContent

Se creó un `InboundPathProcessorInterface` (`PathProcessorPageContent.php`) que intercepta requests entrantes,
busca coincidencia de `path_alias` en la tabla `page_content_field_data`, y reescribe la URL a `/page/{id}`.

### Decisiones de Diseño

1. **Prioridad 200** en el servicio (vs 100 de core `path_alias`): Garantiza que los slugs de `page_content` se resuelvan antes que los alias estándar de Drupal.
2. **Sin filtro por `status`**: Se eliminó la condición `->condition('status', 1)` para permitir que admins accedan a borradores por URL amigable. El control de acceso lo maneja `PageContentAccessControlHandler`.
3. **Skip list de prefijos**: Prefijos como `/api/`, `/admin/`, `/user/`, `/media/`, `/session/` se excluyen para rendimiento.
4. **Static cache**: Las resoluciones se cachean por `$path` dentro del request para evitar queries duplicadas.

### Archivos Creados/Modificados

- **[NEW]** `src/PathProcessor/PathProcessorPageContent.php` — PathProcessor con `processInbound()`
- **[MODIFY]** `jaraba_page_builder.services.yml` — Registro del servicio con tag `path_processor_inbound` y prioridad 200

### Regla Derivada

**PATH-ALIAS-PROCESSOR-001 (P0):** Cuando una entidad ContentEntity usa un campo `path_alias` propio (no gestionado por el módulo `path_alias` del core), se DEBE implementar un `InboundPathProcessorInterface` registrado como servicio con prioridad superior a 100 (core alias priority). El procesador DEBE:
1. Excluir prefijos de sistema (/api/, /admin/, /user/, etc.) para rendimiento
2. No filtrar por status — delegar en el AccessControlHandler de la entidad
3. Usar static cache por path dentro del request
4. Retornar la ruta canónica de la entidad (ej. `/page/{id}`)

### Patrón Replicable

Si se crea otro tipo de entidad con slugs custom:
1. Añadir campo `path_alias` a la entidad (string, nullable)
2. Crear `src/PathProcessor/PathProcessorMiEntidad.php` implementando `InboundPathProcessorInterface`
3. Registrar en `services.yml` con `path_processor_inbound` y prioridad 200+
4. Asegurar que la entidad tiene `AccessControlHandler` para gestionar permisos
5. Implementar skip list de prefijos de sistema

### Meta-Sitio: Lecciones Aprendidas

- Las 7 páginas del meta-sitio se gestionan como entidades `PageContent` con contenido GrapesJS.
- La actualización de contenido vía JavaScript en el editor GrapesJS usa `editor.getComponents()` y `editor.store()`.
- Los títulos y aliases se actualizan vía la API REST: `PATCH /api/v1/pages/{id}/config`.
- La publicación se gestiona vía: `POST /api/v1/pages/{id}/publish`.
- Los cambios de contenido en GrapesJS requieren recargar el editor para ver el HTML actualizado en la API pública.
