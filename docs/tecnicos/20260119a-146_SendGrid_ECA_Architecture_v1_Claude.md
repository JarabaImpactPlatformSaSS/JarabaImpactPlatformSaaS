


ARQUITECTURA EMAIL
SendGrid + Drupal ECA

Sistema de Email Marketing Nativo
con Automatización ECA


JARABA IMPACT PLATFORM


Versión: 1.0  |  Enero 2026  |  Código: 146_SendGrid_ECA_Architecture_v1
 
1. Resumen Ejecutivo

Esta arquitectura utiliza SendGrid como servicio de envío de emails transaccionales y de marketing, con toda la lógica de automatización implementada nativamente en Drupal mediante el módulo ECA. Esto proporciona control total sobre las secuencias, mejor coste a escala, y evita dependencia de un SaaS de marketing externo.

1.1 Comparativa: ActiveCampaign vs SendGrid + ECA

Aspecto	ActiveCampaign Pro	SendGrid + ECA
Coste 1K contactos	~€79/mes	~€20/mes
Coste 10K contactos	~€375/mes	~€35/mes
Coste 50K contactos	~€949/mes	~€90/mes
Lógica automatización	En la nube (AC)	En Drupal (control total)
Templates	Editor visual AC	MJML + Twig (más flexible)
Personalización	Limitada a campos AC	Acceso a toda la BD Drupal
Segmentación	Tags y listas AC	Views + cualquier campo
A/B Testing	Incluido	Requiere desarrollo
Analytics	Dashboard AC	Custom + webhooks
Deliverability	Buena	Excelente (IP dedicada)
Dependencia vendor	Alta	Baja (solo envío)
Desarrollo inicial	Bajo (config)	Medio-Alto (código)
Mantenimiento	Bajo	Medio

1.2 Cuándo Elegir Cada Opción

Elige ActiveCampaign si:
• Necesitas arrancar rápido con mínimo desarrollo
• Tu lista será <5K contactos a largo plazo
• No tienes capacidad de desarrollo Drupal disponible

Elige SendGrid + ECA si:
• Proyectas >10K contactos (ahorro significativo)
• Necesitas personalización profunda con datos de Drupal
• Quieres independencia de vendor lock-in
• Tienes equipo de desarrollo que puede mantenerlo
 
2. Arquitectura Técnica

2.1 Stack Tecnológico

Componente	Tecnología
Envío Email	SendGrid API v3 (o Amazon SES como alternativa)
Motor Automatización	Drupal ECA Module 2.x
Cola de Mensajes	Drupal Queue API + Redis (opcional)
Templates	MJML → HTML compilado + Twig para variables
Programación	ECA Scheduler + Drupal Cron
Tracking	SendGrid Event Webhooks → entidad email_tracking
Preferencias	Entidad user_email_preferences
Analytics	Custom dashboard con datos de tracking

2.2 Diagrama de Flujo

[Evento Drupal] → [ECA Evalúa Condiciones] → [Queue Item Creado]
       ↓
[Cron Procesa Queue] → [Renderiza Template MJML] → [SendGrid API]
       ↓
[Webhook SendGrid] → [email_tracking Entity] → [Analytics Dashboard]

2.3 Módulo jaraba_email

Estructura del módulo personalizado:

modules/custom/jaraba_email/
├── jaraba_email.info.yml
├── jaraba_email.module
├── jaraba_email.services.yml
├── src/
│   ├── Entity/
│   │   ├── EmailSequence.php
│   │   ├── EmailSequenceStep.php
│   │   ├── EmailEnrollment.php
│   │   └── EmailTracking.php
│   ├── Service/
│   │   ├── EmailService.php
│   │   ├── SendGridClient.php
│   │   ├── TemplateRenderer.php
│   │   └── SequenceManager.php
│   └── Plugin/
│       └── ECA/
│           ├── Event/
│           ├── Condition/
│           └── Action/
├── templates/
│   └── mjml/
└── config/
    └── eca/
 
3. Modelo de Datos

3.1 Entidad: email_sequence
Define una secuencia de emails (ej: onboarding de 7 días)

Campo	Tipo / Descripción
id	INT AUTO_INCREMENT PRIMARY KEY
machine_name	VARCHAR(64) UNIQUE -- ej: onboarding_job_seeker
label	VARCHAR(255) -- Nombre visible
description	TEXT -- Descripción interna
trigger_event	VARCHAR(128) -- Evento ECA que inicia la secuencia
trigger_conditions	JSON -- Condiciones adicionales (avatar_type, vertical, etc.)
goal_event	VARCHAR(128) -- Evento que marca completion
is_active	BOOLEAN DEFAULT TRUE
vertical_id	INT FK → vertical (NULL = todas)
created	DATETIME
updated	DATETIME

