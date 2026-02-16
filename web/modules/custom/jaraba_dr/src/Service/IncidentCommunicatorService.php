<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de comunicacion multi-canal de incidentes.
 *
 * ESTRUCTURA:
 * Gestiona las notificaciones de incidentes DR a traves de multiples
 * canales: email, Slack, SMS y webhooks. Implementa escalado automatico
 * cuando los incidentes criticos no reciben respuesta dentro del
 * timeout configurado.
 *
 * LOGICA:
 * - Envia notificaciones por los canales configurados (email, slack, webhook).
 * - Encola mensajes para envio asincrono via QueueFactory.
 * - Registra cada comunicacion en el communication_log del incidente (JSON).
 * - Gestiona escalados cuando no hay respuesta en el timeout configurado.
 * - Los incidentes P1 se notifican inmediatamente; P2-P4 van a la cola.
 *
 * RELACIONES:
 * - DrIncident (entidad de incidentes, campo communication_log JSON)
 * - jaraba_dr.settings (canales, timeout de escalado, destinatarios)
 * - QueueFactory (envio asincrono para canales no criticos)
 * - MailManager (canal email)
 * - DrApiController (consumido desde /api/v1/dr/incidents)
 *
 * Spec: Doc 185 s4.3. Plan: FASE 10, Stack Compliance Legal N1.
 */
class IncidentCommunicatorService {

  /**
   * Canales de comunicacion soportados.
   */
  const CHANNEL_EMAIL = 'email';
  const CHANNEL_SLACK = 'slack';
  const CHANNEL_WEBHOOK = 'webhook';

  /**
   * Construye el servicio de comunicacion de incidentes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   Gestor de envio de mail.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   Factoria de colas.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logging.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly MailManagerInterface $mailManager,
    protected readonly QueueFactory $queueFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Envia notificacion de un incidente por todos los canales configurados.
   *
   * Para incidentes P1 criticos, envia inmediatamente por todos los canales.
   * Para P2-P4, encola las notificaciones para envio asincrono.
   *
   * @param int $incidentId
   *   ID de la entidad DrIncident.
   * @param string $message
   *   Mensaje a enviar.
   *
   * @return int
   *   Numero de notificaciones enviadas o encoladas.
   */
  public function notifyIncident(int $incidentId, string $message): int {
    $storage = $this->entityTypeManager->getStorage('dr_incident');
    $incident = $storage->load($incidentId);

    if (!$incident) {
      $this->logger->error('No se puede notificar: incidente @id no encontrado.', [
        '@id' => $incidentId,
      ]);
      return 0;
    }

    $config = $this->configFactory->get('jaraba_dr.settings');
    $channels = $config->get('notification_channels') ?? [self::CHANNEL_EMAIL];
    $severity = $incident->get('severity')->value;
    $title = $incident->get('title')->value;
    $notificationCount = 0;

    // Incidentes P1 criticos: envio inmediato por todos los canales.
    $isUrgent = ($severity === 'p1_critical');

    foreach ($channels as $channel) {
      if ($isUrgent) {
        // Envio sincrono inmediato para P1.
        $sent = $this->sendNotification($incidentId, $title, $message, $channel, $severity);
        if ($sent) {
          $this->addCommunicationLog($incidentId, $message, $channel);
          $notificationCount++;
        }
      }
      else {
        // Encolar para P2-P4.
        $queue = $this->queueFactory->get('jaraba_dr_incident_notification');
        $queue->createItem([
          'incident_id' => $incidentId,
          'title' => $title,
          'message' => $message,
          'channel' => $channel,
          'severity' => $severity,
        ]);
        $this->addCommunicationLog($incidentId, $message, $channel);
        $notificationCount++;
      }
    }

    $this->logger->info('Notificacion de incidente @id enviada a @count canales. Severidad: @severity.', [
      '@id' => $incidentId,
      '@count' => $notificationCount,
      '@severity' => $severity,
    ]);

    return $notificationCount;
  }

  /**
   * Registra una entrada en el communication_log del incidente.
   *
   * Actualiza el campo communication_log (JSON) de la entidad DrIncident
   * anadiendo la nueva entrada de comunicacion.
   *
   * @param int $incidentId
   *   ID de la entidad DrIncident.
   * @param string $message
   *   Mensaje enviado.
   * @param string $channel
   *   Canal utilizado: email, slack, webhook.
   *
   * @return bool
   *   TRUE si se registro correctamente.
   */
  public function addCommunicationLog(int $incidentId, string $message, string $channel): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('dr_incident');
      $incident = $storage->load($incidentId);

