# Sprint 5 IA Clase Mundial --- Aprendizajes de Implementacion (100/100)

**Fecha:** 2026-02-27
**Autor:** Claude Opus 4.6
**Score:** 82/100 -> 93/100 -> 100/100

---

## Contexto

- 30 hallazgos de auditoria (20 originales + 10 nuevos detectados en re-auditoria)
- 15 items resueltos en Sprint 5 en una unica sesion de trabajo
- Tecnicas empleadas: background agents paralelos, edicion incremental de 50+ archivos
- Auditoria base: `docs/analisis/2026-02-27_Auditoria_IA_SaaS_Clase_Mundial_v1.md`
- Plan de implementacion: `docs/implementacion/2026-02-27_Plan_Implementacion_Elevacion_IA_Clase_Mundial_v1.md`

La elevacion de 82 a 100 se ejecuto en dos fases:
1. **Sprint 4** (82 -> 93): Resolvio 15 hallazgos criticos y altos
2. **Sprint 5** (93 -> 100): Resolvio los 15 hallazgos restantes para score perfecto

---

## Aprendizajes Clave

### 1. Migracion Gen 1 -> Gen 2 (HAL-AI-21)

**Problema:** Tres agentes copilot (Empleabilidad, Legal, ContentWriter) seguian usando `BaseAgent` Gen 1 sin soporte para model routing, herramientas nativas, ni observabilidad avanzada.

**Solucion:**
- Crear `Smart*Agent` extendiendo `SmartBaseAgent`, implementar `doExecute()` con `match()` dispatch
- Preservar TODA la logica de dominio del Gen 1 (keywords, modos, acciones especificas)
- Registrar en `services.yml` con los 10 argumentos estandar (ver SMART-AGENT-CONSTRUCTOR-001)
- Gen 1 se mantiene como deprecated pero activo --- NO eliminar (backwards compat para integraciones existentes)

**Patron de routing por modo:**
```php
protected function selectTier(string $mode): string {
    return match($mode) {
        'faq', 'simple', 'greeting' => 'fast',
        'coaching', 'analysis', 'review' => 'balanced',
        'strategy', 'draft', 'legal_analysis' => 'premium',
        default => 'balanced',
    };
}
```

**Leccion:** El `match()` de PHP 8.1+ es ideal para dispatch de modos. Mantener la tabla de routing explicita y documentada facilita el tuning posterior sin tocar logica de negocio.

### 2. SemanticCache en CopilotOrchestrator (HAL-AI-25)

**Problema:** El orquestador del copilot no aprovechaba el `SemanticCacheService` basado en Qdrant, resultando en llamadas LLM redundantes para consultas similares.

**Solucion:**
- Inyectar como `@?ecosistema_jaraba_core.semantic_cache` (opcional) --- graceful degradation si Qdrant no esta disponible
- Cache GET **antes** del exact cache check, SET **despues** de response exitosa
- Marcar respuestas cacheadas con `cache_type: 'semantic'` para distinguirlas en observabilidad
- Implementar tanto en `chat()` como en `streamChat()`

**Patron de integracion:**
```php
// Antes del procesamiento
if ($this->semanticCache) {
    $cached = $this->semanticCache->get($input, $context);
    if ($cached) {
        return $this->buildResponse($cached, ['cache_type' => 'semantic']);
    }
}

// Despues del procesamiento exitoso
if ($this->semanticCache && $response->isSuccessful()) {
    $this->semanticCache->set($input, $response->getContent(), $context);
}
```

**Leccion:** La inyeccion `@?` (opcional) es critica para servicios que dependen de infraestructura externa (Qdrant, Redis). El sistema DEBE funcionar sin cache --- solo mas lento.

### 3. AgentBenchmarkService (HAL-AI-22)

**Problema:** No existia forma sistematica de medir la calidad de las respuestas de los agentes ni comparar versiones.

**Solucion:**
- Integrar con `QualityEvaluatorService` existente (patron LLM-as-Judge)
- `AgentBenchmarkResult` como ContentEntity (no ConfigEntity) --- almacena datos historicos que crecen
- Test cases definidos como array con `input`, `expected_output`, `criteria`
- `compareVersions()` para A/B testing de agentes

