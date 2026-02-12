
ECOSISTEMA JARABA
SaaS Admin Center
Centro de Gestión Premium
Especificación Técnica para EDI Google Antigravity
Control Total • Datos en Tiempo Real • Decisiones Informadas
Versión 1.0 | Enero 2026
 
Índice de Contenidos
1. Resumen Ejecutivo
2. Arquitectura del Admin Center
3. Design System Premium
   3.1 Paleta de Colores
   3.2 Tipografía
   3.3 Componentes Base
   3.4 Iconografía y Gráficos
4. Módulo: Dashboard Ejecutivo
   4.1 KPIs Globales
   4.2 Widgets de Tendencia
   4.3 Alertas Activas
5. Módulo: Gestión de Tenants
   5.1 Lista de Tenants
   5.2 Detalle de Tenant
   5.3 Health Score
   5.4 Configuración por Tenant
6. Módulo: Gestión de Usuarios
   6.1 Directorio de Usuarios
   6.2 Roles y Permisos
   6.3 Actividad y Sesiones
7. Módulo: Centro Financiero (FOC Integration)
   7.1 Revenue Dashboard
   7.2 Métricas SaaS
   7.3 Stripe Connect Console
8. Módulo: Analytics & Reports
   8.1 Builder de Reportes
   8.2 Reportes Programados
   8.3 Exportación
9. Módulo: Sistema de Alertas
   9.1 Centro de Notificaciones
   9.2 Configuración de Alertas
   9.3 Playbooks Automatizados
10. Módulo: Configuración Global
11. Módulo: Logs & Auditoría
12. Especificaciones de Componentes UI
13. APIs del Admin Center
14. Roadmap de Implementación
 
1. Resumen Ejecutivo
1.1 Visión del Admin Center
El SaaS Admin Center es el centro neurálgico desde el cual el operador de la plataforma Jaraba Impact controla todos los aspectos del ecosistema multi-tenant. Este documento especifica una interfaz de administración de nivel enterprise con estándares de UX premium que posicionan el producto por encima de competidores como Salesforce Admin Console, HubSpot Settings, o Stripe Dashboard.
1.2 Principios de Diseño
Principio	Implementación
Glanceability	El estado del sistema es comprensible en menos de 5 segundos. KPIs críticos siempre visibles.
Progressive Disclosure	Información detallada disponible bajo demanda. No saturar con datos innecesarios.
Actionable Insights	Cada dato presentado tiene un contexto de acción. ¿Qué hacer con esta información?
Zero Friction	Acciones frecuentes a máximo 2 clics. Atajos de teclado para power users.
Real-time First	Datos actualizados en tiempo real vía WebSockets. Sin necesidad de refresh manual.
Dark Mode Native	Modo oscuro como ciudadano de primera clase. Muchos admins trabajan largas horas.
1.3 Usuarios del Admin Center
Rol	Responsabilidades	Módulos Principales	Frecuencia
Super Admin	Control total del SaaS	Todos	Diaria
Finance Admin	Revenue, billing, FOC	Financiero, Reports	Diaria
Ops Admin	Tenants, usuarios, soporte	Tenants, Users, Alerts	Diaria
Tech Admin	Configuración, logs, APIs	Config, Logs, APIs	Semanal
Viewer	Solo lectura, reportes	Dashboard, Reports	Ocasional
 
