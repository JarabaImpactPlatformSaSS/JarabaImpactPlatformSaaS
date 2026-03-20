# Como JarabaLex transformo un pequeno despacho de abogados
## La historia de Martinez & Asociados

> **Tipo:** Caso de uso narrativo (storytelling de producto)
> **Vertical:** JarabaLex — Inteligencia Legal con IA
> **Publico objetivo:** Abogados individuales y pequenos despachos (1-5 profesionales)
> **Uso:** Landing page, email marketing, redes sociales, material comercial, partnerships con colegios de abogados
> **Basado en:** Funcionalidades reales implementadas en JarabaLex (85-90% produccion)

---

## El despacho antes de JarabaLex

Elena Martinez tiene 38 anos y lleva 12 ejerciendo como abogada en Malaga. Hace cuatro anos abrio su propio despacho, Martinez & Asociados, junto a su companero de facultad, Pablo Romero. Son dos abogados, una administrativa a media jornada y un becario que rota cada seis meses. Se especializan en derecho tributario y mercantil para PYMEs, aunque aceptan casos de otras areas cuando los clientes lo necesitan.

Su dia a dia, hasta hace tres meses, era asi:

**Lunes, 8:30 de la manana.** Elena llega al despacho con un cafe y una lista mental de lo que tiene pendiente. Lo primero: comprobar si ha salido alguna resolucion del TEAC que afecte al recurso que prepara para un cliente. Abre Aranzadi — la suscripcion que comparten Pablo y ella por 320 euros al mes — y empieza a buscar. La interfaz no ha cambiado mucho en los ultimos anos. Escribe las palabras clave, filtra por fecha, repasa los resultados uno a uno. Tarda 45 minutos en confirmar que no hay novedades relevantes. Luego repite el proceso en el BOE para verificar si ha habido modificaciones legislativas que afecten a sus tres casos abiertos de compliance fiscal.

**10:15.** Un cliente llama preguntando por el estado de su recurso de reposicion. Elena busca el expediente en la carpeta del servidor compartido. El archivo de Word con las notas del caso esta en una subcarpeta que Pablo creo con un nombre diferente al que ella esperaba. Tarda diez minutos en encontrarlo. Cuando lo abre, las ultimas notas son de hace dos semanas — no recuerda si hubo una llamada intermedia o no.

**11:00.** Elena tiene que calcular el plazo para presentar un recurso de alzada. Abre la Ley 39/2015, busca el articulo correspondiente, cuenta los dias habiles en el calendario, comprueba si hay festivos en Malaga... Le lleva quince minutos algo que, en teoria, deberia ser automatico.

**14:30.** Pablo le pide que revise un contrato de compraventa que ha redactado desde cero. Elena lo lee, sugiere cambios, lo devuelve. Pablo incorpora los cambios. Van tres versiones del documento y ninguno sabe cual es la definitiva. El contrato se guarda como "contrato_compraventa_v3_definitivo_FINAL(2).docx" en el escritorio de Pablo.

**17:45.** Elena prepara la factura mensual para un cliente. Lo hace en Excel, porque el sistema de facturacion que probaron una vez era demasiado complejo para dos abogados. Copia los importes, calcula el IVA a mano, genera un PDF y lo envia por email.

**19:30.** Se va a casa pensando que ha trabajado once horas y que la mitad del tiempo lo ha dedicado a tareas que no son derecho.

---

## El descubrimiento

Es un jueves por la noche. Elena esta en LinkedIn revisando noticias del sector cuando ve un post de un companero de su promocion en la facultad de Derecho de Malaga. Ha compartido un articulo titulado "La IA que entiende el derecho espanol" y comenta: *"Llevo dos meses usando esto y no vuelvo a Aranzadi. Busco una sentencia del TEAC y me la encuentra en 3 segundos, con resumen y legislacion citada. Y cuesta una cuarta parte."*

Elena hace clic.

La pagina de JarabaLex muestra cuatro planes: Free, Starter (49 euros al mes), Professional (149 euros al mes) y Enterprise (299 euros al mes). Compara mentalmente con los 320 euros que paga por Aranzadi — solo por la base de datos, sin IA, sin gestion de expedientes, sin nada mas. El plan Professional de JarabaLex incluye todo lo que Aranzadi ofrece mas inteligencia artificial, gestion de casos, calendario de plazos, boveda documental, facturacion y plantillas de documentos.

Pero Elena es abogada. No se fia de las promesas. Hace clic en "Empieza gratis" para probar sin compromiso.

---

## Los primeros 14 dias (Trial Professional)

### Dia 1: La primera busqueda

