EMAIL TEMPLATES
Sistema de Comunicaciones Transaccionales

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Código:	136_Platform_Email_Templates
ESP:	ActiveCampaign / SendGrid
 
1. Catálogo de Emails
1.1 Autenticación
ID	Nombre	Trigger	Variables
AUTH_001	Verificación de email	Registro completado	{{name}}, {{verify_url}}
AUTH_002	Bienvenida	Email verificado	{{name}}, {{dashboard_url}}
AUTH_003	Reset de contraseña	Solicitud reset	{{name}}, {{reset_url}}, {{expires}}
AUTH_004	Contraseña cambiada	Password actualizado	{{name}}, {{date}}
AUTH_005	Nuevo login detectado	Login desde nuevo dispositivo	{{name}}, {{device}}, {{location}}, {{date}}
1.2 Billing
ID	Nombre	Trigger	Variables
BILL_001	Factura pagada	invoice.paid webhook	{{name}}, {{invoice_number}}, {{amount}}, {{pdf_url}}
BILL_002	Pago fallido	invoice.payment_failed	{{name}}, {{amount}}, {{retry_date}}, {{update_payment_url}}
BILL_003	Suscripción activada	subscription.created	{{name}}, {{plan}}, {{price}}, {{dashboard_url}}
BILL_004	Upgrade confirmado	Plan upgrade	{{name}}, {{old_plan}}, {{new_plan}}, {{new_features}}
BILL_005	Trial expira pronto	3 días antes de trial end	{{name}}, {{trial_end}}, {{plan}}, {{subscribe_url}}
BILL_006	Cancelación confirmada	subscription.canceled	{{name}}, {{end_date}}, {{feedback_url}}
BILL_007	Dunning - Recordatorio	Día 3 de dunning	{{name}}, {{amount}}, {{update_payment_url}}
BILL_008	Dunning - Urgente	Día 7 de dunning	{{name}}, {{amount}}, {{features_lost}}
BILL_009	Dunning - Último aviso	Día 14 de dunning	{{name}}, {{cancel_date}}
1.3 Marketplace (AgroConecta/ComercioConecta)
ID	Nombre	Destinatario	Variables
MKT_001	Pedido confirmado	Comprador	{{name}}, {{order_id}}, {{items}}, {{total}}
MKT_002	Nuevo pedido recibido	Vendedor	{{seller_name}}, {{order_id}}, {{items}}, {{buyer_name}}
MKT_003	Pedido enviado	Comprador	{{name}}, {{order_id}}, {{tracking}}, {{carrier}}
MKT_004	Pedido entregado	Comprador	{{name}}, {{order_id}}, {{review_url}}
MKT_005	Pago recibido	Vendedor	{{seller_name}}, {{amount}}, {{order_id}}, {{payout_date}}
MKT_006	Nueva reseña	Vendedor	{{seller_name}}, {{rating}}, {{review_text}}, {{product}}
1.4 Empleabilidad
ID	Nombre	Destinatario	Variables
EMP_001	Nueva oferta matching	Candidato	{{name}}, {{job_title}}, {{company}}, {{match_score}}
EMP_002	Aplicación recibida	Candidato	{{name}}, {{job_title}}, {{company}}
EMP_003	Nueva aplicación	Empleador	{{employer_name}}, {{candidate_name}}, {{job_title}}
EMP_004	Candidato shortlisted	Candidato	{{name}}, {{job_title}}, {{company}}
EMP_005	Oferta expirada	Empleador	{{employer_name}}, {{job_title}}, {{applications_count}}
 
