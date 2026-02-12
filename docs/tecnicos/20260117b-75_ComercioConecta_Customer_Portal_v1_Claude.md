PORTAL DEL CLIENTE
Mi Cuenta, Pedidos, Fidelización y Preferencias
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	75_ComercioConecta_Customer_Portal
Dependencias:	67_Order_System, 72_Promotions, 73_Reviews
Base:	53_AgroConecta_Customer (~60% reutilizable)
 
1. Resumen Ejecutivo
Este documento especifica el Portal del Cliente para ComercioConecta. El portal proporciona a los clientes una interfaz completa para gestionar su cuenta, ver pedidos, administrar direcciones, acumular puntos de fidelidad, gestionar favoritos y configurar preferencias de comunicación.
1.1 Objetivos del Portal
• Facilitar el seguimiento de pedidos en tiempo real
• Simplificar la gestión de direcciones y métodos de pago
• Incentivar la fidelización con sistema de puntos
• Permitir gestión de favoritos y wishlists
• Centralizar reseñas y contribuciones del cliente
• Respetar preferencias de privacidad y comunicación
1.2 Secciones del Portal
Sección	Descripción	Prioridad
Dashboard	Resumen de cuenta, pedidos recientes, puntos	Crítica
Mis Pedidos	Historial, seguimiento, devoluciones	Crítica
Direcciones	Libreta de direcciones, predeterminadas	Crítica
Métodos de Pago	Tarjetas guardadas, preferencias	Alta
Fidelización	Puntos, nivel, historial, canjear	Alta
Favoritos	Productos guardados, wishlists	Alta
Mis Reseñas	Reseñas escritas, pendientes	Media
Notificaciones	Preferencias de comunicación	Media
Datos Personales	Perfil, contraseña, privacidad	Media
Ayuda	FAQ, contacto, tickets de soporte	Media
1.3 Métricas de Éxito
Métrica	Objetivo	Benchmark
Tasa de registro	>40% de compradores	30%
Uso de cuenta para checkout	>70%	60%
Uso de puntos de fidelidad	>50%	35%
Reseñas por cliente registrado	>0.5	0.3
Tasa de devolución self-service	>80%	60%
NPS de la experiencia de cuenta	>50	40
 
2. Arquitectura del Portal
2.1 Diagrama de Módulos
┌─────────────────────────────────────────────────────────────────────┐ │                      CUSTOMER PORTAL                                │ ├─────────────────────────────────────────────────────────────────────┤ │                                                                     │ │  ┌─────────────────────────────────────────────────────────────┐   │ │  │                    DASHBOARD                                 │   │ │  │  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────────────┐   │   │ │  │  │ Welcome │ │ Recent  │ │ Points  │ │   Quick         │   │   │ │  │  │  Card   │ │ Orders  │ │ Balance │ │   Actions       │   │   │ │  │  └─────────┘ └─────────┘ └─────────┘ └─────────────────┘   │   │ │  └─────────────────────────────────────────────────────────────┘   │ │                                                                     │ │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────┐   │ │  │  Orders  │ │Addresses │ │ Payment  │ │ Loyalty  │ │Wishlist│   │ │  │  Module  │ │  Module  │ │  Module  │ │  Module  │ │ Module │   │ │  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └────────┘   │ │                                                                     │ │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────────────┐   │ │  │ Reviews  │ │ Profile  │ │Settings  │ │       Support        │   │ │  │  Module  │ │  Module  │ │  Module  │ │       Module         │   │ │  └──────────┘ └──────────┘ └──────────┘ └──────────────────────┘   │ │                                                                     │ └─────────────────────────────────────────────────────────────────────┘                               │                     ┌─────────┴─────────┐                     │    Customer API   │                     │  /api/v1/customer │                     └───────────────────┘
2.2 Flujo de Navegación
Login/Register       │       ▼ ┌─────────────┐ │  Dashboard  │◄────────────────────────────────────┐ └─────────────┘                                      │       │                                              │       ├──► Mis Pedidos ──► Detalle ──► Tracking     │       │         │                         │          │       │         └──► Devolución ──► Estado│          │       │                                   │          │       ├──► Direcciones ──► Añadir/Editar │          │       │                                   │          │       ├──► Métodos Pago ──► Añadir/Editar│          │       │                                   │          │       ├──► Mis Puntos ──► Historial       │          │       │         │                         │          │       │         └──► Canjear ─────────────┘          │       │                                              │       ├──► Favoritos ──► Mover a Carrito ────────────┤       │                                              │       ├──► Mis Reseñas ──► Editar                    │       │         │                                    │       │         └──► Pendientes ──► Escribir ────────┤       │                                              │       └──► Configuración ──► Perfil/Notif/Privacidad─┘
 
