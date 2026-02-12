<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service\Alerts;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de alertas de subvenciones.
 *
 * Gestiona el ciclo de vida de alertas: creacion, envio, lectura
 * y recordatorios de plazos. Las alertas se asocian a un tenant,
 * usuario, suscripcion y convocatoria.
 *
 * ARQUITECTURA:
 * - Entidades FundingAlert con ciclo: pending -> sent -> read / dismissed.
 * - Tipos de alerta: new_match, deadline_reminder, status_change.
 * - Severidad: info, warning, critical.
 * - Recordatorios automaticos a 7, 3 y 1 dias del plazo.
 * - Envio via MailManagerInterface.
 *
 * RELACIONES:
 * - FundingAlertService -> FundingAlert entity (gestiona)
 * - FundingAlertService -> FundingCall entity (referencias)
 * - FundingAlertService -> FundingSubscription entity (referencias)
 * - FundingAlertService -> MailManagerInterface (envio email)
 */
class FundingAlertService {

  /**
   * Dias de antelacion para recordatorios de plazo.
   */
  protected const REMINDER_DAYS = [7, 3, 1];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   Gestor de envio de emails.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Crea una alerta de subvenciones.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $userId
   *   ID del usuario destinatario.
   * @param int $subscriptionId
   *   ID de la suscripcion relacionada.
   * @param int $callId
   *   ID de la convocatoria relacionada.
   * @param string $alertType
   *   Tipo de alerta: 'new_match', 'deadline_reminder', 'status_change'.
   * @param string $title
   *   Titulo de la alerta.
   * @param string $message
   *   Mensaje/cuerpo de la alerta.
   * @param string $severity
   *   Severidad: 'info', 'warning', 'critical'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Entidad FundingAlert creada.
   */
  public function createAlert(
    int $tenantId,
    int $userId,
    int $subscriptionId,
    int $callId,
    string $alertType,
    string $title,
    string $message,
    string $severity = 'info',
  ): EntityInterface {
    $storage = $this->entityTypeManager->getStorage('funding_alert');

    $alert = $storage->create([
      'tenant_id' => $tenantId,
      'user_id' => $userId,
      'subscription_id' => $subscriptionId,
      'call_id' => $callId,
      'alert_type' => $alertType,
      'title' => $title,
      'message' => $message,
      'severity' => $severity,
      'status' => 'pending',
      'created' => \Drupal::time()->getRequestTime(),
    ]);
    $alert->save();

    $this->logger->info('Alerta de subvencion creada: @type para tenant @tenant, usuario @user (@severity).', [
      '@type' => $alertType,
      '@tenant' => $tenantId,
      '@user' => $userId,
      '@severity' => $severity,
    ]);

    return $alert;
  }

