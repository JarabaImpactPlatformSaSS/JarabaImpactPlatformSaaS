PORTAL DEL COMERCIANTE
Dashboard, Gestión y Analytics para Comercios
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	74_ComercioConecta_Merchant_Portal
Dependencias:	Todos los documentos anteriores ComercioConecta
Base:	Nuevo (específico ComercioConecta)
 
1. Resumen Ejecutivo
Este documento especifica el Portal del Comerciante para ComercioConecta. El portal proporciona una interfaz unificada donde los comercios pueden gestionar su tienda online, pedidos, catálogo, promociones, reseñas y analizar el rendimiento de su negocio.
1.1 Objetivos del Portal
• Centralizar la gestión del comercio en una única interfaz
• Simplificar operaciones diarias (pedidos, stock, envíos)
• Proporcionar insights accionables con analytics
• Minimizar tiempo de gestión con automatizaciones
• Acceso móvil responsive para gestión sobre la marcha
• Integración con POS y sistemas externos
1.2 Módulos del Portal
Módulo	Descripción	Prioridad
Dashboard	Vista general, KPIs, alertas, acciones rápidas	Crítica
Pedidos	Gestión de pedidos, fulfillment, envíos	Crítica
Catálogo	Productos, variaciones, stock, precios	Crítica
Clientes	Base de datos, segmentación, historial	Alta
Promociones	Cupones, ofertas, Flash Offers	Alta
Reseñas	Moderación, respuestas, Q&A	Alta
Analytics	Ventas, tráfico, conversión, productos	Alta
Configuración	Tienda, envíos, pagos, equipo	Media
Comunicaciones	Mensajes, notificaciones, email	Media
Integraciones	POS, carriers, Google Business	Media
1.3 Roles y Permisos
Rol	Descripción	Permisos Clave
Owner	Propietario del comercio	Todo acceso, facturación, eliminar tienda
Admin	Administrador completo	Todo excepto facturación y eliminar
Manager	Gestor de operaciones	Pedidos, stock, promociones, reseñas
Staff	Personal de tienda	Ver pedidos, actualizar stock, C&C
Viewer	Solo lectura	Ver dashboard y analytics
 
2. Arquitectura del Portal
2.1 Diagrama de Módulos
┌─────────────────────────────────────────────────────────────────────┐ │                      MERCHANT PORTAL                                │ ├─────────────────────────────────────────────────────────────────────┤ │                                                                     │ │  ┌─────────────────────────────────────────────────────────────┐   │ │  │                      DASHBOARD                               │   │ │  │  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────────────┐   │   │ │  │  │  KPIs   │ │ Alerts  │ │ Quick   │ │   Activity      │   │   │ │  │  │  Today  │ │  Panel  │ │ Actions │ │   Feed          │   │   │ │  │  └─────────┘ └─────────┘ └─────────┘ └─────────────────┘   │   │ │  └─────────────────────────────────────────────────────────────┘   │ │                                                                     │ │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────┐   │ │  │  Orders  │ │ Catalog  │ │Customers │ │ Promos   │ │Reviews │   │ │  │  Module  │ │  Module  │ │  Module  │ │  Module  │ │ Module │   │ │  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └────────┘   │ │                                                                     │ │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────────────┐   │ │  │Analytics │ │ Settings │ │Messages  │ │    Integrations      │   │ │  │  Module  │ │  Module  │ │  Module  │ │      Module          │   │ │  └──────────┘ └──────────┘ └──────────┘ └──────────────────────┘   │ │                                                                     │ └─────────────────────────────────────────────────────────────────────┘                               │                     ┌─────────┴─────────┐                     │   Merchant API    │                     │   /api/v1/merchant│                     └───────────────────┘
2.2 Stack Tecnológico Frontend
Componente	Tecnología	Justificación
Framework	React 18+	Componentes reutilizables, ecosystem
State Management	React Query + Zustand	Server state + client state
Routing	React Router v6	Nested routes, lazy loading
UI Components	Tailwind + Headless UI	Customizable, accesible
Charts	Recharts	React-native, responsive
Tables	TanStack Table	Sorting, filtering, pagination
Forms	React Hook Form + Zod	Validación, performance
Notifications	React Hot Toast	Toast notifications
Date Handling	date-fns	Lightweight, tree-shakeable
 