2. Template Base (MJML)
<!-- templates/base.mjml -->
<mjml>
  <mj-head>
    <mj-title>{{ subject }}</mj-title>
    <mj-preview>{{ preview_text }}</mj-preview>
    <mj-attributes>
      <mj-all font-family="Arial, sans-serif" />
      <mj-text font-size="16px" line-height="1.5" color="#333333" />
      <mj-button background-color="#1B5E4F" border-radius="8px" font-size="16px" />
    </mj-attributes>
    <mj-style>
      .footer-link { color: #666666; text-decoration: none; }
      .highlight { color: #1B5E4F; font-weight: bold; }
    </mj-style>
  </mj-head>
  
  <mj-body background-color="#f5f5f5">
    <!-- Header -->
    <mj-section background-color="#1B5E4F" padding="20px">
      <mj-column>
        <mj-image src="{{ logo_url }}" alt="Jaraba Impact" width="150px" />
      </mj-column>
    </mj-section>
    
    <!-- Content -->
    <mj-section background-color="#ffffff" padding="40px 30px">
      <mj-column>
        {% block content %}{% endblock %}
      </mj-column>
    </mj-section>
    
    <!-- Footer -->
    <mj-section background-color="#f5f5f5" padding="20px">
      <mj-column>
        <mj-text align="center" font-size="12px" color="#666666">
          © {{ current_year }} Jaraba Impact S.L. | 
          <a href="{{ unsubscribe_url }}" class="footer-link">Gestionar preferencias</a>
        </mj-text>
        <mj-text align="center" font-size="12px" color="#666666">
          Calle Ejemplo 123, 14001 Córdoba, España
        </mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
3. Ejemplo: Email de Factura
<!-- templates/billing/invoice_paid.mjml -->
{% extends "base.mjml" %}
 
{% block content %}
  <mj-text font-size="24px" font-weight="bold" padding-bottom="20px">
    Factura pagada ✓
  </mj-text>
  
  <mj-text>
    Hola {{ name }},
  </mj-text>
  
  <mj-text>
    Hemos recibido tu pago correctamente. Aquí tienes los detalles:
  </mj-text>
  
  <mj-table>
    <tr>
      <td style="padding: 10px; border-bottom: 1px solid #eee;">Número de factura</td>
      <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">
        <strong>{{ invoice_number }}</strong>
      </td>
    </tr>
    <tr>
      <td style="padding: 10px; border-bottom: 1px solid #eee;">Fecha</td>
      <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">
        {{ invoice_date }}
      </td>
    </tr>
    <tr>
      <td style="padding: 10px; border-bottom: 1px solid #eee;">Plan</td>
      <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">
        {{ plan_name }}
      </td>
    </tr>
    <tr>
      <td style="padding: 10px; font-weight: bold;">Total</td>
      <td style="padding: 10px; text-align: right; font-size: 20px; color: #1B5E4F;">
        <strong>{{ amount }}€</strong>
      </td>
    </tr>
  </mj-table>
  
  <mj-button href="{{ pdf_url }}" padding-top="30px">
    Descargar factura PDF
  </mj-button>
  
  <mj-text padding-top="30px" font-size="14px" color="#666666">
    Si tienes alguna pregunta sobre esta factura, responde a este email 
    o contacta con nosotros en soporte@jarabaimpact.com
  </mj-text>
{% endblock %}
 
4. Configuración ESP
4.1 ActiveCampaign
•	API Key en variable de entorno ACTIVECAMPAIGN_API_KEY
•	Listas segmentadas por vertical y tipo de usuario
•	Automations para secuencias (onboarding, dunning)
•	Tags automáticos basados en acciones
4.2 SendGrid (Transaccional)
•	API Key dedicada para emails transaccionales
•	Domain authentication configurado (SPF, DKIM, DMARC)
•	Dedicated IP para mejor deliverability
•	Event webhooks para tracking (open, click, bounce)
5. Checklist
•	[ ] Diseñar templates MJML para los 25+ emails
•	[ ] Compilar MJML a HTML
•	[ ] Configurar ActiveCampaign con listas y tags
•	[ ] Configurar SendGrid domain authentication
•	[ ] Implementar service de email en Drupal
•	[ ] Conectar triggers (hooks, webhooks)
•	[ ] Test de todos los emails en diferentes clientes
•	[ ] Verificar links de tracking funcionan
•	[ ] Test de unsubscribe

--- Fin del Documento ---
