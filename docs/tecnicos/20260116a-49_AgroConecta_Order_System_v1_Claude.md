SISTEMA DE GESTIÓN DE PEDIDOS
Order Management, Workflows y Fulfillment
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	49_AgroConecta_Order_System
Dependencias:	47_Commerce_Core, 48_Product_Catalog, Stripe Connect
 
1. Resumen Ejecutivo
Este documento especifica el sistema completo de gestión de pedidos para AgroConecta, desde la creación del carrito hasta la entrega final. Incluye soporte para pedidos multi-vendor, productos perecederos, opciones de envío/recogida, y split payments automático.
1.1 Objetivos del Sistema
•	Pedidos multi-vendor: Un carrito puede contener productos de múltiples productores
•	Split automático: Distribución de pagos a cada productor vía Stripe Connect
•	Productos perecederos: Gestión de fechas de caducidad y envíos refrigerados
•	Flexibilidad de entrega: Envío a domicilio, recogida en origen, puntos de recogida
•	Notificaciones en tiempo real: Email + push para cada cambio de estado
•	Panel dual: Gestión para cliente (mis pedidos) y productor (pedidos recibidos)
1.2 Stack Tecnológico
Componente	Tecnología
Order Management	Commerce Order 3.x + custom workflows
Carrito	Commerce Cart con ajustes multi-vendor
Checkout	Commerce Checkout + panes personalizados
Pagos	Stripe Payment Element + Connect Destination Charges
Envíos	Commerce Shipping + integración transportistas (MRW, SEUR)
Notificaciones	Symfony Mailer + ActiveCampaign + OneSignal (push)
Automatización	ECA Module para workflows de estado
Integraciones	Make.com webhooks para CRM, transportistas, contabilidad
 
