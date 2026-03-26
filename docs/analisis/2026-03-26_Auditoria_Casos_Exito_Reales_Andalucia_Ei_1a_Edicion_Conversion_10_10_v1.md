# Auditoria Profunda: Casos de Exito Reales Andalucia +ei 1a Edicion — Conversion 10/10

| Campo | Valor |
|-------|-------|
| Version | 1.0.0 |
| Fecha | 2026-03-26 |
| Autor | Claude (Auditor) + Pepe Jaraba (Direccion) |
| Scope | jaraba_success_cases, ecosistema_jaraba_theme, 8 modulos verticales, docs/assets/casos-de-exito, F:\DATOS casos reales |
| Directrices raiz | SUCCESS-CASES-001, CASE-STUDY-PATTERN-001, LANDING-CONVERSION-SCORE-001, MARKETING-TRUTH-001 |
| Score actual template | 15/15 (PASS) |
| Score actual CONTENIDO | 2/10 (CRITICO) |

---

## 1. Resumen Ejecutivo

La arquitectura tecnica de casos de exito del SaaS alcanza **15/15 en LANDING-CONVERSION-SCORE-001** — template unificado, 15 parciales, Schema.org, sticky CTA, tracking, accesibilidad. Sin embargo, existe una **brecha critica** entre la calidad del contenedor (10/10) y la calidad del contenido (2/10):

- **18 participantes reales** de la 1a Edicion de Andalucia +ei documentados en F:\DATOS con entrevistas, videos, analisis estrategicos, planes de negocio
- **Solo 3 briefs** creados en `docs/assets/casos-de-exito/` (Marcela Calabia, Angel Martinez, Luis Miguel Criado), todos **incompletos**: sin foto profesional, sin testimonial validado, sin metricas cuantificables, sin consentimiento legal
- **9 SuccessCase entities ficticias** en el seed script con personajes inventados (Elena Martinez, Rosa Fernandez, Carlos Etxebarria, etc.) que NO corresponden a participantes reales
- **8 legacy controllers con datos hardcodeados** aun presentes en el codigo (WARN en validator)
- **MARKETING-TRUTH-001 VIOLADA**: el SaaS presenta "casos de exito" que son creaciones literarias, no testimonios verificables de personas reales

**Impacto en conversion**: Un visitante que investiga la veracidad de los testimonios (busqueda LinkedIn, Google del nombre) descubre que los protagonistas no existen. Esto **destruye la confianza** y convierte un template 15/15 en conversion 0.

---

## 2. Inventario Completo: Participantes Reales Andalucia +ei 1a Edicion

Fuente: `F:\DATOS\JJM\Marketing-Comercial\Marca Personal\Modelo de Negocio 2025\10. Casos de Exito`

### 2.1 Tabla de Participantes (18 personas)

| # | Nombre | Perfil/Negocio | Vertical SaaS | Documentacion disponible | Brief en repo | Estado |
|---|--------|---------------|---------------|-------------------------|---------------|--------|
| 1 | Adrian Capatina Tudor | NovAVid Media Network (produccion audiovisual/branding) | emprendimiento | Video, transcripcion, analisis estrategico, contrato, portfolio, redes | NO | Sin brief |
| 2 | Ana Ojeda | (pendiente identificar sector) | empleabilidad/emprendimiento | Investigacion huella publica, analisis estrategico, video, portfolio | NO | Sin brief |
| 3 | Brian Vela | (pendiente identificar sector) | empleabilidad/emprendimiento | Caso escrito (142 KB) | NO | Sin brief |
| 4 | Cristina Martin Pereira | (pendiente identificar sector) | empleabilidad/emprendimiento | Caso, diseño caso, video (312 MB), analisis, LinkedIn | NO | Sin brief |
| 5 | Elena Maria Jimenez | Asistente administrativa / cajera | empleabilidad | 3 perfiles profesionales, mapeo oportunidades, CV PDF, guia LinkedIn | NO | Sin brief |
| 6 | Inmaculada Serrano | (negocio propio, investigacion publica) | emprendimiento | Huella publica, plan estrategico, marca personal, video (400 MB) | NO | Sin brief |
| 7 | Juan Raul Almanza | Limpieza aviacion privada + Domicilio virtual internacional | emprendimiento/serviciosconecta | Video (591 MB), 2 descripciones servicios ODT, portfolio | NO | Sin brief |
| 8 | **Luis Miguel Criado** | Masajista autonomo | empleabilidad | Caso escrito (5.8 MB), fotos, WhatsApp | **SI** | **Incompleto** |
| 9 | Maia Tolomeo | (pendiente identificar sector) | empleabilidad/emprendimiento | Video (43 MB), fotos | NO | Sin brief |
| 10 | **Marcela Calabia** | Comunicacion Estrategica & Coach Resiliencia | emprendimiento | Caso, modelo negocio, estrategia captacion, perfil Preply, agenda, LinkedIn | **SI** | **Incompleto** |
| 11 | Margarita Rivera | (web + Instagram + LinkedIn) | emprendimiento | Video (382 MB), resumen estrategico, redes | NO | Sin brief |
| 12 | Maria Fernandez | (pendiente identificar sector) | empleabilidad/emprendimiento | Analisis estrategico, video (433 MB) | NO | Sin brief |
| 13 | Maria Gonzalez | (solo audios WhatsApp) | empleabilidad | 3 audios WhatsApp | NO | Datos insuficientes |
| 14 | Matilde Esteban | Eresvida (emprendimiento) | emprendimiento | Analisis estrategico, entrevista (6.6 MB), analisis 12 meses, video (524 MB) | NO | Sin brief |
| 15 | Melania Luque | (solo audio + fotos) | empleabilidad | Audio WhatsApp, fotos | NO | Datos insuficientes |
| 16 | Natalia Hidalgo | Emprendimiento (evaluacion emprendedora) | emprendimiento | Analisis completo y plan accion (5.8 MB), resumen, video (560 MB) | NO | Sin brief |
| 17 | Remedios Estevez | Autora y Mentora Autonoma (marca personal) | emprendimiento | Marca personal (5.8 MB), CV PDF | NO | Sin brief |
| 18 | **Angel Martinez** | Camino Viejo — Gastrobiking rural Sierra Morena | emprendimiento/agroconecta | Caso (5.8 MB), modelo negocio, plan estrategico, estudios mercado, videos, YouTube, web | **SI** | **Incompleto** |

