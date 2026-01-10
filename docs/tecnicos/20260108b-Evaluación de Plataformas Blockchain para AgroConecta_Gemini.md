Evaluación Técnica Estratégica de Arquitecturas de Anclaje en Blockchain (Nivel 4) para el Ecosistema AgroConecta
Resumen Ejecutivo
El presente informe técnico despliega una evaluación exhaustiva y multidimensional sobre las opciones de arquitectura para la implementación de un sistema de trazabilidad de Nivel 4 (inmutable y descentralizado) dentro de la plataforma AgroConecta. Este análisis se enmarca rigurosamente dentro de los imperativos estratégicos definidos por el modelo de negocio del Ecosistema Integrado de Pepe Jaraba 1, las restricciones técnicas de un entorno Drupal 11 Headless 1, y, de manera crítica, los requisitos regulatorios de la subvención RETECH (Redes de Emprendimiento Digital) de la Junta de Andalucía.1
El objetivo central es identificar la solución tecnológica que no solo garantice la integridad criptográfica de los datos de trazabilidad ("Lotes de Producción") 1, sino que también maximice la elegibilidad para la financiación pública al constituirse como una "Solución Propia" con un valor añadido demostrable. La evaluación contrasta cuatro paradigmas: la infraestructura institucional europea (EBSI), los protocolos abiertos sobre Bitcoin (OpenTimestamps), las soluciones empresariales cerradas (IBM Food Trust) y las alternativas SaaS/API de nicho (VeChain, OriginTrail, WordProof).
La conclusión principal del análisis determina que, para el horizonte temporal 2025-2026 y considerando el perfil de la PYME agroalimentaria andaluza ("Marta Gómez") 1, la estrategia óptima es un modelo híbrido evolutivo. Se recomienda el desarrollo inmediato de un módulo de integración propietario basado en OpenTimestamps (OTS) para garantizar la soberanía tecnológica, el coste cero operativo y el cumplimiento de la filosofía "Sin Humo" 1, arquitecturado bajo patrones de diseño (Adapter Pattern) que permitan la incorporación fluida de EBSI como capa de validación institucional una vez que el acceso al sector privado se estabilice en 2026.2 Esta aproximación permite justificar la subvención RETECH mediante la creación de propiedad intelectual real (el código del conector y la lógica de negocio) en lugar de la mera reventa de licencias de terceros.
1. Marco Estratégico y Definición de Requisitos Técnicos
1.1 El Contexto del Ecosistema AgroConecta y la Oportunidad RETECH
La plataforma AgroConecta no se concibe como un simple portal de comercio electrónico, sino como un ecosistema integral de transformación digital para el sector agroalimentario andaluz. Su misión es cerrar la brecha existente entre la excelencia del producto físico (aceites, vinos, ibéricos con D.O.P.) y la precariedad de la presencia digital de los productores.1 El avatar del cliente objetivo, "Marta Gómez", gerente de una almazara familiar, requiere soluciones que eleven el valor de su marca sin añadir fricción operativa o costes recurrentes inasumibles.1
En este contexto, la Trazabilidad de Nivel 4 se define no solo como un registro logístico, sino como un activo de marketing premium. Se trata de la capacidad de demostrar matemáticamente, sin depender de la "buena fe" de una base de datos centralizada (Nivel 3), que un lote de producción específico existía en un estado concreto en una fecha determinada. Esto se traduce en la funcionalidad "Phy-gital", donde un código QR en la botella física conecta al consumidor con una historia digital inmutable.1
El catalizador financiero para este desarrollo es la Línea 2 del programa RETECH, gestionada por la Junta de Andalucía. Las bases reguladoras de esta subvención establecen un filtro determinante: la financiación se reserva para la "implantación de soluciones digitales desarrolladas por nuevas empresas digitales", excluyendo explícitamente la venta o implantación de software de código abierto o comercial que no presente "modificaciones de valor suficientes y demostrables".1
Este requisito transforma la decisión tecnológica en una decisión de negocio. Si AgroConecta opta por integrar una solución "llave en mano" como IBM Food Trust mediante una simple clave API, corre el riesgo severo de que la administración considere el proyecto como una "prestación de servicios de terceros", inelegible para los 40.000 € de ayuda máxima. Por el contrario, si AgroConecta desarrolla un módulo propio en Drupal que orqueste la criptografía y la comunicación con una red blockchain pública, está creando Propiedad Intelectual (PI), justificando así la intensidad de la ayuda solicitada.1
1.2 Arquitectura Tecnológica Base: Drupal 11 y la Filosofía "Sin Humo"
La plataforma opera sobre un stack tecnológico moderno y específico, diseñado para la escalabilidad y la eficiencia:
●	Núcleo CMF: Drupal 11.3.1 actuando como gestor de contenidos estructurados y motor de experiencias.1
●	Comercio Desacoplado: Integración con Ecwid para la lógica transaccional, liberando a Drupal de la carga de seguridad PCI y gestión de pagos.1
●	Automatización: Uso intensivo del módulo ECA (Event-Condition-Action) para orquestar flujos de trabajo sin código.1
●	Filosofía "Sin Humo": Un mandato de diseño que rechaza la complejidad innecesaria, los tokens especulativos y las interfaces sobrecargadas.1 La solución de blockchain debe ser invisible para el productor ("Marta") y transparente para el consumidor.
El módulo agroconecta_core ya gestiona la entidad Lote de Producción.1 El desafío técnico es añadir una capa de persistencia inmutable a esta entidad sin romper la arquitectura de servicios ni introducir dependencias frágiles.
2. Evaluación Profunda de la Opción A: European Blockchain Services Infrastructure (EBSI)
2.1 Naturaleza Institucional y Alineación Estratégica
La Infraestructura Europea de Servicios de Blockchain (EBSI) representa el esfuerzo concertado de la Comisión Europea y 29 países (los 27 miembros de la UE más Noruega y Liechtenstein) para crear una red blockchain permisionada y transfronteriza para servicios públicos.3 Desde una perspectiva de reputación y alineación institucional, EBSI es la opción "dorada". Integrar AgroConecta con EBSI permitiría colocar un sello de "Verificado por Europa" en cada botella de aceite andaluz, un argumento de venta devastadoramente potente para la exportación.
La infraestructura se basa en una red de nodos distribuidos operados por entidades gubernamentales y socios autorizados, garantizando que el consenso y la validación de las transacciones se realicen bajo el paraguas de la legalidad europea.5 Esto ofrece una garantía de cumplimiento normativo (GDPR, eIDAS 2.0) que ninguna red pública permisionada puede igualar por defecto.
2.2 Análisis Técnico de la API de Sellado de Tiempo (Timestamp API v4)
Para el caso de uso de trazabilidad documental y certificación de lotes, el servicio core relevante es la Timestamp API.
●	Funcionalidad: Permite a las aplicaciones interactuar con el Contrato Inteligente de Sellado de Tiempo (TimeStamp Smart Contract) para registrar hashes de documentos o datos. Soporta el sellado de registros individuales, versiones de documentos y, crucialmente, el enlace de sellos de tiempo para crear pistas de auditoría.7
●	Evolución y Estabilidad: La API se encuentra actualmente en la versión 4. Es importante notar que el equipo de desarrollo de EBSI introdujo cambios disruptivos (breaking changes) en junio de 2025 para mejorar el rendimiento.9 Esto indica una plataforma viva, en evolución activa, pero que requiere un mantenimiento técnico continuo por parte del integrador (AgroConecta) para adaptarse a las nuevas especificaciones.
●	Mecanismo de Autenticación: El acceso no es abierto. Requiere un flujo de autorización robusto, típicamente involucrando la emisión de Verifiable Credentials (VCs) y el uso de tokens de acceso de corta duración obtenidos a través de la Authorisation API.10 Esto implica que el módulo agroconecta_core debe implementar capacidades de gestión de identidad descentralizada (DID) para autenticarse ante la red.
2.3 Barreras de Acceso para el Sector Privado (El Muro de 2026)
A pesar de su robustez técnica, EBSI presenta un obstáculo significativo para un proyecto que necesita ejecución inmediata en 2025: su modelo de acceso.
●	Enfoque Público: EBSI fue diseñada primariamente para administraciones públicas. La apertura a empresas privadas y organizaciones como "utilities" está en la hoja de ruta, pero la fase de "producción completa" para el sector privado se sitúa en el horizonte de 2026.2
●	Programa Early Adopters: Históricamente, el camino para las PYMES tecnológicas ha sido el programa Early Adopters. Sin embargo, la información más reciente indica que este programa se encuentra en pausa o en transición, con nuevas expresiones de interés procesándose no antes del cuarto trimestre (Q4) de 2025.11
●	Implicación para RETECH: Si AgroConecta basa su propuesta técnica exclusivamente en EBSI, corre el riesgo de no poder demostrar la implementación operativa dentro de los plazos de justificación de la subvención si el acceso a la red principal (Mainnet) se retrasa o se restringe burocráticamente.
2.4 Veredicto sobre EBSI
EBSI es el objetivo estratégico a medio plazo. Ofrece la máxima legitimidad institucional, pero su inmadurez en cuanto a canales de acceso abiertos para PYMES privadas en el corto plazo la convierte en una dependencia de alto riesgo para el lanzamiento inicial ("MVP"). La estrategia inteligente es preparar la arquitectura para EBSI (EBSI-Ready) pero no depender de ella para el despliegue del Día 1.
3. Evaluación Profunda de la Opción B: OpenTimestamps (OTS)
3.1 Arquitectura del Protocolo y Soberanía Tecnológica
OpenTimestamps ofrece un paradigma radicalmente opuesto al de EBSI: confianza basada en matemáticas y descentralización pura, en lugar de confianza institucional. OTS es un protocolo que utiliza la blockchain de Bitcoin como una capa de notariado digital, pero lo hace de una manera altamente escalable y eficiente en costes mediante el uso de Árboles de Merkle.12
El funcionamiento técnico es elegante y se alinea con la eficiencia de recursos:
1.	Agregación: Un servidor de calendario (público y gratuito) recibe miles de hashes de diferentes usuarios.
2.	Compromiso: Estos hashes se combinan criptográficamente en un Árbol de Merkle.
3.	Anclaje: Solo la raíz del árbol (el Merkle Root) se escribe en la blockchain de Bitcoin mediante una transacción OP_RETURN.13
4.	Prueba: El usuario recibe un archivo .ots que contiene la ruta matemática desde su dato original hasta la raíz anclada en Bitcoin.
Esta arquitectura permite realizar sellados de tiempo ilimitados sin coste de transacción directo para el usuario (ni para AgroConecta ni para la PYME), ya que las tarifas de la red Bitcoin son pagadas por los servidores de calendario (financiados por donaciones o empresas patrocinadoras) y amortizadas entre millones de registros.12
3.2 Viabilidad de Integración en Drupal 11
La integración de OTS en un entorno PHP/Drupal presenta desafíos técnicos específicos que, paradójicamente, son beneficiosos para la solicitud de la subvención RETECH, ya que requieren desarrollo de software real.
●	Lenguajes: Las librerías de cliente oficiales de OTS están escritas en Python, Java, Rust y JavaScript.12 No existe una librería oficial mantenida en PHP.
●	Estrategia de Desarrollo (Solución Propia): Para integrar esto en AgroConecta, el equipo técnico deberá desarrollar un "puente". Esto podría realizarse mediante:
○	Un componente en Drupal (Symfony Process) que ejecute el cliente de Python (ots-cli) instalado en el servidor.15
○	Un microservicio en Node.js o Python que exponga una API interna para que Drupal le envíe los hashes de los lotes.
Este trabajo de integración constituye una prueba irrefutable de desarrollo técnico propio, diferenciándose claramente de la simple instalación de un plugin.
3.3 Experiencia de Usuario y Filosofía "Sin Humo"
OTS encaja perfectamente con la filosofía "Sin Humo".
●	Transparencia: No hay tokens que comprar, no hay "gas" que gestionar, no hay volatilidad de precios.
●	Permanencia: La prueba .ots es un archivo autónomo. Incluso si AgroConecta desaparece como empresa, la prueba sigue siendo válida y verificable contra la blockchain de Bitcoin por cualquier tercero, para siempre.16 Esto ofrece una garantía de continuidad al productor agroalimentario que una empresa SaaS privada no puede igualar.
●	Verificación en el Navegador: Existen librerías JavaScript que permiten verificar el archivo .ots directamente en el navegador del consumidor al escanear el QR, sin necesidad de confiar en el servidor de AgroConecta.12
3.4 Veredicto sobre OpenTimestamps
OTS es la opción táctica superior. Ofrece seguridad de nivel militar (Bitcoin), coste operativo cero, independencia de proveedores y requiere un esfuerzo de desarrollo que justifica la subvención. Su única debilidad es la falta de "marca institucional" (no tiene el sello de la UE), pero esto se compensa con su inmutabilidad matemática.
4. Evaluación Profunda de la Opción C: IBM Food Trust y Soluciones Enterprise SaaS
4.1 El Modelo de "Jardín Vallado" (Walled Garden)
IBM Food Trust es la referencia corporativa en trazabilidad alimentaria, construida sobre Hyperledger Fabric.17 Es utilizada por gigantes como Carrefour y Walmart para trazar cadenas de suministro complejas globales.
Técnicamente, es una blockchain permisionada. Los datos no son públicos; el acceso y la validación están controlados por los miembros del consorcio. El modelo de datos se basa estrictamente en los estándares GS1 (EPCIS), lo cual es positivo para la interoperabilidad industrial pero añade una capa de complejidad burocrática enorme para una pequeña almazara.18
4.2 Barreras Económicas y de Dependencia
El modelo de precios de IBM Food Trust está diseñado para la escala empresarial.
●	Costes: Para pequeñas empresas (menos de 50M$ de ingresos), la cuota base puede empezar en torno a los 100$/mes, pero los costes de integración, certificación y soporte pueden elevar la factura inicial a miles de dólares.17
●	Dependencia: Es un SaaS puro. Si la PYME o AgroConecta dejan de pagar la mensualidad, el acceso a los datos históricos y a la capacidad de verificación se pierde o se complica. No existe la propiedad soberana de la prueba de trazabilidad como en OTS.
4.3 Incompatibilidad con los Objetivos RETECH
Desde la perspectiva de la subvención RETECH, elegir IBM Food Trust es un error estratégico crítico.
●	Falta de Desarrollo Propio: Conectar Drupal a la API de IBM es una tarea de integración, no de desarrollo de producto. La administración podría interpretar esto como una reventa de servicios de IBM, lo cual violaría las bases que exigen una "Solución Propia".1
●	Desalineación con el Avatar: "Marta Gómez" necesita sencillez. IBM Food Trust requiere una gestión de datos y una adhesión a estándares GS1 que exceden las capacidades operativas de una micro-pyme sin departamento de TI.
5. Evaluación Profunda de la Opción D: Alternativas "Tipo Ecwid" (API/SaaS de Nicho)
5.1 VeChain (ToolChain)
VeChain se posiciona como una blockchain pública orientada a empresas, con su solución ToolChain que actúa como un BaaS (Blockchain-as-a-Service).19
●	Pros: Facilita la integración de IoT (chips NFC/RFID) que podría ser un valor añadido futuro para productos de muy alto valor.
●	Contras: Introduce el riesgo de la "tokenomics". Aunque ToolChain abstrae el pago de gas (token VTHO) mediante pagos en dinero fiduciario, la infraestructura subyacente depende de la salud económica de un criptoactivo volátil.20 Para un proyecto subvencionado por fondos públicos, vincular la infraestructura crítica a un activo especulativo puede generar reticencias en la auditoría. Además, al igual que IBM, se acerca peligrosamente al modelo de reventa de servicios.
5.2 OriginTrail (Decentralized Knowledge Graph - DKG)
OriginTrail no es solo una blockchain, es un Grafo de Conocimiento Descentralizado (DKG).22 Permite conectar datos semánticos a través de diferentes sistemas, lo cual es excelente para la interoperabilidad de datos complejos y la IA.
●	Pros: Potencial sinergia con los agentes de IA de AgroConecta 1 para verificar la integridad de los datos que alimentan los modelos de lenguaje.
●	Contras: Alta complejidad técnica. Implementar un nodo DKG o interactuar con el grafo requiere conocimientos especializados en grafos de conocimiento y web semántica. El coste de publicar datos en el grafo (en tokens TRAC) añade una variable de coste variable difícil de predecir para el modelo de suscripción de AgroConecta.23
5.3 WordProof y Bernstein.io
●	Bernstein.io: Se especializa en la protección de Propiedad Intelectual (secretos comerciales, recetas) mediante sellado en Bitcoin.24 Aunque utiliza la misma tecnología base que OTS, su modelo de negocio es SaaS, cobrando por créditos de certificación.25
●	WordProof: Ofrece un módulo de Drupal ya existente para sellar contenido (artículos, páginas) en blockchains como EOSIO o Tezos.26
○	Análisis: Usar el módulo de WordProof tal cual sería un error para RETECH (es un plugin de terceros). Sin embargo, estudiar su arquitectura para replicar la lógica de sellado aplicada a los "Lotes de Producción" (en lugar de artículos de blog) es una estrategia de desarrollo válida.
6. Análisis Comparativo Estructurado
La siguiente tabla sintetiza la evaluación de las opciones frente a los criterios críticos del proyecto AgroConecta y la subvención RETECH.