2. Arquitectura de Entidades
El sistema de pedidos extiende Commerce Order con entidades personalizadas para las necesidades específicas del marketplace agrario multi-vendor.
2.1 Entidad: order_agro
Extiende commerce_order. Representa un pedido completo que puede contener items de múltiples productores. El pedido 'padre' se divide en sub-pedidos por productor para gestión independiente.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL
order_number	VARCHAR(32)	Número de pedido público	UNIQUE, NOT NULL, formato: AGR-YYYYMMDD-XXXX
tenant_id	INT	Marketplace donde se realizó	FK tenant.id, NOT NULL, INDEX
customer_id	INT	Cliente que realizó el pedido	FK users.uid, NOT NULL, INDEX
email	VARCHAR(255)	Email de contacto	NOT NULL
phone	VARCHAR(20)	Teléfono de contacto	NULLABLE
state	VARCHAR(32)	Estado actual del pedido	ENUM (ver 2.4), INDEX
billing_address	JSON	Dirección de facturación	NOT NULL, address format
shipping_address	JSON	Dirección de envío	NULLABLE (si recogida)
delivery_method	VARCHAR(32)	Método de entrega	ENUM: shipping|pickup_origin|pickup_point
delivery_date_preferred	DATE	Fecha preferida de entrega	NULLABLE
delivery_notes	TEXT	Notas para la entrega	NULLABLE
subtotal	DECIMAL(10,2)	Suma de items sin envío	NOT NULL, >= 0
shipping_total	DECIMAL(10,2)	Coste total de envío	DEFAULT 0
discount_total	DECIMAL(10,2)	Descuentos aplicados	DEFAULT 0
tax_total	DECIMAL(10,2)	Total de impuestos	DEFAULT 0
total	DECIMAL(10,2)	Total final del pedido	NOT NULL, >= 0
currency	VARCHAR(3)	Moneda ISO 4217	DEFAULT 'EUR'
payment_method	VARCHAR(32)	Método de pago usado	NOT NULL
payment_state	VARCHAR(32)	Estado del pago	ENUM: pending|authorized|paid|refunded|failed
stripe_payment_intent	VARCHAR(64)	ID del PaymentIntent	NULLABLE, INDEX
coupon_code	VARCHAR(32)	Cupón aplicado	NULLABLE
customer_notes	TEXT	Notas del cliente	NULLABLE
internal_notes	TEXT	Notas internas (admin)	NULLABLE
ip_address	VARCHAR(45)	IP del cliente	NULLABLE
placed_at	DATETIME	Fecha/hora de confirmación	NULLABLE, UTC
completed_at	DATETIME	Fecha/hora de completado	NULLABLE, UTC
created	DATETIME	Fecha de creación (carrito)	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.2 Entidad: order_item_agro
Línea de pedido que referencia una variación de producto específica con cantidad y precio.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
order_id	INT	Pedido padre	FK order_agro.id, NOT NULL, INDEX
suborder_id	INT	Sub-pedido (por productor)	FK suborder_agro.id, NULLABLE, INDEX
variation_id	INT	Variación de producto	FK product_variation_agro.id, NOT NULL
producer_id	INT	Productor del item	FK producer_profile.id, NOT NULL, INDEX
title	VARCHAR(255)	Título snapshot	NOT NULL
sku	VARCHAR(64)	SKU snapshot	NOT NULL
quantity	INT	Cantidad pedida	NOT NULL, > 0
unit_price	DECIMAL(10,2)	Precio unitario al comprar	NOT NULL, >= 0
total_price	DECIMAL(10,2)	Precio total (qty * unit)	NOT NULL, >= 0
tax_rate	DECIMAL(4,2)	% IVA aplicado	DEFAULT 10.00
tax_amount	DECIMAL(10,2)	Importe de IVA	DEFAULT 0
weight_total	DECIMAL(8,3)	Peso total del item	NULLABLE
lot_number	VARCHAR(32)	Lote asignado	NULLABLE
expiry_date	DATE	Caducidad del lote	NULLABLE
item_state	VARCHAR(32)	Estado del item	ENUM: pending|confirmed|preparing|shipped|delivered|cancelled
created	DATETIME	Fecha añadido	NOT NULL, UTC
2.3 Entidad: suborder_agro
Sub-pedido que agrupa los items de un mismo productor. Permite gestión independiente y tracking por productor.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
suborder_number	VARCHAR(32)	Número de sub-pedido	UNIQUE, formato: AGR-YYYYMMDD-XXXX-P1
order_id	INT	Pedido padre	FK order_agro.id, NOT NULL, INDEX
producer_id	INT	Productor responsable	FK producer_profile.id, NOT NULL, INDEX
state	VARCHAR(32)	Estado del sub-pedido	ENUM (ver 2.4)
subtotal	DECIMAL(10,2)	Suma de items productor	NOT NULL
shipping_amount	DECIMAL(10,2)	Envío atribuido	DEFAULT 0
commission_rate	DECIMAL(4,2)	% comisión plataforma	NOT NULL
commission_amount	DECIMAL(10,2)	Importe comisión	NOT NULL
producer_payout	DECIMAL(10,2)	Pago neto al productor	NOT NULL
stripe_transfer_id	VARCHAR(64)	ID Transfer Stripe	NULLABLE
payout_state	VARCHAR(32)	Estado del pago	ENUM: pending|transferred|failed
shipment_id	INT	Envío asociado	FK shipment_agro.id, NULLABLE
tracking_number	VARCHAR(64)	Número de seguimiento	NULLABLE
tracking_url	VARCHAR(255)	URL de tracking	NULLABLE
shipped_at	DATETIME	Fecha de envío	NULLABLE
delivered_at	DATETIME	Fecha de entrega	NULLABLE
producer_notes	TEXT	Notas del productor	NULLABLE
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.4 Estados del Pedido (State Machine)
Workflow de estados para order_agro y suborder_agro:
Estado	Descripción	Transiciones Permitidas	Actor
draft	Carrito activo	→ pending (checkout completado)	Sistema
pending	Esperando pago	→ paid, → cancelled	Sistema / Cliente
paid	Pago confirmado	→ processing (auto)	Sistema
processing	En preparación	→ ready, → partially_shipped	Productor
ready	Listo para envío/recogida	→ shipped, → picked_up	Productor
shipped	Enviado	→ delivered, → returned	Transportista/Sistema
picked_up	Recogido por cliente	→ completed	Productor
delivered	Entregado	→ completed, → return_requested	Transportista/Cliente
completed	Pedido finalizado	(estado final)	-
cancelled	Cancelado	(estado final)	Cliente/Admin
return_requested	Devolución solicitada	→ returned, → completed	Cliente
returned	Devuelto y reembolsado	(estado final)	Admin
Diagrama de Estados
draft → pending → paid → processing → ready → shipped → delivered → completed
                                    └→ picked_up ────────────────────────┘
       └→ cancelled                              └→ return_requested → returned
 
