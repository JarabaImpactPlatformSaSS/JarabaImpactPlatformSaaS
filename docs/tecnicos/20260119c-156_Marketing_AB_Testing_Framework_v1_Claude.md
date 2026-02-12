A/B TESTING FRAMEWORK
Extensión jaraba_analytics
Motor de Experimentos con Significancia Estadística y Auto-Optimización
Versión:	1.0
Fecha:	Enero 2026
Código:	156_Marketing_AB_Testing_Framework_v1
Estado:	Especificación Técnica para Implementación
Horas Estimadas:	12-18 horas
Módulo Base:	jaraba_analytics
Dependencias:	jaraba_core, jaraba_email, jaraba_content_hub
1. Resumen Ejecutivo
El A/B Testing Framework proporciona una infraestructura completa para ejecutar experimentos controlados en landing pages, emails, y flujos de usuario. Incluye cálculo de significancia estadística, segmentación de audiencias, auto-finalización de tests y recomendaciones basadas en datos para optimización continua del funnel de conversión.
1.1 Capacidades Principales
•	Experimentos A/B y A/B/n (múltiples variantes)
•	Tests multivariante (MVT) para optimización combinatoria
•	Cálculo automático de significancia estadística (p-value)
•	Determinación de tamaño de muestra mínimo
•	Auto-stop cuando se alcanza significancia
•	Segmentación por dispositivo, ubicación, fuente de tráfico
•	Integración nativa con jaraba_email y landing pages
•	Dashboard de resultados en tiempo real
1.2 Áreas de Experimentación
Área	Elementos Testables	Métricas
Email Marketing	Subject lines, preview text, CTA, send time, contenido	Open rate, CTR
Landing Pages	Headlines, hero images, formularios, CTAs, layout	Conversion rate
Pricing Pages	Precios, features destacados, planes	Signup rate
Onboarding	Pasos del flujo, copy, gamificación	Completion rate
CTAs	Texto, color, posición, tamaño	Click rate
Formularios	Número de campos, orden, validación	Submit rate
2. Arquitectura Técnica
2.1 Entidad: experiment
Definición del experimento A/B.
Campo	Tipo	Descripción
id	SERIAL	Primary key
uuid	VARCHAR(36)	Identificador público único
tenant_id	INT FK	Referencia a tenant
name	VARCHAR(200)	Nombre descriptivo del experimento
hypothesis	TEXT	Hipótesis a validar
experiment_type	VARCHAR(20)	ab|abn|mvt
target_type	VARCHAR(30)	email|landing|cta|form|onboarding
target_id	VARCHAR(100)	ID del recurso bajo test
primary_metric	VARCHAR(50)	open_rate|ctr|conversion|signup|completion
secondary_metrics	JSON	Métricas adicionales a trackear
traffic_allocation	DECIMAL(5,2)	% de tráfico en experimento (0-100)
status	VARCHAR(20)	draft|running|paused|completed|winner_deployed
started_at	TIMESTAMP	Fecha de inicio
ended_at	TIMESTAMP	Fecha de finalización
min_sample_size	INT	Muestra mínima por variante
confidence_level	DECIMAL(4,2)	Nivel de confianza requerido (95, 99)
auto_stop	BOOLEAN	Finalizar automáticamente al alcanzar significancia
winner_variant_id	INT FK	Variante ganadora (post-test)
segment_rules	JSON	Reglas de segmentación de audiencia
created_by	INT FK	Usuario que creó el experimento
created_at	TIMESTAMP	Fecha de creación
updated_at	TIMESTAMP	Última actualización
2.2 Entidad: experiment_variant
Variantes dentro de un experimento.
Campo	Tipo	Descripción
id	SERIAL	Primary key
experiment_id	INT FK	Referencia a experiment
name	VARCHAR(50)	Control, Variant A, Variant B, etc.
is_control	BOOLEAN	Es la versión de control
weight	DECIMAL(5,2)	% de tráfico asignado (suma = 100)
changes	JSON	Cambios aplicados vs control
content_snapshot	JSON	Snapshot del contenido de la variante
impressions	INT	Número de impresiones (calculado)
conversions	INT	Número de conversiones (calculado)
conversion_rate	DECIMAL(6,4)	Tasa de conversión (calculado)
 
