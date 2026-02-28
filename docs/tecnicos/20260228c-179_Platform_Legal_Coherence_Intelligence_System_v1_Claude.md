


SISTEMA DE COHERENCIA LEGAL INTELIGENTE
Legal Coherence Intelligence System (LCIS)
Jerarquía Normativa • Coherencia Transversal • Grounding Jurídico

Atributo	Valor
Versión	1.0
Fecha	Febrero 2026
Estado	Especificación Técnica — Ready for Development
Código	179_Platform_Legal_Coherence_Intelligence_System
Dependencias	108_AI_Agent_Flows, 129_AI_Skills, 130_Knowledge_Training, 20260110h-KB_AI_Nativa, 93_Copilot_Servicios
Prioridad	CRÍTICA — Diferenciador de mercado y mitigación de riesgo legal
Compliance	EU AI Act (2024/1689), ISO 42001, Reglamento eIDAS, RGPD
Verticales	TODOS (transversal) + Jaraba Lex (específico)

⚠️  ADVERTENCIA FUNDAMENTAL: Este sistema NO sustituye el criterio profesional del jurista. Todas las respuestas de IA en contexto legal incluirán disclaimer obligatorio de no-asesoramiento y recomendación de consulta profesional. El sistema es un asistente de investigación y orientación, NUNCA un sustituto del abogado.
 
ÍNDICE DE CONTENIDOS
1. Resumen Ejecutivo	1
1.1 El Problema: IA Jurídicamente Incoherente	1
1.2 La Solución: Legal Coherence Intelligence System (LCIS)	1
1.3 Impacto Esperado	1
1.4 Posicionamiento Competitivo	1
2. Fundamentos Jurídicos del Sistema	1
2.1 Principio de Jerarquía Normativa (Art. 9.3 CE)	1
2.2 Principio de Competencia (Título VIII CE)	1
2.3 Principio de Vigencia Temporal	1
2.4 Principios Interpretativos Adicionales	1
3. Arquitectura Técnica del LCIS	1
3.1 Visión General: Cinco Capas de Coherencia Legal	1
3.2 Capa 1: Legal Intent Classifier	1
3.3 Capa 2: Normative Graph RAG	1
3.4 Capa 3: Hierarchy-Aware System Prompt	1
3.5 Capa 4: Coherence Validator (Post-Generación)	1
3.6 Capa 5: Disclaimer y Citación Automática	1
4. Skill Claude Code: jaraba-legal-coherence	1
4.1 Contenido Completo del SKILL.md	1
5. Módulo Drupal: jaraba_lcis	1
5.1 Estructura del Módulo	1
5.2 Servicio Orquestador: LegalCoherencePipeline	1
6. Alimentación de la Base Normativa	1
6.1 Fuentes de Datos Normativas	1
6.2 Pipeline de Ingesta Normativa	1
6.3 Paquete Normativo Inicial	1
7. Vertical Jaraba Lex: Especificación	1
7.1 Propuesta de Valor Diferencial	1
7.2 Componentes Específicos de Jaraba Lex	1
8. Automatizaciones ECA	1
8.1 Reglas ECA del LCIS	1
9. APIs REST del LCIS	1
10. Testing y Quality Assurance	1
10.1 Suite de Tests de Coherencia Legal	1
11. Roadmap de Implementación	1
11.1 Fases de Desarrollo	1
11.2 Dependencias Críticas	1
12. Conclusión	1

 
 
1. Resumen Ejecutivo
1.1 El Problema: IA Jurídicamente Incoherente
Los sistemas de IA generativa actuales, incluidos los más avanzados como Claude y GPT-4, presentan un problema estructural cuando operan en contextos jurídicos: carecen de un modelo interno del ordenamiento jurídico. Esto significa que pueden producir respuestas que contradicen principios fundamentales del Derecho, como la jerarquía normativa, los ámbitos competenciales, la vigencia temporal de las normas, o la prevalencia de ley especial sobre ley general.
En el contexto del Ecosistema Jaraba, donde múltiples verticales tocan aspectos legales (Empleabilidad toca derecho laboral, Emprendimiento toca mercantil y tributario, AgroConecta toca normativa agroalimentaria, ComercioConecta toca consumo y comercio electrónico, ServiciosConecta toca regulación profesional), y donde se contempla un vertical específico Jaraba Lex para servicios legales, la incoherencia jurídica de la IA no es solo un riesgo técnico: es un riesgo de mercado, reputacional y de responsabilidad civil.
DATO CRÍTICO: Según investigación de Stanford (Magesh et al., 2025), las herramientas líderes de investigación legal con IA producen ‘alucinaciones jurídicas’ en porcentajes significativos de sus respuestas. Un sistema SaaS que reproduzca este patrón en el mercado español destruirá su credibilidad con el primer error público.
1.2 La Solución: Legal Coherence Intelligence System (LCIS)
El LCIS es una capa transversal de inteligencia jurídica que se integra en toda la arquitectura de IA del Ecosistema Jaraba para garantizar que cada respuesta con contenido legal sea coherente con los principios generales del Derecho español y europeo. No es un chatbot legal ni un sustituto del abogado: es un guardrail estructural que impide que la IA produzca respuestas jurídicamente incoherentes.
El sistema opera en cuatro dimensiones simultáneas:
Dimensión	Qué Garantiza	Ejemplo
Jerarquía Normativa	La IA nunca cita un decreto como prevalente sobre una ley orgánica	Constitución > LO > Ley Ordinaria > RD > Orden > Reglamento local
Competencia Territorial	La IA identifica qué nivel administrativo regula qué materia	Laboral = Estado | Urbanismo = CCAA | Licencias = Municipio
Vigencia Temporal	La IA solo cita normas vigentes en la fecha de consulta	Ley derogada por otra posterior nunca aparece como aplicable
Especialidad	Lex specialis derogat generali aplicado sistemáticamente	LOPD vs normativa sectorial salud para datos médicos

1.3 Impacto Esperado
Métrica	Sin LCIS	Con LCIS	Mejora
Coherencia jerarquía normativa	~65% (estimado LLM base)	>95% (validación estructural)	~30% mejora
Citas a normas derogadas	~15% de respuestas	<2% de respuestas	87% reducción
Error competencial (Estado/CCAA/Local)	~20% de respuestas	<3% de respuestas	85% reducción
Confianza profesional usuario	Baja (desconfían de IA genérica)	Alta (sistema grounded verificable)	Diferenciador mercado
Disclaimer legal automático	Manual/inconsistente	100% automático obligatorio	Mitigación riesgo total
Tiempo investigación legal	2-4h búsqueda manual	15-30 min con orientación IA	5-8x más rápido

