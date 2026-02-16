# Flujo de Trabajo del Asistente IA (Claude)

**Fecha de creacion:** 2026-02-16
**Version:** 1.0.0

---

## 1. Inicio de Sesion

Al comenzar o reanudar una conversacion, leer en este orden:

1. **DIRECTRICES** (`docs/00_DIRECTRICES_PROYECTO.md`) — Reglas, convenciones, principios de desarrollo
2. **ARQUITECTURA** (`docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md`) — Modulos, stack, modelo de datos
3. **INDICE** (`docs/00_INDICE_GENERAL.md`) — Estado actual, ultimos cambios, aprendizajes recientes

Esto garantiza contexto completo antes de cualquier implementacion.

---

## 2. Antes de Implementar

- **Leer ficheros de referencia:** Antes de crear o modificar un modulo, revisar modulos existentes que usen el mismo patron (ej: jaraba_verifactu como patron canonico de zero-region)
- **Plan mode para tareas complejas:** Usar plan mode cuando la tarea requiere multiples ficheros, decisiones arquitectonicas, o tiene ambiguedad
- **Verificar aprendizajes previos:** Consultar `docs/tecnicos/aprendizajes/` para evitar repetir errores documentados

---

## 3. Durante la Implementacion

Respetar las directrices del proyecto, incluyendo:

- **i18n:** `TranslatableMarkup` / `$this->t()` para strings visibles al usuario
- **SCSS:** BEM, `@use` (no `@import`), `color-mix()` (no `rgba()`), Federated Design Tokens `var(--ej-*)`
- **Zero-region:** Variables via `hook_preprocess_page()`, NO via controller render array (ZERO-REGION-001/002/003)
- **Hooks:** Hooks nativos PHP en `.module` (NO ECA YAML para logica compleja)
- **Content Entities:** Para todo dato gestionable (Field UI + Views + Access Control)
- **API REST:** Envelope `{success, data, error, message}`, prefijo `/api/v1/`
- **Seguridad:** `_permission` en rutas sensibles, HMAC webhooks, sanitizacion antes de `|raw`
- **Tests:** PHPUnit por modulo (Unit + Kernel + Functional segun aplique)
- **SCSS compilacion:** Via Docker NVM (`/user/.nvm/versions/node/v20.20.0/bin`)
- **declare(strict_types=1):** Obligatorio en todo fichero PHP nuevo
- **tenant_id:** Siempre `entity_reference` a group, NUNCA integer
- **DB indexes:** Obligatorios en `tenant_id` + campos frecuentes
- **Iconos:** SVG duotone via sistema centralizado, no emojis en codigo
- **Modales CRUD:** `data-dialog-type="modal"` con `drupal.dialog.ajax`

---

## 4. Despues de Implementar

Actualizar los 3 documentos maestros + crear aprendizaje:

1. **DIRECTRICES:** Incrementar version en header + añadir entrada al changelog (seccion 14) + nuevas reglas si aplica (seccion 5.8.x)
2. **ARQUITECTURA:** Incrementar version en header + actualizar modulos en seccion 7.1 si se añadieron modulos + changelog al final
3. **INDICE:** Incrementar version en header + nuevo blockquote al inicio (debajo del header) + entrada en tabla Registro de Cambios
4. **Aprendizaje:** Crear fichero en `docs/tecnicos/aprendizajes/YYYY-MM-DD_nombre_descriptivo.md` con formato estandar (tabla metadata, Patron Principal, Aprendizajes Clave con Situacion/Aprendizaje/Regla)

---

## 5. Reglas de Oro

1. **No hardcodear:** Configuracion via Config Entities o State API, nunca valores en codigo
2. **Content Entities para todo:** Datos gestionables siempre como Content Entity con Field UI + Views
3. **`declare(strict_types=1)`:** En todo fichero PHP nuevo
4. **Tenant isolation:** `tenant_id` como entity_reference en toda entidad, filtrado obligatorio
5. **Rate limiting:** En toda operacion costosa (exports, AI calls, bulk operations)
6. **Patron zero-region:** 3 hooks obligatorios (hook_theme + hook_theme_suggestions_page_alter + hook_preprocess_page)
7. **Documentar siempre:** Toda sesion con cambios significativos genera actualizacion documental
