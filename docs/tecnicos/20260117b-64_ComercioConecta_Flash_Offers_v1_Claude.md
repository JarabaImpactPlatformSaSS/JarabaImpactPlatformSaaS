SISTEMA FLASH OFFERS
Escaparate Inteligente - Ofertas Vinculadas a Horario
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	64_ComercioConecta_Flash_Offers
Dependencias:	62_Commerce_Core, 71_Merchant_Portal, 74_Notifications
Tipo:	Componente Exclusivo ComercioConecta
Base:	56_AgroConecta_Promotions_Coupons (~40% reutilizable)
 
1. Resumen Ejecutivo
Este documento especifica el Sistema Flash Offers (Escaparate Inteligente), un componente exclusivo de ComercioConecta que permite a los comercios crear ofertas temporales que solo están activas mientras la tienda está físicamente abierta. Este sistema crea urgencia real y conecta el mundo digital con la experiencia física del comercio de proximidad.
1.1 Propuesta de Valor
"La oferta desaparece cuando cierro la persiana"
• Urgencia real: El cliente sabe que la oferta termina cuando la tienda cierra
• Tráfico a tienda: Incentiva visitas físicas, no solo compras online
• Gestión de excedentes: Ideal para productos del día, últimas unidades, fin de temporada
• Diferenciación: Ningún marketplace grande ofrece esta funcionalidad
• Engagement local: Notificaciones geolocalizadas que impulsan la comunidad de barrio
1.2 Objetivos del Sistema
• Crear y gestionar ofertas flash vinculadas a horarios de apertura
• Sincronizar con Google Business Profile para horarios automáticos
• Mostrar cuenta regresiva en tiempo real hasta cierre de tienda
• Enviar notificaciones push geolocalizadas a usuarios cercanos
• Desactivar ofertas automáticamente al cerrar (o manualmente)
• Gestionar stock limitado y agotamiento de ofertas
1.3 Casos de Uso Principales
Caso de Uso	Descripción	Ejemplo
Oferta del día	Producto destacado con descuento especial solo hoy	"Hoy -30% en camisetas seleccionadas"
Última hora	Ofertas que se activan 2h antes del cierre	"¡Últimas 2 horas! Pan del día a mitad de precio"
Últimas unidades	Stock limitado, cuando se agota desaparece	"Solo quedan 3 unidades al 50%"
Happy Hour	Franja horaria específica, no todo el día	"De 17h a 19h: 2x1 en cafés"
Liquidación flash	Fin de temporada, activación por lote	"Liquidamos verano: todo al 60%"
Evento especial	Vinculado a evento local (feria, fiesta)	"Solo durante la Feria: regalo con compra"
 
2. Arquitectura del Sistema
2.1 Componentes Principales
┌─────────────────────────────────────────────────────────────────────┐ │                         FLASH OFFERS SYSTEM                         │ ├─────────────────────────────────────────────────────────────────────┤ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Merchant   │  │   Opening    │  │    Notification          │  │ │  │   Portal     │──│   Hours      │──│    Engine                │  │ │  │  (Crear)     │  │   Service    │  │  (Push geolocalizado)    │  │ │  └──────────────┘  └──────┬───────┘  └──────────────────────────┘  │ │                           │                                         │ │  ┌──────────────┐  ┌──────┴───────┐  ┌──────────────────────────┐  │ │  │ flash_offer  │  │   Schedule   │  │    Countdown             │  │ │  │   Entity     │──│   Manager    │──│    Widget                │  │ │  │              │  │  (Activar/   │  │  (Tiempo real)           │  │ │  └──────────────┘  │  Desactivar) │  └──────────────────────────┘  │ │                    └──────────────┘                                 │ │                           │                                         │ │  ┌──────────────┐  ┌──────┴───────┐  ┌──────────────────────────┐  │ │  │   Stock      │  │   Price      │  │    Analytics             │  │ │  │   Tracker    │──│   Calculator │──│    Dashboard             │  │ │  │ (Agotamiento)│  │ (Descuentos) │  │  (Conversión)            │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ └─────────────────────────────────────────────────────────────────────┘                               │               ┌───────────────┼───────────────┐               ▼               ▼               ▼        ┌────────────┐ ┌────────────┐ ┌────────────────┐        │  Google    │ │  Frontend  │ │  Mobile App    │        │  Business  │ │  Widget    │ │  Push Notif    │        │  Profile   │ │  (React)   │ │  (FCM/APNs)    │        └────────────┘ └────────────┘ └────────────────┘
2.2 Flujo de Vida de una Oferta Flash
1. Comerciante crea oferta en Merchant Portal (producto, descuento, horario)
2. Sistema valida horario contra opening_hours del comercio
3. Oferta queda en estado 'scheduled' hasta hora de inicio
4. Cron activa oferta → estado 'active' → visible en frontend
5. Se disparan notificaciones push a usuarios en radio configurado
6. Widget muestra cuenta regresiva hasta cierre de tienda
7. Si stock_limit > 0 y se agota → estado 'sold_out'
8. Al llegar hora de cierre → estado 'expired' → desaparece
9. Se registran métricas para analytics
 
