# DIRECTRICES DE DESARROLLO - JARABA IMPACT PLATFORM

> **Documento Central de Referencia Obligatoria**
> Versión: 3.3 | Fecha: 2026-02-20

---

## ⚠️ VERIFICAR ANTES DE CADA COMMIT

Este checklist DEBE revisarse antes de cualquier commit o PR.

### 1. Internacionalización (i18n)

- [ ] **Textos traducibles**: `{% trans %}Texto{% endtrans %}` en Twig
- [ ] **Controladores**: `$this->t('Texto')` en PHP
- [ ] **JavaScript**: `Drupal.t('Texto')` en JS
- [ ] **NO usar**: `|t` filter en Twig (usar bloque `{% trans %}`)

### 2. Estilos CSS/SCSS

- [ ] **Archivos SCSS**: NUNCA crear `.css` directo, siempre `.scss`
- [ ] **Variables inyectables**: `var(--ej-*)` para valores dinámicos
- [ ] **Compilación**: `npm run build` desde WSL con NVM
- [ ] **Limpiar caché**: `lando drush cr` después de compilar

### 3. Paleta de Colores Jaraba (7 colores oficiales)

| Variable | Hex | Uso |
|----------|-----|-----|
| `--ej-color-corporate` | #233D63 | Azul corporativo, identidad marca |
| `--ej-color-impulse` | #FF8C42 | Naranja empresas, CTAs, energia |
| `--ej-color-innovation` | #00A9A5 | Turquesa talento, innovacion |
| `--ej-color-earth` | #556B2F | Verde tierra, agro, sostenibilidad |
| `--ej-color-success` | #10B981 | Verde exito, confirmaciones |
| `--ej-color-warning` | #F59E0B | Ambar alertas, atencion |
| `--ej-color-danger` | #EF4444 | Rojo error, eliminacion, critico |

