# 20260324i-specs-jaraba-impact-platform_Claude

> Fuente: `20260324i-specs-jaraba-impact-platform_Claude.docx`
> Convertido: 2026-03-24
> Caracteres: 25,527

---

ESPECIFICACIONES TÉCNICAS

Jaraba Impact Platform

Configuración para el Programa Andalucía +ei — 2ª Edición

Requisitos funcionales, entidades de datos, flujos de usuario,

configuración de agentes IA y roadmap de implementación

Plataforma de Ecosistemas Digitales S.L. — José Jaraba Muñoz

Marzo 2026 — Documento técnico interno

ÍNDICE

1. Contexto y Alcance1

1.1. Verticales implicados1

2. Modelo de Datos: Entidades Principales1

2.1. Entidad: Participante1

2.2. Entidad: Negocio Prospectado (Cliente Piloto)1

2.3. Entidad: Asistencia1

2.4. Entidad: Entregable1

2.5. Entidad: Evaluación Competencia IA1

3. Requisitos Funcionales1

3.1. Vertical Andalucía +ei (Panel de Gestión del Programa)1

3.2. Sistema de Entregables y Portfolio1

3.3. Copiloto IA: Contextualización por Participante1

3.4. CRM de Prospección de Clientes Piloto1

3.5. Catálogo Digital de Packs1

3.6. Control de Asistencia y Horas1

3.7. Lean Canvas Interactivo1

3.8. Herramientas Financieras1

4. Configuración de Agentes IA1

4.1. System prompts por fase del programa1

4.2. Variables de contexto del participante (inyectadas en cada sesión)1

5. Flujos de Usuario Principales1

5.1. Flujo del participante: de la inscripción a la inserción1

5.2. Flujo del formador: gestión diaria1

5.3. Flujo del orientador: acompañamiento1

6. Roadmap de Implementación1

6.1. Priorización por fases1

6.2. Estimación de esfuerzo1

7. Seguridad, RGPD y Requisitos FSE+1

7.1. Protección de datos1

7.2. Requisitos de publicidad FSE+1

8. Conclusión y Próximos Pasos1

1. Contexto y Alcance

Este documento traduce los requisitos del Programa Andalucía +ei 2ª Edición en especificaciones técnicas para Jaraba Impact Platform. Consolida los requisitos de los siguientes documentos de referencia:

Documento

Requisitos que genera

Informe Estratégico

Visión general, métricas de éxito, verticales implicados

Diseño Formativo (corregido)

Estructura de módulos, sesiones, control asistencia, horas online sincrónicas

Catálogo de Servicios

5 packs con fichas de producto, precios, modalidades, catálogo digital

Guía de Integración

Flujo pack-como-proyecto-vertebrador, matching participante-negocio, clientes piloto

Guía Didáctica del Formador

29 entregables, misiones complementarias, evaluaciones, rúbrica IA

Presentación Sesión Informativa

Captación de participantes, formulario de interés

Estrategia Prospección Clientes Piloto

CRM de prospección, embudo de 6 fases, acuerdos de prueba, matching

1.1. Verticales implicados

El programa utiliza 4 verticales de la plataforma de forma directa y 3 de forma complementaria:

Vertical

Uso

Usuarios

Empleabilidad

CV con IA, matching empleo, preparación entrevistas, diagnóstico profesional

Participantes ruta B (empleo)

Emprendimiento

Lean Canvas, validación hipótesis, plan financiero, copiloto startup

Participantes ruta A (autoempleo)

Andalucía +ei (dedicado)

Gestión del programa: participantes, asistencia, horas, evaluaciones, informes FSE+

Equipo del programa

Content Hub

Creación contenido, calendario editorial, publicación

Participantes (Módulo 4)

ComercioConecta / AgroConecta

Catálogo digital, tienda online, pagos, trazabilidad

Participantes Pack 4

JarabaLex

Consultas legales, alertas BOE/BOJA, plantillas

Participantes (Módulo 3)

ServiciosConecta

CRM clientes, presupuestos, agenda

Participantes Pack 2/5

