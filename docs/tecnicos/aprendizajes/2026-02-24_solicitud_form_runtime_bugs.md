# Aprendizaje #112: SolicitudEiPublicForm — 4 Bugs de Runtime Descubiertos en Browser Testing

**Fecha:** 2026-02-24
**Contexto:** Prueba end-to-end del recorrido de candidato en Andalucia +ei (formulario publico de solicitud) descubre 4 bugs de runtime que impedian completar el envio. Ninguno era detectable por inspeccion estatica de codigo — todos requerían ejecucion real en navegador.
**Impacto:** SolicitudEiPublicForm — formulario 100% bloqueado, ningun candidato podia enviar solicitud

---

## 1. Problema

Al probar el recorrido completo de un candidato en el navegador (`/andalucia-ei/solicitar`), el formulario no completaba el envio. Se descubrieron 4 bugs encadenados — cada uno bloqueaba el formulario de forma diferente:

| # | Severidad | Bug | Sintoma |
|---|-----------|-----|---------|
| BUG-1 | Critica | Time-gate anti-spam bloquea permanentemente | Mensaje "Por favor, espera un momento" aparece siempre, incluso esperando minutos |
| BUG-2 | Critica | `entityTypeManager()` no existe en FormBase | Error 500: `Call to undefined method SolicitudEiPublicForm::entityTypeManager()` |
| BUG-3 | Alta | `catch (\Exception)` no captura `TypeError` de AI triage | Error 500: `TypeError: method_exists(): Argument #1 must be of type object\|string, array given` |
| BUG-4 | Media | Resolucion de tenant con servicio incorrecto | `tenant_context` resuelve por usuario (NULL para anonimos), deberia usar `tenant_manager` (por dominio) |

---

## 2. Diagnostico

### BUG-1 — Time-gate anti-spam bloquea permanentemente

**Causa raiz:** El campo hidden `form_token_ts` usa `#value => time()`. En Drupal, los campos `#type => 'hidden'` con `#value` **regeneran el valor en cada rebuild del formulario**, incluida la fase de validacion. Por tanto:

1. El formulario se construye en GET: `form_token_ts = 1771933758` (timestamp correcto)
2. El usuario envia en POST 30 segundos despues
3. Drupal reconstruye el formulario para validar: `form_token_ts = 1771933788` (timestamp NUEVO)
4. `validateForm()` lee via `$form_state->getValue('form_token_ts')` → obtiene `1771933788` (el regenerado)
5. `time() - 1771933788 = 0` → siempre < 3 segundos → **bloqueo permanente**

**Intentos fallidos que revelaron el mecanismo interno de Drupal:**

| Intento | Tecnica | Por que fallo |
|---------|---------|---------------|
| 1 | `$form_state->set('ts', time())` / `$form_state->get('ts')` | FormBase no cachea form_state entre GET y POST para formularios no-AJAX |
| 2 | `#default_value => time()` en vez de `#value` | Drupal Hidden element regenera independientemente de `#value` vs `#default_value` |
| 3 | `#type => 'markup'` con `<input type="hidden">` raw | Error 500: `Xss::filterAdmin()` elimina tags `<input>` del markup |

### BUG-2 — entityTypeManager() no existe en FormBase

**Causa raiz:** En el fix P0-2 de la sesion anterior, se cambio `\Drupal::entityTypeManager()` (service locator estatico) por `$this->entityTypeManager()` (metodo helper). Pero `SolicitudEiPublicForm` extiende `FormBase`, no `ControllerBase`. **Solo `ControllerBase` tiene el metodo helper `entityTypeManager()`** — `FormBase` no lo proporciona.

**Jerarquia relevante:**
```
ControllerBase → tiene entityTypeManager(), formBuilder(), etc.
FormBase → solo tiene configFactory(), getRequest(), currentUser()
```

### BUG-3 — catch (\Exception) no captura TypeError

