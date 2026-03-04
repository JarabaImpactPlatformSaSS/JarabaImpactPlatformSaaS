



JARABA IMPACT PLATFORM
Plataforma de Ecosistemas Digitales S.L.



PLAN ESTRATEGICO DE LANZAMIENTO
Y CRECIMIENTO DEL SAAS

Analisis Estrategico Comparado | Plan Go-To-Market
Argumentario de Ventas | Mitigacion del Riesgo "Demasiado Ancho"


Version 1.0 | 3 de marzo de 2026
Codigo: 20260303c-Plan_Estrategico_Lanzamiento_SaaS_v1
Verificado contra: Estado del SaaS v1.0.0 (28-02-2026)
Filosofia Sin Humo: Solo datos reales, metricas verificables, acciones concretas
 
Indice de Contenidos
Indice de Contenidos	1
1. Resumen Ejecutivo	1
1.1 Cifras Clave del Estado Actual	1
1.2 Tesis Estrategica Central	1
2. Analisis de Mercado y Contexto Competitivo	1
2.1 Macro-Contexto del Mercado SaaS	1
2.2 Competidores Directos (Mercado Espanol)	1
2.3 Mapa de Posicionamiento Competitivo	1
3. Riesgo "Demasiado Ancho" - Diagnostico y Mitigacion	1
3.1 Diagnostico del Problema	1
3.2 Estrategia de Mitigacion: Submarinas con Periscopio	1
3.2.1 Arquitectura de Marca por Vertical	1
3.2.2 Regla del Periscopio	1
3.2.3 Implementacion Tecnica de la Separacion	1
4. Plan Go-To-Market: Expansion Concentrica	1
4.1 Modelo de Lanzamiento por Fases	1
4.2 Fase 0: Piloto Institucional (Meses 1-3)	1
4.2.1 Acciones Fase 0	1
4.2.2 Gate Criteria para pasar a Fase 1	1
4.3 Fase 1: Escala Institucional (Meses 3-6)	1
4.4 Fase 2: Comercial Andalucia (Meses 6-12)	1
4.5 Fase 3-4: Expansion Nacional e Internacional	1
5. Argumentario de Ventas Segmentado	1
5.1 Para Entidades de Empleo (PIIL / SAE / Desarrollo Local)	1
Argumentos Ordenados por Impacto	1
5.2 Para Productores Agroalimentarios (AgroConecta)	1
5.3 Para Comercio Local (ComercioConecta)	1
5.4 Para Profesionales de Servicios (ServiciosConecta)	1
5.5 Para Instituciones (Ayuntamientos, Diputaciones, GDR)	1
6. Estrategia de Precios	1
6.1 Arquitectura de Precios Actual (Verificada)	1
6.2 Add-ons Marketing (Precio Fijo, Cualquier Vertical)	1
6.3 Estrategia de Precios por Fase	1
Fase 0: Piloto Gratuito	1
Fase 1: Descuento Institucional	1
Fase 2: Comercial con Kit Digital	1
7. Proyecciones Financieras (Escenario Realista)	1
7.1 Modelo de Ingresos a 36 Meses	1
7.2 Estructura de Costes Estimada	1
8. Prioridades de Desarrollo de Producto	1
8.1 Modulos a Habilitar por Fase (de los 38 Inactivos)	1
8.2 Funcionalidades Criticas Faltantes (No Codificadas)	1
9. Estrategia de Marketing y Demand Generation	1
9.1 Content Marketing por Vertical	1
9.2 SEO y GEO (Ventaja Tecnica Existente)	1
9.3 Canal Institucional (Kit Digital / Kit Consulting)	1
10. Matriz de Riesgos y Contingencias	1
11. Dashboard de KPIs por Fase	1
11.1 KPIs Pre-PMF (Meses 1-6)	1
11.2 KPIs Post-PMF (Meses 6-18)	1
12. Plan de Accion: Proximos 90 Dias	1
12.1 Semanas 1-2: Cimientos	1
12.2 Semanas 3-4: Outreach Institucional	1
12.3 Semanas 5-8: MVP Development Sprint	1
12.4 Semanas 9-12: Soft Launch + Iteracion	1
13. Conclusiones y Principios de Ejecucion	1
13.1 Los 7 Principios de Ejecucion	1
13.2 Hitos Clave de Decision	1

 
1. Resumen Ejecutivo
Este documento presenta el plan estrategico integral para el lanzamiento y crecimiento de la Jaraba Impact Platform, un SaaS multi-vertical construido sobre Drupal 11 que sirve a PYMEs rurales espanolas en 10 verticales. El analisis parte del estado real verificado contra el codigo fuente (28-02-2026) y propone una estrategia concretizada para convertir una base tecnica solida en un negocio viable y escalable.

DIAGNOSTICO CRITICO
La plataforma tiene una base tecnica excepcional (712K lineas PHP, 95 modulos, 453 tests, 11 agentes IA Gen 2), pero esta en estado pre-revenue con 0 tenants, 0 ingresos y 0 usuarios reales. El mayor riesgo identificado es el sindrome "Demasiado Ancho": 10 verticales compitiendo por atencion generan confusion de comprador y dispersion de recursos. La estrategia central de este plan es la FOCALIZACION PROGRESIVA.