3. Entidades del Sistema
3.1 Entidad: customer_profile
Perfil extendido del cliente (complementa user de Drupal).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario Drupal	FK users.uid, UNIQUE, NOT NULL
first_name	VARCHAR(64)	Nombre	NOT NULL
last_name	VARCHAR(64)	Apellidos	NOT NULL
phone	VARCHAR(20)	Teléfono	NULLABLE
birth_date	DATE	Fecha nacimiento	NULLABLE
gender	VARCHAR(16)	Género	ENUM: male|female|other|prefer_not_say
avatar	INT	Imagen de perfil	FK file_managed.fid, NULLABLE
locale	VARCHAR(5)	Idioma preferido	DEFAULT 'es'
currency	VARCHAR(3)	Moneda preferida	DEFAULT 'EUR'
timezone	VARCHAR(64)	Zona horaria	DEFAULT 'Europe/Madrid'
loyalty_level	VARCHAR(16)	Nivel fidelidad	ENUM: bronze|silver|gold|platinum
loyalty_points	INT	Puntos actuales	DEFAULT 0
lifetime_points	INT	Puntos totales ganados	DEFAULT 0
lifetime_spent	DECIMAL(12,2)	Total gastado	DEFAULT 0
order_count	INT	Número de pedidos	DEFAULT 0
review_count	INT	Reseñas escritas	DEFAULT 0
referral_code	VARCHAR(16)	Código de referido	UNIQUE
referred_by	INT	Referido por	FK customer_profile.id, NULLABLE
marketing_consent	BOOLEAN	Acepta marketing	DEFAULT FALSE
marketing_consent_at	DATETIME	Fecha consentimiento	NULLABLE
is_verified	BOOLEAN	Email verificado	DEFAULT FALSE
verified_at	DATETIME	Fecha verificación	NULLABLE
last_login	DATETIME	Último acceso	NULLABLE
last_order_at	DATETIME	Último pedido	NULLABLE
created	DATETIME	Fecha registro	NOT NULL
updated	DATETIME	Última modificación	NOT NULL
 
3.2 Entidad: customer_address
Libreta de direcciones del cliente.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
customer_id	INT	Cliente	FK customer_profile.id, NOT NULL, INDEX
label	VARCHAR(64)	Etiqueta	ej: 'Casa', 'Trabajo', 'Padres'
first_name	VARCHAR(64)	Nombre destinatario	NOT NULL
last_name	VARCHAR(64)	Apellidos	NOT NULL
company	VARCHAR(128)	Empresa	NULLABLE
phone	VARCHAR(20)	Teléfono contacto	NOT NULL
street_line1	VARCHAR(255)	Dirección línea 1	NOT NULL
street_line2	VARCHAR(255)	Dirección línea 2	NULLABLE
city	VARCHAR(128)	Ciudad	NOT NULL
province	VARCHAR(64)	Provincia/Estado	NOT NULL
postal_code	VARCHAR(16)	Código postal	NOT NULL
country	VARCHAR(2)	País (ISO 3166-1)	DEFAULT 'ES'
latitude	DECIMAL(10,8)	Latitud	NULLABLE
longitude	DECIMAL(11,8)	Longitud	NULLABLE
is_default_shipping	BOOLEAN	Predeterminada envío	DEFAULT FALSE
is_default_billing	BOOLEAN	Predeterminada facturación	DEFAULT FALSE
delivery_instructions	TEXT	Instrucciones entrega	NULLABLE
is_validated	BOOLEAN	Validada con Google	DEFAULT FALSE
created	DATETIME	Fecha creación	NOT NULL
updated	DATETIME	Última modificación	NOT NULL
3.3 Entidad: customer_wishlist
Listas de deseos y favoritos.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
customer_id	INT	Cliente	FK customer_profile.id, NOT NULL
name	VARCHAR(128)	Nombre de la lista	NOT NULL
description	TEXT	Descripción	NULLABLE
is_default	BOOLEAN	Lista principal	DEFAULT FALSE
is_public	BOOLEAN	Lista pública	DEFAULT FALSE
share_token	VARCHAR(32)	Token para compartir	UNIQUE, NULLABLE
items_count	INT	Número de items	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL
updated	DATETIME	Última modificación	NOT NULL
 
