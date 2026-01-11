
DOCUMENTO TÉCNICO MAESTRO
Jaraba Impact Platform

ANEXO A: Knowledge Base AI-Nativa

Arquitectura RAG Multi-Tenant para Copilots

Verticales: AgroConecta | ArteConecta | TurismoConecta | ...

Extensión de Sección 7: Inteligencia Artificial y Automatización

Versión 3.0 | Enero 2026
 
Tabla de Contenidos
Tabla de Contenidos	1
A1. Contexto y Alcance del Anexo	1
A1.1 Relación con Componentes del Maestro	1
A1.2 Principios 'Sin Humo' Aplicados	1
A2. Arquitectura RAG Multi-Tenant	1
A2.1 Diagrama de Componentes	1
A2.2 Nuevo Módulo: jaraba_rag	1
A2.3 Flujo RAG Integrado con Copilots	1
A3. Aislamiento Multi-Tenant de Knowledge Base	1
A3.1 Jerarquía de Acceso a Conocimiento	1
A3.2 Implementación con Group Module	1
A3.3 Acceso por Plan de Suscripción	1
A4. Grounding Estricto: Anti-Alucinaciones	1
A4.1 System Prompt para Copilots	1
A4.2 Servicio de Validación	1
A4.3 Respuestas Honestas para Gaps	1
A5. Indexación de Contenido Drupal	1
A5.1 Entidades Indexables	1
A5.2 Integración con Answer Capsules (GEO)	1
A5.3 Trigger de Indexación (ECA)	1
A6. Analytics y Bucle de Aprendizaje	1
A6.1 Clasificación de Queries	1
A6.2 Dashboard de Admin (Producer Copilot Extension)	1
A6.3 Notificaciones (Integración ECA + Brevo)	1
A7. GEO Extendido: /llms.txt y Schema.org	1
A7.1 Archivo /llms.txt Dinámico	1
A7.2 Schema.org Extendido para KB	1
A8. Stack Tecnológico (Extension Seccion 2.2)	1
A8.1 Módulos Drupal Requeridos	1
A8.2 Estimación de Costes Adicionales	1
A9. Roadmap de Implementación	1
A9.1 Fase 2 del Maestro: Motor de Integración (Semanas 5-8)	1
A9.2 Fase 3 del Maestro: Lanzamiento (Semanas 9-12)	1
A9.3 Post-Lanzamiento	1
A10. Checklist de Implementación	1
A10.1 Infraestructura KB	1
A10.2 Módulo jaraba_rag	1
A10.3 Indexación	1
A10.4 Copilots	1
A10.5 Analytics	1
A10.6 GEO	1

 
A1. Contexto y Alcance del Anexo
Este anexo extiende la Sección 7 (Inteligencia Artificial y Automatización) del Documento Técnico Maestro, definiendo la arquitectura de la Knowledge Base AI-Nativa que alimenta los Copilots de Jaraba Impact Platform.
POSICIONAMIENTO EN LA ARQUITECTURA
Este documento detalla la capa de datos y retrieval que nutre al Producer Copilot (Seccion 7.1.1) y Consumer Copilot (Seccion 7.1.2) del Documento Maestro. La Knowledge Base es el 'cerebro semantico' que garantiza respuestas precisas, verificables y limitadas al contexto del tenant.

A1.1 Relación con Componentes del Maestro
Componente Maestro	Referencia	Extension en este Anexo
Producer Copilot	Sección 7.1.1	Fuente de datos para generación y consultas
Consumer Copilot	Sección 7.1.2	Búsqueda semántica y recomendaciones
AI Interpolator	Sección 7.3	Contexto verificado para generación
jaraba_core	Sección 4	Nuevo servicio: JarabaRagService.php
jaraba_theme	Sección 3	Widget de chat integrado
Group Module	Sección 2.3	Aislamiento de KB por tenant
ECA Module	Sección 7.2	Triggers para indexación y alertas
GEO Strategy	Sección 8	/llms.txt y Schema.org extendidos
A1.2 Principios 'Sin Humo' Aplicados
•	Grounding Estricto: Cero alucinaciones. El Copilot solo responde con información verificada del tenant.
•	Desarrollo sobre estándares: Módulos Drupal AI (ai, ai_search), no reinventar la rueda.
•	Costes predecibles: Stack optimizado para SaaS multi-tenant sin sorpresas.
•	Filosofía 'Gourmet Digital': La IA es invisible; el protagonismo es del producto y el storytelling.
 
