
AUDITORÍA TÉCNICA
AI COPILOTS Y MODOS EXPERTOS
Evaluación Estratégica de API BOE
como Base de Conocimiento Normativa Premium
JARABA IMPACT PLATFORM
Ecosistema SaaS Multi-Vertical
Versión:	1.0
Fecha:	Febrero 2026
Código:	AUDIT_AI_COPILOTS_BOE_v1
Estado:	Análisis Completado
 
Tabla de Contenidos
Tabla de Contenidos	1
1. Resumen Ejecutivo	1
1.1 Hallazgos Principales	1
1.2 Recomendación Estratégica	1
2. Inventario de AI Copilots por Vertical	1
2.1 Vertical Empleabilidad	1
Capacidades del Copilot	1
Principios de Diseño	1
2.2 Vertical Emprendimiento	1
Capacidades del Business Copilot	1
Principio Diferenciador: Sin Humo	1
2.3 Vertical ServiciosConecta	1
Capacidades del Copilot de Servicios	1
2.4 Vertical AgroConecta - GAPS IDENTIFICADOS	1
Documentos Faltantes	1
Acciones del Producer Copilot (según Plan Maestro)	1
3. Trilogía de Inteligencia Artificial	1
3.1 AI Skills System (Doc 129)	1
Propuesta de Valor	1
Jerarquía de Skills	1
3.2 Tenant Knowledge Training (Doc 130)	1
Los 7 Módulos del Sistema	1
3.3 AI Content Hub (Doc 128)	1
ROI de la Trilogía Completa	1
4. Evaluación API BOE	1
4.1 Datos Abiertos BOE	1
Conjuntos de Datos Disponibles	1
Endpoints Clave	1
4.2 Códigos Electrónicos Relevantes	1
Derecho Tributario (Todos actualizados)	1
Legislación Social (Mayoría actualizados)	1
4.3 Análisis FODA	1
Fortalezas	1
Debilidades	1
5. Arquitectura de Integración Propuesta	1
5.1 Pipeline de Actualización Normativa	1
5.2 Stack Tecnológico	1
5.3 Flujo RAG para Consultas Normativas	1
6. Módulo Premium: Asistencia Tributaria y Laboral	1
6.1 Estrategia de Monetización por Tiers	1
6.2 Diferenciadores Competitivos	1
6.3 Roadmap de Implementación	1
6.4 ROI Esperado	1
7. Conclusiones y Próximos Pasos	1
7.1 Conclusiones Principales	1
7.2 Acciones Inmediatas	1
7.3 Documentos a Crear	1
7.4 Referencias	1

 
1. Resumen Ejecutivo
Esta auditoría técnica documenta el inventario completo de capacidades de Inteligencia Artificial del ecosistema Jaraba Impact Platform, con foco en los AI Copilots (asistentes conversacionales) de cada vertical y la evaluación de la API del BOE como fuente de conocimiento normativo para un módulo premium.
1.1 Hallazgos Principales
•	Cobertura Completa: Empleabilidad, Emprendimiento y ServiciosConecta tienen AI Copilots completamente especificados
•	Gaps Identificados: AgroConecta carece de Producer Copilot (doc 67) y Sales Agent (doc 68)
•	Trilogía IA Diferenciadora: Skills System + Knowledge Training + Content Hub = ChatGPT empresarial personalizable
•	API BOE Viable: Fuente oficial, actualizada permanentemente, gratuita, con formato estructurado
•	Oportunidad de Mercado: Ningún competidor ofrece base de conocimiento normativa actualizada automáticamente
1.2 Recomendación Estratégica
Desarrollar módulo "Asistencia Tributaria y Laboral Premium" como diferenciador competitivo clave, integrando la API del BOE con arquitectura RAG para ofrecer respuestas fundamentadas en normativa oficial con actualización automática.
 
2. Inventario de AI Copilots por Vertical
2.1 Vertical Empleabilidad
Documento: 20_Empleabilidad_AI_Copilot_v1.docx
Capacidades del Copilot
Modo Experto	Descripción	Valor para Usuario
Profile Coach	Sugerencias para mejorar perfil y CV	Optimización profesional
Job Advisor	Recomendaciones de ofertas y estrategia	Orientación laboral
Interview Prep	Preparación de entrevistas con simulación	Mayor confianza
Learning Guide	Orientación sobre cursos y learning paths	Desarrollo continuo
Application Helper	Ayuda para redactar cartas y respuestas	Ahorro de tiempo
FAQ Assistant	Respuestas sobre el ecosistema	Soporte inmediato

