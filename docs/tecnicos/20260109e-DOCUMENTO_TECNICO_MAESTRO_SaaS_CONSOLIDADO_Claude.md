
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DOCUMENTO TÉCNICO MAESTRO
ARQUITECTURA SaaS CONSOLIDADA

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

JARABA IMPACT PLATFORM
Ecosistema Digital Multi-Tenant sobre Drupal 11

Integración de Propuestas:
Arquitectura SaaS + Integración Vertical + UX/UI + Sistema de Theming

Versión:	1.0 Consolidada
Fecha:	Enero 2026
Metodología:	"Sin Humo" - Desarrollo a Medida
 
Tabla de Contenidos
Tabla de Contenidos	1
1. Resumen Ejecutivo	1
1.1 Visión Estratégica	1
1.2 Documentos Consolidados	1
1.3 Filosofía Sin Humo	1
2. Arquitectura Multi-Tenant en Drupal 11	1
2.1 Decisión Arquitectónica: Single-Instance vs Multisite	1
2.2 Stack Tecnológico Consolidado	1
2.3 Configuración de Tipos de Grupo	1
3. Modelo de Datos: Entidades de Contenido Configurables	1
3.1 Entidades Core del SaaS	1
Entidad: Vertical	1
Entidad: Plan de Suscripción	1
Entidad: Tenant (Inquilino)	1
4. Sistema de Theming Configurable	1
4.1 Arquitectura de 4 Capas	1
4.2 Jerarquía de Herencia de Estilos	1
4.3 Variables CSS Configurables	1
Identidad de Marca	1
Tipografía	1
Layout	1
Componentes	1
5. Blueprint de Verticales: AgroConecta como Modelo	1
5.1 AgroConecta: La Primera Vertical Productiva	1
5.2 Sistema de Agentes IA con Strict Grounding	1
5.3 Extensión a Nuevas Verticales	1
6. Sistema de Diseño UX/UI	1
6.1 Principios de Diseño	1
6.2 Tokens de Diseño	1
Espaciado (Grid de 8px)	1
Tipografía	1
Elevación y Sombras	1
6.3 Componentes Core	1
6.4 Accesibilidad WCAG 2.1 AA	1
7. Gestión Financiera y Monetización	1
7.1 Arquitectura de Pagos	1
7.2 Modelo Stripe Connect para Franquicias	1
7.3 Automatización con ECA	1
8. Hoja de Ruta de Implementación	1
8.1 Fases del Proyecto	1
8.2 Métricas de Éxito	1
9. Conclusiones y Próximos Pasos	1
9.1 Resumen de la Arquitectura Consolidada	1
9.2 Ventajas Competitivas	1
9.3 Próximos Pasos Inmediatos	1

(Actualizar campo en Word: Ctrl+A, F9)
 
1. Resumen Ejecutivo
Este documento consolida las mejores prácticas y recomendaciones de cuatro propuestas técnicas independientes para crear la arquitectura definitiva de Jaraba Impact Platform como un SaaS multi-tenant enterprise sobre Drupal 11.
1.1 Visión Estratégica
Jaraba Impact Platform no es un simple portal web, sino una infraestructura SaaS de alta complejidad que actúa como vehículo de impacto central para un modelo de negocio híbrido que integra tres motores fundamentales:
•	Motor Institucional (B2G/B2B): Gestión de programas subvencionados, reportes de impacto, cumplimiento RGPD.
•	Motor de Mercado Privado (B2C/B2B): Venta de productos digitales, suscripciones, mentorías premium.
•	Motor de Licencias y Franquicias (B2B2C): Ecosistema de consultores certificados, marca blanca, royalties.
1.2 Documentos Consolidados
Documento	Aportación Principal
Arquitectura SaaS (Gemini)	Multi-tenancy con Group Module, Stripe Connect, análisis de motores de negocio
Integración Vertical (Gemini)	Sistema de Agentes IA, trazabilidad phy-gital, roadmap de verticales
Arquitectura SaaS (Claude)	Entidades de contenido configurables, ciclo de vida de tenant, ECA workflows
Guía UX/UI (Claude)	Sistema de diseño completo, tokens, componentes, accesibilidad WCAG 2.1
Sistema Theming (Claude)	Arquitectura de 4 capas, CSS Custom Properties, panel visual de admin

