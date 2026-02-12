PORTAL DEL CLIENTE
Mi Cuenta, Pedidos, Favoritos y Preferencias
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	53_AgroConecta_Customer_Portal
Dependencias:	49_Order_System, 50_Checkout_Flow, User System
 
1. Resumen Ejecutivo
Este documento especifica el Portal del Cliente para AgroConecta, el área privada donde los compradores gestionan sus pedidos, direcciones, favoritos y preferencias. Proporciona una experiencia personalizada que fomenta la fidelización y facilita la recompra.
1.1 Objetivos del Portal
•	Self-service completo: El cliente gestiona todo sin soporte
•	Transparencia total: Estado de pedidos en tiempo real
•	Recompra facilitada: Un clic para repetir pedidos anteriores
•	Personalización: Favoritos y recomendaciones basadas en historial
•	Fidelización: Programa de puntos y beneficios por compras
•	Mobile-first: Diseño optimizado para móvil
1.2 Stack Tecnológico
Componente	Tecnología
Autenticación	Drupal User + Social Auth (Google, Facebook, Apple)
Frontend	Drupal Theme + Alpine.js para interactividad
Gestión de Estado	LocalStorage para carrito + Session para usuario
Notificaciones	Email (ActiveCampaign) + Push (Web Push API)
Tracking Pedidos	WebSocket (Mercure) para actualizaciones real-time
Direcciones	Address Field + Google Places Autocomplete
Favoritos	Flag module con almacenamiento en BD
Seguridad	2FA opcional, rate limiting, CSRF protection
1.3 Secciones del Portal
Sección	Funcionalidades	Prioridad
Mi Cuenta	Dashboard, resumen, accesos rápidos	P0 - Crítica
Mis Pedidos	Historial, detalle, tracking, repetir pedido	P0 - Crítica
Mis Direcciones	CRUD direcciones, dirección por defecto	P0 - Crítica
Mis Favoritos	Productos guardados, listas personalizadas	P1 - Alta
Mis Reseñas	Reseñas escritas, pendientes de escribir	P1 - Alta
Mis Puntos	Saldo, historial, canjear puntos	P2 - Media
Mis Datos	Perfil, contraseña, preferencias, RGPD	P0 - Crítica
Notificaciones	Centro de notificaciones, preferencias	P2 - Media
 
2. Dashboard Mi Cuenta
La página principal del área de cliente muestra un resumen personalizado con accesos rápidos a las funciones más utilizadas.
2.1 Layout del Dashboard
┌─────────────────────────────────────────────────────────────────┐
│  👤 ¡Hola, María!                              [Cerrar sesión]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📦 PEDIDO EN CURSO                                             │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ #AGR-2026-00089 • 3 productos • €67.50                  │    │
│  │ Estado: 🚚 En reparto - Llegará hoy                     │    │
│  │ [Ver tracking]                                          │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                 │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌───────────┐  │
│  │ 📦 Pedidos  │ │ 📍 Direc.   │ │ ❤️ Favoritos│ │ ⭐ Reseñas │  │
│  │    12       │ │    2        │ │    8        │ │   3 pend. │  │
│  └─────────────┘ └─────────────┘ └─────────────┘ └───────────┘  │
│                                                                 │
│  🔄 COMPRA DE NUEVO                                             │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐            │
│  │ [Imagen] │ │ [Imagen] │ │ [Imagen] │ │ [Imagen] │            │
│  │ AOVE 1L  │ │ Queso    │ │ Vino     │ │ Miel     │            │
│  │ [Añadir] │ │ [Añadir] │ │ [Añadir] │ │ [Añadir] │            │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘            │
│                                                                 │
│  🎁 TUS PUNTOS: 450 pts          [Ver beneficios disponibles]   │
│                                                                 │
│  💡 RECOMENDADOS PARA TI                                        │
│  [Productos basados en historial de compras]                    │
└─────────────────────────────────────────────────────────────────┘
2.2 Componentes del Dashboard
Componente	Descripción
Saludo personalizado	Nombre del usuario + hora del día (Buenos días/tardes/noches)
Pedido en curso	Si hay pedido activo: número, estado con icono, fecha estimada, CTA tracking
Tarjetas de acceso rápido	4 tarjetas: Pedidos (total), Direcciones (total), Favoritos (total), Reseñas (pendientes)
Compra de nuevo	Carrusel con productos de pedidos anteriores, botón añadir rápido al carrito
Saldo de puntos	Puntos actuales + enlace a beneficios canjeables
Recomendaciones	Productos sugeridos basados en historial y categorías favoritas
 
