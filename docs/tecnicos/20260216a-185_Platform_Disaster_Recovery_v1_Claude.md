
DISASTER RECOVERY PLAN
Plan de Continuidad de Negocio y Recuperacion ante Desastres
Nivel de Madurez: N1
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	185_Platform_Disaster_Recovery_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N1
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Plan formal de Continuidad de Negocio y Recuperacion ante Desastres (BC/DR) para la Jaraba Impact Platform. Complementa el doc 131 (infraestructura) y el doc 139 (go-live runbook) con procedimientos formales de failover, restauracion, testing periodico y comunicacion a tenants durante incidentes.

1.1 Objetivos de Recuperacion
Metrica	Tier 1 (Enterprise)	Tier 2 (Professional)	Tier 3 (Starter/Free)
RTO (Recovery Time)	< 1 hora	< 4 horas	< 8 horas
RPO (Recovery Point)	< 1 hora	< 6 horas	< 24 horas
SLA Uptime	99.9%	99.5%	99.0%
Failover Time	< 5 min (auto)	< 15 min (manual)	< 30 min (manual)
Backup Frequency	Cada hora	Cada 6 horas	Diario
Data Retention	90 dias	30 dias	14 dias
 
2. Analisis de Riesgos y Escenarios
2.1 Escenarios de Desastre
Escenario	Probabilidad	Impacto	RTO Requerido	Estrategia
Fallo hardware servidor	Media	Alto	< 1h	Snapshot + nuevo servidor IONOS
Corrupcion base de datos	Baja	Critico	< 30min	Restore desde backup + WAL replay
Ataque ransomware	Baja	Critico	< 2h	Restore desde backup offline
Fallo datacenter IONOS	Muy baja	Critico	< 4h	Migrar a DC secundario
Error humano (delete masivo)	Media	Alto	< 1h	Restore selectivo de tablas
Fallo servicio tercero (Stripe)	Media	Medio	< 15min	Modo degradado + cola
DDoS sostenido	Media	Medio	< 30min	Cloudflare + rate limiting
Fallo DNS	Baja	Alto	< 15min	DNS secundario pre-configurado

2.2 Componentes Criticos
Componente	Tipo	Backup	Failover	Dependencias
MariaDB	Base de datos principal	Cada 6h + WAL	Replica standby	Almacenamiento, red
Redis	Cache + sesiones	RDB cada 1h	Reinicio + warmup	MariaDB (source of truth)
Qdrant	Vector DB (IA)	Snapshot diario	Rebuild desde source	Embeddings API
Drupal Application	Aplicacion web	Git + config export	Docker rebuild < 5min	MariaDB, Redis
Files/Media	Archivos subidos	Sync a S3 diario	Restore desde S3	Almacenamiento
Certificates/Secrets	TLS, API keys	Vault + Git cifrado	Rotate + redeploy	DNS, Vault
 
3. Procedimientos de Backup
3.1 Estrategia 3-2-1
•	3 copias de cada dato: produccion + backup local + backup remoto (S3)
•	2 tipos de almacenamiento: disco local + almacenamiento objeto (S3/Minio)
•	1 copia offsite: en region geografica diferente al servidor principal

3.2 Schedule de Backups
Componente	Frecuencia	Retencion	Ubicacion	Cifrado	Verificacion
MariaDB full dump	Cada 6h	30 dias	Local + S3	AES-256	Restore test semanal
MariaDB WAL/binlog	Continuo	7 dias	Local	AES-256	Replay test mensual
Redis RDB snapshot	Cada hora	48h	Local	No (cache)	Warm test semanal
Qdrant snapshots	Diario	7 dias	Local + S3	AES-256	Query test semanal
Files/media	Diario incremental	14 dias	S3	AES-256	Checksum mensual
Config/secrets	En cada cambio	Versionado	Git + Vault	GPG	Audit trimestral
Full server image	Semanal	4 semanas	IONOS Backup	AES-256	Restore test mensual

3.3 Script de Backup Automatizado
Referencia al script existente en doc 131 (/opt/jaraba/scripts/backup.sh) con las siguientes mejoras:
•	Verificacion de integridad post-backup (checksum SHA-256)
•	Notificacion Slack en caso de fallo de backup
•	Rotacion automatica de backups antiguos
•	Cifrado AES-256 antes de upload a S3
•	Logging detallado en /var/log/jaraba/backup.log
 
4. Procedimientos de Restauracion
4.1 Restauracion de Base de Datos
4.1.1 Restore Completo
1.	Detener la aplicacion Drupal (docker-compose stop drupal)
2.	Identificar backup mas reciente en S3 o local
3.	Descargar y descifrar el backup: gpg -d backup.sql.gz.gpg | gunzip > restore.sql
4.	Restaurar: mysql -u root -p jaraba < restore.sql
5.	Aplicar WAL/binlog desde el backup hasta el punto de fallo
6.	Verificar integridad: drush sqlq 'SELECT COUNT(*) FROM node'
7.	Reiniciar aplicacion: docker-compose up -d
8.	Verificar funcionalidad: ejecutar health checks
9.	Invalidar cache Redis: redis-cli FLUSHALL
10.	Notificar a equipo y tenants afectados

