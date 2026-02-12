# Mapa de URLs Frontend - Verificación Diseño Premium

> **Fecha**: 2026-01-26
> **Autor**: Gemini Antigravity
> **Versión**: 1.0
> **Estado**: Verificación completada

## Resumen

Documento de mapeo de todas las URLs del frontend del SaaS para verificar la extensión del diseño premium implementado en la homepage a todas las páginas.

---

## Resumen de Templates Activos

| Template | Tipo | Diseño Premium |
|----------|------|----------------|
| `page--front.html.twig` | Homepage | ✅ 100% |
| `page--vertical-landing.html.twig` | Landings verticales | ✅ Verificado |
| `page--dashboard.html.twig` | Dashboards usuarios | ✅ Actualizado |
| `page.html.twig` | Páginas estándar | ✅ Actualizado |

---

## Verificación Realizada (2026-01-26)

### URLs Públicas

| URL | Header Glassmórfico | Footer Premium | Estado |
|-----|---------------------|----------------|--------|
| `/` | ✅ `blur(20px)` | ✅ | Verificado |
| `/jobs` | ✅ `blur(20px)` | ✅ | Verificado |
| `/empleo` | ✅ `blur(20px)` | ✅ | Verificado |
| `/talento` | ✅ `blur(20px)` | ✅ | Verificado |
| `/emprender` | ✅ `blur(20px)` | ✅ | Verificado |
| `/comercio` | ✅ `blur(20px)` | ✅ | Verificado |
| `/instituciones` | ✅ `blur(20px)` | ✅ | Verificado |
| `/demo` | ✅ `blur(20px)` | ✅ | Verificado |
| `/marketplace` | ✅ `blur(20px)` | ✅ | Verificado |
| `/paths` | ✅ `blur(20px)` | ✅ | Verificado |

### URLs Autenticadas (Dashboards)

| URL | Header Glassmórfico | Footer Premium | Estado |
|-----|---------------------|----------------|--------|
| `/jobseeker` | ✅ `blur(20px)` | ✅ | Verificado |
| `/employer` | ✅ `blur(20px)` | ✅ | Verificado |
| `/my-profile` | ✅ `blur(20px)` | ✅ | Verificado |
| `/my-company` | ✅ `blur(20px)` | ✅ | Verificado |
| `/entrepreneur/dashboard` | ✅ `blur(20px)` | ✅ | Verificado |
| `/my-applications` | ✅ `blur(20px)` | ✅ | Verificado |
| `/my-dashboard` | ✅ `blur(20px)` | ✅ | Verificado |

### Resumen

- **Total URLs verificadas**: 17
- **Con diseño premium**: 17 (100%)
- **Con errores**: 0

---

## URLs por Vertical

### 🏠 Landing Pages (Públicas)
| URL | Template | Estado |
|-----|----------|--------|
| `/` | page--front | ✅ Verificado |
| `/empleo` | page--vertical-landing | ✅ Verificado |
| `/talento` | page--vertical-landing | Pendiente |
| `/emprender` | page--vertical-landing | ✅ Verificado |
| `/comercio` | page--vertical-landing | Pendiente |
| `/instituciones` | page--vertical-landing | Pendiente |
| `/demo` | page.html | ✅ Verificado |
| `/marketplace` | page.html | Pendiente |

---

### 💼 Vertical Empleabilidad

#### Candidatos (Jobseeker)
| URL | Descripción |
|-----|-------------|
| `/jobseeker` | Dashboard del candidato |
| `/jobseeker/recommendations` | Recomendaciones de empleo |
| `/jobseeker/stats` | Estadísticas del candidato |
| `/my-profile` | Mi perfil |
| `/my-profile/edit` | Editar perfil |
| `/my-profile/experience` | Experiencia laboral |
| `/my-profile/education` | Educación |
| `/my-profile/skills` | Habilidades |
| `/my-profile/cv` | CV Builder |
| `/my-profile/self-discovery` | Autodescubrimiento |
| `/my-profile/self-discovery/life-wheel` | Rueda de la Vida |
| `/my-profile/self-discovery/timeline` | Línea de Vida |
| `/my-profile/self-discovery/interests` | RIASEC |
| `/my-applications` | Mis candidaturas |
| `/my-jobs/saved` | Ofertas guardadas |
| `/my-jobs/alerts` | Alertas de empleo |

#### Empleadores (Employer)
| URL | Descripción |
|-----|-------------|
| `/employer` | Panel del empleador |
| `/employer/jobs` | Mis ofertas |
| `/employer/applications` | Candidaturas recibidas |
| `/my-company` | Mi empresa |
| `/my-company/analytics` | Estadísticas |
| `/my-company/jobs` | Mis ofertas |

#### Job Board (Público)
| URL | Descripción |
|-----|-------------|
| `/jobs` | Búsqueda de empleo ✅ |
| `/jobs/{id}` | Detalle de oferta |
| `/jobs/{id}/apply` | Aplicar a oferta |

---

### 🚀 Vertical Emprendimiento

| URL | Descripción |
|-----|-------------|
| `/entrepreneur/dashboard` | Panel del emprendedor |
| `/paths` | Catálogo de itinerarios |
| `/path/{id}` | Detalle del itinerario |
| `/my-progress` | Mi progreso |

---

### 🏢 Core / Multi-vertical

| URL | Descripción |
|-----|-------------|
| `/my-dashboard` | Dashboard self-service |
| `/my-settings` | Configuración |
| `/onboarding/seleccionar-plan` | Selección de plan |
| `/onboarding/bienvenida` | Página de bienvenida |
| `/user/login` | Login ✅ |
| `/user/register` | Registro |

---

## Notas Técnicas

### Templates que Heredan Diseño Premium

1. **`page--dashboard.html.twig`** → Usado por rutas con `_admin_route: FALSE` y patrón `/employer`, `/jobseeker`, `/entrepreneur/dashboard`, `/my-company`

2. **`page--vertical-landing.html.twig`** → Usado por landings de verticales (empleo, talento, emprender, comercio, instituciones)

3. **`page.html.twig`** → Fallback para el resto de páginas (incluye sidebars con efecto glass)

### Archivos SCSS Implementados

- `_page-premium.scss` - Estilos globales para wrappers y animaciones
- `_glass-utilities.scss` - Utilidades glassmórficas reutilizables

### Detección Automática de Templates

La detección de templates se realiza en `ecosistema_jaraba_theme.theme`:

```php
function ecosistema_jaraba_theme_theme_suggestions_page_alter(&$suggestions, $variables) {
  // Añadir sugerencias para rutas específicas
}
```

---

## Referencias

- [Arquitectura Frontend Extensible](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/implementacion/2026-01-25_arquitectura_frontend_extensible.md)
- [Auditoría UX Frontend](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/arquitectura/2026-01-24_1936_auditoria-ux-frontend-saas.md)
