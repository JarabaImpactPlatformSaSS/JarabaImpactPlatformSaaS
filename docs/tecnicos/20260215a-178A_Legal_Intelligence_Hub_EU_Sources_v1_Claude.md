
ANEXO A: FUENTES EUROPEAS
Ampliación del Legal Intelligence Hub
TJUE + TEDH + EUR-Lex + Comisión Europea + Órganos Reguladores UE
Vertical ServiciosConecta — JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Febrero 2026
Código:	178A_Legal_Intelligence_Hub_EU_Sources
Documento base:	178_ServiciosConecta_Legal_Intelligence_Hub_v1
Prioridad:	ALTA — Completa cobertura normativa
 
 
1. Justificación: Por Qué las Fuentes Europeas son Imprescindibles
El documento base (178) cubre exhaustivamente las fuentes nacionales españolas, pero para un profesional del derecho, la asesoría fiscal o la gestoría, la dimensión europea no es un complemento sino una necesidad estructural. España, como Estado miembro de la UE y del Consejo de Europa, está vinculada por un ordenamiento jurídico supranacional que tiene primacía sobre el derecho interno.
1.1 Impacto Real en la Práctica Profesional Diaria
Profesional	Ejemplo Práctico	Fuente Europea Necesaria
Abogado civil/familia	Custodia transfronteriza de menores	TJUE: Reglamento Bruselas II bis (2201/2003), sentencias sobre competencia judicial
Abogado laboral	Despido de trabajadora embarazada	TJUE: Directiva 92/85/CEE, sentencias sobre discriminación por maternidad
Abogado penal	Euroorden / extradición UE	TJUE: Decisión Marco 2002/584/JAI, TEDH: art. 6 CEDH (juicio justo)
Asesor fiscal	Operación intracomunitaria IVA	TJUE: Directivas IVA, sentencias Halifax, Kittel sobre fraude carrusel
Asesor fiscal	Impuesto de Sucesiones no residente	TJUE: C-127/12 (Comisión vs España) sobre libre circulación capitales
Gestoría	Justificación subvención FSE+	Reglamento (UE) 2021/1060 y 2021/1057 sobre fondos estructurales
Gestoría	Protección datos empleados	RGPD (UE 2016/679) + Directrices EDPB + Resoluciones AEPD transponiendo
Abogado DDHH	Condiciones de detención	TEDH: art. 3 CEDH, sentencias contra España (N.D. y N.T.)
Asesor mercantil	Ayudas de Estado a empresa pública	Comisión Europea: Decisiones de ayudas de Estado (art. 107 TFUE)
Abogado consumo	Cláusulas abusivas en hipotecas	TJUE: C-415/11 (Aziz), C-421/14, Directiva 93/13/CEE
Como se observa, prácticamente todas las áreas de práctica profesional requieren acceso a normativa y jurisprudencia europea. Un Legal Intelligence Hub sin esta dimensión sería como un GPS sin autopistas: funcional pero incompleto para el uso real.
 
