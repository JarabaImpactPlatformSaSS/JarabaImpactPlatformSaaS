# Aprendizaje #145 — jaraba_support: Sistema de Soporte Omnicanal Clase Mundial

**Fecha:** 2026-02-27
**Categoria:** Soporte al Cliente / SLA / AI / SSE
**Impacto:** Alto

## Contexto

Implementacion completa del modulo `jaraba_support` en 10 fases, desde entidades y servicios base hasta AI agent Gen 2, SLA engine con business hours, attachments con ClamAV, SSE streaming y health score compuesto.

## Patrones Clave

### 1. State Machine con Transiciones Validadas

```php
private const VALID_TRANSITIONS = [
  'open' => ['in_progress', 'escalated', 'closed'],
  'in_progress' => ['waiting_customer', 'resolved', 'escalated', 'closed'],
  'waiting_customer' => ['in_progress', 'resolved', 'closed'],
  'resolved' => ['open', 'closed'],
  'closed' => ['open'],
  'escalated' => ['in_progress', 'closed'],
];
```

- Mapa estatico de transiciones validas en TicketService
- `transitionStatus()` verifica antes de ejecutar
- `first_responded_at` se trackea automaticamente en `addMessage()` cuando un agente responde por primera vez

### 2. SLA Engine con Business Hours

- **SlaPolicy ConfigEntity** con ID `{plan_tier}_{priority}` como lookup key directa
- `attachSlaToTicket()`: resuelve policy → calcula deadline via BusinessHoursService
- `BusinessHoursService::addBusinessHours()`: camina minuto a minuto respetando schedule/timezone/holidays con max 365 iteraciones de seguridad
- `processSlaCron()`: detecta tickets breached/warned y notifica
- Pause/Resume: extiende deadline proporcionalmente al tiempo pausado

### 3. Attachment Security Multi-Capa

1. **Upload**: MIME whitelist (13 tipos), extension whitelist (14), double-extension check contra 16 extensiones peligrosas, max size 10MB
2. **ClamAV Scan**: nSCAN via unix socket con 4-layer heuristic fallback:
   - Dangerous extension check
   - Double-extension detection
   - Suspicious content en primeros 8KB
   - MIME vs magic-bytes mismatch
3. **Signed URLs**: HMAC SHA-256 con base64 JSON token, expiracion 1h, 3-tier authorization (admin/reporter/assignee), BinaryFileResponse con security headers

### 4. Ticket Routing Multi-Factor

```
Score = skills_match(+50) + vertical_match(+30) + workload_inverse(+20) + experience_bonus(+15)
```

- Skills match: +50 si el agente tiene la skill requerida
- Vertical match: +30 si el agente maneja la vertical del ticket
- Workload: inversamente proporcional a tickets abiertos asignados
- Experience: +15 bonus para prioridad critical/high basado en tickets resueltos

### 5. SSE via Database Event Queue

- `TicketStreamService` persiste eventos en tabla `support_ticket_events`
- `getEventsForAgent()`: filtra por tickets asignados + watched con dedup
- Collision detection: `support_ticket_viewers` con heartbeat `merge()` + 5min staleness
- `SupportStreamController`: SSE endpoint con `StreamedResponse`

### 6. Health Score Compuesto

5 componentes con pesos:
| Componente | Peso |
|---|---|
| volume_trend | 15 |
| sla_compliance | 30 |
| csat_average | 25 |
| escalation_rate | 15 |
| resolution_speed | 15 |

Churn alert si score < 40.

### 7. Bug Fix: Parameter Order Mismatch

**Problema**: SlaEngineService llamaba `addBusinessHours($now, $hours, $scheduleId)` pero BusinessHoursService espera `addBusinessHours($scheduleId, $from, $hours)`.

**Leccion**: Regla SERVICE-CALL-CONTRACT-001 — siempre verificar firma exacta de metodos inyectados. `hasService()` + try-catch NO protege contra TypeError por firma incorrecta.

### 8. Bug Fix: SupportStreamController Method Inexistente

**Problema**: Llamaba `$this->streamService->getEventsForTenant()` que no existe.
**Solucion**: Usar `getEventsForAgent()` para ambos agentes y clientes.

## Reglas Derivadas

- **STATE-MACHINE-SUPPORT-001**: Transitions validadas via mapa estatico, first_responded_at auto-tracking
- **SLA-BUSINESS-HOURS-001**: Policy lookup por {plan_tier}_{priority}, business hours walking con timezone
- **ATTACHMENT-MULTI-LAYER-001**: Upload validation → ClamAV → heuristics → HMAC signed URLs
- **SSE-DB-QUEUE-001**: Eventos persistidos en tabla, polling con Last-Event-ID, collision detection

## Verificacion

- 22 unit tests / 111 assertions: 0 errores
- 18/18 servicios registrados y verificados
- 9/9 entity types registrados
- 4 tablas verificadas (support_ticket, support_ticket_field_data, support_ticket_events, support_ticket_viewers)
- SCSS compilado (23KB modulo + 23KB theme)
- PHP lint 0 errores en todos los ficheros
- Modulo desinstalado y reinstalado exitosamente

## Ficheros Principales

- Entity: `jaraba_support/src/Entity/SupportTicket.php`
- Services: `jaraba_support/src/Service/` (18 servicios)
- Controllers: `jaraba_support/src/Controller/` (SupportApiController, SupportStreamController)
- Install: `jaraba_support/jaraba_support.install` (hook_schema + update_10001)
- AI Agent: `jaraba_ai_agents/src/Plugin/AiAgent/SupportAgentSmartAgent.php`
- Tests: `jaraba_support/tests/src/Unit/Service/` (3 ficheros)
