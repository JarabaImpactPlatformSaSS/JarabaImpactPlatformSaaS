---
description: Ejecutar tests Cypress E2E en entorno WSL + Lando
---

# Cypress E2E Testing

## Prerequisitos

1. Node.js instalado en WSL (v18+)
2. Lando ejecutándose con el sitio activo
3. Dependencias de sistema instaladas

## Instalación (Primera vez)

```bash
# Instalar dependencias de sistema
sudo apt-get update
sudo apt-get install -y libgtk2.0-0 libgtk-3-0 libgbm-dev \
  libnotify-dev libnss3 libxss1 libasound2 libxtst6 xauth xvfb libnspr4
```

## Ejecución

```bash
# En terminal WSL Ubuntu
cd /home/PED/JarabaImpactPlatformSaaS/tests/e2e

# Ejecutar todos los tests (headless)
npx cypress run --project . --config video=false

# Ejecutar spec específico
npx cypress run --project . --spec 'cypress/e2e/auth.cy.js'

# Con UI (requiere WSLg/X11)
npx cypress open --project .
```

## Solución de Problemas

### Error: npm usa Node de Windows
```bash
# Verificar
which npm  # Debe ser /usr/bin/npm
node --version  # Debe ser versión Linux

# Si hay conflicto, usar ruta completa
/usr/bin/npm install
```

### Error: EACCES permisos videos
```bash
npx cypress run --project . --config video=false
# O arreglar permisos
sudo chown -R $(whoami) cypress/
```

### Error: Cannot find module 'cypress'
El archivo `cypress.config.js` NO debe usar `require('cypress')`.
```javascript
// CORRECTO:
module.exports = { e2e: { ... } };

// INCORRECTO:
const { defineConfig } = require('cypress');
```

## Estructura de Tests

| Spec | Cobertura |
|------|-----------|
| auth.cy.js | Login/logout |
| homepage.cy.js | Landing, header, hero, footer, a11y |
| theming.cy.js | Visual Picker, presets |
| components.cy.js | Cards, heroes, headers |
| sepe.cy.js | SEPE Teleformación |
| empleabilidad.cy.js | Vertical Empleabilidad |
| emprendimiento.cy.js | Vertical Emprendimiento |
