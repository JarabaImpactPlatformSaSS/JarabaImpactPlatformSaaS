
PLAN DE IMPLEMENTACION
SERVIDOR DEDICADO AMD EPYC ZEN 5
IONOS AE12-128 NVMe
Runbook Completo para Claude Code — Version Definitiva
JARABA IMPACT PLATFORM

Campo	Valor
Version:	2.0 (Definitiva)
Fecha:	Marzo 2026
Estado:	Ready for Implementation
Codigo:	131b_Server_Implementation_Runbook_AMD_v2
Prioridad:	CRITICA - Prerequisito de produccion
Destinatario:	EDI Google Antigravity / Claude Code
Sustituye a:	131_Platform_Infrastructure_Deployment_v1 + 131b v1
Servidor:	IONOS AE12-128 NVMe (EPYC 4465P Zen 5, 12c/24t)
Verificado contra:	Estado_Implementacion_Plataforma_SaaS_v1.0.0 (2026-03-03)
 
Tabla de Contenidos
Tabla de Contenidos	1
1. Analisis de Infraestructura y Decisiones Estrategicas	1
1.1 Por que migrar del servidor Managed actual	1
1.2 Seleccion del servidor: AE12-128 NVMe	1
1.3 Servicios que NO corren en el servidor (y por que)	1
1.3.1 Qdrant — Qdrant Cloud (externo)	1
1.3.2 Almacenamiento de objetos — Cloudflare R2 (externo)	1
1.3.3 Entrega de email — SendGrid API (externo)	1
1.4 Estrategia de almacenamiento en 3 capas	1
1.4.1 Palanca 1 — Backups externos (activar dia 1)	1
1.4.2 Palanca 2 — S3 para archivos publicos de Drupal (activar ano 2)	1
1.5 Proyeccion de almacenamiento a 5 anos	1
2. Previsiones de Configuracion y Costes Escalonados	1
2.1 Ano 1 — Lanzamiento (3 verticales, 0-500 usuarios, pre-revenue)	1
2.2 Ano 2 — Traccion (5-7 verticales, 500-2.000 usuarios, primeros ingresos)	1
2.3 Ano 3 — Consolidacion (8-10 verticales, 2.000-5.000 usuarios, revenue recurrente)	1
2.4 Ano 4 — Escalado (5.000-10.000+ usuarios, expansion nacional)	1
2.5 Ano 5 — Madurez (10.000-25.000 usuarios, licencias/franquicias)	1
2.6 Resumen Consolidado 5 Anos	1
3. Especificaciones del Servidor y Distribucion de Memoria	1
3.1 Servidor Contratado	1
3.2 Mapa de Memoria: 128 GB DDR5	1
4. Fase 0: Provision y Acceso Inicial	1
4.1 Validacion Hardware	1
4.2 Verificacion RAID	1
5. Fase 1: Hardening del Sistema Operativo	1
5.1 Actualizacion + paquetes base	1
5.2 Usuario administrativo + SSH hardening	1
5.3 Firewall + Fail2ban + sysctl	1
6. Fase 2: Docker y Estructura de Proyecto	1
6.1 Docker Engine	1
6.2 Estructura de directorios	1
6.3 Variables de entorno (.env)	1
7. Fase 3: Stack de Servicios — Docker Compose	1
7.1 Servicios del docker-compose.prod.yml	1
7.2 Configuracion MariaDB 10.11	1
7.3 Configuracion PHP-FPM 8.4	1
7.4 Drupal settings.php — Qdrant Cloud	1
7.5 Multi-dominio via Traefik	1
8. Fase 4: Backup y Disaster Recovery (3 Capas)	1
8.1 Capa 1 — Backup local + subida a R2	1
8.2 Capa 2 — Cloudflare R2 (retencion 30 dias)	1
8.3 Capa 3 — NAS 16TB via GoodSync	1
8.4 RTO / RPO	1
9. Fase 5: Monitoring	1
10. Fase 6: DNS, Dominios y SSL	1
11. Fase 7: Migracion desde Servidor Managed	1
D-7: Test completo en servidor nuevo	1
D-1: Import de prueba (DB + rsync files)	1
Dia D: Ventana 30-60 minutos	1
12. Validacion Post-Implementacion	1
13. Comandos de Operacion Diaria	1
14. Conclusion	1

 
1. Analisis de Infraestructura y Decisiones Estrategicas
Este apartado documenta el razonamiento detras de cada decision de infraestructura, los calculos que las sustentan, y las previsiones economicas a 5 anos.
1.1 Por que migrar del servidor Managed actual
El servidor Managed IONOS L-16 NVMe actual tiene restricciones incompatibles con un SaaS multi-tenant de 92 modulos custom, 441 entidades y 925 servicios:
Restriccion	Limite Actual	Necesidad Real	Impacto
Base de datos	5 GB hard limit	20-100+ GB (proyeccion 5 anos)	IONOS bloquea writes automaticamente al alcanzar limite
Conexiones DB	40 paralelas	200-500+	Errores 503 bajo carga concurrente
Root access	NO disponible	Obligatorio	Imposible instalar Redis, Docker, Tika
Customizacion DB	NO my.cnf	Obligatorio	Sin optimizacion InnoDB, sin binlog, sin tuning
Politica uso	Prohibe alta carga	SaaS = alta carga por definicion	Riesgo de suspension de cuenta

