# Aprendizaje #194 — Kit Digital Agente Digitalizador: Implementación Completa

**Fecha:** 2026-03-18
**Módulos:** jaraba_billing, ecosistema_jaraba_theme
**Reglas nuevas:** KIT-DIGITAL-001 a KIT-DIGITAL-007
**Regla de oro:** #135

---

## Contexto

PED S.L. cumple todos los requisitos para adhesión como Agente Digitalizador del Kit Digital (facturación PIIL 427.500 EUR, 8.5x el umbral). Implementación completa del sistema de gestión de Acuerdos de Prestación de Soluciones de Digitalización en 4 fases.

## Hallazgos

### Fase 1 — Backend

- **KitDigitalAgreement ContentEntity** (17 campos, lifecycle 7 estados: draft→signed→active→justification_pending→justified→paid→expired). Constantes PAQUETES (5), SEGMENTOS (5), STATUSES (7) centralizadas en la entity.
- **KitDigitalService** (10 métodos): createAgreement con secuencia automática KD-YYYY-NNNN, getCategoriesForPaquete mapea 5 paquetes a categorías C1-C9 del Anexo IV, getMaxBonoAmount con tabla paquete×segmento, calculateCoveredMonths con mínimo 12 meses, linkToStripeSubscription, getActiveAgreements, isKitDigitalTenant, getExpiringAgreements, processExpiredAgreements.
- PremiumEntityFormBase con 5 secciones (identificación, beneficiario, paquete, ciclo de vida, justificación).
- AccessControlHandler con ACCESS-RETURN-TYPE-001, TENANT-ISOLATION-ACCESS-001.

### Fase 2 — Frontend

- KitDigitalController con 2 métodos (landing 5 paquetes + paquete individual).
- Cada página de paquete cumple 9 requisitos Anexo II Red.es: nombre, categorías, segmentos, sectores, descripción funcional, requisitos técnicos, precio regular vs bono, duración 12 meses, logos obligatorios.
- Zero-region templates con parcial _kit-digital-logos.html.twig reutilizable.
- SCSS mobile-first 420 líneas con var(--ej-*) y color-mix().
- 4 logos SVG placeholder obligatorios (Kit Digital, NextGenEU, Plan Recuperación, Gobierno España).
- Precios dinámicos via MetaSitePricingService (NO-HARDCODE-PRICE-001).

### Fase 3 — Automatización

- KitDigitalAdminController: dashboard KPIs (total, active, pending_justification, bono_total_eur, expiring_soon) + distribución por paquete + tabla recientes.
- Cron: processExpiredAgreements() transiciona active→justification_pending automáticamente.
- 3 emails transaccionales (agreement_created, justification_reminder, bono_expiring).
- SetupWizard step + 2 Daily Actions con badges dinámicos (SETUP-WIZARD-DAILY-001).

### Fase 4 — Stripe

- linkToStripeSubscription() vincula bono→suscripción Stripe.
- calculateCoveredMonths() con mínimo 12 meses (requisito Kit Digital).
- Patrón trial_end: Stripe no cobra durante período de bono.

### PHPStan Level 6

- Database::query() prohibido por regla de seguridad PHPStan — usar select()/insert()/update()/delete() exclusivamente.
- $container->has() en ternary reportado como "always true" — usar try-catch pattern en su lugar.
- Baseline regenerado: 46141 entradas.

### RUNTIME-VERIFY-001

- 5 rutas HTTP 200 verificadas (landing + 5 paquetes).
- Body classes correctas: `page-kit-digital clean-layout full-width-layout`.
- DB table `kit_digital_agreement` EXISTS.
- Entity type instalada via hook_update_10006.
- KitDigitalService funcional: categorías, bonos, meses cubiertos, tenant detection OK.
- Admin dashboard renderiza con datos reales (KPIs actualizados).
- CI: tests green, deploy IONOS en curso.

## Reglas nuevas

- **KIT-DIGITAL-001:** Toda página /kit-digital/* DEBE incluir logos obligatorios y mención a NextGenerationEU.
- **KIT-DIGITAL-002:** Precios en web Kit Digital DEBEN ser Config Entities editables (Regla #131 Doc 158).
- **KIT-DIGITAL-003:** KitDigitalAgreement pertenece a jaraba_billing (no crear módulo nuevo).
- **KIT-DIGITAL-004:** Flujo de firma usa jaraba_legal firma PAdES existente.
- **KIT-DIGITAL-005:** Métricas Kit Digital se integran en FOC existente (no dashboard separado).
- **KIT-DIGITAL-006:** ROUTE-LANGPREFIX-001 aplica: rutas sin /es/ hardcoded.
- **KIT-DIGITAL-007:** TENANT-001 aplica: cada KitDigitalAgreement tiene tenant_id FK.

## Regla de oro #135

Landing pages Kit Digital DEBEN cumplir los 9 requisitos del Anexo II Red.es — nombre solución, categorías Kit Digital, segmentos beneficiarios, sectores actividad, descripción funcional, requisitos técnicos, precio regular vs bono, duración 12 meses, logos obligatorios. Sin cualquiera de estos, Red.es rechaza la adhesión como Agente Digitalizador.

## Cross-refs

- Doc 179: 179_Kit_Digital_Agente_Digitalizador_Implementacion_v1 (spec)
- Plan: docs/implementacion/20260318-Plan_Implementacion_Kit_Digital_Agente_Digitalizador_v1_Claude.md
- Directrices v144.0.0, Arquitectura v132.0.0, Indice v173.0.0, Flujo v97.0.0
- 33 directrices verificadas, 12 tests, 112 assertions
