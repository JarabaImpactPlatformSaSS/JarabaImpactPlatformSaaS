# Bloque E: Training & Certification - Plan de Completación v2
## Auditoría de Gaps y Plan para Completar el 25% Restante

**Fecha de creación:** 2026-01-23  
**Última actualización:** 2026-02-04 11:58  
**Autor:** IA Asistente (Claude)  
**Versión:** 2.0.0  
**Estado:** ~75% implementado → Objetivo 100%

---

## 📑 Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Directrices Obligatorias del Proyecto](#2-directrices-obligatorias-del-proyecto)
3. [Análisis de Gaps](#3-análisis-de-gaps)
4. [Plan de Implementación](#4-plan-de-implementación)
5. [Especificaciones Técnicas](#5-especificaciones-técnicas)
6. [Checklist Pre-Commit](#6-checklist-pre-commit)
7. [Verificación](#7-verificación)
8. [Registro de Cambios](#8-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Estado Actual (75% Implementado)

| Componente | Estado | Ubicación |
|------------|--------|-----------|
| **Entidades** | ✅ | |
| `TrainingProduct` | ✅ 10.7KB | `src/Entity/TrainingProduct.php` |
| `CertificationProgram` | ✅ 8.6KB | `src/Entity/CertificationProgram.php` |
| `UserCertification` | ✅ 6.3KB | `src/Entity/UserCertification.php` |
| **Servicios** | ✅ | |
| `LadderService` | ✅ 4.3KB | `src/Service/LadderService.php` |
| `RoyaltyTracker` | ✅ 4.2KB | `src/Service/RoyaltyTracker.php` |
| `UpsellEngine` | ✅ 4.9KB | `src/Service/UpsellEngine.php` |
| **APIs REST** | ✅ | |
| `GET /api/v1/training/products` | ✅ | |
| `GET /api/v1/training/ladder` | ✅ | |
| `GET /api/v1/training/recommend` | ✅ | |
| **Navegación Admin** | ✅ | |
| `/admin/content/training-products` | ✅ Tab Content | |
| `/admin/structure/training-product` | ✅ Field UI | |

### 1.2 Gaps Identificados (25% Pendiente)

| Gap | Descripción | Horas | Prioridad |
|-----|-------------|-------|-----------|
| **1** | Módulo `jaraba_credentials` (Open Badge 3.0) | 40h | 🔴 Crítico |
| **2** | Verificación pública `/verify/{uuid}` | 8h | 🔴 Crítico |
| **3** | Integración exámenes H5P | 16h | 🟠 Alto |
| **4** | Automatizaciones ECA (hooks Drupal) | 16h | 🟠 Alto |
| **5** | Dashboard certificados `/my-certifications` | 12h | 🟡 Medio |
| **6** | Directorio de consultores `/consultores` | 8h | 🟢 Bajo |
| **7** | Sistema de territorios | 16h | 🟢 Futuro |

**Inversión mínima para funcionalidad core:** 72h (Gaps 1-5)

---

## 2. Directrices Obligatorias del Proyecto

> [!CAUTION]
> **TODAS estas directrices son de cumplimiento obligatorio.** El código que no las cumpla será rechazado.

### 2.1 Content Entity Navigation (workflow: `/drupal-custom-modules`)

| Ubicación | Ruta | Propósito |
|-----------|------|-----------|
| `/admin/content/{entities}` | Listado | Pestaña en Content |
| `/admin/structure/{entity}` | Field UI | Administrar campos |
| `/admin/config/{module}/settings` | Settings | Configuración |

**4 Archivos YAML Obligatorios para cada Content Entity:**
- [ ] `*.routing.yml` → URLs
- [ ] `*.links.menu.yml` → Menú en Structure
- [ ] `*.links.task.yml` → Pestaña en Content
- [ ] `*.links.action.yml` → Botón "Añadir"

### 2.2 SCSS + Variables Inyectables (workflow: `/scss-estilos`)

```scss
// ❌ PROHIBIDO - Valores hardcodeados
color: #233D63;
padding: 24px;

// ✅ OBLIGATORIO - Variables inyectables
color: var(--ej-color-corporate, #{$ej-color-corporate-fallback});
padding: var(--ej-spacing-lg, #{$ej-spacing-lg-fallback});
```

**Paleta Jaraba Obligatoria:**

| Variable | Hex | Uso Semántico |
|----------|-----|---------------|
| `corporate` | #233D63 | Base corporativa |
| `innovation` | #00A9A5 | Empleabilidad, IA, certificaciones |
| `impulse` | #FF8C42 | CTAs, acciones, upsells |
| `success` | #10B981 | Estados positivos, aprobados |

**Compilación con Dart Sass Moderno:**
```bash
cd web/modules/custom/ecosistema_jaraba_core
source ~/.nvm/nvm.sh && nvm use --lts
npm run build  # Usa Dart Sass moderno
lando drush cr
```

### 2.3 Iconografía SVG (workflow: `/scss-estilos`)

**Crear AMBAS versiones para cada icono nuevo:**
- `business/certificate.svg` - Versión outline
- `business/certificate-duotone.svg` - Versión duotone

**Uso en Twig:**
```twig
{{ jaraba_icon('business', 'certificate', { color: 'innovation', size: '32px' }) }}
{{ jaraba_icon('business', 'certificate', { variant: 'duotone', color: 'success' }) }}
```

### 2.4 i18n - Textos Traducibles (workflow: `/i18n-traducciones`)

```php
// ✅ En Controladores PHP
$this->t('Mis Certificaciones')

// ✅ En Forms
'#title' => $this->t('Programa de Certificación')
```

```twig
{# ✅ En Templates Twig #}
{% trans %}Descargar Certificado{% endtrans %}
{% trans %}Verificar Autenticidad{% endtrans %}
```

```javascript
// ✅ En JavaScript
Drupal.t('Certificado verificado correctamente')
```

### 2.5 Frontend Page Pattern (workflow: `/frontend-page-pattern`)

**Template Twig limpio sin regiones Drupal:**
```twig
{# page--my-certifications.html.twig #}
{{ attach_library('ecosistema_jaraba_theme/global') }}
{{ attach_library('ecosistema_jaraba_theme/slide-panel') }}
{{ attach_library('jaraba_training/certifications-dashboard') }}

{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
  site_name: site_name,
  logo: logo|default(''),
  logged_in: logged_in,
  theme_settings: theme_settings|default({})
} %}

<main id="main-content" class="certifications-main">
  <div class="certifications-wrapper">
    {{ page.content }}
  </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
  site_name: site_name,
  theme_settings: theme_settings|default({})
} %}
```

**Clases de body via hook (NO en template):**
```php
// ecosistema_jaraba_theme.theme
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  
  if (str_starts_with($route, 'jaraba_training.') || 
      str_starts_with($route, 'jaraba_credentials.')) {
    $variables['attributes']['class'][] = 'certifications-page';
    $variables['attributes']['class'][] = 'page-certifications';
  }
}
```

> ⚠️ **CRÍTICO:** Las clases añadidas con `attributes.addClass()` en el template NO funcionan para el body.

### 2.6 Slide-Panel para CRUD (workflow: `/slide-panel-modales`)

> [!IMPORTANT]
> **Todas las acciones de crear/editar/ver en frontend abren en modal slide-panel.**

```html
<button data-slide-panel="certificate-detail"
        data-slide-panel-url="/api/v1/credentials/{{ credential.uuid }}"
        data-slide-panel-title="{% trans %}Detalle del Certificado{% endtrans %}">
  {% trans %}Ver Certificado{% endtrans %}
</button>
```

**Controlador con detección AJAX:**
```php
public function viewCredential(string $uuid, Request $request): array|Response {
    $credential = $this->credentialStorage->loadByUuid($uuid);
    $build = ['#theme' => 'credential_detail', '#credential' => $credential];
    
    if ($request->isXmlHttpRequest()) {
        $html = (string) $this->renderer->render($build);
        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
    
    return $build;
}
```

### 2.7 ECA vía Hooks Drupal (workflow: `/drupal-eca-hooks`)

> [!IMPORTANT]
> **Las automatizaciones ECA se implementan mediante hooks de Drupal, NO mediante UI BPMN.**

```php
// jaraba_training.module

/**
 * Implements hook_entity_insert().
 * 
 * ECA-TRAIN-003: Emisión automática de badge al crear certificación.
 */
function jaraba_training_entity_insert(EntityInterface $entity): void {
  if ($entity->getEntityTypeId() === 'user_certification') {
    _jaraba_training_emit_badge($entity);
  }
}

/**
 * Implements hook_entity_update().
 * 
 * ECA-TRAIN-004: Tracking de royalties al cambiar estado.
 */
function jaraba_training_entity_update(EntityInterface $entity): void {
  if ($entity->getEntityTypeId() === 'user_certification') {
    $newStatus = $entity->get('certification_status')->value ?? '';
    $oldStatus = $entity->original->get('certification_status')->value ?? '';
    
    if ($newStatus === 'active' && $oldStatus !== 'active') {
      _jaraba_training_track_royalty($entity);
    }
  }
}

/**
 * Implements hook_cron().
 * 
 * ECA-TRAIN-001/002: Upsells y propuestas periódicas.
 */
function jaraba_training_cron(): void {
  _jaraba_training_process_upsells();
  _jaraba_training_process_certification_proposals();
}
```

### 2.8 Layout Mobile-First + Full-Width

```scss
.certifications-wrapper {
    max-width: 1400px;
    margin-inline: auto;
    padding: var(--ej-spacing-xl, 2rem) var(--ej-spacing-lg, 1.5rem);
    
    @media (max-width: 767px) {
        padding: var(--ej-spacing-lg, 1.5rem) var(--ej-spacing-md, 1rem);
    }
}

.certificate-card {
    // Mobile-first: diseño para móvil primero
    display: flex;
    flex-direction: column;
    gap: var(--ej-spacing-md);
    
    @media (min-width: 768px) {
        flex-direction: row;
        align-items: center;
    }
}
```

### 2.9 Tenant Isolation

- [ ] Tenant NO accede a `/admin/appearance`
- [ ] Tenant NO accede a tema de administración Drupal
- [ ] Tenant accede solo a `/my-certifications` (frontend limpio)
- [ ] Admin SaaS accede a `/admin/content/user-certifications`

---

## 3. Análisis de Gaps

### Gap 1: Módulo jaraba_credentials (Open Badge 3.0)

**Estado:** 🔴 No existe  
**Dependencias:** Desbloquea Gaps 2, 4 (parcial), 5

**Estructura propuesta:**
```
web/modules/custom/jaraba_credentials/
├── jaraba_credentials.info.yml
├── jaraba_credentials.module              # Hooks ECA
├── jaraba_credentials.services.yml
├── jaraba_credentials.routing.yml
├── jaraba_credentials.permissions.yml
├── jaraba_credentials.links.menu.yml      # Structure
├── jaraba_credentials.links.task.yml      # Content tabs
├── jaraba_credentials.links.action.yml    # Add buttons
├── src/
│   ├── Entity/
│   │   ├── IssuerProfile.php              # Ed25519 keys
│   │   ├── CredentialTemplate.php         # Badge/cert model
│   │   └── IssuedCredential.php           # Instance
│   ├── Service/
│   │   ├── CryptographyService.php        # Ed25519 sodium
│   │   ├── OpenBadgeBuilder.php           # JSON-LD OB3
│   │   ├── CredentialIssuer.php           # Orchestration
│   │   └── CredentialVerifier.php         # Public validation
│   ├── Controller/
│   │   ├── VerifyController.php           # /verify/{uuid}
│   │   └── CredentialApiController.php
│   └── Form/
│       ├── IssuerProfileForm.php
│       ├── CredentialTemplateForm.php
│       └── CredentialSettingsForm.php     # Field UI
└── templates/
    ├── credential-verify.html.twig        # Public page
    └── credential-card.html.twig          # Card component
```

---

### Gap 2: Verificación Pública `/verify/{uuid}`

**Estado:** 🔴 No existe  
**Dependencia:** Gap 1

**Requisitos:**
- Ruta pública (sin autenticación)
- JSON-LD OB3 firmado visible
- QR code para compartir
- Validación Ed25519 en tiempo real
- Estado: activo/revocado/expirado

---

### Gap 3: Integración Exámenes H5P

**Estado:** 🟡 Infraestructura existe en jaraba_lms

**Pendiente:**
- Servicio `ExamEvaluator` para consultar resultados H5P
- Cálculo automático de `UserCertification.exam_score`
- Validación de `CertificationProgram.minimum_score`

---

### Gap 4: Automatizaciones vía Hooks Drupal

**Estado:** 🔴 No implementado

| ID | Trigger | Acción | Hook |
|----|---------|--------|------|
| ECA-TRAIN-001 | Compra completada | Email upsell | `hook_cron()` |
| ECA-TRAIN-002 | Curso completado | Propuesta certificación | `hook_cron()` |
| ECA-TRAIN-003 | Certificación creada | Emitir OB3 badge | `hook_entity_insert()` |
| ECA-TRAIN-004 | Certificación activa | Tracking royalties | `hook_entity_update()` |

---

### Gap 5: Dashboard Certificados

**Estado:** 🔴 No existe

**Requisitos:**
- Ruta `/my-certifications` (frontend limpio)
- Template `page--my-certifications.html.twig`
- Partials: `_header.html.twig`, `_footer.html.twig`
- Listado de certificaciones del usuario
- Descarga PDF en slide-panel
- Compartir en LinkedIn

---

## 4. Plan de Implementación

### Fase 1: Open Badges Core (Sprint E-COMPLETE-1)

**Objetivo:** Módulo `jaraba_credentials` funcional  
**Inversión:** 40h

**Checklist:**
- [ ] Crear `jaraba_credentials.info.yml` con dependencias
- [ ] Crear 3 Content Entities con Field UI:
  - [ ] `IssuerProfile` con campo Ed25519 public_key
  - [ ] `CredentialTemplate` con JSON skills_certified
  - [ ] `IssuedCredential` con ob3_json firmado
- [ ] Crear `CryptographyService` con sodium Ed25519
- [ ] Crear `OpenBadgeBuilder` para JSON-LD OB3
- [ ] Crear endpoint `/verify/{uuid}` público
- [ ] Crear template `credential-verify.html.twig`
- [ ] Iconos: `business/badge.svg` + `business/badge-duotone.svg`
- [ ] SCSS: `_credentials-verify.scss` con variables inyectables
- [ ] 4 YAMLs de navegación

---

### Fase 2: Integraciones (Sprint E-COMPLETE-2)

**Objetivo:** H5P + Automatizaciones  
**Inversión:** 32h

**Checklist:**
- [ ] Crear `ExamEvaluator` service
- [ ] Integrar con `jaraba_lms` H5P results
- [ ] Implementar `hook_entity_insert()` para ECA-TRAIN-003
- [ ] Implementar `hook_entity_update()` para ECA-TRAIN-004
- [ ] Implementar `hook_cron()` para ECA-TRAIN-001/002
- [ ] Queue para emails diferidos

---

### Fase 3: Frontend (Sprint E-COMPLETE-3)

**Objetivo:** Dashboard usuario  
**Inversión:** 20h

**Checklist:**
- [ ] Crear ruta `/my-certifications`
- [ ] Crear `page--my-certifications.html.twig` limpio
- [ ] Registrar template suggestion en `.theme`
- [ ] Añadir body classes via `hook_preprocess_html()`
- [ ] Slide-panel para detalle de certificado
- [ ] Botón "Descargar PDF"
- [ ] Botón "Compartir en LinkedIn"
- [ ] SCSS móvil-first: `_certifications-dashboard.scss`

---

## 5. Especificaciones Técnicas

### 5.1 Open Badge 3.0 JSON-LD

```json
{
  "@context": [
    "https://www.w3.org/2018/credentials/v1",
    "https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.2.json"
  ],
  "id": "https://jaraba.es/verify/uuid",
  "type": ["VerifiableCredential", "OpenBadgeCredential"],
  "issuer": {
    "id": "https://jaraba.es/issuers/jaraba-impact",
    "type": "Profile",
    "name": "Jaraba Impact Platform"
  },
  "issuanceDate": "2026-02-04T12:00:00Z",
  "credentialSubject": {
    "id": "did:email:user@example.com",
    "achievement": {
      "name": "Consultor Certificado Jaraba",
      "criteria": { "narrative": "Aprobó examen con 85%+" }
    }
  },
  "proof": {
    "type": "Ed25519Signature2020",
    "proofValue": "..."
  }
}
```

### 5.2 Ed25519 con Sodium

```php
class CryptographyService {
    public function generateKeyPair(): array {
        $keyPair = sodium_crypto_sign_keypair();
        return [
            'public' => sodium_crypto_sign_publickey($keyPair),
            'private' => sodium_crypto_sign_secretkey($keyPair),
        ];
    }
    
    public function sign(string $message, string $privateKey): string {
        return sodium_crypto_sign_detached($message, $privateKey);
    }
    
    public function verify(string $message, string $signature, string $publicKey): bool {
        return sodium_crypto_sign_verify_detached($signature, $message, $publicKey);
    }
}
```

---

## 6. Checklist Pre-Commit

### 6.1 Content Entity Navigation
- [ ] ¿4 archivos YAML de navegación por entidad?
- [ ] ¿`field_ui_base_route` apunta a ruta settings?
- [ ] ¿Aparece en `/admin/content` como pestaña?
- [ ] ¿Aparece en `/admin/structure` para Field UI?

### 6.2 Internacionalización
- [ ] ¿Textos PHP usan `$this->t()`?
- [ ] ¿Textos Twig usan `{% trans %}`?
- [ ] ¿JavaScript usa `Drupal.t()`?

### 6.3 Estilos
- [ ] ¿SCSS usa variables `var(--ej-*)`?
- [ ] ¿Colores usan paleta Jaraba?
- [ ] ¿Layout móvil-first?
- [ ] ¿Compilado con Dart Sass?

### 6.4 Iconografía
- [ ] ¿Icono tiene versión outline?
- [ ] ¿Icono tiene versión duotone?

### 6.5 UX Frontend
- [ ] ¿Template es página limpia sin regiones?
- [ ] ¿Usa partials `_header.html.twig`, `_footer.html.twig`?
- [ ] ¿Clases body via `hook_preprocess_html()`?
- [ ] ¿Acciones CRUD abren en slide-panel?

### 6.6 ECA/Automatizaciones
- [ ] ¿Implementado vía hooks en `.module`?
- [ ] ¿NO usa UI BPMN de ECA?
- [ ] ¿Emails vía queue diferida?

---

## 7. Verificación

### 7.1 Comandos

```bash
# Verificar entidades
docker exec jarabasaas_appserver_1 drush entity:types | grep -E "(credential|issuer)"

# Verificar rutas
docker exec jarabasaas_appserver_1 drush route:list | grep -E "(credential|verify)"

# Compilar SCSS
cd web/modules/custom/ecosistema_jaraba_core
source ~/.nvm/nvm.sh && nvm use --lts && npm run build
lando drush cr
```

### 7.2 Verificación Manual

1. `/admin/content` → Tab "Credenciales" visible
2. `/admin/structure/credential-template` → Field UI funciona
3. `/verify/{uuid}` → Página pública muestra OB3 + QR
4. `/my-certifications` → Dashboard limpio sin sidebar admin
5. Clic en certificado → Slide-panel se abre

---

## 8. Registro de Cambios

| Fecha | Versión | Cambios |
|-------|---------|---------|
| 2026-01-23 | 1.0.0 | Documento original |
| **2026-02-04** | **2.0.0** | Auditoría de gaps, incorporación de directrices: SCSS inyectable, i18n, Content Entity navigation, slide-panel, frontend limpio, iconografía duotone, ECA vía hooks Drupal, layouts mobile-first. Plan detallado por sprints. |

---

## Workflows Relevantes

| Workflow | Uso |
|----------|-----|
| `/drupal-custom-modules` | Estructura de entidades y navegación |
| `/drupal-eca-hooks` | Automatizaciones vía hooks (NO BPMN) |
| `/scss-estilos` | Variables inyectables y paleta |
| `/i18n-traducciones` | Textos traducibles |
| `/frontend-page-pattern` | Templates limpios |
| `/slide-panel-modales` | CRUD en modales |

---

*Documento actualizado: 2026-02-04 con todas las directrices del proyecto*
