SISTEMA DE PROMOCIONES Y CUPONES
Gestión Avanzada de Descuentos y Campañas
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	72_ComercioConecta_Promotions_Coupons
Dependencias:	62_Commerce_Core, 64_Flash_Offers, 68_Checkout_Flow
Base:	56_AgroConecta_Promotions (~50% reutilizable)
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Promociones y Cupones para ComercioConecta. El sistema proporciona herramientas avanzadas para crear descuentos, campañas promocionales, códigos de cupón, programas de fidelización, y reglas de precio dinámicas orientadas al comercio de proximidad.
1.1 Objetivos del Sistema
• Incrementar conversión con promociones atractivas
• Fomentar recurrencia con programas de fidelización
• Reducir abandono de carrito con incentivos automáticos
• Competir con grandes retailers mediante ofertas locales
• Gestión centralizada de descuentos multi-comercio
• Analytics de efectividad de promociones
1.2 Tipos de Promociones Soportadas
Tipo	Descripción	Ejemplo
Porcentaje	X% de descuento	-20% en toda la compra
Cantidad Fija	X€ de descuento	-10€ en pedidos >50€
Envío Gratis	Sin coste de envío	Envío gratis >30€
BOGO	Buy One Get One	2x1 en camisetas
Bundle	Pack con descuento	3 productos por 25€
Escalado	Descuento progresivo	10% 2 uds, 20% 3+ uds
Regalo	Producto gratis	Regalo con compra >75€
Primera Compra	Solo nuevos clientes	-15% primera compra
Fidelización	Por nivel de cliente	VIP: -10% siempre
Flash Offer	Tiempo limitado	Happy Hour 18-20h
1.3 Diferencias vs. Flash Offers
Aspecto	Promociones/Cupones	Flash Offers (Doc 64)
Duración	Horas a meses	Minutos a horas
Urgencia	Media	Muy alta (countdown)
Aplicación	Código o automática	Automática por geolocalización
Público	Todos o segmentados	Clientes cercanos
Integración	Checkout estándar	Horarios de apertura
Objetivo	Ventas generales	Rotación de stock última hora
 
2. Arquitectura del Sistema
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────┐ │                   PROMOTIONS & COUPONS SYSTEM                       │ ├─────────────────────────────────────────────────────────────────────┤ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │  Promotion   │  │   Coupon     │  │    Discount              │  │ │  │   Manager    │──│   Manager    │──│    Calculator            │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Rule       │  │   Campaign   │  │    Loyalty               │  │ │  │   Engine     │──│   Scheduler  │──│    Integration           │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Code       │  │   Stacking   │  │    Analytics             │  │ │  │   Generator  │──│   Resolver   │──│    Tracker               │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ └───────────────────────────────────────────────────────────────────┘                               │         ┌─────────────────────┼─────────────────────┐         ▼                     ▼                     ▼  ┌────────────┐        ┌────────────┐        ┌────────────┐  │  Checkout  │        │  Product   │        │  Cart      │  │   Flow     │        │  Display   │        │  Widget    │  └────────────┘        └────────────┘        └────────────┘
2.2 Flujo de Aplicación de Descuentos
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐ │  Cart    │───▶│  Check   │───▶│  Apply   │───▶│ Calculate│ │  Items   │    │  Rules   │    │ Discounts│    │  Totals  │ └──────────┘    └──────────┘    └──────────┘    └──────────┘                      │               │                      ▼               ▼               ┌──────────┐    ┌──────────┐               │ Eligibility   │ Stacking │               │ Validator│    │ Resolver │               └──────────┘    └──────────┘  Orden de aplicación: 1. Precios de oferta (sale_price en producto) 2. Promociones automáticas por cantidad 3. Flash Offers activas 4. Cupones de porcentaje 5. Cupones de cantidad fija 6. Descuentos de fidelización 7. Cupones de envío gratis
 
3. Entidades del Sistema
3.1 Entidad: promotion
Promociones automáticas que se aplican sin necesidad de código.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
merchant_id	INT	Comercio (null=plataforma)	FK, NULLABLE
tenant_id	INT	Tenant	FK, NOT NULL
name	VARCHAR(128)	Nombre interno	NOT NULL
label	VARCHAR(255)	Texto visible cliente	NOT NULL, ej: '2x1 en camisetas'
description	TEXT	Descripción completa	NULLABLE
promotion_type	VARCHAR(32)	Tipo de promoción	ENUM: percentage|fixed|bogo|bundle|tiered|gift|shipping
discount_value	DECIMAL(10,2)	Valor del descuento	NOT NULL
discount_unit	VARCHAR(16)	Unidad	ENUM: percent|amount
min_quantity	INT	Cantidad mínima	DEFAULT 1
min_purchase	DECIMAL(10,2)	Compra mínima €	DEFAULT 0
max_discount	DECIMAL(10,2)	Descuento máximo €	NULLABLE
applies_to	VARCHAR(32)	Ámbito aplicación	ENUM: order|product|category|brand
target_ids	JSON	IDs objetivo	Array de product_id, category_tid, brand_id
exclude_sale	BOOLEAN	Excluir productos rebajados	DEFAULT TRUE
customer_segment	VARCHAR(32)	Segmento cliente	ENUM: all|new|returning|vip
valid_from	DATETIME	Inicio validez	NOT NULL
valid_until	DATETIME	Fin validez	NULLABLE
priority	INT	Prioridad (mayor=primero)	DEFAULT 0
is_stackable	BOOLEAN	Acumulable con otros	DEFAULT FALSE
is_active	BOOLEAN	Promoción activa	DEFAULT TRUE
created	DATETIME	Fecha creación	NOT NULL
updated	DATETIME	Última modificación	NOT NULL
 
