PRESUPUESTADOR AUTOMÁTICO
Generación Inteligente de Presupuestos Profesionales
Catálogo de Servicios + Estimación AI + Templates Personalizables
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	92_ServiciosConecta_Presupuestador_Auto
Dependencias:	82_Services_Core, 91_AI_Triaje_Casos
Modelo IA:	Gemini 2.0 Flash con Catálogo Grounded
Prioridad:	ALTA - Conversión de consultas a ventas
 
1. Resumen Ejecutivo
El Presupuestador Automático permite a profesionales generar presupuestos detallados en segundos, combinando un catálogo de servicios predefinido con estimación inteligente basada en la complejidad del caso. El sistema utiliza el resultado del triaje (doc 91) junto con el catálogo de servicios del profesional para sugerir una propuesta económica completa y personalizada.
Este componente cierra el ciclo consulta→presupuesto→contratación. Un abogado que antes tardaba 30 minutos en preparar un presupuesto detallado puede ahora revisar y enviar una propuesta profesional en menos de 2 minutos, aumentando significativamente su tasa de conversión y reduciendo el tiempo de respuesta al cliente.
1.1 El Problema: Presupuestos Lentos e Inconsistentes
Situación Actual	Problema	Consecuencia
Presupuestos manuales	Cada presupuesto se redacta desde cero	30-60 min por presupuesto, baja productividad
Sin catálogo estandarizado	Precios inconsistentes según el día/humor	Clientes comparan y detectan incoherencias
Respuesta lenta	'Te envío el presupuesto mañana'	Cliente contacta a la competencia mientras espera
Sin histórico	No se sabe qué se presupuestó ni aceptó	Imposible analizar pricing ni tasa de conversión
Formato poco profesional	Email con texto plano o Word genérico	Imagen poco profesional, menor valor percibido

1.2 La Solución: Presupuestador Inteligente
•	Catálogo de servicios: Base de datos de servicios con precios, rangos y condiciones
•	Estimación AI: Sugiere servicios y estima complejidad basándose en el triaje del caso
•	Múltiples modalidades: Precio fijo, por horas, success fee, mixto, suscripción
•	Templates personalizables: Diseño profesional con branding del despacho
•	Envío y seguimiento: Email/WhatsApp con link de visualización y aceptación online
•	Conversión a contrato: Aceptación genera contrato de servicios para firma digital
•	Analytics: Métricas de conversión, tiempo de respuesta, servicios más vendidos
1.3 Flujo de Generación de Presupuesto
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Consulta   │────►│   Triaje    │────►│ Presupuesto │────►│  Contrato   │
│  (doc 91)   │     │     AI      │     │     AI      │     │   + Firma   │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
                                              │                           
                    ┌─────────────────────────┴─────────────────────────┐ 
                    │                                                   │ 
                    ▼                                                   ▼ 
             ┌─────────────┐                                  ┌─────────────┐
             │  Profesional│                                  │   Cliente   │
             │   Revisa    │                                  │   Recibe    │
             │   Ajusta    │                                  │   Acepta    │
             │   Envía     │                                  │   o Negocia │
             └─────────────┘                                  └─────────────┘
 
2. Modelo de Datos
2.1 Entidad: service_catalog_item (Catálogo de Servicios)
Cada servicio que el profesional puede ofrecer con sus modalidades de precio.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
provider_id	INT	Profesional propietario	FK provider_profile.id, NOT NULL
tenant_id	INT	Tenant	FK tenant.id, NOT NULL, INDEX
category_tid	INT	Categoría del servicio	FK taxonomy_term.tid, NOT NULL
name	VARCHAR(255)	Nombre del servicio	NOT NULL
description	TEXT	Descripción detallada	NOT NULL
short_description	VARCHAR(500)	Descripción para presupuesto	NOT NULL
pricing_model	VARCHAR(24)	Modelo de precio	ENUM: fixed|hourly|range|success_fee|subscription
base_price	DECIMAL(10,2)	Precio base	NOT NULL
price_min	DECIMAL(10,2)	Precio mínimo (rango)	NULLABLE
price_max	DECIMAL(10,2)	Precio máximo (rango)	NULLABLE
hourly_rate	DECIMAL(10,2)	Tarifa por hora	NULLABLE
estimated_hours_min	INT	Horas mínimas estimadas	NULLABLE
estimated_hours_max	INT	Horas máximas estimadas	NULLABLE
success_fee_percent	DECIMAL(5,2)	% de éxito (success fee)	NULLABLE
includes	JSON	Qué incluye el servicio	["Estudio del caso", "Reunión inicial"]
excludes	JSON	Qué NO incluye	["Tasas judiciales", "Peritos"]
complexity_factors	JSON	Factores de complejidad	Ver estructura abajo
is_active	BOOLEAN	Activo en catálogo	DEFAULT TRUE
display_order	INT	Orden de visualización	DEFAULT 0

