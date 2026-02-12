
INVESTIGACIÓN DE INTEGRACIÓN H5P
PARA DRUPAL 11
Clase Mundial de Primer Nivel
JARABA IMPACT PLATFORM
Ecosistema Integrado de Empleabilidad y Emprendimiento Digital

Versión:	  1.0
Fecha:	  Febrero 2026
Código:	  H5P_Integration_Research
Dependencias:	  08_LMS_Core, 46_Training
 
1. Resumen Ejecutivo
Este documento investiga en profundidad las opciones de integración de H5P (HTML5 Package) con Drupal 11 para la Jaraba Impact Platform, con el objetivo de alcanzar una implementación de clase mundial que soporte los sistemas LMS de las verticales de Empleabilidad, Emprendimiento y los programas de Capacitación/Certificación del ecosistema.
H5P es el estándar de facto para contenido interactivo en plataformas educativas, utilizado por más de 100.000 organizaciones globalmente, incluyendo universidades del top-10 mundial, grandes corporaciones y ONGs reconocidas. La investigación identifica tres vías principales de integración, analiza su compatibilidad con Drupal 11, y proporciona una recomendación arquitectónica óptima para el ecosistema Jaraba.

CONCLUSIÓN PRINCIPAL: Se recomienda una arquitectura híbrida que combine H5P Self-Hosted (módulo Drupal nativo) para MVP con migración progresiva a H5P.com SaaS (Premium/Enterprise) vía LTI cuando el volumen lo justifique. Esta estrategia maximiza control, minimiza costes iniciales y habilita capacidades IA (Smart Import) a futuro.

2. Panorama H5P: Estado del Arte 2025-2026
2.1 Qué es H5P
H5P (HTML5 Package) es una plataforma open-source (licencia MIT) para crear y compartir contenido interactivo multimedia. Los contenidos son paquetes HTML5 autocontenidos que funcionan en cualquier navegador y dispositivo, sin dependencias de Flash o plugins externos.

Capacidades Clave
•	Más de 50 tipos de contenido interactivo (quizzes, videos interactivos, presentaciones, juegos, simulaciones)
•	Emisión nativa de statements xAPI para tracking de aprendizaje
•	Editor visual integrado en el CMS (no requiere código)
•	Importación/exportación de paquetes .h5p para portabilidad
•	Responsive y mobile-first por diseño
•	Plugins nativos para Drupal, WordPress y Moodle
•	WCAG 2.1 AA compliance en tipos de contenido core

2.2 Novedades 2025-2026

Novedad	Descripción e Impacto
Smart Import (IA)	Motor de IA generativa que convierte documentos, URLs, YouTube y audio en H5Ps interactivos automáticamente. Soporta 56 idiomas incluyendo español. Personalización mediante prompts. Disponible en planes Premium y Enterprise de H5P.com.
Multimedia Choice	Evolución de Image Choice: permite crear tareas con video, audio e imágenes como alternativas. Ideal para evaluaciones multisensoriales.
Layout Builder	Editor WYSIWYG con drag-and-drop y multi-columna para crear contenidos con layout profesional. Accesibilidad integrada.
Live Engagement	Tipos de contenido para competiciones, encuestas y polls en tiempo real. Ideal para sesiones de formación sincrónica.
CKEditor 5	Upgrade del editor de texto a CKEditor 5 dentro de H5P. Mejor UX de edición.
Mejoras Accesibilidad	Mejoras extensivas en Agamotto, Game Map y múltiples tipos de contenido para cumplimiento WCAG 2.1 AA.
Regionalización Smart Import	El servicio Smart Import ahora se hospeda por región para mejor rendimiento y cumplimiento de datos (relevante para GDPR/RGPD).

3. Opciones de Integración H5P para Drupal 11
Existen tres vías principales para integrar H5P con Drupal 11, cada una con diferentes niveles de control, coste y funcionalidad. A continuación se analizan en detalle.

3.1 Opción A: H5P Self-Hosted (Módulo Drupal Nativo)

