# Aprendizaje #106: Secure Messaging Implementation (Doc 178)

**Fecha:** 2026-02-20
**Modulo:** `jaraba_messaging`
**Alcance:** 104 archivos, 6 sprints, implementacion completa desde cero
**Documentos base:** Doc 178 (Especificacion tecnica), Doc 178A (Anexo)

---

## 1. Contexto

Implementacion completa del modulo `jaraba_messaging` para mensajeria segura bidireccional en tiempo real. El modulo soporta conversaciones cifradas con AES-256-GCM entre profesionales y clientes en las 5 verticales de la plataforma (ServiciosConecta, JarabaLex, Empleabilidad, Emprendimiento, AgroConecta/ComercioConecta).

## 2. Patrones Aprendidos

### 2.1 Custom Schema Tables con DTOs Readonly

**Problema:** Los mensajes cifrados requieren columnas `MEDIUMBLOB` y `VARBINARY` que la Entity API de Drupal no soporta nativamente. Ademas, el volumen de escrituras es alto (potencialmente miles por minuto).

**Solucion:** Usar `hook_schema()` para definir tablas custom (`secure_message`, `message_audit_log`, `message_read_receipt`) y DTOs `final readonly class` para encapsular las filas:

```php
final readonly class SecureMessageDTO {
  public static function fromRow(object $row): self {
    return new self(
      id: (int) $row->id,
      conversationId: (int) $row->conversation_id,
      // ... campos
    );
  }
  public function toArray(): array { /* ... */ }
}
```

**Regla:** Cuando la Entity API no cubre los tipos de columna necesarios o el volumen es muy alto, custom schema + DTO es la combinacion correcta. El servicio maneja CRUD via `@database`.

### 2.2 Cifrado Server-Side AES-256-GCM

**Problema:** Los mensajes deben cifrarse at-rest pero seguir siendo buscables y procesables por IA (RAG/Qdrant). E2E impediria estas funcionalidades.

**Solucion:** Cifrado server-side con AES-256-GCM:
- **IV:** 12 bytes aleatorios por mensaje (`random_bytes(12)`)
- **Tag:** 16 bytes de autenticacion (GCM lo genera automaticamente)
- **Almacenamiento:** `$iv . $tag . $ciphertext` concatenados en MEDIUMBLOB
- **Clave:** Derivada con Argon2id (`sodium_crypto_pwhash`) desde env var `JARABA_PMK`
- **Cache:** La clave derivada se cachea por tenant_id en una propiedad del servicio (en memoria, no en BD)

**Regla MSG-ENC-001:** Los datos sensibles en tablas custom DEBEN cifrarse con AES-256-GCM. IV aleatorio por registro, clave derivada desde env var, NUNCA almacenar claves en BD.

### 2.3 Hash Chain SHA-256 para Audit Inmutable

**Problema:** El audit trail debe ser inmutable e irrefutable. Cualquier modificacion o eliminacion de entradas debe ser detectable.

**Solucion:** Cada entrada del audit log incluye un `previous_hash` que encadena con el hash de la entrada anterior:

```php
$hash = hash('sha256', $previousHash . $conversationId . $action . $timestamp . json_encode($metadata));
```

La verificacion recorre la cadena completa y reporta la posicion exacta de cualquier rotura via un `IntegrityReport` Value Object.

### 2.4 WebSocket Auth Middleware

**Problema:** Las conexiones WebSocket no pasan por el middleware HTTP de Drupal, por lo que no tienen session ni CSRF automatico.

**Solucion:** `AuthMiddleware` que intercepta `onOpen()`:
1. Extrae JWT del query string (`?token=xxx`) o session cookie del header HTTP
2. Valida el token y resuelve `user_id` + `tenant_id`
3. Adjunta identidad al objeto `ConnectionInterface`
4. Conexiones invalidas se cierran con codigo 4401

