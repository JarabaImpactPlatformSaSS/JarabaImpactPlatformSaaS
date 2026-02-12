
ECOSISTEMA JARABA
UX Journey Specifications
Navegación Inteligente por Avatar
IA Proactiva • Mínima Fricción • Máxima Conversión
Documento Preparado para EDI Google Antigravity
Versión 1.0 | Enero 2026
 
Índice de Contenidos
1. Resumen Ejecutivo y Principios de Diseño
2. Arquitectura de Navegación Inteligente
3. AgroConecta - 3 Avatares
   3.1 Productor Agrícola
   3.2 Comprador B2B
   3.3 Consumidor Final
4. ComercioConecta - 2 Avatares
   4.1 Comerciante Local
   4.2 Comprador Local
5. ServiciosConecta - 2 Avatares
   5.1 Profesional/Proveedor
   5.2 Cliente
6. Empleabilidad - 3 Avatares
   6.1 Job Seeker
   6.2 Employer
   6.3 Orientador/Mentor
7. Emprendimiento - 3 Avatares
   7.1 Emprendedor
   7.2 Mentor
   7.3 Gestor de Programa
8. Andalucía +ei - 3 Avatares
   8.1 Beneficiario
   8.2 Técnico STO
   8.3 Administrador Programa
9. Sistema de Certificación - 3 Avatares
   9.1 Estudiante
   9.2 Formador
   9.3 Administrador LMS
10. Matriz de Intervenciones IA
11. KPIs de Journey por Avatar
12. Implementación Técnica
 
1. Resumen Ejecutivo y Principios de Diseño
1.1 Objetivo del Documento
Este documento especifica los recorridos de navegación (UX Journeys) para cada avatar/rol del Ecosistema Jaraba SaaS. El objetivo es que EDI Google Antigravity implemente una lógica de navegación que:
•	Maximice retención: Usuarios permanecen en la plataforma más tiempo
•	Minimice fricción: Cada clic acerca al objetivo, sin pasos innecesarios
•	Sea proactiva: La IA anticipa necesidades antes de que el usuario las exprese
•	Contextualice ofertas: Cross-sell y upsell relevantes según estado del journey
•	Mida impacto: KPIs específicos por avatar y punto del journey
1.2 Principios de Navegación Inteligente
Principio 1: Zero-Click Intelligence
La IA debe inferir intención del usuario antes del primer clic. Ejemplos:
•	Si un Job Seeker entra lunes a las 9am, mostrar ofertas nuevas del fin de semana
•	Si un Productor tiene stock bajo de un producto popular, sugerir reposición
•	Si un Emprendedor no ha completado su plan de negocio en 7 días, ofrecer asistencia
Principio 2: Progressive Disclosure
Mostrar solo lo necesario para el siguiente paso. La complejidad se revela gradualmente según el usuario avanza y demuestra competencia.
Principio 3: Contextual Upsell
Las ofertas de productos/servicios adicionales aparecen solo cuando son relevantes al momento del journey. Nunca interrumpir, siempre complementar.
Principio 4: Friction Audit
Cada pantalla debe responder: ¿Cuántos clics hasta el objetivo? Si son más de 3, rediseñar.
Principio 5: Celebración de Progreso
Cada micro-logro se celebra visualmente. El usuario siempre sabe qué ha conseguido y qué le falta.
1.3 Inventario de Avatares (19 Total)
Vertical	Avatar	Objetivo Principal	Métrica Clave
AgroConecta	Productor	Vender productos	GMV mensual
	Comprador B2B	Aprovisionarse	Pedido recurrente
	Consumidor	Comprar local	Ticket medio
ComercioConecta	Comerciante	Digitalizar negocio	Ventas online
	Comprador	Descubrir local	Frecuencia compra
ServiciosConecta	Profesional	Conseguir clientes	Reservas mes
	Cliente	Resolver necesidad	NPS
Empleabilidad	Job Seeker	Encontrar empleo	Tasa inserción
	Employer	Contratar talento	Time-to-hire
	Orientador	Guiar candidatos	Candidatos activos
Emprendimiento	Emprendedor	Lanzar negocio	Hitos completados
	Mentor	Acompañar	Mentees activos
	Gestor	Coordinar programa	Tasa supervivencia
Andalucía +ei	Beneficiario	Obtener ayuda	Expediente aprobado
	Técnico STO	Validar solicitudes	Expedientes/día
	Admin	Gestionar programa	Ejecución presupuesto
