# Jaraba AIOps - Piloto

Sistema de operaciones inteligentes basado en detección de anomalías.

## Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                    AIOPS PIPELINE                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐ │
│   │   Collect    │───▶│   Detect     │───▶│   Remediate  │ │
│   │   Metrics    │    │   Anomalies  │    │   (Auto)     │ │
│   └──────────────┘    └──────────────┘    └──────────────┘ │
│         │                    │                    │         │
│         ▼                    ▼                    ▼         │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐ │
│   │  JSON Logs   │    │  Anomaly Log │    │   Notify     │ │
│   │  (metrics)   │    │  (alerts)    │    │   (Email)    │ │
│   └──────────────┘    └──────────────┘    └──────────────┘ │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Quick Start

```powershell
# Ejecutar pipeline completo
.\scripts\aiops\run-aiops.ps1

# Con auto-remediación habilitada
.\scripts\aiops\run-aiops.ps1 -AutoRemediate

# Verbose + notificaciones
.\scripts\aiops\run-aiops.ps1 -Verbose -Notify

# Solo recolectar métricas
.\scripts\aiops\collect-metrics.ps1 -Verbose

# Solo detectar anomalías
.\scripts\aiops\detect-anomalies.ps1
```

## Scripts

| Script | Función |
|--------|---------|
| `collect-metrics.ps1` | Recolecta métricas del sistema |
| `detect-anomalies.ps1` | Detecta anomalías con análisis estadístico |
| `run-aiops.ps1` | Pipeline completo |

## Métricas Recolectadas

- **Site:** Response time, status code, availability
- **Appserver:** CPU %, Memory %
- **Database:** Status, paused, connections
- **Qdrant:** Status, points count

## Detección de Anomalías

### Umbrales Estáticos

| Métrica | Warning | Critical |
|---------|---------|----------|
| Response Time | 1000ms | 3000ms |
| CPU % | 70% | 90% |
| Memory % | 80% | 95% |

### Umbrales Dinámicos

Usa análisis estadístico basado en histórico:
- Calcula media y desviación estándar de las últimas N muestras
- Alerta si el valor actual > media + (2.5 × std)

## Datos

Los datos se almacenan en `scripts/aiops/data/`:
- `metrics_YYYY-MM-DD.jsonl` - Métricas diarias (JSON Lines)
- `anomalies.log` - Log de anomalías detectadas

## Integración con Self-Healing

El piloto AIOps se integra con los scripts de self-healing:
- Si detecta DB pausada → ejecuta `docker unpause`
- Si detecta site lento → ejecuta `drush cr`
- Logs unificados para troubleshooting

## Próximas Mejoras (Roadmap)

1. **ML Real:** Implementar Isolation Forest para detección más sofisticada
2. **Predicción:** Usar Prophet/ARIMA para capacity planning
3. **Dashboard:** Visualización con Grafana
4. **Notificaciones:** Integración completa con email/Slack

---

**Sprint:** Level 5 - Sprint 5 (Piloto AIOps)  
**Fecha:** 2026-01-11
