# 📋 DIRECTRICES DEL PROYECTO - JarabaImpactPlatformSaaS

> **⚠️ DOCUMENTO MAESTRO**: Este documento debe leerse y memorizarse al inicio de cada conversación o al reanudarla.

**Fecha de creación:** 2026-01-09 15:28  
**Última actualización:** 2026-02-18  
**Versión:** 52.0.0 (The Living SaaS — ZKP Oracle + Generative Liquid UI)

---

## 📑 Tabla de Contenidos (TOC)

1. [Información General del Proyecto](#1-información-general-del-proyecto)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Arquitectura Multi-tenant](#3-arquitectura-multi-tenant)
4. [Seguridad y Permisos](#4-seguridad-y-permisos)
5. [Principios de Desarrollo](#5-principios-de-desarrollo)
6. [Entornos de Desarrollo](#6-entornos-de-desarrollo)
7. [Estructura de Documentación](#7-estructura-de-documentación)
8. [Convenciones de Nomenclatura](#8-convenciones-de-nomenclatura)
9. [Formato de Documentos](#9-formato-de-documentos)
10. [Flujo de Trabajo de Documentación](#10-flujo- de-trabajo-de-documentación)
11. [Estándares de Código y Comentarios](#11-estándares-de-código-y-comentarios)
12. [Control de Versiones](#12-control-de-versiones)
13. [Procedimientos de Actualización](#13-procedimientos-de-actualización)
14. [Glosario de Términos](#14-glosario-de-términos)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 1. Información General del Proyecto

### 1.1 Nombre del Proyecto
**JarabaImpactPlatformSaaS**

### 1.3 Visión
Evolucionar hacia un **SaaS como Organismo Vivo**, capaz de adaptarse autónomamente al contexto del usuario y del mercado mediante inteligencia colectiva privada.

### 1.4 Módulos Principales (Nuevas Fronteras)
- **Zero-Knowledge Intelligence** ⭐: Oráculo de mercado (`jaraba_zkp`) que ofrece benchmarks sectoriales sin procesar datos privados crudos (Differential Privacy).
- **Generative Liquid UI** ⭐: Interfaz ambiental (`jaraba_ambient_ux`) que muta su layout y componentes en runtime basándose en el estado de salud y riesgo del Tenant.
- **AI Agents Orchestration** ⭐: Sistema autónomo basado en `AgentToolRegistry`.
- **Intelligent Billing & Wallet** ⭐: Ledger Inmutable SOC2.

---

## 4. Seguridad y Permisos

### 4.9 Privacidad de Datos y ZKP (2026-02-18)

| Directriz | ID | Descripción | Prioridad |
|-----------|-----|-------------|-----------|
| **Ruido de Laplace (ZKP)** | PRIVACY-ZKP-001 | Todo dato compartido para inteligencia colectiva DEBE ser procesado por el oráculo de privacidad diferencial añadiendo ruido de Laplace antes de la agregación | P0 |
| **Mutación Auditada** | UX-LIQUID-001 | Los cambios dinámicos en la interfaz (Liquid UI) DEBEN ser trazables en el `AuditLog` para asegurar que la ayuda contextual no oculte información legal obligatoria | P1 |

---

## 15. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-18 | **52.0.0** | **The Living SaaS:** Implementación de las fronteras finales. Bloque O (ZKP) y Bloque P (Liquid UI). Nuevas reglas de privacidad matemática y adaptabilidad de interfaz. |
| 2026-02-18 | 51.0.0 | Economía Agéntica Implementada: Bloques M y N completados. |
| 2026-02-18 | 50.0.0 | SaaS Golden Master Candidate: Consolidación final de todos los bloques. |