Con 3 verticales activas (Empleabilidad, Emprendimiento, Andalucia +ei) y una cohorte PIIL de 50 participantes, el limite de 5GB se alcanza entre el mes 2 y el mes 4 de operacion real. La migracion no es una mejora — es un prerequisito.
1.2 Seleccion del servidor: AE12-128 NVMe
Se evaluaron todos los servidores AMD del catalogo IONOS.es (verificado marzo 2026). El AE16-128 NVMe XL (recomendacion inicial) no esta disponible. La decision recae en el AE12-128 NVMe:
Criterio	AE12-128 NVMe (Elegido)	AE16-128 NVMe (Zen 2)	AE32-256 NVMe
CPU	EPYC 4465P Zen 5, 12c/24t	EPYC 7302P Zen 2, 16c/32t	EPYC 7543P Zen 3, 32c/64t
Frecuencia max	5.4 GHz	3.3 GHz	3.7 GHz
RAM	128 GB DDR5 ECC	128 GB DDR4 ECC	256 GB DDR4 ECC
Almacenamiento	2x1TB NVMe (RAID 1)	2x960GB NVMe (HW RAID 1)	2x1920GB NVMe (HW RAID 1)
PCIe	5.0	3.0	4.0
Arquitectura	2024 (Grado)	2019 (Rome)	2021 (Milan)
Precio/mes	144 EUR	128 EUR	264 EUR
Rendimiento single-thread	EXCELENTE (5.4 GHz Zen 5)	BAJO (3.3 GHz Zen 2)	MEDIO (3.7 GHz Zen 3)

Decision: Zen 5 a 5.4 GHz es un 63% mas rapido por core que Zen 2 a 3.3 GHz. Drupal procesa cada request PHP en un solo thread — la velocidad por core es el factor determinante para la latencia percibida por el usuario. Los 12 cores Zen 5 rinden mas que 16 cores Zen 2 en cualquier escenario web. DDR5 aporta menor latencia por acceso, ideal para los patrones de lectura aleatoria de Drupal. El ahorro de 120 EUR/mes respecto al AE32-256 se invierte mejor en desarrollo.
1.3 Servicios que NO corren en el servidor (y por que)
1.3.1 Qdrant — Qdrant Cloud (externo)
La arquitectura verificada (Anexo A1, doc 20260111b) especifica Qdrant Cloud para produccion con HTTPS obligatorio y API Key. En desarrollo local (Lando) corre como container. Esta decision libera 8 GB de RAM en el servidor y elimina la carga de mantenimiento de un servicio de datos vectoriales.
Coste: Free tier (1GB) en ano 1 → ~25 EUR/mes en ano 2 → ~50-150 EUR/mes en anos 3-5
1.3.2 Almacenamiento de objetos — Cloudflare R2 (externo)
Sustituye a Minio local. R2 sirve como destino de backups (Palanca 1) y como almacenamiento CDN de archivos publicos de Drupal (Palanca 2, activada desde ano 2). Al no tener costes de transferencia de salida, R2 elimina la necesidad de preocuparse por el trafico de imagenes de producto.
Coste: ~1 EUR/mes en ano 1 → ~30-60 EUR/mes en anos 3-5
1.3.3 Entrega de email — SendGrid API (externo)
El modulo jaraba_email (doc 151, 115-155h estimadas, especificado) es el cerebro del email marketing: 50+ secuencias drip, 150+ templates MJML, segmentacion dinamica, A/B testing, personalizacion con IA. Toda la logica vive en Drupal con ECA. SendGrid es exclusivamente la tuberia de entrega — recibe el email ya construido por jaraba_email y lo entrega al buzon del destinatario con reputacion de IP establecida, SPF/DKIM/DMARC, y tracking de webhooks.
Alternativas viables: Amazon SES (0.10$/1000 emails), Resend, Postmark. El cambio solo requiere sustituir el SendGridClient service.
Coste: Free tier (100/dia) en ano 1 → ~15-75 EUR/mes en anos 2-5
1.4 Estrategia de almacenamiento en 3 capas
Con 1TB de disco NVMe usable, la gestion del almacenamiento es critica para alcanzar los 5 anos sin upgrade de servidor:
Capa	Ubicacion	Tecnologia	Proposito	Retencion
1. Disco primario	Servidor IONOS (Logrono)	NVMe ext4 local	DB, Redis, archivos privados PIIL, archivos publicos (ano 1)	Datos activos
2. Cloud caliente	Cloudflare R2 (global)	S3-compatible + CDN	Backups automatizados + CDN imagenes/PDFs publicos	30 dias backups, indefinido files
3. Archivo frio	NAS 16TB oficina (GoodSync)	SFTP pull via GoodSync	Backup de ultimo recurso, retencion historica indefinida	Indefinida (16TB capacidad)

