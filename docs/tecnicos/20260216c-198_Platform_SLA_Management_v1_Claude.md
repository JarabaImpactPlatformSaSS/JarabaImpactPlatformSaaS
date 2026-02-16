
SLA MANAGEMENT
Gestion Formal de SLAs, Status Page y Postmortems
Nivel de Madurez: N3
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	198_Platform_SLA_Management_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N3
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Gestion formal de SLAs: tiers de disponibilidad (99.9%, 99.95%, 99.99%), calculo automatico de uptime por tenant, creditos por incumplimiento, status page publica, postmortems automatizados y reporting mensual a clientes enterprise.

1.1 SLA Tiers
Tier	Uptime	Downtime/mes	Downtime/ano	Plan
Standard	99.9%	43.8 min	8.76h	Professional
Premium	99.95%	21.9 min	4.38h	Enterprise
Critical	99.99%	4.38 min	52.6 min	Enterprise+
 
2. Modelo de Datos
2.1 sla_agreement
Campo	Tipo	Descripcion
id	UUID	Identificador
tenant_id	UUID FK	Tenant
sla_tier	ENUM	standard|premium|critical
uptime_target	DECIMAL(5,3)	Objetivo de uptime (99.900)
credit_policy	JSON	Politica de creditos
start_date	DATE	Inicio del SLA
end_date	DATE	Fin del SLA
custom_terms	JSON	Terminos custom
status	ENUM	active|expired|suspended

2.2 sla_measurement
Campo	Tipo	Descripcion
id	UUID	Identificador
sla_id	UUID FK	SLA agreement
period_start	DATE	Inicio del periodo
period_end	DATE	Fin del periodo
total_minutes	INT	Minutos totales del periodo
downtime_minutes	DECIMAL(8,2)	Minutos de downtime
uptime_pct	DECIMAL(6,3)	Porcentaje de uptime
sla_met	BOOLEAN	SLA cumplido
credit_amount	DECIMAL(8,2)	Credito si aplica
incidents	JSON	Lista de incidentes del periodo
excluded_maintenance	DECIMAL(8,2)	Minutos excluidos (mantenimiento)
 
3. Status Page Publica
3.1 Componentes Monitorizados
Componente	Health Check	Intervalo	Umbral Alerta
Web Application	HTTP 200 + response < 2s	30s	2 fallos consecutivos
API REST	Health endpoint + latency	30s	3 fallos consecutivos
Database	Query test + connection pool	15s	1 fallo
Redis Cache	PING + memory check	15s	2 fallos consecutivos
Email Service	SendGrid API status	60s	3 fallos
AI/Copilots	Claude API health	60s	3 fallos
Payment System	Stripe API status	60s	1 fallo
 
4. Postmortem Automatizado
4.1 Template de Postmortem
•	Titulo: Descripcion breve del incidente
•	Severidad: SEV-1 a SEV-4
•	Duracion: Inicio a resolucion
•	Impacto: Tenants y usuarios afectados
•	Timeline: Cronologia de eventos con timestamps
•	Causa raiz: Analisis tecnico detallado
•	Resolucion: Acciones tomadas para resolver
•	Acciones preventivas: Mejoras para evitar recurrencia
•	Lecciones aprendidas: Que funciono bien, que mejorar
 
5. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
SLA agreement model	5-6h	225-270	CRITICA
Uptime calculator	8-10h	360-450	CRITICA
Credit automation	5-6h	225-270	ALTA
Status page publica	8-10h	360-450	ALTA
Postmortem system	5-6h	225-270	MEDIA
Monthly reporting	5-6h	225-270	MEDIA
TOTAL	36-44h	1,620-1,980	N3

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