1.4 Posicionamiento Competitivo
El análisis del mercado legaltech español revela un hueco estratégico claro. Los competidores principales (Aranzadi K+, Lefebvre GenIA-L, Lexroom, AiConsultas, Maite.ai) operan como herramientas verticales independientes con bases de datos propietarias masivas. Ninguno ofrece coherencia normativa estructural como sistema transversal integrado en un ecosistema multi-vertical. Este es el diferenciador de Jaraba Lex.
Competidor	Modelo	Debilidad vs Jaraba Lex
Aranzadi K+ (Wolters Kluwer)	IA sobre BD propietaria masiva. Azure OpenAI.	Vertical puro. No integra con gestión despacho. Coste alto (~200-500€/mes).
Lefebvre GenIA-L Juris	IA sobre contenido Lefebvre. Informes estructurados.	Cerrado a su ecosistema editorial. Sin multi-tenancy.
Lexroom (Italia→España)	Serie A €20M. BOE/Gazzetta como fuente.	Recén llegado a España. Sin red local. Coste alto.
AiConsultas	SaaS específico derecho español.	Solo consultas. Sin gestión integral. Sin verticales.
Harvey	Enterprise. Grandes despachos.	Precio prohibitivo para PYME rural. Solo inglés nativo.
JARABA LEX	LCIS transversal + Gestión despacho + Multi-vertical	Coherencia normativa estructural + precio accesible + ecosistema integrado
 
2. Fundamentos Jurídicos del Sistema
Antes de especificar la arquitectura técnica, es imprescindible definir con precisión los principios jurídicos que el LCIS debe modelar. Estos principios no son opiniones: son reglas estructurales del ordenamiento jurídico español y europeo que todo jurista conoce y que la IA debe respetar con absoluta consistencia.
2.1 Principio de Jerarquía Normativa (Art. 9.3 CE)
La Constitución Española establece en su artículo 9.3 la garantía del principio de jerarquía normativa. Este principio implica que una norma de rango inferior no puede contradecir una de rango superior. El LCIS debe modelar esta jerarquía como un grafo ordenado estricto:
Pirámide Normativa Española (Modelo LCIS)
Nivel	Rango	Tipo Normativo	Autoridad Emisora	Weight LCIS
N1	Supraconstitucional	Tratados UE, CEDH	UE, Consejo Europa	1.00
N2	Constitucional	Constitución Española 1978	Poder Constituyente	0.98
N3	Ley Orgánica	LO (mayoría absoluta)	Cortes Generales	0.95
N4	Ley Ordinaria / DL	Ley, DL, DLeg, Estatutos Autonomía	Cortes / Gobierno	0.90
N5	Reglamento estatal	Real Decreto, Orden Ministerial	Gobierno / Ministerios	0.80
N6	Ley autonómica	Ley Parlamento Autonómico	Parlamentos CCAA	0.85*
N7	Reglamento autonómico	Decreto, Orden Consejería	Gobierno CCAA	0.70
N8	Normativa local	Ordenanza, Bando, Reglamento	Ayuntamientos, Diputaciones	0.60

* NOTA COMPETENCIAL: El weight de ley autonómica (N6=0.85) es SUPERIOR al de reglamento estatal (N5=0.80) en materias de competencia exclusiva autonómica. El sistema LCIS resuelve esto dinámicamente según el ámbito material de la consulta.
2.2 Principio de Competencia (Título VIII CE)
El sistema constitucional español distribuye competencias entre Estado y Comunidades Autónomas (arts. 148-149 CE). El LCIS debe conocer esta distribución para evitar el error más frecuente de las IAs genéricas: aplicar normativa estatal cuando la competencia es autonómica, o viceversa.
Mapa Competencial del LCIS
Ámbito Material	Competencia	Ejemplo Normativo	Vertical Jaraba
Legislación laboral	Estado (exclusiva, art. 149.1.7ª)	Estatuto Trabajadores, LGSS	Empleabilidad
Comercio interior	CCAA (art. 148.1.12ª)	Leyes autonom. comercio	ComercioConecta
Urbanismo y vivienda	CCAA (art. 148.1.3ª)	LOUA (Andalucía)	Emprendimiento
Agricultura y ganadería	CCAA + Estado (bases)	PAC + Leyes auton. agrarias	AgroConecta
Defensa consumidores	CCAA (desarrollo) + Estado (bases)	LGDCU + leyes autonómicas	ComercioConecta
Colegios profesionales	CCAA (art. 148.1.1ª)	Leyes colegiales autonómicas	ServiciosConecta
Protección datos	Estado (exclusiva) + UE (RGPD)	LOPDGDD, RGPD	TODOS
Fiscalidad	Estado + CCAA (cedidos) + Local	LGT, IRPF, IVA, IBI...	Emprendimiento
Subvenciones	Quien tenga competencia material	Ley 38/2003 + normas autonom.	TODOS

2.3 Principio de Vigencia Temporal
Las normas tienen un ciclo de vida: entrada en vigor, posibles modificaciones, y eventual derogación. El artículo 2.2 del Código Civil establece que las leyes solo se derogan por otras posteriores. El LCIS debe modelar este ciclo de vida para garantizar que nunca cita una norma derogada como vigente, ni aplica una norma futura que aún no ha entrado en vigor.
Estados de Vigencia en el LCIS
Estado	Definición	Comportamiento LCIS
VIGENTE	Norma en vigor a fecha de consulta	Citable. Weight normal.
DEROGADA_TOTAL	Norma completamente derogada	NO citable como aplicable. Solo referencia histórica.
DEROGADA_PARCIAL	Algunos artículos derogados, otros vigentes	Solo artículos vigentes citables. Indicar derogación parcial.
MODIFICADA	Redacción alterada por norma posterior	Citar versión VIGENTE. Indicar modificación si relevante.
PENDIENTE	Publicada pero no en vigor (vacatio legis)	Mencionar como futura. NO como aplicable.
TRANSITORIA	Régimen transitorio activo	Aplicable según disposiciones transitorias.

2.4 Principios Interpretativos Adicionales
El LCIS debe incorporar los siguientes principios interpretativos reconocidos en el artículo 3.1 del Código Civil y la doctrina constitucional:
Lex specialis derogat generali: La norma especial prevalece sobre la general en su ámbito específico. Ejemplo: la normativa de protección de datos sanitarios prevalece sobre la LOPDGDD general en el ámbito sanitario.
Lex posterior derogat priori: En caso de conflicto entre normas del mismo rango, prevalece la más reciente. El LCIS resuelve esto automáticamente vía timestamps de vigencia.
Principio pro persona: En caso de duda interpretativa, se aplica la norma más favorable al ciudadano. Especialmente relevante en derecho laboral (principio pro operario) y consumo.
Reserva de ley: Ciertas materias solo pueden regularse por ley formal, nunca por reglamento. El LCIS debe alertar si detecta regulación reglamentaria en materia reservada a ley.
Primacía del Derecho UE: El Derecho de la Unión Europea tiene primacía sobre el Derecho nacional en materias de competencia comunitaria (Costa/ENEL, 1964; Simmenthal, 1978). Reglamentos UE son directamente aplicables.
 
