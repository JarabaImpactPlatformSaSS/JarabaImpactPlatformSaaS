# ANÁLISIS DE IMPACTO ESTRATÉGICO
## Copiloto de Emprendimiento v2.0 en el Programa Andalucía +ei Segunda Edición

**Documento de Análisis | Enero 2026**

---

## RESUMEN EJECUTIVO

La implementación del Copiloto de Emprendimiento v2.0 representa una **transformación sistémica** del Programa Andalucía +ei, no un simple añadido tecnológico. Este análisis examina el impacto en las cuatro dimensiones fundamentales del programa: Configuración, Lógica, Estructura y Contenido.

**Conclusión principal:** El Copiloto convierte el programa de un modelo de **formación lineal con soporte reactivo** a un **ecosistema de validación adaptativo con soporte proactivo 24/7**.

---

## 1. IMPACTO EN LA CONFIGURACIÓN

### 1.1 Sistema de Diagnóstico y Asignación de Carriles

| ANTES (V1) | DESPUÉS (con Copiloto V2.0) |
|------------|----------------------------|
| Diagnóstico manual basado en entrevista inicial | **Diagnóstico DIME automatizado** con 10 preguntas que genera puntuación 0-20 |
| Asignación a carril por criterio del formador | **Asignación algorítmica**: 0-9 pts → IMPULSO, 10-20 pts → ACELERA |
| Sin registro de bloqueos emocionales | **Detección automática** de 5 tipos de bloqueos: impostor, miedo_precio, miedo_rechazo, tecnofobia, parálisis |
| Nivel técnico estimado subjetivamente | **Nivel técnico calculado** (1-5) basado en respuestas DIME |

**Impacto operativo:**
- Eliminación del "efecto convoy" detectado en V1 (Adrián vs Matilde)
- Personalización desde el día 0 del programa
- Datos estructurados para análisis posterior y mejora continua

### 1.2 Perfiles de Usuario y Persistencia de Datos

**Nueva infraestructura de datos:**
- `entrepreneur_profile`: Perfil completo con DIME, carril, fase, bloqueos, puntos
- `hypothesis`: Hipótesis de negocio con estado de validación
- `experiment`: Test Cards + Learning Cards con resultados
- `bmc_validation_state`: Estado de los 9 bloques del Canvas
- `pivot_log`: Historial de pivots y aprendizajes

**Impacto:** El programa pasa de gestionar "alumnos" a gestionar "emprendedores con proyectos vivos" que evolucionan semana a semana con datos trazables.

### 1.3 Configuración de la IA por Carril

| Carril IMPULSO | Carril ACELERA |
|----------------|----------------|
| Lenguaje más sencillo, sin jerga | Terminología técnica permitida |
| Más preguntas de confirmación | Respuestas más directas |
| Herramientas No-Code prioritarias | Automatizaciones avanzadas |
| Foco en primer cliente | Foco en sistematización y escala |
| Mayor soporte emocional | Mayor desafío intelectual |

---

## 2. IMPACTO EN LA LÓGICA DEL PROGRAMA

### 2.1 De Formación Lineal a Validación Iterativa

**ANTES (V1):**
```
Semana 1 → Semana 2 → ... → Semana 12 → Demo Day → FIN
(Contenido fijo, ritmo único, evaluación final)
```

**DESPUÉS (V2.0 con Copiloto):**
```
┌─────────────────────────────────────────────────────┐
│                   CICLO CONTINUO                     │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐      │
│  │ HIPÓTESIS│ →  │EXPERIMENTO│ →  │APRENDIZAJE│     │
│  │ (Creo que)│    │(Test Card)│    │(Learning) │     │
│  └────┬─────┘    └────┬─────┘    └─────┬────┘      │
│       │               │                │           │
│       └───────────────┴────────────────┘           │
│                       ↓                            │
│              DECISIÓN: PERSEVERAR │ PIVOTAR │ MATAR │
└─────────────────────────────────────────────────────┘
```

**Impacto:** El emprendedor ya no "pasa" de semana en semana, sino que **valida hipótesis** y toma decisiones basadas en evidencia.

### 2.2 Los 5 Modos del Copiloto como Lógica Adaptativa

El Copiloto detecta automáticamente qué tipo de ayuda necesita el emprendedor:

| Modo | Trigger | Comportamiento |
|------|---------|----------------|
| **COACH_EMOCIONAL** | "miedo", "no puedo", "agobio" | Escucha activa, normaliza, ofrece Kit Emocional |
| **CONSULTOR_TÁCTICO** | "cómo hago", "qué herramienta" | Paso a paso, plantillas, tutoriales |
| **SPARRING_PARTNER** | "qué te parece", "valídame" | Feedback honesto, simulación de cliente |
| **CFO_SINTÉTICO** | "precio", "cobrar", "cuánto" | Calculadora de la Verdad, análisis financiero |
| **ABOGADO_DIABLO** | "estoy seguro", "todos quieren" | Preguntas incómodas, retos a suposiciones |