1.4.1 Palanca 1 — Backups externos (activar dia 1)
El script de backup del servidor genera dumps MariaDB comprimidos cada 6 horas, los sube a Cloudflare R2 via rclone, y borra la copia local tras confirmar la subida. En local solo queda el ultimo backup (para restauracion rapida). GoodSync en la oficina conecta por SFTP al servidor y descarga los backups al NAS de 16TB como tercera copia.
Herramientas: rclone (servidor → R2) + GoodSync (servidor → NAS via SFTP puerto 2222)
Ahorro de disco: 30-50 GB en ano 3, 80-100 GB en ano 5
1.4.2 Palanca 2 — S3 para archivos publicos de Drupal (activar ano 2)
Cuando el directorio sites/default/files supere 50 GB (previsto en ano 2 con Commerce activo), se activa el modulo contrib s3fs que redirige el stream wrapper public:// a Cloudflare R2. Las imagenes de producto, fotos de perfil y PDFs descargables se almacenan en R2 y se sirven via CDN (cdn.jarabaimpact.com). Los archivos privados (documentos PIIL firmados, justificaciones FSE+) permanecen en disco local.
Ahorro de disco: 90 GB en ano 3, 185 GB en ano 5
Beneficio adicional: Las imagenes se sirven desde el nodo CDN mas cercano al usuario, no desde Logrono. Paginas mas rapidas.
1.5 Proyeccion de almacenamiento a 5 anos
Componente	Ano 1	Ano 2	Ano 3	Ano 4	Ano 5
SO + Docker + imagenes	35 GB	40 GB	45 GB	48 GB	50 GB
MariaDB (data + indices)	10 GB	35 GB	70 GB	100 GB	150 GB
MariaDB binlog (7 dias)	5 GB	10 GB	15 GB	20 GB	25 GB
Drupal files (publicos)	15 GB	50 GB*	15 GB*	15 GB*	15 GB*
Drupal private files	5 GB	15 GB	35 GB	50 GB	70 GB
Redis persistence	3 GB	4 GB	5 GB	6 GB	7 GB
Backups locales (24h)	5 GB	10 GB	15 GB	20 GB	25 GB
Monitoring (Prometheus+Loki)	15 GB	20 GB	25 GB	28 GB	30 GB
TOTAL	93 GB	184 GB	225 GB	287 GB	372 GB
% del disco (1TB)	9%	18%	23%	29%	37%
* Palanca 2 activa desde ano 2: archivos publicos migrados a R2, solo metadatos y thumbnails quedan en local.
OK: Con ambas palancas activas, el disco de 1TB alcanza comodamente los 5 anos con un 63% libre. Sin palancas, se alcanzaria el 75% en el ano 4.
 
2. Previsiones de Configuracion y Costes Escalonados
2.1 Ano 1 — Lanzamiento (3 verticales, 0-500 usuarios, pre-revenue)
Empleabilidad, Emprendimiento y Andalucia +ei desplegados. Pilotos institucionales PIIL. Sin Commerce activo. IA limitada a diagnosticos y copilots basicos. Email limitado a notificaciones transaccionales.
Servicio	Tier / Configuracion	Coste/mes	Notas
Servidor AE12-128 NVMe	Completo desde dia 1	144 EUR	Coste fijo (compromiso 24 meses)
Qdrant Cloud	Free tier (1GB, 1 coleccion)	0 EUR	Suficiente para RAG inicial
Cloudflare	Free plan + R2 (<10GB backups)	~1 EUR	CDN basico + DNS gratuito. R2 a centimos
Claude API	~20K tokens/dia (Haiku mayoritario)	~30-50 EUR	Diagnosticos, copilot basico
SendGrid	Free tier (100 emails/dia)	0 EUR	Sobra para <500 usuarios transaccionales
Dominios (3)	Renovaciones anuales	~5 EUR	jarabaimpact + pepejaraba + plataformadeecosistemas
NAS 16TB (GoodSync)	Backup diario via SFTP	0 EUR	Ya disponible en oficina
TOTAL ANO 1: ~180-200 EUR/mes (~2.160-2.400 EUR/ano)