2. Modelo de Datos: Entidades Principales

2.1. Entidad: Participante

Campo

Tipo

Obligatorio

Descripción

id

UUID

Sí

Identificador único

nombre_completo

String

Sí

Nombre y apellidos

nif_nie

String

Sí

Documento identidad (encriptado)

email

String

Sí

Correo electrónico

telefono

String

Sí

Teléfono móvil

provincia

Enum

Sí

Provincia de participación

colectivo_vulnerable

Enum[]

Sí

1+ colectivos: discapacidad, desempleo_larga, mayor_45, migrante, exclusion, perceptor

ruta

Enum

Sí (post OI)

A (autoempleo) | B (empleo) | hibrida

nivel_digital

Enum

Sí (post OI)

A (autónomo) | B (apoyo) | C (nivelación)

pack_preseleccionado

Enum[]

Sí (post OI)

1-3 packs candidatos

pack_confirmado

Enum

Sí (post M1)

Pack definitivo tras validación

objetivos_smart

JSON[3]

Sí (post OI)

3 objetivos con indicadores

estado_programa

Enum

Sí

inscrito | orientacion | formacion | acompanamiento | insertado | baja

fecha_alta_ss

Date

No

Fecha alta Seguridad Social (inserción)

meses_ss_acumulados

Integer

No

Meses de alta acumulados (objetivo ≥4)

compromiso_firmado

Boolean

Sí

Firma del compromiso de participación

perfil_riasec

JSON

No

Resultados hexágono Holland

2.2. Entidad: Negocio Prospectado (Cliente Piloto)

Campo

Tipo

Obligatorio

Descripción

id

UUID

Sí

Identificador único

nombre_negocio

String

Sí

Nombre comercial

sector

Enum

Sí

hosteleria | comercio | profesional | agro | salud | educacion | turismo | servicios

direccion

String

Sí

Dirección física

telefono

String

Sí

Teléfono de contacto

persona_contacto

String

Sí

Nombre del dueño/a o responsable

url_web

String

No

Web actual (puede estar vacío = sin web)

url_google_maps

String

No

Enlace a ficha Google

valoracion_google

Decimal

No

Puntuación media en Google

num_resenas

Integer

No

Número total de reseñas

resenas_sin_responder

Integer

No

Reseñas sin respuesta

clasificacion

Enum

Sí

rojo (urgente) | amarillo (medio) | verde (bajo)

estado_embudo

Enum

Sí

identificado | contactado | interesado | propuesta | acuerdo | ejecucion | convertido | descartado

pack_compatible

Enum[]

Sí

Packs que podrían servir a este negocio

participante_asignado

FK Participante

No

Participante emparejado (matching)

fecha_inicio_prueba

Date

No

Inicio periodo de prueba

fecha_fin_prueba

Date

No

Fin periodo de prueba

satisfaccion_prueba

Enum

No

muy_satisfecho | satisfecho | neutral | insatisfecho

convertido_a_pago

Boolean

No

Si se convierte en cliente de pago

notas

Text

No

Historial de interacciones

2.3. Entidad: Asistencia

Campo

Tipo

Descripción

participante_id

FK

Referencia al participante

sesion_id

String

Identificador de sesión (ej: OI-1.1, M0-1, M1-3)

modulo

Enum

orientacion | modulo_0 | modulo_1 | ... | modulo_5 | acompanamiento

fecha

DateTime

Fecha y hora de la sesión

modalidad

Enum

presencial | online_sincronica

horas

Decimal

Horas de la sesión

asistio

Boolean

Si el participante asistió

evidencia

Enum

firma_hoja | conexion_videoconferencia | ambas

2.4. Entidad: Entregable

Campo

Tipo

Descripción

participante_id

FK

Referencia al participante

numero

Integer

Número del entregable (1-29 según apéndice Guía Didáctica)

titulo

String

Título del entregable

sesion_origen

String

Sesión donde se genera (ej: M2-2)

generado_con_ia

Boolean

Si fue generado con supervisión IA

estado

Enum

pendiente | en_progreso | completado | validado

validado_por

Enum

