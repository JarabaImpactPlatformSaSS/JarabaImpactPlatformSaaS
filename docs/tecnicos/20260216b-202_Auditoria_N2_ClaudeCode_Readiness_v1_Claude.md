
AUDITORIA CLAUDE CODE READINESS
Documentacion Nivel 2 - Growth Ready (186-193)
Nivel de Madurez: N2 - Growth Ready
8 documentos | 460-590h estimadas | Analisis de implementabilidad
Version:	1.0
Fecha:	Febrero 2026
Codigo:	202_Auditoria_N2_ClaudeCode_Readiness_v1
Docs Auditados:	186, 187, 188, 189, 190, 191, 192, 193
Score Global N2:	15.6% Claude Code Ready
Hallazgo Clave:	Heterogeneidad tecnologica requiere framework adaptado
 
1. Observacion Critica: N2 No Es Homogeneo

A diferencia del Nivel 1 (3 modulos Drupal puros), el Nivel 2 contiene 8 documentos con 4 arquetipos tecnologicos distintos. El framework de auditoria debe adaptarse:

Arquetipo	Docs	Stack Principal	Framework Auditoria
A. Modulo Drupal Puro	186, 188, 190, 191, 192	PHP + Drupal 11	12 componentes estandar (C1-C12)
B. Hibrido Drupal + Python	189	PHP wrapper + Python ML	C1-C12 + scripts Python + modelo ML
C. Mobile Nativo	187	Capacitor + React + Drupal API	Reducido: C3, C4 (API) + mobile config
D. SDK/Framework	193	PHP SDK + Docker sandbox	C1-C4 + SDK boilerplate + CI pipeline

Implicacion: No todos los docs N2 necesitan los mismos 12 componentes. El doc 187 (Mobile) no necesita routing.yml Drupal porque su logica esta en React/Capacitor. El doc 189 necesita scripts Python ademas de los componentes Drupal. La auditoria usa un framework adaptado por arquetipo.
 
2. Framework de Evaluacion Adaptado

2.1 Arquetipo A: Modulo Drupal Puro (186, 188, 190, 191, 192)
Mismo framework que N1 - los 12 componentes C1-C12.

2.2 Arquetipo B: Hibrido Drupal + Python (189)
12 componentes Drupal + 4 componentes adicionales:
•	C13: Python scripts con requirements.txt y entry points
•	C14: Modelo ML (algoritmo, features, training pipeline)
•	C15: Cron/scheduler para re-entrenamiento
•	C16: PHP wrapper service para invocar Python

2.3 Arquetipo C: Mobile Nativo (187)
Framework reducido a 8 componentes:
•	CM1: capacitor.config.ts (config Capacitor)
•	CM2: package.json con dependencias nativas
•	CM3: Plugins nativos (Push, Camera, Biometrics, Geolocation)
•	CM4: API endpoints Drupal para mobile (C3 parcial)
•	CM5: Push notification service Drupal (entity + service)
•	CM6: Deep link routing config
•	CM7: App Store deployment config (iOS plist, Android manifest)
•	CM8: Offline storage strategy

2.4 Arquetipo D: SDK/Framework (193)
Framework especifico para developer tooling:
•	CS1: SDK boilerplate/scaffold (archivos base)
•	CS2: Interface/Abstract class que conectores deben implementar
•	CS3: Sandbox Docker config (Lando)
•	CS4: CI/CD pipeline para certificacion
•	CS5: Developer Portal content (OpenAPI spec)
•	CS6: Marketplace entity + commerce integration
•	CS7: Revenue share Stripe Connect config
•	CS8: Test suite template para conectores
 
3. Doc 186: AI Autonomous Agents
Arquetipo: A - Modulo Drupal Puro (jaraba_agents)
Complejidad: ALTA - Orchestracion AI + guardrails + multi-vertical

Componente	Estado	Detalle
C1: info.yml	FALTA	No incluye fichero de declaracion del modulo
C2: permissions.yml	FALTA	No define permisos para gestion de agentes
C3: routing.yml	FALTA	API endpoints listados pero sin formato YAML
C4: services.yml	FALTA	6 servicios nombrados sin DI arguments
C5: Entity PHP	PARCIAL	autonomous_agent y agent_execution en tablas, sin PHP
C6: Service contracts	PARCIAL	BaseAutonomousAgent descrito conceptualmente
C7: Controllers	FALTA	Sin controllers REST
C8: Forms	FALTA	Sin formulario de configuracion de agentes
C9: config/install	FALTA	Guardrails levels en prosa, no en config YAML
C10: config/schema	FALTA	No incluido
C11: ECA recipes	PARCIAL	Agent triggers en prosa narrativa
C12: Twig templates	FALTA	Sin dashboard template de agentes

