
ESPECIFICACIONES TÉCNICAS
Método Jaraba™ en el SaaS

4 entregables para trasladar el método a la plataforma digital

1. Actualización de pepejaraba.com/metodo (v2)
2. Página del Método en plataformadeecosistemas.com
3. Landing de certificación y franquicia digital
4. Módulo Drupal de certificación (rúbrica + portfolio + emisión)

Plataforma de Ecosistemas Digitales S.L.
José Jaraba Muñoz, CEO & Fundador

Implementación por Claude Code — Marzo 2026 — Confidencial
 
ÍNDICE
PARTE A: Actualización de pepejaraba.com/metodo	1
A.1. Contexto técnico	1
A.2. Estructura de la página (wireframe de secciones)	1
A.3. Contenido textual exacto por sección	1
Sección 1: Hero	1
Sección 2: El problema	1
Sección 3: La solución	1
Sección 4: Las 3 capas	1
Sección 5: Las 4 competencias	1
Secciones 6, 7, 8: CID + 3 caminos + CTA	1
A.4. Requisitos técnicos de implementación	1
PARTE B: Página del Método en plataformadeecosistemas.com	1
B.1. Contexto	1
B.2. Estructura de la página	1
B.3. Requisitos de implementación	1
PARTE C: Landing de Certificación y Franquicia Digital	1
C.1. Contexto	1
C.2. Estructura de la landing	1
C.3. Contenido del formulario de contacto	1
Modo «Soy profesional»	1
Modo «Represento a una entidad»	1
C.4. Requisitos de implementación	1
PARTE D: Módulo Drupal de Certificación	1
D.1. Arquitectura	1
D.2. Modelo de datos	1
D.2.1. Tabla: jaraba_certifications	1
D.2.2. Tabla: jaraba_portfolio_items	1
D.2.3. Tabla: jaraba_rubric_evaluations	1
D.3. Requisitos funcionales	1
D.3.1. Ciclo del participante	1
D.3.2. Ciclo del evaluador (formador)	1
D.3.3. Generación de certificado	1
D.3.4. Administración	1
D.4. Estructura de archivos del módulo	1
D.5. Estimación de esfuerzo	1
Resumen y Estimación Global	1
Prioridad de implementación	1

 
PARTE A: Actualización de pepejaraba.com/metodo

A.1. Contexto técnico
Propiedad	Valor
URL	https://pepejaraba.com/metodo
CMS	Drupal 11 (mismo ecosistema_jaraba_core)
Contenido actual	CID v1: 3 fases + 4 principios. Sin IA, sin rúbrica, sin certificación
Acción	REEMPLAZAR contenido completo manteniendo URL y estructura de página
Audiencia	Marca personal: emprendedores, pymes, curiosos, potenciales clientes B2C
Tono	Cálido, directo, sin tecnicismos. Primera persona. «Sin humo»

A.2. Estructura de la página (wireframe de secciones)
La página se estructura en 8 secciones verticales, responsive, con scroll continuo. Cada sección es un bloque de contenido Drupal (paragraph type o block) editable desde el CMS.

Sección	Nombre	Contenido	CTA
1	Hero	Título: «El Método Jaraba». Subtítulo: «Supervisión de Agentes IA como competencia profesional.» Badge: «46% de inserción laboral en colectivos vulnerables»	Ver cómo funciona ↓
2	El problema	«Hay un puente roto entre los recursos y las personas.» Conectar con el manifiesto. 3 problemas: herramientas que no entienden, formación que no aterriza, IA que abruma en vez de ayudar.	
3	La solución: el flujo invertido	Tabla visual: Modelo tradicional vs Método Jaraba. Tarea con IA → Supervisión → Comprensión. Metáfora del director de obra.	
4	Las 3 capas	3 tarjetas grandes: Criterio (¿para qué?), Supervisión IA (¿cómo?), Posicionamiento (¿cómo cobro?). Cada tarjeta con pregunta + descripción 2 líneas.	
5	Las 4 competencias	4 bloques horizontales: Pedir, Evaluar, Iterar, Integrar. Cada uno con icono + título + ejemplo práctico de 1 línea.	
6	El CID: 90 días	Timeline visual de 3 fases: Días 1-30 (Criterio), 31-60 (Supervisión), 61-90 (Posicionamiento). Entregable por fase.	
7	3 caminos, 1 método	3 columnas: Empleabilidad / Emprendimiento / Digitalización. Cada una con audiencia + resultado + link al vertical correspondiente en plataformadeecosistemas.com.	Descubre tu camino →
8	Evidencia + CTA final	Dato: 46% inserción (Andalucía +ei). Logos institucionales. Principios «sin humo». Botón final.	Empezar gratis →

