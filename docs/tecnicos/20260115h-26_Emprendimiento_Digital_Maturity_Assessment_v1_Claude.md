EVALUACIÓN DE MADUREZ DIGITAL
Digital Maturity Assessment
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	26_Emprendimiento_Digital_Maturity_Assessment
Dependencias:	25_Business_Diagnostic, Calculadora_Madurez_Digital
 
1. Resumen Ejecutivo
La Evaluación de Madurez Digital es una auditoría profunda post-TTV que analiza 5 dimensiones del negocio: Presencia Online, Operaciones Digitales, Ventas Digitales, Marketing Digital y Automatización. Genera un scoring 0-100 con benchmarks sectoriales y un plan de mejora detallado.
1.1 Diferencia con Calculadora TTV
Aspecto	Calculadora TTV	Assessment Profundo
Objetivo	Captación rápida (<60 seg)	Diagnóstico completo (15-20 min)
Preguntas	4 preguntas básicas	40-60 preguntas detalladas
Output	Nivel + CTA inmediato	Informe + Plan de mejora
Momento	Pre-registro	Post-registro, journey activo
Benchmarks	Genéricos	Por sector específico
1.2 Las 5 Dimensiones
Dimensión	Peso	Qué Evalúa
Presencia Online	20%	Web, redes sociales, SEO local, reputación
Operaciones Digitales	25%	Gestión, facturación, inventario, CRM
Ventas Digitales	25%	E-commerce, pagos online, omnicanalidad
Marketing Digital	20%	Contenido, email, ads, analytics
Automatización	10%	Flujos automáticos, integraciones, IA
 
2. Arquitectura de Datos
2.1 Entidad: maturity_assessment
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
user_id	INT	FK users.uid
diagnostic_id	INT	FK business_diagnostic.id
sector	VARCHAR(32)	Sector del negocio para benchmarks
assessment_date	DATETIME	Fecha de evaluación
total_score	INT	Puntuación global 0-100
presence_score	INT	Puntuación Presencia Online 0-100
operations_score	INT	Puntuación Operaciones 0-100
sales_score	INT	Puntuación Ventas Digitales 0-100
marketing_score	INT	Puntuación Marketing 0-100
automation_score	INT	Puntuación Automatización 0-100
answers	JSON	Todas las respuestas estructuradas
gaps_identified	JSON	Gaps detectados con prioridad
recommendations	JSON	Recomendaciones generadas
benchmark_position	VARCHAR(16)	ENUM: below_avg|average|above_avg|top
sector_percentile	INT	Percentil dentro del sector
improvement_plan	JSON	Plan de mejora estructurado
previous_assessment_id	INT	Assessment anterior para comparar
score_change	INT	Variación vs assessment anterior
status	VARCHAR(16)	ENUM: in_progress|completed|expired
 
3. Cuestionario por Dimensión
3.1 Presencia Online (8-10 preguntas)
#	Pregunta	Opciones (puntos)
P1	¿Tienes página web propia?	No (0), En construcción (5), Sí básica (10), Sí optimizada (15), Sí con e-commerce (20)
P2	¿Tu web es responsive (móvil)?	No sé (0), No (5), Parcialmente (10), Sí (15)
P3	¿Tienes Google My Business?	No (0), Reclamado sin completar (5), Completo (10), Optimizado con reseñas (15)
P4	¿En cuántas redes sociales estás activo?	Ninguna (0), 1 (5), 2-3 (10), 4+ (15)
P5	¿Con qué frecuencia publicas contenido?	Nunca (0), Esporádico (5), Semanal (10), Diario (15)
P6	¿Tienes reseñas online?	No (0), <10 (5), 10-50 (10), 50+ (15)
P7	¿Tu negocio aparece en Google Maps?	No (0), Sí sin optimizar (10), Sí optimizado (20)
3.2 Operaciones Digitales (10-12 preguntas)
#	Pregunta	Opciones (puntos)
O1	¿Cómo gestionas la facturación?	Manual/papel (0), Excel (5), Software básico (10), Software integrado (15)
O2	¿Tienes sistema de gestión de clientes (CRM)?	No (0), Excel (5), CRM básico (10), CRM avanzado (15)
O3	¿Cómo controlas el inventario?	No controlo (0), Manual (5), Excel (10), Software (15)
O4	¿Usas herramientas de productividad cloud?	No (0), Algunas (10), Suite completa (20)
O5	¿Tienes email con dominio propio?	No (0), Sí sin usar (5), Sí activo (15)
O6	¿Cómo gestionas citas/reservas?	Teléfono/WhatsApp (0), Formulario (10), Sistema online (20)
 
