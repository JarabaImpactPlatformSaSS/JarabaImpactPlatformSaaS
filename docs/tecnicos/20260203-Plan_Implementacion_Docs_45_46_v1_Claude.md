# Plan de Implementación: Documentos 45 y 46

**Código:** 20260203-Plan_Implementacion_Docs_45_46_v1  
**Fecha:** Febrero 2026  
**Estado:** Especificación Técnica  
**Dependencias:** jaraba_sepe_teleformacion, jaraba_lms, jaraba_mentoring, jaraba_copilot_v2

---

## 1. Resumen Ejecutivo

Este documento especifica la implementación de dos módulos nuevos basados en los documentos técnicos 45 (Andalucía +ei) y 46 (Training Certification System).

| Módulo | Descripción | Estimación |
|--------|-------------|------------|
| `jaraba_andalucia_ei` | Programa Andalucía +ei con tracking de horas IA y fases PIIL | 8-10 días |
| `jaraba_training` | Escalera de Valor con certificaciones y upsell automático | 9-12 días |

---

## 2. Análisis de Dependencias Existentes

| Módulo Existente | Funcionalidad Aprovechable |
|------------------|---------------------------|
| `jaraba_sepe_teleformacion` | SepeSoapService, SepeParticipante, SepeAccionFormativa |
| `jaraba_lms` | Course, Lesson, Activity, Enrollment, ProgressRecord |
| `jaraba_mentoring` | MentorProfile, MentoringPackage, MentoringEngagement |
| `ecosistema_jaraba_core` | Credentials (Open Badges 3.0), FOC metrics |
| `jaraba_copilot_v2` | AI Conversations, RAG pipeline |

---

## 3. FASE 1: Módulo Andalucía +ei (Doc 45)

> [!IMPORTANT]
> **Decisión aprobada**: Crear módulo separado `jaraba_andalucia_ei` que reutilice servicios de `jaraba_sepe_teleformacion`.

### 3.1 Estructura del Módulo

```
web/modules/custom/jaraba_andalucia_ei/
├── jaraba_andalucia_ei.info.yml
├── jaraba_andalucia_ei.install
├── jaraba_andalucia_ei.module
├── jaraba_andalucia_ei.services.yml
├── jaraba_andalucia_ei.routing.yml
├── jaraba_andalucia_ei.permissions.yml
├── jaraba_andalucia_ei.links.menu.yml      # → /admin/structure
├── jaraba_andalucia_ei.links.task.yml      # → tab en /admin/content
├── jaraba_andalucia_ei.links.action.yml    # → botón "Añadir"
├── src/
│   ├── Entity/
│   │   ├── ProgramaParticipanteEi.php
│   │   └── ProgramaParticipanteEiInterface.php
│   ├── Service/
│   │   ├── AiMentorshipTracker.php
│   │   ├── StoExportService.php
│   │   └── FaseTransitionManager.php
│   ├── Form/
│   │   └── ProgramaParticipanteEiSettingsForm.php
│   ├── Controller/
│   │   └── AndaluciaEiController.php
│   └── ProgramaParticipanteEiListBuilder.php
├── templates/
│   └── page--andalucia-ei.html.twig        # Frontend full-width
└── config/
    └── install/
        └── jaraba_andalucia_ei.settings.yml
```

### 3.2 Entidad ProgramaParticipanteEi

Campos según especificación Doc 45 § 3.1:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| user_id | entity_reference | Usuario Drupal |
| group_id | entity_reference | Grupo Andalucía +ei |
| dni_nie | string(12) | Documento identificativo |
| colectivo | list_string | jovenes, mayores_45, larga_duracion |
| provincia_participacion | string | Provincia de inscripción STO |
| fecha_alta_sto | datetime | Fecha registro STO (inmutable) |
| fase_actual | list_string | atencion, insercion, baja |
| horas_orientacion_ind | decimal | Horas orientación individual |
| horas_orientacion_grup | decimal | Horas orientación grupal |
| horas_formacion | decimal | Horas formación acumuladas |
| horas_mentoria_ia | decimal | Horas con Tutor IA |
| horas_mentoria_humana | decimal | Horas con mentor humano |
| carril | list_string | impulso_digital, acelera_pro, hibrido |
| incentivo_recibido | boolean | €528 recibido y firmado |
| tipo_insercion | list_string | cuenta_ajena, cuenta_propia, agrario |
| fecha_insercion | datetime | Fecha inserción verificada |
| sto_sync_status | list_string | pending, synced, error |

### 3.3 Navegación Admin (CRÍTICO)

```yaml
# jaraba_andalucia_ei.routing.yml

# Content Entities en /admin/content (NO en /admin/config)
entity.programa_participante_ei.collection:
  path: '/admin/content/andalucia-ei'
  
entity.programa_participante_ei.settings:
  path: '/admin/structure/programa-participante-ei'  # Para Field UI
```

```yaml
# jaraba_andalucia_ei.links.task.yml - Tab en Content
entity.programa_participante_ei.collection:
  title: 'Andalucía +ei'
  route_name: entity.programa_participante_ei.collection
  base_route: system.admin_content
  weight: 25
```

### 3.4 Frontend Dashboard (Full-Width)

```twig
{# page--andalucia-ei.html.twig #}
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}

<main id="main-content" class="andalucia-ei-main">
  <div class="andalucia-ei-wrapper">
    {{ page.content }}
  </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
```

```php
// ecosistema_jaraba_theme.theme - hook_preprocess_html
$variables['attributes']['class'][] = 'andalucia-ei-page';
```