A2. Arquitectura RAG Multi-Tenant
A2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────────┐
│           JARABA IMPACT PLATFORM - KNOWLEDGE BASE AI-NATIVA            │
│                    (Extension de Seccion 7 del Maestro)                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  CAPA DE PRESENTACION (jaraba_theme)                                    │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────────┐  │
│  │ Producer Copilot│  │ Consumer Copilot│  │ Admin Dashboard         │  │
│  │ (Dashboard)     │  │ (Tienda)        │  │ (Analytics KB)          │  │
│  └────────┬────────┘  └────────┬────────┘  └───────────┬─────────────┘  │
│           └────────────────────┼──────────────────────┘                │
│                                │                                        │
│  CAPA DE NEGOCIO (jaraba_core + Modulos AI)                             │
│  ┌─────────────────────────────┴─────────────────────────────────────┐  │
│  │                      jaraba_rag (NUEVO)                           │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐    │  │
│  │  │ RagService  │  │ Grounding   │  │ QueryAnalytics          │    │  │
│  │  │ .php        │  │ Validator   │  │ Service                 │    │  │
│  │  └──────┬──────┘  └──────┬──────┘  └───────────┬─────────────┘    │  │
│  └─────────┼────────────────┼────────────────────┼───────────────────┘  │
│            │                │                    │                      │
│  ┌─────────┴────────────────┴────────────────────┴───────────────────┐  │
│  │                 RETRIEVAL LAYER (AI Search + Group)               │  │
│  │  ┌─────────────────────────────────────────────────────────────┐  │  │
│  │  │ VECTOR DB (Qdrant)                                          │  │  │
│  │  │                                                             │  │  │
│  │  │  COLLECTION: vertical_agro    COLLECTION: vertical_arte     │  │  │
│  │  │  ├── tenant_123               ├── tenant_456                │  │  │
│  │  │  ├── tenant_124               ├── tenant_457                │  │  │
│  │  │  └── shared_vertical          └── shared_vertical           │  │  │
│  │  │                                                             │  │  │
│  │  │  Payload: tenant_id | plan_level | content_type | access    │  │  │
│  │  └─────────────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│  CAPA DE DATOS (Drupal Entities)                                        │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐ │
│  │ commerce_   │  │ node:       │  │ taxonomy_   │  │ media:          │ │
│  │ product     │  │ article/faq │  │ term        │  │ document/video  │ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
A2.2 Nuevo Módulo: jaraba_rag
Se crea un nuevo módulo custom que extiende jaraba_core con la lógica de RAG:
modules/custom/jaraba_rag/
├── jaraba_rag.info.yml
├── jaraba_rag.services.yml
├── jaraba_rag.module
├── src/
│   ├── Service/
│   │   ├── JarabaRagService.php        # Orquestador principal
│   │   ├── GroundingValidator.php      # Verificacion anti-alucinacion
│   │   ├── QueryAnalyticsService.php   # Analytics y deteccion gaps
│   │   └── TenantContextService.php    # Extraccion contexto tenant
│   ├── Plugin/
│   │   └── search_api/processor/       # Procesadores Search API
│   │       └── TenantFilter.php        # Inyeccion filtro tenant
│   └── Controller/
│       └── RagAdminController.php      # Dashboard analytics
└── config/
    └── install/
        └── jaraba_rag.settings.yml
A2.3 Flujo RAG Integrado con Copilots
1.	Usuario interactúa con Consumer Copilot (Sección 7.1.2 del Maestro)
2.	JarabaRagService extrae tenant_id del contexto (Group Module)
3.	Query se vectoriza y busca en namespace del tenant (AI Search)
4.	Chunks recuperados pasan por GroundingValidator
5.	Contexto verificado se inyecta en prompt del AI Interpolator (Sección 7.3)
6.	Respuesta generada incluye citas a productos/artículos de Drupal
7.	QueryAnalyticsService registra interacción para mejora continua
 
