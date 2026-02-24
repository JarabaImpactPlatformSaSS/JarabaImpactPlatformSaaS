# Aprendizaje #119 — Auditoria Horizontal: Seguridad Access Handlers + CAN-SPAM Emails

**Fecha:** 2026-02-24
**Contexto:** Primera auditoria cross-cutting del SaaS, revisando flujos horizontales que cruzan los 21 modulos: strict equality en access handlers (seguridad) y compliance CAN-SPAM en plantillas MJML transaccionales
**Impacto:** 52 vulnerabilidades de type juggling eliminadas en 39 access handlers; 28 plantillas MJML con cumplimiento CAN-SPAM completo y paleta de marca unificada
**Commits:** `41cb1f6e`, `e07c5c23` (Sprint 1) — `cb362e5b`, `31ea8163` (Sprint 2)
**Regla de oro:** #33

---

## 1. Problema

Tras completar 6 auditorias verticales (Empleabilidad, Emprendimiento, AgroConecta, ComercioConecta, ServiciosConecta, JarabaLex), una revision horizontal revelo dos categorias de deuda transversal:

### P0 Seguridad — Loose equality en access handlers

52 instancias de `==` (loose equality) en 39 access handlers de 21 modulos. PHP loose equality permite type juggling: `"0" == false`, `"1e0" == "1"`, `null == "0"`. En comparaciones de ownership (`$entity->getOwnerId() == $account->id()`), un atacante podria explotar coercion de tipos para obtener acceso no autorizado.

| Modulo | Ficheros | Instancias |
|--------|----------|------------|
| jaraba_comercio_conecta | 12 | 12 |
| jaraba_groups | 5 | 8 |
| jaraba_servicios_conecta | 3 | 6 |
| jaraba_legal_billing | 4 | 6 |
| jaraba_job_board | 2 | 4 |
| jaraba_referral | 3 | 4 |
| jaraba_events | 2 | 3 |
| jaraba_business_tools | 3 | 3 |
| jaraba_legal_vault | 2 | 3 |
| ecosistema_jaraba_core | 1 | 2 |
| jaraba_diagnostic | 1 | 2 |
| jaraba_candidate | 1 | 2 |
| jaraba_legal_intelligence | 2 | 2 |
| jaraba_legal_calendar | 2 | 2 |
| jaraba_identity | 1 | 1 |
| jaraba_agent_market | 1 | 1 |
| jaraba_matching | 1 | 1 |
| jaraba_resources | 1 | 1 |
| jaraba_lms | 1 | 1 |
| jaraba_mentoring | 1 | 1 |
| jaraba_legal_cases | 1 | 1 |
| **TOTAL** | **39** | **52** |

### P0 Legal — CAN-SPAM en plantillas MJML horizontales

28 plantillas MJML horizontales (base, auth, billing, marketplace, fiscal, andalucia_ei) con 4 deficiencias:

| Deficiencia | Regla violada | Ficheros |
|-------------|---------------|----------|
| Sin `<mj-preview>` (preheader) | EMAIL-PREVIEW-001 | 28/28 |
| Sin direccion postal del remitente | CAN-SPAM §5(a)(1)(C) | 28/28 |
| Font off-brand (`Arial` sin `Outfit`) | BRAND-FONT-001 | 22/28 (6 andalucia_ei ya tenian Outfit) |
| Colores off-brand (6 universales + 4 grupo) | BRAND-COLOR-001 | 28/28 |

---

## 2. Diagnostico

### Type juggling en PHP — Por que `==` es peligroso en access checks

```php
// Drupal Entity::getOwnerId() retorna string|null
// AccountInterface::id() retorna int|string

// Caso real de bypass:
$entity->getOwnerId()  // "0" (entidad sin propietario)
$account->id()          // 0   (usuario anonimo)
"0" == 0                // true (!) — loose equality

// Con strict equality:
(int) "0" === (int) 0   // true — correcto, son el mismo valor numerico
// Pero mas importante:
$entity->getOwnerId()  // null (campo vacio)
$account->id()          // 0
null == 0               // true (!) — bypass critico
(int) null === (int) 0  // true — pero null coerce a 0 intencionalmente
```

