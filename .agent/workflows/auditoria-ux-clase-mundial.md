---
description: Workflow para auditorías UX completas del SaaS
---

# Auditoría UX Clase Mundial

Este workflow documenta el proceso para realizar auditorías UX exhaustivas del SaaS.

## Prerrequisitos

- Acceso admin al sitio (usuario: admin)
- Navegador con DevTools disponible
- Lando corriendo (`lando start`)

## Pasos

### 1. Auditar Homepage Pública

```bash
# Verificar homepage sin login
lando drush cr
```

1. Navegar a la homepage SIN autenticar
2. Evaluar:
   - Primera impresión (5 segundos)
   - Propuesta de valor visible
   - CTAs claros
   - Meta tags SEO (title, description)

### 2. Auditar Dashboards por Avatar

Para cada avatar (job_seeker, recruiter, entrepreneur, producer, etc.):

1. Navegar a `/dashboard/{avatar}`
2. Verificar:
   - ¿Ruta existe o da 404?
   - ¿Contenido correcto para el avatar?
   - ¿Journey Engine activo?
   - ¿Copilot contextual?

### 3. Documentar Hallazgos

Crear documento en:
```
docs/arquitectura/YYYY-MM-DD_HHMM_auditoria-ux-frontend-saas.md
```

Con secciones:
- Resumen Ejecutivo
- Puntuaciones por Área
- Hallazgos Detallados
- Plan de Remediación

### 4. Actualizar Documentación

1. Actualizar `docs/00_INDICE_GENERAL.md` con referencia
2. Crear aprendizaje en `docs/tecnicos/aprendizajes/`
3. Actualizar versión del índice

### 5. Crear Plan de Implementación

Si se detectan gaps críticos:
- Fase 1: Quick Wins (40h)
- Fase 2: Dashboards por Avatar (80h)
- Fase 3: Estándares Clase Mundial (60h)

## Métricas de Éxito

| Métrica | Mínimo Aceptable |
|---------|------------------|
| Lighthouse Performance | > 90 |
| Time to First Value | < 3 clicks |
| SEO Score | 100% compliant |
| Avatar Dashboard Coverage | 19/19 |

## Patrón: Progressive Profiling

```
Pre-Login:  Landing por Intención (no por Avatar)
Post-Login: Dashboard personalizado por Avatar + Journey Engine
```

## Referencias

- [Auditoría UX 2026-01-24](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/arquitectura/2026-01-24_1936_auditoria-ux-frontend-saas.md)
- [Aprendizaje Progressive Profiling](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/aprendizajes/2026-01-24_auditoria_ux_clase_mundial.md)