### 2.2 Distribucion por Vertical SaaS Potencial

| Vertical | Candidatos reales | Mejor candidato | Justificacion |
|----------|-------------------|-----------------|---------------|
| **empleabilidad** | Elena Maria Jimenez, Luis Miguel Criado, Ana Ojeda | Luis Miguel Criado | Narrativa clasica insercion laboral: de desempleado a autonomo |
| **emprendimiento** | Marcela Calabia, Angel Martinez, Adrian Capatina Tudor, Natalia Hidalgo, Matilde Esteban, Inmaculada Serrano, Remedios Estevez | Marcela Calabia | Coach comunicacion con modelo negocio documentado. Angel Martinez tambien excelente (gastrobiking, producto tangible) |
| **comercioconecta** | Angel Martinez (bicis + productos locales) | Angel Martinez | Camino Viejo tiene producto fisico + experiencia turistica |
| **agroconecta** | Angel Martinez (ruta por parques naturales + productos locales) | Angel Martinez | Conexion directa con productos agrarios de Sierra Morena |
| **serviciosconecta** | Juan Raul Almanza (domicilio virtual + limpieza aviacion), Luis Miguel Criado (masajista) | Luis Miguel Criado | Servicio profesional autonomo con agenda |
| **formacion** | Marcela Calabia (coach/formadora), Remedios Estevez (mentora) | Marcela Calabia | Potencial de cursos online sobre comunicacion |
| **jarabalex** | Ninguno directo | — | Ningun participante del programa es abogado |
| **content_hub** | Adrian Capatina Tudor (produccion contenido audiovisual) | Adrian Capatina Tudor | NovAVid = creacion de contenido profesional |
| **andalucia_ei** | PED S.L. (dogfooding) | PED S.L. | Caso real: 1a edicion gestionada con Excel, 2a con el SaaS |

### 2.3 Estado de los 3 Briefs Existentes

| Campo obligatorio | Marcela | Angel | Luis Miguel |
|-------------------|---------|-------|-------------|
| Nombre completo | SI | SI | SI |
| Profesion | SI | SI | SI |
| Empresa/marca | NO (falta confirmar) | SI (Camino Viejo) | NO (autonomo) |
| Ubicacion detallada | PENDIENTE | PENDIENTE | PENDIENTE |
| Narrativa Reto | Borrador | Borrador | Borrador |
| Narrativa Solucion | Borrador | Borrador | Borrador |
| Narrativa Resultado | Borrador | Borrador | Borrador |
| Foto profesional | **NO** | **NO** | **NO** |
| Testimonial corto | **NO** | **NO** | **NO** |
| Testimonial largo | **NO** | **NO** | **NO** |
| Metricas cuantificables | **NO** | **NO** | **NO** |
| Consentimiento RGPD | **NO** | **NO** | **NO** |
| Video testimonial | NO | NO | NO |
| Logo empresa | NO | NO (tiene web) | N/A |

**Completitud de briefs: ~30% (solo datos basicos, sin assets criticos)**

---

## 3. Analisis Gap: Ficcion vs Realidad

### 3.1 SuccessCase Entities Actuales (seed script) — FICTICIAS

| Vertical | Protagonista FICTICIO | Existe en Andalucia +ei? | Verificable? |
|----------|----------------------|--------------------------|--------------|
| agroconecta | Cooperativa Sierra de Cazorla / Antonio Morales | **NO** | NO — buscar "Cooperativa Sierra Cazorla AgroConecta" en Google = 0 resultados |
| jarabalex | Elena Martinez / Martinez & Asociados | **NO** | NO — despacho inventado |
| empleabilidad | Rosa Fernandez, Torremolinos | **NO** | NO — persona inventada |
| emprendimiento | Carlos Etxebarria, Bilbao | **NO** | NO — persona inventada |
| comercioconecta | Carmen Ruiz / Boutique La Mariposa, Sevilla | **NO** | NO — negocio inventado |
| serviciosconecta | Carmen Navarro, fisioterapeuta, Madrid | **NO** | NO — persona inventada |
| andalucia_ei | PED S.L. | **SI** | SI — caso de dogfooding real |
| formacion | Maria Lopez, instructora marketing | **NO** | NO — persona inventada |
| content_hub | Bodega Montilla, Cordoba | **NO** | NO — bodega inventada |

