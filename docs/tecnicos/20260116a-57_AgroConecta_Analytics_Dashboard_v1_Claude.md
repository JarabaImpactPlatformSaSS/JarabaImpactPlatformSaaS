ANALYTICS Y DASHBOARD
Métricas, KPIs, Reporting y Business Intelligence
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	57_AgroConecta_Analytics_Dashboard
Dependencias:	All AgroConecta modules, Chart.js, Matomo
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Analytics y Dashboard para AgroConecta, proporcionando visibilidad completa del rendimiento del marketplace mediante métricas de negocio, KPIs operativos, reportes automatizados y visualizaciones interactivas para la toma de decisiones data-driven.
1.1 Objetivos del Sistema
•	Visibilidad: Métricas en tiempo real del estado del marketplace
•	Decisiones: Datos accionables para optimizar operaciones
•	Alertas: Notificaciones proactivas de anomalías
•	Reporting: Informes automatizados para stakeholders
•	Compliance: Trazabilidad para auditorías y cumplimiento
•	Multi-tenant: Dashboards específicos por rol y tenant
1.2 Stack Tecnológico
Componente	Tecnología
Visualización	Chart.js 4.x para gráficos interactivos
Dashboards	Drupal Views + custom blocks + Alpine.js
Agregación	Cron jobs + materialized views para métricas precalculadas
Web Analytics	Matomo (self-hosted, GDPR compliant)
Time Series	TimescaleDB o MySQL partitioned tables
Exportación	CSV, Excel (PhpSpreadsheet), PDF (Entity Print)
Alertas	ECA + custom thresholds + email/push notifications
Caché	Redis para métricas frecuentes (TTL: 5-60 min)
1.3 Usuarios y Dashboards
Rol	Dashboard	Frecuencia
Super Admin	Vista global: todos los tenants, métricas plataforma	Diaria
Tenant Admin	Métricas de su marketplace específico	Diaria
Productor	Ventas propias, productos, reviews (ver doc 52)	Diaria
Operaciones	Logística, incidencias, tiempos de entrega	Tiempo real
Marketing	Conversión, campañas, promociones, tráfico	Semanal
Finanzas	Revenue, comisiones, payouts, reconciliación	Mensual
 
2. KPIs Principales del Marketplace
2.1 Métricas de Ventas
KPI	Fórmula / Cálculo	Benchmark	Período
GMV	Gross Merchandise Value: SUM(order_total)	+15% MoM	Mensual
Revenue	Ingresos plataforma: SUM(comisiones + fees)	+10% MoM	Mensual
AOV	Average Order Value: GMV / total_orders	> €45	Mensual
Orders	Número total de pedidos completados	+20% MoM	Diario
Take Rate	Revenue / GMV × 100	8-12%	Mensual
Items/Order	Total items / total orders	> 3	Mensual
2.2 Métricas de Usuarios
KPI	Fórmula / Cálculo	Benchmark	Período
MAU	Monthly Active Users (login o compra)	+10% MoM	Mensual
New Users	Registros nuevos en período	+15% MoM	Semanal
Conversion Rate	Compradores / Visitantes × 100	> 2.5%	Semanal
Repeat Rate	Clientes con >1 pedido / Total clientes	> 30%	Mensual
CLV	Customer Lifetime Value: AOV × Frequency × Lifespan	> €150	Trimestral
Churn Rate	Clientes sin compra en 90 días / Total	< 25%	Mensual
2.3 Métricas de Productores
KPI	Fórmula / Cálculo	Benchmark	Período
Active Producers	Productores con ≥1 venta en 30 días	> 80% del total	Mensual
Avg Rating	Media de valoraciones de productores	> 4.5	Mensual
Response Time	Tiempo medio confirmación pedido	< 4 horas	Semanal
Fulfillment Rate	Pedidos completados / Total pedidos	> 98%	Mensual
Products/Producer	Media de productos activos por productor	> 15	Mensual
Producer Churn	Productores sin actividad 60 días	< 10%	Mensual
2.4 Métricas Operativas
KPI	Fórmula / Cálculo	Benchmark	Período
Avg Delivery Time	Media días desde pedido hasta entrega	< 3 días	Semanal
On-Time Delivery	Entregas en fecha / Total entregas	> 95%	Semanal
Return Rate	Pedidos devueltos / Total pedidos	< 3%	Mensual
Incident Rate	Pedidos con incidencia / Total	< 5%	Semanal
Support Tickets	Tickets por cada 100 pedidos	< 8	Semanal
Resolution Time	Tiempo medio resolución incidencias	< 24 horas	Semanal
 
