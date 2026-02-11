# API Reference - Jaraba FOC

Documentación de endpoints REST del Centro de Operaciones Financieras.

## Autenticación

Todos los endpoints requieren autenticación Drupal y el permiso `access foc dashboard` o `administer foc`.

---

## Endpoints

### GET `/api/foc/metrics`

Obtiene las métricas financieras actuales del ecosistema.

**Permisos:** `access foc dashboard`

**Respuesta (200):**
```json
{
  "success": true,
  "data": {
    "mrr": "3830.00",
    "arr": "45960.00",
    "gross_margin": "94.66",
    "arpu": "1915.00",
    "ltv": "28725.00",
    "ltv_cac_ratio": "143.62",
    "cac_payback": "0.1"
  },
  "timestamp": 1704700800
}
```

---

### POST `/api/foc/snapshot`

Crea un snapshot de métricas para histórico.

**Permisos:** `administer foc`

**Respuesta (200):**
```json
{
  "success": true,
  "snapshot_id": 42,
  "message": "Snapshot creado correctamente."
}
```

---

### POST `/api/foc/stripe-webhook`

Endpoint para recibir webhooks de Stripe Connect.

**Autenticación:** Firma HMAC de Stripe (header `Stripe-Signature`)

**Eventos soportados:**
- `payment_intent.succeeded` → Registra transacción
- `invoice.paid` → Registra ingreso recurrente
- `charge.refunded` → Registra reembolso
- `account.updated` → Actualiza estado de vendedor
- `customer.subscription.created/deleted` → Tracking de suscripciones

---

## Rutas de UI

| Ruta | Descripción | Permiso |
|------|-------------|---------|
| `/admin/foc` | Dashboard principal | `access foc dashboard` |
| `/admin/foc/tenants` | Analítica de inquilinos | `access foc dashboard` |
| `/admin/foc/verticals` | Rentabilidad por vertical | `access foc dashboard` |
| `/admin/foc/projections` | Proyecciones AI | `access foc dashboard` |
| `/admin/foc/alerts` | Listado de alertas | `access foc dashboard` |
| `/admin/foc/transactions` | Libro mayor | `view financial transactions` |
| `/admin/config/jaraba/foc` | Configuración | `administer foc` |

---

## Integración con Stripe Connect

### Configuración
1. Obtener API keys en [dashboard.stripe.com](https://dashboard.stripe.com)
2. Configurar en `/admin/config/jaraba/foc`:
   - Secret Key (sk_live_xxx)
   - Webhook Secret (whsec_xxx)
3. Registrar webhook URL: `https://tudominio.com/api/foc/stripe-webhook`

### Destination Charges
El FOC usa Destination Charges para split payments:
```
Total Cobrado: €100
├── Application Fee (plataforma): €10 (10%)
└── Destino (cuenta vendedor): €90
```

---

## Permisos Disponibles

| Permiso | Descripción |
|---------|-------------|
| `access foc dashboard` | Ver dashboard y métricas |
| `view financial transactions` | Ver libro mayor |
| `administer foc` | Configuración completa |
