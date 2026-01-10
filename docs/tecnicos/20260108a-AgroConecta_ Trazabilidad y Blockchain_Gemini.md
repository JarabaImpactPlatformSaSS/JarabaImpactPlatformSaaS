Evaluación Integral de Trazabilidad y Viabilidad de Integración Blockchain en la Arquitectura Drupal 11 de AgroConecta: Análisis Técnico, Estratégico y Normativo para el Marco RETECH
1. Resumen Ejecutivo y Alcance del Informe
El presente informe técnico constituye una evaluación exhaustiva y multidimensional de la plataforma digital AgroConecta, diseñada como una solución de "Comercio Gourmet Desacoplado" para el sector agroalimentario de Andalucía. El propósito fundamental de este documento es determinar, con rigor académico y precisión técnica, el nivel de madurez actual de sus sistemas de trazabilidad y analizar la viabilidad, necesidad estratégica y ruta de implementación para la integración de tecnología Blockchain (DLT) sobre su arquitectura base en Drupal 11. Este análisis no se realiza en el vacío, sino que está intrínsecamente vinculado a los requisitos de elegibilidad y justificación de valor ("Solución Propia") de la subvención RETECH ("Redes de Emprendimiento Digital") y las ayudas a la modernización agroindustrial de la Junta de Andalucía para el periodo 2025-2026.
La investigación parte de una premisa crítica: el sector agroalimentario andaluz, caracterizado por productos de excelencia física (Aceite de Oliva Virgen Extra, Vinos con Denominación de Origen, Ibéricos), sufre una desconexión estructural con su representación digital. El avatar de cliente objetivo, "Marta Gómez", gerente de una explotación familiar, requiere herramientas que no solo faciliten la venta (transaccional), sino que certifiquen la calidad (reputacional) ante un mercado global saturado de fraude y "greenwashing".
El análisis de la situación actual ("As-Is") revela que AgroConecta, en su versión 1.0, opera bajo una arquitectura Headless sofisticada, utilizando Drupal 11 como gestor de contenidos y experiencias (Content Management Framework) y Ecwid como motor transaccional SaaS. La trazabilidad se resuelve mediante entidades de contenido nativas (lote_produccion) y códigos QR. Si bien este modelo supera la funcionalidad de un e-commerce estándar al incorporar narrativa de origen, se clasifica técnicamente como un sistema de Trazabilidad Declarativa Centralizada. La integridad de los datos reside exclusivamente en la base de datos SQL del servidor, lo que la hace vulnerable a manipulaciones internas y reduce su valor como prueba forense ante terceros.
Frente a esto, la integración de Blockchain se presenta no como una mera actualización tecnológica, sino como un cambio de paradigma hacia la Trazabilidad Verificable Descentralizada. El informe examina las implicaciones de adoptar tecnologías de registro distribuido, contrastando modelos de redes públicas (Ethereum/EVM), redes de consorcio (Hyperledger Fabric) y grafos de conocimiento descentralizados (OriginTrail). Se concluye que, dadas las restricciones técnicas de Drupal 11 (requerimientos de PHP 8.3, obsolescencia de módulos contribuidos antiguos) y la filosofía "Sin Humo" (eficiencia operativa) de AgroConecta, la estrategia óptima no es el despliegue de nodos completos, sino una arquitectura de Anclaje Digital (Hash Anchoring).
Esta solución híbrida propone utilizar Drupal como oráculo de datos que "notariza" criptográficamente los lotes de producción en una red de capa 2 (como Polygon) o mediante APIs de certificación, garantizando la inmutabilidad sin sacrificar el rendimiento de la experiencia de usuario. Esta aproximación no solo eleva el Nivel de Madurez de Trazabilidad (TML) de la plataforma, sino que proporciona la justificación definitiva de "desarrollo original" requerida por la normativa RETECH, asegurando la financiación y la sostenibilidad del modelo de negocio a largo plazo.
________________________________________2. Marco Estratégico y Normativo: El Imperativo de la Digitalización Certificada
2.1. El Contexto del Sector Agroalimentario Andaluz y la Brecha Digital
La agricultura y la industria agroalimentaria representan un pilar fundamental de la economía andaluza, pero se enfrentan a una presión dual: la necesidad de eficiencia operativa y la demanda de transparencia radical por parte de los mercados internacionales. El Plan Estratégico de la Política Agrícola Común (PAC) 2023-2027 y las normativas autonómicas derivadas, como la Orden de 29 de septiembre de 2025, establecen un marco donde la digitalización deja de ser opcional para convertirse en un requisito de supervivencia y competitividad.1
El análisis del perfil del beneficiario tipo, representado por las microempresas y PYMES agroalimentarias (el segmento de "Marta Gómez"), revela una disparidad crítica. Estas empresas suelen poseer productos de altísimo valor organoléptico y cultural, amparados frecuentemente por figuras de calidad diferenciada (DOP, IGP), pero carecen de la infraestructura digital para capitalizar ese valor en el canal directo al consumidor (D2C).1 Su presencia digital se limita a menudo a webs corporativas estáticas o tiendas online desconectadas de la realidad productiva, lo que genera problemas de stock ("Agotado" fuera de temporada) y una dependencia excesiva de la distribución tradicional que erosiona los márgenes.1
La "Brecha de Valor" identificada en el modelo de negocio de AgroConecta es precisamente esa distancia entre la calidad física y la mediocridad digital. La solución propuesta no busca simplemente "digitalizar" (poner en internet), sino "valorizar" digitalmente el activo físico. En este sentido, la trazabilidad no es un mero cumplimiento administrativo del Reglamento (CE) nº 178/2002, sino el activo de marketing más potente disponible: la capacidad de probar, sin lugar a dudas, que una botella de aceite proviene de una parcela específica y fue molturada en una fecha concreta.
2.2. La Subvención RETECH y el Requisito de "Solución Propia"
El catalizador financiero para abordar esta transformación es el programa RETECH ("Redes de Emprendimiento Digital"). Este programa es singular en su enfoque, ya que no financia la simple adquisición de software comercial estándar, sino que exige la implementación de soluciones innovadoras y personalizadas. El análisis detallado de las bases reguladoras 1 destaca un criterio de elegibilidad excluyente y decisivo para la arquitectura técnica: el concepto de "Solución Propia".
La normativa establece explícitamente que se excluyen de la financiación las actividades de "venta, implantación o prestación de servicios referentes a productos de fuentes abiertas... sin modificaciones de valor suficientes y demostrables".1 Esto coloca a cualquier proveedor de servicios digitales en una posición delicada: una instalación estándar de Drupal, WordPress o PrestaShop, por muy bien configurada que esté, corre el riesgo de ser desestimada como "no innovadora" o carente de propiedad intelectual suficiente.
Aquí es donde la estrategia tecnológica de AgroConecta debe ser defensiva y proactiva. La arquitectura no puede limitarse a ensamblar piezas existentes ("Lego" de módulos contribuidos); debe demostrar ingeniería de software original. La integración de Blockchain se convierte, bajo esta óptica, en una herramienta estratégica de justificación de subvención. Desarrollar un módulo personalizado en Drupal 11 que interactúe criptográficamente con una red distribuida constituye, por definición, una "modificación de valor suficiente y demostrable", alejando el proyecto de la categoría de commodity y situándolo en la de innovación tecnológica subvencionable (con ayudas de hasta 40.000 € en la Línea 2).1
2.3. Alineación con las Ayudas a la Transformación Agroalimentaria
Paralelamente a RETECH, la Junta de Andalucía ha convocado ayudas por valor de 88 millones de euros para la transformación y comercialización de productos agrícolas (Intervención 68422).2 Estas ayudas priorizan inversiones que fomenten la "mejora de la orientación al mercado", la "digitalización" y la "trazabilidad".1
El análisis de los criterios de valoración de estas convocatorias muestra una preferencia clara por proyectos que integren la sostenibilidad y la innovación en los procesos. La trazabilidad Blockchain encaja transversalmente en estos objetivos:
1.	Orientación al Mercado: Permite la venta premium basada en la confianza.
2.	Innovación: Introduce tecnologías de la Industria 4.0 (DLT).
3.	Sostenibilidad: Permite certificar prácticas agronómicas sostenibles (huella hídrica, ecológica) de manera inmutable, combatiendo el greenwashing.
Por tanto, la viabilidad de integrar Blockchain en AgroConecta no debe evaluarse solo desde el coste técnico, sino desde el Retorno de la Inversión (ROI) Institucional: la capacidad de captar fondos públicos que, de otro modo, serían inaccesibles para una solución web convencional.
________________________________________3. Evaluación Técnica de la Arquitectura Actual ("As-Is")
3.1. Arquitectura "Headless Gourmet": Drupal 11 como CMF
La plataforma AgroConecta se ha construido sobre una filosofía de diseño denominada "Headless Gourmet" y "Sin Humo".1 Esta arquitectura responde a la necesidad de ofrecer una experiencia de usuario (UX) de altísima calidad ("Gourmet") sin la deuda técnica y la lentitud de los sistemas monolíticos tradicionales.
Técnicamente, esto se traduce en una separación de responsabilidades estricta:
●	Capa de Presentación y Contenidos (Frontend/CMF): Gestionada por Drupal 11. Drupal no se usa aquí como un simple gestor de páginas, sino como un marco de gestión de datos estructurados (Content Management Framework). Su responsabilidad es el storytelling: la narrativa de la marca, la presentación visual de los productos, las recetas y la gestión de la identidad corporativa.
●	Capa Transaccional (Backend Commerce): Delegada en Ecwid. Esta decisión estratégica "Sin Humo" descarga la complejidad de la gestión de cobros, seguridad PCI-DSS, impuestos y logística en un SaaS especializado.1
●	Capa de Integración (API Layer): Un módulo personalizado, agroconecta_core, actúa como el "sistema nervioso", inyectando los widgets y lógicas de compra de Ecwid dentro de las plantillas Twig de Drupal.1
Esta arquitectura es sólida para el comercio electrónico, permitiendo escalabilidad y seguridad en las transacciones. Sin embargo, el desafío surge cuando analizamos cómo se gestiona la "verdad" sobre el producto.
3.2. El Modelo de Datos de Trazabilidad Actual: La Entidad "Lote de Producción"
En la versión actual de AgroConecta, la trazabilidad se implementa mediante una entidad de contenido personalizada en Drupal: lote_produccion.1 Este enfoque es un ejemplo clásico de modelado de datos relacional dentro de un CMS.
La estructura de datos de esta entidad incluye campos críticos para la trazabilidad 1:
●	Identificador Único (field_id_lote): Un código alfanumérico que actúa como clave primaria lógica, vinculando el registro digital con el lote físico etiquetado en el almacén.
●	Relación de Producto (field_producto_asociado): Una referencia de entidad (Entity Reference) que conecta el lote específico (ej. "Cosecha Octubre 2025") con el producto comercial general (ej. "Aceite Picual Premium"). Esto permite que, al visitar la ficha del producto, el consumidor pueda ver los lotes disponibles o, inversamente, al consultar un lote, pueda comprar el producto.
●	Datos de Origen y Proceso: Campos taxonómicos y de fecha para registrar la Finca/Parcela de origen, la Variedad de aceituna o uva, la field_fecha_cosecha y la field_fecha_molturacion.
●	Evidencia Documental (field_certificados_pdf): Un campo de archivo que permite subir los boletines de análisis físico-químico y organoléptico emitidos por laboratorios externos.
La lógica de negocio encapsulada en agroconecta_core automatiza la generación de un puente "phy-gital" (físico-digital): al guardar una nueva entidad de lote, el sistema genera automáticamente una URL canónica única y un código QR descargable que apunta a dicha URL.1
3.3. Evaluación del Nivel de Madurez de Trazabilidad (TML)
Para evaluar objetivamente este sistema, aplicamos un marco de madurez de trazabilidad digital basado en la literatura académica reciente sobre cadenas de suministro agroalimentarias.4