Elena se registra a las 22:00 desde su portatil. El proceso le lleva dos minutos: email, contrasena, vertical "JarabaLex", nombre del despacho. Un asistente de configuracion le pide que seleccione sus areas de practica: derecho tributario, derecho mercantil, derecho administrativo. Marca las tres.

A las 22:15, sin haber leido ningun manual, escribe en la barra de busqueda:

*"Resolucion TEAC sobre deducibilidad de gastos de vehiculo para autonomos 2024"*

En 1,8 segundos aparecen 7 resultados. El primero es exactamente la resolucion que busco la semana pasada en Aranzadi y tardo 45 minutos en encontrar. JarabaLex muestra:

- El numero de resolucion y fecha
- Un resumen generado por IA de 3 lineas con los puntos clave
- La legislacion citada (articulos de la Ley del IRPF, LIS)
- Una etiqueta de estado: "Vigente"
- Un boton para guardarla como favorita

Elena la abre. El texto completo esta ahi, con los "Key Holdings" (ratios decidendi) destacados en un recuadro. Abajo, una seccion de "Legislacion citada" con enlaces directos a los articulos del BOE.

Elena mira la hora. Son las 22:18. Ha encontrado en tres minutos lo que normalmente le lleva casi una hora.

### Dia 2: El copiloto legal

A la manana siguiente, Elena le cuenta a Pablo lo que ha descubierto. El se muestra esceptico: *"Ya probamos herramientas asi y nunca funcionan bien con derecho espanol"*. Elena le ensena el copiloto.

En la esquina inferior derecha hay un icono flotante: el copiloto de JarabaLex. Elena escribe:

*"Necesito jurisprudencia sobre la prescripcion de la obligacion tributaria cuando hay actuaciones inspectoras interrumpidas. Quiero resoluciones del Tribunal Supremo de los ultimos 3 anos."*

El copiloto procesa la consulta. Elena ve una animacion sutil mientras el sistema trabaja: primero clasifica la intencion (busqueda juridica especializada), luego busca en el vector store, valida la coherencia normativa (las 9 capas del LCIS) y finalmente genera una respuesta:

> *"He encontrado 4 sentencias relevantes del Tribunal Supremo (2022-2025) sobre prescripcion tributaria con actuaciones inspectoras interrumpidas. La linea jurisprudencial consolidada establece que...*
>
> *1. STS 1456/2024 de 15 de octubre — Confirma que la interrupcion de actuaciones por mas de 6 meses sin justificacion reanuda el computo de prescripcion...*
>
> *2. STS 892/2023 de 3 de mayo — Matiza que la carga de la prueba sobre la justificacion de la interrupcion recae en la Administracion...*
>
> *Legislacion aplicable: Art. 66 bis LGT, Art. 150.2 LGT.*
>
> *⚖️ Nota: Esta informacion tiene caracter orientativo y no sustituye el asesoramiento juridico profesional. Fuentes: CENDOJ, BOE. Consulte siempre la legislacion vigente y la jurisprudencia actualizada para su caso concreto."*

Pablo lee por encima del hombro de Elena. Se queda callado un momento.

*"Vale. Quiero mi propia cuenta."*

### Dia 4: Los expedientes

Elena crea su primer caso en JarabaLex: el recurso de alzada del cliente que la llamo el lunes. En cinco minutos tiene:

- Nombre del caso, numero de expediente, cliente asignado
- Fecha limite calculada automaticamente (20 dias habiles desde la notificacion, excluyendo festivos de Malaga y de agosto)
- Una alerta que le avisara 5 y 3 dias antes del vencimiento
- Sincronizacion con su Google Calendar (la alerta aparece en su movil)
- Un timeline de actividad donde queda registrado todo lo que hace

Sube el expediente administrativo a la boveda documental. El archivo queda cifrado con AES-256 y solo Elena y Pablo tienen acceso. Cada vez que alguien lo abre o descarga, queda registrado en el log de auditoria.

### Dia 7: Las alertas

Elena configura tres alertas:

1. "Resoluciones del TEAC sobre IVA en operaciones intracomunitarias" — cada vez que se publique una nueva resolucion, recibe un email con resumen
2. "Modificaciones legislativas que afecten a la Ley de Sociedades de Capital" — monitoriza el BOE diariamente
3. "Jurisprudencia del TJUE sobre proteccion de datos" — vigila resoluciones europeas relevantes para sus clientes de compliance

El viernes por la manana, al abrir el email, encuentra el digest semanal de JarabaLex: dos resoluciones nuevas del TEAC sobre IVA intracomunitario, con resumen de 3 lineas cada una. Lee los resumenes en 2 minutos y decide que una de ellas es relevante para un caso abierto. La abre, la guarda como favorita y la vincula al expediente. Todo sin abrir Aranzadi.