**Resultado: 8 de 9 casos son ficticios. Solo PED S.L. (andalucia_ei) es real.**

### 3.2 Violacion MARKETING-TRUTH-001

La regla dice: *"Claims marketing en templates DEBEN coincidir con billing real. 14 dias trial Stripe, NO 'gratis para siempre'."*

Extension logica: **testimonios publicados como "casos de exito" DEBEN ser de personas reales con consentimiento verificable.** Un "caso de exito" ficticio no es marketing — es ficcion. Esto es:

1. **Riesgo legal**: Publicar testimonios inventados como reales viola la Ley General de Publicidad (art. 3, publicidad enganosa) y el Reglamento (UE) 2022/2065 (DSA, art. 26, transparencia en publicidad)
2. **Riesgo reputacional**: Cualquier due diligence de un inversor, partner institucional o la propia Junta de Andalucia (financiadora del programa) descubriria que los testimonios son inventados
3. **Riesgo de conversion**: Usuarios sofisticados buscan los nombres en LinkedIn/Google. No encontrar resultados genera desconfianza inmediata
4. **Incoherencia con dogfooding**: El SaaS se presenta como herramienta de programas publicos de insercion laboral (FSE+), pero los propios casos del programa estan inventados

### 3.3 Brecha "Codigo Existe vs Usuario Experimenta"

| Capa | Estado tecnico | Estado experiencia usuario |
|------|---------------|---------------------------|
| Template 15 secciones | 15/15 PASS | 15/15 secciones con datos ficticios |
| SuccessCase entity (50+ campos) | Funcional, migraciones OK | 8/9 entities con datos inventados |
| Seed script idempotente | Funcional, 9 entities | Propaga ficcion automaticamente |
| Cross-case pollination | Funcional, loadCrossCase() | Enlaza a otro caso ficticio |
| Social proof section | testimonials + metrics + logos | Testimonios de personas que no existen |
| Schema.org Review | JSON-LD correcto | Google indexa reviews de personas ficticias |
| Open Graph meta tags | og:title, og:description | Comparten en redes datos inventados |
| API /api/success-cases | JSON funcional | Page Builder consume datos ficticios |
| 8 Legacy controllers | Hardcoded, WARN en validator | Duplican ficcion en rutas legacy |

---

## 4. Auditoria por Perspectiva Senior

### 4.1 Consultor de Negocio Senior

**Hallazgos criticos:**
- Los 18 participantes reales representan un activo de negocio infrautilizado. Cada uno tiene video de reunion (300-600 MB), analisis estrategico y documentacion detallada
- La **sistematizacion de entrevistas** (fichero .docx de 5.8 MB) contiene la metodologia para extraer testimonios, pero NO se ha ejecutado el pipeline completo brief → foto → consentimiento → entity
- El caso de PED S.L. (dogfooding) esta bien planteado pero necesita datos cuantitativos reales: horas ahorradas, participantes gestionados, coste por participante
- **Oportunidad**: Los 18 casos cubren 5 de los 9 verticales comerciales. Con los datos existentes, se pueden construir 8-10 casos reales de calidad

**Recomendacion:** Priorizar 5 casos reales (1 por vertical cubierto) sobre mantener 9 ficticios. Un caso real con foto, video y metricas verificables vale 10x un caso ficticio perfecto.

### 4.2 Desarrollador de Carreras Senior

**Hallazgos:**
- Elena Maria Jimenez es el caso mas completo para **empleabilidad**: tiene 3 perfiles profesionales redactados, CV, mapeo de oportunidades, guia LinkedIn. Es una narrativa clasica de reinsercion
- Luis Miguel Criado tiene la narrativa "De desempleado a autonomo" perfecta para el vertical
- Las evaluaciones emprendedoras (Natalia Hidalgo, 5.8 MB) contienen datos de progreso que podrian alimentar `metrics_json` con cifras reales
- Los videos de reunion (2-5 min utiles por participante) son materia prima para `video_url` de testimonial

**Recomendacion:** Extraer de los .docx existentes las metricas de progreso (KPIs de insercion, tiempo hasta primer empleo/primer cliente, mejora de ingresos) y documentarlas como `metrics_json` cuantificable.

### 4.3 Analista Financiero Senior

**Hallazgos:**
- Los casos ficticios incluyen metricas financieras impresionantes pero no verificables: "+305% margen", "47.000 euros facturados en 90 dias", "30.000 euros/mes"
- Las metricas del programa PIIL real estan documentadas: 50 participantes, 46% tasa de insercion (datos de la 1a edicion, referenciados en el caso de PED S.L.)
- Sin embargo, NO hay metricas individuales por participante en el repo
- **Riesgo**: Si un auditor FSE+ pide evidencia de los "casos de exito" publicados en el SaaS, no hay trazabilidad

**Recomendacion:** Las metricas de casos reales deben ser conservadoras y verificables. "De 0 a 1 cliente en 3 meses" es mas creible y verificable que "47.000 euros en 90 dias".

### 4.4 Consultor de Marketing Senior / Publicista Senior

