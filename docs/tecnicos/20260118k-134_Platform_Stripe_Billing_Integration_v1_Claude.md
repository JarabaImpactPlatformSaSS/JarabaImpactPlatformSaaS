STRIPE BILLING INTEGRATION
Sistema de Pagos, Suscripciones y Facturación Multi-Tenant

Stripe Connect • Subscriptions • Usage-Based • Marketplace
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica - Ready for Development
Código:	134_Platform_Stripe_Billing_Integration
Dependencias:	111_UsageBased_Pricing, Stripe API v2024-12
Prioridad:	🔴 CRÍTICO - Bloquea Revenue
 
1. Resumen Ejecutivo
Este documento especifica la integración completa con Stripe para gestionar todos los flujos de pago del ecosistema Jaraba: suscripciones SaaS, comisiones de marketplace, pagos únicos, y facturación con compliance fiscal español.
1.1 Modelo de Revenue del Ecosistema
Fuente de Revenue	Tipo Stripe	Ejemplo
Suscripciones SaaS	Stripe Subscriptions	Tenant paga 79€/mes por plan Growth
Comisiones Marketplace	Stripe Connect (destination)	Jaraba cobra 8% de venta en AgroConecta
Servicios Premium	Stripe Checkout (one-time)	Tenant compra pack de créditos IA
Add-ons	Subscription Items	Tenant añade 5 usuarios extra a 10€/mes
Usage Overage	Metered Billing	Tenant excede límite de API calls

1.2 Stack Tecnológico
Componente	Tecnología	Versión/Config
Payment Gateway	Stripe	API v2024-12-18.acacia
Multi-tenant Payments	Stripe Connect	Platform + Connected Accounts
Subscriptions	Stripe Billing	Subscription + Price objects
Invoicing	Stripe Invoicing	Auto-invoicing enabled
Tax Calculation	Stripe Tax	ES (Spain) tax rates
Customer Portal	Stripe Customer Portal	Embedded + Redirect modes
Webhooks	Stripe Webhooks	Versioned, signed
Drupal Module	stripe_api + custom	jaraba_billing module

1.3 Arquitectura de Alto Nivel
┌─────────────────────────────────────────────────────────────────────────────┐
│                    JARABA BILLING ARCHITECTURE                              │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                         STRIPE PLATFORM                             │   │
│  │                    (Jaraba Impact S.L. Account)                     │   │
│  │                                                                     │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │   │
│  │  │ Subscriptions│  │   Products   │  │   Customers  │              │   │
│  │  │              │  │   & Prices   │  │              │              │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘              │   │
│  │                                                                     │   │
│  │  ┌──────────────────────────────────────────────────────────────┐  │   │
│  │  │                    STRIPE CONNECT                            │  │   │
│  │  │                                                              │  │   │
│  │  │  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐│  │   │
│  │  │  │ Connected  │ │ Connected  │ │ Connected  │ │ Connected  ││  │   │
│  │  │  │ Account    │ │ Account    │ │ Account    │ │ Account    ││  │   │
│  │  │  │ (Bodega)   │ │ (Frutería) │ │ (Abogado)  │ │ (Tienda)   ││  │   │
│  │  │  └────────────┘ └────────────┘ └────────────┘ └────────────┘│  │   │
│  │  │                                                              │  │   │
│  │  │  Marketplace payments flow through platform, split to sellers│  │   │
│  │  └──────────────────────────────────────────────────────────────┘  │   │
│  │                                                                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                    │                                        │
│                                    │ Webhooks                               │
│                                    ▼                                        │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                      DRUPAL (jaraba_billing)                        │   │
│  │                                                                     │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │   │
│  │  │ Subscription │  │   Invoice    │  │   Payment    │              │   │
│  │  │   Manager    │  │   Service    │  │   Processor  │              │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘              │   │
│  │                                                                     │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │   │
│  │  │   Dunning    │  │   Revenue    │  │   Tax        │              │   │
│  │  │   Handler    │  │   Analytics  │  │   Calculator │              │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘              │   │
│  │                                                                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
 
2. Catálogo de Productos Stripe
2.1 Productos de Suscripción SaaS
Cada vertical tiene su propio producto Stripe con múltiples precios (tiers).
Product ID	Nombre	Vertical	Tipo
prod_empleabilidad	Jaraba Empleabilidad	Empleabilidad	SaaS Subscription
prod_emprendimiento	Jaraba Emprendimiento	Emprendimiento	SaaS Subscription
prod_agroconecta	AgroConecta	AgroConecta	SaaS + Marketplace
prod_comercioconecta	ComercioConecta	ComercioConecta	SaaS + Marketplace
prod_serviciosconecta	ServiciosConecta	ServiciosConecta	SaaS + Marketplace

2.2 Precios por Tier (Empleabilidad como ejemplo)
Price ID	Tier	Precio	Billing	Lookup Key
price_emp_starter_monthly	Starter	29€	Mensual	empleabilidad_starter_monthly
price_emp_starter_yearly	Starter	290€	Anual (17% dto)	empleabilidad_starter_yearly
price_emp_growth_monthly	Growth	79€	Mensual	empleabilidad_growth_monthly
price_emp_growth_yearly	Growth	790€	Anual (17% dto)	empleabilidad_growth_yearly
price_emp_pro_monthly	Pro	149€	Mensual	empleabilidad_pro_monthly
price_emp_pro_yearly	Pro	1490€	Anual (17% dto)	empleabilidad_pro_yearly
price_emp_enterprise	Enterprise	Custom	Anual	empleabilidad_enterprise

2.3 Add-ons y Metered Billing
Price ID	Descripción	Precio	Tipo	Unidad
price_addon_users	Usuarios adicionales	10€/usuario/mes	Licensed	per_unit
price_addon_storage	Storage adicional	5€/10GB/mes	Licensed	per_unit
price_addon_api_calls	API calls overage	0.001€/call	Metered	sum
price_addon_ai_credits	Créditos IA	0.01€/crédito	Metered	sum
price_addon_sms	SMS transaccionales	0.05€/SMS	Metered	sum
price_addon_whatsapp	WhatsApp messages	0.08€/mensaje	Metered	sum

2.4 Comisiones de Marketplace
Para verticales con marketplace (Agro, Comercio, Servicios), la comisión se calcula en cada transacción.
Vertical	Comisión Jaraba	Comisión Stripe	Neto Vendedor	Ejemplo (100€ venta)
AgroConecta	8%	1.4% + 0.25€	90.35€	Productor recibe 90.35€
ComercioConecta	6%	1.4% + 0.25€	92.35€	Comercio recibe 92.35€
ServiciosConecta	10%	1.4% + 0.25€	88.35€	Profesional recibe 88.35€
(Enterprise)	Negociable 3-5%	1.4% + 0.25€	~93-95%	Según volumen
 