**Regla MSG-WS-001:** Auth DEBE ocurrir en `onOpen()`, ANTES de permitir cualquier mensaje.

### 2.5 ConnectionManager con Indices SplObjectStorage

**Problema:** Necesidad de buscar conexiones por user_id y tenant_id para enviar mensajes dirigidos y hacer broadcast por tenant.

**Solucion:** `ConnectionManager` mantiene:
- `SplObjectStorage` principal con metadata (user_id, tenant_id)
- Array indexado `$userConnections[userId][]` para busqueda rapida
- Array indexado `$tenantConnections[tenantId][]` para broadcast

### 2.6 Optional DI con @?

**Problema:** `AttachmentBridgeService` depende de `jaraba_vault` que puede no estar instalado.

**Solucion:** En `services.yml` usar `@?jaraba_vault.client`:

```yaml
jaraba_messaging.attachment_bridge:
  class: Drupal\jaraba_messaging\Service\AttachmentBridgeService
  arguments:
    - '@?jaraba_vault.client'
```

El constructor acepta `?VaultClientInterface $vault = NULL` y degrada a almacenamiento local:

```php
$this->vaultClient?->store($data) ?? $this->storeLocally($data);
```

### 2.7 Cursor-Based Pagination

**Problema:** Los mensajes se cargan cronologicamente inversos. OFFSET es ineficiente en tablas grandes.

**Solucion:** Cursor con `before_id`:

```php
$query->condition('id', '<', $beforeId)->orderBy('id', 'DESC')->range(0, $limit);
```

Response: `{ meta: { has_more: true, oldest_id: 456 } }`.

### 2.8 Conflicto de Nombre create() en ControllerBase

**Problema:** Un metodo de API llamado `create()` en el controlador entra en conflicto con el metodo estatico `create(ContainerInterface $container)` de `ControllerBase` (factory DI).

**Solucion:** Renombrar el metodo de API a `createConversation()` y actualizar el routing.yml. Verificar SIEMPRE que los nombres de metodos de API no colisionen con metodos heredados de `ControllerBase`.

### 2.9 Rate Limiting via Database

**Problema:** Limitar envio de mensajes sin Redis (entorno dev) ni infraestructura adicional.

**Solucion:** COUNT en la tabla `secure_message` con ventana temporal:

```php
$count = $db->select('secure_message')
  ->condition('sender_id', $userId)
  ->condition('created_at', time() - 60, '>')
  ->countQuery()->execute()->fetchField();
if ($count >= 30) throw new RateLimitException(30, 60, 'user');
```

**Regla MSG-RATE-001:** Rate limiting con contadores DB. Excepciones tipadas con `limit`, `windowSeconds`, `scope`.

## 3. Errores Corregidos

| Error | Causa | Solucion |
|-------|-------|----------|
| PHP Fatal: cannot redeclare `create()` | Conflicto con `ControllerBase::create()` (static factory) | Renombrar a `createConversation()` |
| MessageOwnerAccessCheck no resuelve | Faltaba `@database` en services.yml | Anadir argumento `- '@database'` |

## 4. Metricas Finales

| Metrica | Valor |
|---------|-------|
| Total archivos creados | 104 |
| Archivos PHP | 64 |
| Archivos YAML/Config | 13 |
| Templates Twig | 9 |
| SCSS | 11 |
| JS | 4 |
| PHP syntax errors | 0 |
| Sprints completados | 6/6 |
| Entidades Content Entity | 2 (SecureConversation, ConversationParticipant) |
| Custom schema tables | 3 (secure_message, message_audit_log, message_read_receipt) |
| Servicios | 18 |
| Access Checks | 7 |
| Controladores | 7 |
| ECA Plugins | 8 |
| Permisos | 13 |
| Endpoints API REST | 20+ |

## 5. Reglas Nuevas

