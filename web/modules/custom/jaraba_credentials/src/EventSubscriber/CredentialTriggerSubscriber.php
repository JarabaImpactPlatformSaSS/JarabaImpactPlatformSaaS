<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jaraba_credentials\Event\CredentialEvents;
use Drupal\jaraba_credentials\Event\CredentialIssuedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Suscriptor reactivo para eventos del ciclo de vida de credenciales.
 *
 * P1-04: Reacciona en tiempo real a la emision de credenciales
 * para despachar notificaciones, webhooks y actualizaciones.
 *
 * Reemplaza la dependencia exclusiva de cron para notificaciones,
 * proporcionando reaccion inmediata tras la emision.
 *
 * FLUJO:
 * 1. CredentialIssuer emite credencial y despacha CredentialIssuedEvent.
 * 2. Este suscriptor recibe el evento y ejecuta:
 *    a) Encola email de notificacion al destinatario.
 *    b) Despacha webhook si el tenant tiene configurados endpoints.
 *    c) Registra metrica de emision para analytics.
 */
class CredentialTriggerSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CredentialEvents::CREDENTIAL_ISSUED => [
        ['onCredentialIssued', 0],
        ['onCredentialIssuedNotify', -10],
        ['onCredentialIssuedWebhook', -20],
      ],
    ];
  }

  /**
   * Registra la emision de la credencial en logs estructurados.
   *
   * @param \Drupal\jaraba_credentials\Event\CredentialIssuedEvent $event
   *   El evento de emision.
   */
  public function onCredentialIssued(CredentialIssuedEvent $event): void {
    $credential = $event->getCredential();
    $trigger = $event->getTrigger();

    $this->logger->info(
      'Credential issued: @uuid (template: @template) for user @user via @trigger',
      [
        '@uuid' => $credential->uuid(),
        '@template' => $event->getTemplateId(),
        '@user' => $event->getRecipientId(),
        '@trigger' => $trigger,
      ]
    );
  }

  /**
   * Encola notificacion por email al destinatario.
   *
   * P1-04: Notificacion en tiempo real (no solo via cron).
   * Utiliza el mail plugin manager de Drupal para compatibilidad
   * con cualquier backend de email (SMTP, SparkPost, etc.).
   *
   * @param \Drupal\jaraba_credentials\Event\CredentialIssuedEvent $event
   *   El evento de emision.
   */
  public function onCredentialIssuedNotify(CredentialIssuedEvent $event): void {
    $credential = $event->getCredential();
    $recipientId = $event->getRecipientId();

    if (!$recipientId) {
      return;
    }

    try {
      $user = $this->entityTypeManager->getStorage('user')->load($recipientId);
      if (!$user) {
        return;
      }

      $templateId = $credential->get('template_id')->target_id;
      $template = $this->entityTypeManager->getStorage('credential_template')->load($templateId);
      $credentialName = $template ? $template->get('name')->value : 'Credencial';

      // Encolar la notificacion via queue para no bloquear la peticion.
      $queue = \Drupal::queue('jaraba_credentials_notifications');
      $queue->createItem([
        'type' => 'credential_issued',
        'credential_id' => $credential->id(),
        'user_id' => $recipientId,
        'user_email' => $user->getEmail(),
        'credential_name' => $credentialName,
        'trigger' => $event->getTrigger(),
        'timestamp' => \Drupal::time()->getRequestTime(),
      ]);

      $this->logger->debug(
        'Notification queued for credential @uuid to user @user',
        [
          '@uuid' => $credential->uuid(),
          '@user' => $recipientId,
        ]
      );
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Error queueing credential notification: @message',
        ['@message' => $e->getMessage()]
      );
    }
  }

  /**
   * Despacha webhook a sistemas externos configurados para el tenant.
   *
   * P1-04: Permite integracion con plataformas externas (LinkedIn,
   * Europass, sistemas HR) mediante webhooks POST con payload OB3.
   *
   * @param \Drupal\jaraba_credentials\Event\CredentialIssuedEvent $event
   *   El evento de emision.
   */
  public function onCredentialIssuedWebhook(CredentialIssuedEvent $event): void {
    $credential = $event->getCredential();

    // Obtener tenant para verificar si tiene webhooks configurados.
    $tenantId = $credential->get('tenant_id')->value ?? NULL;
    if (!$tenantId) {
      return;
    }

    try {
      $tenant = $this->entityTypeManager->getStorage('group')->load($tenantId);
      if (!$tenant) {
        return;
      }

      // Verificar si el tenant tiene campo de webhook URL configurado.
      if (!$tenant->hasField('field_credential_webhook_url') ||
          $tenant->get('field_credential_webhook_url')->isEmpty()) {
        return;
      }

      $webhookUrl = $tenant->get('field_credential_webhook_url')->value;
      if (empty($webhookUrl)) {
        return;
      }

      // Preparar payload con datos OB3 de la credencial.
      $payload = [
        'event' => 'credential.issued',
        'timestamp' => date('c'),
        'credential' => [
          'id' => $credential->uuid(),
          'type' => $credential->get('template_id')->target_id,
          'recipient_email' => $credential->get('recipient_email')->value,
          'issued_on' => $credential->get('issued_on')->value,
          'verification_url' => $credential->get('verification_url')->value,
        ],
        'trigger' => $event->getTrigger(),
      ];

      // Encolar webhook para no bloquear la peticion HTTP actual.
      $queue = \Drupal::queue('jaraba_credentials_webhooks');
      $queue->createItem([
        'url' => $webhookUrl,
        'payload' => $payload,
        'tenant_id' => $tenantId,
        'credential_id' => $credential->id(),
      ]);

      $this->logger->debug(
        'Webhook queued for credential @uuid to @url',
        [
          '@uuid' => $credential->uuid(),
          '@url' => $webhookUrl,
        ]
      );
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Error queueing credential webhook: @message',
        ['@message' => $e->getMessage()]
      );
    }
  }

}
