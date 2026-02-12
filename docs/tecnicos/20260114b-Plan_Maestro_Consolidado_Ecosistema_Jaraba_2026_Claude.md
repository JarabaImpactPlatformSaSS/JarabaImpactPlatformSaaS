
PLAN MAESTRO CONSOLIDADO
ECOSISTEMA JARABA 2026
Arquitectura Estratégica, Técnica y Operativa
Versión:	3.0 (Armonizada Claude + Gemini)
Fecha:	Enero 2026
Estado:	Documento Definitivo
Clasificación:	Interno - Estratégico

Sistema Operativo de Negocio para la Transformación Digital "Sin Humo"
 
Tabla de Contenidos
Tabla de Contenidos	1
1. Resumen Ejecutivo	1
1.1 Principios Arquitectónicos	1
2. Modelo de Negocio: Triple Motor Económico	1
2.1 Estrategia de Ingresos Híbrida	1
2.2 Diferenciador "Océano Azul"	1
2.3 Estructura de Planes SaaS	1
3. Arquitectura Técnica del SaaS	1
3.1 Stack Tecnológico Principal	1
3.2 Arquitectura Soft Multi-Tenancy	1
3.3 Entidades Personalizadas del FOC	1
4. Verticales Comercializables	1
4.1 Las 5 Verticales Operativas	1
4.2 AgroConecta (Pymes Agrarias)	1
Presupuesto Modelo: Bodegas Robles	1
4.3 ComercioConecta (Comercio Local & Phygital)	1
Componentes Exclusivos	1
4.4 ServiciosConecta (Servicios Profesionales)	1
5. El Método Jaraba™: Ciclo de Impacto Digital (CID)	1
5.1 Las 3 Fases del CID	1
5.2 Modelo de Triaje Inteligente	1
6. Inteligencia Artificial y Ejército de Agentes	1
6.1 Sistema de Agentes Especializados	1
6.2 Arquitectura RAG Multi-Tenant	1
6.3 Estrategia GEO (Generative Engine Optimization)	1
Técnica Answer Capsule	1
7. Gamificación: Economía de Créditos de Impacto	1
7.1 Valoración y Equivalencia	1
7.2 Mecánica de Obtención (Earning)	1
7.3 Niveles de Expertise	1
7.4 Catálogo de Redención (Spending)	1
8. Centro de Operaciones Financieras (FOC)	1
8.1 Métricas de Salud y Crecimiento (North Star)	1
8.2 Métricas Específicas del Modelo Híbrido	1
8.3 Matriz de Alertas Financieras	1
9. Hoja de Ruta de Implementación	1
10. Valoración de Riesgos y Mitigación	1
11. Conclusión	1

 
1. Resumen Ejecutivo
Este documento consolida la arquitectura definitiva del Ecosistema Jaraba, integrando la documentación técnica de la Jaraba Impact Platform con el análisis estratégico y comercial. El resultado es un marco completo que abarca desde los principios arquitectónicos hasta los flujos operativos de cada vertical de negocio.
El Ecosistema Jaraba se posiciona como el Sistema Operativo de Negocio para la transformación digital "Sin Humo". Su objetivo es democratizar la tecnología de vanguardia (IA, SaaS, Automatización) para colectivos tradicionalmente excluidos: seniors, pymes rurales, comercio local y autónomos.
1.1 Principios Arquitectónicos
Principio	Implementación
Soberanía de Datos	SSOT (Single Source of Truth) centralizado en Drupal. Todos los datos financieros fluyen hacia el Data Warehouse interno.
Unit Economics	Descomposición de rentabilidad hasta nivel atómico: tenant individual y producto. Cost Allocation preciso para multi-tenancy.
Soft Multi-Tenancy	Una instalación Drupal + Group Module. Aislamiento lógico de datos con economías de escala en infraestructura.
Destination Charges	Stripe Connect con split inmediato de fondos. La plataforma solo factura por comisiones, no por GMV total.
Analítica Prescriptiva	No solo qué pasó, sino qué hacer. Sistema de alertas con playbooks automatizados vía ECA.
Filosofía "Sin Humo"	Rechazo al bloatware. Código limpio, desarrollo sobre estándares abiertos (Drupal Core, Bootstrap 5 SASS), automatización real.

 
2. Modelo de Negocio: Triple Motor Económico
El ecosistema trasciende la consultoría tradicional para convertirse en un Sistema Operativo de Negocio escalable. Su viabilidad se apoya en la diversificación y la sinergia entre múltiples verticales: Emprendimiento, Empleabilidad, PYMEs, Consultores y Entidades.
2.1 Estrategia de Ingresos Híbrida
Motor	Componentes	% Objetivo	Fase
Motor Institucional	Subvenciones, PERTE, Kit Digital, ONGs, Programas de Empleo. Lógica de bolsas presupuestarias y justificación de impacto.	30%	1-2
Motor Mercado Privado	Infoproductos, Club Jaraba (membresía), Mentorías, Cursos, Marketplace. Alta frecuencia transaccional.	40%	2-3
Motor de Licencias	Activación franquicia, Cuotas recurrentes, Royalties, Certificaciones de consultores. MRR predecible.	30%	3+