  /**
   * Envia todas las alertas pendientes.
   *
   * @return int
   *   Numero de alertas enviadas.
   */
  public function sendPendingAlerts(): int {
    $sentCount = 0;

    try {
      $alertStorage = $this->entityTypeManager->getStorage('funding_alert');
      $pendingIds = $alertStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'pending')
        ->sort('created', 'ASC')
        ->execute();

      if (empty($pendingIds)) {
        return 0;
      }

      $alerts = $alertStorage->loadMultiple($pendingIds);

      foreach ($alerts as $alert) {
        $sent = $this->sendAlert($alert);
        if ($sent) {
          $sentCount++;
        }
      }

      $this->logger->info('Alertas de subvenciones enviadas: @count de @total.', [
        '@count' => $sentCount,
        '@total' => count($pendingIds),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando alertas pendientes: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $sentCount;
  }

  /**
   * Envia una alerta individual via email.
   *
   * @param \Drupal\Core\Entity\EntityInterface $alert
   *   Entidad FundingAlert a enviar.
   *
   * @return bool
   *   TRUE si el envio fue exitoso.
   */
  public function sendAlert(EntityInterface $alert): bool {
    try {
      $userId = (int) $alert->get('user_id')->value;
      $user = $this->entityTypeManager->getStorage('user')->load($userId);

      if (!$user || !$user->getEmail()) {
        $this->logger->warning('No se encontro email para usuario @id.', ['@id' => $userId]);
        return FALSE;
      }

      $title = $alert->get('title')->value ?? '';
      $message = $alert->get('message')->value ?? '';
      $alertType = $alert->get('alert_type')->value ?? 'funding_alert';

      $mailKey = match ($alertType) {
        'deadline_reminder' => 'funding_deadline',
        'new_match', 'status_change' => 'funding_alert',
        default => 'funding_alert',
      };

      $params = [
        'subject' => $title,
        'body' => $message,
      ];

      $result = $this->mailManager->mail(
        'jaraba_funding',
        $mailKey,
        $user->getEmail(),
        'es',
        $params,
        NULL,
        TRUE
      );

      if ($result['result'] ?? FALSE) {
        $alert->set('status', 'sent');
        $alert->set('sent_at', \Drupal::time()->getRequestTime());
        $alert->save();
        return TRUE;
      }

      $this->logger->warning('Fallo el envio de email para alerta @id.', ['@id' => $alert->id()]);
      return FALSE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error enviando alerta @id: @error', [
        '@id' => $alert->id(),
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene alertas no leidas para un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   * @param int $limit
   *   Numero maximo de alertas a retornar.
   *
   * @return array
   *   Array de datos de alertas.
   */
  public function getUnreadAlerts(int $userId, int $limit = 20): array {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_alert');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('status', ['pending', 'sent'], 'IN')
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $alerts = $storage->loadMultiple($ids);
      $results = [];

      foreach ($alerts as $alert) {
        $results[] = [
          'id' => (int) $alert->id(),
          'tenant_id' => (int) $alert->get('tenant_id')->value,
          'call_id' => (int) $alert->get('call_id')->value,
          'alert_type' => $alert->get('alert_type')->value,
          'title' => $alert->get('title')->value,
          'message' => $alert->get('message')->value,
          'severity' => $alert->get('severity')->value,
          'status' => $alert->get('status')->value,
          'created' => (int) $alert->get('created')->value,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo alertas no leidas para usuario @id: @error', [
        '@id' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Marca una alerta como leida.
   *
   * @param int $alertId
   *   ID de la alerta.
   */
  public function markAsRead(int $alertId): void {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_alert');
      $alert = $storage->load($alertId);

      if ($alert) {
        $alert->set('status', 'read');
        $alert->save();
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error marcando alerta @id como leida: @error', [
        '@id' => $alertId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Marca una alerta como descartada.
   *
   * @param int $alertId
   *   ID de la alerta.
   */
  public function markAsDismissed(int $alertId): void {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_alert');
      $alert = $storage->load($alertId);

      if ($alert) {
        $alert->set('status', 'dismissed');
        $alert->save();
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error marcando alerta @id como descartada: @error', [
        '@id' => $alertId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Crea recordatorios para convocatorias con plazo proximo.
   *
   * Genera alertas de tipo 'deadline_reminder' para convocatorias
   * cuyo plazo vence en 7, 3 o 1 dias.
   *
   * @return int
   *   Numero de recordatorios creados.
   */
  public function createDeadlineReminders(): int {
    $remindersCreated = 0;

    try {
      $callStorage = $this->entityTypeManager->getStorage('funding_call');
      $matchStorage = $this->entityTypeManager->getStorage('funding_match');
      $alertStorage = $this->entityTypeManager->getStorage('funding_alert');

      foreach (self::REMINDER_DAYS as $days) {
        $targetDate = date('Y-m-d', strtotime("+{$days} days"));

        // Buscar convocatorias con plazo en la fecha objetivo.
        $callIds = $callStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', 'abierta')
          ->condition('deadline', $targetDate)
          ->execute();

        if (empty($callIds)) {
          continue;
        }

        foreach ($callIds as $callId) {
          $call = $callStorage->load($callId);
          if (!$call) {
            continue;
          }

          // Buscar matches para esta convocatoria.
          $matchIds = $matchStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('call_id', $callId)
            ->execute();

          if (empty($matchIds)) {
            continue;
          }

          $matchEntities = $matchStorage->loadMultiple($matchIds);

          foreach ($matchEntities as $match) {
            $subscriptionId = (int) $match->get('subscription_id')->value;
            $subscription = $this->entityTypeManager->getStorage('funding_subscription')->load($subscriptionId);

            if (!$subscription) {
              continue;
            }

            $tenantId = (int) ($subscription->get('tenant_id')->value ?? 0);
            $userId = (int) ($subscription->get('user_id')->value ?? 0);

            if ($tenantId === 0 || $userId === 0) {
              continue;
            }

            // Verificar que no exista ya un recordatorio para esta combinacion.
            $existingIds = $alertStorage->getQuery()
              ->accessCheck(FALSE)
              ->condition('call_id', $callId)
              ->condition('user_id', $userId)
              ->condition('alert_type', 'deadline_reminder')
              ->condition('title', "Plazo en {$days} dias", 'CONTAINS')
              ->range(0, 1)
              ->execute();

            if (!empty($existingIds)) {
              continue;
            }

            $title = $call->get('title')->value ?? 'Convocatoria';
            $severity = match (TRUE) {
              $days <= 1 => 'critical',
              $days <= 3 => 'warning',
              default => 'info',
            };

            $this->createAlert(
              $tenantId,
              $userId,
              $subscriptionId,
              (int) $callId,
              'deadline_reminder',
              "Plazo en {$days} dias: {$title}",
              "El plazo de solicitud para '{$title}' vence en {$days} dias (fecha limite: {$targetDate}).",
              $severity
            );

            $remindersCreated++;
          }
        }
      }

      if ($remindersCreated > 0) {
        $this->logger->info('Creados @count recordatorios de plazos de subvenciones.', [
          '@count' => $remindersCreated,
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando recordatorios de plazos: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $remindersCreated;
  }

  /**
   * Obtiene estadisticas de alertas para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Estadisticas con claves:
   *   - total: (int) Total de alertas.
   *   - unread: (int) Alertas no leidas.
   *   - sent_today: (int) Alertas enviadas hoy.
   */
  public function getAlertStats(int $tenantId): array {
    $stats = ['total' => 0, 'unread' => 0, 'sent_today' => 0];

    try {
      $storage = $this->entityTypeManager->getStorage('funding_alert');

      // Total de alertas.
      $stats['total'] = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->count()
        ->execute();

      // Alertas no leidas.
      $stats['unread'] = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', ['pending', 'sent'], 'IN')
        ->count()
        ->execute();

      // Alertas enviadas hoy.
      $todayStart = strtotime('today midnight');
      $stats['sent_today'] = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'sent')
        ->condition('sent_at', $todayStart, '>=')
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estadisticas de alertas para tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $stats;
  }

}
