# 📚 ÍNDICE GENERAL DE DOCUMENTACIÓN

> **Documento auto-actualizable**: Este índice se mantiene sincronizado con la estructura de carpetas y documentos del proyecto.

**Fecha de creación:** 2026-01-09 15:28
**Última actualización:** 2026-02-18
**Versión:** 70.0.0 (Page Builder Template Consistency — 129 Templates Resynced)

> **🎨 PAGE BUILDER TEMPLATE CONSISTENCY: 129 TEMPLATES RESYNCED** (2026-02-18)
> - **Preview Images:** 88 templates vinculados a sus PNGs existentes + 4 PNGs placeholder creados para serviciosconecta.
> - **Metadatos:** Typo "Seccion seccion" corregido en 15 ficheros, tildes en 59 descripciones verticales, label duplicado corregido.
> - **Preview Data Rico:** 55 templates verticales enriquecidos con datos de dominio (features[], testimonials[], faqs[], stats[], plans[]).
> - **Pipelines Unificados:** Status filter en TemplatePickerController, icon keys en CanvasApiController, categoría default a 'content'.
> - **Drupal 10+ Fix:** `applyUpdates()` eliminado reemplazado en Legal Intelligence update_10004.
> - **Reglas nuevas:** PB-PREVIEW-001, PB-DATA-001, PB-CAT-001, DRUPAL-ENTUP-001.
> - **Aprendizaje #101.** Page Builder Template Consistency & Drupal 10+ Entity Updates.
> - **Directrices v55.0.0, Arquitectura v55.0.0, Flujo v10.0.0, Indice v70.0.0**

> **🔧 CI/CD HARDENING: SECURITY SCAN & DEPLOY ESTABILIZADOS** (2026-02-18)
> - **Trivy Config Fix:** Corregidas claves inválidas (`exclude-dirs` → `scan.skip-dirs`). Exclusiones de vendor/core/contrib ahora operativas.
> - **Deploy Resiliente:** Smoke test con fallback SSH cuando `PRODUCTION_URL` no está configurado.
> - **Reglas nuevas:** CICD-TRIVY-001 (estructura config Trivy), CICD-DEPLOY-001 (fallback en smoke tests).
> - **Aprendizaje #100.** Estabilización Trivy Config y Deploy Smoke Test.
> - **Directrices v54.0.0, Arquitectura v54.0.0, Indice v69.0.0**

> **✅ FASE 5: CONSOLIDACIÓN & ESTABILIZACIÓN (THE GREEN MILESTONE)** (2026-02-18)
> - **Estabilización Masiva:** 370+ tests unitarios corregidos en 17 módulos (Core, IA, Fiscal, Billing, PWA, etc.).
> - **Stack Cumplimiento Fiscal N1:** Integración de `jaraba_privacy`, `jaraba_legal` y `jaraba_dr`. `ComplianceAggregator` operacional.
> - **Refactorización DI:** Soporte para mocking de clases contrib `final` mediante inyección flexible.
> - **Directrices v53.0.0, Arquitectura v53.0.0, Indice v68.0.0**

> **🌌 FASE 4: LA FRONTERA FINAL — BLOQUES O + P (LIVING SAAS)** (2026-02-18)
> - **Bloque O: ZKP Intelligence:** Módulo `jaraba_zkp`. `ZkOracleService` implementado con Privacidad Diferencial (Laplace Noise).
> - **Bloque P: Generative Liquid UI:** Módulo `jaraba_ambient_ux`. `IntentToLayoutService` implementado. La interfaz muta (Crisis/Growth) vía `hook_preprocess_html`.
> - **Estado Final:** Plataforma Soberana Autoadaptativa.
> - **Aprendizaje #99.** Inteligencia ZK y UX Ambiental.
> - **Directrices v52.0.0, Arquitectura v52.0.0, Indice v67.0.0**

> **🤖 FASE 3: LA ECONOMÍA AGÉNTICA IMPLEMENTADA — BLOQUES M + N** (2026-02-18)
> - **Bloque M: Identidad Soberana (DID):** Módulo `jaraba_identity` implementado. Entidad `IdentityWallet` Ed25519.
> - **Bloque N: Mercado de Agentes:** Módulo `jaraba_agent_market` implementado. Protocolo JDTP y Ledger inmutable.
> - **Directrices v51.0.0, Arquitectura v51.0.0, Indice v66.0.0**

...

## 15. Registro de Cambios (Hitos Recientes)

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-18 | **70.0.0** | **Page Builder Template Consistency:** 129 templates resynced (preview_image, metadatos, preview_data rico, pipelines unificados). Fix applyUpdates() Drupal 10+. Aprendizaje #101. |
| 2026-02-18 | 69.0.0 | **CI/CD Hardening:** Fix de config Trivy (scan.skip-dirs) y deploy resiliente con fallback SSH. Reglas CICD-TRIVY-001 y CICD-DEPLOY-001. |
| 2026-02-18 | 68.0.0 | **Unified & Stabilized:** Consolidación final de las 5 fases. Estabilización masiva de tests unitarios e implementación del Stack Fiscal N1. |
| 2026-02-18 | 67.0.0 | **The Living SaaS:** Implementación de Inteligencia ZKP e Interfaz Líquida (Bloques O y P). Plataforma autoadaptativa. |
| 2026-02-18 | 66.0.0 | **Economía Agéntica:** Implementación de DID y Protocolo JDTP (Bloques M y N). |
| 2026-02-18 | 65.0.0 | **SaaS Golden Master:** Consolidación final de orquestación IA y Wallet SOC2. |