**Impacto lógico:** El programa ya no tiene un "tono único" sino que **se adapta al momento emocional y técnico** del emprendedor.

### 2.3 De Evaluación Final a Validación Continua del BMC

**Sistema de semáforos por bloque:**

| % Validación | Semáforo | Significado |
|--------------|----------|-------------|
| 0-39% | 🔴 ROJO | Necesita validación urgente |
| 40-79% | 🟡 AMARILLO | En progreso, hipótesis en prueba |
| 80-100% | 🟢 VERDE | Validado con evidencia |

**Los 9 bloques monitorizados:**
1. VP - Propuesta de Valor
2. CS - Segmentos de Clientes
3. CH - Canales
4. CR - Relaciones con Clientes
5. RS - Fuentes de Ingresos
6. KR - Recursos Clave
7. KA - Actividades Clave
8. KP - Alianzas Clave
9. C$ - Estructura de Costes

**Impacto:** El Demo Day ya no presenta "ideas bonitas" sino **modelos parcialmente validados** con evidencia real.

### 2.4 Gamificación como Motor de Compromiso

**Sistema de Puntos de Impacto:**

| Acción | Puntos |
|--------|--------|
| Experimento iniciado | +10 |
| Experimento completado (PERSEVERE) | +100 |
| Experimento completado (PIVOT) | +75 |
| Experimento completado (KILL) | +50 |
| Hipótesis creada | +15 |
| Hipótesis validada | +50 |
| Primera venta real | +200 |
| Bloque BMC en verde | +100 |
| Actividad semanal mantenida | +20 |

**Impacto:** Convierte acciones "aburridas" (validar, pivotar, documentar) en logros visibles y motivadores.

---

## 3. IMPACTO EN LA ESTRUCTURA DEL PROGRAMA

### 3.1 Nueva Distribución de las 100 Horas

**ANTES (V1):**
| Componente | Horas | Formato |
|------------|-------|---------|
| Orientación | 10h | Individual, reactivo |
| Formación | 50h | Sesiones síncronas magistrales |
| Acompañamiento | 40h | Mentorías individuales |

**DESPUÉS (V2.0):**
| Componente | Horas | Formato |
|------------|-------|---------|
| Orientación + DIME | 10h | Diagnóstico automatizado + sesión inicial |
| Formación asíncrona | 24h | Píldoras de vídeo 5-8 min (Flipped) |
| Sesiones síncronas | 26h | Talleres 100% prácticos, role-plays |
| Acompañamiento humano | 34h | Mentorías estratégicas de alto valor |
| **Copiloto IA** | **∞** | **Disponible 24/7 sin coste adicional** |

**Impacto estructural:** El Copiloto **libera 20+ horas** de soporte técnico/dudas básicas que antes consumía el mentor humano.

### 3.2 Rediseño del Flujo Semanal

**Nueva estructura semanal tipo:**

| Día | Actividad | Soporte |
|-----|-----------|---------|
| **Lunes** | Píldora de vídeo (5-8 min) + ejercicio | Copiloto para dudas |
| **Martes** | Sesión síncrona grupal (2-2.5h) | Facilitador + Copiloto |
| **Miércoles** | Trabajo autónomo en entregable | Copiloto 24/7 |
| **Jueves** | Mentoría grupal/individual (1h) | Mentor humano |
| **Vie-Dom** | Acción de campo (experimento real) | Copiloto + Círculo de Responsabilidad |

### 3.3 Integración en las 12 Semanas

| Semana | Fase | Rol del Copiloto |
|--------|------|------------------|
| 0 | Pre-programa | Procesa DIME, asigna carril |
| 1-3 | Mentalidad y Diagnóstico | Coach Emocional + Inventario "Pájaro en Mano" |
| 4-6 | Validación y Construcción | Consultor Táctico + sugerencia de experimentos |
| 7-9 | Viabilidad Económica | CFO Sintético + Calculadora de la Verdad |
| 10-11 | Ventas y Marketing | Sparring Partner + simulaciones de venta |
| 12 | Demo Day | Abogado del Diablo + preparación de pitch |
| Post | Círculos de Responsabilidad | Seguimiento continuo sin coste |

### 3.4 Nuevos Entregables por Semana

El Copiloto habilita entregables que antes eran inviables:

| Entregable | Semana | Soporte del Copiloto |
|------------|--------|---------------------|
| Inventario "Pájaro en Mano" | 1 | Guía paso a paso, ejemplos |
| Primera Hipótesis | 2 | Formulación correcta, priorización |
| Test Card #1 | 3 | Selección de experimento, criterios |
| Learning Card #1 | 4 | Análisis de resultados, siguiente paso |
| Canvas Propuesta de Valor | 4-5 | Feedback sobre encaje |
| Landing Page | 5 | Revisión de copy, CTA |
| Precio + Margen | 7 | Calculadora de la Verdad |
| Primer Presupuesto | 10 | Simulación de objeciones |
| Hoja de Ruta 90 días | 12 | Priorización de próximos pasos |

---

## 4. IMPACTO EN EL CONTENIDO DEL PROGRAMA

### 4.1 De 5 Módulos Estáticos a Biblioteca Dinámica

**ANTES (V1) - 5 Módulos fijos:**
1. Presentación y Ayudas
2. Investigación y Propuesta de Valor
3. Creación del Producto/Servicio
4. Viabilidad Económica
5. Marketing Digital

**DESPUÉS (V2.0) - Sistema modular adaptativo:**

| Capa | Contenido | Acceso |
|------|-----------|--------|
| **Píldoras** | 20+ vídeos de 5-8 min | Asíncrono, ritmo personal |
| **Herramientas** | Plantillas interactivas (Canvas, Test Card, etc.) | On-demand |
| **Biblioteca de Experimentos** | 44 técnicas de validación | Sugeridas por el Copiloto |
| **Kit Emocional** | Antídotos a bloqueos | Activados por trigger |
| **Base de Conocimiento** | Normativa, subvenciones, procedimientos | Consulta vía Copiloto |

### 4.2 Los 44 Experimentos como Nuevo Contenido Central

El programa ahora incluye una **biblioteca de 44 experimentos de validación** organizados por:

**Por categoría (cuándo usarlos):**
- **DISCOVERY (10):** Cuando no sabes si el problema existe
- **INTEREST (12):** Cuando quieres medir interés sin producto
- **PREFERENCE (12):** Cuando quieres validar la solución específica
- **COMMITMENT (10):** Cuando quieres evidencia de pago real

**Por tipo de hipótesis:**
- **DESIRABILITY:** ¿Lo quieren? (cliente, problema, solución)
- **FEASIBILITY:** ¿Lo puedo hacer? (recursos, tecnología)
- **VIABILITY:** ¿Es rentable? (precio, costes, margen)

**Ejemplos adaptados a Andalucía:**
- Entrevista de Descubrimiento → María nutricionista en su pueblo
- Landing Page Simple → Rosa talleres de cerámica en Carrd
- MVP Concierge → Jorge menús semanales por WhatsApp
- Preventa → María talleres de costura antes de alquilar local

### 4.3 Kit de Primeros Auxilios Emocionales (Nuevo Contenido)

| Bloqueo | Herramienta | Contenido |
|---------|-------------|-----------|
| Síndrome del Impostor | Checklist Antídoto | 10 preguntas objetivas para re-enfocar |
| Miedo al Precio | Mantra de Cobro | Re-encuadre: cobrar bien = responsabilidad |
| Miedo al Rechazo | Scripts para el "No" | Plantillas literales para responder |
| Tecnofobia | Guiones paso a paso | Instrucciones de 30 segundos con capturas |
| Parálisis por Análisis | Protocolo Limonada | Convertir fracaso en aprendizaje |

**Activación:** El Copiloto detecta triggers emocionales y ofrece el Kit apropiado automáticamente.

### 4.4 Contenido Diferenciado por Carril

| Tema | IMPULSO | ACELERA |
|------|---------|---------|
| **Herramientas digitales** | Carrd, Canva, WhatsApp Business | Notion, Zapier, CRM, Funnels |
| **Validación** | Entrevistas simples, observación | A/B Testing, cohortes, métricas |
| **Precio** | "Calcula tu hora mínima" | "Optimiza tu LTV/CAC" |
| **Ventas** | Scripts literales, role-play básico | Negociación avanzada, objeciones |
| **Escala** | "Consigue tu primer cliente" | "Contrata tu primer colaborador" |

### 4.5 Nuevas Herramientas Interactivas

El Copiloto habilita herramientas que antes requerían sesión con mentor:

| Herramienta | Función | Antes | Ahora |
|-------------|---------|-------|-------|
| **Formulario DIME** | Diagnóstico de madurez | Entrevista 1h | 8 minutos autoservicio |
| **Canvas Propuesta de Valor** | Definir encaje | Taller 2h | Interactivo + feedback IA |
| **Test Card** | Planificar experimento | Explicación + plantilla | Formulario guiado + sugerencias |
| **Learning Card** | Registrar resultado | Seguimiento manual | Automático con triggers |
| **Calculadora de la Verdad** | Definir precio | Sesión financiera | Interactiva con explicaciones |
| **Dashboard BMC** | Ver progreso | Excel manual | Tiempo real con semáforos |

