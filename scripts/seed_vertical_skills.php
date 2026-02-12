<?php

/**
 * @file
 * Script para crear las 30 Skills Verticales predefinidas del AI Skills System.
 *
 * Crea skills especializadas por vertical con contenido experto
 * adaptado al mercado español. Idempotente: verifica existencia antes de crear.
 *
 * Ejecutar con: lando drush php:script scripts/seed_vertical_skills.php
 *
 * Verticales:
 * - empleabilidad (7 skills)
 * - emprendimiento (7 skills)
 * - agroconecta (6 skills)
 * - comercioconecta (5 skills)
 * - serviciosconecta (5 skills)
 */

use Drupal\jaraba_skills\Entity\AiSkill;

// =============================================================================
// EMPLEABILIDAD (7 skills) — Mercado laboral español
// =============================================================================

$verticalSkills = [

  // --- EMPLEABILIDAD ---

  [
    'name' => 'Optimización de CV',
    'skill_type' => 'vertical',
    'vertical_id' => 'empleabilidad',
    'priority' => 80,
    'content' => <<<'CONTENT'
## Propósito
Analiza y optimiza currículos para maximizar la tasa de entrevistas en el mercado laboral español. Aplica cuando el usuario sube, pega o describe su CV, o solicita revisión de su perfil profesional.

## Input Esperado
- CV del usuario (texto completo o secciones específicas)
- Puesto objetivo o sector de interés (opcional)
- Años de experiencia
- Nivel de estudios

## Proceso
1. **Análisis estructural**: Verificar que el CV sigue el formato europeo estándar (datos personales, perfil profesional, experiencia, formación, competencias, idiomas).
2. **Evaluación de impacto**: Identificar logros cuantificables vs. descripciones genéricas de funciones. Transformar "responsable de ventas" en "Incrementé ventas un 25% en 12 meses gestionando cartera de 40 clientes".
3. **Optimización ATS**: Incluir palabras clave del sector relevante. Verificar compatibilidad con sistemas de filtrado automático (formato limpio, sin tablas complejas ni gráficos).
4. **Adaptación cultural**: Aplicar convenciones españolas — foto profesional incluida, DNI/NIE no obligatorio desde GDPR, indicar disponibilidad de incorporación.
5. **Longitud y formato**: Máximo 2 páginas para <10 años experiencia, 3 para senior. Tipografía legible (Arial/Calibri 10-11pt).
6. **Sección de competencias**: Separar técnicas de transversales. Alinear con marco ESCO europeo.

## Output Esperado
Respuesta estructurada con:
- **Diagnóstico**: Puntuación del CV actual (1-10) con justificación
- **Mejoras prioritarias**: Lista ordenada de cambios con mayor impacto
- **Texto sugerido**: Redacción mejorada de las secciones débiles
- **Checklist final**: Verificación de elementos imprescindibles

## Restricciones
- NO inventar experiencia ni inflar logros
- NO incluir datos personales sensibles (religión, estado civil, edad exacta)
- NO usar plantillas creativas para sectores tradicionales (banca, administración pública)
- NO asumir que el usuario domina todos los idiomas que lista — preguntar nivel real

## Ejemplos
**Input**: "Soy administrativo con 5 años de experiencia, quiero mejorar mi CV para optar a puestos de office manager"
**Output**: Reestructuración con perfil profesional orientado a gestión, logros cuantificados en eficiencia administrativa, competencias digitales destacadas (SAP, Microsoft 365, gestión documental).

**Input**: "Tengo un CV de 4 páginas con toda mi trayectoria desde los 18 años"
**Output**: Versión condensada de 2 páginas focalizando últimos 10 años, agrupando experiencia antigua en línea resumen.

## Validación
- ¿El CV resultante pasa un filtro ATS con las keywords del puesto objetivo?
- ¿Cada experiencia incluye al menos un logro cuantificable?
- ¿El formato cumple estándares españoles/europeos?
- ¿La longitud es apropiada al nivel de experiencia?
CONTENT,
  ],

  [
    'name' => 'Preparación de Entrevistas',
    'skill_type' => 'vertical',
    'vertical_id' => 'empleabilidad',
    'priority' => 79,
    'content' => <<<'CONTENT'
## Propósito
Prepara al candidato para entrevistas de trabajo simulando preguntas reales del mercado español y proporcionando estrategias de respuesta basadas en el método STAR. Aplica cuando el usuario tiene una entrevista próxima o quiere practicar.

## Input Esperado
- Puesto al que opta y empresa (si se conoce)
- CV del candidato o resumen de experiencia
- Tipo de entrevista (presencial, videollamada, técnica, grupal, por competencias)
- Sector de la empresa

## Proceso
1. **Investigación del puesto**: Analizar la descripción del puesto para identificar competencias clave que evaluarán.
2. **Banco de preguntas**: Generar 10-15 preguntas probables, clasificadas en: situacionales, conductuales, técnicas y motivacionales.
3. **Respuestas STAR**: Para cada pregunta conductual, guiar al usuario en formular respuestas con Situación-Tarea-Acción-Resultado.
4. **Preguntas trampa**: Preparar respuestas para clásicos españoles: "¿Por qué dejaste tu último trabajo?", "¿Cuáles son tus pretensiones salariales?", "¿Dónde te ves en 5 años?".
5. **Preguntas para el entrevistador**: Sugerir 3-5 preguntas inteligentes que demuestren interés genuino.
6. **Logística**: Consejos sobre vestimenta según sector, puntualidad (llegar 10 min antes), documentación a llevar.

## Output Esperado
- Lista de preguntas probables con respuestas modelo personalizadas
- Guión de presentación personal (elevator pitch de 60 segundos)
- Red flags a evitar durante la entrevista
- Plan de seguimiento post-entrevista (email de agradecimiento)

## Restricciones
- NO dar respuestas que suenen memorizadas o artificiales
- NO aconsejar mentir sobre experiencia, motivos de salida o expectativas
- NO ignorar el contexto cultural español (la entrevista en España tiende a ser más personal)
- NO generar preguntas ilegales (estado civil, planes de maternidad) — si el usuario reporta que le han hecho estas preguntas, informar de que son ilegales según la Ley 15/2022

## Ejemplos
**Input**: "Tengo entrevista para desarrollador junior en una startup de Madrid"
**Output**: 12 preguntas técnicas + conductuales adaptadas a cultura startup, respuestas STAR personalizadas, consejo de vestimenta casual-smart.

**Input**: "Me han convocado a una dinámica de grupo para banca"
**Output**: Guía de roles en dinámicas grupales, estrategias de visibilidad sin dominar, errores comunes en assessment centers bancarios.

## Validación
- ¿Las preguntas son realistas para el sector y nivel del puesto?
- ¿Las respuestas STAR son específicas (no genéricas)?
- ¿Se ha considerado el tipo de entrevista específico?
- ¿Los consejos son culturalmente apropiados para España?
CONTENT,
  ],

  [
    'name' => 'Negociación Salarial',
    'skill_type' => 'vertical',
    'vertical_id' => 'empleabilidad',
    'priority' => 78,
    'content' => <<<'CONTENT'
## Propósito
Asesora al usuario en estrategias de negociación salarial basadas en datos reales del mercado laboral español. Aplica cuando el usuario recibe una oferta, está en proceso de revisión salarial o quiere conocer su valor de mercado.

## Input Esperado
- Puesto actual o al que opta
- Ubicación geográfica (ciudad/CCAA)
- Años de experiencia en el puesto
- Salario actual o último conocido (si lo comparte)
- Tipo de contrato (indefinido, temporal, autónomo)
- Tamaño de empresa y sector

## Proceso
1. **Benchmark salarial**: Consultar rangos del mercado español según puesto, experiencia y ubicación. Fuentes de referencia: Infojobs Trends, Hays, Michael Page, Randstad.
2. **Cálculo de paquete total**: Desglosar salario bruto anual en 12/14 pagas, bonus variable, beneficios sociales (seguro médico, tickets restaurante, formación, teletrabajo).
3. **Estrategia de negociación**: Preparar argumentos basados en valor aportado, no en necesidad personal.
4. **Contraoferta**: Si la oferta inicial está por debajo del rango, formular contraoferta razonada con 3 argumentos de peso.
5. **Timing**: Identificar el momento óptimo para negociar (tras la oferta formal, nunca en la primera entrevista).
6. **Alternativas no salariales**: Si hay techo salarial, negociar: días extra de vacaciones, jornada flexible, teletrabajo, formación, plan de carrera.

## Output Esperado
- Rango salarial de mercado para el perfil (percentil 25-50-75)
- Guión de negociación con frases concretas
- Lista de beneficios negociables más allá del salario
- Cálculo de salario neto estimado (considerando IRPF y SS)

## Restricciones
- NO inventar cifras de mercado — usar rangos realistas del mercado español
- NO aconsejar tácticas agresivas o ultimátums
- NO ignorar el convenio colectivo cuando aplique
- NO confundir salario bruto con neto — siempre aclarar

## Ejemplos
**Input**: "Me ofrecen 28.000€ brutos como analista de datos junior en Barcelona"
**Output**: Rango de mercado 26.000-35.000€, la oferta está en percentil 25, estrategia para negociar a 31.000€ con argumentos de coste de vida en Barcelona y skills en Python/SQL.

**Input**: "Llevo 3 años sin subida salarial y quiero pedir un aumento"
**Output**: Preparación de caso con logros documentados del período, benchmark actualizado, momento ideal (tras evaluación anual), guión de conversación con manager.

## Validación
- ¿Los rangos salariales son realistas para España (no USA/UK)?
- ¿Se ha considerado la ubicación geográfica (Madrid ≠ Jaén)?
- ¿El guión de negociación es profesional y no confrontativo?
- ¿Se distingue claramente bruto de neto?
CONTENT,
  ],

  [
    'name' => 'Optimización de LinkedIn',
    'skill_type' => 'vertical',
    'vertical_id' => 'empleabilidad',
    'priority' => 77,
    'content' => <<<'CONTENT'
## Propósito
Optimiza el perfil de LinkedIn del usuario para aumentar visibilidad ante reclutadores y oportunidades profesionales en el mercado español e hispanohablante. Aplica cuando el usuario quiere mejorar su presencia profesional digital.

## Input Esperado
- URL del perfil o texto de las secciones actuales
- Objetivo profesional (búsqueda activa, networking, marca personal)
- Sector e industria
- Nivel profesional (junior, mid, senior, directivo)

## Proceso
1. **Titular profesional**: Crear headline que vaya más allá del puesto actual. Fórmula: [Rol] | [Especialidad] | [Valor diferencial]. Máx. 120 caracteres.
2. **Extracto/Acerca de**: Redactar en primera persona, 3 párrafos: quién eres profesionalmente, qué aportas, qué buscas. Incluir keywords del sector.
3. **Experiencia**: Transformar descripciones en logros con métricas. Usar viñetas, máximo 5 por posición.
4. **Aptitudes y validaciones**: Seleccionar top 3 alineadas con objetivo. Pedir validaciones estratégicas.
5. **Configuración SEO**: Palabras clave en titular, extracto, experiencia y aptitudes para aparecer en búsquedas de reclutadores.
6. **Contenido y actividad**: Estrategia de publicación (frecuencia, temas, formato) para aumentar visibilidad orgánica.

## Output Esperado
- Titular profesional optimizado (2-3 opciones)
- Extracto completo redactado
- Experiencia reformulada con logros
- Lista de 10 aptitudes prioritarias
- Plan de contenidos semanal básico

## Restricciones
- NO escribir en tercera persona (en LinkedIn español se usa primera persona)
- NO incluir información que no esté en el CV real del usuario
- NO sobreoptimizar con keywords artificiales (spam de palabras clave)
- NO ignorar la foto — recomendar foto profesional si no tiene

## Ejemplos
**Input**: "Mi titular dice 'Desempleado buscando oportunidades'"
**Output**: Nuevo titular: "Especialista en Marketing Digital | SEO & SEM | Transformando datos en estrategias de crecimiento", con extracto completo y recomendaciones.

**Input**: "Tengo 500 contactos pero nadie ve mis publicaciones"
**Output**: Análisis de frecuencia y tipo de contenido, estrategia de engagement, horarios óptimos para publicar en España (martes-jueves 8-10h).

## Validación
- ¿El titular incluye keywords buscados por reclutadores del sector?
- ¿El extracto transmite valor diferencial en <30 segundos de lectura?
- ¿Las recomendaciones son aplicables al mercado hispanohablante?
- ¿El perfil resultante pasaría un "test de reclutador" (¿le contactarían?)?
CONTENT,
  ],

  [
    'name' => 'Redacción de Carta de Presentación',
    'skill_type' => 'vertical',
    'vertical_id' => 'empleabilidad',
    'priority' => 76,
    'content' => <<<'CONTENT'
## Propósito
Redacta cartas de presentación personalizadas y persuasivas para candidaturas en el mercado español. Aplica cuando el usuario necesita acompañar su CV con una carta para una oferta concreta o candidatura espontánea.

## Input Esperado
- Oferta de empleo o empresa objetivo
- CV o resumen de experiencia del candidato
- Motivación específica para el puesto (si la tiene)
- Canal de envío (email, portal empleo, entrega en mano)

## Proceso
1. **Análisis de la oferta**: Extraer los 3-5 requisitos clave que la empresa busca.
2. **Matching**: Identificar experiencias del candidato que responden directamente a cada requisito.
3. **Estructura de la carta**: Párrafo de apertura (por qué esta empresa), párrafo central (qué aporto con evidencias), párrafo de cierre (llamada a la acción).
4. **Personalización**: Mencionar la empresa por nombre, referir algún proyecto o valor corporativo concreto.
5. **Tono**: Profesional pero con personalidad. Evitar clichés ("soy una persona dinámica y proactiva").
6. **Formato**: Máximo 300 palabras. Encabezado con datos de contacto y fecha.

## Output Esperado
- Carta de presentación completa lista para enviar
- Versión para email (más breve, sin encabezado formal)
- 2-3 variaciones del párrafo de apertura

## Restricciones
- NO usar plantillas genéricas con huecos para rellenar
- NO repetir el CV en forma de prosa — la carta complementa, no duplica
- NO usar fórmulas arcaicas ("Estimado señor/señora, me dirijo a usted...")
- NO exceder 1 página A4

## Ejemplos
**Input**: "Quiero aplicar a un puesto de project manager en Telefónica"
**Output**: Carta que conecta experiencia en gestión de proyectos con la transformación digital de Telefónica, mencionando valores corporativos y un proyecto concreto del candidato con métricas de éxito.

**Input**: "Candidatura espontánea a una ONG de cooperación"
**Output**: Carta enfocada en motivación personal genuina, competencias transferibles y disponibilidad, con tono más cercano adaptado al sector social.

## Validación
- ¿La carta menciona la empresa y el puesto específico?
- ¿Incluye al menos 2 evidencias concretas de valor aportado?
- ¿El tono es apropiado para el sector y la empresa?
- ¿La extensión es ≤300 palabras?
CONTENT,
  ],

  [
    'name' => 'Estrategia de Búsqueda de Empleo',
    'skill_type' => 'vertical',
    'vertical_id' => 'empleabilidad',
    'priority' => 75,
    'content' => <<<'CONTENT'
## Propósito
Diseña una estrategia integral de búsqueda de empleo personalizada para el mercado laboral español. Aplica cuando el usuario está en búsqueda activa, quiere cambiar de sector o reincorporarse tras un período de inactividad.

## Input Esperado
- Situación actual (empleado buscando cambio, desempleado, recién graduado, reincorporación)
- Sector/puestos objetivo
- Ubicación y movilidad geográfica
- Urgencia de la búsqueda
- Canales ya utilizados

## Proceso
1. **Diagnóstico**: Evaluar la situación actual del usuario, fortalezas, debilidades y objetivo realista.
2. **Mapa de canales**: Portales de empleo España (InfoJobs, LinkedIn, Indeed, Computrabajo), ETTs (Randstad, Adecco, Manpower), SEPE/SAE, bolsas de empleo sectoriales, networking.
3. **Plan semanal**: Estructurar la búsqueda como un trabajo — horarios, objetivos diarios (X candidaturas, X contactos, X seguimientos).
4. **Candidatura estratégica**: No aplicar masivamente — seleccionar ofertas donde el match sea >70%. Personalizar cada candidatura.
5. **Red de contactos**: Estrategia de networking activo — contactos de segundo grado en LinkedIn, eventos sectoriales, colegios profesionales.
6. **Seguimiento**: Sistema para trackear candidaturas enviadas, respuestas recibidas y próximos pasos.
7. **Plan B**: Alternativas como formación complementaria, voluntariado profesional o freelance mientras se busca.

## Output Esperado
- Plan de búsqueda semanal estructurado (lunes a viernes)
- Lista de canales prioritarios según perfil y sector
- Plantilla de seguimiento de candidaturas
- Timeline realista para encontrar empleo según sector

## Restricciones
- NO prometer plazos concretos de colocación
- NO recomendar solo portales — el mercado oculto en España es >50%
- NO ignorar las oficinas del SEPE/SAE y sus programas de empleo
- NO desanimar al usuario si lleva tiempo buscando

## Ejemplos
**Input**: "Llevo 6 meses buscando como administrativo en Sevilla y solo aplico por InfoJobs"
**Output**: Diversificación a 5 canales (InfoJobs + LinkedIn + ETTs + SAE + networking), plan semanal estructurado, revisión de CV para ATS, estrategia de candidaturas espontáneas a empresas del Parque Tecnológico.

**Input**: "Soy ingeniera y me acabo de mudar a España desde Colombia"
**Output**: Guía de homologación de títulos, portales especializados en perfiles internacionales, asociaciones profesionales de ingeniería en España, adaptación cultural del CV.

## Validación
- ¿El plan incluye al menos 3 canales diferentes?
- ¿Las recomendaciones son específicas para la ubicación del usuario?
- ¿Se incluye tanto mercado visible como oculto?
- ¿El plan es realista y sostenible (no agotador)?
CONTENT,
  ],

  [
    'name' => 'Análisis de Brecha de Competencias',
    'skill_type' => 'vertical',
    'vertical_id' => 'empleabilidad',
    'priority' => 74,
    'content' => <<<'CONTENT'
## Propósito
Identifica las brechas entre las competencias actuales del usuario y las requeridas para su objetivo profesional, proponiendo un plan de desarrollo formativo. Aplica cuando el usuario quiere evolucionar profesionalmente o cambiar de sector.

## Input Esperado
- Perfil actual del usuario (formación, experiencia, competencias)
- Puesto o sector objetivo
- Tiempo y presupuesto disponible para formación
- Preferencia de modalidad (online, presencial, mixta)

## Proceso
1. **Mapeo de competencias actuales**: Listar skills técnicas y transversales del usuario con nivel (básico/intermedio/avanzado).
2. **Análisis del objetivo**: Investigar las competencias que requiere el puesto o sector objetivo. Fuentes: ofertas de empleo del sector, marcos ESCO, perfiles profesionales INCUAL.
3. **Identificación de brechas**: Comparar ambas listas. Clasificar gaps en: críticos (bloquean acceso), importantes (mejoran competitividad), deseables (diferenciación).
4. **Plan de desarrollo**: Para cada brecha crítica, proponer formación específica: cursos con certificación reconocida en España (FUNDAE, universidades, certificaciones internacionales).
5. **Priorización temporal**: Ordenar el plan de formación por impacto en empleabilidad. Las brechas críticas primero.
6. **Recursos gratuitos**: Incluir opciones de formación gratuita cuando existan (SEPE, FUNDAE, MOOCs certificados, programas autonómicos).

## Output Esperado
- Matriz de competencias: actual vs. requerido, con gap identificado
- Plan formativo priorizado con:
  - Nombre del curso/certificación
  - Proveedor y modalidad
  - Duración estimada
  - Coste (o si es gratuito vía FUNDAE/SEPE)
- Timeline de desarrollo (3, 6, 12 meses)

## Restricciones
- NO recomendar formación genérica sin relación directa con el gap
- NO ignorar la formación bonificada FUNDAE (la mayoría de trabajadores por cuenta ajena tienen derecho)
- NO asumir que más certificaciones = más empleo — priorizar calidad y relevancia
- NO descartar la experiencia no formal del usuario

## Ejemplos
**Input**: "Soy contable y quiero pasar a controller financiero"
**Output**: Gaps identificados en ERP avanzado (SAP FI/CO), reporting NIIF, análisis predictivo. Plan: certificación SAP (6 meses), curso NIIF online (3 meses), Excel/Power BI avanzado (2 meses). Coste total estimado vs. opciones FUNDAE.

**Input**: "Trabajo en hostelería y quiero pasarme a marketing digital"
**Output**: Brechas completas en marketing digital. Plan intensivo: certificación Google Ads + Analytics (gratuita), curso community management (SEPE gratuito), portfolio con proyecto personal. Timeline: 6-9 meses para primer empleo junior.

## Validación
- ¿Las brechas identificadas son reales para el mercado español?
- ¿El plan formativo incluye proveedores concretos y accesibles?
- ¿Se han considerado opciones de financiación/gratuidad?
- ¿El timeline es realista para la situación del usuario?
CONTENT,
  ],

  // --- EMPRENDIMIENTO ---

  [
    'name' => 'Coaching de Business Model Canvas',
    'skill_type' => 'vertical',
    'vertical_id' => 'emprendimiento',
    'priority' => 80,
    'content' => <<<'CONTENT'
## Propósito
Guía al emprendedor en la creación y validación de su Business Model Canvas, asegurando coherencia entre los 9 bloques y viabilidad en el mercado español. Aplica cuando el usuario está diseñando un nuevo negocio o pivotando uno existente.

## Input Esperado
- Idea de negocio o descripción general del proyecto
- Fase del emprendimiento (idea, validación, crecimiento)
- Sector y mercado objetivo
- Cualquier bloque del Canvas que ya tenga definido

## Proceso
1. **Propuesta de valor**: Definir el problema que resuelve y para quién. Usar framework Jobs-to-be-Done. Validar que es un problema real pagable.
2. **Segmentos de clientes**: Identificar early adopters vs. mercado masivo. En España, considerar particularidades regionales y estacionalidad.
3. **Canales**: Evaluar canales de distribución y comunicación realistas para el presupuesto de una startup española (CAC asequible).
4. **Relación con clientes**: Definir el tipo de relación (autoservicio, comunidad, asistencia personal) según segmento.
5. **Fuentes de ingresos**: Modelar pricing realista para el mercado español (poder adquisitivo, competencia, disposición a pagar).
6. **Recursos, actividades y socios clave**: Identificar lo mínimo necesario para el MVP — no sobredimensionar.
7. **Estructura de costes**: Estimar costes realistas incluyendo SS autónomos (cuota progresiva), gestoría, herramientas.
8. **Validación cruzada**: Verificar coherencia entre todos los bloques — cada segmento tiene canal, cada propuesta tiene ingreso.

## Output Esperado
- Canvas completo con los 9 bloques desarrollados
- 3 hipótesis críticas a validar antes de invertir
- Métricas clave para cada hipótesis (cómo saber si funciona)
- Siguiente paso concreto de validación

## Restricciones
- NO validar ideas sin cuestionar las hipótesis subyacentes
- NO asumir que el mercado español funciona igual que el americano
- NO ignorar la cuota de autónomos y la fiscalidad española
- NO crear canvas demasiado optimistas — ser realista con el TAM español

## Ejemplos
**Input**: "Quiero montar una app de delivery de comida saludable en Málaga"
**Output**: Canvas completo con análisis de competencia local (Glovo, Just Eat ya dominan), propuesta de valor diferencial (nicho salud + local), costes de flota propia vs. última milla, modelo de suscripción. Hipótesis crítica: ¿hay demanda suficiente en Málaga para nicho saludable?

**Input**: "Tengo una idea de SaaS B2B para gestión de residuos"
**Output**: Canvas B2B con ciclo de venta largo, regulación ambiental como driver, partners estratégicos (consultoras medioambientales), pricing por licencia vs. por uso.

## Validación
- ¿Los 9 bloques son coherentes entre sí?
- ¿Las hipótesis críticas están claramente identificadas?
- ¿Los costes incluyen realidades españolas (autónomos, IVA, SS)?
- ¿La propuesta de valor resuelve un problema real y pagable?
CONTENT,
  ],

  [
    'name' => 'Revisión de Pitch Deck',
    'skill_type' => 'vertical',
    'vertical_id' => 'emprendimiento',
    'priority' => 79,
    'content' => <<<'CONTENT'
## Propósito
Analiza y mejora presentaciones para inversores (pitch decks) siguiendo estándares del ecosistema inversor español y europeo. Aplica cuando el usuario prepara una ronda de inversión, concurso de emprendimiento o presentación ante aceleradoras.

## Input Esperado
- Contenido actual del pitch deck (slides o texto)
- Audiencia objetivo (business angels, VC, aceleradora, programa público)
- Fase de inversión (pre-seed, seed, serie A)
- Métricas actuales del negocio (si existen)

## Proceso
1. **Estructura 10-12 slides**: Problema → Solución → Mercado → Modelo de negocio → Tracción → Equipo → Competencia → Financiero → Ask → Contacto.
2. **Slide de problema**: ¿Es un problema real con datos? Evitar el "yo tuve este problema y asumí que todos lo tienen".
3. **Slide de mercado**: TAM/SAM/SOM con fuentes verificables para el mercado español/europeo. No copiar cifras globales de Statista sin contextualizar.
4. **Slide de tracción**: Métricas reales — MRR, usuarios activos, NPS, retention. Si es pre-revenue, validaciones cualitativas (entrevistas, LOIs, pilotos).
5. **Slide financiero**: Proyecciones a 3 años con hipótesis explícitas. Unit economics (CAC, LTV, payback period).
6. **Slide de equipo**: Por qué este equipo es el indicado para este problema. Complementariedad de skills.
7. **El Ask**: Cuánto se pide, para qué exactamente, qué milestones se alcanzarán con ese dinero.
8. **Storytelling**: Crear un hilo narrativo que conecte todas las slides.

## Output Esperado
- Feedback detallado slide por slide
- Sugerencias de contenido para slides débiles
- Guión de presentación (qué decir en cada slide, 30-60 seg/slide)
- Preguntas que hará el inversor y cómo responderlas

## Restricciones
- NO inflar métricas o proyecciones para impresionar
- NO usar jerga de Silicon Valley sin contexto ("unicornio", "disruption") — los inversores españoles lo detectan
- NO ignorar la competencia — decir "no tenemos competencia" es red flag
- NO poner demasiado texto por slide — regla de 20 palabras máximo

## Ejemplos
**Input**: "Presento ante Lanzadera (Valencia) para fase de aceleración"
**Output**: Feedback adaptado al perfil de Lanzadera (buscan tracción inicial, equipo comprometido, escalabilidad), ajuste de ask a rangos de aceleración (50-200K), enfoque en métricas de validación.

**Input**: "Ronda seed de 500K ante business angels de Barcelona"
**Output**: Deck enfocado en unit economics, mercado adresable en Cataluña/España, plan de uso de fondos con milestones a 18 meses, valoración justificada.

## Validación
- ¿El pitch cuenta una historia coherente en 10-12 slides?
- ¿Las proyecciones financieras tienen hipótesis explícitas y realistas?
- ¿El ask es específico (cantidad + uso + milestones)?
- ¿El pitch dura ≤10 minutos en presentación oral?
CONTENT,
  ],

  [
    'name' => 'Proyección Financiera',
    'skill_type' => 'vertical',
    'vertical_id' => 'emprendimiento',
    'priority' => 78,
    'content' => <<<'CONTENT'
## Propósito
Genera proyecciones financieras realistas para startups y PYMEs en el contexto fiscal y económico español. Aplica cuando el usuario necesita un plan financiero para inversores, banca o planificación interna.

## Input Esperado
- Modelo de negocio (SaaS, marketplace, servicios, producto físico)
- Ingresos actuales (si existen)
- Estructura de costes conocida
- Horizonte temporal deseado (12, 24, 36 meses)
- Tipo de entidad (autónomo, SL, SA)

## Proceso
1. **Modelo de ingresos**: Construir bottom-up desde unidades vendidas × precio, no top-down desde % del TAM. Considerar estacionalidad española (verano, Navidad, septiembre).
2. **Costes fijos**: SS autónomos (tarifa plana → progresiva), alquiler (según ciudad), herramientas SaaS, gestoría (150-300€/mes), seguros.
3. **Costes variables**: COGS, comisiones, costes de adquisición, logística.
4. **Fiscalidad española**: IS (25% general, 15% nuevas empresas 2 primeros años), IVA (21% general, 10% reducido, 4% superreducido), retenciones IRPF si autónomo.
5. **Cash flow**: Proyección mensual con previsión de cobro (plazo medio en España: 60-90 días B2B). Identificar meses de caja negativa.
6. **Escenarios**: Pesimista, base y optimista con hipótesis explícitas para cada uno.
7. **KPIs financieros**: Burn rate mensual, runway, break-even point, margen bruto, margen neto.

## Output Esperado
- Cuenta de resultados proyectada (mensual primer año, trimestral años 2-3)
- Cash flow mensual con saldos acumulados
- Break-even analysis (cuándo y bajo qué condiciones)
- Tabla de KPIs financieros por período
- Escenario pesimista con plan de contingencia

## Restricciones
- NO usar tipos impositivos de otros países
- NO ignorar la cuota de autónomos ni las retenciones
- NO asumir cobro inmediato en B2B España (estándar 60 días)
- NO proyectar hockey stick sin justificación — crecimiento gradual es más creíble
- NO olvidar el IVA como flujo de caja (se cobra y se paga trimestralmente)

## Ejemplos
**Input**: "SaaS B2B de gestión de RRHH, pricing 99€/mes, 20 clientes actuales"
**Output**: Proyección 36 meses con crecimiento de 20 a 200 clientes (escenario base), MRR de 1.980€ a 19.800€, break-even en mes 14, costes de equipo (2 devs + 1 comercial), fiscalidad SL.

**Input**: "Tienda online de productos artesanales andaluces"
**Output**: Proyección con ticket medio 35€, estacionalidad (pico Navidad +300%), margen bruto 55%, costes logística, IVA reducido para alimentación, break-even en mes 8.

## Validación
- ¿Los tipos impositivos son correctos para España y el tipo de entidad?
- ¿Se ha considerado el plazo de cobro realista?
- ¿Los escenarios tienen hipótesis explícitas y diferenciadas?
- ¿El cash flow mensual es coherente con ingresos y gastos?
CONTENT,
  ],

  [
    'name' => 'Análisis Competitivo',
    'skill_type' => 'vertical',
    'vertical_id' => 'emprendimiento',
    'priority' => 77,
    'content' => <<<'CONTENT'
## Propósito
Realiza análisis competitivos estructurados para posicionar correctamente un negocio en el mercado español. Aplica cuando el usuario necesita entender su entorno competitivo, definir diferenciación o preparar estrategia de mercado.

## Input Esperado
- Descripción del negocio o producto
- Sector y mercado geográfico
- Competidores conocidos (si los tiene identificados)
- Criterios de comparación prioritarios

## Proceso
1. **Identificación de competidores**: Directos (mismo producto/servicio), indirectos (mismo problema, diferente solución), sustitutos (alternativas no obvias). Incluir competidores españoles y europeos que operen en España.
2. **Matriz comparativa**: Evaluar cada competidor en: pricing, funcionalidades, UX, soporte, presencia en España, opiniones clientes (Trustpilot, Google Reviews).
3. **Análisis de posicionamiento**: Mapa de posicionamiento en 2 ejes relevantes (ej. precio vs. personalización, simplicidad vs. funcionalidades).
4. **Fortalezas y debilidades**: DAFO de cada competidor principal basado en información pública.
5. **Ventanas de oportunidad**: Identificar segmentos desatendidos, funcionalidades ausentes, quejas recurrentes de clientes de competidores.
6. **Barreras de entrada**: Evaluar qué protege a los incumbentes y qué dificulta la entrada de nuevos players.

## Output Esperado
- Tabla comparativa de competidores (mínimo 5)
- Mapa de posicionamiento visual (descripción de ejes y ubicación)
- 3 oportunidades de diferenciación concretas
- Estrategia competitiva recomendada (coste, diferenciación o nicho)

## Restricciones
- NO decir "no hay competencia" — siempre hay alternativas
- NO copiar análisis de mercado estadounidense sin adaptar a España
- NO basar el análisis solo en lo que dicen las webs de competidores — incluir opiniones de usuarios
- NO ignorar competidores locales/regionales españoles

## Ejemplos
**Input**: "App de gestión de gastos para autónomos españoles"
**Output**: Análisis de Quipu, Holded, Billin, Sage, Debitoor. Mapa de posicionamiento (facilidad de uso vs. funcionalidades fiscales), oportunidad en integración con SII/AEAT y asesoría fiscal automatizada.

**Input**: "Plataforma de formación online en sostenibilidad"
**Output**: Comparativa con Coursera, Udemy, UNIR, escuelas de negocio (IE, ESADE), plataformas corporativas. Nicho: formación certificada en ESG para PYMEs españolas con obligaciones de reporting.

## Validación
- ¿Se incluyen al menos 5 competidores relevantes?
- ¿El análisis considera competidores que operan en España?
- ¿Las oportunidades identificadas son accionables?
- ¿El mapa de posicionamiento usa ejes relevantes para el mercado?
CONTENT,
  ],

  [
    'name' => 'Validación de MVP',
    'skill_type' => 'vertical',
    'vertical_id' => 'emprendimiento',
    'priority' => 76,
    'content' => <<<'CONTENT'
## Propósito
Guía al emprendedor en el diseño y ejecución de experimentos de validación de su MVP (Producto Mínimo Viable) con metodología lean. Aplica cuando el usuario quiere validar hipótesis de negocio antes de invertir en desarrollo completo.

## Input Esperado
- Descripción del producto/servicio
- Hipótesis principal a validar
- Recursos disponibles (tiempo, presupuesto, equipo)
- Segmento de clientes objetivo

## Proceso
1. **Definición de hipótesis**: Formular hipótesis falsificable: "Creemos que [segmento] pagará [precio] por [solución] porque [razón]".
2. **Diseño del MVP**: Seleccionar tipo de MVP según hipótesis: landing page, concierge, Wizard of Oz, prototipo clickable, piloto con early adopters.
3. **Métricas de validación**: Definir criterios de éxito ANTES del experimento. Ej: "Validado si >5% de visitantes dejan email" o ">3 de 10 entrevistados dicen que pagarían".
4. **Plan de ejecución**: Paso a paso con herramientas específicas (Carrd/Webflow para landing, Typeform para encuestas, Calendly para entrevistas).
5. **Canales de captación**: Dónde encontrar early adopters en España — comunidades sectoriales, LinkedIn, asociaciones profesionales, grupos de Facebook/Telegram locales.
6. **Análisis de resultados**: Framework para interpretar datos y decidir: pivotar, perseverar o abandonar.

## Output Esperado
- Hipótesis formulada de forma falsificable
- Tipo de MVP recomendado con justificación
- Plan de ejecución en 2-4 semanas
- Criterios de éxito/fracaso cuantificados
- Guión de entrevista de validación (si aplica)

## Restricciones
- NO recomendar construir producto completo como "MVP"
- NO validar solo con amigos y familia — sesgo de confirmación
- NO aceptar "me parece buena idea" como validación — buscar intención de pago
- NO ignorar la fase de discovery (entrevistas) antes de construir

## Ejemplos
**Input**: "Quiero crear una app para conectar cuidadores de mascotas con dueños"
**Output**: MVP tipo landing page + grupo WhatsApp. Validar demanda con landing en Instagram Ads segmentado a dueños de mascotas en Madrid (presupuesto 100€, objetivo 50 registros en 2 semanas). Guión de entrevista para los registrados.

**Input**: "SaaS de automatización de informes ESG para PYMEs"
**Output**: MVP concierge — hacer los informes manualmente para 5 PYMEs piloto, validar willingness-to-pay y friction points. Contactar vía LinkedIn a responsables de RSC.

## Validación
- ¿La hipótesis es falsificable con el experimento diseñado?
- ¿Los criterios de éxito son cuantitativos y definidos a priori?
- ¿El MVP es realmente mínimo (no un producto casi terminado)?
- ¿El experimento se puede ejecutar en ≤4 semanas?
CONTENT,
  ],

  [
    'name' => 'Estrategia de Pricing',
    'skill_type' => 'vertical',
    'vertical_id' => 'emprendimiento',
    'priority' => 75,
    'content' => <<<'CONTENT'
## Propósito
Diseña estrategias de pricing óptimas para productos y servicios en el mercado español, maximizando ingreso y competitividad. Aplica cuando el usuario necesita definir precios, reestructurar su modelo de pricing o evaluar cambios de precio.

## Input Esperado
- Producto o servicio a preciar
- Costes directos e indirectos
- Precios de competidores (si los conoce)
- Segmento de clientes y su sensibilidad al precio
- Modelo de negocio (suscripción, pago único, freemium, por uso)

## Proceso
1. **Análisis de costes**: Calcular coste unitario total incluyendo costes ocultos españoles (SS, IVA que no se recupera en B2C, comisiones de pago).
2. **Análisis de valor**: ¿Cuánto ahorra o genera al cliente? Precio basado en valor, no solo en coste + margen.
3. **Benchmark competitivo**: Posicionar precio vs. competidores directos en España. Considerar poder adquisitivo regional.
4. **Modelo de pricing**: Recomendar estructura (flat rate, tiers, per-seat, usage-based) según tipo de producto y mercado.
5. **Psicología de precios**: Aplicar anclaje, efecto señuelo, precios con 9 (9,99€ vs 10€), paquetes bundle.
6. **Fiscalidad**: Incluir IVA en precio final para B2C. En B2B, mostrar sin IVA. Considerar recargo de equivalencia para comercio minorista.
7. **Test de precio**: Diseñar experimento para validar willingness-to-pay antes de fijar precio definitivo.

## Output Esperado
- Estructura de pricing recomendada con tiers/planes
- Tabla de precios con márgenes por tier
- Comparativa con competidores
- Plan de comunicación de precios (cómo presentar el valor)
- Estrategia de descuentos y promociones

## Restricciones
- NO copiar pricing de mercados con diferente poder adquisitivo (USA, UK)
- NO olvidar que el IVA español es 21% (impacta margen en B2C)
- NO fijar precios solo por costes sin considerar valor percibido
- NO crear demasiados tiers (3-4 es óptimo, más genera parálisis de decisión)

## Ejemplos
**Input**: "SaaS de facturación para autónomos, coste unitario 3€/mes"
**Output**: 3 tiers — Básico (9,99€/mes: facturas ilimitadas), Pro (19,99€/mes: + gastos + modelo 303), Premium (34,99€/mes: + asesoría fiscal). Posicionamiento entre Billin y Holded.

**Input**: "Curso online de fotografía de 20 horas"
**Output**: Precio ancla 297€, precio lanzamiento 197€, paquete premium con mentoría 497€. Comparativa con cursos similares en Domestika/Udemy. Estrategia de early bird.

## Validación
- ¿Los precios son competitivos para el mercado español?
- ¿El margen cubre costes + beneficio razonable?
- ¿La estructura de tiers tiene lógica de upgrade clara?
- ¿Se ha considerado el IVA en precios B2C?
CONTENT,
  ],

  [
    'name' => 'Estrategia Go-to-Market',
    'skill_type' => 'vertical',
    'vertical_id' => 'emprendimiento',
    'priority' => 74,
    'content' => <<<'CONTENT'
## Propósito
Diseña la estrategia de lanzamiento al mercado para productos y servicios en España, definiendo canales, messaging y plan de ejecución. Aplica cuando el usuario está preparando un lanzamiento o quiere escalar su go-to-market.

## Input Esperado
- Producto/servicio a lanzar
- Segmento de clientes objetivo
- Presupuesto de marketing disponible
- Timeline deseado de lanzamiento
- Canales de venta actuales (si los tiene)

## Proceso
1. **ICP (Ideal Customer Profile)**: Definir perfil detallado del cliente ideal — demografía, empresa, dolor, presupuesto, proceso de decisión.
2. **Messaging**: Crear propuesta de valor clara, tagline y elevator pitch. Adaptar al contexto cultural español.
3. **Canales de adquisición**: Priorizar por CAC y velocidad — content marketing, paid ads (Google Ads, Meta), PR, partnerships, ventas directas, eventos.
4. **Funnel de conversión**: Mapear el journey desde awareness hasta compra. Identificar fricción y optimizar cada paso.
5. **Plan de lanzamiento**: Cronograma de 12 semanas: pre-lanzamiento (buzz), lanzamiento (push), post-lanzamiento (iteración).
6. **Métricas**: KPIs por fase — awareness (impresiones, tráfico), consideración (registros, demos), conversión (ventas, MRR), retención (churn, NPS).
7. **Partnerships**: Identificar alianzas estratégicas en el ecosistema español (asociaciones, cámaras de comercio, empresas complementarias).

## Output Esperado
- Documento de GTM con ICP, messaging y canales priorizados
- Cronograma de lanzamiento en 12 semanas
- Presupuesto desglosado por canal con ROI esperado
- Plantilla de tracking de métricas
- Quick wins para primeras 2 semanas

## Restricciones
- NO crear planes que requieran presupuestos irrealistas para una startup española
- NO ignorar canales offline relevantes en España (ferias, networking presencial)
- NO copiar estrategias de growth hacking de Silicon Valley sin adaptación
- NO subestimar el ciclo de venta B2B en España (más largo que en USA)

## Ejemplos
**Input**: "Lanzamos un software de gestión para clínicas dentales en España"
**Output**: ICP (clínicas de 2-5 sillones, facturación 300K-1M€), canal principal LinkedIn + visita comercial, partnership con distribuidores de material dental, presencia en Expodental, trial gratuito 30 días, webinars de gestión.

**Input**: "Marketplace de servicios de limpieza para particulares en Madrid"
**Output**: ICP (profesionales 30-50 años, doble ingreso, zonas residenciales), canal principal Google Ads + Instagram local, programa de referidos, lanzamiento por barrios (Salamanca → Chamberí → Retiro), pricing introductorio.

## Validación
- ¿El ICP es suficientemente específico para guiar la ejecución?
- ¿Los canales son realistas para el presupuesto disponible?
- ¿El cronograma tiene hitos medibles cada 2-4 semanas?
- ¿Se han considerado particularidades del mercado español?
CONTENT,
  ],

  // --- AGROCONECTA ---

  [
    'name' => 'Listado de Producto Agrícola',
    'skill_type' => 'vertical',
    'vertical_id' => 'agroconecta',
    'priority' => 80,
    'content' => <<<'CONTENT'
## Propósito
Crea fichas de producto optimizadas para la venta online de productos agroalimentarios andaluces, cumpliendo normativa de etiquetado y maximizando atractivo comercial. Aplica cuando un productor necesita publicar sus productos en el marketplace.

## Input Esperado
- Nombre del producto y variedad
- Origen geográfico (municipio, comarca, provincia)
- Tipo de producción (ecológica, integrada, convencional)
- Formato y unidades disponibles
- Precio de venta al público
- Certificaciones (si las tiene)
- Fotos disponibles (descripción)

## Proceso
1. **Título optimizado**: Formato [Producto] [Variedad] [Origen] [Certificación]. Ej: "Aceite de Oliva Virgen Extra Picual - Sierra de Cazorla - Ecológico".
2. **Descripción comercial**: Storytelling del producto — tierra, proceso, tradición familiar. Conectar emocionalmente con el comprador urbano.
3. **Ficha técnica**: Información obligatoria según Reglamento UE 1169/2011 — ingredientes, alérgenos, modo de conservación, peso neto, lote, fecha de caducidad/consumo preferente.
4. **Sellos y certificaciones**: Destacar DOP, IGP, Producción Ecológica UE, Calidad Certificada Junta de Andalucía.
5. **Sugerencias de uso**: Maridajes, recetas, ocasiones de consumo. Conectar producto con experiencias gastronómicas.
6. **SEO agrícola**: Keywords relevantes para búsquedas de producto agroalimentario (venta directa, del campo a la mesa, producto local).

## Output Esperado
- Título de producto optimizado
- Descripción comercial (150-200 palabras)
- Ficha técnica completa (cumplimiento normativo)
- 3 sugerencias de uso/receta
- Tags y categorías recomendadas para el marketplace

## Restricciones
- NO usar claims nutricionales o de salud sin base legal (Reglamento CE 1924/2006)
- NO inventar certificaciones o denominaciones de origen
- NO usar "casero" o "artesanal" si no cumple definición legal
- NO omitir información obligatoria de etiquetado

## Ejemplos
**Input**: "Tomates raf de Almería, cultivo en invernadero, caja de 5kg"
**Output**: "Tomate Raf de La Cañada (Almería) - Caja 5kg - Temporada". Descripción: historia del raf almeriense, textura y sabor, recolección en punto óptimo. Ficha con conservación (no refrigerar), caducidad, uso (ensalada, gazpacho, tostada con AOVE).

**Input**: "Miel de romero ecológica, apicultor de Jaén, tarros de 500g"
**Output**: "Miel de Romero Ecológica - Sierra de Segura (Jaén) - 500g". Historia del apicultor, trashumancia, notas de cata, sello ecológico UE + Calidad Certificada. Usos: endulzante natural, con queso curado, infusiones.

## Validación
- ¿El listado cumple la normativa de etiquetado UE?
- ¿La descripción transmite autenticidad sin exagerar?
- ¿Se han incluido todas las certificaciones aplicables?
- ¿Las sugerencias de uso son relevantes y prácticas?
CONTENT,
  ],

  [
    'name' => 'Marketing Estacional Agrícola',
    'skill_type' => 'vertical',
    'vertical_id' => 'agroconecta',
    'priority' => 79,
    'content' => <<<'CONTENT'
## Propósito
Genera contenido de marketing alineado con el calendario agrícola andaluz, aprovechando la estacionalidad como ventaja comercial. Aplica cuando un productor necesita planificar comunicaciones o promociones según temporada de cosecha.

## Input Esperado
- Productos del agricultor con temporadas de cosecha
- Canales de comunicación disponibles (RRSS, email, WhatsApp)
- Público objetivo (consumidor final, horeca, distribuidor)
- Presupuesto de marketing (bajo/medio/alto)

## Proceso
1. **Calendario de cosecha**: Mapear productos a meses — aceituna (nov-feb), fresa (ene-may), cereza (may-jun), vendimia (ago-oct), cítricos (nov-may).
2. **Contenido pre-temporada**: Crear expectativa 2-4 semanas antes. "Se acerca la campaña del aceite nuevo" con fotos del olivar.
3. **Contenido en temporada**: Storytelling de recolección, procesos, fotos/vídeos del campo. Transmisiones en directo de recogida.
4. **Ofertas de temporada**: Pre-venta, cajas de temporada, packs regalo (Navidad, Día de la Madre), suscripciones de producto fresco.
5. **Post-temporada**: Recetas de conservación, producto en conserva, anticipar siguiente temporada.
6. **Efemérides**: Día Mundial del Olivo, Semana de la Dieta Mediterránea, fiestas locales gastronómicas (vendimia de Montilla, feria del jamón de Aracena).

## Output Esperado
- Calendario editorial trimestral con 12+ publicaciones
- Textos para RRSS (Instagram, Facebook) adaptados
- Ideas de campaña estacional con mecánica promocional
- Hashtags relevantes por temporada y producto

## Restricciones
- NO recomendar promociones que devalúen el producto premium
- NO ignorar la estacionalidad real del campo andaluz
- NO proponer acciones que requieran equipo de marketing profesional si el agricultor trabaja solo
- NO usar stock photos — siempre recomendar contenido real del campo

## Ejemplos
**Input**: "Productor de AOVE en Baena, temporada nov-feb, vende por RRSS y mercados"
**Output**: Calendario nov: "Primer día de campaña" con foto del verdeo. Dic: "Aceite nuevo ya disponible" + pack regalo Navidad. Ene: "Cómo distinguir un buen AOVE" (contenido educativo). Feb: "Últimas unidades de cosecha temprana".

**Input**: "Finca de fresas en Huelva, venta B2C y a restaurantes"
**Output**: Ene: pre-venta "Reserva tu caja de fresón de Huelva". Feb-Mar: fotos de recogida + envío mismo día. Abr: recetas (gazpacho de fresa, tarta). May: "Última semana de temporada" urgencia.

## Validación
- ¿El calendario respeta las temporadas reales de los productos?
- ¿El contenido es ejecutable por un agricultor con smartphone?
- ¿Las promociones mantienen la percepción de calidad?
- ¿Se han incluido fechas clave del sector?
CONTENT,
  ],

  [
    'name' => 'Historia de Trazabilidad',
    'skill_type' => 'vertical',
    'vertical_id' => 'agroconecta',
    'priority' => 78,
    'content' => <<<'CONTENT'
## Propósito
Transforma los datos de trazabilidad de un producto agroalimentario en una historia de transparencia atractiva para el consumidor. Aplica cuando un productor quiere comunicar el recorrido de su producto del campo a la mesa.

## Input Esperado
- Producto y variedad
- Datos de la finca/parcela (ubicación, extensión, tipo de suelo)
- Proceso de producción (cultivo, recolección, procesado, envasado)
- Certificaciones y controles de calidad
- Logística (almacenamiento, transporte, tiempos)

## Proceso
1. **Origen**: Contar la historia del terroir — suelo, clima, agua, microclima andaluz. Coordenadas o referencia geográfica reconocible.
2. **Cultivo**: Prácticas agrícolas (cuándo se plantó, cómo se cuida, qué técnicas se usan). Si es ecológico, explicar la diferencia práctica.
3. **Recolección**: Momento óptimo, método (manual vs. mecanizado), cuidado del producto. El factor humano.
4. **Procesado**: Transformación si aplica (almazara para aceite, bodega para vino, obrador para conservas). Tiempos y temperaturas.
5. **Control de calidad**: Análisis, catas, certificaciones. Números de registro sanitario, lotes.
6. **Del productor al consumidor**: Cadena de distribución, km recorridos, huella de carbono si se puede calcular.

## Output Esperado
- Narrativa de trazabilidad en formato storytelling (300-500 palabras)
- Infografía textual del recorrido (timeline del producto)
- Datos clave para código QR de trazabilidad
- Versión corta para etiqueta (50 palabras)

## Restricciones
- NO inventar datos de trazabilidad — solo usar información real proporcionada
- NO exagerar prácticas sostenibles sin evidencia
- NO usar tecnicismos que el consumidor no entienda
- NO omitir procesos intermedios relevantes (manipulación, almacenamiento)

## Ejemplos
**Input**: "AOVE Picual, finca El Lentiscar en Jaén, recolección en verde con vibrador, almazara propia en frío a <27°C"
**Output**: Historia desde los olivos centenarios de Sierra Mágina hasta el envasado en 24h. Timeline: "Octubre: vareo y recolección selectiva → Misma mañana: entrada en almazara → Extracción en frío (24°C) → Decantación 48h → Envasado y numeración de lote → Tu mesa en 72h".

## Validación
- ¿Todos los datos de la trazabilidad son verificables?
- ¿La historia es emocionante pero veraz?
- ¿El consumidor entiende el recorrido completo del producto?
- ¿Se incluyen los elementos obligatorios (lote, registro sanitario)?
CONTENT,
  ],

  [
    'name' => 'Certificación de Calidad',
    'skill_type' => 'vertical',
    'vertical_id' => 'agroconecta',
    'priority' => 77,
    'content' => <<<'CONTENT'
## Propósito
Asesora al productor agroalimentario sobre certificaciones de calidad aplicables a su producto y guía en el proceso de obtención. Aplica cuando un productor quiere certificarse o conocer qué sellos puede obtener.

## Input Esperado
- Tipo de producto y proceso de producción
- Ubicación de la explotación (CCAA, provincia)
- Prácticas actuales de producción
- Certificaciones que ya posee (si alguna)
- Objetivo comercial (mercado local, nacional, exportación)

## Proceso
1. **Inventario de certificaciones aplicables**:
   - **DOP/IGP**: Verificar si el producto y zona están amparados por denominación existente.
   - **Producción Ecológica**: Reglamento UE 2018/848. CAAE como certificador principal en Andalucía.
   - **Producción Integrada**: Decreto 245/2003 de la Junta de Andalucía.
   - **GlobalGAP**: Para acceso a gran distribución.
   - **Calidad Certificada**: Marca de garantía de la Junta de Andalucía.
   - **ISO 22000/FSSC 22000**: Seguridad alimentaria para transformadores.
2. **Requisitos y plazos**: Detallar requisitos específicos para cada certificación aplicable. Período de conversión (2-3 años para ecológico).
3. **Costes**: Estimar coste de certificación, auditorías anuales, adaptaciones necesarias.
4. **Subvenciones**: Identificar ayudas PAC, programas de desarrollo rural, ayudas CCAA para conversión a ecológico.
5. **ROI de la certificación**: Calcular el sobreprecio que justifica la inversión en certificación.

## Output Esperado
- Lista priorizada de certificaciones recomendadas
- Roadmap de obtención (pasos, plazos, costes)
- Subvenciones y ayudas disponibles
- Análisis de ROI por certificación
- Contactos de certificadoras en Andalucía

## Restricciones
- NO garantizar la obtención de ninguna certificación
- NO simplificar el proceso de conversión a ecológico (son 2-3 años reales)
- NO ignorar costes ocultos (adaptaciones de instalaciones, formación)
- NO recomendar certificaciones que no apliquen al producto o zona

## Ejemplos
**Input**: "Productor de almendras en Granada, convencional, quiero pasar a ecológico"
**Output**: Roadmap: contactar CAAE, período de conversión 2 años, adaptaciones necesarias (sin herbicidas, abono orgánico), coste certificación ~600€/año, subvención PAC eco ~300€/ha. ROI: sobreprecio almendra eco +40%.

**Input**: "Bodega en Montilla-Moriles, ya tengo DO"
**Output**: Complementar con Producción Integrada (reduce fitosanitarios, valorado en exportación), considerar Calidad Certificada para vinos de gama alta, GlobalGAP si vende a Mercadona/Carrefour.

## Validación
- ¿Las certificaciones recomendadas son aplicables al producto y zona?
- ¿Los costes y plazos son realistas?
- ¿Se han identificado subvenciones vigentes?
- ¿El ROI estimado es conservador y justificado?
CONTENT,
  ],

  [
    'name' => 'Contenido de Recetas',
    'skill_type' => 'vertical',
    'vertical_id' => 'agroconecta',
    'priority' => 76,
    'content' => <<<'CONTENT'
## Propósito
Genera recetas atractivas que utilicen los productos del agricultor como ingrediente protagonista, sirviendo como herramienta de marketing de contenido. Aplica cuando un productor quiere crear contenido que impulse ventas a través del uso del producto.

## Input Esperado
- Producto/s del agricultor a promocionar
- Público objetivo (familias, foodies, hostelería)
- Nivel de dificultad deseado (fácil, medio, elaborado)
- Temporada actual
- Preferencias dietéticas a considerar (sin gluten, vegano, etc.)

## Proceso
1. **Selección de recetas**: Elegir 3-5 recetas donde el producto sea protagonista indiscutible. Mezclar: una tradicional andaluza, una moderna/fusión, una rápida (<20 min).
2. **Formato de receta**: Nombre atractivo, foto sugerida, tiempo total, raciones, dificultad, ingredientes con cantidades exactas, pasos numerados claros.
3. **Storytelling gastronómico**: Contextualizar la receta — origen, tradición, maridaje, ocasión ideal.
4. **Consejos de producto**: Cómo elegir el mejor ejemplar, conservación, sustituciones.
5. **Adaptaciones**: Versión sin gluten, vegana o baja en sal cuando sea posible.
6. **Call to action**: Vincular la receta con la compra del producto en el marketplace.

## Output Esperado
- 3 recetas completas con formato estandarizado
- Descripción de foto sugerida para cada receta
- Tips de conservación del producto
- Hashtags para compartir en RRSS
- CTA para compra del producto

## Restricciones
- NO crear recetas que no tengan el producto como estrella
- NO usar ingredientes difíciles de encontrar en España
- NO ignorar alergias comunes (gluten, frutos secos, lácteos)
- NO copiar recetas con copyright — crear originales inspiradas en tradición

## Ejemplos
**Input**: "Aceite de oliva virgen extra picual, para consumidores finales"
**Output**: 1) Salmorejo cordobés tradicional (fácil, 15min). 2) Helado de AOVE con sal Maldon (elaborado, wow factor). 3) Tosta de AOVE con tomate raf y jamón (5 min, cotidiana). Cada receta con CTA: "Prueba esta receta con nuestro AOVE Picual de Sierra Mágina".

