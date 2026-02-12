
JARABA IMPACT PLATFORM

Especificación Técnica

Sitio Web Institucional B2B

jarabaimpact.com

Hub Institucional para Aliados Estratégicos, Fondos y Compradores B2B

Código	128_JarabaImpact_Website_v1
Versión	1.0
Fecha	Enero 2026
Estado	Especificación Técnica Completa
Dependencias	100_Frontend_Architecture, 05_Core_Theming, 126_Personal_Brand_Tenant, 127_PED_Corporate_Website
 
1. Resumen Ejecutivo
Este documento define la especificación técnica completa para jarabaimpact.com, el sitio web institucional B2B del Ecosistema Jaraba. Este dominio actúa como la puerta de entrada para aliados estratégicos, instituciones públicas, fondos europeos y compradores institucionales de las verticales SaaS.
1.1 Posición en la Arquitectura de Dominios
Dominio	Función	Audiencia Principal
pepejaraba.com	Marca Personal / Thought Leadership	Público general, leads iniciales
jarabaimpact.com	INSTITUCIONAL B2B / VENTAS	Junta, SEPE, inversores, compradores
plataformadeecosistemas.com	Operativo SaaS Drupal	Usuarios finales multi-tenant
plataformadeecosistemas.es	Corporativo Legal	Reguladores, socios, due diligence
1.2 Propósito Estratégico
El sitio jarabaimpact.com cumple funciones críticas que ningún otro dominio del ecosistema puede cubrir:
•	Legitimidad Institucional: Punto de verificación para convocatorias públicas y fondos europeos.
•	Venta B2B: Showcase de verticales SaaS (AgroConecta, ComercioConecta, ServiciosConecta) para compradores institucionales.
•	Alianzas Estratégicas: Canal oficial para universidades, cámaras de comercio, ONGs y partners.
•	Impacto Social: Datos verificables del Triple Motor Económico y métricas de transformación.
•	Comunicación Institucional: Newsroom para prensa, notas de prensa y material multimedia.
1.3 Diferenciación vs. Otros Dominios
Aspecto	pepejaraba.com	jarabaimpact.com	plataformadeecosistemas.es
Tono	Personal, cercano	Institucional, profesional	Legal, formal
CTA Principal	Suscríbete al newsletter	Solicita una demo / Contacta	Descarga dossier
Venta Directa	No	Sí (B2B)	No
Contenido	Blog, podcast, recursos	Verticales, casos de éxito	Datos societarios
SEO Focus	Long-tail educativo	Transaccional B2B	Brand terms
 
2. Arquitectura de Información
2.1 Estructura de Navegación Principal
Sección	Subsecciones	Propósito
Inicio	Hero, Propuesta de valor, KPIs destacados	Primera impresión institucional
Plataforma	Triple Motor, Tecnología, Arquitectura	Explicar el modelo de negocio
Verticales	AgroConecta, ComercioConecta, ServiciosConecta, Empleabilidad, Emprendimiento	Showcase de productos B2B
Impacto	KPIs, Casos de éxito, Testimonios, Certificaciones	Prueba social y credibilidad
Programas	Andalucía +ei, Kit Digital, Colaboraciones	Programas institucionales
Recursos	Centro de documentación, Libro Blanco, Prensa	Materiales descargables
Contacto	Formulario institucional, Mapa	Conversión B2B
2.2 Mapa del Sitio Detallado
Nivel 1 - Páginas principales:
1.	/ → Inicio (Landing institucional)
2.	/plataforma → Plataforma (El modelo)
3.	/verticales → Verticales SaaS
4.	/impacto → Impacto y resultados
5.	/programas → Programas institucionales
6.	/recursos → Centro de recursos
7.	/contacto → Contacto institucional
Nivel 2 - Subpáginas de Verticales:
•	/verticales/agroconecta → Marketplace agrícola
•	/verticales/comercioconecta → Comercio local
•	/verticales/serviciosconecta → Servicios profesionales
•	/verticales/empleabilidad → Plataforma de empleo
•	/verticales/emprendimiento → Apoyo a emprendedores
Nivel 2 - Subpáginas de Recursos:
•	/recursos/documentacion → Centro de documentación
•	/recursos/libro-blanco → Libro Blanco del modelo
•	/recursos/prensa → Newsroom y notas de prensa
•	/recursos/casos-de-exito → Estudios de caso detallados
 
