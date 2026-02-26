<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio para cargar, procesar y compilar plantillas MJML de email.
 *
 * PROPÓSITO:
 * Gestiona el catálogo completo de plantillas de email transaccional
 * de la plataforma Jaraba Impact. Cada plantilla se identifica por un
 * código único (e.g., AUTH_001) y se almacena como fichero MJML.
 *
 * FLUJO:
 * 1. Carga el fichero MJML correspondiente al template ID.
 * 2. Reemplaza las variables {{ variable }} con los valores proporcionados.
 * 3. Compila el MJML a HTML via MjmlCompilerService.
 * 4. Devuelve el HTML listo para envío.
 *
 * ESPECIFICACIÓN: Doc 139 - Marketing_AI_Stack_Native
 */
class TemplateLoaderService {

  /**
   * El servicio de compilación MJML.
   *
   * @var \Drupal\jaraba_email\Service\MjmlCompilerService
   */
  protected MjmlCompilerService $mjmlCompiler;

  /**
   * El logger para registrar eventos.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Mapa de template ID a ruta relativa del fichero MJML y descripción.
   *
   * @var array<string, array{file: string, description: string}>
   */
  protected const TEMPLATE_MAP = [
    // Auth templates (AUTH_001 - AUTH_005).
    'AUTH_001' => [
      'file' => 'auth/verify_email.mjml',
      'description' => 'Verificación de dirección de email',
    ],
    'AUTH_002' => [
      'file' => 'auth/welcome.mjml',
      'description' => 'Bienvenida tras registro exitoso',
    ],
    'AUTH_003' => [
      'file' => 'auth/password_reset.mjml',
      'description' => 'Solicitud de restablecimiento de contraseña',
    ],
    'AUTH_004' => [
      'file' => 'auth/password_changed.mjml',
      'description' => 'Confirmación de cambio de contraseña',
    ],
    'AUTH_005' => [
      'file' => 'auth/new_login.mjml',
      'description' => 'Notificación de nuevo inicio de sesión',
    ],

    // Billing templates (BILL_001 - BILL_007).
    'BILL_001' => [
      'file' => 'billing/invoice.mjml',
      'description' => 'Factura disponible',
    ],
    'BILL_002' => [
      'file' => 'billing/payment_failed.mjml',
      'description' => 'Error en el procesamiento del pago',
    ],
    'BILL_003' => [
      'file' => 'billing/subscription_created.mjml',
      'description' => 'Confirmación de nueva suscripción',
    ],
    'BILL_004' => [
      'file' => 'billing/plan_upgrade.mjml',
      'description' => 'Confirmación de cambio de plan',
    ],
    'BILL_005' => [
      'file' => 'billing/trial_ending.mjml',
      'description' => 'Aviso de fin de periodo de prueba',
    ],
    'BILL_006' => [
      'file' => 'billing/subscription_cancelled.mjml',
      'description' => 'Confirmación de cancelación de suscripción',
    ],
    'BILL_007' => [
      'file' => 'billing/dunning_notice.mjml',
      'description' => 'Aviso de pago pendiente (dunning)',
    ],

    // Marketplace templates (MKTP_001 - MKTP_006).
    'MKTP_001' => [
      'file' => 'marketplace/order_confirmed.mjml',
      'description' => 'Confirmación de pedido al comprador',
    ],
    'MKTP_002' => [
      'file' => 'marketplace/new_order_seller.mjml',
      'description' => 'Notificación de nuevo pedido al vendedor',
    ],
    'MKTP_003' => [
      'file' => 'marketplace/order_shipped.mjml',
      'description' => 'Notificación de envío del pedido',
    ],
    'MKTP_004' => [
      'file' => 'marketplace/order_delivered.mjml',
      'description' => 'Confirmación de entrega del pedido',
    ],
    'MKTP_005' => [
      'file' => 'marketplace/payout_processed.mjml',
      'description' => 'Confirmación de pago al vendedor',
    ],
    'MKTP_006' => [
      'file' => 'marketplace/new_review.mjml',
      'description' => 'Notificación de nueva valoración recibida',
    ],

    // Empleabilidad templates (EMPL_001 - EMPL_005).
    'EMPL_001' => [
      'file' => 'empleabilidad/job_match.mjml',
      'description' => 'Oferta de empleo compatible con el perfil',
    ],
    'EMPL_002' => [
      'file' => 'empleabilidad/application_sent.mjml',
      'description' => 'Confirmación de envío de candidatura',
    ],
    'EMPL_003' => [
      'file' => 'empleabilidad/new_application.mjml',
      'description' => 'Notificación de nueva candidatura al reclutador',
    ],
    'EMPL_004' => [
      'file' => 'empleabilidad/candidate_shortlisted.mjml',
      'description' => 'Notificación de preselección al candidato',
    ],
    'EMPL_005' => [
      'file' => 'empleabilidad/listing_expired.mjml',
      'description' => 'Aviso de expiración de oferta de empleo',
    ],

    // Empleabilidad sequence templates (EMPL_SEQ_001 - EMPL_SEQ_005).
    // Plan Elevación Empleabilidad v1 — Fase 6.
    'EMPL_SEQ_001' => [
      'file' => 'empleabilidad/seq_onboarding_welcome.mjml',
      'description' => 'Onboarding: Bienvenida post-diagnóstico de empleabilidad',
    ],
    'EMPL_SEQ_002' => [
      'file' => 'empleabilidad/seq_engagement_reactivation.mjml',
      'description' => 'Re-engagement: Reactivación candidato inactivo',
    ],
    'EMPL_SEQ_003' => [
      'file' => 'empleabilidad/seq_upsell_starter.mjml',
      'description' => 'Upsell: Promoción plan Starter tras actividad alta',
    ],
    'EMPL_SEQ_004' => [
      'file' => 'empleabilidad/seq_interview_prep.mjml',
      'description' => 'Post-entrevista: Preparación y tips para la entrevista',
    ],
    'EMPL_SEQ_005' => [
      'file' => 'empleabilidad/seq_post_hire.mjml',
      'description' => 'Post-empleo: Felicitación y retención tras contratación',
    ],

    // Emprendimiento templates (EMPR_001 - EMPR_006).
    'EMPR_001' => [
      'file' => 'emprendimiento/welcome_entrepreneur.mjml',
      'description' => 'Bienvenida al programa de emprendimiento',
    ],
    'EMPR_002' => [
      'file' => 'emprendimiento/diagnostic_completed.mjml',
      'description' => 'Resultados del diagnóstico de madurez digital',
    ],
    'EMPR_003' => [
      'file' => 'emprendimiento/canvas_milestone.mjml',
      'description' => 'Hito alcanzado en Business Model Canvas',
    ],
    'EMPR_004' => [
      'file' => 'emprendimiento/experiment_result.mjml',
      'description' => 'Resultado de experimento de validación',
    ],
    'EMPR_005' => [
      'file' => 'emprendimiento/mentor_matched.mjml',
      'description' => 'Notificación de mentor asignado',
    ],
    'EMPR_006' => [
      'file' => 'emprendimiento/weekly_progress.mjml',
      'description' => 'Resumen semanal de progreso del emprendedor',
    ],

    // Emprendimiento sequence templates (ENTR_SEQ_001 - ENTR_SEQ_005).
    // Plan Elevación Emprendimiento v2 — Fase 3 (G3).
    'ENTR_SEQ_001' => [
      'file' => 'emprendimiento/seq_onboarding_founder.mjml',
      'description' => 'Onboarding: Bienvenida fundador',
    ],
    'ENTR_SEQ_002' => [
      'file' => 'emprendimiento/seq_canvas_abandonment.mjml',
      'description' => 'Re-engagement: Canvas abandonado',
    ],
    'ENTR_SEQ_003' => [
      'file' => 'emprendimiento/seq_upsell_starter.mjml',
      'description' => 'Upsell: Promoción plan Starter',
    ],
    'ENTR_SEQ_004' => [
      'file' => 'emprendimiento/seq_mvp_celebration.mjml',
      'description' => 'Celebración: MVP validado',
    ],
    'ENTR_SEQ_005' => [
      'file' => 'emprendimiento/seq_post_funding.mjml',
      'description' => 'Post-funding: Retención y próximos pasos',
    ],

    // Fiscal compliance templates (FISC_001 - FISC_003).
    // Plan Implementacion Stack Cumplimiento Fiscal v1 — FASE 11 (F11-6).
    'FISC_001' => [
      'file' => 'fiscal/certificate_expiring.mjml',
      'description' => 'Alerta de certificado digital próximo a expirar',
    ],
    'FISC_002' => [
      'file' => 'fiscal/verifactu_chain_break.mjml',
      'description' => 'Alerta crítica de rotura de cadena VeriFactu',
    ],
    'FISC_003' => [
      'file' => 'fiscal/face_invoice_rejected.mjml',
      'description' => 'Notificación de factura rechazada por FACe',
    ],

    // Notification templates (NOTIF_001+).
    // Sprint 6 — REST Endpoints.
    'NOTIF_001' => [
      'file' => 'notifications/contact_form.mjml',
      'description' => 'Notificación de nuevo contacto desde formulario web',
    ],
  ];

