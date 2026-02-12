# Sistema Premium de Previews de Plantillas - Especificación Técnica v1

**Fecha**: 2026-01-30  
**Autor**: Claude Assistant  
**Estado**: Aprobado  
**Módulo**: `jaraba_page_builder`

---

## 1. Resumen Ejecutivo

Este documento define la arquitectura para un sistema de previews de plantillas de clase mundial que garantiza fidelidad visual entre las miniaturas PNG diseñadas profesionalmente y los previews live renderizados dinámicamente.

### Objetivos

| Objetivo | Métrica |
|----------|---------|
| Fidelidad visual | Preview live indistinguible de miniatura PNG |
| Escalabilidad | Soporte para 69+ plantillas |
| Mantenibilidad | Datos centralizados en YAML |
| Automatización | Base para validación visual futura |

---

## 2. Problema Identificado

### Gap Actual

| Aspecto | Miniatura PNG (Diseño) | Preview Live (Código) |
|---------|------------------------|------------------------|
| Tarjetas | 9 elementos | 3 elementos |
| Iconos | Coloridos, profesionales | Pin rojo genérico |
| Fondo | Verde suave gradiente | Blanco plano |
| Contenido | Inglés, marketing-ready | Español genérico |

### Impacto

- **Confianza del usuario**: Expectativa vs realidad genera abandono
- **Percepción de calidad**: SaaS no parece premium
- **Conversión**: Usuarios no eligen plantillas porque no ven resultado real

---

## 3. Arquitectura de la Solución

### 3.1 Flujo de Datos

```
Template YAML
├── preview_data (curado)    ─┐
├── fields_schema            ─┼─→ TemplatePickerController
├── preview_image (PNG)      ─┘         │
                                        ▼
                              ¿preview_data existe?
                              /              \
                           Sí                No
                            │                 │
                  Usar datos curados    Generar automático
                            │                 │
                            └─────┬───────────┘
                                  ▼
                           Renderizar Twig
                                  │
                                  ▼
                           Preview iframe
```

### 3.2 Priorización de Datos

1. **preview_data** (campo YAML) - Datos curados idénticos a PNG
2. **Generación automática** - Datos inferidos del schema

---

## 4. Componentes Técnicos

### 4.1 Extensión de Entidad PageTemplate

**Archivo**: `src/Entity/PageTemplate.php`

```php
// Propiedad nueva
protected $preview_data = [];

// Getter
public function getPreviewData(): array
{
    return $this->preview_data ?? [];
}
```

**Archivo**: `src/PageTemplateInterface.php`

```php
public function getPreviewData(): array;
```

### 4.2 Anotación config_export

```php
config_export = {
    "id",
    "label",
    "description",
    "category",
    "twig_template",
    "fields_schema",
    "plans_required",
    "is_premium",
    "preview_image",
    "preview_data",  // NUEVO
    "weight",
}
```

### 4.3 Schema de Configuración

**Archivo**: `config/schema/jaraba_page_builder.schema.yml`

```yaml
jaraba_page_builder.template.*:
  type: config_entity
  mapping:
    # ... campos existentes ...
    preview_data:
      type: ignore
      label: 'Datos de preview curados'
```

### 4.4 Controlador Actualizado

**Archivo**: `src/Controller/TemplatePickerController.php`

```php
protected function getPreviewData(PageTemplateInterface $template): array
{
    // Prioridad 1: Datos curados del YAML
    $preview_data = $template->getPreviewData();
    if (!empty($preview_data)) {
        return $preview_data;
    }
    
    // Prioridad 2: Generación automática desde schema
    return $this->generatePreviewDataFromSchema($template);
}
```

---

## 5. Ejemplo: features_grid

### 5.1 Referencia Visual (PNG)