1.3 Filosofía Sin Humo
La arquitectura consolida tres principios fundamentales que guían todas las decisiones técnicas:
1.	Rechazo al Bloatware: Código limpio y desarrollo a medida sobre estándares (Drupal Core, Bootstrap 5 SASS). Sin constructores visuales pesados ni plugins genéricos.
2.	Gourmet Digital: La tecnología es invisible. El protagonismo recae en el storytelling, la calidad visual y la percepción de valor.
3.	Arquitectura Componentizada: Sistema modular donde cada pieza (Hero, Ficha, Dashboard) es independiente, parametrizable y reutilizable entre verticales.
 
2. Arquitectura Multi-Tenant en Drupal 11
2.1 Decisión Arquitectónica: Single-Instance vs Multisite
La decisión más crítica del proyecto es cómo implementar la multi-tenencia. Tras analizar ambas propuestas, se valida la elección de Single-Instance Multi-Tenancy con segregación lógica:
Criterio	Drupal Multisite	Single-Instance + Group
Efecto Red	Imposible (datos en silos)	Óptimo (datos cruzados)
Matching Empleo	Requiere API custom	Nativo con Search API
Mantenimiento	N actualizaciones	1 actualización
Marca Blanca	Compleja	Domain Access + Theming
Escalabilidad	Limitada	Ilimitada (horizontal)

DECISIÓN CONSOLIDADA: La arquitectura Single-Instance con Group Module es la única viable para cumplir la promesa de valor del ecosistema: las empresas de la plataforma contratan al talento formado en la plataforma.
2.2 Stack Tecnológico Consolidado
Capa	Tecnología	Justificación
CMS Core	Drupal 11 + PHP 8.3	Gestión de contenidos, usuarios, permisos granulares
Multi-Tenancy	Group + Domain Access	Aislamiento lógico + URLs personalizadas por tenant
E-commerce	Drupal Commerce + Ecwid	Commerce para suscripciones, Ecwid para productos simples
Pagos	Stripe Connect	Split payments para modelo de franquicia
Automatización	ECA (Event-Condition-Action)	Reglas de negocio sin código
Marketing	ActiveCampaign API	CRM y automatización de nurturing
Búsqueda	Search API + Solr	Facetas avanzadas para matching de talento
Caché	Redis + Varnish	Performance multi-tenant
IA	Sistema de Agentes (OpenAI/Gemini)	Copiloto contextual con strict grounding

2.3 Configuración de Tipos de Grupo
Se definen entidades de configuración GroupType para modelar los diferentes niveles de inquilinato:
Tipo de Grupo	Características Habilitadas
Institución	Gestión de subgrupos (programas), personalización de tema, reportes agregados de impacto, campos RGPD
Franquicia	Catálogo de cursos (heredados o propios), gestión de clientes (CRM), pasarela Stripe Connect, royalties
Comunidad	Foros de discusión, tablón de anuncios, biblioteca de recursos, eventos, chat interno
Vertical	Tipos de contenido específicos, taxonomías propias, agentes IA especializados, subtheme visual
 
3. Modelo de Datos: Entidades de Contenido Configurables
3.1 Entidades Core del SaaS
El sistema utiliza entidades de contenido de Drupal para máxima flexibilidad, permitiendo que los administradores configuren planes y servicios sin intervención técnica:
Entidad: Vertical
Campo	Tipo	Descripción
name	string	Nombre de la vertical (AgroConecta, FormaTech, etc.)
machine_name	slug	Identificador único para código y URLs
description	text_long	Descripción para landing y marketing
theme_settings	json	Colores, logo, tipografía por defecto
enabled_features	list_string	Módulos/funcionalidades activas (trazabilidad, empleo, etc.)
ai_agents	entity_ref	Agentes IA especializados para esta vertical

Entidad: Plan de Suscripción
Campo	Tipo	Descripción
name	string	Starter, Professional, Enterprise
vertical	entity_ref	Vertical a la que pertenece
price_monthly	commerce_price	Precio mensual en EUR
price_yearly	commerce_price	Precio anual (con descuento)
features	list_string	Lista de características incluidas
limits	json	{users: 5, storage_gb: 10, ai_queries: 100}
stripe_price_id	string	ID del precio en Stripe para cobro automático