Decisiones clave: No se activa Cloudflare Pro. No se paga Qdrant (free tier). SendGrid gratis. Claude API prioriza Haiku (tier fast, mas economico). El grueso del gasto es el servidor.
2.2 Ano 2 — Traccion (5-7 verticales, 500-2.000 usuarios, primeros ingresos)
ComercioConecta y AgroConecta en produccion. Catalogos de producto con imagenes. Mas cohortes Andalucia +ei. jaraba_email activo con secuencias drip. Se activa Palanca 2 (S3 para files publicos).
Servicio	Tier / Configuracion	Coste/mes	Cambio vs Ano 1
Servidor AE12-128 NVMe	Sin cambios	144 EUR	=
Qdrant Cloud	Starter (~4GB, 3-5 colecciones)	~25 EUR	+25 EUR (matching engine)
Cloudflare	Pro + WAF + R2 (~50GB)	~30 EUR	+29 EUR (WAF para Commerce, R2 files)
Claude API	~50K tokens/dia (matching, content)	~80-120 EUR	+60 EUR (mas interacciones IA)
SendGrid	Essentials (40K emails/mes)	~15 EUR	+15 EUR (secuencias drip activas)
Dominios	+ subdominios verticales	~8 EUR	+3 EUR
NAS 16TB	Sin cambios	0 EUR	=
TOTAL ANO 2: ~302-342 EUR/mes (~3.624-4.104 EUR/ano)
2.3 Ano 3 — Consolidacion (8-10 verticales, 2.000-5.000 usuarios, revenue recurrente)
Todas las verticales operativas. ServiciosConecta con reservas. Content Hub generando contenido con IA. CRM B2B activo (jaraba_crm). Multiples tenants pagando. Email marketing a volumen.
Servicio	Tier / Configuracion	Coste/mes	Cambio vs Ano 2
Servidor AE12-128 NVMe	Sin cambios (CPU ~40-50%)	144 EUR	=
Qdrant Cloud	Growth (~10GB, 10+ colecciones)	~50 EUR	+25 EUR
Cloudflare	Pro + WAF + R2 (~200GB)	~35 EUR	+5 EUR
Claude API	~100K tokens/dia (11 agentes)	~150 EUR	+50 EUR
SendGrid	Pro (100K emails/mes, IP dedicada)	~35 EUR	+20 EUR
Make.com	Starter (jaraba_social publicacion)	~10 EUR	+10 EUR (nuevo)
Dominios	Consolidado	~10 EUR	+2 EUR
NAS 16TB	Sin cambios	0 EUR	=
TOTAL ANO 3: ~434 EUR/mes (~5.208 EUR/ano)
2.4 Ano 4 — Escalado (5.000-10.000+ usuarios, expansion nacional)
La plataforma sale de Andalucia. Multiples programas PIIL en paralelo. Commerce con volumen real. DB ~50-70GB. El servidor trabaja mas (CPU ~60-70%) pero aguanta.
Servicio	Tier / Configuracion	Coste/mes	Cambio vs Ano 3
Servidor AE12-128 NVMe	Bajo presion pero suficiente	144 EUR	=
Qdrant Cloud	Business (~25GB)	~100 EUR	+50 EUR
Cloudflare	Pro + WAF + R2 (~500GB)	~40 EUR	+5 EUR
Claude API	~200K tokens/dia	~250 EUR	+100 EUR
SendGrid	Pro (250K emails/mes)	~55 EUR	+20 EUR
Make.com	Pro (25K operaciones/mes)	~25 EUR	+15 EUR
Dominios	+ regionales posibles	~15 EUR	+5 EUR
NAS 16TB	Sin cambios	0 EUR	=
TOTAL ANO 4: ~629 EUR/mes (~7.548 EUR/ano)
Punto de decision del servidor: si CPU >70% sostenido, planificar migracion a AE32-256 (264 EUR/mes). Si <70%, mantener.
2.5 Ano 5 — Madurez (10.000-25.000 usuarios, licencias/franquicias)
Tercer motor economico activo. Posible multi-region. DB ~100-150GB. Servidor original podria necesitar upgrade.
Servicio	Tier / Configuracion	Coste/mes	Cambio vs Ano 4
Servidor	AE12-128 (optimizado) o AE32-256	144-264 EUR	0-120 EUR (decision basada en metricas)
Qdrant Cloud	Business+ (~50GB)	~150 EUR	+50 EUR
Cloudflare	Business + WAF + R2 (~1TB)	~60 EUR	+20 EUR
Claude API	~300K+ tokens/dia	~350 EUR	+100 EUR
SendGrid	Pro (500K emails/mes)	~75 EUR	+20 EUR
Make.com	Pro+	~35 EUR	+10 EUR
Dominios	Consolidado	~15 EUR	=
NAS 16TB	Sin cambios	0 EUR	=
TOTAL ANO 5: ~829-949 EUR/mes (~9.948-11.388 EUR/ano)
2.6 Resumen Consolidado 5 Anos
	Ano 1	Ano 2	Ano 3	Ano 4	Ano 5