A.3. Contenido textual exacto por sección

Sección 1: Hero
Contenido
Badge: Método Jaraba™

Título H1: Aprende a supervisar agentes de IA.
            Y que eso se convierta en tu profesión.

Subtítulo: Un sistema de capacitación en 90 días que te enseña a generar
impacto económico real dirigiendo inteligencia artificial.
Sin humo. Sin tecnicismos. Con resultados medibles.

Dato destacado: 46% de inserción laboral
Sub-dato: en colectivos vulnerables — Programa Andalucía +ei, 1ª Ed.

CTA primario: Ver cómo funciona ↓
CTA secundario: Empezar gratis → (link a registro plataforma)

Sección 2: El problema
Contenido
Título H2: Vi un puente roto y decidí construirlo.

Párrafo: Durante 30 años gestioné más de 100 millones de euros en fondos
europeos. Diseñé planes estratégicos para provincias enteras. Y vi cómo
esos recursos no llegaban a quien más los necesitaba.

Tarjeta 1: «Me dicen que use IA, pero no sé por dónde empezar»
Descripción: Hay miles de herramientas. Ninguna te enseña a pensar.

Tarjeta 2: «Hice un curso y sigo sin saber cómo cobrar por esto»
Descripción: La formación tradicional enseña teoría. El método enseña a facturar.

Tarjeta 3: «Mi negocio es invisible en internet»
Descripción: No necesitas un experto. Necesitas aprender a dirigir uno (de IA).

Sección 3: La solución
Contenido
Título H2: No aprendes a hacer las cosas. Aprendes a dirigir a quien las hace.

Párrafo: En la formación tradicional, primero te explican la teoría,
luego practicas, y meses después (si llegas) lo aplicas.

El Método Jaraba lo invierte:

Paso 1: Haces la tarea con un agente de IA. Desde el día 1.
Paso 2: Supervisas el resultado. ¿Está bien? ¿Falta algo? ¿Suena a ti?
Paso 3: En ese proceso, entiendes el concepto. Sin que nadie te dé clase.

Visual: Tú eres el director de obra. La IA es tu equipo especializado.
Tú decides qué se construye y cómo. Ellos ejecutan bajo tu supervisión.

Sección 4: Las 3 capas
Contenido
Título H2: Tres capas. Una competencia profesional completa.

Capa 1 — CRITERIO
Pregunta: ¿Para qué?
Descripción: Saber lo que quieres. Entender tu mercado. Tomar decisiones.
Esto no lo sustituye ninguna IA.

Capa 2 — SUPERVISIÓN IA
Pregunta: ¿Cómo con IA?
Descripción: Pedir. Evaluar. Iterar. Integrar. Las 4 competencias que convierten
a cualquier persona en director/a de un equipo de agentes de IA.

Capa 3 — POSICIONAMIENTO
Pregunta: ¿Cómo cobro?
Descripción: Propuesta de valor. Presencia digital. Embudo de captación.
Porque de nada sirve saber si no facturas.

Sección 5: Las 4 competencias
Contenido
Título H2: 4 competencias que se entrenan, se miden y se certifican.

PEDIR — Formular instrucciones claras al agente IA.
Ejemplo: «Calcula el punto de equilibrio de una cafetería con estos datos...»

