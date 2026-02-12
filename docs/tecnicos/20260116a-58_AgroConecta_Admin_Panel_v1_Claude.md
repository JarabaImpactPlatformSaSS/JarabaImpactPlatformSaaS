PANEL DE ADMINISTRACIÓN
Backoffice, Gestión de Contenidos y Operaciones
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	58_AgroConecta_Admin_Panel
Dependencias:	04_Core_Permisos_RBAC, All AgroConecta modules
 
1. Resumen Ejecutivo
Este documento especifica el Panel de Administración para AgroConecta, el backoffice centralizado que permite a los administradores gestionar todos los aspectos del marketplace: contenidos, usuarios, pedidos, productores, configuraciones y operaciones diarias.
1.1 Objetivos del Sistema
•	Centralización: Un único punto de acceso para toda la gestión
•	Eficiencia: Workflows optimizados para tareas frecuentes
•	Control: Visibilidad completa de operaciones y contenidos
•	Seguridad: Acceso granular basado en roles y permisos
•	Auditoría: Registro de todas las acciones administrativas
•	Multi-tenant: Gestión aislada por tenant cuando aplique
1.2 Stack Tecnológico
Componente	Tecnología
Framework Admin	Drupal Admin UI + Gin Admin Theme
Tablas/Listados	Views + VBO (Views Bulk Operations)
Formularios	Drupal Form API + Field UI + Inline Entity Form
Permisos	RBAC via Drupal Permissions + Group module
Búsqueda Admin	Admin Toolbar Search + custom filters
Acciones en lote	VBO + ECA para automatizaciones
Audit Log	Entity Activity Tracker + custom logging
UX/UI	Responsive design, keyboard shortcuts, quick actions
1.3 Roles Administrativos
Rol	Responsabilidades	Acceso
Super Admin	Configuración global, multi-tenant, usuarios admin	Todo
Tenant Admin	Gestión completa de su marketplace	Su tenant
Content Manager	Productos, categorías, contenido editorial	Contenido
Operations Manager	Pedidos, envíos, incidencias, logística	Operaciones
Producer Manager	Onboarding, aprobación, soporte productores	Productores
Support Agent	Atención al cliente, tickets, reclamaciones	Soporte
Marketing Manager	Promociones, campañas, comunicaciones	Marketing
Finance Manager	Facturación, payouts, reconciliación	Finanzas
 
2. Estructura del Panel de Administración
2.1 Menú Principal
┌─────────────────────────────────────────────────────────────────────────┐
│  🏠 AGROCONECTA ADMIN                    👤 Admin ▼  🔔 3  ⚙️ Config  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  📊 Dashboard                                                           │
│                                                                         │
│  📦 PEDIDOS                                                             │
│     ├── Todos los pedidos                                              │
│     ├── Pendientes de envío (12)                                       │
│     ├── Incidencias (3)                                                │
│     └── Devoluciones                                                   │
│                                                                         │
│  🏷️ CATÁLOGO                                                            │
│     ├── Productos                                                       │
│     ├── Categorías                                                      │
│     ├── Colecciones                                                     │
│     └── Atributos                                                       │
│                                                                         │
│  🏪 PRODUCTORES                                                         │
│     ├── Todos los productores                                          │
│     ├── Pendientes aprobación (5)                                      │
│     └── Payouts                                                         │
│                                                                         │
│  👥 CLIENTES                                                            │
│     ├── Todos los clientes                                             │
│     ├── Segmentos                                                       │
│     └── Reseñas                                                         │
│                                                                         │
│  📣 MARKETING                                                           │
│     ├── Promociones                                                     │
│     ├── Cupones                                                         │
│     └── Banners                                                         │
│                                                                         │
│  💰 FINANZAS                                                            │
│  📊 REPORTES                                                            │
│  ⚙️ CONFIGURACIÓN                                                       │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
2.2 Módulos del Panel
Módulo	Funcionalidades	Sección
Dashboard	KPIs, alertas, accesos rápidos, tareas pendientes	3
Gestión Pedidos	Listado, detalle, estados, acciones, incidencias	4
Gestión Catálogo	Productos, categorías, colecciones, atributos	5
Gestión Productores	Onboarding, aprobación, monitoreo, payouts	6
Gestión Clientes	Usuarios, segmentos, comunicaciones, soporte	7
Configuración	Ajustes globales, integraciones, permisos	8
 
