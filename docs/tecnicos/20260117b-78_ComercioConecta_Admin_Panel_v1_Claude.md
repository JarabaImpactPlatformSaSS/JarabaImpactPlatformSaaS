PANEL DE ADMINISTRACIÓN
Gestión Centralizada de la Plataforma ComercioConecta
Vertical ComercioConecta - JARABA IMPACT PLATFORM

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Código:	78_ComercioConecta_Admin_Panel
Dependencias:	Todos los documentos ComercioConecta
Base:	Drupal Admin + React Dashboard
 
1. Resumen Ejecutivo
Panel de Administración centralizado para gestionar comercios, usuarios, pedidos, contenido, moderación, configuración y analytics de la plataforma ComercioConecta.
1.1 Módulos del Panel
Módulo	Descripción	Prioridad
Dashboard	KPIs globales, alertas, actividad en tiempo real	Crítica
Comercios	CRUD, verificación, suspensión, onboarding	Crítica
Usuarios	Clientes, staff comercios, admins	Crítica
Pedidos	Vista global, incidencias, disputas	Alta
Catálogo	Productos globales, categorías, moderación	Alta
Reseñas	Moderación, reportes, respuestas	Alta
Pagos	Transacciones, payouts, comisiones	Alta
Promociones	Campañas globales, cupones plataforma	Media
Contenido	Páginas, banners, emails	Media
Analytics	Reportes, métricas, exportación	Alta
Configuración	Settings globales, feature flags	Media
Logs	Auditoría, errores, actividad	Media
1.2 Roles de Administración
Rol	Descripción	Permisos Clave
Super Admin	Acceso total a la plataforma	Todo, config crítica, eliminar datos
Platform Admin	Gestión operativa completa	Todo excepto config crítica
Support Manager	Soporte y moderación	Tickets, moderación, usuarios
Content Manager	Contenido y marketing	CMS, promociones, banners
Finance Admin	Gestión financiera	Pagos, comisiones, reportes
Viewer	Solo lectura	Ver dashboards y reportes
 
2. Dashboard Principal
2.1 KPIs de Plataforma
KPI	Descripción	Comparación	Alerta Si
GMV Hoy	Gross Merchandise Value	vs. ayer, vs. semana	< 80% promedio
Pedidos Hoy	Total pedidos procesados	vs. ayer	< 70% promedio
Comercios Activos	Con pedidos últimos 30d	vs. mes anterior	Baja > 5%
Nuevos Usuarios	Registros hoy	vs. ayer	—
Tasa Conversión	Visitas → Compras	vs. media 7d	< 2%
Ticket Medio	AOV plataforma	vs. media 30d	Baja > 10%
Incidencias Abiertas	Pedidos con problemas	—	> 1% pedidos
Reseñas Pendientes	Sin moderar	—	> 50
Payouts Pendientes	Sin procesar > 48h	—	> 0
2.2 Centro de Alertas
// Alertas del dashboard admin  CRÍTICAS (Rojo): - Servicio de pagos no disponible - Error rate > 5% en últimos 15 min - Queue de emails atascada  ALTAS (Naranja): - Comercio pendiente verificación > 48h - Comercio con > 5 disputas abiertas - Payout fallido - Pedido sin procesar > 24h  MEDIAS (Amarillo): - Reseña reportada pendiente > 24h - Producto reportado - Click & Collect expirado  INFO (Azul): - Nuevo comercio registrado - Récord de GMV batido
 
3. Gestión de Comercios
3.1 Lista de Comercios
Columna	Descripción	Filtrable
ID	ID interno	Sí
Nombre	Nombre comercial	Sí (búsqueda)
Estado	active/pending/suspended/closed	Sí (multi)
Categoría	Categoría principal	Sí
Ubicación	Ciudad/Provincia	Sí
GMV (30d)	Ventas últimos 30 días	Sí (rango)
Pedidos (30d)	Pedidos últimos 30 días	Sí
Rating	Valoración media	Sí (rango)
Verificado	Estado verificación	Sí
Fecha Alta	Fecha de registro	Sí (rango)
3.2 Perfil de Comercio (Admin View)
Tabs del perfil de comercio:  [General] - Info básica, direcciones, horarios, estado [Documentos] - Docs de verificación, aprobar/rechazar [Financiero] - Stripe Connect, balance, payouts, comisiones [Pedidos] - Lista, métricas, incidencias [Productos] - Catálogo, moderación masiva [Reseñas] - Recibidas, reportadas [Logs] - Historial de cambios, acciones admin
3.3 Flujo de Verificación
Estados: NOT_STARTED → DOCUMENTS_PENDING → UNDER_REVIEW → APPROVED/REJECTED  Documentos requeridos: 1. Alta en Hacienda / IAE (obligatorio) 2. CIF / NIF del titular (obligatorio) 3. Certificado titularidad bancaria (obligatorio) 4. Foto del establecimiento (opcional)  Acciones admin: - approve_document: Aprobar documento individual - reject_document: Rechazar con motivo - request_document: Solicitar documento adicional - approve_merchant: Aprobar comercio completo - suspend_merchant: Suspender temporalmente - reactivate_merchant: Reactivar
 
