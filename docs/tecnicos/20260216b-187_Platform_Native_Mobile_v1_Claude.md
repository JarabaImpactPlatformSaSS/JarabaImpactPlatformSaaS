
NATIVE MOBILE APP
Aplicacion Nativa iOS/Android con Capacitor y Funcionalidades Nativas
Nivel de Madurez: N2
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	187_Platform_Native_Mobile_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N2
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Especificacion de la aplicacion movil nativa que extiende la PWA actual (doc 109) con capacidades nativas de iOS y Android: push notifications, camara para QR scanning offline, geolocalizacion de proximidad, autenticacion biometrica y deep linking.

1.1 PWA vs Nativa: Que Aporta la App Nativa
Capacidad	PWA (Doc 109)	App Nativa (Este doc)	Impacto
Push Notifications	Limitado (no iOS Safari)	Nativo iOS + Android	CRITICO para engagement
Camara/QR	Basico via WebRTC	Nativo + offline scanning	CRITICO para Agro/Comercio
Geolocation	API Web (foreground)	Background + geofencing	ALTO para proximidad
Biometric Auth	WebAuthn (limitado)	FaceID/TouchID nativo	ALTO para UX
Deep Linking	URL schemes basicos	Universal Links + App Links	ALTO para marketing
Offline	Service Worker (limitado)	SQLite + sync queue	MEDIO para rural
Performance	Web-bound	Nativo/Near-native	MEDIO
 
2. Arquitectura Tecnica
2.1 Stack Tecnologico
Componente	Tecnologia	Justificacion
Framework	Capacitor (Ionic) + React	Reutiliza 70% del frontend existente
UI Components	Ionic React + jaraba_theme	Consistencia con design system
State Management	React Context + SWR	Mismo patron que web
Local Storage	SQLite via @capacitor-community/sqlite	Offline first
Push	Firebase Cloud Messaging	iOS + Android unificado
Camera	@capacitor/camera + ML Kit	QR scanning offline
Geolocation	@capacitor/geolocation	Background tracking
Biometrics	@capacitor-community/biometric-auth	FaceID/TouchID
Deep Links	@capacitor/app + Universal Links	Marketing attribution
CI/CD	Fastlane + GitHub Actions	Build automatizado

2.2 Modelo de Datos: mobile_device
Campo	Tipo	Descripcion
id	UUID	Identificador unico
user_id	UUID FK	Usuario propietario
device_token	VARCHAR(500)	FCM/APNs token
platform	ENUM	ios|android
os_version	VARCHAR(20)	Version del SO
app_version	VARCHAR(20)	Version de la app
device_model	VARCHAR(100)	Modelo del dispositivo
biometric_enabled	BOOLEAN	Biometria activada
push_enabled	BOOLEAN	Push activado
last_active	TIMESTAMP	Ultima actividad
tenant_id	UUID FK	Tenant asociado
 
3. Funcionalidades Nativas por Vertical
3.1 AgroConecta
•	QR Scanner offline para trazabilidad en campo sin cobertura
•	Captura de fotos georeferenciadas de productos/finca
•	Notificaciones push de nuevos pedidos y stock bajo
•	Modo offline con sync cuando recupera conexion

3.2 ComercioConecta
•	Scanner QR para ofertas flash y codigos de descuento
•	Geofencing: notificacion push al pasar cerca de comercio asociado
•	Wallet pass para tarjetas de fidelidad
•	Camara para digitalizar tickets y facturas

3.3 Empleabilidad
•	Push notifications de nuevas ofertas matching
•	Video interview via app con camara nativa
•	Scanner de tarjetas de visita con OCR
•	Calendario nativo con entrevistas programadas

3.4 Emprendimiento
•	Push de recordatorios de tareas y mentoring
•	Captura rapida de ideas de negocio (voz + foto)
•	Scanner de documentos para digitalizar facturas/albaranes
•	Widget de metricas de negocio en pantalla inicio

3.5 ServiciosConecta
•	Firma digital via pantalla tactil
•	Fotos de expediente con geolocalizacion
•	Push de nuevas reservas y cancelaciones
•	Videoconferencia integrada con camara nativa
 
4. Push Notifications System
4.1 Modelo de Datos: push_notification
Campo	Tipo	Descripcion
id	UUID	Identificador unico
recipient_id	UUID FK	Usuario destinatario
title	VARCHAR(100)	Titulo de la notificacion
body	VARCHAR(255)	Cuerpo del mensaje
data	JSON	Payload custom (deep link, etc.)
channel	ENUM	general|jobs|orders|alerts|marketing
priority	ENUM	high|normal|low
sent_at	TIMESTAMP	Fecha de envio
delivered_at	TIMESTAMP	Confirmacion de entrega
opened_at	TIMESTAMP	Fecha de apertura
deep_link	VARCHAR(500)	URL de deep link
tenant_id	UUID FK	Tenant emisor

4.2 Canales de Notificacion
Canal	Eventos	Frecuencia Max
Jobs/Ofertas	Nuevo match, estado candidatura, entrevista	3/dia
Pedidos	Nuevo pedido, envio, entrega, review	Sin limite
Alertas	SLA breach, seguridad, pagos fallidos	Sin limite
Marketing	Promociones, nuevos cursos, eventos	1/dia
General	Actualizaciones sistema, mantenimiento	1/semana
 
5. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
Capacitor setup + base app	15-20h	675-900	CRITICA
Push Notification System	12-15h	540-675	CRITICA
QR Scanner offline	10-12h	450-540	ALTA
Biometric Auth	6-8h	270-360	ALTA
Deep Linking	6-8h	270-360	ALTA
Geolocation + Geofencing	8-10h	360-450	MEDIA
Offline Mode + Sync	12-15h	540-675	MEDIA
App Store submission	5-6h	225-270	CRITICA
Testing dispositivos	8-10h	360-450	CRITICA
TOTAL	82-104h	3,690-4,680	N2

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