Estructura JSON: complexity_factors
{
  "factors": [
    {
      "name": "Cuantía económica",
      "type": "range",
      "options": [
        { "label": "< 10.000€", "multiplier": 1.0 },
        { "label": "10.000€ - 50.000€", "multiplier": 1.3 },
        { "label": "50.000€ - 150.000€", "multiplier": 1.6 },
        { "label": "> 150.000€", "multiplier": 2.0 }
      ]
    },
    {
      "name": "Número de partes",
      "type": "count",
      "options": [
        { "label": "2 partes", "multiplier": 1.0 },
        { "label": "3-4 partes", "multiplier": 1.2 },
        { "label": "> 4 partes", "multiplier": 1.5 }
      ]
    },
    {
      "name": "Urgencia",
      "type": "boolean",
      "options": [
        { "label": "Normal", "multiplier": 1.0 },
        { "label": "Urgente (+25%)", "multiplier": 1.25 }
      ]
    }
  ]
}

 
2.2 Entidad: quote (Presupuesto)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
quote_number	VARCHAR(32)	Número de presupuesto	UNIQUE per tenant (PRES-2026-0001)
provider_id	INT	Profesional que presupuesta	FK provider_profile.id, NOT NULL
tenant_id	INT	Tenant	FK tenant.id, NOT NULL, INDEX
inquiry_id	INT	Consulta origen (si existe)	FK client_inquiry.id, NULLABLE
client_name	VARCHAR(255)	Nombre del cliente	NOT NULL
client_email	VARCHAR(255)	Email del cliente	NOT NULL
client_phone	VARCHAR(24)	Teléfono	NULLABLE
client_company	VARCHAR(255)	Empresa (si B2B)	NULLABLE
client_nif	VARCHAR(20)	NIF/CIF	NULLABLE
title	VARCHAR(255)	Título del presupuesto	NOT NULL
introduction	TEXT	Texto introductorio	NULLABLE
subtotal	DECIMAL(10,2)	Subtotal sin IVA	NOT NULL
discount_percent	DECIMAL(5,2)	Descuento %	DEFAULT 0
discount_amount	DECIMAL(10,2)	Descuento €	DEFAULT 0
tax_rate	DECIMAL(5,2)	% IVA	DEFAULT 21.00
tax_amount	DECIMAL(10,2)	Importe IVA	NOT NULL
total	DECIMAL(10,2)	Total con IVA	NOT NULL
currency	VARCHAR(3)	Moneda	DEFAULT 'EUR'
valid_until	DATE	Válido hasta	NOT NULL (default +30 días)
payment_terms	TEXT	Condiciones de pago	NULLABLE
notes	TEXT	Notas adicionales	NULLABLE
access_token	VARCHAR(64)	Token para ver/aceptar	UNIQUE, NOT NULL
status	VARCHAR(16)	Estado	ENUM: draft|sent|viewed|accepted|rejected|expired
sent_at	DATETIME	Fecha de envío	NULLABLE
viewed_at	DATETIME	Primera visualización	NULLABLE
responded_at	DATETIME	Fecha respuesta cliente	NULLABLE
rejection_reason	TEXT	Motivo de rechazo	NULLABLE
converted_to_case_id	INT	Expediente generado	FK client_case.id, NULLABLE
ai_generated	BOOLEAN	Generado por AI	DEFAULT FALSE
created	DATETIME	Fecha creación	NOT NULL

 
2.3 Entidad: quote_line_item (Líneas del Presupuesto)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
quote_id	INT	Presupuesto padre	FK quote.id, NOT NULL, INDEX
catalog_item_id	INT	Servicio del catálogo	FK service_catalog_item.id, NULLABLE
line_order	INT	Orden en el presupuesto	NOT NULL
description	TEXT	Descripción del concepto	NOT NULL
quantity	DECIMAL(10,2)	Cantidad (o horas)	DEFAULT 1
unit	VARCHAR(24)	Unidad	ENUM: unit|hour|session|month|project
unit_price	DECIMAL(10,2)	Precio unitario	NOT NULL
complexity_multiplier	DECIMAL(3,2)	Multiplicador complejidad	DEFAULT 1.00
complexity_factors_applied	JSON	Factores aplicados	[{factor, option, multiplier}]
line_total	DECIMAL(10,2)	Total de línea	NOT NULL (qty * price * multiplier)
is_optional	BOOLEAN	Concepto opcional	DEFAULT FALSE
notes	TEXT	Notas de la línea	NULLABLE