Certificación	Estudiante	Aprender y certificar	Certificados emitidos
	Formador	Enseñar y evaluar	Tasa aprobados
	Admin LMS	Gestionar formación	Engagement rate
 
2. Arquitectura de Navegación Inteligente
2.1 Capas del Sistema de Navegación
El sistema de navegación inteligente opera en tres capas:
Capa 1: Context Engine
Motor que analiza en tiempo real:
•	Identidad: rol, permisos, tenant, historial
•	Temporal: hora del día, día de semana, estacionalidad
•	Comportamental: páginas visitadas, tiempo en pantalla, scroll depth
•	Transaccional: compras previas, carritos abandonados, suscripciones
•	Social: conexiones, menciones, actividad de red
Capa 2: Decision Engine (IA)
Motor de decisión que determina:
•	Qué contenido mostrar primero (priorización)
•	Qué acciones sugerir (recommendations)
•	Qué ofertas presentar (monetización)
•	Cuándo intervenir (timing)
•	Cómo comunicar (tono, formato)
Capa 3: Presentation Engine
Motor de presentación que adapta:
•	Layout según dispositivo y preferencias
•	Componentes según estado del journey
•	Microinteracciones según preset de estilo
•	CTAs según objetivo del momento
2.2 Estados del Journey
Cada avatar atraviesa estados definidos. El sistema debe conocer en qué estado está el usuario para adaptar la experiencia:
Estado	Descripción	Objetivo UX	Intervención IA
Discovery	Primera visita, explorando	Mostrar valor rápido	Onboarding guiado
Activation	Completando perfil, primeros pasos	Reducir fricción	Asistente contextual
Engagement	Uso regular, valor percibido	Profundizar uso	Sugerencias proactivas
Conversion	Momento de compra/acción clave	Facilitar transacción	Ofertas personalizadas
Retention	Usuario recurrente	Fidelizar, premiar	Programa de lealtad
Expansion	Listo para más valor	Upsell/cross-sell	Upgrades contextuales
Advocacy	Promotor activo	Facilitar referidos	Programa embajadores
At-Risk	Señales de abandono	Re-engagement	Win-back campaigns
2.3 Triggers de Intervención IA
La IA interviene basada en triggers específicos:
Triggers Temporales
•	Inactividad: 7 días sin login → email de reengagement
•	Aniversario: 1 año como usuario → oferta especial
•	Estacionalidad: inicio campaña agrícola → alertas de preparación
Triggers Comportamentales
•	Abandono: salir de checkout → recuperación de carrito
•	Hesitación: 30s en página de precios → chat proactivo
•	Patrón: 3 búsquedas sin resultado → sugerencia alternativa
Triggers Transaccionales
•	Post-compra: inmediato → confirmación + cross-sell
•	Reorden: ciclo típico cumplido → sugerencia de recompra
•	Upgrade: uso al 80% de límite → oferta de plan superior
Triggers Contextuales
•	Ubicación: cerca de proveedor → notificación de disponibilidad
•	Evento: feria sectorial → contenido relevante
•	Social: conexión activa → oportunidad de networking
 
3. AgroConecta - 3 Avatares
3.1 Avatar: Productor Agrícola
Perfil
Quién es	Agricultor, ganadero, bodeguero, artesano alimentario
Objetivo principal	Vender sus productos a precio justo, sin intermediarios
Pain points	Falta de tiempo, baja digitalización, desconocimiento de marketing
Motivación	Visibilidad, ingresos directos, reconocimiento de calidad
Dispositivo	80% móvil (en campo), 20% desktop (gestión)
Journey Map: Productor
Estado: Discovery → Primera visita
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Landing desde anuncio/referido	Hero con propuesta de valor clara	Detectar origen → personalizar mensaje
2	Click en Empezar a Vender	Formulario mínimo: nombre, producto, foto	Autocompletado con OCR de foto
3	Sube primera foto de producto	Preview de ficha generada	Producer Copilot: genera descripción, sugiere precio
4	Revisa y confirma	Producto publicado + celebración	Sugiere añadir más productos
5	Primer pedido recibido	Notificación con detalles	Guía de preparación de envío
KPI Target: Time to First Product Published < 5 minutos
Puntos de Intervención IA - Productor
Momento	Trigger	Acción IA	Objetivo
Onboarding	Foto subida	Generar ficha completa automática	Reducir fricción
Stock bajo	Inventario < 20%	Alerta + sugerencia de reposición	Evitar rotura
Precio	Precio muy bajo/alto vs mercado	Recomendación de ajuste	Optimizar ventas
Inactividad	7 días sin actualizar	Recordatorio + tips de engagement	Reactivar
Oportunidad	Demanda alta de producto similar	Sugerir nuevo producto	Expandir catálogo
Calidad	Review negativa	Guía de mejora + respuesta sugerida	Gestión reputación
Upsell	10 ventas completadas	Ofrecer certificación premium	Monetización
Cross-sell Contextual - Productor
•	Tras publicar primer producto → Kit Digital de fotografía de producto
•	Tras primera venta → Curso de packaging y envío
•	Al alcanzar 10 productos → Plan Profesional con analytics avanzado
•	Al recibir primera review → Certificación de calidad DOP/IGP
 
