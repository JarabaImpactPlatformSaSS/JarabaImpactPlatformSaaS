



EVALUACIÓN ARQUITECTÓNICA

Jaraba Canvas:
¿Configurador o Constructor?

Análisis comparativo: Enfoque híbrido custom (SortableJS + iframe) vs GrapesJS para alcanzar un editor visual de clase mundial


Fecha:	4 de Febrero de 2026
Propósito:	Evaluación objetiva sin sesgos
Alcance:	Arquitectura, UX, costos, riesgos
Veredicto:	Recomendación fundamentada
 
1. La Pregunta Central
La auditoría de EDI revela que se desviaron de la especificación GrapesJS y construyeron un enfoque híbrido custom con SortableJS + iframe. El score global reportado es 65-78% de cumplimiento con las specs. La cuestión no es quién tiene razón, sino: ¿cuál de los dos enfoques produce un editor de clase mundial para los tenants de Jaraba?
Para responder con rigor, necesitamos distinguir dos conceptos que la auditoría mezcla: un configurador de páginas y un constructor de páginas. Son productos fundamentalmente diferentes.
1.1 Configurador vs Constructor
Dimensión	Configurador de Páginas	Constructor de Páginas
¿Qué hace el usuario?	Selecciona plantillas y cambia opciones en formularios	Arrastra componentes a un canvas y los edita visualmente in-situ
Interacción principal	Selects, toggles, inputs en un panel lateral	Drag-and-drop, click-to-edit, inline text editing
¿Edita contenido donde lo ve?	No. Edita en un formulario, ve resultado en preview separado	Sí. Click en texto, escribe. Click en imagen, reemplaza.
Undo/Redo	Raramente necesario (los forms son atómicos)	Esencial (cada acción de edición es granular)
Flexibilidad	Limitada a las opciones pre-definidas	Libre dentro del sistema de componentes
Curva de aprendizaje	Baja (formularios familiares)	Media (requiere aprender la interfaz visual)
Ejemplo de mercado	Squarespace 1.0, WordPress Customizer	Webflow, Framer, Elementor, Wix
Percepción del usuario	"Personalizo mi sitio"	"Construyo mi sitio"

La implementación actual de EDI es un configurador competente. Las specs definían un constructor. Para alcanzar "clase mundial" en 2026, el mercado exige un constructor.
 
2. Análisis del Enfoque EDI: SortableJS + Iframe
2.1 Lo Que Funciona Bien
Voy a ser justo: la implementación de EDI tiene méritos reales que debo reconocer.
•	Fidelidad de preview: el iframe muestra la página REAL renderizada por Twig. No hay discrepancia entre preview y resultado final. Esto es genuinamente superior a un canvas GrapesJS que podría tener diferencias sutiles de renderizado.
•	Velocidad de desarrollo: ~60-70h vs 120-155h estimadas. Entregaron un MVP funcional en la mitad del tiempo. En un contexto de recursos limitados, esto tiene valor.
•	Hot-swap de variantes: el cambio de header/footer via postMessage sin recarga es una solución elegante y performante.
•	Zero dependencias externas: JavaScript vanilla + Drupal behaviors. Código que el equipo entiende al 100%.
•	Bloques premium: los 12 Aceternity + 10 Magic UI están implementados al 100%.
2.2 Lo Que No Funciona
Ahora, los gaps críticos que impiden llamar a esto un editor de "clase mundial":
Gap	Impacto en UX	Por Qué Importa
0% Undo/Redo	CRÍTICO	Todo editor de contenido desde Microsoft Word (1983) tiene undo. Su ausencia genera ansiedad en el usuario y pérdida irreversible de trabajo. Es inaceptable en 2026.
0% Edición inline	CRÍTICO	El usuario no puede hacer click en un título y editarlo. No puede cambiar el texto de un botón CTA haciéndole click. Debe ir a un formulario. Esto es UX de 2015.
0% Drag-drop de bloques al canvas	ALTO	SortableJS REORDENA items existentes. No permite ARRASTRAR un bloque nuevo desde la sidebar y SOLTARLO entre bloques existentes en la posición exacta deseada.
0% Edición de menú	MEDIO	El menú está fijo desde el tema. El tenant no puede añadir/quitar/reordenar items de navegación desde el editor.
0% Edición inline logo/CTA	MEDIO	Cambiar logo o CTA requiere salir del canvas a otro panel.
60% Auto-save	MEDIO	Guardado manual con dirty tracking. El usuario puede perder trabajo si cierra la pestaña.
0% Onboarding tour	BAJO	Pospuesto, implementable con driver.js en 8h.
2.3 El Argumento del Iframe: ¿Realmente es Superior?
La auditoría afirma que el iframe ofrece "fidelidad 100%" y es "mejor que GrapesJS". Examinemos esto con honestidad.
Es verdad que un iframe mostrando la página real Twig-rendered tiene fidelidad pixel-perfect. Pero el iframe introduce limitaciones severas que la auditoría no menciona:
•	No hay edición inline: el iframe es un documento separado. No puedes hacer click en un texto dentro del iframe y editarlo directamente. Toda interacción requiere postMessage, que es asíncrono y limitado.
•	No hay drag-and-drop cross-document: arrastrar un bloque desde el sidebar (documento padre) y soltarlo dentro del iframe (documento hijo) es técnicamente complejo y frágil. HTML5 Drag API tiene problemas conocidos con iframes.
•	No hay overlays visuales naturales: las drop zones, bordes de selección, handles de resize y tooltips que un editor visual necesita no se pueden superponer naturalmente al contenido del iframe desde el documento padre.
•	No hay inspección de componentes: no puedes hacer click en un elemento dentro del iframe y que el panel lateral muestre sus propiedades automáticamente (como hace Webflow, Framer, o GrapesJS).

