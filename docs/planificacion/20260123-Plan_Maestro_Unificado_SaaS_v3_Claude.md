# Plan Maestro Unificado SaaS v3.0
## Jaraba Impact Platform - Roadmap 2026-2027

**Fecha de creación:** 2026-01-23 08:38  
**Última actualización:** 2026-01-23 20:50  
**Autor:** IA Asistente (Claude/Antigravity)  
**Versión:** 3.0.0

---

## 📋 Documentos de Implementación por Bloque

> **Cada bloque tiene un documento detallado con matriz de specs, checklists y pasos:**

| Bloque | Documento de Implementación | Horas |
|--------|----------------------------|-------|
| A | [20260123a-Bloque_A_Gaps_Auditoria_Implementacion_Claude.md](../implementacion/20260123a-Bloque_A_Gaps_Auditoria_Implementacion_Claude.md) | 1,690h |
| B | [20260123b-Bloque_B_Copiloto_v3_Implementacion_Claude.md](../implementacion/20260123b-Bloque_B_Copiloto_v3_Implementacion_Claude.md) | 96h |
| C | [20260123c-Bloque_C_Journey_Engine_Implementacion_Claude.md](../implementacion/20260123c-Bloque_C_Journey_Engine_Implementacion_Claude.md) | 530h |
| D | [20260123d-Bloque_D_Admin_Center_Implementacion_Claude.md](../implementacion/20260123d-Bloque_D_Admin_Center_Implementacion_Claude.md) | 635h |
| E | [20260123e-Bloque_E_Training_System_Implementacion_Claude.md](../implementacion/20260123e-Bloque_E_Training_System_Implementacion_Claude.md) | 124h |
| F | [20260123f-Bloque_F_AI_Content_Hub_Implementacion_Claude.md](../implementacion/20260123f-Bloque_F_AI_Content_Hub_Implementacion_Claude.md) | 340-410h |
| G | [20260123g-Bloque_G_AI_Skills_Implementacion_Claude.md](../implementacion/20260123g-Bloque_G_AI_Skills_Implementacion_Claude.md) | 200-250h |

---