A3. Aislamiento Multi-Tenant de Knowledge Base
Extendiendo el modelo de 'Soft Multi-Tenancy' del Group Module (Sección 2.3 del Maestro), la Knowledge Base implementa aislamiento estricto de datos para que cada tenant solo acceda a su información y a la compartida por la vertical.
A3.1 Jerarquía de Acceso a Conocimiento
┌─────────────────────────────────────────────────────────────────┐
│ CASCADA DE CONOCIMIENTO (consistente con Seccion 3.1 Maestro)  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  NIVEL 1: PLATAFORMA (acceso todos)                             │
│  └── Politicas generales, FAQs plataforma, terminos legales     │
│                                                                 │
│  NIVEL 2: VERTICAL (acceso por vertical)                        │
│  ├── AgroConecta: Guias agricolas, normativas alimentarias      │
│  ├── ArteConecta: Guias artisticas, propiedad intelectual       │
│  └── TurismoConecta: Normativas turisticas, certificaciones     │
│                                                                 │
│  NIVEL 3: PLAN (acceso por suscripcion)                         │
│  ├── Starter: Conocimiento basico                               │
│  ├── Growth: + Guias avanzadas multicanal                       │
│  ├── Pro: + Documentacion API, analytics avanzados              │
│  └── Enterprise: + Contenido exclusivo, formacion               │
│                                                                 │
│  NIVEL 4: TENANT (acceso exclusivo)                             │
│  └── Productos, articulos, FAQs propios del tenant              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
A3.2 Implementación con Group Module
El filtrado de KB se integra con el sistema existente de Groups:
// src/Service/TenantContextService.php

class TenantContextService {

  public function __construct(
    private GroupMembershipLoader $membershipLoader,
    private AccountInterface $currentUser
  ) {}

  public function getSearchFilters(): array {
    // Obtener grupo actual del usuario (Group Module)
    $memberships = $this->membershipLoader->loadByUser($this->currentUser);
    $group = $memberships[0]?->getGroup();
    
    if (!$group) {
      throw new AccessDeniedHttpException('Usuario sin tenant asignado');
    }
    
    // Extraer contexto del tenant
    $tenant_id = $group->id();
    $vertical = $group->get('field_vertical')->value;
    $plan = $group->get('field_plan')->value; // starter|growth|pro|enterprise
    
    // Construir filtros para Vector DB
    return [
      'tenant_id' => $tenant_id,
      'vertical' => $vertical,
      'plan_level' => $this->getAccessiblePlanLevels($plan),
    ];
  }
  
  private function getAccessiblePlanLevels(string $plan): array {
    // Planes accesibles segun suscripcion (Seccion 9.1 Maestro)
    return match($plan) {
      'enterprise' => ['starter', 'growth', 'pro', 'enterprise'],
      'pro' => ['starter', 'growth', 'pro'],
      'growth' => ['starter', 'growth'],
      default => ['starter'],
    };
  }
}
A3.3 Acceso por Plan de Suscripción
Consistente con la estructura de planes del Maestro (Sección 9.1):
Plan	Precio/mes	Comision	Acceso a Knowledge Base
Starter	€29	5%	KB básica: FAQs, guías inicio, soporte estándar
Growth	€59	3%	+ KB multicanal: guías Amazon, eBay, redes sociales
Pro	€99	1.5%	+ KB avanzada: docs API, analytics, optimización
Enterprise	€199+	Negociable	+ KB exclusiva: formación, consultoría, SLA

IMPORTANTE
El Consumer Copilot NUNCA sugiere productos o funcionalidades de planes superiores al del tenant. Esto evita 'cobro indebido' y frustracion del usuario. El sistema detecta oportunidades de upsell y las comunica al admin via dashboard, NO al usuario final.
 