3. Arquitectura Técnica del LCIS
3.1 Visión General: Cinco Capas de Coherencia Legal
El LCIS opera como un sistema de cinco capas defensivas que se aplican secuencialmente a toda interacción de IA que contenga contenido jurídico. Cada capa añade un nivel de verificación que las capas anteriores no cubren. Una respuesta debe pasar TODAS las capas para ser entregada al usuario.
Capa	Nombre	Función	Tecnología
C1	Legal Intent Classifier	Detectar si la consulta tiene componente jurídico	NLI + clasificador fine-tuned
C2	Normative Graph RAG	Recuperar normas relevantes con jerarquía y vigencia	Qdrant + Knowledge Graph
C3	Hierarchy-Aware Prompt	System prompt que impone reglas de coherencia al LLM	Prompt engineering estructurado
C4	Coherence Validator	Verificar post-generación que la respuesta es coherente	NLI + reglas deterministas
C5	Disclaimer & Citation	Añadir disclaimer legal y citas verificables	Template engine + metadata

3.2 Capa 1: Legal Intent Classifier
No toda consulta al ecosistema tiene componente legal. Un agricultor preguntando por el precio del aceite no necesita coherencia normativa. Pero si pregunta por los requisitos de etiquetado, sí. La Capa 1 clasifica el intent de cada consulta para activar o no el pipeline LCIS.
Taxonomía de Intents Legales
Intent	Descripción	Ejemplo	Activa LCIS
LEGAL_DIRECT	Consulta explícitamente jurídica	¿Qué dice la ley sobre despido improcedente?	SÍ (completo)
LEGAL_IMPLICIT	Consulta con implicaciones legales no explícitas	¿Puedo vender mermelada casera online?	SÍ (completo)
LEGAL_REFERENCE	Mención tangencial a normativa	¿Mi tienda necesita licencia de apertura?	SÍ (parcial)
COMPLIANCE_CHECK	Verificación de cumplimiento normativo	¿Mi web cumple con RGPD?	SÍ (completo)
NON_LEGAL	Sin componente jurídico	¿Cuál es el mejor aceite de oliva?	NO

Implementación Técnica del Clasificador
// Servicio: jaraba_lcis/src/Service/LegalIntentClassifier.php

namespace Drupal\jaraba_lcis\Service;

use Drupal\jaraba_ai\Service\LLMClient;
use Drupal\jaraba_lcis\Model\LegalIntent;

class LegalIntentClassifier {
  private LLMClient $llm;
  private array $legalKeywords;

  /**
   * Clasifica el intent legal de una consulta.
   *
   * Usa clasificación híbrida: keywords + LLM ligero.
   * El LLM solo se invoca si keywords no son concluyentes.
   */
  public function classify(string $query, string $vertical): LegalIntent {
    // Paso 1: Clasificación rápida por keywords (coste cero)
    $keywordScore = $this->keywordAnalysis($query);
    if ($keywordScore > 0.85) {
      return new LegalIntent('LEGAL_DIRECT', $keywordScore);
    }
    if ($keywordScore < 0.15) {
      return new LegalIntent('NON_LEGAL', 1.0 - $keywordScore);
    }

    // Paso 2: Clasificación LLM para zona gris (gemini-2.5-flash)
    $prompt = $this->buildClassificationPrompt($query, $vertical);
    $result = $this->llm->classify($prompt, 'gemini-2.5-flash');
    return LegalIntent::fromLLMResponse($result);
  }
}

3.3 Capa 2: Normative Graph RAG
Esta es la capa más innovadora del LCIS y el principal diferenciador técnico. En lugar de usar RAG plano (flat vector search), el LCIS implementa un Graph RAG jurídico que modela explícitamente las relaciones entre normas: jerarquía, derogaciones, modificaciones, competencias y referencias cruzadas.
PRINCIPIO ARQUITECTÓNICO: Un sistema RAG convencional plano es fundamentalmente incapaz de responder consultas que requieren navegación jerárquica del ordenamiento jurídico (De Martim, 2025; Linna & Linna, 2026). La coherencia normativa REQUIERE un knowledge graph explícito.
Modelo de Datos del Knowledge Graph Legal
El Knowledge Graph Legal se implementa como una capa sobre Qdrant (vector DB) enriquecida con metadatos estructurados que modelan las relaciones normativas. Cada norma es un nodo con propiedades específicas:
// Entidad: legal_norm (Content Entity con revisions)
// Módulo: jaraba_lcis

Schema legal_norm {
  id: BIGSERIAL PRIMARY KEY
  norm_type: ENUM('constitucion','tratado_ue','ley_organica','ley_ordinaria',
    'real_decreto_ley','real_decreto_legislativo','real_decreto',
    'orden_ministerial','ley_autonomica','decreto_autonomico',
    'orden_consejeria','ordenanza_local','reglamento_ue',
    'directiva_ue','reglamento_local')
  hierarchy_level: INT NOT NULL  // N1=1 ... N8=8
  hierarchy_weight: DECIMAL(3,2) // 0.00-1.00
  title: VARCHAR(500) NOT NULL
  short_title: VARCHAR(200)  // Título abreviado común
  identifier: VARCHAR(100) UNIQUE  // Ej: 'BOE-A-2015-11430'
  publication_date: DATE NOT NULL
  effective_date: DATE NOT NULL  // Entrada en vigor
  expiry_date: DATE  // NULL = vigente
  status: ENUM('vigente','derogada_total','derogada_parcial',
    'modificada','pendiente','transitoria') DEFAULT 'vigente'
  territorial_scope: ENUM('ue','estatal','autonomico','local')
  autonomous_community: VARCHAR(50)  // NULL si estatal/UE
  municipality: VARCHAR(100)  // NULL si no local
  subject_areas: JSON  // ['laboral','fiscal','civil'...]
  competence_basis: VARCHAR(200)  // Art. CE que fundamenta
  issuing_authority: VARCHAR(200)
  boe_url: VARCHAR(500)  // URL oficial BOE/BOJA
  eur_lex_url: VARCHAR(500)  // URL EUR-Lex si UE
  full_text_hash: VARCHAR(64)  // SHA-256 para detectar cambios
  tenant_id: INT  // NULL = norma global, NOT NULL = norma privada tenant
  created: TIMESTAMP NOT NULL
  updated: TIMESTAMP NOT NULL
}

Relaciones Entre Normas (Edges del Grafo)
Schema legal_norm_relation {
  id: BIGSERIAL PRIMARY KEY
  source_norm_id: INT REFERENCES legal_norm(id)
  target_norm_id: INT REFERENCES legal_norm(id)
  relation_type: ENUM(
    'deroga_total',      // source deroga completamente target
    'deroga_parcial',    // source deroga artículos específicos de target
    'modifica',          // source cambia redacción de target
    'desarrolla',        // source es reglamento de desarrollo de target
    'transpone',         // source transpone directiva UE target
    'cita',              // source referencia a target
    'complementa',       // source complementa regulación de target
    'prevalece_sobre',   // source prevalece jerárquicamente
    'es_especial_de',    // source es lex specialis de target
    'sustituye'          // source reemplaza a target en ámbito específico
  )
  affected_articles: JSON  // Artículos específicos afectados
  effective_date: DATE  // Desde cuándo aplica la relación
  metadata: JSON  // Información adicional sobre la relación
}