2.2 Diferenciador "Océano Azul"
A diferencia de competidores masivos (Vilma Núñez, Euge Oller, Laura Ribas), el Ecosistema Jaraba conecta la estrategia con la herramienta técnica (Drupal) y el talento cualificado (Bolsa de empleo) en un ciclo cerrado único. El ecosistema no solo forma, sino que emplea y comercializa en una misma plataforma.
2.3 Estructura de Planes SaaS
Plan	Precio/mes	Productos	Comisión	Características
Starter	29€	50	5%	Tienda básica, 1 usuario, email support
Growth	59€	500	3%	+ Multicanal, 3 usuarios, chat support
Pro	99€	2.000	1.5%	+ API, 10 usuarios, priority support
Enterprise	199€+	Ilimitado	Negociable	+ White-label, SLA, dedicado

 
3. Arquitectura Técnica del SaaS
La transición estratégica hacia Drupal 11 + Commerce 3.x + Stripe Connect es el pilar que permite abandonar la "obesidad digital" de WordPress/Elementor en favor de un rendimiento superior y control total sobre los datos.
3.1 Stack Tecnológico Principal
Componente	Tecnología	Función
Núcleo CMS	Drupal 11	Data Warehouse Operativo y CMS semántico
E-Commerce	Drupal Commerce 3.x	Gestión de productos, pedidos, tiendas multi-tenant
Multi-Tenancy	Group Module	Aislamiento lógico de tenants/franquicias
Pagos	Stripe Connect	Destination Charges con split automático
Integraciones	Make.com	Hub de integración para marketplaces externos
CRM/Marketing	ActiveCampaign	Automatización de comunicación (músculo)
Base Vectorial	Qdrant	RAG multi-tenant con namespaces aislados
Automatización	ECA Module	Event-Condition-Action para reglas de negocio
Forecasting	PHP-ML	Algoritmos de proyección financiera

3.2 Arquitectura Soft Multi-Tenancy
La gestión eficiente de múltiples tiendas/franquicias utiliza el módulo Group de Drupal para crear aislamiento lógico sin multiplicar instalaciones:
•	Eficiencia Operativa: Una actualización de seguridad se aplica instantáneamente a todos los tenants.
•	Aislamiento de Datos: Cada vendedor accede solo a su silo de productos y pedidos.
•	Economía de Escala: Cientos de groups coexisten con coste marginal cercano a cero.
•	Noisy Neighbor Detection: Monitoreo de recursos por Group ID para ajuste de pricing.