**Leccion:** Los resultados de benchmarks son datos historicos, no configuracion. Usar ContentEntity permite queries, views, y exportacion. ConfigEntity seria incorrecto porque no es "configuracion del sitio" sino "datos generados".

### 4. PromptVersioning (HAL-AI-23)

**Problema:** Los prompts de los agentes estaban hardcodeados en las clases PHP, sin posibilidad de versionado, rollback, ni A/B testing.

**Solucion:**
- `PromptTemplate` como ConfigEntity con `config_prefix` pattern --- exportable/importable via `drush config:export/import`
- Versionado semantico automatico (major.minor en cada save)
- `rollback()` desactiva todas las versiones activas y reactiva la version target
- Config schema obligatorio en `jaraba_ai_agents.schema.yml`

**Patron de versionado:**
```yaml
# config/install/jaraba_ai_agents.prompt_template.greeting_v1.yml
id: greeting_v1
label: 'Greeting Prompt'
agent_id: smart_support
version: '1.0'
template: |
  You are a helpful support agent for {brand_name}...
variables:
  - brand_name
  - user_name
status: true
```

**Leccion:** ConfigEntity es correcto aqui porque los prompts son configuracion operacional que debe sincronizarse entre entornos. El `config_prefix` pattern permite tener multiples versiones como entidades de config separadas.

### 5. BrandVoiceProfile Entity (HAL-AI-30)

**Problema:** La voz de marca estaba definida solo en configuracion global, sin posibilidad de personalizacion por tenant.

**Solucion:**
- ContentEntity con `tenant_id` (entity_reference:group) para aislamiento multi-tenant
- `TenantBrandVoiceService` lee entity first, fallback a config global
- 8 arquetipos de marca (Hero, Sage, Creator, etc.)
- 5 personality traits con escala 1-10 (formality, warmth, humor, technicality, enthusiasm)
- Campo `terms` como JSON para vocabulario especifico del tenant

**Leccion:** El patron "entity first, config fallback" es robusto para multi-tenant. Permite que tenants nuevos funcionen con defaults globales mientras tenants maduros personalizan su experiencia.

### 6. CSS Code Splitting (HAL-AI-12)

**Problema:** Un unico archivo CSS monolitico (`main.css`) de +3000 lineas cargaba en todas las paginas, incluyendo estilos especificos de verticales y features no utilizadas.

**Solucion:**
- Extraer `@use` selectivos de `main.scss` a `scss/bundles/*.scss`
- Compilar cada bundle separadamente con `sass`
- Registrar como libraries independientes en `.libraries.yml`
- Attach via `hook_page_attachments_alter()` con route prefix matching

**Implementacion del mapping:**
```php
function ecosistema_jaraba_theme_page_attachments_alter(array &$attachments) {
    $route = \Drupal::routeMatch()->getRouteName();
    $mapping = [
        'jaraba_content_hub.' => ['ecosistema_jaraba_theme/content-hub-bundle'],
        'entity.content_article.' => ['ecosistema_jaraba_theme/content-hub-bundle'],
        'jaraba_page_builder.' => ['ecosistema_jaraba_theme/page-builder-bundle'],
        'jaraba_ai_agents.' => ['ecosistema_jaraba_theme/ai-agents-bundle'],
    ];
    foreach ($mapping as $prefix => $libraries) {
        if (str_starts_with($route, $prefix)) {
            foreach ($libraries as $library) {
                $attachments['#attached']['library'][] = $library;
            }
        }
    }
}
```

**Leccion:** El code splitting CSS via Drupal libraries es mas mantenible que lazy-loading custom. El mapping ruta->library es explicito y auditable. Soporta arrays de libraries por ruta para composicion.

### 7. CWV Tracking - Core Web Vitals (HAL-AI-10)

**Problema:** No habia monitorizacion de metricas de rendimiento del lado del cliente (LCP, CLS, INP, TTFB).

**Solucion:**
- `PerformanceObserver` API nativo del navegador --- sin dependencias externas
- Report via `dataLayer.push()` para integracion con Google Analytics / GTM
- LCP y CLS usan `visibilitychange` event (reportar al salir de la pagina)
- INP acumula interacciones y reporta la peor (P98)
- TTFB usa `performance.getEntriesByType('navigation')`

