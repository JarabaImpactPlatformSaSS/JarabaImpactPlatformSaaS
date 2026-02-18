<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Fraud;

use Drupal\jaraba_predictive\Fraud\FraudRuleInterface;
use Drupal\Core\Database\Connection;

/**
 * Regla para detectar bots de aplicación masiva en el Job Board.
 */
class JobApplicationSpamRule implements FraudRuleInterface {

  public function __construct(
    protected readonly Connection $database,
  ) {}

  public function getName(): string {
    return 'job_board_application_spam';
  }

  public function getWeight(): float {
    return 0.7;
  }

  public function evaluate(mixed $subject, array $context = []): int {
    // El subject es el ID del usuario (candidato).
    $uid = (int) $context['user_id'];
    if (!$uid) return 0;

    // Contamos aplicaciones en la última hora.
    $count = (int) $this->database->select('job_application', 'a')
      ->condition('a.uid', $uid)
      ->condition('a.created', time() - 3600, '>=')
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($count > 20) return 100; // 20 aplicaciones/hora es humano imposible.
    if ($count > 10) return 50;

    return 0;
  }

}