3.3 Entidades Personalizadas del FOC
El Centro de Operaciones Financieras (FOC) utiliza entidades inmutables para garantizar la integridad contable:
Entidad	Descripción
financial_transaction	Libro mayor contable (append-only). Registra todas las transacciones con UUID, amount, currency, timestamp y metadata.
cost_allocation	Asignación de costes por vertical, tenant y categoría. Permite calcular márgenes unitarios precisos.
foc_metric_snapshot	Instantáneas diarias de métricas calculadas. Permite análisis de tendencias y auditorías históricas.
deferred_revenue	Gestión de ingresos diferidos para cumplimiento ASC 606. Calendarios automáticos de reconocimiento.

 
4. Verticales Comercializables
El ecosistema implementa verticales especializados que pueden comercializarse como productos independientes a entidades, ayuntamientos y asociaciones empresariales.
4.1 Las 5 Verticales Operativas
Vertical	Avatar	Necesidad / Implementación	Métricas Clave
Empleabilidad	Lucía (+45)	LMS con rutas de aprendizaje, seguimiento de progreso, certificaciones automáticas	Completados, Certificaciones, Colocaciones
Emprendimiento	Javier (rural)	Webforms de diagnóstico con lógica condicional, itinerarios personalizados, Groups de colaboración	Diagnósticos, Negocios creados, GMV
PYMEs	Marta (negocio)	Drupal Commerce Stores individuales optimizadas GEO, acceso a talento formado	GMV, Productos listados, Contrataciones
Consultores	David (experto)	Rol Consultant con permisos avanzados sobre Groups, venta de mentorías via Stripe Connect	Usuarios gestionados, MRR mentorías, NPS
Entidades	Elena (admin)	Marca Blanca como Group con branding propio, reportes de impacto justificables	Usuarios cohorte, Grant Burn Rate, Impacto

4.2 AgroConecta (Pymes Agrarias)
AgroConecta es el vertical estratégico modelo que sirve como blueprint para futuras expansiones. No es un proyecto de sector "Agro", es un Framework de Impacto replicable a cualquier entidad que necesite un marketplace profesional.
Presupuesto Modelo: Bodegas Robles
Concepto	Descripción	Importe
Aprovisionamiento Tenant	Instancia aislada Drupal 11, seguridad y silo Qdrant	600 €
Branding Parametrizado	Inyección de identidad visual en tema base	300 €
Configuración Financiera	Alta y validación de cuenta Stripe Connect	300 €
TOTAL SETUP	Inversión inicial única	1.200 €
Licencia AgroConecta	Uso plataforma, hosting NVMe, actualizaciones	588 €/año
Pack Phygital	Integración POS + Módulo Trazabilidad QR	800 €
Onboarding Método Jaraba	Sesión triaje + Formación equipo	600 €
TOTAL AÑO 1	Setup + Licencia + Phygital + Formación	3.188 €
AÑO 2+	Mantenimiento recurrente	588 €/año

Nota: Este presupuesto es 100% elegible para el Bono Kit Digital (Segmento II y III), lo que podría reducir la inversión inicial a 0 €.

4.3 ComercioConecta (Comercio Local & Phygital)
Vertical diseñado para la digitalización del comercio de proximidad. Es el producto estrella para la Administración Pública (B2G): "No les damos una web, les damos un Sistema Operativo de Barrio que une sus tiendas físicas con el mundo digital".
Componentes Exclusivos
Componente	Función "Sin Humo"
Motor POS Sync	Conector preconfigurado para TPV comunes (Square, SumUp, Shopify POS)
Escaparate Inteligente	Herramienta para ofertas "Flash" que solo duran mientras la tienda está abierta
Local SEO Booster	Automatización que empuja productos a Google Maps y "Cerca de mí"
Agente Vendedor Local	IA entrenada en técnicas de venta cruzada para tiendas físicas
QR Dinámicos	Etiquetas físicas con trazabilidad y captación de reseñas in-situ

