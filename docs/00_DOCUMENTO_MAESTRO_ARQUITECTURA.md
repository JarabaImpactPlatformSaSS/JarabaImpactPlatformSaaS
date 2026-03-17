# 🏗️ DOCUMENTO MAESTRO DE ARQUITECTURA
## Jaraba Impact Platform SaaS v74.0

**Fecha:** 2026-03-16
**Versión:** 127.0.0 (Profile Hub + Avatar Access Checker + Entrepreneur Dashboard Premium + aprendizaje #189)
**Estado:** Verticales Componibles (addon_type=vertical + TenantVerticalService) + Tenant Settings Hub (6 secciones tagged) + Stripe Sync Bidireccional + Landing Elevation 3 Niveles + Claude Code DX Pipeline + Meta-Sitios 3 Idiomas (ES+EN+PT-BR) + Secrets Remediation (SECRET-MGMT-001) + Analytics Stack Completo + Auditoria IA 30/30 (100/100) + AI Stack Clase Mundial (33 items) + Streaming Real + MCP Server + Native Function Calling + Produccion
**Nivel de Madurez:** 5.0 / 5.0 (Resiliencia & Cumplimiento Certificado)

---
## 📑 Tabla de Contenidos

1. [Visión del Proyecto](#1-visión-del-proyecto)
2. [Propuesta de Valor](#2-propuesta-de-valor)
3. [Arquitectura de Alto Nivel](#3-arquitectura-de-alto-nivel)
4. [Stack Tecnológico](#4-stack-tecnológico)
5. [Modelo Multi-Tenant](#5-modelo-multi-tenant)
6. [Modelo de Datos](#6-modelo-de-datos)
7. [Módulos del Sistema](#7-módulos-del-sistema)
8. [Inteligencia Artificial](#8-inteligencia-artificial)
9. [Seguridad y Cumplimiento](#9-seguridad-y-cumplimiento)
10. [Infraestructura y DevOps](#10-infraestructura-y-devops)
11. [Roadmap](#11-roadmap)
12. [Estado de Auditoría (2026-02-13)](#12-estado-de-auditoría-2026-02-13)

---
13. [Referencias](#-referencias)
14. [Plan Maestro v3.0](#-plan-maestro-v30-auditoría-2026-01-23)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 1. Visión del Proyecto

### 1.1 ¿Qué es Jaraba Impact Platform?

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                         │
│   🌱 JARABA IMPACT PLATFORM                                             │
│                                                                         │
│   "La primera plataforma de comercio diseñada para que                  │
│    la Inteligencia Artificial venda tus productos"                      │
│                                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                                                                  │  │
│   │   Productores    ──▶    Plataforma    ──▶    Consumidores       │  │
│   │   Locales              AI-First            Conscientes          │  │
│   │                                                                  │  │
│   │   🧑‍🌾 🫒 🍷 🧀        🤖 💻 📊           👥 🛒 💚              │  │
│   │                                                                  │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Problema que Resuelve

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        ANTES vs DESPUÉS                                  │
├────────────────────────────┬────────────────────────────────────────────┤
│         ANTES              │              DESPUÉS                        │
│                            │                                             │
│  ❌ Productores aislados   │  ✅ Ecosistema conectado                    │
│  ❌ Sin presencia digital  │  ✅ Tienda online + IA                      │
│  ❌ Marketing inexistente  │  ✅ Agentes IA de marketing                 │
│  ❌ Sin trazabilidad       │  ✅ Blockchain + certificados               │
│  ❌ Ventas limitadas       │  ✅ GEO: IA vende por ti                    │
│  ❌ Gestión manual         │  ✅ Automatización completa                 │
│                            │                                             │
└────────────────────────────┴────────────────────────────────────────────┘
```

---

## 2. Propuesta de Valor

### 2.1 Para Productores

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     BENEFICIOS PARA PRODUCTORES                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   🛒 TIENDA ONLINE                                                      │
│   └── E-commerce completo sin conocimientos técnicos                    │
│       └── Drupal Commerce 3.x con pagos Stripe                          │
│                                                                         │
│   🤖 AGENTES IA                                                         │
│   ├── 📝 Storytelling: Genera biografías y descripciones                │
│   ├── 📣 Marketing: Campañas automáticas en redes sociales              │
│   ├── 💬 Customer Experience: Recordatorios y upselling                 │
│   └── 📊 Analytics: Insights de negocio                                 │
│                                                                         │
│   📜 CERTIFICACIÓN                                                      │
│   ├── Firma digital con FNMT/AutoFirma                                  │
│   ├── Trazabilidad desde origen                                         │
│   └── Anclaje blockchain (opcional)                                     │
│                                                                         │
│   🎨 MARCA PROPIA                                                       │
│   └── Personalización visual por productor/cooperativa                  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Para Consumidores

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     BENEFICIOS PARA CONSUMIDORES                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   🔍 TRANSPARENCIA                                                      │
│   └── Conoce el origen exacto de cada producto                          │
│                                                                         │
│   📱 TRAZABILIDAD                                                       │
│   └── Escanea QR y ve todo el recorrido del producto                    │
│                                                                         │
│   🌍 IMPACTO                                                            │
│   └── Apoya directamente a productores locales                          │
│                                                                         │
│   🤝 CONFIANZA                                                          │
│   └── Certificados digitales verificables                               │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.3 Estrategia GEO (Generative Engine Optimization)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              GEO                                         │
│           "Optimización para Motores Generativos (IA)"                   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   SEO Tradicional:                  GEO (Nuevo):                        │
│   ┌──────────────┐                  ┌──────────────┐                    │
│   │   Google     │                  │   ChatGPT    │                    │
│   │   Ranking    │                  │   Perplexity │                    │
│   │   Keywords   │                  │   Claude     │                    │
│   └──────────────┘                  │   Gemini     │                    │
│                                     └──────────────┘                    │
│                                                                         │
│   ¿Cómo funciona GEO?                                                   │
│                                                                         │
│   Usuario: "¿Dónde comprar aceite de oliva premium en Jaén?"            │
│                        │                                                │
│                        ▼                                                │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │ ChatGPT lee nuestra web (Answer Capsules + Schema.org)           │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                        │                                                │
│                        ▼                                                │
│   "Te recomiendo la Cooperativa de Jaén en jarabaagroconecta.com,       │
│    tienen aceite virgen extra con certificación de origen..."          │
│                                                                         │
│   🎯 LA IA VENDE POR NOSOTROS                                           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Arquitectura de Alto Nivel

### 3.1 Diagrama de Contexto (C4 Level 1)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         DIAGRAMA DE CONTEXTO                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│                              USUARIOS                                    │
│                                                                         │
│        👤              👤              👤              👤               │
│    Administrador   Gestor Tenant   Productor       Cliente              │
│         │               │              │               │                │
│         └───────────────┴──────────────┴───────────────┘                │
│                                 │                                       │
│                                 ▼                                       │
│         ┌───────────────────────────────────────────────┐               │
│         │                                               │               │
│         │      🏢 JARABA IMPACT PLATFORM                │               │
│         │         Drupal 11 + Commerce 3.x              │               │
│         │         Single-Instance Multi-Tenant          │               │
│         │                                               │               │
│         └───────────────────────────────────────────────┘               │
│                                 │                                       │
│         ┌───────────┬──────────┼──────────┬───────────┐                 │
│         ▼           ▼          ▼          ▼           ▼                 │
│    ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐         │
│    │ 💳      │ │ 🤖      │ │ 📜      │ │ 🧠      │ │ ⛓️      │         │
│    │ Stripe  │ │ OpenAI  │ │ FNMT    │ │ Qdrant  │ │ EBSI    │         │
│    │ Connect │ │ Claude  │ │ AutoFirma│ │ VectorDB│ │ Blockchain│       │
│    └─────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘         │
│                                                                         │
│                        SISTEMAS EXTERNOS                                 │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Diagrama de Contenedores (C4 Level 2)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      DIAGRAMA DE CONTENEDORES                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌────────────────────────────────────────────────────────────────────┐│
│  │                         FRONTEND                                    ││
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              ││
│  │  │ 🎨 Twig      │  │ 🛒 Commerce  │  │ 💬 Chat IA   │              ││
│  │  │ Templates    │  │ Storefront   │  │ Widget       │              ││
│  │  └──────────────┘  └──────────────┘  └──────────────┘              ││
│  └────────────────────────────────────────────────────────────────────┘│
│                                    │                                    │
│                                    ▼                                    │
│  ┌────────────────────────────────────────────────────────────────────┐│
│  │                        BACKEND DRUPAL 11                            ││
│  │                                                                     ││
│  │  ┌──────────────────────────────────────────────────────────────┐  ││
│  │  │                    MÓDULOS CORE                               │  ││
│  │  │  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐ │  ││
│  │  │  │ecosistema_ │ │ jaraba_    │ │ jaraba_    │ │ jaraba_    │ │  ││
│  │  │  │jaraba_core │ │ commerce   │ │ rag        │ │ social     │ │  ││
│  │  │  └────────────┘ └────────────┘ └────────────┘ └────────────┘ │  ││
│  │  └──────────────────────────────────────────────────────────────┘  ││
│  │                                                                     ││
│  │  ┌──────────────────────────────────────────────────────────────┐  ││
│  │  │                    SERVICIOS                                  │  ││
│  │  │  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐ │  ││
│  │  │  │ Tenant     │ │ Plan       │ │ KB Indexer │ │ Agent      │ │  ││
│  │  │  │ Manager    │ │ Validator  │ │ Service    │ │ Orchestrator││  ││
│  │  │  └────────────┘ └────────────┘ └────────────┘ └────────────┘ │  ││
│  │  └──────────────────────────────────────────────────────────────┘  ││
│  └────────────────────────────────────────────────────────────────────┘│
│                                    │                                    │
│         ┌──────────────────────────┼──────────────────────────┐        │
│         ▼                          ▼                          ▼        │
│  ┌──────────────┐          ┌──────────────┐          ┌──────────────┐  │
│  │ 🗄️ MariaDB   │          │ 🔴 Redis     │          │ 🧠 Qdrant    │  │
│  │ Database     │          │ Cache        │          │ VectorDB     │  │
│  └──────────────┘          └──────────────┘          └──────────────┘  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

### 3.6 Stack de Cumplimiento Fiscal N1 ⭐
Integración unificada de soberanía legal y resiliencia técnica:
- **Soberanía de Datos (jaraba_privacy)**: Gestión automatizada de DPA y ARCO-POL SLA.
- **Transparencia Contractual (jaraba_legal)**: ToS Lifecycle y monitorización de SLA real.
- **Resiliencia & Recuperación (jaraba_dr)**: Verificación de backups SHA-256 y orquestación de DR Tests.

---

---

## 4. Stack Tecnológico

### 4.1 Tecnologías Principales

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        STACK TECNOLÓGICO                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   BACKEND                                                               │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  Drupal 11.x      │  PHP 8.4+         │  Composer 2.x          │  │
│   │  CMS + Framework  │  Lenguaje         │  Dependencias          │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   E-COMMERCE                                                            │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  Commerce 3.x     │  Stripe Connect   │  Product Bundles       │  │
│   │  Drupal Nativo    │  Split Payments   │  Variaciones           │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   BASE DE DATOS                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  MariaDB 10.11    │  Redis 7.x        │  Qdrant 1.16           │  │
│   │  Relacional       │  Cache/Queue      │  Vector DB             │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   INTELIGENCIA ARTIFICIAL                                               │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  OpenAI           │  Anthropic        │  Google AI             │  │
│   │  GPT-4 / Embeddings│  Claude          │  Gemini                │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   INFRAESTRUCTURA                                                       │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  IONOS (Prod)     │  Lando (Dev)      │  GitHub Actions        │  │
│   │  Hosting          │  Local Docker     │  CI/CD                 │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Modelo Multi-Tenant

### 5.1 Jerarquía del Ecosistema

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      MODELO MULTI-TENANT                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│                    🏢 PLATAFORMA JARABA                                  │
│                    (Single-Instance Drupal)                              │
│                              │                                          │
│          ┌──────────────────┼──────────────────┐                        │
│          ▼                  ▼                  ▼                        │
│   ┌────────────┐    ┌────────────┐    ┌────────────┐                   │
│   │ 🌾 VERTICAL │    │ 📚 VERTICAL│    │ 🏔️ VERTICAL│                   │
│   │ AgroConecta│    │ FormaTech  │    │ TurismoLocal│                  │
│   │            │    │            │    │             │                   │
│   │ Productores│    │ Formación  │    │ Experiencias│                  │
│   │ Agrícolas  │    │ Online     │    │ Turísticas  │                   │
│   └──────┬─────┘    └────────────┘    └─────────────┘                   │
│          │                                                              │
│   ┌──────┴──────────────────────────┐                                   │
│   ▼                                 ▼                                   │
│   ┌────────────┐            ┌────────────┐                              │
│   │ 🏛️ TENANT   │            │ 🏛️ TENANT   │                              │
│   │ Cooperativa│            │ D.O.       │                              │
│   │ Jaén       │            │ La Mancha  │                              │
│   └──────┬─────┘            └────────────┘                              │
│          │                                                              │
│   ┌──────┴──────────────────────────┐                                   │
│   ▼                                 ▼                                   │
│   ┌────────────┐            ┌────────────┐                              │
│   │ 🧑‍🌾 PRODUCTOR│            │ 🧑‍🌾 PRODUCTOR│                              │
│   │ Finca      │            │ Bodega     │                              │
│   │ El Olivo   │            │ La Viña    │                              │
│   └────────────┘            └────────────┘                              │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.2 Aislamiento de Datos

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    ESTRATEGIA DE AISLAMIENTO                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Single-Instance + Group Module (NO multisite)                         │
│                                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                    BASE DE DATOS ÚNICA                           │  │
│   │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐            │  │
│   │  │ Tenant A│  │ Tenant B│  │ Tenant C│  │ Shared  │            │  │
│   │  │ (Group) │  │ (Group) │  │ (Group) │  │ Config  │            │  │
│   │  └─────────┘  └─────────┘  └─────────┘  └─────────┘            │  │
│   │       ↑            ↑            ↑            ↑                  │  │
│   │       └────────────┴────────────┴────────────┘                  │  │
│   │                         │                                        │  │
│   │                  Group Content                                   │  │
│   │               (Aislamiento Lógico)                               │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   VENTAJAS:                                                             │
│   ✅ Efecto red: Queries cruzadas (matching talento ↔ empresas)        │
│   ✅ Mantenimiento: 1 actualización para todos                          │
│   ✅ Escalabilidad: Sin límite de tenants                               │
│   ✅ Datos compartidos: Taxonomías, catálogos                          │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 6. Modelo de Datos

### 6.1 Entidades Principales

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      MODELO DE ENTIDADES                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                        VERTICAL                                  │  │
│   │  id, nombre, slug, descripción, tema_base                        │  │
│   │  features[] (referencia a Feature Config Entity)                 │  │
│   └───────────────────────────┬─────────────────────────────────────┘  │
│                               │ 1:N                                     │
│                               ▼                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                         TENANT                                   │  │
│   │  id, nombre, dominio, vertical_id (FK)                           │  │
│   │  plan_id (FK → SaasPlan), group_id, domain_id                    │  │
│   │  configuración (logo, colores, credenciales)                     │  │
│   └───────────────────────────┬─────────────────────────────────────┘  │
│                               │ 1:N                                     │
│                               ▼                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                       PRODUCTOR                                  │  │
│   │  id, nombre, email, teléfono                                     │  │
│   │  tenant_id (FK), user_id (FK Drupal)                             │  │
│   │  estado (activo|suspendido|pendiente)                            │  │
│   └───────────────────────────┬─────────────────────────────────────┘  │
│                               │ 1:N                                     │
│                               ▼                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                     COMMERCE PRODUCT                             │  │
│   │  id, título, descripción, SKU                                    │  │
│   │  precio, stock, imágenes                                         │  │
│   │  productor_id (FK), categorías                                   │  │
│   └───────────────────────────┬─────────────────────────────────────┘  │
│                               │ 1:N                                     │
│                               ▼                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                          LOTE                                    │  │
│   │  id, código_trazabilidad, producto_id (FK)                       │  │
│   │  fecha_producción, origen, hash_blockchain                       │  │
│   └───────────────────────────┬─────────────────────────────────────┘  │
│                               │ 1:N                                     │
│                               ▼                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                      CERTIFICADO                                 │  │
│   │  id, tipo, lote_id (FK), fecha_emisión                           │  │
│   │  firma_digital, validez, documento_pdf                           │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Planes SaaS

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        PLANES SAAS                                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   ┌────────────────┬────────────────┬────────────────┐                 │
│   │    BÁSICO      │  PROFESIONAL   │   ENTERPRISE   │                 │
│   │    €49/mes     │    €149/mes    │    €499/mes    │                 │
│   ├────────────────┼────────────────┼────────────────┤                 │
│   │                │                │                │                 │
│   │ 10 productores │ 50 productores │ ∞ productores  │                 │
│   │ 5 GB storage   │ 25 GB storage  │ 100 GB storage │                 │
│   │ 1 dominio      │ 3 dominios     │ ∞ dominios     │                 │
│   │                │                │                │                 │
│   │ ❌ Agentes IA  │ ✅ Agentes IA  │ ✅ Agentes IA  │                 │
│   │ ❌ Firma FNMT  │ ✅ Firma FNMT  │ ✅ Firma FNMT  │                 │
│   │ ❌ Blockchain  │ ❌ Blockchain  │ ✅ Blockchain  │                 │
│   │ ❌ White-label │ ❌ White-label │ ✅ White-label │                 │
│   │                │                │                │                 │
│   │ SLA: 99.5%     │ SLA: 99.9%     │ SLA: 99.95%    │                 │
│   │ Soporte: Email │ Soporte: Chat  │ Soporte: 24/7  │                 │
│   │                │                │                │                 │
│   └────────────────┴────────────────┴────────────────┘                 │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 7. Módulos del Sistema

### 7.1 Módulos Core & Inteligencia

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      MÓDULOS DE INTELIGENCIA                             │
├─────────────────────────────────────────────────────────────────────────┤
...
│   📦 jaraba_ai_agents (v2.0) ⭐                                         │
│   ├── BaseAgent: Clase abstracta con DI flexible (Mock-ready)           │
│   │   └── buildSystemPrompt(): Inyecta regla identidad (parte #0)      │
│   ├── AgentOrchestrator: Enrutamiento dinámico de intenciones           │
│   └── JarabaLexCopilot: Asistente jurídico especializado                │
│                                                                         │
│   🛡️ AI IDENTITY ENFORCEMENT (AI-IDENTITY-001 + AI-COMPETITOR-001)     │
│   ├── BaseAgent.buildSystemPrompt(): Regla identidad como parte #0     │
│   │   (heredada por 14+ agentes: Emprendimiento, Empleabilidad,        │
│   │   JarabaLex, Legal, Sales, Merchant, Producer, Marketing, etc.)    │
│   ├── CopilotOrchestratorService.buildSystemPrompt(): $identityRule    │
│   │   antepuesto a los 8 modos (coach→landing_copilot)                 │
│   ├── PublicCopilotController: IDENTIDAD INQUEBRANTABLE en prompt      │
│   ├── FaqBotService: Regla en ambos prompts (KB + plataforma)          │
│   ├── ServiciosConectaCopilotAgent: Antepuesto a getSystemPromptFor()  │
│   ├── CoachIaService: Antepuesto a generateCoachingPrompt()            │
│   └── AiContentController: Identidad "copywriter de Jaraba"           │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│              AI REMEDIATION STACK (28 FIXES, 3 PHASES) ⭐              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   🔒 AIIdentityRule (Centralizada, FIX-001)                            │
│   ├── ecosistema_jaraba_core/src/AI/AIIdentityRule.php                │
│   ├── static apply(string $prompt): string — inyecta regla identidad  │
│   ├── Consumida por: BaseAgent, SmartBaseAgent, CopilotOrchestrator,  │
│   │   PublicCopilotController, FaqBotService, CoachIaService, etc.    │
│   └── Reemplaza 14+ copias duplicadas de la regla de identidad        │
│                                                                         │
│   🛡️ AIGuardrailsService (Pipeline Completo, FIX-003 + FIX-028)      │
│   ├── 4 acciones: ALLOW, MODIFY, BLOCK, FLAG                         │
│   ├── checkPII(): DNI, NIE, IBAN ES, NIF/CIF, +34 (FIX-028)        │
│   ├── BLOCKED_PATTERNS: IBAN ES pattern anadido                       │
│   ├── RAG injection filter (FIX-009): detecta prompt injection        │
│   └── Integrado en SmartBaseAgent.execute() pipeline                   │
│                                                                         │
│   🧠 SmartBaseAgent (Contrato Restaurado, FIX-004)                    │
│   ├── Pipeline: guardrails → model routing → execute → observability  │
│   ├── ModelRouterService: 3 tiers (fast/balanced/premium)             │
│   │   ├── Regex bilingue EN+ES para assessComplexity() (FIX-019)     │
│   │   ├── Pricing en YAML config (FIX-020):                          │
│   │   │   └── jaraba_ai_agents.model_routing.yml (hot-updatable)     │
│   │   └── Modelos: Haiku 4.5 / Sonnet 4.6 / Opus 4.6               │
│   ├── AIObservabilityService: tracking completo (FIX-021)             │
│   │   ├── BrandVoiceTrainerService: log() en indexExample + refine    │
│   │   └── WorkflowExecutorService: log() en success + failure paths  │
│   └── @? UnifiedPromptBuilder: DI opcional (FIX-026)                  │
│                                                                         │
│   📊 AIOpsService (Metricas Reales, FIX-022)                          │
│   ├── getResourceMetrics(): /proc/stat, /proc/meminfo, disk_free     │
│   ├── getLatencyTrend(): AVG/P95 desde ai_telemetry table            │
│   ├── getErrorTrend(): error rates desde watchdog table               │
│   └── getCurrentMonthlyCost(): SUM(cost_estimated) desde ai_telemetry│
│                                                                         │
│   🔄 Streaming SSE (FIX-006 + FIX-024)                                │
│   ├── MIME: text/event-stream (no text/plain)                         │
│   ├── Eventos: mode, thinking, chunk, done, error                     │
│   ├── Chunking semantico: splitIntoParagraphs() (no 80-char)         │
│   └── streaming_mode: 'buffered' en done event                        │
│                                                                         │
│   📁 Canonical Verticals (FIX-027): 10 nombres canonicos             │
│   ├── empleabilidad, emprendimiento, comercioconecta, agroconecta    │
│   ├── jarabalex, serviciosconecta, andalucia_ei                      │
│   ├── jaraba_content_hub, formacion, demo                             │
│   └── Aliases legacy: comercio_conecta→comercioconecta, etc.         │
│                                                                         │
│   📝 Agent Generations (FIX-025 → Sprint 5 COMPLETE):                  │
│   ├── Gen 0: MarketingAgent (@deprecated → SmartMarketingAgent)      │
│   ├── Gen 1: MIGRATED — 0 remaining (all promoted to Gen 2)         │
│   └── Gen 2 (11 agents): SmartMarketing, Storytelling,              │
│       CustomerExperience, Support, ProducerCopilot, Sales,           │
│       MerchantCopilot, SmartEmployabilityCopilot,                    │
│       SmartLegalCopilot, SmartContentWriter                          │
│       + LearningPathAgent (Gen 2 in jaraba_lms)                      │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│          AI ELEVATION 10 GAPs — STREAMING + MCP + NATIVE TOOLS ⭐     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   🔄 Streaming Real Token-by-Token (GAP-01)                            │
│   ├── StreamingOrchestratorService extends CopilotOrchestrator         │
│   ├── ChatInput::setStreamedOutput(TRUE) → Provider real streaming     │
│   ├── PHP Generator yield: chunk, cached, done, error                  │
│   ├── Buffer 80 chars + PII masking incremental (GAP-03/10)           │
│   ├── Paridad con buffered: temp 0.3/0.7, PII, circuit breaker tenant │
│   ├── Semantic cache composite mode (mode:current_page)                │
│   └── Fallback automatico a buffered si servicio no disponible         │
│                                                                         │
│   🔧 Native Function Calling API-Level (GAP-09)                        │
│   ├── ToolRegistry::generateNativeToolsInput() → ToolsInput           │
│   │   └── ToolsFunctionInput > ToolsPropertyInput (Drupal AI module)  │
│   ├── SmartBaseAgent::callAiApiWithNativeTools()                       │
│   │   ├── ChatInput::setChatTools(ToolsInput) — API-level tools       │
│   │   ├── ChatMessage::getTools() → ToolsFunctionOutputInterface[]    │
│   │   └── Fallback a callAiApiWithTools() (text-based) si falla      │
│   └── Loop iterativo max 5 (identico al text-based)                   │
│                                                                         │
│   🌐 MCP Server JSON-RPC 2.0 (GAP-08)                                  │
│   ├── POST /api/v1/mcp — McpServerController::handle()                │
│   ├── Metodos: initialize, tools/list, tools/call, ping               │
│   ├── Protocol version: 2025-11-25                                     │
│   ├── PII sanitization en tool output                                  │
│   └── Permiso: use ai agents + CSRF                                   │
│                                                                         │
│   📡 Distributed Tracing (GAP-02)                                       │
│   ├── TraceContextService: trace_id (UUID) + span_id por operacion    │
│   └── Propagado a: observability, cache, guardrails, SSE done event   │
│                                                                         │
│   🧠 Agent Long-Term Memory (GAP-07)                                    │
│   ├── AgentLongTermMemoryService: Qdrant + BD                         │
│   ├── Types: fact, preference, interaction_summary, correction         │
│   └── remember() + recall() en buildSystemPrompt()                     │
│                                                                         │
│   🆕 Sprint 5 — AI Services Clase Mundial (100/100):                    │
│   ├── AgentBenchmarkService: golden datasets + QualityEvaluator       │
│   │   └── AgentBenchmarkResult entity para tracking historico          │
│   ├── PromptVersionService + PromptTemplate ConfigEntity:             │
│   │   └── Version management, rollback, history per prompt            │
│   ├── BrandVoiceProfile ContentEntity: per-tenant brand voice         │
│   │   └── Extends TenantBrandVoiceService con entity persistence      │
│   ├── PersonalizationEngineService: unified recommendation engine     │
│   │   └── Orchestrates 6 source services                              │
│   ├── SemanticCache integration in CopilotOrchestrator:               │
│   │   └── Cache GET before LLM call, SET after response               │
│   └── MultiModalBridgeService COMPLETE:                               │
│       ├── analyzeImage() (GPT-4o Vision)                              │
│       ├── transcribeAudio() (Whisper)                                 │
│       ├── synthesizeSpeech() (TTS-1/TTS-1-HD)                        │
│       └── generateImage() (DALL-E 3)                                  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│         META-SITES ANALYTICS STACK (4 CAPAS) ⭐                    │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📊 Capa 1: Conversion Tracking (metasite-tracking.js)                │
│   ├── 6 behaviors: CTA clicks, form submits, scroll depth,           │
│   │   engagement time, section views, cross-pollination               │
│   └── dataLayer push para cada evento                                │
│                                                                         │
│   🧪 Capa 2: A/B Testing (metasite-experiments.js)                     │
│   ├── Cookie jb_exp_{id} persistente 30 dias, pesos por variante     │
│   ├── DOM manipulation: text/html/style/class/attribute/href         │
│   ├── Impression tracking: POST /api/experiments/{id}/impression     │
│   └── dataLayer: experiment_view, experiment_conversion               │
│                                                                         │
│   🔥 Capa 3: Heatmap (jaraba_heatmap/tracker)                          │
│   ├── heatmap-tracker.js: clicks, scroll, hover                      │
│   ├── HeatmapApiController (backend)                                 │
│   └── Dashboard: /heatmap/analytics                                  │
│                                                                         │
│   🌐 Capa 4: GTM/GA4 (_gtm-analytics.html.twig)                       │
│   ├── Google Consent Mode v2 (GDPR/RGPD defaults conservadores)      │
│   ├── GTM container ID desde theme_settings (configurable)           │
│   ├── GA4 standalone fallback si no hay GTM                          │
│   └── dataLayer context push: meta_site, tenant_id, user_type        │
│                                                                         │
│   🌍 SEO Internacional (_hreflang-meta.html.twig)                      │
│   ├── hreflang tags: es, en, x-default                               │
│   └── Inyectado en html.html.twig <head>                             │
│                                                                         │
│   📦 Activacion: page--page-builder.html.twig                         │
│   ├── Condicionado: {% if meta_site %} (no cargar en dashboard)      │
│   ├── attach_library: ecosistema_jaraba_theme/metasite-tracking      │
│   ├── attach_library: ecosistema_jaraba_theme/metasite-experiments   │
│   └── attach_library: jaraba_heatmap/tracker                        │
│                                                                         │
│   📡 PWA (preexistente)                                                │
│   ├── manifest.json + sw.js ya implementados                        │
│   └── Sin cambios adicionales necesarios                            │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      STACK CUMPLIMIENTO FISCAL                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 ecosistema_jaraba_core (Compliance)                                │
│   ├── ComplianceAggregator: Consolidación de 9 KPIs críticos             │
│   └── FiscalComplianceService: Score 0-100 unificado                    │
│                                                                         │
│   📦 jaraba_billing (Delegation)                                        │
│   └── FiscalInvoiceDelegation: Enrutamiento VeriFactu / Facturae / B2B  │
│                                                                         │
│   📦 jaraba_verifactu (SIF)                                             │
│   ├── HashChainService: Integridad irrefutable SHA-256                  │
│   └── EventLogService: Auditoría append-only RD 1007/2023               │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      CUSTOMER SUCCESS & RETENCIÓN                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_customer_success (v2.0) ⭐                                  │
│   ├── Entidades (7): CustomerHealth, ChurnPrediction, CsPlaybook,      │
│   │   PlaybookExecution, ExpansionSignal, VerticalRetentionProfile,     │
│   │   SeasonalChurnPrediction (append-only)                             │
│   ├── Servicios (8): HealthScoreCalculator, ChurnPrediction,           │
│   │   PlaybookExecutor, EngagementScoring, NpsSurvey, LifecycleStage,  │
│   │   VerticalRetentionService, SeasonalChurnService                    │
│   ├── 5 Perfiles verticales: AgroConecta (cosecha), ComercioConecta    │
│   │   (rebajas), ServiciosConecta (ROI), Empleabilidad (exito),        │
│   │   Emprendimiento (fase)                                             │
│   ├── Dashboard FOC: /customer-success/retention (heatmap estacional)  │
│   ├── 13 Endpoints API REST (6 genericos + 7 verticalizados)           │
│   └── QueueWorker: VerticalRetentionCronWorker (cron diario 03:00)     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      VERTICAL: SERVICIOSCONECTA                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_servicios_conecta (v2.0 — Booking Engine) ⭐               │
│   ├── Entidades (5): ProviderProfile, ServiceOffering, Booking,        │
│   │   AvailabilitySlot, ServicePackage                                  │
│   ├── Servicios (4): ProviderService, ServiceOfferingService,          │
│   │   AvailabilityService (isSlotAvailable, hasCollision,              │
│   │   markSlotBooked, releaseSlot), ReviewService                       │
│   ├── API REST: ServiceApiController (6 endpoints)                     │
│   │   ├── GET  /providers (marketplace listing)                        │
│   │   ├── GET  /providers/{id} (detail + offerings)                    │
│   │   ├── GET  /offerings (listing)                                    │
│   │   ├── GET  /providers/{id}/availability (slots)                    │
│   │   ├── POST /bookings (create with validation)                      │
│   │   └── PATCH /bookings/{id} (state machine transitions)            │
│   ├── State Machine: pending_confirmation → confirmed →                │
│   │   completed / cancelled_client / cancelled_provider / no_show      │
│   ├── Cron: auto-cancel stale, reminders (24h/1h flags),              │
│   │   no-show detection, expired slot cleanup                          │
│   ├── Notifications: hook_mail (5 templates), hook_entity_update       │
│   └── Marketplace: Twig templates, zero-region preprocess              │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      SOPORTE AL CLIENTE CLASE MUNDIAL                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_support (v1.0 — Omnichannel Support) ⭐                   │
│   ├── Entidades (7+2): SupportTicket, TicketMessage, TicketAttachment, │
│   │   TicketWatcher, TicketTag, TicketAiClassification,               │
│   │   TicketAiResolution + SlaPolicy (Config), SupportSettings (Config)│
│   ├── Servicios (18): TicketService (state machine), SlaEngineService  │
│   │   (policy resolution + business hours + pause/resume),             │
│   │   BusinessHoursService (timezone + holidays + walking),            │
│   │   TicketRoutingService (multi-factor: skills/vertical/workload),   │
│   │   AttachmentService (upload + MIME/size/double-ext security),      │
│   │   AttachmentScanService (ClamAV + 4-layer heuristic fallback),    │
│   │   AttachmentUrlService (HMAC SHA-256 signed URLs 1h),             │
│   │   TicketNotificationService, TicketMergeService, CsatSurveyService│
│   │   SupportHealthScoreService (5-component 0-100), TicketStreamSvc, │
│   │   SupportCronService, SupportAnalyticsService,                     │
│   │   TicketAiClassificationService, TicketAiResolutionService         │
│   ├── AI: SupportAgentSmartAgent (Gen 2, 5 actions:                   │
│   │   classify_ticket, suggest_response, summarize_thread,             │
│   │   detect_sentiment, draft_resolution)                              │
│   ├── API REST: SupportApiController (12+ endpoints)                  │
│   │   ├── GET  /tickets (listing with filters)                        │
│   │   ├── POST /tickets (create with auto-routing + SLA)              │
│   │   ├── GET  /tickets/{id} (detail + viewers)                       │
│   │   ├── PATCH /tickets/{id} (status transitions)                    │
│   │   ├── POST /tickets/{id}/messages (add message)                   │
│   │   ├── POST /tickets/{id}/attachments (upload + scan)              │
│   │   ├── GET  /tickets/{id}/attachments (list + signed URLs)         │
│   │   ├── POST /tickets/{id}/classify (AI classification)             │
│   │   ├── POST /tickets/{id}/suggest (AI resolution)                  │
│   │   ├── POST /tickets/search (LIKE query)                           │
│   │   ├── POST /tickets/inbound-email (email→ticket)                  │
│   │   └── GET  /attachments/download (HMAC signed)                    │
│   ├── SSE: SupportStreamController (Server-Sent Events)               │
│   │   └── GET  /support/stream (real-time ticket updates)             │
│   ├── State Machine (10 states): new → ai_handling → open →           │
│   │   pending_customer/pending_internal → escalated → resolved →      │
│   │   closed + reopened (re-enter open) + merged (lateral)            │
│   ├── SLA: {plan_tier}_{priority} policies, business hours,           │
│   │   pause/resume with deadline extension, breach + warning           │
│   ├── Cron: SLA processing, attachment scans, auto-close 7d,         │
│   │   CSAT surveys, SSE event purge 24h                               │
│   ├── Tables: support_ticket_events (SSE queue),                      │
│   │   support_ticket_viewers (collision detection)                     │
│   └── Tests: 26 unit tests / 134 assertions (4 files)                │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      COMUNICACION: MENSAJERIA SEGURA (IMPLEMENTED)     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_messaging (v1.0 — Implemented) 🔒                         │
│   ├── Entidades (4): SecureConversation (ContentEntity),               │
│   │   ConversationParticipant (ContentEntity),                         │
│   │   + SecureMessage (custom table), MessageAuditLog (custom table)   │
│   ├── Modelos (3): SecureMessageDTO (readonly), EncryptedPayload      │
│   │   (Value Object), IntegrityReport (Value Object)                   │
│   ├── Servicios (18): MessagingService, ConversationService,           │
│   │   MessageService, MessageEncryptionService, TenantKeyService,      │
│   │   MessageAuditService, NotificationBridgeService,                  │
│   │   AttachmentBridgeService, PresenceService, SearchService,         │
│   │   RetentionService, + 7 Access Checks                             │
│   ├── Controladores (7): Conversation, Message, Presence, Search,     │
│   │   Audit, Export (RGPD Art.20), MessagingPage (frontend)            │
│   ├── Cifrado: AES-256-GCM + Argon2id KDF + per-tenant keys          │
│   ├── Audit: SHA-256 hash chain (append-only, inmutable)              │
│   ├── API REST: 20+ endpoints + cursor-based pagination               │
│   ├── WebSocket: Ratchet (dev) / Swoole (prod) + Redis pub/sub       │
│   ├── ECA Plugins (8): 3 eventos, 3 condiciones, 2 acciones          │
│   ├── Frontend: 9 templates Twig (zero-region), 11 SCSS, 4 JS        │
│   ├── Permisos (13): 8 roles (cliente → super_admin)                  │
│   └── Total: 104 archivos, 6 sprints completados                      │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      PRECIOS CONFIGURABLES v2.1 ⭐                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 ecosistema_jaraba_core (Plan Config) ⭐                           │
│   ├── ConfigEntities (2):                                              │
│   │   ├── SaasPlanTier: tier_key, aliases, Stripe Price IDs, weight   │
│   │   └── SaasPlanFeatures: vertical+tier, features[], limits{}       │
│   ├── PlanResolverService (broker central):                            │
│   │   ├── normalize(): Alias → tier key canonico                      │
│   │   ├── getFeatures(): Cascade especifico → default → NULL          │
│   │   ├── checkLimit() / hasFeature(): Consultas atomicas             │
│   │   ├── resolveFromStripePriceId(): Resolucion inversa Stripe       │
│   │   └── getPlanCapabilities(): Array plano para QuotaManager        │
│   ├── Seed Data: 21 YAMLs (3 tiers + 3 defaults + 15 verticales)    │
│   ├── Admin UI: /admin/config/jaraba/plan-tiers + plan-features      │
│   ├── Drush: jaraba:validate-plans (completitud de configs)           │
│   ├── Update Hook: 9019 (FileStorage + CONFIG-SEED-001)              │
│   └── SCSS: _plan-admin.scss (body class page-plan-admin)            │
│                                                                         │
│   Integraciones cross-module (inyeccion @? opcional):                  │
│   ├── QuotaManagerService (jaraba_page_builder): PlanResolver first   │
│   │   con fallback a array hardcoded para backwards-compat            │
│   ├── PlanValidator (jaraba_billing): 3-source cascade                │
│   │   FVL → PlanFeatures → SaasPlan fallback                         │
│   └── BillingWebhookController: Stripe Price ID → tier resolution    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      STRIPE EMBEDDED CHECKOUT ⭐                       │
│                      STRIPE-CHECKOUT-001                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_billing (Checkout Self-Service)                             │
│   ├── StripeProductSyncService: SaasPlan → Stripe Product/Price        │
│   │   ├── syncPlan(): idempotente con LockBackend                      │
│   │   ├── Crea Product si no existe (stripe_product_id)                │
│   │   ├── Crea/archiva Price al cambiar importe (immutable prices)    │
│   │   └── presave hook: sync automatico al guardar SaasPlan           │
│   ├── CheckoutSessionService: crea Stripe Checkout Session             │
│   │   ├── ui_mode: 'embedded' (NO hosted redirect)                    │
│   │   ├── return_url (NUNCA success_url/cancel_url)                   │
│   │   └── metadata: saas_plan_id + billing_cycle + vertical           │
│   ├── CheckoutController: 4 handlers zero-region                       │
│   │   ├── page(): render checkout-page.html.twig (2 columnas)         │
│   │   ├── createSession(): API POST → client_secret JSON              │
│   │   ├── success(): render checkout-success.html.twig                │
│   │   └── cancel(): render checkout-cancel.html.twig                  │
│   └── BillingWebhookController: checkout.session.completed handler    │
│       └── Auto-provisioning: crear suscripcion + asignar plan         │
│                                                                         │
│   Flujo frontend:                                                       │
│   ├── stripe-checkout.js: Drupal.behaviors.stripeCheckout              │
│   │   ├── Carga Stripe.js dinamicamente (PCI compliance)              │
│   │   ├── CSRF token via /session/token (cacheado)                    │
│   │   ├── POST a createSession → client_secret                       │
│   │   └── stripe.initEmbeddedCheckout() → mount en DOM               │
│   ├── pricing-toggle.js: sync ?cycle= en CTAs checkout                │
│   └── pricing-page.html.twig: CTA → checkout si saas_plan_id existe  │
│                                                                         │
│   Templates zero-region (page--checkout.html.twig):                    │
│   ├── checkout-page.html.twig: resumen plan + formulario Stripe       │
│   ├── checkout-success.html.twig: confirmacion con SVG animado        │
│   └── checkout-cancel.html.twig: cancelacion con warning              │
│                                                                         │
│   SCSS: _checkout.scss (BEM + var(--ej-*) + mobile-first)             │
│   Body class: page-checkout via hook_preprocess_html()                 │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

┌─────────────────────────────────────────────────────────────────────────┐
│                      EMPLEABILIDAD: PERFIL PREMIUM ELEVADO ⭐         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_candidate (v3.0 — Profile Elevation) ⭐                   │
│   ├── CandidateProfileForm (premium sectioned):                        │
│   │   ├── 6 secciones: Personal, Profesional, Ubicacion, Preferencias,│
│   │   │   Presencia Online, Privacidad                                 │
│   │   ├── HIDDEN_FIELDS: user_id, uid, created, changed, etc.        │
│   │   ├── FIELD_LABELS: 30+ campos con labels en espanol              │
│   │   └── Glass-card UI con navigation pills                          │
│   ├── ProfileSectionForm (generico):                                   │
│   │   └── CRUD slide-panel para education, experience, language       │
│   ├── ProfileSectionFormController: add/edit/delete por entity_type   │
│   │   └── isSlidePanelRequest(): XHR sin _wrapper_format = slide-panel│
│   ├── Campo photo: entity_reference → image (image_image widget)      │
│   │   └── update_10004: backup refs, uninstall, install, restore      │
│   ├── Campos date: timestamp → datetime (education, experience)       │
│   │   └── update_10005: reinstall entities para cambio de tipo        │
│   ├── CvController: slide-panel AJAX + PNG preview fallback           │
│   │   └── 5 CV preview PNGs (classic, creative, minimal, modern, tech)│
│   ├── Seccion idiomas: ruta + languagesSection() + template           │
│   ├── ProfileCompletionService: entity queries para secciones         │
│   │   └── hasRelatedEntities() para experience/education/skills/langs │
│   └── profile_section_manager.js: delete confirmation + AJAX          │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│           ANDALUCIA EI: PLAN MAESTRO 12 SPRINTS CLASE MUNDIAL ⭐       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_andalucia_ei (v3.0 — Plan Maestro Clase Mundial) ⭐      │
│                                                                         │
│   PARTE I — Firma Electrónica (Sprints 1-6):                           │
│   ├── Sprint 1: FirmaWorkflowService state machine (8 estados:        │
│   │   borrador→pendiente_firma→firmado_parcial→firmado + caducado)    │
│   ├── Sprint 2: Firma táctil (canvas), AutoFirma (PAdES), sello      │
│   │   empresa (PKCS#12), SCSS firma-pad + verificación QR             │
│   ├── Sprint 3: DocumentoFirmaOrchestrator (37 categorías → 4 flujos:│
│   │   FIRMA_SIMPLE, FIRMA_DUAL, FIRMA_SELLO, SIN_FIRMA)              │
│   ├── Sprint 4: Dashboard firma masiva + JS handler batch             │
│   │   (selección, filtros, progreso, sello lote)                      │
│   ├── Sprint 5: Post-firma automatizado (QR URI, TSA timestamp,      │
│   │   notificación), verificación pública /verificar/{hash}           │
│   └── Sprint 6: Tests (14 FirmaController + 12 Orchestrator)         │
│                                                                         │
│   PARTE II — Emprendimiento Inclusivo (Sprints 7-12):                  │
│   ├── Sprint 7: PlanEmprendimientoEi entity (20 campos, 4 fases:     │
│   │   ideación→validación→lanzamiento→consolidación) +                │
│   │   EiEmprendimientoBridgeService (scoring viabilidad 0-100, SROI)  │
│   ├── Sprint 8: AdaptacionItinerarioService (8 tipos barrera,         │
│   │   complejidad scoring) + CopilotContext enriquecido con barreras  │
│   ├── Sprint 9: EiMatchingBridgeService (Qdrant vectorial             │
│   │   participante↔empresa) + EmpresaCandidatosController             │
│   ├── Sprint 10: EiAlumniBridgeService + AlumniController +           │
│   │   comunidad-alumni.html.twig + _historia-exito.html.twig          │
│   ├── Sprint 11: EiBadgeBridgeService (9 hitos PIIL) +               │
│   │   ImpactoPublicoController (SROI, IRIS+, ODS) + dashboard público │
│   └── Sprint 12: EiPushNotificationService (8 eventos, routing       │
│       prioridad) + notificaciones en transición de fase               │
│                                                                         │
│   Entidades: ExpedienteDocumento, PlanEmprendimientoEi                 │
│   Servicios (14+): FirmaWorkflow, DocumentoFirmaOrchestrator,         │
│   EiEmprendimientoBridge, AdaptacionItinerario, EiMatchingBridge,     │
│   EiAlumniBridge, EiBadgeBridge, EiPushNotification + 7 previos       │
│   100+ ficheros, 231 tests OK                                          │
│                                                                         │
│   PARTE III — Diseño Programa Formativo (Sprint 13):                   │
│   └── Sprint 13: AccionFormativaEi (20 campos, revisiones, VoBo SAE) │
│       + SesionProgramadaEi (25 campos, recurrencia, plazas)           │
│       + InscripcionSesionEi (18 campos, asistencia, ActuaciónSto)     │
│       + PlanFormativoEi (32 campos, computed stored para Views)        │
│       + VoboSaeWorkflowService (8 estados state machine)               │
│       + IndicadoresEsfService (14 output + 6 resultado FSE+)          │
│       + ProgramaVerticalAccessService (carril→verticales temporal)     │
│       + 4 API endpoints Hub, Portal participante, Alertas normativas  │
│       + 25 tests nuevos, 16 permisos, 351 tests total                 │
│                                                                         │
│   Entidades Sprint 13: AccionFormativaEi, SesionProgramadaEi,          │
│   InscripcionSesionEi, PlanFormativoEi                                 │
│   Servicios Sprint 13 (7): AccionFormativa, VoboSaeWorkflow,           │
│   SesionProgramada, InscripcionSesion, ProgramaVerticalAccess,         │
│   IndicadoresEsf, PlanFormativo                                        │
│   53 ficheros, 351 tests OK                                             │
│                                                                         │
│   PARTE IV — Reestructuración Actuaciones PIIL (Sprint 14):            │
│   └── Sprint 14: Alineación normativa BBRR Art. 2 + STO Manual ICV25 │
│       14-A: SesionProgramadaEi 6 tipos PIIL (orient_lab_ind/grup,     │
│       orient_ins_ind/grup, sesion_formativa, tutoria_seguimiento)       │
│       + ActuacionSto 8 tipos + FASE_POR_TIPO + LEGACY_MAP             │
│       + MaterialDidacticoEi entity (7 tipos, archivo+url)              │
│       + fase_piil/contenido_sto/subcontenido_sto en 3 entities        │
│       14-B: ActuacionComputeService (persona atendida: ≥10h orient    │
│       + ≥2h ind + ≥50h form + ≥75% asist; persona insertada:          │
│       atendida + ≥40h orient_ins + contrato ≥4m/RETA)                 │
│       + CoordinadorHubService getEstadisticasPorFase/PIIL sessions    │
│       + Migration script tipos legacy → PIIL                           │
│       14-C: Dashboard PIIL tab (Fase Atención + Fase Inserción)        │
│       + KPIs persona atendida/insertada + sesiones por fase            │
│       14-D: 17 tests (constants + compute + entity)                    │
│                                                                         │
│   Entidades Sprint 14: MaterialDidacticoEi (nueva)                     │
│   Entities modificadas: SesionProgramadaEi, AccionFormativaEi,         │
│   ActuacionSto, PlanFormativoEi, InscripcionSesionEi                   │
│   Servicios Sprint 14 (1 nuevo): ActuacionComputeService               │
│   Servicios modificados: CoordinadorHubService                          │
│   368 tests OK                                                          │
│                                                                         │
│   PARTE V — Sprint 15-18 Clase Mundial 10/10 (2026-03-12):            │
│   └── Sprint 15: 9 correcciones normativas P0                         │
│       15-A: PIIL-PROV-001 provincias FT_679 (solo Málaga+Sevilla)    │
│       15-B: canTransitToInsercion 4 criterios (10h+2h+50h+75%)      │
│       15-C: Reapertura desde baja + contrato temporal InsercionLaboral│
│       15-D: Seguimiento ≥40h + STO plazo 15 días                     │
│   └── Sprint 16: 5 herramientas operacionales                         │
│       16-A: DIME auto-asignación carril (0-8/9-14/15-20)            │
│       16-B: 4 alertas normativas (STO 15d, orient 2h, asist 75%,    │
│       indicadores 6m) + hook_update_10025 indicadores_6m_completado  │
│       16-C: BMC Validation Dashboard panel emprendimiento             │
│   └── Sprint 17: 8 features elevación clase mundial                   │
│       17-A: Advanced analytics (funnel PIIL, distribuciones,          │
│       promedios horas vs objetivos normativos)                         │
│       17-B: StoBidireccionalService (push/pull/reconciliación +       │
│       dashboard widget 4 KPIs synced/pending/error/sin_estado)        │
│       17-C: ICalExportService RFC 5545 (VTIMEZONE Europe/Madrid,      │
│       HMAC-SHA256 auth, 3 endpoints feed tenant/orientador/subscribe) │
│       17-D: EiMultichannelNotificationService (10 tipos × push+wa+   │
│       app, GDPR acepta_whatsapp, @?WhatsAppApiService cross-module)  │
│       17-E: Leaderboard gamificación top 10 + Club Alumni panel       │
│       17-F: Copilot restricción por fase (11 modos × 6 fases PIIL)  │
│   └── Sprint 18: Pulido final                                         │
│       18-A: WCAG 2.1 AA (47 scope="col", contrast tokens, semantic)  │
│       18-B: 432 tests OK (+17 nuevos: iCal, STO, multichannel,       │
│       DIME, compute, constants)                                        │
│                                                                         │
│   Servicios Sprint 15-18 (3 nuevos): StoBidireccionalService,         │
│   ICalExportService, EiMultichannelNotificationService                 │
│   Controllers Sprint 17 (1 nuevo): ICalExportController               │
│   Entities modificadas: ProgramaParticipanteEi, InsercionLaboral,     │
│   SesionProgramadaEi, AccionFormativaEi, ActuacionSto                 │
│   432 tests OK                                                         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      META-SITE: TENANT-AWARE RENDERING ⭐             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_site_builder (MetaSiteResolverService)                    │
│   ├── resolveFromPageContent(): PageContent → meta-site context       │
│   ├── resolveFromRequest(): 3-strategy domain → tenant resolution    │
│   │   ├── Strategy 1: Domain Access hostname match                   │
│   │   ├── Strategy 2: Tenant.domain field match                      │
│   │   └── Strategy 3: Subdomain prefix → Tenant.domain STARTS_WITH  │
│   ├── buildMetaSiteContext(): SiteConfig + SitePageTree → nav/footer  │
│   ├── Fix: SitePageTree status filter 'published' → 1 (int)          │
│   └── Per-request static cache (evita queries repetidas)              │
│                                                                         │
│   📦 jaraba_geo (Schema.org tenant-aware)                             │
│   └── Organization schema: name/description/logo from SiteConfig     │
│       cuando la pagina pertenece a un meta-sitio                       │
│                                                                         │
│   📦 ecosistema_jaraba_theme (overrides tenant-aware)                 │
│   ├── preprocess_html: <title> con meta_title_suffix de SiteConfig   │
│   │   + body class meta-site meta-site-tenant-{id}                    │
│   ├── preprocess_page: site_name, navigation, header/footer,         │
│   │   logo, CTA, copyright desde SiteConfig + SitePageTree            │
│   ├── preprocess_page__user: attach skills_manager + section_manager │
│   ├── CWV Optimization (Sprint 5):                                    │
│   │   ├── 7 CSS bundles extracted from main.scss (code splitting)    │
│   │   ├── cwv-tracking.js: PerformanceObserver (LCP,CLS,INP,FCP,TTFB)│
│   │   ├── fetchpriority="high" on 10 hero templates                  │
│   │   ├── AVIF + WebP responsive images via _responsive-image.twig   │
│   │   └── responsive_image() Twig function                           │
│                                                                         │
│   📦 jaraba_crm (Premium Forms)                                       │
│   └── 5 forms migrados a PremiumEntityFormBase: Company, Contact,    │
│       Opportunity, Activity, PipelineStage (glass-card UI + pills)    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      TENANT BRIDGE: RESOLUCION TENANT↔GROUP ⭐        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 ecosistema_jaraba_core (TenantBridge)                             │
│   ├── TenantBridgeService (ecosistema_jaraba_core.tenant_bridge):     │
│   │   ├── getTenantForGroup(GroupInterface): TenantInterface          │
│   │   ├── getGroupForTenant(TenantInterface): GroupInterface          │
│   │   ├── getTenantIdForGroup(int $groupId): int                     │
│   │   └── getGroupIdForTenant(int $tenantId): int                    │
│   ├── Dependencias: entity_type.manager                               │
│   ├── Error handling: \InvalidArgumentException si entidad no existe  │
│   │                                                                     │
│   Consumidores (inyeccion @ecosistema_jaraba_core.tenant_bridge):     │
│   ├── QuotaManagerService: Group→Tenant para billing quota checks     │
│   ├── BillingController: Tenant entity para operaciones de cobro      │
│   ├── BillingService: Tenant entity para suscripciones Stripe        │
│   ├── StripeWebhookController: Tenant entity para webhook processing │
│   └── SaasPlan: Tenant entity para plan management                    │
│                                                                         │
│   Regla TENANT-BRIDGE-001: NUNCA cargar getStorage('group') con      │
│   Tenant IDs ni viceversa. Tenant = billing, Group = content isolation│
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      TENANT ISOLATION: ACCESS CONTROL ⭐               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_page_builder (Access Control)                              │
│   ├── PageContentAccessControlHandler (EntityHandlerInterface + DI):  │
│   │   ├── createInstance(): Inyecta TenantContextService              │
│   │   ├── checkAccess(): view=publicado, update/delete=isSameTenant() │
│   │   ├── isSameTenant(): (int) entity.tenant_id === currentTenantId │
│   │   └── checkCreateAccess(): authenticated + create permission      │
│   │                                                                     │
│   📦 ecosistema_jaraba_core (Generic Access + Path + Context)         │
│   ├── DefaultEntityAccessControlHandler (renombrado desde             │
│   │   DefaultAccessControlHandler): Fallback generico para entidades  │
│   │   sin handler especifico                                           │
│   ├── PathProcessorPageContent: Tenant-aware path resolution          │
│   │   ├── Inyecta TenantContextService (@? opcional)                  │
│   │   └── Filtra por tenant_id del contexto actual si disponible     │
│   ├── TenantContextService (enhanced):                                │
│   │   ├── getCurrentTenantId(): ?int (nullable cuando no hay grupo)  │
│   │   └── Resiliente a usuarios sin grupo (returns NULL, no throws)  │
│   │                                                                     │
│   Politica de acceso (TENANT-ISOLATION-ACCESS-001):                   │
│   ├── view: Paginas publicadas son publicas (status=1)               │
│   ├── update: Solo si isSameTenant() + permiso                       │
│   ├── delete: Solo si isSameTenant() + permiso                       │
│   └── create: Solo authenticated + permiso de creacion               │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      PAGE BUILDER: PATHPROCESSOR + META-SITIO ⭐       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_page_builder (PathProcessor)                                │
│   ├── PathProcessorPageContent: InboundPathProcessorInterface          │
│   │   ├── processInbound(): path_alias → /page/{id} resolution        │
│   │   ├── resolveHomepage(): Root / → homepage por dominio/tenant     │
│   │   │   (usa MetaSiteResolverService para resolver hostname)        │
│   │   ├── Prioridad 250 (superior a PathProcessorFront=200)           │
│   │   ├── 4to parametro: @?MetaSiteResolverService (DI opcional)     │
│   │   ├── Sin filtro status → AccessControlHandler gestiona acceso    │
│   │   ├── Skip list: /api/, /admin/, /user/, /media/, /session/       │
│   │   └── Static cache por path dentro del request                     │
│   │                                                                     │
│   Meta-Sitio jarabaimpact.com (7 paginas):                              │
│   ├── Homepage: /jarabaimpact (Hero + plataforma SaaS)                 │
│   ├── Plataforma: /plataforma (Triple Motor Economico)                 │
│   ├── Verticales: /verticales (6 verticales SaaS)                      │
│   ├── Impacto: /impacto (Estadisticas + beneficiarios)                 │
│   ├── Programas: /programas (Programas institucionales)                │
│   ├── Recursos: /recursos (Centro de recursos)                         │
│   └── Contacto: /contacto (Formulario + datos)                         │
│                                                                         │
│   Meta-Sitio pepejaraba.com (9 paginas, Tenant ID=5):                  │
│   ├── Inicio: / (Hero + pain points + metodo + ecosistema)            │
│   ├── Manifiesto: /manifiesto                                          │
│   ├── Metodo Jaraba: /metodo-jaraba                                    │
│   ├── Casos de Exito: /casos-de-exito                                  │
│   ├── Blog: /blog                                                      │
│   ├── Contacto: /contacto-pepe                                         │
│   ├── Aviso Legal: /aviso-legal                                        │
│   ├── Politica Privacidad: /politica-privacidad                        │
│   └── Politica Cookies: /politica-cookies                              │
│                                                                         │
│   APIs Usadas:                                                          │
│   ├── PATCH /api/v1/pages/{id}/config (título + path_alias)            │
│   ├── POST /api/v1/pages/{id}/publish (publicación)                    │
│   └── GrapesJS API (contenido visual del canvas)                       │
│                                                                         │
│   🔒 Concurrent Edit Locking (Sprint 5):                               │
│   ├── Optimistic locking: `changed` timestamp + X-Entity-Changed      │
│   ├── PageContent fields: edit_lock_uid + edit_lock_expires            │
│   ├── 3 API endpoints: acquire/release/status lock                    │
│   └── JS heartbeat renewal + conflict notifications                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      VERTICAL: EMPLEABILIDAD ⭐                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_candidate (v2.0 — Profile Premium) ⭐                     │
│   ├── Entidades (6): CandidateProfile, CandidateSkill,                │
│   │   CandidateExperience, CandidateEducation (NEW),                  │
│   │   CandidateLanguage, CopilotConversation + CopilotMessage         │
│   ├── Premium /my-profile: 7 secciones glassmorphism                  │
│   │   ├── Hero: Avatar, nombre, headline, ubicacion, badge            │
│   │   ├── About: Summary (|safe_html XSS-safe) + nivel educacion     │
│   │   ├── Experience: Timeline cronologica descendente                 │
│   │   ├── Education: Grid de registros CandidateEducation              │
│   │   ├── Skills: Pills con badge de verificacion                     │
│   │   ├── Links: LinkedIn, GitHub, Portfolio, Website                  │
│   │   └── CTA: Completion ring SVG + enlace a edicion                 │
│   ├── Empty State: Glassmorphism card + benefit cards + CTA            │
│   ├── ProfileController: Carga resiliente (try/catch por entidad)     │
│   ├── SCSS: 920 lineas, design tokens --ej-*, BEM cp-*, responsive   │
│   ├── Iconos: 15 pares jaraba_icon() duotone verificados              │
│   └── Admin: /admin/content/candidate-educations con Field UI         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      ICON SYSTEM: ZERO CHINCHETAS ⭐                   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 ecosistema_jaraba_core (Icon Engine)                              │
│   ├── JarabaTwigExtension: jaraba_icon() Twig function                │
│   │   ├── Firma: jaraba_icon(category, name, {variant, color, size})  │
│   │   ├── Resolucion: {modulePath}/images/icons/{category}/{name}     │
│   │   │   [-variant].svg                                               │
│   │   ├── Variantes: outline (default), outline-bold, filled, duotone │
│   │   ├── Fallback: emoji via getFallbackEmoji() → 📌 (chincheta)    │
│   │   └── Inline SVG: stroke/fill inherits CSS currentColor           │
│   │                                                                     │
│   ├── Categorias primarias (6):                                        │
│   │   ├── actions/ (download, check, search, sparkles, etc.)          │
│   │   ├── fiscal/ (invoice, balance, treasury, etc.)                  │
│   │   ├── media/ (play-circle, image, camera)                         │
│   │   ├── micro/ (arrow-right, chevron-down, dot — 12px)             │
│   │   ├── ui/ (settings, globe, lock, file-text, etc.)               │
│   │   └── users/ (user, group, id-card)                               │
│   │                                                                     │
│   ├── Bridge categories (7 — symlinks a categorias primarias):        │
│   │   ├── achievement/ → actions/ (trophy, medal, target, etc.)       │
│   │   ├── finance/ → fiscal/ (wallet, credit-card, coins, etc.)      │
│   │   ├── general/ → ui/ (settings, info, alert-triangle, etc.)      │
│   │   ├── legal/ → ui/ (scale, shield, file-text, etc.)              │
│   │   ├── navigation/ → ui/ (home, menu, compass, etc.)              │
│   │   ├── status/ → ui/ (check-circle, clock, alert-circle, etc.)    │
│   │   └── tools/ → ui/ (wrench, code, terminal, etc.)                │
│   │                                                                     │
│   ├── Categorias verticales (2):                                      │
│   │   ├── commerce/ (store, cart, catalog, delivery-truck, etc.)     │
│   │   └── business/ (rocket, briefcase, empleo, time-pressure,       │
│   │       launch-idea, talent-spotlight, career-connect,              │
│   │       seed-momentum, store-digital + duotone cada)                │
│   │                                                                     │
│   ├── SVGs: ~366 iconos (outline + duotone por cada)                  │
│   │   ├── Outline: stroke-only, stroke-width="2"                      │
│   │   ├── Duotone: stroke + fill con opacity="0.2" para capas fondo  │
│   │   ├── Canvas inline: hex explicito (#233D63), NO currentColor    │
│   │   └── ICON-SYMLINK-REL-001: symlinks SIEMPRE relativos (Docker)  │
│   │                                                                     │
│   ├── Auditoria Templates: 305 pares unicos verificados, 0 chinchetas│
│   │   ├── 32 llamadas con convencion rota corregidas (4 modulos)      │
│   │   ├── ~170 SVGs/symlinks creados para bridge categories           │
│   │   ├── 2 symlinks circulares corregidos (ui/save, bookmark)        │
│   │   └── 1 symlink roto reparado (general/alert-duotone)            │
│   │                                                                     │
│   └── Auditoria Canvas Data: 11 emojis Unicode eliminados (4 paginas)│
│       ├── Page 57: 6 emojis → 6 SVGs inline business/ (duotone)      │
│       ├── Pages 60-62: 5 emojis → 5 SVGs inline estandar            │
│       └── ICON-EMOJI-001 + ICON-CANVAS-INLINE-001 nuevas             │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      SETUP WIZARD: PATRON TRANSVERSAL ⭐              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 ecosistema_jaraba_core (Setup Wizard Infrastructure)              │
│   ├── SetupWizardStepInterface: contrato 13 metodos                   │
│   │   ├── getId, getWizardId, getLabel, getDescription, getWeight     │
│   │   ├── getIcon, getRoute, getRouteParameters                       │
│   │   ├── useSlidePanel, getSlidePanelSize                            │
│   │   ├── isComplete(int $tenantId): bool (count-based rapido)        │
│   │   ├── getCompletionData(int $tenantId): array (quality warnings)  │
│   │   └── isOptional(): bool                                          │
│   │                                                                     │
│   ├── SetupWizardRegistry: tagged service collector                   │
│   │   ├── addStep(): llamado via SetupWizardCompilerPass              │
│   │   ├── getStepsForWizard(wizardId, tenantId): Twig-ready array    │
│   │   │   ├── Computa is_active (primer incompleto no-opcional)       │
│   │   │   ├── completion_percentage (solo non-optional steps)         │
│   │   │   └── step_number, serialized labels                          │
│   │   └── hasWizard(wizardId): bool (quick check)                    │
│   │                                                                     │
│   ├── SetupWizardCompilerPass: DI/Compiler/                           │
│   │   └── Tag: ecosistema_jaraba_core.setup_wizard_step               │
│   │                                                                     │
│   ├── SetupWizardApiController: REST async refresh                    │
│   │   └── GET /api/v1/setup-wizard/{id}/status (CSRF header)         │
│   │                                                                     │
│   Parciales Twig (ecosistema_jaraba_theme):                            │
│   ├── _setup-wizard.html.twig: stepper horizontal, ring SVG,          │
│   │   stagger entrance, active glow, confetti completion              │
│   ├── _daily-actions.html.twig: grid cards, shimmer hover,           │
│   │   badge pop, anchor navigation (href_override)                    │
│   └── setup-wizard.js: Drupal.behaviors, localStorage dismiss,       │
│       async refresh, animateNumber, celebrateStep/Completion          │
│                                                                         │
│   Caso vertical: Andalucia +ei (coordinador_ei wizard):               │
│   ├── CoordinadorPlanFormativoStep (weight 10)                        │
│   ├── CoordinadorAccionesFormativasStep (weight 20)                   │
│   ├── CoordinadorSesionesStep (weight 30)                             │
│   └── CoordinadorValidacionStep (weight 40, optional)                 │
│       └── PIIL: ≥50h formacion + ≥10h orientacion + 0 VoBo           │
│                                                                         │
│   Extensibilidad: nuevos verticales registran steps via tag solamente │
│   — 0 cambios en ecosistema_jaraba_core                               │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

### DAILY ACTIONS — PATRÓN EXTENSIBLE (DAILY-ACTIONS-REGISTRY-001)

> Complemento del Setup Wizard. Mientras el wizard guía la configuración inicial (one-time), las Daily Actions facilitan las operaciones recurrentes del día a día.

#### Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────────┐
│                    ECOSISTEMA_JARABA_CORE                        │
│                                                                 │
│  ┌──────────────────────────────┐  ┌──────────────────────────┐ │
│  │ DailyActionInterface         │  │ DailyActionsRegistry     │ │
│  │ ──────────────────────────── │  │ ────────────────────────  │ │
│  │ + getId(): string            │  │ - actions: Interface[][] │ │
│  │ + getDashboardId(): string   │  │ + addAction(action)      │ │
│  │ + getLabel(): Translatable   │  │ + getActionsForDashboard()│ │
│  │ + getIcon(): array           │  │ + hasDashboard()         │ │
│  │ + getColor(): string         │  │                          │ │
│  │ + getRoute(): string         │  │ (tagged service collector)│ │
│  │ + isPrimary(): bool          │  └──────────────────────────┘ │
│  │ + getContext(tenantId): array │                               │
│  │   → badge, badge_type, visible│  DailyActionsCompilerPass    │
│  └──────────────────────────────┘  (tag: daily_action)          │
└─────────────────────────────────────────────────────────────────┘
        │                    │                    │
        ▼                    ▼                    ▼
┌──────────────┐  ┌──────────────────┐  ┌──────────────────┐
│ 11 módulos    │  │ 13 dashboards    │  │ 54 tagged         │
│ verticales    │  │ (9 primary +     │  │ services          │
│               │  │  4 secondary)    │  │                   │
└──────────────┘  └──────────────────┘  └──────────────────┘
```

#### Cobertura Multi-Vertical y Multi-Rol

| Vertical | Rol Primario | Rol(es) Secundario(s) | Wizard Steps | Daily Actions |
|----------|-------------|----------------------|-------------|---------------|
| Andalucía +ei | Coordinador | Orientador | 7 | 9 |
| Empleabilidad | Candidato | — | 5 | 4 |
| Emprendimiento | Emprendedor (Copilot) | Entrepreneur (Business Tools) | 7 | 8 |
| Comercio Conecta | Merchant | — | 5 | 5 |
| AgroConecta | Productor | — | 5 | 4 |
| Servicios Conecta | Proveedor | — | 4 | 4 |
| JarabaLex | Profesional jurídico | — | 3 | 4 |
| Content Hub | Editor | — | 3 | 4 |
| Formación/LMS | Instructor | Learner | 6 | 8 |
| Mentoring | — | Mentor | 3 | 4 |
| **Total** | **9 primarios** | **4 secundarios** | **48** | **54** |

#### Avatar Access Checker (AVATAR-ACCESS-001)

Reemplaza `_permission` en rutas frontend de dashboards/portales con `_avatar_access` basado en JourneyState:

```yaml
# routing.yml — antes (requiere Drupal role/permission que no existe)
_permission: 'access entrepreneur dashboard'

# routing.yml — después (verifica avatar en JourneyState)
_avatar_access: 'emprendedor,mentor,gestor_programa'
```

- `AvatarAccessCheck` (tag: `access_check`, `applies_to: _avatar_access`)
- Normaliza nomenclatura dual via `AVATAR_ALIASES` (JourneyState español ↔ AvatarDetection inglés)
- Admin bypass automático (rol `administrator`)
- 22 rutas migradas en 8 módulos (candidate, business_tools, mentoring, comercio, agro, servicios, legal, lms)
- Rutas admin (`/admin/...`) mantienen `_permission`

#### Profile Hub (PROFILE-HUB-001)

`/user/{uid}` actúa como hub inteligente de onboarding:

- `AvatarWizardBridgeService`: cascada JourneyState → AvatarDetection para resolver wizard_id/dashboard_id
- `AvatarWizardMapping`: value object inmutable con wizardId, dashboardId, contextId, avatarType, vertical
- `JOURNEY_TO_CANONICAL`: normaliza 19 avatares JourneyState + 9 avatares AvatarDetection
- Reutiliza partials `_setup-wizard.html.twig` + `_daily-actions.html.twig` al 100%
- CTA "Ir a mi panel" con verificación de existencia de ruta

#### Patrón de Integración en Controladores

```php
// L1: Constructor DI (optional)
protected ?SetupWizardRegistry $wizardRegistry = NULL,
protected ?DailyActionsRegistry $dailyActionsRegistry = NULL,

// L2: Dashboard method
$setupWizard = $this->wizardRegistry?->hasWizard('wizard_id')
  ? $this->wizardRegistry->getStepsForWizard('wizard_id', $tenantId) : NULL;
$dailyActions = $this->dailyActionsRegistry
  ?->getActionsForDashboard('dashboard_id', $tenantId) ?? [];
```

#### PIPELINE-E2E-001 — Verificación Obligatoria

Toda integración Setup Wizard + Daily Actions DEBE verificar 4 capas:
- **L1** Service → DI en constructor → create() factory
- **L2** Controller → getStepsForWizard() + getActionsForDashboard() → render array
- **L3** hook_theme() → variables declaradas (setup_wizard, daily_actions)
- **L4** Template → {% include %} de parciales → textos traducidos → DOM visible

┌─────────────────────────────────────────────────────────────────────────┐
│                      SEGURIDAD: ACCESS HANDLERS ⭐                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   ACCESS-STRICT-001 (Auditoria Horizontal)                             │
│   ├── 52 instancias de == (loose equality) → (int) === (int)          │
│   ├── 39 access handlers en 21 modulos                                 │
│   ├── Patrones corregidos:                                             │
│   │   ├── $entity->getOwnerId() == $account->id()                    │
│   │   ├── $entity->get('field')->target_id == $account->id()          │
│   │   └── $merchant->getOwnerId() == $account->id()                  │
│   ├── Fix universal: (int) LHS === (int) $account->id()              │
│   ├── Previene type juggling: "0"==false, null==0, ""==0             │
│   └── Verificacion: grep "== $account->id()" | grep -v "===" → 0    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      EMAIL: CAN-SPAM COMPLIANCE ⭐                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_email (28 plantillas horizontales)                         │
│   ├── Grupos: base (1) + auth (5) + billing (7) + marketplace (6)    │
│   │   + fiscal (3) + andalucia_ei (6)                                 │
│   ├── CAN-SPAM Compliance:                                             │
│   │   ├── <mj-preview>: Preheader unico por plantilla (28/28)        │
│   │   ├── Direccion postal: Juncaril, Albolote (28/28)               │
│   │   └── Opt-out: {{ unsubscribe_url }} (ya existia)                │
│   ├── Brand Consistency:                                               │
│   │   ├── Font: Outfit, Arial, Helvetica, sans-serif (28/28)         │
│   │   ├── Azul primario: #1565C0 (unificado desde 4 variantes)       │
│   │   ├── Body text: #333333, Muted: #666666, BG: #f8f9fa            │
│   │   ├── Dividers: #E0E0E0, Disclaimer: #999999                     │
│   │   └── Headings: #1565C0                                           │
│   └── Colores semanticos preservados:                                  │
│       ├── Error: #dc2626 (payment_failed, dunning_notice)             │
│       ├── Exito: #16a34a (subscription_created, orders)               │
│       ├── Warning: #f59e0b (trial_ending), #D97706 (fiscal)          │
│       └── Andalucia EI: #FF8C42 (naranja), #00A9A5 (teal)           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│            GESTIÓN DE SECRETOS: SECRET-MGMT-001 🔒                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Arquitectura de 3 capas para secretos:                                │
│                                                                         │
│   Capa 1: config/sync/ (Git — valores vacíos)                          │
│   ├── social_auth_google.settings.yml: client_id='', client_secret='' │
│   ├── social_auth_linkedin.settings.yml: client_secret=''             │
│   ├── social_auth_microsoft.settings.yml: client_secret=''            │
│   ├── symfony_mailer.mailer_transport.smtp_ionos.yml: user/pass/host=''│
│   └── recaptcha_v3.settings.yml: site_key='', secret_key=''          │
│                                                                         │
│   Capa 2: config/deploy/settings.secrets.php (Runtime overrides)       │
│   ├── 14 $config overrides desde getenv()                              │
│   ├── OAuth: Google (2), LinkedIn (2), Microsoft (2)                   │
│   ├── SMTP IONOS: user, pass, host                                    │
│   ├── reCAPTCHA v3: site_key, secret_key                              │
│   ├── Stripe: secret_key, webhook_secret, publishable_key             │
│   └── Incluido en settings.php ANTES de settings.local.php            │
│                                                                         │
│   Capa 3: Variables de entorno                                          │
│   ├── Local: .env (gitignored) + Lando env_file injection             │
│   ├── Producción: panel hosting (IONOS, etc.)                          │
│   └── .env.example: 12 variables documentadas                          │
│                                                                         │
│   Flujo config:import / config:export:                                  │
│   ├── import: YAML vacíos → BD vacía → $config override en runtime    │
│   ├── export: BD vacía → YAML vacíos (overrides NO se exportan)       │
│   └── Resultado: secretos NUNCA tocan git ni la BD                    │
│                                                                         │
│   Limpieza historial git:                                               │
│   ├── git-filter-repo --blob-callback (Python, no shell)              │
│   ├── 10 secretos eliminados de 459 commits                           │
│   └── Force push + verificación 0 matches                              │
│                                                                         │
│   Regla: SECRET-MGMT-001 (P0)                                          │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│            AUDITORIA IA CLASE MUNDIAL: 25 GAPS (7+16+2) 🔍            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Auditoria vs Salesforce Agentforce, HubSpot Breeze,                  │
│   Shopify Sidekick, Intercom Fin, Notion AI                            │
│                                                                         │
│   📊 Nivel actual: Backend 4.8/5, UX 4.2/5, Testing 3.5/5 (100/100) │
│   🎯 Nivel objetivo: ACHIEVED — 30/30 items completados               │
│                                                                         │
│   7 REFINAMIENTO (codigo existente):                                    │
│   ├── GAP-AUD-001: Onboarding Wizard + AI recommendations             │
│   │   └── TenantOnboardingWizardService (409 ln) + SmartBaseAgent     │
│   ├── GAP-AUD-002: Pricing AI Metering (token usage per plan)         │
│   │   └── PricingController (356 ln) + UsageDashboardController       │
│   ├── GAP-AUD-003: Demo/Playground publico (copilot embebido)         │
│   │   └── DemoController (246 ln) + PublicCopilotController (547 ln)  │
│   ├── GAP-AUD-004: AI Dashboard GEO (Chart.js visualizacion)          │
│   ├── GAP-AUD-005: llms.txt MCP Discovery (7 Gen 2 agents + tools)   │
│   │   └── LlmsTxtController (255 ln) + ToolRegistry integration      │
│   ├── GAP-AUD-006: Schema.org GEO (speakable, HowTo, FAQ)            │
│   │   └── SchemaGeneratorService (471 ln) + SchemaOrgService (529 ln) │
│   └── GAP-AUD-007: Dark Mode AI Components (40 CSS variables)         │
│                                                                         │
│   16 TRUE GAPS NUEVOS:                                                  │
│   ├── GAP-AUD-008: Command Bar Cmd+K (Spotlight)                      │
│   │   └── CommandRegistryService + tagged jaraba.command_provider      │
│   ├── GAP-AUD-009: Inline AI (sparkle buttons en forms)               │
│   │   └── InlineAiService + PremiumEntityFormBase.getInlineAiFields() │
│   ├── GAP-AUD-010: Proactive Intelligence (entity + cron + bell)      │
│   │   └── ProactiveInsight ContentEntity + QueueWorker                │
│   ├── GAP-AUD-011: Voice AI (Web Speech API client-side)              │
│   ├── GAP-AUD-012: A2A Protocol (Agent Card + task lifecycle)         │
│   │   └── Extiende McpServerController JSON-RPC 2.0                  │
│   ├── GAP-AUD-013: Vision/Multimodal (GPT-4o) ✅ COMPLETE            │
│   │   └── MultiModalBridgeService: image+audio+speech+generation      │
│   ├── GAP-AUD-014: AI Test Coverage (40+ unit, 15+ kernel)           │
│   ├── GAP-AUD-015: Prompt Regression (golden fixtures)                │
│   │   └── PromptRegressionTestBase + 7 fixtures                      │
│   ├── GAP-AUD-016: Blog Slugs (ParamConverter, presave auto-slug)    │
│   ├── GAP-AUD-017: Content Hub tenant_id (TENANT-ISOLATION-ACCESS)   │
│   ├── GAP-AUD-018: Skill Inference (SkillInferenceService + LLM)     │
│   ├── GAP-AUD-019: Adaptive Learning (activar $aiAgent unused)       │
│   ├── GAP-AUD-020: Demand Forecasting (DemandForecastingService)     │
│   ├── GAP-AUD-021: AI Writing in GrapesJS (grapesjs-jaraba-ai.js)   │
│   ├── GAP-AUD-022: Service Matching (Qdrant real wiring)             │
│   └── GAP-AUD-023: Design System Docs (ComponentDocController)       │
│                                                                         │
│   2 INFRAESTRUCTURA:                                                    │
│   ├── GAP-AUD-024: Cost Attribution (Observability → Metering)        │
│   └── GAP-AUD-025: Horizontal Scaling (Redis AI worker pool)         │
│                                                                         │
│   Plan: 4 sprints, 320-440h, 55+ tests                                │
│   Doc: Plan_Implementacion_Auditoria_IA_Clase_Mundial_v1.md           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│              REVIEWS & COMMENTS CLASE MUNDIAL (10 VERTICALES) ⭐       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   AUDITORIA: 20 hallazgos (4 seguridad + 5 bugs + 4 arquitectura      │
│              + 3 directrices + 4 brechas clase mundial)                │
│                                                                         │
│   4 ENTIDADES EXISTENTES (heterogeneas):                               │
│   ├── comercio_review (ComercioConecta): 14 campos, tenant→taxonomy   │
│   ├── review_agro (AgroConecta): 14 campos, tenant→taxonomy, TYPE_*   │
│   ├── review_servicios (ServiciosConecta): 12 campos, SIN tenant_id   │
│   └── session_review (Mentoring): 11 campos, SIN tenant_id            │
│                                                                         │
│   2 ENTIDADES NUEVAS PLANIFICADAS:                                     │
│   ├── course_review (LMS/Formacion): rating + completion_pct          │
│   └── content_comment (Content Hub): threading parent_id + depth 3    │
│                                                                         │
│   TRAIT TRANSVERSAL: ReviewableEntityTrait                              │
│   ├── 5 campos compartidos: review_status, helpful_count, photos,     │
│   │   ai_summary, ai_summary_generated_at                             │
│   ├── Helpers con fallback: getReviewStatusValue() (status/state),    │
│   │   getAuthorId() (reviewer_uid/uid/mentee_id)                      │
│   └── Se aplica a las 6 entidades para normalizar acceso              │
│                                                                         │
│   5 SERVICIOS TRANSVERSALES:                                           │
│   ├── ReviewModerationService: cola moderacion, auto-approve, bulk    │
│   ├── ReviewAggregationService: AggregateRating + cache + invalidate  │
│   ├── ReviewSchemaOrgService: JSON-LD AggregateRating + Review        │
│   ├── ReviewInvitationService: email post-transaccion, rate limit     │
│   └── ReviewAiSummaryService: resumen IA por batch cron               │
│                                                                         │
│   FRONTEND:                                                             │
│   ├── _star-rating.html.twig: SVG stars con half-fill, WCAG aria     │
│   ├── _review-card.html.twig: card reutilizable con helpful/report   │
│   ├── _review-form.html.twig: slide-panel star widget interactivo    │
│   ├── _review-summary.html.twig: AggregateRating + distribution bar  │
│   ├── star-rating.js: 0.5 step, keyboard nav, CSRF submit            │
│   ├── SCSS: 350+ lineas BEM, dark mode, reduced-motion               │
│   └── GrapesJS: review-block plugin (summary + form embed)           │
│                                                                         │
│   REMEDIACION SEGURIDAD:                                               │
│   ├── REV-S1/S2: tenant_id taxonomy→group (entity_reference)         │
│   ├── REV-S3/S4: +tenant_id en ReviewServicios y SessionReview       │
│   ├── Access handlers: EntityHandlerInterface DI + isSameTenant()    │
│   └── CSRF + API-WHITELIST-001 en todos los endpoints                │
│                                                                         │
│   SCHEMA.ORG: AggregateRating + Review JSON-LD (Rich Snippets)       │
│                                                                         │
│   Reglas: REVIEW-TRAIT-001, REVIEW-MODERATION-001,                    │
│           SCHEMA-AGGREGATE-001 (P0/P1)                                │
│   Docs:                                                                │
│   ├── Auditoria: Auditoria_Sistemas_Calificaciones_Comentarios_v1.md │
│   └── Plan: Plan_Implementacion_Reviews_Comentarios_v1.md            │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      PREMIUM ENTITY FORMS: 237 FORMULARIOS ⭐          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 ecosistema_jaraba_core (PremiumEntityFormBase)                    │
│   ├── Clase abstracta: PremiumEntityFormBase                           │
│   │   ├── Extiende ContentEntityForm con glass-card UI               │
│   │   ├── getSectionDefinitions(): Secciones con label, icon, fields │
│   │   ├── getFormIcon(): Icono principal del formulario               │
│   │   ├── Navigation pills + sticky action bar                       │
│   │   ├── SCSS: _premium-forms.scss (glassmorphism, responsive)      │
│   │   ├── JS: premium-forms.js (scroll sync, pills, dirty state)    │
│   │   ├── Slide-panel: sticky nav wrap, compact pills, blur bg      │
│   │   ├── CHECKBOX-SCOPE-001: toggle solo .form-type-boolean        │
│   │   ├── OBSERVER-SCROLL-ROOT-001: IO root=.slide-panel__body      │
│   │   └── STICKY-NAV-WRAP-001: flex-wrap + scroll-margin-top        │
│   │                                                                     │
│   Migracion completa: 237 formularios en 50 modulos, 8 fases          │
│   ├── Patron A (Simple): Formularios sin DI ni campos computados     │
│   │   └── Solo requiere getSectionDefinitions() + getFormIcon()      │
│   ├── Patron B (Computed Fields): Campos auto-calculados              │
│   │   └── Usar #disabled = TRUE para campos no editables             │
│   ├── Patron C (DI): Formularios con inyeccion de dependencias       │
│   │   └── Patron parent::create() para preservar DI de la base      │
│   ├── Patron D (Custom Logic): Formularios con logica especial       │
│   │   └── Override de buildForm()/save() manteniendo secciones       │
│   │                                                                     │
│   Distribucion por vertical:                                            │
│   ├── ecosistema_jaraba_core: ~30 forms (entidades base)             │
│   ├── jaraba_billing: ~15 forms (facturacion, suscripciones)         │
│   ├── jaraba_candidate: ~10 forms (perfil, educacion, experiencia)   │
│   ├── jaraba_crm: 5 forms (Company, Contact, Opportunity, etc.)     │
│   ├── jaraba_andalucia_ei: ~12 forms (expedientes, solicitudes)      │
│   ├── jaraba_page_builder: ~8 forms (paginas, templates)             │
│   ├── jaraba_customer_success: ~10 forms (health, churn, playbooks) │
│   └── +43 modulos restantes con 1-8 forms cada uno                   │
│                                                                         │
│   Regla: PREMIUM-FORMS-PATTERN-001 (P1)                               │
│   ├── Extiende PremiumEntityFormBase (NUNCA ContentEntityForm)        │
│   ├── Fieldsets/details groups PROHIBIDOS (usar secciones)            │
│   ├── Iconos por categoria (ui, actions, fiscal, users, etc.)        │
│   └── 0 ContentEntityForm restantes en modulos custom                 │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      WORKFLOW AUTOMATION ENGINE ⭐                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_workflows (v1.0 — S4-04)                                   │
│   ├── Entidad: WorkflowRule (ContentEntity, trigger-based actions)     │
│   ├── Servicio: WorkflowExecutionService                               │
│   │   ├── event_dispatcher para trigger events                        │
│   │   └── @?jaraba_ai_agents.observability (AI logging opcional)      │
│   ├── Form: WorkflowRuleForm                                          │
│   ├── Access: WorkflowRuleAccessControlHandler                        │
│   ├── ListBuilder: WorkflowRuleListBuilder                            │
│   ├── Config Schema: jaraba_workflows.schema.yml                      │
│   └── Test: WorkflowExecutionServiceTest (Unit)                       │
│                                                                         │
│   Dependencias: ecosistema_jaraba_core                                 │
│   15 ficheros total                                                    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│              DEMO VERTICAL PLG (Product-Led Growth) ⭐                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 ecosistema_jaraba_core (Demo subsystem, ~4,000 LOC)               │
│                                                                         │
│   User Journey:                                                         │
│   /demo → /demo/start/{profileId} → /demo/dashboard/{sessionId}       │
│     ├── Guided Tour (4 pasos, auto-start)                              │
│     ├── Magic Actions → /demo/ai/storytelling/{sessionId}              │
│     ├── AI Playground → /demo/ai-playground                            │
│     └── Progressive Disclosure → /demo/dashboard/{sessionId}           │
│         └── Conversion Modal → /registro/{vertical}                    │
│                                                                         │
│   Controller: DemoController (~765 LOC)                                 │
│   ├── demoLanding() — 10 perfiles demo con social proof               │
│   ├── startDemo() — sesion DB + guided tour + dashboard inicial       │
│   ├── demoDashboard() — progressive disclosure + nudges               │
│   ├── demoAiStorytelling() — AI storytelling con StorytellingAgent    │
│   ├── aiPlayground() — copilot publico embebido                       │
│   ├── regenerateStory() — POST API regeneracion con feature gate      │
│   ├── trackAction() — POST API tracking + TTFV                        │
│   └── convertToReal() — POST API conversion flow                     │
│                                                                         │
│   Services:                                                             │
│   ├── DemoInteractiveService (1,523 LOC)                               │
│   │   ├── 10 perfiles demo (producer, winery, cheese, buyer...)       │
│   │   ├── Metricas sinteticas por vertical                            │
│   │   ├── Session management (demo_sessions table, SHA-256 IP)        │
│   │   ├── Analytics aggregation (demo_analytics table, cron)          │
│   │   └── getDemoStory() — 11 historias hardcoded por perfil          │
│   ├── DemoFeatureGateService (149 LOC)                                 │
│   │   ├── Features: page_builder_templates, story_generations         │
│   │   └── Usage tracking por session                                  │
│   ├── DemoJourneyProgressionService (319 LOC)                          │
│   │   ├── Disclosure levels: basic → intermediate → advanced          │
│   │   └── Nudge system (action-based progression)                     │
│   └── GuidedTourService (user_completed_tours table)                   │
│       ├── Tour definitions (demo_welcome: 4 steps)                    │
│       └── getTourDriverJS() → JSON config para JS driver              │
│                                                                         │
│   JS Libraries (self-contained, zero external deps):                    │
│   ├── demo-dashboard.js — chart, tracking, conversion, countdown      │
│   ├── demo-guided-tour.js — overlay, spotlight, popovers, keyboard   │
│   ├── demo-storytelling.js — regenerate fetch, copy, feedback         │
│   └── demo-ai-playground.js — copilot chat, scenarios, typing        │
│                                                                         │
│   Templates (5 themes + 3 partials):                                    │
│   ├── demo-landing.html.twig (profile cards)                          │
│   ├── demo-dashboard.html.twig (initial, with tour steps)             │
│   ├── demo-dashboard-view.html.twig (progressive disclosure)          │
│   ├── demo-ai-storytelling.html.twig (story + regenerate)             │
│   ├── demo-ai-playground.html.twig (chat + scenarios)                 │
│   └── partials: _demo-chart, _demo-cta, _demo-convert-modal          │
│                                                                         │
│   DB Tables: demo_sessions, demo_analytics, user_completed_tours       │
│   Tests: 24 unit tests, 84 assertions                                  │
│   SCSS: _demo.scss (1,180 LOC) in ecosistema_jaraba_core              │
│                                                                         │
│   drupalSettings injection chain:                                       │
│   ├── demo.sessionId, demo.salesHistory (dashboard)                   │
│   ├── demoTour (from GuidedTourService::getTourDriverJS)              │
│   ├── demoStorytelling.regenerateUrl (Url::fromRoute)                 │
│   └── demoPlayground.copilotEndpoint (Url::fromRoute)                 │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘


### 7.2 Interactive Content AI-Powered (jaraba_interactive)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    INTERACTIVE CONTENT SYSTEM                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Plugin Architecture (@InteractiveType)                                │
│   ├── InteractiveTypeManager (discovery + sorting)                     │
│   ├── InteractiveTypeBase (abstract: calculatePercentage, validate)    │
│   └── InteractiveTypeInterface (contract: 8 methods)                   │
│       ├── QuestionSet (assessment, weight:0)                           │
│       ├── InteractiveVideo (media, weight:10)                          │
│       ├── CoursePresentation (interactive, weight:20)                  │
│       ├── BranchingScenario (interactive, weight:30)                   │
│       ├── DragAndDrop (assessment, weight:40)                          │
│       └── Essay (assessment, weight:50)                                │
│                                                                         │
│   Services                                                              │
│   ├── Scorer (delegates to plugin calculateScore)                      │
│   ├── XApiEmitter (xAPI statements per type)                           │
│   └── ContentGenerator (AI-powered content creation)                   │
│                                                                         │
│   EventSubscribers                                                      │
│   ├── CompletionSubscriber (XP + certifications, priority:100)         │
│   └── XapiSubscriber (xAPI emission per type)                          │
│                                                                         │
│   Editor System                                                         │
│   ├── EditorController (zero-region frontend)                          │
│   ├── content-editor.js (orchestrator)                                 │
│   ├── preview-engine.js (iframe preview)                               │
│   └── 6 type-specific editors (JS)                                     │
│                                                                         │
│   CRUD API (/api/v1/interactive/content)                               │
│   ├── POST / (create)                                                  │
│   ├── GET / (list + filter)                                            │
│   ├── PUT /{id} (update)                                               │
│   ├── PATCH /{id}/status (publish/archive)                             │
│   ├── DELETE /{id} (soft delete)                                       │
│   └── POST /{id}/duplicate (clone)                                     │
│                                                                         │
│   Tests: 9 PHPUnit files, 100+ test methods                           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 7.3 Tenant Marca Personal (pepejaraba.com)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PEPEJARABA.COM TENANT                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Seed Script: scripts/seed_pepejaraba.php (766 LOC)                   │
│   ├── Vertical: Marca Personal                                         │
│   ├── SaasPlan: Personal Brand Premium                                 │
│   ├── Tenant: Pepe Jaraba - Marca Personal                            │
│   ├── Domain: pepejaraba.com                                           │
│   └── Theme: #FF8C42 (orange) + #00A9A5 (teal)                       │
│                                                                         │
│   7 PageContent Pages (multiblock mode)                                │
│   ├── Homepage (/) — 7 sections                                        │
│   ├── Sobre Mí (/sobre) — 3 sections                                  │
│   ├── Servicios (/servicios) — 2 sections (pricing table)             │
│   ├── Ecosistema (/ecosistema) — 2 sections (bento grid)              │
│   ├── Blog (/blog) — 2 sections                                       │
│   ├── Recursos (/recursos) — 2 sections                               │
│   └── Contacto (/contacto) — 3 sections                               │
│                                                                         │
│   Navigation: SiteMenu (main) + 6 SiteMenuItems                       │
│   Config: domain.record + design_token_config YAMLs                    │
│   Infra: Nginx vhost + SSL Let's Encrypt + trusted_host_patterns      │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 7.4 Arquitectura de Theming (Federated Design Tokens)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    THEMING ARCHITECTURE                                  │
│                 Federated Design Tokens v2.1                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   SINGLE SOURCE OF TRUTH (SSOT)                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  📦 ecosistema_jaraba_core                                       │  │
│   │  ├── scss/_variables.scss    → $ej-* (compile-time tokens)      │  │
│   │  ├── scss/_injectable.scss   → var(--ej-*) (runtime tokens)     │  │
│   │  └── 33 parciales SCSS       → 7 dashboards, componentes        │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                              │                                          │
│                              ▼                                          │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                    MÓDULOS SATÉLITE                              │  │
│   │  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐             │  │
│   │  │ page_builder │ │ interactive  │ │ credentials  │             │  │
│   │  │ 7 SCSS       │ │ 2 SCSS       │ │ 4 SCSS       │             │  │
│   │  │ package.json │ │ package.json │ │ package.json │             │  │
│   │  └──────────────┘ └──────────────┘ └──────────────┘             │  │
│   │  Solo consumen: var(--ej-*, $fallback)                          │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                              │                                          │
│                              ▼                                          │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                    TEMA DRUPAL                                   │  │
│   │  📦 ecosistema_jaraba_theme                                      │  │
│   │  ├── 45 archivos SCSS      → components, features               │  │
│   │  ├── SDC Components (2)    → Card, Hero                         │  │
│   │  ├── Zero-Region Templates → page--empleabilidad, --emprendimiento│ │
│   │  ├── Preprocess hooks      → empleabilidad + emprendimiento      │  │
│   │  └── Inyección runtime     → hook_page_attachments              │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   MÉTRICAS CONSOLIDACIÓN (Feb 2026):                                    │
│   ✅ 102 archivos SCSS documentados                                     │
│   ✅ 14 módulos con package.json estandarizado                          │
│   ✅ 0 funciones darken()/lighten() deprecadas                          │
│   ✅ 100% módulos satélite usando var(--ej-*)                           │
│                                                                         │
│   📚 DOCUMENTO DETALLADO:                                               │
│   docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md      │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 8. Inteligencia Artificial

### 8.1 Sistema de Agentes IA

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      AGENTES DE IA                                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│                    ┌─────────────────────────┐                          │
│                    │   AGENT ORCHESTRATOR    │                          │
│                    │   (Coordinador Central) │                          │
│                    └───────────┬─────────────┘                          │
│                                │                                        │
│        ┌───────────────────────┼───────────────────────┐                │
│        ▼                       ▼                       ▼                │
│   ┌─────────────┐       ┌─────────────┐       ┌─────────────┐          │
│   │ 📝          │       │ 📣          │       │ 💬          │          │
│   │ Storytelling│       │ Marketing   │       │ Customer    │          │
│   │ Agent       │       │ Agent       │       │ Experience  │          │
│   │             │       │             │       │ Agent       │          │
│   │ • Biografías│       │ • Campañas  │       │ • Upselling │          │
│   │ • Historias │       │ • Posts     │       │ • Reminder  │          │
│   │ • Descrip.  │       │ • Email     │       │ • Support   │          │
│   └─────────────┘       └─────────────┘       └─────────────┘          │
│                                                                         │
│   AI SKILLS VERTICALES (30 predefinidas):                               │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  empleabilidad (7): cv_optimization, interview_preparation...  │  │
│   │  emprendimiento (7): canvas_coaching, pitch_deck_review...     │  │
│   │  agroconecta (6): product_listing_agro, seasonal_marketing...  │  │
│   │  comercioconecta (5): flash_offer_design, local_seo_content... │  │
│   │  serviciosconecta (5): case_summarization, client_comms...     │  │
│   │  Seed: scripts/seed_vertical_skills.php (1,647 LOC)           │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   COPILOT EMPLEABILIDAD (6 modos):                                     │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  EmployabilityCopilotAgent (extiende BaseAgent)                 │  │
│   │  profile_coach: Optimización LinkedIn + personal branding       │  │
│   │  job_advisor: Estrategia búsqueda + matching personalizado      │  │
│   │  interview_prep: Simulación entrevistas + feedback              │  │
│   │  learning_guide: Ruta formativa + skills gap analysis           │  │
│   │  application_helper: Cover letter + follow-up templates         │  │
│   │  faq: Preguntas frecuentes empleo España                        │  │
│   │  DI: @ai.provider + @tenant_brand_voice + @observability        │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   COPILOT JARABALEX (6 modos):                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  LegalCopilotAgent (extiende BaseAgent)                         │  │
│   │  legal_search: Búsqueda guiada jurisprudencia + normativa       │  │
│   │  legal_analysis: Análisis resoluciones + líneas jurisprudenciales│  │
│   │  legal_alerts: Configuración alertas inteligentes               │  │
│   │  legal_citations: Inserción citas en expedientes (4 formatos)   │  │
│   │  legal_eu: Derecho europeo EUR-Lex/CURIA/HUDOC/EDPB            │  │
│   │  faq: Preguntas frecuentes JarabaLex                            │  │
│   │  DI: @ai.provider + @tenant_brand_voice + @observability        │  │
│   │  Regla: LEGAL-RAG-001 (disclaimer + citas verificables)         │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   PROVEEDORES IA SOPORTADOS:                                            │
│   ┌─────────────┐  ┌─────────────┐  ┌─────────────┐                    │
│   │   OpenAI    │  │  Anthropic  │  │  Google AI  │                    │
│   │   GPT-4     │  │   Claude    │  │   Gemini    │                    │
│   └─────────────┘  └─────────────┘  └─────────────┘                    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 8.2 Knowledge Base RAG

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    KNOWLEDGE BASE RAG                                    │
│           (Retrieval-Augmented Generation)                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                    FLUJO DE INDEXACIÓN                           │  │
│   │                                                                  │  │
│   │   Producto     ──▶   Chunking    ──▶   Embeddings   ──▶  Qdrant │  │
│   │   Guardado          (500 tokens)      (OpenAI 1536D)     Vector │  │
│   │                                                             DB   │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                    FLUJO DE BÚSQUEDA                             │  │
│   │                                                                  │  │
│   │   "¿Qué aceite     ──▶   Embedding   ──▶   Qdrant    ──▶  Top 5 │  │
│   │    recomendáis?"         Query           Search         Results │  │
│   │                                              │                   │  │
│   │                                              ▼                   │  │
│   │                                      ┌─────────────┐             │  │
│   │                                      │  LLM + RAG  │             │  │
│   │                                      │  Respuesta  │             │  │
│   │                                      │  Contextual │             │  │
│   │                                      └─────────────┘             │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   ARQUITECTURA DUAL:                                                    │
│   ┌────────────────────────┐    ┌────────────────────────┐             │
│   │ 🐳 Lando (Desarrollo)  │    │ ☁️ IONOS (Producción)   │             │
│   │ http://qdrant:6333     │    │ https://qdrant.cloud    │             │
│   └────────────────────────┘    └────────────────────────┘             │
│                                                                         │
│   FAQ BOT CONTEXTUAL (G114-4):                                          │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │   Cliente      ──▶   Embedding   ──▶   Qdrant    ──▶  Scoring  │  │
│   │   /ayuda chat        Query           Search         3-tier     │  │
│   │                                                                  │  │
│   │   ≥0.75 ──▶ LLM Grounded (Haiku, max_tokens=512, temp=0.3)     │  │
│   │   0.55-0.75 ──▶ Baja confianza + sugerir contacto              │  │
│   │   <0.55 ──▶ Escalación con datos de contacto del negocio       │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 8.3 Centro de Ayuda Clase Mundial (/ayuda)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    CENTRO DE AYUDA CLASE MUNDIAL                        │
│                        /ayuda — Público                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   MÓDULO: jaraba_tenant_knowledge                                       │
│   CONTROLLER: HelpCenterController (index, viewArticle, searchApi)      │
│   TEMPLATE: help-center.html.twig + help-article.html.twig             │
│                                                                         │
│   8 CATEGORÍAS SaaS:                                                    │
│   ┌──────────────┬────────────────┬──────────────┬──────────────┐       │
│   │ getting_     │ account        │ features     │ billing      │       │
│   │ started      │ (user)         │ (zap)        │ (credit-card)│       │
│   ├──────────────┼────────────────┼──────────────┼──────────────┤       │
│   │ ai_copilot   │ integrations   │ security     │ trouble-     │       │
│   │ (cpu)        │ (link)         │ (shield)     │ shooting     │       │
│   └──────────────┴────────────────┴──────────────┴──(tool)──────┘       │
│                                                                         │
│   ARQUITECTURA:                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │  Hero (search + trust signals + article count)                   │   │
│   │  Quick Links → /soporte/crear, /contacto, /ayuda/kb, /blog      │   │
│   │  Categorías Grid (8 cards, ordered by getCategoryMeta())         │   │
│   │  Artículos Populares (top 6 FAQs)                                │   │
│   │  KB Cross-link (si kb_articles_count > 0)                        │   │
│   │  FAQs por Categoría (expandible, link a artículo individual)     │   │
│   │  CTA Contacto (botones /contacto + /soporte)                     │   │
│   │  FAQ Bot Widget (G114-4, faq-bot-widget.html.twig)               │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│   BÚSQUEDA UNIFICADA:                                                   │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │  searchApi() → TenantFaq LIKE + KbArticle LIKE (hasDefinition)  │   │
│   │  JS: drupalSettings.helpCenter.searchApiUrl (LANGPREFIX-001)    │   │
│   │  Autocomplete: FAQ items + KB items (type badge)                 │   │
│   │  URLs: Url::fromRoute() — never hardcoded                       │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│   SEO:                                                                  │
│   - FAQPage JSON-LD (todas las FAQs visibles)                          │
│   - BreadcrumbList JSON-LD (Home > Centro de Ayuda)                    │
│   - QAPage JSON-LD (artículo individual)                               │
│   - OG + Twitter meta tags                                              │
│   - Canonical URL                                                       │
│                                                                         │
│   SEED DATA:                                                            │
│   - update_10003: 25 FAQs plataforma (tenant_id = NULL)                │
│   - Migración categorías e-commerce → SaaS                             │
│   - Idempotente: notExists('tenant_id') + condition('question')         │
│                                                                         │
│   INTEGRACIÓN:                                                          │
│   - jaraba_support: Quick Links → /soporte/crear                       │
│   - Knowledge Base: Cross-link + búsqueda unificada                    │
│   - FAQ Bot: Widget incluido via Twig include                          │
│   - Footer: "Centro de Ayuda|/ayuda" en col2_links                     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 9. Seguridad y Cumplimiento

### 9.1 Capas de Seguridad

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      SEGURIDAD MULTINIVEL                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   CAPA 1: INFRAESTRUCTURA                                               │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  • HTTPS obligatorio (Let's Encrypt)                             │  │
│   │  • Firewall + Rate Limiting                                      │  │
│   │  • Backups automáticos cada 4h                                   │  │
│   │  • Cifrado at-rest y in-transit                                  │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   CAPA 2: APLICACIÓN                                                    │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  • Drupal Security Updates automáticos                           │  │
│   │  • CSRF Protection                                               │  │
│   │  • XSS Prevention (Twig autoescape)                              │  │
│   │  • SQL Injection Prevention (DB API)                             │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   CAPA 3: DATOS                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  • Aislamiento por Group Module                                  │  │
│   │  • API Keys en Key Module (config sync git-tracked)               │  │
│   │  • Config Sync: config/sync/ (589 YML, git-tracked)              │  │
│   │  • Logs sanitizados (sin datos sensibles)                        │  │
│   │  • AuditLog inmutable (eventos seguridad con IP, actor, tenant)  │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   CAPA 4: COMPLIANCE (G115-1)                                           │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  • Dashboard /admin/seguridad (25+ controles)                    │  │
│   │  • SOC 2 Type II + ISO 27001:2022 + ENS RD 311/2022 + GDPR      │  │
│   │  • Verificación security headers en tiempo real                  │  │
│   │  • Auto-refresh cada 30 segundos                                 │  │
│   │  • GDPR Drush: gdpr:export (Art.15), gdpr:anonymize (Art.17)    │  │
│   │  • Security CI: Daily Trivy + ZAP + SARIF (GitHub Security)      │  │
│   │  • Incident Response Playbook: SEV1-4, AEPD 72h                  │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 9.2 Hallazgos de Auditoría de Seguridad (2026-02-06)

> **Referencia:** [Auditoría Profunda SaaS Multidimensional v1](./tecnicos/auditorias/20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md)

La auditoría profunda multidimensional del 2026-02-06 identificó **9 hallazgos de seguridad** (5 críticos + 4 altos):

| ID | Severidad | Hallazgo | Estado |
|----|-----------|----------|--------|
| SEC-01 | CRITICA | Inyección de prompts vía configuración de tenant | Pendiente |
| SEC-02 | CRITICA | Webhook público sin verificación de firma HMAC | Pendiente |
| SEC-03 | CRITICA | Claves Stripe en config DB en vez de env vars | Pendiente |
| SEC-04 | CRITICA | Qdrant sin autenticación por API key | Pendiente |
| SEC-05 | CRITICA | APIs públicas `/api/v1/*` sin autenticación | Pendiente |
| SEC-06 | ALTA | Rutas demo sin restricción de tipo en parámetros | Pendiente |
| SEC-07 | ALTA | Mensajes de error internos expuestos al usuario | Pendiente |
| SEC-08 | ALTA | Sin configuración CORS ni CSP headers | **Completado** — SecurityHeadersSubscriber (CORS configurable, CSP, X-Frame-Options, HSTS, Permissions-Policy, Referrer-Policy, X-Permitted-Cross-Domain-Policies, Vary:Host multi-tenant) |
| SEC-09 | ALTA | Re-index sin verificación de tenant ownership | Pendiente |

**Nuevas directrices derivadas:**
- Toda clave API debe almacenarse en variables de entorno, nunca en configuración de Drupal
- Todo endpoint que invoque LLM/embedding debe tener rate limiting por tenant y usuario
- Toda interpolación en prompts del sistema debe sanitizarse contra whitelist
- Filtros Qdrant para tenant deben usar `must` (AND), nunca `should` (OR)

### 9.3 Cumplimiento GDPR

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      CUMPLIMIENTO GDPR                                   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   DERECHOS ARCO-POL IMPLEMENTADOS:                                      │
│                                                                         │
│   ┌──────────────┬──────────────────────────────────────────────────┐  │
│   │ Derecho      │ Implementación                                   │  │
│   ├──────────────┼──────────────────────────────────────────────────┤  │
│   │ Acceso       │ Exportación de datos en JSON/CSV                 │  │
│   │ Rectificación│ Edición de perfil por usuario                    │  │
│   │ Cancelación  │ Anonimización automática                         │  │
│   │ Oposición    │ Opt-out de comunicaciones                        │  │
│   │ Portabilidad │ Descarga de datos en formato estándar            │  │
│   │ Olvido       │ Eliminación completa tras 30 días                │  │
│   │ Limitación   │ Suspensión de procesamiento                      │  │
│   └──────────────┴──────────────────────────────────────────────────┘  │
│                                                                         │
│   RETENCIÓN DE DATOS:                                                   │
│   • Datos de usuario: Hasta baja + 30 días                              │
│   • Logs de acceso: 90 días                                             │
│   • Backups: 30 días                                                    │
│   • Datos fiscales: 7 años (requisito legal)                            │
│                                                                         │
│   HERRAMIENTAS GDPR AUTOMATIZADAS:                                     │
│   • drush gdpr:export {uid}     → Art. 15 Acceso (JSON)                │
│   • drush gdpr:anonymize {uid}  → Art. 17 Olvido (hash replacement)    │
│   • drush gdpr:report           → Informe compliance general           │
│   • /tenant/export (self-service) → Art. 20 Portabilidad (ZIP)         │
│   │   jaraba_tenant_export: 6 secciones datos, async Queue API,        │
│   │   descarga ZIP con manifest JSON + CSV + archivos originales       │
│   • Playbook: SECURITY_INCIDENT_RESPONSE_PLAYBOOK.md                   │
│   • AEPD notificación: 72h (GDPR Art. 33)                              │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 9.4 Cumplimiento Fiscal — Stack VeriFactu + Facturae + E-Factura

> **Estado:** FASES 0-11 COMPLETADAS. 3 modulos implementados + integration layer.
> **Prioridad:** P0 CRITICO — Deadline legal 1 enero 2027 (sociedades) / 1 julio 2027 (autonomos).
> **Sancion:** Hasta 50.000 EUR/ejercicio por software no conforme (RD 1007/2023).

**Impacto dual:** Jaraba Impact S.L. como emisor de facturas SaaS a tenants + tenants como emisores a sus clientes finales.

| Modulo | Normativa | Estado | Entidades | Servicios | API Endpoints | Tests |
|--------|-----------|--------|-----------|-----------|---------------|-------|
| **`jaraba_verifactu`** | RD 1007/2023, Orden HAC/1177/2024 | IMPLEMENTADO (F0-F5) | 4 | 7 | 21 | 23 |
| **`jaraba_facturae`** | Ley 25/2013, Facturae 3.2.2 | IMPLEMENTADO (F6-F7) | 3 | 6 | 21 | 26 |
| **`jaraba_einvoice_b2b`** | Ley 18/2022 (Crea y Crece) | IMPLEMENTADO (F8-F9) | 4 | 6 | 24 | 23 |
| **Integration Layer** | Transversal (F10-F11) | IMPLEMENTADO | — | 3 | — | 38 |
| **TOTAL** | | **12 FASES** | **11** | **22** | **66** | **110** |

**FASE 11 — Integration Layer (completada 2026-02-16):**
- `FiscalComplianceService`: Score 0-100 por tenant, 5 factores x 20 pts (VeriFactu chain, AEAT remision, certificados, FACe rechazos, B2B morosidad)
- `FiscalDashboardController`: Ruta `/admin/jaraba/fiscal`, dashboard unificado
- `FiscalInvoiceDelegationService`: Deteccion automatica B2G/B2B/nacional por NIF prefix (Q/S/P = B2G)
- KPI card en Admin Center + slide-panel trigger
- 3 MJML email alerts (certificate_expiring, verifactu_chain_break, face_invoice_rejected)
- 9 features en FeatureAccessService FEATURE_ADDON_MAP
- Design token config YAML (17 color tokens fiscales)
- 38 tests (unit + kernel + functional)

**Arquitectura integrada:** Un invoice puede tener simultaneamente un VeriFactu record (obligatorio para todos) + un Facturae document (B2G) + un E-Invoice document (B2B). Los tres modulos comparten CertificateManagerService (PKCS#12) y se integran con `jaraba_billing` (BillingInvoice como fuente).

### 9.5 Stack Compliance Legal N1 — Privacidad, Legal, DR

> **Estado:** IMPLEMENTADO (3 modulos, 198 archivos, +13,281 LOC).
> **Nivel N1 Foundation:** GDPR DPA + Legal Terms SaaS + Disaster Recovery.
> **Auditoria N1:** 12.5% (pre) → 95%+ (post-implementacion).

| Modulo | Alcance | Entidades | Servicios | Templates | Tests |
|--------|---------|-----------|-----------|-----------|-------|
| **`jaraba_privacy`** | GDPR DPA + LOPD-GDD, consentimiento cookies, DSAR, breaches | 5 (DpaAgreement, PrivacyPolicy, CookieConsent, ProcessingActivity, DataRightsRequest) | 5 (DpaManager, CookieConsentManager, DataRightsHandler, BreachNotification, PrivacyPolicyGenerator) | privacy-dashboard + 5 partials + cookie-banner | 4 unit |
| **`jaraba_legal`** | ToS/SLA/AUP versioning, offboarding, whistleblower | 6 (ServiceAgreement, SlaRecord, UsageLimitRecord, AupViolation, OffboardingRequest, WhistleblowerReport) | 5 (TosManager, SlaCalculator, AupEnforcer, OffboardingManager, WhistleblowerChannel) + LegalApiController (12 endpoints) | legal-dashboard + 5 partials + whistleblower-form | 4 unit |
| **`jaraba_dr`** | Backup verification, failover testing, incident management | 3 (BackupVerification, DrIncident, DrTestResult) | 5 (BackupVerifier, DrTestRunner, FailoverOrchestrator, IncidentCommunicator, StatusPageManager) | dr-dashboard + dr-status-page + 4 partials | 4 unit |

**Gap-filling completado (commit e16d3e28):** Servicios con logica de produccion completa (+16,035 LOC):
- `jaraba_dr`: 5 servicios con logica real (verificacion backups, ejecucion DR tests, failover orquestacion, comunicacion incidentes, gestion status page)
- `jaraba_legal`: 5 servicios con logica real + LegalApiController (12 REST endpoints: ToS 3, SLA 2, AUP 2, Offboarding 3, Whistleblower 2 publicos)
- `jaraba_privacy`: JS behaviors (cookie-banner, dpa-signature, privacy-dashboard) + 8 SCSS partials + 4 unit tests

**ComplianceAggregatorService** (ecosistema_jaraba_core):
- Servicio transversal que agrega KPIs de los 3 modulos compliance en un dashboard unificado
- 9 KPIs (3 por modulo): dpa_coverage, arco_pol_sla, cookie_consent_rate, tos_acceptance_rate, sla_compliance, aup_violations, backup_health, dr_test_coverage, status_page_uptime
- Score global 0-100 ponderado con grade A-F
- Alertas automaticas: critico <50%, warning <80%
- Inyeccion condicional: modulos no instalados reportan 'not_available' (NULL guard pattern)
- **CompliancePanelController**: Ruta `/admin/jaraba/compliance`, auto-refresh AJAX 60s, API endpoint `/api/v1/compliance/overview`

**Infraestructura theme:**
- 3 zero-region page templates (page--privacy, page--legal-compliance, page--dr-status)
- 3 compliance partials (metric-card, status-badge, timeline)
- 36 SVG compliance icons (duotone + solid)
- 24 SCSS partials (8 por modulo: variables, main, dashboard, responsive + 4 componentes)
- Body classes + template suggestions en ecosistema_jaraba_theme.theme

---

## 10. Infraestructura y DevOps

### 10.1 Entornos

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         ENTORNOS                                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                    DESARROLLO (Lando)                            │  │
│   │  • jaraba-saas.lndo.site                                         │  │
│   │  • qdrant.jaraba-saas.lndo.site                                  │  │
│   │  • Docker containers locales                                     │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                           │                                             │
│                           ▼ git push                                    │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                    CI/CD (GitHub Actions)                        │  │
│   │  • Tests automáticos                                             │  │
│   │  • Linting (PHPStan, ESLint)                                     │  │
│   │  • Build + Deploy                                                │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                           │                                             │
│                           ▼ deploy                                      │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                    PRODUCCIÓN (IONOS)                            │  │
│   │  • jarabaimpact.com                                              │  │
│   │  • PHP 8.4 + MariaDB                                             │  │
│   │  • Qdrant Cloud                                                  │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.2 Pipeline CI/CD

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      PIPELINE CI/CD                                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   git push main                                                         │
│        │                                                                │
│        ▼                                                                │
│   ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐             │
│   │  Lint   │───▶│  Test   │───▶│Security │───▶│  Build  │───▶│Deploy│  │
│   │         │    │         │    │         │    │         │    │      │  │
│   │ PHPStan │    │ PHPUnit │    │Composer │    │Composer │    │ Git  │  │
│   │ ESLint  │    │ 80% cov │    │ Trivy   │    │  SCSS   │    │Drush │  │
│   └─────────┘    └─────────┘    └─────────┘    └─────────┘    └──────┘  │
│                                                                         │
│   + SECURITY SCAN (daily cron):                                        │
│   Trivy + OWASP ZAP + npm/composer audit → SARIF → GitHub Security     │
│                                                      │                 │
│                                        ┌─────────────┘                 │
│                                        ▼                               │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                     DEPLOY STEPS                                 │  │
│   │  1. git reset --hard origin/main                                 │  │
│   │  2. composer install --no-dev                                    │  │
│   │  3. drush updatedb -y                                            │  │
│   │  4. UUID sync (config vs site)                                   │  │
│   │  5. drush config:import -y  ← lee config/sync/ (git-tracked)    │  │
│   │  6. drush cache:rebuild                                          │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                        │                               │
│                                        ▼                               │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                     SLACK NOTIFICATIONS                          │  │
│   │  ✅ Deploy exitoso a producción                                  │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.3 Monitoring Stack (Production Gaps v8.0.0)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      MONITORING STACK                                    │
│                 Docker Compose Standalone                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   ┌────────────────────────────────────────────────────────────────────┐│
│   │                    METRICS (Prometheus :9090)                       ││
│   │  Scrape targets: drupal, mysql, qdrant, node, loki                 ││
│   │  14 alert rules: ServiceDown, HighErrorRate, SlowResponseTime,     ││
│   │  DatabaseConnectionPoolExhausted, QdrantDiskFull,                  ││
│   │  StripeWebhookFailures, SSLCertificateExpiring...                  ││
│   └────────────────────────────────────────────────────────────────────┘│
│                              │                                          │
│                              ▼                                          │
│   ┌────────────────────────────────────────────────────────────────────┐│
│   │                    VISUALIZATION (Grafana :3001)                    ││
│   │  Dashboards: Platform Overview, API Performance, Business KPIs     ││
│   │  Data sources: Prometheus + Loki                                   ││
│   └────────────────────────────────────────────────────────────────────┘│
│                                                                         │
│   ┌──────────────────────────┐  ┌──────────────────────────┐           │
│   │ LOGS                      │  │ ALERTING                  │           │
│   │ Loki :3100 + Promtail    │  │ AlertManager :9093         │           │
│   │ drupal, php-fpm, nginx   │  │ critical→Slack+email       │           │
│   │ 720h retention, TSDB     │  │ warning→Slack #alerts      │           │
│   └──────────────────────────┘  └──────────────────────────┘           │
│                                                                         │
│   FICHEROS:                                                            │
│   monitoring/docker-compose.monitoring.yml                              │
│   monitoring/prometheus/prometheus.yml + rules/jaraba_alerts.yml        │
│   monitoring/prometheus/rules/scaling_alerts.yml (F10)                  │
│     └── 10 alert rules + 5 recording rules (3 fases escalado)          │
│   monitoring/loki/loki-config.yml                                      │
│   monitoring/promtail/promtail-config.yml                              │
│   monitoring/alertmanager/alertmanager.yml                              │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.4 Security CI Automatizado

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    SECURITY SCAN PIPELINE                                │
│                 .github/workflows/security-scan.yml                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Cron: daily 02:00 UTC                                                 │
│        │                                                                │
│        ▼                                                                │
│   ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐             │
│   │Composer │───▶│  npm    │───▶│  Trivy  │───▶│ OWASP   │             │
│   │ audit   │    │  audit  │    │  FS     │    │  ZAP    │             │
│   │         │    │         │    │  scan   │    │ baseline│             │
│   └─────────┘    └─────────┘    └─────────┘    └─────────┘             │
│                                                      │                 │
│                                               SARIF Upload             │
│                                               GitHub Security          │
│                                                                         │
│   GDPR DRUSH COMMANDS:                                                 │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  drush gdpr:export {uid}     → Art. 15 (Acceso) — JSON export  │  │
│   │  drush gdpr:anonymize {uid}  → Art. 17 (Olvido) — Hash replace │  │
│   │  drush gdpr:report           → Informe compliance general      │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   INCIDENT RESPONSE:                                                   │
│   docs/tecnicos/SECURITY_INCIDENT_RESPONSE_PLAYBOOK.md                 │
│   SEV1-SEV4 matrix, AEPD 72h (GDPR Art. 33), templates comunicación   │
│                                                                         │
│   DEPENDABOT REMEDIATION (2026-02-14):                                 │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  42 alertas → 0 abiertas (2 critical + 14 high + 15 med + 11 low)│
│   │  Validación STAGING_URL pre-ZAP scan (AUDIT-SEC-N17)           │  │
│   │  npm overrides para deps transitivas bloqueadas por upstream   │  │
│   │  web/core/yarn.lock → dismiss documentado (Drupal upstream)    │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.5 Go-Live Procedures

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    GO-LIVE RUNBOOK                                       │
│                 scripts/golive/ + docs/tecnicos/                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   SCRIPTS EJECUTABLES:                                                 │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  01_preflight_checks.sh  │ 24 validaciones pre-lanzamiento     │  │
│   │  (PHP, MariaDB, Redis, Qdrant, Stripe, SSL, DNS, módulos)      │  │
│   ├─────────────────────────────────────────────────────────────────┤  │
│   │  02_validation_suite.sh  │ Smoke tests por vertical, API,      │  │
│   │  CSRF checks, endpoint health                                   │  │
│   ├─────────────────────────────────────────────────────────────────┤  │
│   │  03_rollback.sh          │ Rollback automatizado 7 pasos       │  │
│   │  con notificaciones Slack                                       │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   FASES RUNBOOK:                                                       │
│   1. Pre-Go-Live (24h antes)  → preflight_checks.sh                   │
│   2. Deploy                    → git pull, composer, drush              │
│   3. Validación                → validation_suite.sh                   │
│   4. Go/No-Go                  → Criterios cuantitativos               │
│   5. Soft Launch               → 10% tráfico, monitoring               │
│   6. Public Launch             → 100% tráfico                          │
│                                                                         │
│   DOCUMENTO: docs/tecnicos/GO_LIVE_RUNBOOK.md (708 LOC, RACI)         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.6 Per-Tenant Backup/Restore (F10 Scaling Infrastructure)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PER-TENANT BACKUP/RESTORE                             │
│                 scripts/restore_tenant.sh                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   COMANDOS:                                                            │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  ./restore_tenant.sh backup <tenant_id>   → Backup completo    │  │
│   │  ./restore_tenant.sh restore <tenant_id> <file>  → Restore     │  │
│   │  ./restore_tenant.sh list <tenant_id>     → Lista backups      │  │
│   │  ./restore_tenant.sh tables               → Lista tablas       │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   AUTO-DISCOVERY: INFORMATION_SCHEMA query para encontrar 159+         │
│   tablas con columna tenant_id. Zero hardcoding.                       │
│                                                                         │
│   ENTORNOS: ENVIRONMENT=lando (default) | ENVIRONMENT=ionos (prod)    │
│                                                                         │
│   ESCALADO HORIZONTAL (3 Fases):                                       │
│   docs/arquitectura/scaling-horizontal-guide.md                        │
│   Fase 1: Single Server IONOS L-16 (≤50 tenants)                      │
│   Fase 2: Separated DB (≤200 tenants)                                  │
│   Fase 3: Load Balanced HAProxy + Redis Cluster (1000+ tenants)        │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.7 Backup Automatizado Diario (Daily Backup)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    DAILY BACKUP SYSTEM                                   │
│              .github/workflows/daily-backup.yml                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   TRIGGER: Cron diario 03:00 UTC (05:00 CET) + workflow_dispatch       │
│                                                                         │
│   PROCESO:                                                              │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  1. SSH → IONOS via webfactory/ssh-agent@v0.9.0                 │  │
│   │  2. drush sql-dump --gzip (fallback: mysqldump)                 │  │
│   │  3. Verificación integridad: gzip -t                            │  │
│   │  4. Naming: db_daily_{YYYYMMDD_HHMMSS}.sql.gz                  │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   DIRECTORIOS (separados para GoodSync):                              │
│   • ~/backups/daily/       → Backups automáticos diarios               │
│   • ~/backups/pre_deploy/  → Backups pre-deploy (deploy.yml)           │
│                                                                         │
│   ROTACIÓN INTELIGENTE:                                                │
│   • Diarios < 30 días: se mantienen todos                              │
│   • 30-84 días: solo lunes (semanal)                                   │
│   • > 84 días: se eliminan                                             │
│   • Pre-deploy > 30 días: se eliminan                                  │
│                                                                         │
│   ALERTAS: Slack via SLACK_WEBHOOK si falla                            │
│   VERIFICACIÓN: verify-backups.yml busca en ambos subdirectorios       │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.8 Testing Infrastructure

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    TESTING INFRASTRUCTURE                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   UNIT TESTS (PHPUnit 11):                                             │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  789+ tests (730 pass, Unit + Kernel + Functional)              │  │
│   │  Coverage threshold: 80% (enforced in CI)                       │  │
│   │  Codecov upload + GitHub annotations                            │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   PERFORMANCE (k6):                                                    │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  tests/performance/load_test.js                                 │  │
│   │  Scenarios: smoke (1 VU) → load (50 VUs) → stress (200 VUs)    │  │
│   │  Endpoints: homepage, login, API skills, checkout               │  │
│   │  Thresholds: p95 < 500ms, error rate < 1%                      │  │
│   ├─────────────────────────────────────────────────────────────────┤  │
│   │  tests/performance/multi_tenant_load_test.js (F10)              │  │
│   │  4 Scenarios: multi_tenant_api, crm_pipeline, tenant_isolation, │  │
│   │  scaling_breakpoint (ramp to 100 VUs)                           │  │
│   │  7 Custom metrics: per-API category + tenant isolation failures │  │
│   │  Thresholds: crm p95<500ms, isolation failures=0                │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   VISUAL REGRESSION (BackstopJS):                                      │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  tests/visual/backstop.json                                     │  │
│   │  10 páginas × 3 viewports (375px, 768px, 1440px)               │  │
│   │  Puppeteer engine, misMatchThreshold 0.1-0.5                   │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   E2E (Cypress):                                                       │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  12 suites, ~670 líneas                                         │  │
│   │  Canvas editor, dashboard, AI panel, multi-tenant, a11y         │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.8.1 Validacion Arquitectonica Automatizada (ARCH-VALIDATE-001)

```
┌─────────────────────────────────────────────────────────────────────────┐
│              AUTOMATED ARCHITECTURAL VALIDATION SYSTEM                    │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   19 Scripts (scripts/validation/):                                     │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  validate-services-di.php       DI type mismatches YAML↔PHP    │  │
│   │  validate-routing.php           Route→Controller existence     │  │
│   │  validate-entity-integrity.php  ENTITY-001 + 6 checks         │  │
│   │  validate-query-chains.php      QUERY-CHAIN-001 detection      │  │
│   │  validate-config-sync.sh        config/install vs config/sync  │  │
│   │  validate-container-deps.php    Container DI integrity         │  │
│   │  validate-circular-deps.php     Circular reference detection   │  │
│   │  validate-logger-injection.php  Logger DI consistency          │  │
│   │  validate-service-consumers.php Orphan service detection       │  │
│   │  validate-compiled-assets.php   SCSS→CSS freshness             │  │
│   │  validate-tenant-isolation.php  Tenant query filter            │  │
│   │  validate-test-coverage-map.php Test coverage mapping          │  │
│   │  validate-optional-deps.php     Cross-module @? enforcement    │  │
│   │  validate-controller-readonly   Controller inherited props     │  │
│   │  validate-presave-resilience    Presave hook try-catch         │  │
│   │  validate-entity-schema-sync    Orphan computed + translatable │  │
│   │  validate-btn-contrast-dark     Button contrast dark bg        │  │
│   │  validate-nav-i18n.php          I18N hardcoded path detection  │  │
│   │  validate-deploy-readiness.php  Production deploy checks       │  │
│   │  validate-env-parity.php        Dev/Prod parity (14 checks)    │  │
│   │  validate-no-hardcoded-prices   EUR price hardcoding in Twig   │  │
│   │  validate-twig-ortografia.php  Spanish tildes+ñ in {% trans %}│  │
│   │  validate-all.sh                Orchestrator --fast / --full   │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   4 Integration Points:                                                 │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  Pre-commit hook    Conditional (staged *.services.yml,        │  │
│   │                     *.routing.yml, Entity/*.php) → --fast <3s  │  │
│   │  CI (ci.yml)        Step before PHPStan → --full               │  │
│   │  Deploy (deploy.yml) Step pre-deploy → --full                  │  │
│   │  Lando tooling      lando validate / lando validate-fast       │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   Auto-discovery: glob() for modules/services/routes/entities          │
│   Zero hardcoded lists. New modules validated automatically.           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.9 Email System (MJML Templates)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    EMAIL TEMPLATE SYSTEM                                 │
│                 jaraba_email module                                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   TEMPLATES MJML (35 transaccionales + 1 base):                        │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  auth/       (5) verify, welcome, password_reset/changed, login │  │
│   │  billing/    (7) invoice, payment_failed, subscription,         │  │
│   │                   upgrade, trial, cancel, dunning               │  │
│   │  marketplace/(6) order_confirmed, new_order_seller, shipped,    │  │
│   │                   delivered, payout, review                     │  │
│   │  empleabilidad/(10) job_match, application, new_application,    │  │
│   │                      shortlisted, expired + seq_onboarding,     │  │
│   │                      seq_reactivation, seq_upsell,              │  │
│   │                      seq_interview_prep, seq_post_hire           │  │
│   │  emprendimiento/(6) welcome_entrepreneur, diagnostic_completed, │  │
│   │                      canvas_milestone, experiment_result,       │  │
│   │                      mentor_matched, weekly_progress            │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   FLUJO:                                                               │
│   template_id ──▶ TemplateLoaderService ──▶ {{ variables }}            │
│                         │                       │                      │
│                         ▼                       ▼                      │
│                   MJML file              MjmlCompilerService           │
│                                               │                        │
│                                               ▼                        │
│                                         HTML responsive                │
│                                                                         │
│   SERVICIO: jaraba_email.template_loader                               │
│   COMPILER: jaraba_email.mjml_compiler                                 │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.6 Developer Experience Pipeline (Claude Code)

```
┌─────────────────────────────────────────────────────────────────────────┐
│              CLAUDE CODE DEVELOPMENT PIPELINE                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                   CAPA 1: PROTECCION (PreToolUse)                │  │
│   │  • pre-tool-use.sh — Bloquea: composer.lock, .lando.yml,        │  │
│   │    settings.php, settings.secrets.php, phpstan-baseline.neon     │  │
│   │  • Advierte: master docs (DOC-GUARD-001), CI workflows          │  │
│   │  • JSON stdout: permissionDecision deny/ask + exit code 2/0     │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                           │                                             │
│                           ▼                                             │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                   CAPA 2: LINT (PostToolUse)                     │  │
│   │  • post-edit-lint.sh — 4 tipos de archivo:                       │  │
│   │    PHP: syntax + ContentEntityForm + eval/exec                   │  │
│   │    SCSS: hex hardcoded + @import + rgba(#hex)                    │  │
│   │    JS: URL hardcoded + innerHTML sin checkPlain                  │  │
│   │    Twig: |raw + textos sin {% trans %}                           │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                           │                                             │
│                           ▼                                             │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                   CAPA 3: SUBAGENTES                             │  │
│   │  • reviewer — Code review con 20+ directrices del proyecto       │  │
│   │  • tester — Generacion tests PHP 8.4 (7 reglas criticas)        │  │
│   │  • security-auditor — 10 categorias de escaneo                  │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                           │                                             │
│                           ▼                                             │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                   CAPA 4: SLASH COMMANDS                         │  │
│   │  • /fix-issue [#N] — Workflow 8 pasos con branch + PR           │  │
│   │  • /create-entity [mod] [id] — Scaffold 9 archivos completo     │  │
│   │  • /verify-runtime [mod] — 12 checks RUNTIME-VERIFY-001         │  │
│   │  • /audit-wcag [url] — 10 categorias WCAG 2.1 AA                │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                           │                                             │
│                           ▼                                             │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │                   CAPA 5: INTEGRACIONES                          │  │
│   │  • MCP Stripe — .mcp.json con @stripe/mcp (stdio)               │  │
│   │  • CLAUDE.md — 236 LOC con identidad proyecto + stack + reglas  │  │
│   │  • Estado del SaaS — 793 LOC para Claude Chat Project Knowledge │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   ARCHIVOS: 14 creados en .claude/ + docs/                              │
│   TESTS: 30 funcionales (5+14+5+5+1)                                   │
│   DIRECTRICES: HOOK-PRETOOLUSE-001, HOOK-POSTLINT-001,                 │
│                CLAUDE-SUBAGENT-001                                      │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 11. Roadmap

### 11.1 Estado Actual

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      NIVEL DE MADUREZ                                    │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Nivel 1: Inicial              ████████████████████████████████  100%  │
│   Nivel 2: Documentada          ████████████████████████████████  100%  │
│   Nivel 3: Gestionada           ████████████████████████████████  100%  │
│   Nivel 4: Optimizada           ████████████████████████████████  100%  │
│   Nivel 5: Adaptativa           ████████████████████████████████  100%  │
│                                                                         │
│                         NIVEL ACTUAL: 5.0 / 5.0                         │
│                                                                         │
│   Plan Cierre Gaps Clase Mundial: 12/12 Fases Completadas              │
│   F1-F8: ECA, Freemium, Visitor, Landing, Onboarding, Admin, Elena,   │
│          Merchant Copilot                                               │
│   F9: B2B Sales Flow (BANT + SalesPlaybook)                            │
│   F10: Scaling Infra (restore_tenant + k6 + Prometheus)                │
│   F11: IA Clase Mundial (Brand Voice + Prompt A/B + MultiModal)        │
│   F12: Lenis Integration Premium (smooth scroll)                       │
│                                                                         │
│   Sprint Diferido Backlog: 22/22 TODOs resueltos (5 fases)             │
│   FASE 1: Quick Wins (pricing, ratings, canvas, player review)         │
│   FASE 2: UX Sprint 5 (header SaaS, i18n, dynamic fields, a11y)       │
│   FASE 3: Knowledge Base CRUD (FAQs, policies, documents)              │
│   FASE 4: Infraestructura (agent re-exec, tests, webhooks, entities)   │
│   FASE 5: Integraciones (tokens V2.1, batch dispatch, stock, GEO)      │
│                                                                         │
│   Admin Center Premium (Spec f104): 7/7 FASEs completadas              │
│   FASE 1: Shell Layout (sidebar + topbar + aggregator)                 │
│   FASE 2: DataTable reusable + Tenants page                           │
│   FASE 3: Users page + Avatar Detection                               │
│   FASE 4: Finance Centro (MRR, ARPU, Churn, LTV)                      │
│   FASE 5: Alerts & Playbooks (FocAlert + CsPlaybook)                  │
│   FASE 6: Analytics + Logs (Chart.js, AI Telemetry)                    │
│   FASE 7: Settings + Dark Mode + A11y WCAG 2.1 AA                     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 11.2 Hitos 2026

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        HITOS 2026                                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   ENE ──●── KB RAG Qdrant operativo ✅                                  │
│         │                                                               │
│   ENE ──●── FOC v2 Diseño + Documentación 🔄                             │
│         │                                                               │
│   FEB ──●── FOC Fase 1: Entidades Financieras                           │
│         │                                                               │
│   FEB ──●── FOC Fase 2: Stripe Connect Destination Charges              │
│         │                                                               │
│   MAR ──●── Game Day #1 Chaos Engineering                               │
│         │                                                               │
│   ABR ──●── FOC Fase 3: Motor Proyecciones PHP-ML                       │
│         │                                                               │
│   MAY ──●── FOC Fase 4: Alertas ECA + Playbooks                         │
│         │                                                               │
│   JUN ──●── Predictive Capacity Planning                                │
│         │                                                               │
│   SEP ──●── Game Day #2                                                 │
│         │                                                               │
│   DIC ──●── NIVEL 5.0 CERTIFICADO ⭐                                    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```


---

## 12. Estado de Auditoría (2026-02-15)

### 12.1 Métricas de la Auditoría Integral

<!-- AUDIT-SPEC-N03: Content Entities actualizado de 268 a 375 (verificado via @ContentEntityType grep 2026-02-18) -->
La auditoría integral del 2026-02-13 analizó la plataforma desde 15 disciplinas simultáneas, cubriendo 69 módulos custom (actualizado 2026-02-16), 375+ Content Entities y ~769 rutas API.

| Dimensión | Hallazgos | Críticos | Altos | Medios | Bajos |
|-----------|-----------|----------|-------|--------|-------|
| **Seguridad** | 19 | 3 | 6 | 7 | 3 |
| **Rendimiento** | 17 | 3 | 5 | 6 | 3 |
| **Consistencia** | 20 | 1 | 7 | 8 | 4 |
| **Specs vs Implementación** | 9 | 0 | 2 | 5 | 2 |
| **TOTAL** | **65** | **7** | **20** | **26** | **12** |

### 12.2 Evaluación de Madurez Post-Remediación

La madurez se eleva de 4.5/5.0 a **4.9/5.0** tras completar FASE 1 (7 Críticos) y FASE 2 (8 Altos) de la remediación:

| Área | Pre-Remediación | Post-Remediación | Cambios Realizados |
|------|-----------------|------------------|-------------------|
| Multi-Tenancy | 4.5 | **5.0** | AccessControlHandler en 34 entidades, tenant_id migrado a entity_reference en 6 entidades, TenantContextService deduplicado |
| Seguridad | 4.0 | **4.8** | HMAC en webhooks (Stripe + WhatsApp), `_permission` en 100+ rutas, `\|raw` sanitizado server-side. Pendiente: PKCE, rate limiting, SAST |
| Rendimiento | 4.0 | **4.8** | Índices DB en entidades de alto volumen, LockBackendInterface financiero, social publish async, caching en 6 servicios, 4 cron→QueueWorker. Pendiente: CDN, lazy-load images |
| Consistencia | 4.5 | **4.8** | Servicios duplicados eliminados, PUT→PATCH estandarizado, config schemas, @import→@use, core_version unificado. Pendiente: 303 CSS --ej-*, 76 rutas sin /api/v1/, 28 formatos JSON |
| Código | 5.0 | **5.0** | Estándares PHP 8.4 / Drupal 11 correctos |
| Frontend | 4.5 | **4.8** | @import→@use completado, Drupal.behaviors parcial. Pendiente: 303 CSS custom properties |
| IA/ML | 5.0 | **5.0** | Agentes, RAG, guardrails implementados |
| **Promedio** | **4.5** | **4.9** | **23/65 hallazgos resueltos (7 Críticos + 8 Altos + 8 Medios)** |

### 12.3 Elevación Verticales a Clase Mundial (2026-02-17)

| Vertical | Fases | Estado | Servicios Nuevos | Archivos |
|----------|-------|--------|-----------------|----------|
| **Empleabilidad** | 10/10 | ✅ Clase Mundial | EmployabilityFeatureGateService, EmployabilityEmailSequenceService, EmployabilityCrossVerticalBridgeService, EmployabilityJourneyProgressionService, EmployabilityHealthScoreService, EmployabilityCopilotAgent, CopilotApiController, TemplateLoaderService | 34+ archivos |
| **Emprendimiento** | 6/6 + v2 (7 gaps) | ✅ Clase Mundial (Paridad) | EmprendimientoFeatureGateService, EmprendimientoExperimentService, EmprendimientoHealthScoreService, EmprendimientoJourneyProgressionService, EmprendimientoEmailSequenceService, EmprendimientoCopilotAgent, EmprendimientoCrossVerticalBridgeService | 25+ archivos |
| **Andalucía +ei** | 12/12 | ✅ Clase Mundial | AndaluciaEiFeatureGateService, AndaluciaEiEmailSequenceService, AndaluciaEiCrossVerticalBridgeService, AndaluciaEiJourneyProgressionService, AndaluciaEiHealthScoreService, AndaluciaEiExperimentService | 43 archivos |
| **JarabaLex** | 14/14 | ✅ Clase Mundial | JarabaLexFeatureGateService, JarabaLexEmailSequenceService, JarabaLexCrossVerticalBridgeService, JarabaLexJourneyProgressionService, JarabaLexHealthScoreService, LegalCopilotAgent, JarabaLexJourneyDefinition, LegalCopilotBridgeService | 50 archivos |
| **AgroConecta** | 14/14 | ✅ Clase Mundial | AgroConectaFeatureGateService, AgroConectaEmailSequenceService, AgroConectaCrossVerticalBridgeService, AgroConectaJourneyProgressionService, AgroConectaHealthScoreService, AgroConectaExperimentService, AgroConectaCopilotBridgeService | 60+ archivos |
| **ComercioConecta** | 18/18 (3 sprints) | ✅ Clase Mundial | ComercioConectaFeatureGateService, ComercioConectaEmailSequenceService, ComercioConectaCrossVerticalBridgeService, ComercioConectaJourneyProgressionService, ComercioConectaHealthScoreService, ComercioConectaExperimentService, ComercioConectaCopilotBridgeService + 18 services locales (OrderRetail, Cart, Checkout, StripePayment, CartRecovery, CustomerProfile, Wishlist, MerchantPayout, ComercioSearch, LocalSeo, FlashOffer, QrRetail, ReviewRetail, Notification, ShippingRetail, ClickCollect, PosIntegration, ComercioAdmin, Moderation, ComercioAnalytics, MerchantAnalytics) | 178 archivos |
| **ServiciosConecta** | 14/14 | ✅ Clase Mundial | ServiciosConectaFeatureGateService, ServiciosConectaEmailSequenceService, ServiciosConectaCrossVerticalBridgeService, ServiciosConectaJourneyProgressionService, ServiciosConectaHealthScoreService, ServiciosConectaExperimentService, ServiciosConectaCopilotAgent, ServiciosConectaCopilotBridgeService | 50+ archivos |

**Empleabilidad (10 Fases):**
1. Clean Page Templates (Zero Region + FAB)
2. Sistema Modal CRUD (data-dialog-type)
3. SCSS Compliance (45+ rgba→color-mix)
4. Feature Gating (3 features × 3 planes)
5. Upgrade Triggers (4 trigger types)
6. Email Sequences (5 secuencias MJML)
7. CRM Integration (7 estados pipeline)
8. Cross-Vertical Bridges (4 bridges)
9. AI Journey Progression Proactiva (7 reglas)
10. Health Scores + KPIs (5 dimensiones + 8 KPIs)

**Emprendimiento v2 — Paridad con Empleabilidad (7 Gaps):**
1. Health Score (5 dimensiones: canvas_completeness 25%, hypothesis_validation 30%, experiment_velocity 15%, copilot_engagement 15%, funding_readiness 15% + 8 KPIs)
2. Journey Progression Proactiva (7 reglas: inactivity_discovery, canvas_incomplete, hypothesis_stalled, all_killed_no_pivot, mvp_validated_no_mentor, funding_eligible, post_scaling_expansion)
3. Email Sequences (5 secuencias MJML: SEQ_ENT_001-005 onboarding, canvas abandonment, upsell starter, MVP celebration, post-funding)
4. Copilot Agent (6 modos: business_strategist, financial_advisor, customer_discovery_coach, pitch_trainer, ecosystem_connector, faq)
5. Cross-Vertical Bridges (3 salientes: formacion, servicios, comercio)
6. CRM Sync Pipeline (7 estados: idea_registered→lead, diagnostic→mql, bmc→sql, mvp→demo, funding→proposal, scaling→closed_won, abandoned→closed_lost)
7. Upgrade Triggers (5 nuevos: canvas_completed 0.38, first_hypothesis_validated 0.42, mentor_matched 0.35, experiment_success 0.40, funding_eligible 0.45)

**Andalucía +ei (12 Fases):**
1. Clean Page Templates (Zero Region + FAB + preprocess hooks)
2. SCSS Compliance (zero rgba, color-mix, var(--ej-*), package.json)
3. Design Token Config Vertical (paleta #FF8C42/#00A9A5/#233D63)
4. Feature Gating (6 features × 3 planes = 18 FreemiumVerticalLimit)
5. Email Lifecycle (6 secuencias SEQ_AEI_001-006 + 6 MJML templates)
6. Cross-Vertical Bridges (4 bridges salientes: emprendimiento, empleabilidad, servicios, formación)
7. Proactive AI Journey Progression (8 reglas: inactividad, milestones, inserción, re-engagement)
8. Health Scores + KPIs (5 dimensiones + 8 KPIs verticales)
9. i18n Compliance (JourneyDefinition const→static methods + TranslatableMarkup)
10. Upgrade Triggers + CRM (milestones 25h/50h/75h/100h, pipeline atencion→insercion→baja)
11. A/B Testing Framework (8 eventos conversión, 4 scopes)
12. Embudo Público → Registro → Ventas (insert hook, conversion tracking, dashboard enriquecido)

**JarabaLex (14 Fases):**
1. Landing Page (SCSS modifier + GrapesJS thumbnail)
2. Zero-Region Consolidation (body classes via hook_preprocess_html)
3. Modal CRUD (citation modal, alert confirm, dialog.ajax)
4. SCSS Compliance + Design Tokens (vertical_jarabalex navy/gold)
5. Feature Gating (JarabaLexFeatureGateService + 36 FreemiumVerticalLimit: 18 legal research + 18 despacho management)
6. Upgrade Triggers (11 legal trigger types: 5 research + 6 despacho + fire() + copilot upsell)
7. Email Sequences (5 MJML templates SEQ_LEX_001-005)
8. CRM Integration (ensure_crm_contact + sync_crm_event pipeline)
9. Cross-Vertical Bridges (4: emprendimiento, empleabilidad, fiscal, formacion)
10. Journey Definition (3 avatars: profesional_juridico, gestor_fiscal, investigador_legal)
11. AI Journey Progression Proactiva (7 reglas: inactividad, bookmarks, alertas, citas, upgrade, fiscal, UE)
12. Health Scores + KPIs (5 dimensiones + 8 KPIs verticales)
13. LegalCopilotAgent (6 modos, AgentInterface completo, LEGAL-RAG-001)
14. Avatar Navigation (legal_professional) + 3 Funnel Definitions

**AgroConecta (14 Fases + Page Builder Premium):**
1. FeatureGateService (CUMULATIVE/MONTHLY/BINARY) + 12 FreemiumVerticalLimit configs (4 features x 3 planes)
2. UpgradeTrigger types (8 nuevos: agro_products, orders, copilot, photos, traceability, partner_hub, analytics, demand_forecaster)
3. AgroConectaCopilotBridgeService (context productor + soft suggestions + market insights)
4. Body classes consolidation (hook_preprocess_html + page--clean-layout)
5. Zero-region page--agroconecta.html.twig + Copilot FAB + hook_preprocess_page clean_content
6. SCSS compliance (95 rgba→color-mix en 16 ficheros) + design token vertical nature_green
7. Email sequences MJML (6 templates SEQ_AGRO_001-006: onboarding, activacion, re-engagement, upsell starter, upsell pro, retention)
8. CrossVerticalBridgeService (4 bridges: emprendimiento, fiscal, formacion, empleabilidad)
9. JourneyProgressionService (10 reglas proactivas: 8 productor + 2 consumidor)
10. HealthScoreService (5 dimensiones: catalog 25%, sales 30%, engagement 20%, copilot 10%, presence 15% + 8 KPIs)
11. ExperimentService (4 A/B tests: checkout_cta_color, product_card_layout, pricing_page_variant, landing_hero_copy)
12. Avatar navigation (buyer + producer deep links) + 4 Funnel Definitions (acquisition, activation, monetization, consumer_purchase)
13. QA integral (52 ficheros, 0 errores, 18 agentes paralelos)
14. Page Builder Premium: 11 templates (jaraba_icon, data-effect fade-up, staggered delays, FAQ JSON-LD, LocalBusiness microdata, AggregateRating, lightbox, countdown timer, pricing toggle, star ratings) + 11 YML configs (is_premium:true, animation:fade-up, plans_required, fields_schema)

**ComercioConecta (18 Fases en 3 Sprints):**
Sprint 1 — Infraestructura de Elevacion (14 fases patron reutilizable):
1. FeatureGateService (CUMULATIVE/MONTHLY/BINARY) + 9 FreemiumVerticalLimit (products, orders, copilot x 3 planes)
2. UpgradeTrigger types (7 nuevos: comercio_products, orders, copilot, flash_offers, qr_codes, reviews, shipping)
3. ComercioConectaCopilotBridgeService (context comerciante + merchant insights)
4. Body classes (hook_preprocess_html + page--comercio route mapping)
5. Zero-region page--comercio-marketplace.html.twig + Copilot FAB + hook_preprocess_page
6. SCSS compliance (color-mix, var(--ej-*)) + design token vertical comercio_conecta
7. Email sequences MJML (6 templates SEQ_COM_001-006: onboarding, activation, first-sale, upsell, retention, cart-abandoned)
8. CrossVerticalBridgeService (4 bridges: agroconecta, servicios, emprendimiento, empleabilidad)
9. JourneyProgressionService (8 reglas proactivas: 6 comerciante + 2 consumidor)
10. HealthScoreService (5 dim: catalog 25%, sales 30%, engagement 20%, copilot 10%, presence 15% + 8 KPIs)
11. ExperimentService (4 A/B tests)
12. Avatar navigation (merchant + consumer) + 4 Funnel Definitions
13. Page Builder Premium: 11 templates (jaraba_icon, schema.org, FAQ JSON-LD, LocalBusiness)
14. QA integral

Sprint 2 — Comercio Core Completo:
15. F6 Orders+Checkout+Payments: 9 entidades (OrderRetail, OrderItemRetail, SuborderRetail, Cart, CartItem, ReturnRequest, CouponRetail, CouponRedemption, AbandonedCart) + 5 services + Stripe Connect split + IVA 21% + checkout.js/scss
16. F7 Portales: 3 entidades (CustomerProfile, Wishlist, WishlistItem) + 3 services + customer-portal.js + merchant-portal.js
17. F8 Search+SEO: 5 entidades (SearchIndex, SearchSynonym, SearchLog, LocalBusinessProfile, NapEntry) + 2 services (Haversine, Schema.org) + search.js/scss
18. F9 Engagement: 11 entidades (FlashOffer, FlashOfferClaim, QrCodeRetail, QrScanEvent, QrLeadCapture, ReviewRetail, QuestionAnswer, NotificationTemplate/Log/Preference, PushSubscription) + 4 services

Sprint 3 — Funcionalidades Avanzadas:
19. F10 Shipping+POS+Admin+Analytics: 10 entidades (ShipmentRetail, ShippingMethodRetail, ShippingZone, CarrierConfig, PosConnection, PosSync, PosConflict, ModerationQueue, IncidentTicket, PayoutRecord) + 7 services

**ServiciosConecta (14 Fases):**
1. Bug fix releaseSlot() + ProviderService refactor N+1
2. ServiciosConectaSettingsForm config
3. FeatureGateService (CUMULATIVE/MONTHLY/BINARY) + 4 FreemiumVerticalLimit
4. 8 UpgradeTrigger types + CopilotBridgeService
5. Body classes + zero-region page templates (marketplace + dashboard)
6. SCSS compliance (5 Tailwind→var(--ej-*), rgba→color-mix, design token)
7. ReviewService + ReviewServicios entity
8. EmailSequenceService (6 MJML SEQ_SVC_001-006)
9. CrossVerticalBridgeService (4 bridges)
10. JourneyProgressionService (10 reglas: 8 profesional + 2 cliente)
11. HealthScoreService (5 dim + 8 KPIs) + CopilotAgent (6 modos)
12. ExperimentService (3 A/B) + Avatar nav (profesional + cliente_servicios)
13. 2 FunnelDefinitions + 15 PB templates (11 fix + 4 premium)
14. QA integral

### 12.4 Dependabot Security Posture (2026-02-14)

| Métrica | Antes | Después |
|---------|-------|---------|
| **Critical** | 2 | **0** |
| **High** | 14 | **0** |
| **Medium** | 15 | **0** |
| **Low** | 11 | **0** (1 dismissed — Drupal upstream) |
| **Total abiertas** | 42 | **0** |

**Técnicas aplicadas:**
- `npm audit fix` para dependencias directas en lockfiles contrib
- `npm audit fix --force` para major bumps en devDependencies (ckeditor5-dev-utils 30→49)
- `overrides` en package.json para transitivas bloqueadas por upstream (mocha→diff ^7→^8.0.3)
- Dismiss documentado para `web/core/yarn.lock` (webpack — mantenido por Drupal upstream)

### 12.5 Especificación Stack Cumplimiento Fiscal (2026-02-15)

5 documentos técnicos especificando el stack completo de cumplimiento fiscal para el ecosistema:

| Doc | Código | Módulo | Contenido |
|-----|--------|--------|-----------|
| **178** | Auditoría VeriFactu & World-Class Gap Analysis | — | Análisis estratégico: VeriFactu NO implementado, score 20.75/100, roadmap 3 fases (1,056-1,427h), componentes reutilizables |
| **179** | Platform VeriFactu Implementation | `jaraba_verifactu` | 4 entidades, 7 servicios, 21 endpoints, 5 ECA flows, 23 tests, 7 permisos RBAC, 4 sprints (230-316h) |
| **180** | Platform Facturae 3.2.2 + FACe B2G | `jaraba_facturae` | 3 entidades, 6 servicios, 21 endpoints, 5 ECA flows, 26 tests, firma XAdES-EPES (230-304h) |
| **181** | Platform E-Factura B2B (Crea y Crece) | `jaraba_einvoice_b2b` | 4 entidades, 6 servicios, 24 endpoints, 5 ECA flows, 23 tests, UBL 2.1 + Facturae dual (260-336h) |
| **182** | Gap Analysis Madurez Documental | — | 4 niveles (N0 100%, N1 97%, N2 85%, N3 0%), 18 docs pendientes, 1,040-1,335h gap total |

**Inversión total stack fiscal:** 720-956h / 32,400-43,020 EUR (implementación 3 módulos).

### 12.6 Vertical JarabaLex — Legal Intelligence Hub (2026-02-15, actualizado 2026-02-28)

3 documentos técnicos + 1 plan de implementación especificando el Legal Intelligence Hub, elevado a vertical independiente JarabaLex (antes sub-feature ServiciosConecta):

| Doc | Código | Contenido |
|-----|--------|-----------|
| **178** | ServiciosConecta Legal Intelligence Hub | Especificación base: 5 Content Entities, 7 Services, 8 Spiders (4 nacionales + 4 UE), Pipeline NLP 9 etapas, Qdrant dual collection, merge & rank con EU primacy |
| **178A** | Legal Intelligence Hub EU Sources | Fuentes europeas: EUR-Lex (CELLAR SPARQL), CURIA/TJUE, HUDOC/TEDH, EDPB, embeddings multilingües 1024D, cross-lingual search |
| **178B** | Legal Intelligence Hub Implementation | Guía implementación: arquitectura módulo, compilación SCSS, Docker NLP (spaCy + FastAPI), 12 iconos SVG duotone, paleta legal 10 tokens |
| **Plan** | Plan Implementación Legal Intelligence Hub v1 | 10 fases (0-9), 530-685h, 23,850-30,825 EUR, 14 directrices con ejemplos código, estrategia convivencia jaraba_legal_knowledge |

**Unificacion Landing JarabaLex + Despachos (2026-02-28):** La landing `/despachos` (features de practica legal) se unifico en `/jarabalex` via 301 redirect. 5 modulos legales habilitados (calendar, vault, billing, lexnet, templates). 18 FreemiumVerticalLimit configs creados (36 total). LexNET como killer differentiator. Auditoria: `docs/analisis/2026-02-28_Auditoria_Landing_JarabaLex_Despachos_Clase_Mundial_v1.md`. Plan: `docs/implementacion/2026-02-28_Plan_Unificacion_Landing_JarabaLex_Despachos_Clase_Mundial_v1.md`.

**Relación con jaraba_legal_knowledge:** El nuevo módulo `jaraba_legal_intelligence` extiende las capacidades de `jaraba_legal_knowledge` (5 entidades, 10 servicios, colección Qdrant `jaraba_knowledge`). Estrategia de convivencia: jaraba_legal_knowledge se mantiene como módulo ligero para RAG normativo básico (BOE, fiscal) + grafo de relaciones normativas (`LegalNormRelation` con 10 tipos de relacion); jaraba_legal_intelligence añade resoluciones judiciales, fuentes europeas, NLP avanzado, dashboard profesional y audit trail EU AI Act (`LegalCoherenceLog`).

> **SISTEMA DE COHERENCIA JURIDICA (LCIS) — 9 CAPAS** (2026-02-28)
> Legal Coherence Intelligence System implementado en `jaraba_legal_intelligence/src/LegalCoherence/`. 9 servicios PHP puros (sin dependencias Drupal en capas 1-4), patron fail-open en todas las capas. 149+ unit tests, 277+ assertions. 2 entities de soporte: `legal_norm_relation` (grafo normativo, 10 tipos de relacion) en jaraba_legal_knowledge, `legal_coherence_log` (audit trail EU AI Act Art. 12) en jaraba_legal_intelligence. 2 QueueWorkers: `NormativeUpdateWorker` (actualiza vigencia via relaciones), `NormVectorizationWorker` (re-vectoriza normas en Qdrant).
>
> | Capa | Servicio | LOC | Funcion |
> |------|----------|-----|---------|
> | **L1** Knowledge Base | `LegalCoherenceKnowledgeBase` | 445 | 9 niveles jerarquicos, 6 regimenes forales, 30+ regex, competencias Art.149.1 |
> | **L2** Intent Classifier | `LegalIntentClassifierService` | 345 | Gate 5 intents (DIRECT/IMPLICIT/REFERENCE/COMPLIANCE/NON_LEGAL), shortcircuits |
> | **L3** Normative Graph | `NormativeGraphEnricher` | 329 | Authority-Aware RAG: 0.55 semantic + 0.30 authority + 0.15 recency, derogation filter |
> | **L4** Prompt Rules | `LegalCoherencePromptRule` | 176 | 8+1 reglas inyectadas (R1-R8 + R9 territorial), version short, foral auto-detection |
> | **L5** Constitutional | `LegalConstitutionalGuardrailService` | — | Guardrails constitucionales pre-LLM |
> | **L6** Validator | `LegalCoherenceValidatorService` | 684 | 7 checks post-LLM, scoring PENALTIES, regenerate/block/warn, anti-sycophancy V8 |
> | **L7** Verifier | `LegalCoherenceVerifierService` | — | Verificacion final coherencia |
> | **L8** Disclaimer | `LegalDisclaimerEnforcementService` | 166 | Disclaimer no-eliminable, fallback, score threshold 70% |
> | **MT** Multi-Turn | `LegalConversationContext` | 256 | Cross-turn assertions, contradiction detection, MAX_TURNS=20, MAX_ASSERTIONS=50 |
>
> Reglas: LEGAL-COHERENCE-KB-001, LEGAL-COHERENCE-INTENT-001, LEGAL-COHERENCE-PROMPT-001, LEGAL-COHERENCE-FAILOPEN-001, LEGAL-COHERENCE-REGEN-001, LEGAL-COHERENCE-MULTITURN-001 en Directrices v103.0.0. EU AI Act: HIGH RISK (Annex III, 8 — Administration of Justice). Aprendizaje #153.

### 12.7 Especificación Niveles de Madurez N1/N2/N3 (2026-02-16)

21 documentos técnicos especificando capacidades organizados por nivel de madurez plataforma, más 3 auditorías de readiness y 1 plan de implementación fiscal:

**Nivel 1 — Foundation/Compliance (docs 183-185):**

| Doc | Título | Área |
|-----|--------|------|
| **183** | GDPR DPA Templates — Plantillas legales multi-tenant RGPD/LOPD-GDD | Legal/Compliance |
| **184** | Legal Terms SaaS — TdS, SLA, AUP, Licencia de Datos, Offboarding | Legal |
| **185** | Disaster Recovery Plan — Continuidad de Negocio y Recuperación ante Desastres | DR/BCP |
| **201** | Auditoría N1 Claude Code Readiness | **NOT READY — 12 gaps críticos** |

**Nivel 2 — Growth Ready (docs 186-193):**

| Doc | Título | Área |
|-----|--------|------|
| **186** | AI Autonomous Agents — Ejecución autónoma con guardrails de seguridad | AI |
| **187** | Native Mobile App — iOS/Android con Capacitor | Mobile |
| **188** | Multi-Agent Orchestration — Agentes especializados con memoria compartida | AI |
| **189** | Predictive Analytics — Churn, Lead Scoring, Forecasting, Anomaly Detection | Analytics |
| **190** | Multi-Region Operations — Fiscalidad multi-país, multi-currency, data residency | Infrastructure |
| **191** | STO/PIIL Integration — Servicio Telemático de Orientación + Programas PIIL | Integrations |
| **192** | European Funding Module — Fondos europeos, subvenciones, convocatorias | Funding |
| **193** | Connector SDK — SDK para conectores, certificación y marketplace | Platform |
| **202** | Auditoría N2 Claude Code Readiness | **Score Global: 15.6%** |

**Nivel 3 — Enterprise Class (docs 194-200):**

| Doc | Título | Área |
|-----|--------|------|
| **194** | SOC 2 Type II Readiness — Preparación con evidencia automatizada | Security |
| **195** | ISO 27001 SGSI — Sistema de Gestión de Seguridad de la Información | Security |
| **196** | ENS Compliance — Esquema Nacional de Seguridad (RD 311/2022) | Security |
| **197** | High Availability Multi-Region — 99.99% Galera Cluster + zero-downtime | Infrastructure |
| **198** | SLA Management — Gestión formal SLAs, status page y postmortems | Operations |
| **199** | SSO SAML/SCIM — Single Sign-On enterprise SAML 2.0, SCIM 2.0, MFA | Security/Identity |
| **200** | Data Governance — Clasificación, retención, lineage y KMS | Data |
| **203** | Auditoría N3 Claude Code Readiness | **Score Global: 10.4%** |

**Plan de implementación fiscal:**

| Doc | Título |
|-----|--------|
| **Plan** | Plan Implementación Stack Cumplimiento Fiscal v1 — `jaraba_verifactu` + `jaraba_facturae` + `jaraba_einvoice_b2b` (107KB, 720-956h) |

**Separación directorios backup:** `~/backups/daily/` (automáticos diarios) y `~/backups/pre_deploy/` (pre-deploy) separados para configuración GoodSync. Paso de migración one-time añadido a `daily-backup.yml`.

### 12.8 CI Testing Infrastructure (2026-02-16)

**Estado:** 2,039 tests pasando (1,895 Unit + 144 Kernel), 0 errores, 0 fallos.

**Workflow:** "Jaraba SaaS - Deploy to IONOS" ejecuta PHPUnit Unit y Kernel tests en cada push a main. SQLite para Kernel tests (phpunit.xml).

**Patrones Kernel Test establecidos (7 commits, 61→0 errores):**

| Patron | Regla | Descripcion |
|--------|-------|-------------|
| **Module dependencies** | KERNEL-DEP-001 | KernelTestBase `$modules` DEBE incluir todos los modulos que proveen field types: `options` (list_string), `datetime` (datetime), `flexible_permissions` + `group` (entity_reference→group), `file` (entity_reference→file) |
| **Synthetic services** | KERNEL-SYNTH-001 | Dependencias de modulos no cargados → `register(ContainerBuilder)` con `setSynthetic(TRUE)` + `setUp()` con `createMock()`. Usado para: `ecosistema_jaraba_core.certificate_manager`, `jaraba_foc.stripe_connect` |
| **Entity reference targets** | KERNEL-EREF-001 | Entity references a entidades inexistentes se descartan silenciosamente en save(). Tests DEBEN crear entity target primero |
| **Timestamp tolerance** | KERNEL-TIME-001 | `time() - 1` / `time() + 1` para evitar flaky tests por race conditions en CI |
| **SQLite decimals** | KERNEL-DECIMAL-001 | SQLite retorna `'1210'` no `'1210.00'`. Usar `assertEqualsWithDelta($expected, (float)$value, 0.001)` |
| **UID 1 super admin** | KERNEL-UID1-001 | Drupal uid=1 bypasses ALL access checks. Tests de permisos DEBEN crear uid=1 placeholder en setUp() |

**Bug de produccion descubierto y corregido:** `jaraba_verifactu.module` referenciaba campo `modo_verifactu` (no existe) en vez de `is_active` en funciones helper de tenant. Detectado por Kernel tests que instalaban el entity schema real.

**Distribucion test files:** 233 Unit + 38 Kernel + 28 Functional = 299 archivos test en 69 modulos custom.

### 12.9 Referencias

| Documento | Ubicación |
|-----------|-----------|
| Auditoría Integral Estado SaaS v1 | `docs/tecnicos/auditorias/20260213-Auditoria_Integral_Estado_SaaS_v1_Claude.md` |
| Plan de Remediación v1 | `docs/implementacion/20260213-Plan_Remediacion_Auditoria_Integral_v1.md` |
| Aprendizajes Auditoría Integral | `docs/tecnicos/aprendizajes/2026-02-13_auditoria_integral_estado_saas.md` |
| Aprendizajes Security CI + Dependabot | `docs/tecnicos/aprendizajes/2026-02-14_security_ci_dependabot_remediation.md` |
| Directrices v21.0.0 (11 reglas AUDIT-*) | `docs/00_DIRECTRICES_PROYECTO.md` |

### 12.10 Elevacion Sistematica Landing Pages + Homepage (2026-02-28)

Patron de elevacion en 3 niveles aplicado sistematicamente a landing pages verticales y homepage:

| Pagina | Nivel 1 (Contenido) | Nivel 2 (SEO) | Nivel 3 (Config) |
|--------|---------------------|---------------|-------------------|
| **Empleabilidad** | Ya elevada (10/10 fases) | Schema.org 15 featureList + meta/OG/Twitter | 12 FVL nuevos, 2 FVL bugs fix, PlanFeatures fix, 3 SaasPlan (29/79/199€) |
| **Talento** | 4→12 features, 4→10 FAQs, testimonios recruiter | Schema.org 12 featureList + meta/OG/Twitter | Hereda de Empleabilidad (persona recruiter) |
| **Homepage** | 6 Twig partials actualizados (hero/features/stats/cross-pollination) | Schema.org highPrice 99→199, featureList 7→12, meta/OG/Twitter via isFrontPage() | N/A (homepage no es vertical) |
| **JarabaLex** | 8 features, 10 FAQs, LexNET killer differentiator | Schema.org + meta/OG/Twitter | 36 FVL, 3 SaasPlan |
| **ServiciosConecta** | 8 features reales, 10 FAQs | Schema.org + meta/OG/Twitter | 9 FVL, 3 SaasPlan |
| **ComercioConecta** | 8 features, 10 FAQs | Schema.org + meta/OG/Twitter | FVL existentes |
| **AgroConecta** | Features/FAQs elevados | Schema.org + meta/OG/Twitter | 12 FVL |
| **Emprendimiento** | Features/FAQs elevados | Schema.org + meta/OG/Twitter | FVL existentes |

**Arquitectura Homepage (template-driven):**
```
page--front.html.twig
├── {% include '_hero.html.twig' %}          ← fallback data en template
├── {% include '_trust-bar.html.twig' %}
├── {% include '_vertical-selector.html.twig' %}
├── {% include '_features.html.twig' %}      ← {% set default_features = [...] %}
├── {% include '_stats.html.twig' %}         ← METRICS-HONESTY: solo verificables
├── {% include '_product-demo.html.twig' %}
├── {% include '_lead-magnet.html.twig' %}
├── {% include '_cross-pollination.html.twig' %}
├── {% include '_testimonials.html.twig' %}
├── {% include '_seo-schema.html.twig' %}    ← Schema.org WebApplication
└── {% include '_footer.html.twig' %}
```

**Descubrimiento:** Talento NO es vertical canonico. Es landing de persona recruiter dentro de Empleabilidad. Config hereda de `empleabilidad_*`.

Reglas: LANDING-ELEVATION-001, METRICS-HONESTY-001 en Directrices v105.0.0. Aprendizaje #155.

---

## 📎 Referencias

| Documento | Ubicación |
|-----------|-----------|
| Directrices del Proyecto | `docs/00_DIRECTRICES_PROYECTO.md` |
| Índice General | `docs/00_INDICE_GENERAL.md` |
| Arquitectura Alto Nivel | `docs/arquitectura/2026-01-09_1902_arquitectura-alto-nivel.md` |
| Planes SaaS | `docs/logica/2026-01-09_1908_definicion-planes-saas.md` |
| Roadmap Nivel 5 | `docs/planificacion/2026-01-11_1503_roadmap-nivel5-arquitectura.md` |
| Guía KB RAG | `docs/tecnicos/20260111-Guia_Tecnica_KB_RAG_Qdrant.md` |
| Documento FOC v2 | `docs/tecnicos/20260113d-FOC_Documento_Tecnico_Definitivo_v2_Claude.md` |
| README FOC (API) | `web/modules/custom/jaraba_foc/README.md` |
| **Plan Estratégico v4.0** | `docs/planificacion/20260114-Plan_Estrategico_SaaS_Q1Q4_2026.md` |
| **Auditoría Profunda SaaS v1** | `docs/tecnicos/auditorias/20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md` |
| **Plan Elevación Page Builder v1.2** ⭐ | `docs/arquitectura/2026-02-08_plan_elevacion_page_builder_clase_mundial.md` |
| **Aprendizajes Elevación Page Builder** | `docs/tecnicos/aprendizajes/2026-02-08_elevacion_page_builder_clase_mundial.md` |
| **Aprendizajes Auditoría v2.1** ⭐ | `docs/tecnicos/aprendizajes/2026-02-09_auditoria_v2_falsos_positivos_page_builder.md` |
| **Plan v2.1 Falsos Positivos** ⭐ | `docs/planificacion/20260209-Plan_Elevacion_Page_Site_Builder_v2.md` |
| **Aprendizajes Auditoría** | `docs/tecnicos/aprendizajes/2026-02-06_auditoria_profunda_saas_multidimensional.md` |
| **Aprendizajes PHPUnit 11** ⭐ | `docs/tecnicos/aprendizajes/2026-02-11_phpunit11_kernel_test_remediation.md` |
| **Aprendizajes Sprint C4 IA Page Builder** ⭐ | `docs/tecnicos/aprendizajes/2026-02-11_sprint_c4_ia_asistente_page_builder.md` |
| **Plan ServiciosConecta Fase 1** ⭐ | `docs/implementacion/20260209-Plan_Implementacion_ServiciosConecta_v1.md` |
| **Aprendizajes ServiciosConecta** | `docs/tecnicos/aprendizajes/2026-02-09_servicios_conecta_fase1_implementation.md` |
| **Plan Maestro Unificado v3.0** ⭐ | `docs/planificacion/20260123-Plan_Maestro_Unificado_SaaS_v3_Claude.md` |
| **Bloque G: AI Skills System** ⭐ | `docs/implementacion/20260123g-Bloque_G_AI_Skills_Implementacion_Claude.md` |
| **Auditoría Gap Q1 2027** | `docs/tecnicos/20260115g-Auditoria_Gap_Arquitectura_v1_Claude.md` |
| **Mapeo Arquitectónico Integral** | `docs/arquitectura/2026-01-19_1858_mapeo-arquitectonico-integral-saas.md` |
| **Vertical Emprendimiento Gap Analysis** | `docs/tecnicos/20260115h-Gap_Analysis_Documentacion_Tecnica_Emprendimiento_v1_Claude.md` |
| **Copiloto v2 Especificaciones** | `docs/tecnicos/20260121a-Especificaciones_Tecnicas_Copiloto_v2_Claude.md` |
| **Programa Andalucía +ei** | `docs/tecnicos/20260115c-Programa%20Maestro%20Andaluc%C3%ADa%20+ei%20V2.0_Gemini.md` |
| **Aprendizajes Avatar + Empleabilidad** ⭐ | `docs/tecnicos/aprendizajes/2026-02-12_avatar_empleabilidad_activation.md` |
| **Aprendizajes Self-Discovery Entities + Services** ⭐ | `docs/tecnicos/aprendizajes/2026-02-12_self_discovery_content_entities_services.md` |
| **Plan Cierre Gaps Specs 20260122-25** ⭐ | `docs/implementacion/2026-02-12_Plan_Cierre_Gaps_Specs_20260122_20260125.md` |
| **Aprendizajes Heatmaps + Tracking Fases 1-5** ⭐ | `docs/tecnicos/aprendizajes/2026-02-12_heatmaps_tracking_phases_1_5.md` |
| **Auditoría Integral Estado SaaS v1** ⭐ | `docs/tecnicos/auditorias/20260213-Auditoria_Integral_Estado_SaaS_v1_Claude.md` |
| **Plan Remediación Auditoría Integral v1** ⭐ | `docs/implementacion/20260213-Plan_Remediacion_Auditoria_Integral_v1.md` |
| **Aprendizajes Auditoría Integral** ⭐ | `docs/tecnicos/aprendizajes/2026-02-13_auditoria_integral_estado_saas.md` |
| **Plan Sprint Diferido (22 TODOs, 5 fases)** ⭐ | `docs/implementacion/20260213-Plan_Implementacion_Sprint_Diferido_v1.md` |
| **Aprendizajes Sprint Diferido** ⭐ | `docs/tecnicos/aprendizajes/2026-02-13_sprint_diferido_22_todos_5_fases.md` |
| **Plan Elevación Empleabilidad v1** ⭐ | `docs/implementacion/2026-02-15_Plan_Elevacion_Clase_Mundial_Vertical_Empleabilidad_v1.md` |
| **Aprendizajes Elevación Empleabilidad 10 Fases** ⭐ | `docs/tecnicos/aprendizajes/2026-02-15_empleabilidad_elevacion_10_fases.md` |
| **Aprendizajes Elevación Emprendimiento 6 Fases** ⭐ | `docs/tecnicos/aprendizajes/2026-02-15_emprendimiento_elevacion_6_fases.md` |
| **Plan Emprendimiento v2 Paridad 7 Gaps** ⭐ | `docs/implementacion/20260215-Plan_Elevacion_Emprendimiento_v2_Paridad_Empleabilidad_7_Gaps.md` |
| **Aprendizajes Emprendimiento Paridad 7 Gaps** ⭐ | `docs/tecnicos/aprendizajes/2026-02-15_emprendimiento_paridad_empleabilidad_7_gaps.md` |
| **Plan Elevación Andalucía +ei 12 Fases** ⭐ | `docs/implementacion/20260215c-Plan_Elevacion_Andalucia_EI_Clase_Mundial_v1_Claude.md` |
| **Aprendizajes Andalucía +ei Elevación 12 Fases** ⭐ | `docs/tecnicos/aprendizajes/2026-02-15_andalucia_ei_elevacion_12_fases.md` |
| **Auditoría VeriFactu & World-Class Gap Analysis** ⭐ | `docs/tecnicos/20260215b-178_Auditoria_VeriFactu_WorldClass_v1_Claude.md` |
| **Especificación VeriFactu Implementation** ⭐ | `docs/tecnicos/20260215b-179_Platform_VeriFactu_Implementation_v1_Claude.md` |
| **Especificación Facturae 3.2.2 + FACe B2G** ⭐ | `docs/tecnicos/20260215c-180_Platform_Facturae_FACe_B2G_v1_Claude.md` |
| **Especificación E-Factura B2B (Crea y Crece)** ⭐ | `docs/tecnicos/20260215d-181_Platform_EFactura_B2B_v1_Claude.md` |
| **Gap Analysis Madurez Documental Niveles 0-3** ⭐ | `docs/tecnicos/20260215e-182_Gap_Analysis_Madurez_Documental_v1_Claude.md` |
| **Aprendizajes Stack Fiscal VeriFactu** ⭐ | `docs/tecnicos/aprendizajes/2026-02-15_verifactu_stack_fiscal_compliance.md` |
| **Especificación Legal Intelligence Hub** ⭐ | `docs/tecnicos/20260215a-178_ServiciosConecta_Legal_Intelligence_Hub_v1_Claude.md` |
| **Especificación Legal Intelligence Hub EU Sources** ⭐ | `docs/tecnicos/20260215a-178A_Legal_Intelligence_Hub_EU_Sources_v1_Claude.md` |
| **Especificación Legal Intelligence Hub Implementation** ⭐ | `docs/tecnicos/20260215a-178B_Legal_Intelligence_Hub_Implementation_v1_Claude.md` |
| **Plan Implementación Legal Intelligence Hub v1** ⭐ | `docs/implementacion/20260215-Plan_Implementacion_Legal_Intelligence_Hub_v1.md` |
| **Plan Implementación Stack Fiscal v1** ⭐ | `docs/implementacion/20260216-Plan_Implementacion_Stack_Cumplimiento_Fiscal_v1.md` |
| **GDPR DPA Templates (Doc 183)** ⭐ | `docs/tecnicos/20260216a-183_Platform_GDPR_DPA_Templates_v1_Claude.md` |
| **Legal Terms SaaS (Doc 184)** ⭐ | `docs/tecnicos/20260216a-184_Platform_Legal_Terms_v1_Claude.md` |
| **Disaster Recovery Plan (Doc 185)** ⭐ | `docs/tecnicos/20260216a-185_Platform_Disaster_Recovery_v1_Claude.md` |
| **AI Autonomous Agents (Doc 186)** ⭐ | `docs/tecnicos/20260216b-186_Platform_AI_Autonomous_Agents_v1_Claude.md` |
| **Native Mobile App (Doc 187)** ⭐ | `docs/tecnicos/20260216b-187_Platform_Native_Mobile_v1_Claude.md` |
| **Multi-Agent Orchestration (Doc 188)** ⭐ | `docs/tecnicos/20260216b-188_Platform_Multi_Agent_Orchestration_v1_Claude.md` |
| **Predictive Analytics (Doc 189)** ⭐ | `docs/tecnicos/20260216b-189_Platform_Predictive_Analytics_v1_Claude.md` |
| **Multi-Region Operations (Doc 190)** ⭐ | `docs/tecnicos/20260216b-190_Platform_Multi_Region_v1_Claude.md` |
| **STO/PIIL Integration (Doc 191)** ⭐ | `docs/tecnicos/20260216b-191_Platform_STO_PIIL_Integration_v1_Claude.md` |
| **European Funding Module (Doc 192)** ⭐ | `docs/tecnicos/20260216b-192_Platform_European_Funding_v1_Claude.md` |
| **Connector SDK (Doc 193)** ⭐ | `docs/tecnicos/20260216b-193_Platform_Connector_SDK_v1_Claude.md` |
| **SOC 2 Type II Readiness (Doc 194)** ⭐ | `docs/tecnicos/20260216c-194_Platform_SOC2_Type_II_Readiness_v1_Claude.md` |
| **ISO 27001 SGSI (Doc 195)** ⭐ | `docs/tecnicos/20260216c-195_Platform_ISO_27001_SGSI_v1_Claude.md` |
| **ENS Compliance (Doc 196)** ⭐ | `docs/tecnicos/20260216c-196_Platform_ENS_Compliance_v1_Claude.md` |
| **HA Multi-Region (Doc 197)** ⭐ | `docs/tecnicos/20260216c-197_Platform_HA_Multi_Region_v1_Claude.md` |
| **SLA Management (Doc 198)** ⭐ | `docs/tecnicos/20260216c-198_Platform_SLA_Management_v1_Claude.md` |
| **SSO SAML/SCIM (Doc 199)** ⭐ | `docs/tecnicos/20260216c-199_Platform_SSO_SAML_SCIM_v1_Claude.md` |
| **Data Governance (Doc 200)** ⭐ | `docs/tecnicos/20260216c-200_Platform_Data_Governance_v1_Claude.md` |
| **Auditoría N1 Readiness (Doc 201)** ⭐ | `docs/tecnicos/20260216a-201_Auditoria_N1_ClaudeCode_Readiness_v1_Claude.md` |
| **Auditoría N2 Readiness (Doc 202)** ⭐ | `docs/tecnicos/20260216b-202_Auditoria_N2_ClaudeCode_Readiness_v1_Claude.md` |
| **Auditoría N3 Readiness (Doc 203)** ⭐ | `docs/tecnicos/20260216c-203_Auditoria_N3_ClaudeCode_Readiness_v1_Claude.md` |
| **Aprendizajes Tenant Export + Backup** ⭐ | `docs/tecnicos/aprendizajes/2026-02-16_tenant_export_backup_automatizado.md` |
| **Plan Elevacion JarabaLex Vertical Independiente** ⭐ | `docs/implementacion/20260216-Elevacion_JarabaLex_Vertical_Independiente_v1.md` |
| **Aprendizajes Elevacion JarabaLex** ⭐ | `docs/tecnicos/aprendizajes/2026-02-16_jarabalex_elevacion_vertical_independiente.md` |
| **Aprendizajes FASE 11 + Compliance Legal N1** ⭐ | `docs/tecnicos/aprendizajes/2026-02-16_fase11_fiscal_integration_compliance_legal_n1.md` |
| **Aprendizajes Gap-filling + ComplianceAggregator** ⭐ | `docs/tecnicos/aprendizajes/2026-02-16_gap_filling_compliance_aggregator.md` |

---

## 📊 Plan Maestro v3.0 (Auditoría 2026-01-23)

| Bloque | Descripción | Horas |
|--------|-------------|-------|
| **A** | Gaps Auditoría: SEPE, Frontend, AgroConecta, Expansión | 1,690h |
| **B** | Copiloto v3: Osterwalder/Blank | 96h |
| **C** | Journey Engine: 19 avatares, 7 estados | 530h |
| **D** | Admin Center: 8 módulos premium | 635h |
| **E** | Training System: 6 peldaños certificación | 124h |
| **F** | AI Content Hub: Blog, Newsletter, AI Writing | 340-410h |
| **G** | AI Skills System: Especialización agentes IA | 200-250h |
| **TOTAL** | **7 bloques, 24 meses** | **~4,500h** |

---

| 2026-02-18 | **48.0.0** | **Auditoria Integridad Planes 20260217 — Gaps medium resueltos:** 10 PHPUnit test files para 5 modulos N2 Growth Ready (~100 test methods total). jaraba_funding: OpportunityTrackerServiceTest (11 tests) + BudgetAnalyzerServiceTest (9 tests). jaraba_multiregion: TaxCalculatorServiceTest (17 tests) + ViesValidatorServiceTest (12 tests). jaraba_institutional: ProgramManagerServiceTest + StoFichaGeneratorServiceTest. jaraba_agents: AgentOrchestratorServiceTest (22 tests) + GuardrailsEnforcerServiceTest (24 tests). jaraba_predictive: ChurnPredictorServiceTest (10 tests) + LeadScorerServiceTest (11 tests). ServiciosConecta strict_types 31 files (37/37). CopilotApiController proactive API ComercioConecta. Aprendizaje #97. |
| 2026-02-18 | **47.0.0** | **Auditoria Integridad Planes 20260217 — 2 gaps criticos resueltos:** Auditoria de integridad de 4 planes contra codebase. ComercioConecta: `declare(strict_types=1)` anadido a 177 PHP files (compliance 178/178). ServiciosConecta: 6 MJML email templates creados en jaraba_email (onboarding_profesional, activacion, reengagement, upsell_starter, upsell_pro, retention). PB blocks ComercioConecta verificados existentes (11/11). Aprendizaje #96. |
| 2026-02-17 | **46.0.0** | **Plan Elevacion ComercioConecta Clase Mundial v1 — Sprint 1-3 completado (6o vertical elevado):** jaraba_comercio_conecta elevado a Clase Mundial (18 fases en 3 sprints). Nuevo modulo anadido al registro seccion 7.1 con 42 Content Entities, 25 Services, 9 Controllers. Tabla 12.3 actualizada con ComercioConecta (18 fases + ServiciosConecta 14 fases). Sprint 1: 14 fases elevacion (FeatureGate, CopilotBridge, Journey, Health, Experiment, 6 MJML, 11 PB). Sprint 2: F6-F9 (28 entidades: orders, portales, search, engagement). Sprint 3: F10 (10 entidades: shipping, POS, admin, analytics). 7 servicios transversales ecosistema_jaraba_core + 18 servicios locales. 178 PHP files. Aprendizaje #95. |
| 2026-02-17 | **45.0.0** | **Plan Elevacion ServiciosConecta Clase Mundial v1 — 14 fases, 26/26 paridad (7o vertical):** ServiciosConecta elevado de 5/26 (19.2%) a 26/26 (100%). Modulo actualizado en seccion 7.1 (6 entities, 5 services, Clase Mundial badge, 14 fases detalle, 15 PB, SaaS plans). 8 servicios transversales ecosistema_jaraba_core (FeatureGate, CopilotBridge, EmailSequence, CrossVertical, Journey, HealthScore, CopilotAgent, Experiment). 4 FreemiumVerticalLimit + 8 UpgradeTrigger types + 2 FunnelDefinitions. SCSS compliance (5 Tailwind→var(--ej-*), color-mix). 15 PB templates (11 fix + 4 premium). ReviewService + ReviewServicios entity. Bug fix releaseSlot(). Plan: `20260217-Plan_Elevacion_ServiciosConecta_Clase_Mundial_v1.md`. Aprendizaje #94. |
| 2026-02-17 | **44.0.0** | **N2 MACRO-FASE 3 Growth Ready Platform — 5 modulos N2:** jaraba_funding v2 refactorizado (3 entities, 5 services). 4 modulos nuevos anadidos al registro seccion 7.1: jaraba_multiregion (4 entities, 5 services, expansion EU), jaraba_institutional (3 entities, 5 services, FSE/FUNDAE), jaraba_agents (5 entities, 12 services, multi-agente L0-L4), jaraba_predictive (3 entities, 7 services, PredictionBridge PHP→Python). 262 ficheros, 18 entidades, 34 servicios, 80 rutas REST. 77 modulos custom. Aprendizaje #93. |
| 2026-02-17 | **43.0.0** | **JarabaLex Legal Practice Platform Completa — FASE A2-C3:** 5 modulos nuevos anadidos al registro seccion 7.1 (jaraba_legal_calendar, jaraba_legal_billing, jaraba_legal_vault, jaraba_legal_lexnet, jaraba_legal_templates). Diagnostico Lead Magnet (LegalLandingController). JarabaLexCopilotAgent 6 modos en jaraba_ai_agents. 15 test files. 73 modulos custom. Aprendizaje #92. |
| 2026-02-17 | **42.0.0** | **Plan Elevacion AgroConecta Clase Mundial v1 — 14 fases + 11 PB premium:** AgroConecta elevado a Clase Mundial (14/14 fases). Modulo actualizado en seccion 7.1 (18 Services, Clase Mundial badge, 14 fases detalle). Tabla 12.3 actualizada con AgroConecta (14/14 + detalle 14 fases + PB premium). 7 servicios nuevos (FeatureGate, EmailSequence, CrossVertical, Journey, Health, Experiment, CopilotBridge). 12 FreemiumVerticalLimit + 8 UpgradeTrigger types + 4 FunnelDefinitions. 95 rgba→color-mix en 16 SCSS. 11 templates PB premium (jaraba_icon, schema.org, FAQ JSON-LD, LocalBusiness). 6 MJML email templates. Aprendizaje #91. |
| 2026-02-16 | **41.0.0** | **FASE A1 jaraba_legal_cases — Legal Practice Platform:** Nuevo modulo jaraba_legal_cases anadido al registro seccion 7.1. 4 Content Entities (ClientCase, CaseActivity append-only, ClientInquiry, InquiryTriage). 4 Services, 3 Controllers, 11 API REST endpoints. 2 zero-region page templates. 47 ficheros. Aprendizaje #90. |
| 2026-02-16 | **40.0.0** | **Plan Elevacion JarabaLex v1 — 14 Fases Clase Mundial:** jaraba_legal_intelligence elevado de Vertical Independiente a Clase Mundial (14/14 fases). Modulo actualizado en seccion 7.1 (icon checkmark, 10 services, Copilot Agent, FeatureGate, 5 MJML, 3 funnels). Copilot JarabaLex 6 modos anadido a seccion 8.1. Tabla 12.3 actualizada a 14/14 + detalle 14 fases. Aprendizaje #89. |
| 2026-02-16 | **39.0.0** | **Documentation Update — 5 Modules Added:** jaraba_tenant_export, jaraba_privacy, jaraba_legal, jaraba_dr, ComplianceAggregatorService añadidos al registro de modulos seccion 7.1. Reglas ZERO-REGION-001/002/003 en Directrices v39.0.0. Aprendizaje #88. |

> **Versión:** 100.0.0 | **Fecha:** 2026-03-05 | **Autor:** IA Asistente


## 15. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-03-16 | **122.0.0** | **DAILY-ACTIONS-REGISTRY-001 + PIPELINE-E2E-001:** Nueva sección arquitectural "DAILY ACTIONS — PATRÓN EXTENSIBLE" con diagrama de componentes (DailyActionInterface 16 métodos, DailyActionsRegistry tagged service collector, DailyActionsCompilerPass). Tabla cobertura multi-vertical multi-rol: 54 daily actions + 48 wizard steps en 11 módulos, 13 dashboards (9 primary + 4 secondary), 13 roles. Patrón integración controladores con DI opcional. PIPELINE-E2E-001: verificación obligatoria 4 capas (L1 Service/DI, L2 Controller, L3 hook_theme, L4 Template). Regla de oro #125. Aprendizaje #184. |
| 2026-03-13 | **121.0.0** | **AI-ECOSYSTEM-SAFEGUARD Auditoría Integral Ecosistema IA Nativo:** Streaming architecture: StreamingOrchestratorService paridad con buffered en 7 dimensiones (temperature 0.3/0.7, PII masking bidireccional, circuit breaker tenant-scoped, semantic cache composite mode, AIIdentityRule SSOT, catch(\Throwable), extractSuggestions [ACTION]). CopilotBridge cobertura 10/10 verticales: +DemoCopilotBridgeService (ecosistema_jaraba_core), LegalCopilotBridgeService completado con CopilotBridgeInterface. Circuit breaker: getCircuitBreakerKey() con tenant_id en State API keys. Cache: tenant_id ?? '0' (eliminado vertical fallback). Markdown parser clase mundial en copilot-chat-widget.js + contextual-copilot.js: code blocks con language badge, tablas pipe-syntax, blockquotes, bold+italic+strikethrough, [ACTION:label\|url] CTAs. SCSS premium: dark code theme (#1e293b), table responsive hover, action button lift animation. Aprendizaje #183. |
| 2026-03-13 | **120.0.0** | **SETUP-WIZARD-DAILY-001 Patrón Transversal + Premium World-Class UI:** Nueva seccion arquitectural "SETUP WIZARD: PATRON TRANSVERSAL" con infraestructura completa: SetupWizardStepInterface (13 metodos), SetupWizardRegistry (tagged services + CompilerPass), SetupWizardApiController (REST async), parciales Twig reutilizables (_setup-wizard + _daily-actions), JS celebrations. Caso vertical Andalucia +ei: 4 wizard steps + 5 daily action cards. Icon system: 14 SVGs nuevos, ~366 total (de ~352). ICON-SYMLINK-REL-001: symlinks relativos obligatorio para Docker/Lando. Aprendizaje #182. |
| 2026-03-13 | **119.0.0** | **ORTOGRAFIA-TRANS-001 + Coordinador Hub Premium Elevation:** Seccion 10.8.1 actualizada de 18 a 19 scripts de validacion (+validate-twig-ortografia.php: diccionario 402 entradas tildes+ñ, deteccion generica -cion/-sion, filtro comentarios Twig). lint-staged ampliado: .twig files pre-commit. post-edit-lint.sh: seccion ORTOGRAFIA-TRANS-001 con ~50 palabras. Coordinador Hub: tabs "white pill on tinted rail" + compliance cards --ring-color cascade. Aprendizaje #181. |
| 2026-03-11 | **116.0.0** | **Sprint 14 Reestructuración Actuaciones PIIL — Normative Type Alignment + Persona Atendida/Insertada:** MaterialDidacticoEi entity nueva (7 tipos material, archivo+url_externa, TENANT-ISOLATION-ACCESS-001). ActuacionComputeService (cálculo persona atendida/insertada, triggered via InscripcionSesionEi::postSave con PRESAVE-RESILIENCE-001). SesionProgramadaEi: 6 tipos PIIL-aligned (orient_lab_ind/grup, orient_ins_ind/grup, sesion_formativa, tutoria_seguimiento) con FASE_POR_TIPO + TIPOS_SESION_LEGACY_MAP + preSave formativa→accion_formativa validation. ActuacionSto: 8 tipos con TIPOS_LEGACY_MAP. AccionFormativaEi: +contenido_sto +subcontenido_sto +materiales. PlanFormativoEi: +cumple_persona_atendida +cumple_persona_insertada. CoordinadorHubService: +getEstadisticasPorFase() +getUpcomingSessionsPiil(). Dashboard: tab PIIL con Fase Atención/Inserción + KPIs. Migration script. hook_update_10023. 17 tests (368 total). Regla de oro #119. Aprendizaje #178. |
| 2026-03-11 | **114.0.0** | **NO-HARDCODE-PRICE-001 Dynamic Pricing + 23 Scripts Validacion:** Sistema de precios dinamicos en frontend conectado a MetaSitePricingService. Homepage PED: comparativa JarabaLex lee professional_price de SaasPlan entity, hero legal subtitle usa from_label, pricing hub overview renderiza 3 tiers desde getPricingPreview('_default'). 4 templates migrados de precios hardcodeados a variables dinamicas con fallbacks. PricingController: +overview_tiers render variable. hook_theme: +overview_tiers en pricing_hub_page. Script #23 validate-no-hardcoded-prices.php (regex EUR/€ en Twig, excepciones competidores/mock/comentarios). Post-edit-lint hook: deteccion de precios EUR en .twig en tiempo real (seccion 3 en case twig). validate-all.sh +NO-HARDCODE-PRICE-001 en fast mode. Fix BTN-CONTRAST-DARK-001: color blanco explicito en .ped-hero h1 (override _base.scss headings color). CSS compilado. Regla de oro #117. Aprendizaje #176. |
| 2026-03-11 | **113.0.0** | **ENV-PARITY-001 Dev/Prod Parity + 18 Scripts Validacion:** Seccion 10.8.1 actualizada de 6 a 18 scripts de validacion (lista completa). Nuevo script validate-env-parity.php (~900 LOC, 14 checks: PHP version 6 fuentes, 14 ext-* composer.json, MariaDB, Redis, PHP config, MariaDB my.cnf, OPcache invalidation, Supervisor, filesystem, multi-domain, code paths, composer.lock, reverse proxy, wildcard SSL). config/deploy/mariadb/my.cnf creado (InnoDB 16G escalable 40G, NVMe I/O 4000 IOPS, max_connections 300, max_allowed_packet 256M para canvas_data). deploy.yml +OPcache invalidation (`drush php:eval "opcache_reset()"` post-cache-rebuild). composer.json +12 ext-* declarations (bcmath, curl, dom, fileinfo, gd, intl, json, mbstring, pdo_mysql, sodium, xml, zip). php.ini +max_input_vars 5000. validate-all.sh +ENV-PARITY-001 full mode + 3 skip_checks faltantes. Analisis runbook AMD EPYC Zen 5 AE12-128 NVMe. Regla de oro #116. Aprendizaje #175. |
| 2026-03-10 | **112.0.0** | **I18N Navigation + Canvas Safeguard 4 Capas:** I18N-NAVPREFIX-001 (language_prefix inyectado en preprocess_page, 5 headers + footer prefijados con `{{ lp }}`). I18N-PATHPROCESSOR-001 (eliminado filtro langcode en PathProcessorPageContent — entity ID es único cross-language). I18N-LANGSWITCHER-001 (language switcher usa path_alias traducido para page_content). SAFEGUARD-CANVAS-001 (4 capas: backup-canvas.php backup/restore, presave hook shrinkage/empty/contamination detection, translation script entity reload + size mismatch, validate-translation-integrity.php). SAFEGUARD-ALIAS-001 (hallucination detection >80 chars + sentence patterns, 68 double-slash + 1 hallucination corregidos). validate-nav-i18n.php integrado en validate-all.sh fast mode. Regla de oro #115. Aprendizaje #174. |
| 2026-03-06 | **106.0.0** | **Theming Multi-Tenant Fixes:** SSOT-THEME-001 corregido (SiteConfig es fallback nivel 5, no override). Visual Customizer convertido de modal centrado a slide-panel lateral (480px, z-index 1050, live-update). overflow-x:clip para preservar position:sticky. CSS-ANIM-INLINE-001 (animation inline mata class-based animations). Sticky action bar con glassmorphism. Hostname-first tenant resolution en TenantThemeCustomizerForm. Aprendizaje #168. |
| 2026-03-06 | **105.0.0** | **Theming Unificado Multi-Tenant (THEMING-UNIFY-001 + SSOT-THEME-001):** ecosistema_jaraba_core: +UnifiedThemeResolverService (cascada 5 niveles: Plataforma→Vertical→Plan→Tenant→Meta-sitio, resolucion por hostname para anonimos via MetaSiteResolverService + fallback usuario autenticado via TenantContextService, cache por request). jaraba_theming: TenantThemeConfig +header_cta_url campo (BaseFieldDefinition string 255, update_10002 installFieldStorageDefinition), jaraba_theming_page_attachments() usa UnifiedThemeResolverService para resolver tenant_id por hostname (fix CSS tokens anonimos). ecosistema_jaraba_theme.theme: META-SITIO OVERRIDE block (130 lineas) reemplazado por llamada unificada a resolveForCurrentRequest() (80 lineas), bloque duplicado jaraba-tenant-tokens eliminado de hook_preprocess_html. SSOT: TenantThemeConfig=visual (colores, fuentes, CTA, layout, footer, redes sociales), SiteConfig=estructural (nombre, logo, nav, legal, SEO). SiteConfig Nivel 5 FALLBACK (applySiteConfigOverrides verifica empty() antes de aplicar — NO override). 6 tests unitarios (34 assertions). 2 reglas nuevas: THEMING-UNIFY-001, SSOT-THEME-001. Aprendizaje #166. |
| 2026-03-05 | **104.0.0** | **Stripe Embedded Checkout Self-Service (STRIPE-CHECKOUT-001):** Nueva seccion STRIPE EMBEDDED CHECKOUT con ASCII box. jaraba_billing: +StripeProductSyncService (SaasPlan→Stripe Product/Price, idempotente con lock, archiva precios obsoletos), +CheckoutSessionService (ui_mode embedded, return_url), +CheckoutController (4 handlers zero-region), +stripe-checkout.js (carga Stripe.js dinamicamente, CSRF, POST session, initEmbeddedCheckout), +presave hook auto-sync. SaasPlan entity +2 campos (stripe_product_id, stripe_price_yearly_id) + hook_update_9035. Templates: page--checkout.html.twig (zero-region), checkout-page (2 columnas), checkout-success (SVG animado), checkout-cancel (warning). SCSS: _checkout.scss BEM + var(--ej-*). MetaSitePricingService +saas_plan_id en getPricingPreview(). pricing-page.html.twig: CTAs checkout directos si saas_plan_id existe. pricing-toggle.js: sync ?cycle= param. ecosistema_jaraba_theme.theme: +body class page-checkout + theme suggestion page__checkout. Drush: StripeSyncCommands (jaraba:stripe:sync-plans). 4 rutas, 3 templates checkout, 1 JS, 1 SCSS. Aprendizaje #165. |
| 2026-03-05 | **103.0.0** | **Salvaguardas 100% Madurez:** GAP-01 SCSS hex→tokens: migrate-hex-to-tokens.php (180+ mappings, 2088 reemplazos, 3 pasadas), regla SCSS-COMPILETIME-001 (Sass compile-time `color.scale/adjust/change` requiere hex estatico, NUNCA `var()`; usar `color-mix()` para runtime). 8 modulos corregidos (comercio_conecta, einvoice_b2b, legal_intelligence, success_cases, foc, tenant_knowledge, facturae, ecosistema_jaraba_core). GAP-05 hook_update_N: generate-install-hooks.php genera hook_update_N + hook_requirements para 275 entities en 67 modulos (20 .install nuevos, 47 existentes). GAP-04 presave resilience: 3 modulos corregidos (content_hub, legal_calendar, page_builder) con hasService()+try-catch. Compilacion SCSS: 0 failures en todos los modulos y temas. 16 validadores: 15 PASS + 1 SKIP. Aprendizaje #164. |
| 2026-03-05 | **102.0.0** | **Remediacion Onboarding/Checkout/Catalogos 32 Gaps Clase Mundial:** ecosistema_jaraba_core: +GoogleOAuthService (OAuth2 client sin contrib, credenciales via settings.secrets.php), +GoogleOAuthController (redirect+callback+user creation, CSRF state token session), +2 rutas OAuth (/user/login/google, /user/login/google/callback). OnboardingController: +isConfigured() check para Google OAuth. Register template: +boton Google + Schema.org Organization JSON-LD + WCAG 2.1 AA (skip nav, aria-describedby, role=alert, focus-visible, touch targets 44px). _onboarding-progress.html.twig reescrito como semantic nav+ol con aria-current=step. SCSS: +skip-link, +focus-visible, +social-login, +wizard-animations, +prefers-reduced-motion. ecosistema_jaraba_theme: page--auth preprocess ampliado para onboarding routes (extrae system_main_block). jaraba_lms: InstructorDashboardController +courseAnalytics() (KPIs, per-lesson stats, enrollment trend, engagement chart), +1 ruta analytics, +1 library instructor-analytics con Chart.js. jaraba_billing: trial expiration notifications. jaraba_comercio_conecta: checkout con Stripe real, security badges. Bugs corregidos: FieldItemList en register template, page--auth preprocess. 2 reglas nuevas. Aprendizaje #163. |
| 2026-03-05 | **101.0.0** | **Multi-Tenant Route Cache Isolation + Vary:Host + Visual Customizer Fix:** SecurityHeadersSubscriber ampliado con onAddVaryHost() prioridad -10 (appends Vary:Host DESPUES de FinishResponseSubscriber). Domain entity PED local dev creada. SEC-08 completado. visual-customizer.js: 11 campos fantasma eliminados, 4 mismatches, 3 faltantes. TenantThemeCustomizerForm: tab titles plain text. TenantSubscriptionService: lazy-load dedup. 3 reglas: DOMAIN-ROUTE-CACHE-001, VARY-HOST-001, PHANTOM-ARG-JS-001. Aprendizaje #162. |
| 2026-03-05 | **100.0.0** | **Verticales Componibles + Tenant Settings Hub + Stripe Sync + Tenant Token Override:** jaraba_addons: Addon entity +vertical_ref campo + addon_type='vertical' + isVerticalAddon()/getVerticalRef(), TenantVerticalService (resolucion multi-vertical primario+addons por tenant), VerticalAddonBillingService (orquestacion activacion/desactivacion), VerticalAddonApiController (4 REST endpoints /api/v1/addons/verticals/). ecosistema_jaraba_core: TenantSettingsRegistry + TenantSettingsSectionPass CompilerPass + 6 secciones tagged (Domain/Plan/Branding/Design/ApiKeys/Webhooks), TenantSelfServiceController refactorizado con VERTICAL_PRODUCT_MAP. jaraba_billing: FeatureAccessService +hasActiveAddonSubscription() (GAP-H01 legacy fallback), TenantSubscriptionService changePlan() sync bidireccional con Stripe (revert local si falla), UsageStripeSyncService (GAP-C04 metered billing). jaraba_theming: ThemeTokenService auto-resuelve tenant_id desde TenantContextService, genera CSS :root en hook_preprocess_html. jaraba_ai_agents: BaseAgent::getVerticalContext() enriquece con getAddonVerticalsContext() si TenantVerticalService disponible. Theme: pricing hub/page UI improvements, route-tenant-dashboard + route-tenant-settings libraries. Config: 200+ FreemiumVerticalLimit, 28 ECA flows, plan features, design tokens actualizados. 2 reglas: ADDON-VERTICAL-001, TENANT-SETTINGS-HUB-001. Aprendizaje #161. |
| 2026-03-04 | **99.0.0** | **CI pipeline salvaguardas verde + PHPStan 1890→0 + ACCESS-RETURN-TYPE-001:** CI fitness-functions pipeline: 35 errores→0. PHPStan Level 6: 1890 errores corregidos en baseline (return type AccessResult→AccessResultInterface en 130+ AccessControlHandlers, entity field access, metodos con tipos incompatibles). Kernel tests: 460 tests, 2243 assertions, 0 errores. PHPStan baseline reducido a ~41K lineas. 1 regla nueva: ACCESS-RETURN-TYPE-001 (P0). Regla de oro #101. Aprendizaje #160. |
| 2026-03-04 | **98.0.0** | **Stripe Connect Integration + DI Audit Runtime Fixes:** Seccion PAGOS ampliada: Stripe Connect tipo plataforma/marketplace con destination charges, subscriptions recurrentes + metered billing basado en uso, 2 webhook endpoints separados (billing + FOC) con secrets independientes. settings.secrets.php unificado: 3 namespaces config (ecosistema_jaraba_core.stripe, jaraba_foc.settings, jaraba_legal_billing.settings) alimentados por 4 env vars via getenv(). StripeConnectService con fallback getenv() directo. Stripe CLI v1.37.2 instalado. DI audit exhaustivo: 21 mismatches corregidos en 9 modulos — 12 logger channel→factory (pixels 7, comercio_conecta 8, agroconecta 3, business_tools 4, resources 1), 6 phantom args eliminados (candidate 3, job_board 1, lms 3), 6 circular deps resueltas (interactive, core, foc, email, lms, resources). PHP 8.4 nullable fix FlashOfferService. 4 reglas nuevas: LOGGER-DI-001, CIRCULAR-DEP-001, PHANTOM-ARG-001, STRIPE-ENV-UNIFY-001. Gaps salvaguarda: no detection circular deps, no phantom arg validation, logger mismatch solo en --full mode. Reglas de oro #99, #100. Aprendizaje #159. |
| 2026-03-04 | **97.0.0** | **7 Items SaaS Post-Evaluacion — 7 implementaciones estrategicas:** ecosistema_jaraba_core: +1 ConfigEntity VerticalBrandConfig (18 campos sub-marca por vertical, 10 config YAMLs, revelation levels landing/trial/expansion/enterprise) + 3 Services (RevelationLevelService, NavigationFilterService, VerticalBrandSeoService) + VerticalBrandController + FeatureGateRouterService (ServiceLocator 10 verticales) + Twig test feature_allowed en JarabaTwigExtension. jaraba_analytics: +1 ContentEntity ProductMetricSnapshot (16 campos metricas pre-PMF: activation_rate, retained_d7/d30, nps_score, monthly_churn_rate, kill_criteria_triggered) + 1 ConfigEntity ActivationCriteriaConfig (10 YAMLs verticales, thresholds PMF) + 5 Services (ActivationTracking, RetentionCalculator, NpsSurvey, KillCriteriaAlert, ProductMetricsAggregator) + PrePmfDashboardController + NPS API endpoint. jaraba_pilot_manager (MODULO NUEVO): 3 ContentEntities (PilotProgram 18 campos status draft/active/completed/cancelled, PilotTenant 15 campos con activation_score/retention_d30/churn_risk, PilotFeedback 13 campos nps/csat/feature_request/bug_report/general) + PilotEvaluatorService (evaluatePilot/evaluateTenant/getConversionProbability/generateReport) + PilotDashboardController + 5 ECA Events + 3 AccessControlHandlers tenant-aware + 3 PremiumEntityFormBase + 3 ListBuilders. jaraba_institutional: +7 campos ProgramParticipant (piil_registration_number, fundae_group_id, fse_plus_indicator, certification_date/type, digital_skills_level DigComp 2.2, employment_sector CNAE) + 6 campos InstitutionalProgram (piil_program_code, fundae_action_id, fse_plus_priority_axis, cofinancing_rate, target_employment_rate, reporting_frequency) + PiilMetricsService + StoSyncService + PiilDashboardController. jaraba_integrations: +OpenApiSpecService (spec OpenAPI 3.0 dinamica) + OpenApiController (/api/v1/openapi.json cache 1h). Safeguards: CHECK 7 validate-entity-integrity.php (entity types sin .install), UPDATE-HOOK-REQUIRED-001 reforzado ConfigEntities. 40 tests, 294 assertions. Aprendizaje #158. |
| 2026-03-04 | **96.0.0** | **LCIS Gap Closure — 2 Entities + 2 QueueWorkers + EU AI Act Audit Trail:** Seccion 12.6 LCIS actualizada: jaraba_legal_knowledge 4→5 entidades (+LegalNormRelation con 10 tipos de relacion normativa: deroga_total/deroga_parcial/modifica/desarrolla/transpone/cita/complementa/prevalece_sobre/es_especial_de/sustituye, source/target_norm_id entity_reference, affected_articles JSON, PremiumEntityFormBase, AccessControlHandler tenant-aware). jaraba_legal_intelligence +1 entity LegalCoherenceLog (15 campos audit trail: query_text, intent_type, coherence_score, validator_results JSON, verifier_results JSON, norm_citations JSON, hierarchy_chain JSON, disclaimer_appended, retries_needed, blocked, block_reason, response_snippet, vertical, trace_id — EU AI Act Art. 12 compliance, read-only). +2 QueueWorkers: NormativeUpdateWorker (cron 60s, actualiza status normas afectadas + crea LegalNormRelation), NormVectorizationWorker (cron 120s, chunking + embeddings + Qdrant upsert, defensivo si servicios no disponibles). NormativeGraphEnricher L3 actualizado: consulta legal_norm_relation real para detectar derogaciones/modificaciones en vez de heuristicas. LegalCoherenceValidatorService L6 + LegalCoherenceVerifierService L7 actualizados: crean LegalCoherenceLog tras cada validacion/verificacion. 2 hook_update_N() para instalar entity types. 5 tests nuevos (2 Unit + 3 Kernel). Reglas LCIS-AUDIT-001, LCIS-GRAPH-001 en Directrices v107.0.0. Aprendizaje #157. |
| 2026-03-10 | **110.0.0** | **Anti-Spam Stack Consolidation + Coordinador Dashboard Clase Mundial + Reclutamiento Landing Elevation SEO:** Anti-spam: eliminadas capas redundantes (antibot, honeypot) de login/password forms, reCAPTCHA v3 con default_challenge=none, keys inyectadas desde GitHub Secrets en deploy.yml via putenv(). Coordinador dashboard: 19 gaps cerrados en 3 sprints P0/P1/P2 (empty states siempre visibles, focus trap Tab/Shift+Tab en modales, CSRF token TTL 1h refresh, aria-live polite, Drupal.behaviors detach con clearInterval, responsive tables sticky first column, prefers-reduced-motion, focus-visible, zero-value checkmarks). /user profile: widgets Andalucía +ei con badges por fase (5 variantes color-mix), stats row success/alert, completitud 100% success. Reclutamiento landing: social proof grid 4 cards (22.222 plazas, 40% inserción, 100% gratuito, 24/7 mentoría IA), lead magnet guía descargable, 12 FAQs SEO People Also Ask, BreadcrumbList schema, og:image 1200x630 con width/height/type metadata. StoExportForm + download route. cross_vertical fix (solo TRUE para jaraba_mentoring). 312 unit tests (1122 assertions, 0 errores) — 21 nuevos test files. Aprendizaje #173. |
| 2026-03-09 | **109.0.0** | **Twig Render Array Safety + Entity Update Hook Hardening:** 4 reglas arquitectonicas nuevas. TWIG-URL-RENDER-ARRAY-001: descubrimiento critico — `url()` en Drupal 11 Twig devuelve render array (lineas 244-247 de TwigExtension.php: `$build = ['#markup' => $url]; $generated_url->applyTo($build); return $build;`), NO un string. Concatenar con `~` genera "Array to string conversion". Solo usar dentro de `{{ }}`. TWIG-INCLUDE-ONLY-001: includes Twig DEBEN usar `only` para evitar leakage de variables (render arrays del padre contaminan el parcial). UPDATE-HOOK-FIELDABLE-001: `updateFieldableEntityType()` DEBE recibir `getFieldStorageDefinitions()` (no `getBaseFieldDefinitions()`) — campos computed como metatag crean orphan entries permanentes en key-value store. TRANSLATABLE-FIELDS-INSTALL-001: hacer entity translatable requiere instalar 6 campos content_translation individualmente. Fixes aplicados: _seo-schema.html.twig (3 archivos), jaraba_billing_update_10005, jaraba_site_builder_update_10009. Aprendizaje #171. |
| 2026-03-06 | **108.0.0** | **SEO/GEO Meta-Sitio Corporativo 10/10 — Reclutamiento Elevation + Schema.org Enriquecido:** Page Builder SEO mejorado: `_jaraba_page_builder_inject_seo_meta()` inyecta `<meta name="description">` ademas de og:description. `_jaraba_page_builder_extract_og_image()` cascade 5 niveles (hero_image > og_image > image > og-image-dynamic.png > logo.svg). Hreflang deduplication: variable `is_page_content` en preprocess_html condiciona parcial Twig (HreflangService ya cubre page_content). URLs absolutas con `'absolute' => TRUE` en Url::fromRoute(). Orphan templates resueltos: `_ped-schema.html.twig` (Corporation + GeoCoordinates + 2 PostalAddress Malaga/Sevilla + hasMap) y `_seo-schema.html.twig` (Organization + SoftwareApplication) ahora incluidos en `html.html.twig`. NAP normalizado: telefono `+34 623 174 304`, email `contacto@plataformadeecosistemas.es`, eliminados placeholders falsos de map templates. Cookie banner: localStorage-first pattern en `submitConsent()` — persist + hide ANTES del fetch. Landing reclutamiento: ZERO-REGION-003 aplicado (library via hook_page_attachments), publicidad oficial PIIL CV (logos institucionales obligatorios), Schema.org EducationalOccupationalProgram (2 sedes + GeoCoordinates + funder FSE+/SAE), geo meta tags (ES-AN, ICBM), popup reclutamiento en meta-sites homepage (sessionStorage dismiss, requestPath detection). 5 reglas nuevas. Aprendizaje #170. |
| 2026-03-03 | **95.0.0** | **Sistema de Validacion Arquitectonica Automatizada — 6 Scripts + 4 Puntos de Control:** Nueva seccion 10.8.1 Automated Architectural Validation System con ASCII box. 6 scripts en `scripts/validation/`: validate-services-di.php (DI type mismatches YAML↔PHP, 781 servicios, mapping 30+ servicios core), validate-routing.php (route→controller existence, filtro isCustomClass jaraba_*/ecosistema_*, 2127 refs), validate-entity-integrity.php (6 checks: ENTITY-001, AUDIT-CONS-001, ENTITY-PREPROCESS-001, FIELD-UI-SETTINGS-TAB-001, VIEWS-DATA-001, ECA-EVENT-001), validate-query-chains.php (QUERY-CHAIN-001 regex multilinea), validate-config-sync.sh (config/install vs config/sync drift), validate-all.sh (orquestador --fast <3s / --full). 4 puntos de integracion: pre-commit condicional (staged files trigger), CI step antes de PHPStan, deploy step pre-rsync, lando validate/validate-fast tooling. Auto-descubrimiento via glob, zero listas hardcoded. 6 violaciones pre-existentes corregidas (3 SettingsForm + 3 admin_permission). 4 bugs runtime (3 ECA event_name + 1 $wContract). 3 reglas: ARCH-VALIDATE-001, ECA-EVENT-001, BASELINE-CLEAN-001. Regla de oro #94-96. Aprendizaje #156. |
| 2026-02-28 | **94.0.0** | **Elevacion Sistematica Landing Pages + Homepage — 3 Niveles Clase Mundial:** Nueva seccion 12.10 Landing Elevation con tabla de estado por pagina (Empleabilidad/Talento/Homepage/JarabaLex/ServiciosConecta/ComercioConecta/AgroConecta/Emprendimiento). Patron 3 niveles: (1) contenido landing reflejando infraestructura real del codebase, (2) Schema.org SoftwareApplication + meta/OG/Twitter en PageAttachmentsHooks, (3) config layer FVL/SaasPlan/PlanFeatures coherentes. Empleabilidad config layer: Schema.org 15 featureList, 12 FVL nuevos (starter copilot_messages/job_alerts/job_applications + profesional 3x + enterprise 6x), 2 FVL bugs corregidos (diagnostics -1→3, offers -1→25), PlanFeatures professional fix (todos → -1), 3 SaasPlan (29/79/199 EUR). Talento: talento() reescrito 4→12 features (Mini-ATS 8 estados, Matching 5D, EmployerProfile, HealthScore, WebPush, JobAlerts, FraudDetection, API REST), 4→10 FAQs, testimonios recruiter-especificos — NO es vertical canonico sino persona recruiter de Empleabilidad. Homepage: 6 Twig partials actualizados (_seo-schema highPrice 99→199 featureList 7→12, _hero subtitle 10 verticales, _features 11 agentes IA Gen 2 badge 10 verticales, _stats metricas verificables 10/11/80+/100%, _cross-pollination 10 verticales), meta/OG/Twitter via isFrontPage(). Arquitectura homepage template-driven documentada (13 partials con fallback data). 2 reglas nuevas en Directrices v105.0.0. Aprendizaje #155. |
| 2026-02-28 | **93.0.0** | **Claude Code Development Pipeline — DX Clase Mundial:** Nueva seccion 10.6 Developer Experience Pipeline con ASCII box 5 capas. Capa 1 Proteccion: `pre-tool-use.sh` PreToolUse hook bloquea 7 archivos criticos (exit 2 + JSON deny stdout), advierte master docs (ask). Capa 2 Lint: `post-edit-lint.sh` PostToolUse hook valida 4 tipos archivo (PHP syntax + PREMIUM-FORMS + eval, SCSS CSS-VAR-ALL-COLORS-001 + @import + rgba, JS ROUTE-LANGPREFIX-001 + innerHTML XSS, Twig |raw + i18n). Capa 3 Subagentes: reviewer (20+ directrices, 4 niveles severidad), tester (7 reglas PHP 8.4, templates Unit+Kernel), security-auditor (10 categorias escaneo). Capa 4 Commands: /fix-issue (8 pasos branch+PR), /create-entity (scaffold 9 archivos), /verify-runtime (12 checks), /audit-wcag (10 categorias WCAG 2.1 AA). Capa 5 Integraciones: MCP Stripe, CLAUDE.md 236 LOC, Estado SaaS 793 LOC. settings.json con hooks config. 14 ficheros, 30 tests funcionales. 3 bugs corregidos (Python extraction, output channel, multiline grep). 3 reglas nuevas en Directrices v104.0.0. Aprendizaje #154. |
| 2026-02-28 | **92.0.0** | **Sistema de Coherencia Juridica LCIS 9 Capas — Validacion Normativa + Anti-Sycophancy + Multi-Turn:** Nuevo ASCII box SISTEMA DE COHERENCIA JURIDICA (LCIS) en seccion 12.6. 9 servicios PHP en `jaraba_legal_intelligence/src/LegalCoherence/`: LegalCoherenceKnowledgeBase (L1, 9 niveles NORMATIVE_HIERARCHY derecho_ue_primario→circulares, 6 FORAL_LAW_REGIMES, 7 STATE_EXCLUSIVE_COMPETENCES Art.149.1, 30+ NORM_TYPE_PATTERNS regex, pesos authority 0.98-0.20), LegalIntentClassifierService (L2, gate 5 intents LEGAL_DIRECT/IMPLICIT/REFERENCE/COMPLIANCE/NON_LEGAL, scoring keywords con thresholds 0.85/0.15, shortcircuits acciones legales + jarabalex, bonus vertical), NormativeGraphEnricher (L3, authority-aware RAG ranking formula 0.55 semantic + 0.30 authority + 0.15 recency, derogation filter, territory warnings, CCAA competence bonus), LegalCoherencePromptRule (L4, 8+1 reglas R1-R8 + R9 territorial, COHERENCE_PROMPT full + COHERENCE_PROMPT_SHORT, applyWithTerritory() con foral law auto-detection, requiresCoherence()/useShortVersion()), LegalConstitutionalGuardrailService (L5), LegalCoherenceValidatorService (L6, 7 checks: hierarchy_inversion/organic_law/eu_primacy/competence/vigencia/contradiction/sycophancy + antinomies, PENALTIES scoring critical=-0.30 to low=-0.05, action regenerate max 2 retries/block/warn/allow, anti-sycophancy V8 premise validation), LegalCoherenceVerifierService (L7), LegalDisclaimerEnforcementService (L8, non-removable disclaimer, fallback without LegalDisclaimerService, coherence score display threshold 70%), LegalConversationContext (MT, extractAssertions para competencia/primacia/vigencia/reserva_lo/retroactividad/jerarquia, checkCrossTurnCoherence, MAX_TURNS=20, MAX_ASSERTIONS=50). 7 ficheros de test: 149 tests, 277 assertions, 0 errores. EU AI Act compliance HIGH RISK Annex III art.8. 6 reglas nuevas en Directrices v103.0.0. Aprendizaje #153. |
| 2026-02-28 | **91.0.0** | **Unificacion Landing JarabaLex + Despachos — 5 Modulos Habilitados + 18 FreemiumVerticalLimit + LexNET + SEO:** Unificacion de `/despachos` en `/jarabalex` via 301 redirect (`Url::fromRoute()`). 5 modulos legales habilitados (jaraba_legal_calendar, jaraba_legal_vault, jaraba_legal_billing, jaraba_legal_lexnet, jaraba_legal_templates). 18 nuevos FreemiumVerticalLimit configs (6 feature_keys × 3 planes: max_cases, vault_storage_mb, calendar_deadlines, billing_invoices_month, lexnet_submissions_month, template_generations_month — total 36). JarabaLexFeatureGateService +6 FEATURE_TRIGGER_MAP entries. UpgradeTriggerService +6 trigger types (11 total) con mensajes y iconos. 3 SaasPlan YAMLs actualizados (starter/pro/enterprise con legal modules). jarabalex() reescrito con 8 features (LexNET killer differentiator, expedientes, agenda, facturacion, boveda), 10 FAQs, pain points despacho, pricing preview con limites reales. despachos() convertido a 301 redirect. legalRedirect() actualizado. Megamenu: "Despachos" → "JarabaLex". PageAttachmentsHooks: SEO meta description + Schema.org SoftwareApplication JSON-LD para /jarabalex (RouteMatchInterface inyectado). Theme: landing.legal anadido a $vertical_routes. Fix: 5 controllers PHP 8.4 readonly property conflict con ControllerBase (CONTROLLER-READONLY-001). Fix: VaultApiController syntax error. 2 reglas nuevas en Directrices v102.0.0: LEGAL-LANDING-UNIFIED-001, CONTROLLER-READONLY-001. Aprendizaje #152. |
| 2026-02-28 | **90.0.0** | **Centro de Ayuda Clase Mundial — /ayuda con 25 FAQs + Busqueda Unificada FAQ+KB:** Nuevo ASCII box CENTRO DE AYUDA CLASE MUNDIAL en seccion 8.3. Modulo `jaraba_tenant_knowledge` elevado: HelpCenterController refactorizado (getCategoryMeta(), buildFaqPageSchema(), buildBreadcrumbSchema(), buildHelpCenterSeoHead(), getKbArticleCount(), searchApi unificado FAQ+KB). 8 categorias SaaS multi-vertical (getting_started, account, features, billing, ai_copilot, integrations, security, troubleshooting). 25 FAQs seed platform-wide via update_10003 (tenant_id=NULL). Template: hero con trust signals, quick links via Url::fromRoute(), KB cross-link, CTA con botones accionables. Busqueda unificada: FAQ+KB con hasDefinition() guard, slug-based KB URLs, campo type en JSON, JS drupalSettings searchApiUrl. SEO: FAQPage + BreadcrumbList + QAPage JSON-LD, OG/Twitter meta tags, canonical. SCSS: quick-links grid, kb-promo banner, contact buttons, animations con prefers-reduced-motion + no-js fallback. Integracion: jaraba_support (quick links), Knowledge Base (cross-link + busqueda), FAQ Bot widget, footer link. 10 ficheros modificados. Aprendizaje #151. |
| 2026-02-27 | **88.0.0** | **Navegación Ecosistema + Pricing Labels — Megamenu Selectivo + formatFeatureLabels():** Corrección de 6 regresiones de navegación del ecosistema: megamenu restringido a SaaS principal via `header_megamenu|default(false)` con inyección PHP selectiva en `preprocess_page()`, megamenu transparente corregido con `var(--header-bg, #ffffff)` fallback, menu items alineados via normalización button/anchor CSS, barra ecosistema footer activada por defecto con 4 links, mobile overlay condicional por `use_megamenu`, pricing features con labels humanos via `MetaSitePricingService::formatFeatureLabels()` (mapa 28 machine names → labels traducibles, almacenamiento dual features/features_raw). Starter tier con features base default cuando vacío (basic_profile, community, one_vertical, email_support). 2 reglas: MEGAMENU-CONTROL-001 (P1), PRICING-LABEL-001 (P1). Aprendizaje #149. |
| 2026-02-27 | **87.0.0** | **Demo Vertical PLG 100% Clase Mundial — Guided Tour + Storytelling AI + Progressive Disclosure:** Nuevo ASCII box DEMO VERTICAL PLG. Subsistema demo en ecosistema_jaraba_core (~4,000 LOC): DemoController (765 LOC, 8 metodos: landing/start/dashboard/storytelling/playground/regenerate/track/convert), DemoInteractiveService (1,523 LOC, 10 perfiles, metricas sinteticas, session management demo_sessions table, analytics aggregation demo_analytics table, 11 historias por perfil), DemoFeatureGateService (149 LOC, usage tracking), DemoJourneyProgressionService (319 LOC, disclosure levels basic→intermediate→advanced, nudge system), GuidedTourService (demo_welcome 4 pasos, user_completed_tours table). 4 JS libraries self-contained (dashboard chart+tracking+conversion+countdown, guided-tour overlay+spotlight+popovers+keyboard, storytelling fetch+copy+feedback, ai-playground copilot+scenarios+typing). 5 templates + 3 partials DRY. drupalSettings injection chain: demo.sessionId + demoTour (GuidedTourService) + demoStorytelling.regenerateUrl (Url::fromRoute) + demoPlayground.copilotEndpoint (Url::fromRoute). User journey: /demo → profile select → dashboard (tour auto-start) → magic actions → storytelling (AI regenerate) → progressive disclosure → conversion modal → /registro/{vertical}. 24 unit tests, 84 assertions. SCSS _demo.scss 1,180 LOC compilado. 2 reglas nuevas en Directrices v96.0.0: SCSS-COMPILE-VERIFY-001, RUNTIME-VERIFY-001. Aprendizaje #146. |
| 2026-02-27 | **86.0.0** | **jaraba_support Clase Mundial — Soporte Omnicanal + SLA Engine + AI Agent + SSE + HMAC Attachments:** Nuevo ASCII box SOPORTE AL CLIENTE CLASE MUNDIAL. Modulo `jaraba_support` implementado completo en 10 fases. Arquitectura: 7 Content Entities (SupportTicket con state machine 6 estados open→in_progress→waiting_customer→resolved→closed + escalated lateral, TicketMessage, TicketAttachment, TicketWatcher, TicketTag, TicketAiClassification, TicketAiResolution) + 2 Config Entities (SlaPolicy con id {plan_tier}_{priority}, SupportSettings). 18 servicios: TicketService (state machine con VALID_TRANSITIONS + first_responded_at tracking), SlaEngineService (attachSlaToTicket resolve policy → calculateDeadline con BusinessHoursService, processSlaCron check breached/warned), BusinessHoursService (loadSchedule desde ConfigEntity, isDayInSchedule timezone-aware, isHoliday, addBusinessHours minuto a minuto con max 365 iteraciones), TicketRoutingService (multi-factor scoring: skills +50, vertical +30, workload inversely proportional +20, experience bonus +15 para critical/high), AttachmentService (upload con FileSystemInterface::saveData, validation MIME/extension/size/double-extension 16 dangerous exts), AttachmentScanService (ClamAV nSCAN via unix socket + 4-layer heuristic fallback: dangerous ext, double-ext, suspicious content 8KB, MIME mismatch), AttachmentUrlService (HMAC SHA-256 signed URLs base64 JSON token 1h expiry, 3-tier auth admin/reporter/assignee, BinaryFileResponse con security headers), TicketNotificationService (5 metodos: created/newMessage/slaWarning/slaBreached escalation/resolved con CSAT link), TicketMergeService (transferencia mensajes/adjuntos/watchers con duplicate detection + system message), CsatSurveyService (schedule 1h delay, submit 1-5 clamp, low satisfaction warning), SupportHealthScoreService (5 componentes: volume trend, SLA compliance, CSAT, escalation rate, resolution speed, churn alert <40), TicketStreamService (DB-backed SSE con support_ticket_events, agent+watcher ticket merging, viewer registration), SupportCronService (5 tareas: SLA/scans/auto-close 7d/CSAT surveys/purge 24h), SupportAgentSmartAgent (Gen 2, 5 acciones fast/balanced/premium). SupportApiController 12+ endpoints REST. SupportStreamController SSE. hook_schema 2 tablas auxiliares + update_10001. 22 unit tests / 111 assertions. Aprendizaje #145. |
| 2026-02-27 | **85.0.0** | **Sprint 5 IA Clase Mundial 100/100 — Gen2 Complete + SemanticCache + MultiModal + CWV + Locking:** Auditoria IA 30/30 completada (score 100/100). 11 agentes Gen 2 (0 Gen 1 remaining): SmartMarketing, Storytelling, CustomerExperience, Support, ProducerCopilot, Sales, MerchantCopilot, SmartEmployabilityCopilot, SmartLegalCopilot, SmartContentWriter + LearningPathAgent (jaraba_lms). Nuevos servicios: AgentBenchmarkService (golden datasets + AgentBenchmarkResult entity), PromptVersionService + PromptTemplate ConfigEntity (versioning + rollback), BrandVoiceProfile ContentEntity (per-tenant brand voice persistence), PersonalizationEngineService (6 source orchestration). SemanticCache integrado en CopilotOrchestrator (GET before LLM, SET after). MultiModalBridgeService COMPLETE: analyzeImage (GPT-4o Vision), transcribeAudio (Whisper), synthesizeSpeech (TTS-1/TTS-1-HD), generateImage (DALL-E 3). Page Builder concurrent edit locking: optimistic locking via `changed` + X-Entity-Changed, edit_lock_uid/edit_lock_expires fields, 3 API endpoints acquire/release/status, JS heartbeat. CWV: 7 CSS bundles (code splitting), cwv-tracking.js PerformanceObserver (LCP/CLS/INP/FCP/TTFB), fetchpriority="high" 10 hero templates, AVIF+WebP responsive images, responsive_image() Twig function. |
| 2026-02-27 | **84.0.0** | **DOC-GUARD + Kernel Test Resilience + jaraba_workflows:** Nuevo ASCII box WORKFLOW AUTOMATION ENGINE en seccion 7.1. Modulo `jaraba_workflows` (S4-04) documentado: WorkflowRule entity (trigger-based actions), WorkflowExecutionService (event_dispatcher + observability opcional), 15 ficheros. Fix Kernel tests: servicios AI cross-module (`@ai.provider`, `@jaraba_ai_agents.*`) cambiados a `@?` (optional) en jaraba_content_hub + jaraba_lms para prevenir ServiceNotFoundException. ContentWriterAgent + ContentEmbeddingService con constructores nullable. Regla KERNEL-OPTIONAL-AI-001 en Directrices v91.0.0. DOC-GUARD-001: pre-commit hook + CI verification de umbrales de documentos maestros. Aprendizaje #142. |
| 2026-02-27 | **81.0.0** | **Reviews & Comments Clase Mundial — Auditoría + Plan Consolidación 10 Verticales:** Nuevo ASCII box REVIEWS & COMMENTS CLASE MUNDIAL. Auditoría exhaustiva de 4 sistemas de calificaciones heterogéneos (comercio_review, review_agro, review_servicios, session_review) con 20 hallazgos: 4 seguridad (tenant_id apunta a taxonomy en vez de group, 2 entidades sin tenant_id), 5 bugs (campo $text indefinido, clase inexistente, entidad reading_history inexistente, hook_cron ausente, búsqueda pública ausente), 4 arquitectura (duplicación de código en 3 servicios, nomenclatura inconsistente status/state, Q&A sin respuesta de provider, lógica de negocio en controladores), 3 directrices (presave sin hasService, slug hardcoded, accessCheck ausente), 4 brechas clase mundial (sin Schema.org AggregateRating, sin frontend visual, sin moderación IA, sin invitaciones post-transacción). Plan de implementación con ReviewableEntityTrait (5 campos compartidos + helpers con fallback), 5 servicios transversales (moderation, aggregation, schema.org, invitation, AI summary), 2 entidades nuevas (course_review para LMS, content_comment para Content Hub threading), 6 Twig partials, 350+ líneas SCSS, star-rating.js widget, GrapesJS review-block plugin. Cobertura de 10 verticales canónicos: comercioconecta, agroconecta, serviciosconecta, mentoring, formacion, jaraba_content_hub + empleabilidad, emprendimiento, jarabalex, andalucia_ei (extensibles). 3 reglas nuevas: REVIEW-TRAIT-001, REVIEW-MODERATION-001, SCHEMA-AGGREGATE-001. Aprendizaje #140. |
| 2026-02-26 | **79.0.0** | **Remediación de Secretos — SECRET-MGMT-001 + git-filter-repo:** Nuevo ASCII box GESTIÓN DE SECRETOS. Arquitectura de 3 capas: (1) config/sync/ con YAML sanitizados (valores vacíos para campos sensibles), (2) config/deploy/settings.secrets.php con 14 $config overrides desde getenv() (OAuth Google/LinkedIn/Microsoft, SMTP IONOS, reCAPTCHA v3, Stripe), (3) variables de entorno (.env local gitignored + Lando env_file injection, panel hosting producción). Flujo config:import/export seguro: YAML vacíos → BD → $config override runtime-only → export nunca expone secretos. Limpieza historial git con git-filter-repo --blob-callback (10 secretos eliminados de 459 commits, force push). 1 regla nueva: SECRET-MGMT-001 (P0). Aprendizaje #138. |
| 2026-02-27 | **83.0.0** | **Reviews & Comments Clase Mundial — Sprint 11: 18/18 Brechas Implementadas (Elevación 55%→80%+):** 18 brechas en 3 sprints. Sprint 11.1 Table Stakes: ReviewHelpfulnessService (Wilson Lower Bound z=1.96), ReviewApiController (filtering/sorting/owner response), verified purchase badges. Sprint 11.2 Differentiators: ReviewAnalyticsService + ReviewAnalyticsDashboardController (Chart.js trend/doughnut), photo gallery (lightbox JS), FakeReviewDetectionService (jaraba_ai_agents, multi-signal scoring), ReviewSeoController (JSON-LD + microdata + pagination), ReviewSentimentService (presave hook), ReviewTenantSettings ConfigEntity (18 campos configurables per-tenant), ReviewNotificationService (presave hooks). Sprint 11.3 Consolidación: ReviewTranslationService (language detection ES/EN/PT-BR), ReviewGamificationService (6 actions, 5 tiers bronze→diamond, 6 badges, leaderboard, self-creating tables), ReviewImportExportService (CSV/JSON), ReviewAbTestService (5 experiments, consistent hashing crc32), ReviewVideoService (MIME validation 100MB), ReviewPublicApiController (3 GET endpoints, max 50/page, approved-only), ReviewWebhookService (6 event types, HMAC sha256 signatures, queue-based async delivery, max 3 retries). Entity lifecycle hooks en ecosistema_jaraba_core.module: presave (sentiment + fake detection enrichment), insert (aggregation + notification + gamification + webhook), update (aggregation + status change + webhook). update_9021 para tablas gamification/AB/webhook. 14 nuevos servicios + 4 controladores + 1 ConfigEntity + review-analytics.js + review-seo-page.html.twig. 7 test files nuevos (44 tests). Total suite: 3.342 tests / 0 errores. Aprendizaje #141. |
| 2026-02-27 | **82.0.0** | **Reviews & Comments Clase Mundial — Implementacion Completa 10 Fases + 18 Brechas:** Nuevo ASCII box REVIEWS SYSTEM ARCHITECTURE. ReviewableEntityTrait compartido (5 campos + 8 metodos) aplicado a 6 entidades en 4 verticales. 2 entidades nuevas: CourseReview (jaraba_formacion), ContentComment (jaraba_content_hub). 5 servicios transversales en ecosistema_jaraba_core: ReviewModerationService (6 entity types, STATUS_FIELD_MAP), ReviewAggregationService (RATING/TARGET/STATUS_FIELD_MAP, cache tags, denormalizacion), ReviewSchemaOrgService (JSON-LD AggregateRating + ReviewList). 2 servicios en jaraba_ai_agents: ReviewAiSummaryService (ModelRouterService fast tier), ReviewInvitationService (templates MJML). Frontend: page--reviews.html.twig + 5 parciales + _reviews.scss (design tokens) + star-rating.js. GrapesJS Review Widget Block. Schema.org SEO con JSON-LD. Security: tenant_id entity_reference→group, access handlers DI, CSRF. 68 unit tests (PHPUnit 11 + PHP 8.4 patterns). Auditoria post-implementacion: 55% cobertura (target 80%+). 18 brechas en Fase 11 (156-218h). Aprendizaje #140. |
| 2026-02-26 | **78.0.0** | **Meta-Sitios Analytics Stack — GTM/GA4 + A/B Testing + i18n Hreflang + Heatmap:** Nuevo bloque arquitectonico "META-SITES ANALYTICS STACK (4 CAPAS)": (1) Conversion Tracking con 6 behaviors dataLayer, (2) A/B Testing frontend con cookies y DOM manipulation, (3) Heatmap con jaraba_heatmap/tracker, (4) GTM/GA4 con Consent Mode v2 GDPR. SEO internacional con hreflang ES/EN/x-default. PWA preexistente verificada. 4 librerias condicionadas con meta_site. 5 reglas nuevas en Directrices v83.0.0. |
| 2026-02-26 | **77.0.0** | **Auditoria IA Clase Mundial — 25 Gaps hacia Paridad con Lideres del Mercado:** Nuevo ASCII box AUDITORIA IA CLASE MUNDIAL. 25 gaps auditados contra Salesforce Agentforce, HubSpot Breeze, Shopify Sidekick, Intercom Fin. 7 refinamiento (Onboarding AI, Pricing Metering, Demo Playground, Dashboard GEO, llms.txt MCP, Schema.org GEO, Dark Mode AI) + 16 nuevos (Command Bar Cmd+K con CommandRegistryService tagged services, Inline AI con SmartBaseAgent fast tier + sparkle buttons, Proactive Intelligence ContentEntity + QueueWorker cron, Voice AI Web Speech API client-side, A2A Protocol extending MCP JSON-RPC 2.0 con Agent Card /.well-known/agent.json, Vision/Multimodal MultiModalBridgeService implementacion, 40+ unit + 15+ kernel + 7 prompt regression tests, Blog slugs ParamConverter + ContentArticle tenant_id, 5 vertical AI: SkillInferenceService + AdaptiveLearningService AI activation + DemandForecastingService + GrapesJS AI Writer + ServiceMatchingService Qdrant wiring, Design System ComponentDocumentationController) + 2 infraestructura (Cost Attribution AIObservability→TenantMetering, Horizontal Scaling Redis worker pool). 4 sprints, 320-440h. Plan: `docs/implementacion/2026-02-26_Plan_Implementacion_Auditoria_IA_Clase_Mundial_v1.md`. Aprendizaje #134. |
| 2026-02-26 | **76.0.0** | **AI Elevation 10 GAPs — Streaming Real + MCP Server + Native Tools:** Nuevo ASCII box AI ELEVATION 10 GAPs. GAP-01: StreamingOrchestratorService extiende CopilotOrchestratorService, ChatInput::setStreamedOutput(TRUE) para streaming real, PHP Generator con eventos chunk/cached/done/error, buffer 80 chars + PII masking incremental. GAP-09: ToolRegistry::generateNativeToolsInput() convierte a ToolsInput/ToolsFunctionInput/ToolsPropertyInput, SmartBaseAgent::callAiApiWithNativeTools() con ChatInput::setChatTools(), fallback a text-based. GAP-08: McpServerController POST /api/v1/mcp JSON-RPC 2.0 con initialize/tools-list/tools-call/ping, MCP 2025-11-25. GAP-02: TraceContextService trace_id UUID + span_id. GAP-03/10: Buffer PII masking cross-chunk. GAP-07: AgentLongTermMemoryService Qdrant + BD. 3 ficheros nuevos + 4 modificados. 7 reglas nuevas. Aprendizaje #133. |
| 2026-02-26 | **75.0.0** | **AI Remediation Plan — 28 Fixes, 3 Phases:** Nuevo ASCII box AI REMEDIATION STACK. Fase 1 (P0): AIIdentityRule clase estatica centralizada, brand voice fallback, guardrails pipeline ALLOW/MODIFY/BLOCK/FLAG, SmartBaseAgent contrato restaurado (routing+observability+guardrails), CopilotOrchestratorService 8 modos reales, streaming SSE con MIME correcto y eventos tipados, AgentOrchestrator simplificado, feedback loop con threshold. Fase 2 (P1): RAG prompt injection filter, embedding cache, A/B testing framework, tenant brand voice YAML, content approval workflow entity, campaign calendar entity, performance dashboard, Qdrant graceful fallback, auto-disable por error rate, vertical context normalizado. Fase 3 (P2): ModelRouterService con regex bilingue EN+ES (FIX-019), model pricing a YAML config con schema (FIX-020), observability conectada en BrandVoiceTrainer+WorkflowExecutor (FIX-021), AIOpsService con metricas reales /proc+BD (FIX-022), feedback widget JS↔PHP alineado (FIX-023), streaming semantico por parrafos (FIX-024), Gen 0/1 agents documentados (FIX-025), @? UnifiedPromptBuilder optional DI (FIX-026), canonical verticals 10 nombres (FIX-027), PII espanol DNI/NIE/IBAN/NIF/+34 (FIX-028). 55 ficheros, +3678/-236 lineas. 5 reglas nuevas. Regla de oro #45. Aprendizaje #127. |
| 2026-02-25 | **74.0.0** | **Premium Forms Migration 237 + USR-004 User Edit Redirect:** Nuevo ASCII box PREMIUM ENTITY FORMS. `PremiumEntityFormBase` como clase abstracta estandar para todos los formularios de entidad (237 forms en 50 modulos). 4 patrones de migracion (A: Simple, B: Computed, C: DI, D: Custom Logic). Glass-card UI con navigation pills y sticky action bar. SCSS `_premium-forms.scss`. 0 `ContentEntityForm` restantes en modulos custom. Fix USR-004: redirect de `/user/{id}/edit` a perfil canonico tras save. Regla PREMIUM-FORMS-PATTERN-001 (P1). Aprendizaje #125. |
| 2026-02-26 | **79.0.0** | **Meta-Sitios Multilingüe — i18n EN+PT-BR + Language Switcher:** Nuevo soporte multilingüe completo para los 3 meta-sitios. PageContent entity ya es `translatable = TRUE` — modulo `jaraba_i18n` con `AITranslationService` (translateBatch/translateEntity con brand voice) y `TranslationManagerService` (createTranslation, stats, dashboard) como infraestructura existente. Script `translate-metasite-pages.php` crea traducciones batch: 46 traducciones generadas (EN para pepejaraba+jarabaimpact+PED, PT-BR para jarabaimpact). Manejo especial de canvas_data (GrapesJS JSON → extraer components[].content + attributes, traducir, re-ensamblar), content_data (JSON recursivo con skip patterns para IDs/URLs/colores), rendered_html (regex text nodes → batch translate → replace). Hreflang dinamico: `_hreflang-meta.html.twig` itera `available_languages` (inyectado desde preprocess_html via `getTranslationLanguages()`). Language Switcher: `_language-switcher.html.twig` dropdown glassmorphism con banderas emoji, `language-switcher.js` (toggle/ESC/arrow keys), SCSS con dark variant + responsive. Preprocess hook inyecta `available_languages` + `current_langcode` en PageContent meta-site context. PT-BR: `drush language:add pt-br` → 12.038 traducciones Drupal importadas. 2 reglas nuevas en Directrices v87.0.0. Aprendizaje #139. |
| 2026-02-25 | **73.0.0** | **Meta-Site Icon Emoji Remediation + PathProcessor Enhancement:** Icon System: nueva categoria `business/` con 12 SVGs (6 conceptuales + 6 duotone) para meta-sitio pepejaraba.com. 11 emojis Unicode eliminados de canvas_data (4 paginas). Seccion Icon System ampliada: categorias verticales, auditoria canvas_data, reglas ICON-EMOJI-001 + ICON-CANVAS-INLINE-001. PathProcessor: prioridad actualizada a 250, nuevo `resolveHomepage()` con MetaSiteResolverService. MetaSiteResolverService: 3-strategy domain resolution (Domain Access + Tenant.domain + subdomain prefix) documentada. Meta-sitio pepejaraba.com (9 paginas) anadido junto a jarabaimpact.com. Aprendizaje #124. |
| 2026-02-25 | **72.0.0** | **Elevacion Empleabilidad + Andalucia EI Plan Maestro + Meta-Site Rendering:** 3 ASCII boxes nuevos. Empleabilidad: CandidateProfileForm premium con 6 secciones, ProfileSectionForm generico CRUD, photo entity_reference→image, date timestamp→datetime, 5 CV PNGs, seccion idiomas, ProfileCompletionService con entity queries. Andalucia EI Plan Maestro 8 fases: P0/P1 fixes, 11 bloques PB verticales, landing conversion, portal participante, ExpedienteDocumento (19 categorias), mensajeria integration, AI automation (CopilotContextProvider + AdaptiveDifficultyEngine + 4 nudges), SEO. Meta-Site: MetaSiteResolverService, Schema.org tenant-aware, title tag override, header/footer/nav desde SiteConfig. CRM: 5 forms a PremiumEntityFormBase. 71+ ficheros. Aprendizaje #123. |
| 2026-02-25 | **71.0.0** | **Remediacion Tenant 11 Fases:** 2 ASCII boxes nuevos: TENANT BRIDGE (TenantBridgeService con 4 metodos, consumidores, error handling, regla TENANT-BRIDGE-001) y TENANT ISOLATION (PageContentAccessControlHandler con DI + isSameTenant(), DefaultEntityAccessControlHandler rename, PathProcessor tenant-aware, TenantContextService enhanced nullable). 14 correcciones billing entity type en 6 ficheros. CI pipeline con kernel-test job (MariaDB 10.11). 5 tests nuevos. Scripts movidos a scripts/maintenance/. Reglas TENANT-BRIDGE-001, TENANT-ISOLATION-ACCESS-001, CI-KERNEL-001. Aprendizaje #122. |
| 2026-02-24 | **70.0.0** | **Meta-Sitio jarabaimpact.com — PathProcessor + Content:** Nuevo `PathProcessorPageContent` (InboundPathProcessorInterface, prioridad 200) para resolver path_alias de entidades PageContent a rutas /page/{id}. 7 páginas institucionales creadas y publicadas con contenido en español via GrapesJS. APIs: PATCH /config (títulos + aliases), POST /publish (publicación), GrapesJS store (contenido). Regla PATH-ALIAS-PROCESSOR-001. Aprendizaje #120. |
| 2026-02-24 | **69.0.0** | **Auditoria Horizontal — Strict Equality + CAN-SPAM MJML:** Primera auditoria cross-cutting del SaaS. 52 instancias de `==` reemplazadas por `(int) === (int)` en 39 access handlers de 21 modulos (ACCESS-STRICT-001). 28 plantillas MJML horizontales con compliance CAN-SPAM completo: mj-preview, postal Juncaril, font Outfit, paleta de marca unificada (#1565C0 como azul primario, 6 colores universales reemplazados). Colores semanticos preservados. Secciones de arquitectura: Access Handlers + Email CAN-SPAM. 5 reglas nuevas. Aprendizaje #119. |
| 2026-02-24 | **68.0.0** | **Empleabilidad Profile Premium — Fase Final:** Nueva entidad `CandidateEducation` (ContentEntity completa con AdminHtmlRouteProvider, field_ui_base_route, 6 rutas admin, SettingsForm, update hook 10002). Fix XSS `\|raw` → `\|safe_html` en template de perfil premium. Controller fallback cleanup → render array con template premium. Seccion de arquitectura Empleabilidad documentada (6 entidades, 7 secciones glassmorphism, ProfileController resiliente). 3 ficheros creados, 6 modificados. Aprendizaje #118. |
| 2026-02-24 | **67.0.0** | **Entity Admin UI Remediation Complete:** 286 entidades auditadas, 175 Field UI tabs, CI 100% green. |
| 2026-02-24 | **66.0.0** | **Icon System — Zero Chinchetas:** Sistema de iconos `jaraba_icon()` auditado y completado. 305 pares unicos verificados en todo el codebase con 0 chinchetas restantes. ~170 SVGs/symlinks nuevos en 8 bridge categories. 32 llamadas con convencion rota corregidas en 4 modulos (jaraba_interactive, jaraba_i18n, jaraba_facturae, jaraba_resources). 177 templates Page Builder verificados. 2 symlinks circulares y 1 roto reparados. Reglas ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001. |
| 2026-02-23 | **64.0.0** | **Andalucia +ei Launch Readiness:** Correccion de 8 incidencias bloqueantes para la 2a edicion. Fix critico: `{{ messages }}` en template de solicitud (formulario tragaba errores silenciosamente). 6 emojis reemplazados por `jaraba_icon()`. 5 rutas nuevas para paginas legales/informativas (`/politica-privacidad`, `/terminos-uso`, `/politica-cookies`, `/sobre-nosotros`, `/contacto`). Controladores con `theme_get_setting()` para contenido configurable. 3 templates zero-region. Footer con URLs canonicas en espanol. Badge "6 verticales" corregido. TAB 14 en theme settings para contenido legal. 13 ficheros modificados. Reglas FORM-MSG-001, LEGAL-ROUTE-001, LEGAL-CONFIG-001. Aprendizaje #110. |
| 2026-02-23 | **63.0.0** | **AI Identity Enforcement + Competitor Isolation:** Blindaje de identidad IA implementado en toda la plataforma. `BaseAgent.buildSystemPrompt()` inyecta regla de identidad como parte #0 (heredada por 14+ agentes). `CopilotOrchestratorService` antepone `$identityRule` a los 8 modos. `PublicCopilotController` incluye bloque IDENTIDAD INQUEBRANTABLE. Servicios standalone (FaqBotService, ServiciosConectaCopilotAgent, CoachIaService) con regla manual. Eliminadas 5 menciones de competidores en prompts de IA. 12 archivos modificados. Reglas AI-IDENTITY-001, AI-COMPETITOR-001. |
| 2026-02-23 | **62.2.0** | **Sticky Header Global:** `.landing-header` migrado de `position: fixed` a `position: sticky` por defecto. Solo `body.landing-page`/`body.page-front` mantienen `fixed`. Eliminados padding-top compensatorios fragiles de `.main-content`, `.user-main`, `.error-page`. Toolbar admin ajustado globalmente (`top: 39px/79px`). 4 archivos SCSS modificados. Regla CSS-STICKY-001. |
| 2026-02-23 | **62.0.0** | **Precios Configurables v2.1:** 2 ConfigEntities (`SaasPlanTier` + `SaasPlanFeatures`) como fuente de verdad para tiers, features y limites. `PlanResolverService` broker central con cascade especifico→default→NULL. Integracion en QuotaManagerService, PlanValidator y BillingWebhookController. 21 seed YAMLs + update hook 9019. Admin UI en `/admin/config/jaraba/plan-tiers` y `plan-features`. Drush command `jaraba:validate-plans`. 14 archivos nuevos + 11 editados. |
| 2026-02-20 | **61.0.0** | **Secure Messaging Implementado (Doc 178):** Modulo `jaraba_messaging` implementado al completo con 104 archivos. 4 entidades PHP (SecureConversation + ConversationParticipant ContentEntities, SecureMessage + MessageAuditLog custom tables), 3 modelos (SecureMessageDTO readonly, EncryptedPayload, IntegrityReport), 18 servicios + 7 access checks, 7 controladores REST, 4 WebSocket (Ratchet server + ConnectionManager + MessageHandler + AuthMiddleware), 8 ECA plugins (3 eventos + 3 condiciones + 2 acciones), 9 Twig templates (zero-region), 11 SCSS + 4 JS. Cifrado AES-256-GCM server-side + Argon2id KDF. SHA-256 hash chain audit. RGPD Art.20 export. Cursor-based pagination. |
| 2026-02-20 | 60.0.0 | **Secure Messaging Plan (Doc 178):** Plan de implementacion para `jaraba_messaging`. 64+ archivos planificados en 6 sprints. |
| 2026-02-20 | 59.0.0 | **ServiciosConecta Sprint S3 — Booking Engine Operativo:** Fix critico de `createBooking()` API (field mapping booking_date/offering_id/uid, validaciones provider activo+aprobado, offering ownership, advance_booking_min, client data, price, meeting_url Jitsi). Implementacion de `isSlotAvailable()`, `markSlotBooked()` y `hasCollision()` (refactored) en AvailabilityService. Fix `updateBooking()` state machine (cancelled_client/cancelled_provider, role enforcement provider-only para confirm/complete/no_show). Fix cron reminder duplicates (flags reminder_24h_sent/reminder_1h_sent). Fix hook_entity_update (booking_date, getOwnerId, cancelled_ prefix). 3 archivos modificados, 0 nuevos. |
| 2026-02-20 | 58.0.0 | **Vertical Retention Playbooks (Doc 179):** Implementacion completa del motor de retencion verticalizado en `jaraba_customer_success`. 2 entidades nuevas (VerticalRetentionProfile con 16 campos JSON, SeasonalChurnPrediction append-only), 2 servicios (VerticalRetentionService con evaluacion estacional, SeasonalChurnService con predicciones ajustadas), 7 endpoints API REST, dashboard FOC con heatmap, 5 perfiles verticales con calendarios de 12 meses, QueueWorker cron. 25 archivos nuevos + 11 modificados. |
| 2026-02-20 | 57.0.0 | **Page Builder Preview Audit:** Auditoría completa de los 4 escenarios del Page Builder (Biblioteca, Canvas Editor, Canvas Insert, Página Pública). 66 imágenes de preview premium glassmorphism 3D generadas y desplegadas para 6 verticales (AgroConecta 11, ComercioConecta 11, Empleabilidad 11, Emprendimiento 11, ServiciosConecta 11, JarabaLex 11). Inventario: 219 bloques, 31 categorías, 4 duplicados detectados. |
| 2026-02-20 | 56.0.0 | **Gemini Remediation:** Auditoria y correccion de ~40 archivos. Restauracion de seguridad CSRF en APIs Copilot (patron `_csrf_request_header_token`), fix XSS en Twig (`\|safe_html`), PWA meta tags duales, TranslatableMarkup cast, role check granular, email XSS escape. Stack de seguridad API reforzado. |
| 2026-02-18 | 55.0.0 | **Page Builder Template Consistency:** 129 templates resynced con preview_image, metadatos corregidos, preview_data rico para 55 verticales. Pipelines Canvas Editor y Template Picker unificados (status filter, icon keys, default category). Update hook 9006 aplicado. Fix de `applyUpdates()` eliminado en Drupal 10+ para Legal Intelligence. |
| 2026-02-18 | 54.0.0 | **CI/CD Hardening:** Corrección de trivy.yaml (claves `scan.skip-dirs`), deploy resiliente con fallback SSH. Security Scan y Deploy en verde. |
| 2026-02-18 | 53.0.0 | **The Unified & Stabilized SaaS:** Consolidación final de las 5 fases. Implementación del Stack de Cumplimiento Fiscal N1. Estabilización masiva de 370+ tests unitarios. |
| 2026-02-18 | 52.0.0 | **The Living SaaS:** Lanzamiento de los Bloques O y P. Inteligencia ZKP con Privacidad Diferencial e Interfaz Adaptativa (Ambient UX). |

| 2026-03-17 | 125.0.0 | **Stripe Checkout E2E Operativo:** 7 bugs P0-P2 corregidos (colisión rutas Commerce, doble /v1/ en URLs, CSP script-src, metadata mismatch, processRegistration incompatible, catch Exception→Throwable). Rutas movidas a /planes/checkout/*. 17 Products + Prices sincronizados. Checkout Embedded con body limpio. 9 email templates transaccionales (trial, payment_failed, cancelled + 6 dunning). Pricing CTAs actualizados. |

> **Versión:** 99.0.0 | **Fecha:** 2026-03-04 | **Autor:** IA Asistente