4. Gestión de Usuarios
4.1 Tipos de Usuarios
Tipo	Descripción	Acciones Admin
Customer	Clientes compradores	Ver, Editar, Suspender, Eliminar (GDPR)
Merchant Owner	Propietario de comercio	Ver, Editar, Reset password
Merchant Staff	Personal del comercio	Ver, Editar, Desvincular
Admin	Administradores plataforma	Crear, Editar, Permisos
Support	Agentes de soporte	Crear, Editar, Permisos
4.2 Perfil de Cliente (Admin View)
Tabs del perfil de cliente:  [General] - Info personal, direcciones, preferencias [Pedidos] - Historial completo, estadísticas [Fidelización] - Nivel, puntos, historial [Reseñas] - Escritas, reportadas [Tickets] - Soporte, comunicaciones [Logs] - Logins, cambios, acciones admin  Acciones disponibles: - Editar perfil - Reset password - Verificar email manualmente - Ajustar puntos de fidelidad - Suspender cuenta - Eliminar cuenta (GDPR) - Exportar datos (GDPR) - Impersonar (login as user)
 
5. Gestión de Pedidos
5.1 Vista Global de Pedidos
Columna	Descripción	Filtros
Nº Pedido	ORD-2026-XXXXXX	Búsqueda
Fecha	Fecha y hora	Rango de fechas
Cliente	Nombre del cliente	Búsqueda
Comercio	Nombre del comercio	Select múltiple
Total	Importe total	Rango
Estado	Estado actual	Multi-select
Fulfillment	Envío / C&C / Local	Multi-select
Pago	paid / pending / refunded	Multi-select
Incidencia	Con/sin incidencia	Sí/No
Disputa	Con/sin disputa	Sí/No
5.2 Gestión de Incidencias
Tipos de incidencias:  ENVÍO: - shipping_delayed: Retraso - shipping_lost: Paquete perdido - shipping_damaged: Dañado  PRODUCTO: - product_wrong: Incorrecto - product_defective: Defectuoso - product_missing: Faltante  PAGO: - payment_failed: Fallo - refund_failed: Reembolso fallido  Estados: OPEN → IN_PROGRESS → PENDING_MERCHANT → RESOLVED/ESCALATED  Resoluciones: full_refund, partial_refund, replacement, store_credit, coupon
 
6. Moderación de Contenido
6.1 Cola de Moderación
Tipo	Trigger	Prioridad	SLA
Reseña reportada	Usuario reporta	Alta	24h
Reseña automática	IA detecta contenido	Media	48h
Producto reportado	Reportado fraudulento	Alta	24h
Comercio reportado	Múltiples quejas	Alta	24h
Imagen sospechosa	IA detecta inapropiado	Alta	24h
6.2 Reglas de Auto-Moderación
Reglas automáticas:  REVIEWS: - spam_keywords: Flag si contiene palabras prohibidas - competitor_mention: Flag si menciona competidores - profanity: Auto-reject si contiene insultos - excessive_caps: Flag si > 50% mayúsculas  PRODUCTOS: - prohibited_items: Auto-hide si categoría prohibida - price_anomaly: Flag si precio > 10x media categoría  IMAGENES: - nsfw_detection: Auto-hide si AI confidence > 0.8
 
7. Gestión Financiera
7.1 Dashboard Financiero
KPI	Descripción	Período
GMV	Gross Merchandise Value	Hoy / Mes
Revenue	Comisiones de plataforma	Hoy / Mes
Pending Payouts	Pendiente pagar a comercios	Actual
Refunds	Total reembolsado	Hoy / Mes
Chargeback Rate	Porcentaje de disputas	Mes (threshold < 1%)
Processing Fees	Comisiones Stripe	Mes
7.2 Gestión de Payouts
Estado	Descripción	Acciones
Pending	Pendiente de procesar	Procesar, Retener
Processing	En proceso	—
Completed	Completado	Ver detalles
Failed	Fallido	Reintentar, Investigar
On Hold	Retenido (disputa)	Liberar, Investigar
Cancelled	Cancelado	Ver motivo
 
8. Analytics y Reportes
8.1 Reportes Disponibles
Reporte	Contenido	Frecuencia	Formatos
Ventas	GMV, pedidos, AOV por período	Diario/Semanal/Mensual	Excel, PDF
Comercios	Performance por comercio	Semanal/Mensual	Excel
Productos	Top productos, categorías	Semanal	Excel
Clientes	Nuevos, activos, churn	Mensual	Excel, PDF
Financiero	Revenue, comisiones, payouts	Mensual	Excel, PDF
Moderación	Items moderados, tiempos	Semanal	Excel
Incidencias	Tipos, resolución, SLA	Semanal	Excel
8.2 Widgets de Analytics
Dashboard Analytics:  1. OVERVIEW - GMV, pedidos, AOV, usuarios activos 2. GRÁFICOS - GMV diario, pedidos por día semana, métodos pago 3. TABLAS - Top 10 comercios, productos, categorías 4. MAPAS - Heatmap pedidos por provincia 5. FUNNELS - Visita → Producto → Carrito → Checkout → Compra 6. COHORTS - Retención por mes de registro, LTV  Filtros globales: fechas, comercio, categoría, región, dispositivo
 