Criterio de Evaluación	EBSI (Timestamp API)	OpenTimestamps (OTS)	IBM Food Trust	SaaS (VeChain/OriginTrail)
Alineación "Solución Propia" (RETECH)	Alta: Requiere desarrollar conector custom.	Muy Alta: Exige desarrollo de lógica compleja.	Baja: Riesgo de ser considerado reventa.	Media: Integración vía API estándar.
Coste Operativo para la PYME	Bajo (Gratuito en pilotos/fase actual).	Nulo: Infraestructura pública gratuita.	Alto: Suscripción mensual recurrente.	Variable: Depende del uso/tokens.
Barrera de Entrada Técnica	Alta: Credenciales, DIDs, acceso restringido.	Media: Librerías cliente, criptografía.	Media: Estándares GS1 obligatorios.	Alta: Gestión de wallets/tokens.
Soberanía y Permanencia	Institucional (Depende de la UE).	Absoluta: Matemáticamente garantizada.	Corporativa (Depende de IBM).	Red (Depende de la salud del token).
Viabilidad Inmediata (2025)	Riesgo: Acceso privado limitado/incierto.2	Inmediata: Protocolo abierto y estable.16	Inmediata.	Inmediata.
Valor de Marca ("Gourmet Digital")	Excelente ("Verified by Europe").	Bueno ("Verified by Bitcoin/Math").	Bueno (Marca IBM).	Neutro (Nicho tecnológico).
7. Recomendación Estratégica: El Módulo "AgroConecta Ledger"
Basado en el análisis, se descarta categóricamente el uso de IBM Food Trust y soluciones SaaS similares debido al riesgo de inelegibilidad para la subvención RETECH y los costes operativos insostenibles para el avatar "Marta Gómez".
La recomendación firme es adoptar una Estrategia Híbrida Evolutiva, comenzando con una implementación propietaria sobre OpenTimestamps diseñada para ser compatible con EBSI en el futuro.
7.1 La Propuesta de Valor Técnica
AgroConecta desarrollará un módulo personalizado para Drupal 11, denominado internamente agroconecta_ledger, que actuará como la autoridad de certificación de la plataforma. Este módulo no dependerá de servicios de terceros para la validación, sino que construirá las pruebas criptográficas internamente utilizando el protocolo OTS.
Esto permite a AgroConecta presentar ante la Junta de Andalucía un proyecto de I+D+i real: "Desarrollo de un sistema de trazabilidad descentralizada agnóstico para la cadena agroalimentaria andaluza".
7.2 Hoja de Ruta de Implementación Técnica (Drupal 11)
La implementación se debe estructurar en fases para asegurar el cumplimiento de los plazos de la subvención y la robustez técnica.
Fase 1: Arquitectura de Datos y "Core" (Meses 1-2)
El primer paso es preparar el modelo de datos dentro de agroconecta_core 1 para soportar la inmutabilidad.
●	Ampliación de Entidad: Se deben añadir campos técnicos a la entidad Lote de Producción:
○	field_hash_sha256: Para almacenar la huella digital única de los datos del lote (fecha, origen, analítica).
○	field_ots_proof: Un campo de tipo archivo (File) para almacenar el fichero binario .ots que devuelve el servidor de calendario.
○	field_blockchain_status: Un campo de estado (Pendiente, Anclado, Verificado).
●	Servicio de Hashing: Implementar un servicio PHP (HashingService) que serialice los datos del lote de forma determinista y calcule su hash SHA-256. Es crucial normalizar los datos (ordenar claves JSON) para asegurar que el hash sea siempre idéntico para los mismos datos.
Fase 2: El Motor de Anclaje OTS (Meses 2-3)
Desarrollo del servicio de integración con OpenTimestamps. Dado que no hay librería PHP nativa robusta compatible con PHP 8.4 (requisito de Drupal 11), se optará por una arquitectura de Microservicio Local o Wrapper CLI.
●	Wrapper CLI: La solución más eficiente para "Sin Humo". El servidor de AgroConecta tendrá instalado el cliente Python de OTS.
●	Servicio Drupal: BlockchainAnchorService invocará el comando del sistema:
ots stamp --calendar https://alice.btc.calendar.opentimestamps.org -o /ruta/privada/proofs/lote_123.ots /ruta/temporal/lote_123.txt
●	Gestión de Asincronía: El anclaje en Bitcoin no es instantáneo (tarda ~10 minutos en confirmar el bloque). El módulo debe implementar un CronJob o un QueueWorker en Drupal que:
1.	Envíe el hash al calendario (instantáneo).
2.	Guarde la prueba incompleta.
3.	Se ejecute periódicamente para "actualizar" (upgrade) la prueba una vez que el bloque de Bitcoin se haya minado, completando la cadena de confianza.
Fase 3: Automatización con ECA (Mes 3)
Integración con el módulo ECA para automatizar el flujo sin intervención del productor.1
●	Evento: Insert o Update de una entidad Lote de Producción.
●	Condición: El campo field_blockchain_status no es "Anclado".
●	Acción: Encolar el lote para el BlockchainAnchorService.
Fase 4: La Experiencia de Usuario "Phy-gital" (Mes 4)
Desarrollo del Frontend en el tema agroconecta_theme.
●	Verificación en Cliente: Cuando el consumidor escanea el QR, la página de aterrizaje carga el archivo .ots (público) y los datos del lote.
●	Librería JS: Se integra la librería opentimestamps.js en el tema. El navegador del usuario recalcula el hash de los datos que ve en pantalla y lo compara con el archivo .ots, verificando criptográficamente que "lo que ve es lo que se firmó".
●	UI de Confianza: Si la verificación es correcta, se muestra un distintivo verde: "Certificado de Origen Inmutable - Anclado en Bitcoin".
7.3 Preparación para EBSI (Horizonte 2026)
Para asegurar la longevidad y el alineamiento institucional, el módulo agroconecta_ledger se diseñará utilizando el Patrón Adaptador (Adapter Pattern). Se definirá una interfaz AnchorInterface. La implementación inicial será OTSAnchor, pero la arquitectura estará lista para conectar un EBSIAnchor en el futuro.
Cuando EBSI abra sus puertas a las PYMES privadas en 2026, AgroConecta podrá activar este segundo adaptador, permitiendo un "Doble Anclaje": seguridad matemática vía Bitcoin y reconocimiento institucional vía EBSI, ofreciendo así la solución de trazabilidad más robusta del mercado.
8. Conclusión
La adopción de OpenTimestamps como tecnología base, encapsulada dentro de un módulo de desarrollo propio en Drupal 11, constituye la estrategia ganadora para AgroConecta. Satisface los estrictos requisitos de la subvención RETECH al demostrar innovación y desarrollo de software genuino, protege la estructura de costes de la plataforma y de sus clientes (las PYMES) al evitar licencias de terceros, y se alinea con la ética de transparencia "Sin Humo".
Al rechazar las "Cajas Negras" de las grandes consultoras y optar por la construcción de tecnología soberana sobre protocolos abiertos, AgroConecta no solo asegura su financiación, sino que construye un activo tecnológico real y perdurable para el campo andaluz. La plataforma nace compatible con la realidad del mercado (Bitcoin) y preparada para el futuro institucional (EBSI), una posición estratégica inmejorable.
Obras citadas
1.	1. El Ecosistema Integrado de Pepe Jaraba. Emprendimiento y Empleabilidad Digital.docx
2.	Simplification of registration of companies in the 28th Regime - European Parliament, fecha de acceso: enero 8, 2026, https://www.europarl.europa.eu/RegData/etudes/IDAN/2025/776000/IUST_IDA(2025)776000_EN.pdf
3.	European blockchain services infrastructure | Shaping Europe's digital future, fecha de acceso: enero 8, 2026, https://digital-strategy.ec.europa.eu/en/policies/european-blockchain-services-infrastructure
4.	Home - EBSI - - European Commission, fecha de acceso: enero 8, 2026, https://ec.europa.eu/digital-building-blocks/sites/spaces/EBSI/pages/447687044/Home
5.	Understanding EBSI's Blockchain Ecosystem, fecha de acceso: enero 8, 2026, https://hub.ebsi.eu/blockchain
6.	Blockchain Effect on SaaS Companies - Augurian, fecha de acceso: enero 8, 2026, https://augurian.com/blog/blockchain-effect-on-saas-companies/
7.	Timestamp API - EBSI hub, fecha de acceso: enero 8, 2026, https://hub.ebsi.eu/apis/pilot/timestamp
8.	Timestamp API v3 - EBSI hub, fecha de acceso: enero 8, 2026, https://hub.ebsi.eu/apis/pilot/timestamp/v3
9.	Changes | EBSI hub, fecha de acceso: enero 8, 2026, https://hub.ebsi.eu/changes
10.	All APIs | EBSI hub, fecha de acceso: enero 8, 2026, https://hub.ebsi.eu/apis
11.	Early Adopters - EBSI - - European Commission, fecha de acceso: enero 8, 2026, https://ec.europa.eu/digital-building-blocks/sites/spaces/EBSI/pages/802128059/Early+Adopters
12.	OpenTimestamps, fecha de acceso: enero 8, 2026, https://opentimestamps.org/
13.	OpenTimestamps and Knots/OCEAN - Peter Todd, fecha de acceso: enero 8, 2026, https://petertodd.org/2025/opentimestamps-and-knots-ocean
14.	OpenTimestamps - Wikipedia, fecha de acceso: enero 8, 2026, https://en.wikipedia.org/wiki/OpenTimestamps
15.	opentimestamps-client/release-notes.md at master - GitHub, fecha de acceso: enero 8, 2026, https://github.com/opentimestamps/opentimestamps-client/blob/master/release-notes.md
16.	OpenTimestamps client - GitHub, fecha de acceso: enero 8, 2026, https://github.com/opentimestamps/opentimestamps-client
17.	IBM takes its food supply blockchain solution worldwide, fecha de acceso: enero 8, 2026, https://www.supplychaindive.com/news/IBM-Food-Trust-SaaS-available-Carrefour/539065/
18.	Topco Joins IBM's Food Trust Blockchain Network, fecha de acceso: enero 8, 2026, https://www.topco.com/News/topco-joins-ibms-food-trust-blockchain-network
19.	VeChain Thor (VET) Price Prediction 2024, 2025-2030 | PrimeXBT, fecha de acceso: enero 8, 2026, https://primexbt.com/for-traders/vechain-price-prediction/
20.	VeChain's 2026 Manifesto: The Fight for Utility in a “Casino Market”, fecha de acceso: enero 8, 2026, https://vechain.org/vechains-2026-manifesto
21.	VeChain Price Prediction: Is VET Crypto a Good Investment? - StealthEX.io, fecha de acceso: enero 8, 2026, https://stealthex.io/blog/vechain-price-prediction/
22.	OriginTrail Price, TRAC Price, Live Charts, and Marketcap - Coinbase, fecha de acceso: enero 8, 2026, https://www.coinbase.com/price/origintrail
23.	Delegated Staking - OriginTrail, fecha de acceso: enero 8, 2026, https://docs.origintrail.io/graveyard/everything/delegated-staking
24.	The blockchain for better NDA and licensing contracts - Bernstein.io, fecha de acceso: enero 8, 2026, https://www.bernstein.io/blockchain-nda-contracts
25.	Pricing - Bernstein.io - Blockchain for intellectual property, fecha de acceso: enero 8, 2026, https://www.bernstein.io/pricing
26.	WordProof | Drupal.org, fecha de acceso: enero 8, 2026, https://www.drupal.org/project/wordproof