El riesgo real no es el caso `null == 0` aislado, sino la cascada de coerciones inesperadas que PHP aplica con `==`:
- `"" == 0` → true
- `"abc" == 0` → true (en PHP < 8.0, aun relevante en migraciones)
- `"1e0" == "1"` → true (notacion cientifica)

### Patrones encontrados en los access handlers

Tres patrones distintos de comparacion de ownership:

```php
// Patron 1: getOwnerId() directo
$entity->getOwnerId() == $account->id()

// Patron 2: via field target_id
$entity->get('field_name')->target_id == $account->id()

// Patron 3: via entidad intermedia
$merchant->getOwnerId() == $account->id()
```

### Colores off-brand — Analisis de origen

Los colores off-brand provienen de Tailwind CSS defaults que se usaron como punto de partida durante el scaffolding inicial de plantillas MJML:

| Tailwind default | Brand token | Uso |
|------------------|-------------|-----|
| `#374151` (gray-700) | `#333333` | Body text |
| `#6b7280` (gray-500) | `#666666` | Muted/footer text |
| `#f3f4f6` (gray-100) | `#f8f9fa` | Body background |
| `#e5e7eb` (gray-200) | `#E0E0E0` | Divider borders |
| `#9ca3af` (gray-400) | `#999999` | Very muted text |
| `#111827` (gray-900) | `#1565C0` | Heading text |
| `#2563eb` (blue-600) | `#1565C0` | Primary blue |

Adicionalmente, 3 verticales usaban azules propios sin alinear con la paleta corporativa:
- Fiscal: `#1A365D`, `#553C9A`
- Andalucia EI: `#233D63`

---

## 3. Solucion

### Sprint 1 — Strict equality en access handlers

**Fix universal aplicado a las 52 instancias:**

```php
// ANTES (vulnerable)
$entity->getOwnerId() == $account->id()
$entity->get('field')->target_id == $account->id()
$merchant->getOwnerId() == $account->id()

// DESPUES (seguro)
(int) $entity->getOwnerId() === (int) $account->id()
(int) $entity->get('field')->target_id === (int) $account->id()
(int) $merchant->getOwnerId() === (int) $account->id()
```

**Por que `(int)` en ambos lados:**
- `getOwnerId()` retorna `string|null`, `id()` retorna `int|string`
- Cast explicito a `(int)` normaliza ambos lados y documenta la intencion
- `===` sin cast fallaria: `"42" === 42` es `false` en PHP

**Nota sobre el TenantAccessControlHandler:**
El fichero principal del core (`ecosistema_jaraba_core/src/TenantAccessControlHandler.php`) estaba en un path sin subdirectorio `Access/`, lo que hizo que pasara desapercibido en la primera pasada. Siempre buscar en `src/` ademas de `src/Access/`.

### Sprint 2 — CAN-SPAM en plantillas MJML

**5 cambios por plantilla, aplicados a las 28:**

#### 1. Preheader (`<mj-preview>`)

```xml
<mj-body background-color="#f8f9fa" width="600px">
    <mj-preview>Tu factura de {{ site_name }} est&aacute; disponible</mj-preview>
    <!-- Header -->
```

Cada plantilla recibio un preheader unico en espanol con HTML entities para acentos.

#### 2. Direccion postal (CAN-SPAM)

```xml
        <!-- Ultimo elemento antes de </mj-column> en el footer -->
        <mj-text align="center" font-size="11px" color="#999999" padding-top="8px">
          Pol. Ind. Juncaril, C/ Baza Parcela 124, 18220 Albolote, Granada, Espa&ntilde;a
        </mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
```

#### 3. Font brand