Usuarios	0-500	500-2.000	2.000-5.000	5.000-10.000	10.000-25.000
Verticales activas	3	5-7	8-10	10	10+
Coste/mes	~190 EUR	~320 EUR	~434 EUR	~629 EUR	~889 EUR
Coste/ano	~2.280 EUR	~3.840 EUR	~5.208 EUR	~7.548 EUR	~10.668 EUR
Coste acumulado	2.280 EUR	6.120 EUR	11.328 EUR	18.876 EUR	29.544 EUR
Servidor	AE12-128	AE12-128	AE12-128	AE12-128	AE12-128 o AE32-256
DB estimada	~10 GB	~35 GB	~70 GB	~100 GB	~150 GB
Disco usado	9%	18%	23%	29%	37%

Con un ARPU medio de 15-30 EUR/mes a 10.000 usuarios (ano 5), los ingresos mensuales estarian entre 50.000 y 150.000 EUR. La infraestructura representaria menos del 1% de la facturacion.
 
3. Especificaciones del Servidor y Distribucion de Memoria
3.1 Servidor Contratado
Componente	Especificacion	Notas
Modelo IONOS	AE12-128 NVMe	Servidor dedicado unmanaged
CPU	AMD EPYC 4465P (Zen 5 / Grado)	12 cores / 24 threads, 3.4-5.4 GHz
RAM	128 GB DDR5 ECC	Baja latencia, ideal para Drupal random access
Almacenamiento	2x 1TB NVMe SSD	Software RAID 1 = 1TB usable
PCIe	5.0	~14 GB/s bandwidth teorico
Red	1 Gbps garantizado	Trafico ilimitado incluido
Datacenter	Logrono, Espana	Latencia minima para usuarios ES
SO Base	Ubuntu 24.04 LTS	Kernel 6.x
Coste	144 EUR/mes (IVA excl.)	Compromiso 24 meses (PVP sin descuento: 160 EUR)
3.2 Mapa de Memoria: 128 GB DDR5
Sin Qdrant local ni Minio local. Memoria redistribuida para maximizar rendimiento de MariaDB y PHP-FPM:
Servicio	RAM Asignada	% Total	Justificacion
Sistema Operativo + kernel	4 GB	3%	Ubuntu 24.04 base + buffers
MariaDB 10.11 (InnoDB buffer pool)	44 GB	34%	Buffer pool 40GB + overhead 4GB. DB entera en RAM hasta ano 3
PHP-FPM 8.4 (Drupal workers)	20 GB	16%	40 workers x 512MB limit. 12 cores Zen 5 a 5.4 GHz
Redis 7.4 (cache + sessions + queues)	6 GB	5%	Cache Drupal, sessions, queue backend, rate limiting, embeddings cache
Drupal cron + background tasks	4 GB	3%	ECA rules, queue workers, imports STO
Traefik v3 (reverse proxy)	512 MB	0.4%	Routing multi-dominio + TLS auto
Apache Tika 2.9.1	2 GB	1.6%	Procesamiento PDFs/docs para RAG
Monitoring (Prometheus+Grafana+Loki)	6 GB	5%	30 dias metricas, 14 dias logs
RESERVA para picos	41.5 GB	32%	Headroom critico para estabilidad
OK: La reserva del 32% es intencionada y superior a la version anterior (28%). Al no tener Qdrant ni Minio locales, el headroom extra garantiza que el servidor nunca sufra OOM kills bajo carga.
 
4. Fase 0: Provision y Acceso Inicial
Producto: AE12-128 NVMe | Ubicacion: Logrono | SO: Ubuntu 24.04 LTS | Compromiso: 24 meses

4.1 Validacion Hardware
ssh root@<IP_PUBLICA>
lscpu | grep -E 'Model name|CPU\(s\)|Thread|Core'
free -h
lsblk

# Esperado: AMD EPYC 4465P, 24 threads, ~125Gi RAM, 2x NVMe ~1TB
4.2 Verificacion RAID
cat /proc/mdstat
mdadm --detail /dev/md0
AVISO: Verificar que /var tiene >500GB. Si no, redimensionar ANTES de instalar Docker.
5. Fase 1: Hardening del Sistema Operativo
5.1 Actualizacion + paquetes base
apt update && apt upgrade -y
apt install -y curl wget git unzip htop iotop net-tools \
  software-properties-common apt-transport-https ca-certificates \
  gnupg lsb-release fail2ban ufw logrotate jq rclone
timedatectl set-timezone Europe/Madrid
locale-gen es_ES.UTF-8
Nota: rclone se instala desde el inicio para los backups a Cloudflare R2.
5.2 Usuario administrativo + SSH hardening
adduser --gecos '' jaraba
usermod -aG sudo jaraba

