# Auditoría de Gap: Documentos Técnicos vs. Arquitectura Actual
## Jaraba Impact Platform SaaS - Enero 2026

**Fecha de creación:** 2026-01-15 19:25  
**Última actualización:** 2026-01-15 19:25  
**Autor:** IA Asistente  
**Versión:** 1.0.0

---

## 📑 Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Documentos Analizados](#2-documentos-analizados)
3. [Estado Actual de la Arquitectura](#3-estado-actual-de-la-arquitectura)
4. [Análisis de Gap por Área](#4-análisis-de-gap-por-área)
5. [Priorización de Implementación](#5-priorización-de-implementación)
6. [Riesgos y Dependencias](#6-riesgos-y-dependencias)
7. [Recomendaciones](#7-recomendaciones)
8. [Próximos Pasos](#8-próximos-pasos)
9. [Registro de Cambios](#9-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Esta auditoría analiza el **gap** entre los requisitos especificados en los **11 documentos técnicos nuevos** (fecha 20260115) ubicados en `/docs/tecnicos/` y el **estado actual** de la arquitectura de negocio y técnica del SaaS.

> **⚠️ IMPORTANTE**: Se identifican **gaps críticos** en 4 áreas principales que requieren implementación para cumplir con la visión Q1 2027: nuevos verticales, agentes autónomos avanzados, entidades financieras especializadas y sistema de diagnóstico TTV < 60s.

---

## 2. Documentos Analizados

| Documento | Tipo | Requisitos Clave |
|-----------|------|------------------|
| `20260115b-Diagnostico_Express_TTV_Especificacion_Tecnica_Claude.md` | Especificación Técnica | TTV < 45s, Value-First Onboarding, Componentes React, ECA Post-Registro |
| `20260115c-Calculadora_Madurez_Digital_Especificacion_Tecnica_Claude.md` | Especificación Técnica | Estimación económica (€/año perdidos), 4 preguntas, Maturity Score 0-10 |
| `20260115d-Ecosistema Jaraba_ Estrategia de Verticalización y Precios_Gemini.md` | Estrategia de Negocio | 2 verticales Premium, Pricing €29-99/mes, Agentes Autónomos |
| `20260115e-SaaS Verticales_ Empleabilidad y Emprendimiento IA_Gemini.md` | Análisis de Mercado | Benchmarks competitivos, Outcome-Based Pricing, AI Agent Pricing |
| `20260115f-01_Core_Entidades_Esquema_BD_v1_Claude.md` | Esquema BD | 6 entidades nuevas |
| `20260115f-02_Core_Modulos_Personalizados_v1_Claude.md` | Arquitectura Módulos | 12 módulos custom |
| `20260115f-03_Core_APIs_Contratos_v1_Claude.md` | Especificación API | REST API v1, OAuth 2.0 + API Keys, Webhooks HMAC |
| `20260115f-04_Core_Permisos_RBAC_v1_Claude.md` | Sistema RBAC | 5 roles plataforma, 5 roles tenant, 5 avatares verticales |
| `20260115f-05_Core_Theming_jaraba_theme_v1_Claude.md` | Sistema Frontend | 4 capas CSS, Visual Picker, WCAG 2.1 AA |
| `20260115f-06_Core_Flujos_ECA_v1_Claude.md` | Automatización | 12 flujos ECA |
| `20260115f-07_Core_Configuracion_MultiTenant_v1_Claude.md` | Configuración MT | Group Module 3.0, Domain Access, TenantContextService |

---

## 3. Estado Actual de la Arquitectura

| Componente | Estado | Documentación |
|------------|--------|---------------|
| **Nivel de Madurez** | ✅ 5.0/5.0 Certificado | `maturity_assessment_20260111.md` |
| **Módulo Core** | ✅ `ecosistema_jaraba_core` | Entidades: Vertical, SaasPlan, Tenant |
| **Multi-Tenencia** | ✅ Group Module + Domain Access | Soft Multi-Tenancy operativo |
| **Monetización** | ✅ Reverse Trial + Auto-Downgrade | Via `ReverseTrialService` |
| **PWA** | ✅ Service Worker + Manifest | Mobile-First implementado |
| **Telemetría IA** | ✅ `AITelemetryService` | Registro de invocaciones, costes, tokens |
| **FinOps** | ✅ v3.0 | Dashboard implementado |
| **API-First** | 🟡 Parcial | OpenAPI 3.0 en desarrollo |
| **Agentes IA** | ✅ 10 agentes | AgroConecta (Copilot, Marketing, CX, etc.) |

---

## 4. Análisis de Gap por Área

### 4.1 🔴 Gaps Críticos

#### 4.1.1 Nuevos Verticales de Negocio
| Requisito | Estado Actual | Gap |
|-----------|---------------|-----|
| **Vertical Empleabilidad Digital** (Avatar: Lucía +45) | ❌ No existe | Requiere vertical, features, agentes específicos |
| **Vertical Emprendimiento Rural** (Avatar: Javier) | ❌ No existe | Requiere vertical, features, agentes específicos |
| Pricing €29-49/mes Empleabilidad | ❌ No configurado | Requiere SaasPlan específico |
| Pricing €49-99/mes Emprendimiento | ❌ No configurado | Requiere SaasPlan específico |

#### 4.1.2 Sistema de Diagnóstico Express (TTV < 60s)
| Requisito | Estado Actual | Gap |
|-----------|---------------|-----|
| Entidad `diagnostic_express_result` | ❌ No existe | Crear Content Entity |
| Motor de Scoring JS (client-side) | ❌ No existe | Implementar `scoring-engine.js` |
| Componentes React | ❌ No existe | Desarrollar biblioteca |
| Flujo ECA `ECA-USR-002` | ❌ No existe | Crear modelo ECA |

#### 4.1.3 Entidades Financieras Especializadas
| Requisito | Estado Actual | Gap |
|-----------|---------------|-----|
| `financial_transaction` (append-only) | ❌ No existe | Crear entidad inmutable |
| `cost_allocation` | ❌ No existe | Crear entidad |
| `foc_metric_snapshot` | ❌ No existe | Crear entidad |

#### 4.1.4 Agentes Autónomos de Nueva Generación
| Requisito | Estado Actual | Gap |
|-----------|---------------|-----|
| **Agente Postulación Autónoma (RPA)** | ❌ No existe | Scraping LinkedIn/InfoJobs |
| **CFO Sintético** | ❌ No existe | Automatización fiscal |
| **Coach IA 24/7** | ❌ No existe | Terapia CBT |

### 4.2 🟢 Alineamiento Existente

| Área | Estado |
|------|--------|
| Multi-Tenancy con Group Module | ✅ Alineado |
| Domain Access para subdominios | ✅ Alineado |
| Theming con variables CSS | ✅ Alineado |
| PWA con Service Worker | ✅ Alineado |
| RAG con Qdrant | ✅ Alineado |

---

## 5. Priorización de Implementación

| Fase | Sprint | Entregables |
|------|--------|-------------|
| **Fase 1** | 1-2 | Entidades financieras, `diagnostic_express_result`, Verticales (pendiente docs) |
| **Fase 2** | 3-4 | Motor scoring JS, Componentes React, Flujos ECA diagnóstico |
| **Fase 3** | 5-6 | API REST v1, OAuth 2.0, Webhooks HMAC |
| **Fase 4** | 7-10 | CFO Sintético, Coach IA, Prototipo RPA |

---

## 6. Riesgos y Dependencias

| Riesgo | Impacto | Mitigación |
|--------|---------|------------|
| Agentes RPA en plataformas externas | 🔴 Alto | LinkedIn/InfoJobs pueden bloquear |
| Complejidad legal de CFO Sintético | 🔴 Alto | No ofrecer asesoría fiscal |

---

## 7. Recomendaciones

1. **No duplicar módulos**: Evolucionar `ecosistema_jaraba_core` en lugar de crear `jaraba_core` nuevo
2. **Reutilizar infraestructura existente**: El `TenantContextService` actual es base sólida
3. **Agentes autónomos por fases**: Comenzar con Coach IA (menor riesgo legal) antes de RPA

---

## 8. Próximos Pasos

1. ✅ Revisar este informe de gap
2. ✅ **Documentación Empleabilidad completada** (17 especificaciones técnicas añadidas)
3. 📋 Proceder con implementación LMS Core y Job Board (Fase 1-2)
4. 📋 Documentar vertical Emprendimiento (pendiente)

---

## 9. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-15 | 1.0.0 | Creación inicial del documento de auditoría |
| 2026-01-16 | 1.1.0 | **Empleabilidad Documentado**: 17 specs técnicas integradas (LMS, Job Board, Matching, CV Builder, Credentials, Dashboards, AI Copilot) |