3. Mis Pedidos
Sección central para consultar el historial de pedidos, ver detalles, hacer seguimiento y gestionar devoluciones.
3.1 Lista de Pedidos
•	Ordenación: Por fecha descendente (más reciente primero)
•	Filtros: Estado (en curso, completados, cancelados), rango de fechas
•	Búsqueda: Por número de pedido o nombre de producto
•	Paginación: 10 pedidos por página, infinite scroll en móvil
Tarjeta de Pedido
┌─────────────────────────────────────────────────────────────────┐
│ 📦 Pedido #AGR-2026-00089              14 enero 2026            │
│ Estado: ✅ Entregado                                            │
│                                                                 │
│ [img] AOVE Picual 500ml x2     [img] Queso Manchego x1         │
│                                + 1 producto más                 │
│                                                                 │
│ Total: €67.50         [Ver detalle]  [Repetir pedido]           │
└─────────────────────────────────────────────────────────────────┘
3.2 Detalle del Pedido
Sección	Contenido
Cabecera	Número de pedido, fecha, estado con badge de color
Timeline de Estado	Visual: Confirmado → Preparando → Enviado → En reparto → Entregado
Productos	Lista: imagen, nombre, variación, cantidad, precio, enlace a reseña
Por Productor	Agrupación por productor con estado de cada sub-order
Envío	Transportista, tracking (link a página externa), fecha estimada/real
Dirección	Dirección de entrega formateada
Resumen Económico	Subtotal, envío, descuentos, total
Factura	Botón descargar factura PDF
Acciones	Repetir pedido, Solicitar devolución (si aplica), Contactar soporte
3.3 Tracking en Tiempo Real
Página de seguimiento integrada con actualizaciones automáticas:
•	Timeline visual: Pasos del envío con fechas y horas
•	Mapa: Ubicación aproximada si el carrier lo permite
•	Notificaciones: Push notification en cambios de estado
•	Link externo: Enlace a tracking del transportista
•	Multi-envío: Si el pedido tiene varios envíos, mostrar cada uno
3.4 Repetir Pedido
1.	Usuario hace clic en 'Repetir pedido'
2.	Sistema verifica disponibilidad de cada producto
3.	Si alguno no está disponible: mostrar alerta con alternativas
4.	Añadir todos los productos disponibles al carrito
5.	Redirigir al carrito con mensaje de confirmación
6.	Precargar dirección del pedido original si aún existe
 
4. Mis Direcciones
Gestión de direcciones de envío guardadas para agilizar futuros checkouts.
4.1 Entidad: customer_address
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario propietario	FK user.id, NOT NULL, INDEX
label	VARCHAR(50)	Nombre de la dirección (Casa, Trabajo...)	NOT NULL
given_name	VARCHAR(50)	Nombre	NOT NULL
family_name	VARCHAR(50)	Apellidos	NOT NULL
organization	VARCHAR(100)	Empresa (opcional)	NULLABLE
address_line1	VARCHAR(100)	Calle y número	NOT NULL
address_line2	VARCHAR(100)	Piso, puerta, etc.	NULLABLE
postal_code	VARCHAR(10)	Código postal	NOT NULL
locality	VARCHAR(100)	Ciudad	NOT NULL
administrative_area	VARCHAR(100)	Provincia	NOT NULL
country_code	CHAR(2)	Código país ISO	DEFAULT 'ES'
phone	VARCHAR(20)	Teléfono de contacto	NULLABLE
is_default	BOOLEAN	Dirección por defecto	DEFAULT FALSE
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
4.2 Funcionalidades
Funcionalidad	Descripción
Listar direcciones	Vista de tarjetas con todas las direcciones, destacando la por defecto
Añadir dirección	Formulario con Google Places Autocomplete, validación de CP
Editar dirección	Mismo formulario pre-rellenado
Eliminar dirección	Confirmación modal, no eliminar si es la única
Marcar por defecto	Un clic para establecer como default, desmarca la anterior
Límite	Máximo 10 direcciones por usuario
 
