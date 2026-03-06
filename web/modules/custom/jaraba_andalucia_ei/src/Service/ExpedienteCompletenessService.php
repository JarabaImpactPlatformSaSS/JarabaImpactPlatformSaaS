<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumento;
use Psr\Log\LoggerInterface;

/**
 * Calculates document completeness by role/status for a participant.
 *
 * Groups documents into STO requirements, program documents,
 * mentoring service sheets, and insertion documents.
 * Returns completeness percentages per category group.
 */
class ExpedienteCompletenessService {

  /**
   * Required documents per category group.
   */
  protected const REQUIRED_GROUPS = [
    'sto' => [
      'sto_dni',
      'sto_empadronamiento',
      'sto_vida_laboral',
      'sto_demanda_empleo',
      'sto_prestaciones',
    ],
    'programa' => [
      'programa_contrato',
      'programa_consentimiento',
      'programa_compromiso',
    ],
    'mentoria' => [
      'mentoria_hoja_servicio',
    ],
    'insercion' => [
      'insercion_contrato_laboral',
      'insercion_alta_ss',
    ],
  ];

  /**
   * Constructs an ExpedienteCompletenessService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Gets full completeness analysis for a participant.
   *
   * @param int $participanteId
   *   The programa_participante_ei entity ID.
   *
   * @return array
   *   Keyed by group: ['sto' => ['required' => [...], 'completed' => [...],
   *   'missing' => [...], 'percent' => int], ...] + 'total_percent'.
   */
  public function getCompleteness(int $participanteId): array {
    $docs = $this->loadDocuments($participanteId);

    // Build a map of existing approved/pending docs by category.
    $existingCategories = [];
    foreach ($docs as $doc) {
      $cat = $doc->getCategoria();
      $estado = $doc->getEstadoRevision();
      // Consider 'aprobado' and 'pendiente' as present (not rejected).
      if ($estado !== 'rechazado') {
        $existingCategories[$cat] = $estado;
      }
    }

    $result = [];
    $totalRequired = 0;
    $totalCompleted = 0;

    foreach (static::REQUIRED_GROUPS as $group => $requiredCats) {
      $completed = [];
      $missing = [];

      foreach ($requiredCats as $cat) {
        if (isset($existingCategories[$cat])) {
          $completed[] = [
            'category' => $cat,
            'label' => ExpedienteDocumento::CATEGORIAS[$cat] ?? $cat,
            'status' => $existingCategories[$cat],
          ];
        }
        else {
          $missing[] = [
            'category' => $cat,
            'label' => ExpedienteDocumento::CATEGORIAS[$cat] ?? $cat,
          ];
        }
      }

      $total = count($requiredCats);
      $done = count($completed);
      $totalRequired += $total;
      $totalCompleted += $done;

      $result[$group] = [
        'label' => $this->getGroupLabel($group),
        'required' => $requiredCats,
        'completed' => $completed,
        'missing' => $missing,
        'percent' => $total > 0 ? (int) round(($done / $total) * 100) : 100,
      ];
    }

    $result['total_percent'] = $totalRequired > 0
      ? (int) round(($totalCompleted / $totalRequired) * 100)
      : 100;

    return $result;
  }

  /**
   * Checks if participant has STO-complete documentation.
   *
   * @param int $participanteId
   *   The programa_participante_ei entity ID.
   *
   * @return bool
   *   TRUE if all STO-required documents are present and approved.
   */
  public function isStoComplete(int $participanteId): bool {
    $completeness = $this->getCompleteness($participanteId);
    return isset($completeness['sto']) && $completeness['sto']['percent'] === 100;
  }

  /**
   * Gets documents grouped by category for the hub view.
   *
   * @param int $participanteId
   *   The programa_participante_ei entity ID.
   *
   * @return array
   *   Grouped by category key: ['sto_dni' => [{doc data}], ...].
   */
  public function getDocumentsByCategory(int $participanteId): array {
    $docs = $this->loadDocuments($participanteId);
    $grouped = [];

    foreach ($docs as $doc) {
      $cat = $doc->getCategoria();
      $grouped[$cat][] = [
        'id' => (int) $doc->id(),
        'titulo' => $doc->getTitulo(),
        'categoria' => $cat,
        'categoria_label' => ExpedienteDocumento::CATEGORIAS[$cat] ?? $cat,
        'estado_revision' => $doc->getEstadoRevision(),
        'firmado' => $doc->isFirmado(),
        'requerido_sto' => $doc->isRequeridoSto(),
        'archivo_nombre' => $doc->get('archivo_nombre')->value ?? '',
        'created' => $doc->get('created')->value,
      ];
    }

    return $grouped;
  }

  /**
   * Loads all documents for a participant.
   */
  protected function loadDocuments(int $participanteId): array {
    try {
      $ids = $this->entityTypeManager->getStorage('expediente_documento')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('participante_id', $participanteId)
        ->condition('status', TRUE)
        ->sort('categoria')
        ->sort('created', 'DESC')
        ->execute();

      return !empty($ids) ? $this->entityTypeManager->getStorage('expediente_documento')->loadMultiple($ids) : [];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading documents for participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Returns a human-readable label for a document group.
   */
  protected function getGroupLabel(string $group): string {
    return match ($group) {
      'sto' => 'Documentos STO',
      'programa' => 'Documentos del Programa',
      'mentoria' => 'Hojas de Servicio de Mentoría',
      'insercion' => 'Documentos de Inserción',
      default => ucfirst($group),
    };
  }

}