3. Proceso de Checkout
El checkout de AgroConecta es un proceso optimizado de una sola página con múltiples panes que guían al usuario desde el carrito hasta la confirmación.
3.1 Checkout Panes (Pasos)
Orden	Pane	Contenido / Validación
1	Login / Guest	Login, registro rápido, o continuar como invitado (email requerido)
2	Resumen del Carrito	Items agrupados por productor, cantidades editables, subtotales por productor
3	Dirección de Envío	Formulario de dirección, autocompletado Google Places, validación código postal
4	Método de Entrega	Envío estándar, envío express, recogida en origen (por productor), punto de recogida
5	Fecha de Entrega	Selector de fecha preferida (mín. 2 días, excluye domingos), notas especiales
6	Datos de Facturación	Checkbox 'igual que envío', formulario si diferente, NIF/CIF para factura
7	Cupón / Promoción	Campo de cupón con validación AJAX, descuento aplicado en tiempo real
8	Pago	Stripe Payment Element (tarjeta, Bizum, Google Pay, Apple Pay), guardar tarjeta
9	Revisión Final	Resumen completo, términos y condiciones, botón 'Confirmar Pedido'
10	Confirmación	Mensaje de éxito, número de pedido, resumen enviado por email, CTAs siguientes
3.2 Cálculo de Envío Multi-Vendor
El envío se calcula por cada productor individualmente y se suma al total:
1.	Agrupar items del carrito por producer_id
2.	Para cada productor, calcular peso total de sus items
3.	Obtener zona de envío del productor → dirección del cliente
4.	Aplicar tarifa por peso/zona del productor
5.	Sumar envíos de todos los productores = shipping_total
6.	Mostrar desglose al cliente: 'Envío Finca A: 5€ + Envío Bodega B: 3€'
Opciones de Envío Gratuito
•	Por productor: Envío gratis si subtotal del productor > X€ (configurable por productor)
•	Global: Envío gratis si total del pedido > 100€ (configurable por tenant)
•	Promocional: Cupón de envío gratis aplicable
 
4. Integración de Pagos (Stripe Connect)
El pago se procesa con Stripe Connect usando Destination Charges, que permite cobrar al cliente una vez y distribuir automáticamente a cada productor.
4.1 Flujo de Pago
7.	Cliente completa checkout: Stripe Payment Element captura método de pago
8.	Crear PaymentIntent: amount = total del pedido, sin transfer_data aún
9.	Confirmar pago: PaymentIntent pasa a status 'succeeded'
10.	Crear Transfers: Para cada productor, Transfer separado a su cuenta Connect
11.	Registrar en suborder: stripe_transfer_id, producer_payout, commission_amount
4.2 Cálculo de Comisiones
// Ejemplo: Pedido de 100€ con 2 productores
// Productor A: 60€ (comisión 5%)
// Productor B: 40€ (comisión 7%)

PaymentIntent: 100€ (cobro al cliente)

Transfer A: 57€ (60€ - 3€ comisión) → acct_PRODUCER_A
Transfer B: 37.20€ (40€ - 2.80€ comisión) → acct_PRODUCER_B

