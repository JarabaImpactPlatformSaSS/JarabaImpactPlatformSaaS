
FUNDING INTELLIGENCE MODULE
Sistema Unificado de Subvenciones y Normativa
BDNS + BOJA + BOE + Matching IA + Alertas Personalizadas
JARABA IMPACT PLATFORM
Especificación Técnica Completa - Ready for Development
Versión:	1.0
Fecha:	Febrero 2026
Estado:	Ready for Development
Código:	179_Platform_Funding_Intelligence_Module
Dependencias:	178_Legal_Knowledge, 44_AI_Business_Copilot, 25_Business_Diagnostic
Horas Estimadas:	520-680 horas
Inversión:	€23,400-30,600 @ €45/h
 
1. Resumen Ejecutivo
El Funding Intelligence Module es un sistema integral que unifica el acceso a subvenciones públicas (BDNS), normativa estatal (BOE) y autonómica (BOJA), proporcionando alertas inteligentes personalizadas y matching IA con el perfil del negocio.
1.1 Problema que Resuelve
• Fragmentación: Subvenciones dispersas en múltiples portales (Estado, CCAA, Local)
• Complejidad: Lenguaje administrativo inaccesible para emprendedores
• Pérdida de oportunidades: Plazos que vencen sin conocimiento
• Coste elevado: Gestorías cobran €500-2000 por expediente de subvención
• Sin matching: Los usuarios no saben qué ayudas les corresponden
1.2 Solución Jaraba
• Agregación automática de BDNS + BOJA + BOE en tiempo real
• Matching IA con perfil del negocio (integrado con diagnóstico empresarial)
• Alertas personalizadas por email/push/SMS
• Copilot de Subvenciones conversacional con RAG
• Calculadora de elegibilidad automática
• Calendario de plazos integrado con recordatorios
1.3 Fuentes de Datos
Fuente	Tipo	API	Cobertura	Auth
BDNS	Subvenciones	REST JSON	Todas AAPP desde 2016	Pública
BOJA	Normativa Andalucía	REST JSON/RDF	Desde 1979	Pública
BOE	Normativa Estatal	REST JSON	Consolidada	Pública
1.4 Modelo de Monetización
Tier	Funcionalidades	Precio
Básico	Búsqueda manual, Vista convocatorias	€0 (incluido)
Premium	Alertas personalizadas + Matching IA + Calendario	+€19/mes
Enterprise	Copilot + Documentación asistida + Prioridad soporte	+€49/mes
1.5 Diferenciación Competitiva
Competidor	Modelo	Precio	IA Matching	Alertas
BDNS Oficial	Portal público	Gratis	No	Email básico
Ayming	Consultoría	€2000+/exp	No	Manual
Infosubvenciones	Directorio	€99/año	No	Email
JARABA	SaaS + IA	€19-49/mes	SÍ	Multi-canal
 
2. Arquitectura del Sistema
2.1 Diagrama de Alto Nivel
┌─────────────────────────────────────────────────────────────────┐
│                   FUNDING INTELLIGENCE MODULE                   │
├─────────────────────────────────────────────────────────────────┤
│   FUENTES EXTERNAS              CAPA DE INGESTION              │
│   ┌─────────┐                   ┌─────────────────┐            │
│   │  BDNS   │──────────────────▶│ BdnsApiClient   │            │
│   └─────────┘                   └────────┬────────┘            │
│   ┌─────────┐                   ┌────────▼────────┐            │
│   │  BOJA   │──────────────────▶│ BojaApiClient   │            │
│   └─────────┘                   └────────┬────────┘            │
│   ┌─────────┐                   ┌────────▼────────┐            │
│   │  BOE    │──────────────────▶│ (178_Legal)     │            │
│   └─────────┘                   └────────┬────────┘            │
│                                          ▼                     │
│                              ┌─────────────────────┐           │
│                              │  UNIFIED INGESTION  │           │
│                              │     SERVICE         │           │
│                              └──────────┬──────────┘           │
│   ┌─────────────────────────────────────┼──────────────────┐  │
│   │                    CAPA DE DATOS    │                  │  │
│   │   ┌─────────────┐    ┌─────────────▼─────────────┐    │  │
│   │   │   MySQL     │    │        Qdrant             │    │  │
│   │   │  Entities   │    │   Vector Embeddings       │    │  │
│   │   └─────────────┘    └───────────────────────────┘    │  │
│   └────────────────────────────────────────────────────────┘  │
│   ┌─────────────────────────────────────────────────────────┐ │
│   │                 CAPA DE INTELIGENCIA                    │ │
│   │   ┌─────────────┐  ┌──────────────┐  ┌──────────────┐  │ │
│   │   │  Matching   │  │   Alert      │  │   Copilot    │  │ │
│   │   │   Engine    │  │   Engine     │  │   Service    │  │ │
│   │   └─────────────┘  └──────────────┘  └──────────────┘  │ │
│   └─────────────────────────────────────────────────────────┘ │
│   ┌─────────────────────────────────────────────────────────┐ │
│   │                    CAPA DE USUARIO                      │ │
│   │   ┌─────────────┐  ┌──────────────┐  ┌──────────────┐  │ │
│   │   │  Dashboard  │  │   Calendar   │  │   Alerts     │  │ │
│   │   │   React     │  │   Widget     │  │   Panel      │  │ │
│   │   └─────────────┘  └──────────────┘  └──────────────┘  │ │
│   └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
2.2 Stack Tecnológico
Componente	Tecnología	Justificación
Backend	Drupal 11 + PHP 8.3	Consistencia ecosistema
API Clients	Guzzle HTTP	Async, retry, cache
Vector DB	Qdrant Cloud	Semantic search
Embeddings	text-embedding-3-small	Balance coste/calidad
AI Generation	Claude 3.5 Sonnet	Copilot y summarization
Cache	Redis	TTL por tipo de dato
Queue	Drupal Queue + Redis	Procesamiento async
Frontend	React 18	Dashboard interactivo
Notifications	SendGrid + FCM	Email + Push
2.3 Estructura del Módulo Drupal
modules/custom/jaraba_funding/
├── jaraba_funding.info.yml
├── jaraba_funding.module
├── jaraba_funding.install
├── jaraba_funding.routing.yml
├── jaraba_funding.services.yml
├── jaraba_funding.permissions.yml
├── config/
│   ├── install/
│   │   ├── jaraba_funding.settings.yml
│   │   └── jaraba_funding.sources.yml
│   └── eca/
│       ├── eca.model.funding_daily_sync.yml
│       ├── eca.model.funding_alert_dispatch.yml
│       └── eca.model.funding_deadline_reminder.yml
├── src/
│   ├── Entity/
│   │   ├── FundingCall.php
│   │   ├── FundingSubscription.php
│   │   ├── FundingMatch.php
│   │   └── FundingAlert.php
│   ├── Service/
│   │   ├── Api/
│   │   │   ├── BdnsApiClient.php
│   │   │   └── BojaApiClient.php
│   │   ├── Ingestion/
│   │   │   ├── FundingIngestionService.php
│   │   │   └── FundingNormalizerService.php
│   │   ├── Intelligence/
│   │   │   ├── FundingMatchingEngine.php
│   │   │   ├── FundingEligibilityCalculator.php
│   │   │   └── FundingCopilotService.php
│   │   └── Alerts/
│   │       ├── FundingAlertService.php
│   │       └── FundingNotificationDispatcher.php
│   ├── Controller/
│   │   ├── FundingDashboardController.php
│   │   └── FundingCopilotController.php
│   └── Plugin/
│       ├── rest/resource/
│       │   ├── FundingCallsResource.php
│       │   └── FundingMatchesResource.php
│       └── QueueWorker/
│           ├── FundingIngestionWorker.php
│           └── FundingAlertWorker.php
├── js/components/
│   ├── FundingDashboard.jsx
│   ├── FundingSearch.jsx
│   ├── FundingCalendar.jsx
│   └── FundingCopilot.jsx
└── tests/
    └── src/Unit/
        ├── BdnsApiClientTest.php
        └── MatchingEngineTest.php
 