3. Dashboard Principal
3.1 KPIs en Tiempo Real
KPI	Descripción	Comparación	Visualización
Ventas Hoy	Total facturado hoy	vs. ayer, vs. semana pasada	Número grande + trend
Pedidos Hoy	Pedidos recibidos	vs. ayer	Número + mini chart
Ticket Medio	AOV del día	vs. media 30 días	Número + indicador
Visitas	Sesiones únicas	vs. ayer	Número + sparkline
Conversión	Visitas → Pedidos	vs. media	Porcentaje + trend
Pendientes	Pedidos sin procesar	Urgencia	Número destacado
C&C Listos	Click & Collect preparados	Sin recoger	Badge de alerta
Reseñas	Reseñas pendientes moderar	Nuevas hoy	Contador
3.2 Panel de Alertas
// Tipos de alertas en el dashboard const alertTypes = [   // Críticas (rojo) - Requieren acción inmediata   {     type: 'critical',     examples: [       'Stock agotado en producto popular',       'Pedido con incidencia de envío',       'Reseña negativa (1-2 estrellas)',       'Click & Collect sin recoger > 24h',       'Pago fallido pendiente',     ]   },      // Advertencias (amarillo) - Atención pronto   {     type: 'warning',     examples: [       'Stock bajo (< 5 unidades)',       'Pedido pendiente > 2h',       'Pregunta sin responder > 24h',       'Cupón expira mañana',       'Sincronización POS fallida',     ]   },      // Información (azul) - Notificaciones   {     type: 'info',     examples: [       'Nuevo pedido recibido',       'Nueva reseña publicada',       'Producto más vendido del día',       'Meta de ventas alcanzada',     ]   },      // Éxito (verde) - Celebraciones   {     type: 'success',     examples: [       'Récord de ventas batido',       'Rating subió a 4.5★',       '100 pedidos completados',     ]   } ];
 
3.3 Acciones Rápidas
// Quick Actions en el dashboard const quickActions = [   {     icon: 'PlusIcon',     label: 'Nuevo Producto',     action: () => navigate('/merchant/catalog/new'),     shortcut: 'Ctrl+N',   },   {     icon: 'TagIcon',     label: 'Crear Cupón',     action: () => openModal('create-coupon'),     shortcut: 'Ctrl+K',   },   {     icon: 'BoltIcon',     label: 'Flash Offer',     action: () => openModal('flash-offer'),     shortcut: 'Ctrl+F',   },   {     icon: 'PrinterIcon',     label: 'Imprimir Etiquetas',     action: () => openModal('print-labels'),     badge: pendingShipmentsCount,   },   {     icon: 'ChartIcon',     label: 'Ver Informe',     action: () => navigate('/merchant/analytics'),   },   {     icon: 'CogIcon',     label: 'Configuración',     action: () => navigate('/merchant/settings'),   }, ];
3.4 Activity Feed
// Feed de actividad en tiempo real interface ActivityItem {   id: string;   type: 'order' | 'review' | 'question' | 'stock' | 'customer';   action: string;   timestamp: Date;   data: Record<string, any>;   link?: string; }  // Ejemplos de actividad const recentActivity = [   {     type: 'order',     action: 'Nuevo pedido #ORD-2026-001234',     timestamp: '2 min',     data: { total: 89.95, items: 3 },     link: '/merchant/orders/ORD-2026-001234',   },   {     type: 'review',     action: 'Nueva reseña 5★ en "Camiseta Algodón"',     timestamp: '15 min',     data: { rating: 5, author: 'María G.' },     link: '/merchant/reviews/456',   },   {     type: 'stock',     action: 'Stock bajo: "Vestido Verano" (3 uds)',     timestamp: '1h',     data: { product: 'Vestido Verano', stock: 3 },     link: '/merchant/catalog/789',   },   {     type: 'customer',     action: 'Nuevo cliente registrado',     timestamp: '2h',     data: { name: 'Carlos R.' },   }, ];
 
