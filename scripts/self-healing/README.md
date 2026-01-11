# Jaraba Self-Healing Scripts

Scripts automatizados de auto-recuperación basados en los hallazgos del **Game Day #1** (2026-01-11).

## Arquitectura

```
scripts/self-healing/
├── config.sh           # Configuración común (emails, timeouts, etc.)
├── run-all.sh          # Script maestro para cron
├── db-health.sh        # Health check de base de datos
├── qdrant-health.sh    # Health check de Qdrant
└── cache-recovery.sh   # Health check de cache Drupal
```

## Quick Start

```bash
# Dar permisos de ejecución
chmod +x scripts/self-healing/*.sh

# Ejecutar todos los checks manualmente
./scripts/self-healing/run-all.sh

# Ejecutar un check específico
./scripts/self-healing/db-health.sh
./scripts/self-healing/qdrant-health.sh
./scripts/self-healing/cache-recovery.sh
```

## Configuración de Cron

Añadir al crontab (`crontab -e`):

```cron
# Jaraba Self-Healing - ejecutar cada minuto
* * * * * /path/to/JarabaImpactPlatformSaaS/scripts/self-healing/run-all.sh >> /var/log/jaraba-self-healing/cron.log 2>&1
```

## Health Checks

### 1. Database Health (`db-health.sh`)
- **Prioridad:** 🔴 CRÍTICA
- **Frecuencia:** Cada minuto
- **Detecta:** Contenedor parado, pausado, MySQL no responde
- **Acción:** Auto-start, unpause, restart
- **Hallazgo Game Day:** Contenedor pausado causa timeout indefinido

### 2. Qdrant Health (`qdrant-health.sh`)
- **Prioridad:** 🔴 ALTA
- **Frecuencia:** Cada minuto
- **Detecta:** Contenedor parado, pausado, HTTP timeout
- **Acción:** Auto-start, unpause, restart con backoff exponencial

### 3. Cache Recovery (`cache-recovery.sh`)
- **Prioridad:** 🟠 MEDIA
- **Frecuencia:** Cada 5 minutos
- **Detecta:** Sitio lento (>3s), errores HTTP
- **Acción:** `drush cr` + warm critical pages

## Notificaciones

Las notificaciones se envían por email cuando:
- ✅ Se auto-recupera un servicio exitosamente
- ❌ La auto-recuperación falla y se requiere intervención manual

Configurar email en `config.sh`:
```bash
NOTIFY_EMAIL="admin@jarabaimpact.com"
```

## Logs

Los logs se guardan en `/var/log/jaraba-self-healing/`:
- `self-healing.log` - Log principal de todos los scripts
- `cron.log` - Output de ejecuciones via cron

## MTTR Objetivos

| Servicio | MTTR Objetivo | MTTR Medido (Game Day) |
|----------|---------------|------------------------|
| Database | < 10s | < 5s ✅ |
| Qdrant | < 30s | < 100ms ✅ |
| Cache | < 1 min | < 3s ✅ |

## Basado en

- **Game Day #1:** 2026-01-11
- **Documento:** `docs/implementacion/2026-01-11_game-day-1-chaos-engineering.md`
- **Sprint:** Level 5 - Sprint 3: Runbooks Self-Healing v2