ESTADO: El módulo H5P oficial (drupal/h5p) está en alpha perpetua pero funciona en producción. El fork h5p_package ofrece soporte activo de la comunidad Drupal para D10/D11.

Módulos Disponibles

Módulo	Proyecto D.O.	D11 Compat.	Estado	Notas
H5P (oficial)	drupal/h5p	Alpha	2.0.0-alpha5	Módulo oficial del H5P Core Team. Alpha perpetua pero funcional en producción.
H5P Package (fork)	drupal/h5p_package	Sí	Activo	Fork comunitario con soporte D10+. Parches más rápidos. Mantenido por ITCON Services.
H5P Analytics	drupal/h5p_analytics	Sí	Contrib	Captura xAPI statements de H5P y los envía a LRS externo. Colas batch con cron.
H5P Challenge	drupal/h5p_challenge	Sí	Estable	Sistema de retos/desafíos con H5P. Soporte explícito D11.
LMS H5P	drupal/lms_h5p	Sí	Contrib	Plugin de actividad H5P para Drupal LMS. xAPI + scoring automático.

Instalación
La instalación se realiza vía Composer, con H5P implementado como campo (field) en Drupal:

composer require 'drupal/h5p:^2.0@alpha'
# O el fork comunitario:
composer require 'drupal/h5p_package'
drush en h5p h5p_editor

Ventajas
•	Coste: €0 (MIT license, completamente gratuito)
•	Control total sobre datos, contenido y configuración
•	Sin dependencia de servicios externos
•	Los datos xAPI permanecen en infraestructura propia (RGPD nativo)
•	Más de 50 tipos de contenido disponibles
•	Integración nativa con el sistema de permisos de Drupal
•	Compatible con Paragraphs, Layout Builder y bloques custom

Limitaciones
•	Módulo oficial en estado alpha perpetuo (sin release estable)
•	No incluye LRS nativo (requiere solución complementaria)
•	Sin Smart Import (IA generativa para contenido)
•	Las librerías H5P se almacenan en sites/default/files/h5p (sin soporte S3)
•	Algunas librerías avanzadas (AR/VR) pueden no funcionar
•	Mantenimiento del módulo depende de la comunidad

3.2 Opción B: H5P.com SaaS vía LTI

ESTADO: Servicio SaaS maduro utilizado por 2.000+ organizaciones desde 2018. Integración vía LTI 1.3 con cualquier LMS compatible.

Planes y Precios

Característica	Pro	Premium	Enterprise
Precio	Desde $690/año (3 autores)	Desde ~$1.200/año (3 autores)	Cotización personalizada
Autores	3-10	3-10	Ilimitados
50+ Content Types	✓	✓	✓
Live Engagement	✓	✓	✓
Embed Codes	✓	✓	✓
LTI Integration	✓	✓	✓
Smart Import (IA)	✗	✓	Opcional
Layout Builder	✗	✓	Opcional
SSO	✗	✗	✓
LRS Externo	✗	✗	✓
Drill-down Reports	Opcional	Opcional	✓
Soporte Dedicado	Email	Email	Customer Success Manager

Ventajas SaaS
•	Smart Import: generación automática de contenido interactivo con IA en 56 idiomas
•	Hosting gestionado con CDN global (sin carga en servidor propio)
•	Actualizaciones automáticas de content types y seguridad
•	Drill-down reports y analytics avanzados vía LTI
•	Layout Builder WYSIWYG profesional
•	Live Engagement para formación sincrónica
•	Content Hub (OER) para reutilización de recursos

Limitaciones SaaS
•	Coste recurrente (desde $690/año)
•	Dependencia de servicio externo
•	Requiere implementación LTI en Drupal (desarrollo custom)
•	Multi-tenant: necesitaría organización/subcuenta por tenant
•	Datos de contenido alojados en servidores H5P (aunque GDPR compliant)

3.3 Opción C: Drupal LMS + H5P Integrado

ESTADO: Drupal LMS es un proyecto activo mantenido por Tag1 Consulting basado en Drupal 10.3+. Fork moderno de Opigno con arquitectura modular.