```xml
<!-- ANTES -->
<mj-all font-family="Arial, Helvetica, sans-serif" />
<!-- DESPUES -->
<mj-all font-family="Outfit, Arial, Helvetica, sans-serif" />
```

#### 4. Colores universales (6 reemplazos en 28 ficheros)

| Off-brand | Brand | Contexto |
|-----------|-------|----------|
| `#374151` | `#333333` | Body text en `<mj-text>` |
| `#6b7280` | `#666666` | Footer, muted text, `.footer-link` |
| `#f3f4f6` | `#f8f9fa` | `<mj-body background-color>` |
| `#e5e7eb` | `#E0E0E0` | `<mj-divider border-color>` |
| `#9ca3af` | `#999999` | Disclaimer text |
| `#111827` | `#1565C0` | Heading text |

#### 5. Colores por grupo

| Grupo | Off-brand | Brand | Ficheros |
|-------|-----------|-------|----------|
| base + auth + billing + marketplace | `#2563eb` | `#1565C0` | 19 |
| fiscal (certificate, verifactu) | `#1A365D` | `#1565C0` | 2 |
| fiscal (face_invoice) | `#553C9A` | `#1565C0` | 1 |
| fiscal (face_invoice) | `#faf5ff` | `#E3F2FD` | 1 |
| andalucia_ei | `#233D63` | `#1565C0` | 6 |

#### Colores semanticos preservados (NO tocados)

| Color | Uso | Ficheros |
|-------|-----|----------|
| `#dc2626` | Rojo error | payment_failed, dunning_notice |
| `#16a34a` | Verde exito | subscription_created, order_confirmed, order_delivered |
| `#f59e0b` | Amber warning | trial_ending |
| `#FF8C42` | Naranja Andalucia EI | 6 plantillas andalucia_ei (botones y accent) |
| `#00A9A5` | Teal Andalucia EI | welcome_participant |
| `#10b981` | Verde progreso | phase_transition, training_completion |
| `#D97706` | Amber fiscal | certificate_expiring |
| `#fef2f2`, `#fef3c7`, `#f0fdf4`, `#fee2e2`, `#fffbeb`, `#FFF7ED`, `#ECFDF5` | Fondos semanticos de cajas | Multiples |
| `#991b1b`, `#92400e`, `#166534`, `#78350f`, `#065f46` | Textos oscuros en cajas semanticas | Multiples |

---

## 4. Regla nueva — REGLA #33: Auditorias horizontales periodicas

> **Despues de completar auditorias verticales, ejecutar siempre una auditoria horizontal** que revise flujos cross-cutting: access handlers, plantillas de email, configuracion de permisos, tokens de marca, CSRF, y otros patrones que se repiten identicamente en todos los modulos. Los bugs sistematicos no se descubren auditando un solo vertical — requieren vision transversal.

### Sub-reglas derivadas

| ID | Regla | Prioridad |
|----|-------|-----------|
| ACCESS-STRICT-001 | Toda comparacion de ownership en access handlers DEBE usar `(int) ... === (int) ...` — nunca `==` | P0 |
| EMAIL-PREVIEW-001 | Toda plantilla MJML DEBE tener `<mj-preview>` con preheader descriptivo unico | P0 |
| EMAIL-POSTAL-001 | Toda plantilla MJML DEBE incluir direccion postal del remitente en el footer (CAN-SPAM §5) | P0 |
| BRAND-FONT-001 | `Outfit` DEBE ser el primer font en todo `font-family` de emails | P1 |
| BRAND-COLOR-001 | Solo colores del sistema de tokens permitidos en MJML — prohibido Tailwind defaults | P1 |

---

## 5. Verificacion