EVALUAR — Determinar si el resultado es correcto y útil.
Ejemplo: «La IA dice que la tarifa plana es 60€. ¿Sigue siendo así en 2026?»

ITERAR — Ajustar las instrucciones para mejorar el output.
Ejemplo: «Suena demasiado formal. Reescríbelo como si hablaras con un vecino.»

INTEGRAR — Combinar outputs de varios agentes en un resultado final.
Ejemplo: «Une el plan financiero, el Lean Canvas y el pitch en un solo documento.»

Secciones 6, 7, 8: CID + 3 caminos + CTA
Contenido
== SECCIÓN 6: EL CICLO DE 90 DÍAS ==
Título H2: 3 fases. 90 días. Resultados que puedes medir.
Fase 1 (Días 1-30): Criterio y primeras tareas con IA.
  Entregable: Diagnóstico + hipótesis + primeras tareas productivas.
Fase 2 (Días 31-60): Supervisión y construcción.
  Entregable: Portfolio con 5+ outputs profesionales reales.
Fase 3 (Días 61-90): Posicionamiento e impacto.
  Entregable: Presencia digital + proyecto piloto + primer ingreso.

== SECCIÓN 7: 3 CAMINOS, 1 MÉTODO ==
Título H2: Un método. Tres aplicaciones. Tu elección.
Columna 1 — BUSCO TRABAJO: CV con IA, entrevistas simuladas, perfil digital.
  CTA: Impulsar mi carrera → (/empleabilidad)
Columna 2 — QUIERO EMPRENDER: Lean Canvas IA, packs, clientes, facturación.
  CTA: Lanzar mi negocio → (/emprendimiento)
Columna 3 — TENGO NEGOCIO: Web, redes, reseñas, embudo de captación.
  CTA: Digitalizar mi negocio → (/comercioconecta)

== SECCIÓN 8: EVIDENCIA + CTA FINAL ==
Dato grande: 46% inserción laboral.
Sub: Programa Andalucía +ei, 1ª Edición. Colectivos vulnerables.
Frase: Si funciona con el colectivo más difícil, funciona contigo.
CTA: Empieza gratis → (registro plataformadeecosistemas.com)
Logos institucionales: FAMP, Diputaciones, Ayuntamientos (ya presentes).

A.4. Requisitos técnicos de implementación
ID	Requisito	Prioridad	Esfuerzo
MET-A01	Reemplazar contenido de la ruta /metodo en el CMS Drupal de pepejaraba.com. Mantener URL, metadatos SEO, y estructura de página. El contenido actual se archiva como revisión.	Crítica	4h
MET-A02	Diagrama visual «Un núcleo, tres aplicaciones» insertado como imagen SVG inline o PNG responsive. Fuente: metodo-jaraba-diagram.png ya generado.	Alta	2h
MET-A03	Timeline visual del CID de 90 días con 3 fases como componente CSS (no imagen). Responsive, animado al scroll.	Alta	4h
MET-A04	4 tarjetas de competencias (Pedir, Evaluar, Iterar, Integrar) con iconos SVG del sistema de iconos existente.	Alta	2h
MET-A05	3 columnas «caminos» con links a los verticales de plataformadeecosistemas.com. Cross-domain link con UTM: utm_source=pepejaraba&utm_medium=metodo&utm_content=empleabilidad|emprendimiento|digitalizacion.	Alta	2h
MET-A06	Meta SEO: title=«Método Jaraba: Supervisión de Agentes IA en 90 Días», description=«Sistema de capacitación profesional para generar impacto económico supervisando agentes IA. 46% de inserción laboral. 3 capas, 4 competencias, 90 días.», OG image=diagrama.	Alta	1h
MET-A07	Schema.org JSON-LD: @type=Course con provider=PED S.L., name=Método Jaraba, description, url, hasCourseInstance con 3 aplicaciones.	Media	2h

 
PARTE B: Página del Método en plataformadeecosistemas.com