El módulo Drupal LMS (drupal.org/project/lms) proporciona un ecosistema LMS completo que integra nativamente con H5P a través de submmódulos especializados:

Submódulos Disponibles
•	LMS Core: Gestión de cursos, lecciones, actividades y enrollments
•	LMS xAPI: Integración de paquetes xAPI con LRS minimalista integrado para scores y estados
•	LMS H5P: Plugin de actividad H5P con scoring automático vía xAPI statements
•	LMS Certificate: Generación automática de certificados PDF al completar cursos
•	LMS Messages: Notificaciones automáticas vía Message module en eventos LMS
•	LMS Webform: Integración con Webform para actividades tipo formulario
•	LMS File Upload: Actividades de subida de archivos

Ventajas Drupal LMS
•	Arquitectura modular nativa de Drupal (no distribución monolítica)
•	Mantenido por Tag1 Consulting (empresa de referencia en Drupal)
•	LRS minimalista integrado para xAPI sin dependencias externas
•	Integración directa con H5P como tipo de actividad
•	Certificados PDF nativos
•	Compatible con Drupal 10.3+ (verificar D11 específico)

Limitaciones Drupal LMS
•	Proyecto relativamente joven (fork de Opigno, reescrito)
•	No cubierto por security advisory policy de Drupal (lms_h5p)
•	LRS minimalista: puede ser insuficiente para reporting avanzado
•	Puede colisionar con la arquitectura jaraba_lms custom existente

4. Ecosistema xAPI y Learning Record Store
La captura de statements xAPI es crítica para el tracking de progreso en el LMS de Empleabilidad. H5P emite xAPI nativamente pero Drupal no incluye LRS. Estas son las opciones disponibles:

Solución LRS	Tipo	Coste	D11 Compat.	Evaluación para Jaraba
jaraba_lms custom	Interno	€0	Nativo	YA ESPECIFICADO en doc 08. progress_record como entidad Drupal. Recomendado.
H5P Analytics Module	Bridge	€0	Sí	Captura xAPI de H5P y envía a LRS externo o interno. Complemento ideal.
Simple xAPI Module	Conector	€0	Sí	Conexión a LRS externo xAPI-compliant. Track node views. Útil como complemento.
Tin Can LRS Module	LRS completo	€0	D7 only	LRS nativo Drupal con Views + Rules. Solo D7. NO viable.
Learning Locker	LRS externo	Variable	Independiente	Open source PHP. Overengineering para MVP pero opción futura.
LMS xAPI (Drupal LMS)	LRS mini	€0	D10.3+	LRS minimalista integrado. Considerar si se adopta Drupal LMS module.

RECOMENDACIÓN xAPI: Mantener la arquitectura progress_record ya especificada en el documento 08_LMS_Core como LRS interno, complementada con el módulo h5p_analytics para captura automática de statements H5P. Filosofia Sin Humo: no añadir Learning Locker ni infraestructura nueva innecesaria.

5. Tipos de Contenido H5P Prioritarios para Jaraba
De los 50+ tipos de contenido H5P disponibles, los siguientes son críticos para las verticales del ecosistema Jaraba, mapeados a sus casos de uso específicos:

5.1 Empleabilidad (Impulso Empleo)

Tipo H5P	Caso de Uso	xAPI Verb	Prioridad
Course Presentation	Módulos teóricos con slides interactivos	completed	CRÍTICA
Interactive Video	Videos con checkpoints y preguntas	interacted	CRÍTICA
Question Set	Evaluaciones de conocimiento	scored	CRÍTICA
Branching Scenario	Simulaciones de entrevista de trabajo	completed	ALTA
Drag and Drop	Matching skills con requisitos de ofertas	answered	ALTA
Essay	Redacción de carta de presentación	answered	MEDIA
Multimedia Choice	Evaluaciones con video/audio/imagen	answered	MEDIA

5.2 Emprendimiento (Impulso Negocio)