3. Dashboard Administrativo
Pantalla principal con visión general del estado del marketplace y accesos rápidos.
3.1 Widgets del Dashboard
Widget	Contenido	Roles
KPIs del Día	GMV, pedidos, nuevos usuarios, rating medio	Todos
Tareas Pendientes	Pedidos por confirmar, productores por aprobar, reseñas por moderar	Todos
Alertas Activas	Incidencias, stock bajo, errores de pago	Todos
Últimos Pedidos	5 pedidos más recientes con estado y acciones	Operations+
Gráfico Ventas	Ventas últimos 7 días vs semana anterior	Admin+
Top Productos Hoy	5 productos más vendidos del día	Content+
Acciones Rápidas	+ Producto, + Promoción, Exportar pedidos	Según permiso
Actividad Reciente	Log de acciones de otros admins	Admin+
3.2 Acciones Rápidas
•	Keyboard shortcuts: Alt+N (nuevo), Alt+S (buscar), Alt+O (pedidos)
•	Command palette: Ctrl+K para acceder a cualquier función
•	Búsqueda global: Buscar pedidos, productos, clientes, productores
•	Favoritos: Guardar vistas y filtros frecuentes
 
4. Gestión de Pedidos
4.1 Listado de Pedidos
┌─────────────────────────────────────────────────────────────────────────┐
│  📦 PEDIDOS                    [+ Crear Manual]  [Exportar]  [Filtros]  │
├─────────────────────────────────────────────────────────────────────────┤
│  🔍 [Buscar por nº, cliente, producto...]   Estado: [Todos ▼]           │
│  Fecha: [Hoy ▼]  Productor: [Todos ▼]  Incidencia: [ ]                  │
├─────────────────────────────────────────────────────────────────────────┤
│  [ ] │ Pedido     │ Cliente       │ Total   │ Estado      │ Fecha       │
│ ─────┼────────────┼───────────────┼─────────┼─────────────┼─────────────│
│  [ ] │ #AC-10234  │ María García  │ €67.50  │ 🟡 Procesando│ 16/01 10:23│
│  [ ] │ #AC-10233  │ Juan López    │ €123.00 │ 📦 Enviado  │ 16/01 09:45 │
│  [✓] │ #AC-10232  │ Ana Martín    │ €45.90  │ ⚠️ Incidencia│ 16/01 08:30│
│  [ ] │ #AC-10231  │ Pedro Ruiz    │ €89.00  │ ✅ Entregado │ 15/01 18:20│
│  [ ] │ #AC-10230  │ Laura Sánchez │ €156.50 │ ✅ Entregado │ 15/01 16:45│
├─────────────────────────────────────────────────────────────────────────┤
│  Con seleccionados: [Marcar enviado ▼]  [Imprimir etiquetas]            │
│                                                                         │
│  Mostrando 1-50 de 1,234 pedidos          [← Anterior] [Siguiente →]    │
└─────────────────────────────────────────────────────────────────────────┘
4.2 Acciones en Lote (VBO)
Acción	Descripción	Confirmación
Marcar como enviado	Cambiar estado a 'Enviado' con tracking	Sí
Imprimir etiquetas	Generar PDF con etiquetas de envío	No
Imprimir albaranes	Generar PDF con albaranes	No
Notificar cliente	Enviar email de actualización	Sí
Asignar incidencia	Marcar con tipo de incidencia	Sí
Exportar selección	Descargar CSV/Excel de pedidos	No
Cancelar pedidos	Cancelar y notificar (requiere motivo)	Sí + motivo
4.3 Detalle de Pedido
Información mostrada en la vista de detalle:
•	Cabecera: Nº pedido, fecha, estado actual, acciones
•	Cliente: Nombre, email, teléfono, historial de pedidos
•	Dirección: Envío y facturación, mapa embebido
•	Productos: Líneas con imagen, nombre, cantidad, precio, productor
•	Totales: Subtotal, descuentos, envío, impuestos, total
•	Pago: Método, estado, ID transacción, reembolsos
•	Envío: Carrier, tracking, timeline de estados
•	Timeline: Historial completo de cambios de estado
•	Notas: Notas internas del equipo, notas del cliente
 