3. Diseño y Experiencia de Usuario
3.1 Sistema Visual Institucional
Elemento	Valor	Uso
Color Primario	#1B4F72	Headers, CTAs principales, textos destacados
Color Secundario	#17A589	Acentos, iconos, elementos interactivos
Color Acento	#E67E22	Botones de acción, badges, alertas
Tipografía Display	Montserrat	Títulos, navegación, CTAs
Tipografía Body	Inter	Texto corrido, descripciones
Border Radius	12px / 16px	Cards, botones, contenedores
Sombras	Elevation system	Cards elevadas, dropdowns, modales
3.2 Componentes UI Clave
3.2.1 Hero Section
•	Fondo: Gradiente institucional (#1B4F72 → #17A589) con efectos de partículas/orbs
•	Titular principal: Propuesta de valor clara en menos de 10 palabras
•	Subtítulo: Explicación del modelo en 1-2 líneas
•	CTAs duales: "Solicita una Demo" (primario) + "Descarga el Libro Blanco" (secundario)
•	Trust badges: Logos de instituciones colaboradoras (Junta, SEPE, universidades)
3.2.2 Cards de Verticales
•	Icono representativo del sector
•	Nombre de la vertical (AgroConecta, etc.)
•	Tagline de una línea
•	3 features bullets
•	CTA: "Ver más" → página detallada
•	Hover effect: elevación + borde de color
3.2.3 Sección de Impacto
•	Counters animados con números destacados
•	Métricas: +30 años experiencia, +100M€ gestionados, +50.000 beneficiarios
•	Grid de logos de partners/instituciones
•	Carrusel de testimonios con foto, nombre, cargo y cita
3.3 Estrategia Responsive
Breakpoint	Dispositivo	Adaptaciones
≥1280px	Desktop Large	Layout completo, animaciones full
1024-1279px	Desktop Small	Grid reducido, navegación completa
768-1023px	Tablet	Menú hamburguesa, cards apiladas
≤767px	Mobile	Single column, CTAs sticky, navegación drawer
 
4. Contenido por Sección
4.1 Inicio (Homepage)
Hero Principal
Titular: "Infraestructura Digital para la Transformación Rural"
Subtítulo: "Plataforma SaaS multi-tenant que sincroniza empleo, emprendimiento y comercio local con tecnología de impacto."
CTAs: "Solicita una Demo" | "Descarga el Libro Blanco"
Sección Triple Motor
Motor	Descripción	Ejemplos
Motor Institucional	Fondos públicos y programas subvencionados	Andalucía +ei, PIIL, NextGen
Motor Privado	Productos digitales y servicios SaaS	Verticales, membresías, consultoría
Motor de Licencias	Franquicias y certificación Método Jaraba	Consultores certificados, royalties
Sección Verticales (Preview)
Grid de 5 cards con las verticales principales:
•	AgroConecta: "Del campo a tu mesa. Trazabilidad blockchain para productores locales."
•	ComercioConecta: "Impulsa tu comercio local. QR dinámicos y ofertas flash."
•	ServiciosConecta: "Profesionales de confianza. Agenda, firma digital y buzón seguro."
•	Empleabilidad: "Conectamos talento con oportunidades. LMS + Job Board integrado."
•	Emprendimiento: "De la idea al negocio. Diagnóstico, mentoring y financiación."
Sección Impacto (Preview)
Métrica	Valor	Contexto
+30	Años de experiencia	En transformación digital rural
+100M€	Fondos gestionados	En programas públicos
+50.000	Beneficiarios	Formados y acompañados
5	Verticales SaaS	Productos listos para escalar
4.2 Plataforma
Página explicativa del modelo técnico y de negocio:
8.	Visión general del Ecosistema Jaraba
9.	Arquitectura técnica (Drupal 11, Multi-tenant, AI-native)
10.	Triple Motor Económico (detalle completo)
11.	Stack tecnológico (Qdrant, Stripe Connect, etc.)
12.	Ventajas competitivas vs. soluciones tradicionales
13.	Roadmap de desarrollo 2026-2027
 
4.3 Páginas de Verticales
Cada vertical tiene una página dedicada con estructura uniforme:
Sección	Contenido
Hero	Nombre, tagline, imagen representativa, CTA "Solicitar Info"
Problema	Pain points del sector que resuelve
Solución	Features principales con iconos y descripciones
Beneficios	Grid de 6 beneficios con métricas cuando sea posible
Demo	Video o screenshots interactivos de la plataforma
Pricing	Tabla de planes (si aplica) o CTA a contacto
Casos de Éxito	2-3 testimonios del sector
FAQ	5-7 preguntas frecuentes específicas
CTA Final	"Agenda una Demo" con formulario embebido
4.3.1 AgroConecta - Contenido Específico
Tagline: "Marketplace agrícola con trazabilidad blockchain"
Features destacados:
•	QR dinámico de trazabilidad por producto
•	Producer Copilot: De foto a ficha en 45 segundos
•	Hub documental para partners y distribuidores
•	Dashboard de analytics por productor
•	Integración con cooperativas y denominaciones de origen
4.3.2 ComercioConecta - Contenido Específico
Tagline: "Digitalización del comercio local en 48 horas"
Features destacados:
•	Ofertas Flash geolocalizadas
•	QR dinámico para escaparates
•	Integración POS (TPV)
•	Local SEO automatizado
•	App móvil white-label para asociaciones
4.3.3 ServiciosConecta - Contenido Específico
Tagline: "Profesionales de confianza con firma digital"
Features destacados:
•	Buzón de Confianza con cifrado extremo a extremo
•	Firma digital PAdES integrada
•	Videoconferencia con grabación y actas
•	Presupuestador automático con IA
•	Portal documental para clientes
 
4.4 Impacto
Página dedicada a demostrar resultados medibles:
4.4.1 Dashboard de Métricas
•	Counters animados con cifras actualizadas
•	Gráficos de evolución temporal
•	Comparativas antes/después
•	Desglose por vertical y región
4.4.2 Casos de Éxito
•	Format: Reto → Solución → Resultados → Testimonio
•	Incluir datos cuantitativos siempre que sea posible
•	Fotos reales de beneficiarios/empresas
•	Videos cortos de testimonios (opcional)
4.4.3 Certificaciones y Reconocimientos
•	Homologación SEPE para teleformación
•	Partnerships con Junta de Andalucía
•	Sellos de calidad (ISO, accesibilidad, etc.)
•	Premios y menciones en medios
4.5 Programas Institucionales
Programa	Descripción	Estado
Andalucía +ei	Orientación profesional e inserción laboral para desempleados	Activo
Kit Digital	Digitalización de PYMEs con fondos NextGen	Activo
PIIL	Proyectos Integrales de Inserción Laboral	En preparación
Autodigitales	Formación digital para autónomos rurales	Piloto
Expansión LATAM	Adaptación del modelo para Latinoamérica	Planificación
4.6 Centro de Recursos
4.6.1 Documentación
•	Libro Blanco del Ecosistema Jaraba (PDF descargable)
•	Fichas técnicas de cada vertical
•	Guías de implementación para instituciones
•	Dossier corporativo actualizado
4.6.2 Prensa (Newsroom)
•	Notas de prensa ordenadas cronológicamente
•	Kit de prensa con logos, fotos y recursos gráficos
•	Contacto para medios de comunicación
•	Menciones y apariciones en medios
 
5. Estrategia SEO y GEO
5.1 Estrategia de Keywords
Tipo	Keywords Objetivo	Intención
Brand	Jaraba Impact, Ecosistema Jaraba, Método Jaraba	Navegacional
Producto	plataforma digitalización rural, SaaS multi-tenant España	Transaccional
Problema	transformación digital pymes rurales, digitalizar comercio local	Informacional
Sector	marketplace agrícola España, software trazabilidad alimentos	Transaccional
Institucional	programas digitalización fondos europeos, kit digital andalucía	Informacional
5.2 Optimización GEO (IA Generativa)
Estrategia para posicionamiento en motores de búsqueda generativos (ChatGPT, Claude, Perplexity):
•	Answer Capsules: Bloques de contenido estructurados para respuestas directas de IA
•	Schema.org: Marcado semántico completo (Organization, Product, Service, FAQ)
•	Server-Side Rendering (SSR) para que crawlers de IA indexen contenido dinámico
•	Contenido factual y verificable con fuentes citadas
•	Actualización frecuente de datos y métricas
5.3 Metadatos por Página
Página	Title	Meta Description (155 chars)
Inicio	Jaraba Impact | Infraestructura Digital Rural	Plataforma SaaS para transformación digital de empleo, emprendimiento y comercio local. +30 años de experiencia.
Plataforma	Plataforma Jaraba Impact | Triple Motor Económico	Modelo híbrido que sincroniza fondos públicos, productos SaaS y certificación profesional.
AgroConecta	AgroConecta | Marketplace Agrícola con Trazabilidad	Conecta productores locales con consumidores. Trazabilidad blockchain, QR dinámico y logística.
Impacto	Impacto | +100M€ Gestionados en Transformación Digital	Métricas verificables, casos de éxito y certificaciones. Descubre nuestros resultados.
 
6. Estrategia de Conversión (CRO)
6.1 Embudos de Conversión
6.1.1 Embudo Institucional (Junta, SEPE, Universidades)
14.	Entrada: Homepage → Sección Impacto → Programas
15.	Conversión media: Descarga de Libro Blanco (lead magnet)
16.	Conversión alta: Formulario "Contacto Institucional"
17.	Nurturing: Email sequence institucional + Dossier detallado
18.	Cierre: Reunión con Pepe/equipo → Propuesta personalizada
6.1.2 Embudo Comprador B2B (Vertical SaaS)
19.	Entrada: Búsqueda específica → Página de Vertical
20.	Conversión media: Demo interactiva o video tour
21.	Conversión alta: "Solicita una Demo Personalizada"
22.	Nurturing: Secuencia de casos de éxito del sector
23.	Cierre: Demo en vivo → Propuesta → Piloto/Contrato
6.2 Formularios de Conversión
Formulario	Campos	Ubicación
Libro Blanco	Nombre, Email, Organización, Rol	Modal / Recursos
Demo Vertical	Nombre, Email, Empresa, Vertical interés, Tamaño empresa	Páginas de vertical
Contacto Institucional	Nombre, Email, Organización, Tipo (pública/privada), Mensaje	Página de contacto
Newsletter	Email	Footer / Sidebar
6.3 Jerarquía de CTAs
Prioridad	CTA	Estilo
Primario	"Solicita una Demo"	Botón naranja, grande, con icono
Secundario	"Descarga el Libro Blanco"	Botón outline azul, mediano
Terciario	"Ver casos de éxito"	Link con flecha, sin botón
Sticky	"¿Hablamos?"	Botón flotante esquina inferior derecha
 
7. Implementación Técnica
7.1 Stack Tecnológico
Capa	Tecnología	Justificación
CMS	Drupal 11	Coherencia con plataforma principal, multilingüe, API-first
Frontend	Twig + Tailwind CSS	Rendimiento, mantenibilidad, SSR nativo
Hosting	IONOS Dedicated Server	Proximidad datos, GDPR, coste controlado
CDN	Cloudflare	Performance, DDoS protection, edge caching
Analytics	Plausible / Matomo	Privacy-first, GDPR compliant
Forms	Webform + CRM Integration	Gestión de leads, automatización
Chat	Intercom / Crisp	Soporte en tiempo real, chatbot
7.2 Objetivos de Performance
Métrica	Objetivo	Herramienta de Medición
LCP (Largest Contentful Paint)	< 2.5s	Lighthouse / PageSpeed
FID (First Input Delay)	< 100ms	Web Vitals
CLS (Cumulative Layout Shift)	< 0.1	Lighthouse
TTFB (Time to First Byte)	< 600ms	WebPageTest
Performance Score	> 90	Lighthouse mobile
7.3 Accesibilidad (WCAG 2.1 AA)
•	Contraste de color mínimo 4.5:1 para texto normal
•	Navegación completa por teclado
•	Textos alternativos en todas las imágenes
•	Estructura de headings semántica (H1 único por página)
•	Formularios con labels asociados y mensajes de error claros
•	Skip links para navegación rápida
•	Responsive text sizing (rem units)
 
8. Roadmap de Implementación
Sprint	Duración	Entregables
Sprint 1	2 semanas	Diseño UI/UX completo, sistema de diseño, maquetas Figma
Sprint 2	2 semanas	Homepage, navegación, footer, componentes base
Sprint 3	2 semanas	Páginas de Plataforma e Impacto, counters animados
Sprint 4	2 semanas	Páginas de Verticales (5 páginas con contenido)
Sprint 5	2 semanas	Programas, Recursos, Centro de documentación
Sprint 6	1 semana	Formularios, integraciones CRM, analytics, QA final
Estimación total: 11 semanas (~3 meses)
Inversión estimada: €8.000 - €12.000 (desarrollo) + €2.000 - €3.000 (contenido y copywriting)
 
9. Métricas de Éxito (KPIs)
KPI	Baseline	Objetivo 6 meses	Medición
Visitas mensuales	0	5.000	Plausible
Leads cualificados/mes	0	50	CRM
Descargas Libro Blanco	0	200	Webform
Solicitudes de Demo	0	20	CRM
Tiempo medio en página	-	> 2:30	Analytics
Bounce rate	-	< 50%	Analytics
Posición keywords principales	-	Top 10	Ahrefs/Semrush

— Fin del Documento —
128_JarabaImpact_Website_v1.docx
Enero 2026
