# Aprendizaje #90 — Implementacion FASE A1 `jaraba_legal_cases`

| Campo | Valor |
|-------|-------|
| **Fecha** | 2026-02-16 |
| **Modulo** | `jaraba_legal_cases` (JarabaLex FASE A1) |
| **Contexto** | Implementacion del modulo pivote del vertical JarabaLex Legal Practice Platform — 4 Content Entities, 4 Services, 3 Controllers (11 API endpoints), 3 Forms, 4 AccessControlHandlers, 2 ListBuilders, 6 SCSS partials, 3 Twig partials, 2 zero-region page templates, 4 SVG icons, JS behavior. Modulo habilitado en produccion con `drush en` exitoso. |
| **Resultado** | 47 ficheros creados (excl. node_modules), 22 PHP files pasan lint, 4 entidades registradas, 9 permisos, 12 terminos taxonomia `legal_area`, SCSS compilado, theme modificado |
| **Aprendizaje anterior** | #89 — Patron elevacion vertical 14 fases |

---

## Patron Principal

**Implementacion modulo Drupal 11 completo desde plan de implementacion detallado** — El plan de 10 pasos con 24 directrices y ficheros de referencia explícitos permite implementacion secuencial sin ambiguedad. El patron "leer sibling primero, copiar estructura, adaptar contenido" es altamente eficiente para modulos nuevos dentro de un ecosistema maduro.

---

## Aprendizajes Clave

### 1. Patron preSave() para numeros auto-generados

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | ClientCase necesita `case_number` auto-generado como `EXP-YYYY-NNNN`, y ClientInquiry como `CON-YYYY-NNNN` |
| **Aprendizaje** | Implementar en `preSave()` del entity: query `STARTS_WITH` al prefijo del ano actual, sort DESC, range(0,1), extraer ultimo numero con regex, incrementar. Guard: solo si `isNew()` y campo vacio. No usar `hook_entity_presave()` para mantener encapsulacion |
| **Regla** | **ENTITY-AUTONUMBER-001**: Numeros auto-generados de entidades SIEMPRE en `preSave()` del entity class, nunca en hooks externos. Usar query `STARTS_WITH` + regex para secuencia, con `accessCheck(FALSE)` |

### 2. Patron append-only para CaseActivity

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | CaseActivity es un registro inmutable de actividad — no debe permitir edicion ni eliminacion |
| **Aprendizaje** | Definir solo el form handler `default` (sin `edit`). En el AccessControlHandler, retornar `AccessResult::forbidden()` para operaciones `update` y `delete` excepto admin. No incluir `EntityChangedInterface` ni `EntityChangedTrait` para evitar campo `changed` |
| **Regla** | **ENTITY-APPEND-001**: Entidades append-only: solo form handler `default`, sin edit/delete links, AccessResult::forbidden() en update/delete, sin EntityChangedInterface. Para logging de alto volumen, considerar tablas custom (MILESTONE-001) |

### 3. Patron zero-region con 2 templates + 2 suggestions especificas

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | El dashboard de expedientes y el detalle necesitan templates zero-region independientes, no el generico `page__dashboard` |
| **Aprendizaje** | Para rutas que necesitan template propio: (1) Anadir prefijo del modulo al array `$ecosistema_prefixes` en `hook_theme_suggestions_page_alter()` (para el fallback `page__dashboard`), (2) Anadir suggestions especificas con `$route_str === 'modulo.ruta'` para cada template custom. Esto permite que las rutas especificas usen su template y las demas rutas del modulo caigan al dashboard generico |
| **Regla** | **THEME-SUGGEST-001**: Al crear zero-region templates especificas para un modulo: (1) Registrar prefijo en `$ecosistema_prefixes` (baja prioridad, fallback), (2) Anadir `if ($route_str === ...)` por cada template custom (alta prioridad, especifico). Drupal evalua sugerencias de ultima a primera |

