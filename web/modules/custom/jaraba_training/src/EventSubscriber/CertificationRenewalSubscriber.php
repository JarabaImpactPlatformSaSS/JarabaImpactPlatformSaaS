<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Subscriber para renovación de certificaciones del Método Jaraba.
 *
 * CERT-05: 30 días antes de expiración, envía email + notificación dashboard.
 * Ejecutado via cron (hook_cron en .module).
 */
class CertificationRenewalSubscriber {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MailManagerInterface $mailManager,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Procesa certificaciones próximas a expirar.
   *
   * Llamado desde hook_cron. Busca certificaciones que expiran en 30 días
   * y envía email de recordatorio al titular.
   *
   * @return int
   *   Número de recordatorios enviados.
   */
  public function processExpiringCertifications(): int {
    $sent = 0;

    try {
      $storage = $this->entityTypeManager->getStorage('user_certification');
      $thirtyDays = date('Y-m-d', strtotime('+30 days'));
      $twentyNineDays = date('Y-m-d', strtotime('+29 days'));

      // Certificaciones que expiran exactamente en 30 días (±1 día).
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('certification_status', 'completed')
        ->condition('expiration_date', $twentyNineDays, '>=')
        ->condition('expiration_date', $thirtyDays, '<=')
        ->execute();

      $userStorage = $this->entityTypeManager->getStorage('user');

      foreach ($storage->loadMultiple($ids) as $cert) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $cert */
        $userId = $cert->get('user_id')->target_id;
        /** @var \Drupal\user\Entity\User|null $user */
        $user = $userId !== '' ? $userStorage->load($userId) : NULL;
        if ($user === NULL) {
          continue;
        }

        $email = $user->getEmail();
        if ($email === NULL || $email === '') {
          continue;
        }

        $params = [
          'subject' => 'Tu certificación Método Jaraba expira pronto',
          'body' => [
            'Hola ' . $user->getDisplayName() . ',',
            '',
            'Tu certificación del Método Jaraba expira el ' . ($cert->get('expiration_date')->value ?? 'próximamente') . '.',
            'Para renovarla, añade 2 nuevas evidencias a tu portfolio y solicita la renovación desde tu panel.',
            '',
            'Equipo Plataforma de Ecosistemas Digitales',
          ],
        ];

        try {
          $this->mailManager->mail('jaraba_training', 'certification_renewal', $email, 'es', $params);
          $sent++;
        }
        catch (\Throwable $e) {
          $this->logger->warning('Renewal email failed for user @uid: @e', [
            '@uid' => $userId,
            '@e' => $e->getMessage(),
          ]);
        }
      }

      if ($sent > 0) {
        $this->logger->info('Sent @count certification renewal reminders.', ['@count' => $sent]);
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('CertificationRenewal error: @e', ['@e' => $e->getMessage()]);
    }

    return $sent;
  }

}