formador | orientador | pares | autoevaluacion

archivo_url

String

Enlace al archivo en la plataforma

fecha_completado

DateTime

Fecha de completado

2.5. Entidad: Evaluación Competencia IA

Campo

Tipo

Descripción

participante_id

FK

Referencia al participante

fecha

DateTime

Fecha de evaluación

tipo

Enum

inicial (M0) | intermedia (M3) | final (M5)

nivel

Enum

1_inicial | 2_basico | 3_competente | 4_autonomo

indicadores

JSON

Detalle por indicador de la rúbrica

evaluador

Enum

formador | autoevaluacion

3. Requisitos Funcionales

3.1. Vertical Andalucía +ei (Panel de Gestión del Programa)

Este es el panel que usa el equipo del programa (director, formador, orientador) para gestionar los 45 participantes, controlar asistencia, seguir entregables y generar informes para la justificación FSE+.

ID

Requisito

Prioridad

Módulo origen

Vertical/Herramienta

AEI-01

Dashboard de programa: vista global con KPIs en tiempo real (participantes activos, % asistencia media, entregables completados, inserciones acumuladas, estado embudo clientes piloto)

Alta

Todos

Vertical Andalucía +ei

AEI-02

Lista de participantes con filtros: por estado, ruta (A/B), pack, nivel digital, provincia, colectivo. Vista de tabla y vista de fichas.

Alta

Todos

Vertical Andalucía +ei

AEI-03

Ficha individual del participante: datos personales, ruta, pack, objetivos SMART, historial de asistencia, entregables, evaluaciones, cliente piloto asignado, estado inserción.

Alta

Todos

Vertical Andalucía +ei

AEI-04

Control de asistencia digital: el formador marca asistencia sesión por sesión. Cálculo automático de % asistencia y horas acumuladas (presencial + online sincrónico). Alerta si participante cae por debajo del 75%.

Alta

Todos

Vertical Andalucía +ei

AEI-05

Generador de informes FSE+: exportar datos de participantes, asistencia, inserciones en formato requerido por la Junta de Andalucía. Incluir indicadores de género, colectivo, resultado.

Alta

Cierre

Vertical Andalucía +ei

AEI-06

Registro de horas del equipo: el director, formador y orientador registran sus horas de dedicación al programa (para justificación económica).

Media

Todos

Vertical Andalucía +ei

3.2. Sistema de Entregables y Portfolio

ID

Requisito

Prioridad

Módulo origen

Vertical/Herramienta

POR-01

Portfolio digital del participante: página con los 29 entregables organizados por módulo. Cada entregable tiene estado (pendiente/completado/validado), archivo adjunto y fecha.

Alta

M0-M5

Emprendimiento + Empleabilidad

POR-02

Validación de entregables por el formador: el formador puede marcar un entregable como «validado» directamente desde la ficha del participante. Notificación al participante.

Alta

M0-M5

Vertical Andalucía +ei

POR-03

Autoevalúación: cuestionario integrado (sesión M5-4) con la rúbrica de 4 niveles de competencia IA. Resultado almacenado y visible en la ficha del participante.

Media

M5

Vertical Andalucía +ei

POR-04

Portfolio público: opción de hacer públicos los entregables seleccionados como evidencia de competencia (para mostrar a potenciales clientes o empleadores). URL compartible.

Media

M5

Empleabilidad / Emprendimiento

3.3. Copiloto IA: Contextualización por Participante

ID

Requisito

Prioridad

Módulo origen

Vertical/Herramienta

COP-01

Contexto persistente del participante: el copiloto IA debe tener acceso al perfil del participante (ruta, pack confirmado, sector, tipo de cliente, provincia) para personalizar todas las respuestas sin que el participante tenga que repetir su contexto en cada sesión.

Alta

M0+

Copiloto IA

COP-02

Modo formación: cuando el participante está en fase de formación, el copiloto actúa en modo «mentor»: no solo da respuestas sino que explica el razonamiento detrás, hace preguntas de comprensión y sugiere cómo evaluar críticamente el output.

Alta

M0-M5

Copiloto IA

