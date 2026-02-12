PORTAL DEL PRODUCTOR
Dashboard, Gestión de Productos, Pedidos y Payouts
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	52_AgroConecta_Producer_Portal
Dependencias:	48_Product_Catalog, 49_Order_System, Stripe Connect
 
1. Resumen Ejecutivo
Este documento especifica el Portal del Productor para AgroConecta, el área privada donde los productores gestionan su catálogo, procesan pedidos, configuran envíos, visualizan analytics y acceden a sus pagos. Es la herramienta central de operación diaria.
1.1 Objetivos del Portal
•	Autonomía total: El productor gestiona todo sin intervención del admin
•	Operación eficiente: Flujos optimizados para procesar pedidos rápidamente
•	Visibilidad financiera: Transparencia total en ventas, comisiones y payouts
•	Insights accionables: Métricas que ayudan a tomar decisiones de negocio
•	Mobile-first: Diseño responsive para gestión desde el campo
•	Onboarding guiado: Wizard inicial para configurar la tienda paso a paso
1.2 Stack Tecnológico
Componente	Tecnología
Frontend	Drupal Theme + Alpine.js para interactividad
Dashboard	Views + Custom blocks + Chart.js para gráficos
Formularios	Webform o Drupal Forms API con AJAX
Tablas	Views con filtros expuestos y bulk operations
Notificaciones	Mercure Hub para real-time + toast notifications
Pagos	Stripe Connect Dashboard embebido + APIs
Documentos	Entity Print para facturas y albaranes PDF
Permisos	Drupal Permissions + Group + Custom Access
1.3 Secciones del Portal
Sección	Funcionalidades	Prioridad
Dashboard	KPIs, alertas, pedidos pendientes, gráficos de ventas	P0 - Crítica
Pedidos	Lista, detalle, confirmar, preparar, enviar, incidencias	P0 - Crítica
Productos	CRUD, variaciones, stock, precios, imágenes, SEO	P0 - Crítica
Envíos	Tarifas, zonas, etiquetas, tracking, recogidas	P1 - Alta
Finanzas	Ventas, comisiones, payouts, facturas, impuestos	P1 - Alta
Analytics	Ventas por producto, clientes, tendencias, comparativas	P2 - Media
Reseñas	Ver reseñas, responder, solicitar, reportar	P2 - Media
Configuración	Perfil, horarios, vacaciones, notificaciones, Stripe	P1 - Alta
 
2. Dashboard Principal
La página de inicio del productor muestra una vista panorámica del estado del negocio con métricas clave, alertas y acciones pendientes.
2.1 Layout del Dashboard
┌─────────────────────────────────────────────────────────────────┐
│  🏠 Mi Tienda: Finca Los Olivos                    [⚙️] [👤]   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ⚠️ ALERTAS (2)                                                 │
│  • 3 pedidos pendientes de confirmar (> 2h)                     │
│  • Stock bajo: AOVE Picual 500ml (5 uds)                        │
│                                                                 │
│  ┌─────────────┬─────────────┬─────────────┬─────────────┐      │
│  │ Ventas Hoy  │ Este Mes    │ Pedidos     │ Valoración  │      │
│  │   €245.00   │  €3,420.50  │  Pend: 3    │   ⭐ 4.8    │      │
│  │   ↑ 12%     │   ↑ 8%      │  Prep: 2    │  (47 rev)   │      │
│  └─────────────┴─────────────┴─────────────┴─────────────┘      │
│                                                                 │
│  📦 PEDIDOS PENDIENTES                          [Ver todos →]   │
│  ┌────────────┬──────────────┬─────────┬──────────┬──────────┐  │
│  │ #AGR-00045 │ Juan García  │ €67.50  │ Pending  │[Confirm] │  │
│  │ #AGR-00044 │ María López  │ €124.00 │ Pending  │[Confirm] │  │
│  │ #AGR-00043 │ Pedro Ruiz   │ €45.00  │ Prepar.  │[Ready]   │  │
│  └────────────┴──────────────┴─────────┴──────────┴──────────┘  │
│                                                                 │
│  📈 VENTAS ÚLTIMOS 7 DÍAS                                       │
│  [========== Gráfico de barras/líneas ==========]               │
│                                                                 │
│  🏆 TOP PRODUCTOS                    💬 RESEÑAS RECIENTES       │
│  1. AOVE Picual 500ml (23 uds)       ⭐⭐⭐⭐⭐ "Excelente..."   │
│  2. AOVE Hojiblanca 1L (18 uds)      ⭐⭐⭐⭐ "Buen producto..."  │
│  3. Pack Degustación (12 uds)        [Ver todas →]              │
└─────────────────────────────────────────────────────────────────┘
2.2 KPIs del Dashboard
KPI	Cálculo	Contexto
Ventas Hoy	SUM(sub_orders.subtotal) WHERE date = today	Comparativa vs mismo día semana anterior (%)
Ventas Mes	SUM(sub_orders.subtotal) WHERE month = current	Comparativa vs mes anterior (%)
Pedidos Pendientes	COUNT(sub_orders) WHERE state IN (pending, confirmed, processing)	Desglose por estado
Valoración Media	AVG(reviews.rating) WHERE producer_id = X	Número total de reseñas
Tasa de Conversión	Pedidos / Visitas a productos del productor	Últimos 30 días
Tiempo Medio Preparación	AVG(confirmed_at - placed_at)	Meta: < 24 horas
2.3 Sistema de Alertas
Alerta	Condición	Acción Sugerida
Pedidos sin confirmar	sub_orders.state = pending AND created > 2h	Enlace a confirmar
Stock bajo	product.stock <= product.low_stock_threshold	Enlace a editar stock
Stock agotado	product.stock = 0 AND product.published = TRUE	Reponer o despublicar
Reseña negativa	review.rating <= 2 AND NOT responded	Enlace a responder
Payout disponible	Fondos en Stripe Connect listos	Ver detalles de payout
Incidencia de envío	shipment.state = exception	Contactar cliente/carrier
 
