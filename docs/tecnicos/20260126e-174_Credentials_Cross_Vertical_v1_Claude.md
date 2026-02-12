CROSS-VERTICAL CREDENTIALS
Sistema de Badges Multi-Vertical
Credenciales que Cruzan Verticales del Ecosistema
JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	174_Credentials_Cross_Vertical
Dependencias:	172_Implementation_Guide, 173_Stackable_Extensions
 
1. Resumen Ejecutivo
Los Cross-Vertical Credentials son credenciales que reconocen logros alcanzados mediante la combinación de actividades en múltiples verticales del ecosistema Jaraba. Este sistema incentiva a los usuarios a explorar y utilizar diferentes servicios de la plataforma, aumentando el engagement y el lifetime value.
1.1 Verticales del Ecosistema
Vertical	Descripción	Target
Empleabilidad	Formación y empleabilidad digital	Personas en búsqueda de empleo
Emprendimiento	Transformación digital de negocios	Emprendedores, autónomos
AgroConecta	Marketplace agrícola	Productores, cooperativas
ComercioConecta	Comercio local digital	Comercios de proximidad
ServiciosConecta	Servicios profesionales	Profesionales independientes
1.2 Concepto de Cross-Vertical
┌──────────────────────────────────────────────────────────────────────┐ │               CROSS-VERTICAL CREDENTIAL                               │ │               "Emprendedor Empleable"                                 │ │                                                                      │ │   ┌────────────────────┐        ┌────────────────────┐              │ │   │   EMPLEABILIDAD    │   +    │   EMPRENDIMIENTO   │              │ │   │                    │        │                    │              │ │   │ • LinkedIn Expert  │        │ • Business Canvas  │              │ │   │ • CV Pro           │        │ • Digital Maturity │              │ │   │ • Interview Ready  │        │ • Mentor Sessions  │              │ │   └────────────────────┘        └────────────────────┘              │ │                                                                      │ │   Usuario activo en AMBAS verticales → Credencial especial          │ └──────────────────────────────────────────────────────────────────────┘
 
2. Modelo de Datos
2.1 Entidad: cross_vertical_credential
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
name	VARCHAR(255)	Nombre del badge cross-vertical	NOT NULL
machine_name	VARCHAR(64)	Identificador máquina	UNIQUE
description	TEXT	Descripción del logro	NOT NULL
result_template_id	INT	Template de credencial resultante	FK credential_template.id
verticals_required	JSON	Verticales involucradas	["empleabilidad","emprendimiento"]
conditions	JSON	Condiciones por vertical	Objeto con reglas
bonus_credits	INT	Créditos bonus adicionales	DEFAULT 0
bonus_xp	INT	XP bonus adicionales	DEFAULT 0
rarity	VARCHAR(16)	Nivel de rareza	common|rare|epic|legendary
status	BOOLEAN	Activo/Inactivo	DEFAULT TRUE
2.2 Estructura de Condiciones
{   "verticals_required": ["empleabilidad", "emprendimiento"],   "conditions": {     "empleabilidad": {       "type": "credentials_count",       "min_count": 3,       "credential_types": ["course_badge", "path_certificate"]     },     "emprendimiento": {       "type": "milestones_achieved",       "required_milestones": ["diagnostic_complete", "action_plan_started"]     }   },   "additional_rules": {     "time_window_days": 180,     "require_active_subscription": false   } }
 
3. Catálogo de Cross-Vertical Badges
3.1 Badges Empleabilidad + Emprendimiento
Badge	Condiciones	Rareza	Bonus
Emprendedor Empleable	3+ badges Empleabilidad + Diagnostic completo + 1 Action Plan	Rare	+800 CR
Dual Track Master	LinkedIn Power User Stack + Growth Ready Stack	Epic	+1500 CR
Ecosistema Champion	5+ badges cada vertical + Mentor sessions + First hire/sale	Legendary	+3000 CR
3.2 Badges Multi-Commerce
Badge	Condiciones	Rareza	Bonus
Omnichannel Seller	Ventas en AgroConecta + ComercioConecta + ServiciosConecta	Rare	+600 CR
Local Economy Hero	10+ transacciones cross-vertical + 4.5+ rating promedio	Epic	+1200 CR
Marketplace Titan	€5000+ GMV combinado + 50+ clientes únicos	Legendary	+2500 CR
3.3 Badges de Impacto Social
Badge	Condiciones	Rareza	Bonus
Rural Digital Pioneer	Usuario de zona rural + 2+ verticales activas + Formación completa	Rare	+700 CR
Mentor Multiplicador	Completar programa + Mentorizar a 3+ usuarios nuevos	Epic	+1000 CR
Transformador Digital	De desempleado a emprendedor activo con ventas en <12 meses	Legendary	+5000 CR
 