**Hallazgos:**
- La arquitectura del template (hero + pain points + timeline + social proof + pricing + FAQ) es de referencia. El problema no es el contenedor sino el contenido
- **Copy actual**: Los textos ficticios son de alta calidad narrativa pero suenan a "IA escribio esto" — misma estructura, mismo tono, mismos patrones ("En 14 dias...", "El copiloto...", "Sin tarjeta de credito...")
- Los testimoniales reales de los .docx tendran imperfecciones linguisticas que paradojicamente los haran MAS creibles
- **Video**: Hay +4 GB de videos de reuniones. Fragmentos de 30-60 segundos con el participante explicando su experiencia serian devastadoramente efectivos como `video_url`
- La foto de perfil es el activo mas critico — los 3 briefs existentes no la tienen

**Recomendacion:**
1. Solicitar foto profesional a los 5 participantes prioritarios (puede ser smartphone, fondo neutro, retrato vertical)
2. Extraer 30-60s de video testimonial de las grabaciones existentes (con consentimiento)
3. El copy debe mantener la voz natural del participante, no reescribirlo "bonito"

### 4.5 Arquitecto SaaS Senior / Ingeniero Drupal Senior

**Hallazgos tecno-arquitectonicos:**

1. **Legacy controllers (8)**: CaseStudyRouteSubscriber redirige las rutas legacy al controller unificado, pero los 8 controllers originales siguen existiendo con datos hardcodeados. No son un riesgo funcional (las rutas estan sobreescritas) pero si un riesgo de mantenimiento y confusion
2. **Seed script propaga ficcion**: `seed-success-cases.php` y `complete-success-cases-data.php` estan diseñados para crear los 9 casos ficticios. Ejecutar el seed en un nuevo entorno propaga automaticamente contenido no verificable
3. **hook_requirements falta**: El modulo `jaraba_success_cases` no tiene `hook_requirements()` que valide que las entities publicadas tienen datos reales (foto, testimonial, metricas)
4. **Setup Wizard / Daily Actions**: El plan de elevacion (20260323) identifica correctamente que los casos son pre-login y no requieren wizard steps para usuarios finales. Sin embargo, falta una **DailyAction administrativa** que alerte cuando hay verticales sin caso real publicado
5. **Content Seed Pipeline**: Los briefs en `docs/assets/casos-de-exito/` deberian ser la fuente del seed script, pero actualmente el seed ignora los briefs y usa datos ficticios directamente en PHP

**Deuda tecnica identificada:**

| ID | Deuda | Prioridad | Esfuerzo |
|----|-------|-----------|----------|
| DT-SC-001 | 8 legacy controllers con datos hardcodeados | Media | 2h (eliminar, las rutas ya redirigen) |
| DT-SC-002 | Seed scripts con datos ficticios | Alta | 4h (reescribir con datos reales) |
| DT-SC-003 | Sin hook_requirements en jaraba_success_cases | Media | 1h |
| DT-SC-004 | Sin DailyAction admin para revision casos | Baja | 1h |
| DT-SC-005 | Briefs incompletos no alimentan seed automaticamente | Alta | 3h (pipeline brief→entity) |
| DT-SC-006 | additional_testimonials_json con personas ficticias | Alta | 2h (reemplazar con reales) |
| DT-SC-007 | comparison_json nombra competidores sin verificar | Media | 1h (verificar Aranzadi, vLex, etc.) |

### 4.6 Ingeniero UX Senior

**Hallazgos:**
- El flujo de conversion del template es correcto: hero emocional → pain points → solucion → social proof → pricing → CTA
- **Credibilidad visual**: La ausencia de fotos reales es el mayor destructor de confianza. Un avatar con inicial (fallback actual del `success-case-card.html.twig`) grita "caso inventado"
- El Schema.org Review con personas ficticias podria generar un penalizacion de Google si detecta patrones de fake reviews
- La seccion de social proof con `additional_testimonials_json` tiene 3 testimoniales extras por vertical — todos ficticios, todos con nombres inventados

### 4.7 Ingeniero SEO/GEO Senior

**Hallazgos:**
- Las rutas `/{vertical}/caso-de-exito/{slug}` estan indexandose con contenido ficticio
- Schema.org Article + Review + FAQPage generan rich snippets con datos no verificables
- canonical, hreflang y OG tags correctos tecnicamente pero apuntan a contenido ficticio
- `meta_description` de cada caso incluye nombres ficticios que Google indexa
- **Riesgo**: Si Google detecta fake reviews en los Schema.org Review, puede penalizar todo el dominio

### 4.8 Ingeniero IA Senior

**Hallazgos:**
- El copilot (CopilotLeadCaptureService) puede referenciar casos de exito en sus respuestas via GroundingProvider
- Si un usuario pregunta al copilot "muestrame un caso de exito de emprendimiento" y el copilot cita a "Carlos Etxebarria", el usuario puede descubrir que es ficticio
- AI-GUARDRAILS-PII-001 no aplica a datos ficticios, pero si los datos se reemplazan por reales, hay que verificar que el PII de participantes reales no se filtre al copilot sin consentimiento

---

## 5. Plan de Accion: De Ficcion a Realidad

