# Aprendizajes: FASE 11 Fiscal Integration Layer + Stack Compliance Legal N1

| Campo | Valor      |
|-------|------------|
| Fecha | 2026-02-16 |

---

## Patron Principal

La sesion completo la FASE 11 (Integration Layer) del Stack Fiscal VeriFactu y la implementacion completa del Stack Compliance Legal N1 (3 modulos, 198 archivos, +13,281 LOC). La FASE 11 creo servicios transversales (FiscalComplianceService, FiscalDashboardController, FiscalInvoiceDelegationService) que integran los 3 modulos fiscales existentes (jaraba_verifactu, jaraba_facturae, jaraba_einvoice_b2b) con el dashboard y el billing. El Stack Compliance Legal N1 (jaraba_privacy, jaraba_legal, jaraba_dr) se implemento siguiendo fielmente las 20 directrices del plan de implementacion (docs 183/184/185/201).

---

## Aprendizajes Clave

### 1. FiscalComplianceService como patron de integracion cross-module con score composito

**Situacion:** Los 3 modulos fiscales (verifactu, facturae, einvoice_b2b) funcionaban de forma independiente sin vision unificada del estado de cumplimiento fiscal del tenant. Se necesitaba un score unico 0-100 para el dashboard y alertas.

**Aprendizaje:** El patron de 5 factores x 20 puntos (peso igual) con grade A-F proporciona un modelo simple, comprensible y accionable. Cada factor evalua un aspecto distinto: modulos instalados, registros enviados, errores recientes, certificados vigentes, configuracion completa. El servicio usa NULL guards (`$this->hashVerificationService ?? null`) para funcionar incluso cuando los modulos fiscales no estan instalados, degradando graciosamente el score en lugar de fallar.

**Regla:** FISCAL-INT-001: Los servicios de integracion cross-module DEBEN usar inyeccion de dependencias opcional con NULL guards — NUNCA hard dependencies. El score composito debe degradar graciosamente (factores no evaluables = 0 puntos, no excepcion).

### 2. FiscalDashboardController con feature gating y zero-region template

**Situacion:** El dashboard fiscal necesitaba mostrar estadisticas de modulos que pueden no estar instalados (verifactu, facturae, einvoice_b2b). Cada seccion del dashboard es condicional.

**Aprendizaje:** El patron `getInstalledModules()` + render array condicional permite que el controller genere variables Twig con `installed: TRUE/FALSE` para cada modulo. El template Twig usa `{% if fiscal_verifactu_stats.installed %}` para mostrar/ocultar secciones. drupalSettings inyecta el score al JS para animaciones. El page template zero-region en el theme (`page--fiscal.html.twig`) usa `{{ page.content }}` sin regiones laterales, consistente con el patron de todos los dashboards del ecosistema.

**Regla:** FISCAL-INT-002: Los dashboards que agregan datos de multiples modulos opcionales DEBEN usar un mapa `installed_modules` que el controller pasa como render variable — el template decide visibilidad, no el controller.

### 3. FiscalInvoiceDelegationService con deteccion automatica NIF prefix

**Situacion:** Las facturas deben enviarse por distintos canales segun el destinatario: B2G (sector publico) via FACe/FacturaE, B2B (empresas) via SII/VeriFactu, B2C via formato libre. La deteccion manual es propensa a errores.

**Aprendizaje:** Los NIFs de administraciones publicas espanolas siguen patrones predecibles (prefijos S, Q, P para organismos publicos). El servicio detecta automaticamente el tipo de destinatario por el prefijo del NIF/CIF y delega al modulo correcto. Esto elimina la necesidad de que el usuario seleccione manualmente el canal de envio, reduciendo errores de cumplimiento.

**Regla:** FISCAL-INT-003: La delegacion de facturas electronicas DEBE ser automatica basada en NIF prefix — B2G (S/Q/P prefixes) → FacturaE, B2B → VeriFactu/SII, B2C → formato libre. NUNCA requerir seleccion manual del canal.

### 4. Stack Compliance Legal N1 con 20 directrices cumplidas desde el diseno

**Situacion:** El plan de implementacion definia 20 directrices obligatorias (i18n, SCSS Federated Design Tokens, Dart Sass, zero-region, hook_preprocess_html, modals CRUD, Field UI+Views, config desde UI, parciales Twig, seguridad, comentarios 3D, iconos SVG duotone, hooks no ECA, API envelope, tenant_id, AccessControlHandler, DB indexes). La implementacion debia cumplirlas todas.

**Aprendizaje:** Documentar las directrices como checklist ANTES de la implementacion y mapearlas a secciones especificas del plan (tabla de correspondencia 40+ filas) permite verificar cumplimiento durante la codificacion en lugar de en auditoria posterior. Las 3 modules (privacy, legal, dr) siguieron el mismo scaffold: .info.yml → .install → entities → services → controllers → templates → SCSS → tests. Este patron repetible reduce errores y acelera la implementacion.

**Regla:** COMPLIANCE-LEGAL-001: Todo stack de modulos nuevos DEBE tener una tabla de correspondencia directriz↔seccion del plan ANTES de codificar. Las directrices se verifican durante la implementacion, no despues.

### 5. La contabilizacion real del proyecto revela crecimiento no documentado

**Situacion:** La estadistica del indice marcaba 37 modulos custom y 18 con package.json. Al actualizar la documentacion, el conteo real revelo 69 modulos custom y 30 con package.json — casi el doble de lo documentado.

**Aprendizaje:** Las estadisticas del indice se actualizaban incrementalmente con cada sesion (+1 modulo, +2 tests) pero nunca se reconciliaban con el estado real del filesystem. Multiples sesiones de implementacion crearon modulos que se contaron en el changelog pero no se sumaron correctamente al total. La reconciliacion periodica con `ls -d web/modules/custom/*/` es necesaria para mantener la precision.

**Regla:** STATS-001: Cada 10 versiones del indice, ejecutar reconciliacion de estadisticas contra el filesystem real: `ls -d web/modules/custom/*/`, `find -name "*.mjml"`, `find -name "*Test.php"`. Las estadisticas incrementales acumulan error.

---

## Metricas de la Sesion

| Metrica | Valor |
|---------|-------|
| Archivos creados/modificados | 198+ archivos compliance legal + FASE 11 integration |
| Lineas de codigo | +13,281 LOC (compliance legal) + ~1,200 LOC (FASE 11) |
| Tests unitarios | 38 PHPUnit tests (FASE 11) |
| Modulos nuevos | 3 (jaraba_privacy, jaraba_legal, jaraba_dr) |
| Servicios nuevos | 18 (15 compliance legal + 3 fiscal integration) |
| API endpoints | 30 (compliance legal) |
| Entidades nuevas | 14 (compliance legal) |
| Directrices cumplidas | 20/20 (compliance legal) |
| Score madurez | 4.9/5.0 |
