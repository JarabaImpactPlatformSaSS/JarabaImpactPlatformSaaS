
DOCUMENTO TÉCNICO MAESTRO

Jaraba Impact Platform
Framework SaaS Multi-Tenant para Comercio Digital de Impacto

Verticales: AgroConecta | ArteConecta | TurismoConecta | ...

Arquitectura Técnica Integrada
Drupal 11 + Commerce 3.x + Stripe Connect + Make.com

Versión 2.0 | Enero 2026
 
Tabla de Contenidos
Tabla de Contenidos	1
1. Resumen Ejecutivo y Filosofía	1
1.1 Pilares de la Solución	1
1.2 Evolución Arquitectónica: De Ecwid a Commerce Nativo	1
1.3 Visión del Ecosistema	1
2. Arquitectura del Sistema	1
2.1 Diagrama de Componentes	1
2.2 Stack Tecnológico	1
2.3 Modelo de Multi-Tenancy	1
3. Framework Visual: jaraba_theme	1
3.1 Sistema de Componentización Parametrizada	1
3.2 Variables CSS Configurables	1
3.3 Componentes Estrella	1
3.3.1 Header Inmersivo	1
3.3.2 User Dashboard	1
3.3.3 Visual Picker	1
3.3.4 Product Cards GEO-Optimizadas	1
4. Núcleo Funcional: jaraba_core	1
4.1 Trazabilidad 'Phy-gital'	1
4.2 Servicio de Stripe Connect	1
4.3 Webhooks y Eventos	1
5. E-Commerce y Sistema de Pagos	1
5.1 Drupal Commerce 3.x	1
5.2 Stripe Connect: Split Payments	1
5.2.1 Tipos de Cuenta	1
5.2.2 Flujo de Destination Charges	1
5.2.3 Comisiones por Plan	1
5.3 Flujo de Onboarding Financiero	1
6. Integraciones y Automatización	1
6.1 Make.com como Hub de Integración	1
6.2 Escenarios Make.com Predefinidos	1
6.2.1 Sync Productos → Marketplaces	1
6.2.2 Abandoned Cart Recovery	1
6.2.3 Nuevo Pedido → Notificaciones Multicanal	1
6.3 Comparativa de Canales	1
7. Inteligencia Artificial y Automatización	1
7.1 Sistema de Agentes IA	1
7.1.1 Producer Copilot	1
7.1.2 Consumer Copilot	1
7.2 Reglas de Negocio (ECA)	1
7.3 AI Interpolator: Generación de Contenido	1
8. Estrategia GEO (Generative Engine Optimization)	1
8.1 El Problema del Widget JavaScript	1
8.2 Arquitectura de Datos Semánticos	1
8.3 Técnica 'Answer Capsule'	1
8.4 Configuración de robots.txt para AI Crawlers	1
8.5 Checklist de Implementación GEO	1
Nivel Técnico	1
Nivel Contenido	1
Nivel Monitorización	1
9. Modelo de Negocio SaaS	1
9.1 Estructura de Planes	1
9.2 Comparativa de Costes: Ecwid vs Commerce	1
9.3 Análisis de Riesgos	1
10. Roadmap de Implementación	1
10.1 Fase 1: Núcleo GEO (Semanas 1-4)	1
10.2 Fase 2: Motor de Integración (Semanas 5-8)	1
10.3 Fase 3: Lanzamiento (Semanas 9-12)	1
11. Guía de Despliegue	1
11.1 Requisitos del Servidor	1
11.2 Servicios de Hosting Recomendados	1
11.3 Pasos de Instalación	1
12. Estado del Proyecto y Conclusiones	1
12.1 Estado Actual	1
12.2 Conclusión Estratégica	1
12.3 Próximos Pasos Inmediatos	1

 
1. Resumen Ejecutivo y Filosofía
Jaraba Impact Platform es un ecosistema SaaS multi-tenant diseñado para la transformación digital de PYMEs en sectores de impacto (agroalimentario, artesanía, turismo rural, etc.). La plataforma opera bajo tres pilares fundamentales que guían todas las decisiones arquitectónicas:
1.1 Pilares de la Solución
•	Filosofía 'Gourmet Digital': La tecnología debe ser invisible; el protagonismo recae en el storytelling, la calidad visual y la percepción de valor del producto. Cada interacción debe transmitir calidad y artesanía.
•	Metodología 'Sin Humo': Rechazo al bloatware. Código limpio, desarrollo sobre estándares abiertos (Drupal Core, Bootstrap 5 SASS), automatización real de procesos y decisiones basadas en datos, no en modas.
•	Arquitectura Componentizada: Un sistema modular donde cada pieza (Hero, Ficha, Dashboard, Checkout) es independiente, parametrizable y reutilizable entre verticales.
1.2 Evolución Arquitectónica: De Ecwid a Commerce Nativo
DECISIÓN ESTRATÉGICA v2.0
Tras un análisis exhaustivo desde 5 perspectivas profesionales (Arquitecto SaaS, Ingeniero Software, UX, Negocio, SEO), se ha determinado que la arquitectura óptima para un SaaS multi-tenant escalable es DRUPAL COMMERCE 3.x + STRIPE CONNECT + MAKE.COM, reemplazando la integración con Ecwid del MVP inicial.

