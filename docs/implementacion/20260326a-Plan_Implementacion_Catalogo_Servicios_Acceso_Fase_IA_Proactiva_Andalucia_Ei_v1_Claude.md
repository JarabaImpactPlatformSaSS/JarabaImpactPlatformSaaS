# Plan de Implementacion: Catalogo de Servicios, Acceso por Fase e IA Proactiva — Andalucia +ei

**Version:** 1.0.0
**Fecha:** 2026-03-26
**Autor:** Claude Opus 4.6
**Roles:** Arquitecto SaaS, Ingeniero Drupal, Ingeniero UX, Ingeniero IA, Ingeniero SEO, Ingeniero Theming
**Estado:** PENDIENTE DE IMPLEMENTACION
**Prerrequisito:** Auditoria `2026-03-26_Auditoria_Profunda_Andalucia_Ei_Catalogo_Servicios_Integracion_Cross_Vertical_v1.md`
**Modulo principal:** `jaraba_andalucia_ei`
**Modulos afectados:** `ecosistema_jaraba_core`, `jaraba_copilot_v2`, `jaraba_page_builder`, `jaraba_billing`

---

## Indice de Navegacion (TOC)

1. [Objetivos y Alcance](#1-objetivos-y-alcance)
2. [Principios Arquitectonicos](#2-principios-arquitectonicos)
3. [Pre-Implementacion: Checklist de Directrices](#3-pre-implementacion-checklist-de-directrices)
4. [Sprint E — P0: Experiencia Participante Base](#4-sprint-e--p0-experiencia-participante-base)
   - 4.1 [E1: Setup Wizard Participante (8 Steps)](#41-e1-setup-wizard-participante-8-steps)
   - 4.2 [E2: Daily Actions Participante (7 Actions)](#42-e2-daily-actions-participante-7-actions)
   - 4.3 [E3: Conectar Recordatorios Sesion a Cron](#43-e3-conectar-recordatorios-sesion-a-cron)
   - 4.4 [E4: Recordatorio Periodico Firmas Pendientes](#44-e4-recordatorio-periodico-firmas-pendientes)
5. [Sprint F — P1: Diferenciacion de Acceso e IA por Rol](#5-sprint-f--p1-diferenciacion-de-acceso-e-ia-por-rol)
   - 5.1 [F1: Acceso Diferenciado Formacion vs Insercion](#51-f1-acceso-diferenciado-formacion-vs-insercion)
   - 5.2 [F2: Copilot Bridge Orientador](#52-f2-copilot-bridge-orientador)
   - 5.3 [F3: Copilot Bridge Formador](#53-f3-copilot-bridge-formador)
   - 5.4 [F4: Contexto de Pack en Copilot Participante](#54-f4-contexto-de-pack-en-copilot-participante)
   - 5.5 [F5: Enforcement 12 Meses + Avisos Pre-Expiracion](#55-f5-enforcement-12-meses--avisos-pre-expiracion)
6. [Sprint G — P2: Operaciones de Negocio del Participante](#6-sprint-g--p2-operaciones-de-negocio-del-participante)
   - 6.1 [G1: CRM Basico para Clientes del Participante](#61-g1-crm-basico-para-clientes-del-participante)
   - 6.2 [G2: Flujo Facturacion Pack a Clientes](#62-g2-flujo-facturacion-pack-a-clientes)
   - 6.3 [G3: Flujo Cliente Piloto Completo](#63-g3-flujo-cliente-piloto-completo)
   - 6.4 [G4: Plantillas GrapesJS Negocios Locales](#64-g4-plantillas-grapesjs-negocios-locales)
7. [Sprint H — P3: Metodo Impacto y Excelencia](#7-sprint-h--p3-metodo-impacto-y-excelencia)
   - 7.1 [H1: Landing /metodo para jarabaimpact.com](#71-h1-landing-metodo-para-jarabaimpactcom)
   - 7.2 [H2: GroundingProvider para Empleabilidad](#72-h2-groundingprovider-para-empleabilidad)
   - 7.3 [H3: Auto-Respuesta IA a Leads](#73-h3-auto-respuesta-ia-a-leads)
   - 7.4 [H4: Dashboard Metricas Negocio por Pack](#74-h4-dashboard-metricas-negocio-por-pack)
8. [Medidas de Salvaguarda](#8-medidas-de-salvaguarda)
9. [Tabla de Correspondencia: Specs → Archivos](#9-tabla-de-correspondencia-specs--archivos)
10. [Tabla de Cumplimiento de Directrices](#10-tabla-de-cumplimiento-de-directrices)
11. [Verificacion Post-Implementacion (RUNTIME-VERIFY-001)](#11-verificacion-post-implementacion-runtime-verify-001)
12. [Glosario](#12-glosario)

---

## 1. Objetivos y Alcance

### 1.1 Objetivo Principal

Cerrar TODOS los gaps identificados en la auditoria para que cada componente del Catalogo de Servicios del Programa (5 Packs x 3 modalidades) tenga un cauce completo en la plataforma: desde el registro del participante hasta la facturacion a su primer cliente, pasando por formacion guiada, insercion con herramientas de negocio, y asistencia IA proactiva en cada paso.

### 1.2 Alcance

| Dimension | Incluido | Excluido |
|-----------|----------|----------|
| Setup Wizard participante | 8 steps con efecto Zeigarnik | — |
| Daily Actions participante | 7 actions adaptativas por fase | — |
| Acceso diferenciado por fase | 2 alcances (formacion/insercion) | Acceso granular por pack individual |
| IA proactiva orientador + formador | Copilot bridge dedicado | Agentes autonomos Gen 2 por rol |
| Enforcement 12 meses | Cron + avisos + transicion | Cambios en KitDigitalService |
| CRM basico participante | Entity ClienteParticipanteEi | CRM enterprise con pipeline avanzado |
| Facturacion pack a clientes | Activacion Stripe product/price | Facturacion electronica FacturAE |
| Recordatorios sesion/firma | Integracion cron existente | QueueWorker dedicado |
| Plantillas GrapesJS locales | 5 templates por tipo negocio | Templates premium avanzados |
| Copilot contexto pack | Inyeccion pack en prompt | Modos copilot nuevos |

### 1.3 Dependencias

| Dependencia | Modulo | Estado |
|-------------|--------|--------|
| SetupWizardRegistry + CompilerPass | ecosistema_jaraba_core | Implementado |
| DailyActionsRegistry + CompilerPass | ecosistema_jaraba_core | Implementado |
| EiMultichannelNotificationService | jaraba_andalucia_ei | Implementado |
| ProgramaVerticalAccessService | jaraba_andalucia_ei | Implementado (modificar) |
| AndaluciaEiCopilotBridgeService | jaraba_andalucia_ei | Implementado (extender) |
| CopilotPhaseConfigService | jaraba_andalucia_ei | Implementado |
| PackServicioEi entity | jaraba_andalucia_ei | Implementado |
| Stripe Connect | jaraba_billing | Implementado |
| GrapesJS template registry | jaraba_page_builder | Implementado |

---

## 2. Principios Arquitectonicos

### 2.1 Directrices Cardinales (de CLAUDE.md)

Cada linea de codigo DEBE cumplir estas directrices sin excepcion:

1. **TENANT-001**: TODA query filtra por tenant_id
2. **OPTIONAL-CROSSMODULE-001**: Referencias cross-modulo con `@?` en services.yml
3. **PREMIUM-FORMS-PATTERN-001**: Toda entity form extiende PremiumEntityFormBase
4. **CONTROLLER-READONLY-001**: Sin `protected readonly` en constructor promotion para propiedades heredadas
5. **CSS-VAR-ALL-COLORS-001**: CADA color es `var(--ej-*, fallback)`, NUNCA hex hardcoded
6. **ICON-CONVENTION-001**: Iconos via `jaraba_icon('category', 'name', { variant, color, size })`
7. **TWIG-INCLUDE-ONLY-001**: `{% include ... only %}` para parciales
8. **ROUTE-LANGPREFIX-001**: URLs via `Url::fromRoute()` con try-catch en preprocess
9. **ZERO-REGION-001**: Variables y drupalSettings via hook_preprocess_page()
10. **SLIDE-PANEL-RENDER-002**: Formularios con `_controller:` para slide-panel, NUNCA `_form:`

### 2.2 Patron Frontend Limpio

Toda pagina frontend del participante sigue el patron Zero Region:

```
page--participante-{ruta}.html.twig
  └── {{ clean_content }}  (NO {{ page.content }})
  └── {{ clean_messages }}
  └── {% include 'partials/_header.html.twig' with {...} only %}
  └── {% include 'partials/_participante-{seccion}.html.twig' with {...} only %}
  └── {% include 'partials/_footer.html.twig' with {...} only %}
```

- Body classes via `hook_preprocess_html()` (NUNCA `attributes.addClass()` en template)
- Acciones crear/editar/ver en slide-panel (no navegar fuera)
- Layout full-width, mobile-first
- Textos SIEMPRE traducibles: `{% trans %}texto{% endtrans %}` (bloque, NO filtro `|t`)

### 2.3 Patron SCSS

```scss
// Cada parcial SCSS DEBE incluir:
@use '../variables' as *;

// Colores SIEMPRE con tokens:
.component {
  background: var(--ej-color-surface-primary, #FFFFFF);
  color: var(--ej-color-text-primary, #233D63);
  border: 1px solid var(--ej-color-border-default, #E2E8F0);
}

// Alpha con color-mix (NO rgba):
.overlay {
  background: color-mix(in srgb, var(--ej-color-azul-corporativo) 10%, transparent);
}
```

Compilacion: `npm run build` desde `web/themes/custom/ecosistema_jaraba_theme/`

### 2.4 Patron Servicios Tagged

Setup Wizard y Daily Actions usan servicios tagged con CompilerPass:

```yaml
# En jaraba_andalucia_ei.services.yml:
jaraba_andalucia_ei.wizard_step.participante.completar_perfil:
  class: Drupal\jaraba_andalucia_ei\SetupWizard\Participante\CompletarPerfilStep
  tags:
    - { name: ecosistema_jaraba_core.setup_wizard_step, dashboard: participante_ei, weight: 10 }
  arguments:
    - '@entity_type.manager'
    - '@?jaraba_andalucia_ei.acceso_programa'
```

---

## 3. Pre-Implementacion: Checklist de Directrices

Antes de escribir codigo, verificar:

- [ ] Leer `docs/00_DIRECTRICES_PROYECTO.md` secciones: Entity conventions, Frontend rules, Icon directives, SCSS directives
- [ ] Leer `docs/00_FLUJO_TRABAJO_CLAUDE.md` secciones: Setup Wizard Registry, Daily Actions Registry, Premium Forms
- [ ] Leer `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` secciones: 5-Layer Cascade, CSS Custom Properties
- [ ] Verificar que no existe duplicidad con servicios existentes (`grep -r "class.*Step.*implements" web/modules/custom/jaraba_andalucia_ei/`)
- [ ] Verificar parciales Twig existentes antes de crear nuevos (`ls web/modules/custom/jaraba_andalucia_ei/templates/partials/`)
- [ ] Verificar libraries.yml para evitar duplicados
- [ ] Verificar services.yml existente para evitar colisiones de ID

---

## 4. Sprint E — P0: Experiencia Participante Base

### 4.1 E1: Setup Wizard Participante (8 Steps)

**Objetivo:** Convertir el portal del participante de dashboard pasivo a experiencia guiada con efecto Zeigarnik (ZEIGARNIK-PRELOAD-001: arranca al 33-50%).

**Logica de negocio:** El participante PIIL es frecuentemente una persona en situacion de vulnerabilidad. Necesita guia clara de que hacer en cada momento. El wizard se adapta a la fase actual: solo muestra steps relevantes para la fase en curso.

#### Archivos a Crear

| Archivo | Proposito |
|---------|-----------|
| `src/SetupWizard/Participante/CompletarPerfilStep.php` | Step 1: Datos personales |
| `src/SetupWizard/Participante/FirmarAcuerdoStep.php` | Step 2: Acuerdo participacion |
| `src/SetupWizard/Participante/FirmarDaciStep.php` | Step 3: DACI |
| `src/SetupWizard/Participante/CompletarDimeStep.php` | Step 4: Diagnostico DIME |
| `src/SetupWizard/Participante/SeleccionarPackStep.php` | Step 5: Preseleccionar packs |
| `src/SetupWizard/Participante/PrimeraSesionStep.php` | Step 6: Primera sesion |
| `src/SetupWizard/Participante/ConfirmarPackStep.php` | Step 7: Confirmar pack |
| `src/SetupWizard/Participante/PublicarPackStep.php` | Step 8: Publicar en catalogo |

#### Especificacion por Step

**Step 1: CompletarPerfilStep**

```php
// Datos del step:
// - ID: 'participante_ei.completar_perfil'
// - Dashboard: 'participante_ei'
// - Weight: 10 (primero)
// - Titulo: 'Completa tu perfil'
// - Descripcion: 'Rellena tus datos personales para que podamos personalizar tu itinerario'
// - Icono: jaraba_icon('user', 'profile', { variant: 'duotone', color: 'azul-corporativo' })
// - Ruta CTA: 'jaraba_andalucia_ei.participante_portal' (slide-panel edicion perfil)
//
// Condicion completado:
// El participante tiene rellenados: colectivo, provincia_participacion, nivel_digital
//
// Fase aplicable: acogida
```

**Step 2: FirmarAcuerdoStep**

```php
// - ID: 'participante_ei.firmar_acuerdo'
// - Weight: 20
// - Titulo: 'Firma el Acuerdo de Participacion'
// - Descripcion: 'Documento bilateral obligatorio para formalizar tu participacion en el programa'
// - Icono: jaraba_icon('document', 'signature', { variant: 'duotone', color: 'naranja-impulso' })
// - Ruta CTA: 'jaraba_andalucia_ei.firma_documento' (slide-panel firma)
//
// Condicion completado: $participante->isAcuerdoParticipacionFirmado()
// Fase aplicable: acogida
```

**Step 3: FirmarDaciStep**

```php
// - ID: 'participante_ei.firmar_daci'
// - Weight: 30
// - Titulo: 'Firma el DACI'
// - Descripcion: 'Declaracion de Aceptacion de Compromisos Individual — proteccion de tus datos'
// - Icono: jaraba_icon('document', 'shield', { variant: 'duotone', color: 'verde-innovacion' })
// - Ruta CTA: 'jaraba_andalucia_ei.firma_documento'
//
// Condicion completado: $participante->isDaciFirmado()
// Fase aplicable: acogida
```

**Step 4: CompletarDimeStep**

```php
// - ID: 'participante_ei.completar_dime'
// - Weight: 40
// - Titulo: 'Completa tu diagnostico DIME'
// - Descripcion: 'Test de madurez emprendedora que nos ayuda a asignarte el mejor itinerario'
// - Icono: jaraba_icon('assessment', 'diagnostic', { variant: 'duotone', color: 'azul-corporativo' })
// - Ruta CTA: 'jaraba_copilot_v2.copilot_page' (con contexto DIME)
//
// Condicion completado: $participante->get('dime_score')->value !== NULL
// Fase aplicable: diagnostico
```

**Step 5: SeleccionarPackStep**

```php
// - ID: 'participante_ei.seleccionar_pack'
// - Weight: 50
// - Titulo: 'Preselecciona tus packs de servicio'
// - Descripcion: 'Elige 2-3 packs que te interesen. Los exploraras en profundidad durante la formacion'
// - Icono: jaraba_icon('business', 'package', { variant: 'duotone', color: 'naranja-impulso' })
// - Ruta CTA: 'jaraba_andalucia_ei.catalogo_publico' (con filtro participante)
//
// Condicion completado: !empty($participante->get('pack_preseleccionado')->value)
// Fase aplicable: diagnostico, atencion
```

**Step 6: PrimeraSesionStep**

```php
// - ID: 'participante_ei.primera_sesion'
// - Weight: 60
// - Titulo: 'Asiste a tu primera sesion formativa'
// - Descripcion: 'Tu primer paso en la formacion del programa. Consulta tu calendario de sesiones'
// - Icono: jaraba_icon('calendar', 'event', { variant: 'duotone', color: 'verde-innovacion' })
// - Ruta CTA: 'jaraba_andalucia_ei.participante_portal' (seccion #mis_sesiones)
//
// Condicion completado: (float)$participante->get('horas_formacion')->value > 0
// Fase aplicable: atencion
```

**Step 7: ConfirmarPackStep**

```php
// - ID: 'participante_ei.confirmar_pack'
// - Weight: 70
// - Titulo: 'Confirma tu pack definitivo'
// - Descripcion: 'Tras validar tu idea en el Modulo 1, elige el pack con el que lanzaras tu negocio'
// - Icono: jaraba_icon('business', 'check-circle', { variant: 'duotone', color: 'verde-innovacion' })
// - Ruta CTA: (slide-panel seleccion pack)
//
// Condicion completado: !empty($participante->get('pack_confirmado')->value)
// Fase aplicable: atencion (post-Modulo 1)
```

**Step 8: PublicarPackStep**

```php
// - ID: 'participante_ei.publicar_pack'
// - Weight: 80
// - Titulo: 'Publica tu pack en el catalogo'
// - Descripcion: 'Tu servicio visible para clientes potenciales. El primer paso hacia tu primer cliente'
// - Icono: jaraba_icon('marketing', 'launch', { variant: 'duotone', color: 'naranja-impulso' })
// - Ruta CTA: 'jaraba_andalucia_ei.catalogo_publico'
//
// Condicion completado: PackServicioEi del participante con publicado = TRUE
// Fase aplicable: insercion
```

#### Integracion en ParticipantePortalController

**Archivo a modificar:** `src/Controller/ParticipantePortalController.php`

Anadir inyeccion de `SetupWizardRegistry` y `DailyActionsRegistry` via constructor (con `@?` por ser cross-modulo en ecosistema_jaraba_core). En el metodo `portal()`, anadir:

```php
// Resolver wizard y daily actions para participante_ei.
$setupWizard = NULL;
if ($this->setupWizardRegistry) {
  $setupWizard = $this->setupWizardRegistry->getWizardData('participante_ei', $contextId);
}

$dailyActions = NULL;
if ($this->dailyActionsRegistry) {
  $dailyActions = $this->dailyActionsRegistry->getActionsData('participante_ei', $contextId);
}
```

Anadir al render array:
```php
'#setup_wizard' => $setupWizard,
'#daily_actions' => $dailyActions,
```

**Archivo a modificar:** `jaraba_andalucia_ei.module` — anadir variables en `hook_theme()` para `participante_portal`:
```php
'setup_wizard' => NULL,
'daily_actions' => NULL,
```

**Archivo a modificar:** `templates/participante-portal.html.twig` — insertar secciones:
```twig
{# Setup Wizard — guia paso a paso #}
{% if setup_wizard and setup_wizard.steps|length > 0 %}
  {% include '@ecosistema_jaraba_theme/partials/_setup-wizard.html.twig' with {
    wizard: setup_wizard
  } only %}
{% endif %}

{# Daily Actions — acciones del dia #}
{% if daily_actions and daily_actions.actions|length > 0 %}
  {% include '@ecosistema_jaraba_theme/partials/_daily-actions.html.twig' with {
    actions: daily_actions.actions
  } only %}
{% endif %}
```

#### Registro en services.yml

Anadir 8 entradas en `jaraba_andalucia_ei.services.yml` con tag `ecosistema_jaraba_core.setup_wizard_step` y dashboard `participante_ei`. Cada step recibe `@entity_type.manager` y `@?jaraba_andalucia_ei.acceso_programa` como argumentos minimos.

#### PIPELINE-E2E-001 Verificacion

Para cada step, verificar las 4 capas:
1. **L1 Service:** Step class registrado como servicio tagged
2. **L2 Controller:** `ParticipantePortalController::portal()` pasa `#setup_wizard`
3. **L3 hook_theme:** Variable `setup_wizard` declarada
4. **L4 Template:** `{% include '_setup-wizard.html.twig' ... %}` presente

### 4.2 E2: Daily Actions Participante (7 Actions)

**Objetivo:** Dar al participante acciones concretas y priorizadas cada dia, adaptadas a su fase y pack.

#### Archivos a Crear

| Archivo | Proposito | Fase |
|---------|-----------|------|
| `src/DailyAction/Participante/MisSesionesHoyAction.php` | Sesiones hoy | Todas |
| `src/DailyAction/Participante/FirmasPendientesAction.php` | Firmas pendientes | Todas |
| `src/DailyAction/Participante/EntregablesPendientesAction.php` | Entregables por completar | atencion+ |
| `src/DailyAction/Participante/ChatCopilotAction.php` | Hablar con mentor IA | Todas |
| `src/DailyAction/Participante/ProgresoFormacionAction.php` | Progreso formativo | atencion+ |
| `src/DailyAction/Participante/GestionarClientesAction.php` | CRM clientes | insercion+ |
| `src/DailyAction/Participante/FacturarClienteAction.php` | Facturar a cliente | insercion+ |

#### Logica de Visibilidad por Fase

Cada action implementa `isVisible(string $dashboardId, mixed $contextId): bool` que consulta la fase actual del participante:

```php
// MisSesionesHoyAction — visible en TODAS las fases activas
public function isVisible(string $dashboardId, mixed $contextId): bool {
  return $this->isParticipanteActivo($contextId);
}

// GestionarClientesAction — visible SOLO en insercion y seguimiento
public function isVisible(string $dashboardId, mixed $contextId): bool {
  $participante = $this->getParticipante($contextId);
  return $participante && in_array($participante->getFaseActual(), ['insercion', 'seguimiento']);
}
```

#### Especificacion MisSesionesHoyAction

```php
// - ID: 'participante_ei.sesiones_hoy'
// - Dashboard: 'participante_ei'
// - Weight: 10 (primera accion)
// - Titulo: 'Mis sesiones de hoy'
// - Icono: jaraba_icon('calendar', 'today', { variant: 'duotone', color: 'azul-corporativo' })
// - Badge: count de sesiones inscritas con fecha = hoy
// - Ruta: 'jaraba_andalucia_ei.participante_portal' (scroll a #mis-sesiones)
// - Color variant: 'azul-corporativo'
//
// getBadgeCount(): Consulta inscripcion_sesion_ei WHERE participante_id AND
//   sesion_programada_ei.fecha = TODAY AND estado = 'confirmada'
```

#### Especificacion FirmasPendientesAction

```php
// - ID: 'participante_ei.firmas_pendientes'
// - Weight: 15
// - Titulo: 'Firmas pendientes'
// - Icono: jaraba_icon('document', 'pen', { variant: 'duotone', color: 'naranja-impulso' })
// - Badge: count de expediente_documento WHERE uid = current AND estado = 'pendiente_firma'
// - Ruta: 'jaraba_andalucia_ei.participante_portal' (scroll a #firmas)
// - Color variant: badge > 0 ? 'naranja-impulso' : 'neutral'
```

#### Especificacion EntregablesPendientesAction

```php
// - ID: 'participante_ei.entregables_pendientes'
// - Weight: 20
// - Titulo: 'Entregables por completar'
// - Icono: jaraba_icon('education', 'assignment', { variant: 'duotone', color: 'verde-innovacion' })
// - Badge: count de entregable_formativo_ei WHERE participante_id AND estado = 'pendiente'
// - Visible: fase IN ['atencion', 'insercion', 'seguimiento']
```

#### Especificacion ChatCopilotAction

```php
// - ID: 'participante_ei.chat_copilot'
// - Weight: 25
// - Titulo: 'Habla con tu mentor IA'
// - Icono: jaraba_icon('ai', 'copilot', { variant: 'duotone', color: 'azul-corporativo' })
// - Badge: ninguno
// - Ruta: 'jaraba_copilot_v2.copilot_page'
// - Visible: SIEMPRE (todas las fases)
```

#### Especificacion GestionarClientesAction (solo insercion+)

```php
// - ID: 'participante_ei.gestionar_clientes'
// - Weight: 50
// - Titulo: 'Gestionar mis clientes'
// - Icono: jaraba_icon('business', 'clients', { variant: 'duotone', color: 'verde-innovacion' })
// - Badge: count de cliente_participante_ei WHERE participante_id
// - Visible: fase IN ['insercion', 'seguimiento']
// - Ruta: (nueva ruta CRM basico participante, Sprint G)
```

### 4.3 E3: Conectar Recordatorios Sesion a Cron

**Objetivo:** Los participantes reciben notificacion push + WhatsApp 24h y 1h antes de cada sesion programada.

**Archivo a modificar:** `jaraba_andalucia_ei.module`, funcion `jaraba_andalucia_ei_cron()`

**Logica:** Anadir un timer independiente de 1 hora (3600s) que invoca `enviarRecordatoriosSesion()` con ambas ventanas. El metodo ya filtra por tenant y ventana temporal, asi que basta invocarlo para cada tenant activo.

```php
// Dentro de jaraba_andalucia_ei_cron(), DESPUES del bloque de alertas normativas:

// Recordatorios de sesion (cada hora).
$lastReminder = $state->get('jaraba_andalucia_ei.reminder_last_run', 0);
if ($now - $lastReminder >= 3600) {
  $state->set('jaraba_andalucia_ei.reminder_last_run', $now);

  if (\Drupal::hasService('jaraba_andalucia_ei.ei_multichannel_notification')) {
    try {
      $notifService = \Drupal::service('jaraba_andalucia_ei.ei_multichannel_notification');
      // Obtener tenant IDs activos del programa.
      $tenantIds = _jaraba_andalucia_ei_get_active_tenant_ids();
      foreach ($tenantIds as $tenantId) {
        $notifService->enviarRecordatoriosSesion($tenantId, 24);
        $notifService->enviarRecordatoriosSesion($tenantId, 1);
      }
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_andalucia_ei')->error(
        'Error en recordatorios sesion: @msg', ['@msg' => $e->getMessage()]
      );
    }
  }
}
```

**Helper a crear:** `_jaraba_andalucia_ei_get_active_tenant_ids()` — consulta programa_participante_ei con fase_actual IN fases_activas, DISTINCT tenant_id.

**Impacto:** ~30 lineas de codigo. Activa funcionalidad que ya existe pero estaba desconectada.

### 4.4 E4: Recordatorio Periodico Firmas Pendientes

**Objetivo:** Participantes con firmas pendientes >24h reciben recordatorio diario (no solo 1 vez al cambiar estado).

**Archivo a modificar:** `jaraba_andalucia_ei.module`, funcion `jaraba_andalucia_ei_cron()`

**Logica:** Timer de 24 horas que busca expediente_documento con estado = 'pendiente_firma' y changed < now - 24h. Para cada documento, envia notificacion al participante.

```php
// Recordatorio firmas pendientes (cada 24h).
$lastFirmaReminder = $state->get('jaraba_andalucia_ei.firma_reminder_last_run', 0);
if ($now - $lastFirmaReminder >= 86400) {
  $state->set('jaraba_andalucia_ei.firma_reminder_last_run', $now);

  if (\Drupal::hasService('jaraba_andalucia_ei.firma_workflow')) {
    try {
      $firmaService = \Drupal::service('jaraba_andalucia_ei.firma_workflow');
      $firmaService->enviarRecordatoriosFirmasPendientes();
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_andalucia_ei')->error(
        'Error recordatorios firmas: @msg', ['@msg' => $e->getMessage()]
      );
    }
  }
}
```

**Metodo a crear en FirmaWorkflowService:** `enviarRecordatoriosFirmasPendientes()` — busca documentos con estado pendiente_firma y changed > 24h, envia via EiMultichannelNotificationService tipo `firma_pendiente`.

---

## 5. Sprint F — P1: Diferenciacion de Acceso e IA por Rol

### 5.1 F1: Acceso Diferenciado Formacion vs Insercion

**Objetivo:** Participantes en fase insercion acceden a herramientas de negocio que no estan disponibles en formacion.

**Archivo a modificar:** `src/Service/ProgramaVerticalAccessService.php`

**Cambios clave:**

1. Anadir constantes de fases por alcance:

```php
private const FASES_FORMACION = ['acogida', 'diagnostico', 'atencion'];
private const FASES_INSERCION = ['insercion', 'seguimiento'];
```

2. Anadir constante de features por alcance:

```php
// Features adicionales desbloqueadas en insercion.
private const FEATURES_INSERCION = [
  'crm_clientes',          // CRM basico para gestionar clientes propios
  'facturacion_clientes',   // Emitir facturas via Stripe
  'pack_publicacion',       // Publicar pack en catalogo
  'jarabalex_completo',     // Acceso completo a tramites Hacienda
  'comercioconecta_tienda', // Tienda digital operativa
  'dashboard_negocio',      // Metricas de negocio por pack
  'firma_clientes',         // Firma digital para contratos con clientes
  'ical_export',            // Exportacion calendario profesional
];
```

3. Nuevo metodo `hasFeatureAccess(int $uid, string $feature): bool`:

```php
public function hasFeatureAccess(int $uid, string $feature): bool {
  // Features basicas: siempre disponibles en fases activas.
  if (!in_array($feature, self::FEATURES_INSERCION, TRUE)) {
    return $this->hasAccess($uid, $feature);
  }

  // Features de insercion: solo en fases insercion/seguimiento.
  $participante = $this->getParticipanteActivo($uid);
  if (!$participante) {
    return FALSE;
  }

  $fase = $participante->getFaseActual();
  return in_array($fase, self::FASES_INSERCION, TRUE) && !$this->isExpired($uid);
}
```

4. Metodo `getAvailableFeatures(int $uid): array` para el portal:

```php
public function getAvailableFeatures(int $uid): array {
  $participante = $this->getParticipanteActivo($uid);
  if (!$participante) {
    return [];
  }

  $fase = $participante->getFaseActual();
  $features = ['copilot', 'portfolio', 'expediente', 'sesiones', 'formacion'];

  // Features de formacion (atencion+).
  if (in_array($fase, ['atencion', 'insercion', 'seguimiento'])) {
    $features = array_merge($features, ['lean_canvas', 'calculadora_pe', 'jarabalex_basico', 'grapesjs_formativo', 'content_hub_formativo']);
  }

  // Features de insercion.
  if (in_array($fase, self::FASES_INSERCION)) {
    $features = array_merge($features, self::FEATURES_INSERCION);
  }

  return $features;
}
```

**Integracion en ParticipantePortalController:** Pasar `#available_features` al render array. El template muestra/oculta secciones segun features disponibles.

**Integracion en Daily Actions:** `GestionarClientesAction` y `FacturarClienteAction` usan `hasFeatureAccess()` en su `isVisible()`.

### 5.2 F2: Copilot Bridge Orientador

**Archivo a modificar:** `src/Service/AndaluciaEiCopilotBridgeService.php`

**Cambio:** Anadir rama `isOrientador()` en `getRelevantContext()`:

```php
public function getRelevantContext(int $userId): array {
  if ($this->isCoordinador($userId)) {
    return $this->getCoordinadorContext($userId);
  }

  if ($this->isOrientador($userId)) {
    return $this->getOrientadorContext($userId);
  }

  if ($this->isFormador($userId)) {
    return $this->getFormadorContext($userId);
  }

  // Participante (existente).
  if ($this->contextProvider) {
    return $this->getPiilParticipantContext();
  }

  return $this->getGenericRequestContext($userId);
}
```

**Nuevo metodo getOrientadorContext():**

Proporciona al copilot:
- System prompt: "Eres el asistente de orientacion laboral del orientador. Tu rol es ayudar a planificar sesiones, evaluar competencias, detectar riesgos de abandono y preparar informes de progreso."
- Contexto: participantes_asignados (count por fase), sesiones_hoy (count + detalle), horas_acumuladas (orientacion individual + grupal), alertas_normativas (count criticas/altas de sus participantes), participantes_en_riesgo (list)
- Modos permitidos: orientacion_individual, orientacion_grupal, evaluacion_competencias, insercion_laboral, informe_progreso
- Soft suggestion contextual: "Hoy tienes [N] sesiones. [Nombre] tiene riesgo medio de abandono — no ha asistido a las ultimas 2 sesiones."

### 5.3 F3: Copilot Bridge Formador

**Archivo a modificar:** `src/Service/AndaluciaEiCopilotBridgeService.php`

**Nuevo metodo getFormadorContext():**

Proporciona al copilot:
- System prompt: "Eres el asistente pedagogico del formador. Tu rol es ayudar a preparar sesiones, crear materiales didacticos, evaluar entregables y monitorear la asistencia."
- Contexto: sesiones_hoy (count + detalle), materiales_pendientes (count), asistencias_sin_marcar (count de sesiones pasadas sin asistencia registrada), acciones_formativas_asignadas (list), entregables_por_validar (count)
- Modos permitidos: preparacion_sesion, evaluacion_entregable, material_didactico, asistencia
- Soft suggestion: "Tienes sesion [titulo] hoy a las [hora]. Faltan [N] asistencias por marcar de sesiones anteriores."

### 5.4 F4: Contexto de Pack en Copilot Participante

**Archivo a modificar:** `src/Service/AndaluciaEiCopilotContextProvider.php`

**Cambio:** Anadir informacion del pack confirmado al contexto:

```php
// En getContext():
$packInfo = $this->getPackContext($participante);
if ($packInfo) {
  $context['pack_confirmado'] = $packInfo;
  $context['_system_prompt_addition'] .= "\n\nEl participante tiene Pack " . $packInfo['tipo'] .
    " (" . $packInfo['modalidad'] . "). Adapta tus respuestas a este contexto de negocio.";
}
```

**Nuevo metodo getPackContext():**

Busca PackServicioEi del participante, devuelve: tipo, modalidad, precio_mensual, publicado, clientes_count (si CRM existe).

### 5.5 F5: Enforcement 12 Meses + Avisos Pre-Expiracion

**Archivos a crear:**

| Archivo | Proposito |
|---------|-----------|
| `src/Service/BonoProgramaExpiryService.php` | Calcula expiracion, envia avisos, gestiona transicion |

**Logica del servicio:**

```php
class BonoProgramaExpiryService {
  private const MESES_PROGRAMA = 12;
  private const AVISOS_DIAS = [60, 30, 15, 7, 3, 1];

  public function getExpiracionProgramada(ProgramaParticipanteEiInterface $p): ?\DateTimeImmutable {
    $inicio = $p->get('fecha_inicio_programa')->value;
    if (!$inicio) {
      $inicio = $p->getCreatedTime();
    }
    return (new \DateTimeImmutable('@' . $inicio))->modify('+' . self::MESES_PROGRAMA . ' months');
  }

  public function getDiasRestantes(ProgramaParticipanteEiInterface $p): int {
    $expiracion = $this->getExpiracionProgramada($p);
    $now = new \DateTimeImmutable();
    return max(0, (int) $now->diff($expiracion)->days * ($expiracion > $now ? 1 : -1));
  }

  public function evaluarAvisos(): int {
    // Busca participantes activos cuya expiracion coincide con AVISOS_DIAS.
    // Envia notificacion tipo 'bono_expiracion_Xd' via EiMultichannelNotificationService.
    // Retorna count de avisos enviados.
  }

  public function ejecutarExpiraciones(): int {
    // Busca participantes con dias_restantes <= 0 y aun en fase activa.
    // Transiciona a plan free (limita features).
    // Envia sugerencia de upgrade via copilot soft suggestion.
    // Retorna count de expiraciones procesadas.
  }
}
```

**Integracion en cron:** Timer de 24h, invoca `evaluarAvisos()` y `ejecutarExpiraciones()`.

**Integracion en portal:** Mostrar banner "Te quedan X dias de acceso completo" cuando dias < 30.

---

## 6. Sprint G — P2: Operaciones de Negocio del Participante

### 6.1 G1: CRM Basico para Clientes del Participante

**Objetivo:** El participante en fase insercion puede gestionar sus propios clientes (los negocios a los que vende sus packs).

**Entity a crear:** `ClienteParticipanteEi`

**Campos:**
- `participante_id` (entity_reference → programa_participante_ei, required)
- `nombre_negocio` (string, max 255, required, label)
- `nombre_contacto` (string, max 255)
- `email` (email)
- `telefono` (string, max 20)
- `sector` (list_string: hosteleria, comercio, profesional, agro, salud, educacion, turismo, servicios)
- `pack_contratado` (list_string: 5 tipos de pack)
- `modalidad` (list_string: basico, estandar, premium)
- `precio_mensual` (decimal)
- `estado` (list_string: prospecto, piloto, activo, pausado, baja)
- `fecha_inicio` (datetime)
- `notas` (text_long)
- `es_piloto` (boolean, default FALSE)
- `tenant_id` (entity_reference → group)
- `uid` (owner)
- `created`, `changed`

**AccessControlHandler:** `ClienteParticipanteEiAccessControlHandler` — verifica que uid actual es el participante propietario O tiene permisos de coordinador.

**Ruta:** `/participante/mis-clientes` — listado de clientes con filtros por estado.

**Slide-panel:** Crear/editar cliente sin abandonar la pagina.

**Relacion con NegocioProspectadoEi:** Son entidades diferentes. `NegocioProspectadoEi` es para la prospeccion empresarial del PROGRAMA (leads para dar al participante). `ClienteParticipanteEi` es para los clientes PROPIOS del participante una vez insertado. Un `NegocioProspectadoEi` puede convertirse en `ClienteParticipanteEi` cuando el participante cierra la venta.

### 6.2 G2: Flujo Facturacion Pack a Clientes

**Objetivo:** El participante puede facturar a sus clientes directamente desde la plataforma.

**Logica:** Cuando el participante publica su PackServicioEi, se crea automaticamente un Stripe Product + Price en la cuenta conectada del tenant. El participante puede enviar enlace de pago o activar suscripcion recurrente.

**Servicio a crear:** `PackBillingActivationService`

**Metodos:**
- `activarBillingPack(PackServicioEiInterface $pack): void` — crea Stripe product/price, actualiza campos
- `generarEnlacePago(int $clienteId, int $packId): string` — genera Stripe Checkout session
- `activarSuscripcionRecurrente(int $clienteId, int $packId): void` — crea Stripe subscription

**Integracion con Stripe Connect:** Usa destination charges (STRIPE-ENV-UNIFY-001). El participante opera bajo el tenant del programa, con PED S.L. como platform account.

### 6.3 G3: Flujo Cliente Piloto Completo

**Objetivo:** Orquestar el flujo completo: prospeccion → matching → acuerdo piloto → ejecucion → conversion.

**Servicio a crear:** `PilotClientFlowService`

**Flujo:**
1. `NegocioProspectadoEi` en fase `acuerdo` → crear `ClienteParticipanteEi` con `es_piloto = TRUE`
2. Generar documento "Acuerdo Cliente Piloto" via `FirmaWorkflowService`
3. Durante piloto (2-4 semanas): tracking de entregables via `EntregableFormativoEi`
4. Al finalizar piloto: solicitar feedback (nuevo campo `feedback_piloto` en `ClienteParticipanteEi`)
5. Conversion: cambiar estado de `piloto` a `activo`, activar billing

### 6.4 G4: Plantillas GrapesJS Negocios Locales

**Objetivo:** El participante de Pack 3 (Presencia Online) puede crear la web de su cliente usando plantillas predefinidas.

**Plantillas a crear en jaraba_page_builder Template Registry:**

| Template ID | Tipo Negocio | Paginas | Elementos Clave |
|-------------|-------------|---------|-----------------|
| `local-restaurant` | Restaurante/Bar | Home, Menu, Reservas, Sobre, Contacto | Schema.org Restaurant, menu PDF, Google Maps |
| `local-shop` | Comercio/Tienda | Home, Productos, Ofertas, Sobre, Contacto | Schema.org LocalBusiness, catalogo basico |
| `local-professional` | Profesional (abogado, medico) | Home, Servicios, Equipo, Blog, Contacto | Schema.org ProfessionalService, FAQ |
| `local-beauty` | Peluqueria/Estetica | Home, Servicios, Precios, Galeria, Contacto | Schema.org HealthAndBeautyBusiness, galeria |
| `local-artisan` | Artesano/Taller | Home, Portfolio, Proceso, Tienda, Contacto | Schema.org LocalBusiness, galeria portfolio |

**Cada plantilla incluye:**
- 3-5 paginas con contenido placeholder en espanol
- Iconos via `jaraba_icon()` con colores de marca
- Schema.org LocalBusiness optimizado para SEO local
- Google Maps embed configurable
- Formulario de contacto integrado
- Responsive mobile-first
- Variables CSS inyectables desde Theme Settings del tenant

---

## 7. Sprint H — P3: Metodo Impacto y Excelencia

### 7.1 H1: Landing /metodo para jarabaimpact.com

**Objetivo:** Crear landing page publica `/metodo` que explique el Metodo Impacto Jaraba como sistema replicable via certificaciones y franquicias.

**Template:** `page--metodo.html.twig` (Zero Region, frontend limpio)

**Secciones:**
1. Hero: "El Metodo Impacto Jaraba — Tu Plan de Transformacion Digital en 90 Dias"
2. 3 Fases: Diagnostico → Implementacion → Optimizacion
3. 5 Packs como modulos del metodo
4. Resultados: caso PED S.L. como prueba
5. 3 Niveles de certificacion: Consultor, Partner, Franquicia
6. CTA: "Solicita informacion" → contacto

**Controller:** En `jaraba_page_builder` o `ecosistema_jaraba_theme` (ruta publica configurable por metasitio).

**Consistencia MARKETING-TRUTH-001:** Solo mostrar niveles de certificacion que existan como entidades en el sistema. Inicialmente, mostrar como "Proximamente" con formulario de interes.

### 7.2 H2: GroundingProvider para Empleabilidad

**Archivo a crear:** `web/modules/custom/jaraba_candidate/src/Service/CandidateGroundingProvider.php`

**Logica:** Indexa CandidateProfile entities para busqueda semantica del copilot. Devuelve ofertas de empleo, perfiles de candidatos y recomendaciones de formacion.

**Tag:** `jaraba_copilot_v2.grounding_provider` con priority 70.

### 7.3 H3: Auto-Respuesta IA a Leads

**Objetivo:** Cuando un lead entra via `NegocioProspectadoEi` o `CopilotLeadCaptureService`, generar respuesta automatica en <5 minutos.

**Servicio a crear:** `LeadAutoResponderService`

**Flujo:**
1. Hook insert de `NegocioProspectadoEi` o evento `copilot_lead_captured`
2. Crear tarea en cola `andalucia_ei_lead_response`
3. QueueWorker: generar respuesta personalizada con IA (modelo fast/Haiku)
4. Enviar por email + notificacion al coordinador
5. Registrar en CRM como actividad

### 7.4 H4: Dashboard Metricas Negocio por Pack

**Objetivo:** El participante en insercion ve un dashboard con metricas de su negocio: clientes, facturacion, horas trabajadas, rentabilidad.

**Ruta:** `/participante/mi-negocio`

**Template:** `page--participante-mi-negocio.html.twig` (Zero Region)

**Secciones:**
- KPIs: clientes_activos, facturacion_mensual, horas_mes, precio_hora_efectivo
- Grafico: evolucion mensual ingresos
- Tabla: clientes con estado y ultimo pago
- Calculadora PE actualizada con datos reales
- CTA: "Mejorar mi pack" / "Anadir segundo pack"

---

## 8. Medidas de Salvaguarda

### 8.1 Validadores a Crear

| Script | Proposito | Checks |
|--------|-----------|--------|
| `validate-andalucia-ei-catalogo-servicios.php` | Verifica integridad catalogo → implementacion | 10 checks |
| `validate-andalucia-ei-phase-access.php` | Verifica diferenciacion de acceso por fase | 6 checks |

**Checks de validate-andalucia-ei-catalogo-servicios.php:**

1. Los 5 PackServicioEi::PACK_TIPOS existen en constantes
2. ParticipantePortalController pasa #setup_wizard y #daily_actions
3. hook_theme() declara variables setup_wizard y daily_actions para participante_portal
4. Existen >= 6 wizard steps tagged para dashboard participante_ei
5. Existen >= 5 daily actions tagged para dashboard participante_ei
6. enviarRecordatoriosSesion() se invoca desde hook_cron
7. ProgramaVerticalAccessService tiene metodo hasFeatureAccess
8. AndaluciaEiCopilotBridgeService tiene ramas isOrientador + isFormador
9. BonoProgramaExpiryService existe y esta registrado
10. PackServicioEi entities con publicado=TRUE tienen stripe_product_id

**Checks de validate-andalucia-ei-phase-access.php:**

1. FASES_FORMACION y FASES_INSERCION definidas sin solapamiento
2. FEATURES_INSERCION no vacio
3. hasFeatureAccess() devuelve FALSE para features insercion en fase acogida
4. hasFeatureAccess() devuelve TRUE para features insercion en fase insercion
5. getAvailableFeatures() retorna mas features en insercion que en formacion
6. Daily actions con fase insercion+ usan hasFeatureAccess en isVisible

### 8.2 Pre-commit Hook

Anadir en `.husky/pre-commit` (lint-staged):
- `jaraba_andalucia_ei.services.yml` → ejecutar `validate-andalucia-ei-catalogo-servicios.php --fast`

### 8.3 CI Gate

Anadir en `ci.yml`:
```yaml
- name: Validate Andalucia EI Catalogo
  run: php scripts/validation/validate-andalucia-ei-catalogo-servicios.php
```

---

## 9. Tabla de Correspondencia: Specs → Archivos

| Spec ID | Descripcion | Archivos Afectados | Sprint |
|---------|------------|-------------------|--------|
| SPEC-CAT-001 | Wizard participante | 8 Step classes + services.yml + controller + hook_theme + template | E |
| SPEC-CAT-002 | Daily Actions participante | 7 Action classes + services.yml + controller + hook_theme + template | E |
| SPEC-CAT-003 | Recordatorios sesion cron | jaraba_andalucia_ei.module (hook_cron) | E |
| SPEC-CAT-004 | Recordatorio firmas periodico | FirmaWorkflowService + hook_cron | E |
| SPEC-CAT-005 | Acceso diferenciado fase | ProgramaVerticalAccessService | F |
| SPEC-CAT-006 | Copilot bridge orientador | AndaluciaEiCopilotBridgeService | F |
| SPEC-CAT-007 | Copilot bridge formador | AndaluciaEiCopilotBridgeService | F |
| SPEC-CAT-008 | Contexto pack en copilot | AndaluciaEiCopilotContextProvider | F |
| SPEC-CAT-009 | Enforcement 12 meses | BonoProgramaExpiryService + hook_cron | F |
| SPEC-CAT-010 | CRM clientes participante | ClienteParticipanteEi entity + controller + template | G |
| SPEC-CAT-011 | Facturacion pack a clientes | PackBillingActivationService | G |
| SPEC-CAT-012 | Flujo cliente piloto | PilotClientFlowService | G |
| SPEC-CAT-013 | Plantillas GrapesJS locales | 5 template YAML en jaraba_page_builder | G |
| SPEC-CAT-014 | Landing /metodo | Controller + template page--metodo.html.twig | H |
| SPEC-CAT-015 | GroundingProvider empleabilidad | CandidateGroundingProvider | H |
| SPEC-CAT-016 | Auto-respuesta leads | LeadAutoResponderService + QueueWorker | H |
| SPEC-CAT-017 | Dashboard metricas negocio | Controller + template + SCSS | H |

---

## 10. Tabla de Cumplimiento de Directrices

Cada archivo creado en este plan DEBE cumplir:

| Directriz | Aplicacion | Verificacion |
|-----------|-----------|-------------|
| TENANT-001 | Toda query de ClienteParticipanteEi, PackServicioEi | `->condition('tenant_id', $tenantId)` |
| OPTIONAL-CROSSMODULE-001 | Bridges en services.yml | `@?` para dependencias cross-modulo |
| PREMIUM-FORMS-PATTERN-001 | Formularios de ClienteParticipanteEi | `extends PremiumEntityFormBase` |
| CONTROLLER-READONLY-001 | Nuevos controllers | Sin `protected readonly` en herencia ControllerBase |
| CSS-VAR-ALL-COLORS-001 | SCSS de nuevos componentes | `var(--ej-*, fallback)` en todo color |
| ICON-CONVENTION-001 | Templates de wizard steps y daily actions | `jaraba_icon('category', 'name', options)` |
| ICON-DUOTONE-001 | Iconos por defecto | `variant: 'duotone'` |
| ICON-COLOR-001 | Colores de iconos | Solo azul-corporativo, naranja-impulso, verde-innovacion, white, neutral |
| TWIG-INCLUDE-ONLY-001 | Parciales en templates | `{% include ... only %}` |
| ROUTE-LANGPREFIX-001 | URLs en PHP | `Url::fromRoute()` con try-catch en preprocess |
| ZERO-REGION-001 | Paginas frontend | `{{ clean_content }}`, hook_preprocess_page |
| SLIDE-PANEL-RENDER-002 | Formularios participante | `_controller:` en routing, isSlidePanelRequest() |
| TWIG-SYNTAX-LINT-001 | Templates Twig nuevos | Sin doble coma, sin {# anidado |
| SCSS-COMPILE-VERIFY-001 | SCSS compilado | Verificar timestamp CSS > SCSS |
| SCSS-COLORMIX-001 | Alpha colors | `color-mix(in srgb, ...)` en lugar de `rgba()` |
| ENTITY-PREPROCESS-001 | ClienteParticipanteEi view mode | `template_preprocess_cliente_participante_ei()` |
| ENTITY-FK-001 | participante_id | entity_reference (mismo modulo) |
| AUDIT-CONS-001 | ClienteParticipanteEi | AccessControlHandler en anotacion |
| UPDATE-HOOK-REQUIRED-001 | Nueva entity + nuevos campos | hook_update_N() con installEntityType/updateFieldable |
| FIELD-UI-SETTINGS-TAB-001 | field_ui_base_route | Default local task tab |
| MARKETING-TRUTH-001 | Landing /metodo | Solo mostrar lo que existe en backend |
| PIPELINE-E2E-001 | Cada feature | Service → Controller → hook_theme → Template |
| SETUP-WIZARD-DAILY-001 | participante_ei | Tagged services via CompilerPass |
| ZEIGARNIK-PRELOAD-001 | Wizard participante | 2 global auto-complete steps |
| i18n | Textos UI | `{% trans %}...{% endtrans %}`, `@Translation()`, `$this->t()` |
| SCSS moderno | Dart Sass | `@use`, `color-mix()`, NO `@import`, NO `rgba()` |
| Responsive | Templates | Mobile-first, flex/grid, `--ej-spacing-*` tokens |

---

## 11. Verificacion Post-Implementacion (RUNTIME-VERIFY-001)

Tras CADA sprint, ejecutar:

### Sprint E

```bash
# 1. Verificar wizard steps registrados
lando drush ev "print_r(\Drupal::service('ecosistema_jaraba_core.setup_wizard_registry')->getStepIds('participante_ei'));"

# 2. Verificar daily actions registrados
lando drush ev "print_r(\Drupal::service('ecosistema_jaraba_core.daily_actions_registry')->getActionIds('participante_ei'));"

# 3. Verificar cron ejecuta recordatorios
lando drush ev "\Drupal::state()->delete('jaraba_andalucia_ei.reminder_last_run'); jaraba_andalucia_ei_cron();"

# 4. Verificar ruta participante portal renderiza wizard
lando drush router:list --path=/andalucia-ei/mi-participacion

# 5. Verificar SCSS compilado
cd web/themes/custom/ecosistema_jaraba_theme && npm run build

# 6. Verificar safeguard
php scripts/validation/validate-andalucia-ei-catalogo-servicios.php
```

### Sprint F

```bash
# 1. Verificar copilot bridge orientador
lando drush ev "\$b = \Drupal::service('jaraba_andalucia_ei.copilot_bridge'); print_r(array_keys(\$b->getRelevantContext(ORIENTADOR_UID)));"

# 2. Verificar acceso diferenciado
lando drush ev "\$s = \Drupal::service('jaraba_andalucia_ei.programa_vertical_access'); var_dump(\$s->hasFeatureAccess(PARTICIPANTE_UID, 'crm_clientes'));"

# 3. Verificar enforcement expiracion
php scripts/validation/validate-andalucia-ei-phase-access.php
```

### Sprint G

```bash
# 1. Verificar entity instalada
lando drush entity:updates

# 2. Verificar Field UI accesible
# Navegar a /admin/structure/cliente-participante-ei

# 3. Verificar Views data
lando drush ev "print_r(\Drupal::entityTypeManager()->getDefinition('cliente_participante_ei')->get('handlers'));"
```

---

## 12. Glosario

| Sigla | Significado |
|-------|------------|
| PIIL | Programas Integrales para la Insercion Laboral |
| FSE+ | Fondo Social Europeo Plus |
| STO | Servicio de Teleformacion y Orientacion (SEPE) |
| DACI | Declaracion de Aceptacion de Compromisos Individual |
| DIME | Diagnostico Individual de Madurez Emprendedora |
| ICV | Itinerarios de Cualificacion y Validacion |
| CNAE | Clasificacion Nacional de Actividades Economicas |
| IAE | Impuesto de Actividades Economicas |
| SS | Seguridad Social |
| PE | Punto de Equilibrio |
| SROI | Social Return on Investment |
| VoBo | Visto Bueno |
| SAE | Servicio Andaluz de Empleo |
| BMC | Business Model Canvas |
| CRM | Customer Relationship Management |
| SSOT | Single Source of Truth |
| DI | Dependency Injection (Inyeccion de Dependencias) |
| SCSS | Sassy CSS (preprocesador CSS) |
| GrapesJS | Editor visual web de codigo abierto v5.7 |
| Qdrant | Motor de busqueda vectorial |
| PWA | Progressive Web App |
| TOC | Table of Contents (Indice) |
| UX | User Experience (Experiencia de Usuario) |
| IA | Inteligencia Artificial |
| CTA | Call to Action (Llamada a la Accion) |
| FAB | Floating Action Button |
| KPI | Key Performance Indicator |
| QR | Quick Response (codigo de barras bidimensional) |
| SEO | Search Engine Optimization |
| GDPR | General Data Protection Regulation (RGPD en espanol) |
| IRPF | Impuesto sobre la Renta de las Personas Fisicas |
| IVA | Impuesto sobre el Valor Anadido |