A4. Grounding Estricto: Anti-Alucinaciones
PRINCIPIO FUNDAMENTAL
En el ecosistema Jaraba, donde la credibilidad es un activo estrategico, el Copilot NO puede inventar requisitos de subvenciones, consejos legales erroneos o caracteristicas de productos inexistentes. Cada afirmacion debe ser verificable contra la Knowledge Base del tenant.

A4.1 System Prompt para Copilots
Extensión del prompt del AI Interpolator (Sección 7.3 del Maestro):
// System Prompt - Consumer Copilot

Eres el asistente de compras de {tenant.name}, una tienda de
{vertical.description} en Jaraba Impact Platform.

## REGLAS INQUEBRANTABLES

1. SOLO CONTEXTO: Responde UNICAMENTE usando la informacion del
   CATALOGO Y CONOCIMIENTO proporcionado abajo. NUNCA inventes.

2. HONESTIDAD: Si no tienes informacion, responde:
   "No tengo esa informacion. Puedo ayudarte con [sugerir
   productos/temas que SI estan en el catalogo]?"

3. CITAS: Cada producto mencionado DEBE incluir enlace.
   Formato: [Nombre Producto](/producto/slug)

4. LIMITE: Solo hablas de productos de {tenant.name}.
   NO mencionas competidores ni productos externos.

5. FILOSOFIA 'GOURMET DIGITAL': Tu tono es calido, artesanal.
   Transmites calidad y cuidado, no vendes agresivamente.

## CATALOGO Y CONOCIMIENTO
═══════════════════════════════════════════════════════════════
{retrieved_chunks}
═══════════════════════════════════════════════════════════════
A4.2 Servicio de Validación
// src/Service/GroundingValidator.php

class GroundingValidator {

  public function validate(string $response, array $chunks): ValidationResult {
    $claims = $this->extractClaims($response);
    $validatedClaims = [];
    
    foreach ($claims as $claim) {
      $relevantChunk = $this->findSupportingChunk($claim, $chunks);
      
      if ($relevantChunk && $this->isEntailed($claim, $relevantChunk)) {
        $validatedClaims[] = [
          'claim' => $claim,
          'source' => $relevantChunk['source_url'],
          'valid' => TRUE,
        ];
      } else {
        // Claim no verificable = potencial alucinacion
        $validatedClaims[] = [
          'claim' => $claim,
          'valid' => FALSE,
          'action' => 'REMOVE_OR_REPHRASE',
        ];
      }
    }
    
    $hallucinations = array_filter($validatedClaims, fn($c) => !$c['valid']);
    
    return new ValidationResult(
      isValid: count($hallucinations) === 0,
      claims: $validatedClaims,
      hallucinationCount: count($hallucinations)
    );
  }
}
A4.3 Respuestas Honestas para Gaps
Escenario	Respuesta Incorrecta	Respuesta Correcta
Producto no existe	"Tenemos aceite de coco a €15"	"No tenemos aceite de coco. ¿Te interesa nuestro aceite de oliva virgen extra?"
Info no disponible	"El envío tarda 2-3 días"	"No tengo info de envíos. Contacta con la tienda en [enlace]"
Fuera de dominio	"Para tu declaración de renta..."	"Mi especialidad es [productos]. Para temas fiscales, consulta un profesional."
Comparación competencia	"Somos mejores que X"	"Puedo contarte sobre nuestros productos. ¿Qué te gustaría saber?"
 
A5. Indexación de Contenido Drupal
La Knowledge Base se nutre de todas las entidades Drupal del tenant, aprovechando la estructura existente de Commerce 3.x (Sección 5.1 del Maestro).
A5.1 Entidades Indexables
Entidad Drupal	Referencia Maestro	Campos Indexados	Estrategia Chunking
commerce_product	Sección 5.1	title, body, variations, price, field_ai_summary	Por sección + Answer Capsule
node:article	jaraba_theme	title, body, field_summary, taxonomy	Por párrafos (500 tokens)
node:faq	GEO (Sec 8)	field_question, field_answer	Q&A completo como chunk
taxonomy_term	Commerce 3.x	name, description, synonyms	Término completo
media:document	jaraba_core	Contenido extraído PDF/DOCX	Por páginas
media:video	AI Automators	Transcripción (Whisper)	Por segmentos
A5.2 Integración con Answer Capsules (GEO)
El campo field_ai_summary de productos (Sección 8.3 del Maestro) se indexa como chunk prioritario:
// Estrategia de chunking para commerce_product