3. Entidades Drupal
3.1 Entidad: FundingCall (Convocatoria)
Representa una convocatoria de subvención o ayuda pública de cualquier fuente.
3.1.1 Campos Base
Campo	Tipo	Descripción	Requerido
external_id	string(64)	ID en fuente origen (BDNS-xxx, BOJA-xxx)	Sí
source	list_string	bdns|boja|boe|bocm|dogc	Sí
title	string(500)	Título de la convocatoria	Sí
summary	text_long	Resumen generado por IA	No
description	text_long	Descripción completa	No
granting_body	string(255)	Órgano concedente	Sí
admin_level	list_string	state|regional|local|european	Sí
region	list_string	CCAA o 'all' para nacional	No
3.1.2 Campos de Beneficiarios y Sectores
Campo	Tipo	Descripción
beneficiary_types	string_long	JSON: autonomo, pyme, micropyme, gran_empresa...
sectors	string_long	JSON: comercio, hosteleria, agricultura, tecnologia...
keywords	string_long	JSON: palabras clave extraídas para búsqueda
3.1.3 Campos Económicos
Campo	Tipo	Descripción
total_budget	decimal(14,2)	Presupuesto total de la convocatoria
amount_min	decimal(12,2)	Importe mínimo por beneficiario
amount_max	decimal(12,2)	Importe máximo por beneficiario
aid_type	list_string	subvencion|prestamo|garantia|bonificacion|mixto
intensity_max	integer	% máximo de costes elegibles
is_minimis	boolean	¿Es ayuda de minimis?
eu_fund	list_string	none|feder|fse|feader|femp|prtr
3.1.4 Campos Temporales
Campo	Tipo	Descripción
publication_date	datetime	Fecha de publicación
opening_date	datetime	Fecha de apertura de solicitudes
deadline	datetime	Fecha límite de solicitud
status	list_string	upcoming|open|closed|resolved
3.1.5 Campos de Integración
Campo	Tipo	Descripción
official_url	uri	URL oficial de la convocatoria
documents	string_long	JSON: URLs de documentos (bases, extracto...)
qdrant_point_id	string(64)	ID del vector en Qdrant
last_synced	timestamp	Última sincronización
3.2 Entidad: FundingSubscription (Suscripción Alertas)
Configuración de alertas personalizadas por usuario.
Campo	Tipo	Descripción
user_id	entity_reference	Usuario propietario
tenant_id	entity_reference	Tenant (Group)
name	string(128)	Nombre de la suscripción
filter_regions	string_long	JSON: CCAA filtradas
filter_sectors	string_long	JSON: sectores filtrados
filter_beneficiaries	string_long	JSON: tipos beneficiario
filter_keywords	string_long	JSON: palabras clave
filter_amount_min	decimal(12,2)	Importe mínimo
use_profile_matching	boolean	Usar matching IA con perfil
min_match_score	integer	Score mínimo (0-100)
notify_email	boolean	Notificar por email
notify_push	boolean	Notificar por push
frequency	list_string	immediate|daily|weekly
is_active	boolean	Suscripción activa
3.3 Entidad: FundingMatch (Resultado Matching)
Resultado del matching entre convocatoria y perfil de usuario.
Campo	Tipo	Descripción
user_id	entity_reference	Usuario
tenant_id	entity_reference	Tenant
funding_call_id	entity_reference	Convocatoria
match_score	integer	Score total (0-100)
score_breakdown	string_long	JSON: desglose por criterio
eligibility_status	list_string	eligible|likely_eligible|needs_review|not_eligible
eligibility_notes	string_long	JSON: razones elegibilidad
estimated_amount	decimal(12,2)	Importe estimado
user_interest	list_string	not_set|interested|not_interested|applied|dismissed
notified	boolean	Ya notificado
 
