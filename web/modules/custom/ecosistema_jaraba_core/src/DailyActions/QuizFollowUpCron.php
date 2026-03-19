<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DailyActions;

/**
 * Cron job: Email de seguimiento a leads del quiz que no se registraron.
 *
 * Se ejecuta en ecosistema_jaraba_core_cron().
 * Busca QuizResult con email != NULL, converted = FALSE, created > 24h y < 7d.
 * Envía un email recordatorio con la recomendación personalizada.
 *
 * Fase B: drip sequence (24h, 72h, 7d) con contenido progresivo.
 */
class QuizFollowUpCron {

  /**
   * Procesar leads del quiz no convertidos (llamar desde cron).
   */
  public static function processUnconvertedLeads(): void {
    if (!\Drupal::hasService('entity_type.manager')) {
      return;
    }

    try {
      $storage = \Drupal::entityTypeManager()->getStorage('quiz_result');
      $now = \Drupal::time()->getRequestTime();
      $oneDayAgo = $now - 86400;
      $sevenDaysAgo = $now - (86400 * 7);

      // Buscar leads con email que no se convirtieron hace 24h-7d.
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('email', NULL, 'IS NOT NULL')
        ->condition('converted', FALSE)
        ->condition('created', $sevenDaysAgo, '>=')
        ->condition('created', $oneDayAgo, '<=')
        ->range(0, 50)
        ->execute();

      if (empty($ids)) {
        return;
      }

      $results = $storage->loadMultiple($ids);
      $mailManager = \Drupal::service('plugin.manager.mail');

      foreach ($results as $result) {
        $email = $result->get('email')->value;
        if (empty($email)) {
          continue;
        }

        $vertical = $result->getRecommendedVertical();
        $created = (int) $result->get('created')->value;
        $hoursSince = ($now - $created) / 3600;

        // Determinar fase del drip (24h, 72h, 7d).
        $dripPhase = 'reminder_24h';
        if ($hoursSince > 120) {
          $dripPhase = 'reminder_7d';
        }
        elseif ($hoursSince > 48) {
          $dripPhase = 'reminder_72h';
        }

        // Evitar duplicados: verificar si ya se envió este fase.
        $answers = $result->getAnswers();
        $sentPhases = $answers['_drip_sent'] ?? [];
        if (in_array($dripPhase, $sentPhases, TRUE)) {
          continue;
        }

        // Enviar email.
        $mailManager->mail(
          'ecosistema_jaraba_core',
          'quiz_followup',
          $email,
          'es',
          [
            'quiz_result' => $result,
            'vertical' => $vertical,
            'drip_phase' => $dripPhase,
          ],
        );

        // Marcar fase como enviada.
        $sentPhases[] = $dripPhase;
        $answers['_drip_sent'] = $sentPhases;
        $result->set('answers', $answers);
        $result->save();

        \Drupal::logger('ecosistema_jaraba_core')->info(
          'Quiz follow-up @phase sent to @email for vertical @v.',
          ['@phase' => $dripPhase, '@email' => $email, '@v' => $vertical]
        );
      }
    }
    catch (\Throwable $e) {
      \Drupal::logger('ecosistema_jaraba_core')->warning(
        'Quiz follow-up cron error: @e',
        ['@e' => $e->getMessage()]
      );
    }
  }

}
