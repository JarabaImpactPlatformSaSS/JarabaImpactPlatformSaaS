# 📋 DIRECTRICES DEL PROYECTO - JarabaImpactPlatformSaaS

> **⚠️ DOCUMENTO MAESTRO**: Este documento debe leerse y memorizarse al inicio de cada conversación o al reanudarla.

**Fecha de creación:** 2026-01-09 15:28  
**Última actualización:** 2026-02-18
**Versión:** 55.0.0 (Page Builder Template Consistency — 129 Templates Resynced)

---

## 📑 Tabla de Contenidos (TOC)

1. [Información General del Proyecto](#1-información-general-del-proyecto)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Arquitectura Multi-tenant](#3-arquitectura-multi-tenant)
4. [Seguridad y Permisos](#4-seguridad-y-permisos)
5. [Principios de Desarrollo](#5-principios-de-desarrollo)
6. [Testing y Calidad](#6-testing-y-calidad)
7. [Estructura de Documentación](#7-estructura-de-documentación)
8. [Convenciones de Nomenclatura](#8-convenciones-de-nomenclatura)
9. [Formato de Documentos](#9-formato-de-documentos)
10. [Flujo de Trabajo de Documentación](#10-flujo-de-trabajo-de-documentación)
11. [Estándares de Código y Comentarios](#11-estándares-de-código-y-comentarios)
12. [Control de Versiones](#12-control-de-versiones)
13. [Procedimientos de Actualización](#13-procedimientos-de-actualización)
14. [Glosario de Términos](#14-glosario-de-términos)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 2. Stack Tecnológico

### 2.1 Backend & Core
- **Lenguaje:** PHP 8.4 (requerido para compatibilidad con Drupal 11).
- **Framework:** Drupal 11.
- **Motor de BD:** MariaDB 11.4+.
- **Caché & Pub/Sub:** Redis 7.4.

---

## 6. Testing y Calidad (Actualizado 2026-02-18)

| Norma | ID | Descripción | Prioridad |
|-------|----|-------------|-----------|
| **Mocking de Clases Final** | TEST-MOCK-001 | Las clases `final` (ej. AiProviderPluginManager) NO pueden mockearse directamente. Se DEBE inyectar como `object` en constructores y usar interfaces temporales en los tests. | P0 |
| **Namespaces en Tests** | TEST-NS-001 | Las interfaces de mock temporales DEBEN envolverse en `if (!interface_exists(...))` para evitar colisiones durante la ejecución masiva de suites. | P0 |
| **Metadatos de Caché** | TEST-CACHE-001 | Todo mock de entidad en tests de AccessControl DEBE implementar `getCacheContexts`, `getCacheTags` y `getCacheMaxAge` para evitar fallos de tipo en core. | P1 |
| **Firma Digital XML** | TEST-XML-001 | Las aserciones sobre XML con namespaces DEBEN usar XPath en lugar de comparaciones de cadenas literales para evitar fragilidad por prefijos. | P1 |
| **Trivy Config YAML** | CICD-TRIVY-001 | Las claves `skip-dirs` y `skip-files` en trivy.yaml DEBEN estar anidadas bajo el bloque `scan:`. Claves al nivel raíz o con nombres incorrectos (`exclude-dirs`) son ignoradas silenciosamente. Verificar en logs CI que el conteo de archivos es coherente. | P0 |
| **Deploy Smoke Fallback** | CICD-DEPLOY-001 | Todo smoke test que dependa de un secret de URL DEBE implementar un fallback (SSH/Drush) antes de fallar. Usar `::warning::` cuando el fallback tiene éxito, no `::error::`. | P1 |
| **Preview Image en Templates** | PB-PREVIEW-001 | Todo YAML de PageTemplate DEBE incluir `preview_image` apuntando al PNG correspondiente. Convención: `id` con guiones → `/modules/custom/jaraba_page_builder/images/previews/{id-con-guiones}.png`. | P0 |
| **Preview Data Rico** | PB-DATA-001 | Los `preview_data` de templates verticales DEBEN incluir arrays con 3+ items representativos del dominio (features, testimonials, faqs, stats, etc.), no solo campos genéricos. | P1 |
| **Categoría Default Unificada** | PB-CAT-001 | La categoría por defecto de PageTemplate DEBE ser `'content'` en las 3 fuentes: `PageTemplate.php`, `CanvasApiController`, `TemplateRegistryService`. | P1 |
| **Drupal 10+ Entity Updates** | DRUPAL-ENTUP-001 | `EntityDefinitionUpdateManager::applyUpdates()` fue eliminado en Drupal 10+. Usar `installFieldStorageDefinition()` / `updateFieldStorageDefinition()` explícitamente en update hooks. | P0 |

---

## 15. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-18 | **55.0.0** | **Page Builder Template Consistency:** Resync de 129 templates con preview_image, metadatos corregidos (tildes, labels), preview_data rico para 55 verticales, pipelines Canvas/Picker unificados. Fix de `applyUpdates()` en Legal Intelligence. Reglas PB-PREVIEW-001, PB-DATA-001, PB-CAT-001, DRUPAL-ENTUP-001. |
| 2026-02-18 | 54.0.0 | **CI/CD Hardening:** Fix de config Trivy (`scan.skip-dirs`), deploy resiliente con fallback SSH. Nuevas reglas CICD-TRIVY-001 y CICD-DEPLOY-001. |
| 2026-02-18 | 53.0.0 | **The Unified & Stabilized SaaS:** Consolidación final de las 5 fases. Estabilización de 370+ tests en 17 módulos. Refactorización masiva de DI para clases final de IA y estandarización de mocks para PHPUnit 11. |
| 2026-02-18 | 52.0.0 | **The Living SaaS:** Implementación de las fronteras finales. Bloque O (ZKP) y Bloque P (Liquid UI). Nuevas reglas de privacidad matemática y adaptabilidad de interfaz. |
| 2026-02-18 | 51.0.0 | Economía Agéntica Implementada: Bloques M y N completados. |
| 2026-02-18 | 50.0.0 | SaaS Golden Master Candidate: Consolidación final de todos los bloques. |
