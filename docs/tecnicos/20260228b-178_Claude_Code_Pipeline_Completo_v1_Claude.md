
ECOSISTEMA JARABA

Claude Code Development Pipeline

Especificación Técnica de Implementación



Documento 178 del Ecosistema

Campo	Valor
Versión	1.0
Fecha	Febrero 2026
Código	178_Claude_Code_Pipeline_Completo_v1
Estado	Ready for Implementation
Clasificación	World-Class SaaS Development Pipeline
Dependencias	01-07 Core, 112 Marketplace, 129 AI Skills, 132 CI/CD, 115 Security
Horas Estimadas	180-240h implementación completa
Equipo	EDI Google Antigravity

JARABA IMPACT PLATFORM
Filosofía Sin Humo — Máximo impacto, mínima complejidad innecesaria
 
Índice
Índice	1
1. Resumen Ejecutivo	1
1.1 Problema que Resuelve	1
1.2 Qué Contiene Este Documento	1
1.3 Impacto Esperado	1
2. CLAUDE.md — Memoria Persistente del Proyecto	1
2.1 Propósito	1
2.2 Contenido Completo del CLAUDE.md	1
2.3 Principios de Diseño del CLAUDE.md	1
2.4 Estructura de Directorios del Pipeline Completo	1
3. Skills Personalizados — Expertise Encapsulada	1
3.1 Catálogo de Skills	1
3.2 Drupal Theming & Design System	1
3.3 ECA Workflows & Automation	1
3.4 Stripe Connect & Billing	1
3.5 Multi-Tenant Architecture	1
3.6 SEPE Teleformación Compliance	1
3.7 AI Strict Grounding & RAG	1
3.8 Vertical Blueprint (Base+Extension)	1
4. Subagentes Especializados	1
4.1 Subagente: Reviewer (Writer/Reviewer Pattern)	1
4.2 Subagente: Tester	1
4.3 Subagente: Security Auditor	1
5. Hooks — Quality Gates Automáticos	1
5.1 Configuración en settings.json	1
5.2 Hook: Pre-Commit	1
5.3 Hook: Pre-Push	1
5.4 Hook: Pre-Tool-Use	1
6. Configuraciones MCP Server	1
6.1 Drupal MCP Server (CRÍTICO)	1
6.2 Google Stitch MCP (Prototipado)	1
6.3 Stripe MCP (Pagos)	1
6.4 GitHub MCP (CI/CD)	1
6.5 Sentry MCP (Observabilidad)	1
6.6 Semgrep MCP (Seguridad)	1
7. Slash Commands — Workflows Repetibles	1
7.1 Catálogo de Commands	1
7.2 Ejemplo Detallado: /fix-issue	1
7.3 Ejemplo Detallado: /create-vertical	1
8. Gobernanza de Seguridad MCP	1
8.1 OAuth 2.1 para Todo MCP Server en Producción	1
8.2 Principio de Mínimo Privilegio	1
8.3 Audit Logging para Compliance	1
8.4 Detección de PII y Datos Sensibles	1
8.5 Secrets Management	1
9. Plugin jaraba-dev — Paquete Distribuible	1
9.1 Instalación	1
9.2 Contenido del Plugin	1
9.3 Versionado del Plugin	1
10. Pipeline Orquestado End-to-End	1
10.1 Flujo Completo de una Tarea	1
10.2 Matriz de Activación por Tipo de Tarea	1
11. Roadmap de Implementación	1
11.1 Fases y Cronograma	1
11.2 Estimación de Esfuerzo Detallada	1
11.3 ROI Proyectado	1
12. Conclusión	1
12.1 Los 5 Mandamientos del Pipeline	1

 
1. Resumen Ejecutivo

Este documento define la especificación técnica completa para transformar el pipeline de desarrollo del Ecosistema Jaraba mediante Claude Code como agente de desarrollo autónomo de clase mundial. No es un documento conceptual: contiene todo el código, configuraciones, estructuras de archivos y lógica necesaria para que Claude Code pueda implementar el sistema de forma completamente autónoma.

1.1 Problema que Resuelve
El ecosistema Jaraba cuenta con 177 especificaciones técnicas que suman más de 500.000 palabras de documentación. Un desarrollador humano necesitaría semanas solo para absorber el contexto antes de escribir una línea de código. Claude Code, correctamente configurado, puede acceder a toda esta expertise de forma instantánea y contextual, ejecutando tareas con la maestría de un equipo senior completo.

1.2 Qué Contiene Este Documento

Componente	Qué Es	Para Qué Sirve
CLAUDE.md	Archivo de memoria persistente del proyecto	Define reglas, convenciones, arquitectura que Claude siempre conoce
7 Skills Personalizados	Carpetas con instrucciones especializadas	Se activan automáticamente según contexto de la tarea
3 Subagentes	Instancias Claude con roles aislados	Writer/Reviewer, Tester, Security Auditor
5 Hooks	Scripts determinísticos en eventos	Quality gates automáticos: linting, tests, WCAG, secrets
6 Configuraciones MCP	Conexiones a herramientas externas	Drupal, Stitch, Stripe, GitHub, Sentry, Semgrep
8 Slash Commands	Workflows invocados manualmente	Acciones frecuentes: deploy, audit, create-vertical
Plugin jaraba-dev	Paquete distribuible completo	Estandarizar configuración de todo el equipo EDI
Gobernanza de Seguridad	Framework OAuth 2.1 + audit logging	Compliance SOC2, ISO 27001, ENS, EU AI Act

1.3 Impacto Esperado
Métrica	Antes	Después	Mejora
Tiempo onboarding desarrollador	2-3 semanas	1-2 días	10x más rápido
Contexto disponible por tarea	Lo que recuerde el dev	100% del ecosistema	Cobertura total
Detección bugs pre-commit	Manual, inconsistente	Automatizada, 100%	Zero defectos al PR
Adherencia estándares Drupal	~70% (variable)	~98% (hooks + reviewer)	Calidad consistente
Tiempo prototipado UI	2-3 días	2-3 horas	10x más rápido
Cobertura WCAG 2.1 AA	Manual spot-checks	Automática en cada commit	100% cobertura
Seguridad código nuevo	Review manual esporádico	Semgrep + reviewer en cada PR	Continua
Documentación actualizada	Se desactualiza rápido	Se genera con el código	Siempre al día

✅ PRINCIPIO SIN HUMO: Este documento prioriza implementación inmediata sobre perfección teórica. Cada componente tiene un coste cero o mínimo y genera valor desde el primer día.
 
2. CLAUDE.md — Memoria Persistente del Proyecto

2.1 Propósito
El archivo CLAUDE.md es la memoria persistente que Claude Code lee automáticamente al inicio de cada sesión. Define el contexto completo del proyecto: arquitectura, convenciones, patrones obligatorios y reglas que el agente debe conocer SIEMPRE, independientemente de la tarea específica que ejecute. Es el equivalente a la experiencia acumulada de un desarrollador senior que lleva años en el proyecto.

ℹ️ UBICACIÓN: Se coloca en la raíz del repositorio. Claude Code lo detecta automáticamente. NO requiere invocación manual.

2.2 Contenido Completo del CLAUDE.md
A continuación se presenta el contenido exacto, listo para copiar al repositorio:

# JARABA IMPACT PLATFORM — CLAUDE.md
# Última actualización: Febrero 2026
# Ecosistema: 5 verticales, 177+ especificaciones, Drupal 11
 
## IDENTIDAD DEL PROYECTO
- Nombre: Jaraba Impact Platform (Ecosistema Jaraba)
- Filosofía: "Sin Humo" — código limpio, práctico, sin complejidad innecesaria
- Stack: Drupal 11 + PHP 8.3 + MariaDB 11.2 + Redis + Qdrant
- Multi-tenancy: Group Module (soft isolation)
- Pagos: Stripe Connect (destination charges)
- IA: Claude API + Gemini API con strict grounding
- Servidor: IONOS Dedicated L-16 NVMe, 128GB RAM, AMD EPYC
 
## ARQUITECTURA MULTI-TENANT
- Cada tenant es un Group entity (gid)
- TODA query DEBE filtrar por grupo activo: \$group_context->getActiveGroup()
- Campos compartidos viven en entidades base; overrides por tenant en group_content
- Theming: CSS Custom Properties con presets industriales (doc 101, 102)
- Patrón: jaraba_theme base → presets por industria → overrides por tenant
- RBAC: Roles globales + roles de grupo. Verificar AMBOS en cada access check
 
## CONVENCIONES DE CÓDIGO
### Módulos Personalizados
- Prefijo: jaraba_* (NUNCA otro prefijo)
- Estructura: jaraba_{vertical}_{feature} (ej: jaraba_agro_catalog)
- Cada módulo: .info.yml, .module, .routing.yml, .permissions.yml, .services.yml
- Services: inyección de dependencias SIEMPRE, nunca \Drupal::service()
- Entidades: Content entities con revisions para todo dato de negocio
- Config entities para configuración administrativa
 
### PHP
- Estándar: Drupal Coding Standards + DrupalPractice
- PHPStan: Nivel 6 mínimo
- Tipado estricto: declare(strict_types=1) en TODOS los archivos
- Return types: obligatorios en todos los métodos públicos
- Documentación: PHPDoc completo en clases y métodos públicos
 
### JavaScript/TypeScript
- Framework frontend: React (dentro de Drupal libraries)
- ESLint: configuración Drupal
- Sin jQuery excepto donde Drupal core lo requiera
 
### CSS
- Metodología: BEM dentro de componentes Drupal
- Variables: CSS Custom Properties para theming (NUNCA hardcodear colores)
- Prefijo: --jaraba-{vertical}-{property}
- Mobile-first: breakpoints em-based
- Accesibilidad: contraste AA mínimo, focus visible obligatorio
 
### Twig Templates
- Prefijo: jaraba-{entity}--{view-mode}.html.twig
- Accesibilidad: aria-labels en todos los interactivos
- Sin lógica de negocio en templates (solo presentación)
 
## PATRONES OBLIGATORIOS
### ECA (Event-Condition-Action)
- Naming: ECA-{VERTICAL}-{NNN} (ej: ECA-AGRO-001)
- Documentar en YAML: evento, condiciones, acciones, rollback
- Tests: cada regla ECA debe tener test de integración
 
### API REST
- Versionado: /api/v1/{resource}
- Formato respuesta: JSON:API donde posible, REST custom si necesario
- Paginación: cursor-based para listas largas
- Rate limiting: por tenant y por IP
- Autenticación: OAuth 2.1 vía Simple OAuth
 
### Stripe Connect
- Modelo: Destination Charges (plataforma cobra, envía a connected accounts)
- Webhooks: SIEMPRE idempotentes, verificar firma Stripe
- Entidad jaraba_subscription para tracking local
- FOC (doc 113): sincronizar métricas MRR, churn, LTV
 
