<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Captura y procesa correcciones de profesionales juridicos.
 *
 * LEGAL-COHERENCE-FEEDBACK-001: Professional corrections feed back
 * into JLB benchmark, KB patterns, and quality metrics.
 *
 * Flujo:
 * 1. Abogado ve respuesta del Copilot Legal.
 * 2. Click "Corregir respuesta" → formulario estructurado.
 * 3. Correccion guardada con estado 'pending_review'.
 * 4. Queue de revision (admin) para validar.
 * 5. Correcciones validadas se incorporan a:
 *    a) Benchmark JLB como nuevos test cases.
 *    b) Knowledge Base si aplica (nuevos patrones).
 *    c) Metricas de calidad (correcciones/100 consultas).
 *
 * EU AI Act Art. 9(9): "Logging shall include... quality feedback."
 *
 * NOTA: La entidad legal_feedback se creara en una fase posterior.
 * En v1, almacenamos en State API con estructura minimal.
 */
final class LegalFeedbackService {

  /**
   * Tipos de correccion.
   */
  public const TYPE_NORM_INCORRECT = 'norm_incorrect';
  public const TYPE_HIERARCHY_ERROR = 'hierarchy_error';
  public const TYPE_COMPETENCE_ERROR = 'competence_error';
  public const TYPE_VIGENCIA_ERROR = 'vigencia_error';
  public const TYPE_CITATION_ERROR = 'citation_error';
  public const TYPE_INTERPRETATION_ERROR = 'interpretation_error';
  public const TYPE_OTHER = 'other';

  /**
   * Estados de revision.
   */
  public const STATUS_PENDING = 'pending_review';
  public const STATUS_VALIDATED = 'validated';
  public const STATUS_REJECTED = 'rejected';
  public const STATUS_INCORPORATED = 'incorporated';

  public function __construct(
    protected readonly ?EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Registra una correccion profesional.
   *
   * @param array $feedback
   *   Datos de la correccion:
   *   - response_id: ID de la respuesta del copilot.
   *   - correction_type: Tipo de error (TYPE_*).
   *   - incorrect_text: Fragmento incorrecto.
   *   - correct_text: Texto correcto segun el profesional.
   *   - norm_reference: Norma que soporta la correccion.
   *   - explanation: Explicacion del error.
   *   - user_id: ID del profesional que corrige.
   *   - tenant_id: Tenant del profesional.
   *
   * @return string
   *   ID del feedback creado.
   */
  public function submitCorrection(array $feedback): string {
    $feedbackId = 'lcfb_' . bin2hex(random_bytes(8));

    // Almacenar en State API (v1, hasta tener entidad legal_feedback).
    $existingFeedback = \Drupal::state()->get('legal_coherence.feedback', []);
    $existingFeedback[$feedbackId] = [
      'id' => $feedbackId,
      'response_id' => $feedback['response_id'] ?? '',
      'correction_type' => $feedback['correction_type'] ?? self::TYPE_OTHER,
      'incorrect_text' => $feedback['incorrect_text'] ?? '',
      'correct_text' => $feedback['correct_text'] ?? '',
      'norm_reference' => $feedback['norm_reference'] ?? '',
      'explanation' => $feedback['explanation'] ?? '',
      'user_id' => $feedback['user_id'] ?? 0,
      'tenant_id' => $feedback['tenant_id'] ?? '',
      'status' => self::STATUS_PENDING,
      'created' => time(),
    ];
    \Drupal::state()->set('legal_coherence.feedback', $existingFeedback);

    $this->logger->info('Legal feedback submitted: @type by user @user (id: @id)', [
      '@type' => $feedback['correction_type'] ?? 'unknown',
      '@user' => $feedback['user_id'] ?? 'anonymous',
      '@id' => $feedbackId,
    ]);

    return $feedbackId;
  }

  /**
   * Procesa una correccion validada por un administrador.
   *
   * Genera nuevo test case para JLB y actualiza metricas.
   *
   * @param string $feedbackId
   *   ID del feedback.
   *
   * @return bool
   *   TRUE si se proceso correctamente.
   */
  public function processValidatedCorrection(string $feedbackId): bool {
    $allFeedback = \Drupal::state()->get('legal_coherence.feedback', []);

    if (!isset($allFeedback[$feedbackId])) {
      return FALSE;
    }

    $feedback = $allFeedback[$feedbackId];

    if ($feedback['status'] !== self::STATUS_VALIDATED) {
      return FALSE;
    }

    // Marcar como incorporado.
    $allFeedback[$feedbackId]['status'] = self::STATUS_INCORPORATED;
    $allFeedback[$feedbackId]['incorporated_at'] = time();
    \Drupal::state()->set('legal_coherence.feedback', $allFeedback);

    $this->logger->info('Legal feedback incorporated: @id (@type)', [
      '@id' => $feedbackId,
      '@type' => $feedback['correction_type'],
    ]);

    return TRUE;
  }

  /**
   * Valida una correccion (marca como aprobada por admin).
   *
   * @param string $feedbackId
   *   ID del feedback.
   *
   * @return bool
   *   TRUE si se valido correctamente.
   */
  public function validateCorrection(string $feedbackId): bool {
    $allFeedback = \Drupal::state()->get('legal_coherence.feedback', []);

    if (!isset($allFeedback[$feedbackId])) {
      return FALSE;
    }

    $allFeedback[$feedbackId]['status'] = self::STATUS_VALIDATED;
    $allFeedback[$feedbackId]['validated_at'] = time();
    \Drupal::state()->set('legal_coherence.feedback', $allFeedback);

    return TRUE;
  }

  /**
   * Obtiene metricas de feedback.
   *
   * @return array{total: int, pending: int, validated: int, incorporated: int, by_type: array}
   */
  public function getMetrics(): array {
    $allFeedback = \Drupal::state()->get('legal_coherence.feedback', []);

    $metrics = [
      'total' => count($allFeedback),
      'pending' => 0,
      'validated' => 0,
      'incorporated' => 0,
      'rejected' => 0,
      'by_type' => [],
    ];

    foreach ($allFeedback as $fb) {
      $status = $fb['status'] ?? self::STATUS_PENDING;
      match ($status) {
        self::STATUS_PENDING => $metrics['pending']++,
        self::STATUS_VALIDATED => $metrics['validated']++,
        self::STATUS_INCORPORATED => $metrics['incorporated']++,
        self::STATUS_REJECTED => $metrics['rejected']++,
        default => NULL,
      };

      $type = $fb['correction_type'] ?? self::TYPE_OTHER;
      $metrics['by_type'][$type] = ($metrics['by_type'][$type] ?? 0) + 1;
    }

    return $metrics;
  }

}