2. Catálogo de Fuentes Europeas
2.1 Fuentes Principales
Fuente	URL / API	Contenido	Acceso Técnico	Prioridad
CURIA (TJUE)	curia.europa.eu	Sentencias y autos del Tribunal de Justicia y Tribunal General desde 1953	Web scraping + EUR-Lex SPARQL (sector 6)	CRÍTICA
EUR-Lex (Cellar)	publications.europa.eu/webapi/rdf/sparql	Legislación UE completa: Reglamentos, Directivas, Decisiones, DOUE	SPARQL endpoint + REST API (Open Data)	CRÍTICA
HUDOC (TEDH)	hudoc.echr.coe.int	Jurisprudencia del Tribunal Europeo de Derechos Humanos	REST API JSON con filtros por país, artículo, fecha	ALTA
DOUE	eur-lex.europa.eu/oj	Diario Oficial de la UE (series L y C)	Vía EUR-Lex SPARQL/REST	ALTA
Comisión Europea	ec.europa.eu/competition	Decisiones de competencia, ayudas de Estado, concentraciones	Web scraping + APIs específicas DG COMP	MEDIA
EDPB	edpb.europa.eu	Directrices, opiniones y decisiones vinculantes RGPD	Web scraping (sin API formal)	ALTA
EBA/ESMA/EIOPA	eba.europa.eu / esma.europa.eu	Regulación financiera: directrices, Q&A, opiniones	Web scraping + RSS feeds	MEDIA
Abogado General TJUE	Vía CURIA/EUR-Lex	Conclusiones del Abogado General (orientan sentencias)	SPARQL endpoint EUR-Lex	ALTA
2.2 Detalle Técnico de Acceso por Fuente
2.2.1 EUR-Lex / Cellar (SPARQL + REST)
EUR-Lex es la fuente más rica y mejor estructurada. El repositorio Cellar ofrece acceso abierto mediante SPARQL endpoint y REST API bajo la licencia de reutilización de la Oficina de Publicaciones de la UE.
SPARQL Endpoint: https://publications.europa.eu/webapi/rdf/sparql
Modelo de datos: CDM (Common Data Model) basado en FRBR + ELI (European Legislation Identifier)
Identificadores: CELEX numbers (ej: 32016R0679 = RGPD, 62011CJ0415 = Sentencia Aziz)
Tipos de recursos accesibles vía SPARQL:
•	Directivas (DIR, DIR_IMPL, DIR_DEL)
•	Reglamentos (REG, REG_IMPL, REG_DEL, REG_FINANC)
•	Decisiones (DEC, DEC_IMPL, DEC_DEL)
•	Jurisprudencia TJUE (sector 6 CELEX: sentencias, autos, conclusiones AG)
•	Acuerdos internacionales, recomendaciones, dictámenes
// Ejemplo SPARQL: Directivas vigentes sobre IVA
PREFIX cdm: <http://publications.europa.eu/ontology/cdm#>
SELECT ?work ?celex ?title WHERE {
  ?work cdm:work_has_resource-type
    <http://pub.../resource-type/DIR>.
  ?work cdm:resource_legal_id_celex ?celex.
  ?work cdm:resource_legal_in-force 'true'.
  ?expr cdm:expression_belongs_to_work ?work.
  ?expr cdm:expression_uses_language
    <http://pub.../language/SPA>.
  ?expr cdm:expression_title ?title.
  FILTER(CONTAINS(?title, 'impuesto'))
} LIMIT 50
2.2.2 CURIA / TJUE
La jurisprudencia del TJUE es accesible por dos vías complementarias:
•	Vía EUR-Lex SPARQL (sector 6 CELEX): metadatos completos + texto de sentencias publicadas
•	Vía web CURIA: formulario de búsqueda avanzada con acceso al texto íntegro y conclusiones del AG
Para sentencias anteriores a 1997 se accede exclusivamente vía EUR-Lex. A partir de 1998, CURIA ofrece acceso directo con mayor detalle procedimental.
El sistema implementará un spider dual: EUR-Lex SPARQL para metadatos y clasificación automática, y CURIA web scraping para el texto completo con formato enriquecido cuando no esté disponible vía API.
Identificador estándar: ECLI (European Case Law Identifier), ej: ECLI:EU:C:2013:164 (Aziz)
2.2.3 HUDOC / TEDH
HUDOC proporciona acceso a toda la jurisprudencia del Tribunal Europeo de Derechos Humanos. Ofrece una interfaz REST con respuestas JSON que permite filtrar por:
•	País demandado (respondentstate: ESP para España)
•	Artículo del CEDH violado (artículos 2 a 18 + Protocolos)
•	Tipo de resolución: sentencia Gran Sala, sentencia Sala, decisión de admisibilidad
•	Rango de fechas, palabras clave, importancia (1=alta, 2=media, 3=baja)
•	Idioma: sentencias contra España disponibles en español; Gran Sala en EN/FR
La API HUDOC devuelve resultados en formato JSON con campos: docname, appno (número solicitud), importance, respondent, violation, nonviolation, conclusion, y enlace al texto completo.
2.2.4 EDPB (European Data Protection Board)
El EDPB publica directrices interpretativas del RGPD que son de obligado cumplimiento práctico para asesorías y gestorías que manejan datos de clientes. No dispone de API formal, por lo que se implementará web scraping con monitorización RSS del feed de publicaciones.
Contenido relevante: directrices sobre transferencias internacionales, evaluaciones de impacto, consentimiento, videovigilancia, bases de legitimación, y decisiones vinculantes del mecanismo de coherencia.
 
