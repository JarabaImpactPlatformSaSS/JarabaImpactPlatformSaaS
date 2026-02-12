JARABA IMPACT PLATFORM
Especificación de Flujos ECA
Event-Condition-Action Automation
Workflows y Playbooks Automatizados
Enero 2026
 
1. Introducción al Sistema ECA
El módulo ECA (Event-Condition-Action) de Drupal permite crear automatizaciones complejas sin escribir código PHP. La Jaraba Impact Platform utiliza ECA para orquestar flujos de negocio críticos: desde el onboarding de usuarios hasta la prevención de churn.
1.1 Arquitectura ECA
Componente	Descripción	Ejemplo
Event (Evento)	Disparador que inicia el flujo	user_insert, commerce_order_complete, cron
Condition (Condición)	Validación que debe cumplirse	user.role == 'merchant', order.total > 100
Action (Acción)	Operación que se ejecuta	Enviar email, crear entidad, webhook
1.2 Ventajas de ECA sobre código PHP
•	Mantenibilidad: Los flujos se definen en YAML, versionables en Git
•	Visibilidad: Panel visual para ver y editar flujos sin acceso al código
•	Testabilidad: Cada flujo puede probarse de forma aislada
•	Extensibilidad: Nuevos eventos, condiciones y acciones via plugins
1.3 Módulos ECA Instalados
Módulo	Versión	Función
eca	^2.0	Core del sistema ECA
eca_content	^2.0	Eventos de contenido (node, media)
eca_user	^2.0	Eventos de usuario (login, register, update)
eca_form	^2.0	Eventos de formularios
eca_queue	^2.0	Procesamiento asíncrono en cola
eca_webhook	^2.0	Envío de webhooks salientes
eca_tamper	^2.0	Transformación de datos
bpmn_io	^2.0	Editor visual BPMN (opcional)
 
2. Catálogo de Flujos del Ecosistema
Los flujos ECA se organizan por dominio funcional. Cada flujo tiene un identificador único para trazabilidad.
ID	Nombre	Dominio	Trigger	Prioridad
ECA-USR-001	Onboarding Usuario Nuevo	Usuarios	user_insert	Alta
ECA-USR-002	Asignación Rol por Diagnóstico	Usuarios	diagnostic_completed	Alta
ECA-USR-003	Welcome Email Personalizado	Usuarios	user_insert	Media
ECA-ORD-001	Orden Completada	Commerce	commerce_order_complete	Crítica
ECA-ORD-002	Carrito Abandonado	Commerce	cron (24h)	Media
ECA-ORD-003	Procesamiento de Reembolso	Commerce	refund_created	Alta
ECA-FIN-001	Alerta Churn Spike	FOC	metric_threshold	Crítica
ECA-FIN-002	Revenue Acceleration	FOC	metric_opportunity	Alta
ECA-FIN-003	Grant Burn Rate Warning	FOC	metric_threshold	Crítica
ECA-TEN-001	Tenant Onboarding	Tenants	group_insert	Alta
ECA-TEN-002	Stripe Connect Completado	Tenants	stripe_account_updated	Alta
ECA-AI-001	Query Log y Analytics	IA/RAG	ai_query_completed	Baja
ECA-WH-001	Dispatcher de Webhooks	Integraciones	entity_update	Media
 
3. Flujos de Gestión de Usuarios
3.1 ECA-USR-001: Onboarding Usuario Nuevo
Trigger: Usuario se registra en la plataforma
# config/eca/eca.model.user_onboarding.yml
id: user_onboarding
label: 'Onboarding Usuario Nuevo'
status: true
version: '1.0'

events:
  - plugin: 'eca_user:user_insert'
    label: 'Usuario creado'

conditions:
  - plugin: 'eca_user:user_has_role'
    settings:
      role: 'authenticated'
    negate: false

actions:
  # 1. Crear perfil extendido
  - plugin: 'eca_content:entity_create'
    settings:
      entity_type: 'user_profile_extended'
      values:
        user_id: '[user:uid]'
        avatar_type: 'pending'
        health_score: 50
        impact_credits: 0

  # 2. Asignar al tenant por defecto (si aplica)
  - plugin: 'eca_group:add_member'
    settings:
      group_id: '[site:default_tenant]'
      user_id: '[user:uid]'
      role: 'group_member'

  # 3. Webhook a ActiveCampaign
  - plugin: 'eca_webhook:send'
    settings:
      url: 'https://[account].api-us1.com/api/3/contacts'
      method: 'POST'
      headers:
        Api-Token: '[env:ACTIVECAMPAIGN_API_KEY]'
      body:
        contact:
          email: '[user:mail]'
          firstName: '[user:field_first_name]'
          lastName: '[user:field_last_name]'

  # 4. Log de auditoría
  - plugin: 'eca:log_message'
    settings:
      level: 'info'
      message: 'Onboarding completado para usuario [user:uid]'
 