En resumen: el iframe es excelente como herramienta de PREVIEW, pero es una barrera para la EDICIÓN. GrapesJS resuelve esto usando su propio canvas (también un iframe interno) pero con control total del DOM, lo que permite edición inline, drag-and-drop, y overlays.
 
3. El Costo Real de Cerrar los Gaps
La auditoría sugiere que los gaps son "subsanables sin necesidad de migrar a GrapesJS". Pero ¿cuánto cuesta realmente cerrarlos manteniendo el enfoque custom?
Gap	Esfuerzo Custom	Lo Que Implica	GrapesJS Ya Lo Tiene
Undo/Redo	25-35h	Implementar state stack propio, snapshots JSON de todo el estado de la página, handlers para cada tipo de acción, límite de memoria	✅ Built-in (ilimitado)
Edición inline texto	30-40h	ContentEditable dentro del iframe, sincronización bidireccional via postMessage, toolbar flotante, sanitización HTML	✅ Built-in (RTE nativo)
Drag-drop sidebar→canvas	25-35h	HTML5 DnD cross-iframe, ghost elements, drop zone detection, animaciones de inserción	✅ Block Manager nativo
Selección de componentes	15-20h	Click detection en iframe, highlight overlay, panel binding, deselection handlers	✅ Component system
Auto-save + debounce	8-12h	Debounce timer, dirty detection, conflict resolution, indicador visual	✅ Storage Manager
Menú editor	15-20h	Ya especificado en Doc 177, requiere integración con canvas	Requiere custom igual
TOTAL Gaps	118-162h	Reimplementar lo que GrapesJS da gratis	~20h config

La ironía: cerrar los gaps del enfoque custom cuesta 118-162h, que es MAS que las 120-155h de implementar GrapesJS completo. Y el resultado sería una reimplementación inferior de funcionalidad que GrapesJS lleva 8+ años perfeccionando con una comunidad de 22.000+ desarrolladores.
3.1 La Trampa del "Código Propio es Más Mantenible"
La auditoría argumenta que JavaScript vanilla es más mantenible que una dependencia externa. Este argumento tiene tres fallas:
•	Mantenimiento de bugs: cada bug en undo/redo, drag-and-drop, o edición inline es TU bug. Con GrapesJS (MIT, 22k stars, releases regulares), la comunidad lo encuentra y arregla antes que tú.
•	Surface area: 118-162h de código custom añade ~3.000-5.000 líneas de JavaScript que el equipo debe mantener, testear y evolucionar. GrapesJS encapsula ~50.000 líneas probadas.
•	Vendor lock-in inexistente: GrapesJS es MIT. No hay vendor. El código es abierto. Puedes fork si el proyecto muere (no ha muerto en 8 años). Es menos riesgo que depender de tu propio código custom que solo tu equipo conoce.
 
