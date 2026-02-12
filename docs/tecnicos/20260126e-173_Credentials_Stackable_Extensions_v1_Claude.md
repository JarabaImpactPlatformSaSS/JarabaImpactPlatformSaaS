STACKABLE CREDENTIALS
Sistema de Microcredenciales Apilables
Combinación Automática de Badges
JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	173_Credentials_Stackable_Extensions
Dependencias:	172_Jaraba_Credentials_Implementation_Guide, 17_Credentials_System
 
1. Resumen Ejecutivo
Este documento especifica el sistema de Stackable Credentials (Credenciales Apilables), que permite definir combinaciones de badges que, al completarse, generan automáticamente una credencial de nivel superior. Este modelo sigue el paradigma de microcredenciales reconocido por la UE y frameworks como Europass.
1.1 Concepto de Stacking
El stacking permite que múltiples credenciales individuales se combinen para formar una credencial compuesta de mayor valor:
┌─────────────────────────────────────────────────────────────┐ │           CREDENCIAL COMPUESTA (Stack)                       │ │           "Digital Marketing Specialist"                     │ │                                                             │ │   ┌───────────┐   ┌───────────┐   ┌───────────┐            │ │   │  Badge A  │ + │  Badge B  │ + │  Badge C  │            │ │   │   SEO     │   │  Analytics│   │  Ads Mgmt │            │ │   │ Fundament.│   │  Expert   │   │  Certif.  │            │ │   └───────────┘   └───────────┘   └───────────┘            │ │                                                             │ │   Completar los 3 badges → Auto-genera Stack Certificate    │ └─────────────────────────────────────────────────────────────┘
1.2 Beneficios del Sistema
•	Modularidad: Los usuarios pueden completar badges a su ritmo y obtener valor incremental
•	Motivación: Visibilidad del progreso hacia credenciales superiores aumenta engagement
•	Flexibilidad: Permite múltiples rutas para alcanzar la misma credencial compuesta
•	Reconocimiento EU: Alineado con European Qualifications Framework (EQF) y microcredenciales
 
2. Modelo de Datos
2.1 Entidad: credential_stack
Define una credencial compuesta y las credenciales componentes requeridas.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
name	VARCHAR(255)	Nombre del stack	NOT NULL
machine_name	VARCHAR(64)	Identificador máquina	UNIQUE per tenant
description	TEXT	Descripción del stack	NOT NULL
result_template_id	INT	Template de credencial resultante	FK credential_template.id
required_templates	JSON	IDs de templates requeridos	Array of template IDs
min_required	INT	Mínimo de badges requeridos	Si < total, es opcional
optional_templates	JSON	Templates opcionales adicionales	Array, NULLABLE
bonus_credits	INT	Créditos bonus por completar stack	Adicionales a badges
bonus_xp	INT	XP bonus por completar stack	Adicionales a badges
eqf_level	INT	Nivel EQF equivalente	RANGE 1-8, NULLABLE
ects_credits	DECIMAL(4,1)	Créditos ECTS equivalentes	NULLABLE
group_id	INT	Tenant propietario	FK groups.id
status	BOOLEAN	Stack activo	DEFAULT TRUE
2.2 Entidad: user_stack_progress
Tracking del progreso del usuario hacia completar un stack.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
stack_id	INT	Stack objetivo	FK credential_stack.id
user_id	INT	Usuario	FK users.uid
completed_templates	JSON	Templates ya completados	Array of IDs
progress_percent	INT	Porcentaje de progreso	RANGE 0-100
status	VARCHAR(16)	Estado del progreso	in_progress|completed
started_at	DATETIME	Fecha de inicio	Primera credencial
completed_at	DATETIME	Fecha de completado	NULLABLE
result_credential_id	INT	Credencial emitida	FK issued_credential.id
 
