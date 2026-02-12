SISTEMA DE FACTURACIÓN
Generación, Envío y Gestión de Cobros
Facturas + Notas de Crédito + Recordatorios + Stripe Integration
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	96_ServiciosConecta_Sistema_Facturacion
Dependencias:	92_Presupuestador_Auto, Stripe Connect
Compliance:	Facturae 3.2.2, SII/AEAT, Verifactu
Prioridad:	CRÍTICA - Flujo de caja del despacho
 
1. Resumen Ejecutivo
El Sistema de Facturación cierra el ciclo comercial de ServiciosConecta: desde que el cliente acepta un presupuesto (doc 92) hasta que el cobro se materializa. Proporciona generación automática de facturas, envío al cliente con enlace de pago, recordatorios automáticos de vencimiento, integración con Stripe para cobros con tarjeta/domiciliación, y cumplimiento con la normativa española de facturación electrónica.
El sistema soporta múltiples modelos de facturación: por caso (al cierre), por hito (entregas parciales), recurrente (suscripciones mensuales) y por horas (timetracking). También gestiona notas de crédito, abonos y devoluciones, manteniendo la trazabilidad contable completa.
1.1 Flujo de Facturación
┌─────────────────────────────────────────────────────────────────────────┐
│                    FLUJO DE FACTURACIÓN                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │ Presupuesto  │───▶│   Factura    │───▶│    Envío     │              │
│  │  Aceptado    │    │  Generada    │    │  al Cliente  │              │
│  └──────────────┘    └──────────────┘    └───────┬──────┘              │
│                                                   │                     │
│         ┌────────────────────────────────────────┘                     │
│         │                                                              │
│         ▼                                                              │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │   Cliente    │───▶│    Pago      │───▶│   Factura    │              │
│  │ Recibe Link  │    │  con Stripe  │    │   Cobrada    │              │
│  └──────────────┘    └──────────────┘    └──────────────┘              │
│                                                                         │
│  Si no paga en fecha:                                                   │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │ Recordatorio │───▶│  2º Aviso    │───▶│  Escalar a   │              │
│  │   Día -3     │    │  Día +7      │    │  Profesional │              │
│  └──────────────┘    └──────────────┘    └──────────────┘              │
└─────────────────────────────────────────────────────────────────────────┘
1.2 Modelos de Facturación Soportados
Modelo	Descripción	Ejemplo
Por caso (cierre)	Factura única al finalizar el expediente	Divorcio contencioso
Por hito	Facturas parciales según entregas/fases	Constitución SL: 30/40/30%
Provisión de fondos	Anticipo al inicio + liquidación al cierre	Procedimiento judicial
Recurrente	Cuota mensual por servicios continuos	Asesoría fiscal mensual
Por horas	Según timetracking del profesional	Consultoría legal
Éxito	% sobre resultado obtenido	Reclamación de cantidad

 
2. Modelo de Datos
2.1 Entidad: invoice (Factura)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
invoice_number	VARCHAR(32)	Número de factura	UNIQUE per tenant, FAC-2026-0001
series	VARCHAR(8)	Serie de facturación	FAC, REC, PRO
tenant_id	INT	Tenant	FK tenant.id, NOT NULL, INDEX
provider_id	INT	Profesional emisor	FK users.uid, NOT NULL
case_id	INT	Expediente relacionado	FK client_case.id, NULLABLE
quote_id	INT	Presupuesto origen	FK quote.id, NULLABLE
client_name	VARCHAR(255)	Nombre/Razón social	NOT NULL
client_nif	VARCHAR(20)	NIF/CIF del cliente	NOT NULL
client_address	TEXT	Dirección fiscal	NOT NULL
client_email	VARCHAR(255)	Email para envío	NOT NULL
issue_date	DATE	Fecha de emisión	NOT NULL
due_date	DATE	Fecha de vencimiento	NOT NULL
subtotal	DECIMAL(12,2)	Base imponible	NOT NULL
tax_rate	DECIMAL(5,2)	% IVA	DEFAULT 21.00
tax_amount	DECIMAL(12,2)	Importe IVA	NOT NULL
irpf_rate	DECIMAL(5,2)	% Retención IRPF	DEFAULT 0 (o 15 para prof.)
irpf_amount	DECIMAL(12,2)	Importe retención	NOT NULL
total	DECIMAL(12,2)	Total a pagar	NOT NULL
currency	VARCHAR(3)	Moneda	DEFAULT 'EUR'
status	VARCHAR(16)	Estado de la factura	ENUM: ver tabla siguiente
payment_method	VARCHAR(32)	Método de pago	stripe|transfer|cash|check
stripe_invoice_id	VARCHAR(64)	ID de Stripe Invoice	NULLABLE
stripe_payment_url	VARCHAR(512)	URL de pago Stripe	NULLABLE
paid_at	DATETIME	Fecha de cobro	NULLABLE
paid_amount	DECIMAL(12,2)	Importe cobrado	NULLABLE
notes	TEXT	Notas/observaciones	NULLABLE
created	DATETIME	Fecha creación	NOT NULL

