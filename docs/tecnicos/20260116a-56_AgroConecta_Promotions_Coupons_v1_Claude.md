PROMOCIONES Y CUPONES
Descuentos, Códigos Promocionales y Ofertas Especiales
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	56_AgroConecta_Promotions_Coupons
Dependencias:	48_Product_Catalog, 50_Checkout_Flow, Commerce Promotion
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Promociones y Cupones para AgroConecta, que permite crear y gestionar descuentos, códigos promocionales y ofertas especiales para incentivar las ventas, fidelizar clientes y aumentar el valor medio del pedido.
1.1 Objetivos del Sistema
•	Adquisición: Atraer nuevos clientes con ofertas de bienvenida
•	Conversión: Reducir abandono de carrito con descuentos
•	AOV: Aumentar valor medio con umbrales de descuento
•	Fidelización: Recompensar clientes recurrentes
•	Liquidación: Mover stock estancado o de temporada
•	Visibilidad: Promocionar productores nuevos
1.2 Stack Tecnológico
Componente	Tecnología
Motor Promociones	Commerce Promotion (Drupal Commerce 3.x)
Reglas de Oferta	Offer Types: percentage_off, fixed_amount_off, buy_x_get_y
Condiciones	Conditions: order_total, products, customer, date_range
Cupones	Commerce Coupon con códigos únicos y bulk generation
Validación	Real-time en carrito y checkout via AJAX
Programación	Scheduler para activar/desactivar automáticamente
Reporting	Views + custom queries para métricas de uso
Prevención Fraude	Rate limiting, email verification, abuse detection
1.3 Tipos de Promoción
Tipo	Descripción	Requiere Código
Promoción Automática	Se aplica automáticamente si se cumplen condiciones	No
Cupón	Requiere código para activar el descuento	Sí
Descuento Producto	Precio rebajado visible en ficha de producto	No
Bundle / Pack	Precio especial por compra conjunta	No
Envío Gratis	Elimina coste de envío por umbral o código	Opcional
 
2. Arquitectura de Entidades
2.1 Entidad: promotion
Promociones automáticas y ofertas especiales.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
name	VARCHAR(100)	Nombre interno (admin)	NOT NULL
display_name	VARCHAR(100)	Nombre visible al cliente	NULLABLE
description	TEXT	Descripción de la promoción	NULLABLE
order_types	JSON	Tipos de pedido aplicables	DEFAULT ['default']
stores	JSON	Tiendas/tenants aplicables	NULLABLE
offer_type	VARCHAR(64)	Tipo de oferta	NOT NULL, see 2.3
offer_config	JSON	Configuración de la oferta	NOT NULL
conditions	JSON	Condiciones para aplicar	NULLABLE
coupons_enabled	BOOLEAN	Requiere cupón	DEFAULT FALSE
usage_limit	INT	Usos totales permitidos	NULLABLE (ilimitado)
usage_limit_customer	INT	Usos por cliente	DEFAULT 1
current_usage	INT	Contador de usos	DEFAULT 0
start_date	DATETIME	Inicio de vigencia	NULLABLE
end_date	DATETIME	Fin de vigencia	NULLABLE
weight	INT	Prioridad (menor = primero)	DEFAULT 0
status	BOOLEAN	Activa/Inactiva	DEFAULT TRUE
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
2.2 Entidad: coupon
Códigos promocionales vinculados a promociones.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
promotion_id	INT	Promoción asociada	FK promotion.id, NOT NULL
code	VARCHAR(50)	Código del cupón	UNIQUE, NOT NULL, UPPER
usage_limit	INT	Usos totales permitidos	NULLABLE (ilimitado)
usage_limit_customer	INT	Usos por cliente	DEFAULT 1
current_usage	INT	Contador de usos	DEFAULT 0
status	BOOLEAN	Activo/Inactivo	DEFAULT TRUE
created	DATETIME	Fecha de creación	NOT NULL, UTC
2.3 Tipos de Oferta (offer_type)
Offer Type	Descripción	Configuración
order_percentage_off	% descuento sobre total pedido	percentage: 10 (= 10%)
order_fixed_amount_off	Cantidad fija de descuento	amount: 5.00, currency: EUR
product_percentage_off	% descuento en productos específicos	percentage: 20, products: [...]
product_fixed_amount_off	Cantidad fija en productos	amount: 2.00, products: [...]
buy_x_get_y	Compra X unidades, lleva Y gratis	buy: 2, get: 1, products: [...]
free_shipping	Envío gratis	shipping_methods: [...]
fixed_price	Precio fijo para producto/bundle	price: 25.00, products: [...]
 
