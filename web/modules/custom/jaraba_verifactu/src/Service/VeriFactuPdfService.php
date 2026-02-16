<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Service;

use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;
use TCPDF;

/**
 * Service for injecting VeriFactu compliance elements into invoice PDFs.
 *
 * Adds the following elements per Orden HAC/1177/2024:
 * - QR code for AEAT verification (bottom-right corner)
 * - VERI*FACTU label (below QR)
 * - Abbreviated SHA-256 hash of the record
 *
 * Follows the BrandedPdfService pattern using TCPDF.
 *
 * Spec: Doc 179, FASE 4, entregable F4-4.
 */
class VeriFactuPdfService {

  /**
   * QR code dimensions in mm.
   */
  protected const QR_SIZE_MM = 25;

  /**
   * Margin from the right edge in mm.
   */
  protected const MARGIN_RIGHT_MM = 15;

  /**
   * Margin from the bottom edge in mm.
   */
  protected const MARGIN_BOTTOM_MM = 30;

  /**
   * AEAT institutional blue color.
   */
  protected const AEAT_BLUE = '#003366';

  public function __construct(
    protected VeriFactuQrService $qrService,
    protected FileSystemInterface $fileSystem,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Stamps VeriFactu compliance elements onto an existing invoice PDF.
   *
   * @param string $pdfUri
   *   URI of the existing PDF file (e.g., private://invoices/2026/02/inv.pdf).
   * @param array $recordData
   *   VeriFactu record data with keys:
   *   - nif_emisor: (string) Emitter NIF.
   *   - numero_factura: (string) Invoice number.
   *   - fecha_expedicion: (string) Date in YYYY-MM-DD format.
   *   - importe_total: (string) Total amount.
   *   - hash_record: (string) 64-char SHA-256 hash.
   *   - qr_image: (string|null) Pre-generated QR as base64 PNG.
   *
   * @return string|null
   *   URI of the stamped PDF, or NULL on failure.
   */
  public function stampInvoicePdf(string $pdfUri, array $recordData): ?string {
    try {
      $realPath = $this->fileSystem->realpath($pdfUri);
      if (!$realPath || !file_exists($realPath)) {
        $this->logger->error('VeriFactu PDF stamp: source file not found: @uri', [
          '@uri' => $pdfUri,
        ]);
        return NULL;
      }

      $pdf = $this->createPdfInstance();
      $pageCount = $pdf->setSourceFile($realPath);

      for ($page = 1; $page <= $pageCount; $page++) {
        $templateId = $pdf->importPage($page);
        $size = $pdf->getTemplateSize($templateId);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId);

        // Only stamp the first page.
        if ($page === 1) {
          $this->addVerifactuElements($pdf, $recordData, $size['width'], $size['height']);
        }
      }

      // Overwrite the original file.
      $pdf->Output($realPath, 'F');

      if (file_exists($realPath)) {
        $this->logger->info('VeriFactu PDF stamped: @uri', ['@uri' => $pdfUri]);
        return $pdfUri;
      }

      return NULL;
    }
    catch (\Throwable $e) {
      $this->logger->error('VeriFactu PDF stamp failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Generates a standalone VeriFactu compliance page as PDF.
   *
   * Creates a single-page PDF with the QR code, VERI*FACTU label,
   * invoice details and hash — for attachment or separate printing.
   *
   * @param array $recordData
   *   VeriFactu record data (same as stampInvoicePdf).
   *
   * @return string|null
   *   URI of the generated PDF, or NULL on failure.
   */
  public function generateCompliancePage(array $recordData): ?string {
    try {
      $pdf = new TCPDF('P', 'mm', 'A4', TRUE, 'UTF-8', FALSE);
      $pdf->SetCreator('Jaraba VeriFactu');
      $pdf->SetAuthor('Ecosistema Jaraba');
      $pdf->SetTitle('VeriFactu - ' . ($recordData['numero_factura'] ?? ''));
      $pdf->setPrintHeader(FALSE);
      $pdf->setPrintFooter(FALSE);
      $pdf->SetMargins(20, 20, 20);
      $pdf->AddPage();

      $aeatRgb = $this->hexToRgb(self::AEAT_BLUE);

      // Title.
      $pdf->SetFont('helvetica', 'B', 18);
      $pdf->SetTextColor($aeatRgb[0], $aeatRgb[1], $aeatRgb[2]);
      $pdf->Cell(0, 12, 'VERI*FACTU', 0, 1, 'C');
      $pdf->Ln(4);

      // Subtitle.
      $pdf->SetFont('helvetica', '', 10);
      $pdf->SetTextColor(100, 100, 100);
      $pdf->Cell(0, 6, 'Registro verificable de factura - RD 1007/2023', 0, 1, 'C');
      $pdf->Ln(8);

      // Separator line.
      $pdf->SetDrawColor($aeatRgb[0], $aeatRgb[1], $aeatRgb[2]);
      $pdf->SetLineWidth(0.5);
      $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
      $pdf->Ln(8);

      // Invoice details.
      $this->addDetailRow($pdf, 'NIF Emisor:', $recordData['nif_emisor'] ?? '—');
      $this->addDetailRow($pdf, 'Numero Factura:', $recordData['numero_factura'] ?? '—');
      $this->addDetailRow($pdf, 'Fecha Expedicion:', $recordData['fecha_expedicion'] ?? '—');
      $this->addDetailRow($pdf, 'Importe Total:', ($recordData['importe_total'] ?? '0.00') . ' EUR');
      $pdf->Ln(4);

      // Hash.
      $hash = $recordData['hash_record'] ?? '';
      if ($hash !== '') {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor($aeatRgb[0], $aeatRgb[1], $aeatRgb[2]);
        $pdf->Cell(0, 6, 'SHA-256 Hash del registro:', 0, 1);
        $pdf->SetFont('courier', '', 8);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->MultiCell(0, 5, $hash, 0, 'L');
        $pdf->Ln(8);
      }

      // QR Code (centered).
      $qrImage = $recordData['qr_image'] ?? NULL;
      if ($qrImage === NULL && !empty($recordData['nif_emisor'])) {
        $qrImage = $this->qrService->generateQrImage($recordData);
      }

      if ($qrImage) {
        $this->renderQrToPdf($pdf, $qrImage, 75, $pdf->GetY(), 60);
        $pdf->SetY($pdf->GetY() + 65);
      }

      // Footer note.
      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetTextColor(120, 120, 120);
      $pdf->Cell(0, 5, 'Documento generado automaticamente por el sistema VeriFactu.', 0, 1, 'C');
      $pdf->Cell(0, 5, 'Verifique en: https://www2.agenciatributaria.gob.es', 0, 1, 'C');

      // Save.
      $directory = 'private://verifactu/' . date('Y/m');
      $filename = 'verifactu-' . ($recordData['numero_factura'] ?? time()) . '.pdf';
      $filename = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename);

      return $this->savePdf($pdf, $directory, $filename);
    }
    catch (\Throwable $e) {
      $this->logger->error('VeriFactu compliance page generation failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Adds VeriFactu compliance elements to a PDF page.
   *
   * Places QR code (bottom-right), VERI*FACTU label, and abbreviated hash.
   *
   * @param \TCPDF $pdf
   *   The TCPDF instance.
   * @param array $recordData
   *   VeriFactu record data.
   * @param float $pageWidth
   *   Page width in mm.
   * @param float $pageHeight
   *   Page height in mm.
   */
  protected function addVerifactuElements(TCPDF $pdf, array $recordData, float $pageWidth, float $pageHeight): void {
    $qrX = $pageWidth - self::MARGIN_RIGHT_MM - self::QR_SIZE_MM;
    $qrY = $pageHeight - self::MARGIN_BOTTOM_MM - self::QR_SIZE_MM - 12;
    $aeatRgb = $this->hexToRgb(self::AEAT_BLUE);

    // QR code.
    $qrImage = $recordData['qr_image'] ?? NULL;
    if ($qrImage === NULL && !empty($recordData['nif_emisor'])) {
      $qrImage = $this->qrService->generateQrImage($recordData);
    }

    if ($qrImage) {
      $this->renderQrToPdf($pdf, $qrImage, $qrX, $qrY, self::QR_SIZE_MM);
    }

    // VERI*FACTU label (below QR).
    $labelY = $qrY + self::QR_SIZE_MM + 1;
    $pdf->SetFont('courier', 'B', 7);
    $pdf->SetTextColor($aeatRgb[0], $aeatRgb[1], $aeatRgb[2]);
    $pdf->SetXY($qrX, $labelY);
    $pdf->Cell(self::QR_SIZE_MM, 4, 'VERI*FACTU', 0, 1, 'C');

    // Abbreviated hash.
    $hash = $recordData['hash_record'] ?? '';
    if ($hash !== '') {
      $pdf->SetFont('courier', '', 5);
      $pdf->SetTextColor(120, 120, 120);
      $pdf->SetXY($qrX, $labelY + 4);
      $pdf->Cell(self::QR_SIZE_MM, 3, substr($hash, 0, 16) . '...', 0, 1, 'C');
    }
  }

  /**
   * Renders a QR image onto the PDF.
   *
   * @param \TCPDF $pdf
   *   TCPDF instance.
   * @param string $qrImage
   *   Base64-encoded PNG image.
   * @param float $x
   *   X position in mm.
   * @param float $y
   *   Y position in mm.
   * @param float $size
   *   QR size in mm.
   */
  protected function renderQrToPdf(TCPDF $pdf, string $qrImage, float $x, float $y, float $size): void {
    // Strip data URI prefix if present.
    $base64 = $qrImage;
    if (str_contains($base64, ',')) {
      $base64 = substr($base64, strpos($base64, ',') + 1);
    }

    $imageData = base64_decode($base64, TRUE);
    if ($imageData === FALSE) {
      return;
    }

    // Use TCPDF's @image string method.
    $pdf->Image('@' . $imageData, $x, $y, $size, $size, 'PNG');
  }

  /**
   * Adds a detail row to the compliance page.
   */
  protected function addDetailRow(TCPDF $pdf, string $label, string $value): void {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(50, 7, $label, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(40, 40, 40);
    $pdf->Cell(0, 7, $value, 0, 1);
  }

  /**
   * Creates a TCPDF+FPDI instance for importing existing PDFs.
   *
   * @return \setasign\Fpdi\Tcpdf\Fpdi
   *   FPDI-TCPDF instance ready for page import.
   */
  protected function createPdfInstance(): \setasign\Fpdi\Tcpdf\Fpdi {
    $pdf = new \setasign\Fpdi\Tcpdf\Fpdi('P', 'mm', 'A4', TRUE, 'UTF-8', FALSE);
    $pdf->setPrintHeader(FALSE);
    $pdf->setPrintFooter(FALSE);
    return $pdf;
  }

  /**
   * Saves a PDF to the private filesystem.
   *
   * @param \TCPDF $pdf
   *   TCPDF instance with rendered content.
   * @param string $directory
   *   Target directory URI (e.g., private://verifactu/2026/02).
   * @param string $filename
   *   PDF filename.
   *
   * @return string|null
   *   URI of the generated file, or NULL on failure.
   */
  protected function savePdf(TCPDF $pdf, string $directory, string $filename): ?string {
    $this->fileSystem->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS,
    );

    $realPath = $this->fileSystem->realpath($directory);
    if (!$realPath) {
      $this->logger->error('VeriFactu PDF: cannot resolve directory: @dir', [
        '@dir' => $directory,
      ]);
      return NULL;
    }

    $fullPath = $realPath . '/' . $filename;
    $pdf->Output($fullPath, 'F');

    if (file_exists($fullPath)) {
      $uri = $directory . '/' . $filename;
      $this->logger->info('VeriFactu PDF generated: @uri', ['@uri' => $uri]);
      return $uri;
    }

    return NULL;
  }

  /**
   * Converts a hexadecimal color to an RGB array.
   *
   * @param string $hex
   *   Color in hex format (#RRGGBB or RRGGBB).
   *
   * @return array
   *   Array of three integers [R, G, B] in range 0-255.
   */
  protected function hexToRgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) {
      return [0, 0, 0];
    }
    return [
      (int) hexdec(substr($hex, 0, 2)),
      (int) hexdec(substr($hex, 2, 2)),
      (int) hexdec(substr($hex, 4, 2)),
    ];
  }

}
