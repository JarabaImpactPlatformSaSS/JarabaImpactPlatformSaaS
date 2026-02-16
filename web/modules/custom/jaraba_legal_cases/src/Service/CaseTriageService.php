<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de triaje IA para consultas juridicas.
 *
 * ESTRUCTURA:
 * Servicio que invoca al proveedor de IA para analizar una consulta
 * juridica y generar un resultado de triaje con score de urgencia,
 * tipo de caso sugerido, prioridad y resumen.
 *
 * LOGICA:
 * Recibe una entidad ClientInquiry, construye el prompt para el
 * modelo de IA, parsea la respuesta y crea una entidad InquiryTriage.
 * El proveedor de IA se inyectara cuando este disponible en el
 * ecosistema; por ahora genera un triaje basico basado en reglas.
 */
class CaseTriageService {

  /**
   * Construye una nueva instancia de CaseTriageService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Ejecuta el triaje IA de una consulta juridica.
   *
   * @param int $inquiry_id
   *   ID de la consulta a triar.
   *
   * @return array
   *   Resultado con success, triage_id, urgency_score, etc.
   */
  public function triageInquiry(int $inquiry_id): array {
    try {
      $inquiry_storage = $this->entityTypeManager->getStorage('client_inquiry');
      $inquiry = $inquiry_storage->load($inquiry_id);

      if (!$inquiry) {
        return [
          'success' => FALSE,
          'error' => sprintf('Consulta con ID %d no encontrada.', $inquiry_id),
        ];
      }

      $description = $inquiry->get('description')->value ?? '';
      $subject = $inquiry->get('subject')->value ?? '';
      $case_type_requested = $inquiry->get('case_type_requested')->value ?? '';

      // Triaje basado en reglas (placeholder para IA futura).
      $urgency = $this->calculateUrgency($description, $subject);
      $suggested_type = !empty($case_type_requested) ? $case_type_requested : 'civil';
      $suggested_priority = $urgency >= 70 ? 'critical' : ($urgency >= 50 ? 'high' : ($urgency >= 30 ? 'medium' : 'low'));

      $triage_storage = $this->entityTypeManager->getStorage('inquiry_triage');
      $triage = $triage_storage->create([
        'inquiry_id' => $inquiry_id,
        'urgency_score' => $urgency,
        'suggested_case_type' => $suggested_type,
        'suggested_priority' => $suggested_priority,
        'ai_summary' => sprintf('Consulta sobre: %s. Urgencia estimada: %d/100.', $subject, $urgency),
        'confidence_score' => 0.7,
        'triage_model' => 'rules-v1',
        'uid' => \Drupal::currentUser()->id(),
      ]);
      $triage->save();

      // Actualizar estado de la consulta a 'triaged'.
      $inquiry->set('status', 'triaged');
      $inquiry->save();

      $this->logger->info('Triage: Consulta @id triada con urgencia @score', [
        '@id' => $inquiry_id,
        '@score' => $urgency,
      ]);

      return [
        'success' => TRUE,
        'triage_id' => (int) $triage->id(),
        'urgency_score' => $urgency,
        'suggested_case_type' => $suggested_type,
        'suggested_priority' => $suggested_priority,
        'confidence_score' => 0.7,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Triage: Error triando consulta @id: @msg', [
        '@id' => $inquiry_id,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => 'Error interno al realizar el triaje.',
      ];
    }
  }

  /**
   * Calcula la urgencia basada en reglas simples.
   *
   * @param string $description
   *   Descripcion de la consulta.
   * @param string $subject
   *   Asunto de la consulta.
   *
   * @return int
   *   Score de urgencia 0-100.
   */
  protected function calculateUrgency(string $description, string $subject): int {
    $text = mb_strtolower($description . ' ' . $subject);
    $score = 30;

    $urgent_keywords = ['urgente', 'plazo', 'embargo', 'desahucio', 'detencion', 'cautelar', 'inmediato'];
    foreach ($urgent_keywords as $keyword) {
      if (str_contains($text, $keyword)) {
        $score += 15;
      }
    }

    return min(100, $score);
  }

}