Gap Especifico: Falta la interfaz BaseAutonomousAgent que define el contrato que cada agente vertical debe implementar. Sin esta interfaz, Claude Code no sabe que metodos son obligatorios (execute, evaluate, rollback, getCapabilities). Tambien falta el GuardrailsEnforcer con la logica de aprobacion por nivel (L0-L4).
Score: 3/12 parciales = 12.5%
 
4. Doc 187: Native Mobile (Capacitor)
Arquetipo: C - Mobile Nativo (Capacitor + React + Drupal API)
Complejidad: ALTA - Stack completamente distinto a Drupal

Componente	Estado	Detalle
CM1: capacitor.config.ts	FALTA	No incluye configuracion Capacitor
CM2: package.json	FALTA	No lista dependencias npm/capacitor
CM3: Plugins nativos	PARCIAL	Describe FCM, Camera, Biometrics pero sin config code
CM4: API Drupal mobile	PARCIAL	Endpoints listados pero sin mobile-specific headers
CM5: Push service Drupal	PARCIAL	Entity mobile_device + push_notification en tablas
CM6: Deep links config	FALTA	Menciona Universal Links sin apple-app-site-association
CM7: App Store config	FALTA	No incluye Info.plist ni AndroidManifest entries
CM8: Offline strategy	FALTA	Mencion conceptual sin implementacion

Gap Especifico: Este doc tiene un problema fundamental: describe funcionalidad mobile para 5 verticales pero no incluye NINGUN fichero de configuracion Capacitor, ni package.json, ni estructura de proyecto React. Claude Code necesita saber que plugins Capacitor instalar, como configurar FCM, y como estructurar la app. Ademas, el modulo Drupal jaraba_mobile (push + device registry) necesita los mismos 12 componentes que cualquier modulo.
Score: 3/8 parciales = 18.8%
 
5. Doc 188: Multi-Agent Orchestration
Arquetipo: A - Modulo Drupal Puro (extension de jaraba_agents)
Complejidad: MUY ALTA - Router LLM + handoff protocol + shared memory

Componente	Estado	Detalle
C1: info.yml	FALTA	No incluido
C2: permissions.yml	FALTA	No incluido
C3: routing.yml	FALTA	Endpoints de chat sin routing formal
C4: services.yml	FALTA	AgentRouter, HandoffManager sin DI
C5: Entity PHP	PARCIAL	agent_conversation, agent_handoff en tablas
C6: Service contracts	PARCIAL	Routing signals definidos, no metodos PHP
C7: Controllers	FALTA	Sin chat API controller
C8: Forms	FALTA	Sin config form para routing rules
C9: config/install	FALTA	Routing signals hardcoded en prosa
C10: config/schema	FALTA	No incluido
C11: ECA recipes	FALTA	Handoff protocol en prosa, sin ECA YAML
C12: Twig templates	FALTA	Sin observability dashboard template

Gap Especifico: Falta el AgentRouter service con la logica de clasificacion LLM + reglas. El handoff protocol de 6 pasos esta en prosa pero sin implementacion de AgentHandoffManager. La integracion con Qdrant para shared memory esta descrita conceptualmente pero sin QdrantMemoryService con metodos store/retrieve/search.
Score: 2/12 parciales = 8.3%
 
6. Doc 189: Predictive Analytics
Arquetipo: B - Hibrido Drupal + Python ML
Complejidad: ALTA - PHP wrapper + Python scikit-learn + feature store

Componente	Estado	Detalle
C1: info.yml	FALTA	No incluido
C2: permissions.yml	FALTA	No incluido
C3: routing.yml	FALTA	API predictive endpoints sin routing YAML
C4: services.yml	FALTA	6 servicios sin DI
C5: Entity PHP	PARCIAL	churn_prediction entity en tabla, sin PHP
C6: Service contracts	PARCIAL	ChurnPredictor metodos listados sin tipos
C7: Controllers	FALTA	Sin prediction API controller
C8: Forms	FALTA	Sin prediction config form
C9: config/install	FALTA	Feature weights en prosa, no config
C10: config/schema	FALTA	No incluido
C11: ECA recipes	PARCIAL	Cron re-training mencionado, sin YAML
C12: Twig templates	FALTA	Sin dashboard predictive template
C13: Python scripts	FALTA	Sin train.py, predict.py, requirements.txt
C14: Modelo ML	PARCIAL	Features definidas pero sin pipeline training
C15: Cron scheduler	FALTA	Re-training schedule sin implementacion
C16: PHP-Python bridge	FALTA	Sin wrapper que invoque Python subprocess