3. Gestión de Pedidos
El módulo de pedidos permite al productor ver y procesar sus sub-orders de forma eficiente, desde la confirmación hasta el envío.
3.1 Lista de Pedidos
Vista tabular con filtros y acciones bulk:
•	Filtros: Estado, fecha (desde/hasta), cliente, importe mínimo/máximo
•	Ordenación: Por fecha (desc default), importe, estado
•	Columnas: Número, Cliente, Items, Total, Estado, Fecha, Acciones
•	Bulk actions: Confirmar seleccionados, Imprimir albaranes, Exportar CSV
•	Paginación: 20 pedidos por página, infinite scroll opcional
3.2 Detalle del Pedido
Vista completa de un sub-order específico:
Sección	Contenido
Cabecera	Número de sub-order, estado con badge de color, fecha, acciones principales
Cliente	Nombre, email, teléfono (si disponible), historial de pedidos con este productor
Dirección de Envío	Dirección completa formateada, botón copiar, enlace a Google Maps
Items del Pedido	Tabla: imagen, producto, variación, SKU, cantidad, precio unitario, subtotal
Resumen Económico	Subtotal, envío, total, comisión plataforma, payout estimado
Timeline	Historial de eventos: creado, confirmado, preparando, enviado, entregado
Notas	Notas del cliente + notas internas del productor
Envío	Transportista, tracking number (si existe), etiqueta PDF, estado del envío
3.3 Flujo de Procesamiento
PEDIDO RECIBIDO
     │
     ▼
[1. CONFIRMAR] ──────► El productor revisa y acepta el pedido
     │                 • Verifica stock disponible
     │                 • Opcional: rechazar con motivo
     ▼
[2. PREPARAR] ───────► El productor prepara los productos
     │                 • Estado: 'processing'
     │                 • Notifica al cliente
     ▼
[3. LISTO] ──────────► Pedido listo para envío/recogida
     │                 • Genera etiqueta automática (si envío)
     │                 • Programa recogida del transportista
     ▼
[4. ENVIAR] ─────────► Marcar como enviado
     │                 • Introduce tracking si es manual
     │                 • Email al cliente con tracking
     ▼
[ENTREGADO] ─────────► Confirmado por tracking/cliente
                       • Trigger: payout al productor
 
4. Gestión de Productos
El módulo de productos permite al productor gestionar su catálogo completo, incluyendo variaciones, precios, stock e imágenes.
4.1 Lista de Productos
Funcionalidad	Descripción
Vista de tabla	Imagen miniatura, nombre, SKU, precio, stock, estado, ventas, acciones
Vista de tarjetas	Grid visual con imagen grande, info básica, quick actions
Filtros	Categoría, estado (publicado/borrador/agotado), rango de precio, stock
Búsqueda	Por nombre, SKU, descripción (fulltext)
Bulk actions	Publicar/despublicar, actualizar precios %, eliminar
Quick edit	Editar precio y stock inline sin abrir formulario completo
Duplicar	Crear copia del producto con sufijo '(Copia)'
4.2 Formulario de Producto
Tabs del formulario de creación/edición:
Tab 1: Información Básica
•	Nombre del producto: Título principal, max 100 chars
•	Descripción corta: Para listados, max 200 chars
•	Descripción completa: Editor WYSIWYG con formato
•	Categoría: Selector jerárquico de la taxonomía
•	Etiquetas: Tags libres para búsqueda
Tab 2: Precios y Stock
•	Precio base: Precio de venta (IVA incluido o excluido según config)
•	Precio anterior: Para mostrar descuento tachado (opcional)
•	Coste: Coste de producción (privado, para márgenes)
•	Stock: Cantidad disponible, 0 = agotado
•	Umbral stock bajo: Para alertas (default: 5)
•	Gestión de stock: On/Off (algunos productos son bajo demanda)
Tab 3: Imágenes
•	Imagen principal: Obligatoria, drag & drop, crop automático
•	Galería: Hasta 8 imágenes adicionales, reordenables
•	Alt text: Descripción para accesibilidad y SEO
•	Formatos: JPG, PNG, WebP. Max 5MB. Conversión automática a WebP
Tab 4: Variaciones (si aplica)
•	Atributos: Definir atributos (Formato, Tamaño, etc.)
•	Generar variaciones: Crear combinaciones automáticamente
•	Por variación: SKU único, precio, stock, imagen específica
Tab 5: Envío
•	Peso: Peso del producto en kg
•	Dimensiones: Largo x Ancho x Alto en cm
•	Requiere refrigeración: Checkbox para cadena de frío
•	Clase de envío: Estándar, frágil, voluminoso
Tab 6: SEO
•	Meta título: Override del título para SEO
•	Meta descripción: Descripción para buscadores
•	URL amigable: Slug personalizable
 
