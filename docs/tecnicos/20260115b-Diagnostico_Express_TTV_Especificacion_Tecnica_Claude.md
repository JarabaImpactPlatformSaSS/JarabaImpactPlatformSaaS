
DIAGNÓSTICO EXPRESS
Time-to-Value < 60 Segundos
Vertical de Empleabilidad Digital
ESPECIFICACIÓN TÉCNICA
Sistema de diagnóstico instantáneo que entrega valor
ANTES del registro, no después

Versión:	1.0
Fecha:	Enero 2026
Vertical:	Empleabilidad Digital
Avatar:	Lucía (+45 años)
TTV Objetivo:	< 45 segundos
 
Tabla de Contenidos
Tabla de Contenidos	1
1. Resumen Ejecutivo	1
1.1 El Problema Actual	1
1.2 La Solución: Value-First Onboarding	1
2. Arquitectura del Flujo de Usuario	1
2.1 Diagrama de Flujo (45 segundos)	1
3. Diseño de las 3 Preguntas Clave	1
3.1 Criterios de Selección	1
3.2 Pregunta 1: Presencia LinkedIn	1
3.3 Pregunta 2: Actualización CV	1
3.4 Pregunta 3: Estrategia de Búsqueda	1
4. Algoritmo de Scoring	1
4.1 Fórmula de Cálculo	1
4.2 Matriz de Perfiles Resultantes	1
4.3 Matriz de Brechas y Acciones	1
5. Generación del Resultado Instantáneo	1
5.1 Estructura del Panel de Resultado	1
5.2 Biblioteca de Datos de Impacto	1
6. Implementación Técnica en Drupal 11	1
6.1 Arquitectura de Componentes	1
6.2 Código del Motor de Scoring	1
7. Especificación UI/UX	1
7.1 Diseño Visual de Preguntas	1
7.2 Diseño del Panel de Resultado	1
7.3 Animación de Carga (Falsa)	1
8. Integración con el Ecosistema Jaraba	1
8.1 Flujo Post-Registro (ECA)	1
8.2 Secuencias de Email por Perfil	1
8.3 Conexión con el FOC	1
9. Roadmap de Implementación	1
9.1 KPIs de Éxito	1
10. Conclusión	1

 
1. Resumen Ejecutivo
El Diagnóstico Express es un sistema de evaluación instantánea que permite a usuarios del vertical de Empleabilidad obtener un análisis de su perfil digital en menos de 45 segundos, SIN necesidad de registro previo.
1.1 El Problema Actual
El flujo actual de onboarding presenta los siguientes problemas críticos:
•	TTV actual: 15-30 minutos (inaceptable para conversión)
•	El usuario debe registrarse ANTES de ver cualquier valor
•	Triaje extenso con 15+ preguntas que genera abandono
•	El "momento aha" llega demasiado tarde en el funnel

