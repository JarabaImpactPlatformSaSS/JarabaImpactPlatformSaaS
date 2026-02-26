
ECOSISTEMA JARABA
Especificación de Implementación para Claude Code
Remediación Integral del Meta-Sitio pepejaraba.com + jarabaimpact.com

Campo	Valor
Código	178_MetaSite_Remediation_ClaudeCode_v1
Versión	1.0
Fecha	Febrero 2026
Estado	Especificación Técnica para Implementación Inmediata
Entorno destino	WordPress (pepejaraba.com) + WordPress (jarabaimpact.com)
Entorno futuro	Drupal 11 SaaS (jaraba-saas.lndo.site → pepejaraba.com)
Dependencias	05_Core_Theming, 123_Personal_Brand, 124_Content_Ready, 128_JarabaImpact, 146_SendGrid_ECA
Prioridad	BLOCKER – Prerrequisito para go-to-market
 
1. Contexto y Alcance
Este documento contiene las especificaciones técnicas completas y listas para ejecución por Claude Code de TODAS las correcciones, mejoras e implementaciones nuevas necesarias para llevar los meta-sitios pepejaraba.com y jarabaimpact.com de su estado actual a un estado «listo para convertir».
El análisis se basa en la auditoría realizada sobre los sitios de producción (WordPress/Astra) que alimentan el desarrollo local en Lando. Las especificaciones cubren AMBOS entornos: correcciones inmediatas en WordPress de producción y la implementación definitiva en el SaaS Drupal 11.
1.1 Inventario de problemas por severidad
ID	Severidad	Problema	Sección
BUG-001	CRITICAL	CTA Kit Impulso Digital apunta a # (href vacío)	§2
BUG-002	CRITICAL	Página Casos de Éxito vacía («No post found!»)	§3
BUG-003	CRITICAL	3 blog posts del homepage son ficticios (imgs stock Astra)	§4
BUG-004	HIGH	Formulario de contacto no renderizado	§5
BUG-005	HIGH	No existe página /servicios con Value Ladder	§6
BUG-006	HIGH	No hay secuencia de email post-descarga Kit	§7
BUG-007	HIGH	Testimonios reales solo en jarabaimpact, no en pepejaraba	§8
BUG-008	MEDIUM	Headline del Hero infrautiliza credencial +100M€	§9
BUG-009	MEDIUM	Botón «Acceder al Ecosistema» sin contexto ni onboarding	§10
BUG-010	MEDIUM	Schema JSON-LD no implementado en <head>	§11
BUG-011	MEDIUM	Meta tags y OG tags ausentes o genéricos	§11
BUG-012	MEDIUM	Cross-pollination entre dominios insuficiente	§12
BUG-013	LOW	Kit PDF sin capturas de pantalla de herramientas	§13
BUG-014	LOW	Falta botón WhatsApp sticky en mobile	§14
 
