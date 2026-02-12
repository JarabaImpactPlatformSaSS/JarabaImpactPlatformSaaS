FLUJO DE CHECKOUT MULTI-VENDOR
Carrito, Envío por Productor y Pago Unificado
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	50_AgroConecta_Checkout_Flow
Dependencias:	47_Commerce_Core, 49_Order_System, Stripe
 
1. Resumen Ejecutivo
Este documento especifica el flujo completo de checkout para AgroConecta, desde la gestión del carrito multi-vendor hasta la confirmación del pedido. El sistema permite comprar productos de múltiples productores en una sola transacción, con cálculo de envío independiente por origen y un único pago que se distribuye automáticamente.
1.1 Objetivos del Sistema
•	Carrito multi-vendor: Productos de N productores en un solo carrito
•	Transparencia de costes: Desglose claro de envío por cada productor
•	Checkout optimizado: Single-page checkout con progreso visual
•	Métodos de entrega flexibles: Envío, recogida en origen, puntos de recogida
•	Guest checkout: Compra sin registro obligatorio
•	Conversión máxima: Mínima fricción, guardado automático de progreso
1.2 Stack Tecnológico
Componente	Tecnología
Carrito	Commerce Cart API + Alpine.js para interactividad
Checkout	Commerce Checkout con panes personalizados
Direcciones	Address Field + Google Places Autocomplete
Envíos	Commerce Shipping con rates por productor
Pagos	Stripe Payment Element (Cards, Bizum, Google/Apple Pay)
Cupones	Commerce Promotion con validación AJAX
Persistencia	Session storage + DB para carritos de usuarios registrados
Analytics	Enhanced E-commerce (GA4) + Meta Pixel
1.3 Métricas de Éxito
Métrica	Objetivo	Baseline Industria
Tasa de abandono de carrito	< 65%	70-80%
Tiempo medio de checkout	< 3 minutos	4-5 minutos
Tasa de conversión checkout iniciado → completado	> 50%	40-45%
Errores de pago por problemas UX	< 2%	5%
 
2. Gestión del Carrito
El carrito de AgroConecta agrupa productos de múltiples productores, mostrando un desglose visual por origen que prepara al cliente para entender el modelo multi-vendor.
2.1 Estructura del Carrito
El carrito se presenta agrupado por productor para transparencia:
┌─────────────────────────────────────────────────────────────┐
│  🛒 Tu Carrito (5 productos de 2 productores)               │
├─────────────────────────────────────────────────────────────┤
│  📦 Finca Los Olivos (Priego de Córdoba)                    │
│  ├── AOVE Picual 500ml          x2      €12.00    €24.00   │
│  └── AOVE Hojiblanca 1L         x1      €18.00    €18.00   │
│                                   Subtotal:        €42.00   │
│                                   Envío (estimado): €5.95   │
├─────────────────────────────────────────────────────────────┤
│  📦 Bodegas Robles (Montilla)                               │
│  ├── Fino en Rama 750ml         x3      €8.50     €25.50   │
│  └── Pedro Ximénez 500ml        x1      €12.00    €12.00   │
│                                   Subtotal:        €37.50   │
│                                   Envío (estimado): €4.50   │
├─────────────────────────────────────────────────────────────┤
│  Subtotal productos:                              €79.50   │
│  Envío total (2 orígenes):                        €10.45   │
│  ─────────────────────────────────────────────────────────  │
│  TOTAL:                                           €89.95   │
│                                                             │
│  [Tengo un cupón]              [ Tramitar Pedido →]         │
└─────────────────────────────────────────────────────────────┘
2.2 Funcionalidades del Carrito
Funcionalidad	Comportamiento
Añadir producto	AJAX sin recarga. Animación de feedback. Mini-cart se actualiza. GA4 event: add_to_cart
Modificar cantidad	Stepper +/- con debounce 300ms. Validación de stock en tiempo real. Recálculo de totales
Eliminar item	Confirmación inline. Opción 'Deshacer' por 5 segundos. GA4 event: remove_from_cart
Guardar para después	Mueve item a wishlist. Disponible solo para usuarios registrados
Aplicar cupón	Validación AJAX. Feedback inmediato. Descuento aplicado visualmente
Calcular envío	Estimación por código postal. Actualiza al introducir dirección en checkout
Persistencia	Session para anónimos (30 días). DB para registrados (merge al login)
Cross-device	Sincronización automática al iniciar sesión en otro dispositivo
2.3 Validaciones en Tiempo Real
•	Stock disponible: Verificación al añadir y antes de checkout. Mensaje si stock insuficiente
•	Producto activo: Verificación de que el producto sigue publicado. Alerta si fue despublicado
•	Precio actualizado: Detección de cambios de precio. Notificación al cliente con nuevo total
•	Cantidad máxima: Respeto de max_quantity por variación. Mensaje explicativo
•	Productor activo: Verificación de que el productor puede vender. Alerta si fue desactivado
 