Razones del cambio:
•	Multi-tenancy nativo: Commerce permite gestionar cientos de tiendas desde una única instalación
•	Costes predecibles: Elimina el coste por tenant de Ecwid ($19-99/mes cada uno)
•	Control de datos: Todos los datos permanecen en tu infraestructura
•	SEO/GEO superior: Renderizado server-side óptimo para motores de IA generativa
•	Split payments automático: Stripe Connect gestiona comisiones y pagos a tenants
•	Integración multicanal: Make.com conecta con Amazon, eBay, Facebook Shop y más
1.3 Visión del Ecosistema
La plataforma trasciende la venta de productos para convertirse en un 'Sistema Operativo de Negocio' que integra:
•	Vertical de Emprendimiento: Herramientas para que PYMEs vendan productos físicos y digitales
•	Vertical de Empleabilidad: Marketplace de talento digital con certificaciones verificables
•	Modelo de Franquicia: Licencias white-label para entidades (ONGs, Cámaras de Comercio) que quieran replicar el modelo
 
2. Arquitectura del Sistema
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────────┐
│                      JARABA IMPACT PLATFORM v2.0                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                     CAPA DE PRESENTACIÓN                        │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │   │
│  │  │ jaraba_theme│  │ Admin UI    │  │ Consumer/Producer Apps  │  │   │
│  │  │ (Twig+SCSS) │  │ (Gin Admin) │  │ (React/PWA opcional)    │  │   │
│  │  └─────────────┘  └─────────────┘  └─────────────────────────┘  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                     CAPA DE NEGOCIO (Drupal 11)                 │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │   │
│  │  │ Commerce 3.x│  │ Group Module│  │ jaraba_core             │  │   │
│  │  │ (E-commerce)│  │(Multi-tenant)│ │ (Business Logic)        │  │   │
│  │  └─────────────┘  └─────────────┘  └─────────────────────────┘  │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │   │
│  │  │ ECA Module  │  │ AI Interp.  │  │ Schema.org / Metatag    │  │   │
│  │  │(Automation) │  │ (Copilots)  │  │ (GEO Optimization)      │  │   │
│  │  └─────────────┘  └─────────────┘  └─────────────────────────┘  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                     CAPA DE INTEGRACIONES                       │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │   │
│  │  │   STRIPE    │  │   MAKE.COM  │  │   MARKETING STACK       │  │   │
│  │  │  CONNECT    │  │  (Hub Int.) │  │  (Brevo, HubSpot...)    │  │   │
│  │  └──────┬──────┘  └──────┬──────┘  └───────────┬─────────────┘  │   │
│  └─────────┼────────────────┼────────────────────┼─────────────────┘   │
│            │                │                    │                     │
│            ▼                ▼                    ▼                     │
│  ┌─────────────┐  ┌─────────────────────┐  ┌─────────────────────┐     │
│  │  Tenants    │  │    MARKETPLACES     │  │   EMAIL / CRM       │     │
│  │  (Pagos)    │  │ Amazon|eBay|Meta    │  │   Automation        │     │
│  └─────────────┘  └─────────────────────┘  └─────────────────────┘     │
└─────────────────────────────────────────────────────────────────────────┘
2.2 Stack Tecnológico
Capa	Componente	Tecnología	Justificación
Core	CMS/Framework	Drupal 11.x	LTS 2028, API-first, enterprise
Core	E-commerce	Commerce 3.1.x	Multi-store nativo, extensible
Core	Multi-tenancy	Group + Domain Access	Soft multi-tenancy eficiente
Core	Automatización	ECA Module	Workflows sin código, YAML
Pagos	Gateway	Commerce Stripe	Payment Element, 3DS, wallets
Pagos	Split	Stripe Connect	Comisiones automáticas
Integraciones	Hub	Make.com	1000+ apps, visual, escalable
SEO/GEO	Schema	Schema.org Metatag	JSON-LD nativo
IA	Copilots	AI Interpolator	OpenAI/Gemini/Claude API
Frontend	Tema	jaraba_theme	Twig + Bootstrap 5 SCSS
2.3 Modelo de Multi-Tenancy
La plataforma utiliza 'Soft Multi-Tenancy' mediante el módulo Group de Drupal, donde todos los tenants comparten una única instalación y base de datos, con aislamiento lógico estricto:
┌─────────────────────────────────────────────────────────────────┐
│                    BASE DE DATOS ÚNICA                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │   GROUP:    │  │   GROUP:    │  │   GROUP:    │   ...       │
│  │  Tenant A   │  │  Tenant B   │  │  Tenant C   │             │
│  │  ─────────  │  │  ─────────  │  │  ─────────  │             │
│  │  • Store    │  │  • Store    │  │  • Store    │             │
│  │  • Products │  │  • Products │  │  • Products │             │
│  │  • Orders   │  │  • Orders   │  │  • Orders   │             │
│  │  • Users    │  │  • Users    │  │  • Users    │             │
│  │  • Config   │  │  • Config   │  │  • Config   │             │
│  └─────────────┘  └─────────────┘  └─────────────┘             │
│                                                                 │
│  VENTAJAS:                                                      │
│  • Una actualización = todos los tenants actualizados          │
│  • Coste marginal por nuevo tenant ≈ 0                         │
│  • Visión global para analytics e IA                           │
│  • Gestión jerárquica (Franquicia → Sub-franquicias)           │
└─────────────────────────────────────────────────────────────────┘
 