4. Módulo de Pedidos
4.1 Vista de Lista de Pedidos
Columna	Descripción	Filtrable	Ordenable
Número	ORD-2026-XXXXXX	Sí (búsqueda)	Sí
Fecha	Fecha y hora del pedido	Sí (rango)	Sí
Cliente	Nombre del cliente	Sí	Sí
Items	Número de productos	No	Sí
Total	Importe total	Sí (rango)	Sí
Estado	Estado del pedido	Sí (multi)	Sí
Fulfillment	Tipo: envío, C&C, local	Sí	No
Pago	Estado del pago	Sí	No
Acciones	Botones de acción	No	No
4.2 Estados de Pedido (Vista Kanban)
// Vista Kanban de pedidos por estado const orderKanban = [   {     column: 'Nuevos',     state: 'confirmed',     color: 'blue',     actions: ['process', 'cancel'],   },   {     column: 'En Preparación',     state: 'processing',     color: 'yellow',     actions: ['ready_pickup', 'ship'],   },   {     column: 'Listo C&C',     state: 'ready_pickup',     color: 'purple',     actions: ['collected', 'extend', 'cancel'],   },   {     column: 'Enviado',     state: 'shipped',     color: 'orange',     actions: ['track', 'mark_delivered'],   },   {     column: 'Completado',     state: 'completed',     color: 'green',     actions: ['refund', 'review_request'],   }, ];  // Drag & drop para cambiar estado // Confirmar acción con modal si es irreversible
4.3 Detalle de Pedido
// Secciones del detalle de pedido  1. HEADER    - Número de pedido + estado badge    - Fecha y hora    - Canal de venta (web, app, POS)    - Acciones principales  2. CLIENTE    - Nombre, email, teléfono    - Historial de pedidos (link)    - Notas del cliente  3. PRODUCTOS    - Lista de items con imagen, nombre, variante, cantidad, precio    - Descuentos aplicados por línea    - Subtotal  4. RESUMEN FINANCIERO    - Subtotal productos    - Descuentos (cupones, promos)    - Envío    - Impuestos    - TOTAL  5. ENVÍO / RECOGIDA    - Dirección de entrega o tienda C&C    - Método seleccionado    - Fecha estimada    - Tracking (si aplica)    - Código de recogida (si C&C)  6. PAGO    - Método de pago    - Estado del pago    - ID de transacción    - Acciones: refund parcial/total  7. TIMELINE    - Historial de estados    - Quién cambió cada estado    - Notas internas  8. NOTAS INTERNAS    - Añadir nota (solo visible para staff)
 
4.4 Acciones de Pedido
Acción	Desde Estado	A Estado	Efectos
Procesar	confirmed	processing	Reserva stock confirmada
Marcar Listo C&C	processing	ready_pickup	Genera código, notifica cliente
Registrar Recogida	ready_pickup	collected	Confirma entrega, completa pedido
Generar Etiqueta	processing	processing	Crea shipment, label PDF
Marcar Enviado	processing	shipped	Decrementa stock, notifica
Marcar Entregado	shipped/in_transit	delivered	Completa pedido
Cancelar	confirmed/processing	cancelled	Libera stock, refund auto
Reembolso Parcial	cualquier	mismo	Refund parcial Stripe
Reembolso Total	cualquier	refunded	Refund total, cierra pedido
4.5 Procesamiento Batch
// Acciones batch sobre múltiples pedidos  const batchActions = [   {     action: 'print_labels',     label: 'Imprimir Etiquetas',     icon: 'PrinterIcon',     allowedStates: ['processing'],     handler: async (orderIds) => {       const labels = await generateBatchLabels(orderIds);       return downloadPdf(labels);     },   },   {     action: 'mark_shipped',     label: 'Marcar como Enviado',     icon: 'TruckIcon',     allowedStates: ['processing'],     requiresConfirmation: true,   },   {     action: 'mark_ready_pickup',     label: 'Marcar Listo para Recoger',     icon: 'ShoppingBagIcon',     allowedStates: ['processing'],     filter: (order) => order.fulfillment_type === 'click_collect',   },   {     action: 'export_csv',     label: 'Exportar a CSV',     icon: 'DownloadIcon',     allowedStates: 'all',   },   {     action: 'send_reminder',     label: 'Enviar Recordatorio C&C',     icon: 'BellIcon',     allowedStates: ['ready_pickup'],     filter: (order) => order.fulfillment_type === 'click_collect',   }, ];
 