4.1.2 Restore Selectivo (tabla especifica)
11.	Extraer tabla del dump: sed -n '/CREATE TABLE.*tabla/,/UNLOCK TABLES/p' backup.sql
12.	Restaurar tabla en base temporal: mysql -u root -p temp_db < tabla.sql
13.	Verificar datos en temporal
14.	Copiar a produccion: INSERT INTO prod.tabla SELECT * FROM temp.tabla
15.	Verificar integridad referencial

4.2 Restauracion de Aplicacion Completa
16.	Provisionar nuevo servidor IONOS (si fallo hardware)
17.	Restaurar imagen de servidor desde IONOS Backup
18.	Verificar Docker Compose y volumenes
19.	Restaurar base de datos desde backup
20.	Restaurar archivos media desde S3
21.	Restaurar configuracion desde Git
22.	Actualizar DNS si cambio de IP
23.	Ejecutar drush cr (clear cache) y drush updb
24.	Verificar todos los servicios: MariaDB, Redis, Qdrant, Drupal
25.	Ejecutar suite de health checks automatizados
 
5. Comunicacion en Incidentes
5.1 Status Page
•	URL: status.jarabaimpact.com
•	Componentes monitorizados: Aplicacion web, API, Base de datos, Email, IA/Copilots, Pagos
•	Estados: Operational, Degraded Performance, Partial Outage, Major Outage, Maintenance
•	Actualizaciones automaticas desde Prometheus/Grafana
•	Historial de incidentes publico (ultimos 90 dias)

5.2 Plantillas de Comunicacion
5.2.1 Inicio de Incidente
[Investigating] Estamos investigando un problema que afecta a [componente]. Algunos usuarios pueden experimentar [sintoma]. Estamos trabajando en una solucion y actualizaremos en los proximos 30 minutos.

5.2.2 Actualizacion
[Update] Hemos identificado la causa del problema: [causa]. Estamos implementando una solucion. Tiempo estimado de resolucion: [ETA]. Los datos de los usuarios NO estan comprometidos.

5.2.3 Resolucion
[Resolved] El incidente ha sido resuelto. El servicio esta operando con normalidad. Causa raiz: [causa]. Acciones preventivas: [acciones]. Duracion total: [duracion].

5.3 Escalation Matrix
Severidad	Notificar a	Canal	Tiempo Max
SEV-1 (Outage total)	CTO + CEO + todos los admins tenant	Slack + SMS + Email	< 15 min
SEV-2 (Degradacion mayor)	CTO + DevOps lead	Slack + Email	< 30 min
SEV-3 (Degradacion menor)	DevOps lead	Slack	< 1h
SEV-4 (Incidencia cosmetics)	Dev team	Slack	< 4h
 
6. Testing de DR
6.1 Calendario de Pruebas
Test	Frecuencia	Duracion	Responsable	Criterio Exito
Restore de DB desde backup	Semanal	30 min	DevOps	Restore completo < RTO
Failover de aplicacion	Mensual	1h	DevOps + CTO	Failover < 5 min
Restore completo de server	Trimestral	2h	DevOps + CTO	Full recovery < RTO
DR Drill (simulacro completo)	Semestral	4h	Todo equipo	Comunicacion + recovery
Chaos testing (kill service)	Mensual	1h	DevOps	Auto-recovery funciona

6.2 Modelo de Datos: dr_test_result
Campo	Tipo	Descripcion
id	UUID	Identificador unico
test_type	ENUM	db_restore|app_failover|full_restore|dr_drill|chaos
executed_at	TIMESTAMP	Fecha de ejecucion
executed_by	UUID FK	Responsable
duration_minutes	INT	Duracion del test
rto_achieved	INT	RTO alcanzado en minutos
rpo_achieved	INT	RPO alcanzado en minutos
passed	BOOLEAN	Test superado (SI/NO)
findings	JSON	Hallazgos y observaciones
remediation_actions	JSON	Acciones correctivas
next_test_date	DATE	Proxima ejecucion programada
 
7. Implementacion Tecnica
7.1 Modulo: jaraba_dr
•	src/Service/BackupVerifier.php: Verificacion automatica de integridad de backups
•	src/Service/FailoverOrchestrator.php: Orquestacion de failover manual/automatico
•	src/Service/StatusPageManager.php: Actualizacion automatica de status page
•	src/Service/IncidentCommunicator.php: Envio de notificaciones a tenants
•	src/Service/DrTestRunner.php: Ejecucion y registro de tests DR

7.2 Integracion con Monitoring (Doc 133)
•	Prometheus: Alertas que disparan DR procedures automaticamente
•	Grafana: Dashboard de DR con metricas de backup y recovery
•	Slack: Canal #incident-response para comunicacion en tiempo real
•	PagerDuty/OpsGenie: Escalacion automatica segun severidad
 
8. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
Backup Verification System	6-8h	270-360	CRITICA
Failover Orchestration	8-10h	360-450	CRITICA
Status Page + Communicator	6-8h	270-360	ALTA
DR Test Framework	5-6h	225-270	ALTA
Runbooks documentados	4-5h	180-225	CRITICA
Integracion Monitoring	3-4h	135-180	MEDIA
TOTAL	32-41h	1,440-1,845	N1 BLOQUEANTE

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