3. Servicio de Estimación AI
3.1 QuoteEstimatorService
<?php namespace Drupal\jaraba_quotes\Service;

class QuoteEstimatorService {
  
  public function generateEstimate(
    InquiryTriage $triage,
    ProviderProfile $provider
  ): QuoteEstimate {
    // 1. Cargar catálogo del profesional relevante a la categoría
    $catalogItems = $this->getCatalogForCategory(
      $provider->id(),
      $triage->getCategoryTid()
    );
    
    // 2. Construir contexto para el AI
    $context = $this->buildContext($triage, $catalogItems);
    
    // 3. Llamar a Gemini para estimar
    $response = $this->gemini->generateContent(
      model: 'gemini-2.0-flash-001',
      contents: $this->buildEstimationPrompt($context),
      generationConfig: [
        'responseMimeType' => 'application/json',
        'responseSchema' => $this->getEstimateSchema(),
        'temperature' => 0.2,
      ]
    );
    
    // 4. Parsear y validar contra catálogo real
    $estimate = json_decode($response->getText(), true);
    
    return $this->buildQuoteEstimate($estimate, $catalogItems);
  }
  
  private function buildEstimationPrompt(EstimationContext $context): string {
    return <<<PROMPT
Eres un asistente de presupuestación para profesionales.

## INSTRUCCIONES
1. SOLO puedes sugerir servicios del CATÁLOGO proporcionado abajo
2. Estima la complejidad basándote en la información del triaje
3. Selecciona los factores de complejidad apropiados de cada servicio
4. NO inventes servicios ni precios - usa SOLO el catálogo

## CATÁLOGO DE SERVICIOS DISPONIBLES
{$this->formatCatalog($context->catalogItems)}

## INFORMACIÓN DEL CASO (del triaje)
Categoría: {$context->triage->getCategory()}
Subcategoría: {$context->triage->getSubcategory()}
Urgencia: {$context->triage->getUrgencyScore()}/5
Resumen: {$context->triage->getSummary()}

Entidades extraídas:
{$this->formatEntities($context->triage->getExtractedEntities())}

## TAREA
Sugiere qué servicios del catálogo aplicar y con qué factores de complejidad.
Devuelve el resultado en formato JSON.
PROMPT;
  }
}

 
4. APIs REST
4.1 APIs de Catálogo de Servicios
Método	Endpoint	Descripción	Auth
POST	/api/v1/catalog/services	Crear servicio en catálogo	Provider
GET	/api/v1/catalog/services	Listar servicios del catálogo	Provider
GET	/api/v1/catalog/services/{uuid}	Detalle de servicio	Provider
PATCH	/api/v1/catalog/services/{uuid}	Actualizar servicio	Provider
DELETE	/api/v1/catalog/services/{uuid}	Desactivar servicio	Provider
POST	/api/v1/catalog/services/import	Importar catálogo desde Excel	Provider