### IA / RAG
- Strict grounding: TODA respuesta IA debe citar fuente verificable
- Qdrant: colecciones por tenant con metadata filtering
- Rate limiting: por tenant, con fallback graceful
- Logging: TODA interacción IA registrada en ai_generation_log
- Modelo default: claude-sonnet-4-5 (coste/calidad óptimo)
- Fallback: gemini-2.5-flash si Claude no disponible
 
## TESTING
- PHPUnit: obligatorio para services y controllers
- Kernel tests: para queries con base de datos
- Functional tests: para flujos completos de usuario
- Coverage mínimo: 80% en módulos jaraba_*
- Fixtures: usar factories, NUNCA datos hardcodeados
 
## ACCESIBILIDAD (WCAG 2.1 AA)
- Contraste: ratio mínimo 4.5:1 texto, 3:1 componentes UI
- Teclado: TODO navegable sin ratón
- Screen readers: landmarks, headings jerárquicos, aria-live
- Focus: outline visible de 2px mínimo
- Formularios: labels asociados, errores inline, instrucciones claras
 
## SEGURIDAD
- Input: sanitizar SIEMPRE con Drupal\Component\Utility\Xss
- SQL: SOLO Drupal DB API o Entity Query (NUNCA raw SQL)
- CSRF: verificar token en todas las acciones mutativas
- Secrets: NUNCA en código, usar Drupal Key module
- Headers: CSP, X-Frame-Options, X-Content-Type-Options
- Dependencias: composer audit + npm audit en cada PR
 
## DOCUMENTACIÓN RELACIONADA
- Core: docs 01-07
- Empleabilidad: docs 08-24
- Emprendimiento: docs 25-44
- AgroConecta: docs 47-61, 80-82
- ComercioConecta: docs 62-79
- ServiciosConecta: docs 82-99
- Platform: docs 100-177
- Para specs detalladas: consultar doc específico por número

2.3 Principios de Diseño del CLAUDE.md
Principio	Implementación	Justificación
Concisión	Máximo 300 líneas	Ventana de contexto limitada; cada línea debe aportar valor
Reglas, no explicaciones	Imperativo: 'SIEMPRE', 'NUNCA'	Claude responde mejor a instrucciones directas que a prosa explicativa
Específico al proyecto	Solo convenciones Jaraba	No repetir lo que Claude ya sabe de Drupal/PHP general
Actualizable	Fecha de última actualización visible	El equipo debe actualizar cuando cambien convenciones
Sin secretos	NUNCA incluir API keys o credenciales	El archivo se versiona en Git; usar Drupal Key module

2.4 Estructura de Directorios del Pipeline Completo
jaraba-platform/
├── CLAUDE.md                          # Memoria persistente (este archivo)
├── .claude/
│   ├── settings.json                  # Configuración Claude Code
│   ├── agents/                        # Subagentes especializados
│   │   ├── reviewer.md                # Agente revisor de código
│   │   ├── tester.md                  # Agente generador de tests
│   │   └── security-auditor.md        # Agente auditor de seguridad
│   ├── commands/                      # Slash commands personalizados
│   │   ├── deploy-tenant.md           # /deploy-tenant
│   │   ├── create-vertical.md         # /create-vertical
│   │   ├── audit-wcag.md              # /audit-wcag
│   │   ├── fix-issue.md               # /fix-issue
│   │   ├── generate-api.md            # /generate-api
│   │   ├── create-eca.md              # /create-eca
│   │   ├── stripe-webhook.md          # /stripe-webhook
│   │   └── tenant-theme.md            # /tenant-theme
│   └── hooks/
│       ├── pre-commit.sh              # Linting + PHPCS + secrets
│       ├── post-commit.sh             # Notificación + docs update
│       ├── pre-push.sh                # Tests completos
│       ├── notification.sh            # Hook de notificación
│       └── pre-tool-use.sh            # Validación pre-herramienta
├── .claude/skills/                    # Skills especializados
│   ├── jaraba-drupal-theming/
│   │   └── SKILL.md
│   ├── jaraba-eca-workflows/
│   │   └── SKILL.md
│   ├── jaraba-stripe-connect/
│   │   └── SKILL.md
│   ├── jaraba-multi-tenant/
│   │   └── SKILL.md
│   ├── jaraba-sepe-compliance/
│   │   └── SKILL.md
│   ├── jaraba-ai-grounding/
│   │   └── SKILL.md
│   └── jaraba-vertical-blueprint/
│       └── SKILL.md
├── .claude/mcp/
│   ├── drupal-mcp-server.json         # Configuración Drupal MCP
│   ├── stitch-mcp.json                # Configuración Google Stitch
│   ├── stripe-mcp.json                # Configuración Stripe MCP
│   ├── github-mcp.json                # Configuración GitHub MCP
│   ├── sentry-mcp.json                # Configuración Sentry MCP
│   └── semgrep-mcp.json               # Configuración Semgrep MCP
├── web/
│   ├── modules/custom/jaraba_*/       # Módulos personalizados
│   └── themes/custom/jaraba_theme/    # Theme personalizado
├── .lando.yml                         # Entorno local
├── docker-compose.yml                 # Docker para CI/CD
└── composer.json                      # Dependencias PHP
 
3. Skills Personalizados — Expertise Encapsulada

Los Skills son el multiplicador más potente del pipeline. Cada skill es una carpeta con un archivo SKILL.md que Claude Code descubre y carga automáticamente cuando la tarea es relevante. No requieren invocación manual: el sistema los activa por contexto semántico. Encapsulan toda la expertise de los 177+ documentos del ecosistema en instrucciones ejecutables.

ℹ️ ACTIVACIÓN AUTOMÁTICA: Claude Code detecta el contexto de la tarea y carga los skills relevantes sin que el desarrollador lo solicite. Un skill bien escrito es invisible pero omnipresente.

3.1 Catálogo de Skills
Skill	Scope	Se Activa Cuando...	Docs Referencia
jaraba-drupal-theming	UI/UX/CSS	Tareas de UI, theming, estilos, templates Twig	05, 100-103
jaraba-eca-workflows	Automatización	Crear workflows, triggers, cron jobs	06, reglas ECA por vertical
jaraba-stripe-connect	Pagos/Billing	Pagos, suscripciones, facturación, FOC	134, 111, 113
jaraba-multi-tenant	Arquitectura	Datos por tenant, RBAC, aislamiento	07, 04, Group Module
jaraba-sepe-compliance	Compliance	Formación bonificada, certificaciones SEPE	105, 106, 107
jaraba-ai-grounding	IA/RAG	Copilots, recomendaciones, generación contenido	128-130, 108
jaraba-vertical-blueprint	Escalabilidad	Nuevos verticales, extensión de existentes	47-61, 62-79, 82-99

3.2 Drupal Theming & Design System

Nombre: jaraba-drupal-theming  |  Ubicación: .claude/skills/jaraba-drupal-theming/SKILL.md
Se activa cuando: Cualquier tarea de UI, theming, estilos, componentes visuales, CSS, Twig templates

Contenido completo del SKILL.md:
# SKILL: Jaraba Drupal Theming & Design System
 
## CUÁNDO APLICAR
Activa este skill cuando la tarea involucre: crear/modificar templates Twig,
CSS custom properties, presets de industria, componentes UI, theming multi-tenant,
responsive design, o cualquier aspecto visual de la plataforma.
 
## ARQUITECTURA DE THEMING (3 CAPAS)
 
### Capa 1: jaraba_theme (Base)
Archivo: web/themes/custom/jaraba_theme/jaraba_theme.info.yml
- Theme base de Drupal con Claro como base admin
- Define TODAS las CSS Custom Properties del sistema
- NO contiene estilos específicos de vertical ni tenant
 
### Capa 2: Presets de Industria (docs 101, 102)
Archivos: web/themes/custom/jaraba_theme/css/presets/{industria}.css
- Cada preset redefine variables CSS para su industria
- Industrias: agricultura, comercio-local, servicios-profesionales, formacion, tech-startup
- Se activan mediante clase en <body>: body.preset--{industria}
 
### Capa 3: Override por Tenant
Almacenamiento: Entidad tenant_theme_config en base de datos
- CSS Custom Properties personalizadas por tenant
- Logo, favicon, colores primarios/secundarios
- Se inyectan dinámicamente vía jaraba_theme_preprocess_html()
 
## CSS CUSTOM PROPERTIES (CATÁLOGO COMPLETO)
 
:root {
  /* === Paleta Base === */
  --jaraba-color-primary: #1B4F72;
  --jaraba-color-primary-light: #2E86C1;
  --jaraba-color-primary-dark: #154360;
  --jaraba-color-secondary: #E67E22;
  --jaraba-color-secondary-light: #F39C12;
  --jaraba-color-accent: #27AE60;
  --jaraba-color-error: #E74C3C;
  --jaraba-color-warning: #F39C12;
  --jaraba-color-success: #27AE60;
  --jaraba-color-info: #3498DB;
 
  /* === Superficies === */
  --jaraba-surface-background: #FFFFFF;
  --jaraba-surface-card: #FFFFFF;
  --jaraba-surface-elevated: #F8F9FA;
  --jaraba-surface-overlay: rgba(0,0,0,0.5);
 
  /* === Texto === */
  --jaraba-text-primary: #2C3E50;
  --jaraba-text-secondary: #7F8C8D;
  --jaraba-text-inverse: #FFFFFF;
  --jaraba-text-link: var(--jaraba-color-primary);
 
  /* === Tipografía === */
  --jaraba-font-family-heading: 'Montserrat', sans-serif;
  --jaraba-font-family-body: 'Roboto', sans-serif;
  --jaraba-font-family-mono: 'Roboto Mono', monospace;
  --jaraba-font-size-xs: 0.75rem;
  --jaraba-font-size-sm: 0.875rem;
  --jaraba-font-size-base: 1rem;
  --jaraba-font-size-lg: 1.125rem;
  --jaraba-font-size-xl: 1.25rem;
  --jaraba-font-size-2xl: 1.5rem;
  --jaraba-font-size-3xl: 2rem;
 
  /* === Espaciado === */
  --jaraba-space-xs: 0.25rem;
  --jaraba-space-sm: 0.5rem;
  --jaraba-space-md: 1rem;
  --jaraba-space-lg: 1.5rem;
  --jaraba-space-xl: 2rem;
  --jaraba-space-2xl: 3rem;
 
  /* === Bordes y Sombras === */
  --jaraba-radius-sm: 4px;
  --jaraba-radius-md: 8px;
  --jaraba-radius-lg: 12px;
  --jaraba-radius-full: 9999px;
  --jaraba-shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
  --jaraba-shadow-md: 0 4px 6px rgba(0,0,0,0.07);
  --jaraba-shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
 
  /* === Breakpoints (reference only, use in @media) === */
  /* sm: 640px, md: 768px, lg: 1024px, xl: 1280px, 2xl: 1536px */
}
 
