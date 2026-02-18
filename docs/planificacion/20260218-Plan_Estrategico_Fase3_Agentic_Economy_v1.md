# 🚀 Plan Estratégico Fase 3: La Economía Agéntica (The Agentic Economy)

**Fecha de creación:** 2026-02-18
**Estado:** Planificación Estratégica (Roadmap 2027)
**Versión:** 1.0.0
**Alcance:** Bloques M (Identidad Soberana) y N (Mercado de Agentes)

---

## 📑 Tabla de Contenidos (TOC)

1. [Visión y Contexto Estratégico](#1-visión-y-contexto-estratégico)
2. [Arquitectura Técnica (The "How")](#2-arquitectura-técnica-the-how)
3. [Bloque M: Infraestructura de Identidad Soberana (DID)](#3-bloque-m-infraestructura-de-identidad-soberana-did)
4. [Bloque N: El Mercado de Agentes (Autonomous Economy)](#4-bloque-n-el-mercado-de-agentes-autonomous-economy)
5. [Frontend y UX de Clase Mundial](#5-frontend-y-ux-de-clase-mundial)
6. [Seguridad y Compliance (SOC2 + ZKP)](#6-seguridad-y-compliance-soc2--zkp)
7. [Fases de Implementación (Roadmap 2027)](#7-fases-de-implementación-roadmap-2027)
8. [Tabla de Correspondencia de Especificaciones](#8-tabla-de-correspondencia-de-especificaciones)
9. [Cumplimiento de Directrices del Proyecto](#9-cumplimiento-de-directrices-del-proyecto)

---

## 1. Visión y Contexto Estratégico

### 1.1 De la Eficiencia a la Autonomía
Hemos construido un "Ferrari tecnológico" (SaaS Golden Master) que optimiza la gestión humana. La siguiente frontera no es hacer al humano más rápido, sino **eliminar la fricción de la transacción humana**.

### 1.2 La Economía Hiper-Agéntica
La visión para 2027 es transformar Jaraba Impact Platform en el **Sistema Operativo de la Economía Local**.
*   **Gemelos Digitales (Digital Twins):** Agentes persistentes que representan los intereses del usuario (Productor, Consumidor, Candidato) 24/7.
*   **Negociación Autónoma:** Los agentes negocian, acuerdan y cierran tratos (compras, contrataciones) basados en parámetros predefinidos, sin intervención humana directa hasta la firma final o incluso automatizándola.

---

## 2. Arquitectura Técnica (The "How")

### 2.1 Nuevos Módulos Core
Se crearán dos nuevos módulos en `web/modules/custom/`:
*   `jaraba_identity` (Bloque M)
*   `jaraba_agent_market` (Bloque N)

### 2.2 Protocolo JDTP (Jaraba Digital Twin Protocol)
Protocolo de comunicación estándar basado en JSON-LD y WebSockets para la negociación entre agentes.

```json
{
  "@context": "https://jaraba.io/contexts/negotiation/v1",
  "type": "Offer",
  "actor": "did:jaraba:producer:12345",
  "target": "did:jaraba:consumer:67890",
  "payload": {
    "product": "sku_tomate_rosa",
    "quantity": 100,
    "price": { "amount": 2.50, "currency": "EUR" },
    "terms": "shipping_included"
  },
  "signature": "ed25519_signature_..."
}
```

---

## 3. Bloque M: Infraestructura de Identidad Soberana (DID)

### 3.1 Objetivo
Pasar de usuarios locales (`uid`) a **Identidades Descentralizadas (DID)** portables. La reputación de un productor en AgroConecta debe servirle para pedir crédito en JarabaFintech.

### 3.2 Entidades de Contenido (`jaraba_identity`)
*   **`IdentityWallet`**: Contenedor seguro de claves y credenciales.
    *   Campos: `did` (string, único), `public_key` (text), `status` (list: active, suspended).
*   **`VerifiableCredential`**: Credencial emitida firmada criptográficamente.
    *   Campos: `issuer_did`, `holder_did`, `claims` (json), `proof` (json).

### 3.3 Servicios Clave
*   **`DidResolverService`**: Resuelve DIDs a documentos DID (W3C Standard).
*   **`CredentialIssuerService`**: Emite credenciales verificables basadas en logros del SaaS (ej: "Productor Verificado Nivel 5").
*   **`ZeroKnowledgeProofService`**: Permite probar atributos (ej: "Tengo > 18 años" o "Solvencia > X") sin revelar el dato exacto.

---

## 4. Bloque N: El Mercado de Agentes (Autonomous Economy)

### 4.1 Objetivo
Crear una "Sala de Negociación" virtual donde los Gemelos Digitales interactúan.

### 4.2 Entidades de Contenido (`jaraba_agent_market`)
*   **`DigitalTwin`**: Configuración del agente del usuario.
    *   Campos: `uid` (entity_reference), `strategy` (json: agresiva, conservadora), `budget` (decimal), `interests` (taxonomy_reference).
*   **`NegotiationSession`**: Registro de una negociación viva.
    *   Campos: `initiator_twin`, `responder_twin`, `status` (open, closed, failed), `ledger` (json: historial de ofertas).
*   **`SmartContract`**: Acuerdo final inmutable.
    *   Campos: `terms` (text), `signatures` (json), `execution_trigger` (ej: Stripe Payment Intent).

### 4.3 Servicios Clave
*   **`TwinOrchestratorService`**: Gestiona el ciclo de vida de los gemelos (despertar, negociar, dormir).
*   **`IntentMatchingEngine`**: Motor de Redis Pub/Sub que empareja `Ask` (Oferta) con `Bid` (Demanda) en tiempo real.
*   **`NegotiationProtocolService`**: Implementa la máquina de estados de la negociación (Offer -> Counter-Offer -> Accept/Reject).

---

## 5. Frontend y UX de Clase Mundial

### 5.1 Dashboard del Gemelo Digital
*   **Ruta**: `/agent/dashboard`
*   **Template**: `page--agent-dashboard.html.twig` (Zero-Region, Full-Width).
*   **Diseño**: Estilo "Centro de Mando Futurista". Uso de modo oscuro por defecto o adaptativo.
*   **Componentes (Partials)**:
    *   `_twin-status.html.twig`: Estado del agente (Activo/Negociando/Dormido).
    *   `_active-negotiations.html.twig`: Lista de tratos en curso con indicadores de probabilidad de éxito.
    *   `_market-pulse.html.twig`: Gráfico de tiempo real de demanda/oferta (Canvas API).

### 5.2 GrapesJS Blocks
*   **Twin Status Widget**: Bloque arrastrable para que los tenants muestren el estado de sus agentes en sus portales.
*   **Market Ticker**: Cinta de "últimos tratos cerrados" anónima.

### 5.3 Directrices de Implementación Frontend
*   **SCSS**: Uso estricto de `var(--ej-*)`. Nuevos tokens para el dashboard de agentes definidos en `ecosistema_jaraba_core`.
    *   `_agent-dashboard.scss` en el módulo.
*   **Modales**: Todas las configuraciones de estrategia del agente se abren en modales (`data-dialog-type="modal"`).
*   **JS**: `Drupal.behaviors.agentDashboard` usando `once()` y `fetch` para actualizaciones en tiempo real (SSE/WebSockets).

---

## 6. Seguridad y Compliance (SOC2 + ZKP)

### 6.1 Privacidad por Diseño
*   Las negociaciones ocurren en canales cifrados.
*   **ZKP (Zero-Knowledge Proofs)**: Un agente puede probar que tiene fondos suficientes para una compra sin revelar su saldo total.

### 6.2 Auditoría SOC2
*   Cada `NegotiationSession` genera un rastro de auditoría inmutable en el `AuditLog` existente, extendido para incluir firmas criptográficas de cada paso de la negociación.

---

## 7. Fases de Implementación (Roadmap 2027)

| Fase | Trimestre | Entregable Principal |
|------|-----------|----------------------|
| **Fase 3.1** | Q1 2027 | **Infraestructura DID**: Módulo `jaraba_identity`, emisión de credenciales básicas. |
| **Fase 3.2** | Q2 2027 | **Gemelos Digitales**: Módulo `jaraba_agent_market`, configuración de agentes, Dashboard UI. |
| **Fase 3.3** | Q3 2027 | **Motor de Negociación**: Protocolo JDTP, Matching Engine en Redis, primeras transacciones piloto. |
| **Fase 3.4** | Q4 2027 | **Economía Autónoma**: Apertura del mercado, APIs públicas para agentes externos, integración total con Stripe. |

---

## 8. Tabla de Correspondencia de Especificaciones

| Componente | Especificación Técnica | Directriz Aplicada |
|------------|------------------------|--------------------|
| **Módulos** | `jaraba_identity`, `jaraba_agent_market` | Módulos custom en `web/modules/custom/`, `declare(strict_types=1)` |
| **Entidades** | `DigitalTwin`, `NegotiationSession`... | Content Entities con Field UI, Views, AccessHandlers |
| **Frontend** | `page--agent-dashboard.html.twig` | Zero-Region, `hook_preprocess_page`, no `page.content` |
| **Estilos** | `_agent-dashboard.scss` | Dart Sass, `color-mix`, `var(--ej-*)`, Mobile-First |
| **Iconos** | `jaraba_icon('ai', 'robot')` | Sistema de iconos SVG centralizado, sin emojis |
| **Seguridad** | `AuditLog` integration | HMAC, Permisos granulares, Sanitización |
| **Config** | Config Entities para Estrategias | Configuración via UI, no hardcode |

---

## 9. Cumplimiento de Directrices del Proyecto

### 9.1 i18n y Textos
*   Todo el código PHP utilizará `$this->t()` o `new TranslatableMarkup()`.
*   Las plantillas Twig usarán `{{ 'Texto'|t }}`.
*   Los strings JS usarán `Drupal.t()`.

### 9.2 SCSS y Theming
*   Archivos SCSS en `scss/` del módulo, compilados a `css/` con `npx sass`.
*   Uso exclusivo de variables inyectables `var(--ej-*)`.
*   Definición de nuevos tokens en `ecosistema_jaraba_core` si es necesario.

### 9.3 Plantillas Limpias
*   Uso de `hook_theme_suggestions_page_alter` para asignar `page--agent-dashboard`.
*   Template base limpio sin regiones heredadas.
*   Inyección de variables mediante `hook_preprocess_page`.
*   Clases de body inyectadas mediante `hook_preprocess_html`.

### 9.4 Navegación Admin
*   Entradas de menú en `jaraba_agent_market.links.menu.yml`:
    *   `/admin/content/agents` (Gestión de Gemelos).
    *   `/admin/structure/agent-strategies` (Configuración de Estrategias).

### 9.5 Verificación Docker
*   Todos los comandos de generación de código, limpieza de caché (`drush cr`) y compilación de assets se ejecutarán dentro del contenedor Docker (`lando ssh`).

---

> **Nota:** Este documento establece la hoja de ruta para la próxima gran evolución del SaaS. Su implementación debe seguir estrictamente las directrices de calidad y arquitectura definidas en el proyecto.
