ANÁLISIS DE BRECHAS
DOCUMENTACIÓN TÉCNICA DE IMPLEMENTACIÓN
Vertical de Emprendimiento Digital
Versión:	1.0
Fecha:	Enero 2026
Estado:	Análisis Estratégico
Vertical:	Emprendimiento Digital (Avatar: Javier)
Clasificación:	Interno - Planificación Técnica

 
1. Resumen Ejecutivo
Este documento identifica las especificaciones técnicas de implementación que NO EXISTEN actualmente en la documentación del proyecto para la vertical de Emprendimiento Digital del Ecosistema Jaraba.
1.1 Documentación Existente
La vertical de Emprendimiento cuenta actualmente con:
• Calculadora de Madurez Digital - Sistema de captación TTV (< 60 segundos)
• Rol entrepreneur definido en RBAC con permisos básicos
• Menciones en Plan Maestro - Descripción conceptual de webforms, itinerarios y Groups
• Avatares definidos (Javier, Ana, Carmen, Martín, Laura, Andrés) con sus dolores y aspiraciones
1.2 Gap Crítico Identificado
La Calculadora de Madurez Digital cubre únicamente la fase de captación (Time-to-Value). Sin embargo, todo el journey post-conversión carece de especificaciones técnicas: diagnósticos avanzados de negocio, itinerarios de digitalización, sistema de mentorías, grupos de colaboración, validación de modelos de negocio, y métricas de impacto emprendedor.
1.3 Asimetría Documental vs. Empleabilidad
La vertical de Empleabilidad cuenta con 17 documentos técnicos de implementación (08-24_Empleabilidad_*.docx) que cubren todo el journey del usuario. La vertical de Emprendimiento tiene 0 documentos equivalentes.
Vertical	Docs Técnicos	Cobertura
Empleabilidad	17 documentos	Journey completo
Emprendimiento	1 documento (TTV)	Solo captación

1.4 Resumen de Documentos Faltantes
Categoría	Docs Faltantes	Prioridad	Esfuerzo Est.	Semanas
Sistema de Diagnósticos de Negocio	3	CRÍTICA	Alto	3
Itinerarios de Digitalización	3	CRÍTICA	Alto	3
Sistema de Mentorías	3	ALTA	Medio	2
Groups de Colaboración	2	ALTA	Medio	2
Validación de Modelo de Negocio	3	ALTA	Alto	3
Sistema de Productos/Kits Digitales	2	MEDIA	Medio	2
Dashboard y Métricas	3	MEDIA	Medio	2
IA y Automatización	2	ALTA	Alto	3
TOTAL	21	-	-	20
 
2. Sistema de Diagnósticos de Negocio
El Plan Maestro define que la vertical de Emprendimiento requiere "Webforms de diagnóstico con lógica condicional". Más allá de la Calculadora de Madurez Digital (TTV), se necesitan diagnósticos profundos para el journey post-conversión.
Documento Requerido	Descripción / Contenido Esperado	Prioridad
25_Emprendimiento_Business_Diagnostic_Core_v1.docx	Sistema completo de diagnóstico empresarial: entidades Drupal (business_diagnostic, diagnostic_section, diagnostic_answer), motor de scoring multidimensional, categorización por fase de negocio (idea, validación, crecimiento, escalado), generación automática de roadmap	CRÍTICA
26_Emprendimiento_Digital_Maturity_Assessment_v1.docx	Evaluación profunda de madurez digital post-TTV: análisis de presencia online, operaciones, ventas digitales, marketing y automatización. Scoring 0-100 con benchmarks sectoriales y plan de mejora detallado	CRÍTICA
27_Emprendimiento_Competitive_Analysis_Tool_v1.docx	Herramienta de análisis competitivo: identificación de competidores, matriz de posicionamiento, análisis de gaps de mercado, oportunidades de diferenciación. Integración con web scraping básico	ALTA