4. Servicios de API
4.1 BdnsApiClient
Cliente para la API REST del Sistema Nacional de Publicidad de Subvenciones (SNPSAP/BDNS).
4.1.1 Endpoints Consumidos
Endpoint	Método	Descripción
/GE/es/convocatorias	GET	Búsqueda de convocatorias con filtros
/GE/es/convocatoria/{code}	GET	Detalle de convocatoria por código BDNS
/GE/es/concesiones	GET	Concesiones otorgadas
4.1.2 Parámetros de Búsqueda
Parámetro	Tipo	Descripción
fechaDesde	date	Fecha inicio búsqueda
fechaHasta	date	Fecha fin búsqueda
ccaa	string	Código CCAA (01=Andalucía, 13=Madrid...)
estado	string	A=Abierta, C=Cerrada, R=Resuelta
texto	string	Búsqueda de texto libre
organo	string	Filtro por órgano concedente
page	integer	Página de resultados
size	integer	Tamaño de página (max 50)
4.1.3 Mapeo de Códigos CCAA
Código	CCAA	Código	CCAA
01	Andalucía	10	Comunidad Valenciana
02	Aragón	11	Extremadura
03	Asturias	12	Galicia
04	Islas Baleares	13	Madrid
05	Canarias	14	Murcia
06	Cantabria	15	Navarra
07	Castilla y León	16	País Vasco
08	Castilla-La Mancha	17	La Rioja
09	Cataluña	18/19	Ceuta/Melilla
4.2 BojaApiClient
Cliente para la API de Datos Abiertos de la Junta de Andalucía.
4.2.1 Endpoint Base
Base URL: https://www.juntadeandalucia.es/ssdigitales/datasets/contentapi

GET /search/boja.json
  ?q=data.t_year:2026 AND data.t_sectionN1:1
  &_source=data.t_year,data.t_asumarioNoHtml,data.t_organisation...
  &sort=data.d_dateUTC:desc
  &size=50
4.2.2 Campos Disponibles
Campo API	Descripción	Uso
data.t_year	Año publicación	Filtro temporal
data.t_sectionN1	Sección (1=Disposiciones)	Filtro tipo
data.t_asumarioNoHtml	Título/Sumario	Título convocatoria
data.t_organisation	Organismo	Órgano concedente
data.t_bodyNoHtml	Contenido texto	Descripción
data.t_urlPdf	URL del PDF	Documento oficial
data.d_date	Fecha publicación	Ordenación
4.2.3 Filtrado de Subvenciones
El cliente filtra disposiciones que contienen keywords de financiación:
fundingKeywords = [
  'subvenci', 'ayuda', 'convocatoria', 'bases reguladoras',
  'incentivo', 'bonificaci', 'financiaci', 'dotación económica'
]
 
5. Motor de Matching IA
5.1 Algoritmo de Scoring
El matching calcula un score de 0-100 basado en múltiples criterios ponderados:
Criterio	Peso	Descripción
Región	20%	Match geográfico (100 si coincide o es nacional)
Tipo Beneficiario	25%	Match con tipo de empresa del perfil
Sector	20%	Coincidencia de sectores de actividad
Tamaño	15%	Adecuación por empleados y facturación
Semántico	20%	Similaridad vectorial descripción negocio vs convocatoria
5.1.1 Score por Región
calculateRegionScore(call, profile):
  if call.region == 'all':           # Nacional
    return 100
  if call.region == profile.region:  # Match exacto
    return 100
  return 0                            # No aplica
5.1.2 Score por Tipo Beneficiario
calculateBeneficiaryScore(call, profile):
  if call.beneficiary_types is empty:
    return 80  # Sin restricción
  
  if profile.type in call.beneficiary_types:
    return 100  # Match directo
  
  # Inclusiones (pyme incluye micropyme y autonomo)
  inclusions = {
    'pyme': ['micropyme', 'autonomo'],
    'gran_empresa': ['pyme', 'micropyme']
  }
  
  for call_type in call.beneficiary_types:
    if profile.type in inclusions.get(call_type, []):
      return 90  # Match por inclusión
  
  return 20  # Bajo match
5.1.3 Score por Sector
calculateSectorScore(call, profile):
  if call.sectors is empty:
    return 70  # Sin restricción
  
  intersection = call.sectors ∩ profile.sectors
  if intersection:
    ratio = len(intersection) / len(call.sectors)
    return 60 + (40 * ratio)
  
  # Check sectores relacionados
  related = getSectorRelations()
  for ps in profile.sectors:
    if call.sectors ∩ related[ps]:
      return 50
  
  return 10
5.1.4 Score Semántico
calculateSemanticScore(call, profile):
  if not call.qdrant_point_id or not profile.business_description:
    return 50  # Neutral
  
  profile_embedding = qdrant.embed(profile.business_description)
  similarity = qdrant.similarity(call.qdrant_point_id, profile_embedding)
  
  return round(similarity * 100)  # 0.0-1.0 → 0-100