4.2 APIs de Presupuestos
Método	Endpoint	Descripción	Auth
POST	/api/v1/quotes	Crear presupuesto manual	Provider
POST	/api/v1/quotes/generate	Generar presupuesto con AI desde triaje	Provider
GET	/api/v1/quotes	Listar presupuestos con filtros	Provider
GET	/api/v1/quotes/{uuid}	Detalle de presupuesto	Provider
PATCH	/api/v1/quotes/{uuid}	Actualizar presupuesto (borrador)	Provider
POST	/api/v1/quotes/{uuid}/lines	Añadir línea al presupuesto	Provider
PATCH	/api/v1/quotes/{uuid}/lines/{id}	Modificar línea	Provider
DELETE	/api/v1/quotes/{uuid}/lines/{id}	Eliminar línea	Provider
POST	/api/v1/quotes/{uuid}/send	Enviar presupuesto al cliente	Provider
POST	/api/v1/quotes/{uuid}/duplicate	Duplicar presupuesto	Provider
GET	/api/v1/quotes/{uuid}/pdf	Descargar PDF del presupuesto	Provider

4.3 APIs para Cliente (Portal Presupuesto)
Método	Endpoint	Descripción	Auth
GET	/api/v1/quotes/view/{token}	Ver presupuesto (cliente)	Token
POST	/api/v1/quotes/view/{token}/accept	Aceptar presupuesto	Token
POST	/api/v1/quotes/view/{token}/reject	Rechazar con motivo	Token
POST	/api/v1/quotes/view/{token}/negotiate	Solicitar negociación	Token
GET	/api/v1/quotes/view/{token}/pdf	Descargar PDF	Token

 
5. Flujos de Automatización (ECA)
Código	Evento	Acciones
QUO-001	inquiry.triaged	Generar estimación AI automáticamente y guardarla en borrador
QUO-002	quote.sent	Email + WhatsApp al cliente con link al presupuesto + registrar envío
QUO-003	quote.viewed	Notificar al profesional que el cliente abrió el presupuesto
QUO-004	quote.accepted	Crear expediente + generar contrato para firma + notificar profesional
QUO-005	quote.rejected	Notificar profesional + registrar motivo para analytics
QUO-006	quote.negotiation_requested	Notificar profesional + crear tarea de seguimiento
QUO-007	quote.expiring (3 días)	Recordatorio al cliente + notificación al profesional
QUO-008	quote.expired	Marcar como expirado + notificar profesional
QUO-009	cron.weekly	Generar reporte de conversión: enviados vs aceptados vs rechazados

6. Métricas y Analytics
Métrica	Objetivo	Cálculo
Tasa de conversión	> 40%	% presupuestos aceptados / enviados
Tiempo hasta envío	< 4 horas	Tiempo desde consulta hasta envío de presupuesto
Tiempo de respuesta cliente	< 48 horas	Tiempo desde envío hasta aceptación/rechazo
Valor medio por presupuesto	Tracking	Media de total de presupuestos aceptados
Accuracy de estimación AI	> 80%	% donde profesional no modifica significativamente
Motivos de rechazo	Tracking	Distribución de razones (precio, timing, otro)

7. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 8.1	Semana 21	Entidades service_catalog_item + quote + quote_line_item	91_AI_Triaje
Sprint 8.2	Semana 22	CRUD catálogo + APIs presupuestos + QuoteService	Sprint 8.1
Sprint 8.3	Semana 23	QuoteEstimatorService (AI) + integración con triaje	Sprint 8.2
Sprint 8.4	Semana 24	Portal cliente + PDF generator + ECA flows + analytics	Sprint 8.3

7.1 Criterios de Aceptación
•	✓ Profesional puede mantener catálogo de servicios con precios y complejidades
•	✓ AI genera estimación basada en triaje usando solo el catálogo (strict grounding)
•	✓ Presupuesto editable antes de enviar al cliente
•	✓ Cliente recibe link, puede ver/aceptar/rechazar/negociar
•	✓ Aceptación genera expediente automáticamente
•	✓ PDF profesional con branding del despacho
•	✓ Dashboard con métricas de conversión y análisis de rechazos

--- Fin del Documento ---
