# 179_Kit_Digital_Agente_Digitalizador_Implementacion_v1
# Version: 1.0 | Fecha: 18 marzo 2026
# Estado: Especificacion Tecnica para Implementacion
# Dependencias: 158_Vertical_Pricing_Matrix_v2, 134_Stripe_Billing, jaraba_billing
# Destinatario: Claude Code — spec ejecutable
# Equipo: Claude Code (integramente)

---

## 1. CONTEXTO Y JUSTIFICACION

### 1.1 Por que PED S.L. puede ser Agente Digitalizador

Plataforma de Ecosistemas Digitales S.L. (NIF B93750271) cumple todos los requisitos para adhesion como Agente Digitalizador del Kit Digital:

**Requisito de facturacion (CUMPLE):**
- Umbral exigido: 100.000 EUR acumulados en 2 anos, o 50.000 EUR en 1 ano
- Facturacion acreditada PED: 427.500 EUR en proyectos PIIL (insercion laboral digital)
  - SC/ICV/0156/2023: 225.000 EUR (50 proyectos, 8 provincias, dic2023-jun2025)
  - SC/ICV/0111/2025: 202.500 EUR (45 proyectos, Malaga+Sevilla, dic2025-jun2027)
- La facturacion corresponde al mercado espanol (requisito explicito)
- Superacion del umbral: 8,5x el minimo exigido

**Otros requisitos:**
- Al corriente de obligaciones tributarias y Seguridad Social: SI (empresa activa)
- No empresa en crisis: SI (subvencion publica activa)
- Domicilio fiscal en UE: SI (Espana)
- Web dedicada Kit Digital: PENDIENTE (este documento especifica como crearla)

**Restriccion bidireccional:** Si PED es Agente Digitalizador, PED NO puede ser beneficiaria del Kit Digital. Irrelevante en la practica: PED ya tiene su propia plataforma construida.

### 1.2 Categorias Kit Digital seleccionadas

De las ~13 categorias del Anexo IV, PED solicita adhesion en las siguientes:

| # | Categoria Kit Digital | Vertical(es) Jaraba | Bono max Seg.I | Bono max Seg.II | Bono max Seg.III |
|---|----------------------|---------------------|---------------|----------------|-----------------|
| C1 | Comercio electronico | ComercioConecta, AgroConecta | 2.000 EUR | 2.000 EUR | 2.000 EUR |
| C2 | Sitio web y presencia en internet | Todos (Page Builder GrapesJS) | 2.000 EUR | 2.000 EUR | 2.000 EUR |
| C3 | Gestion de clientes (CRM) | jaraba_crm addon + ServiciosConecta | 4.000 EUR | 4.000 EUR | 2.000 EUR |
| C4 | Gestion de procesos | Emprendimiento + ServiciosConecta + JarabaLex | 6.000 EUR | 3.000 EUR | 2.000 EUR |
| C5 | Factura electronica | ServiciosConecta + JarabaLex (VeriFactu) | 1.000 EUR | 1.000 EUR | 1.000 EUR |
| C6 | Business Intelligence y Analitica | FOC + Analytics todos verticales | 4.000 EUR | 4.000 EUR | 1.500 EUR |
| C7 | Comunicaciones seguras | Buzon Confianza (ServiciosConecta, JarabaLex) | 6.000 EUR | 6.000 EUR | 6.000 EUR |
| C8 | Marketplace | AgroConecta + ComercioConecta | 2.000 EUR | 2.000 EUR | 2.000 EUR |
| C9 | Gestion de redes sociales | jaraba_social addon | 2.500 EUR | 2.500 EUR | 2.500 EUR |

**Bono maximo combinable por segmento:**
- Segmento I (10-49 empleados): hasta 12.000 EUR
- Segmento II (3-9 empleados): hasta 6.000 EUR
- Segmento III (0-2 empleados): hasta 2.000-3.000 EUR
- Segmento IV (50-99): hasta 25.000 EUR
- Segmento V (100-249): hasta 29.000 EUR

---

## 2. PAQUETES VERTICALES KIT DIGITAL

### 2.1 Paquete "Comercio Digital" (ComercioConecta)

**Target:** Comercios minoristas de proximidad, tiendas de barrio, boutiques.
**Categorias Kit Digital:** C1 (Comercio electronico) + C2 (Sitio web) + C3 (CRM) + C6 (BI)
**Bono maximo aplicable:** Hasta 12.000 EUR (Seg.I)