3.4 Entidad: wishlist_item
Productos en listas de deseos.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
wishlist_id	INT	Lista	FK customer_wishlist.id, NOT NULL
product_id	INT	Producto	FK product_retail.id, NOT NULL
variation_id	INT	Variación específica	FK, NULLABLE
quantity	INT	Cantidad deseada	DEFAULT 1
priority	TINYINT	Prioridad 1-5	DEFAULT 3
notes	TEXT	Notas personales	NULLABLE
price_at_add	DECIMAL(10,2)	Precio cuando se añadió	NOT NULL
notified_price_drop	BOOLEAN	Notificado bajada precio	DEFAULT FALSE
added_at	DATETIME	Fecha añadido	NOT NULL
UNIQUE: (wishlist_id, product_id, variation_id)
3.5 Entidad: loyalty_transaction
Historial de movimientos de puntos de fidelidad.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
customer_id	INT	Cliente	FK customer_profile.id, NOT NULL, INDEX
type	VARCHAR(32)	Tipo transacción	ENUM: earn|redeem|expire|adjust|bonus
points	INT	Puntos (+ o -)	NOT NULL
balance_after	INT	Saldo después	NOT NULL
source	VARCHAR(32)	Origen	ENUM: order|review|referral|birthday|manual|promo
reference_type	VARCHAR(32)	Tipo referencia	NULLABLE, ej: 'order', 'review'
reference_id	INT	ID referencia	NULLABLE
description	VARCHAR(255)	Descripción	NOT NULL
expires_at	DATETIME	Fecha expiración	NULLABLE
created	DATETIME	Fecha transacción	NOT NULL
 
3.6 Entidad: notification_preferences
Preferencias de comunicación del cliente.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
customer_id	INT	Cliente	FK customer_profile.id, UNIQUE, NOT NULL
email_orders	BOOLEAN	Email de pedidos	DEFAULT TRUE
email_shipping	BOOLEAN	Email de envío	DEFAULT TRUE
email_marketing	BOOLEAN	Email promocional	DEFAULT FALSE
email_reviews	BOOLEAN	Solicitar reseñas	DEFAULT TRUE
email_price_drops	BOOLEAN	Bajadas de precio	DEFAULT TRUE
email_back_in_stock	BOOLEAN	Disponible de nuevo	DEFAULT TRUE
push_orders	BOOLEAN	Push de pedidos	DEFAULT TRUE
push_shipping	BOOLEAN	Push de envío	DEFAULT TRUE
push_marketing	BOOLEAN	Push promocional	DEFAULT FALSE
push_price_drops	BOOLEAN	Push bajada precio	DEFAULT FALSE
sms_orders	BOOLEAN	SMS de pedidos	DEFAULT FALSE
sms_shipping	BOOLEAN	SMS de envío	DEFAULT FALSE
whatsapp_enabled	BOOLEAN	WhatsApp habilitado	DEFAULT FALSE
frequency	VARCHAR(16)	Frecuencia marketing	ENUM: instant|daily|weekly
updated	DATETIME	Última modificación	NOT NULL
 