5. Finanzas y Payouts
El módulo financiero proporciona transparencia total sobre ventas, comisiones y pagos al productor mediante Stripe Connect.
5.1 Resumen Financiero
Métrica	Este Mes	Total Histórico
Ventas Brutas	€3,420.50	€45,230.00
Comisiones Plataforma	-€171.03 (5%)	-€2,261.50
Costes de Envío	-€285.00	-€3,890.00
Ingresos Netos	€2,964.47	€39,078.50
Pendiente de Pago	€845.20	-
Pagos Recibidos	€2,119.27	€38,233.30
5.2 Historial de Transacciones
Lista detallada de todas las transacciones:
•	Tipos: Venta, Comisión, Envío, Reembolso, Payout
•	Columnas: Fecha, Tipo, Descripción, Pedido relacionado, Importe, Balance
•	Filtros: Tipo de transacción, rango de fechas, importe
•	Exportar: CSV para contabilidad, con todos los campos necesarios
5.3 Payouts de Stripe Connect
Configuración y gestión de pagos automáticos:
Configuración de Cuenta Stripe
•	Onboarding: Link a Stripe Connect Onboarding para verificación
•	Estado: Pendiente, Verificado, Restringido
•	Cuenta bancaria: IBAN verificado para transferencias
•	Dashboard Stripe: Enlace al Express Dashboard de Stripe
Programación de Payouts
Frecuencia	Descripción
Instantáneo	Pago inmediato al confirmar entrega (comisión +1%)
Diario	Transferencia automática cada día a las 00:00
Semanal	Cada lunes con el acumulado de la semana anterior
Mensual	El día 1 de cada mes con el acumulado del mes anterior
Manual	El productor solicita el pago cuando lo desee (mínimo €50)
5.4 Facturas y Documentos
•	Factura de comisiones: PDF mensual con desglose de comisiones cobradas
•	Resumen de ventas: PDF con todas las ventas del periodo
•	Certificado de pagos: Para declaraciones de impuestos
•	Exportación contable: Formato compatible con software contable (A3, Contaplus, etc.)
 
6. Analytics y Reportes
El módulo de analytics proporciona insights sobre el rendimiento del negocio con visualizaciones interactivas y datos accionables.
6.1 Métricas de Ventas
Métrica	Visualización	Periodo Disponible
Ventas totales	Gráfico de líneas	7d, 30d, 90d, 12m, todo
Número de pedidos	Gráfico de barras	7d, 30d, 90d, 12m
Ticket medio	KPI con tendencia	30d, 90d, 12m
Ventas por categoría	Gráfico de dona	30d, 90d, 12m
Top productos	Tabla ranking	7d, 30d, 90d
Comparativa periodos	Gráfico comparativo	vs periodo anterior
6.2 Métricas de Clientes
•	Clientes únicos: Número de clientes distintos en el periodo
•	Clientes recurrentes: % que ha comprado más de una vez
•	Frecuencia de compra: Media de pedidos por cliente
•	Distribución geográfica: Mapa de calor por provincias
•	Top clientes: Ranking por volumen de compra
6.3 Métricas de Producto
•	Visitas por producto: Pageviews de cada ficha de producto
•	Tasa de conversión: Visitas → Añadir al carrito → Compra
•	Productos abandonados: Añadidos al carrito pero no comprados
•	Rotación de stock: Velocidad de venta de cada producto
•	Margen por producto: Si se ha registrado coste, calcular margen
6.4 Métricas Operativas
•	Tiempo medio de confirmación: Desde pedido hasta confirmación
•	Tiempo medio de preparación: Desde confirmación hasta envío
•	Tasa de incidencias: % de pedidos con problemas
•	Valoración media: Evolución de las reseñas en el tiempo
 