5. Módulo de Catálogo
5.1 Vista de Productos
Columna	Descripción	Acciones
Imagen	Thumbnail del producto	Click para ampliar
Nombre	Título del producto	Click para editar
SKU	Código interno	Copiar al portapapeles
Categoría	Categoría principal	Filtro rápido
Precio	Precio de venta	Edición inline
Stock	Stock total (todas ubicaciones)	Edición inline
Estado	Publicado/Borrador/Agotado	Toggle rápido
Ventas	Unidades vendidas (30d)	Ordenar
Rating	Valoración media	Link a reseñas
Acciones	Editar, duplicar, archivar	Menú dropdown
5.2 Editor de Producto
// Secciones del editor de producto  Tabs: [General] [Variantes] [Inventario] [Imágenes] [SEO] [Opciones]  // TAB: GENERAL - Título * - Descripción (editor rich text) - Categoría principal * - Categorías adicionales - Marca - Tags - Precio original - Precio de venta * - Precio rebajado (sale_price) - Fechas de rebaja  // TAB: VARIANTES - Atributos variables (talla, color, etc.) - Generador automático de combinaciones - Tabla de variantes: SKU, precio, stock por variante - Imagen por variante  // TAB: INVENTARIO - Gestión de stock: Sí/No - Stock por ubicación (multi-location) - Permitir pedidos sin stock - Umbral de stock bajo - Código de barras (EAN/UPC)  // TAB: IMÁGENES - Galería de imágenes (drag & drop para reordenar) - Imagen principal - Alt text por imagen - Zoom habilitado  // TAB: SEO - Meta título - Meta descripción - URL slug - Preview de Google  // TAB: OPCIONES - Producto destacado - Nuevo (badge) - Envío gratuito - Solo en tienda (no online) - Producto digital
 
5.3 Gestión de Inventario
// Vista de inventario multi-ubicación  interface StockLocation {   id: number;   name: string;           // 'Tienda Principal', 'Almacén'   type: 'store' | 'warehouse';   address: string;   isDefault: boolean; }  interface ProductStock {   productId: number;   variationId?: number;   locationId: number;   quantity: number;   reserved: number;       // Reservado para pedidos   available: number;      // quantity - reserved   lowStockThreshold: number; }  // Acciones de stock const stockActions = [   'adjust': 'Ajustar stock (+ / -)',   'transfer': 'Transferir entre ubicaciones',   'receive': 'Recibir mercancía',   'count': 'Inventario físico',   'history': 'Ver historial de movimientos', ];  // Movimiento de stock interface StockMovement {   id: number;   productId: number;   variationId?: number;   fromLocationId?: number;   toLocationId?: number;   quantity: number;   type: 'sale' | 'return' | 'adjustment' | 'transfer' | 'receive';   reason?: string;   reference?: string;     // Order ID, PO number, etc.   userId: number;   createdAt: Date; }
5.4 Importación/Exportación
Formato	Importar	Exportar	Casos de Uso
CSV	✓	✓	Actualizaciones masivas de precio/stock
Excel (XLSX)	✓	✓	Catálogo completo con formato
Google Sheets	✓	✓	Colaboración, actualizaciones periódicas
JSON	✓	✓	Integraciones API
XML (Feed)	—	✓	Google Merchant, Meta Catalog
 