3.2 Entidad: email_sequence_step
Cada paso/email dentro de una secuencia

Campo	Tipo / Descripción
id	INT AUTO_INCREMENT PRIMARY KEY
sequence_id	INT FK → email_sequence
step_order	INT -- Orden en la secuencia (1, 2, 3...)
delay_value	INT -- Cantidad de tiempo de espera
delay_unit	ENUM(minutes, hours, days) -- Unidad de delay
template_id	VARCHAR(128) -- ID del template MJML
subject	VARCHAR(255) -- Asunto del email (con tokens)
conditions	JSON -- Condiciones extra para este step
is_active	BOOLEAN DEFAULT TRUE

3.3 Entidad: email_enrollment
Registro de usuarios enrolled en secuencias

Campo	Tipo / Descripción
id	INT AUTO_INCREMENT PRIMARY KEY
user_id	INT FK → users
sequence_id	INT FK → email_sequence
current_step	INT -- Step actual (0 = no iniciado)
status	ENUM(active, completed, cancelled, paused)
enrolled_at	DATETIME
next_email_at	DATETIME -- Cuándo enviar el siguiente
completed_at	DATETIME NULL
metadata	JSON -- Datos de contexto al enrollar

3.4 Entidad: email_tracking
Tracking de eventos de email (webhooks SendGrid)

Campo	Tipo / Descripción
id	INT AUTO_INCREMENT PRIMARY KEY
message_id	VARCHAR(255) -- ID de SendGrid
user_id	INT FK → users
enrollment_id	INT FK → email_enrollment (NULL si no es secuencia)
template_id	VARCHAR(128)
event_type	ENUM(sent, delivered, opened, clicked, bounced, spam, unsubscribed)
event_data	JSON -- Datos del webhook
created	DATETIME
 
4. Flujos ECA

4.1 ECA-EMAIL-001: Enroll en Secuencia

# config/eca/eca.model.email_sequence_enroll.yml
id: email_sequence_enroll
label: "Enroll usuario en secuencia de email"
events:
  - plugin: "eca_user:user_insert"
    label: "Usuario registrado"
conditions:
  - plugin: "eca:entity_field_value"
    settings:
      field: "field_avatar_type"
      operator: "equals"
      value: "job_seeker"
actions:
  - plugin: "jaraba_email:enroll_sequence"
    settings:
      sequence: "onboarding_job_seeker"
      user: "[user:uid]"
      context:
        vertical: "[user:field_vertical:target_id]"
        signup_source: "[user:field_signup_source]"

4.2 ECA-EMAIL-002: Procesar Cola de Emails

# config/eca/eca.model.email_queue_process.yml
id: email_queue_process
label: "Procesar cola de emails pendientes"
events:
  - plugin: "eca:cron"
    settings:
      interval: 300  # Cada 5 minutos
actions:
  # 1. Buscar enrollments con next_email_at <= NOW
  - plugin: "eca:entity_query"
    settings:
      entity_type: "email_enrollment"
      conditions:
        status: "active"
        next_email_at: "<= [current_datetime]"
      limit: 100
      result_key: "pending_emails"
  # 2. Para cada enrollment
  - plugin: "eca:foreach"
    settings:
      items: "[pending_emails]"
      item_key: "enrollment"
    actions:
      - plugin: "jaraba_email:send_sequence_step"
        settings:
          enrollment_id: "[enrollment:id]"

4.3 ECA-EMAIL-003: Goal Reached (Cancelar Secuencia)

# config/eca/eca.model.email_goal_reached.yml
id: email_goal_reached
label: "Usuario alcanzó goal - completar secuencia"
events:
  # Ejemplo: completó su perfil (goal del onboarding)
  - plugin: "eca_content:entity_update"
    settings:
      entity_type: "candidate_profile"
conditions:
  - plugin: "eca:entity_field_value"
    settings:
      field: "profile_completion"
      operator: ">="
      value: "80"
actions:
  - plugin: "jaraba_email:complete_sequence"
    settings:
      user: "[entity:user_id]"
      sequence: "onboarding_job_seeker"
      reason: "goal_reached"
 
5. Servicio de Envío (SendGrid)

5.1 Configuración SendGrid