### 5.1 Fase 0 — Recopilacion de Assets Reales (BLOQUEANTE — Requiere Pepe)

**Responsable**: Pepe Jaraba (unico con acceso a participantes y consentimientos)

| # | Accion | Participante | Plazo | Entregable |
|---|--------|-------------|-------|------------|
| 1 | Completar brief + solicitar foto profesional | Marcela Calabia | 1 semana | brief.md completo + foto JPG 800x800 |
| 2 | Completar brief + solicitar foto profesional | Angel Martinez | 1 semana | brief.md completo + foto JPG 800x800 |
| 3 | Completar brief + solicitar foto profesional | Luis Miguel Criado | 1 semana | brief.md completo + foto JPG 800x800 |
| 4 | Crear brief nuevo + solicitar foto profesional | Adrian Capatina Tudor | 2 semanas | brief.md + foto + consentimiento |
| 5 | Crear brief nuevo + solicitar foto profesional | Elena Maria Jimenez | 2 semanas | brief.md + foto + consentimiento |
| 6 | Crear brief nuevo + solicitar foto profesional | Natalia Hidalgo | 2 semanas | brief.md + foto + consentimiento |
| 7 | Crear brief nuevo + solicitar foto profesional | Matilde Esteban (Eresvida) | 2 semanas | brief.md + foto + consentimiento |
| 8 | Crear brief nuevo + solicitar foto profesional | Cristina Martin Pereira | 2 semanas | brief.md + foto + consentimiento |
| 9 | Extraer fragmentos video testimonial (30-60s) | Los 5 prioritarios | 2 semanas | MP4 o YouTube ID por participante |
| 10 | Obtener consentimiento RGPD firmado | Todos los anteriores | 2 semanas | Documento firmado (fisico o digital) |

**Prioridad absoluta**: Los items 1-3 (completar los 3 briefs existentes) son el minimo viable.

### 5.2 Fase 1 — Migracion Seed Script a Datos Reales

**Responsable**: Desarrollo (tras recibir briefs completos de Fase 0)

| # | Accion | Archivos | Directriz |
|---|--------|----------|-----------|
| 1 | Reescribir `seed-success-cases.php` con datos reales de briefs | `scripts/migration/seed-success-cases.php` | SUCCESS-CASES-001, MARKETING-TRUTH-001 |
| 2 | Reescribir `complete-success-cases-data.php` con narrativas reales | `scripts/migration/complete-success-cases-data.php` | MARKETING-TRUTH-001 |
| 3 | Actualizar `metrics_json` con metricas verificables y conservadoras | Seed scripts | MARKETING-TRUTH-001 |
| 4 | Reemplazar `additional_testimonials_json` con testimoniales reales | Seed scripts | MARKETING-TRUTH-001 |
| 5 | Subir fotos profesionales a `public://success-cases/` | Deploy manual + entity update | SUCCESS-CASES-001 |
| 6 | Configurar `video_url` con YouTube IDs reales | Entity update via admin UI | VIDEO-HERO-001 |

### 5.3 Fase 2 — Limpieza de Deuda Tecnica

| # | Accion | Archivos | Esfuerzo |
|---|--------|----------|----------|
| 1 | Eliminar 8 legacy controllers (las rutas ya redirigen via CaseStudyRouteSubscriber) | 8 controladores en modulos verticales | 2h |
| 2 | Añadir hook_requirements con checks: foto, testimonial, metricas, consentimiento | `jaraba_success_cases.install` | 1h |
| 3 | Crear DailyAction admin `RevisarCasosExitoAction` (verticales sin caso real) | `src/DailyActions/` | 1h |
| 4 | Actualizar validator SUCCESS-CASES-SSOT-001 para que CHECK 1 sea FAIL (no WARN) | `validate-success-cases-ssot.php` | 30min |
| 5 | Crear nueva regla TESTIMONIAL-VERIFICATION-001 (metricas verificables, consentimiento) | CLAUDE.md | 15min |

### 5.4 Fase 3 — Cobertura Vertical Completa

**Realidad**: De los 9 verticales comerciales, los participantes reales solo cubren 5:

| Vertical | Caso real disponible | Solucion si no hay caso real |
|----------|---------------------|-------------------------------|
| empleabilidad | Luis Miguel Criado, Elena Maria Jimenez | OK — 2 casos reales |
| emprendimiento | Marcela Calabia, Angel Martinez, Adrian Capatina Tudor, Natalia Hidalgo, Matilde Esteban | OK — 5+ casos reales |
| serviciosconecta | Luis Miguel Criado (masajista autonomo con agenda) | OK — 1 caso real |
| agroconecta | Angel Martinez (gastrobiking con productos locales de Sierra Morena) | OK — 1 caso real (stretch) |
| andalucia_ei | PED S.L. (dogfooding real) | OK — caso interno |
| **jarabalex** | **NINGUNO** | Mantener caso ficticio MARCADO como "ejemplo ilustrativo" o buscar cliente beta |
| **comercioconecta** | **NINGUNO directo** | Angel Martinez tiene componente comercial pero es stretch |
| **formacion** | **NINGUNO directo** | Marcela Calabia tiene potencial formador pero es stretch |
| **content_hub** | Adrian Capatina Tudor (creacion contenido) | OK — 1 caso real (stretch) |