B.1. Contexto
Propiedad	Valor
URL	https://plataformadeecosistemas.com/es/metodo
Estado actual	NO EXISTE. La home menciona «Metodología Probada» pero no hay página dedicada
Acción	CREAR página nueva + añadir enlace en el menú principal (bajo «Soluciones» o como item propio)
Audiencia	Usuarios del SaaS, potenciales clientes B2C/B2B, evaluadores de programas públicos
Tono	Profesional, orientado a producto. Tercera persona. Datos y evidencia.
Diferencia con pepejaraba.com/metodo	Pepejaraba.com es marca personal (historia de José). Plataformadeecosistemas.com es marca de producto (cómo funciona la plataforma).

B.2. Estructura de la página
Sección	Nombre	Contenido
1	Hero	Título: «Método Jaraba™: la metodología detrás de la plataforma». Sub: «3 capas de capacidad, 4 competencias IA, 90 días de ciclo. Probado con 46% de inserción laboral.»
2	Cómo funciona	3 pasos visuales: Tarea con IA → Supervisión → Comprensión. Conexión con los 11 agentes IA de la plataforma.
3	Las 3 capas integradas	3 capas como cards con link a los verticales correspondientes. Criterio → verticales de orientación. Supervisión → copiloto IA. Posicionamiento → catálogo + Stripe.
4	Mapa de verticales	Grid de los 10 verticales mostrando cómo cada uno implementa el método. Reutilizar los iconos existentes del menú.
5	Certificación	Preview de la rúbrica de 4 niveles. Link a la landing de certificación (Parte C).
6	Para instituciones	Bloque B2G: «Licencie el Método Jaraba para sus programas de empleo». Link a la landing de franquicia (Parte C).
7	Evidencia	46% inserción + logos institucionales + cita del fundador.
8	CTA	«Prueba la plataforma gratis durante 14 días» + «Ver planes y precios»

B.3. Requisitos de implementación
ID	Requisito	Prioridad	Esfuerzo
MET-B01	Crear nodo de página /es/metodo en Drupal con las 8 secciones como paragraph types del tema existente (hero_section, features_grid, cta_block, etc.). Integrar en el menú principal.	Crítica	6h
MET-B02	Sección «Mapa de verticales»: reutilizar el componente de grid de verticales de la home pero añadiendo una línea por vertical que explica qué capa del método cubre.	Alta	3h
MET-B03	Bloque de certificación con rúbrica visual (4 niveles como progress bar o steps). Link a /es/certificacion.	Alta	2h
MET-B04	Bloque B2G con formulario de contacto específico para instituciones. Campos: nombre entidad, tipo (pública/privada), programa que ejecuta, nº participantes, teléfono, email.	Alta	3h
MET-B05	Breadcrumb: Inicio > Método Jaraba. Canonical URL. Meta SEO específicos.	Media	1h
MET-B06	Añadir link «Metodología» al megamenú de plataformadeecosistemas.com. Posición: entre «Soluciones» y «Precios», o como subitem de Soluciones.	Media	1h

 
PARTE C: Landing de Certificación y Franquicia Digital

C.1. Contexto
Propiedad	Valor
URL	https://plataformadeecosistemas.com/es/certificacion
URL alternativa	https://plataformadeecosistemas.com/es/partners (para franquicia)
Estado actual	NO EXISTE
Audiencia dual	1) Profesionales que quieren certificarse. 2) Entidades que quieren licenciar el método.
Tono	Aspiracional pero serio. Datos concretos. Sin promesas vacías.
Objetivo de conversión	Formulario de contacto completado (para ambas audiencias).

