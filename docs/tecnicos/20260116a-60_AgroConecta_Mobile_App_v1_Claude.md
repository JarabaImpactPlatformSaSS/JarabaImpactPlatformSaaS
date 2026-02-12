APLICACIÓN MÓVIL
App Nativa iOS y Android para Clientes y Productores
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	60_AgroConecta_Mobile_App
Dependencias:	React Native, APIs AgroConecta, FCM
 
1. Resumen Ejecutivo
Este documento especifica la Aplicación Móvil para AgroConecta, disponible para iOS y Android, que permite a clientes comprar productos del campo y a productores gestionar su negocio desde cualquier lugar, con experiencia nativa optimizada para dispositivos móviles.
1.1 Objetivos de la App
•	Accesibilidad: Comprar y vender desde cualquier lugar
•	Engagement: Push notifications para mayor retención
•	Conversión: Checkout optimizado para móvil
•	Productividad: Gestión rápida para productores en campo
•	Offline: Funcionalidad básica sin conexión
•	Nativa: Experiencia fluida con features del dispositivo
1.2 Stack Tecnológico
Componente	Tecnología
Framework	React Native 0.73+ con Expo SDK 50
Lenguaje	TypeScript 5.x
Estado	Zustand + React Query (TanStack Query)
Navegación	React Navigation 6.x
UI Components	React Native Paper + custom design system
Auth	OAuth 2.0 + Secure storage (Keychain/Keystore)
Push	Firebase Cloud Messaging (FCM) + Expo Notifications
Analytics	Firebase Analytics + Sentry (crash reporting)
Offline	AsyncStorage + SQLite (expo-sqlite)
Pagos	Stripe React Native SDK + Apple Pay + Google Pay
1.3 Aplicaciones
App	Descripción	Usuarios
AgroConecta	App principal de compras para consumidores	Clientes finales
AgroConecta Pro	App de gestión para vendedores	Productores
 
2. App Cliente: AgroConecta
2.1 Estructura de Navegación
┌─────────────────────────────────────────────────────────────────────────┐
│                          AGROCONECTA APP                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  TAB NAVIGATION (Bottom Tabs)                                           │
│  ┌─────────┬─────────┬─────────┬─────────┬─────────┐                    │
│  │  🏠     │  🔍     │  🛒     │  ❤️     │  👤     │                    │
│  │  Home   │ Buscar  │ Carrito │Favoritos│ Cuenta  │                    │
│  └─────────┴─────────┴─────────┴─────────┴─────────┘                    │
│                                                                         │
│  STACK SCREENS (por cada tab)                                           │
│                                                                         │
│  Home Stack:        Search Stack:       Cart Stack:                     │
│  ├── HomeScreen     ├── SearchScreen    ├── CartScreen                  │
│  ├── CategoryScreen ├── ResultsScreen   ├── CheckoutScreen              │
│  ├── ProductScreen  ├── FiltersScreen   ├── PaymentScreen               │
│  └── ProducerScreen └── ProductScreen   └── ConfirmationScreen          │
│                                                                         │
│  Account Stack:     Favorites Stack:                                    │
│  ├── ProfileScreen  ├── WishlistScreen                                  │
│  ├── OrdersScreen   └── ProductScreen                                   │
│  ├── OrderDetailScr                                                     │
│  ├── AddressesScreen                                                    │
│  ├── NotificationsScr                                                   │
│  └── SettingsScreen                                                     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
2.2 Pantallas Principales
Pantalla	Contenido	Gestos
Home	Banners, categorías destacadas, productos recomendados, últimos pedidos	Pull refresh
Categoría	Grid de productos, filtros rápidos, ordenación	Scroll infinito
Producto	Galería swipeable, descripción, variantes, añadir carrito, reviews	Swipe galería
Búsqueda	Barra búsqueda, historial, sugerencias, resultados	Voice search
Carrito	Lista productos, cantidades, cupón, resumen, checkout	Swipe eliminar
Checkout	Dirección, método envío, pago (Apple/Google Pay), confirmar	Face/Touch ID
Mis Pedidos	Lista pedidos, estado, tracking en tiempo real	Pull refresh
Perfil	Datos personales, direcciones, métodos pago, preferencias	-
 