**Funcionalidades incluidas:**
- Tienda online con catalogo de productos (ComercioConecta Starter/Pro)
- Sitio web profesional responsive con Page Builder GrapesJS
- QR dinamicos para productos y ofertas flash
- Local SEO automatizado (Google Business Profile sync)
- CRM basico de clientes (jaraba_crm addon)
- Dashboard de analitica de ventas (FOC)
- Formacion inicial incluida (2h onboarding + documentacion)

**Requisitos minimos Kit Digital que cumple:**
- Plataforma de venta con carrito y checkout
- Catalogo de productos con fichas
- Metodos de pago online (Stripe)
- Responsive / mobile-first
- Posicionamiento basico en internet (SEO)
- Autoregistro / auto-gestion por el beneficiario
- Dominio propio incluido (subdominio tenant)

**Precio regular:** 39-99 EUR/mes (Starter-Pro)
**Duracion Kit Digital:** 12 meses de servicio (requisito minimo segun categoria)

### 2.2 Paquete "Productor Digital" (AgroConecta)

**Target:** Productores agroalimentarios, cooperativas, fincas ecologicas.
**Categorias Kit Digital:** C1 (Comercio electronico) + C8 (Marketplace) + C6 (BI) + C2 (Sitio web)
**Bono maximo aplicable:** Hasta 10.000 EUR (Seg.I)

**Funcionalidades incluidas:**
- Tienda propia del productor con branding personalizado
- Catalogo con trazabilidad QR a nivel de lote
- Fichas de producto con storytelling del productor (video, historia, metodo)
- Certificaciones DOP/IGP/Eco gestionables
- Integracion shipping (MRW/SEUR/Correos)
- Dashboard productor con analitica de ventas
- Alta en marketplace AgroConecta cross-productor

**Precio regular:** 49-129 EUR/mes (Starter-Pro)
**Duracion Kit Digital:** 12 meses

### 2.3 Paquete "Profesional Digital" (ServiciosConecta)

**Target:** Consultores, coaches, terapeutas, asesores, autonomos de servicios.
**Categorias Kit Digital:** C4 (Gestion de procesos) + C5 (Factura electronica) + C3 (CRM) + C7 (Comunicaciones seguras)
**Bono maximo aplicable:** Hasta 17.000 EUR (Seg.I)

**Funcionalidades incluidas:**
- Sistema de reservas/booking online con calendario sync
- Facturacion electronica VeriFactu compliant
- Firma digital PAdES integrada
- Buzon de Confianza (documentos cifrados con cliente)
- Portal documental del cliente
- CRM con pipeline de presupuestos
- Presupuestador automatico

**Precio regular:** 29-79 EUR/mes (Starter-Pro)
**Duracion Kit Digital:** 12 meses

### 2.4 Paquete "Despacho Digital" (JarabaLex)

**Target:** Abogados individuales, despachos pequenos, asesorias juridicas rurales.
**Categorias Kit Digital:** C4 (Gestion de procesos) + C5 (Factura electronica) + C7 (Comunicaciones seguras) + C2 (Sitio web)
**Bono maximo aplicable:** Hasta 15.000 EUR (Seg.I)

**Funcionalidades incluidas:**
- Gestion de expedientes con integracion LexNet
- Calendario judicial sincronizado
- Facturacion legal con time tracking
- Firma digital PAdES para documentos legales
- Buzon de Confianza cifrado para comunicacion con clientes
- IA Copilot legal (LCIS 9 capas) con compliance EU AI Act
- Base de conocimiento normativo integrada
- Sitio web profesional del despacho

**Precio regular:** 39-149 EUR/mes
**Duracion Kit Digital:** 12 meses

### 2.5 Paquete "Emprendedor Digital" (Emprendimiento)

**Target:** Emprendedores, nuevos autonomos, startups, programas de emprendimiento.
**Categorias Kit Digital:** C4 (Gestion de procesos) + C6 (BI) + C2 (Sitio web) + C3 (CRM)
**Bono maximo aplicable:** Hasta 14.000 EUR (Seg.I)

**Funcionalidades incluidas:**
- Diagnostico de madurez digital (lead magnet gratuito → conversion)
- Business Model Canvas con IA
- 44 experimentos de validacion Lean Startup (Osterwalder)
- AI Business Copilot v2 (5 modos: learn/build/coach/mentor/market)
- Proyecciones financieras
- Sitio web profesional del nuevo negocio
- CRM para pipeline de clientes/inversores

