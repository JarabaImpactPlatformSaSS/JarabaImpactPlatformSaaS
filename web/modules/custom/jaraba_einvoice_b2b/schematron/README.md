# EN 16931 Schematron Validation Rules

This directory contains Schematron (.sch) files for semantic validation
of UBL 2.1 invoices against EN 16931 business rules and Spanish CIUS
(Core Invoice Usage Specification).

## Required Files

| File | Source | Purpose |
|------|--------|---------|
| `EN16931-UBL-validation.sch` | [CEF eInvoicing](https://github.com/ConnectingEurope/eInvoicing-EN16931) | EN 16931 semantic business rules (CEN/TS 16931-3-2) |
| `ES-CIUS-rules.sch` | AEAT (pending publication) | Spanish CIUS: NIF validation, Spanish tax codes, IRPF withholdings |

## Download Instructions

### EN 16931 UBL Validation

1. Clone or download the CEF eInvoicing repository:
   https://github.com/ConnectingEurope/eInvoicing-EN16931

2. Copy the UBL Schematron file:
   `ubl/schematron/EN16931-UBL-validation.sch` → `EN16931-UBL-validation.sch`

3. The Schematron validates ~170 business rules including:
   - BR-01 to BR-65: Mandatory element checks
   - BR-CO-01 to BR-CO-26: Calculation consistency
   - BR-DEC-01 to BR-DEC-26: Decimal precision
   - BR-CL-01 to BR-CL-26: Code list validation

### Spanish CIUS Rules

The Spanish CIUS (ES-CIUS-rules.sch) will be published by AEAT as part of
the SPFE (Solucion Publica de Facturacion Electronica) regulation under
Ley 18/2022 (Crea y Crece). Until official publication, the module uses
programmatic validation in `EInvoiceValidationService` for Spanish-specific
rules:

- NIF/CIF format validation
- Spanish VAT type codes (01=IVA, 02=IPSI, 03=IGIC)
- IRPF withholding validation
- Payment terms compliance (Ley 15/2010 morosidad)

## Validation Pipeline

The module implements 4-layer validation (Doc 181, Section 3.3):

1. **XSD schema** validation against `UBL-Invoice-2.1.xsd`
2. **Schematron EN 16931** semantic business rules
3. **Spanish CIUS** country-specific rules
4. **Business rules** (NIF valid, IBAN correct, totals balanced)

## Spec Reference

- Doc 181, Section 3.3 (Validation Pipeline)
- EN 16931-1:2017 + CEN/TS 16931-3-2 (Schematron for UBL)
- Ley 18/2022 (Crea y Crece) + pending AEAT regulation
