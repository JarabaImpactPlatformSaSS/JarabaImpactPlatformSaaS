# Guía de Configuración: Monitoring Externo

**Fecha:** 2026-02-10  
**Estado:** Pendiente de configuración manual

## UptimeRobot (Recomendado — Free Tier)

### Configuración

1. Registrarse en [UptimeRobot](https://uptimerobot.com) (plan gratuito: 50 monitores, 5 min)
2. Crear nuevo monitor:

| Campo | Valor |
|-------|-------|
| **Monitor Type** | HTTP(s) - Keyword |
| **Friendly Name** | Jaraba SaaS - Production |
| **URL** | `https://plataformadeecosistemas.com/health` |
| **Keyword** | `"status":"ok"` |
| **Keyword Type** | Keyword exists |
| **Monitoring Interval** | 5 minutes |

3. Configurar alertas:
   - Email principal del proyecto
   - Slack webhook (mismo del CI/CD): `secrets.SLACK_WEBHOOK`

### Monitor adicional (opcional)

| Campo | Valor |
|-------|-------|
| **Monitor Type** | HTTP(s) |
| **Friendly Name** | Jaraba SaaS - Homepage |
| **URL** | `https://plataformadeecosistemas.com` |
| **Monitoring Interval** | 5 minutes |

## Status Page (Opcional)

UptimeRobot Free incluye status page pública:
- URL sugerida: `https://stats.uptimerobot.com/jaraba-saas`
- Incluir ambos monitores (Health + Homepage)

## Arquitectura de Monitoring Actual

```
┌──────────────────────────────────────────────────────┐
│                    MONITORING                        │
│                                                      │
│  EXTERNO:                                            │
│  ├── UptimeRobot → /health (cada 5 min)             │
│  └── deploy.yml → /health (post-deploy, 3 retries)  │
│                                                      │
│  INTERNO:                                            │
│  ├── hook_cron → health check (cada hora)            │
│  │   ├── Loguea en health_check_log                  │
│  │   └── Email alert si degraded                     │
│  ├── /admin/health → Dashboard admin (visual)        │
│  └── /admin/health/api → API JSON (admin-only)       │
│                                                      │
│  BACKUPS:                                            │
│  └── verify-backups.yml → SSH IONOS (lunes 9:00)     │
└──────────────────────────────────────────────────────┘
```
