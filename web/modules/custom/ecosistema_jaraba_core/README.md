# Ecosistema Jaraba Core

> Módulo Drupal 11 para plataforma SaaS multi-tenant

[![Drupal 11](https://img.shields.io/badge/Drupal-11-blue.svg)](https://www.drupal.org)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-purple.svg)](https://www.php.net)
[![License: GPL-2.0+](https://img.shields.io/badge/License-GPL--2.0+-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## Descripción

Módulo core de la plataforma Ecosistema Jaraba que proporciona:

- **Multi-tenancy**: Gestión de verticales de negocio y tenants
- **Planes SaaS**: Suscripciones con límites configurables
- **Integración Stripe**: Pagos, suscripciones y webhooks
- **Firma Digital**: Integración con AutoFirma
- **Onboarding**: Flujo completo de registro de nuevos tenants

## Requisitos

- Drupal 11.x
- PHP 8.2+
- Composer

### Dependencias Drupal

```yaml
- drupal:user
- drupal:field
- drupal:text
- drupal:options
- drupal:datetime
```

### Dependencias Composer

```bash
composer require stripe/stripe-php
```

## Instalación

```bash
# 1. Copiar el módulo
cp -r ecosistema_jaraba_core web/modules/custom/

# 2. Instalar dependencias PHP
composer require stripe/stripe-php

# 3. Compilar SCSS
cd web/modules/custom/ecosistema_jaraba_core
npm install sass --save-dev
npx sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed

# 4. Habilitar el módulo
drush en ecosistema_jaraba_core -y
drush cr
```

## Configuración

### Variables de Entorno (Producción)

```bash
STRIPE_PUBLIC_KEY=pk_live_xxx
STRIPE_SECRET_KEY=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
```

### settings.local.php

```php
$config['ecosistema_jaraba_core.stripe']['public_key'] = getenv('STRIPE_PUBLIC_KEY');
$config['ecosistema_jaraba_core.stripe']['secret_key'] = getenv('STRIPE_SECRET_KEY');
$config['ecosistema_jaraba_core.stripe']['webhook_secret'] = getenv('STRIPE_WEBHOOK_SECRET');
```

## Estructura del Módulo

```
ecosistema_jaraba_core/
├── config/install/          # Configuraciones por defecto
├── css/                     # CSS compilado
├── images/cards/            # Iconos de tarjetas de crédito
├── js/                      # JavaScript (onboarding, stripe, firma)
├── scss/                    # Fuentes SCSS modulares
├── templates/               # Templates Twig
├── src/
│   ├── Controller/          # Controladores
│   ├── Entity/              # Content Entities
│   ├── Form/                # Formularios
│   └── Service/             # Servicios de negocio
└── tests/                   # PHPUnit tests
```

## Entidades

| Entidad | Tipo | Descripción |
|---------|------|-------------|
| `Vertical` | Content | Líneas de negocio (AgroConecta, FormaTech) |
| `SaasPlan` | Content | Planes de suscripción con límites |
| `Tenant` | Content | Organizaciones suscritas |

## Servicios

| Servicio | ID | Descripción |
|----------|-----|-------------|
| TenantManager | `ecosistema_jaraba_core.tenant_manager` | Ciclo de vida de tenants |
| PlanValidator | `ecosistema_jaraba_core.plan_validator` | Validación de límites |
| TenantOnboardingService | `ecosistema_jaraba_core.tenant_onboarding` | Flujo de registro |

## Rutas Principales

### Administración

- `/admin/structure/verticales` - Gestión de verticales
- `/admin/structure/saas-plans` - Gestión de planes
- `/admin/structure/tenants` - Gestión de tenants

### Onboarding

- `/registro/{vertical}` - Formulario de registro público
- `/onboarding/seleccionar-plan` - Selección de plan
- `/onboarding/configurar-pago` - Configuración de pago
- `/onboarding/bienvenida` - Página de bienvenida

### API

- `POST /api/stripe/create-subscription` - Crear suscripción
- `POST /webhook/stripe` - Webhook de Stripe

## Tests

```bash
# Ejecutar todos los tests
./vendor/bin/phpunit -c web/modules/custom/ecosistema_jaraba_core/phpunit.xml

# Solo tests unitarios
./vendor/bin/phpunit -c web/modules/custom/ecosistema_jaraba_core/phpunit.xml --testsuite Unit

# Solo tests de kernel
./vendor/bin/phpunit -c web/modules/custom/ecosistema_jaraba_core/phpunit.xml --testsuite Kernel
```

## Desarrollo

### Compilar SCSS (modo watch)

```bash
cd web/modules/custom/ecosistema_jaraba_core
npx sass scss/main.scss css/ecosistema-jaraba-core.css --watch
```

### Variables CSS Inyectables

Las variables de color y tipografía se pueden personalizar por vertical/tenant:

```css
:root {
  --ej-color-primary: #2E7D32;
  --ej-color-secondary: #1B5E20;
  --ej-font-family: 'Inter', sans-serif;
}
```

## Licencia

GPL-2.0-or-later

## Autor

Ecosistema Jaraba - [jaraba.io](https://jaraba.io)
