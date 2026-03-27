MÉTODO JARABA™
**Guía Didáctica Genérica**

*Especificación de transformación para Claude Code*

Cómo convertir la Guía Didáctica del Programa Andalucía +ei
en la Guía Didáctica Genérica del Método Jaraba
(reutilizable por cualquier franquiciado en cualquier contexto)

Plataforma de Ecosistemas Digitales S.L.
Marzo 2026 — Documento técnico — Claude Code


# 1. Objetivo de la Transformación


La Guía Didáctica del Formador del Programa Andalucía +ei (1.309 párrafos, 51 KB) es el activo formativo más valioso del Método Jaraba. Contiene el desarrollo completo sesión por sesión de las 100 horas regladas del programa: guión del formador, actividades, temporización, entregables, materiales y rúbricas.
Sin embargo, está redactada específicamente para el contexto del Programa PIL de Andalucía (Servicio Andaluz de Empleo, FSE+ Andalucía, colectivos vulnerables inscritos en el SAE, restricciones normativas de la Orden de 29/09/2023). Para que cualquier franquiciado pueda usarla en su contexto, necesitamos una versión genérica que mantenga el 95% del contenido pero parametrice el 5% específico.


| Principio de transformación NO se reescribe la guía desde cero. Se transforma el documento existente con operaciones de buscar-y-reemplazar + edición quirurgica de los párrafos específicos del contexto PIL. El resultado debe ser funcional sin necesidad de que el franquiciado edite nada: los parámetros variables se marcan con placeholders que el franquiciado rellena antes de impartir. |
| --- |


# 2. Operaciones de Transformación


## 2.1. Reemplazos globales (buscar y reemplazar en todo el documento)


| Buscar (exacto) | Reemplazar por | Ocurrencias estimadas |
| --- | --- | --- |
| Programa Andalucía +ei | Programa {{NOMBRE_PROGRAMA}} | ~50 |
| Andalucía +ei | {{NOMBRE_PROGRAMA}} | ~30 |
| 2ª Edición | {{EDICION}} | ~10 |
| Convocatoria 2025 | {{CONVOCATORIA}} | ~5 |
| Servicio Andaluz de Empleo | Servicio Público de Empleo {{SPE}} | ~8 |
| SAE | {{SPE}} | ~15 |
| inscrito en el SAE | inscrito en el {{SPE}} | ~5 |
| colectivos vulnerables inscritos SAE | participantes del programa | ~10 |
| Sevilla y Málaga | {{PROVINCIAS}} | ~8 |
| 623 17 43 04 | {{TELEFONO}} | ~5 |
| jose@plataformadeecosistemas.com | {{EMAIL_FORMADOR}} | ~3 |
| José Jaraba Muñoz | {{NOMBRE_FORMADOR}} | ~5 |
| 45 proyectos integrales | {{NUM_PARTICIPANTES}} participantes | ~5 |
| 45 plazas | {{NUM_PARTICIPANTES}} plazas | ~3 |
| 528€ | {{INCENTIVO}} | ~5 |
| incentivo de 528€ | incentivo de {{INCENTIVO}} | ~3 |
| FSE+ Andalucía 2021-2027 | {{FONDO}} | ~5 |
| Junta de Andalucía | {{ADMINISTRACION}} | ~5 |
| Orden de 29/09/2023 | {{NORMATIVA_BASE}} | ~3 |
| Resolución de Concesión 19/12/2025 | {{RESOLUCION}} | ~2 |
| 29 diciembre 2025 | {{FECHA_INICIO}} | ~2 |
| 18 meses | {{DURACION_PROGRAMA}} | ~3 |


## 2.2. Párrafos a eliminar

Estos párrafos son específicos del contexto PIL/FSE+ y no tienen equivalente genérico. Se eliminan y se sustituyen por una nota parametrizable.