Proceso de Recuperación con Jerarquía (Authority-Aware Retrieval)
El proceso de retrieval del Normative Graph RAG no es un simple vector search. Es un proceso en 6 pasos que incorpora jerarquía, competencia y vigencia:
// Servicio: jaraba_lcis/src/Service/NormativeGraphRetriever.php

public function retrieve(string $query, LegalContext $context): NormativeResult {
  // PASO 1: Vector Search inicial (semántico)
  $candidates = $this->qdrant->search(
    collection: 'jaraba_legal_norms',
    query_vector: $this->embed($query),
    limit: 30,  // Sobre-recuperar para filtrar después
    filter: $this->buildTenantFilter($context->tenantId)
  );

  // PASO 2: Filtro de Vigencia Temporal
  $vigentes = array_filter($candidates, function($norm) use ($context) {
    return $norm->effective_date <= $context->queryDate
      && ($norm->expiry_date === null || $norm->expiry_date > $context->queryDate)
      && $norm->status !== 'derogada_total';
  });

  // PASO 3: Filtro de Competencia Territorial
  $competentes = $this->filterByCompetence(
    $vigentes, $context->subjectArea, $context->territory
  );

  // PASO 4: Authority-Aware Ranking
  // Combinar score semántico con hierarchy_weight
  foreach ($competentes as &$norm) {
    $norm->final_score = 
      ($norm->semantic_score * 0.6)  // Relevancia semántica
      + ($norm->hierarchy_weight * 0.25)  // Autoridad jerárquica
      + ($this->recencyBonus($norm) * 0.15);  // Norma más reciente
  }
  usort($competentes, fn($a,$b) => $b->final_score <=> $a->final_score);

  // PASO 5: Graph Traversal para Contexto Jerárquico
  $topNorms = array_slice($competentes, 0, 10);
  $enriched = $this->enrichWithRelations($topNorms);
  // Incluye: normas que derogan, normas que desarrollan, normas que citan

  // PASO 6: Construir Contexto Normativo Estructurado
  return new NormativeResult(
    norms: $enriched,
    hierarchyChain: $this->buildHierarchyChain($enriched),
    derogationWarnings: $this->checkDerogations($enriched),
    competenceMap: $this->mapCompetences($enriched, $context)
  );
}

3.4 Capa 3: Hierarchy-Aware System Prompt
La capa 3 es el system prompt especializado que instruye al LLM para que respete los principios jurídicos. Este prompt se inyecta en TODA llamada al LLM que pase la clasificación legal de la Capa 1. Es el elemento más crítico del sistema porque define el comportamiento del LLM.
System Prompt Completo del LCIS
SYSTEM PROMPT: LEGAL COHERENCE INTELLIGENCE SYSTEM
═══════════════════════════════════════════════════

Eres el asistente jurídico de [TENANT_NAME] en la plataforma Jaraba.

## REGLAS INQUEBRANTABLES DE COHERENCIA JURÍDICA

### R1: JERARQUÍA NORMATIVA ABSOLUTA
Siempre que cites normativa, respeta esta jerarquía estricta:
  Derecho UE primario > Constitución > Ley Orgánica > Ley Ordinaria >
  Real Decreto > Orden Ministerial > Normativa autonómica > Normativa local
Si existe contradicción entre normas de distinto rango, SIEMPRE prevalece
la de rango superior. Indícalo explícitamente al usuario.

### R2: COMPETENCIA TERRITORIAL
Identifica QUIÉN tiene competencia sobre la materia consultada:
- Si es competencia EXCLUSIVA del Estado (art. 149.1 CE): cita normativa estatal.
- Si es competencia EXCLUSIVA autonómica (art. 148 CE): cita normativa autonómica.
- Si es competencia COMPARTIDA: cita bases estatales + desarrollo autonómico.
- Si el territorio del usuario es [TERRITORY]: prioriza normativa de [TERRITORY].
NUNCA apliques normativa de una CCAA a otra distinta.

### R3: VIGENCIA TEMPORAL ESTRICTA
Solo cites normas VIGENTES a fecha de hoy ([CURRENT_DATE]).
Si una norma ha sido DEROGADA, NO la cites como aplicable.
Si ha sido MODIFICADA, cita la versión VIGENTE, no la original.
Si está PENDIENTE de entrada en vigor, menciónala pero indica que
aún no es aplicable.

### R4: ESPECIALIDAD (LEX SPECIALIS)
Si existe una norma específica para la materia consultada, preválece
sobre la norma general. Ejemplo: normativa sectorial de protección de
datos sanitarios prevalece sobre LOPDGDD en el ámbito sanitario.

### R5: STRICT GROUNDING JURÍDICO
SOLO responde con información del CONTEXTO NORMATIVO proporcionado.
Si el contexto no contiene normas suficientes para responder,
responde: 'No dispongo de información normativa suficiente para
responder con seguridad jurídica. Te recomiendo consultar con un
profesional del derecho.'
NUNCA inventes artículos, leyes o jurisprudencia.

### R6: DISCLAIMER OBLIGATORIO
TODA respuesta con contenido jurídico DEBE terminar con:
'[Nota: Esta información tiene carácter orientativo y no constituye
asesoramiento jurídico profesional. Para su caso concreto, consulte
con un abogado o profesional cualificado.]'

### R7: CITACIÓN CON TRAZABILIDAD
Cada afirmación normativa DEBE incluir referencia verificable:
Formato: [Art. X de Ley Y/AAAA, de DD de mes, de materia]
Si el contexto incluye URL del BOE/BOJA, inclúyelo.

## CONTEXTO NORMATIVO RECUPERADO
[NORMATIVE_CONTEXT_INJECTED_HERE]

## METADATOS DE LA CONSULTA
- Vertical: [VERTICAL]
- Territorio usuario: [TERRITORY]
- Fecha consulta: [CURRENT_DATE]
- Ámbito material detectado: [SUBJECT_AREAS]

3.5 Capa 4: Coherence Validator (Post-Generación)
La Capa 4 verifica la respuesta generada por el LLM ANTES de entregarla al usuario. Aplica validaciones tanto deterministas (reglas fijas) como probabilísticas (NLI Entailment).
Validaciones Deterministas (Coste Cero, Instantáneas)
Validación	Regla	Acción si Falla
V1: Derogation Check	Ninguna norma citada aparece en derogated_norms_cache	BLOQUEAR. Regenerar sin norma derogada.
V2: Hierarchy Conflict	Norma de rango inferior no contradice norma de rango superior en contexto	ALERTAR. Añadir nota explícita de jerarquía.
V3: Competence Mismatch	Norma autonómica no aplicada fuera de su territorio	BLOQUEAR. Regenerar con filtro territorial.
V4: Temporal Validity	Todas las normas citadas vigentes a fecha consulta	BLOQUEAR. Eliminar cita y regenerar.
V5: Disclaimer Present	Respuesta contiene disclaimer obligatorio	INYECTAR automáticamente si ausente.
V6: Citation Format	Toda afirmación normativa tiene cita verificable	ALERTAR. Reducir confianza de respuesta.
V7: Reserved Matter	No se regula por reglamento materia reservada a ley	ALERTAR. Añadir nota de reserva de ley.