1.1 Cifras Clave del Estado Actual
Metrica	Valor	Evaluacion
Modulos custom	95 (57 habilitados, 38 inactivos)	Alto volumen, pendiente activacion
Lineas de codigo PHP	~712,000	Base tecnica madura
Tests automatizados	453 (367 Unit + 55 Kernel + 31 Func)	Cobertura solida
ContentEntity types	426	Modelo de datos complejo
Agentes IA Gen 2	11 operativos	Diferenciador competitivo
Verticales definidos	10 canonicos	RIESGO: excesiva amplitud
Tenants activos	0 (solo desarrollo)	PRE-LAUNCH
MRR actual	0 euros	Pre-revenue
Documentacion tecnica	306 especificaciones	Exhaustiva

1.2 Tesis Estrategica Central
El exito del lanzamiento no depende de la amplitud de la plataforma (que ya es excepcional), sino de la profundidad de validacion en un unico vertical. La estrategia se resume en:
•	FOCO: Lanzar solo Empleabilidad como vertical principal (validacion institucional via PIIL/Andalucia+ei).
•	VALIDACION: Conseguir 10-15 tenants piloto con datos reales antes de expandir.
•	EXPANSION: Activar verticales adicionales solo cuando el primero demuestre PMF (NRR >100%, churn <5%).
•	DISCIPLINA: Aplicar metricas kill-criteria: si no hay 20 tenants en mes 6, pivotar antes de escalar.
 
2. Analisis de Mercado y Contexto Competitivo
2.1 Macro-Contexto del Mercado SaaS
El mercado SaaS global se estima en $464.7B en 2026 con un CAGR del 18.8%. El vertical SaaS crece el doble que el horizontal SaaS, con especial dinamismo en legaltech y healthtech (+27% interanual). Las PYMEs representan el 38% de la adopcion global de SaaS, con CRM como la aplicacion dominante (33% del gasto).

Indicador	Valor 2025-2026	Relevancia Jaraba
Mercado SaaS Espana	~8,160M USD	Mercado domestico directo
CAGR SaaS Espana	19% (2024-2029)	Crecimiento sostenido
Kit Digital ejecutado	880,000+ bonos concedidos	Canal de adquisicion validado
Kit Digital remanentes	Orden TDF/39/2026 publicada	Segunda oportunidad fondos
SaaS vertical crecimiento	2x vs horizontal	Alineado con modelo Jaraba
IA embebida en SaaS	26% de plataformas (2025)	Jaraba ya tiene 11 agentes
Vertical legaltech/healthtech	+27% YoY	JarabaLex posicionado

2.2 Competidores Directos (Mercado Espanol)
El ecosistema SaaS espanol tiene actores consolidados pero ninguno cubre el nicho de Jaraba: plataforma multi-vertical de impacto social para territorio rural. Esta es la oportunidad, pero tambien el riesgo de competir en multiples frentes.

Competidor	Vertical	Pricing	Fortaleza	Debilidad vs Jaraba
Holded	Gestion empresarial	Desde 39,50 EUR/mes	>100K usuarios, marca consolidada	No tiene empleo, ni marketplace, ni IA vertical
Factorial	RRHH	Desde 5 EUR/empl/mes	Unicornio espanol, >$1B valoracion	Solo RRHH, no cubre emprendimiento ni comercio
Quipu	Contabilidad	Desde 12 EUR/mes	Especifico para autonomos	Limitado a facturacion/contabilidad
Shopify	E-commerce	Desde 36 EUR/mes	Lider global, ecosistema enorme	Sin empleo/formacion, comisiones altas
Agroptima	Agro gestion	Desde 15 EUR/mes	Especifico agricultura espanola	Solo gestion de finca, no marketplace
Typeform	Formularios/encuestas	Desde 21 EUR/mes	UX excepcional, marca global	Sin verticalizacion, solo herramienta
Signaturit	Firma digital	Custom pricing	Lider firma digital Espana	Producto puntual, no plataforma

2.3 Mapa de Posicionamiento Competitivo
Jaraba Impact Platform ocupa un espacio unico en la interseccion de cuatro dimensiones que ningun competidor combina:
•	1. Multi-vertical integrado: Empleo + Emprendimiento + Comercio + Agro + Servicios + Legal en una sola plataforma con datos cruzados.
•	2. IA nativa por vertical: 11 agentes Gen 2 especializados (SmartEmployabilityCopilot, ProducerCopilot, LegalCopilot...), no IA generica.
•	3. Triple Motor Economico: Combina B2G (institucional), B2B (mercado privado) y B2B (licencias/franquicias). Ningun competidor espanol tiene este modelo.
•	4. DNA territorial rural: Construido para entornos con baja conectividad, sesgo rural, y compliance institucional espanol (SEPE, PIIL, FSE).

VENTAJA COMPETITIVA DEFENSIBLE
La combinacion de 30+ anos de capital relacional institucional (ventaja no replicable), IA vertical nativa (11 agentes), y arquitectura multi-tenant Drupal (soft isolation via Group Module) crea una barrera de entrada significativa. Un competidor necesitaria construir tanto la tecnologia como las relaciones institucionales, lo que requiere anos, no meses.
 