| Ubicación | Contenido a eliminar | Sustituir por |
| --- | --- | --- |
| §1.1 (restricciones) | Tabla completa de restricciones normativas (Orden 29/09/2023) | {{INSERTAR_RESTRICCIONES_NORMATIVAS}} — Nota: cada programa tiene sus propias restricciones. El franquiciado debe insertar aquí las suyas. |
| §1.1 (cambio crítico) | Recuadro sobre modificación de julio 2025 del límite online | Eliminar. Solo relevante para PIL Andalucía. |
| §1.2 (horas) | Tabla de distribución de horas con valores fijos (10h+50h+40h) | Misma tabla pero con placeholders: {{HORAS_ORIENTACION}} + {{HORAS_FORMACION}} + {{HORAS_ACOMPANAMIENTO}} |
| OI-2.2 (sesiones individuales) | Referencia a firma de Compromiso de Participación FSE+ | Sustituir por: «Firma del compromiso de participación del programa {{NOMBRE_PROGRAMA}}» |
| Cada § de sesión | Referencia «☆ En Jaraba Impact Platform: ...» | Mantener TAL CUAL — la plataforma es común a todos los franquiciados |
| Apéndice de entregables | Tabla de 29 entregables | Mantener TAL CUAL — los entregables son universales |


## 2.3. Secciones a añadir

La versión genérica añade una sección nueva al principio del documento:


| Nueva sección: «Configuración del programa» (insertar antes de §1) # Configuración del programa  Antes de utilizar esta guía, el formador debe completar los parámetros de su programa específico:  | Parámetro | Valor | |---|---| | Nombre del programa | {{NOMBRE_PROGRAMA}} | | Edición | {{EDICION}} | | Número de participantes | {{NUM_PARTICIPANTES}} | | Provincias/territorio | {{PROVINCIAS}} | | Servicio Público de Empleo | {{SPE}} | | Administración financiadora | {{ADMINISTRACION}} | | Fondo de financiación | {{FONDO}} | | Normativa base | {{NORMATIVA_BASE}} | | Fecha de inicio | {{FECHA_INICIO}} | | Duración total | {{DURACION_PROGRAMA}} | | Horas de orientación | {{HORAS_ORIENTACION}} | | Horas de formación | {{HORAS_FORMACION}} | | Horas de acompañamiento | {{HORAS_ACOMPANAMIENTO}} | | Incentivo por participante | {{INCENTIVO}} | | Teléfono de contacto | {{TELEFONO}} | | Email del formador | {{EMAIL_FORMADOR}} | | Nombre del formador | {{NOMBRE_FORMADOR}} |  Una vez completada esta tabla, busca y reemplaza cada {{PARAMETRO}} en todo el documento por su valor real. El resto del contenido es universal y no requiere edición. |
| --- |


# 3. Portada y Metadatos


## 3.1. Portada genérica


| Portada Título: GUÍA DIDÁCTICA DEL FORMADOR Subtítulo: Método Jaraba™ Línea 1: Desarrollo completo sesión por sesión Línea 2: Orientación + Formación (Módulos 0-5) + Acompañamiento Línea 3: Guión del formador, actividades, temporización,           entregables, materiales y rúbricas  Pie: Programa {{NOMBRE_PROGRAMA}} — {{EDICION}}      Implementado con Jaraba Impact Platform      plataformadeecosistemas.com |
| --- |


## 3.2. Header y footer


| Header/Footer Header: Método Jaraba™ — Guía Didáctica del Formador Footer: {{NOMBRE_PROGRAMA}} — Pág. X (eliminar cualquier referencia a PED S.L. en el footer,  el franquiciado usa su propia entidad) |
| --- |


# 4. Instrucciones para Claude Code