  /**
   * Datos de ejemplo para previsualización de cada plantilla.
   *
   * @var array<string, array<string, string>>
   */
  protected const SAMPLE_DATA = [
    'AUTH_001' => [
      'user_name' => 'María García',
      'user_email' => 'maria@ejemplo.com',
      'verification_url' => 'https://jaraba.es/verify?token=abc123',
    ],
    'AUTH_002' => [
      'user_name' => 'María García',
      'vertical_name' => 'Comercio Conecta',
      'dashboard_url' => 'https://jaraba.es/dashboard',
    ],
    'AUTH_003' => [
      'user_name' => 'María García',
      'reset_url' => 'https://jaraba.es/reset?token=xyz789',
      'expiry_hours' => '24',
    ],
    'AUTH_004' => [
      'user_name' => 'María García',
      'change_date' => '12 de febrero de 2026, 14:30',
      'support_url' => 'https://jaraba.es/soporte',
    ],
    'AUTH_005' => [
      'user_name' => 'María García',
      'login_date' => '12 de febrero de 2026, 10:15',
      'login_ip' => '192.168.1.100',
      'login_device' => 'Chrome en Windows 11',
      'security_url' => 'https://jaraba.es/account/security',
    ],
    'BILL_001' => [
      'user_name' => 'Carlos López',
      'invoice_number' => 'INV-2026-0042',
      'invoice_date' => '1 de febrero de 2026',
      'amount' => '49,99 €',
      'plan_name' => 'Profesional',
      'invoice_url' => 'https://jaraba.es/billing/invoices/INV-2026-0042',
    ],
    'BILL_002' => [
      'user_name' => 'Carlos López',
      'amount' => '49,99 €',
      'last_four' => '4242',
      'retry_date' => '15 de febrero de 2026',
      'update_payment_url' => 'https://jaraba.es/billing/payment-methods',
    ],
    'BILL_003' => [
      'user_name' => 'Carlos López',
      'plan_name' => 'Profesional',
      'amount' => '49,99 €',
      'billing_cycle' => 'mes',
      'next_billing_date' => '1 de marzo de 2026',
    ],
    'BILL_004' => [
      'user_name' => 'Carlos López',
      'old_plan' => 'Básico',
      'new_plan' => 'Profesional',
      'new_amount' => '49,99 €/mes',
      'effective_date' => '12 de febrero de 2026',
    ],
    'BILL_005' => [
      'user_name' => 'Carlos López',
      'trial_end_date' => '26 de febrero de 2026',
      'days_remaining' => '7',
      'plan_name' => 'Profesional',
      'upgrade_url' => 'https://jaraba.es/billing/upgrade',
    ],
    'BILL_006' => [
      'user_name' => 'Carlos López',
      'cancel_date' => '12 de febrero de 2026',
      'access_end_date' => '1 de marzo de 2026',
      'reactivate_url' => 'https://jaraba.es/billing/reactivate',
    ],
    'BILL_007' => [
      'user_name' => 'Carlos López',
      'amount' => '49,99 €',
      'days_overdue' => '15',
      'update_payment_url' => 'https://jaraba.es/billing/payment-methods',
      'suspension_date' => '28 de febrero de 2026',
    ],
    'MKTP_001' => [
      'user_name' => 'Ana Martínez',
      'order_number' => 'ORD-20260212-001',
      'items' => 'Aceite de Oliva Virgen Extra (x2), Miel de Romero (x1)',
      'total' => '38,50 €',
      'estimated_delivery' => '17-19 de febrero de 2026',
    ],
    'MKTP_002' => [
      'user_name' => 'Pedro Ruiz',
      'order_number' => 'ORD-20260212-001',
      'buyer_name' => 'Ana Martínez',
      'items' => 'Aceite de Oliva Virgen Extra (x2), Miel de Romero (x1)',
      'total' => '38,50 €',
      'manage_url' => 'https://jaraba.es/seller/orders/ORD-20260212-001',
    ],
    'MKTP_003' => [
      'user_name' => 'Ana Martínez',
      'order_number' => 'ORD-20260212-001',
      'tracking_number' => 'ES1234567890',
      'tracking_url' => 'https://tracking.ejemplo.com/ES1234567890',
      'carrier' => 'SEUR',
    ],
    'MKTP_004' => [
      'user_name' => 'Ana Martínez',
      'order_number' => 'ORD-20260212-001',
      'review_url' => 'https://jaraba.es/orders/ORD-20260212-001/review',
    ],
    'MKTP_005' => [
      'user_name' => 'Pedro Ruiz',
      'payout_amount' => '145,20 €',
      'payout_date' => '12 de febrero de 2026',
      'transaction_count' => '8',
      'account_last_four' => '6789',
    ],
    'MKTP_006' => [
      'user_name' => 'Pedro Ruiz',
      'reviewer_name' => 'Ana Martínez',
      'rating' => '★★★★★ (5/5)',
      'review_text' => 'Excelente calidad. El aceite es increíble y la miel riquísima. Envío rápido.',
      'product_name' => 'Aceite de Oliva Virgen Extra',
    ],
    'EMPL_001' => [
      'user_name' => 'Laura Fernández',
      'job_title' => 'Desarrolladora Full-Stack',
      'company_name' => 'TechImpacto S.L.',
      'location' => 'Sevilla (híbrido)',
      'match_score' => '92',
      'job_url' => 'https://jaraba.es/empleabilidad/jobs/dev-fullstack-123',
    ],
    'EMPL_002' => [
      'user_name' => 'Laura Fernández',
      'job_title' => 'Desarrolladora Full-Stack',
      'company_name' => 'TechImpacto S.L.',
      'application_date' => '12 de febrero de 2026',
    ],
    'EMPL_003' => [
      'user_name' => 'Recursos Humanos',
      'candidate_name' => 'Laura Fernández',
      'job_title' => 'Desarrolladora Full-Stack',
      'match_score' => '92',
      'review_url' => 'https://jaraba.es/recruiter/applications/app-456',
    ],
    'EMPL_004' => [
      'user_name' => 'Laura Fernández',
      'job_title' => 'Desarrolladora Full-Stack',
      'company_name' => 'TechImpacto S.L.',
      'next_steps' => 'Entrevista técnica programada para el 20 de febrero de 2026 a las 10:00h. Recibirás un enlace de videoconferencia por email.',
    ],
    'EMPL_005' => [
      'user_name' => 'Recursos Humanos',
      'job_title' => 'Desarrolladora Full-Stack',
      'applications_count' => '24',
      'renew_url' => 'https://jaraba.es/recruiter/listings/dev-fullstack-123/renew',
    ],
    // Empleabilidad sequence sample data (EMPL_SEQ_001 - EMPL_SEQ_005).
    'EMPL_SEQ_001' => [
      'user_name' => 'Laura Fernández',
      'profile_type' => 'En Progreso',
      'score' => '68',
      'primary_gap' => 'Presencia digital y LinkedIn',
      'dashboard_url' => 'https://jaraba.es/empleabilidad/dashboard',
    ],
    'EMPL_SEQ_002' => [
      'user_name' => 'Laura Fernández',
      'matching_jobs_count' => '12',
      'job_1_title' => 'Desarrolladora Full-Stack',
      'job_1_company' => 'TechImpacto S.L.',
      'job_1_match' => '92',
      'job_2_title' => 'Frontend Developer',
      'job_2_company' => 'Digital Solutions',
      'job_2_match' => '87',
      'job_3_title' => 'Software Engineer',
      'job_3_company' => 'InnovaCode',
      'job_3_match' => '84',
      'jobs_url' => 'https://jaraba.es/empleabilidad/jobs',
    ],
    'EMPL_SEQ_003' => [
      'user_name' => 'Laura Fernández',
      'total_applications' => '3',
      'profile_completion' => '72',
      'upgrade_url' => 'https://jaraba.es/upgrade?vertical=empleabilidad&source=email',
    ],
    'EMPL_SEQ_004' => [
      'user_name' => 'Laura Fernández',
      'job_title' => 'Desarrolladora Full-Stack',
      'company_name' => 'TechImpacto S.L.',
      'copilot_url' => 'https://jaraba.es/empleabilidad/copilot?mode=interview_prep',
    ],
    'EMPL_SEQ_005' => [
      'user_name' => 'Laura Fernández',
      'job_title' => 'Desarrolladora Full-Stack',
      'company_name' => 'TechImpacto S.L.',
      'credential_name' => 'Job Application Expert',
      'total_applications' => '18',
      'days_on_platform' => '45',
      'total_credits' => '1.250',
      'share_url' => 'https://jaraba.es/credentials/share/cert-789',
      'referral_url' => 'https://jaraba.es/referral?source=post_hire',
    ],
    'EMPR_001' => [
      'user_name' => 'Pablo Emprendedor',
      'dashboard_url' => 'https://jaraba.es/emprendimiento/copilot/dashboard',
    ],
    'EMPR_002' => [
      'user_name' => 'Pablo Emprendedor',
      'maturity_score' => '62',
      'maturity_level' => 'Intermedio',
      'carril' => 'ACELERA',
      'action_plan_url' => 'https://jaraba.es/emprendimiento/copilot/dashboard',
    ],
    'EMPR_003' => [
      'user_name' => 'Pablo Emprendedor',
      'milestone_name' => 'Canvas creado',
      'canvas_name' => 'App de delivery rural',
      'blocks_completed' => '5',
      'validation_percentage' => '45',
      'canvas_url' => 'https://jaraba.es/emprendimiento/bmc',
    ],
    'EMPR_004' => [
      'user_name' => 'Pablo Emprendedor',
      'experiment_title' => 'Landing Page Smoke Test',
      'decision' => 'PERSEVERE',
      'result' => 'VALIDATED',
      'points_awarded' => '100',
      'key_learning' => 'El 23% de los visitantes se registraron, validando la propuesta de valor.',
      'current_level' => '2',
      'experiments_url' => 'https://jaraba.es/emprendimiento/experimentos/gestion',
    ],
    'EMPR_005' => [
      'user_name' => 'Pablo Emprendedor',
      'mentor_name' => 'Ana Mentora',
      'mentor_specialty' => 'Validación de modelos SaaS',
      'match_score' => '88',
      'schedule_url' => 'https://jaraba.es/mentoring/schedule',
    ],
    'EMPR_006' => [
      'user_name' => 'Pablo Emprendedor',
      'program_week' => '4',
      'current_phase' => 'Engagement',
      'impact_points' => '250',
      'validated_hypotheses' => '3',
      'completed_experiments' => '2',
      'copilot_suggestion' => 'Esta semana, enfócate en completar tu Value Proposition Canvas. El modo VPC Designer te guiará paso a paso.',
      'dashboard_url' => 'https://jaraba.es/emprendimiento/copilot/dashboard',
    ],
    // Emprendimiento sequence sample data (ENTR_SEQ_001 - ENTR_SEQ_005).
    'ENTR_SEQ_001' => [
      'user_name' => 'Pablo Emprendedor',
      'idea_title' => 'App de delivery rural',
      'sector' => 'AgriTech / Logística',
      'dashboard_url' => 'https://jaraba.es/emprendimiento/copilot/dashboard',
    ],
    'ENTR_SEQ_002' => [
      'user_name' => 'Pablo Emprendedor',
      'canvas_completion' => '35',
      'blocks_pending' => 'Propuesta de Valor, Segmentos de Cliente, Canales, Flujos de Ingreso',
      'bmc_url' => 'https://jaraba.es/emprendimiento/bmc',
    ],
    'ENTR_SEQ_003' => [
      'user_name' => 'Pablo Emprendedor',
      'total_hypotheses' => '3',
      'profile_completion' => '68',
      'upgrade_url' => 'https://jaraba.es/upgrade?vertical=emprendimiento&source=email',
    ],
    'ENTR_SEQ_004' => [
      'user_name' => 'Pablo Emprendedor',
      'experiment_name' => 'Landing Page Smoke Test',
      'decision' => 'VALIDATED',
      'hypothesis_name' => 'Los agricultores rurales pagarían por delivery express',
      'share_url' => 'https://jaraba.es/credentials/share/mvp-789',
    ],
    'ENTR_SEQ_005' => [
      'user_name' => 'Pablo Emprendedor',
      'funding_name' => 'ENISA Jóvenes Emprendedores 2026',
      'amount' => '25.000 €',
      'next_steps_url' => 'https://jaraba.es/funding/application/FA-001/next-steps',
      'referral_url' => 'https://jaraba.es/referral?source=post_funding',
    ],
    // Fiscal compliance sample data (FISC_001 - FISC_003).
    'FISC_001' => [
      'user_name' => 'Carlos Administrador',
      'tenant_name' => 'Empresa Ejemplo S.L.',
      'days_remaining' => '15',
      'expiry_date' => '3 de marzo de 2026',
      'certificate_subject' => 'CN=EMPRESA EJEMPLO SL - CIF B12345678',
      'certificate_issuer' => 'AC FNMT Usuarios',
      'renew_url' => 'https://jaraba.es/admin/jaraba/fiscal',
    ],
    'FISC_002' => [
      'user_name' => 'Carlos Administrador',
      'tenant_name' => 'Empresa Ejemplo S.L.',
      'affected_record' => 'VF-2026-000042 (ID: 42)',
      'expected_hash' => 'a1b2c3d4e5f6...',
      'actual_hash' => 'f6e5d4c3b2a1...',
      'detection_date' => '16 de febrero de 2026, 10:30',
      'dashboard_url' => 'https://jaraba.es/admin/jaraba/verifactu',
    ],
    'FISC_003' => [
      'user_name' => 'Carlos Administrador',
      'tenant_name' => 'Empresa Ejemplo S.L.',
      'invoice_number' => 'FE-2026-000015',
      'recipient_name' => 'Ayuntamiento de Sevilla',
      'amount' => '12.500,00 €',
      'organo_gestor' => 'Concejalía de Hacienda',
      'dir3_code' => 'L01410917',
      'rejection_reason' => 'Error en NIF del emisor: no coincide con el certificado',
      'rejection_date' => '15 de febrero de 2026',
      'invoice_url' => 'https://jaraba.es/admin/jaraba/facturae/15',
    ],
    // Notification sample data (NOTIF_001+).
    'NOTIF_001' => [
      'sender_name' => 'María García',
      'sender_email' => 'maria@ejemplo.com',
      'subject' => 'Consulta sobre plan Enterprise',
      'message' => 'Hola, estoy interesada en el plan Enterprise para una organización de 200 empleados. ¿Podemos agendar una demo?',
      'source' => 'web',
      'date' => '26 de febrero de 2026, 14:30',
      'ip_address' => '83.45.123.45',
      'crm_url' => 'https://jaraba.es/admin/jaraba/crm/contacts',
    ],
  ];