2. Arquitectura del Admin Center
2.1 Estructura de Navegación
Navegación lateral colapsable con iconos y etiquetas. Estructura jerárquica de dos niveles.
┌─────────────────────────────────────────────────────────────┐
│  🏠 Dashboard                                               │
│  ├── Overview                                               │
│  ├── KPIs                                                   │
│  └── Alerts                                                 │
│  🏢 Tenants                                                 │
│  ├── All Tenants                                            │
│  ├── By Vertical                                            │
│  └── Health Monitor                                         │
│  👥 Users                                                   │
│  ├── Directory                                              │
│  ├── Roles & Permissions                                    │
│  └── Sessions                                               │
│  💰 Finance                                                 │
│  ├── Revenue                                                │
│  ├── SaaS Metrics                                           │
│  ├── Stripe Console                                         │
│  └── Invoicing                                              │
│  📊 Analytics                                               │
│  ├── Report Builder                                         │
│  ├── Scheduled Reports                                      │
│  └── Exports                                                │
│  🔔 Alerts                                                  │
│  ├── Notification Center                                    │
│  ├── Alert Rules                                            │
│  └── Playbooks                                              │
│  ⚙️ Settings                                                │
│  ├── Global Config                                          │
│  ├── Integrations                                           │
│  ├── API Keys                                               │
│  └── Billing Plans                                          │
│  📋 Logs                                                    │
│  ├── Activity Log                                           │
│  ├── Audit Trail                                            │
│  └── Error Log                                              │
└─────────────────────────────────────────────────────────────┘
2.2 Layout Principal
Layout de tres columnas con sidebar colapsable:
•	Sidebar izquierdo (240px / 64px colapsado): Navegación principal
•	Contenido principal (fluido): Área de trabajo con scroll independiente
•	Panel derecho (320px, opcional): Detalles contextuales, quick actions
2.3 Componentes de Layout
Componente	Descripción	Comportamiento
TopBar	Barra superior fija	Breadcrumbs, search global, user menu, notifications
Sidebar	Navegación lateral	Colapsable, sticky, tooltips en modo mini
PageHeader	Título y acciones de página	Título, subtítulo, botones de acción primaria
ContentArea	Área principal scrollable	Padding consistente, max-width para legibilidad
ContextPanel	Panel lateral derecho	Slide-in, detalles de item seleccionado
CommandPalette	Acceso rápido Cmd+K	Búsqueda global, navegación, acciones
 
3. Design System Premium
3.1 Paleta de Colores
Sistema de colores semántico con soporte nativo para dark mode:
Token	Light Mode	Dark Mode	Uso
--color-bg-primary	#FFFFFF	#0F172A	Fondo principal de página
--color-bg-secondary	#F8FAFC	#1E293B	Fondo de cards, sidebar
--color-bg-tertiary	#F1F5F9	#334155	Hover states, badges
--color-text-primary	#0F172A	#F8FAFC	Texto principal
--color-text-secondary	#64748B	#94A3B8	Texto secundario, labels
--color-text-muted	#94A3B8	#64748B	Texto deshabilitado, hints
--color-border	#E2E8F0	#334155	Bordes, divisores
--color-accent	#3B82F6	#60A5FA	Acciones primarias, links
--color-success	#10B981	#34D399	Estados positivos, confirmaciones
--color-warning	#F59E0B	#FBBF24	Alertas, precaución
--color-danger	#EF4444	#F87171	Errores, acciones destructivas
--color-info	#0EA5E9	#38BDF8	Información, tooltips
3.2 Tipografía
Sistema tipográfico optimizado para interfaces de datos:
Elemento	Font Family	Size / Weight	Uso
Display	Inter	32px / 700	Títulos de página
Heading 1	Inter	24px / 600	Secciones principales
Heading 2	Inter	20px / 600	Subsecciones
Heading 3	Inter	16px / 600	Cards, grupos
Body	Inter	14px / 400	Texto general
Body Small	Inter	13px / 400	Labels, descripciones
Caption	Inter	12px / 400	Hints, metadata
Mono	JetBrains Mono	13px / 400	Código, IDs, valores numéricos
Data Large	Inter	28px / 700	KPIs, números grandes
Data Medium	Inter	20px / 600	Métricas secundarias
3.3 Componentes Base
Botones
Variante	Estilo	Uso	Ejemplo
Primary	Filled, accent color	Acción principal de página	Crear Tenant
Secondary	Outlined, border visible	Acciones secundarias	Exportar
Ghost	Sin borde, hover sutil	Acciones inline	Editar
Danger	Filled, danger color	Acciones destructivas	Eliminar
Icon	Solo icono, tooltip	Toolbars, acciones compactas	⋮ Menu
Cards
Cards con variantes para diferentes contextos:
•	Default: Borde sutil, sombra mínima
•	Elevated: Sombra más pronunciada para destacar
•	Interactive: Hover state con elevación
•	Stat Card: Para KPIs con icono, valor, trend
•	Alert Card: Bordes laterales de color semántico
Tables
Tablas de datos con funcionalidades enterprise:
•	Sorting: Click en headers para ordenar
•	Filtering: Filtros por columna y globales
•	Pagination: Server-side con page size configurable
•	Row Selection: Checkbox para acciones batch
•	Column Resizing: Drag para ajustar anchos
•	Row Expansion: Click para ver detalles inline
•	Sticky Headers: Headers fijos en scroll
3.4 Iconografía y Gráficos
Sistema de iconos: Lucide Icons (consistente con React ecosystem)
Librería de gráficos: Recharts / ECharts para visualizaciones complejas
•	Line Charts: Tendencias temporales
•	Area Charts: Volúmenes, stacked data
•	Bar Charts: Comparativas, rankings
•	Pie/Donut: Composición, distribución
•	Treemaps: Jerarquías, proporciones
•	Sparklines: Mini-tendencias inline
•	Gauges: Métricas vs objetivo
 