COP-03

Prompts prediseñados por sesión: para cada sesión del programa, el copiloto ofrece 3-5 prompts sugeridos adaptados al pack del participante. Ej: en M2-2, el prompt sugerido es «Calcula mi punto de equilibrio con estos datos: [auto-rellenados del portfolio]».

Alta

M0-M5

Copiloto IA

COP-04

Historial de interacciones: todas las conversaciones del participante con el copiloto se almacenan y son revisables por el formador (para supervisar la calidad de la supervisión IA del participante).

Media

M0+

Copiloto IA

COP-05

Detección de alucinaciones: el copiloto incluye automáticamente disclaimers cuando genera datos numéricos o factuales que no puede verificar, reforzando el hábito de verificación del participante.

Media

M0+

Copiloto IA

3.4. CRM de Prospección de Clientes Piloto

ID

Requisito

Prioridad

Módulo origen

Vertical/Herramienta

CRM-01

Pipeline visual de prospección: vista tipo kanban con las 6 fases del embudo (identificado → contactado → interesado → propuesta → acuerdo → conversión). Arrastrar y soltar para cambiar de fase.

Alta

Prospección

CRM integrado

CRM-02

Ficha de negocio prospectado: todos los campos de la entidad «Negocio Prospectado», con historial de interacciones (notas, llamadas, visitas, emails).

Alta

Prospección

CRM integrado

CRM-03

Clasificación por colores: etiquetas visuales rojo/amarillo/verde según urgencia de necesidad digital.

Alta

Prospección

CRM integrado

CRM-04

Matching participante-negocio: interfaz para asignar un participante a un negocio. Sugerencias automáticas basadas en criterios (pack compatible, proximidad, nivel digital).

Alta

M5

CRM + Andalucía +ei

CRM-05

Acuerdo de prueba digital: plantilla de acuerdo pre-rellenada con datos del negocio y del participante. Firma digital integrada.

Alta

M5

Firma digital

CRM-06

Seguimiento post-prueba: registro de satisfacción, conversión a pago, testimonio recogido.

Media

Acomp.

CRM integrado

CRM-07

KPIs de prospección en dashboard: negocios por fase, tasas de conversión entre fases, cobertura de participantes.

Media

Prospección

Dashboard Andalucía +ei

3.5. Catálogo Digital de Packs

ID

Requisito

Prioridad

Módulo origen

Vertical/Herramienta

CAT-01

Plantillas de pack pre-configuradas: los 5 packs con sus 2-3 modalidades (Básico/Estándar/Premium), precios sugeridos, descripción y entregables estándar. El participante personaliza sobre la plantilla.

Alta

M5

Catálogo digital

CAT-02

Publicación en catálogo con 1 clic: el participante elige su pack, personaliza la descripción con el copiloto IA, y publica en su catálogo digital. URL pública compartible.

Alta

M5

Catálogo digital

CAT-03

Cobro recurrente mensual: cada pack publicado permite activar suscripción mensual vía Stripe. El cliente del participante paga directamente a través de la plataforma.

Alta

M5

Stripe

CAT-04

Botón de contratación: en cada ficha de pack publicada, botón «Contratar este servicio» que lleva al formulario de contacto o al pago directo.

Alta

M5

Catálogo + Stripe

3.6. Control de Asistencia y Horas

ID

Requisito

Prioridad

Módulo origen

Vertical/Herramienta

ASI-01

Registro de asistencia presencial: el formador marca asistencia de cada participante por sesión. Se asocia a la sesión del programa (OI-1.1, M0-1, etc.) con sus horas.

Alta

Todos

Vertical Andalucía +ei

ASI-02

Registro de asistencia online sincrónica: se registra la conexión del participante a la videoconferencia. Debe distinguirse de la actividad autónoma en la plataforma.

Alta

Online

Vertical Andalucía +ei

ASI-03

Cálculo automático de horas: acumulado de horas de orientación (≥10h), formación presencial + online (≥50h, con ≤37,5h = 75% para completado), acompañamiento (≥40h para inserción).

Alta

Todos

Vertical Andalucía +ei

