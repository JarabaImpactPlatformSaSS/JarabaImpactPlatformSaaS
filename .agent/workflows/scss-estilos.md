---
description: Directrices para trabajar con estilos SCSS y variables inyectables
---

# Workflow: Estilos SCSS y Variables Inyectables

> [!CAUTION]
> ## ⛔ REGLA INQUEBRANTABLE
> **NUNCA crear archivos CSS directamente.** Siempre crear archivos SCSS que se compilan a CSS.
> **SIEMPRE usar variables CSS inyectables** (`var(--ej-*)`) para colores, tipografía y spacing.
> Este patrón permite personalización por tenant/vertical desde la UI de Drupal sin recompilar.

> [!NOTE]
> **URL de desarrollo local:** `https://jaraba-saas.lndo.site`
> No usar jaraba-impact-platform.lndo.site para verificaciones.

> [!IMPORTANT]
> ## 📚 Documento Maestro de Arquitectura
> Para la especificación completa del patrón **Federated Design Tokens**, consultar:
> - **Proyecto:** [`docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md)
> - **KI:** `knowledge/standards/theming_architecture_master.md`

## 🎨 Directrices de Iconografía SVG

> [!CAUTION]
> ## ⛔ REGLA DE ICONOS
> **SIEMPRE crear AMBAS versiones de cada nuevo icono:**
> 1. `{nombre}.svg` - Versión outline (trazo)
> 2. `{nombre}-duotone.svg` - Versión duotone (2 tonos con opacity)
>
> Los **colores se aplican dinámicamente** via CSS filter desde `jaraba_icon()`.
> NO crear archivos separados por color.

### Ubicación de Iconos
```
web/modules/custom/ecosistema_jaraba_core/images/icons/
├── analytics/    # Gráficos, métricas, análisis
├── business/     # Empresa, diagnóstico, objetivos
├── ai/           # IA, automatización, cerebro
├── ui/           # Interfaz, navegación, controles
├── actions/      # Acciones CRUD, refresh, download
└── verticals/    # Verticales específicos (agro, empleo)
```

### Estructura de Duotone SVG
```svg
<!-- Capa de fondo (opacity 0.3) -->
<path d="..." fill="currentColor" opacity="0.3"/>
<!-- Capa principal (stroke o fill sólido) -->
<path d="..." stroke="currentColor" stroke-width="2"/>
```

### Uso en Templates
```twig
{# Outline (default) - para KPIs, botones, elementos pequeños #}
{{ jaraba_icon('business', 'diagnostic', { color: 'azul-corporativo', size: '24px' }) }}

{# Duotone - para headers de sección, cards destacadas, impacto visual #}
{{ jaraba_icon('business', 'diagnostic', { variant: 'duotone', color: 'naranja-impulso', size: '32px' }) }}
```

### Colores Disponibles (Paleta de Marca)
- `azul-profundo`, `azul-verdoso`, `azul-corporativo`
- `naranja-impulso`, `verde-innovacion`
- `verde-oliva`, `verde-oliva-oscuro`
- `success`, `warning`, `danger`, `neutral`



## Ubicación de Archivos

- **SCSS parciales**: `web/modules/custom/ecosistema_jaraba_core/scss/`
- **CSS compilado**: `web/modules/custom/ecosistema_jaraba_core/css/ecosistema-jaraba-core.css`
- **Archivo principal**: `scss/main.scss`

## Variables

### Variables SCSS (`_variables.scss`)
- `$ej-color-primary-fallback`, `$ej-spacing-md`, etc.
- Valores por defecto que se usan durante la compilación

### Variables CSS Inyectables (`_injectable.scss`)
- `var(--ej-color-primary)`, `var(--ej-text-muted)`, etc.
- Se pueden sobrescribir en runtime desde Drupal (por vertical/tenant)

## Reglas Obligatorias

1. **Siempre usar variables CSS inyectables** cuando existan:
   ```scss
   // ✅ CORRECTO
   color: var(--ej-color-primary, #{$ej-color-primary-fallback});
   
   // ❌ INCORRECTO
   color: #2E7D32;
   ```

2. **Crear un parcial SCSS** por cada dashboard/componente:
   ```
   scss/_mi-componente.scss
   ```

3. **Importar en main.scss**:
   ```scss
   @use 'mi-componente';
   ```

4. **Usar `@use 'variables' as *;`** al inicio de cada parcial

5. **No crear archivos .css directamente** - siempre SCSS que se compila

## Compilación (IMPORTANTE - Usar NVM)

> [!WARNING]
> **En WSL/Linux, npm de Windows puede interferir.** Siempre cargar NVM manualmente antes de compilar.

// turbo-all
### Pasos de Compilación

```bash
# 1. Ir al directorio del módulo
cd /home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core

# 2. Cargar NVM manualmente
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

# 3. Activar versión de Node de Linux
nvm use --lts

# 4. Verificar que npm es correcto (debe ser /home/pepejaraba/.nvm/...)
which npm

# 5. Instalar dependencias (si es primera vez o hay cambios en package.json)
npm install

# 6. Dar permisos al binario de sass
chmod +x node_modules/.bin/sass

# 7. Compilar
npm run build

# 8. Limpiar caché de Drupal
lando drush cr
```

### Verificación de npm

- **✅ Correcto**: `/home/pepejaraba/.nvm/versions/node/.../bin/npm`
- **❌ Incorrecto**: `/mnt/c/Program Files/nodejs/npm` (npm de Windows)

Si npm sigue apuntando a Windows, reinstalar NVM:
```bash
rm -rf ~/.nvm
# Volver a instalar NVM desde https://github.com/nvm-sh/nvm
```

## 🎨 Paleta de Colores de Marca Jaraba

> [!IMPORTANT]
> **Estos son los colores oficiales de marca.** Usar SIEMPRE estas variables para mantener coherencia visual.

### Paleta Oficial Jaraba (7 colores)

| Variable SCSS | Variable CSS | Hex | Uso Semántico |
|---------------|--------------|-----|---------------|
| `$azul-profundo` | `--ej-color-azul-profundo` | `#003366` | Autoridad, profundidad |
| `$azul-verdoso` | `--ej-color-azul-verdoso` | `#2B7A78` | Conexión, equilibrio |
| `$azul-corporativo` | `--ej-color-corporate` | `#233D63` | La "J", confianza, base |
| `$naranja-impulso` | `--ej-color-impulse` | `#FF8C42` | Empresas, emprendimiento |
| `$verde-innovacion` | `--ej-color-innovation` | `#00A9A5` | Talento, empleabilidad |
| `$verde-oliva` | `--ej-color-agro` | `#556B2F` | AgroConecta, naturaleza |
| `$verde-oliva-oscuro` | `--ej-color-agro-dark` | `#3E4E23` | AgroConecta intenso |

### Aliases Semánticos (Twig)

```twig
{{ jaraba_color('corporate') }}   → #233D63
{{ jaraba_color('innovation') }}  → #00A9A5
{{ jaraba_color('impulse') }}     → #FF8C42
{{ jaraba_color('agro') }}        → #556B2F
```

### Colores UI Extendidos

| Variable CSS | Hex | Descripción |
|--------------|-----|-------------|
| `--ej-color-primary` | `#4F46E5` | Indigo - Acciones primarias UI |
| `--ej-color-secondary` | `#7C3AED` | Violeta - IA, features premium |
| `--ej-color-success` | `#10B981` | Esmeralda - Estados positivos |
| `--ej-color-warning` | `#F59E0B` | Ámbar - Alertas |
| `--ej-color-danger` | `#EF4444` | Rojo - Errores, destructivo |
| `--ej-color-neutral` | `#64748B` | Slate - Muted, disabled |

### Variables de Tipografía y Layout

| Variable CSS | Descripción |
|--------------|-------------|
| `--ej-font-family` | Tipografía principal |
| `--ej-text-primary` | Color texto principal |
| `--ej-text-muted` | Color texto secundario |
| `--ej-border-color` | Color de bordes |
| `--ej-shadow-md` | Sombra media |

### Uso en SCSS

```scss
// ✅ CORRECTO: Usar variables de marca
.hero-section {
  background: var(--ej-color-corporate, #233D63);
}

.talent-card {
  border-color: var(--ej-color-innovation, #00A9A5);
}

.cta-button {
  background: var(--ej-color-impulse, #FF8C42);
}
```

### Uso con Iconos (Twig)

```twig
{# Iconos con colores de marca #}
{{ jaraba_icon('business', 'diagnostic', { color: 'corporate' }) }}
{{ jaraba_icon('ai', 'brain', { color: 'innovation' }) }}
{{ jaraba_icon('actions', 'rocket', { color: 'impulse' }) }}

{# Obtener color para CSS inline #}
<div style="background: {{ jaraba_color('corporate') }}">
```

## Checklist Pre-Commit

- [ ] ¿Usé variables CSS inyectables donde aplica?
- [ ] ¿Creé parcial SCSS (no CSS directo)?
- [ ] ¿Añadí import en main.scss?
- [ ] ¿Compilé el CSS con `npm run build`?
- [ ] ¿Limpié caché de Drupal?

## Lecciones Aprendidas (2026-01-18)

1. **npx en WSL puede fallar** si npm de Windows interfiere. Solución: cargar NVM manualmente.
2. **El contenedor Lando no tiene node/npm**. La compilación debe hacerse desde WSL con NVM.
3. **Permisos del binario sass**: Siempre ejecutar `chmod +x node_modules/.bin/sass` después de `npm install`.
4. **El package.json debe tener script build**: `"build": "sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed"`

## Lecciones Aprendidas (2026-01-20)

5. **Docker Lando vs WSL para SCSS**: El contenedor `jarabasaas_appserver_1` NO tiene npm/node instalado. 
   - Para compilar SCSS usar WSL: `wsl bash -c "cd /home/PED/... && source ~/.nvm/nvm.sh && nvm use --lts && npm run build"`
   - Para comandos Drupal (drush cr) usar Docker: `docker exec jarabasaas_appserver_1 drush cr`
6. **Sincronización Z: drive**: Los archivos editados en Windows (Z:\) se sincronizan automáticamente con el contenedor Docker en `/app/`.

## Lecciones Aprendidas (2026-01-24)

7. **Nunca crear archivos CSS directos para módulos custom**. 
   - ❌ `jaraba_*/css/*.css` 
   - ✅ `ecosistema_jaraba_core/scss/_nuevo-componente.scss` + import en `main.scss`
8. **Library dependencies**: Si un módulo necesita estilos, declarar dependencia del CSS global:
   ```yaml
   # jaraba_*.libraries.yml
   my_module.dashboard:
     dependencies:
       - ecosistema_jaraba_core/global
   ```
9. **Paleta Jaraba obligatoria**: Usar siempre los aliases semánticos:
   - `corporate` (#233D63) - Base corporativa
   - `innovation` (#00A9A5) - Empleabilidad, talento
   - `impulse` (#FF8C42) - Emprendimiento, CTAs
   - `agro` (#556B2F) - AgroConecta

## Lecciones Aprendidas (2026-01-25)

10. **Compilación SCSS del tema desde Windows (sin WSL)**: Usar npx directamente desde PowerShell:
    ```powershell
    cd z:\home\PED\...\ecosistema_jaraba_theme
    # IMPORTANTE: El sitio carga main.css, NO ecosistema-jaraba-theme.css
    npx sass scss/main.scss:css/main.css --style=compressed
    ```
11. **Media queries con excepciones**: Para layouts especiales (ej. minimal), añadir overrides con clase en body:
    ```scss
    // Excepción para layout minimal en desktop
    .header-layout-minimal .mobile-menu-overlay {
        display: block !important;
    }
    ```

## Lecciones Aprendidas (2026-02-02)

12. **Patrón Premium Card Glassmorphism**: Para elevar cards de dashboards operativos a nivel premium:
    ```scss
    .premium-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.9));
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.8);
        box-shadow: 
            0 4px 24px rgba(0,0,0,0.04),
            0 1px 2px rgba(0,0,0,0.02),
            inset 0 1px 0 rgba(255,255,255,0.9);
    }
    ```

13. **Hover 3D Lift con Cubic-Bezier**: Micro-animaciones premium para interactividad:
    ```scss
    .premium-card:hover {
        transform: translateY(-6px) scale(1.02);
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    ```

14. **Sombras de Color para Brand Identity**: Aplicar sombras que coincidan con colores de marca de cada plataforma:
    - Meta: `box-shadow: 0 4px 16px rgba(0, 120, 255, 0.35)`
    - Google: `box-shadow: 0 4px 12px rgba(66, 133, 244, 0.2)`
    - LinkedIn: `box-shadow: 0 4px 16px rgba(10, 102, 194, 0.35)`

15. **Efecto Shine en Hover**: Crear efecto de brillo que barre la card:
    ```scss
    .premium-card::before {
        content: '';
        position: absolute;
        top: 0; left: -100%;
        width: 50%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: left 0.6s ease;
        pointer-events: none;
    }
    .premium-card:hover::before { left: 150%; }
    ```

## Lecciones Aprendidas (2026-02-05)

16. **Dualidad de archivos CSS del tema**: El tema `ecosistema_jaraba_theme` tiene dos archivos CSS:
    - `css/main.css` - ⚠️ **ESTE ES EL QUE CARGA EL SITIO** vía `ecosistema_jaraba_theme.libraries.yml`
    - `css/ecosistema-jaraba-theme.css` - Generado por `npm run build` en package.json (NO se usa directamente)
    
    **Comando correcto para compilar:**
    ```powershell
    cd z:\home\PED\JarabaImpactPlatformSaaS\web\themes\custom\ecosistema_jaraba_theme
    npx sass scss/main.scss:css/main.css --style=compressed
    docker exec jarabasaas_appserver_1 drush cr
    ```
    
    **⚠️ NUNCA usar el script `npm run build` del package.json** ya que genera el archivo incorrecto.

## Lecciones Aprendidas (2026-02-09)

17. **Dart Sass `@use` Module System — Cada Parcial es Independiente**:
    En Dart Sass, `@use` crea módulos aislados. Las variables importadas en `main.scss` NO se heredan a los parciales cargados con `@use`. Cada parcial DEBE declarar sus propios imports.
    ```scss
    // ❌ ERROR: _marketplace.scss sin imports propios
    .servicios-marketplace { max-width: $container-max; } // Undefined variable!

    // ✅ CORRECTO: _marketplace.scss con imports explícitos
    @use 'sass:color';
    @use 'variables' as *;
    .servicios-marketplace { max-width: $container-max; } // OK
    ```

18. **Módulos Verticales con SCSS Independiente**: Cada módulo vertical (AgroConecta, ServiciosConecta, ComercioConecta) tiene su propio `package.json` y pipeline SCSS separado del módulo core:
    ```
    jaraba_servicios_conecta/
    ├── package.json       → {"scripts": {"build": "sass scss/main.scss css/jaraba-servicios-conecta.css ..."}}
    ├── scss/_variables.scss → $servicios-primary: #2563EB; (colores propios del vertical)
    └── scss/main.scss     → Entry point con @use de cada parcial
    ```

19. **`color.scale()` vs `darken()`/`lighten()`**: Las funciones `darken()` y `lighten()` están deprecated en Dart Sass. Usar siempre `color.scale()`:
    ```scss
    @use 'sass:color';
    // ✅ Correcto
    background: color.scale($my-color, $lightness: 85%);
    // ❌ Deprecated
    background: lighten($my-color, 85%);
    ```

