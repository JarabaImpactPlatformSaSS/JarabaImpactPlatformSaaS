<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Generates service sheets (hojas de servicio) for mentoring sessions.
 *
 * When a mentoring session is completed (status=completed + session_notes filled),
 * this service auto-generates a PDF document and stores it as an
 * ExpedienteDocumento with category 'mentoria_hoja_servicio'.
 *
 * Uses BrandedPdfService::generateReport() for PDF rendering with DomPDF.
 */
class HojaServicioMentoriaService {

  /**
   * Constructs a HojaServicioMentoriaService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ExpedienteService $expedienteService,
    protected ?object $brandedPdfService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Generates a service sheet for a completed mentoring session.
   *
   * @param int $sessionId
   *   The mentoring_session entity ID.
   *
   * @return int|null
   *   The ExpedienteDocumento entity ID if created, or NULL on failure.
   */
  public function generarHojaServicio(int $sessionId): ?int {
    try {
      $session = $this->entityTypeManager->getStorage('mentoring_session')->load($sessionId);
      if (!$session) {
        $this->logger->warning('Session @id not found for service sheet generation.', ['@id' => $sessionId]);
        return NULL;
      }

      // Validate session is ready for service sheet.
      $status = $session->get('status')->value;
      $notes = $session->get('session_notes')->value;
      if ($status !== 'completed' || empty($notes)) {
        return NULL;
      }

      // Get mentor and mentee info.
      $mentor = $session->get('mentor_id')->entity;
      $mentee = $session->get('mentee_id')->entity;

      // Resolve the participante from mentee user.
      $participanteId = $this->resolveParticipanteId($mentee ? (int) $mentee->id() : 0);
      if (!$participanteId) {
        $this->logger->warning('No participante found for mentee in session @id.', ['@id' => $sessionId]);
        return NULL;
      }

      // Build data for PDF generation.
      $data = $this->buildPdfData($session, $mentor, $mentee);

      // Generate PDF via BrandedPdfService.
      $pdfUri = NULL;
      if ($this->brandedPdfService) {
        $pdfUri = $this->brandedPdfService->generateReport($data);
      }

      // Create ExpedienteDocumento.
      $docData = [
        'titulo' => sprintf('Hoja de Servicio - Sesión %d - %s', $session->get('session_number')->value ?? 1, date('d/m/Y')),
        'categoria' => 'mentoria_hoja_servicio',
        'participante_id' => $participanteId,
        'archivo_nombre' => sprintf('hoja-servicio-sesion-%d-%s.pdf', $sessionId, date('Ymd')),
        'archivo_mime' => 'application/pdf',
        'estado_revision' => 'pendiente',
        'requerido_sto' => TRUE,
      ];

      if ($pdfUri) {
        $docData['archivo_vault_id'] = $pdfUri;
      }

      $docId = $this->expedienteService->subirDocumento($docData);

      if ($docId) {
        // Link service sheet doc to session.
        $session->set('service_sheet_doc', $docId);
        $session->save();

        $this->logger->info('Generated service sheet @doc for session @session.', [
          '@doc' => $docId,
          '@session' => $sessionId,
        ]);
      }

      return $docId;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generating service sheet for session @id: @msg', [
        '@id' => $sessionId,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Builds PDF data from session.
   *
   * @param mixed $session
   *   The mentoring_session entity.
   * @param mixed $mentor
   *   The mentor profile entity.
   * @param mixed $mentee
   *   The mentee user entity.
   *
   * @return array
   *   Data array for BrandedPdfService::generateReport().
   */
  protected function buildPdfData(mixed $session, mixed $mentor, mixed $mentee): array {
    $objectives = $session->get('objectives_worked')->value;
    $agreements = $session->get('agreements')->value;
    $nextSteps = $session->get('next_steps')->value;

    return [
      'title' => 'Hoja de Servicio de Mentoría',
      'subtitle' => sprintf('Sesión #%d', $session->get('session_number')->value ?? 1),
      'sections' => [
        [
          'heading' => 'Datos de la Sesión',
          'content' => sprintf(
            "Fecha: %s\nMentor: %s\nParticipante: %s\nTipo: %s\nDuración: %s",
            $session->get('scheduled_start')->value ?? '-',
            $mentor ? $mentor->getDisplayName() : '-',
            $mentee ? ($mentee->getDisplayName() ?? $mentee->getAccountName()) : '-',
            $session->get('session_type')->value ?? 'followup',
            $this->calculateDuration($session),
          ),
        ],
        [
          'heading' => 'Notas de la Sesión',
          'content' => $session->get('session_notes')->value ?? '',
        ],
        [
          'heading' => 'Objetivos Trabajados',
          'content' => $objectives ? $this->formatJsonList($objectives) : 'No especificados.',
        ],
        [
          'heading' => 'Acuerdos Alcanzados',
          'content' => $agreements ? $this->formatJsonList($agreements) : 'No especificados.',
        ],
        [
          'heading' => 'Próximos Pasos',
          'content' => $nextSteps ? $this->formatJsonList($nextSteps) : 'No especificados.',
        ],
      ],
      'footer' => 'Documento generado automáticamente por Jaraba Impact Platform. Requiere firma digital del participante y orientador.',
    ];
  }

  /**
   * Resolves the programa_participante_ei ID from a user ID.
   */
  protected function resolveParticipanteId(int $userId): ?int {
    if ($userId <= 0) {
      return NULL;
    }

    try {
      $ids = $this->entityTypeManager->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $userId)
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      return !empty($ids) ? (int) reset($ids) : NULL;
    }
    catch (\Throwable $e) {
      return NULL;
    }
  }

  /**
   * Calculates session duration.
   */
  protected function calculateDuration(mixed $session): string {
    $start = $session->get('actual_start')->value ?? $session->get('scheduled_start')->value;
    $end = $session->get('actual_end')->value ?? $session->get('scheduled_end')->value;

    if ($start && $end) {
      $diff = abs(strtotime($end) - strtotime($start));
      $hours = (int) ($diff / 3600);
      $minutes = (int) (($diff % 3600) / 60);
      return sprintf('%dh %dmin', $hours, $minutes);
    }

    return '1h 00min';
  }

  /**
   * Formats a JSON-encoded list into plain text.
   */
  protected function formatJsonList(string $json): string {
    $items = json_decode($json, TRUE);
    if (!is_array($items)) {
      return $json;
    }

    return implode("\n", array_map(fn($i, $item) => sprintf('%d. %s', $i + 1, is_string($item) ? $item : json_encode($item)), array_keys($items), $items));
  }

}