4. Módulo: Dashboard Ejecutivo
4.1 KPIs Globales
Fila superior de scorecards con métricas críticas:
KPI	Tipo	Comparación	Acción Click
MRR	Currency	vs mes anterior	→ Finance/Revenue
Active Tenants	Count	vs mes anterior	→ Tenants/All
Active Users	Count	vs mes anterior	→ Users/Directory
Net Revenue Retention	Percentage	vs benchmark (105%)	→ Finance/SaaS Metrics
Churn Rate	Percentage	vs benchmark (3%)	→ Tenants/Health
Open Alerts	Count	Severidad	→ Alerts/Center
Diseño de Scorecard:
┌────────────────────────────────────────┐
│  💰                                    │
│  Monthly Recurring Revenue             │
│                                        │
│  €47,350                     ↑ 12.5%   │
│  ───────────────────                   │
│  vs €42,100 last month                 │
│  [▁▂▃▄▅▆▇█] sparkline                  │
└────────────────────────────────────────┘
4.2 Widgets de Tendencia
Revenue Trend (12 meses)
Gráfico de área con layers: Ingresos, Costes, Beneficio Neto. Hover muestra breakdown por vertical.
Tenant Distribution
Donut chart con distribución por vertical. Centro muestra total. Click en segmento filtra datos.
Top 10 Tenants by Revenue
Bar chart horizontal con nombre, MRR, trend. Color indica health score.
Geographic Distribution
Mapa de España/Andalucía con heat map de concentración de tenants.
4.3 Alertas Activas
Widget de alertas con priorización:
┌────────────────────────────────────────┐
│  🔔 Active Alerts (3)                  │
├────────────────────────────────────────┤
│  🔴 CRITICAL                           │
│  Tenant 'Bodega Carmona' payment failed│
│  2 hours ago  [View] [Dismiss]         │
├────────────────────────────────────────┤
│  🟠 WARNING                            │
│  Churn risk detected: 5 tenants        │
│  Today  [View All] [Start Playbook]    │
├────────────────────────────────────────┤
│  🟡 INFO                               │
│  New tenant signup pending approval    │
│  3 hours ago  [Review] [Approve]       │
└────────────────────────────────────────┘
 