3. Riesgo "Demasiado Ancho" - Diagnostico y Mitigacion
3.1 Diagnostico del Problema
Con 10 verticales canonicos, 426 ContentEntities y 95 modulos, la plataforma presenta un riesgo critico de confusion de comprador. Un productor agroalimentario que busca vender online no entiende por que necesita una plataforma que tambien gestiona casos legales y programas de empleo. El exceso de oferta genera:

Sintoma	Impacto	Probabilidad
Confusion de propuesta de valor	El comprador no entiende que resuelve Jaraba para EL	ALTA
Paralis por analisis	Demasiadas opciones retrasan la decision de compra	ALTA
Percepcion de "maestro de nada"	El comprador duda de la profundidad en SU vertical	MEDIA-ALTA
Coste de soporte elevado	Equipo de CS debe dominar 10 verticales simultaneamente	ALTA
Dispersion de desarrollo	38 modulos inactivos compiten por recursos de habilitacion	MEDIA
Marca difusa	Marketing intenta comunicar 10 propuestas de valor a la vez	ALTA

3.2 Estrategia de Mitigacion: Submarinas con Periscopio
La estrategia para mitigar el riesgo "Demasiado Ancho" se llama "Submarinas con Periscopio": cada vertical se presenta como un producto independiente con su propia marca, landing page y argumentario. La plataforma subyacente es invisible para el comprador inicial.

3.2.1 Arquitectura de Marca por Vertical
Vertical	Submarca	Propuesta 1 Linea	Buyer Persona
Empleabilidad	Jaraba Emplea	Gestiona itinerarios de empleo con IA y cumplimiento SEPE	Entidades PIIL, agencias empleo, SAE
Emprendimiento	Jaraba Emprende	Acompanamiento digital para emprendedores con mentor IA	Consultores, CEDETs, CADE, camaras
AgroConecta	AgroConecta	Marketplace directo del productor al consumidor con trazabilidad	Cooperativas, productores agroalimentarios
ComercioConecta	ComercioConecta	Tienda online + QR dinamico para comercio local	Comercios de proximidad, barrio
ServiciosConecta	ServiciosConecta	Agenda, reservas y firma digital para profesionales	Fontaneros, abogados, consultores
JarabaLex	JarabaLex	Gestion de despacho legal con IA integrada y LexNet	Abogados, procuradores, asesores

3.2.2 Regla del Periscopio
El comprador solo ve SU vertical. La plataforma integrada solo se revela cuando el comprador pregunta o cuando existe oportunidad de cross-sell. El orden de revelacion es:
•	Nivel 1 (Landing): Solo el vertical relevante. "Jaraba Emplea es la plataforma de empleo con IA para entidades de desarrollo local."
•	Nivel 2 (Demo/Trial): Muestra funcionalidades del vertical + menciona add-ons de marketing. "Ademas de empleo, puedes anadir CRM y email marketing."
•	Nivel 3 (Expansion): Revela el ecosistema. "Tus candidatos formados pueden conectar con emprendimientos y comercios locales del mismo territorio."
•	Nivel 4 (Enterprise/Institucional): Presenta la vision completa. "Una sola plataforma para todo el desarrollo territorial: empleo, emprendimiento, comercio y servicios."

3.2.3 Implementacion Tecnica de la Separacion
La arquitectura multi-tenant con Group Module ya soporta esta estrategia. Cada tenant solo ve las entidades y menus de SU vertical. Implementacion requerida:
•	Dominios por vertical: emplea.jarabaimpact.com, agro.jarabaimpact.com, lex.jarabaimpact.com
•	Landing pages independientes por vertical (Page Builder GrapesJS con presets de industria)
•	Onboarding especifico por vertical (jaraba_onboarding ya habilitado, configurar flows)
•	Menus y dashboards filtrados por vertical (TenantContextService + vertical config)
•	Emails y comunicaciones con branding del sub-vertical, no de la plataforma global
 
4. Plan Go-To-Market: Expansion Concentrica
4.1 Modelo de Lanzamiento por Fases
El lanzamiento sigue un modelo de expansion concentrica: desde el nucleo institucional andaluz hacia el mercado comercial espanol. Cada fase tiene criterios de paso (gate criteria) que deben cumplirse antes de avanzar.

Fase	Timeline	Objetivo	Vertical	Tenants Target	MRR Target
F0: Piloto Institucional	Meses 1-3	Validar con usuarios reales	Empleabilidad	5-10	250-750 EUR
F1: Escala Institucional	Meses 3-6	Credibilidad + casos de uso	Empleab + Emprend	20-30	1,500-2,500 EUR
F2: Comercial Andalucia	Meses 6-12	Validar canal privado + PMF	+Agro, +Comercio	50-80	4,000-6,000 EUR
F3: Espana Nacional	Meses 12-18	Expansion geografica	Todos activos	150+	12,000-18,000 EUR
F4: Internacional	Meses 18-30	Solo si PMF demostrado	Verticales validados	300+	30,000+ EUR

4.2 Fase 0: Piloto Institucional (Meses 1-3)
POR QUE INSTITUCIONAL PRIMERO
El canal institucional (PIIL, Andalucia+ei) ofrece 3 ventajas criticas para un SaaS pre-revenue: (1) CAC cercano a cero porque se accede por relacion, no por publicidad; (2) usuarios cautivos que DEBEN usar la herramienta del programa; (3) credibilidad que luego se transfiere al canal comercial ("usado por entidades del SAE").