5.2 Cálculo de Elegibilidad
Independiente del score, se evalúan requisitos bloqueantes y advertencias:
5.2.1 Requisitos Bloqueantes
Check	Condición de Bloqueo
Región	Convocatoria regional y perfil en otra CCAA
Tipo Beneficiario	Tipo no incluido en beneficiarios permitidos
Minimis	Límite de 300.000€ en 3 años alcanzado
Cumplimiento	No estar al corriente con Hacienda/SS
5.2.2 Advertencias
Check	Condición de Advertencia
Sector	Sector no explícitamente incluido
Minimis	Más del 80% del límite consumido
Antigüedad	Posible requisito de antigüedad mínima
5.2.3 Estados de Elegibilidad
Estado	Criterio	Color UI
eligible	Sin blockers ni warnings	Verde
likely_eligible	Sin blockers, 1 warning	Verde claro
needs_review	Sin blockers, 2+ warnings	Amarillo
not_eligible	1+ blockers	Rojo
 
6. Copilot de Subvenciones
6.1 Detección de Intenciones
El Copilot detecta la intención del usuario para proporcionar respuestas especializadas:
Intención	Triggers	Acción
search	busca, encuentra, hay, qué ayudas	Búsqueda semántica + filtros
detail	cuéntame, explica, qué es	Detalle de convocatoria específica
eligibility	puedo, soy elegible, requisitos	Verificación de elegibilidad
comparison	compara, diferencia, cuál elijo	Comparativa de convocatorias
documentation	documentos, formulario, cómo pedir	Guía de documentación
deadline	plazo, cuándo, fecha límite	Alertas de plazos
general	(otros)	RAG general sobre subvenciones
6.2 Flujo de Consulta
query(userQuery, context):
  1. intent = detectIntent(userQuery)
  
  2. switch intent.type:
       'search'      → handleSearch(query, context)
       'eligibility' → handleEligibility(query, context)
       'deadline'    → handleDeadline(context)
       'detail'      → handleDetail(intent.entity_id)
       default       → handleGeneral(query, context)
  
  3. return {
       answer: generated_response,
       calls: relevant_funding_calls,
       matches: calculated_matches,
       latency_ms: elapsed_time
     }
6.3 Prompt de Búsqueda
SYSTEM_PROMPT = '''
Eres un asistente experto en subvenciones y ayudas públicas españolas.
Tu rol es ayudar a emprendedores y PYMES a encontrar financiación.

## CONTEXTO DEL USUARIO
Perfil del negocio: {business_profile}

## CONVOCATORIAS ENCONTRADAS
{funding_calls_json}

## INSTRUCCIONES
1. Presenta las convocatorias más relevantes de forma clara y concisa
2. Si hay match_score, menciona el nivel de coincidencia con su perfil
3. Destaca los importes y plazos importantes
4. Sugiere cuál podría ser más interesante para su caso
5. Usa un tono cercano y profesional
6. NO inventes información que no esté en los datos
7. Si una convocatoria tiene plazo cercano (< 15 días), destácalo

## FORMATO
- Respuesta directa, no uses listas extensas
- Máximo 3-4 párrafos
- Incluye emojis relevantes (💰 para importes, ⏰ para plazos)
'''
6.4 Prompt de Elegibilidad
ELIGIBILITY_PROMPT = '''
Analiza la elegibilidad del usuario para las siguientes convocatorias.

## PERFIL DEL NEGOCIO
{business_profile}

## CONVOCATORIAS ELEGIBLES
{eligible_calls}

## INSTRUCCIONES
1. Explica de forma clara por qué el usuario ES o NO ES elegible
2. Si es elegible, destaca los puntos fuertes de su perfil
3. Si hay dudas, indica qué información adicional se necesita
4. Proporciona estimación del importe que podría solicitar
5. Menciona requisitos importantes como:
   - Estar al corriente con Hacienda y Seguridad Social
   - Límites de minimis si aplica
   - Documentación necesaria

## ADVERTENCIA
- NUNCA afirmes elegibilidad con certeza absoluta
- Siempre recomienda verificar con las bases oficiales
- Sugiere consultar con gestoría para casos complejos
'''
 
7. Sistema de Alertas
7.1 Tipos de Alertas
Tipo	Trigger	Urgencia
new_call	Nueva convocatoria que hace match	Normal
deadline_7d	7 días para cierre	Media
deadline_3d	3 días para cierre	Alta
deadline_1d	1 día para cierre	Crítica
status_change	Cambio de estado (abierta→cerrada)	Normal
budget_update	Actualización de presupuesto	Baja
7.2 Canales de Notificación
Canal	Tecnología	Configuración
Email	SendGrid	Plantilla MJML personalizada
Push Web	FCM	Service Worker PWA
Push Móvil	FCM	App nativa (futuro)
SMS	Twilio	Solo tier Enterprise
In-App	Drupal	Badge y panel de notificaciones
7.3 Frecuencias de Envío
Frecuencia	Descripción	Tier
immediate	Envío inmediato por cada match	Premium/Enterprise
daily	Resumen diario a las 09:00	Premium/Enterprise
weekly	Resumen semanal (lunes 09:00)	Básico
7.4 Plantilla de Email
<!-- templates/funding-email-alert.mjml -->
<mjml>
  <mj-head>
    <mj-attributes>
      <mj-all font-family="Arial, sans-serif" />
      <mj-text font-size="14px" color="#333" />
    </mj-attributes>
  </mj-head>
  <mj-body>
    <mj-section>
      <mj-column>
        <mj-image src="{{ logo_url }}" width="150px" />
        <mj-text font-size="20px" font-weight="bold">
          {{ alert_title }}
        </mj-text>
      </mj-column>
    </mj-section>
    
    {% for call in funding_calls %}
    <mj-section>
      <mj-column>
        <mj-text font-size="16px" font-weight="bold">
          {{ call.title }}
        </mj-text>
        <mj-text>
          <strong>Organismo:</strong> {{ call.granting_body }}<br/>
          <strong>Importe máx:</strong> {{ call.amount_max|format_currency }}<br/>
          <strong>Plazo:</strong> {{ call.deadline|format_date }}
          {% if call.days_left <= 7 %}
            <span style="color: red;">⚠️ {{ call.days_left }} días</span>
          {% endif %}
        </mj-text>
        {% if call.match_score %}
        <mj-text>
          <strong>Coincidencia:</strong> {{ call.match_score }}%
        </mj-text>
        {% endif %}
        <mj-button href="{{ call.detail_url }}">
          Ver convocatoria
        </mj-button>
      </mj-column>
    </mj-section>
    {% endfor %}
    
    <mj-section>
      <mj-column>
        <mj-button href="{{ dashboard_url }}" background-color="#1E3A5F">
          Ver todas las subvenciones
        </mj-button>
        <mj-text font-size="12px" color="#666">
          <a href="{{ unsubscribe_url }}">Gestionar alertas</a>
        </mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
 