4. ¿Qué Define un Editor "Clase Mundial" en 2026?
Antes de recomendar, definamos objetivamente qué significa "clase mundial" usando los estándares del mercado.
Capacidad	Estándar 2026	EDI Actual	GrapesJS Spec v2
Drag-drop bloques al canvas	Obligatorio (todos lo tienen)	❌ Reordena, no inserta	✅ Block Manager nativo
Edición inline de texto	Obligatorio	❌ Formularios separados	✅ ContentEditable integrado
Undo/Redo	Obligatorio (mínimo 20 pasos)	❌ No existe	✅ Ilimitado built-in
Preview responsive	Obligatorio (3+ breakpoints)	✅ Viewport toggle	✅ Device Manager
Auto-save	Obligatorio	⚠ Manual	✅ Storage Manager
Header/Footer editable	Diferenciador (pocos lo tienen)	⚠ Solo variantes	✅ Componentes con traits
Bloques premium (animaciones)	Diferenciador	✅ 22 bloques	✅ 22 bloques como componentes
Multi-tenant nativo	Diferenciador único	✅ Implementado	✅ Especificado
Design Tokens en editor	Diferenciador	✅ CSS Variables	✅ Canvas styles injection
IA generativa nativa	Diferenciador	✅ AI Field Generator	✅ AI Content Assistant

Score objetivo para "clase mundial": 10/10 en obligatorios + 3/5 en diferenciadores. EDI actual: 2/5 obligatorios + 4/5 diferenciadores = NO clase mundial. Spec v2 GrapesJS: 5/5 obligatorios + 5/5 diferenciadores = clase mundial.
El problema no son los diferenciadores. EDI implementa los diferenciadores bien (multi-tenant, Design Tokens, IA, bloques premium). El problema son los básicos obligatorios que faltan: drag-drop real, edición inline, undo/redo. Sin estos, no importa cuántos bloques premium tengas; la experiencia de edición es de 2015.
 
5. Recomendación
5.1 Veredicto
La implementación actual de EDI es un configurador de páginas competente que funciona. No es un constructor de páginas de clase mundial. Los argumentos de la auditoría sobre "fidelidad 100%" y "sin vendor lock-in" son técnicamente válidos pero no compensan la ausencia de capacidades básicas que todo editor visual del mercado ofrece desde hace años.
Cerrar los gaps manteniendo el enfoque custom costaría más (118-162h) que implementar GrapesJS correctamente (120-155h), con un resultado inferior y mayor carga de mantenimiento a largo plazo.
5.2 Opción Recomendada: Híbrido Inteligente
Dicho esto, no recomiendo descartar todo el trabajo de EDI. Recomiendo un enfoque híbrido que preserve lo mejor de ambos mundos:
Componente	Fuente	Justificación
Motor de edición del body	GrapesJS	Drag-drop, inline editing, undo/redo, auto-save. No reinventar la rueda.
Preview mode	Iframe de EDI	La fidelidad 100% del iframe es valiosa como modo PREVIEW (no como modo edición).
Parciales (header/footer)	Híbrido: traits GrapesJS + hot-swap EDI	Componentes GrapesJS con selectores de variante. Re-renderizado via hot-swap.
Design Tokens	Implementación EDI	CSS Variables inyectadas funciona perfectamente. Mantener.
Bloques Aceternity/Magic UI	Templates EDI	Los templates Twig existentes se adaptan como componentes GrapesJS.
AI Content Assistant	Implementación EDI	ai-content-generator.js funciona. Integrar como plugin GrapesJS.
Selector de plantillas	Implementación EDI	Template Picker con galería visual. Precarga bloques en content zone.
Menú Editor	Doc 177 (Menu Builder)	Modal dedicado con SortableJS para items. No traits GrapesJS.
5.3 Dos Modos de Edición
El editor debe ofrecer dos modos que el tenant puede alternar:
Modo	Motor	Para Quién	Experiencia
Canvas Visual	GrapesJS	Tenants que quieren construir páginas libremente	Drag-drop, inline editing, undo/redo. Comparable a Webflow.
Configurador Rápido	SortableJS + iframe (EDI)	Tenants con baja literacidad digital que prefieren formularios	Seleccionar plantilla, rellenar campos, ver preview. Más guiado.

Esto respeta la realidad del mercado rural de Jaraba: algunos tenants quieren un Webflow, otros quieren un Squarespace. Ofrecemos ambos con un toggle. El modo configurador ya existe (EDI). El modo canvas requiere GrapesJS.
 