3.2 Avatar: Comprador B2B
Perfil
Quién es	Chef, responsable de compras de hotel/restaurante, tienda gourmet
Objetivo principal	Aprovisionamiento fiable de productos de calidad
Pain points	Inconsistencia de proveedores, logística compleja, falta de trazabilidad
Motivación	Diferenciación, narrativa de producto local, costes predecibles
Dispositivo	50% móvil (pedidos rápidos), 50% desktop (gestión)
Journey Map: Comprador B2B
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Busca ingrediente específico	Resultados con filtros B2B (volumen, precio/kg)	Ordenar por match con historial
2	Compara proveedores	Vista comparativa lado a lado	Destacar diferenciadores clave
3	Solicita muestra	Formulario simplificado	Autocompletar con datos empresa
4	Recibe y aprueba muestra	Facilitar primer pedido mayorista	Sugerir cantidad óptima según temporada
5	Configura pedido recurrente	Automatización de reorden	Ajustar según patrones de consumo
KPI Target: Time to First Order < 48h desde registro
Puntos de Intervención IA - Comprador B2B
Momento	Trigger	Acción IA	Objetivo
Búsqueda	Query con intención B2B	Activar vista profesional automática	Experiencia relevante
Reorden	Ciclo típico de compra cumplido	Notificación con pedido pre-llenado	Facilitar recurrencia
Alternativa	Producto favorito sin stock	Sugerir alternativa equivalente	Evitar pérdida
Temporada	Inicio de temporada de producto	Alerta de disponibilidad + reserva	Anticipar demanda
Precio	Bajada de precio en favoritos	Notificación de oportunidad	Incrementar pedido
 
3.3 Avatar: Consumidor Final
Perfil
Quién es	Particular interesado en productos locales, ecológicos, de calidad
Objetivo principal	Acceder a productos auténticos directamente del productor
Pain points	Desconoce productores, desconfianza online, complejidad de compra
Motivación	Salud, sostenibilidad, apoyo al comercio local
Dispositivo	70% móvil, 30% desktop
Journey Map: Consumidor Final
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Descubre via RRSS/búsqueda	Landing con historias de productores	Personalizar según origen
2	Explora categorías	Navegación visual con filtros simples	Destacar productos cercanos y populares
3	Lee historia del productor	Perfil con fotos, certificaciones, reviews	Generar confianza con trazabilidad
4	Añade al carrito	Sugerencias complementarias	Recomendar maridajes, cestas
5	Checkout	Proceso simplificado, múltiples pagos	Guardar preferencias para futuro
6	Recibe pedido	Tracking + contenido sobre producto	Enviar recetas, conservación
KPI Target: Conversion Rate > 3%, AOV > 45€
 
4. ComercioConecta - 2 Avatares
4.1 Avatar: Comerciante Local
Perfil
Quién es	Propietario de tienda de barrio, comercio familiar, autónomo retail
Objetivo principal	Digitalizar su negocio sin perder esencia local
Pain points	Miedo a la tecnología, poco tiempo, competencia de grandes plataformas
Motivación	Supervivencia del negocio, alcanzar clientes jóvenes, optimizar operaciones
Dispositivo	60% móvil (mostrador), 40% desktop (gestión)
Journey Map: Comerciante Local
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Registra negocio	Wizard de 3 pasos con Google My Business	Importar datos automáticamente
2	Configura tienda online	Plantillas por sector pre-configuradas	Sugerir preset visual según sector
3	Sube productos (fotos móvil)	Procesamiento batch de imágenes	Generar fichas automáticas con IA
4	Conecta TPV/POS (opcional)	Integración con sistemas populares	Sincronización automática de stock
5	Publica primera oferta flash	Editor visual simple	Sugerir horario óptimo de publicación
6	Genera QR dinámico	QR imprimible para escaparate	Tracking de escaneos en tiempo real
KPI Target: First Sale Online < 7 días, Store Setup < 30 min
Puntos de Intervención IA - Comerciante
Momento	Trigger	Acción IA	Objetivo
Onboarding	Primer acceso	Tour guiado personalizado por sector	Reducir abandono
Catálogo vacío	0 productos tras 24h	Asistente de carga masiva	Activar tienda
Oferta flash	Stock alto + baja rotación	Sugerir promoción específica	Mover inventario
SEO Local	Bajo tráfico de búsqueda	Auditoría SEO + acciones sugeridas	Aumentar visibilidad
Reseñas	Venta completada	Solicitud automática de review	Social proof
Upsell	Uso básico estable >30 días	Ofrecer plan con más funciones	Monetización
Cross-sell Contextual - Comerciante
•	Tras activar tienda → Servicio de fotografía profesional
•	Tras primera venta → Curso de marketing digital para comercio
•	Al alcanzar 50 productos → Plan con analytics avanzado
•	Tras 10 ofertas flash → Sistema de fidelización de clientes
 