3. Itinerarios de Digitalización
Equivalente al sistema LMS de Empleabilidad, pero orientado a la transformación digital de negocios. El Plan Maestro menciona "itinerarios personalizados" pero no existe especificación técnica.
Documento Requerido	Descripción / Contenido Esperado	Prioridad
28_Emprendimiento_Digitalization_Paths_v1.docx	Sistema de rutas de digitalización: entidad digitalization_path con fases, módulos y recursos. Asignación automática según diagnóstico. Integración con Método Jaraba (3 fases: Diagnóstico, Implementación, Optimización)	CRÍTICA
29_Emprendimiento_Action_Plans_v1.docx	Planes de acción por vertical: entidad action_plan con tareas, plazos, recursos y dependencias. Sistema de checklists interactivos. Notificaciones automáticas. Templates por sector (comercio, servicios, agro)	CRÍTICA
30_Emprendimiento_Progress_Milestones_v1.docx	Sistema de hitos y progreso: entidades milestone y achievement. Tracking de avance por fase. Gamificación con créditos de impacto. Celebración de logros y certificaciones parciales	ALTA
 
4. Sistema de Mentorías
El rol consultant tiene permisos para "Ofrecer mentorías" y "Cobrar por servicios" según 04_Core_Permisos_RBAC, pero no existe documentación técnica del sistema de mentorías.
Documento Requerido	Descripción / Contenido Esperado	Prioridad
31_Emprendimiento_Mentoring_Core_v1.docx	Sistema core de mentorías: entidades mentor_profile, mentoring_session, mentoring_package. Matching mentor-emprendedor por sector y necesidades. Calendario de disponibilidad. Integración Stripe Connect para pagos	ALTA
32_Emprendimiento_Mentoring_Sessions_v1.docx	Gestión de sesiones: reservas, confirmaciones, recordatorios. Videollamadas integradas (Jitsi/Zoom). Notas de sesión. Sistema de tareas post-sesión. Evaluación bidireccional mentor-emprendedor	ALTA
33_Emprendimiento_Mentor_Dashboard_v1.docx	Dashboard del mentor/consultor: pipeline de clientes, sesiones programadas, ingresos, métricas de impacto. Gestión de disponibilidad. Reportes para entidades financiadoras	MEDIA

5. Groups de Colaboración
El Plan Maestro menciona "Groups de colaboración" para emprendedores, utilizando el Group Module de Drupal. NO EXISTE especificación técnica de cómo implementar estos grupos.
Documento Requerido	Descripción / Contenido Esperado	Prioridad
34_Emprendimiento_Collaboration_Groups_v1.docx	Sistema de grupos de colaboración: tipos de grupo (cohorte, sector, territorio, interés). Funcionalidades por tipo. Roles dentro del grupo. Contenido compartido. Foro de discusión. Eventos grupales	ALTA
35_Emprendimiento_Networking_Events_v1.docx	Sistema de eventos y networking: entidad networking_event. Eventos virtuales y presenciales. Sistema de inscripción. Matchmaking entre asistentes. Seguimiento post-evento. Integración con CRM	MEDIA
 
6. Validación de Modelo de Negocio
Componente crítico para emprendedores en fase inicial. Herramientas para validar ideas antes de invertir recursos significativos. Filosofía "Sin Humo" aplicada: métricas reales, no vanidad.
Documento Requerido	Descripción / Contenido Esperado	Prioridad
36_Emprendimiento_Business_Model_Canvas_v1.docx	Canvas de modelo de negocio digital: entidad business_model_canvas con los 9 bloques. Versiones y comparativas. Feedback de mentores. Exportación PDF. Templates por sector. Integración con diagnósticos	ALTA
37_Emprendimiento_MVP_Validation_v1.docx	Sistema de validación de MVP: entidades mvp_hypothesis, validation_experiment, experiment_result. Metodología Lean Startup. Tracking de métricas de validación. Pivots y decisiones documentadas	ALTA
38_Emprendimiento_Financial_Projections_v1.docx	Herramienta de proyecciones financieras: plantillas de P&L, cash flow, punto de equilibrio. Escenarios (pesimista, realista, optimista). Métricas SaaS si aplica. Comparativa con benchmarks sectoriales	ALTA

7. Sistema de Productos/Kits Digitales
La escalera de valor incluye "Kits de digitalización", "Cursos Autodigitales Express" y "Membresía Club Jaraba". NO EXISTE documentación técnica para la entrega y gestión de estos productos.
Documento Requerido	Descripción / Contenido Esperado	Prioridad
39_Emprendimiento_Digital_Kits_v1.docx	Sistema de kits digitales: entidad digital_kit con recursos, plantillas, checklists. Desbloqueo progresivo. Personalización por sector. Integración con itinerarios. Tracking de uso y completitud	MEDIA
40_Emprendimiento_Membership_System_v1.docx	Sistema de membresías (Club Jaraba): niveles (Básico, Élite), beneficios por nivel, contenido exclusivo, renovaciones automáticas. Integración Stripe subscriptions. Métricas de retención	MEDIA
 