Validación NLI (Entailment Semántico)
Además de las validaciones deterministas, el sistema ejecuta una verificación NLI (Natural Language Inference) que comprueba que cada afirmación de la respuesta está respaldada por el contexto normativo recuperado. Esto es idéntico al NLI Entailment Validator del doc 20260110h-KB_AI_Nativa pero especializado para contenido legal.
// Servicio: jaraba_lcis/src/Service/LegalCoherenceValidator.php

public function validate(
  string $response,
  NormativeResult $normativeContext,
  LegalContext $queryContext
): ValidationResult {
  $result = new ValidationResult();

  // 1. Validaciones deterministas (instantáneas)
  $result->addChecks($this->deterministicChecks($response, $normativeContext));

  // 2. Si hay bloqueantes, NO continuar con NLI (ahorrar coste)
  if ($result->hasBlockers()) {
    return $result;
  }

  // 3. Extraer claims legales de la respuesta
  $claims = $this->extractLegalClaims($response);

  // 4. Verificar entailment de cada claim
  foreach ($claims as $claim) {
    $entailment = $this->nliCheck($claim, $normativeContext->getRelevantChunks($claim));
    if ($entailment->label === 'contradiction') {
      $result->addBlocker('NLI_CONTRADICTION', $claim->text, $entailment->evidence);
    } elseif ($entailment->label === 'neutral' && $entailment->score < 0.5) {
      $result->addWarning('NLI_UNGROUNDED', $claim->text);
    }
  }

  // 5. Calcular Legal Coherence Score
  $result->setCoherenceScore($this->calculateCoherenceScore($result));

  return $result;
}

3.6 Capa 5: Disclaimer y Citación Automática
La última capa es la más simple pero la más importante desde el punto de vista de responsabilidad legal de la plataforma. Garantiza que TODA respuesta con contenido jurídico incluya:
1. Disclaimer de no-asesoramiento: Texto legal fijo que exonera a la plataforma de responsabilidad por uso de la información como asesoramiento profesional. Este disclaimer es configurable por tenant (un despacho de abogados puede personalizarlo) pero NUNCA puede eliminarse.
2. Citas con trazabilidad: Cada norma citada incluye: título, artículo, fecha, y URL oficial (BOE/BOJA/EUR-Lex). Esto permite al usuario verificar la información de forma independiente.
3. Legal Coherence Score: Un indicador visual (0-100) que muestra el nivel de confianza del sistema en la coherencia de la respuesta. Score < 60 muestra advertencia explícita.
4. Metadatos de contexto: Ámbito territorial aplicado, fecha de referencia, áreas normativas consultadas.
 
4. Skill Claude Code: jaraba-legal-coherence
Este Skill se integra en el pipeline de Claude Code (doc 178) y se activa automáticamente cuando Claude Code trabaja en cualquier componente que toque contenido legal. Es el ‘cerebro jurídico’ que el agente de desarrollo consulta para asegurar coherencia normativa en el código que genera.
4.1 Contenido Completo del SKILL.md
# SKILL: jaraba-legal-coherence
# Activación: Tareas que involucren contenido legal, normativa,
# compliance, asesoramiento, o el vertical Jaraba Lex.

## IDENTIDAD
Este skill garantiza coherencia jurídica en TODO output de IA del
ecosistema Jaraba. Aplica transversalmente a los 5 verticales y es
OBLIGATORIO para el vertical Jaraba Lex.

## PRINCIPIOS JURÍDICOS MODELADOS

### 1. JERARQUÍA NORMATIVA (Art. 9.3 CE)
Pirámide: Derecho UE > CE > LO > Ley > RD > OM > Norma auton. > Norma local
REGLA: Norma inferior NUNCA contradice superior. Si detectas conflicto,
la norma superior prevalece SIEMPRE. Excepción: competencia exclusiva CCAA.

### 2. COMPETENCIA TERRITORIAL (Título VIII CE)
- Art. 149.1 CE: Competencias exclusivas Estado
- Art. 148 CE: Competencias asumibles por CCAA
- Estatutos Autonomía: Competencias asumidas
REGLA: SIEMPRE identificar competente antes de citar normativa.
NUNCA aplicar normativa autonómica de una CCAA en otra.

### 3. VIGENCIA TEMPORAL (Art. 2.2 CC)
Normas tienen: publicación, entrada vigor, modificaciones, derogación.
REGLA: Solo citar normas VIGENTES. Si derogada, indicar y citar sustituta.
Usar SIEMPRE fecha actual del sistema como referencia temporal.

### 4. ESPECIALIDAD (Lex Specialis)
Norma especial prevalece sobre general en su ámbito.
REGLA: Buscar siempre norma más específica antes de citar la general.

### 5. PRIMACÍA DERECHO UE (Costa/ENEL, 1964)
Derecho UE prevalece sobre Derecho nacional en competencias comunitarias.
Reglamentos UE: directamente aplicables.
Directivas: requieren transposición, pero efecto directo vertical si no transpuestas.

## IMPLEMENTACIÓN EN CÓDIGO

### Entidades Obligatorias
- legal_norm: Content entity con revisions (schema completo en doc 179)
- legal_norm_relation: Relaciones entre normas (grafo)
- legal_coherence_log: Audit trail de validaciones

### Servicios
- LegalIntentClassifier: Detecta intent legal en queries
- NormativeGraphRetriever: RAG jerárquico con authority-aware ranking
- LegalCoherenceValidator: Validación post-generación (7 checks + NLI)
- LegalDisclaimerService: Inyección automática de disclaimers
- NormativeUpdateService: Actualización de vigencia y derogaciones

### Patrón de Integración
El LCIS se inyecta como middleware en el pipeline RAG existente:

  Query → LegalIntentClassifier → [Si legal] → NormativeGraphRetriever
    → Hierarchy-Aware Prompt → LLM → LegalCoherenceValidator
    → [Si pasa] → LegalDisclaimerService → Response
    → [Si falla] → Regenerar con constraints adicionales (max 2 retries)

### Testing
OBLIGATORIO: Test suite con 50+ casos que cubran:
- 10 casos de jerarquía normativa (norma inferior vs superior)
- 10 casos de competencia territorial (Estado vs CCAA vs Local)
- 10 casos de vigencia temporal (derogadas, modificadas, pendientes)
- 10 casos de especialidad (general vs especial)
- 5 casos de primacía UE
- 5 casos de disclaimer obligatorio

### EU AI Act Compliance (Reglamento UE 2024/1689)
El LCIS clasifica como sistema de IA de RIESGO LIMITADO (Categoría 3)
ya que proporciona información legal orientativa. Obligaciones:
- Transparencia: Usuario SIEMPRE sabe que interactúa con IA
- Disclaimer: Obligatorio y no eliminable
- Logging: Toda interacción registrada para audit (Art. 12)
- Human oversight: Recomendación de consulta profesional SIEMPRE

