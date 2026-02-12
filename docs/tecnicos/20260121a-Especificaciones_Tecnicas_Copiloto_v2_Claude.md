
ESPECIFICACIONES TÉCNICAS
COPILOTO DE EMPRENDIMIENTO v2.0
Sistema de Asistencia Inteligente Hiperpersonalizado
para Validación de Modelos de Negocio
Jaraba Impact Platform
Programa Andalucía +ei V2.0
Versión 2.0 | Enero 2026
Documento de Implementación para EDI Google Antigravity
 
TABLA DE CONTENIDOS
1. Resumen Ejecutivo y Alcance
2. Arquitectura del Sistema
3. Modelo de Datos (Schemas)
4. Sistema de Prompt Dinámico
5. Biblioteca de 44 Experimentos de Validación
6. Motor de Reglas y Lógica de Negocio
7. Componentes de Interfaz (React/HTML)
8. APIs y Endpoints
9. Integración con Drupal 11
10. Plan de Implementación por Sprints
ANEXO A: JSON Schemas Completos
ANEXO B: Prompt Maestro Completo
ANEXO C: Fichas de los 44 Experimentos
 
1. RESUMEN EJECUTIVO Y ALCANCE
1.1 Objetivo del Sistema
El Copiloto de Emprendimiento v2.0 transforma el actual Tutor Jaraba (chatbot genérico) en un sistema de asistencia inteligente hiperpersonalizado que:
•	CONOCE al emprendedor: Accede a su perfil completo (DIME, carril, sector, bloqueos, fase actual)
•	ADAPTA sus respuestas: Según carril (Impulso/Acelera), nivel técnico y estado emocional
•	GUÍA la validación: Sugiere experimentos específicos de una biblioteca de 44 tests
•	TRACKEA el progreso: Actualiza el estado de validación de cada bloque del Business Model Canvas
•	APLICA protocolos: Soporte emocional automático según bloqueos detectados
1.2 Marcos Metodológicos Integrados
Marco Teórico	Autor/Fuente	Aplicación en el Sistema
Efectuación	Saras Sarasvathy	Inventario Bird in Hand, pérdida asumible
Customer Development	Steve Blank	Descubrimiento y validación de clientes
Business Model Canvas	Osterwalder - Generación de Modelos	Estructura de 9 bloques del modelo
Value Proposition Canvas	Osterwalder - Diseñando la PV	Encaje problema-solución
Testing Business Ideas	Osterwalder - Cómo Testar Ideas	Biblioteca de 44 experimentos
La Empresa Invencible	Osterwalder - The Invincible Company	Portfolio exploración/explotación
MBA Personal	Josh Kaufman	Fundamentos de negocio simplificados

1.3 Módulos a Implementar
•	Motor de Contexto Dinámico: Inyección de datos del perfil en tiempo real al prompt
•	Sistema de Modos Adaptativos: Coach Emocional, Consultor Táctico, Sparring Partner, CFO Sintético, Abogado del Diablo
•	Biblioteca de Experimentos: 44 tests clasificados por tipo, tiempo, coste, fiabilidad
•	Priorizador de Hipótesis: Matriz Importancia/Evidencia para Deseabilidad/Factibilidad/Viabilidad
•	Tablero de Validación: Dashboard visual del progreso por bloque del BMC
•	Sistema de Fichas: Test Card + Learning Card digitales con persistencia
•	Historial de Pivots: Log cronológico de decisiones y aprendizajes
 