8. Dashboards y Métricas de Impacto
Métricas clave definidas en Plan Maestro: "Diagnósticos, Negocios creados, GMV". Se requieren dashboards para emprendedores, mentores y administradores.
Documento Requerido	Descripción / Contenido Esperado	Prioridad
41_Emprendimiento_Dashboard_Entrepreneur_v1.docx	Dashboard del emprendedor: progreso en itinerario, próximos pasos, métricas de negocio (ventas, clientes, conversión), sesiones de mentoría, recursos disponibles, logros y badges	MEDIA
42_Emprendimiento_Dashboard_Program_v1.docx	Dashboard de programa (para entidades): cohortes activas, progreso agregado, métricas de impacto (negocios creados, empleos generados, facturación), burn rate de grants, reportes para financiadores	MEDIA
43_Emprendimiento_Impact_Metrics_v1.docx	Sistema de métricas de impacto: KPIs de emprendimiento (supervivencia a 1 año, facturación media, empleos creados), alineación con ODS, metodología SROI, exportación para informes ESG y justificación de subvenciones	ALTA

9. Inteligencia Artificial y Automatización
Empleabilidad tiene 20_AI_Copilot y 21_Recommendation_System. Emprendimiento necesita equivalentes adaptados a su contexto.
Documento Requerido	Descripción / Contenido Esperado	Prioridad
44_Emprendimiento_AI_Business_Copilot_v1.docx	Copiloto IA para emprendedores: asistente contextual para tareas de negocio, generación de textos comerciales, análisis de competencia, respuestas a dudas de digitalización. Integración RAG con base de conocimiento	ALTA
45_Emprendimiento_Automation_Flows_v1.docx	Flujos de automatización específicos: ECA rules para onboarding, seguimiento de progreso, recordatorios de tareas, alertas de mentores, triggers de gamificación. Make.com para integraciones externas	ALTA
 
10. Roadmap de Documentación Propuesto
Plan de desarrollo de las 21 especificaciones técnicas identificadas, organizado por fases de implementación:
Fase	Documentos	Dependencias	Timeline Est.
Fase 1	Business Diagnostic Core, Digitalization Paths, Progress Milestones	Core entities existente, Calculadora TTV	Semanas 1-3
Fase 2	Action Plans, Business Model Canvas, MVP Validation	Fase 1 completada	Semanas 4-6
Fase 3	Mentoring Core, Sessions, Collaboration Groups	Fase 2 + Stripe Connect	Semanas 7-10
Fase 4	Dashboards, Impact Metrics, AI Copilot, Automation	Fase 3 + Qdrant RAG	Semanas 11-14
Fase 5	Digital Kits, Membership, Networking Events, Financial Projections	Todas las anteriores	Semanas 15-20

11. Conclusión y Recomendaciones
La vertical de Emprendimiento tiene documentación estratégica sólida (avatares, escaleras de valor, flujos conceptuales), pero carece de las 21 especificaciones técnicas de implementación necesarias para construir el sistema completo.
11.1 Acciones Inmediatas
1. Priorizar documentación del Sistema de Diagnósticos: Sin diagnóstico profundo, no hay personalización del journey
2. Especificar Itinerarios de Digitalización: Es el equivalente al LMS que guía todo el proceso
3. Definir Sistema de Mentorías: Diferenciador competitivo y fuente de ingresos premium
11.2 Riesgos de No Documentar
• Implementación inconsistente sin especificaciones claras
• Asimetría funcional entre verticales (Empleabilidad muy avanzada vs. Emprendimiento básico)
• Imposibilidad de justificar impacto ante entidades financiadoras
• Deuda técnica acumulada por desarrollar sin blueprint
• Pérdida de coherencia con la filosofía "Sin Humo" (desarrollo sin documentación = humo)
11.3 Sinergia con Empleabilidad
Muchos componentes pueden reutilizar arquitectura de Empleabilidad: sistema de progreso, dashboards, métricas de impacto, AI Copilot. Se recomienda diseñar con abstracción cross-vertical donde sea posible para maximizar reutilización de código.
--- Fin del Documento ---