Configuración	Valor
Plan Recomendado	Pro (50K-100K emails/mes) ~$89/mes
Domain Authentication	SPF + DKIM + DMARC configurado
IP Dedicada	Sí (mejora deliverability)
Event Webhooks	Habilitados para tracking
Suppression Management	Habilitado (bounces, spam, unsubs)

5.2 SendGridClient Service

<?php
namespace Drupal\jaraba_email\Service;

use SendGrid\Mail\Mail;

class SendGridClient {
  private $apiKey;
  private $fromEmail = "noreply@jarabaimpact.com";
  private $fromName = "Jaraba Impact";

  public function send(string $to, string $subject, string $html, array $metadata = []): string {
    $email = new Mail();
    $email->setFrom($this->fromEmail, $this->fromName);
    $email->addTo($to);
    $email->setSubject($subject);
    $email->addContent("text/html", $html);

    // Custom args para tracking
    $email->addCustomArg("user_id", $metadata["user_id"] ?? "");
    $email->addCustomArg("enrollment_id", $metadata["enrollment_id"] ?? "");
    $email->addCustomArg("template_id", $metadata["template_id"] ?? "");

    // Tracking settings
    $email->setClickTracking(true);
    $email->setOpenTracking(true);

    $sg = new \SendGrid($this->apiKey);
    $response = $sg->send($email);

    return $response->headers()["X-Message-Id"] ?? "";
  }
}

5.3 Webhook Handler

// Endpoint: POST /api/v1/webhooks/sendgrid
public function handleWebhook(Request $request): JsonResponse {
  $events = json_decode($request->getContent(), true);

  foreach ($events as $event) {
    EmailTracking::create([
      "message_id" => $event["sg_message_id"],
      "user_id" => $event["user_id"] ?? null,
      "enrollment_id" => $event["enrollment_id"] ?? null,
      "template_id" => $event["template_id"] ?? null,
      "event_type" => $event["event"], // delivered, opened, clicked, etc.
      "event_data" => json_encode($event),
    ])->save();

    // Acciones específicas
    if ($event["event"] === "unsubscribe") {
      $this->handleUnsubscribe($event["email"]);
    }
  }

  return new JsonResponse(["status" => "ok"]);
}
 
6. Sistema de Templates (MJML)

6.1 Estructura de Templates

templates/mjml/
├── base.mjml                    # Layout base
├── components/
│   ├── header.mjml              # Header con logo
│   ├── footer.mjml              # Footer con unsub
│   ├── button.mjml              # CTA button
│   └── card.mjml                # Card component
├── sequences/
│   ├── onboarding/
│   │   ├── welcome.mjml
│   │   ├── first_step.mjml
│   │   ├── feature_discovery.mjml
│   │   └── completion.mjml
│   ├── reactivation/
│   └── churn_prevention/
└── transactional/
    ├── password_reset.mjml
    ├── order_confirmation.mjml
    └── invoice.mjml

6.2 Ejemplo: base.mjml

<mjml>
  <mj-head>
    <mj-attributes>
      <mj-all font-family="Arial, sans-serif" />
      <mj-button background-color="#FF8C42" border-radius="8px" />
      <mj-text font-size="16px" line-height="1.5" color="#2D3748" />
    </mj-attributes>
  </mj-head>
  <mj-body background-color="#F7FAFC">
    <!-- Header -->
    <mj-include path="./components/header.mjml" />

    <!-- Content Block -->
    {% block content %}{% endblock %}

    <!-- Footer -->
    <mj-include path="./components/footer.mjml" />
  </mj-body>
</mjml>

6.3 Ejemplo: welcome.mjml (con Twig)

{% extends "base.mjml" %}
{% block content %}
<mj-section>
  <mj-column>
    <mj-text font-size="28px" font-weight="bold">
      ¡Bienvenido/a, {{ user.name }}!
    </mj-text>
    <mj-text>
      Estamos encantados de tenerte en {{ vertical_name }}.
      Tu camino hacia {{ goal_description }} empieza ahora.
    </mj-text>
    <mj-button href="{{ first_action_url }}">
      {{ first_action_label }}
    </mj-button>
  </mj-column>
</mj-section>
{% endblock %}

6.4 Compilación MJML

Los templates MJML se compilan a HTML en build time:

# package.json script
"scripts": {
  "build:email": "mjml templates/mjml/**/*.mjml -o templates/html/"
}
 
7. Secuencias por Vertical

7.1 Secuencia: onboarding_job_seeker

