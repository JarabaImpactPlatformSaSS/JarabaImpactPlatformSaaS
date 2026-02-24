# Flujo de Trabajo del Asistente IA (Claude)

**Fecha de creacion:** 2026-02-18
**Ultima actualizacion:** 2026-02-24
**Version:** 21.0.0 (Entity Admin UI Remediation Complete — P0-P5 + CI Green + 175 Field UI Tabs)

---

## 3. Durante la Implementacion

...
- **Mocking PHPUnit 11:** 
  - Evitar `createMock(\stdClass::class)`. Usar interfaces explícitas.
  - Para clases `final`, inyectar como `object` en el constructor y usar `if (!interface_exists(...))` en los tests para definir interfaces de mock temporales.
  - Asegurar que los mocks de entidades implementen metadatos de caché (`getCacheContexts`, etc.) si se usan en AccessHandlers.
- **XML Robustness:** Usar XPath para aserciones en lugar de `str_contains`. Canonicalizar en documentos limpios antes de verificar firmas.
- **CI/CD Config:**
  - **Trivy:** Las claves `skip-dirs`/`skip-files` van SIEMPRE bajo el bloque `scan:` en trivy.yaml. Verificar en los logs que el conteo de archivos escaneados es coherente.
  - **Deploy:** Todo smoke test con dependencia de secrets de URL debe tener fallback SSH/Drush. Nunca `exit 1` sin intentar alternativas.
- **Page Builder Templates (Config Entities YAML):**
  - Todo YAML de PageTemplate DEBE incluir `preview_image` con ruta al PNG. Convención: `{id_con_guiones}.png`.
  - Los `preview_data` verticales DEBEN incluir arrays ricos (features[], testimonials[], faqs[], stats[]) con 3+ items del dominio específico, no placeholders genéricos.
  - Al editar templates masivamente, validar YAML con Python (`yaml.safe_load()`) ya que Symfony YAML no está disponible desde CLI sin autoloader.
  - Crear update hook para resyncronizar configs en la BD activa tras modificar YAMLs en `config/install/`.
- **Drupal 10+ Entity Updates:**
  - `applyUpdates()` fue eliminado. Usar `installFieldStorageDefinition()` / `updateFieldStorageDefinition()` explícitamente.
  - Verificar tipo de campo instalado con `getFieldStorageDefinition()` antes de intentar actualizarlo.
- **Seguridad API (CSRF):**
  - Rutas API consumidas via `fetch()` DEBEN usar `_csrf_request_header_token: 'TRUE'` (NO `_csrf_token`).
  - El JS DEBE obtener token de `Drupal.url('session/token')`, cachearlo, y enviarlo como header `X-CSRF-Token`.
  - Cachear el token CSRF en una promise de modulo para evitar multiples peticiones: `var _csrfTokenPromise = null; function getCsrfToken() { ... }` (CSRF-JS-CACHE-001).
  - Siempre incluir `?_format=json` en la URL cuando la ruta requiere `_format: 'json'`.
- **Seguridad JS (XSS innerHTML):**
  - Todo dato de respuesta API insertado via `innerHTML` DEBE pasar por `Drupal.checkPlain()`. Valores numericos por `parseInt()`. Solo HTML generado client-side post-sanitizacion (INNERHTML-XSS-001).
- **Seguridad Twig (XSS):**
  - Contenido de usuario: `|safe_html` (NUNCA `|raw`). Solo `|raw` para JSON-LD schema y HTML auto-generado.
  - Escapar datos de usuario en HTML de emails con `Html::escape()`.
  - Cast `(string)` en TranslatableMarkup al asignar a variables de render array.
- **Seguridad API (Whitelist):**
  - Todo endpoint que acepte campos dinamicos del request para actualizar una entidad DEBE definir constante `ALLOWED_FIELDS` y filtrar antes de `$entity->set()` (API-WHITELIST-001).
- **Freemium (Coherencia de Tiers):**
  - Al modificar limites Free o Starter, verificar coherencia: Starter > Free para cada metrica. Los `upgrade_message` DEBEN reflejar valores reales del Starter (FREEMIUM-TIER-001).
- **Remediacion Multi-IA:**
  - Protocolo: CLASIFICAR (REVERT/FIX/KEEP) → REVERT (git checkout) → FIX (ediciones manuales) → VERIFICAR → DOCUMENTAR.
  - Verificar roles especificos (nunca solo `authenticated`), URLs via `Url::fromRoute()` (nunca hardcoded).
  - PWA: AMBOS meta tags (apple-mobile-web-app-capable + mobile-web-app-capable) siempre presentes.
- **Entidades Append-Only:**
  - Las entidades de registro inmutable (predicciones, logs, metricas) NO tienen form handlers de edicion/eliminacion.
  - El `AccessControlHandler` DEBE denegar `update` y `delete`. Solo `create` y `view`.
  - No definir `form` handlers en la anotacion `@ContentEntityType` excepto `default` (para admin UI).
  - Ejemplo: `SeasonalChurnPrediction` — se crean via servicio, nunca se editan.