**Optimizaciones aplicadas:**
- `fetchpriority="high"` en hero images
- `decoding="async"` en TODAS las imagenes no-hero
- `loading="lazy"` para imagenes below-the-fold

**Leccion:** Las metricas CWV deben reportarse en `visibilitychange` (no en `load`) porque LCP y CLS pueden cambiar durante toda la vida de la pagina. El `dataLayer` pattern permite desacoplar la medicion del reporting.

### 8. Responsive Images con AVIF (HAL-AI-13)

**Problema:** Las imagenes se servian en un unico formato sin optimizacion por formato moderno ni breakpoints responsivos.

**Solucion:**
- Elemento `<picture>` con `<source>` AVIF primero, WebP segundo, `<img>` fallback
- Twig function `responsive_image()` en `JarabaTwigExtension`
- ImageStyle breakpoints: `responsive_400w`, `responsive_800w`, `responsive_1200w`
- Partial `_responsive-image.html.twig` como include reutilizable

**Estructura del elemento:**
```html
<picture>
  <source srcset="/styles/responsive_400w/image.avif 400w,
                  /styles/responsive_800w/image.avif 800w,
                  /styles/responsive_1200w/image.avif 1200w"
          type="image/avif" sizes="(max-width: 600px) 400px, (max-width: 1024px) 800px, 1200px">
  <source srcset="..." type="image/webp" sizes="...">
  <img src="/styles/responsive_800w/image.jpg" alt="..." loading="lazy" decoding="async">
</picture>
```

**Leccion:** AVIF ofrece ~50% reduccion vs WebP pero el soporte de navegador aun no es universal (Safari 16+). El cascade `<source>` AVIF -> WebP -> JPEG garantiza compatibilidad total. Registrar los ImageStyles en un update hook para que se creen automaticamente.

### 9. MultiModal Completo (HAL-AI-24)

**Problema:** El stack de IA solo soportaba Text-to-Text. No habia capacidades de Text-to-Speech ni Text-to-Image.

**Solucion:**
- Aprovechar que Drupal AI module ya tiene `OperationType` para TTS e ImageGen
- `resolveProviderForOperation()` helper para resolver el provider proxy correcto
- `synthesizeSpeech()`: TTS-1 por defecto, TTS-1-HD con option, voice configurable
- `generateImage()`: guardrails check (PII + jailbreak) ANTES de enviar prompt al modelo
- Outputs guardados como archivos temporales: `temporary://jaraba-tts/`, `temporary://jaraba-imagegen/`

**Guardrails para generacion de imagenes:**
```php
public function generateImage(string $prompt, array $options = []): array {
    // SIEMPRE verificar guardrails ANTES de enviar al modelo
    $guardrailResult = $this->guardrails->checkInput($prompt);
    if ($guardrailResult->isBlocked()) {
        return ['error' => 'Content policy violation', 'details' => $guardrailResult->getReason()];
    }
    // Proceder con la generacion...
}
```

**Leccion:** Los guardrails de seguridad (PII, jailbreak) deben ejecutarse ANTES de cualquier llamada a modelo generativo, especialmente para imagenes donde el contenido generado puede ser inapropiado. El costo de una verificacion de texto es minimo comparado con una generacion de imagen.

### 10. Concurrent Edit Locking (HAL-AI-27)

**Problema:** Dos usuarios podian editar la misma entidad simultaneamente, resultando en perdida de datos del primero en guardar.

**Solucion:**
- Optimistic locking via comparacion de timestamp `changed`
- Header `X-Entity-Changed` en save -> respuesta 409 Conflict si hay mismatch
- Campos transitorios: `edit_lock_uid` + `edit_lock_expires` (NOT revisionable)
- `setSyncing(TRUE)` para saves de lock (no crear revisiones innecesarias)
- JS: lock acquire al abrir form -> heartbeat cada 2 minutos -> `beforeunload` release con `keepalive: true`

**Flujo completo:**
1. Usuario A abre formulario -> JS adquiere lock (`POST /api/entity/{id}/lock`)
2. JS inicia heartbeat cada 120s para renovar el lock
3. Usuario B intenta abrir -> ve mensaje "Editando por Usuario A"
4. Usuario A guarda -> lock se libera automaticamente
5. Si Usuario A cierra pestana sin guardar -> `beforeunload` con `navigator.sendBeacon()` libera lock
6. Si el heartbeat falla (crash del navegador) -> lock expira en 5 minutos