**Precio regular:** 39-99 EUR/mes (Starter-Pro)
**Duracion Kit Digital:** 12 meses

---

## 3. IMPLEMENTACION TECNICA

### 3.1 Web dedicada Kit Digital (OBLIGATORIA para adhesion)

**URL:** `https://plataformadeecosistemas.com/kit-digital`

**Contenido minimo exigido (Anexo II del Anuncio de Adhesion):**

```
/kit-digital/                    → Landing principal Kit Digital
/kit-digital/comercio-digital    → Paquete ComercioConecta
/kit-digital/productor-digital   → Paquete AgroConecta
/kit-digital/profesional-digital → Paquete ServiciosConecta
/kit-digital/despacho-digital    → Paquete JarabaLex
/kit-digital/emprendedor-digital → Paquete Emprendimiento
```

**Cada pagina de paquete debe incluir (requisito Red.es):**
1. Nombre de la solucion digital
2. Categorias Kit Digital que cubre
3. Segmentos de beneficiarios a los que se dirige (I, II, III)
4. Sectores de actividad compatibles
5. Descripcion funcional de la solucion
6. Requisitos tecnicos (navegador, dispositivo)
7. Precio regular y precio con bono Kit Digital
8. Duracion del servicio (minimo 12 meses)
9. Logos obligatorios: Kit Digital + NextGenerationEU + Plan de Recuperacion + Gobierno de Espana

### 3.2 Entidad Drupal: KitDigitalAgreement

Nueva entidad para gestionar los Acuerdos de Prestacion de Soluciones de Digitalizacion:

```php
/**
 * @ContentEntityType(
 *   id = "kit_digital_agreement",
 *   label = @Translation("Kit Digital Agreement"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\jaraba_billing\Form\KitDigitalAgreementForm",
 *     },
 *     "access" = "Drupal\jaraba_billing\Access\KitDigitalAgreementAccessControlHandler",
 *   },
 *   base_table = "kit_digital_agreement",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class KitDigitalAgreement extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Tenant reference
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE);

    // Beneficiary data
    $fields['beneficiary_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Beneficiary Name'))
      ->setRequired(TRUE);

    $fields['beneficiary_nif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Beneficiary NIF'))
      ->setRequired(TRUE);

    $fields['segmento'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Segmento'))
      ->setSetting('allowed_values', [
        'I' => 'Segmento I (10-49 empleados)',
        'II' => 'Segmento II (3-9 empleados)',
        'III' => 'Segmento III (0-2 empleados)',
        'IV' => 'Segmento IV (50-99 empleados)',
        'V' => 'Segmento V (100-249 empleados)',
      ])
      ->setRequired(TRUE);

    // Bono digital
    $fields['bono_digital_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Bono Digital Amount (EUR)'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2);

    $fields['bono_digital_ref'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Bono Digital Reference'));

    // Package
    $fields['paquete'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Paquete Kit Digital'))
      ->setSetting('allowed_values', [
        'comercio_digital' => 'Comercio Digital (ComercioConecta)',
        'productor_digital' => 'Productor Digital (AgroConecta)',
        'profesional_digital' => 'Profesional Digital (ServiciosConecta)',
        'despacho_digital' => 'Despacho Digital (JarabaLex)',
        'emprendedor_digital' => 'Emprendedor Digital (Emprendimiento)',
      ])
      ->setRequired(TRUE);

    // Categories covered
    $fields['categorias_kit_digital'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Kit Digital Categories'));

    // Vertical plan tier
    $fields['plan_tier'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Plan Tier'))
      ->setSetting('allowed_values', [
        'starter' => 'Starter',
        'pro' => 'Pro',
        'enterprise' => 'Enterprise',
      ]);

    // Stripe subscription linkage
    $fields['stripe_subscription_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Subscription ID'));

    // Duration
    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start Date'))
      ->setRequired(TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End Date'))
      ->setRequired(TRUE);

    // Status
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setSetting('allowed_values', [
        'draft' => 'Borrador',
        'signed' => 'Firmado',
        'active' => 'Activo',
        'justification_pending' => 'Justificacion pendiente',
        'justified' => 'Justificado',
        'paid' => 'Bono cobrado',
        'expired' => 'Expirado',
      ])
      ->setDefaultValue('draft');

    // Justification
    $fields['justification_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Justification Date'));

    $fields['justification_memory'] = BaseFieldDefinition::create('file')
      ->setLabel(t('Memoria Tecnica'));

    // Created/changed
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }
}
```