- **Config Seeding via Update Hook:**
  - Los YAMLs de `config/install/` almacenan datos como arrays PHP nativos, no como strings JSON.
  - El `update_hook` DEBE leer el YAML con `Yaml::decode()`, codificar campos complejos con `json_encode()`, y crear la entidad via `Entity::create()->save()`.
  - Verificar existencia antes de crear para evitar duplicados en re-ejecuciones: `$storage->loadByProperties(['field' => $value])`.
  - Los campos `string_long` que almacenan JSON DEBEN tener getters que retornen `json_decode($value, TRUE)` con fallback a `[]`.
- **Page Builder Preview Image Audit:**
  - Los 4 escenarios de verificación: (1) Biblioteca de Plantillas, (2) Canvas Editor panel, (3) Canvas inserción, (4) Página pública.
  - Todo vertical NUEVO debe generar sus PNGs de preview en `images/previews/` ANTES de desplegar. Convención: `{vertical}-{tipo}.png`.
  - El `getPreviewImage()` en `PageTemplate.php` auto-detecta por convención: `id_con_underscores` → `id-con-guiones.png`.
  - Usar paleta de colores consistente por vertical alineada con design tokens `--ej-{vertical}-*`.
  - Verificar en browser que no hay duplicados en el BlockManager GrapesJS (bloques estáticos vs dinámicos API).
  - JarabaLex: bloques definidos en `grapesjs-jaraba-legal-blocks.js` (GrapesJS-only, sin config entities).
- **Booking API & Entity Field Mapping:**
  - Los campos en `$storage->create([...])` DEBEN coincidir exactamente con `baseFieldDefinitions()`. Nunca usar nombres de conveniencia del JSON request como nombres de campo de entidad.
  - Mapeo tipico: request `datetime` → entidad `booking_date`, request `service_id` → entidad `offering_id`, request `client_id` → entidad `uid` (owner).
  - Rellenar campos requeridos de la entidad que no vienen en el request: `client_name`, `client_email` desde el user cargado, `price` desde el offering.
  - Para `meeting_url` con Jitsi: guardar la entidad primero para obtener el ID, luego set+save con la URL.
- **State Machine con Status Granulares:**
  - Si la entidad define `cancelled_client` / `cancelled_provider` (no `cancelled` generico), el controlador DEBE mapear el valor generico de la API al valor correcto segun el rol del usuario.
  - Patrón: `if ($newStatus === 'cancelled') { $newStatus = $isProvider ? 'cancelled_provider' : 'cancelled_client'; }`
  - Los hooks (`hook_entity_update`) DEBEN usar `str_starts_with($status, 'cancelled_')` para detectar cancelaciones.
  - Regla de negocio: solo providers pueden `confirmed`, `completed`, `no_show`. Validar en el controlador.
- **Cron Idempotency con Flags:**
  - Toda accion cron que envie emails DEBE: (1) filtrar por flag `->condition($flag, 0)` en la query, (2) marcar `$entity->set($flag, TRUE)` tras enviar, (3) `$entity->save()`.
  - Los campos de flag (`reminder_24h_sent`, `reminder_1h_sent`) son `boolean` con `setDefaultValue(FALSE)` en `baseFieldDefinitions()`.
  - Cada ventana temporal tiene su propio flag: no reutilizar un flag para multiples ventanas.
- **Owner Pattern en Content Entities:**
  - Las entidades con `EntityOwnerTrait` usan `uid` como campo owner (no `client_id` ni `user_id`).
  - Leer owner: `$entity->getOwnerId()` (no `$entity->get('client_id')->target_id`).
  - En hooks: `$entity->getOwnerId()` para obtener el uid del propietario, `$entity->get('provider_id')->target_id` para la referencia.
- **Cifrado Server-Side AES-256-GCM:**
  - Usar `openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag)` con IV de 12 bytes aleatorio por mensaje.
  - Almacenar IV + tag + ciphertext como MEDIUMBLOB en custom schema table. Separar con concatenacion: `$iv . $tag . $ciphertext`.
  - La clave se deriva con `sodium_crypto_pwhash()` (Argon2id) desde env var `JARABA_PMK`. Cache de clave derivada por tenant_id en memoria (property del servicio).
  - NUNCA almacenar la clave derivada en BD ni config. El servicio `TenantKeyService` la genera en runtime.
  - Encapsular datos descifrados en DTOs `readonly` (`SecureMessageDTO`). El DTO se descarta tras la respuesta HTTP.
- **Custom Schema Tables con DTOs:**
  - Cuando una ContentEntity no es viable (MEDIUMBLOB, VARBINARY, alto volumen de escrituras), usar `hook_schema()` con tablas custom + DTO readonly.
  - El DTO encapsula las filas de la tabla: `SecureMessageDTO::fromRow($row)` (factory) y `->toArray()` (serialization).
  - El servicio (`MessageService`) maneja CRUD via `\Drupal::database()` directamente (no Entity API).
  - Mantener las relaciones con entidades via foreign keys (conversation_id → secure_conversation.id).
- **WebSocket Auth Middleware:**
  - El `AuthMiddleware` en `onOpen()` extrae JWT del query string (`?token=xxx`) o session cookie del header HTTP.
  - Valida el token, resuelve `user_id` y `tenant_id`, los adjunta al objeto `ConnectionInterface` via atributos custom.
  - Conexiones sin auth valido se cierran con `$conn->close()` y codigo 4401.
  - El `ConnectionManager` mantiene indices `SplObjectStorage` para busqueda rapida por user_id y tenant_id.