5. Módulo: Gestión de Tenants
5.1 Lista de Tenants
Vista principal con tabla de todos los tenants del ecosistema:
Columnas de la Tabla
Columna	Tipo	Sortable	Filterable
Tenant Name	Text + Avatar	Sí	Search
Vertical	Badge	Sí	Multi-select
Plan	Badge	Sí	Multi-select
MRR	Currency	Sí	Range
Users	Number	Sí	Range
Health Score	Progress + Color	Sí	Range
Status	Badge	Sí	Multi-select
Created	Date	Sí	Date range
Actions	Menu	No	No
Acciones de Tabla
•	View: Abrir detalle en panel lateral
•	Edit: Abrir modal de edición
•	Impersonate: Login como tenant (con audit log)
•	Suspend: Suspender temporalmente
•	Delete: Eliminar (con confirmación y retención)
Bulk Actions
•	Export Selected: CSV/Excel de tenants seleccionados
•	Send Notification: Enviar mensaje a tenants seleccionados
•	Change Plan: Cambio masivo de plan
•	Tag: Añadir/quitar tags
5.2 Detalle de Tenant
Vista 360º del tenant con tabs de información:
┌────────────────────────────────────────────────────────┐
│  🏢 Bodega Carmona S.L.                    [Edit]      │
│  AgroConecta • Plan Professional • Active              │
├────────────────────────────────────────────────────────┤
│  [Overview] [Users] [Billing] [Activity] [Settings]   │
├────────────────────────────────────────────────────────┤
│                                                        │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │
│  │ MRR      │ │ Users    │ │ Products │ │ Orders   │  │
│  │ €249     │ │ 5        │ │ 47       │ │ 234      │  │
│  │ ↑ 0%     │ │ ↑ 2      │ │ ↑ 12     │ │ ↑ 18%    │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘  │
│                                                        │
│  Health Score: ████████░░ 85%                          │
│  Churn Risk: Low                                       │
│  NPS: 72 (Promoter)                                    │
│                                                        │
│  Activity Timeline                                     │
│  ─────────────────────────────────────────────────     │
│  Today 09:15  Product 'AOVE Premium' updated           │
│  Today 08:30  New order #4521 received                 │
│  Yesterday    3 products added                         │
│                                                        │
└────────────────────────────────────────────────────────┘
5.3 Health Score
Sistema de puntuación de salud del tenant basado en múltiples factores:
Factor	Peso	Cálculo
Login Frequency	25%	Días activos últimos 30 días / 30
Feature Adoption	20%	Features usadas / Features disponibles en plan
Transaction Volume	20%	Transacciones este mes vs promedio histórico
Support Tickets	15%	Inverso: menos tickets = mejor score
Payment Health	10%	Pagos on-time / Total pagos
Growth Trend	10%	MoM growth en métricas clave
Thresholds:
•	🟢 80-100: Healthy - No action needed
•	🟡 60-79: Attention - Monitor closely
•	🟠 40-59: At Risk - Proactive outreach
•	🔴 0-39: Critical - Immediate intervention
5.4 Configuración por Tenant
Settings específicos que el admin puede ajustar por tenant:
•	Limits: Usuarios máximos, storage, API calls
•	Features: Feature flags específicos
•	Branding: Logo, colores (si aplica white-label)
•	Integrations: APIs habilitadas, webhooks
•	Billing: Override de precios, descuentos
 
6. Módulo: Gestión de Usuarios
6.1 Directorio de Usuarios
Lista global de todos los usuarios del ecosistema con filtros avanzados:
Filtros Disponibles
•	Tenant: Filtrar por tenant específico o 'All tenants'
•	Role: admin, user, viewer, etc.
•	Status: active, invited, suspended, deleted
•	Last Active: Rango de fechas
•	Created: Rango de fechas
•	Vertical: AgroConecta, ComercioConecta, etc.
Columnas de la Tabla
Columna	Contenido	Notas
User	Avatar + Nombre + Email	Avatar generado si no hay foto
Tenant	Nombre del tenant	Link al detalle de tenant
Role	Badge con color	Admin=purple, User=blue, Viewer=gray
Status	Badge	Active=green, Invited=yellow, Suspended=red
Last Active	Relative time	'2 hours ago', 'Yesterday', etc.
Sessions	Number	Sesiones activas actuales
Actions	Dropdown menu	View, Edit, Impersonate, Suspend, Delete
6.2 Roles y Permisos
Gestión de roles con matriz de permisos granular:
Roles del Sistema
Rol	Descripción	Scope
Platform Super Admin	Control total del SaaS	Global
Platform Admin	Gestión operativa sin acceso a config crítica	Global
Tenant Admin	Admin del tenant específico	Tenant
Tenant User	Usuario estándar del tenant	Tenant
Tenant Viewer	Solo lectura dentro del tenant	Tenant
Matriz de Permisos (ejemplo)
Permission              Super  Admin  T-Admin  T-User  Viewer
─────────────────────────────────────────────────────────────
tenants.create           ✓      ✓       -        -       -
tenants.delete           ✓      -       -        -       -
tenants.view_all         ✓      ✓       -        -       -
tenants.view_own         ✓      ✓       ✓        ✓       ✓
users.create_global      ✓      ✓       -        -       -
users.create_tenant      ✓      ✓       ✓        -       -
billing.view             ✓      ✓       ✓        -       -
billing.manage           ✓      ✓       -        -       -
settings.global          ✓      -       -        -       -
logs.audit               ✓      ✓       -        -       -
6.3 Actividad y Sesiones
Monitorización de sesiones activas y actividad de usuarios:
Active Sessions Panel
•	Lista de sesiones activas con: User, IP, Device, Location, Duration
•	Acción: Force logout de sesión individual o todas las sesiones de un usuario
•	Alerta: Sesiones desde ubicaciones inusuales
Activity Log por Usuario
•	Timeline de acciones: login, logout, cambios de configuración, acciones de negocio
•	Filtrable por tipo de acción, rango de fechas
•	Exportable para auditoría
 
