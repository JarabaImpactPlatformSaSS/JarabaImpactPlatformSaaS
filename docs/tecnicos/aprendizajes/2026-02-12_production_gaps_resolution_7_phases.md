# Resolución Sistemática de Gaps Críticos para Producción — 7 Fases

**Fecha:** 2026-02-12
**Contexto:** Cierre de gaps bloqueantes para lanzamiento a producción
**Impacto:** Alto — Infraestructura operacional completa

---

## Resumen

Auditoría de 27 especificaciones (20260118) reveló gaps críticos que bloqueaban el lanzamiento. La plataforma tenía fundamentos sólidos (51 módulos, 227 tests, AI trilogy completa), pero carecía de contenido de skills verticales, observabilidad, procedimientos operacionales, catálogo de precios Stripe y templates email. Se implementaron 7 fases de forma paralela maximizando throughput.

---

## Fase 1: 30 Skills Verticales AI (Doc 129 Anexo A)

**Fichero:** `scripts/seed_vertical_skills.php` (1,647 líneas)

### Aprendizaje: Contenido Experto > Contenido Genérico

Los skills AI deben contener conocimiento de dominio específico del mercado español, no instrucciones genéricas. Cada skill incluye estructura Markdown completa:

```
## Propósito → ## Input Esperado → ## Proceso → ## Output Esperado → ## Restricciones → ## Ejemplos → ## Validación
```

### Distribución por Vertical

| Vertical | Skills | IDs |
|----------|--------|-----|
| empleabilidad | 7 | cv_optimization, interview_preparation, salary_negotiation, linkedin_optimization, cover_letter_writing, job_search_strategy, skill_gap_analysis |
| emprendimiento | 7 | canvas_coaching, pitch_deck_review, financial_projection, competitive_analysis, mvp_validation, pricing_strategy, go_to_market |
| agroconecta | 6 | product_listing_agro, seasonal_marketing, traceability_story, quality_certification, recipe_content, b2b_proposal |
| comercioconecta | 5 | flash_offer_design, local_seo_content, customer_retention, inventory_alert, review_response |
| serviciosconecta | 5 | case_summarization, client_communication, document_generation, appointment_prep, quote_generation |

### Patrón Idempotente

```php
$existing = \Drupal::entityTypeManager()
  ->getStorage('ai_skill')
  ->loadByProperties(['name' => $skill['name']]);
if (!empty($existing)) { $skipped++; continue; }
```

**Regla SKILLS-001**: Todo seed script de contenido AI debe ser idempotente (check por name antes de crear).

---

## Fase 2: Monitoring Stack Completo (Doc 133)

**Directorio:** `monitoring/`

### Stack Standalone (Docker Compose)

| Componente | Puerto | Función |
|------------|--------|---------|
| Prometheus | 9090 | Scraping métricas (15s interval) |
| Grafana | 3001 | Dashboards + alertas visuales |
| Loki | 3100 | Agregación de logs |
| Promtail | — | Recolector de logs (drupal, php-fpm, webserver) |
| AlertManager | 9093 | Routing de alertas por severidad |

### Aprendizaje: Monitoring Separado de Lando

Stack de monitoring como Docker Compose independiente (`monitoring/docker-compose.monitoring.yml`), no como servicio dentro de `.lando.yml`. Evita inestabilidad de Docker Desktop con demasiados contenedores y permite despliegue independiente en producción.

### Alertas Configuradas (14 reglas)

| Alerta | Condición | Severidad |
|--------|-----------|-----------|
| ServiceDown | up == 0, 2min | critical |
| HighErrorRate | 5xx > 5%, 5min | critical |
| SlowResponseTime | p95 > 2s, 10min | warning |
| DatabaseConnectionPoolExhausted | > 90%, 5min | critical |
| QdrantDiskFull | > 85%, 10min | critical |
| StripeWebhookFailures | > 0.1/s, 5min | critical |
| SSLCertificateExpiring | < 7 días | warning |

### Routing AlertManager

- **critical** → Slack #jaraba-critical + email equipo
- **warning** → Slack #jaraba-alerts
- **info** → solo Grafana

**Regla MONITORING-001**: Toda alerta critical debe tener al menos 2 canales de notificación.

---

## Fase 3: Go-Live Runbook Ejecutable (Doc 139)