3. Flujo de Checkout
El checkout de AgroConecta es un proceso single-page con accordion/steps que minimiza la fricción y maximiza la conversión. Cada paso se valida antes de permitir avanzar.
3.1 Pasos del Checkout
#	Paso	Contenido	Obligatorio
1	Identificación	Login, Registro rápido, o Continuar como invitado (solo email)	Sí
2	Dirección de Envío	Formulario de dirección con autocompletado Google Places	Sí (si envío)
3	Método de Entrega	Selección por cada productor: envío estándar, express, recogida	Sí
4	Fecha de Entrega	Selector de fecha preferida (opcional, +2 días mínimo)	No
5	Facturación	Checkbox 'Igual que envío' o formulario independiente + NIF	Sí
6	Cupón	Campo para código de descuento con validación en tiempo real	No
7	Pago	Stripe Payment Element: tarjeta, Bizum, Google Pay, Apple Pay	Sí
8	Revisión	Resumen completo, T&C checkbox, botón 'Confirmar Pedido'	Sí
3.2 Diagrama de Flujo
[Carrito]
    │
    ▼
┌─────────────────┐
│ ¿Usuario logado?│
└────────┬────────┘
    Sí   │   No
    │    └──────────────────┐
    │                       ▼
    │              [Login/Registro/Guest]
    │                       │
    └───────────┬───────────┘
                ▼
       [Dirección de Envío]
                │
                ▼
       [Método de Entrega × N productores]
                │
                ▼
       [Datos de Facturación]
                │
                ▼
       [Pago - Stripe Element]
                │
                ▼
       [Revisión + Confirmar]
                │
                ▼
       [Confirmación + Email]
 
4. Paso 1: Identificación
El primer paso permite al usuario identificarse o continuar como invitado, minimizando la fricción mientras se captura información esencial.
4.1 Opciones de Identificación
Opción	Descripción	Datos Requeridos
Login	Usuario existente inicia sesión. Se cargan direcciones guardadas.	Email + Password
Registro Rápido	Crear cuenta durante checkout. Se envía email de verificación post-compra.	Email + Password
Guest Checkout	Compra sin cuenta. Se ofrece crear cuenta en confirmación.	Solo Email
Social Login	Login con Google o Facebook. Pre-rellena email y nombre.	OAuth consent
4.2 Formulario de Guest Checkout
Campo	Tipo	Validación	Notas
email	Email	Formato válido, único si ya existe cuenta (ofrecer login)	Obligatorio
phone	Tel	Formato ES: +34 o 6/7/9XX XXX XXX	Opcional (útil para envío)
newsletter	Checkbox	N/A	Opt-in marketing, default OFF
4.3 Detección de Email Existente
Si el email introducido ya existe en el sistema:
1.	Mostrar mensaje: "Ya tienes una cuenta con este email"
2.	Ofrecer opciones: [Iniciar sesión] [Recuperar contraseña] [Usar otro email]
3.	Si elige login: mostrar campo de password inline
4.	Tras login exitoso: cargar direcciones guardadas
 