7. Módulo: Centro Financiero (FOC Integration)
Integración completa con el Financial Operations Center documentado en FOC v2.0
7.1 Revenue Dashboard
Métricas Principales
Métrica	Visualización	Drill-down
MRR	Scorecard + Sparkline 12m	Por vertical, por plan, por tenant
ARR	Scorecard con proyección	Breakdown anualizado
New MRR	Bar chart mensual	Nuevos tenants este mes
Expansion MRR	Bar chart mensual	Upgrades, add-ons
Churned MRR	Bar chart mensual (negativo)	Tenants perdidos
Net New MRR	Waterfall chart	New + Expansion - Churn
Revenue by Vertical
Treemap interactivo mostrando distribución de revenue por vertical. Click para drill-down.
Revenue Cohort Analysis
Heatmap de retención de revenue por cohorte mensual de adquisición.
7.2 Métricas SaaS
Métrica	Valor Actual	Benchmark	Status
Gross Revenue Retention	[Calculado]	> 90%	🟢 On Track / 🟡 / 🔴
Net Revenue Retention	[Calculado]	> 105%	Estado visual
Logo Churn Rate	[Calculado]	< 5%	Estado visual
Revenue Churn Rate	[Calculado]	< 3%	Estado visual
CAC	[Calculado]	Contextual	Estado visual
LTV	[Calculado]	Contextual	Estado visual
LTV:CAC Ratio	[Calculado]	> 3:1	Estado visual
CAC Payback (months)	[Calculado]	< 12	Estado visual
ARPU	[Calculado]	Trend ↑	Estado visual
7.3 Stripe Connect Console
Panel de gestión de Stripe Connect integrado:
Connected Accounts
•	Lista de cuentas Stripe Express conectadas
•	Status: pending, active, restricted
•	Actions: View in Stripe, Send onboarding link, Disconnect
Transactions Monitor
•	Feed de transacciones recientes vía webhooks
•	Filtros: tipo, estado, tenant, monto, fecha
•	Detalle: charge ID, application fee, net amount
Payouts Dashboard
•	Calendario de payouts programados
•	Balance disponible por cuenta
•	Alertas de payouts fallidos
 
8. Módulo: Analytics & Reports
8.1 Report Builder
Constructor visual de reportes con drag-and-drop:
Componentes del Builder
•	Data Sources: Tenants, Users, Transactions, Products, Orders
•	Dimensions: Tiempo, Vertical, Plan, Region, Custom fields
•	Metrics: Conteos, sumas, promedios, porcentajes
•	Filters: Condiciones múltiples con AND/OR
•	Visualizations: Table, Line, Bar, Pie, Area, Pivot
Templates Predefinidos
Template	Contenido
Monthly Business Review	KPIs, revenue trend, churn analysis, top tenants
Cohort Analysis	Retention por cohorte, LTV por cohorte
Vertical Performance	Comparativa de métricas entre verticales
User Engagement	DAU/MAU, feature usage, activity patterns
Revenue Forecast	Proyección 12 meses con escenarios
Churn Analysis	Razones de churn, señales predictivas, recovery rate
8.2 Reportes Programados
Sistema de scheduling para envío automático de reportes:
Configuración
•	Frecuencia: Diario, Semanal, Mensual, Trimestral
•	Día/Hora: Selección específica
•	Destinatarios: Lista de emails, roles
•	Formato: PDF, Excel, CSV
•	Opciones: Incluir comentarios, comparativa con periodo anterior
8.3 Exportación
Opciones de exportación desde cualquier vista de datos:
•	CSV: Datos raw para análisis externo
•	Excel: Formateo básico, múltiples hojas
•	PDF: Reporte formateado para presentación
•	API: Endpoint para integración con BI tools
 
