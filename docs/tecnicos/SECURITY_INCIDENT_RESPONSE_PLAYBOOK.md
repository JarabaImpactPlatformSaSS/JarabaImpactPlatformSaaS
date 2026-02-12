# Playbook de Respuesta a Incidentes de Seguridad

**Plataforma:** Jaraba Impact Platform SaaS
**Version:** 1.0
**Ultima actualizacion:** 2026-02-12
**Clasificacion:** Interno - Confidencial

---

## Indice

1. [Matriz de Clasificacion de Severidad](#1-matriz-de-clasificacion-de-severidad)
2. [Procedimientos de Respuesta por Tipo](#2-procedimientos-de-respuesta-por-tipo)
3. [Plantillas de Comunicacion](#3-plantillas-de-comunicacion)
4. [Lista de Contactos](#4-lista-de-contactos)
5. [Cronograma de Notificacion RGPD Art. 33](#5-cronograma-de-notificacion-rgpd-art-33)
6. [Preservacion de Evidencia](#6-preservacion-de-evidencia)
7. [Revision Post-Incidente](#7-revision-post-incidente)

---

## 1. Matriz de Clasificacion de Severidad

| Nivel | Descripcion | Ejemplos | Tiempo de Respuesta | Escalado |
|-------|-------------|----------|---------------------|----------|
| **SEV1 - Critico** | Brecha de datos confirmada o compromiso de sistema | Exfiltracion de datos personales, acceso root no autorizado, ransomware activo, compromiso de base de datos de produccion | **15 minutos** | CISO + DPO + Direccion + Legal |
| **SEV2 - Alto** | Explotacion activa de vulnerabilidad | Explotacion de CVE en produccion, escalada de privilegios, inyeccion SQL confirmada, bypass de autenticacion | **1 hora** | CISO + Equipo de seguridad + DPO |
| **SEV3 - Medio** | Actividad sospechosa que requiere investigacion | Intentos de fuerza bruta, escaneo de puertos anomalo, patron de acceso inusual, alerta de IDS/IPS | **4 horas** | Equipo de seguridad |
| **SEV4 - Bajo** | Violacion menor de politica de seguridad | Contrasena debil detectada, certificado proximo a expirar, configuracion no optima, incumplimiento menor de politica | **24 horas** | Administrador de seguridad |

### Criterios de Escalado

- Si un incidente SEV3 o SEV4 **no se resuelve** en su ventana de tiempo, se escala automaticamente al siguiente nivel.
- Cualquier incidente que involucre **datos personales** (RGPD) se trata como minimo SEV2.
- Incidentes que afecten a **multiples tenants** se tratan como minimo SEV1.

---

## 2. Procedimientos de Respuesta por Tipo

### 2.1 Brecha de Datos (Data Breach)

#### Deteccion
- Alerta de Trivy/WAF indicando acceso no autorizado a datos.
- Notificacion del sistema de audit log (`audit_log` entity) de accesos anomalos.
- Reporte de usuario o tercero sobre exposicion de datos.
- Anomalias detectadas por `UsageAnomalyDetectorService`.
- Alertas de Prometheus/Grafana sobre patrones de consulta inusuales.

#### Contencion (primeros 30 minutos)
1. **Aislar** el sistema afectado: deshabilitar el tenant o modulo comprometido.
   ```bash
   # Poner el sitio en modo mantenimiento si es necesario
   drush state:set system.maintenance_mode 1

   # Revocar sesiones activas del usuario comprometido
   drush sql:query "DELETE FROM sessions WHERE uid = <UID>"

   # Bloquear IP sospechosa en el firewall
   # (via panel de IONOS o reglas de iptables)
   ```
2. **Preservar** logs y evidencia antes de cualquier cambio (ver seccion 6).
3. **Revocar** credenciales comprometidas (API keys, tokens de Stripe, claves de Qdrant).
4. **Notificar** al DPO: el reloj de 72 horas del RGPD comienza en el momento de la deteccion.

#### Erradicacion
1. Identificar el vector de ataque (logs de Apache/Nginx, audit_log, Loki).
2. Parchear la vulnerabilidad explotada.
3. Rotar todas las credenciales potencialmente comprometidas:
   - Claves de base de datos (MariaDB).
   - API keys de Stripe, OpenAI, Qdrant.
   - Secretos de GitHub Actions.
   - Claves VAPID de push notifications.
4. Escanear con Trivy para confirmar que no hay persistencia.

#### Recuperacion
1. Restaurar desde backup verificado si hay datos corrompidos:
   ```bash
   # Verificar backups disponibles
   ls -lt ~/backups/db_pre_deploy_*.sql.gz | head -5
   ```
2. Reactivar servicios de forma gradual, monitorizando metricas.
3. Verificar integridad de datos de todos los tenants afectados.
4. Reactivar el sitio: `drush state:set system.maintenance_mode 0`

#### Post-Mortem
- Completar la plantilla de revision post-incidente (seccion 7).
- Plazo maximo: **5 dias habiles** tras la resolucion.

---

### 2.2 Ransomware

#### Deteccion
- Archivos cifrados detectados en el filesystem.
- Nota de rescate encontrada.
- Servicios inaccesibles sin causa aparente.

#### Contencion
1. **Desconectar inmediatamente** el servidor afectado de la red.
2. **NO reiniciar** el sistema (preservar memoria RAM para forense).
3. **NO pagar** el rescate bajo ninguna circunstancia.
4. Documentar la nota de rescate y cualquier indicador de compromiso (IOCs).

#### Erradicacion
1. Identificar la variante de ransomware y vector de entrada.
2. Verificar si afecta a backups (los backups de IONOS deben estar en ubicacion separada).
3. Escanear todos los sistemas conectados para detectar movimiento lateral.

#### Recuperacion
1. Reinstalar el sistema operativo desde imagen limpia.
2. Restaurar datos desde el backup mas reciente no comprometido.
3. Redesplegar la aplicacion via GitHub Actions (`deploy-production.yml`).
4. Verificar integridad de la base de datos y archivos de tenants.
5. Cambiar TODAS las credenciales del sistema.

#### Post-Mortem
- Incluir analisis de la cadena de ataque completa.
- Evaluar mejoras en la estrategia de backup y segmentacion de red.

---

### 2.3 Ataque DDoS

#### Deteccion
- Latencia elevada en metricas de Prometheus/Grafana.
- Alertas de `ecosistema_jaraba_core.rate_limiter` por limites excedidos.
- Logs de acceso con volumen anomalo desde IPs concentradas.

#### Contencion
1. Activar reglas de rate limiting agresivas:
   ```bash
   # Verificar estado del rate limiter
   drush state:get ecosistema_jaraba_core.rate_limit_enabled
   ```
2. Bloquear rangos de IP atacantes en el firewall/CDN.
3. Activar modo "bajo ataque" en Cloudflare/CDN si esta disponible.
4. Considerar poner servicios no criticos en modo mantenimiento.

#### Erradicacion
1. Analizar patrones del ataque (volumetrico, aplicacion, reflexion).
2. Implementar reglas WAF especificas para los patrones detectados.
3. Coordinar con el proveedor de hosting (IONOS) si es necesario.

#### Recuperacion
1. Desactivar gradualmente las reglas de emergencia.
2. Monitorizar durante 48 horas para detectar resurgencia.
3. Verificar que todos los servicios responden correctamente.

#### Post-Mortem
- Documentar la eficacia de las medidas de mitigacion.
- Evaluar necesidad de un servicio anti-DDoS dedicado.

---

### 2.4 Compromiso de Credenciales

#### Deteccion
- Accesos desde ubicaciones geograficas inusuales (audit_log).
- Multiples intentos de login fallidos seguidos de uno exitoso.
- Actividad de API con patron anomalo.
- Alerta de servicio de terceros (Stripe, GitHub) sobre credenciales filtradas.

#### Contencion
1. **Revocar inmediatamente** las credenciales comprometidas.
2. **Bloquear** la cuenta de usuario afectada:
   ```bash
   drush user:block <username>
   ```
3. **Invalidar** todas las sesiones del usuario:
   ```bash
   drush sql:query "DELETE FROM sessions WHERE uid = <UID>"
   ```
4. Si son API keys: revocar y regenerar en el servicio correspondiente.

#### Erradicacion
1. Determinar el alcance: que sistemas/datos fueron accedidos con las credenciales.
2. Revisar audit_log para todas las acciones realizadas con la cuenta comprometida.
3. Verificar que no se crearon cuentas de backdoor o se modificaron permisos.
4. Comprobar que no se exfiltraron datos de tenants.

#### Recuperacion
1. Emitir nuevas credenciales de forma segura al usuario afectado.
2. Activar 2FA obligatorio si no estaba habilitado.
3. Revisar y reforzar politicas de contrasenas.

#### Post-Mortem
- Identificar como se comprometieron las credenciales (phishing, reutilizacion, filtrado).

---

### 2.5 Ataque a la Cadena de Suministro (Supply Chain)

#### Deteccion
- Alerta de `composer audit` o `npm audit` sobre paquete comprometido.
- Notificacion de seguridad de GitHub (Dependabot, Advisory).
- Comportamiento anomalo tras actualizacion de dependencia.
- Trivy detecta vulnerabilidad en dependencia con CVSS >= 9.0.

#### Contencion
1. **Bloquear** la version comprometida en `composer.lock` / `package-lock.json`.
2. **No desplegar** hasta confirmar que la version en produccion es segura.
3. Verificar si la version comprometida esta en produccion:
   ```bash
   composer show <paquete> | grep versions
   npm ls <paquete>
   ```

#### Erradicacion
1. Actualizar a una version parcheada o revertir a la ultima version segura.
2. Auditar los cambios introducidos por la version comprometida.
3. Verificar que no se inyecto codigo malicioso en el build.
4. Ejecutar scan completo de Trivy:
   ```bash
   trivy fs --security-checks vuln,secret --severity CRITICAL,HIGH .
   ```

#### Recuperacion
1. Redesplegar con dependencias verificadas.
2. Ejecutar tests completos (`phpunit`, `phpstan`, `eslint`).
3. Monitorizar comportamiento de la aplicacion durante 48 horas.

#### Post-Mortem
- Evaluar procesos de verificacion de dependencias.
- Considerar pinning estricto de versiones y verificacion de checksums.

---

## 3. Plantillas de Comunicacion

### 3.1 Comunicacion Interna al Equipo

```
ASUNTO: [SEV{N}] Incidente de Seguridad - {Tipo} - {Fecha}

ESTADO: {Detectado | En contencion | Erradicado | Recuperado | Cerrado}
SEVERIDAD: SEV{N}
HORA DE DETECCION: {YYYY-MM-DD HH:MM UTC}
RESPONSABLE: {Nombre}

RESUMEN:
{Descripcion breve del incidente}

IMPACTO:
- Sistemas afectados: {lista}
- Tenants afectados: {numero y nombres si aplica}
- Datos comprometidos: {Si/No - tipo de datos}

ACCIONES TOMADAS:
1. {accion 1}
2. {accion 2}

PROXIMOS PASOS:
1. {paso 1}
2. {paso 2}

REUNION DE SEGUIMIENTO: {fecha/hora}
```

### 3.2 Notificacion a Usuarios Afectados (RGPD Art. 34)

```
ASUNTO: Aviso de seguridad importante sobre su cuenta en {Nombre del Tenant}

Estimado/a {Nombre del usuario}:

Le informamos de que hemos detectado un incidente de seguridad que puede
haber afectado a sus datos personales.

QUE HA OCURRIDO:
{Descripcion clara y comprensible del incidente}

QUE DATOS SE HAN VISTO AFECTADOS:
{Lista de categorias de datos: nombre, email, datos de uso, etc.}

QUE HEMOS HECHO:
{Medidas adoptadas para contener y resolver el incidente}

QUE PUEDE HACER USTED:
- Cambie su contrasena de acceso a la plataforma.
- Revise la actividad reciente de su cuenta.
- Si detecta cualquier actividad sospechosa, contacte con nosotros.
{Recomendaciones adicionales especificas}

CONTACTO DEL DELEGADO DE PROTECCION DE DATOS:
{Nombre del DPO}
Email: {email del DPO}
Telefono: {telefono}

Lamentamos las molestias ocasionadas. La seguridad de sus datos es
nuestra maxima prioridad.

Atentamente,
{Nombre del responsable}
{Cargo}
Jaraba Impact Platform
```

### 3.3 Notificacion a la AEPD (Agencia Espanola de Proteccion de Datos)

> **IMPORTANTE:** Esta notificacion debe realizarse en un plazo maximo de **72 horas**
> desde la deteccion del incidente, conforme al RGPD Art. 33.
> Canal de notificacion: https://sedeagpd.gob.es

```
NOTIFICACION DE BRECHA DE SEGURIDAD DE DATOS PERSONALES
(RGPD Art. 33 / LOPDGDD Art. 73)

1. DATOS DEL RESPONSABLE DEL TRATAMIENTO
   - Razon social: {Razon social de la empresa}
   - CIF: {CIF}
   - Direccion: {Direccion}
   - Persona de contacto: {Nombre}
   - Email de contacto: {email}
   - Delegado de Proteccion de Datos: {Nombre del DPO}
   - Contacto DPO: {email del DPO}

2. DESCRIPCION DE LA BRECHA
   - Fecha y hora de deteccion: {YYYY-MM-DD HH:MM UTC}
   - Fecha estimada de inicio: {YYYY-MM-DD HH:MM UTC}
   - Tipo de brecha:
     [ ] Confidencialidad (acceso no autorizado)
     [ ] Integridad (modificacion no autorizada)
     [ ] Disponibilidad (perdida de acceso)
   - Descripcion: {Descripcion detallada del incidente}

3. CATEGORIAS DE DATOS AFECTADOS
   [ ] Datos identificativos (nombre, email, telefono)
   [ ] Datos de acceso (credenciales)
   [ ] Datos economicos (informacion de facturacion)
   [ ] Datos de uso de la plataforma
   [ ] Datos de interacciones con IA
   [ ] Otros: {especificar}

4. NUMERO DE AFECTADOS
   - Numero estimado de interesados: {numero}
   - Numero estimado de registros: {numero}

5. CONSECUENCIAS PROBABLES
   {Descripcion de las posibles consecuencias para los afectados}

6. MEDIDAS ADOPTADAS
   - Medidas de contencion: {descripcion}
   - Medidas correctoras: {descripcion}
   - Medidas para mitigar efectos: {descripcion}

7. COMUNICACION A LOS AFECTADOS
   - Se ha comunicado a los afectados: {Si/No}
   - Fecha de comunicacion: {fecha}
   - Medio utilizado: {email/carta/notificacion en plataforma}
   - Si no se ha comunicado, motivos: {justificacion}

8. DOCUMENTACION ADJUNTA
   {Lista de documentos adjuntos: logs, informes forenses, etc.}
```

### 3.4 Comunicacion a Direccion / Management

```
ASUNTO: [URGENTE] Informe de Incidente de Seguridad SEV{N}

Para: Direccion General
De: {CISO / Responsable de Seguridad}
Fecha: {Fecha}

RESUMEN EJECUTIVO:
{1-2 parrafos con resumen no tecnico del incidente}

IMPACTO EN NEGOCIO:
- Tenants afectados: {numero}
- Usuarios afectados: {numero}
- Tiempo de inactividad: {duracion}
- Riesgo regulatorio: {bajo/medio/alto}
- Impacto reputacional estimado: {bajo/medio/alto}

ESTADO ACTUAL: {En contencion / Resuelto / En investigacion}

OBLIGACIONES REGULATORIAS:
- Notificacion AEPD: {Requerida/No requerida} - Plazo: {fecha limite}
- Notificacion a usuarios: {Requerida/No requerida}

COSTE ESTIMADO:
- Respuesta al incidente: {estimacion}
- Remediacion tecnica: {estimacion}
- Potenciales sanciones: {estimacion basada en RGPD}

DECISIONES REQUERIDAS:
1. {Decision 1}
2. {Decision 2}
```

---

## 4. Lista de Contactos

> **NOTA:** Completar con los datos reales del equipo. Mantener esta seccion
> actualizada trimestralmente.

| Rol | Nombre | Email | Telefono | Disponibilidad |
|-----|--------|-------|----------|----------------|
| **CISO** | {Nombre} | {email} | {telefono} | 24/7 |
| **DPO** (Delegado de Proteccion de Datos) | {Nombre} | {email} | {telefono} | Laborables |
| **CTO / Lead Developer** | {Nombre} | {email} | {telefono} | 24/7 |
| **Responsable Legal** | {Nombre} | {email} | {telefono} | Laborables |
| **Director General** | {Nombre} | {email} | {telefono} | Escalado SEV1 |
| **Administrador de Sistemas** | {Nombre} | {email} | {telefono} | 24/7 |
| **Contacto IONOS** (Hosting) | Soporte IONOS | soporte@ionos.es | 900 801 392 | 24/7 |
| **AEPD** (Autoridad de Control) | Agencia Espanola de Proteccion de Datos | internacional@aepd.es | 901 100 099 | Laborables |

### Canales de Comunicacion de Emergencia

- **Slack:** Canal `#seguridad-incidentes` (privado)
- **Email de grupo:** seguridad@{dominio}.io
- **Telefono de guardia:** {numero}
- **AEPD Sede electronica:** https://sedeagpd.gob.es

---

## 5. Cronograma de Notificacion RGPD Art. 33

El Reglamento General de Proteccion de Datos (RGPD) establece en su **Articulo 33**
que el responsable del tratamiento debe notificar una brecha de datos personales a
la autoridad de control competente (AEPD en Espana) **sin dilacion indebida y, a mas
tardar, 72 horas despues de haber tenido conocimiento de ella**.

### Cronograma de Actuacion

| Hora | Accion | Responsable |
|------|--------|-------------|
| **T+0** | Deteccion del incidente. Registrar hora exacta. | Quien detecte |
| **T+15min** | Notificacion al CISO y equipo de respuesta. | Quien detecte |
| **T+30min** | Evaluacion inicial: determinar si hay datos personales afectados. | CISO |
| **T+1h** | Si hay datos personales: notificar al DPO. Comienza el plazo de 72h. | CISO |
| **T+2h** | Primera contencion completada. Evaluacion de impacto preliminar. | Equipo tecnico |
| **T+4h** | Informe preliminar interno con alcance estimado. | CISO + DPO |
| **T+24h** | Evaluacion detallada: datos afectados, numero de interesados. | DPO |
| **T+48h** | Borrador de notificacion a AEPD preparado y revisado por Legal. | DPO + Legal |
| **T+66h** | Notificacion a AEPD revisada y aprobada por Direccion. | DPO + Direccion |
| **T+72h** | **PLAZO LIMITE: Notificacion enviada a AEPD.** | DPO |
| **T+72h** | Evaluar si se requiere notificacion a los afectados (Art. 34). | DPO + Legal |
| **T+96h** | Si aplica: enviar notificacion a usuarios afectados. | DPO + Equipo |

### Excepciones al Plazo de 72 Horas

Segun el RGPD Art. 33.1, si la notificacion no se realiza en 72 horas, debera
acompanarse de una **justificacion motivada**. Posibles causas:

- Investigacion forense en curso que requiere mas tiempo.
- Coordinacion con autoridades policiales que solicitan retraso.
- Alcance del incidente aun no determinado (notificacion parcial permitida).

---

## 6. Preservacion de Evidencia

### Principios Generales

- **No modificar** la evidencia original.
- **Documentar** cada paso con marcas de tiempo.
- **Mantener** la cadena de custodia en todo momento.
- **Almacenar** la evidencia en ubicacion segura y separada.

### Procedimiento de Recoleccion

#### 6.1 Logs del Sistema

```bash
# Copiar logs de aplicacion con marca de tiempo
INCIDENT_DIR="/root/incidents/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$INCIDENT_DIR"

# Logs de Drupal (watchdog)
drush watchdog:show --count=1000 --format=json > "$INCIDENT_DIR/drupal_watchdog.json"

# Audit logs de la plataforma
drush sql:query "SELECT * FROM audit_log WHERE created >= UNIX_TIMESTAMP(NOW() - INTERVAL 7 DAY)" > "$INCIDENT_DIR/audit_log.sql"

# Logs de Apache/Nginx
cp /var/log/apache2/access.log "$INCIDENT_DIR/apache_access.log"
cp /var/log/apache2/error.log "$INCIDENT_DIR/apache_error.log"

# Logs de Promtail/Loki si estan disponibles
# Exportar via API de Loki los ultimos 7 dias

# Calcular checksums para integridad
sha256sum "$INCIDENT_DIR"/* > "$INCIDENT_DIR/checksums.sha256"
```

#### 6.2 Estado del Sistema

```bash
# Snapshot del estado actual
date -u > "$INCIDENT_DIR/timestamp.txt"
uname -a >> "$INCIDENT_DIR/system_info.txt"
ps aux >> "$INCIDENT_DIR/processes.txt"
netstat -tlnp >> "$INCIDENT_DIR/network_connections.txt"
last -50 >> "$INCIDENT_DIR/login_history.txt"
```

#### 6.3 Base de Datos

```bash
# Dump de tablas relevantes para la investigacion
# NUNCA hacer dump completo si hay datos sensibles - solo tablas necesarias
mysqldump -u root drupal sessions > "$INCIDENT_DIR/sessions_dump.sql"
mysqldump -u root drupal watchdog > "$INCIDENT_DIR/watchdog_dump.sql"
mysqldump -u root drupal audit_log > "$INCIDENT_DIR/audit_log_dump.sql"
```

#### 6.4 Cadena de Custodia

Registrar para cada pieza de evidencia:

| Campo | Valor |
|-------|-------|
| ID de evidencia | {EVD-YYYYMMDD-NNN} |
| Descripcion | {que es} |
| Fecha/hora de recoleccion | {timestamp UTC} |
| Recolectado por | {nombre} |
| Metodo de recoleccion | {comando/herramienta} |
| Hash SHA-256 | {hash} |
| Ubicacion de almacenamiento | {ruta} |

---

## 7. Revision Post-Incidente

### Plantilla de Informe Post-Mortem

```
INFORME POST-MORTEM DE INCIDENTE DE SEGURIDAD

ID del Incidente: {INC-YYYYMMDD-NNN}
Severidad: SEV{N}
Fecha del Incidente: {fecha}
Fecha del Informe: {fecha}
Autor: {nombre}
Revisores: {nombres}

1. RESUMEN EJECUTIVO
   {Resumen de 2-3 parrafos del incidente, impacto y resolucion}

2. CRONOLOGIA DETALLADA
   | Hora (UTC) | Evento | Actor |
   |------------|--------|-------|
   | {hora}     | {que}  | {quien} |

3. CAUSA RAIZ
   {Analisis detallado de la causa raiz. Utilizar la tecnica de los
   "5 Porques" para llegar a la causa fundamental.}

   - Por que 1: {respuesta}
   - Por que 2: {respuesta}
   - Por que 3: {respuesta}
   - Por que 4: {respuesta}
   - Por que 5: {respuesta}

4. IMPACTO
   - Tenants afectados: {numero y lista}
   - Usuarios afectados: {numero}
   - Datos comprometidos: {categorias y volumen}
   - Tiempo de inactividad: {duracion}
   - Coste estimado: {euros}

5. QUE FUNCIONO BIEN
   - {elemento 1}
   - {elemento 2}

6. QUE SE PUEDE MEJORAR
   - {elemento 1}
   - {elemento 2}

7. ACCIONES CORRECTIVAS
   | Accion | Responsable | Prioridad | Fecha limite | Estado |
   |--------|-------------|-----------|-------------|--------|
   | {accion} | {nombre} | {P0-P3} | {fecha} | {estado} |

8. LECCIONES APRENDIDAS
   {Conclusiones clave para prevenir incidentes similares}

9. METRICAS DE RESPUESTA
   - Tiempo de deteccion (TTD): {minutos/horas}
   - Tiempo de contencion (TTC): {minutos/horas}
   - Tiempo de resolucion (TTR): {horas/dias}
   - Notificacion AEPD: {Si/No - dentro de plazo Si/No}

10. APROBACION
    - CISO: {nombre} - Fecha: {fecha}
    - DPO: {nombre} - Fecha: {fecha}
    - Direccion: {nombre} - Fecha: {fecha}
```

### Calendario de Revisiones

- **Reunion de post-mortem:** Maximo 5 dias habiles tras la resolucion del incidente.
- **Revision de acciones correctivas:** 30 dias tras el post-mortem.
- **Revision trimestral:** Revisar todos los incidentes del trimestre para identificar tendencias.
- **Actualizacion del playbook:** Tras cada incidente SEV1 o SEV2, actualizar este documento si es necesario.

---

## Anexo A: Herramientas del Ecosistema

| Herramienta | Uso en Respuesta a Incidentes |
|-------------|-------------------------------|
| Drupal Watchdog (`drush watchdog:show`) | Logs de aplicacion |
| Audit Log Entity (`audit_log`) | Registro de eventos de seguridad |
| Prometheus + Grafana | Metricas y alertas de infraestructura |
| Loki + Promtail | Agregacion centralizada de logs |
| Alertmanager | Enrutamiento de alertas (Slack, email) |
| Trivy | Escaneo de vulnerabilidades |
| OWASP ZAP | Analisis DAST de la aplicacion |
| `RateLimiterService` | Proteccion contra abuso de APIs |
| `UsageAnomalyDetectorService` | Deteccion de anomalias de uso |
| `AuditLogService` | Registro programatico de eventos |
| `drush gdpr:export` | Exportacion de datos personales (Art. 15) |
| `drush gdpr:anonymize` | Derecho al olvido (Art. 17) |
| `drush gdpr:report` | Informe de estado de cumplimiento |

## Anexo B: Referencias Legales

- **RGPD** (Reglamento UE 2016/679): Articulos 33 y 34 sobre notificacion de brechas.
- **LOPDGDD** (Ley Organica 3/2018): Complemento nacional al RGPD.
- **ENS** (Esquema Nacional de Seguridad): Aplicable si se gestionan datos publicos.
- **Directiva NIS2** (UE 2022/2555): Requisitos de ciberseguridad para servicios digitales.
- **AEPD - Guia de Notificacion de Brechas:** https://www.aepd.es/guias/guia-brechas-seguridad.pdf
