# Plan de Implementacion Stack de Cumplimiento Fiscal v1.0
# VeriFactu + Facturae B2G + E-Factura B2B

> **Fecha:** 2026-02-16
> **Ultima actualizacion:** 2026-02-16
> **Autor:** Claude Opus 4.6
> **Version:** 1.0.0
> **Estado:** Planificacion inicial
> **Vertical:** Plataforma transversal (todos los verticales con facturacion)
> **Modulos principales:** `jaraba_verifactu`, `jaraba_facturae`, `jaraba_einvoice_b2b`

---

## Tabla de Contenidos (TOC)

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
  - [1.1 Vision y Posicionamiento](#11-vision-y-posicionamiento)
  - [1.2 Marco Legal y Deadlines](#12-marco-legal-y-deadlines)
  - [1.3 Relacion con la infraestructura existente](#13-relacion-con-la-infraestructura-existente)
  - [1.4 Patron arquitectonico de referencia](#14-patron-arquitectonico-de-referencia)
  - [1.5 Avatares principales](#15-avatares-principales)
- [2. Tabla de Correspondencia con Especificaciones Tecnicas](#2-tabla-de-correspondencia-con-especificaciones-tecnicas)
- [3. Cumplimiento de Directrices del Proyecto](#3-cumplimiento-de-directrices-del-proyecto)
  - [3.1 Directriz: i18n — Textos siempre traducibles](#31-directriz-i18n--textos-siempre-traducibles)
  - [3.2 Directriz: Modelo SCSS con Federated Design Tokens](#32-directriz-modelo-scss-con-federated-design-tokens)
  - [3.3 Directriz: Dart Sass moderno](#33-directriz-dart-sass-moderno)
  - [3.4 Directriz: Frontend limpio sin regiones Drupal](#34-directriz-frontend-limpio-sin-regiones-drupal)
  - [3.5 Directriz: Body classes via hook_preprocess_html](#35-directriz-body-classes-via-hook_preprocess_html)
  - [3.6 Directriz: CRUD en modales slide-panel](#36-directriz-crud-en-modales-slide-panel)
  - [3.7 Directriz: Entidades con Field UI y Views](#37-directriz-entidades-con-field-ui-y-views)
  - [3.8 Directriz: No hardcodear configuracion](#38-directriz-no-hardcodear-configuracion)
  - [3.9 Directriz: Parciales Twig reutilizables](#39-directriz-parciales-twig-reutilizables)
  - [3.10 Directriz: Seguridad](#310-directriz-seguridad)
  - [3.11 Directriz: Comentarios de codigo](#311-directriz-comentarios-de-codigo)
  - [3.12 Directriz: Iconos SVG duotone](#312-directriz-iconos-svg-duotone)
  - [3.13 Directriz: AI via abstraccion @ai.provider](#313-directriz-ai-via-abstraccion-aiprovider)
  - [3.14 Directriz: Automaciones via hooks Drupal](#314-directriz-automaciones-via-hooks-drupal)
  - [3.15 Directriz: Configuracion del tema desde UI de Drupal](#315-directriz-configuracion-del-tema-desde-ui-de-drupal)
- [4. Arquitectura de los Modulos](#4-arquitectura-de-los-modulos)
  - [4.1 Modulo jaraba_verifactu](#41-modulo-jaraba_verifactu)
  - [4.2 Modulo jaraba_facturae](#42-modulo-jaraba_facturae)
  - [4.3 Modulo jaraba_einvoice_b2b](#43-modulo-jaraba_einvoice_b2b)
  - [4.4 Servicio compartido CertificateManagerService](#44-servicio-compartido-certificatemanagerservice)
  - [4.5 Compilacion SCSS](#45-compilacion-scss)
- [5. Estado por Fases](#5-estado-por-fases)
- [6. FASE 0: Infraestructura compartida y CertificateManagerService](#6-fase-0-infraestructura-compartida-y-certificatemanagerservice)
- [7. FASE 1: jaraba_verifactu — Entidades y Modelo de Datos](#7-fase-1-jaraba_verifactu--entidades-y-modelo-de-datos)
- [8. FASE 2: jaraba_verifactu — Servicios Core (Hash, Record, QR)](#8-fase-2-jaraba_verifactu--servicios-core-hash-record-qr)
- [9. FASE 3: jaraba_verifactu — AEAT SOAP y Remision](#9-fase-3-jaraba_verifactu--aeat-soap-y-remision)
- [10. FASE 4: jaraba_verifactu — Frontend, Dashboard y PDF](#10-fase-4-jaraba_verifactu--frontend-dashboard-y-pdf)
- [11. FASE 5: jaraba_verifactu — ECA Hooks, Cron y Tests](#11-fase-5-jaraba_verifactu--eca-hooks-cron-y-tests)
- [12. FASE 6: jaraba_facturae — Entidades y XML Builder](#12-fase-6-jaraba_facturae--entidades-y-xml-builder)
- [13. FASE 7: jaraba_facturae — XAdES, FACe y Servicios](#13-fase-7-jaraba_facturae--xades-face-y-servicios)
- [14. FASE 8: jaraba_facturae — Frontend, ECA y Tests](#14-fase-8-jaraba_facturae--frontend-eca-y-tests)
- [15. FASE 9: jaraba_einvoice_b2b — Entidades y UBL 2.1](#15-fase-9-jaraba_einvoice_b2b--entidades-y-ubl-21)
- [16. FASE 10: jaraba_einvoice_b2b — Servicios, SPFE y Tests](#16-fase-10-jaraba_einvoice_b2b--servicios-spfe-y-tests)
- [17. FASE 11: Integracion cross-modulo y Dashboard Fiscal Unificado](#17-fase-11-integracion-cross-modulo-y-dashboard-fiscal-unificado)
- [18. Paleta de Colores y Design Tokens](#18-paleta-de-colores-y-design-tokens)
- [19. Patron de Iconos SVG](#19-patron-de-iconos-svg)
- [20. Orden de Implementacion Global](#20-orden-de-implementacion-global)
- [21. Relacion con jaraba_billing y jaraba_foc](#21-relacion-con-jaraba_billing-y-jaraba_foc)
- [22. Registro de Cambios](#22-registro-de-cambios)

---

## 1. Resumen Ejecutivo

El Stack de Cumplimiento Fiscal es un conjunto de tres modulos Drupal 11 que cubren la totalidad de las obligaciones de facturacion electronica del marco legal espanol y europeo. La implementacion es **critica y no negociable**: el incumplimiento del RD 1007/2023 (VeriFactu) acarrea sanciones de hasta 50.000 EUR por ejercicio fiscal, y la Ley 25/2013 (Facturae B2G) imposibilita la facturacion a Administraciones Publicas.

Los tres modulos forman un ecosistema integrado donde cada pieza aborda un ambito normativo distinto pero comparte infraestructura comun (certificados digitales, entidades de configuracion por tenant, patrones de inmutabilidad y audit log):

- **`jaraba_verifactu`** (Doc 179) — Cumplimiento del Sistema VeriFactu (RD 1007/2023 + Orden HAC/1177/2024): registro de facturas con encadenamiento SHA-256, comunicacion SOAP con la AEAT, QR de verificacion y etiqueta VERI*FACTU. **Prioridad P0 CRITICA** — deadline legal 1 enero 2027 para sociedades.
- **`jaraba_facturae`** (Doc 180) — Generacion de facturas electronicas en formato Facturae 3.2.2 con firma XAdES-EPES y envio al portal FACe para facturacion B2G (Ley 25/2013). **Prioridad P1** — requerido cuando el motor institucional esta activo.
- **`jaraba_einvoice_b2b`** (Doc 181) — Facturacion electronica B2B bajo la Ley Crea y Crece (Ley 18/2022), con soporte dual UBL 2.1 (EN 16931) y Facturae 3.2.2, preparacion para la plataforma SPFE de la AEAT, y gestion de morosidad. **Prioridad P2** — reglamento pendiente de publicacion.

### 1.1 Vision y Posicionamiento

El Stack Fiscal posiciona a Jaraba Impact Platform como **la unica plataforma SaaS de impacto social que incluye cumplimiento fiscal integral nativo**, eliminando la necesidad de que los tenants contraten servicios de facturacion externos. El pitch diferenciador es:

_"Factura a tus clientes, a la Administracion y cumple con VeriFactu desde la misma plataforma donde gestionas tu negocio de impacto — sin herramientas externas, sin integraciones complejas, sin riesgo de sanciones."_

Los cinco pilares del stack:

1. **Compliance nativo**: El tenant no necesita configurar nada mas alla de su certificado digital. La plataforma genera registros VeriFactu, facturas Facturae y e-facturas B2B de forma automatica.
2. **Multi-tenant by design**: Cada tenant tiene su propia configuracion fiscal (NIF, certificado, series de facturacion, entorno AEAT), aislada del resto.
3. **Inmutabilidad contable**: Los registros VeriFactu y los logs de comunicacion FACe son append-only. No admiten edicion ni borrado, solo anulaciones compensatorias.
4. **Automatizacion ECA**: Cuando un BillingInvoice se marca como pagado en Stripe, se genera automaticamente el registro VeriFactu, la factura Facturae (si el comprador es AAPP) o la e-factura B2B (si es empresa privada).
5. **Dashboard fiscal unificado**: Un unico panel en `/admin/jaraba/fiscal` donde el gestor del tenant visualiza el estado de cumplimiento de los tres modulos, con alertas de certificados proximos a expirar, remisiones pendientes y facturas rechazadas.

### 1.2 Marco Legal y Deadlines

| Normativa | Modulo | Deadline | Sancion | Prioridad |
|-----------|--------|----------|---------|-----------|
| RD 1007/2023 + Orden HAC/1177/2024 (VeriFactu) | `jaraba_verifactu` | 1 ene 2027 (sociedades), 1 jul 2027 (autonomos) | Hasta 50.000 EUR/ejercicio | **P0 CRITICA** |
| Ley 25/2013 (Facturae B2G) | `jaraba_facturae` | Ya vigente | Rechazo de factura por AAPP | **P1 ALTA** |
| Ley 18/2022 Crea y Crece (E-Factura B2B) | `jaraba_einvoice_b2b` | ~H2 2027 (>8M EUR), ~H2 2028 (resto) | Hasta 10.000 EUR | **P2 MEDIA** |

**Impacto dual en el ecosistema:**

- **Jaraba como empresa**: Debe cumplir VeriFactu para sus propias facturas de suscripcion SaaS y Facturae 3.2.2 si factura a Administraciones Publicas (programas de empleo, subvenciones).
- **Tenants del ecosistema**: Cooperativas, productores, profesionales y empresas que operan en los verticales AgroConecta, ComercioConecta y ServiciosConecta necesitan generar facturas electronicas validas para sus propias operaciones B2B y B2G.

### 1.3 Relacion con la infraestructura existente

El Stack Fiscal se construye sobre la infraestructura consolidada del ecosistema, reutilizando componentes existentes y respetando los patrones arquitectonicos establecidos:

| Componente existente | Reutilizacion en Stack Fiscal | % Reutilizacion |
|---------------------|-------------------------------|----------------|
| **ecosistema_jaraba_core** | Entidades base (Tenant, Vertical, SaasPlan), TenantContextService, Design Tokens CSS, `hook_preprocess_html()` | 70% |
| **ecosistema_jaraba_theme** | Parciales Twig (_header, _footer, _copilot-fab), slide-panel singleton, page templates limpias | 80% |
| **jaraba_billing** | BillingInvoice como trigger de generacion de registros fiscales, StripeInvoiceService para datos de factura | 60% |
| **jaraba_foc** | FinancialTransaction append-only (patron reutilizable), StripeConnectService para split payments | 40% |
| **jaraba_security_compliance** | AuditLog entity para trazabilidad, IntegrityService para hash SHA-256 | 80% |
| **jaraba_credentials** | CryptographyService Ed25519 (patron de firma digital reutilizable) | 30% |
| **jaraba_email** | TemplateLoaderService para notificaciones de alertas de certificados y remisiones fallidas | 40% |

### 1.4 Patron arquitectonico de referencia

- **Content Entities con Field UI + Views** para todos los datos de negocio (registros VeriFactu, documentos Facturae, e-facturas B2B, configuraciones por tenant, logs de comunicacion).
- **Entidades append-only** para registros fiscales y logs: sin operacion de edicion ni borrado, solo creacion y anulaciones compensatorias.
- **Frontend limpio sin regiones Drupal**: Templates Twig full-width con parciales reutilizables, sin `page.content` ni bloques heredados.
- **CRUD en slide-panel modal**: Acciones de crear registros, consultar detalles, editar configuracion se abren en panel lateral.
- **Federated Design Tokens**: SCSS con variables locales `$fiscal-*` como fallback, consumo via `var(--ej-*)`.
- **Body classes via `hook_preprocess_html()`**: NUNCA `attributes.addClass()` en templates Twig para el body.
- **API REST versionada** bajo `/api/v1/verifactu/`, `/api/v1/facturae/`, `/api/v1/einvoice/` con autenticacion, rate limiting y permisos granulares.
- **Automaciones via hooks Drupal**: `hook_entity_insert/update`, `hook_cron`. NO ECA BPMN.
- **Textos siempre traducibles**: `$this->t()` en PHP, `{% trans %}` en Twig, `Drupal.t()` en JS.
- **Dart Sass moderno**: `color.adjust()` en lugar de `darken()`/`lighten()` deprecados, `color-mix()` en lugar de `rgba()`.
- **Certificados PKCS#12 gestionados por tenant** con almacenamiento seguro y alertas de expiracion.

### 1.5 Avatares principales

**Avatar 1: Maria — Gestora Administrativa de Cooperativa**

| Atributo | Descripcion |
|----------|-------------|
| **Nombre** | Maria Lopez Ruiz |
| **Rol** | Gestora administrativa en Cooperativa Aceites de Jaen (vertical AgroConecta) |
| **Ubicacion** | Jaen, Andalucia |
| **Situacion** | Gestiona facturacion de la cooperativa: 200+ facturas/mes a distribuidores (B2B) y 15-20 facturas/trimestre a programas publicos (B2G) |
| **Pain Point** | Usa 3 herramientas distintas para facturacion, VeriFactu, y envio a FACe. Teme las sanciones de VeriFactu porque no entiende la normativa |
| **Meta** | Que la plataforma genere automaticamente los registros VeriFactu y envie las facturas a FACe sin intervencion manual |

**Avatar 2: Carlos — Asesor Fiscal Autonomo**

| Atributo | Descripcion |
|----------|-------------|
| **Nombre** | Carlos Fernandez Garcia |
| **Rol** | Asesor fiscal independiente (vertical ServiciosConecta) |
| **Ubicacion** | Cordoba, Andalucia |
| **Situacion** | Gestiona la facturacion de 50+ clientes autonomos y PYMEs. Necesita cumplir VeriFactu para todos ellos |
| **Pain Point** | Cada cliente tiene su propio certificado digital y serie de facturacion. Necesita gestion multi-tenant nativa |
| **Meta** | Panel unificado donde ver el estado de cumplimiento VeriFactu de todos sus clientes, con alertas de certificados y remisiones pendientes |

---

## 2. Tabla de Correspondencia con Especificaciones Tecnicas

La siguiente tabla mapea cada especificacion tecnica del Stack Fiscal con su fase de implementacion, entidades principales y estimacion de esfuerzo.

| Doc # | Titulo Especificacion | Modulo | Fases | Entidades | Servicios | Endpoints API | Tests | Horas Est. |
|-------|----------------------|--------|-------|-----------|-----------|---------------|-------|------------|
| **178** | Auditoria VeriFactu World-Class (Gap Analysis) | Todos | Todas | N/A (analisis) | N/A | N/A | N/A | Referencia |
| **179** | Platform VeriFactu Implementation | `jaraba_verifactu` | F1-F5 | `verifactu_invoice_record` (append-only, 33+ campos, hash chain SHA-256), `verifactu_event_log` (SIF inmutable, 12 event types), `verifactu_remision_batch` (AEAT SOAP, max 1.000 registros), `verifactu_tenant_config` (certificado, NIF, serie, entorno) | VeriFactuHashService, VeriFactuRecordService, VeriFactuQrService, VeriFactuXmlService, VeriFactuRemisionService, VeriFactuPdfService | 21 endpoints (Admin 5, Records 6, Remision 5, Auditoria 5) | 23 (7 unit, 9 kernel, 7 functional) | 230-316h |
| **180** | Platform Facturae 3.2.2 + FACe B2G | `jaraba_facturae` | F6-F8 | `facturae_document` (50+ campos, Facturae XML completo, estados FACe), `facturae_tenant_config` (config fiscal, IBAN, retenciones, DIR3), `facturae_face_log` (append-only, SOAP req/res) | FacturaeXmlService, FacturaeXAdESService, FACeClientService, FacturaeNumberingService, FacturaeValidationService, FacturaeDIR3Service | 21 endpoints (Documentos 7, Firma 3, FACe 5, Config 3, DIR3 3) | 26 (8 unit, 10 kernel, 8 functional) | 230-304h |
| **181** | Platform E-Factura B2B (Ley Crea y Crece) | `jaraba_einvoice_b2b` | F9-F10 | `einvoice_document` (dual UBL/Facturae, inbound/outbound, payment status), `einvoice_tenant_config` (SPFE credentials, Peppol, payment terms), `einvoice_delivery_log` (append-only, multi-canal), `einvoice_payment_event` (morosidad Ley 3/2004) | EInvoiceUblService, EInvoiceFormatConverterService, EInvoiceDeliveryService, EInvoicePaymentStatusService, EInvoiceValidationService, SPFEClientService (stub) | 24 endpoints (Documentos 7, Envio 8, Pagos 5, Config 4) | 23 (7 unit, 9 kernel, 7 functional) | 260-336h |
| **182** | Gap Analysis Madurez Documental | Todos | Referencia | N/A | N/A | N/A | N/A | Referencia |

**Resumen de volumetria:**

| Metrica | jaraba_verifactu | jaraba_facturae | jaraba_einvoice_b2b | **TOTAL** |
|---------|-----------------|-----------------|---------------------|-----------|
| Content Entities | 4 | 3 | 4 | **11** |
| Servicios PHP | 6 | 6 | 6 | **18** |
| Endpoints REST API | 21 | 21 | 24 | **66** |
| ECA Hooks | 5 | 5 | 5 | **15** |
| Permisos RBAC | 7 | 8 | 9 | **24** |
| Tests PHPUnit | 23 | 26 | 23 | **72** |
| Templates Twig | 6 | 5 | 5 | **16** |
| Parciales Twig | 8 | 6 | 6 | **20** |
| Archivos SCSS | 8 | 6 | 6 | **20** |
| Iconos SVG (outline+duotone) | 8 | 6 | 6 | **20** |
| **Horas estimadas** | 230-316h | 230-304h | 260-336h | **720-956h** |
| **Coste estimado (45 EUR/h)** | 10.350-14.220 EUR | 10.350-13.680 EUR | 11.700-15.120 EUR | **32.400-43.020 EUR** |

---

## 3. Cumplimiento de Directrices del Proyecto

Esta seccion documenta como el Stack de Cumplimiento Fiscal cumple con cada directriz obligatoria del proyecto, segun `docs/00_DIRECTRICES_PROYECTO.md` y los workflows definidos en `.agent/workflows/`.

### 3.1 Directriz: i18n — Textos siempre traducibles

**Referencia:** `.agent/workflows/i18n-traducciones.md`

Todo texto visible al usuario DEBE ser traducible. No se admite ningun string hardcodeado en la interfaz. Esto aplica a los tres modulos del stack fiscal.

| Contexto | Metodo | Ejemplo Stack Fiscal |
|----------|--------|---------------------|
| PHP (Controllers, Services) | `$this->t('Texto')` | `$this->t('VeriFactu record generated successfully')` |
| PHP (Entities, fuera de DI) | `new TranslatableMarkup('Texto')` | `new TranslatableMarkup('VeriFactu Invoice Record')` |
| Twig templates | `{% trans %}Texto{% endtrans %}` | `{% trans %}Pending AEAT submission{% endtrans %}` |
| Twig con variables | `{{ 'Texto @var'\|t({'@var': value}) }}` | `{{ 'Invoice @num from @date'\|t({'@num': num, '@date': date}) }}` |
| JavaScript | `Drupal.t('Texto')` | `Drupal.t('Chain integrity verified')` |
| Annotations de entidad | `@Translation('Texto')` | `@Translation('VeriFactu Invoice Record')` |
| YAML (menus, permisos) | Texto directo (Drupal traduce) | `title: 'VeriFactu compliance dashboard'` |
| Formularios | `'#title' => $this->t('Texto')` | `'#title' => $this->t('PKCS#12 certificate file')` |

**Ejemplo CORRECTO:**
```php
// En VeriFactuDashboardController.php
$this->messenger()->addStatus($this->t('Generated @count VeriFactu records for invoice @invoice.', [
  '@count' => $recordCount,
  '@invoice' => $invoiceNumber,
]));
```

**Ejemplo PROHIBIDO:**
```php
// NUNCA hardcodear strings visibles al usuario
$this->messenger()->addStatus("Generados $count registros VeriFactu.");
```

### 3.2 Directriz: Modelo SCSS con Federated Design Tokens

**Referencia:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`, `.agent/workflows/scss-estilos.md`

Los tres modulos fiscales como modulos satelite NUNCA definen variables `$ej-*`. Solo consumen CSS Custom Properties con fallbacks inline. Cada modulo tiene su propio `_variables-fiscal.scss` con variables locales como fallback.

| Capa | Nombre | Ubicacion | Que define el Stack Fiscal |
|------|--------|-----------|---------------------------|
| 1 | SCSS Tokens (SSOT) | `ecosistema_jaraba_core/scss/_variables.scss` | Nada (solo lectura) |
| 2 | CSS Custom Properties | `ecosistema_jaraba_core/scss/_injectable.scss` | Nada (solo lectura) |
| 3 | Componente local | `jaraba_verifactu/scss/_variables-fiscal.scss` | Variables locales `$fiscal-*` como fallback |
| 4 | Tenant Override | `hook_preprocess_html()` del tema | Nada (gestionado por ecosistema_jaraba_core) |

**Ejemplo CORRECTO:**
```scss
// jaraba_verifactu/scss/_variables-fiscal.scss
// Variables locales del stack fiscal - SOLO como fallback para var(--ej-*)
$fiscal-primary: #1B4332;      // Verde Hacienda Profundo
$fiscal-accent: #2D6A4F;       // Verde Fiscal Medio
$fiscal-surface: #F0FFF4;      // Fondo verde suave
$fiscal-success: #059669;      // Verde Valido/Enviado
$fiscal-danger: #DC2626;       // Rojo Rechazado/Error
$fiscal-warning: #D97706;      // Ambar Pendiente
$fiscal-info: #2563EB;         // Azul Informativo
$fiscal-aeat-blue: #003366;    // Azul AEAT institucional
$fiscal-face-green: #006633;   // Verde FACe portal
$fiscal-eu-blue: #003399;      // Azul Union Europea
$fiscal-text: #1A1A2E;         // Texto principal
$fiscal-text-light: #6B7280;   // Texto secundario

// USO en parciales SCSS del modulo:
.verifactu-record-card {
  background: var(--ej-bg-surface, #{$fiscal-surface});
  color: var(--ej-text-primary, #{$fiscal-text});
  border-left: 4px solid var(--ej-color-success, #{$fiscal-success});
}

.verifactu-badge--pending {
  background: color-mix(in srgb, var(--ej-color-warning, #{$fiscal-warning}) 15%, transparent);
  color: var(--ej-color-warning, #{$fiscal-warning});
}
```

**Ejemplo PROHIBIDO:**
```scss
// NUNCA hacer esto en un modulo satelite
$ej-color-primary: #1B4332;  // PROHIBIDO: redefine token SSOT
@import '../../ecosistema_jaraba_core/scss/variables';  // PROHIBIDO: @import deprecado
background: rgba(27, 67, 50, 0.15);  // PROHIBIDO: usar color-mix() en su lugar
```

### 3.3 Directriz: Dart Sass moderno

**Referencia:** `.agent/workflows/scss-estilos.md`

Toda funcion de manipulacion de color debe usar la sintaxis moderna de Dart Sass. Regla P4-COLOR-002: usar `color-mix()` para transparencias, nunca `rgba()` hardcodeado. Regla SCSS-002: zero `@import`, solo `@use`.

**CORRECTO:**
```scss
@use 'sass:color';

.fiscal-status--valid {
  background: color-mix(in srgb, var(--ej-color-success, #{$fiscal-success}) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--ej-color-success, #{$fiscal-success}) 30%, transparent);
}

.fiscal-btn--hover {
  background: color.adjust($fiscal-primary, $lightness: -10%);
}

.fiscal-card--elevated {
  box-shadow: 0 4px 12px color-mix(in srgb, var(--ej-color-corporate, #233D63) 12%, transparent);
}
```

**PROHIBIDO (funciones deprecadas):**
```scss
background: darken($fiscal-primary, 10%);     // DEPRECADO
background: lighten($fiscal-accent, 10%);     // DEPRECADO
background: rgba($fiscal-primary, 0.15);      // PROHIBIDO: usar color-mix()
@import 'variables-fiscal';                   // PROHIBIDO: usar @use
```

**Compilacion dentro del contenedor Docker:**
```bash
# jaraba_verifactu
lando ssh -c "cd /app/web/modules/custom/jaraba_verifactu && \
  export NVM_DIR=\"\$HOME/.nvm\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && . \"\$NVM_DIR/nvm.sh\" && \
  nvm use --lts && npm run build"

# jaraba_facturae
lando ssh -c "cd /app/web/modules/custom/jaraba_facturae && \
  export NVM_DIR=\"\$HOME/.nvm\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && . \"\$NVM_DIR/nvm.sh\" && \
  nvm use --lts && npm run build"

# jaraba_einvoice_b2b
lando ssh -c "cd /app/web/modules/custom/jaraba_einvoice_b2b && \
  export NVM_DIR=\"\$HOME/.nvm\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && . \"\$NVM_DIR/nvm.sh\" && \
  nvm use --lts && npm run build"

# Limpiar cache
lando drush cr
```

### 3.4 Directriz: Frontend limpio sin regiones Drupal

**Referencia:** `.agent/workflows/frontend-page-pattern.md`

Todas las paginas frontend del Stack Fiscal usan templates Twig limpias que renderizan el HTML completo sin `{{ page.content }}`, sin bloques heredados, sin sidebars, y con layout full-width pensado para movil. El tenant no tiene acceso al tema de administracion de Drupal.

**Estructura obligatoria de cada pagina frontend:**

```twig
{# page--verifactu.html.twig #}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('jaraba_verifactu/verifactu.dashboard') }}

<div class="page-wrapper page-wrapper--clean page-wrapper--premium page-wrapper--fiscal">

  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    'site_name': site_name,
    'site_slogan': site_slogan,
    'logo': logo,
    'logged_in': logged_in
  } only %}

  <main class="main-content main-content--full main-content--fiscal" role="main">
    <div class="main-content__inner main-content__inner--full">
      {# Contenido especifico de la pagina - SIN page.content #}
      {# Los datos llegan desde hook_preprocess_page via variables Twig #}

      {% include '@jaraba_verifactu/partials/_verifactu-stats.html.twig' with {
        'stats': verifactu_stats
      } only %}

      {% include '@jaraba_verifactu/partials/_verifactu-records-table.html.twig' with {
        'records': verifactu_records,
        'pagination': pagination
      } only %}
    </div>
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    'site_name': site_name
  } only %}

  {% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' only %}
</div>
```

**Ejemplo PROHIBIDO:**
```twig
{# NUNCA usar regiones de Drupal para contenido del modulo #}
<main>{{ page.content }}</main>
{# NUNCA usar bloques heredados #}
{{ drupal_block('system_main_block') }}
```

**Paginas frontend del Stack Fiscal:**

| Template | Ruta | Modulo | Proposito |
|----------|------|--------|-----------|
| `page--verifactu.html.twig` | `/verifactu` | jaraba_verifactu | Dashboard VeriFactu del tenant |
| `page--verifactu-records.html.twig` | `/verifactu/records` | jaraba_verifactu | Listado de registros VeriFactu |
| `page--facturae.html.twig` | `/facturae` | jaraba_facturae | Dashboard Facturae B2G |
| `page--facturae-documents.html.twig` | `/facturae/documents` | jaraba_facturae | Listado de documentos Facturae |
| `page--einvoice.html.twig` | `/einvoice` | jaraba_einvoice_b2b | Dashboard E-Factura B2B |
| `page--fiscal.html.twig` | `/admin/jaraba/fiscal` | ecosistema_jaraba_core | Dashboard fiscal unificado (admin) |

### 3.5 Directriz: Body classes via hook_preprocess_html

**Referencia:** `.agent/workflows/frontend-page-pattern.md`

Las clases anadidas con `attributes.addClass()` en templates Twig **NO funcionan para el body**. Se DEBE usar `hook_preprocess_html()`.

**CORRECTO (en jaraba_verifactu.module):**
```php
/**
 * Implements hook_preprocess_html().
 *
 * Inyecta clases CSS al <body> segun la ruta activa del modulo VeriFactu.
 * DIRECTRIZ: NUNCA usar attributes.addClass() en templates Twig para body.
 */
function jaraba_verifactu_preprocess_html(array &$variables): void {
  $route_name = \Drupal::routeMatch()->getRouteName();

  // Mapa de rutas del modulo VeriFactu a clases CSS del body.
  $routeClasses = [
    'jaraba_verifactu.dashboard' => 'page--verifactu-dashboard',
    'jaraba_verifactu.records' => 'page--verifactu-records',
    'jaraba_verifactu.record.view' => 'page--verifactu-record-detail',
    'jaraba_verifactu.remision' => 'page--verifactu-remision',
    'jaraba_verifactu.audit' => 'page--verifactu-audit',
    'jaraba_verifactu.settings' => 'page--verifactu-settings',
  ];

  if (isset($routeClasses[$route_name])) {
    $variables['attributes']['class'][] = $routeClasses[$route_name];
    $variables['attributes']['class'][] = 'page--fiscal';
    $variables['attributes']['class'][] = 'page--clean-layout';
  }
}
```

**Patron identico para jaraba_facturae y jaraba_einvoice_b2b** con sus respectivos mapas de rutas.

**PROHIBIDO:**
```twig
{# NUNCA usar esto para body classes #}
{% set attributes = attributes.addClass('page--verifactu') %}
```

### 3.6 Directriz: CRUD en modales slide-panel

**Referencia:** `.agent/workflows/slide-panel-modales.md`

Todas las acciones de crear/editar/ver en las paginas frontend del Stack Fiscal se abren en un slide-panel modal para que el usuario no abandone la pagina en la que esta trabajando.

**Acciones que se abren en slide-panel:**

| Modulo | Accion | Slide-panel URL |
|--------|--------|----------------|
| jaraba_verifactu | Ver detalle de registro VeriFactu | `/verifactu/record/{id}/view?ajax=1` |
| jaraba_verifactu | Configurar tenant VeriFactu | `/verifactu/config/edit?ajax=1` |
| jaraba_verifactu | Ver detalle de remision AEAT | `/verifactu/remision/{id}/view?ajax=1` |
| jaraba_facturae | Ver documento Facturae | `/facturae/document/{id}/view?ajax=1` |
| jaraba_facturae | Crear factura Facturae manual | `/facturae/document/add?ajax=1` |
| jaraba_facturae | Configurar tenant Facturae | `/facturae/config/edit?ajax=1` |
| jaraba_facturae | Buscar unidad DIR3 | `/facturae/dir3/search?ajax=1` |
| jaraba_einvoice_b2b | Ver e-factura B2B | `/einvoice/document/{id}/view?ajax=1` |
| jaraba_einvoice_b2b | Crear e-factura manual | `/einvoice/document/add?ajax=1` |
| jaraba_einvoice_b2b | Ver estado de pago | `/einvoice/payment/{id}/view?ajax=1` |

**Patron HTML (data attributes, sin JS adicional):**
```html
<!-- Boton para ver detalle de registro VeriFactu -->
<button class="btn btn--outline"
        data-slide-panel="record-{{ record.id }}"
        data-slide-panel-url="/verifactu/record/{{ record.id }}/view?ajax=1"
        data-slide-panel-title="{{ 'VeriFactu record detail'|t }}"
        data-slide-panel-size="--large">
  {{ jaraba_icon('fiscal', 'invoice-record', { size: '16px' }) }}
  {% trans %}View details{% endtrans %}
</button>
```

**Patron PHP en Controller (deteccion AJAX):**
```php
/**
 * Renderiza el detalle de un registro VeriFactu.
 *
 * Si la peticion es AJAX (slide-panel), devuelve solo el HTML del contenido.
 * Si es peticion normal, devuelve el render array completo.
 */
public function viewRecord(Request $request, int $record_id): Response|array {
  $record = $this->entityTypeManager->getStorage('verifactu_invoice_record')->load($record_id);
  $build = [
    '#theme' => 'verifactu_record_detail',
    '#record' => $record,
  ];

  if ($request->isXmlHttpRequest()) {
    $html = (string) $this->renderer->render($build);
    return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
  }

  return $build;
}
```

**Dependencia de libreria en cada modulo:**
```yaml
dependencies:
  - ecosistema_jaraba_theme/slide-panel
```

### 3.7 Directriz: Entidades con Field UI y Views

**Referencia:** `.agent/workflows/drupal-custom-modules.md`, `docs/00_DIRECTRICES_PROYECTO.md`

Todas las entidades del Stack Fiscal DEBEN ser Content Entities con soporte para Field UI y Views, asegurando plena navegacion en `/admin/structure` y `/admin/content`.

**Checklist obligatorio por entidad:**

- `@ContentEntityType` annotation completa con `id`, `label`, `label_collection`, `label_singular`, `label_plural`
- Handlers: `list_builder`, `views_data` (`Drupal\views\EntityViewsData`), `form` (default/add/edit/delete), `access`, `route_provider` (`AdminHtmlRouteProvider`)
- `field_ui_base_route` apuntando a la ruta de settings en `/admin/structure/`
- Links: `canonical`, `add-form`, `edit-form`, `delete-form`, `collection`
- Collection en `/admin/content/verifactu-{entity}` (pestana en Content)
- Settings en `/admin/structure/verifactu-{entity}` (enlace en Structure)
- 4 archivos YAML de navegacion admin: `.links.menu.yml`, `.links.task.yml`, `.links.action.yml`, `.links.contextual.yml`

**Navegacion admin completa para las 11 entidades:**

| Entidad | Collection (/admin/content/) | Settings (/admin/structure/) |
|---------|------------------------------|------------------------------|
| `verifactu_invoice_record` | `/admin/content/verifactu-records` | `/admin/structure/verifactu-records` |
| `verifactu_event_log` | `/admin/content/verifactu-events` | `/admin/structure/verifactu-events` |
| `verifactu_remision_batch` | `/admin/content/verifactu-remisions` | `/admin/structure/verifactu-remisions` |
| `verifactu_tenant_config` | `/admin/content/verifactu-config` | `/admin/structure/verifactu-config` |
| `facturae_document` | `/admin/content/facturae-documents` | `/admin/structure/facturae-documents` |
| `facturae_tenant_config` | `/admin/content/facturae-config` | `/admin/structure/facturae-config` |
| `facturae_face_log` | `/admin/content/facturae-logs` | `/admin/structure/facturae-logs` |
| `einvoice_document` | `/admin/content/einvoice-documents` | `/admin/structure/einvoice-documents` |
| `einvoice_tenant_config` | `/admin/content/einvoice-config` | `/admin/structure/einvoice-config` |
| `einvoice_delivery_log` | `/admin/content/einvoice-delivery-logs` | `/admin/structure/einvoice-delivery-logs` |
| `einvoice_payment_event` | `/admin/content/einvoice-payments` | `/admin/structure/einvoice-payments` |

**Ejemplo CORRECTO (VeriFactuInvoiceRecord):**
```php
/**
 * Define la entidad Registro VeriFactu (factura).
 *
 * Entidad APPEND-ONLY: no admite edicion ni borrado. Solo creacion y anulacion.
 * Cada registro incluye un hash SHA-256 encadenado al registro anterior,
 * conforme al Anexo II del RD 1007/2023.
 *
 * @ContentEntityType(
 *   id = "verifactu_invoice_record",
 *   label = @Translation("VeriFactu Invoice Record"),
 *   label_collection = @Translation("VeriFactu Invoice Records"),
 *   label_singular = @Translation("VeriFactu invoice record"),
 *   label_plural = @Translation("VeriFactu invoice records"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_verifactu\VeriFactuInvoiceRecordListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_verifactu\Form\VeriFactuInvoiceRecordForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_verifactu\VeriFactuInvoiceRecordAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   field_ui_base_route = "jaraba_verifactu.invoice_record.settings",
 *   fieldable = TRUE,
 *   admin_permission = "administer verifactu",
 * )
 */
```

**Nota sobre entidades append-only:** Las entidades `verifactu_invoice_record`, `verifactu_event_log`, `facturae_face_log` y `einvoice_delivery_log` son inmutables. El AccessControlHandler deniega las operaciones `update` y `delete` para todos los roles excepto Platform Admin, y solo para propositos de administracion de emergencia (nunca en operacion normal).

### 3.8 Directriz: No hardcodear configuracion

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md`

Toda configuracion de negocio DEBE ser editable desde la UI de Drupal sin tocar codigo. Los valores por defecto se definen en `config/install/*.yml` y se gestionan via formularios de configuracion en `/admin/config/jaraba/`.

| Dato | Mecanismo | Ejemplo |
|------|-----------|---------|
| URL del servicio AEAT (produccion/test) | Config Settings | `jaraba_verifactu.settings.aeat_endpoint` |
| Intervalo de control de flujo AEAT (60s) | Config Settings | `jaraba_verifactu.settings.flow_control_seconds` |
| Max registros por remision (1.000) | Config Settings | `jaraba_verifactu.settings.max_records_per_batch` |
| Reintentos maximos SOAP (5) | Config Settings | `jaraba_verifactu.settings.max_retries` |
| URL portal FACe (produccion/staging) | Config Settings | `jaraba_facturae.settings.face_endpoint` |
| Patron de numeracion Facturae | Campo en `facturae_tenant_config` | `{SERIE}{YYYY}-{NUM:5}` |
| Terminos de pago B2B por defecto (30 dias) | Config Settings | `jaraba_einvoice_b2b.settings.default_payment_terms` |
| Umbral de morosidad (dias) | Config Settings | `jaraba_einvoice_b2b.settings.overdue_threshold_days` |
| Certificado PKCS#12 por tenant | Campo file en `*_tenant_config` | Subida via formulario Drupal |
| NIF/CIF del tenant | Campo en `*_tenant_config` | Editable en UI de configuracion fiscal |
| Entorno AEAT (produccion/pruebas) | Campo en `verifactu_tenant_config` | Select en formulario |
| Prompts de clasificacion IA | Config Settings (text_long) | Editables sin tocar codigo |

**Patron de fallback con `?:` (falsy coalesce):**
```php
// CORRECTO: Usa ?: porque Drupal config puede devolver string vacio ''.
$aeatEndpoint = $this->config('jaraba_verifactu.settings')
  ->get('aeat_endpoint') ?: 'https://prewww10.aeat.es/wlpl/SSII-FACT/ws/fe/SiiFactFEV2SOAP';

// INCORRECTO: ?? solo comprueba null, no string vacio.
$aeatEndpoint = $this->config('jaraba_verifactu.settings')
  ->get('aeat_endpoint') ?? 'https://prewww10.aeat.es/...';
```

**Configuracion del tema para parciales fiscales:** Los textos del footer, header, y labels generales se configuran desde la UI del tema (`/admin/appearance/settings/ecosistema_jaraba_theme`), NO hardcodeados en templates. Los parciales del Stack Fiscal reciben las variables del tema via `theme_settings` inyectado en `hook_preprocess_page()`.

### 3.9 Directriz: Parciales Twig reutilizables

**Referencia:** `.agent/workflows/frontend-page-pattern.md`

Antes de crear un nuevo parcial, verificar si ya existe uno reutilizable en el tema o en otro modulo. Los parciales del tema se incluyen con `@ecosistema_jaraba_theme`, los del modulo con `@jaraba_verifactu`.

**Parciales existentes que el Stack Fiscal DEBE reutilizar (NO duplicar):**

| Parcial | Ubicacion | Uso en Stack Fiscal |
|---------|-----------|---------------------|
| `_header.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Header de todas las paginas frontend |
| `_footer.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Footer de todas las paginas frontend |
| `_copilot-fab.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | FAB del copilot IA en dashboards |
| `_stats.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | KPI cards en dashboards |
| `_avatar-nav.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Navegacion contextual por avatar |

**Parciales NUEVOS del Stack Fiscal (organizados por modulo):**

**jaraba_verifactu:**

| Parcial | Reutilizado en |
|---------|----------------|
| `_verifactu-stats.html.twig` | Dashboard, dashboard fiscal unificado |
| `_verifactu-record-card.html.twig` | Listado de registros, busqueda, detalle |
| `_verifactu-chain-status.html.twig` | Dashboard, detalle de registro, auditoria |
| `_verifactu-remision-card.html.twig` | Listado de remisiones, detalle |
| `_verifactu-qr-preview.html.twig` | Detalle de registro, preview de PDF |
| `_certificate-status.html.twig` | Dashboard VeriFactu, Facturae, E-Factura (COMPARTIDO) |
| `_fiscal-status-badge.html.twig` | Todas las tablas y cards de los 3 modulos (COMPARTIDO) |
| `_fiscal-tenant-selector.html.twig` | Dashboard fiscal unificado (COMPARTIDO) |

**jaraba_facturae:**

| Parcial | Reutilizado en |
|---------|----------------|
| `_facturae-stats.html.twig` | Dashboard Facturae, dashboard fiscal unificado |
| `_facturae-document-card.html.twig` | Listado de documentos, busqueda |
| `_facturae-face-status.html.twig` | Detalle de documento, listado |
| `_facturae-dir3-search.html.twig` | Formulario de creacion, slide-panel |
| `_facturae-signature-info.html.twig` | Detalle de documento firmado |
| `_facturae-xml-preview.html.twig` | Detalle de documento, auditoria |

**jaraba_einvoice_b2b:**

| Parcial | Reutilizado en |
|---------|----------------|
| `_einvoice-stats.html.twig` | Dashboard E-Factura, dashboard fiscal unificado |
| `_einvoice-document-card.html.twig` | Listado de documentos, busqueda |
| `_einvoice-delivery-status.html.twig` | Detalle de documento, listado |
| `_einvoice-payment-timeline.html.twig` | Detalle de pago, morosidad |
| `_einvoice-format-badge.html.twig` | Indicador UBL/Facturae en listados |
| `_einvoice-morosidad-alert.html.twig` | Dashboard, alertas de morosidad |

### 3.10 Directriz: Seguridad

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md`, reglas AUDIT-SEC-001/002/003

- **Permisos granulares**: Los 24 permisos RBAC cubren operaciones especificas (`_permission` en routing, NUNCA `_user_is_logged_in`). Los registros VeriFactu no pueden ser borrados por ningun rol (integridad legal).
- **HMAC en webhooks**: Si se implementa recepcion de callbacks de AEAT o FACe, validar HMAC antes de procesar.
- **Certificados PKCS#12**: Almacenados en directorio privado de Drupal (`private://certificates/`), nunca en `public://`. Contrasena cifrada en campo de tipo `password` (no exportable en config).
- **Aislamiento de tenant**: Todas las queries incluyen filtro de `tenant_id` via `TenantContextService`. NUNCA consultar datos fiscales cross-tenant (regla TENANT-001).
- **Sanitizacion de datos en prompts**: Los datos fiscales interpolados en prompts IA para clasificacion o resumen se sanitizan contra whitelist.
- **Rate limiting en API**: Flood API para endpoints REST publicos (100 req/hora/usuario). Los endpoints de remision AEAT respetan el flow control de 60 segundos entre envios.
- **Inmutabilidad legal**: Los registros VeriFactu, logs FACe y logs de entrega E-Factura son append-only. El AccessControlHandler deniega `update`/`delete`.
- **DB indices**: Indices en `tenant_id`, `hash`, `aeat_status`, `created` para todas las entidades de alto volumen (regla AUDIT-PERF-001).
- **LockBackendInterface**: Operaciones financieras criticas (calculo de hash encadenado, numeracion secuencial) usan lock para evitar condiciones de carrera (regla AUDIT-PERF-002).

### 3.11 Directriz: Comentarios de codigo

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md`

Los comentarios en espanol cubren tres dimensiones:

1. **Estructura**: Organizacion, relaciones entre modulos, patrones arquitectonicos del stack fiscal.
2. **Logica**: Proposito (por que se calcula el hash asi), reglas de negocio (Anexo II del RD 1007/2023), decisiones de diseno (por que append-only), edge cases (que pasa si la cadena de hash se rompe).
3. **Sintaxis**: Parametros (tipo + proposito), valores de retorno, excepciones, estructuras de datos del XML SOAP.

**Ejemplo CORRECTO:**
```php
/**
 * Calcula el hash SHA-256 de un registro de alta conforme al Anexo II del RD 1007/2023.
 *
 * El hash se calcula concatenando los siguientes campos en el orden exacto
 * especificado por la AEAT: NIF emisor + numero factura + fecha expedicion +
 * tipo factura + cuota tributaria + importe total + tipo registro + hash anterior.
 * El "hash anterior" es el hash del ultimo registro del mismo emisor (NIF+serie),
 * formando una cadena inmutable que garantiza la integridad de los registros.
 *
 * Si es el primer registro de la cadena (no existe hash anterior), se usa
 * la cadena vacia como valor del campo "hash anterior" segun la especificacion.
 *
 * @param array $fields Campos del registro segun Anexo II (NIF, numero, fecha, etc.)
 * @param string|null $previousHash Hash SHA-256 del registro anterior en la cadena, o NULL si es el primero
 * @return string Hash SHA-256 en hexadecimal (64 caracteres)
 * @throws \InvalidArgumentException Si falta algun campo obligatorio del Anexo II
 */
```

### 3.12 Directriz: Iconos SVG duotone

**Referencia:** `.agent/workflows/scss-estilos.md`

Se crean 10 iconos nuevos en la categoria `fiscal/` del sistema de iconos del ecosistema. Cada icono tiene version outline y duotone.

**Directorio:** `ecosistema_jaraba_core/images/icons/fiscal/`

| Icono | Nombre | Uso principal |
|-------|--------|---------------|
| Factura con check | `invoice-check` | Registro VeriFactu valido |
| Cadena hash | `hash-chain` | Integridad de cadena SHA-256 |
| Codigo QR | `qr-verify` | QR de verificacion AEAT |
| Sello AEAT | `aeat-seal` | Estado de remision AEAT |
| Documento firmado | `signed-document` | Factura firmada XAdES |
| Portal FACe | `face-portal` | Estado de envio FACe |
| Factura B2B | `invoice-b2b` | E-Factura entre empresas |
| Estado de pago | `payment-status` | Tracking de morosidad |
| Certificado digital | `certificate` | Gestion de PKCS#12 |
| Escudo fiscal | `shield-fiscal` | Dashboard fiscal, compliance |

**Uso en Twig:**
```twig
{{ jaraba_icon('fiscal', 'invoice-check', { color: 'success', size: '24px' }) }}
{{ jaraba_icon('fiscal', 'hash-chain', { variant: 'duotone', color: 'corporate', size: '32px' }) }}
{{ jaraba_icon('fiscal', 'aeat-seal', { color: 'fiscal-aeat', size: '20px' }) }}
```

**Estructura del SVG duotone:**
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
  <!-- Capa de fondo (opacidad 0.3) -->
  <path d="..." fill="currentColor" opacity="0.3"/>
  <!-- Capa principal (trazo solido) -->
  <path d="..." stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

### 3.13 Directriz: AI via abstraccion @ai.provider

**Referencia:** `.agent/workflows/ai-integration.md`

El Stack Fiscal utiliza IA de forma limitada y especifica. NUNCA implementar clientes HTTP directos a APIs de IA.

**Usos de IA en el Stack Fiscal:**

| Funcion | Proveedor | Modelo | Razon |
|---------|-----------|--------|-------|
| Clasificacion automatica de tipo de factura | Gemini 2.0 Flash | `gemini-2.0-flash` | Strict Grounding, JSON schema, temperatura 0.1 |
| Extraccion de datos de factura recibida (OCR+NLP) | Gemini 2.0 Flash | `gemini-2.0-flash` | Vision + texto, extraccion estructurada |
| Sugerencia de codigo DIR3 para comprador publico | Claude Haiku | `claude-haiku-4-5` | Rapido, matching contra directorio DIR3 |
| Deteccion de anomalias en patrones de facturacion | Gemini 2.0 Flash | `gemini-2.0-flash` | Analisis batch, bajo coste |

**CORRECTO:**
```php
// Clasificar tipo de factura con Gemini via @ai.provider.
$provider = \Drupal::service('ai.provider');
$response = $provider->chat([
  new ChatMessage('system', $this->getInvoiceClassificationPrompt()),
  new ChatMessage('user', json_encode($invoiceData)),
], 'gemini-2.0-flash', ['temperature' => 0.1, 'response_format' => 'json']);
```

**PROHIBIDO:**
```php
// NUNCA llamada HTTP directa a API de IA.
$client = new \GuzzleHttp\Client();
$response = $client->post('https://generativelanguage.googleapis.com/v1beta/...', [...]);
```

### 3.14 Directriz: Automaciones via hooks Drupal

**Referencia:** `.agent/workflows/drupal-eca-hooks.md`

Las automaciones del Stack Fiscal se implementan via hooks nativos de Drupal, NO via ECA BPMN.

**Hooks a implementar en los tres modulos:**

| Hook | Modulo | Entidad trigger | Accion |
|------|--------|----------------|--------|
| `hook_entity_insert` | jaraba_verifactu | `billing_invoice` (pago confirmado) | Generar registro VeriFactu de alta automaticamente |
| `hook_entity_update` | jaraba_verifactu | `billing_invoice` (anulacion) | Generar registro VeriFactu de anulacion |
| `hook_cron` | jaraba_verifactu | N/A | Procesar cola de remisiones AEAT (cada 60s min) |
| `hook_cron` | jaraba_verifactu | N/A | Verificacion diaria de integridad de cadena de hash (03:00 UTC) |
| `hook_cron` | jaraba_verifactu | N/A | Alerta semanal de certificados proximos a expirar |
| `hook_entity_insert` | jaraba_facturae | `billing_invoice` (comprador es AAPP) | Generar documento Facturae automaticamente |
| `hook_cron` | jaraba_facturae | N/A | Sincronizar estado FACe cada 4 horas |
| `hook_cron` | jaraba_facturae | N/A | Alerta de certificados proximos a expirar |
| `hook_entity_insert` | jaraba_facturae | `facturae_document` (firmado) | Generar PDF con QR y firma |
| `hook_entity_update` | jaraba_facturae | `facturae_document` | Generar factura rectificativa si se anula |
| `hook_entity_insert` | jaraba_einvoice_b2b | `billing_invoice` (comprador empresa privada) | Generar e-factura B2B automaticamente |
| `hook_cron` | jaraba_einvoice_b2b | N/A | Sincronizar estado de pago por morosidad diaria |
| `hook_cron` | jaraba_einvoice_b2b | N/A | Deteccion de morosidad (>30 dias, >60 dias) |
| `hook_entity_insert` | jaraba_einvoice_b2b | `einvoice_document` (inbound) | Registrar factura recibida y notificar |
| `hook_cron` | jaraba_einvoice_b2b | N/A | Comunicacion periodica con SPFE |

### 3.15 Directriz: Configuracion del tema desde UI de Drupal

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md`, seccion 2.2

El SaaS sigue el modelo de archivos SCSS que se compilan a CSS, con variables inyectables cuyos valores se configuran a traves de la interfaz de Drupal sin codigo. La configuracion del tema ya existente en `ecosistema_jaraba_theme` (70+ opciones en `/admin/appearance/settings/ecosistema_jaraba_theme`) proporciona los valores para los parciales reutilizables (_header, _footer), de modo que **no hay que tocar codigo para cambiar contenido del header o footer**. Los parciales del Stack Fiscal heredan esta configuracion automaticamente al usar `{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}`.

**Variables del tema ya configurables que usan los parciales fiscales:**

| Variable tema | Donde se configura | Parcial que la consume |
|--------------|-------------------|----------------------|
| `site_name` | Configuracion del sitio | `_header.html.twig`, `_footer.html.twig` |
| `logo` | Apariencia > Logo | `_header.html.twig` |
| `header_style` | Apariencia > Header > Estilo | `_header.html.twig` |
| `footer_text` | Apariencia > Footer > Texto | `_footer.html.twig` |
| `enable_avatar_nav` | Apariencia > Header > Navegacion contextual | `_avatar-nav.html.twig` |
| `primary_color`, `secondary_color` | Apariencia > Identidad de Marca | Inyectadas como CSS custom properties |

**Los modulos fiscales NO anaden nuevos settings al tema.** Toda la configuracion especifica fiscal se gestiona en las entidades `*_tenant_config` y en los formularios de configuracion del modulo (`/admin/config/jaraba/verifactu`, `/admin/config/jaraba/facturae`, `/admin/config/jaraba/einvoice`), accesibles desde la UI de Drupal.

---

## 4. Arquitectura de los Modulos

### 4.1 Modulo jaraba_verifactu

```
web/modules/custom/jaraba_verifactu/
  config/
    install/
      jaraba_verifactu.settings.yml
    schema/
      jaraba_verifactu.schema.yml
  css/
    verifactu.css
  js/
    verifactu-dashboard.js
    verifactu-chain-viewer.js
  scss/
    _variables-fiscal.scss
    _verifactu-dashboard.scss
    _verifactu-records.scss
    _verifactu-chain.scss
    _verifactu-remision.scss
    _verifactu-badges.scss
    _verifactu-responsive.scss
    _verifactu-qr.scss
    main.scss
  src/
    Entity/
      VeriFactuInvoiceRecord.php
      VeriFactuEventLog.php
      VeriFactuRemisionBatch.php
      VeriFactuTenantConfig.php
    Form/
      VeriFactuInvoiceRecordForm.php
      VeriFactuTenantConfigForm.php
      VeriFactuSettingsForm.php
    Controller/
      VeriFactuDashboardController.php
      VeriFactuApiController.php
      VeriFactuAuditController.php
    Service/
      VeriFactuHashService.php
      VeriFactuRecordService.php
      VeriFactuQrService.php
      VeriFactuXmlService.php
      VeriFactuRemisionService.php
      VeriFactuPdfService.php
    Access/
      VeriFactuInvoiceRecordAccessControlHandler.php
      VeriFactuEventLogAccessControlHandler.php
    ListBuilder/
      VeriFactuInvoiceRecordListBuilder.php
      VeriFactuEventLogListBuilder.php
      VeriFactuRemisionBatchListBuilder.php
    Plugin/
      QueueWorker/
        VeriFactuRemisionQueueWorker.php
    Exception/
      VeriFactuChainBreakException.php
      VeriFactuAeatCommunicationException.php
  templates/
    page--verifactu.html.twig
    page--verifactu-records.html.twig
    verifactu-record-detail.html.twig
    verifactu-dashboard.html.twig
    verifactu-audit.html.twig
    verifactu-pdf-label.html.twig
    partials/
      _verifactu-stats.html.twig
      _verifactu-record-card.html.twig
      _verifactu-chain-status.html.twig
      _verifactu-remision-card.html.twig
      _verifactu-qr-preview.html.twig
      _certificate-status.html.twig
      _fiscal-status-badge.html.twig
      _fiscal-tenant-selector.html.twig
  tests/
    src/
      Unit/
        VeriFactuHashServiceTest.php
        VeriFactuQrServiceTest.php
        VeriFactuXmlServiceTest.php
        VeriFactuRecordServiceTest.php
        VeriFactuRemisionServiceTest.php
        VeriFactuPdfServiceTest.php
        VeriFactuEventLogTest.php
      Kernel/
        VeriFactuInvoiceRecordEntityTest.php
        VeriFactuEventLogEntityTest.php
        VeriFactuRemisionBatchEntityTest.php
        VeriFactuTenantConfigEntityTest.php
        VeriFactuHashChainIntegrityTest.php
        VeriFactuRecordCreationFlowTest.php
        VeriFactuXmlSchemaValidationTest.php
        VeriFactuPermissionsTest.php
        VeriFactuCronTest.php
      Functional/
        VeriFactuDashboardTest.php
        VeriFactuApiEndpointsTest.php
        VeriFactuSlidePanel Test.php
        VeriFactuAeatConnectionTest.php
        VeriFactuConcurrentRecordTest.php
        VeriFactuChainRecoveryTest.php
        VeriFactuPdfGenerationTest.php
  xsd/
    SuministroFactEmitidas.xsd
    RespuestaFactEmitidas.xsd
  jaraba_verifactu.info.yml
  jaraba_verifactu.install
  jaraba_verifactu.libraries.yml
  jaraba_verifactu.links.menu.yml
  jaraba_verifactu.links.task.yml
  jaraba_verifactu.links.action.yml
  jaraba_verifactu.module
  jaraba_verifactu.permissions.yml
  jaraba_verifactu.routing.yml
  jaraba_verifactu.services.yml
  package.json
```

**Dependencias (info.yml):**
```yaml
name: Jaraba VeriFactu
type: module
description: 'Cumplimiento VeriFactu (RD 1007/2023): registro de facturas con hash chain SHA-256, remision AEAT SOAP, QR verificacion.'
core_version_requirement: ^11
package: Jaraba Fiscal
dependencies:
  - ecosistema_jaraba_core:ecosistema_jaraba_core
  - jaraba_billing:jaraba_billing
```

### 4.2 Modulo jaraba_facturae

```
web/modules/custom/jaraba_facturae/
  config/
    install/
      jaraba_facturae.settings.yml
    schema/
      jaraba_facturae.schema.yml
  css/
    facturae.css
  js/
    facturae-dashboard.js
    facturae-dir3-autocomplete.js
  scss/
    _variables-fiscal.scss
    _facturae-dashboard.scss
    _facturae-documents.scss
    _facturae-signature.scss
    _facturae-face.scss
    _facturae-responsive.scss
    main.scss
  src/
    Entity/
      FacturaeDocument.php
      FacturaeTenantConfig.php
      FacturaeFaceLog.php
    Form/
      FacturaeDocumentForm.php
      FacturaeTenantConfigForm.php
      FacturaeSettingsForm.php
    Controller/
      FacturaeDashboardController.php
      FacturaeApiController.php
      FacturaeDir3Controller.php
    Service/
      FacturaeXmlService.php
      FacturaeXAdESService.php
      FACeClientService.php
      FacturaeNumberingService.php
      FacturaeValidationService.php
      FacturaeDIR3Service.php
    Access/
      FacturaeDocumentAccessControlHandler.php
      FacturaeFaceLogAccessControlHandler.php
    ListBuilder/
      FacturaeDocumentListBuilder.php
      FacturaeFaceLogListBuilder.php
    Plugin/
      QueueWorker/
        FACeSyncQueueWorker.php
  templates/
    page--facturae.html.twig
    page--facturae-documents.html.twig
    facturae-document-detail.html.twig
    facturae-dashboard.html.twig
    facturae-pdf-template.html.twig
    partials/
      _facturae-stats.html.twig
      _facturae-document-card.html.twig
      _facturae-face-status.html.twig
      _facturae-dir3-search.html.twig
      _facturae-signature-info.html.twig
      _facturae-xml-preview.html.twig
  tests/
    src/
      Unit/ (8 tests)
      Kernel/ (10 tests)
      Functional/ (8 tests)
  xsd/
    Facturaev3_2_2.xsd
    Facturaev3_2_2-Signature.xsd
  policy/
    Facturaev3_2_2-policy.pdf
  jaraba_facturae.info.yml
  jaraba_facturae.install
  jaraba_facturae.libraries.yml
  jaraba_facturae.links.menu.yml
  jaraba_facturae.links.task.yml
  jaraba_facturae.links.action.yml
  jaraba_facturae.module
  jaraba_facturae.permissions.yml
  jaraba_facturae.routing.yml
  jaraba_facturae.services.yml
  package.json
```

### 4.3 Modulo jaraba_einvoice_b2b

```
web/modules/custom/jaraba_einvoice_b2b/
  config/
    install/
      jaraba_einvoice_b2b.settings.yml
    schema/
      jaraba_einvoice_b2b.schema.yml
  css/
    einvoice.css
  js/
    einvoice-dashboard.js
    einvoice-payment-tracker.js
  scss/
    _variables-fiscal.scss
    _einvoice-dashboard.scss
    _einvoice-documents.scss
    _einvoice-payments.scss
    _einvoice-delivery.scss
    _einvoice-responsive.scss
    main.scss
  src/
    Entity/
      EInvoiceDocument.php
      EInvoiceTenantConfig.php
      EInvoiceDeliveryLog.php
      EInvoicePaymentEvent.php
    Form/
      EInvoiceDocumentForm.php
      EInvoiceTenantConfigForm.php
      EInvoiceSettingsForm.php
    Controller/
      EInvoiceDashboardController.php
      EInvoiceApiController.php
      EInvoicePaymentController.php
    Service/
      EInvoiceUblService.php
      EInvoiceFormatConverterService.php
      EInvoiceDeliveryService.php
      EInvoicePaymentStatusService.php
      EInvoiceValidationService.php
      SPFEClientService.php
    SPFEClient/
      SPFEClientInterface.php
      SPFEClientStub.php
      SPFEClientLive.php
    Model/
      EN16931Model.php
    Access/
      EInvoiceDocumentAccessControlHandler.php
      EInvoiceDeliveryLogAccessControlHandler.php
    ListBuilder/
      EInvoiceDocumentListBuilder.php
      EInvoicePaymentEventListBuilder.php
  templates/
    page--einvoice.html.twig
    einvoice-document-detail.html.twig
    einvoice-dashboard.html.twig
    einvoice-payment-detail.html.twig
    einvoice-morosidad-report.html.twig
    partials/
      _einvoice-stats.html.twig
      _einvoice-document-card.html.twig
      _einvoice-delivery-status.html.twig
      _einvoice-payment-timeline.html.twig
      _einvoice-format-badge.html.twig
      _einvoice-morosidad-alert.html.twig
  tests/
    src/
      Unit/ (7 tests)
      Kernel/ (9 tests)
      Functional/ (7 tests)
  xsd/
    UBL-Invoice-2.1.xsd
    UBL-CommonAggregateComponents-2.1.xsd
  schematron/
    EN16931-UBL-validation.sch
    ES-CIUS-rules.sch
  jaraba_einvoice_b2b.info.yml
  jaraba_einvoice_b2b.install
  jaraba_einvoice_b2b.libraries.yml
  jaraba_einvoice_b2b.links.menu.yml
  jaraba_einvoice_b2b.links.task.yml
  jaraba_einvoice_b2b.links.action.yml
  jaraba_einvoice_b2b.module
  jaraba_einvoice_b2b.permissions.yml
  jaraba_einvoice_b2b.routing.yml
  jaraba_einvoice_b2b.services.yml
  package.json
```

### 4.4 Servicio compartido CertificateManagerService

Los tres modulos comparten la gestion de certificados digitales PKCS#12. Para evitar duplicacion, el `CertificateManagerService` se implementa en `ecosistema_jaraba_core` como servicio transversal.

```php
/**
 * Gestiona certificados digitales PKCS#12 para firma electronica y autenticacion.
 *
 * Servicio centralizado que comparten jaraba_verifactu, jaraba_facturae y
 * jaraba_einvoice_b2b. Responsable de:
 * - Almacenamiento seguro en private:// (nunca public://)
 * - Lectura y parseo del PKCS#12
 * - Validacion de vigencia y alerta de expiracion
 * - Extraccion de clave privada y certificado X.509 para firma
 *
 * @package Drupal\ecosistema_jaraba_core\Service
 */
class CertificateManagerService {
  public function loadCertificate(int $tenantId): array;
  public function validateCertificate(int $tenantId): CertificateValidationResult;
  public function getExpiringCertificates(int $daysThreshold = 30): array;
  public function getPrivateKey(int $tenantId, string $password): \OpenSSLAsymmetricKey;
  public function getX509Certificate(int $tenantId): string;
}
```

### 4.5 Compilacion SCSS

Cada modulo tiene su propio `package.json` con scripts de build/watch usando Dart Sass:

```json
{
  "name": "jaraba_verifactu",
  "version": "1.0.0",
  "private": true,
  "scripts": {
    "build": "sass scss/main.scss:css/verifactu.css --style=compressed --no-source-map",
    "watch": "sass scss/main.scss:css/verifactu.css --watch"
  },
  "devDependencies": {
    "sass": "^1.80.0"
  }
}
```

**Estructura de `main.scss` (ejemplo jaraba_verifactu):**
```scss
// main.scss - Archivo principal de compilacion para jaraba_verifactu.
// DIRECTRIZ: Solo @use, nunca @import. Cada parcial declara sus propias dependencias.
@use 'variables-fiscal';
@use 'verifactu-dashboard';
@use 'verifactu-records';
@use 'verifactu-chain';
@use 'verifactu-remision';
@use 'verifactu-badges';
@use 'verifactu-qr';
@use 'verifactu-responsive';
```

---

## 5. Estado por Fases

| Fase | Nombre | Modulo(s) | Horas Est. | Prioridad | Estado |
|------|--------|-----------|------------|-----------|--------|
| **F0** | Infraestructura compartida y CertificateManagerService | ecosistema_jaraba_core | 30-40h | P0 | Pendiente |
| **F1** | jaraba_verifactu: Entidades y Modelo de Datos | jaraba_verifactu | 40-55h | P0 | Pendiente |
| **F2** | jaraba_verifactu: Servicios Core (Hash, Record, QR) | jaraba_verifactu | 50-65h | P0 | Pendiente |
| **F3** | jaraba_verifactu: AEAT SOAP y Remision | jaraba_verifactu | 50-70h | P0 | Pendiente |
| **F4** | jaraba_verifactu: Frontend, Dashboard y PDF | jaraba_verifactu | 40-55h | P0 | Pendiente |
| **F5** | jaraba_verifactu: ECA Hooks, Cron y Tests | jaraba_verifactu | 50-70h | P0 | Pendiente |
| **F6** | jaraba_facturae: Entidades y XML Builder | jaraba_facturae | 60-80h | P1 | Pendiente |
| **F7** | jaraba_facturae: XAdES, FACe y Servicios | jaraba_facturae | 80-100h | P1 | Pendiente |
| **F8** | jaraba_facturae: Frontend, ECA y Tests | jaraba_facturae | 90-124h | P1 | Pendiente |
| **F9** | jaraba_einvoice_b2b: Entidades y UBL 2.1 | jaraba_einvoice_b2b | 80-100h | P2 | Pendiente |
| **F10** | jaraba_einvoice_b2b: Servicios, SPFE y Tests | jaraba_einvoice_b2b | 80-116h | P2 | Pendiente |
| **F11** | Integracion cross-modulo y Dashboard Fiscal Unificado | Todos | 70-80h | P1 | Pendiente |
| **TOTAL** | | | **720-955h** | | |

---

## 6. FASE 0: Infraestructura compartida y CertificateManagerService

**Duracion estimada:** 30-40 horas
**Prioridad:** P0 CRITICA (prerequisito para F1-F10)
**Modulo afectado:** `ecosistema_jaraba_core`

### 6.1 Objetivo

Crear la infraestructura compartida que los tres modulos fiscales necesitan: el servicio de gestion de certificados digitales (`CertificateManagerService`), los iconos SVG de la categoria `fiscal/`, los parciales Twig compartidos, y las variables SCSS fiscales.

### 6.2 Entregables

| # | Entregable | Archivo(s) | Descripcion |
|---|-----------|-----------|-------------|
| F0-1 | CertificateManagerService | `ecosistema_jaraba_core/src/Service/CertificateManagerService.php` | Servicio transversal para gestion de certificados PKCS#12: carga, validacion, extraccion de clave privada y certificado X.509, alerta de expiracion. Almacenamiento en `private://certificates/`. |
| F0-2 | CertificateValidationResult | `ecosistema_jaraba_core/src/ValueObject/CertificateValidationResult.php` | Value Object con `isValid`, `expiresAt`, `daysUntilExpiry`, `subject`, `issuer`, `serialNumber`. Patron inmutable. |
| F0-3 | Registro servicio | `ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.certificate_manager` con inyeccion de `file_system`, `entity_type.manager`, `logger.factory`. |
| F0-4 | Iconos SVG fiscales | `ecosistema_jaraba_core/images/icons/fiscal/` | 10 iconos (outline + duotone = 20 archivos SVG). Ver seccion 3.12. |
| F0-5 | Variables SCSS fiscales | Archivo `_variables-fiscal.scss` compartido | Variables locales `$fiscal-*` como fallback. Se copia a cada modulo fiscal. |
| F0-6 | Parciales compartidos | `jaraba_verifactu/templates/partials/` | `_certificate-status.html.twig`, `_fiscal-status-badge.html.twig`, `_fiscal-tenant-selector.html.twig` — usados por los 3 modulos. Se colocan en el primer modulo (VeriFactu) y se referencian con namespace Twig desde los otros dos. |
| F0-7 | Template page--fiscal | `ecosistema_jaraba_theme/templates/page--fiscal.html.twig` | Pagina frontend limpia para el dashboard fiscal unificado (`/admin/jaraba/fiscal`). Layout zero-region con parciales. |
| F0-8 | Tests unitarios | `tests/src/Unit/CertificateManagerServiceTest.php` | 8 tests: certificado valido, expirado, corrupto, password incorrecto, sin archivo, proximo a expirar, extraccion clave, extraccion X.509. |

### 6.3 Reglas aplicables

- **DRUPAL11-001**: No redeclarar `$entityTypeManager` en subclases de ControllerBase.
- **AUDIT-PERF-002**: Usar `LockBackendInterface` para operaciones de escritura concurrente de certificados.
- **TENANT-001**: El certificado se almacena en `private://certificates/{tenant_id}/` con aislamiento por tenant.

---

## 7. FASE 1: jaraba_verifactu — Entidades y Modelo de Datos

**Duracion estimada:** 40-55 horas
**Prioridad:** P0 CRITICA
**Modulo afectado:** `jaraba_verifactu`
**Spec de referencia:** Doc 179, Seccion 2

### 7.1 Objetivo

Crear las 4 Content Entities del modulo VeriFactu con sus handlers, formularios, listbuilders, access control handlers, navegacion admin y Field UI completo.

### 7.2 Entregables

| # | Entregable | Descripcion |
|---|-----------|-------------|
| F1-1 | `VeriFactuInvoiceRecord` entity | Content Entity append-only con 33+ campos: NIF emisor, numero factura, fecha expedicion, tipo factura (F1-F3, R1-R5), cuota tributaria, importe total, hash SHA-256, hash anterior, clave regimen IVA (01-15), estado AEAT (pending/accepted/rejected), URL QR verificacion, XML del registro, referencia a BillingInvoice, tenant_id (entity_reference). Indices DB en `tenant_id`, `hash`, `aeat_status`, `nif_emisor`, `created`. |
| F1-2 | `VeriFactuEventLog` entity | Content Entity inmutable con 12 event types segun SIF: SYSTEM_START, RECORD_CREATE, RECORD_CANCEL, CHAIN_BREAK, CHAIN_RECOVERY, AEAT_SUBMIT, AEAT_RESPONSE, CERTIFICATE_CHANGE, CONFIG_CHANGE, AUDIT_ACCESS, INTEGRITY_CHECK, MANUAL_INTERVENTION. Incluye hash encadenado propio. |
| F1-3 | `VeriFactuRemisionBatch` entity | Content Entity para lotes de envio a AEAT: hasta 1.000 registros por batch, estado (queued/sending/sent/partial_error/error), timestamp de envio, respuesta AEAT XML, numero de registros aceptados/rechazados. |
| F1-4 | `VeriFactuTenantConfig` entity | Content Entity con configuracion por tenant: NIF, nombre fiscal, certificado PKCS#12 (referencia a archivo en private://), contrasena cifrada, serie de facturacion, entorno AEAT (produccion/pruebas), ultimo hash de la cadena, software_id y version para la declaracion responsable. |
| F1-5 | AccessControlHandlers | Handlers para las 4 entidades. `VeriFactuInvoiceRecord` y `VeriFactuEventLog` deniegan `update`/`delete` para todos los roles (append-only). |
| F1-6 | ListBuilders | ListBuilders con columnas relevantes, filtros por estado AEAT, y paginacion. |
| F1-7 | Formularios | Forms para `VeriFactuTenantConfig` (subida certificado, config NIF), `VeriFactuSettingsForm` (config global del modulo). Los registros se crean programaticamente, no via formulario de usuario. |
| F1-8 | Navegacion admin | 4 archivos YAML (`.links.menu.yml`, `.links.task.yml`, `.links.action.yml`, `.links.contextual.yml`) con tabs en `/admin/content` y enlaces en `/admin/structure` para las 4 entidades. |
| F1-9 | Install hook | `jaraba_verifactu.install` con `hook_install()` que crea las tablas de entidades y `hook_schema()` si se necesitan tablas custom adicionales. Indices DB obligatorios en las columnas de alta cardinalidad. |
| F1-10 | Config install | `config/install/jaraba_verifactu.settings.yml` con defaults: `aeat_endpoint`, `flow_control_seconds: 60`, `max_records_per_batch: 1000`, `max_retries: 5`. |
| F1-11 | Schema config | `config/schema/jaraba_verifactu.schema.yml` con tipos y descripciones de cada config key. |

### 7.3 Especificacion detallada: Campos de VeriFactuInvoiceRecord

Los campos siguen estrictamente el Anexo II del RD 1007/2023 y la Orden HAC/1177/2024:

| Campo | Tipo | Obligatorio | Descripcion (Anexo II) |
|-------|------|-------------|------------------------|
| `record_type` | string (enum) | Si | Tipo de registro: `alta`, `anulacion` |
| `nif_emisor` | string(9) | Si | NIF/NIE del emisor de la factura |
| `nombre_emisor` | string(120) | Si | Razon social del emisor |
| `numero_factura` | string(60) | Si | Numero de factura segun serie |
| `fecha_expedicion` | date | Si | Fecha de expedicion de la factura |
| `tipo_factura` | string (enum) | Si | F1 (completa), F2 (simplificada), F3 (reemplazo simplificada), R1-R5 (rectificativas) |
| `clave_regimen` | string (enum) | Si | Clave de regimen IVA (01-15) |
| `base_imponible` | decimal(12,2) | Si | Base imponible |
| `tipo_impositivo` | decimal(5,2) | Si | Tipo impositivo IVA (%) |
| `cuota_tributaria` | decimal(12,2) | Si | Cuota IVA resultante |
| `importe_total` | decimal(12,2) | Si | Importe total de la factura |
| `hash_record` | string(64) | Si | Hash SHA-256 del registro (calculado) |
| `hash_previous` | string(64) | No | Hash del registro anterior (NULL si primero) |
| `qr_url` | string(500) | Si | URL de verificacion AEAT para QR |
| `qr_image` | string (base64) | Si | Imagen QR generada |
| `aeat_status` | string (enum) | Si | pending, accepted, rejected, error |
| `aeat_response_code` | string(10) | No | Codigo de respuesta AEAT |
| `aeat_response_message` | text | No | Mensaje de respuesta AEAT |
| `xml_registro` | text (long) | Si | XML completo del registro SOAP |
| `remision_batch_id` | entity_reference | No | Referencia al batch de envio |
| `billing_invoice_id` | entity_reference | No | Referencia a BillingInvoice origen |
| `software_id` | string(30) | Si | ID del software de facturacion (Jaraba) |
| `software_version` | string(20) | Si | Version del software |
| `tenant_id` | entity_reference | Si | Referencia al tenant (Group) |
| `created` | datetime | Si | Timestamp de creacion (UTC) |

---

## 8. FASE 2: jaraba_verifactu — Servicios Core (Hash, Record, QR)

**Duracion estimada:** 50-65 horas
**Prioridad:** P0 CRITICA
**Spec de referencia:** Doc 179, Seccion 3

### 8.1 Objetivo

Implementar los servicios core que calculan el hash SHA-256 encadenado, crean registros de alta y anulacion, y generan codigos QR de verificacion AEAT.

### 8.2 Entregables

| # | Entregable | Descripcion |
|---|-----------|-------------|
| F2-1 | `VeriFactuHashService` | Calcula hash SHA-256 conforme Anexo II. Metodos: `calculateAltaHash(array $fields, ?string $previousHash): string`, `calculateAnulacionHash(array $fields, ?string $previousHash): string`, `verifyChainIntegrity(int $tenantId): ChainIntegrityResult`. Usa `LockBackendInterface` para evitar condiciones de carrera en la cadena. |
| F2-2 | `VeriFactuRecordService` | Orquesta la creacion de registros. Metodos: `createAltaRecord(BillingInvoice $invoice): VeriFactuInvoiceRecord`, `createAnulacionRecord(VeriFactuInvoiceRecord $original): VeriFactuInvoiceRecord`, `createRectificativaRecord(BillingInvoice $invoice, VeriFactuInvoiceRecord $original): VeriFactuInvoiceRecord`. Invoca Hash→QR→XML→Queue en secuencia. |
| F2-3 | `VeriFactuQrService` | Genera QR de verificacion AEAT. Metodos: `buildVerificationUrl(VeriFactuInvoiceRecord $record): string`, `generateQrImage(string $url, int $size = 300): string`. Usa `chillerlan/php-qrcode`. URL: `https://www2.agenciatributaria.gob.es/wlpl/TIKE-CONT/ValidarQR?nif={NIF}&numserie={NUM}&fecha={FECHA}&importe={TOTAL}`. |
| F2-4 | `VeriFactuEventLogService` | Registra eventos SIF. Metodo: `logEvent(string $eventType, int $tenantId, ?int $recordId, array $details): VeriFactuEventLog`. Calcula hash encadenado propio para el log de eventos (cadena independiente de la de registros). |
| F2-5 | Registro servicios | `jaraba_verifactu.services.yml` con DI de todos los servicios, usando `@lock`, `@entity_type.manager`, `@logger.factory`, `@ecosistema_jaraba_core.certificate_manager`. |
| F2-6 | Tests unitarios | 4 test files: `VeriFactuHashServiceTest` (hash correcto, cadena, primer registro, campos faltantes), `VeriFactuQrServiceTest` (URL correcta, QR generado), `VeriFactuRecordServiceTest` (alta, anulacion, rectificativa), `VeriFactuEventLogTest` (12 event types). |

### 8.3 Algoritmo de hash SHA-256 (Anexo II RD 1007/2023)

```
Hash = SHA-256(
  NIF_emisor + "," +
  numero_factura + "," +
  fecha_expedicion + "," +
  tipo_factura + "," +
  cuota_tributaria + "," +
  importe_total + "," +
  tipo_registro + "," +
  hash_anterior
)
```

Donde `hash_anterior` es el hash del ultimo registro del mismo emisor+serie. Si es el primer registro, se usa cadena vacia `""`. El resultado es un string hexadecimal de 64 caracteres.

---

## 9. FASE 3: jaraba_verifactu — AEAT SOAP y Remision

**Duracion estimada:** 50-70 horas
**Prioridad:** P0 CRITICA
**Spec de referencia:** Doc 179, Secciones 3, 4, 6

### 9.1 Objetivo

Implementar la comunicacion SOAP con la AEAT para envio de registros VeriFactu, incluyendo control de flujo de 60 segundos, reintentos, parseo de respuestas y la API REST del modulo.

### 9.2 Entregables

| # | Entregable | Descripcion |
|---|-----------|-------------|
| F3-1 | `VeriFactuXmlService` | Construye los mensajes SOAP conformes a los XSD de la AEAT. Metodos: `buildSoapEnvelope(array $records): string`, `validateAgainstXsd(string $xml): ValidationResult`, `parseAeatResponse(string $responseXml): AeatResponse`. Incluye todos los namespaces requeridos (`soapenv`, `siiR`, `sii`). Tipos de factura (F1-F3, R1-R5) y claves de regimen IVA (01-15). |
| F3-2 | `VeriFactuRemisionService` | Cliente SOAP con autenticacion PKCS#12. Metodos: `submitBatch(VeriFactuRemisionBatch $batch): RemisionResult`, `processQueue(): int`. Control de flujo de 60 segundos entre envios. Maximo 1.000 registros por batch. Reintentos con backoff exponencial (max 5). Circuit breaker: 5 fallos consecutivos → pausa 5 minutos. |
| F3-3 | `VeriFactuRemisionQueueWorker` | Plugin QueueWorker (`@QueueWorker`) con `ContainerFactoryPluginInterface`. Procesa batches de remision en background. Intervalo configurable en settings. |
| F3-4 | Exceptions | `VeriFactuChainBreakException` (cadena de hash rota), `VeriFactuAeatCommunicationException` (error SOAP/HTTP con AEAT). |
| F3-5 | API REST (21 endpoints) | Implementados en `VeriFactuApiController` y `VeriFactuAuditController`. Ver tabla de endpoints a continuacion. |
| F3-6 | XSD schemas | Archivos `SuministroFactEmitidas.xsd` y `RespuestaFactEmitidas.xsd` en directorio `xsd/`. |
| F3-7 | Tests | `VeriFactuXmlServiceTest` (SOAP envelope, XSD validation), `VeriFactuRemisionServiceTest` (submit, retry, circuit breaker). |

### 9.3 Endpoints REST API

| Grupo | Metodo | Ruta | Permiso | Descripcion |
|-------|--------|------|---------|-------------|
| **Admin** | GET | `/api/v1/verifactu/config` | `administer verifactu` | Obtener configuracion del tenant |
| Admin | PUT | `/api/v1/verifactu/config` | `administer verifactu` | Actualizar configuracion del tenant |
| Admin | POST | `/api/v1/verifactu/config/certificate` | `administer verifactu` | Subir certificado PKCS#12 |
| Admin | GET | `/api/v1/verifactu/config/certificate/status` | `administer verifactu` | Estado del certificado |
| Admin | POST | `/api/v1/verifactu/config/test-connection` | `administer verifactu` | Test de conexion AEAT |
| **Records** | GET | `/api/v1/verifactu/records` | `view verifactu records` | Listar registros (paginado, filtros) |
| Records | GET | `/api/v1/verifactu/records/{id}` | `view verifactu records` | Detalle de registro |
| Records | POST | `/api/v1/verifactu/records` | `create verifactu records` | Crear registro manual de alta |
| Records | POST | `/api/v1/verifactu/records/{id}/cancel` | `create verifactu records` | Crear registro de anulacion |
| Records | GET | `/api/v1/verifactu/records/{id}/qr` | `view verifactu records` | Obtener QR de verificacion |
| Records | GET | `/api/v1/verifactu/records/{id}/xml` | `view verifactu records` | Obtener XML del registro |
| **Remision** | GET | `/api/v1/verifactu/remisions` | `view verifactu records` | Listar batches de remision |
| Remision | GET | `/api/v1/verifactu/remisions/{id}` | `view verifactu records` | Detalle de batch |
| Remision | POST | `/api/v1/verifactu/remisions` | `submit verifactu remision` | Crear batch manual |
| Remision | POST | `/api/v1/verifactu/remisions/{id}/retry` | `submit verifactu remision` | Reintentar batch fallido |
| Remision | GET | `/api/v1/verifactu/remisions/queue-status` | `administer verifactu` | Estado de la cola |
| **Auditoria** | GET | `/api/v1/verifactu/audit/chain` | `audit verifactu` | Estado de integridad de cadena |
| Auditoria | POST | `/api/v1/verifactu/audit/chain/verify` | `audit verifactu` | Verificar integridad completa |
| Auditoria | GET | `/api/v1/verifactu/audit/events` | `audit verifactu` | Log de eventos SIF |
| Auditoria | GET | `/api/v1/verifactu/audit/stats` | `view verifactu records` | Estadisticas de cumplimiento |
| Auditoria | GET | `/api/v1/verifactu/audit/declaration` | `administer verifactu` | Declaracion responsable SIF |

---

## 10. FASE 4: jaraba_verifactu — Frontend, Dashboard y PDF

**Duracion estimada:** 40-55 horas
**Prioridad:** P0 CRITICA
**Spec de referencia:** Doc 179, Secciones 4, 8

### 10.1 Objetivo

Crear los templates Twig, SCSS, JS y parciales para el dashboard VeriFactu del tenant, el listado de registros, el visor de cadena de hash, y la inyeccion de QR+etiqueta en PDFs.

### 10.2 Entregables

| # | Entregable | Descripcion |
|---|-----------|-------------|
| F4-1 | `page--verifactu.html.twig` | Pagina frontend limpia (zero-region) para el dashboard VeriFactu. Incluye parciales de stats, ultimos registros, estado de cadena, alertas de certificado. Layout mobile-first full-width. |
| F4-2 | `page--verifactu-records.html.twig` | Pagina frontend limpia para listado de registros con filtros por estado AEAT, fecha, tipo. DataTable server-side con paginacion. |
| F4-3 | `VeriFactuDashboardController` | Controller con `preprocess` que inyecta variables Twig para stats, registros recientes, estado de cadena, estado de certificado. Detecta AJAX para slide-panel. |
| F4-4 | `VeriFactuPdfService` | Servicio que modifica PDFs de facturas para anadir: QR de verificacion (esquina inferior derecha), etiqueta `VERI*FACTU` (segun Orden HAC/1177/2024), hash abreviado del registro. Usa TCPDF o dompdf. |
| F4-5 | SCSS (8 archivos) | `_verifactu-dashboard.scss`, `_verifactu-records.scss`, `_verifactu-chain.scss`, `_verifactu-remision.scss`, `_verifactu-badges.scss`, `_verifactu-qr.scss`, `_verifactu-responsive.scss`, `main.scss`. Todos con `@use`, `var(--ej-*)`, `color-mix()`, mobile-first. |
| F4-6 | JS (2 archivos) | `verifactu-dashboard.js` (Drupal.behaviors, once(), fetch API para stats en tiempo real, auto-refresh 30s), `verifactu-chain-viewer.js` (visualizacion de cadena de hash con Canvas 2D). |
| F4-7 | Libraries | `jaraba_verifactu.libraries.yml` con definicion de libraries: `verifactu.dashboard`, `verifactu.records`, `verifactu.chain`. Dependencias: `ecosistema_jaraba_theme/slide-panel`, `core/drupal`, `core/once`. |
| F4-8 | Template suggestions | En `jaraba_verifactu.module`: `hook_theme_suggestions_page_alter()` para asignar templates limpios a las rutas del modulo. |
| F4-9 | Body classes | En `jaraba_verifactu.module`: `hook_preprocess_html()` con mapa de rutas a clases CSS (ver seccion 3.5). |
| F4-10 | Parciales (8) | Los 8 parciales definidos en seccion 3.9 (stats, record-card, chain-status, remision-card, qr-preview, certificate-status, fiscal-status-badge, fiscal-tenant-selector). |

### 10.3 Reglas de frontend

- **Layout mobile-first**: Breakpoints en 480px (mobile), 768px (tablet), 1024px (desktop), 1440px (wide). Cards apiladas en mobile, grid 2-3 columnas en desktop.
- **BEM naming**: `.verifactu-dashboard__stats`, `.verifactu-record-card__status`, `.verifactu-chain__node`.
- **Accesibilidad**: `role="main"`, `aria-label` en botones, `focus-visible` para navegacion con teclado, `prefers-reduced-motion` para desactivar animaciones.
- **Color-mix para badges**: `.verifactu-badge--accepted { background: color-mix(in srgb, var(--ej-color-success) 15%, transparent); }`.

---

## 11. FASE 5: jaraba_verifactu — ECA Hooks, Cron y Tests

**Duracion estimada:** 50-70 horas
**Prioridad:** P0 CRITICA
**Spec de referencia:** Doc 179, Secciones 5, 9

### 11.1 Objetivo

Implementar las automatizaciones via hooks Drupal (generacion automatica de registros, cron de remision, verificacion de integridad diaria, alertas de certificados) y la suite completa de 23 tests PHPUnit.

### 11.2 Entregables

| # | Entregable | Descripcion |
|---|-----------|-------------|
| F5-1 | ECA-VF-001: Auto-generacion de registro | `hook_entity_insert/update` en `billing_invoice`: cuando el estado cambia a `paid`, generar automaticamente un `verifactu_invoice_record` de tipo alta. Usa `VeriFactuRecordService::createAltaRecord()`. |
| F5-2 | ECA-VF-002: Auto-anulacion | `hook_entity_update` en `billing_invoice`: cuando se anula una factura pagada, generar registro de anulacion. Usa `VeriFactuRecordService::createAnulacionRecord()`. |
| F5-3 | ECA-VF-003: Cron remision AEAT | `hook_cron`: cada ejecucion verifica si hay registros pendientes de envio (>60s desde ultimo envio). Agrupa en batches de max 1.000 registros. Usa `VeriFactuRemisionService::processQueue()`. |
| F5-4 | ECA-VF-004: Verificacion integridad | `hook_cron`: una vez al dia (03:00 UTC, controlado via `\Drupal::state()`), ejecuta `VeriFactuHashService::verifyChainIntegrity()` para cada tenant. Si detecta rotura, registra evento `CHAIN_BREAK` y envia alerta por email. |
| F5-5 | ECA-VF-005: Alerta certificados | `hook_cron`: semanalmente verifica `CertificateManagerService::getExpiringCertificates(30)`. Envia email al tenant admin si quedan <30 dias para expiracion. |
| F5-6 | Tests Unit (7) | `VeriFactuHashServiceTest`, `VeriFactuQrServiceTest`, `VeriFactuXmlServiceTest`, `VeriFactuRecordServiceTest`, `VeriFactuRemisionServiceTest`, `VeriFactuPdfServiceTest`, `VeriFactuEventLogTest`. |
| F5-7 | Tests Kernel (9) | `VeriFactuInvoiceRecordEntityTest`, `VeriFactuEventLogEntityTest`, `VeriFactuRemisionBatchEntityTest`, `VeriFactuTenantConfigEntityTest`, `VeriFactuHashChainIntegrityTest`, `VeriFactuRecordCreationFlowTest`, `VeriFactuXmlSchemaValidationTest`, `VeriFactuPermissionsTest`, `VeriFactuCronTest`. |
| F5-8 | Tests Functional (7) | `VeriFactuDashboardTest`, `VeriFactuApiEndpointsTest`, `VeriFactuSlidePanelTest`, `VeriFactuAeatConnectionTest`, `VeriFactuConcurrentRecordTest`, `VeriFactuChainRecoveryTest`, `VeriFactuPdfGenerationTest`. |
| F5-9 | Permisos RBAC (7) | `administer verifactu`, `view verifactu records`, `create verifactu records`, `submit verifactu remision`, `audit verifactu`, `view verifactu dashboard`, `manage verifactu config`. Matrizados contra 5 roles (Platform Admin, Tenant Admin, Accountant, Manager, User). |

---

## 12. FASE 6: jaraba_facturae — Entidades y XML Builder

**Duracion estimada:** 60-80 horas
**Prioridad:** P1 ALTA
**Spec de referencia:** Doc 180, Secciones 1-3

### 12.1 Objetivo

Crear las 3 Content Entities del modulo Facturae y el servicio de generacion de XML Facturae 3.2.2.

### 12.2 Entregables

| # | Entregable | Descripcion |
|---|-----------|-------------|
| F6-1 | `FacturaeDocument` entity | Content Entity con 50+ campos cubriendo la estructura completa Facturae 3.2.2: FileHeader (SchemaVersion, Modality, InvoiceIssuerType, ThirdParty), Parties (SellerParty, BuyerParty con TaxIdentification, AdministrativeCentres DIR3), Invoices (InvoiceHeader, InvoiceTotals, Items, PaymentDetails, LegalLiterals). Estados: draft, generated, signed, sent_face, registered_face, rejected_face, paid, cancelled. |
| F6-2 | `FacturaeTenantConfig` entity | Content Entity con configuracion fiscal por tenant: datos fiscales completos (NIF, razon social, domicilio, regimen fiscal), IBAN, retenciones (tipo, porcentaje), literales legales, patron de numeracion (`{SERIE}{YYYY}-{NUM:5}`), certificado (referencia a CertificateManager), endpoint FACe (produccion/staging), 3 codigos DIR3 por defecto (Oficina Contable, Organo Gestor, Unidad Tramitadora). |
| F6-3 | `FacturaeFaceLog` entity | Content Entity append-only para log de comunicaciones FACe: operacion (sendInvoice, queryInvoice, cancelInvoice), request SOAP, response SOAP, HTTP status code, timestamp, duracion_ms, invoice_id referencia. |
| F6-4 | `FacturaeXmlService` | Construye XML Facturae 3.2.2 completo. Metodos: `buildFacturaeXml(FacturaeDocument $document): string`, `buildFileHeader(FacturaeDocument $document): \DOMElement`, `buildParties(FacturaeDocument $document): \DOMElement`, `buildInvoice(FacturaeDocument $document): \DOMElement`. Implementa la estructura completa con TaxIdentification, AdministrativeCentres para DIR3 (Oficina Contable, Organo Gestor, Unidad Tramitadora), InvoiceTotals, PaymentDetails con IBAN. |
| F6-5 | `FacturaeNumberingService` | Servicio de numeracion secuencial con bloqueo pesimista (SELECT FOR UPDATE). Metodos: `getNextNumber(int $tenantId, string $serie): string`, `formatNumber(int $sequence, string $pattern): string`. Patron configurable con tokens: `{SERIE}`, `{YYYY}`, `{MM}`, `{NUM:N}` (N digitos con padding). |
| F6-6 | `FacturaeValidationService` | Validacion multi-capa. Metodos: `validateXsd(string $xml): ValidationResult`, `validateNif(string $nif): bool` (algoritmo NIF/CIF/NIE), `validateDir3(string $code): bool`, `validateIban(string $iban): bool` (ISO 13616), `validateAmounts(FacturaeDocument $document): ValidationResult` (reconciliacion de importes). |
| F6-7 | AccessControlHandlers | `FacturaeFaceLog` es append-only (deniega update/delete). `FacturaeDocument` permite update solo en estado `draft`. |
| F6-8 | Navegacion admin | 4 archivos YAML para las 3 entidades con tabs en `/admin/content/facturae-*` y enlaces en `/admin/structure/facturae-*`. |
| F6-9 | XSD y policy | `xsd/Facturaev3_2_2.xsd`, `policy/Facturaev3_2_2-policy.pdf` para validacion y firma. |

---

## 13. FASE 7: jaraba_facturae — XAdES, FACe y Servicios

**Duracion estimada:** 80-100 horas
**Prioridad:** P1 ALTA
**Spec de referencia:** Doc 180, Secciones 3-4

### 13.1 Objetivo

Implementar la firma electronica XAdES-EPES, el cliente SOAP para FACe, el servicio DIR3, y la API REST del modulo.

### 13.2 Entregables

| # | Entregable | Descripcion |
|---|-----------|-------------|
| F7-1 | `FacturaeXAdESService` | Firma XAdES-EPES para Facturae 3.2.2. Metodos: `signDocument(string $xml, int $tenantId): string`. Implementa: SHA-256 digest del documento, firma RSA con clave privada del PKCS#12 (via CertificateManagerService), canonicalizacion C14N, SignedProperties con SigningTime y SigningCertificate, SignaturePolicyIdentifier apuntando a la politica de firma Facturae 3.2.2. Usa extensiones nativas de PHP (`ext-openssl`, `ext-dom`). Zero dependencias externas de Composer. |
| F7-2 | `FACeClientService` | Cliente SOAP para portal FACe. Metodos: `sendInvoice(FacturaeDocument $document): FACeResponse`, `queryInvoice(string $registroId): FACeStatus`, `queryInvoiceList(array $filters): array`, `cancelInvoice(string $registroId, string $reason): FACeResponse`. Autenticacion mutual TLS con certificado PKCS#12. Endpoints: produccion `https://face.gob.es/api/` y staging `https://se-face.redsara.es/api/`. Estados FACe del ciclo de vida: 1200 (Registrada), 1300 (Registrada en RCF), 2400 (Contabilizada), 2500 (Reconocida obligacion de pago), 2600 (Pagada), 3100 (Anulacion solicitada), 3200 (Anulacion aceptada), 3300 (Anulacion rechazada). |
| F7-3 | `FacturaeDIR3Service` | Servicio de consulta del directorio DIR3 (60.000+ unidades organizativas). Metodos: `search(string $query): array` (autocomplete), `getUnit(string $code): DIR3Unit`, `validateCentre(string $code, string $type): bool`. Cache de 24h. Tipos de centro: `01` Oficina Contable, `02` Organo Gestor, `03` Unidad Tramitadora. |
| F7-4 | API REST (21 endpoints) | Implementados en `FacturaeApiController` y `FacturaeDir3Controller`. |
| F7-5 | FACeSyncQueueWorker | QueueWorker para sincronizacion periodica del estado de facturas enviadas a FACe. Cada 4 horas consulta el estado de las facturas en estado `sent_face`. |
| F7-6 | Tests | `FacturaeXAdESServiceTest` (firma valida, policy identifier, certificate info), `FACeClientServiceTest` (send, query, cancel, mutual TLS), `FacturaeValidationServiceTest` (NIF, IBAN, DIR3, XSD, importes). |

---

## 14. FASE 8: jaraba_facturae — Frontend, ECA y Tests

**Duracion estimada:** 90-124 horas
**Prioridad:** P1 ALTA
**Spec de referencia:** Doc 180, Secciones 5-8

### 14.1 Objetivo

Crear frontend, hooks ECA, y la suite completa de 26 tests.

### 14.2 Entregables

| # | Entregable | Descripcion |
|---|-----------|-------------|
| F8-1 | Templates Twig | `page--facturae.html.twig` (dashboard), `page--facturae-documents.html.twig` (listado), `facturae-document-detail.html.twig`, `facturae-dashboard.html.twig`, `facturae-pdf-template.html.twig`. Todos zero-region con parciales. |
| F8-2 | Parciales (6) | `_facturae-stats.html.twig`, `_facturae-document-card.html.twig`, `_facturae-face-status.html.twig`, `_facturae-dir3-search.html.twig`, `_facturae-signature-info.html.twig`, `_facturae-xml-preview.html.twig`. |
| F8-3 | SCSS (6 archivos) | Dashboard, documents, signature, face, responsive, main. Con `@use`, `var(--ej-*)`, `color-mix()`, BEM, mobile-first. |
| F8-4 | JS (2 archivos) | `facturae-dashboard.js` (Drupal.behaviors, once(), stats realtime), `facturae-dir3-autocomplete.js` (autocomplete contra API DIR3). |
| F8-5 | ECA-FA-001 | `hook_entity_insert` en `billing_invoice`: si el comprador es AAPP (deteccion por tipo de cliente o NIF que empieza por Q/S/P), generar documento Facturae automaticamente. |
| F8-6 | ECA-FA-002 | `hook_cron`: sincronizar estado FACe cada 4 horas. Consulta `FACeClientService::queryInvoiceList()` para facturas en estado `sent_face`. |
| F8-7 | ECA-FA-003 | `hook_cron`: alerta semanal de certificados proximos a expirar (reutiliza CertificateManagerService). |
| F8-8 | ECA-FA-004 | `hook_entity_insert` en `facturae_document` con estado `signed`: generar PDF con datos de firma, QR de verificacion FACe. |
| F8-9 | ECA-FA-005 | `hook_entity_update` en `facturae_document`: si se anula, generar factura rectificativa automatica. |
| F8-10 | Tests (26) | 8 unit + 10 kernel + 8 functional. Cubren XML generation, XAdES signing, FACe communication, DIR3 search, numbering, validation, permissions, dashboard, API endpoints. |
| F8-11 | Permisos RBAC (8) | `administer facturae`, `view facturae documents`, `create facturae documents`, `delete facturae documents`, `sign facturae documents`, `send facturae to face`, `view facturae logs`, `manage facturae config`. |

---

## 15. FASE 9: jaraba_einvoice_b2b — Entidades y UBL 2.1

**Duracion estimada:** 80-100 horas
**Prioridad:** P2 MEDIA
**Spec de referencia:** Doc 181, Secciones 1-3

### 15.1 Objetivo

Crear las 4 Content Entities del modulo E-Factura B2B y el servicio de generacion UBL 2.1 conforme a EN 16931.

### 15.2 Entregables

| # | Entregable | Descripcion |
|---|-----------|-------------|
| F9-1 | `EInvoiceDocument` entity | Content Entity con soporte dual outbound/inbound, formatos UBL 2.1 y Facturae 3.2.2, canales de entrega (spfe, platform, email, peppol), estados de documento y de pago independientes. |
| F9-2 | `EInvoiceTenantConfig` entity | Content Entity con configuracion B2B: credenciales SPFE (pendiente de API AEAT), Peppol participant ID, email/webhook para recepcion, terminos de pago por defecto (dias), umbral de morosidad. |
| F9-3 | `EInvoiceDeliveryLog` entity | Content Entity append-only para log multi-canal de comunicaciones. |
| F9-4 | `EInvoicePaymentEvent` entity | Content Entity para eventos de pago: payment_received, partial_payment, overdue_notification, dispute_opened, dispute_resolved. Referencia a einvoice_document. Campos: amount, payment_date, method, reference. |
| F9-5 | `EInvoiceUblService` | Genera UBL 2.1 conforme a EN 16931. Mapeo completo de Business Terms (BT-1 a BT-115) a elementos UBL. Metodos: `generateUbl(EInvoiceDocument $document): string`, `buildInvoiceLine(array $lineData): \DOMElement`. Soporta todos los tipos: 380 (Invoice), 381 (Credit Note), 383 (Debit Note), 386 (Prepayment Invoice). |
| F9-6 | `EInvoiceFormatConverterService` | Conversion bidireccional Facturae <-> UBL via modelo semantico neutral (EN16931Model). Metodos: `convertToUbl(string $facturaeXml): string`, `convertToFacturae(string $ublXml): string`, `detectFormat(string $xml): string`, `toNeutralModel(string $xml): EN16931Model`. |
| F9-7 | `EN16931Model` | Value Object con la estructura semantica neutral de EN 16931 para conversion entre formatos. Campos: seller, buyer, invoice_number, issue_date, due_date, currency, lines[], tax_totals[], payment_means, payment_terms. |
| F9-8 | Navegacion admin | 4 archivos YAML para las 4 entidades. |
| F9-9 | XSD y Schematron | `xsd/UBL-Invoice-2.1.xsd`, `schematron/EN16931-UBL-validation.sch`, `schematron/ES-CIUS-rules.sch`. |

---

## 16. FASE 10: jaraba_einvoice_b2b — Servicios, SPFE y Tests

**Duracion estimada:** 80-116 horas
**Prioridad:** P2 MEDIA
**Spec de referencia:** Doc 181, Secciones 3-8

### 16.1 Objetivo

Implementar los servicios restantes (entrega multi-canal, morosidad, validacion, stub SPFE), API REST, hooks ECA y tests.

### 16.2 Entregables

| # | Entregable | Descripcion |
|---|-----------|-------------|
| F10-1 | `EInvoiceDeliveryService` | Orquestador multi-canal. Metodos: `deliver(EInvoiceDocument $document): DeliveryResult`, `resolveChannel(EInvoiceDocument $document): string`. Canales: SPFE (futuro), platform (interno entre tenants), email (con UBL adjunto), Peppol (futuro). Cada entrega registra un `EInvoiceDeliveryLog`. |
| F10-2 | `EInvoicePaymentStatusService` | Gestion de morosidad conforme Ley 3/2004. Metodos: `checkOverdue(EInvoiceDocument $document): OverdueResult`, `detectMorosidad(): array` (batch diario), `calculateOverdueDays(EInvoiceDocument $document): int`. Plazos: 30 dias general, 60 dias maximo, 30 dias AAPP. |
| F10-3 | `EInvoiceValidationService` | Validacion en 4 capas: (1) XSD schema, (2) EN 16931 Schematron, (3) Spanish CIUS rules, (4) business rules (importes, NIF, fechas). Metodos: `validate(string $xml, string $format): ValidationResult`, `validateSchematron(string $xml, string $schematronPath): array`. |
| F10-4 | `SPFEClientService` | Patron Interface + Stub. `SPFEClientInterface` define los metodos. `SPFEClientStub` implementa un stub funcional para desarrollo (simula respuestas SPFE). `SPFEClientLive` se implementara cuando la AEAT publique la API. Metodos: `register(EInvoiceDocument $document): SPFEResponse`, `queryStatus(string $registrationId): SPFEStatus`, `receiveInbound(): array`. |
| F10-5 | API REST (24 endpoints) | Implementados en `EInvoiceApiController` y `EInvoicePaymentController`. Documentos (7), Envio/Recepcion (8), Estado de pago (5), Config/Conversion (4). |
| F10-6 | Templates y Parciales | 5 templates + 6 parciales. Zero-region, mobile-first, BEM, `var(--ej-*)`. |
| F10-7 | SCSS y JS | 6 SCSS + 2 JS. Dashboard interactivo con timeline de pagos y alertas de morosidad. |
| F10-8 | ECA Hooks (5) | Auto-generacion B2B, sync estado pago, deteccion morosidad diaria, recepcion inbound, comunicacion SPFE periodica. |
| F10-9 | Tests (23) | 7 unit + 9 kernel + 7 functional. Cubren UBL generation, format conversion, delivery, payment status, validation, Schematron, SPFE stub, permissions, API. |
| F10-10 | Permisos RBAC (9) | `administer einvoice`, `view einvoice documents`, `create einvoice documents`, `delete einvoice documents`, `send einvoice`, `receive einvoice`, `manage einvoice`, `manage einvoice payments`, `view einvoice reports`. |

---

## 17. FASE 11: Integracion cross-modulo y Dashboard Fiscal Unificado

**Duracion estimada:** 70-80 horas
**Prioridad:** P1 ALTA (tras completar F5 y F8)
**Modulos afectados:** Todos + ecosistema_jaraba_core

### 17.1 Objetivo

Crear el dashboard fiscal unificado que consolida el estado de cumplimiento de los tres modulos, integrar los flujos automaticos con jaraba_billing, y anadir el stack fiscal al Admin Center.

### 17.2 Entregables

| # | Entregable | Descripcion |
|---|-----------|-------------|
| F11-1 | `FiscalDashboardController` | Controller en `ecosistema_jaraba_core` (ruta `/admin/jaraba/fiscal`) que agrega datos de los tres modulos: registros VeriFactu pendientes, facturas Facturae rechazadas por FACe, e-facturas B2B con morosidad, estado de certificados de todos los tenants. DI opcional (NULL) para modulos que no esten instalados. |
| F11-2 | `FiscalComplianceService` | Servicio en `ecosistema_jaraba_core` que calcula el compliance score del tenant: 0-100 basado en cadena VeriFactu intacta, remisiones al dia, certificados vigentes, facturas FACe sin rechazos, morosidad B2B controlada. |
| F11-3 | `page--fiscal.html.twig` | Pagina frontend zero-region para el dashboard fiscal unificado. Incluye parciales de los 3 modulos (stats, alertas, certificados). Accesible desde el Admin Center (sidebar link). |
| F11-4 | Integracion Admin Center | Anadir enlace "Cumplimiento Fiscal" al sidebar del Admin Center (Spec f104). KPI card en el dashboard principal con compliance score. Slide-panel con resumen fiscal rapido. |
| F11-5 | Integracion jaraba_billing | Logica en `jaraba_billing.module` que detecta el tipo de factura (B2G, B2B, nacional) y delega al modulo fiscal correspondiente: VeriFactu para todas, Facturae adicional si B2G, E-Factura adicional si B2B. |
| F11-6 | Email alertas fiscales | 3 templates MJML nuevos en `jaraba_email`: `fiscal/certificate_expiring.mjml`, `fiscal/verifactu_chain_break.mjml`, `fiscal/face_invoice_rejected.mjml`. Registrados en TemplateLoaderService. |
| F11-7 | Feature gating | Anadir features fiscales al FeatureAccessService: modulo VeriFactu requiere plan Pro o superior, Facturae requiere plan Pro, E-Factura requiere plan Enterprise. Configuracion via FreemiumVerticalLimit. |
| F11-8 | Design Token Config | `ecosistema_jaraba_core.design_token_config.fiscal.yml` con paleta de colores fiscales para override por tenant. |
| F11-9 | Test integracion | Tests E2E que verifican el flujo completo: BillingInvoice pagada → VeriFactu record creado → remision AEAT → verificacion cadena. |

---

## 18. Paleta de Colores y Design Tokens

### 18.1 Colores fiscales (variables locales `$fiscal-*`)

| Token | Variable SCSS | Hex | Uso |
|-------|--------------|-----|-----|
| Primary | `$fiscal-primary` | `#1B4332` | Verde Hacienda Profundo — encabezados, bordes principales |
| Accent | `$fiscal-accent` | `#2D6A4F` | Verde Fiscal Medio — botones secundarios, highlights |
| Surface | `$fiscal-surface` | `#F0FFF4` | Fondo verde suave — areas de contenido |
| Success | `$fiscal-success` | `#059669` | Verde Valido — registros aceptados, cadena intacta |
| Danger | `$fiscal-danger` | `#DC2626` | Rojo Rechazado — registros rechazados, errores |
| Warning | `$fiscal-warning` | `#D97706` | Ambar Pendiente — registros pendientes de envio |
| Info | `$fiscal-info` | `#2563EB` | Azul Informativo — mensajes informativos |
| AEAT Blue | `$fiscal-aeat-blue` | `#003366` | Azul institucional AEAT — badges oficiales |
| FACe Green | `$fiscal-face-green` | `#006633` | Verde portal FACe — estados FACe |
| EU Blue | `$fiscal-eu-blue` | `#003399` | Azul Union Europea — badges UBL/EN 16931 |
| Text | `$fiscal-text` | `#1A1A2E` | Texto principal |
| Text Light | `$fiscal-text-light` | `#6B7280` | Texto secundario |

### 18.2 Consumo via CSS Custom Properties

Todos los colores se consumen con `var(--ej-*)` y fallback a la variable local:

```scss
.fiscal-card {
  background: var(--ej-bg-surface, #{$fiscal-surface});
  color: var(--ej-text-primary, #{$fiscal-text});
  border: 1px solid var(--ej-border-color, color-mix(in srgb, #{$fiscal-primary} 20%, transparent));
}
```

### 18.3 Design Token Config YAML

```yaml
# ecosistema_jaraba_core.design_token_config.fiscal.yml
# Override de colores para modulos fiscales (inyectado via hook_preprocess_html)
langcode: es
status: true
dependencies: {}
id: fiscal
label: 'Paleta Fiscal'
colors:
  primary: '#1B4332'
  secondary: '#2D6A4F'
  accent: '#059669'
  surface: '#F0FFF4'
```

---

## 19. Patron de Iconos SVG

### 19.1 Directorio y estructura

```
ecosistema_jaraba_core/images/icons/fiscal/
  invoice-check.svg
  invoice-check-duotone.svg
  hash-chain.svg
  hash-chain-duotone.svg
  qr-verify.svg
  qr-verify-duotone.svg
  aeat-seal.svg
  aeat-seal-duotone.svg
  signed-document.svg
  signed-document-duotone.svg
  face-portal.svg
  face-portal-duotone.svg
  invoice-b2b.svg
  invoice-b2b-duotone.svg
  payment-status.svg
  payment-status-duotone.svg
  certificate.svg
  certificate-duotone.svg
  shield-fiscal.svg
  shield-fiscal-duotone.svg
```

### 19.2 Uso en Twig

```twig
{# Icono de registro VeriFactu valido #}
{{ jaraba_icon('fiscal', 'invoice-check', { color: 'success', size: '20px' }) }}

{# Icono de cadena de hash (version duotone) #}
{{ jaraba_icon('fiscal', 'hash-chain', { variant: 'duotone', color: 'corporate', size: '24px' }) }}

{# Badge de estado AEAT #}
{{ jaraba_icon('fiscal', 'aeat-seal', { color: 'fiscal-aeat', size: '16px' }) }}

{# Certificado digital #}
{{ jaraba_icon('fiscal', 'certificate', { variant: 'duotone', color: 'warning', size: '32px' }) }}
```

### 19.3 Estructura SVG duotone (plantilla)

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
  <!-- Capa de fondo (opacidad 0.3 para efecto duotone) -->
  <path d="M4 6h16v12H4z" fill="currentColor" opacity="0.3"/>
  <!-- Capa principal (trazo solido) -->
  <path d="M4 6h16v12H4zM8 10h8M8 14h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  <!-- Check de validacion -->
  <path d="M15 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
```

---

## 20. Orden de Implementacion Global

### 20.1 Cronograma recomendado

```
MES 1-2 (Marzo-Abril 2026) — FASE 0 + FASES 1-3
  Semana 1-2:  FASE 0 — Infraestructura compartida
  Semana 3-4:  FASE 1 — VeriFactu entidades
  Semana 5-6:  FASE 2 — VeriFactu servicios core
  Semana 7-8:  FASE 3 — VeriFactu AEAT SOAP

MES 3-4 (Mayo-Junio 2026) — FASES 4-5 + FASE 6
  Semana 9-10:  FASE 4 — VeriFactu frontend
  Semana 11-12: FASE 5 — VeriFactu ECA + tests
  Semana 13-14: FASE 6 — Facturae entidades + XML

MES 5-6 (Julio-Agosto 2026) — FASES 7-8
  Semana 15-16: FASE 7 — Facturae XAdES + FACe
  Semana 17-18: FASE 8 — Facturae frontend + ECA + tests

MES 7-8 (Septiembre-Octubre 2026) — FASES 9-11
  Semana 19-20: FASE 9 — E-Factura entidades + UBL
  Semana 21-22: FASE 10 — E-Factura servicios + tests
  Semana 23-24: FASE 11 — Integracion + Dashboard unificado

MES 9 (Noviembre 2026) — Buffer y QA
  Semana 25-26: QA integral, pruebas con AEAT test
  Semana 27-28: Pruebas con certificados reales, piloto

DEADLINE LEGAL: 1 Enero 2027 (sociedades)
```

### 20.2 Dependencias entre fases

```
F0 (Infraestructura) ──▶ F1 ──▶ F2 ──▶ F3 ──▶ F4 ──▶ F5 (VeriFactu completo)
                    │
                    ├──▶ F6 ──▶ F7 ──▶ F8 (Facturae completo)
                    │
                    └──▶ F9 ──▶ F10 (E-Factura completo)

F5 + F8 ──▶ F11 (Dashboard Fiscal Unificado)
```

### 20.3 Criterios de aceptacion por hito

| Hito | Fecha limite | Criterio |
|------|-------------|----------|
| VeriFactu MVP | Mayo 2026 | Registros de alta creados automaticamente al pagar invoice, hash chain verificada |
| VeriFactu Produccion | Agosto 2026 | Remisiones AEAT exitosas en entorno de pruebas, QR funcional, PDF con etiqueta |
| Facturae B2G MVP | Agosto 2026 | XML Facturae 3.2.2 valido contra XSD, firma XAdES-EPES con certificado real |
| Facturae B2G Produccion | Octubre 2026 | Envio exitoso a FACe staging, ciclo de vida completo |
| E-Factura B2B MVP | Octubre 2026 | UBL 2.1 generado conforme EN 16931, conversion bidireccional |
| Dashboard Fiscal | Noviembre 2026 | Compliance score calculado, alertas funcionales, certificados monitorizados |
| **Go-Live VeriFactu** | **Diciembre 2026** | **Produccion con certificados reales, cadena integra, remisiones exitosas** |

---

## 21. Relacion con jaraba_billing y jaraba_foc

### 21.1 jaraba_billing como trigger

El modulo `jaraba_billing` es el punto de entrada principal para la generacion automatica de registros fiscales. Cuando un `BillingInvoice` cambia de estado a `paid` (confirmado via webhook Stripe), se activan los hooks fiscales:

```
BillingInvoice (estado: paid)
    │
    ├──▶ hook_entity_update (jaraba_verifactu)
    │     └── VeriFactuRecordService::createAltaRecord()
    │         └── Hash → QR → XML → Queue
    │
    ├──▶ hook_entity_update (jaraba_facturae) [si comprador es AAPP]
    │     └── FacturaeXmlService::buildFacturaeXml()
    │         └── Sign → FACe → PDF
    │
    └──▶ hook_entity_update (jaraba_einvoice_b2b) [si comprador es empresa]
          └── EInvoiceUblService::generateUbl()
              └── Validate → Deliver → Log
```

### 21.2 jaraba_foc como referencia

El modulo `jaraba_foc` establece el patron de entidades financieras inmutables (append-only) que el Stack Fiscal reutiliza. Las `FinancialTransaction` del FOC son el equivalente contable de los registros VeriFactu: ambos son inmutables, auditables y encadenados.

### 21.3 Coexistencia sin duplicacion

- **VeriFactu no reemplaza a Billing**: VeriFactu genera registros de cumplimiento fiscal a partir de facturas de Billing. No genera facturas.
- **Facturae no reemplaza a PDF**: Facturae genera el XML electronico formal para B2G. El PDF de la factura lo sigue generando Billing. Facturae anade firma, QR y etiqueta al PDF existente.
- **E-Factura complementa**: E-Factura anade el formato UBL/EN 16931 para intercambio B2B. No sustituye al formato interno de Billing.

---

## 22. Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-02-16 | Claude Opus 4.6 | Creacion del plan de implementacion completo. 12 fases (F0-F11), 720-956 horas estimadas. Correspondencia con docs 178-182. Cumplimiento de 15 directrices del proyecto. |

---

**Documentos relacionados:**
- [20260215b-178_Auditoria_VeriFactu_WorldClass_v1](../tecnicos/20260215b-178_Auditoria_VeriFactu_WorldClass_v1_Claude.md)
- [20260215b-179_Platform_VeriFactu_Implementation_v1](../tecnicos/20260215b-179_Platform_VeriFactu_Implementation_v1_Claude.md)
- [20260215c-180_Platform_Facturae_FACe_B2G_v1](../tecnicos/20260215c-180_Platform_Facturae_FACe_B2G_v1_Claude.md)
- [20260215d-181_Platform_EFactura_B2B_v1](../tecnicos/20260215d-181_Platform_EFactura_B2B_v1_Claude.md)
- [20260215e-182_Gap_Analysis_Madurez_Documental_v1](../tecnicos/20260215e-182_Gap_Analysis_Madurez_Documental_v1_Claude.md)
- [Arquitectura Theming Master](../arquitectura/2026-02-05_arquitectura_theming_saas_master.md)
- [Directrices del Proyecto](../00_DIRECTRICES_PROYECTO.md)

---
_Jaraba Impact Platform | Plan de Implementacion Stack Cumplimiento Fiscal v1.0 | Febrero 2026_