Tipo H5P	Caso de Uso	xAPI Verb	Prioridad
Interactive Video	Masterclasses de emprendimiento	interacted	CRÍTICA
Branching Scenario	Simulaciones de toma de decisiones empresariales	completed	CRÍTICA
Course Presentation	Formación en digitalización y modelos de negocio	completed	ALTA
Interactive Book	Guías completas de creación de empresa	progressed	ALTA
Timeline	Roadmaps de madurez digital	interacted	MEDIA

5.3 Capacitación/Certificación (SEPE/Andalucía)

Tipo H5P	Caso de Uso	xAPI Verb	Prioridad
Question Set	Exámenes de certificación con scoring	scored	CRÍTICA
Course Presentation	Contenido de teleformación SEPE	completed	CRÍTICA
Interactive Video	Videoconferencias grabadas con checkpoints	interacted	ALTA
Fill in the Blanks	Ejercicios de memorización de normativa	answered	ALTA
Mark the Words	Identificación de conceptos clave legales	answered	MEDIA

6. Arquitectura Recomendada: Estrategia Híbrida

FILOSOFÍA SIN HUMO: Implementar lo que funciona con coste mínimo. Escalar con inteligencia cuando el volumen lo demande.

6.1 Fase 1: MVP (Meses 1-6) — Self-Hosted

Componente	Implementación
Módulo H5P	drupal/h5p_package (fork comunitario) vía Composer
Editor	H5P Editor integrado en Drupal (widget en campo H5P)
LRS/Tracking	jaraba_lms progress_record + h5p_analytics module
Video	YouTube Unlisted (coste €0) + H5P Interactive Video overlay
xAPI Config	Endpoint interno /api/v1/xapi/statements, captura verbos: played, completed, answered, scored, interacted
Content Types	Course Presentation, Interactive Video, Question Set, Branching Scenario, Drag and Drop, Essay
Multi-tenant	H5P field en entidad activity del Group (tenant). Librerías compartidas, contenido por grupo.
Coste	€0 en licencias. Solo esfuerzo de desarrollo e integración.

6.2 Fase 2: Escalado (Meses 6-12) — Híbrido

Componente	Evolución
Video Hosting	Migración a Bunny.net Stream cuando >1.000 reproducciones/mes
Smart Import	Evaluar H5P.com Premium ($1.200/año) para generación IA de contenido
Embed Strategy	Contenido generado en H5P.com, embebido vía embed code en Drupal
Analytics	Dashboard de video analytics en FOC + progress tracking avanzado
Certificación	Open Badge 3.0 automático al completar rutas H5P

6.3 Fase 3: Clase Mundial (Meses 12+) — Enterprise

Componente	Objetivo Final
H5P.com Enterprise	Autores ilimitados, SSO, LRS externo, Customer Success Manager
LTI 1.3 Nativo	Implementación jaraba_lti module para integración bidireccional completa
Smart Import + IA Jaraba	Smart Import H5P + Claude API para pipeline de contenido automatizado
White-Label	Contenido H5P brandeable por tenant/franquicia
SEPE Compliance	Tracking xAPI completo homologable para teleformación oficial
Live Engagement	Polls, competitions y surveys en tiempo real para sesiones sincrónicas

7. Comparativa Consolidada de Opciones

Criterio	A: Self-Hosted	B: H5P.com SaaS	C: Drupal LMS
Coste Inicial	€0	$690-1.200/año	€0
Compatibilidad D11	Alpha/Fork funcional	Vía LTI/Embed (independiente)	D10.3+ (verificar D11)
Control de Datos	Total (RGPD nativo)	Externo (GDPR compliant)	Total
Smart Import (IA)	No disponible	Sí (Premium/Enterprise)	No disponible
LRS Integrado	No (requiere custom)	Sí (vía LTI)	Sí (minimalista)
Multi-tenant	Vía Group Module	Subcuentas/orgs	Requiere evaluación
Mantenimiento	Comunidad Drupal	H5P Core Team	Tag1 Consulting
Filosofía Sin Humo	★★★★★	★★★	★★★★
Alineación Jaraba	MVP ideal	Escalado ideal	Alternativa si se rediseña LMS