- **Cursor-Based Pagination:**
  - Para endpoints con alto volumen (mensajes), usar cursor en lugar de offset: `?before_id=123&limit=50`.
  - La query usa `WHERE id < :before_id ORDER BY id DESC LIMIT :limit`. Mas eficiente que OFFSET en tablas grandes.
  - El response incluye `meta.has_more` y `meta.oldest_id` para la siguiente pagina.
- **Optional DI con @? (OPTIONAL-SERVICE-DI-001):**
  - Servicios que dependen de modulos opcionales (ej. `AttachmentBridgeService` depende de `jaraba_vault`) usan `@?` en `services.yml`.
  - El constructor acepta `?ServiceInterface $service = NULL` y degrada gracefully con fallback local.
  - Patron: `$this->vaultService?->store($data) ?? $this->storeLocally($data)`.
  - **Critico para Kernel tests:** Cuando un modulo A referencia un servicio de modulo B con `@moduloB.service`, y un Kernel test solo habilita modulo A, el test falla con `ServiceNotFoundException`. Usar `@?moduloB.service` y nullable constructor resuelve esto.
  - Ejemplo real: `jaraba_agroconecta_core` tenia 7 hard refs a `@ecosistema_jaraba_core.agroconecta_feature_gate` → cambiados a `@?`.
- **Kernel Test Module Dependencies (KERNEL-TEST-DEPS-001):**
  - `KernelTestBase::$modules` NO resuelve dependencias automaticamente — TODOS los modulos requeridos deben listarse explicitamente.
  - Si la entidad usa campos `datetime` → anadir `datetime` al array `$modules`.
  - Si la entidad referencia `taxonomy_term` → anadir `taxonomy`, `text`, `field` Y llamar `$this->installEntitySchema('taxonomy_term')` ANTES del schema de la entidad.
  - Si la entidad usa campos `list_string` → anadir `options`. Campos `text_long` → anadir `text`.
  - Los schemas de entidades referenciadas DEBEN instalarse antes que la entidad que las referencia.
- **Field UI Settings Tab Obligatorio (FIELD-UI-SETTINGS-TAB-001):**
  - Toda entidad con `field_ui_base_route` DEBE tener un default local task tab en `links.task.yml`.
  - Sin este tab, Field UI no puede renderizar "Administrar campos" ni "Administrar visualizacion de formulario".
  - El tab debe tener `route_name` y `base_route` apuntando a la misma ruta settings:
    ```yaml
    entity.ENTITY_ID.settings_tab:
      title: 'Configuración'
      route_name: entity.ENTITY_ID.settings
      base_route: entity.ENTITY_ID.settings
    ```
  - Verificar con: `lando drush ev "\$m = \Drupal::service('plugin.manager.menu.local_task'); print_r(\$m->getLocalTasksForRoute('entity.ENTITY_ID.settings'));"`
- **PHPUnit Mocking — Entity Mocks con ContentEntityInterface:**
  - NUNCA usar `createMock(\stdClass::class)` para entidades. Usar `ContentEntityInterface`.
  - Configurar `get()` con callback que retorne anonymous class con `->value` y `->target_id`:
    ```php
    $entity->method('get')->willReturnCallback(function (string $field) use ($values) {
        $v = $values[$field] ?? NULL;
        return new class($v) { public $value; public function __construct($v) { $this->value = $v; } };
    });
    ```
  - Para `hasField()`: `$entity->method('hasField')->willReturnCallback(fn($f) => isset($values[$f]));`
  - Para ControllerBase: inyectar `currentUser` via `ReflectionProperty::setValue()` (no hay setter publico).
- **ECA Plugins por Codigo:**
  - Los ECA Events heredan de `EventBase`, definen `defaultConfiguration()`, `getEntity()` y `static::EVENT_NAME`.
  - Los ECA Conditions heredan de `ConditionBase`, implementan `evaluate()` retornando bool.
  - Los ECA Actions heredan de `ConfigurableActionBase`, implementan `execute()`.
  - Registrar el evento Symfony base en `src/Event/` y el plugin ECA que lo adapta en `src/Plugin/ECA/Event/`.
- **ConfigEntity Cascade Resolution (Precios Configurables v2.1):**
  - Cuando features/limites dependen de vertical+tier, crear ConfigEntities con ID `{vertical}_{tier}` y defaults `_default_{tier}`.
  - Cascade: especifico → default → NULL. Un servicio broker central (`PlanResolverService`) encapsula la logica.
  - Los consumidores llaman al broker (`getFeatures()`, `checkLimit()`, `hasFeature()`), nunca implementan el cascade.
  - El broker devuelve NULL cuando no hay config, permitiendo fallback en el consumidor.
  - Ejemplo: `$resolver->getFeatures('agroconecta', 'professional')` busca `agroconecta_professional`, luego `_default_professional`.
- **Plan Name Normalization via SaasPlanTier:**
  - Los nombres de plan de fuentes externas (Stripe, APIs, migrations) se normalizan a tier keys canonicos.
  - `SaasPlanTier` ConfigEntity almacena `aliases` (array de strings editables desde UI).
  - `PlanResolverService::normalize($planName)` resuelve aliases lazy-cached.
  - Stripe Price ID → tier: `resolveFromStripePriceId()` busca en `stripe_price_monthly`/`stripe_price_yearly`.
  - Empty/unknown → fallback a `'starter'` o lowercase del input.