2.3 Entidad: experiment_exposure
Registro de exposiciones de usuarios a variantes.
Campo	Tipo	Descripción
id	SERIAL	Primary key
experiment_id	INT FK	Referencia a experiment
variant_id	INT FK	Variante asignada
visitor_id	VARCHAR(36)	ID único del visitante (cookie)
user_id	INT FK NULL	Usuario autenticado (si aplica)
device_type	VARCHAR(20)	desktop|mobile|tablet
browser	VARCHAR(50)	Navegador detectado
country	VARCHAR(2)	Código país (ES, US, etc.)
utm_source	VARCHAR(100)	Fuente de tráfico
utm_campaign	VARCHAR(100)	Campaña de origen
exposed_at	TIMESTAMP	Momento de exposición
converted	BOOLEAN	Ha convertido
converted_at	TIMESTAMP	Momento de conversión
conversion_value	DECIMAL(10,2)	Valor de conversión (si aplica)
2.4 Entidad: experiment_result
Resultados estadísticos calculados.
Campo	Tipo	Descripción
id	SERIAL	Primary key
experiment_id	INT FK	Referencia a experiment
variant_id	INT FK	Variante analizada
metric_name	VARCHAR(50)	Nombre de la métrica
sample_size	INT	Tamaño de muestra
mean	DECIMAL(10,6)	Media observada
std_dev	DECIMAL(10,6)	Desviación estándar
confidence_interval_low	DECIMAL(10,6)	Límite inferior IC
confidence_interval_high	DECIMAL(10,6)	Límite superior IC
p_value	DECIMAL(10,8)	P-value vs control
is_significant	BOOLEAN	p_value < (1 - confidence_level)
lift	DECIMAL(6,4)	% mejora vs control
calculated_at	TIMESTAMP	Timestamp del cálculo
3. API REST Endpoints
3.1 Gestión de Experimentos
Método	Endpoint	Descripción
GET	/api/v1/experiments	Listar experimentos del tenant
POST	/api/v1/experiments	Crear nuevo experimento
GET	/api/v1/experiments/{uuid}	Detalle de experimento con resultados
PATCH	/api/v1/experiments/{uuid}	Actualizar configuración
POST	/api/v1/experiments/{uuid}/start	Iniciar experimento
POST	/api/v1/experiments/{uuid}/pause	Pausar experimento
POST	/api/v1/experiments/{uuid}/complete	Finalizar y declarar ganador
POST	/api/v1/experiments/{uuid}/deploy-winner	Desplegar variante ganadora
3.2 Assignment y Tracking
Método	Endpoint	Descripción
GET	/api/v1/experiments/assign	Obtener variante para visitor
POST	/api/v1/experiments/{uuid}/exposure	Registrar exposición
POST	/api/v1/experiments/{uuid}/conversion	Registrar conversión
3.3 Ejemplo: Asignación de Variante
GET /api/v1/experiments/assign?target_type=landing&target_id=home-v2&visitor_id=abc123
Response: {   "experiment_uuid": "exp-123-456",   "experiment_name": "Homepage Hero Test",   "variant": {     "id": 2,     "name": "Variant A",     "changes": {       "headline": "Digitaliza tu Negocio Hoy",       "cta_color": "#E67E22"     }   },   "is_control": false }
 
