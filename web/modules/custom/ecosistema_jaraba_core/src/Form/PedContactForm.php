<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Flood\FloodInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario público de contacto para el meta-sitio PED.
 *
 * Integración:
 * - jaraba_crm: Crea crm_contact + crm_activity
 * - jaraba_email: Suscribe a lista de leads
 * - Notificación por email al administrador.
 *
 * Seguridad:
 * - Flood API: 5 envíos/hora por IP
 * - Honeypot: campo oculto
 * - GDPR: consentimiento obligatorio
 * - Sanitización de inputs
 */
class PedContactForm extends FormBase {

  /**
   * Flood service.
   */
  protected FloodInterface $flood;

  /**
   * Logger.
   */
  protected LoggerInterface $logger;

  /**
   * CRM Contact service (optional cross-module).
   */
  protected ?object $crmContactService;

  /**
   * CRM Activity service (optional cross-module).
   */
  protected ?object $crmActivityService;

  /**
   * Email Subscriber service (optional cross-module).
   */
  protected ?object $emailSubscriberService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->flood = $container->get('flood');
    $instance->logger = $container->get('logger.channel.ecosistema_jaraba_core');
    $instance->crmContactService = $container->has('jaraba_crm.contact')
      ? $container->get('jaraba_crm.contact')
      : NULL;
    $instance->crmActivityService = $container->has('jaraba_crm.activity')
      ? $container->get('jaraba_crm.activity')
      : NULL;
    $instance->emailSubscriberService = $container->has('jaraba_email.subscriber_service')
      ? $container->get('jaraba_email.subscriber_service')
      : NULL;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ped_contact_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attributes']['class'][] = 'ped-contact-form';

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre completo'),
      '#required' => TRUE,
      '#maxlength' => 128,
      '#attributes' => [
        'placeholder' => $this->t('Tu nombre y apellidos'),
        'class' => ['ped-contact-form__input'],
        'autocomplete' => 'name',
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => [
        'placeholder' => $this->t('tu@email.com'),
        'class' => ['ped-contact-form__input'],
        'autocomplete' => 'email',
      ],
    ];

    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Telefono'),
      '#maxlength' => 20,
      '#attributes' => [
        'placeholder' => $this->t('+34 600 000 000'),
        'class' => ['ped-contact-form__input'],
        'autocomplete' => 'tel',
      ],
    ];

    $form['inquiry_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo de consulta'),
      '#required' => TRUE,
      '#options' => [
        '' => $this->t('— Selecciona —'),
        'institutional' => $this->t('Colaboracion institucional / B2G'),
        'investor' => $this->t('Informacion para inversores'),
        'press' => $this->t('Prensa y medios'),
        'partnership' => $this->t('Propuesta de partnership'),
        'demo' => $this->t('Solicitar demo de la plataforma'),
        'legal' => $this->t('Consulta sobre JarabaLex'),
        'employment' => $this->t('Oportunidades profesionales'),
        'other' => $this->t('Otra consulta'),
      ],
      '#attributes' => [
        'class' => ['ped-contact-form__select'],
      ],
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mensaje'),
      '#required' => TRUE,
      '#rows' => 5,
      '#maxlength' => 2000,
      '#attributes' => [
        'placeholder' => $this->t('Cuentanos en que podemos ayudarte...'),
        'class' => ['ped-contact-form__textarea'],
      ],
    ];

    // Honeypot (campo oculto para detectar bots).
    $form['website_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Website'),
      '#attributes' => [
        'class' => ['ped-contact-form__honeypot'],
        'tabindex' => '-1',
        'autocomplete' => 'off',
      ],
    ];

    $form['gdpr_consent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('He leido y acepto la <a href="/politica-privacidad" target="_blank">politica de privacidad</a>. Plataforma de Ecosistemas Digitales S.L. tratara mis datos para gestionar esta consulta y responderme por email. Puedo ejercer mis derechos ARCO en info@plataformadeecosistemas.es.'),
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['ped-contact-form__checkbox'],
      ],
    ];

    $form['newsletter_optin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Deseo recibir informacion sobre novedades y servicios de PED.'),
      '#attributes' => [
        'class' => ['ped-contact-form__checkbox'],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enviar mensaje'),
      '#attributes' => [
        'class' => ['ped-cta-inline', 'ped-cta-inline--primary'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Honeypot check.
    if (!empty($form_state->getValue('website_url'))) {
      $form_state->setErrorByName('', $this->t('Error de validacion.'));
      return;
    }

    // Flood control: 5 envíos/hora por IP.
    $ip = $this->getRequest()->getClientIp();
    if (!$this->flood->isAllowed('ped_contact_form', 5, 3600, $ip)) {
      $form_state->setErrorByName('', $this->t('Has enviado demasiados mensajes. Intentalo de nuevo mas tarde.'));
      return;
    }

    // Email validation.
    $email = $form_state->getValue('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email', $this->t('Introduce un email valido.'));
    }

    // GDPR consent required.
    if (empty($form_state->getValue('gdpr_consent'))) {
      $form_state->setErrorByName('gdpr_consent', $this->t('Debes aceptar la politica de privacidad.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $ip = $this->getRequest()->getClientIp();
    $this->flood->register('ped_contact_form', 3600, $ip);

    $name = htmlspecialchars($form_state->getValue('name'), ENT_QUOTES, 'UTF-8');
    $email = $form_state->getValue('email');
    $phone = htmlspecialchars($form_state->getValue('phone') ?? '', ENT_QUOTES, 'UTF-8');
    $inquiryType = $form_state->getValue('inquiry_type');
    $message = htmlspecialchars($form_state->getValue('message'), ENT_QUOTES, 'UTF-8');
    $newsletterOptin = !empty($form_state->getValue('newsletter_optin'));

    // Split name into first/last.
    $nameParts = explode(' ', $name, 2);
    $firstName = $nameParts[0];
    $lastName = $nameParts[1] ?? '';

    // Tenant ID for PED = 7.
    $tenantId = 7;

    // 1. Create CRM Contact.
    $contactId = NULL;
    if ($this->crmContactService) {
      try {
        $contact = $this->crmContactService->create([
          'first_name' => $firstName,
          'last_name' => $lastName,
          'email' => $email,
          'phone' => $phone,
          'source' => 'website',
          'tenant_id' => $tenantId,
          'uid' => 0,
        ]);
        $contactId = (int) $contact->id();
      }
      catch (\Throwable $e) {
        $this->logger->error('PED contact form: CRM contact creation failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // 2. Log CRM Activity.
    if ($this->crmActivityService && $contactId) {
      try {
        $typeLabel = $form['inquiry_type']['#options'][$inquiryType] ?? $inquiryType;
        $this->crmActivityService->create([
          'subject' => "Formulario web: $typeLabel",
          'type' => 'email',
          'contact_id' => $contactId,
          'notes' => $message,
          'tenant_id' => $tenantId,
          'uid' => 0,
        ]);
      }
      catch (\Throwable $e) {
        $this->logger->error('PED contact form: CRM activity creation failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // 3. Subscribe to email list (if opted in).
    if ($newsletterOptin && $this->emailSubscriberService) {
      try {
        // List ID 1 = default tenant list (will be created if needed).
        $this->emailSubscriberService->subscribe($email, 1, [
          'first_name' => $firstName,
          'last_name' => $lastName,
          'source' => 'form',
          'gdpr_consent' => TRUE,
          'tenant_id' => $tenantId,
        ]);
      }
      catch (\Throwable $e) {
        $this->logger->error('PED contact form: Email subscription failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // 4. Send notification email to admin.
    try {
      $mailManager = \Drupal::service('plugin.manager.mail');
      $mailManager->mail(
        'ecosistema_jaraba_core',
        'ped_contact_notification',
        'info@plataformadeecosistemas.es',
        'es',
        [
          'contact_name' => $name,
          'contact_email' => $email,
          'contact_phone' => $phone,
          'inquiry_type' => $form['inquiry_type']['#options'][$inquiryType] ?? $inquiryType,
          'message' => $message,
        ],
      );
    }
    catch (\Throwable $e) {
      $this->logger->error('PED contact form: Email notification failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    // 5. Log.
    $this->logger->info('PED contact form submission from @name (@email) — @type', [
      '@name' => $name,
      '@email' => $email,
      '@type' => $inquiryType,
    ]);

    $this->messenger()->addStatus($this->t('Gracias @name. Hemos recibido tu mensaje y te responderemos en menos de 24 horas.', [
      '@name' => $firstName,
    ]));
  }

}