3. Dashboard Administrativo
Panel principal para administradores de la plataforma con visión global del marketplace.
3.1 Layout del Dashboard
┌─────────────────────────────────────────────────────────────────────────┐
│  📊 DASHBOARD AGROCONECTA          [Hoy ▼] [Comparar ▼] [Exportar]     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐    │
│  │ 💰 GMV Hoy   │ │ 📦 Pedidos   │ │ 👥 Usuarios  │ │ ⭐ Rating    │    │
│  │   €4,523     │ │    67        │ │    1,234     │ │   4.7        │    │
│  │   ▲ +12%     │ │   ▲ +8%      │ │   ▲ +15%     │ │   ▲ +0.2     │    │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘    │
│                                                                         │
│  ┌─────────────────────────────────────┐ ┌───────────────────────────┐  │
│  │ 📈 VENTAS ÚLTIMOS 30 DÍAS          │ │ 🥧 VENTAS POR CATEGORÍA   │  │
│  │                                     │ │                           │  │
│  │    ╭─╮                              │ │     ┌────┐                │  │
│  │   ╭╯ ╰╮   ╭─╮                      │ │   ╱ Aceites╲ 35%          │  │
│  │  ╭╯   ╰╮ ╭╯ ╰─╮   ╭╮              │ │  │  Vinos   │ 25%          │  │
│  │ ╭╯     ╰─╯    ╰╮ ╭╯╰╮             │ │  │  Quesos  │ 20%          │  │
│  │─╯              ╰─╯  ╰─            │ │   ╲ Otros  ╱ 20%          │  │
│  │ 1    5    10   15   20   25   30  │ │     └────┘                │  │
│  └─────────────────────────────────────┘ └───────────────────────────┘  │
│                                                                         │
│  ┌─────────────────────────────────┐ ┌─────────────────────────────────┐│
│  │ 🏆 TOP PRODUCTORES              │ │ 🔥 PRODUCTOS MÁS VENDIDOS       ││
│  │                                 │ │                                 ││
│  │ 1. Finca Los Olivos    €2,340  │ │ 1. AOVE Picual 500ml     145 ud ││
│  │ 2. Bodega La Sierra    €1,890  │ │ 2. Queso Manchego        98 ud  ││
│  │ 3. Quesería Artesana   €1,456  │ │ 3. Vino Reserva          87 ud  ││
│  │ 4. Apícola del Valle   €1,234  │ │ 4. Miel Romero           76 ud  ││
│  │ 5. Jamones Serranos    €1,123  │ │ 5. Jamón Ibérico         65 ud  ││
│  └─────────────────────────────────┘ └─────────────────────────────────┘│
│                                                                         │
│  ⚠️ ALERTAS: 3 pedidos pendientes >24h | 2 productos sin stock          │
└─────────────────────────────────────────────────────────────────────────┘
3.2 Widgets del Dashboard
Widget	Tipo Gráfico	Datos
KPI Cards	Número + % cambio + sparkline	GMV, pedidos, usuarios, rating
Ventas Timeline	Line chart (Chart.js)	Ventas diarias 30 días
Categorías	Doughnut chart	% ventas por categoría
Top Productores	Ranking table	Top 10 por ventas
Top Productos	Ranking table	Top 10 por unidades
Mapa Geográfico	Choropleth map	Ventas por provincia
Funnel Conversión	Funnel chart	Visita→Carrito→Checkout→Compra
Alertas	Alert list	Issues que requieren atención
 
4. Sistema de Reportes
Informes automatizados y bajo demanda para análisis detallado.
4.1 Reportes Disponibles
Reporte	Contenido	Frecuencia	Formato
Ventas Diario	GMV, pedidos, AOV, productos vendidos	Diario 8:00	Email + PDF
Performance Semanal	KPIs vs semana anterior, top/bottom	Lunes 9:00	Email + PDF
Productores Mensual	Ventas por productor, comisiones, ratings	1º del mes	PDF + Excel
Financiero Mensual	Revenue, comisiones, payouts, reconciliación	5º del mes	PDF + Excel
Inventario	Stock bajo, agotados, rotación	Semanal	Excel
Promociones	Uso cupones, ROI campañas, conversión	Fin campaña	PDF
Logística	Tiempos entrega, incidencias, carriers	Semanal	Excel
Clientes	Segmentación, CLV, churn, cohortes	Mensual	PDF + Excel
4.2 Generador de Reportes Personalizado
Interfaz para crear reportes ad-hoc:
1.	Seleccionar entidad: Pedidos, productos, clientes, productores
2.	Elegir campos: Columnas a incluir (drag & drop)
3.	Aplicar filtros: Fecha, categoría, productor, estado, etc.
4.	Agregaciones: SUM, AVG, COUNT, GROUP BY
5.	Ordenación: Por cualquier columna ASC/DESC
6.	Previsualizar: Ver primeras 100 filas
7.	Exportar: CSV, Excel, PDF
8.	Guardar: Como reporte personalizado reutilizable
9.	Programar: Envío automático periódico
 