4. Servicio de Evaluación Cross-Vertical
4.1 CrossVerticalEvaluator.php
<?php namespace Drupal\jaraba_credentials\Service;  class CrossVerticalEvaluator {    public function __construct(     protected EntityTypeManagerInterface $entityTypeManager,     protected CredentialIssuer $issuer,     protected VerticalActivityTracker $activityTracker   ) {}    /**    * Evalúa si el usuario califica para badges cross-vertical.    */   public function evaluateForUser(int $uid): array {     $awarded = [];          // Cargar actividad del usuario por vertical     $userActivity = $this->activityTracker->getUserActivitySummary($uid);          // Cargar definiciones de cross-vertical badges     $crossBadges = $this->entityTypeManager       ->getStorage('cross_vertical_credential')       ->loadByProperties(['status' => TRUE]);      foreach ($crossBadges as $badge) {       if ($this->userAlreadyHasBadge($uid, $badge->id->value)) {         continue;       }        if ($this->evaluateConditions($badge, $userActivity)) {         $credential = $this->awardCrossVerticalBadge($badge, $uid);         $awarded[] = [           'badge' => $badge,           'credential' => $credential,         ];       }     }      return $awarded;   }    protected function evaluateConditions($badge, array $activity): bool {     $conditions = json_decode($badge->conditions->value, TRUE);     $verticalsRequired = json_decode($badge->verticals_required->value, TRUE);      foreach ($verticalsRequired as $vertical) {       if (!isset($activity[$vertical])) {         return FALSE;       }        $verticalCondition = $conditions[$vertical] ?? [];       if (!$this->evaluateVerticalCondition($verticalCondition, $activity[$vertical])) {         return FALSE;       }     }      // Evaluar reglas adicionales (time window, etc.)     if (isset($conditions['additional_rules'])) {       if (!$this->evaluateAdditionalRules($conditions['additional_rules'], $activity)) {         return FALSE;       }     }      return TRUE;   }    protected function evaluateVerticalCondition(array $condition, array $activity): bool {     switch ($condition['type'] ?? 'credentials_count') {       case 'credentials_count':         $count = $activity['credentials_count'] ?? 0;         return $count >= ($condition['min_count'] ?? 1);        case 'milestones_achieved':         $achieved = $activity['milestones'] ?? [];         $required = $condition['required_milestones'] ?? [];         return count(array_intersect($achieved, $required)) === count($required);        case 'transactions_count':         return ($activity['transactions_count'] ?? 0) >= ($condition['min_count'] ?? 1);        case 'gmv_threshold':         return ($activity['gmv_total'] ?? 0) >= ($condition['min_amount'] ?? 0);        default:         return FALSE;     }   }    protected function awardCrossVerticalBadge($badge, int $uid): object {     // Construir evidencia de cada vertical     $evidence = $this->buildCrossVerticalEvidence($badge, $uid);          // Emitir credencial     $credential = $this->issuer->issue(       $badge->result_template_id->target_id,       $uid,       $evidence     );      // Aplicar bonus     $this->applyBonus($uid, $badge->bonus_credits->value, $badge->bonus_xp->value);      return $credential;   } }
 
5. Automatizaciones ECA
5.1 ECA-CROSS-001: Evaluación Periódica
id: eca_cross_vertical_daily_eval label: 'Daily cross-vertical badge evaluation' events:   - plugin: 'cron'     configuration:       frequency: daily       time: '03:00'  actions:   - plugin: 'eca_custom_action'     configuration:       php: |         $evaluator = \Drupal::service('jaraba_credentials.cross_vertical_evaluator');                  // Obtener usuarios activos en últimos 30 días         $activeUsers = \Drupal::entityQuery('user')           ->condition('access', strtotime('-30 days'), '>')           ->execute();                  foreach ($activeUsers as $uid) {           $awarded = $evaluator->evaluateForUser($uid);                      foreach ($awarded as $result) {             \Drupal::logger('jaraba_credentials')->info(               'Cross-vertical badge @badge awarded to user @uid',               [                 '@badge' => $result['badge']->name->value,                 '@uid' => $uid,               ]             );           }         }
5.2 ECA-CROSS-002: Trigger por Evento Significativo
id: eca_cross_vertical_on_milestone label: 'Evaluate cross-vertical on milestone achievement' events:   - plugin: 'jaraba_credentials.credential_issued'   - plugin: 'entity:postsave'     configuration:       entity_type: user_milestone   - plugin: 'commerce_order.order.paid'  actions:   - plugin: 'eca_custom_action'     configuration:       php: |         $evaluator = \Drupal::service('jaraba_credentials.cross_vertical_evaluator');         $uid = $event->getUser()->id();         $evaluator->evaluateForUser($uid);
6. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas
Sprint 1	Semana 1-2	Entidad cross_vertical_credential. VerticalActivityTracker service.	50h
Sprint 2	Semana 3-4	CrossVerticalEvaluator. Integración con verticales existentes.	60h
Sprint 3	Semana 5-6	ECA flows. Configuración de badges iniciales. UI showcase.	40h
Sprint 4	Semana 7-8	Notificaciones especiales. Gamificación de rareza. Tests. QA.	30h
Total estimado: 180 horas de desarrollo
--- Fin del Documento ---
174_Credentials_Cross_Vertical_v1.docx | Jaraba Impact Platform | Enero 2026