### Dia 10: La plantilla que ahorra dos horas

Pablo tiene que redactar una contestacion a una demanda mercantil. En lugar de partir de cero (o buscar un modelo en Word de hace tres anos), abre el modulo de plantillas de JarabaLex. Selecciona "Contestacion a demanda — mercantil", rellena los campos del formulario (juzgado, numero de autos, partes, hechos clave) y genera el borrador.

El documento sale con la estructura formal correcta, los fundamentos de derecho esquematizados y los suplicos redactados. Pablo lo revisa, ajusta los argumentos especificos del caso y lo tiene listo en 40 minutos. Antes le habria llevado dos horas y media.

### Dia 12: La factura sin Excel

Elena genera la factura mensual del cliente de compliance fiscal. Va a "Facturacion", selecciona las entradas de tiempo registradas durante el mes (cada vez que trabajo en el caso, hizo clic en "Iniciar temporizador"), revisa los importes, anade una descripcion y genera la factura. El sistema calcula el IVA, genera un PDF profesional con el logo del despacho y la envia por email directamente desde la plataforma.

Pablo, que la observa, dice: *"¿Y el Excel?"*

*"¿Que Excel?"*

### Dia 14: La decision

Es viernes. El trial de 14 dias termina manana. Elena y Pablo se sientan a evaluar:

**Lo que JarabaLex les ha ahorrado esta quincena:**
- ~15 horas de busqueda juridica (45 min/dia → 5 min/dia)
- ~6 horas de redaccion (plantillas + copiloto para borradores)
- ~3 horas de gestion administrativa (facturas, plazos, expedientes)
- **Total: ~24 horas en 14 dias = casi 2 horas al dia**

**El coste:**
- Aranzadi: 320 EUR/mes (solo base de datos, sin IA ni gestion)
- JarabaLex Professional: 149 EUR/mes (todo incluido: busqueda IA + copiloto + casos + calendario + boveda + facturacion + plantillas)
- **Ahorro: 171 EUR/mes + 2 horas diarias de productividad**

Elena contrata el plan Professional. Cancela Aranzadi esa misma tarde.

---

## Tres meses despues

Martinez & Asociados ha cambiado. No de manera dramatica — siguen siendo dos abogados, una administrativa y un becario. Pero la dinamica diaria es otra.

**Elena** empieza cada manana revisando el dashboard de JarabaLex. Ve si hay alertas nuevas, comprueba los plazos de la semana en el calendario sincronizado con su movil y revisa el estado de los casos abiertos. Todo en un solo lugar. Ya no abre cinco pestanas diferentes.

**Pablo** se ha convertido en el fan numero uno del copiloto. Lo usa para todo: buscar jurisprudencia antes de reuniones con clientes, generar borradores de contratos, preparar argumentarios para vistas. Dice que es como tener un pasante brillante que nunca se cansa y que conoce toda la jurisprudencia del Tribunal Supremo.

**La administrativa** ya no persigue a Elena para que le pase las horas trabajadas. El sistema de control horario registra automaticamente el tiempo por caso, y las facturas se generan en dos clics.

**El becario** usa el plan Free para sus practicas — tiene acceso limitado a busquedas y puede ver los casos que Elena le asigna. Cuando termine las practicas, probablemente se llevara JarabaLex a su proximo destino profesional.

**Los clientes** notan la diferencia sin saber por que. Las respuestas llegan mas rapido. Los informes incluyen citas jurisprudenciales precisas con resumen de cada sentencia. Las facturas son claras y profesionales. Un cliente le dijo a Elena: *"No se que habeis cambiado, pero se nota."*

### Los numeros despues de 3 meses

| Metrica | Antes | Ahora | Cambio |
|---------|-------|-------|--------|
| Horas semanales en busqueda juridica | 12-15h | 3-4h | -75% |
| Tiempo medio para localizar una resolucion | 25-45 min | 1-3 min | -95% |
| Tiempo redaccion de contestacion a demanda | 2,5h | 45 min | -70% |
| Plazos vencidos por descuido (trimestral) | 1-2 | 0 | -100% |
| Tiempo generacion factura mensual | 45 min | 5 min | -89% |
| Coste herramientas legales | 320 EUR/mes | 149 EUR/mes | -53% |
| Capacidad de casos simultaneos | 15-18 | 22-25 | +40% |
| Ingresos mensuales del despacho | ~8.500 EUR | ~11.200 EUR | +32% |

