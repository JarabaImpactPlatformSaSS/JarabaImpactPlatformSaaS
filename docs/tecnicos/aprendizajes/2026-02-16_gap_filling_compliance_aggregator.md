# Aprendizajes: Gap-filling Compliance Stack + ComplianceAggregatorService

| Campo | Valor      |
|-------|------------|
| Fecha | 2026-02-16 |

---

## Patron Principal

La sesion completo el gap-filling de los 3 modulos compliance legal N1 (jaraba_privacy, jaraba_legal, jaraba_dr) implementando logica de produccion real en los 15 servicios que previamente eran stubs. Ademas, se creo el ComplianceAggregatorService como servicio transversal que agrega 9 KPIs de los 3 modulos en un dashboard unificado con score global 0-100. Se resolvio un error 500 critico en jaraba_tenant_export causado por el patron zero-region template que no procesa page.content. Total: +16,035 LOC, 97 archivos, 13 test files nuevos.

---

## Aprendizajes Clave

### 1. ComplianceAggregatorService como patron de agregacion KPI cross-module con inyeccion condicional

**Situacion:** Los 3 modulos compliance (privacy, legal, dr) operan de forma independiente sin vision unificada del estado de cumplimiento del tenant. Se necesitaba un dashboard que mostrara el score global de compliance con KPIs de los 3 modulos, incluso cuando alguno no esta instalado.

**Aprendizaje:** El patron de 9 KPIs (3 por modulo) con inyeccion condicional (servicios nullable) permite agregar datos de modulos opcionales sin dependencias hard. Cuando un modulo no esta instalado, sus 3 KPIs reportan `status: 'not_available'` y se excluyen del calculo del score ponderado. El CompliancePanelController expone tanto HTML (render array) como JSON (API endpoint) para soportar AJAX auto-refresh cada 60 segundos. Los umbrales de alerta (critico <50%, warning <80%) se aplican por KPI individual, no sobre el score global.

**Regla:** COMPLIANCE-AGG-001: Los servicios de agregacion cross-module DEBEN usar inyeccion condicional (nullable) y reportar KPIs no disponibles como `not_available` en vez de 0 — esto evita penalizar el score global por modulos no instalados.

### 2. El gap-filling de servicios stub requiere implementacion simultanea de tests, JS y SCSS

**Situacion:** Los servicios de los 3 modulos compliance tenian la estructura correcta (clases, metodos, DI) pero retornaban datos mock o arrays vacios. El gap-filling requeria: (1) logica de produccion en PHP, (2) tests unitarios que verificaran esa logica, (3) JS behaviors para interactividad frontend, (4) SCSS partials para estilos.

**Aprendizaje:** Implementar los 4 layers simultaneamente (PHP service + PHPUnit test + JS behavior + SCSS partial) por modulo es mas eficiente que hacerlo por layer. Cada modulo compliance sigue el mismo scaffold: 5 servicios → 4 test files → 2-3 JS behaviors → 8 SCSS partials (variables, main, dashboard, responsive + 4 componentes). La consistencia del scaffold entre modulos acelera la implementacion y reduce errores.

**Regla:** COMPLIANCE-LEGAL-002: Al hacer gap-filling de servicios stub, implementar los 4 layers (service logic + test + JS + SCSS) por modulo completo, no por layer horizontal. El scaffold por modulo es: 5 services → 4 tests → 2-3 JS → 8 SCSS.

### 3. LegalApiController con endpoints publicos anonimos para whistleblower (Directiva EU 2019/1937)

**Situacion:** El canal de denuncias (whistleblower) requiere anonimato total del denunciante por la Directiva EU 2019/1937. Los endpoints de reporte y consulta de estado deben ser accesibles sin autenticacion para proteger la identidad del denunciante.