  /**
   * Ruta base a las plantillas MJML.
   *
   * @var string
   */
  protected string $templateBasePath;

  /**
   * Construye un TemplateLoaderService.
   *
   * @param \Drupal\jaraba_email\Service\MjmlCompilerService $mjmlCompiler
   *   El servicio de compilación MJML.
   * @param \Psr\Log\LoggerInterface $logger
   *   El servicio de logging.
   */
  public function __construct(MjmlCompilerService $mjmlCompiler, LoggerInterface $logger) {
    $this->mjmlCompiler = $mjmlCompiler;
    $this->logger = $logger;
    $this->templateBasePath = dirname(__DIR__, 2) . '/templates/mjml';
  }

  /**
   * Carga y compila una plantilla de email.
   *
   * Busca el fichero MJML correspondiente al template ID, reemplaza
   * las variables con los valores proporcionados, y compila el resultado
   * a HTML mediante MjmlCompilerService.
   *
   * Variables comunes (inyectadas automáticamente si no se proporcionan):
   * - site_name: "Jaraba"
   * - site_url: URL base del sitio
   * - current_year: Año actual
   * - unsubscribe_url: URL de cancelación de suscripción
   *
   * @param string $templateId
   *   Identificador único de la plantilla (e.g., 'AUTH_001').
   * @param array $variables
   *   Array asociativo de variables a reemplazar en la plantilla.
   *
   * @return string
   *   HTML compilado listo para envío por email.
   *
   * @throws \InvalidArgumentException
   *   Si el template ID no existe en el catálogo.
   * @throws \RuntimeException
   *   Si el fichero MJML no se encuentra en disco.
   */
  public function load(string $templateId, array $variables = []): string {
    if (!isset(self::TEMPLATE_MAP[$templateId])) {
      $this->logger->error('Template ID no válido: @id', ['@id' => $templateId]);
      throw new \InvalidArgumentException("Template ID no válido: {$templateId}");
    }

    $templateFile = $this->templateBasePath . '/' . self::TEMPLATE_MAP[$templateId]['file'];

    if (!file_exists($templateFile)) {
      $this->logger->error('Fichero de plantilla no encontrado: @file', ['@file' => $templateFile]);
      throw new \RuntimeException("Fichero de plantilla no encontrado: {$templateFile}");
    }

    $mjml = file_get_contents($templateFile);

    // Inyectar variables comunes si no están proporcionadas.
    $defaults = $this->getDefaultVariables();
    $variables = array_merge($defaults, $variables);

    // Reemplazar variables {{ variable_name }} en el MJML.
    $mjml = $this->replaceVariables($mjml, $variables);

    // Compilar MJML a HTML.
    $html = $this->mjmlCompiler->compile($mjml);

    $this->logger->info('Plantilla @id compilada correctamente.', ['@id' => $templateId]);

    return $html;
  }

