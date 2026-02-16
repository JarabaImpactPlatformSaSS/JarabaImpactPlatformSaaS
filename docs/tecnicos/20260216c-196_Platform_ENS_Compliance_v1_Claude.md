
ENS COMPLIANCE
Esquema Nacional de Seguridad (RD 311/2022) para Contratos AAPP
Nivel de Madurez: N3
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	196_Platform_ENS_Compliance_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N3
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Cumplimiento del Esquema Nacional de Seguridad (RD 311/2022) para acceso a contratos con Administraciones Publicas espanolas. Incluye categorizacion del sistema, medidas de seguridad organizativas y operacionales, marco operacional y procedimiento de auditoria bienal.

1.1 Categorizacion del Sistema
Dimension	Nivel	Justificacion
Confidencialidad	MEDIA	Datos personales de ciudadanos (no datos especialmente protegidos)
Integridad	MEDIA	Datos de empleo y emprendimiento requieren precision
Trazabilidad	MEDIA	Requerido para justificacion de fondos publicos
Autenticidad	MEDIA	Certificaciones y documentos oficiales
Disponibilidad	MEDIA	Servicios de orientacion y formacion
Categoria del sistema: MEDIA - Aplican todas las medidas del marco organizativo, operacional y de proteccion para categoria MEDIA.
 
2. Marco Organizativo
2.1 Medidas Organizativas
Medida	ID ENS	Descripcion	Estado
Politica de seguridad	org.1	Documento aprobado por direccion	Parcial (Doc 115)
Normativa de seguridad	org.2	Normas de uso de sistemas	Pendiente
Procedimientos de seguridad	org.3	Procedimientos operativos	Parcial (Doc 138)
Proceso de autorizacion	org.4	Autorizacion de acceso a sistemas	Implementado (RBAC)
 
3. Marco Operacional
3.1 Medidas Operacionales
Medida	ID ENS	Descripcion	Estado
Planificacion	op.pl	Analisis de riesgos, arquitectura	Parcial
Control de acceso	op.acc	Identificacion, autenticacion, RBAC	Implementado
Explotacion	op.exp	Inventario, configuracion, mantenimiento	Parcial
Servicios externos	op.ext	Gestion de proveedores	Parcial (DPA)
Continuidad del servicio	op.cont	Plan de continuidad, DR testing	Doc 185
Monitorizacion	op.mon	Deteccion de intrusiones, alertas	Implementado
 
4. Modelo de Datos: ens_compliance
Campo	Tipo	Descripcion
id	UUID	Identificador
measure_id	VARCHAR(20)	ID medida ENS (org.1, op.acc, etc.)
measure_name	VARCHAR(255)	Nombre de la medida
category	ENUM	organizational|operational|protection
required_level	ENUM	basic|medium|high
current_status	ENUM	implemented|partial|not_implemented|not_applicable
implementation_details	TEXT	Detalle de implementacion
evidence	JSON	Evidencias de cumplimiento
responsible	VARCHAR(255)	Responsable
last_audit	DATE	Ultima auditoria
next_audit	DATE	Proxima auditoria
findings	JSON	Hallazgos de auditoria
 
5. Auditoria Bienal
5.1 Proceso
1.	Preparacion: recopilar evidencias de todos los controles (3 meses antes)
2.	Auditoria interna: revision previa por equipo propio
3.	Auditoria externa: auditor acreditado por CCN
4.	Informe de auditoria: hallazgos y recomendaciones
5.	Plan de accion correctiva: remediar hallazgos
6.	Declaracion de Conformidad: publicacion en sede electronica
 
6. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
Categorizacion formal	6-8h	270-360	CRITICA
Politica seguridad ENS	8-10h	360-450	CRITICA
Marco operacional	12-15h	540-675	ALTA
Marco de proteccion	10-12h	450-540	ALTA
Evidencia automatizada	10-12h	450-540	ALTA
Preparacion auditoria	10-12h	450-540	CRITICA
Gap remediation	12-15h	540-675	ALTA
TOTAL	68-84h	3,060-3,780	N3

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