### 3.3 Servicio: KitDigitalService

```php
<?php

namespace Drupal\jaraba_billing\Service;

/**
 * Manages Kit Digital agreements and bono lifecycle.
 */
class KitDigitalService {

  /**
   * Creates a new Kit Digital agreement for a tenant.
   */
  public function createAgreement(
    int $tenant_id,
    string $paquete,
    string $segmento,
    float $bono_amount,
    string $plan_tier = 'pro'
  ): KitDigitalAgreement;

  /**
   * Maps a paquete to its Kit Digital categories.
   */
  public function getCategoriesForPaquete(string $paquete): array;

  /**
   * Calculates the maximum bono amount for a paquete + segmento.
   */
  public function getMaxBonoAmount(string $paquete, string $segmento): float;

  /**
   * Generates the Memoria Tecnica de Actuacion for justification.
   */
  public function generateJustificationMemory(int $agreement_id): string;

  /**
   * Links the Kit Digital agreement to a Stripe subscription.
   *
   * The bono covers X months of subscription. After bono expires,
   * the tenant transitions to regular Stripe billing.
   */
  public function linkToStripeSubscription(
    int $agreement_id,
    string $stripe_subscription_id
  ): void;

  /**
   * Calculates months of service covered by the bono.
   *
   * Example: Bono 6.000 EUR / Plan Pro 79 EUR/mes = 75 meses
   * But Kit Digital requires minimum 12 months of service.
   */
  public function calculateCoveredMonths(float $bono_amount, float $monthly_price): int;

  /**
   * Returns all active Kit Digital agreements for reporting.
   */
  public function getActiveAgreements(): array;

  /**
   * Checks if a tenant was acquired via Kit Digital bono.
   * Used for Kit Digital Conversion Rate metric in FOC.
   */
  public function isKitDigitalTenant(int $tenant_id): bool;
}
```

### 3.4 Rutas

```yaml
# jaraba_billing.routing.yml (additions)

jaraba_billing.kit_digital.landing:
  path: '/es/kit-digital'
  defaults:
    _controller: '\Drupal\jaraba_billing\Controller\KitDigitalController::landing'
    _title: 'Kit Digital - Soluciones de Digitalizacion'
  requirements:
    _permission: 'access content'

jaraba_billing.kit_digital.paquete:
  path: '/es/kit-digital/{paquete}'
  defaults:
    _controller: '\Drupal\jaraba_billing\Controller\KitDigitalController::paquete'
  requirements:
    _permission: 'access content'
    paquete: 'comercio-digital|productor-digital|profesional-digital|despacho-digital|emprendedor-digital'

jaraba_billing.kit_digital.admin:
  path: '/es/admin/kit-digital'
  defaults:
    _controller: '\Drupal\jaraba_billing\Controller\KitDigitalAdminController::dashboard'
    _title: 'Kit Digital - Gestion de Acuerdos'
  requirements:
    _permission: 'administer kit digital'

jaraba_billing.kit_digital.agreement.create:
  path: '/es/admin/kit-digital/agreement/add'
  defaults:
    _entity_form: 'kit_digital_agreement.default'
    _title: 'Nuevo Acuerdo Kit Digital'
  requirements:
    _permission: 'administer kit digital'
```

### 3.5 Permisos

```yaml
# jaraba_billing.permissions.yml (additions)

administer kit digital:
  title: 'Administer Kit Digital agreements'
  description: 'Create, edit and manage Kit Digital agreements and bono tracking'
  restrict access: true

view kit digital agreements:
  title: 'View Kit Digital agreements'
  description: 'View Kit Digital agreement details and status'
```

### 3.6 Flujo ECA: Alta Kit Digital

**Trigger:** Formulario de solicitud en /es/kit-digital/{paquete}

1. Beneficiario completa formulario con datos empresa (nombre, NIF, segmento, empleados)
2. Sistema valida segmento vs numero de empleados
3. Sistema calcula bono maximo segun paquete + segmento
4. Se genera borrador de Acuerdo de Prestacion de Soluciones de Digitalizacion
5. Se envia email al beneficiario con enlace para firma electronica (PAdES)
6. Tras firma, se crea el tenant y se activa el vertical correspondiente
7. Se vincula a suscripcion Stripe con periodo = meses cubiertos por bono
8. Se registra en FOC como kit_digital_agreement_created
9. Se notifica a admin para seguimiento y justificacion posterior