2. ARQUITECTURA DEL SISTEMA
2.1 Diagrama de Componentes
El sistema sigue una arquitectura de microservicios integrada con el ecosistema Drupal existente:
 ┌─────────────────────────────────────────────────────────────────────────────┐ │                        CAPA DE PRESENTACIÓN                                 │ │  ┌─────────────────┐  ┌───────────────────┐  ┌────────────────────────┐    │ │  │ Chat Interface  │  │ Dashboard BMC     │  │ Herramientas HTML      │    │ │  │ (React Widget)  │  │ (React Component) │  │ (Test/Learning Cards)  │    │ │  └─────────────────┘  └───────────────────┘  └────────────────────────┘    │ └─────────────────────────────────────────────────────────────────────────────┘                                     │ ┌─────────────────────────────────────────────────────────────────────────────┐ │                           CAPA DE SERVICIOS                                 │ │  ┌───────────────────────┐  ┌───────────────────────┐  ┌─────────────────┐ │ │  │ COPILOT ENGINE        │  │ CONTEXT INJECTOR      │  │ EXPERIMENT      │ │ │  │ - Prompt Builder      │  │ - Profile Loader      │  │ LIBRARY         │ │ │  │ - Mode Selector       │  │ - History Tracker     │  │ - 44 Tests      │ │ │  │ - Response Parser     │  │ - State Manager       │  │ - Recommender   │ │ │  └───────────────────────┘  └───────────────────────┘  └─────────────────┘ │ │  ┌───────────────────────┐  ┌───────────────────────┐  ┌─────────────────┐ │ │  │ HYPOTHESIS ENGINE     │  │ VALIDATION TRACKER    │  │ RULES ENGINE    │ │ │  │ - Prioritizer         │  │ - BMC Progress        │  │ - Carril Logic  │ │ │  │ - D/F/V Classifier    │  │ - Pivot Log           │  │ - Emotion Det.  │ │ │  └───────────────────────┘  └───────────────────────┘  └─────────────────┘ │ └─────────────────────────────────────────────────────────────────────────────┘                                     │ ┌─────────────────────────────────────────────────────────────────────────────┐ │                            CAPA DE DATOS                                    │ │  ┌─────────────────┐  ┌──────────────────┐  ┌────────────────────────────┐ │ │  │ DRUPAL 11       │  │ MySQL/MariaDB    │  │ LLM API (Claude/GPT)      │ │ │  │ - User Entity   │  │ - Tablas nuevas  │  │ - Anthropic API           │ │ │  │ - Group Module  │  │ - JSON fields    │  │ - OpenAI API (fallback)   │ │ │  └─────────────────┘  └──────────────────┘  └────────────────────────────┘ │ └─────────────────────────────────────────────────────────────────────────────┘ 
2.2 Flujo de Datos Principal
Cuando un emprendedor interactúa con el Copiloto:
•	1. Usuario envía mensaje desde Chat Interface
•	2. Context Injector carga perfil completo desde Drupal (user_id, dime_score, carril, fase, bloqueos, historial)
•	3. Rules Engine analiza mensaje: detecta modo requerido, emoción presente, hipótesis sin validar
•	4. Copilot Engine construye prompt dinámico inyectando contexto + reglas de modo
•	5. LLM API procesa y genera respuesta
•	6. Response Parser extrae: acciones sugeridas, experimentos recomendados, actualizaciones de estado
•	7. Validation Tracker actualiza progreso del BMC si corresponde
•	8. Respuesta se envía al usuario con CTAs contextuales
 
3. MODELO DE DATOS
3.1 Tabla: entrepreneur_profile
Almacena el perfil completo del emprendedor incluyendo datos del diagnóstico DIME.
Campo	Tipo	Req	Descripción
id	UUID	Sí	Identificador único del emprendedor
drupal_user_id	INT	Sí	FK a la entidad User de Drupal
nombre	VARCHAR(100)	Sí	Nombre para personalización de respuestas
carril	ENUM	Sí	IMPULSO | ACELERA (asignado por DIME)
dime_score	INT(0-20)	Sí	Puntuación total del diagnóstico DIME
dime_responses	JSON	Sí	Respuestas completas del cuestionario DIME
sector	VARCHAR(100)	No	Sector/industria del proyecto
idea_descripcion	TEXT	No	Descripción de la idea de negocio
fase_actual	ENUM	Sí	INVENTARIO | VALIDACION | MVP | TRACCION
bloqueos_detectados	JSON Array	No	["miedo_precio", "tecnofobia", "impostor"]
nivel_tecnico	INT(1-5)	Sí	1=Novato, 5=Experto (derivado de DIME)
puntos_impacto	INT	Sí	Puntos acumulados en gamificación

3.2 Tabla: hypothesis
Almacena las hipótesis de negocio del emprendedor y su estado de validación.
Campo	Tipo	Req	Descripción
id	UUID	Sí	Identificador único
entrepreneur_id	UUID	Sí	FK a entrepreneur_profile
bmc_block	ENUM	Sí	VP | CS | CH | CR | RS | KR | KA | KP | C$
type	ENUM	Sí	DESIRABILITY | FEASIBILITY | VIABILITY
statement	TEXT	Sí	Declaración de la hipótesis (Creo que...)
importance_score	INT(1-5)	Sí	1=Baja, 5=Crítica para el negocio
evidence_score	INT(1-5)	Sí	1=Sin datos, 5=Validada con evidencia fuerte
status	ENUM	Sí	PENDING | TESTING | VALIDATED | INVALIDATED | PIVOTED
priority_rank	INT	No	Orden de prioridad calculado (1=más urgente)

