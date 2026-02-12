CREDENTIALS EMPRENDIMIENTO
Extensión del Sistema de Credenciales
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	175_Credentials_Emprendimiento_Extension
Dependencias:	172_Implementation_Guide, 25-44_Emprendimiento_*, 30_Progress_Milestones
 
1. Resumen Ejecutivo
Este documento especifica la extensión del módulo jaraba_credentials para la vertical de Emprendimiento. Define el catálogo completo de badges, los triggers de emisión automática basados en hitos del journey emprendedor, y la integración con el sistema de gamificación existente (Créditos de Impacto).
1.1 Alineación con Documentación Existente
Esta extensión se integra con la documentación técnica de Emprendimiento existente:
Documento	Integración
25_Business_Diagnostic	Badges por completar diagnóstico y scores de madurez
28_Digitalization_Paths	Certificates por completar rutas de digitalización
30_Progress_Milestones	Trigger de badges por hitos del journey
31-33_Mentoring	Badges por sesiones de mentoría completadas
36_Business_Model_Canvas	Badge por canvas completado y validado
37_MVP_Validation	Badge por MVP validado con métricas
38_Financial_Projections	Badge por proyecciones financieras completas
 
2. Catálogo de Badges Emprendimiento
2.1 Fase 1: Diagnóstico y Descubrimiento
Badge	Trigger	Tipo	CR	XP
Diagnóstico Completado	business_diagnostic.status = completed	course_badge	50	100
Madurez Digital Básica	digital_maturity_score >= 30	skill_endorsement	100	150
Madurez Digital Intermedia	digital_maturity_score >= 60	skill_endorsement	200	250
Madurez Digital Avanzada	digital_maturity_score >= 85	skill_endorsement	400	400
Análisis Competitivo	competitive_analysis.status = completed	course_badge	75	120
Ruta Definida	digitalization_path asignado	achievement	100	150
2.2 Fase 2: Planificación y Diseño
Badge	Trigger	Tipo	CR	XP
Business Canvas Creator	business_model_canvas.status = draft	course_badge	150	200
Business Canvas Validated	business_model_canvas.validated = true	path_certificate	300	350
First Action Plan	action_plan.count >= 1	achievement	100	150
Action Plan Pro	action_plan tasks 100% completed	path_certificate	250	300
Financial Architect	financial_projections.completed	course_badge	200	250
Pitch Ready	Canvas + Projections + Deck completed	path_certificate	500	500
2.3 Fase 3: Ejecución y Crecimiento
Badge	Trigger	Tipo	CR	XP
MVP Launched	mvp_validation.launched = true	achievement	300	350
MVP Validated	mvp_validation.metrics_achieved	path_certificate	500	500
First Sale	commerce_order.count >= 1	achievement	250	300
Revenue €1K	total_revenue >= 1000	achievement	400	400
Revenue €10K	total_revenue >= 10000	achievement	1000	800
Digital Kit Completado	digital_kit.implementation = 100%	path_certificate	350	400
 
2.4 Badges de Mentoría y Comunidad
Badge	Trigger	Tipo	CR	XP
First Mentoring Session	mentoring_sessions.count >= 1	achievement	100	150
Mentoring Completo	mentoring_sessions.count >= 5	path_certificate	350	400
Networker Activo	networking_events.attended >= 3	achievement	150	200
Collaboration Champion	collaboration_groups.active_participation	achievement	200	250
Mentor Estrella	mentor_rating >= 4.8 AND sessions >= 10	skill_endorsement	500	600
2.5 Diplomas de Programa
Diploma	Requisitos	Tipo	CR	XP
Emprendedor Digital Básico	Diagnóstico + Ruta + 3 badges Fase 1	diploma	500	600
Emprendedor Digital Avanzado	Básico + Canvas Validated + Financial + 5 badges Fase 2	diploma	1000	1000
Transformador Digital Expert	Avanzado + MVP Validated + First Sale + Mentoring Completo	diploma	2000	1500
 