4.2.1 Acciones Fase 0
•	Semana 1-2: Definir MVP minimo de Empleabilidad Starter. Solo: registro usuarios, LMS basico (5 cursos), Job Board (3 ofertas activas), dashboard admin con metricas PIIL.
•	Semana 2-3: Contactar 10 entidades PIIL de la red existente. Oferta: acceso gratuito 3 meses a cambio de feedback, datos de uso y caso de estudio.
•	Semana 3-4: Firmar LOIs con 3-5 entidades piloto. Definir metricas de exito conjuntas (activacion >40%, uso semanal, NPS).
•	Semana 5-8: Sprint de desarrollo MVP. Prioridad: habilitar jaraba_support + jaraba_notifications para soporte real.
•	Semana 9-12: Soft launch con pilotos. Monitoring diario. Iteracion rapida sobre feedback.

4.2.2 Gate Criteria para pasar a Fase 1
Metrica	Umbral Minimo	Ideal	Kill Criteria
Activacion (complete onboarding)	>40%	>60%	<20% = pivotar
Retencion D30 (login en ultimos 30 dias)	>25%	>40%	<15% = pivotar
NPS	>30	>50	<10 = pivotar
Tiempo hasta primer valor	<30 min	<15 min	>60 min = redisenar
Tickets soporte/semana/tenant	<5	<2	>10 = UX roto
Entidades piloto activas	3+	5+	<2 = revaluar

4.3 Fase 1: Escala Institucional (Meses 3-6)
Con pilotos validados, expandir a mas entidades institucionales y activar el vertical de Emprendimiento.
•	Canal: Red de entidades PIIL (8 provincias andaluzas), CADE, Andalucia Emprende, asociaciones de desarrollo local.
•	Producto: Empleabilidad Pro (EUR 79/mes) + Emprendimiento Starter (EUR 29/mes). Add-ons: jaraba_email, jaraba_crm.
•	Equipo: Pepe (ventas institucionales) + 1 developer senior + 1 soporte/CS.
•	Marketing: Casos de estudio de pilotos. Webinar mensual: "Como digitalizar programas PIIL". Presencia en eventos Andalucia Emprende.

4.4 Fase 2: Comercial Andalucia (Meses 6-12)
Con PMF validado en canal institucional, abrir canal comercial. Activar AgroConecta y ComercioConecta.
•	Canal: Kit Digital (como agente digitalizador adherido o via partners), outbound directo, content marketing, SEO.
•	Producto: 4 verticales con planes Starter-Pro-Enterprise. Marketing bundles. Prueba gratuita 30 dias.
•	Equipo: +1 inside sales rep + 1 marketing content.
•	Pricing Kit Digital: Paquetes alineados con categorias Kit Digital (presencia web, e-commerce, gestion procesos, IA). Bono digital cubre 100% del Starter anual.

4.5 Fase 3-4: Expansion Nacional e Internacional
Solo se ejecutan si las metricas de Fase 2 se cumplen: NRR >100%, churn mensual <5%, LTV:CAC >3:1, al menos 50 tenants activos.
•	Fase 3 (Meses 12-18): Expansion a Cataluna, Madrid, Valencia via channel partners. White-label para asociaciones empresariales. Objetivo: 150+ tenants.
•	Fase 4 (Meses 18-30): Solo si Espana validada. Piloto en Portugal (proximidad linguistica/cultural). LATAM solo con socio local. Objetivo: 300+ tenants.
 
5. Argumentario de Ventas Segmentado
5.1 Para Entidades de Empleo (PIIL / SAE / Desarrollo Local)
PROPUESTA DE VALOR
"Jaraba Emplea digitaliza tus itinerarios de insercion laboral con IA, cumpliendo los requisitos SEPE de teleformacion, y genera automaticamente los informes de impacto que necesitas para justificar la subvencion."

Argumentos Ordenados por Impacto
•	Ahorro de tiempo: "El matching IA entre candidatos y ofertas reduce el tiempo de colocacion. Tu equipo tecnico dedica menos horas a busqueda manual y mas a acompanamiento."
•	Compliance automatico: "Genera informes STO compatibles con el Servicio Telematico de Orientacion del SAE. Exporta datos de participantes, acciones y fases (Atencion/Insercion) sin entrada manual duplicada."
•	Formacion integrada: "LMS con cursos, lecciones y certificaciones Open Badge 3.0. Cumple las 10 horas minimas de orientacion y 50 horas de formacion por participante que exige PIIL."
•	Coste competitivo: "EUR 79/mes plan Pro vs EUR 3,000+/mes de plataformas enterprise equivalentes. Con Kit Digital/Kit Consulting, el coste puede ser EUR 0 el primer ano."
•	Datos de impacto: "Dashboard de metricas PIIL: participantes atendidos, inserciones laborales logradas, certificaciones emitidas. Exportable para justificacion FSE."

5.2 Para Productores Agroalimentarios (AgroConecta)
PROPUESTA DE VALOR
"AgroConecta te conecta directamente con compradores, eliminando intermediarios. Cada lote tiene trazabilidad completa con QR verificable que aumenta la confianza del consumidor."