Step	Delay / Template / Asunto
1	0 min | welcome_job_seeker | ¡Tu camino al empleo empieza hoy!
2	1 día | profile_reminder | Completa tu perfil en 5 minutos
3	2 días | job_matches_preview | [X] ofertas coinciden con tu perfil
4	4 días | diagnostic_invite | Tu Diagnóstico de Empleabilidad
5	7 días | job_alerts_setup | Configura tus alertas de empleo

7.2 Secuencia: onboarding_producer

Step	Delay / Template / Asunto
1	0 min | welcome_producer | ¡Bienvenido/a a AgroConecta!
2	1 día | store_setup | Configura tu tienda en 10 minutos
3	2 días | first_product | Sube tu primer producto
4	4 días | traceability_qr | Activa la trazabilidad QR
5	7 días | marketplace_stats | [X] compradores buscan productos como los tuyos

7.3 Secuencia: reactivation_inactive

Step	Delay / Template / Asunto
1	0 min (trigger: inactive_14d) | reactivation_miss_you | Te echamos de menos
2	7 días | reactivation_fomo | Mira lo que te estás perdiendo
3	14 días | reactivation_last_chance | Último aviso: ¿seguimos juntos?
4	21 días | reactivation_archive | Tu cuenta será archivada

7.4 Secuencia: churn_prevention

Step	Delay / Template / Asunto
1	0 min (trigger: cancel_requested) | churn_survey | Hemos visto que quieres irte...
2	3 días | churn_offer | Oferta especial para quedarte
3	7 días | churn_call | ¿Podemos hablar?
 
8. Analytics y Métricas

8.1 Dashboard de Email (Views)

Crear Views en Drupal para visualizar métricas desde email_tracking:

Métrica	Query / Cálculo
Total Enviados	COUNT(event_type = sent)
Delivery Rate	COUNT(delivered) / COUNT(sent) * 100
Open Rate	COUNT(DISTINCT opened) / COUNT(delivered) * 100
Click Rate	COUNT(DISTINCT clicked) / COUNT(delivered) * 100
Bounce Rate	COUNT(bounced) / COUNT(sent) * 100
Unsubscribe Rate	COUNT(unsubscribed) / COUNT(delivered) * 100
Sequence Completion	COUNT(enrollment.status = completed) / COUNT(enrollments) * 100

8.2 Integración con FOC

Las métricas de email se sincronizan con el Financial Operations Center para:
• Correlacionar campañas de email con conversiones
• Calcular ROI de secuencias (coste envío vs revenue generado)
• Identificar secuencias de alto rendimiento
• Alertar si delivery rate cae por debajo del umbral
 
9. Roadmap de Implementación

Fase	Timeline / Entregables
Fase 1: Core (Sem 1-2)	Módulo jaraba_email, entidades, SendGridClient. Integración API. Webhook handler.
Fase 2: Templates (Sem 3)	Sistema MJML + Twig. Compilación. Templates base y transaccionales.
Fase 3: Secuencias (Sem 4-5)	SequenceManager, ECA flows para enroll/process/complete. Admin UI.
Fase 4: Verticales (Sem 6-7)	Templates por avatar. Secuencias Empleabilidad, Emprendimiento, Agro.
Fase 5: Analytics (Sem 8)	Dashboard métricas. Integración FOC. Alertas de deliverability.
Fase 6: Optimización (Sem 9+)	A/B testing manual. Refinamiento de secuencias. Documentación.

9.1 Inversión Estimada

Concepto	Estimación
Desarrollo módulo jaraba_email	€8,000-12,000
Diseño templates MJML (25+)	€1,500-2,500
Integración ECA y testing	€2,000-3,000
SendGrid Pro (anual)	~€1,000/año
Total Setup	€11,500-17,500
Total Año 2+ (solo SendGrid)	~€1,000/año

9.2 Comparativa de Coste Total 3 Años

Escenario	ActiveCampaign Pro	SendGrid + ECA
Setup inicial	€2,500	€14,500
Año 1 (5K contactos)	€2,500 + €2,460 = €4,960	€14,500 + €500 = €15,000
Año 2 (15K contactos)	€5,400	€800
Año 3 (30K contactos)	€8,500	€1,000
TOTAL 3 AÑOS	€18,860	€16,800
Break-even	-	Año 2.5 aprox

Conclusión: SendGrid + ECA es más económico a partir de ~15K contactos y proporciona control total sobre la lógica de automatización.


--- Fin del Documento ---