3. Modelo de Datos
3.1 Entidad: billing_customer
Mapeo entre tenant de Drupal y customer de Stripe.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
tenant_id	INT	Tenant en Drupal	FK groups.id, UNIQUE, NOT NULL
stripe_customer_id	VARCHAR(64)	ID en Stripe (cus_xxx)	UNIQUE, NOT NULL
stripe_connect_id	VARCHAR(64)	Connected Account (acct_xxx)	NULLABLE, para sellers
billing_email	VARCHAR(255)	Email de facturación	NOT NULL
billing_name	VARCHAR(255)	Nombre fiscal	NOT NULL
tax_id	VARCHAR(20)	NIF/CIF	NOT NULL para España
tax_id_type	VARCHAR(20)	Tipo de tax ID	ENUM: es_cif, eu_vat
billing_address	JSON	Dirección completa	NOT NULL, structured
default_payment_method	VARCHAR(64)	PM por defecto (pm_xxx)	NULLABLE
invoice_settings	JSON	Config de facturación	NULLABLE
metadata	JSON	Datos adicionales	NULLABLE
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última actualización	NOT NULL

3.2 Entidad: billing_subscription
Suscripciones activas de cada tenant.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
tenant_id	INT	Tenant	FK groups.id, NOT NULL, INDEX
stripe_subscription_id	VARCHAR(64)	ID en Stripe (sub_xxx)	UNIQUE, NOT NULL
stripe_customer_id	VARCHAR(64)	Customer asociado	NOT NULL, INDEX
product_id	VARCHAR(64)	Producto Stripe	NOT NULL
price_id	VARCHAR(64)	Precio actual	NOT NULL
vertical	VARCHAR(32)	Vertical del producto	NOT NULL
tier	VARCHAR(32)	Tier actual	ENUM: starter, growth, pro, enterprise
status	VARCHAR(32)	Estado	ENUM: active, past_due, canceled, trialing, paused
billing_cycle	VARCHAR(16)	Ciclo	ENUM: monthly, yearly
current_period_start	DATETIME	Inicio período actual	NOT NULL
current_period_end	DATETIME	Fin período actual	NOT NULL
cancel_at_period_end	BOOLEAN	Cancelar al final	DEFAULT FALSE
canceled_at	DATETIME	Fecha cancelación	NULLABLE
trial_start	DATETIME	Inicio trial	NULLABLE
trial_end	DATETIME	Fin trial	NULLABLE
metadata	JSON	Datos adicionales	NULLABLE
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última actualización	NOT NULL
 
3.3 Entidad: billing_invoice
Registro local de facturas para reporting y compliance.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
tenant_id	INT	Tenant	FK groups.id, NOT NULL, INDEX
stripe_invoice_id	VARCHAR(64)	ID en Stripe (in_xxx)	UNIQUE, NOT NULL
stripe_customer_id	VARCHAR(64)	Customer	NOT NULL
invoice_number	VARCHAR(64)	Número de factura	NOT NULL, UNIQUE
status	VARCHAR(32)	Estado	ENUM: draft, open, paid, void, uncollectible
currency	VARCHAR(3)	Moneda	DEFAULT EUR
subtotal	INT	Subtotal en céntimos	NOT NULL
tax	INT	IVA en céntimos	NOT NULL
total	INT	Total en céntimos	NOT NULL
amount_paid	INT	Pagado en céntimos	DEFAULT 0
amount_due	INT	Pendiente en céntimos	DEFAULT total
invoice_pdf	VARCHAR(500)	URL del PDF	NULLABLE
hosted_invoice_url	VARCHAR(500)	URL de pago	NULLABLE
billing_reason	VARCHAR(64)	Razón	subscription_cycle, subscription_create, manual
period_start	DATETIME	Inicio período	NOT NULL
period_end	DATETIME	Fin período	NOT NULL
due_date	DATETIME	Fecha vencimiento	NULLABLE
paid_at	DATETIME	Fecha de pago	NULLABLE
lines	JSON	Líneas de factura	NOT NULL
created_at	DATETIME	Fecha creación	NOT NULL

3.4 Entidad: billing_payment
Registro de todos los pagos procesados.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
tenant_id	INT	Tenant	FK groups.id, INDEX
stripe_payment_intent_id	VARCHAR(64)	PaymentIntent (pi_xxx)	UNIQUE, NOT NULL
stripe_charge_id	VARCHAR(64)	Charge ID (ch_xxx)	NULLABLE
invoice_id	INT	Factura asociada	FK billing_invoice.id, NULLABLE
amount	INT	Cantidad en céntimos	NOT NULL
currency	VARCHAR(3)	Moneda	DEFAULT EUR
status	VARCHAR(32)	Estado	ENUM: succeeded, pending, failed, canceled
payment_method_type	VARCHAR(32)	Tipo de método	card, sepa_debit, etc
payment_method_last4	VARCHAR(4)	Últimos 4 dígitos	NULLABLE
failure_code	VARCHAR(64)	Código de error	NULLABLE
failure_message	TEXT	Mensaje de error	NULLABLE
receipt_url	VARCHAR(500)	URL del recibo	NULLABLE
created_at	DATETIME	Fecha	NOT NULL
 
3.5 Entidad: billing_connect_account
Cuentas conectadas de vendedores en marketplace.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
tenant_id	INT	Tenant vendedor	FK groups.id, UNIQUE, NOT NULL
stripe_account_id	VARCHAR(64)	Account ID (acct_xxx)	UNIQUE, NOT NULL
account_type	VARCHAR(32)	Tipo de cuenta	ENUM: express, standard, custom
business_type	VARCHAR(32)	Tipo de negocio	ENUM: individual, company
charges_enabled	BOOLEAN	Puede recibir pagos	DEFAULT FALSE
payouts_enabled	BOOLEAN	Puede recibir payouts	DEFAULT FALSE
details_submitted	BOOLEAN	Onboarding completo	DEFAULT FALSE
requirements	JSON	Requisitos pendientes	NULLABLE
tos_acceptance	JSON	Aceptación de términos	NULLABLE
payout_schedule	JSON	Config de payouts	DEFAULT: daily, 7 days
default_currency	VARCHAR(3)	Moneda por defecto	DEFAULT EUR
country	VARCHAR(2)	País	DEFAULT ES
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última actualización	NOT NULL

3.6 Entidad: billing_transfer
Transferencias a cuentas conectadas (marketplace payouts).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
seller_tenant_id	INT	Vendedor	FK groups.id, NOT NULL, INDEX
stripe_transfer_id	VARCHAR(64)	Transfer ID (tr_xxx)	UNIQUE, NOT NULL
stripe_account_id	VARCHAR(64)	Cuenta destino	NOT NULL
source_payment_id	INT	Pago original	FK billing_payment.id, NOT NULL
order_id	INT	Pedido asociado	NULLABLE, INDEX
amount	INT	Cantidad en céntimos	NOT NULL
platform_fee	INT	Comisión Jaraba céntimos	NOT NULL
currency	VARCHAR(3)	Moneda	DEFAULT EUR
status	VARCHAR(32)	Estado	ENUM: pending, paid, failed, reversed
created_at	DATETIME	Fecha	NOT NULL