3.3 Tabla: experiment
Registra los experimentos realizados con sus resultados (Test Card + Learning Card).
Campo	Tipo	Req	Descripción
id	UUID	Sí	Identificador único
hypothesis_id	UUID	Sí	FK a hypothesis
experiment_type_id	INT	Sí	FK a experiment_library (1-44)
test_description	TEXT	Sí	Para verificar esto, haré...
metrics_to_measure	TEXT	Sí	Y mediré...
success_criteria	TEXT	Sí	Tengo razón si... (umbral numérico)
status	ENUM	Sí	PLANNED | IN_PROGRESS | COMPLETED | ABANDONED
observations	TEXT	No	Observé que... (Learning Card)
learnings	TEXT	No	A partir de ahí aprendí que...
decision	ENUM	No	PERSEVERE | PIVOT | ZOOM_IN | ZOOM_OUT | KILL
next_actions	TEXT	No	Por lo tanto, haré...
 
3.4 Tabla: experiment_library (Catálogo de 44 Experimentos)
Catálogo maestro de tipos de experimentos basado en 'Testing Business Ideas' de Osterwalder.
Campo	Tipo	Req	Descripción
id	INT	Sí	1-44 (identificador fijo)
name_es	VARCHAR(100)	Sí	Nombre en español
category	ENUM	Sí	DISCOVERY | INTEREST | PREFERENCE | COMMITMENT
hypothesis_type	ENUM	Sí	DESIRABILITY | FEASIBILITY | VIABILITY
time_required	ENUM	Sí	HOURS | DAYS | WEEKS
cost_level	ENUM	Sí	FREE | LOW (<50€) | MEDIUM (<500€) | HIGH
evidence_strength	ENUM	Sí	WEAK | MEDIUM | STRONG
description	TEXT	Sí	Descripción detallada del experimento
how_to_execute	TEXT	Sí	Pasos para ejecutar el experimento
tools_suggested	JSON Array	No	["Typeform", "Google Forms", "Tally"]
example_andalucia	TEXT	Sí	Ejemplo adaptado al contexto Andalucía +ei
carril_recommended	ENUM	Sí	IMPULSO | ACELERA | BOTH
 