4.4 ServiciosConecta (Servicios Profesionales)
Vertical para digitalizar el capital intelectual y profesional de la zona: abogados, clínicas, arquitectos, consultores y técnicos. A diferencia del comercio, aquí no vendemos "stock", vendemos confianza, tiempo y conocimiento.
Funcionalidad	Descripción
Booking Engine	Gestión de citas con pago previo para evitar "no-shows". Sincronización con Google Calendar/Outlook.
Buzón de Confianza	Custodia documental cifrada y área privada segura para intercambio de archivos sensibles (DNI, contratos, informes).
Firma Digital PAdES	Integración con AutoFirma para contratos de servicios firmados sin salir de la plataforma.
Triaje de Casos	Agente de IA que realiza entrevista inicial para clasificar urgencia y tipo de servicio.
Presupuestador Auto	Asistente de IA que genera presupuestos basados en horas y materiales.

 
5. El Método Jaraba™: Ciclo de Impacto Digital (CID)
Todo usuario transita por tres fases quirúrgicas que transforman su situación inicial en resultados medibles.
5.1 Las 3 Fases del CID
Fase	Nombre	Descripción
Fase 1	Diagnóstico / Triaje	Triaje de capacidades y propósitos. Definición del Objetivo Mínimo Viable (OMV) a 90 días.
Fase 2	Acción / Motor Práctico	Construcción de activos (Web, LinkedIn, Tienda). Ejecución de la primera victoria (venta o entrevista).
Fase 3	Optimización / Multiplicador	Análisis de datos por IA, automatización de tareas recurrentes y escalado estratégico.

5.2 Modelo de Triaje Inteligente
El proceso automatiza la Fase 1 utilizando agentes de IA para eliminar la fricción técnica:
1.	Captación mediante Webform Inteligente: Captura de capacidades, propósitos y barreras con lógica condicional.
2.	Procesamiento Semántico (AI Intent Classification): El Consumer Copilot categoriza al usuario en escalas de valor.
3.	Diseño del Itinerario (GraphRAG): El sistema conecta el perfil con la oferta de la plataforma.
4.	Definición del OMV: La IA propone un objetivo alcanzable a 90 días que el alumno valida.
5.	Generación de la Hoja de Ruta: Documento "Plan de Impulso Digital" con la secuencia de pasos.
6.	Sincronización CRM (ECA): Lead Scoring automático y secuencia de bienvenida personalizada.

 
6. Inteligencia Artificial y Ejército de Agentes
La plataforma no solo usa IA, sino que está diseñada para que la IA venda los productos. Los agentes de IA son el "cerebro" (inteligencia) y ActiveCampaign es el "músculo" (ejecución).
6.1 Sistema de Agentes Especializados
Agente	Función
Market Spy Agent	Rastrea /llms.txt, sitemaps y microdatos de competidores para extraer cambios en precios, productos y debilidades.
Producer Copilot	Asiste en la creación de contenido optimizado para GEO. Genera Answer Capsules desde fotos y notas básicas.
Consumer Copilot	Asistente de compras con búsqueda semántica. Recomienda productos basándose en necesidades complejas.
AI Logger	Clasifica cada interacción del chat detectando señales de compra (PURCHASE_INTENT) o riesgo de abandono.
Content Gap Analyzer	Identifica preguntas de usuarios no cubiertas, sugiriendo nuevos contenidos o productos a crear.
Revisor de Vigencia	Rastrea boletines oficiales para marcar como obsoletos los contenidos que ya no son válidos (subvenciones, etc.).

6.2 Arquitectura RAG Multi-Tenant
El Cerebro Semántico utiliza una arquitectura RAG (Retrieval-Augmented Generation) con Qdrant que garantiza respuestas basadas exclusivamente en información verificada de cada cliente (Grounding Estricto).
•	Namespaces Aislados: Cada tenant tiene su propio espacio de datos vectoriales (ej. vertical_agro_tenant_bodegasrobles).
•	Filtros Obligatorios: Cada consulta incluye tenant_id para evitar mezcla de datos entre clientes.
•	Fragmentado Contextual: La IA entiende que una nota de cata pertenece a un vino específico, no a otro.
•	GraphRAG (Neo4j): Permite razonamiento relacional complejo, como matching entre habilidades y ofertas.