2.2 Estados de Factura
Estado	Descripción	Transiciones Permitidas
draft	Borrador, no enviada ni contabilizada	→ issued, → cancelled
issued	Emitida, pendiente de envío	→ sent
sent	Enviada al cliente	→ viewed, → paid, → overdue
viewed	Cliente ha abierto el enlace	→ paid, → overdue
paid	Cobrada completamente	→ refunded (parcial/total)
partial	Cobro parcial recibido	→ paid, → overdue
overdue	Vencida sin cobrar	→ paid, → partial, → written_off
refunded	Devuelta (con nota de crédito)	Estado final
cancelled	Anulada (solo desde draft)	Estado final
written_off	Incobrable, dado de baja	Estado final

 
2.3 Entidad: invoice_line (Líneas de Factura)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
invoice_id	INT	Factura padre	FK invoice.id, NOT NULL, INDEX
line_order	INT	Orden de la línea	NOT NULL
description	TEXT	Descripción del servicio	NOT NULL
quantity	DECIMAL(10,2)	Cantidad	DEFAULT 1
unit	VARCHAR(16)	Unidad	unit|hour|session|month
unit_price	DECIMAL(12,2)	Precio unitario	NOT NULL
discount_percent	DECIMAL(5,2)	% Descuento	DEFAULT 0
line_total	DECIMAL(12,2)	Total línea	qty * price * (1-discount)
tax_rate	DECIMAL(5,2)	% IVA de la línea	DEFAULT 21

2.4 Entidad: credit_note (Nota de Crédito)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
credit_note_number	VARCHAR(32)	Número	UNIQUE, NC-2026-0001
invoice_id	INT	Factura rectificada	FK invoice.id, NOT NULL
reason	VARCHAR(64)	Motivo de rectificación	error|refund|discount|cancellation
amount	DECIMAL(12,2)	Importe abonado	NOT NULL
tax_amount	DECIMAL(12,2)	IVA del abono	NOT NULL
total	DECIMAL(12,2)	Total nota de crédito	NOT NULL
refund_status	VARCHAR(16)	Estado devolución	pending|processed|failed
stripe_refund_id	VARCHAR(64)	ID refund de Stripe	NULLABLE
created	DATETIME	Fecha creación	NOT NULL

 
3. Servicios Principales
3.1 InvoiceService
<?php namespace Drupal\jaraba_invoicing\Service;

class InvoiceService {
  
  public function createFromQuote(Quote $quote): Invoice {
    $invoice = Invoice::create([
      'tenant_id' => $quote->getTenantId(),
      'provider_id' => $quote->getProviderId(),
      'case_id' => $quote->getCaseId(),
      'quote_id' => $quote->id(),
      'invoice_number' => $this->generateNumber($quote->getTenantId()),
      'series' => 'FAC',
      'client_name' => $quote->getClientName(),
      'client_nif' => $quote->getClientNif(),
      'client_address' => $quote->getClientAddress(),
      'client_email' => $quote->getClientEmail(),
      'issue_date' => new \DateTime(),
      'due_date' => $this->calculateDueDate($quote->getPaymentTerms()),
      'subtotal' => $quote->getSubtotal(),
      'tax_rate' => $quote->getTaxRate(),
      'tax_amount' => $quote->getTaxAmount(),
      'irpf_rate' => $this->getIrpfRate($quote),
      'irpf_amount' => $this->calculateIrpf($quote),
      'total' => $this->calculateTotal($quote),
      'status' => 'draft',
    ]);
    
    // Copiar líneas del presupuesto
    foreach ($quote->getLineItems() as $line) {
      InvoiceLine::create([
        'invoice_id' => $invoice->id(),
        'description' => $line->getDescription(),
        'quantity' => $line->getQuantity(),
        'unit_price' => $line->getUnitPrice(),
        'line_total' => $line->getLineTotal(),
      ]);
    }
    
    return $invoice;
  }
  
  public function issue(Invoice $invoice): Invoice {
    if ($invoice->getStatus() !== 'draft') {
      throw new InvalidStateException('Solo borradores pueden emitirse');
    }
    
    // Bloquear número de factura (ya no se puede modificar)
    $invoice->setStatus('issued');
    $invoice->save();
    
    // Generar PDF
    $this->pdfGenerator->generate($invoice);
    
    // Crear factura en Stripe (si aplica)
    if ($invoice->getPaymentMethod() === 'stripe') {
      $stripeInvoice = $this->stripeService->createInvoice($invoice);
      $invoice->setStripeInvoiceId($stripeInvoice->id);
      $invoice->setStripePaymentUrl($stripeInvoice->hosted_invoice_url);
      $invoice->save();
    }
    
    $this->eventDispatcher->dispatch(new InvoiceIssuedEvent($invoice));
    return $invoice;
  }
  
  public function send(Invoice $invoice): Invoice {
    $invoice->setStatus('sent');
    $invoice->save();
    
    // Enviar email con PDF adjunto + link de pago
    $this->mailer->sendInvoice($invoice);
    
    $this->eventDispatcher->dispatch(new InvoiceSentEvent($invoice));
    return $invoice;
  }
  