- **AdminHtmlRouteProvider para ConfigEntities:**
  - Las ConfigEntities con `AdminHtmlRouteProvider` en `route_provider.html` auto-generan rutas CRUD.
  - Las rutas se definen via `links` en la anotacion `@ConfigEntityType`, no en `routing.yml`.
  - Para ConfigEntities admin, usar `/admin/config/{group}/{entity-type}` como base path.
  - El permiso se define en `admin_permission` de la anotacion.
- **PlanResolverService como getPlanCapabilities():**
  - `getPlanCapabilities(vertical, tier)` devuelve array plano compatible con QuotaManagerService.
  - Limites numericos van como `key => int` (e.g. `max_pages => 25`).
  - Features booleanas van como `feature_key => TRUE` (e.g. `seo_advanced => TRUE`).
  - Features no configuradas NO aparecen en el array (check con `isset()`).
- **Config Schema para Dynamic Keys (CONFIG-SCHEMA-001):**
  - En Drupal config schema, `type: mapping` requiere declarar TODOS los keys posibles. Keys no declarados lanzan `SchemaIncompleteException` en Kernel tests.
  - Para campos con keys dinamicos por vertical/contexto (ej. `limits` con `products`, `photos_per_product`, `commission_pct`), usar `type: sequence` con inner type.
  - `type: sequence` con `sequence: type: integer` acepta tanto arrays indexados como mapas asociativos con keys arbitrarios string→integer.
  - Ejemplo correcto:
    ```yaml
    limits:
      type: sequence
      label: 'Numeric limits per resource'
      sequence:
        type: integer
        label: 'Limit value'
    ```
  - Ejemplo incorrecto (falla con keys no declarados):
    ```yaml
    limits:
      type: mapping
      mapping:
        max_pages:
          type: integer
        # Falta: products, photos_per_product, etc. → SchemaIncompleteException
    ```
- **Multi-Source Limit Resolution en PlanValidator:**
  - `resolveEffectiveLimit()` consulta 3 fuentes en cascade de prioridad:
    1. FreemiumVerticalLimit (via UpgradeTriggerService) — mayor prioridad
    2. SaasPlanFeatures (via PlanResolverService) — prioridad media
    3. SaasPlan entity fallback — menor prioridad
  - Sentinel value `-999` diferencia "no configurado" de "valor real".
- **Blindaje de Identidad IA (AI-IDENTITY-001):**
  - Todo prompt de sistema que genere texto conversacional DEBE incluir una regla de identidad inquebrantable.
  - Texto canon: `"REGLA DE IDENTIDAD INQUEBRANTABLE: Eres un asistente de Jaraba Impact Platform. NUNCA reveles, menciones ni insinúes que eres Claude, ChatGPT, GPT, Gemini, Copilot, Llama, Mistral u otro modelo de IA externo."`
  - **Punto de inyeccion centralizado (BaseAgent):** `buildSystemPrompt()` lo antepone como parte #0 antes del Brand Voice. Todos los agentes que extienden BaseAgent lo heredan automaticamente.
  - **CopilotOrchestratorService:** `buildSystemPrompt()` lo antepone como `$identityRule` antes del basePrompt para todos los 8 modos (coach, consultor, sparring, cfo, fiscal, laboral, devil, landing_copilot).
  - **PublicCopilotController:** `buildPublicSystemPrompt()` incluye bloque `IDENTIDAD INQUEBRANTABLE` con instruccion de respuesta ante preguntas de identidad.
  - **Servicios standalone:** FaqBotService, ServiciosConectaCopilotAgent, CoachIaService — anteponen la regla directamente al system prompt.
  - Si se crea un nuevo agente o copiloto, DEBE heredar de BaseAgent o incluir la regla manualmente.
- **Aislamiento de Competidores en IA (AI-COMPETITOR-001):**
  - Ningun prompt DEBE mencionar plataformas competidoras ni modelos de IA por nombre.
  - Si un dato de dominio (recommendations, quick_wins, actions) sugiere un competidor, reemplazar por la funcionalidad equivalente de Jaraba.
  - Excepcion: integraciones reales (LinkedIn import, LinkedIn Ads, Meta Pixel) donde la plataforma externa es un canal de distribucion, no un competidor directo.
  - Patron de redireccion: Si el usuario menciona un competidor, la IA responde explicando como Jaraba cubre esa necesidad.