3. Modelo de Datos Extendido para Fuentes Europeas
El modelo base del documento 178 (entidad legal_resolution) se extiende con campos específicos para resoluciones europeas:
3.1 Campos Adicionales
Campo	Tipo	Descripción	Aplicable a
celex_number	VARCHAR(32)	Identificador CELEX de EUR-Lex	TJUE, legislación UE
ecli	VARCHAR(64)	European Case Law Identifier	TJUE, TEDH
eli	VARCHAR(128)	European Legislation Identifier	Legislación UE
case_number	VARCHAR(64)	Número de asunto (C-415/11, 8675/15)	TJUE, TEDH
procedure_type	VARCHAR(64)	prejudicial, infraccion, anulacion, amparo	TJUE, TEDH
applicant_state	VARCHAR(3)	Estado demandante/interesado (ISO 3166-1)	TEDH, infracciones
respondent_state	VARCHAR(3)	Estado demandado (ISO 3166-1)	TEDH, infracciones
cedh_articles	JSON	Artículos del CEDH alegados/violados	TEDH
eu_legal_basis	JSON	Base jurídica UE: tratados, directivas, reglamentos	TJUE, legislación
advocate_general	VARCHAR(128)	Nombre del Abogado General + enlace conclusiones	TJUE
transposition_status	JSON	Estado de transposición en España (para Directivas)	Directivas UE
language_original	VARCHAR(3)	Idioma original de la resolución	Todas las fuentes UE
language_available	JSON	Idiomas disponibles del texto completo	Todas las fuentes UE
importance_level	INT	Nivel de importancia (1=key case, 2=media, 3=baja)	TEDH, TJUE
3.2 Taxonomías Europeas Adicionales
Vocabulario	Machine Name	Ejemplos
Tipo Procedimiento UE	eu_procedure_type	Cuestión prejudicial, Recurso por incumplimiento, Recurso de anulación, Acción por omisión
Materia Derecho UE	eu_subject_matter	Libre circulación, Competencia, Fiscalidad, Medio ambiente, Consumidores, Datos personales, Laboral
Tratado Base	eu_treaty_base	TFUE, TUE, CEDH, Carta DFUE, Acuerdos internacionales
EuroVoc	eurovoc_concept	Tesauro multilingue de la UE (5.000+ conceptos clasificados)
Artículos CEDH	cedh_articles	Art. 2 Vida, Art. 3 Tortura, Art. 5 Libertad, Art. 6 Juicio justo, Art. 8 Vida privada, Art. 10 Expresión, Art. 14 Discriminación
Órgano UE Emisor	eu_issuing_body	TJUE (Gran Sala, Sala, Pleno), Tribunal General, TEDH (Gran Sala, Sala, Comité), Comisión, Consejo, Parlamento, EDPB, EBA, ESMA
 
4. Adaptaciones del Pipeline NLP para Fuentes Europeas
4.1 Retos Específicos del Corpus Europeo
Reto	Descripción	Solución
Multilingismo	Resoluciones en EN/FR/ES con terminología jurídica específica	Embeddings multilingues (multilingual-e5-large) + traducción automática con Gemini para resúmenes
Volumen TJUE	~40.000 sentencias + ~100.000 autos desde 1953	Ingesta incremental desde EUR-Lex, priorizando sentencias desde 2000
Clasificación EuroVoc	Tesauro de 5.000+ conceptos vs taxonomías nacionales	Mapeo automático EuroVoc ↔ taxonomías internas con Gemini
Conclusiones AG	Texto extenso con razonamiento jurídico vs sentencia final	Procesamiento separado: abstract específico de conclusiones + relación con sentencia
Transposición	Vincular Directiva UE con ley española que la traspone	Cruce automático EUR-Lex (medidas nacionales) + BOE
Vigencia cruzada	Una sentencia TJUE puede invalidar doctrina nacional	Alerta crítica cuando sentencia TJUE afecta a resoluciones DGT/TEAC citadas
4.2 Prompt Extendido para Resoluciones Europeas
Eres un analista jurídico experto en Derecho de la Unión Europea
y en el Convenio Europeo de Derechos Humanos.

Analiza la siguiente resolución y proporciona:
1. TIPO: sentencia_tjue|auto_tjue|conclusiones_ag|sentencia_tedh|
        decision_tedh|directiva|reglamento|decision_comision|
        directriz_edpb
2. ORGANO: TJUE (Gran Sala|Sala X|Pleno) | TEDH (Gran Sala|
          Sala|Comité) | Comisión Europea | EDPB
3. PROCEDIMIENTO: prejudicial|infraccion|anulacion|omision|
                  amparo_cedh|interestatal
4. PARTES: demandante, demandado, estados intervinientes
5. BASE_JURIDICA: tratados, directivas, reglamentos aplicados
6. TEMAS_EUROVOC: 3-8 conceptos EuroVoc relevantes
7. RESUMEN_ES: 3-5 líneas en español (traducir si necesario)
8. RATIO_DECIDENDI: doctrina establecida (en español)
9. IMPACTO_ESPANA: cómo afecta esta resolución al derecho
                  español (transposición, aplicación directa,
                  interpretación conforme)