2. BUG-001: CTA Kit Impulso Digital Roto [CRITICAL]
2.1 Diagnóstico
En pepejaraba.com (producción WordPress), el botón Hero «Descarga tu Kit de Impulso Gratuito →» tiene href="#". El PDF del Kit existe (11 páginas, alta calidad) pero no hay mecanismo de entrega ni captura de email.
2.2 Solución WordPress (inmediata)
Opción A: Con plugin de formularios existente
•	Crear formulario en WPForms/Contact Form 7/Elementor Forms con campos: Nombre, Email, Pregunta de segmentación (¿Qué te describe mejor?: Busco empleo / Quiero emprender / Tengo un negocio)
•	Configurar acción post-submit: enviar email con link de descarga al PDF alojado en /wp-content/uploads/kit-impulso-digital-pepejaraba.pdf
•	Crear página /kit-impulso-digital/ con el formulario embebido
•	Actualizar href del botón Hero: href="/kit-impulso-digital/"
•	Crear página de agradecimiento /kit-impulso-gracias/ con: link de descarga directa + CTA secundario «Agenda tu diagnóstico gratuito» + compartir por WhatsApp
Opción B: Modal popup (mejor UX, menos fricción)
•	Añadir popup/modal que se activa al clic del CTA Hero
•	Formulario inline dentro del modal: Email + Select de avatar
•	Submit → descarga automática del PDF + redirect a /kit-impulso-gracias/
•	GDPR: checkbox de consentimiento explícito obligatorio
2.3 Solución Drupal 11 SaaS (definitiva)
Implementar mediante jaraba_core Webform entity:
# Webform: kit_impulso_digital
# Route: /kit-impulso-digital
# Fields:
#   - email (required, email validation)
#   - nombre (required, text)
#   - avatar_type (required, select: job_seeker|entrepreneur|business_owner)
#   - gdpr_consent (required, checkbox)
# Actions:
#   1. Create jaraba_lead entity with avatar_type tag
#   2. Trigger ECA: jaraba_email:enroll_sequence('kit_impulso_onboarding')
#   3. Send transactional email via SendGrid with PDF attachment
#   4. Redirect to /kit-impulso-gracias with ?avatar={avatar_type}
#   5. Webhook: ActiveCampaign contact create + tag 'kit_downloaded'
2.4 Página de agradecimiento /kit-impulso-gracias/
Contenido dinámico según avatar_type del query parameter:
Avatar	Mensaje personalizado	CTA siguiente paso
job_seeker	¡Genial! Tu Kit está en tu email. Empieza por la herramienta #3 (LinkedIn) – es la que más impacto tiene para encontrar empleo.	Agenda tu diagnóstico gratuito de empleabilidad →
entrepreneur	¡Tu Kit está listo! Te recomiendo empezar por la herramienta #4 (Notion) para organizar tu plan de negocio.	Descubre cómo validar tu idea en 7 días →
business_owner	¡Descarga completada! Para tu negocio, empieza por la herramienta #2 (Google My Business) – resultados en 48h.	Haz tu diagnóstico digital express gratuito →
2.5 Criterios de aceptación
•	El botón Hero NO apunta a # sino a formulario funcional
•	Al enviar formulario, el usuario recibe email con PDF en <30 segundos
•	El email capturado se almacena con campo avatar_type
•	La página de gracias muestra contenido personalizado por avatar
•	Botón de compartir por WhatsApp funcional con mensaje pre-formateado
•	GDPR compliant: checkbox explícito, link a política de privacidad
 