**Causa raiz:** El servicio de triaje IA (`SolicitudTriageService`) invoca el modulo AI de Drupal (`OpenAiBasedProviderClientBase->chat()`). Este lanza un `TypeError` cuando recibe un array donde esperaba un objeto. El catch block usaba `\Exception`, pero en PHP:

```
Throwable (interfaz)
├── Error (clase)
│   ├── TypeError    ← TypeError extiende Error, NO Exception
│   ├── ValueError
│   └── ...
└── Exception (clase)
    ├── RuntimeException
    └── ...
```

`catch (\Exception $e)` **nunca captura** `TypeError` porque este extiende `\Error`, no `\Exception`.

### BUG-4 — Resolucion de tenant con servicio incorrecto

**Causa raiz:** El codigo usaba `ecosistema_jaraba_core.tenant_context` para resolver el tenant_id. Este servicio resuelve por **usuario autenticado** (busca tenant donde `admin_user = $uid`). En un formulario publico anonimo, el usuario siempre es `uid=0` → tenant siempre NULL.

El servicio correcto es `ecosistema_jaraba_core.tenant_manager`, que resuelve por **hostname** (`$request->getHost()` → busca tenant por dominio).

Nota: Para el dominio principal `jaraba-saas.lndo.site`, no hay tenant asociado (es la plataforma madre), por lo que `tenant_id = NULL` es correcto en este caso. Pero el servicio `tenant_manager` es el apropiado para formularios publicos.

---

## 3. Solucion Implementada

### 3.1 Time-gate: leer del user input raw (BUG-1)

```php
// buildForm() — mantener #value (se regenera, pero el original persiste en POST)
$form['form_token_ts'] = [
    '#type' => 'hidden',
    '#value' => time(),
];

// validateForm() — leer de getUserInput() (POST raw), NO de getValue() (procesado)
$userInput = $form_state->getUserInput();
$ts = (int) ($userInput['form_token_ts'] ?? 0);
if ($ts > 0 && (time() - $ts) < 3) {
    $form_state->setErrorByName('', $this->t('...'));
    return;
}
```

**Por que funciona:** `$form_state->getUserInput()` devuelve los datos crudos del POST tal como los envio el navegador. El timestamp original (`1771933758`) persiste en el input HTML del navegador y se envia sin modificar. `$form_state->getValue()` devuelve el valor procesado por Drupal (regenerado a `1771933788`).

### 3.2 DI explicita de EntityTypeManagerInterface (BUG-2)

```php
use Drupal\Core\Entity\EntityTypeManagerInterface;

class SolicitudEiPublicForm extends FormBase
{
    protected EntityTypeManagerInterface $entityTypeManager;

    public static function create(ContainerInterface $container): static
    {
        $instance = parent::create($container);
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->mailManager = $container->get('plugin.manager.mail');
        $instance->triageService = $container->get('jaraba_andalucia_ei.solicitud_triage');
        return $instance;
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $storage = $this->entityTypeManager->getStorage('solicitud_ei');
        // ...
    }
}
```

### 3.3 catch (\Throwable) para errores de AI (BUG-3)

```php
// ANTES:
} catch (\Exception $e) {

// DESPUES:
} catch (\Throwable $e) {
    // El triaje IA no debe bloquear la solicitud.
    \Drupal::logger('jaraba_andalucia_ei')->error('Error en triaje IA: @msg', [
        '@msg' => $e->getMessage(),
    ]);
}
```

Tambien se aplico `\Throwable` al catch del bloque de resolucion de tenant.

### 3.4 tenant_manager en vez de tenant_context (BUG-4)

```php
// ANTES:
if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
    $tenant = \Drupal::service('ecosistema_jaraba_core.tenant_context')->getCurrentTenant();

// DESPUES:
if (\Drupal::hasService('ecosistema_jaraba_core.tenant_manager')) {
    $tenant = \Drupal::service('ecosistema_jaraba_core.tenant_manager')->getCurrentTenant();
```

---

## 4. Reglas Derivadas

### DRUPAL-HIDDEN-VALUE-001 (nueva)