# SSH hardening: /etc/ssh/sshd_config.d/jaraba-hardening.conf
Port 2222
PermitRootLogin no
PasswordAuthentication no
AllowUsers jaraba
MaxAuthTries 3
CRITICO: Verificar acceso SSH por puerto 2222 ANTES de cerrar la sesion root.
5.3 Firewall + Fail2ban + sysctl
ufw default deny incoming && ufw default allow outgoing
ufw allow 2222/tcp comment 'SSH'
ufw allow 80/tcp comment 'HTTP'
ufw allow 443/tcp comment 'HTTPS'
ufw enable
Fail2ban, unattended-upgrades y sysctl tuning (vm.swappiness=10, net.core.somaxconn=65535, fs.file-max=2097152) segun seccion detallada en Anexo Tecnico.
6. Fase 2: Docker y Estructura de Proyecto
6.1 Docker Engine
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
usermod -aG docker jaraba
6.2 Estructura de directorios
mkdir -p /opt/jaraba/{
  config/{mariadb,redis,php,nginx,traefik,prometheus,grafana/provisioning,loki,promtail,alertmanager,blackbox},
  scripts, logs/{traefik,drupal,mariadb},
  backups/{database,files},
  secrets, drupal/{web,private,tmp}
}
chown -R jaraba:jaraba /opt/jaraba
chmod 700 /opt/jaraba/secrets
6.3 Variables de entorno (.env)
Archivo: /opt/jaraba/.env — Generar TODOS los passwords con openssl rand -base64 32
# Database
DB_ROOT_PASSWORD=<GENERAR>
DB_PASSWORD=<GENERAR>
DB_NAME=jaraba
DB_USER=drupal

# Drupal
DRUPAL_HASH_SALT=<openssl rand -hex 64>
TRUSTED_HOST_PATTERNS='^.+\.jarabaimpact\.com$|^.+\.pepejaraba\.com$|^.+\.plataformadeecosistemas\.es$'

# Redis
REDIS_PASSWORD=<GENERAR>

# External APIs (via getenv() — SECRET-MGMT-001)
QDRANT_CLUSTER_URL=https://<cluster>.cloud.qdrant.io:6333
QDRANT_API_KEY=<desde Qdrant Cloud dashboard>
STRIPE_SECRET_KEY=sk_live_...
ANTHROPIC_API_KEY=sk-ant-...
SENDGRID_API_KEY=SG...

# Monitoring
GRAFANA_PASSWORD=<GENERAR>
CRITICO: El archivo .env NUNCA se commitea a git. Secrets via getenv() segun directriz SECRET-MGMT-001.
 
7. Fase 3: Stack de Servicios — Docker Compose
Calibrado para 128GB DDR5 / 12c-24t. Sin Qdrant local. Sin Minio local. Qdrant apunta a Qdrant Cloud via variable QDRANT_CLUSTER_URL.
7.1 Servicios del docker-compose.prod.yml
Servicio	Imagen	RAM Limit	Ports	Healthcheck
traefik	traefik:v3.0	512 MB	80, 443 (publico)	-
drupal	jaraba/drupal:11-prod	20 GB (limit), 8 GB (reservation)	80 (interno, via Traefik)	HTTP /health
drupal-cron	jaraba/drupal:11-prod	4 GB	- (solo background)	-
mariadb	mariadb:10.11	44 GB (limit), 36 GB (reservation)	127.0.0.1:3306 (solo localhost)	healthcheck.sh --innodb_initialized
redis	redis:7-alpine	6 GB (limit), 4 GB (reservation)	- (solo red Docker)	redis-cli ping
tika	apache/tika:2.9.1	2 GB	- (solo red Docker)	-

Diferencias clave con doc 131 original: No hay container Qdrant (externo en Qdrant Cloud). No hay container Minio (sustituido por Cloudflare R2 externo). MariaDB usa version 10.11 (NO 11.2 — segun stack verificado). PHP 8.4 (NO 8.3 — segun Estado SaaS v1.0.0). Buffer pool de MariaDB a 40GB (vs 24GB del 131, optimizado para 128GB DDR5).
7.2 Configuracion MariaDB 10.11
Archivo: /opt/jaraba/config/mariadb/my.cnf
[mysqld]
innodb_buffer_pool_size = 40G
innodb_buffer_pool_instances = 16
innodb_log_file_size = 1G
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
innodb_io_capacity = 4000
innodb_io_capacity_max = 8000
max_connections = 300
max_allowed_packet = 256M
character_set_server = utf8mb4
collation_server = utf8mb4_unicode_ci
slow_query_log = 1
long_query_time = 2
log_bin = /var/lib/mysql/mysql-bin
binlog_format = ROW
expire_logs_days = 7
7.3 Configuracion PHP-FPM 8.4
40 workers (20GB / 512MB = 40), OPcache con JIT, sessions en Redis:
pm = dynamic
pm.max_children = 40
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20