Principios de Diseño
•	Strict Grounding: Solo responde basándose en información verificable del sistema
•	Personalización: Todas las respuestas consideran perfil, historial y objetivos
•	Accionable: Sugerencias incluyen acciones concretas ejecutables en la plataforma
•	Empático: Tono de apoyo, especialmente en momentos de rechazo
•	Transparente: Reconoce cuando no tiene información
2.2 Vertical Emprendimiento
Documento: 44_Emprendimiento_AI_Business_Copilot_v1.docx
Capacidades del Business Copilot
Modo Experto	Descripción	Valor para Emprendedor
Business Coach	Orientación estratégica sobre el negocio	Dirección clara
Canvas Advisor	Ayuda para completar Business Model Canvas	Modelo validado
Task Guide	Guía paso a paso para completar tareas	Sin bloqueos
Content Writer	Generación de textos comerciales	Marketing efectivo
Competitor Analyzer	Análisis básico de competencia	Inteligencia de mercado
Pricing Advisor	Orientación sobre estrategia de precios	Rentabilidad
Marketing Helper	Sugerencias de marketing digital	Visibilidad
FAQ Assistant	Respuestas sobre el ecosistema	Soporte inmediato

Principio Diferenciador: Sin Humo
El Business Copilot implementa el principio "Sin Humo" del ecosistema: honesto sobre limitaciones, no promete resultados irreales, fundamenta todas las recomendaciones en datos verificables.
2.3 Vertical ServiciosConecta
Documento: 93_ServiciosConecta_Copilot_Servicios_v1.docx
Capacidades del Copilot de Servicios
Caso de Uso	Ejemplo de Prompt	Valor
Búsqueda en expediente	¿Cuál era el importe de la factura de diciembre?	Evita buscar en 50 documentos
Resumen de caso	Dame un resumen del caso García	Preparación en 30 segundos
Redacción de email	Redacta email recordando documentos pendientes	Ahorra 10-15 min
Próximos pasos	¿Qué debería hacer a continuación?	Sin tareas olvidadas
Preparar reunión	Prepárame los puntos a tratar	Agenda estructurada

Tecnología: Gemini 2.0 Flash + RAG (Qdrant) + Strict Grounding. A diferencia del Triaje (doc 91) que procesa consultas entrantes, o del Presupuestador (doc 92) que genera propuestas económicas, el Copilot es un compañero de trabajo para tareas del día a día.
2.4 Vertical AgroConecta - GAPS IDENTIFICADOS
Fuente: Gap_Analysis_Documentacion_Tecnica_AgroConecta_v1.docx
Documentos Faltantes
Documento	Contenido Esperado	Prioridad
67_AgroConecta_Producer_Copilot_v1.docx	Generación descripciones, optimización SEO, sugerencias precios, análisis competencia, respuestas a reseñas	MEDIA
68_AgroConecta_Sales_Agent_v1.docx	Chatbot atención cliente, recomendaciones producto, venta cruzada, recuperación carritos, soporte post-venta	MEDIA

Acciones del Producer Copilot (según Plan Maestro)
•	generate_description: Foto de producto → Descripción optimizada SEO con Answer Capsule
•	suggest_price: Análisis de competencia y costes → Precio recomendado con margen
•	respond_review: Reseña recibida → Borrador de respuesta profesional
•	forecast_demand: Histórico + temporada → Predicción de demanda
 
3. Trilogía de Inteligencia Artificial
La plataforma implementa tres sistemas complementarios que transforman cada tenant en un "ChatGPT empresarial" personalizado sin necesidad de código.
3.1 AI Skills System (Doc 129)
Enseña a la IA CÓMO ejecutar tareas con maestría.
Propuesta de Valor
•	RAG Tradicional: Información factual (QUÉ sabe la IA)
•	Skills System: Conocimiento procedimental (CÓMO lo hace)
Jerarquía de Skills
Scope	Descripción	Ejemplo
Core Skills	Habilidades base del ecosistema	Atención al cliente genérica
Vertical Skills	Especializaciones por vertical	Matching candidato-oferta
Agent Skills	Habilidades de agentes específicos	Análisis de competencia
Tenant Skills	Personalizaciones del cliente	Políticas específicas