Entidad: Tenant (Inquilino)
Campo	Tipo	Descripción
name	string	Nombre comercial del inquilino
subscription_plan	entity_ref	Plan contratado actualmente
domain	string	Subdominio o dominio personalizado
theme_overrides	json	Personalizaciones de marca (logo, colores)
stripe_customer_id	string	ID de cliente en Stripe
stripe_connect_id	string	ID de cuenta conectada (si es franquicia)
admin_user	entity_ref	Usuario administrador principal
subscription_status	list_string	trial | active | past_due | cancelled
 
4. Sistema de Theming Configurable
4.1 Arquitectura de 4 Capas
El sistema de theming permite personalización visual completa sin recompilar código. Se basa en un concepto de "Puente" que conecta la configuración administrativa con el renderizado:
Capa	Tecnología	Función
1. ADN	_variables.scss	Define valores por defecto y estructura molecular del diseño con Bootstrap 5
2. Control	Panel Admin PHP	Expone selectores visuales (color pickers, radio buttons) en UI de administración
3. Puente	preprocess_html()	Inyecta valores de config como CSS Custom Properties en el <head>
4. Render	CSS Variables	Los componentes usan var(--color-primario) que se sobrescribe en runtime

4.2 Jerarquía de Herencia de Estilos
El sistema implementa una cascada de configuración con 4 niveles de especificidad (el más específico gana):
Nivel	Ámbito	Ejemplo	Quién Configura
1 (Base)	Plataforma	Indigo #6366F1	Equipo Desarrollo
2	Vertical	AgroConecta → Naranja #FF8C42	Admin de Vertical
3	Plan	Enterprise → Badge dorado	Configuración de Plan
4 (Gana)	Tenant	Aceites Marta → Verde #10B981	Admin del Tenant

4.3 Variables CSS Configurables
Catálogo completo de variables que pueden personalizarse desde el panel de administración:
Identidad de Marca
--color-primario, --color-secundario, --color-acento, --color-success, --color-warning, --color-danger, --color-info
Tipografía
--font-familia-base, --font-familia-headings, --font-size-base, --font-size-h1 a --font-size-h6, --font-weight-normal, --font-weight-bold
Layout
--header-height, --header-bg-color, --sidebar-width, --sidebar-bg-color, --footer-bg-color, --container-max-width
Componentes
--card-border-radius, --card-shadow, --button-border-radius, --input-border-radius, --modal-backdrop-opacity
 
5. Blueprint de Verticales: AgroConecta como Modelo
5.1 AgroConecta: La Primera Vertical Productiva
AgroConecta sirve como blueprint arquitectónico para todas las verticales futuras. Demuestra cómo implementar funcionalidades especializadas manteniendo la coherencia del ecosistema:
Componente	Implementación en AgroConecta
Tipos de Contenido	Producto Gourmet, Lote de Producción, Receta, Historia del Productor
Taxonomías	Tipo de Producto, Denominación de Origen, Alérgenos, Certificaciones
Módulo Custom	agroconecta_core: TrazabilidadService, EcwidService, QrController
Integración Externa	Ecwid API (catálogo y pagos), generación de QR para trazabilidad
Agente IA	ConsumerCopilot (recomendaciones), ProductAgent (fichas), RecipeAgent
Theme Settings	Paleta tierra/naranja, iconografía agrícola, hero con fotografía de campo

5.2 Sistema de Agentes IA con Strict Grounding
La arquitectura de IA de AgroConecta se extiende a todo el ecosistema. El principio de Strict Grounding evita alucinaciones anclando las respuestas a datos reales del sistema:
4.	Extracción de Keywords: El sistema extrae palabras clave del mensaje del usuario.
5.	Búsqueda Interna: Realiza queries en nodos de Drupal (Productos, Servicios, FAQ) y taxonomías.
6.	Enriquecimiento de Prompt: Construye un prompt con datos reales, políticas y contexto del tenant.
7.	Generación Controlada: El LLM (OpenAI/Gemini) responde usando exclusivamente la información proporcionada.
5.3 Extensión a Nuevas Verticales
Para crear una nueva vertical (ej: FormaTech, TurismoLocal, ImpulsoEmpleo), seguir este blueprint:
8.	Crear entidad Vertical con campos específicos (taxonomías, features habilitados)
9.	Definir tipos de contenido especializados (Course, JobOffer, Booking, etc.)
10.	Desarrollar subtheme con CSS variables para personalización visual
11.	Configurar planes de suscripción específicos con límites y precios
12.	Implementar servicios PHP especializados si hay lógica de negocio única
13.	Crear agentes IA con prompts específicos del dominio (RecruiterAgent, TutorAgent)
14.	Definir reglas ECA para automatización específica de la vertical
15.	Documentar API si hay integraciones externas (schemas, webhooks)
 