3. Entidades del Sistema
3.1 Entidad: flash_offer
Entidad principal que representa una oferta flash. Puede aplicarse a un producto específico, una categoría, o todo el catálogo del comercio.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
merchant_id	INT	Comercio propietario	FK merchant_profile.id, NOT NULL, INDEX
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
title	VARCHAR(128)	Título de la oferta	NOT NULL, ej: '¡Hoy -30%!'
description	TEXT	Descripción promocional	NULLABLE
offer_type	VARCHAR(32)	Tipo de oferta	ENUM: product|category|store_wide|bundle
discount_type	VARCHAR(16)	Tipo de descuento	ENUM: percentage|fixed|bogo|free_shipping
discount_value	DECIMAL(10,2)	Valor del descuento	NOT NULL, ej: 30.00 para 30%
min_purchase	DECIMAL(10,2)	Compra mínima requerida	NULLABLE, DEFAULT 0
max_discount	DECIMAL(10,2)	Descuento máximo (cap)	NULLABLE
product_ids	JSON	IDs de productos aplicables	Array de product_retail.id, NULLABLE
category_tids	JSON	IDs de categorías aplicables	Array de taxonomy_term.tid, NULLABLE
variation_ids	JSON	Variaciones específicas	Array de variation.id, NULLABLE
exclude_ids	JSON	Productos excluidos	Array de product_retail.id, NULLABLE
stock_limit	INT	Unidades disponibles	NULLABLE, 0 = ilimitado
stock_sold	INT	Unidades vendidas	DEFAULT 0
per_customer_limit	INT	Máximo por cliente	DEFAULT 0 (ilimitado)
schedule_type	VARCHAR(32)	Tipo de programación	ENUM: store_hours|custom|recurring
start_time	TIME	Hora de inicio	NULLABLE (si store_hours, usa apertura)
end_time	TIME	Hora de fin	NULLABLE (si store_hours, usa cierre)
valid_date	DATE	Fecha específica	NULLABLE (si es un solo día)
valid_from	DATE	Fecha inicio rango	NULLABLE
valid_until	DATE	Fecha fin rango	NULLABLE
days_of_week	JSON	Días activos	Array: [1,2,3,4,5] = L-V
hours_before_close	INT	Activar X horas antes de cierre	NULLABLE, para 'última hora'
status	VARCHAR(16)	Estado actual	ENUM: draft|scheduled|active|paused|sold_out|expired
activated_at	DATETIME	Última activación	NULLABLE
deactivated_at	DATETIME	Última desactivación	NULLABLE
image_fid	INT	Imagen promocional	FK file_managed.fid, NULLABLE
badge_text	VARCHAR(32)	Texto del badge	NULLABLE, ej: 'FLASH', 'HOY'
badge_color	VARCHAR(7)	Color del badge hex	DEFAULT '#FF5722'
priority	INT	Prioridad de display	DEFAULT 0, mayor = primero
is_stackable	BOOLEAN	Combinable con otras ofertas	DEFAULT FALSE
notify_radius_km	DECIMAL(5,2)	Radio de notificación	DEFAULT 2.00 km
notify_on_activate	BOOLEAN	Enviar push al activar	DEFAULT TRUE
views_count	INT	Visualizaciones	DEFAULT 0
clicks_count	INT	Clicks en CTA	DEFAULT 0
conversions_count	INT	Compras con esta oferta	DEFAULT 0
revenue_generated	DECIMAL(12,2)	Ingresos generados	DEFAULT 0.00
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
3.2 Entidad: flash_offer_redemption
Registro de cada uso de una oferta flash. Se usa para tracking de límites por cliente, analytics, y prevención de abuso.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
offer_id	INT	Oferta utilizada	FK flash_offer.id, NOT NULL, INDEX
order_id	INT	Pedido asociado	FK commerce_order.id, NOT NULL
customer_uid	INT	Usuario cliente	FK users.uid, NOT NULL, INDEX
customer_email	VARCHAR(255)	Email (si guest)	NULLABLE
variation_id	INT	Variación comprada	FK product_variation_retail.id, NULLABLE
quantity	INT	Cantidad comprada	NOT NULL, >= 1
original_price	DECIMAL(10,2)	Precio original	NOT NULL
discount_applied	DECIMAL(10,2)	Descuento aplicado	NOT NULL
final_price	DECIMAL(10,2)	Precio final pagado	NOT NULL
redemption_channel	VARCHAR(16)	Canal de compra	ENUM: web|app|pos|click_collect
redeemed_at	DATETIME	Momento del uso	NOT NULL, UTC
ip_address	VARCHAR(45)	IP del cliente	NULLABLE, para anti-fraude
user_agent	VARCHAR(255)	User agent	NULLABLE
INDEX: (offer_id, customer_uid) - Para verificar límite por cliente rápidamente.
3.3 Entidad: opening_hours_override
Sobreescritura de horarios para días especiales (festivos, eventos, vacaciones). Se usa para ajustar las ofertas flash automáticamente.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
merchant_id	INT	Comercio	FK merchant_profile.id, NOT NULL, INDEX
date	DATE	Fecha específica	NOT NULL, INDEX
is_closed	BOOLEAN	Cerrado todo el día	DEFAULT FALSE
open_time	TIME	Hora apertura especial	NULLABLE
close_time	TIME	Hora cierre especial	NULLABLE
reason	VARCHAR(128)	Motivo	NULLABLE, ej: 'Festivo local'
created	DATETIME	Fecha creación	NOT NULL
UNIQUE: (merchant_id, date) - Solo un override por día por comercio.
 