El incremento de ingresos no viene de cobrar mas, sino de hacer mas. Con 2 horas diarias recuperadas, Elena y Pablo pueden atender 6-7 casos mas al mes sin sacrificar calidad. Y las alertas automaticas les permiten ofrecer un servicio proactivo que sus clientes valoran: *"Te llamaba porque he visto que ha salido una resolucion nueva del TEAC que afecta a tu situacion..."*

---

## Lo que Elena le dice a sus colegas

Tres meses despues, es Elena la que publica en LinkedIn. Su post dice:

> *"Hace tres meses descubri JarabaLex y llevo semanas queriendo compartirlo. Somos un despacho de 2 abogados en Malaga, especializados en tributario y mercantil.*
>
> *Antes pagabamos 320€/mes por Aranzadi y dedicabamos media manana a buscar jurisprudencia. Ahora pagamos 149€/mes por JarabaLex y encontramos cualquier resolucion en segundos, con resumen de IA, legislacion citada y estado de vigencia.*
>
> *Pero lo mejor no es la busqueda. Es todo lo demas: el copiloto que entiende derecho espanol, el calendario que calcula plazos en dias habiles, la boveda documental cifrada, las plantillas que generan borradores en minutos, la facturacion integrada...*
>
> *Si eres abogado y sigues usando herramientas del siglo pasado, hazte un favor: prueba los 14 dias gratis. Yo no he vuelto a abrir Aranzadi."*

El post tiene 47 comentarios y 312 reacciones. Elena no sabe que acaba de generar mas conversion que cualquier campana de publicidad.

---

## Epilogo: Un ano despues

Martinez & Asociados ha contratado un tercer abogado. Los ingresos crecieron un 45% sin aumentar los costes fijos significativamente. Elena evalua subir al plan Enterprise (299 EUR/mes) para tener acceso a la API, que quiere integrar con su sistema de CRM, y para que los tres abogados tengan cuentas independientes con SLA garantizado.

Pablo, por su parte, ha empezado a dar charlas en el Colegio de Abogados de Malaga sobre como la IA puede ayudar a los pequenos despachos. En cada charla, menciona JarabaLex. No porque le paguen, sino porque cree genuinamente que ha cambiado su forma de trabajar.

La administrativa ha dejado de quejarse del Excel. El becario, que termino las practicas, abrio su cuenta de JarabaLex Free desde su nueva firma. Y el cliente que dijo "no se que habeis cambiado, pero se nota" les ha referido a tres empresas mas.

Porque al final, la mejor tecnologia no es la que se nota. Es la que libera tiempo para hacer lo que realmente importa: ejercer el derecho.

---

## Glosario de siglas

| Sigla | Significado |
|-------|------------|
| **AES-256** | Advanced Encryption Standard con clave de 256 bits — estandar de cifrado para documentos |
| **BOE** | Boletin Oficial del Estado — publicacion oficial de legislacion en Espana |
| **CENDOJ** | Centro de Documentacion Judicial del CGPJ — base de jurisprudencia espanola |
| **DGT** | Direccion General de Tributos — emite consultas tributarias vinculantes |
| **EDPB** | European Data Protection Board — organo europeo de proteccion de datos |
| **IA** | Inteligencia Artificial |
| **IVA** | Impuesto sobre el Valor Anadido — 21% en Espana peninsular |
| **LCIS** | Legal Coherence Intelligence System — sistema de 9 capas de validacion juridica de JarabaLex |
| **LexNET** | Sistema telematico del CGPJ para comunicaciones judiciales electronicas |
| **LGT** | Ley General Tributaria (Ley 58/2003) |
| **LIS** | Ley del Impuesto sobre Sociedades |
| **PYME** | Pequena y Mediana Empresa |
| **RAG** | Retrieval-Augmented Generation — tecnica de IA que combina busqueda con generacion |
| **SLA** | Service Level Agreement — acuerdo de nivel de servicio garantizado |
| **STS** | Sentencia del Tribunal Supremo |
| **TEAC** | Tribunal Economico-Administrativo Central — resuelve recursos en materia tributaria |
| **TEDH** | Tribunal Europeo de Derechos Humanos (Estrasburgo) |
| **TJUE** | Tribunal de Justicia de la Union Europea (Luxemburgo) |

---

*Historia basada en funcionalidades reales implementadas en JarabaLex (85-90% produccion).*
*Todos los datos de ahorro de tiempo y costes son estimaciones basadas en benchmarks del sector legal espanol.*
*Los personajes son ficticios. Cualquier parecido con despachos reales es pura coincidencia... o inspiracion.*