6.3 Estrategia GEO (Generative Engine Optimization)
La arquitectura Server-Side Rendering (SSR) de Drupal ofrece HTML semántico puro, vital para que los crawlers de LLMs (GPTBot, ClaudeBot, PerplexityBot) indexen el contenido.
Técnica Answer Capsule
Un bloque de contenido optimizado ubicado en los primeros 150-200 caracteres de cada ficha de producto, diseñado para ser citado por asistentes de IA:
Ejemplo: "El Aceite de Oliva Virgen Extra de Finca La Huerta es un aceite premium con acidez 0.2%, elaborado con aceitunas Picual de Jaén. Precio: 12.50€."

 
7. Gamificación: Economía de Créditos de Impacto
Un sistema de incentivos para asegurar el progreso y la retención de usuarios, transformando el avance académico y profesional en valor comercial dentro del ecosistema.
7.1 Valoración y Equivalencia
Equivalencia: 100 Créditos de Impacto = 1 € de valor interno para servicios o productos del marketplace.

7.2 Mecánica de Obtención (Earning)
Fase	Acción del Usuario (Hito)	Recompensa	Insignia (Badge)
1	Completar triaje inicial y auditoría de brechas	50 CR	Explorador Digital
1	Definir Objetivo Mínimo Viable (OMV) a 90 días	100 CR	Arquitecto de Ruta
2	Optimizar Perfil LinkedIn (Avatar Lucía/David)	200 CR	Marca con Impacto
2	Publicar primer producto/servicio en Marketplace	300 CR	Motor Encendido
3	Enviar Informe de Resultados del primer ciclo	500 CR	Impulsor Senior
3	Obtener primera valoración 5 estrellas de cliente	250 CR	Sello de Calidad

7.3 Niveles de Expertise
Nivel	Créditos	Ventajas
Asociado	0 - 1.000	Acceso a comunidad básica y recursos gratuitos
Certificado	1.001 - 5.000	Acceso a leads pre-cualificados, visibilidad destacada. Requiere auditar 1 caso de éxito.
Master	> 5.000	100% visibilidad en marketplace, opción de co-autoría en contenidos. Élite que co-crea.

7.4 Catálogo de Redención (Spending)
•	Reducir comisión de venta del 5% al 2% durante un mes: 10.000 CR
•	Acceso a Masterclass exclusiva de Pepe Jaraba: 5.000 CR
•	Kit de Automatización Avanzada: 2.500 CR
•	Promoción en portada del Marketplace por 7 días: 3.500 CR

 
8. Centro de Operaciones Financieras (FOC)
El FOC transforma Drupal 11 de un CMS tradicional a un Data Warehouse Operativo, capaz de ingerir, procesar y visualizar datos financieros complejos provenientes de múltiples fuentes.
8.1 Métricas de Salud y Crecimiento (North Star)
Métrica	Fórmula / Definición	Benchmark 2025
MRR	Ingresos mensuales normalizados. New MRR + Expansion MRR - Churned MRR	Crecimiento 15-20% MoM early stage
ARR	MRR × 12. Solo ingresos recurrentes (excluir one-time fees)	YoY growth 27% (top performers)
Gross Margin	(Revenue - COGS) / Revenue × 100	70-85% (best-in-class 81%)
NRR	(Starting MRR + Expansion - Churn - Contraction) / Starting MRR × 100	> 100% (ideal 110-120%)
LTV:CAC	Customer Lifetime Value / Customer Acquisition Cost	≥ 3:1 (ideal 5:1)
Rule of 40	Revenue Growth Rate + Profit Margin	≥ 40% (SaaS saludable)

8.2 Métricas Específicas del Modelo Híbrido
Métrica Híbrida	Descripción y Uso
Grant Burn Rate	Velocidad de consumo de fondos de subvención vs. progreso de hitos. Alerta si % consumido > % tiempo transcurrido.
GMV (Marketplace)	Gross Merchandise Value: volumen bruto transaccionado en el marketplace. Indicador de actividad económica.
Application Fee Rate	Comisión efectiva capturada via Stripe Connect Destination Charges. Revenue real de la plataforma.
Tenant Margin	Margen neto por tenant individual: Ingresos Tenant - (COGS Directo + COGS Atribuido). Detecta noisy neighbors.
Tenant Health Score	Score compuesto: Usage frequency + Support sentiment + Contract renewal proximity + NPS.

