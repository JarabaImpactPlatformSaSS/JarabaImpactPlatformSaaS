# Arquitectura de Alto Nivel - JarabaImpactPlatformSaaS

**Fecha de creación:** 2026-01-09 19:02  
**Última actualización:** 2026-01-09 19:55  
**Autor:** IA Asistente (Arquitecto SaaS Senior)  
**Versión:** 1.2.0  
**Categoría:** Arquitectura

---

## 📑 Tabla de Contenidos (TOC)

1. [Visión General](#1-visión-general)
2. [Diagrama de Contexto (C4 Level 1)](#2-diagrama-de-contexto-c4-level-1)
3. [Diagrama de Contenedores (C4 Level 2)](#3-diagrama-de-contenedores-c4-level-2)
4. [Diagrama de Componentes (C4 Level 3)](#4-diagrama-de-componentes-c4-level-3)
5. [Modelo de Datos](#5-modelo-de-datos)
6. [Flujo de Datos](#6-flujo-de-datos)
7. [Integraciones Externas](#7-integraciones-externas)
8. [Estrategia Multi-tenant](#8-estrategia-multi-tenant)
9. [Decisiones Arquitectónicas (ADRs)](#9-decisiones-arquitectónicas-adrs)
10. [Consideraciones de Escalabilidad](#10-consideraciones-de-escalabilidad)
11. [Registro de Cambios](#11-registro-de-cambios)

---

## 1. Visión General

### 1.1 Propósito del Sistema

**JarabaImpactPlatformSaaS** es una plataforma multi-tenant que permite a organizaciones (Tenants) gestionar ecosistemas de productores locales con capacidades de:

- **E-commerce**: Tiendas embebidas vía Ecwid
- **Trazabilidad**: Seguimiento de productos desde origen
- **Certificación Digital**: Firma electrónica con FNMT/AutoFirma
- **Inteligencia Artificial**: Agentes para marketing, storytelling, experiencia de cliente
- **Personalización Visual**: Theming dinámico por Tenant

### 1.2 Principios Arquitectónicos

| Principio | Descripción |
|-----------|-------------|
| **Multi-tenancy** | Aislamiento de datos y configuración por Tenant |
| **Extensibilidad** | Arquitectura modular basada en servicios Drupal |
| **Integración** | APIs externas como ciudadanos de primera clase |
| **Seguridad** | Roles granulares, validación estricta, credenciales externalizadas |
| **Experiencia de Usuario** | Interfaz unificada con personalización por Tenant |

### 1.3 Stakeholders

| Stakeholder | Interés Principal |
|-------------|-------------------|
| **Administrador de Plataforma** | Gestión global, onboarding de Tenants |
| **Gestor de Tenant** | Administración de productores de su organización |
| **Productor** | Gestión de tienda, productos, pedidos |
| **Cliente Final** | Compra de productos, trazabilidad |
| **Equipo de Desarrollo** | Mantenibilidad, extensibilidad |

---

## 2. Diagrama de Contexto (C4 Level 1)

Este diagrama muestra el sistema como una caja negra y sus interacciones con usuarios y sistemas externos.

```mermaid
graph TB
    subgraph usuarios["👥 Usuarios"]
        ADMIN["👤 Administrador<br/><small>Gestiona plataforma global</small>"]
        GESTOR["👤 Gestor de Sede<br/><small>Administra su organización</small>"]
        PRODUCTOR["👤 Productor<br/><small>Gestiona su tienda</small>"]
        CLIENTE["👤 Cliente<br/><small>Compra productos</small>"]
    end
    
    subgraph platform["🏢 JarabaImpactPlatformSaaS"]
        SISTEMA["Plataforma SaaS<br/><small>Drupal 11 Single-Instance + Group</small><br/><small>Gestión de ecosistemas de productores</small>"]
    end
    
    subgraph externos["🔌 Sistemas Externos"]
        ECWID["🛒 Ecwid<br/><small>E-commerce Platform</small>"]
        FNMT["📜 FNMT/AutoFirma<br/><small>Firma Digital</small>"]
        AI_APIS["🤖 APIs de IA<br/><small>OpenAI, Anthropic, Google</small>"]
        BLOCKCHAIN["⛓️ Blockchain<br/><small>Trazabilidad (futuro)</small>"]
        EMAIL["📧 Servidor Email<br/><small>Notificaciones</small>"]
    end
    
    ADMIN -->|"Configura sedes, planes"| SISTEMA
    GESTOR -->|"Gestiona productores"| SISTEMA
    PRODUCTOR -->|"Gestiona productos, ve pedidos"| SISTEMA
    CLIENTE -->|"Navega, compra, consulta trazabilidad"| SISTEMA
    
    SISTEMA <-->|"API REST: productos, pedidos, SSO"| ECWID
    SISTEMA <-->|"Firma de certificados"| FNMT
    SISTEMA -->|"Generación de contenido"| AI_APIS
    SISTEMA -.->|"Hash de trazabilidad (futuro)"| BLOCKCHAIN
    SISTEMA -->|"Emails transaccionales"| EMAIL
```

### 2.1 Descripción de Interacciones

| Origen | Destino | Descripción |
|--------|---------|-------------|
| Admin → Sistema | HTTP/Browser | Configuración global, gestión de Sedes |
| Gestor → Sistema | HTTP/Browser | Alta de productores, reportes |
| Productor → Sistema | HTTP/Browser | Panel de control, gestión de tienda |
| Cliente → Sistema | HTTP/Browser | Navegación, compras, consultas |
| Sistema ↔ Ecwid | REST API | Sincronización de productos, pedidos, SSO |
| Sistema ↔ FNMT | Certificados X.509 | Firma de lotes y certificados |
| Sistema → IA | REST API | Generación de contenido, respuestas |
| Sistema → Email | SMTP | Notificaciones, confirmaciones |

---

## 3. Diagrama de Contenedores (C4 Level 2)

Este diagrama descompone el sistema en contenedores de alto nivel (aplicaciones, almacenes de datos).

```mermaid
graph TB
    subgraph browser["🌐 Navegador del Usuario"]
        WEB_APP["📱 Aplicación Web<br/><small>Tema Drupal + Ecwid Widget</small>"]
    end
    
    subgraph platform["🏢 Plataforma SaaS"]
        subgraph drupal["Drupal 11 Single-Instance"]
            CMS["📄 CMS Core<br/><small>Gestión de contenido</small>"]
            API["🔌 API Layer<br/><small>REST endpoints</small>"]
            SERVICES["⚙️ Backend Services<br/><small>Lógica de negocio</small>"]
            AGENTS["🤖 AI Agents Module<br/><small>Orquestador + Agentes</small>"]
            THEME["🎨 Theme Engine<br/><small>CSS Variables por Tenant</small>"]
        end
        
        subgraph data["💾 Capa de Datos (Single-Instance)"]
            DB_SINGLE[("🗄️ BD Única MySQL<br/><small>Aislamiento por Group</small>")]
            GROUPS["👥 Groups<br/><small>Verticales + Tenants</small>"]
            FILES["📁 File Storage<br/><small>Archivos por Group</small>"]
        end
    end
    
    subgraph external["🔌 Servicios Externos"]
        ECWID_API["🛒 Ecwid API"]
        AUTOFIRMA["📜 AutoFirma"]
        LLM_API["🤖 LLM APIs"]
    end
    
    WEB_APP -->|"HTTPS"| CMS
    WEB_APP -->|"HTTPS"| API
    WEB_APP -->|"HTTPS (iframe)"| ECWID_API
    
    CMS --> SERVICES
    API --> SERVICES
    SERVICES --> AGENTS
    CMS --> THEME
    
    SERVICES --> DB_SINGLE
    SERVICES --> GROUPS
    SERVICES --> FILES
    
    SERVICES <-->|"REST"| ECWID_API
    SERVICES <-->|"Local/WebService"| AUTOFIRMA
    AGENTS -->|"REST"| LLM_API
```

### 3.1 Descripción de Contenedores

| Contenedor | Tecnología | Responsabilidad |
|------------|------------|-----------------|
| **CMS Core** | Drupal 11 | Gestión de contenido, entidades, usuarios |
| **API Layer** | Drupal REST | Endpoints para frontend y widgets |
| **Backend Services** | PHP Services | Lógica de negocio, orquestación |
| **AI Agents Module** | Custom Module | Integración con LLMs, agentes especializados |
| **Theme Engine** | Twig + CSS | Renderizado con variables por Tenant |
| **BD Única** | MySQL | Todos los datos, aislamiento por Group |
| **Groups** | Group Module | Verticales y Tenants como Groups |
| **File Storage** | Sistema de archivos | Uploads con control de acceso por Group |

---

## 4. Diagrama de Componentes (C4 Level 3)

Detalle de los componentes dentro del módulo de Backend Services.

```mermaid
graph TB
    subgraph services["⚙️ Backend Services"]
        subgraph core["Core Services"]
            SEDE_MGR["🏛️ SedeManager<br/><small>CRUD de Sedes</small><br/><small>Negociación de tema</small>"]
            PLAN_MGR["📋 PlanManager<br/><small>Límites SaaS</small><br/><small>Verificación de cuotas</small>"]
        end
        
        subgraph producer["Producer Services"]
            PROD_MGR["👨‍🌾 ProducerManager<br/><small>CRUD Productores</small><br/><small>Vinculación con Ecwid</small>"]
            PRODUCT_SVC["📦 ProductService<br/><small>Sincronización productos</small>"]
            ORDER_SVC["🛒 OrderService<br/><small>Gestión de pedidos</small>"]
        end
        
        subgraph traceability["Traceability Services"]
            TRACE_SVC["📍 TrazabilidadService<br/><small>Registro de lotes</small><br/><small>Historial de origen</small>"]
            CERT_SVC["📜 CertificadoService<br/><small>Emisión de certificados</small><br/><small>Integración AutoFirma</small>"]
        end
        
        subgraph ai["AI Services"]
            AI_ORCH["🤖 AgentOrchestrator<br/><small>Routing de agentes</small><br/><small>Gestión de contexto</small>"]
            MARKETING_AGENT["📣 MarketingAgent<br/><small>Posts, emails, SEO</small>"]
            STORY_AGENT["📖 StorytellingAgent<br/><small>Bios, historias</small>"]
            CX_AGENT["💬 CustomerExperienceAgent<br/><small>Recomendaciones</small>"]
        end
        
        subgraph integration["Integration Services"]
            ECWID_SVC["🛒 EcwidService<br/><small>API wrapper</small><br/><small>SSO</small>"]
            AI_PROVIDER["🔌 MultiAiProviderService<br/><small>Abstracción de LLMs</small>"]
        end
    end
    
    SEDE_MGR --> PLAN_MGR
    PROD_MGR --> SEDE_MGR
    PROD_MGR --> ECWID_SVC
    PRODUCT_SVC --> ECWID_SVC
    ORDER_SVC --> ECWID_SVC
    
    TRACE_SVC --> PROD_MGR
    CERT_SVC --> TRACE_SVC
    
    AI_ORCH --> MARKETING_AGENT
    AI_ORCH --> STORY_AGENT
    AI_ORCH --> CX_AGENT
    MARKETING_AGENT --> AI_PROVIDER
    STORY_AGENT --> AI_PROVIDER
    CX_AGENT --> AI_PROVIDER
```

### 4.1 Descripción de Componentes Principales

| Componente | Archivo/Clase | Responsabilidad |
|------------|---------------|-----------------|
| **TenantManager** | `TenantManager.php` | CRUD de Tenants, negociación de tema por dominio |
| **PlanValidator** | `SaasPlan` Content Entity | Definición de límites por plan (productores, storage) |
| **ProducerManager** | `ProducerManager.php` | Alta/baja de productores, validación de cuotas |
| **EcwidService** | `EcwidService.php` | Wrapper para API Ecwid, SSO, sincronización |
| **TrazabilidadService** | `TrazabilidadService.php` | Registro de lotes, consulta de historial |
| **CertificadoService** | `CertificadoService.php` | Emisión y firma de certificados digitales |
| **AgentOrchestrator** | `AgentOrchestrator.php` | Routing de peticiones a agentes especializados |
| **MultiAiProviderService** | `MultiAiProviderService.php` | Abstracción de OpenAI, Anthropic, Google |

---

## 5. Modelo de Datos

### 5.1 Diagrama Entidad-Relación

```mermaid
erDiagram
    VERTICAL ||--o{ TENANT : contiene
    VERTICAL ||--o{ PLAN_SAAS : ofrece
    
    TENANT ||--o{ PRODUCTOR : tiene
    TENANT ||--|| PLAN_SAAS : suscrito_a
    TENANT ||--|| THEME_CONFIG : personaliza
    
    PRODUCTOR ||--o{ PRODUCTO : vende
    PRODUCTOR ||--|| TIENDA_ECWID : vinculado_a
    
    PRODUCTO ||--o{ LOTE : tiene
    PRODUCTO ||--o{ PEDIDO_LINEA : incluido_en
    
    LOTE ||--o{ CERTIFICADO : certificado_por
    
    PEDIDO ||--o{ PEDIDO_LINEA : contiene
    PEDIDO }o--|| CLIENTE : realizado_por
    
    VERTICAL {
        int id PK
        string name
        string machine_name UK
        text description
        json theme_settings
        list enabled_features
    }
    
    TENANT {
        int id PK
        int vertical_id FK
        string name
        string domain
        int plan_id FK
        string subscription_status
        string stripe_customer_id
        string stripe_connect_id
        datetime trial_ends
        datetime current_period_end
    }
    
    PLAN_SAAS {
        int id PK
        int vertical_id FK
        string name
        decimal price_monthly
        decimal price_yearly
        json limits
        list features
        string stripe_price_id
    }
    
    THEME_CONFIG {
        int id PK
        int tenant_id FK
        string color_primario
        string color_secundario
        string tipografia
        string logo_url
        json css_custom
    }
    
    PRODUCTOR {
        int id PK
        int tenant_id FK
        string nombre
        string email
        string telefono
        int ecwid_store_id
        string estado
        datetime created
    }
    
    PRODUCTO {
        int id PK
        int productor_id FK
        string nombre
        text descripcion
        decimal precio
        int stock
        int ecwid_product_id
        boolean activo
    }
    
    LOTE {
        int id PK
        int producto_id FK
        string codigo
        date fecha_produccion
        string origen
        json metadata
        string hash_blockchain
    }
    
    CERTIFICADO {
        int id PK
        int lote_id FK
        string tipo
        blob firma_digital
        datetime emitido
        datetime validez
    }
```

### 5.2 Descripción de Entidades

| Entidad | Descripción | Ubicación |
|---------|-------------|-----------|
| **Ecosistema** | Contenedor raíz, agrupa todas las Sedes | BD Principal |
| **Sede** | Tenant/organización con su configuración | BD Principal + BD propia |
| **Plan SaaS** | Configuración de límites y features | Config Entity Drupal |
| **Config Tema** | Variables visuales de la Sede | BD de Sede |
| **Productor** | Usuario vendedor con tienda | BD de Sede |
| **Producto** | Artículo a la venta | BD de Sede + Ecwid |
| **Lote** | Unidad de trazabilidad | BD de Sede |
| **Certificado** | Documento firmado digitalmente | BD de Sede |

---

## 6. Flujo de Datos

### 6.1 Flujo: Alta de Nueva Sede

```mermaid
sequenceDiagram
    participant Admin
    participant Platform as Plataforma SaaS
    participant DB as Base de Datos
    participant Ecwid
    
    Admin->>Platform: Crear nueva Sede (nombre, plan, dominio)
    Platform->>Platform: Validar datos
    Platform->>DB: Crear registro Sede
    Platform->>DB: Crear BD dedicada (multisite)
    Platform->>Platform: Configurar tema por defecto
    Platform->>Ecwid: Crear cuenta Ecwid (API)
    Ecwid-->>Platform: Credenciales de tienda
    Platform->>DB: Guardar credenciales encriptadas
    Platform-->>Admin: Sede creada ✓
```

### 6.2 Flujo: Sincronización de Producto

```mermaid
sequenceDiagram
    participant Productor
    participant Drupal
    participant EcwidSvc as EcwidService
    participant Ecwid as Ecwid API
    
    Productor->>Drupal: Crear/Editar producto
    Drupal->>Drupal: Validar datos
    Drupal->>EcwidSvc: syncProduct(data)
    EcwidSvc->>Ecwid: POST/PUT /products
    Ecwid-->>EcwidSvc: ecwid_product_id
    EcwidSvc-->>Drupal: Producto sincronizado
    Drupal->>Drupal: Guardar en BD local
    Drupal-->>Productor: Producto guardado ✓
```

### 6.3 Flujo: Generación de Contenido IA

```mermaid
sequenceDiagram
    participant User as Usuario
    participant UI as Interfaz
    participant API as API Drupal
    participant Orch as AgentOrchestrator
    participant Agent as MarketingAgent
    participant LLM as API LLM
    
    User->>UI: Solicitar post para redes
    UI->>API: POST /api/agent/action
    API->>Orch: routeRequest(action, context)
    Orch->>Agent: execute(params)
    Agent->>Agent: Construir prompt
    Agent->>LLM: Enviar prompt
    LLM-->>Agent: Respuesta generada
    Agent->>Agent: Formatear respuesta
    Agent-->>Orch: Contenido estructurado
    Orch-->>API: Resultado
    API-->>UI: JSON response
    UI-->>User: Mostrar contenido
```

---

## 7. Integraciones Externas

### 7.1 Ecwid (E-commerce)

```mermaid
graph LR
    subgraph drupal["Drupal"]
        ECWID_SVC["EcwidService"]
        PROD_MGR["ProducerManager"]
    end
    
    subgraph ecwid["Ecwid"]
        STORE_API["Store API"]
        PRODUCT_API["Product API"]
        ORDER_API["Order API"]
        SSO_API["SSO API"]
    end
    
    ECWID_SVC <-->|"GET/POST productos"| PRODUCT_API
    ECWID_SVC <-->|"GET pedidos"| ORDER_API
    PROD_MGR -->|"Embed dashboard"| SSO_API
```

| Operación | Endpoint Ecwid | Frecuencia |
|-----------|----------------|------------|
| Crear producto | `POST /products` | On-demand |
| Actualizar producto | `PUT /products/{id}` | On-demand |
| Obtener pedidos | `GET /orders` | Polling/Webhook |
| SSO Panel | `GET /sso` | On-demand |

### 7.2 FNMT/AutoFirma (Firma Digital)

```mermaid
graph LR
    subgraph drupal["Drupal"]
        CERT_SVC["CertificadoService"]
    end
    
    subgraph firma["AutoFirma"]
        AFIRMA["Cliente AutoFirma"]
        FNMT["Certificado FNMT"]
    end
    
    CERT_SVC -->|"Documento a firmar"| AFIRMA
    AFIRMA -->|"Firma con cert"| FNMT
    FNMT -->|"Documento firmado"| AFIRMA
    AFIRMA -->|"Resultado"| CERT_SVC
```

### 7.3 APIs de Inteligencia Artificial

```mermaid
graph TB
    subgraph agents["Agentes IA"]
        ORCH["AgentOrchestrator"]
        MA["MarketingAgent"]
        SA["StorytellingAgent"]
        CXA["CustomerExperienceAgent"]
    end
    
    subgraph provider["MultiAiProviderService"]
        SELECTOR["Provider Selector"]
    end
    
    subgraph llms["LLM APIs"]
        OPENAI["OpenAI"]
        ANTHROPIC["Anthropic"]
        GOOGLE["Google AI"]
    end
    
    ORCH --> MA & SA & CXA
    MA & SA & CXA --> SELECTOR
    SELECTOR -->|"Según config"| OPENAI
    SELECTOR -->|"Según config"| ANTHROPIC
    SELECTOR -->|"Según config"| GOOGLE
```

---

## 8. Estrategia Multi-tenant

### 8.1 Modelo de Aislamiento

```mermaid
graph TB
    subgraph shared["Recursos Compartidos"]
        CODE["📦 Código base<br/><small>Módulos Drupal</small>"]
        CONFIG["⚙️ Config compartida<br/><small>Planes, APIs</small>"]
        CACHE["💨 Caché<br/><small>Redis (namespace)</small>"]
    end
    
    subgraph isolated["Recursos Aislados por Sede"]
        subgraph sede1["Sede: AgroConecta"]
            DB1[("BD")]
            FILES1["📁 Files"]
            THEME1["🎨 Tema"]
        end
        
        subgraph sede2["Sede: PepeJaraba"]
            DB2[("BD")]
            FILES2["📁 Files"]
            THEME2["🎨 Tema"]
        end
    end
    
    CODE --> sede1 & sede2
    CONFIG --> sede1 & sede2
```

### 8.2 Resolución de Tenant

```mermaid
flowchart TD
    A[Request entrante] --> B{¿Dominio conocido?}
    B -->|Sí| C[SedeThemeNegotiator]
    B -->|No| D[Sede por defecto]
    C --> E[Cargar config de Sede]
    E --> F[Inyectar variables CSS]
    F --> G[Renderizar con tema]
```

### 8.3 Límites por Plan

| Plan | Productores | Storage | Agentes IA | Trazabilidad | Firma Digital |
|------|-------------|---------|------------|--------------|---------------|
| **Básico** | 10 | 5 GB | ❌ | ❌ | ❌ |
| **Profesional** | 50 | 25 GB | ✅ Limitada | ✅ | ❌ |
| **Enterprise** | Ilimitado | 100 GB | ✅ Completa | ✅ | ✅ |

---

## 9. Decisiones Arquitectónicas (ADRs)

### ADR-001: Single-Instance + Group vs Multisite

| Aspecto | Decisión |
|---------|----------|
| **Contexto** | Necesitamos aislamiento de datos entre Tenants |
| **Decisión** | Single-Instance con Group Module + Domain Access |
| **Razón** | Efecto red (queries cruzadas), 1 actualización de core, escalabilidad |
| **Consecuencias** | Requiere auditoría de permisos, tests de aislamiento |

### ADR-002: Ecwid como Motor de E-commerce

| Aspecto | Decisión |
|---------|----------|
| **Contexto** | Necesitamos capacidades de e-commerce completas |
| **Decisión** | Integrar Ecwid en lugar de Drupal Commerce |
| **Razón** | Menor desarrollo, PCI compliance, panel nativo para productores |
| **Consecuencias** | Dependencia externa, costes por transacción, sincronización |

### ADR-003: Abstracción de Proveedores IA

| Aspecto | Decisión |
|---------|----------|
| **Contexto** | Múltiples proveedores de LLM con diferentes APIs |
| **Decisión** | MultiAiProviderService como capa de abstracción |
| **Razón** | Flexibilidad, fallback, optimización de costes |
| **Consecuencias** | Complejidad de abstracción, mínimo común denominador |

---

## 10. Consideraciones de Escalabilidad

### 10.1 Puntos de Escalado

| Componente | Estrategia | Trigger |
|------------|------------|---------|
| **Web/App** | Horizontal (load balancer) | CPU > 70% |
| **Base de datos** | Read replicas | Queries > 1000/s |
| **Archivos** | CDN + Object Storage | Storage > 80% |
| **Caché** | Redis Cluster | Hit rate < 80% |
| **IA** | Rate limiting + cola | Latencia > 5s |

### 10.2 Cuellos de Botella Identificados

1. **Sincronización Ecwid**: Rate limits de API
2. **Generación IA**: Latencia de LLMs externos
3. **Firma Digital**: Dependencia de cliente local

### 10.3 Estrategias de Mitigación

- **Colas**: Procesar sincronizaciones en background
- **Caché**: Cachear respuestas IA por contexto similar
- **Batch**: Agrupar operaciones de firma

---

## 11. Registro de Cambios

| Fecha | Versión | Autor | Descripción |
|-------|---------|-------|-------------|
| 2026-01-09 | 1.0.0 | IA Asistente | Creación inicial del documento de arquitectura |
| 2026-01-09 | 1.1.0 | IA Asistente | Alineado con Doc. Maestro: Single-Instance + Group, Drupal 11 |
| 2026-01-09 | 1.2.0 | IA Asistente | Correcciones de coherencia: Sede→Tenant, ERD actualizado, C4 L2 corregido |