Plataforma retiene: 5.80€ (comisiones) - fees Stripe
4.3 Métodos de Pago Soportados
Método	Configuración Stripe	Notas
Tarjeta (Visa, MC, Amex)	Payment Element - card	Default, 3DS automático
Bizum	Payment Element - bizum	Popular en España, mobile-first
Google Pay	Payment Element - google_pay	Requiere dominio verificado
Apple Pay	Payment Element - apple_pay	Requiere verificación Apple
SEPA Direct Debit	Payment Element - sepa_debit	Para pedidos recurrentes B2B
4.4 Gestión de Reembolsos
•	Reembolso total: Refund del PaymentIntent + reversal de Transfers
•	Reembolso parcial (item): Refund proporcional + reversal parcial del Transfer afectado
•	Reembolso por productor: Solo afecta al suborder y Transfer de ese productor
•	Comisión en reembolso: La plataforma absorbe la comisión (no se cobra al productor)
 
5. Sistema de Notificaciones
El sistema envía notificaciones automáticas por email y push ante cada cambio de estado del pedido, tanto al cliente como al productor.
5.1 Notificaciones al Cliente
Evento	Email	Push (App)
Pedido confirmado	Confirmación con resumen, número, link seguimiento	'Tu pedido #AGR-XXX está confirmado'
Pago recibido	Recibo de pago con desglose	(incluido en confirmación)
En preparación	'Tus productos están siendo preparados'	'Pedido en preparación'
Enviado	Tracking number + URL de seguimiento	'Tu pedido ha sido enviado. Sigue tu envío →'
En reparto	(si transportista notifica)	'Tu pedido llegará hoy'
Entregado	Confirmación entrega + solicitud de reseña	'Pedido entregado. ¿Qué te ha parecido?'
Listo para recoger	Ubicación, horarios, código de recogida	'Tu pedido está listo en [Productor]'
Cancelado	Motivo + confirmación de reembolso	'Tu pedido ha sido cancelado'
5.2 Notificaciones al Productor
Evento	Email	Dashboard Alert
Nuevo pedido	Detalle de items, dirección, instrucciones	Badge + sonido + notificación push
Pedido pagado	Confirmación de fondos + fecha estimada payout	'Pago confirmado: XX€'
Recordatorio preparación	Si pedido > 24h en 'processing'	'Tienes pedidos pendientes de preparar'
Cliente recogió	Confirmación de entrega	'Pedido #XXX recogido'
Devolución solicitada	Detalle y motivo de la devolución	Alerta prioritaria
Payout realizado	Resumen de transferencia a cuenta bancaria	'Transferencia: XX€ enviada a tu cuenta'
Nueva reseña	Texto de reseña + rating + link responder	'Tienes una nueva reseña (★★★★☆)'
5.3 Templates de Email
•	Sistema: Symfony Mailer + Twig templates
•	Diseño: Responsive, branding del tenant, modo oscuro compatible
•	Tracking: Pixel de apertura + UTM en enlaces para ActiveCampaign
•	Idioma: Según preferencia del usuario (ES default, EN disponible)
 