3.7 Entidad: billing_usage_record
Registros de uso para metered billing.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
tenant_id	INT	Tenant	FK groups.id, NOT NULL, INDEX
subscription_item_id	VARCHAR(64)	Subscription Item (si_xxx)	NOT NULL, INDEX
metric	VARCHAR(64)	Métrica	api_calls, ai_credits, sms, storage_gb
quantity	INT	Cantidad	NOT NULL
timestamp	DATETIME	Timestamp del uso	NOT NULL
stripe_usage_record_id	VARCHAR(64)	ID en Stripe	NULLABLE
reported_at	DATETIME	Cuando se reportó	NULLABLE
created_at	DATETIME	Fecha creación	NOT NULL
 
4. Servicios PHP del Módulo jaraba_billing
4.1 StripeClientFactory
<?php
// jaraba_billing/src/Service/StripeClientFactory.php
 
namespace Drupal\jaraba_billing\Service;
 
use Stripe\StripeClient;
 
class StripeClientFactory {
  
  private string $secretKey;
  private string $webhookSecret;
  private string $connectWebhookSecret;
  
  public function __construct(string $secretKey, string $webhookSecret, string $connectWebhookSecret) {
    $this->secretKey = $secretKey;
    $this->webhookSecret = $webhookSecret;
    $this->connectWebhookSecret = $connectWebhookSecret;
  }
  
  public function createClient(): StripeClient {
    return new StripeClient([
      'api_key' => $this->secretKey,
      'stripe_version' => '2024-12-18.acacia',
    ]);
  }
  
  public function getWebhookSecret(): string {
    return $this->webhookSecret;
  }
  
  public function getConnectWebhookSecret(): string {
    return $this->connectWebhookSecret;
  }
}

4.2 CustomerService
<?php
// jaraba_billing/src/Service/CustomerService.php
 
namespace Drupal\jaraba_billing\Service;
 
use Stripe\StripeClient;
use Drupal\group\Entity\Group;
 
class CustomerService {
  
  private StripeClient $stripe;
  
  public function createCustomer(Group $tenant): BillingCustomer {
    // Crear customer en Stripe
    $stripeCustomer = $this->stripe->customers->create([
      'email' => $tenant->get('field_billing_email')->value,
      'name' => $tenant->get('field_billing_name')->value,
      'metadata' => [
        'tenant_id' => $tenant->id(),
        'vertical' => $tenant->get('field_vertical')->value,
      ],
      'address' => [
        'line1' => $tenant->get('field_address_line1')->value,
        'city' => $tenant->get('field_city')->value,
        'postal_code' => $tenant->get('field_postal_code')->value,
        'country' => 'ES',
      ],
      'tax_id_data' => [
        [
          'type' => 'es_cif',
          'value' => $tenant->get('field_tax_id')->value,
        ],
      ],
      'invoice_settings' => [
        'default_payment_method' => null,
        'footer' => 'Gracias por confiar en Jaraba Impact',
      ],
    ]);
    
    // Guardar en Drupal
    $billingCustomer = BillingCustomer::create([
      'tenant_id' => $tenant->id(),
      'stripe_customer_id' => $stripeCustomer->id,
      'billing_email' => $stripeCustomer->email,
      'billing_name' => $stripeCustomer->name,
      'tax_id' => $tenant->get('field_tax_id')->value,
      'tax_id_type' => 'es_cif',
      'billing_address' => $stripeCustomer->address,
    ]);
    $billingCustomer->save();
    
    return $billingCustomer;
  }
  
  public function attachPaymentMethod(string $customerId, string $paymentMethodId): void {
    $this->stripe->paymentMethods->attach($paymentMethodId, [
      'customer' => $customerId,
    ]);
    
    // Set as default
    $this->stripe->customers->update($customerId, [
      'invoice_settings' => [
        'default_payment_method' => $paymentMethodId,
      ],
    ]);
  }
}
 
4.3 SubscriptionService
<?php
// jaraba_billing/src/Service/SubscriptionService.php
 
namespace Drupal\jaraba_billing\Service;
 
class SubscriptionService {
  
  private StripeClient $stripe;
  private CustomerService $customerService;
  
  /**
   * Crear nueva suscripción para tenant.
   */
  public function createSubscription(
    int $tenantId, 
    string $priceId, 
    bool $trialEnabled = true
  ): BillingSubscription {
    
    $customer = $this->customerService->getByTenantId($tenantId);
    
    $params = [
      'customer' => $customer->getStripeCustomerId(),
      'items' => [
        ['price' => $priceId],
      ],
      'payment_behavior' => 'default_incomplete',
      'payment_settings' => [
        'save_default_payment_method' => 'on_subscription',
      ],
      'expand' => ['latest_invoice.payment_intent'],
      'metadata' => [
        'tenant_id' => $tenantId,
      ],
    ];
    
    // Trial de 14 días si es nuevo
    if ($trialEnabled) {
      $params['trial_period_days'] = 14;
    }
    
    $stripeSubscription = $this->stripe->subscriptions->create($params);
    
    // Guardar en Drupal
    $subscription = BillingSubscription::create([
      'tenant_id' => $tenantId,
      'stripe_subscription_id' => $stripeSubscription->id,
      'stripe_customer_id' => $customer->getStripeCustomerId(),
      'product_id' => $stripeSubscription->items->data[0]->price->product,
      'price_id' => $priceId,
      'vertical' => $this->extractVerticalFromPrice($priceId),
      'tier' => $this->extractTierFromPrice($priceId),
      'status' => $stripeSubscription->status,
      'billing_cycle' => $this->extractBillingCycle($priceId),
      'current_period_start' => $stripeSubscription->current_period_start,
      'current_period_end' => $stripeSubscription->current_period_end,
      'trial_start' => $stripeSubscription->trial_start,
      'trial_end' => $stripeSubscription->trial_end,
    ]);
    $subscription->save();
    
    // Actualizar permisos del tenant según tier
    $this->updateTenantPermissions($tenantId, $subscription->getTier());
    
    return $subscription;
  }
  
  /**
   * Upgrade/Downgrade de plan.
   */
  public function changePlan(int $tenantId, string $newPriceId): BillingSubscription {
    $subscription = $this->getActiveSubscription($tenantId);
    
    // Proration behavior: always_invoice para upgrade, none para downgrade
    $currentTier = $subscription->getTier();
    $newTier = $this->extractTierFromPrice($newPriceId);
    $prorationBehavior = $this->isUpgrade($currentTier, $newTier) 
      ? 'always_invoice' 
      : 'none';
    
    $stripeSubscription = $this->stripe->subscriptions->update(
      $subscription->getStripeSubscriptionId(),
      [
        'items' => [
          [
            'id' => $subscription->getStripeItemId(),
            'price' => $newPriceId,
          ],
        ],
        'proration_behavior' => $prorationBehavior,
        'metadata' => [
          'previous_tier' => $currentTier,
          'upgrade_date' => date('Y-m-d'),
        ],
      ]
    );
    
    // Actualizar local
    $subscription->set('price_id', $newPriceId);
    $subscription->set('tier', $newTier);
    $subscription->save();
    
    // Actualizar permisos
    $this->updateTenantPermissions($tenantId, $newTier);
    
    return $subscription;
  }
  
