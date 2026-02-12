SISTEMA DE VALIDACIÓN DE MVP
Minimum Viable Product Validation
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	37_Emprendimiento_MVP_Validation
Dependencias:	36_Business_Model_Canvas, 25_Business_Diagnostic, 29_Action_Plans
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del Sistema de Validación de MVP para la vertical de Emprendimiento. El sistema implementa la metodología Lean Startup aplicada al contexto de pequeños negocios y emprendedores rurales, siguiendo la filosofía "Sin Humo": métricas reales, no vanidad; validación con datos, no suposiciones.
1.1 Objetivos del Sistema
•	Validación de hipótesis: Definir y testear hipótesis de negocio de forma estructurada
•	Experimentos guiados: Plantillas de experimentos adaptados a negocios locales
•	Métricas de validación: Tracking de KPIs reales, no métricas de vanidad
•	Ciclo Build-Measure-Learn: Implementación del ciclo de aprendizaje validado
•	Decisiones informadas: Pivotar o perseverar basado en evidencia
•	Documentación de aprendizajes: Registro histórico de lo aprendido para futura referencia
1.2 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con módulo jaraba_mvp_validation custom
Hipótesis	Custom entity hypothesis con estados workflow
Experimentos	Custom entity experiment con tipos predefinidos
Métricas	Integración con analytics externos + custom tracking
Landing pages	Webform + Page Builder para tests de landing
Encuestas	Webform con lógica condicional para customer discovery
Visualización	Chart.js para gráficos de métricas y progreso
 
2. Metodología Lean Startup Adaptada
El sistema implementa una versión simplificada de la metodología Lean Startup, adaptada para emprendedores sin background técnico ni grandes recursos.
2.1 El Ciclo Build-Measure-Learn Simplificado
Fase	Descripción	Entregable
IDEA	Hipótesis a validar derivada del Canvas	Hipótesis documentada con criterio de éxito
BUILD	Crear el mínimo experimento para testear	Experimento configurado (landing, encuesta...)
MEASURE	Recoger datos del experimento	Métricas reales registradas
LEARN	Analizar resultados y decidir	Decisión: pivotar, perseverar o profundizar
2.2 Tipos de Hipótesis
El sistema categoriza las hipótesis según el bloque del Canvas que validan:
Tipo	machine_name	Pregunta Central	Riesgo si no valida
Problema	problem	¿Existe el problema que queremos resolver?	ALTO - No hay mercado
Cliente	customer	¿Hemos identificado al cliente correcto?	ALTO - Targeting erróneo
Solución	solution	¿Nuestra solución resuelve el problema?	ALTO - Producto inútil
Precio	pricing	¿Pagarán el precio propuesto?	MEDIO - Modelo no viable
Canal	channel	¿Podemos alcanzar a los clientes?	MEDIO - Distribución
Crecimiento	growth	¿Podemos escalar la adquisición?	BAJO - Límite de crecimiento
2.3 Métricas Reales vs. Vanidad
Filosofía "Sin Humo" aplicada a las métricas:
Métrica de Vanidad ❌	Métrica Real ✓	Por qué importa
Visitas a la web	% que completa la acción clave	El tráfico sin conversión es humo
Seguidores en redes	% de engagement real	Seguidores comprados no compran
"Me gusta" en publicación	Mensajes de interés recibidos	Los likes no son intención de compra
Emails recopilados	% de apertura y click	Lista muerta no tiene valor
Descargas de app/recurso	Usuarios activos recurrentes	Descargar ≠ usar
 
