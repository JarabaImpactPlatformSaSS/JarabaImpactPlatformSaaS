SISTEMA CHECKOUT FLOW
Flujo de Compra Optimizado para Comercio de Proximidad
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	68_ComercioConecta_Checkout_Flow
Dependencias:	62_Commerce_Core, 67_Order_System, 66_Product_Catalog
Base:	50_AgroConecta_Checkout_Flow (~65% reutilizable)
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Checkout para ComercioConecta. El checkout está diseñado para maximizar la conversión en comercio de proximidad, ofreciendo una experiencia rápida, móvil-first, y con múltiples opciones de pago y entrega adaptadas al mercado español.
1.1 Objetivos del Sistema
• Checkout en 3 pasos o menos (optimizado para móvil)
• Soporte para guest checkout (sin registro obligatorio)
• Múltiples métodos de pago: Tarjeta, Bizum, Apple Pay, Google Pay
• Opción de pago en tienda para Click & Collect
• Cálculo de envío en tiempo real con múltiples opciones
• Aplicación de cupones, ofertas flash, y descuentos de fidelización
1.2 Métricas de Conversión Objetivo
Métrica	Benchmark Sector	Objetivo ComercioConecta
Cart-to-Checkout Rate	45%	55%
Checkout Completion Rate	65%	75%
Mobile Conversion Rate	2.5%	3.5%
Guest Checkout Usage	40%	50%
Tiempo medio checkout	3-4 min	< 2 min
1.3 Flujo de Alto Nivel
┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐ │  CART   │───▶│ CONTACT │───▶│DELIVERY │───▶│ PAYMENT │───▶│ CONFIRM │ │         │    │  INFO   │    │ OPTIONS │    │         │    │         │ └─────────┘    └─────────┘    └─────────┘    └─────────┘    └─────────┘      │              │              │              │              │      │              │              │              │              │    Items        Email/Tel      Envío vs       Tarjeta        Resumen    Totals       Dirección      Click&Coll     Bizum          Tracking    Cupones      Guest/Login    Fecha/Hora     Apple Pay      Factura
 