**Decision clave**: Para los verticales sin caso real (jarabalex, comercioconecta, formacion), las opciones son:

1. **Etiquetar como "ejemplo ilustrativo"**: Transparencia total. Texto: "Este ejemplo muestra como un profesional podria usar [vertical]. Basado en proyectos reales del ecosistema." MARKETING-TRUTH-001 cumplida
2. **Buscar clientes beta reales**: Ofrecer 3 meses gratis a cambio de testimonial real. Timeline: 3-6 meses
3. **No publicar caso**: Ocultar la seccion de caso de exito en verticales sin caso real. Preferible a publicar ficcion

**Recomendacion**: Opcion 1 para lanzamiento inmediato + Opcion 2 como estrategia a 6 meses.

---

## 6. Analisis de Conversion 10/10 — Comparativa

### 6.1 Score Actual por Dimension

| Dimension | Template (tecnico) | Contenido (real) | Score combinado |
|-----------|-------------------|------------------|-----------------|
| 1. Hero emocional + urgencia | 10/10 | 2/10 (nombre ficticio) | 4/10 |
| 2. Pain points cuantificados | 10/10 | 2/10 (metricas inventadas) | 4/10 |
| 3. Timeline 14 dias | 10/10 | 3/10 (timeline plausible pero no real) | 5/10 |
| 4. Social proof | 10/10 | 1/10 (3 testimoniales ficticios por caso) | 3/10 |
| 5. Video testimonial | 8/10 (VIDEO-HERO-001 ok) | 0/10 (sin video real) | 2/10 |
| 6. Foto protagonista | 10/10 (campo existe) | 0/10 (sin fotos reales subidas) | 2/10 |
| 7. Schema.org Review | 10/10 | 1/10 (fake review = riesgo penalizacion) | 3/10 |
| 8. Pricing inline | 10/10 (4 tiers, MetaSitePricingService) | 10/10 (precios reales) | 10/10 |
| 9. FAQ contextualizada | 10/10 (10+ preguntas, FAQPage) | 5/10 (preguntas plausibles, respuestas genericas) | 7/10 |
| 10. Cross-case pollination | 10/10 (loadCrossCase()) | 1/10 (enlaza a otro caso ficticio) | 3/10 |
| **TOTAL** | **98/100** | **25/100** | **43/100** |

### 6.2 Score Objetivo con Datos Reales

Con 5 casos reales completos (foto + video + testimonial + metricas verificables + consentimiento):

| Dimension | Score con datos reales |
|-----------|-----------------------|
| 1. Hero emocional + urgencia | 9/10 (nombre real + foto real) |
| 2. Pain points cuantificados | 8/10 (metricas conservadoras pero verificables) |
| 3. Timeline real | 8/10 (timeline del programa real, no necesariamente 14 dias) |
| 4. Social proof | 9/10 (testimonial real + metricas programa + logos institucionales reales) |
| 5. Video testimonial | 9/10 (fragmento de reunion real) |
| 6. Foto protagonista | 9/10 (foto real, quiza no profesional pero autentica) |
| 7. Schema.org Review | 10/10 (review real = sin riesgo Google) |
| 8. Pricing inline | 10/10 (sin cambio) |
| 9. FAQ contextualizada | 8/10 (preguntas reales de participantes) |
| 10. Cross-case pollination | 8/10 (enlaza a otro caso real) |
| **TOTAL** | **88/100 = 9/10 clase mundial** |

**Delta**: De 43/100 (actual con ficcion) a 88/100 (con 5 casos reales). **+45 puntos** simplemente reemplazando ficcion con realidad.

---

## 7. Cumplimiento Setup Wizard + Daily Actions (SETUP-WIZARD-DAILY-001)

### 7.1 Analisis

Los casos de exito son paginas de conversion **pre-login** para visitantes anonimos. El plan de elevacion (20260323, seccion 7) identifica correctamente que:

- **NO se requieren wizard steps para usuarios finales** — los casos se consumen antes del registro
- **SI se sugiere una DailyAction administrativa** para marketing (`RevisarCasosExitoAction`)

### 7.2 Gaps Detectados

| Patron | Estado | Gap |
|--------|--------|-----|
| SetupWizardRegistry | N/A para success cases | Correcto — no aplica |
| DailyActionsRegistry | **Falta** DailyAction admin | Crear `RevisarCasosExitoAction` |
| Zeigarnik global steps | N/A | Correcto — no aplica |
| PIPELINE-E2E-001 | Template 15/15, wiring completo | Sin gap tecnico |

### 7.3 DailyAction Propuesta

```
RevisarCasosExitoAction
- Dashboard: __global__ (visible para admin/marketing)
- Checks:
  1. Verticales con 0 SuccessCase publicadas (CRITICO)
  2. SuccessCase publicadas sin hero_image (ALTO)
  3. SuccessCase publicadas con metrics_json vacio (MEDIO)
  4. SuccessCase publicadas con faq_json < 10 items (BAJO)
- Ruta: /admin/content/success-case
```

---

## 8. Hallazgos de la Confrontacion docs/assets vs F:\DATOS

### 8.1 Participantes con Documentacion Rica NO Aprovechada