  /**
   * Devuelve la lista de plantillas disponibles.
   *
   * @return array<string, array{id: string, description: string, file: string}>
   *   Array indexado por template ID con información de cada plantilla.
   */
  public function getAvailableTemplates(): array {
    $templates = [];

    foreach (self::TEMPLATE_MAP as $id => $info) {
      $templates[$id] = [
        'id' => $id,
        'description' => $info['description'],
        'file' => $info['file'],
      ];
    }

    return $templates;
  }

  /**
   * Genera una previsualización de una plantilla con datos de ejemplo.
   *
   * Útil para revisión en backoffice y testing. Carga la plantilla
   * con datos ficticios representativos para ver el resultado final.
   *
   * @param string $templateId
   *   Identificador único de la plantilla (e.g., 'AUTH_001').
   *
   * @return string
   *   HTML compilado con datos de ejemplo.
   *
   * @throws \InvalidArgumentException
   *   Si el template ID no existe en el catálogo.
   */
  public function preview(string $templateId): string {
    if (!isset(self::SAMPLE_DATA[$templateId])) {
      $this->logger->warning('No hay datos de ejemplo para el template: @id', ['@id' => $templateId]);
      return $this->load($templateId);
    }

    return $this->load($templateId, self::SAMPLE_DATA[$templateId]);
  }