En Drupal, `#type => 'hidden'` con `#value` regenera el valor en cada form rebuild (incluida validacion). Para leer el valor original enviado por el navegador, usar `$form_state->getUserInput()['field_name']` en vez de `$form_state->getValue('field_name')`. Esto aplica a cualquier campo hidden usado como timestamp, token, o valor que no debe cambiar entre GET y POST.

### DRUPAL-FORMBASE-DI-001 (nueva)

`FormBase` NO proporciona los metodos helper de `ControllerBase` (`entityTypeManager()`, `formBuilder()`, `languageManager()`, etc.). En formularios que extienden `FormBase`, siempre inyectar dependencias explicitamente via `create()` como propiedades de instancia. NUNCA asumir que los helpers de `ControllerBase` estan disponibles.

### PHP-THROWABLE-001 (nueva)

Usar `catch (\Throwable $e)` en vez de `catch (\Exception $e)` cuando el codigo llamado puede lanzar errores de tipo (`TypeError`, `ValueError`, etc.). Esto es especialmente critico con:
- Modulos contrib/third-party (AI module, integraciones externas)
- Operaciones que no deben bloquear el flujo principal (triaje IA, envio de emails, logging)

La regla general: si el catch es un "safety net" para que el flujo no se rompa, usar `\Throwable`.

### TENANT-RESOLVER-001 (nueva)

Para formularios publicos/anonimos, usar `tenant_manager` (resolucion por dominio) en vez de `tenant_context` (resolucion por usuario). `tenant_context` requiere un usuario autenticado con `admin_user` asociado a un tenant — siempre devuelve NULL para anonimos.

| Servicio | Estrategia | Uso correcto |
|----------|-----------|--------------|
| `tenant_context` | Por usuario (`admin_user = $uid`) | Dashboards, paneles admin, areas autenticadas |
| `tenant_manager` | Por hostname (`$request->getHost()`) | Formularios publicos, landing pages, APIs anonimas |

---

## 5. Fichero Modificado

| Fichero | Cambios | Bugs |
|---------|---------|------|
| `jaraba_andalucia_ei/src/Form/SolicitudEiPublicForm.php` | 4 fixes: getUserInput(), DI EntityTypeManager, \Throwable x2, tenant_manager | BUG 1-4 |

---

## 6. Verificacion en Navegador

| Test | Resultado |
|------|-----------|
| GET `/andalucia-ei/solicitar` | HTTP 200, 15 campos, honeypot, time-gate |
| POST con datos validos (espera >3s) | HTTP 303 → redirect a `/es/andalucia-ei` |
| Solicitud creada en BD | `solicitud_ei.id=2`, todos los campos correctos, `estado=pendiente` |
| AI triage error capturado | Watchdog entry: "Error en triaje IA" (no bloquea) |
| Paginas legales accesibles | `/politica-privacidad`, `/terminos-uso`, `/politica-cookies` → HTTP 200 |
| Vista admin solicitudes | Tabla con 8 columnas, enlaces Editar/Eliminar/Ver funcionales |

---

## 7. Leccion Clave

**El browser testing descubre bugs que la revision de codigo y el analisis estatico no pueden detectar.** Los 4 bugs de este aprendizaje son invisibles en una lectura de codigo:

1. **BUG-1** requiere entender el ciclo de vida interno del form rebuild de Drupal (no documentado de forma obvia)
2. **BUG-2** requiere saber que `FormBase` y `ControllerBase` tienen APIs diferentes (confusion comun)
3. **BUG-3** requiere que el modulo AI lance un `TypeError` real (solo ocurre en runtime con datos reales)
4. **BUG-4** requiere saber que hay dos servicios de resolucion de tenant con estrategias diferentes

**Patron recomendado:** Despues de cada ronda de fixes por auditoria de codigo, ejecutar el flujo completo en navegador (curl con cookies o Cypress) para verificar que los cambios funcionan en runtime. Las regresiones introducidas por refactoring (BUG-2 fue causado por el fix P0-2) solo se detectan con ejecucion real.