**Leccion:** El `keepalive: true` en `fetch()` y `navigator.sendBeacon()` son ambos necesarios para `beforeunload` --- `sendBeacon` como fallback para navegadores que no soporten `keepalive`. `setSyncing(TRUE)` es critico para evitar que saves de mantenimiento (lock/heartbeat) generen revisiones basura.

### 11. PersonalizationEngine (S5-09)

**Problema:** Existian 6 servicios de recomendacion/personalizacion independientes pero sin orquestacion unificada.

**Solucion:**
- Crear un orquestador (`PersonalizationEngineService`) que coordina los 6 servicios existentes --- NO crear pipeline ML propio
- Context-aware weights: pesos diferentes segun vertical (content, employment, learning, commerce, default)
- Re-ranking por engagement historico del usuario (clics, tiempo en pagina, completions)
- Fallback graceful si servicios individuales fallan (reduce peso a 0, normaliza resto)

**Tabla de pesos por contexto:**
| Servicio | Content | Employment | Learning | Commerce | Default |
|----------|---------|------------|----------|----------|---------|
| Collaborative Filtering | 0.3 | 0.2 | 0.25 | 0.3 | 0.25 |
| Content-Based | 0.25 | 0.15 | 0.3 | 0.2 | 0.25 |
| Behavioral | 0.2 | 0.25 | 0.2 | 0.25 | 0.2 |
| Skill-Based | 0.05 | 0.3 | 0.15 | 0.05 | 0.1 |
| Trending | 0.15 | 0.05 | 0.05 | 0.15 | 0.15 |
| Serendipity | 0.05 | 0.05 | 0.05 | 0.05 | 0.05 |

**Leccion:** Orquestar servicios existentes es casi siempre mejor que construir un sistema monolitico nuevo. Los pesos por contexto permiten personalizar sin cambiar codigo --- solo configuracion. El 5% de serendipity evita filter bubbles.

### 12. Unit Tests para Servicios AI (HAL-AI-15)

**Problema:** Varios servicios core de IA carecian de tests unitarios, complicando el refactoring seguro.

**Solucion:**
- Usar `ReflectionClass` para instanciar clases `final` como mocks (e.g., `AiProviderPluginManager`)
- Anonymous class para testar abstract classes (e.g., `SmartBaseAgent`)
- Mock interfaces custom cuando las clases concretas son `final`
- `getMockBuilder()->addMethods()` para metodos no declarados en la interfaz

**Patron para abstract class testing:**
```php
$agent = new class(
    $mockProvider,
    $mockConfig,
    $mockLogger,
    // ...8 more args
) extends SmartBaseAgent {
    public function doExecute(string $input, array $context = []): array {
        return ['response' => 'test'];
    }
    public function getAgentId(): string {
        return 'test_agent';
    }
};
```

**Leccion:** PHPUnit 11 elimino `getMockForAbstractClass()`, pero las anonymous classes son mas expresivas y permiten controlar exactamente que metodos se implementan. `addMethods()` es el reemplazo correcto de `setMethods()` para metodos no existentes en la clase base.

---

## Patrones Reutilizables

### Background Agents Paralelos
Lanzar 3-4 tareas de edicion independientes en paralelo cuando no hay dependencias entre archivos. Esto reduce el tiempo total de implementacion significativamente. Ejemplo: editar servicios en modulos diferentes mientras se crean tests en paralelo.

### DOC-GUARD-001 Disciplina
SIEMPRE usar Edit (nunca Write) para documentos maestros (`00_*.md`, `07_*.md`). El pre-commit hook rechazara commits que violen los umbrales de lineas minimas. Verificar con `scripts/maintenance/verify-doc-integrity.sh` antes de commit.

### COMMIT-SCOPE-001 Separacion
Un documento maestro por commit, separado del codigo. Esto facilita revert selectivo y mantiene el historial limpio.

### Incremental Audit Updates
Al actualizar documentos de auditoria con muchos hallazgos:
1. Actualizar estados de hallazgos uno por uno (PENDIENTE -> COMPLETADO)
2. Luego actualizar scorecard
3. Luego actualizar roadmap
4. Finalmente actualizar changelog

