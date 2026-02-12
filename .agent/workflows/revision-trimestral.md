---
description: Procedimiento de revisión trimestral del plan estratégico SaaS
---

# Revisión Trimestral del Plan Estratégico

Este workflow describe el procedimiento para revisar y ajustar el roadmap estratégico de Jaraba Impact Platform cada trimestre.

## Calendario de Revisiones

| Trimestre | Fecha de Revisión |
|-----------|-------------------|
| Q1 → Q2 | 2026-04-01 |
| Q2 → Q3 | 2026-07-01 |
| Q3 → Q4 | 2026-10-01 |
| Q4 → Q1 2027 | 2027-01-02 |

## Pasos del Procedimiento

### 1. Preparación (1 día antes)

```bash
# Recopilar métricas de KPIs actuales
# - Time-to-First-Value
# - NRR (Net Revenue Retention)
# - AI Response Success Rate
# - GEO Citations
# - Tenant Self-Service Actions
```

### 2. Análisis de Métricas

- Abrir el plan estratégico: `docs/planificacion/20260114-Plan_Estrategico_SaaS_Q1Q4_2026.md`
- Comparar KPIs actuales vs. targets de la sección 4
- Identificar desviaciones significativas (>15%)

### 3. Recopilar Feedback

- Revisar tickets de soporte del trimestre
- Consultar NPS de tenants
- Analizar feedback cualitativo de entrevistas

### 4. Evaluar Gaps

- Actualizar estado de gaps (🔴 → 🟡 → 🟢)
- Identificar nuevos gaps emergentes
- Repriorizar según impacto/urgencia

### 5. Ajustar Roadmap

// turbo
```bash
# Crear backup del plan antes de modificar
Copy-Item "docs/planificacion/20260114-Plan_Estrategico_SaaS_Q1Q4_2026.md" "docs/planificacion/20260114-Plan_Estrategico_SaaS_Q1Q4_2026_backup_$(Get-Date -Format 'yyyyMMdd').md"
```

- Mover items no completados al siguiente trimestre
- Añadir nuevas iniciativas según feedback
- Recalcular estimaciones de esfuerzo
- Ajustar targets de KPIs si es necesario

### 6. Documentar Decisiones

- Añadir entrada en sección 7 "Registro de Revisiones"
- Formato: `| YYYY-MM-DD | X.X.X | Cambios principales | Autor |`

### 7. Comunicar Cambios

- Actualizar `00_DIRECTRICES_PROYECTO.md` con nueva versión
- Actualizar `00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` si hay cambios arquitectónicos
- Notificar al equipo sobre los cambios principales

### 8. Verificar Actualización

// turbo
```bash
# Verificar que los archivos se actualizaron correctamente
git status docs/planificacion/
git diff docs/planificacion/20260114-Plan_Estrategico_SaaS_Q1Q4_2026.md | head -50
```

## Plantilla de Informe Rápido

Al finalizar la revisión, crear un resumen con:

```markdown
## Informe Revisión Q[X] 2026 - YYYY-MM-DD

### Estado General: [✅ En track | ⚠️ Desviaciones | 🔴 Riesgo]

### Top 3 Logros
1. ...
2. ...
3. ...

### Top 3 Desafíos
1. ...
2. ...
3. ...

### Decisiones Clave
1. ...
2. ...

### Próximo Revisión: [fecha]
```

## Notas

- El plan estratégico está en: `docs/planificacion/20260114-Plan_Estrategico_SaaS_Q1Q4_2026.md`
- Los KPIs de referencia están en la sección 4 del documento
- El procedimiento completo está en la sección 6 del documento