6. Flujos de Automatización (ECA)
Los siguientes flujos ECA automatizan el ciclo de vida del pedido desde la confirmación hasta el completado.
6.1 ECA-ORDER-001: Pedido Confirmado
Trigger: order_agro.state cambia a 'paid'
12.	Generar order_number único (AGR-YYYYMMDD-XXXX)
13.	Crear suborder_agro para cada productor con items
14.	Calcular commission_amount y producer_payout por suborder
15.	Reservar stock de cada variación (stock_quantity -= quantity)
16.	Cambiar estado a 'processing'
17.	Enviar email de confirmación al cliente
18.	Enviar notificación de nuevo pedido a cada productor
19.	Webhook order.confirmed a Make.com (CRM, contabilidad)
6.2 ECA-ORDER-002: Suborder Enviado
Trigger: suborder_agro.state cambia a 'shipped'
20.	Verificar que tracking_number está informado
21.	Actualizar shipped_at = NOW()
22.	Enviar email al cliente con tracking
23.	Si todos los suborders están 'shipped': cambiar order.state a 'shipped'
24.	Webhook suborder.shipped a Make.com
6.3 ECA-ORDER-003: Pedido Entregado
Trigger: order_agro.state cambia a 'delivered'
25.	Actualizar delivered_at = NOW() en todos los suborders
26.	Programar Transfers de Stripe a productores (T+2)
27.	Enviar email de confirmación de entrega
28.	Programar email de solicitud de reseña (delay: 3 días)
29.	Actualizar total_sales del productor
30.	Asignar créditos de impacto al cliente
6.4 ECA-ORDER-004: Carrito Abandonado
Trigger: Cron cada hora
31.	Buscar carritos con state='draft' AND changed < NOW() - 2 horas AND email IS NOT NULL
32.	Verificar que no se ha enviado recordatorio en últimas 24h
33.	Enviar email de recuperación con items del carrito
34.	Opcionalmente incluir cupón de descuento (10% off)
35.	Marcar carrito como 'reminder_sent'
36.	Webhook cart.abandoned a ActiveCampaign
6.5 ECA-ORDER-005: Payout a Productores
Trigger: Cron diario a las 10:00
37.	Buscar suborders con state='delivered' AND payout_state='pending' AND delivered_at < NOW() - 48h
38.	Para cada suborder: crear Stripe Transfer al producer
39.	Actualizar stripe_transfer_id y payout_state='transferred'
40.	Enviar email de confirmación de payout al productor
41.	Registrar en FOC para contabilidad
 
7. APIs de Pedidos
Endpoints para gestión de pedidos desde frontend y aplicaciones externas.
7.1 Endpoints de Cliente
Método	Endpoint	Descripción
GET	/api/v1/cart	Obtener carrito actual del usuario
POST	/api/v1/cart/items	Añadir item al carrito (variation_id, quantity)
PATCH	/api/v1/cart/items/{id}	Actualizar cantidad de item
DELETE	/api/v1/cart/items/{id}	Eliminar item del carrito
POST	/api/v1/cart/coupon	Aplicar cupón de descuento
DELETE	/api/v1/cart/coupon	Eliminar cupón aplicado
POST	/api/v1/checkout	Iniciar proceso de checkout (crea PaymentIntent)
POST	/api/v1/checkout/complete	Confirmar pedido tras pago exitoso
GET	/api/v1/orders	Listar pedidos del usuario autenticado
GET	/api/v1/orders/{number}	Detalle de pedido con items y tracking
POST	/api/v1/orders/{number}/cancel	Solicitar cancelación (si estado permite)
7.2 Endpoints de Productor
Método	Endpoint	Descripción
GET	/api/v1/producer/orders	Listar suborders del productor (filtros: state, date)
GET	/api/v1/producer/orders/{id}	Detalle de suborder con items y datos cliente
POST	/api/v1/producer/orders/{id}/confirm	Confirmar recepción del pedido
POST	/api/v1/producer/orders/{id}/ready	Marcar como listo para envío/recogida
POST	/api/v1/producer/orders/{id}/ship	Marcar como enviado (body: tracking_number, carrier)
POST	/api/v1/producer/orders/{id}/pickup	Confirmar recogida por cliente
GET	/api/v1/producer/payouts	Historial de payouts recibidos
 
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades order_agro, order_item_agro, suborder_agro. Migrations. State machine config.	47_Commerce_Core
Sprint 2	Semana 3-4	Carrito multi-vendor. Checkout panes. Cálculo de envío por productor.	Sprint 1
Sprint 3	Semana 5-6	Integración Stripe Payment Element. Split payments. Transfers a productores.	Sprint 2 + Stripe
Sprint 4	Semana 7-8	Sistema de notificaciones. Email templates. Integración ActiveCampaign.	Sprint 3
Sprint 5	Semana 9-10	Flujos ECA completos. Panel de pedidos cliente. Panel de pedidos productor.	Sprint 4 + ECA
Sprint 6	Semana 11-12	APIs REST. Webhooks Make.com. Reembolsos. QA completo. Go-live.	Sprint 5
--- Fin del Documento ---
49_AgroConecta_Order_System_v1.docx | Jaraba Impact Platform | Enero 2026