3. Condiciones de Promoción
Reglas que determinan cuándo se aplica una promoción. Se pueden combinar con operadores AND/OR.
3.1 Condiciones de Pedido
Condición	Descripción	Ejemplo
order_total_price	Total del pedido (>, <, =, >=, <=)	>= €50.00
order_item_count	Número de productos en el carrito	>= 3 productos
order_item_quantity	Cantidad total de unidades	>= 5 unidades
order_currency	Moneda del pedido	= EUR
3.2 Condiciones de Producto
Condición	Descripción	Ejemplo
order_contains_product	Contiene producto(s) específico(s)	IDs: [123, 456]
order_product_category	Contiene producto de categoría(s)	Categoría: Aceites
order_product_producer	Contiene producto de productor(es)	Productor: Finca X
order_product_tag	Contiene producto con tag	Tag: ecológico
3.3 Condiciones de Cliente
Condición	Descripción	Ejemplo
customer_role	Rol del usuario	premium_member
customer_email_domain	Dominio del email	@empresa.com
customer_order_count	Número de pedidos previos	= 0 (nuevo cliente)
customer_total_spent	Total gastado histórico	>= €500
customer_loyalty_tier	Nivel del programa de puntos	gold, platinum
customer_registered_days	Días desde registro	<= 30 (recién registrado)
3.4 Condiciones de Tiempo
Condición	Descripción	Ejemplo
current_date	Fecha actual dentro de rango	15-31 Diciembre
current_time	Hora actual dentro de rango	12:00-14:00
current_day_of_week	Día de la semana	Lunes, Martes
 
4. Gestión de Cupones
Códigos promocionales que los clientes introducen para obtener descuentos.
4.1 Tipos de Cupón
Tipo	Descripción	Ejemplo Código
Genérico	Un código compartido por todos los usuarios	BIENVENIDO10
Único	Código individual, un uso por cliente	ABC-123-XYZ
Bulk Generated	Generación masiva de códigos únicos	NAV2026-XXXXX
Referral	Código personal de usuario para referidos	REF-MARIA-5A2B
4.2 Generación de Códigos
•	Manual: Admin introduce código específico (VERANO2026)
•	Aleatorio: Sistema genera código único (8 caracteres alfanuméricos)
•	Patrón: Prefijo + aleatorio (NAV2026-XXXXX)
•	Bulk: Generar N códigos de una vez (para campañas)
4.3 Validación de Cupón
function validateCoupon(code, cart, customer) {
  const coupon = findCouponByCode(code.toUpperCase());
  
  // 1. Verificar que el cupón existe
  if (!coupon) return { valid: false, error: "Código no válido" };
  
  // 2. Verificar que está activo
  if (!coupon.status) return { valid: false, error: "Cupón desactivado" };
  
  // 3. Verificar promoción asociada activa y en fechas
  const promo = coupon.promotion;
  if (!promo.status) return { valid: false, error: "Promoción inactiva" };
  if (!isWithinDateRange(promo)) return { valid: false, error: "Promoción expirada" };
  
  // 4. Verificar límites de uso
  if (coupon.usage_limit && coupon.current_usage >= coupon.usage_limit)
    return { valid: false, error: "Cupón agotado" };
  
  // 5. Verificar uso por cliente
  const customerUsage = getCustomerUsage(coupon.id, customer.id);
  if (customerUsage >= coupon.usage_limit_customer)
    return { valid: false, error: "Ya has usado este cupón" };
  
  // 6. Verificar condiciones de la promoción
  const conditionsResult = evaluateConditions(promo.conditions, cart, customer);
  if (!conditionsResult.passed)
    return { valid: false, error: conditionsResult.message };
  
  // 7. Calcular descuento
  const discount = calculateDiscount(promo, cart);
  return { valid: true, discount, promotion: promo };
}
 
