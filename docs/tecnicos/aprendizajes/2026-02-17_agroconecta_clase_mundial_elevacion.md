# Aprendizaje #91 — Plan Elevacion AgroConecta Clase Mundial v1

| Campo | Valor |
|-------|-------|
| **Fecha** | 2026-02-17 |
| **Modulo** | `jaraba_agroconecta_core` + `ecosistema_jaraba_core` (servicios transversales AgroConecta) |
| **Contexto** | Implementacion completa del Plan Elevacion AgroConecta Clase Mundial v1 — 14 fases (FASE 0-13) + elevacion premium de 11 templates Page Builder. Patron reutilizable de 14 fases aplicado por 5a vez (tras Empleabilidad, Emprendimiento, Andalucia+ei, JarabaLex). Ejecutado con 18 agentes paralelos para maximizar throughput. |
| **Resultado** | 14/14 fases completadas, 11 templates PB premium, 95 rgba() eliminados, 52 ficheros QA (0 errores), 12 FreemiumVerticalLimit configs, 6 servicios nuevos en ecosistema_jaraba_core, 1 servicio nuevo en jaraba_agroconecta_core, 6 MJML email templates, 4 Funnel Definitions, 8 UpgradeTrigger types, 16 SCSS files migrados a color-mix() |
| **Aprendizaje anterior** | #90 — Implementacion FASE A1 jaraba_legal_cases |

---

## Patron Principal

**Elevacion vertical a Clase Mundial con agentes paralelos** — El patron de 14 fases es suficientemente maduro para ejecutarse de forma masivamente paralela: las fases que tocan ficheros diferentes pueden lanzarse simultaneamente, mientras que las fases con dependencias de ficheros compartidos (services.yml) se coordinan secuencialmente. El patron Page Builder premium es un complemento natural que se ejecuta en paralelo con las 14 fases core.

---

## Aprendizajes Clave

### 1. Coordinacion de agentes paralelos en elevacion vertical

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | 14 fases de elevacion + 11 templates PB requieren modificar ~60 ficheros. Ejecutar secuencialmente es lento. |
| **Aprendizaje** | Lanzar hasta 10+ agentes paralelos agrupando fases por independencia de ficheros: FASE 0 (feature gate + configs) sola, FASEs 1-2 juntas (trigger types + copilot bridge), FASEs 3-4 juntas (body classes + zero-region), FASEs 5-6 juntas (SCSS + design tokens), FASEs 7-8 juntas (email + cross-vertical), FASEs 9-10-11 juntas (journey + health + experiment), FASE 12 sola (avatar + funnels). PB templates en 3 lotes paralelos (4+4+3). Ficheros compartidos (services.yml) se editan secuencialmente tras completar agentes. |
| **Regla** | **PARALLEL-ELEV-001**: Al ejecutar elevacion vertical, agrupar fases por independencia de ficheros y lanzar agentes paralelos. Ficheros compartidos (services.yml, .module, .install) se editan en el hilo principal tras completar los agentes que los necesitan. QA (FASE 13) siempre al final con agentes de verificacion paralelos (PHP lint + template audit). |

### 2. Migracion SCSS rgba() a color-mix() a escala

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | AgroConecta tenia 95 ocurrencias de `rgba()` distribuidas en 16 ficheros SCSS, todas debian migrar a `color-mix(in srgb, ...)` segun directriz SCSS. |
| **Aprendizaje** | Mapear colores rgba a tokens CSS antes de empezar: `rgba(0,0,0,X)` → `var(--ej-color-text)`, `rgba(85,107,47,X)` → `var(--ej-color-agro)`, `rgba(255,255,255,X)` → `#fff`, etc. Patron uniforme: `color-mix(in srgb, {token} {percentage}%, transparent)`. Convertir opacidad decimal a porcentaje (0.12 → 12%). Verificar con grep post-migracion que 0 ocurrencias quedan. |
| **Regla** | **SCSS-COLORMIX-001**: Para migrar rgba() a color-mix() a escala: (1) crear tabla de mapeo rgba→token CSS antes de editar, (2) aplicar patron `color-mix(in srgb, {token} {pct}%, transparent)`, (3) verificar con grep que 0 rgba() quedan, (4) no olvidar box-shadow y border que tambien usan rgba. |