4. Servicios Principales
4.1 CustomerProfileService
<?php namespace Drupal\jaraba_customer\Service;  class CustomerProfileService {    // Perfil   public function getProfile(int $userId): CustomerProfile;   public function updateProfile(int $userId, array $data): CustomerProfile;   public function uploadAvatar(int $userId, File $file): string;   public function deleteAvatar(int $userId): void;      // Cuenta   public function changePassword(int $userId, string $current, string $new): bool;   public function requestEmailChange(int $userId, string $newEmail): void;   public function confirmEmailChange(int $userId, string $token): bool;   public function verifyEmail(int $userId, string $token): bool;   public function resendVerification(int $userId): void;      // Estadísticas   public function getStats(int $userId): CustomerStats;   public function recalculateStats(int $userId): void;      // Referidos   public function generateReferralCode(int $userId): string;   public function applyReferral(int $userId, string $code): bool;   public function getReferrals(int $userId): array;      // GDPR   public function exportData(int $userId): string;   public function requestDeletion(int $userId): void;   public function anonymize(int $userId): void; }
4.2 AddressBookService
<?php namespace Drupal\jaraba_customer\Service;  class AddressBookService {    // CRUD   public function getAddresses(int $customerId): array;   public function getAddress(int $addressId): ?CustomerAddress;   public function createAddress(int $customerId, array $data): CustomerAddress;   public function updateAddress(int $addressId, array $data): CustomerAddress;   public function deleteAddress(int $addressId): bool;      // Predeterminadas   public function setDefaultShipping(int $customerId, int $addressId): void;   public function setDefaultBilling(int $customerId, int $addressId): void;   public function getDefaultShipping(int $customerId): ?CustomerAddress;   public function getDefaultBilling(int $customerId): ?CustomerAddress;      // Validación   public function validateAddress(array $address): ValidationResult;   public function autocomplete(string $input): array;   public function geocode(CustomerAddress $address): ?GeoCoordinates;      // Límites   public function getAddressCount(int $customerId): int;   public function canAddMore(int $customerId): bool;  // Max 10 direcciones }
 
4.3 WishlistService
<?php namespace Drupal\jaraba_customer\Service;  class WishlistService {    // Listas   public function getWishlists(int $customerId): array;   public function getWishlist(int $wishlistId): ?CustomerWishlist;   public function createWishlist(int $customerId, array $data): CustomerWishlist;   public function updateWishlist(int $wishlistId, array $data): CustomerWishlist;   public function deleteWishlist(int $wishlistId): bool;   public function getDefaultWishlist(int $customerId): CustomerWishlist;      // Items   public function addItem(int $wishlistId, int $productId, ?int $variationId): WishlistItem;   public function removeItem(int $itemId): bool;   public function updateItem(int $itemId, array $data): WishlistItem;   public function moveToCart(int $itemId): void;   public function moveAllToCart(int $wishlistId): int;   public function isInWishlist(int $customerId, int $productId): bool;      // Compartir   public function generateShareLink(int $wishlistId): string;   public function getByShareToken(string $token): ?CustomerWishlist;      // Notificaciones   public function checkPriceDrops(int $customerId): array;   public function notifyPriceDrop(WishlistItem $item, float $newPrice): void;   public function notifyBackInStock(WishlistItem $item): void; }
4.4 LoyaltyService
<?php namespace Drupal\jaraba_customer\Service;  class LoyaltyService {    // Puntos   public function getBalance(int $customerId): int;   public function getLevel(int $customerId): string;   public function getNextLevelProgress(int $customerId): LevelProgress;      // Transacciones   public function earnPoints(int $customerId, int $points, string $source, ?array $ref): void;   public function redeemPoints(int $customerId, int $points, string $reason): bool;   public function getTransactions(int $customerId, array $filters = []): array;      // Niveles   public function checkLevelUp(int $customerId): ?string;   public function applyLevelBenefits(int $customerId, Cart $cart): void;      // Cupones de canje   public function getRedemptionOptions(int $customerId): array;   public function redeemForCoupon(int $customerId, string $optionId): Coupon;      // Expiración   public function getExpiringPoints(int $customerId, int $days = 30): int;   public function expireOldPoints(): int;  // Cron job      // Bonificaciones   public function awardBirthdayBonus(int $customerId): void;   public function awardReferralBonus(int $customerId, int $referredId): void; }
 
5. Sistema de Fidelización
5.1 Niveles de Fidelidad
Nivel	Requisito	Beneficios	Color/Badge
Bronce	0€ (registro)	1 punto/€, newsletter	🥉 Naranja
Plata	200€ acumulados	1.5 puntos/€, acceso anticipado ofertas	🥈 Gris
Oro	500€ acumulados	2 puntos/€, envío gratis >30€, -5%	🥇 Dorado
Platino	1.000€ acumulados	3 puntos/€, envío gratis siempre, -10%, soporte VIP	💎 Azul
5.2 Formas de Ganar Puntos
Acción	Puntos Base	Multiplicador por Nivel	Límite
Compra	1 punto por €	1x / 1.5x / 2x / 3x	Sin límite
Registro	50 puntos	N/A (una vez)	Una vez
Primera compra	100 puntos	N/A (una vez)	Una vez
Reseña con texto	20 puntos	1x	1 por producto
Reseña con foto	+10 puntos	1x	3 fotos máx
Reseña con video	+30 puntos	1x	1 video máx
Referir amigo	200 puntos	1x	10/mes
Cumpleaños	100 puntos	1x	Anual
Completar perfil	30 puntos	N/A (una vez)	Una vez
5.3 Opciones de Canje
// Opciones de canje de puntos const redemptionOptions = [   {     id: 'discount_5',     type: 'coupon',     points: 100,     value: 5.00,     description: 'Cupón de 5€',     minLevel: 'bronze',   },   {     id: 'discount_10',     type: 'coupon',     points: 180,     value: 10.00,     description: 'Cupón de 10€ (¡10% bonus!)',     minLevel: 'bronze',   },   {     id: 'discount_25',     type: 'coupon',     points: 400,     value: 25.00,     description: 'Cupón de 25€ (¡25% bonus!)',     minLevel: 'silver',   },   {     id: 'free_shipping',     type: 'benefit',     points: 50,     description: 'Envío gratis en tu próximo pedido',     minLevel: 'bronze',   },   {     id: 'express_upgrade',     type: 'benefit',     points: 75,     description: 'Upgrade a envío express gratis',     minLevel: 'silver',   },   {     id: 'early_access',     type: 'benefit',     points: 150,     description: 'Acceso anticipado 24h a próximas rebajas',     minLevel: 'gold',   }, ];  // Expiración de puntos const POINTS_EXPIRY_MONTHS = 24;  // Puntos expiran a los 24 meses
 
6. Módulo de Mis Pedidos
6.1 CustomerOrderService
<?php namespace Drupal\jaraba_customer\Service;  class CustomerOrderService {    // Consultas   public function getOrders(int $customerId, array $filters = []): PaginatedResult;   public function getOrder(int $orderId, int $customerId): ?OrderDetail;   public function getRecentOrders(int $customerId, int $limit = 5): array;   public function searchOrders(int $customerId, string $query): array;      // Tracking   public function getTrackingInfo(int $orderId): TrackingInfo;   public function subscribeToUpdates(int $orderId, string $channel): void;      // Devoluciones   public function canReturn(int $orderId): bool;   public function getReturnableItems(int $orderId): array;   public function initiateReturn(int $orderId, array $items, string $reason): ReturnRequest;   public function getReturnStatus(int $returnId): ReturnStatus;   public function uploadReturnProof(int $returnId, File $file): void;      // Acciones   public function cancelOrder(int $orderId, string $reason): bool;   public function reorder(int $orderId): Cart;   public function downloadInvoice(int $orderId): string;      // Click & Collect   public function getPickupCode(int $orderId): ?string;   public function getPickupQR(int $orderId): string; }
6.2 Vista de Lista de Pedidos
Columna	Descripción	Acciones
Número	ORD-2026-XXXXXX	Link a detalle
Fecha	dd/mm/yyyy	Ordenar
Productos	Thumbnails + count	Expandir
Total	XX,XX €	—
Estado	Badge de estado	—
Tracking	Link si enviado	Abrir tracking
Acciones	Menú contextual	Ver, Devolver, Reordenar
6.3 Detalle de Pedido (Vista Cliente)
// Secciones del detalle de pedido para cliente  1. HEADER    - Número de pedido    - Estado actual con badge    - Fecha del pedido    - Botones: Descargar factura, Ayuda  2. TIMELINE DE ESTADO    - Estados completados (✓)    - Estado actual (destacado)    - Estados pendientes (gris)    - Fechas de cada estado  3. PRODUCTOS    - Lista de items con imagen, nombre, variante, cantidad, precio    - Link a producto    - Botón "Reseñar" si no tiene reseña    - Indicador "Devuelto" si aplica  4. RESUMEN    - Subtotal    - Descuentos aplicados    - Envío    - Puntos ganados / usados    - TOTAL  5. INFORMACIÓN DE ENTREGA    - Tipo: Envío / Click & Collect    - Dirección o tienda de recogida    - Fecha estimada    - Tracking number + link externo    - Código de recogida (C&C)    - QR de recogida (C&C)  6. INFORMACIÓN DE PAGO    - Método (últimos 4 dígitos si tarjeta)    - Estado del pago    - Dirección de facturación  7. ACCIONES    - Solicitar devolución (si elegible)    - Cancelar (si pendiente)    - Volver a pedir    - Contactar soporte
 
6.4 Flujo de Devolución Self-Service
// Flujo de devolución desde el portal del cliente  Paso 1: SELECCIONAR ITEMS   - Mostrar items elegibles (dentro de 14 días)   - Checkbox por item   - Cantidad a devolver por item   - Indicar si producto defectuoso  Paso 2: MOTIVO   - Seleccionar motivo:     • Talla incorrecta     • Color diferente al esperado     • Producto defectuoso     • No coincide con la descripción     • Cambié de opinión     • Llegó tarde     • Otro (especificar)   - Comentarios adicionales (opcional)   - Subir fotos si defectuoso  Paso 3: MÉTODO DE DEVOLUCIÓN   - Envío con etiqueta prepagada (gratis si defecto)   - Devolución en tienda (si hay C&C)   - Recogida a domicilio (cargo adicional)  Paso 4: REEMBOLSO   - Mostrar resumen de reembolso   - Método: Al original / Saldo en tienda (+10% bonus)   - Tiempo estimado: 5-7 días hábiles  Paso 5: CONFIRMACIÓN   - Resumen de la solicitud   - Número de devolución: RET-2026-XXXXXX   - Etiqueta de envío (PDF)   - Email de confirmación  // Estados de devolución enum ReturnStatus {   REQUESTED = 'Solicitada';   APPROVED = 'Aprobada';   IN_TRANSIT = 'En tránsito';   RECEIVED = 'Recibida';   INSPECTING = 'En inspección';   REFUNDED = 'Reembolsada';   REJECTED = 'Rechazada'; }
 
7. Módulo de Favoritos
7.1 Funcionalidades de Wishlist
• Lista por defecto 'Mis Favoritos' creada automáticamente
• Crear múltiples listas (ej: 'Cumpleaños', 'Navidad')
• Añadir desde PDP con un clic (corazón)
• Mover items entre listas
• Compartir lista pública con link
• Notificar bajada de precio
• Notificar cuando vuelve a stock
• Añadir al carrito desde wishlist
7.2 Componente WishlistPage
// WishlistPage.jsx export function WishlistPage() {   const { data: wishlists } = useWishlists();   const [activeList, setActiveList] = useState(null);   const addToCart = useAddToCart();   const removeFromWishlist = useRemoveFromWishlist();      return (     <div className="wishlist-page">       <h1>Mis Favoritos</h1>              {/* Selector de listas */}       <div className="wishlist-tabs">         {wishlists?.map(list => (           <button             key={list.id}             className={activeList === list.id ? 'active' : ''}             onClick={() => setActiveList(list.id)}>             {list.name} ({list.items_count})           </button>         ))}         <button onClick={() => openCreateListModal()}>           <PlusIcon /> Nueva lista         </button>       </div>              {/* Grid de productos */}       <div className="wishlist-grid">         {activeList?.items.map(item => (           <WishlistItemCard             key={item.id}             item={item}             onAddToCart={() => addToCart(item)}             onRemove={() => removeFromWishlist(item.id)}             priceDropped={item.current_price < item.price_at_add}           />         ))}       </div>              {/* Acciones de lista */}       <div className="list-actions">         <Button onClick={() => addAllToCart()}>           Añadir todo al carrito         </Button>         <Button variant="outline" onClick={() => shareList()}>           <ShareIcon /> Compartir lista         </Button>       </div>     </div>   ); }
 
8. Configuración de Cuenta
8.1 Secciones de Configuración
Sección	Campos	Acciones
Datos personales	Nombre, email, teléfono, fecha nac.	Editar, verificar email
Contraseña	Contraseña actual, nueva	Cambiar contraseña
Direcciones	Libreta de direcciones	CRUD, predeterminadas
Métodos de pago	Tarjetas guardadas	Añadir, eliminar, predeterminada
Notificaciones	Email, push, SMS, WhatsApp	Toggles por tipo
Privacidad	Marketing, cookies, datos	Consentimientos
Idioma y región	Idioma, moneda, zona horaria	Selects
Seguridad	Sesiones activas, 2FA	Cerrar sesiones, activar 2FA
Eliminar cuenta	—	Solicitar eliminación
8.2 Preferencias de Notificación
// Matriz de preferencias de notificación const notificationMatrix = [   // Transaccionales (no desactivables completamente)   {     category: 'Pedidos',     types: [       { key: 'order_confirmation', label: 'Confirmación de pedido', email: true, push: true },       { key: 'order_shipped', label: 'Pedido enviado', email: true, push: true },       { key: 'order_delivered', label: 'Pedido entregado', email: true, push: true },       { key: 'order_pickup_ready', label: 'Listo para recoger', email: true, push: true, sms: true },     ]   },      // Promocionales (opt-in)   {     category: 'Promociones',     types: [       { key: 'offers', label: 'Ofertas y descuentos', email: false, push: false },       { key: 'new_arrivals', label: 'Novedades', email: false, push: false },       { key: 'flash_sales', label: 'Ventas flash', email: false, push: false },     ]   },      // Personalizadas   {     category: 'Mis productos',     types: [       { key: 'price_drop', label: 'Bajada de precio (favoritos)', email: true, push: false },       { key: 'back_in_stock', label: 'Disponible de nuevo', email: true, push: false },     ]   },      // Fidelización   {     category: 'Fidelización',     types: [       { key: 'points_earned', label: 'Puntos ganados', email: false, push: true },       { key: 'level_up', label: 'Subida de nivel', email: true, push: true },       { key: 'points_expiring', label: 'Puntos por expirar', email: true, push: true },     ]   },      // Reviews   {     category: 'Reseñas',     types: [       { key: 'review_request', label: 'Solicitud de reseña', email: true, push: false },       { key: 'review_response', label: 'Respuesta a mi reseña', email: true, push: true },     ]   }, ];
 
8.3 Cumplimiento GDPR
// Funcionalidades GDPR requeridas  1. DERECHO DE ACCESO    - Botón "Descargar mis datos"    - Genera JSON/CSV con todos los datos del usuario    - Incluye: perfil, pedidos, direcciones, reseñas, puntos    - Disponible en 24h (procesamiento async)  2. DERECHO DE RECTIFICACIÓN    - Todos los datos editables desde el portal    - Historial de cambios para auditoría  3. DERECHO AL OLVIDO    - Botón "Eliminar mi cuenta"    - Requiere confirmación por email    - Período de gracia de 30 días    - Anonimización de datos asociados a pedidos    - Eliminación completa de datos personales  4. DERECHO A LA PORTABILIDAD    - Exportación en formato estándar (JSON)    - Incluye todos los datos personales  5. CONSENTIMIENTOS    - Granular por tipo de comunicación    - Registro de fecha/hora de cada consentimiento    - Fácil de retirar (un clic)    - Doble opt-in para marketing  6. COOKIES    - Banner de consentimiento    - Gestión de preferencias de cookies    - Categorías: necesarias, analíticas, marketing  // Tiempo de retención const DATA_RETENTION = {   orders: '7 years',        // Requisito fiscal   profile: 'until_deletion',   analytics: '26 months',   marketing: 'until_withdrawal', };
 
9. APIs REST
9.1 Endpoints de Perfil
Método	Endpoint	Descripción
GET	/api/v1/customer/profile	Obtener mi perfil
PATCH	/api/v1/customer/profile	Actualizar perfil
POST	/api/v1/customer/profile/avatar	Subir avatar
DELETE	/api/v1/customer/profile/avatar	Eliminar avatar
POST	/api/v1/customer/profile/change-password	Cambiar contraseña
POST	/api/v1/customer/profile/verify-email	Verificar email
GET	/api/v1/customer/profile/stats	Estadísticas de cuenta
POST	/api/v1/customer/profile/export	Exportar datos (GDPR)
POST	/api/v1/customer/profile/delete-request	Solicitar eliminación
9.2 Endpoints de Direcciones
Método	Endpoint	Descripción
GET	/api/v1/customer/addresses	Listar direcciones
POST	/api/v1/customer/addresses	Crear dirección
GET	/api/v1/customer/addresses/{id}	Obtener dirección
PATCH	/api/v1/customer/addresses/{id}	Actualizar dirección
DELETE	/api/v1/customer/addresses/{id}	Eliminar dirección
POST	/api/v1/customer/addresses/{id}/default-shipping	Marcar envío default
POST	/api/v1/customer/addresses/{id}/default-billing	Marcar facturación default
9.3 Endpoints de Pedidos
Método	Endpoint	Descripción
GET	/api/v1/customer/orders	Listar mis pedidos
GET	/api/v1/customer/orders/{id}	Detalle de pedido
GET	/api/v1/customer/orders/{id}/tracking	Info de tracking
POST	/api/v1/customer/orders/{id}/cancel	Cancelar pedido
POST	/api/v1/customer/orders/{id}/return	Iniciar devolución
GET	/api/v1/customer/orders/{id}/invoice	Descargar factura
POST	/api/v1/customer/orders/{id}/reorder	Volver a pedir
 
9.4 Endpoints de Fidelización
Método	Endpoint	Descripción
GET	/api/v1/customer/loyalty	Estado de fidelización
GET	/api/v1/customer/loyalty/transactions	Historial de puntos
GET	/api/v1/customer/loyalty/redemption-options	Opciones de canje
POST	/api/v1/customer/loyalty/redeem	Canjear puntos
GET	/api/v1/customer/loyalty/referral-code	Mi código de referido
GET	/api/v1/customer/loyalty/referrals	Mis referidos
9.5 Endpoints de Wishlist
Método	Endpoint	Descripción
GET	/api/v1/customer/wishlists	Listar mis listas
POST	/api/v1/customer/wishlists	Crear lista
GET	/api/v1/customer/wishlists/{id}	Obtener lista
PATCH	/api/v1/customer/wishlists/{id}	Actualizar lista
DELETE	/api/v1/customer/wishlists/{id}	Eliminar lista
POST	/api/v1/customer/wishlists/{id}/items	Añadir item
DELETE	/api/v1/customer/wishlists/{id}/items/{itemId}	Eliminar item
POST	/api/v1/customer/wishlists/{id}/add-to-cart	Añadir todo al carrito
GET	/api/v1/wishlist/shared/{token}	Ver lista compartida (público)
 
10. Componentes Frontend
10.1 Arquitectura de Componentes
src/customer/ ├── layouts/ │   ├── AccountLayout.jsx        // Layout con sidebar │   └── AccountSidebar.jsx        // Navegación lateral │ ├── pages/ │   ├── Dashboard.jsx             // Mi cuenta (home) │   ├── orders/ │   │   ├── OrderList.jsx         // Mis pedidos │   │   ├── OrderDetail.jsx       // Detalle de pedido │   │   ├── OrderTracking.jsx     // Seguimiento │   │   └── ReturnWizard.jsx      // Flujo de devolución │   ├── addresses/ │   │   ├── AddressList.jsx       // Libreta direcciones │   │   └── AddressForm.jsx       // Añadir/editar │   ├── loyalty/ │   │   ├── LoyaltyDashboard.jsx  // Mi fidelización │   │   ├── PointsHistory.jsx     // Historial puntos │   │   └── RedeemPoints.jsx      // Canjear puntos │   ├── wishlist/ │   │   ├── WishlistPage.jsx      // Mis favoritos │   │   └── SharedWishlist.jsx    // Vista pública │   ├── reviews/ │   │   ├── MyReviews.jsx         // Mis reseñas │   │   └── PendingReviews.jsx    // Pendientes de reseñar │   └── settings/ │       ├── ProfileSettings.jsx   // Datos personales │       ├── NotificationSettings.jsx │       ├── PrivacySettings.jsx │       └── SecuritySettings.jsx │ └── components/     ├── OrderCard.jsx     ├── AddressCard.jsx     ├── PointsBalance.jsx     ├── LevelBadge.jsx     ├── WishlistItemCard.jsx     └── ReviewCard.jsx
10.2 Componente CustomerDashboard
// Dashboard.jsx - Página principal de Mi Cuenta export function CustomerDashboard() {   const { data: profile } = useProfile();   const { data: recentOrders } = useRecentOrders(3);   const { data: loyalty } = useLoyalty();      return (     <div className="customer-dashboard">       {/* Bienvenida */}       <WelcomeCard name={profile?.first_name} />              {/* Grid de stats */}       <div className="stats-grid">         <StatCard           icon={<ShoppingBagIcon />}           label="Pedidos"           value={profile?.order_count}           link="/account/orders"         />         <StatCard           icon={<StarIcon />}           label="Puntos"           value={loyalty?.balance}           sublabel={`Nivel ${loyalty?.level}`}           link="/account/loyalty"         />         <StatCard           icon={<HeartIcon />}           label="Favoritos"           value={profile?.wishlist_count}           link="/account/wishlist"         />         <StatCard           icon={<MessageIcon />}           label="Reseñas"           value={profile?.review_count}           link="/account/reviews"         />       </div>              {/* Pedidos recientes */}       <Card title="Pedidos recientes" action={{ label: 'Ver todos', href: '/account/orders' }}>         {recentOrders?.map(order => (           <OrderCard key={order.id} order={order} compact />         ))}       </Card>              {/* Barra de progreso de nivel */}       <LoyaltyProgressCard loyalty={loyalty} />              {/* Acciones rápidas */}       <QuickActionsCard />     </div>   ); }
 
11. Flujos de Automatización (ECA)
11.1 ECA-CUST-001: Nuevo Registro
Trigger: User created
1. Crear customer_profile
2. Crear wishlist por defecto
3. Crear notification_preferences con defaults
4. Generar referral_code único
5. Otorgar 50 puntos de bienvenida
6. Enviar email de bienvenida
7. Si tiene referral → aplicar y notificar referrer
11.2 ECA-CUST-002: Pedido Completado
Trigger: Order state = 'completed'
1. Calcular puntos: order_total × multiplicador_nivel
2. Añadir puntos con transacción
3. Actualizar lifetime_spent y order_count
4. Verificar subida de nivel
5. Si sube nivel → notificar y aplicar beneficios
11.3 ECA-CUST-003: Bajada de Precio en Wishlist
Trigger: Product price decreased
1. Buscar wishlist_items con ese producto
2. Filtrar donde price_at_add > new_price
3. Filtrar por notified_price_drop = false
4. Enviar notificación a cada cliente
5. Marcar notified_price_drop = true
11.4 ECA-CUST-004: Cumpleaños
Trigger: Cron diario compara birth_date
1. Buscar clientes con cumpleaños hoy
2. Otorgar 100 puntos de cumpleaños
3. Enviar email de felicitación con cupón especial
 
12. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades customer. Dashboard. Perfil básico. Autenticación.	Auth system
Sprint 2	Semana 3-4	Módulo Mis Pedidos. Lista, detalle, tracking.	67_Order_System
Sprint 3	Semana 5-6	Direcciones. Devoluciones self-service.	Sprint 2
Sprint 4	Semana 7-8	Sistema de Fidelización. Puntos, niveles, canje.	Sprint 3
Sprint 5	Semana 9-10	Wishlist completo. Notificaciones de precio.	Sprint 4
Sprint 6	Semana 11-12	Configuración. GDPR. Flujos ECA. QA y go-live.	Sprint 5
12.1 Criterios de Aceptación Sprint 2 (Pedidos)
✓ Listar pedidos con paginación y filtros
✓ Ver detalle completo de pedido
✓ Ver tracking en tiempo real
✓ Descargar factura PDF
✓ Función reorder funcional
12.2 Dependencias
• Sistema de autenticación Drupal
• 67_Order_System (pedidos)
• 72_Promotions_Coupons (canje de puntos)
• 73_Reviews_Ratings (mis reseñas)
• Google Maps API (validación direcciones)
--- Fin del Documento ---
75_ComercioConecta_Customer_Portal_v1.docx | Jaraba Impact Platform | Enero 2026
