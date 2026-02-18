<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Fraud;

use Drupal\jaraba_predictive\Fraud\FraudRuleInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Regla que detecta emisiones de certificados sospechosamente rápidas.
 */
class SpeedOfCertificationRule implements FraudRuleInterface {

  public function getName(): string {
    return 'lms_speed_of_certification';
  }

  public function getWeight(): float {
    return 0.6;
  }

  public function evaluate(mixed $subject, array $context = []): int {
    // El subject sería la entidad 'lms_enrollment' o similar.
    if (!$subject instanceof ContentEntityInterface || $subject->getEntityTypeId() !== 'lms_enrollment') {
      return 0;
    }

    $created = (int) $subject->get('created')->value;
    $completed = (int) ($context['completed_at'] ?? time());
    
    $timeTakenMinutes = ($completed - $created) / 60;

    // Si un curso de 10 horas se completa en 2 minutos...
    if ($timeTakenMinutes < 5) {
      return 100;
    }

    return 0;
  }

}