C.2. Estructura de la landing
Sección	Nombre	Contenido
1	Hero	Título: «Certíficate en Supervisión de Agentes IA». Sub: «El Método Jaraba forma profesionales que saben dirigir IA para generar impacto. Certifica tu competencia.»
2	2 caminos	Split visual: izquierda «Soy profesional → Quiero certificarme» / derecha «Soy entidad → Quiero licenciar el método». Anclas a las secciones correspondientes.
3	Para profesionales	Rúbrica visual de 4 niveles. 3 tipos de certificación (Profesional, Especialista, Formador). Portfolio de evidencias. Validez. Precio (por definir o «consúltenos»).
4	Para entidades	Qué incluye la licencia (7 componentes del doc fundacional). Modelo económico (setup + SaaS + royalty). Dato diferencial: 46% inserción. Caso Andalucía +ei como referencia.
5	Comparativa	Tabla: «Formación genérica en IA» vs «Método Jaraba». Filas: certificable, replicable, con plataforma, con evidencia, con agentes IA integrados.
6	FAQ	6-8 preguntas: ¿Necesito conocimientos previos? ¿Cuánto dura? ¿Es online? ¿Qué plataforma se usa? ¿Cuánto cuesta? ¿Qué validez tiene el certificado?
7	Formulario de contacto	Formulario unificado con selector: «Soy profesional» / «Represento a una entidad». Campos condicionales según selección.
8	Trust signals	Logos institucionales + dato de inserción + cita fundador + «Primera implementación: Programa Andalucía +ei, Junta de Andalucía».

C.3. Contenido del formulario de contacto
Modo «Soy profesional»
Campo	Tipo	Obligatorio
Nombre completo	text	Sí
Email	email	Sí
Teléfono	tel	Sí
Situación actual	select: Empleado/a | Desempleado/a | Emprendiendo | Autónomo/a | Otro	Sí
Aplicación de interés	select: Empleabilidad | Emprendimiento | Digitalización | No lo tengo claro	Sí
Provincia	select: 52 provincias de España	Sí
Cómo nos conoció	select: RRSS | Búsqueda Google | Recomendación | Programa público | Otro	Sí
Consentimiento RGPD	checkbox	Sí

Modo «Represento a una entidad»
Campo	Tipo	Obligatorio
Nombre de la entidad	text	Sí
Tipo de entidad	select: Entidad colaboradora PIL | Fundación | Cámara de Comercio | Ayuntamiento | Diputación | Otro	Sí
Persona de contacto	text	Sí
Cargo	text	Sí
Email institucional	email	Sí
Teléfono	tel	Sí
Programa que ejecuta o quiere ejecutar	textarea	No
Nº aproximado de participantes/año	select: <25 | 25-50 | 50-100 | 100-200 | >200	Sí
Provincia	select	Sí
Consentimiento RGPD	checkbox	Sí

C.4. Requisitos de implementación
ID	Requisito	Prioridad	Esfuerzo
MET-C01	Crear página /es/certificacion en Drupal con las 8 secciones. Formulario dual con campos condicionales según tipo de usuario.	Crítica	8h
MET-C02	Rúbrica visual interactiva: 4 niveles como stepper horizontal. Al hacer clic en cada nivel, se despliega la descripción + indicadores + requisitos.	Alta	4h
MET-C03	Tabla comparativa «Formación genérica vs Método Jaraba» con checkmarks verdes/rojas. Responsive (se convierte en lista en móvil).	Alta	2h
MET-C04	Formulario con integración CRM: al enviar, crear lead en el CRM de la plataforma con tipo=«certificacion_profesional» o tipo=«franguicia_entidad».	Alta	4h
MET-C05	Email automático post-formulario: confirmación al usuario + notificación a José con datos del lead.	Alta	2h
MET-C06	FAQ con componente accordion/collapsible existente del tema.	Media	1h
MET-C07	SEO: title, description, OG, Schema.org @type=EducationalOrganization.	Media	1h

 
PARTE D: Módulo Drupal de Certificación

Alcance
Este módulo permite gestionar el ciclo completo de certificación del Método Jaraba dentro de Jaraba Impact Platform: inscripción, evaluación con rúbrica, portfolio de evidencias, emisión de certificado digital, y renovación.
Es la pieza que convierte el método en un producto digital escalable y medible.