Gap Especifico: El gap mas critico es la ausencia total del componente Python. Claude Code necesita: requirements.txt (scikit-learn, pandas, redis), train_churn_model.py con pipeline completo (feature extraction, train/test split, model fitting, serialization), predict.py como CLI invocable desde PHP, y el PredictionBridge PHP service que ejecuta 'python3 predict.py --tenant={id} --model=churn'.
Score: 4/16 parciales = 12.5%
 
7. Doc 190: Multi-Region
Arquetipo: A - Modulo Drupal Puro (jaraba_multiregion)
Complejidad: MEDIA-ALTA - Currency + Tax + VIES + Data Residency

Componente	Estado	Detalle
C1: info.yml	FALTA	No incluido
C2: permissions.yml	FALTA	No incluido
C3: routing.yml	FALTA	Endpoints de region config sin routing
C4: services.yml	FALTA	5 servicios sin DI
C5: Entity PHP	PARCIAL	tenant_region entity descrita en tabla
C6: Service contracts	PARCIAL	TaxCalculator, ViesValidator mencionados
C7: Controllers	FALTA	Sin region API controller
C8: Forms	FALTA	Sin region settings form
C9: config/install	PARCIAL	Tax rules bien definidas pero en prosa
C10: config/schema	FALTA	No incluido
C11: ECA recipes	FALTA	Sin automatizacion de VIES validation
C12: Twig templates	FALTA	Sin currency selector template

Gap Especifico: Las reglas de IVA/VAT (B2C, B2B, reverse charge, OSS threshold) estan bien definidas en el documento pero como tabla narrativa. Claude Code necesita esto como config YAML estructurado + TaxCalculator::calculate() con la logica de decision. Tambien falta el ViesValidator service que llama a la API VIES de la UE para validar VAT numbers.
Score: 3/12 parciales = 12.5%
 
8. Doc 191: STO/PIIL Integration
Arquetipo: A - Modulo Drupal Puro (jaraba_institutional)
Complejidad: ALTA - Integracion con STO sin API publica + FUNDAE + FSE+

Componente	Estado	Detalle
C1: info.yml	FALTA	No incluido
C2: permissions.yml	FALTA	No incluido
C3: routing.yml	FALTA	Sin rutas formales para admin institucional
C4: services.yml	FALTA	Sin DI definition
C5: Entity PHP	PARCIAL	institutional_program + program_participant en tablas
C6: Service contracts	PARCIAL	Ficha Tecnica STO generator descrito conceptualmente
C7: Controllers	FALTA	Sin institutional API controller
C8: Forms	FALTA	Sin program management forms
C9: config/install	FALTA	Indicadores FUNDAE/FSE+ en prosa
C10: config/schema	FALTA	No incluido
C11: ECA recipes	PARCIAL	Auto-generacion ficha STO descrita, sin YAML
C12: Twig templates	FALTA	Sin template para Ficha Tecnica PDF

Gap Especifico: El doc 45 (Andalucia +ei) tiene mas detalle de implementacion STO que este doc 191. Falta cruzar ambos. El gap critico es el generador de Ficha Tecnica STO: Claude Code necesita saber exactamente que campos del STO llena, en que formato (PDF con estructura SAE), y como se firma digitalmente (PAdES via doc 89). Tambien faltan los indicadores FUNDAE como entidades calculadas.
Score: 3/12 parciales = 12.5%
Dependencia: Este doc deberia referenciar explicitamente doc 45 y doc 89 como prerequisitos.
 
9. Doc 192: European Funding
Arquetipo: A - Modulo Drupal Puro (jaraba_funding)
Complejidad: MEDIA - CRUD de oportunidades + auto-generacion informes

Componente	Estado	Detalle
C1: info.yml	FALTA	No incluido
C2: permissions.yml	FALTA	No incluido
C3: routing.yml	FALTA	Sin rutas formales
C4: services.yml	FALTA	Sin DI definition
C5: Entity PHP	PARCIAL	funding_opportunity + funding_application en tablas
C6: Service contracts	PARCIAL	Auto-generacion de informes mencionada
C7: Controllers	FALTA	Sin funding API controller
C8: Forms	FALTA	Sin opportunity/application forms
C9: config/install	FALTA	Target calls listadas pero no en config
C10: config/schema	FALTA	No incluido
C11: ECA recipes	PARCIAL	Alert deadline descrita, sin YAML
C12: Twig templates	FALTA	Sin template para informes tecnicos PDF

Score: 3/12 parciales = 12.5%
 
