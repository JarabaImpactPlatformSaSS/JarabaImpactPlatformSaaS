# Lecciones Aprendidas: Integración Qdrant v5.1

> Fecha: 2026-01-11  
> Contexto: Debugging de indexación de productos en Qdrant

---

## 1. PHP: `??` vs `?:` para Config Fallbacks

### Problema
```php
// ❌ NO funciona si el valor es "" (string vacío)
$chunkSize = $config->get('embeddings.chunk_size') ?? 500;
```

### Solución
```php
// ✅ Funciona con null Y con ""
$chunkSize = $config->get('embeddings.chunk_size') ?: 500;
```

### Explicación
| Valor | `?? 500` | `?: 500` |
|-------|----------|----------|
| `null` | → 500 | → 500 |
| `""` | → "" ❌ | → 500 ✅ |
| `0` | → 0 | → 500 |

> **Regla**: Para configs de Drupal que pueden devolver strings vacíos, usar `?:` con validación adicional para `0`.

---

## 2. Drupal Config Overrides

### Cómo funcionan
Los overrides en `settings.php` sobrescriben la BD pero:
- **NO aparecen en formularios** (muestran valor de BD)
- Son **inmutables** en runtime
- Requieren `drush cr` para activarse

### Inclusión correcta
```php
// En settings.php - usar ruta relativa a __DIR__
$_jaraba_rag = __DIR__ . '/../../modules/custom/jaraba_rag/config/settings.jaraba_rag.php';
if (file_exists($_jaraba_rag)) {
  include $_jaraba_rag;
}
```

> **Nota**: `$app_root` debe estar definida antes del include.

---

## 3. Qdrant en Lando

### Configuración Docker
```yaml
# .lando.yml
services:
  qdrant:
    type: compose
    services:
      image: qdrant/qdrant:v1.16.3
      ports:
        - "6333:6333"
```

### URLs de acceso
| Desde | URL |
|-------|-----|
| Host (navegador) | `http://qdrant.jaraba-saas.lndo.site` |
| Contenedor PHP | `http://qdrant:6333` |

---

## 4. Debugging de Indexación RAG

### Pipeline de indexación
```
Hook (entity_insert/update)
    ↓
extractContent() → Extraer texto
    ↓
chunkContent() → Dividir en fragmentos
    ↓
generateEmbedding() → OpenAI API
    ↓
upsertPoint() → Qdrant API
```

### Puntos de fallo comunes
1. **0 chunks** → `chunk_size = 0` (fallback no funcionó)
2. **cURL error 3** → `host` vacío (ídem)
3. **Sin contenido** → Campos mal configurados en `extractContent()`

### Log útil para debug
```php
$this->log("Iniciando chunking", [
    'text_length' => strlen($text),
    'chunk_size' => $chunkSize,
    'overlap' => $overlap,
]);
```

---

## 5. Fallbacks Robustos (Patrón Recomendado)

```php
// Para valores numéricos de config que pueden ser 0, null, o ""
$value = (int) ($config->get('key') ?: 0);
if ($value <= 0) {
    $value = DEFAULT_VALUE;
}

// Para strings
$value = $config->get('key') ?: 'default_value';
```

---

## Archivos de Referencia

- [KbIndexerService.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_rag/src/Service/KbIndexerService.php) - Servicio de indexación
- [QdrantDirectClient.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_rag/src/Client/QdrantDirectClient.php) - Cliente Qdrant
- [settings.jaraba_rag.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_rag/config/settings.jaraba_rag.php) - Config overrides