| ID | Nombre | Descripcion |
|----|--------|-------------|
| MSG-ENC-001 | Cifrado Server-Side | AES-256-GCM + Argon2id KDF + env var. NUNCA almacenar claves en BD |
| MSG-WS-001 | WebSocket Auth Middleware | Auth en onOpen(), JWT o session, cerrar invalidos con 4401 |
| MSG-RATE-001 | Rate Limiting Mensajeria | 30 msg/min/user, 100 msg/min/conversation. Contadores DB |

## 6. Post-Audit Hardening (2026-02-23)

Auditoria de compliance detecto 5 gaps y se implementaron todas las correcciones:

### 6.1 Tests creados (P1 — Security-critical)

| Test | Tipo | Cobertura |
|------|------|-----------|
| `EncryptedPayloadTest` | Unit | IV/tag validation, immutability, edge cases |
| `MessageEncryptionServiceTest` | Kernel | Roundtrip, IV uniqueness, tenant isolation, tamper detection, large messages |
| `MessageAuditServiceTest` | Kernel | Hash chain integrity, tamper detection, genesis hash, pagination, conversation isolation |

### 6.2 Optional DI @? corregido (P2)

**AttachmentBridgeService**: Eliminadas llamadas a `\Drupal::hasService()` y `\Drupal::service()`. Ahora usa propiedad `$vaultService` inyectada via setter con `@?jaraba_buzon_confianza.vault` en services.yml.

**PresenceService**: Nuevo soporte Redis via setter `setRedisClient()` con `@?redis.factory`. Degrada a arrays estaticos si Redis no esta disponible.

### 6.3 ECA Config YAMLs pre-configurados (P3)

Creados 3 modelos ECA en `config/optional/` (se importan solo si ECA esta instalado):
- `messaging_new_message_notification` — Notifica a usuarios offline
- `messaging_first_message_welcome` — Auto-respuesta en primer mensaje
- `messaging_unread_reminder` — Recordatorio de mensajes no leidos

### 6.4 Redis backend para PresenceService (P4)

PresenceService refactorizado con patron try-Redis-first, catch-fallback-to-memory:
- `SETEX` con TTL configurable para online users
- `SETEX` con 5s TTL para typing indicators
- Sets de Redis (`SADD/SMEMBERS`) para consulta rapida por tenant
- `SCAN` para cleanup de typing indicators por usuario

### 6.5 Audit log hashes CHAR(64) → BLOB binario (P4)

- Schema actualizado: `previous_hash` y `current_hash` de CHAR(64) a TINYBLOB (32 bytes)
- Ahorro: ~50% storage por columna hash (32 bytes vs 64 bytes por entry)
- Conversion hex2bin/bin2hex en el boundary DB del MessageAuditService
- Update hook `jaraba_messaging_update_10001()` migra datos existentes en batches
- Chain integrity preservada: la computacion de hash sigue usando hex strings internamente

### 6.6 Metricas Post-Hardening

| Metrica | Antes | Despues |
|---------|-------|---------|
| Tests | 0 | 3 (1 Unit + 2 Kernel) |
| ECA configs | 0 | 3 |
| Optional DI correcta | 0 | 2 (vault + redis) |
| Hash storage efficiency | 64 bytes/hash | 32 bytes/hash |
| PresenceService backends | Memory only | Redis + Memory fallback |

## 7. Documentos Actualizados

- **Directrices v61.0.0** — +3 reglas (MSG-ENC-001, MSG-WS-001, MSG-RATE-001)
- **Arquitectura v61.0.0** — jaraba_messaging de PLANNED a IMPLEMENTED, 104 archivos
- **Flujo v15.0.0** — +8 patrones nuevos, reglas de oro #20, #21
- **Desarrollo v3.3** — +3 checklists (Cifrado, WebSocket, Custom Schema)
- **Vertical Customization v2.1.0** — Seccion 7 cross-vertical messaging
- **Indice v77.0.0** — Highlight actualizado de Plan a Implementado