3. Arquitectura de Entidades
El sistema introduce 4 entidades Drupal personalizadas para gestionar el ciclo completo de validación.
3.1 Entidad: hypothesis
Representa una hipótesis de negocio a validar, derivada del Business Model Canvas.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL, INDEX
user_id	INT	Emprendedor propietario	FK users.uid, NOT NULL
tenant_id	INT	Tenant/programa	FK tenant.id, NULLABLE
canvas_id	INT	Canvas origen	FK business_model_canvas.id, NULLABLE
canvas_block	VARCHAR(32)	Bloque del canvas relacionado	ENUM: customer_segments|value_propositions|...
hypothesis_type	VARCHAR(24)	Tipo de hipótesis	ENUM: problem|customer|solution|pricing|channel|growth
title	VARCHAR(255)	Título corto	NOT NULL
statement	TEXT	Declaración de la hipótesis	NOT NULL
assumption	TEXT	Suposición subyacente	NOT NULL
success_criteria	TEXT	Criterio de éxito medible	NOT NULL
target_metric	VARCHAR(64)	Métrica objetivo	NOT NULL
target_value	DECIMAL(10,2)	Valor objetivo de la métrica	NOT NULL
current_value	DECIMAL(10,2)	Valor actual medido	NULLABLE
confidence_level	INT	Nivel de confianza inicial %	RANGE 0-100
risk_level	VARCHAR(16)	Nivel de riesgo	ENUM: critical|high|medium|low
priority	INT	Prioridad de validación	RANGE 1-5
status	VARCHAR(16)	Estado de la hipótesis	ENUM: draft|testing|validated|invalidated|pivoted
validation_date	DATETIME	Fecha de validación	NULLABLE
learnings	TEXT	Aprendizajes documentados	NULLABLE
next_action	VARCHAR(24)	Decisión post-validación	ENUM: persevere|pivot|kill|deepen
created	DATETIME	Creación	NOT NULL, UTC
changed	DATETIME	Modificación	NOT NULL, UTC
 
3.2 Entidad: validation_experiment
Representa un experimento diseñado para validar una hipótesis específica.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
hypothesis_id	INT	Hipótesis que valida	FK hypothesis.id, NOT NULL
experiment_type	VARCHAR(32)	Tipo de experimento	ENUM: landing_page|survey|interview|...
title	VARCHAR(255)	Título del experimento	NOT NULL
description	TEXT	Descripción detallada	NULLABLE
methodology	TEXT	Metodología del experimento	NOT NULL
sample_size_target	INT	Tamaño de muestra objetivo	NOT NULL, > 0
sample_size_current	INT	Muestra actual alcanzada	DEFAULT 0
start_date	DATE	Fecha de inicio	NOT NULL
end_date	DATE	Fecha de fin planificada	NULLABLE
actual_end_date	DATE	Fecha de fin real	NULLABLE
budget_estimated	DECIMAL(8,2)	Presupuesto estimado €	DEFAULT 0
budget_actual	DECIMAL(8,2)	Gasto real €	DEFAULT 0
tool_used	VARCHAR(64)	Herramienta utilizada	NULLABLE
external_url	VARCHAR(500)	URL del experimento (landing, encuesta)	NULLABLE
status	VARCHAR(16)	Estado	ENUM: draft|running|paused|completed|cancelled
raw_data	JSON	Datos crudos recopilados	NULLABLE
results_summary	TEXT	Resumen de resultados	NULLABLE
created	DATETIME	Creación	NOT NULL
3.3 Entidad: experiment_metric
Almacena las métricas recopiladas durante un experimento.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
experiment_id	INT	Experimento padre	FK validation_experiment.id, NOT NULL
metric_name	VARCHAR(64)	Nombre de la métrica	NOT NULL
metric_type	VARCHAR(24)	Tipo de métrica	ENUM: count|percentage|currency|rating|boolean
value	DECIMAL(12,4)	Valor registrado	NOT NULL
recorded_at	DATETIME	Momento del registro	NOT NULL
source	VARCHAR(64)	Fuente del dato	NULLABLE
notes	VARCHAR(500)	Notas contextuales	NULLABLE
3.4 Entidad: customer_interview
Almacena entrevistas de descubrimiento de cliente realizadas como parte de la validación.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
experiment_id	INT	Experimento asociado	FK validation_experiment.id, NOT NULL
interviewee_profile	VARCHAR(255)	Perfil del entrevistado	NOT NULL
interview_date	DATE	Fecha de la entrevista	NOT NULL
duration_minutes	INT	Duración en minutos	NULLABLE
channel	VARCHAR(24)	Canal de la entrevista	ENUM: in_person|video|phone|chat
transcript	TEXT	Transcripción o notas	NULLABLE
key_insights	JSON	Insights extraídos	NULLABLE
pain_points	JSON	Puntos de dolor identificados	NULLABLE
willingness_to_pay	BOOLEAN	Disposición a pagar	NULLABLE
suggested_price	DECIMAL(8,2)	Precio sugerido por entrevistado	NULLABLE
nps_score	INT	Puntuación NPS si aplica	RANGE 0-10, NULLABLE
follow_up_consented	BOOLEAN	Acepta seguimiento	DEFAULT FALSE
 