5. Gestión de Catálogo
5.1 Gestión de Productos
Función	Descripción
Listado	Tabla con filtros por categoría, productor, estado, stock, precio
Crear/Editar	Formulario completo: info básica, precios, stock, imágenes, SEO, variantes
Duplicar	Copiar producto existente como base para nuevo
Importar	Carga masiva desde CSV/Excel con mapeo de campos
Exportar	Descargar catálogo completo o filtrado
Acciones lote	Publicar, despublicar, eliminar, cambiar categoría, ajustar precio
Historial	Ver cambios realizados y por quién
Preview	Ver producto como aparecerá en el frontend
5.2 Gestión de Categorías
•	Árbol visual: Estructura jerárquica con drag & drop para reordenar
•	Crear/Editar: Nombre, descripción, imagen, padre, orden, SEO
•	Mover productos: Reasignar productos entre categorías
•	Merge: Fusionar categorías (mover productos y eliminar)
•	Estadísticas: Nº productos, ventas, visitas por categoría
5.3 Gestión de Colecciones
•	Manuales: Seleccionar productos específicos, ordenar manualmente
•	Automáticas: Definir reglas (tag, categoría, precio, etc.)
•	Programar: Fecha inicio y fin de publicación
•	Preview: Ver productos que entran en la colección
 
6. Gestión de Productores
6.1 Flujo de Onboarding
┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐
│ Solicitud│───▶│ Revisión│───▶│Documentos│───▶│ Stripe  │───▶│  Activo │
│ recibida │    │  admin  │    │verificados│   │ Connect │    │         │
└─────────┘    └─────────┘    └─────────┘    └─────────┘    └─────────┘
6.2 Panel de Revisión
Sección	Información a Revisar
Datos empresa	Razón social, CIF, dirección, datos de contacto
Documentación	Alta autónomos/sociedad, certificado sanitario, seguros
Productos	Descripción de lo que venderá, categorías, fotos ejemplo
Logística	Capacidad de envío, zonas, tiempos de preparación
Verificación	Checklist de requisitos cumplidos
Acciones	Aprobar, rechazar (con motivo), solicitar más info
6.3 Monitoreo de Productores
Métricas y alertas por productor:
•	Ventas: GMV, pedidos, productos vendidos, tendencia
•	Calidad: Rating medio, % reseñas negativas, respuesta a reviews
•	Operaciones: Tiempo confirmación, fulfillment rate, incidencias
•	Stock: Productos agotados, % del catálogo disponible
•	Alertas: Rating < 4.0, incidencias > 5%, inactividad > 7 días
6.4 Gestión de Payouts
•	Ver balance: Saldo pendiente por productor
•	Historial: Todos los pagos realizados
•	Retenciones: Bloquear payouts por incidencias pendientes
•	Manual payout: Forzar pago fuera de ciclo si necesario
•	Reportes: Descargar liquidaciones para contabilidad
 
7. Gestión de Clientes
7.1 Listado de Clientes
Columna	Descripción
Nombre	Nombre completo con enlace a perfil
Email	Email con icono de verificación
Registro	Fecha de alta
Pedidos	Nº total de pedidos
Total gastado	Suma de todos sus pedidos
Último pedido	Fecha del pedido más reciente
Segmento	Nuevo, Recurrente, VIP, Inactivo
Estado	Activo, Bloqueado
7.2 Perfil de Cliente
•	Información personal: Datos de contacto, direcciones guardadas
•	Historial de pedidos: Todos sus pedidos con detalle rápido
•	Métodos de pago: Tarjetas guardadas (últimos 4 dígitos)
•	Reseñas: Reviews que ha dejado
•	Tickets soporte: Historial de incidencias
•	Puntos fidelidad: Balance actual, historial de movimientos
•	Notas internas: Comentarios del equipo sobre este cliente
•	Acciones: Enviar email, crear pedido manual, bloquear cuenta
7.3 Moderación de Reseñas
Cola	Criterio	Acciones
Pendientes	Reseñas flaggeadas por auto-moderación	Aprobar, Rechazar, Editar
Reportadas	Reseñas reportadas por usuarios	Mantener, Ocultar, Eliminar
Negativas	Reseñas 1-2 estrellas para seguimiento	Contactar cliente, Escalar
Sin respuesta	Negativas sin respuesta del productor >48h	Notificar productor
 