7. Configuración del Productor
Área de configuración donde el productor personaliza su tienda, gestiona su perfil y configura preferencias operativas.
7.1 Perfil de la Tienda
Campo	Tipo	Descripción	Restricciones
store_name	VARCHAR(100)	Nombre comercial de la tienda	NOT NULL
slug	VARCHAR(100)	URL amigable: /tienda/{slug}	UNIQUE, NOT NULL
logo	Image	Logo de la tienda (200x200 min)	NULLABLE
banner	Image	Banner de cabecera (1200x300)	NULLABLE
description	TEXT	Descripción de la tienda	Max 2000 chars
story	TEXT	Historia del productor (markdown)	NULLABLE
address	Address	Dirección física de la finca/bodega	NOT NULL
coordinates	POINT	Geolocalización para mapa	NULLABLE
phone	VARCHAR(20)	Teléfono de contacto	NULLABLE
email	Email	Email público de contacto	NOT NULL
website	URL	Web externa del productor	NULLABLE
social_links	JSON	Instagram, Facebook, Twitter...	NULLABLE
7.2 Configuración de Envíos
•	Transportista preferido: MRW, SEUR, GLS, etc.
•	Zonas de envío: Definir dónde envía y dónde no
•	Tarifas personalizadas: Override de tarifas globales
•	Umbral envío gratis: Importe a partir del cual no cobra envío
•	Recogida en origen: Habilitar/deshabilitar, horarios
•	Días de preparación: Tiempo máximo para preparar pedido
7.3 Vacaciones y Disponibilidad
•	Modo vacaciones: Desactiva la tienda temporalmente
•	Fechas de ausencia: Calendario con periodos no disponibles
•	Mensaje personalizado: Texto que verán los clientes
•	Permitir pedidos: Aceptar pedidos aunque esté de vacaciones
7.4 Notificaciones
Notificación	Email	Push	SMS
Nuevo pedido recibido	✓ On	✓ On	Opcional
Pedido pendiente > 2h	✓ On	✓ On	—
Stock bajo	✓ On	Opcional	—
Nueva reseña	✓ On	Opcional	—
Pago recibido	✓ On	—	—
Incidencia de envío	✓ On	✓ On	Opcional
 
8. APIs del Portal del Productor
8.1 Endpoints de Dashboard
Método	Endpoint	Descripción
GET	/api/v1/producer/dashboard/kpis	KPIs principales del dashboard
GET	/api/v1/producer/dashboard/alerts	Alertas activas
GET	/api/v1/producer/dashboard/sales-chart	Datos para gráfico de ventas
POST	/api/v1/producer/alerts/{id}/dismiss	Descartar alerta
8.2 Endpoints de Productos
Método	Endpoint	Descripción
GET	/api/v1/producer/products	Listar productos del productor
POST	/api/v1/producer/products	Crear nuevo producto
GET	/api/v1/producer/products/{id}	Detalle de producto
PATCH	/api/v1/producer/products/{id}	Actualizar producto
DELETE	/api/v1/producer/products/{id}	Eliminar producto
POST	/api/v1/producer/products/{id}/duplicate	Duplicar producto
PATCH	/api/v1/producer/products/{id}/stock	Actualizar stock rápido
POST	/api/v1/producer/products/{id}/images	Subir imagen al producto
8.3 Endpoints de Finanzas
Método	Endpoint	Descripción
GET	/api/v1/producer/finance/summary	Resumen financiero
GET	/api/v1/producer/finance/transactions	Historial de transacciones
GET	/api/v1/producer/finance/payouts	Historial de payouts
POST	/api/v1/producer/finance/payout-request	Solicitar payout manual
GET	/api/v1/producer/finance/stripe-dashboard	URL al Express Dashboard
GET	/api/v1/producer/finance/export	Exportar transacciones CSV
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Layout base del portal. Navegación. Dashboard con KPIs estáticos.	Theme base
Sprint 2	Semana 3-4	Módulo de pedidos completo: lista, detalle, flujo de estados.	49_Order_System
Sprint 3	Semana 5-6	Módulo de productos: CRUD completo, variaciones, imágenes.	48_Product_Catalog
Sprint 4	Semana 7-8	Finanzas: resumen, transacciones, integración Stripe Connect.	Stripe Connect
Sprint 5	Semana 9-10	Analytics con Chart.js. Configuración de perfil y envíos.	Sprint 4
Sprint 6	Semana 11-12	Sistema de alertas. Notificaciones real-time. Optimización mobile. QA.	Sprint 5 + Mercure
--- Fin del Documento ---
52_AgroConecta_Producer_Portal_v1.docx | Jaraba Impact Platform | Enero 2026
