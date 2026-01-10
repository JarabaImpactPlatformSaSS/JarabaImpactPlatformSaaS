# Stripe Production Configuration Guide

> **Fecha**: 2026-01-10  
> **Estado**: Documentación de configuración

## 1. Requisitos Previos

- [ ] Cuenta de Stripe activada en modo live
- [ ] Verificación de identidad completada
- [ ] Cuenta bancaria configurada para payouts
- [ ] Webhooks endpoint accesible públicamente (HTTPS)

## 2. Variables de Entorno Requeridas

```bash
# settings.local.php o variables de entorno del servidor
STRIPE_LIVE_PUBLIC_KEY="pk_live_..."
STRIPE_LIVE_SECRET_KEY="sk_live_..."
STRIPE_LIVE_WEBHOOK_SECRET="whsec_..."
```

## 3. Configuración en Drupal

### 3.1 Acceder a la configuración
1. Ir a `/admin/config/ecosistema-jaraba/stripe`
2. O via Drush: `lando drush cset ecosistema_jaraba_core.stripe ...`

### 3.2 Campos a configurar

| Campo | Valor | Notas |
|-------|-------|-------|
| Mode | `live` | Cambiar de `test` a `live` |
| Public Key | `pk_live_...` | Visible en frontend |
| Secret Key | `sk_live_...` | **Nunca exponer** |
| Webhook Secret | `whsec_...` | Verificación de firma |

## 4. Webhooks Configuration

### 4.1 Crear Webhook en Stripe Dashboard

1. Ir a Stripe Dashboard > Developers > Webhooks
2. Add endpoint: `https://your-domain.com/stripe/webhook`
3. Seleccionar eventos:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.paid`
   - `invoice.payment_failed`

### 4.2 Copiar Webhook Secret

```bash
# El webhook secret se muestra una sola vez
whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

## 5. Products y Prices

### 5.1 Crear productos en Stripe

| Producto | Price ID (ejemplo) | Precio |
|----------|-------------------|--------|
| Plan Starter | `price_1O...` | €29/mes |
| Plan Pro | `price_1O...` | €79/mes |
| Plan Enterprise | `price_1O...` | €199/mes |

### 5.2 Actualizar SaasPlan entities

Editar cada plan en `/admin/structure/saas-plans` y añadir el `stripe_price_id`.

## 6. Stripe Connect (Marketplace)

Para payouts a productores:

1. Configurar Connect en Stripe Dashboard
2. Habilitar Express/Standard accounts
3. Configurar porcentaje de comisión de plataforma

## 7. Checklist Final

- [ ] Claves live configuradas
- [ ] Webhook endpoint respondiendo 200
- [ ] Eventos de webhook procesándose
- [ ] Products/Prices creados en Stripe
- [ ] SaasPlan entities actualizadas con stripe_price_id
- [ ] Stripe Connect configurado (opcional)
- [ ] Prueba de checkout end-to-end
- [ ] Prueba de cancel/upgrade subscription
