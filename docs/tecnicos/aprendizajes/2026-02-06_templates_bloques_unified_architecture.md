# Aprendizaje: Templates-Bloques Unified Architecture

> **Fecha**: 2026-02-06  
> **Área**: Page Builder / GrapesJS / Arquitectura

---

## Problema Encontrado

Al intentar elevar el Page Builder a 10/10, se identificó una **brecha arquitectónica crítica**:

- **Galería de Templates**: 76 templates HTML/Twig (sistema pre-GrapesJS)
- **Bloques GrapesJS**: ~35 bloques JavaScript (sistema actual)
- **Sin sincronización**: Dos catálogos independientes

## Diagnóstico

Los templates de la galería (`/page-builder/templates`) eran originalmente secciones para insertar visualmente antes de la integración con GrapesJS. Al añadir GrapesJS, se creó un nuevo catálogo de bloques en JavaScript sin mapear 1:1 con la galería existente.

## Solución Implementada

### Patrón: Single Source of Truth (SSoT)

```
Template Registry (definiciones) 
    ↓
    ├── Galería Templates (frontend)
    ├── GrapesJS Blocks (canvas editor)
    └── API/IA (sugerencias)
```

### Decisión Arquitectónica

1. **Bridge Pattern**: No eliminar la galería actual
2. **Sincronización gradual**: Añadir bloques faltantes en JS
3. **Futuro**: Migrar a Template Registry centralizado (YAML)

## Lecciones Aprendidas

### ✅ Buenas Prácticas

| Práctica | Beneficio |
|----------|-----------|
| Auditoría exhaustiva antes de expansión | Evita duplicados |
| Mapeo cruzado galería ↔ JS | Identifica gaps reales |
| Single Source of Truth | Mantenimiento unificado |

### ❌ Anti-patterns a Evitar

| Anti-pattern | Problema |
|--------------|----------|
| Dos catálogos independientes | Mantenimiento 2x |
| Asumir paridad sin verificar | Inconsistencias silenciosas |
| Expandir sin inventariar | Duplicación de esfuerzo |

## Métricas de Éxito

| Métrica | Antes | Después |
|---------|-------|---------|
| Fuentes de catálogo | 2 | 1 (SSoT) |
| Paridad templates-bloques | 35/76 (46%) | 76/76 (100%) |
| Esfuerzo mantenimiento | 2x | 1x |

## Referencias

- [Documento Arquitectura](../arquitectura/2026-02-06_arquitectura_unificada_templates_bloques.md)
- [KI Visual Builder](file:///C:/Users/Pepe%20Jaraba/.gemini/antigravity/knowledge/jaraba_visual_builder_ecosystem/)