## TWIG TEMPLATES - CONVENCIONES
 
1. Nomenclatura: jaraba-{entity}--{view-mode}.html.twig
2. Accesibilidad OBLIGATORIA:
   - Todos los <img> con alt text descriptivo
   - Todos los botones/links interactivos con aria-label
   - Headings jerárquicos (h1 > h2 > h3, sin saltar niveles)
   - Focus visible: outline 2px mínimo
   - Regiones con role="region" y aria-label
 
3. Patrón BEM para clases:
   .jaraba-card {}
   .jaraba-card__header {}
   .jaraba-card__body {}
   .jaraba-card--featured {}
 
4. No lógica de negocio en templates:
   ✗ {% if user.hasRole('admin') %} → USAR preprocess
   ✓ {% if is_admin %} → Variable preparada en preprocess
 
## WCAG 2.1 AA CHECKLIST (OBLIGATORIO EN CADA COMPONENTE)
- [ ] Contraste texto: ratio ≥ 4.5:1 (text) / ≥ 3:1 (large text/UI)
- [ ] Teclado: navegable con Tab, Enter, Escape, Arrow keys
- [ ] Focus visible: outline ≥ 2px, color con contraste suficiente
- [ ] Screen reader: landmarks, headings, aria-live para updates
- [ ] Responsive: funcional de 320px a 2560px
- [ ] Motion: respetar prefers-reduced-motion
- [ ] Touch targets: mínimo 44x44px en móvil

3.3 ECA Workflows & Automation

Nombre: jaraba-eca-workflows  |  Ubicación: .claude/skills/jaraba-eca-workflows/SKILL.md
Se activa cuando: Creación de automatizaciones, workflows, triggers de negocio, reglas ECA, cron jobs

Contenido completo del SKILL.md:
# SKILL: Jaraba ECA Workflows & Automation
 
## CUÁNDO APLICAR
Activa este skill cuando la tarea involucre: crear reglas de automatización,
definir workflows de negocio, configurar triggers/events, implementar cron jobs,
o cualquier lógica event-driven del ecosistema.
 
## MÓDULO ECA EN DRUPAL 11
ECA (Event-Condition-Action) es el sistema de automatización de Jaraba.
Reemplaza Rules module con arquitectura más limpia y mejor rendimiento.
 
Módulo Drupal: drupal/eca (ya en composer.json)
Submodules activos: eca_base, eca_content, eca_user, eca_queue, eca_cron
 
## CONVENCIÓN DE NOMENCLATURA
 
Formato: ECA-{VERTICAL}-{NNN}
Ejemplos:
  ECA-CORE-001    → Regla core del sistema
  ECA-EMPL-001    → Regla de Empleabilidad
  ECA-EMPR-001    → Regla de Emprendimiento
  ECA-AGRO-001    → Regla de AgroConecta
  ECA-COME-001    → Regla de ComercioConecta
  ECA-SERV-001    → Regla de ServiciosConecta
  ECA-CH-001      → Regla de Content Hub
  ECA-BILL-001    → Regla de Billing/Stripe
 
## ESTRUCTURA YAML DE CADA REGLA ECA
 
Cada regla ECA DEBE documentarse en YAML:
 
id: eca_{vertical}_{nnn}
label: 'Descripción clara de la regla'
status: true
weight: 0
events:
  - plugin_id: 'content_entity:insert'  # Evento que dispara
    configuration:
      entity_type_id: node
      bundle: product
conditions:
  - plugin_id: 'eca_entity_field_value'  # Condición a verificar
    configuration:
      field_name: status
      expected_value: 1
actions:
  - plugin_id: 'eca_queue_task'  # Acción a ejecutar
    configuration:
      queue_name: 'jaraba_notifications'
      data: '{{ entity.id }}'
 
## REGLAS ECA CORE DEL ECOSISTEMA
 
### ECA-CORE-001: Tenant Onboarding
Evento: User joins group (nuevo tenant miembro)
Condiciones: User tiene rol 'tenant_admin'
Acciones:
  1. Crear configuración default de theme
  2. Crear Knowledge Base inicial vacía
  3. Enviar email de bienvenida con wizard link
  4. Registrar en audit_log
 
### ECA-CORE-002: Content Moderation
Evento: Node insert/update
Condiciones: Content type soporta moderación
Acciones:
  1. Si draft → notificar reviewer asignado
  2. Si published → indexar en Qdrant
  3. Si archived → desindexar de Qdrant
 
### ECA-CORE-003: AI Usage Logging
Evento: AI API call completada
Condiciones: Siempre
Acciones:
  1. Registrar en ai_generation_log
  2. Actualizar contadores de uso por tenant
  3. Si límite 80% → alertar tenant_admin
  4. Si límite 100% → bloquear y notificar
 
## PATRONES DE IMPLEMENTACIÓN
 
### Patrón Queue + Worker (para operaciones pesadas)
NUNCA ejecutar operaciones costosas síncronamente en ECA.
Siempre encolar y procesar en background:
 
Evento → ECA → Queue Task → Cron → Worker → Resultado
 
### Patrón Idempotente
TODA acción ECA DEBE ser idempotente:
- Verificar estado ANTES de actuar
- Usar locks distribuidos (Redis) para operaciones críticas
- Registrar ejecución para evitar duplicados
 
### Patrón Rollback
Para operaciones multi-paso, implementar compensación:
- Registrar cada paso completado
- En caso de fallo, ejecutar compensación en orden inverso
- Notificar al admin si rollback falla
 
## TESTING DE REGLAS ECA
CADA regla ECA requiere test de integración:
- Test que el evento dispara la regla
- Test que la condición filtra correctamente
- Test que la acción ejecuta lo esperado
- Test de idempotencia (ejecutar 2x = mismo resultado)
- Test de fallo (qué pasa si la acción falla)

3.4 Stripe Connect & Billing

Nombre: jaraba-stripe-connect  |  Ubicación: .claude/skills/jaraba-stripe-connect/SKILL.md
Se activa cuando: Funcionalidades de pago, facturación, suscripciones, webhooks Stripe, métricas financieras

Contenido completo del SKILL.md:
# SKILL: Jaraba Stripe Connect & Billing
 
## CUÁNDO APLICAR
Activa este skill cuando la tarea involucre: pagos, suscripciones, checkout,
facturación, webhooks de Stripe, métricas SaaS financieras, o integración
con el Financial Operations Center (FOC).
 
## MODELO DE NEGOCIO: DESTINATION CHARGES
Jaraba usa el modelo Destination Charges de Stripe Connect:
- La plataforma (Jaraba) cobra al cliente
- Jaraba retiene su comisión (application_fee_amount)
- El resto se envía automáticamente al connected account (tenant)
 
¿POR QUÉ Destination Charges y no Direct Charges?
- Control total del flujo de pago
- Gestión centralizada de disputas
- Facturación unificada para compliance
- Flexibility en splits de comisión
 
## ARQUITECTURA DE ENTIDADES
 
### jaraba_subscription
Campos:
  - id (UUID)
  - tenant_id (group reference)
  - stripe_subscription_id (string, unique)
  - stripe_customer_id (string)
  - plan_id (entity reference → jaraba_plan)
  - status (enum: trialing|active|past_due|canceled|unpaid)
  - current_period_start (datetime)
  - current_period_end (datetime)
  - cancel_at_period_end (boolean)
  - trial_end (datetime, nullable)
  - metadata (JSON)
 
### jaraba_plan
Campos:
  - id (UUID)
  - vertical (enum: empleabilidad|emprendimiento|agroconecta|comercioconecta|serviciosconecta)
  - tier (enum: starter|growth|pro|enterprise)
  - stripe_price_id (string, unique)
  - name (string)
  - monthly_price (decimal)
  - annual_price (decimal)
  - features (JSON: array de feature flags)
  - limits (JSON: {ai_calls: 100, storage_gb: 5, users: 3})
 
## WEBHOOKS — SIEMPRE IDEMPOTENTES
 
Endpoint: /api/v1/webhooks/stripe
Verificación: SIEMPRE verificar firma con stripe_webhook_secret
 
Eventos manejados:
  customer.subscription.created    → Crear jaraba_subscription
  customer.subscription.updated    → Actualizar status/período
  customer.subscription.deleted    → Marcar como canceled
  invoice.payment_succeeded        → Registrar pago, actualizar FOC
  invoice.payment_failed           → Alertar tenant, activar dunning
  customer.subscription.trial_will_end → Notificar tenant 3 días antes
  account.updated                  → Actualizar estado KYC connected account
 
### Patrón de Idempotencia para Webhooks
 
public function handleWebhook(Request request): Response {
  // 1. Verificar firma Stripe
  stripe_event = Webhook::constructEvent(payload, sig, secret);
 
  // 2. Verificar idempotencia
  if (event_already_processed(stripe_event->id)) {
    return new Response('Already processed', 200);
  }
 
  // 3. Procesar en transacción
  try {
    db_transaction_start();
    process_event(stripe_event);
    mark_event_processed(stripe_event->id);
    db_transaction_commit();
  } catch (Exception e) {
    db_transaction_rollback();
    log_error(e);
    return new Response('Error', 500); // Stripe reintentará
  }
 
  return new Response('OK', 200);
}
 
## MÉTRICAS FOC (Financial Operations Center)
 
El FOC (doc 113) requiere estas métricas actualizadas:
  - MRR (Monthly Recurring Revenue): sum de todas las suscripciones activas
  - ARR (Annual Recurring Revenue): MRR * 12
  - Churn Rate: suscripciones canceladas / total activas (mensual)
  - LTV (Lifetime Value): ARPU / churn_rate
  - ARPU (Average Revenue Per User): MRR / total_subscribers
  - NRR (Net Revenue Retention): (MRR inicio + expansions - contractions - churn) / MRR inicio
  - CAC Payback: meses para recuperar coste adquisición
 
Actualización: vía ECA-BILL-001 (cron diario a las 02:00 UTC)
 
## SEGURIDAD
- Stripe API keys: NUNCA en código, usar Drupal Key module
- Webhook secret: almacenar en Key module
- PCI compliance: Stripe.js para tokenización, NUNCA datos de tarjeta en servidor
- Logs: NUNCA loguear números de tarjeta, solo últimos 4 dígitos
- Testing: SIEMPRE usar Stripe test mode keys en desarrollo

3.5 Multi-Tenant Architecture

Nombre: jaraba-multi-tenant  |  Ubicación: .claude/skills/jaraba-multi-tenant/SKILL.md
Se activa cuando: Funcionalidades con datos/configuración por tenant, queries con filtro de grupo, RBAC, aislamiento

Contenido completo del SKILL.md:
# SKILL: Jaraba Multi-Tenant Architecture
 
