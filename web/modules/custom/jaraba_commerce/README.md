# Jaraba Commerce

Módulo custom de integración de Drupal Commerce 3.x para la plataforma Jaraba Impact.

## Descripción

Este módulo extiende las funcionalidades nativas de Drupal Commerce para adaptarlas a las necesidades de la plataforma SaaS multi-tenant.

## Características

- **GEO Optimization**: Productos con Answer Capsules para motores generativos
- **Stripe Connect**: Split payments automáticos plataforma/tenant
- **Multi-tenant**: Aislamiento de productos por tenant
- **Schema.org**: Markup JSON-LD para productos

## Dependencias

- `commerce` (Drupal Commerce 3.x)
- `commerce_stripe`
- `ecosistema_jaraba_core`

## Instalación

```bash
drush en jaraba_commerce -y
drush cr
```

## Configuración

1. Configurar credenciales Stripe en `/admin/commerce/config/stripe`
2. Habilitar GEO optimization en `/admin/config/jaraba/commerce`

## Servicios

| Servicio | Descripción |
|----------|-------------|
| `jaraba_commerce.product_display` | Renderizado GEO optimizado |
| `jaraba_commerce.stripe_connect` | Gestión de split payments |

## Mantenimiento

- **Autor**: Jaraba Development Team
- **Versión**: 1.0.0
- **Compatibilidad**: Drupal 11.x, PHP 8.4+