1.2 La Solución: Value-First Onboarding
El principio fundamental es: "Entrega valor ANTES de pedir nada". El usuario recibe un diagnóstico completo y accionable en 45 segundos. Solo después de experimentar el valor, se le invita a registrarse para continuar.
ANTES (Flujo Actual)	DESPUÉS (Diagnóstico Express)
1. Landing → Registro obligatorio	1. Landing → 3 preguntas visuales
2. Confirmación email	2. Resultado INMEDIATO
3. Triaje largo (15+ preguntas)	3. "Tu perfil es 4/10"
4. Procesamiento IA	4. Acción #1 concreta
5. Espera de resultado	5. "¿Guardar y continuar?" → Registro
TTV: 15-30 minutos	TTV: 45 segundos

 
2. Arquitectura del Flujo de Usuario
2.1 Diagrama de Flujo (45 segundos)
┌─────────────────────────────────────────────────────────────────┐
│                    SEGUNDO 0-15: CAPTURA                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │  PREGUNTA 1: "¿Tienes perfil de LinkedIn?"              │   │
│   │                                                         │   │
│   │     [  😔 No  ]    [ 😐 Sí, básico ]    [ 😊 Sí, activo ]│   │
│   └─────────────────────────────────────────────────────────┘   │
│                            ↓ (5 seg)                            │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │  PREGUNTA 2: "¿Has actualizado tu CV en el último año?" │   │
│   │                                                         │   │
│   │     [  😔 No  ]    [ 😐 Hace meses ]    [ 😊 Reciente ] │   │
│   └─────────────────────────────────────────────────────────┘   │
│                            ↓ (5 seg)                            │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │  PREGUNTA 3: "¿Cómo buscas empleo actualmente?"         │   │
│   │                                                         │   │
│   │  [ 📰 Portales ]  [ 👥 Contactos ]  [ 🤖 No sé por dónde]│   │
│   └─────────────────────────────────────────────────────────┘   │
│                            ↓ (5 seg)                            │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                  SEGUNDO 15-30: PROCESAMIENTO                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│            ┌────────────────────────────────────┐               │
│            │   ⏳ "Analizando tu perfil..."     │               │
│            │      [████████░░] 80%              │               │
│            └────────────────────────────────────┘               │
│                                                                 │
│    Algoritmo de scoring ejecutándose en cliente (JS)            │
│    - Sin llamadas a servidor                                    │
│    - Resultado pre-calculado por combinatoria                   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                   SEGUNDO 30-45: RESULTADO                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │                                                         │   │
│   │        TU PERFIL DIGITAL DE EMPLEABILIDAD               │   │
│   │                                                         │   │
│   │              ┌─────────────┐                            │   │
│   │              │    4/10     │  ← Puntuación visual       │   │
│   │              │  ██████░░░░ │                            │   │
│   │              └─────────────┘                            │   │
│   │                                                         │   │
│   │   📊 Diagnóstico: "PERFIL INVISIBLE"                    │   │
│   │                                                         │   │
│   │   🎯 Tu mayor brecha:                                   │   │
│   │      "LinkedIn inexistente o desactualizado"            │   │
│   │                                                         │   │
│   │   ⚡ Acción inmediata #1:                               │   │
│   │      "Crea o actualiza tu titular de LinkedIn           │   │
│   │       en los próximos 2 minutos"                        │   │
│   │                                                         │   │
│   │   💡 Dato de impacto:                                   │   │
│   │      "El 87% de reclutadores revisan LinkedIn           │   │
│   │       antes de llamar a un candidato"                   │   │
│   │                                                         │   │
│   │   ┌─────────────────────────────────────────────────┐   │   │
│   │   │  🚀 Mejorar mi perfil ahora (Plan gratuito)     │   │   │
│   │   └─────────────────────────────────────────────────┘   │   │
│   │                                                         │   │
│   │   [ Guardar mi diagnóstico ] ← Trigger de registro      │   │
│   │                                                         │   │
│   └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

 
3. Diseño de las 3 Preguntas Clave
Las preguntas han sido diseñadas siguiendo criterios de máxima eficiencia diagnóstica con mínima fricción cognitiva.
3.1 Criterios de Selección
Criterio	Justificación
Alta predicción	Cada pregunta debe predecir significativamente la empleabilidad digital del usuario.
Respuesta visual	Opciones con iconos/emojis que se procesan en <1 segundo sin leer texto largo.
Sin ambigüedad	El usuario debe poder responder instantáneamente sin pensar.
Accionable	La respuesta debe permitir generar una recomendación concreta.