  /**
   * Reemplaza variables en formato {{ variable_name }} en el contenido.
   *
   * Soporta tanto {{ variable }} como {{variable}} (con o sin espacios).
   * Las variables no encontradas en el array se dejan intactas para
   * facilitar la depuración.
   *
   * @param string $content
   *   El contenido con variables a reemplazar.
   * @param array $variables
   *   Array asociativo nombre => valor.
   *
   * @return string
   *   Contenido con las variables reemplazadas.
   */
  protected function replaceVariables(string $content, array $variables): string {
    foreach ($variables as $name => $value) {
      // Soportar {{ variable }} y {{variable}}.
      $content = str_replace(
        ["{{ {$name} }}", "{{{$name}}}"],
        (string) $value,
        $content
      );
    }

    return $content;
  }

  /**
   * Obtiene las variables comunes por defecto para todas las plantillas.
   *
   * @return array<string, string>
   *   Variables comunes con valores por defecto.
   */
  protected function getDefaultVariables(): array {
    global $base_url;

    $siteUrl = $base_url ?? 'https://jaraba.es';

    return [
      'site_name' => 'Jaraba',
      'site_url' => $siteUrl,
      'current_year' => date('Y'),
      'unsubscribe_url' => $siteUrl . '/email/unsubscribe',
    ];
  }

}