D.1. Arquitectura
Propiedad	Valor
Módulo	ecosistema_jaraba_certificacion
Dependencias	ecosistema_jaraba_core (usuarios, CRM, verticales)
Content types nuevos	jaraba_certification, jaraba_portfolio_item, jaraba_rubric_evaluation
Vistas nuevas	Panel de certificaciones (admin), Mi certificación (usuario), Directorio de certificados (público)
Integraciones	PDF generator (certificado), firma digital (existente), Stripe (pago certificación)

D.2. Modelo de datos

D.2.1. Tabla: jaraba_certifications
Campo	Tipo	Descripción
id	BIGINT AUTO_INCREMENT PK	Identificador
user_id	BIGINT FK users	Participante
certification_type	ENUM('profesional','especialista','formador','entidad')	Tipo de certificación
application_id	ENUM('empleabilidad','emprendimiento','digitalizacion')	Aplicación del método
status	ENUM('inscrito','en_evaluacion','aprobado','rechazado','expirado','renovado')	Estado
rubric_level_achieved	INT (1-4)	Nivel alcanzado en la rúbrica
evaluator_id	BIGINT FK users NULL	Formador evaluador
evaluation_date	DATE NULL	Fecha de evaluación
certificate_code	VARCHAR(20) UNIQUE NULL	Código único del certificado (JI-2026-XXXXX)
certificate_pdf_path	VARCHAR(255) NULL	Ruta al PDF del certificado generado
valid_from	DATE NULL	Fecha de inicio de validez
valid_until	DATE NULL	Fecha de expiración
payment_id	VARCHAR(100) NULL	ID de pago Stripe si aplica
notes	TEXT NULL	Notas del evaluador
created_at	DATETIME	Fecha de creación
updated_at	DATETIME	Fecha de actualización

D.2.2. Tabla: jaraba_portfolio_items
Campo	Tipo	Descripción
id	BIGINT AUTO_INCREMENT PK	Identificador
certification_id	BIGINT FK jaraba_certifications	Certificación a la que pertenece
title	VARCHAR(255)	Título de la evidencia
description	TEXT	Descripción del trabajo realizado
competency	ENUM('pedir','evaluar','iterar','integrar')	Competencia que demuestra
layer	ENUM('criterio','supervision_ia','posicionamiento')	Capa del método que cubre
file_path	VARCHAR(255) NULL	Archivo adjunto (PDF, imagen, link)
external_url	VARCHAR(500) NULL	URL externa (web publicada, perfil LinkedIn, etc.)
ai_model_used	VARCHAR(50) NULL	Modelo IA usado para generar el output
evaluator_score	INT (1-4) NULL	Puntuación del evaluador para esta evidencia
evaluator_feedback	TEXT NULL	Feedback del evaluador
created_at	DATETIME	Fecha de subida

D.2.3. Tabla: jaraba_rubric_evaluations
Campo	Tipo	Descripción
id	BIGINT AUTO_INCREMENT PK	Identificador
certification_id	BIGINT FK jaraba_certifications	Certificación evaluada
evaluator_id	BIGINT FK users	Formador evaluador
comp_pedir	INT (1-4)	Puntuación en competencia Pedir
comp_evaluar	INT (1-4)	Puntuación en competencia Evaluar
comp_iterar	INT (1-4)	Puntuación en competencia Iterar
comp_integrar	INT (1-4)	Puntuación en competencia Integrar
layer_criterio	INT (1-4)	Puntuación en capa Criterio
layer_supervision	INT (1-4)	Puntuación en capa Supervisión IA
layer_posicionamiento	INT (1-4)	Puntuación en capa Posicionamiento
overall_level	INT (1-4)	Nivel global alcanzado (mínimo de las 4 competencias)
overall_notes	TEXT NULL	Comentarios generales
recommended_action	ENUM('aprobar','mejorar','rechazar')	Recomendación del evaluador
evaluated_at	DATETIME	Fecha de evaluación