8. Flujos ECA
8.1 ECA-FUND-001: Sincronización Diaria
# config/eca/eca.model.funding_daily_sync.yml
id: funding_daily_sync
label: 'Sincronización Diaria de Subvenciones'
status: true

events:
  - plugin: 'eca_cron:cron'
    configuration:
      frequency: '0 6 * * *'  # 06:00 diario

actions:
  - plugin: 'eca_service:service_call'
    label: 'Sync BDNS'
    configuration:
      service: 'jaraba_funding.ingestion_service'
      method: 'syncBdns'
      token_name: 'bdns_results'

  - plugin: 'eca_service:service_call'
    label: 'Sync BOJA'
    configuration:
      service: 'jaraba_funding.ingestion_service'
      method: 'syncBoja'
      token_name: 'boja_results'

  - plugin: 'eca_service:service_call'
    label: 'Procesar alertas'
    configuration:
      service: 'jaraba_funding.alert_service'
      method: 'processNewCalls'
      arguments: ['[bdns_results:new_ids]']

  - plugin: 'eca_log:log_message'
    configuration:
      message: 'Funding sync: BDNS=[bdns_results:count], BOJA=[boja_results:count]'
8.2 ECA-FUND-002: Recordatorios de Plazos
# config/eca/eca.model.funding_deadline_reminder.yml
id: funding_deadline_reminder
label: 'Recordatorios de Plazos'
status: true

events:
  - plugin: 'eca_cron:cron'
    configuration:
      frequency: '0 8 * * *'  # 08:00 diario

actions:
  - plugin: 'eca_service:service_call'
    label: 'Procesar deadlines'
    configuration:
      service: 'jaraba_funding.alert_service'
      method: 'processDeadlineAlerts'

  - plugin: 'eca_service:service_call'
    label: 'Actualizar estados'
    configuration:
      service: 'jaraba_funding.deadline_tracker'
      method: 'updateExpiredCalls'
8.3 ECA-FUND-003: Recálculo de Matches
# config/eca/eca.model.funding_match_calculation.yml
id: funding_match_calculation
label: 'Recálculo de Matches'
status: true

events:
  - plugin: 'eca_content:entity_update'
    configuration:
      entity_type: 'business_diagnostic'

conditions:
  - plugin: 'eca_content:entity_field_value_changed'
    configuration:
      field_name: 'business_type'

actions:
  - plugin: 'eca_queue:queue_item'
    configuration:
      queue_name: 'funding_match_recalculation'
      data:
        user_id: '[entity:user_id]'
8.4 ECA-FUND-004: Dispatch de Alertas
# config/eca/eca.model.funding_alert_dispatch.yml
id: funding_alert_dispatch
label: 'Dispatch de Alertas'
status: true

events:
  - plugin: 'eca_content:entity_insert'
    configuration:
      entity_type: 'funding_alert'

conditions:
  - plugin: 'eca_content:entity_field_value'
    configuration:
      field_name: 'status'
      value: 'pending'

actions:
  - plugin: 'eca_queue:queue_item'
    configuration:
      queue_name: 'funding_alert_dispatch'
      data:
        alert_id: '[entity:id]'
 
9. API REST
9.1 Endpoints
Endpoint	Método	Descripción	Auth
/api/v1/funding/calls	GET	Listar convocatorias	Token
/api/v1/funding/calls/{id}	GET	Detalle convocatoria	Token
/api/v1/funding/search	POST	Búsqueda avanzada	Token
/api/v1/funding/matches	GET	Matches del usuario	Token
/api/v1/funding/matches/{id}	PATCH	Actualizar interés	Token
/api/v1/funding/subscriptions	GET/POST	CRUD suscripciones	Token
/api/v1/funding/copilot	POST	Consulta Copilot	Token
/api/v1/funding/stats	GET	Estadísticas dashboard	Token
/api/v1/funding/deadlines	GET	Plazos próximos	Token
9.2 GET /api/v1/funding/calls
9.2.1 Parámetros
Parámetro	Tipo	Default	Descripción
status	string	open	Filtro por estado
region	string	-	Filtro por CCAA
sector	string	-	Filtro por sector
beneficiary	string	-	Filtro tipo beneficiario
min_amount	integer	-	Importe mínimo
search	string	-	Búsqueda texto
page	integer	1	Página
per_page	integer	20	Items por página (max 50)
9.2.2 Respuesta
{
  "data": [
    {
      "id": 123,
      "external_id": "BDNS-789456",
      "title": "Kit Digital 2026 - Segmento I",
      "granting_body": "Red.es",
      "region": "all",
      "amount_min": 1000,
      "amount_max": 6000,
      "deadline": "2026-12-31",
      "status": "open",
      "official_url": "https://...",
      "match_score": 92  // Si autenticado con perfil
    }
  ],
  "meta": {
    "total": 156,
    "page": 1,
    "per_page": 20,
    "total_pages": 8
  }
}
9.3 POST /api/v1/funding/copilot
9.3.1 Request
{
  "query": "¿Hay ayudas para digitalizar mi tienda?",
  "context": {
    "conversation_id": "uuid-optional",
    "include_profile": true
  }
}
9.3.2 Respuesta
{
  "answer": "He encontrado 3 convocatorias relevantes...",
  "calls": [
    {"id": 123, "title": "...", "match_score": 95},
    {"id": 456, "title": "...", "match_score": 87}
  ],
  "intent": {"type": "search"},
  "latency_ms": 1250,
  "conversation_id": "uuid"
}
 