3. Framework Visual: jaraba_theme
El tema es un desarrollo a medida basado en Stable9 y Bootstrap 5 (compilado, no CDN), con un sistema de personalización parametrizada que permite customización completa sin tocar código.
3.1 Sistema de Componentización Parametrizada
Para permitir que usuarios no técnicos personalicen completamente la apariencia, se implementa un sistema de 4 capas:
┌─────────────────────────────────────────────────────────────────┐
│  CAPA 1: SCSS (ADN)                                             │
│  ─────────────────                                              │
│  Variables por defecto en _variables.scss                       │
│  $primary: #FF8C42;                                             │
│  $font-family-base: 'Inter', sans-serif;                        │
├─────────────────────────────────────────────────────────────────┤
│  CAPA 2: PHP (Panel Admin)                                      │
│  ────────────────────────                                       │
│  jaraba_theme.theme expone selectores visuales                  │
│  Color pickers, radio buttons con miniaturas                    │
├─────────────────────────────────────────────────────────────────┤
│  CAPA 3: Config Entity (Almacenamiento Multi-Tenant)            │
│  ──────────────────────────────────────────────────             │
│  Cada tenant tiene su propia configuración guardada             │
│  Cascada: Plataforma → Vertical → Plan → Tenant                 │
├─────────────────────────────────────────────────────────────────┤
│  CAPA 4: CSS Runtime (El Puente)                                │
│  ────────────────────────────────                               │
│  hook_preprocess_html() inyecta CSS Custom Properties           │
│  --color-primario: #FF8C42;                                     │
│  Sobrescribe SCSS en tiempo real                                │
└─────────────────────────────────────────────────────────────────┘
3.2 Variables CSS Configurables
El sistema expone más de 45 variables CSS configurables sin código:
Categoría	Variables	Ejemplo
Identidad de Marca	primary, secondary, accent	--color-primary: #FF8C42
Tipografía	font-family, font-size-base, headings	--font-family-base: 'Inter'
Layout	container-width, sidebar-width	--container-max-width: 1200px
Componentes	card-radius, button-radius, shadow	--card-border-radius: 12px
Hero	hero-height, hero-overlay-opacity	--hero-min-height: 500px
Header	header-bg, header-position	--header-bg-color: transparent
3.3 Componentes Estrella
3.3.1 Header Inmersivo
Navegación transparente con position: absolute, lógica de superposición y cambio de color dinámico para integrarse con fotografías de producto a pantalla completa.
3.3.2 User Dashboard
Transformación de user.html.twig en un cuadro de mando completo para el productor con métricas de ventas, productos activos, pedidos pendientes y accesos directos a las funciones más usadas.
3.3.3 Visual Picker
Implementación en el admin de selectores gráficos (miniaturas interactivas) para elegir layouts de Cabecera, Producto y Landing sin escribir código.
3.3.4 Product Cards GEO-Optimizadas
Tarjetas de producto que incluyen 'Answer Capsules' en los primeros 150 caracteres, optimizadas para extracción por LLMs.
 