5. Tracking y Eventos
Sistema de captura de eventos para análisis de comportamiento y funnel de conversión.
5.1 Eventos E-commerce
Evento	Datos Capturados	Trigger
page_view	URL, referrer, device, user_id (si logged)	Cada página
product_view	product_id, name, price, category, producer	Ficha producto
add_to_cart	product_id, quantity, price, variation	Click añadir
remove_from_cart	product_id, quantity	Click eliminar
begin_checkout	cart_value, item_count, coupon_applied	Inicio checkout
add_shipping_info	shipping_method, postal_code	Paso envío
add_payment_info	payment_method	Paso pago
purchase	order_id, value, items[], coupon, shipping	Compra OK
search	query, results_count, filters_applied	Búsqueda
apply_coupon	coupon_code, success, discount_value	Aplicar cupón
5.2 Funnel de Conversión
FUNNEL DE CONVERSIÓN - Últimos 30 días

┌──────────────────────────────────────────────────────┐ 100%
│              VISITANTES: 45,230                      │
└──────────────────────────────────────────────────────┘
                         ↓ 28%
      ┌────────────────────────────────────────┐ 28%
      │       VEN PRODUCTO: 12,664            │
      └────────────────────────────────────────┘
                         ↓ 35%
            ┌──────────────────────────────┐ 9.8%
            │   AÑADEN AL CARRITO: 4,432   │
            └──────────────────────────────┘
                         ↓ 52%
                ┌────────────────────────┐ 5.1%
                │  INICIAN CHECKOUT: 2,305│
                └────────────────────────┘
                         ↓ 65%
                    ┌────────────────┐ 3.3%
                    │ COMPRAN: 1,498 │
                    └────────────────┘

Tasa de conversión global: 3.3%
 
6. Sistema de Alertas
Notificaciones proactivas cuando se detectan anomalías o se superan umbrales.
6.1 Tipos de Alertas
Alerta	Condición	Severidad	Notifica
Pedido estancado	Pendiente confirmación > 4 horas	⚠️ Alta	Push + Email
Stock agotado	Producto popular con stock = 0	⚠️ Alta	Push + Email
Stock bajo	Stock < umbral definido	📋 Media	Email
Reseña negativa	Rating ≤ 2 estrellas	⚠️ Alta	Push + Email
Incidencia envío	Estado carrier = exception	🔴 Crítica	Push + SMS
Caída de ventas	Ventas hoy < 50% media 7 días	📋 Media	Email
Pico de tráfico	Visitas > 200% media horaria	ℹ️ Info	Dashboard
Error de pago	Tasa error pagos > 5% última hora	🔴 Crítica	Push + SMS
Productor inactivo	Sin actividad 14 días (y tiene stock)	📋 Media	Email
Abuso cupón	Cupón usado > 3x media esperada	⚠️ Alta	Email
6.2 Configuración de Alertas
Los administradores pueden personalizar:
•	Umbrales: Valores que disparan cada alerta
•	Canales: Email, push, SMS, Slack
•	Destinatarios: Quién recibe cada tipo de alerta
•	Horarios: No molestar fuera de horario (excepto críticas)
•	Cooldown: Evitar spam de alertas repetidas (ej: 1h)
•	Escalación: Si no se resuelve en X tiempo, escalar a superior
 
