# Aprendizaje #105: ServiciosConecta Sprint S3 — Booking Engine Fix

**Fecha:** 2026-02-20
**Modulo:** `jaraba_servicios_conecta`
**Contexto:** Correccion de bugs criticos en la API de reservas, state machine y cron de recordatorios. La Fase 1 (entidades + marketplace) estaba completa pero la logica de negocio de Fases 2-5 tenia errores que impedian crear y gestionar reservas.
**Reglas nuevas:** API-FIELD-001, STATE-001, CRON-FLAG-001

---

## Resumen

Sprint S3 de ServiciosConecta: fix de 3 archivos criticos del modulo `jaraba_servicios_conecta` que impedian el funcionamiento del motor de reservas. Bugs encontrados: field mapping incorrecto en createBooking(), metodos faltantes en AvailabilityService, state machine con status inexistentes, cron sin flags de idempotencia, y hooks con nombres de campo incorrectos.

---

## Lecciones Aprendidas

### 1. Entity Field Mapping — Los nombres del request NO son los de la entidad

**Problema:** `createBooking()` usaba `service_id`, `client_id`, `datetime`, `notes` como nombres de campo en `$storage->create()`, pero la entidad Booking define `offering_id`, `uid`, `booking_date`, `client_notes`.

**Causa raiz:** Al escribir el controller, se usaron nombres intuitivos del JSON request como nombres de campo de la entidad sin verificar `baseFieldDefinitions()`.

**Solucion:** Mapeo explicito en el controlador:
```php
$booking = $storage->create([
  'offering_id' => $offeringId,    // NO 'service_id'
  'uid' => $currentUser->id(),     // NO 'client_id'
  'booking_date' => $datetime,     // NO 'datetime'
  'client_notes' => $data['notes'] ?? '',  // NO 'notes'
]);
```

**Regla API-FIELD-001:** Los campos en `$storage->create()` DEBEN coincidir exactamente con `baseFieldDefinitions()`. Siempre leer la entidad antes de escribir el controller.

### 2. EntityOwnerTrait — `uid` es el campo owner, no `client_id` ni `user_id`

**Problema:** El controller usaba `$entity->get('client_id')->target_id` para obtener el cliente, y el hook usaba `$entity->get('user_id')->target_id` para el proveedor. Ninguno de estos campos existe.

**Causa raiz:** Las entidades con `EntityOwnerTrait` definen automaticamente un campo `uid` como owner. Al necesitar referenciar al "cliente" de una booking, se invento un `client_id` inexistente.

**Solucion:**
- Booking owner (`uid`) = cliente que reserva. Usar `$entity->getOwnerId()`.
- ProviderProfile owner (`uid`) = usuario del profesional. Usar `$entity->getOwnerId()`.
- Provider reference = `$entity->get('provider_id')->target_id`.

**Patron:** En una relacion tripartita (owner-provider-offering), el owner es siempre `uid` via EntityOwnerTrait. Las demas relaciones son entity_reference explicitas.

### 3. State Machine — Los status de la API deben mapearse a los de la entidad

**Problema:** El controller usaba `cancelled` como status valido, pero la entidad define `cancelled_client` y `cancelled_provider` (sin `cancelled` generico).

**Causa raiz:** Desacoplamiento entre el diseno de la API (simplicidad: `cancelled`) y el modelo de datos (precision: `cancelled_client` / `cancelled_provider`).

**Solucion:** Mapeo en el punto de entrada:
```php
if ($newStatus === 'cancelled') {
  $newStatus = $isProvider ? 'cancelled_provider' : 'cancelled_client';
}
```

Y deteccion generica en hooks:
```php
if (str_starts_with($new_status, 'cancelled_')) {
  // Liberar slot, enviar notificacion...
}
```

**Regla STATE-001:** Los valores internos DEBEN coincidir con `allowed_values`. El mapeo generico se hace solo en el punto de entrada API.

### 4. Cron Idempotency — Sin flags, cada ejecucion reenvia todos los recordatorios

**Problema:** `_send_reminders()` no verificaba ni actualizaba los flags `reminder_24h_sent` / `reminder_1h_sent`. Cada cron (cada 15 min) reenviaba recordatorios a todas las bookings en la ventana temporal.

**Causa raiz:** Los campos de flag existen en la entidad Booking (`reminder_24h_sent`, `reminder_1h_sent` boolean con default FALSE), pero el cron no los usaba.

**Solucion:**
```php
// En la query: filtrar por NOT sent
->condition($flag, 0)

// Tras enviar: marcar como sent
$booking->set($flag, TRUE);
$booking->save();
```