4.2 Avatar: Comprador Local
Perfil
Quién es	Vecino del barrio, consumidor que valora comercio de proximidad
Objetivo principal	Descubrir y comprar en comercios locales con conveniencia digital
Pain points	Desconoce oferta local, horarios limitados, falta de información online
Motivación	Apoyar comercio local, conveniencia, descubrir ofertas
Dispositivo	85% móvil, 15% desktop
Journey Map: Comprador Local
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Escanea QR en escaparate	Landing de tienda con ofertas activas	Mostrar ofertas tiempo-limitado primero
2	Explora catálogo desde casa	Filtros por categoría, precio, disponibilidad	Ordenar por relevancia personal
3	Reserva producto para recoger	Confirmación + hora de recogida	Recordatorio antes de cierre
4	Recoge en tienda	Check-in digital + puntos fidelidad	Sugerir productos relacionados en tienda
5	Deja reseña	Formulario simple post-visita	Incentivar con puntos/descuento
KPI Target: Click-to-Reserve < 60 segundos, Return Rate > 40%
 
5. ServiciosConecta - 2 Avatares
5.1 Avatar: Profesional/Proveedor de Servicios
Perfil
Quién es	Abogado, psicólogo, consultor, diseñador, coach, terapeuta
Objetivo principal	Conseguir clientes de forma predecible y gestionar agenda eficientemente
Pain points	Captación inconsistente, gestión administrativa, cobros, no-shows
Motivación	Llenar agenda, automatizar admin, construir reputación online
Dispositivo	60% desktop (consultas), 40% móvil (gestión rápida)
Journey Map: Profesional
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Crea perfil profesional	Wizard con importación de LinkedIn	Autocompletar con datos públicos
2	Define servicios y tarifas	Templates por especialidad	Sugerir precios según mercado
3	Configura disponibilidad	Calendario con sync Google/Outlook	Optimizar slots según demanda
4	Primera reserva entrante	Notificación + ficha cliente	Preparar contexto de la consulta
5	Realiza sesión (presencial/video)	Herramientas de videollamada integradas	Notas automáticas, transcripción
6	Factura y cobra	Facturación automática	Recordatorios de pago inteligentes
KPI Target: First Booking < 14 días, Booking Rate > 70%
Puntos de Intervención IA - Profesional
Momento	Trigger	Acción IA	Objetivo
Perfil incompleto	<50% completado	Guía paso a paso con ejemplos	Mejorar conversión
Agenda vacía	0 reservas próxima semana	Sugerir promoción de lanzamiento	Generar demanda
No-show risk	Cliente con historial de cancelaciones	Recordatorio extra + confirmación	Reducir no-shows
Post-sesión	Sesión completada	Generar resumen + próximos pasos	Retención cliente
Review	72h post-servicio	Solicitar valoración	Social proof
Upsell	Cliente recurrente (>3 sesiones)	Ofrecer paquete de sesiones	Incrementar LTV
 
5.2 Avatar: Cliente de Servicios
Perfil
Quién es	Particular o empresa que necesita un servicio profesional
Objetivo principal	Encontrar profesional adecuado y resolver su necesidad
Pain points	Dificultad para comparar, desconfianza, procesos complejos de contratación
Motivación	Solución rápida, profesional verificado, precio claro
Dispositivo	75% móvil, 25% desktop
Journey Map: Cliente
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Describe necesidad	Formulario conversacional inteligente	Triaje automático de caso
2	Recibe matches de profesionales	Lista rankeada por fit	Explicar por qué cada match
3	Compara perfiles	Vista comparativa con reviews	Destacar diferenciadores
4	Reserva cita	Calendario en tiempo real	Sugerir horario óptimo
5	Recibe servicio	Recordatorios, preparación	Checklist pre-cita automático
6	Evalúa y recomienda	Review + programa referidos	Incentivar compartir experiencia
KPI Target: Time to First Booking < 10 min, NPS > 50
 
