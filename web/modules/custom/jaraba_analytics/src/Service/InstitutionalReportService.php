<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de generacion de informes institucionales.
 *
 * Genera 5 tipos de informes PDF para justificacion de fondos publicos:
 * 1. Seguimiento Mensual — alumnos, progreso, incidencias.
 * 2. Memoria Economica — desglose gastos por partida.
 * 3. Informe de Impacto — insercion laboral, creacion empresa.
 * 4. Justificacion Tecnica — evidencias actividad formativa.
 * 5. Certificados de Asistencia — generacion masiva por cohorte.
 *
 * Reutiliza BrandedPdfService de ecosistema_jaraba_core para PDF con marca.
 *
 * F7 — Doc 182.
 */
class InstitutionalReportService {

  /**
   * Tipos de informe disponibles.
   */
  public const REPORT_TYPES = [
    'monthly_tracking' => 'Seguimiento Mensual',
    'economic_report' => 'Memoria Economica',
    'impact_report' => 'Informe de Impacto',
    'technical_justification' => 'Justificacion Tecnica',
    'attendance_certificates' => 'Certificados de Asistencia',
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera un informe institucional.
   *
   * @param string $type
   *   Tipo de informe (clave de REPORT_TYPES).
   * @param array $data
   *   Datos para el informe. Estructura segun tipo:
   *   - monthly_tracking: program_name, period, students[], progress_summary, incidents[]
   *   - economic_report: program_name, period, budget_lines[], totals
   *   - impact_report: program_name, period, metrics (insertion_rate, companies_created, etc.)
   *   - technical_justification: program_name, period, activities[], evidence[]
   *   - attendance_certificates: program_name, cohort_name, students[]
   * @param int|null $tenantId
   *   ID del tenant para la marca.
   *
   * @return array{success: bool, uri?: string, filename?: string, error?: string}
   */
  public function generateReport(string $type, array $data, ?int $tenantId = NULL): array {
    if (!isset(self::REPORT_TYPES[$type])) {
      return ['success' => FALSE, 'error' => 'Tipo de informe no valido: ' . $type];
    }

    try {
      $pdf = $this->createPdfInstance();
      $programName = $data['program_name'] ?? 'Programa';
      $period = $data['period'] ?? date('m/Y');

      $pdf->SetCreator('Ecosistema Jaraba');
      $pdf->SetAuthor($programName);
      $pdf->SetTitle(self::REPORT_TYPES[$type] . ' — ' . $period);
      $pdf->SetMargins(20, 20, 20);
      $pdf->SetAutoPageBreak(TRUE, 25);
      $pdf->SetPrintHeader(FALSE);
      // Enable footer for page numbers + generation date.
      $pdf->SetPrintFooter(TRUE);

      match ($type) {
        'monthly_tracking' => $this->buildMonthlyTracking($pdf, $data),
        'economic_report' => $this->buildEconomicReport($pdf, $data),
        'impact_report' => $this->buildImpactReport($pdf, $data),
        'technical_justification' => $this->buildTechnicalJustification($pdf, $data),
        'attendance_certificates' => $this->buildAttendanceCertificates($pdf, $data),
      };

      // Guardar PDF.
      $directory = 'private://institutional_reports/' . date('Y/m');
      \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

      $filename = $type . '_' . str_replace('/', '-', $period) . '_' . time() . '.pdf';
      $uri = $directory . '/' . $filename;
      $realpath = \Drupal::service('file_system')->realpath($uri) ?: '/tmp/' . $filename;

      $pdf->Output($realpath, 'F');

      $this->logger->info('Institutional report generated: @type for @program (@period)', [
        '@type' => $type,
        '@program' => $programName,
        '@period' => $period,
      ]);

      return [
        'success' => TRUE,
        'uri' => $uri,
        'filename' => $filename,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error generating institutional report @type: @error', [
        '@type' => $type,
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Retorna los tipos de informes disponibles con metadatos.
   *
   * @return array
   *   Array de tipos con label, description, icon.
   */
  public function getAvailableReportTypes(): array {
    return [
      'monthly_tracking' => [
        'label' => t('Seguimiento Mensual'),
        'description' => t('Alumnos, progreso por modulo, incidencias.'),
        'icon' => 'calendar',
      ],
      'economic_report' => [
        'label' => t('Memoria Economica'),
        'description' => t('Desglose de gastos por partida presupuestaria.'),
        'icon' => 'dollar-sign',
      ],
      'impact_report' => [
        'label' => t('Informe de Impacto'),
        'description' => t('Insercion laboral, creacion de empresas, indicadores.'),
        'icon' => 'trending-up',
      ],
      'technical_justification' => [
        'label' => t('Justificacion Tecnica'),
        'description' => t('Evidencias de actividad formativa para el financiador.'),
        'icon' => 'file-text',
      ],
      'attendance_certificates' => [
        'label' => t('Certificados de Asistencia'),
        'description' => t('Generacion masiva de certificados por cohorte.'),
        'icon' => 'award',
      ],
    ];
  }

  /**
   * Crea instancia TCPDF con footer de paginacion y fecha.
   */
  protected function createPdfInstance(): \TCPDF {
    if (!class_exists('TCPDF')) {
      // Fallback: try autoloading.
      $autoload = DRUPAL_ROOT . '/vendor/autoload.php';
      if (file_exists($autoload)) {
        require_once $autoload;
      }
    }

    $pdf = new class('P', 'mm', 'A4', TRUE, 'UTF-8', FALSE) extends \TCPDF {

      /**
       * {@inheritdoc}
       */
      public function Footer(): void {
        $this->SetY(-15);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(150, 150, 150);

        // Left: generation date.
        $this->Cell(0, 10,
          'Generado: ' . date('d/m/Y H:i') . ' | Ecosistema Jaraba',
          0, 0, 'L');

        // Right: page number.
        $this->Cell(0, 10,
          'Pagina ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(),
          0, 0, 'R');
      }

    };

    return $pdf;
  }

  /**
   * Build Seguimiento Mensual report.
   */
  protected function buildMonthlyTracking(\TCPDF $pdf, array $data): void {
    $pdf->AddPage();

    // Title.
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor(35, 61, 99); // corporate blue
    $pdf->Cell(0, 12, 'SEGUIMIENTO MENSUAL', 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 8, ($data['program_name'] ?? 'Programa') . ' — ' . ($data['period'] ?? ''), 0, 1, 'C');
    $pdf->Ln(10);

    // Summary.
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->SetTextColor(35, 61, 99);
    $pdf->Cell(0, 8, 'Resumen de Participantes', 0, 1, 'L');
    $pdf->Ln(3);

    $students = $data['students'] ?? [];
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(50, 50, 50);

    // Table header.
    $pdf->SetFillColor(35, 61, 99);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(50, 8, 'Nombre', 1, 0, 'L', TRUE);
    $pdf->Cell(40, 8, 'Email', 1, 0, 'L', TRUE);
    $pdf->Cell(30, 8, 'Estado', 1, 0, 'C', TRUE);
    $pdf->Cell(25, 8, 'Progreso', 1, 0, 'C', TRUE);
    $pdf->Cell(25, 8, 'Asistencia', 1, 1, 'C', TRUE);

    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetFont('helvetica', '', 9);
    $fill = FALSE;
    foreach ($students as $student) {
      if ($fill) {
        $pdf->SetFillColor(245, 245, 250);
      }
      $pdf->Cell(50, 7, $student['name'] ?? '', 'LR', 0, 'L', $fill);
      $pdf->Cell(40, 7, $student['email'] ?? '', 'LR', 0, 'L', $fill);
      $pdf->Cell(30, 7, $student['status'] ?? '', 'LR', 0, 'C', $fill);
      $pdf->Cell(25, 7, ($student['progress'] ?? 0) . '%', 'LR', 0, 'C', $fill);
      $pdf->Cell(25, 7, ($student['attendance'] ?? 0) . '%', 'LR', 1, 'C', $fill);
      $fill = !$fill;
    }
    $pdf->Cell(170, 0, '', 'T');
    $pdf->Ln(8);

    // Progress summary.
    if (!empty($data['progress_summary'])) {
      $pdf->SetFont('helvetica', 'B', 13);
      $pdf->SetTextColor(35, 61, 99);
      $pdf->Cell(0, 8, 'Resumen de Progreso', 0, 1, 'L');
      $pdf->SetFont('helvetica', '', 10);
      $pdf->SetTextColor(50, 50, 50);
      $pdf->MultiCell(0, 6, $data['progress_summary'], 0, 'L');
      $pdf->Ln(5);
    }

    // Incidents.
    $incidents = $data['incidents'] ?? [];
    if (!empty($incidents)) {
      $pdf->SetFont('helvetica', 'B', 13);
      $pdf->SetTextColor(35, 61, 99);
      $pdf->Cell(0, 8, 'Incidencias', 0, 1, 'L');
      $pdf->SetFont('helvetica', '', 10);
      $pdf->SetTextColor(50, 50, 50);
      foreach ($incidents as $incident) {
        $pdf->Cell(5, 6, '-', 0, 0);
        $pdf->MultiCell(0, 6, $incident, 0, 'L');
      }
    }
  }

  /**
   * Build Memoria Economica report.
   */
  protected function buildEconomicReport(\TCPDF $pdf, array $data): void {
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor(35, 61, 99);
    $pdf->Cell(0, 12, 'MEMORIA ECONOMICA', 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 8, ($data['program_name'] ?? 'Programa') . ' — ' . ($data['period'] ?? ''), 0, 1, 'C');
    $pdf->Ln(10);

    // Budget lines table.
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->SetTextColor(35, 61, 99);
    $pdf->Cell(0, 8, 'Desglose por Partida Presupuestaria', 0, 1, 'L');
    $pdf->Ln(3);

    $pdf->SetFillColor(35, 61, 99);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(55, 8, 'Partida', 1, 0, 'L', TRUE);
    $pdf->Cell(30, 8, 'Presupuesto', 1, 0, 'R', TRUE);
    $pdf->Cell(30, 8, 'Ejecutado', 1, 0, 'R', TRUE);
    $pdf->Cell(30, 8, 'Disponible', 1, 0, 'R', TRUE);
    $pdf->Cell(25, 8, '% Ejecucion', 1, 1, 'C', TRUE);

    $lines = $data['budget_lines'] ?? [];
    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetFont('helvetica', '', 9);

    foreach ($lines as $line) {
      $budget = (float) ($line['budget'] ?? 0);
      $spent = (float) ($line['spent'] ?? 0);
      $remaining = $budget - $spent;
      $pct = $budget > 0 ? round(($spent / $budget) * 100, 1) : 0;

      $pdf->Cell(55, 7, $line['name'] ?? '', 'LR', 0, 'L');
      $pdf->Cell(30, 7, number_format($budget, 2, ',', '.') . ' EUR', 'LR', 0, 'R');
      $pdf->Cell(30, 7, number_format($spent, 2, ',', '.') . ' EUR', 'LR', 0, 'R');
      $pdf->Cell(30, 7, number_format($remaining, 2, ',', '.') . ' EUR', 'LR', 0, 'R');
      $pdf->Cell(25, 7, $pct . '%', 'LR', 1, 'C');
    }
    $pdf->Cell(170, 0, '', 'T');
    $pdf->Ln(5);

    // Totals.
    $totals = $data['totals'] ?? [];
    if (!empty($totals)) {
      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->Cell(55, 8, 'TOTAL', 0, 0, 'L');
      $pdf->Cell(30, 8, number_format((float) ($totals['budget'] ?? 0), 2, ',', '.') . ' EUR', 0, 0, 'R');
      $pdf->Cell(30, 8, number_format((float) ($totals['spent'] ?? 0), 2, ',', '.') . ' EUR', 0, 0, 'R');
      $pdf->Cell(30, 8, number_format((float) ($totals['remaining'] ?? 0), 2, ',', '.') . ' EUR', 0, 0, 'R');
      $pctTotal = ($totals['budget'] ?? 0) > 0 ? round((($totals['spent'] ?? 0) / $totals['budget']) * 100, 1) : 0;
      $pdf->Cell(25, 8, $pctTotal . '%', 0, 1, 'C');
    }
  }

  /**
   * Build Informe de Impacto report.
   */
  protected function buildImpactReport(\TCPDF $pdf, array $data): void {
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor(35, 61, 99);
    $pdf->Cell(0, 12, 'INFORME DE IMPACTO', 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 8, ($data['program_name'] ?? 'Programa') . ' — ' . ($data['period'] ?? ''), 0, 1, 'C');
    $pdf->Ln(10);

    // Key metrics.
    $metrics = $data['metrics'] ?? [];
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->SetTextColor(35, 61, 99);
    $pdf->Cell(0, 8, 'Indicadores Clave', 0, 1, 'L');
    $pdf->Ln(3);

    $metricLabels = [
      'total_participants' => 'Participantes Totales',
      'completion_rate' => 'Tasa de Finalizacion (%)',
      'insertion_rate' => 'Tasa de Insercion Laboral (%)',
      'companies_created' => 'Empresas Creadas',
      'satisfaction_score' => 'Satisfaccion Media (/10)',
      'certifications_issued' => 'Certificaciones Emitidas',
    ];

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(50, 50, 50);

    foreach ($metricLabels as $key => $label) {
      if (isset($metrics[$key])) {
        $pdf->Cell(100, 7, $label . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, (string) $metrics[$key], 0, 1, 'R');
        $pdf->SetFont('helvetica', '', 10);
      }
    }
    $pdf->Ln(5);

    // Qualitative notes.
    if (!empty($data['qualitative_notes'])) {
      $pdf->SetFont('helvetica', 'B', 13);
      $pdf->SetTextColor(35, 61, 99);
      $pdf->Cell(0, 8, 'Analisis Cualitativo', 0, 1, 'L');
      $pdf->SetFont('helvetica', '', 10);
      $pdf->SetTextColor(50, 50, 50);
      $pdf->MultiCell(0, 6, $data['qualitative_notes'], 0, 'L');
    }
  }

  /**
   * Build Justificacion Tecnica report.
   */
  protected function buildTechnicalJustification(\TCPDF $pdf, array $data): void {
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor(35, 61, 99);
    $pdf->Cell(0, 12, 'JUSTIFICACION TECNICA', 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 8, ($data['program_name'] ?? 'Programa') . ' — ' . ($data['period'] ?? ''), 0, 1, 'C');
    $pdf->Ln(10);

    // Activities.
    $activities = $data['activities'] ?? [];
    if (!empty($activities)) {
      $pdf->SetFont('helvetica', 'B', 13);
      $pdf->SetTextColor(35, 61, 99);
      $pdf->Cell(0, 8, 'Actividades Formativas Realizadas', 0, 1, 'L');
      $pdf->Ln(3);

      $pdf->SetFillColor(35, 61, 99);
      $pdf->SetTextColor(255, 255, 255);
      $pdf->SetFont('helvetica', 'B', 9);
      $pdf->Cell(50, 8, 'Actividad', 1, 0, 'L', TRUE);
      $pdf->Cell(25, 8, 'Fecha', 1, 0, 'C', TRUE);
      $pdf->Cell(20, 8, 'Horas', 1, 0, 'C', TRUE);
      $pdf->Cell(30, 8, 'Participantes', 1, 0, 'C', TRUE);
      $pdf->Cell(45, 8, 'Formador', 1, 1, 'L', TRUE);

      $pdf->SetTextColor(50, 50, 50);
      $pdf->SetFont('helvetica', '', 9);

      foreach ($activities as $activity) {
        $pdf->Cell(50, 7, $activity['name'] ?? '', 'LR', 0, 'L');
        $pdf->Cell(25, 7, $activity['date'] ?? '', 'LR', 0, 'C');
        $pdf->Cell(20, 7, (string) ($activity['hours'] ?? 0), 'LR', 0, 'C');
        $pdf->Cell(30, 7, (string) ($activity['participants'] ?? 0), 'LR', 0, 'C');
        $pdf->Cell(45, 7, $activity['trainer'] ?? '', 'LR', 1, 'L');
      }
      $pdf->Cell(170, 0, '', 'T');
      $pdf->Ln(8);
    }

    // Evidence list.
    $evidence = $data['evidence'] ?? [];
    if (!empty($evidence)) {
      $pdf->SetFont('helvetica', 'B', 13);
      $pdf->SetTextColor(35, 61, 99);
      $pdf->Cell(0, 8, 'Evidencias Documentales', 0, 1, 'L');
      $pdf->SetFont('helvetica', '', 10);
      $pdf->SetTextColor(50, 50, 50);
      foreach ($evidence as $i => $item) {
        $pdf->Cell(0, 6, ($i + 1) . '. ' . $item, 0, 1, 'L');
      }
    }
  }

  /**
   * Build Certificados de Asistencia (batch).
   */
  protected function buildAttendanceCertificates(\TCPDF $pdf, array $data): void {
    $students = $data['students'] ?? [];
    $programName = $data['program_name'] ?? 'Programa';
    $cohortName = $data['cohort_name'] ?? '';
    $hours = $data['total_hours'] ?? 0;
    $dateIssued = $data['date_issued'] ?? date('d/m/Y');

    foreach ($students as $student) {
      $pdf->AddPage('L'); // Landscape for certificates.

      // Border.
      $pdf->SetDrawColor(35, 61, 99);
      $pdf->SetLineWidth(1.5);
      $pdf->Rect(10, 10, 277, 190);
      $pdf->SetLineWidth(0.5);
      $pdf->Rect(13, 13, 271, 184);

      // Title.
      $pdf->Ln(25);
      $pdf->SetFont('helvetica', 'B', 28);
      $pdf->SetTextColor(35, 61, 99);
      $pdf->Cell(0, 15, 'CERTIFICADO DE ASISTENCIA', 0, 1, 'C');

      $pdf->Ln(8);
      $pdf->SetFont('helvetica', '', 14);
      $pdf->SetTextColor(80, 80, 80);
      $pdf->Cell(0, 10, 'Se certifica que', 0, 1, 'C');

      // Student name.
      $pdf->Ln(3);
      $pdf->SetFont('helvetica', 'B', 22);
      $pdf->SetTextColor(35, 61, 99);
      $pdf->Cell(0, 12, $student['name'] ?? '', 0, 1, 'C');

      // Details.
      $pdf->Ln(5);
      $pdf->SetFont('helvetica', '', 13);
      $pdf->SetTextColor(80, 80, 80);
      $pdf->Cell(0, 8, 'ha participado en el programa formativo', 0, 1, 'C');

      $pdf->SetFont('helvetica', 'B', 16);
      $pdf->SetTextColor(35, 61, 99);
      $pdf->Cell(0, 10, $programName, 0, 1, 'C');

      if ($cohortName) {
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 8, 'Cohorte: ' . $cohortName, 0, 1, 'C');
      }

      $pdf->SetFont('helvetica', '', 12);
      $pdf->Cell(0, 8, 'con una duracion de ' . $hours . ' horas lectivas.', 0, 1, 'C');

      // Date.
      $pdf->Ln(10);
      $pdf->Cell(0, 8, 'Fecha de emision: ' . $dateIssued, 0, 1, 'C');
    }
  }

}