8. Guía de Implementación Técnica MVP
8.1 Configuración xAPI para H5P
Configuración del endpoint xAPI interno para captura de statements H5P, alineada con el documento 08_Empleabilidad_LMS_Core_v1:

# /admin/config/h5p/settings
xapi_endpoint: /api/v1/xapi/statements
xapi_actor_type: mbox_sha1
xapi_context_registration: enrollment.uuid
xapi_context_extensions:
  tenant_id: {tenant.id}
  course_id: {course.id}
  lesson_id: {lesson.id}
  activity_id: {activity.id}
# Verbos capturados
verbs_to_track:
  - played, paused, seeked, completed
  - answered, interacted, passed, failed

8.2 Criterios de Completitud
Configuración estándar para marcar actividades H5P como completadas en el sistema de progreso:
•	Mínimo de reproducción de video: 90% del contenido visualizado
•	Questions: passing score mínimo de 70%
•	Branching Scenario: completar al menos una rama hasta el final
•	Course Presentation: avanzar por todas las slides
•	Interactive Video: responder checkpoints obligatorios

8.3 Integración Multi-Tenant
El campo H5P se añade a la entidad activity del Group Module, asegurando aislamiento por tenant:
•	Librerías H5P: compartidas globalmente (sites/default/files/h5p)
•	Contenido H5P: creado y asignado por grupo/tenant
•	Permisos: 'access h5p editor' limitado a roles admin y content_manager del tenant
•	xAPI context: incluye tenant_id en extensions para filtrado

9. Roadmap de Implementación

Sprint	Timeline	Entregables	Esfuerzo	Dependencias
Sprint 1	Semanas 1-2	Instalación h5p_package + h5p_editor. Campo H5P en activity entity. Descarga librerías core (6 tipos prioritarios).	40h	01_Core_Entidades
Sprint 2	Semanas 3-4	h5p_analytics configurado. xAPI endpoint interno. Integración progress_record. Tests de captura.	60h	08_LMS_Core, Sprint 1
Sprint 3	Semanas 5-6	H5P Interactive Video + YouTube Unlisted. Completion criteria configurado. Template Twig responsive.	40h	Sprint 2, CP_Video_LMS
Sprint 4	Semanas 7-8	Multi-tenant: permisos por grupo, contenido por tenant. QA completo. Documentación. Go-live.	50h	Sprint 3, 07_MultiTenant

Esfuerzo Total Estimado: 190 horas (4 sprints, 8 semanas) con 1 desarrollador senior. Coste en licencias: €0.

10. Conclusiones y Recomendaciones

DECISIÓN RECOMENDADA: Estrategia Híbrida Progresiva

1.	MVP con Self-Hosted (h5p_package): Coste €0, control total, alineado con arquitectura jaraba_lms existente. 50+ tipos de contenido. 8 semanas de implementación.
2.	Complementar con h5p_analytics: Bridge automático entre H5P xAPI statements y progress_record. Sin LRS externo necesario.
3.	Evaluar H5P.com Premium en Fase 2: Cuando se necesite Smart Import (IA) para escalar producción de contenido. ~$1.200/año. Embeber vía embed codes o LTI.
4.	Roadmap a Enterprise en Fase 3: Autores ilimitados, SSO, LRS externo, white-label. Cuando el ecosistema tenga 10+ tenants activos con contenido formativo.
5.	Monitorizar Drupal LMS module: Si evoluciona a release estable D11 con Tag1 mantenimiento, evaluar adopción de componentes específicos (certificates, messages) como complemento.


Este enfoque híbrido progresivo sigue la filosofía Sin Humo del ecosistema Jaraba: implementar lo práctico primero, escalar con datos reales, y nunca añadir complejidad innecesaria. La base self-hosted garantiza independencia y control desde el día uno, mientras que la vía SaaS queda disponible como acelerador cuando el volumen de contenido y el número de tenants justifiquen la inversión.