6. Empleabilidad - 3 Avatares
6.1 Avatar: Job Seeker
Perfil
Quién es	Persona en búsqueda activa de empleo, desempleado o en mejora
Objetivo principal	Encontrar empleo adecuado a su perfil y expectativas
Pain points	CV desactualizado, falta de visibilidad, no saber qué mejorar
Motivación	Estabilidad económica, desarrollo profesional, propósito
Dispositivo	65% móvil, 35% desktop
Journey Map: Job Seeker
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Crea perfil / sube CV	Parser de CV automático	Extraer skills, experiencia, sugerir mejoras
2	Completa evaluación de skills	Tests adaptativos por área	Identificar gaps y fortalezas
3	Recibe recomendaciones de ofertas	Feed personalizado de ofertas	Matching Score visible
4	Aplica a ofertas	One-click apply con perfil	Personalizar carta automáticamente
5	Realiza formación recomendada	Cursos integrados en plataforma	Learning path según objetivo
6	Prepara entrevista	Simulador de entrevista IA	Feedback personalizado
7	Consigue empleo	Celebración + encuesta de cierre	Solicitar testimonial
KPI Target: Tasa Inserción > 40%, Time to Employment < 90 días
Puntos de Intervención IA - Job Seeker
Momento	Trigger	Acción IA	Objetivo
CV débil	Score CV < 60%	Sugerencias específicas de mejora	Aumentar empleabilidad
Skill gap	Oferta interesante pero falta skill	Recomendar curso específico	Cerrar brecha
Inactividad	7 días sin aplicar	Nuevas ofertas + motivación	Reactivar
Match alto	Oferta con >85% match	Alerta prioritaria + ayuda aplicación	No perder oportunidad
Entrevista	Entrevista agendada	Info empresa + simulador	Preparar candidato
Rechazo	Aplicación rechazada	Feedback constructivo + alternativas	Mantener motivación
Cross-sell Contextual - Job Seeker
•	Tras crear perfil → Curso gratuito de LinkedIn optimization
•	Tras primer rechazo → Sesión con orientador laboral
•	Skill gap detectado → Certificación específica (pagada)
•	Entrevista agendada → Pack de preparación premium
 
6.2 Avatar: Employer
Perfil
Quién es	RRHH, hiring manager, CEO de PYME con necesidades de contratación
Objetivo principal	Contratar talento adecuado de forma rápida y eficiente
Pain points	Exceso de CVs irrelevantes, proceso largo, alta rotación
Motivación	Reducir time-to-hire, mejorar calidad de contratación
Dispositivo	70% desktop, 30% móvil
Journey Map: Employer
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Publica oferta de empleo	Editor con templates por sector	Optimizar redacción para atracción
2	Recibe candidaturas	Dashboard con filtros y scoring	Ranking automático por fit
3	Revisa candidatos preseleccionados	Perfiles enriquecidos con insights	Destacar fortalezas/riesgos
4	Agenda entrevistas	Calendario integrado con candidatos	Optimizar slots disponibles
5	Realiza entrevistas	Guía de entrevista por competencias	Scorecard automático
6	Hace oferta y contrata	Workflow de oferta integrado	Sugerir salario competitivo
KPI Target: Time to Hire < 30 días, Quality of Hire Score > 80%
6.3 Avatar: Orientador/Mentor
Perfil
Quién es	Técnico de empleo, orientador laboral, career coach
Objetivo principal	Guiar a candidatos hacia empleabilidad y colocación
Pain points	Carga de trabajo alta, seguimiento manual, medir impacto
Motivación	Impacto social, eficiencia en gestión, resultados medibles
Dispositivo	80% desktop, 20% móvil
Puntos de Intervención IA - Orientador
Momento	Trigger	Acción IA	Objetivo
Nuevo candidato	Asignación de caso	Resumen ejecutivo automático	Preparar primera sesión
Candidato estancado	>30 días sin progreso	Alerta + sugerencias de intervención	Reactivar caso
Oportunidad	Oferta ideal para candidato	Notificación proactiva	Match caliente
Reporte	Fin de semana/mes	Generar informe automático	Facilitar admin
 