3.2 Entidad: coupon
Códigos de descuento que requieren introducción manual por el cliente.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
code	VARCHAR(32)	Código del cupón	UNIQUE, UPPER, NOT NULL
merchant_id	INT	Comercio (null=plataforma)	FK, NULLABLE
tenant_id	INT	Tenant	FK, NOT NULL
campaign_id	INT	Campaña asociada	FK coupon_campaign.id, NULLABLE
name	VARCHAR(128)	Nombre interno	NOT NULL
description	TEXT	Descripción	NULLABLE
discount_type	VARCHAR(32)	Tipo descuento	ENUM: percentage|fixed|shipping|bogo|gift
discount_value	DECIMAL(10,2)	Valor descuento	NOT NULL
min_purchase	DECIMAL(10,2)	Compra mínima	DEFAULT 0
max_discount	DECIMAL(10,2)	Descuento máximo	NULLABLE
applies_to	VARCHAR(32)	Ámbito	ENUM: order|product|category|brand|shipping
target_ids	JSON	IDs objetivo	NULLABLE
exclude_sale	BOOLEAN	Excluir rebajados	DEFAULT FALSE
usage_limit	INT	Usos totales máximos	NULLABLE
usage_count	INT	Usos actuales	DEFAULT 0
per_customer_limit	INT	Usos por cliente	DEFAULT 1
first_purchase_only	BOOLEAN	Solo primera compra	DEFAULT FALSE
min_loyalty_level	VARCHAR(16)	Nivel mínimo fidelidad	NULLABLE
valid_from	DATETIME	Inicio validez	NOT NULL
valid_until	DATETIME	Fin validez	NULLABLE
is_single_use	BOOLEAN	Un solo uso total	DEFAULT FALSE
is_active	BOOLEAN	Cupón activo	DEFAULT TRUE
created	DATETIME	Fecha creación	NOT NULL
 
3.3 Entidad: coupon_campaign
Agrupación de cupones para campañas de marketing.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
merchant_id	INT	Comercio	FK, NULLABLE
tenant_id	INT	Tenant	FK, NOT NULL
name	VARCHAR(128)	Nombre de campaña	NOT NULL
description	TEXT	Descripción	NULLABLE
campaign_type	VARCHAR(32)	Tipo	ENUM: general|welcome|recovery|loyalty|seasonal|referral
code_prefix	VARCHAR(8)	Prefijo para códigos	NULLABLE, ej: 'VERANO'
codes_to_generate	INT	Cantidad de códigos	DEFAULT 1
discount_type	VARCHAR(32)	Tipo descuento	NOT NULL
discount_value	DECIMAL(10,2)	Valor descuento	NOT NULL
min_purchase	DECIMAL(10,2)	Compra mínima	DEFAULT 0
budget_total	DECIMAL(12,2)	Presupuesto total €	NULLABLE
budget_used	DECIMAL(12,2)	Presupuesto usado €	DEFAULT 0
valid_from	DATETIME	Inicio campaña	NOT NULL
valid_until	DATETIME	Fin campaña	NULLABLE
is_active	BOOLEAN	Campaña activa	DEFAULT TRUE
created	DATETIME	Fecha creación	NOT NULL
3.4 Entidad: coupon_usage
Registro de uso de cupones para tracking y límites.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
coupon_id	INT	Cupón usado	FK coupon.id, NOT NULL
order_id	INT	Pedido	FK retail_order.id, NOT NULL
user_id	INT	Usuario	FK, NULLABLE
customer_email	VARCHAR(255)	Email cliente	NOT NULL
discount_amount	DECIMAL(10,2)	Descuento aplicado	NOT NULL
order_total	DECIMAL(10,2)	Total del pedido	NOT NULL
used_at	DATETIME	Fecha de uso	NOT NULL
UNIQUE: (coupon_id, order_id)
 