---

## 5. SÍNTESIS DE IMPACTOS

### 5.1 Tabla Resumen de Transformaciones

| Dimensión | De (V1) | A (V2.0 con Copiloto) |
|-----------|---------|----------------------|
| **CONFIGURACIÓN** | Diagnóstico subjetivo | DIME algorítmico + asignación automática |
| **LÓGICA** | Formación lineal | Validación iterativa (hipótesis → experimento → aprendizaje) |
| **ESTRUCTURA** | 100h con soporte limitado | 100h + Copiloto 24/7 ilimitado |
| **CONTENIDO** | 5 módulos estáticos | Biblioteca dinámica + 44 experimentos + Kit Emocional |

### 5.2 Métricas de Impacto Esperadas

| Métrica | V1 (estimado) | V2.0 (objetivo) | Mejora |
|---------|---------------|-----------------|--------|
| Tiempo para primera venta | 4-5 meses | 2-3 meses | -40% |
| Abandono post-programa | 40-50% | 15-25% | -50% |
| Experimentos por alumno | 0-1 | 5-8 | +700% |
| Satisfacción NPS | 60-70 | 85+ | +25% |
| Coste por consulta básica | €50/h mentor | €0 (Copiloto) | -100% |
| Disponibilidad de soporte | 20h/semana | 168h/semana | +740% |

### 5.3 Riesgos y Mitigaciones

| Riesgo | Probabilidad | Mitigación |
|--------|--------------|------------|
| Baja adopción del Copiloto | Media | Onboarding guiado, mentor normaliza uso |
| Dependencia excesiva de IA | Baja | Limitación de respuestas, derivación a humano |
| Resistencia al cambio (facilitadores) | Media | Formación, demostración de valor |
| Datos incompletos para personalización | Media | Obligatoriedad de DIME para acceder |

---

## 6. RECOMENDACIONES DE IMPLEMENTACIÓN

### 6.1 Cambios en el Rol del Facilitador

| Función | Antes | Ahora |
|---------|-------|-------|
| Responder dudas técnicas | 60% del tiempo | 10% (Copiloto las absorbe) |
| Soporte emocional reactivo | 20% del tiempo | 10% (Kit Emocional automático) |
| **Mentoring estratégico** | 20% del tiempo | **50% del tiempo** |
| **Dinamización de sesiones** | Incluido | **30% del tiempo** |

### 6.2 Cambios en el Onboarding de Alumnos

**Nueva secuencia obligatoria:**
1. Completar DIME (8 min) → Asignación automática de carril
2. Ver vídeo de bienvenida personalizado por carril
3. Primera interacción con Copiloto (pregunta guiada)
4. Completar Inventario "Pájaro en Mano" con asistencia del Copiloto
5. Primera sesión síncrona ya con contexto completo

### 6.3 Cambios en la Evaluación

| Componente | Peso V1 | Peso V2.0 |
|------------|---------|-----------|
| Asistencia a sesiones | 40% | 20% |
| Entregables teóricos | 40% | 15% |
| **Experimentos completados** | 0% | **35%** |
| **Validación del BMC** | 0% | **20%** |
| **Peer Review** | 20% | 10% |

### 6.4 Cambios en el Demo Day

**ANTES:** Presentación de idea + plan de negocio
**AHORA:** Presentación de:
- Hipótesis validadas/invalidadas (evidencia real)
- Dashboard BMC con semáforos
- Aprendizajes de experimentos (no solo éxitos)
- Hoja de Ruta 90 días basada en datos

---

## 7. CONCLUSIÓN

El Copiloto de Emprendimiento v2.0 **no es un chatbot añadido al programa**, sino un **cambio de paradigma** que transforma:

1. **La naturaleza del programa:** De "curso de emprendimiento" a "aceleradora de validación"
2. **El rol del formador:** De "profesor que transmite" a "mentor que desbloquea"
3. **La experiencia del alumno:** De "asistir y escuchar" a "validar y decidir"
4. **El seguimiento post-programa:** De "esperamos que te vaya bien" a "continuamos contigo 24/7"

**Resultado esperado:** Un programa que produce emprendedores con **modelos de negocio parcialmente validados**, no solo con "ideas bonitas y un plan".

---

*Documento preparado para José Jaraba Muñoz | Plataforma de Ecosistemas Digitales*
*Enero 2026*