5. Visualización de Promociones
5.1 Descuento en Ficha de Producto
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│  ┌───────────────┐                                              │
│  │               │  AOVE Picual Premium 500ml                   │
│  │   [IMAGEN]    │  Finca Los Olivos                            │
│  │               │                                              │
│  │  🏷️ -20%     │  ⭐⭐⭐⭐⭐ (72 reseñas)                      │
│  └───────────────┘                                              │
│                                                                 │
│                   €12.50  €15.90                                │
│                   ~~~~~~  ------  (Precio anterior tachado)     │
│                                                                 │
│                   ¡Ahorras €3.40!                               │
│                   Oferta válida hasta 31/01/2026                │
│                                                                 │
│                   [  Añadir al Carrito  ]                       │
└─────────────────────────────────────────────────────────────────┘
5.2 Cupón en Carrito
┌─────────────────────────────────────────────────────────────────┐
│  🛒 TU CARRITO                                                  │
│                                                                 │
│  [img] AOVE Picual 500ml x2              €25.00                 │
│  [img] Queso Manchego Curado             €18.50                 │
│  [img] Miel de Romero 500g               €9.90                  │
│                                                                 │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│  🎫 ¿Tienes un código promocional?                              │
│  ┌────────────────────────────┐  [Aplicar]                      │
│  │ VERANO2026                 │                                 │
│  └────────────────────────────┘                                 │
│                                                                 │
│  ✅ Cupón aplicado: VERANO2026                    [Eliminar]    │
│     10% de descuento en tu pedido                               │
│                                                                 │
│  ─────────────────────────────────────────────────────────────  │
│  Subtotal:                                       €53.40         │
│  Descuento (VERANO2026):                         -€5.34         │
│  Envío:                                          €4.95          │
│  ─────────────────────────────────────────────────────────────  │
│  TOTAL:                                          €53.01         │
│                                                                 │
│              [  Finalizar Compra  ]                             │
└─────────────────────────────────────────────────────────────────┘
5.3 Mensajes de Incentivo
Situación	Mensaje
Cerca de envío gratis	'¡Añade €8.50 más y el envío es gratis!'
Cerca de descuento por volumen	'¡Añade 1 producto más y consigue 10% de descuento!'
Promoción activa no aplicada	'Añade un producto de Aceites para obtener -15%'
Cupón expirado	'Este cupón expiró el 31/12/2025'
Cupón no válido	'El código XXXXX no es válido'
Cupón ya usado	'Ya has utilizado este cupón anteriormente'
 
6. Casos de Uso Comunes
6.1 Bienvenida Nuevos Clientes
Nombre: Descuento Bienvenida 10%
Tipo: Cupón genérico
Código: BIENVENIDO10
Oferta: 10% descuento en todo el pedido
Condiciones: customer_order_count = 0, order_total >= €25
Límite: 1 uso por cliente
6.2 Envío Gratis por Umbral
Nombre: Envío gratis > €50
Tipo: Promoción automática
Oferta: free_shipping
Condiciones: order_total_price >= €50
Vigencia: Permanente
6.3 Black Friday
Nombre: Black Friday 2026
Tipo: Promoción automática
Oferta: 25% descuento en productos seleccionados
Condiciones: product_tag = 'black-friday-2026'
Vigencia: 29/11/2026 00:00 - 02/12/2026 23:59
6.4 Compra 2, Lleva 3
Nombre: 3x2 en Aceites
Tipo: Promoción automática
Oferta: buy_x_get_y: buy 2, get 1 free
Condiciones: product_category = 'aceites'
Nota: El gratuito es el de menor precio
6.5 Descuento por Volumen
Nombre: Descuento escalonado
Tipo: Promoción automática (múltiple)
Ofertas:
  • €50-€99: 5% descuento
  • €100-€199: 10% descuento
  • €200+: 15% descuento