5. Mis Favoritos
Sistema de productos favoritos y listas personalizadas para organizar productos de interés.
5.1 Entidad: wishlist
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario propietario	FK user.id, NOT NULL, INDEX
name	VARCHAR(100)	Nombre de la lista	NOT NULL
description	VARCHAR(500)	Descripción opcional	NULLABLE
is_default	BOOLEAN	Lista principal de favoritos	DEFAULT FALSE
is_public	BOOLEAN	Lista compartible públicamente	DEFAULT FALSE
share_token	VARCHAR(32)	Token para compartir lista	UNIQUE, NULLABLE
created	DATETIME	Fecha de creación	NOT NULL, UTC
5.2 Entidad: wishlist_item
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
wishlist_id	INT	Lista a la que pertenece	FK wishlist.id, NOT NULL, INDEX
product_id	INT	Producto guardado	FK product_agro.id, NOT NULL
variation_id	INT	Variación específica (opcional)	FK variation.id, NULLABLE
notes	VARCHAR(500)	Notas del usuario	NULLABLE
priority	INT	Orden en la lista	DEFAULT 0
added_at	DATETIME	Fecha de adición	NOT NULL, UTC
5.3 Funcionalidades
•	Añadir a favoritos: Corazón en ficha de producto, toggle on/off
•	Lista por defecto: 'Mis Favoritos' se crea automáticamente
•	Crear lista: Listas temáticas: 'Para regalar', 'Navidad', etc.
•	Mover entre listas: Drag & drop o menú contextual
•	Compartir lista: URL única para compartir con amigos/familia
•	Alertas de precio: Notificar si un favorito baja de precio
•	Alertas de stock: Notificar si un favorito agotado vuelve a estar disponible
•	Añadir todo al carrito: Botón para añadir toda la lista de una vez
 
6. Mis Datos y Preferencias
Gestión del perfil personal, credenciales de acceso, preferencias y opciones de privacidad.
6.1 Datos Personales
Campo	Tipo	Editable
Nombre	VARCHAR(50)	Sí
Apellidos	VARCHAR(100)	Sí
Email	Email único	Sí (con verificación)
Teléfono	VARCHAR(20)	Sí
Fecha nacimiento	DATE (opcional)	Sí
Avatar	Imagen (o Gravatar)	Sí
NIF/CIF	VARCHAR(15) - para facturas	Sí
6.2 Seguridad
•	Cambiar contraseña: Requiere contraseña actual + nueva (con requisitos)
•	Verificación en dos pasos: 2FA opcional con app authenticator o SMS
•	Sesiones activas: Ver y cerrar sesiones en otros dispositivos
•	Historial de accesos: Últimos logins con IP y dispositivo
•	Cuentas vinculadas: Gestionar conexiones con Google, Facebook, Apple
6.3 Preferencias de Comunicación
Tipo de Comunicación	Email	Push	SMS
Confirmación de pedido	✓ Siempre	✓ Siempre	—
Actualizaciones de envío	✓ On	✓ On	Opcional
Ofertas y promociones	Opcional	Opcional	—
Nuevos productos de favoritos	Opcional	Opcional	—
Bajada de precio en favoritos	Opcional	Opcional	—
Recordatorio de carrito abandonado	Opcional	—	—
Newsletter semanal	Opcional	—	—
6.4 Privacidad y RGPD
•	Descargar mis datos: Exportar todos los datos en formato JSON/CSV
•	Eliminar mi cuenta: Proceso de baja con periodo de gracia de 30 días
•	Consentimientos: Ver y modificar consentimientos dados
•	Política de privacidad: Enlace a política actualizada
•	Cookies: Gestionar preferencias de cookies
 
