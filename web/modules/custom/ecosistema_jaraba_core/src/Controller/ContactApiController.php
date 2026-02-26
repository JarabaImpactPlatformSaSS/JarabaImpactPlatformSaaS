<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST Controller for the public contact form.
 *
 * Sprint 6 — POST /api/v1/public/contact
 *
 * Receives contact form submissions, validates, stores in DB,
 * and sends notification email to the site admin.
 *
 * SECURITY:
 * - Rate limiting: max 5 submissions per IP per hour
 * - Field sanitization: strip_tags on all fields
 * - GDPR: no tracking cookies, data stored in DB only
 * - Public access (no authentication required)
 */
class ContactApiController extends ControllerBase {

  /**
   * Database connection.
   */
  protected Connection $database;

  /**
   * Mail manager.
   */
  protected MailManagerInterface $mailManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->mailManager = $container->get('plugin.manager.mail');
    return $instance;
  }

  /**
   * Handles POST /api/v1/public/contact.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON with status.
   */
  public function submit(Request $request): JsonResponse {
    // Parse JSON body.
    $data = json_decode($request->getContent(), TRUE);
    if (empty($data)) {
      return new JsonResponse(['error' => 'Invalid request body'], 400);
    }

    // Validate required fields.
    $name = trim(strip_tags($data['name'] ?? ''));
    $email = trim(strip_tags($data['email'] ?? ''));
    $subject = trim(strip_tags($data['subject'] ?? 'Contacto desde web'));
    $message = trim(strip_tags($data['message'] ?? ''));
    $source = trim(strip_tags($data['source'] ?? 'unknown'));

    if (empty($name) || empty($email) || empty($message)) {
      return new JsonResponse([
        'error' => 'Missing required fields: name, email, message',
      ], 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return new JsonResponse(['error' => 'Invalid email address'], 422);
    }

    // Rate limiting: max 5 per IP per hour.
    $ip = $request->getClientIp();
    $oneHourAgo = \Drupal::time()->getRequestTime() - 3600;

    try {
      $recentCount = $this->database->select('contact_submissions', 'cs')
        ->condition('ip_address', $ip)
        ->condition('created', $oneHourAgo, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();

      if ((int) $recentCount >= 5) {
        return new JsonResponse([
          'error' => 'Too many submissions. Please try again later.',
        ], 429);
      }
    }
    catch (\Exception $e) {
      // Table may not exist yet — will be created on first run.
    }

    // Store in database.
    try {
      $this->ensureTable();
      $this->database->insert('contact_submissions')
        ->fields([
          'name' => mb_substr($name, 0, 255),
          'email' => mb_substr($email, 0, 255),
          'subject' => mb_substr($subject, 0, 255),
          'message' => mb_substr($message, 0, 5000),
          'source' => mb_substr($source, 0, 50),
          'ip_address' => $ip,
          'created' => \Drupal::time()->getRequestTime(),
          'status' => 'new',
        ])
        ->execute();
    }
    catch (\Exception $e) {
      $this->getLogger('ecosistema_jaraba_core')->error(
        'Contact form DB error: @error',
        ['@error' => $e->getMessage()]
      );
    }

    // Integration: Create CRM contact if jaraba_crm module is available.
    $this->createCrmContact($name, $email, $subject, $message);


    // Send notification email to admin.
    $this->sendNotification($name, $email, $subject, $message);

    return new JsonResponse([
      'status' => 'ok',
      'message' => 'Mensaje recibido correctamente.',
    ]);
  }

  /**
   * Ensures the contact_submissions table exists.
   */
  protected function ensureTable(): void {
    if ($this->database->schema()->tableExists('contact_submissions')) {
      return;
    }

    $this->database->schema()->createTable('contact_submissions', [
      'fields' => [
        'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
        'name' => ['type' => 'varchar', 'length' => 255, 'not null' => TRUE],
        'email' => ['type' => 'varchar', 'length' => 255, 'not null' => TRUE],
        'subject' => ['type' => 'varchar', 'length' => 255, 'not null' => TRUE, 'default' => ''],
        'message' => ['type' => 'text', 'size' => 'medium', 'not null' => TRUE],
        'source' => ['type' => 'varchar', 'length' => 50, 'not null' => TRUE, 'default' => 'web'],
        'ip_address' => ['type' => 'varchar', 'length' => 45, 'not null' => TRUE],
        'created' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
        'status' => ['type' => 'varchar', 'length' => 20, 'not null' => TRUE, 'default' => 'new'],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'ip_created' => ['ip_address', 'created'],
        'status' => ['status'],
        'created' => ['created'],
      ],
    ]);
  }

  /**
   * Sends email notification to admin using premium email templates.
   *
   * Uses jaraba_email.template_loader (NOTIF_001) when available,
   * falls back to Drupal MailManager with plain text.
   */
  protected function sendNotification(string $name, string $email, string $subject, string $message): void {
    $config = \Drupal::config('ecosistema_jaraba_theme.settings');
    $adminEmail = $config->get('contact_email') ?: 'info@jarabaimpact.com';

    try {
      $this->mailManager->mail(
        'ecosistema_jaraba_core',
        'contact_notification',
        $adminEmail,
        'es',
        [
          'subject' => "[Contacto Web] {$subject}",
          'sender_name' => $name,
          'sender_email' => $email,
          'message' => $message,
          'source' => 'web',
          'date' => date('d/m/Y H:i'),
          'ip_address' => \Drupal::request()->getClientIp(),
        ],
        $email,
        TRUE
      );
    }
    catch (\Exception $e) {
      $this->getLogger('ecosistema_jaraba_core')->warning(
        'Contact notification email failed: @error',
        ['@error' => $e->getMessage()]
      );
    }
  }

  /**
   * Creates a CRM contact from a contact form submission.
   *
   * Integrates with jaraba_crm module if available.
   * Source is set to 'web_contact_form' for tracking origin.
   */
  protected function createCrmContact(string $name, string $email, string $subject, string $message): void {
    if (!\Drupal::hasService('jaraba_crm.contact')) {
      return;
    }

    try {
      /** @var \Drupal\jaraba_crm\Service\ContactService $contactService */
      $contactService = \Drupal::service('jaraba_crm.contact');

      // Split name into first/last.
      $parts = explode(' ', $name, 2);
      $firstName = $parts[0];
      $lastName = $parts[1] ?? '';

      $contactService->create([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'source' => 'web_contact_form',
        'notes' => "[{$subject}] {$message}",
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('ecosistema_jaraba_core')->warning(
        'CRM contact creation failed: @error',
        ['@error' => $e->getMessage()]
      );
    }
  }

}