6. Sistema de Diseño UX/UI
6.1 Principios de Diseño
Principio	Implementación
Gourmet Digital	Tecnología invisible, protagonismo en storytelling y percepción de valor premium
Zero Friction	Máximo 3 clics para tareas principales, lenguaje claro sin jerga técnica
Progressive Disclosure	Revelar complejidad gradualmente según el nivel del usuario
Rol-Centric	Cada tipo de usuario ve interfaz optimizada para sus necesidades específicas
Mobile-First	Diseño responsive con prioridad en experiencia táctil y PWA

6.2 Tokens de Diseño
Espaciado (Grid de 8px)
space-1: 4px | space-2: 8px | space-3: 12px | space-4: 16px | space-6: 24px | space-8: 32px | space-12: 48px | space-16: 64px
Tipografía
Font Base: Inter (cuerpo) | Headings: Inter Bold | Monospace: JetBrains Mono | Tamaño base: 16px
Elevación y Sombras
Flat (sin sombra) | Raised (cards) | Overlay (dropdowns) | Modal (diálogos) | Toast (notificaciones)
6.3 Componentes Core
Componente	Variantes	Notas de Uso
Button	Primary, Secondary, Ghost, Danger, Success	Solo 1 primario visible por pantalla
Card	Base, Interactive, Stat, Media, Action	border-radius: 12px, padding: 24px
Form	Input, Select, Checkbox, Radio, Toggle	Labels siempre visibles, error inline
Table	Sortable, Filterable, Selectable, Pagination	Sticky header en scroll
Navigation	Sidebar, TopBar, Breadcrumbs, Tabs	Sidebar: 260px expanded, 72px collapsed
Alert	Info, Success, Warning, Error	Con icono y acción opcional
Modal	Dialog, Drawer, Sheet, Command Palette	Cmd+K para búsqueda global

6.4 Accesibilidad WCAG 2.1 AA
•	Contraste mínimo 4.5:1 para texto, 3:1 para elementos gráficos
•	Focus visible en todos los elementos interactivos (outline 2px offset)
•	Touch targets mínimo 44x44px con spacing de 8px entre elementos
•	Alt text obligatorio en todas las imágenes (automatizable con Agente IA)
•	Skip links para navegación por teclado
•	Soporte reduced-motion para animaciones
 
7. Gestión Financiera y Monetización
7.1 Arquitectura de Pagos
Tipo de Transacción	Herramienta	Flujo
Productos simples	Ecwid	Checkout rápido, widget embebido, sincronización con Drupal
Suscripciones SaaS	Commerce Recurring	Cobro automático mensual/anual, gestión de estados
Franquicias (split)	Stripe Connect	Cobro a plataforma → transfer a franquicia − fee
Royalties	Commerce + Custom	Cálculo automático, acumulación, liquidación periódica

7.2 Modelo Stripe Connect para Franquicias
El modelo de franquicia digital requiere split payments automatizados. Stripe Connect con Express Accounts es la solución recomendada:
16.	Alumno compra curso (100€) en el portal del Consultor Franquiciado.
17.	Cobro en cuenta plataforma: Stripe cobra técnicamente a Jaraba Impact Platform.
18.	Split automático: Transferencia de 80€ (80%) a cuenta conectada del Consultor.
19.	Application Fee: Retención de 20€ (20%) como comisión de plataforma.
20.	Reporting fiscal: Stripe genera los documentos necesarios para ambas partes.
7.3 Automatización con ECA
Las reglas ECA (Event-Condition-Action) automatizan todo el ciclo financiero:
Regla ECA	Descripción
Cobro de Suscripción	Evento: cron diario | Si renewal_date == hoy → Crear order → Cobrar → Actualizar fecha → Email
Gestión de Impagos	Evento: pago fallido | Incrementar retry_count → Notificar → Si 3 fallos → Downgrade plan
Cálculo de Royalties	Evento: order.paid | Si tenant tiene referrer → Calcular % → Crear royalty_transaction
Liquidación Royalties	Evento: cron mensual | Si balance >= umbral → Crear payout → Marcar liquidado → Email
Trial Expiring	Evento: 3 días antes de fin trial → Email recordatorio con CTA upgrade
Onboarding Tenant	Evento: tenant.create | Crear grupo → Asignar domain → Clonar contenido base → Email welcome
 