10. Componentes React
10.1 FundingDashboard.jsx
Dashboard principal con tabs para matches, búsqueda, calendario y copilot.
// Estructura principal
const FundingDashboard = ({ userId, tenantId }) => {
  const [matches, setMatches] = useState([]);
  const [urgentDeadlines, setUrgentDeadlines] = useState([]);
  const [activeTab, setActiveTab] = useState('matches');

  return (
    <div className="funding-dashboard">
      {/* Stats Row */}
      <StatsRow
        openCalls={stats.open_calls}
        highMatches={matches.filter(m => m.score >= 80).length}
        urgentCount={urgentDeadlines.length}
        potentialFunding={stats.potential}
      />

      {/* Urgent Alert */}
      {urgentDeadlines.length > 0 && <UrgentAlert deadlines={urgentDeadlines} />}

      {/* Tabs */}
      <Tabs active={activeTab} onChange={setActiveTab}>
        <Tab id="matches" icon="🎯" label="Recomendadas" />
        <Tab id="search" icon="🔍" label="Buscar" />
        <Tab id="calendar" icon="📅" label="Calendario" />
        <Tab id="copilot" icon="🤖" label="Asistente" />
      </Tabs>

      {/* Content */}
      {activeTab === 'matches' && <MatchesGrid matches={matches} />}
      {activeTab === 'search' && <FundingSearch />}
      {activeTab === 'calendar' && <FundingCalendar />}
      {activeTab === 'copilot' && <FundingCopilot />}
    </div>
  );
};
10.2 FundingMatchCard.jsx
Card de convocatoria con score de match y acciones rápidas.
const FundingMatchCard = ({ match, onInterestChange }) => {
  const { funding_call: call, match_score, eligibility_status } = match;
  const daysLeft = call.getDaysUntilDeadline();

  return (
    <div className={`match-card ${eligibility_status}`}>
      <div className="header">
        <h3>{call.title}</h3>
        <MatchBadge score={match_score} />
      </div>

      <div className="body">
        <p><strong>Organismo:</strong> {call.granting_body}</p>
        <p><strong>Importe:</strong> {formatCurrency(call.amount_max)}</p>
        <p className={daysLeft <= 7 ? 'urgent' : ''}>
          <strong>Plazo:</strong> {formatDate(call.deadline)}
          {daysLeft <= 7 && ` (${daysLeft} días)`}
        </p>
      </div>

      <EligibilityBadge status={eligibility_status} />

      <div className="actions">
        <Button onClick={() => onInterestChange('interested')}>
          Me interesa
        </Button>
        <Button variant="secondary" href={call.official_url}>
          Ver convocatoria
        </Button>
      </div>
    </div>
  );
};
10.3 FundingCopilot.jsx
Interfaz conversacional con el asistente de subvenciones.
const FundingCopilot = ({ userId, tenantId }) => {
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);

  const sendMessage = async () => {
    if (!input.trim()) return;

    const userMessage = { role: 'user', content: input };
    setMessages(prev => [...prev, userMessage]);
    setInput('');
    setLoading(true);

    const response = await fetch('/api/v1/funding/copilot', {
      method: 'POST',
      body: JSON.stringify({ query: input })
    });

    const data = await response.json();
    setMessages(prev => [...prev, {
      role: 'assistant',
      content: data.answer,
      calls: data.calls
    }]);
    setLoading(false);
  };

  return (
    <div className="copilot-container">
      <MessageList messages={messages} />
      {loading && <TypingIndicator />}
      <InputBar value={input} onChange={setInput} onSend={sendMessage} />
      <QuickActions suggestions={[
        '¿Qué ayudas hay para mi sector?',
        '¿Cuáles cierran pronto?',
        '¿Soy elegible para Kit Digital?'
      ]} />
    </div>
  );
};
 
11. Tests Automatizados
11.1 Unit Tests
11.1.1 BdnsApiClientTest
// tests/src/Unit/BdnsApiClientTest.php
class BdnsApiClientTest extends UnitTestCase {

  public function testSearchConvocatoriasReturnsArray(): void {
    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->method('request')->willReturn(
      new Response(200, [], json_encode([
        'content' => [['codigoBDNS' => '123', 'titulo' => 'Test']],
        'last' => true
      ]))
    );

    $client = new BdnsApiClient($httpClient, ...);
    $results = $client->searchConvocatorias(['estado' => 'abierta']);

    $this->assertIsArray($results);
    $this->assertCount(1, $results);
    $this->assertEquals('123', $results[0]['external_id']);
  }

