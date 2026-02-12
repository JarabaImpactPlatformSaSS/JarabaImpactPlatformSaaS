# Bloque A: Gaps Auditoría - Documento de Implementación
## SEPE, Frontend Premium, Verticales Commerce

**Fecha de creación:** 2026-01-23 08:49  
**Última actualización:** 2026-01-23 08:49  
**Autor:** IA Asistente (Claude)  
**Versión:** 1.0.0

---

## 📑 Tabla de Contenidos (TOC)

1. [Matriz de Especificaciones](#1-matriz-de-especificaciones)
2. [Checklist Multidisciplinar](#2-checklist-multidisciplinar-8-expertos)
3. [A.1 Quick Wins + SEPE](#3-a1-quick-wins--sepe)
4. [A.2 Frontend Premium](#4-a2-frontend-premium)
5. [A.3 AgroConecta Commerce](#5-a3-agroconecta-commerce)
6. [A.4 Expansión](#6-a4-expansión)
7. [Checklist Directrices Obligatorias](#7-checklist-directrices-obligatorias)
8. [Registro de Cambios](#8-registro-de-cambios)

---

## 1. Matriz de Especificaciones

### 1.1 SEPE Teleformación (Docs 105-107)

| Doc | Archivo | Contenido Clave |
|-----|---------|-----------------|
| 105 | [20260117g-105_Homologacion_Teleformacion_SEPE_v1_Claude.md](../tecnicos/20260117g-105_Homologacion_Teleformacion_SEPE_v1_Claude.md) | Requisitos homologación |
| 106 | [20260117g-106_Modulo_SEPE_Teleformacion_Implementacion_v1_Claude.md](../tecnicos/20260117g-106_Modulo_SEPE_Teleformacion_Implementacion_v1_Claude.md) | Implementación módulo |
| 107 | [20260117g-107_SEPE_Kit_Validacion_Procedimiento_v1_Claude.md](../tecnicos/20260117g-107_SEPE_Kit_Validacion_Procedimiento_v1_Claude.md) | Kit validación |

### 1.2 Frontend Premium (Docs 100-104)

| Doc | Archivo | Contenido Clave |
|-----|---------|-----------------|
| 100 | [20260117f-100_Frontend_Architecture_MultiTenant_v1_Claude.md](../tecnicos/20260117f-100_Frontend_Architecture_MultiTenant_v1_Claude.md) | 5 capas frontend |
| 101 | [20260117f-101_Industry_Style_Presets_v1_Claude.md](../tecnicos/20260117f-101_Industry_Style_Presets_v1_Claude.md) | 15 presets |
| 102 | [20260117f-102_Industry_Style_Presets_Premium_Implementation_v1_Claude.md](../tecnicos/20260117f-102_Industry_Style_Presets_Premium_Implementation_v1_Claude.md) | Implementación presets |

### 1.3 AgroConecta Commerce (Docs 47-61, 80-82)

| Doc | Archivo | Contenido Clave |
|-----|---------|-----------------|
| 47 | [20260116a-47_AgroConecta_Commerce_Core_v1_Claude.md](../tecnicos/20260116a-47_AgroConecta_Commerce_Core_v1_Claude.md) | Commerce Core |
| 48 | [20260116a-48_AgroConecta_Product_Catalog_v1.md](../tecnicos/20260116a-48_AgroConecta_Product_Catalog_v1.md) | Catálogo productos |
| 49-61 | 47-61_*.md | Order, Checkout, Shipping, Portales |
| 80-82 | 80-82_*.md | Trazabilidad, QR, Partners |

### 1.4 ComercioConecta (Docs 62-79)

| Doc | Archivo | Contenido Clave |
|-----|---------|-----------------|
| 62-79 | [20260117b-62 a 79_ComercioConecta_*.md](../tecnicos/) | 18 specs commerce retail |

### 1.5 ServiciosConecta (Docs 82-99)

| Doc | Archivo | Contenido Clave |
|-----|---------|-----------------|
| 82-99 | [20260117e-82 a 99_ServiciosConecta_*.md](../tecnicos/) | 18 specs servicios |

### 1.6 🔄 Estrategia de Reuso (DIRECTRIZ OBLIGATORIA)

> ⚠️ **VERIFICACIÓN PREVIA**: Antes de cada paso, ejecutar análisis de reuso.

#### A. Reuso Cross-Vertical

**AgroConecta → revisar qué existe en:**
- Empleabilidad: Profile entities, matching patterns
- Emprendimiento: Copiloto modes, canvas templates

| Componente Reutilizable | Módulo Origen | Acción |
|-------------------------|---------------|--------|
| Matching Engine | `jaraba_matching` | Referencia |
| Commerce Core | `jaraba_commerce` | Base |
| Copiloto Modes | `jaraba_copilot_v2` | Extender |

#### B. Reuso AgroConecta Anterior (Legacy)

> **IGNORAR** todo relacionado con **Ecwid** → usar Drupal Commerce + Stripe.

| Componente | Ruta | Acción |
|------------|------|--------|
| 8 Agentes IA | `z:/home/PED/AgroConecta/src/Agent/` | Adaptar |
| SeasonCore | `Service/SeasonCore.php` | Copiar |
| Workflows ECA | `config/install/eca.*` | Adaptar |
| Tema "Sin Humo" | `agroconecta_theme/` | Base presets |

#### C. Checklist Pre-Paso

```
- [ ] ¿Existe funcionalidad similar en verticales anteriores?
- [ ] ¿Hay código en AgroConecta legacy aplicable?
- [ ] ¿Se puede abstraer a módulo compartido?
- [ ] **IGNORAR**: EcwidService, Ecwid SSO
```

---

## 2. Checklist Multidisciplinar (8 Expertos)

### 2.1 Consultor de Negocio Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Unit Economics definidos? | [ ] | |
| ¿Revenue model claro por vertical? | [ ] | |
| ¿GTM plan para AgroConecta? | [ ] | |
| ¿SEPE genera revenue institucional? | [ ] | Target: €50K/año |

### 2.2 Analista Financiero Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Integración con FOC Dashboard? | [ ] | MRR, ARR tracking |
| ¿Stripe Connect para marketplace? | [ ] | Destination Charges |
| ¿Royalties tracking (Training)? | [ ] | |

### 2.3 Experto Producto Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿MVP scope definido por vertical? | [ ] | |
| ¿User stories documentadas? | [ ] | |
| ¿Roadmap por vertical alineado? | [ ] | |

### 2.4 Arquitecto SaaS Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Multi-tenant via Group Module? | [ ] | |
| ¿Escalabilidad horizontal? | [ ] | |
| ¿Patrón ECA via hooks? | [ ] | No ECA UI |

### 2.5 Ingeniero Software Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿PHPStan en CI? | [ ] | |
| ¿PHPUnit para servicios críticos? | [ ] | |
| ¿Cypress E2E para flujos commerce? | [ ] | |

### 2.6 Ingeniero UX Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿WCAG 2.1 AA compliance? | [ ] | |
| ¿Journey por avatar definido? | [ ] | Doc 103 |
| ¿Component Library implementada? | [ ] | 6 headers, 8 cards |

### 2.7 Ingeniero SEO/GEO Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Schema.org para productos? | [ ] | Product, LocalBusiness |
| ¿llms.txt creado? | [ ] | Quick Win |
| ¿E-E-A-T para verticales? | [ ] | |

### 2.8 Ingeniero IA Senior

| Verificación | Estado | Notas |
|--------------|--------|-------|
| ¿Copilot por vertical? | [ ] | AgroConecta Copilot |
| ¿RAG para conocimiento vertical? | [ ] | |
| ¿Token budgets definidos? | [ ] | |

---

## 3. A.1 Quick Wins + SEPE

### 3.1 Quick Wins (40h)

#### Paso 1: llms.txt (1h)
```bash
# Crear archivo en web root
touch web/llms.txt
```
- [ ] **Archivo:** `web/llms.txt`
- [ ] **Contenido:** Descripción de agentes IA disponibles
- [ ] **Verificar:** Accesible en /llms.txt

#### Paso 2: PHPStan CI (4h)
- [ ] Archivo `.github/workflows/phpstan.yml`
- [ ] Configurar nivel 6 mínimo
- [ ] Integrar en PR checks

#### Paso 3: Auditoría Lighthouse (4h)
- [ ] Ejecutar Lighthouse en rutas críticas
- [ ] Documentar scores Accessibility
- [ ] Plan de remediación

#### Paso 4: PHPUnit Servicios (30h)
| Servicio | Test File | Prioridad |
|----------|-----------|-----------|
| TenantManager | TenantManagerTest.php | P0 |
| PlanValidator | PlanValidatorTest.php | P0 |
| MatchingService | MatchingServiceTest.php | P1 |

### 3.2 SEPE Teleformación (100h)

> **Referencia:** Docs 105-107

#### Paso 1: Crear módulo (20h)
- [ ] `modules/custom/jaraba_sepe_teleformacion/`
- [ ] Entity `SepeCentro` (Content Entity)
- [ ] Entity `SepeAccionFormativa` (Content Entity)
- [ ] Entity `SepeParticipante` (Content Entity)

#### Paso 2: Servicios SOAP (40h)
- [ ] `SepeSoapController` - Endpoints WSDL
- [ ] `SepeDataMapperService` - Transformación datos
- [ ] `SepeCalculatorService` - Cálculos horas/asistencia

#### Paso 3: Kit Validación (20h)
- [ ] Tests con XML de ejemplo SEPE
- [ ] Validador de respuestas
- [ ] Logs de comunicación

#### Paso 4: Documentación (10h)
- [ ] Guía instalación centro
- [ ] Manual Declaración Responsable

---

## 4. A.2 Frontend Premium

> **Referencia:** Docs 100-104

### 4.1 Design Tokens (32h)

#### Paso 1: Sistema de tokens
- [ ] Archivo `scss/_tokens.scss`
- [ ] Variables CSS inyectables `var(--ej-*)`
- [ ] Fallbacks SCSS

#### Paso 2: Cascada PHP
```php
// TenantThemeService.php
public function getTokens(Tenant $tenant): array {
    return [
        'platform' => $this->getPlatformTokens(),
        'vertical' => $this->getVerticalTokens($tenant->vertical),
        'tenant' => $this->getTenantTokens($tenant),
    ];
}
```
- [ ] `hook_preprocess_html` para inyección

### 4.2 Component Library (56h)

| Componente | Variantes | Archivo SCSS |
|------------|-----------|--------------|
| Header | 6: classic, transparent, centered, mega, sidebar, minimal | `_header-variants.scss` |
| Cards | 8: default, horizontal, product, profile, course, metric, testimonial, cta | `_card-variants.scss` |
| Hero | 5: fullscreen, split, compact, animated, slider | `_hero-variants.scss` |

### 4.3 Visual Picker (40h)

- [ ] Entity `tenant_theme_config` (Content Entity)
- [ ] Ruta `/admin/appearance/jaraba-customizer`
- [ ] Preview iframe tiempo real
- [ ] Feature Flags por plan

### 4.4 Industry Presets (32h)

15 presets:
1. `agro_gourmet`
2. `agro_ecologico`
3. `comercio_moda`
4. `comercio_barrio`
5. `servicios_legal`
6. `servicios_salud`
7. `servicios_consultoria`
8. `empleo_tecnologia`
9. `empleo_industrial`
10. `emprendimiento_startup`
11. `emprendimiento_artesano`
12. `formacion_ejecutivo`
13. `formacion_profesional`
14. `institucional_publico`
15. `institucional_ong`

---

## 5. A.3 AgroConecta Commerce

> **Referencia:** Docs 47-61, 80-82

### 5.1 Commerce Core (80h)

#### Entidades a crear:
| Entidad | Tipo | Campos Clave |
|---------|------|--------------|
| `product_agro` | Content Entity | SKU, precio, stock, productor |
| `product_variation_agro` | Content Entity | Peso, precio/kg |
| `producer_profile` | Content Entity | Finca, certificaciones |

#### Integración Drupal Commerce 3.x
- [ ] composer require drupal/commerce
- [ ] Configurar stores por tenant
- [ ] Product types específicos

### 5.2 Pagos + Shipping (80h)
- [ ] Stripe Connect Destination Charges
- [ ] Shipping rates por zona
- [ ] Checkout multi-step

### 5.3 Portales + QR (80h)
- [ ] Producer Portal (`/my-farm/*`)
- [ ] Customer Portal (`/my-orders/*`)
- [ ] QR trazabilidad por lote

---

## 6. A.4 Expansión

### 6.1 ComercioConecta (300h)
- Ver Docs 62-79

### 6.2 ServiciosConecta (300h)
- Ver Docs 82-99

### 6.3 Platform Features (200h)
- PWA completa
- Webhooks

### 6.4 Marketing Nativo (250h)
- jaraba_crm
- jaraba_email
- jaraba_social

---

## 7. Checklist Directrices Obligatorias

> ⚠️ **VERIFICAR ANTES DE CADA COMMIT**

### 7.1 SCSS y Variables Inyectables

| Verificación | Estado |
|--------------|--------|
| ¿Archivos SCSS, no CSS directo? | [ ] |
| ¿Usa `var(--ej-*)` para colores? | [ ] |
| ¿Paleta oficial 7 colores? | [ ] |
| ¿Iconos outline + duotone creados? | [ ] |
| ¿Compilado con `npm run build`? | [ ] |

### 7.2 Internacionalización (i18n)

| Verificación | Estado |
|--------------|--------|
| ¿Textos PHP con `$this->t()`? | [ ] |
| ¿Textos Twig con `{% trans %}`? | [ ] |
| ¿JS con `Drupal.t()`? | [ ] |

### 7.3 Content Entities

| Verificación | Estado |
|--------------|--------|
| ¿Handler `views_data` en annotation? | [ ] |
| ¿Handler `list_builder` definido? | [ ] |
| ¿`field_ui_base_route` configurado? | [ ] |
| ¿Rutas en `/admin/content`? | [ ] |
| ¿Settings en `/admin/structure`? | [ ] |
| ¿4 archivos YAML creados? | [ ] |
| - *.routing.yml | [ ] |
| - *.links.menu.yml | [ ] |
| - *.links.task.yml | [ ] |
| - *.links.action.yml | [ ] |

### 7.4 Post-Implementación

| Verificación | Estado |
|--------------|--------|
| ¿`composer dump-autoload -o`? | [ ] |
| ¿`drush cr`? | [ ] |
| ¿Docker restart si cambios clase? | [ ] |
| ¿Verificar en navegador? | [ ] |

---

## 8. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-23 | 1.0.0 | Creación inicial - Documento de implementación Bloque A |