3.5 Entidad: promotion_rule
Reglas condicionales complejas para promociones avanzadas.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
promotion_id	INT	Promoción padre	FK promotion.id, NOT NULL
rule_type	VARCHAR(32)	Tipo de regla	ENUM: condition|action
operator	VARCHAR(16)	Operador	ENUM: and|or|equals|gt|lt|gte|lte|in|not_in|contains
field	VARCHAR(64)	Campo a evaluar	ej: 'cart.total', 'item.category'
value	JSON	Valor de comparación	NOT NULL
action_type	VARCHAR(32)	Tipo de acción	ENUM: discount|gift|shipping|points
action_value	JSON	Valor de acción	NULLABLE
sort_order	INT	Orden de evaluación	DEFAULT 0
3.6 Entidad: gift_with_purchase
Productos regalo que se añaden automáticamente al carrito.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
promotion_id	INT	Promoción asociada	FK promotion.id, NOT NULL
gift_product_id	INT	Producto regalo	FK product_retail.id, NOT NULL
gift_variation_id	INT	Variación específica	FK, NULLABLE
quantity	INT	Cantidad regalo	DEFAULT 1
min_purchase	DECIMAL(10,2)	Compra mínima para regalo	NOT NULL
max_gifts_per_order	INT	Máx regalos por pedido	DEFAULT 1
stock_reserved	INT	Stock reservado regalos	DEFAULT 0
is_active	BOOLEAN	Activo	DEFAULT TRUE
 
