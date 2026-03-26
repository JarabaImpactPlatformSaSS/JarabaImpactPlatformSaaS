# Plan de Implementacion: Email Transaccional Amazon SES — Clase Mundial

**Version:** 1.0.0
**Fecha:** 2026-03-26
**Autor:** Claude Opus 4.6
**Roles:** Arquitecto SaaS Senior, Ingeniero Software Senior, Ingeniero Drupal Senior, Ingeniero de Seguridad Senior, Ingeniero SEO/GEO Senior
**Estado:** PENDIENTE DE IMPLEMENTACION
**Prerrequisito:** `20260326b-Auditoria_Email_Deliverability_Infraestructura_SMTP_v1_Claude.md`
**Modulo principal:** `jaraba_ses_transport` (nuevo)
**Modulos afectados:** `jaraba_ses_transport`, `jaraba_email`, `ecosistema_jaraba_core`, todos los 22 modulos con `hook_mail()`

---

## Indice de Navegacion (TOC)

1. [Objetivos y Alcance](#1-objetivos-y-alcance)
2. [Principios Arquitectonicos](#2-principios-arquitectonicos)
3. [Pre-Implementacion: Checklist de Directrices](#3-pre-implementacion-checklist-de-directrices)
4. [Infraestructura Existente](#4-infraestructura-existente)
   - 4.1 [Arquitectura Actual de Email](#41-arquitectura-actual-de-email)
   - 4.2 [Symfony Mailer Plugin System](#42-symfony-mailer-plugin-system)
   - 4.3 [SendGrid Parcialmente Integrado](#43-sendgrid-parcialmente-integrado)
   - 4.4 [Modulos Emisores de Email](#44-modulos-emisores-de-email)
5. [Sprint A — P0: Amazon SES + Transporte Drupal + DNS](#5-sprint-a--p0-amazon-ses--transporte-drupal--dns)
   - 5.1 [Cuenta AWS y Configuracion SES](#51-cuenta-aws-y-configuracion-ses)
   - 5.2 [Verificacion de Dominio y DKIM](#52-verificacion-de-dominio-y-dkim)
   - 5.3 [Modulo jaraba_ses_transport](#53-modulo-jaraba_ses_transport)
   - 5.4 [Registros DNS: SPF, DKIM, DMARC](#54-registros-dns-spf-dkim-dmarc)
   - 5.5 [Variables de Entorno y Deploy Pipeline](#55-variables-de-entorno-y-deploy-pipeline)
   - 5.6 [Activacion del Transporte en Produccion](#56-activacion-del-transporte-en-produccion)
   - 5.7 [Salida del Sandbox de SES](#57-salida-del-sandbox-de-ses)
6. [Sprint B — P1: Bounce/Complaint Handling + Monitoring](#6-sprint-b--p1-bouncecomplaints-handling--monitoring)
   - 6.1 [SNS Topics y Suscripciones](#61-sns-topics-y-suscripciones)
   - 6.2 [Webhook Controller para SNS](#62-webhook-controller-para-sns)
   - 6.3 [EmailSuppressionService](#63-emailsuppressionservice)
   - 6.4 [EmailDeliverabilityDashboard](#64-emaildeliverabilitydashboard)
   - 6.5 [Alertas Proactivas](#65-alertas-proactivas)
7. [Sprint C — P2: Separacion de Canales + Hardening](#7-sprint-c--p2-separacion-de-canales--hardening)
   - 7.1 [Canal Transaccional vs Marketing](#71-canal-transaccional-vs-marketing)
   - 7.2 [CSS Inlining para Emails HTML](#72-css-inlining-para-emails-html)
   - 7.3 [Log Persistente de Emails](#73-log-persistente-de-emails)
   - 7.4 [Retry en ci-notify-email.php](#74-retry-en-ci-notify-emailphp)
   - 7.5 [Smoke Test Email en CI](#75-smoke-test-email-en-ci)
8. [Medidas de Salvaguarda](#8-medidas-de-salvaguarda)
9. [Tabla de Correspondencia: Specs a Archivos](#9-tabla-de-correspondencia-specs-a-archivos)
10. [Tabla de Cumplimiento de Directrices](#10-tabla-de-cumplimiento-de-directrices)
11. [Verificacion Post-Implementacion (RUNTIME-VERIFY-001)](#11-verificacion-post-implementacion-runtime-verify-001)
12. [Testing Strategy](#12-testing-strategy)
13. [Variables de Entorno y Secrets](#13-variables-de-entorno-y-secrets)
14. [Rollback Plan](#14-rollback-plan)
15. [Glosario](#15-glosario)

---

## 1. Objetivos y Alcance

### Objetivo

Migrar el email transaccional del SaaS desde IONOS SMTP compartido a **Amazon SES** (Simple Email Service) en la region `eu-central-1` (Frankfurt), eliminando la dependencia de infraestructura compartida y alcanzando el nivel de deliverability clase mundial que un SaaS multi-tenant con facturacion electronica, compliance RGPD y regulacion FSE+ requiere.

La migracion es **transparente** para los 22 modulos que envian email: solo cambia el transporte subyacente en Symfony Mailer, sin modificar una sola linea de codigo en los modulos emisores.

### Alcance

| Dimension | Incluido | Excluido |
|-----------|----------|----------|
| **Transporte** | Amazon SES API (eu-central-1), nuevo modulo transport | Envio directo via Postfix/Sendmail |
| **DNS** | SPF, DKIM (2048-bit), DMARC propio, PTR servidor | BIMI, MTA-STS (futuro) |
| **Monitoring** | Bounce/complaint rate, delivery metrics, alertas | Full observability stack (Grafana/Prometheus) |
| **Bounce handling** | Supresion automatica hard bounce, retry soft bounce | Rehabilitacion manual de suprimidos |
| **Canales** | Transaccional (SES) + Marketing (SendGrid existente) | Unificacion en proveedor unico |
| **Modulos** | Los 22 modulos con hook_mail() + CI notify script | Reescritura de templates MJML |
| **Email campanas** | Mantiene SendGrid para jaraba_email campaigns | Migracion de campanas a SES |

### Dependencias

| Componente | Estado | Ubicacion |
|------------|:------:|-----------|
| Symfony Mailer 7.4.6 | Existente | `vendor/symfony/mailer/` |
| drupal/symfony_mailer 1.6.2 | Existente | `web/modules/contrib/symfony_mailer/` |
| TransportPluginInterface | Existente | `web/modules/contrib/symfony_mailer/src/TransportPluginInterface.php` |
| TransportBase | Existente | `web/modules/contrib/symfony_mailer/src/Plugin/MailerTransport/TransportBase.php` |
| settings.secrets.php | Existente | `config/deploy/settings.secrets.php` |
| deploy.yml | Existente | `.github/workflows/deploy.yml` |
| SendGridClientService | Existente | `web/modules/custom/jaraba_email/src/Service/SendGridClientService.php` |
| EmailWebhookController | Existente | `web/modules/custom/jaraba_email/src/Controller/EmailWebhookController.php` |
| symfony/amazon-mailer | **Nuevo** | `composer require symfony/amazon-mailer` |
| aws/aws-sdk-php | **Nuevo** (transitivo) | Dependencia de symfony/amazon-mailer |
| Cuenta AWS con SES habilitado | **Nuevo** | Configuracion manual en AWS Console |

### Por que Amazon SES y no otro proveedor

| Criterio | Amazon SES | Postmark | SendGrid | Resend |
|----------|:----------:|:--------:|:--------:|:------:|
| **Coste (10k emails/mes)** | ~1 EUR | ~10 EUR | ~15 EUR | Gratis (3k) |
| **Region EU (RGPD)** | eu-central-1 Frankfurt | EU (Irlanda) | EU (Irlanda) | EU |
| **DKIM automatico** | Si (Easy DKIM) | Si | Si | Si |
| **IP dedicada** | Si (24 EUR/mes, opcional) | Incluida (shared pool reputado) | Si (89 EUR/mes) | No |
| **Symfony transport nativo** | Si (`ses+api://`) | Si (`postmark+api://`) | No (custom) | No |
| **Bounce/complaint webhooks** | Si (SNS) | Si (webhooks) | Si (Event Webhook) | Si |
| **Escalabilidad** | Ilimitada | 125k/mes (plan medio) | 100k/mes (plan Pro) | 50k/mes |
| **SLA** | 99.9% | 99.99% | 99.95% | 99.9% |
| **Ya integrado en proyecto** | No | No | Parcialmente | No |

**Decision:** Amazon SES ofrece el mejor balance coste/funcionalidad/escalabilidad para un SaaS multi-tenant. La integracion con Symfony Mailer es nativa (transport `ses+api://`). La region Frankfurt garantiza compliance RGPD. El coste es insignificante (~0.10 EUR/1000 emails). Y si en el futuro necesitamos IP dedicada, SES la ofrece por 24 EUR/mes — 4x mas barato que SendGrid.

---

## 2. Principios Arquitectonicos

| # | Principio | Aplicacion |
|:-:|-----------|------------|
| 1 | **SECRET-MGMT-001** | Credenciales AWS via `getenv()` en `settings.secrets.php`. NUNCA en config/sync/ |
| 2 | **OPTIONAL-CROSSMODULE-001** | `jaraba_ses_transport` es independiente. Modulos emisores NO lo referencian directamente |
| 3 | **PHANTOM-ARG-001** | Args en services.yml coinciden exactamente con constructores PHP |
| 4 | **OPTIONAL-PARAM-ORDER-001** | Parametros opcionales al final del constructor |
| 5 | **LOGGER-INJECT-001** | `@logger.channel.jaraba_ses` en services.yml, `LoggerInterface $logger` en constructor |
| 6 | **UPDATE-HOOK-REQUIRED-001** | Si se crean entities nuevas (EmailSendLog, EmailSuppression), hook_update_N() obligatorio |
| 7 | **UPDATE-HOOK-CATCH-001** | try-catch con `\Throwable` en hooks de update |
| 8 | **AUDIT-CONS-001** | Toda ContentEntity con AccessControlHandler en anotacion |
| 9 | **ENTITY-001** | Entities con EntityOwnerTrait + EntityOwnerInterface + EntityChangedInterface |
| 10 | **TENANT-001** | Toda query filtra por tenant. EmailSuppression es global (cross-tenant) por diseno |
| 11 | **CSRF-API-001** | Webhook SNS NO usa CSRF (es server-to-server). Valida via certificado X.509 de SNS |
| 12 | **PRESAVE-RESILIENCE-001** | Servicios opcionales con hasService() + try-catch |
| 13 | **EMAIL-DEDICATED-IP-001** | Nuevo: transaccional via SES, NUNCA SMTP compartido |
| 14 | **EMAIL-DKIM-001** | Nuevo: DKIM obligatorio en DNS |
| 15 | **EMAIL-DMARC-001** | Nuevo: DMARC propio con reportes |
| 16 | **EMAIL-MONITORING-001** | Nuevo: metricas de deliverability monitoreadas |
| 17 | **EMAIL-BOUNCE-SYNC-001** | Nuevo: bounce → supresion automatica |

---

## 3. Pre-Implementacion: Checklist de Directrices

| Directriz | Verificado | Notas |
|-----------|:----------:|-------|
| `declare(strict_types=1)` en archivos nuevos | Pendiente | ~12 archivos nuevos |
| PHPStan Level 6 | Pendiente | Incluir en phpstan.neon |
| PHPCS Drupal + DrupalPractice | Pendiente | Pre-commit hook |
| No secrets en config/sync | Pendiente | AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY via env |
| Services.yml args match constructor | Pendiente | PHANTOM-ARG-001 |
| Entity con AccessControlHandler | Pendiente | EmailSendLog, EmailSuppression |
| hook_update_N() si nueva entity | Pendiente | UPDATE-HOOK-REQUIRED-001 |
| Tests unitarios + kernel | Pendiente | 4+ test classes |
| CONTROLLER-READONLY-001 | Pendiente | Controllers no usan readonly en props heredadas |
| CSS-VAR-ALL-COLORS-001 | N/A | Sin SCSS nuevo (modulo backend) |
| PREMIUM-FORMS-PATTERN-001 | Pendiente | Si entity forms nuevas, extienden PremiumEntityFormBase |

---

## 4. Infraestructura Existente

### 4.1 Arquitectura Actual de Email

El email del SaaS fluye a traves de la siguiente cadena:

```
22 modulos con hook_mail()
       │
       ▼
MailManagerInterface::mail()
       │
       ▼
Symfony Mailer Module (drupal/symfony_mailer 1.6.2)
  ├── Politica default: from = contacto@plataformadeecosistemas.com
  ├── Theme: ecosistema_jaraba_theme
  └── Transport: smtp_ionos (config entity)
       │
       ▼
smtp.ionos.es:587 (TLS, auth via SMTP_USER/SMTP_PASS env vars)
       │
       ▼
mout-xforward.kundenserver.de (relay compartido, 82.165.159.0/26)
       │
       ▼
Servidor destino (Gmail, Outlook, etc.)
```

**Punto critico de cambio:** Solo el transporte cambia (`smtp_ionos` → `ses`). Todo lo demas (politicas, from address, theme, hook_mail) permanece identico.

### 4.2 Symfony Mailer Plugin System

El modulo `drupal/symfony_mailer` 1.6.2 proporciona:

- **Plugin annotation:** `@MailerTransport` para registrar transportes custom
- **Base class:** `TransportBase` con form builder, DSN generation, config persistence
- **Transport Manager:** `symfony_mailer.transport_manager` con service collector
- **Config entity:** `mailer_transport` para persistir configuracion de cada transporte
- **Transportes existentes:** SmtpTransport, DsnTransport, NativeTransport, SendmailTransport, NullTransport

Un nuevo transporte SES se registra como plugin de Drupal, aparece en la UI de admin, y se configura sin tocar codigo.

### 4.3 SendGrid Parcialmente Integrado

El modulo `jaraba_email` tiene `SendGridClientService` que:

- Envia campanas y secuencias via SendGrid API v3 directamente
- Procesa webhooks de eventos (delivered, open, click, bounce, complaint)
- Valida firmas HMAC-SHA256 de webhooks
- **Limitacion:** Solo se usa para email de marketing. El transaccional va por Symfony Mailer.

**Decision:** Mantener SendGrid para marketing (campanas, secuencias) y usar SES para transaccional. Canales separados = reputacion independiente.

### 4.4 Modulos Emisores de Email

22 modulos implementan `hook_mail()`. Ninguno referencia el transporte directamente — todos usan `MailManagerInterface::mail()`. La migracion es **transparente** para ellos.

Ver lista completa en la auditoria: `20260326b-Auditoria_Email_Deliverability_Infraestructura_SMTP_v1_Claude.md`, seccion 10.1.

---

## 5. Sprint A — P0: Amazon SES + Transporte Drupal + DNS

### 5.1 Cuenta AWS y Configuracion SES

**Acciones manuales (AWS Console):**

1. Crear cuenta AWS (si no existe) o usar cuenta existente
2. Seleccionar region **eu-central-1** (Frankfurt) — obligatorio para RGPD
3. Navegar a **Amazon SES > Verified identities > Create identity**
4. Verificar el dominio `plataformadeecosistemas.com` (tipo: Domain)
5. Habilitar **Easy DKIM** con claves de 2048 bits (opcion RSA_2048_BIT)
6. Habilitar **Custom MAIL FROM domain** como `mail.plataformadeecosistemas.com`
7. Crear usuario IAM dedicado `jaraba-ses-sender` con politica minima:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "JarabaSESSend",
      "Effect": "Allow",
      "Action": [
        "ses:SendEmail",
        "ses:SendRawEmail",
        "ses:GetSendQuota",
        "ses:GetSendStatistics"
      ],
      "Resource": "arn:aws:ses:eu-central-1:*:identity/plataformadeecosistemas.com"
    }
  ]
}
```

8. Generar **Access Key ID** y **Secret Access Key** para el usuario IAM
9. Guardar las credenciales de forma segura (1Password, Vault, etc.)

**Nota de seguridad:** El usuario IAM tiene permisos minimos — solo puede enviar email desde el dominio verificado. No puede leer emails, gestionar identidades ni modificar configuracion de SES.

### 5.2 Verificacion de Dominio y DKIM

Al verificar el dominio en SES, AWS genera automaticamente:

1. **3 registros CNAME para DKIM:**

```
Selector 1: abcdef._domainkey.plataformadeecosistemas.com → abcdef.dkim.amazonses.com
Selector 2: ghijkl._domainkey.plataformadeecosistemas.com → ghijkl.dkim.amazonses.com
Selector 3: mnopqr._domainkey.plataformadeecosistemas.com → mnopqr.dkim.amazonses.com
```

2. **1 registro MX para Custom MAIL FROM:**

```
mail.plataformadeecosistemas.com MX 10 feedback-smtp.eu-central-1.amazonses.com
```

3. **1 registro TXT para SPF del Custom MAIL FROM:**

```
mail.plataformadeecosistemas.com TXT "v=spf1 include:amazonses.com ~all"
```

Estos registros se configuran en el panel DNS de IONOS para el dominio `plataformadeecosistemas.com`.

### 5.3 Modulo jaraba_ses_transport

**Estructura del modulo:**

```
web/modules/custom/jaraba_ses_transport/
├── jaraba_ses_transport.info.yml
├── jaraba_ses_transport.services.yml
├── jaraba_ses_transport.module
├── src/
│   └── Plugin/
│       └── MailerTransport/
│           └── SesTransport.php
└── tests/
    └── src/
        └── Unit/
            └── Plugin/
                └── MailerTransport/
                    └── SesTransportTest.php
```

**jaraba_ses_transport.info.yml:**

```yaml
name: 'Jaraba SES Transport'
type: module
description: 'Amazon SES transport for Symfony Mailer. Provides dedicated email deliverability for the SaaS platform.'
core_version_requirement: ^10.3 || ^11
package: 'Jaraba - Infraestructura'
dependencies:
  - symfony_mailer:symfony_mailer
```

**jaraba_ses_transport.services.yml:**

```yaml
services:
  logger.channel.jaraba_ses:
    parent: logger.channel_base
    arguments: ['jaraba_ses']
```

**src/Plugin/MailerTransport/SesTransport.php:**

Descripcion detallada del plugin:

- **Annotation:** `@MailerTransport(id = "ses", label = @Translation("Amazon SES"), description = @Translation("Amazon Simple Email Service via API"))`
- **Extends:** `TransportBase` (del modulo symfony_mailer)
- **Metodo `defaultConfiguration()`:** Define campos de configuracion con defaults:
  - `region`: string, default `eu-central-1`
  - `access_key`: string, default vacio (viene de env var)
  - `secret_key`: string, default vacio (viene de env var)
- **Metodo `buildConfigurationForm()`:** Genera formulario de admin con:
  - Select de region AWS (eu-central-1, eu-west-1, eu-west-2, us-east-1, us-west-2)
  - Campo access_key (tipo password, con placeholder "Set via AWS_SES_ACCESS_KEY env var")
  - Campo secret_key (tipo password, con placeholder "Set via AWS_SES_SECRET_KEY env var")
  - Todos los labels y descriptions traducibles con `$this->t()`
- **Metodo `getDsn()`:** Genera el DSN de Symfony: `ses+api://{access_key}:{secret_key}@default?region={region}`

**Nota critica sobre credenciales:** Las credenciales NUNCA se almacenan en la config entity. El formulario muestra placeholders indicando que vienen de env vars. La resolucion real ocurre en `settings.secrets.php` (ver seccion 5.5).

**jaraba_ses_transport.module:**

```php
<?php

declare(strict_types=1);

/**
 * @file
 * Amazon SES transport for Symfony Mailer.
 */
```

Archivo minimo. No se necesitan hooks procedurales para un modulo de transporte.

### 5.4 Registros DNS: SPF, DKIM, DMARC

**Cambios DNS en el panel de IONOS para `plataformadeecosistemas.com`:**

| Tipo | Host | Valor | Prioridad | Notas |
|------|------|-------|:---------:|-------|
| TXT | @ | `v=spf1 include:amazonses.com -all` | - | Reemplaza el SPF actual. `-all` (hardfail) |
| CNAME | `abc._domainkey` | `abc.dkim.amazonses.com` | - | DKIM selector 1 (valor real de AWS) |
| CNAME | `def._domainkey` | `def.dkim.amazonses.com` | - | DKIM selector 2 (valor real de AWS) |
| CNAME | `ghi._domainkey` | `ghi.dkim.amazonses.com` | - | DKIM selector 3 (valor real de AWS) |
| MX | mail | `feedback-smtp.eu-central-1.amazonses.com` | 10 | Custom MAIL FROM |
| TXT | mail | `v=spf1 include:amazonses.com ~all` | - | SPF para subdomain mail |
| TXT | `_dmarc` | `v=DMARC1; p=quarantine; rua=mailto:dmarc-reports@plataformadeecosistemas.com; ruf=mailto:dmarc-forensic@plataformadeecosistemas.com; pct=100; adkim=r; aspf=r` | - | DMARC propio (reemplaza CNAME a IONOS) |

**Eliminaciones DNS:**

| Tipo | Host | Valor a eliminar | Razon |
|------|------|------------------|-------|
| CNAME | `_dmarc` | `dmarc.ionos.es` | Sustituido por TXT propio |
| TXT | @ | `v=spf1 include:_spf-eu.ionos.com ~all` | Sustituido por SES |

**Nota sobre SPF:** Si durante la transicion se necesita mantener IONOS como fallback temporal:

```
v=spf1 include:amazonses.com include:_spf-eu.ionos.com ~all
```

Cambiar a `-all` y eliminar IONOS cuando se confirme que todo funciona via SES.

**Progresion DMARC recomendada:**

| Semana | Politica | Descripcion |
|:------:|----------|-------------|
| 1 | `p=none; rua=...` | Solo monitoreo, recibir reportes |
| 2 | `p=quarantine; pct=50; rua=...` | 50% de fallos van a spam |
| 3 | `p=quarantine; pct=100; rua=...` | 100% de fallos van a spam |
| 4+ | `p=reject; rua=...` | Rechazar emails que fallen autenticacion |

### 5.5 Variables de Entorno y Deploy Pipeline

**Nuevas variables de entorno:**

| Variable | Valor | Donde |
|----------|-------|-------|
| `AWS_SES_ACCESS_KEY` | Access Key ID del usuario IAM | GitHub Secrets |
| `AWS_SES_SECRET_KEY` | Secret Access Key del usuario IAM | GitHub Secrets |
| `AWS_SES_REGION` | `eu-central-1` | GitHub Secrets (o hardcoded) |

**Cambio en `config/deploy/settings.secrets.php`:**

Anadir debajo de la seccion EMAIL existente:

```php
// ============================================================================
// EMAIL — Amazon SES Transport (EMAIL-DEDICATED-IP-001)
// ============================================================================

if ($ses_access = getenv('AWS_SES_ACCESS_KEY')) {
  $config['symfony_mailer.mailer_transport.ses']['configuration']['access_key'] = $ses_access;
}
if ($ses_secret = getenv('AWS_SES_SECRET_KEY')) {
  $config['symfony_mailer.mailer_transport.ses']['configuration']['secret_key'] = $ses_secret;
}
if ($ses_region = getenv('AWS_SES_REGION')) {
  $config['symfony_mailer.mailer_transport.ses']['configuration']['region'] = $ses_region;
}
```

**Cambio en `.github/workflows/deploy.yml`:**

Anadir en la seccion de secrets (junto a los SMTP existentes):

```yaml
KEY_AWS_SES_ACCESS_KEY: ${{ secrets.AWS_SES_ACCESS_KEY }}
KEY_AWS_SES_SECRET_KEY: ${{ secrets.AWS_SES_SECRET_KEY }}
KEY_AWS_SES_REGION: ${{ secrets.AWS_SES_REGION }}
```

Y en la generacion de `settings.env.php`:

```bash
# Amazon SES
[ -n "${KEY_AWS_SES_ACCESS_KEY}" ] && echo "putenv('AWS_SES_ACCESS_KEY=${KEY_AWS_SES_ACCESS_KEY}');"
[ -n "${KEY_AWS_SES_SECRET_KEY}" ] && echo "putenv('AWS_SES_SECRET_KEY=${KEY_AWS_SES_SECRET_KEY}');"
[ -n "${KEY_AWS_SES_REGION}" ] && echo "putenv('AWS_SES_REGION=${KEY_AWS_SES_REGION}');"
```

### 5.6 Activacion del Transporte en Produccion

**Config entity para el transporte SES:**

Archivo: `config/install/symfony_mailer.mailer_transport.ses.yml`

```yaml
langcode: es
status: true
dependencies:
  module:
    - jaraba_ses_transport
id: ses
label: 'Amazon SES'
plugin: ses
configuration:
  access_key: ''
  secret_key: ''
  region: 'eu-central-1'
```

**Cambio en configuracion del default transport:**

Archivo: `config/sync/symfony_mailer.settings.yml`

```yaml
default_transport: ses
```

Esto redirige **todo el email del SaaS** (los 22 modulos) a traves de Amazon SES. No se toca ni una linea de codigo en los modulos emisores.

**Mantener smtp_ionos como fallback:** La config entity `smtp_ionos` se conserva (no se elimina). Si SES tiene un outage, se puede reactivar temporalmente cambiando `default_transport: smtp_ionos` via Drush:

```bash
drush config:set symfony_mailer.settings default_transport smtp_ionos -y
```

### 5.7 Salida del Sandbox de SES

AWS SES empieza en modo **sandbox**: solo puede enviar a direcciones verificadas. Para produccion se debe solicitar salida:

1. AWS Console → SES → Account dashboard → "Request production access"
2. Completar formulario:
   - **Mail type:** Transactional
   - **Website URL:** `https://plataformadeecosistemas.com`
   - **Use case description:** "Multi-tenant SaaS platform sending transactional emails: account activation, password reset, billing invoices, legal notifications (GDPR), electronic invoicing (TicketBAI/VeriFactu). Expected volume: 5,000-20,000 emails/month. All recipients are registered users or business contacts."
   - **Compliance:** Describe bounce/complaint handling, unsubscribe mechanism, consent management
3. AWS tipicamente responde en 24-48h
4. Una vez aprobado, el limite inicial es ~50,000 emails/dia (escalable bajo solicitud)

**Mientras se espera aprobacion:** Verificar direcciones de prueba individuales y ejecutar tests completos del pipeline.

---

## 6. Sprint B — P1: Bounce/Complaint Handling + Monitoring

### 6.1 SNS Topics y Suscripciones

**Configuracion en AWS Console:**

1. Crear **SNS Topic** `jaraba-ses-notifications` en eu-central-1
2. En SES → Configuration sets → crear `jaraba-production`
3. En el configuration set, anadir **event destinations:**
   - Bounce → SNS topic `jaraba-ses-notifications`
   - Complaint → SNS topic `jaraba-ses-notifications`
   - Delivery → SNS topic `jaraba-ses-notifications` (para metricas)
   - Reject → SNS topic `jaraba-ses-notifications`
4. Crear **SNS Subscription** tipo HTTPS:
   - Endpoint: `https://plataformadeecosistemas.com/api/v1/webhooks/ses`
   - Protocolo: HTTPS

**El configuration set se asocia al transporte SES** anadiendo el header `X-SES-CONFIGURATION-SET: jaraba-production` en el transport plugin.

### 6.2 Webhook Controller para SNS

**Archivo:** `web/modules/custom/jaraba_ses_transport/src/Controller/SesWebhookController.php`

Descripcion del controller:

- **Ruta:** `POST /api/v1/webhooks/ses` (publica, sin CSRF — es server-to-server)
- **Routing requirements:** `_access: 'TRUE'` + validacion de certificado SNS
- **Validacion:** Verifica la firma X.509 del mensaje SNS (Amazon firma todos los mensajes con su certificado)
- **Flujo:**
  1. Parsear JSON body del request
  2. Si `Type == SubscriptionConfirmation`: confirmar suscripcion visitando `SubscribeURL`
  3. Si `Type == Notification`: parsear `Message` JSON
  4. Despachar al servicio correspondiente segun `notificationType`:
     - `Bounce` → `EmailSuppressionService::handleBounce()`
     - `Complaint` → `EmailSuppressionService::handleComplaint()`
     - `Delivery` → `EmailDeliverabilityService::recordDelivery()`
     - `Reject` → Log warning

**Validacion de certificado SNS:**

```php
// Descargar certificado de SigningCertURL (solo dominios *.amazonaws.com)
// Verificar firma con openssl_verify() y la clave publica del certificado
// Rechazar si la firma no coincide
```

**Seguridad adicional:**

- Validar que `SigningCertURL` pertenece a `*.amazonaws.com` (prevenir SSRF)
- Validar que `TopicArn` coincide con el configurado
- Rate limit en la ruta (Drupal flood control, 100 requests/minuto)

### 6.3 EmailSuppressionService

**Archivo:** `web/modules/custom/jaraba_ses_transport/src/Service/EmailSuppressionService.php`

Este servicio gestiona la lista de supresion global del SaaS:

**Responsabilidades:**

1. **Procesar hard bounces:** Marcar email como suprimido permanentemente
2. **Procesar soft bounces:** Incrementar contador, suprimir tras 3 intentos en 72h
3. **Procesar complaints:** Marcar email como suprimido + alertar equipo
4. **Consultar supresion:** Antes de enviar, verificar si email esta suprimido
5. **Sincronizar con EmailSubscriber:** Actualizar status en entity de jaraba_email

**Tabla de base de datos (schema, NO entity):**

```php
// hook_schema() en jaraba_ses_transport.install
'jaraba_email_suppression' => [
  'description' => 'Global email suppression list',
  'fields' => [
    'email' => ['type' => 'varchar', 'length' => 254, 'not null' => TRUE],
    'reason' => ['type' => 'varchar', 'length' => 32, 'not null' => TRUE],
    'bounce_type' => ['type' => 'varchar', 'length' => 32],
    'bounce_subtype' => ['type' => 'varchar', 'length' => 64],
    'diagnostic_code' => ['type' => 'varchar', 'length' => 512],
    'suppressed_at' => ['type' => 'int', 'not null' => TRUE],
    'soft_bounce_count' => ['type' => 'int', 'default' => 0],
    'last_soft_bounce' => ['type' => 'int', 'default' => 0],
  ],
  'primary key' => ['email'],
  'indexes' => [
    'reason' => ['reason'],
    'suppressed_at' => ['suppressed_at'],
  ],
],
```

**Razon de usar tabla directa en lugar de entity:** Alto volumen (potencialmente miles de entries), consultas frecuentes (antes de cada envio), no necesita Field UI ni Views ni revisions. Similar al patron de `copilot_funnel_event` (COPILOT-FUNNEL-TRACKING-001).

**Integracion con el envio:** El modulo registra un event subscriber que intercepta el evento `MessageEvent` de Symfony Mailer y verifica la lista de supresion **antes** de enviar. Si el destinatario esta suprimido, cancela el envio y registra un log.

### 6.4 EmailDeliverabilityDashboard

**Integracion en Insights Hub:**

Dado que `jaraba_insights_hub` ya tiene dashboards de SEO, el dashboard de deliverability se anade como una nueva tab.

**Archivo:** `web/modules/custom/jaraba_ses_transport/src/Controller/DeliverabilityDashboardController.php`

**Ruta:** `/admin/config/services/email-deliverability`

**Metricas mostradas:**

| Metrica | Fuente | Umbral alerta |
|---------|--------|:-------------:|
| Delivery rate | Conteo deliveries / total enviados | < 95% |
| Bounce rate | Hard bounces / total enviados | > 5% |
| Complaint rate | Complaints / total enviados | > 0.1% |
| Suppression list size | COUNT jaraba_email_suppression | > 500 |
| Emails enviados hoy | Conteo deliveries ultimas 24h | Informativo |

**Tabla de metricas diarias (schema):**

```php
'jaraba_ses_daily_metrics' => [
  'fields' => [
    'date' => ['type' => 'varchar', 'length' => 10, 'not null' => TRUE],
    'sent' => ['type' => 'int', 'default' => 0],
    'delivered' => ['type' => 'int', 'default' => 0],
    'bounced' => ['type' => 'int', 'default' => 0],
    'complained' => ['type' => 'int', 'default' => 0],
    'rejected' => ['type' => 'int', 'default' => 0],
  ],
  'primary key' => ['date'],
],
```

### 6.5 Alertas Proactivas

**EmailDeliverabilityAlertService:**

- Ejecuta via `hook_cron()` una vez al dia
- Comprueba metricas del dia anterior
- Si bounce rate > 5% O complaint rate > 0.1%: envia alerta
- Alerta via el mismo sistema de email (SES) + log critico en watchdog
- **Patron:** Similar a `STATUS-REPORT-PROACTIVE-001` del safeguard system

**Nota:** Amazon SES suspende automaticamente cuentas con bounce rate > 10% o complaint rate > 0.5%. Las alertas proactivas del SaaS detectan el problema ANTES de que SES actue.

---

## 7. Sprint C — P2: Separacion de Canales + Hardening

### 7.1 Canal Transaccional vs Marketing

**Arquitectura de canales separados:**

| Canal | Proveedor | Uso | Sender |
|-------|-----------|-----|--------|
| **Transaccional** | Amazon SES | Registro, billing, legal, workflows, notificaciones | `contacto@plataformadeecosistemas.com` |
| **Marketing** | SendGrid (existente) | Campanas, secuencias, newsletters via `jaraba_email` | `noreply@plataformadeecosistemas.com` (o por tenant) |

El modulo `jaraba_email` ya usa `SendGridClientService` directamente para campanas. Este canal no se modifica. Solo se asegura que los emails de marketing **no** pasen por SES.

**Beneficio:** Si un pico de campanas de marketing genera complaints, no afecta la reputacion del canal transaccional (y viceversa).

### 7.2 CSS Inlining para Emails HTML

**Cambio en config:**

Archivo: `config/sync/symfony_mailer.mailer_policy._.yml`

Habilitar CSS inlining en la politica default para que los emails se rendericen correctamente en Gmail y Outlook:

```yaml
mailer_inline_css:
  enabled: true
```

**Alternativa si la opcion no esta disponible en el plugin:** Usar el paquete `pelago/emogrifier` que Drupal incluye como dependencia.

### 7.3 Log Persistente de Emails

**Tabla `jaraba_ses_send_log`:**

Para auditoria y debugging, registrar cada email enviado:

```php
'jaraba_ses_send_log' => [
  'fields' => [
    'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
    'message_id' => ['type' => 'varchar', 'length' => 128, 'not null' => TRUE],
    'recipient' => ['type' => 'varchar', 'length' => 254, 'not null' => TRUE],
    'sender' => ['type' => 'varchar', 'length' => 254, 'not null' => TRUE],
    'subject' => ['type' => 'varchar', 'length' => 512],
    'module' => ['type' => 'varchar', 'length' => 64],
    'mail_key' => ['type' => 'varchar', 'length' => 128],
    'status' => ['type' => 'varchar', 'length' => 32, 'default' => 'sent'],
    'created' => ['type' => 'int', 'not null' => TRUE],
    'delivery_status' => ['type' => 'varchar', 'length' => 32],
    'delivery_timestamp' => ['type' => 'int'],
    'bounce_type' => ['type' => 'varchar', 'length' => 32],
    'error_message' => ['type' => 'text', 'size' => 'medium'],
  ],
  'primary key' => ['id'],
  'unique keys' => ['message_id' => ['message_id']],
  'indexes' => [
    'recipient' => ['recipient'],
    'created' => ['created'],
    'status' => ['status'],
    'module_key' => ['module', 'mail_key'],
  ],
],
```

**Retencion:** Cron limpia registros > 90 dias. Configurable via `jaraba_ses_transport.settings`.

### 7.4 Retry en ci-notify-email.php

**Cambio en `scripts/ci-notify-email.php`:**

Anadir retry con backoff exponencial (3 intentos, 2s/4s/8s). Si el transporte SES falla, intentar via SMTP IONOS como fallback:

```php
$transports = ['ses', 'smtp_ionos'];
foreach ($transports as $transportId) {
  for ($attempt = 1; $attempt <= 3; $attempt++) {
    try {
      $transport = Transport::fromDsn($dsn[$transportId]);
      $mailer = new Mailer($transport);
      $mailer->send($email);
      exit(0); // exito
    } catch (\Exception $e) {
      sleep(pow(2, $attempt)); // 2, 4, 8 segundos
    }
  }
}
```

### 7.5 Smoke Test Email en CI

**Nuevo step en `.github/workflows/ci.yml`:**

Tras el deploy a staging, enviar un email de test y verificar que no genera error:

```yaml
- name: Email smoke test
  run: |
    ssh -p 2222 ${{ secrets.DEPLOY_HOST }} \
      "cd /var/www/jaraba && php vendor/bin/drush eval \
      \"\\Drupal::service('plugin.manager.mail')->mail('system', 'test', 'test@verified-address.com', 'es', ['body' => 'CI smoke test']);\""
```

Solo en entorno staging, no en produccion.

---

## 8. Medidas de Salvaguarda

### 8.1 Validators Nuevos

| Validator | Archivo | Tipo | Descripcion |
|-----------|---------|------|-------------|
| `validate-email-transport.php` | `scripts/validation/` | `run_check` | Verifica que `default_transport` en `symfony_mailer.settings.yml` NO sea `smtp_ionos` (debe ser `ses`) |
| `validate-email-dns.php` | `scripts/validation/` | `warn_check` | Verifica SPF, DKIM, DMARC via DNS lookup (requiere red) |
| `validate-email-secrets.php` | `scripts/validation/` | `run_check` | Verifica que `AWS_SES_ACCESS_KEY` y `AWS_SES_SECRET_KEY` estan en settings.secrets.php |

### 8.2 Monitoring Proactivo

| Metrica | Umbral | Accion |
|---------|:------:|--------|
| Bounce rate > 5% | WARNING | Email a admin + log critico |
| Bounce rate > 8% | CRITICO | Email urgente + considerar pausa de envios |
| Complaint rate > 0.1% | WARNING | Email a admin |
| Complaint rate > 0.3% | CRITICO | Email urgente (SES suspende a 0.5%) |
| Deliveries = 0 en 24h | WARNING | Posible outage de SES o configuracion rota |

### 8.3 Rollback Instantaneo

Si SES presenta problemas, rollback en 30 segundos:

```bash
# Revertir a SMTP IONOS
drush config:set symfony_mailer.settings default_transport smtp_ionos -y
drush cr

# Verificar
drush config:get symfony_mailer.settings default_transport
```

La config entity `smtp_ionos` se mantiene siempre disponible como fallback.

### 8.4 Dual-Send Test Previo

Antes de activar SES en produccion, ejecutar durante 48h en modo dual:

1. Email transaccional → SES (default)
2. Email de test diario → SMTP IONOS (via Drush)

Verificar que todos los emails de SES llegan correctamente antes de eliminar IONOS del SPF.

---

## 9. Tabla de Correspondencia: Specs a Archivos

| Especificacion | Archivo(s) | Sprint |
|----------------|------------|:------:|
| Transport plugin SES | `web/modules/custom/jaraba_ses_transport/src/Plugin/MailerTransport/SesTransport.php` | A |
| Module info | `web/modules/custom/jaraba_ses_transport/jaraba_ses_transport.info.yml` | A |
| Module services | `web/modules/custom/jaraba_ses_transport/jaraba_ses_transport.services.yml` | A |
| Config entity transport | `config/install/symfony_mailer.mailer_transport.ses.yml` | A |
| Default transport switch | `config/sync/symfony_mailer.settings.yml` | A |
| Secrets SES | `config/deploy/settings.secrets.php` (editar) | A |
| Deploy pipeline | `.github/workflows/deploy.yml` (editar) | A |
| Composer dependency | `composer.json` → `symfony/amazon-mailer` | A |
| SNS Webhook controller | `web/modules/custom/jaraba_ses_transport/src/Controller/SesWebhookController.php` | B |
| Webhook routing | `web/modules/custom/jaraba_ses_transport/jaraba_ses_transport.routing.yml` | B |
| Suppression service | `web/modules/custom/jaraba_ses_transport/src/Service/EmailSuppressionService.php` | B |
| Suppression schema | `web/modules/custom/jaraba_ses_transport/jaraba_ses_transport.install` | B |
| Daily metrics schema | `web/modules/custom/jaraba_ses_transport/jaraba_ses_transport.install` | B |
| Deliverability dashboard | `web/modules/custom/jaraba_ses_transport/src/Controller/DeliverabilityDashboardController.php` | B |
| Alert service | `web/modules/custom/jaraba_ses_transport/src/Service/EmailDeliverabilityAlertService.php` | B |
| Send log schema | `web/modules/custom/jaraba_ses_transport/jaraba_ses_transport.install` | C |
| Send log event subscriber | `web/modules/custom/jaraba_ses_transport/src/EventSubscriber/EmailSendLogSubscriber.php` | C |
| CI notify retry | `scripts/ci-notify-email.php` (editar) | C |
| Validator transport | `scripts/validation/validate-email-transport.php` | C |
| Validator DNS | `scripts/validation/validate-email-dns.php` | C |
| Validator secrets | `scripts/validation/validate-email-secrets.php` | C |
| Unit tests | `web/modules/custom/jaraba_ses_transport/tests/src/Unit/` | A-C |

---

## 10. Tabla de Cumplimiento de Directrices

| Directriz | Cumplimiento | Notas |
|-----------|:------------:|-------|
| SECRET-MGMT-001 | Si | AWS credentials via getenv() en settings.secrets.php |
| OPTIONAL-CROSSMODULE-001 | Si | Modulo standalone, sin dependencias cross-modulo |
| PHANTOM-ARG-001 | Si | services.yml args exactos con constructores |
| OPTIONAL-PARAM-ORDER-001 | Si | Params opcionales al final |
| LOGGER-INJECT-001 | Si | @logger.channel.jaraba_ses, LoggerInterface en constructor |
| CONTROLLER-READONLY-001 | Si | Controllers sin readonly en props heredadas |
| CSRF-API-001 | N/A | Webhook SNS valida via X.509, no CSRF |
| AUDIT-SEC-001 | Si | SNS webhook con verificacion de firma criptografica |
| TENANT-001 | Parcial | Supresion es global cross-tenant (por diseno de deliverability) |
| UPDATE-HOOK-REQUIRED-001 | Si | hook_update_N() para tablas nuevas si modulo ya instalado |
| UPDATE-HOOK-CATCH-001 | Si | \Throwable en try-catch |
| PRESAVE-RESILIENCE-001 | Si | Servicios opcionales con try-catch |
| EMAIL-DEDICATED-IP-001 | Si | SES con IP compartida reputada (o dedicada opcional) |
| EMAIL-DKIM-001 | Si | Easy DKIM 2048-bit via SES |
| EMAIL-DMARC-001 | Si | DMARC propio con rua + ruf |
| EMAIL-SPF-HARDFAIL-001 | Si | SPF -all tras periodo de transicion |
| EMAIL-MONITORING-001 | Si | Dashboard + alertas proactivas diarias |
| EMAIL-BOUNCE-SYNC-001 | Si | Hard bounce → supresion, soft bounce → retry 3x |
| PHPCS Drupal/DrupalPractice | Pendiente | Pre-commit hook |
| PHPStan Level 6 | Pendiente | Incluir modulo en phpstan.neon |
| declare(strict_types=1) | Si | En todos los archivos PHP nuevos |

---

## 11. Verificacion Post-Implementacion (RUNTIME-VERIFY-001)

### Sprint A — Verificacion Minima

| Check | Comando / Accion | Esperado |
|-------|------------------|----------|
| Modulo habilitado | `drush pm:list --filter=jaraba_ses_transport` | Enabled |
| Transport registrado | `drush config:get symfony_mailer.settings default_transport` | `ses` |
| Composer dependency | `composer show symfony/amazon-mailer` | Instalado |
| DNS SPF | `dig +short TXT plataformadeecosistemas.com` | Incluye `amazonses.com` |
| DNS DKIM | `dig +short CNAME *._domainkey.plataformadeecosistemas.com` | 3 CNAMEs a amazonses.com |
| DNS DMARC | `dig +short TXT _dmarc.plataformadeecosistemas.com` | `v=DMARC1; p=quarantine...` |
| SES identity verified | AWS Console → SES → Verified identities | Status: Verified |
| SES production access | AWS Console → SES → Account dashboard | Production, no sandbox |
| Email test desde app | Enviar test email a Gmail/Outlook | Recibido, headers con DKIM pass |
| Email headers | `Received:` muestra `amazonses.com` | No `kundenserver.de` |

### Sprint B — Verificacion Completa

| Check | Comando / Accion | Esperado |
|-------|------------------|----------|
| Webhook SNS accesible | `curl -I https://plataformadeecosistemas.com/api/v1/webhooks/ses` | 200 o 405 |
| SNS subscription confirmed | AWS Console → SNS → Subscriptions | Confirmed |
| Bounce test | Enviar a `bounce@simulator.amazonses.com` | Supresion registrada |
| Complaint test | Enviar a `complaint@simulator.amazonses.com` | Complaint registrado |
| Delivery test | Enviar a `success@simulator.amazonses.com` | Delivery registrado |
| Metricas dashboard | `/admin/config/services/email-deliverability` | Muestra datos |
| Tablas creadas | `drush sqlq "SHOW TABLES LIKE 'jaraba_ses%'"` | 3 tablas |

---

## 12. Testing Strategy

### Unit Tests

| Test Class | Archivo | Cobertura |
|------------|---------|-----------|
| `SesTransportTest` | `tests/src/Unit/Plugin/MailerTransport/SesTransportTest.php` | DSN generation, config defaults, region validation |
| `EmailSuppressionServiceTest` | `tests/src/Unit/Service/EmailSuppressionServiceTest.php` | Hard bounce logic, soft bounce counting, complaint handling |
| `SnsSignatureValidatorTest` | `tests/src/Unit/Validator/SnsSignatureValidatorTest.php` | Firma valida, firma invalida, URL spoofing, cert domain check |

### Kernel Tests

| Test Class | Archivo | Cobertura |
|------------|---------|-----------|
| `EmailSuppressionSchemaTest` | `tests/src/Kernel/EmailSuppressionSchemaTest.php` | Tabla creada, CRUD operations, indice funcional |
| `SesWebhookIntegrationTest` | `tests/src/Kernel/Controller/SesWebhookIntegrationTest.php` | Subscription confirmation, bounce processing, complaint processing |

### Manual Tests (Pre-produccion)

| Test | Procedimiento | Criterio de exito |
|------|---------------|-------------------|
| Email desde app | Registrar usuario nuevo | Email de activacion recibido con DKIM pass |
| Bounce handling | Enviar a SES bounce simulator | Email suprimido en tabla, log registrado |
| Complaint handling | Enviar a SES complaint simulator | Email suprimido, alerta generada |
| Rollback | Cambiar transport a smtp_ionos | Email sale por IONOS correctamente |
| Multi-dominio | Enviar desde pepejaraba.es | Email sale con from correcto |
| DMARC report | Esperar 24h | Reporte agregado recibido en buzon rua |

---

## 13. Variables de Entorno y Secrets

| Variable | Proposito | Donde se configura | Donde se consume |
|----------|-----------|-------------------|-----------------|
| `AWS_SES_ACCESS_KEY` | IAM Access Key ID para SES | GitHub Secrets | settings.secrets.php → config override |
| `AWS_SES_SECRET_KEY` | IAM Secret Access Key para SES | GitHub Secrets | settings.secrets.php → config override |
| `AWS_SES_REGION` | Region AWS (eu-central-1) | GitHub Secrets | settings.secrets.php → config override |
| `SMTP_USER` | IONOS SMTP user (fallback) | GitHub Secrets | settings.secrets.php (existente) |
| `SMTP_PASS` | IONOS SMTP password (fallback) | GitHub Secrets | settings.secrets.php (existente) |
| `SMTP_HOST` | IONOS SMTP host (fallback) | GitHub Secrets | settings.secrets.php (existente) |

**Las variables SMTP_* de IONOS se mantienen** como fallback. No se eliminan hasta que SES haya demostrado estabilidad durante al menos 30 dias.

---

## 14. Rollback Plan

### Nivel 1 — Rollback rapido (30 segundos)

Si SES falla en produccion:

```bash
ssh -p 2222 82.223.204.169
cd /var/www/jaraba
sudo -u www-data vendor/bin/drush config:set symfony_mailer.settings default_transport smtp_ionos -y
sudo -u www-data vendor/bin/drush cr
```

Esto revierte inmediatamente a IONOS SMTP. Los emails pendientes en cola se enviaran via IONOS.

### Nivel 2 — Rollback DNS (1-24 horas)

Si se necesita revertir DNS completamente:

1. Restaurar SPF: `v=spf1 include:_spf-eu.ionos.com ~all`
2. Eliminar CNAMEs de DKIM de SES
3. Restaurar DMARC CNAME a `dmarc.ionos.es`

**TTL recomendado durante transicion:** 300 segundos (5 min) para propagacion rapida.

### Nivel 3 — Rollback completo (deploy)

Si se necesita desinstalar el modulo:

```bash
drush pm:uninstall jaraba_ses_transport -y
composer remove symfony/amazon-mailer
drush cr
```

---

## 15. Glosario

| Sigla | Significado |
|-------|-------------|
| **SES** | Simple Email Service — servicio de email transaccional de Amazon Web Services |
| **SNS** | Simple Notification Service — servicio de webhooks/notificaciones de AWS para recibir eventos de SES |
| **IAM** | Identity and Access Management — sistema de permisos de AWS |
| **DKIM** | DomainKeys Identified Mail — firma criptografica RSA 2048-bit en cabeceras de email |
| **DMARC** | Domain-based Message Authentication, Reporting & Conformance — politica DNS para emails que fallan autenticacion |
| **SPF** | Sender Policy Framework — registro DNS que lista IPs autorizadas para enviar email del dominio |
| **PTR** | Pointer Record — DNS inverso que mapea IP a hostname |
| **DSN** | Data Source Name — formato de Symfony para configurar transportes (`ses+api://key:secret@default?region=X`) |
| **MAIL FROM** | Subdominio usado como "envelope sender" en la comunicacion SMTP (diferente del header From) |
| **Easy DKIM** | Funcionalidad de SES que gestiona automaticamente la rotacion de claves DKIM |
| **Sandbox** | Modo inicial de SES donde solo se puede enviar a direcciones verificadas |
| **Bounce** | Email no entregado. Hard = permanente (direccion inexistente). Soft = temporal (buzon lleno) |
| **Complaint** | Destinatario marca email como spam. SES registra via feedback loop con ISPs |
| **Suppression list** | Lista de emails a los que no se debe enviar (bounced, complained) |
| **Configuration set** | Agrupacion de SES que asocia eventos a destinos de notificacion |
| **X.509** | Estandar de certificados digitales. SNS firma mensajes con certificado X.509 verificable |
| **RGPD** | Reglamento General de Proteccion de Datos — normativa europea de privacidad |
| **TicketBAI** | Sistema de facturacion electronica del Pais Vasco |
| **VeriFactu** | Sistema de verificacion de facturas de la AEAT (Agencia Tributaria espanola) |
| **FSE+** | Fondo Social Europeo Plus — programa de financiacion que regula Andalucia +ei |
| **SSRF** | Server-Side Request Forgery — ataque donde se manipula al servidor para hacer peticiones a URLs arbitrarias |
| **HMAC** | Hash-based Message Authentication Code — verificacion de integridad y autenticidad de mensajes |
| **ISP** | Internet Service Provider — en contexto email, se refiere a proveedores de buzon (Gmail, Outlook, Yahoo) |
| **TTL** | Time To Live — duracion de cache de un registro DNS |

---

## Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-03-26 | Claude Opus 4.6 | Creacion inicial con 3 sprints |