## CUÁNDO APLICAR
Activa este skill cuando la tarea involucre: queries que acceden a datos
de tenant, control de acceso por grupo, configuración por tenant,
aislamiento de datos, o cualquier funcionalidad multi-tenant.
 
## ARQUITECTURA: SOFT MULTI-TENANCY CON GROUP MODULE
 
Drupal Group Module proporciona aislamiento lógico sin bases de datos separadas.
Cada tenant = una entidad Group con su propia membresía, contenido y configuración.
 
### Principio Cardinal
TODA query a datos de negocio DEBE filtrar por grupo activo.
NUNCA exponer datos cross-tenant accidentalmente.
 
## CONTEXTO DE GRUPO
 
### Obtener Grupo Activo
// En services (inyección de dependencias):
public function __construct(
  private GroupContextInterface $group_context
) {}
 
public function getData(): array {
  $group = $this->group_context->getActiveGroup();
  if (!$group) {
    throw new AccessDeniedException('No active tenant context');
  }
  // SIEMPRE filtrar por grupo
  return $this->entityQuery('jaraba_product')
    ->condition('group_id', $group->id())
    ->execute();
}
 
### Resolución de Grupo
El grupo activo se resuelve (en orden de prioridad):
1. URL path: /{tenant-slug}/... → resolver por slug
2. Subdominio: {tenant}.jarabaimpact.com → resolver por subdomain
3. Header: X-Tenant-ID → para API calls
4. Sesión: usuario pertenece a un solo grupo → auto-resolve
 
## RBAC (Role-Based Access Control)
 
### Roles Globales (Drupal)
- anonymous: visitante sin autenticar
- authenticated: usuario autenticado básico
- platform_admin: administrador Jaraba (super admin)
 
### Roles de Grupo (Group Module)
- tenant_admin: administrador del tenant
- tenant_manager: gestor con permisos amplios
- tenant_member: miembro estándar
- tenant_viewer: solo lectura
 
### Verificación de Acceso (OBLIGATORIO)
En CADA access check verificar AMBOS niveles:
 
public function access(AccountInterface $account): AccessResult {
  // 1. Verificar rol global
  if ($account->hasPermission('administer jaraba')) {
    return AccessResult::allowed();
  }
 
  // 2. Verificar rol de grupo
  $group = $this->groupContext->getActiveGroup();
  $membership = $group->getMember($account);
  if (!$membership) {
    return AccessResult::forbidden('Not a member of this tenant');
  }
 
  // 3. Verificar permiso específico de grupo
  if (!$group->hasPermission('manage products', $account)) {
    return AccessResult::forbidden('Insufficient tenant permissions');
  }
 
  return AccessResult::allowed()
    ->addCacheableDependency($group)
    ->addCacheableDependency($membership);
}
 
## CONFIGURACIÓN POR TENANT
 
### Patrón: Config Entity por Grupo
Usar entidades de configuración asociadas al grupo, NO settings globales:
 
Entidad: tenant_config
Campos: group_id, config_key, config_value (JSON), vertical
 
Ejemplo uso:
$config = TenantConfig::loadByGroup($group->id(), 'ai_settings');
$max_ai_calls = $config->getValue()['monthly_limit'] ?? 100;
 
### Patrón: Feature Flags por Plan
Los features disponibles dependen del plan de suscripción del tenant:
 
$subscription = $this->subscriptionManager->getActive($group->id());
$plan = $subscription->getPlan();
if (!$plan->hasFeature('ai_copilot')) {
  throw new FeatureNotAvailableException('Upgrade to Growth plan');
}
 
## CACHE — CRITICAL PARA MULTI-TENANT
Todas las respuestas cacheables DEBEN incluir cache tags y contexts:
 
Cache contexts (OBLIGATORIOS para multi-tenant):
  - 'group': varía por grupo activo
  - 'user.group_permissions': varía por permisos del usuario en el grupo
 
Cache tags (para invalidación precisa):
  - 'group:{gid}': invalidar cuando cambie el grupo
  - 'jaraba_product_list:group:{gid}': invalidar lista de productos del grupo
 
NUNCA cachear respuestas sin context 'group'.
Esto causaría data leak cross-tenant.
 
## TESTING MULTI-TENANT
Cada test DEBE:
1. Crear dos grupos de prueba (tenant A, tenant B)
2. Crear datos en ambos
3. Verificar que operaciones en tenant A NO ven datos de tenant B
4. Verificar que platform_admin SÍ ve datos cross-tenant
5. Verificar cache isolation entre tenants

3.6 SEPE Teleformación Compliance

Nombre: jaraba-sepe-compliance  |  Ubicación: .claude/skills/jaraba-sepe-compliance/SKILL.md
Se activa cuando: Desarrollo de formación bonificada, certificaciones, requisitos SEPE, web services SEPE

Contenido completo del SKILL.md:
# SKILL: Jaraba SEPE Teleformación Compliance
 
## CUÁNDO APLICAR
Activa este skill cuando la tarea involucre: formación bonificada,
teleformación, certificaciones SEPE, homologación, web services de SEPE,
tracking de horas formativas, o generación de certificados oficiales.
 
## CONTEXTO DE NEGOCIO
SEPE (Servicio Público de Empleo Estatal) homologa plataformas de
teleformación para formación bonificada. Docs de referencia: 105, 106, 107.
La homologación desbloquea formación subvencionada para empresas.
 
## REQUISITOS TÉCNICOS DE HOMOLOGACIÓN (doc 105)
 
### 1. Tracking de Actividad
- Registro de CADA sesión del alumno: inicio, fin, duración
- Granularidad: por módulo formativo, no solo por curso
- Verificación de identidad: login seguro + checkpoints periódicos
- Anti-fraude: detección de inactividad (sin interacción > 15 min)
- Almacenamiento: mínimo 5 años para auditoría
 
### 2. Contenidos Formativos
- Estructura: módulos → unidades → actividades
- Evaluación: tests con nota mínima configurable (default 70%)
- Multimedia: soporte video, audio, documentos, ejercicios interactivos
- Accesibilidad: WCAG 2.1 AA obligatorio para formación bonificada
 
### 3. Comunicación
- Tutorización: chat/foro con tutor asignado
- Tiempo de respuesta máximo tutor: 48 horas
- Notificaciones: recordatorios de progreso, deadlines
 
### 4. Web Services SEPE
Endpoint: https://servicios.sepe.gob.es/formacion/...
Protocolo: SOAP (XML)
Autenticación: Certificado digital de la entidad
 
Operaciones requeridas:
  - comunicarInicio: Notificar inicio de acción formativa
  - comunicarFin: Notificar fin de acción formativa
  - comunicarParticipantes: Enviar lista de participantes
  - comunicarAsistencia: Enviar registros de asistencia
  - consultarEstado: Verificar estado de comunicaciones
 
### 5. Certificados
- Generación automática al completar curso
- Firma digital PAdES (doc 89)
- Datos obligatorios: nombre alumno, DNI, denominación formativa,
  horas, fechas, calificación, nº expediente
 
## ENTIDADES DEL MÓDULO jaraba_sepe
 
sepe_training_action:
  - id, title, sepe_code, modality (teleformacion|mixta)
  - start_date, end_date, total_hours
  - status (draft|communicated|in_progress|completed|audited)
 
sepe_participant:
  - id, user_id, training_action_id
  - dni, name, enrollment_date
  - completion_status, final_grade, certificate_id
 
sepe_session_log:
  - id, participant_id, module_id
  - session_start, session_end, duration_seconds
  - ip_address, user_agent
  - activity_type (video|reading|exercise|test)
  - inactivity_flags (JSON)
 
## TESTING SEPE
- Simular flujo completo: matrícula → tracking → evaluación → certificado
- Verificar que tracking cumple granularidad requerida
- Test de web services SEPE en modo sandbox
- Verificar generación de certificados con firma digital

3.7 AI Strict Grounding & RAG

Nombre: jaraba-ai-grounding  |  Ubicación: .claude/skills/jaraba-ai-grounding/SKILL.md
Se activa cuando: Funcionalidades IA: copilots, recomendaciones, matching, generación contenido, chatbots

Contenido completo del SKILL.md:
# SKILL: Jaraba AI Strict Grounding & RAG
 
## CUÁNDO APLICAR
Activa este skill cuando la tarea involucre: agentes IA, copilots por vertical,
sistema de recomendaciones, matching engine, generación de contenido,
chatbots, o cualquier funcionalidad que use LLMs.
 
## PRINCIPIO FUNDAMENTAL: STRICT GROUNDING
TODA respuesta de IA en el ecosistema Jaraba DEBE estar fundamentada
en datos verificables. Las alucinaciones son inaceptables en contexto
B2B/B2G donde decisiones de negocio dependen de las respuestas.
 
Regla: Si la IA no puede fundamentar su respuesta en datos del knowledge
base del tenant, DEBE decir "No tengo información suficiente para
responder esto con precisión" en lugar de inventar.
 
## ARQUITECTURA RAG (Retrieval-Augmented Generation)
 
### Stack
- Vector DB: Qdrant (ya presupuestado, colección por tenant)
- Embedding: text-embedding-3-small (OpenAI) o equivalente
- LLM primario: claude-sonnet-4-5 (coste/calidad óptimo para SaaS)
- LLM fallback: gemini-2.5-flash (si Claude no disponible)
- Cache: Redis para respuestas frecuentes (TTL configurable por tenant)
 
### Flujo RAG Completo
1. QUERY: Usuario envía pregunta
2. CLASSIFY: Determinar intent y vertical
3. RETRIEVE: Buscar en Qdrant (top-k=5, score_threshold=0.7)
4. FILTER: Aplicar metadata filtering por tenant_id + vertical
5. AUGMENT: Construir prompt con contexto recuperado
6. GENERATE: Enviar a LLM con system prompt + grounding instructions
7. VERIFY: Post-procesamiento para detectar claims sin fuente
8. RESPOND: Entregar respuesta con fuentes citadas
9. LOG: Registrar en ai_generation_log (SIEMPRE)
 
### Colecciones Qdrant por Vertical
Naming: jaraba_{vertical}_{tenant_id}
Ejemplo: jaraba_agroconecta_group_42
 
Cada documento indexado tiene metadata:
{
  "tenant_id": 42,
  "vertical": "agroconecta",
  "doc_type": "product|faq|policy|training",
  "source_entity_id": "node:123",
  "language": "es",
  "updated_at": "2026-02-28T10:00:00Z"
}
 
## RATE LIMITING POR TENANT
 
Límites según plan (configurable en jaraba_plan):
  Starter:    50 AI calls/mes,  5K tokens/call
  Growth:    200 AI calls/mes, 10K tokens/call
  Pro:       500 AI calls/mes, 15K tokens/call
  Enterprise: Ilimitado,       20K tokens/call
 