2. Carrito de Compra
2.1 Funcionalidades del Carrito
• Añadir/eliminar productos con selección de variación (talla, color)
• Modificar cantidades con validación de stock en tiempo real
• Guardar para después (wishlist)
• Persistencia cross-device para usuarios logueados
• Carrito abandonado: recuperación vía email/push
• Mini-cart en header con preview
2.2 CartService
<?php namespace Drupal\jaraba_checkout\Service;  class CartService {    // Gestión del carrito   public function getCart(?int $userId = null): Cart;   public function addItem(Cart $cart, int $variationId, int $quantity, array $options = []): CartItem;   public function updateQuantity(Cart $cart, int $itemId, int $quantity): CartItem;   public function removeItem(Cart $cart, int $itemId): void;   public function clearCart(Cart $cart): void;      // Validaciones   public function validateStock(Cart $cart): StockValidationResult;   public function validatePrices(Cart $cart): PriceValidationResult;   public function checkMerchantMinimums(Cart $cart): MinimumCheckResult;      // Cupones y descuentos   public function applyCoupon(Cart $cart, string $code): CouponResult;   public function removeCoupon(Cart $cart, string $code): void;   public function applyFlashOffer(Cart $cart, int $offerId): void;   public function applyLoyaltyDiscount(Cart $cart, int $points): void;      // Cálculos   public function calculateTotals(Cart $cart): CartTotals;   public function getEstimatedShipping(Cart $cart, Address $destination): array;      // Persistencia   public function saveCart(Cart $cart): void;   public function mergeGuestCart(Cart $guestCart, int $userId): Cart; }
2.3 Estructura del Carrito
// Cart object structure {   "cart_id": "cart_abc123",   "user_id": null,  // null = guest   "session_id": "sess_xyz789",   "items": [     {       "item_id": 1,       "variation_id": 456,       "product_id": 123,       "title": "Camiseta Básica - M - Blanco",       "quantity": 2,       "unit_price": 29.95,       "original_price": 39.95,  // Si hay descuento       "discount_amount": 20.00,       "line_total": 39.90,       "merchant_id": 789,       "stock_available": 15,       "image_url": "https://..."     }   ],   "coupons": [     { "code": "VERANO20", "discount": 10.00, "type": "percentage" }   ],   "flash_offers": [     { "offer_id": 101, "discount": 5.00 }   ],   "subtotal": 39.90,   "discount_total": 15.00,   "shipping_estimate": 4.95,   "tax_total": 5.22,   "grand_total": 35.07,   "created_at": "2026-01-17T10:00:00Z",   "updated_at": "2026-01-17T10:30:00Z" }
 
2.4 Validación de Stock en Tiempo Real
El carrito valida el stock disponible en cada interacción para evitar sobreventa:
// Stock validation durante checkout public function validateStock(Cart $cart): StockValidationResult {   $issues = [];      foreach ($cart->items as $item) {     $available = $this->stockService->getAvailableStock(       $item->variation_id,       $cart->fulfillment_type,       $cart->fulfillment_location_id     );          if ($available < $item->quantity) {       if ($available === 0) {         $issues[] = new StockIssue(           $item,            'out_of_stock',            "Producto agotado"         );       } else {         $issues[] = new StockIssue(           $item,            'insufficient',            "Solo quedan {$available} unidades",           $available         );       }     }   }      return new StockValidationResult(     valid: empty($issues),     issues: $issues   ); }
2.5 Carrito Abandonado
Trigger	Tiempo	Acción	Canal
Usuario sale sin completar	1 hora	Push notification (si app)	Push
Carrito inactivo	4 horas	Email recordatorio #1	Email
Sin actividad	24 horas	Email con incentivo (-5%)	Email
Sin actividad	72 horas	Email final + productos relacionados	Email
Carrito expirado	7 días	Limpiar carrito guest	Sistema
 
3. Proceso de Checkout
3.1 Paso 1: Información de Contacto
El primer paso captura la información esencial del cliente:
Campo	Tipo	Requerido	Validación
Email	email	Sí	Formato email válido, dominio MX existe
Teléfono	tel	Sí	Formato español (+34), móvil preferido
Nombre	text	Sí	Min 2 caracteres
Apellidos	text	Sí	Min 2 caracteres
DNI/NIF	text	Solo factura	Formato válido español
Contraseña	password	No (opcional)	Min 8 chars, crear cuenta
3.1.1 Guest vs. Registro
• Guest checkout: Solo email + teléfono, sin contraseña
• Registro opcional: Checkbox "Crear cuenta para futuras compras"
• Login existente: Detecta email registrado y ofrece login
• Social login: Google, Apple ID (opcional)
3.2 Paso 2: Opciones de Entrega
El cliente elige cómo recibir su pedido:
3.2.1 Opción A: Envío a Domicilio
Campo	Tipo	Requerido	Notas
Dirección	text	Sí	Autocompletado Google Places
Número/Piso	text	Sí	Portal, piso, puerta
Código Postal	text	Sí	5 dígitos, valida cobertura
Ciudad	text	Sí	Autorellenado desde CP
Provincia	select	Sí	Autorellenado desde CP
Instrucciones	textarea	No	Portero, horario preferido
3.2.2 Opción B: Click & Collect
• Mapa interactivo con tiendas disponibles
• Filtro por distancia, stock disponible
• Horarios de apertura visibles
• Selección de franja horaria (si aplica)
• Tiempo estimado de preparación
3.2.3 Opciones de Envío
Opción	Tiempo	Coste Típico	Descripción
Estándar	3-5 días	3.95€ - 5.95€	Envío económico
Express	24-48h	6.95€ - 9.95€	Entrega rápida
Mismo día	Hoy	9.95€ - 14.95€	Si pedido antes de 14h
Click & Collect	2-24h	Gratis	Recogida en tienda
Envío gratis	>X€	0€	Pedidos superiores a umbral
 
3.3 Paso 3: Método de Pago
3.3.1 Métodos de Pago Disponibles
Método	Proveedor	Comisión	Disponibilidad
Tarjeta (Visa/MC/Amex)	Stripe	1.4% + 0.25€	Siempre
Bizum	Stripe (SEPA) / Redsys	0.5% (max 1€)	España
Apple Pay	Stripe	1.4% + 0.25€	iOS/Safari
Google Pay	Stripe	1.4% + 0.25€	Android/Chrome
PayPal	PayPal	2.9% + 0.35€	Opcional por comercio
Pago en tienda	N/A	0%	Solo Click & Collect
Transferencia	Manual	0%	Solo B2B / pedidos grandes
3.3.2 Integración Stripe
// Stripe Payment Intent para checkout async function createPaymentIntent(cart, customer) {   const paymentIntent = await stripe.paymentIntents.create({     amount: Math.round(cart.grand_total * 100), // Céntimos     currency: 'eur',     customer: customer.stripe_customer_id,     metadata: {       order_id: cart.cart_id,       merchant_id: cart.items[0].merchant_id,       tenant_id: cart.tenant_id     },     // Stripe Connect: Destination Charges     transfer_data: {       destination: merchant.stripe_account_id,       amount: Math.round(merchantAmount * 100) // Neto tras comisión     },     payment_method_types: [       'card',       'link',  // Stripe Link (1-click)     ],     // Para Apple Pay / Google Pay     automatic_payment_methods: {       enabled: true     }   });      return paymentIntent.client_secret; }
3.3.3 Integración Bizum
Bizum se integra como método de pago alternativo muy popular en España:
// Flujo Bizum via Stripe SEPA o Redsys // Opción 1: Stripe + SEPA Instant const bizumPayment = await stripe.paymentIntents.create({   amount: amount,   currency: 'eur',   payment_method_types: ['sepa_debit'],   payment_method_data: {     type: 'sepa_debit',     sepa_debit: { iban: customerIban },     billing_details: { name: customerName, email: customerEmail }   },   mandate_data: {     customer_acceptance: {       type: 'online',       online: { ip_address: clientIp, user_agent: userAgent }     }   } });  // Opción 2: Redsys directo (si el comercio tiene TPV) // Redirect a pasarela Redsys con Ds_Merchant_PayMethods = 'z' (Bizum)
 
3.3.4 Pago en Tienda (Click & Collect)
Para Click & Collect, el cliente puede optar por pagar al recoger:
• El pedido se crea con estado 'pending_payment'
• Stock se reserva (no se decrementa hasta pago)
• Cliente paga en tienda con cualquier método (efectivo, tarjeta)
• Comerciante marca como pagado en el panel/POS
• Si no recoge en 48h, pedido se cancela y stock se libera
3.4 Paso 4: Confirmación
• Resumen completo del pedido
• Número de pedido visible
• Tiempo estimado de entrega/recogida
• Código de recogida (si Click & Collect)
• Enlace a tracking
• Botón para descargar factura
• Sugerencia de productos relacionados
 
4. Sistema de Cupones y Descuentos
4.1 Tipos de Cupones
Tipo	Ejemplo	Aplicación	Stackable
Porcentaje	VERANO20 = 20%	Sobre subtotal o productos	Sí (config)
Cantidad fija	DESCUENTO10 = 10€	Sobre total	Sí (config)
Envío gratis	ENVIOGRATIS	Elimina coste envío	Sí
BOGO	2X1CAMISETAS	2ª unidad gratis/descuento	No
Primera compra	BIENVENIDO15	Solo nuevos clientes	No
Fidelización	VIP25	Solo clientes nivel X	Sí
4.2 Entidad: coupon
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
code	VARCHAR(32)	Código del cupón	UNIQUE, NOT NULL, UPPER
merchant_id	INT	Comercio (null=plataforma)	FK, NULLABLE
tenant_id	INT	Tenant	FK, NOT NULL
name	VARCHAR(128)	Nombre interno	NOT NULL
description	TEXT	Descripción pública	NULLABLE
discount_type	VARCHAR(16)	Tipo de descuento	ENUM: percentage|fixed|shipping|bogo
discount_value	DECIMAL(10,2)	Valor del descuento	NOT NULL
min_purchase	DECIMAL(10,2)	Compra mínima	DEFAULT 0
max_discount	DECIMAL(10,2)	Descuento máximo	NULLABLE
applies_to	VARCHAR(32)	Ámbito de aplicación	ENUM: order|product|category|shipping
product_ids	JSON	Productos aplicables	Array, NULLABLE
category_tids	JSON	Categorías aplicables	Array, NULLABLE
exclude_sale_items	BOOLEAN	Excluir ya rebajados	DEFAULT FALSE
usage_limit	INT	Usos totales permitidos	NULLABLE, 0=ilimitado
usage_count	INT	Usos actuales	DEFAULT 0
per_customer_limit	INT	Usos por cliente	DEFAULT 1
valid_from	DATETIME	Inicio validez	NULLABLE
valid_until	DATETIME	Fin validez	NULLABLE
first_purchase_only	BOOLEAN	Solo primera compra	DEFAULT FALSE
min_loyalty_level	VARCHAR(16)	Nivel fidelización mínimo	NULLABLE
is_active	BOOLEAN	Cupón activo	DEFAULT TRUE
created	DATETIME	Fecha creación	NOT NULL
 
4.3 CouponService
<?php namespace Drupal\jaraba_checkout\Service;  class CouponService {    public function validate(string $code, Cart $cart, ?int $userId): ValidationResult;   public function apply(Coupon $coupon, Cart $cart): DiscountResult;   public function remove(Coupon $coupon, Cart $cart): void;      public function calculateDiscount(Coupon $coupon, Cart $cart): float;   public function getApplicableItems(Coupon $coupon, Cart $cart): array;      public function incrementUsage(Coupon $coupon, int $userId): void;   public function checkUsageLimit(Coupon $coupon, int $userId): bool;      public function generateBulkCodes(array $config, int $quantity): array;   public function deactivateExpired(): int; }  class ValidationResult {   public bool $valid;   public ?string $error_code;  // invalid|expired|usage_limit|min_purchase|not_applicable   public ?string $error_message;   public ?float $potential_discount; }
4.4 Prioridad de Descuentos
Cuando hay múltiples descuentos, se aplican en este orden:
1. Descuentos de producto (precio rebajado en catálogo)
2. Flash Offers activas
3. Cupones de porcentaje
4. Cupones de cantidad fija
5. Descuentos de fidelización
6. Cupones de envío gratis
4.5 Configuración de Stacking
// Configuración por tenant de cómo se combinan descuentos $stackingRules = [   'allow_multiple_coupons' => false,  // Solo 1 cupón por pedido   'allow_coupon_with_flash' => true,  // Cupón + Flash Offer = OK   'allow_coupon_with_sale' => false,  // Cupón no aplica a ya rebajados   'allow_loyalty_with_coupon' => true, // Puntos + cupón = OK   'max_total_discount_percent' => 50,  // Máximo 50% descuento total ];
 
5. Cálculo de Impuestos
5.1 Tipos de IVA en España
Tipo	Porcentaje	Productos Aplicables
General	21%	Ropa, electrónica, accesorios, mayoría
Reducido	10%	Alimentación procesada, hostelería
Superreducido	4%	Pan, leche, frutas, verduras, libros
Exento	0%	Servicios sanitarios, educación
Canarias (IGIC)	7%	Tipo general en Canarias
Ceuta/Melilla (IPSI)	0.5-10%	Según producto
5.2 TaxService
<?php namespace Drupal\jaraba_checkout\Service;  class TaxService {    public function calculateTax(Cart $cart, Address $shipping, Address $billing): TaxResult;      public function getTaxRateForProduct(ProductVariation $variation, Address $destination): float;   public function getTaxRateByCategory(int $categoryTid, string $region): float;      public function isIntracomunitario(Address $billing): bool;   public function applyReverseCharge(Cart $cart): void;  // B2B UE      public function formatForInvoice(TaxResult $tax): array; }  class TaxResult {   public float $subtotal_without_tax;   public array $tax_lines;  // [{rate: 21, amount: 10.50, name: 'IVA 21%'}]   public float $total_tax;   public float $total_with_tax;   public string $tax_region;  // ES, ES-CN, ES-CE, ES-ML }
5.3 Reglas de Aplicación
• Península y Baleares: IVA según tipo de producto
• Canarias: IGIC en lugar de IVA (7% general)
• Ceuta y Melilla: IPSI en lugar de IVA
• UE (B2B con VAT válido): Reverse charge, 0%
• Extra-UE: Sin IVA, posibles aranceles en destino
 
6. CheckoutService
6.1 Servicio Principal
<?php namespace Drupal\jaraba_checkout\Service;  class CheckoutService {    // Inicio de checkout   public function initCheckout(Cart $cart): CheckoutSession;   public function resumeCheckout(string $sessionId): ?CheckoutSession;      // Pasos   public function saveContactInfo(CheckoutSession $session, array $data): StepResult;   public function saveDeliveryOptions(CheckoutSession $session, array $data): StepResult;   public function savePaymentMethod(CheckoutSession $session, string $method): StepResult;      // Validaciones   public function validateStep(CheckoutSession $session, string $step): ValidationResult;   public function canProceedToPayment(CheckoutSession $session): bool;      // Finalización   public function processPayment(CheckoutSession $session, array $paymentData): PaymentResult;   public function createOrder(CheckoutSession $session, PaymentResult $payment): RetailOrder;   public function completeCheckout(CheckoutSession $session): CheckoutResult;      // Utilidades   public function calculateShippingOptions(CheckoutSession $session): array;   public function applyPromoCode(CheckoutSession $session, string $code): PromoResult;   public function getOrderSummary(CheckoutSession $session): OrderSummary; }
6.2 CheckoutSession
Campo	Tipo	Descripción	Restricciones
id	VARCHAR(64)	ID de sesión	PRIMARY KEY, UUID
cart_id	INT	Carrito asociado	FK cart.id, NOT NULL
user_id	INT	Usuario (si logueado)	FK users.uid, NULLABLE
current_step	VARCHAR(32)	Paso actual	ENUM: contact|delivery|payment|review
contact_data	JSON	Datos de contacto	NULLABLE
shipping_address	JSON	Dirección de envío	NULLABLE
billing_address	JSON	Dirección facturación	NULLABLE
fulfillment_type	VARCHAR(32)	Tipo fulfillment elegido	NULLABLE
fulfillment_location_id	INT	Ubicación C&C	NULLABLE
shipping_method	VARCHAR(32)	Método de envío	NULLABLE
shipping_cost	DECIMAL(10,2)	Coste envío calculado	NULLABLE
payment_method	VARCHAR(32)	Método de pago	NULLABLE
payment_intent_id	VARCHAR(64)	Stripe PaymentIntent	NULLABLE
coupon_codes	JSON	Cupones aplicados	Array
totals	JSON	Totales calculados	NULLABLE
expires_at	DATETIME	Expiración sesión	NOT NULL, +2h
created	DATETIME	Fecha creación	NOT NULL
updated	DATETIME	Última actualización	NOT NULL
 
6.3 Flujo de Procesamiento de Pago
// Flujo completo de pago con Stripe async function processPayment(session, paymentMethodId) {   // 1. Validar sesión y stock final   const validation = await validateCheckout(session);   if (!validation.valid) {     throw new CheckoutError(validation.errors);   }      // 2. Crear o actualizar PaymentIntent   let paymentIntent;   if (session.payment_intent_id) {     paymentIntent = await stripe.paymentIntents.update(       session.payment_intent_id,       { amount: session.totals.grand_total * 100 }     );   } else {     paymentIntent = await createPaymentIntent(session);     session.payment_intent_id = paymentIntent.id;     await saveSession(session);   }      // 3. Confirmar pago   const result = await stripe.paymentIntents.confirm(     paymentIntent.id,     { payment_method: paymentMethodId }   );      // 4. Manejar resultado   if (result.status === 'succeeded') {     const order = await createOrder(session, result);     await clearCart(session.cart_id);     await sendConfirmationEmail(order);     return { success: true, order_id: order.id };   } else if (result.status === 'requires_action') {     // 3D Secure requerido     return {        success: false,        requires_action: true,       client_secret: result.client_secret      };   } else {     throw new PaymentError(result.last_payment_error);   } }
6.4 Manejo de 3D Secure
// Frontend: Manejar 3D Secure con Stripe.js async function handle3DSecure(clientSecret) {   const { error, paymentIntent } = await stripe.confirmCardPayment(     clientSecret,     {       payment_method: paymentMethodId,       return_url: `${window.location.origin}/checkout/complete`     }   );      if (error) {     showError(error.message);   } else if (paymentIntent.status === 'succeeded') {     // Pago completado tras 3DS     await finalizeOrder(paymentIntent.id);   } }
 
7. Componentes Frontend
7.1 Arquitectura de Componentes React
// Estructura de componentes del Checkout src/ ├── components/ │   ├── cart/ │   │   ├── CartDrawer.jsx          // Mini-cart lateral │   │   ├── CartPage.jsx            // Página completa carrito │   │   ├── CartItem.jsx            // Línea de producto │   │   ├── CartSummary.jsx         // Resumen con totales │   │   └── CouponInput.jsx         // Input de cupón │   │ │   ├── checkout/ │   │   ├── CheckoutPage.jsx        // Contenedor principal │   │   ├── CheckoutStepper.jsx     // Indicador de pasos │   │   ├── ContactStep.jsx         // Paso 1: Contacto │   │   ├── DeliveryStep.jsx        // Paso 2: Entrega │   │   ├── PaymentStep.jsx         // Paso 3: Pago │   │   ├── ReviewStep.jsx          // Paso 4: Revisión │   │   └── OrderConfirmation.jsx   // Confirmación final │   │ │   ├── payment/ │   │   ├── StripePaymentForm.jsx   // Formulario Stripe Elements │   │   ├── BizumPayment.jsx        // Integración Bizum │   │   ├── ApplePayButton.jsx      // Apple Pay │   │   ├── GooglePayButton.jsx     // Google Pay │   │   └── PayInStoreOption.jsx    // Pagar en tienda │   │ │   └── delivery/ │       ├── AddressForm.jsx         // Formulario dirección │       ├── AddressAutocomplete.jsx // Google Places │       ├── PickupLocationMap.jsx   // Mapa Click & Collect │       ├── ShippingOptions.jsx     // Selector de envío │       └── TimeSlotPicker.jsx      // Selector de franja
7.2 StripePaymentForm
// StripePaymentForm.jsx import { useStripe, useElements, CardElement } from '@stripe/react-stripe-js';  export function StripePaymentForm({ clientSecret, onSuccess, onError }) {   const stripe = useStripe();   const elements = useElements();   const [processing, setProcessing] = useState(false);      const handleSubmit = async (e) => {     e.preventDefault();     if (!stripe || !elements) return;          setProcessing(true);          const { error, paymentIntent } = await stripe.confirmCardPayment(       clientSecret,       {         payment_method: {           card: elements.getElement(CardElement),           billing_details: billingDetails         }       }     );          if (error) {       onError(error.message);     } else if (paymentIntent.status === 'succeeded') {       onSuccess(paymentIntent);     }          setProcessing(false);   };      return (     <form onSubmit={handleSubmit}>       <CardElement options={cardElementOptions} />       <button disabled={!stripe || processing}>         {processing ? 'Procesando...' : 'Pagar'}       </button>     </form>   ); }
 
8. APIs REST
8.1 Endpoints de Carrito
Método	Endpoint	Descripción	Auth
GET	/api/v1/cart	Obtener carrito actual	Session
POST	/api/v1/cart/items	Añadir item al carrito	Session
PATCH	/api/v1/cart/items/{id}	Actualizar cantidad	Session
DELETE	/api/v1/cart/items/{id}	Eliminar item	Session
POST	/api/v1/cart/coupon	Aplicar cupón	Session
DELETE	/api/v1/cart/coupon/{code}	Eliminar cupón	Session
POST	/api/v1/cart/validate	Validar carrito (stock, precios)	Session
DELETE	/api/v1/cart	Vaciar carrito	Session
8.2 Endpoints de Checkout
Método	Endpoint	Descripción	Auth
POST	/api/v1/checkout/init	Iniciar checkout	Session
GET	/api/v1/checkout/{sessionId}	Obtener sesión checkout	Session
PATCH	/api/v1/checkout/{sessionId}/contact	Guardar contacto	Session
PATCH	/api/v1/checkout/{sessionId}/delivery	Guardar entrega	Session
GET	/api/v1/checkout/{sessionId}/shipping-options	Obtener opciones envío	Session
POST	/api/v1/checkout/{sessionId}/payment-intent	Crear PaymentIntent	Session
POST	/api/v1/checkout/{sessionId}/complete	Completar checkout	Session
GET	/api/v1/checkout/{sessionId}/summary	Resumen final	Session
8.3 Endpoints de Utilidades
Método	Endpoint	Descripción	Auth
POST	/api/v1/address/validate	Validar dirección	Público
GET	/api/v1/address/autocomplete	Autocompletado Google	Público
POST	/api/v1/coupon/validate	Validar cupón	Session
GET	/api/v1/pickup-locations	Tiendas Click & Collect	Público
GET	/api/v1/shipping/estimate	Estimación envío	Session
 
9. Flujos de Automatización (ECA)
9.1 ECA-CHK-001: Carrito Abandonado
Trigger: Carrito con items sin actividad > 1 hora
1. Si usuario logueado con push → enviar notificación push
2. Si tiene email → programar email recordatorio (4h)
3. Si no convierte en 24h → email con incentivo
4. Registrar en analytics de abandono
9.2 ECA-CHK-002: Checkout Iniciado
Trigger: POST /api/v1/checkout/init
1. Reservar stock temporalmente (30 min)
2. Crear checkout_session
3. Registrar evento checkout_started en analytics
4. Si abandona checkout → liberar reserva tras expiración
9.3 ECA-CHK-003: Pago Completado
Trigger: Stripe webhook payment_intent.succeeded
1. Convertir checkout_session en retail_order
2. Convertir reservas de stock en asignaciones
3. Generar número de pedido
4. Enviar email de confirmación
5. Notificar al comercio
6. Iniciar flujo de fulfillment
9.4 ECA-CHK-004: Pago Fallido
Trigger: Stripe webhook payment_intent.payment_failed
1. Mantener checkout_session activa
2. Enviar email "Problema con tu pago"
3. Ofrecer método de pago alternativo
4. Si no reintenta en 24h → liberar reserva
9.5 ECA-CHK-005: Cupón Aplicado
Trigger: Cupón aplicado exitosamente
1. Recalcular totales del carrito
2. Verificar que no excede max_total_discount
3. Registrar uso de cupón (para analytics)
4. Mostrar ahorro al cliente
 
10. Optimizaciones de Conversión
10.1 Técnicas de UX
Técnica	Implementación	Impacto Esperado
Express Checkout	Apple Pay / Google Pay en carrito	+15% conversión móvil
Address Autocomplete	Google Places API	-40% errores dirección
Progress Indicator	Stepper visual claro	+10% completion rate
Trust Badges	SSL, métodos pago, garantías	+5% conversión
Stock Urgency	"Solo quedan 3 unidades"	+8% conversión
Saved Cards	Stripe Link / tarjetas guardadas	+20% repeat purchase
Exit Intent Popup	Descuento al intentar salir	+3% recuperación
10.2 Validación en Tiempo Real
• Validación de campos mientras el usuario escribe
• Formato automático de tarjeta (espacios cada 4 dígitos)
• Detección de tipo de tarjeta (Visa/MC/Amex) por BIN
• Validación de código postal con cobertura de envío
• Verificación de email en tiempo real (formato + dominio)
10.3 A/B Testing Sugerido
Test	Variante A	Variante B	Métrica
Checkout steps	3 pasos	1 página accordion	Completion rate
CTA button	"Pagar ahora"	"Completar pedido"	Click rate
Shipping display	Gratis desde X€	Coste visible siempre	AOV
Guest checkout	Por defecto	Login primero	Conversion rate
Express pay position	Arriba del form	Abajo del form	Usage rate
 
11. Seguridad del Checkout
11.1 Cumplimiento PCI DSS
• Datos de tarjeta NUNCA tocan nuestros servidores
• Stripe Elements tokeniza en el cliente
• Solo almacenamos payment_method_id y últimos 4 dígitos
• Nivel de cumplimiento: SAQ A (mínimo)
11.2 Protección contra Fraude
Medida	Implementación	Acción
Stripe Radar	ML anti-fraude incluido	Bloquea transacciones sospechosas
3D Secure 2	SCA compliance	Verificación adicional bancaria
Velocity checks	Max intentos por IP/email	Rate limit 5 intentos/hora
Address Verification	AVS check	Verifica dirección con banco
CVV required	Siempre requerido	Previene uso de tarjetas robadas
Device fingerprint	Stripe.js automatic	Detecta dispositivos sospechosos
11.3 Protección de Datos (RGPD)
• Consentimiento explícito para marketing
• Datos mínimos necesarios
• Encriptación de datos sensibles en reposo
• Derecho al olvido: eliminar datos de guest tras 30 días sin compra
• Checkout sessions expiran y se eliminan tras 7 días
 
12. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	CartService completo. Entidad cart y cart_item. APIs de carrito. Mini-cart component.	62_Commerce_Core
Sprint 2	Semana 3-4	CheckoutService pasos 1-2. ContactStep. DeliveryStep. AddressAutocomplete.	Sprint 1
Sprint 3	Semana 5-6	Integración Stripe completa. PaymentStep. 3D Secure. Apple Pay / Google Pay.	Sprint 2
Sprint 4	Semana 7-8	Sistema de cupones. CouponService. Entidad coupon. Validaciones y stacking.	Sprint 3
Sprint 5	Semana 9-10	TaxService. Cálculo IVA por región. Bizum integration. Pago en tienda.	Sprint 4
Sprint 6	Semana 11-12	Carrito abandonado automation. Analytics. A/B testing setup. QA y go-live.	Sprint 5
12.1 Criterios de Aceptación Sprint 3 (Pagos)
✓ Pago con tarjeta funciona end-to-end
✓ 3D Secure se activa cuando es requerido
✓ Apple Pay funciona en Safari/iOS
✓ Google Pay funciona en Chrome/Android
✓ Webhooks procesan pagos exitosos y fallidos
12.2 Dependencias Externas
• Stripe PHP SDK ^10.0
• Stripe.js + React Stripe.js
• Google Places API (autocomplete)
• React Hook Form (validación)
• Drupal Commerce Cart module
--- Fin del Documento ---
68_ComercioConecta_Checkout_Flow_v1.docx | Jaraba Impact Platform | Enero 2026
