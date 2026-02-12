<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service\Alerts;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Queue\QueueFactory;
use Psr\Log\LoggerInterface;

/**
 * Dispatcher de notificaciones por multiples canales.
 *
 * Orquesta el envio de notificaciones via email, plataforma
 * y digest agrupados. Delega el envio real al FundingAlertService
 * y encola digests para procesamiento diferido.
 *
 * ARQUITECTURA:
 * - Dispatch multi-canal: email + plataforma.
 * - Digest: agrupacion de alertas por frecuencia (daily, weekly).
 * - Procesamiento asincrono via cola jaraba_funding_alert.
 *
 * RELACIONES:
 * - FundingNotificationDispatcher -> FundingAlertService (envio)
 * - FundingNotificationDispatcher -> QueueFactory (cola asincrona)
 */
class FundingNotificationDispatcher {

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_funding\Service\Alerts\FundingAlertService $alertService
   *   Servicio de alertas de subvenciones.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   Factory de colas para procesamiento asincrono.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected FundingAlertService $alertService,
    protected QueueFactory $queueFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Despacha una alerta por todos los canales configurados.
   *
   * @param \Drupal\Core\Entity\EntityInterface $alert
   *   Entidad FundingAlert a despachar.
   *
   * @return bool
   *   TRUE si al menos un canal tuvo exito.
   */
  public function dispatch(EntityInterface $alert): bool {
    $success = FALSE;

    // Canal email.
    $emailSent = $this->sendEmail($alert);
    if ($emailSent) {
      $success = TRUE;
    }

    // Canal plataforma (notificacion interna).
    $platformSent = $this->sendPlatformNotification($alert);
    if ($platformSent) {
      $success = TRUE;
    }

    if ($success) {
      $this->logger->info('Notificacion despachada para alerta @id.', [
        '@id' => $alert->id(),
      ]);
    }
    else {
      $this->logger->warning('No se pudo despachar notificacion para alerta @id.', [
        '@id' => $alert->id(),
      ]);
    }

    return $success;
  }

  /**
   * Envia una alerta por email.
   *
   * @param \Drupal\Core\Entity\EntityInterface $alert
   *   Entidad FundingAlert a enviar por email.
   *
   * @return bool
   *   TRUE si el envio fue exitoso.
   */
  public function sendEmail(EntityInterface $alert): bool {
    try {
      return $this->alertService->sendAlert($alert);
    }
    catch (\Exception $e) {
      $this->logger->error('Error enviando email para alerta @id: @error', [
        '@id' => $alert->id(),
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Envia una notificacion interna de plataforma.
   *
   * Marca la alerta como disponible en la bandeja de notificaciones
   * del usuario dentro de la plataforma.
   *
   * @param \Drupal\Core\Entity\EntityInterface $alert
   *   Entidad FundingAlert.
   *
   * @return bool
   *   TRUE si la notificacion fue registrada.
   */
  public function sendPlatformNotification(EntityInterface $alert): bool {
    try {
      // La alerta ya existe como entidad, lo que la hace visible
      // en la bandeja de notificaciones del usuario.
      // Actualizar status para que aparezca como notificacion nueva.
      $status = $alert->get('status')->value ?? 'pending';
      if ($status === 'pending') {
        $alert->set('status', 'sent');
        $alert->save();
      }

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error enviando notificacion de plataforma para alerta @id: @error', [
        '@id' => $alert->id(),
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Encola un digest de alertas para procesamiento posterior.
   *
   * @param int $userId
   *   ID del usuario.
   * @param string $frequency
   *   Frecuencia del digest: 'daily' o 'weekly'.
   */
  public function queueDigest(int $userId, string $frequency): void {
    try {
      $queue = $this->queueFactory->get('jaraba_funding_alert');

      $queue->createItem([
        'action' => 'send_digest',
        'user_id' => $userId,
        'frequency' => $frequency,
      ]);

      $this->logger->info('Digest @frequency encolado para usuario @user.', [
        '@frequency' => $frequency,
        '@user' => $userId,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error encolando digest para usuario @user: @error', [
        '@user' => $userId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Construye el contenido de un email digest de alertas.
   *
   * @param int $userId
   *   ID del usuario.
   * @param array $alerts
   *   Array de datos de alertas para incluir en el digest.
   *
   * @return array
   *   Contenido del digest con claves:
   *   - subject: (string) Asunto del email.
   *   - body: (string) Cuerpo del email.
   *   - alert_count: (int) Numero de alertas incluidas.
   */
  public function buildDigestEmail(int $userId, array $alerts): array {
    $count = count($alerts);

    $subject = "Resumen de subvenciones: {$count} alerta(s) nuevas";

    $body = "Estimado usuario,\n\n";
    $body .= "Este es su resumen periodico de alertas de subvenciones.\n\n";

    if (empty($alerts)) {
      $body .= "No hay nuevas alertas desde el ultimo resumen.\n";
    }
    else {
      $body .= "Se han detectado {$count} alerta(s) nuevas:\n\n";

      foreach ($alerts as $index => $alertData) {
        $num = $index + 1;
        $title = $alertData['title'] ?? 'Sin titulo';
        $severity = $alertData['severity'] ?? 'info';
        $message = $alertData['message'] ?? '';
        $alertType = $alertData['alert_type'] ?? '';

        $typeLabel = match ($alertType) {
          'new_match' => 'Nueva coincidencia',
          'deadline_reminder' => 'Recordatorio de plazo',
          'status_change' => 'Cambio de estado',
          default => 'Alerta',
        };

        $severityLabel = match ($severity) {
          'critical' => 'URGENTE',
          'warning' => 'ATENCION',
          default => 'INFO',
        };

        $body .= "{$num}. [{$severityLabel}] {$typeLabel}: {$title}\n";
        if (!empty($message)) {
          $body .= "   {$message}\n";
        }
        $body .= "\n";
      }
    }

    $body .= "---\n";
    $body .= "Puede consultar los detalles en su panel de subvenciones.\n";
    $body .= "Para modificar la frecuencia de estos resumenes, acceda a la configuracion de alertas.\n";

    return [
      'subject' => $subject,
      'body' => $body,
      'alert_count' => $count,
    ];
  }

}
