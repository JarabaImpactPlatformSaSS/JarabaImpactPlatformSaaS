
HOMOLOGACIÓN PLATAFORMA DE TELEFORMACIÓN
Sistema de Formación Profesional para el Empleo
SEPE / FUNDAE / SAE Andalucía

JARABA IMPACT PLATFORM
Proyecto EDI Google Antigravity
Documento Técnico de Diseño e Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	105_Homologacion_Teleformacion_SEPE
Dependencias:	08_LMS_Core, 46_Training_Certification
Infraestructura:	IONOS Servidor Dedicado L-16 NVMe
 
1. Resumen Ejecutivo
Este documento especifica los requisitos técnicos, funcionales y administrativos necesarios para homologar la Jaraba Impact Platform como centro de teleformación oficial ante el Servicio Público de Empleo Estatal (SEPE), FUNDAE, y el Servicio Andaluz de Empleo (SAE). La homologación permitirá impartir formación bonificable y certificados de profesionalidad con validez estatal.
1.1 Objetivos Estratégicos
•	Inscripción en modalidad teleformación: Habilitar la impartición de especialidades formativas no vinculadas a certificados de profesionalidad (formación bonificable FUNDAE)
•	Acreditación para Certificados de Profesionalidad: Obtener autorización para impartir CPs oficiales con validez estatal
•	Acceso a convocatorias públicas: Participar como entidad solicitante en programas de formación del SEPE y SAE
•	Credibilidad institucional: Posicionar el Ecosistema Jaraba como referente en formación oficial para empleabilidad y emprendimiento
1.2 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con módulo jaraba_sepe_teleformacion custom
LMS Base	jaraba_lms + H5P para contenido SCORM/xAPI
Web Service SEPE	SOAP/WSDL conforme Orden TMS/369/2019
Learning Record Store	Drupal entities + xAPI endpoint interno
Infraestructura	IONOS Servidor Dedicado L-16 NVMe managed
Ancho de Banda	1 Gbit/s (requisito SEPE: 100 Mbps)
Disponibilidad	24x7 con SLA 99.9%
Automatización	ECA Module para flujos de seguimiento y reporting
 
2. Marco Regulatorio
La formación profesional para el empleo en modalidad de teleformación está regulada por un marco normativo multinivel que establece requisitos técnicos, pedagógicos y administrativos específicos.
2.1 Normativa Estatal Aplicable
Norma	Contenido
Ley 30/2015	Sistema de Formación Profesional para el Empleo en el ámbito laboral
RD 694/2017	Desarrollo de la Ley 30/2015: iniciativas, requisitos, destinatarios
Orden TMS/368/2019	Oferta formativa y bases reguladoras de subvenciones
Orden TMS/369/2019	Registro Estatal de Entidades de Formación y procesos de acreditación
RD 34/2008	Certificados de profesionalidad
RD 659/2023	Ordenación del Sistema de Formación Profesional (nueva FP)
2.2 Requisitos Específicos de Teleformación (Art. 4.2 RD 694/2017)
La teleformación debe realizarse a través de una plataforma virtual de aprendizaje que:
1.	Posibilite la interactividad de alumnos, tutores y recursos situados en distinto lugar
2.	Asegure la gestión de los contenidos formativos
3.	Garantice un proceso de aprendizaje sistematizado para los participantes
4.	Permita el seguimiento continuo y en tiempo real del progreso
5.	Facilite la evaluación de todo el proceso formativo
6.	Cumpla requisitos de accesibilidad y diseño universal
2.3 Tipos de Homologación Disponibles
Tipo	Alcance	Trámite
Inscripción	Especialidades NO vinculadas a certificados de profesionalidad	Declaración responsable (efecto inmediato)
Acreditación	Certificados de Profesionalidad oficiales	Solicitud con resolución (máx. 6 meses)
 
