# Aprendizajes: Smart Router + Redis + Cache IA

**Fecha:** 2026-01-22  
**Contexto:** Implementación de Smart Router v2, Cache Copilot, y Redis

---

## 1. Cache de Respuestas IA con Drupal Cache API

### Problema
Las llamadas repetidas al Copiloto con mensajes similares consumían tokens innecesariamente.

### Solución
Crear `CopilotCacheService` que:
1. Genera claves hash normalizadas (mensaje + modo + contexto)
2. Verifica cache antes de llamar a proveedores IA
3. Almacena respuestas con TTL de 1 hora
4. Trackea hit/miss rate para métricas

### Código Clave
```php
// CopilotCacheService.php
public function generateCacheKey(string $message, string $mode, array $context): string {
    $normalizedMessage = strtolower(trim(preg_replace('/\s+/', ' ', $message)));
    $payload = json_encode(['message' => $normalizedMessage, 'mode' => $mode, ...]);
    return 'copilot_response:' . md5($payload);
}
```

### Lección
Usar Drupal Cache API permite cambiar backend sin modificar código (BD → Redis → Memcached).

---

## 2. Redis en Lando

### Configuración
```yaml
# .lando.yml
services:
  redis:
    type: redis:7
    portforward: 6379
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
```

### Verificación
```bash
lando redis-cli ping  # → PONG
```

### Lección
Incluir `healthcheck` en servicios Lando evita fallos silenciosos durante rebuild.

---

## 3. Detección Automática de Entorno en settings.php

### Problema
La configuración de Redis debe funcionar tanto en Lando como en producción.

### Solución
```php
// settings.php
$is_lando = getenv('LANDO') === 'ON';
$redis_host = $is_lando ? 'redis' : (getenv('REDIS_HOST') ?: 'redis');
```

### Lección
Usar variables de entorno con fallbacks permite configuración única que funciona en ambos entornos.

---

## 4. Gaps vs Realidad

### Problema
El Gap Analysis indicaba que varios componentes estaban "pendientes", pero ya estaban implementados.

### Verificación
Ejecutar `drush pm-list` y revisar directorio `src/Entity` de cada módulo para confirmar estado real.

### Hallazgo
- FOC Fase 1: ✅ 4 entidades ya existían
- Diagnóstico Express: ✅ 37 archivos
- LMS: ✅ Módulo completo
- API REST: ✅ 420 líneas

### Lección
Siempre verificar código existente antes de implementar. El Gap Analysis puede quedar desactualizado.

---

## 5. Pendiente para Producción

### Tarea
Configurar Redis en Docker/IONOS:

```yaml
# docker-compose.yml (producción)
redis:
  image: redis:7-alpine
  restart: unless-stopped
  volumes:
    - redis_data:/data
```

Configurar variables:
- `REDIS_HOST=redis`
- `REDIS_PORT=6379`

---

## 6. Variables de Entorno en Lando (Gotcha)

### Problema
Las variables definidas en `env_file` y `overrides.environment` del `.lando.yml` NO llegan a PHP como `getenv()`.

### Síntoma
```bash
docker exec appserver bash -c "echo REDIS_HOST=$REDIS_HOST"
# Output: REDIS_HOST=  (vacío!)
```

### Solución
Configurar Redis **directamente** en `settings.php` con valores hardcodeados:
```php
if (extension_loaded('redis') && class_exists('Redis')) {
  $settings['redis.connection']['host'] = 'redis';  // Hardcoded
  $settings['redis.connection']['port'] = 6379;
  $settings['cache']['default'] = 'cache.backend.redis';
}
```

### Lección
En Lando con receta `drupal11`, **no confiar en variables de entorno para configuración crítica**. Usar valores hardcodeados o detectar hostname.

---

## 7. services.yml para Redis Cache Tags

### Problema
Redis conectado pero con advertencia: "No cache tags found, make sure that the redis cache tag checksum service is used."

### Solución
Crear `web/sites/default/services.yml`:
```yaml
services:
  cache_tags.invalidator.checksum:
    class: Drupal\redis\Cache\RedisCacheTagsChecksum
    arguments: ['@redis.factory']
    tags:
      - { name: cache_tags_invalidator }
```

### Lección
El módulo Redis no registra automáticamente el servicio de checksum. Hay que declararlo manualmente en services.yml.

---

## 8. Tika - Imagen Docker

### Problema
`apache/tika:2.9.1` no existe en Docker Hub.

### Solución
Usar imagen sin versión específica:
```yaml
tika:
  type: compose
  services:
    image: apache/tika
    command: java -jar /tika-server-standard.jar -h 0.0.0.0
    ports:
      - '9998:9998'
```

### Lección
Evitar versiones específicas de imágenes Docker en desarrollo local. Usar `latest` o sin tag para mayor compatibilidad.

