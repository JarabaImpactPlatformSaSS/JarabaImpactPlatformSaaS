# Aprendizajes: Stack Cumplimiento Fiscal — VeriFactu + Facturae + E-Factura B2B

| Campo | Valor      |
|-------|------------|
| Fecha | 2026-02-15 |

---

## Patron Principal

La sesion de especificacion fiscal produjo 5 documentos tecnicos (178-182) que cubren el stack completo de cumplimiento fiscal del ecosistema Jaraba. El hallazgo principal es que VeriFactu NO esta implementado como modulo, a pesar de que la plataforma ya tiene componentes reutilizables (~70%): hash chains SHA-256 del Buzon de Confianza, firma digital PAdES, QR dinamico, append-only logs del FOC, y ECA automation. La especificacion define 3 modulos nuevos (jaraba_verifactu, jaraba_facturae, jaraba_einvoice_b2b) con una inversion estimada de 720-956h y un deadline legal critico: sociedades 1 enero 2027, autonomos 1 julio 2027.

---

## Aprendizajes Clave

### 1. VeriFactu tiene impacto dual: Jaraba como empresa Y como plataforma para tenants

Jaraba Impact S.L. necesita VeriFactu para emitir facturas de suscripciones SaaS a tenants (deadline 1 ene 2027). Ademas, los tenants autonomos que usan ServiciosConecta/AgroConecta/ComercioConecta para facturar a sus clientes necesitan un sistema VeriFactu-compliant (deadline 1 jul 2027). Este doble impacto multiplica la urgencia y justifica la prioridad P0.

### 2. Los componentes existentes del ecosistema cubren ~70% de la base tecnica

El hash chain SHA-256 del Buzon de Confianza (doc 88) es reutilizable al ~80% adaptando de documentos a registros de facturacion. PAdES (doc 89) reutilizable al ~60% adaptando a XAdES para XML. QR dinamico (docs 65/81) al ~50% cambiando URL a verificacion AEAT. FOC append-only al ~90%. ECA automation al ~85%. Esto reduce significativamente el esfuerzo de implementacion real vs greenfield.

### 3. Los tres modulos fiscales comparten CertificateManagerService (PKCS#12)

Un unico certificado digital puede servir para firmar registros VeriFactu (XAdES), firmar facturas Facturae (XAdES-EPES), y autenticarse ante la SPFE para E-Factura B2B. La gestion centralizada del certificado (almacenamiento, renovacion, alertas de caducidad) es transversal a los 3 modulos y debe implementarse como servicio compartido.

### 4. Una factura puede tener simultaneamente registros VeriFactu + Facturae + E-Invoice

Un BillingInvoice puede generar: (1) un verifactu_invoice_record (obligatorio para todas las facturas), (2) un facturae_document si el destinatario es Administracion Publica (B2G), y (3) un einvoice_document si es B2B obligatorio bajo Ley Crea y Crece. Los tres modulos operan de forma independiente pero coordinada sobre la misma factura origen.

### 5. El reglamento de E-Factura B2B (Ley Crea y Crece) AUN no esta publicado

A febrero 2026, solo existe la ley base (Ley 18/2022) y un borrador. La Orden Ministerial tecnica y la Solucion Publica de Facturacion Electronica (SPFE) estan pendientes. El modulo jaraba_einvoice_b2b se especifica con un SPFEClient stub que debera actualizarse cuando se publique el reglamento definitivo. Prioridad P2 refleja esta incertidumbre.

### 6. VeriFactu exige inalterabilidad total: registros APPEND-ONLY con hash chain

Cada verifactu_invoice_record incluye un hash SHA-256 encadenado con el registro anterior (patron del Anexo II Orden HAC/1177/2024). Prohibido modificar o borrar registros emitidos. El ecosistema ya tiene este patron en FOC (financial_transaction inmutable), lo que facilita la implementacion. Las correcciones se hacen via registros de anulacion/rectificacion, nunca editando el original.

### 7. La remision AEAT tiene flow control estricto: 60 segundos, 1000 registros por lote

El VeriFactuRemisionService debe respetar el flow control de AEAT: maximo 1000 registros por batch, 60 segundos entre envios, reintentos con backoff exponencial. El cron de remision (ECA-VF-003) procesa la cola cada 60 segundos. Esto implica disenar un sistema de colas robusto con gestion de errores y reintentos.

### 8. El Gap Analysis documental revela 3 gaps criticos para Level 1 Production-Ready

Tras los docs 179-181, el compliance fiscal quedo cubierto. Los 3 gaps restantes para N1 son: (1) GDPR/DPA templates operativos (35-45h), (2) Legal Terms SaaS (35-45h), (3) Disaster Recovery Plan (35-45h). Total: 105-135h para completar N1. N2 tiene 8 gaps (AI Agents, Mobile, Multi-Region...) y N3 requiere 7 docs enterprise (SOC 2, ISO 27001, ENS...).

### 9. La nomenclatura de documentos usa prefijo de fecha + codigo secuencial para evitar colisiones

Los docs 178-182 usan prefijo `20260215b-178`, `20260215b-179`, `20260215c-180`, `20260215d-181`, `20260215e-182` para distinguirse de otros docs con numeros similares de sesiones anteriores (ej: doc 178 de enero era "Visitor Journey", doc 179 era "Insights Hub"). El prefijo de fecha completo + letra de sesion garantiza unicidad.

---

## Estadisticas

| Metrica | Valor |
|---------|-------|
| Documentos creados | 5 (docs 178-182) |
| Modulos especificados | 3 (jaraba_verifactu, jaraba_facturae, jaraba_einvoice_b2b) |
| Entidades totales | 11 (4 + 3 + 4) |
| Servicios totales | 19 (7 + 6 + 6) |
| REST API endpoints | 66 (21 + 21 + 24) |
| ECA flows | 15 (5 + 5 + 5) |
| Tests especificados | 72 (23 + 26 + 23) |
| Inversion estimada | 720-956h / 32,400-43,020 EUR |
| Componentes reutilizables | ~70% base existente |

---

## Referencias

- [20260215b-178_Auditoria_VeriFactu_WorldClass_v1_Claude.md](../20260215b-178_Auditoria_VeriFactu_WorldClass_v1_Claude.md)
- [20260215b-179_Platform_VeriFactu_Implementation_v1_Claude.md](../20260215b-179_Platform_VeriFactu_Implementation_v1_Claude.md)
- [20260215c-180_Platform_Facturae_FACe_B2G_v1_Claude.md](../20260215c-180_Platform_Facturae_FACe_B2G_v1_Claude.md)
- [20260215d-181_Platform_EFactura_B2B_v1_Claude.md](../20260215d-181_Platform_EFactura_B2B_v1_Claude.md)
- [20260215e-182_Gap_Analysis_Madurez_Documental_v1_Claude.md](../20260215e-182_Gap_Analysis_Madurez_Documental_v1_Claude.md)