•	Margen directo: "Vende directo al consumidor/hosteleria. Comision 8% vs 15-25% de marketplaces generalistas. Aumenta tu margen un 30-50%."
•	Trazabilidad diferenciadora: "Cada lote con QR que muestra origen, fecha, certificaciones y cadena de custodia. El consumidor premium paga mas por transparencia."
•	Visibilidad digital: "Tu perfil de productor optimizado para SEO y GEO. Apareces en busquedas locales y en respuestas de IA (ChatGPT, Perplexity)."
•	Gestion integrada: "Pedidos, envios, pagos y comunicacion con clientes en una sola plataforma. Sin saltar entre 5 herramientas."
•	Precio accesible: "Starter EUR 39/mes. 30 dias gratis. Elegible para Kit Digital."

5.3 Para Comercio Local (ComercioConecta)
PROPUESTA DE VALOR
"ComercioConecta pone tu comercio local online en 24 horas con tienda, QR dinamico para ofertas flash, y gestion omnicanal."

•	Rapidez: "Tienda online profesional en 24 horas con el constructor de paginas integrado. Sin necesidad de conocimientos tecnicos."
•	QR inteligente: "QR dinamicos en tu escaparate que puedes actualizar desde el movil. Ofertas flash, horarios, carta del dia... sin reimprimir nada."
•	Omnicanal: "Publica productos automaticamente en redes sociales. Recibe pedidos online y en tienda desde el mismo panel."
•	Fidelizacion: "Programa de recompensas para clientes recurrentes incluido en plan Pro."
•	Precio vs Shopify: "EUR 29/mes (Starter) vs EUR 36/mes (Shopify Basic). Misma funcionalidad core, soporte en espanol, comision menor."

5.4 Para Profesionales de Servicios (ServiciosConecta)
PROPUESTA DE VALOR
"ServiciosConecta es tu oficina digital: agenda online, firma digital legal, videollamadas y facturacion automatica en una sola herramienta."

•	Agenda inteligente: "Reservas online sincronizadas con Google/Outlook. Evita dobles reservas y llamadas perdidas."
•	Firma digital legal: "Firma PAdES incluida en plan Pro. Contratos y presupuestos legalmente validos con un clic."
•	Presupuestos con IA: "Describe el trabajo y la IA genera un presupuesto profesional en minutos. Personalizable con tu marca."
•	Todo integrado: "Sin pagar Zoom + Calendly + HelloSign + Holded por separado. Todo en EUR 79/mes."

5.5 Para Instituciones (Ayuntamientos, Diputaciones, GDR)
PROPUESTA DE VALOR
"Jaraba Impact Platform es la plataforma marca blanca para el desarrollo territorial digital: empleo, emprendimiento, comercio y servicios en una sola licencia. Mide el impacto con datos reales."

•	Marca blanca: "Tu identidad visual, tu dominio, nuestra tecnologia. El ciudadano nunca ve 'Jaraba', ve el sello de tu institucion."
•	Multi-programa: "Una sola plataforma para programas de empleo, emprendimiento, comercio local y servicios. Sin contratar 4 proveedores."
•	Impacto medible: "Dashboard de impacto social: empleos generados, negocios creados, GMV del comercio local, formaciones completadas."
•	Compliance: "Cumplimiento GDPR, accesibilidad, y alineacion con criterios PIIL/FSE para justificacion de subvenciones."
•	Modelo licencia: "Licencia anual institucional con usuarios ilimitados. Soporte dedicado y formacion para personal municipal."
 
6. Estrategia de Precios
6.1 Arquitectura de Precios Actual (Verificada)
Vertical	Starter	Pro	Enterprise
Empleabilidad	29 EUR/mes	79 EUR/mes	149 EUR/mes
Emprendimiento	29 EUR/mes	89 EUR/mes	169 EUR/mes
AgroConecta	39 EUR/mes	99 EUR/mes	199 EUR/mes
ComercioConecta	29 EUR/mes	79 EUR/mes	149 EUR/mes
ServiciosConecta	29 EUR/mes	79 EUR/mes	149 EUR/mes

6.2 Add-ons Marketing (Precio Fijo, Cualquier Vertical)
Add-on	Precio	Funcion
jaraba_crm	19 EUR/mes	CRM basico: contactos, pipeline, actividades
jaraba_email	29 EUR/mes (5K emails)	Email marketing con templates y automatizacion
jaraba_email Plus	59 EUR/mes (25K emails)	Email marketing avanzado
jaraba_social	25 EUR/mes	Publicacion automatica en redes sociales
events_webinars	19 EUR/mes	Gestion de eventos y webinars
ab_testing	15 EUR/mes	A/B testing para paginas y emails
referral_program	19 EUR/mes	Programa de referidos con recompensas
Marketing Starter Bundle	35 EUR/mes (ahorro 30%)	CRM + Email basico
Marketing Pro Bundle	59 EUR/mes (ahorro 30%)	CRM + Email + Social
Marketing Complete Bundle	99 EUR/mes (ahorro 30%)	Todos los add-ons

6.3 Estrategia de Precios por Fase
Fase 0: Piloto Gratuito
•	Acceso gratuito 3 meses para entidades piloto PIIL
•	Condicion: LOI firmada, compromiso de feedback semanal, caso de estudio publicable
•	Post-piloto: transicion a plan Pro con 50% descuento primer ano (EUR 39,50/mes)