| Participante | Documentacion en F:\DATOS | Aprovechamiento actual |
|-------------|--------------------------|----------------------|
| Adrian Capatina Tudor | Video, contrato, portfolio, redes, analisis | 0% — sin brief, sin entity |
| Cristina Martin Pereira | Caso diseñado (5.8 MB), video (312 MB), LinkedIn, analisis | 0% — sin brief, sin entity |
| Natalia Hidalgo | Analisis completo + plan accion (5.8 MB), video (560 MB) | 0% — sin brief, sin entity |
| Matilde Esteban | Analisis estrategico, entrevista (6.6 MB), video (524 MB), analisis 12 meses | 0% — sin brief, sin entity |
| Inmaculada Serrano | Plan estrategico, marca personal, video (400 MB) | 0% — sin brief, sin entity |

### 8.2 Documentos de Sistematizacion NO Procesados

- **"Estructura de la entrevista a participantes de Andalucia +ei.docx"** (16.5 KB): Define la metodologia de entrevista. Deberia haber generado 18 briefs estandarizados — solo se crearon 3 stubs
- **"Sistematizacion de Entrevistas Andalucia +ei.docx"** (5.8 MB): Documento completo de sistematizacion que contiene insights de todas las entrevistas — no se ha procesado para alimentar el SaaS
- **"Evaluacion Evolucion Emprendedora Natalia Hidalgo.docx"** (5.8 MB): Evaluacion detallada con metricas de progreso — no alimenta `metrics_json`

### 8.3 Videos de Alta Calidad NO Utilizados

| Participante | Video disponible | Duracion estimada | Fragmento testimonial extraible |
|-------------|-----------------|-------------------|-------------------------------|
| Adrian Capatina Tudor | Reunion grabada | ~60 min | SI — 30-60s sobre impacto del programa |
| Ana Ojeda | Video reunion (605 MB) | ~60 min | SI |
| Cristina Martin Pereira | Video (312 MB) | ~30 min | SI |
| Inmaculada Serrano | Video (400 MB) | ~40 min | SI |
| Juan Raul Almanza | Video (591 MB) | ~60 min | SI |
| Margarita Rivera | Video (382 MB) | ~40 min | SI |
| Maria Fernandez | Video (433 MB) | ~45 min | SI |
| Matilde Esteban | Video (524 MB) | ~50 min | SI |
| Natalia Hidalgo | Video (560 MB) | ~55 min | SI |
| Angel Martinez | Multiples videos y presentaciones | Variable | SI |

**Total: +4 GB de video testimonial sin aprovechar.**

---

## 9. Reglas Nuevas Propuestas

### 9.1 TESTIMONIAL-VERIFICATION-001 (P0 — Propuesta nueva)

> Todo testimonial publicado como "caso de exito" en el SaaS DEBE cumplir:
> 1. Persona real e identificable (nombre completo + foto + perfil publico opcional)
> 2. Consentimiento RGPD documentado en docs/assets/casos-de-exito/{persona}/consentimiento.pdf
> 3. Metricas cuantificables verificables (no estimaciones optimistas sin base)
> 4. Si no hay caso real para un vertical, etiquetar como "ejemplo ilustrativo" (NO presentar como caso real)
> Validacion: `validate-testimonial-verification.php` (propuesto)

### 9.2 SEED-REAL-DATA-001 (P1 — Propuesta nueva)

> Los scripts de seed (`scripts/migration/seed-*.php`) DEBEN alimentarse de datos reales documentados en `docs/assets/`. NUNCA crear entities con datos ficticios presentados como reales.
> Excepciones: entidades de demo/sandbox marcadas explicitamente con `status = FALSE` o `featured = FALSE`.

### 9.3 LEGACY-CONTROLLER-CLEANUP-001 (P2 — Propuesta nueva)

> Los 8 legacy CaseStudyControllers con datos hardcodeados DEBEN eliminarse una vez confirmado que CaseStudyRouteSubscriber redirige correctamente todas las rutas.
> Archivos: `{Agro,AndaluciaEi,Emprendimiento,Empleabilidad,Comercio,ContentHub,Formacion,Servicios}CaseStudyController.php`

---

## 10. Matriz de Priorizacion

| # | Accion | Impacto conversion | Esfuerzo | Dependencia | Prioridad |
|---|--------|--------------------|----------|-------------|-----------|
| 1 | Completar 3 briefs existentes (foto + testimonial + metricas + consentimiento) | CRITICO (+30 puntos) | MEDIO (2 sem, requiere Pepe) | Bloqueante para todo | **P0** |
| 2 | Reescribir seed scripts con datos de los 3 briefs reales | ALTO (+15 puntos) | BAJO (4h dev) | Depende de #1 | **P0** |
| 3 | Crear 5 briefs nuevos (Adrian, Elena, Natalia, Matilde, Cristina) | ALTO (+20 puntos) | ALTO (3 sem, requiere Pepe) | Paralelo a #1 | **P1** |
| 4 | Etiquetar 4 verticales sin caso real como "ejemplo ilustrativo" | MEDIO (+10 confianza) | BAJO (2h dev) | Inmediato | **P1** |
| 5 | Eliminar 8 legacy controllers | BAJO (limpieza) | BAJO (2h dev) | Inmediato | **P2** |
| 6 | Extraer fragmentos video testimonial 30-60s | ALTO (+15 puntos) | MEDIO (requiere Pepe + edicion) | Depende de consentimiento | **P1** |
| 7 | hook_requirements + DailyAction admin | BAJO (monitoring) | BAJO (2h dev) | Inmediato | **P2** |
| 8 | Validator TESTIMONIAL-VERIFICATION-001 | MEDIO (salvaguarda) | BAJO (2h dev) | Inmediato | **P2** |