7. Emprendimiento - 3 Avatares
7.1 Avatar: Emprendedor
Perfil
Quién es	Persona con idea de negocio o negocio en fase inicial
Objetivo principal	Validar idea, lanzar negocio, conseguir financiación/clientes
Pain points	Aislamiento, falta de conocimientos, acceso a recursos limitado
Motivación	Independencia, impacto, crecimiento personal/económico
Dispositivo	55% móvil, 45% desktop
Journey Map: Emprendedor
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Registra idea de negocio	Formulario estructurado	Análisis inicial de viabilidad
2	Completa diagnóstico de madurez	Evaluación 360º del proyecto	Identificar fortalezas y gaps
3	Recibe plan de acción personalizado	Roadmap con hitos	Priorizar acciones por impacto
4	Trabaja en Business Model Canvas	Editor interactivo guiado	Sugerir mejoras por sección
5	Valida MVP	Herramientas de testing	Analizar feedback usuarios
6	Conecta con mentor	Matching con mentores	Sugerir mentor según necesidad
7	Solicita financiación	Catálogo de convocatorias	Match con ayudas elegibles
KPI Target: Hitos Completados > 70%, Supervivencia 1 año > 80%
Puntos de Intervención IA - Emprendedor
Momento	Trigger	Acción IA	Objetivo
Onboarding	Primer acceso	Tour personalizado + quick wins	Engagement inicial
Bloqueo	Hito >14 días sin progreso	Recursos específicos + oferta mentor	Desbloquear
Validación	BMC completado	Análisis de modelo + sugerencias	Mejorar viabilidad
Financiación	Nueva convocatoria compatible	Alerta + checklist de requisitos	Captar oportunidad
Networking	Evento relevante próximo	Invitación + preparación	Expandir red
Celebración	Hito completado	Reconocimiento + siguiente paso	Motivación
Cross-sell Contextual - Emprendedor
•	Tras diagnóstico → Curso de modelo de negocio
•	Antes de MVP → Kit de validación con herramientas
•	Buscando financiación → Servicio de preparación de pitch
•	Post-lanzamiento → Membresía de comunidad emprendedora
 
7.2 Avatar: Mentor
Perfil
Quién es	Empresario experimentado, consultor, experto sectorial voluntario
Objetivo principal	Devolver a la comunidad, compartir experiencia, networking
Pain points	Tiempo limitado, preparación de sesiones, seguimiento
Motivación	Impacto, reconocimiento, mantenerse activo
Dispositivo	70% desktop, 30% móvil
Puntos de Intervención IA - Mentor
•	Nuevo mentee asignado → Resumen ejecutivo del proyecto + historial
•	Pre-sesión → Agenda sugerida basada en últimos avances del mentee
•	Post-sesión → Generación automática de notas y próximos pasos
•	Mentee estancado → Alerta con sugerencias de intervención
7.3 Avatar: Gestor de Programa
Perfil
Quién es	Director de incubadora, coordinador de programa público, responsable de aceleradora
Objetivo principal	Maximizar impacto del programa, reportar resultados, gestionar recursos
Pain points	Reporting manual, seguimiento de cohortes, demostrar ROI
Motivación	Resultados medibles, renovación de financiación, reconocimiento
Puntos de Intervención IA - Gestor
•	Inicio de cohorte → Dashboard de seguimiento pre-configurado
•	KPI en riesgo → Alerta temprana con acciones sugeridas
•	Fin de periodo → Generación automática de informe de impacto
•	Convocatoria próxima → Checklist de preparación de solicitud
 