Fase 1: Descuento Institucional
•	Plan Pro anual con 17% descuento (2 meses gratis): EUR 79 x 10 = EUR 790/ano
•	Descuento adicional 30% para ONGs/entidades sin animo de lucro primer ano
•	Licencia institucional ilimitada: pricing personalizado (desde EUR 2,400/ano)

Fase 2: Comercial con Kit Digital
•	Starter anual (EUR 348) cubierto 100% por Kit Digital Segmento III (EUR 3,000 bono)
•	Pro anual (EUR 948) + Marketing Starter (EUR 420) = EUR 1,368, cubierto por Kit Digital Segmentos I-II
•	El cliente percibe coste EUR 0 el primer ano = reduccion radical de barrera de entrada
 
7. Proyecciones Financieras (Escenario Realista)
7.1 Modelo de Ingresos a 36 Meses
Metrica	Ano 1 (M12)	Ano 2 (M24)	Ano 3 (M36)
Tenants activos	80	200	400
ARPU mensual	65 EUR	90 EUR	120 EUR
MRR	5,200 EUR	18,000 EUR	48,000 EUR
ARR	62,400 EUR	216,000 EUR	576,000 EUR
Churn mensual	6% (aprendizaje)	4% (mejorando)	3% (maduro)
NRR	95% (neto negativo)	105% (expansion)	112% (saludable)
CAC promedio	250 EUR (institucional)	400 EUR (mixto)	500 EUR (comercial)
LTV (36m, churn estimado)	975 EUR	2,070 EUR	3,840 EUR
LTV:CAC	3.9:1	5.2:1	7.7:1
Gross Margin	72%	78%	82%

7.2 Estructura de Costes Estimada
Categoria	Ano 1	Ano 2	Ano 3
Desarrollo (3,500-4,500h)	108,000 EUR	72,000 EUR	54,000 EUR
Infraestructura (IONOS + servicios)	12,000 EUR	18,000 EUR	24,000 EUR
Salarios (equipo)	36,000 EUR	96,000 EUR	168,000 EUR
Marketing	6,000 EUR	24,000 EUR	48,000 EUR
Operaciones (legal, contable)	12,000 EUR	15,000 EUR	18,000 EUR
Total Gastos	174,000 EUR	225,000 EUR	312,000 EUR
Ingresos estimados	42,000 EUR	162,000 EUR	432,000 EUR
Resultado neto	-132,000 EUR	-63,000 EUR	+120,000 EUR

Break-even estimado: Mes 20-22 del lanzamiento (Q4 2027), asumiendo crecimiento sostenido de MRR 12-15% MoM y control de costes.

NOTA FINANCIERA CRITICA
Estas proyecciones asumen ejecucion sin errores significativos. La realidad historica muestra que los SaaS pre-revenue tardan 2-3x mas en alcanzar las metricas proyectadas. Recomendacion: mantener reservas de tesoreria para 18 meses de runway y no contratar agresivamente hasta validar PMF (Fase 2 completada).
 
8. Prioridades de Desarrollo de Producto
8.1 Modulos a Habilitar por Fase (de los 38 Inactivos)
Prioridad	Modulo	Fase	Justificacion
P0	jaraba_support	F0	Soporte a pilotos es critico para retencion
P0	jaraba_sla	F0	SLA credible para clientes institucionales
P0	jaraba_notifications	F0	Push/in-app para engagement
P1	jaraba_pwa	F1	Acceso movil esencial en zonas rurales
P1	jaraba_legal	F1	Base legal (ToS, AUP) para compliance comercial
P1	jaraba_privacy	F1	GDPR compliance demostrable
P2	jaraba_social_commerce	F2	Potencia AgroConecta y ComercioConecta
P2	jaraba_verifactu	F2	Obligacion fiscal espanola (factura electronica)
P2	jaraba_sso	F2	Login simplificado para clientes enterprise
P3	jaraba_whitelabel	F3	Habilitador del Motor de Licencias
P3	jaraba_messaging	F3	Comunicacion in-platform
P3	jaraba_funding	F3	Conectar clientes con subvenciones disponibles
Aplazar	jaraba_zkp	Post-PMF	Zero-knowledge proofs: no critico ahora
Aplazar	jaraba_multiregion	Post-PMF	Solo relevante para internacionalizacion
Aplazar	jaraba_mobile	Post-PMF	PWA es suficiente fase inicial

8.2 Funcionalidades Criticas Faltantes (No Codificadas)
Gap	Impacto	Solucion Propuesta	Esfuerzo
Dashboard cross-vertical admin	ALTO: admin no tiene vista unificada	Nuevo modulo jaraba_admin_dashboard	120-160h
Onboarding product-led	CRITICO: 40-60% abandono sin onboarding	Configurar jaraba_onboarding por vertical	80-120h
API publica documentada	MEDIO: bloquea integraciones terceros	OpenAPI/Swagger sobre rutas existentes	60-80h
Webhooks outbound por tenant	MEDIO: limita automatizacion cliente	Nuevo servicio en ecosistema_jaraba_core	40-60h
Health scoring clientes	ALTO: CS reactivo sin datos	Ampliar jaraba_customer_success con scoring	80-100h
 
9. Estrategia de Marketing y Demand Generation
9.1 Content Marketing por Vertical
Cada vertical necesita su propio funnel de contenido. El Content Hub (jaraba_content_hub) ya soporta categorias, autores y generacion IA. Estrategia por vertical:

Vertical	Keywords SEO Target	Contenido Mes 1-6	Canal Principal
Empleabilidad	software gestion empleo, plataforma SEPE, itinerarios insercion	8 articulos + 2 casos estudio + 3 webinars	LinkedIn + Eventos SAE
AgroConecta	marketplace agricola, venta directa productor, trazabilidad	6 articulos + 1 video demo + 2 guias	Instagram + Ferias agro
ComercioConecta	tienda online comercio local, QR dinamico, Kit Digital	6 articulos + 3 videos tutoriales	Facebook + WhatsApp Business
ServiciosConecta	agenda online profesional, firma digital, facturacion	4 articulos + 2 guias PDF	Google Ads + Directorios
JarabaLex	software gestion despacho, abogado digital, LexNet	4 articulos + 1 whitepaper	LinkedIn + Colegios abogados

9.2 SEO y GEO (Ventaja Tecnica Existente)
La plataforma ya tiene implementada arquitectura GEO avanzada: SSR, JSON-LD (FAQPage, QAPage, LocalBusiness, Product), Answer Capsules optimizadas para crawlers IA, canonical URLs y sitemap. Esta ventaja tecnica debe activarse con contenido real:
•	Crear 50+ paginas de Answer Capsules por vertical optimizadas para AI Overviews de Google
•	Implementar JSON-LD LocalBusiness para cada tenant con GeoCoordinates
•	Publicar FAQ estructuradas (FAQPage schema) por cada vertical
•	Monitorizar apariciones en Perplexity, ChatGPT y Google AI Overviews

9.3 Canal Institucional (Kit Digital / Kit Consulting)
El programa Kit Digital ha cerrado sus convocatorias principales, pero la Orden TDF/39/2026 redistribuye fondos remanentes. Estrategia:
•	Corto plazo: Registrarse como Agente Digitalizador adherido para solicitudes remanentes.
•	Kit Consulting: Ofrecer paquetes de consultoria digital (12,000-24,000 EUR) que incluyan implementacion de la plataforma.
•	Oficinas Acelera Pyme: Colaborar con las 29+ millones EUR en presupuesto para impulsar digitalizacion PYMEs.
 
10. Matriz de Riesgos y Contingencias

Riesgo	Prob.	Impacto	Mitigacion	Contingencia
No alcanzar PMF	ALTA	CRITICO	Focus en 1 vertical, kill criteria claros	Pivotar vertical o modelo en M6
Demasiado Ancho confunde	ALTA	ALTO	Submarinas con Periscopio, submarcas	Desactivar verticales no validados
Churn >7% mensual	MEDIA	ALTO	Onboarding obsesivo, CS proactivo	Simplificar UX, reducir scope
Retraso desarrollo	MEDIA	ALTO	MVP estricto, priorizar modulos P0	Externalizar modulos no-core
Dependencia institucional	MEDIA	MEDIO	Diversificar a comercial en F2	Acelerar canal Kit Digital
Competidor entra en nicho	BAJA	MEDIO	Moverse rapido, lock-in datos	Profundizar diferenciacion IA
Kit Digital no renueva	MEDIA	MEDIO	No depender >30% de Kit Digital	Pivot a pricing directo
Equipo insuficiente	ALTA	ALTO	Contratar developer senior M1	Freelance Drupal senior pool
 
11. Dashboard de KPIs por Fase
11.1 KPIs Pre-PMF (Meses 1-6)
KPI	Formula	Objetivo	Frecuencia
Activation Rate	Usuarios que completan onboarding / Total registros	>40%	Semanal
D7 Retention	Usuarios activos dia 7 / Nuevos registros	>50%	Semanal
D30 Retention	Usuarios activos dia 30 / Nuevos registros	>25%	Mensual
Time-to-Value	Tiempo hasta primera accion de valor	<15 min	Continuo
NPS	Encuesta estandar Net Promoter Score	>40	Mensual
Feature Adoption	Usuarios que usan 3+ features core	>60%	Mensual
Support CSAT	Satisfaccion post-ticket soporte	>4.0/5	Por ticket

11.2 KPIs Post-PMF (Meses 6-18)
KPI	Formula	Objetivo	Alarma
MRR Growth	MRR este mes / MRR mes anterior - 1	>12% MoM	<8% MoM
Monthly Churn	Tenants perdidos / Total inicio mes	<5%	>7%
ARPU	MRR / Tenants activos	>75 EUR	<50 EUR
CAC Payback	CAC / (ARPU x Gross Margin)	<12 meses	>18 meses
NRR	(MRR inicio + expansion - contraccion - churn) / MRR inicio	>100%	<90%
LTV:CAC	LTV estimado / CAC promedio	>3:1	<2:1
Organic Signup %	Signups organicos / Total signups	>20%	<10%
 
12. Plan de Accion: Proximos 90 Dias

PRINCIPIO RECTOR
Cada semana debe producir algo demostrable a un potencial cliente. No hay semana "solo de planificacion". Desarrollo, ventas y validacion ocurren EN PARALELO, no en secuencia.