  /**
   * Cancelar suscripción al final del período.
   */
  public function cancelAtPeriodEnd(int $tenantId): void {
    $subscription = $this->getActiveSubscription($tenantId);
    
    $this->stripe->subscriptions->update(
      $subscription->getStripeSubscriptionId(),
      ['cancel_at_period_end' => true]
    );
    
    $subscription->set('cancel_at_period_end', TRUE);
    $subscription->save();
  }
  
  /**
   * Reactivar suscripción cancelada.
   */
  public function reactivate(int $tenantId): void {
    $subscription = $this->getActiveSubscription($tenantId);
    
    $this->stripe->subscriptions->update(
      $subscription->getStripeSubscriptionId(),
      ['cancel_at_period_end' => false]
    );
    
    $subscription->set('cancel_at_period_end', FALSE);
    $subscription->save();
  }
}
 
4.4 ConnectService (Marketplace)
<?php
// jaraba_billing/src/Service/ConnectService.php
 
namespace Drupal\jaraba_billing\Service;
 
class ConnectService {
  
  private StripeClient $stripe;
  private array $commissionRates = [
    'agroconecta' => 0.08,      // 8%
    'comercioconecta' => 0.06,  // 6%
    'serviciosconecta' => 0.10, // 10%
  ];
  
  /**
   * Crear cuenta conectada para vendedor.
   */
  public function createConnectedAccount(int $tenantId, array $businessData): BillingConnectAccount {
    $account = $this->stripe->accounts->create([
      'type' => 'express', // Express para onboarding simplificado
      'country' => 'ES',
      'email' => $businessData['email'],
      'capabilities' => [
        'card_payments' => ['requested' => true],
        'transfers' => ['requested' => true],
      ],
      'business_type' => $businessData['business_type'] ?? 'company',
      'business_profile' => [
        'name' => $businessData['business_name'],
        'mcc' => $this->getMccForVertical($businessData['vertical']),
        'url' => $businessData['website'] ?? null,
      ],
      'settings' => [
        'payouts' => [
          'schedule' => [
            'interval' => 'daily',
            'delay_days' => 7, // 7 días de retención
          ],
        ],
      ],
      'metadata' => [
        'tenant_id' => $tenantId,
        'vertical' => $businessData['vertical'],
      ],
    ]);
    
    // Guardar en Drupal
    $connectAccount = BillingConnectAccount::create([
      'tenant_id' => $tenantId,
      'stripe_account_id' => $account->id,
      'account_type' => 'express',
      'business_type' => $businessData['business_type'] ?? 'company',
      'charges_enabled' => FALSE,
      'payouts_enabled' => FALSE,
      'details_submitted' => FALSE,
    ]);
    $connectAccount->save();
    
    return $connectAccount;
  }
  
  /**
   * Generar link de onboarding para vendedor.
   */
  public function createOnboardingLink(int $tenantId, string $returnUrl, string $refreshUrl): string {
    $account = $this->getByTenantId($tenantId);
    
    $link = $this->stripe->accountLinks->create([
      'account' => $account->getStripeAccountId(),
      'refresh_url' => $refreshUrl,
      'return_url' => $returnUrl,
      'type' => 'account_onboarding',
    ]);
    
    return $link->url;
  }
  
  /**
   * Procesar pago de marketplace con split.
   */
  public function createMarketplacePayment(
    int $buyerTenantId,
    int $sellerTenantId,
    int $amountCents,
    string $vertical,
    array $metadata = []
  ): PaymentResult {
    
    $sellerAccount = $this->getByTenantId($sellerTenantId);
    $buyerCustomer = $this->customerService->getByTenantId($buyerTenantId);
    
    // Calcular comisión
    $commissionRate = $this->commissionRates[$vertical] ?? 0.08;
    $platformFee = (int) round($amountCents * $commissionRate);
    
    // Crear PaymentIntent con transfer automático
    $paymentIntent = $this->stripe->paymentIntents->create([
      'amount' => $amountCents,
      'currency' => 'eur',
      'customer' => $buyerCustomer->getStripeCustomerId(),
      'payment_method_types' => ['card'],
      'application_fee_amount' => $platformFee,
      'transfer_data' => [
        'destination' => $sellerAccount->getStripeAccountId(),
      ],
      'metadata' => array_merge($metadata, [
        'buyer_tenant_id' => $buyerTenantId,
        'seller_tenant_id' => $sellerTenantId,
        'vertical' => $vertical,
        'platform_fee_rate' => $commissionRate,
      ]),
    ]);
    
    return new PaymentResult(
      $paymentIntent->id,
      $paymentIntent->client_secret,
      $amountCents,
      $platformFee,
      $amountCents - $platformFee
    );
  }
}
 
4.5 UsageService (Metered Billing)
<?php
// jaraba_billing/src/Service/UsageService.php
 
namespace Drupal\jaraba_billing\Service;
 
class UsageService {
  
  private StripeClient $stripe;
  
  /**
   * Reportar uso de una métrica.
   */
  public function reportUsage(int $tenantId, string $metric, int $quantity): void {
    $subscription = $this->subscriptionService->getActiveSubscription($tenantId);
    $subscriptionItem = $this->getSubscriptionItemForMetric($subscription, $metric);
    
    // Crear registro local
    $usageRecord = BillingUsageRecord::create([
      'tenant_id' => $tenantId,
      'subscription_item_id' => $subscriptionItem->id,
      'metric' => $metric,
      'quantity' => $quantity,
      'timestamp' => time(),
    ]);
    $usageRecord->save();
    
    // Reportar a Stripe (batch cada 5 minutos via cron)
    // No reportamos inmediatamente para optimizar API calls
  }
  
  /**
   * Cron job: Reportar uso acumulado a Stripe.
   */
  public function flushUsageToStripe(): void {
    // Obtener registros no reportados
    $unreported = BillingUsageRecord::query()
      ->condition('reported_at', NULL, 'IS NULL')
      ->condition('timestamp', strtotime('-1 hour'), '>')
      ->execute();
    
    // Agrupar por subscription_item_id
    $grouped = [];
    foreach ($unreported as $record) {
      $key = $record->get('subscription_item_id');
      if (!isset($grouped[$key])) {
        $grouped[$key] = 0;
      }
      $grouped[$key] += $record->get('quantity');
    }
    
    // Reportar a Stripe
    foreach ($grouped as $subscriptionItemId => $totalQuantity) {
      $this->stripe->subscriptionItems->createUsageRecord(
        $subscriptionItemId,
        [
          'quantity' => $totalQuantity,
          'timestamp' => time(),
          'action' => 'increment',
        ]
      );
    }
    
    // Marcar como reportados
    foreach ($unreported as $record) {
      $record->set('reported_at', time());
      $record->save();
    }
  }
  