### 3.7 Metricas FOC especificas

```php
// Metricas a trackear en FOC para Kit Digital
'kit_digital_agreements_total' => count active agreements,
'kit_digital_bono_total_eur' => sum of all bono amounts,
'kit_digital_conversion_rate' => kit_digital_tenants / total_tenants,
'kit_digital_months_covered_avg' => average months of service covered by bono,
'kit_digital_post_bono_retention' => % tenants that continue paying after bono expires,
```

---

## 4. PROCESO DE ADHESION — CHECKLIST

### 4.1 Pre-requisitos (antes de solicitar)

- [ ] Web dedicada Kit Digital creada y publicada en plataformadeecosistemas.com/kit-digital
- [ ] Contenido minimo de cada paquete segun Anexo II
- [ ] Logos obligatorios integrados (Kit Digital, NextGenerationEU, Gobierno Espana)
- [ ] Manual de identidad del digitalizador implementado
- [ ] Certificado digital PED S.L. vigente para firma electronica
- [ ] Documentacion de facturacion PIIL (2 resoluciones SAE) preparada

### 4.2 Solicitud (Sede Electronica Red.es)

- [ ] Formulario online cumplimentado en castellano
- [ ] Seleccion de categorias (C1-C9 listadas arriba)
- [ ] URL de web dedicada por cada categoria
- [ ] Zona geografica: toda Espana (o Andalucia inicialmente)
- [ ] Acreditacion experiencia: resoluciones PIIL como evidencia
- [ ] Firma electronica del representante legal (Jose Jaraba Munoz)

### 4.3 Post-adhesion

- [ ] Inclusion en Catalogo de Agentes Digitalizadores en AceleraPyme
- [ ] Activacion de flujo de captacion de beneficiarios
- [ ] Dashboard admin Kit Digital operativo
- [ ] Formacion equipo en proceso de justificacion

---

## 5. FLUJO DE JUSTIFICACION

Tras 12 meses de prestacion del servicio, PED debe presentar:

1. **Memoria tecnica de actuacion:** Actividades realizadas, resultados obtenidos, numero de beneficiarios atendidos, funcionalidades implementadas.

2. **Memoria economica:** Acreditacion del servicio prestado, cuantia del bono justificada vs concedida.

3. **Pruebas graficas:** Screenshots de las soluciones implementadas, evidencia de uso por el beneficiario.

4. **Declaracion DACI:** Ausencia de conflicto de intereses.

La entidad `KitDigitalAgreement` trackea todo este ciclo con los campos `justification_date`, `justification_memory` y estados `justification_pending` → `justified` → `paid`.

---

## 6. DIRECTRICES PARA CLAUDE CODE

### 6.1 Reglas de implementacion

- **KIT-DIGITAL-001:** Toda pagina /kit-digital/* DEBE incluir logos obligatorios y mencion a NextGenerationEU
- **KIT-DIGITAL-002:** Los precios mostrados en la web Kit Digital DEBEN ser Config Entities editables desde admin (Regla #131 de Doc 158)
- **KIT-DIGITAL-003:** La entidad KitDigitalAgreement pertenece al modulo jaraba_billing (no crear modulo nuevo)
- **KIT-DIGITAL-004:** El flujo de firma del Acuerdo usa jaraba_legal firma PAdES existente
- **KIT-DIGITAL-005:** Las metricas Kit Digital se integran en FOC existente (no dashboard separado)
- **KIT-DIGITAL-006:** ROUTE-LANGPREFIX-001 aplica: todas las rutas con /es/ prefix
- **KIT-DIGITAL-007:** TENANT-001 aplica: cada KitDigitalAgreement tiene tenant_id FK

### 6.2 Patron de implementacion

Seguir el patron Setup Wizard + Daily Actions:
- **Wizard:** Formulario Kit Digital → Datos empresa → Seleccion paquete → Firma → Activacion tenant
- **Daily Actions:** Dashboard admin con acuerdos pendientes de justificacion, bonos proximos a expirar, tenants post-bono sin conversion a pago

---

*Documento 179 v1 generado 18 marzo 2026.*
*Fuentes: Orden ETD/1498/2021 (Bases Reguladoras Kit Digital), Anuncio de Adhesion Red.es, resoluciones PIIL SC/ICV/0156/2023 y SC/ICV/0111/2025, Doc 158 v2 (Pricing Matrix).*
*Este documento es ejecutable por Claude Code sin ambiguedad.*