**Input**: "Fresas de Huelva, temporada febrero-mayo"
**Output**: 1) Gazpacho de fresa y tomate (innovador, primavera). 2) Tarta de fresas con nata (clásica, celebraciones). 3) Ensalada de fresas, queso de cabra y nueces (rápida, saludable).

## Validación
- ¿Las recetas son reproducibles con instrucciones claras?
- ¿El producto del agricultor es el ingrediente estrella?
- ¿Se adaptan a la temporada actual?
- ¿El contenido funciona como herramienta de marketing?
CONTENT,
  ],

  [
    'name' => 'Propuesta Comercial B2B',
    'skill_type' => 'vertical',
    'vertical_id' => 'agroconecta',
    'priority' => 75,
    'content' => <<<'CONTENT'
## Propósito
Genera propuestas comerciales profesionales para que productores agroalimentarios vendan a canal HORECA (hoteles, restaurantes, catering) y distribuidores. Aplica cuando un productor quiere acceder al canal profesional.

## Input Esperado
- Catálogo de productos del productor con precios y formatos
- Cliente objetivo (restaurante, hotel, catering, distribuidor, tienda gourmet)
- Volumen de producción disponible
- Zona de distribución
- Certificaciones y sellos de calidad

## Proceso
1. **Perfil del comprador**: Adaptar la propuesta al tipo de cliente — un restaurante gastronómico valora exclusividad, un catering valora volumen y precio, un distribuidor valora márgenes.
2. **Presentación del productor**: Quiénes somos, dónde estamos, nuestra historia, capacidad de producción.
3. **Catálogo profesional**: Productos con formatos HORECA (granel, cajas profesionales), precios por volumen, condiciones de entrega.
4. **Valor diferencial**: Producto local km0, trazabilidad completa, historia detrás del producto, exclusividad territorial.
5. **Condiciones comerciales**: Mínimos de pedido, frecuencia de entrega, forma de pago (anticipado, 30-60 días), transporte.
6. **Propuesta de colaboración**: Ideas de co-branding — menú con productos del productor, evento de cata en restaurante.

## Output Esperado
- Propuesta comercial en formato profesional (1-2 páginas)
- Tarifa HORECA con precios por volumen
- Ficha técnica por producto (para el chef/comprador)
- Email de presentación para primer contacto
- Argumentario de venta en 3 puntos clave

## Restricciones
- NO usar precios de venta al público para canal profesional (descuento HORECA 20-40%)
- NO prometer entregas que no pueda cumplir
- NO ignorar la importancia de muestras gratuitas para el primer pedido
- NO olvidar requisitos legales (facturación, registro sanitario)

## Ejemplos
**Input**: "Productor de queso artesanal en Cádiz, quiero vender a restaurantes de la costa"
**Output**: Propuesta con catálogo de 4 quesos (payoyo, cabra, oveja, mezcla), precios HORECA (-30%), mínimo 5kg por pedido, entrega 48h costa gaditana. Email de presentación mencionando maridaje con vinos de la tierra.

**Input**: "Cooperativa de cítricos en Valencia, quiero entrar en Mercamadrid"
**Output**: Propuesta para mayorista con volúmenes (pallets), calidad certificada GlobalGAP, calendario de variedades (mandarina oct-ene, naranja nov-may), transporte refrigerado incluido, condiciones de pago a 30 días.

## Validación
- ¿La propuesta es profesional y adaptada al tipo de comprador?
- ¿Los precios HORECA incluyen margen razonable para el canal?
- ¿Las condiciones de entrega son realistas?
- ¿Se incluyen todos los datos legales necesarios?
CONTENT,
  ],

  // --- COMERCIOCONECTA ---

  [
    'name' => 'Diseño de Oferta Flash',
    'skill_type' => 'vertical',
    'vertical_id' => 'comercioconecta',
    'priority' => 80,
    'content' => <<<'CONTENT'
## Propósito
Crea ofertas flash irresistibles para comercios locales que generen tráfico inmediato y urgencia de compra. Aplica cuando un comerciante quiere promocionar un producto o servicio con descuento temporal.

## Input Esperado
- Producto o servicio a promocionar
- Precio original y margen disponible para descuento
- Duración deseada de la oferta (horas, días)
- Canal de difusión (RRSS, WhatsApp Business, escaparate digital, email)
- Objetivo (liquidar stock, atraer nuevos clientes, aumentar ticket medio)

## Proceso
1. **Mecánica de la oferta**: Definir tipo — descuento directo, 2x1, regalo con compra, precio especial para primeros X clientes, happy hour.
2. **Urgencia y escasez**: Crear sensación de limitación real — "Solo 48 horas", "Primeras 20 unidades", "Solo este viernes".
3. **Copy de la oferta**: Título impactante (8 palabras máx), beneficio claro, condiciones, CTA. Adaptar a español coloquial-comercial.
4. **Diseño para canal**: Formatos según canal — cuadrado para Instagram, vertical para Stories/WhatsApp, banner para web.
5. **Cálculo de rentabilidad**: Verificar que incluso con descuento, la operación genera margen positivo o el objetivo (captación) justifica la inversión.
6. **Seguimiento**: Métrica de éxito y acción post-oferta (captar datos del cliente para futuras comunicaciones).

## Output Esperado
- Texto completo de la oferta para 2 canales
- Mecánica detallada (condiciones, exclusiones)
- Checklist de ejecución
- Plantilla de seguimiento de resultados
- Sugerencia de oferta siguiente para crear hábito

## Restricciones
- NO proponer descuentos que dejen al comerciante sin margen
- NO crear ofertas engañosas (subir precio para luego "rebajarlo")
- NO olvidar la normativa española de rebajas y promociones (Ley 7/1996)
- NO proponer ofertas que canibalicen ventas regulares

## Ejemplos
**Input**: "Peluquería, quiero llenar las mañanas de lunes a miércoles"
**Output**: "Mañanas VIP: -30% en todos los servicios de L-X de 10h a 13h. Solo esta semana. Reserva tu cita por WhatsApp". Difusión: Stories Instagram + mensaje a base de datos WhatsApp Business.

**Input**: "Tienda de ropa, quiero liquidar colección de invierno"
**Output**: "Flash Sale: 48 HORAS | Hasta -50% en selección de invierno. Primeros 30 clientes: regalo sorpresa con compra +50€". Post Instagram + Stories con countdown.

## Validación
- ¿La oferta es rentable incluso con el descuento?
- ¿La urgencia es real y creíble?
- ¿El copy es claro y atractivo para el público local?
- ¿Cumple normativa de promociones comerciales?
CONTENT,
  ],

  [
    'name' => 'Contenido SEO Local',
    'skill_type' => 'vertical',
    'vertical_id' => 'comercioconecta',
    'priority' => 79,
    'content' => <<<'CONTENT'
## Propósito
Genera contenido optimizado para posicionamiento local en buscadores, aumentando la visibilidad del comercio en búsquedas geográficas. Aplica cuando un comerciante quiere aparecer en búsquedas tipo "X cerca de mí" o "[servicio] en [ciudad]".

## Input Esperado
- Tipo de comercio y servicios/productos principales
- Ubicación exacta (ciudad, barrio)
- Google Business Profile actual (si tiene)
- Competidores locales principales
- Diferenciadores del comercio

## Proceso
1. **Auditoría Google Business Profile**: Verificar que nombre, dirección, teléfono (NAP) sean consistentes en toda la web. Categoría correcta, horarios actualizados, fotos de calidad.
2. **Keywords locales**: Investigar búsquedas tipo "[servicio] + [ubicación]". Incluir variaciones (barrio, zona, "cerca de", "mejor").
3. **Contenido para web**: Crear textos para página principal y servicios que incluyan keywords locales de forma natural. No keyword stuffing.
4. **Google Posts**: Crear plantilla de publicaciones semanales en Google Business (ofertas, novedades, eventos).
5. **Estrategia de reseñas**: Plan para conseguir reseñas positivas legítimas — cuándo pedirlas, cómo facilitar el proceso, cómo responder a negativas.
6. **Schema markup local**: Recomendar datos estructurados LocalBusiness para la web del comercio.
7. **Directorios locales**: Listar directorios españoles relevantes para el sector (Páginas Amarillas, QDQ, directorios sectoriales, guías locales).

## Output Esperado
- Lista de keywords locales prioritarias (20+)
- Textos optimizados para página principal y servicios
- Plantilla de Google Post semanal
- Plan de captación de reseñas
- Checklist de presencia en directorios

## Restricciones
- NO crear reseñas falsas ni incentivar con descuentos a cambio de reseñas
- NO sobreoptimizar con keywords — el texto debe sonar natural
- NO ignorar Google Business Profile (es el factor #1 de SEO local)
- NO prometer posiciones en Google — SEO es proceso, no resultado instantáneo

## Ejemplos
**Input**: "Óptica en el barrio de Triana, Sevilla"
**Output**: Keywords: "óptica Triana", "gafas graduadas Sevilla", "optometrista cerca Triana", "revisión vista Sevilla centro". Texto web optimizado con mención natural del barrio y servicios. Plan de reseñas: pedir al entregar gafas graduadas (momento de satisfacción).

**Input**: "Taller mecánico en Almería capital"
**Output**: Keywords: "taller mecánico Almería", "ITV Almería", "cambio de aceite Almería centro", "mecánico de confianza Almería". Google Posts semanales con consejos de mantenimiento. Directorios: Páginas Amarillas, Euromaster (si aplica), TripAdvisor (para turistas).

## Validación
- ¿Las keywords tienen volumen de búsqueda local real?
- ¿El contenido suena natural al leer en voz alta?
- ¿Se ha optimizado Google Business Profile completamente?
- ¿El plan de reseñas es ético y sostenible?
CONTENT,
  ],

  [
    'name' => 'Retención de Clientes',
    'skill_type' => 'vertical',
    'vertical_id' => 'comercioconecta',
    'priority' => 78,
    'content' => <<<'CONTENT'
## Propósito
Diseña estrategias de fidelización y retención para comercios locales, aumentando la frecuencia de compra y el valor de vida del cliente. Aplica cuando un comerciante quiere que sus clientes vuelvan más a menudo y compren más.

## Input Esperado
- Tipo de comercio y ticket medio
- Frecuencia de compra actual estimada
- Base de clientes actual (tamaño aproximado)
- Canales de contacto con clientes (WhatsApp, email, RRSS)
- Acciones de fidelización actuales (si las tiene)

## Proceso
1. **Análisis de retención**: Estimar tasa de retención actual. En comercio local español, la media es 30-40%. Objetivo: superar 50%.
2. **Programa de fidelización simple**: Diseñar sistema accesible — tarjeta de sellos (física o digital), puntos, descuento por recurrencia. Que sea fácil de gestionar para un comercio pequeño.
3. **Comunicación post-venta**: Estrategia de seguimiento — WhatsApp Business con mensaje personalizado 7 días después de la compra.
4. **Segmentación básica**: Dividir clientes en: recurrentes (VIP), ocasionales, inactivos (>3 meses sin comprar). Acción diferente para cada grupo.
5. **Reactivación**: Campaña para clientes inactivos — oferta especial "te echamos de menos", comunicación personalizada.
6. **Experiencia en tienda**: Elementos que generan recuerdo y boca a boca — packaging especial, detalle sorpresa, atención memorable.

## Output Esperado
- Programa de fidelización diseñado (mecánica + beneficios)
- Calendario de comunicaciones de retención (mensual)
- Plantillas de mensajes por segmento
- Métricas de seguimiento
- 5 ideas de "momento wow" para el comercio

## Restricciones
- NO proponer programas de fidelización complejos que el comerciante no pueda gestionar
- NO recomendar exceso de comunicaciones (spam) — el cliente local valora el respeto
- NO ignorar el trato personal (el comercio local compite con cercanía)
- NO proponer sistemas que requieran inversión tecnológica elevada

## Ejemplos
**Input**: "Panadería artesanal, ticket medio 5€, clientes del barrio"
**Output**: Tarjeta de sellos: "Cada 10 barras, una gratis". WhatsApp Business: aviso de pan especial del viernes. Detalle sorpresa: cookie gratis con compra >10€ los miércoles. Reactivación: mensaje a inactivos "Tenemos un pan nuevo que tienes que probar".

**Input**: "Tienda de electrónica, ticket medio 150€, compradores esporádicos"
**Output**: Programa de puntos digital (1€ = 1 punto, 200 puntos = 10€ descuento). Follow-up post-compra: tutorial de uso + encuesta satisfacción. Reactivación trimestral con novedades relevantes.

## Validación
- ¿El programa de fidelización es gestionable por un comercio pequeño?
- ¿Las comunicaciones son valiosas y no intrusivas?
- ¿Se segmentan los clientes de forma práctica?
- ¿Las métricas son medibles con herramientas simples?
CONTENT,
  ],

  [
    'name' => 'Alerta de Inventario',
    'skill_type' => 'vertical',
    'vertical_id' => 'comercioconecta',
    'priority' => 77,
    'content' => <<<'CONTENT'
## Propósito
Genera comunicaciones efectivas para gestionar situaciones de inventario — productos nuevos, últimas unidades, reposiciones y productos descatalogados. Aplica cuando el comerciante necesita comunicar cambios de stock a sus clientes.

## Input Esperado
- Tipo de alerta (producto nuevo, stock bajo, reposición, descatalogado)
- Producto afectado y detalles
- Canal de comunicación (RRSS, WhatsApp, email, escaparate)
- Público objetivo (todos los clientes, segmento específico)

## Proceso
1. **Tipología de alerta**: Clasificar según urgencia e impacto — novedad (curiosidad), escasez (urgencia), reposición (alivio), descatalogado (última oportunidad).
2. **Copy por tipo**:
   - **Novedad**: Generar curiosidad y deseo. "Acaba de llegar y ya es nuestro favorito".
   - **Últimas unidades**: Crear urgencia real. "Quedan 3 unidades. Sin reposición hasta septiembre".
   - **Reposición**: Notificar a quienes lo esperaban. "¡Ya está de vuelta! El [producto] que nos pedíais".
   - **Descatalogado**: Última oportunidad. "Últimas unidades al -40%. Cuando se acabe, se acabó".
3. **Timing**: Cuándo enviar — novedades el lunes/martes (atención alta), ofertas de stock el jueves/viernes (compra impulsiva fin de semana).
4. **Segmentación**: Alertas de reposición solo a quienes preguntaron. Novedades a toda la base.
5. **Automatización simple**: Configurar alertas recurrentes con WhatsApp Business o Instagram Shopping.

## Output Esperado
- Texto de alerta listo para enviar por canal especificado
- Sugerencia de timing óptimo
- Versión para RRSS y versión para WhatsApp
- Seguimiento de efectividad (qué medir)

## Restricciones
- NO crear falsas escasez (decir "últimas unidades" si hay stock abundante)
- NO bombardear a clientes con alertas de inventario constantes
- NO enviar alertas de reposición sin segmentar (solo a interesados)
- NO usar alarmismo en las comunicaciones

## Ejemplos
**Input**: "Han llegado las zapatillas de temporada nueva a mi tienda de deportes"
**Output**: Instagram: "NUEVA TEMPORADA | Ya están aquí las [marca] que estabais esperando. Tallas 36-46. Pásate a probarlas antes de que vuelen ⚡". WhatsApp a clientes habituales: "Hola [nombre], han llegado las nuevas [marca]. ¿Te guardamos tu talla?"

**Input**: "Quedan 5 unidades de un vino que no vamos a reponer"
**Output**: "🍷 ÚLTIMAS 5 BOTELLAS | [Nombre del vino] - Descatalogado. -25% hasta agotar existencias. Para los que saben lo que tenían". Stories con countdown.

## Validación
- ¿La alerta es honesta sobre la situación de stock?
- ¿El tono es apropiado para el tipo de alerta?
- ¿Se ha segmentado correctamente la audiencia?
- ¿El timing de envío maximiza la respuesta?
CONTENT,
  ],

  [
    'name' => 'Respuesta a Reseñas',
    'skill_type' => 'vertical',
    'vertical_id' => 'comercioconecta',
    'priority' => 76,
    'content' => <<<'CONTENT'
## Propósito
Redacta respuestas profesionales y auténticas a reseñas online (Google, TripAdvisor, RRSS), tanto positivas como negativas, protegiendo la reputación del comercio. Aplica cuando un comerciante recibe una reseña y necesita responder adecuadamente.

## Input Esperado
- Texto de la reseña recibida
- Plataforma (Google, TripAdvisor, Facebook, Instagram)
- Contexto real de la situación (qué pasó realmente)
- Puntuación (1-5 estrellas)
- Historial del cliente (conocido/desconocido, recurrente/puntual)

## Proceso
1. **Clasificación**: Positiva (4-5★), neutra (3★), negativa (1-2★), troll/falsa.
2. **Respuesta a positivas**: Agradecer personalizando (no copiar-pegar la misma respuesta siempre), reforzar el aspecto mencionado, invitar a volver. Incluir nombre del cliente si está visible.
3. **Respuesta a negativas**: CARE model — Comprender (empatía), Asumir (si hay error), Resolver (acción concreta), Extender (invitación a segunda oportunidad).
4. **Respuesta a trolls/falsas**: Tono profesional, desmentir con hechos si es necesario, reportar a la plataforma.
5. **Timing**: Responder en <24h para negativas, <48h para positivas.
6. **Escalada offline**: Si el problema es grave, ofrecer resolver por teléfono/email — no debatir públicamente.

## Output Esperado
- Respuesta redactada lista para publicar
- Nota interna de acción (si se requiere corrección interna)
- Sugerencia de prevención para evitar reseñas similares
- Si es negativa: plan de recuperación del cliente

## Restricciones
- NO responder con agresividad o sarcasmo, NUNCA
- NO revelar datos privados del cliente en la respuesta pública
- NO ignorar las reseñas negativas — el silencio es peor
- NO usar plantillas genéricas evidentes ("Gracias por su visita, esperamos verle pronto")
- NO culpar al cliente públicamente aunque tenga la culpa

## Ejemplos
**Input**: "1★ - 'Fui a recoger mi pedido y no estaba preparado. Perdí 20 minutos esperando. Inaceptable.'"
**Output**: "Hola [nombre], lamento mucho la espera que tuviste. Tienes toda la razón, 20 minutos de espera no es el servicio que queremos dar. He revisado lo que pasó y hemos ajustado nuestro proceso de preparación para que no vuelva a ocurrir. Me gustaría compensarte personalmente — ¿podrías escribirnos al [email/WhatsApp]? Gracias por ayudarnos a mejorar."

**Input**: "5★ - 'Siempre un trato excelente. El pan de masa madre es el mejor del barrio.'"
**Output**: "¡Gracias, [nombre]! Nos alegra mucho saber que disfrutas de nuestro pan de masa madre — lo hacemos con mucho mimo cada madrugada. ¡Te esperamos pronto! Un saludo de todo el equipo."

## Validación
- ¿La respuesta es personalizada (no genérica)?
- ¿En negativas, se ofrece solución concreta?
- ¿El tono es profesional y empático?
- ¿Se resuelve el conflicto sin escalar públicamente?
CONTENT,
  ],

  // --- SERVICIOSCONECTA ---

  [
    'name' => 'Resumen de Caso',
    'skill_type' => 'vertical',
    'vertical_id' => 'serviciosconecta',
    'priority' => 80,
    'content' => <<<'CONTENT'
## Propósito
Genera resúmenes ejecutivos de casos profesionales (legal, consultoría, asesoría) para comunicación interna o al cliente, ahorrando tiempo en documentación. Aplica cuando un profesional de servicios necesita sintetizar un caso complejo.

## Input Esperado
- Descripción del caso o expediente
- Partes involucradas
- Estado actual del caso
- Documentos o notas relevantes
- Audiencia del resumen (interno/cliente/terceros)

## Proceso
1. **Extracción de hechos clave**: Identificar cronología, partes, pretensiones, documentación relevante, plazos.
2. **Estado procesal/operativo**: Dónde está el caso ahora — fase de estudio, en trámite, pendiente de resolución, cerrado.
3. **Análisis de situación**: Fortalezas y riesgos del caso para el cliente.
4. **Próximos pasos**: Acciones pendientes con responsable y fecha límite.
5. **Formato por audiencia**:
   - **Interno**: Detalle técnico completo, análisis de riesgos.
   - **Cliente**: Lenguaje accesible, estado claro, acciones requeridas del cliente.
   - **Terceros**: Solo información estrictamente necesaria.

## Output Esperado
- Resumen ejecutivo (1 página máximo)
- Cronología de hitos (timeline)
- Lista de próximos pasos con fechas
- Riesgos identificados (solo versión interna)

## Restricciones
- NO incluir opiniones legales como hechos
- NO usar jerga técnica en comunicaciones al cliente sin explicación
- NO revelar estrategia interna en resúmenes para terceros
- NO omitir plazos procesales críticos
- NO inventar hechos o datos no proporcionados

## Ejemplos
**Input**: "Caso de reclamación de cantidad por impago de factura de 15.000€, demandado no contesta, estamos en fase de ejecución"
**Output**: Resumen ejecutivo: "Reclamación de [Empresa] contra [Deudor] por impago de factura [nº]. Sentencia favorable de [fecha]. En fase de ejecución de título judicial. Próximo paso: investigación patrimonial para embargo. Plazo: 10 días."

**Input**: "Asesoría fiscal: cliente PYME necesita saber si le conviene pasar de autónomo a SL"
**Output**: Resumen comparativo: situación actual como autónomo (facturación, IRPF, SS), proyección como SL (IS, salario administrador, costes constitución y gestoría). Recomendación con umbral de facturación para el cambio.

## Validación
- ¿El resumen es factual (sin opiniones no fundamentadas)?
- ¿La longitud es apropiada para la audiencia?
- ¿Los próximos pasos tienen responsable y fecha?
- ¿Se protege la confidencialidad según la audiencia?
CONTENT,
  ],

  [
    'name' => 'Comunicación con Clientes',
    'skill_type' => 'vertical',
    'vertical_id' => 'serviciosconecta',
    'priority' => 79,
    'content' => <<<'CONTENT'
## Propósito
Redacta comunicaciones profesionales para el trato con clientes de servicios (legal, consultoría, asesoría, arquitectura, ingeniería), manteniendo el tono apropiado según contexto. Aplica cuando un profesional necesita redactar emails, informes o notificaciones a clientes.

## Input Esperado
- Tipo de comunicación (actualización, solicitud de documentación, informe, facturación, resultado)
- Contexto del caso/proyecto
- Tono deseado (formal, cercano-profesional, urgente)
- Información a comunicar
- Acciones requeridas del cliente (si las hay)

## Proceso
1. **Estructura del mensaje**: Saludo → Contexto breve → Información principal → Acción requerida → Cierre con disponibilidad.
2. **Adaptación de tono**: Formal para legal/institucional, cercano-profesional para consultoría/asesoría, técnico-accesible para ingeniería/arquitectura.
3. **Claridad de peticiones**: Si se necesita algo del cliente, especificar QUÉ, PARA QUÉ y PARA CUÁNDO.
4. **Gestión de expectativas**: Si hay retrasos o malas noticias, comunicar con transparencia y alternativas.
5. **Confidencialidad**: Verificar que no se incluye información que no deba compartirse.
6. **CTA claro**: Cada comunicación termina con una acción clara o confirmación de que no se requiere acción.

## Output Esperado
- Comunicación completa lista para enviar
- Versión formal y versión cercana (si aplica)
- Asunto de email optimizado
- Checklist de documentos a adjuntar (si aplica)

## Restricciones
- NO usar lenguaje excesivamente técnico sin explicación
- NO enviar malas noticias sin solución o alternativa
- NO ser ambiguo en plazos o responsabilidades
- NO incluir información confidencial de otros clientes o casos
- NO usar tono paternalista o condescendiente

## Ejemplos
**Input**: "Necesito pedirle al cliente 3 documentos para su declaración de la renta y tiene plazo hasta el 15 del mes"
**Output**: "Estimado/a [nombre], para preparar tu declaración de la renta de este ejercicio necesitamos los siguientes documentos antes del 15 de [mes]: 1) Certificado de retenciones de tu empresa, 2) Datos fiscales descargados de la AEAT, 3) Recibos de alquiler (si aplica). Puedes enviárnoslos por email o subirlos a tu carpeta en nuestra plataforma. Si necesitas ayuda para obtener alguno, no dudes en llamarnos."

**Input**: "El caso del cliente ha tenido un resultado desfavorable en primera instancia"
**Output**: Comunicación empática que explica el resultado sin alarmismo, análisis breve de las opciones (recurso de apelación, acuerdo extrajudicial), plazos para decidir, y oferta de reunión para explicar en detalle.

## Validación
- ¿El tono es apropiado para el tipo de cliente y situación?
- ¿Las acciones requeridas son claras y con plazo?
- ¿Se protege la confidencialidad?
- ¿El mensaje es completo pero conciso?
CONTENT,
  ],

  [
    'name' => 'Generación de Documentos',
    'skill_type' => 'vertical',
    'vertical_id' => 'serviciosconecta',
    'priority' => 78,
    'content' => <<<'CONTENT'
## Propósito
Genera borradores de documentos profesionales estandarizados (contratos, informes, propuestas, presupuestos) para profesionales de servicios, acelerando la producción documental. Aplica cuando un profesional necesita crear un documento tipo que luego revisará y personalizará.

## Input Esperado
- Tipo de documento (contrato, propuesta, informe, presupuesto, acta)
- Datos del profesional/empresa
- Datos del cliente/destinatario
- Contenido específico a incluir
- Normativa aplicable (si es relevante)

## Proceso
1. **Selección de plantilla**: Identificar la estructura estándar del documento según tipo y sector.
2. **Datos obligatorios**: Verificar que se tienen todos los datos necesarios — si faltan, listar qué se necesita.
3. **Redacción**: Generar borrador con lenguaje profesional apropiado al tipo de documento.
4. **Cláusulas estándar**: Incluir cláusulas habituales según tipo (confidencialidad, protección de datos, jurisdicción, forma de pago).
5. **Normativa**: Referenciar normativa aplicable (Código Civil, LOPDyGDD, normativa sectorial).
6. **Formato**: Estructura clara con numeración, encabezados y formato profesional.

## Output Esperado
- Borrador completo del documento con campos a personalizar marcados [ENTRE CORCHETES]
- Lista de campos pendientes de completar
- Notas sobre cláusulas opcionales a considerar
- Aviso de que es borrador que requiere revisión profesional

## Restricciones
- SIEMPRE indicar que es un BORRADOR que requiere revisión de un profesional habilitado
- NO generar documentos legales definitivos sin supervisión profesional
- NO omitir la cláusula LOPD/RGPD en documentos que traten datos personales
- NO copiar cláusulas abusivas o que contravengan la ley de consumidores
- NO generar documentos que requieran firma electrónica sin indicarlo

## Ejemplos
**Input**: "Contrato de prestación de servicios de asesoría fiscal para una PYME"
**Output**: Borrador con: identificación de partes, objeto del contrato (servicios fiscales), alcance (declaraciones, contabilidad, asesoramiento), honorarios y forma de pago, duración y renovación, confidencialidad, protección de datos, jurisdicción. Campos [EMPRESA], [CIF], [HONORARIO MENSUAL] para completar.

**Input**: "Presupuesto de proyecto de reforma de local comercial"
**Output**: Documento con: datos del profesional (colegiado), descripción del proyecto, partidas desglosadas con mediciones y precios unitarios, plazo de ejecución, condiciones de pago, validez del presupuesto (30 días), IVA desglosado.

## Validación
- ¿El documento incluye todos los elementos obligatorios del tipo?
- ¿Se indica claramente que es un borrador?
- ¿Los campos a completar están marcados visiblemente?
- ¿Se incluyen cláusulas de protección de datos y confidencialidad?
CONTENT,
  ],

  [
    'name' => 'Preparación de Reunión',
    'skill_type' => 'vertical',
    'vertical_id' => 'serviciosconecta',
    'priority' => 77,
    'content' => <<<'CONTENT'
## Propósito
Prepara agendas, materiales y briefings para reuniones profesionales con clientes, optimizando el tiempo del profesional y asegurando reuniones productivas. Aplica cuando un profesional tiene una reunión agendada y necesita prepararse.

## Input Esperado
- Tipo de reunión (primera toma de contacto, seguimiento, presentación de resultados, negociación)
- Cliente y contexto (historial si existe)
- Temas a tratar
- Duración estimada
- Formato (presencial, videollamada)

## Proceso
1. **Agenda estructurada**: Crear agenda con tiempos asignados. Regla: 80% del tiempo para el cliente, 20% para el profesional.
2. **Briefing del cliente**: Resumen de historial, últimas interacciones, temas pendientes, sensibilidades.
3. **Objetivos de la reunión**: Definir 1-3 resultados esperados al terminar (decisión, información, siguiente paso).
4. **Preguntas clave**: Preparar las preguntas que el profesional debe hacer para avanzar el caso/proyecto.
5. **Materiales de soporte**: Listar documentos a tener preparados (informes, propuestas, presupuestos).
6. **Anticipación de objeciones**: Si es reunión de presentación de propuesta/presupuesto, preparar respuestas a objeciones comunes.
7. **Acción post-reunión**: Template de acta de reunión con campos predefinidos para completar durante/después.

## Output Esperado
- Agenda de la reunión con tiempos
- Briefing del cliente (1 párrafo)
- Lista de preguntas a formular
- Materiales a preparar (checklist)
- Template de acta post-reunión

## Restricciones
- NO sobrecargar la agenda — menos es más
- NO preparar presentaciones largas — la reunión es para escuchar al cliente
- NO asumir que el cliente recuerda todo lo anterior — contextualizar brevemente
- NO olvidar definir siguiente paso concreto

## Ejemplos
**Input**: "Primera reunión con potencial cliente que necesita asesoría laboral para su empresa de 50 empleados"
**Output**: Agenda 45min: bienvenida (5min), necesidades del cliente (15min), presentación de servicios relevantes (10min), propuesta de trabajo (10min), Q&A y siguiente paso (5min). Preguntas: nº empleados, convenio colectivo, incidencias recientes, gestoría actual.

**Input**: "Reunión de seguimiento trimestral con cliente de contabilidad"
**Output**: Agenda 30min: repaso de KPIs del trimestre (10min), incidencias y resoluciones (10min), anticipación próximo trimestre (obligaciones fiscales, 5min), otros temas (5min). Briefing con resumen de facturación, incidencias del trimestre.

## Validación
- ¿La agenda tiene tiempos realistas y no está sobrecargada?
- ¿Los objetivos de la reunión son concretos y medibles?
- ¿Se ha preparado el contexto del cliente?
- ¿Hay un template para capturar acciones post-reunión?
CONTENT,
  ],

  [
    'name' => 'Generación de Presupuestos',
    'skill_type' => 'vertical',
    'vertical_id' => 'serviciosconecta',
    'priority' => 76,
    'content' => <<<'CONTENT'
## Propósito
Genera presupuestos profesionales desglosados para profesionales de servicios, con pricing competitivo y presentación que maximice la tasa de aceptación. Aplica cuando un profesional necesita presentar una propuesta económica a un cliente potencial o existente.

## Input Esperado
- Tipo de servicio a presupuestar
- Alcance del trabajo (qué incluye y qué no)
- Datos del cliente
- Tarifa horaria o por proyecto del profesional
- Complejidad estimada del trabajo
- Competidores conocidos (si los hay)

## Proceso
1. **Desglose de servicios**: Separar el proyecto en partidas claras y comprensibles para el cliente.
2. **Cuantificación**: Horas estimadas, unidades, porcentajes — que el cliente vea de dónde sale cada cifra.
3. **Pricing strategy**: Presentar 2-3 opciones (básico, estándar, premium) para dar sensación de control al cliente.
4. **Valor, no coste**: Enfocar el presupuesto en el valor que recibirá el cliente, no solo en el coste.
5. **Condiciones**: Validez del presupuesto, forma de pago, plazos de ejecución, exclusiones.
6. **IVA y fiscalidad**: Desglosar IVA (21%), indicar si aplica retención (15% IRPF en profesionales), suplidos.
7. **Garantías**: Qué pasa si el cliente no queda satisfecho, revisiones incluidas, soporte post-proyecto.

## Output Esperado
- Presupuesto profesional con 2-3 opciones
- Carta de presentación del presupuesto (email)
- Desglose detallado por partida
- Condiciones generales
- Nota de valor (ROI para el cliente)

## Restricciones
- NO presentar un solo precio sin opciones (el cliente necesita sensación de control)
- NO olvidar IVA y retenciones — el cliente español espera ver el precio final
- NO infravalorar el trabajo para ganar el proyecto (race to the bottom)
- NO incluir trabajos que no se han acordado (scope creep preventivo)
- NO copiar presupuestos genéricos sin personalizar

## Ejemplos
**Input**: "Proyecto de rediseño web para clínica dental, 15 páginas, con SEO básico"
**Output**: 3 opciones — Básica (web informativa 8 páginas, 2.400€+IVA), Estándar (15 páginas + SEO + blog, 4.200€+IVA), Premium (+ reserva online + Google Ads setup, 6.800€+IVA). Desglose: diseño UX, desarrollo, contenido, SEO, formación. Pago: 40% inicio, 60% entrega.

**Input**: "Asesoría fiscal anual para autónomo con facturación <100K€"
**Output**: Cuota mensual con 3 niveles — Básico (modelo 303+130, 90€/mes), Estándar (+ contabilidad + renta, 150€/mes), Premium (+ asesoría proactiva + planificación fiscal, 220€/mes). Todos +IVA, con retención 15% IRPF.

## Validación
- ¿El presupuesto tiene al menos 2 opciones?
- ¿El desglose justifica cada partida?
- ¿IVA y retenciones están correctamente calculados?
- ¿Las condiciones de pago son claras?
- ¿Se presenta el valor para el cliente, no solo el coste?
CONTENT,
  ],

];