5. Paso 2: Dirección de Envío
Captura la dirección de entrega con autocompletado inteligente y validación de código postal para cálculo de envío preciso.
5.1 Campos del Formulario
Campo	Tipo	Validación	Notas
given_name	Text	2-50 caracteres, solo letras y espacios	Nombre
family_name	Text	2-50 caracteres	Apellidos
organization	Text	Opcional, max 100 chars	Empresa (si aplica)
address_line1	Text	5-100 caracteres	Calle y número
address_line2	Text	Opcional, max 100 chars	Piso, puerta, etc.
postal_code	Text	5 dígitos, validar que existe	Código postal
locality	Text	Auto-rellenado desde CP	Ciudad
administrative_area	Select	Auto-rellenado desde CP	Provincia
country_code	Hidden	Default: ES	País (España por defecto)
5.2 Autocompletado Google Places
•	Activación: Al escribir en address_line1, aparecen sugerencias
•	Selección: Al elegir sugerencia, se rellenan todos los campos automáticamente
•	Restricción: Limitado a España (componentRestrictions: {country: 'es'})
•	Fallback: Si falla API, formulario manual completamente funcional
5.3 Validación de Código Postal
Al introducir código postal válido:
5.	Verificar que existe en base de datos de CPs de España
6.	Auto-rellenar ciudad y provincia
7.	Verificar si los productores del carrito envían a esa zona
8.	Si algún productor no envía: mostrar alerta y opciones (recogida, quitar items)
9.	Recalcular costes de envío de todos los productores
5.4 Direcciones Guardadas
Para usuarios registrados:
•	Mostrar lista de direcciones guardadas con radio buttons
•	Dirección por defecto pre-seleccionada
•	Opción 'Usar otra dirección' expande formulario vacío
•	Checkbox 'Guardar esta dirección' para nuevas direcciones
 
6. Paso 3: Método de Entrega
El método de entrega se selecciona POR CADA PRODUCTOR del carrito, permitiendo combinaciones flexibles (envío de uno, recogida de otro).
6.1 Estructura de Selección
┌─────────────────────────────────────────────────────────────┐
│  📦 Finca Los Olivos - 3 productos (€42.00)                 │
│                                                             │
│  Elige cómo recibir estos productos:                        │
│                                                             │
│  (●) Envío estándar (3-5 días)              €5.95           │
│  ( ) Envío express (24-48h)                 €9.95           │
│  ( ) Recogida en origen (GRATIS)            €0.00           │
│      📍 Ctra. Priego km 5, Priego de Córdoba                │
│      🕐 L-V 9:00-14:00, 17:00-20:00                         │
│                                                             │
│  ℹ️  Envío gratis en pedidos de este productor > €50        │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  📦 Bodegas Robles - 2 productos (€37.50)                   │
│                                                             │
│  (●) Envío estándar (3-5 días)              €4.50           │
│  ( ) Recogida en bodega (GRATIS)            €0.00           │
│      📍 C/ San Francisco 8, Montilla                        │
│      🕐 L-S 10:00-14:00, 18:00-21:00                        │
└─────────────────────────────────────────────────────────────┘
6.2 Métodos de Entrega Disponibles
Método	Descripción	Configuración
shipping_standard	Envío estándar por transportista (3-5 días laborables)	Tarifa por zona + peso
shipping_express	Envío urgente (24-48h)	Tarifa premium (+50-100%)
shipping_refrigerated	Envío refrigerado para perecederos	Solo productores habilitados
pickup_origin	Recogida en las instalaciones del productor	Siempre gratis, horarios config.
pickup_point	Recogida en punto de conveniencia (futuro)	Integración con redes de puntos
6.3 Cálculo de Tarifas de Envío
Cada productor configura sus propias tarifas. El sistema calcula:
10.	Obtener zona de envío: origen del productor → destino del cliente
11.	Calcular peso total de los items de ese productor
12.	Aplicar tarifa: base_rate + (weight_rate × peso_kg)
13.	Verificar umbral de envío gratis del productor
14.	Si subtotal_productor >= umbral: envío = 0
Ejemplo de Tabla de Tarifas (por productor)
Zona	Base (€)	€/kg extra	Gratis desde
Local (misma provincia)	3.50	0.50	€30
Regional (Andalucía)	4.95	0.75	€50
Nacional (Península)	6.95	1.00	€75
Islas (Baleares/Canarias)	12.95	2.00	€100
 