4. Servicio de Horarios de Apertura
El servicio OpeningHoursService gestiona los horarios de apertura de los comercios, integrando con Google Business Profile y manejando excepciones.
4.1 OpeningHoursService
<?php namespace Drupal\jaraba_flash\Service;  class OpeningHoursService {    // Consulta de horarios   public function isOpen(MerchantProfile $merchant, ?\DateTime $at = null): bool;   public function getOpeningTime(MerchantProfile $merchant, ?\DateTime $date = null): ?\DateTime;   public function getClosingTime(MerchantProfile $merchant, ?\DateTime $date = null): ?\DateTime;   public function getTimeUntilClose(MerchantProfile $merchant): ?\DateInterval;   public function getNextOpeningTime(MerchantProfile $merchant): ?\DateTime;      // Horarios semanales   public function getWeeklySchedule(MerchantProfile $merchant): array;   public function setWeeklySchedule(MerchantProfile $merchant, array $schedule): void;      // Excepciones   public function getOverrides(MerchantProfile $merchant, \DateTime $from, \DateTime $to): array;   public function setOverride(MerchantProfile $merchant, \DateTime $date, ?array $hours, bool $closed): void;   public function removeOverride(MerchantProfile $merchant, \DateTime $date): void;      // Sincronización Google   public function syncFromGoogleBusiness(MerchantProfile $merchant): SyncResult;   public function syncToGoogleBusiness(MerchantProfile $merchant): SyncResult; }
4.2 Formato de Horarios (OpeningHoursSpecification)
Los horarios se almacenan en formato compatible con Schema.org para reutilización en JSON-LD:
// merchant_profile.opening_hours (JSON) [   {     "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],     "opens": "10:00",     "closes": "14:00"   },   {     "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],     "opens": "17:00",     "closes": "20:30"   },   {     "dayOfWeek": ["Saturday"],     "opens": "10:00",     "closes": "14:00"   }   // Sunday: cerrado (no entry) ]
4.3 Integración con Google Business Profile
• Se sincroniza automáticamente si merchant_profile.google_place_id está configurado
• Usa Google My Business API v4 (ahora Business Profile API)
• Sincronización bidireccional: cambios en Drupal → Google y viceversa
• Detecta automáticamente horarios especiales de Google (festivos)
 
5. Servicio de Ofertas Flash
5.1 FlashOfferService
<?php namespace Drupal\jaraba_flash\Service;  class FlashOfferService {    // CRUD   public function create(MerchantProfile $merchant, array $data): FlashOffer;   public function update(FlashOffer $offer, array $data): FlashOffer;   public function delete(FlashOffer $offer): void;   public function duplicate(FlashOffer $offer, ?\DateTime $newDate = null): FlashOffer;      // Activación/Desactivación   public function activate(FlashOffer $offer): void;   public function deactivate(FlashOffer $offer, string $reason = 'manual'): void;   public function pause(FlashOffer $offer): void;   public function resume(FlashOffer $offer): void;      // Consultas   public function getActiveOffers(MerchantProfile $merchant): array;   public function getActiveOffersForProduct(ProductRetail $product): array;   public function getActiveOffersNearby(float $lat, float $lng, float $radiusKm): array;   public function getScheduledOffers(MerchantProfile $merchant): array;      // Cálculo de precios   public function calculateDiscount(FlashOffer $offer, ProductVariationRetail $variation): DiscountResult;   public function applyToCart(FlashOffer $offer, OrderInterface $cart): void;   public function validateRedemption(FlashOffer $offer, int $customerUid, int $quantity): ValidationResult;      // Stock   public function decrementStock(FlashOffer $offer, int $quantity): bool;   public function checkStockAvailability(FlashOffer $offer): int; // remaining      // Analytics   public function recordView(FlashOffer $offer): void;   public function recordClick(FlashOffer $offer): void;   public function recordConversion(FlashOffer $offer, OrderInterface $order): void; }
5.2 DiscountResult
class DiscountResult {   public bool $applicable;           // ¿Se puede aplicar?   public float $originalPrice;       // Precio original   public float $discountAmount;      // Cantidad descontada   public float $finalPrice;          // Precio final   public float $discountPercentage;  // % de descuento real   public ?string $rejectionReason;   // Razón si no aplicable   public array $warnings;            // Avisos (stock bajo, etc.) }
5.3 Scheduler: Activación y Desactivación Automática
Un cron job ejecuta cada minuto para gestionar el ciclo de vida de las ofertas:
// Cron job: flash_offers_scheduler // Frecuencia: cada minuto  public function processScheduledOffers(): void {   $now = new \DateTime();      // 1. Activar ofertas programadas cuya hora de inicio ha llegado   $toActivate = $this->getOffersToActivate($now);   foreach ($toActivate as $offer) {     if ($this->openingHours->isOpen($offer->getMerchant())) {       $this->activate($offer);       if ($offer->notify_on_activate) {         $this->notificationService->sendFlashOfferNotification($offer);       }     }   }      // 2. Desactivar ofertas cuya hora de fin ha llegado o tienda ha cerrado   $toDeactivate = $this->getOffersToDeactivate($now);   foreach ($toDeactivate as $offer) {     $merchant = $offer->getMerchant();     $closingTime = $this->openingHours->getClosingTime($merchant);          if ($now >= $closingTime || $offer->end_time <= $now->format('H:i:s')) {       $this->deactivate($offer, 'store_closed');     }   }      // 3. Marcar como sold_out las que agotaron stock   $this->checkAndMarkSoldOut(); }
 
6. Tipos de Ofertas Flash
6.1 Por Alcance (offer_type)
Tipo	Descripción	Campos Requeridos	Ejemplo
product	Producto específico	product_ids (1+)	30% en Camiseta X
category	Categoría completa	category_tids (1+)	20% en toda la Moda
store_wide	Todo el catálogo	Ninguno adicional	15% en toda la tienda
bundle	Combo de productos	product_ids (2+), bundle_price	Pack verano: 3 por 50€
6.2 Por Descuento (discount_type)
Tipo	Descripción	discount_value	Ejemplo
percentage	Porcentaje de descuento	30.00 = 30%	30% de descuento
fixed	Cantidad fija	10.00 = 10€	10€ de descuento
bogo	Buy One Get One	50.00 = 50% en 2º	2ª unidad al 50%
free_shipping	Envío gratis	0 (ignorado)	Envío gratis hoy
6.3 Por Programación (schedule_type)
Tipo	Descripción	Comportamiento
store_hours	Vinculado a horario de tienda	Activa al abrir, desactiva al cerrar automáticamente
custom	Horario personalizado	start_time y end_time definen ventana dentro del día
recurring	Recurrente semanal	days_of_week define qué días, se repite cada semana
6.4 Ofertas Especiales: 'Última Hora'
El campo hours_before_close permite crear ofertas que se activan automáticamente X horas antes del cierre de la tienda:
// Oferta 'Última Hora' para panadería {   "title": "¡Últimas 2 horas! Pan del día -50%",   "schedule_type": "store_hours",   "hours_before_close": 2,   "discount_type": "percentage",   "discount_value": 50,   "category_tids": [123],  // Categoría 'Pan del día'   "days_of_week": [1, 2, 3, 4, 5, 6]  // Lunes a Sábado }  // Resultado: Si tienda cierra a 20:30, oferta se activa a 18:30
 
7. Widget de Cuenta Regresiva
El widget de cuenta regresiva es el elemento visual clave que crea urgencia mostrando el tiempo restante hasta que la oferta expire (cierre de tienda).
7.1 Componente React: FlashCountdown
// FlashCountdown.jsx import { useState, useEffect } from 'react';  export function FlashCountdown({ endTime, onExpire }) {   const [timeLeft, setTimeLeft] = useState(calculateTimeLeft(endTime));      useEffect(() => {     const timer = setInterval(() => {       const left = calculateTimeLeft(endTime);       setTimeLeft(left);       if (left.total <= 0) {         clearInterval(timer);         onExpire?.();       }     }, 1000);     return () => clearInterval(timer);   }, [endTime]);      if (timeLeft.total <= 0) return null;      return (     <div className="flash-countdown bg-red-600 text-white px-4 py-2 rounded-lg">       <span className="font-bold">⚡ TERMINA EN: </span>       <span className="font-mono text-xl">         {timeLeft.hours}h {timeLeft.minutes}m {timeLeft.seconds}s       </span>     </div>   ); }
7.2 Variantes de Visualización
Variante	Uso	Diseño
compact	Badge en tarjeta de producto	Solo HH:MM:SS, fondo rojo
standard	Detalle de producto	Texto + tiempo + barra de progreso
banner	Cabecera de categoría/tienda	Full width, animación de pulso
floating	Sticky en mobile	Flotante en parte inferior de pantalla
minimal	Lista de productos	Solo icono + minutos restantes
7.3 API de Tiempo Real
El frontend obtiene el tiempo de fin desde un endpoint ligero que calcula dinámicamente basándose en el horario de cierre del comercio:
// GET /api/v1/flash-offers/{id}/countdown // Response: {   "offer_id": 456,   "status": "active",   "ends_at": "2026-01-17T20:30:00+01:00",  // ISO 8601   "seconds_remaining": 7200,   "stock_remaining": 5,  // null si ilimitado   "reason_end": "store_closing"  // store_closing|time_limit|manual }
 
8. Notificaciones Push Geolocalizadas
Cuando una oferta flash se activa, el sistema puede enviar notificaciones push a usuarios que estén dentro del radio configurado (notify_radius_km) y hayan dado permiso.
8.1 Flujo de Notificación
1. Oferta se activa → FlashOfferService::activate()
2. Si notify_on_activate = TRUE → NotificationService::sendFlashOfferNotification()
3. Obtener ubicación del comercio (lat, lng)
4. Query usuarios con última ubicación conocida dentro de notify_radius_km
5. Filtrar por: opt-in de notificaciones, favoritos del comercio, intereses de categoría
6. Enviar push via Firebase Cloud Messaging (FCM) / Apple Push Notification (APNs)
8.2 Segmentación de Audiencia
Segmento	Descripción	Prioridad
nearby_now	Usuarios actualmente en radio (ubicación < 1h)	Alta
favorites	Usuarios que marcaron la tienda como favorita	Alta
recent_buyers	Compraron en esta tienda últimos 30 días	Media
category_interest	Interactuaron con categoría de la oferta	Media
all_local	Todos en el radio, con opt-in general	Baja
8.3 Contenido de la Notificación
// Push notification payload {   "notification": {     "title": "⚡ ¡Oferta Flash en Moda Local!",     "body": "30% en camisetas - Solo hasta que cerremos (20:30)",     "image": "https://cdn.../offer-image.jpg"   },   "data": {     "type": "flash_offer",     "offer_id": "456",     "merchant_id": "123",     "deep_link": "comercioconecta://offer/456",     "expires_at": "2026-01-17T20:30:00+01:00"   },   "android": {     "priority": "high",     "ttl": "7200s"  // Expira con la oferta   },   "apns": {     "headers": { "apns-priority": "10" }   } }
8.4 Límites Anti-Spam
• Máximo 3 notificaciones flash por comercio por día por usuario
• Mínimo 2 horas entre notificaciones del mismo comercio
• Máximo 10 notificaciones flash totales por día por usuario
• Quiet hours: No enviar entre 22:00 y 09:00 (configurable)
 
9. Flujos de Automatización (ECA)
9.1 ECA-FLASH-001: Tienda Abre
Trigger: Cron detecta que hora actual = opening_time del comercio
1. Obtener ofertas con schedule_type = 'store_hours' y status = 'scheduled'
2. Verificar que la fecha actual está en valid_from/valid_until
3. Verificar que el día actual está en days_of_week
4. Activar cada oferta válida
5. Enviar notificaciones push si notify_on_activate = TRUE
9.2 ECA-FLASH-002: Tienda Cierra
Trigger: Cron detecta que hora actual = closing_time del comercio
1. Obtener ofertas activas del comercio con schedule_type = 'store_hours'
2. Desactivar cada oferta con reason = 'store_closed'
3. Calcular y guardar métricas del día
4. Si la oferta es recurring, programar para siguiente día válido
9.3 ECA-FLASH-003: Última Hora
Trigger: Cron detecta que hora actual = closing_time - hours_before_close
1. Obtener ofertas con hours_before_close > 0 y status = 'scheduled'
2. Activar cada oferta
3. Enviar notificación especial: '¡Últimas X horas! [Oferta]'
9.4 ECA-FLASH-004: Stock Agotado
Trigger: flash_offer.stock_sold >= flash_offer.stock_limit (cuando limit > 0)
1. Cambiar status a 'sold_out'
2. Ocultar oferta del frontend inmediatamente
3. Notificar al comerciante: 'Tu oferta flash se agotó'
4. Registrar tiempo hasta agotamiento para analytics
9.5 ECA-FLASH-005: Compra con Oferta Flash
Trigger: Order completado con líneas que tienen flash_offer aplicado
1. Crear flash_offer_redemption para cada uso
2. Incrementar flash_offer.stock_sold
3. Incrementar flash_offer.conversions_count
4. Actualizar flash_offer.revenue_generated
5. Verificar si debe marcar como sold_out (ECA-FLASH-004)
 
10. APIs REST
10.1 Endpoints para Comerciantes
Método	Endpoint	Descripción	Auth
GET	/api/v1/flash-offers	Listar ofertas del comercio	Merchant
POST	/api/v1/flash-offers	Crear oferta flash	Merchant
GET	/api/v1/flash-offers/{id}	Detalle de oferta	Merchant
PATCH	/api/v1/flash-offers/{id}	Actualizar oferta	Merchant
DELETE	/api/v1/flash-offers/{id}	Eliminar oferta	Merchant
POST	/api/v1/flash-offers/{id}/activate	Activar manualmente	Merchant
POST	/api/v1/flash-offers/{id}/deactivate	Desactivar manualmente	Merchant
POST	/api/v1/flash-offers/{id}/duplicate	Duplicar oferta	Merchant
GET	/api/v1/flash-offers/{id}/stats	Estadísticas de la oferta	Merchant
10.2 Endpoints Públicos (Frontend)
Método	Endpoint	Descripción	Auth
GET	/api/v1/flash-offers/active	Ofertas activas (filtros: merchant, category, nearby)	Público
GET	/api/v1/flash-offers/{id}/countdown	Tiempo restante para widget	Público
GET	/api/v1/flash-offers/nearby	Ofertas cercanas a coordenadas	Público
GET	/api/v1/merchants/{id}/flash-offers	Ofertas activas de un comercio	Público
POST	/api/v1/flash-offers/{id}/view	Registrar visualización	Público
POST	/api/v1/flash-offers/{id}/click	Registrar click	Público
10.3 Endpoints de Horarios
Método	Endpoint	Descripción	Auth
GET	/api/v1/merchants/{id}/hours	Horarios de la semana	Público
PATCH	/api/v1/merchants/{id}/hours	Actualizar horarios	Merchant
GET	/api/v1/merchants/{id}/hours/overrides	Excepciones (festivos)	Merchant
POST	/api/v1/merchants/{id}/hours/overrides	Crear excepción	Merchant
DELETE	/api/v1/merchants/{id}/hours/overrides/{date}	Eliminar excepción	Merchant
POST	/api/v1/merchants/{id}/hours/sync-google	Sincronizar con Google	Merchant
 
11. Analytics de Ofertas Flash
11.1 Métricas por Oferta
Métrica	Descripción	Cálculo
Impresiones	Veces que se mostró la oferta	views_count
Clicks	Clicks en CTA 'Ver oferta'	clicks_count
CTR	Click-through rate	clicks_count / views_count × 100
Conversiones	Compras con la oferta aplicada	conversions_count
Conversion Rate	% de clicks que compran	conversions_count / clicks_count × 100
Revenue	Ingresos generados	revenue_generated
AOV	Average Order Value	revenue_generated / conversions_count
Stock Velocity	Tiempo hasta agotamiento	sold_out_at - activated_at
Discount Given	Total descuento otorgado	SUM(redemption.discount_applied)
11.2 Métricas Agregadas por Comercio
• Total ofertas flash creadas (mes/año)
• Promedio de conversiones por oferta
• Mejor día de la semana para ofertas
• Mejor hora para activar ofertas
• Categorías con mejor performance
• ROI de ofertas (revenue vs descuento dado)
11.3 Dashboard del Comerciante
El Merchant Portal incluye una sección dedicada a Flash Offers con gráficos de performance, comparativa de ofertas, y recomendaciones IA para optimizar descuentos y horarios.
 
12. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidad flash_offer y flash_offer_redemption. FlashOfferService CRUD básico. Admin UI para crear ofertas.	62_Commerce_Core
Sprint 2	Semana 3-4	OpeningHoursService completo. Entidad opening_hours_override. Integración Google Business Profile API.	Sprint 1
Sprint 3	Semana 5-6	Scheduler de activación/desactivación. Flujos ECA (001-005). Cálculo de descuentos en checkout.	Sprint 2 + 06_Core_Flujos
Sprint 4	Semana 7-8	Widget React FlashCountdown (todas las variantes). API de tiempo real. Frontend de ofertas en catálogo.	Sprint 3
Sprint 5	Semana 9-10	Sistema de notificaciones push geolocalizadas. Integración FCM/APNs. Límites anti-spam.	Sprint 4 + 74_Notifications
Sprint 6	Semana 11-12	Analytics dashboard. Métricas por oferta y agregadas. UI en Merchant Portal. QA y go-live.	Sprint 5
12.1 Criterios de Aceptación Sprint 3 (Core)
✓ Oferta se activa automáticamente cuando tienda abre
✓ Oferta se desactiva automáticamente cuando tienda cierra
✓ Descuento se aplica correctamente en el carrito
✓ Stock limitado se decrementa y marca sold_out
✓ Límite por cliente funciona correctamente
12.2 Dependencias Externas
• Google Business Profile API (My Business API v4)
• Firebase Cloud Messaging (FCM) para Android
• Apple Push Notification service (APNs) para iOS
• React 18+ para componentes de countdown
--- Fin del Documento ---
64_ComercioConecta_Flash_Offers_v1.docx | Jaraba Impact Platform | Enero 2026