3.3 Ventas Digitales (8-10 preguntas)
#	Pregunta	Opciones (puntos)
V1	¿Vendes online?	No (0), Marketplace (10), Web propia (15), Omnicanal (20)
V2	¿Qué % de ventas son online?	0% (0), 1-10% (5), 11-30% (10), 31-50% (15), >50% (20)
V3	¿Aceptas pagos con tarjeta?	No (0), Solo presencial (10), Online también (20)
V4	¿Tienes catálogo digital de productos?	No (0), PDF (5), Web (10), Interactivo (15)
V5	¿Ofreces envíos a domicilio?	No (0), Manual (10), Integrado (20)
V6	¿Tienes sistema de pedidos online?	No (0), WhatsApp (5), Formulario (10), Carrito (20)
3.4 Marketing Digital (8-10 preguntas)
#	Pregunta	Opciones (puntos)
M1	¿Tienes estrategia de contenidos?	No (0), Improvisada (5), Planificada (10), Con calendario (15)
M2	¿Haces email marketing?	No (0), Esporádico (5), Regular (10), Automatizado (20)
M3	¿Inviertes en publicidad digital?	No (0), Ocasional (5), Regular (10), Optimizada (20)
M4	¿Mides resultados de marketing?	No (0), Básico (10), Analytics completo (20)
M5	¿Tienes base de datos de clientes?	No (0), Desorganizada (5), Organizada (10), Segmentada (15)
3.5 Automatización (5-6 preguntas)
#	Pregunta	Opciones (puntos)
A1	¿Tienes respuestas automáticas configuradas?	No (0), Email (10), Multicanal (20)
A2	¿Usas herramientas de automatización?	No (0), Básicas (10), Avanzadas (20)
A3	¿Tus sistemas están integrados entre sí?	No (0), Algunos (10), Totalmente (20)
A4	¿Usas inteligencia artificial en tu negocio?	No (0), Curiosidad (5), Uso básico (10), Integrada (20)
 
4. Sistema de Scoring y Benchmarks
4.1 Cálculo de Puntuación
Total_Score = (Presence×0.20) + (Operations×0.25) + (Sales×0.25) + (Marketing×0.20) + (Automation×0.10)
4.2 Niveles de Madurez
Rango	Nivel	Descripción
0-20	Analógico	Negocio tradicional sin presencia digital relevante
21-40	Emergente	Primeros pasos digitales, presencia básica
41-60	En Desarrollo	Herramientas digitales en uso, potencial de mejora
61-80	Digitalizado	Operaciones digitalizadas, venta online activa
81-100	Avanzado	Negocio digital-first, automatización, datos
4.3 Benchmarks por Sector
Sector	Media	Top 25%	Expectativa Ecosistema
Comercio local	35	55	65+
Hostelería	40	60	70+
Servicios profesionales	45	65	75+
Artesanía/Manufactura	30	50	60+
Agricultura	25	45	55+
 
5. Generación de Plan de Mejora
El sistema genera automáticamente un plan de mejora priorizado basado en gaps detectados:
5.1 Priorización de Gaps
Prioridad	Criterio	Ejemplo
🔴 Crítica	Gap en dimensión con peso alto + score <30	Sin web ni redes (Presencia)
🟠 Alta	Gap en dimensión con peso alto + score <50	Facturación manual (Operaciones)
🟡 Media	Gap en dimensión secundaria o score 50-70	Sin email marketing (Marketing)
🟢 Baja	Optimización de dimensión ya funcional	Mejorar automatizaciones existentes
5.2 Conexión con Itinerarios
Cada gap se mapea automáticamente a tareas del itinerario de digitalización:
•	Gap 'sin_web' → Tarea 'Crear landing page básica'
•	Gap 'sin_gmb' → Tarea 'Configurar Google My Business'
•	Gap 'facturacion_manual' → Tarea 'Implementar software de facturación'
•	Gap 'sin_ecommerce' → Tarea 'Montar tienda online'
 
6. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/maturity-assessment	Obtener assessment actual del usuario
POST	/api/v1/maturity-assessment/start	Iniciar nuevo assessment
PATCH	/api/v1/maturity-assessment/{id}	Guardar respuestas parciales
POST	/api/v1/maturity-assessment/{id}/complete	Finalizar y calcular scores
GET	/api/v1/maturity-assessment/{id}/report	Obtener informe completo
GET	/api/v1/maturity-assessment/{id}/improvement-plan	Obtener plan de mejora
GET	/api/v1/maturity-assessment/history	Historial de assessments
GET	/api/v1/maturity-assessment/benchmarks/{sector}	Benchmarks del sector
7. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidad maturity_assessment. Cuestionario completo.
Sprint 2	Semana 3-4	Motor de scoring. Benchmarks por sector.
Sprint 3	Semana 5-6	Generador de plan de mejora. Mapeo a tareas.
Sprint 4	Semana 7-8	Informe PDF. Comparativa histórica. QA.
--- Fin del Documento ---