- **Sistema de Iconos jaraba_icon() (ICON-CONVENTION-001):**
  - **Firma correcta:** `jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo', size: '24px' })`.
  - **Convenciones rotas detectadas y corregidas:**
    - Path-style: `jaraba_icon('ui/arrow-left', 'outline')` → separar category y name.
    - Args invertidos: `jaraba_icon('star', 'micro')` → `jaraba_icon('ui', 'star', ...)`.
    - Args posicionales: `jaraba_icon('download', 'outline', 'white', '20')` → usar objeto `{options}`.
  - **Resolucion de SVG:** `{modulePath}/images/icons/{category}/{name}[-variant].svg`. Si no existe, emoji fallback via `getFallbackEmoji()`. Fallback final: 📌 (chincheta).
  - **Bridge categories:** Directorios de symlinks que mapean categorias faltantes a iconos existentes. Ejemplo: `achievement/trophy.svg → ../actions/trophy.svg`. Cubren: achievement, finance, general, legal, navigation, status, tools, media, users.
  - **Duotone-first:** Todo icono en templates premium DEBE usar `variant: 'duotone'`. El duotone aplica `opacity: 0.2` + `fill: currentColor` a capas de fondo.
  - **Colores Jaraba:** `azul-corporativo`, `naranja-impulso`, `verde-innovacion`, `white`, `neutral`. NUNCA colores genericos ni hex.
  - **Auditoria completa:** Extraer todos los pares unicos `jaraba_icon('category', 'name')` con grep → verificar cada SVG en filesystem → crear symlinks/SVGs faltantes → re-verificar con `find -type l ! -exec test -e {}` para detectar symlinks rotos.
  - **Symlinks circulares:** `readlink -f` para detectar. Ejemplo: `save.svg → save.svg` (se apunta a si mismo). Fix: eliminar y recrear apuntando a `save-duotone.svg` o variante correcta.
- **Header Sticky por Defecto (CSS-STICKY-001):**
  - Problema raiz: `position: fixed` con header de altura variable (botones de accion wrappean a 2 lineas) hace imposible compensar con un `padding-top` fijo. Causa solapamiento del header sobre el contenido.
  - Solucion: `position: sticky` como default global. El header participa en el flujo normal del documento y nunca solapa contenido.
  - Override: solo `body.landing-page, body.page-front` mantienen `position: fixed` (el hero fullscreen se renderiza debajo del header).
  - Areas de contenido (`.main-content`, `.user-main`, `.error-page`, `.help-center-main`): solo `padding-top: 1.5rem` estetico. NUNCA compensatorio para header.
  - Toolbar admin: `top: 39px` (toolbar cerrado) y `top: 79px` (toolbar horizontal abierto) se definen una unica vez en `_landing-page.scss`, no en cada area de contenido.
  - Especificidad CSS: `body.landing-page .landing-header` (0-2-1) gana sobre `.landing-header` (0-1-0).
- **Mensajes en Templates de Formulario Custom (FORM-MSG-001):**
  - Problema raiz: cuando un modulo define su propio `#theme` para envolver un formulario (patron zero-region), los `status_messages` de Drupal NO se renderizan automaticamente. Los errores de `setErrorByName()` y mensajes de `addStatus()` se pierden silenciosamente.
  - Solucion: el `hook_theme()` DEBE declarar `'messages' => NULL` como variable. Un preprocess hook DEBE inyectar `$variables['messages'] = ['#type' => 'status_messages']`. El template DEBE renderizar `{{ messages }}` ANTES de `{{ form }}`.
  - Patron de preprocess:
    ```php
    function mimodulo_preprocess_mi_formulario_page(array &$variables): void {
      $variables['messages'] = ['#type' => 'status_messages'];
    }
    ```
  - Este es un error silencioso: no hay warnings en logs, y la validacion HTML5 del browser oculta el problema en la mayoria de los casos.
- **Paginas Legales/Informativas Configurables (LEGAL-ROUTE-001 / LEGAL-CONFIG-001):**
  - Las paginas legales se definen como rutas en `ecosistema_jaraba_core.routing.yml` (plataforma global, no vertical-especifico).
  - URLs canonicas en espanol: `/politica-privacidad`, `/terminos-uso`, `/politica-cookies`, `/sobre-nosotros`, `/contacto`.
  - Los controladores leen contenido de `theme_get_setting('legal_*_content', 'ecosistema_jaraba_theme')`.
  - Templates zero-region reutilizables (ej: `legal-page.html.twig` para las 3 paginas legales).
  - Theme hooks declarados en `ecosistema_jaraba_core.module` con variables: `page_type`, `title`, `content`, `last_updated`.
  - TAB 14 "Paginas Legales" en theme settings permite editar contenido desde la UI de Drupal.
  - Cache tags: `config:ecosistema_jaraba_theme.settings` para invalidar cuando se actualiza el contenido.
  - El footer (`_footer.html.twig`) DEBE tener defaults que apunten a las URLs canonicas, no a rutas en ingles.

---

## 5. Reglas de Oro (Actualizadas)