  /**
   * Verificar si tenant excede límites de su plan.
   */
  public function checkUsageLimits(int $tenantId): UsageLimitStatus {
    $subscription = $this->subscriptionService->getActiveSubscription($tenantId);
    $tier = $subscription->getTier();
    $limits = $this->getTierLimits($tier);
    
    // Obtener uso del período actual
    $periodStart = $subscription->get('current_period_start');
    $currentUsage = $this->getUsageForPeriod($tenantId, $periodStart);
    
    $status = new UsageLimitStatus();
    
    foreach ($limits as $metric => $limit) {
      $used = $currentUsage[$metric] ?? 0;
      $percentage = $limit > 0 ? ($used / $limit) * 100 : 0;
      
      $status->addMetric($metric, $used, $limit, $percentage);
      
      // Alertar si > 80%
      if ($percentage > 80 && $percentage < 100) {
        $this->sendUsageWarning($tenantId, $metric, $percentage);
      }
      
      // Bloquear o cobrar overage si > 100%
      if ($percentage >= 100) {
        $status->setOverage($metric, $used - $limit);
      }
    }
    
    return $status;
  }
  
  private function getTierLimits(string $tier): array {
    return [
      'starter' => [
        'api_calls' => 10000,
        'ai_credits' => 1000,
        'storage_gb' => 5,
        'users' => 3,
      ],
      'growth' => [
        'api_calls' => 50000,
        'ai_credits' => 5000,
        'storage_gb' => 25,
        'users' => 10,
      ],
      'pro' => [
        'api_calls' => 200000,
        'ai_credits' => 20000,
        'storage_gb' => 100,
        'users' => 50,
      ],
      'enterprise' => [
        'api_calls' => PHP_INT_MAX,
        'ai_credits' => PHP_INT_MAX,
        'storage_gb' => PHP_INT_MAX,
        'users' => PHP_INT_MAX,
      ],
    ][$tier] ?? [];
  }
}
 
5. Webhooks de Stripe
5.1 Endpoints de Webhook
Endpoint	Secret	Eventos
/api/stripe/webhook	STRIPE_WEBHOOK_SECRET	Eventos principales (subscriptions, invoices, payments)
/api/stripe/connect-webhook	STRIPE_CONNECT_WEBHOOK_SECRET	Eventos de Connect (account.updated, transfers)

5.2 Eventos Manejados
Evento	Acción	Prioridad
customer.subscription.created	Crear BillingSubscription, actualizar permisos tenant	🔴 Crítico
customer.subscription.updated	Sync status, detectar upgrade/downgrade	🔴 Crítico
customer.subscription.deleted	Marcar cancelada, degradar permisos	🔴 Crítico
invoice.paid	Crear BillingInvoice, marcar pagada	🔴 Crítico
invoice.payment_failed	Trigger dunning flow	🔴 Crítico
invoice.finalized	Guardar PDF URL	🟡 Alto
payment_intent.succeeded	Crear BillingPayment	🟡 Alto
payment_intent.payment_failed	Log failure, notificar	🟡 Alto
account.updated	Sync Connect account status	🟡 Alto
transfer.created	Crear BillingTransfer	🟡 Alto
customer.updated	Sync datos de cliente	🟢 Normal
payment_method.attached	Update default PM	🟢 Normal

5.3 Webhook Handler
<?php
// jaraba_billing/src/Controller/WebhookController.php
 
namespace Drupal\jaraba_billing\Controller;
 
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
 
class WebhookController {
  
  public function handleWebhook(Request $request): Response {
    $payload = $request->getContent();
    $sigHeader = $request->headers->get('Stripe-Signature');
    
    try {
      $event = Webhook::constructEvent(
        $payload,
        $sigHeader,
        $this->stripeFactory->getWebhookSecret()
      );
    } catch (\Exception $e) {
      return new Response('Invalid signature', 400);
    }
    
    // Log del evento
    $this->logger->info('Stripe webhook: @type', ['@type' => $event->type]);
    
    // Dispatch al handler correcto
    $handler = $this->getHandler($event->type);
    if ($handler) {
      try {
        $handler->handle($event);
      } catch (\Exception $e) {
        $this->logger->error('Webhook error: @error', ['@error' => $e->getMessage()]);
        return new Response('Handler error', 500);
      }
    }
    
    return new Response('OK', 200);
  }
  
  private function getHandler(string $eventType): ?WebhookHandlerInterface {
    $handlers = [
      'customer.subscription.created' => SubscriptionCreatedHandler::class,
      'customer.subscription.updated' => SubscriptionUpdatedHandler::class,
      'customer.subscription.deleted' => SubscriptionDeletedHandler::class,
      'invoice.paid' => InvoicePaidHandler::class,
      'invoice.payment_failed' => InvoicePaymentFailedHandler::class,
      'payment_intent.succeeded' => PaymentSucceededHandler::class,
      'account.updated' => AccountUpdatedHandler::class,
      'transfer.created' => TransferCreatedHandler::class,
    ];
    
    $class = $handlers[$eventType] ?? null;
    return $class ? \Drupal::service($class) : null;
  }
}
 
5.4 Handler de Suscripción Actualizada
<?php
// jaraba_billing/src/WebhookHandler/SubscriptionUpdatedHandler.php
 
namespace Drupal\jaraba_billing\WebhookHandler;
 
class SubscriptionUpdatedHandler implements WebhookHandlerInterface {
  
  public function handle(Event $event): void {
    $stripeSubscription = $event->data->object;
    
    // Buscar suscripción local
    $subscription = BillingSubscription::loadByStripeId($stripeSubscription->id);
    if (!$subscription) {
      throw new \Exception('Subscription not found: ' . $stripeSubscription->id);
    }
    
    $oldStatus = $subscription->get('status');
    $oldTier = $subscription->get('tier');
    
    // Actualizar campos
    $subscription->set('status', $stripeSubscription->status);
    $subscription->set('current_period_start', $stripeSubscription->current_period_start);
    $subscription->set('current_period_end', $stripeSubscription->current_period_end);
    $subscription->set('cancel_at_period_end', $stripeSubscription->cancel_at_period_end);
    
    // Detectar cambio de precio (upgrade/downgrade)
    $newPriceId = $stripeSubscription->items->data[0]->price->id;
    if ($newPriceId !== $subscription->get('price_id')) {
      $newTier = $this->extractTierFromPrice($newPriceId);
      $subscription->set('price_id', $newPriceId);
      $subscription->set('tier', $newTier);
      
      // Actualizar permisos del tenant
      $this->permissionService->updateTenantPermissions(
        $subscription->get('tenant_id'),
        $newTier
      );
      
      // Notificar al tenant
      if ($this->isUpgrade($oldTier, $newTier)) {
        $this->notificationService->sendUpgradeConfirmation(
          $subscription->get('tenant_id'),
          $oldTier,
          $newTier
        );
      }
    }
    
    // Manejar cambios de estado críticos
    if ($oldStatus !== $stripeSubscription->status) {
      $this->handleStatusChange($subscription, $oldStatus, $stripeSubscription->status);
    }
    
    $subscription->save();
  }
  