### 3.5 CRUD en Slide-Panel

Todas las acciones de crear/editar participantes deben abrirse en slide-panel:

```html
<button data-slide-panel="nuevo-participante"
        data-slide-panel-url="/admin/content/andalucia-ei/add"
        data-slide-panel-title="{% trans %}Nuevo Participante{% endtrans %}">
  + {% trans %}Añadir Participante{% endtrans %}
</button>
```

---

## 4. FASE 2: Training Certification System (Doc 46)

### 4.1 Estructura del Módulo

```
web/modules/custom/jaraba_training/
├── jaraba_training.info.yml
├── jaraba_training.install
├── jaraba_training.module
├── jaraba_training.services.yml
├── jaraba_training.routing.yml
├── jaraba_training.permissions.yml
├── jaraba_training.links.menu.yml
├── jaraba_training.links.task.yml
├── jaraba_training.links.action.yml
├── src/
│   ├── Entity/
│   │   ├── TrainingProduct.php
│   │   ├── CertificationProgram.php
│   │   └── UserCertification.php
│   ├── Service/
│   │   ├── LadderService.php
│   │   ├── UpsellEngine.php
│   │   └── RoyaltyTracker.php
│   ├── Plugin/rest/resource/
│   │   └── TrainingLadderResource.php
│   └── Controller/
│       └── TrainingController.php
├── templates/
│   └── page--training.html.twig
└── config/
    └── install/
        └── jaraba_training.settings.yml
```

### 4.2 Entidades

#### TrainingProduct (Escalera de Valor)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| title | string | Nombre del producto |
| product_type | list_string | lead_magnet, microcourse, membership, etc. |
| ladder_level | integer | Peldaño 0-5 |
| price | decimal | Precio base |
| billing_type | list_string | free, one_time, recurring, cohort |
| course_ids | json | Cursos LMS incluidos |
| next_product_id | entity_reference | Siguiente en escalera |

#### CertificationProgram

| Campo | Tipo | Descripción |
|-------|------|-------------|
| certification_type | list_string | consultant, entity, regional_franchise |
| entry_fee | decimal | Fee de activación |
| annual_fee | decimal | Cuota anual |
| royalty_percent | decimal | % royalty sobre ventas |
| required_courses | json | Cursos obligatorios (LMS) |
| exam_required | boolean | Requiere examen |

### 4.3 APIs REST

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/v1/training/products` | GET | Listar productos |
| `/api/v1/training/ladder` | GET | Escalera completa |
| `/api/v1/training/ladder/recommend` | GET | Siguiente recomendado |
| `/api/v1/training/products/{id}/purchase` | POST | Iniciar compra |

---

## 5. Cumplimiento de Directrices

### 5.1 Content Entities (workflow drupal-custom-modules)

- [ ] **4 archivos YAML** obligatorios: routing, links.menu, links.task, links.action
- [ ] **Navegación**: Content en `/admin/content`, Structure en `/admin/structure`
- [ ] **Field UI**: `field_ui_base_route` apuntando a settings
- [ ] **Views**: Handler `views_data` en anotación
- [ ] **Interface**: Crear `*Interface.php` para cada entidad

### 5.2 Frontend (workflow frontend-page-pattern)

- [ ] **Templates Twig limpias** sin regiones/bloques Drupal
- [ ] **Parciales reutilizables** vía `{% include %}`
- [ ] **hook_preprocess_html** para clases body (NO `attributes.addClass()`)
- [ ] **Full-width layout** con diseño mobile-first
- [ ] **CRUD en slide-panel** para no abandonar página

### 5.3 i18n y SCSS (DIRECTRICES_DESARROLLO.md)

- [ ] **Textos traducibles**: `{% trans %}` en Twig, `$this->t()` en PHP
- [ ] **SCSS solamente**: Nunca crear `.css` directo
- [ ] **Variables inyectables**: `var(--ej-*)` configurables desde UI
- [ ] **Dart Sass moderno**: `@use` en lugar de `@import`
- [ ] **Iconos**: `jaraba_icon('category', 'name', {options})`
- [ ] **Paleta Jaraba**: corporate #233D63, impulse #FF8C42, innovation #00A9A5

---

## 6. Cronograma

| Fase | Sprint | Días | Dependencias |
|------|--------|------|--------------|
| 1.1 | jaraba_andalucia_ei entities | 2-3 | - |
| 1.2 | AiMentorshipTracker | 3-4 | jaraba_copilot_v2 |
| 1.3 | Frontend + Flujos ECA | 2-3 | Sprint 1.1 |
| 2.1 | jaraba_training entities | 4-5 | - |
| 2.2 | APIs REST | 3-4 | Sprint 2.1 |
| 2.3 | UpsellEngine | 2-3 | Sprint 2.2 |
| **Total** | | **16-22 días** | |

---

## 7. Verificación

### Tests Automatizados

```bash
lando drush pm:enable jaraba_andalucia_ei jaraba_training
lando drush entup
lando phpunit web/modules/custom/jaraba_andalucia_ei/tests
lando phpunit web/modules/custom/jaraba_training/tests
```

### Verificación Manual

1. **Navegación**: Verificar pestañas en `/admin/content` y `/admin/structure`
2. **Field UI**: Verificar "Administrar campos" disponible
3. **Frontend**: Verificar layout full-width sin sidebar
4. **Slide-panel**: Verificar CRUD sin abandonar página

---

*Documento generado: Febrero 2026 | Jaraba Impact Platform*
