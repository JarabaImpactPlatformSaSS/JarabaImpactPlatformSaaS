# Auditoría y Plan: pepejaraba.com - Meta-sitio de Marca Personal

> **Fecha**: 2 de Febrero de 2026  
> **Código**: 20260202_PepeJaraba_Audit_Plan_v1  
> **Estado**: Plan aprobado para ejecución

---

## 1. Resumen Ejecutivo

Este documento consolida la auditoría del estado actual y el plan de implementación para el meta-sitio pepejaraba.com usando el Site Builder y Page Builder del SaaS Jaraba.

### Hallazgos Clave

| Aspecto | Estado | Detalle |
|---------|--------|---------|
| WordPress (borrador) | ✅ Auditado | 14 páginas, 19 activos de medios |
| SaaS Page Builder | ✅ Listo | 70 templates, 17 categorías |
| Arquitectura | ✅ Correcta | PageContent son entidades custom (no nodos) |
| Bug identificado | ⚠️ Menor | HTML escapado en subtítulo héroe |

---

## 2. Auditoría WordPress

### 2.1 Estructura de Páginas

| Página | Función |
|--------|---------|
| Transformación Digital Empresas | **Home** - Hero + Social Proof + Avatares |
| Blog del Impulsador Digital | Blog "Sin Humo" |
| Casos de Éxito (matriz) | Con 3 subpáginas: Empleabilidad, Emprendimiento, Pymes |
| Método | Metodología Jaraba™ |
| Manifiesto | Principios y valores |
| Contacto | Formulario + CTA |
| Legales | Aviso Legal, Cookies, Privacidad |

### 2.2 Activos de Medios

- Retratos profesionales de Pepe Jaraba
- Logotipo e isotipo Jaraba  
- Escudos institucionales (Junta Andalucía, Ayuntamientos)
- Códigos QR y materiales de captación

---

## 3. Auditoría SaaS

### 3.1 Capacidades del Page Builder

| Métrica | Valor |
|---------|-------|
| Templates disponibles | 70 |
| Categorías de bloques | 17 |
| Sistema Multi-block | Operativo |
| Fidelidad visual | 100% (67/67 templates) |

### 3.2 Arquitectura de Entidades

**Pregunta del usuario**: ¿Por qué las páginas del constructor no aparecen en `/admin/content`?

**Respuesta**: Es **arquitectónicamente correcto**. Las páginas del Page Builder son entidades de contenido personalizadas (`PageContent`), NO nodos de Drupal. Cada tipo tiene su propia ruta de administración:

| Entidad | Ruta de Gestión |
|---------|-----------------|
| PageContent | `/admin/content/pages` |
| HomepageContent | `/admin/content/homepage` |
| FeatureCard | `/admin/content/feature-cards` |
| PageExperiment | `/admin/content/experiments` |

Esta separación es una práctica estándar en arquitecturas SaaS multi-tenant para mantener aislamiento y control granular.

---

## 4. Plan de Implementación

### 4.1 Estructura del Sitio

```
pepejaraba.com/
├── / (Homepage)
│   ├── Hero + Foto + CTAs
│   ├── Social Proof (logos institucionales)
│   ├── Avatares (3 perfiles target)
│   ├── Ecosistema Preview
│   ├── Testimonios
│   └── Lead Magnet (Kit de Impulso)
├── /sobre (Manifiesto)
├── /servicios (Value Ladder 5 niveles)
├── /ecosistema (5 verticales)
├── /blog
└── /contacto
```

### 4.2 Mapeo de Templates

| Sección Homepage | Template Recomendado |
|------------------|---------------------|
| Hero | `parallax_hero` o `hero_split_text_image` |
| Social Proof | `logo_cloud` |
| Avatares | `bento_grid` o `feature_cards_three` |
| Ecosistema | `icon_grid` |
| Testimonios | `testimonial_carousel` |
| Lead Magnet | `newsletter_cta` |

### 4.3 Roadmap de Ejecución

| Paso | Descripción | Duración |
|------|-------------|----------|
| 1 | Corregir bug HTML escapado | 10 min |
| 2 | Crear Homepage (9 secciones) | 30 min |
| 3 | Crear páginas secundarias | 40 min |
| 4 | Configurar navegación | 10 min |
| 5 | Verificación final | 15 min |

**Total estimado**: ~1.5 horas

---

## 5. Notas Técnicas

### 5.1 Bug a Corregir

El campo `hero_subtitle` en `HomepageContent` muestra etiquetas HTML escapadas. Se requiere revisar la plantilla Twig o cambiar el tipo de campo.

### 5.2 Producción

Para desplegar en producción con el dominio pepejaraba.com real, se necesitará:
1. Crear Group Type `tenant_personal_brand`
2. Configurar DNS apuntando al servidor
3. Generar certificado SSL con Let's Encrypt
4. Configurar virtual host Nginx

Documentación técnica completa en: [126_Personal_Brand_Tenant_Config_v1](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260118a-126_Personal_Brand_Tenant_Config_v1_Claude.md)

---

## 6. Referencias

- [123_PepeJaraba_Personal_Brand_Plan](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260118a-123_PepeJaraba_Personal_Brand_Plan_v1_Claude.md)
- [124_PepeJaraba_Content_Ready](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260118a-124_PepeJaraba_Content_Ready_v1_Claude.md)
- [126_Personal_Brand_Tenant_Config](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260118a-126_Personal_Brand_Tenant_Config_v1_Claude.md)

---

*Documento generado por el Sistema de Auditoría Automatizada del SaaS Jaraba*