3. Requisitos Técnicos de la Plataforma
3.1 Infraestructura Base (IONOS L-16 NVMe)
Requisito SEPE/FUNDAE	Especificación	Estado Jaraba
Ancho de banda mínimo	100 Mbps	1 Gbit/s ✓
Disponibilidad	24x7	24x7 con SLA 99.9% ✓
Usuarios concurrentes	40% de matriculados	Soportado ✓
Ratio tutor/alumno	Máx. 80 alumnos/tutor	Configurable ✓
Backup	Periodicidad suficiente	Diario automatizado ✓
Protección DDoS	Recomendado	IONOS Firewall IP ✓
Tráfico	Sin límite	Ilimitado ✓
3.2 Requisitos Funcionales de la Plataforma
Funcionalidad	Componente Jaraba	Estado
Compatibilidad SCORM 1.2+ / IMS	H5P Module	✓ Implementado
Tracking xAPI/LRS	jaraba_lms + progress_record	✓ Implementado
Perfiles diferenciados (admin/tutor/alumno)	Drupal Roles + RBAC	✓ Implementado
Herramientas síncronas (videoconferencia)	Jitsi Meet Integration	✓ Implementado
Herramientas asíncronas (foros, chat, mensajería)	Drupal Forum + Private Msg	⚠ Parcial
Gestión de contenidos multimedia	Media Library + Bunny.net	✓ Implementado
Sistema de evaluación	H5P + Quiz entities	✓ Implementado
Emisión de certificados	Open Badges 3.0	✓ Implementado
Trazabilidad completa	xAPI + ECA logging	✓ Implementado
Servicio Web SOAP/WSDL SEPE	jaraba_sepe_soap (NUEVO)	🔴 Pendiente
3.3 Requisitos de Accesibilidad
La plataforma debe cumplir con los criterios de accesibilidad establecidos en la normativa:
•	WCAG 2.1 Nivel AA (Web Content Accessibility Guidelines)
•	UNE 139803:2012 (Norma española de accesibilidad web, prioridades 1 y 2)
•	Real Decreto 1112/2018 (Accesibilidad de sitios web del sector público)
Acción requerida: Auditoría de accesibilidad con herramientas WAVE, axe DevTools y validación manual. Estimación: 20 horas.
 
4. Arquitectura del Módulo jaraba_sepe_soap
El componente crítico para la homologación es la implementación del servicio web SOAP que permite al SEPE conectarse automáticamente a la plataforma para obtener informes de seguimiento de las acciones formativas.
4.1 Especificación del Web Service (Orden TMS/369/2019, Anexo V)
El servicio web debe implementar las siguientes operaciones SOAP:
Operación SOAP	Descripción
ObtenerDatosCentro()	Devuelve datos identificativos del centro de formación
CrearAccion(idAccion)	Crea una acción formativa con el identificador indicado
ObtenerListaAcciones()	Lista todos los identificadores de acciones del centro
ObtenerDatosAccion(idAccion)	Devuelve datos completos de una acción formativa específica
ObtenerParticipantes(idAccion)	Lista participantes de una acción con datos de seguimiento
ObtenerSeguimiento(idAccion, dni)	Devuelve seguimiento detallado de un participante
4.2 Modelo de Datos de Seguimiento SEPE
El modelo de datos definido por el SEPE requiere la siguiente estructura:
4.2.1 Estructura: DatosCentro
Campo	Tipo	Descripción
CIF	String(9)	CIF/NIF de la entidad de formación
RazonSocial	String(100)	Nombre o razón social
CodigoCentro	String(20)	Código asignado por SEPE
Direccion	String(200)	Dirección del centro
CodigoPostal	String(5)	Código postal
Municipio	String(100)	Municipio
Provincia	String(50)	Provincia
Telefono	String(15)	Teléfono de contacto
Email	String(100)	Email de contacto
URLPlataforma	String(255)	URL de la plataforma de teleformación
4.2.2 Estructura: DatosAccion
Campo	Tipo	Descripción
IdAccion	String(20)	Identificador único de la acción formativa
CodigoEspecialidad	String(15)	Código del Catálogo de Especialidades
Denominacion	String(200)	Nombre de la acción formativa
Modalidad	String(1)	T=Teleformación, M=Mixta
NumeroHoras	Integer	Duración total en horas
FechaInicio	Date	Fecha de inicio (YYYY-MM-DD)
FechaFin	Date	Fecha de finalización
NumParticipantes	Integer	Número de participantes matriculados
Estado	String(1)	P=Pendiente, E=En curso, F=Finalizada
4.2.3 Estructura: DatosSeguimiento
Campo	Tipo	Descripción
DNI	String(9)	DNI/NIE del participante
Nombre	String(50)	Nombre del participante
Apellidos	String(100)	Apellidos del participante
FechaAlta	Date	Fecha de matriculación
FechaBaja	Date	Fecha de baja (si aplica)
HorasConectado	Decimal	Total horas de conexión a plataforma
PorcentajeProgreso	Integer	% de contenido completado (0-100)
NumActividadesRealizadas	Integer	Actividades/evaluaciones completadas
NotaMedia	Decimal	Nota media de evaluaciones
Estado	String(1)	A=Activo, B=Baja, F=Finalizado, C=Certificado
UltimaConexion	DateTime	Fecha/hora última conexión
 