### Ámbitos Materiales por Vertical
- Empleabilidad: Laboral, Seguridad Social, Formación, Empleo
- Emprendimiento: Mercantil, Fiscal, Subvenciones, Autonomía
- AgroConecta: Agroalimentario, Sanitario, PAC, Etiquetado, Denominaciones
- ComercioConecta: Consumo, Comercio electrónico, Fiscal, Protección datos
- ServiciosConecta: Colegios profesionales, Contratación, eIDAS, Fiscal
- Jaraba Lex: TODOS los ámbitos (vertical especializado en derecho)
 
5. Módulo Drupal: jaraba_lcis
5.1 Estructura del Módulo
web/modules/custom/jaraba_lcis/
├── jaraba_lcis.info.yml
├── jaraba_lcis.services.yml
├── jaraba_lcis.permissions.yml
├── jaraba_lcis.routing.yml
├── config/
│   ├── install/
│   │   ├── jaraba_lcis.settings.yml
│   │   └── taxonomy.vocabulary.legal_subject_area.yml
│   └── schema/
│       └── jaraba_lcis.schema.yml
├── src/
│   ├── Entity/
│   │   ├── LegalNorm.php
│   │   ├── LegalNormRelation.php
│   │   └── LegalCoherenceLog.php
│   ├── Service/
│   │   ├── LegalIntentClassifier.php
│   │   ├── NormativeGraphRetriever.php
│   │   ├── HierarchyAwarePromptBuilder.php
│   │   ├── LegalCoherenceValidator.php
│   │   ├── LegalDisclaimerService.php
│   │   ├── NormativeUpdateService.php
│   │   ├── LegalCoherencePipeline.php  // Orquestador
│   │   └── SpanishLegalOntology.php   // Ontología derecho ES
│   ├── Model/
│   │   ├── LegalIntent.php
│   │   ├── NormativeResult.php
│   │   ├── ValidationResult.php
│   │   ├── LegalContext.php
│   │   └── HierarchyChain.php
│   ├── Plugin/
│   │   └── QueueWorker/
│   │       ├── NormativeUpdateWorker.php
│   │       └── NormVectorizationWorker.php
│   ├── Controller/
│   │   └── LcisApiController.php
│   └── EventSubscriber/
│       └── LcisRagMiddleware.php  // Se inyecta en pipeline RAG
└── tests/
    ├── src/Unit/
    │   ├── LegalIntentClassifierTest.php
    │   ├── LegalCoherenceValidatorTest.php
    │   └── HierarchyChainTest.php
    └── src/Kernel/
        ├── NormativeGraphRetrieverTest.php
        └── LegalCoherencePipelineTest.php

5.2 Servicio Orquestador: LegalCoherencePipeline
El servicio principal que orquesta las 5 capas en un flujo unificado. Se inyecta como event subscriber en el pipeline RAG existente del ecosistema.
// jaraba_lcis/src/Service/LegalCoherencePipeline.php
declare(strict_types=1);

namespace Drupal\jaraba_lcis\Service;

use Drupal\jaraba_ai\Event\RagQueryEvent;
use Drupal\jaraba_ai\Event\RagResponseEvent;
use Drupal\jaraba_lcis\Model\LegalContext;
use Psr\Log\LoggerInterface;

class LegalCoherencePipeline {
  private const MAX_RETRIES = 2;
  private const MIN_COHERENCE_SCORE = 0.6;

  public function __construct(
    private LegalIntentClassifier $intentClassifier,
    private NormativeGraphRetriever $graphRetriever,
    private HierarchyAwarePromptBuilder $promptBuilder,
    private LegalCoherenceValidator $validator,
    private LegalDisclaimerService $disclaimerService,
    private LoggerInterface $logger,
  ) {}

  /**
   * Procesa una consulta a través del pipeline LCIS.
   *
   * Se invoca como middleware del pipeline RAG.
   * Solo se activa si LegalIntentClassifier detecta intent legal.
   */
  public function process(RagQueryEvent $event): void {
    $query = $event->getQuery();
    $context = $this->buildLegalContext($event);

    // CAPA 1: Clasificación de intent legal
    $intent = $this->intentClassifier->classify($query, $context->vertical);
    if ($intent->isNonLegal()) {
      return; // No activar LCIS
    }

    // CAPA 2: Recuperación normativa con grafo
    $normativeResult = $this->graphRetriever->retrieve($query, $context);

    // CAPA 3: Construir prompt con jerarquía
    $legalPrompt = $this->promptBuilder->build($normativeResult, $context);
    $event->appendSystemPrompt($legalPrompt);
    $event->appendContext($normativeResult->toPromptContext());

    // Almacenar para validación post-generación
    $event->setMetadata('lcis_normative_result', $normativeResult);
    $event->setMetadata('lcis_legal_context', $context);
    $event->setMetadata('lcis_intent', $intent);
  }

  /**
   * Valida la respuesta generada (post-LLM).
   */
  public function validateResponse(RagResponseEvent $event): void {
    $normativeResult = $event->getMetadata('lcis_normative_result');
    if (!$normativeResult) return; // LCIS no se activó

    $response = $event->getResponse();
    $context = $event->getMetadata('lcis_legal_context');

    // CAPA 4: Validación de coherencia
    $validation = $this->validator->validate($response, $normativeResult, $context);

    if ($validation->hasBlockers()) {
      $this->logger->warning('LCIS blockers detected', [
        'blockers' => $validation->getBlockers(),
        'query' => $event->getQuery(),
      ]);
      // Solicitar regeneración con constraints adicionales
      $event->requestRegeneration($validation->getConstraints());
      return;
    }

    // CAPA 5: Añadir disclaimer y metadatos
    $enrichedResponse = $this->disclaimerService->enrich(
      $response, $normativeResult, $validation, $context
    );
    $event->setResponse($enrichedResponse);

    // Logging para audit y mejora continua
    $this->logInteraction($event, $validation);
  }
}
 
6. Alimentación de la Base Normativa
6.1 Fuentes de Datos Normativas
Fuente	Contenido	Actualización	API/Método
BOE (boe.es)	Toda normativa estatal. Disposiciones, Análisis jurídico.	Diaria	BOE Open Data API + RSS
BOJA (juntadeandalucia.es)	Normativa autonómica andaluza	Diaria	Web scraping estructurado
EUR-Lex (eur-lex.europa.eu)	Normativa UE: Reglamentos, Directivas, Tratados	Diaria	SPARQL endpoint + REST API
Noticias Jurídicas (noticias.juridicas.com)	Legislación consolidada con análisis vigencia	Semanal	Web scraping con respeto robots.txt
CENDOJ (poderjudicial.es)	Jurisprudencia TS, AN, TSJ	Semanal	API búsqueda + RSS
Tenant Knowledge	Normativa específica subida por el tenant profesional	On-demand	Knowledge Training System (doc 130)