3.2 ECA-USR-002: Asignación Rol por Diagnóstico Express
Trigger: Usuario completa el Diagnóstico Express post-registro
# config/eca/eca.model.diagnostic_role_assignment.yml
id: diagnostic_role_assignment
label: 'Asignación Rol por Diagnóstico'
status: true

events:
  - plugin: 'eca_content:entity_insert'
    settings:
      entity_type: 'diagnostic_express_result'

actions:
  # Asignar rol según score
  - plugin: 'eca:switch'
    settings:
      value: '[diagnostic:score]'
      cases:
        - condition: 'value <= 4'
          actions:
            - plugin: 'eca_user:user_role_add'
              settings:
                user: '[diagnostic:user_id]'
                role: 'empleabilidad_urgente'
        - condition: 'value >= 5 AND value <= 6'
          actions:
            - plugin: 'eca_user:user_role_add'
              settings:
                user: '[diagnostic:user_id]'
                role: 'empleabilidad_desarrollo'
        - condition: 'value >= 7'
          actions:
            - plugin: 'eca_user:user_role_add'
              settings:
                user: '[diagnostic:user_id]'
                role: 'empleabilidad_optimizacion'

  # Webhook a ActiveCampaign con tags
  - plugin: 'eca_webhook:send'
    settings:
      url: 'https://[account].api-us1.com/api/3/contactTags'
      method: 'POST'
      body:
        contactTag:
          contact: '[user:ac_contact_id]'
          tag: 'diagnostico_express'

  # Tag por gap principal
  - plugin: 'eca_webhook:send'
    settings:
      url: 'https://[account].api-us1.com/api/3/contactTags'
      method: 'POST'
      body:
        contactTag:
          contact: '[user:ac_contact_id]'
          tag: 'gap_[diagnostic:primary_gap]'

  # Desbloquear contenido según gap
  - plugin: 'eca_content:entity_create'
    settings:
      entity_type: 'content_unlock'
      values:
        user_id: '[diagnostic:user_id]'
        content_category: '[diagnostic:primary_gap]'
        unlock_date: '[current:timestamp]'

  # Asignar créditos de impacto iniciales
  - plugin: 'eca:entity_update'
    settings:
      entity_type: 'user_profile_extended'
      conditions:
        user_id: '[diagnostic:user_id]'
      values:
        impact_credits: '[profile:impact_credits] + 50'
 
4. Flujos de Commerce
4.1 ECA-ORD-001: Orden Completada
Trigger: Pago procesado exitosamente vía Stripe
# config/eca/eca.model.order_completed.yml
id: order_completed
label: 'Procesamiento Orden Completada'
status: true

events:
  - plugin: 'commerce_order:order_paid'
    label: 'Orden pagada'

conditions:
  - plugin: 'eca:entity_field_value'
    settings:
      field: 'state'
      value: 'completed'

actions:
  # 1. Crear transacción financiera (inmutable)
  - plugin: 'eca_content:entity_create'
    settings:
      entity_type: 'financial_transaction'
      values:
        uuid: '[uuid:generate]'
        tenant_id: '[order:store:owner:tenant_id]'
        vertical_id: '[order:store:vertical]'
        transaction_type: 'income_sale'
        motor_type: 'private'
        amount: '[order:total_price:number]'
        currency: '[order:total_price:currency_code]'
        platform_fee: '[order:application_fee]'
        processor_fee: '[order:stripe_fee]'
        net_amount: '[order:net_amount]'
        external_id: '[order:stripe_payment_intent]'
        reference_type: 'commerce_order'
        reference_id: '[order:order_id]'

  # 2. Actualizar stock
  - plugin: 'commerce_stock:decrement'
    settings:
      order_id: '[order:order_id]'

  # 3. Webhook externo (order.completed)
  - plugin: 'jaraba_webhook:dispatch'
    settings:
      event_type: 'order.completed'
      tenant_id: '[order:store:owner:tenant_id]'
      payload:
        order_id: '[order:order_id]'
        total: '[order:total_price:number]'
        currency: '[order:total_price:currency_code]'
        customer_email: '[order:email]'
        items: '[order:items:json]'

  # 4. Email de confirmación
  - plugin: 'eca_mail:send'
    settings:
      to: '[order:email]'
      subject: 'Pedido #[order:order_number] confirmado'
      template: 'order_confirmation'