D.3. Requisitos funcionales
D.3.1. Ciclo del participante
ID	Requisito	Prioridad	Esfuerzo
CERT-01	Inscripción a certificación: desde el panel del usuario, botón «Solicitar certificación Jaraba Impact». Seleccionar tipo (profesional/especialista) y aplicación (empleabilidad/emprendimiento/digitalización). Pago via Stripe si aplica.	Crítica	6h
CERT-02	Panel de portfolio: el participante sube evidencias (archivos, URLs, capturas). Cada evidencia se etiqueta con la competencia y la capa que demuestra. Mínimo de evidencias según tipo de certificación.	Crítica	10h
CERT-03	Validación de portfolio: el sistema verifica que se cumplen los mínimos de evidencias por competencia antes de permitir solicitar evaluación. Checklist visual de progreso.	Alta	4h
CERT-04	Mi certificado: página donde el usuario certificado ve su certificado digital, código de verificación, fecha de validez, y botón de descarga PDF.	Alta	4h
CERT-05	Renovación: 30 días antes de la expiración, email automático + notificación en dashboard. Para renovar: añadir 2 evidencias nuevas + pago.	Media	4h

D.3.2. Ciclo del evaluador (formador)
ID	Requisito	Prioridad	Esfuerzo
CERT-06	Panel de evaluación: lista de certificaciones pendientes de evaluación. Vista del portfolio completo del participante. Formulario de rúbrica con puntuación 1-4 por cada competencia y capa.	Crítica	8h
CERT-07	Rúbrica interactiva: al seleccionar una puntuación (1-4), el sistema muestra los indicadores observables de ese nivel (del documento fundacional). El evaluador confirma que los indicadores se cumplen.	Alta	4h
CERT-08	Cálculo automático del nivel: el nivel global es el mínimo de las 4 competencias. Si una competencia es nivel 2 y las demás son nivel 3, el nivel global es 2. El sistema avisa si hay disparidad >1 nivel.	Alta	2h
CERT-09	Acción post-evaluación: aprobar (genera certificado), pedir mejoras (devuelve al participante con feedback específico), rechazar (con motivo documentado).	Alta	3h

D.3.3. Generación de certificado
ID	Requisito	Prioridad	Esfuerzo
CERT-10	Generación de PDF: certificado con nombre del participante, tipo de certificación, aplicación, nivel alcanzado, fecha, código de verificación, firma digital del evaluador, logo Método Jaraba, logos institucionales si aplica.	Crítica	8h
CERT-11	Código de verificación público: URL pública plataformadeecosistemas.com/certificado/{codigo} que muestra nombre, tipo, nivel, fecha, estado (válido/expirado). Sin datos sensibles.	Alta	4h
CERT-12	Directorio público de certificados: página pública con buscador por nombre o código. Solo muestra certificados activos con consentimiento del titular.	Media	4h

D.3.4. Administración
ID	Requisito	Prioridad	Esfuerzo
CERT-13	Dashboard de certificaciones: KPIs (certificaciones emitidas, en evaluación, renovaciones pendientes, ingresos por certificación). Filtros por tipo, aplicación, evaluador, fecha.	Alta	6h
CERT-14	Gestión de evaluadores: asignar rol «Formador Certificado Jaraba» a usuarios. Solo los usuarios con este rol pueden evaluar portfolios.	Alta	2h
CERT-15	Configuración de rúbrica: panel admin para editar los indicadores de cada nivel de cada competencia sin tocar código. Los indicadores se almacenan en Drupal config.	Media	4h
CERT-16	Configuración de precios: precio por tipo de certificación, con opción de «gratuito para participantes de programas públicos» (flag en la entidad Participante).	Media	2h