3. BUG-002: Página Casos de Éxito Vacía [CRITICAL]
3.1 Diagnóstico
pepejaraba.com/casos-de-exito/ muestra «No post found!». Sin embargo, jarabaimpact.com tiene 3 casos de éxito reales publicados con foto, video y citas textuales: Marcela Calabia, Ángel Martínez (Camino Viejo) y Luis Miguel Criado.
3.2 Contenido a migrar desde jarabaimpact.com
Caso	Programa	Titular	Cita clave	URL origen
Marcela Calabia	Andalucía +ei	De la Incertidumbre a Crear mi Propio Camino como Autónoma	No sabía por dónde empezar. Este curso me dio no solo las herramientas...	jarabaimpact.com/2025/09/29/caso-de-exito-como-marcela...
Ángel Martínez	Andalucía +ei	Del Estrés Corporativo al Éxito Rural: Camino Viejo	La formación Jaraba Impact de PED es oro puro...	jarabaimpact.com/2025/10/02/caso-de-exito-del-estres...
Luis Miguel Criado	Andalucía +ei	De la Parálisis Administrativa a la Acción	El programa me dio las herramientas para dar los pasos más difíciles...	En proceso de publicación
3.3 Implementación WordPress (inmediata)
•	Crear 3 posts de tipo «post» (o CPT «caso-de-exito» si existe) con categoría «Casos de éxito»
•	Cada post debe incluir: foto real del protagonista (descargar de jarabaimpact.com), cita destacada como blockquote, video embed si existe, estructura Antes → Proceso → Resultado, tags de vertical (Emprendimiento, Empleabilidad, Pymes)
•	Asignar tags según los filtros existentes en la página: Pymes, Emprendimiento, Empleabilidad
•	Los 3 casos son de Emprendimiento (programa Andalucía +ei). NECESARIO: añadir al menos 1 caso de Empleabilidad y 1 de Pymes (aunque sea más breve) para que los filtros no queden vacíos
3.4 Estructura de cada caso de éxito (plantilla)
Cada post de caso de éxito debe seguir esta estructura:
•	Encabezado: Programa origen (ej: «Programa de Emprendimiento Inclusivo Andalucía +ei») + foto del protagonista
•	Cita hero: La frase más impactante del testimonio como blockquote grande
•	Contexto / Antes: Situación del protagonista antes de trabajar con Pepe (¿cuál era su problema/frustración?)
•	Proceso / Durante: ¿Qué hizo? ¿Qué herramientas usó? ¿Cómo fue la experiencia?
•	Resultado / Después: Resultado concreto y medible. Números cuando sea posible.
•	CTA: «¿Quieres una transformación similar? Agenda tu consultoría gratuita →»
3.5 Implementación Drupal 11 SaaS
Content Type: jaraba_case_study
# Content Type: jaraba_case_study
# Machine name: case_study
# Fields:
#   - title (text, required)
#   - field_protagonist_name (text)
#   - field_protagonist_role (text)
#   - field_protagonist_photo (image, required)
#   - field_program (entity_reference: taxonomy_term 'programs')
#   - field_vertical (entity_reference: taxonomy_term 'verticals')
#   - field_quote_hero (text_long) -- cita principal
#   - field_quotes_additional (text_long, multiple) -- citas extra
#   - field_video (media: video, optional)
#   - field_context_before (text_long, formatted)
#   - field_process (text_long, formatted)
#   - field_results (text_long, formatted)
#   - field_metrics (key_value pairs: label/value, ej: 'Facturación'/'30%')
#   - field_cta_text (text, default: '¿Quieres resultados similares?')
#   - field_cta_link (link, default: '/contacto')
# Display:
#   - Teaser: photo + quote_hero + protagonist name/role + link
#   - Full: Hero photo + all sections structured
# View: casos_de_exito
#   - Path: /casos-de-exito
#   - Exposed filter: field_vertical (select)
#   - Sort: created DESC
#   - No results text: 'Próximamente más historias de transformación.'
 