4. SISTEMA DE PROMPT DINÁMICO
4.1 Arquitectura del Prompt
El prompt se construye dinámicamente concatenando módulos según el contexto:
PROMPT_FINAL = [     SYSTEM_BASE,           // Identidad y filosofía base (constante)     CONTEXT_ENTREPRENEUR,  // Datos del perfil (inyectado desde BD)     RULES_CARRIL,          // Reglas específicas del carril (Impulso/Acelera)     RULES_MODE,            // Reglas del modo activo (Coach/Consultor/Sparring/CFO/Devil)     RULES_PHASE,           // Reglas de la fase actual (Inventario/Validación/MVP/Tracción)     EXPERIMENT_CATALOG,    // Experimentos disponibles (filtrados por contexto)     CONVERSATION_HISTORY,  // Últimos N mensajes de la sesión     USER_MESSAGE           // Mensaje actual del usuario ]
4.2 Módulo SYSTEM_BASE (Constante)
Define la identidad y filosofía del Copiloto. Es inmutable.
### 1. ROL E IDENTIDAD  Eres el "Copiloto de Negocio Jaraba", un consultor de negocios experto en  transformación digital y emprendimiento "Sin Humo". Tu misión es acompañar  a los alumnos del Programa Andalucía +ei en su viaje emprendedor.  IMPORTANTE: No eres un chatbot genérico. Tienes acceso al PERFIL COMPLETO  del emprendedor con quien hablas y DEBES adaptar cada respuesta a su  situación específica.  ### 2. TU FILOSOFÍA - EL MÉTODO JARABA "SIN HUMO"  A) EFECTUACIÓN (Bird in Hand):    - "No necesitas más recursos, necesitas usar lo que ya tienes"    - ¿Qué tienes? → Usar antes de buscar más    - ¿A quién conoces? → Empezar por tu red existente  B) CUSTOMER DEVELOPMENT (Steve Blank):    - "Ningún plan de negocio sobrevive al primer contacto con clientes"    - Salir del edificio ANTES de construir nada    - El rechazo es dato, no dolor personal  C) TESTING BUSINESS IDEAS (Osterwalder):    - Toda creencia es una hipótesis hasta que se valida con evidencia    - Experimentos baratos y rápidos primero    - Priorizar: Deseabilidad > Factibilidad > Viabilidad  ### 3. ESTILO DE COMUNICACIÓN  - DIRECTO: Ve al grano, sin rodeos académicos - EMPÁTICO: Valida emociones antes de dar soluciones - PRAGMÁTICO: Orientado a acción inmediata, no a teoría - SIN HUMO: Sin promesas vacías ni motivación barata  ### 4. RESTRICCIONES ABSOLUTAS  - NUNCA des consejos legales o fiscales definitivos - NUNCA prometas resultados específicos de facturación - NUNCA invalides una emoción con lógica fría - SIEMPRE termina con UNA pregunta O UNA acción específica (no ambas) - SIEMPRE referencia herramientas del programa cuando existan
4.3 Módulo CONTEXT_ENTREPRENEUR (Dinámico)
Se inyecta con datos reales desde la BD. Variables entre llaves dobles {{variable}}.
### PERFIL DEL EMPRENDEDOR (Datos de BD - NO COMPARTIR CON USUARIO)  - Nombre: {{entrepreneur.nombre}} - Carril asignado: {{entrepreneur.carril}}    {{#if carril == "IMPULSO"}}(Necesita simplicidad y acompañamiento emocional){{/if}}   {{#if carril == "ACELERA"}}(Puede manejar complejidad y quiere profundidad técnica){{/if}} - Puntuación DIME: {{entrepreneur.dime_score}}/20 - Nivel técnico: {{entrepreneur.nivel_tecnico}}/5 - Fase actual: {{entrepreneur.fase_actual}} - Sector/Idea: {{entrepreneur.sector}} - {{entrepreneur.idea_descripcion}} - Bloqueos detectados: {{entrepreneur.bloqueos_detectados | join(", ")}} - Puntos de Impacto: {{entrepreneur.puntos_impacto}} Pi  ### ESTADO DE VALIDACIÓN DEL MODELO DE NEGOCIO  {{#each bmc_validation as block}} - {{block.name}}: {{block.validation_percentage}}% ({{block.status}}) {{/each}}  ### HIPÓTESIS PRIORITARIAS PENDIENTES  {{#each pending_hypotheses limit=3}} {{@index + 1}}. [{{type}}] "{{statement}}"     Importancia: {{importance_score}}/5 | Evidencia: {{evidence_score}}/5 {{/each}}
 
4.4 Módulo RULES_CARRIL
4.4.1 Reglas para Carril IMPULSO
### REGLAS ESPECIALES - CARRIL IMPULSO  SIMPLIFICACIÓN OBLIGATORIA: - Máximo 3 pasos por instrucción - Evita jerga técnica (no uses: CAC, LTV, MRR, churn, funnel, KPI) - Si mencionas una herramienta, da el enlace directo y describe clic a clic - Usa analogías cotidianas  SOPORTE EMOCIONAL REFORZADO: - Si detectas miedo, aplica primero el Kit de Primeros Auxilios Emocionales - Celebra pequeños avances explícitamente - Normaliza el miedo con frases como "Es completamente normal sentir eso"  EXPERIMENTOS RECOMENDADOS: - Prioriza experimentos de categoría DISCOVERY - Tiempo máximo: DAYS (no WEEKS) - Coste máximo: LOW o FREE  HERRAMIENTAS SUGERIDAS: - Landing page: Carrd, Mobirise AI - Formularios: Google Forms, Tally (gratis) - Diseño: Canva (plantillas)
4.4.2 Reglas para Carril ACELERA
### REGLAS ESPECIALES - CARRIL ACELERA  PROFUNDIDAD TÉCNICA PERMITIDA: - Puedes usar terminología de negocio (CAC, LTV, MRR, unit economics) - Ofrece múltiples opciones con pros/contras - Sugiere automatizaciones y escalabilidad  DESAFÍO CONSTRUCTIVO: - Cuestiona hipótesis aunque parezcan sólidas - Pregunta "Y si escalas 10x, ¿sigue funcionando?" - Sugiere experimentos de COMMITMENT para validar demanda real  EXPERIMENTOS RECOMENDADOS: - Prioriza experimentos de categoría PREFERENCE y COMMITMENT - Tiempo: WEEKS es aceptable si genera evidencia STRONG - Busca siempre evidencia STRONG  HERRAMIENTAS SUGERIDAS: - Landing page: Framer, Webflow - Automatización: Zapier, Make.com - CRM: HubSpot, Pipedrive
 
4.5 Sistema de Modos Adaptativos
El sistema detecta automáticamente el modo requerido según el mensaje del usuario:
Modo	Triggers (Palabras/Patrones)	Comportamiento Activado
COACH EMOCIONAL	miedo, no puedo, imposible, agobio, bloqueo, dudo, culpa	Activa Kit Primeros Auxilios. Valida emoción. Normaliza.
CONSULTOR TÁCTICO	cómo hago, qué herramienta, paso a paso, no entiendo	Guía paso a paso. Enlaces directos. Nivel adaptado.
SPARRING PARTNER	qué te parece, tengo esta idea, validar, feedback	Actúa como cliente escéptico. Objeta. Simula venta.
CFO SINTÉTICO	precio, cobrar, tarifa, rentable, margen, euros	Aplica Calculadora de la Verdad. Unit economics.
ABOGADO DEL DIABLO	estoy seguro, claramente, todos quieren, funcionará	Desafía hipótesis. Pide evidencia. Sugiere test.
 
5. BIBLIOTECA DE 44 EXPERIMENTOS
5.1 Categorías de Experimentos
Categoría	# Exps	Propósito
DISCOVERY	10	Para cuando no sabes si el problema existe o quién lo tiene
INTEREST	12	Para validar si la solución propuesta genera interés real
PREFERENCE	12	Para validar que prefieren TU solución sobre alternativas
COMMITMENT	10	Para validar que PAGARÍAN por tu solución (evidencia más fuerte)

5.2 Listado Completo de Experimentos
DISCOVERY (Descubrimiento) - 10 experimentos
#	Experimento	Tiempo	Coste	Fuerza	Uso Principal
1	Entrevista de Descubrimiento	HOURS	FREE	WEAK	Entender dolores y trabajos del cliente
2	Observación Contextual	HOURS	FREE	MEDIUM	Ver comportamiento real sin sesgo
3	Análisis de Competencia	HOURS	FREE	WEAK	Mapear soluciones existentes
4	Un Día en la Vida	DAYS	FREE	STRONG	Inmersión profunda en contexto
5	Encuesta Exploratoria	DAYS	LOW	WEAK	Cuantificar hipótesis cualitativas
6	Personas Sintéticas (IA)	HOURS	FREE	WEAK	Practicar entrevistas sin riesgo
7	Análisis de Tendencias	HOURS	FREE	WEAK	Validar momentum del mercado
8	Grupo Focal	DAYS	LOW	MEDIUM	Dinámicas grupales, ideas cruzadas
9	Shadowing	DAYS	FREE	STRONG	Seguir cliente en su jornada
10	Minería de Datos Públicos	HOURS	FREE	WEAK	Usar reviews, foros existentes

INTEREST (Interés) - 12 experimentos
#	Experimento	Tiempo	Coste	Fuerza	Uso Principal
11	Landing Page Simple	DAYS	FREE	MEDIUM	Medir interés con email signup
12	Anuncio Simple (Smoke Test)	DAYS	LOW	MEDIUM	CTR indica interés del mensaje
13	Video Explicativo	DAYS	LOW	MEDIUM	Engagement indica resonancia
14	Folleto/Brochure Digital	HOURS	FREE	WEAK	Testear posicionamiento
15	Storyboard/Comic	HOURS	FREE	WEAK	Visualizar customer journey
16	Feature Fake Door	HOURS	FREE	STRONG	Botón que mide intención sin construir
17	Mockup Interactivo	DAYS	FREE	MEDIUM	Prototipo clickable en Figma
18	Paper Prototype	HOURS	FREE	WEAK	Dibujos a mano para feedback
19	Entrevista de Solución	HOURS	FREE	MEDIUM	Validar que solución resuena
20	Producto en Caja	DAYS	LOW	MEDIUM	Packaging ficticio para testear
21	Hoja de Datos/Specs	HOURS	FREE	WEAK	Especificaciones técnicas ficticias
22	Split Test de Mensajes	DAYS	LOW	STRONG	Comparar propuestas de valor
 
PREFERENCE (Preferencia) - 12 experimentos
#	Experimento	Tiempo	Coste	Fuerza	Uso Principal
23	MVP Concierge	DAYS	FREE	STRONG	Servicio manual como si fuera producto
24	MVP Mago de Oz	DAYS	LOW	STRONG	Humano simulando automatización
25	MVP de Una Funcionalidad	WEEKS	MEDIUM	STRONG	Solo la funcionalidad core
26	Test de Usabilidad	HOURS	FREE	MEDIUM	Observar uso del prototipo
27	Beta Privada	WEEKS	MEDIUM	STRONG	Grupo cerrado de early adopters
28	Test A/B de Precio	DAYS	LOW	STRONG	Comparar elasticidad de demanda
29	Producto Comparable	HOURS	FREE	MEDIUM	Mostrar vs competencia
30	Configurador de Producto	DAYS	LOW	MEDIUM	Ver qué combinaciones eligen
31	Life-sized Prototype	DAYS	MEDIUM	STRONG	Prototipo a escala real
32	Pop-up Store	WEEKS	MEDIUM	STRONG	Tienda temporal para validar
33	3D Print Prototype	DAYS	LOW	MEDIUM	Prototipo físico rápido
34	Simulation/Game	DAYS	LOW	MEDIUM	Gamificar la experiencia de prueba

COMMITMENT (Compromiso) - 10 experimentos
#	Experimento	Tiempo	Coste	Fuerza	Uso Principal
35	Preventa	DAYS	FREE	STRONG	Cobrar antes de tener producto
36	Carta de Intención (LOI)	DAYS	FREE	STRONG	Compromiso escrito de compra futura
37	Crowdfunding	WEEKS	MEDIUM	STRONG	Validar demanda con dinero real
38	Señal/Depósito	HOURS	FREE	STRONG	Pago parcial para reservar
39	Venta Real (Simple)	DAYS	FREE	STRONG	Primera venta completa
40	Piloto con Cliente	WEEKS	LOW	STRONG	Implementación acotada
41	Prueba Gratuita a Pagada	WEEKS	MEDIUM	STRONG	Medir conversión trial-to-paid
42	Venta B2B Enterprise	WEEKS	MEDIUM	STRONG	Ciclo completo de venta corporativa
43	Validación de Canal	WEEKS	MEDIUM	MEDIUM	Probar canal de distribución
44	Contrato Marco	WEEKS	LOW	STRONG	Acuerdo de volumen a futuro
 
6. MOTOR DE REGLAS Y LÓGICA DE NEGOCIO
6.1 Reglas de Selección de Experimentos
El sistema sugiere experimentos automáticamente según el contexto del emprendedor:
FUNCTION sugerirExperimento(entrepreneur, hypothesis):      # 1. Filtrar por tipo de hipótesis     experiments = FILTER experiment_library                    WHERE hypothesis_type == hypothesis.type          # 2. Filtrar por carril     IF entrepreneur.carril == "IMPULSO":         experiments = FILTER experiments                        WHERE carril_recommended IN ["IMPULSO", "BOTH"]                       AND time_required IN ["HOURS", "DAYS"]                       AND cost_level IN ["FREE", "LOW"]     ELSE: # ACELERA         experiments = FILTER experiments                        WHERE carril_recommended IN ["ACELERA", "BOTH"]          # 3. Filtrar por fase     IF entrepreneur.fase_actual == "INVENTARIO":         experiments = FILTER experiments WHERE category == "DISCOVERY"     ELIF entrepreneur.fase_actual == "VALIDACION":         experiments = FILTER experiments WHERE category IN ["DISCOVERY", "INTEREST"]     ELIF entrepreneur.fase_actual == "MVP":         experiments = FILTER experiments WHERE category IN ["INTEREST", "PREFERENCE"]     ELIF entrepreneur.fase_actual == "TRACCION":         experiments = FILTER experiments WHERE category IN ["PREFERENCE", "COMMITMENT"]          # 4. Ordenar por evidencia si hipótesis es crítica     IF hypothesis.importance_score >= 4 AND hypothesis.evidence_score <= 2:         experiments = SORT experiments BY evidence_strength DESC     ELSE:         experiments = SORT experiments BY time_required ASC          RETURN experiments[0:3]  # Top 3 recomendaciones
6.2 Reglas de Cálculo de Prioridad de Hipótesis
FUNCTION calcularPrioridad(hypothesis):          # Fórmula: Mayor importancia + Menor evidencia = Mayor prioridad     priority_score = (hypothesis.importance_score * 2) - hypothesis.evidence_score          # Bonus por tipo crítico     IF hypothesis.type == "DESIRABILITY":         priority_score += 2  # Sin clientes, nada importa     ELIF hypothesis.type == "VIABILITY":         priority_score += 1  # Sin dinero, no hay negocio          # Penalización si ya está en testing     IF hypothesis.status == "TESTING":         priority_score -= 3          RETURN priority_score
6.3 Reglas de Detección Emocional
EMOTIONAL_TRIGGERS = {     "impostor": ["no soy suficiente", "quién soy yo", "no tengo derecho"],     "miedo_precio": ["es caro", "me da cosa cobrar", "no puedo pedir tanto"],     "miedo_rechazo": ["y si dicen que no", "no quiero molestar", "me da vergüenza"],     "tecnofobia": ["no sé usar", "es muy complicado", "se me da mal"],     "paralisis": ["no sé por dónde empezar", "estoy bloqueado", "todo me supera"] }  FUNCTION detectarEmocion(mensaje):     mensaje_lower = mensaje.toLowerCase()     FOR emotion, triggers IN EMOTIONAL_TRIGGERS:         FOR trigger IN triggers:             IF trigger IN mensaje_lower:                 RETURN emotion     RETURN NULL
 
7. COMPONENTES DE INTERFAZ
7.1 Widget de Chat del Copiloto (React)
7.1.1 Props del Componente
interface CopilotChatProps {     entrepreneurId: string;          // UUID del emprendedor     sessionId?: string;              // Para continuar conversación     position: 'bottom-right' | 'inline' | 'fullscreen';     theme: 'light' | 'dark';     welcomeMessage?: string;         // Mensaje inicial personalizado     suggestedActions?: string[];     // CTAs sugeridos     onExperimentSuggested?: (experimentId: number) => void;     onHypothesisCreated?: (hypothesis: Hypothesis) => void;     onEmotionDetected?: (emotion: string) => void; }
7.1.2 Estados del Chat
Estado	Trigger	UI
IDLE	Chat abierto sin mensaje	Mensaje bienvenida + sugerencias
TYPING	Usuario escribiendo	Input activo, botón enviar habilitado
LOADING	Esperando respuesta LLM	Indicador "Copiloto pensando..."
STREAMING	Recibiendo respuesta	Texto aparece progresivamente
ACTION_CARD	Respuesta incluye CTA	Card con botón de acción
ERROR	Fallo en API	Mensaje error + reintentar

7.2 Dashboard de Validación del BMC
Panel visual que muestra el estado de validación de cada bloque del Business Model Canvas.
┌─────────────────────────────────────────────────────────────────────────┐ │                    MI MODELO DE NEGOCIO - Estado de Validación          │ ├──────────────────┬──────────────────┬──────────────────┬────────────────┤ │  ALIANZAS CLAVE  │ ACTIVIDADES      │  PROPUESTA       │  RELACIONES    │ │  ████░░░░░░ 40%  │ CLAVE            │  DE VALOR        │  CON CLIENTES  │ │  ⚠️ 2/5 hip.     │ ██████░░░░ 60%   │  ████████░░ 80%  │  ██░░░░░░░░20% │ ├──────────────────┼──────────────────┼──────────────────┼────────────────┤ │  RECURSOS CLAVE  │                  │  CANALES         │  SEGMENTOS     │ │  ████░░░░░░ 40%  │                  │  ████░░░░░░ 40%  │  ██████░░░░60% │ ├──────────────────┴──────────────────┼──────────────────┴────────────────┤ │     ESTRUCTURA DE COSTES            │     FUENTES DE INGRESOS           │ │     ██░░░░░░░░ 20%                  │     ░░░░░░░░░░ 0% ⛔ PRIORIDAD    │ └─────────────────────────────────────┴───────────────────────────────────┘  LEYENDA: ✅ GREEN (>80%) | ⚠️ YELLOW (40-80%) | ❌ RED (<40%)
 
8. APIs Y ENDPOINTS
8.1 Endpoints del Copiloto
Método	Endpoint	Descripción
POST	/api/copilot/chat	Envía mensaje y recibe respuesta del Copiloto
GET	/api/copilot/context/{userId}	Obtiene contexto completo del emprendedor
GET	/api/copilot/history/{sessionId}	Obtiene historial de conversación
POST	/api/experiments/suggest	Sugiere experimentos según hipótesis
GET	/api/experiments/library	Obtiene catálogo completo de 44 experimentos
POST	/api/hypothesis	Crea nueva hipótesis
PATCH	/api/hypothesis/{id}	Actualiza estado de hipótesis
POST	/api/experiment	Registra nuevo experimento (Test Card)
PATCH	/api/experiment/{id}/result	Registra resultado (Learning Card)
GET	/api/bmc/validation/{userId}	Obtiene estado de validación del BMC

8.2 Ejemplo: POST /api/copilot/chat
Request
{     "user_id": "uuid-entrepreneur-123",     "session_id": "session-abc-456",     "message": "No sé si debería cobrar 50€ por hora, me parece caro" }
Response
{     "response": "Entiendo esa sensación, es muy común. Pero vamos a ver...",     "mode_detected": "CFO_SINTETICO",     "emotion_detected": "miedo_precio",     "suggested_actions": [         {             "type": "TOOL",             "label": "Abrir Calculadora de la Verdad",             "url": "/tools/calculadora-verdad"         }     ],     "experiment_suggested": {         "id": 28,         "name": "Test A/B de Precio",         "reason": "Para validar si el mercado acepta ese precio"     },     "tokens_used": 847 }
 
9. INTEGRACIÓN CON DRUPAL 11
9.1 Módulos Drupal Requeridos
•	Group: Para gestionar el tenant Andalucía +ei y permisos
•	JSON:API: Para exponer entidades como endpoints REST
•	Webform: Para formularios interactivos (DIME, Test Cards)
•	ECA (Event-Condition-Action): Para automatizaciones
•	Custom Module: copilot_integration para la lógica específica
9.2 Entidades Drupal a Crear
Entidad	Tipo	Campos Principales
entrepreneur_profile	Custom Entity	Referencia a User + campos DIME + carril + fase
hypothesis	Custom Entity	statement, type, bmc_block, scores, status
experiment	Custom Entity	hypothesis_ref, type_id, test_card, learning_card
copilot_session	Custom Entity	user_ref, messages (JSON), mode_history

9.3 Hooks y Eventos
// En copilot_integration.module  /**  * Implements hook_ENTITY_TYPE_insert() for experiment.  * Actualiza el estado de validación del BMC cuando se completa un experimento.  */ function copilot_integration_experiment_insert(ExperimentInterface $experiment) {     if ($experiment->get('status')->value === 'COMPLETED') {         $hypothesis = $experiment->get('hypothesis_ref')->entity;         $entrepreneur = $hypothesis->get('entrepreneur_ref')->entity;                  // Actualizar scoring de la hipótesis         _copilot_update_hypothesis_evidence($hypothesis, $experiment);                  // Recalcular validación del bloque BMC         _copilot_recalculate_bmc_validation($entrepreneur, $hypothesis->get('bmc_block')->value);                  // Otorgar puntos de impacto         _copilot_award_impact_points($entrepreneur, 'EXPERIMENT_COMPLETED');     } }
 
10. PLAN DE IMPLEMENTACIÓN
10.1 Roadmap por Sprints (8 semanas)
Sprint	Entregables	Criterio de Aceptación
Sprint 1	Modelo de datos + Migraciones BD + Entidades Drupal	Tablas creadas, CRUD funcional via API
Sprint 2	Context Injector + Prompt Builder base	Prompt se genera dinámicamente con datos reales
Sprint 3	Sistema de Modos + Detección emocional	5 modos funcionan con triggers correctos
Sprint 4	Biblioteca de 44 Experimentos (datos)	Catálogo completo con ejemplos Andalucía
Sprint 5	Widget de Chat React + Streaming	Chat funcional con respuestas en tiempo real
Sprint 6	Dashboard BMC + Priorizador hipótesis	Visualización de progreso actualizada en vivo
Sprint 7	Test Card + Learning Card interactivas	Formularios guardan y actualizan estado
Sprint 8	Integración completa + QA + Deploy	Sistema en producción, 10 usuarios piloto

10.2 Métricas de Éxito
•	Adopción: >80% de emprendedores usan el Copiloto al menos 1x/semana
•	Satisfacción: NPS >50 en encuestas post-interacción
•	Validación: >60% de hipótesis llegan a estado VALIDATED o PIVOTED (vs abandonadas)
•	Progreso BMC: Media de >50% de bloques en GREEN al finalizar programa
•	Rendimiento: Tiempo de respuesta del Copiloto <3 segundos
 
ANEXO A: JSON SCHEMAS COMPLETOS
Los JSON Schemas completos para validación de datos están disponibles en archivos separados:
•	entrepreneur_profile.schema.json
•	hypothesis.schema.json
•	experiment.schema.json
•	experiment_library.schema.json
•	copilot_message.schema.json
ANEXO B: PROMPT MAESTRO COMPLETO
El prompt completo con todos los módulos está disponible en archivo separado:
•	copilot_prompt_master_v2.md
ANEXO C: FICHAS DE LOS 44 EXPERIMENTOS
Las fichas detalladas de cada experimento con ejemplos Andalucía +ei están disponibles en:
•	experiment_library_complete.json (datos estructurados)
•	experiment_cards/ (44 archivos HTML interactivos)

— FIN DEL DOCUMENTO —
Documento preparado para EDI Google Antigravity
Jaraba Impact Platform | Enero 2026