5. Entidades del Módulo jaraba_sepe_teleformacion
El módulo introduce nuevas entidades que extienden el LMS core para cumplir con los requisitos específicos de seguimiento y reporting del SEPE.
5.1 Entidad: sepe_centro
Almacena los datos del centro de formación acreditado/inscrito ante el SEPE.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
cif	VARCHAR(9)	CIF/NIF de la entidad	UNIQUE, NOT NULL
razon_social	VARCHAR(100)	Nombre o razón social	NOT NULL
codigo_sepe	VARCHAR(20)	Código asignado por SEPE	UNIQUE, NULLABLE
tipo_registro	VARCHAR(16)	Tipo de homologación	ENUM: inscripcion|acreditacion
fecha_registro	DATE	Fecha de inscripción/acreditación	NULLABLE
direccion	VARCHAR(200)	Dirección completa	NOT NULL
codigo_postal	VARCHAR(5)	Código postal	NOT NULL
municipio	VARCHAR(100)	Municipio	NOT NULL
provincia	VARCHAR(50)	Provincia	NOT NULL
telefono	VARCHAR(15)	Teléfono	NOT NULL
email	VARCHAR(100)	Email de contacto	NOT NULL
url_plataforma	VARCHAR(255)	URL de la plataforma	NOT NULL
url_seguimiento	VARCHAR(255)	URL del servicio SOAP	NOT NULL
certificado_ssl	TEXT	Certificado para WS-Security	NULLABLE
tenant_id	INT	Tenant propietario	FK tenant.id
is_active	BOOLEAN	Centro activo	DEFAULT TRUE
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
5.2 Entidad: sepe_accion_formativa
Representa una acción formativa comunicada al SEPE. Vincula un course del LMS con su registro oficial.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
id_accion_sepe	VARCHAR(20)	ID asignado por SEPE	UNIQUE, NOT NULL
centro_id	INT	Centro de formación	FK sepe_centro.id, NOT NULL
course_id	INT	Curso del LMS vinculado	FK course.id, NOT NULL
codigo_especialidad	VARCHAR(15)	Código del Catálogo	NOT NULL
denominacion	VARCHAR(200)	Nombre oficial	NOT NULL
modalidad	VARCHAR(1)	Modalidad	ENUM: T|M
numero_horas	INT	Duración en horas	NOT NULL, > 0
fecha_inicio	DATE	Fecha de inicio	NOT NULL
fecha_fin	DATE	Fecha de finalización	NOT NULL
num_participantes_max	INT	Plazas máximas	NOT NULL
estado	VARCHAR(16)	Estado de la acción	ENUM: pendiente|autorizada|en_curso|finalizada|cancelada
fecha_comunicacion	DATETIME	Fecha comunicación inicio	NULLABLE
numero_expediente	VARCHAR(30)	Expediente administrativo	NULLABLE
es_certificado	BOOLEAN	Es Certificado Profesionalidad	DEFAULT FALSE
nivel_cp	INT	Nivel del CP (1, 2 o 3)	NULLABLE
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
5.3 Entidad: sepe_participante
Registro de participante en una acción formativa SEPE con sus datos de seguimiento agregados.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
accion_id	INT	Acción formativa	FK sepe_accion_formativa.id
enrollment_id	INT	Matrícula LMS	FK enrollment.id, NOT NULL
dni	VARCHAR(9)	DNI/NIE del participante	NOT NULL, INDEX
nombre	VARCHAR(50)	Nombre	NOT NULL
apellidos	VARCHAR(100)	Apellidos	NOT NULL
fecha_alta	DATE	Fecha de matriculación	NOT NULL
fecha_baja	DATE	Fecha de baja	NULLABLE
motivo_baja	VARCHAR(100)	Motivo de la baja	NULLABLE
horas_conectado	DECIMAL(8,2)	Total horas conexión	DEFAULT 0
porcentaje_progreso	INT	% completado	DEFAULT 0, RANGE 0-100
num_actividades	INT	Actividades realizadas	DEFAULT 0
nota_media	DECIMAL(5,2)	Nota media	NULLABLE
estado	VARCHAR(16)	Estado del participante	ENUM: activo|baja|finalizado|certificado
ultima_conexion	DATETIME	Última conexión	NULLABLE
fecha_finalizacion	DATETIME	Fecha de finalización	NULLABLE
apto	BOOLEAN	Resultado final APTO	NULLABLE
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
6. Implementación del Web Service SOAP
6.1 Arquitectura del Servicio
El web service se implementa como un controlador Drupal custom que expone un endpoint SOAP conforme al fichero WSDL proporcionado por el SEPE.
Endpoint de producción: https://plataforma.jarabaimpact.es/sepe/ws/seguimiento
WSDL: https://plataforma.jarabaimpact.es/sepe/ws/seguimiento?wsdl
Protocolo: SOAP 1.1 con codificación RPC/literal
6.2 Autenticación WS-Security
El servicio implementa autenticación mediante WS-Security con certificado digital:
•	Validación de certificado X.509 del SEPE en cada petición
•	Firma digital de respuestas con certificado del centro
•	Timestamp validation para prevenir replay attacks
•	Logging completo de todas las peticiones para auditoría
6.3 Mapeo Entidades Jaraba → Modelo SEPE
Entidad Jaraba	Dato SEPE	Transformación
sepe_centro	DatosCentro	Mapeo directo 1:1
sepe_accion_formativa	DatosAccion	Incluye datos de course vinculado
sepe_participante	DatosSeguimiento	Agregación desde enrollment + progress_record
progress_record	HorasConectado	SUM(duration_seconds) / 3600
progress_record	PorcentajeProgreso	AVG de completion por módulo
enrollment	Estado	Mapeo: in_progress→A, completed→F, dropped→B
 
