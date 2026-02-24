# Aprendizaje #114: SolicitudEi Entity View + Triaje IA End-to-End Operativo

**Fecha:** 2026-02-24
**Contexto:** La pagina canonica de solicitudes (`/admin/content/andalucia-ei/solicitudes/{id}`) no renderizaba ningun campo. El triaje IA no funcionaba por incompatibilidad con la API del modulo Drupal AI. Ambos problemas se resolvieron y el flujo completo esta operativo: solicitud → triaje IA → vista admin con todos los campos.
**Impacto:** SolicitudEi — vista admin no funcional + triaje IA roto. Ahora operativo end-to-end.

---

## 1. Problema

Dos problemas independientes bloqueaban la gestion administrativa de solicitudes:

| # | Severidad | Bug | Sintoma |
|---|-----------|-----|---------|
| P1 | Critica | Vista canonica de entidad vacia | `/admin/content/andalucia-ei/solicitudes/3` muestra titulo pero ningun campo |
| P2 | Alta | Triaje IA no se ejecuta | El servicio falla silenciosamente, campos `ai_score`/`ai_justificacion`/`ai_recomendacion` siempre NULL |
| P3 | Alta | API del modulo AI incompatible | `$response->getText()` no existe en `ChatOutput` |
| P4 | Media | Modelo AI no soportado | `claude-haiku-4-5-latest` rechazado por el modulo contrib |
| P5 | Media | Clave API corrupta por Key module | `base64_encoded: "false"` (string) evaluado como truthy → decodifica la clave |
| P6 | Media | CRLF en .env | Line endings Windows corrompen valores de env vars en contenedores Linux |

---

## 2. Diagnostico

### P1 — Vista canonica vacia

**Causa raiz dual:**

1. **Sin `view_builder` handler**: La anotacion `@ContentEntityType` de `SolicitudEi` no declaraba handler `view_builder`. Sin este handler, Drupal no tiene ningun build controller para renderizar la entidad en su ruta canonica.

2. **Sin `setDisplayOptions('view', ...)`**: Los campos base definidos en `baseFieldDefinitions()` solo tenian `setDisplayConfigurable('view', TRUE)` (permite configurar desde UI), pero NO tenian `setDisplayOptions('view', [...])` (opciones por defecto). Sin opciones por defecto, el `EntityViewBuilder` no incluye ningun campo en el render array.

**Distincion clave:**
```php
// Esto NO es suficiente para que un campo aparezca por defecto:
->setDisplayConfigurable('view', TRUE)

// Esto SI hace que el campo aparezca sin configuracion previa:
->setDisplayOptions('view', [
    'label' => 'inline',
    'type' => 'string',
    'weight' => -15,
])
->setDisplayConfigurable('view', TRUE)
```

`setDisplayConfigurable('view', TRUE)` solo habilita la configuracion via Field UI (`/admin/structure/...`). Si no hay un `EntityViewDisplay` guardado en config Y no hay `setDisplayOptions()`, el campo no se renderiza.

### P2/P3 — API del modulo Drupal AI

**Causa raiz:** El servicio `SolicitudTriageService` fue escrito asumiendo una API de chat que no coincide con la del modulo `drupal/ai` (contrib):

| Aspecto | Codigo original (roto) | API correcta |
|---------|----------------------|--------------|
| Input de chat | Array plano `['messages' => [...]]` | `new ChatInput([new ChatMessage('user', $prompt)])` |
| System prompt | Clave en array | `$input->setSystemPrompt($text)` |
| Leer respuesta | `$response->getText()` | `$response->getNormalized()->getText()` |

La clase `ChatOutput` devuelta por `$llm->chat()` no tiene metodo `getText()`. La cadena correcta es `getNormalized()` (que devuelve un `ChatMessage`) y luego `getText()` sobre el mensaje.

### P4 — Modelo AI no soportado

**Causa raiz:** El modulo `drupal/ai` (provider Anthropic) usa API version `2024-02-29` que no soporta aliases de modelo como `claude-haiku-4-5-latest`. Solo acepta IDs explicitos como `claude-3-haiku-20240307`.

### P5 — base64_encoded como string truthy

**Causa raiz:** Al configurar las API keys con `drush cset key.key.openai_api key_provider_settings.base64_encoded false`, Drupal almacena la cadena `"false"` (no el booleano `FALSE`). En PHP, la cadena `"false"` es **truthy**:

```php
var_dump((bool) "false"); // true
var_dump((bool) FALSE);   // false
```

El `EnvKeyProvider` del Key module evalua `if ($settings['base64_encoded'])` → como la cadena `"false"` es truthy, aplica `base64_decode()` sobre la API key, producieno garbled binary.

**Fix:** Usar `drush ev` con PHP real para almacenar el booleano nativo:
```php
$config = \Drupal::configFactory()->getEditable('key.key.openai_api');
$settings = $config->get('key_provider_settings');
$settings['base64_encoded'] = FALSE;
$settings['strip_line_breaks'] = FALSE;
$config->set('key_provider_settings', $settings)->save();
```

### P6 — CRLF en .env

**Causa raiz:** El archivo `.env` editado desde Windows tenia line endings `\r\n` (CRLF). Cuando Docker/Lando lee `env_file`, los valores de las variables incluyen el `\r` al final. Una API key como `sk-proj-xxx\r` es invalida para cualquier proveedor.

**Detalle adicional:** `lando restart` NO recarga variables de `env_file` — solo `lando rebuild -y` destruye y recrea los contenedores con las variables actualizadas.

---

## 3. Solucion Implementada

### 3.1 View builder + display options (P1)

Anadido handler `view_builder` en la anotacion de la entidad:

```php
/**
 * @ContentEntityType(
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     ...
 *   },
 * )
 */
```

Anadido `setDisplayOptions('view', [...])` a los 22 campos base, con formatters apropiados segun tipo:

| Tipo de campo | Formatter | Label |
|---------------|-----------|-------|
| `string` | `string` | `inline` |
| `email` | `email_mailto` | `inline` |
| `datetime` | `datetime_default` | `inline` |
| `list_string` | `list_default` | `inline` |
| `boolean` | `boolean` | `inline` |
| `string_long` | `basic_string` | `above` |
| `integer` | `number_integer` | `inline` |
| `entity_reference` | `entity_reference_label` | `inline` |
| `created` / `changed` | `timestamp` | `inline` |

### 3.2 API del modulo Drupal AI (P2/P3)

```php
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;

// Crear input con objetos tipados:
$input = new ChatInput([
    new ChatMessage('user', $prompt),
]);
$input->setSystemPrompt($systemPrompt);

// Invocar y leer respuesta:
$response = $llm->chat($input, $providerConfig['model']);
$text = $response->getNormalized()->getText();
```

### 3.3 Modelo explicito (P4)

```php
private const PROVIDERS = [
    ['id' => 'anthropic', 'model' => 'claude-3-haiku-20240307'],
    ['id' => 'openai', 'model' => 'gpt-4o-mini'],
];
```

### 3.4 Failover con \Throwable (mejora adicional)

```php
} catch (\Throwable $e) {
    $this->logger->warning('Proveedor IA @id fallo: @msg', [...]);
    continue;
}
```

---

## 4. Reglas Derivadas

### ENTITY-VIEW-DISPLAY-001 (nueva)

Toda `ContentEntity` con ruta canonica (`links.canonical`) DEBE tener:
1. **`view_builder` handler**: `"view_builder" = "Drupal\Core\Entity\EntityViewBuilder"` en la anotacion `@ContentEntityType`
2. **`setDisplayOptions('view', [...])`** en CADA campo base que deba mostrarse en la vista canonica
3. **`setDisplayConfigurable('view', TRUE)`** para permitir personalizacion desde Field UI

Solo `setDisplayConfigurable('view', TRUE)` sin `setDisplayOptions()` NO muestra el campo por defecto — requiere configuracion manual previa via Field UI.

### DRUPAL-AI-CHAT-001 (nueva)

Al integrar con el modulo `drupal/ai` (contrib), la API de chat requiere:
1. **Input**: `new ChatInput([new ChatMessage('role', 'content')])` — NO arrays planos
2. **System prompt**: `$input->setSystemPrompt($text)` — NO como clave de array
3. **Response**: `$response->getNormalized()->getText()` — `getNormalized()` devuelve `ChatMessage`, luego `getText()`
4. **Modelos**: Usar IDs explicitos (`claude-3-haiku-20240307`), NO aliases (`claude-haiku-4-5-latest`)
5. **Failover**: `catch (\Throwable)` para capturar tanto `Exception` como `TypeError`/`Error` de proveedores