4. Catálogo de Tipos de Experimentos
El sistema ofrece plantillas de experimentos predefinidos, adaptados a negocios locales con recursos limitados.
4.1 Experimentos de Bajo Coste
Tipo	Descripción	Coste	Tiempo	Valida
landing_page	Landing page con CTA para medir interés	0-50€	1-2 días	Problema, Solución
survey	Encuesta estructurada a potenciales clientes	0€	1-3 días	Cliente, Problema
interview	Entrevistas de descubrimiento 1:1	0€	1-2 semanas	Problema, Cliente, Precio
smoke_test	Anuncio falso para medir demanda	10-50€	3-7 días	Solución, Canal
concierge	Servicio manual antes de automatizar	Variable	2-4 semanas	Solución, Precio
wizard_of_oz	Simular producto con proceso manual	Variable	2-4 semanas	Solución
pre_sale	Venta anticipada antes de crear el producto	0€	1-2 semanas	Precio, Solución
social_proof	Testear mensajes en redes sociales	0-20€	3-5 días	Canal, Cliente
4.2 Template: Landing Page Test
Configuración detallada para el experimento más común:
•	Objetivo: Medir interés real en la propuesta de valor
•	Setup: Landing page con titular, propuesta, y CTA (email, WhatsApp, pre-pedido)
•	Tráfico: Facebook/Instagram Ads segmentados o tráfico orgánico
•	Muestra mínima: 100 visitas para resultados significativos
•	Métrica principal: Tasa de conversión del CTA
•	Criterio de éxito típico: > 5% de conversión para validar interés
4.3 Template: Customer Interview
•	Objetivo: Entender problemas reales y validar segmento de cliente
•	Formato: Entrevista semiestructurada de 20-30 minutos
•	Muestra mínima: 5 entrevistas para patrones, 10-15 para saturación
•	Preguntas clave: Háblame de la última vez que..., ¿Cómo lo resuelves hoy?, ¿Qué tan frecuente es?
•	Criterio de éxito: > 60% expresan el problema activamente, > 30% ya gastan dinero en soluciones
 
5. Flujos de Automatización (ECA)
5.1 ECA-MVP-001: Generación de Hipótesis desde Canvas
Trigger: Canvas alcanza completeness_score >= 50% Y es primera vez
1.	Analizar bloques del Canvas buscando suposiciones implícitas
2.	Generar 3-5 hipótesis sugeridas basadas en el contenido
3.	Priorizar por risk_level: primero problem > customer > solution
4.	Crear hipótesis en estado 'draft' para revisión del usuario
5.	Notificar al usuario con invitación a revisar y validar hipótesis
5.2 ECA-MVP-002: Inicio de Experimento
Trigger: validation_experiment.status cambia a 'running'
6.	Actualizar hypothesis.status a 'testing'
7.	Si experiment_type = 'landing_page': crear landing en el sistema
8.	Si experiment_type = 'survey': crear webform con plantilla
9.	Programar recordatorios diarios de registro de métricas
10.	Enviar guía de ejecución del experimento al usuario
5.3 ECA-MVP-003: Análisis de Resultados
Trigger: validation_experiment.status cambia a 'completed'
11.	Calcular métricas agregadas del experimento
12.	Comparar current_value de hipótesis con target_value
13.	Si current_value >= target_value: sugerir status = 'validated'
14.	Si current_value < target_value * 0.5: sugerir status = 'invalidated'
15.	Generar resumen de resultados con IA
16.	Notificar al usuario con resultados y opciones de siguiente paso
5.4 ECA-MVP-004: Documentación de Aprendizaje
Trigger: hypothesis.status cambia a 'validated' O 'invalidated' O 'pivoted'
17.	Solicitar al usuario documentación de learnings
18.	Registrar decisión next_action con justificación
19.	Si next_action = 'pivot': crear nueva hipótesis derivada
20.	Actualizar Business Model Canvas si hay cambios
21.	Asignar créditos de impacto por ciclo de aprendizaje completado
 
6. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/hypotheses	Listar hipótesis del usuario
GET	/api/v1/hypotheses/{id}	Detalle de hipótesis con experimentos
POST	/api/v1/hypotheses	Crear nueva hipótesis
PATCH	/api/v1/hypotheses/{id}	Actualizar hipótesis (status, learnings)
POST	/api/v1/hypotheses/generate	Generar hipótesis desde Canvas con IA
GET	/api/v1/experiments	Listar experimentos del usuario
GET	/api/v1/experiments/{id}	Detalle de experimento con métricas
POST	/api/v1/experiments	Crear nuevo experimento
PATCH	/api/v1/experiments/{id}	Actualizar experimento
POST	/api/v1/experiments/{id}/start	Iniciar experimento
POST	/api/v1/experiments/{id}/complete	Completar experimento
POST	/api/v1/experiments/{id}/metrics	Registrar métrica del experimento
GET	/api/v1/experiments/{id}/metrics	Listar métricas del experimento
POST	/api/v1/experiments/{id}/interviews	Registrar entrevista
GET	/api/v1/experiments/{id}/interviews	Listar entrevistas del experimento
GET	/api/v1/experiment-templates	Listar plantillas de experimentos
GET	/api/v1/validation-dashboard	Dashboard resumen de validación
 
7. Dashboard de Validación
7.1 Vista Principal
El dashboard presenta el estado de validación del modelo de negocio de un vistazo:
•	Scorecard de Validación: % de hipótesis críticas validadas (target: 100% antes de escalar)
•	Mapa de Riesgos: Visualización de hipótesis por tipo y estado
•	Timeline de Ciclos: Histórico de ciclos Build-Measure-Learn completados
•	Experimentos Activos: Lista de experimentos en curso con progreso
•	Aprendizajes Clave: Feed de insights documentados
7.2 Indicadores de Progreso
Indicador	Cálculo	Target
Validation Score	(Hipótesis validadas / Total críticas) × 100	> 80% para escalar
Learning Velocity	Ciclos completados por semana	> 1 ciclo/semana
Pivot Rate	Hipótesis pivotadas / Total testeadas	< 50% (indica buen targeting inicial)
Cost per Learning	Gasto total / Aprendizajes documentados	< 100€/aprendizaje
Time to Validation	Días desde creación hasta validación	< 14 días por hipótesis
7.3 Integración con Canvas
El dashboard enlaza visualmente con el Business Model Canvas:
•	Cada bloque del Canvas muestra su estado de validación (verde/amarillo/rojo)
•	Click en bloque → ver hipótesis y experimentos asociados
•	Alertas visuales en bloques con suposiciones no validadas
 
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades hypothesis, validation_experiment. Migrations.	36_Canvas completado
Sprint 2	Semana 3-4	Sistema de métricas. experiment_metric. customer_interview.	Sprint 1
Sprint 3	Semana 5-6	Templates de experimentos. Landing page builder básico.	Sprint 2
Sprint 4	Semana 7-8	Integración con Canvas. Generación de hipótesis con IA.	Sprint 3
Sprint 5	Semana 9-10	Dashboard de validación. Visualizaciones. ECA rules.	Sprint 4
Sprint 6	Semana 11-12	Reportes de aprendizaje. Exportación. QA.	Sprint 5
8.1 KPIs de Éxito
KPI	Target	Medición
Adopción del sistema	> 60% de emprendedores	% que crea al menos 1 hipótesis
Ciclos completados	> 3 por emprendedor	Media de ciclos Build-Measure-Learn
Documentación de learnings	> 80%	% de hipótesis con learnings documentados
Tiempo medio de ciclo	< 10 días	Días desde hipótesis hasta decisión
Tasa de validación	50-70%	% de hipótesis que resultan validadas
9. Conclusión: Filosofía "Sin Humo"
El Sistema de Validación de MVP es la materialización técnica de la filosofía "Sin Humo" del Ecosistema Jaraba aplicada al emprendimiento:
•	No asumas, valida: Cada suposición del modelo de negocio debe ser testeada con datos reales
•	Métricas reales, no vanidad: Enfocarse en indicadores que predicen éxito del negocio
•	Fallar barato y rápido: Descubrir que una idea no funciona ANTES de invertir mucho
•	Documentar el aprendizaje: Cada pivote o perseverancia fundamentado y registrado
El objetivo final es que ningún emprendedor del Ecosistema Jaraba invierta tiempo y recursos significativos en un modelo de negocio no validado.
--- Fin del Documento ---
37_Emprendimiento_MVP_Validation_v1.docx | Jaraba Impact Platform | Enero 2026