7. Flujos de Automatización (ECA)
7.1 ECA-SEPE-001: Alta de Participante en Acción SEPE
Trigger: Creación de enrollment en curso vinculado a sepe_accion_formativa
7.	Verificar que el curso está vinculado a una acción SEPE activa
8.	Obtener DNI del usuario desde campo profile (requerido para SEPE)
9.	Crear registro sepe_participante con datos del usuario
10.	Actualizar num_participantes en sepe_accion_formativa
11.	Log de auditoría: Participante {dni} dado de alta en acción {id_accion_sepe}
7.2 ECA-SEPE-002: Actualización de Seguimiento
Trigger: Inserción/actualización en progress_record
12.	Verificar que enrollment está vinculado a sepe_participante
13.	Recalcular horas_conectado: SUM(progress_record.duration_seconds) / 3600
14.	Recalcular porcentaje_progreso desde enrollment.progress_percent
15.	Recalcular num_actividades completadas
16.	Recalcular nota_media de evaluaciones
17.	Actualizar ultima_conexion
18.	Si porcentaje_progreso = 100 y nota_media >= umbral: marcar estado = 'finalizado'
7.3 ECA-SEPE-003: Finalización de Acción Formativa
Trigger: Fecha actual >= fecha_fin de sepe_accion_formativa
19.	Actualizar estado de acción a 'finalizada'
20.	Para cada participante activo sin finalizar: marcar como 'baja' con motivo 'no_completado'
21.	Generar informe de cierre (PDF) con estadísticas
22.	Enviar notificación al administrador del centro
23.	Log de auditoría: Acción {id_accion_sepe} finalizada. {n} aptos, {m} bajas
 
8. APIs REST del Módulo
APIs internas para gestión de la homologación. Requieren autenticación OAuth2 y rol sepe_admin.
Método	Endpoint	Descripción
GET	/api/v1/sepe/centros	Listar centros de formación registrados
GET	/api/v1/sepe/centros/{id}	Detalle de centro con acreditaciones
POST	/api/v1/sepe/centros	Registrar nuevo centro
GET	/api/v1/sepe/acciones	Listar acciones formativas (filtros: estado, centro)
POST	/api/v1/sepe/acciones	Crear nueva acción formativa
GET	/api/v1/sepe/acciones/{id}	Detalle de acción con participantes
POST	/api/v1/sepe/acciones/{id}/comunicar-inicio	Comunicar inicio al SEPE
GET	/api/v1/sepe/acciones/{id}/participantes	Listar participantes con seguimiento
GET	/api/v1/sepe/acciones/{id}/informe	Generar informe de seguimiento (PDF)
POST	/api/v1/sepe/validar-wsdl	Test de validación del servicio SOAP
9. Documentación Pedagógica Requerida
Además de los requisitos técnicos, el SEPE exige documentación pedagógica para cada especialidad:
Documento	Contenido
Proyecto Formativo General	Objetivos, metodología, recursos, sistema de evaluación
Planificación Didáctica	Secuenciación de contenidos, temporalización, actividades
Guía del Alumno	Instrucciones de acceso, navegación, funcionamiento del curso
Guía del Tutor-Formador	Funciones, herramientas, procedimientos de seguimiento
Plan de Evaluación	Criterios, instrumentos, ponderaciones, rúbricas
Plan de Tutorías Presenciales	Calendario, contenido, localización (si CP)
Manual de la Plataforma	Documentación técnica de la plataforma de teleformación
Sistema de Calidad	Certificación ISO 9001 o equivalente (recomendado)
 