6.2 Pipeline de Ingesta Normativa
La ingesta de normativa sigue un pipeline automatizado que garantiza la calidad y estructura de los datos:
// Pipeline de ingesta: NormativeIngestionPipeline

PASO 1: FETCH → Descargar nueva normativa de fuentes
  - BOE API: GET /api/boe/sumario/{fecha}
  - EUR-Lex: SPARQL query cambios últimas 24h
  - BOJA: RSS feed + web scraping selectivo
  - Frecuencia: Cron cada 6 horas

PASO 2: PARSE → Extraer estructura de la norma
  - Título, tipo normativo, fecha publicación/vigor
  - Artículos individuales como chunks
  - Disposiciones transitorias, derogatorias, finales
  - Metadata: rango, ámbito territorial, materia

PASO 3: RELATE → Detectar relaciones con normas existentes
  - Parser de disposiciones derogatorias: detectar qué deroga
  - Parser de disposiciones finales: detectar qué modifica
  - Cross-reference detection: citas a otras normas
  - LLM-assisted: para relaciones implícitas (Gemini Flash)

PASO 4: VECTORIZE → Generar embeddings para vector search
  - text-embedding-3-small (OpenAI) por chunk
  - Metadata enriquecida: hierarchy_level, weight, scope
  - Collection: jaraba_legal_norms (Qdrant)

PASO 5: VALIDATE → Verificar coherencia de la ingesta
  - Check: ¿las derogaciones marcaron correctamente las normas afectadas?
  - Check: ¿los ámbitos territoriales son consistentes?
  - Check: ¿hay normas huérfanas sin relaciones?
  - Alerta automática si validación falla > umbral

6.3 Paquete Normativo Inicial
Para el lanzamiento del LCIS, se requiere un paquete normativo base que cubra los ámbitos materiales de los 5 verticales. Este paquete no necesita ser exhaustivo: el sistema está diseñado para crecer incrementalmente. Pero el paquete inicial debe cubrir las normas fundamentales.
Ámbito	Normas Fundamentales (Paquete Inicial)	Cantidad Est.
Constitucional	CE 1978, Tratado UE, CEDH, Carta Derechos Fundamentales UE	~15
Laboral (Empleabilidad)	ET, LGSS, LISOS, Ley Empleo, RD formación, Ley ETTs	~30
Mercantil (Emprendimiento)	Código Comercio, LSC, Ley Emprendedores, Ley Autonom. Andalucía	~25
Fiscal (transversal)	LGT, LIRPF, LIVA, LIS, LITPAJD, normativa auton. Andalucía	~35
Agroalimentario (AgroConecta)	PAC, Ley Cadena Alimentaria, normas etiquetado, denominaciones	~20
Consumo (ComercioConecta)	LGDCU, LSSI-CE, Ley Comercio Andalucía, RGPD, LOPDGDD	~25
Profesional (ServiciosConecta)	Leyes Colegios Prof., eIDAS, Ley 6/2020 firma, normas deontológicas	~20
Subvenciones (transversal)	Ley 38/2003, normas FEDER, FSE+, PIIL Andalucía	~15
TOTAL PAQUETE INICIAL		~185 normas
 
7. Vertical Jaraba Lex: Especificación
Jaraba Lex es el vertical especializado en servicios legales. Opera sobre la infraestructura de ServiciosConecta (docs 82-99) pero añade una capa de inteligencia jurídica profunda proporcionada por el LCIS. Es el vertical donde la coherencia normativa pasa de ser un guardrail defensivo a ser el producto principal.
7.1 Propuesta de Valor Diferencial
Funcionalidad	Competidores (K+, GenIA-L...)	Jaraba Lex
Búsqueda normativa	Sí (BD propietaria masiva)	Sí (Graph RAG con jerarquía)
Coherencia jerárquica automática	NO (resultado plano)	SÍ (validación 5 capas)
Gestión de despacho integrada	NO (herramienta aislada)	SÍ (booking, facturación, buzón, firma)
Multi-territorial automático	Limitado	SÍ (detecta CCAA y aplica normativa)
Disclaimer automático configurable	Básico	SÍ (por tenant, por ámbito, no eliminable)
Triaje IA de casos	NO	SÍ (doc 91 + LCIS)
Precio accesible rural	200-500€/mes	39-99€/mes (plan Growth/Pro)
Formación continua integrada	NO	SÍ (LMS Empleabilidad aplicado a abogados)

7.2 Componentes Específicos de Jaraba Lex
7.2.1 Copilot Legal con LCIS
Extiende el Copilot de Servicios (doc 93) con las 5 capas LCIS activadas por defecto. A diferencia de los otros verticales donde LCIS es un guardrail, en Jaraba Lex es el core del producto. El Copilot Legal puede:
Investigación normativa asistida: Buscar normativa aplicable a un caso con consciencia de jerarquía, competencia territorial y vigencia. Resultado estructurado con cadena jerárquica.
Redacción asistida grounded: Redactar escritos con citas normativas verificadas. Cada cita vinculada a fuente oficial.
Análisis de viabilidad: Evaluar la viabilidad de una acción legal basándose en normativa vigente y jurisprudencia disponible. Con Legal Coherence Score visible.
Alertas normativas: Notificar al abogado cuando una norma relevante para sus casos activos se modifica o deroga.
7.2.2 Base de Conocimiento Legal Expandida
Los tenants de Jaraba Lex acceden a una base normativa significativamente mayor que otros verticales, incluyendo jurisprudencia del CENDOJ. El sistema de Knowledge Training (doc 130) permite a cada despacho añadir sus propias fuentes: circulares internas, modelos de escritos, jurisprudencia relevante para su especialidad.
7.2.3 Planes de Precios Jaraba Lex
Plan	Precio/mes	Consultas IA/mes	Base Normativa	Features
Starter	39€	50	Paquete inicial (~185 normas)	Copilot básico, disclaimer, 1 usuario
Growth	79€	200	Inicial + CCAA del tenant	Copilot completo, triaje IA, 3 usuarios
Pro	149€	500	Completa + jurisprudencia	Todo + alertas normativas, 10 usuarios
Enterprise	A medida	Ilimitadas	Completa + custom + privada	Todo + API, SSO, SLA, ilimitado
 
8. Automatizaciones ECA
8.1 Reglas ECA del LCIS
ID Regla	Evento	Condición	Acción
ECA-LCIS-001	Norma nueva ingestada	Contiene disposición derogatoria	Actualizar status de normas afectadas + invalidar cache
ECA-LCIS-002	Coherence Score < 0.6	Consulta con intent LEGAL_DIRECT	Loguear, alertar admin, ofrecer consulta profesional
ECA-LCIS-003	Validación BLOQUEANTE	2+ regeneraciones fallidas	Escalar a respuesta estándar: 'Consulte profesional'
ECA-LCIS-004	Cron diario 06:00	Siempre	Ejecutar pipeline ingesta BOE/BOJA/EUR-Lex
ECA-LCIS-005	Norma modificada	Afecta a casos activos del tenant	Notificar abogado/profesional vía email + dashboard
ECA-LCIS-006	Tenant Jaraba Lex creado	Plan Growth o superior	Activar base normativa CCAA específica + jurisprudencia
ECA-LCIS-007	Usuario intenta desactivar disclaimer	Siempre	BLOQUEAR. Disclaimer NO eliminable (compliance EU AI Act)