3. Configuración de Triggers Automáticos
3.1 ECA-EMP-CRED-001: Diagnóstico Completado
id: eca_emprendimiento_diagnostic_badge label: 'Issue badge on diagnostic completion' events:   - plugin: 'entity:presave'     configuration:       entity_type: business_diagnostic  conditions:   - plugin: 'entity_field_value'     configuration:       field_name: status       value: completed   - plugin: 'entity_is_new'     negate: true  actions:   - plugin: 'jaraba_credentials_issue'     configuration:       template_machine_name: 'diagnostico_completado'       recipient: '[business_diagnostic:user_id]'       evidence:         - maturity_score: '[business_diagnostic:digital_maturity_score]'         - completed_at: '[business_diagnostic:completed_at]'         - vertical: '[business_diagnostic:business_vertical]'
3.2 ECA-EMP-CRED-002: Madurez Digital por Score
id: eca_emprendimiento_maturity_badges label: 'Issue maturity badges based on score thresholds' events:   - plugin: 'entity:postsave'     configuration:       entity_type: digital_maturity_assessment  actions:   - plugin: 'eca_custom_action'     configuration:       php: |         $score = $entity->score->value;         $uid = $entity->user_id->target_id;         $issuer = \Drupal::service('jaraba_credentials.issuer');                  $thresholds = [           30 => 'madurez_digital_basica',           60 => 'madurez_digital_intermedia',           85 => 'madurez_digital_avanzada',         ];                  foreach ($thresholds as $threshold => $template) {           if ($score >= $threshold) {             // Verificar que no tenga ya este badge             if (!$issuer->userHasCredential($uid, $template)) {               $issuer->issueByMachineName($template, $uid, [                 ['name' => 'Score achieved', 'description' => "Score: $score%"]               ]);             }           }         }
3.3 ECA-EMP-CRED-003: First Sale Achievement
id: eca_emprendimiento_first_sale label: 'Issue First Sale badge on commerce order' events:   - plugin: 'commerce_order.order.paid'  conditions:   - plugin: 'eca_custom_condition'     configuration:       php: |         // Verificar que es la primera venta del usuario         $uid = $event->getOrder()->getCustomerId();         $orders = \Drupal::entityQuery('commerce_order')           ->condition('uid', $uid)           ->condition('state', 'completed')           ->count()           ->execute();         return $orders == 1;  actions:   - plugin: 'jaraba_credentials_issue'     configuration:       template_machine_name: 'first_sale'       recipient: '[commerce_order:uid]'       evidence:         - order_id: '[commerce_order:order_id]'         - amount: '[commerce_order:total_price:formatted]'         - date: '[commerce_order:completed]'
 
4. Templates de Credenciales
4.1 Configuración de Import
Los templates se importan via config/install del módulo. Ejemplo de YAML:
# config/install/jaraba_credentials.credential_template.diagnostico_completado.yml langcode: es status: true dependencies: {} id: diagnostico_completado uuid: null name: 'Diagnóstico Empresarial Completado' machine_name: diagnostico_completado description: |   Demuestra que el emprendedor ha completado el diagnóstico integral    de su negocio, incluyendo análisis de madurez digital, análisis    DAFO y evaluación de competencias digitales. credential_type: course_badge image_uri: 'public://badges/emprendimiento/diagnostico_completado.png' criteria_html: |   <h3>Criterios de Obtención</h3>   <ul>     <li>Completar el cuestionario de diagnóstico empresarial (100%)</li>     <li>Obtener evaluación de madurez digital</li>     <li>Recibir recomendaciones personalizadas</li>   </ul> skills_json: '["business_analysis", "digital_assessment", "strategic_thinking"]' alignment_json: |   {     "esco": ["http://data.europa.eu/esco/skill/S4.7.1"],     "entrecomp": ["ideas_opportunities.1"]   } validity_months: 24 credits_value: 50 xp_value: 100 is_stackable: true trigger_type: auto trigger_config: |   {     "entity_type": "business_diagnostic",     "field": "status",     "value": "completed"   }
4.2 Diseño Visual de Badges
Especificaciones para los diseños de badges de Emprendimiento:
Elemento	Especificación	Fase 1-2	Fase 3+
Formato	PNG transparente	✓	✓
Dimensiones	400x400 px mínimo	✓	✓
Color primario	Naranja #E67E22	Base	Dorado
Color secundario	Azul #1B4F72	Acentos	Acentos
Borde	Según tipo	Plata	Oro
Icono central	Representativo del logro	Simple	Elaborado
 
5. Integración con Sistema de Gamificación
5.1 Sincronización con Créditos de Impacto
<?php /**  * Event subscriber para sincronizar credenciales con gamificación.  */ class EmprendimientoCredentialSubscriber implements EventSubscriberInterface {    public static function getSubscribedEvents() {     return [       CredentialIssuedEvent::EVENT_NAME => 'onCredentialIssued',     ];   }    public function onCredentialIssued(CredentialIssuedEvent $event) {     $credential = $event->getCredential();     $template = $event->getTemplate();     $uid = $credential->recipient_uid->target_id;      // 1. Añadir créditos de impacto     $credits = $template->credits_value->value;     if ($credits > 0) {       $this->impactCreditsService->addCredits($uid, $credits, [         'source' => 'credential',         'credential_id' => $credential->id(),         'description' => 'Badge: ' . $template->name->value,       ]);     }      // 2. Añadir XP     $xp = $template->xp_value->value;     if ($xp > 0) {       $this->gamificationService->addXp($uid, $xp);     }      // 3. Evaluar si cruza umbral de nivel de expertise     $this->expertiseLevelService->evaluateUserLevel($uid);      // 4. Verificar hitos relacionados     $this->milestoneService->evaluateMilestones($uid, 'credential_earned', [       'credential_type' => $template->credential_type->value,       'template_id' => $template->id(),     ]);   } }
5.2 Niveles de Expertise Emprendimiento
Nivel	XP Requerido	Badges Mínimos	Beneficios
Explorador	0	0	Acceso básico
Iniciado	500	3 badges Fase 1	Descuento 10% kits
Profesional	2000	Diploma Básico	Prioridad mentoría
Experto	5000	Diploma Avanzado	Visibilidad marketplace
Master	10000	Diploma Expert + €5K revenue	Co-autoría contenidos
 
6. APIs Específicas Emprendimiento
Método	Endpoint	Descripción
GET	/api/v1/emprendimiento/credentials/catalog	Catálogo de badges disponibles
GET	/api/v1/emprendimiento/credentials/progress	Progreso hacia próximos badges
GET	/api/v1/emprendimiento/credentials/journey	Mapa visual del journey con badges
GET	/api/v1/emprendimiento/credentials/next-recommended	Siguiente badge recomendado
GET	/api/v1/emprendimiento/expertise-level	Nivel de expertise actual
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas
Sprint 1	Semana 1-2	Templates YAML de badges Fase 1. Diseños visuales. Config import.	40h
Sprint 2	Semana 3-4	Templates Fase 2-3. ECA triggers diagnóstico y canvas.	50h
Sprint 3	Semana 5-6	ECA triggers mentoría y commerce. Integración gamificación.	50h
Sprint 4	Semana 7-8	APIs específicas. UI progreso journey. Diplomas.	40h
Sprint 5	Semana 9-10	Niveles de expertise. Tests E2E. QA. Go-live.	30h
Total estimado: 210 horas de desarrollo
Nota: Este desarrollo es posterior al módulo core jaraba_credentials (Doc 172).
--- Fin del Documento ---
175_Credentials_Emprendimiento_Extension_v1.docx | Jaraba Impact Platform | Enero 2026