3. Servicios de Stacking
3.1 StackProgressService.php
<?php namespace Drupal\jaraba_credentials\Service;  use Drupal\Core\Entity\EntityTypeManagerInterface;  class StackProgressService {    public function __construct(     protected EntityTypeManagerInterface $entityTypeManager,     protected CredentialIssuer $credentialIssuer   ) {}    /**    * Evalúa si el usuario ha completado algún stack tras obtener una credencial.    */   public function evaluateStacksForUser(int $uid, int $newCredentialTemplateId): array {     $completedStacks = [];          // 1. Obtener todas las credenciales activas del usuario     $userCredentials = $this->getUserActiveCredentials($uid);     $userTemplateIds = array_column($userCredentials, 'template_id');          // 2. Buscar stacks que incluyan el nuevo template     $relevantStacks = $this->entityTypeManager       ->getStorage('credential_stack')       ->loadByProperties(['status' => TRUE]);      foreach ($relevantStacks as $stack) {       $requiredTemplates = json_decode($stack->required_templates->value, TRUE);              // Verificar si el nuevo template es parte de este stack       if (!in_array($newCredentialTemplateId, $requiredTemplates)) {         continue;       }        // 3. Verificar si el usuario cumple los requisitos       $completedRequired = array_intersect($requiredTemplates, $userTemplateIds);       $minRequired = $stack->min_required->value ?? count($requiredTemplates);        if (count($completedRequired) >= $minRequired) {         // 4. Verificar que no tenga ya este stack         if (!$this->userHasStack($uid, $stack->id->value)) {           // 5. Emitir credencial de stack           $stackCredential = $this->issueStackCredential($stack, $uid, $completedRequired);           $completedStacks[] = [             'stack' => $stack,             'credential' => $stackCredential,           ];         }       } else {         // Actualizar progreso         $this->updateStackProgress($stack, $uid, $completedRequired);       }     }      return $completedStacks;   }    /**    * Emite la credencial de stack con evidencias de componentes.    */   protected function issueStackCredential($stack, int $uid, array $componentIds): object {     $evidence = [];          foreach ($componentIds as $templateId) {       $credential = $this->getUserCredentialForTemplate($uid, $templateId);       $evidence[] = [         'id' => 'component-' . $credential->uuid->value,         'name' => 'Component Badge: ' . $credential->template->name->value,         'description' => 'Issued on ' . $credential->issued_at->value,       ];     }      return $this->credentialIssuer->issue(       $stack->result_template_id->target_id,       $uid,       $evidence     );   }    /**    * Actualiza el progreso del usuario hacia un stack.    */   protected function updateStackProgress($stack, int $uid, array $completed): void {     $storage = $this->entityTypeManager->getStorage('user_stack_progress');          $existing = $storage->loadByProperties([       'stack_id' => $stack->id->value,       'user_id' => $uid,     ]);      $requiredCount = count(json_decode($stack->required_templates->value, TRUE));     $minRequired = $stack->min_required->value ?? $requiredCount;     $progress = (int) ((count($completed) / $minRequired) * 100);      if ($existing) {       $progress_entity = reset($existing);       $progress_entity->set('completed_templates', json_encode($completed));       $progress_entity->set('progress_percent', min($progress, 100));     } else {       $progress_entity = $storage->create([         'stack_id' => $stack->id->value,         'user_id' => $uid,         'completed_templates' => json_encode($completed),         'progress_percent' => $progress,         'status' => 'in_progress',         'started_at' => date('Y-m-d\TH:i:s'),       ]);     }      $progress_entity->save();   } }
 
4. Automatizaciones ECA
4.1 ECA-STACK-001: Evaluación Post-Emisión
id: eca_stack_evaluate_on_credential label: 'Evaluate stacks when credential is issued' events:   - plugin: 'jaraba_credentials.credential_issued'  actions:   - plugin: 'eca_custom_action'     configuration:       php: |         $stackService = \Drupal::service('jaraba_credentials.stack_progress');         $credential = $event->getCredential();                  $completedStacks = $stackService->evaluateStacksForUser(           $credential->recipient_uid->target_id,           $credential->template_id->target_id         );                  // Log completed stacks         foreach ($completedStacks as $result) {           \Drupal::logger('jaraba_credentials')->info(             'User @uid completed stack @stack',             [               '@uid' => $credential->recipient_uid->target_id,               '@stack' => $result['stack']->name->value,             ]           );         }
4.2 ECA-STACK-002: Notificación de Progreso
id: eca_stack_progress_notification label: 'Notify user of stack progress update' events:   - plugin: 'entity:postsave'     configuration:       entity_type: user_stack_progress  conditions:   - plugin: 'entity_field_value_changed'     configuration:       field_name: progress_percent  actions:   - plugin: 'eca_send_email'     configuration:       to: '[user_stack_progress:user:mail]'       subject: '📈 ¡Progreso hacia [user_stack_progress:stack:name]!'       body: |         Hola [user_stack_progress:user:display_name],                  Has completado el [user_stack_progress:progress_percent]%          hacia obtener la credencial:                  🎯 [user_stack_progress:stack:name]                  Te faltan [user_stack_progress:remaining_count] badges para completarla.                  ¡Sigue así!
 
5. Ejemplos de Stacks por Vertical
5.1 Vertical Empleabilidad
Stack Certificate	Badges Requeridos	Bonus
LinkedIn Power User	LinkedIn Profile Creator + LinkedIn Content Pro + LinkedIn Networking	+500 CR, +200 XP
Job Search Master	CV Writing Pro + ATS Optimizer + Interview Skills + Job Alert Expert	+800 CR, +350 XP
Empleabilidad Digital Expert	LinkedIn Power User + Job Search Master + Personal Branding	+1500 CR, +500 XP, 2 ECTS
5.2 Vertical Emprendimiento
Stack Certificate	Badges Requeridos	Bonus
Digital Business Foundations	Business Diagnostic + Digital Maturity + First Action Plan	+400 CR, +150 XP
Growth Ready	Business Model Canvas + Financial Projections + MVP Validated	+600 CR, +250 XP
Transformation Champion	Digital Business Foundations + Growth Ready + Mentor Sessions Complete	+1200 CR, +450 XP, 3 ECTS
5.3 Configuración de Stack en JSON
{   "name": "LinkedIn Power User",   "machine_name": "linkedin_power_user",   "description": "Dominio completo de LinkedIn para búsqueda activa de empleo",   "result_template_id": 15,   "required_templates": [1, 2, 3],   "min_required": 3,   "optional_templates": [4, 5],   "bonus_credits": 500,   "bonus_xp": 200,   "eqf_level": 4,   "ects_credits": 0.5 }
 
6. APIs del Sistema de Stacking
Método	Endpoint	Descripción
GET	/api/v1/stacks	Listar stacks disponibles
GET	/api/v1/stacks/{id}	Detalle de stack con componentes
GET	/api/v1/stacks/my-progress	Progreso del usuario en todos los stacks
GET	/api/v1/stacks/{id}/progress	Progreso en un stack específico
GET	/api/v1/stacks/recommended	Stacks recomendados según perfil
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas
Sprint 1	Semana 1-2	Entidades: credential_stack, user_stack_progress. Migrations.	40h
Sprint 2	Semana 3-4	StackProgressService. Evaluación automática. ECA flows.	50h
Sprint 3	Semana 5-6	APIs REST. UI de progreso. Widgets de dashboard.	40h
Sprint 4	Semana 7-8	Configuración de stacks iniciales. Tests. QA. Go-live.	30h
Total estimado: 160 horas de desarrollo
--- Fin del Documento ---
173_Credentials_Stackable_Extensions_v1.docx | Jaraba Impact Platform | Enero 2026
