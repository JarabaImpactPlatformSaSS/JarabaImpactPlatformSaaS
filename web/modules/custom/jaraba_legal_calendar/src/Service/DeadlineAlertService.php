<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Alertas de plazos proximos via email.
 *
 * Comprueba plazos pendientes cuya fecha de vencimiento esta dentro del
 * rango de alerta (alert_days_before) y envia notificacion al responsable.
 */
class DeadlineAlertService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Comprueba plazos proximos y envia alertas.
   *
   * @return int
   *   Numero de alertas enviadas.
   */
  public function checkUpcomingDeadlines(): int {
    $storage = $this->entityTypeManager->getStorage('legal_deadline');
    $now = new \DateTime();
    $maxDate = (new \DateTime())->modify('+30 days');

    $ids = $storage->getQuery()
      ->condition('status', ['pending', 'in_progress'], 'IN')
      ->condition('due_date', $now->format('Y-m-d\TH:i:s'), '>=')
      ->condition('due_date', $maxDate->format('Y-m-d\TH:i:s'), '<=')
      ->accessCheck(FALSE)
      ->execute();

    if (empty($ids)) {
      return 0;
    }

    $count = 0;
    $deadlines = $storage->loadMultiple($ids);
    foreach ($deadlines as $deadline) {
      $dueDate = new \DateTime($deadline->get('due_date')->value);
      $alertDays = (int) $deadline->get('alert_days_before')->value;
      $alertDate = (clone $dueDate)->modify("-{$alertDays} days");

      if ($now >= $alertDate && $now <= $dueDate) {
        $this->sendAlert($deadline);
        $count++;
      }
    }

    $this->logger->info('@count alertas de plazos enviadas.', ['@count' => $count]);
    return $count;
  }

  /**
   * Envia alerta de plazo al responsable asignado.
   *
   * @param \Drupal\Core\Entity\EntityInterface $deadline
   *   Entidad LegalDeadline.
   */
  public function sendAlert($deadline): void {
    $assignee = $deadline->get('assigned_to')->entity;
    if (!$assignee) {
      return;
    }

    $email = $assignee->getEmail();
    if (empty($email)) {
      return;
    }

    $params = [
      'deadline_title' => $deadline->label(),
      'due_date' => $deadline->get('due_date')->value,
      'case_id' => $deadline->get('case_id')->target_id,
    ];

    $this->mailManager->mail(
      'jaraba_legal_calendar',
      'deadline_alert',
      $email,
      $assignee->getPreferredLangcode(),
      $params
    );
  }

}