4. Núcleo Funcional: jaraba_core
Este módulo custom contiene la Propiedad Intelectual (IP) del proyecto y la lógica de negocio diferenciadora.
4.1 Trazabilidad 'Phy-gital'
Implementación de la lógica para conectar el mundo físico (lotes de producción) con el digital:
•	Generación de IDs: Servicio TrazabilidadService.php que crea identificadores únicos (LOTE-2025-XXXX) automáticamente al guardar un producto
•	Códigos QR: Controlador QrController.php usando endroid/qr-code para generar PNGs de alta resolución listos para etiquetas físicas
•	Página de Trazabilidad: Landing pública donde el consumidor escanea el QR y ve origen, productor, certificaciones y fecha de producción
4.2 Servicio de Stripe Connect
Clase JarabaStripeConnect.php que gestiona la integración financiera:
// Métodos principales del servicio

public function createConnectedAccount(string $email, string $country): string
  // Crea cuenta Express para nuevo tenant

public function createAccountLink(string $accountId, string $returnUrl): string
  // Genera URL de onboarding KYC

public function processPaymentWithSplit(
    int $amount,
    string $currency,
    string $tenantStripeId,
    int $platformFeePercent = 5
): PaymentIntent
  // Procesa pago con split automático plataforma/tenant
4.3 Webhooks y Eventos
Sistema de webhooks para comunicación con Make.com y servicios externos:
Evento	Trigger	Payload	Uso
product.created	Nuevo producto	Product entity JSON	Sync marketplaces
product.updated	Producto editado	Product entity JSON	Sync marketplaces
order.completed	Pedido pagado	Order + items JSON	Notificaciones, CRM
cart.abandoned	Carrito 2h sin completar	Cart + user JSON	Email recovery
user.registered	Nuevo tenant/productor	User entity JSON	Onboarding flow
 
5. E-Commerce y Sistema de Pagos
5.1 Drupal Commerce 3.x
Commerce 3.x es un framework e-commerce modular que convierte Drupal en una plataforma de comercio completa:
Módulo	Función	Configuración Jaraba
commerce_product	Gestión de catálogo	Tipos: Físico, Digital, Servicio
commerce_order	Gestión de pedidos	Workflows: Draft → Pending → Completed
commerce_cart	Carrito de compra	Abandonded cart tracking enabled
commerce_checkout	Proceso de pago	One-page checkout, guest enabled
commerce_payment	Pasarelas de pago	Stripe Payment Element
commerce_shipping	Envíos	Flat rate + Zonas por peso
commerce_stock	Inventario	Stock por variación
commerce_promotion	Promociones	Cupones, descuentos automáticos
5.2 Stripe Connect: Split Payments
Stripe Connect permite dividir automáticamente los pagos entre la plataforma y los tenants:
5.2.1 Tipos de Cuenta
Tipo	Control	Onboarding	Recomendación
Standard	Stripe gestiona todo	Stripe hosted	Vendedores independientes
Express	Compartido	Stripe hosted simplificado	✓ RECOMENDADO para Jaraba
Custom	Plataforma total	Tu propio flujo	Alto volumen / requisitos especiales
5.2.2 Flujo de Destination Charges
CLIENTE paga €100 por producto del Tenant A
         │
         ▼
┌─────────────────────────────────────────────────┐
│              STRIPE PROCESA PAGO                │
│  ┌─────────────────────────────────────────┐    │
│  │ Total: €100.00                          │    │
│  │ Stripe Fee: -€2.90 - €0.25 = -€3.15     │    │
│  │ Application Fee (5%): -€5.00 → JARABA   │    │
│  │ ─────────────────────────────────────── │    │
│  │ Neto Tenant A: €91.85                   │    │
│  └─────────────────────────────────────────┘    │
└─────────────────────────────────────────────────┘
         │
         ▼
  Automático: €91.85 → Cuenta bancaria Tenant A
             €5.00  → Cuenta Jaraba
