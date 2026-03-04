# Aprendizaje #159: Stripe Integration + DI Audit Runtime Fixes

**Fecha:** 2026-03-04
**Contexto:** Configuracion de Stripe Connect para el SaaS + auditoria exhaustiva de inyeccion de dependencias tras descubrir errores runtime en cascada.
**Documentos relacionados:** CLAUDE.md, Directrices v109.0.0, Arquitectura v98.0.0

## 1. Stripe Connect Integration

### Configuracion
- **Cuenta Stripe:** Tipo "Plataforma o marketplace" con Stripe Connect (destination charges)
- **Productos:** Pagos recurrentes (subscriptions) + facturacion basada en uso (metered billing)
- **Categoria fiscal:** SaaS (generico, no comercial — hay personas fisicas comprando formacion)
- **Descriptor bancario:** JARABA IMPACT

### Patron SECRET-MGMT-001 Ampliado
- Variables de entorno: `STRIPE_PUBLIC_KEY`, `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_FOC_WEBHOOK_SECRET`
- `settings.secrets.php` inyecta en 3 namespaces config: `ecosistema_jaraba_core.stripe`, `jaraba_foc.settings`, `jaraba_legal_billing.settings`
- `StripeConnectService::getSecretKey()` tiene fallback a `getenv()` directo para resiliencia

### Webhooks
- Endpoint Billing: `/api/v1/billing/webhook` — eventos subscription, invoice, payment_intent, checkout
- Endpoint FOC: `/api/v1/foc/webhook` — eventos account, transfer, payout, payment_intent
- Secrets separados por endpoint (buena practica Stripe)

## 2. DI Audit — 3 Categorias de Error

### 2.1 LOGGER-DI-001: Logger Factory vs Channel Mismatch
**Patron erroneo:** `services.yml` inyecta `@logger.channel.{module}` pero el constructor PHP declara `LoggerChannelFactoryInterface` y llama `$logger->get('channel')`.
**Solucion:** Verificar SIEMPRE el type hint del constructor antes de decidir entre `@logger.factory` y `@logger.channel.*`:
- Si constructor acepta `LoggerChannelFactoryInterface` → usar `@logger.factory`
- Si constructor acepta `LoggerInterface` → usar `@logger.channel.*`
**Modulos afectados:** 12 servicios en jaraba_pixels (7), jaraba_comercio_conecta (8 services via @logger.factory), jaraba_agroconecta_core (3), jaraba_business_tools (4), jaraba_resources (1).

### 2.2 CIRCULAR-DEP-001: Phantom Arguments in services.yml
**Patron erroneo:** `services.yml` declara argumentos `@?optional_service` que el constructor PHP NO acepta, creando dependencias circulares A→B→A.
**Solucion:** El numero de argumentos en services.yml DEBE coincidir exactamente con el numero de parametros del constructor PHP.
**Modulos afectados:** jaraba_interactive (indentacion YAML), ecosistema_jaraba_core (style_preset 4→2 args), jaraba_foc (metrics_calculator), jaraba_email (campaign_service), jaraba_lms (course, certification, xapi), jaraba_resources (subscription_service), jaraba_business_tools (canvas_service), jaraba_candidate (skills, profile_completion, import), jaraba_job_board (employer).

### 2.3 PHP 8.4 Nullable Deprecation
**Patron erroneo:** `float $param = NULL` — implicitamente nullable, deprecado en PHP 8.4.
**Solucion:** `?float $param = NULL` — explicitamente nullable.
**Modulo afectado:** jaraba_comercio_conecta FlashOfferService.

## 3. Leccion Critica: NO Tomar Atajos

Un `replace_all` masivo de `@logger.channel.jaraba_pixels` a `@logger.factory` rompio 2 servicios que correctamente usaban `LoggerInterface`. La leccion:
- **SIEMPRE** verificar el constructor de CADA servicio individualmente antes de modificar services.yml
- **NUNCA** hacer cambios masivos sin verificacion unitaria
- El coste de verificar 1 constructor (~15 segundos) es infinitamente menor que el coste de un error runtime

## 4. Reglas Nuevas

| Regla | Prioridad | Descripcion |
|-------|-----------|-------------|
| LOGGER-DI-001 | P0 | Verificar type hint constructor (Factory vs Channel) antes de inyectar logger |
| CIRCULAR-DEP-001 | P0 | Argumentos services.yml DEBEN coincidir exactamente con parametros constructor |
| PHANTOM-ARG-001 | P0 | NUNCA anadir @? args a services.yml sin verificar que el constructor los acepta |
| STRIPE-ENV-UNIFY-001 | P1 | Todos los modulos que usan Stripe DEBEN leer keys via settings.secrets.php, no config directo |

## 5. Reglas de Oro

- **#99:** LOGGER-DI-001 — Antes de inyectar un logger, SIEMPRE verificar el type hint del constructor: `LoggerChannelFactoryInterface` necesita `@logger.factory`, `LoggerInterface` necesita `@logger.channel.*`. Un mismatch causa `LoggerChannel::get() undefined method` en runtime.
- **#100:** CIRCULAR-DEP-001 + PHANTOM-ARG-001 — El numero de argumentos en services.yml DEBE coincidir exactamente con los parametros del constructor PHP. Argumentos `@?` fantasma que el constructor no acepta crean dependencias circulares silenciosas que solo se manifiestan en `drush cr`.

## 6. Gaps del Sistema de Salvaguarda Identificados

3 categorias de error no detectadas por el sistema de validacion actual:
1. **Dependencias circulares:** No existe `validate-service-graph.php` (algoritmo Tarjan)
2. **Logger type mismatch:** `validate-services-di.php` solo corre en modo `--full`, no en `--fast`
3. **Phantom arguments:** No existe validacion de conteo constructor params vs services.yml args

**Recomendacion:** Crear `validate-service-graph.php` con deteccion de ciclos y ampliar `validate-services-di.php` con verificacion de conteo de argumentos en modo `--fast`.
