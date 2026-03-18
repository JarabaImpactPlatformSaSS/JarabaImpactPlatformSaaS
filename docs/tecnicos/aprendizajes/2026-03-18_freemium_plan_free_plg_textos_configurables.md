# Aprendizaje #195 — Plan Free Real + Textos PLG Configurables

**Fecha:** 2026-03-18
**Autor:** Claude Opus 4.6
**Tipo:** Implementación freemium + configurabilidad UI

---

## Contexto

Las landings de los verticales mostraban "Plan gratuito disponible. Sin tarjeta de crédito." pero NO existía un plan gratuito real en la BD. Los precios empezaban desde €29/mes (Starter). El texto era una promesa sin respaldo, lo que erosiona la confianza del usuario.

## Solución: 3 Acciones

### A. Plan Free Real (€0/mes) por Vertical
- 8 SaasPlan entities creados via `update_9038` con `price_monthly: 0.00`
- Weight 0 (antes de Starter weight 10) — el plan Free aparece primero
- Límites por vertical basados en los 70 FreemiumVerticalLimit existentes:
  - Empleabilidad: 1 CV, 5 copilot/día, 3 aplicaciones/día
  - Emprendimiento: 1 BMC, 3 hipótesis, 5 copilot/día
  - AgroConecta: 10 productos, 5 pedidos/mes, 10% comisión
  - ComercioConecta: 10 productos, 1 flash offer, 1 QR
  - ServiciosConecta: 3 servicios, 10 reservas/mes
  - JarabaLex: 5 expedientes, 10 búsquedas/mes
  - Andalucía +ei: 5 participantes, 3 sesiones/mes
  - Formación: 1 curso, 10 alumnos

### B. Trial 14 Días (ya existía)
`CheckoutSessionService::DEFAULT_TRIAL_DAYS = 14` — cuando el usuario upgradea de Free a Starter/Pro, obtiene 14 días de trial del plan superior.

### C. Textos PLG Configurables desde Theme Settings
- 4 campos en Appearance > Ecosistema Jaraba Theme > PLG / Textos de conversión:
  - `plg_free_plan_note`: "Plan gratuito disponible. Sin tarjeta de crédito."
  - `plg_cta_subtitle`: "Sin tarjeta de crédito. Empieza gratis."
  - `plg_guarantee_text`: "Sin tarjeta de crédito. Sin permanencia. Cancela cuando quieras."
  - `plg_register_subtitle`: "Crea tu cuenta gratis en menos de 2 minutos..."
- 5 templates migrados de `{% trans %}` hardcodeado a variables configurables con `|default()`
- PricingController: `getPlgGuaranteeText()` lee desde `theme_get_setting()`

## Descubrimientos

### 1. FreemiumVerticalLimit ya existía
70 configs de límites free para 8 verticales ya estaban definidas en config/sync. Solo faltaba el SaasPlan entity formal que las respaldara.

### 2. MetaSitePricingService necesitó ajuste
`loadVerticalPrices()` asignaba tiers por posición (1st=starter). Con un plan Free (weight=0) antes de Starter (weight=10), ahora detecta si el primer plan tiene precio 0 y ajusta el array de tiers: `['free', 'starter', 'professional', 'enterprise']`.

### 3. Textos hardcodeados estaban en 4 capas
Twig parciales, controllers PHP, GrapesJS blocks y meta descriptions — todos con el mismo texto pero sin fuente única. La migración a `theme_get_setting()` centraliza el control.

## Regla de Oro #136
Todo texto PLG visible al usuario ("Plan gratuito disponible", "Sin tarjeta de crédito") DEBE tener respaldo real en la BD (SaasPlan entity a €0) Y ser configurable desde la UI del tema. Prometer algo que no existe erosiona la confianza y el NPS.

## Métricas
- 36 planes SaaS totales (8 Free + 24 de pago + 4 legacy)
- 16 YAML configs creados (8 config/sync + 8 config/install)
- 5 templates migrados a variables configurables
- 4 campos PLG en Theme Settings