8. Andalucía +ei - 3 Avatares
8.1 Avatar: Beneficiario
Perfil
Quién es	Emprendedor andaluz que solicita ayudas del programa +ei
Objetivo principal	Obtener financiación y apoyo para su proyecto emprendedor
Pain points	Burocracia, documentación compleja, plazos ajustados
Motivación	Financiación, legitimidad, acceso a red de apoyo
Journey Map: Beneficiario Andalucía +ei
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Verifica elegibilidad	Checklist interactivo de requisitos	Pre-validar criterios automáticamente
2	Completa solicitud	Formulario guiado paso a paso	Validar campos en tiempo real
3	Adjunta documentación	Upload con checklist visual	Verificar completitud docs
4	Envía y espera resolución	Tracking de estado en tiempo real	Notificar cambios de estado
5	Subsana requerimientos (si aplica)	Formulario de subsanación guiado	Explicar qué falta exactamente
6	Recibe resolución	Notificación + próximos pasos	Guía de siguiente fase
KPI Target: Solicitudes completas > 85%, Subsanaciones < 20%
8.2 Avatar: Técnico STO
Perfil
Quién es	Funcionario o técnico de entidad colaboradora que tramita solicitudes
Objetivo principal	Procesar expedientes eficientemente cumpliendo normativa
Pain points	Volumen alto, documentación incompleta, plazos legales
Motivación	Cumplimiento, eficiencia, reducir errores
Puntos de Intervención IA - Técnico STO
•	Nueva solicitud → Pre-validación automática de documentación
•	Documentación incompleta → Generación de requerimiento de subsanación
•	Plazo próximo a vencer → Alerta de priorización
•	Fin de jornada → Resumen de expedientes pendientes
•	Auditoría → Generación de informe de trazabilidad
8.3 Avatar: Administrador de Programa
Perfil
Quién es	Responsable de la Junta de Andalucía que supervisa el programa
Objetivo principal	Gestionar programa, ejecutar presupuesto, reportar impacto
Pain points	Visibilidad limitada, reporting manual, coordinación multi-STO
Puntos de Intervención IA - Administrador
•	Dashboard diario → KPIs de ejecución presupuestaria y expedientes
•	Desviación detectada → Alerta temprana con causa probable
•	Informe periódico → Generación automática con datos actualizados
•	Comparativa STOs → Benchmarking de rendimiento entre entidades
 
9. Sistema de Certificación - 3 Avatares
9.1 Avatar: Estudiante
Perfil
Quién es	Profesional o particular que busca formación y certificación
Objetivo principal	Adquirir conocimientos y credenciales verificables
Pain points	Tiempo limitado, contenido no relevante, certificados sin valor
Motivación	Mejora profesional, empleabilidad, reconocimiento
Journey Map: Estudiante
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Explora catálogo de cursos	Catálogo filtrable con previews	Recomendar según perfil y objetivos
2	Se matricula en curso	Checkout + onboarding del curso	Personalizar learning path
3	Consume contenido	Player con tracking de progreso	Adaptar ritmo según engagement
4	Completa ejercicios/quizzes	Feedback inmediato	Identificar áreas de refuerzo
5	Realiza examen de certificación	Examen proctored o supervisado	Generar preguntas adaptativas
6	Obtiene credencial	Badge Open Badges 3.0 verificable	Sugerir siguiente certificación
KPI Target: Completion Rate > 70%, Certification Rate > 60%
Puntos de Intervención IA - Estudiante
Momento	Trigger	Acción IA	Objetivo
Onboarding	Primera matrícula	Evaluar nivel previo y adaptar	Personalizar experiencia
Abandono	3 días sin acceder	Recordatorio + motivación	Retener
Dificultad	Quiz fallido 2 veces	Recursos adicionales + tutoría	Apoyar aprendizaje
Progreso	Módulo completado	Celebración + preview siguiente	Motivar continuidad
Pre-examen	Contenido 100% completado	Simulador de examen	Preparar para certificación
Post-certificación	Badge emitido	Sugerir learning path avanzado	Upsell formación
9.2 Avatar: Formador
Perfil
Quién es	Instructor, profesor, experto que crea y/o imparte formación
Objetivo principal	Crear contenido efectivo y guiar estudiantes al éxito
Pain points	Creación de contenido consume tiempo, evaluación manual
Puntos de Intervención IA - Formador
•	Creación de curso → Asistente de estructuración de contenido
•	Evaluaciones → Generación automática de preguntas
•	Estudiantes con dificultad → Alerta + sugerencias de intervención
•	Fin de cohorte → Informe de resultados y mejoras sugeridas
9.3 Avatar: Administrador LMS
Perfil
Quién es	Responsable de formación de organización, gestor de plataforma
Objetivo principal	Gestionar catálogo, usuarios, reportar compliance
Pain points	Administración manual, reporting complejo, integración sistemas
Puntos de Intervención IA - Admin LMS
•	Nuevos usuarios masivos → Importación inteligente con validación
•	Compliance pendiente → Alerta de formaciones obligatorias
•	Baja participación → Sugerencias de gamificación
•	Auditoría → Generación automática de informes xAPI
 