10. Proceso de Acreditación/Inscripción
10.1 Fase 1: Inscripción (Especialidades No-CP)
Objetivo: Habilitar formación bonificable FUNDAE
Trámite: Declaración Responsable ante SEPE (efecto inmediato)
Requisitos: 
•	Certificado digital de la entidad
•	URL de la plataforma de teleformación operativa
•	URL del servicio web SOAP funcional (validado con kit de autoevaluación)
•	Documentación de las especialidades a inscribir
10.2 Fase 2: Acreditación (Certificados de Profesionalidad)
Objetivo: Impartir CPs oficiales con validez estatal
Trámite: Solicitud de acreditación (resolución máx. 6 meses)
Requisitos adicionales: 
•	Centro presencial autorizado en Andalucía para sesiones de tutoría y evaluación final
•	Material Virtual de Aprendizaje (MVA) específico para cada CP
•	Equipo docente acreditado (tutores-formadores con requisitos del RD 34/2008)
•	Acuerdos de colaboración para prácticas en empresa (módulo FCT)
10.3 Certificados de Profesionalidad Prioritarios
Código	Denominación	Nivel	Vertical
SSCE0110	Docencia de la formación profesional para el empleo	3	Empleabilidad
ADGD0208	Gestión integrada de recursos humanos	3	Empleabilidad
IFCT0310	Administración de bases de datos	3	Empleabilidad
COMM0112	Gestión de marketing y comunicación	3	Emprendimiento
ADGD0110	Asistencia en la gestión administrativa	2	Emprendimiento
AGAU0208	Gestión de la producción agrícola	3	AgroConecta
 
11. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas Est.
Sprint 1	Sem 1-2	Entidades sepe_centro, sepe_accion_formativa, sepe_participante. Migrations. Admin UI.	30-40h
Sprint 2	Sem 3-4	Web Service SOAP: estructura WSDL, operaciones básicas, tests unitarios.	40-50h
Sprint 3	Sem 5-6	Autenticación WS-Security. Mapeo de datos LMS→SEPE. Validación con kit SEPE.	30-40h
Sprint 4	Sem 7-8	Flujos ECA completos. APIs REST de gestión. Dashboard de seguimiento.	25-35h
Sprint 5	Sem 9-10	Auditoría accesibilidad WCAG. Documentación pedagógica. Preparación expediente.	20-30h
Sprint 6	Sem 11-12	Presentación Declaración Responsable. Validación con SAE. Go-live inscripción.	15-20h
Inversión Total Estimada: 160-215 horas de desarrollo
12. Modelo de Negocio Post-Homologación
12.1 Nuevas Vías de Ingresos
Canal	Descripción	Ticket Medio	Margen
Formación Bonificada B2B	Empresas bonifican vía FUNDAE	€50-100/alumno	40-60%
Subvenciones Públicas	Programas SAE/SEPE adjudicados	€500-800/alumno	15-25%
CPs Privados	Alumnos pagan certificado oficial	€997-2.497/CP	50-70%
Licencias SaaS B2B	Centros de formación licencian plataforma	€297-997/mes	80%
12.2 Proyección de Impacto (Año 1)
Métrica	Escenario Base	Escenario Optimista
Alumnos formación bonificada	200	500
Alumnos subvenciones públicas	100	300
Alumnos CPs privados	50	150
Ingresos adicionales anuales	€45.000-80.000	€120.000-200.000

--- Fin del Documento ---

105_Homologacion_Teleformacion_SEPE_v1.docx | Jaraba Impact Platform | Enero 2026