  public function testMapsCcaaCodeCorrectly(): void {
    $client = new BdnsApiClient(...);
    $this->assertEquals('andalucia', $client->mapCcaaCode('01'));
    $this->assertEquals('madrid', $client->mapCcaaCode('13'));
  }
}
11.1.2 MatchingEngineTest
// tests/src/Unit/MatchingEngineTest.php
class MatchingEngineTest extends UnitTestCase {

  /**
   * @dataProvider regionScoreProvider
   */
  public function testCalculateRegionScore(
    string $callRegion,
    string $profileRegion,
    int $expectedScore
  ): void {
    $engine = new FundingMatchingEngine(...);
    $score = $engine->calculateRegionScore($callRegion, $profileRegion);
    $this->assertEquals($expectedScore, $score);
  }

  public static function regionScoreProvider(): array {
    return [
      'Nacional para cualquiera' => ['all', 'andalucia', 100],
      'Match exacto' => ['andalucia', 'andalucia', 100],
      'Sin match' => ['madrid', 'andalucia', 0],
    ];
  }

  public function testBeneficiaryScoreWithInclusion(): void {
    $engine = new FundingMatchingEngine(...);
    // PYME incluye autónomo
    $score = $engine->calculateBeneficiaryScore(['pyme'], 'autonomo');
    $this->assertEquals(90, $score);
  }
}
11.2 Kernel Tests
// tests/src/Kernel/FundingIngestionTest.php
class FundingIngestionTest extends KernelTestBase {

  protected static $modules = ['jaraba_funding', 'jaraba_ai'];

  public function testIngestionCreatesEntities(): void {
    $service = $this->container->get('jaraba_funding.ingestion_service');

    // Mock API response
    $mockCalls = [
      ['external_id' => 'BDNS-001', 'title' => 'Test Call', ...]
    ];

    $result = $service->processConvocatorias($mockCalls);

    $this->assertEquals(1, $result['created']);
    
    // Verify entity was created
    $storage = $this->entityTypeManager->getStorage('funding_call');
    $calls = $storage->loadByProperties(['external_id' => 'BDNS-001']);
    $this->assertCount(1, $calls);
  }

  public function testDeduplicationPreventseDuplicates(): void {
    // Insert first
    $service->processConvocatorias([['external_id' => 'BDNS-001', ...]]);
    
    // Try to insert duplicate
    $result = $service->processConvocatorias([['external_id' => 'BDNS-001', ...]]);
    
    $this->assertEquals(0, $result['created']);
    $this->assertEquals(1, $result['updated']);
  }
}
 
12. Configuración
12.1 Variables de Entorno
# API Clients
BDNS_API_BASE_URL=https://www.pap.hacienda.gob.es/bdnstrans/api
BOJA_API_BASE_URL=https://www.juntadeandalucia.es/ssdigitales/datasets/contentapi

# Qdrant
QDRANT_URL=https://xxx.qdrant.io
QDRANT_API_KEY=xxx
QDRANT_FUNDING_COLLECTION=funding_calls

# AI
OPENAI_API_KEY=xxx  # Para embeddings
ANTHROPIC_API_KEY=xxx  # Para Copilot

# Notifications
SENDGRID_API_KEY=xxx
FCM_SERVER_KEY=xxx

# Sync Settings
FUNDING_SYNC_HOUR=6
FUNDING_DEADLINE_ALERT_HOUR=8
FUNDING_DEFAULT_REGION=andalucia
12.2 Configuración Drupal
# config/install/jaraba_funding.settings.yml
sync:
  enabled: true
  frequency: daily
  hour: 6
  sources:
    - bdns
    - boja

bdns:
  cache_ttl: 1800  # 30 minutos
  max_results_per_request: 50
  default_filters:
    estado: A  # Abiertas

boja:
  cache_ttl: 3600  # 1 hora
  sections:
    - '1'  # Disposiciones Generales

matching:
  min_score_threshold: 50
  weights:
    region: 20
    beneficiary_type: 25
    sector: 20
    size: 15
    semantic: 20

alerts:
  deadline_days: [7, 3, 1]
  batch_size: 100

copilot:
  model: claude-sonnet-4-5-20250929
  max_tokens: 1500
  top_k_results: 5
12.3 Permisos
# jaraba_funding.permissions.yml
access funding dashboard:
  title: 'Access funding dashboard'
  description: 'View funding calls and matches'

manage funding subscriptions:
  title: 'Manage funding subscriptions'
  description: 'Create and edit alert subscriptions'

use funding copilot:
  title: 'Use funding copilot'
  description: 'Access AI-powered funding assistant'
  restrict access: true

administer funding:
  title: 'Administer funding module'
  description: 'Full admin access to funding settings'
  restrict access: true
 