3.2 Pregunta 1: Presencia LinkedIn
Pregunta	"¿Tienes perfil de LinkedIn?"
Opción A	😔 "No tengo" → Puntuación: 0 pts | Flag: linkedin_none
Opción B	😐 "Sí, pero básico" → Puntuación: 1 pt | Flag: linkedin_basic
Opción C	😊 "Sí, activo" → Puntuación: 3 pts | Flag: linkedin_active
Peso	40% del score total (LinkedIn es el factor #1 en empleabilidad digital)

3.3 Pregunta 2: Actualización CV
Pregunta	"¿Has actualizado tu CV en el último año?"
Opción A	😔 "No / Hace más de un año" → Puntuación: 0 pts | Flag: cv_outdated
Opción B	😐 "Hace unos meses" → Puntuación: 1 pt | Flag: cv_recent
Opción C	😊 "Está actualizado" → Puntuación: 2 pts | Flag: cv_current
Peso	30% del score total

3.4 Pregunta 3: Estrategia de Búsqueda
Pregunta	"¿Cómo buscas empleo actualmente?"
Opción A	🤖 "No sé por dónde empezar" → Puntuación: 0 pts | Flag: search_lost
Opción B	📰 "Portales de empleo" → Puntuación: 1 pt | Flag: search_portals
Opción C	👥 "Networking y contactos" → Puntuación: 3 pts | Flag: search_network
Peso	30% del score total

 
4. Algoritmo de Scoring
4.1 Fórmula de Cálculo
// Pesos de cada dimensión
const WEIGHTS = {
  linkedin: 0.40,  // 40%
  cv: 0.30,        // 30%
  search: 0.30     // 30%
};

// Puntuaciones máximas por dimensión
const MAX_SCORES = {
  linkedin: 3,
  cv: 2,
  search: 3
};

// Cálculo del score normalizado (0-10)
function calculateScore(answers) {
  const linkedinNorm = (answers.linkedin / MAX_SCORES.linkedin) * WEIGHTS.linkedin;
  const cvNorm = (answers.cv / MAX_SCORES.cv) * WEIGHTS.cv;
  const searchNorm = (answers.search / MAX_SCORES.search) * WEIGHTS.search;
  
  const totalNorm = linkedinNorm + cvNorm + searchNorm;
  const score = Math.round(totalNorm * 10);
  
  return score; // 0-10
}

4.2 Matriz de Perfiles Resultantes
Score	Nivel	Diagnóstico	Perfil Tipo
0-2	CRÍTICO	"Perfil Invisible"	Sin presencia digital. Urgente intervención.
3-4	BAJO	"Perfil Desconectado"	Existe pero no funciona. Optimización necesaria.
5-6	MEDIO	"Perfil en Construcción"	Base correcta, falta estrategia.
7-8	ALTO	"Perfil Competitivo"	Bien posicionado, optimizar detalles.
9-10	EXCELENTE	"Perfil Magnético"	Atrae oportunidades. Escalar.

4.3 Matriz de Brechas y Acciones
El sistema identifica la brecha principal basándose en qué dimensión tiene mayor déficit relativo:
Brecha Principal	Condición	Acción #1 Recomendada
LinkedIn	linkedin = 0 OR (linkedin = 1 AND es la más baja)	"Crea/actualiza tu titular de LinkedIn en 2 min"
CV Desactualizado	cv = 0 AND linkedin > 0	"Actualiza tu CV con el formato ATS en 10 min"
Sin Estrategia	search = 0 AND linkedin > 0 AND cv > 0	"Activa tu red de contactos con este script"
Portales Ineficientes	search = 1 (solo portales)	"Complementa portales con networking activo"
Optimización	score >= 7	"Potencia tu marca personal con contenido"

 
5. Generación del Resultado Instantáneo
5.1 Estructura del Panel de Resultado
El resultado se presenta en un panel visual con 5 componentes clave:
#	Componente	Descripción y Propósito
1	Score Visual	Número grande (ej: 4/10) con barra de progreso coloreada. Impacto emocional inmediato.
2	Etiqueta Diagnóstico	Nombre memorable del perfil ("Perfil Invisible"). Genera identificación.
3	Brecha Principal	La debilidad #1 identificada con icono de alerta. Crea urgencia.
4	Acción Inmediata	Paso concreto y ejecutable en menos de 10 minutos. Da control al usuario.
5	Dato de Impacto	Estadística relevante que justifica la acción (ej: "87% de reclutadores..."). Genera credibilidad.

5.2 Biblioteca de Datos de Impacto
Brecha	Dato de Impacto
LinkedIn	"El 87% de los reclutadores revisa LinkedIn antes de contactar a un candidato" (Fuente: LinkedIn Talent Solutions 2024)
CV Desactualizado	"Un CV sin actualizar reduce un 60% las probabilidades de pasar el filtro ATS" (Fuente: Jobscan)
Sin Estrategia	"El 70% de los empleos no se publican. Se cubren por networking" (Fuente: SHRM)
Solo Portales	"Candidatos que combinan portales + networking tienen 5x más entrevistas" (Fuente: Harvard Business Review)
Optimización	"Publicar 1 post/semana en LinkedIn aumenta un 40% la visibilidad ante reclutadores" (Fuente: LinkedIn)

 
6. Implementación Técnica en Drupal 11
6.1 Arquitectura de Componentes
┌─────────────────────────────────────────────────────────────────┐
│                     CAPA DE PRESENTACIÓN                        │
│                   (React Component / JSX)                        │
├─────────────────────────────────────────────────────────────────┤
│  DiagnosticoExpress.jsx                                         │
│  ├── QuestionSlider.jsx    (Las 3 preguntas con animación)      │
│  ├── LoadingAnimation.jsx  (Barra de progreso falsa)            │
│  ├── ResultPanel.jsx       (Panel de resultado)                 │
│  └── CTARegistro.jsx       (Botón de conversión)                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     CAPA DE LÓGICA (JS)                         │
├─────────────────────────────────────────────────────────────────┤
│  scoring-engine.js                                              │
│  ├── calculateScore()      (Algoritmo de puntuación)            │
│  ├── identifyGap()         (Detectar brecha principal)          │
│  ├── getRecommendation()   (Obtener acción recomendada)         │
│  └── getImpactData()       (Obtener estadística)                │
│                                                                 │
│  ⚠️ TODO se ejecuta en CLIENTE (JavaScript)                     │
│  ⚠️ SIN llamadas al servidor = latencia CERO                    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   CAPA DE PERSISTENCIA                          │
│                   (Solo post-registro)                          │
├─────────────────────────────────────────────────────────────────┤
│  Drupal Entities:                                               │
│  ├── user (tras registro)                                       │
│  ├── diagnostico_express_result (Custom Entity)                 │
│  │   ├── field_score (integer)                                  │
│  │   ├── field_gap_principal (taxonomy)                         │
│  │   ├── field_answers (json)                                   │
│  │   └── field_created (datetime)                               │
│  └── ECA Rule: trigger secuencia onboarding en ActiveCampaign   │
└─────────────────────────────────────────────────────────────────┘

6.2 Código del Motor de Scoring
// scoring-engine.js - Ejecuta 100% en cliente

const CONFIG = {
  weights: { linkedin: 0.40, cv: 0.30, search: 0.30 },
  maxScores: { linkedin: 3, cv: 2, search: 3 },
  profiles: {
    critical: { min: 0, max: 2, label: "Perfil Invisible", color: "#E74C3C" },
    low: { min: 3, max: 4, label: "Perfil Desconectado", color: "#F39C12" },
    medium: { min: 5, max: 6, label: "Perfil en Construcción", color: "#E67E22" },
    high: { min: 7, max: 8, label: "Perfil Competitivo", color: "#27AE60" },
    excellent: { min: 9, max: 10, label: "Perfil Magnético", color: "#16A085" }
  },
  gaps: {
    linkedin: {
      label: "LinkedIn inexistente o desactualizado",
      action: "Crea o actualiza tu titular de LinkedIn en los próximos 2 minutos",
      impact: "El 87% de los reclutadores revisa LinkedIn antes de contactar"
    },
    cv: {
      label: "CV desactualizado o sin formato ATS",
      action: "Actualiza tu CV con el formato ATS en 10 minutos",
      impact: "Un CV sin actualizar reduce un 60% las probabilidades de pasar filtros"
    },
    search: {
      label: "Estrategia de búsqueda ineficiente",
      action: "Activa tu red de contactos con nuestro script de networking",
      impact: "El 70% de los empleos no se publican, se cubren por networking"
    },
    optimization: {
      label: "Potencial de visibilidad sin explotar",
      action: "Potencia tu marca personal publicando contenido",
      impact: "Publicar 1 post/semana aumenta un 40% tu visibilidad"
    }
  }
};

export function calculateScore(answers) {
  const { linkedin, cv, search } = answers;
  const { weights, maxScores } = CONFIG;
  
  const normalized = 
    (linkedin / maxScores.linkedin) * weights.linkedin +
    (cv / maxScores.cv) * weights.cv +
    (search / maxScores.search) * weights.search;
  
  return Math.round(normalized * 10);
}

export function getProfile(score) {
  const { profiles } = CONFIG;
  for (const [key, profile] of Object.entries(profiles)) {
    if (score >= profile.min && score <= profile.max) {
      return { key, ...profile };
    }
  }
  return profiles.critical;
}

export function identifyGap(answers, score) {
  const { linkedin, cv, search } = answers;
  
  // Prioridad: LinkedIn > CV > Search
  if (linkedin === 0) return 'linkedin';
  if (cv === 0 && linkedin > 0) return 'cv';
  if (search <= 1 && linkedin > 0 && cv > 0) return 'search';
  if (score >= 7) return 'optimization';
  
  // Brecha relativa más alta
  const gaps = {
    linkedin: (CONFIG.maxScores.linkedin - linkedin) / CONFIG.maxScores.linkedin,
    cv: (CONFIG.maxScores.cv - cv) / CONFIG.maxScores.cv,
    search: (CONFIG.maxScores.search - search) / CONFIG.maxScores.search
  };
  
  return Object.entries(gaps).sort((a, b) => b[1] - a[1])[0][0];
}

export function getRecommendation(gapKey) {
  return CONFIG.gaps[gapKey] || CONFIG.gaps.linkedin;
}

export function generateResult(answers) {
  const score = calculateScore(answers);
  const profile = getProfile(score);
  const gapKey = identifyGap(answers, score);
  const recommendation = getRecommendation(gapKey);
  
  return {
    score,
    profile,
    gap: {
      key: gapKey,
      ...recommendation
    },
    answers,
    timestamp: new Date().toISOString()
  };
}

 
7. Especificación UI/UX
7.1 Diseño Visual de Preguntas
Elemento	Especificación
Layout	Centrado vertical y horizontal. Una pregunta visible a la vez (wizard).
Tipografía pregunta	Montserrat Bold, 28px, color #2C3E50. Máximo 8 palabras.
Botones respuesta	Cards horizontales 120x80px. Emoji 32px + texto 14px debajo. Hover: sombra + scale 1.05.
Transición	Slide horizontal 300ms ease-out al seleccionar respuesta.
Progreso	3 dots en la parte superior. Dot activo: filled #E67E22. Inactivo: outline.
Fondo	Gradiente suave de #F8F9FA a #FFFFFF. Sin distracciones.

7.2 Diseño del Panel de Resultado
Elemento	Especificación
Score circular	SVG circular 150x150px. Número grande 48px bold centro. Borde progreso coloreado según nivel.
Etiqueta perfil	Badge pill con color de fondo según nivel. Texto 16px bold blanco.
Sección brecha	Card con borde izquierdo 4px color alerta. Icono ⚠️ + texto 16px.
Acción recomendada	Card con fondo #E8F8F5 (verde claro). Icono ⚡ + texto 16px bold.
Dato impacto	Texto 14px italic color #666. Icono 💡 precediendo.
CTA Principal	Botón full-width. Fondo #E67E22, texto blanco 18px bold. "Mejorar mi perfil ahora".
CTA Secundario	Link texto 14px #2B579A subrayado. "Guardar mi diagnóstico" → trigger registro.

7.3 Animación de Carga (Falsa)
La animación de "procesamiento" es puramente psicológica. El cálculo es instantáneo, pero 2-3 segundos de animación aumentan la percepción de valor:
// Pseudo-loading para efecto psicológico
async function showFakeLoading() {
  const messages = [
    "Analizando tu presencia digital...",
    "Evaluando tu estrategia de búsqueda...",
    "Generando recomendaciones personalizadas..."
  ];
  
  for (let i = 0; i <= 100; i += 5) {
    await delay(50); // Total: ~1 segundo
    updateProgress(i);
    if (i % 33 === 0) updateMessage(messages[Math.floor(i/33)]);
  }
  
  await delay(500); // Pausa dramática
  showResult();
}

// El resultado ya está calculado ANTES de la animación
// La animación es solo UX theater

 
8. Integración con el Ecosistema Jaraba
8.1 Flujo Post-Registro (ECA)
Una vez que el usuario decide registrarse tras ver su resultado, se activa el siguiente flujo automatizado:
TRIGGER: Usuario completa registro tras Diagnóstico Express

CONDICIÓN: diagnostic_express_completed = TRUE

ACCIONES ECA:
├── 1. Crear entidad diagnostic_express_result
│       └── Guardar score, gap, answers, timestamp
│
├── 2. Asignar rol inicial según score
│       ├── score <= 4: rol "empleabilidad_urgente"
│       ├── score 5-6: rol "empleabilidad_desarrollo"
│       └── score >= 7: rol "empleabilidad_optimizacion"
│
├── 3. Webhook a ActiveCampaign
│       ├── Tag: "diagnostico_express"
│       ├── Tag: gap principal (ej: "gap_linkedin")
│       ├── Custom field: score
│       └── Trigger: secuencia onboarding personalizada
│
├── 4. Desbloquear contenido según gap
│       ├── gap_linkedin: Módulo "LinkedIn en 30 min"
│       ├── gap_cv: Módulo "CV ATS Ganador"
│       └── gap_search: Módulo "Networking Digital"
│
└── 5. Asignar Créditos de Impacto iniciales
        └── +50 CR por completar diagnóstico

8.2 Secuencias de Email por Perfil
Perfil	Secuencia AC	Contenido
Invisible (0-2)	rescue_urgente_7d	7 emails en 7 días. Tono urgente. Micro-acciones diarias. Primera: crear LinkedIn.
Desconectado (3-4)	activacion_14d	14 emails en 14 días. Tono motivacional. Enfoque en optimización de lo existente.
En Construcción (5-6)	estrategia_21d	21 emails. Tono estratégico. Contenido avanzado de networking y marca personal.
Competitivo (7-8)	optimizacion_30d	30 emails mensuales. Tono peer-to-peer. Tips avanzados y casos de éxito.
Magnético (9-10)	embajador_vip	Secuencia VIP. Invitación a ser caso de éxito. Oportunidades de colaboración.

8.3 Conexión con el FOC
El Diagnóstico Express alimenta métricas clave del Centro de Operaciones Financieras:
Métrica FOC	Cómo la alimenta el Diagnóstico Express
Conversion Rate	% de usuarios que completan diagnóstico → % que se registran. Target: 40%+.
Lead Quality Score	El score del diagnóstico predice propensión a compra. Score alto = lead caliente.
Content Gap Analysis	Agregado de gaps detectados → qué contenido crear. Si 60% tiene gap_linkedin, priorizar ese módulo.
Activation Rate	% de usuarios que completan la Acción #1 recomendada en 24h. Target: 30%+.
CAC por Perfil	Coste de adquisición segmentado por perfil de diagnóstico. Optimizar campañas.

 
9. Roadmap de Implementación
Fase	Timeline	Entregables
Sprint 1	Semana 1	Componente React de preguntas + animaciones. Motor de scoring en JS. Tests unitarios.
Sprint 2	Semana 2	Panel de resultado con todos los componentes visuales. Responsive mobile-first.
Sprint 3	Semana 3	Integración Drupal: Custom Entity, flujo de registro post-diagnóstico, ECA rules.
Sprint 4	Semana 4	Integración ActiveCampaign: webhooks, secuencias por perfil. Analytics y tracking.
Sprint 5	Semana 5	QA completo, A/B testing de copies, optimización de conversión. Go-live.

9.1 KPIs de Éxito
KPI	Target	Medición
Time-to-Value	< 45 seg	Tiempo desde landing hasta ver resultado
Completion Rate (Diagnóstico)	> 80%	% que completa las 3 preguntas
Conversion to Register	> 35%	% que se registra tras ver resultado
Activation Rate (Acción #1)	> 25%	% que completa acción recomendada en 24h
NPS Post-Diagnóstico	> 50	Encuesta micro tras resultado

10. Conclusión
El Diagnóstico Express representa un cambio de paradigma en el onboarding del vertical de Empleabilidad. Al entregar valor ANTES del registro, transformamos la experiencia de "pedir para dar" a "dar para recibir".
El objetivo final es que Lucía, en menos de 1 minuto, pase de "no sé por dónde empezar" a "ya sé exactamente qué hacer". Ese es el verdadero Time-to-Value.

DIAGNÓSTICO EXPRESS
Especificación Técnica v1.0
Jaraba Impact Platform | Enero 2026