4.2 ECA-ORD-002: Carrito Abandonado
Trigger: Cron cada hora, detecta carritos sin actividad >24h
# config/eca/eca.model.cart_abandoned.yml
id: cart_abandoned
label: 'Recuperación Carrito Abandonado'
status: true

events:
  - plugin: 'eca:cron'
    settings:
      interval: '0 * * * *'  # Cada hora

actions:
  # Query carritos abandonados
  - plugin: 'eca:entity_query'
    settings:
      entity_type: 'commerce_order'
      conditions:
        state: 'draft'
        changed: '< [timestamp:-24 hours]'
        cart_abandoned_notified: 'NULL'
      result_key: 'abandoned_carts'

  # Loop por cada carrito
  - plugin: 'eca:foreach'
    settings:
      items: '[abandoned_carts]'
      item_key: 'cart'
    actions:
      # Webhook cart.abandoned
      - plugin: 'jaraba_webhook:dispatch'
        settings:
          event_type: 'cart.abandoned'
          tenant_id: '[cart:store:owner:tenant_id]'
          payload:
            cart_id: '[cart:order_id]'
            customer_email: '[cart:email]'
            total: '[cart:total_price:number]'
            items: '[cart:items:json]'
            recovery_url: '[site:url]/cart/[cart:order_id]/recover'

      # Marcar como notificado
      - plugin: 'eca:entity_update'
        settings:
          entity: '[cart]'
          values:
            cart_abandoned_notified: '[current:timestamp]'
 
5. Playbooks Financieros (FOC)
Los playbooks son flujos ECA complejos que implementan acciones prescriptivas basadas en métricas del FOC. No solo alertan, sino que ejecutan acciones correctivas.
5.1 Matriz de Alertas Financieras
Alerta	Trigger	Severidad	Acción ECA Automatizada
Churn Spike	Churn > 5% mensual	🔴 Crítica	Crear tarea CRM + Secuencia retención AC
LTV:CAC Comprimido	Ratio < 3:1	🟡 Advertencia	Alerta dashboard + Review campañas
Gross Margin Drop	GM < 70%	🔴 Crítica	Auditar COGS + Review cost allocation
Grant Burn Rate	> tiempo elapsed	🔴 Crítica	Alerta + Congelar partidas no esenciales
Runway Warning	< 12 meses	🔴 Crítica	Iniciar fundraising + Reducir burn
NRR Below Target	NRR < 100%	🟡 Advertencia	Trigger upsell campaigns
Noisy Neighbor	Tenant GM < 20%	🟡 Advertencia	Revisar contrato + Renegociar pricing
5.2 ECA-FIN-001: Playbook Churn Prevention
# config/eca/eca.model.churn_prevention.yml
id: churn_prevention_playbook
label: 'Playbook: Prevención de Churn'
status: true

events:
  - plugin: 'jaraba_foc:metric_threshold'
    settings:
      metric: 'revenue_churn_rate'
      operator: '>'
      threshold: 5
      period: 'monthly'

actions:
  # 1. Identificar tenants at-risk
  - plugin: 'eca:entity_query'
    settings:
      entity_type: 'tenant'
      conditions:
        health_score: '< 60'
        usage_trend: 'declining'
      result_key: 'at_risk_tenants'

  # 2. Loop por cada tenant at-risk
  - plugin: 'eca:foreach'
    settings:
      items: '[at_risk_tenants]'
      item_key: 'tenant'
    actions:
      # 2.1 Crear tarea en CRM para CS Manager
      - plugin: 'eca_webhook:send'
        settings:
          url: '[env:CRM_API_URL]/tasks'
          method: 'POST'
          body:
            title: 'URGENTE: Riesgo de Churn - [tenant:name]'
            description: 'Health Score: [tenant:health_score]. Revisar engagement.'
            assigned_to: '[tenant:cs_manager_id]'
            priority: 'high'
            due_date: '[timestamp:+3 days]'

      # 2.2 Enrollar en secuencia de reactivación (ActiveCampaign)
      - plugin: 'eca_webhook:send'
        settings:
          url: 'https://[account].api-us1.com/api/3/contactAutomations'
          method: 'POST'
          body:
            contactAutomation:
              contact: '[tenant:owner:ac_contact_id]'
              automation: '[env:AC_CHURN_PREVENTION_AUTOMATION]'

      # 2.3 Registrar en log de intervenciones
      - plugin: 'eca_content:entity_create'
        settings:
          entity_type: 'churn_intervention_log'
          values:
            tenant_id: '[tenant:id]'
            intervention_type: 'automated_prevention'
            health_score_at_intervention: '[tenant:health_score]'
            timestamp: '[current:timestamp]'

  # 3. Alerta en dashboard
  - plugin: 'jaraba_foc:create_alert'
    settings:
      severity: 'critical'
      title: 'Churn Spike Detectado'
      message: 'Churn rate: [metric:value]%. Tenants at-risk: [at_risk_tenants:count]'
      dashboard: 'foc_executive'
 