| Flujo de ejecución 1. Leer el archivo fuente: guia-didactica-formador-andalucia-ei.docx 2. Descomprimir el DOCX (es un ZIP con XML dentro) 3. Ejecutar las operaciones de reemplazo de la §2.1 sobre document.xml 4. Ejecutar las eliminaciones de la §2.2 5. Insertar la sección de configuración de la §2.3 al principio 6. Actualizar portada y metadatos según §3 7. Recomprimir como DOCX 8. Validar con validate.py 9. El resultado es: guia-didactica-formador-metodo-jaraba-generica.docx |
| --- |


| Script bash para Claude Code # 1. Copiar fuente al directorio de trabajo cp /mnt/project/guia-didactica-formador-andalucia-ei.docx /home/claude/  # 2. Descomprimir python /mnt/skills/public/docx/scripts/office/unpack.py \   /home/claude/guia-didactica-formador-andalucia-ei.docx \   /home/claude/guia-generica/  # 3. Ejecutar reemplazos en document.xml # (usar sed o python con la tabla de la sección 2.1) python3 << 'EOF' import re with open('/home/claude/guia-generica/word/document.xml','r') as f:     xml = f.read()  replacements = {     'Programa Andalucía +ei': 'Programa {{NOMBRE_PROGRAMA}}',     'Andalucía +ei': '{{NOMBRE_PROGRAMA}}',     # ... (toda la tabla de la §2.1) }  for old, new in replacements.items():     xml = xml.replace(old, new)  with open('/home/claude/guia-generica/word/document.xml','w') as f:     f.write(xml) EOF  # 4. Reempaquetar python /mnt/skills/public/docx/scripts/office/repack.py \   /home/claude/guia-generica/ \   /home/claude/guia-didactica-formador-metodo-jaraba-generica.docx  # 5. Validar python /mnt/skills/public/docx/scripts/office/validate.py \   /home/claude/guia-didactica-formador-metodo-jaraba-generica.docx |
| --- |


| Advertencia crítica sobre reemplazos en XML Los reemplazos deben hacerse sobre el XML raw del DOCX, no sobre texto plano. En el XML de Word, una cadena como 'Andalucía +ei' puede estar dividida en múltiples <w:r> (runs) si tiene formato mixto. Claude Code debe: (1) primero intentar el reemplazo directo, (2) si no encuentra la cadena completa, buscar los fragmentos en runs adyacentes y consolidar antes de reemplazar. Alternativa más segura: convertir el DOCX a texto plano con pandoc, verificar los reemplazos, y luego aplicarlos al XML con un parser que respete la estructura de runs. |
| --- |


# 5. Verificación


Tras la transformación, Claude Code debe verificar:
- Búsqueda de 'Andalucía' en todo el documento: debe devolver 0 resultados (todas las referencias han sido parametrizadas).
- Búsqueda de 'SAE' en todo el documento: debe devolver 0 resultados.
- Búsqueda de 'FSE+' en todo el documento: debe devolver 0 resultados.
- Búsqueda de '528' en todo el documento: debe devolver 0 resultados.
- Búsqueda de '45 proyectos' o '45 plazas': debe devolver 0 resultados.
- Búsqueda de 'José Jaraba' en el cuerpo (no en la portada del método): debe devolver 0 resultados.
- Búsqueda de '{{': debe devolver exactamente N resultados (los placeholders definidos). Verificar que no hay placeholders huérfanos.
- El documento se abre correctamente en Word/LibreOffice sin errores.
- La estructura de secciones (headings) se mantiene intacta.
- Las tablas de temporización de cada sesión se mantienen intactas.


| Métrica | Valor esperado |
| --- | --- |
| Tamaño del documento | ±5% del original (~49-53 KB) |
| Número de párrafos | ±2% del original (~1.280-1.340) |
| Placeholders {{...}} | 17 tipos × ~10 ocurrencias cada uno = ~170 |
| Referencias a Andalucía/SAE/FSE+ | 0 |
| Tiempo de transformación estimado | 8-12 horas |


*Fin de la Spec de Transformación*
Método Jaraba™ — Marzo 2026