8. Hoja de Ruta de Implementación
8.1 Fases del Proyecto
Fase	Período	Entregables
1	Mes 1-2	Cimientos: Drupal 11 + Group + Domain Access + Gin Theme. Entidades core (Vertical, Plan, Tenant). Cierre de brechas de auditoría AgroConecta.
2	Mes 3-4	Multi-Tenancy: Configuración avanzada de Group. Sistema de Theming 4 capas. Panel de admin visual. Migración de usuarios existentes a Grupos.
3	Mes 5-6	Comercio: Drupal Commerce + Stripe Connect. Suscripciones recurrentes. Sistema de royalties. Onboarding automatizado de tenants.
4	Mes 7-8	Verticales: Empleabilidad (JobOffer, Candidate, RecruiterAgent). Matching con Search API + Solr. Dashboards polimórficos por rol.
5	Mes 9+	Escalado: Expansión internacional (multi-idioma, multi-divisa). Nuevas verticales (FormaTech, TurismoLocal). API pública para terceros.

8.2 Métricas de Éxito
Métrica	Objetivo	Herramienta de Medición
Time to First Tenant	< 2 meses	Jira/Notion
Onboarding Time (nuevo tenant)	< 15 minutos	Analytics de flujo
Uptime SLA	99.9%	UptimeRobot/Pingdom
API Response Time (p95)	< 200ms	New Relic/Datadog
Churn Rate mensual	< 5%	Commerce analytics
NPS (Net Promoter Score)	> 50	Encuestas trimestrales
WCAG 2.1 AA Compliance	100%	Lighthouse/axe
 
9. Conclusiones y Próximos Pasos
9.1 Resumen de la Arquitectura Consolidada
La arquitectura consolidada para Jaraba Impact Platform sobre Drupal 11 ofrece:
•	Flexibilidad total mediante entidades de contenido configurables sin intervención técnica.
•	Escalabilidad horizontal con arquitectura multi-tenant híbrida (Group + Domain Access).
•	Automatización completa del ciclo de vida del cliente mediante ECA workflows.
•	Gestión financiera robusta con soporte para múltiples modelos de negocio (suscripciones, split payments, royalties).
•	Extensibilidad de verticales siguiendo el blueprint establecido por AgroConecta.
•	Cumplimiento normativo (RGPD, WCAG 2.1, PCI DSS) integrado desde el diseño.
•	IA integrada con sistema de agentes especializados y strict grounding para evitar alucinaciones.
9.2 Ventajas Competitivas
Ventaja	Descripción
Propiedad del código	Sin dependencia de SaaS externos ni vendor lock-in
Personalización ilimitada	Cada vertical puede tener funcionalidades únicas sin afectar al core
Control de datos	Todos los datos en infraestructura propia (EU/España)
Escalabilidad de costes	Sin cuotas por usuario que escalen exponencialmente
Efecto Red	Datos cruzados entre verticales (empresas ↔ talento)
Cumplimiento RETECH	"Solución Propia" con propiedad intelectual demostrable

9.3 Próximos Pasos Inmediatos
21.	Validar este documento con stakeholders técnicos y de negocio
22.	Configurar entorno de desarrollo con Lando + Drupal 11
23.	Implementar entidades core (Vertical, Plan, Tenant, Service)
24.	Configurar módulo Group y Domain Access con tipos de grupo definidos
25.	Configurar Drupal Commerce + Stripe Connect en modo test
26.	Migrar AgroConecta como primera vertical productiva
27.	Implementar sistema de theming de 4 capas con panel visual
28.	Establecer pipeline CI/CD y entornos staging/production

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Fin del Documento Técnico Maestro
Jaraba Impact Platform | Arquitectura SaaS Consolidada | v1.0