Inversión estimada: 455-585 horas (8 sprints, 16 semanas)
3.2 Tenant Knowledge Training (Doc 130)
Enseña a la IA QUÉ sabe el negocio específico - "Entrena tu IA sin escribir código"
Los 7 Módulos del Sistema
Módulo	Descripción	Complejidad
Info del Negocio	Datos básicos que toda IA necesita	Muy Baja
FAQs Personalizadas	Preguntas y respuestas del negocio	Baja
Políticas	Envíos, devoluciones, pagos, etc.	Baja
Documentos	Subir PDFs, Word, Excel	Baja
Productos Enriquecidos	Info adicional del catálogo	Media
Aprendizaje Feedback	Corregir respuestas de la IA	Baja
Test de la IA	Probar antes de go-live	Muy Baja

Stack: React + Tailwind, Drupal REST, Apache Tika, LangChain TextSplitter, OpenAI embeddings, Qdrant, Claude 3.5 Haiku.
Inversión estimada: 430-545 horas (6 sprints, 12 semanas)
3.3 AI Content Hub (Doc 128)
La IA CREA contenido nuevo para el negocio. Genera textos comerciales, descripciones de producto, posts de redes sociales, emails marketing, y más.
ROI de la Trilogía Completa
•	Reducción de tickets de soporte: -25%
•	Mayor satisfacción del cliente: CSAT > 4.2
•	Justificación de upgrade a planes superiores
•	Retención de clientes por personalización profunda
Inversión total trilogía: 915-1,200 horas (€41,175-54,000)
 
4. Evaluación API BOE
Análisis de viabilidad de la API del Boletín Oficial del Estado como fuente de conocimiento normativo actualizado para módulo premium.
4.1 Datos Abiertos BOE
URL: https://www.boe.es/datosabiertos/api/api.php
Conjuntos de Datos Disponibles
•	Legislación consolidada elaborada por servicios documentales AEBOE
•	Sumario del diario oficial BOE
•	Sumario del diario oficial BORME
•	Tablas auxiliares complementarias
Endpoints Clave
Endpoint	Descripción	Formato
/legislacion-consolidada	Búsqueda de normas consolidadas	XML/JSON
/legislacion-consolidada/id/{id}/metadatos	Metadatos de una norma	XML/JSON
/legislacion-consolidada/id/{id}/texto	Texto consolidado completo	XML
/legislacion-consolidada/id/{id}/texto/indice	Lista de bloques de una norma	XML/JSON
/legislacion-consolidada/id/{id}/texto/bloque/{id_bloque}	Texto de bloque específico	XML
/boe/sumario/{fecha}	Sumario para un día	XML/JSON

4.2 Códigos Electrónicos Relevantes
URL: https://www.boe.es/biblioteca_juridica/index.php?tipo=C
Compilaciones permanentemente actualizadas de las principales normas vigentes.
Derecho Tributario (Todos actualizados)
•	Código de Legislación Tributaria
•	Ley General Tributaria y sus reglamentos
•	IRPF, IVA, Sociedades, Sucesiones y Donaciones
•	Impuestos especiales, Tasas y Precios Públicos
•	Normativa Catastral
Legislación Social (Mayoría actualizados)
•	Código de Legislación Social
•	Código Laboral y de la Seguridad Social
•	Trabajo Autónomo
•	Prevención de riesgos laborales
•	Despidos Colectivos y ERTE
4.3 Análisis FODA
Fortalezas
•	Fuente oficial y autoritativa (Agencia Estatal BOE)
•	Actualización permanente automática
•	Sistema de alertas vía Mi BOE
•	Formato estructurado XML/JSON con XSD
•	API gratuita sin coste de licencia
•	Cobertura completa normativa tributaria y laboral
Debilidades
•	No es conversacional (requiere RAG)
•	Complejidad normativa requiere interpretación experta
•	Solo texto legal, sin casos prácticos
•	Pipeline RAG debe re-indexar manualmente
 
5. Arquitectura de Integración Propuesta
5.1 Pipeline de Actualización Normativa
Proceso automatizado para mantener la base de conocimiento sincronizada con BOE:
1.	Scraper Programado (Cron diario): Consulta API BOE con parámetro 'from' para detectar cambios
2.	Procesamiento: Descarga texto, extrae metadatos, chunking por artículos/secciones
3.	Embeddings: Generación con OpenAI text-embedding-3-small
4.	Indexación Qdrant: Namespace jaraba_normativa_oficial con metadata rica
5.	Enriquecimiento: FAQs interpretativas + casos prácticos + disclaimers
5.2 Stack Tecnológico
Componente	Tecnología	Función
LLM Principal	Claude API	Generación de respuestas
Vector DB	Qdrant	Almacenamiento embeddings
Embeddings	OpenAI text-embedding-3-small	Vectorización de normativa
Scraper	PHP + Guzzle	Consumo API BOE
Queue	Drupal Queue API	Procesamiento asíncrono
Cache	Redis	Respuestas frecuentes
Admin UI	React + Tailwind	Dashboard de normativa