6. Roadmap Revisado
6.1 Fase 1: Integrar GrapesJS como Modo Canvas (10 sprints)
Sprint	Sem	Entregables	Horas	Preserva de EDI
1	1-2	GrapesJS core. Storage REST. Canvas controller. Toggle modo canvas/configurador.	20-25h	Storage REST API, rutas
2	3-4	Adaptador: convertir 67 block_template a componentes GrapesJS. Miniaturas en Block Manager.	25-30h	Templates Twig, thumbnails
3	5-6	Header/Footer como componentes GrapesJS fijos. Traits con variantes. Hot-swap al cambiar.	20-25h	Hot-swap postMessage, variantes
4	7-8	Design Tokens en canvas. CSS Variables inyectadas. Responsive preview.	15-20h	Sistema de Design Tokens 100%
5	9-10	Bloques premium (Aceternity/Magic UI) como componentes GrapesJS con traits.	20-25h	Templates existentes
6	11-12	AI Content Assistant como plugin GrapesJS. Menú editor modal.	15-20h	ai-content-generator.js
7	13-14	Renderizado público Zero Region. Sanitización HTML. Cache.	10-15h	Pipeline de rendering
8	15-16	Undo/redo verificado. Auto-save con indicador. Responsive 3 breakpoints.	10-12h	—
9	17-18	Onboarding tour (driver.js). Command Palette (⌘K).	10-12h	—
10	19-20	Testing E2E. Polish UX. Documentación.	10-12h	Cypress base
TOTAL		155-195h | €12.400-15.600		
6.2 Inversión Total Comparada
Escenario	Horas	Costo	Resultado UX	Mantenimiento
A: Mantener solo enfoque EDI	~70h (ya gastadas)	€5.600	6/10 (configurador)	Bajo pero con techo bajo
B: Cerrar gaps en enfoque EDI	70h + 118-162h = 188-232h	€15.000-18.600	7.5/10 (custom limitado)	Alto (código custom sin comunidad)
C: Híbrido inteligente (recomendado)	70h + 155-195h = 225-265h	€18.000-21.200	9.5/10 (clase mundial)	Medio (GrapesJS + custom ligero)
D: GrapesJS puro (descartar EDI)	155-195h	€12.400-15.600	9/10 (pierde fidelidad preview)	Medio

El escenario C (híbrido inteligente) cuesta solo €2.600-5.600 más que el B (cerrar gaps custom), pero produce un resultado cualitativamente superior (9.5 vs 7.5) con menor carga de mantenimiento a largo plazo.
 
7. Respuesta a los Argumentos de la Auditoría
Argumento EDI	Validez	Contraargumento
"Fidelidad 100% del iframe"	Válido para PREVIEW	El iframe es excelente como modo preview. Pero no como modo edición. GrapesJS + botón "Preview en pestaña nueva" (iframe real) ofrece ambos.
"Sin vendor lock-in con GrapesJS"	Argumento débil	GrapesJS es MIT, no SaaS. No hay vendor. El código es tuyo. Puedes fork. 22k stars = no va a desaparecer.
"Código vanilla más mantenible"	Falso en este caso	5.000 líneas custom para reimplementar drag-drop + undo + inline edit son MENOS mantenibles que configurar una librería probada por millones.
"Score 78% ponderado es suficiente"	Depende del objetivo	78% está bien para un MVP. No es "clase mundial". Para "clase mundial" necesitas 95%+ en obligatorios.
"Los gaps son subsanables sin rediseño"	Técnicamente verdadero	Sí, pero a un costo mayor (118-162h) que hacerlo con GrapesJS (120-155h), con resultado inferior.
"El auto-save manual es preferido"	Discutible	En un editor visual, el auto-save con indicador es estándar de la industria. Ofrecer ambos: auto-save + botón guardar manual.
 
8. Conclusión
EDI Google Antigravity entregó un configurador de páginas funcional en la mitad del tiempo estimado. Eso tiene mérito. Pero las specs pedían un constructor de páginas de clase mundial, y en esa dimensión la implementación actual se queda corta en capacidades que el mercado considera obligatorias.
La cuestión no es qué es "suficiente". Un configurador de plantillas es suficiente para muchos tenants. La cuestión es qué DIFERENCIA a la plataforma Jaraba de Wix, Squarespace, o cualquier CMS con templates. Y la respuesta es: un editor visual nativo que combina drag-drop clase mundial + multi-tenant + 5 verticales + IA generativa + Design Tokens. Eso no existe en el mercado. Pero sin el drag-drop clase mundial, solo tienes otro configurador de templates.

Dimensión	Veredicto
¿El enfoque EDI funciona?	SÍ. Es un configurador competente.
¿Es clase mundial?	No. Le faltan 3 de 5 capacidades obligatorias.
¿GrapesJS es la solución correcta?	Sí, como motor del modo Canvas.
¿Hay que descartar el trabajo de EDI?	No. Se preserva ~60% (tokens, templates, preview, AI, hot-swap).
¿Cuál es el camino?	Híbrido: GrapesJS para modo Canvas + enfoque EDI para modo Rápido.


─── Fin del Documento ───

Evaluación Arquitectónica Jaraba Canvas
Sin sesgos. Sin contemplaciones. Solo la mejor solución para el tenant.