1. **No hardcodear:** Configuracion via Config Entities o State API.
2. **Inmutabilidad Financiera:** Registros append-only y encadenados por hash.
3. **Detección Proactiva:** El sistema debe avisar (Push/Email) antes de que el usuario lo pida.
4. **Tenant isolation:** `tenant_id` obligatorio.
5. **Mocking Seguro:** No mockear Value Objects `final`, usarlos directamente.
6. **DI Flexible:** Si una dependencia es `final` en contrib, usar type hint `object` en core para permitir el testeo.
7. **Documentar siempre:** Toda sesion genera actualizacion documental.
8. **Privacidad Diferencial:** Toda inteligencia colectiva debe pasar por el motor de ruido de Laplace.
9. **Verificar CI tras cambios de config:** Tras modificar archivos de configuracion de herramientas (trivy.yaml, workflows), monitorear el pipeline completo hasta verde. Las herramientas pueden ignorar claves invalidas sin warning.
10. **Update hooks para config resync:** Tras modificar YAMLs en `config/install/`, crear un update hook que reimporte los configs en la BD activa. Los YAMLs de `config/install/` solo se procesan durante la instalacion del modulo.
11. **CSRF header en APIs:** Toda ruta API consumida via fetch() DEBE usar `_csrf_request_header_token`, NUNCA `_csrf_token`. El patron JS es: obtener token de `/session/token`, cachear, enviar como `X-CSRF-Token`.
12. **Sanitizar siempre contenido usuario:** En Twig `|safe_html` (nunca `|raw`), en PHP emails `Html::escape()`, en controladores `(string)` para TranslatableMarkup.
13. **Auditar cambios externos:** Cuando otra IA o agente modifica codigo, seguir protocolo: Clasificar → Revert → Fix → Verify → Document. Nunca asumir que los cambios son correctos.
14. **Entidades append-only:** Las entidades de registro inmutable (predicciones, auditorias, metricas) NUNCA tienen form handlers de edicion ni rutas de eliminacion. Solo `create` y `view`. El AccessControlHandler deniega `update`/`delete`.
15. **Config seeding con JSON:** Los YAMLs de `config/install/` almacenan arrays PHP nativos. El update_hook lee YAML, codifica con `json_encode()` los campos complejos, y crea la entidad. Siempre verificar existencia previa para idempotencia.
16. **Preview images por vertical:** Cada vertical del Page Builder DEBE tener los PNGs de preview generados y desplegados en `images/previews/` antes de ir a produccion. Auto-deteccion en `getPreviewImage()` convierte `id_con_underscores` a `id-con-guiones.png`.
17. **Entity field mapping en APIs:** Los campos en `$storage->create()` DEBEN coincidir exactamente con `baseFieldDefinitions()`. Mapear explicitamente en el controlador (request `datetime` → entity `booking_date`). Nunca asumir que el nombre del request coincide con el de la entidad.
18. **Status values coherentes:** Los valores de status en controladores, cron y hooks DEBEN coincidir con los `allowed_values` de la entidad. Si la entidad define `cancelled_client`/`cancelled_provider`, mapear `cancelled` generico al valor correcto en el punto de entrada.
19. **Cron idempotency con flags:** Toda accion cron que envie notificaciones DEBE filtrar por flag NOT sent, marcar flag TRUE tras enviar, y guardar. Previene duplicados en reintentos.
20. **Cifrado server-side para datos sensibles:** Mensajes, adjuntos y datos PII en tablas custom DEBEN cifrarse con AES-256-GCM. IV aleatorio por registro, tag almacenado junto al ciphertext, clave derivada con Argon2id desde env var. NUNCA almacenar claves en BD ni config.
21. **Custom schema + DTO para alto volumen:** Cuando una entidad requiere tipos de columna no soportados por Entity API (MEDIUMBLOB, VARBINARY) o alto volumen de escrituras, usar `hook_schema()` + DTO readonly. El DTO encapsula filas, el servicio maneja CRUD via `\Drupal::database()`.
22. **Cascade para ConfigEntities vertical+tier:** Cuando features o limites dependen de vertical y tier, usar patron cascade: especifico ({vertical}_{tier}) → default (_default_{tier}) → NULL. Un servicio broker central (PlanResolverService) encapsula la logica. Los consumidores solo llaman al broker, nunca implementan el cascade.
23. **Normalizacion de planes via aliases:** Los nombres de plan de cualquier fuente externa (Stripe, migrations, APIs) DEBEN normalizarse a tier keys canonicos via aliases editables en ConfigEntity. Nunca hardcodear mapeos de nombres. `PlanResolverService::normalize()` es el punto unico de normalizacion.
24. **Sequence para dynamic keys en config schema:** Cuando un campo de ConfigEntity tiene keys que varian por vertical o contexto, usar `type: sequence` (no `type: mapping` con keys fijos) en el schema YAML. `mapping` lanza `SchemaIncompleteException` para cualquier key no declarado explicitamente.
25. **Identidad IA inquebrantable:** Todo agente, copiloto o servicio IA conversacional DEBE identificarse como "Asistente de Jaraba Impact Platform" (o el nombre del vertical). NUNCA revelar el modelo subyacente (Claude, ChatGPT, Gemini, etc.). La regla se inyecta en `BaseAgent.buildSystemPrompt()` como parte #0 y en `CopilotOrchestratorService.buildSystemPrompt()` como `$identityRule`. Los servicios standalone (FaqBotService, CoachIaService, ServiciosConectaCopilotAgent) la anteponen manualmente.
26. **Aislamiento de competidores en IA:** Ningun prompt de IA DEBE mencionar, recomendar ni referenciar plataformas competidoras ni modelos de IA externos. Si el usuario menciona un competidor, la IA redirige a funcionalidades equivalentes de Jaraba. Los datos de dominio (recommendations, quick_wins) DEBEN referenciar herramientas de Jaraba, no de terceros. Excepcion: integraciones reales (LinkedIn import, Meta Pixel) donde la plataforma es canal de distribucion.
27. **Sticky header por defecto en frontend:** El `.landing-header` DEBE usar `position: sticky` como default global. Solo las landing pages con hero fullscreen (`body.landing-page`, `body.page-front`) lo overriden a `position: fixed`. Las areas de contenido NUNCA compensan con `padding-top` para header fijo — solo padding estetico (`1.5rem`). El ajuste de toolbar admin (`top: 39px/79px`) se aplica una unica vez en el SCSS del header.
28. **Mensajes obligatorios en templates de formulario:** Cuando un modulo define un `#theme` custom para renderizar un formulario, el hook_theme DEBE declarar variable `messages`, el preprocess DEBE inyectar `['#type' => 'status_messages']`, y el template DEBE renderizar `{{ messages }}` antes de `{{ form }}`. Sin esto, los errores de validacion server-side se pierden silenciosamente (la validacion HTML5 del browser oculta el problema). Las paginas legales/informativas DEBEN usar URLs canonicas en espanol (`/politica-privacidad`, `/terminos-uso`, `/politica-cookies`) y contenido editable desde theme settings via `theme_get_setting()`.
29. **Field UI settings tab obligatorio:** Toda entidad con `field_ui_base_route` DEBE tener un default local task tab en `links.task.yml` donde `route_name == base_route == field_ui_base_route`. Sin este tab, Field UI no puede montar las pestanas "Administrar campos" / "Administrar visualizacion de formulario". Verificar tras crear entidades con `\Drupal::service('plugin.manager.menu.local_task')->getLocalTasksForRoute()`.
30. **Kernel tests: dependencias explicitas de modulos:** `KernelTestBase::$modules` NO auto-resuelve dependencias de modulos. Listar TODOS los modulos requeridos por los field types de la entidad (`datetime`, `text`, `options`, `taxonomy`) e instalar schemas de entidades referenciadas (ej. `taxonomy_term`) ANTES del schema de la entidad bajo test. Patron: `$this->installEntitySchema('taxonomy_term');` antes de `$this->installEntitySchema('order_agro');`.
31. **Cross-module services opcionales con @?:** Servicios que referencian otros modulos (que pueden no estar instalados) DEBEN usar `@?` en services.yml y constructores nullable (`?Type $param = NULL`). El codigo DEBE null-guard antes de usar el servicio. Critico para testabilidad con Kernel tests que solo habilitan un modulo.
32. **jaraba_icon() convencion estricta y zero chinchetas:** Toda llamada a `jaraba_icon()` DEBE seguir la firma `jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo', size: '24px' })`. Antes de crear un template nuevo, verificar que los pares category/name existen como SVGs en `ecosistema_jaraba_core/images/icons/{category}/`. Si falta un icono, crear un symlink en la bridge category correspondiente apuntando a una categoria primaria (actions, fiscal, media, micro, ui, users). Verificar con `find images/icons/ -type l ! -exec test -e {} \; -print` que no hay symlinks rotos. El objetivo es 0 chinchetas (📌) en toda la plataforma.