Dimensión de Trazabilidad	Evaluación AgroConecta v1.0 (Drupal Nativo)	Análisis Crítico
Amplitud (Breadth)	Alta. Capacidad de registrar múltiples atributos (origen, calidad, fechas).	El sistema de entidades de Drupal permite modelar cualquier dato necesario, superando a plataformas rígidas.
Profundidad (Depth)	Media-Alta. Rastrea desde la finca hasta el producto final (Lote).	Cubre el tramo crítico para la D.O. Sin embargo, no rastrea "hacia adelante" (distribución, retail) una vez que el producto sale de la bodega, perdiendo la pista en la cadena logística externa.
Precisión (Precision)	Nivel Lote. Adecuado para la industria transformadora.	No alcanza la granularidad de "ítem único" (serialización individual de cada botella), lo cual sería costoso e innecesario para este segmento de mercado actualmente.
Acceso y Transparencia	Alta (Frontend). Información accesible vía QR sin barreras.	El consumidor accede fácilmente a la información. La UX es excelente.
Integridad de Datos (Trust)	Baja / Centralizada. Dependiente de la autoridad de la marca.	Punto Crítico. La "verdad" reside en una base de datos MySQL mutable. Un administrador con privilegios puede alterar la fecha de cosecha o cambiar el PDF de análisis meses después de la venta sin dejar rastro público.
Interoperabilidad	Baja. Silo de datos propietario.	Los datos no siguen estándares de intercambio automático (como GS1 EPCIS) para integrarse con sistemas de grandes retailers o logísticos.6
Conclusión del Diagnóstico Actual: AgroConecta v1.0 ofrece una trazabilidad informativa y de marketing, excelente para el storytelling y la diferenciación comercial básica. Sin embargo, carece de trazabilidad certificada o probatoria. En un escenario de crisis de confianza (fraude en el sector, reclamación de un importador), la base de datos de Drupal no constituye una prueba inmutable de integridad. Es un sistema basado en la confianza en el emisor ("Trust-based"), no en la verificación tecnológica ("Trustless").
________________________________________4. Fundamentos de Blockchain para la Trazabilidad Agroalimentaria
Para comprender el salto cualitativo que implica la integración, es necesario analizar qué aporta específicamente la tecnología Blockchain (DLT) frente a la base de datos tradicional de Drupal.
4.1. El Cambio de Paradigma: De la Persistencia a la Inmutabilidad
La tecnología Blockchain introduce un "Libro Mayor Distribuido" (Distributed Ledger) donde cada evento de la cadena de suministro se registra como una transacción criptográficamente sellada.7 Las propiedades fundamentales que aporta al sector agroalimentario son:
1.	Inmutabilidad (Tamper-Proof): Una vez que un bloque de transacciones es validado y añadido a la cadena, es computacionalmente inviable modificarlo sin alterar todos los bloques subsiguientes y obtener el consenso de la red.9 Esto transforma el dato de "editable" a "eterno".
2.	Descentralización: La verdad no reside en el servidor de AgroConecta, sino replicada en múltiples nodos de la red. Esto elimina el punto único de fallo y la posibilidad de colusión interna para falsificar registros.11
3.	Auditabilidad Pública: En redes públicas o permisionadas con acceso de lectura, cualquier actor (consumidor, auditor, regulador) puede verificar la existencia y la fecha de un registro sin pedir permiso a la empresa productora.12
4.	Automatización Inteligente (Smart Contracts): Permite ejecutar lógica de negocio automática. Por ejemplo, liberar un pago al agricultor automáticamente cuando el lote es recepcionado y validado en la almazara, o invalidar un lote si un sensor IoT registra una rotura de la cadena de frío.13
4.2. Taxonomía de Soluciones Blockchain y su Ajuste a AgroConecta
No todas las blockchains son iguales, y la elección de la arquitectura determina la viabilidad técnica y económica del proyecto.
A. Redes de Consorcio / Permisionadas (Ej. Hyperledger Fabric)
Son el estándar corporativo para grandes cadenas de suministro (como IBM Food Trust).14 Ofrecen privacidad granular (los competidores no ven tus datos) y alto rendimiento.
●	Análisis para AgroConecta: Inviable. Requieren una infraestructura pesada (nodos peers, orderers, servicios de certificación) que contradice la filosofía "Sin Humo" de eficiencia y bajo coste operativo para una PYME.16 Además, la falta de SDKs de PHP mantenidos activamente hace que la integración con Drupal sea compleja y costosa de mantener.17
B. Redes Públicas (Ethereum, Polygon, Solana)
Redes abiertas donde cualquiera puede escribir y leer. Son ideales para la transparencia cara al consumidor.
●	Análisis para AgroConecta: Alta Viabilidad. El ecosistema Ethereum (EVM) tiene las mejores herramientas de desarrollo y compatibilidad. El problema de los costes de transacción (Gas) en la red principal de Ethereum se mitiga utilizando redes de Capa 2 (Layer-2) como Polygon, donde el coste de "notarizar" un lote es fraccionario (céntimos de euro).18
C. Grafos de Conocimiento Descentralizados (OriginTrail)
Una capa tecnológica diseñada específicamente para conectar datos de supply chain de manera semántica y verificable, funcionando como un middleware entre el ERP/Web y la Blockchain subyacente.19
●	Análisis para AgroConecta: Interesante Estratégicamente. OriginTrail permite estructurar los datos del grafo de conocimiento (DKG) de una forma muy alineada con la estructura de entidades de Drupal. Sin embargo, añade una capa de complejidad técnica y dependencia de un token específico (TRAC) que podría complicar la gestión para una PYME no tecnológica.
D. Plataformas Blockchain-as-a-Service (BaaS) (Ej. VeChain ToolChain)
Soluciones "llave en mano" que ofrecen APIs REST para interactuar con la blockchain sin gestionar nodos ni wallets complejas.20
●	Análisis para AgroConecta: Máxima Alineación con "Sin Humo". Al igual que Ecwid gestiona la complejidad del e-commerce, una solución BaaS gestionaría la complejidad criptográfica. Sin embargo, puede generar dependencia del proveedor (vendor lock-in) y costes recurrentes por suscripción.
________________________________________5. Análisis de Viabilidad de Integración en Drupal 11
Este es el núcleo técnico del informe. La migración a Drupal 11 impone restricciones severas debido a la modernización de su core (basado en Symfony 7) y la exigencia de PHP 8.3.
5.1. El Desafío de la Obsolescencia de Módulos
El ecosistema de módulos contribuidos de Drupal para Blockchain está en un estado de transición crítica. Muchos módulos que funcionaban en Drupal 7, 8 o 9 no son compatibles con Drupal 11.21
●	Módulo blockchain: Diseñado para crear una blockchain dentro de Drupal. Esto es conceptualmente erróneo para nuestro caso de uso (Drupal no debe ser el nodo, sino el cliente) y el módulo no tiene mantenimiento activo para D11.23
●	Módulo fabric (Hyperledger): Abandonado y sin cobertura de seguridad. Intentar usarlo en D11 requeriría una reescritura total del código, lo cual es un esfuerzo desproporcionado.21
●	Módulo ethereum: Históricamente el más robusto, actualmente se encuentra en un proceso de reescritura profunda hacia la rama 3.0.x para adaptarse a las nuevas librerías PHP y estándares Web3. Su uso en producción en D11 conlleva riesgos de estabilidad.24
●	Módulo crypto_widget: Funcional para visualización de precios, pero inútil para trazabilidad o escritura en cadena.25
Conclusión de Viabilidad de Módulos: No existe un módulo "plug-and-play" en el ecosistema Drupal 11 actual que resuelva la necesidad de trazabilidad de AgroConecta. Esto es una ventaja estratégica para RETECH: confirma la necesidad ineludible de desarrollar un Módulo Personalizado (Custom Module), lo que justifica plenamente la partida de desarrollo de software original y cumple el requisito de "Solución Propia".
5.2. Compatibilidad PHP 8.3 y Librerías Externas
Drupal 11 se ejecuta sobre PHP 8.3, lo que implica tipos estrictos y deprecación de muchas funciones antiguas. La integración debe realizarse utilizando Composer para gestionar librerías PHP externas modernas que sí sean compatibles.
La librería candidata principal es web3p/web3.php. Es una librería PHP nativa para interactuar con nodos Ethereum/EVM. Su mantenimiento es activo y permite la firma de transacciones y la interacción con Smart Contracts. Integrar esta librería dentro de un módulo custom de Drupal 11 es técnicamente viable y robusto.
5.3. Impacto en el Rendimiento y la Experiencia "Gourmet"
Un riesgo crítico de Blockchain es la latencia. Las transacciones en Blockchain no son instantáneas; requieren tiempo de minado y confirmación (desde segundos en Polygon hasta minutos en Ethereum Mainnet).
Si AgroConecta diseñara el proceso de forma síncrona (es decir, que al guardar el Lote en Drupal, el sistema esperase a la confirmación de la Blockchain), la experiencia de usuario del gestor ("Marta") sería desastrosa: el navegador se congelaría durante segundos o minutos.
Solución Arquitectónica: La integración en Drupal 11 debe ser Asíncrona.
1.	Utilizar la Queue API de Drupal.
2.	Al guardar el lote, se crea un ítem en la cola ("Pendiente de Anclaje").
3.	Un Cron Job o un Worker procesa la cola en segundo plano, enviando las transacciones a la Blockchain.
4.	Una vez confirmada, el sistema actualiza la entidad del lote con el Hash de Transacción.
Esta arquitectura preserva la fluidez "Gourmet" del frontend mientras gestiona la complejidad criptográfica en el backend.
________________________________________6. Propuesta de Arquitectura: "AgroConecta Chain" (Modelo Híbrido)
Basado en el análisis anterior, se recomienda una arquitectura híbrida de Anclaje de Hashes (Hash Anchoring) sobre una red pública de bajo coste.
6.1. Definición del Modelo Híbrido
No se volcarán todos los datos del lote en la Blockchain (sería costoso e ineficiente). Se utilizará la Blockchain como un Notario Digital.
●	Off-Chain (Drupal): Almacena los datos ricos: textos, historias, imágenes, PDFs de análisis. Es la "Fuente de Información".
●	On-Chain (Blockchain): Almacena únicamente el "Hash" (huella digital criptográfica) de los datos críticos y la marca de tiempo. Es la "Prueba de Integridad".
6.2. Especificación del Módulo Custom agroconecta_integrity
Se desarrollará un módulo nuevo para Drupal 11 con los siguientes componentes:
1.	Servicio de Hashing (IntegrityManager):
○	Clase encargada de serializar los campos clave del lote (ID, Finca, Fecha, y el contenido binario del PDF de análisis).
○	Genera un hash SHA-256: Hash_Lote = SHA256(ID + Finca + Fecha + SHA256(PDF)).
○	Este diseño asegura que si alguien modifica el PDF en el servidor Drupal, el hash ya no coincidirá con el registrado en la Blockchain, revelando la manipulación.
2.	Conector Web3 (BlockchainConnector):
○	Servicio que envuelve la librería web3.php.
○	Gestiona la conexión RPC con la red Polygon (Matic). Se elige Polygon por ser una red EVM estándar (compatible con herramientas Ethereum), pública, segura y con costes de transacción extremadamente bajos (< 0.05€).
○	Gestiona de forma segura la clave privada de la "Wallet de la Almazara" utilizando el módulo Key de Drupal (almacenamiento fuera del webroot o en variables de entorno).
3.	Smart Contract de Registro (AgroRegistry.sol):
○	Un contrato inteligente sencillo desplegado en Polygon.
○	Función principal: registerBatch(string batchId, bytes32 dataHash).
○	Eventos: Emite un evento BatchRegistered que facilita la indexación y búsqueda externa.
4.	Sistema de Verificación Frontend:
○	En la plantilla Twig de la ficha del lote, se inyecta un componente JavaScript ligero.
○	Este JS lee el Transaction ID guardado en Drupal.
○	Consulta directamente a un nodo público de Polygon (independiente de Drupal) para verificar qué hash se registró en esa transacción.
○	Calcula el hash de los datos que se están mostrando en pantalla.
○	Resultado Visual: Si los hashes coinciden, muestra un sello de "Certificado Blockchain Verificado". Si no, muestra una alerta de "Datos no íntegros".
6.3. Integración con el Flujo de Trabajo Existente
El flujo operativo para "Marta Gómez" no cambia, respetando la simplicidad:
1.	Marta crea el "Lote de Producción" en Drupal y sube el PDF del laboratorio.
2.	Pulsa "Guardar".
3.	Drupal (en segundo plano) calcula el hash y lo envía a Polygon.
4.	Minutos después, aparece automáticamente el "Check Verde" de Blockchain en la ficha del lote.
5.	El QR generado lleva al consumidor a la página donde puede ver esta verificación en tiempo real.
________________________________________7. Hoja de Ruta de Implementación y Presupuesto
Para garantizar el éxito y la alineación con los plazos de RETECH, se propone una implementación en fases.
Fase 1: Desarrollo del Núcleo de Integridad (Meses 1-3)
●	Actividad: Desarrollo del Smart Contract AgroRegistry y del módulo Drupal agroconecta_integrity con servicio de hashing y conector Web3 básico.
●	Entregable: Capacidad de registrar hashes de lotes en la Testnet de Polygon (Amoy).
●	Hito RETECH: Justificación de "Solución Propia" mediante código original.
Fase 2: Automatización y UX (Meses 4-5)
●	Actividad: Implementación de la Queue API para asincronía. Desarrollo del widget de verificación frontend (JS/Twig). Gestión de claves de seguridad.
●	Entregable: Sistema funcional en Mainnet. UX fluida sin tiempos de espera.
●	Hito RETECH: Implantación piloto en una PYME real.
Fase 3: Ecosistema Extendido (Meses 6+)
●	Actividad: Integración de firmas digitales para laboratorios externos (permitir que el laboratorio firme el PDF antes de subirlo). Exploración de tokenización (NFTs) para fidelización de clientes.
●	Entregable: Plataforma de trazabilidad avanzada con múltiples actores.
Estimación de Costes (Orientativa para Memoria Técnica)
Partida	Estimación	Concepto Subvencionable
Ingeniería de Software (Módulo Drupal)	150 horas	Desarrollo Tecnológico / Solución Propia
Desarrollo Blockchain (Smart Contracts)	50 horas	Innovación Tecnológica
Auditoría de Seguridad y Testing	3.000 €	Calidad y Seguridad
Despliegue e Infraestructura (1er año)	2.000 €	Servicios en la Nube / Implantación
TOTAL ESTIMADO	~15.000 € - 20.000 €	Totalmente cubierto por RETECH (hasta 40k)
Nota: Los costes de transacción (Gas) en Polygon son operativos y despreciables (aprox. 10-50€ anuales para una almazara mediana), por lo que no representan una barrera de entrada.
________________________________________8. Conclusiones y Recomendación Final
El análisis realizado permite concluir categóricamente que la integración de Blockchain en AgroConecta es técnicamente viable, estratégicamente necesaria y económicamente rentable bajo el paraguas de la subvención RETECH.
1.	Necesidad Estratégica: El modelo de trazabilidad actual ("Confía en mi base de datos") es insuficiente para diferenciar a AgroConecta como una solución innovadora de alto valor. La trazabilidad Blockchain ("Confía en la matemática") aporta el diferencial necesario para ganar la subvención y competir en el mercado premium.
2.	Viabilidad Técnica: A pesar de los desafíos de Drupal 11, la arquitectura híbrida de anclaje de hashes sobre Polygon, gestionada por un módulo personalizado con librerías PHP modernas, es robusta, segura y escalable.
3.	Filosofía "Sin Humo": La solución propuesta evita la sobreingeniería (nodos propios, redes privadas complejas) y se centra en aportar valor tangible (verificación de integridad) con un coste operativo mínimo y una complejidad oculta para el usuario.
Recomendación: Se insta a proceder de inmediato con el diseño técnico del módulo agroconecta_integrity y la redacción de la memoria técnica de RETECH destacando esta integración como el núcleo innovador de la "Solución Propia". AgroConecta tiene la oportunidad de dejar de ser un "CMS para vender aceite" y convertirse en la Plataforma de Certificación Digital del Agro Andaluz.
Obras citadas
1.	20260105-Resolución de 23 de diciembre de 2025, TCDP Agro - Intervención 68422.pdf
2.	La Junta convoca ayudas que alcanzan 88 millones para la transformación y comercialización de productos agroalimentarios - Europa Press, fecha de acceso: enero 8, 2026, https://www.europapress.es/andalucia/andalucia-verde-01334/noticia-junta-convoca-ayudas-alcanzan-88-millones-transformacion-comercializacion-productos-agroalimentarios-20260105121914.html
3.	La Junta ultima la convocatoria de ayudas de 88M€ para modernización de la agroindustria - Noticias - Junta de Andalucía, fecha de acceso: enero 8, 2026, https://www.juntadeandalucia.es/organismos/agriculturapescaaguaydesarrollorural/servicios/actualidad/noticias/detalle/624145.html
4.	Agri-food traceability today, fecha de acceso: enero 8, 2026, https://openpub.fmach.it/retrieve/8aaa7972-c5b4-432c-bfef-370d6680d6e7/2025%20TFST%20Perini.pdf
5.	Digital Food Supply Chain Traceability Framework - MDPI, fecha de acceso: enero 8, 2026, https://www.mdpi.com/2504-3900/82/1/9
6.	Guide: Integrating with IBM Food Trust ™ Blockchain - ByteAlly, fecha de acceso: enero 8, 2026, https://byteally.com/insights/supply-chain/integrating-with-ibm-food-trust-blockchain-guide/
7.	Digital Traceability in Agri-Food Supply Chains: A Comparative Analysis of OECD Member Countries - MDPI, fecha de acceso: enero 8, 2026, https://www.mdpi.com/2304-8158/13/7/1075
8.	From source to stomach: How blockchain tracks food across the supply chain and saves lives - The World Economic Forum, fecha de acceso: enero 8, 2026, https://www.weforum.org/stories/2024/08/blockchain-food-supply-chain/
9.	Improving Agricultural Product Traceability Using Blockchain - PMC - PubMed Central, fecha de acceso: enero 8, 2026, https://pmc.ncbi.nlm.nih.gov/articles/PMC9103666/
10.	Improving Agricultural Product Traceability Using Blockchain - MDPI, fecha de acceso: enero 8, 2026, https://www.mdpi.com/1424-8220/22/9/3388
11.	Blockchain for Agri-Food Traceability - United Nations Development Programme, fecha de acceso: enero 8, 2026, https://www.undp.org/publications/blockchain-agri-food-traceability
12.	Blockchain Traceability in Agriculture | Case Study - Espeo Software, fecha de acceso: enero 8, 2026, https://espeo.eu/case-studies/blockchain-traceability-for-agricultural-logistics/
13.	Agriculture-Food Supply Chain Management Based on Blockchain and IoT: A Narrative on Enterprise Blockchain Interoperability - MDPI, fecha de acceso: enero 8, 2026, https://www.mdpi.com/2077-0472/12/1/40
14.	Introduction — Hyperledger Fabric Docs main documentation, fecha de acceso: enero 8, 2026, https://hyperledger-fabric.readthedocs.io/en/latest/whatis.html
15.	About your programming platform - IBM, fecha de acceso: enero 8, 2026, https://www.ibm.com/docs/en/transparent-supply?topic=developers-programming-platform
16.	Performance considerations — Hyperledger Fabric Docs main documentation, fecha de acceso: enero 8, 2026, https://hyperledger-fabric.readthedocs.io/en/latest/performance.html
17.	Client SDK for Hyperledger Fabric for use in PHP applications - GitHub, fecha de acceso: enero 8, 2026, https://github.com/americanexpress/hyperledger-fabric-sdk-php
18.	safe_smart_accounts 1.0.x-dev | Drupal.org, fecha de acceso: enero 8, 2026, https://www.drupal.org/project/safe_smart_accounts/releases/1.0.x-dev
19.	OriginTrail Decentralized Knowledge Graph (DKG), fecha de acceso: enero 8, 2026, https://docs.origintrail.io/dkg-knowledge-hub/learn-more/readme/decentralized-knowle-dge-graph-dkg
20.	What is VeChain, and how does it work? — TradingView News, fecha de acceso: enero 8, 2026, https://www.tradingview.com/news/cointelegraph:bb8c8e647094b:0-what-is-vechain-and-how-does-it-work/
21.	Hyperledger-Fabric | Drupal.org, fecha de acceso: enero 8, 2026, https://www.drupal.org/project/fabric
22.	Modules | Drupal.org, fecha de acceso: enero 8, 2026, https://www.drupal.org/project/project_module
23.	Blockchain module | Drupal.org, fecha de acceso: enero 8, 2026, https://www.drupal.org/project/blockchain
24.	Ethereum | Drupal.org, fecha de acceso: enero 8, 2026, https://www.drupal.org/project/ethereum
25.	Crypto Widget | Drupal.org, fecha de acceso: enero 8, 2026, https://www.drupal.org/project/crypto_widget