5.2.3 Comisiones por Plan
Plan Jaraba	Cuota/mes	Comisión Venta	Tenant Recibe (de €100)*
Starter	€29	5%	€91.85
Growth	€59	3%	€93.85
Pro	€99	1.5%	€95.35
Enterprise	Custom	Negociable	Variable
*Después de Stripe fees (~3.15%)
5.3 Flujo de Onboarding Financiero
1.	Tenant se registra en Jaraba Impact Platform
2.	Sistema crea Group (multi-tenancy) + Store (Commerce)
3.	Sistema llama a Stripe API: createConnectedAccount()
4.	Stripe devuelve acct_XXXXX → Se guarda en field_stripe_account_id
5.	Sistema genera link de onboarding → Redirige al tenant
6.	Tenant completa KYC en formulario Stripe (identidad, banco)
7.	Stripe notifica via webhook que la cuenta está verificada
8.	Tenant puede empezar a vender con split automático
 
6. Integraciones y Automatización
6.1 Make.com como Hub de Integración
Make.com actúa como el 'sistema nervioso' que conecta Drupal con el ecosistema externo sin desarrollo custom:
┌─────────────────────────────────────────────────────────────────┐
│                      DRUPAL COMMERCE                            │
│                   (Single Source of Truth)                      │
└───────────────────────────┬─────────────────────────────────────┘
                            │ Webhooks                             
                            ▼                                      
┌─────────────────────────────────────────────────────────────────┐
│                         MAKE.COM                                │
│                    (Integration Hub)                            │
├─────────────────────────────────────────────────────────────────┤
│                            │                                    │
│    ┌───────────────────────┼───────────────────────┐            │
│    ▼                       ▼                       ▼            │
│ MARKETPLACES          SOCIAL COMMERCE         MARKETING         │
│ ────────────          ──────────────          ─────────         │
│ • Amazon SP-API       • Facebook Shop         • Brevo/Mailchimp │
│ • eBay API            • Instagram Shop        • HubSpot CRM     │
│ • Etsy API            • TikTok Shop           • WhatsApp Bus.   │
│ • Walmart API         • Pinterest             • Slack notif.    │
│ • Google Shopping     • Google Shopping       • Telegram Bot    │
└─────────────────────────────────────────────────────────────────┘
6.2 Escenarios Make.com Predefinidos
6.2.1 Sync Productos → Marketplaces
Canal	Trigger	Acción	Sincronización
Facebook/Instagram	Webhook: product.updated	Meta Catalog API	Bidireccional
Google Shopping	Scheduled: cada 6h	Feed XML upload	Drupal → Google
Amazon	Webhook: product.updated	SP-API Feeds	Bidireccional
eBay	Webhook: product.updated	Inventory API	Bidireccional
6.2.2 Abandoned Cart Recovery
TRIGGER: Scheduled (cada 1 hora)
    │
    ├── DRUPAL: GET /api/carts/abandoned
    │   (carritos > 2h sin completar, valor > €20)
    │
    ├── ITERATOR: Por cada carrito
    │
    ├── BREVO: Send transactional email
    │   Template: 'abandoned_cart_reminder'
    │   Variables: {user_name, items[], total, recovery_link}
    │
    └── DRUPAL: PATCH /api/carts/{id}/mark-reminded

Secuencia completa de recuperación:
•	T+2h: Email 'Olvidaste algo en tu carrito'
•	T+24h: Email con 5% descuento
•	T+72h: Email 'Última oportunidad'
Resultado típico: 10-15% de carritos recuperados
6.2.3 Nuevo Pedido → Notificaciones Multicanal
TRIGGER: Webhook order.completed
    │
    └── PARALLEL (ejecuta todos simultáneamente)
        ├── BREVO: Email confirmación al cliente
        ├── WHATSAPP: Notificación al vendedor
        ├── SLACK: Alerta canal #pedidos
        ├── HUBSPOT: Crear/actualizar deal
        └── DRUPAL: Actualizar stock
6.3 Comparativa de Canales
VENTAJA MAKE.COM
Make.com ofrece MÁS canales que Ecwid nativo, incluyendo Amazon y eBay que Ecwid NO soporta. Además permite lógica personalizada y sincronización bidireccional.

Canal	Ecwid	Make.com	Ventaja
Facebook Shop	✓ Nativo	✓ Meta Catalog API	Equivalente
Instagram	✓ Nativo	✓ Meta Catalog API	Equivalente
Google Shopping	✓ Nativo	✓ Merchant Center API	Equivalente
Amazon Seller	✗ No soportado	✓ SP-API nativo	Make.com MEJOR
eBay	✗ No soportado	✓ API nativo	Make.com MEJOR
Etsy	✗ No soportado	✓ API nativo	Make.com MEJOR
WhatsApp Business	✗ No soportado	✓ Cloud API	Make.com MEJOR
Custom integrations	✗ No posible	✓ HTTP/Webhooks	Make.com MEJOR
 