D.4. Estructura de archivos del módulo
Estructura de directorios
modules/custom/ecosistema_jaraba_certificacion/
├── ecosistema_jaraba_certificacion.info.yml
├── ecosistema_jaraba_certificacion.module
├── ecosistema_jaraba_certificacion.install
├── ecosistema_jaraba_certificacion.routing.yml
├── ecosistema_jaraba_certificacion.services.yml
├── ecosistema_jaraba_certificacion.permissions.yml
├── ecosistema_jaraba_certificacion.links.menu.yml
├── config/
│   ├── install/
│   │   ├── ecosistema_jaraba_certificacion.settings.yml
│   │   └── ecosistema_jaraba_certificacion.rubric.yml
│   └── schema/
│       └── ecosistema_jaraba_certificacion.schema.yml
├── src/
│   ├── Controller/
│   │   ├── CertificationDashboardController.php
│   │   ├── PortfolioController.php
│   │   ├── EvaluationController.php
│   │   └── PublicVerificationController.php
│   ├── Service/
│   │   ├── CertificationService.php
│   │   ├── PortfolioValidationService.php
│   │   ├── RubricEvaluationService.php
│   │   ├── CertificatePdfGeneratorService.php
│   │   └── CertificationRenewalService.php
│   ├── Form/
│   │   ├── CertificationApplicationForm.php
│   │   ├── PortfolioItemForm.php
│   │   ├── RubricEvaluationForm.php
│   │   ├── CertificationSettingsForm.php
│   │   └── RubricConfigForm.php
│   ├── Plugin/
│   │   └── Block/
│   │       ├── MyCertificationBlock.php
│   │       └── CertificationKpiBlock.php
│   └── EventSubscriber/
│       └── CertificationExpirationSubscriber.php
├── templates/
│   ├── certification-dashboard.html.twig
│   ├── portfolio-panel.html.twig
│   ├── rubric-evaluation-form.html.twig
│   ├── certificate-pdf.html.twig
│   ├── public-verification.html.twig
│   └── certification-progress.html.twig
├── css/
│   └── certification-panel.css
├── js/
│   └── rubric-interactive.js
└── tests/
    └── src/Unit/
        ├── RubricEvaluationServiceTest.php
        └── PortfolioValidationServiceTest.php

D.5. Estimación de esfuerzo
Componente	Requisitos	Horas
Ciclo del participante	CERT-01 a CERT-05	28h
Ciclo del evaluador	CERT-06 a CERT-09	17h
Generación de certificado	CERT-10 a CERT-12	16h
Administración	CERT-13 a CERT-16	14h
Testing + QA	Transversal	10h
TOTAL	16 requisitos	85h (~2 semanas)

 
Resumen y Estimación Global

Parte	Entregable	Requisitos	Esfuerzo estimado
A	pepejaraba.com/metodo (v2)	7 requisitos (MET-A01 a A07)	17h
B	plataformadeecosistemas.com/metodo	6 requisitos (MET-B01 a B06)	16h
C	Landing certificación / franquicia	7 requisitos (MET-C01 a C07)	22h
D	Módulo Drupal certificación	16 requisitos (CERT-01 a CERT-16)	85h
	TOTAL	36 requisitos	140h (~3,5 semanas)

Prioridad de implementación
Prioridad	Pieza	Justificación
1 (inmediato)	Parte A: pepejaraba.com/metodo	Es contenido puro, sin desarrollo. Claude Code puede reemplazar el contenido del CMS en 1 día. Impacto inmediato en posicionamiento.
2 (semana 1-2)	Parte B: plataformadeecosistemas.com/metodo	Complementa la Parte A con la visión de producto. Conecta el método con los verticales y los agentes IA.
3 (semana 2-3)	Parte C: Landing certificación/franquicia	Empieza a captar leads de entidades y profesionales interesados ANTES de que el módulo esté listo.
4 (semana 3-6)	Parte D: Módulo Drupal certificación	Es la pieza más compleja pero puede esperar porque la 2ª Edición aún está en fase de formación (las certificaciones se emiten al final del CID).

Fin de las Especificaciones Técnicas
Método Jaraba™ en el SaaS — Jaraba Impact Platform — Marzo 2026