4. Motor Estadístico
4.1 Cálculo de Significancia
El framework utiliza el test Z de dos proporciones para calcular la significancia estadística de las diferencias entre variantes:
•	H0: No hay diferencia entre control y variante (p1 = p2)
•	H1: Existe diferencia significativa (p1 ≠ p2)
•	Z = (p1 - p2) / sqrt(p*(1-p)*(1/n1 + 1/n2))
•	P-value calculado desde distribución normal estándar
•	Significativo si p-value < α (típicamente 0.05)
4.2 Tamaño de Muestra Mínimo
Cálculo automático del tamaño de muestra necesario basado en:
Parámetro	Valor Default
Nivel de confianza (1-α)	95% (α = 0.05)
Poder estadístico (1-β)	80% (β = 0.20)
MDE (Minimum Detectable Effect)	Configurable (10-20% típico)
Baseline conversion rate	Calculado de datos históricos
4.3 Corrección por Múltiples Comparaciones
Para tests A/B/n con más de 2 variantes, se aplica corrección de Bonferroni: α_adjusted = α / número_de_comparaciones para evitar falsos positivos.
5. Flujos ECA (Automatización)
5.1 ECA: Asignación Sticky de Variante
Trigger: Request a recurso bajo test sin cookie de experimento
1.	Verificar si experimento está activo (status = running)
2.	Verificar si visitor cumple segment_rules
3.	Generar número aleatorio [0-100]
4.	Asignar variante según weights acumulados
5.	Crear experiment_exposure con datos del visitor
6.	Setear cookie jaraba_exp_{exp_uuid} = variant_id (30 días)
7.	Retornar contenido de la variante asignada
5.2 ECA: Registro de Conversión
Trigger: Evento de conversión (form submit, signup, purchase)
8.	Leer cookie jaraba_exp_* para identificar experimentos activos
9.	Para cada experimento con exposición:
•	Verificar que conversión coincide con primary_metric
•	Actualizar experiment_exposure.converted = true
•	Incrementar conversions en experiment_variant
10.	Disparar recálculo de resultados
5.3 ECA: Auto-Stop por Significancia
Trigger: Recálculo de experiment_result (cada hora o cada 100 conversiones)
11.	Si experiment.auto_stop = false → Salir
12.	Verificar que todas las variantes tienen min_sample_size
13.	Calcular p-value para cada variante vs control
14.	Si alguna variante tiene p-value < (1 - confidence_level):
•	Marcar experiment.status = 'completed'
•	Asignar winner_variant_id a la de mayor lift significativo
•	Notificar a created_by vía email
•	Generar reporte de resultados PDF
 
6. Integración con jaraba_email
El A/B Testing Framework se integra nativamente con jaraba_email para tests de campañas:
Elemento Testable	Implementación
Subject Line	Se crean variantes con diferentes subjects, split automático de lista
Preview Text	Combinable con subject line para tests MVT
Send Time	Envío escalonado a diferentes horas, medición de open rate por hora
CTA Button	Texto, color y posición del botón principal
Contenido	Templates diferentes para el body del email
Flujo de Email A/B Test
15.	Crear experimento con target_type = 'email' y target_id = campaign_uuid
16.	Definir variantes con cambios en subject/content
17.	Al enviar campaña, split de lista según weights
18.	Open/click trackea exposición y conversión automáticamente
19.	Al completar, opción de enviar ganadora al resto de la lista
7. Dashboard de Resultados
Componente React con visualización en tiempo real:
•	Gráfico de barras comparativo de conversion rates
•	Indicador de significancia estadística por variante
•	Intervalos de confianza visualizados
•	Progreso hacia min_sample_size
•	Tabla de métricas secundarias
•	Desglose por segmento (device, source, country)
•	Timeline de conversiones acumuladas
•	Recomendación automática basada en datos
8. Roadmap de Implementación
Sprint	Entregables	Horas
Sprint 1	Entidades DB, API CRUD experimentos, variantes básicas	4-5h
Sprint 2	Motor de asignación sticky, cookies, tracking exposiciones	3-4h
Sprint 3	Motor estadístico (p-value, sample size, CI), resultados	3-5h
Sprint 4	ECA flows (auto-stop, conversiones), integración email	2-3h
Sprint 5	Dashboard React, reportes, deploy winner, QA	2-3h
Total estimado: 12-18 horas
9. Buenas Prácticas
•	Definir hipótesis clara antes de iniciar el test
•	Testear un solo cambio a la vez (excepto MVT intencional)
•	Esperar a alcanzar min_sample_size antes de tomar decisiones
•	No detener tests prematuramente por resultados parciales
•	Documentar aprendizajes de cada experimento
•	Usar segmentación para descubrir insights por audiencia
•	Iterar: el ganador de hoy es el control de mañana