5.3 ECA-FIN-002: Playbook Revenue Acceleration
# config/eca/eca.model.revenue_acceleration.yml
id: revenue_acceleration_playbook
label: 'Playbook: Aceleración de Revenue'
status: true

events:
  - plugin: 'jaraba_foc:expansion_opportunity'
    settings:
      min_feature_gap: 3  # Features no utilizadas
      min_usage_score: 70  # Alto engagement

actions:
  # 1. Identificar tenants con oportunidad de expansion
  - plugin: 'eca:entity_query'
    settings:
      entity_type: 'tenant'
      conditions:
        plan_type: 'starter|professional'
        health_score: '>= 70'
        features_unused_count: '>= 3'
      result_key: 'expansion_candidates'

  # 2. Para cada candidato
  - plugin: 'eca:foreach'
    settings:
      items: '[expansion_candidates]'
      item_key: 'tenant'
    actions:
      # 2.1 Calcular features relevantes no usadas
      - plugin: 'jaraba_foc:analyze_feature_gap'
        settings:
          tenant_id: '[tenant:id]'
          result_key: 'feature_analysis'

      # 2.2 Crear oportunidad en CRM
      - plugin: 'eca_webhook:send'
        settings:
          url: '[env:CRM_API_URL]/opportunities'
          method: 'POST'
          body:
            name: 'Upsell - [tenant:name]'
            value: '[feature_analysis:estimated_value]'
            stage: 'qualification'
            notes: 'Features recomendadas: [feature_analysis:recommendations]'

      # 2.3 Enrollar en secuencia de upsell
      - plugin: 'eca_webhook:send'
        settings:
          url: 'https://[account].api-us1.com/api/3/contactAutomations'
          body:
            contactAutomation:
              contact: '[tenant:owner:ac_contact_id]'
              automation: '[env:AC_UPSELL_AUTOMATION]'

  # 3. Notificación a Sales
  - plugin: 'eca:notify'
    settings:
      channel: 'slack'
      webhook: '[env:SLACK_SALES_WEBHOOK]'
      message: '💰 [expansion_candidates:count] oportunidades de upsell identificadas'
 
6. Flujos de Gestión de Tenants
6.1 ECA-TEN-001: Tenant Onboarding
# config/eca/eca.model.tenant_onboarding.yml
id: tenant_onboarding
label: 'Onboarding Nuevo Tenant'
status: true

events:
  - plugin: 'eca_group:group_insert'
    settings:
      group_type: 'tenant_commercial|tenant_training|tenant_institutional'

actions:
  # 1. Crear entidad tenant con defaults
  - plugin: 'eca_content:entity_create'
    settings:
      entity_type: 'tenant'
      values:
        group_id: '[group:id]'
        name: '[group:label]'
        machine_name: '[group:label:machine_name]'
        vertical_id: '[group:field_vertical:target_id]'
        plan_type: 'starter'
        platform_fee_percent: 8.00
        status: 'active'

  # 2. Crear Store en Commerce (si es comercial)
  - plugin: 'eca:condition'
    settings:
      condition: '[group:type] == "tenant_commercial"'
    actions:
      - plugin: 'commerce_store:create'
        settings:
          type: 'online'
          name: 'Tienda [group:label]'
          owner: '[group:owner:uid]'

  # 3. Configuración de theme por defecto
  - plugin: 'eca_content:entity_create'
    settings:
      entity_type: 'tenant_theme_config'
      values:
        tenant_id: '[tenant:id]'
        color_primary: '#FF8C42'
        color_secondary: '#00A9A5'
        font_headings: 'Montserrat'

  # 4. Webhook tenant.onboarded
  - plugin: 'jaraba_webhook:dispatch'
    settings:
      event_type: 'tenant.onboarded'
      payload:
        tenant_id: '[tenant:id]'
        name: '[tenant:name]'
        vertical: '[tenant:vertical_id]'
        plan: '[tenant:plan_type]'

  # 5. Email de bienvenida al propietario
  - plugin: 'eca_mail:send'
    settings:
      to: '[group:owner:mail]'
      subject: 'Bienvenido a Jaraba Impact Platform'
      template: 'tenant_welcome'