- Grid 3x3 con 9 tarjetas
- Fondo verde claro (#e8f5e9)
- Iconos coloridos (bolt, shield, chart-line, etc.)
- Título: "Features Grid"
- Contenido en inglés

### 5.2 Datos YAML Curados

```yaml
preview_data:
  section_title: 'Features Grid'
  background_variant: 'light-green'
  columns: 3
  features:
    - icon: 'bolt'
      icon_color: 'gold'
      title: 'Lightning Fast'
      description: 'Instant results with our optimized engine.'
    - icon: 'shield'
      icon_color: 'teal'
      title: 'Secure & Private'
      description: 'Bank-level encryption for your data.'
    - icon: 'chart-line'
      icon_color: 'blue'
      title: 'Scalable Growth'
      description: 'Grows with your business seamlessly.'
    - icon: 'brain'
      icon_color: 'green'
      title: 'AI-Powered Insights'
      description: 'Smart analytics to drive decisions.'
    - icon: 'globe'
      icon_color: 'teal'
      title: 'Global Reach'
      description: 'Connect with customers everywhere.'
    - icon: 'puzzle-piece'
      icon_color: 'green'
      title: 'Easy Integration'
      description: 'Works with all your favorite tools.'
    - icon: 'headset'
      icon_color: 'teal'
      title: '24/7 Support'
      description: 'Always here to help you succeed.'
    - icon: 'sliders'
      icon_color: 'teal'
      title: 'Customizable'
      description: 'Tailor the platform to your brand.'
    - icon: 'tag'
      icon_color: 'green'
      title: 'Affordable Pricing'
      description: 'Flexible plans for every budget.'
```

---

## 6. Estilos SCSS

### 6.1 Variante de Fondo

```scss
.jaraba-features {
    &--light-green {
        background: linear-gradient(180deg, #e8f5e9 0%, #f1f8e9 100%);
    }
}
```

### 6.2 Iconos Coloridos

```scss
.jaraba-feature-card__icon {
    &[data-color="gold"] { 
        color: #FFD700; 
        background: rgba(255, 215, 0, 0.1);
    }
    &[data-color="teal"] { 
        color: #26A69A;
        background: rgba(38, 166, 154, 0.1);
    }
    &[data-color="blue"] { 
        color: #2196F3;
        background: rgba(33, 150, 243, 0.1);
    }
    &[data-color="green"] { 
        color: #4CAF50;
        background: rgba(76, 175, 80, 0.1);
    }
}
```

---

## 7. Plan de Implementación

### Fase 1: Infraestructura (30 min)

| Paso | Archivo | Cambio |
|------|---------|--------|
| 1 | `PageTemplate.php` | Añadir `preview_data` y getter |
| 2 | `PageTemplateInterface.php` | Añadir método interfaz |
| 3 | `jaraba_page_builder.schema.yml` | Definir schema |
| 4 | `TemplatePickerController.php` | Usar getPreviewData() |

### Fase 2: features_grid Modelo (1 hora)

| Paso | Archivo | Cambio |
|------|---------|--------|
| 1 | `template.features_grid.yml` | Añadir 9 features curados |
| 2 | `_features.scss` | Variante light-green + iconos coloridos |
| 3 | `features-grid.html.twig` | Soportar background_variant |

### Fase 3: Validación

- Comparar preview live vs miniatura PNG
- Ajustar hasta coincidencia perfecta

---

## 8. Criterios de Aceptación

| Aspecto | PNG | Preview Live | ✓ |
|---------|-----|--------------|---|
| Número de tarjetas | 9 | 9 | |
| Color de fondo | Verde claro | Verde claro | |
| Iconos coloridos | Sí | Sí | |
| Tipografía | Sans-serif | Lexend | |
| Layout grid | 3x3 | 3x3 | |
| Contenido | Inglés | Inglés | |

---

## 9. Referencias

- Documento base: `docs/tecnicos/20260126d-160_Page_Builder_SaaS_v1_Claude.md`
- Arquitectura bloques: `docs/arquitectura/2026-01-26_arquitectura_bloques_premium.md`
- Schema YAML: `config/schema/jaraba_page_builder.schema.yml`

---

## 10. Registro de Cambios

| Fecha | Versión | Cambio |
|-------|---------|--------|
| 2026-01-30 | 1.0 | Creación inicial |