**Directorio:** `scripts/golive/`

### Scripts Creados

| Script | Líneas | Función |
|--------|--------|---------|
| `01_preflight_checks.sh` | 799 | 24 validaciones pre-lanzamiento (PHP, MariaDB, Redis, Qdrant, Stripe, SSL, DNS, módulos, permisos, config) |
| `02_validation_suite.sh` | 609 | Smoke tests por vertical, API validation, CSRF checks |
| `03_rollback.sh` | 717 | Rollback automatizado 7 pasos con notificaciones Slack |

### Aprendizaje: Line Endings en Scripts Generados

Scripts creados por agentes en WSL pueden tener `\r\n` (Windows line endings) que causan `syntax error near unexpected token $'\r'`. Siempre ejecutar `sed -i 's/\r$//'` antes de validar con `bash -n`.

**Regla GOLIVE-001**: Todo script shell generado debe pasar `bash -n` (syntax check) antes de commit.

### Runbook Ejecutivo

`docs/tecnicos/GO_LIVE_RUNBOOK.md` (708 líneas):
- 6 fases: Pre-Go-Live → Deploy → Validación → Go/No-Go → Soft Launch → Public Launch
- Matriz RACI
- Procedimientos de escalación
- Criterios Go/No-Go cuantitativos

---

## Fase 4: Seguridad CI + GDPR (Doc 138)

### Security Scan Automatizado

**Fichero:** `.github/workflows/security-scan.yml`

- Ejecución diaria 02:00 UTC (cron)
- Composer audit + npm audit + Trivy FS scan + OWASP ZAP baseline
- Upload SARIF a GitHub Security tab
- Notificación Slack en vulnerabilidades

### GDPR Drush Commands

**Fichero:** `ecosistema_jaraba_core/src/Commands/GdprCommands.php` (947 líneas)

| Comando | Artículo GDPR | Función |
|---------|---------------|---------|
| `drush gdpr:export {uid}` | Art. 15 (Acceso) | Exporta datos personales del usuario en JSON |
| `drush gdpr:anonymize {uid}` | Art. 17 (Olvido) | Anonimiza datos, reemplaza con hash |
| `drush gdpr:report` | — | Informe de compliance general |

### Aprendizaje: Drush Commands como Servicio

Registrar Drush commands como servicio con tag `drush.command` en services.yml:

```yaml
ecosistema_jaraba_core.gdpr_commands:
  class: Drupal\ecosistema_jaraba_core\Commands\GdprCommands
  arguments: ['@entity_type.manager', '@database', '@logger.channel.ecosistema_jaraba_core']
  tags:
    - { name: drush.command }
```

### Playbook de Respuesta a Incidentes

`docs/tecnicos/SECURITY_INCIDENT_RESPONSE_PLAYBOOK.md` (626 líneas):
- Matriz SEV1-SEV4
- 5 tipos de incidentes
- Timeline GDPR Art. 33 (notificación AEPD 72h)
- Templates comunicación

**Regla SECURITY-001**: Todo workflow de seguridad CI debe incluir al menos composer audit + dependency scan (Trivy).

---

## Fase 5: Catálogo Stripe (Doc 134)

**Fichero:** `scripts/stripe/seed_products_prices.php`

### Productos y Precios

5 productos (uno por vertical) × 4 tiers × 2 intervalos = 40 precios:

| Vertical | Starter | Growth | Pro | Enterprise |
|----------|---------|--------|-----|-----------|
| empleabilidad | €49/mes | €99/mes | €199/mes | €499/mes |
| emprendimiento | €59/mes | €119/mes | €239/mes | €599/mes |
| agroconecta | €39/mes | €79/mes | €159/mes | €399/mes |
| comercioconecta | €29/mes | €59/mes | €119/mes | €299/mes |
| serviciosconecta | €49/mes | €99/mes | €199/mes | €499/mes |

Descuentos anuales: 16-20% según tier.

### Aprendizaje: Lookup Keys para Idempotencia Stripe

Usar `lookup_key` en precios Stripe permite referencia programática sin almacenar IDs:

```php
$price = \Stripe\Price::create([
  'product' => $product->id,
  'unit_amount' => $amount,
  'currency' => 'eur',
  'recurring' => ['interval' => $interval],
  'lookup_key' => "jaraba_{$vertical}_{$tier}_{$interval}",
  'transfer_lookup_key' => true,
]);
```