      if (!$incident) {
        return FALSE;
      }

      $log = $incident->getCommunicationLogDecoded();
      $log[] = [
        'timestamp' => time(),
        'channel' => $channel,
        'message' => $message,
        'sent_by' => 'system',
      ];

      $incident->set('communication_log', json_encode($log, JSON_THROW_ON_ERROR));
      $incident->save();

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al registrar comunicacion para incidente @id: @error', [
        '@id' => $incidentId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Verifica si hay incidentes que requieren escalado.
   *
   * Busca incidentes activos cuyo tiempo sin respuesta excede el
   * timeout de escalado configurado y envia notificaciones de escalado.
   *
   * @return int
   *   Numero de incidentes escalados.
   */
  public function checkEscalations(): int {
    $config = $this->configFactory->get('jaraba_dr.settings');
    $escalationTimeoutMinutes = $config->get('escalation_timeout_minutes') ?? 30;
    $escalationTimeoutSeconds = $escalationTimeoutMinutes * 60;
    $escalatedCount = 0;

    $storage = $this->entityTypeManager->getStorage('dr_incident');

    // Buscar incidentes activos criticos y mayores.
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', ['investigating', 'identified'], 'IN')
      ->condition('severity', ['p1_critical', 'p2_major'], 'IN');

    $ids = $query->execute();
    if (empty($ids)) {
      return 0;
    }

    $entities = $storage->loadMultiple($ids);
    $now = time();

    foreach ($entities as $entity) {
      $startedAt = (int) $entity->get('started_at')->value;
      if ($startedAt <= 0) {
        $startedAt = (int) $entity->get('created')->value;
      }

      $elapsedSeconds = $now - $startedAt;

      // Verificar si ha excedido el timeout de escalado.
      if ($elapsedSeconds < $escalationTimeoutSeconds) {
        continue;
      }

      // Verificar si ya fue escalado recientemente (evitar spam).
      $log = $entity->getCommunicationLogDecoded();
      $lastEscalation = $this->getLastEscalationTimestamp($log);
      if ($lastEscalation > 0 && ($now - $lastEscalation) < $escalationTimeoutSeconds) {
        continue;
      }

      // Enviar notificacion de escalado.
      $incidentId = (int) $entity->id();
      $title = $entity->get('title')->value;
      $severity = $entity->get('severity')->value;
      $elapsedMinutes = (int) round($elapsedSeconds / 60);

      $escalationMessage = (string) new TranslatableMarkup(
        'ESCALADO: Incidente "@title" (severidad: @severity) sin resolver tras @minutes minutos.',
        [
          '@title' => $title,
          '@severity' => $severity,
          '@minutes' => $elapsedMinutes,
        ]
      );

      // Enviar por email directamente (escalados siempre son urgentes).
      $this->sendNotification($incidentId, $title, $escalationMessage, self::CHANNEL_EMAIL, $severity);
      $this->addCommunicationLog($incidentId, $escalationMessage, 'escalation');

      $this->logger->warning('Incidente @id escalado tras @minutes minutos.', [
        '@id' => $incidentId,
        '@minutes' => $elapsedMinutes,
      ]);

      $escalatedCount++;
    }

    if ($escalatedCount > 0) {
      $this->logger->info('Verificacion de escalados completada: @count incidentes escalados.', [
        '@count' => $escalatedCount,
      ]);
    }

    return $escalatedCount;
  }

  /**
   * Obtiene el historial de comunicaciones de un incidente.
   *
   * @param int $incidentId
   *   ID de la entidad DrIncident.
   *
   * @return array<int, array<string, mixed>>
   *   Log de comunicaciones del incidente.
   */
  public function getIncidentCommunicationHistory(int $incidentId): array {
    $storage = $this->entityTypeManager->getStorage('dr_incident');
    $incident = $storage->load($incidentId);

    if (!$incident) {
      return [];
    }

    return $incident->getCommunicationLogDecoded();
  }