---

## 11. Metricas Globales — Reconciliacion

Segun `_metricas-globales.md`, las KPIs del ecosistema tienen discrepancias entre metasitios:

| KPI | Valores encontrados | Estado | Recomendacion |
|-----|-------------------|--------|---------------|
| Experiencia | +30 años | Pendiente validacion | Verificar con CV Pepe |
| Fondos gestionados | +100M euros vs "100" sin unidad | **DISCREPANCIA** | Unificar: verificar y documentar fuente |
| Beneficiarios | +50,000 vs +15,000 | **DISCREPANCIA** | Usar la cifra con documentacion soporte |
| Personas formadas | +15,000 | Pendiente | Documentar fuente |
| Empleos creados | +3,200 | Pendiente | Documentar fuente (informes SAE?) |
| PYMES digitalizadas | +800 | Pendiente | Documentar fuente |
| Municipios impactados | +120 | Pendiente | Documentar fuente |
| Tasa exito | 98% | **Sospechosamente alta** | Definir metodologia, ajustar si necesario |
| Verticales | 7 (ahora son 10) | **DESACTUALIZADO** | Actualizar a 10 |

**MARKETING-TRUTH-001 aplica**: Cada KPI publicada DEBE tener una fuente verificable documentada en `_metricas-globales.md`.

---

## 12. Checklist de Aceptacion 10/10

### Contenido Real (P0)
- [ ] 3 briefs completados con foto, testimonial, metricas, consentimiento
- [ ] Seed scripts reescritos con datos reales de los 3 participantes
- [ ] Foto profesional subida como hero_image para cada caso real
- [ ] Metricas cuantificables verificables documentadas

### Transparencia (P1)
- [ ] Verticales sin caso real etiquetados como "ejemplo ilustrativo"
- [ ] additional_testimonials_json con testimoniales reales o eliminados
- [ ] KPIs globales reconciliadas con fuentes verificables

### Deuda Tecnica (P2)
- [ ] 8 legacy controllers eliminados
- [ ] hook_requirements añadido (foto, testimonial, metricas)
- [ ] DailyAction admin `RevisarCasosExitoAction` creada
- [ ] Validator TESTIMONIAL-VERIFICATION-001 implementado
- [ ] SUCCESS-CASES-SSOT-001 CHECK 1 promovido de WARN a FAIL

### Contenido Expandido (P3)
- [ ] 5 briefs nuevos creados (Adrian, Elena, Natalia, Matilde, Cristina)
- [ ] Videos testimoniales extraidos (30-60s por participante)
- [ ] Cobertura: al menos 5 de 9 verticales con caso real

---

## 13. Glosario de Siglas

| Sigla | Significado |
|-------|------------|
| ATS | Applicant Tracking System — sistema de seguimiento de candidatos que filtra CVs automaticamente |
| AOVE | Aceite de Oliva Virgen Extra |
| CRM | Customer Relationship Management — gestion de relaciones con clientes |
| CTA | Call To Action — boton o enlace que invita a realizar una accion de conversion |
| DOP | Denominacion de Origen Protegida |
| DSA | Digital Services Act — Reglamento (UE) 2022/2065 de servicios digitales |
| FAQ | Frequently Asked Questions — preguntas frecuentes |
| FSE+ | Fondo Social Europeo Plus — fondo de la UE para empleo e inclusion |
| JSON-LD | JavaScript Object Notation for Linked Data — formato de datos estructurados para SEO |
| KPI | Key Performance Indicator — indicador clave de rendimiento |
| LMS | Learning Management System — sistema de gestion de aprendizaje |
| MVP | Minimum Viable Product — producto minimo viable |
| OG | Open Graph — protocolo de metadatos para compartir en redes sociales |
| PAC | Politica Agraria Comun de la Union Europea |
| PED | Plataforma de Ecosistemas Digitales S.L. |
| PII | Personally Identifiable Information — informacion de identificacion personal |
| PIIL | Programa Integrado de Insercion Laboral |
| PLG | Product-Led Growth — estrategia de crecimiento dirigida por el producto |
| QR | Quick Response — codigo de respuesta rapida bidimensional |
| RGPD | Reglamento General de Proteccion de Datos — normativa europea de privacidad |
| SAE | Servicio Andaluz de Empleo |
| SaaS | Software as a Service — software como servicio |
| SEO | Search Engine Optimization — optimizacion para motores de busqueda |
| SSOT | Single Source Of Truth — fuente unica de verdad para datos |
| UX | User Experience — experiencia de usuario |
| WCAG | Web Content Accessibility Guidelines — directrices de accesibilidad web |

---

*Documento generado como parte del sistema de documentacion de Jaraba Impact Platform.*
*Cumple DOC-GUARD-001: documento nuevo, no sobreescribe master docs.*
*Cumple DOC-GLOSSARY-001: glosario de siglas incluido al final.*
