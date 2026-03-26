# Plan de Implementacion: Casos de Exito Reales Andalucia +ei 1a Edicion

| Campo | Valor |
|-------|-------|
| Version | 1.0.0 |
| Fecha | 2026-03-26 |
| Autor | Claude (Desarrollo) + Pepe Jaraba (Direccion, datos) |
| Documento prerequisito | `docs/analisis/2026-03-26_Auditoria_Casos_Exito_Reales_Andalucia_Ei_1a_Edicion_Conversion_10_10_v1.md` |
| Modulos afectados | jaraba_success_cases, ecosistema_jaraba_core, 8 modulos verticales (legacy cleanup) |
| Score actual contenido | 2/10 (8 de 9 casos ficticios) |
| Score objetivo | 7/10 (3 casos reales + PED dogfooding + 5 ficticios marcados como pre-lanzamiento) |
| Directrices raiz | SUCCESS-CASES-001, MARKETING-TRUTH-001, CASE-STUDY-PATTERN-001, LANDING-CONVERSION-SCORE-001, SETUP-WIZARD-DAILY-001 |

---

## Indice de Navegacion (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Alcance y Restricciones](#2-alcance-y-restricciones)
3. [Inventario de Assets Reales Disponibles](#3-inventario-de-assets-reales-disponibles)
4. [Fase 1 — Enriquecer Seed Scripts con Datos Reales](#4-fase-1--enriquecer-seed-scripts-con-datos-reales)
5. [Fase 2 — DailyAction Admin RevisarCasosExitoAction](#5-fase-2--dailyaction-admin-revisarcasosexitoaccion)
6. [Fase 3 — Ampliar hook_requirements](#6-fase-3--ampliar-hook_requirements)
7. [Fase 4 — Eliminar Legacy Controllers](#7-fase-4--eliminar-legacy-controllers)
8. [Fase 5 — Validator TESTIMONIAL-VERIFICATION-001](#8-fase-5--validator-testimonial-verification-001)
9. [Fase 6 — Promover SSOT CHECK 1 de WARN a FAIL](#9-fase-6--promover-ssot-check-1-de-warn-a-fail)
10. [Tabla de Correspondencia de Especificaciones Tecnicas](#10-tabla-de-correspondencia-de-especificaciones-tecnicas)
11. [Tabla de Cumplimiento de Directrices](#11-tabla-de-cumplimiento-de-directrices)
12. [Criterios de Aceptacion](#12-criterios-de-aceptacion)
13. [Glosario de Siglas](#13-glosario-de-siglas)

---

## 1. Resumen Ejecutivo

Este plan implementa las recomendaciones de la auditoria de casos de exito reales de Andalucia +ei 1a Edicion. El objetivo es cerrar la brecha entre el template de conversion (15/15) y la calidad del contenido (actualmente 2/10) mediante:

1. **Enriquecimiento de los 4 casos reales** (Marcela Calabia, Angel Martinez, Luis Miguel Criado, PED S.L.) con datos verificables del programa real
2. **Creacion de una DailyAction administrativa** que alerte sobre casos incompletos
3. **Ampliacion de hook_requirements** para monitorizar calidad de datos en /admin/reports/status
4. **Eliminacion de 8 legacy controllers** con datos hardcodeados (ya sobreescritos por CaseStudyRouteSubscriber)
5. **Creacion del validator TESTIMONIAL-VERIFICATION-001** como salvaguarda automatizada
6. **Promocion del CHECK 1 de SSOT de WARN a FAIL** para forzar la limpieza de legacy controllers

**Restriccion critica**: Los 5 casos ficticios de verticales que NO tienen participantes reales de Andalucia +ei (jarabalex, comercioconecta, formacion, content_hub, y el ficticio de emprendimiento Carlos Etxebarria) NO se tocan — son placeholders de pre-lanzamiento del SaaS.

---

## 2. Alcance y Restricciones

### 2.1 En alcance

| Elemento | Accion |
|----------|--------|
| SuccessCase entity `andalucia_ei` (PED S.L.) | Enriquecer con datos reales del PIIL 2023 |
| SuccessCase entity `empleabilidad` | Reemplazar "Rosa Fernandez" ficticia con Luis Miguel Criado real |
| SuccessCase entity `emprendimiento` | Reemplazar "Carlos Etxebarria" ficticio con Marcela Calabia real |
| SuccessCase entity para nuevo caso | Crear Angel Martinez como caso de `serviciosconecta` o `agroconecta` |
| 8 legacy controllers | Eliminar ficheros PHP |
| hook_requirements | Ampliar con 4 checks de calidad de datos |
| DailyAction | Crear RevisarCasosExitoAction global para admin |
| Validator | Crear validate-testimonial-verification.php |
| validate-success-cases-ssot.php | Promover CHECK 1 de WARN a FAIL |

### 2.2 Fuera de alcance (NO tocar)

| Elemento | Razon |
|----------|-------|
| SuccessCase `jarabalex` (Elena Martinez ficticia) | No hay abogado en Andalucia +ei |
| SuccessCase `comercioconecta` (Boutique La Mariposa ficticia) | No hay comercio en Andalucia +ei |
| SuccessCase `formacion` (Maria Lopez ficticia) | No hay formadora en Andalucia +ei |
| SuccessCase `content_hub` (Bodega Montilla ficticia) | No hay creador contenido directo |
| SuccessCase `agroconecta` (Cooperativa Sierra Cazorla ficticia) | Mantener — caso emblemático de la plataforma |
| Template case-study-landing.html.twig | Ya es 15/15, no requiere cambios |
| CaseStudyLandingController.php | Ya unificado, no requiere cambios |
| Parciales _cs-*.html.twig | Ya optimizados, no requiere cambios |

### 2.3 Dependencias de Pepe (bloqueantes futuras)

Estos assets mejorarian el score pero requieren accion de Pepe:

| Asset | Estado | Impacto si se obtiene |
|-------|--------|----------------------|
| Foto profesional Marcela Calabia | Sin foto | +1 punto conversion |
| Foto profesional Angel Martinez | Fotos WhatsApp disponibles | +1 punto conversion |
| Foto profesional Luis Miguel Criado | **Fotos JPG disponibles** (2024) | +1 punto conversion |
| Testimonial textual de cada participante | Sin quote validado | +2 puntos conversion |
| Consentimiento RGPD firmado | Pendiente | Requisito legal |
| Fragmento video 30-60s | Videos largos disponibles | +2 puntos conversion |

---

## 3. Inventario de Assets Reales Disponibles

### 3.1 Luis Miguel Criado (empleabilidad)

**Datos verificables:**
- Profesion: Terapeuta de masajes autonomo
- Programa: Andalucia +ei, 1a Edicion (PIIL CV 2023-2024)
- Vertical: Empleabilidad (insercion laboral → autonomo)
- **Fotos reales**: 5 ficheros JPG en F:\DATOS (2024-06-04 y WhatsApp 2025-10)
- Caso de exito escrito: "El Poder de Empezar" (5.8 MB .docx)
- Narrativa: De desempleado a autonomo como terapeuta de masajes

### 3.2 Marcela Calabia (emprendimiento)

**Datos verificables:**
- Profesion: Coach de Comunicacion Estrategica y Resiliencia
- Programa: Andalucia +ei, 1a Edicion
- Vertical: Emprendimiento
- Video reunión: MP4 (47 MB, 2025-09-18)
- Caso escrito: 102 KB
- Modelo de negocio documentado: viable (ChatGPT analysis)
- Estrategia captacion clientes: servicios linguisticos (5.8 MB)
- Perfil Premium Preply configurado
- Libro: "Comunicar con Confianza" / "Sin culpa. Con coraje"
- Reunión con transcripcion (2025-10-01)

### 3.3 Angel Martinez (emprendimiento/turismo)

**Datos verificables:**
- Profesion: Cofundador Camino Viejo — Gastrobiking rural
- Empresa: Camino Viejo (web operativa: biciletas-caminoviejo)
- Programa: Andalucia +ei, 1a Edicion
- Sector: Turismo rural, ciclismo, gastronomia Sierra Morena
- Video reunion: MP4 (351 MB highlight + 2 GB completo, 2025-10-07)
- Caso de exito escrito: 5.8 MB
- Plan estrategico cicloturismo: 5.8 MB
- Modelo negocio documentado: valor añadido
- YouTube: Canal Gastrobiking Camino Viejo
- Fotos: 3 imagenes WhatsApp (paisajes/rutas)
- Transcripcion + AI summary disponibles

### 3.4 PED S.L. (andalucia_ei — dogfooding)

**Datos verificables (internos):**
- 1a edicion: 50 participantes, 8 provincias andaluzas
- 8 orientadores coordinados con Excel
- Tasa insercion: 46% (datos PIIL reales)
- 2a edicion construida sobre el propio SaaS
- 21 entities, 7 bridges cross-vertical, 4 roles

---

## 4. Fase 1 — Enriquecer Seed Scripts con Datos Reales

### 4.1 Descripcion

Actualizar los seed scripts para que los 4 casos de Andalucia +ei contengan datos reales y verificables, manteniendo intactos los 5 casos ficticios de otros verticales.

### 4.2 Archivos a modificar

| Archivo | Accion |
|---------|--------|
| `scripts/migration/seed-success-cases.php` | Reemplazar datos de `empleabilidad` (Rosa→Luis Miguel) y actualizar `andalucia_ei` (PED) |
| `scripts/migration/complete-success-cases-data.php` | Actualizar narrativas de `empleabilidad` y `andalucia_ei` con datos reales |

### 4.3 Datos reales para seed script

**empleabilidad** — Luis Miguel Criado (reemplaza Rosa Fernandez):
- name: Luis Miguel Criado
- slug: luis-miguel-criado
- headline: De desempleado a terapeuta autonomo: el poder de dar el primer paso
- protagonist_name: Luis Miguel Criado
- protagonist_role: Terapeuta de masajes autonomo
- sector: Salud y bienestar / Terapias manuales
- location: Andalucia
- program_name: Andalucia +ei
- program_funder: Junta de Andalucia (Consejeria de Empleo)
- program_year: 2023-2024
- vertical: empleabilidad

**andalucia_ei** — PED S.L. (enriquecer existente):
- Metricas verificables: 50 participantes, 8 provincias, 46% insercion, 8 orientadores
- Timeline real del PIIL

### 4.4 Patron de implementacion

El seed script ya usa el patron idempotente `slug + vertical` para detectar duplicados. Al cambiar el slug de `rosa-fernandez-malaga` a `luis-miguel-criado`, se creara una nueva entity sin afectar la existente. La entidad ficticia de Rosa Fernandez quedara en BD pero el seed no la recreara si se ejecuta desde cero.

---

## 5. Fase 2 — DailyAction Admin RevisarCasosExitoAction

### 5.1 Descripcion

Crear una DailyAction global (`__global__`) visible solo para usuarios con permiso `administer success cases` que verifica la calidad de las SuccessCase entities publicadas y muestra alertas en el dashboard.

### 5.2 Archivos a crear

| Archivo | Descripcion |
|---------|-------------|
| `web/modules/custom/jaraba_success_cases/src/DailyActions/RevisarCasosExitoAction.php` | Clase DailyAction |

### 5.3 Archivos a modificar

| Archivo | Cambio |
|---------|--------|
| `jaraba_success_cases.services.yml` | Registrar servicio con tag `ecosistema_jaraba_core.daily_action` |

### 5.4 Logica de getContext()

```
1. Contar verticales comerciales (9) sin SuccessCase publicada → badge = count
2. Contar SuccessCase publicadas sin hero_image → suma al badge
3. Visible solo si el usuario actual tiene permiso 'administer success cases'
4. badge_type = 'warning' si hay gaps, 'info' si todo OK
```

### 5.5 Patron seguido

Identico a `CrearArticuloGlobalAction` (global, simple, sin DI compleja). Dashboard `__global__`, icono `achievement/star` duotone, color `naranja-impulso`, ruta `/admin/content/success-case`.

---

## 6. Fase 3 — Ampliar hook_requirements

### 6.1 Descripcion

El hook_requirements existente ya verifica (1) entity types instalados y (2) cobertura vertical. Ampliar con checks de calidad de datos para las entities publicadas.

### 6.2 Checks nuevos

| # | Check | Severity | Condicion |
|---|-------|----------|-----------|
| 3 | hero_image | WARNING | SuccessCase publicadas sin hero_image → lista |
| 4 | quote_short | WARNING | SuccessCase publicadas sin quote_short → lista |
| 5 | metrics_json | INFO | SuccessCase publicadas con metrics_json vacio → lista |
| 6 | faq_json | INFO | SuccessCase publicadas con faq_json < 10 items → lista |

### 6.3 Archivo a modificar

`web/modules/custom/jaraba_success_cases/jaraba_success_cases.install`

---

## 7. Fase 4 — Eliminar Legacy Controllers

### 7.1 Descripcion

Los 8 legacy CaseStudy controllers contienen datos hardcodeados de casos ficticios. Sus rutas ya estan sobreescritas por `CaseStudyRouteSubscriber` que redirige al controller unificado `CaseStudyLandingController`. Eliminar los ficheros PHP y las rutas en routing.yml.

### 7.2 Verificacion previa

El `CaseStudyRouteSubscriber` mapea las 9 rutas legacy (incluyendo la 9a de jaraba_legal_intelligence):
- Intercepta las rutas en `onAlterRoutes()`
- Reapunta `_controller` al `CaseStudyLandingController::caseStudy`
- Inyecta `vertical_path` y `slug` como defaults
- Las rutas siguen existiendo en routing.yml pero el controller ya no importa

### 7.3 Ficheros a eliminar (8 controllers)

| # | Fichero | Modulo |
|---|---------|--------|
| 1 | `web/modules/custom/jaraba_agroconecta_core/src/Controller/AgroCaseStudyController.php` | jaraba_agroconecta_core |
| 2 | `web/modules/custom/jaraba_andalucia_ei/src/Controller/AndaluciaEiCaseStudyController.php` | jaraba_andalucia_ei |
| 3 | `web/modules/custom/jaraba_business_tools/src/Controller/EmprendimientoCaseStudyController.php` | jaraba_business_tools |
| 4 | `web/modules/custom/jaraba_candidate/src/Controller/EmpleabilidadCaseStudyController.php` | jaraba_candidate |
| 5 | `web/modules/custom/jaraba_comercio_conecta/src/Controller/ComercioCaseStudyController.php` | jaraba_comercio_conecta |
| 6 | `web/modules/custom/jaraba_content_hub/src/Controller/ContentHubCaseStudyController.php` | jaraba_content_hub |
| 7 | `web/modules/custom/jaraba_lms/src/Controller/FormacionCaseStudyController.php` | jaraba_lms |
| 8 | `web/modules/custom/jaraba_servicios_conecta/src/Controller/ServiciosCaseStudyController.php` | jaraba_servicios_conecta |

### 7.4 Rutas en routing.yml

Las rutas (`jaraba_*.case_study.*`) en cada modulo se mantienen porque:
1. El `CaseStudyRouteSubscriber` las necesita como "host" para interceptar
2. Eliminar la ruta sin eliminar la referencia en el subscriber causaria error
3. El subscriber busca por route name — si no existe, simplemente la ignora

**Conclusion**: Eliminar solo los controllers PHP, mantener las rutas. El subscriber ya apunta al controller correcto.

---

## 8. Fase 5 — Validator TESTIMONIAL-VERIFICATION-001

### 8.1 Descripcion

Nuevo script de validacion que verifica que las SuccessCase entities publicadas tienen datos de calidad minima para ser consideradas "testimonios verificables".

### 8.2 Archivo a crear

`scripts/validation/validate-testimonial-verification.php`

### 8.3 Checks

| # | Check | Tipo | Descripcion |
|---|-------|------|-------------|
| 1 | Foto de perfil | run_check | Toda SuccessCase publicada DEBE tener hero_image o una imagen en el directorio de assets |
| 2 | Testimonial | run_check | Toda SuccessCase publicada DEBE tener quote_short no vacio |
| 3 | Metricas | warn_check | SuccessCase publicadas DEBERIAN tener metrics_json con al menos 2 metricas |
| 4 | Programa real | warn_check | SuccessCase con program_name DEBERIA tener program_funder y program_year |
| 5 | FAQ minimo | warn_check | faq_json DEBERIA tener >= 10 items para Schema.org FAQPage |

### 8.4 Integracion

- Registrar en `validate-all.sh` como `warn_check` (no bloquea CI, alerta)
- Ejecutable: `php scripts/validation/validate-testimonial-verification.php`

---

## 9. Fase 6 — Promover SSOT CHECK 1 de WARN a FAIL

### 9.1 Descripcion

El validator `validate-success-cases-ssot.php` actualmente emite WARN cuando detecta legacy controllers con datos hardcodeados. Tras eliminar los 8 controllers (Fase 4), el check deberia pasar a FAIL para prevenir regresiones futuras.

### 9.2 Archivo a modificar

`scripts/validation/validate-success-cases-ssot.php`

### 9.3 Cambio

Cambiar el tipo de CHECK 1 de `warn_check` a `run_check`. Si alguien añade un nuevo controller con datos hardcodeados, el validator fallara en CI.

---

## 10. Tabla de Correspondencia de Especificaciones Tecnicas

| ID | Especificacion | Fase | Archivos | Directriz |
|----|---------------|------|----------|-----------|
| REAL-001 | Seed script con datos reales Luis Miguel Criado | F1 | `seed-success-cases.php` | SUCCESS-CASES-001, MARKETING-TRUTH-001 |
| REAL-002 | Seed script enriquecido PED S.L. con metricas PIIL | F1 | `seed-success-cases.php` | MARKETING-TRUTH-001 |
| REAL-003 | Complete script con narrativas verificables | F1 | `complete-success-cases-data.php` | SUCCESS-CASES-001 |
| REAL-004 | DailyAction global admin para revision casos | F2 | `RevisarCasosExitoAction.php`, `services.yml` | SETUP-WIZARD-DAILY-001 |
| REAL-005 | Tag ecosistema_jaraba_core.daily_action | F2 | `services.yml` | SETUP-WIZARD-DAILY-001 |
| REAL-006 | hook_requirements check hero_image | F3 | `jaraba_success_cases.install` | STATUS-REPORT-PROACTIVE-001 |
| REAL-007 | hook_requirements check quote_short | F3 | `jaraba_success_cases.install` | STATUS-REPORT-PROACTIVE-001 |
| REAL-008 | hook_requirements check metrics_json | F3 | `jaraba_success_cases.install` | STATUS-REPORT-PROACTIVE-001 |
| REAL-009 | hook_requirements check faq_json >= 10 | F3 | `jaraba_success_cases.install` | STATUS-REPORT-PROACTIVE-001 |
| REAL-010 | Eliminar AgroCaseStudyController | F4 | `AgroCaseStudyController.php` | LEGACY-CONTROLLER-CLEANUP-001 |
| REAL-011 | Eliminar AndaluciaEiCaseStudyController | F4 | `AndaluciaEiCaseStudyController.php` | LEGACY-CONTROLLER-CLEANUP-001 |
| REAL-012 | Eliminar EmprendimientoCaseStudyController | F4 | `EmprendimientoCaseStudyController.php` | LEGACY-CONTROLLER-CLEANUP-001 |
| REAL-013 | Eliminar EmpleabilidadCaseStudyController | F4 | `EmpleabilidadCaseStudyController.php` | LEGACY-CONTROLLER-CLEANUP-001 |
| REAL-014 | Eliminar ComercioCaseStudyController | F4 | `ComercioCaseStudyController.php` | LEGACY-CONTROLLER-CLEANUP-001 |
| REAL-015 | Eliminar ContentHubCaseStudyController | F4 | `ContentHubCaseStudyController.php` | LEGACY-CONTROLLER-CLEANUP-001 |
| REAL-016 | Eliminar FormacionCaseStudyController | F4 | `FormacionCaseStudyController.php` | LEGACY-CONTROLLER-CLEANUP-001 |
| REAL-017 | Eliminar ServiciosCaseStudyController | F4 | `ServiciosCaseStudyController.php` | LEGACY-CONTROLLER-CLEANUP-001 |
| REAL-018 | Validator testimonial-verification | F5 | `validate-testimonial-verification.php` | TESTIMONIAL-VERIFICATION-001 |
| REAL-019 | Promover CHECK 1 SSOT a FAIL | F6 | `validate-success-cases-ssot.php` | SUCCESS-CASES-001 |

---

## 11. Tabla de Cumplimiento de Directrices

| Directriz | Estado previo | Estado tras plan | Fase |
|-----------|---------------|-----------------|------|
| SUCCESS-CASES-001 | PASS (con WARN legacy) | PASS (sin WARN, FAIL para nuevos) | F4, F6 |
| MARKETING-TRUTH-001 | VIOLADA (8 ficticios como reales) | PARCIAL (4 reales + 5 ficticios pre-lanzamiento) | F1 |
| CASE-STUDY-PATTERN-001 | PASS 15/15 | PASS 15/15 (sin cambios) | — |
| LANDING-CONVERSION-SCORE-001 | PASS 15/15 | PASS 15/15 (sin cambios) | — |
| SETUP-WIZARD-DAILY-001 | NO APLICA | Cumplida — DailyAction admin | F2 |
| STATUS-REPORT-PROACTIVE-001 | PARCIAL | Cumplida — 6 checks en hook_requirements | F3 |
| TESTIMONIAL-VERIFICATION-001 | NO EXISTIA | Cumplida — validator nuevo | F5 |
| LEGACY-CONTROLLER-CLEANUP-001 | NO EXISTIA | Cumplida — 8 controllers eliminados | F4 |
| TENANT-ISOLATION-ACCESS-001 | PASS | PASS (sin cambios) | — |
| PREMIUM-FORMS-PATTERN-001 | PASS | PASS (sin cambios) | — |
| ICON-CONVENTION-001 | PASS | PASS (sin cambios) | — |
| CSS-VAR-ALL-COLORS-001 | PASS | PASS (sin cambios) | — |
| ZERO-REGION-001 | PASS | PASS (sin cambios) | — |
| TWIG-INCLUDE-ONLY-001 | PASS | PASS (sin cambios) | — |

---

## 12. Criterios de Aceptacion

- [ ] `php scripts/validation/validate-success-cases-ssot.php` → PASS (0 WARN, 0 FAIL)
- [ ] `php scripts/validation/validate-case-study-conversion-score.php` → PASS (15/15)
- [ ] `php scripts/validation/validate-case-study-completeness.php` → PASS
- [ ] `php scripts/validation/validate-testimonial-verification.php` → ejecutable sin errores
- [ ] Los 8 legacy controllers eliminados (0 ficheros *CaseStudyController.php con datos hardcodeados)
- [ ] DailyAction RevisarCasosExitoAction registrada y funcional
- [ ] hook_requirements muestra 6 checks en /admin/reports/status
- [ ] Seed script contiene datos de Luis Miguel Criado (real) en vez de Rosa Fernandez (ficticia)
- [ ] Seed script contiene metricas reales del PIIL para PED S.L.
- [ ] CaseStudyRouteSubscriber sigue redirigiendo correctamente las 9 rutas legacy

---

## 13. Glosario de Siglas

| Sigla | Significado |
|-------|------------|
| CTA | Call To Action — boton o enlace que invita a realizar una accion de conversion |
| DI | Dependency Injection — patron de inyeccion de dependencias del contenedor de servicios |
| FAQ | Frequently Asked Questions — preguntas frecuentes |
| FSE+ | Fondo Social Europeo Plus — fondo de la UE para empleo e inclusion |
| PIIL | Programa Integrado de Insercion Laboral |
| RGPD | Reglamento General de Proteccion de Datos |
| SaaS | Software as a Service — software como servicio |
| SAE | Servicio Andaluz de Empleo |
| SCSS | Sassy CSS — preprocesador CSS |
| SEO | Search Engine Optimization |
| SSOT | Single Source Of Truth — fuente unica de verdad |

---

*Documento generado como parte del sistema de documentacion de Jaraba Impact Platform.*
*Cumple DOC-GUARD-001: documento nuevo, no sobreescribe master docs.*
*Cumple DOC-GLOSSARY-001: glosario de siglas incluido al final.*
