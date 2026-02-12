# Documento Maestro de Cierre: Jaraba Impact Platform SaaS

> **Fecha:** 2026-01-28  
> **Versión:** 1.0 (Consolidación definitiva)  
> **Target:** Puntuación 10/10 en todas las dimensiones  
> **Inversión Total:** 710-970h (~€46,000-63,000)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado Actual vs Target](#2-estado-actual-vs-target)
3. [Especificaciones de Cierre (178-187)](#3-especificaciones-de-cierre-178-187)
4. [Arquitectura Técnica Consolidada](#4-arquitectura-técnica-consolidada)
5. [Flujos UX por Actor](#5-flujos-ux-por-actor)
6. [Directrices de Implementación](#6-directrices-de-implementación)
7. [Frontend Premium: Lenis Integration](#7-frontend-premium-lenis-integration)
8. [Roadmap de Ejecución](#8-roadmap-de-ejecución)
9. [Verificación y Métricas](#9-verificación-y-métricas)

---

## 1. Resumen Ejecutivo

Este documento consolida las auditorías, especificaciones y directrices técnicas para llevar la plataforma Jaraba Impact Platform SaaS a **puntuación 10/10** en todas las dimensiones evaluadas.

### Fuentes Consolidadas

| Documento | Código | Contenido |
|-----------|--------|-----------|
| Auditoría UX | `20260128a` | Evaluación ecosistema desde 5 perspectivas |
| Especificaciones 10/10 | `20260128b` | 10 documentos de cierre (178-187) |
| Evaluación Multidisciplinar | `20260128` | 10 perspectivas profesionales + Lenis |

### Inversión Consolidada

| Concepto | Horas | Coste (€65/h) |
|----------|-------|---------------|
| Especificaciones 178-187 | 252-340h | €16,380-22,100 |
| Implementación (×1.5) | 378-510h | €24,570-33,150 |
| Testing + QA | 80-120h | €5,200-7,800 |
| **TOTAL** | **710-970h** | **€46,150-63,050** |

---

## 2. Estado Actual vs Target

| Dimensión | Antes | Después | Gap a Cerrar |
|-----------|-------|---------|--------------|
| Arquitectura Negocio | 8.5 | 10.0 | Doc 186: B2B Sales Flow |
| Arquitectura Técnica | 9.0 | 10.0 | Doc 187: Scaling Infrastructure |
| Consistencia Funcional | 7.5 | 10.0 | Docs 184, 185: Merchant Copilot + ECA Registry |
| UX Admin SaaS | 6.5 | 10.0 | Doc 181: Admin UX Complete |
| UX Tenant Admin | 7.0 | 10.0 | Docs 179, 182: Onboarding Wizard + Entity Dashboard |
| UX Usuario Visitante | 6.0 | 10.0 | Docs 178, 180, 183: Visitor Journey + Landings + Freemium |

---

## 3. Especificaciones de Cierre (178-187)

### 3.1 UX Visitante (Docs 178, 180, 183)

#### Doc 178: Visitor Journey Complete
**Horas:** 40-56h | **Prioridad:** CRÍTICA

```
AWARENESS → INTEREST → DESIRE → ACTION → ACTIVATION → CONVERSION
```

**Implementar:**
- Modelo AIDA con métricas específicas por etapa
- Detección automática de vertical por UTM/keyword/geolocalización
- Lead magnets por vertical:
  - Empleabilidad: Diagnóstico Express TTV (<3 min)
  - Emprendimiento: Calculadora Madurez Digital (<5 min)
  - AgroConecta: Guía PDF sin intermediarios
  - ComercioConecta: Auditoría SEO Local (<2 min)
  - ServiciosConecta: Template Propuesta Profesional

**Referencia:** [178_Visitor_Journey_Complete_v1](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260128b-Especificaciones_Completas_10_10_Ecosistema_Jaraba_Claude.md#documento-178)

---

#### Doc 180: Landing Pages Verticales
**Horas:** 48-64h (5 landings × 10-12h)

**Estructura común:**
1. Hero (Headline + CTA + Visual)
2. Pain Points (3-4 problemas)
3. Solution (3 pasos simples)
4. Features (6-8 con iconos)
5. Social Proof (testimonios + logos)
6. Lead Magnet CTA
7. Pricing Preview
8. FAQ (Schema.org)
9. Final CTA

**URLs a implementar:**
- `/agroconecta` - Productores agroalimentarios
- `/comercioconecta` - Comercios locales
- `/serviciosconecta` - Profesionales
- `/empleabilidad` - Job seekers +45
- `/emprendimiento` - Startups rurales

---

#### Doc 183: Freemium & Trial Model
**Horas:** 16-24h

| Vertical | Free Limit | Upgrade Trigger |
|----------|------------|-----------------|
| AgroConecta | 5 productos, 10 pedidos/mes | Límite alcanzado (35% conv) |
| ComercioConecta | 10 productos, 1 QR | Primera venta (42% conv) |
| ServiciosConecta | 3 servicios, 10 reservas | Feature bloqueada (28% conv) |
| Empleabilidad | 1 diagnóstico, 10 ofertas/día | CV Builder Pro |
| Emprendimiento | 1 BMC borrador | Validación MVP |

---

### 3.2 UX Tenant Admin (Docs 179, 182)

#### Doc 179: Tenant Onboarding Wizard
**Horas:** 32-40h | **KPI:** Completion rate >70%

**7 Pasos:**
1. **Bienvenida** (30s) - Confirmar vertical
2. **Identidad** (2min) - Logo, nombre, colores + IA extrae paleta
3. **Datos Fiscales** (2min) - NIF/CIF, dirección
4. **Pagos** (3min) - Stripe Connect onboarding
5. **Equipo** (1min) - Invitar colaboradores (opcional)
6. **Contenido Inicial** (3min) - Primer producto/servicio
7. **Lanzamiento** (30s) - Confetti + celebración

**Persistencia:**
```php
// Entidad: tenant_onboarding_progress
- tenant_id, current_step, completed_steps[], step_data{}
- started_at, completed_at, time_spent_seconds
- skipped_steps[]
```

---

#### Doc 182: Entity Admin Dashboard (Avatar Elena)
**Horas:** 24-32h

**Necesidades específicas:**
- Grant Burn Rate tracker + exportación evidencias
- Gestión de cohortes con progreso agregado
- Generador de informes PDF formato institucional
- Panel de actividad de tutores/mentores
- Checklist compliance SEPE

**Templates de informe:**
- Informe Seguimiento Mensual
- Memoria Económica
- Informe de Impacto
- Justificación Técnica
- Certificados de Asistencia (generación masiva)

---

### 3.3 UX Admin SaaS (Doc 181)

**Horas:** 24-32h

#### Día en la Vida del Super Admin

| Hora | Tarea | Pantalla |
|------|-------|----------|
| 8:00 | Morning Check | Dashboard → Alertas → MRR overnight |
| 9:00 | Tenant Triage | Health Monitor → Score <60 → Playbooks |
| 10:00 | Approvals | Pending → Verificar Stripe → Aprobar |
| 14:00 | Financial Review | FOC → MRR/ARR → Churn → Grant Burn |
| 16:00 | Support Escalation | Impersonate → Debug → Resolver |

#### Command Palette (⌘K)

| Comando | Shortcut | Acción |
|---------|----------|--------|
| `go tenants` | G+T | Lista tenants |
| `go finance` | G+F | Dashboard FOC |
| `impersonate [email]` | I | Login como usuario |
| `alerts` | A | Alertas activas |

---

### 3.4 Consistencia Funcional (Docs 184, 185)

#### Doc 184: Merchant Copilot
**Horas:** 20-28h

**Capacidades:**
- Descripción de producto desde foto → Texto SEO
- Pricing sugerido basado en mercado local
- Post para redes → Copy + hashtags locales
- Sugerencia oferta flash para stock lento
- Respuesta profesional a reseñas
- Email promocional por ocasión

---

#### Doc 185: ECA Registry Master

**Convención de nomenclatura:**
```
ECA-{DOMINIO}-{NUMERO}
Dominios: USR, ORD, FIN, TEN, AI, WH, MKT, LMS, JOB, BIZ
```

**Flujos Core:**
- `ECA-USR-001` Onboarding Usuario Nuevo
- `ECA-TEN-001` Tenant Onboarding
- `ECA-TEN-002` Stripe Connect Completado
- `ECA-FIN-001` Alerta Churn Spike
- `ECA-FIN-003` Grant Burn Rate Warning

---

### 3.5 Arquitectura (Docs 186, 187)

#### Doc 186: B2B Sales Flow
**Horas:** 16-20h

**Pipeline:**
1. Lead (10%) → 2. MQL (20%) → 3. SQL (40%) → 4. Demo (60%) → 5. Propuesta (75%) → 6. Negociación (85%) → 7. Cerrado (100%)

**BANT Qualification:** Budget, Authority, Need, Timeline

---

#### Doc 187: Scaling Infrastructure
**Horas:** 24-32h

| Fase | Trigger | Arquitectura |
|------|---------|--------------|
| 1 (Actual) | <200 tenants | Single Server (IONOS Dedicated) |
| 2 | 300+ tenants | Separated DB (Master + Replica) |
| 3 | 500+ tenants | Load Balanced (HAProxy + 3 nodes) |

**Backup por Tenant:** Restore individual sin afectar otros via soft multi-tenancy.

---

## 4. Arquitectura Técnica Consolidada

### 4.1 Stack Tecnológico

| Capa | Tecnología | Evaluación |
|------|------------|------------|
| CMS/Backend | Drupal 11 + Commerce 3.x | ✅ BUENO |
| Multi-Tenancy | Group Module (Soft Isolation) | ✅ BUENO |
| Pagos | Stripe Connect (Destination Charges) | ✅ BUENO |
| IA/RAG | Qdrant + Claude API | ✅ BUENO |
| Frontend | SCSS + Design Tokens + Lenis | ✅ BUENO |
| Automatización | Hooks nativos Drupal (no ECA UI) | ✅ BUENO |

### 4.2 Cascada de Configuración (4 Niveles)

```
Nivel 1: Plataforma (Super Admin) → Reglas globales, pasarelas, seguridad
Nivel 2: Vertical (Desarrollador) → Iconografía, tonos, componentes
Nivel 3: Plan (Sistema) → Features habilitados, límites
Nivel 4: Tenant (Admin) → Logo, colores, textos legales
```

### 4.3 Arquitectura Frontend 5 Capas

```
CAPA 5: CSS RUNTIME  ← hook_preprocess_html → :root variables
CAPA 4: CONFIG ENTITY ← tenant_theme_config (BD)
CAPA 3: COMPONENT LIB ← Visual Picker miniaturas
CAPA 2: DESIGN TOKENS ← Panel colores/tipografía
CAPA 1: SCSS/CSS     ← Dart Sass, ADN del tema
```

---

## 5. Flujos UX por Actor

### 5.1 Visitante Anónimo
```
Landing Vertical → Lead Magnet → Registro → Activation (Aha! Moment) → Upgrade
```

### 5.2 Tenant Admin
```
Registro → Wizard 7 pasos → Dashboard específico por avatar → Operación diaria
```

### 5.3 Super Admin SaaS
```
Dashboard Ejecutivo → Tenant Triage → Approvals → FOC Review → Support Escalation
```

---

## 6. Directrices de Implementación

### 6.1 Patrones Obligatorios

| Patrón | Referencia | Obligatoriedad |
|--------|------------|----------------|
| Content Entity Pattern | `00_DIRECTRICES_PROYECTO.md` §5 | ✅ 100% |
| 4 YAML files | routing, menu, task, action | ✅ 100% |
| Entity References (no JSON) | Aprendizaje Page Builder | ✅ 100% |
| Slide-panel modals | Workflow `/slide-panel-modales` | ✅ CRUD frontend |
| Hooks nativos (no ECA UI) | Workflow `/drupal-eca-hooks` | ✅ Automatizaciones |
| SCSS Dart Sass | Variables inyectables | ✅ 100% |
| `jaraba_icon()` | No emojis | ✅ 100% |

### 6.2 Nomenclatura Unificada

| Término | Uso |
|---------|-----|
| Dashboard | Vistas de usuario autenticado |
| Admin Panel | Backoffice administrativo |
| Portal | Interfaces públicas/externas |

### 6.3 AI Integration

> **NUNCA implementar clientes HTTP directos a APIs de IA.**

```php
// ✅ CORRECTO: Usar módulo AI de Drupal
use Drupal\ai\AiProviderPluginManager;

class CopilotOrchestratorService {
    public function __construct(
        private AiProviderPluginManager $aiProvider,
    ) {}
}
```

---

## 7. Frontend Premium: Lenis Integration

### 7.1 Evaluación

| Característica | Beneficio |
|----------------|-----------|
| <4KB tamaño | Performance óptimo |
| position: sticky compatible | Headers con transiciones |
| Touch optimizado | Mobile-first |
| GSAP integration | Animaciones sincronizadas |
| Accesible | WCAG compatible |

### 7.2 Implementación Recomendada

**Esfuerzo:** 8-12h

```javascript
// drupal_lenis.behavior.js
Drupal.behaviors.lenisScroll = {
  attach: function (context) {
    if (context !== document) return;
    
    // Respetar preferencias de usuario
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return;
    }
    
    const lenis = new Lenis({
      duration: 1.2,
      easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
      smooth: true,
      smoothTouch: false,
    });
    
    function raf(time) {
      lenis.raf(time);
      requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);
  }
};
```

### 7.3 Aplicación

- ✅ Homepage y Landing Pages verticales
- ✅ Efectos parallax en hero sections
- ✅ Scroll reveal animations
- ❌ No aplicar en dashboards admin

---

## 8. Roadmap de Ejecución

### Sprint 1-2 (Semanas 1-2): Quick Wins
| Entregable | Horas | Doc |
|------------|-------|-----|
| Tracking infrastructure | 12-16h | 178 |
| Homepage universal + selector | 16-20h | 178 |
| Estandarizar nomenclatura | 4h | Auditoría |
| Merchant Copilot básico | 8h | 184 |

### Sprint 3-4 (Semanas 3-4): Visitor Journey
| Entregable | Horas | Doc |
|------------|-------|-----|
| Lead magnets (5 verticales) | 12-16h | 178 |
| Signup flow + social auth | 12-16h | 178 |
| Landing AgroConecta | 10-12h | 180 |
| Landing ComercioConecta | 10-12h | 180 |

### Sprint 5-6 (Semanas 5-6): Tenant Experience
| Entregable | Horas | Doc |
|------------|-------|-----|
| Onboarding Wizard (7 pasos) | 32-40h | 179 |
| Entity Admin Dashboard | 24-32h | 182 |

### Sprint 7-8 (Semanas 7-8): Admin & Infrastructure
| Entregable | Horas | Doc |
|------------|-------|-----|
| SaaS Admin UX Complete | 24-32h | 181 |
| B2B Sales Flow | 16-20h | 186 |
| Scaling Infrastructure docs | 24-32h | 187 |
| Lenis Integration | 8-12h | - |

---

## 9. Verificación y Métricas

### 9.1 KPIs de Éxito

| Dimensión | Métrica | Target |
|-----------|---------|--------|
| Visitor Journey | Visitor-to-signup rate | >5% |
| Tenant Onboarding | Completion rate | >70% |
| Activation | Time to First Value | <10 min |
| Conversion | Trial-to-paid rate | >25% |
| Retention | NRR | >100% |
| Admin UX | Tiempo Morning Check | <5 min |

### 9.2 Checklist de Validación

- [ ] Todas las landings verticales implementadas
- [ ] Lead magnets funcionando en 5 verticales
- [ ] Onboarding wizard con 70%+ completion
- [ ] Merchant Copilot operativo
- [ ] ECA Registry actualizado
- [ ] Admin Center con Command Palette
- [ ] Entity Admin Dashboard para Elena
- [ ] Modelo freemium definido y triggers activos
- [ ] Lenis integrado en landings
- [ ] Performance tests documentados

---

## Referencias

| Código | Documento | Ubicación |
|--------|-----------|-----------|
| 178-187 | Especificaciones 10/10 | `docs/tecnicos/20260128b-*` |
| Auditoría | UX Ecosystem | `docs/tecnicos/20260128a-*` |
| Directrices | Proyecto | `docs/00_DIRECTRICES_PROYECTO.md` |
| Plan Maestro | v3.0 | `docs/planificacion/20260123-Plan_Maestro_*` |

---

**Jaraba Impact Platform SaaS | Documento Maestro Consolidado v1.0 | Enero 2026**