9. Módulo: Sistema de Alertas
9.1 Centro de Notificaciones
Hub centralizado de todas las alertas y notificaciones:
Categorías de Alertas
Categoría	Icono	Ejemplos	Severidad típica
Financial	💰	Payment failed, churn risk, revenue drop	Critical / Warning
Operational	⚙️	Tenant limit reached, storage full	Warning
Security	🔒	Suspicious login, failed attempts	Critical
System	🖥️	API errors, webhook failures	Warning / Info
Business	📈	New signup, milestone reached	Info
Estados de Alerta
•	New: Recién creada, no vista
•	Seen: Vista pero no procesada
•	In Progress: Siendo atendida
•	Resolved: Resuelta
•	Dismissed: Descartada sin acción
9.2 Configuración de Alertas
Editor visual de reglas de alerta:
┌────────────────────────────────────────────────────────┐
│  Create Alert Rule                                     │
├────────────────────────────────────────────────────────┤
│  Name: [Churn Risk Detection                        ]  │
│                                                        │
│  WHEN                                                  │
│  ┌─────────────────────────────────────────────────┐   │
│  │ Metric: [Health Score        ▼]                 │   │
│  │ Condition: [drops below      ▼]                 │   │
│  │ Value: [60                    ]                 │   │
│  │ Time window: [7 days         ▼]                 │   │
│  └─────────────────────────────────────────────────┘   │
│                                                        │
│  THEN                                                  │
│  ┌─────────────────────────────────────────────────┐   │
│  │ ☑ Create notification (Severity: [Warning ▼])   │   │
│  │ ☑ Send email to: [ops-team@jaraba.com        ]  │   │
│  │ ☑ Send Slack to: [#alerts                    ]  │   │
│  │ ☐ Trigger playbook: [                       ▼]  │   │
│  └─────────────────────────────────────────────────┘   │
│                                                        │
│  [Cancel]                              [Save Rule]     │
└────────────────────────────────────────────────────────┘
9.3 Playbooks Automatizados
Secuencias de acciones automáticas triggeradas por alertas:
Playbook: Churn Prevention
1.	Identificar tenants con Health Score < 60
2.	Crear ticket en sistema de soporte
3.	Enrollar en secuencia de email de reactivación
4.	Agendar llamada de CS si no hay respuesta en 48h
5.	Ofrecer descuento de retención si aplica
Playbook: Payment Recovery
6.	Pago fallido detectado via Stripe webhook
7.	Enviar email de notificación al tenant admin
8.	Reintentar cobro automático en 24h
9.	Si falla de nuevo, enviar link de actualización de pago
10.	Tras 7 días sin resolución, aplicar grace period y notificar
 
10. Módulo: Configuración Global
10.1 General Settings
•	Platform Name: Nombre mostrado en la plataforma
•	Logo: Logo principal y favicon
•	Primary Domain: Dominio principal del SaaS
•	Support Email: Email de soporte visible
•	Default Language: Idioma por defecto
•	Timezone: Zona horaria para reportes
10.2 Billing Plans
Gestión de planes de suscripción:
Campo	Tipo	Ejemplo	Notas
Plan Name	Text	Professional	Nombre visible
Price Monthly	Currency	€99	Precio mensual
Price Annual	Currency	€990	Precio anual (descuento)
User Limit	Number	10	Usuarios incluidos
Storage	GB	50	Almacenamiento incluido
Features	Multi-select	[Lista]	Features habilitadas
Stripe Price ID	Text	price_xxx	ID de Stripe para billing
10.3 Integrations
Gestión de integraciones externas:
•	Stripe: API keys, webhook secret, connected account settings
•	Email (SMTP/SES): Configuración de envío de emails
•	Slack: Webhook URL para notificaciones
•	Analytics: Google Analytics, Mixpanel, etc.
•	Storage: AWS S3, Google Cloud Storage
•	AI: OpenAI/Anthropic API keys para features IA
10.4 API Keys
Gestión de API keys para integraciones programáticas:
•	Create: Generar nueva API key con scope y expiración
•	List: Ver todas las keys activas
•	Revoke: Revocar key específica
•	Audit: Log de uso de cada API key
 
11. Módulo: Logs & Auditoría
11.1 Activity Log
Log cronológico de todas las acciones en la plataforma:
Campos del Log
Campo	Tipo	Descripción
Timestamp	DateTime	Momento exacto con timezone
Actor	User reference	Usuario que realizó la acción (o 'system')
Action	Enum	create, update, delete, login, logout, etc.
Resource Type	String	tenant, user, product, order, etc.
Resource ID	UUID	ID del recurso afectado
Changes	JSON	Diff de cambios (before/after)
IP Address	String	IP del cliente
User Agent	String	Browser/device info
11.2 Audit Trail
Log inmutable para compliance y auditoría legal:
•	Append-only: Registros no editables ni eliminables
•	Cryptographic hash: Cada entrada firmada para integridad
•	Retention: Mínimo 7 años según regulación
•	Export: Formato estándar para auditorías externas
Eventos Auditados
•	Authentication: Login, logout, password change, 2FA events
•	Authorization: Permission changes, role assignments
•	Data Access: Acceso a datos sensibles (PII, financieros)
•	Data Modification: Cambios en datos críticos
•	Configuration: Cambios en settings del sistema
11.3 Error Log
Log de errores del sistema para debugging:
•	Severity: debug, info, warning, error, critical
•	Stack trace: Para errores de código
•	Context: Request info, user context
•	Aggregation: Grouping de errores similares
•	Alerting: Notificación automática para errores críticos
 
12. Especificaciones de Componentes UI
12.1 Scorecard Component
interface ScorecardProps {
  title: string;              // 'Monthly Recurring Revenue'
  value: string | number;     // '€47,350'
  change?: {
    value: number;            // 12.5
    direction: 'up' | 'down'; // 'up'
    period: string;           // 'vs last month'
  };
  icon?: ReactNode;           // <DollarSign />
  sparkline?: number[];       // [100, 120, 115, 140, 160, 175]
  status?: 'success' | 'warning' | 'danger' | 'neutral';
  onClick?: () => void;       // Navigation handler
}
12.2 DataTable Component
interface DataTableProps<T> {
  data: T[];
  columns: ColumnDef<T>[];
  pagination?: {
    page: number;
    pageSize: number;
    total: number;
    onPageChange: (page: number) => void;
  };
  sorting?: {
    column: string;
    direction: 'asc' | 'desc';
    onSort: (column: string) => void;
  };
  filtering?: {
    filters: FilterDef[];
    onFilter: (filters: FilterValue[]) => void;
  };
  selection?: {
    selected: string[];
    onSelect: (ids: string[]) => void;
  };
  actions?: {
    row: ActionDef[];        // Actions per row
    bulk: ActionDef[];       // Bulk actions
  };
  loading?: boolean;
  emptyState?: ReactNode;
}
12.3 AlertCard Component
interface AlertCardProps {
  severity: 'critical' | 'warning' | 'info';
  title: string;
  description: string;
  timestamp: Date;
  category: 'financial' | 'operational' | 'security' | 'system';
  actions?: {
    label: string;
    onClick: () => void;
    variant: 'primary' | 'secondary' | 'ghost';
  }[];
  onDismiss?: () => void;
}
12.4 CommandPalette Component
// Triggered by Cmd+K / Ctrl+K
interface CommandPaletteProps {
  isOpen: boolean;
  onClose: () => void;
  commands: {
    navigation: Command[];   // Go to pages
    actions: Command[];      // Quick actions
    search: Command[];       // Search results
  };
}

interface Command {
  id: string;
  label: string;
  icon?: ReactNode;
  shortcut?: string;        // 'Cmd+N'
  onSelect: () => void;
}
 
13. APIs del Admin Center
13.1 Dashboard APIs
Method	Endpoint	Descripción
GET	/api/admin/dashboard/kpis	KPIs globales con comparativas
GET	/api/admin/dashboard/revenue-trend	Serie temporal de revenue
GET	/api/admin/dashboard/alerts/active	Alertas activas
GET	/api/admin/dashboard/tenants/top	Top tenants por revenue
13.2 Tenants APIs
Method	Endpoint	Descripción
GET	/api/admin/tenants	Lista paginada de tenants
GET	/api/admin/tenants/{id}	Detalle de tenant
POST	/api/admin/tenants	Crear tenant
PATCH	/api/admin/tenants/{id}	Actualizar tenant
DELETE	/api/admin/tenants/{id}	Eliminar tenant (soft)
GET	/api/admin/tenants/{id}/health	Health score detallado
GET	/api/admin/tenants/{id}/activity	Activity log del tenant
POST	/api/admin/tenants/{id}/impersonate	Generar token de impersonation
13.3 Users APIs
Method	Endpoint	Descripción
GET	/api/admin/users	Lista global de usuarios
GET	/api/admin/users/{id}	Detalle de usuario
PATCH	/api/admin/users/{id}	Actualizar usuario
POST	/api/admin/users/{id}/suspend	Suspender usuario
DELETE	/api/admin/users/{id}/sessions	Force logout all sessions
GET	/api/admin/roles	Lista de roles
GET	/api/admin/permissions	Matriz de permisos
13.4 WebSocket Events
Eventos en tiempo real via WebSocket:
// Connection
ws://admin.jaraba.io/ws?token={jwt}

// Events
{ event: 'kpi.updated', data: { metric: 'mrr', value: 47350 } }
{ event: 'alert.created', data: { id: 'xxx', severity: 'critical' } }
{ event: 'tenant.status_changed', data: { id: 'xxx', status: 'active' } }
{ event: 'transaction.completed', data: { ... } }
 
14. Roadmap de Implementación
14.1 Fases de Desarrollo
Sprint	Entregables	Módulos	Horas
1-2	Design System, Layout, Navegación	Core UI	60-80h
3-4	Dashboard ejecutivo, KPIs, Widgets	Dashboard	60-80h
5-6	Lista tenants, Detalle, Health Score	Tenants	60-80h
7-8	Directorio usuarios, Roles, Sesiones	Users	50-70h
9-10	Revenue dashboard, Métricas SaaS, Stripe	Finance	70-90h
11-12	Report Builder, Scheduled Reports	Analytics	60-80h
13-14	Centro alertas, Reglas, Playbooks	Alerts	50-70h
15-16	Settings, Integrations, API Keys	Settings	40-60h
17-18	Activity Log, Audit Trail, Error Log	Logs	40-50h
19-20	WebSockets, Real-time, Polish, QA	Final	50-70h
Total estimado: 540-730 horas (20 sprints, ~10 meses)
14.2 Dependencias Técnicas
•	React 18+ con TypeScript
•	Tailwind CSS para styling
•	Tanstack Table para data tables
•	Recharts / ECharts para visualizaciones
•	React Query para data fetching
•	WebSocket para real-time updates
•	Drupal 11 backend con APIs REST
14.3 Criterios de Aceptación
•	Responsive: Funcional desde 1024px hasta 4K
•	Performance: First Contentful Paint < 1.5s
•	Accesibilidad: WCAG 2.1 AA compliant
•	Dark Mode: Soporte completo sin degradación
•	Real-time: Updates sin refresh manual
•	Browser Support: Chrome, Firefox, Safari, Edge (últimas 2 versiones)

— Fin del Documento —
Ecosistema Jaraba | SaaS Admin Center Premium v1.0