6.6 Referidos
Nombre: Programa de Referidos
Tipo: Cupón único por usuario
Código: REF-{USER}-{RANDOM} (ej: REF-MARIA-5A2B)
Beneficio referido: €10 descuento primera compra
Beneficio referidor: 200 puntos de fidelización
 
7. Prevención de Fraude
Mecanismos para evitar el abuso del sistema de cupones y promociones.
7.1 Medidas de Protección
Medida	Implementación
Límite por cliente	Max 1 uso por email/cuenta. Tracking por user_id y email.
Rate limiting	Max 5 intentos de cupón por minuto por IP/sesión
Email verification	Cupones de alto valor requieren email verificado
Device fingerprint	Detectar múltiples cuentas desde mismo dispositivo
IP tracking	Alertar si misma IP usa cupones en múltiples cuentas
Dirección shipping	Detectar misma dirección de envío en múltiples cuentas
Método de pago	Detectar misma tarjeta en múltiples cuentas
Límite total	Cupones con uso máximo global (ej: primeras 100 compras)
Blacklist	Bloquear emails/IPs/dispositivos problemáticos
7.2 Alertas de Abuso
•	Alerta automática: Si un cupón supera 3x el uso medio esperado
•	Alerta manual: Reportes de usuarios sobre cupones compartidos
•	Dashboard: Vista de cupones con uso anómalo
•	Acción: Desactivar cupón, revertir descuentos fraudulentos
 
8. APIs de Promociones
8.1 Endpoints de Cliente
Método	Endpoint	Descripción
POST	/api/v1/cart/coupon	Aplicar cupón al carrito
DELETE	/api/v1/cart/coupon	Eliminar cupón del carrito
GET	/api/v1/cart/promotions	Ver promociones aplicadas
POST	/api/v1/coupon/validate	Validar cupón (sin aplicar)
GET	/api/v1/me/referral-code	Obtener código de referido
8.2 Endpoints de Admin
Método	Endpoint	Descripción
GET	/api/v1/admin/promotions	Listar promociones
POST	/api/v1/admin/promotions	Crear promoción
PATCH	/api/v1/admin/promotions/{id}	Actualizar promoción
DELETE	/api/v1/admin/promotions/{id}	Eliminar promoción
POST	/api/v1/admin/promotions/{id}/coupons	Crear cupón para promoción
POST	/api/v1/admin/promotions/{id}/coupons/bulk	Generar cupones en bulk
GET	/api/v1/admin/promotions/{id}/stats	Estadísticas de promoción
GET	/api/v1/admin/coupons/{code}/usage	Historial de uso de cupón
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Configuración Commerce Promotion. Entidades promotion y coupon. Offer types básicos.	Commerce Core
Sprint 2	Semana 3-4	Condiciones: order, product, customer. Validación en carrito. UI aplicar cupón.	50_Checkout_Flow
Sprint 3	Semana 5-6	Promociones automáticas. Buy X Get Y. Envío gratis. Programación temporal.	Sprint 2
Sprint 4	Semana 7-8	Admin UI: crear/editar promociones. Generación bulk de cupones. Previsualización.	Sprint 3
Sprint 5	Semana 9-10	Prevención fraude: rate limiting, fingerprint. Alertas de abuso.	Sprint 4
Sprint 6	Semana 11-12	Programa referidos. Reporting y métricas. QA y optimización. Go-live.	53_Customer_Portal
--- Fin del Documento ---
56_AgroConecta_Promotions_Coupons_v1.docx | Jaraba Impact Platform | Enero 2026