Implementación: Redis counter con key tenant:{gid}:ai_calls:{YYYY-MM}
 
## SYSTEM PROMPTS — ESTRUCTURA OBLIGATORIA
 
Cada copilot vertical DEBE seguir esta estructura de prompt:
 
<system>
Eres {nombre_copilot}, asistente especializado de {vertical} en la
plataforma Jaraba.
 
<grounding_rules>
- SOLO responde basándote en la información proporcionada en <context>
- Si no tienes información suficiente, di "No tengo datos para responder esto"
- NUNCA inventes datos, cifras, URLs o referencias
- Cita la fuente: "Según [documento/FAQ]: ..."
- Si el usuario pide algo fuera de tu vertical, redirige amablemente
</grounding_rules>
 
<tenant_context>
{tenant_info_from_knowledge_training}
</tenant_context>
 
<skills>
{resolved_skills_from_skill_manager}
</skills>
 
<context>
{rag_retrieved_documents}
</context>
</system>
 
## LOGGING OBLIGATORIO
TODA interacción IA genera un registro en ai_generation_log:
 
ai_generation_log:
  - id, tenant_id, user_id
  - copilot_type (empleabilidad|emprendimiento|agro|comercio|servicios)
  - model_used (claude-sonnet-4-5|gemini-2.5-flash)
  - prompt_tokens, completion_tokens, total_tokens
  - latency_ms
  - user_query (truncated at 500 chars)
  - grounding_score (0-100: % of response backed by sources)
  - user_feedback (thumbs_up|thumbs_down|null)
  - sources_cited (JSON array of entity references)
  - created_at
 
## FALLBACK CHAIN
1. Intentar Claude API
2. Si rate limited/unavailable → Gemini API
3. Si ambos fallan → Respuesta cached si disponible
4. Si no cache → Mensaje: "Servicio temporalmente no disponible"
NUNCA dejar al usuario sin respuesta.

3.8 Vertical Blueprint (Base+Extension)

Nombre: jaraba-vertical-blueprint  |  Ubicación: .claude/skills/jaraba-vertical-blueprint/SKILL.md
Se activa cuando: Creación de nuevos verticales, extensión de existentes, replicación de patrones AgroConecta

Contenido completo del SKILL.md:
# SKILL: Jaraba Vertical Blueprint (Base+Extension)
 
## CUÁNDO APLICAR
Activa este skill cuando la tarea involucre: crear un nuevo vertical,
extender un vertical existente, replicar patrones entre verticales,
o implementar funcionalidades que deban existir en múltiples verticales.
 
## PATRÓN BASE+EXTENSION
 
### Filosofía
AgroConecta es el BLUEPRINT para todos los verticales de marketplace.
ComercioConecta y ServiciosConecta EXTIENDEN el patrón, no lo reinventan.
 
### Capas de Reutilización
1. CORE (docs 01-07): Entidades base, permisos, APIs, theming, ECA, multi-tenant
2. VERTICAL_BASE: Módulo compartido por verticales similares
3. VERTICAL_SPECIFIC: Extensiones únicas de cada vertical
 
### Ejemplo Concreto
jaraba_marketplace_base (compartido):
  - Product catalog base
  - Order system base
  - Checkout flow base
  - Reviews system base
 
jaraba_agro_* (AgroConecta específico):
  - Traceability system (doc 80)
  - QR dinámico agrícola (doc 81)
  - Seasonal pricing
 
jaraba_comercio_* (ComercioConecta específico):
  - POS integration (doc 63)
  - Flash offers (doc 64)
  - Local SEO (doc 71)
 
## CHECKLIST PARA NUEVO VERTICAL
 
### 1. Entidades (adaptar de docs 47-49)
- [ ] Product/Service entity con campos verticales
- [ ] Order entity con status machine
- [ ] Provider/Producer profile entity
- [ ] Customer/Consumer profile entity
- [ ] Review entity
 
### 2. APIs REST (adaptar de doc 61)
- [ ] CRUD de productos/servicios
- [ ] Flujo de pedidos/reservas
- [ ] Búsqueda y filtrado
- [ ] Dashboard endpoints
- [ ] Webhooks para integraciones
 
### 3. Dashboards (adaptar de docs 57-58)
- [ ] Dashboard Provider: ventas, pedidos, métricas
- [ ] Dashboard Customer: historial, favoritos
- [ ] Dashboard Admin: KPIs del vertical
 
### 4. IA/Copilot (adaptar de doc 44)
- [ ] System prompt del vertical
- [ ] Skills específicos (doc 129)
- [ ] Knowledge base del vertical en Qdrant
 
### 5. Automatizaciones ECA
- [ ] Notificaciones de pedido/reserva
- [ ] Alertas de stock/disponibilidad
- [ ] Generación de informes periódicos
- [ ] Sync con sistemas externos
 
### 6. Theming
- [ ] Preset de industria (doc 101)
- [ ] Componentes UI específicos
- [ ] Mobile-responsive completo
 
### 7. Testing
- [ ] Unit tests de services
- [ ] Integration tests de APIs
- [ ] E2E tests de flujos críticos
- [ ] Performance tests de búsqueda
- [ ] Accessibility tests WCAG 2.1 AA
 
## TIEMPO ESTIMADO POR VERTICAL NUEVO
Usando el patrón Base+Extension con AgroConecta como blueprint:
  - Vertical marketplace (tipo Comercio): 60-80% reutilizable → 200-280h
  - Vertical servicios (tipo Servicios): 40-60% reutilizable → 300-400h
  - Vertical nuevo (sin precedente): 20-30% reutilizable → 400-550h

 
4. Subagentes Especializados

Los subagentes son instancias de Claude con contexto propio, herramientas y permisos aislados. Ejecutan tareas especializadas en paralelo al agente principal, eliminando el sesgo de la auto-revisión. El patrón Writer/Reviewer es el más revolucionario: una instancia escribe código, otra independiente lo revisa sin conocer el proceso de creación.

4.1 Subagente: Reviewer (Writer/Reviewer Pattern)
Ubicación: .claude/agents/reviewer.md

# Subagente: Code Reviewer
# Rol: Revisar código generado por el agente principal sin sesgo
 
## IDENTIDAD
Eres un revisor de código senior especializado en Drupal 11 y el Ecosistema Jaraba.
Tu rol es EXCLUSIVAMENTE revisar, NUNCA escribir código nuevo.
 
## CRITERIOS DE REVISIÓN (en orden de prioridad)
 
