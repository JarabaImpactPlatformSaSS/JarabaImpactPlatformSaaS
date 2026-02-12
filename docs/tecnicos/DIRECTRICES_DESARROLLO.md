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

### 3. Paleta de Colores Jaraba

| Variable | Hex | Uso |
|----------|-----|-----|
| `--ej-color-corporate` | #233D63 | Azul base (la "J") |
| `--ej-color-impulse` | #FF8C42 | Naranja empresas |
| `--ej-color-innovation` | #00A9A5 | Verde talento |
| `--ej-color-agro` | #556B2F | Verde campo |

### 4. Iconografía

- [ ] **Formato**: `jaraba_icon('category', 'name', {options})`
- [ ] **Categorías**: analytics, business, ai, ui, actions, verticals
- [ ] **Variantes**: `{ variant: 'outline' }` o `{ variant: 'duotone' }`
- [ ] **Colores vía CSS**: NO crear iconos por color

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
- [ ] Colores: Paleta Jaraba (corporate, impulse, innovation, agro)
- [ ] Iconos: `jaraba_icon('cat', 'name', {opts})`
- [ ] SDC: component.yml + twig + scss
- [ ] Compilado: `npm run build` + `drush cr`
```

---

*Última actualización: 2026-01-23*