  private function handleStatusChange(
    BillingSubscription $subscription, 
    string $oldStatus, 
    string $newStatus
  ): void {
    $tenantId = $subscription->get('tenant_id');
    
    switch ($newStatus) {
      case 'past_due':
        // Iniciar dunning
        $this->dunningService->startDunning($tenantId);
        $this->notificationService->sendPaymentFailedNotice($tenantId);
        break;
        
      case 'canceled':
        // Degradar a free o desactivar
        $this->permissionService->degradeToFree($tenantId);
        $this->notificationService->sendCancellationConfirmation($tenantId);
        break;
        
      case 'active':
        if ($oldStatus === 'past_due') {
          // Pago recuperado
          $this->dunningService->stopDunning($tenantId);
          $this->notificationService->sendPaymentRecoveredNotice($tenantId);
        }
        break;
    }
  }
}
 
6. Dunning: Gestión de Pagos Fallidos
6.1 Secuencia de Dunning
Día	Acción	Canal	Impacto en Servicio
0	Primer intento fallido - Email informativo	Email	Ninguno
3	Segundo intento automático + Email recordatorio	Email	Banner de aviso en dashboard
7	Tercer intento + Email urgente	Email + SMS	Funciones premium desactivadas
10	Cuarto intento + Llamada a la acción	Email + SMS + In-app	Solo lectura
14	Último intento + Aviso de cancelación	Email + SMS	Cuenta suspendida
21	Cancelación automática	Email	Datos retenidos 30 días
51	Eliminación de datos (GDPR)	-	Datos eliminados

6.2 Dunning Service
<?php
// jaraba_billing/src/Service/DunningService.php
 
namespace Drupal\jaraba_billing\Service;
 
class DunningService {
  
  private array $dunningSequence = [
    ['days' => 0, 'action' => 'email_soft', 'restrict' => false],
    ['days' => 3, 'action' => 'email_reminder', 'restrict' => 'banner'],
    ['days' => 7, 'action' => 'email_urgent_sms', 'restrict' => 'premium_disabled'],
    ['days' => 10, 'action' => 'email_sms_inapp', 'restrict' => 'readonly'],
    ['days' => 14, 'action' => 'email_final', 'restrict' => 'suspended'],
    ['days' => 21, 'action' => 'cancel', 'restrict' => 'canceled'],
  ];
  
  public function startDunning(int $tenantId): void {
    // Verificar si ya está en dunning
    if ($this->isInDunning($tenantId)) {
      return;
    }
    
    DunningState::create([
      'tenant_id' => $tenantId,
      'started_at' => time(),
      'current_step' => 0,
      'last_action_at' => time(),
    ])->save();
    
    // Ejecutar primera acción
    $this->executeStep($tenantId, 0);
  }
  
  public function processDunning(): void {
    // Cron job diario
    $dunningStates = DunningState::loadMultiple();
    
    foreach ($dunningStates as $state) {
      $daysSinceStart = (time() - $state->get('started_at')) / 86400;
      $currentStep = $state->get('current_step');
      $nextStep = $currentStep + 1;
      
      if (isset($this->dunningSequence[$nextStep])) {
        $nextConfig = $this->dunningSequence[$nextStep];
        
        if ($daysSinceStart >= $nextConfig['days']) {
          $this->executeStep($state->get('tenant_id'), $nextStep);
          $state->set('current_step', $nextStep);
          $state->set('last_action_at', time());
          $state->save();
        }
      }
    }
  }
  
  private function executeStep(int $tenantId, int $step): void {
    $config = $this->dunningSequence[$step];
    $subscription = $this->subscriptionService->getActiveSubscription($tenantId);
    
    // Aplicar restricción
    if ($config['restrict']) {
      $this->applyRestriction($tenantId, $config['restrict']);
    }
    
    // Enviar notificaciones
    switch ($config['action']) {
      case 'email_soft':
        $this->emailService->sendPaymentFailedSoft($tenantId);
        break;
        
      case 'email_reminder':
        $this->emailService->sendPaymentReminder($tenantId);
        break;
        
      case 'email_urgent_sms':
        $this->emailService->sendPaymentUrgent($tenantId);
        $this->smsService->sendPaymentUrgent($tenantId);
        break;
        
      case 'email_sms_inapp':
        $this->emailService->sendPaymentFinal($tenantId);
        $this->smsService->sendPaymentFinal($tenantId);
        $this->inAppService->showPaymentModal($tenantId);
        break;
        
      case 'email_final':
        $this->emailService->sendCancellationWarning($tenantId);
        $this->smsService->sendCancellationWarning($tenantId);
        break;
        
      case 'cancel':
        $this->subscriptionService->cancel($tenantId);
        $this->emailService->sendCancellationConfirmation($tenantId);
        $this->stopDunning($tenantId);
        break;
    }
  }
  
  private function applyRestriction(int $tenantId, string $restriction): void {
    $tenant = Group::load($tenantId);
    
    switch ($restriction) {
      case 'banner':
        $tenant->set('field_payment_banner', TRUE);
        break;
        
      case 'premium_disabled':
        $this->permissionService->disablePremiumFeatures($tenantId);
        break;
        
      case 'readonly':
        $this->permissionService->setReadOnly($tenantId);
        break;
        
      case 'suspended':
        $this->permissionService->suspendAccount($tenantId);
        break;
        
      case 'canceled':
        $this->permissionService->degradeToFree($tenantId);
        break;
    }
    
    $tenant->save();
  }
  
  public function stopDunning(int $tenantId): void {
    $state = DunningState::loadByTenantId($tenantId);
    if ($state) {
      // Restaurar permisos
      $subscription = $this->subscriptionService->getActiveSubscription($tenantId);
      if ($subscription) {
        $this->permissionService->updateTenantPermissions(
          $tenantId, 
          $subscription->getTier()
        );
      }
      
      // Limpiar banner
      $tenant = Group::load($tenantId);
      $tenant->set('field_payment_banner', FALSE);
      $tenant->save();
      
      // Eliminar estado de dunning
      $state->delete();
    }
  }
}
 
7. Customer Portal de Facturación
7.1 Funcionalidades del Portal
Funcionalidad	Implementación	Notas
Ver suscripción actual	Stripe Customer Portal	Tier, precio, próxima factura
Cambiar plan	Custom UI + Stripe API	Con confirmación de proration
Ver facturas	Stripe Customer Portal	Descarga PDF
Actualizar método de pago	Stripe Customer Portal	Card, SEPA
Cancelar suscripción	Custom UI + Stripe API	Con encuesta de salida
Ver uso actual	Custom UI	Métricas vs límites del plan
Añadir/quitar add-ons	Custom UI + Stripe API	Usuarios, storage
Datos de facturación	Stripe Customer Portal	Nombre, NIF, dirección