// =============================================================================
// Crear las skills.
// =============================================================================

$created = 0;
$skipped = 0;

echo "🌱 Sembrando 30 Skills Verticales...\n\n";

foreach ($verticalSkills as $skillData) {
  // Verificar si ya existe.
  $existing = \Drupal::entityTypeManager()
    ->getStorage('ai_skill')
    ->loadByProperties(['name' => $skillData['name']]);

  if (!empty($existing)) {
    echo "⏭️  Skill ya existe: {$skillData['name']} [{$skillData['vertical_id']}]\n";
    $skipped++;
    continue;
  }

  // Crear la skill.
  $skill = AiSkill::create([
    'name' => $skillData['name'],
    'skill_type' => $skillData['skill_type'],
    'vertical_id' => $skillData['vertical_id'],
    'content' => $skillData['content'],
    'priority' => $skillData['priority'],
    'is_active' => TRUE,
  ]);
  $skill->save();

  echo "✅ Creada: {$skillData['name']} [{$skillData['vertical_id']}] (Prioridad: {$skillData['priority']})\n";
  $created++;
}

echo "\n========================================\n";
echo "📊 Resumen:\n";
echo "  - Creadas: {$created}\n";
echo "  - Omitidas (ya existían): {$skipped}\n";
echo "  - Total esperado: 30 (7 empleabilidad + 7 emprendimiento + 6 agroconecta + 5 comercioconecta + 5 serviciosconecta)\n";
echo "========================================\n";
echo "\n💡 Verificar con: lando drush sqlq \"SELECT skill_type, vertical_id, COUNT(*) FROM ai_skill GROUP BY skill_type, vertical_id\"\n";
