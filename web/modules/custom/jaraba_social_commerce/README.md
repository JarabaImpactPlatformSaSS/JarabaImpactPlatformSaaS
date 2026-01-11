# Jaraba Social Commerce

Módulo de integración de comercio social para la plataforma Jaraba Impact.

## Descripción

Este módulo permite la publicación automática de productos en redes sociales y la gestión de ventas desde múltiples canales.

## Características

- **Multi-canal**: Facebook, Instagram, TikTok, Pinterest
- **Make.com Integration**: Hub de automatización
- **Auto-posting**: Publicación automática de nuevos productos
- **Social Proof**: Integración de reviews y testimonios

## Dependencias

- `ecosistema_jaraba_core`
- `jaraba_commerce`
- Cuenta Make.com (para automatizaciones)

## Instalación

```bash
drush en jaraba_social_commerce -y
drush cr
```

## Configuración

1. Configurar webhooks Make.com en `/admin/config/jaraba/social`
2. Conectar cuentas de redes sociales

## Estado

🔄 **En desarrollo** - Funcionalidades base implementadas

## Mantenimiento

- **Autor**: Jaraba Development Team
- **Versión**: 0.1.0
- **Compatibilidad**: Drupal 11.x, PHP 8.4+