Esto evita errores de edicion y permite verificar cada paso.

### Optional Service Injection (@?)
Para servicios que dependen de infraestructura externa (Qdrant, Redis, APIs externas):
```yaml
arguments:
  - '@?service.that.might.not.exist'
```
El servicio recibe `null` si la dependencia no esta disponible. SIEMPRE verificar con `if ($this->optionalService)` antes de usar.

---

## Ficheros Clave Creados

| Fichero | Tipo | Modulo |
|---------|------|--------|
| `SmartEmployabilityCopilotAgent.php` | Gen 2 Agent | jaraba_ai_agents |
| `SmartLegalCopilotAgent.php` | Gen 2 Agent | jaraba_ai_agents |
| `SmartContentWriterAgent.php` | Gen 2 Agent | jaraba_ai_agents |
| `AgentBenchmarkService.php` | Service | jaraba_ai_agents |
| `AgentBenchmarkResult.php` | ContentEntity | jaraba_ai_agents |
| `PromptTemplate.php` | ConfigEntity | jaraba_ai_agents |
| `PromptVersionService.php` | Service | jaraba_ai_agents |
| `BrandVoiceProfile.php` | ContentEntity | ecosistema_jaraba_core |
| `TenantBrandVoiceService.php` | Service | ecosistema_jaraba_core |
| `PersonalizationEngineService.php` | Service | ecosistema_jaraba_core |
| `MultiModalService.php` | Service | jaraba_ai_agents |
| `ConcurrentEditService.php` | Service | ecosistema_jaraba_core |
| `cwv-tracking.js` | Frontend JS | ecosistema_jaraba_theme |
| `scss/bundles/content-hub.scss` | CSS Bundle | ecosistema_jaraba_theme |
| `scss/bundles/page-builder.scss` | CSS Bundle | ecosistema_jaraba_theme |
| `scss/bundles/ai-agents.scss` | CSS Bundle | ecosistema_jaraba_theme |
| `scss/bundles/empleabilidad.scss` | CSS Bundle | ecosistema_jaraba_theme |
| `scss/bundles/emprendimiento.scss` | CSS Bundle | ecosistema_jaraba_theme |
| `scss/bundles/comercio.scss` | CSS Bundle | ecosistema_jaraba_theme |
| `scss/bundles/formacion.scss` | CSS Bundle | ecosistema_jaraba_theme |
| `_responsive-image.html.twig` | Twig Partial | ecosistema_jaraba_theme |
| `ModelRouterServiceTest.php` | Unit Test | jaraba_ai_agents |
| `SmartBaseAgentTest.php` | Unit Test | jaraba_ai_agents |
| `GuardrailsServiceTest.php` | Unit Test | ecosistema_jaraba_core |
| `PersonalizationEngineTest.php` | Unit Test | ecosistema_jaraba_core |

---

## Metricas del Sprint

| Metrica | Valor |
|---------|-------|
| Hallazgos resueltos | 15 (Sprint 5) / 30 total |
| Archivos editados | 50+ |
| Archivos creados | 25+ |
| Tests anadidos | 4 suites, 28 test methods |
| Score final | 100/100 |
| Tiempo de ejecucion | 1 sesion |

---

## Relacion con Documentos

- **Auditoria v1:** `docs/analisis/2026-02-27_Auditoria_IA_SaaS_Clase_Mundial_v1.md`
- **Plan de implementacion:** `docs/implementacion/2026-02-27_Plan_Implementacion_Elevacion_IA_Clase_Mundial_v1.md`
- **Aprendizajes IA previos:** `docs/tecnicos/aprendizajes/2026-02-26_auditoria_ia_clase_mundial_25_gaps.md`
- **Aprendizajes nivel 5:** `docs/tecnicos/aprendizajes/2026-02-26_elevacion_ia_nivel5_23_fixes.md`
- **Arquitectura IA nivel 5:** `docs/arquitectura/2026-02-26_arquitectura_elevacion_ia_nivel5.md`
- **Memory rules:** `SMART-AGENT-CONSTRUCTOR-001`, `AGENT-GEN2-PATTERN-001`, `AI-GUARDRAILS-PII-001`
