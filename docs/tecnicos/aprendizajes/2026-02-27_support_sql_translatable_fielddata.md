# Aprendizaje #148: Consultas SQL directas contra entidades traducibles — 3 capas de bugs

**Fecha:** 2026-02-27
**Modulo:** `jaraba_support`
**Severidad:** CRITICA (crash total en runtime)
**Verificado:** Runtime real con `lando drush eval`

---

## Contexto

El modulo `jaraba_support` tenia 19 consultas SQL directas (`$this->database->select()`) que referenciaban la tabla base `support_ticket`. Pero la entidad `SupportTicket` tiene `translatable = TRUE`, lo que significa que Drupal almacena los campos en `support_ticket_field_data`, no en la tabla base.

## Las 3 Capas de Bugs

### Capa 1: Tabla incorrecta (SQL Column not found)

19 queries referenciaban `support_ticket` en vez de `support_ticket_field_data`. Campos como `status`, `assignee_uid`, `ticket_number`, `priority`, `satisfaction_rating`, `resolved_at` no existen en la tabla base — solo `id`, `uuid`, `langcode`.

**Impacto:** Crear un ticket → crash total. Dashboard → KPIs vacios. Health Score → siempre 100 (fallback on exception).

**Fix:** Cambiar tabla en queries directas. Convertir las 2 queries de `SupportTicket::preSave()`/`generateTicketNumber()` a entity queries.

### Capa 2: Experiencia de usuario rota (template + SCSS)

- Template `_support-status-badge.html.twig` tenia 4 estados fantasma (`in_progress`, `pending_third_party`, `on_hold`, `cancelled`) y le faltaban 2 estados reales (`pending_internal`, `reopened`).
- SCSS `_status-badges.scss` no tenia regla CSS para `reopened`.
- El usuario veia badges vacios o sin color para tickets en estados reales.

**Fix:** Alinear template con los 10 estados del entity definition. Anadir regla SCSS para `reopened`. Recompilar CSS.

### Capa 3: Fatal errors y aritmetica incorrecta en runtime

**Fatal errors por cadenas de metodos rotas:**
- `addExpression()` retorna un string (alias), NO `$this`. La cadena `->addExpression('AVG(...)', 'avg')->execute()` llama `execute()` sobre un string → fatal.
- `join()` retorna un string (alias), NO `$this`. La cadena `->join(...)->condition(...)` llama `condition()` sobre un string → fatal.
- 3 cadenas rotas en `SupportHealthScoreService` (CSAT, escalation, resolution).

**Tipos incompatibles datetime vs timestamp:**
- `resolved_at` y `first_responded_at` son campos `datetime` → almacenan VARCHAR `'2026-02-27T10:30:00'`.
- `created` es campo `created` → almacena INT Unix timestamp.
- `AVG(t.resolved_at - t.created)` = `'2026-02-27...' - 1740614400` = `2026 - 1740614400` = basura.
- `t.resolved_at >= strtotime('today midnight')` = VARCHAR vs INT = siempre FALSE en MySQL.

**Fix:**
- Romper cadenas en sentencias separadas: `$query->addExpression(...)` sin encadenar.
- `UNIX_TIMESTAMP(REPLACE(t.resolved_at, 'T', ' '))` para convertir datetime a Unix.
- `isNotNull('t.resolved_at')` en vez de `condition('t.resolved_at', 0, '>')`.
- ISO 8601 para comparaciones: `(new \DateTime(...))->format('Y-m-d\TH:i:s')`.

## Estados reales de SupportTicket (10)

| Estado | Descripcion |
|--------|-------------|
| `new` | Ticket recien creado |
| `ai_handling` | IA procesando |
| `open` | Asignado a agente humano |
| `pending_customer` | Esperando respuesta del cliente |
| `pending_internal` | Esperando accion interna |
| `escalated` | Escalado a nivel superior |
| `resolved` | Resuelto |
| `closed` | Cerrado definitivamente |
| `reopened` | Reabierto tras resolucion |
| `merged` | Fusionado en otro ticket |

**NOTA:** La documentacion original decia "6 estados: open→in_progress→waiting_customer→resolved→closed + escalated" pero el codigo real tiene 10. `in_progress` y `waiting_customer` nunca existieron como allowed_values.

## Reglas derivadas

- **TRANSLATABLE-FIELDDATA-001 (P0):** Entidades con `translatable = TRUE` → queries directas contra `{type}_field_data`.
- **QUERY-CHAIN-001 (P0):** `addExpression()` y `join()` retornan strings → no encadenar.
- **DATETIME-ARITHMETIC-001 (P1):** datetime (VARCHAR) vs created (INT) → `UNIX_TIMESTAMP(REPLACE(...))`.

## Regla de oro

**#79:** Tests con mocks que retornan `willReturnSelf()` para metodos que realmente retornan strings (addExpression, join) crean falsa confianza. Agregar tests de contrato que verifiquen el nombre de tabla y no permitan la regresion.

## Archivos modificados (8)

1. `jaraba_support/src/Entity/SupportTicket.php` — 2 queries → entity queries
2. `jaraba_support/src/Service/SupportAnalyticsService.php` — 7 tabla + 2 estados + 2 datetime
3. `jaraba_support/src/Service/SupportHealthScoreService.php` — 7 tabla + 1 join + 3 cadenas rotas + 1 datetime
4. `jaraba_support/src/Service/TicketStreamService.php` — 1 tabla
5. `jaraba_support/src/Service/SlaEngineService.php` — 1 estado fantasma
6. `jaraba_support/templates/partials/_support-status-badge.html.twig` — 10 estados reales
7. `jaraba_support/scss/_status-badges.scss` — +reopened CSS rule
8. `jaraba_support/css/jaraba-support.css` — recompilado

## Tests anadidos (4)

1. `SupportAnalyticsServiceTest::testQueriesUseFieldDataTable()` — verifica tabla correcta
2. `SupportHealthScoreServiceTest::testQueriesUseFieldDataTable()` — verifica tabla + join
3. `SupportHealthScoreServiceTest::testReturns100OnException()` — fallback behavior
4. `SupportHealthScoreServiceTest::testScoreIsClamped()` — score 0-100

## Verificacion runtime (lando drush eval)

| Test | Resultado |
|------|-----------|
| `generateTicketNumber()` | `JRB-202602-0001`, `0002` |
| `getOverviewStats()` | 2 tickets, 1 resolved, CSAT=4.0 |
| `tickets_resolved_today` | 1 (datetime comparison OK) |
| `avg_response_time` | 31min (UNIX_TIMESTAMP aritmetica OK) |
| `csat_score` | 4.0 |
| Health Score | 95/100 (calculo real) |
| SSE stream query | Sin errores SQL |

**Total:** 26 tests / 134 assertions / 0 errores.
