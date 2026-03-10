# Plan Maestro: Andalucía +ei Clase Mundial
# Firma Electrónica × Emprendimiento Inclusivo × Matching × Adaptación por Colectivo

> **Fecha**: 2026-03-10
> **Versión**: 2.0.0
> **Módulo principal**: `jaraba_andalucia_ei`
> **Módulos transversales**: `ecosistema_jaraba_core`, `jaraba_legal_vault`, `jaraba_matching`,
> `jaraba_job_board`, `jaraba_candidate`, `jaraba_business_tools`, `jaraba_lms`, `jaraba_pwa`
> **Estado**: Planificado
> **Prioridad**: P0 — Cumplimiento normativo PIIL CV 2025 + Diferenciación clase mundial

---

## Índice de Navegación (TOC)

### PARTE I — Firma Electrónica (Cumplimiento Normativo PIIL)

1. [Diagnóstico: Estado Actual y Gaps](#1-diagnóstico-estado-actual-y-gaps)
   1. [Lo que existe](#11-lo-que-existe)
   2. [Gaps críticos identificados](#12-gaps-críticos-identificados)
   3. [Diagrama de flujo actual vs objetivo](#13-diagrama-de-flujo-actual-vs-objetivo)
2. [Arquitectura Objetivo](#2-arquitectura-objetivo)
   1. [Principios de diseño](#21-principios-de-diseño)
   2. [Componentes nuevos](#22-componentes-nuevos)
   3. [Flujo end-to-end](#23-flujo-end-to-end)
3. [Sprint 1 — FirmaWorkflowService + Bridge AutoFirma↔ExpedienteDocumento](#3-sprint-1)
   1. [FirmaWorkflowService](#31-firmaworkflowservice)
   2. [Bridge: adaptación AutoFirmaController para ContentEntities](#32-bridge-autofirmacontroller)
   3. [Máquina de estados de firma](#33-máquina-de-estados)
   4. [Tabla de auditoría dedicada](#34-tabla-de-auditoría-dedicada)
4. [Sprint 2 — Firma Táctil Móvil (SignaturePad)](#4-sprint-2)
   1. [Componente JS SignaturePad](#41-componente-js-signaturepad)
   2. [FirmaTactilController](#42-firmatactilcontroller)
   3. [Integración con slide-panel](#43-integración-slide-panel)
   4. [SCSS y accesibilidad](#44-scss-y-accesibilidad)
5. [Sprint 3 — Flujos de Firma por Tipo de Documento](#5-sprint-3)
   1. [Acuerdo de Participación](#51-acuerdo-de-participación)
   2. [DACI](#52-daci)
   3. [Hojas de Servicio (firma dual)](#53-hojas-de-servicio)
   4. [Recibí Incentivo / Renuncia Incentivo](#54-recibí-incentivo--renuncia-incentivo)
   5. [Recibos de Actuaciones STO](#55-recibos-de-actuaciones-sto)
6. [Sprint 4 — Dashboard de Firma y Notificaciones](#6-sprint-4)
   1. [Panel de firma pendiente (participante)](#61-panel-de-firma-pendiente)
   2. [Dashboard de firma masiva (orientador/coordinador)](#62-dashboard-de-firma-masiva)
   3. [Notificaciones push y email](#63-notificaciones-push-y-email)
7. [Sprint 5 — Verificación, QR y Sellado Temporal](#7-sprint-5)
   1. [QR de verificación en PDFs firmados](#71-qr-de-verificación)
   2. [Endpoint público de verificación](#72-endpoint-público-de-verificación)
   3. [Sellado temporal TSA (FNMT)](#73-sellado-temporal-tsa)
   4. [Certificado de sello de empresa](#74-certificado-de-sello-de-empresa)
8. [Sprint 6 — Tests y Validación Firma](#8-sprint-6)

### PARTE II — Emprendimiento Inclusivo Clase Mundial (+ei)

9. [Diagnóstico de Infraestructura Existente](#9-diagnóstico-de-infraestructura-existente)
   1. [Mapa de módulos ya construidos](#91-mapa-de-módulos-ya-construidos)
   2. [Lo que existe pero no está conectado](#92-lo-que-existe-pero-no-está-conectado)
   3. [Lo que realmente falta](#93-lo-que-realmente-falta)
10. [Sprint 7 — Itinerario Dual Empleo/Emprendimiento](#10-sprint-7)
    1. [PlanEmprendimientoEi entity](#101-planemprendimientoei-entity)
    2. [Integración con jaraba_business_tools](#102-integración-business-tools)
    3. [Hitos de emprendimiento diferenciados](#103-hitos-emprendimiento)
    4. [Copilot contextual por vía](#104-copilot-contextual)
11. [Sprint 8 — Motor de Adaptación por Colectivo y Barreras](#11-sprint-8)
    1. [BarreraAcceso entity o campo estructurado](#111-barrera-acceso)
    2. [AdaptacionItinerarioService](#112-adaptacion-itinerario-service)
    3. [Derivación automática a servicios sociales](#113-derivación-automática)
    4. [Copilot sensible al colectivo](#114-copilot-sensible)
12. [Sprint 9 — Matching Bidireccional Participante↔Empresa](#12-sprint-9)
    1. [Bridge andalucia_ei ↔ jaraba_matching](#121-bridge-matching)
    2. [Bridge andalucia_ei ↔ jaraba_job_board](#122-bridge-job-board)
    3. [Portal empresa (vista participantes anonimizados)](#123-portal-empresa)
    4. [Matching IA con Qdrant para +ei](#124-matching-ia-qdrant)
13. [Sprint 10 — Red Alumni Viva](#13-sprint-10)
    1. [Comunidad Alumni digital](#131-comunidad-alumni)
    2. [Mentoring peer-to-peer (alumni → participante)](#132-mentoring-peer)
    3. [Historias de éxito conectadas a datos reales](#133-historias-éxito)
14. [Sprint 11 — Gamificación Significativa e Impacto Verificable](#14-sprint-11)
    1. [Integración PuntosImpactoEi → BadgeAwardService](#141-badges)
    2. [Micro-aprendizaje diario (jaraba_lms bridge)](#142-micro-aprendizaje)
    3. [Dashboard público de transparencia](#143-dashboard-transparencia)
    4. [Indicadores IRIS+ y SROI](#144-iris-sroi)
15. [Sprint 12 — Accesibilidad WCAG 2.1 AA Completa](#15-sprint-12)
    1. [Auditoría de templates andalucia_ei](#151-auditoría-templates)
    2. [Push notifications para firma y programa](#152-push-notifications)
    3. [Adaptación idiomática para colectivos](#153-adaptación-idiomática)

### PARTE III — Tablas de Correspondencia y Cumplimiento

16. [Tabla de Correspondencia: Especificaciones Técnicas](#16-tabla-de-correspondencia)
17. [Tabla de Cumplimiento de Directrices](#17-tabla-de-cumplimiento-de-directrices)

---

## 1. Diagnóstico: Estado Actual y Gaps

### 1.1. Lo que existe

El SaaS dispone de una infraestructura de firma electrónica madura pero **desconectada**
del flujo documental del vertical Andalucía +ei:

| Componente | Ubicación | Estado | Problema |
|---|---|---|---|
| `FirmaDigitalService` | `ecosistema_jaraba_core` | Producción | Firma server-side con PKCS#12 + TSA (FNMT). **No invocado** por ningún servicio de andalucia_ei |
| `AutoFirmaController` | `ecosistema_jaraba_core` | Producción | 5 endpoints REST para AutoFirma desktop. **Opera con nodos `documento_firma`**, no con `ExpedienteDocumento` |
| `BrandedPdfService` | `ecosistema_jaraba_core` | Producción | Genera PDFs con branding tenant. **Usado por andalucia_ei** como dependencia opcional |
| `CertificateManagerService` | `ecosistema_jaraba_core` | Producción | Gestión PKCS#12 multi-tenant. **Funcional** |
| `ExpedienteDocumento` | `jaraba_andalucia_ei` | Producción | Tiene campos `firmado`, `firma_fecha`, `firma_certificado_info`. **Nunca se actualizan** |
| `ActuacionSto` | `jaraba_andalucia_ei` | Producción | Tiene `firmado_participante`, `firmado_orientador`. **Son booleans sin mecanismo de recogida** |
| `AcuerdoParticipacionService` | `jaraba_andalucia_ei` | Producción | Genera PDF + crea `ExpedienteDocumento`. **No invoca firma** |
| `DaciService` | `jaraba_andalucia_ei` | Producción | Genera PDF + crea `ExpedienteDocumento`. **No invoca firma** |
| `ReciboServicioService` | `jaraba_andalucia_ei` | Producción | Genera recibo por actuación. **No invoca firma** |
| `IncentiveReceiptService` | `jaraba_andalucia_ei` | Producción | Genera recibí €528. **No invoca firma** |
| `IncentiveWaiverService` | `jaraba_andalucia_ei` | Producción | Genera renuncia €528. **No invoca firma** |
| `HojaServicioMentoriaService` | `jaraba_andalucia_ei` | Producción | Genera hoja de servicio. **No invoca firma** |
| `jaraba_legal_vault` | Módulo separado | Producción | Almacenamiento cifrado con auditoría. **Integrado** via `archivo_vault_id` |
| Librería JS `autofirma` | `ecosistema_jaraba_core` | Registrada | Cliente WebSocket AutoFirma. **Sin UI de invocación** desde andalucia_ei |

### 1.2. Gaps Críticos Identificados

#### GAP-1: Desconexión AutoFirma ↔ ExpedienteDocumento (P0)

El `AutoFirmaController` opera exclusivamente con nodos (`Node`) de tipo `documento_firma`.
Los documentos de Andalucía +ei son `ExpedienteDocumento` (ContentEntity custom).
**No hay puente** que permita firmar un `ExpedienteDocumento` via AutoFirma.

**Impacto**: Los 6 tipos de documento del programa (Acuerdo, DACI, Hojas de Servicio,
Recibí, Renuncia, Recibos de Actuaciones) se generan como PDF pero **nunca se firman**
electrónicamente. La firma se queda como un booleano `TRUE` puesto por código.

#### GAP-2: Sin firma táctil/móvil (P0)

AutoFirma requiere la app de escritorio (`firmaelectronica.gob.es`).
Los participantes de un programa de inclusión usan mayoritariamente **móvil**.
No existe componente de firma manuscrita táctil (signature pad) en el frontend.

**Impacto**: El participante no puede firmar desde el portal `/andalucia-ei/mi-participacion`.
El orientador no puede recoger firmas in-situ durante sesiones presenciales.

#### GAP-3: Sin workflow de firma (P1)

Los documentos saltan de `generado` a `firmado=TRUE` sin estados intermedios.
No hay: pendiente de firma, enviado a firma, firmado parcialmente (dual-signature),
rechazado, caducado.

**Impacto**: No es posible trackear el ciclo de vida de firma para auditoría PIIL.
La Junta de Andalucía exige trazabilidad de firma en documentos STO.

#### GAP-4: Sin firma dual coordinada (P1)

Las Hojas de Servicio y Recibos de Actuaciones STO requieren **dos firmas**:
participante + orientador/técnico. Actualmente `firmado_participante` y `firmado_orientador`
son booleans independientes sin mecanismo de coordinación.

**Impacto**: No hay garantía de que ambas partes hayan firmado. No hay notificación
cruzada (el orientador firma, se notifica al participante y viceversa).

#### GAP-5: Sin notificaciones de firma pendiente (P2)

Cuando se genera un documento que requiere firma, nadie recibe notificación.
El participante no sabe que tiene documentos pendientes.

#### GAP-6: Sin verificación QR en documentos firmados (P2)

Los PDFs firmados no incluyen un QR de verificación que permita a terceros
(Junta de Andalucía, SAE, auditoría FSE+) comprobar la autenticidad del documento.

#### GAP-7: Sin sellado temporal real en documentos andalucia_ei (P2)

`FirmaDigitalService` soporta TSA (FNMT + FreeTSA) pero los servicios de
andalucia_ei no lo invocan. Los documentos carecen de sello temporal con validez legal.

### 1.3. Diagrama de Flujo Actual vs Objetivo

**FLUJO ACTUAL (roto)**:
```
Servicio genera PDF → Crea ExpedienteDocumento → firmado=TRUE (hardcoded) → FIN
                                                  ↑ NO hay firma real
```

**FLUJO OBJETIVO (completo)**:
```
Servicio genera PDF
  → BrandedPdfService (branding tenant)
  → ExpedienteDocumento(estado_firma='borrador')
  → FirmaWorkflowService::solicitarFirma()
    → estado_firma='pendiente_firma'
    → Notificación al firmante (push + email)
    → Firmante abre desde portal (slide-panel)
    → Opción A: AutoFirma (desktop, certificado digital)
    → Opción B: Firma táctil (móvil, signature pad)
    → FirmaWorkflowService::procesarFirma()
      → Validación de firma
      → FirmaDigitalService::sellarConTSA() (sellado temporal)
      → QR de verificación insertado en PDF
      → ExpedienteDocumento(firmado=TRUE, firma_fecha, firma_certificado_info)
      → Vault cifrado actualizado
      → Auditoría registrada
      → Si dual-signature: notificar al segundo firmante
      → estado_firma='firmado' (o 'firmado_parcial' si falta co-firma)
```

---

## 2. Arquitectura Objetivo

### 2.1. Principios de Diseño

1. **El código existe ≠ el usuario lo experimenta** (RUNTIME-VERIFY-001).
   Cada componente será verificado end-to-end, desde el PHP hasta el DOM final.

2. **Móvil primero**. El 70%+ de participantes de programas de inclusión usan móvil.
   Toda la UX de firma DEBE funcionar en pantalla táctil ≤ 375px.

3. **Dual-path**: AutoFirma (certificado cualificado, nivel alto eIDAS) +
   Firma táctil (nivel básico, válida para documentos internos del programa).

4. **Zero Region** (ZERO-REGION-001). Las páginas de firma se renderizan limpias,
   sin bloques de Drupal, con template Twig dedicado.

5. **Slide-panel** (SLIDE-PANEL-RENDER-001). Los formularios de firma se abren
   en slide-panel para no sacar al usuario de su contexto.

6. **Multi-tenant safe** (TENANT-001). Toda query filtra por tenant.
   Los certificados son por tenant. Los documentos firmados aislados.

7. **Auditoría trazable** (LCIS-AUDIT-001). Cada firma genera registro inmutable
   con: quién, cuándo, qué, desde dónde, con qué certificado, hash del documento.

### 2.2. Componentes Nuevos

| Componente | Tipo | Módulo | Descripción |
|---|---|---|---|
| `FirmaWorkflowService` | Service | `jaraba_andalucia_ei` | Orquestador del ciclo de vida de firma. Máquina de estados. |
| `FirmaDocumentoController` | Controller | `jaraba_andalucia_ei` | Endpoints API para flujo de firma (solicitar, firmar, verificar, rechazar) |
| `FirmaTactilController` | Controller | `jaraba_andalucia_ei` | Renderiza formulario de firma táctil en slide-panel |
| `firma-electronica` | Library (JS) | `jaraba_andalucia_ei` | Cliente JS: signature pad + integración AutoFirma |
| `_firma-documento.html.twig` | Partial Twig | `jaraba_andalucia_ei` | Componente visual de firma (status badge, botón firmar, info certificado) |
| `_firma-pad.html.twig` | Partial Twig | `jaraba_andalucia_ei` | Componente signature pad táctil |
| `_firma-pendientes.html.twig` | Partial Twig | `jaraba_andalucia_ei` | Widget de firmas pendientes para portal participante |
| `_firma-masiva.html.twig` | Partial Twig | `jaraba_andalucia_ei` | Panel de firma masiva para orientador/coordinador |
| `_firmas.scss` | SCSS parcial | `jaraba_andalucia_ei` | Estilos del sistema de firma |
| `hook_update_10015` | Install hook | `jaraba_andalucia_ei` | Campos estado_firma, firma_ip, firma_dispositivo en ExpedienteDocumento |
| `firma_audit_log` | DB Table | `jaraba_andalucia_ei` | Tabla de auditoría de firma inmutable |
| Ruta verificación QR | Route pública | `jaraba_andalucia_ei` | `/verificar/{hash}` — verificación pública de documento firmado |

### 2.3. Flujo End-to-End por Actor

#### Participante (móvil, portal `/andalucia-ei/mi-participacion`)

1. Orientador genera Acuerdo de Participación desde dashboard coordinador
2. `AcuerdoParticipacionService::generar()` crea PDF + `ExpedienteDocumento`
3. `FirmaWorkflowService::solicitarFirma()` → estado `pendiente_firma`
4. Notificación push (PWA) + email al participante: "Tienes un documento pendiente de firma"
5. Participante abre portal → ve widget "1 documento pendiente de firma"
6. Click → slide-panel con preview del PDF + botón "Firmar"
7. Signature pad táctil se despliega (canvas HTML5, touch events)
8. Participante firma con el dedo → imagen PNG de la firma
9. JS envía firma al server → `FirmaDocumentoController::firmarTactil()`
10. Server: inserta firma manuscrita en PDF, aplica sello TSA, genera QR
11. PDF firmado almacenado en vault cifrado
12. `ExpedienteDocumento` actualizado: `firmado=TRUE`, `firma_fecha`, `firma_certificado_info`
13. Registro de auditoría: IP, User-Agent, hash documento, timestamp
14. Portal muestra "Firmado" con badge verde

#### Orientador (desktop/tablet, dashboard `/andalucia-ei/coordinador`)

1. Completa sesión de orientación → genera Hoja de Servicio
2. `ReciboServicioService::generar()` crea PDF + `ExpedienteDocumento` + `ActuacionSto`
3. `FirmaWorkflowService::solicitarFirmaDual()` → estado `pendiente_firma_tecnico`
4. Orientador firma primero (AutoFirma si tiene certificado, o táctil si presencial)
5. Estado cambia a `pendiente_firma_participante`
6. Notificación al participante
7. Participante firma → estado `firmado`
8. Ambas firmas trazables en auditoría

#### Coordinador (firma masiva)

1. Abre panel de firma masiva en dashboard
2. Ve lista filtrable de documentos pendientes de firma del sello de empresa
3. Selecciona documentos (checkbox) → "Firmar seleccionados con sello de empresa"
4. `FirmaDigitalService::firmarPdf()` aplica sello PKCS#12 del tenant
5. Todos los documentos actualizados en batch
6. Resumen: "15 documentos firmados con sello de [Entidad]"

---

## 3. Sprint 1 — FirmaWorkflowService + Bridge AutoFirma↔ExpedienteDocumento

### 3.1. FirmaWorkflowService

**Archivo**: `src/Service/FirmaWorkflowService.php`

Orquestador central del ciclo de vida de firma de documentos. Gestiona la máquina
de estados, coordina con `FirmaDigitalService` para sellado, con `jaraba_legal_vault`
para almacenamiento cifrado, y con el sistema de notificaciones.

**Responsabilidades**:
- Solicitar firma simple o dual para un `ExpedienteDocumento`
- Transicionar estados de firma con validaciones
- Coordinar firma dual (participante + técnico) con orden configurable
- Aplicar sello TSA post-firma
- Insertar firma manuscrita (PNG) en PDF
- Generar QR de verificación
- Registrar auditoría inmutable
- Notificar firmantes pendientes

**Dependencias (inyección)**:
```
jaraba_andalucia_ei.firma_workflow:
  class: Drupal\jaraba_andalucia_ei\Service\FirmaWorkflowService
  arguments:
    - '@entity_type.manager'
    - '@logger.channel.jaraba_andalucia_ei'
    - '@?ecosistema_jaraba_core.firma_digital'
    - '@?ecosistema_jaraba_core.branded_pdf'
    - '@?ecosistema_jaraba_core.tenant_context'
    - '@?jaraba_legal_vault.document_vault'
    - '@?jaraba_support.notification'
```

**Métodos principales**:

| Método | Parámetros | Retorno | Descripción |
|---|---|---|---|
| `solicitarFirma()` | `int $documentoId, int $firmanteUid, string $tipo` | `array` | Inicia workflow de firma simple |
| `solicitarFirmaDual()` | `int $documentoId, int $tecnicoUid, int $participanteUid` | `array` | Inicia workflow de firma dual (técnico primero) |
| `procesarFirmaTactil()` | `int $documentoId, string $firmaBase64, int $firmanteUid` | `array` | Procesa firma manuscrita táctil |
| `procesarFirmaAutofirma()` | `int $documentoId, string $pdfFirmadoBase64, array $certInfo` | `array` | Procesa firma vía AutoFirma (PAdES) |
| `procesarFirmaSello()` | `int $documentoId` | `array` | Aplica sello de empresa (PKCS#12 del tenant) |
| `rechazarFirma()` | `int $documentoId, int $uid, string $motivo` | `array` | Rechaza firma con motivo |
| `getEstadoFirma()` | `int $documentoId` | `array` | Estado actual + firmantes + historial |
| `getDocumentosPendientes()` | `int $uid, ?int $tenantId` | `array` | Lista documentos pendientes de firma de un usuario |
| `verificarDocumento()` | `string $hash` | `array` | Verificación pública por hash (para QR) |

### 3.2. Bridge: Adaptación AutoFirmaController para ContentEntities

**Problema**: `AutoFirmaController` solo trabaja con `Node` tipo `documento_firma`.
**Solución**: No reescribir el controller. Crear un **adapter** en `jaraba_andalucia_ei`
que traduce `ExpedienteDocumento` al contrato esperado por AutoFirma JS.

**Archivo**: `src/Controller/FirmaDocumentoController.php`

**Rutas nuevas** (en `jaraba_andalucia_ei.routing.yml`):

```yaml
jaraba_andalucia_ei.firma.documento:
  path: '/api/v1/andalucia-ei/firma/documento/{expediente_documento}'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\FirmaDocumentoController::getDocumentoParaFirma'
  requirements:
    _permission: 'access andalucia ei participante portal'
    _csrf_request_header_token: 'TRUE'
    expediente_documento: '\d+'

jaraba_andalucia_ei.firma.firmar_tactil:
  path: '/api/v1/andalucia-ei/firma/firmar-tactil'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\FirmaDocumentoController::firmarTactil'
  methods: [POST]
  requirements:
    _permission: 'access andalucia ei participante portal'
    _csrf_request_header_token: 'TRUE'

jaraba_andalucia_ei.firma.firmar_autofirma:
  path: '/api/v1/andalucia-ei/firma/firmar-autofirma'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\FirmaDocumentoController::firmarAutofirma'
  methods: [POST]
  requirements:
    _permission: 'use digital signature'
    _csrf_request_header_token: 'TRUE'

jaraba_andalucia_ei.firma.firmar_sello:
  path: '/api/v1/andalucia-ei/firma/firmar-sello'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\FirmaDocumentoController::firmarSello'
  methods: [POST]
  requirements:
    _permission: 'administer jaraba_andalucia_ei'
    _csrf_request_header_token: 'TRUE'

jaraba_andalucia_ei.firma.rechazar:
  path: '/api/v1/andalucia-ei/firma/rechazar'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\FirmaDocumentoController::rechazar'
  methods: [POST]
  requirements:
    _permission: 'access andalucia ei participante portal'
    _csrf_request_header_token: 'TRUE'

jaraba_andalucia_ei.firma.estado:
  path: '/api/v1/andalucia-ei/firma/estado/{expediente_documento}'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\FirmaDocumentoController::getEstado'
  requirements:
    _permission: 'access andalucia ei participante portal'
    _csrf_request_header_token: 'TRUE'
    expediente_documento: '\d+'

jaraba_andalucia_ei.firma.pendientes:
  path: '/api/v1/andalucia-ei/firma/pendientes'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\FirmaDocumentoController::getPendientes'
  requirements:
    _permission: 'access andalucia ei participante portal'
    _csrf_request_header_token: 'TRUE'

jaraba_andalucia_ei.firma.slide_panel:
  path: '/andalucia-ei/firma/{expediente_documento}'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\FirmaTactilController::firmaPanel'
    _title: 'Firmar Documento'
  requirements:
    _permission: 'access andalucia ei participante portal'
    expediente_documento: '\d+'

jaraba_andalucia_ei.verificar_documento:
  path: '/verificar/{hash}'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\FirmaDocumentoController::verificarPublico'
    _title: 'Verificación de Documento'
  requirements:
    _access: 'TRUE'
```

### 3.3. Máquina de Estados de Firma

**Campo nuevo en `ExpedienteDocumento`**: `estado_firma` (string, list)

```
Valores:
  borrador          → Documento generado, sin solicitud de firma
  pendiente_firma   → Firma solicitada (simple)
  pendiente_firma_tecnico    → Dual: esperando firma del técnico
  pendiente_firma_participante → Dual: técnico ya firmó, esperando participante
  firmado_parcial   → Una de las dos firmas dual completada
  firmado           → Todas las firmas completadas
  rechazado         → Firma rechazada con motivo
  caducado          → Plazo de firma expirado (configurable, default 30 días)
```

**Transiciones válidas**:
```
borrador → pendiente_firma | pendiente_firma_tecnico
pendiente_firma → firmado | rechazado | caducado
pendiente_firma_tecnico → firmado_parcial | rechazado | caducado
firmado_parcial → pendiente_firma_participante
pendiente_firma_participante → firmado | rechazado | caducado
rechazado → pendiente_firma (re-solicitud)
caducado → pendiente_firma (re-solicitud)
```

**Campos nuevos en `ExpedienteDocumento`** (Sprint 1, `hook_update_10015`):

| Campo | Tipo | Descripción |
|---|---|---|
| `estado_firma` | `list_string` | Estado en la máquina de estados (default: `borrador`) |
| `firma_solicitada_fecha` | `datetime` | Cuándo se solicitó la firma |
| `firma_solicitante_uid` | `entity_reference(user)` | Quién solicitó la firma |
| `firma_ip` | `string(45)` | IP del firmante (IPv4/IPv6) |
| `firma_user_agent` | `string(512)` | User-Agent del firmante |
| `firma_metodo` | `list_string` | `tactil`, `autofirma`, `sello_empresa` |
| `firma_hash_documento` | `string(64)` | SHA-256 del PDF en el momento de firma |
| `co_firmante_uid` | `entity_reference(user)` | Segundo firmante (dual-signature) |
| `co_firma_fecha` | `datetime` | Fecha de la co-firma |
| `co_firma_metodo` | `list_string` | Método de la co-firma |
| `verificacion_hash` | `string(64)` | Hash público para QR de verificación |
| `verificacion_qr_uri` | `string(255)` | URI del QR generado |

### 3.4. Tabla de Auditoría Dedicada

**Tabla**: `jaraba_andalucia_ei_firma_audit`

Registra de forma **inmutable** cada evento del ciclo de firma.
No se usa la tabla genérica de log (que rota). Esta tabla es permanente
para cumplimiento de auditoría FSE+ y PIIL.

**Schema** (en `hook_schema()` ampliación):

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | serial | PK autoincremental |
| `documento_id` | int unsigned | FK a `expediente_documento.id` |
| `participante_id` | int unsigned | FK a `programa_participante_ei.id` |
| `tenant_id` | int unsigned | FK al grupo tenant |
| `accion` | varchar(50) | `firma_solicitada`, `firma_completada`, `firma_rechazada`, `sello_aplicado`, `verificacion` |
| `actor_uid` | int unsigned | UID del actor |
| `actor_nombre` | varchar(255) | Nombre del actor (desnormalizado para audit) |
| `metodo_firma` | varchar(20) | `tactil`, `autofirma`, `sello_empresa` |
| `hash_documento` | varchar(64) | SHA-256 del PDF firmado |
| `certificado_cn` | varchar(255) | CN del certificado (si AutoFirma/sello) |
| `certificado_issuer` | varchar(255) | Issuer del certificado |
| `certificado_serial` | varchar(128) | Serial number del certificado |
| `ip_address` | varchar(45) | IP del firmante |
| `user_agent` | varchar(512) | User-Agent |
| `motivo_rechazo` | text | Si accion = `firma_rechazada` |
| `metadata` | text (JSON) | Datos adicionales (coordenadas firma táctil, etc.) |
| `created` | int | Unix timestamp |

**Índices**:
- `idx_documento_id` → búsqueda por documento
- `idx_participante_id` → búsqueda por participante
- `idx_tenant_id` → aislamiento multi-tenant
- `idx_accion_created` → cronología de acciones
- `idx_hash_documento` → verificación por hash

---

## 4. Sprint 2 — Firma Táctil Móvil (SignaturePad)

### 4.1. Componente JS SignaturePad

**Archivo**: `js/firma-electronica.js`

Componente Vanilla JS (sin React/Vue — directriz del proyecto) basado en
`Drupal.behaviors` con soporte para:

- Canvas HTML5 con eventos `touchstart/touchmove/touchend` y `mousedown/mousemove/mouseup`
- Resolución mínima del canvas: 600×200px (se escala al contenedor)
- Línea suave con interpolación Bézier (evitar pixelado en trazo rápido)
- Grosor de trazo: 2.5px (optimizado para dedo en pantalla)
- Color de trazo: `var(--ej-color-corporate, #233D63)` (azul corporativo)
- Botones: "Limpiar" (borra canvas) y "Firmar" (captura PNG y envía)
- Indicador visual de que la firma es obligatoria antes de poder confirmar
- Accesibilidad: `role="img"`, `aria-label`, focus ring para navegación por teclado

**Patrón JS**:
```javascript
(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.jarabaFirmaElectronica = {
    attach: function (context) {
      // ... SignaturePad logic
      // URLs via drupalSettings (ROUTE-LANGPREFIX-001)
      // CSRF token cacheado (CSRF-JS-CACHE-001)
      // Drupal.t() para textos (traducible)
      // Drupal.checkPlain() para datos de API (INNERHTML-XSS-001)
    }
  };
})(Drupal, drupalSettings);
```

**Librería** (en `jaraba_andalucia_ei.libraries.yml`):
```yaml
firma-electronica:
  css:
    component:
      css/firma-electronica.css: {}
  js:
    js/firma-electronica.js: {}
  dependencies:
    - core/drupal
    - core/drupalSettings
    - core/once
```

### 4.2. FirmaTactilController

**Archivo**: `src/Controller/FirmaTactilController.php`

Renderiza el slide-panel de firma con preview del PDF y signature pad.
Sigue SLIDE-PANEL-RENDER-001: usa `renderPlain()`, no `render()`.

**Ruta**: `/andalucia-ei/firma/{expediente_documento}`

**Template**: `templates/partials/_firma-pad.html.twig`

**drupalSettings inyectados** (via `hook_preprocess_page()` o controller):
```php
$variables['#attached']['drupalSettings']['jarabaFirma'] = [
  'firmarTactilUrl' => Url::fromRoute('jaraba_andalucia_ei.firma.firmar_tactil')->toString(),
  'firmarAutofirmaUrl' => Url::fromRoute('jaraba_andalucia_ei.firma.firmar_autofirma')->toString(),
  'documentoId' => $expediente_documento->id(),
  'documentoTitulo' => $expediente_documento->label(),
  'requiereDualFirma' => $this->requiereDualFirma($expediente_documento),
  'csrfToken' => \Drupal::csrfToken()->get('session'),
];
```

### 4.3. Integración con Slide-Panel

La firma se abre SIEMPRE en slide-panel (directriz del proyecto: toda acción
crear/editar/ver en frontend se abre en modal/slide-panel).

**Detección**: `isSlidePanelRequest()` = `isXmlHttpRequest() && !_wrapper_format`

**Flujo UX**:
1. Participante ve widget "Documentos pendientes de firma" en portal
2. Click en "Firmar" → fetch async al controller
3. Controller detecta slide-panel → `renderPlain()` del template
4. Slide-panel se desliza desde la derecha (480px, z-index 1050)
5. Contenido: preview PDF (thumbnail o primer página) + signature pad
6. Participante firma → canvas captura PNG → POST al server
7. Slide-panel muestra confirmación → se cierra
8. Widget se actualiza (AJAX o reload parcial)

### 4.4. SCSS y Accesibilidad

**Archivo**: `scss/_firmas.scss`

```scss
@use '../variables' as *;

// SCSS-001: Cada parcial incluye @use de variables.
// CSS-VAR-ALL-COLORS-001: Todos los colores via var(--ej-*).
// SCSS-COLORMIX-001: Alpha via color-mix(), no rgba().

.firma-pad {
  // ... estilos con var(--ej-*)
}

.firma-pad__canvas {
  border: 2px dashed var(--ej-border-color, #E5E7EB);
  border-radius: var(--ej-radius-md, 8px);
  background: var(--ej-color-surface, #FFFFFF);
  touch-action: none; // Prevenir scroll al firmar
  cursor: crosshair;

  &:focus-visible {
    outline: 3px solid var(--ej-color-impulse, #FF8C42);
    outline-offset: 2px;
  }
}

.firma-pad__actions {
  display: flex;
  gap: var(--ej-spacing-sm, 0.5rem);
  margin-block-start: var(--ej-spacing-md, 1rem);
}
```

**Compilación** (SCSS-COMPILE-VERIFY-001):
```bash
cd web/themes/custom/ecosistema_jaraba_theme && npm run build
```

**Accesibilidad WCAG 2.1 AA**:
- Canvas tiene `role="img"` y `aria-label="{% trans %}Área de firma manuscrita{% endtrans %}"`
- Botones con `aria-describedby` explicando la acción
- Contraste de trazo sobre fondo: ratio ≥ 4.5:1 (azul corporativo #233D63 sobre blanco)
- Focus visible en todos los interactivos
- Texto alternativo para lectores de pantalla cuando la firma está completada

---

## 5. Sprint 3 — Flujos de Firma por Tipo de Documento

### 5.1. Acuerdo de Participación

**Servicio**: `AcuerdoParticipacionService`
**Categoría STO**: `sto_acuerdo_participacion`
**Firma requerida**: Simple (solo participante)
**Método preferido**: Firma táctil (participante firma en sesión presencial de acogida)
**Documento oficial**: `Acuerdo_participacion_ICV25.odt`

**Modificación necesaria en `AcuerdoParticipacionService::firmar()`**:

Después de generar el PDF y crear el `ExpedienteDocumento`, en lugar de poner
`firmado=TRUE` directamente, delegar a `FirmaWorkflowService`:

```php
// ANTES (actual):
$participante->set('acuerdo_participacion_firmado', TRUE);
$participante->set('acuerdo_participacion_fecha', date('Y-m-d\TH:i:s'));

// DESPUÉS (objetivo):
$documento = $this->expedienteService->createDocument(...);
$this->firmaWorkflow->solicitarFirma(
  documentoId: (int) $documento->id(),
  firmanteUid: (int) $participante->getOwnerId(),
  tipo: 'acuerdo_participacion',
);
// El campo acuerdo_participacion_firmado se actualiza DESPUÉS
// de que la firma sea procesada (via hook o callback en FirmaWorkflowService).
```

### 5.2. DACI

**Servicio**: `DaciService`
**Categoría STO**: `sto_daci`
**Firma requerida**: Simple (solo participante)
**Método preferido**: Firma táctil
**Documento oficial**: `Anexo_DACI_ICV25.odt`

Mismo patrón que Acuerdo. El campo `daci_firmado` en `ProgramaParticipanteEi`
se actualiza SOLO cuando `FirmaWorkflowService` confirma la firma completada.

### 5.3. Hojas de Servicio (Firma Dual)

**Servicios**: `ReciboServicioService`, `HojaServicioMentoriaService`
**Categorías STO**: `mentoria_hoja_servicio`, `orientacion_hoja_servicio`, `formacion_hoja_servicio`
**Firma requerida**: **DUAL** (técnico/orientador + participante)
**Orden**: Técnico firma primero → Participante firma después
**Documento oficial**: Hoja de Servicio del STO

**Flujo dual**:
```
ReciboServicioService genera PDF
  → FirmaWorkflowService::solicitarFirmaDual(
      documentoId,
      tecnicoUid: $orientadorId,
      participanteUid: $participanteId,
    )
  → estado_firma = 'pendiente_firma_tecnico'
  → Notificación al orientador: "Hoja de servicio pendiente de firma"
  → Orientador firma (táctil en tablet durante sesión, o AutoFirma después)
  → estado_firma = 'firmado_parcial' / 'pendiente_firma_participante'
  → Notificación al participante: "Tu hoja de servicio necesita tu firma"
  → Participante firma (táctil en móvil)
  → estado_firma = 'firmado'
  → ActuacionSto.firmado_participante = TRUE
  → ActuacionSto.firmado_orientador = TRUE
```

**Sincronización** `ActuacionSto` ↔ `ExpedienteDocumento`:

Cuando `FirmaWorkflowService` completa una firma dual en un `ExpedienteDocumento`
que tiene `recibo_servicio_id` referenciando a una `ActuacionSto`, DEBE actualizar
los campos `firmado_participante` y `firmado_orientador` de la actuación.

### 5.4. Recibí Incentivo / Renuncia Incentivo

**Servicios**: `IncentiveReceiptService`, `IncentiveWaiverService`
**Categorías STO**: `sto_recibi_incentivo`, `sto_renuncia_incentivo`
**Firma requerida**: Simple (solo participante)
**Documentos oficiales**: `Recibi_Incentivo_ICV25.odt`, `Renuncia_Incentivo_ICV25.odt`

Mismo patrón que Acuerdo. Los campos `incentivo_recibido` / `incentivo_renuncia`
en `ProgramaParticipanteEi` se actualizan SOLO tras firma confirmada.

### 5.5. Recibos de Actuaciones STO

**Servicio**: `ReciboServicioService` (tipo `intermediacion` o `prospeccion`)
**Categoría STO**: `sto_recibo_actuaciones`
**Firma requerida**: Dual (técnico + participante) en actuaciones individuales,
  Simple (solo técnico) en actuaciones grupales
**Documento**: Recibo de actuación genérico

**Lógica condicional**:
```php
$tipoActuacion = $actuacion->getTipoActuacion();
$esGrupal = in_array($tipoActuacion, ['orientacion_grupal', 'formacion'], TRUE);

if ($esGrupal) {
  // Solo firma del técnico (no se puede firmar 20 participantes uno a uno).
  $this->firmaWorkflow->solicitarFirma($docId, $tecnicoUid, 'recibo_actuacion');
} else {
  // Firma dual.
  $this->firmaWorkflow->solicitarFirmaDual($docId, $tecnicoUid, $participanteUid);
}
```

---

## 6. Sprint 4 — Dashboard de Firma y Notificaciones

### 6.1. Panel de Firma Pendiente (Participante)

**Parcial Twig**: `templates/partials/_firma-pendientes.html.twig`

Widget integrado en el portal del participante (`participante-portal.html.twig`)
que muestra:
- Número de documentos pendientes (badge rojo si > 0)
- Lista compacta: tipo de documento, fecha de solicitud, botón "Firmar"
- Click en "Firmar" → abre slide-panel con preview + signature pad

**Inclusión** en `participante-portal.html.twig`:
```twig
{% if firma_pendientes|length > 0 %}
  {% include 'modules/custom/jaraba_andalucia_ei/templates/partials/_firma-pendientes.html.twig'
    with { pendientes: firma_pendientes } only %}
{% endif %}
```

**Variable inyectada** desde `ParticipantePortalController::portal()`:
```php
$firmaPendientes = $this->firmaWorkflow
  ? $this->firmaWorkflow->getDocumentosPendientes(
      (int) $this->currentUser()->id(),
      $tenantId,
    )
  : [];
```

### 6.2. Dashboard de Firma Masiva (Orientador/Coordinador)

**Template**: `templates/partials/_firma-masiva.html.twig`

**Funcionalidades**:
- Tabla filtrable: por tipo de documento, por participante, por fecha
- Checkbox de selección múltiple
- Botón "Firmar seleccionados" (aplica firma del técnico a todos)
- Botón "Aplicar sello de empresa" (solo coordinador con permiso admin)
- Indicador de progreso durante firma masiva
- Resumen post-firma: cuántos firmados, cuántos fallidos

**Integración en `coordinador-dashboard.html.twig`**:
Nuevo tab/sección "Firmas Pendientes" con el widget.

**Integración en `orientador-dashboard.html.twig`**:
Lista de hojas de servicio pendientes de su firma.

### 6.3. Notificaciones Push y Email

**Integración con `jaraba_support.notification`** (servicio existente):

| Evento | Canal | Destinatario | Mensaje |
|---|---|---|---|
| Firma solicitada | Push + Email | Firmante | "Tienes un documento pendiente de firma: {tipo}" |
| Firma dual: técnico firmó | Push | Participante | "Tu orientador ha firmado la Hoja de Servicio. Necesitamos tu firma." |
| Firma completada | Push | Solicitante | "El documento {tipo} ha sido firmado por {firmante}" |
| Firma rechazada | Push + Email | Solicitante | "El documento {tipo} ha sido rechazado: {motivo}" |
| Firma a punto de caducar | Push | Firmante | "Tienes 3 días para firmar {tipo}" |

**Textos traducibles** ({% trans %}):
Todos los mensajes de notificación se definen con `$this->t()` en PHP
y `{% trans %}` en Twig para ser traducibles al árabe, rumano, etc.
(colectivos de inclusión del programa).

---

## 7. Sprint 5 — Verificación, QR y Sellado Temporal

### 7.1. QR de Verificación en PDFs Firmados

Cuando un documento se firma, se genera un QR que contiene la URL de verificación:
```
https://jaraba-saas.lndo.site/verificar/{hash_sha256}
```

El QR se inserta en el PDF firmado (esquina inferior derecha, 2cm × 2cm)
junto con el texto: "Verificar autenticidad: escanee este código QR".

**Generación QR**: Via `endroid/qr-code` (ya disponible como dependencia de
`jaraba_page_builder` para otros QR del SaaS).

**Integración en PDF**: `BrandedPdfService` acepta un bloque QR opcional
en el footer del PDF.

### 7.2. Endpoint Público de Verificación

**Ruta**: `/verificar/{hash}` (acceso público, sin login)

**Template**: `templates/verificacion-documento.html.twig` (página limpia, Zero Region)

**Muestra**:
- Estado: "Documento verificado" (badge verde) o "Documento no encontrado" (badge rojo)
- Tipo de documento (sin revelar datos personales)
- Fecha de firma
- Método de firma (táctil / certificado digital / sello de empresa)
- Entidad firmante (si sello de empresa)
- Hash SHA-256 del documento

**NO muestra** (RGPD):
- Nombre del participante
- DNI/NIE
- Contenido del documento

### 7.3. Sellado Temporal TSA (FNMT)

`FirmaDigitalService` ya implementa sellado TSA con FNMT y FreeTSA.
`FirmaWorkflowService` lo invoca DESPUÉS de cada firma completada:

```php
// Tras insertar firma manuscrita o recibir firma AutoFirma:
if ($this->firmaDigitalService) {
  $this->firmaDigitalService->aplicarSelloTemporal($pdfUri);
}
```

**Configuración** (`settings.secrets.php`):
```php
$config['ecosistema_jaraba_core.firma_settings'] = [
  'tsa_url' => getenv('ECOSISTEMA_JARABA_TSA_URL') ?: 'http://tsa.fnmt.es/tsa/tss',
  'tsa_url_fallback' => 'https://freetsa.org/tsr',
  'certificate_path' => getenv('ECOSISTEMA_JARABA_CERT_PATH'),
  'certificate_password' => getenv('ECOSISTEMA_JARABA_CERT_PASSWORD'),
];
```

### 7.4. Certificado de Sello de Empresa

El sello de empresa (firma automática con PKCS#12 del tenant) se aplica a:
- Documentos internos del programa que no requieren firma de persona física
- Certificados de participación
- Informes de progreso
- Como co-firma institucional en documentos con firma del participante

**Gestión**: Via `CertificateManagerService` (ya existente en `ecosistema_jaraba_core`).
Cada tenant sube su certificado `.p12` desde la configuración del tenant
(Tenant Settings Hub → sección "Certificado Digital").

---

## 8. Sprint 6 — Tests y Validación

### Tests Unitarios Nuevos

| Test File | Tests | Descripción |
|---|---|---|
| `FirmaWorkflowServiceTest.php` | 15+ | Máquina de estados, firma simple, firma dual, rechazo, caducidad |
| `FirmaDocumentoControllerTest.php` | 8+ | Endpoints API, validaciones, tenant isolation |
| `SignaturePadIntegrationTest.php` | 5+ | Validación de firma PNG, dimensiones, formato |

**Patrones de test**: MOCK-DYNPROP-001 (clases anónimas), TEST-CACHE-001 (cache metadata).

### Validaciones Post-Implementación

```bash
# RUNTIME-VERIFY-001: 12 checks
/verify-runtime jaraba_andalucia_ei

# Específicos de firma:
# 1. CSS compilado con _firmas.scss incluido
# 2. Tabla firma_audit_log creada
# 3. Rutas de firma accesibles
# 4. drupalSettings.jarabaFirma inyectado en portal
# 5. data-* selectores matchean entre JS y HTML del signature pad
# 6. Canvas táctil funcional en viewport 375px
# 7. QR legible en PDF generado
# 8. Sellado TSA aplicado (verificar con pdfsig)
# 9. Vault almacena versión firmada
# 10. Auditoría registrada en tabla dedicada
```

---

---

# PARTE II — Emprendimiento Inclusivo Clase Mundial (+ei)

> **Insight clave de la auditoría**: La infraestructura para clase mundial YA EXISTE
> en módulos transversales (`jaraba_matching`, `jaraba_job_board`, `jaraba_candidate`,
> `jaraba_business_tools`, `jaraba_lms`, `jaraba_pwa`). Lo que falta no es construir
> sino **conectar**. El programa +ei debe convertirse en el **orquestador** que
> teje estas capacidades en un itinerario coherente de emprendimiento inclusivo.

---

## 9. Diagnóstico de Infraestructura Existente

### 9.1. Mapa de Módulos ya Construidos

| Capacidad | Módulo | Entidades | Servicios Clave | Conectado a +ei |
|---|---|---|---|---|
| Matching IA (Qdrant vectorial) | `jaraba_matching` | MatchResult, MatchFeedback | MatchingService, QdrantMatchingClient | **NO** |
| Bolsa de empleo | `jaraba_job_board` | JobPosting, JobApplication, EmployerProfile | JobPostingService, ApplicationService | **NO** |
| Perfil candidato | `jaraba_candidate` | CandidateProfile, Skill, Education, Experience | ProfileService, CvBuilderService, SkillInferenceService | **NO** |
| Herramientas negocio | `jaraba_business_tools` | BusinessModelCanvas, MvpHypothesis, FinancialProjection | CanvasService, MvpValidationService, SroiCalculatorService | **Parcial** (via CrossVerticalBridge) |
| LMS / Formación | `jaraba_lms` | Course, Enrollment, Lesson | EnrollmentService, GamificationService, CertificationService | **Parcial** (bridge formacion_continua) |
| PWA / Push | `jaraba_pwa` | PushSubscription | PlatformPushService, PwaSyncManagerService | **NO** |
| Badges / Gamificación | `ecosistema_jaraba_core` | BadgeAward | BadgeAwardService, ReviewGamificationService | **NO** |
| Copilot Emprendimiento | `jaraba_copilot_v2` | — | EmprendimientoCopilotAgent (6 modos) | **Parcial** (bridge activa agente) |
| Mentoring | `jaraba_mentoring` | — | MentorMatchingService, SessionSchedulerService | **NO** |
| Notificaciones multi-canal | `jaraba_support` | Ticket | TicketNotificationService | **NO** |
| Journey Engine | `jaraba_journey` | JourneyState | JourneyEngineService, JourneyTriggerService | **NO** |

### 9.2. Lo que Existe pero NO Está Conectado

**El problema no es de capacidad sino de cableado.** Ejemplos concretos:

1. **`MatchingService` + Qdrant** puede hacer matching semántico participante↔empresa,
   pero nadie pasa los datos de `ProgramaParticipanteEi` al motor de matching.

2. **`PlatformPushService`** envía push notifications a cualquier usuario suscrito,
   pero ningún evento de +ei (firma pendiente, nueva fase, sesión programada)
   dispara una notificación.

3. **`BadgeAwardService`** emite badges, pero `PuntosImpactoEiService` calcula
   puntos sin emitir ningún badge cuando se alcanza un hito.

4. **`JourneyEngineService`** orquesta transiciones cross-sell entre verticales,
   pero los cambios de fase en +ei no disparan journey triggers.

5. **`CandidateProfile`** tiene competencias, educación y experiencia, pero
   `ProgramaParticipanteEi` no crea ni actualiza un perfil de candidato.

6. **`SroiCalculatorService`** calcula Social Return on Investment, pero
   `JustificacionEconomicaService` no alimenta datos al calculador SROI.

### 9.3. Lo que Realmente Falta (Nuevos Componentes)

| Componente | Tipo | Por qué no existe | Sprint |
|---|---|---|---|
| `PlanEmprendimientoEi` | ContentEntity | Ningún módulo trackea el plan de negocio DENTRO del itinerario PIIL | S7 |
| `BarrerasAccesoService` | Service | Los datos de IndicadorFsePlus se recogen pero no adaptan el itinerario | S8 |
| `EiMatchingBridgeService` | Service | No hay puente entre participantes +ei y el motor de matching | S9 |
| `EiAlumniBridgeService` | Service | Alumni existe como campo booleano, no como comunidad activa | S10 |
| `EiBadgeBridgeService` | Service | Puntos de impacto no emiten badges | S11 |
| `EiPushNotificationService` | Service | Eventos de +ei no disparan push notifications | S12 |
| Templates WCAG-compliant | Twig | Solo 3 aria-* en 28 templates | S12 |

---

## 10. Sprint 7 — Itinerario Dual Empleo/Emprendimiento

> El "+ei" del nombre del programa significa "más emprendimiento inclusivo".
> Sin embargo, el sistema actual trata el emprendimiento como un `tipo_insercion`
> (un campo string con valor `'cuenta_propia'`). Un programa de clase mundial
> necesita un itinerario paralelo completo para emprendimiento.

### 10.1. PlanEmprendimientoEi Entity

**Entidad nueva**: `plan_emprendimiento_ei`

Representa el plan de negocio de un participante que elige la vía de emprendimiento.
Vincula al participante con las herramientas de `jaraba_business_tools` y trackea
los hitos propios del emprendimiento dentro del itinerario PIIL.

**Scaffolding**: Usar `/create-entity jaraba_andalucia_ei plan_emprendimiento_ei`

**Campos específicos**:

| Campo | Tipo | Descripción |
|---|---|---|
| `participante_id` | entity_reference → programa_participante_ei | Participante propietario |
| `tenant_id` | entity_reference → group | Tenant (ENTITY-FK-001) |
| `idea_negocio` | text_long | Descripción de la idea de negocio |
| `sector` | list_string | Sector CNAE de la actividad |
| `forma_juridica_objetivo` | list_string | autonomo, sl, coop_trabajo, coop_social, comunidad_bienes |
| `fase_emprendimiento` | list_string | ideacion, validacion, lanzamiento, consolidacion |
| `canvas_id` | entity_reference → business_model_canvas (jaraba_business_tools) | BMC vinculado |
| `mvp_hypothesis_id` | entity_reference → mvp_hypothesis | Hipótesis MVP vinculada |
| `projection_id` | entity_reference → financial_projection | Proyección financiera vinculada |
| `fecha_alta_reta` | datetime | Fecha de alta en RETA (si aplica) |
| `fecha_alta_iae` | datetime | Fecha de alta en IAE (si aplica) |
| `inversion_inicial` | decimal(10,2) | Inversión inicial estimada |
| `fuentes_financiacion` | text_long | JSON: [{tipo, importe, estado}] |
| `necesita_microcredito` | boolean | Solicita microcrédito |
| `primer_cliente_fecha` | datetime | Fecha del primer cliente/ingreso |
| `facturacion_acumulada` | decimal(10,2) | Facturación acumulada desde alta |
| `empleo_generado` | integer | Número de empleos generados (incluido el propio) |
| `mentor_emprendimiento_uid` | entity_reference → user | Mentor de emprendimiento asignado |
| `diagnostico_viabilidad` | list_string | viable, viable_con_condiciones, no_viable, pendiente |
| `notas` | text_long | Notas del orientador |

**Fases de emprendimiento** (paralelas a las fases PIIL):
```
ideacion       ← Acogida/Diagnóstico PIIL (descubre que quiere emprender)
validacion     ← Atención PIIL (BMC, customer discovery, MVP)
lanzamiento    ← Inserción PIIL (alta RETA/IAE, primer cliente)
consolidacion  ← Seguimiento PIIL (facturación, empleo generado)
```

**hook_update_10016**: Instalar entity type `plan_emprendimiento_ei`.

### 10.2. Integración con jaraba_business_tools

**Servicio puente**: `EiEmprendimientoBridgeService`

Conecta el `PlanEmprendimientoEi` con las herramientas existentes:

```
jaraba_andalucia_ei.ei_emprendimiento_bridge:
  class: Drupal\jaraba_andalucia_ei\Service\EiEmprendimientoBridgeService
  arguments:
    - '@entity_type.manager'
    - '@logger.channel.jaraba_andalucia_ei'
    - '@?jaraba_business_tools.canvas'
    - '@?jaraba_business_tools.mvp_validation'
    - '@?jaraba_business_tools.projection'
    - '@?jaraba_business_tools.sroi_calculator'
    - '@?ecosistema_jaraba_core.tenant_context'
```

**Funcionalidades**:

| Método | Descripción |
|---|---|
| `crearPlanDesdeParticipante(int $participanteId)` | Crea PlanEmprendimientoEi + BusinessModelCanvas vacío + enlaza |
| `sincronizarFaseConPiil(int $planId)` | Actualiza fase_emprendimiento según fase_actual del participante |
| `getResumenPlan(int $planId)` | Datos consolidados: canvas completo, validaciones MVP, proyecciones |
| `calcularViabilidad(int $planId)` | Diagnóstico automático basado en BMC + proyecciones + MVP |
| `getSroiEmprendimiento(int $planId)` | Calcula SROI del emprendimiento (usa SroiCalculatorService) |
| `getHitosEmprendimiento(int $participanteId)` | Lista de hitos con estado (completado/pendiente) |

### 10.3. Hitos de Emprendimiento Diferenciados

Los hitos de emprendimiento son distintos a los de empleo. Se integran en el
timeline del participante (`ParticipantePortalController::buildTimeline()`):

**Hitos vía empleo** (ya implementados):
```
Acogida: Alta STO → Acuerdo → DACI → FSE+ entrada
Diagnóstico: DIME → Itinerario asignado → Primera sesión
Atención: Orientación ≥5h → Formación ≥25h → Completar orientación/formación
Inserción: Plan inserción → Búsqueda activa → Inserción confirmada
Seguimiento: FSE+ salida → Seguimiento 6 meses
```

**Hitos vía emprendimiento** (nuevos):
```
Acogida: Alta STO → Acuerdo → DACI → FSE+ entrada (idénticos)
Diagnóstico: DIME → Interés emprendedor detectado → Primer BMC borrador
Atención: Orientación emprendimiento ≥5h → Customer Discovery → MVP validado →
          Plan financiero → Diagnóstico viabilidad
Inserción: Alta RETA/IAE → Primer cliente/ingreso → Facturación >0
Seguimiento: FSE+ salida → Empleo generado → Seguimiento 6 meses
```

**Impacto en CalendarioProgramaService**: Ampliar `getHitos()` con rama condicional
según `tipo_insercion` del participante.

### 10.4. Copilot Contextual por Vía

`AndaluciaEiCopilotContextProvider::getContext()` ya proporciona contexto al copilot.
Se amplía para incluir datos del plan de emprendimiento:

```php
// Si el participante tiene plan de emprendimiento activo:
if ($this->eiEmprendimientoBridge) {
  $plan = $this->eiEmprendimientoBridge->getResumenPlan($participanteId);
  if ($plan) {
    $context['plan_emprendimiento'] = $plan;
    $context['modos_adicionales'] = ['business_strategist', 'financial_advisor'];
  }
}
```

Esto activa automáticamente los 6 modos del `EmprendimientoCopilotAgent` para
participantes con vía de emprendimiento, sin necesidad de cambiar de vertical.

---

## 11. Sprint 8 — Motor de Adaptación por Colectivo y Barreras

> Para un programa de emprendimiento INCLUSIVO, "inclusivo" no puede ser
> solo una etiqueta. El itinerario debe adaptarse a las barreras reales
> de cada participante.

### 11.1. BarrerasAcceso — Campo Estructurado

En lugar de crear una entidad nueva, usamos un campo `text_long` con estructura
JSON en `ProgramaParticipanteEi`, porque las barreras son atributos del
participante, no entidades independientes.

**Campo nuevo**: `barreras_acceso` (text_long, JSON)

**Estructura JSON**:
```json
{
  "idioma": {
    "activa": true,
    "nivel": "alto",
    "idioma_nativo": "ar",
    "castellano_nivel": "A2",
    "necesita_interprete": true
  },
  "brecha_digital": {
    "activa": true,
    "nivel": "medio",
    "tiene_dispositivo": true,
    "tiene_internet": false,
    "competencia_digital": "basico"
  },
  "carga_cuidados": {
    "activa": true,
    "nivel": "alto",
    "menores_cargo": 2,
    "mayores_cargo": 0,
    "horario_disponible": "mananas"
  },
  "situacion_administrativa": {
    "activa": false
  },
  "vivienda": {
    "activa": true,
    "nivel": "critico",
    "tipo": "sin_hogar",
    "necesita_derivacion": true
  },
  "salud_mental": {
    "activa": true,
    "nivel": "medio",
    "en_tratamiento": true,
    "necesita_derivacion": false
  },
  "violencia_genero": {
    "activa": false
  },
  "movilidad_geografica": {
    "activa": true,
    "nivel": "bajo",
    "tiene_vehiculo": false,
    "transporte_publico_accesible": true
  }
}
```

**hook_update_10017**: Instalar campo `barreras_acceso` en programa_participante_ei.

### 11.2. AdaptacionItinerarioService

**Archivo**: `src/Service/AdaptacionItinerarioService.php`

Servicio que analiza el perfil del participante (colectivo FSE+ + barreras +
datos de IndicadorFsePlus) y genera recomendaciones de adaptación del itinerario.

```
jaraba_andalucia_ei.adaptacion_itinerario:
  class: Drupal\jaraba_andalucia_ei\Service\AdaptacionItinerarioService
  arguments:
    - '@entity_type.manager'
    - '@logger.channel.jaraba_andalucia_ei'
    - '@?jaraba_andalucia_ei.riesgo_abandono'
    - '@?ecosistema_jaraba_core.tenant_context'
```

**Métodos principales**:

| Método | Descripción |
|---|---|
| `evaluarBarreras(int $participanteId)` | Lee barreras + IndicadorFsePlus, calcula perfil de complejidad |
| `getAdaptaciones(int $participanteId)` | Lista de adaptaciones recomendadas para el itinerario |
| `getDerivacionesNecesarias(int $participanteId)` | Derivaciones a servicios externos (servicios sociales, vivienda, salud) |
| `getIntensidadAcompanamiento(int $participanteId)` | alta/media/baja según complejidad |
| `getRecursosEspecificos(int $participanteId)` | Recursos adaptados al colectivo |

**Adaptaciones por barrera**:

| Barrera | Adaptación automática |
|---|---|
| Idioma (nivel < B1) | Sesiones con intérprete, material bilingüe, copilot en idioma nativo |
| Brecha digital (sin dispositivo) | Préstamo tablet, formación digital básica prioritaria |
| Brecha digital (sin internet) | Sesiones presenciales prioritarias, material offline (PWA) |
| Carga de cuidados (alta) | Sesiones en horario compatible, formación online, mentoring async |
| Vivienda (sin hogar) | Derivación URGENTE a servicios sociales ANTES de orientación laboral |
| Salud mental | Derivación a salud mental, intensidad de acompañamiento reducida |
| Violencia de género | Derivación a instituto de la mujer, orientación en espacio seguro |
| Discapacidad | Adaptación material (lectura fácil, audio), accesibilidad WCAG |
| Movilidad (sin transporte) | Sesiones online, prospección empresas cercanas al domicilio |

**Integración con CopilotContextProvider**:
```php
$context['barreras'] = $this->adaptacionService->evaluarBarreras($participanteId);
$context['adaptaciones'] = $this->adaptacionService->getAdaptaciones($participanteId);
$context['instruccion_adicional'] = 'Adapta tu comunicación al perfil de este participante.
  Barreras activas: ' . implode(', ', $barrerasActivas) . '.
  Intensidad de acompañamiento: ' . $intensidad . '.';
```

### 11.3. Derivación Automática a Servicios Sociales

Cuando `AdaptacionItinerarioService::getDerivacionesNecesarias()` detecta una
barrera crítica (vivienda sin_hogar, violencia_genero activa), genera una
**alerta prioritaria** en el dashboard del coordinador.

**Integración con AlertasNormativasService**: Nuevo tipo de alerta `derivacion_urgente`:
```php
$alertas[] = [
  'tipo' => 'derivacion_urgente',
  'prioridad' => 'critica',
  'participante_id' => $participanteId,
  'mensaje' => 'Participante requiere derivación a servicios sociales: situación de vivienda crítica',
  'recurso_externo' => 'Servicios Sociales Comunitarios - Ayuntamiento de [municipio]',
  'accion' => 'Contactar antes de continuar con orientación laboral',
];
```

### 11.4. Copilot Sensible al Colectivo

El copilot IA debe adaptar su comunicación según el perfil del participante:

- **Idioma**: Si `idioma.castellano_nivel < B1`, usar frases simples, vocabulario básico
- **Brecha digital**: Si `brecha_digital.competencia_digital = 'basico'`, explicar
  paso a paso cómo navegar la plataforma
- **Discapacidad intelectual**: Usar lectura fácil, frases cortas, apoyo visual
- **Violencia de género**: Enfoque empoderador, nunca preguntar por la situación
  personal directamente, derivar a profesionales especializados

Estas instrucciones se inyectan como **prompt rules** en el contexto del copilot.

---

## 12. Sprint 9 — Matching Bidireccional Participante↔Empresa

> El SaaS ya tiene `MatchingService` con Qdrant (vectorial) + `jaraba_job_board`.
> Solo falta el puente que conecte los participantes de +ei con este motor.

### 12.1. Bridge andalucia_ei ↔ jaraba_matching

**Servicio**: `EiMatchingBridgeService`

```
jaraba_andalucia_ei.ei_matching_bridge:
  class: Drupal\jaraba_andalucia_ei\Service\EiMatchingBridgeService
  arguments:
    - '@entity_type.manager'
    - '@logger.channel.jaraba_andalucia_ei'
    - '@?jaraba_matching.matching'
    - '@?jaraba_candidate.profile'
    - '@?jaraba_candidate.skills'
    - '@?ecosistema_jaraba_core.tenant_context'
```

**Funcionalidades**:

| Método | Descripción |
|---|---|
| `sincronizarPerfilCandidato(int $participanteId)` | Crea/actualiza CandidateProfile desde datos del participante |
| `ejecutarMatching(int $participanteId)` | Lanza matching contra JobPostings activos vía Qdrant |
| `getMatchesPorParticipante(int $participanteId, int $limit)` | Obtiene matches ordenados por score |
| `getMatchesPorEmpresa(int $prospeccionId, int $limit)` | Participantes compatibles con la empresa |
| `notificarMatch(int $matchId)` | Notifica al participante y al orientador de un nuevo match |

**Flujo de sincronización**:
```
ProgramaParticipanteEi se actualiza (presave hook)
  → Si fase_actual IN ['atencion', 'insercion']:
    → EiMatchingBridgeService::sincronizarPerfilCandidato()
      → Crea/actualiza CandidateProfile con:
        - Competencias inferidas de formación completada (jaraba_lms enrollments)
        - Experiencia de actuaciones STO
        - Colectivo y barreras (para matching inclusivo)
        - Preferencias geográficas
        - Tipo inserción preferido (empleo/emprendimiento)
      → Embedding vectorial generado por Qdrant
    → Si hay JobPostings activos en el tenant:
      → Matching automático
      → Si score > threshold: notificar orientador
```

### 12.2. Bridge andalucia_ei ↔ jaraba_job_board

**Ruta nueva** en portal del participante: sección "Oportunidades" que muestra
ofertas de empleo matcheadas desde `jaraba_job_board`.

**Integración en `ParticipantePortalController::portal()`**:
```php
$oportunidades = $this->eiMatchingBridge
  ? $this->eiMatchingBridge->getMatchesPorParticipante(
      (int) $participante->id(), 5
    )
  : [];

// Render en portal con parcial _oportunidades-matching.html.twig
```

### 12.3. Portal Empresa (Vista Participantes Anonimizados)

Las empresas prospectadas (`ProspeccionEmpresarial` con estado `colaborador`)
acceden a una vista anonimizada de participantes compatibles.

**NO se muestra**: nombre, DNI, datos personales.
**SÍ se muestra**: perfil competencial, sector formativo, nivel experiencia,
disponibilidad geográfica, tipo inserción buscado.

**Ruta**: `/andalucia-ei/empresa/{prospeccion_empresarial}/candidatos`
**Permiso**: Nuevo permiso `view andalucia ei matching anonimizado`

### 12.4. Matching IA con Qdrant para +ei

`QdrantMatchingClient` ya indexa en colecciones vectoriales.
Se crea una colección específica para +ei:

**Colección**: `andalucia_ei_participantes`

**Campos vectorizados**:
- Competencias (del CandidateProfile sincronizado)
- Sector formativo (de enrollments en jaraba_lms)
- Experiencia previa (de CandidateExperience)
- Preferencia geográfica (provincia del participante)
- Tipo inserción (empleo vs emprendimiento)

**Scoring de matching**: Score base del vector + bonus por:
- Empresa en misma provincia (+10%)
- Empresa con tipo_colaboracion='formacion_dual' y participante en fase atención (+15%)
- Empresa del sector donde el participante tiene formación completada (+20%)

---

## 13. Sprint 10 — Red Alumni Viva

### 13.1. Comunidad Alumni Digital

Los campos ya existen en `ProgramaParticipanteEi` (`is_alumni`, `alumni_fecha`,
`alumni_disponible_mentoria`). Lo que falta es la **experiencia**:

**Template nuevo**: `templates/comunidad-alumni.html.twig` (Zero Region, página limpia)
**Ruta**: `/andalucia-ei/alumni`
**Permiso**: `access andalucia ei alumni`

**Contenido**:
- Directorio de alumni (nombre público, sector, tipo inserción lograda, año)
- Filtros: por sector, por tipo (empleo/emprendimiento), por año de cohorte
- Indicador "Disponible para mentoring" (si `alumni_disponible_mentoria = TRUE`)
- Botón "Contactar" → abre conversación en sistema de mensajería existente
- Estadísticas de la comunidad: total alumni, tasa de retención, empleos generados

### 13.2. Mentoring Peer-to-Peer (Alumni → Participante)

**Integración con `jaraba_mentoring`**: Un alumni con `alumni_disponible_mentoria`
se registra como mentor peer en el módulo de mentoring existente.

**Flujo**:
```
Alumni marca "Disponible para mentoring" en su perfil
  → Sistema crea/actualiza MentorProfile en jaraba_mentoring
    con tipo = 'peer_alumni_ei'
  → MentorMatchingService incluye peer mentors en el matching
  → Participante en fase atención/inserción recibe match con alumni
  → SessionSchedulerService programa sesiones peer
  → HumanMentorshipTracker registra horas
```

### 13.3. Historias de Éxito Conectadas a Datos Reales

Las imágenes de testimonios ya existen (Adrián, Cristina, Marcela).
Se conectan con datos reales de impacto:

**Parcial nuevo**: `templates/partials/_historia-exito.html.twig`

```twig
{# TWIG-INCLUDE-ONLY-001: solo variables necesarias #}
<article class="historia-exito" aria-labelledby="historia-{{ alumni.id }}">
  <img src="{{ alumni.foto }}" alt="{% trans %}Foto de {{ alumni.nombre }}{% endtrans %}"
    loading="lazy" width="200" height="200">
  <h3 id="historia-{{ alumni.id }}">{{ alumni.nombre }}</h3>
  <p class="historia-exito__sector">{{ alumni.sector }}</p>
  <p class="historia-exito__tipo">
    {% if alumni.tipo_insercion == 'cuenta_propia' %}
      {% trans %}Emprendedor/a{% endtrans %}
    {% else %}
      {% trans %}Insertado/a laboralmente{% endtrans %}
    {% endif %}
  </p>
  <p class="historia-exito__impacto">
    {% trans %}{{ alumni.horas_formacion }}h de formación · {{ alumni.meses_programa }} meses en el programa{% endtrans %}
  </p>
  {% if alumni.testimonial %}
    <blockquote>{{ alumni.testimonial }}</blockquote>
  {% endif %}
</article>
```

Usado en la landing de reclutamiento (`andalucia-ei-reclutamiento.html.twig`)
y en la comunidad alumni.

---

## 14. Sprint 11 — Gamificación Significativa e Impacto Verificable

### 14.1. Integración PuntosImpactoEi → BadgeAwardService

**Servicio puente**: `EiBadgeBridgeService`

Cuando `PuntosImpactoEiService` calcula que un participante ha alcanzado un hito,
emite un badge via `BadgeAwardService`:

| Hito | Puntos | Badge | Icono |
|---|---|---|---|
| Primera semana completada | 10 | `ei_primera_semana` | jaraba_icon('achievement', 'star', {color: 'naranja-impulso'}) |
| Diagnóstico DIME completado | 20 | `ei_diagnostico_completado` | jaraba_icon('achievement', 'target', {color: 'verde-innovacion'}) |
| 10h de orientación | 30 | `ei_orientacion_10h` | jaraba_icon('achievement', 'compass', {color: 'azul-corporativo'}) |
| 25h de formación | 42 | `ei_formacion_25h` | jaraba_icon('achievement', 'book', {color: 'verde-innovacion'}) |
| 50h de formación | 55 | `ei_formacion_completa` | jaraba_icon('achievement', 'graduation', {color: 'naranja-impulso'}) |
| Inserción lograda | 90 | `ei_insercion` | jaraba_icon('achievement', 'rocket', {color: 'verde-innovacion'}) |
| Emprendimiento lanzado | 90 | `ei_emprendimiento` | jaraba_icon('achievement', 'lightbulb', {color: 'naranja-impulso'}) |
| Alumni activo | 100 | `ei_alumni` | jaraba_icon('achievement', 'crown', {color: 'azul-corporativo'}) |
| Mentor peer activo | 110 | `ei_mentor_peer` | jaraba_icon('achievement', 'heart', {color: 'naranja-impulso'}) |

**Parcial**: `_participante-logros.html.twig` (ya existe) → ampliar con badges reales.

### 14.2. Micro-Aprendizaje Diario (jaraba_lms Bridge)

**Integración con `jaraba_lms`**: Seleccionar lecciones breves (≤10 min)
relevantes para la fase actual del participante y enviar como notificación diaria.

**Servicio**: Ampliar `CalendarioProgramaService` con método `getLeccionDelDia()`:

```php
public function getLeccionDelDia(int $participanteId): ?array {
  // Buscar enrollment activo → curso con lecciones no completadas → lección más corta
  // Filtrar por fase actual (ej: en inserción → lecciones de búsqueda empleo)
  // Retornar: título, duración, URL
}
```

**Notificación push diaria** (via `PlatformPushService`):
"Tu pill del día: [título] (5 min) — Aprende algo nuevo cada día"

### 14.3. Dashboard Público de Transparencia

**Ruta pública**: `/andalucia-ei/impacto` (acceso público, sin login)
**Template**: `templates/andalucia-ei-impacto-publico.html.twig` (Zero Region)

**Muestra datos agregados anonimizados**:
- Total participantes atendidos (persona_atendida)
- Total inserciones logradas (persona_insertada)
- Total emprendimientos lanzados
- Horas de formación impartidas
- Distribución por colectivo (gráfico)
- Distribución por tipo de inserción (empleo vs emprendimiento)
- SROI estimado (€ retorno social por € invertido)
- Alineación con ODS (Objetivos de Desarrollo Sostenible): ODS 1, 4, 5, 8, 10

**NO muestra** (RGPD): Ningún dato personal ni identificable.

### 14.4. Indicadores IRIS+ y SROI

**IRIS+** (Global Impact Investing Network): Estándar de medición de impacto social.

Integración con `SroiCalculatorService` de `jaraba_business_tools`:

| Indicador IRIS+ | Código | Fuente en +ei |
|---|---|---|
| Clients: Individuals | PI1556 | Total programa_participante_ei activos |
| Client Outcomes: Employment | PI7734 | Total inserciones laborales |
| Client Outcomes: Self-Employment | PI3213 | Total plan_emprendimiento_ei en consolidación |
| Training Hours Provided | OI5103 | Suma horas_formacion + horas_orientacion |
| Jobs Created (by enterprises) | PI3468 | Suma empleo_generado en planes de emprendimiento |
| Social Return on Investment | — | `SroiCalculatorService::calculate()` |

---

## 15. Sprint 12 — Accesibilidad WCAG 2.1 AA Completa

### 15.1. Auditoría de Templates andalucia_ei

**Estado actual**: Solo 3 atributos `aria-*` en 28 templates. Inaceptable para
un programa de inclusión.

**Acción**: Ejecutar `/audit-wcag` en cada template y corregir:

- Todos los interactivos (botones, links, campos) necesitan `aria-label` o texto visible
- Headings jerárquicos (h1 → h2 → h3, sin saltos)
- Imágenes con `alt` descriptivo
- Formularios con `<label>` asociados
- Focus visible (`:focus-visible`) en todos los interactivos
- Contraste mínimo 4.5:1 para texto normal, 3:1 para texto grande
- `role="alert"` en mensajes de éxito/error
- `role="navigation"` en menús
- `role="main"` en contenido principal
- `sr-only` class para texto solo para lectores de pantalla
- `aria-live="polite"` en contenido que se actualiza dinámicamente (widgets AJAX)

### 15.2. Push Notifications para Firma y Programa

Conectar eventos de +ei con `PlatformPushService`:

| Evento | Tipo Push | Prioridad |
|---|---|---|
| Documento pendiente de firma | push + email | alta |
| Cambio de fase | push | media |
| Sesión de mentoring programada | push | alta |
| Badge obtenido | push | baja |
| Pill del día (micro-aprendizaje) | push | baja |
| Match con empresa detectado | push + email | alta |
| Alerta de riesgo de abandono | push (al orientador) | alta |
| Derivación urgente necesaria | push + email (al coordinador) | crítica |

### 15.3. Adaptación Idiomática para Colectivos

El programa atiende colectivos con idiomas nativos diversos.
Prioridades de traducción más allá del español:

1. **Árabe** — Comunidad marroquí/magrebí (colectivo principal en Andalucía)
2. **Rumano** — Segunda comunidad extranjera en España
3. **Inglés** — Colectivos subsaharianos y refugiados
4. **Francés** — Colectivos de África occidental
5. **Ucraniano** — Refugiados recientes

**Implementación**: Via el sistema de i18n de Drupal existente + `jaraba_i18n`
(ya tiene `AITranslationService`). Los textos del portal del participante y
del copilot IA se traducen prioritariamente a estos 5 idiomas.

---

## 16. Tabla de Correspondencia: Especificaciones Técnicas

| Especificación | Componente | Implementación | Sprint |
|---|---|---|---|
| Firma manuscrita táctil | SignaturePad JS | Canvas HTML5, touch events, PNG export | S2 |
| Firma con certificado digital | AutoFirma Integration | Bridge ExpedienteDocumento ↔ AutoFirma | S1 |
| Firma con sello de empresa | FirmaDigitalService | PKCS#12 + TSA del tenant | S1 |
| Firma dual (participante + técnico) | FirmaWorkflowService | Máquina de estados, notificaciones cruzadas | S1 |
| Sellado temporal (TSA) | FirmaDigitalService | FNMT + FreeTSA fallback | S5 |
| QR de verificación | endroid/qr-code | SHA-256 hash, URL pública | S5 |
| Verificación pública | FirmaDocumentoController | `/verificar/{hash}`, Zero Region, sin PII | S5 |
| Auditoría inmutable | firma_audit_log table | Registro permanente por firma | S1 |
| Notificaciones de firma | jaraba_support.notification | Push + email por evento | S4 |
| Almacenamiento cifrado | jaraba_legal_vault | Vault envelope encryption | Ya existe |
| PDF con branding tenant | BrandedPdfService | Color cascade 4 niveles | Ya existe |
| Multi-tenant isolation | TENANT-001 | Toda query filtra por tenant | Transversal |
| Accesibilidad WCAG 2.1 AA | SCSS + ARIA | Contraste 4.5:1, focus visible, screen reader | S2 |
| Mobile-first | Responsive SCSS | touch-action: none, viewport 375px+ | S2 |
| Slide-panel UX | SLIDE-PANEL-RENDER-001 | renderPlain(), no render() | S2 |
| Textos traducibles | i18n | {% trans %}, Drupal.t(), $this->t() | Transversal |
| URLs via drupalSettings | ROUTE-LANGPREFIX-001 | Nunca hardcoded, Url::fromRoute() | Transversal |
| CSRF en API | CSRF-API-001 | _csrf_request_header_token: 'TRUE' | S1 |
| Secrets en env vars | SECRET-MGMT-001 | settings.secrets.php + getenv() | Ya configurado |
| Entity forms | PREMIUM-FORMS-PATTERN-001 | PremiumEntityFormBase | No aplica (no hay form de firma como entity) |
| Iconos | ICON-CONVENTION-001 | jaraba_icon('ui', 'signature', {variant: 'duotone'}) | S2 |
| Colores SCSS | CSS-VAR-ALL-COLORS-001 | var(--ej-*, fallback) | S2 |
| SCSS moderno | Dart Sass | @use, color-mix(), no @import | S2 |
| Zero Region | ZERO-REGION-001 | Template limpio para /verificar/{hash} | S5 |
| PAdES (PDF signature) | AutoFirma + FirmaDigitalService | SHA256withRSA | S1 |
| eIDAS compliance | Dual-path | Nivel alto (AutoFirma) + Nivel básico (táctil) | Transversal |
| Itinerario dual empleo/emprendimiento | PlanEmprendimientoEi entity | 4 fases emprendimiento paralelas a PIIL | S7 |
| BMC / Lean Canvas integrado | EiEmprendimientoBridgeService | Conecta a jaraba_business_tools.canvas | S7 |
| MVP Validation integrado | EiEmprendimientoBridgeService | Conecta a jaraba_business_tools.mvp_validation | S7 |
| Proyecciones financieras | EiEmprendimientoBridgeService | Conecta a jaraba_business_tools.projection | S7 |
| Alta RETA/IAE como hito | PlanEmprendimientoEi.fecha_alta_reta | Campo datetime en entity | S7 |
| Barreras de acceso estructuradas | barreras_acceso (JSON) | 8 tipos de barrera con niveles | S8 |
| Adaptación por colectivo | AdaptacionItinerarioService | Motor de reglas barrera → adaptación | S8 |
| Derivación a servicios sociales | AlertasNormativasService ampliado | Alerta derivacion_urgente automática | S8 |
| Copilot sensible al colectivo | CopilotContextProvider ampliado | Prompt rules por barrera | S8 |
| Matching IA participante↔empresa | EiMatchingBridgeService | Qdrant vectorial + scoring +ei | S9 |
| Sincro perfil candidato | EiMatchingBridgeService | ProgramaParticipanteEi → CandidateProfile | S9 |
| Portal empresa anonimizado | FirmaDocumentoController | Vista perfiles sin PII | S9 |
| Comunidad alumni digital | comunidad-alumni.html.twig | Directorio, filtros, contacto | S10 |
| Mentoring peer-to-peer | jaraba_mentoring bridge | Alumni como mentor tipo peer_alumni_ei | S10 |
| Historias de éxito con datos reales | _historia-exito.html.twig | Alumni + métricas de impacto | S10 |
| Badges por hitos PIIL | EiBadgeBridgeService | PuntosImpacto → BadgeAwardService | S11 |
| Micro-aprendizaje diario | CalendarioProgramaService | getLeccionDelDia() + push notification | S11 |
| Dashboard público de impacto | andalucia-ei-impacto-publico.html.twig | Datos agregados anonimizados | S11 |
| IRIS+ / SROI | SroiCalculatorService bridge | 6 indicadores IRIS+ mapeados | S11 |
| WCAG 2.1 AA completa | 28 templates auditados | aria-*, roles, contraste, focus | S12 |
| Push notifications programáticas | EiPushNotificationService | 8 tipos de evento → push | S12 |
| Adaptación idiomática (5 idiomas) | jaraba_i18n + AITranslationService | AR, RO, EN, FR, UK | S12 |

---

## 17. Tabla de Cumplimiento de Directrices

| ID Directriz | Regla | Cómo se cumple | Verificación |
|---|---|---|---|
| TENANT-001 | Toda query filtra por tenant | `FirmaWorkflowService` recibe `$tenantId` en queries | `validate-tenant-isolation.php` |
| TENANT-ISOLATION-ACCESS-001 | ACH verifica tenant match | `ExpedienteDocumentoAccessControlHandler` ya implementado | Manual review |
| CONTROLLER-READONLY-001 | No readonly en props heredadas | `FirmaDocumentoController` asigna en body | PHPStan L6 |
| PREMIUM-FORMS-PATTERN-001 | Forms extienden PremiumEntityFormBase | N/A — la firma no es un entity form, es un controller | — |
| SLIDE-PANEL-RENDER-001 | renderPlain() en slide-panel | `FirmaTactilController::firmaPanel()` | Test funcional |
| FORM-CACHE-001 | No setCached(TRUE) incondicional | No hay form_state en el flujo de firma | — |
| CSRF-API-001 | _csrf_request_header_token en API routes | Todas las rutas POST de firma | `grep routing.yml` |
| API-WHITELIST-001 | Campos permitidos en API | `FirmaDocumentoController` filtra input | Code review |
| ACCESS-STRICT-001 | Comparaciones (int)===(int) | En verificación de ownership/tenant | PHPStan |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() | drupalSettings, no hardcoded | grep `'/es/'` |
| SECRET-MGMT-001 | Secrets en env vars | Certificados via settings.secrets.php | `validate-all.sh` |
| AUDIT-SEC-001 | Webhooks con HMAC | N/A — no hay webhooks en firma | — |
| AUDIT-SEC-002 | Rutas con _permission | Todas las rutas de firma tienen permiso específico | `grep routing.yml` |
| CSS-VAR-ALL-COLORS-001 | Colores via var(--ej-*) | `_firmas.scss` usa tokens | `migrate-hex-to-tokens.php` |
| ICON-CONVENTION-001 | jaraba_icon() en Twig | Parciales de firma usan jaraba_icon | Template review |
| ICON-DUOTONE-001 | Variante default duotone | Iconos de firma en duotone | Template review |
| SCSS-001 | @use en cada parcial | `_firmas.scss` incluye `@use '../variables' as *` | Build SCSS |
| SCSS-COMPILE-VERIFY-001 | Timestamp CSS > SCSS | Verificado post-build | `validate-compiled-assets.php` |
| SCSS-COLORMIX-001 | color-mix() para alpha | Sin rgba() en _firmas.scss | grep `rgba` |
| ZERO-REGION-001 | Variables via hook_preprocess | `hook_preprocess_page()` inyecta drupalSettings | Runtime |
| ZERO-REGION-003 | #attached en preprocess | drupalSettings de firma en preprocess | Runtime |
| TWIG-INCLUDE-ONLY-001 | `only` en includes parciales | Todos los includes de _firma-*.html.twig | Template review |
| INNERHTML-XSS-001 | Drupal.checkPlain() en innerHTML | JS de firma sanitiza datos de API | Code review |
| CSRF-JS-CACHE-001 | Token CSRF cacheado en JS | Variable `csrfToken` en módulo JS | Code review |
| PRESAVE-RESILIENCE-001 | try-catch en servicios opcionales | FirmaWorkflowService con `?` deps + try-catch | Code review |
| OPTIONAL-CROSSMODULE-001 | @? para deps cross-módulo | services.yml usa @? para firma_digital, vault, etc. | `validate-optional-deps.php` |
| LOGGER-INJECT-001 | LoggerInterface directa | @logger.channel.jaraba_andalucia_ei → LoggerInterface | `validate-logger-injection.php` |
| UPDATE-HOOK-REQUIRED-001 | hook_update_N para campos nuevos | `hook_update_10015` para campos de firma | `validate-entity-integrity.php` |
| UPDATE-HOOK-CATCH-001 | catch(\Throwable) en hooks | Todos los try-catch usan \Throwable | grep `\Exception` |
| UPDATE-FIELD-DEF-001 | setName() + setTargetEntityTypeId() | En hook_update_10015 | Code review |
| MOCK-DYNPROP-001 | Clases anónimas en tests | FirmaWorkflowServiceTest usa anonymous classes | Test review |
| TEST-CACHE-001 | Cache metadata en mocks | Mocks implementan getCacheContexts/Tags/MaxAge | Test review |
| LABEL-NULLSAFE-001 | label() puede devolver NULL | Null-safe en mensajes de notificación | Code review |
| DOC-GUARD-001 | No sobreescribir master docs | Plan en docs/tareas/, no en 00_*.md | Pre-commit hook |
| RUNTIME-VERIFY-001 | Verificación post-implementación | 10 checks específicos de firma | /verify-runtime |
| IMPLEMENTATION-CHECKLIST-001 | 4 pilares de verificación | Complitud + Integridad + Consistencia + Coherencia | validate-all.sh |
| ENTITY-PREPROCESS-001 | template_preprocess para entities | ExpedienteDocumento ya tiene preprocess | Ya implementado |
| FIELD-UI-SETTINGS-TAB-001 | Settings form + local task | ExpedienteDocumento ya tiene Field UI | Ya implementado |

---

## Apéndice A: Estimación de Archivos por Sprint

| Sprint | Archivos Nuevos | Archivos Modificados |
|---|---|---|
| S1 | `FirmaWorkflowService.php`, `FirmaDocumentoController.php`, 2 rutas | `services.yml`, `routing.yml`, `.install` (hook_update_10015 + schema) |
| S2 | `FirmaTactilController.php`, `firma-electronica.js`, `_firmas.scss`, `_firma-pad.html.twig` | `libraries.yml`, `.module` (hook_theme + preprocess) |
| S3 | — | `AcuerdoParticipacionService.php`, `DaciService.php`, `ReciboServicioService.php`, `IncentiveReceiptService.php`, `IncentiveWaiverService.php`, `HojaServicioMentoriaService.php` |
| S4 | `_firma-pendientes.html.twig`, `_firma-masiva.html.twig` | `ParticipantePortalController.php`, `CoordinadorDashboardController.php`, `OrientadorDashboardController.php`, `.module` |
| S5 | `verificacion-documento.html.twig`, 1 ruta pública | `FirmaWorkflowService.php` (QR + TSA) |
| S6 | 3 test files | — |

**Total**: ~12 archivos nuevos, ~15 archivos modificados, 1 tabla DB nueva,
10+ campos nuevos en ExpedienteDocumento, 10 rutas nuevas.

---

## Apéndice B: Dependencias Externas

| Dependencia | Estado | Uso |
|---|---|---|
| TCPDF | Ya instalada | Generación y manipulación PDF |
| endroid/qr-code | Ya instalada | Generación QR de verificación |
| OpenSSL (ext-openssl) | Ya disponible | Firma digital, certificados |
| poppler-utils (pdfsig) | Opcional, disponible en Lando | Validación de firmas PAdES |
| AutoFirma | App desktop del firmante | Firma con certificado cualificado |
| FNMT TSA | Servicio externo | Sellado temporal |

---

> **Nota**: Este plan cumple con DOC-GUARD-001 — se crea como documento nuevo en
> `docs/tareas/`, NO modifica master docs. Las actualizaciones a master docs
> (DIRECTRICES, ARQUITECTURA, ÍNDICE) se harán en commit separado (`docs:`)
> tras la implementación, conforme a COMMIT-SCOPE-001.