## 📑 Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Bloque A: Gaps Auditoría](#2-bloque-a-gaps-auditoría)
3. [Bloque B: Copiloto v3](#3-bloque-b-copiloto-emprendimiento-v3)
4. [Bloque C: Journey Engine](#4-bloque-c-journey-engine)
5. [Bloque D: Admin Center](#5-bloque-d-admin-center)
6. [Bloque E: Training System](#6-bloque-e-training--certification)
7. [Bloque F: AI Content Hub](#7-bloque-f-ai-content-hub)
8. [Bloque G: AI Skills System](#8-bloque-g-ai-skills-system)
9. [Timeline Integrado](#9-timeline-integrado)
10. [Criterios de Éxito](#10-criterios-de-éxito)
11. [Registro de Cambios](#11-registro-de-cambios)

---

## 1. Resumen Ejecutivo

> **Este plan unifica TODOS los componentes pendientes en un roadmap coherente de 24 meses (~4,500h).**

| Bloque | Descripción | Horas | Timeline |
|--------|-------------|-------|----------|
| **A. Gaps Auditoría** | SEPE, Frontend, Commerce, Marketing Stack | 1,690h | Q1-Q4 2026 |
| **B. Copiloto v3** | Hiperpersonalización Osterwalder/Blank | 96h | Q1-Q2 2026 |
| **C. Journey Engine** | 19 avatares, 7 estados, IA proactiva | 530h | Q2-Q4 2026 |
| **D. Admin Center** | Dashboard ejecutivo, 8 módulos | 635h | Q3 2026 - Q1 2027 |
| **E. Training System** | Escalera valor 6 peldaños, certificación | 124h | Q2-Q3 2026 |
| **F. AI Content Hub** | Blog, Newsletter, AI Writing | 340-410h | Q3-Q4 2026 |
| **G. AI Skills System** | Especialización continua agentes IA | 200-250h | Q4 2026 - Q1 2027 |
| **TOTAL** | | **~4,500h** | **~24 meses** |

### 1.1 Estrategia de Reuso (Directriz Obligatoria)

> ⚠️ **VERIFICACIÓN PREVIA OBLIGATORIA**: Antes de iniciar cualquier paso del roadmap, ejecutar el análisis de reuso.

#### A. Reuso Cross-Vertical (Verticales Ya Implementados)

Orden de implementación de verticales:
```
1. Empleabilidad (base) → 2. Emprendimiento → 3. AgroConecta → 4. ComercioConecta → 5. ServiciosConecta
```

**Componentes típicamente reutilizables entre verticales:**

| Componente | Módulo Origen | Reutilizable Para |
|------------|---------------|-------------------|
| Matching Engine | `jaraba_matching` | Todos los verticales |
| Copiloto Modos Base | `jaraba_copilot_v2` | Todos los verticales |
| Dashboard Patterns | `ecosistema_jaraba_core` | Todos los verticales |
| Commerce Core | `jaraba_commerce` | AgroConecta, ComercioConecta, ServiciosConecta |
| LMS/Training | `jaraba_lms` | Emprendimiento, ServiciosConecta |

#### B. Reuso AgroConecta Anterior (Proyecto Legacy)

> **IGNORAR** todo lo relacionado con **Ecwid** → usar Drupal Commerce + Stripe.

| Componente | Ruta Original | Aplicación SaaS |
|------------|---------------|-----------------|
| 8 Agentes IA | `z:/home/PED/AgroConecta/src/Agent/` | Adaptar a copilotos |
| SeasonCore | `Service/SeasonCore.php` | Estacionalidad productos |
| Brand Voice | Config | Personalización tenant |
| Workflows ECA | `config/install/eca.*` | Adaptar (no ECA UI) |

---

## 2. Bloque A: Gaps Auditoría

> **Referencia:** [Bloque A Implementation Doc](../implementacion/20260123a-Bloque_A_Gaps_Auditoria_Implementacion_Claude.md)

### A.1 Quick Wins + SEPE (P0) - Q1 2026

| Item | Sprints | Horas | Entregables |
|------|---------|-------|-------------|
| Quick Wins | 1-2 | 40h | llms.txt, PHPStan CI, PHPUnit críticos |
| SEPE Teleformación | 3-6 | 100h | Módulo jaraba_sepe, WSDL, Kit validación |
| **Subtotal** | | **140h** | |

### A.2 Frontend Premium (P1) - Q1-Q2 2026

| Item | Sprints | Horas | Entregables |
|------|---------|-------|-------------|
| Design Tokens | 7-8 | 32h | Cascada Plataforma→Vertical→Tenant |
| Component Library | 9-10 | 56h | 6 headers, 8 cards, 5 heroes |
| Visual Picker | 11-12 | 40h | tenant_theme_config, admin panel |
| Industry Presets | 13-14 | 32h | 15 presets por sector |
| Testing (Cypress) | paralelo | 40h | E2E básico |
| **Subtotal** | | **200h** | |

### A.3 AgroConecta Commerce (P1) - Q2-Q3 2026

| Item | Sprints | Horas | Entregables |
|------|---------|-------|-------------|
| Commerce Core | 15-18 | 80h | Entidades, Commerce 3.x |
| Pagos + Shipping | 19-22 | 80h | Stripe Connect, envíos |
| Portales + QR | 23-26 | 80h | Producer Portal, trazabilidad |
| Launch | 27-30 | 60h | k6, go-live MVP |
| **Subtotal** | | **300h** | |

### A.4 Expansión (P2-P3) - Q4 2026 - Q2 2027

| Item | Sprints | Horas |
|------|---------|-------|
| ComercioConecta | 42-53 | 300h |
| Platform Features (PWA full) | 54-59 | 200h |
| ServiciosConecta | 60-71 | 300h |
| Marketing AI Stack Nativo | 72-83 | 250h |
| **Subtotal** | | **1,050h** |

> ⚠️ **MARKETING AI STACK NATIVO** (Docs 149-157) - Reemplaza ActiveCampaign, HubSpot, Mailchimp
> 
> | Módulo | Doc | Horas | Reemplaza |
> |--------|-----|-------|-----------|
> | `jaraba_crm` | 150 | 40-50h | HubSpot, Pipedrive |
> | `jaraba_email` | 151 | 115-155h | Mailchimp, ActiveCampaign |
> | `jaraba_social` | 152 | 50-70h | Buffer, Hootsuite |
> | Paid Ads Integration | 153 | 15-20h | - |
> | Pixel Manager | 154 | 10-15h | GTM |
> | Events & Webinars | 155 | 15-20h | Calendly+Zoom |
> | A/B Testing | 156 | 12-18h | Optimizely |
> | Referral Program | 157 | 8-12h | ReferralCandy |

**Total Bloque A:** 1,690h

---

## 3. Bloque B: Copiloto Emprendimiento v3

> **Fuentes:** Libros Osterwalder (6), Blank/Dorf, Kaufman

### B.1 Objetivos
- Hiperpersonalización desde BD del emprendedor
- Integración 6 libros Osterwalder + Lean Startup
- Procesos Customer Development estructurados 4 fases

### B.2 Componentes

| Componente | Tipo | Horas |
|------------|------|-------|
| `EntrepreneurContextService` | Service | 16h |
| `ValuePropositionCanvasService` | Service | 24h |
| `CustomerDiscoveryService` | Service | 24h |
| Test/Learning Cards | Services | 16h |
| Patrones + Pivot Detector | Services | 16h |
| **Subtotal** | | **96h** |

### B.3 Nuevos Modos Copiloto
- 🎯 VPC Designer (propuesta de valor, diferencial)
- 🔍 Customer Discovery Coach (entrevistas, validación)
- 📊 Business Pattern Expert (modelo negocio, monetización)
- 🔄 Pivot Advisor (señales de pivote)

---

## 4. Bloque C: Journey Engine

> **Referencia:** [103_UX_Journey_Specifications_Avatar_v1_Claude.md](../tecnicos/20260117f-103_UX_Journey_Specifications_Avatar_v1_Claude.md)

### C.1 Arquitectura
- Context Engine → Decision Engine → Presentation Engine
- **19 avatares** en 7 verticales
- **7 estados** de journey: Discovery → Activation → Engagement → Conversion → Retention → Expansion → At-Risk

### C.2 Roadmap

| Item | Sprints | Horas |
|------|---------|-------|
| Core + Estado tracking | C1-C2 | 90h |
| AgroConecta (3 avatares) | C3-C4 | 70h |
| ComercioConecta + Servicios (4) | C5-C6 | 70h |
| Empleabilidad (3) | C7-C8 | 70h |
| **Emprendimiento (3)** | C9-C10 | 70h |
| Andalucía +ei + Certificación (6) | C11-C12 | 90h |
| IA proactiva + Polish | C13-C14 | 70h |
| **Subtotal** | | **530h** |

---

## 5. Bloque D: Admin Center

> **Referencia:** [104_SaaS_Admin_Center_Premium_v1_Claude.md](../tecnicos/20260117f-104_SaaS_Admin_Center_Premium_v1_Claude.md)

### D.1 Módulos
Dashboard Ejecutivo, Gestión Tenants, Usuarios, Centro Financiero, Analytics, Alertas, Configuración, Logs

### D.2 Roadmap

| Item | Sprints | Horas |
|------|---------|-------|
| Design System + Layout | D1-D2 | 70h |
| Dashboard ejecutivo | D3-D4 | 70h |
| Tenants + Health Score | D5-D6 | 70h |
| Users + RBAC | D7-D8 | 60h |
| Finance + Stripe | D9-D10 | 80h |
| Analytics + Reports | D11-D12 | 70h |
| Alerts + Playbooks | D13-D14 | 60h |
| Settings + API Keys | D15-D16 | 50h |
| Logs + Audit | D17-D18 | 45h |
| WebSockets + QA | D19-D20 | 60h |
| **Subtotal** | | **635h** |

---

## 6. Bloque E: Training & Certification

> **Referencia:** [46_Training_Certification_System_v1_Claude.md](../tecnicos/20260115j-46_Training_Certification_System_v1_Claude.md)

### E.1 Escalera de Valor (6 Peldaños)

| Nivel | Producto | Precio | Modelo |
|-------|----------|--------|--------|
| 0 | Lead Magnets | €0 | Captura |
| 1 | Microcursos | €29-97 | Pago único |
| 2 | Club Jaraba | €19-99/mes | Suscripción |
| 3 | Mentoría Grupal | €297-497 | Cohorte |
| 4 | Mentoría 1:1 | €997-1.997 | Premium |
| 5 | Certificación | €2.000-5.000 | Licencia |

### E.2 Roadmap

| Item | Sprints | Horas |
|------|---------|-------|
| training_product + APIs | E1-E2 | 24h |
| certification_program | E3-E4 | 24h |
| Exámenes + Open Badge | E5-E6 | 24h |
| ECA flows + Royalties | E7-E8 | 20h |
| Dashboard + Directorio | E9-E10 | 16h |
| Territorios + Franquicias | E11-E12 | 16h |
| **Subtotal** | | **124h** |

---

## 7. Bloque F: AI Content Hub

> **Referencia:** [20260123f-Bloque_F_AI_Content_Hub_Implementacion_Claude.md](../implementacion/20260123f-Bloque_F_AI_Content_Hub_Implementacion_Claude.md)

### F.1 Stack Tecnológico

| Componente | Tecnología |
|------------|------------|
| Core CMS | Drupal 11 + `jaraba_content_hub` |
| AI Generation | Claude API (claude-sonnet-4-5) |
| Newsletter | `jaraba_email` nativo (reemplaza ActiveCampaign) |
| Vector Search | Qdrant Cloud |
| Editor | CKEditor 5 + React |

### F.2 Roadmap

| Sprint | Horas | Entregable |
|--------|-------|------------|
| F.1 Core Blog | 80-100h | content_article, content_category, REST APIs |
| F.2 AI Assistant | 60-70h | Claude integration, generation services |
| F.3 Newsletter | 50-60h | jaraba_email integration |
| F.4 Recommendations | 50-60h | Qdrant integration |
| F.5 Frontend | 100-120h | Blog components, Editor dashboard |
| **Subtotal** | | **340-410h** |

---

## 8. Bloque G: AI Skills System

> **Referencia:** [20260123g-Bloque_G_AI_Skills_Implementacion_Claude.md](../implementacion/20260123g-Bloque_G_AI_Skills_Implementacion_Claude.md)

### G.1 Propuesta de Valor

**Diferencia crítica:**
- RAG tradicional → Qué información existe (factual)
- Skills System → Cómo ejecutar con maestría (procedimental)

### G.2 Arquitectura de 4 Capas

```
SKILL RESOLUTION
├── 1. TENANT SKILLS (Priority 1 - Highest)
│   └── Brand voice, Custom workflows
├── 2. AGENT SKILLS (Priority 2)
│   └── Producer Copilot, Consumer Copilot
├── 3. VERTICAL SKILLS (Priority 3)
│   └── AgroConecta, Empleabilidad, etc.
└── 4. CORE SKILLS (Priority 4 - Lowest)
    └── Tone, GDPR, Escalation, GEO
```

### G.3 Roadmap

| Sprint | Horas | Entregable |
|--------|-------|------------|
| G1-G2 | 40-50h | Entidades: ai_skill, revisions, usage |
| G3-G4 | 40-50h | SkillManager Service |
| G5-G6 | 30-40h | REST APIs: /skills/resolve |
| G7-G8 | 40-50h | Editor Visual (Monaco + React) |
| G9-G10 | 50-60h | 35 Skills predefinidas |
| **Subtotal** | | **200-250h** |

---

## 9. Timeline Integrado

### 9.1 Resumen por Fase

| Fase | Quarter | Meses | Horas | Bloques |
|------|---------|-------|-------|---------|
| 1 | Q1 2026 | 1-3 | 436h | A.1, A.2, B |
| 2 | Q2 2026 | 4-6 | 594h | A.3, C (parcial), E |
| 3 | Q3 2026 | 7-9 | 560h | A.3 (fin), C (cont), F |
| 4 | Q4 2026 | 10-12 | 780h | C (fin), D (inicio), G, A.4 |
| 5 | Q1 2027 | 13-15 | 600h | D (fin), A.4 (ServiciosConecta) |
| 6 | Q2 2027 | 16-18 | 530h | A.4 (Marketing), Integración |

**Total:** ~4,500h en ~24 meses (equipo de 5-6 desarrolladores)

### 9.2 Dependencias Críticas

```mermaid
graph LR
    QW[Quick Wins] --> SEPE
    QW --> FP[Frontend Premium]
    SEPE --> AC[AgroConecta]
    FP --> AC
    COP[Copiloto v3] --> JE[Journey Engine]
    JE --> ADM[Admin Center]
    TRAIN[Training] --> JE
    AC --> CC[ComercioConecta]
    CC --> SC[ServiciosConecta]
    ACH[AI Content Hub] --> NL[jaraba_email]
    SKILLS[AI Skills] --> COP
    NL --> MK[Marketing Stack]
```

---

## 10. Criterios de Éxito

| Área | Métrica | Target |
|------|---------|--------|
| SEPE | Centro piloto | 1 en producción Q1 |
| Frontend | Visual Picker funcional | Q2 2026 |
| AgroConecta | GMV mensual | €10K Q3 |
| Copiloto v3 | NPS Personalización | >8/10 |
| Journey Engine | Time to Value | <5 min |
| Admin Center | First Contentful Paint | <1.5s |
| Training | Conversion Lead→Micro | 12% |
| AI Content Hub | Artículos IA/mes | 50+ |
| AI Skills | Skills activas | 50+ |
| Marketing Stack | Email open rate | >25% |

---

## 11. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-23 20:50 | 3.0.0 | **Auditoría exhaustiva v3.0**. Añadidos Bloques F (AI Content Hub) y G (AI Skills System). Actualizado Timeline a 24 meses (~4,500h). Marketing Stack nativo consolidado. |
| 2026-01-23 09:46 | 2.0.0 | Plan Maestro Unificado - Integra Auditoría + 5 Pilares (A-E) |
| 2026-01-22 | 1.0.0 | Plan de Gaps Auditoría original |

---

**Jaraba Impact Platform | Plan Maestro Unificado v3.0 | Enero 2026**