10. Matriz de Intervenciones IA
10.1 Tipos de Intervención
Tipo	Descripción	Ejemplo
Proactiva	IA inicia sin acción del usuario	Sugerir reposición de stock
Reactiva	IA responde a acción del usuario	Autocompletar formulario
Preventiva	IA anticipa problema	Alerta de no-show probable
Correctiva	IA corrige después de error	Sugerir mejora tras rechazo
Educativa	IA enseña mientras asiste	Explicar por qué una acción
Celebratoria	IA reconoce logros	Badge al completar hito
10.2 Canales de Intervención
•	In-app notifications: Para acciones inmediatas dentro de la plataforma
•	Email: Para seguimiento asíncrono, resúmenes, reactivación
•	Push móvil: Para alertas urgentes y tiempo-sensibles
•	WhatsApp/SMS: Para confirmaciones críticas (reservas, entregas)
•	Chat proactivo: Para asistencia en momentos de hesitación
10.3 Reglas de No-Intrusión
La IA debe respetar límites para no saturar al usuario:
•	Máximo 3 notificaciones push por día
•	Máximo 2 emails no transaccionales por semana
•	Chat proactivo solo tras 30s de inactividad en página crítica
•	Respeto de horarios: no notificar entre 22:00 y 08:00
•	Frecuencia decreciente si no hay interacción
 
11. KPIs de Journey por Avatar
11.1 Métricas Universales
KPI	Definición	Target
Time to Value	Tiempo hasta primera acción de valor	< 5 minutos
Activation Rate	% que completa onboarding	> 70%
Engagement Score	Índice de uso activo semanal	> 60%
Task Completion	% de tareas iniciadas que se completan	> 80%
Feature Adoption	% de features usadas por usuario	> 50%
Churn Risk Score	Probabilidad de abandono (ML)	< 20%
NPS	Net Promoter Score	> 50
11.2 Métricas por Vertical
Vertical	KPI Principal	KPI Secundario	Target
AgroConecta	GMV por productor	Pedidos recurrentes	€2,000/mes, 40% recurrente
ComercioConecta	Ventas online	Tráfico local	+30% MoM, 500 visitas/mes
ServiciosConecta	Reservas/mes	Booking rate	20 reservas, >70% rate
Empleabilidad	Tasa inserción	Time to employment	>40%, <90 días
Emprendimiento	Supervivencia 1 año	Hitos completados	>80%, >70%
Andalucía +ei	Expedientes resueltos	Tiempo resolución	>95%, <30 días
Certificación	Completion rate	Certification rate	>70%, >60%
 
12. Implementación Técnica
12.1 Arquitectura de Navegación
// Estructura de estado del journey por usuario
{
  "user_id": "uuid",
  "avatar_type": "productor|comprador_b2b|...",
  "journey_state": "discovery|activation|engagement|...",
  "current_step": 3,
  "completed_steps": [1, 2],
  "context": {
    "last_action": "product_uploaded",
    "time_in_state": 172800,
    "interactions_today": 5,
    "risk_score": 0.15
  },
  "triggers_pending": ["stock_low_alert", "upsell_certification"]
}
12.2 API de Journey Engine
Endpoint	Descripción
GET /journey/{user_id}/state	Obtiene estado actual del journey
POST /journey/{user_id}/event	Registra evento y actualiza estado
GET /journey/{user_id}/next-actions	Obtiene acciones sugeridas por IA
POST /journey/{user_id}/trigger	Dispara intervención específica
GET /journey/{user_id}/kpis	Métricas del journey del usuario
GET /journey/cohort/{avatar}/analytics	Analytics agregados por avatar
12.3 Componentes UI por Estado
Estado	Componentes Primarios	Microinteracciones
Discovery	Hero, ValueProps, Testimonials	Scroll reveal, hover highlights
Activation	Wizard, ProgressBar, Checklist	Step transitions, celebrations
Engagement	Dashboard, Feed, Notifications	Real-time updates, badges
Conversion	Checkout, Pricing, CTA buttons	Urgency counters, trust signals
Retention	Loyalty, Referrals, Achievements	Points animation, level up
At-Risk	Win-back modal, Support chat	Gentle nudges, exit intent
12.4 Roadmap de Implementación
Sprint	Entregables	Avatares	Horas
1-2	Journey Engine core, estado tracking	Todos (infra)	80-100h
3-4	AgroConecta journeys (3 avatares)	Productor, B2B, Consumidor	60-80h
5-6	ComercioConecta + ServiciosConecta	4 avatares	60-80h
7-8	Empleabilidad journeys	Job Seeker, Employer, Orientador	60-80h
9-10	Emprendimiento journeys	3 avatares	60-80h
11-12	Andalucía +ei + Certificación	6 avatares	80-100h
13-14	IA proactiva, testing, optimización	Todos	60-80h
Total estimado: 460-600 horas (14 sprints, ~7 meses)

— Fin del Documento —
Ecosistema Jaraba | UX Journey Specifications por Avatar v1.0