10. ARTICULOS_CEDH: (solo TEDH) artículos violados/no violados

REGLAS:
- Siempre proporciona RESUMEN e IMPACTO_ESPANA en español
- Cita la base jurídica con precisión (artículo y apartado)
- Si la resolución modifica jurisprudencia previa, indicálo
4.3 Embeddings Multilingues
Para el corpus europeo se utilizará un modelo de embeddings multilingue que permita buscar en español y encontrar resultados en francés o inglés:
Aspecto	Corpus Nacional (doc 178)	Corpus Europeo (este Anexo)
Modelo embeddings	text-embedding-3-large (OpenAI)	multilingual-e5-large (Open Source) o text-embedding-3-large con query traducida
Vector size	3072	1024 (e5-large) o 3072 (OpenAI)
Colección Qdrant	legal_intelligence	legal_intelligence_eu (separada para optimizar filtros)
Idioma de búsqueda	Español	Español (query se traduce automáticamente si necesario)
Idioma de resultados	Español	Multi: abstract siempre en ES, texto original en idioma fuente
 
5. Búsqueda Integrada Nacional + Europea
El profesional no necesita saber si la resolución que busca es nacional o europea. El sistema fusiona automáticamente resultados de ambas colecciones Qdrant.
5.1 Flujo de Búsqueda Unificada
Usuario: '¿Cómo afecta la libre circulación de capitales
         al Impuesto de Sucesiones para no residentes?'

1. Query a legal_intelligence (nacional):
   → DGT consultas sobre ISD no residentes
   → TEAC resoluciones sobre tributación no residentes
   → TS/TSJ sentencias sobre ISD y discriminación fiscal

2. Query a legal_intelligence_eu (europeo):
   → TJUE C-127/12 Comisión vs España (condena por ISD)
   → TJUE C-181/12 Welte (libre circulación capitales)
   → Art. 63 TFUE (libre circulación capitales)

3. Merge & Rank:
   → Relevancia semántica + frescura + importancia órgano
   → Boost: TJUE > TS > DGT para temas con primacía UE
   → Indicador visual: bandera ES/UE/CEDH en cada resultado
5.2 Indicadores de Primacía y Efecto Directo
Cuando una resolución europea contradice doctrina nacional, el sistema añade un badge de alerta:
Indicador	Significado	Ejemplo
⚠️ PRIMACÍA UE	Sentencia TJUE que invalida o modifica aplicación del derecho español	C-127/12: España condenada por discriminación en ISD a no residentes
⬆️ EFECTO DIRECTO	Norma UE que los ciudadanos pueden invocar directamente ante tribunales españoles	Art. 63 TFUE: libre circulación de capitales invocable directamente
⇄ TRANSPOSICIÓN	Directiva UE con su ley española equivalente	Directiva 93/13/CEE → TRLGDCU (RDL 1/2007)
⏳ PLAZO TRANSPOSICIÓN	Directiva con plazo de transposición vencido o próximo	Alerta cuando España no ha traspuesto en plazo
📌 INTERPRETACIÓN CONFORME	Doctrina nacional debe interpretarse a la luz de sentencia TJUE	STS que aplica doctrina Aziz (C-415/11) sobre cláusulas abusivas
 
6. Alertas Específicas para Fuentes Europeas
Tipo Alerta	Trigger	Ejemplo Real	Urgencia
Nueva sentencia TJUE con efecto en España	Sentencia TJUE indexada donde respondent=ESP o afecta normativa española citada	TJUE condena a España por incumplimiento Directiva	CRÍTICA
TJUE contradice doctrina nacional	Sentencia TJUE cuya ratio es incompatible con DGT/TEAC/TS citados en expedientes	TJUE establece exención IVA que DGT venía negando	CRÍTICA
Nueva Directriz EDPB	Publicación de directriz que afecta al tratamiento de datos del profesional	EDPB publica directriz sobre IA y datos personales	ALTA
Sentencia TEDH contra España	Sentencia TEDH con respondent=ESP	Condena por violación art. 6 CEDH en proceso judicial	ALTA
Plazo transposición Directiva	Directiva con deadline próximo (<90 días) sin ley española de transposición detectada	Directiva NIS2 con plazo vencido	MEDIA
Conclusiones AG relevantes	AG publica conclusiones en caso con impacto para España	AG recomienda condena a España por régimen fiscal	MEDIA
 