6. Módulo de Analytics
6.1 Dashboard de Analytics
// Métricas disponibles en Analytics  // VENTAS - Ventas totales (período) - Número de pedidos - Ticket medio (AOV) - Ventas por día/semana/mes - Comparativa con período anterior - Ventas por canal (web, app, POS) - Ventas por método de pago - Devoluciones y reembolsos  // PRODUCTOS - Top productos más vendidos - Productos sin ventas - Stock value total - Rotación de inventario - Productos más vistos vs comprados - Tasa de conversión por producto  // CLIENTES - Nuevos vs recurrentes - Customer Lifetime Value (CLV) - Frecuencia de compra - Cohort analysis - Clientes top por gasto  // TRÁFICO - Visitas únicas - Páginas vistas - Bounce rate - Tiempo en sitio - Fuentes de tráfico - Dispositivos  // CONVERSIÓN - Funnel: Visita → Carrito → Checkout → Compra - Abandono de carrito - Tasa de conversión global - Conversión por dispositivo  // MARKETING - Rendimiento de cupones - ROI de promociones - Efectividad de Flash Offers
6.2 Informes Predefinidos
Informe	Contenido	Frecuencia Sugerida
Resumen Diario	Ventas, pedidos, tickets, alertas	Diario (email)
Resumen Semanal	KPIs semana, top productos, tendencias	Semanal (lunes)
Resumen Mensual	Análisis completo, comparativas, objetivos	Mensual
Inventario	Stock actual, rotación, valoración	Semanal
Clientes	Nuevos, recurrentes, segmentación	Mensual
Productos	Rendimiento, ABC analysis	Mensual
Promociones	ROI campañas, cupones usados	Por campaña
Reseñas	Rating, sentimiento, respuestas	Semanal
 
