<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;

/**
 * Branded PDF generation service.
 *
 * Generates PDFs (invoices, certificates, reports) applying tenant
 * branding from the WhitelabelConfig entity. Delegates to TCPDF when
 * available, otherwise produces a placeholder file path.
 */
class BrandedPdfService {

  /**
   * The file system service.
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    FileSystemInterface $file_system,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
  ) {
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Generates a branded PDF document.
   *
   * @param string $type
   *   Document type: 'invoice', 'certificate' or 'report'.
   * @param int $tenantId
   *   The tenant (group) ID.
   * @param array $data
   *   Document-specific data payload.
   *
   * @return string|null
   *   File URI of the generated PDF, or NULL on failure.
   */
  public function generatePdf(string $type, int $tenantId, array $data): ?string {
    try {
      $brand = $this->getBrandConfig($tenantId);
      $directory = 'private://whitelabel-pdf/' . $type . '/' . date('Y/m');

      $this->fileSystem->prepareDirectory(
        $directory,
        FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
      );

      // If TCPDF is available, delegate to the parent core service.
      if (class_exists('TCPDF')) {
        return $this->generateWithTcpdf($type, $brand, $data, $directory);
      }

      // Fallback: create a placeholder HTML file.
      $filename = $type . '-' . $tenantId . '-' . time() . '.html';
      $uri = $directory . '/' . $filename;
      $html = $this->buildHtmlFallback($type, $brand, $data);

      $this->fileSystem->saveData($html, $uri, FileSystemInterface::EXISTS_REPLACE);

      $this->logger->info('Branded PDF (HTML fallback) generated: @uri', ['@uri' => $uri]);

      return $uri;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generating branded PDF (@type) for tenant @tenant: @message', [
        '@type' => $type,
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Returns available PDF template types.
   *
   * @return array
   *   Keyed array of template type => label.
   */
  public function getAvailableTemplates(): array {
    return [
      'invoice' => 'Invoice',
      'certificate' => 'Certificate',
      'report' => 'Report',
    ];
  }

  /**
   * Loads brand configuration for a tenant from WhitelabelConfig.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return array
   *   Brand config array.
   */
  protected function getBrandConfig(int $tenantId): array {
    $defaults = [
      'company_name' => 'Ecosistema Jaraba',
      'primary_color' => '#FF8C42',
      'secondary_color' => '#00A9A5',
      'logo_url' => '',
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('whitelabel_config');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('config_status', 'active')
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return $defaults;
      }

      $config = $storage->load(reset($ids));
      if (!$config) {
        return $defaults;
      }

      return [
        'company_name' => $config->get('company_name')->value ?: $defaults['company_name'],
        'primary_color' => $config->get('primary_color')->value ?: $defaults['primary_color'],
        'secondary_color' => $config->get('secondary_color')->value ?: $defaults['secondary_color'],
        'logo_url' => $config->get('logo_url')->value ?: $defaults['logo_url'],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('Could not load brand config for tenant @id: @message', [
        '@id' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      return $defaults;
    }
  }

  /**
   * Generates PDF using TCPDF library.
   *
   * @param string $type
   *   Document type.
   * @param array $brand
   *   Brand configuration.
   * @param array $data
   *   Document data.
   * @param string $directory
   *   Target directory URI.
   *
   * @return string|null
   *   File URI or NULL.
   */
  protected function generateWithTcpdf(string $type, array $brand, array $data, string $directory): ?string {
    $pdf = new \TCPDF('P', 'mm', 'A4', TRUE, 'UTF-8', FALSE);
    $pdf->SetCreator($brand['company_name']);
    $pdf->SetAuthor($brand['company_name']);
    $pdf->SetTitle(ucfirst($type));
    $pdf->SetMargins(20, 15, 20);
    $pdf->SetAutoPageBreak(TRUE, 30);
    $pdf->SetPrintHeader(FALSE);
    $pdf->SetPrintFooter(FALSE);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 12, mb_strtoupper($type), 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 6, $brand['company_name'], 0, 'C');

    $filename = $type . '-' . time() . '.pdf';
    $realPath = $this->fileSystem->realpath($directory);

    if (!$realPath) {
      return NULL;
    }

    $fullPath = $realPath . '/' . $filename;
    $pdf->Output($fullPath, 'F');

    $uri = $directory . '/' . $filename;

    if (file_exists($fullPath)) {
      $this->logger->info('Branded PDF generated: @uri', ['@uri' => $uri]);
      return $uri;
    }

    return NULL;
  }

  /**
   * Builds an HTML fallback when TCPDF is not available.
   *
   * @param string $type
   *   Document type.
   * @param array $brand
   *   Brand configuration.
   * @param array $data
   *   Document data.
   *
   * @return string
   *   HTML content.
   */
  protected function buildHtmlFallback(string $type, array $brand, array $data): string {
    $title = htmlspecialchars($data['title'] ?? ucfirst($type), ENT_QUOTES);
    $company = htmlspecialchars($brand['company_name'], ENT_QUOTES);
    $primary = htmlspecialchars($brand['primary_color'], ENT_QUOTES);

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>{$title}</title>
<style>body{font-family:sans-serif;margin:2em}h1{color:{$primary}}footer{margin-top:3em;font-size:0.8em;color:#888}</style>
</head>
<body>
<h1>{$title}</h1>
<p>{$company}</p>
<p>Document type: {$type}</p>
<footer>Generated by Jaraba Whitelabel</footer>
</body></html>
HTML;
  }

}