opcache.enable = 1
opcache.memory_consumption = 512
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.jit = 1255
opcache.jit_buffer_size = 256M

session.save_handler = redis
session.save_path = "tcp://redis:6379?auth=${REDIS_PASSWORD}&database=1"
7.4 Drupal settings.php — Qdrant Cloud
Produccion apunta a Qdrant Cloud con HTTPS obligatorio (segun Anexo A1):
// Qdrant Cloud (PRODUCCION — HTTPS obligatorio)
$qdrant_url = getenv('QDRANT_CLUSTER_URL');
$qdrant_key = getenv('QDRANT_API_KEY');
if (!str_starts_with($qdrant_url, 'https://')) {
  throw new \RuntimeException('QDRANT_CLUSTER_URL debe usar HTTPS');
}
$config['jaraba_rag.settings']['qdrant_cluster_url'] = $qdrant_url;
$config['jaraba_rag.settings']['qdrant_api_key'] = $qdrant_key;
7.5 Multi-dominio via Traefik
labels:
  - "traefik.http.routers.drupal.rule=Host(`app.jarabaimpact.com`) || Host(`pepejaraba.com`) || Host(`www.pepejaraba.com`) || Host(`plataformadeecosistemas.es`) || Host(`www.plataformadeecosistemas.es`)"
  - "traefik.http.routers.drupal.tls.certresolver=letsencrypt"
 
8. Fase 4: Backup y Disaster Recovery (3 Capas)
8.1 Capa 1 — Backup local + subida a R2
Archivo: /opt/jaraba/scripts/backup.sh — Ejecutado via cron cada 6h
#!/bin/bash
set -euo pipefail
DATE=$(date +%Y%m%d_%H%M%S)

# 1. Dump MariaDB
docker exec jaraba-mariadb mariadb-dump \
  -u root -p"${DB_ROOT_PASSWORD}" \
  --single-transaction --quick --routines --triggers \
  ${DB_NAME} | gzip > /tmp/db_${DATE}.sql.gz

# 2. Subir a Cloudflare R2
rclone copy /tmp/db_${DATE}.sql.gz jaraba-r2:jaraba-backups/database/

# 3. Verificar y borrar local
rclone ls jaraba-r2:jaraba-backups/database/db_${DATE}.sql.gz && rm /tmp/db_${DATE}.sql.gz

# 4. Mantener solo ultimo backup local para restore rapido
cp /tmp/db_${DATE}.sql.gz /opt/jaraba/backups/database/latest.sql.gz 2>/dev/null || true
8.2 Capa 2 — Cloudflare R2 (retencion 30 dias)
Configurar lifecycle rule en el bucket R2 para borrar automaticamente objetos >30 dias en la carpeta database/. Los archivos publicos de Drupal (carpeta files/) se retienen indefinidamente.
8.3 Capa 3 — NAS 16TB via GoodSync
Job GoodSync configurado en equipo de oficina:
Origen: SFTP → IP_SERVIDOR:2222 (usuario jaraba, clave SSH) → /opt/jaraba/backups/
Destino: \\NAS\backups\jaraba-ionos\
Programacion: Diario a hora fija. Sincronizacion unidireccional (solo origen→destino).
Estructura NAS: /backups/jaraba-ionos/{database/, files/, private/, config/}
AVISO: GoodSync depende de que el equipo de oficina este encendido. No sustituye a R2 como backup primario — es la tercera linea de defensa.
8.4 RTO / RPO
Metrica	Objetivo	Estrategia
RPO	< 6 horas	Backups cada 6h + binlog continuo
RTO	< 1 hora	Restore script automatizado desde R2 o local
Restore completo	< 30 minutos	Imagen IONOS + restore datos
 
9. Fase 5: Monitoring
Stack completo: Prometheus + Grafana + Loki + exporters. Docker Compose separado (docker-compose.monitoring.yml). Todos los puertos solo en 127.0.0.1.
Servicio	RAM Limit	Retencion	Puerto
Prometheus	2 GB	30 dias	127.0.0.1:9090
Grafana	1 GB	Persistente	Via Traefik (grafana.jarabaimpact.com)
Loki + Promtail	2.5 GB	14 dias	127.0.0.1:3100
AlertManager	256 MB	-	127.0.0.1:9093
Node Exporter	128 MB	-	127.0.0.1:9100
Blackbox Exporter	128 MB	-	127.0.0.1:9115
Alertas criticas: RAM >85%, Disco >80%, MariaDB unhealthy, Redis down, Drupal P95 >3s, SSL <14 dias, Backup failed. Notificaciones via email (SendGrid transaccional).
 
