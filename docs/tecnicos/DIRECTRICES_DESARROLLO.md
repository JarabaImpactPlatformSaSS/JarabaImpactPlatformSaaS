# DIRECTRICES DE DESARROLLO - JARABA IMPACT PLATFORM

> **Documento Central de Referencia Obligatoria**
> Versión: 1.0 | Fecha: Enero 2026

---

## ⚠️ VERIFICAR ANTES DE CADA COMMIT

Este checklist DEBE revisarse antes de cualquier commit o PR.

### 1. Internacionalización (i18n)

- [ ] **Textos traducibles**: `{% trans %}Texto{% endtrans %}` en Twig
- [ ] **Controladores**: `$this->t('Texto')` en PHP
- [ ] **JavaScript**: `Drupal.t('Texto')` en JS
- [ ] **NO usar**: `|t` filter en Twig (usar bloque `{% trans %}`)

### 2. Estilos CSS/SCSS

- [ ] **Archivos SCSS**: NUNCA crear `.css` directo, siempre `.scss`
- [ ] **Variables inyectables**: `var(--ej-*)` para valores dinámicos
- [ ] **Compilación**: `npm run build` desde WSL con NVM
- [ ] **Limpiar caché**: `lando drush cr` después de compilar

### 3. Paleta de Colores Jaraba (7 colores oficiales)

| Variable | Hex | Uso |
|----------|-----|-----|
| `--ej-color-corporate` | #233D63 | Azul corporativo, identidad marca |
| `--ej-color-impulse` | #FF8C42 | Naranja empresas, CTAs, energia |
| `--ej-color-innovation` | #00A9A5 | Turquesa talento, innovacion |
| `--ej-color-earth` | #556B2F | Verde tierra, agro, sostenibilidad |
| `--ej-color-success` | #10B981 | Verde exito, confirmaciones |
| `--ej-color-warning` | #F59E0B | Ambar alertas, atencion |
| `--ej-color-danger` | #EF4444 | Rojo error, eliminacion, critico |

- [ ] **Variantes**: Usar `color-mix()` para oscurecer/aclarar: `color-mix(in srgb, var(--ej-color-success, #10B981) 65%, black)`
- [ ] **Transparencias**: Usar `color-mix()` con transparent: `color-mix(in srgb, var(--ej-color-danger, #EF4444) 10%, transparent)`
- [ ] **NO usar**: Colores Tailwind (#059669, #dc2626), Material Design (#1565C0, #C62828), o Bootstrap (#dc3545)
- [ ] **Fallbacks**: El hex dentro de `var()` DEBE coincidir con la paleta oficial

### 4. Iconografía

- [ ] **Formato**: `jaraba_icon('category', 'name', {options})`
- [ ] **Categorías**: actions, ai, business, commerce, general, ui, verticals
- [ ] **Variantes**: `{ variant: 'outline' }` o `{ variant: 'duotone' }`
- [ ] **Colores vía CSS**: NO crear iconos por color
- [ ] **Twig**: `{{ jaraba_icon('cat', 'name', { size: '20px' }) }}` — NUNCA emojis inline
- [ ] **SCSS content:**: Usar escapes Unicode texto (`'\26A0'`, `'\2726'`) — NUNCA emojis color

### 5. Componentes SDC (Drupal 11)

- [ ] **Estructura**: `.component.yml` + `.twig` + `.scss`
- [ ] **Compound Variants**: Un template, múltiples variantes
- [ ] **Props tipados**: Definir en component.yml
- [ ] **Slots**: Para contenido personalizable

### 6. Entidades Drupal (Content Entities)

- [ ] **Interface**: Crear `*Interface.php` para cada entidad
- [ ] **Anotaciones**: `@ContentEntityType` completas
- [ ] **Campos base**: `created`, `changed`, `uuid`
- [ ] **Permisos**: Añadir a `.permissions.yml`

### 7. APIs y Servicios

- [ ] **Inyección de dependencias**: Constructores tipados
- [ ] **Logger**: Usar `@logger.channel.{module}`
- [ ] **Config**: No hardcodear valores, usar `ConfigFactory`

---

## 📁 Referencias a Workflows

Para guías detalladas, consultar:

- `/scss-estilos` - SCSS y variables inyectables
- `/i18n-traducciones` - Internacionalización
- `/sdc-components` - SDC con Compound Variants
- `/drupal-custom-modules` - Content Entities

---

## 📋 Checklist Rápido (Copiar a cada PR)

```markdown
### Pre-commit Checklist
- [ ] i18n: Textos con `{% trans %}` / `$this->t()`
- [ ] SCSS: No CSS directo, variables `var(--ej-*)`
- [ ] Colores: Paleta 7 colores Jaraba + `color-mix()` para variantes
- [ ] Iconos: `jaraba_icon('cat', 'name', {opts})` — sin emojis Unicode
- [ ] SDC: component.yml + twig + scss
- [ ] Compilado: `npm run build` + `drush cr`
```

---

*Última actualización: 2026-02-12*