**Regla CRON-FLAG-001:** Toda accion cron con side-effects (emails, webhooks) DEBE ser idempotente via flags de ejecucion.

### 5. Refactoring collision detection — Extraer logica duplicable a metodo privado

**Problema:** La logica de deteccion de colision entre candidato y bookings existentes estaba inline en `getAvailableSlots()` (lineas 160-170). Para el nuevo `isSlotAvailable()`, se necesitaba la misma logica.

**Solucion:** Extraer a `hasCollision(int $candidateStart, int $candidateEnd, array $bookings, int $buffer): bool` como metodo privado. Ambos metodos publicos lo reutilizan.

**Patron:** Cuando un metodo nuevo necesita la misma logica que uno existente, extraer la logica compartida a un metodo privado en lugar de duplicar o llamar al metodo publico con parametros artificiales.

### 6. Validaciones defensivas en createBooking() — 5 capas

**Solucion implementada:**
1. **Provider existe** — load() + null check
2. **Provider activo + aprobado** — `is_active` AND `verification_status === 'approved'`
3. **Offering pertenece al provider** — `$offering->get('provider_id')->target_id !== $providerId`
4. **Datetime futuro + advance_booking_min** — timestamp > now + (advance_hours * 3600)
5. **Slot disponible** — `isSlotAvailable()` (slot recurrente + sin colision)

**Patron:** Las validaciones van de mas barata a mas cara: null checks → field checks → relationship checks → time checks → availability checks (con queries).

---

## Archivos Modificados (3)

| Archivo | Cambio |
|---------|--------|
| `src/Service/AvailabilityService.php` | +3 metodos: `isSlotAvailable()`, `markSlotBooked()`, `hasCollision()` (privado). Refactor de `getAvailableSlots()` para usar `hasCollision()`. |
| `src/Controller/ServiceApiController.php` | Reescritura de `createBooking()` (field mapping, 5 validaciones, client data, price, Jitsi URL). Reescritura de `updateBooking()` (state machine, role enforcement, cancellation_reason, booking_date). |
| `jaraba_servicios_conecta.module` | Fix `_send_reminders()` (flags 24h/1h). Fix `hook_entity_update()` (booking_date, getOwnerId, cancelled_ prefix, str_starts_with). Fix `_cancel_stale_bookings()` (cancelled_client). Fix provider approval (getOwnerId vs user_id). |

---

## Bugs Corregidos

| Bug | Archivo | Causa | Fix |
|-----|---------|-------|-----|
| `createBooking()` 500 error | Controller | `isSlotAvailable()` no existia | Implementado en AvailabilityService |
| `createBooking()` entity save fail | Controller | Campos incorrectos (service_id, datetime, client_id) | Mapeado a offering_id, booking_date, uid |
| `createBooking()` sin validaciones | Controller | No validaba provider/offering/datetime | 5 validaciones anadidas |
| `updateBooking()` invalid status | Controller | `cancelled` no existe en entidad | Mapeo a cancelled_client/cancelled_provider |
| `updateBooking()` campo inexistente | Controller | `client_id`, `datetime` | getOwnerId(), booking_date |
| Cron duplica recordatorios | Module | Sin flags idempotencia | condition($flag, 0) + set($flag, TRUE) |
| hook_entity_update campo inexistente | Module | `client_id`, `datetime`, `user_id` | getOwnerId(), booking_date |
| hook_entity_update status check | Module | `=== 'cancelled'` | str_starts_with('cancelled_') |
| Stale bookings status invalido | Module | `'cancelled'` | `'cancelled_client'` |

---

## Estado del Motor de Reservas

| Componente | Estado Pre-Sprint | Estado Post-Sprint |
|------------|------------------|--------------------|
| Entity definitions (5) | OK | OK |
| Marketplace listing API | OK | OK |
| Provider detail API | OK | OK |
| Availability slots API | OK | OK |
| `createBooking()` | BROKEN (500) | OPERATIVO |
| `updateBooking()` | BROKEN (invalid status) | OPERATIVO |
| `isSlotAvailable()` | NO EXISTIA | OPERATIVO |
| `markSlotBooked()` | NO EXISTIA | OPERATIVO |
| Cron reminders | DUPLICADOS | IDEMPOTENTE |
| Cron stale cancel | STATUS INVALIDO | CORREGIDO |
| Cron no-show | OK | OK |
| hook_entity_update notifications | CAMPOS ROTOS | CORREGIDO |
| hook_entity_update slot release | STATUS INCORRECTO | CORREGIDO |
| hook_mail (5 templates) | OK | OK |

---

> **Version:** 1.0.0 | **Fecha:** 2026-02-20 | **Autor:** IA Asistente