### 1. Seguridad (BLOQUEANTE)
- ¿Hay inyección SQL? (Solo Drupal DB API permitida)
- ¿Hay XSS? (Todo output sanitizado con Xss::filter o #markup)
- ¿Hay CSRF? (Token verificado en mutaciones)
- ¿Secrets hardcodeados? (NUNCA — usar Key module)
- ¿Data leak cross-tenant? (TODA query filtra por group_id)
 
### 2. Arquitectura (BLOQUEANTE)
- ¿Sigue convenciones jaraba_* para módulos?
- ¿Inyección de dependencias? (NUNCA \Drupal::service())
- ¿Multi-tenant correcto? (Cache contexts incluyen 'group')
- ¿Entidades con revisions para datos de negocio?
- ¿API REST versionada? (/api/v1/...)
 
### 3. Calidad (WARNING)
- ¿PHPDoc completo en clases/métodos públicos?
- ¿Tests incluidos? (PHPUnit mínimo para services)
- ¿Manejo de errores? (try/catch con logging, no silenciar)
- ¿Strict types? (declare(strict_types=1) en todos los archivos)
- ¿Return types en métodos públicos?
 
### 4. Accesibilidad (WARNING)
- ¿Templates con aria-labels en interactivos?
- ¿Contraste AA? (4.5:1 texto, 3:1 UI)
- ¿Focus visible? (outline ≥ 2px)
- ¿Responsive? (funcional desde 320px)
 
### 5. Performance (ADVISORY)
- ¿Queries N+1? (Usar entity loading múltiple)
- ¿Cache tags correctos? (Invalidación precisa)
- ¿Operaciones pesadas en queue? (No síncronas)
 
## FORMATO DE RESPUESTA
Para CADA archivo revisado, responder:
 
ARCHIVO: {path}
RESULTADO: APROBADO | BLOQUEADO | WARNING
 
[Si BLOQUEADO o WARNING, listar issues:]
- [SEGURIDAD] Línea {n}: {descripción del problema}
  SUGERENCIA: {cómo arreglarlo}
 
RESUMEN GLOBAL: APROBADO PARA MERGE | REQUIERE CAMBIOS

4.2 Subagente: Tester
Ubicación: .claude/agents/tester.md

# Subagente: Test Generator
# Rol: Generar tests comprehensivos para código nuevo
 
## IDENTIDAD
Eres un ingeniero QA senior especializado en testing de Drupal 11.
Tu rol es generar tests que cubran todos los caminos críticos.
 
## TIPOS DE TEST A GENERAR
 
### Unit Tests (PHPUnit)
- Para: Services, Value Objects, Utilities
- Ubicación: modules/custom/jaraba_*/tests/src/Unit/
- Patrón: 1 test class por service, múltiples métodos de test
- Mocks: mockear TODAS las dependencias externas
- Coverage: ≥ 80% de líneas del service
 
### Kernel Tests
- Para: Entity operations, queries con DB, cache
- Ubicación: modules/custom/jaraba_*/tests/src/Kernel/
- Patrón: Extender KernelTestBase, instalar solo módulos necesarios
- Multi-tenant: SIEMPRE crear 2 grupos y verificar aislamiento
 
### Functional Tests
- Para: Flujos de usuario completos
- Ubicación: modules/custom/jaraba_*/tests/src/Functional/
- Patrón: Simular navegación web, verificar respuestas HTTP
- Accesibilidad: verificar aria-labels y estructura semántica
 
## NAMING CONVENTION
test{Acción}_{Escenario}_{Resultado}
Ejemplo: testCreateProduct_WithValidData_ReturnsCreated
Ejemplo: testCreateProduct_WithoutGroupContext_ThrowsAccessDenied
Ejemplo: testListProducts_AsTenantB_ReturnsOnlyTenantBProducts

4.3 Subagente: Security Auditor
Ubicación: .claude/agents/security-auditor.md

# Subagente: Security Auditor
# Rol: Auditoría de seguridad profunda pre-deploy
 
## IDENTIDAD
Eres un auditor de seguridad especializado en aplicaciones web Drupal.
Tu rol es identificar vulnerabilidades ANTES de que lleguen a producción.
 
## CHECKLIST OWASP TOP 10 ADAPTADO A DRUPAL
 
### A01: Broken Access Control
- Verificar RBAC en CADA endpoint
- Verificar aislamiento multi-tenant en CADA query
- Buscar acceso directo a entidades sin access check
- Verificar que admin routes requieren permisos correctos
 
### A02: Cryptographic Failures
- Verificar TLS en todas las conexiones externas
- Buscar datos sensibles en logs (tarjetas, passwords, DNI)
- Verificar que passwords usan bcrypt (Drupal default)
- Verificar que API keys están en Key module
 
### A03: Injection
- Buscar raw SQL (PROHIBIDO — solo Drupal DB API)
- Verificar sanitización de inputs en TODAS las formas
- Buscar eval(), exec(), system() (PROHIBIDOS)
- Verificar que uploads validan mime type Y extensión
 
### A07: Auth Failures
- Verificar rate limiting en login
- Verificar MFA disponible para admin roles
- Buscar session fixation vulnerabilities
- Verificar token expiration en OAuth 2.1
 
## FORMATO DE REPORTE
Severity: CRITICAL | HIGH | MEDIUM | LOW | INFO
Para cada hallazgo:
  [SEVERITY] {Título}
  Ubicación: {archivo}:{línea}
  Descripción: {qué es vulnerable}
  Impacto: {qué podría explotar un atacante}
  Remediación: {cómo arreglarlo, con código ejemplo}
  Referencia: {CWE-XXX o OWASP código}
 
5. Hooks — Quality Gates Automáticos

Los hooks son scripts determinísticos que se ejecutan fuera del loop agéntico en eventos específicos de Claude Code. A diferencia de los skills (que guían al agente), los hooks imponen restricciones que el agente no puede saltarse. Son la última línea de defensa para mantener la calidad del código.

5.1 Configuración en settings.json
Ubicación: .claude/settings.json
{
  "hooks": {
    "PreToolUse": [
      {
        "matcher": "Edit|Create|Write",
        "hook": ".claude/hooks/pre-tool-use.sh"
      }
    ],
    "PostToolUse": [
      {
        "matcher": "Edit|Create|Write",
        "hook": ".claude/hooks/post-edit-lint.sh"
      }
    ],
    "PreCommit": [
      {
        "hook": ".claude/hooks/pre-commit.sh"
      }
    ],
    "PostCommit": [
      {
        "hook": ".claude/hooks/post-commit.sh"
      }
    ],
    "PrePush": [
      {
        "hook": ".claude/hooks/pre-push.sh"
      }
    ]
  },
  "permissions": {
    "allow": [
      "Read",
      "Edit",
      "Write",
      "Bash(composer *)",
      "Bash(vendor/bin/phpunit *)",
      "Bash(vendor/bin/phpcs *)",
      "Bash(vendor/bin/phpstan *)",
      "Bash(npm *)",
      "Bash(drush *)",
      "Bash(git *)",
      "Bash(lando *)"
    ],
    "deny": [
      "Bash(rm -rf /)",
      "Bash(curl * | bash)",
      "Bash(wget * | bash)",
      "Bash(chmod 777 *)"
    ]
  }
}

5.2 Hook: Pre-Commit
Se ejecuta antes de cada commit. Bloquea el commit si falla cualquier validación.
#!/bin/bash
# .claude/hooks/pre-commit.sh
# Quality gate: BLOQUEA commit si falla
 
set -e
EXIT_CODE=0
 
echo "🔍 Pre-commit quality gates..."
 
# 1. PHP CodeSniffer (Drupal standards)
echo "  [1/5] PHP CodeSniffer..."
if ! vendor/bin/phpcs --standard=Drupal,DrupalPractice \
    --extensions=php,module,install,theme \
    web/modules/custom/jaraba_* 2>/dev/null; then
  echo "  ❌ PHPCS failed. Fix coding standards."
  EXIT_CODE=1
fi
 
# 2. PHPStan (Static Analysis Level 6)
echo "  [2/5] PHPStan..."
if ! vendor/bin/phpstan analyse web/modules/custom/jaraba_* \
    --level=6 --no-progress 2>/dev/null; then
  echo "  ❌ PHPStan failed. Fix type errors."
  EXIT_CODE=1
fi
 
# 3. ESLint (JavaScript)
echo "  [3/5] ESLint..."
if ls web/modules/custom/jaraba_*/js/*.js 1>/dev/null 2>&1; then
  if ! npx eslint web/modules/custom/jaraba_*/js/ 2>/dev/null; then
    echo "  ❌ ESLint failed. Fix JS errors."
    EXIT_CODE=1
  fi
fi
 
# 4. Secrets Detection
echo "  [4/5] Secrets scan..."
SECRETS_PATTERNS="(api_key|api[-_]?secret|password|passwd|token|secret[-_]?key|private[-_]?key)\s*[:=]\s*['\"][^'\"]+['\"]"
if git diff --cached --name-only | xargs grep -lPi "$SECRETS_PATTERNS" 2>/dev/null; then
  echo "  ❌ POTENTIAL SECRETS DETECTED. Use Drupal Key module."
  EXIT_CODE=1
fi
 
# 5. Strict Types Check
echo "  [5/5] Strict types..."
for file in $(git diff --cached --name-only -- '*.php'); do
  if [[ -f "$file" ]] && ! head -5 "$file" | grep -q "declare(strict_types=1)"; then
    echo "  ❌ Missing strict_types in $file"
    EXIT_CODE=1
  fi
done
 
if [ $EXIT_CODE -eq 0 ]; then
  echo "✅ All pre-commit checks passed."
else
  echo "🚫 Pre-commit checks FAILED. Fix issues before committing."
fi
 
exit $EXIT_CODE

5.3 Hook: Pre-Push
Se ejecuta antes de push. Ejecuta tests completos para evitar romper el pipeline CI/CD.
#!/bin/bash
# .claude/hooks/pre-push.sh
# Full test suite before push
 
set -e
echo "🧪 Pre-push test suite..."
 
# 1. PHPUnit (Unit + Kernel tests)
echo "  [1/3] PHPUnit..."
if ! vendor/bin/phpunit \
    --configuration web/core/phpunit.xml.dist \
    --testsuite unit,kernel \
    web/modules/custom/jaraba_* 2>/dev/null; then
  echo "  ❌ PHPUnit failed."
  exit 1
fi
 
# 2. WCAG Basic Check (if pa11y available)
echo "  [2/3] Accessibility check..."
if command -v pa11y &>/dev/null; then
  # Check main pages
  pa11y --standard WCAG2AA --reporter cli \
    http://jaraba.lndo.site 2>/dev/null || {
    echo "  ⚠️ WCAG issues found (non-blocking)."
  }
else
  echo "  ⏭️ pa11y not installed, skipping."
fi
 
# 3. Composer audit
echo "  [3/3] Security audit..."
if ! composer audit --no-dev 2>/dev/null; then
  echo "  ⚠️ Vulnerable dependencies found (non-blocking)."
fi
 
echo "✅ Pre-push checks completed."

5.4 Hook: Pre-Tool-Use
Se ejecuta antes de que Claude Code modifique archivos. Previene modificaciones peligrosas.
#!/bin/bash
# .claude/hooks/pre-tool-use.sh
# Validates tool usage before execution
 
# Get the file path from environment
FILE_PATH="$CLAUDE_TOOL_INPUT_PATH"
 
# Block modifications to critical files
PROTECTED_FILES=(
  "composer.lock"
  ".lando.yml"
  "docker-compose.yml"
  "web/sites/default/settings.php"
  "web/sites/default/services.yml"
)
 
for protected in "${PROTECTED_FILES[@]}"; do
  if [[ "$FILE_PATH" == *"$protected" ]]; then
    echo "🚫 BLOCKED: $protected is a protected file."
    echo "   Modify through proper configuration management."
    exit 1
  fi
done
 
# Block modifications outside allowed directories
ALLOWED_DIRS=(
  "web/modules/custom/"
  "web/themes/custom/"
  "tests/"
  ".claude/"
  "config/sync/"
)
 
ALLOWED=false
for dir in "${ALLOWED_DIRS[@]}"; do
  if [[ "$FILE_PATH" == *"$dir"* ]]; then
    ALLOWED=true
    break
  fi
done
 
if [ "$ALLOWED" = false ] && [ -n "$FILE_PATH" ]; then
  echo "⚠️ WARNING: Modifying file outside standard directories: $FILE_PATH"
fi
 
exit 0
 
6. Configuraciones MCP Server

Cada MCP Server conecta Claude Code con una herramienta externa. La regla de oro es: máximo 4-5 servidores activos simultáneamente. Habilitar/deshabilitar según la tarea. Cada servidor consume ventana de contexto con sus metadatos.

⚠️ BEST PRACTICE: Tratar cada MCP server como microservicio. Habilitar solo los necesarios para la tarea actual. Un agente con 2 MCPs bien configurados supera a uno con 10 mal enfocados.

6.1 Drupal MCP Server (CRÍTICO)
Permite a Claude Code interactuar directamente con Drupal: crear content types, campos, taxonomías, vistas, roles y permisos usando lenguaje natural.
// .claude/mcp/drupal-mcp-server.json
{
  "name": "drupal-jaraba",
  "description": "Drupal 11 CMS - Jaraba Impact Platform",
  "transport": "stdio",
  "command": "drush",
  "args": ["mcp-tools:serve"],
  "env": {
    "DRUPAL_ROOT": "./web",
    "LANDO_APP_NAME": "jaraba"
  },
  "tools_exposed": [
    "create_content_type",
    "add_field",
    "create_taxonomy",
    "create_view",
    "manage_permissions",
    "create_role",
    "cache_rebuild",
    "config_export"
  ],
  "authentication": {
    "mode": "disabled",
    "note": "Local development only. Enable OAuth 2.1 for staging/production."
  },
  "safety": {
    "confirm_destructive": true,
    "dry_run_default": true
  }
}

6.2 Google Stitch MCP (Prototipado)
Genera interfaces UI desde descripciones en lenguaje natural. Ideal para primera iteración de diseño de nuevas pantallas.
// .claude/mcp/stitch-mcp.json
{
  "name": "google-stitch",
  "description": "UI Generation & Design Token Extraction",
  "transport": "http",
  "url": "https://stitch.googleapis.com/mcp/v1",
  "tools_exposed": [
    "generate_screen_from_text",
    "extract_design_context",
    "fetch_screen_code",
    "fetch_screen_image",
    "create_project"
  ],
  "authentication": {
    "type": "oauth2",
    "client_id": "$STITCH_CLIENT_ID",
    "scopes": ["stitch.screens.generate", "stitch.designs.read"]
  },
  "usage_notes": "Use for rapid prototyping ONLY. Extract design tokens, then implement in jaraba_theme with CSS Custom Properties. Never use Stitch output directly in production."
}