6.2 ECA-TEN-002: Stripe Connect Completado
# config/eca/eca.model.stripe_onboarding_complete.yml
id: stripe_onboarding_complete
label: 'Stripe Connect Onboarding Completado'
status: true

events:
  - plugin: 'jaraba_stripe:account_updated'
    settings:
      event: 'account.updated'

conditions:
  - plugin: 'eca:value_compare'
    settings:
      value1: '[stripe_account:charges_enabled]'
      value2: true
      operator: '=='

actions:
  # 1. Actualizar tenant
  - plugin: 'eca:entity_update'
    settings:
      entity_type: 'tenant'
      conditions:
        stripe_account_id: '[stripe_account:id]'
      values:
        stripe_onboarding_complete: true

  # 2. Habilitar funcionalidades de venta
  - plugin: 'eca_group:grant_permission'
    settings:
      group_id: '[tenant:group_id]'
      permission: 'create commerce_product'

  # 3. Notificar al propietario
  - plugin: 'eca_mail:send'
    settings:
      to: '[tenant:owner:mail]'
      subject: '¡Tu cuenta de pagos está lista!'
      template: 'stripe_ready'

  # 4. Log
  - plugin: 'eca:log_message'
    settings:
      level: 'info'
      message: 'Stripe onboarding completado para tenant [tenant:id]'
 
7. Sistema de Webhooks Salientes
7.1 ECA-WH-001: Dispatcher de Webhooks
El dispatcher centraliza todos los webhooks salientes, aplicando firma HMAC y reintentos automáticos.
Evento	Trigger	Payload Principal
product.created	Producto creado en Commerce	id, sku, title, price, stock, images, seo
product.updated	Producto actualizado	id, changed_fields, previous_values
order.completed	Pago procesado exitosamente	order_id, total, items, customer
order.cancelled	Pedido cancelado	order_id, reason, refund_amount
cart.abandoned	Carrito sin actividad >24h	cart_id, items, recovery_url
user.registered	Usuario nuevo registrado	user_id, email, roles
diagnostic.completed	Diagnóstico Express completado	user_id, score, profile_type, gap
tenant.onboarded	Nuevo tenant creado	tenant_id, name, vertical, plan
alert.triggered	Alerta FOC disparada	alert_type, severity, message, data
7.2 Firma y Seguridad de Webhooks
# Plugin de acción personalizado: jaraba_webhook:dispatch
# Implementación en jaraba_webhooks/src/Plugin/ECA/Action/WebhookDispatch.php

public function execute(): void {
  $event_type = $this->configuration['event_type'];
  $tenant_id = $this->configuration['tenant_id'];
  $payload = $this->configuration['payload'];
  
  // Obtener endpoints suscritos a este evento
  $endpoints = $this->webhookEndpointStorage->loadByTenantAndEvent(
    $tenant_id, 
    $event_type
  );
  
  foreach ($endpoints as $endpoint) {
    // Preparar payload con metadata
    $full_payload = [
      'event' => $event_type,
      'timestamp' => time(),
      'tenant_id' => $tenant_id,
      'data' => $payload,
    ];
    
    // Generar firma HMAC-SHA256
    $timestamp = time();
    $signature = hash_hmac(
      'sha256',
      $timestamp . '.' . json_encode($full_payload),
      $endpoint->getSecret()
    );
    
    // Encolar para envío asíncrono
    $this->queue->createItem([
      'url' => $endpoint->getUrl(),
      'payload' => $full_payload,
      'headers' => [
        'X-Jaraba-Event' => $event_type,
        'X-Jaraba-Signature' => $signature,
        'X-Jaraba-Timestamp' => $timestamp,
        'X-Jaraba-Delivery-ID' => Uuid::uuid4()->toString(),
      ],
      'retry_count' => 0,
      'max_retries' => 3,
    ]);
  }
}
 