8. Configuración del Sistema
8.1 Ajustes Generales
Sección	Configuraciones
Tienda	Nombre, logo, favicon, datos de contacto, redes sociales
Localización	País, zona horaria, idiomas, moneda, formato fecha/número
Checkout	Pasos, campos requeridos, guest checkout, términos
Envío	Métodos, tarifas, zonas, umbral envío gratis
Impuestos	Tasas IVA, reglas por producto/zona, inclusión en precio
Pagos	Métodos activos, credenciales, configuración Stripe
Emails	Plantillas, remitente, logo, firma
SEO	Meta tags por defecto, robots, sitemap, Schema.org
8.2 Gestión de Usuarios Admin
•	Crear usuario: Email, nombre, rol, permisos adicionales
•	Editar permisos: Asignar/revocar permisos granulares
•	Desactivar: Bloquear acceso sin eliminar historial
•	2FA: Requerir autenticación de dos factores
•	Audit log: Ver actividad de cada usuario admin
8.3 Integraciones
Integración	Configuración	Estado
Stripe	API keys, webhooks, Connect settings	✅ Conectado
Carriers	MRW, SEUR, GLS, Correos: credenciales, defaults	✅ Conectado
Matomo	Site ID, URL tracking, GDPR consent	✅ Conectado
Email (SMTP)	Servidor, puerto, credenciales	✅ Conectado
Mailchimp	API key, listas, sincronización	⚪ No config
Slack	Webhook para alertas	⚪ No config
 
9. Auditoría y Logging
9.1 Entidad: admin_audit_log
Campo	Tipo	Descripción	Restricciones
id	BigSerial	ID interno	PRIMARY KEY
user_id	INT	Usuario que realizó la acción	FK user.id, NOT NULL
action	VARCHAR(50)	Tipo de acción	NOT NULL, INDEX
entity_type	VARCHAR(64)	Tipo de entidad afectada	NOT NULL, INDEX
entity_id	INT	ID de la entidad	NOT NULL
old_values	JSONB	Valores antes del cambio	NULLABLE
new_values	JSONB	Valores después del cambio	NULLABLE
ip_address	VARCHAR(45)	IP del usuario	NOT NULL
user_agent	VARCHAR(255)	Browser/device	NULLABLE
created	TIMESTAMP	Momento de la acción	NOT NULL, INDEX
9.2 Acciones Auditadas
•	Login/logout de administradores
•	Creación, edición, eliminación de cualquier entidad
•	Cambios de estado en pedidos
•	Aprobación/rechazo de productores
•	Moderación de reseñas
•	Cambios en configuración
•	Creación/modificación de usuarios admin
•	Exportaciones de datos
•	Acciones en lote (bulk operations)
 
10. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Estructura base: Gin theme, menú, roles base, dashboard inicial	04_Core_RBAC
Sprint 2	Semana 3-4	Gestión pedidos: listado, filtros, detalle, acciones VBO	49_Order_System
Sprint 3	Semana 5-6	Gestión catálogo: productos, categorías, importar/exportar	48_Product_Catalog
Sprint 4	Semana 7-8	Gestión productores: onboarding, aprobación, monitoreo, payouts	52_Producer_Portal
Sprint 5	Semana 9-10	Gestión clientes: listado, perfiles, moderación reseñas	53_Customer_Portal
Sprint 6	Semana 11-12	Configuración, integraciones, audit log, QA, optimización	Sprint 5
--- Fin del Documento ---
58_AgroConecta_Admin_Panel_v1.docx | Jaraba Impact Platform | Enero 2026