ASI-04

Alertas automáticas: notificación al formador si un participante falta a 2 sesiones consecutivas o si su % de asistencia cae por debajo del 80% (margen de seguridad sobre el 75% mínimo).

Media

Todos

Vertical Andalucía +ei

ASI-05

Generación de hojas de servicios: exportar hojas de asistencia por sesión en formato PDF para firma presencial (requisito FSE+).

Alta

Todos

Vertical Andalucía +ei

3.7. Lean Canvas Interactivo

ID

Requisito

Prioridad

Módulo origen

Vertical/Herramienta

LCA-01

Canvas digital interactivo: 9 bloques editables, versionable (v1, v2, v3...), con historial de cambios. Cada versión se asocia a la sesión donde se genera.

Alta

M1

Emprendimiento

LCA-02

Asistencia IA en cada bloque: al hacer clic en un bloque, el copiloto sugiere contenido basado en el pack confirmado del participante.

Alta

M1

Emprendimiento + Copiloto

LCA-03

Fichas de prueba de hipótesis: vinculadas al Canvas. Cada hipótesis tiene su ficha de prueba (hipótesis, prueba, resultado, aprendizaje) y se enlaza con el bloque del Canvas que valida.

Alta

M1

Emprendimiento

3.8. Herramientas Financieras

ID

Requisito

Prioridad

Módulo origen

Vertical/Herramienta

FIN-01

Fichas de producto/servicio: plantilla con nombre, descripción, precio, coste variable, margen bruto. Pre-rellenada con datos del pack elegido.

Alta

M2

Emprendimiento

FIN-02

Calculadora de punto de equilibrio: input de gastos fijos + margen bruto → output de facturación mínima y número de clientes necesarios. Asistida por copiloto IA.

Alta

M2

Emprendimiento + Copiloto

FIN-03

Previsión financiera a 12 meses: tabla editable con 3 escenarios (conservador, realista, optimista). Pre-rellenada con datos de tarifa plana, Cuota Cero y precios del pack.

Alta

M2

Emprendimiento

FIN-04

Mapa de ayudas personalizado: búsqueda automática de ayudas disponibles según perfil del participante (edad, colectivo, provincia, IAE). Verificación contra BDNS.

Media

M2

Emprendimiento + JarabaLex

4. Configuración de Agentes IA

4.1. System prompts por fase del programa

El copiloto IA debe adaptar su comportamiento según la fase en la que se encuentra el participante. El system prompt base se enriquece con el contexto del participante y las instrucciones específicas de cada fase:

Fase

Comportamiento del copiloto

Restricciones

Orientación Inicial

Exploratorio: hace preguntas, ayuda a descubrir, sugiere con apertura. No empuja hacia ningún pack específico.

No dar consejos fiscales ni legales específicos aún

Módulo 0

Didáctico: explica qué hace y por qué, enseña a formular mejores instrucciones, señala cuando una instrucción es vaga.

Incluir siempre disclaimer en datos factuales

Módulos 1-3

Mentor: ayuda a construir, cuestiona las decisiones débiles, sugiere alternativas. Usa siempre el contexto del pack confirmado.

Recordar que los números financieros deben verificarse

Módulo 4

Productivo: genera contenido profesional, optimiza para SEO, crea calendarios. Modo «producción» más que «formación».

Contenido debe sonar natural, no a IA genérica

Módulo 5

Operativo: asiste en la ejecución real del proyecto piloto. Genera facturas, emails, propuestas comerciales reales.

Datos deben ser correctos para documentos legales

Acompañamiento

Autónomo: responde preguntas operativas del día a día del negocio. Modo «copiloto de negocio» continuo.

Derivar a formador/orientador si el problema excede capacidad IA

4.2. Variables de contexto del participante (inyectadas en cada sesión)

Variable

Ejemplo

Fuente

{nombre}

Lucía Martínez

Perfil participante

{pack_confirmado}

Pack 1: Contenido Digital Estándar

Ficha participante

{sector_cliente}

Hostelería y comercio local

Lean Canvas / Hipótesis

{ciudad}