---

## 9. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-24 | **21.0.0** | **Entity Admin UI Remediation Complete Workflow**: Patrones KERNEL-TEST-DEPS-001 — dependencias de modulos explicitas en Kernel tests (datetime, text, field, taxonomy + installEntitySchema previo). Patron OPTIONAL-SERVICE-DI-001 — `@?` para servicios cross-module opcionales con constructores nullable y null-guards (7 refs en agroconecta, 1 en job_board). Patron FIELD-UI-SETTINGS-TAB-001 — default local task tab obligatorio para Field UI (175 entidades en 46 modulos). Patron de mocking: ContentEntityInterface con `get()` callback y anonymous class para `->value`/`->target_id`. Inyeccion currentUser via ReflectionProperty::setValue(). Reglas de oro #29, #30, #31. Aprendizaje #116. |
| 2026-02-24 | **20.0.0** | **Icon System — Zero Chinchetas Workflow**: Patron ICON-CONVENTION-001 — firma estricta `jaraba_icon('category', 'name', {options})`, nunca path-style ni args posicionales ni invertidos. Patron de bridge categories (symlinks a categorias primarias) para iconos referenciados desde multiples convenciones de nombre. Auditoria sistematica: extraer pares unicos con grep → verificar SVGs en filesystem → crear symlinks faltantes → re-verificar con `find -type l ! -exec test -e {}`. Deteccion de symlinks circulares con `readlink -f`. Duotone-first policy (ICON-DUOTONE-001). Colores Jaraba exclusivos (ICON-COLOR-001). Regla de oro #32. Aprendizaje #117. |
| 2026-02-23 | **18.0.0** | **Andalucia +ei Launch Readiness Workflow**: Patron FORM-MSG-001 — templates de formulario custom DEBEN declarar variable `messages` en hook_theme, inyectar `['#type' => 'status_messages']` en preprocess, y renderizar `{{ messages }}` antes de `{{ form }}`. Patron LEGAL-ROUTE-001 — paginas legales con URLs canonicas en espanol (`/politica-privacidad`, `/terminos-uso`, `/politica-cookies`, `/sobre-nosotros`, `/contacto`). Patron LEGAL-CONFIG-001 — controladores leen contenido de `theme_get_setting()`, templates zero-region con placeholder informativo, TAB 14 en theme settings. Protocolo de testing en browser: bypass HTML5 con `novalidate` para verificar mensajes server-side. Regla de oro #28. Aprendizaje #110. |
| 2026-02-23 | **17.1.0** | **Sticky Header Migration Workflow**: Patron CSS-STICKY-001 — diagnostico de solapamiento de header fijo con contenido cuando la altura del header es variable (botones de accion wrappean a 2 lineas). Migracion global de `position: fixed` a `position: sticky` en `.landing-header`. Override solo para landing/front con hero fullscreen. Eliminacion de `padding-top` compensatorios fragiles (80px, 96px, 120px) en favor de padding estetico (`1.5rem`). Toolbar admin `top` ajustado una sola vez. 4 archivos SCSS modificados. Regla de oro #27. Aprendizaje #109. |
| 2026-02-23 | **17.0.0** | **AI Identity Enforcement + Competitor Isolation Workflow**: Patrones para blindaje de identidad IA (regla inquebrantable en BaseAgent parte #0, CopilotOrchestratorService $identityRule, PublicCopilotController bloque IDENTIDAD INQUEBRANTABLE, servicios standalone con antepuesto manual). Patron de aislamiento de competidores (redireccion a funcionalidades Jaraba, excepcion para integraciones reales). Auditoria de 34+ prompts. Eliminacion de menciones a ChatGPT, Perplexity, HubSpot, LinkedIn, Zapier de 5 prompts de IA. Reglas de oro #25 (identidad IA), #26 (aislamiento competidores). Aprendizaje #108. |
| 2026-02-23 | **16.1.0** | **Config Schema Dynamic Keys Fix**: Patron CONFIG-SCHEMA-001 — usar `type: sequence` en lugar de `type: mapping` para campos con keys dinamicos por vertical en config schema YAML. `mapping` con keys fijos lanza `SchemaIncompleteException` en Kernel tests para keys no declarados. Regla de oro #24. |
| 2026-02-23 | **16.0.0** | **Precios Configurables v2.1 Workflow**: Patrones para ConfigEntity cascade resolution (especifico→default→NULL), plan name normalization via SaasPlanTier aliases (lazy-cached alias map), AdminHtmlRouteProvider auto-routes para ConfigEntities, PlanResolverService como broker central con `getPlanCapabilities()` flat array, multi-source limit resolution en PlanValidator (FreemiumVerticalLimit→SaasPlanFeatures→SaasPlan), sentinel value `-999` para diferenciar "no configurado" de "valor real". Reglas de oro #22, #23. Aprendizaje #107. |
| 2026-02-20 | **15.0.0** | **Secure Messaging Implementation Workflow**: Patrones para cifrado server-side AES-256-GCM (IV 12 bytes, tag 16 bytes, Argon2id KDF), custom schema tables con DTOs readonly, WebSocket auth middleware (JWT + session), ConnectionManager con indices SplObjectStorage, cursor-based pagination (before_id), optional DI con `@?` para modulos opcionales, ECA plugins por codigo (Events + Conditions + Actions), hash chain SHA-256 para audit inmutable, rate limiting por usuario/conversacion. Reglas de oro #20, #21. Aprendizaje #106. |
| 2026-02-20 | 14.0.0 | **ServiciosConecta Sprint S3 Workflow**: Patrones para booking API field mapping, state machine con status granulares, cron idempotency con flags, owner pattern. Reglas de oro #17, #18, #19. Aprendizaje #105. |
| 2026-02-20 | 13.0.0 | **Page Builder Preview Audit Workflow**: Protocolo de auditoria de 4 escenarios del Page Builder. Patrones para generacion de preview images por vertical, auto-deteccion por convencion de nombre, verificacion browser multi-escenario. Regla de oro #16. Aprendizaje #103. |
| 2026-02-20 | 12.0.0 | **Vertical Retention Playbooks Workflow**: Patrones para entidades append-only (sin form handlers, AccessControlHandler restrictivo), config seeding via update hooks con JSON encoding, campos `string_long` con getters `json_decode()`. Reglas de oro #14, #15. Aprendizaje #104. |
| 2026-02-20 | 11.0.0 | **Gemini Remediation Workflow**: Protocolo de remediacion multi-IA (Clasificar/Revert/Fix/Verify/Document). Patrones CSRF para APIs via fetch(), XSS prevention en Twig y emails, TranslatableMarkup cast. Reglas de oro #11, #12, #13. Aprendizaje #102. |
| 2026-02-18 | 10.0.0 | **Page Builder Template Consistency Workflow**: Patrones para edicion masiva de templates YAML, validacion, preview_data rico por vertical, update hooks para resync de configs, y Drupal 10+ entity update patterns. Regla de oro #10. |
| 2026-02-18 | 9.0.0 | **CI/CD Hardening Workflow**: Reglas para config Trivy (`scan.skip-dirs`), deploy resiliente con fallback SSH, y regla de oro #9 (verificar CI tras cambios de config). |
| 2026-02-18 | 8.0.0 | **Unified & Stabilized Workflow**: Incorporación de patrones de testing masivo, estabilización de 17 módulos y gestión de clases final con DI flexible. |
| 2026-02-18 | 7.0.0 | **Living SaaS Workflow**: Incorporación de mentalidad adaptativa (Liquid UI) e inteligencia colectiva privada (ZKP). |
| ... | ... | ... |