9. APIs REST del LCIS
Método	Endpoint	Descripción	Auth
POST	/api/v1/lcis/classify	Clasificar intent legal de una query	OAuth 2.1
POST	/api/v1/lcis/retrieve	Recuperar normativa relevante con jerarquía	OAuth 2.1
POST	/api/v1/lcis/validate	Validar coherencia de una respuesta generada	OAuth 2.1
GET	/api/v1/lcis/norms/{id}	Obtener norma con relaciones	OAuth 2.1
GET	/api/v1/lcis/norms/search	Buscar normas por texto + filtros	OAuth 2.1
GET	/api/v1/lcis/hierarchy/{norm_id}	Obtener cadena jerárquica de una norma	OAuth 2.1
POST	/api/v1/lcis/norms	Crear norma (admin o ingesta automática)	OAuth 2.1 + admin
PATCH	/api/v1/lcis/norms/{id}/status	Actualizar estado vigencia de norma	OAuth 2.1 + admin
GET	/api/v1/lcis/stats	Estadísticas del LCIS (coherence scores, usage)	OAuth 2.1
GET	/api/v1/lcis/alerts/{tenant_id}	Alertas normativas para un tenant	OAuth 2.1
 
10. Testing y Quality Assurance
10.1 Suite de Tests de Coherencia Legal
El LCIS requiere una suite de tests específica que valide los principios jurídicos. Estos tests son ADICIONALES a los tests estándar de PHPUnit/Kernel del ecosistema.
Casos de Test Obligatorios
Cat.	Test Case	Input	Expected Output	Principio
H1	Jerarquía CE vs RD	Pregunta donde RD contradice CE	Respuesta prioriza CE, nota sobre contradicción	Jerarquía
H2	LO vs Ley Ordinaria	Materia orgánica regulada por ley ordinaria	Alerta: materia reservada a LO	Reserva ley
H3	Reglamento UE vs Ley ES	Conflicto entre Reglamento UE y ley española	Prevalece Reglamento UE	Primacía UE
C1	Laboral: Estado vs CCAA	Consulta laboral citando norma autonómica	Corregir: laboral = competencia estatal	Competencia
C2	Urbanismo: CCAA correcta	Consulta urbanismo en Andalucía	Citar LOUA, no ley urbanística Madrid	Competencia
C3	CCAA cruzada	Aplicar norma Cataluña a Andalucía	BLOQUEAR. Error territorial.	Competencia
T1	Norma derogada	Citar ley derogada como vigente	BLOQUEAR. Citar norma sustituta.	Vigencia
T2	Norma pendiente	Ley publicada pero no en vigor	Mencionar pero indicar no aplicable aún	Vigencia
T3	Versión modificada	Artículo con redacción antigua	Citar versión vigente actual	Vigencia
E1	Lex specialis	LOPD vs norma sectorial salud	Priorizar norma especial sanitaria	Especialidad
D1	Disclaimer presente	Cualquier respuesta legal	Disclaimer obligatorio al final	Compliance
D2	Disclaimer no eliminable	Tenant intenta quitar disclaimer	BLOQUEAR eliminación	Compliance
 
11. Roadmap de Implementación
11.1 Fases de Desarrollo
Fase	Semanas	Entregables	Horas Est.	Coste Est.
F1: Fundamentos	1-3	Entidades legal_norm + legal_norm_relation, ontología básica, paquete normativo inicial (185 normas)	120-160h	5.400-7.200€
F2: Pipeline LCIS	4-7	5 capas implementadas, servicios PHP, integración RAG middleware, system prompt	160-220h	7.200-9.900€
F3: Ingesta Automática	8-9	Conectores BOE/BOJA/EUR-Lex, pipeline de actualización, ECA rules	80-110h	3.600-4.950€
F4: Skill + Testing	10-11	Skill Claude Code, suite 50+ tests, validación end-to-end	60-80h	2.700-3.600€
F5: Jaraba Lex Vertical	12-16	Copilot Legal, planes pricing, alertas normativas, onboarding	120-160h	5.400-7.200€
TOTAL	16 sem.	Sistema completo	540-730h	24.300-32.850€

11.2 Dependencias Críticas
Dependencia	Estado	Impacto si No Disponible
Qdrant Vector DB	Disponible (presupuestado)	BLOQUEANTE: Sin vector search no hay RAG legal
Pipeline RAG existente (doc 20260110h)	Especificado	BLOQUEANTE: LCIS es middleware del pipeline RAG
BOE Open Data API	Disponible públicamente	Parcial: se puede usar web scraping como fallback
Claude/Gemini API	Disponible (presupuestado)	BLOQUEANTE: Sin LLM no hay generación
Knowledge Training (doc 130)	Especificado	Parcial: base normativa funciona sin KB de tenant
 
12. Conclusión
El Sistema de Coherencia Legal Inteligente (LCIS) transforma un problema estructural de las IAs generativas —la incoherencia jurídica— en un diferenciador competitivo de primera magnitud para el Ecosistema Jaraba.
Los 5 Pilares del LCIS
1. Jerarquía normativa modelada como grafo, no como texto plano. Esto permite razonamiento estructural que ningún RAG convencional puede ofrecer. Es el mismo principio que propone la investigación de vanguardia en Graph RAG for Legal Norms (De Martim, 2025) y que recomiendan Linna & Linna (2026) en Springer como requisito para IA legal fiable.
2. Authority-aware retrieval con peso jerárquico. No basta con encontrar normas semánticamente relevantes; hay que priorizarlas por rango, competencia y vigencia. Esto replica el razonamiento que todo jurista aplica automáticamente.
3. Validación post-generación en 5 capas. El LLM genera, pero el sistema verifica. 7 checks deterministas + NLI semántico. El disclaimer no es una formalidad: es la última línea de defensa legal de la plataforma.
4. Transversalidad + especialización. El LCIS opera como guardrail en los 5 verticales existentes Y como core product en Jaraba Lex. Una sola inversión, doble retorno.
5. EU AI Act ready from day one. Con logging obligatorio, transparencia, disclaimer no eliminable y human oversight recomendado en cada respuesta, el LCIS cumple con las obligaciones del Reglamento UE 2024/1689 para sistemas de riesgo limitado desde su lanzamiento.

ACCIÓN INMEDIATA: Fase 1 (Fundamentos) puede comenzar hoy con las especificaciones de este documento. Las entidades, la ontología y el paquete normativo inicial no tienen dependencias externas bloqueantes. Cada norma ingestada hoy es una respuesta más coherente mañana.

——— Fin del Documento 179 ———