7. Inteligencia Artificial y Automatización
7.1 Sistema de Agentes IA
La plataforma integra IA no como añadido, sino como interfaz principal de interacción:
7.1.1 Producer Copilot
Asistente integrado en el dashboard del vendedor:
•	Genera descripciones de producto optimizadas para GEO a partir de imagen y notas básicas
•	Sugiere precios basados en análisis de mercado
•	Crea 'Answer Capsules' automáticamente
•	Responde dudas sobre uso de la plataforma
7.1.2 Consumer Copilot
Asistente de compras para el usuario final:
•	Búsqueda semántica: 'Busco ingredientes para cena vegana sin gluten'
•	Recomendaciones personalizadas basadas en historial
•	Respuestas a preguntas sobre productos en lenguaje natural
•	Consulta directa a la base de datos estructurada de Drupal

ANEXO A: KNOWLEDGE BASE AI-NATIVA
La arquitectura detallada de la Knowledge Base que alimenta los Copilots, incluyendo RAG Multi-Tenant con Qdrant, Grounding Estricto anti-alucinaciones, y Analytics de queries, está documentada en el Anexo A:
→ docs/tecnicos/20260110i-Anexo_A_Knowledge_Base_AI_Nativa_claude.md

7.2 Reglas de Negocio (ECA)
Implementadas mediante módulo ECA con archivos YAML exportables:
Regla	Trigger	Condición	Acción
Onboarding	Usuario registrado	Rol = Productor	Email bienvenida + guía
Control Calidad	Producto guardado	Sin imágenes	Notificar + no publicar
Stock bajo	Stock actualizado	Stock < umbral	Email alerta + dashboard
SEO Auto	Producto creado	Descripción existe	Generar metatags
Review Request	Pedido entregado + 7d	No ha dejado review	Email solicitando review
7.3 AI Interpolator: Generación de Contenido
Campo field_descripcion_gourmet que utiliza OpenAI/Gemini para generar copywriting persuasivo:
// Prompt template para generación de descripción

Eres un experto en copywriting para productos gourmet.
Genera una descripción persuasiva para:

Producto: {product.title}
Categoría: {product.category}
Origen: {product.origin}
Características: {product.features}

Requisitos:
1. Primeros 150 caracteres deben responder: ¿qué es y por qué es especial?
2. Incluir beneficios tangibles
3. Tono cálido y artesanal
4. Optimizado para búsqueda por voz
 
8. Estrategia GEO (Generative Engine Optimization)
CAMBIO DE PARADIGMA
El SEO tradicional ya no es suficiente. Con ChatGPT Search, Perplexity y Google AI Overviews, la visibilidad depende de que los LLMs puedan extraer y citar tu contenido. Y Combinator predice -25% de tráfico de búsqueda tradicional para 2026.
8.1 El Problema del Widget JavaScript
Las plataformas como Ecwid que renderizan contenido via JavaScript tienen una desventaja crítica:
IMPORTANTE
Los crawlers de LLMs (GPTBot, ClaudeBot, PerplexityBot) priorizan eficiencia y velocidad, favoreciendo HTML estático y semántico. Cuando encuentran un widget JS, a menudo ven un contenedor vacío. Drupal Commerce con Server-Side Rendering entrega el 100% del contenido en el primer byte de respuesta.
8.2 Arquitectura de Datos Semánticos
Drupal permite implementar Schema.org de manera profunda y extensible:
Schema Type	Ecwid	Drupal Commerce	Impacto GEO
Product	✓ Básico	✓ Completo + extensiones	Alto
Offer	✓ Básico	✓ Con condiciones	Alto
AggregateRating	✓	✓	Medio
Review	✓	✓ + ReviewAction	Medio
Organization	✗	✓	Alto
LocalBusiness	✗	✓	Alto para local
FAQPage	✗	✓	Muy Alto
HowTo	✗	✓	Alto
BreadcrumbList	✗	✓	Medio
8.3 Técnica 'Answer Capsule'
Los LLMs extraen mejor contenido cuando encuentran respuestas directas en los primeros 150-200 caracteres:
<!-- ESTRUCTURA ÓPTIMA PARA GEO -->

<h1>Aceite de Oliva Virgen Extra - Finca La Huerta</h1>

<!-- ANSWER CAPSULE: Respuesta directa en primeros 150 caracteres -->
<p class='answer-capsule'>
  El <strong>Aceite de Oliva Virgen Extra de Finca La Huerta</strong>
  es un aceite premium con <strong>acidez 0.2%</strong>, elaborado con
  aceitunas <strong>Picual de Jaén</strong>. Precio: <strong>€12.50</strong>.