4. Servicios Principales
4.1 PromotionService
<?php namespace Drupal\jaraba_promotions\Service;  class PromotionService {    // CRUD   public function create(array $data): Promotion;   public function update(Promotion $promotion, array $data): Promotion;   public function delete(Promotion $promotion): bool;   public function load(int $id): ?Promotion;      // Búsqueda   public function getActivePromotions(int $merchantId = null): array;   public function getApplicablePromotions(Cart $cart): array;   public function getPromotionsByType(string $type): array;      // Aplicación   public function applyToCart(Cart $cart): DiscountResult;   public function applyToItem(CartItem $item, Promotion $promotion): DiscountResult;   public function checkEligibility(Cart $cart, Promotion $promotion): EligibilityResult;      // Validación   public function validatePromotion(Promotion $promotion): ValidationResult;   public function checkDateValidity(Promotion $promotion): bool;   public function checkUsageLimits(Promotion $promotion): bool; }
4.2 CouponService
<?php namespace Drupal\jaraba_promotions\Service;  class CouponService {    // CRUD   public function create(array $data): Coupon;   public function createBatch(CouponCampaign $campaign, int $quantity): array;   public function load(int $id): ?Coupon;   public function loadByCode(string $code): ?Coupon;      // Validación   public function validate(string $code, Cart $cart): ValidationResult;   public function checkCode(string $code): bool;   public function checkUsageLimit(Coupon $coupon): bool;   public function checkCustomerLimit(Coupon $coupon, string $email): bool;   public function checkMinPurchase(Coupon $coupon, Cart $cart): bool;   public function checkDateValidity(Coupon $coupon): bool;   public function checkProductEligibility(Coupon $coupon, Cart $cart): bool;      // Aplicación   public function apply(Coupon $coupon, Cart $cart): DiscountResult;   public function remove(Coupon $coupon, Cart $cart): void;   public function recordUsage(Coupon $coupon, RetailOrder $order): CouponUsage;      // Utilidades   public function incrementUsage(Coupon $coupon): void;   public function getUsageStats(Coupon $coupon): array; }
 
4.3 DiscountCalculatorService
<?php namespace Drupal\jaraba_promotions\Service;  class DiscountCalculatorService {    // Cálculo principal   public function calculate(Cart $cart): DiscountBreakdown;      // Por tipo   public function calculatePercentage(float $base, float $percent, ?float $max): float;   public function calculateFixed(float $base, float $amount): float;   public function calculateBogo(array $items, string $buyX, string $getY): float;   public function calculateTiered(array $items, array $tiers): float;   public function calculateBundle(array $items, array $bundleConfig): float;      // Resolución de stacking   public function resolveStacking(array $discounts): array;   public function applyStackingRules(array $discounts, StackingConfig $config): array;   public function getBestDiscount(array $discounts): Discount;      // Distribución   public function distributeToItems(float $discount, array $items): array;   public function allocateProportionally(float $total, array $items): array; }
4.4 CodeGeneratorService
<?php namespace Drupal\jaraba_promotions\Service;  class CodeGeneratorService {    // Generación   public function generate(int $length = 8, string $prefix = ''): string;   public function generateBatch(int $count, int $length = 8, string $prefix = ''): array;   public function generateUnique(int $length = 8, string $prefix = ''): string;      // Formatos   public function alphanumeric(int $length): string;  // ABC123XY   public function numeric(int $length): string;        // 12345678   public function readable(int $length): string;       // SUMMER-2026-XYZ   public function segmented(int $segments, int $segmentLength): string; // XXXX-XXXX-XXXX      // Validación   public function isUnique(string $code): bool;   public function validateFormat(string $code): bool;      // Prefijos predefinidos   const PREFIXES = [     'welcome' => 'BIENVENIDO',     'recovery' => 'VUELVE',     'loyalty' => 'VIP',     'seasonal' => 'PROMO',     'referral' => 'AMIGO',   ]; }
 
5. Tipos de Promociones Detallados
5.1 Promoción por Porcentaje
// Ejemplo: 20% en toda la compra $promotion = [   'promotion_type' => 'percentage',   'discount_value' => 20,   'discount_unit' => 'percent',   'applies_to' => 'order',   'min_purchase' => 30.00,   'max_discount' => 50.00,  // Máximo 50€ de descuento ];  // Cálculo $cartTotal = 150.00; $discount = min($cartTotal * 0.20, 50.00);  // = 30€ $finalTotal = $cartTotal - $discount;       // = 120€
5.2 Promoción BOGO (Buy One Get One)
// Ejemplo: 2x1 en camisetas $promotion = [   'promotion_type' => 'bogo',   'discount_value' => 100,  // 100% descuento en el segundo   'applies_to' => 'category',   'target_ids' => [12],  // category_id = 12 (Camisetas)   'min_quantity' => 2, ];  // Variantes BOGO 'bogo_config' => [   'buy_quantity' => 2,   'get_quantity' => 1,   'get_discount' => 100,  // 100% = gratis, 50% = mitad precio   'apply_to' => 'cheapest',  // cheapest | expensive | specific ];  // Cálculo con 3 camisetas: 25€, 20€, 15€ // Paga 2 (25€ + 20€), la más barata (15€) gratis $discount = 15.00;
5.3 Promoción Bundle
// Ejemplo: Pack 3 productos por 25€ $promotion = [   'promotion_type' => 'bundle',   'label' => 'Pack Básicos: 3 por 25€',   'bundle_config' => [     'products' => [       ['category_id' => 12, 'quantity' => 1],  // 1 camiseta       ['category_id' => 15, 'quantity' => 1],  // 1 pantalón       ['category_id' => 18, 'quantity' => 1],  // 1 accesorio     ],     'bundle_price' => 25.00,     'allow_variations' => true,   ] ];  // Cálculo // Suma individual: 15€ + 35€ + 8€ = 58€ // Precio bundle: 25€ // Descuento: 33€
 
5.4 Promoción Escalonada (Tiered)
// Ejemplo: Compra más, ahorra más $promotion = [   'promotion_type' => 'tiered',   'label' => 'Compra más, ahorra más',   'tiers' => [     ['min_quantity' => 2, 'discount' => 10, 'unit' => 'percent'],     ['min_quantity' => 3, 'discount' => 15, 'unit' => 'percent'],     ['min_quantity' => 5, 'discount' => 20, 'unit' => 'percent'],   ],   'applies_to' => 'product',   'target_ids' => [101, 102, 103],  // Productos específicos ];  // Alternativa por importe $tiers_by_amount = [   ['min_purchase' => 50, 'discount' => 5, 'unit' => 'amount'],   // -5€   ['min_purchase' => 100, 'discount' => 15, 'unit' => 'amount'], // -15€   ['min_purchase' => 150, 'discount' => 30, 'unit' => 'amount'], // -30€ ];
5.5 Regalo con Compra
// Ejemplo: Regalo gratis con compra > 75€ $promotion = [   'promotion_type' => 'gift',   'label' => 'Regalo sorpresa con tu compra',   'min_purchase' => 75.00, ];  $gift = [   'gift_product_id' => 999,  // ID del producto regalo   'gift_variation_id' => null,  // Cliente elige variación   'quantity' => 1,   'max_gifts_per_order' => 1,   'stock_reserved' => 100,  // 100 unidades reservadas para regalos ];  // El regalo se añade automáticamente al carrito con precio 0€ // Se muestra como "REGALO" en el checkout
5.6 Envío Gratis Condicional
// Ejemplo: Envío gratis en pedidos > 50€ $promotion = [   'promotion_type' => 'shipping',   'label' => 'Envío GRATIS en pedidos +50€',   'discount_value' => 100,  // 100% del envío   'min_purchase' => 50.00,   'shipping_methods' => ['standard', 'express'],  // Métodos aplicables   'max_discount' => 9.95,  // Máximo valor de envío cubierto ];  // Visualización en carrito: // "¡Te faltan 12,50€ para envío GRATIS!"
 
6. Reglas de Stacking (Acumulación)
6.1 StackingResolverService
<?php namespace Drupal\jaraba_promotions\Service;  class StackingResolverService {    // Configuración por defecto   private array $defaultRules = [     'allow_multiple_coupons' => false,     'allow_coupon_with_promotion' => true,     'allow_coupon_with_sale' => false,     'allow_coupon_with_flash' => true,     'allow_loyalty_with_all' => true,     'max_total_discount_percent' => 50,     'max_coupons_per_order' => 1,   ];      public function resolve(array $applicableDiscounts, Cart $cart): array {     // 1. Agrupar por tipo     $grouped = $this->groupByType($applicableDiscounts);          // 2. Verificar compatibilidad     $compatible = $this->filterCompatible($grouped);          // 3. Ordenar por prioridad     $sorted = $this->sortByPriority($compatible);          // 4. Aplicar límites     $limited = $this->applyLimits($sorted, $cart);          return $limited;   }      public function checkCompatibility(Discount $a, Discount $b): bool;   public function getMaxDiscount(Cart $cart): float; }
6.2 Matriz de Compatibilidad
	Cupón %	Cupón €	Promo Auto	Flash Offer	Fidelidad	Envío Gratis
Cupón %	❌	❌	✓	✓	✓	✓
Cupón €	❌	❌	✓	✓	✓	✓
Promo Auto	✓	✓	Config	✓	✓	✓
Flash Offer	✓	✓	✓	❌	✓	✓
Fidelidad	✓	✓	✓	✓	N/A	✓
Envío Gratis	✓	✓	✓	✓	✓	✓
✓ = Compatible, ❌ = Excluyente, Config = Configurable por comercio
6.3 Orden de Aplicación
// Orden de aplicación de descuentos (de primero a último)  1. SALE PRICE (Precio de oferta del producto)    - Se aplica primero, es el precio base    - No acumulable con cupones si exclude_sale = true  2. AUTOMATIC PROMOTIONS (Promociones automáticas)    - BOGO, Bundle, Tiered    - Por prioridad descendente  3. FLASH OFFERS (Ofertas relámpago)    - Si están activas para el momento/ubicación    - Una sola Flash Offer por producto  4. PERCENTAGE COUPONS (Cupones de porcentaje)    - Sobre el subtotal después de promos    - Un solo cupón % permitido  5. FIXED AMOUNT COUPONS (Cupones de cantidad fija)    - Sobre el total después de %    - Un solo cupón € permitido  6. LOYALTY DISCOUNTS (Descuentos de fidelidad)    - Siempre acumulables    - Por nivel del cliente  7. SHIPPING DISCOUNTS (Descuentos de envío)    - Se aplican al final    - Acumulables con todo
 
7. Campañas Automatizadas
7.1 CampaignService
<?php namespace Drupal\jaraba_promotions\Service;  class CampaignService {    // Gestión de campañas   public function create(array $data): CouponCampaign;   public function launch(CouponCampaign $campaign): void;   public function pause(CouponCampaign $campaign): void;   public function end(CouponCampaign $campaign): void;      // Generación de cupones   public function generateCodes(CouponCampaign $campaign): array;   public function assignToCustomers(CouponCampaign $campaign, array $customers): void;      // Distribución   public function sendByEmail(CouponCampaign $campaign, array $emails): void;   public function sendBySms(CouponCampaign $campaign, array $phones): void;   public function sendByPush(CouponCampaign $campaign, array $userIds): void;      // Analytics   public function getStats(CouponCampaign $campaign): CampaignStats;   public function getConversionRate(CouponCampaign $campaign): float;   public function getRoi(CouponCampaign $campaign): float; }
7.2 Tipos de Campañas Predefinidas
Tipo	Trigger	Descuento Típico	Objetivo
Welcome	Registro de usuario	15% primera compra	Conversión nuevos
Recovery	Carrito abandonado 24h	10% o envío gratis	Recuperar ventas
Win-back	Sin compra en 60 días	20% en próxima	Reactivar clientes
Birthday	Cumpleaños del cliente	15% + regalo	Fidelización
Referral	Invita a un amigo	10€ cada uno	Adquisición
Loyalty	Alcanzar nivel VIP	Acceso a ofertas	Retención
Seasonal	Black Friday, Navidad	Variable	Ventas estacionales
Stock Clearance	Stock bajo rotación	30-50%	Liquidación
7.3 Campaña de Recuperación de Carrito
// Flujo automatizado de recuperación  // ECA-PROMO-RECOVERY: Carrito abandonado // Trigger: Cart sin actividad > 1 hora, tiene email  $recoveryFlow = [   // Paso 1: 1 hora después   [     'delay' => '1 hour',     'action' => 'email',     'template' => 'cart_reminder_1',     'subject' => '¿Olvidaste algo?',     'include_coupon' => false,   ],      // Paso 2: 24 horas después   [     'delay' => '24 hours',     'action' => 'email',     'template' => 'cart_reminder_2',     'subject' => 'Tu carrito te espera + 10% de descuento',     'include_coupon' => true,     'coupon_config' => [       'discount_type' => 'percentage',       'discount_value' => 10,       'valid_hours' => 48,       'single_use' => true,     ]   ],      // Paso 3: 72 horas después (último intento)   [     'delay' => '72 hours',     'action' => 'email',     'template' => 'cart_reminder_final',     'subject' => 'Última oportunidad: Envío GRATIS',     'include_coupon' => true,     'coupon_config' => [       'discount_type' => 'shipping',       'discount_value' => 100,       'valid_hours' => 24,     ]   ] ];
 
7.4 Campaña de Bienvenida
// Campaña automática para nuevos registros  $welcomeCampaign = [   'name' => 'Welcome 2026',   'campaign_type' => 'welcome',   'trigger' => 'user_register',      'coupon_config' => [     'code_prefix' => 'BIENVENIDO',     'discount_type' => 'percentage',     'discount_value' => 15,     'min_purchase' => 25.00,     'first_purchase_only' => true,     'valid_days' => 30,     'per_customer_limit' => 1,   ],      'email_config' => [     'template' => 'welcome_coupon',     'subject' => '¡Bienvenido! Tu 15% de descuento te espera',     'send_delay' => '0',  // Inmediato   ],      // Seguimiento si no usa el cupón   'reminder_config' => [     'enabled' => true,     'delay_days' => 7,     'template' => 'welcome_reminder',     'subject' => 'Tu descuento de bienvenida caduca pronto',   ] ];
7.5 Programa de Referidos
// Sistema de referidos con cupones  $referralProgram = [   'name' => 'Invita y Gana',   'campaign_type' => 'referral',      // Recompensa para quien invita   'referrer_reward' => [     'type' => 'coupon',     'discount_type' => 'fixed',     'discount_value' => 10.00,     'min_purchase' => 30.00,     'trigger' => 'referee_first_purchase',  // Cuando el invitado compra   ],      // Recompensa para el invitado   'referee_reward' => [     'type' => 'coupon',     'discount_type' => 'percentage',     'discount_value' => 15,     'min_purchase' => 25.00,     'first_purchase_only' => true,   ],      // Límites   'limits' => [     'max_referrals_per_user' => 10,     'max_rewards_per_month' => 5,   ],      // Tracking   'tracking' => [     'referral_code_length' => 8,     'code_prefix' => 'AMIGO',     'cookie_days' => 30,   ] ];
 
8. Integración con Programa de Fidelización
8.1 LoyaltyIntegrationService
<?php namespace Drupal\jaraba_promotions\Service;  class LoyaltyIntegrationService {    // Niveles de fidelidad   public function getCustomerLevel(int $userId): string;   public function getLevelBenefits(string $level): array;   public function getAutomaticDiscount(string $level): ?float;      // Puntos   public function calculatePointsEarned(RetailOrder $order): int;   public function canRedeemPoints(int $userId, int $points): bool;   public function redeemForDiscount(int $userId, int $points): Coupon;      // Promociones exclusivas   public function getExclusivePromotions(string $level): array;   public function checkLevelEligibility(Promotion $promotion, int $userId): bool; }
8.2 Niveles y Beneficios
Nivel	Requisito	Descuento Auto	Puntos x €	Beneficios Exclusivos
Bronce	Registro	0%	1 punto	Newsletter, cumpleaños
Plata	100€ acumulados	5%	1.5 puntos	Acceso anticipado ofertas
Oro	500€ acumulados	10%	2 puntos	Envío gratis, regalo anual
Platino	1000€ acumulados	15%	3 puntos	Atención prioritaria, eventos
8.3 Canje de Puntos
// Configuración de canje de puntos $redemptionRules = [   // Puntos a descuento fijo   'points_to_discount' => [     100 => 5.00,   // 100 puntos = 5€     200 => 12.00,  // 200 puntos = 12€ (bonus)     500 => 35.00,  // 500 puntos = 35€ (bonus)   ],      // Puntos a beneficios   'points_to_benefits' => [     50 => 'free_shipping',     // 50 puntos = envío gratis     150 => 'express_shipping', // 150 puntos = envío express gratis     300 => 'gift_wrap',        // 300 puntos = envoltorio regalo   ],      // Reglas   'min_points_redeem' => 50,   'max_discount_percent' => 30,  // Máx 30% del pedido en puntos   'points_expire_months' => 24, ];  // Al canjear, se genera un cupón único de uso inmediato public function redeemForDiscount(int $userId, int $points): Coupon {   $value = $this->calculateRedemptionValue($points);      return $this->couponService->create([     'code' => $this->generateRedemptionCode($userId),     'discount_type' => 'fixed',     'discount_value' => $value,     'usage_limit' => 1,     'valid_until' => new \DateTime('+24 hours'),     'user_id' => $userId,  // Solo este usuario   ]); }
 
9. Analytics de Promociones
9.1 PromotionAnalyticsService
<?php namespace Drupal\jaraba_promotions\Service;  class PromotionAnalyticsService {    // Métricas de promoción   public function getPromotionStats(Promotion $promotion): PromotionStats;   public function getUsageCount(Promotion $promotion): int;   public function getTotalDiscountGiven(Promotion $promotion): float;   public function getAverageOrderValue(Promotion $promotion): float;   public function getConversionLift(Promotion $promotion): float;      // Métricas de cupón   public function getCouponStats(Coupon $coupon): CouponStats;   public function getRedemptionRate(CouponCampaign $campaign): float;   public function getCouponRoi(Coupon $coupon): float;      // Métricas generales   public function getDiscountImpact(\DateTime $from, \DateTime $to): array;   public function getTopPromotions(int $limit = 10): array;   public function getUnderutilizedPromotions(): array;   public function getAbuseIndicators(): array; }
9.2 Métricas Clave (KPIs)
Métrica	Fórmula	Benchmark	Objetivo
Redemption Rate	Cupones usados / Emitidos	15-25%	> 20%
Discount ROI	(Revenue - Descuento) / Descuento	3:1	> 4:1
Incremental Revenue	Revenue con promo - Sin promo	Variable	+ 15%
AOV Lift	AOV con cupón / AOV sin cupón	1.0x	> 1.2x
New Customer %	Nuevos con cupón / Total cupones	30%	> 40%
Abuse Rate	Usos fraudulentos / Total	< 5%	< 2%
9.3 Detección de Abuso
// Patrones de abuso a detectar $abusePatterns = [   // Múltiples cuentas   'multiple_accounts' => [     'same_ip_different_emails' => true,     'similar_names_same_address' => true,     'same_payment_method' => true,   ],      // Uso excesivo   'excessive_usage' => [     'same_coupon_code_shared' => 10,  // > 10 usos desde IPs diferentes     'high_redemption_velocity' => 5,   // > 5 usos en 1 hora   ],      // Patrones sospechosos   'suspicious_patterns' => [     'always_minimum_purchase' => true,     'immediate_cancellation' => true,     'referral_self_loop' => true,   ] ];  // Acciones automáticas public function handleAbuse(AbuseDetection $detection): void {   switch ($detection->severity) {     case 'low':       $this->flagForReview($detection);       break;     case 'medium':       $this->disableCoupon($detection->coupon);       $this->notifyMerchant($detection);       break;     case 'high':       $this->blockUser($detection->userId);       $this->refundAndCancel($detection->orders);       break;   } }
 
10. APIs REST
10.1 Endpoints de Cupones (Cliente)
Método	Endpoint	Descripción	Auth
POST	/api/v1/cart/coupon	Aplicar cupón al carrito	Session
DELETE	/api/v1/cart/coupon/{code}	Eliminar cupón del carrito	Session
GET	/api/v1/coupon/validate/{code}	Validar cupón	Session
GET	/api/v1/my/coupons	Mis cupones disponibles	User
GET	/api/v1/my/coupons/history	Historial de cupones usados	User
10.2 Endpoints de Gestión (Merchant)
Método	Endpoint	Descripción	Auth
GET	/api/v1/promotions	Listar promociones	Merchant
POST	/api/v1/promotions	Crear promoción	Merchant
GET	/api/v1/promotions/{id}	Detalle promoción	Merchant
PATCH	/api/v1/promotions/{id}	Actualizar promoción	Merchant
DELETE	/api/v1/promotions/{id}	Eliminar promoción	Merchant
GET	/api/v1/coupons	Listar cupones	Merchant
POST	/api/v1/coupons	Crear cupón	Merchant
POST	/api/v1/coupons/batch	Crear cupones en lote	Merchant
GET	/api/v1/coupons/{id}/stats	Estadísticas de cupón	Merchant
PATCH	/api/v1/coupons/{id}/deactivate	Desactivar cupón	Merchant
10.3 Endpoints de Campañas
Método	Endpoint	Descripción	Auth
GET	/api/v1/campaigns	Listar campañas	Merchant
POST	/api/v1/campaigns	Crear campaña	Merchant
POST	/api/v1/campaigns/{id}/launch	Lanzar campaña	Merchant
POST	/api/v1/campaigns/{id}/pause	Pausar campaña	Merchant
GET	/api/v1/campaigns/{id}/stats	Estadísticas	Merchant
GET	/api/v1/campaigns/{id}/codes	Códigos generados	Merchant
 
11. Flujos de Automatización (ECA)
11.1 ECA-PROMO-001: Cupón Aplicado
Trigger: POST /api/v1/cart/coupon exitoso
1. Validar cupón (código, fechas, límites)
2. Calcular descuento aplicable
3. Verificar reglas de stacking
4. Actualizar totales del carrito
5. Registrar evento para analytics
11.2 ECA-PROMO-002: Pedido Completado con Cupón
Trigger: Order state = 'completed' AND tiene cupón
1. Incrementar usage_count del cupón
2. Crear registro en coupon_usage
3. Actualizar budget_used de la campaña
4. Calcular y asignar puntos de fidelidad
5. Si es referral → recompensar al referrer
11.3 ECA-PROMO-003: Nuevo Usuario Registrado
Trigger: User created
1. Generar cupón de bienvenida único
2. Enviar email con cupón
3. Programar reminder si no usa en 7 días
11.4 ECA-PROMO-004: Promoción Expirada
Trigger: Cron: promotion.valid_until < NOW()
1. Marcar promoción como inactiva
2. Desactivar cupones asociados
3. Generar reporte final de la promoción
4. Notificar al comercio con resultados
 
12. Componentes Frontend
12.1 CouponInput Component
// CouponInput.jsx export function CouponInput({ cartId, onApply, onRemove }) {   const [code, setCode] = useState('');   const [loading, setLoading] = useState(false);   const [error, setError] = useState(null);   const [appliedCoupon, setAppliedCoupon] = useState(null);      const handleApply = async () => {     setLoading(true);     setError(null);          try {       const response = await fetch('/api/v1/cart/coupon', {         method: 'POST',         body: JSON.stringify({ code: code.toUpperCase() })       });              const data = await response.json();              if (data.success) {         setAppliedCoupon(data.coupon);         onApply(data.discount);       } else {         setError(data.message);       }     } catch (err) {       setError('Error al aplicar el cupón');     } finally {       setLoading(false);     }   };      return (     <div className="coupon-input">       {appliedCoupon ? (         <AppliedCouponTag            coupon={appliedCoupon}            onRemove={() => { setAppliedCoupon(null); onRemove(); }}          />       ) : (         <>           <input             type="text"             value={code}             onChange={(e) => setCode(e.target.value.toUpperCase())}             placeholder="Código de descuento"             maxLength={20}           />           <button onClick={handleApply} disabled={loading || !code}>             {loading ? 'Aplicando...' : 'Aplicar'}           </button>           {error && <p className="error">{error}</p>}         </>       )}     </div>   ); }
12.2 Discount Badge Component
// DiscountBadge.jsx - Badge en productos export function DiscountBadge({ product }) {   const discount = calculateDiscount(product);      if (!discount) return null;      const badges = {     percentage: `${discount.value}%`,     bogo: '2x1',     flash: '⚡ Flash',     new: 'NUEVO',   };      return (     <span className={`discount-badge badge-${discount.type}`}>       {badges[discount.type] || `-${discount.value}€`}     </span>   ); }  // FreeShippingProgress.jsx - Barra de progreso envío gratis export function FreeShippingProgress({ cartTotal, threshold }) {   const remaining = threshold - cartTotal;   const progress = Math.min((cartTotal / threshold) * 100, 100);      return (     <div className="free-shipping-progress">       <div className="progress-bar" style={{ width: `${progress}%` }} />       {remaining > 0 ? (         <p>¡Te faltan <strong>{remaining.toFixed(2)}€</strong> para envío GRATIS!</p>       ) : (         <p className="success">🎉 ¡Tienes envío GRATIS!</p>       )}     </div>   ); }
 
13. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades: promotion, coupon, coupon_usage. CouponService básico. Validación de códigos.	68_Checkout_Flow
Sprint 2	Semana 3-4	PromotionService. Tipos: percentage, fixed, shipping. DiscountCalculatorService.	Sprint 1
Sprint 3	Semana 5-6	Tipos avanzados: BOGO, bundle, tiered, gift. StackingResolverService.	Sprint 2
Sprint 4	Semana 7-8	CampaignService. Generación de códigos. Campañas automatizadas.	Sprint 3
Sprint 5	Semana 9-10	LoyaltyIntegrationService. Canje de puntos. Descuentos por nivel.	Sprint 4
Sprint 6	Semana 11-12	PromotionAnalyticsService. Dashboard. Detección de abuso. QA y go-live.	Sprint 5
13.1 Criterios de Aceptación Sprint 2
✓ Crear y aplicar cupón de porcentaje
✓ Crear y aplicar cupón de cantidad fija
✓ Validar límites de uso (total y por cliente)
✓ Verificar fechas de validez
✓ Aplicar descuento de envío gratis
13.2 Dependencias
• Drupal Commerce Promotion module
• 68_Checkout_Flow (CartService, CheckoutService)
• 64_Flash_Offers (integración)
• Sistema de fidelización (módulo separado)
--- Fin del Documento ---
72_ComercioConecta_Promotions_Coupons_v1.docx | Jaraba Impact Platform | Enero 2026