10. Fase 6: DNS, Dominios y SSL
Dominio	Tipo	Destino	Proposito
app.jarabaimpact.com	A	<IP_SERVIDOR>	Aplicacion SaaS principal
jarabaimpact.com	A	<IP_SERVIDOR>	Web corporativa B2B
pepejaraba.com	A	<IP_SERVIDOR>	Marca personal
plataformadeecosistemas.es	A	<IP_SERVIDOR>	Portal SaaS operativo
cdn.jarabaimpact.com	CNAME	R2 bucket via Cloudflare	CDN archivos publicos (desde ano 2)
grafana.jarabaimpact.com	A	<IP_SERVIDOR>	Dashboard monitoring (restringido)
SSL: Let's Encrypt via Traefik (auto-renovacion). Si Cloudflare proxy activo: modo Full (Strict).
 
11. Fase 7: Migracion desde Servidor Managed
D-7: Test completo en servidor nuevo
D-1: Import de prueba (DB + rsync files)
Dia D: Ventana 30-60 minutos
# 1. Modo mantenimiento en servidor actual
# 2. Export FINAL de DB
# 3. Import en servidor nuevo
docker exec -i jaraba-mariadb mariadb -u root -p"${DB_ROOT_PASSWORD}" ${DB_NAME} < final_dump.sql
# 4. Rsync final (solo deltas)
# 5. drush cr && drush updb -y
# 6. Actualizar DNS (TTL ya reducido a 300s)
# 7. Verificar: curl -I https://app.jarabaimpact.com
# 8. Quitar modo mantenimiento
CRITICO: Reducir TTL de DNS a 300 segundos AL MENOS 48h antes de la migracion.
 
12. Validacion Post-Implementacion
#	Verificacion	Resultado Esperado
01	CPU: lscpu | grep 'Model name'	AMD EPYC 4465P
02	RAM: free -h | grep Mem	~125Gi
03	RAID: cat /proc/mdstat	[UU] (ambos discos activos)
04	Docker: docker compose ps	Todos containers Up (healthy)
05	MariaDB: SELECT 1	OK, max_connections=300
06	Redis: redis-cli ping	PONG
07	Qdrant Cloud: curl https://<cluster>:6333/healthz	OK (externo)
08	Drupal homepage: curl -I https://app.jarabaimpact.com	HTTP/2 200
09	SSL valido	Certificate verify OK
10	OPcache + JIT activos	opcache.enable=1, jit=1255
11	Redis como cache Drupal	cache.backend.redis
12	Backup script	Exit 0, archivo en R2
13	GoodSync conecta via SFTP	Pull exitoso al NAS
14	Monitoring: Grafana accesible	HTTP 200
15	Firewall: ufw status	Solo 2222/80/443
 
13. Comandos de Operacion Diaria
Operacion	Comando
Estado servicios	docker compose -f docker-compose.prod.yml ps
Logs Drupal	docker logs -f jaraba-drupal --tail=200
Reiniciar tras deploy	docker restart jaraba-drupal
Rebuild cache	docker exec jaraba-drupal drush cr
Login admin	docker exec jaraba-drupal drush uli
Import config	docker exec jaraba-drupal drush cim -y
Ver watchdog	docker exec jaraba-drupal drush ws --count=50
Tamano DB	docker exec jaraba-mariadb mariadb -u root -p... -e "SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) AS 'MB' FROM information_schema.tables WHERE table_schema='jaraba';"
Backup manual	docker exec jaraba-mariadb mariadb-dump -u root -p... --single-transaction jaraba | gzip > manual_backup.sql.gz
Modo mantenimiento ON	docker exec jaraba-drupal drush state:set system.maintenance_mode 1
 
14. Conclusion
Este runbook v2 proporciona un camino completo desde servidor vacio hasta plataforma SaaS operativa, calibrado para el hardware real (AMD EPYC 4465P Zen 5, 128GB DDR5, 1TB NVMe) y verificado contra el Estado de Implementacion del SaaS v1.0.0 (92 modulos, 441 entidades, 11 agentes IA Gen 2).

Correcciones respecto a versiones anteriores: Qdrant eliminado del servidor (Qdrant Cloud segun Anexo A1). Minio eliminado (Cloudflare R2). MariaDB corregido a 10.11 (no 11.2). PHP corregido a 8.4 (no 8.3). SendGrid como tuberia de entrega para jaraba_email nativo (no como SaaS de marketing). NAS 16TB integrado como tercera capa de backup. Previsiones economicas escalonadas a 5 anos.

Coste del ano 1: ~190 EUR/mes — menos de lo que cuesta el servidor managed actual, con capacidad para desplegar las 3 primeras verticales, Redis, Docker, backups automatizados, monitoring, y SSL multi-dominio.

--- Fin del Documento ---