12.1 Semanas 1-2: Cimientos
Accion	Responsable	Entregable	Done When
Definir MVP Empleabilidad Starter	Pepe + Tecnico	Documento 1 pagina con scope cerrado	Features listadas y priorizadas
Habilitar jaraba_support + jaraba_sla	Developer	Modulos activos en staging	Tests pasan, tickets creables
Habilitar jaraba_notifications	Developer	Notificaciones email funcionando	Email de bienvenida se envia
Crear landing emplea.jarabaimpact.com	Developer	Pagina live con CTA claro	Formulario de interes operativo
Listar 10 entidades PIIL target	Pepe	Lista con contacto y estado relacion	Min 10 entidades identificadas
Preparar pitch deck honesto	Pepe	PPT 10 slides, sin inflacion	Revisado por persona externa

12.2 Semanas 3-4: Outreach Institucional
Accion	Responsable	Entregable	Done When
Llamar a 10 entidades PIIL	Pepe	Min 5 conversaciones realizadas	3+ interesados en piloto
Enviar propuesta piloto formal	Pepe	Email + 1-pager por entidad	Propuestas enviadas
Configurar tenant piloto en staging	Developer	Tenant demo con datos reales	Login funciona, LMS visible
Firmar LOIs piloto	Pepe	Min 3 LOIs firmadas	Documentos escaneados
Iniciar Sprint 1 MVP	Developer	Registro + Perfil candidato + LMS basico	Usuario puede registrarse y ver cursos

12.3 Semanas 5-8: MVP Development Sprint
Accion	Responsable	Entregable	Done When
Sprint 2: Job Board + Matching basico	Developer	Publicar ofertas, aplicar, filtrar	3 ofertas publicables y buscables
Sprint 3: Dashboard admin PIIL	Developer	Panel con metricas basicas	Exportar CSV de participantes
Crear 3 cursos demo en LMS	Pepe + Contenido	Cursos con H5P (video+quiz)	Curso completable end-to-end
Configurar Stripe Billing	Developer	Planes Starter/Pro creados	Pago de prueba exitoso
Internal QA + bug fix	Equipo	0 bugs criticos en core flow	Smoke test pasa al 100%
Crear materiales onboarding	Pepe	Video 3min + checklist PDF	Revisado y publicado

12.4 Semanas 9-12: Soft Launch + Iteracion
Accion	Responsable	Entregable	Done When
Onboarding primer piloto (1 entidad)	Pepe + Dev	Entidad usando la plataforma	Min 10 usuarios registrados
Monitor diario de uso	Pepe	Hoja tracking activacion/retencion	Datos recogidos cada dia
Feedback semanal con piloto	Pepe	Call 30min + notas estructuradas	Issues priorizados en backlog
Onboarding pilotos 2-4	Equipo	3 entidades mas activas	30+ usuarios totales
Iterar sobre feedback critico	Developer	Fixes deployed en <48h	Issues criticos resueltos
Preparar informe Fase 0	Pepe	Doc con metricas + decision Go/No-Go	Gate criteria evaluados
 
13. Conclusiones y Principios de Ejecucion

EL MANTRA DEL LANZAMIENTO
Un vertical perfecto vale mas que diez verticales mediocres. La plataforma ya existe; lo que falta es DEMOSTRAR que resuelve un problema real para una persona real que paga dinero real.

13.1 Los 7 Principios de Ejecucion

•	1. FOCO ANTES QUE AMPLITUD: Lanzar solo Empleabilidad. Los otros 9 verticales esperan hasta que el primero demuestre PMF con metricas verificables. Sin excepciones.
•	2. INSTITUCIONAL PRIMERO: El capital relacional de 30+ anos es la ventaja competitiva mas fuerte. Monetizarla con pilotos PIIL antes de invertir un euro en publicidad.
•	3. SUBMARINAS CON PERISCOPIO: Cada vertical se presenta como producto independiente con su propia submarca. La plataforma integrada solo se revela cuando anade valor al comprador especifico.
•	4. METRICAS, NO OPINIONES: Activation >40%, D30 >25%, NPS >40, churn <5%. Si no se cumplen, iterar. Si tras 2 iteraciones siguen sin cumplirse, pivotar. Kill criteria definidos y respetados.
•	5. VELOCIDAD DE ITERACION: Ciclos semanales de feedback-implementacion-medicion. Un bug critico se arregla en <48h. Una feature request validada se implementa en <2 semanas.
•	6. HONESTIDAD COMERCIAL: Sin humo en las ventas. No prometer ISO 27001 si no se tiene. No decir 'clase mundial 5/5' sin benchmarks. Los clientes institucionales verifican, y la credibilidad es irreparable.
•	7. BOOTSTRAP HASTA PMF: No buscar financiacion externa hasta tener al menos 50 tenants pagando, NRR >100% y churn <5%. La financiacion debe acelerar crecimiento validado, no financiar experimentacion.

13.2 Hitos Clave de Decision
Hito	Cuando	Decision
3 pilotos activos con datos reales	Mes 3	Go/No-Go para Fase 1
20 tenants, NPS >30, activation >40%	Mes 6	Go/No-Go para Fase 2 (comercial)
50 tenants, churn <5%, MRR >4,000 EUR	Mes 12	Go/No-Go para expansion nacional
NRR >100%, LTV:CAC >3:1	Mes 18	Evaluar fundraising o seguir bootstrap
150+ tenants en 3+ verticales	Mes 24	Evaluar internacionalizacion


Sin Humo. Solo datos, metricas y acciones.
Plataforma de Ecosistemas Digitales S.L. | Jaraba Impact Platform | 2026