13. Roadmap de Implementación
13.1 Sprints
Sprint	Semanas	Entregables	Horas
1	2	Entidades + BdnsApiClient + Tests	80h
2	2	BojaApiClient + IngestionService + ECA Sync	80h
3	2	MatchingEngine + EligibilityCalculator + Tests	90h
4	2	AlertService + NotificationDispatcher + ECA Alerts	80h
5	2	CopilotService + Prompts + API REST	90h
6	2	React Dashboard + FundingCalendar	80h
7	1	Integration Tests + Performance + QA	60h
8	1	Documentación + Deploy + Training	40h
Total: 16 semanas = 4 meses | 600 horas estimadas
13.2 Dependencias
Módulo	Dependencia	Criticidad
MatchingEngine	jaraba_ai (Qdrant)	Alta
CopilotService	jaraba_ai (Claude)	Alta
AlertService	jaraba_email (SendGrid)	Media
EligibilityCalculator	25_Business_Diagnostic	Media
Legal References	178_Legal_Knowledge	Baja
13.3 Checklist Pre-Deploy
• Qdrant collection 'funding_calls' creada con schema correcto
• API keys configuradas: BDNS, BOJA, OpenAI, Anthropic, SendGrid
• Cron jobs configurados para sync y alertas
• Redis configurado para caché y queues
• Templates de email validados en múltiples clientes
• Tests pasando al 100%
• Permisos asignados a roles
• Carga inicial de convocatorias ejecutada
• Monitoring configurado (errores API, latencia)
13.4 KPIs Post-Launch
KPI	Target Mes 1	Target Mes 3
Convocatorias indexadas	500+	1000+
Usuarios con suscripciones	100	500
Matches generados/día	1000	5000
Consultas Copilot/día	50	200
Latencia Copilot p95	<3s	<2s
Precisión matching	>70%	>85%
 
14. Presupuesto
14.1 Desglose por Componente
Componente	Horas	Coste (@€45/h)
Entidades + Schema	40h	€1,800
API Clients (BDNS + BOJA)	60h	€2,700
Ingestion Service	40h	€1,800
Matching Engine	80h	€3,600
Eligibility Calculator	40h	€1,800
Copilot Service	60h	€2,700
Alert System	60h	€2,700
ECA Flows	20h	€900
API REST	40h	€1,800
React Components	80h	€3,600
Tests	40h	€1,800
Integración + QA	40h	€1,800
TOTAL	600h	€27,000
14.2 Costes Operativos Mensuales
Servicio	Uso Estimado	Coste/mes
Qdrant Cloud	100K vectors	€49
OpenAI Embeddings	1M tokens	€10
Claude API (Copilot)	500K tokens	€15
SendGrid	10K emails	€15
TOTAL	-	~€89/mes
14.3 ROI Proyectado
Modelo de ingresos con pricing Premium (€19/mes) y Enterprise (€49/mes):
Métrica	Mes 3	Mes 6	Mes 12
Usuarios Premium	50	150	400
Usuarios Enterprise	10	30	80
MRR	€1,440	€4,320	€11,520
Costes operativos	€89	€150	€300
Margen	€1,351	€4,170	€11,220
Break-even estimado: Mes 8 (considerando inversión inicial de €27,000)
 
15. Anexo: Código PHP Completo
15.1 jaraba_funding.services.yml
services:
  jaraba_funding.bdns_client:
    class: Drupal\jaraba_funding\Service\Api\BdnsApiClient
    arguments:
      - '@http_client'
      - '@logger.factory'
      - '@config.factory'
      - '@cache.funding'

  jaraba_funding.boja_client:
    class: Drupal\jaraba_funding\Service\Api\BojaApiClient
    arguments:
      - '@http_client'
      - '@logger.factory'
      - '@cache.funding'

  jaraba_funding.ingestion_service:
    class: Drupal\jaraba_funding\Service\Ingestion\FundingIngestionService
    arguments:
      - '@jaraba_funding.bdns_client'
      - '@jaraba_funding.boja_client'
      - '@entity_type.manager'
      - '@jaraba_ai.qdrant_client'
      - '@logger.factory'

  jaraba_funding.matching_engine:
    class: Drupal\jaraba_funding\Service\Intelligence\FundingMatchingEngine
    arguments:
      - '@jaraba_ai.qdrant_client'
      - '@jaraba_ai.claude_client'
      - '@jaraba_funding.eligibility_calculator'
      - '@entity_type.manager'
      - '@config.factory'

  jaraba_funding.eligibility_calculator:
    class: Drupal\jaraba_funding\Service\Intelligence\FundingEligibilityCalculator

  jaraba_funding.copilot_service:
    class: Drupal\jaraba_funding\Service\Intelligence\FundingCopilotService
    arguments:
      - '@jaraba_ai.claude_client'
      - '@jaraba_ai.qdrant_client'
      - '@jaraba_funding.matching_engine'
      - '@entity_type.manager'

  jaraba_funding.alert_service:
    class: Drupal\jaraba_funding\Service\Alerts\FundingAlertService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_funding.matching_engine'
      - '@queue'
      - '@logger.factory'

  jaraba_funding.notification_dispatcher:
    class: Drupal\jaraba_funding\Service\Alerts\FundingNotificationDispatcher
    arguments:
      - '@jaraba_email.sendgrid_client'
      - '@entity_type.manager'
      - '@logger.factory'
15.2 jaraba_funding.routing.yml
jaraba_funding.dashboard:
  path: '/funding'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingDashboardController::dashboard'
    _title: 'Subvenciones y Ayudas'
  requirements:
    _permission: 'access funding dashboard'

jaraba_funding.api.calls:
  path: '/api/v1/funding/calls'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::listCalls'
  methods: [GET]
  requirements:
    _permission: 'access funding dashboard'

jaraba_funding.api.copilot:
  path: '/api/v1/funding/copilot'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingCopilotController::query'
  methods: [POST]
  requirements:
    _permission: 'use funding copilot'

jaraba_funding.api.matches:
  path: '/api/v1/funding/matches'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingApiController::listMatches'
  methods: [GET]
  requirements:
    _permission: 'access funding dashboard'

jaraba_funding.api.subscriptions:
  path: '/api/v1/funding/subscriptions'
  defaults:
    _controller: '\Drupal\jaraba_funding\Controller\FundingSubscriptionController::crud'
  methods: [GET, POST, PATCH, DELETE]
  requirements:
    _permission: 'manage funding subscriptions'
— FIN DEL DOCUMENTO —