6.3 Stripe MCP (Pagos)
// .claude/mcp/stripe-mcp.json
{
  "name": "stripe-connect",
  "description": "Stripe Connect - Payments & Subscriptions",
  "transport": "stdio",
  "command": "npx",
  "args": ["-y", "@stripe/mcp", "--tools=all", "--api-key=$STRIPE_SECRET_KEY"],
  "tools_exposed": [
    "create_customer",
    "create_subscription",
    "list_invoices",
    "get_balance_transactions",
    "create_connected_account",
    "create_payment_intent"
  ],
  "safety": {
    "confirm_write_operations": true,
    "environment": "test",
    "note": "ALWAYS use test mode keys in development"
  }
}

6.4 GitHub MCP (CI/CD)
// .claude/mcp/github-mcp.json
{
  "name": "github-jaraba",
  "description": "GitHub Repository - CI/CD Pipeline",
  "transport": "stdio",
  "command": "npx",
  "args": ["-y", "@modelcontextprotocol/server-github"],
  "env": {
    "GITHUB_PERSONAL_ACCESS_TOKEN": "$GITHUB_TOKEN"
  },
  "tools_exposed": [
    "create_pull_request",
    "list_issues",
    "create_issue",
    "get_file_contents",
    "push_files",
    "search_code"
  ],
  "repository": "jaraba-impact/platform"
}

6.5 Sentry MCP (Observabilidad)
// .claude/mcp/sentry-mcp.json
{
  "name": "sentry-jaraba",
  "description": "Error Tracking & Performance Monitoring",
  "transport": "stdio",
  "command": "npx",
  "args": ["-y", "@sentry/mcp-server"],
  "env": {
    "SENTRY_AUTH_TOKEN": "$SENTRY_TOKEN",
    "SENTRY_ORG": "jaraba-impact",
    "SENTRY_PROJECT": "platform"
  },
  "tools_exposed": [
    "list_issues",
    "get_issue_details",
    "resolve_issue",
    "get_error_events",
    "search_errors"
  ]
}

6.6 Semgrep MCP (Seguridad)
// .claude/mcp/semgrep-mcp.json
{
  "name": "semgrep-security",
  "description": "Static Analysis & Vulnerability Detection",
  "transport": "stdio",
  "command": "npx",
  "args": ["-y", "@semgrep/mcp-server"],
  "configuration": {
    "rulesets": [
      "p/drupal",
      "p/php-security",
      "p/owasp-top-ten",
      "p/xss",
      "p/sql-injection"
    ],
    "scan_paths": [
      "web/modules/custom/jaraba_*"
    ]
  }
}
 
7. Slash Commands — Workflows Repetibles

Los slash commands son workflows que el desarrollador invoca manualmente con /nombre. A diferencia de los skills (automáticos), los commands son explícitos para acciones específicas y frecuentes. Cada comando tiene su propio archivo .md en .claude/commands/.

7.1 Catálogo de Commands
Command	Propósito	Parametros	Output
/deploy-tenant	Desplegar configuración para nuevo tenant	tenant_slug, vertical, plan	Config YAML + theme setup
/create-vertical	Scaffolding completo de nuevo vertical	vertical_name, base_vertical	Módulos + APIs + Tests
/audit-wcag	Auditoría WCAG 2.1 AA completa	url_o_template	Reporte con issues y fixes
/fix-issue	Resolver issue de GitHub/Sentry	issue_id	Branch + fix + tests + PR
/generate-api	Generar API REST completa para entidad	entity_name, operations	Controllers + routes + tests
/create-eca	Crear regla ECA documentada	vertical, trigger, action	YAML + handler + test
/stripe-webhook	Crear handler de webhook Stripe	event_type	Handler idempotente + test
/tenant-theme	Generar preset de theme para tenant	industry, colors	CSS preset + preview

7.2 Ejemplo Detallado: /fix-issue
# .claude/commands/fix-issue.md
# Slash Command: /fix-issue
 
## Descripción
Resuelve un issue de GitHub o error de Sentry de forma autónoma:
lee el issue, analiza el código, implementa el fix, genera tests,
y crea un PR listo para review.
 
## Parámetros
$ISSUE_ID - ID del issue de GitHub o Sentry
 
## Instrucciones
 
1. **Analizar el Issue**
   - Si es GitHub issue: usar GitHub MCP para leer detalles
   - Si es Sentry error: usar Sentry MCP para obtener stack trace
   - Identificar archivos afectados
 
2. **Crear Branch**
   git checkout -b fix/$ISSUE_ID
 
3. **Implementar Fix**
   - Leer archivos afectados
   - Aplicar el fix mínimo necesario (no refactorizar sin necesidad)
   - Seguir convenciones del CLAUDE.md
 
4. **Generar Tests**
   - Invocar subagente tester para generar tests del fix
   - Test debe reproducir el bug ANTES del fix (test rojo)
   - Test debe pasar DESPUÉS del fix (test verde)
 
5. **Verificar Calidad**
   - Ejecutar pre-commit hooks manualmente
   - Invocar subagente reviewer para code review
 
6. **Crear PR**
   - Commit con mensaje: "fix(#$ISSUE_ID): {descripción concisa}"
   - Push branch
   - Crear PR vía GitHub MCP con:
     - Título: "Fix #$ISSUE_ID: {título del issue}"
     - Body: Qué causaba el bug, cómo se arregló, tests añadidos
     - Labels: bugfix, auto-generated
     - Reviewers: asignar según CODEOWNERS

7.3 Ejemplo Detallado: /create-vertical
# .claude/commands/create-vertical.md
# Slash Command: /create-vertical
 
## Descripción
Genera el scaffolding completo para un nuevo vertical del ecosistema,
siguiendo el patrón Base+Extension con AgroConecta como blueprint.
 
## Parámetros
$VERTICAL_NAME - Nombre del vertical (ej: turismo, salud)
$BASE_VERTICAL - Vertical base a extender (ej: agroconecta, serviciosconecta)
 
## Instrucciones
 
1. **Crear Módulos**
   Generar estructura base:
   web/modules/custom/jaraba_{vertical}_core/
   web/modules/custom/jaraba_{vertical}_catalog/
   web/modules/custom/jaraba_{vertical}_orders/
   web/modules/custom/jaraba_{vertical}_portal/
   web/modules/custom/jaraba_{vertical}_dashboard/
 
2. **Copiar y Adaptar Entidades del Base**
   - Leer entidades del $BASE_VERTICAL
   - Crear entidades adaptadas para el nuevo vertical
   - Mantener campos compartidos, añadir específicos
 
3. **Generar APIs REST**
   Invocar /generate-api para cada entidad principal
 
4. **Crear Preset de Theming**
   Invocar /tenant-theme con industria del nuevo vertical
 
5. **Crear Skills**
   Generar SKILL.md específico en .claude/skills/jaraba-{vertical}/
 
6. **Generar Tests Base**
   Invocar subagente tester para crear test suite inicial
 
7. **Documentar**
   Crear documento de especificación siguiendo formato del ecosistema
 
8. Gobernanza de Seguridad MCP

Con el EU AI Act (obligaciones principales desde agosto 2026, penalizaciones hasta 7% de facturación global anual), y los requisitos SOC2/ISO 27001/ENS del ecosistema Jaraba, la gobernanza de seguridad MCP no es opcional. Este framework establece las políticas y procedimientos para uso seguro de agentes IA en el pipeline de desarrollo.

🔴 OBLIGATORIO: Implementar gobernanza MCP AHORA evita deuda técnica que sería 3-5x más costosa de remediar después de una auditoría o incidente.

8.1 OAuth 2.1 para Todo MCP Server en Producción
Cada MCP Server que se despliegue en staging o producción DEBE usar OAuth 2.1 con scopes granulares. Las API keys básicas solo se permiten en desarrollo local.
# Configuración Drupal MCP Server con OAuth 2.1
# Simple OAuth 2.1 module (misma autoría que MCP Server)
 
# 1. Crear OAuth Client
drush simple-oauth:create-client \
  --label="Claude Code Agent" \
  --grant-types="authorization_code,client_credentials" \
  --scopes="mcp:read,mcp:write:content,mcp:write:config" \
  --redirect-uri="http://localhost:3000/callback"
 
# 2. Scopes disponibles para MCP
#   mcp:read                 - Leer contenido y configuración
#   mcp:write:content        - Crear/editar contenido
#   mcp:write:config         - Modificar configuración Drupal
#   mcp:admin                - Operaciones administrativas (restringido)
 
# 3. Flujo en producción:
#   - Client Credentials para agentes automatizados (CI/CD)
#   - Authorization Code para desarrolladores interactivos

8.2 Principio de Mínimo Privilegio
MCP Server	Modo Inicial	Escalar A	Cuándo Escalar
Drupal MCP	Read-only (mcp:read)	Write content	Cuando tarea requiera crear/editar contenido
Stripe MCP	Test mode, read-only	Test mode, write	Solo para crear fixtures de testing
GitHub MCP	Read repos + issues	Create PR + push	Solo para /fix-issue y /deploy-tenant
Sentry MCP	Read errors	Resolve issues	Después de verificar fix exitoso
Semgrep MCP	Scan only	N/A (siempre read-only)	Nunca necesita escritura
Stitch MCP	Generate + read	N/A	No tiene operaciones destructivas

8.3 Audit Logging para Compliance
TODA interacción MCP genera un registro de auditoría. Esto es requisito para SOC2 Trust Service Criteria (Security) e ISO 27001 Control A.12 (Logging & Monitoring).
// Estructura del audit log para MCP
{
  "timestamp": "2026-02-28T10:30:00Z",
  "event_type": "mcp_tool_call",
  "agent_id": "claude-code-session-abc123",
  "mcp_server": "drupal-jaraba",
  "tool_name": "create_content_type",
  "parameters": {
    "machine_name": "jaraba_product",
    "label": "Product",
    "fields": ["title", "body", "price"]
  },
  "result": "success",
  "execution_time_ms": 1250,
  "user_id": "developer@jaraba.com",
  "tenant_context": null,
  "ip_address": "10.0.0.1",
  "session_id": "sess_xyz789"
}

8.4 Detección de PII y Datos Sensibles
Configurar alertas cuando agentes IA accedan a datos sensibles. Mapear controles MCP a frameworks de compliance existentes.
Dato Sensible	Acción Requerida	Framework
DNI/NIF	Alertar + registrar acceso	GDPR Art. 9, ENS mp.info.1
Datos bancarios (IBAN)	Alertar + nunca loguear completo	PCI-DSS, GDPR
Datos de salud	Bloquear acceso IA + alertar DPO	GDPR Art. 9, ISO 27001 A.18
Contraseñas/tokens	NUNCA exponer a agente IA	SOC2 CC6.1, ISO 27001 A.10
Datos menores	Bloquear + alertar inmediato	GDPR Art. 8, COPPA

8.5 Secrets Management
# NUNCA hardcodear secrets en configuraciones MCP
# Usar variables de entorno que se resuelven en runtime
 