5.3 Flujo RAG para Consultas Normativas
Query: "¿Puedo deducir gastos de oficina en casa como autónomo?"
•	Filtros aplicados: {source: "boe", codigo: "tributario", ambito: "autonomos"}
•	Top-k: 5 chunks con score > 0.75
•	Contexto enriquecido con FAQs interpretativas
•	Respuesta con citas a artículos específicos del IRPF
•	Disclaimer automático: "Consulte con un asesor profesional"
 
6. Módulo Premium: Asistencia Tributaria y Laboral
6.1 Estrategia de Monetización por Tiers
Tier	Características	Precio
BÁSICO (Incluido)	Orientación general, deep linking a AEAT/TGSS, disclaimers	€0
PREMIUM	Base BOE actualizada, citas oficiales, alertas normativas, calculadoras	+€29/mes
ENTERPRISE+	Marketplace asesores, revisión humana, API integración, auditoría	+€99/mes

6.2 Diferenciadores Competitivos
•	Actualización Automática: Mientras competidores tienen bases estáticas, Jaraba actualiza diariamente desde BOE oficial
•	Strict Grounding: Todas las respuestas citan norma específica con enlace a BOE
•	Multi-Tenant Personalizado: Alertas solo de cambios relevantes para sector/actividad del tenant
•	Progresividad: Desde orientación básica gratuita hasta asesoría semi-profesional premium
•	Compliance: Trazabilidad completa para auditorías (LOPD, ISO 27001)
6.3 Roadmap de Implementación
Sprint	Entregables	Horas Est.
1-2 (4 sem)	Integración API BOE, pipeline scraping, chunking, indexación Qdrant	80-100
3-4 (4 sem)	Integración con Business Copilot, modo consulta normativa, citas/disclaimers	80-100
5-6 (4 sem)	FAQs interpretativas, casos prácticos, calculadoras fiscales básicas	60-80
7-8 (4 sem)	Generación documentos, histórico/auditoría, dashboard compliance	60-100

Inversión Total Estimada: 480-640 horas (10-13 sprints)
6.4 ROI Esperado
•	Justificación upgrade a plan Premium: +30% conversión
•	Reducción churn por valor añadido: -15%
•	Upsell addon Enterprise+: €50-150/mes por tenant
•	Diferenciación competitiva: Único SaaS con normativa BOE actualizada automáticamente
 
7. Conclusiones y Próximos Pasos
7.1 Conclusiones Principales
•	El ecosistema Jaraba tiene una cobertura sólida de AI Copilots en verticales principales
•	La Trilogía IA (Skills + Knowledge + Content) es un diferenciador de mercado único
•	La API BOE es viable y estratégica como fuente de conocimiento normativo premium
•	Existe una oportunidad clara de mercado sin competidores directos
7.2 Acciones Inmediatas
6.	Priorizar desarrollo del módulo "Asistencia Tributaria y Laboral Premium"
7.	Completar especificaciones faltantes de AgroConecta (docs 67-68)
8.	Implementar pipeline de actualización BOE como servicio core
9.	Diseñar tier Premium con módulo normativo como justificación principal
10.	Establecer partnerships con colegios profesionales (asesores, graduados sociales)
7.3 Documentos a Crear
Documento	Descripción	Prioridad
67_AgroConecta_Producer_Copilot_v1.docx	Copilot para productores agrícolas	MEDIA
68_AgroConecta_Sales_Agent_v1.docx	Agente de ventas conversacional	MEDIA
178_Platform_Legal_Knowledge_Module_v1.docx	Módulo normativo BOE integrado	ALTA

7.4 Referencias
•	API BOE: https://www.boe.es/datosabiertos/api/api.php
•	Códigos Electrónicos: https://www.boe.es/biblioteca_juridica/index.php?tipo=C
•	Documentación API: https://www.boe.es/datosabiertos/documentos/APIconsolidada.pdf

— Fin del Documento —