</p>

<!-- Campo dedicado en Drupal: field_ai_summary -->
<!-- Se renderiza en código fuente pero puede ocultarse visualmente -->
8.4 Configuración de robots.txt para AI Crawlers
# robots.txt optimizado para GEO

User-agent: *
Allow: /
Disallow: /admin/
Disallow: /user/login

# PERMITIR AI Crawlers (CRÍTICO para GEO)
User-agent: GPTBot
Allow: /

User-agent: ChatGPT-User
Allow: /

User-agent: PerplexityBot
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: Google-Extended
Allow: /

Sitemap: https://jarabaimpact.com/sitemap.xml
8.5 Checklist de Implementación GEO
Nivel Técnico
✓	JSON-LD para Product, Offer, Review, Organization en todas las páginas
✓	Core Web Vitals: LCP < 1.5s, FID < 100ms, CLS < 0.1
✓	Server-Side Rendering (no widgets JS para contenido principal)
✓	robots.txt permite GPTBot, PerplexityBot, ClaudeBot
✓	Sitemap XML actualizado diariamente
Nivel Contenido
✓	Answer Capsule en primeros 150 caracteres de cada producto
✓	FAQ Schema dinámico en cada ficha de producto
✓	Datos verificables: precios, stock, características actualizados
✓	dateModified visible para señal de frescura
Nivel Monitorización
✓	Google Search Console: Rich Results sin errores
✓	Tracking de AI referrals en GA4 (filtro user-agent)
✓	Auditoría mensual de citaciones en ChatGPT/Perplexity
 
9. Modelo de Negocio SaaS
9.1 Estructura de Planes
Plan	Precio/mes	Productos	Comisión	Características
Starter	€29	50	5%	Tienda básica, 1 usuario, email support
Growth	€59	500	3%	+ Multicanal, 3 usuarios, chat support
Pro	€99	2,000	1.5%	+ API, 10 usuarios, priority support
Enterprise	€199+	Ilimitado	Negociable	+ White-label, SLA, dedicado
9.2 Comparativa de Costes: Ecwid vs Commerce
Análisis TCO (Total Cost of Ownership) a 3 años con 100 tenants:
Concepto	Drupal + Ecwid	Drupal Commerce	Diferencia
Desarrollo inicial	$15,000	$35,000	+$20,000
Ecwid subscriptions (100 x $19 x 12)	$22,800/año	$0	-$22,800/año
Make.com (Teams)	$0	$408/año	+$408/año
Hosting escalado	$8,000/año	$12,000/año	+$4,000/año
Mantenimiento	$8,000/año	$10,000/año	+$2,000/año
TOTAL AÑO 1	$53,800	$57,408	+$3,608
TOTAL AÑO 3	$91,400	$32,408	-$58,992
TOTAL 3 AÑOS ACUMULADO	$198,600	$122,224	-$76,376 (38%)

AHORRO A 3 AÑOS
La arquitectura Drupal Commerce ahorra un 38% en TCO a 3 años. Además, elimina el vendor lock-in y proporciona control total sobre los datos.
9.3 Análisis de Riesgos
Riesgo	Drupal + Ecwid	Drupal Commerce	Mitigación
Vendor lock-in	ALTO	BAJO	Open source, datos propios
Cambios pricing externo	ALTO	BAJO	Solo Make.com (pequeño)
Complejidad técnica	BAJO	MEDIO	Partner especializado
Time to market	BAJO (rápido)	MEDIO	MVP en 12 semanas
Escalabilidad costes	MALA (lineal)	BUENA	Multi-tenant eficiente
 