7.2 Wireframe del Portal
┌──────────────────────────────────────────────────────────────────────────────┐
│  MI SUSCRIPCIÓN                                                              │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  PLAN ACTUAL                                                           │  │
│  │                                                                        │  │
│  │  ┌──────────────────────────────────┐  ┌────────────────────────────┐  │  │
│  │  │  🚀 GROWTH                        │  │  Próxima factura:          │  │  │
│  │  │     79€/mes                       │  │  15 Feb 2026               │  │  │
│  │  │                                   │  │  79,00€ + IVA              │  │  │
│  │  │  Empleabilidad                    │  │                            │  │  │
│  │  │  Renovación: 15 Feb 2026          │  │  [Ver facturas]            │  │  │
│  │  │                                   │  │                            │  │  │
│  │  │  [Cambiar plan]  [Cancelar]       │  │  [Actualizar pago]         │  │  │
│  │  └──────────────────────────────────┘  └────────────────────────────┘  │  │
│  │                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  USO DEL PERÍODO ACTUAL (15 Ene - 15 Feb 2026)                         │  │
│  │                                                                        │  │
│  │  Usuarios        ████████████████░░░░  8 de 10                        │  │
│  │  API Calls       ████████░░░░░░░░░░░░  23,456 de 50,000               │  │
│  │  Créditos IA     ██████████████░░░░░░  3,420 de 5,000                 │  │
│  │  Storage         ██████░░░░░░░░░░░░░░  12.3 GB de 25 GB              │  │
│  │                                                                        │  │
│  │  💡 Vas bien! Si necesitas más, puedes [añadir recursos]              │  │
│  │                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  ADD-ONS ACTIVOS                                         [+ Añadir]   │  │
│  │                                                                        │  │
│  │  • 3 usuarios adicionales (30€/mes)                      [Modificar]  │  │
│  │  • Pack 5000 créditos IA (50€)                               Activo   │  │
│  │                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  DATOS DE FACTURACIÓN                                      [Editar]   │  │
│  │                                                                        │  │
│  │  Bodega Robles S.L.                                                   │  │
│  │  CIF: B14123456                                                       │  │
│  │  Calle Bodegas 42, 14500 Puente Genil, Córdoba                        │  │
│  │  facturacion@bodegasrobles.es                                         │  │
│  │                                                                        │  │
│  │  Método de pago: •••• •••• •••• 4242 (Visa)             [Actualizar]  │  │
│  │                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
 
8. Compliance Fiscal España
8.1 Requisitos de Facturación
Requisito	Implementación	Campo Stripe
NIF/CIF del cliente	Obligatorio para B2B	customer.tax_id (es_cif)
Razón social completa	Nombre fiscal	customer.name
Dirección completa	Incluir CP y provincia	customer.address
Número de factura secuencial	Stripe auto-genera	invoice.number
Fecha de emisión	Automático	invoice.created
Base imponible	Subtotal sin IVA	invoice.subtotal
Tipo de IVA	21% general	invoice.tax (Stripe Tax)
Cuota de IVA	Calculado	invoice.tax
Total factura	Base + IVA	invoice.total
Datos del emisor	Jaraba Impact S.L.	Configurado en Stripe

8.2 Configuración de Stripe Tax
// Configuración de Stripe Tax para España
 
// 1. Habilitar Stripe Tax en Dashboard
// Settings > Tax > Enable Stripe Tax
 
// 2. Configurar origen (Jaraba)
{
  "tax_settings": {
    "defaults": {
      "tax_behavior": "exclusive", // IVA se añade al precio
      "tax_code": "txcd_10000000" // Software as a Service
    },
    "head_office": {
      "address": {
        "country": "ES",
        "city": "Córdoba",
        "postal_code": "14001"
      }
    }
  }
}
 
// 3. En cada Price, configurar tax_behavior
$price = $stripe->prices->create([
  'product' => 'prod_empleabilidad',
  'unit_amount' => 7900, // 79€ en céntimos
  'currency' => 'eur',
  'recurring' => ['interval' => 'month'],
  'tax_behavior' => 'exclusive', // IVA se suma
]);
 
// 4. Al crear Customer, añadir tax_id
$customer = $stripe->customers->create([
  'email' => 'facturacion@bodegasrobles.es',
  'name' => 'Bodega Robles S.L.',
  'tax_id_data' => [
    [
      'type' => 'es_cif',
      'value' => 'B14123456',
    ],
  ],
  'address' => [
    'country' => 'ES',
    'city' => 'Puente Genil',
    'postal_code' => '14500',
    'line1' => 'Calle Bodegas 42',
    'state' => 'Córdoba',
  ],
]);
 
// 5. Stripe calcula automáticamente el IVA correcto
// - España B2B: 21% IVA
// - España B2C: 21% IVA  
// - UE B2B con VAT válido: 0% (reverse charge)
// - UE B2C: IVA del país del cliente

8.3 Casos Especiales de IVA
Caso	IVA	Requisitos
B2B España	21%	NIF/CIF obligatorio
B2C España	21%	Dirección española
B2B UE (Intracomunitario)	0%	VAT ID válido + verificación VIES
B2C UE	IVA país cliente	Dirección del cliente
Canarias (IGIC)	7%	CP 35xxx, 38xxx
Ceuta/Melilla	0%	CP 51xxx, 52xxx
Fuera UE	0%	Exportación, sin IVA
 
9. APIs REST de Billing
9.1 Endpoints Públicos (Tenant)
Método	Endpoint	Descripción
GET	/api/v1/billing/subscription	Obtener suscripción actual
POST	/api/v1/billing/subscription	Crear suscripción (checkout)
PUT	/api/v1/billing/subscription/plan	Cambiar plan
DELETE	/api/v1/billing/subscription	Cancelar suscripción
POST	/api/v1/billing/subscription/reactivate	Reactivar antes de fin período
GET	/api/v1/billing/invoices	Listar facturas
GET	/api/v1/billing/invoices/{id}/pdf	Descargar PDF de factura
GET	/api/v1/billing/usage	Ver uso actual del período
POST	/api/v1/billing/portal-session	Crear sesión del portal Stripe
GET	/api/v1/billing/payment-methods	Listar métodos de pago
POST	/api/v1/billing/payment-methods	Añadir método de pago
DELETE	/api/v1/billing/payment-methods/{id}	Eliminar método de pago
PUT	/api/v1/billing/customer	Actualizar datos de facturación

9.2 Endpoints de Marketplace (Vendedores)
Método	Endpoint	Descripción
GET	/api/v1/billing/connect/account	Estado de cuenta conectada
POST	/api/v1/billing/connect/onboarding	Iniciar onboarding de Stripe
GET	/api/v1/billing/connect/balance	Balance disponible
GET	/api/v1/billing/connect/payouts	Historial de payouts
GET	/api/v1/billing/connect/transfers	Transferencias recibidas
POST	/api/v1/billing/connect/payout	Solicitar payout manual