3. App Productor: AgroConecta Pro
3.1 Funcionalidades Principales
Módulo	Funcionalidades	Prioridad
Dashboard	Ventas hoy, pedidos pendientes, alertas, KPIs rápidos	P0
Pedidos	Lista pedidos, confirmar, marcar preparado, ver detalles	P0
Productos	Lista productos, editar stock, activar/desactivar, precios	P0
Inventario Rápido	Actualizar stock con scanner de código de barras	P1
Reseñas	Ver reseñas recibidas, responder	P1
Finanzas	Balance, historial payouts, próximo pago	P1
Crear Producto	Formulario simplificado con cámara para fotos	P2
Estadísticas	Gráficos de ventas, productos top, tendencias	P2
3.2 Flujo de Gestión de Pedido
┌──────────────┐   ┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│  🔔 PUSH     │──▶│  Ver Pedido  │──▶│  Confirmar   │──▶│   Preparar   │
│ Nuevo pedido │   │   detalle    │   │   pedido     │   │   pedido     │
└──────────────┘   └──────────────┘   └──────────────┘   └──────────────┘
                                                               │
                                                               ▼
                                                        ┌──────────────┐
                                                        │Marcar listo  │
                                                        │para recogida │
                                                        └──────────────┘
3.3 Funcionalidades Específicas Móvil
•	Scanner código barras: Buscar producto por EAN para actualizar stock
•	Cámara integrada: Tomar fotos de productos directamente
•	Push prioritarias: Notificaciones de pedidos con sonido especial
•	Acciones rápidas: Confirmar pedido desde notificación (actionable)
•	Widget: Widget de home con pedidos pendientes (iOS/Android)
 
4. Features Nativas
4.1 Capacidades del Dispositivo
Feature	Uso en AgroConecta	Librería
Cámara	Fotos productos, scanner QR/barcode	expo-camera, expo-barcode-scanner
Biometría	Login, confirmar pago	expo-local-authentication
Ubicación	Dirección automática, productores cercanos	expo-location
Mapas	Ubicación productor, tracking envío	react-native-maps
Galería	Seleccionar fotos existentes	expo-image-picker
Share	Compartir producto en redes	expo-sharing
Deep Links	Links directos a productos/pedidos	expo-linking
Haptics	Feedback táctil en acciones	expo-haptics
4.2 Pagos Móviles
•	Apple Pay: Checkout con un toque en iOS
•	Google Pay: Checkout con un toque en Android
•	Tarjeta guardada: Confirmar con Face ID / Touch ID / Huella
•	Stripe SDK: @stripe/stripe-react-native para gestión segura
4.3 Modo Offline
•	Catálogo cacheado: Productos visitados disponibles offline
•	Carrito persistente: Carrito guardado localmente
•	Pedidos offline: Ver historial sin conexión
•	Sync automático: Sincronización al recuperar conexión
•	Indicador: Banner visible cuando está offline
 
5. Push Notifications
5.1 Tipos de Notificaciones
Tipo	Ejemplo	Rich Media	Acciones
Pedido enviado	Tu pedido #AC-1234 está en camino 📦	Imagen producto	Ver tracking
Pedido entregado	Tu pedido ha llegado. ¡Disfrútalo! ✅	Imagen producto	Dejar reseña
Carrito abandonado	Tu AOVE Picual te espera en el carrito	Imagen producto	Comprar
Bajada de precio	¡El Queso Manchego ahora a €12.90!	Imagen + precio	Ver, Comprar
Nuevo pedido (Pro)	🔔 Nuevo pedido #AC-1234 (€67.50)	-	Confirmar
Reseña recibida (Pro)	Nueva reseña ⭐⭐⭐⭐⭐ en tu AOVE	-	Ver, Responder
5.2 Configuración FCM
// Registro de token push
async function registerForPushNotifications() {
  const { status } = await Notifications.requestPermissionsAsync();
  if (status !== 'granted') return;
  
  const token = await Notifications.getExpoPushTokenAsync({
    projectId: 'your-project-id'
  });
  
  // Enviar token al backend
  await api.post('/me/push-token', { token: token.data });
}
 