Córdoba

Perfil participante

{precio_pack}

250 €/mes

Catálogo de servicios

{gastos_fijos}

73 €/mes

Plan financiero

{iae_cnae}

844 — Servicios de publicidad

Selección Módulo 3

{ruta}

Autoempleo (Opción A)

Ficha participante

{nivel_digital}

B (necesita apoyo puntual)

Evaluación OI

{lean_canvas_v}

v2 (validado)

Lean Canvas

5. Flujos de Usuario Principales

5.1. Flujo del participante: de la inscripción a la inserción

Paso

Acción del participante

Lo que ocurre en la plataforma

1

Recibe credenciales del programa

Se crea cuenta con rol «Participante Andalucía +ei», verticales Empleabilidad + Emprendimiento activados

2

Completa perfil básico + evaluación digital (OI-1.1)

Perfil creado, nivel digital evaluado, primera interacción con copiloto registrada

3

Completa fichas 1-8 + elige pack (OI)

Perfil RIASEC almacenado, pack pre-seleccionado registrado, ruta A/B definida

4

Completa Módulos 0-5 con entregables

29 entregables acumulados en portfolio, Lean Canvas versionado, plan financiero, web publicada

5

Publica pack en catálogo + activa cobros

Catálogo digital vivo con URL pública + Stripe activo

6

Ejecuta proyecto piloto (M5)

Flujo completo registrado: email + briefing + entregable + factura + postventa

7

Se da de alta como autónomo (Acomp.)

Fecha alta SS registrada, contador de meses inicia

8

Factura a primeros clientes

Facturas emitidas vía Stripe, ingresos registrados

9

Acumula 4 meses SS

Estado cambia a «insertado», se documenta para justificación FSE+

5.2. Flujo del formador: gestión diaria

Acción

Frecuencia

Herramienta en plataforma

Marcar asistencia de la sesión

Cada sesión

Panel Asistencia en Andalucía +ei

Revisar entregables pendientes de validación

Semanal

Portfolio de participantes

Supervisar interacciones IA de participantes

Semanal

Historial de copiloto (por participante)

Generar propuestas personalizadas para negocios piloto

Según pipeline

CRM + Copiloto IA

Matching participante-negocio

Módulo 5

Interfaz de matching en CRM

Revisar KPIs del programa

Semanal

Dashboard Andalucía +ei

5.3. Flujo del orientador: acompañamiento

Acción

Frecuencia

Herramienta en plataforma

Revisar ficha individual antes de sesión individual

Antes de cada sesión

Ficha participante en Andalucía +ei

Registrar notas de sesión individual

Después de cada sesión

Historial del participante

Seguir estado de inserción (alta SS, meses)

Quincenal

Ficha participante + alertas

Prospección de negocios

Continua (Meses 1-6)

CRM de prospección

Prospección de ofertas de empleo (ruta B)

Continua

CRM + fuentes externas

6. Roadmap de Implementación

6.1. Priorización por fases

La implementación se estructura en 3 fases alineadas con el calendario del programa. Cada fase debe estar completada ANTES de que los participantes la necesiten.

Fase

Plazo

Requisitos a implementar

Crítico para

Fase 1: Fundamentos

Semanas 1-4 (antes de Orientación)

AEI-01 a AEI-04, ASI-01 a ASI-03, COP-01, CRM-01 a CRM-03, POR-01

Arranque del programa

Fase 2: Formación

Semanas 5-8 (antes de Módulo 0)

COP-02 a COP-05, LCA-01 a LCA-03, FIN-01 a FIN-04, CRM-04 a CRM-05, CAT-01

Módulos 0-5

Fase 3: Inserción

Semanas 9-12 (antes de Acompañamiento)

CAT-02 a CAT-04, AEI-05 a AEI-06, ASI-04 a ASI-05, POR-02 a POR-04, CRM-06 a CRM-07

Proyecto piloto + inserción

6.2. Estimación de esfuerzo

Componente

Requisitos

Esfuerzo estimado

Complejidad

Dashboard Andalucía +ei

AEI-01 a AEI-06