7. Programa de Puntos
Sistema de fidelización que recompensa las compras y otras acciones con puntos canjeables por descuentos.
7.1 Entidad: loyalty_points
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario	FK user.id, NOT NULL, INDEX
balance	INT	Saldo actual de puntos	NOT NULL, >= 0
lifetime_earned	INT	Total de puntos ganados histórico	NOT NULL, >= 0
lifetime_spent	INT	Total de puntos canjeados	NOT NULL, >= 0
tier	VARCHAR(32)	Nivel: bronze, silver, gold, platinum	DEFAULT 'bronze'
tier_expires_at	DATE	Fecha de caducidad del nivel	NULLABLE
7.2 Formas de Ganar Puntos
Acción	Puntos	Límite
Compra (por cada €1 gastado)	1 punto	Sin límite
Primera compra (bienvenida)	100 puntos	Una vez
Registro de cuenta	50 puntos	Una vez
Completar perfil	25 puntos	Una vez
Escribir reseña verificada	10 puntos	5/mes
Referir un amigo (que compre)	200 puntos	10/año
Cumpleaños	50 puntos	1/año
Compra en productor nuevo	2x puntos	Por productor
7.3 Canjear Puntos
•	Conversión: 100 puntos = €1 de descuento
•	Mínimo canjeable: 500 puntos (€5)
•	Aplicación: En checkout, opción de aplicar puntos como descuento
•	Límite por pedido: Máximo 50% del pedido pagable con puntos
•	Caducidad: Los puntos caducan a los 12 meses de inactividad
7.4 Niveles del Programa
Nivel	Requisito	Beneficios
🥉 Bronze	0 - 499 pts/año	1 pt/€ en compras
🥈 Silver	500 - 1499 pts/año	1.25 pt/€, envío gratis > €40, acceso anticipado ofertas
🥇 Gold	1500 - 2999 pts/año	1.5 pt/€, envío gratis > €30, 5% dto adicional, soporte prioritario
💎 Platinum	3000+ pts/año	2 pt/€, envío gratis siempre, 10% dto, regalos exclusivos
 
8. APIs del Portal del Cliente
8.1 Endpoints de Usuario
Método	Endpoint	Descripción
GET	/api/v1/me	Datos del usuario autenticado
PATCH	/api/v1/me	Actualizar perfil
POST	/api/v1/me/password	Cambiar contraseña
POST	/api/v1/me/avatar	Subir avatar
DELETE	/api/v1/me	Solicitar eliminación de cuenta
GET	/api/v1/me/export	Descargar datos (RGPD)
8.2 Endpoints de Pedidos
Método	Endpoint	Descripción
GET	/api/v1/orders	Listar pedidos del usuario
GET	/api/v1/orders/{number}	Detalle de pedido
GET	/api/v1/orders/{number}/tracking	Estado de tracking en tiempo real
POST	/api/v1/orders/{number}/repeat	Añadir productos al carrito
GET	/api/v1/orders/{number}/invoice	Descargar factura PDF
POST	/api/v1/orders/{number}/return	Solicitar devolución
8.3 Endpoints de Direcciones
Método	Endpoint	Descripción
GET	/api/v1/addresses	Listar direcciones
POST	/api/v1/addresses	Crear dirección
PATCH	/api/v1/addresses/{id}	Actualizar dirección
DELETE	/api/v1/addresses/{id}	Eliminar dirección
POST	/api/v1/addresses/{id}/default	Marcar como por defecto
8.4 Endpoints de Favoritos
Método	Endpoint	Descripción
GET	/api/v1/wishlists	Listar listas de deseos
POST	/api/v1/wishlists	Crear nueva lista
POST	/api/v1/wishlists/{id}/items	Añadir producto a lista
DELETE	/api/v1/wishlists/{id}/items/{item_id}	Eliminar de lista
POST	/api/v1/wishlists/{id}/add-to-cart	Añadir toda la lista al carrito
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Autenticación: login, registro, social auth, recuperar contraseña.	User system
Sprint 2	Semana 3-4	Mi Cuenta dashboard. Mis Pedidos: lista, detalle, tracking.	49_Order_System
Sprint 3	Semana 5-6	Mis Direcciones: CRUD completo. Mis Datos: perfil, contraseña.	Sprint 2
Sprint 4	Semana 7-8	Mis Favoritos: listas, compartir. Repetir pedido. Tracking real-time.	Sprint 3 + Mercure
Sprint 5	Semana 9-10	Programa de puntos: ganar, canjear, niveles. Notificaciones.	Sprint 4
Sprint 6	Semana 11-12	RGPD: exportar, eliminar. Preferencias comunicación. QA. Go-live.	Sprint 5
--- Fin del Documento ---
53_AgroConecta_Customer_Portal_v1.docx | Jaraba Impact Platform | Enero 2026