### 3. Patron Page Builder Premium: jaraba_icon + data-attributes + schema.org

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | Los 11 templates Twig de AgroConecta usaban emojis HTML entities (&#127806;, &#128205;) y carecian de animaciones, microdata y capacidades premium. |
| **Aprendizaje** | Patron premium PB completo: (1) CSS classes `jaraba-block jaraba-block--premium`, (2) `data-effect="fade-up"` para animaciones, (3) `jaraba_icon('category', 'name', { variant, size, color })` reemplazando todos los emojis, (4) staggered delays `data-delay="{{ loop.index0 * 100 }}"`, (5) schema.org microdata donde aplique (FAQ → FAQPage JSON-LD, map → LocalBusiness, social_proof → AggregateRating), (6) YML config: `is_premium: true`, `animation: fade-up`, `plans_required: [starter, professional, enterprise]`. |
| **Regla** | **PB-PREMIUM-001**: Al elevar templates PB a premium: (a) jaraba-block jaraba-block--premium en section, (b) data-effect="fade-up" + staggered data-delay en items iterados, (c) jaraba_icon() en lugar de emojis, (d) schema.org JSON-LD donde aplique (FAQ, LocalBusiness, AggregateRating), (e) YML actualizado con is_premium:true + animation + plans_required corregido. |

### 4. FeatureGate con 3 tipos de features: cumulative, monthly, binary

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | AgroConecta tiene features con limites muy diferentes: productos (acumulativos, se cuentan desde entidades), pedidos/copilot (mensuales, se trackean por periodo YYYY-MM), analytics/partner_hub (binarios, on/off por plan). |
| **Aprendizaje** | Clasificar features en 3 categorias con logica `check()` diferente: CUMULATIVE (query count entidades existentes vs limite), MONTHLY (query tabla usage por periodo actual vs limite), BINARY (limite 0=blocked, -1=unlimited, sin conteo). El metodo `recordUsage()` solo aplica a MONTHLY. El metodo `checkAndFire()` combina check + UpgradeTrigger.fire() para flujo completo. Commission rates dinamicas por plan via `getCommissionRate()`. |
| **Regla** | **FEATUREGATE-TYPES-001**: FeatureGateService debe clasificar features en CUMULATIVE (count entities), MONTHLY (track usage table by YYYY-MM), o BINARY (plan check only). Cada tipo tiene logica check() diferente. recordUsage() solo para MONTHLY. getCommissionRate() con default rates por plan (free=15%, starter=10%, pro=5%). |

### 5. QA con agentes paralelos: PHP lint + template verification

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | FASE 13 QA debe verificar ~52 ficheros (12 PHP + 40 YAML + 11 Twig) creados/modificados por 18 agentes diferentes. |
| **Aprendizaje** | Lanzar 2 agentes QA paralelos: (1) PHP lint agent que verifica todos los .php y .yml con `php -l` y estructura YAML, (2) Template verification agent que grep patrones prohibidos (emojis HTML entities, rgba() en SCSS, is_premium:false en YML) y verifica patrones obligatorios (jaraba-block--premium en templates, service registrations). Cross-verificar results manualmente para descartar falsos positivos (grep multi-linea puede dar resultados enganosos). |
| **Regla** | **QA-PARALLEL-001**: QA integral de elevacion vertical: 2 agentes paralelos (PHP lint + template/pattern audit). Verificar: 0 emojis HTML entities, 0 rgba(), 0 is_premium:false, todos los servicios registrados en .services.yml. Descartar falsos positivos de grep multi-linea verificando manualmente. |

### 6. Elevacion PB en lotes paralelos con YML actualizacion centralizada

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | 11 templates PB a elevar: demasiados para un solo agente, pero cada template es independiente. Los 11 YML configs deben actualizarse con los mismos campos. |
| **Aprendizaje** | Dividir en 3 lotes paralelos: (4 templates core: hero+features+stats+content), (4 templates conversion: testimonials+pricing+faq+cta), (3 templates discovery: gallery+map+social_proof + todos los YML). El tercer lote recibe la responsabilidad de actualizar los 11 YMLs porque es el mas ligero en templates. Patron fields_schema en YML: arrays con items.properties para campos de listas (gallery items, pricing plans, FAQ entries). |
| **Regla** | **PB-BATCH-001**: Al elevar N templates PB, dividir en lotes de 3-4 templates por agente. Asignar actualizacion de YML configs al lote mas ligero para balancear carga. Cada YML debe tener is_premium:true, animation:fade-up, plans_required corregido a [starter, professional, enterprise], y fields_schema con array schemas. |

---

## Metricas de Implementacion

| Metrica | Valor |
|---------|-------|
| Ficheros PHP nuevos | 7 (FeatureGate, CopilotBridge, EmailSequence, CrossVertical, Journey, Health, Experiment) |
| Ficheros PHP modificados | 5 (ProductAgroService, OrderService, StripePaymentService, ProducerCopilotService, UpgradeTriggerService, BaseAgent) |
| Ficheros YAML nuevos | 20 (12 FreemiumVerticalLimit + 4 Funnels + 1 DesignToken + 3 others) |
| Ficheros SCSS modificados | 16 (95 rgba→color-mix) |
| Templates Twig elevados | 11 (PB premium) + 1 nuevo (page--agroconecta.html.twig) |
| MJML templates | 6 (SEQ_AGRO_001-006) |
| YML configs PB | 11 actualizados (is_premium, animation, plans_required, fields_schema) |
| Services registrados | 7 (6 ecosistema_jaraba_core + 1 jaraba_agroconecta_core) |
| UpgradeTrigger types | 8 nuevos |
| FreemiumVerticalLimit configs | 12 nuevos (4 features × 3 planes) |
| Funnel definitions | 4 nuevos |
| Agentes paralelos ejecutados | 18 |
| QA: ficheros verificados | 52 (0 errores) |
| Duracion total estimada | ~25 minutos (ejecucion paralela) |

---

## Reglas Nuevas (Resumen)

| ID | Regla | Prioridad |
|----|-------|-----------|
| PARALLEL-ELEV-001 | Agentes paralelos agrupados por independencia de ficheros | P2 |
| SCSS-COLORMIX-001 | Migracion rgba→color-mix con tabla mapeo previa | P1 |
| PB-PREMIUM-001 | Patron premium PB: jaraba_icon + data-effect + schema.org + YML | P1 |
| FEATUREGATE-TYPES-001 | 3 tipos features: cumulative/monthly/binary con logica diferente | P1 |
| QA-PARALLEL-001 | QA con 2 agentes paralelos (lint + pattern audit) | P2 |
| PB-BATCH-001 | Lotes paralelos de 3-4 templates PB + YML en lote ligero | P2 |