9.3 Endpoints Admin (Platform)
Método	Endpoint	Descripción
GET	/api/v1/admin/billing/subscriptions	Listar todas las suscripciones
GET	/api/v1/admin/billing/revenue	Métricas de revenue
GET	/api/v1/admin/billing/mrr	Monthly Recurring Revenue
GET	/api/v1/admin/billing/churn	Métricas de churn
POST	/api/v1/admin/billing/refund	Procesar reembolso
POST	/api/v1/admin/billing/credit	Aplicar crédito a cuenta
 
10. Reporting Financiero
10.1 Métricas Clave
Métrica	Fórmula	Frecuencia
MRR (Monthly Recurring Revenue)	Suma de todas las suscripciones activas	Diario
ARR (Annual Recurring Revenue)	MRR × 12	Diario
Net Revenue	MRR + One-time - Refunds	Diario
ARPU (Average Revenue Per User)	MRR / Active Subscriptions	Mensual
Churn Rate	Cancelaciones / Total inicio mes	Mensual
LTV (Lifetime Value)	ARPU / Churn Rate	Mensual
Expansion MRR	Upgrades + Add-ons del mes	Mensual
Contraction MRR	Downgrades del mes	Mensual
Net MRR Growth	New + Expansion - Churn - Contraction	Mensual
Marketplace GMV	Total ventas marketplace	Diario
Platform Take Rate	Comisiones / GMV	Mensual

10.2 Dashboard de Revenue
┌──────────────────────────────────────────────────────────────────────────────┐
│  REVENUE DASHBOARD                                     Enero 2026           │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │   MRR       │  │   ARR       │  │   Churn     │  │   ARPU      │         │
│  │  €47,320    │  │  €567,840   │  │   2.3%      │  │   €89       │         │
│  │  ↑ 12%      │  │  ↑ 12%      │  │  ↓ 0.4%    │  │  ↑ €7       │         │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘         │
│                                                                              │
│  MRR POR VERTICAL                          MRR POR TIER                      │
│  ┌─────────────────────────────┐          ┌─────────────────────────────┐   │
│  │ Empleabilidad    €18,500    │          │ Enterprise    €15,000       │   │
│  │ ████████████████░░░░░░░     │          │ ███████████░░░░░░░░░░░░░   │   │
│  │                             │          │                             │   │
│  │ Emprendimiento   €12,300    │          │ Pro           €18,200       │   │
│  │ ███████████░░░░░░░░░░░░     │          │ ██████████████░░░░░░░░░░   │   │
│  │                             │          │                             │   │
│  │ AgroConecta      €8,200     │          │ Growth        €11,420       │   │
│  │ ████████░░░░░░░░░░░░░░░     │          │ █████████░░░░░░░░░░░░░░░   │   │
│  │                             │          │                             │   │
│  │ ComercioConecta  €5,100     │          │ Starter       €2,700        │   │
│  │ █████░░░░░░░░░░░░░░░░░░     │          │ ██░░░░░░░░░░░░░░░░░░░░░░   │   │
│  │                             │          │                             │   │
│  │ ServiciosConecta €3,220     │          │                             │   │
│  │ ███░░░░░░░░░░░░░░░░░░░░     │          │                             │   │
│  └─────────────────────────────┘          └─────────────────────────────┘   │
│                                                                              │
│  MARKETPLACE (GMV)                         COHORT ANALYSIS                   │
│  ┌─────────────────────────────┐          ┌─────────────────────────────┐   │
│  │ GMV Total:       €234,500   │          │ Cohorte Ene'25: 87% activos │   │
│  │ Platform Fee:    €18,760    │          │ Cohorte Jul'25: 92% activos │   │
│  │ Take Rate:       8%         │          │ Cohorte Oct'25: 96% activos │   │
│  └─────────────────────────────┘          └─────────────────────────────┘   │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
 
11. Roadmap de Implementación
11.1 Plan de Sprints
Sprint	Timeline	Entregables	Horas Est.
Sprint 1	Sem 1-2	Modelo de datos, StripeClientFactory, CustomerService	45-55
Sprint 2	Sem 3-4	SubscriptionService, Checkout flow, Webhooks básicos	55-65
Sprint 3	Sem 5-6	Customer Portal UI, Facturas, Stripe Tax config	50-60
Sprint 4	Sem 7-8	ConnectService (Marketplace), Onboarding vendedores	55-65
Sprint 5	Sem 9-10	UsageService (Metered), DunningService	45-55
Sprint 6	Sem 11-12	Reporting dashboard, APIs admin, QA completo	50-60
TOTAL	12 semanas	Sistema de billing completo	300-360

11.2 Criterios de Aceptación
Sprint 2: Suscripciones Core
•	Checkout funcional: usuario puede suscribirse a cualquier plan
•	Webhooks procesan correctamente subscription.created/updated/deleted
•	Upgrade/downgrade funciona con proration correcto
•	Cancelación al final del período implementada
Sprint 4: Marketplace
•	Vendedores pueden completar onboarding de Stripe Connect
•	Pagos de marketplace dividen correctamente entre vendedor y platform
•	Payouts llegan a cuentas de vendedores en 7 días
•	Comisiones diferenciadas por vertical funcionan
Sprint 6: Go-Live
•	Dashboard de revenue muestra MRR, ARR, Churn en tiempo real
•	Dunning completo con 6 pasos y restricciones progresivas
•	Facturas cumplen requisitos fiscales españoles
•	Tests e2e cubren flujos críticos de pago
 
12. Checklist de Implementación
12.1 Configuración Stripe
•	[ ] Crear cuenta Stripe (modo live cuando esté listo)
•	[ ] Configurar Stripe Connect (platform)
•	[ ] Habilitar Stripe Tax para España
•	[ ] Crear productos y precios en Stripe Dashboard
•	[ ] Configurar Customer Portal
•	[ ] Configurar webhooks (main + connect)
•	[ ] Añadir dominios a Stripe para 3D Secure
12.2 Backend Drupal
•	[ ] Crear módulo jaraba_billing
•	[ ] Implementar 7 entidades de billing
•	[ ] Implementar servicios: Customer, Subscription, Connect, Usage, Dunning
•	[ ] Implementar webhook handlers (12 eventos)
•	[ ] Implementar APIs REST (25+ endpoints)
•	[ ] Integrar con sistema de permisos de tenant
•	[ ] Cron jobs: usage flush, dunning processor
12.3 Frontend
•	[ ] Checkout flow con Stripe Elements
•	[ ] Customer Portal embebido o redirect
•	[ ] Dashboard de uso y suscripción
•	[ ] UI de cambio de plan con preview de proration
•	[ ] UI de cancelación con encuesta
•	[ ] Banners de dunning
12.4 Testing
•	[ ] Tests unitarios de servicios
•	[ ] Tests de integración con Stripe (test mode)
•	[ ] Tests e2e de checkout completo
•	[ ] Tests de webhooks con Stripe CLI
•	[ ] Tests de dunning flow

--- Fin del Documento ---