- [ ] **Variantes**: Usar `color-mix()` para oscurecer/aclarar: `color-mix(in srgb, var(--ej-color-success, #10B981) 65%, black)`
- [ ] **Transparencias**: Usar `color-mix()` con transparent: `color-mix(in srgb, var(--ej-color-danger, #EF4444) 10%, transparent)`
- [ ] **NO usar**: Colores Tailwind (#059669, #dc2626), Material Design (#1565C0, #C62828), o Bootstrap (#dc3545)
- [ ] **Fallbacks**: El hex dentro de `var()` DEBE coincidir con la paleta oficial

### 4. Iconografía

- [ ] **Formato**: `jaraba_icon('category', 'name', {options})`
- [ ] **Categorías**: actions, ai, business, commerce, general, ui, verticals
- [ ] **Variantes**: `{ variant: 'outline' }` o `{ variant: 'duotone' }`
- [ ] **Colores vía CSS**: NO crear iconos por color
- [ ] **Twig**: `{{ jaraba_icon('cat', 'name', { size: '20px' }) }}` — NUNCA emojis inline
- [ ] **SCSS content:**: Usar escapes Unicode texto (`'\26A0'`, `'\2726'`) — NUNCA emojis color

### 5. Componentes SDC (Drupal 11)

- [ ] **Estructura**: `.component.yml` + `.twig` + `.scss`
- [ ] **Compound Variants**: Un template, múltiples variantes
- [ ] **Props tipados**: Definir en component.yml
- [ ] **Slots**: Para contenido personalizable

### 6. Entidades Drupal (Content Entities)

- [ ] **Interface**: Crear `*Interface.php` para cada entidad
- [ ] **Anotaciones**: `@ContentEntityType` completas
- [ ] **Campos base**: `created`, `changed`, `uuid`
- [ ] **Permisos**: Añadir a `.permissions.yml`

### 7. APIs y Servicios

- [ ] **Inyección de dependencias**: Constructores tipados
- [ ] **Logger**: Usar `@logger.channel.{module}`
- [ ] **Config**: No hardcodear valores, usar `ConfigFactory`

### 8. CI/CD y Security Scanning

- [ ] **Trivy config**: Las claves `skip-dirs`/`skip-files` van bajo el bloque `scan:` (NO al nivel raíz)
- [ ] **Exclusiones**: vendor/, web/core/, web/modules/contrib/ y node_modules/ DEBEN estar en `scan.skip-dirs`
- [ ] **Verificar logs**: Confirmar que "Number of language-specific files" no incluye archivos de terceros
- [ ] **Smoke tests**: Deben tener fallback SSH/Drush cuando `PRODUCTION_URL` no está disponible
- [ ] **Secrets en workflows**: Validar existencia antes de usar; emitir `::warning::` si falta, no `::error::`

### 9. Page Builder Templates (Config Entities YAML)

- [ ] **preview_image obligatorio**: Todo YAML DEBE incluir `preview_image: '/modules/custom/jaraba_page_builder/images/previews/{id-con-guiones}.png'`
- [ ] **PNG existente**: Verificar que el PNG referenciado existe en disco. Si falta, crear placeholder (800x600)
- [ ] **preview_data rico**: Templates verticales DEBEN incluir arrays con 3+ items del dominio (features, testimonials, faqs, stats, plans, gallery)
- [ ] **Cabeceras Drupal**: Todo YAML DEBE tener `langcode: es`, `status: true`, `dependencies: {}`
- [ ] **fields_schema coherente**: Los campos en `fields_schema` DEBEN coincidir con las variables usadas en el Twig template
- [ ] **Categoría correcta**: Usar machine names (`hero`, `cta`, `content`, `agroconecta`, etc.), NO labels en español
- [ ] **Tildes en descripciones**: Usar `Sección`, no `Seccion`; `métricas`, no `metricas`
- [ ] **Update hook**: Tras modificar YAMLs de `config/install/`, crear update hook para resync en BD activa
- [ ] **Validación YAML**: Verificar sintaxis con `python3 -c "import yaml; yaml.safe_load(open('file.yml'))"`

### 10. Drupal 10+ Entity Definition Updates

- [ ] **NO usar `applyUpdates()`**: Fue eliminado en Drupal 10+. Usar llamadas explícitas
- [ ] **Instalar campo**: `$updateManager->installFieldStorageDefinition($name, $entity_type, $module, $definition)`
- [ ] **Actualizar campo**: `$updateManager->updateFieldStorageDefinition($definition)` (solo si el tipo no cambia)
- [ ] **Verificar antes**: Siempre comprobar con `getFieldStorageDefinition()` si el campo ya existe y su tipo

### 11. Seguridad en Rutas API (CSRF)

- [ ] **Rutas API via fetch()**: Usar `_csrf_request_header_token: 'TRUE'` (NO `_csrf_token: 'TRUE'`)
- [ ] **Token en JS**: Obtener de `Drupal.url('session/token')`, cachear en variable, enviar como header `X-CSRF-Token`
- [ ] **Endpoint publico**: Las rutas publicas usan `_access: 'TRUE'`, NO necesitan CSRF
- [ ] **Format requerido**: Anadir `_format: 'json'` en requirements si la ruta devuelve JSON
- [ ] **Query param**: El JS DEBE enviar `?_format=json` en la URL si la ruta requiere `_format`
- [ ] **NO mezclar**: Una ruta NO debe tener `_csrf_token` y `_csrf_request_header_token` a la vez

### 12. Seguridad en Templates Twig (XSS)

- [ ] **Contenido usuario**: Usar `|safe_html` para campos de texto rico editados por usuarios
- [ ] **NUNCA `|raw`**: Solo permitido para JSON-LD schema, CSS/JS inline auto-generado por PHP
- [ ] **Emails HTML**: Escapar datos de usuario con `Html::escape()` antes de interpolar en HTML
- [ ] **TranslatableMarkup**: Cast `(string)` al asignar `$this->t()` a variables de render array
- [ ] **URLs internas**: SIEMPRE usar `Url::fromRoute()`, nunca hardcodear rutas como `"/user/$uid/edit"`
- [ ] **Role checks**: Verificar roles especificos (`candidate`, `jobseeker`), NUNCA solo `authenticated`

### 13. PWA y Meta Tags

- [ ] **Apple meta tag**: `<meta name="apple-mobile-web-app-capable" content="yes">` SIEMPRE presente
- [ ] **Standard meta tag**: `<meta name="mobile-web-app-capable" content="yes">` SIEMPRE presente
- [ ] **No eliminar**: Ambos tags son necesarios; iOS Safari ignora el estandar, Chrome ignora el Apple

### 14. Entidades Append-Only (Registros Inmutables)

- [ ] **Sin form handlers de edicion**: No definir `edit` ni `delete` en la anotacion `@ContentEntityType` handlers `form`
- [ ] **AccessControlHandler restrictivo**: Denegar `update` y `delete` en `checkAccess()`. Solo permitir `create` y `view`
- [ ] **Sin ruta de eliminacion**: No definir link templates para `delete-form` ni `edit-form`
- [ ] **Creacion via servicio**: Las entidades se crean programaticamente desde un servicio, no desde formularios de usuario
- [ ] **Campos inmutables**: Una vez creada la entidad, ningun campo puede modificarse. Sin `EntityChangedTrait`
- [ ] **Ejemplo**: `SeasonalChurnPrediction` — predicciones estacionales que se crean y consultan, nunca se editan

### 15. Config Seeding via Update Hooks (Datos Iniciales)

- [ ] **YAML como fuente**: Los archivos `config/install/*.yml` almacenan datos como arrays PHP nativos, NO como JSON strings
- [ ] **Update hook para crear entidades**: El `update_hook` lee YAML con `Yaml::decode()`, codifica campos complejos con `json_encode()`, y crea entidad via `Entity::create()->save()`
- [ ] **Idempotencia**: Siempre verificar existencia antes de crear: `$storage->loadByProperties(['field' => $value])`
- [ ] **Campos JSON en string_long**: Los getters de la entidad DEBEN retornar `json_decode($value, TRUE) ?? []`
- [ ] **No depender de config import**: Los YAMLs de `config/install/` solo se procesan durante la instalacion del modulo, no en config:import
- [ ] **Ejemplo**: `jaraba_customer_success.retention_profile.agroconecta.yml` → `update_10001()` lee YAML y crea `VerticalRetentionProfile`

### 16. Booking API & Entity Field Mapping

- [ ] **Campos exactos**: Los nombres en `$storage->create([...])` DEBEN coincidir con `baseFieldDefinitions()`. No usar nombres del request JSON
- [ ] **Mapeo explicito**: request `datetime` → entity `booking_date`, request `service_id` → entity `offering_id`
- [ ] **Owner via uid**: Usar `uid` para el owner de la entidad (EntityOwnerTrait), no inventar campos `client_id`
- [ ] **Client data**: Rellenar `client_name`, `client_email`, `client_phone` desde el user cargado si no vienen en el request
- [ ] **Price desde offering**: Campos requeridos como `price` se copian del `ServiceOffering`, no se dejan vacios
- [ ] **meeting_url post-save**: Si la URL depende del entity ID, guardar primero, luego set+save la URL

### 17. State Machine con Status Granulares

- [ ] **Valores exactos**: Los status en controllers/cron/hooks DEBEN coincidir con `allowed_values` de la entidad
- [ ] **Mapeo generico→especifico**: Si API recibe `cancelled`, mapear a `cancelled_client`/`cancelled_provider` segun rol
- [ ] **Detection en hooks**: Usar `str_starts_with($status, 'cancelled_')` para detectar cualquier cancelacion
- [ ] **Role enforcement**: Solo providers pueden `confirmed`, `completed`, `no_show`. Validar en controlador
- [ ] **Owner ID**: Usar `$entity->getOwnerId()` (EntityOwnerTrait), no `$entity->get('client_id')->target_id`

### 18. Cron Idempotency con Flags de Notificacion

- [ ] **Filter por flag**: `->condition($flag, 0)` en la query para excluir ya enviados
- [ ] **Marcar flag**: `$entity->set($flag, TRUE)` tras enviar exitosamente
- [ ] **Guardar entidad**: `$entity->save()` inmediatamente despues de marcar el flag
- [ ] **Flag por ventana**: Cada ventana temporal (24h, 1h) tiene su propio campo flag boolean
- [ ] **Default FALSE**: Los campos flag DEBEN tener `setDefaultValue(FALSE)` en `baseFieldDefinitions()`

### 19. Cifrado Server-Side (AES-256-GCM)

- [ ] **Algoritmo**: Usar `openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag)` — NUNCA AES-CBC ni modos sin autenticacion
- [ ] **IV aleatorio**: Generar 12 bytes aleatorios por mensaje con `random_bytes(12)`. NUNCA reutilizar IV con la misma clave
- [ ] **Tag de autenticacion**: 16 bytes. Almacenar concatenado: `$iv . $tag . $ciphertext` en columna MEDIUMBLOB
- [ ] **Derivacion de clave**: Usar `sodium_crypto_pwhash()` (Argon2id) desde env var `JARABA_PMK`. Cache por tenant_id en propiedad del servicio
- [ ] **NUNCA almacenar clave**: La clave derivada NO se guarda en BD, config, ni logs. Solo existe en memoria durante el request
- [ ] **DTO readonly**: Los datos descifrados se encapsulan en un DTO `readonly` que se descarta tras la respuesta HTTP

### 20. Autenticacion WebSocket

- [ ] **Auth en onOpen()**: Validar JWT (query string `?token=xxx`) o session cookie en el handshake HTTP inicial
- [ ] **Adjuntar identidad**: Tras validar, adjuntar `user_id` y `tenant_id` al objeto `ConnectionInterface`
- [ ] **Cerrar invalidos**: Conexiones sin auth valido se cierran inmediatamente con codigo 4401
- [ ] **ConnectionManager**: Mantener indices `SplObjectStorage` para busqueda rapida por user_id y tenant_id
- [ ] **Heartbeat**: Implementar ping/pong cada 30 segundos para detectar conexiones muertas

### 21. Custom Schema Tables con DTOs

- [ ] **Cuando usar**: Tablas que requieren MEDIUMBLOB, VARBINARY, BIGSERIAL o alto volumen de escrituras (>1000/min)
- [ ] **hook_schema()**: Definir tabla en `.install` con indices apropiados (conversation_id, tenant_id, created_at)
- [ ] **DTO factory**: `DTO::fromRow($row)` para crear desde resultado de query, `->toArray()` para serializar
- [ ] **Servicio CRUD**: El servicio maneja queries via `\Drupal::database()` o inyeccion de `@database`
- [ ] **Foreign keys**: Mantener relaciones con entidades via columnas de referencia (conversation_id → entity.id)
- [ ] **readonly class**: Los DTOs DEBEN ser `final readonly class` para inmutabilidad en memoria

---

## 📁 Referencias a Workflows

Para guías detalladas, consultar:

- `/scss-estilos` - SCSS y variables inyectables
- `/i18n-traducciones` - Internacionalización
- `/sdc-components` - SDC con Compound Variants
- `/drupal-custom-modules` - Content Entities

---

## 📋 Checklist Rápido (Copiar a cada PR)

```markdown
### Pre-commit Checklist
- [ ] i18n: Textos con `{% trans %}` / `$this->t()`
- [ ] SCSS: No CSS directo, variables `var(--ej-*)`
- [ ] Colores: Paleta 7 colores Jaraba + `color-mix()` para variantes
- [ ] Iconos: `jaraba_icon('cat', 'name', {opts})` — sin emojis Unicode
- [ ] SDC: component.yml + twig + scss
- [ ] Compilado: `npm run build` + `drush cr`
- [ ] CI/CD: trivy.yaml `scan.skip-dirs` correctos, smoke tests con fallback
- [ ] Page Builder: `preview_image` en YAML, PNG existe, `preview_data` rico
- [ ] Entity updates: NO `applyUpdates()`, usar install/update explícito
- [ ] CSRF API: `_csrf_request_header_token` en rutas API, token via header
- [ ] Twig XSS: `|safe_html` en contenido usuario, nunca `|raw`
- [ ] PWA: Ambos meta tags (apple + standard) presentes
- [ ] Roles: Verificar roles especificos, nunca solo `authenticated`
- [ ] Append-only: Entidades inmutables sin form edit/delete, AccessControlHandler restrictivo
- [ ] Config seeding: Update hook con `Yaml::decode()` + `json_encode()` + idempotencia
- [ ] API field mapping: Campos en `create()` coinciden con `baseFieldDefinitions()`
- [ ] State machine: Status values coinciden con `allowed_values`, cancelled generico mapeado a rol
- [ ] Cron flags: Notificaciones filtran por flag NOT sent, marcan TRUE tras enviar
- [ ] Cifrado: AES-256-GCM con IV 12 bytes aleatorio, tag 16 bytes, clave Argon2id desde env var
- [ ] WebSocket: Auth en onOpen(), JWT o session, cerrar conexiones invalidas
- [ ] Custom tables: DTOs readonly con factory fromRow(), servicio CRUD via @database
```

---

*Ultima actualizacion: 2026-02-20 (v3.3 — Cifrado AES-256-GCM, WebSocket Auth, Custom Schema DTOs)*