7. Modelo de Datos Analytics
7.1 Entidad: analytics_daily
Métricas agregadas diarias para consultas rápidas:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
date	DATE	Fecha de las métricas	NOT NULL, INDEX
tenant_id	INT	Tenant (multi-tenant)	FK tenant.id, INDEX
gmv	DECIMAL(12,2)	Gross Merchandise Value	DEFAULT 0
revenue	DECIMAL(12,2)	Ingresos plataforma	DEFAULT 0
orders_count	INT	Número de pedidos	DEFAULT 0
orders_completed	INT	Pedidos completados	DEFAULT 0
orders_cancelled	INT	Pedidos cancelados	DEFAULT 0
aov	DECIMAL(8,2)	Average Order Value	COMPUTED
items_sold	INT	Unidades vendidas	DEFAULT 0
new_users	INT	Nuevos registros	DEFAULT 0
active_users	INT	Usuarios activos	DEFAULT 0
new_producers	INT	Nuevos productores	DEFAULT 0
active_producers	INT	Productores con ventas	DEFAULT 0
page_views	INT	Páginas vistas	DEFAULT 0
sessions	INT	Sesiones únicas	DEFAULT 0
conversion_rate	DECIMAL(5,2)	Tasa de conversión %	COMPUTED
avg_delivery_days	DECIMAL(4,2)	Media días entrega	NULLABLE
reviews_count	INT	Nuevas reseñas	DEFAULT 0
avg_rating	DECIMAL(3,2)	Rating medio del día	NULLABLE
created	DATETIME	Fecha cálculo	NOT NULL, UTC
7.2 Entidad: analytics_event
Eventos individuales para análisis detallado:
Campo	Tipo	Descripción	Restricciones
id	BigSerial	ID interno	PRIMARY KEY
event_type	VARCHAR(50)	Tipo de evento	NOT NULL, INDEX
event_data	JSONB	Datos del evento	NOT NULL
user_id	INT	Usuario (si logged)	NULLABLE, INDEX
session_id	VARCHAR(64)	ID de sesión	NOT NULL, INDEX
device_type	VARCHAR(20)	desktop/mobile/tablet	NULLABLE
referrer	VARCHAR(255)	URL de origen	NULLABLE
utm_source	VARCHAR(50)	UTM source	NULLABLE
utm_medium	VARCHAR(50)	UTM medium	NULLABLE
utm_campaign	VARCHAR(100)	UTM campaign	NULLABLE
created	TIMESTAMP	Momento del evento	NOT NULL, INDEX
 
8. APIs de Analytics
8.1 Endpoints de Métricas
Método	Endpoint	Descripción
GET	/api/v1/analytics/dashboard	KPIs principales para dashboard
GET	/api/v1/analytics/sales	Métricas de ventas por período
GET	/api/v1/analytics/users	Métricas de usuarios
GET	/api/v1/analytics/producers	Métricas de productores
GET	/api/v1/analytics/products/top	Top productos por ventas
GET	/api/v1/analytics/categories	Ventas por categoría
GET	/api/v1/analytics/funnel	Datos de funnel de conversión
GET	/api/v1/analytics/geographic	Ventas por ubicación geográfica
8.2 Endpoints de Reportes
Método	Endpoint	Descripción
GET	/api/v1/reports	Listar reportes disponibles
POST	/api/v1/reports/generate	Generar reporte personalizado
GET	/api/v1/reports/{id}/download	Descargar reporte generado
POST	/api/v1/reports/{id}/schedule	Programar envío periódico
8.3 Endpoints de Alertas
Método	Endpoint	Descripción
GET	/api/v1/alerts	Listar alertas activas
POST	/api/v1/alerts/{id}/acknowledge	Marcar alerta como vista
POST	/api/v1/alerts/{id}/resolve	Marcar alerta como resuelta
GET	/api/v1/alerts/config	Obtener configuración de alertas
PATCH	/api/v1/alerts/config	Actualizar configuración
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Data model: analytics_daily, analytics_event. Cron de agregación diaria.	All modules
Sprint 2	Semana 3-4	Dashboard admin: KPI cards, gráfico ventas, top productos/productores.	Chart.js
Sprint 3	Semana 5-6	Tracking eventos e-commerce. Integración Matomo. Funnel de conversión.	Matomo
Sprint 4	Semana 7-8	Reportes automatizados: diario, semanal, mensual. Exportación CSV/PDF.	Sprint 2
Sprint 5	Semana 9-10	Sistema de alertas. Configuración umbrales. Notificaciones multi-canal.	Sprint 3
Sprint 6	Semana 11-12	Generador reportes personalizado. Dashboards por rol. QA y optimización.	Sprint 5
--- Fin del Documento ---
57_AgroConecta_Analytics_Dashboard_v1.docx | Jaraba Impact Platform | Enero 2026