function jaraba_rag_chunk_product(ProductInterface $product): array {
  $chunks = [];
  
  // CHUNK 1: Answer Capsule (prioridad maxima para retrieval)
  $answer_capsule = $product->get('field_ai_summary')->value;
  if ($answer_capsule) {
    $chunks[] = [
      'text' => $answer_capsule,
      'type' => 'answer_capsule',
      'priority' => 1.0, // Boost en retrieval
      'metadata' => [
        'source_url' => $product->toUrl()->toString(),
        'source_title' => $product->getTitle(),
        'price' => $product->get('price')->getValue(),
      ],
    ];
  }
  
  // CHUNK 2+: Descripcion completa fragmentada
  $body = $product->get('body')->value;
  $body_chunks = $this->splitByTokens($body, 500, 100);
  foreach ($body_chunks as $i => $chunk_text) {
    $chunks[] = [
      'text' => $this->enrichWithContext($chunk_text, $product),
      'type' => 'description',
      'priority' => 0.8,
      'chunk_index' => $i,
    ];
  }
  
  return $chunks;
}
A5.3 Trigger de Indexación (ECA)
Extensión de las reglas ECA existentes (Sección 7.2 del Maestro):
Regla ECA	Trigger	Condicion	Accion
KB Index Product	Producto guardado	status = publicado	Queue para indexación vectorial
KB Index Article	Artículo guardado	status = publicado	Queue para indexación vectorial
KB Reindex Tenant	Config tenant cambia	field_kb_enabled = TRUE	Reindexar todo el tenant
KB Remove Deleted	Entidad eliminada	Cualquier tipo indexable	Eliminar vectores asociados
KB Alert Gap	Query sin respuesta	confidence < 0.5	Notificar admin + log
 
A6. Analytics y Bucle de Aprendizaje
El sistema no solo responde preguntas, sino que aprende de ellas para mejorar el contenido y detectar oportunidades de negocio, alimentando el dashboard del Producer Copilot (Sección 7.1.1).
A6.1 Clasificación de Queries
Clasificacion	Descripcion	Accion Automatica
ANSWERED_FULL	Query respondida satisfactoriamente	Log + feedback positivo
ANSWERED_PARTIAL	Info parcial disponible	Sugerir contenido a crear
UNANSWERED	Sin info en KB	Alerta a admin + priorizar
OUT_OF_SCOPE	Fuera del dominio del tenant	Redirigir educadamente
PURCHASE_INTENT	Señal de intención de compra	Tag en CRM (si integrado)
UPSELL_OPPORTUNITY	Pregunta sobre feature de plan superior	Notificar admin (NO al usuario)
A6.2 Dashboard de Admin (Producer Copilot Extension)
Nuevo tab en el dashboard del productor (user.html.twig, Sección 3.3.2):
┌─────────────────────────────────────────────────────────────────┐
│  DASHBOARD: Asistente IA - {tenant.name}           📊 Ene 2026 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  RESUMEN SEMANAL                                                │
│  ──────────────────────────────────────────────────────────     │
│  Consultas totales:    234         ▲ 15% vs semana anterior     │
│  Tasa de respuesta:    89%         ▲ 3%                         │
│  Satisfaccion:         4.3/5       ─ estable                    │
│  Queries sin respuesta: 26         ▼ 12% (mejorando)            │
│                                                                 │
│  TOP PREGUNTAS SIN RESPUESTA (requiere tu atencion)             │
│  ──────────────────────────────────────────────────────────     │
│  1. '¿Haceis envio a Canarias?' (12x)      [+ Crear FAQ]        │
│  2. '¿El aceite es apto para freir?' (8x)  [+ Añadir a producto]│
│  3. '¿Teneis formato de 250ml?' (7x)       [+ Crear variacion?] │
│                                                                 │
│  OPORTUNIDADES DETECTADAS                                       │
│  ──────────────────────────────────────────────────────────     │
│  🔥 'suscripcion mensual' - 5 menciones - ¿Modelo recurrente?   │
│  🔥 'regalo empresa' - 4 menciones - ¿Pack corporativo?         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
A6.3 Notificaciones (Integración ECA + Brevo)
Usando la infraestructura de notificaciones existente (Sección 6.2.3):
Notificacion	Canal	Frecuencia	Contenido
Resumen semanal KB	Email (Brevo)	Lunes 9:00	Métricas + top gaps + oportunidades
Gap crítico	Email + Dashboard	Inmediato	Si >5 queries mismo tema en 24h
Feedback negativo	Dashboard	Tiempo real	Badge en icono KB del dashboard
Upsell opportunity	CRM (HubSpot)	Inmediato	Tag en contacto si integrado
 
