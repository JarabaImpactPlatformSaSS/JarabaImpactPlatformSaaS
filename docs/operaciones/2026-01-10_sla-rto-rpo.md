# SLA y Objetivos de Recuperación - Jaraba SaaS

> **Versión**: 1.0  
> **Fecha**: 2026-01-10

---

## Niveles de Servicio (SLA)

### Disponibilidad

| Tier | SLA Target | Downtime Mensual Máximo |
|------|------------|-------------------------|
| **Producción** | 99.5% | ~3.6 horas |
| **Staging** | 95% | ~36 horas |
| **Desarrollo** | Best effort | N/A |

### Tiempos de Respuesta

| Tipo | Objetivo |
|------|----------|
| Página principal | < 2 segundos |
| Dashboard tenant | < 3 segundos |
| API calls | < 500ms |
| Admin pages | < 4 segundos |

---

## Objetivos de Recuperación

### RTO (Recovery Time Objective)

**Tiempo máximo para restaurar el servicio tras una interrupción.**

| Escenario | RTO |
|-----------|-----|
| Caída de aplicación (PHP error) | 15 minutos |
| Corrupción de base de datos | 1 hora |
| Fallo de servidor IONOS | 4 horas |
| Desastre total (data center) | 24 horas |

### RPO (Recovery Point Objective)

**Cantidad máxima de datos que se pueden perder.**

| Escenario | RPO |
|-----------|-----|
| Backups automáticos diarios | 24 horas |
| Backups bajo demanda | Tiempo desde último backup |
| Transacciones críticas | 0 (con Stripe como source of truth) |

---

## Estrategia de Backups

### Frecuencia

| Tipo | Frecuencia | Retención |
|------|------------|-----------|
| BD completa | Diario (02:00 CET) | 7 días |
| Archivos (files/) | Semanal | 4 semanas |
| Config export | Con cada deploy | Ilimitado (Git) |

### Ubicaciones

| Backup | Ubicación |
|--------|-----------|
| Primario | `~/backups/` en IONOS |
| Secundario | GitHub (código + config) |
| Offsite | [Pendiente configurar] |

---

## Escalado de Incidentes

### Severidad

| Nivel | Descripción | Tiempo Respuesta |
|-------|-------------|------------------|
| **P1 - Crítico** | Sitio completamente caído | 15 minutos |
| **P2 - Alto** | Funcionalidad core afectada | 1 hora |
| **P3 - Medio** | Funcionalidad secundaria afectada | 4 horas |
| **P4 - Bajo** | Problema estético/menor | 24 horas |

### Procedimiento

1. **Detectar** - Monitoreo o reporte de usuario
2. **Clasificar** - Asignar severidad (P1-P4)
3. **Comunicar** - Notificar stakeholders
4. **Resolver** - Aplicar fix o workaround
5. **Documentar** - Post-mortem si P1/P2

---

## Mantenimiento Programado

### Ventanas de Mantenimiento

| Tipo | Horario |
|------|---------|
| Updates de seguridad | Lunes 02:00-04:00 CET |
| Deploys planificados | Martes/Jueves 10:00-12:00 CET |
| Mantenimiento mayor | Sábado 02:00-06:00 CET |

### Notificación

- **Updates menores**: Sin notificación (zero-downtime)
- **Updates mayores**: 48h de anticipación
- **Mantenimiento planificado**: 1 semana de anticipación

---

## Métricas a Monitorear

| Métrica | Umbral Alerta | Umbral Crítico |
|---------|---------------|----------------|
| CPU | > 70% (5 min) | > 90% (5 min) |
| RAM | > 80% | > 95% |
| Disco | > 80% | > 95% |
| Errores PHP/hora | > 10 | > 50 |
| Tiempo respuesta | > 3s | > 10s |

---

## Dependencias Externas

| Servicio | Criticidad | Fallback |
|----------|------------|----------|
| Stripe | Alta | Logs locales + retry |
| IONOS hosting | Crítica | [Definir DR site] |
| GitHub | Media | Repo local |
| DNS Cloudflare | Alta | DNS secundario IONOS |

---

## Firmas de Aprobación

| Rol | Nombre | Fecha |
|-----|--------|-------|
| Product Owner | | |
| DevOps Lead | | |
| CTO | | |
