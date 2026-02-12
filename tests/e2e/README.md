# Cypress E2E Tests - Jaraba Impact Platform

Tests end-to-end automatizados para verificar el funcionamiento del SaaS.

## Estructura

```
tests/e2e/
├── cypress.config.js     # Configuración principal
├── package.json          # Dependencias
├── cypress/
│   ├── e2e/              # Specs de tests
│   │   ├── auth.cy.js         # Autenticación
│   │   ├── homepage.cy.js     # Homepage
│   │   ├── theming.cy.js      # Visual Picker
│   │   ├── components.cy.js   # UI Components
│   │   ├── sepe.cy.js         # SEPE Teleformación
│   │   ├── empleabilidad.cy.js # Vertical
│   │   └── emprendimiento.cy.js # Vertical
│   ├── fixtures/         # Datos de prueba
│   │   └── testData.json
│   └── support/          # Comandos y configuración
│       ├── commands.js
│       └── e2e.js
```

## Instalación

```bash
cd tests/e2e
npm install
```

## Ejecución

```bash
# Abrir Cypress UI (desarrollo)
npm run cy:open

# Ejecutar tests headless
npm run cy:run

# Ejecutar tests específicos
npx cypress run --spec "cypress/e2e/auth.cy.js"

# Ejecutar por tag
npx cypress run --env grepTags="@smoke"
```

## Tags Disponibles

| Tag | Descripción |
|-----|-------------|
| `@smoke` | Tests críticos básicos |
| `@auth` | Autenticación |
| `@theming` | Visual Picker y presets |
| `@components` | UI Components |
| `@sepe` | SEPE Teleformación |
| `@empleabilidad` | Vertical Empleabilidad |
| `@emprendimiento` | Vertical Emprendimiento |
| `@admin` | Requiere login admin |
| `@visual` | Tests visuales |

## Comandos Custom

| Comando | Uso |
|---------|-----|
| `cy.loginAsAdmin()` | Login como admin |
| `cy.loginAsUser(email, pass)` | Login usuario |
| `cy.logout()` | Cerrar sesión |
| `cy.verifyHeader(variant)` | Verificar header |
| `cy.verifyHero(variant)` | Verificar hero |
| `cy.selectIndustryPreset(id)` | Aplicar preset |
| `cy.verifyCssVar(name, value)` | Verificar CSS var |
| `cy.checkA11y()` | Accesibilidad básica |
| `cy.checkResponsive(viewport)` | Test responsive |

## Variables de Entorno

```js
// cypress.config.js
env: {
  adminUsername: 'admin',
  adminPassword: 'admin',
  empleabilidadUrl: '/empleabilidad',
  emprendimientoUrl: '/emprendimiento',
}
```

## CI/CD

Para integración continua:

```bash
npm run test:e2e:ci
```

Configurar en GitHub Actions:

```yaml
- name: Run Cypress tests
  run: |
    cd tests/e2e
    npm ci
    npm run test:e2e:ci
```

## Docker (Alternativa)

Para ejecutar con Docker sin instalar Node.js:

```bash
cd tests/e2e
docker-compose -f docker-compose.cypress.yml run --rm cypress run --spec 'cypress/e2e/auth.cy.js'
```

**Nota:** Docker requiere configuración de red para conectar con Lando. La ejecución recomendada en desarrollo es via WSL nativo.
