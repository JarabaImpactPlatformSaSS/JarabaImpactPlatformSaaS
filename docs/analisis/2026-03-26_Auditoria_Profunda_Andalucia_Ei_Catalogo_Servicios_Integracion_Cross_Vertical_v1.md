# Auditoria Profunda: Andalucia +ei 2a Edicion — Catalogo de Servicios, Integracion Cross-Vertical e IA Nativa Proactiva

**Version:** 1.0.0
**Fecha:** 2026-03-26
**Autor:** Claude Opus 4.6 (arquitecto SaaS senior, ingeniero Drupal senior, ingeniero UX senior, ingeniero IA senior)
**Estado:** VIGENTE
**Alcance:** Vertical Andalucia +ei, integracion con Empleabilidad y Emprendimiento, catalogo de 5 Packs, acceso por fase, IA proactiva todos los roles, Setup Wizard + Daily Actions participante, billing/bonos/Kit Digital

---

## Indice de Navegacion (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Metodologia de Auditoria](#2-metodologia-de-auditoria)
3. [Inventario Completo de lo Implementado](#3-inventario-completo-de-lo-implementado)
4. [Confrontacion: Catalogo de Servicios vs Implementacion](#4-confrontacion-catalogo-de-servicios-vs-implementacion)
   - 4.1 [Pack 1: Contenido Digital](#41-pack-1-contenido-digital)
   - 4.2 [Pack 2: Asistente Virtual](#42-pack-2-asistente-virtual)
   - 4.3 [Pack 3: Presencia Online](#43-pack-3-presencia-online)
   - 4.4 [Pack 4: Tienda Digital](#44-pack-4-tienda-digital)
   - 4.5 [Pack 5: Community Manager](#45-pack-5-community-manager)
   - 4.6 [Integracion Modulos Formativos → Packs](#46-integracion-modulos-formativos--packs)
   - 4.7 [Sistema de Clientes Piloto](#47-sistema-de-clientes-piloto)
5. [Gap: Codigo Existe vs Usuario lo Experimenta](#5-gap-codigo-existe-vs-usuario-lo-experimenta)
6. [Analisis de Acceso por Fase: Formacion vs Insercion](#6-analisis-de-acceso-por-fase-formacion-vs-insercion)
   - 6.1 [Estado Actual del Access Control](#61-estado-actual-del-access-control)
   - 6.2 [Matriz de Acceso Propuesta (2 Alcances)](#62-matriz-de-acceso-propuesta-2-alcances)
   - 6.3 [Mapping Pack → Herramientas Plataforma por Fase](#63-mapping-pack--herramientas-plataforma-por-fase)
7. [Integracion Cross-Vertical: Empleabilidad + Emprendimiento](#7-integracion-cross-vertical-empleabilidad--emprendimiento)
   - 7.1 [Arquitectura de Bridges](#71-arquitectura-de-bridges)
   - 7.2 [Gaps Cross-Vertical](#72-gaps-cross-vertical)
8. [Setup Wizard + Daily Actions: Analisis por Rol](#8-setup-wizard--daily-actions-analisis-por-rol)
   - 8.1 [Staff (Coordinador, Orientador, Formador)](#81-staff-coordinador-orientador-formador)
   - 8.2 [Participante — GAP CRITICO](#82-participante--gap-critico)
9. [IA Nativa Proactiva: Analisis por Rol](#9-ia-nativa-proactiva-analisis-por-rol)
   - 9.1 [Coordinador](#91-coordinador)
   - 9.2 [Orientador — GAP](#92-orientador--gap)
   - 9.3 [Formador — GAP](#93-formador--gap)
   - 9.4 [Participante](#94-participante)
10. [Sistema Proactivo: Notificaciones y Recordatorios](#10-sistema-proactivo-notificaciones-y-recordatorios)
    - 10.1 [Lo que Funciona](#101-lo-que-funciona)
    - 10.2 [Codigo Muerto (Existe pero No se Ejecuta)](#102-codigo-muerto-existe-pero-no-se-ejecuta)
    - 10.3 [Lo que Falta](#103-lo-que-falta)
11. [Billing, Bonos y Kit Digital](#11-billing-bonos-y-kit-digital)
    - 11.1 [Lifecycle del Bono Participante](#111-lifecycle-del-bono-participante)
    - 11.2 [Enforcement 12 Meses](#112-enforcement-12-meses)
    - 11.3 [Transicion Bono → Plan Pagado](#113-transicion-bono--plan-pagado)
12. [Metodo Impacto Jaraba y Metasitio jarabaimpact.com](#12-metodo-impacto-jaraba-y-metasitio-jarabaimpactcom)
13. [Medidas de Salvaguarda](#13-medidas-de-salvaguarda)
14. [Tabla de Correspondencia: Especificaciones Tecnicas](#14-tabla-de-correspondencia-especificaciones-tecnicas)
15. [Tabla de Cumplimiento de Directrices](#15-tabla-de-cumplimiento-de-directrices)
16. [Score Detallado y Roadmap](#16-score-detallado-y-roadmap)
17. [Glosario](#17-glosario)

---

## 1. Resumen Ejecutivo

Esta auditoria profunda examina el vertical Andalucia +ei (2a Edicion, PIIL CV 2025) desde 9 perspectivas simultaneas: arquitectura SaaS, ingenieria de software, UX, Drupal, theming, GrapesJS/Page Builder, SEO, IA nativa y logica de negocio. El objetivo es verificar que CADA componente del Catalogo de Servicios del Programa (5 Packs x 3 modalidades) tiene su cauce completo en la plataforma, desde el codigo backend hasta la experiencia real del usuario.

### Hallazgos Principales

| Dimension | Score | Veredicto |
|-----------|-------|-----------|
| Modelo de datos (entities) | 9.5/10 | Excelente: 24 entities, 64 services, 33 controllers |
| Roles y permisos | 9/10 | Solido: SSOT con RolProgramaService |
| Setup Wizard staff | 9/10 | Completo para 3 roles staff |
| **Setup Wizard participante** | **0/10** | **GAP CRITICO: No existe** |
| **Daily Actions participante** | **0/10** | **GAP CRITICO: No existe** |
| Cross-vertical bridges | 8.5/10 | 12 bridges, 7 opcionales |
| **IA proactiva todos roles** | **5/10** | **Solo coordinador + participante tienen contexto dedicado** |
| **Recordatorios automaticos** | **2/10** | **Metodo existe, no se invoca desde cron** |
| **Acceso diferenciado por fase** | **3/10** | **Mismo acceso formacion = insercion** |
| **Pack → Herramienta mapping** | **4/10** | **Entities existen, wiring incompleto** |
| Billing/bonos lifecycle | 6/10 | Falta expiracion + transicion |
| Metodo Impacto backend | 2/10 | Solo metasite config, sin backend |

**Score Global Actual: 5.8/10** — Lejos del 10/10 clase mundial.

---

## 2. Metodologia de Auditoria

### Fuentes Analizadas

1. **Documentacion normativa**: 49 documentos en `docs/andaluciamasei/`, incluyendo:
   - `20260324d-catalogo-servicios-andalucia-ei_Claude.md` (catalogo 5 Packs, 23.4 KB)
   - `20260324e-integracion-catalogo-andalucia-ei_Claude.md` (integracion modulos, 23.1 KB)
2. **Codigo fuente**: `web/modules/custom/jaraba_andalucia_ei/` (64 services, 33 controllers, 24 entities, 42 templates)
3. **Cross-vertical**: `jaraba_candidate/`, `jaraba_business_tools/`, `jaraba_copilot_v2/`, `ecosistema_jaraba_core/`
4. **Directrices maestras**: DIRECTRICES v166, ARQUITECTURA v150, FLUJO v115, INDICE v195
5. **Arquitectura theming**: `2026-02-05_arquitectura_theming_saas_master.md` v3.1
6. **Memory files**: `andalucia-ei-2e.md`, `frontend-patterns.md`, `ia-services-reference.md`

### Criterios de Evaluacion

- **PIPELINE-E2E-001**: Verificar 4 capas (Service → Controller → hook_theme → Template)
- **RUNTIME-VERIFY-001**: Verificar 5 dependencias runtime
- **IMPLEMENTATION-CHECKLIST-001**: Complitud, integridad, consistencia, coherencia
- **"Codigo existe vs usuario experimenta"**: Para cada feature, verificar que el usuario final puede acceder a ella

---

## 3. Inventario Completo de lo Implementado

### 3.1 Entities (24 Content Entities)

| Entity | Proposito | AccessControlHandler | Views Data | Preprocess |
|--------|-----------|---------------------|-----------|-----------|
| ProgramaParticipanteEi | Participante PIIL (40+ campos) | Si | Si | Si |
| SolicitudEi | Solicitud de participacion | Si | Si | Si |
| PlanFormativoEi | Plan formativo del programa | Si | Si | Si |
| AccionFormativaEi | Accion formativa individual | Si | Si | Si |
| SesionProgramadaEi | Sesion programada | Si | Si | Si |
| InscripcionSesionEi | Inscripcion a sesion | Si | Si | Si |
| MaterialDidacticoEi | Material didactico | Si | Si | Si |
| AsistenciaDetalladaEi | Asistencia dual (presencial/online) | Si | Si | Si |
| PackServicioEi | Pack de servicio (5 tipos x 3 modalidades) | Si | Si | No |
| EntregableFormativoEi | Entregable formativo (29 por participante) | Si | Si | No |
| EvaluacionCompetenciaIaEi | Evaluacion IA de competencias | Si | Si | No |
| PlanEmprendimientoEi | Plan de emprendimiento | Si | Si | Si |
| NegocioProspectadoEi | Negocio prospectado (pipeline 6 fases) | Si | Si | No |
| ActuacionSto | Actuacion STO (SEPE) | Si | Si | Si |
| InsercionLaboral | Registro de insercion laboral | Si | Si | Si |
| IndicadorFsePlus | Indicadores FSE+ | Si | Si | Si |
| ExpedienteDocumento | Documento del expediente | Si | Si | Si |
| FichaTecnicaEi | Ficha tecnica | Si | No | No |
| ProspeccionEmpresarial | Prospeccion empresarial | Si | Si | Si |
| StaffProfileEi | Perfil staff | Si | Si | No |
| RolProgramaLog | Log de asignacion de roles | Si | Si | No |

### 3.2 Services Clave (64 totales)

**Gestion del Programa:**
- `RolProgramaService` — SSOT deteccion de roles (ROL-PROGRAMA-SSOT-001)
- `AccesoProgramaService` — Eligibilidad portal
- `FaseTransitionManager` — Transiciones de fase con prerrequisitos
- `ProgramaVerticalAccessService` — Acceso cross-vertical por carril
- `CalendarioProgramaService` — Calendario y semanas

**Formacion y Sesiones:**
- `AccionFormativaService` — Logica acciones formativas
- `SesionProgramadaService` — Gestion sesiones
- `InscripcionSesionService` — Inscripcion con vinculo STO
- `VoboSaeWorkflowService` — Flujo VoBo SAE 8 estados
- `AsistenciaComplianceService` — Compliance asistencia (75% presencial, 80% online)
- `ICalExportService` — Exportacion iCal

**Packs y Entregables:**
- `CatalogoPacksService` — Publicacion y slugs de packs
- `PortfolioEntregablesService` — Portfolio 29 entregables (auto-seed en insert)
- `CalculadoraPuntoEquilibrioService` — Calculadora punto equilibrio

**Documentos y Firma:**
- `DocumentoFirmaOrchestrator` — Orquestador firmas
- `FirmaWorkflowService` — Lifecycle firma electronica
- `ExpedienteService` — Gestion expediente documental
- `AcuerdoParticipacionService` — Documento bilateral
- `DaciService` — DACI (proteccion datos)
- `IncentiveReceiptService` — Recibo incentivo 528EUR
- `IncentiveWaiverService` — Renuncia incentivo

**IA y Copilot:**
- `AndaluciaEiCopilotBridgeService` — Bridge copilot (dual: coordinador vs participante)
- `AndaluciaEiCopilotContextProvider` — Contexto PIIL rico
- `CopilotPhaseConfigService` — 6 modos por fase
- `CopilotHistorialService` — Historial conversaciones
- `DocumentoRevisionIaService` — Revision IA documentos

**Cross-Vertical Bridges (12):**
1. `EiEmprendimientoBridgeService` → jaraba_business_tools (Canvas, MVP, SROI)
2. `EiMatchingBridgeService` → jaraba_matching (matching Qdrant)
3. `EiAlumniBridgeService` → jaraba_mentoring (alumni, peer mentoring)
4. `EiBadgeBridgeService` → badge_award (gamificacion PIIL)
5. `EiPushNotificationService` → jaraba_pwa + jaraba_support
6. `EiMultichannelNotificationService` → push + WhatsApp + in-app
7. `EiContentHubBridgeService` → jaraba_content_hub (Modulo 4, Pack 1/5)
8. `EiComercioConectaBridgeService` → jaraba_comercioconecta (Pack 4)
9. `EiJarabaLexBridgeService` → jaraba_jarabalex (Modulo 3)
10. `MensajeriaIntegrationService` → jaraba_messaging
11. `AdaptacionItinerarioService` → Itinerario por colectivo y barreras
12. `ProgramaVerticalAccessService` → Acceso temporal a verticales

### 3.3 Controllers (33 totales)

**Dashboards por Rol:**
- `CoordinadorDashboardController` — Hub operativo (14 herramientas)
- `OrientadorDashboardController` — Sesiones y seguimiento
- `FormadorDashboardController` — Sesiones y materiales
- `ParticipantePortalController` — Portal participante (health score, timeline, portfolio)

**Paginas Publicas:**
- `AndaluciaEiLandingController` — Landing del programa
- `AndaluciaEiCaseStudyController` — Caso de exito PED S.L.
- `CatalogoPublicoController` — Catalogo publico de packs
- `PortfolioPublicoController` — Portfolio publico participante
- `PruebaGratuitaController` — Landing prueba gratuita
- `ImpactoPublicoController` — Dashboard impacto publico

---

## 4. Confrontacion: Catalogo de Servicios vs Implementacion

### 4.1 Pack 1: Contenido Digital

**Requisito Catalogo:** Creacion, planificacion y publicacion de contenido digital profesional para redes sociales, web y newsletters de negocios locales usando IA supervisada.

| Herramienta Requerida | Servicio/Modulo SaaS | Estado | Gap |
|----------------------|---------------------|--------|-----|
| IA Copilot (textos, titulos, hashtags) | jaraba_copilot_v2 + AndaluciaEiCopilotBridgeService | Implementado | Falta modo `content_creator` especifico |
| Content Hub (calendario editorial) | jaraba_content_hub + EiContentHubBridgeService | Bridge existe | Participante NO tiene acceso directo al editor de Content Hub |
| Editor visual (graficos) | GrapesJS en jaraba_page_builder | Implementado | Sin plantillas predefinidas para posts sociales |
| SEO automatico | jaraba_page_builder SEO plugin | Implementado | — |
| Dashboard analiticas | ecosistema_jaraba_core analytics | Parcial | Sin metricas de contenido por pack |
| Stripe billing (facturacion clientes) | jaraba_billing + Stripe Connect | Implementado | PackServicioEi tiene stripe_product_id pero sin flujo de activacion |

**Gap Principal:** El bridge `EiContentHubBridgeService` existe pero el participante en fase insercion no tiene una ruta dedicada para acceder al Content Hub con su contexto de pack. El acceso cross-vertical via `ProgramaVerticalAccessService` da acceso al vertical pero no al flujo especifico del pack.

### 4.2 Pack 2: Asistente Virtual

**Requisito Catalogo:** Asistencia administrativa, organizativa y documental para profesionales usando agentes IA.

| Herramienta Requerida | Servicio/Modulo SaaS | Estado | Gap |
|----------------------|---------------------|--------|-----|
| IA Copilot (emails, cartas, resumenes) | jaraba_copilot_v2 | Implementado | Sin modo `virtual_assistant` |
| CRM integrado (contactos, agenda) | Parcial via NegocioProspectadoEi | Solo prospeccion | **Falta CRM basico para clientes del participante** |
| Stripe billing (facturas a clientes) | jaraba_billing | Implementado | Sin flujo de facturacion desde el portal del participante |
| JarabaLex (consultas legales, BOE) | EiJarabaLexBridgeService | Bridge existe | **Acceso no diferenciado por fase** |
| Firma digital | ecosistema_jaraba_core.firma_digital | Implementado | Solo para documentos del programa, no para clientes |

**Gap Principal:** No existe un CRM basico desde el portal del participante para gestionar SUS clientes. `NegocioProspectadoEi` es para prospeccion empresarial del programa (leads para el participante), no para los clientes del participante una vez insertado.

### 4.3 Pack 3: Presencia Online

**Requisito Catalogo:** Web profesional 3-5 paginas, Google Business, redes sociales, SEO local.

| Herramienta Requerida | Servicio/Modulo SaaS | Estado | Gap |
|----------------------|---------------------|--------|-----|
| GrapesJS (web sin codigo) | jaraba_page_builder | Implementado | **Sin plantillas especificas para negocios locales** |
| SEO automatico (Schema.org, geo) | jaraba_page_builder SEO plugin | Implementado | — |
| IA Copilot (textos web, servicios) | jaraba_copilot_v2 | Implementado | — |
| Content Hub (publicaciones periodicas) | jaraba_content_hub | Implementado | — |
| Google Business integration | **NO EXISTE** | No implementado | **GAP: No hay integracion Google Business** |

**Gap Principal:** El editor GrapesJS existe pero no tiene plantillas predefinidas para negocios locales (fontanero, peluqueria, restaurante). El participante tendria que construir desde cero. Ademas, no existe integracion con Google Business Profile.

### 4.4 Pack 4: Tienda Digital

**Requisito Catalogo:** Tienda online completa con catalogo, fotos IA, SEO, gestion pedidos, pagos online, postventa.

| Herramienta Requerida | Servicio/Modulo SaaS | Estado | Gap |
|----------------------|---------------------|--------|-----|
| E-commerce platform | jaraba_comercioconecta + EiComercioConectaBridgeService | Bridge existe | **Participante sin acceso directo al panel comercio** |
| Fotografia producto (IA) | **NO EXISTE** | No implementado | **GAP: No hay optimizacion de fotos con IA** |
| QR trazabilidad | **NO EXISTE** | No implementado | **GAP: Sin sistema QR** |
| Gestion pedidos | jaraba_comercioconecta | Parcial | — |
| Stripe billing (0% comision) | jaraba_billing | Implementado | — |

**Gap Principal:** El bridge `EiComercioConectaBridgeService` existe pero el flujo completo de "crear tienda → subir productos → configurar pagos → gestionar pedidos" no esta orquestado como experiencia guiada para el participante.

### 4.5 Pack 5: Community Manager

**Requisito Catalogo:** Gestion integral de comunidad online: reviews, comentarios, moderacion, crisis reputacional.

| Herramienta Requerida | Servicio/Modulo SaaS | Estado | Gap |
|----------------------|---------------------|--------|-----|
| Monitoreo reviews (Google, TripAdvisor) | **NO EXISTE** | No implementado | **GAP: Sin integracion reviews externas** |
| Respuesta a mensajes privados | **NO EXISTE** | No implementado | **GAP: Sin integracion DMs redes** |
| Gestion crisis reputacional | **NO EXISTE** | No implementado | **GAP: Sin protocolo crisis** |
| Posts redes sociales | jaraba_content_hub parcial | Parcial | — |
| Informe reputacion | **NO EXISTE** | No implementado | **GAP: Sin dashboard reputacion** |

**Gap Principal:** El Pack 5 es el mas dependiente de integraciones externas (Google, TripAdvisor, Instagram DMs). Ninguna de estas integraciones existe. El Content Hub cubre publicacion pero no monitoring ni engagement.

### 4.6 Integracion Modulos Formativos → Packs

El catalogo define una correlacion directa entre modulos formativos y assets del pack:

| Modulo | Formacion | Asset del Pack | Estado SaaS |
|--------|-----------|---------------|-------------|
| Orientacion | Autoconocimiento, hipotesis | Perfil + preseleccion 2-3 packs | Parcial (campo `pack_preseleccionado` existe) |
| Modulo 0: Fundamentos IA | Conversacion con agentes IA | Primeras tareas productivas con copilot | Implementado (CopilotPhaseConfigService) |
| Modulo 1: Propuesta Valor | Lean Canvas, validacion | Lean Canvas del pack + validacion mercado | Bridge emprendimiento existe |
| Modulo 2: Finanzas | Portfolio, precios, PE | Ficha producto con precios + prevision financiera | CalculadoraPuntoEquilibrioService implementado |
| Modulo 3: Tramites | Alta autonomo, IAE, facturacion | IAE/CNAE + solicitudes simuladas + factura modelo | **EiJarabaLexBridgeService existe pero sin flujo guiado** |
| Modulo 4: Marketing | Marca, contenido, funnel | Web + contenido + funnel + 5 contactos CRM | **Parcial: GrapesJS si, CRM contactos NO** |
| Modulo 5: Integracion | Flujo completo, proyecto piloto | Pack publicado + billing activo + proyecto piloto | **Parcial: CatalogoPacksService si, billing flow NO** |
| Acompanamiento | Lanzamiento, consolidacion | Primeros clientes + facturacion real + SS | **Parcial: bridges existen, orquestacion NO** |

### 4.7 Sistema de Clientes Piloto

**Requisito Catalogo:** 50-80 negocios locales piloto, matching participante-negocio, acuerdo de prueba, conversion 40-50%.

| Componente | Estado SaaS | Gap |
|-----------|-------------|-----|
| Prospeccion negocios | NegocioProspectadoEi (6 fases pipeline) | Implementado |
| Matching participante-negocio | EiMatchingBridgeService (Qdrant) | Implementado |
| Acuerdo prueba (firma) | FirmaWorkflowService + ExpedienteDocumento | **Falta template "Acuerdo Cliente Piloto"** |
| Ejecucion pack piloto | PackServicioEi entity | **Falta flujo "modo piloto" (gratis/simbolico)** |
| Conversion a cliente pagado | Stripe billing | **Falta flujo transicion piloto → pago** |
| Feedback y testimonio | **NO EXISTE** | **GAP: Sin sistema feedback cliente piloto** |

---

## 5. Gap: Codigo Existe vs Usuario lo Experimenta

### 5.1 Codigo que Existe pero NO Llega al Usuario

| Feature | Codigo | Motivo de Desconexion | Impacto |
|---------|--------|----------------------|---------|
| Recordatorio sesiones 24h/1h | `EiMultichannelNotificationService::enviarRecordatoriosSesion()` | **No se llama desde hook_cron ni Drush** | Participantes no reciben recordatorios |
| Recordatorio firma pendiente periodico | `FirmaWorkflowService::notificarFirmaPendiente()` | **Solo se dispara 1 vez al cambiar estado** | Sin follow-up 24/48/72h |
| Contexto copilot orientador | `AndaluciaEiCopilotBridgeService` | **Solo implementa coordinador + participante** | Orientador recibe copilot generico |
| Contexto copilot formador | `AndaluciaEiCopilotBridgeService` | **Solo implementa coordinador + participante** | Formador recibe copilot generico |
| Acceso diferenciado insercion | `ProgramaVerticalAccessService` | **Todas las fases activas tienen mismo acceso** | Sin feature gating por fase |
| Bridge Content Hub para participante | `EiContentHubBridgeService` | **Bridge existe pero sin ruta/UI para participante** | Participante no accede al Content Hub |
| Bridge ComercioConecta para participante | `EiComercioConectaBridgeService` | **Bridge existe pero sin ruta/UI para participante** | Participante no accede a tienda digital |
| Facturacion de packs a clientes | `PackServicioEi.stripe_product_id/stripe_price_id` | **Campos existen pero sin flujo de activacion** | Participante no puede facturar a sus clientes |
| Catalogo publico de packs | `CatalogoPacksService::publicarPack()` | **Servicio existe pero sin UI para publicar** | Participante no puede publicar su pack |

### 5.2 Codigo que Funciona Correctamente

| Feature | Codigo | Verificacion |
|---------|--------|-------------|
| Fases PIIL (6 fases con prerequisitos) | FaseTransitionManager | Completo y correcto |
| Roles SSOT | RolProgramaService | 6 roles, cascada correcta |
| 29 entregables auto-seed | PortfolioEntregablesService | Se crean en hook_insert |
| Asistencia dual (presencial/online) | AsistenciaDetalladaEi | 80/20 compliance |
| STO bidireccional | StoBidireccionalService | Push + pull + reconciliacion |
| Firma electronica tactil | FirmaTactilController | Pad tactil funcional |
| Expediente documental | ExpedienteService + hub | Categorizado y completo |
| VoBo SAE 8 estados | VoboSaeWorkflowService | Flujo completo |
| Dashboards staff (3 roles) | Coordinador/Orientador/Formador controllers | Con wizard + daily actions |
| Portal participante (read-only) | ParticipantePortalController | Rico pero pasivo |

---

## 6. Analisis de Acceso por Fase: Formacion vs Insercion

### 6.1 Estado Actual del Access Control

**Archivo:** `web/modules/custom/jaraba_andalucia_ei/src/Service/ProgramaVerticalAccessService.php`

**Constantes actuales:**

```
FASES_ACTIVAS = ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento']
CARRIL_VERTICALS = [
  'impulso_digital' => ['empleabilidad', 'emprendimiento'],
  'acelera_pro' => ['emprendimiento', 'empleabilidad'],
  'hibrido' => ['empleabilidad', 'emprendimiento'],
]
MESES_EXTENSION_SEGUIMIENTO = 6
```

**Problema:** No existe diferenciacion entre fases. Un participante en `acogida` tiene exactamente el mismo acceso a verticales que uno en `insercion`. Esto contradice el catalogo que define acceso progresivo.

### 6.2 Matriz de Acceso Propuesta (2 Alcances)

**Alcance 1: FORMACION (fases acogida, diagnostico, atencion)**

El participante esta aprendiendo. Acceso a herramientas formativas pero NO a herramientas de produccion/facturacion.

| Herramienta | Acceso | Justificacion |
|-------------|--------|---------------|
| Copilot IA (todos los modos) | SI | Mentor IA desde el dia 1 |
| Portfolio entregables | SI | Construye su portfolio formativo |
| Expediente documental | SI | Documentos del programa |
| Sesiones y asistencia | SI | Participa en formacion |
| Lean Canvas (emprendimiento) | SI | Modulo 1 requiere Canvas |
| Calculadora PE | SI | Modulo 2 requiere calculo |
| JarabaLex (consultas basicas) | SI | Modulo 3 requiere tramites |
| GrapesJS (modo formativo) | SI | Modulo 4 requiere crear web |
| Content Hub (modo formativo) | SI | Modulo 4 requiere contenido |
| CRM clientes | NO | Aun no tiene negocio |
| Facturacion Stripe a clientes | NO | Aun no tiene clientes |
| Pack publicado en catalogo | NO | Aun no esta listo |
| Tienda digital operativa | NO | Aun no tiene productos |

**Alcance 2: INSERCION (fases insercion, seguimiento)**

El participante esta operando su negocio o empleado. Acceso COMPLETO a herramientas de produccion y gestion empresarial.

| Herramienta | Acceso | Justificacion |
|-------------|--------|---------------|
| TODO lo anterior | SI | Mantiene acceso formativo |
| CRM basico (gestionar SUS clientes) | SI | Necesita gestionar cartera |
| Facturacion Stripe (cobrar a clientes) | SI | Necesita facturar |
| Pack publicado en catalogo | SI | Necesita visibilidad |
| Tienda digital operativa | SI | Pack 4 requiere tienda |
| Dashboard metricas negocio | SI | Medir rendimiento |
| JarabaLex completo (Hacienda, SS) | SI | Obligaciones fiscales |
| Firma digital para clientes | SI | Contratos con clientes |
| Google Business (cuando exista) | SI | Presencia online |
| Gestion pedidos (ComercioConecta) | SI | Pack 4 operativo |
| Exportacion iCal clientes | SI | Agenda profesional |

**Duracion Alcance 2:** Hasta completar 12 meses desde `fecha_inicio_programa`. Despues, avisos a 60/30/15/7 dias y transicion a plan pagado.

### 6.3 Mapping Pack → Herramientas Plataforma por Fase

| Pack | Modulos Formativos | Herramientas Formacion | Herramientas Insercion |
|------|-------------------|----------------------|----------------------|
| Pack 1: Contenido Digital | M0, M1, M4, M5 | Copilot (content), Content Hub (formativo), GrapesJS (formativo) | Content Hub (produccion), calendario editorial, metricas |
| Pack 2: Asistente Virtual | M0, M2, M3 | Copilot (assistant), JarabaLex (consultas), calculadora | CRM clientes, facturacion, firma digital, JarabaLex completo |
| Pack 3: Presencia Online | M0, M3, M4, M5 | Copilot (web), GrapesJS (crear web), SEO basico | GrapesJS (mantenimiento), SEO completo, Google Business |
| Pack 4: Tienda Digital | M0, M1, M2, M4, M5 | Copilot (ecommerce), Canvas, calculadora | ComercioConecta (tienda), catalogo, pedidos, Stripe |
| Pack 5: Community Manager | M0, M4, M5 | Copilot (community), Content Hub (formativo) | Content Hub (produccion), reviews, engagement, crisis |

---

## 7. Integracion Cross-Vertical: Empleabilidad + Emprendimiento

### 7.1 Arquitectura de Bridges

La integracion cross-vertical sigue un patron solido con bridges opcionales (`@?`):

```
Andalucia +ei (orquestador)
  |
  +---> EiEmprendimientoBridgeService (@?) ---> jaraba_business_tools
  |     [Canvas BMC, MVP, Proyecciones, SROI]
  |     [Mapeo fases: PIIL → Emprendimiento]
  |
  +---> EiMatchingBridgeService (@?) ---> jaraba_matching
  |     [Perfil candidato ↔ Empresa via Qdrant]
  |
  +---> EiContentHubBridgeService (@?) ---> jaraba_content_hub
  |     [Modulo 4, Pack 1/5]
  |
  +---> EiComercioConectaBridgeService (@?) ---> jaraba_comercioconecta
  |     [Pack 4: Tienda Digital]
  |
  +---> EiJarabaLexBridgeService (@?) ---> jaraba_jarabalex
  |     [Modulo 3: Tramites legales]
  |
  +---> ProgramaVerticalAccessService ---> Acceso temporal
        [Carril → Verticales, 6 meses seguimiento]
```

### 7.2 Gaps Cross-Vertical

| Gap | Severidad | Detalle |
|-----|-----------|---------|
| Sin GroundingProvider para Empleabilidad | Media | jaraba_candidate NO tiene GroundingProvider → copilot no busca ofertas semanticamente |
| Bridges sin diferenciacion por fase | Alta | EiContentHubBridgeService, EiComercioConectaBridgeService, EiJarabaLexBridgeService dan acceso identico en formacion e insercion |
| Sin propagacion suspension tenant | Media | Si el sponsor institucional pierde suscripcion, participantes mantienen acceso |
| Journey fallback Emprendimiento → Empleabilidad | Baja | EmprendimientoJourneyDefinition tiene fallback pero sin UI controller |
| CRM compartido ausente | Alta | Participante no tiene CRM propio para gestionar clientes de su pack |

---

## 8. Setup Wizard + Daily Actions: Analisis por Rol

### 8.1 Staff (Coordinador, Orientador, Formador)

**Estado: IMPLEMENTADO Y FUNCIONAL**

| Rol | Wizard Steps | Daily Actions | Zeigarnik | Template |
|-----|-------------|---------------|-----------|----------|
| Coordinador | 4 (plan → acciones → sesiones → validacion) | 6 (solicitudes, participante, sesion, STO, leads, plazos) | SI | coordinador-dashboard.html.twig |
| Orientador | 3 (perfil → participantes → sesion) | 4 (sesiones hoy, ficha, seguimiento, informes) | SI | orientador-dashboard.html.twig |
| Formador | 3 (perfil → sesiones → material) | 3 (sesiones hoy, asistencia, materiales) | SI | formador-dashboard.html.twig |

### 8.2 Participante — GAP CRITICO

**Estado: NO EXISTE**

El `ParticipantePortalController::portal()` NO pasa `#setup_wizard` ni `#daily_actions` al render array. El template `participante-portal.html.twig` no renderiza estas secciones.

**Consecuencia:** El participante ve un dashboard informativo rico (health score, timeline, portfolio, sesiones) pero **sin guia accionable paso a paso**. Un participante en fase `acogida` no sabe que debe hacer primero.

**Wizard Steps Requeridos por Fase:**

| # | Step | Fase | Descripcion | Condicion Completado |
|---|------|------|-------------|---------------------|
| 1 | CompletarPerfilStep | acogida | Rellenar datos personales, colectivo, provincia | Campos basicos rellenos |
| 2 | FirmarAcuerdoStep | acogida | Firmar Acuerdo de Participacion | `acuerdo_participacion_firmado = TRUE` |
| 3 | FirmarDaciStep | acogida | Firmar DACI (proteccion datos) | `daci_firmado = TRUE` |
| 4 | CompletarDimeStep | diagnostico | Completar diagnostico DIME | `dime_score IS NOT NULL` |
| 5 | SeleccionarPackStep | diagnostico/atencion | Preseleccionar 2-3 packs | `pack_preseleccionado IS NOT NULL` |
| 6 | PrimeraSesionStep | atencion | Asistir a primera sesion formativa | `horas_formacion > 0` |
| 7 | ConfirmarPackStep | atencion (M1) | Confirmar pack definitivo tras validacion | `pack_confirmado IS NOT NULL` |
| 8 | PublicarPackStep | insercion (M5) | Publicar pack en catalogo | PackServicioEi.publicado = TRUE |

**Daily Actions Requeridas por Fase:**

| # | Action | Descripcion | Badge | Fase |
|---|--------|-------------|-------|------|
| 1 | MisSesionesHoyAction | Sesiones programadas hoy | Count sesiones | Todas |
| 2 | FirmasPendientesAction | Documentos pendientes de firma | Count firmas | Todas |
| 3 | EntregablesPendientesAction | Entregables formativos por completar | Count pendientes | atencion+ |
| 4 | ChatCopilotAction | Hablar con tu mentor IA | — | Todas |
| 5 | ProgresoFormacionAction | Ver progreso formativo | % completado | atencion+ |
| 6 | GestionarClientesAction | Gestionar clientes (CRM) | Count clientes | insercion+ |
| 7 | FacturarClienteAction | Emitir factura a cliente | — | insercion+ |

---

## 9. IA Nativa Proactiva: Analisis por Rol

### 9.1 Coordinador

**Score: 8/10**

`AndaluciaEiCopilotBridgeService` detecta `isCoordinador()` y proporciona:
- System prompt completo como "asistente de coordinacion operativa"
- 14 herramientas del panel listadas en el prompt
- Stats: participantes activos, acciones formativas, sesiones, solicitudes, inserciones, prospecciones
- Sin restriccion de modos (`_modos_permitidos: []`)

**Gap:** No hay insights proactivos especificos (ej: "3 participantes en riesgo de abandono", "2 plazos vencidos esta semana").

### 9.2 Orientador — GAP

**Score: 3/10**

`AndaluciaEiCopilotBridgeService` NO tiene rama `isOrientador()`. El orientador recibe contexto generico del vertical o cae en `getGenericRequestContext()`.

**Lo que deberia tener:**
- System prompt: "Eres el asistente de orientacion laboral del orientador [nombre]"
- Contexto: participantes asignados (count, fases, riesgos), sesiones hoy, horas acumuladas, alertas normativas de sus participantes
- Modos permitidos: orientacion_individual, orientacion_grupal, evaluacion_competencias, insercion_laboral
- Soft suggestion: "Tienes 2 sesiones hoy. Participante X tiene riesgo medio de abandono."

### 9.3 Formador — GAP

**Score: 3/10**

`AndaluciaEiCopilotBridgeService` NO tiene rama `isFormador()`. El formador recibe contexto generico.

**Lo que deberia tener:**
- System prompt: "Eres el asistente pedagogico del formador [nombre]"
- Contexto: sesiones hoy, materiales pendientes, asistencias sin marcar, acciones formativas asignadas
- Modos permitidos: preparacion_sesion, evaluacion, material_didactico
- Soft suggestion: "Tienes sesion M2-3 hoy a las 10:00. Faltan 3 asistencias por marcar de la sesion anterior."

### 9.4 Participante

**Score: 7/10**

`AndaluciaEiCopilotContextProvider` proporciona contexto rico:
- Fase actual, horas (orientacion, formacion), documentos completados
- Milestones (`puede_transitar_insercion`)
- Barreras activas (accesibilidad)
- Plan emprendimiento activo
- 6 modos por fase via `CopilotPhaseConfigService`

**Gap:** No hay contexto del pack seleccionado. El copilot no sabe que el participante tiene Pack 1 y deberia sugerirle crear contenido para su cliente piloto.

---

## 10. Sistema Proactivo: Notificaciones y Recordatorios

### 10.1 Lo que Funciona

| Feature | Trigger | Canal | Frecuencia |
|---------|---------|-------|-----------|
| Cambio de fase | Entity hook update | Push + App | Inmediato |
| Milestones horas (25h, 50h, 75h, 100h) | Entity hook update | Upgrade trigger | Inmediato |
| Badge obtenido | Entity hook update | Push + App | Inmediato |
| Insercion confirmada | Entity hook update | Push + WhatsApp + App | Inmediato |
| Re-engagement inactividad | Cron (24h timer, 14+ dias) | Email sequence SEQ_AEI_005 | 24h |
| Alertas normativas | Cron (6h) | Log + badge dashboard | 6h |
| Email sequences (5 secuencias) | Various hooks | Email | Inmediato en trigger |

### 10.2 Codigo Muerto (Existe pero No se Ejecuta)

| Feature | Metodo | Problema | Solucion |
|---------|--------|----------|----------|
| Recordatorio sesion 24h | `enviarRecordatoriosSesion($tid, 24)` | No esta en hook_cron | Anadir al cron con timer 1h |
| Recordatorio sesion 1h | `enviarRecordatoriosSesion($tid, 1)` | No esta en hook_cron | Anadir al cron con timer 1h |
| Firma pendiente follow-up | `notificarFirmaPendiente()` | Solo 1 vez al cambiar estado | Cron diario para pendientes >24h |

### 10.3 Lo que Falta

| Feature | Descripcion | Prioridad |
|---------|-------------|-----------|
| Recordatorio firma periodico (24h, 48h, 72h) | Cron que busca firmas pendientes >24h y envia recordatorio | P0 |
| Auto-respuesta a leads (<5 min) | IA genera respuesta automatica al lead entrante | P1 |
| Alerta a formador/orientador sobre baja asistencia | Notificacion cuando participante <75% | P1 |
| Progreso semanal participante | Resumen semanal por email/push | P2 |
| Alerta pre-expiracion bono (60/30/15/7 dias) | Avisos antes de fin de acceso | P1 |
| Sugerencia proactiva copilot por pack | "Tu pack de contenido digital: crea 3 posts esta semana" | P2 |

---

## 11. Billing, Bonos y Kit Digital

### 11.1 Lifecycle del Bono Participante

**Estado Actual:**

```
Participante se registra
  → Free tier via InstitutionalProgram (sponsor PED S.L.)
  → Acceso a verticales via ProgramaVerticalAccessService
  → Fases acogida→insercion: acceso indefinido
  → Fase seguimiento: +6 meses extension
  → Despues: acceso expira
```

**Problema:** No hay avisos previos, no hay transicion a plan pagado, no hay enforcement del limite de 12 meses total.

### 11.2 Enforcement 12 Meses

**Archivo referencia:** `AcuerdoParticipacionService` linea 191: "El programa tiene una duracion maxima de 12 meses desde la fecha de alta."

**Estado:** Solo texto legal. No hay enforcement en codigo.

**Solucion requerida:**
1. Calcular `fecha_inicio_programa + 12 meses = fecha_fin_acceso`
2. Cron diario que evalua participantes proximos a expirar
3. Notificaciones a 60, 30, 15, 7 dias
4. En dia 0: transicion automatica a plan free (con limitaciones)
5. Sugerencia proactiva de plan Starter/Professional via copilot

### 11.3 Transicion Bono → Plan Pagado

**Lo que falta:**

| Componente | Estado | Accion |
|-----------|--------|--------|
| BonoPlanTransitionService | No existe | Crear servicio que gestione la transicion |
| Notificaciones pre-expiracion | No existe | Integrar en cron de alertas |
| Sugerencia de plan por copilot | No existe | Anadir al soft suggestion del participante |
| Landing de upgrade para participantes | No existe | Ruta `/mi-plan/upgrade` con planes recomendados |
| Kit Digital como metodo de pago | Parcial | KitDigitalService existe pero sin flujo desde portal participante |

---

## 12. Metodo Impacto Jaraba y Metasitio jarabaimpact.com

### Estado Actual

El metasitio `jarabaimpact.com` (variant 6, Group ID 6) esta configurado con:
- Domain entity: `domain.record.jarabaimpact_com`
- Hero: "Escala tu negocio digital con la franquicia que ya funciona"
- SEO title: "Franquicia Metodo Jaraba — Ecosistemas de Impacto | Jaraba Impact"
- CTA secundario: "Ver modelo de negocio" → `/metodo`
- Stat: "3 niveles de certificacion"

### Gaps MARKETING-TRUTH-001

| Promesa Marketing | Backend | Estado |
|-------------------|---------|--------|
| "3 niveles de certificacion" | `jaraba_training.settings.yml` → certification_expiry_months: 12 | **Solo config basica, sin sistema de certificacion** |
| "Red de consultores certificados" | No existe | **GAP: Sin directorio de consultores** |
| "Franquicias territoriales con royalties" | No existe | **GAP: Sin sistema de franquicias** |
| "Marketplace de expertos" | No existe | **GAP: Sin marketplace** |
| Ruta `/metodo` | No existe | **GAP: Sin landing Metodo Impacto** |

### Relacion con Andalucia +ei

El **Metodo Impacto Jaraba** es la metodologia que se escala via certificaciones y franquicias. El programa Andalucia +ei es una implementacion de este metodo. La coherencia requiere:

1. El metodo define los 5 Packs como estandar replicable
2. Los participantes del programa aprenden y aplican el metodo
3. Los consultores certificados (ex-participantes exitosos) pueden ofrecer los mismos packs bajo licencia
4. jarabaimpact.com es el hub de propagacion y conversion del metodo

---

## 13. Medidas de Salvaguarda

### 13.1 Salvaguardas Existentes

| Salvaguarda | Proposito | Estado |
|-------------|-----------|--------|
| FaseTransitionManager con prerrequisitos | Impide transiciones sin cumplir requisitos | Implementado |
| AlertasNormativasService (11 alertas) | Detecta riesgos compliance PIIL | Implementado |
| RolProgramaLog (auditoria FSE+) | Log de asignacion/revocacion roles | Implementado |
| AsistenciaComplianceService (75%/80%) | Verifica asistencia minima | Implementado |
| InsercionValidatorService | Valida insercion (4 meses SS) | Implementado |
| SAFEGUARD-CANVAS-001 (4 capas) | Proteccion datos canvas | Implementado |

### 13.2 Salvaguardas Requeridas (Nuevas)

| ID | Salvaguarda | Proposito | Prioridad |
|----|------------|-----------|-----------|
| SAFEGUARD-BONO-EXPIRY-001 | BonoPlanExpiryValidator | Valida que ningun participante excede 12 meses sin transicion | P0 |
| SAFEGUARD-PACK-BILLING-001 | PackBillingIntegrityValidator | Valida que PackServicioEi con `publicado=TRUE` tiene stripe_product_id | P1 |
| SAFEGUARD-PHASE-ACCESS-001 | PhaseAccessValidator | Valida coherencia fase→acceso (formacion no accede CRM, insercion si) | P1 |
| SAFEGUARD-CRON-REMINDER-001 | CronReminderValidator | Valida que enviarRecordatoriosSesion() se invoca desde cron | P0 |
| SAFEGUARD-PILOT-FLOW-001 | PilotClientFlowValidator | Valida flujo completo piloto→cliente pagado | P2 |
| SAFEGUARD-BRIDGE-PHASE-001 | BridgePhaseValidator | Valida que bridges cross-vertical respetan diferenciacion por fase | P1 |

### 13.3 Script de Validacion Recomendado

Crear `scripts/validation/validate-andalucia-ei-catalogo-servicios.php` con:
1. Verificar que los 5 PackServicioEi tipos existen en constantes
2. Verificar que cada pack tiene bridge cross-vertical funcional
3. Verificar que enviarRecordatoriosSesion() se invoca desde cron
4. Verificar que ProgramaVerticalAccessService diferencia fases
5. Verificar que participante_ei tiene wizard steps y daily actions registrados
6. Verificar que BonoPlanTransitionService existe
7. Verificar que copilot bridge tiene ramas para orientador y formador

---

## 14. Tabla de Correspondencia: Especificaciones Tecnicas

| Spec ID | Descripcion | Archivo Referencia | Estado |
|---------|------------|-------------------|--------|
| SPEC-2E-001 | ProgramaParticipanteEi con 40+ campos | Entity/ProgramaParticipanteEi.php | Implementado |
| SPEC-2E-002 | RolProgramaService SSOT | Service/RolProgramaService.php | Implementado |
| SPEC-2E-003 | 29 EntregableFormativoEi auto-seed | Service/PortfolioEntregablesService.php | Implementado |
| SPEC-2E-004 | AsistenciaDetalladaEi dual | Entity/AsistenciaDetalladaEi.php | Implementado |
| SPEC-2E-005 | RolProgramaLog auditoria FSE+ | Entity/RolProgramaLog.php | Implementado |
| SPEC-2E-006 | StaffProfileEi perfiles equipo | Entity/StaffProfileEi.php | Implementado |
| SPEC-2E-007 | CopilotPhaseConfigService 6 modos | Service/CopilotPhaseConfigService.php | Implementado |
| SPEC-2E-008 | PortfolioPublicoController | Controller/PortfolioPublicoController.php | Implementado |
| SPEC-2E-009 | CatalogoPacksService publicacion | Service/CatalogoPacksService.php | Implementado |
| SPEC-2E-010 | ProspeccionPipelineService Kanban | Service/ProspeccionPipelineService.php | Implementado |
| SPEC-2E-011 | CalculadoraPuntoEquilibrioService | Service/CalculadoraPuntoEquilibrioService.php | Implementado |
| SPEC-2E-012 | 12 Cross-Vertical Bridges | Services (12) | Implementado |
| **SPEC-CAT-001** | **Wizard + Daily Actions participante** | **No existe** | **GAP** |
| **SPEC-CAT-002** | **Acceso diferenciado formacion/insercion** | **ProgramaVerticalAccessService** | **GAP** |
| **SPEC-CAT-003** | **CRM basico para clientes del participante** | **No existe** | **GAP** |
| **SPEC-CAT-004** | **Flujo facturacion pack a clientes** | **PackServicioEi campos Stripe** | **GAP** |
| **SPEC-CAT-005** | **Copilot bridge orientador + formador** | **AndaluciaEiCopilotBridgeService** | **GAP** |
| **SPEC-CAT-006** | **Recordatorios sesion en cron** | **EiMultichannelNotificationService** | **GAP** |
| **SPEC-CAT-007** | **Enforcement 12 meses + avisos** | **No existe** | **GAP** |
| **SPEC-CAT-008** | **Flujo cliente piloto completo** | **Parcial** | **GAP** |
| **SPEC-CAT-009** | **Landing /metodo jarabaimpact** | **No existe** | **GAP** |
| **SPEC-CAT-010** | **Plantillas GrapesJS negocios locales** | **No existe** | **GAP** |

---

## 15. Tabla de Cumplimiento de Directrices

| Directriz | Aplica a | Cumplimiento | Notas |
|-----------|---------|-------------|-------|
| TENANT-001 (toda query filtra tenant) | Todas las entities | SI | Verificado en queries |
| TENANT-BRIDGE-001 (TenantBridgeService) | Bridges | SI | Via @? opcional |
| PREMIUM-FORMS-PATTERN-001 | Entity forms | SI | PremiumEntityFormBase |
| CONTROLLER-READONLY-001 | Controllers | SI | Sin readonly en herencia |
| CSS-VAR-ALL-COLORS-001 | SCSS | Pendiente verificar | Nuevos componentes |
| ICON-CONVENTION-001 | Templates | SI | jaraba_icon() |
| ICON-DUOTONE-001 | Iconos | SI | Default duotone |
| ZERO-REGION-001 | Dashboards | SI | clean_content pattern |
| SLIDE-PANEL-RENDER-002 | Formularios | SI | _controller: pattern |
| TWIG-INCLUDE-ONLY-001 | Parciales | SI | `only` keyword |
| ROUTE-LANGPREFIX-001 | URLs | SI | Url::fromRoute() |
| SETUP-WIZARD-DAILY-001 | Todos los roles | **PARCIAL** | **Falta participante** |
| PIPELINE-E2E-001 | Todos los features | **PARCIAL** | **Bridges sin UI** |
| RUNTIME-VERIFY-001 | Post-implementacion | Pendiente | Nuevos features |
| SCSS-COMPILE-VERIFY-001 | SCSS | Pendiente | Nuevos componentes |
| MARKETING-TRUTH-001 | jarabaimpact.com | **NO** | **Promesas sin backend** |
| ENTITY-PREPROCESS-001 | Entities con view mode | **PARCIAL** | **PackServicioEi, EntregableFormativoEi sin preprocess** |
| UPDATE-HOOK-REQUIRED-001 | Nuevas entities/campos | Pendiente | Nuevos campos |

---

## 16. Score Detallado y Roadmap

### Score Actual por Dimension (sobre 10)

| # | Dimension | Score | Peso | Ponderado |
|---|-----------|-------|------|-----------|
| 1 | Modelo de datos (entities) | 9.5 | 10% | 0.95 |
| 2 | Roles y permisos | 9.0 | 8% | 0.72 |
| 3 | Setup Wizard staff | 9.0 | 5% | 0.45 |
| 4 | **Setup Wizard participante** | **0.0** | **10%** | **0.00** |
| 5 | **Daily Actions participante** | **0.0** | **10%** | **0.00** |
| 6 | Cross-vertical bridges | 8.5 | 8% | 0.68 |
| 7 | **IA proactiva todos roles** | **5.0** | **12%** | **0.60** |
| 8 | **Recordatorios automaticos** | **2.0** | **8%** | **0.16** |
| 9 | **Acceso diferenciado por fase** | **3.0** | **10%** | **0.30** |
| 10 | **Pack → Herramienta mapping** | **4.0** | **8%** | **0.32** |
| 11 | Billing/bonos lifecycle | 6.0 | 6% | 0.36 |
| 12 | Metodo Impacto backend | 2.0 | 5% | 0.10 |
| | **TOTAL PONDERADO** | | **100%** | **4.64/10** |

### Roadmap hacia 10/10

**Sprint E (P0 — 1-2 dias):**
- Wizard + Daily Actions participante_ei (8 steps + 7 actions)
- Conectar enviarRecordatoriosSesion() a cron
- Recordatorio periodico firmas pendientes

**Sprint F (P1 — 2-3 dias):**
- Copilot bridge orientador + formador
- ProgramaVerticalAccessService con diferenciacion formacion/insercion
- Enforcement 12 meses + avisos pre-expiracion
- Contexto de pack en copilot participante

**Sprint G (P2 — 3-4 dias):**
- CRM basico para clientes del participante
- Flujo facturacion pack a clientes (Stripe activation)
- Flujo cliente piloto completo
- Plantillas GrapesJS negocios locales

**Sprint H (P3 — 4-5 dias):**
- Landing /metodo para jarabaimpact.com
- GroundingProvider para Empleabilidad
- Auto-respuesta IA a leads
- Dashboard metricas negocio por pack
- Google Business integration (investigar API)

---

## 17. Glosario

| Sigla | Significado |
|-------|------------|
| PIIL | Programas Integrales para la Insercion Laboral |
| FSE+ | Fondo Social Europeo Plus |
| STO | Servicio de Teleformacion y Orientacion (SEPE) |
| DACI | Declaracion de Aceptacion de Compromisos Individual |
| DIME | Diagnostico Individual de Madurez Emprendedora |
| ICV | Itinerarios de Cualificacion y Validacion |
| CNAE | Clasificacion Nacional de Actividades Economicas |
| IAE | Impuesto de Actividades Economicas |
| SS | Seguridad Social |
| PE | Punto de Equilibrio |
| SROI | Social Return on Investment |
| VoBo | Visto Bueno |
| SAE | Servicio Andaluz de Empleo |
| BMC | Business Model Canvas |
| CRM | Customer Relationship Management |
| SSOT | Single Source of Truth |
| DI | Dependency Injection |
| SCSS | Sassy CSS (preprocesador CSS) |
| GrapesJS | Editor visual web de codigo abierto |
| Qdrant | Motor de busqueda vectorial |
| PWA | Progressive Web App |