  /**
   * Envia una notificacion por un canal especifico.
   *
   * @param int $incidentId
   *   ID del incidente.
   * @param string $title
   *   Titulo del incidente.
   * @param string $message
   *   Mensaje a enviar.
   * @param string $channel
   *   Canal de comunicacion.
   * @param string $severity
   *   Severidad del incidente.
   *
   * @return bool
   *   TRUE si el envio fue exitoso.
   */
  protected function sendNotification(int $incidentId, string $title, string $message, string $channel, string $severity): bool {
    $config = $this->configFactory->get('jaraba_dr.settings');

    switch ($channel) {
      case self::CHANNEL_EMAIL:
        return $this->sendEmailNotification($incidentId, $title, $message, $severity, $config);

      case self::CHANNEL_SLACK:
        return $this->sendSlackNotification($incidentId, $title, $message, $severity, $config);

      case self::CHANNEL_WEBHOOK:
        return $this->sendWebhookNotification($incidentId, $title, $message, $severity, $config);

      default:
        $this->logger->warning('Canal de notificacion no soportado: @channel', ['@channel' => $channel]);
        return FALSE;
    }
  }

  /**
   * Envia notificacion por email.
   */
  protected function sendEmailNotification(int $incidentId, string $title, string $message, string $severity, object $config): bool {
    $recipients = $config->get('notification_email_recipients') ?? '';
    if (empty($recipients)) {
      $this->logger->warning('Email de notificacion DR no configurado.');
      return FALSE;
    }

    try {
      $result = $this->mailManager->mail(
        'jaraba_dr',
        'incident_notification',
        $recipients,
        'es',
        [
          'incident_id' => $incidentId,
          'title' => $title,
          'message' => $message,
          'severity' => $severity,
          'subject' => (string) new TranslatableMarkup(
            '[@severity] Incidente DR: @title',
            ['@severity' => strtoupper($severity), '@title' => $title]
          ),
        ]
      );

      return !empty($result['result']);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al enviar email de notificacion DR: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Envia notificacion via Slack webhook.
   */
  protected function sendSlackNotification(int $incidentId, string $title, string $message, string $severity, object $config): bool {
    $webhookUrl = $config->get('slack_webhook_url') ?? '';
    if (empty($webhookUrl)) {
      $this->logger->warning('Slack webhook DR no configurado.');
      return FALSE;
    }

    $severityEmoji = match ($severity) {
      'p1_critical' => ':rotating_light:',
      'p2_major' => ':warning:',
      'p3_minor' => ':information_source:',
      default => ':memo:',
    };

    $payload = json_encode([
      'text' => "$severityEmoji *Incidente DR #$incidentId: $title*\n$message",
      'username' => 'Jaraba DR Bot',
      'icon_emoji' => ':shield:',
    ]);

    if ($payload === FALSE) {
      return FALSE;
    }

    try {
      $ch = curl_init($webhookUrl);
      if ($ch === FALSE) {
        return FALSE;
      }

      curl_setopt_array($ch, [
        CURLOPT_POST => TRUE,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
      ]);

      curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      return $httpCode >= 200 && $httpCode < 300;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al enviar notificacion Slack DR: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Envia notificacion via webhook generico.
   */
  protected function sendWebhookNotification(int $incidentId, string $title, string $message, string $severity, object $config): bool {
    $webhookUrl = $config->get('notification_webhook_url') ?? '';
    if (empty($webhookUrl)) {
      return FALSE;
    }

    $payload = json_encode([
      'event' => 'dr_incident',
      'incident_id' => $incidentId,
      'title' => $title,
      'message' => $message,
      'severity' => $severity,
      'timestamp' => time(),
    ]);

    if ($payload === FALSE) {
      return FALSE;
    }

    try {
      $ch = curl_init($webhookUrl);
      if ($ch === FALSE) {
        return FALSE;
      }

      curl_setopt_array($ch, [
        CURLOPT_POST => TRUE,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
      ]);

      curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      return $httpCode >= 200 && $httpCode < 300;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al enviar webhook DR: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene el timestamp del ultimo escalado de un log de comunicaciones.
   *
   * @param array $log
   *   Log de comunicaciones decodificado.
   *
   * @return int
   *   Timestamp del ultimo escalado, o 0 si no hay.
   */
  protected function getLastEscalationTimestamp(array $log): int {
    $lastTimestamp = 0;
    foreach ($log as $entry) {
      if (isset($entry['channel']) && $entry['channel'] === 'escalation') {
        $timestamp = (int) ($entry['timestamp'] ?? 0);
        if ($timestamp > $lastTimestamp) {
          $lastTimestamp = $timestamp;
        }
      }
    }
    return $lastTimestamp;
  }

}
