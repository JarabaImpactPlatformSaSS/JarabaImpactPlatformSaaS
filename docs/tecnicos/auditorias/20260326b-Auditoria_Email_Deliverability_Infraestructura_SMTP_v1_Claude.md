# Auditoria de Email Deliverability e Infraestructura SMTP — SaaS Produccion

**Fecha de creacion:** 2026-03-26 12:00
**Ultima actualizacion:** 2026-03-26 12:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Metodologia:** 9 Disciplinas Senior (Arquitectura SaaS, Ingenieria SW, UX, Drupal, Web Dev, Theming, GrapesJS, SEO/GEO, IA)
**Referencia previa:** [20260326-Auditoria_Seguridad_Produccion_IONOS_v1_Claude.md](./20260326-Auditoria_Seguridad_Produccion_IONOS_v1_Claude.md)
**Ambito:** Deliverability, autenticacion DNS, infraestructura SMTP, reputacion IP, arquitectura de envio transaccional
**Documentos fuente:** 00_DIRECTRICES_PROYECTO.md v166.0.0, 00_FLUJO_TRABAJO_CLAUDE.md v115.0.0, CLAUDE.md v1.10.0
**Evento desencadenante:** Bounce SMTP 550 5.7.1 — IP 82.165.159.38 bloqueada por Spamhaus (2026-03-26 08:39 UTC)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto y Alcance](#2-contexto-y-alcance)
3. [Incidente Desencadenante](#3-incidente-desencadenante)
4. [Hallazgos Criticos (4)](#4-hallazgos-criticos-4)
5. [Hallazgos Altos (3)](#5-hallazgos-altos-3)
6. [Hallazgos Medios (4)](#6-hallazgos-medios-4)
7. [Hallazgos Bajos (2)](#7-hallazgos-bajos-2)
8. [Areas Aprobadas](#8-areas-aprobadas)
9. [Matriz de Riesgo Consolidada](#9-matriz-de-riesgo-consolidada)
10. [Analisis de Impacto en el SaaS](#10-analisis-de-impacto-en-el-saas)
11. [Arquitectura Actual vs Clase Mundial](#11-arquitectura-actual-vs-clase-mundial)
12. [Plan de Remediacion Priorizado](#12-plan-de-remediacion-priorizado)
13. [Nuevas Reglas Propuestas](#13-nuevas-reglas-propuestas)
14. [Glosario de Terminos](#14-glosario-de-terminos)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 1. Resumen Ejecutivo

La plataforma **Jaraba Impact Platform** envia email transaccional (registro de usuarios, activacion de cuentas, billing, notificaciones de workflows, alertas GDPR, facturas electronicas, campanas de marketing) a traves de **IONOS SMTP compartido** (`smtp.ionos.es:587`). Este servicio es independiente del servidor dedicado contratado — IONOS vende hosting y email como productos separados con infraestructura independiente.

El 26 de marzo de 2026 a las 08:39 UTC, un email dirigido a `contacto@pepejaraba.es` (dominio alojado en Microsoft 365) fue rechazado con error **550 5.7.1** porque la IP de salida del relay de IONOS (`82.165.159.38`, hostname `mout-xforward.kundenserver.de`) estaba listada en **Spamhaus**. Este incidente evidencia un problema estructural: la dependencia de infraestructura SMTP compartida para un SaaS multi-tenant con 22 modulos que envian email.

### Puntuacion Global: 3.5/10 (Email Deliverability)

| Dimension | Puntuacion | Notas |
|-----------|:----------:|-------|
| **Autenticacion DNS (SPF/DKIM/DMARC)** | 2/10 | SPF softfail, sin DKIM, DMARC delegado a IONOS con p=none |
| **Reputacion IP** | 3/10 | IP compartida con historial de bloqueo Spamhaus |
| **Control de envio** | 2/10 | Sin IP dedicada, sin control sobre relay de salida |
| **Monitoring/Alerting** | 1/10 | Sin metricas de deliverability, sin alertas de bounce |
| **Bounce/Complaint handling** | 3/10 | Modelo de datos existe pero sin automatizacion |
| **Arquitectura de transporte** | 4/10 | Symfony Mailer bien configurado, pero transporte inadecuado |
| **Separacion transaccional/marketing** | 5/10 | SendGrid parcialmente integrado para campanas |
| **Seguridad del canal** | 6/10 | TLS, verify_peer, secrets via env vars |

### Distribucion de Hallazgos

| Severidad | Cantidad | Estado |
|-----------|:--------:|--------|
| **CRITICA** | 4 | Requieren fix inmediato |
| **ALTA** | 3 | Fix esta semana |
| **MEDIA** | 4 | Proximo sprint |
| **BAJA** | 2 | Backlog |
| **PASS** | 5 | Verificadas correctas |

---

## 2. Contexto y Alcance

### 2.1 Infraestructura de Email Auditada

| Aspecto | Detalle |
|---------|---------|
| **Servidor dedicado** | IONOS AE12-128 NVMe, IP: 82.223.204.169 (web hosting) |
| **SMTP de envio** | `smtp.ionos.es:587` (servicio compartido IONOS, IPs: 212.227.24.164/220) |
| **Relay de salida** | `mout-xforward.kundenserver.de` (IP pool: 82.165.159.0/26, compartido) |
| **Sender principal** | `contacto@plataformadeecosistemas.com` |
| **MX del sender** | `mx00.ionos.es` / `mx01.ionos.es` |
| **Transporte Drupal** | Symfony Mailer, transport `smtp_ionos`, port 587, TLS |
| **Modulos que envian** | 22 modulos custom con `hook_mail()` + CI notifications |

### 2.2 Cadena de Envio Completa

```
App PHP (82.223.204.169 — servidor dedicado)
  |
  | SMTP AUTH + STARTTLS (port 587)
  v
smtp.ionos.es (212.227.24.164/220 — submission server)
  |
  | Internal relay (sin control del cliente)
  v
mout-xforward.kundenserver.de (82.165.159.0/26 — outbound relay COMPARTIDO)
  |
  | SMTP to recipient MX
  v
Servidor destino (outlook.com, gmail.com, etc.)
```

**Hallazgo clave:** El servidor dedicado (82.223.204.169) **NO envia email directamente**. No tiene Postfix/Sendmail instalado. Toda la entrega depende del relay compartido de IONOS, cuya IP esta fuera del control del cliente.

### 2.3 Alcance de la Auditoria

| Dimension | Incluido | Excluido |
|-----------|----------|----------|
| DNS | SPF, DKIM, DMARC, PTR de todas las IPs | BIMI, MTA-STS |
| Transporte | Symfony Mailer config, env vars, secrets | Codigo interno de cada hook_mail() |
| Reputacion | Spamhaus, consulta DNS-based blocklist | Barracuda, Proofpoint, SORBS |
| Modulos | Los 22 que implementan hook_mail() | Contenido de templates MJML |
| Infra | IONOS SMTP, relay IPs, servidor dedicado | Infraestructura interna de IONOS |
| SendGrid | Integracion existente en jaraba_email | Configuracion de cuenta SendGrid |

### 2.4 Metodologia

- **Tipo:** Analisis estatico de codigo + consultas DNS live + analisis de bounce headers
- **Herramientas:** `dig`, `host`, `whois`, Spamhaus DNS lookup, revision de config/sync/
- **Archivos auditados:** 35+ archivos de configuracion, servicios, routing, workflows

---

## 3. Incidente Desencadenante

### 3.1 Bounce Recibido

**Fecha/hora:** 2026-03-26 08:39:07.426 UTC
**Destinatario:** `contacto@pepejaraba.es`
**Servidor destino:** `pepejaraba-es.mail.protection.outlook.com` (52.101.73.30)
**Error SMTP:**

```
550 5.7.1 Service unavailable, Client host [82.165.159.38] blocked using Spamhaus.
To request removal from this list see https://www.spamhaus.org/query/ip/82.165.159.38
```

### 3.2 Analisis del Bounce

| Campo | Valor | Significado |
|-------|-------|-------------|
| **Codigo** | 550 | Rechazo permanente (hard bounce) |
| **Enhanced status** | 5.7.1 | Policy rejection — seguridad |
| **IP bloqueada** | 82.165.159.38 | Relay de salida de IONOS |
| **PTR** | `mout-xforward.kundenserver.de` | Infraestructura compartida IONOS (kundenserver = "servidor de clientes" en aleman) |
| **Blocklist** | Spamhaus | La mas influyente a nivel global |
| **Receptor** | Microsoft 365 (Outlook) | Consulta Spamhaus para todas las conexiones entrantes |

### 3.3 Estado Posterior

Consulta DNS a Spamhaus realizada a las ~12:00 UTC del mismo dia:

```
38.159.165.82.zen.spamhaus.org → NXDOMAIN (ya no listada)
```

La IP fue **delistada entre las 08:39 y las 12:00 UTC**. Esto sugiere un listing temporal de Spamhaus (tipicamente 24-48h), lo que indica un incidente puntual de spam desde otro cliente del mismo pool de IONOS. Sin embargo, **no hay garantia de que no vuelva a ocurrir**.

---

## 4. Hallazgos Criticos (4)

### EMAIL-C01: IP de salida SMTP compartida sin control de reputacion

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | CRITICA |
| **Regla propuesta** | EMAIL-DEDICATED-IP-001 |
| **CWE** | N/A (infraestructura) |
| **Impacto** | Disponibilidad del servicio de email completo |

**Descripcion detallada:**

El SaaS envia todo su email transaccional y de marketing a traves de `smtp.ionos.es`, un servicio SMTP compartido de IONOS. Los emails salen por un pool de IPs en el rango `82.165.159.0/26` (64 IPs) asignadas al hostname `mout-xforward.kundenserver.de`. Este pool es compartido por **todos los clientes de IONOS** que usan su servicio de email, incluyendo clientes con planes basicos de hosting.

La reputacion de estas IPs depende del comportamiento colectivo de todos los usuarios del pool. Si un solo usuario envia spam, la IP se lista en blocklists como Spamhaus, afectando a **todos los demas usuarios** — incluyendo nuestro SaaS.

**Evidencia DNS:**

```
SPF de plataformadeecosistemas.com:
  v=spf1 include:_spf-eu.ionos.com ~all

_spf-eu.ionos.com expande a:
  ip4:82.165.159.0/26    ← Pool del relay (incluye la IP bloqueada)
  ip4:212.227.126.128/25
  ip4:212.227.15.0/25
  ip4:212.227.17.0/27
  ip4:217.72.192.64/26
  ip4:185.48.116.13/32
```

**Impacto:**

Un SaaS multi-tenant que gestiona billing, registro de usuarios, notificaciones legales (GDPR), facturas electronicas (TicketBAI/VeriFactu) y alertas de workflows **no puede depender de infraestructura compartida** para la entrega de email. El incidente del 2026-03-26 es prueba de que este riesgo es real y materializable.

**Remediacion:** Migrar a un servicio de email transaccional dedicado con IP propia y control de reputacion. Ver Plan de Implementacion asociado.

---

### EMAIL-C02: Ausencia total de DKIM

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | CRITICA |
| **Regla propuesta** | EMAIL-DKIM-001 |
| **CWE** | CWE-345 (Insufficient Verification of Data Authenticity) |
| **Impacto** | Emails pueden ser suplantados; penalizacion en deliverability |

**Descripcion detallada:**

No existe ningun registro DKIM para `plataformadeecosistemas.com`. Se verificaron 8 selectores comunes:

```
default._domainkey.plataformadeecosistemas.com → NXDOMAIN
s1._domainkey → NXDOMAIN
s2._domainkey → NXDOMAIN
ionos._domainkey → NXDOMAIN
mail._domainkey → NXDOMAIN
google._domainkey → NXDOMAIN
selector1._domainkey → NXDOMAIN
selector2._domainkey → NXDOMAIN
```

**Impacto:**

Desde febrero de 2024, Google exige DKIM para remitentes que envian >5.000 emails/dia. Microsoft y Yahoo tienen requisitos similares. Sin DKIM:

1. Los emails no tienen firma criptografica que verifique su autenticidad
2. Cualquier persona puede enviar emails suplantando `contacto@plataformadeecosistemas.com`
3. Los proveedores de email penalizan la puntuacion de spam
4. Algunos proveedores rechazan directamente emails sin DKIM
5. DMARC no puede funcionar correctamente sin DKIM como mecanismo de alineacion

**Remediacion:** Configurar DKIM como parte de la migracion a servicio de email dedicado. El nuevo proveedor generara las claves y proporcionara los registros DNS.

---

### EMAIL-C03: DMARC delegado a IONOS con politica p=none

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | CRITICA |
| **Regla propuesta** | EMAIL-DMARC-001 |
| **CWE** | CWE-345 |
| **Impacto** | Sin proteccion contra spoofing, sin visibilidad de abusos |

**Descripcion detallada:**

El registro DMARC de `plataformadeecosistemas.com` es un CNAME que apunta a `dmarc.ionos.es`, cuyo contenido es:

```
v=DMARC1; p=none;
```

Problemas:

1. **`p=none`** = no se toma ninguna accion contra emails que fallen autenticacion. Es equivalente a no tener DMARC.
2. **Sin `rua=`** = no se reciben reportes agregados. No hay visibilidad de quien envia en nombre del dominio.
3. **Sin `ruf=`** = no se reciben reportes forenses de fallos individuales.
4. **CNAME a IONOS** = el control de la politica DMARC esta delegado a IONOS. Si IONOS cambia su politica, afecta al dominio sin notificacion.

**Remediacion:** Crear registro DMARC propio con politica progresiva: `p=none` + `rua=` (monitoring) → `p=quarantine` → `p=reject`.

---

### EMAIL-C04: Ausencia de reverse DNS (PTR) en servidor dedicado

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | CRITICA (si se plantea envio directo) / MEDIA (con SES) |
| **Regla propuesta** | EMAIL-PTR-001 |
| **Impacto** | Impide envio directo desde el servidor |

**Descripcion detallada:**

```
host 82.223.204.169 → NXDOMAIN (sin registro PTR)
```

El servidor dedicado no tiene reverse DNS configurado. Esto significa que:

1. Si se intentara enviar email directamente desde el servidor (Postfix/Sendmail), seria rechazado por la mayoria de proveedores
2. Cualquier servicio que verifique PTR del servidor (algunos bots, crawlers) no podra resolver el hostname

**Remediacion:** Solicitar a IONOS la configuracion de PTR: `82.223.204.169 → mail.plataformadeecosistemas.com` (o similar). Necesario independientemente de la solucion de email elegida para buenas practicas de servidor.

---

## 5. Hallazgos Altos (3)

### EMAIL-H01: SPF con mecanismo ~all (softfail) en lugar de -all (hardfail)

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Regla propuesta** | EMAIL-SPF-HARDFAIL-001 |

**Descripcion:**

El registro SPF actual usa `~all` (softfail), que indica "los emails que no pasen SPF deberian marcarse como sospechosos pero no rechazarse". Esto permite que emails fraudulentos desde IPs no autorizadas lleguen a la bandeja de entrada del destinatario.

```
Actual:  v=spf1 include:_spf-eu.ionos.com ~all
Optimo:  v=spf1 include:amazonses.com -all
```

**Remediacion:** Cambiar a `-all` (hardfail) tras migrar a servicio dedicado y verificar que todos los canales de envio estan cubiertos en el SPF.

---

### EMAIL-H02: Sin monitoring de deliverability ni alertas de bounce

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Regla propuesta** | EMAIL-MONITORING-001 |

**Descripcion:**

No existe ningun sistema de monitoring para deliverability de email:

- No hay alertas cuando un email rebota
- No hay metricas de tasa de entrega, apertura, clics
- No hay dashboard de salud del canal de email
- No hay alertas proactivas cuando la IP entra en una blocklist
- El incidente del 2026-03-26 se descubrio **manualmente** por el administrador al revisar su buzon

**Impacto:** Emails criticos (activacion de cuentas, facturas, alertas GDPR) pueden fallar silenciosamente sin que nadie se entere.

**Remediacion:** Implementar monitoring con metricas de Amazon SES (bounce rate, complaint rate, delivery rate) + alertas via CloudWatch/SNS.

---

### EMAIL-H03: Webhook de bounce/complaint sin sincronizacion automatica

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Archivos** | `jaraba_email/src/Controller/EmailWebhookController.php` |

**Descripcion:**

El modulo `jaraba_email` tiene un webhook para eventos SendGrid que reconoce bounces y complaints, pero:

1. Los eventos solo se registran en el log (watchdog), no actualizan el estado del `EmailSubscriber`
2. No existe lista de supresion automatica — emails con hard bounce siguen recibiendo campanas
3. No hay distincion entre hard bounce (permanente) y soft bounce (temporal)
4. Los complaints (marcado como spam) no suprimen al suscriptor

**Impacto:** Enviar emails a direcciones que rebotan repetidamente dana la reputacion del sender.

**Remediacion:** Implementar sincronizacion automatica bounce → subscriber status + lista de supresion global.

---

## 6. Hallazgos Medios (4)

### EMAIL-M01: Sin separacion de canales transaccional/marketing

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | MEDIA |

**Descripcion:** Todo el email (registro, billing, GDPR, campanas de marketing, newsletters) sale por el mismo canal SMTP. Un SaaS clase mundial separa estos canales porque tienen requisitos diferentes de deliverability, reputacion y regulacion (CAN-SPAM, RGPD).

---

### EMAIL-M02: CSS inlining deshabilitado en emails HTML

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | MEDIA |
| **Archivo** | `config/sync/symfony_mailer.mailer_policy._.yml` |

**Descripcion:** La configuracion `mailer_inline_css` esta vacia. Muchos clientes de email (especialmente Gmail y Outlook) ignoran estilos en `<style>` tags y requieren CSS inline para renderizar correctamente.

---

### EMAIL-M03: Modulo system.mail.yml con configuracion legacy

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | MEDIA |
| **Archivo** | `config/sync/system.mail.yml` |

**Descripcion:** `system.mail.yml` define `interface.default: php_mail` y `mailer_dsn.scheme: sendmail`. Aunque Symfony Mailer sobreescribe esto en runtime, la configuracion legacy podria causar envios via `php mail()` si Symfony Mailer falla o no esta cargado correctamente.

---

### EMAIL-M04: ci-notify-email.php sin fallback ni retry

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | MEDIA |
| **Archivo** | `scripts/ci-notify-email.php` |

**Descripcion:** El script de notificaciones CI/CD envia directamente via SMTP sin retry. Si el SMTP esta caido o la IP bloqueada (como en el incidente), las notificaciones de deploy fallido se pierden silenciosamente.

---

## 7. Hallazgos Bajos (2)

### EMAIL-L01: Sin registro de email enviado para auditoria

No existe un log persistente de emails transaccionales enviados (destinatario, timestamp, tipo, resultado). Solo watchdog temporal.

### EMAIL-L02: Sin Test de entrega en pipeline CI/CD

No hay smoke test que verifique que el canal de email funciona tras un deploy.

---

## 8. Areas Aprobadas

| Area | Estado | Evidencia |
|------|:------:|-----------|
| **Secrets SMTP via env vars** | PASS | `settings.secrets.php` con `getenv()` para SMTP_USER/PASS/HOST |
| **TLS en transporte** | PASS | Port 587 + `verify_peer: true` |
| **Symfony Mailer como backbone** | PASS | Transport pluggable, politicas por tipo de email |
| **RFC 8058 unsubscribe headers** | PASS | `UnsubscribeTokenService` con HMAC en `jaraba_email` |
| **SendGrid webhook HMAC** | PASS | Verificacion de firma en `EmailWebhookController` |

---

## 9. Matriz de Riesgo Consolidada

| ID | Hallazgo | Probabilidad | Impacto | Riesgo | Prioridad |
|----|----------|:------------:|:-------:|:------:|:---------:|
| EMAIL-C01 | IP compartida sin control | ALTA | CRITICO | P0 | Inmediato |
| EMAIL-C02 | Sin DKIM | ALTA | ALTO | P0 | Inmediato |
| EMAIL-C03 | DMARC p=none delegado | ALTA | ALTO | P0 | Inmediato |
| EMAIL-C04 | Sin PTR servidor | MEDIA | MEDIO | P1 | Esta semana |
| EMAIL-H01 | SPF softfail | MEDIA | ALTO | P1 | Esta semana |
| EMAIL-H02 | Sin monitoring | ALTA | ALTO | P1 | Esta semana |
| EMAIL-H03 | Bounce sin sync | MEDIA | MEDIO | P1 | Esta semana |
| EMAIL-M01 | Sin separacion canales | BAJA | MEDIO | P2 | Sprint |
| EMAIL-M02 | CSS inline deshabilitado | BAJA | BAJO | P2 | Sprint |
| EMAIL-M03 | Config legacy system.mail | BAJA | BAJO | P2 | Sprint |
| EMAIL-M04 | CI notify sin retry | BAJA | BAJO | P2 | Sprint |
| EMAIL-L01 | Sin log persistente | BAJA | BAJO | P3 | Backlog |
| EMAIL-L02 | Sin test CI/CD | BAJA | BAJO | P3 | Backlog |

---

## 10. Analisis de Impacto en el SaaS

### 10.1 Modulos Afectados por el Incidente

Los siguientes 22 modulos envian email via `hook_mail()` y se ven afectados por el bloqueo de IP:

| Modulo | Tipo de Email | Criticidad |
|--------|--------------|:----------:|
| **Drupal Core (user)** | Registro, activacion, reset password | P0 |
| **ecosistema_jaraba_core** | Welcome tenant, lead magnets | P0 |
| **jaraba_billing** | Facturas, cobros, pagos | P0 |
| **jaraba_einvoice_b2b** | Facturas electronicas B2B | P0 |
| **jaraba_facturae** | Alertas certificados, TicketBAI | P0 |
| **jaraba_privacy** | Breach detection, ARCO requests | P0 |
| **jaraba_verifactu** | Notificaciones VeriFactu | P0 |
| **jaraba_workflows** | Workflow notifications | P1 |
| **jaraba_job_board** | Aplicaciones, entrevistas | P1 |
| **jaraba_mentoring** | Mentor applied, welcome | P1 |
| **jaraba_legal_intelligence** | Alertas legales | P1 |
| **jaraba_email** | Campanas, secuencias, newsletters | P1 |
| **jaraba_dr** | Disaster recovery alerts | P1 |
| **jaraba_lms** | Cursos, formacion | P2 |
| **jaraba_comercio_conecta** | Notificaciones comercio | P2 |
| **jaraba_messaging** | Sistema de mensajeria | P2 |
| **jaraba_groups** | Notificaciones de grupo | P2 |
| **jaraba_whitelabel** | Templates white-label | P2 |
| **jaraba_ab_testing** | Alertas A/B testing | P3 |
| **jaraba_business_tools** | Notificaciones herramientas | P3 |
| **jaraba_insights_hub** | Alertas analytics | P3 |
| **jaraba_pixels** | Tracking alerts | P3 |

### 10.2 Impacto por Vertical

| Vertical | Emails Criticos | Riesgo si IP bloqueada |
|----------|----------------|:----------------------:|
| **Todas** | Registro, activacion, password reset | CRITICO |
| **Billing** | Facturas, cobros, Stripe webhooks | CRITICO |
| **Legal** | TicketBAI, VeriFactu, RGPD breaches | CRITICO (regulatorio) |
| **Empleabilidad** | Aplicaciones, entrevistas, matching | ALTO |
| **Andalucia +ei** | Convocatorias, compliance FSE+ | ALTO |
| **Marketing** | Campanas, secuencias, newsletters | MEDIO |

### 10.3 Riesgo Regulatorio

Emails no entregados de tipo legal/regulatorio pueden tener consecuencias:

- **RGPD Art. 33-34**: Notificacion de brechas de datos DEBE realizarse en 72h. Si el email rebota, se incumple.
- **TicketBAI/VeriFactu**: Facturas electronicas no entregadas pueden generar incumplimientos fiscales.
- **FSE+ (Andalucia +ei)**: Comunicaciones de programa obligatorias por regulacion europea.

---

## 11. Arquitectura Actual vs Clase Mundial

### 11.1 Actual (3.5/10)

```
┌────────────────────────────────────────────────────────────┐
│  ARQUITECTURA ACTUAL                                        │
│                                                             │
│  22 modulos → MailManager → Symfony Mailer                  │
│                                  │                          │
│                          smtp_ionos transport               │
│                                  │                          │
│                          smtp.ionos.es:587                  │
│                           (submission)                      │
│                                  │                          │
│                    mout-xforward.kundenserver.de            │
│                      (relay COMPARTIDO, sin DKIM)           │
│                                  │                          │
│                          ¿llegara? ← Spamhaus?             │
│                                                             │
│  Problemas:                                                 │
│  - IP compartida con miles de clientes IONOS                │
│  - Sin DKIM (autenticidad no verificable)                   │
│  - DMARC p=none (sin proteccion anti-spoofing)              │
│  - Sin monitoring (fallos silenciosos)                      │
│  - Sin bounce handling automatico                           │
│  - Sin separacion transaccional/marketing                   │
│  - Sin metricas de deliverability                           │
└────────────────────────────────────────────────────────────┘
```

### 11.2 Clase Mundial (objetivo 10/10)

```
┌────────────────────────────────────────────────────────────┐
│  ARQUITECTURA CLASE MUNDIAL                                 │
│                                                             │
│  ┌─────────────────┐    ┌──────────────────┐               │
│  │ TRANSACCIONAL    │    │ MARKETING         │              │
│  │ (20 modulos)     │    │ (jaraba_email)    │              │
│  └───────┬─────────┘    └────────┬─────────┘               │
│          │                       │                          │
│    Amazon SES API           SendGrid API                    │
│    (eu-central-1)           (ya integrado)                  │
│          │                       │                          │
│    IP dedicada             IP dedicada                      │
│    DKIM firmado            DKIM firmado                     │
│          │                       │                          │
│    SNS Webhooks            SendGrid Webhooks                │
│    (bounce/complaint)      (bounce/complaint)               │
│          │                       │                          │
│    ┌─────┴───────────────────────┴─────┐                   │
│    │     EmailDeliverabilityService     │                   │
│    │  - Metricas unificadas             │                   │
│    │  - Supresion global                │                   │
│    │  - Alertas proactivas              │                   │
│    │  - Dashboard en Insights Hub       │                   │
│    └───────────────────────────────────┘                   │
│                                                             │
│  DNS:                                                       │
│  SPF: include:amazonses.com -all                            │
│  DKIM: amazonses.com selector (2048-bit RSA)                │
│  DMARC: v=DMARC1; p=reject; rua=mailto:dmarc@...           │
│  PTR: 82.223.204.169 → mail.plataformadeecosistemas.com    │
└────────────────────────────────────────────────────────────┘
```

---

## 12. Plan de Remediacion Priorizado

| Prioridad | Accion | Hallazgo | Esfuerzo | Documento |
|:---------:|--------|----------|:--------:|-----------|
| **P0** | Migrar transporte transaccional a Amazon SES | EMAIL-C01 | Alto | Ver Plan de Implementacion |
| **P0** | Configurar DKIM via SES | EMAIL-C02 | Bajo (automatico con SES) | Incluido en Plan |
| **P0** | Crear DMARC propio con rua= | EMAIL-C03 | Bajo | Incluido en Plan |
| **P1** | Configurar PTR del servidor dedicado | EMAIL-C04 | Bajo (ticket IONOS) | Manual |
| **P1** | Cambiar SPF a -all tras migracion | EMAIL-H01 | Bajo | Incluido en Plan |
| **P1** | Implementar monitoring SES + alertas | EMAIL-H02 | Medio | Incluido en Plan |
| **P1** | Automatizar bounce → supresion | EMAIL-H03 | Medio | Incluido en Plan |
| **P2** | Separar canales transaccional/marketing | EMAIL-M01 | Medio | Incluido en Plan |
| **P2** | Habilitar CSS inlining | EMAIL-M02 | Bajo | Config change |
| **P2** | Limpiar system.mail.yml | EMAIL-M03 | Bajo | Config change |
| **P2** | Retry en ci-notify-email.php | EMAIL-M04 | Bajo | Script change |
| **P3** | Log persistente de emails | EMAIL-L01 | Medio | Futuro sprint |
| **P3** | Smoke test email en CI | EMAIL-L02 | Bajo | Futuro sprint |

---

## 13. Nuevas Reglas Propuestas

| Regla | Descripcion | Tipo |
|-------|-------------|------|
| **EMAIL-DEDICATED-IP-001** | Email transaccional del SaaS DEBE usar servicio dedicado con IP propia. NUNCA SMTP compartido de hosting | P0 MUST |
| **EMAIL-DKIM-001** | DKIM DEBE estar configurado para todo dominio emisor. Selector y clave publica en DNS | P0 MUST |
| **EMAIL-DMARC-001** | DMARC propio con `rua=` para reportes. Progresion: none → quarantine → reject en 30 dias | P0 MUST |
| **EMAIL-SPF-HARDFAIL-001** | SPF DEBE usar `-all` (hardfail). `~all` solo durante periodo de transicion (<7 dias) | P1 MUST |
| **EMAIL-MONITORING-001** | Metricas de deliverability (bounce rate, complaint rate) DEBEN monitorearse con alertas automaticas | P1 MUST |
| **EMAIL-BOUNCE-SYNC-001** | Hard bounces DEBEN suprimir automaticamente al destinatario. Soft bounces con retry exponencial (max 3) | P1 MUST |
| **EMAIL-CHANNEL-SPLIT-001** | Email transaccional y marketing DEBEN usar canales separados (diferentes IPs/dominios de envio) | P2 SHOULD |
| **EMAIL-PTR-001** | Servidor dedicado DEBE tener PTR configurado con hostname del dominio principal | P2 SHOULD |

---

## 14. Glosario de Terminos

| Sigla | Significado |
|-------|-------------|
| **SPF** | Sender Policy Framework — registro DNS que lista las IPs autorizadas a enviar email para un dominio |
| **DKIM** | DomainKeys Identified Mail — firma criptografica en cabeceras de email que verifica autenticidad |
| **DMARC** | Domain-based Message Authentication, Reporting & Conformance — politica que indica que hacer con emails que fallan SPF/DKIM |
| **PTR** | Pointer Record — registro DNS inverso que mapea IP a hostname |
| **MX** | Mail Exchanger — registro DNS que indica que servidor recibe email para un dominio |
| **SES** | Simple Email Service — servicio de email transaccional de Amazon Web Services |
| **SNS** | Simple Notification Service — servicio de webhooks/notificaciones de AWS |
| **TLS** | Transport Layer Security — cifrado de la conexion SMTP |
| **STARTTLS** | Comando SMTP para upgrade de conexion sin cifrado a TLS |
| **HMAC** | Hash-based Message Authentication Code — codigo de autenticacion basado en hash |
| **DSN** | Delivery Status Notification — notificacion de estado de entrega de email |
| **Bounce** | Email no entregado. Hard bounce = permanente (direccion no existe). Soft bounce = temporal (buzon lleno) |
| **Complaint** | Destinatario marca email como spam. Impacta reputacion del sender |
| **Blocklist** | Lista de IPs/dominios bloqueados por envio de spam (Spamhaus, Barracuda, etc.) |
| **MJML** | Mailjet Markup Language — lenguaje de templates para email responsive |
| **CAN-SPAM** | Controlling the Assault of Non-Solicited Pornography And Marketing Act (ley USA anti-spam) |
| **RGPD** | Reglamento General de Proteccion de Datos (normativa UE de privacidad) |
| **FSE+** | Fondo Social Europeo Plus — programa de financiacion europea |

---

## 15. Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-03-26 | Claude Opus 4.6 | Creacion inicial tras incidente Spamhaus |