6. Diseño UI/UX
6.1 Design System
Elemento	Especificación	Token
Color Primario	#E87722 (Naranja AgroConecta)	$primary
Color Secundario	#00A9A5 (Verde Teal)	$secondary
Background	#FFFFFF (Light) / #1A1A1A (Dark)	$bg
Texto	#2D3748 (Light) / #F7FAFC (Dark)	$text
Tipografía	Inter (cuerpo) / Montserrat (títulos)	-
Border Radius	8px (cards), 24px (botones pill), 50% (avatars)	$radius
Spacing	4px base: 4, 8, 12, 16, 24, 32, 48, 64	$space-*
Shadows	elevation-1 a elevation-5 (Material Design)	$shadow-*
6.2 Componentes Reutilizables
•	ProductCard: Imagen, título, productor, precio, rating, add to cart
•	CategoryChip: Chip con icono para categorías
•	SearchBar: Barra con icono, placeholder, voice button
•	QuantitySelector: Stepper con - / cantidad / +
•	OrderStatusBadge: Badge con color según estado
•	RatingStars: Estrellas interactivas o display
•	PriceDisplay: Precio actual, precio anterior (tachado), badge descuento
•	EmptyState: Ilustración + mensaje + CTA
•	SkeletonLoader: Placeholders animados durante carga
6.3 Accesibilidad
•	VoiceOver/TalkBack: Labels descriptivos en todos los elementos
•	Contraste: WCAG AA mínimo (4.5:1)
•	Touch targets: Mínimo 44x44 puntos
•	Reduce motion: Respetar preferencias del sistema
•	Font scaling: Soporte para texto grande del sistema
 
7. Performance y Optimización
7.1 Métricas Target
Métrica	Target	Medición
Time to Interactive (TTI)	< 3 segundos	Flipper / Reactotron
First Contentful Paint	< 1.5 segundos	Firebase Perf
App Size (download)	< 50 MB	App Store / Play Store
Memory Usage	< 200 MB	Xcode / Android Studio
Frame Rate	60 FPS constante	Perf Monitor
Crash Rate	< 0.5%	Sentry / Crashlytics
API Response (P95)	< 500ms	Backend monitoring
7.2 Técnicas de Optimización
•	Lazy loading: Cargar pantallas bajo demanda
•	Image optimization: expo-image con caching agresivo
•	List virtualization: FlashList en lugar de FlatList
•	Memoization: useMemo, useCallback, React.memo
•	Bundle splitting: Código separado por features
•	Prefetching: Precargar datos probables (siguiente página)
•	Skeleton screens: Feedback visual inmediato
 
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Plataforma
Sprint 1	Semana 1-2	Setup proyecto: Expo, navegación, auth, design system base	Ambas
Sprint 2	Semana 3-4	App Cliente: Home, categorías, búsqueda, producto	Ambas
Sprint 3	Semana 5-6	App Cliente: Carrito, checkout, pagos (Stripe, Apple/Google Pay)	Ambas
Sprint 4	Semana 7-8	App Cliente: Cuenta, pedidos, favoritos, push notifications	Ambas
Sprint 5	Semana 9-10	App Pro: Dashboard, pedidos, productos, gestión stock	Ambas
Sprint 6	Semana 11-12	App Pro: Scanner, cámara, finanzas. QA, TestFlight/Beta	Ambas
Sprint 7	Semana 13-14	Polish, performance, accesibilidad, App Store review	Ambas
Sprint 8	Semana 15-16	Launch: App Store + Play Store, monitoring, hotfixes	Producción
8.1 Distribución
•	iOS: App Store (requiere cuenta Apple Developer $99/año)
•	Android: Google Play Store (cuenta Developer $25 único)
•	Beta testing: TestFlight (iOS) + Google Play Beta (Android)
•	CI/CD: EAS Build (Expo Application Services)
•	OTA Updates: EAS Update para fixes sin re-submit
--- Fin del Documento ---
60_AgroConecta_Mobile_App_v1.docx | Jaraba Impact Platform | Enero 2026
