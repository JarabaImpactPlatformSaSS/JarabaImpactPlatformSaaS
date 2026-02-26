<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Flood\FloodInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Public contact form endpoint.
 *
 * Receives POST JSON from /contacto form with:
 * - name (required)
 * - email (required)
 * - subject (optional)
 * - message (required)
 * - gdpr_consent (required, boolean)
 *
 * Security:
 * - Flood rate limiting: 3 messages/hour per IP
 * - Honeypot validation (checked client-side)
 * - CSRF token via X-CSRF-Token header
 * - Input sanitization
 *
 * Messages are logged and can be sent via hook_mail().
 */
class PublicContactController extends ControllerBase {

  public function __construct(
    protected FloodInterface $flood,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('flood'),
      $container->get('logger.factory')->get('ecosistema_jaraba_core'),
    );
  }

  /**
   * Handles public contact form submission.
   */
  public function submit(Request $request): JsonResponse {
    // Rate limiting: 3 messages per hour per IP.
    $ip = $request->getClientIp() ?? 'unknown';
    $floodName = 'ecosistema_jaraba_core.public_contact';

    if (!$this->flood->isAllowed($floodName, 3, 3600, $ip)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Has enviado demasiados mensajes. Inténtalo de nuevo en una hora.'),
      ], 429);
    }

    // Parse JSON body.
    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Formato de datos inválido.'),
      ], 400);
    }

    // Validate required fields.
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');
    $gdpr = !empty($data['gdpr_consent']);

    if ($name === '' || $email === '' || $message === '') {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Por favor, completa los campos obligatorios.'),
      ], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('El email no es válido.'),
      ], 400);
    }

    if (!$gdpr) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Debes aceptar la política de privacidad.'),
      ], 400);
    }

    // Sanitize.
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    // Log the contact.
    $this->logger->info('Contact form: @name (@email) - Subject: @subject', [
      '@name' => $name,
      '@email' => $email,
      '@subject' => $subject ?: 'N/A',
    ]);

    // Send notification email to admin.
    $adminEmail = theme_get_setting('contact_email', 'ecosistema_jaraba_theme') ?: '';
    if ($adminEmail) {
      try {
        $mailManager = \Drupal::service('plugin.manager.mail');
        $mailManager->mail('ecosistema_jaraba_core', 'contact_form_notification', $adminEmail, 'es', [
          'sender_name' => $name,
          'sender_email' => $email,
          'subject' => $subject ?: 'Contacto general',
          'message' => $message,
        ]);
      }
      catch (\Exception $e) {
        $this->logger->error('Failed to send contact notification: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Register flood event.
    $this->flood->register($floodName, 3600, $ip);

    return new JsonResponse([
      'status' => 'success',
      'message' => $this->t('Mensaje enviado correctamente.'),
    ]);
  }

}