8.3 Matriz de Alertas Financieras
Alerta	Trigger	Severidad	Acción ECA Automatizada
Churn Spike	> 5% mensual	CRÍTICA	Crear tarea urgente en CRM + Activar secuencia retención
LTV:CAC Comprimido	< 3:1	ADVERTENCIA	Alerta: "Revisar rendimiento campañas o pricing increase"
Gross Margin Drop	< 70%	CRÍTICA	Auditar COGS, review cost allocation, optimizar hosting
Grant Burn Rate	> tiempo	CRÍTICA	Alerta: "Desviación presupuestaria. Congelar no esenciales."
Runway Warning	< 12 meses	CRÍTICA	Iniciar proceso fundraising, acelerar revenue initiatives
NRR Below Target	< 100%	ADVERTENCIA	Focus expansion: trigger upsell campaigns, feature push

 
9. Hoja de Ruta de Implementación
Plan de implementación en 6 meses dividido en cuatro fases progresivas, desde los cimientos de datos hasta la automatización inteligente.
Fase	Timeline	Entregables
Fase 1	Meses 1-2	Cimientos: Entidades personalizadas (financial_transaction, cost_allocation). Conectores ETL Stripe + ActiveCampaign. Taxonomía Verticales. Estructura Groups para tenants.
Fase 2	Mes 3	Visualización: Módulos Charts + ECharts + Views Aggregator. Dashboard principal con Gin + Layout Builder. Validación datos (conciliación con bancos).
Fase 3	Meses 4-5	Inteligencia: Algoritmos de proyección PHP-ML. Configuración ECA para alertas. Sistema de reconocimiento de ingresos diferidos. Análisis por tenant.
Fase 4	Mes 6	Maduración: Playbooks automatizados completos. Modelado de escenarios. Dashboards por rol (Executive, Operations, Tactical). Documentación y training.

10. Valoración de Riesgos y Mitigación
Riesgo	Impacto	Mitigación Implementada
Dependencia Pública	ALTO	Triple Motor Económico para diversificar hacia ingresos privados (40%) y licencias (30%).
Complejidad SaaS	MEDIO	Arquitectura Drupal "Soft Multi-tenant" centralizada con Group Module.
Canibalización	MEDIO	Control territorial por contrato y CRM en el modelo de franquicias.
Invisibilidad IA	BAJO	Estrategia GEO nativa con Answer Capsules y JSON-LD.
Dependencia Pepe	MEDIO	Institucionalización del Método Jaraba y red de Master Consultants certificados.
Obsolescencia Contenido	MEDIO	Agente Revisor de Vigencia + atributos de fecha de caducidad en RAG.

11. Conclusión
El Plan Maestro Consolidado del Ecosistema Jaraba 2026 representa la fusión de visión estratégica, arquitectura técnica robusta y operativa comercial detallada. La plataforma está optimizada para:
•	Eficiencia Económica: Ahorro proyectado del 38% en TCO a 3 años frente a soluciones cerradas.
•	Escalabilidad Real: Soft Multi-tenancy con coste marginal cercano a cero por nuevo tenant.
•	Visibilidad IA: Estrategia GEO nativa que posiciona los productos del ecosistema en motores generativos.
•	Diversificación de Ingresos: Triple Motor Económico que reduce la dependencia de fondos públicos.
•	Verticales Comercializables: AgroConecta, ComercioConecta y ServiciosConecta listos para vender a entidades.

La integración de las verticales de empleo y empresa en un solo núcleo Drupal es la decisión más inteligente para fomentar el efecto red: las empresas creadas en la plataforma contratan talento formado en la plataforma, que a su vez consume productos de la plataforma. Un ciclo cerrado que ningún competidor puede replicar.

Documento Técnico Maestro - Versión Consolidada 3.0
JARABA IMPACT PLATFORM
Enero 2026