8. Integraciones con Servicios Externos
8.1 ActiveCampaign
Acción ECA	Endpoint API	Uso
Crear/Actualizar contacto	POST /api/3/contacts	Sincronizar usuarios nuevos
Añadir tag	POST /api/3/contactTags	Segmentación por diagnóstico, gap, plan
Trigger automation	POST /api/3/contactAutomations	Secuencias de onboarding, nurturing, churn
Update custom field	PUT /api/3/contacts/{id}	Score, health_score, last_activity
Track event	POST /api/3/tracking/event	Eventos custom (course_completed, etc.)
8.2 Make.com (Escenarios Predefinidos)
Escenario	Trigger	Acciones
sync_amazon	product.created/updated	Sincronizar catálogo con Amazon Seller Central
sync_ebay	product.created/updated	Sincronizar catálogo con eBay
sync_meta_catalog	product.created/updated	Actualizar Facebook/Instagram Shops
order_notification	order.completed	Notificación multicanal (Slack, SMS, email)
cart_recovery	cart.abandoned	Secuencia WhatsApp + Email de recuperación
review_request	order.completed +7 días	Solicitar reseña en Google/Trustpilot
8.3 Slack (Notificaciones Operativas)
# config/eca/eca.model.slack_notifications.yml
id: slack_critical_alerts
label: 'Notificaciones Críticas a Slack'
status: true

events:
  - plugin: 'jaraba_foc:alert_created'
    settings:
      severity: 'critical'

actions:
  - plugin: 'eca_webhook:send'
    settings:
      url: '[env:SLACK_ALERTS_WEBHOOK]'
      method: 'POST'
      body:
        blocks:
          - type: 'header'
            text:
              type: 'plain_text'
              text: '🚨 ALERTA CRÍTICA: [alert:title]'
          - type: 'section'
            text:
              type: 'mrkdwn'
              text: '[alert:message]'
          - type: 'context'
            elements:
              - type: 'mrkdwn'
                text: 'Severidad: *[alert:severity]* | Dashboard: [alert:dashboard_url]'
 
9. Testing y Debugging de Flujos ECA
9.1 Modo Debug
Activar logging detallado en settings.php:
// settings.local.php (solo en desarrollo)
$config['eca.settings']['debug_mode'] = TRUE;
$config['eca.settings']['log_level'] = 'debug';
$config['eca.settings']['log_token_values'] = TRUE;
9.2 Testing Manual
1.	Acceder a /admin/config/workflow/eca
2.	Seleccionar el modelo a probar
3.	Click en "Execute manually" (si disponible)
4.	Revisar logs en /admin/reports/dblog
9.3 Testing Automatizado
// tests/src/Kernel/EcaFlowTest.php
class EcaUserOnboardingTest extends KernelTestBase {

  public function testUserOnboardingCreatesProfile() {
    // Arrange: crear usuario
    $user = User::create(['name' => 'test', 'mail' => 'test@example.com']);
    
    // Act: guardar (dispara ECA)
    $user->save();
    
    // Assert: perfil extendido creado
    $profile = $this->entityTypeManager
      ->getStorage('user_profile_extended')
      ->loadByProperties(['user_id' => $user->id()]);
    
    $this->assertNotEmpty($profile);
    $this->assertEquals(50, reset($profile)->get('health_score')->value);
  }
}
 
Apéndice: Checklist de Implementación
•	[ ] Módulos ECA instalados (eca, eca_content, eca_user, eca_webhook)
•	[ ] Variables de entorno configuradas (API keys, webhooks)
•	[ ] ECA-USR-001: Onboarding Usuario implementado y probado
•	[ ] ECA-USR-002: Asignación Rol por Diagnóstico implementado
•	[ ] ECA-ORD-001: Orden Completada con financial_transaction
•	[ ] ECA-ORD-002: Carrito Abandonado con cron configurado
•	[ ] ECA-FIN-001: Playbook Churn Prevention activo
•	[ ] ECA-FIN-002: Playbook Revenue Acceleration activo
•	[ ] ECA-TEN-001: Tenant Onboarding probado
•	[ ] ECA-TEN-002: Stripe Connect Completado integrado
•	[ ] Webhooks salientes con firma HMAC verificada
•	[ ] Integración ActiveCampaign funcionando
•	[ ] Escenarios Make.com configurados
•	[ ] Alertas Slack operativas
•	[ ] Tests automatizados pasando
— Fin del Documento —
Jaraba Impact Platform © 2026