7. Paso 7: Pago
El pago se gestiona con Stripe Payment Element, una solución moderna que soporta múltiples métodos de pago con una única integración y cumplimiento PCI automático.
7.1 Métodos de Pago Soportados
Método	Descripción	Disponibilidad
Tarjeta	Visa, Mastercard, American Express. 3DS automático.	Siempre
Bizum	Pago instantáneo popular en España. Redirect flow.	España (móvil)
Google Pay	Pago con credenciales guardadas en Google.	Chrome, Android
Apple Pay	Pago con Face/Touch ID en dispositivos Apple.	Safari, iOS
SEPA Direct Debit	Domiciliación bancaria para B2B o suscripciones.	Bajo demanda
7.2 Flujo de Pago con Stripe
15.	Crear PaymentIntent: Backend crea PI con amount = total del pedido
16.	Renderizar Payment Element: Frontend muestra formulario de Stripe
17.	Usuario completa datos: Introduce tarjeta o elige wallet
18.	Confirmar pago: stripe.confirmPayment() con redirect a return_url
19.	3DS si necesario: Stripe gestiona autenticación SCA automáticamente
20.	Webhook recibido: payment_intent.succeeded confirma el pago
21.	Página de confirmación: Usuario ve éxito con número de pedido
7.3 Código de Integración
// Backend: Crear PaymentIntent
$paymentIntent = \Stripe\PaymentIntent::create([
  'amount' => $order->getTotal() * 100, // céntimos
  'currency' => 'eur',
  'automatic_payment_methods' => ['enabled' => true],
  'metadata' => [
    'order_id' => $order->id(),
    'tenant_id' => $order->getTenantId(),
  ]
]);

// Frontend: Montar Payment Element
const elements = stripe.elements({
  clientSecret: paymentIntentClientSecret,
  appearance: { theme: 'stripe' }
});
const paymentElement = elements.create('payment');
paymentElement.mount('#payment-element');
7.4 Manejo de Errores
Error	Mensaje al Usuario	Acción
card_declined	Tu tarjeta ha sido rechazada. Prueba con otra tarjeta.	Reintentar con otra tarjeta
insufficient_funds	Fondos insuficientes. Prueba con otra tarjeta.	Reintentar con otra tarjeta
expired_card	Tu tarjeta ha caducado. Usa una tarjeta válida.	Reintentar con otra tarjeta
authentication_required	Se requiere autenticación adicional.	Redirect a 3DS
processing_error	Error temporal. Inténtalo de nuevo en unos segundos.	Reintentar automático
 
8. Página de Confirmación
Tras el pago exitoso, el cliente ve una página de confirmación que refuerza la compra y proporciona próximos pasos claros.
8.1 Contenido de la Página
•	Mensaje de éxito: '¡Gracias por tu pedido!' con check animado
•	Número de pedido: AGR-2026-00001 (grande, destacado)
•	Email de confirmación: 'Hemos enviado los detalles a [email]'
•	Resumen del pedido: Items, totales, dirección de envío
•	Timeline de envío: Por cada productor: fecha estimada de envío
•	Próximos pasos: Explicación clara de qué esperar
•	CTAs: [Ver mi pedido] [Seguir comprando] [Crear cuenta] (si guest)
8.2 Ofertas Post-Compra
Aprovechamos el momento de máxima satisfacción para:
•	Crear cuenta (guest): 'Crea una cuenta para seguir tu pedido fácilmente' - solo pide password
•	Newsletter: Si no está suscrito, ofrecer opt-in con incentivo (10% próxima compra)
•	Productos relacionados: 'Otros clientes también compraron...' (upsell)
•	Compartir: Botones para compartir en redes (opcional, sin ser invasivo)
8.3 Tracking de Conversión
Eventos disparados en la página de confirmación:
// Google Analytics 4 - Enhanced E-commerce
gtag('event', 'purchase', {
  transaction_id: 'AGR-2026-00001',
  value: 89.95,
  currency: 'EUR',
  shipping: 10.45,
  items: [/* array de productos */]
});

// Meta Pixel
fbq('track', 'Purchase', {
  value: 89.95,
  currency: 'EUR',
  content_ids: ['SKU1', 'SKU2'],
  content_type: 'product'
});
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Carrito multi-vendor: agrupación por productor, validaciones de stock, persistencia.	47_Commerce_Core
Sprint 2	Semana 3-4	Checkout panes 1-3: identificación, dirección con Google Places, guardado.	Sprint 1
Sprint 3	Semana 5-6	Método de entrega por productor: cálculo de tarifas, zonas, envío gratis.	Sprint 2
Sprint 4	Semana 7-8	Integración Stripe Payment Element: todos los métodos, manejo de errores, webhooks.	Sprint 3 + Stripe
Sprint 5	Semana 9-10	Cupones y promociones. Página de confirmación. Emails transaccionales.	Sprint 4
Sprint 6	Semana 11-12	Tracking de conversión (GA4, Meta). Recuperación de carrito abandonado. QA. Go-live.	Sprint 5
--- Fin del Documento ---
50_AgroConecta_Checkout_Flow_v1.docx | Jaraba Impact Platform | Enero 2026