**Aprendizaje:** Los 12 endpoints del LegalApiController se dividen en 5 grupos con diferentes niveles de autenticacion: ToS (3, Bearer + permission), SLA (2, Bearer + permission), AUP (2, Bearer + permission), Offboarding (3, Bearer + permission especial), Whistleblower (2, PUBLICOS sin auth). El tracking_code sirve como unico identificador del reporte, generado server-side con suficiente entropia. No se almacena IP, user agent ni cookies del denunciante.

**Regla:** COMPLIANCE-LEGAL-003: Los endpoints whistleblower DEBEN ser publicos (sin autenticacion) para cumplir con la Directiva EU 2019/1937. Usar tracking_code como unico identificador — NUNCA almacenar datos identificativos del denunciante (IP, UA, cookies).

### 4. Zero-region templates NO procesan page.content — datos via hook_preprocess_page

**Situacion:** jaraba_tenant_export tenia un error 500 en `/tenant/export` porque pasaba entity objects como variables no-# en el render array, y el page template zero-region no renderizaba `{{ page.content }}` de la misma forma que los templates con regiones.

**Aprendizaje:** Los page templates zero-region (que usan `{{ page.content }}` directamente sin regiones Drupal) pueden no procesar correctamente variables no-# del render array. La solucion es mover la obtencion de datos al `hook_preprocess_page()` (o `hook_preprocess_page__SUGGESTION()`) e inyectar las variables como variables de template, no como propiedades del render array. Ademas, drupalSettings debe adjuntarse via `$variables['#attached']` en el preprocess, no en el controller.

**Regla:** ZERO-REGION-002: En templates zero-region, NUNCA pasar datos como variables no-# del render array del controller. Usar `hook_preprocess_page__SUGGESTION()` para inyectar variables de template y adjuntar drupalSettings. Los controllers solo deben devolver el render array basico con #theme.

### 5. La reconciliacion de documentacion debe incluir nuevos servicios transversales

**Situacion:** El commit e16d3e28 incluyo el ComplianceAggregatorService y CompliancePanelController (nuevos en ecosistema_jaraba_core) pero la documentacion previa (v52.0.0 indice, v35.0.0 arquitectura, v36.0.0 directrices) no los mencionaba en absoluto. Los 3 documentos raiz necesitaron actualizacion simultanea.

**Aprendizaje:** Cuando un commit introduce servicios transversales nuevos (que afectan a ecosistema_jaraba_core como hub central), la documentacion debe actualizarse en cascada: (1) Arquitectura — nueva seccion o subseccion describiendo el servicio, (2) Directrices — nueva entrada en seccion 1.4 modulos, (3) Indice — banner actualizado + changelog + estadisticas. Los servicios transversales son faciles de omitir porque no crean modulos nuevos sino que extienden el core.

**Regla:** DOC-CASCADE-001: Todo servicio transversal nuevo en ecosistema_jaraba_core DEBE documentarse en los 3 documentos raiz (arquitectura, directrices, indice) en la misma sesion que se implementa. Los servicios transversales son invisibles si solo se documentan en el changelog.

---

## Metricas de la Sesion

| Metrica | Valor |
|---------|-------|
| Archivos modificados/creados | 97 (commit e16d3e28) |
| Lineas de codigo | +16,035 / -773 LOC |
| Tests nuevos | 13 test files (4 privacy + 4 legal + 4 DR + 1 ComplianceAggregator) |
| JS behaviors nuevos | 8 (cookie-banner, dpa-signature, privacy-dashboard, legal-dashboard, tos-acceptance, whistleblower-form, dr-dashboard, incident-detail) |
| SCSS partials nuevos | 24 (8 por modulo compliance) |
| API endpoints nuevos | 12 (LegalApiController) + 1 (compliance overview) |
| Servicios con logica real | 15 (5 por modulo compliance) + 1 (ComplianceAggregatorService) |
| Bugs resueltos | 1 (tenant export 500 error en zero-region) |
| Docs actualizados | 3 (arquitectura v36, directrices v37, indice v53) |