10. Roadmap de Implementación
10.1 Fase 1: Núcleo GEO (Semanas 1-4)
Objetivo: Establecer superioridad en indexación por motores de IA
Semana	Tareas	Entregables
1	Setup Drupal 11 + Commerce 3.x	Ambiente desarrollo
1	Configurar Group Module	Estructura multi-tenant
2	Implementar Schema.org completo	JSON-LD Product, Org, FAQ
2	Crear jaraba_theme base	Componentes GEO-ready
3	Answer Capsules en productos	Templates optimizados
3	Core Web Vitals optimization	LCP < 1.5s
4	Testing con crawlers IA	Verificar indexación
4	KPI: Citación en Perplexity	Productos piloto aparecen
10.2 Fase 2: Motor de Integración (Semanas 5-8)
Objetivo: Habilitar venta multicanal y pagos
Semana	Tareas	Entregables
5	Integrar Stripe Connect	Destination Charges funcional
5	Onboarding financiero	Flujo KYC automatizado
6	Make.com: Meta Catalog	Sync Facebook/Instagram
6	Make.com: Google Merchant	Feed XML automático
7	Make.com: Amazon SP-API	Sync bidireccional
7	Make.com: Email marketing	Abandoned cart + sequences
8	Testing multicanal	E2E testing completo
8	Documentación usuario	Guías de admin
10.3 Fase 3: Lanzamiento (Semanas 9-12)
Objetivo: Polish y lanzamiento con tenants piloto
Semana	Tareas	Entregables
9	Onboarding wizard tenants	Flujo guiado funcional
9	Dashboard métricas	KPIs en tiempo real
10	AI Copilot básico	Generación descripciones
10	Performance final	Load testing OK
11	Security audit	Pen testing completado
11	Soft launch beta	10 tenants piloto
12	Iteración feedback	Fixes prioritarios
12	Launch público	Go-live Plan Starter
 
11. Guía de Despliegue
11.1 Requisitos del Servidor
Componente	Mínimo	Recomendado
PHP	8.2+	8.3
MySQL/MariaDB	8.0+ / 10.6+	8.0 / 10.11
RAM	4GB	8GB+
Storage	50GB SSD	100GB+ NVMe
Composer	2.x	2.7+
Node.js (build)	18.x	20.x LTS
11.2 Servicios de Hosting Recomendados
Servicio	Tier	Precio/mes	Ventajas
Pantheon	Performance M	~$500	Drupal-optimized, CI/CD
Platform.sh	Medium	~$400	Multi-app, auto-scaling
Acquia	Cloud Professional	~$800	Enterprise support
AWS + Aegir	Custom	~$300	Control total, económico
11.3 Pasos de Instalación
9.	Clonar repositorio: git clone + composer install
10.	Compilar frontend: cd web/themes/jaraba_theme && npm install && npm run build
11.	Crear base de datos y configurar settings.php
12.	Instalar Drupal: drush site:install jaraba_profile
13.	Importar configuración: drush config:import
14.	Configurar Stripe Connect: /admin/config/services/stripe-connect
15.	Configurar Make.com webhooks: /admin/config/services/webhooks
16.	Crear primer tenant de prueba
17.	Verificar Rich Results en Google Search Console
18.	Go-live: Apuntar DNS + habilitar SSL
 
12. Estado del Proyecto y Conclusiones
12.1 Estado Actual
Componente	Estado	Notas
Arquitectura definida	✅ Completo	Documentado en este documento
jaraba_theme v1	✅ Completo	Componentización parametrizada
jaraba_core base	✅ Completo	Trazabilidad, QR, webhooks
Commerce integration	🟡 En progreso	Stripe Connect pendiente
Make.com scenarios	🟡 En progreso	Templates listos, conexión pendiente
GEO optimization	🟡 En progreso	Schema.org implementado
AI Copilots	🔴 Pendiente	Fase 3
Multi-tenant Group	🔴 Pendiente	Fase 1
12.2 Conclusión Estratégica
La evolución de Jaraba Impact Platform hacia una arquitectura basada en Drupal Commerce + Stripe Connect + Make.com representa una decisión estratégica fundamentada en:
•	Eficiencia económica: 38% de ahorro en TCO a 3 años
•	Escalabilidad real: Multi-tenancy sin costes lineales
•	Control total: Datos propios, sin vendor lock-in
•	Visibilidad IA: Arquitectura nativa para GEO
•	Flexibilidad: Integración con cualquier marketplace via Make.com

VISIÓN
Jaraba Impact Platform no es 'otra tienda online más', sino 'la primera plataforma de comercio diseñada para que la IA venda tus productos'. Mientras competidores quedan atrapados en limitaciones de JavaScript y ecosistemas cerrados, esta arquitectura habla el idioma nativo de las máquinas.
12.3 Próximos Pasos Inmediatos
19.	Aprobar arquitectura v2.0 y presupuesto de desarrollo
20.	Crear cuenta Stripe Connect en modo test
21.	Crear cuenta Make.com plan Teams
22.	Configurar ambiente de desarrollo Drupal 11 + Commerce
23.	Implementar primer escenario Make.com (Meta Catalog)
24.	Reclutar 5 tenants piloto para beta cerrada
25.	Iniciar Sprint 1 de la Fase 1 (Núcleo GEO)

— Fin del Documento Técnico Maestro v2.0 —

Jaraba Impact Platform | Enero 2026