10. Doc 193: Connector SDK
Arquetipo: D - SDK/Framework (jaraba_connector_sdk)
Complejidad: MUY ALTA - SDK + Sandbox + Certification + Marketplace + Revenue Share

Componente	Estado	Detalle
CS1: SDK boilerplate	PARCIAL	Estructura de ficheros listada, sin contenido
CS2: Interface/Abstract	PARCIAL	install/uninstall/configure/sync mencionados
CS3: Sandbox Docker	FALTA	Sin Lando config para sandbox
CS4: CI/CD pipeline	FALTA	Certification process en prosa, sin pipeline YAML
CS5: OpenAPI spec	FALTA	Sin especificacion API para developers
CS6: Marketplace entity	PARCIAL	connector entity en tabla, sin PHP
CS7: Revenue share	PARCIAL	Tiers 70/30, 80/20, 85/15 definidos, sin Stripe config
CS8: Test template	FALTA	Sin test suite boilerplate para conectores

Gap Especifico: El gap mas critico es que el SDK existe solo como concepto. Claude Code necesita: ConnectorInterface.php con los metodos abstractos, un scaffold generator (drush generate:connector my_connector), la Lando config del sandbox, y el pipeline de GitHub Actions para certificacion automatica. El marketplace (Commerce integration) necesita product variation + Stripe Connect splits.
Score: 4/8 parciales = 25%
 
11. Resumen Consolidado Nivel 2

11.1 Score por Documento
Doc	Modulo	Arquetipo	TIENE	PARCIAL	FALTA	Score	Ready?
186	jaraba_agents	A: Drupal	0	3	9	12.5%	NO
187	jaraba_mobile	C: Mobile	0	3	5	18.8%	NO
188	jaraba_agents_orch	A: Drupal	0	2	10	8.3%	NO
189	jaraba_predictive	B: Hibrido	0	4	12	12.5%	NO
190	jaraba_multiregion	A: Drupal	0	3	9	12.5%	NO
191	jaraba_institutional	A: Drupal	0	3	9	12.5%	NO
192	jaraba_funding	A: Drupal	0	3	9	12.5%	NO
193	jaraba_connector_sdk	D: SDK	0	4	4	25.0%	NO
TOTAL N2			0/100	25/100	67/100	15.6%	NO

11.2 Ranking por Dificultad de Upgrade
Ordenados de mas facil a mas dificil para convertir a v2 Claude Code Ready:

Prioridad	Doc	Dificultad Upgrade	Razon	Sesiones Claude
1	192 Funding	BAJA	CRUD simple + PDF generator + alerts	1 sesion
2	190 Multi-Region	MEDIA	Tax rules + VIES API son logica pura	1 sesion
3	191 STO/PIIL	MEDIA	Cruzar con doc 45 + PDF STO generator	1-2 sesiones
4	186 Agents	ALTA	Interface pattern + guardrails + multi-vertical	2 sesiones
5	189 Predictive	ALTA	Python ML pipeline + PHP bridge	2 sesiones
6	188 Orchestration	MUY ALTA	LLM router + handoff + Qdrant memory	2-3 sesiones
7	187 Mobile	MUY ALTA	Stack totalmente distinto (Capacitor/React)	2-3 sesiones
8	193 SDK	MUY ALTA	SDK + Sandbox + CI/CD + Marketplace	3 sesiones
 
12. Comparativa N1 vs N2

Dimension	N1 (Production Ready)	N2 (Growth Ready)
Documentos	3	8
Score medio	12.5%	15.6%
Homogeneidad	100% Drupal puro	62% Drupal + 13% Hibrido + 13% Mobile + 13% SDK
Complejidad media	MEDIA	ALTA
Dependencias externas	Baja (AEPD, PDF)	Alta (Qdrant, Python, Capacitor, VIES API, STO)
Sesiones para v2	3 sesiones	14-18 sesiones
Esfuerzo total v2	~6 horas Claude	~30-36 horas Claude
Gap principal	Falta codigo PHP/YAML	Falta codigo PHP/YAML + stacks externos

Conclusion: N2 requiere ~5x mas esfuerzo que N1 para el upgrade a Claude Code Ready, debido a la heterogeneidad tecnologica y la complejidad de los modulos.
 
13. Dependencias Criticas entre Docs N2

Los docs N2 no son independientes. Hay dependencias de implementacion:

Doc Origen	Depende De	Tipo	Impacto
188 Orchestration	186 Agents	HARD	188 extiende 186. Sin BaseAutonomousAgent no hay orchestration
188 Orchestration	jaraba_ai (doc 128)	HARD	Usa Qdrant shared memory de doc 128/130
186 Agents	jaraba_ai (doc 128)	HARD	Agentes usan Claude API via jaraba_ai.rag
189 Predictive	jaraba_foc (doc 02)	SOFT	Usa metricas FOC como features para churn prediction
191 STO/PIIL	45 Andalucia +ei	HARD	doc 45 tiene mas detalle STO que doc 191
191 STO/PIIL	89 Firma Digital	HARD	Ficha Tecnica STO necesita PAdES de doc 89
193 SDK	112 Marketplace	HARD	doc 112 tiene mas detalle del marketplace que doc 193
193 SDK	134 Stripe Billing	SOFT	Revenue share usa Stripe Connect de doc 134
187 Mobile	Todos los verticales	SOFT	Mobile features son extensiones de cada vertical

Implicacion para orden de implementacion: 186 (Agents) DEBE ir antes de 188 (Orchestration). 191 (STO/PIIL) necesita 45+89 completados. 193 (SDK) necesita 112+134 operativos.
 
14. Hallazgos Especiales

14.1 Solapamiento con Documentos Existentes
Algunos docs N2 solapan con documentos existentes del ecosistema que tienen MAS detalle:

Doc N2	Solapa Con	Quien Tiene Mas Detalle	Accion
186 Agents	108 Agent Flows	Doc 108 tiene arquitectura mas completa	MERGE: v2 debe incorporar 108
188 Orchestration	108 Agent Flows (cap.5)	Doc 108 tiene multi-agent section	MERGE: v2 extiende 108
191 STO/PIIL	45 Andalucia +ei	Doc 45 tiene entidad programa_participante_ei	MERGE: v2 absorbe 45
193 SDK	112 Integration Marketplace	Doc 112 tiene OAuth2 + MCP completo	MERGE: v2 referencia 112

Recomendacion: Los docs v2 de N2 deben ABSORBER y REFERENCIAR los docs existentes que tienen mas detalle, no duplicar contenido. Esto reduce el esfuerzo de escritura y mejora la coherencia.

14.2 Doc 187 (Mobile): Caso Especial
Doc 187 es el unico documento que requiere un stack tecnologico completamente distinto. No es un modulo Drupal sino una aplicacion Capacitor/React que consume APIs Drupal. Recomendacion: dividir en dos documentos:

•	187a: jaraba_mobile (Drupal) - Modulo Drupal para push notifications, device registry, mobile-specific APIs. Framework estandar C1-C12.
•	187b: Jaraba App (Capacitor) - Proyecto React/Capacitor con su propio package.json, capacitor.config.ts, y build pipeline. Framework mobile CM1-CM8.

14.3 Doc 189 (Predictive): Python Bridge Pattern
El hibrido PHP+Python no es comun en el ecosistema Jaraba. Se recomienda el patron usado en el doc 130 (Tenant Knowledge Training) donde Python se invoca via subprocess desde un PHP service dedicado. El v2 debe documentar:

•	Directorio scripts/python/ dentro del modulo Drupal
•	requirements.txt con versiones pinned
•	CLI interface: python3 train.py --config=/path/to/config.json
•	PHP PredictionBridge::invokeModel() con exec() + JSON stdin/stdout
•	Fallback: si Python no disponible, usar reglas heuristicas PHP
 
15. Plan de Accion: Upgrade N2 a v2

15.1 Orden Optimo (considerando dependencias)

Fase	Doc	Pre-requisitos	Sesiones	Output
Fase 1	192 Funding	Ninguno	1	jaraba_funding completo
Fase 1	190 Multi-Region	Ninguno	1	jaraba_multiregion completo
Fase 2	191 STO/PIIL	Doc 45 + Doc 89 existentes	1-2	jaraba_institutional completo
Fase 2	186 Agents	Doc 108 existente	2	jaraba_agents completo
Fase 3	189 Predictive	jaraba_foc operativo	2	jaraba_predictive + Python pipeline
Fase 3	188 Orchestration	186 completado	2-3	jaraba_agents orchestration ext
Fase 4	187 Mobile	APIs verticales operativas	2-3	jaraba_mobile + Capacitor app
Fase 4	193 SDK	Doc 112 + 134 existentes	3	jaraba_connector_sdk + sandbox

15.2 Estimacion Total
Fase	Sesiones Claude	Horas Est.	Resultado
Fase 1 (Quick Wins)	2	~4h	2 modulos Drupal listos
Fase 2 (Core Growth)	3-4	~8h	Institutional + Agents listos
Fase 3 (Advanced)	4-5	~10h	Predictive + Orchestration listos
Fase 4 (Complex)	5-6	~12h	Mobile + SDK listos
TOTAL N2	14-17	~34h	8 modulos Claude Code Ready

--- Fin del Documento ---