# .env.local (NO en Git, en .gitignore)
STRIPE_SECRET_KEY=sk_test_xxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxx
GITHUB_TOKEN=ghp_xxxxxxxxxxxx
SENTRY_AUTH_TOKEN=sntrys_xxxxxxxxxxxx
STITCH_CLIENT_ID=xxxxxxxxxxxx
DRUPAL_OAUTH_CLIENT_ID=xxxxxxxxxxxx
DRUPAL_OAUTH_CLIENT_SECRET=xxxxxxxxxxxx
 
# En producción: usar Drupal Key module + secrets manager
# Las configuraciones MCP referencian $VARIABLE_NAME
# El runtime resuelve desde .env.local o secrets manager
 
9. Plugin jaraba-dev — Paquete Distribuible

El plugin empaqueta todo el pipeline (CLAUDE.md, skills, subagentes, hooks, MCP configs, slash commands) en un paquete distribuible que cualquier miembro del equipo EDI puede instalar en un solo comando. Garantiza que todos los desarrolladores trabajen con la misma configuración.

9.1 Instalación
# Un solo comando para configurar todo el pipeline
claude plugin install jaraba-dev
 
# O manualmente, clonar el repositorio de configuración:
git clone https://github.com/jaraba-impact/claude-dev-config .claude/
cp .claude/CLAUDE.md ./CLAUDE.md

9.2 Contenido del Plugin
Componente	Archivos	Actualización
CLAUDE.md	1 archivo	Manual cuando cambien convenciones
Skills	7 carpetas con SKILL.md	Cuando se añadan/modifiquen verticales
Subagentes	3 archivos .md	Cuando se refinen criterios de review
Hooks	5 scripts .sh	Cuando se añadan quality gates
MCP Configs	6 archivos .json	Cuando se integren nuevos servicios
Slash Commands	8 archivos .md	Cuando se identifiquen nuevos workflows
settings.json	1 archivo	Cuando cambien permisos o hooks

9.3 Versionado del Plugin
El plugin sigue semver. Los cambios en CLAUDE.md o skills que afecten al comportamiento del agente son MAJOR. Los nuevos commands o MCP configs son MINOR. Fixes son PATCH.
# package.json del plugin
{
  "name": "jaraba-dev",
  "version": "1.0.0",
  "description": "Claude Code development pipeline for Jaraba Impact Platform",
  "files": [
    "CLAUDE.md",
    ".claude/"
  ],
  "scripts": {
    "install": "cp -r .claude/ ../../.claude/ && cp CLAUDE.md ../../CLAUDE.md",
    "update": "npm update jaraba-dev && npm run install",
    "validate": "bash .claude/hooks/pre-commit.sh"
  }
}
 
10. Pipeline Orquestado End-to-End

Este capítulo describe el flujo completo de trabajo, desde que el desarrollador solicita una tarea hasta que el código está en producción. Cada fase activa diferentes componentes del pipeline de forma automática.

10.1 Flujo Completo de una Tarea
┌─────────────────────────────────────────────────────────────────┐
│  DEVELOPER: "Crea el catálogo de productos para TurismoConecta" │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                    ┌──────▼──────┐
                    │ CLAUDE CODE │
                    │ Lee CLAUDE.md│
                    └──────┬──────┘
                           │
              ┌────────────▼────────────────┐
              │ SKILL RESOLUTION            │
              │ Activa automáticamente:      │
              │  • jaraba-vertical-blueprint │
              │  • jaraba-drupal-theming     │
              │  • jaraba-multi-tenant       │
              └────────────┬────────────────┘
                           │
         ┌─────────────────▼─────────────────┐
         │ FASE 1: SCAFFOLDING               │
         │ Usar /create-vertical turismo agro │
         │ → Genera módulos, entidades, APIs  │
         │ → Drupal MCP: crear content types  │
         └─────────────────┬─────────────────┘
                           │
         ┌─────────────────▼─────────────────┐
         │ FASE 2: IMPLEMENTACIÓN            │
         │ → Claude Code escribe código       │
         │ → Hooks pre-edit: valida paths     │
         │ → Hooks post-edit: lint automático │
         └─────────────────┬─────────────────┘
                           │
         ┌─────────────────▼─────────────────┐
         │ FASE 3: REVIEW AUTOMATIZADO       │
         │ → Subagente Reviewer analiza       │
         │ → Subagente Tester genera tests    │
         │ → Subagente Security audita        │
         └─────────────────┬─────────────────┘
                           │
         ┌─────────────────▼─────────────────┐
         │ FASE 4: COMMIT & PUSH             │
         │ → Hook pre-commit: PHPCS+PHPStan  │
         │ → Hook pre-push: PHPUnit suite     │
         │ → GitHub MCP: crear PR descriptivo │
         └─────────────────┬─────────────────┘
                           │
         ┌─────────────────▼─────────────────┐
         │ FASE 5: CI/CD (GitHub Actions)    │
         │ → Pipeline doc 132 ejecuta        │
         │ → Sentry MCP: monitoreo post-deploy│
         └─────────────────┬─────────────────┘
                           │
              ┌────────────▼────────────┐
              │ ✅ CÓDIGO EN PRODUCCIÓN  │
              └─────────────────────────┘

10.2 Matriz de Activación por Tipo de Tarea
Tipo de Tarea	Skills Activados	MCPs Usados	Subagentes	Commands
Nueva feature UI	theming, multi-tenant	Drupal, Stitch	Reviewer	—
Fix de bug	relevante al vertical	Sentry, GitHub	Reviewer, Tester	/fix-issue
Nuevo vertical	blueprint, theming, multi-tenant	Drupal, Stitch	Los 3	/create-vertical
Integración Stripe	stripe-connect	Stripe, Drupal	Reviewer, Security	/stripe-webhook
Formación SEPE	sepe-compliance	Drupal	Reviewer, Tester	—
Copilot IA	ai-grounding, multi-tenant	Drupal	Reviewer, Security	—
Regla ECA	eca-workflows, vertical	Drupal	Reviewer, Tester	/create-eca
API REST	multi-tenant, vertical	Drupal, GitHub	Reviewer, Tester	/generate-api
 
11. Roadmap de Implementación

11.1 Fases y Cronograma
Fase	Semanas	Entregables	Coste	Impacto
1. Fundación	1-2	CLAUDE.md + 3 Skills iniciales + Hooks	0€ (solo tiempo)	Base contextual para todo
2. Skills Completos	3-4	7 Skills + 3 Subagentes + Commands	0€	Expertise encapsulada
3. MCP Desarrollo	5-6	Drupal MCP + Stitch MCP en Lando	0€ (OSS)	Prototipado 10x más rápido
4. MCP Producción	7-10	Stripe MCP + GitHub MCP + OAuth 2.1	~500€ setup	Pipeline autónomo
5. Observabilidad	11-14	Sentry MCP + Semgrep MCP + Audit logs	~200€/mes	Seguridad continua
6. Plugin	15-16	jaraba-dev empaquetado + docs	0€	Onboarding 1 comando

11.2 Estimación de Esfuerzo Detallada
Componente	Horas	Prioridad	Dependencias
CLAUDE.md (creación y refinamiento)	8-12h	P0 — INMEDIATA	Ninguna
3 Skills iniciales (theming, multi-tenant, ECA)	16-24h	P0 — INMEDIATA	CLAUDE.md
4 Skills restantes (Stripe, SEPE, AI, Blueprint)	20-30h	P1 — Semana 3	Skills iniciales
3 Subagentes (Reviewer, Tester, Security)	12-18h	P1 — Semana 3	CLAUDE.md
5 Hooks (pre-commit, pre-push, pre-tool-use, etc.)	8-12h	P0 — INMEDIATA	Ninguna
8 Slash Commands	16-24h	P2 — Semana 5	Skills + Subagentes
Drupal MCP Server config + OAuth 2.1	20-30h	P1 — Semana 5	Drupal MCP module instalado
Stitch MCP config + workflow prototipado	8-12h	P2 — Semana 5	Cuenta Google Labs
Stripe MCP config + test mode	12-16h	P1 — Semana 7	Stripe Connect setup
GitHub MCP + CI/CD integration	12-16h	P2 — Semana 9	GitHub Actions pipeline
Sentry MCP config	8-10h	P3 — Semana 11	Sentry project setup
Semgrep MCP config + rulesets	8-10h	P3 — Semana 11	Semgrep account
Audit logging framework	16-24h	P1 — Semana 7	Base de datos, Redis
Plugin empaquetado	8-12h	P3 — Semana 15	Todo lo anterior
TOTAL ESTIMADO	172-250h	—	—

11.3 ROI Proyectado
Métrica	Inversión	Retorno (Año 1)	ROI
Tiempo desarrollo features	200h setup pipeline	~800h ahorradas en features	4x
Onboarding desarrolladores	Incluido en setup	De 3 semanas a 2 días por dev	10x por nuevo dev
Bugs en producción	Hooks + Review automático	~60% reducción bugs post-deploy	Incalculable (reputación)
Compliance (SOC2/ISO)	Audit logging + Security MCP	Pre-requisito para enterprise	Desbloquea €100K+ MRR
Nuevos verticales	Blueprint skill	De 500h a 200h por vertical	2.5x por vertical
 
12. Conclusión

Este documento transforma la investigación MCP del Ecosistema Jaraba en especificaciones técnicas ejecutables. Cada componente descrito aquí está diseñado para implementación autónoma por Claude Code, siguiendo la filosofía Sin Humo: máximo impacto con mínima complejidad innecesaria.

12.1 Los 5 Mandamientos del Pipeline

1. CLAUDE.md es la inversión más rentable. 8 horas de trabajo generan un contexto permanente que ahorra cientos de horas en cada tarea futura. Empezar aquí, hoy.

2. Skills > MCPs. Un agente bien instruido con 2 MCP servers supera a uno sin contexto con 15. La expertise encapsulada en skills es el verdadero multiplicador.

3. Writer/Reviewer elimina el punto ciego. Dos instancias Claude, una que escribe y otra que revisa, replican la dinámica de equipos humanos senior y eliminan el autocompletado con esteroides.

4. Hooks son non-negotiable. Los hooks son la última línea de defensa. El agente puede ser creativo en cómo resuelve un problema, pero NUNCA puede saltarse las validaciones de calidad.

5. Seguridad desde el día uno. Con EU AI Act en vigor desde agosto 2026 y SOC2/ISO 27001 como requisitos enterprise, implementar gobernanza MCP ahora evita deuda técnica 3-5x más costosa después.

✅ ACCIÓN INMEDIATA: Crear CLAUDE.md + 3 Skills + Hooks pre-commit. Coste: 0€, ~32h de trabajo. Resultado: fundación completa para todo el pipeline agente-orquestado del Ecosistema Jaraba.

— Fin del Documento 178 —

Ecosistema Jaraba · Filosofía Sin Humo · EDI Google Antigravity