40-60h desarrollo

Media-Alta

Sistema de asistencia

ASI-01 a ASI-05

20-30h desarrollo

Media

Portfolio y entregables

POR-01 a POR-04

25-35h desarrollo

Media

Contextualización copiloto IA

COP-01 a COP-05

30-40h (prompts + integración)

Alta

CRM prospección (extensión)

CRM-01 a CRM-07

35-50h desarrollo

Media-Alta

Catálogo de packs

CAT-01 a CAT-04

15-20h (plantillas + config)

Baja-Media

Lean Canvas interactivo

LCA-01 a LCA-03

20-30h desarrollo

Media

Herramientas financieras

FIN-01 a FIN-04

20-30h desarrollo

Media

TOTAL

38 requisitos

205-295h

Nota sobre el esfuerzo

La estimación asume que la plataforma ya tiene los componentes base (CRM, catálogo, firma digital, facturación Stripe, copiloto IA, editor visual) y que se trata de CONFIGURACIÓN y EXTENSIÓN, no de construcción desde cero. Muchos requisitos son ajustes de configuración, creación de plantillas y escritura de system prompts, no desarrollo de código nuevo.

Los requisitos de prioridad Alta deben estar operativos 1 semana antes de que los participantes los necesiten. Los de prioridad Media pueden desplegarse progresivamente.

7. Seguridad, RGPD y Requisitos FSE+

7.1. Protección de datos

Requisito

Implementación

Datos sensibles encriptados

NIF/NIE, datos colectivo vulnerable, datos salud (discapacidad) cifrados en reposo y en tránsito

Consentimiento informado

Formulario de consentimiento RGPD firmado digitalmente en la plataforma durante OI-2.2

Derecho de acceso y supresión

Panel del participante para solicitar exportación o borrado de sus datos

Aislamiento por tenant

Los datos del programa Andalucía +ei están aislados de otros usuarios de la plataforma

Registro de actividad

Log de accesos y modificaciones de datos sensibles (auditoría FSE+)

Servidores UE

Todos los datos en IONOS Alemania (ya implementado)

7.2. Requisitos de publicidad FSE+

Requisito (Art. 50 Reg. UE 2021/1060)

Implementación en plataforma

Logos obligatorios (UE, Ministerio, Junta, SAE, FSE+)

Footer de todas las páginas del vertical Andalucía +ei + documentos generados

Mención de cofinanciación

Texto estándar en todos los documentos exportados y en el perfil público del participante

Lenguaje no sexista e inclusivo

Revisión de todos los textos de la plataforma y de los prompts del copiloto IA

Comunicación a participantes

Información sobre la fuente de financiación en la pantalla de bienvenida del programa

8. Conclusión y Próximos Pasos

Este documento define 38 requisitos funcionales organizados en 8 áreas (dashboard del programa, portfolio/entregables, copiloto IA, CRM de prospección, catálogo de packs, control de asistencia, Lean Canvas y herramientas financieras), más las entidades de datos necesarias, los flujos de usuario principales, la configuración de agentes IA y el roadmap de implementación.

El esfuerzo total estimado es de 205-295 horas de desarrollo/configuración, distribuido en 3 fases de 4 semanas. La mayoría del esfuerzo es configuración y extensión de componentes existentes, no desarrollo desde cero.

Los próximos pasos recomendados son:

Semana 1: Revisión de este documento con el equipo técnico. Validar estimaciones de esfuerzo. Priorizar requisitos de Fase 1.

Semana 2-4: Implementación de Fase 1 (fundamentos): dashboard, asistencia, CRM básico, contextualización copiloto.

Semana 5-8: Implementación de Fase 2 (formación): Lean Canvas, herramientas financieras, prompts por sesión, matching.

Semana 9-12: Implementación de Fase 3 (inserción): catálogo de packs, informes FSE+, portfolio público.

Semana 13: Test integral con 3-5 personas del equipo simulando el flujo completo de un participante.

Fin de las Especificaciones Técnicas

Jaraba Impact Platform — Programa Andalucía +ei 2ª Ed. — Marzo 2026