  public function markAsPaid(Invoice $invoice, PaymentInfo $payment): Invoice {
    $invoice->setStatus('paid');
    $invoice->setPaidAt($payment->getPaidAt());
    $invoice->setPaidAmount($payment->getAmount());
    $invoice->save();
    
    $this->eventDispatcher->dispatch(new InvoicePaidEvent($invoice, $payment));
    return $invoice;
  }
}

 
3.2 StripeInvoiceService
<?php namespace Drupal\jaraba_invoicing\Service;

class StripeInvoiceService {
  
  public function createInvoice(Invoice $invoice): \Stripe\Invoice {
    $tenant = $this->tenantRepository->load($invoice->getTenantId());
    
    // Obtener o crear customer en Stripe
    $customer = $this->getOrCreateCustomer($invoice);
    
    // Crear items de factura
    foreach ($invoice->getLines() as $line) {
      \Stripe\InvoiceItem::create([
        'customer' => $customer->id,
        'description' => $line->getDescription(),
        'amount' => $line->getLineTotal() * 100, // Céntimos
        'currency' => 'eur',
      ], [
        'stripe_account' => $tenant->getStripeAccountId(), // Connect
      ]);
    }
    
    // Crear factura
    $stripeInvoice = \Stripe\Invoice::create([
      'customer' => $customer->id,
      'collection_method' => 'send_invoice',
      'days_until_due' => $this->getDaysUntilDue($invoice),
      'metadata' => [
        'jaraba_invoice_id' => $invoice->id(),
        'jaraba_tenant_id' => $invoice->getTenantId(),
      ],
    ], [
      'stripe_account' => $tenant->getStripeAccountId(),
    ]);
    
    // Finalizar factura para obtener URL de pago
    $stripeInvoice->finalizeInvoice();
    
    return $stripeInvoice;
  }
  
  public function handleWebhook(string $event, array $data): void {
    switch ($event) {
      case 'invoice.paid':
        $this->handleInvoicePaid($data);
        break;
      case 'invoice.payment_failed':
        $this->handlePaymentFailed($data);
        break;
    }
  }
}

4. APIs REST
Método	Endpoint	Descripción	Auth
POST	/api/v1/invoices	Crear factura manual	Provider
POST	/api/v1/invoices/from-quote/{uuid}	Crear desde presupuesto	Provider
GET	/api/v1/invoices	Listar facturas con filtros	Provider
GET	/api/v1/invoices/{uuid}	Detalle de factura	Provider
PATCH	/api/v1/invoices/{uuid}	Modificar borrador	Provider
POST	/api/v1/invoices/{uuid}/issue	Emitir factura	Provider
POST	/api/v1/invoices/{uuid}/send	Enviar al cliente	Provider
POST	/api/v1/invoices/{uuid}/mark-paid	Marcar como cobrada (manual)	Provider
GET	/api/v1/invoices/{uuid}/pdf	Descargar PDF	Provider
POST	/api/v1/invoices/{uuid}/credit-note	Crear nota de crédito	Provider
POST	/api/v1/webhooks/stripe/invoice	Webhook de Stripe	Stripe

 
5. Flujos de Automatización (ECA)
Código	Evento	Acciones
INV-001	quote.accepted	Crear factura desde presupuesto automáticamente (si configurado)
INV-002	invoice.issued	Generar PDF + enviar al cliente + crear evento en calendario
INV-003	invoice.due_soon (3 días)	Enviar recordatorio amable al cliente con link de pago
INV-004	invoice.overdue (día +1)	Enviar aviso de vencimiento + notificar profesional
INV-005	invoice.overdue (día +7)	Segundo aviso más firme + escalar a admin del despacho
INV-006	invoice.paid (webhook)	Actualizar estado + notificar profesional + enviar recibo
INV-007	credit_note.created	Procesar devolución en Stripe + notificar cliente
INV-008	cron.monthly (día 1)	Generar facturas recurrentes para suscripciones activas

6. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 12.1	Semana 37	Modelo datos invoice + invoice_line + credit_note + APIs CRUD	92_Presupuestador
Sprint 12.2	Semana 38	InvoiceService + generación PDF + numeración automática	Sprint 12.1
Sprint 12.3	Semana 39	StripeInvoiceService + webhooks + cobros online	Sprint 12.2
Sprint 12.4	Semana 40	ECA recordatorios + facturación recurrente + tests E2E	Sprint 12.3

6.1 Criterios de Aceptación
•	✓ Factura se crea automáticamente desde presupuesto aceptado
•	✓ PDF generado cumple requisitos legales españoles
•	✓ Cliente puede pagar online con Stripe desde el email
•	✓ Webhook actualiza estado automáticamente al cobrar
•	✓ Recordatorios automáticos 3 días antes y después de vencer
•	✓ Nota de crédito genera devolución automática en Stripe
•	✓ Facturación recurrente mensual funciona para suscripciones

--- Fin del Documento ---