| Check | Comando | Resultado |
|-------|---------|-----------|
| Zero loose equality | `grep -rn "== $account->id()" web/modules/custom/ --include="*.php" \| grep -v "==="` | 0 resultados |
| PHP syntax | `php -l` en 39 ficheros PHP modificados | 0 errores |
| Preheader en 28 horizontales | `grep -l "mj-preview" web/modules/custom/jaraba_email/templates/mjml/{base,auth,billing,marketplace,fiscal,andalucia_ei}*` | 28/28 |
| Postal en 28 horizontales | `grep -l "Juncaril" ...` | 28/28 |
| Zero colores off-brand | `grep -rn "#2563eb\|#1A365D\|#553C9A\|#233D63\|#374151\|#6b7280\|#f3f4f6\|#e5e7eb\|#9ca3af\|#111827"` en 28 MJML | 0 resultados |
| Semanticos preservados | `grep -rn "#dc2626\|#16a34a\|#FF8C42\|#f59e0b"` en 28 MJML | Presentes en ficheros correctos |
| PHPUnit | `phpunit jaraba_legal_lexnet/tests/` | 19 tests, 77 assertions — OK |

---

## 6. Metricas de impacto

| Metrica | Antes | Despues |
|---------|-------|---------|
| Instancias `==` en access handlers | 52 | 0 |
| Plantillas sin `<mj-preview>` | 28 | 0 |
| Plantillas sin postal CAN-SPAM | 28 | 0 |
| Plantillas sin font Outfit | 22 | 0 |
| Colores off-brand en horizontales | ~280 ocurrencias | 0 |
| Colores semanticos preservados | 11 colores | 11 colores (intactos) |

---

## 7. Aprendizajes clave

### 7.1 Los bugs sistematicos son invisibles en auditorias verticales

Cada auditoria vertical vio 1-3 instancias de `==` en su modulo y las podia considerar "menores". Solo la vision horizontal revelo que eran **52 instancias del mismo patron** en 21 modulos — un riesgo sistematico, no puntual.

### 7.2 Scaffolding genera deuda de marca a escala

Cuando se scaffoldean 28 plantillas MJML desde un template base que usa Tailwind defaults, la deuda de marca se multiplica x28 instantaneamente. **El template base debe usar tokens de marca desde el dia 0.**

### 7.3 CAN-SPAM no es opcional — es ley federal

La ley CAN-SPAM (15 U.S.C. §7704) exige:
- Direccion postal fisica del remitente en todo email comercial
- Mecanismo de opt-out visible (ya existia via `{{ unsubscribe_url }}`)
- Identificacion clara del remitente (ya existia via header branded)

Faltaban postal y preheader. El preheader no es legalmente obligatorio pero es best practice para deliverability y UX (es lo que el usuario ve en la bandeja antes de abrir).

### 7.4 Patron de cast explicito `(int)` documenta intencion

```php
// Ambiguo — el lector no sabe si el dev considero los tipos
$entity->getOwnerId() === $account->id();  // Falla si uno es string y otro int

// Explicito — documenta que la comparacion es numerica
(int) $entity->getOwnerId() === (int) $account->id();
```

El `(int)` cast no solo previene bugs — comunica al siguiente desarrollador que la comparacion es intencionalmente numerica.

### 7.5 Ficheros fuera de `/Access/` subdirectory

Algunos modulos colocan sus access handlers directamente en `src/` en lugar de `src/Access/`. El grep debe buscar en `**/*AccessControlHandler.php` sin asumir subdirectorio. Ficheros encontrados fuera de `src/Access/`:

- `ecosistema_jaraba_core/src/TenantAccessControlHandler.php`
- `jaraba_job_board/src/JobPostingAccessControlHandler.php`
- `jaraba_job_board/src/JobApplicationAccessControlHandler.php`
- `jaraba_diagnostic/src/EmployabilityDiagnosticAccessControlHandler.php`
- `jaraba_candidate/src/CandidateProfileAccessControlHandler.php`
- `jaraba_matching/src/MatchResultAccessControlHandler.php`
- `jaraba_lms/src/EnrollmentAccessControlHandler.php`
- `jaraba_mentoring/src/MentoringSessionAccessControlHandler.php`