4. BUG-003: Blog Posts Ficticios en Homepage [CRITICAL]
4.1 Diagnóstico
Los 3 blog posts del homepage usan imágenes genéricas de Astra starter content (branding.jpg, graphic.jpg, web.jpg) y todos los links «Leer más» apuntan de vuelta a la homepage. Los títulos prometen contenido que no existe.
4.2 Acción inmediata (WordPress)
Opción recomendada: Reemplazar los 3 posts ficticios con contenido REAL derivado del Kit Impulso Digital. El Kit ya contiene el material — solo hay que reformatearlo como blog posts.
Post ficticio actual	Reemplazo propuesto	Fuente del contenido
Cómo una Tienda de Barrio Aumentó su Facturación un 30%	MIGRAR caso real de Ángel Martínez (Camino Viejo) desde jarabaimpact.com	jarabaimpact.com/caso-de-exito-del-estres...
5 Herramientas Gratuitas para Lanzar tu Negocio	Las 7 Herramientas Gratuitas que Uso con Mis Clientes (reformateo del Kit Impulso Digital)	Kit Impulso Digital PDF, págs. 3-9
3 Claves para LinkedIn en la Era de la IA	Cómo Optimizar tu LinkedIn para Encontrar Trabajo en 2026 (expandir Herramienta #3 del Kit)	Kit Impulso Digital PDF, pág. 5
4.3 Contenido completo del Blog Post #2 (ejemplo)
Título: Las 7 Herramientas Gratuitas que Uso con Mis Clientes para su Transformación Digital
Slug: /blog/7-herramientas-gratuitas-transformacion-digital/
Meta description: Descubre las 7 herramientas 100% gratuitas y probadas que utilizo con mis clientes para impulsar su transformación digital. Canva, Google My Business, LinkedIn, Notion, WhatsApp Business, Calendly y ChatGPT.
Categoría: Guías prácticas
Estructura del post:
•	Intro (200 palabras): Contexto de por qué estas 7 herramientas y no otras. Filosofía Sin Humo. Las elegí porque son gratuitas, fáciles y dan resultados reales.
•	H2 por cada herramienta (7 secciones): Nombre + enlace, Para quién (3 avatares), Primeros pasos (4 steps), Consejo de experto. Total ~300 palabras por herramienta.
•	CTA intermedio (tras herramienta #4): «Si quieres todas estas herramientas explicadas paso a paso en un PDF bonito, descarga tu Kit de Impulso Digital gratuito →»
•	Hoja de ruta 7 días (al final): Tabla día-acción-herramienta
•	CTA final: Descarga el Kit + Agenda diagnóstico gratuito
Longitud objetivo: ~2.500 palabras. Optimizado para keyword «herramientas gratuitas transformación digital pymes» (vol. est. 480/mes, dificultad baja).
 
5. BUG-004: Formulario de Contacto No Renderizado [HIGH]
5.1 Diagnóstico
La página /contacto/ menciona «Rellena el formulario» pero no hay formulario visible en el HTML. Solo aparecen links a redes sociales, un iframe de Google Maps y el botón «Acceder al Ecosistema».
5.2 Implementación WordPress
•	Verificar si hay shortcode de formulario no renderizado (posible plugin desactivado)
•	Crear formulario con campos: Nombre (required), Email (required), Teléfono (optional), ¿Qué te describe mejor? (select: Busco empleo / Quiero emprender / Tengo un negocio / Soy entidad/institución / Otra consulta), Mensaje (textarea)
•	Añadir Calendly embed (link: calendly.com/pepejaraba) como alternativa: «Prefiero agendar directamente una llamada de 15 minutos →»
•	Acción post-submit: email a hola@pepejaraba.com + autorespuesta al usuario + si avatar=institución, redirigir a jarabaimpact.com
5.3 Implementación Drupal 11
# Webform: contacto_general
# Route: /contacto
# Layout: 2 columnas (formulario izq | info contacto der)
# Columna izquierda:
#   - nombre (textfield, required)
#   - email (email, required)
#   - telefono (tel, optional)
#   - avatar_type (select, required):
#     - job_seeker: 'Busco empleo o quiero reciclarme'
#     - entrepreneur: 'Quiero emprender o tengo una idea'
#     - business_owner: 'Tengo un negocio y quiero digitalizarlo'
#     - institution: 'Represento a una entidad o institución'
#     - other: 'Otra consulta'
#   - mensaje (textarea, required, placeholder: 'Cuéntame brevemente tu situación...')
#   - gdpr_consent (checkbox, required)
#   - submit: 'Enviar mensaje →'
# Columna derecha:
#   - Calendly embed: calendly.com/pepejaraba/diagnostico-15min
#     Headline: '¿Prefieres hablar directamente?'
#     Sub: 'Agenda una llamada gratuita de 15 minutos'
#   - WhatsApp: wa.me/34623174304 con botón verde
#   - Mapa Google Maps de Santaella
#   - Links redes sociales
# Post-submit actions:
#   1. Email to hola@pepejaraba.com with all fields
#   2. Auto-reply to user: 'Gracias {nombre}, te respondo en <48h'
#   3. If avatar_type = 'institution':
#      ECA trigger: tag 'institutional_lead' + redirect jarabaimpact.com
#   4. Create jaraba_lead entity
#   5. Webhook ActiveCampaign: create contact + tag 'form_contacto'
 
6. BUG-005: Página /servicios/ con Value Ladder [HIGH]
6.1 Diagnóstico
No existe página de servicios en el menú de navegación de pepejaraba.com. El Value Ladder está documentado en Doc 123 (Sec 5.3) y Doc 46 pero es invisible para el visitante.
6.2 Estructura de la página /servicios/
La página debe presentar la escalera de valor como un recorrido natural, no como una tabla de precios:
Peldaño	Nombre comercial	Precio	Entregable	CTA
0. Entrada	Kit de Impulso Digital	GRATIS	PDF 11 págs + hoja de ruta 7 días	Descargar Kit →
1. Diagnóstico	Diagnóstico Express Digital	GRATIS (15 min)	Evaluación rápida + 3 recomendaciones personalizadas	Agenda tu diagnóstico →
2. Evaluación	Calculadora de Madurez Digital	47-97€	Informe detallado de madurez + plan priorizado	Evaluar mi negocio →
3. Estrategia	Sesión Estratégica 1:1	197-297€ (90 min)	Plan de Impulso Digital personalizado completo	Reservar sesión →
4. Implementación	Proyecto de Digitalización	997-4.997€	Implementación completa con Método Jaraba™ en 90 días	Solicitar propuesta →
5. Acompañamiento	Mentoría Continua	497€/mes	Mentoría 1:1 + acceso VIP al Ecosistema + soporte prioritario	Aplicar →
6.3 Copy de cada peldaño
Cada peldaño se presenta como una sección del page con:
•	Número de peldaño + nombre + icono representativo
•	1 frase de «Esto es para ti si...» (segmentación por avatar)
•	3-4 bullets de qué incluye
•	Precio claro (sin sorpresas = filosofía Sin Humo)
•	Botón CTA específico
•	Flecha visual hacia el siguiente peldaño («Y si necesitas más...»)
6.4 Navegación
Añadir «Servicios» al menú principal ENTRE «Método» y «Casos de éxito». Orden final: Inicio | Manifiesto | Método | Servicios | Casos de éxito | Blog | Contacto
 
7. BUG-006: Secuencia Email Post-Descarga Kit [HIGH]
7.1 Contexto
Es el gap más dañino del embudo. Sin nurturing, el 95%+ de leads que descargan el Kit se enfrían en 48h. La secuencia debe activarse automáticamente tras la descarga y segmentarse por avatar (job_seeker, entrepreneur, business_owner).
7.2 Secuencia completa: 7 emails en 14 días
Email	Día	Asunto	Objetivo	CTA
E1	Día 0 (inmediato)	Tu Kit de Impulso Digital está aquí 📩	Entrega del PDF + bienvenida cálida	Descargar Kit (link directo)
E2	Día 1	¿Por dónde empezar? Tu primer paso para hoy	Recomendar herramienta #1 según avatar	Empezar con [herramienta]
E3	Día 3	Mi historia: por qué dejé los fondos europeos	Storytelling personal (extracto Manifiesto)	Leer el Manifiesto completo →
E4	Día 5	El error #1 que veo en [avatar_context]	Valor educativo + posicionamiento como experto	Ver el Método Jaraba™
E5	Día 7	[Nombre], ¿has probado la herramienta #X?	Re-engagement + check-in	Responder este email
E6	Día 10	La historia de [caso real similar al avatar]	Social proof con caso de éxito del avatar	Leer caso completo →
E7	Día 14	Tu próximo paso: diagnóstico gratuito de 15 min	Conversión a consultoría gratuita	Agenda tu diagnóstico →
7.3 Segmentación por avatar
Los emails E2, E4, E5, E6 tienen contenido dinámico según avatar_type:
Email	job_seeker	entrepreneur	business_owner
E2: Herramienta recomendada	#3 LinkedIn	#4 Notion	#2 Google My Business
E4: Error #1	CV genérico sin keywords	Planificar sin validar	No estar en Google Maps
E5: Check-in	¿Has actualizado tu LinkedIn?	¿Has organizado tu idea en Notion?	¿Has reclamado tu ficha de Google?
E6: Caso de éxito	Marcela Calabia	Ángel Martínez	Caso PYME (por crear)
7.4 Implementación técnica
Fase 1 (WordPress inmediato): Configurar en ActiveCampaign/Mailchimp como automation con tags de segmentación.
Fase 2 (Drupal 11): Implementar mediante jaraba_email module con ECA triggers (ver Doc 146 - SendGrid ECA Architecture):
# ECA Model: kit_impulso_email_sequence
# Trigger: jaraba_lead.created WHERE source='kit_impulso'
# Sequence:
#   Step 1 (delay: 0): template: kit_welcome_{avatar_type}
#   Step 2 (delay: 86400): template: kit_first_step_{avatar_type}
#   Step 3 (delay: 259200): template: kit_manifesto
#   Step 4 (delay: 432000): template: kit_error_{avatar_type}
#   Step 5 (delay: 604800): template: kit_checkin_{avatar_type}
#   Step 6 (delay: 864000): template: kit_case_study_{avatar_type}
#   Step 7 (delay: 1209600): template: kit_diagnostic_cta
# Goal: If user books diagnostic OR visits /servicios/ => complete sequence
# Unsubscribe: Standard unsubscribe link in footer
 
8. BUG-007: Cross-Pollination de Testimonios [HIGH]
8.1 Acción
Migrar los 3 testimonios reales de jarabaimpact.com a la sección de testimonios del homepage de pepejaraba.com. Actualmente el homepage no tiene testimonios visibles (la sección existe en el wireframe pero no en producción).
8.2 Implementación
•	Añadir sección «Historias de Transformación» en homepage de pepejaraba.com, ANTES de la sección de blog
•	Formato: carrusel o grid de 3 cards con foto, nombre, rol, cita corta, link a caso completo
•	Cada card enlaza al caso de éxito completo en pepejaraba.com/casos-de-exito/ (NO a jarabaimpact.com)
•	Añadir video embed de Marcela Calabia como elemento destacado (ya existe en jarabaimpact.com)
•	En jarabaimpact.com, añadir link recíproco: «Ver más historias en pepejaraba.com →»
 
9. BUG-008: Optimización del Hero Section [MEDIUM]
9.1 Copy actual vs propuesto
Elemento	Actual	Propuesto
Headline	Transformación digital para todos, sin rodeos	+100 millones de euros gestionados para impulsar la transformación digital de pymes, emprendedores y profesionales. Sin humo.
Subheadline	Soy Pepe Jaraba. Te ayudo a conseguir resultados reales de transformación digital con un método práctico y sin tecnicismos.	Soy Pepe Jaraba. +30 años convirtiendo la complejidad digital en planes de acción concretos para agroalimentario, comercio local y servicios profesionales.
CTA primario	Descarga tu Kit de Impulso Gratuito →	Descarga tu Kit de Impulso Gratuito → (SIN CAMBIO pero con href funcional)
CTA secundario	(no existe)	Descubre el Método Jaraba™ → (btn-secondary, href=/metodo/)
9.2 Justificación
•	La credencial +100M€ es el diferenciador más potente y debe abrir el headline (proof-first storytelling)
•	Nombrar los 3 segmentos (pymes, emprendedores, profesionales) en vez de «para todos»: más específico = más conexión
•	«Sin humo» como cierre de headline: es el marcador de marca que diferencia de 500 consultores genéricos
•	Añadir CTA secundario al Método: da opción a quien no está listo para dar email pero quiere saber más
 
10. BUG-009: Botón «Acceder al Ecosistema» [MEDIUM]
El botón del header lleva a plataformadeecosistemas.com sin contexto. Solución:
•	Cambiar texto a «Plataforma de Formación →» o «Accede a la Formación →»
•	Añadir tooltip/hover: «Accede a nuestros cursos, comunidad y herramientas digitales»
•	Alternativa: crear página puente /ecosistema/ en pepejaraba.com que explique qué es el ecosistema con CTAs a cada vertical
11. BUG-010/011: Schema JSON-LD y Meta Tags [MEDIUM]
11.1 Schema JSON-LD a insertar en <head>
Insertar los 3 schemas definidos en Doc 124 (Person, Organization, LocalBusiness) en el <head> de TODAS las páginas de pepejaraba.com. En WordPress: via plugin Yoast/RankMath o functions.php wp_head action.
11.2 Meta tags por página
Página	Title tag	Meta description
/	Pepe Jaraba | Transformación Digital para Pymes, Emprendedores y Profesionales	Consultor de transformación digital con +30 años de experiencia. Método práctico sin tecnicismos para agroalimentario, comercio local y servicios. Descarga tu Kit gratuito.
/manifiesto/	Mi Historia: Por Qué Dejé los Fondos Europeos | Pepe Jaraba	+30 años gestionando +100M€ en fondos europeos. Vi un puente roto entre los recursos y las personas. Así nació el Ecosistema Jaraba.
/metodo/	El Método Jaraba™: Tu Plan de Transformación Digital en 90 Días	3 fases, 90 días, resultados reales. Diagnóstico, implementación y optimización con el Ciclo de Impacto Digital. Sin humo.
/servicios/	Servicios de Transformación Digital | Desde Gratis hasta Mentoría Premium	Kit gratuito, diagnóstico express, consultoría estratégica y mentoría continua. Escalera de valor adaptada a tu momento.
/casos-de-exito/	Casos de Éxito Reales de Transformación Digital | Pepe Jaraba	Historias reales de emprendedores, profesionales y pymes que transformaron su carrera y negocio con el Método Jaraba™.
/contacto/	Contacto | Agenda tu Consultoría Gratuita | Pepe Jaraba	Hablemos sin compromiso. Agenda una llamada de 15 min o envía un mensaje. WhatsApp: +34 623 174 304.
/blog/	Blog de Transformación Digital | Ideas y Guías Sin Humo | Pepe Jaraba	Guías prácticas, casos de éxito y estrategias de digitalización para pymes, emprendedores y profesionales.
11.3 Open Graph tags
Implementar og:title, og:description, og:image (usar foto circular de Pepe como default), og:url, og:type, twitter:card para cada página.
 
12. BUG-012: Cross-Pollination entre Dominios [MEDIUM]
12.1 Estado actual
El footer de pepejaraba.com enlaza a jarabaimpact.com y plataformadeecosistemas.com, pero la conexión es unidireccional y sin contexto narrativo.
12.2 Implementación
•	pepejaraba.com footer: mantener estructura actual pero añadir descripciones de 1 línea bajo cada link del ecosistema
•	jarabaimpact.com: añadir en footer sección «Conoce a Pepe Jaraba» con link a pepejaraba.com/manifiesto/
•	jarabaimpact.com casos de éxito: añadir «Ver más historias y recursos en pepejaraba.com →»
•	pepejaraba.com /servicios/: peldaño 4-5 incluir «Parte de nuestro ecosistema de transformación digital» con link contextualizado a plataformadeecosistemas.com
•	Ambos sitios: compartir design tokens (colores, tipografías, logo) para coherencia visual
13. BUG-013: Capturas de Pantalla en Kit PDF [LOW]
Añadir 1-2 screenshots por herramienta en el Kit Impulso Digital. Prioridad: Google My Business (captura de ficha ejemplo), Canva (captura de plantilla de CV), LinkedIn (captura de perfil optimizado).
Herramienta para actualizar PDF: regenerar via ReportLab o actualizar en herramienta de diseño original.
14. BUG-014: Botón WhatsApp Sticky Mobile [LOW]
Añadir botón flotante WhatsApp en esquina inferior derecha de todas las páginas de pepejaraba.com en mobile. Enlace: wa.me/34623174304?text=Hola%20Pepe%2C%20he%20visitado%20tu%20web%20y%20me%20gustar%C3%ADa%20hablar%20contigo.
WordPress: plugin «Click to Chat» o custom CSS/JS. Drupal 11: componente Twig en page.html.twig condicionado a breakpoint mobile.
 
15. Roadmap de Implementación Consolidado
15.1 Sprint 1: Emergency Fixes (48 horas)
ID	Tarea	Entorno	Esfuerzo	Responsable
BUG-001	Conectar CTA Kit a formulario con captura de email	WordPress prod	3h	Claude Code / Dev
BUG-002	Crear 3 posts de casos de éxito (migrar de jarabaimpact)	WordPress prod	4h	Claude Code / Dev
BUG-003	Reemplazar 3 blog posts ficticios del homepage	WordPress prod	2h	Claude Code / Dev
BUG-004	Insertar formulario funcional en /contacto/	WordPress prod	2h	Claude Code / Dev
BUG-014	Añadir botón WhatsApp sticky	WordPress prod	30min	Claude Code / Dev
15.2 Sprint 2: Core Funnel (1 semana)
ID	Tarea	Entorno	Esfuerzo	Responsable
BUG-005	Crear página /servicios/ con Value Ladder completa	WordPress prod	6h	Claude Code + Copy
BUG-006	Diseñar + implementar secuencia 7 emails post-Kit	ActiveCampaign/Mailchimp	12h	Marketing + Dev
BUG-007	Añadir sección testimonios en homepage pepejaraba	WordPress prod	2h	Claude Code / Dev
BUG-008	Reescribir copy Hero section	WordPress prod	1h	Copy + Dev
BUG-010/011	Insertar Schema JSON-LD + meta tags + OG tags	WordPress prod	3h	Claude Code / Dev
15.3 Sprint 3: Growth Infrastructure (2 semanas)
ID	Tarea	Entorno	Esfuerzo	Responsable
BUG-009	Crear página puente /ecosistema/ o mejorar botón header	WordPress prod	3h	Claude Code
BUG-012	Implementar cross-pollination bidireccional entre dominios	Ambos sitios	4h	Claude Code
BUG-013	Actualizar Kit PDF con capturas de pantalla	PDF / Diseño	4h	Diseño
NEW-001	Publicar 2 blog posts adicionales (SEO pillar content)	WordPress prod	8h	Claude Code + Copy
NEW-002	Implementar Calendly embed en /contacto/ y /servicios/	WordPress prod	2h	Claude Code
NEW-003	A/B test headline Hero (actual vs propuesta)	WordPress prod	2h	Dev + Analytics
NEW-004	Exit-intent popup para Kit Impulso Digital	WordPress prod	2h	Claude Code
15.4 Sprint 4: Migración a Drupal 11 SaaS
Una vez completados Sprints 1-3 en WordPress y validada la conversión, migrar todo el contenido y funcionalidad al entorno Drupal 11 multi-tenant en jaraba-saas.lndo.site, siguiendo las especificaciones de los docs 05, 100, 123, 124, 126, 146.
 
16. Criterios de Aceptación Globales
Criterio	Métrica	Umbral
CTA Kit funcional	Email capturado + PDF entregado en <30s	100% de submits
Casos de éxito visibles	3+ casos reales publicados con foto y cita	No «No post found»
Blog posts reales	3+ posts con contenido original >1000 palabras	No imgs stock Astra
Formulario contacto	Submit funcional + autorespuesta + notificación admin	100% entrega
Página servicios	Value Ladder con 5-6 peldaños, precios y CTAs funcionales	Accesible desde nav
Email sequence	7 emails automatizados disparados tras descarga Kit	Tasa apertura >25%
Schema JSON-LD	3 schemas válidos en Rich Results Test de Google	0 errores
Meta tags	Title + description únicos por página	6+ páginas
Mobile UX	WhatsApp sticky + formularios responsive + textos legibles	Core Web Vitals green
Cross-domain	Links bidireccionales con contexto entre los 3 dominios	Verificación manual

--- Fin del Documento ---