A7. GEO Extendido: /llms.txt y Schema.org
Extensión de la estrategia GEO del Maestro (Sección 8) para que los Copilots externos (ChatGPT, Perplexity) también puedan acceder al contenido de forma estructurada.
A7.1 Archivo /llms.txt Dinámico
Generado automáticamente para cada tenant:
# /llms.txt - Generado por Jaraba Impact Platform
# Tenant: Finca La Huerta | Vertical: AgroConecta

## Sobre esta tienda
Finca La Huerta es una tienda de productos agroalimentarios de calidad
en la plataforma AgroConecta de Jaraba Impact Platform.

## Catalogo de productos
- /productos: Catalogo completo ({product_count} productos)
- /categorias: Navegacion por categorias
- /ofertas: Productos en promocion

## Informacion de la tienda
- /sobre-nosotros: Historia y valores
- /contacto: Formulario de contacto
- /faq: Preguntas frecuentes

## Datos estructurados
Todas las paginas incluyen Schema.org (Product, Organization, FAQPage)

## Actualizacion
Ultima actualizacion: {last_modified}
A7.2 Schema.org Extendido para KB
Además del Schema.org básico (Sección 8.2), se añade markup para el Copilot:
// JSON-LD adicional para paginas con Copilot activo
{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "Asistente de Compras - Finca La Huerta",
  "applicationCategory": "ShoppingApplication",
  "operatingSystem": "Web",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "EUR"
  },
  "featureList": [
    "Busqueda semantica de productos",
    "Recomendaciones personalizadas",
    "Respuestas sobre productos en lenguaje natural"
  ]
}
 
A8. Stack Tecnológico (Extension Seccion 2.2)
Componentes adicionales al stack del Maestro:
Capa	Componente	Tecnologia	Justificacion
KB	Vector Database	Qdrant (Cloud Free / Docker)	Collections nativas, $0/mes
KB	Embeddings	OpenAI text-embedding-3-small	Balance calidad/coste, $0.02/1M
KB	Búsqueda	AI Search (Drupal)	Integración nativa Search API
KB	Grounding	NLI Validator	Verificación anti-alucinación
KB	Analytics	Custom + AI Logging	Detección gaps, métricas
GEO	/llms.txt	llmstxt (Drupal)	Descubrimiento por LLMs externos
A8.1 Módulos Drupal Requeridos
Modulo	Version	Funcion	Dependencia
ai	^1.0	Core AI framework	Requerido
ai_search	^1.0	Búsqueda semántica + RAG	Requerido
ai_chatbot	^1.0	Widget Copilot	Requerido
ai_logging	^1.0	Logging interacciones	Requerido
ai_vdb_provider_qdrant	^1.0	Connector Qdrant	Requerido
llmstxt	^1.0	Generador /llms.txt	Recomendado
schemadotorg	^3.0	Schema.org Blueprints	Ya en Maestro
A8.2 Estimación de Costes Adicionales
Costes incrementales sobre el stack base del Maestro:
Servicio	Tier	Coste/mes	Notas
Qdrant	Cloud Free / Docker	$0	1M vectors gratis, Docker local ilimitado
OpenAI Embeddings	API	~$20	Indexación continua
OpenAI Chat (Copilots)	gpt-4o-mini	~$30	~5K queries/mes/tenant promedio
TOTAL ADICIONAL	-	~$50/mes	≈ $0.50/tenant/mes adicional

