# Aprendizaje: Cypress E2E en Entorno WSL + Lando

**Fecha:** 2026-01-24  
**Contexto:** Implementación de Cypress E2E para Bloque A.2 Frontend Premium

---

## Problema

Ejecutar Cypress E2E en un entorno Windows + WSL + Lando Docker presenta múltiples desafíos:
- npm de Windows vs npm de WSL (interoperabilidad PATH)
- Rutas UNC no soportadas por cmd.exe
- Conectividad Docker↔Lando por resolución DNS
- Permisos de sistema de archivos cruzados

## Solución Implementada

### 1. Configuración WSL Nativa (Recomendada)
```bash
# Instalar dependencias de sistema para Cypress
sudo apt-get install -y libgtk2.0-0 libgtk-3-0 libgbm-dev \
  libnotify-dev libnss3 libxss1 libasound2 libxtst6 xauth xvfb libnspr4

# Ejecutar desde WSL
cd /home/PED/JarabaImpactPlatformSaaS/tests/e2e
npx cypress run --project . --config video=false
```

### 2. cypress.config.js Simplificado
```javascript
// SIN require('cypress') - npx lo provee
module.exports = {
  e2e: {
    baseUrl: 'https://jaraba-saas.lndo.site',
    // ... config
  }
};
```

### 3. Docker Compose (CI/CD)
```yaml
services:
  cypress:
    image: cypress/included:13.6.0
    networks:
      - lando_bridge
networks:
  lando_bridge:
    external: true
    name: landoproxyhyperion5000gandalfedition_edge
```

## Patrones Clave

| Problema | Solución |
|----------|----------|
| npm usa Node de Windows | Ejecutar desde terminal WSL pura |
| node_modules corrupto | `rm -rf node_modules && npm install` |
| Cypress no encuentra módulo | No usar `require('cypress')` en config |
| Faltan librerías sistema | Instalar dependencias GTK/NSS |
| Permisos videos | `--config video=false` o `chown` |

## Tests Validados

| Categoría | Tests Pasados |
|-----------|---------------|
| Auth | Login form, admin login |
| Homepage | Page load, header, hero, footer, a11y, responsive |
| Components | Header, hero, footer, forms, badges, icons |
| SEPE | Config access |

## Archivos Creados

```
tests/e2e/
├── cypress.config.js          # Config CommonJS
├── docker-compose.cypress.yml # Para CI/CD
├── package.json               # Scripts npm
├── README.md
└── cypress/
    ├── e2e/                   # 7 specs
    ├── fixtures/testData.json
    └── support/commands.js
```

## Lecciones

1. **WSL Interop puede causar conflictos** - npm puede llamar a binarios de Windows
2. **Cypress requiere librerías X11** - No es solo Node.js
3. **Docker necesita nombre exacto de red Lando** - `docker network ls | grep lando`
4. **Videos requieren permisos de escritura** - Desactivar o chown