### Comisiones Marketplace

`config/sync/jaraba_billing.marketplace_commissions.yml`:
- agroconecta: 8%, comercioconecta: 6%, serviciosconecta: 10%, enterprise_custom: 3%
- Descuentos por volumen: 10% a partir de €5,000 GMV/mes, 20% a partir de €20,000

**Regla STRIPE-001**: Todo precio Stripe debe tener `lookup_key` para referencia sin IDs hardcodeados.

---

## Fase 6: Templates MJML Email (Doc 136)

**Directorio:** `web/modules/custom/jaraba_email/templates/mjml/`

### 24 Templates Creados

| Categoría | Templates | IDs |
|-----------|-----------|-----|
| auth/ | 5 | AUTH_001-005 (verify, welcome, password_reset, password_changed, new_login) |
| billing/ | 7 | BILL_001-007 (invoice, payment_failed, subscription, upgrade, trial, cancel, dunning) |
| marketplace/ | 6 | MKTP_001-006 (order_confirmed, new_order_seller, shipped, delivered, payout, review) |
| empleabilidad/ | 5 | EMPL_001-005 (job_match, application, new_application, shortlisted, expired) |

### TemplateLoaderService

`jaraba_email/src/Service/TemplateLoaderService.php`:
- Mapea template_id → fichero MJML
- Reemplaza `{{ variables }}` con datos del contexto
- Compila via `MjmlCompilerService`
- Método `preview()` con datos de ejemplo

**Regla EMAIL-001**: Todo template email transaccional debe usar MJML base compartido para consistencia visual.

---

## Fase 7: Testing Enhancement (Doc 135)

### k6 Load Tests

`tests/performance/load_test.js`:
- Escenarios: smoke (1 VU), load (50 VUs), stress (200 VUs)
- Endpoints: homepage, login, API skills, checkout
- Thresholds: p95 < 500ms, error rate < 1%

### BackstopJS Visual Regression

`tests/visual/backstop.json`:
- 10 páginas críticas en 3 viewports (phone 375px, tablet 768px, desktop 1440px)
- Páginas: Homepage, Login, Pricing, Contact, Dashboard, Skills, Compliance, Analytics, Billing, Reseller

### CI Coverage Enforcement

`.github/workflows/ci.yml` — Step añadido:
- Parse `coverage.xml` con PHP
- Threshold: 80% statements covered
- Falla el build si cobertura < 80%

**Regla TEST-002**: Todo merge a main debe pasar threshold de cobertura 80%.

---

## Resumen de Ficheros

| Fase | Ficheros Creados | Ficheros Modificados |
|------|-----------------|---------------------|
| 1. Skills Verticales | 1 | 0 |
| 2. Monitoring | 6 | 0 |
| 3. Go-Live | 4 | 0 |
| 4. Seguridad CI | 3 | 1 |
| 5. Stripe | 2 | 0 |
| 6. Email | 26 | 1 |
| 7. Testing | 2 | 1 |
| **Total** | **44** | **3** |

---

## Reglas Nuevas

| Regla | Descripción |
|-------|-------------|
| **SKILLS-001** | Todo seed script de contenido AI debe ser idempotente |
| **MONITORING-001** | Toda alerta critical debe tener 2+ canales de notificación |
| **GOLIVE-001** | Todo script shell generado debe pasar `bash -n` |
| **SECURITY-001** | CI de seguridad requiere composer audit + dependency scan |
| **STRIPE-001** | Todo precio Stripe debe tener `lookup_key` |
| **EMAIL-001** | Todo template email debe usar MJML base compartido |
| **TEST-002** | Threshold cobertura 80% obligatorio para merge a main |

---

## Patrón: Paralelización de Implementación

Las fases independientes (3, 4, 6) se ejecutaron como agentes background mientras las fases con dependencias mínimas (5, 7) se implementaron directamente. Esto maximizó throughput: 7 fases completadas en una sola sesión.

```
Directo:  Fase 1 → Fase 2 → Fase 5 → Fase 7
Paralelo: ────────── Fase 3 (agent) ──────────
          ────────── Fase 4 (agent) ──────────
          ────────── Fase 6 (agent) ──────────
```