6.3 AnalyticsService
<?php namespace Drupal\jaraba_merchant\Service;  class AnalyticsService {    // Ventas   public function getSalesOverview(int $merchantId, DateRange $range): SalesOverview;   public function getSalesByDay(int $merchantId, DateRange $range): array;   public function getSalesByChannel(int $merchantId, DateRange $range): array;   public function getAverageOrderValue(int $merchantId, DateRange $range): float;      // Productos   public function getTopProducts(int $merchantId, int $limit = 10): array;   public function getProductPerformance(int $productId, DateRange $range): ProductStats;   public function getStockValue(int $merchantId): float;   public function getSlowMovingProducts(int $merchantId, int $days = 30): array;      // Clientes   public function getCustomerStats(int $merchantId, DateRange $range): CustomerStats;   public function getCustomerLifetimeValue(int $merchantId): array;   public function getNewVsReturning(int $merchantId, DateRange $range): array;      // Conversión   public function getConversionFunnel(int $merchantId, DateRange $range): FunnelData;   public function getCartAbandonmentRate(int $merchantId, DateRange $range): float;      // Exportación   public function exportToCsv(string $reportType, array $params): string;   public function exportToPdf(string $reportType, array $params): string;   public function scheduleReport(int $merchantId, ReportConfig $config): void; }
6.4 Comparativas y Benchmarks
// Sistema de benchmarks para comparar rendimiento  interface Benchmark {   metric: string;   merchantValue: number;   categoryAverage: number;    // Media de la categoría del comercio   topPerformerValue: number;  // Top 10% de la plataforma   percentile: number;         // En qué percentil está el comercio }  // Ejemplos de benchmarks const benchmarks = [   {     metric: 'Tasa de Conversión',     merchantValue: 2.8,     categoryAverage: 2.5,     topPerformerValue: 4.2,     percentile: 65,  // Mejor que el 65% de comercios similares     insight: '¡Estás por encima de la media! Optimiza checkout para llegar al top.',   },   {     metric: 'Ticket Medio',     merchantValue: 45.00,     categoryAverage: 52.00,     topPerformerValue: 78.00,     percentile: 35,     insight: 'Considera upselling y bundles para aumentar el ticket.',   },   {     metric: 'Tiempo de Respuesta a Reseñas',     merchantValue: 12,  // horas     categoryAverage: 24,     topPerformerValue: 4,     percentile: 75,     insight: 'Excelente tiempo de respuesta. Sigue así.',   }, ];
 
7. Módulo de Configuración
7.1 Secciones de Configuración
Sección	Contenido	Rol Mínimo
Perfil de Tienda	Nombre, logo, descripción, horarios	Admin
Ubicaciones	Tiendas físicas, almacenes	Admin
Envíos	Métodos, tarifas, zonas, carriers	Admin
Pagos	Métodos aceptados, Stripe config	Owner
Impuestos	Configuración de IVA	Admin
Notificaciones	Emails, push, alertas	Manager
Equipo	Usuarios, roles, permisos	Owner
Integraciones	POS, Google Business, APIs	Admin
Legal	Términos, privacidad, cookies	Owner
Facturación	Plan, facturas, método de pago	Owner
7.2 Configuración de Tienda
// Perfil de tienda interface MerchantSettings {   // Información básica   name: string;   legalName: string;   taxId: string;  // CIF/NIF   logo: File;   coverImage: File;   description: string;   shortDescription: string;      // Contacto   email: string;   phone: string;   whatsapp?: string;      // Dirección principal   address: Address;      // Horarios   openingHours: OpeningHoursSpecification[];   specialHours: SpecialHours[];  // Festivos, etc.      // Redes sociales   socialLinks: {     facebook?: string;     instagram?: string;     twitter?: string;     tiktok?: string;   };      // Categorías de Google Business   gbpCategory: string;   gbpAdditionalCategories: string[];      // Opciones de tienda   allowPickup: boolean;   allowShipping: boolean;   allowLocalDelivery: boolean;   minimumOrderValue: number;      // Apariencia   primaryColor: string;   accentColor: string; }
 
7.3 Gestión de Equipo
// Invitar nuevo miembro al equipo interface TeamInvitation {   email: string;   role: 'admin' | 'manager' | 'staff' | 'viewer';   locations?: number[];  // Acceso a ubicaciones específicas   expiresAt: Date;       // La invitación expira }  // Permisos granulares por módulo const permissions = {   orders: {     view: boolean,     process: boolean,     cancel: boolean,     refund: boolean,   },   catalog: {     view: boolean,     create: boolean,     edit: boolean,     delete: boolean,     manage_stock: boolean,   },   customers: {     view: boolean,     export: boolean,   },   promotions: {     view: boolean,     create: boolean,     edit: boolean,   },   reviews: {     view: boolean,     respond: boolean,     moderate: boolean,   },   analytics: {     view_basic: boolean,     view_advanced: boolean,     export: boolean,   },   settings: {     view: boolean,     edit_store: boolean,     edit_shipping: boolean,     manage_team: boolean,     manage_billing: boolean,   }, };
7.4 Configuración de Notificaciones
Evento	Email	Push App	Dashboard	Configurable
Nuevo pedido	✓	✓	✓	Sí
Pedido cancelado	✓	✓	✓	Sí
Pago recibido	✓	—	✓	Sí
Stock bajo	✓	✓	✓	Umbral configurable
Stock agotado	✓	✓	✓	Sí
Nueva reseña	✓	✓	✓	Por rating
Reseña negativa	✓	✓	✓	Siempre activo
Nueva pregunta	✓	✓	✓	Sí
C&C sin recoger	✓	✓	✓	Siempre activo
Informe diario	✓	—	—	Hora configurable
Informe semanal	✓	—	—	Día configurable
 
8. APIs del Merchant Portal
8.1 Endpoints de Dashboard
Método	Endpoint	Descripción
GET	/api/v1/merchant/dashboard/kpis	KPIs del día
GET	/api/v1/merchant/dashboard/alerts	Alertas activas
GET	/api/v1/merchant/dashboard/activity	Feed de actividad
GET	/api/v1/merchant/dashboard/chart/sales	Gráfico de ventas
POST	/api/v1/merchant/dashboard/alerts/{id}/dismiss	Descartar alerta
8.2 Endpoints de Pedidos
Método	Endpoint	Descripción
GET	/api/v1/merchant/orders	Listar pedidos
GET	/api/v1/merchant/orders/{id}	Detalle de pedido
POST	/api/v1/merchant/orders/{id}/process	Procesar pedido
POST	/api/v1/merchant/orders/{id}/ready-pickup	Marcar listo C&C
POST	/api/v1/merchant/orders/{id}/ship	Marcar enviado
POST	/api/v1/merchant/orders/{id}/cancel	Cancelar pedido
POST	/api/v1/merchant/orders/{id}/refund	Reembolsar
POST	/api/v1/merchant/orders/{id}/note	Añadir nota interna
POST	/api/v1/merchant/orders/batch/labels	Generar etiquetas batch
GET	/api/v1/merchant/orders/export	Exportar pedidos
8.3 Endpoints de Catálogo
Método	Endpoint	Descripción
GET	/api/v1/merchant/products	Listar productos
POST	/api/v1/merchant/products	Crear producto
GET	/api/v1/merchant/products/{id}	Detalle producto
PATCH	/api/v1/merchant/products/{id}	Actualizar producto
DELETE	/api/v1/merchant/products/{id}	Archivar producto
POST	/api/v1/merchant/products/{id}/duplicate	Duplicar producto
PATCH	/api/v1/merchant/products/{id}/stock	Actualizar stock
POST	/api/v1/merchant/products/import	Importar productos
GET	/api/v1/merchant/products/export	Exportar productos
GET	/api/v1/merchant/inventory	Vista de inventario
 
8.4 Endpoints de Analytics
Método	Endpoint	Descripción
GET	/api/v1/merchant/analytics/overview	Resumen general
GET	/api/v1/merchant/analytics/sales	Datos de ventas
GET	/api/v1/merchant/analytics/products	Rendimiento productos
GET	/api/v1/merchant/analytics/customers	Datos de clientes
GET	/api/v1/merchant/analytics/conversion	Funnel de conversión
GET	/api/v1/merchant/analytics/benchmarks	Comparativas
POST	/api/v1/merchant/reports/generate	Generar informe
GET	/api/v1/merchant/reports	Listar informes guardados
8.5 Endpoints de Configuración
Método	Endpoint	Descripción
GET	/api/v1/merchant/settings	Toda la configuración
PATCH	/api/v1/merchant/settings/store	Actualizar perfil
PATCH	/api/v1/merchant/settings/shipping	Actualizar envíos
PATCH	/api/v1/merchant/settings/notifications	Actualizar notificaciones
GET	/api/v1/merchant/team	Listar equipo
POST	/api/v1/merchant/team/invite	Invitar miembro
PATCH	/api/v1/merchant/team/{userId}/role	Cambiar rol
DELETE	/api/v1/merchant/team/{userId}	Eliminar miembro
 
9. Componentes Frontend
9.1 Arquitectura de Componentes
src/merchant/ ├── layouts/ │   ├── MerchantLayout.jsx       // Layout con sidebar │   ├── Sidebar.jsx               // Navegación lateral │   └── TopBar.jsx                // Barra superior con alertas │ ├── pages/ │   ├── Dashboard.jsx             // Dashboard principal │   ├── orders/ │   │   ├── OrderList.jsx         // Lista de pedidos │   │   ├── OrderDetail.jsx       // Detalle de pedido │   │   └── OrderKanban.jsx       // Vista Kanban │   ├── catalog/ │   │   ├── ProductList.jsx       // Lista de productos │   │   ├── ProductEditor.jsx     // Editor de producto │   │   └── InventoryView.jsx     // Vista de inventario │   ├── analytics/ │   │   ├── AnalyticsDashboard.jsx │   │   └── ReportBuilder.jsx │   └── settings/ │       ├── SettingsLayout.jsx │       └── [subsections].jsx │ ├── components/ │   ├── common/ │   │   ├── DataTable.jsx         // Tabla reutilizable │   │   ├── StatCard.jsx          // Tarjeta de estadística │   │   ├── ChartCard.jsx         // Tarjeta con gráfico │   │   └── AlertBanner.jsx       // Banner de alertas │   ├── orders/ │   │   ├── OrderCard.jsx │   │   ├── OrderTimeline.jsx │   │   └── OrderActions.jsx │   ├── products/ │   │   ├── ProductCard.jsx │   │   ├── VariantEditor.jsx │   │   └── StockEditor.jsx │   └── charts/ │       ├── SalesChart.jsx │       ├── ConversionFunnel.jsx │       └── TopProductsChart.jsx
9.2 Componente StatCard
// StatCard.jsx - Tarjeta de KPI export function StatCard({    title,    value,    previousValue,    format = 'number',   icon: Icon,   trend,   link  }) {   const formattedValue = formatValue(value, format);   const change = previousValue      ? ((value - previousValue) / previousValue * 100).toFixed(1)     : null;   const isPositive = change > 0;      return (     <Card className="stat-card">       <div className="stat-header">         <span className="stat-title">{title}</span>         {Icon && <Icon className="stat-icon" />}       </div>              <div className="stat-value">{formattedValue}</div>              {change !== null && (         <div className={`stat-change ${isPositive ? 'positive' : 'negative'}`}>           {isPositive ? <ArrowUpIcon /> : <ArrowDownIcon />}           <span>{Math.abs(change)}%</span>           <span className="vs-text">vs. período anterior</span>         </div>       )}              {trend && (         <Sparkline data={trend} className="stat-sparkline" />       )}              {link && (         <Link to={link} className="stat-link">           Ver detalles →         </Link>       )}     </Card>   ); }
 
10. Flujos de Automatización (ECA)
10.1 ECA-MERCH-001: Nuevo Pedido
Trigger: Order created con merchant_id
1. Crear notificación en dashboard
2. Enviar push notification a app merchant
3. Enviar email si configurado
4. Actualizar KPIs en tiempo real
5. Reproducir sonido en dashboard (si activo)
10.2 ECA-MERCH-002: Stock Bajo
Trigger: Product stock <= low_stock_threshold
1. Crear alerta de tipo 'warning'
2. Enviar notificación si es producto popular
3. Sugerir reposición basada en histórico
10.3 ECA-MERCH-003: Stock Agotado
Trigger: Product stock = 0
1. Crear alerta crítica
2. Notificar inmediatamente (push + email)
3. Marcar producto como 'agotado' en tienda
4. Ofrecer opción de ocultar automáticamente
10.4 ECA-MERCH-004: Informe Diario
Trigger: Cron a la hora configurada (default 08:00)
1. Generar resumen del día anterior
2. Calcular comparativas con día/semana anterior
3. Identificar insights relevantes
4. Enviar email con informe
 
11. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Layout base. Dashboard con KPIs. Sistema de alertas.	Auth system
Sprint 2	Semana 3-4	Módulo de Pedidos completo. Lista, detalle, acciones.	67_Order_System
Sprint 3	Semana 5-6	Módulo de Catálogo. CRUD productos. Editor variantes.	66_Product_Catalog
Sprint 4	Semana 7-8	Gestión de Inventario. Multi-ubicación. Movimientos.	Sprint 3
Sprint 5	Semana 9-10	Módulo Analytics. Gráficos. Informes. Benchmarks.	Todos anteriores
Sprint 6	Semana 11-12	Configuración. Equipo. Notificaciones. Flujos ECA. QA.	Sprint 5
11.1 Criterios de Aceptación Sprint 2 (Pedidos)
✓ Listar pedidos con filtros y búsqueda
✓ Ver detalle completo de pedido
✓ Cambiar estado (procesar, enviar, completar)
✓ Generar etiqueta de envío
✓ Acciones batch funcionando
✓ Vista Kanban drag & drop
11.2 Dependencias
• React 18+, React Router, React Query
• Tailwind CSS, Headless UI
• Recharts para gráficos
• TanStack Table para tablas
• Todos los documentos anteriores de ComercioConecta
• Sistema de autenticación y permisos
--- Fin del Documento ---
74_ComercioConecta_Merchant_Portal_v1.docx | Jaraba Impact Platform | Enero 2026