### 4. Compilacion SCSS: host vs Docker

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | El comando Docker `docker exec jarabasaas_appserver_1 bash -c "npx sass ..."` falla porque `npx` no esta en el PATH del container appserver |
| **Aprendizaje** | Cuando Docker no tiene Node/npx, compilar desde el host con `npm install --save-dev sass && npx sass scss/main.scss css/output.css --style=compressed --no-source-map`. El host WSL2 tiene Node disponible via NVM. Alternativamente, usar `lando ssh` si Lando tiene tooling Node configurado |
| **Regla** | **SCSS-BUILD-001**: Preferir compilacion SCSS desde el host (WSL2/NVM) cuando Docker no tiene Node. Fallback: `lando ssh -c "npx sass ..."` si tooling configurado. El resultado compilado debe comitearse en `css/` |

### 5. install hook: crear vocabulario + terminos idempotente

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | `hook_install()` debe crear el vocabulario `legal_area` con 12 terminos si no existe |
| **Aprendizaje** | Patron seguro: (1) Verificar si vocabulario existe con `$vocab_storage->load($vid)`, (2) Si no existe, crearlo con `$vocab_storage->create([...])->save()`, (3) Verificar si ya hay terminos con query `->condition('vid', $vid)->range(0,1)->accessCheck(FALSE)`, (4) Solo crear si no hay terminos. Esto hace el install idempotente si se ejecuta multiples veces |
| **Regla** | **INSTALL-TAXONOMY-001**: `hook_install()` que crea vocabularios y terminos DEBE ser idempotente: verificar existencia del vocabulario, crearlo si falta, verificar terminos existentes antes de crear nuevos |

### 6. 4 entidades con FK cruzadas via entity_reference

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | `CaseActivity.case_id` → `client_case`, `InquiryTriage.inquiry_id` → `client_inquiry`, `ClientInquiry.converted_to_case_id` → integer (FK futuro) |
| **Aprendizaje** | Para FKs entre entidades del mismo modulo, usar `entity_reference` con `target_type`. Para FKs a entidades que pueden no existir aun o que son opcionales, usar `integer` (como `converted_to_case_id`). Patron de LegalCitation.php: `BaseFieldDefinition::create('integer')` para `expediente_id` |
| **Regla** | **ENTITY-FK-001**: FKs a entidades del mismo modulo: `entity_reference` con `target_type`. FKs a entidades que pueden no existir o son opcionales cross-module: `integer` con constraint manual. Nunca integer para `tenant_id` (siempre `entity_reference` a `taxonomy_term`) |

---

## Metricas

| Metrica | Valor |
|---------|-------|
| Ficheros creados | 47 (excl. node_modules) |
| Ficheros PHP | 22 (todos pasan `php -l`) |
| Content Entities | 4 (ClientCase, CaseActivity, ClientInquiry, InquiryTriage) |
| Total campos definidos | ~63 (22 + 11 + 17 + 13) |
| Services | 4 (CaseManager, ActivityLogger, CaseTriage, InquiryManager) |
| Controllers | 3 (Dashboard, Detail, API) |
| API REST endpoints | 11 |
| Permisos | 9 |
| Templates zero-region | 2 (page--legal-cases, page--legal-case-detail) |
| Parciales Twig | 3 (_case-card, _activity-entry, _inquiry-card) |
| SCSS partials | 6 (main, variables, dashboard, detail, case-card, inquiry-card) |
| SVG icons | 4 (briefcase, briefcase-duotone, gavel, gavel-duotone) |
| Taxonomia terms creados | 12 (areas juridicas) |
| Theme edits | 2 (prefijo en $ecosistema_prefixes + 2 template suggestions) |

---

## Reglas Nuevas Resumen

| ID | Nombre | Descripcion corta |
|----|--------|-------------------|
| ENTITY-AUTONUMBER-001 | Numeros auto-generados en preSave() | Query STARTS_WITH + regex + accessCheck(FALSE) |
| ENTITY-APPEND-001 | Entidades append-only | Solo form default, forbidden update/delete, sin EntityChangedInterface |
| THEME-SUGGEST-001 | Template suggestions especificas | Prefijo en array catch-all + if route especifico |
| SCSS-BUILD-001 | Compilacion SCSS host vs Docker | Preferir host WSL2/NVM cuando Docker sin Node |
| INSTALL-TAXONOMY-001 | Install idempotente taxonomias | Verificar vocab + verificar terms antes de crear |
| ENTITY-FK-001 | FKs entity_reference vs integer | entity_reference mismo modulo, integer cross-module opcional |