### KEY-MODULE-BOOL-001 (nueva)

La configuracion de `key_provider_settings` del Key module requiere booleanos nativos PHP. `drush cset` almacena `false` como la cadena `"false"` (truthy en PHP). Para settings booleanos como `base64_encoded` y `strip_line_breaks`, usar `drush ev` con codigo PHP que asigne `FALSE` nativo:

```php
drush ev '$c = \Drupal::configFactory()->getEditable("key.key.NAME");
$s = $c->get("key_provider_settings");
$s["base64_encoded"] = FALSE;
$s["strip_line_breaks"] = FALSE;
$c->set("key_provider_settings", $s)->save();'
```

### ENV-FILE-CRLF-001 (nueva)

Archivos `.env` usados como `env_file` en Docker/Lando DEBEN tener line endings LF (Unix). CRLF (Windows) anade `\r` al final de cada valor, corrompiendo API keys y tokens. Verificar con `file .env` (debe decir "ASCII text", no "ASCII text, with CRLF line terminators"). Fix: `sed -i 's/\r$//' .env` + `lando rebuild -y` (restart NO recarga env_file).

---

## 5. Ficheros Modificados

| Fichero | Cambios | Commit |
|---------|---------|--------|
| `jaraba_andalucia_ei/src/Entity/SolicitudEi.php` | `view_builder` handler + `setDisplayOptions('view')` en 22 campos | `189dbd4c` |
| `jaraba_andalucia_ei/src/Service/SolicitudTriageService.php` | ChatInput/ChatMessage, getNormalized()->getText(), modelo explicito, \Throwable | `3844e1de` |

---

## 6. Verificacion

### Vista canonica de solicitud

| Test | Resultado |
|------|-----------|
| GET `/admin/content/andalucia-ei/solicitudes/3` | HTTP 200, todos los campos visibles |
| Campos personales | nombre, email (mailto link), telefono, fecha nacimiento, DNI/NIE |
| Campos territoriales | provincia (label humano), municipio |
| Campos triaje | situacion laboral, tiempo desempleo, nivel estudios, migrante (Si/No), prestacion |
| Campos texto largo | experiencia profesional, motivacion (label `above`) |
| Campos admin | estado, colectivo inferido, notas admin, IP, tenant |
| Timestamps | fecha de solicitud, ultima modificacion (formato datetime) |
| Campos IA | Puntuacion IA: 75, Justificacion IA: texto completo, Recomendacion IA: Admitir |

### Triaje IA end-to-end

| Test | Resultado |
|------|-----------|
| Provider primario (Anthropic) | OK — `claude-3-haiku-20240307` responde JSON valido |
| Score calculado | 75/100 para candidato autonomo con experiencia |
| Justificacion | "Amplia experiencia profesional, nivel formativo alto, motivacion clara. No pertenece a colectivo prioritario." |
| Recomendacion | `admitir` (score >= 70) |
| Failover a OpenAI | Configurado con `gpt-4o-mini` como backup |
| Campos guardados en BD | `ai_score=75`, `ai_justificacion=...`, `ai_recomendacion=admitir` |

---

## 7. Leccion Clave

**Las entidades de Drupal requieren configuracion explicita en tres niveles para renderizar campos:**

1. **Entity level**: `view_builder` handler en `@ContentEntityType` — sin esto, Drupal no sabe como construir la pagina
2. **Field level**: `setDisplayOptions('view', [...])` — sin esto, los campos no aparecen en el render por defecto
3. **UI level**: `setDisplayConfigurable('view', TRUE)` — esto solo permite personalizar desde Field UI, NO muestra nada por si solo

**Patron de integracion con Drupal AI module:** El modulo AI contrib tiene una API tipada con objetos (`ChatInput`, `ChatMessage`, `ChatOutput`). No acepta arrays planos ni tiene metodos de conveniencia directos. La cadena correcta es: `ChatInput` → `chat()` → `ChatOutput` → `getNormalized()` → `ChatMessage` → `getText()`.

**PHP strings vs booleans en configuracion Drupal:** `drush cset` serializa todo como strings YAML. Para configuracion que requiere booleanos PHP nativos (Key module, condiciones de acceso), usar `drush ev` con codigo PHP explicito.