9. Configuración Global
9.1 Secciones de Configuración
Sección	Contenido	Rol Mínimo
General	Nombre plataforma, logo, contacto	Platform Admin
Comercios	Requisitos, comisiones default	Platform Admin
Pedidos	Estados, tiempos, políticas	Platform Admin
Pagos	Proveedores, comisiones, límites	Super Admin
Envíos	Carriers default, zonas, tarifas	Platform Admin
Fidelización	Niveles, puntos, recompensas	Platform Admin
Notificaciones	Templates, canales	Content Manager
Legal	Términos, privacidad, cookies	Super Admin
Feature Flags	Habilitar/deshabilitar funciones	Super Admin
9.2 Feature Flags
Feature flags para control granular:  flash_offers: enabled=true, rollout=100% click_collect: enabled=true, rollout=100% local_delivery: enabled=true, rollout=50% whatsapp_notifications: enabled=false (en desarrollo) ai_product_descriptions: enabled=true, rollout=100% pos_integration: enabled=true, categories=[fashion, electronics] dynamic_pricing: enabled=false
 
10. Logs y Auditoría
10.1 Tipos de Logs
Tipo	Contenido	Retención	Acceso
Audit Log	Cambios entidades críticas	2 años	Super Admin
Admin Activity	Acciones de admins	1 año	Platform Admin
Access Log	Logins, intentos fallidos	90 días	Platform Admin
Error Log	Errores de aplicación	30 días	Platform Admin
Payment Log	Transacciones de pago	7 años	Finance Admin
Email Log	Emails enviados	90 días	Content Manager
10.2 Estructura Audit Log
AuditLogEntry: - id, timestamp - user_id, user_type, user_ip - action (create, update, delete, view, export) - entity_type, entity_id, entity_label - changes: [{field, old_value, new_value}] - severity: info | warning | critical  Acciones auditadas: - merchant.* (create, update, verify, suspend, delete) - user.* (create, update, suspend, delete, impersonate) - payout.* (process, hold, adjust) - config.* (update, feature_flag.toggle) - content.* (approve, reject, delete)
 
11. APIs de Administración
11.1 Endpoints Principales
Método	Endpoint	Descripción
GET	/api/v1/admin/dashboard/kpis	KPIs de plataforma
GET	/api/v1/admin/dashboard/alerts	Alertas activas
GET	/api/v1/admin/merchants	Listar comercios
GET	/api/v1/admin/merchants/{id}	Detalle comercio
POST	/api/v1/admin/merchants/{id}/verify	Verificar comercio
POST	/api/v1/admin/merchants/{id}/suspend	Suspender comercio
GET	/api/v1/admin/users	Listar usuarios
POST	/api/v1/admin/users/{id}/impersonate	Impersonar usuario
GET	/api/v1/admin/orders	Listar pedidos global
POST	/api/v1/admin/orders/{id}/resolve	Resolver incidencia
GET	/api/v1/admin/moderation/queue	Cola de moderación
POST	/api/v1/admin/moderation/{type}/{id}/approve	Aprobar contenido
GET	/api/v1/admin/finance/payouts	Lista de payouts
POST	/api/v1/admin/finance/payouts/{id}/process	Procesar payout
GET	/api/v1/admin/analytics/overview	Resumen analytics
POST	/api/v1/admin/reports/generate	Generar reporte
 
12. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Dashboard, KPIs, Alertas, Permisos	Drupal Admin
Sprint 2	Semana 3-4	Comercios: lista, detalle, verificación	Sprint 1
Sprint 3	Semana 5-6	Usuarios, Pedidos global, Incidencias	Sprint 2
Sprint 4	Semana 7-8	Moderación, Cola, Reglas automáticas	Sprint 3
Sprint 5	Semana 9-10	Financiero, Payouts, Comisiones	Sprint 4
Sprint 6	Semana 11-12	Analytics, Reportes, Config, Audit, QA	Sprint 5
12.1 Criterios de Aceptación Sprint 2
✓ Listar comercios con filtros avanzados
✓ Ver perfil completo de comercio
✓ Flujo de verificación funcional
✓ Suspender/reactivar comercio
✓ Notas internas y audit log
12.2 Dependencias
• Drupal Admin y sistema de permisos
• Todos los módulos ComercioConecta (62-77)
• Stripe Connect (financiero)
• React + Recharts (dashboards)
• PhpSpreadsheet + DOMPDF (exportación)
--- Fin del Documento ---
78_ComercioConecta_Admin_Panel_v1.docx | Jaraba Impact Platform | Enero 2026
