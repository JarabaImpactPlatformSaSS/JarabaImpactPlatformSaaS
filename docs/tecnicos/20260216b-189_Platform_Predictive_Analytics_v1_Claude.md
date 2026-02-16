
PREDICTIVE ANALYTICS
Modelos Predictivos: Churn, Lead Scoring, Forecasting y Anomaly Detection
Nivel de Madurez: N2
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	189_Platform_Predictive_Analytics_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N2
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Modelos predictivos para la plataforma SaaS: churn prediction, lead scoring, forecasting MRR/ARR, anomaly detection, cohort analysis y revenue attribution multi-touch. Extiende el doc 116 (Advanced Analytics) con capacidades de machine learning.

1.1 Modelos Predictivos
Modelo	Input	Output	Frecuencia	Impacto
Churn Prediction	Uso, tickets, pagos, engagement	Risk score 0-100	Diario	Reduce churn 15-25%
Lead Scoring	Comportamiento, perfil, intent	Score 0-100	Tiempo real	Mejora conversion 20%
MRR Forecasting	Historico MRR, pipeline, churn	Proyeccion 3-12 meses	Semanal	Planificacion financiera
Anomaly Detection	Metricas de uso y revenue	Alertas automaticas	Continuo	Deteccion temprana
Cohort Analysis	Signup date, vertical, plan	Retention curves	Mensual	Product insights
Revenue Attribution	Touchpoints marketing	Attribution % por canal	Semanal	Optimiza spend
 
2. Churn Prediction Model
2.1 Features de Input
Feature	Tipo	Peso Relativo	Fuente
Dias sin login	Numerico	Alto	Session logs
% features usadas	Numerico	Alto	Usage tracking
Tickets soporte/mes	Numerico	Medio	Helpdesk
NPS score	Numerico	Medio	Surveys
Pagos fallidos	Numerico	Alto	Stripe
Engagement email	Numerico	Bajo	SendGrid
Tiempo en plan actual	Numerico	Medio	Billing
# usuarios activos/total	Ratio	Alto	Session logs

2.2 Modelo de Datos: churn_prediction
Campo	Tipo	Descripcion
id	UUID	Identificador
tenant_id	UUID FK	Tenant evaluado
risk_score	INT (0-100)	Score de riesgo de churn
risk_level	ENUM	low|medium|high|critical
contributing_factors	JSON	Factores que contribuyen al riesgo
recommended_actions	JSON	Acciones sugeridas de retencion
predicted_churn_date	DATE	Fecha estimada de churn
calculated_at	TIMESTAMP	Fecha del calculo
model_version	VARCHAR(20)	Version del modelo
accuracy_confidence	DECIMAL(3,2)	Confianza del modelo
 
3. Lead Scoring
3.1 Scoring Model
Evento	Puntos	Categoria
Signup (crear cuenta)	10	Engagement
Completar onboarding	15	Activation
Usar feature premium	20	Intent
Invitar team member	25	Expansion
Contactar ventas	30	Intent
Visitar pricing page	15	Intent
Descargar whitepaper	10	Engagement
Asistir webinar	20	Engagement
Trial usage > 50%	25	Activation
Referir a otro usuario	30	Advocacy
 
4. Implementacion Tecnica
4.1 Stack ML
Componente	Tecnologia	Justificacion
Feature Store	Redis + MariaDB views	Rendimiento + persistencia
Model Training	Python (scikit-learn)	Modelos clasicos, rapido
Model Serving	PHP wrapper + Python API	Integracion Drupal nativa
Scheduling	ECA + cron	Consistente con plataforma
Monitoring	Prometheus + custom metrics	Drift detection

4.2 Modulo: jaraba_predictive
•	src/Service/ChurnPredictor.php: Calculo de churn risk score
•	src/Service/LeadScorer.php: Scoring de leads en tiempo real
•	src/Service/ForecastEngine.php: Proyecciones MRR/ARR
•	src/Service/AnomalyDetector.php: Deteccion de anomalias en metricas
•	src/Service/CohortAnalyzer.php: Analisis de cohortes automatizado
•	src/Service/AttributionEngine.php: Revenue attribution multi-touch
 
5. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
Churn Prediction	12-15h	540-675	CRITICA
Lead Scoring	8-10h	360-450	ALTA
MRR Forecasting	8-10h	360-450	ALTA
Anomaly Detection	6-8h	270-360	MEDIA
Cohort Analysis	5-6h	225-270	MEDIA
Revenue Attribution	6-8h	270-360	MEDIA
Dashboard + Alertas	6-8h	270-360	ALTA
TOTAL	51-65h	2,295-2,925	N2

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