7. APIs REST Adicionales
Método	Endpoint	Descripción
GET	/api/v1/legal/search?scope=eu	Búsqueda solo en fuentes europeas
GET	/api/v1/legal/search?scope=all	Búsqueda unificada nacional + europea
GET	/api/v1/legal/eu/transposition/{celex}	Estado de transposición en España de una Directiva
GET	/api/v1/legal/eu/impact/{celex}	Impacto en derecho español de una resolución TJUE
GET	/api/v1/legal/tedh/spain	Sentencias TEDH contra España (filtrable)
GET	/api/v1/legal/eu/timeline/{topic}	Línea temporal de evolución normativa UE por tema
 
8. Impacto en Estimación de Horas y Costes
La incorporación de fuentes europeas añade complejidad al proyecto base, pero gran parte de la arquitectura (pipeline NLP, Qdrant, UI de búsqueda, sistema de alertas) ya está diseñada y se reutiliza.
8.1 Horas Adicionales
Sprint	Entregables	Horas Adicionales
Sprint 2 (ampliado)	Spider EUR-Lex SPARQL. Ingesta de legislación UE vigente. Modelo de datos extendido.	40-50h
Sprint 3 (ampliado)	Spider CURIA/TJUE. Spider HUDOC/TEDH. Embeddings multilingues.	50-65h
Sprint 4 (ampliado)	Merge & Rank nacional+europeo. Badges de primacía/efecto directo. Transposición.	35-45h
Sprint 5 (ampliado)	Spider EDPB + EBA/ESMA. Alertas europeas. Conclusiones AG.	30-40h
Sprint 6 (ampliado)	SEO/GEO para resoluciones UE (Schema.org Legislation). Testing multilingual.	25-35h

Total horas adicionales: 180-235 horas
Coste adicional: 8.100-10.575€ (a 45€/hora)

TOTAL PROYECTO CONSOLIDADO (doc 178 + Anexo A): 590-765 horas / 26.550-34.425€
8.2 Estimación de Volumen Europeo en Qdrant
Fuente	Documentos Año 1	Chunks Estimados	Espacio Qdrant
TJUE (sentencias + autos)	~15.000 (priorizando desde 2000)	~300.000	~3.6 GB
EUR-Lex (legislación vigente)	~10.000 (reglamentos + directivas vigentes)	~200.000	~2.4 GB
TEDH (HUDOC)	~5.000 (priorizando casos contra España)	~100.000	~1.2 GB
EDPB + reguladores	~500 documentos	~10.000	~0.1 GB
Conclusiones AG	~3.000	~60.000	~0.7 GB
TOTAL UE AÑO 1	~33.500 documentos	~670.000 chunks	~8.0 GB

TOTAL GLOBAL (Nacional + UE): ~260.500 documentos / ~3.010.000 chunks / ~36 GB en Qdrant
 
9. Conclusión
Este Anexo completa la visión del Legal Intelligence Hub incorporando la dimensión europea que todo profesional jurídico, fiscal y administrativo español necesita. La primacía del derecho de la UE y la vinculación al CEDH hacen que estas fuentes no sean un «nice to have» sino una necesidad funcional básica.
La arquitectura modular del documento base (pipeline NLP + Qdrant + UI + alertas) se extiende de forma natural para absorber las fuentes europeas con un sobrecoste controlado (180-235 horas adicionales, ~30% del proyecto base). La clave está en:
•	EUR-Lex SPARQL/Cellar como fuente principal: acceso abierto, estructurado y mantenido por la Oficina de Publicaciones de la UE
•	HUDOC REST API para jurisprudencia TEDH con filtrado nativo por país y artículo
•	Búsqueda unificada que fusiona resultados nacionales y europeos con indicadores visuales de primacía y efecto directo
•	Alertas cruzadas que detectan cuando una sentencia TJUE invalida doctrina nacional citada en expedientes activos
•	Traducción automática de resúmenes para que el profesional siempre lea en español independientemente del idioma de la fuente

Con esta ampliación, ServiciosConecta ofrece a Elena (y a cualquier profesional legal/fiscal/administrativo de la España rural) una herramienta que ni siquiera Aranzadi o Lefebvre ofrecen de forma integrada: búsqueda semántica unificada nacional + UE, con inserción directa en expedientes y alertas contextuales. Todo por una fracción del coste de una suscripción tradicional.

Control de Versiones
Versión	Fecha	Autor	Cambios
1.0	Febrero 2026	Claude (Anthropic) / Pepe Jaraba	Especificación técnica de fuentes europeas
——— Fin del Anexo A ———