NOTA: CONFIGURACIÓN POR ENTORNO
• LOCAL (Lando): Qdrant Docker en servicio `qdrant:6333`
• PRODUCCIÓN (IONOS Managed): Qdrant Cloud Free (1M vectores gratis)

COSTE TOTAL POR TENANT
Sumando al coste base del Maestro (~€2.50/tenant infrastructura), la KB AI-Nativa añade ~€0.50/tenant, para un total de ~€3.00/tenant/mes. Con planes desde €29/mes, el margen es muy saludable.
 
A9. Roadmap de Implementación
Integrado con las fases del Maestro (Sección 10):
A9.1 Fase 2 del Maestro: Motor de Integración (Semanas 5-8)
La KB AI-Nativa se implementa en paralelo a las integraciones Make.com:
✓	Semana 5-6: Instalar módulos AI + configurar Pinecone
✓	Semana 6: Crear jaraba_rag module con servicios base
✓	Semana 7: Integrar con Group Module para multi-tenancy
✓	Semana 7-8: Configurar pipeline de indexación + ECA triggers
✓	Semana 8: Testing de aislamiento tenant A/B
KPI: Tasa respuesta Copilot > 80%, Aislamiento verificado 100%
A9.2 Fase 3 del Maestro: Lanzamiento (Semanas 9-12)
✓	Semana 9: Activar Consumer Copilot en tiendas piloto
✓	Semana 10: Dashboard analytics en Producer Copilot
✓	Semana 11: Configurar notificaciones de gaps
✓	Semana 12: /llms.txt + Schema.org extendido
KPI: Hallucination rate < 1%, Gaps detectados > 90% accionados
A9.3 Post-Lanzamiento
Mejora	Prioridad	Estimacion	Impacto
GraphRAG para relaciones complejas	Media	40h	Recomendaciones cruzadas
Transcripción vídeos (Whisper)	Media	20h	Más contenido indexable
Integración CRM para upsell	Alta	16h	Conversión de oportunidades
A/B testing de prompts	Media	24h	Optimización continua
 
A10. Checklist de Implementación
A10.1 Infraestructura KB
✓	Cuenta Qdrant Cloud creada (Free Tier) + API key configurada
✓	Módulos AI instalados (ai, ai_search, ai_chatbot, ai_logging)
✓	ai_vdb_provider_qdrant configurado
✓	Qdrant Docker añadido a .lando.yml para desarrollo local
✓	Index creado con namespaces por vertical
A10.2 Módulo jaraba_rag
✓	jaraba_rag.info.yml con dependencias
✓	JarabaRagService.php implementado
✓	TenantContextService.php integrado con Group
✓	GroundingValidator.php funcional
✓	TenantFilter processor para Search API
A10.3 Indexación
✓	commerce_product indexable con Answer Capsule
✓	node:article y node:faq indexables
✓	taxonomy_term indexable
✓	ECA triggers configurados para CRUD
✓	Reindexación bulk ejecutada para tenants piloto
A10.4 Copilots
✓	System prompt con grounding estricto
✓	Consumer Copilot widget en tienda
✓	Producer Copilot tab KB en dashboard
✓	Respuestas honestas para gaps configuradas
A10.5 Analytics
✓	AI Logging activo (GDPR compliant)
✓	Clasificación de queries funcionando
✓	Dashboard métricas visible para productor
✓	Notificaciones email configuradas (Brevo)
A10.6 GEO
✓	/llms.txt dinámico por tenant
✓	Schema.org WebApplication para Copilot
✓	robots.txt permite AI crawlers (verificar Maestro)

— Fin del Anexo A —

Este documento es un anexo del Documento Técnico Maestro v2.0
Jaraba Impact Platform | Enero 2026
