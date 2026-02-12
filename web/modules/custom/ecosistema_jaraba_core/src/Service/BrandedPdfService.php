<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;
use TCPDF;

/**
 * Servicio de generacion de PDFs con marca del tenant.
 *
 * Genera facturas, certificados e informes aplicando automaticamente
 * la identidad visual (colores, logo, tipografias) configurada en el
 * TenantThemeConfig del modulo jaraba_theming.
 *
 * Cada tipo de documento se guarda en su propio directorio dentro de
 * private:// con organizacion por fecha (Y/m).
 *
 * @see \Drupal\jaraba_theming\Service\ThemeTokenService
 * @see \Drupal\jaraba_theming\Entity\TenantThemeConfig
 */
class BrandedPdfService {

  /**
   * El servicio de sistema de archivos.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * El gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El logger del servicio.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   El servicio de sistema de archivos de Drupal.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   El gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logging para ecosistema_jaraba_core.
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
   * Genera un PDF de factura con la marca del tenant.
   *
   * @param array $data
   *   Datos de la factura con las claves:
   *   - invoice_number: (string) Numero de factura.
   *   - date: (string) Fecha de emision.
   *   - client_name: (string) Nombre del cliente.
   *   - client_address: (string) Direccion del cliente.
   *   - items: (array) Lineas de factura, cada una con:
   *     - name: (string) Descripcion.
   *     - quantity: (int|float) Cantidad.
   *     - unit_price: (float) Precio unitario.
   *     - total: (float) Total de la linea.
   *   - subtotal: (float) Subtotal antes de impuestos.
   *   - tax_rate: (float) Porcentaje de impuesto (ej. 21).
   *   - tax_amount: (float) Importe de impuesto.
   *   - total: (float) Total con impuestos.
   *   - notes: (string) Notas adicionales (opcional).
   * @param int|null $tenantId
   *   ID del tenant para obtener la marca. NULL usa los valores por defecto.
   *
   * @return string|null
   *   URI del archivo PDF generado (private://invoices/...) o NULL si falla.
   */
  public function generateInvoice(array $data, ?int $tenantId = NULL): ?string {
    try {
      $brand = $this->getBrandConfig($tenantId);
      $pdf = $this->createPdfInstance();

      // Metadatos del documento.
      $invoiceNumber = $data['invoice_number'] ?? 'SIN-NUMERO';
      $pdf->SetCreator('Ecosistema Jaraba');
      $pdf->SetAuthor('Ecosistema Jaraba');
      $pdf->SetTitle('Factura ' . $invoiceNumber);
      $pdf->SetSubject('Factura generada por Ecosistema Jaraba');

      $pdf->SetMargins(20, 15, 20);
      $pdf->SetAutoPageBreak(TRUE, 30);
      $pdf->SetPrintHeader(FALSE);
      $pdf->SetPrintFooter(FALSE);
      $pdf->AddPage();

      // Cabecera con marca.
      $this->applyBrandHeader($pdf, $brand, 'FACTURA');

      // Informacion de factura y cliente.
      $pdf->Ln(5);
      $primaryRgb = $this->hexToRgb($brand['color_primary']);
      $pdf->SetFont('helvetica', 'B', 11);
      $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
      $pdf->Cell(90, 7, 'Factura: ' . $invoiceNumber, 0, 0, 'L');
      $pdf->Cell(0, 7, 'Fecha: ' . ($data['date'] ?? date('d/m/Y')), 0, 1, 'R');

      $pdf->Ln(3);
      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->SetTextColor(60, 60, 60);
      $pdf->Cell(0, 6, 'Cliente:', 0, 1, 'L');
      $pdf->SetFont('helvetica', '', 10);
      $pdf->SetTextColor(80, 80, 80);
      $pdf->Cell(0, 6, $data['client_name'] ?? '', 0, 1, 'L');

      if (!empty($data['client_address'])) {
        $pdf->MultiCell(0, 5, $data['client_address'], 0, 'L');
      }

      $pdf->Ln(6);

      // Tabla de items.
      $accentRgb = $this->hexToRgb($brand['color_accent']);
      $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
      $pdf->SetTextColor(255, 255, 255);
      $pdf->SetFont('helvetica', 'B', 9);

      $pdf->Cell(80, 8, 'Concepto', 1, 0, 'L', TRUE);
      $pdf->Cell(25, 8, 'Cantidad', 1, 0, 'C', TRUE);
      $pdf->Cell(30, 8, 'Precio Ud.', 1, 0, 'R', TRUE);
      $pdf->Cell(35, 8, 'Total', 1, 1, 'R', TRUE);

      // Filas de items.
      $pdf->SetFont('helvetica', '', 9);
      $pdf->SetTextColor(50, 50, 50);
      $fill = FALSE;

      $items = $data['items'] ?? [];
      foreach ($items as $item) {
        if ($fill) {
          $pdf->SetFillColor(245, 245, 250);
        }
        $pdf->Cell(80, 7, $item['name'] ?? '', 'LR', 0, 'L', $fill);
        $pdf->Cell(25, 7, (string) ($item['quantity'] ?? 0), 'LR', 0, 'C', $fill);
        $pdf->Cell(30, 7, number_format((float) ($item['unit_price'] ?? 0), 2, ',', '.') . ' EUR', 'LR', 0, 'R', $fill);
        $pdf->Cell(35, 7, number_format((float) ($item['total'] ?? 0), 2, ',', '.') . ' EUR', 'LR', 1, 'R', $fill);
        $fill = !$fill;
      }

      // Linea cierre tabla.
      $pdf->Cell(170, 0, '', 'T');
      $pdf->Ln(5);

      // Totales.
      $pdf->SetFont('helvetica', '', 10);
      $pdf->Cell(135, 7, '', 0, 0);
      $pdf->Cell(0, 7, 'Subtotal: ' . number_format((float) ($data['subtotal'] ?? 0), 2, ',', '.') . ' EUR', 0, 1, 'R');

      $taxRate = $data['tax_rate'] ?? 0;
      $pdf->Cell(135, 7, '', 0, 0);
      $pdf->Cell(0, 7, 'IVA (' . $taxRate . '%): ' . number_format((float) ($data['tax_amount'] ?? 0), 2, ',', '.') . ' EUR', 0, 1, 'R');

      $pdf->SetFont('helvetica', 'B', 12);
      $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
      $pdf->Cell(135, 9, '', 0, 0);
      $pdf->Cell(0, 9, 'TOTAL: ' . number_format((float) ($data['total'] ?? 0), 2, ',', '.') . ' EUR', 0, 1, 'R');

      // Notas.
      if (!empty($data['notes'])) {
        $pdf->Ln(8);
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell(0, 5, 'Notas: ' . $data['notes'], 0, 'L');
      }

      // Pie de pagina.
      $this->applyBrandFooter($pdf, $brand);

      // Guardar.
      $safeNumber = preg_replace('/[^a-zA-Z0-9\-]/', '', $invoiceNumber);
      $directory = 'private://invoices/' . date('Y/m');

      return $this->savePdf($pdf, $directory, 'invoice-' . $safeNumber . '-' . time() . '.pdf');
    }
    catch (\Throwable $e) {
      $this->logger->error('Error al generar factura PDF: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Genera un PDF de certificado con la marca del tenant.
   *
   * @param array $data
   *   Datos del certificado con las claves:
   *   - title: (string) Titulo del certificado.
   *   - recipient_name: (string) Nombre del destinatario.
   *   - description: (string) Descripcion o motivo del certificado.
   *   - date: (string) Fecha de emision.
   *   - certificate_id: (string) Identificador unico del certificado.
   *   - issuer_name: (string) Nombre del emisor.
   * @param int|null $tenantId
   *   ID del tenant para obtener la marca. NULL usa los valores por defecto.
   *
   * @return string|null
   *   URI del archivo PDF generado (private://certificates/...) o NULL si falla.
   */
  public function generateCertificate(array $data, ?int $tenantId = NULL): ?string {
    try {
      $brand = $this->getBrandConfig($tenantId);
      $pdf = $this->createPdfInstance('L');

      // Metadatos.
      $certId = $data['certificate_id'] ?? 'CERT-' . time();
      $pdf->SetCreator('Ecosistema Jaraba');
      $pdf->SetAuthor($data['issuer_name'] ?? 'Ecosistema Jaraba');
      $pdf->SetTitle($data['title'] ?? 'Certificado');
      $pdf->SetSubject('Certificado emitido por Ecosistema Jaraba');

      $pdf->SetMargins(25, 20, 25);
      $pdf->SetAutoPageBreak(FALSE);
      $pdf->SetPrintHeader(FALSE);
      $pdf->SetPrintFooter(FALSE);
      $pdf->AddPage();

      $primaryRgb = $this->hexToRgb($brand['color_primary']);
      $secondaryRgb = $this->hexToRgb($brand['color_secondary']);
      $accentRgb = $this->hexToRgb($brand['color_accent']);

      // Borde decorativo exterior.
      $pdf->SetDrawColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
      $pdf->SetLineWidth(1.5);
      $pdf->Rect(10, 10, 277, 190);

      // Borde interior sutil.
      $pdf->SetDrawColor($secondaryRgb[0], $secondaryRgb[1], $secondaryRgb[2]);
      $pdf->SetLineWidth(0.3);
      $pdf->Rect(14, 14, 269, 182);

      // Logo si existe.
      if (!empty($brand['logo_path']) && file_exists($brand['logo_path'])) {
        $pdf->Image($brand['logo_path'], 125, 20, 47, 0, '', '', 'T', TRUE, 300, 'C');
        $pdf->SetY(45);
      }
      else {
        $pdf->SetY(30);
      }

      // Barra de color superior decorativa.
      $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
      $pdf->Rect(25, 18, 247, 3, 'F');

      // Titulo del certificado.
      $pdf->SetFont('helvetica', 'B', 28);
      $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
      $pdf->Cell(0, 15, mb_strtoupper($data['title'] ?? 'CERTIFICADO'), 0, 1, 'C');

      // Linea decorativa bajo el titulo.
      $pdf->SetDrawColor($secondaryRgb[0], $secondaryRgb[1], $secondaryRgb[2]);
      $pdf->SetLineWidth(0.8);
      $y = $pdf->GetY() + 2;
      $pdf->Line(90, $y, 207, $y);
      $pdf->Ln(10);

      // Texto introductorio.
      $pdf->SetFont('helvetica', '', 13);
      $pdf->SetTextColor(80, 80, 80);
      $pdf->Cell(0, 10, 'Se certifica que', 0, 1, 'C');

      // Nombre del destinatario.
      $pdf->Ln(3);
      $pdf->SetFont('helvetica', 'B', 26);
      $pdf->SetTextColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
      $pdf->Cell(0, 14, $data['recipient_name'] ?? '', 0, 1, 'C');

      // Descripcion.
      $pdf->Ln(5);
      $pdf->SetFont('helvetica', '', 12);
      $pdf->SetTextColor(60, 60, 60);
      $pdf->MultiCell(200, 7, $data['description'] ?? '', 0, 'C', FALSE, 1, 48);

      // Fecha e ID del certificado.
      $pdf->Ln(10);
      $pdf->SetFont('helvetica', '', 10);
      $pdf->SetTextColor(100, 100, 100);
      $pdf->Cell(0, 7, 'Fecha de emision: ' . ($data['date'] ?? date('d/m/Y')), 0, 1, 'C');
      $pdf->Cell(0, 7, 'ID: ' . $certId, 0, 1, 'C');

      // Nombre del emisor.
      $pdf->Ln(5);
      $pdf->SetFont('helvetica', 'B', 11);
      $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
      $pdf->Cell(0, 7, $data['issuer_name'] ?? 'Ecosistema Jaraba', 0, 1, 'C');

      // Barra inferior decorativa.
      $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
      $pdf->Rect(25, 189, 247, 3, 'F');

      // Guardar.
      $safeId = preg_replace('/[^a-zA-Z0-9\-]/', '', $certId);
      $directory = 'private://certificates/' . date('Y/m');

      return $this->savePdf($pdf, $directory, 'cert-' . $safeId . '-' . time() . '.pdf');
    }
    catch (\Throwable $e) {
      $this->logger->error('Error al generar certificado PDF: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Genera un PDF de informe con la marca del tenant.
   *
   * @param array $data
   *   Datos del informe con las claves:
   *   - title: (string) Titulo del informe.
   *   - sections: (array) Secciones del informe, cada una con:
   *     - title: (string) Titulo de la seccion.
   *     - content: (string) Contenido textual.
   *   - date: (string) Fecha del informe.
   *   - author: (string) Nombre del autor.
   * @param int|null $tenantId
   *   ID del tenant para obtener la marca. NULL usa los valores por defecto.
   *
   * @return string|null
   *   URI del archivo PDF generado (private://reports/...) o NULL si falla.
   */
  public function generateReport(array $data, ?int $tenantId = NULL): ?string {
    try {
      $brand = $this->getBrandConfig($tenantId);
      $pdf = $this->createPdfInstance();

      // Metadatos.
      $pdf->SetCreator('Ecosistema Jaraba');
      $pdf->SetAuthor($data['author'] ?? 'Ecosistema Jaraba');
      $pdf->SetTitle($data['title'] ?? 'Informe');
      $pdf->SetSubject('Informe generado por Ecosistema Jaraba');

      $pdf->SetMargins(20, 15, 20);
      $pdf->SetAutoPageBreak(TRUE, 30);
      $pdf->SetPrintHeader(FALSE);
      $pdf->SetPrintFooter(FALSE);
      $pdf->AddPage();

      $primaryRgb = $this->hexToRgb($brand['color_primary']);
      $secondaryRgb = $this->hexToRgb($brand['color_secondary']);

      // Cabecera con marca.
      $this->applyBrandHeader($pdf, $brand, $data['title'] ?? 'INFORME');

      // Autor y fecha.
      $pdf->Ln(3);
      $pdf->SetFont('helvetica', '', 10);
      $pdf->SetTextColor(100, 100, 100);

      $meta = [];
      if (!empty($data['author'])) {
        $meta[] = 'Autor: ' . $data['author'];
      }
      $meta[] = 'Fecha: ' . ($data['date'] ?? date('d/m/Y'));
      $pdf->Cell(0, 7, implode('  |  ', $meta), 0, 1, 'L');

      // Linea separadora.
      $pdf->SetDrawColor($secondaryRgb[0], $secondaryRgb[1], $secondaryRgb[2]);
      $pdf->SetLineWidth(0.3);
      $pdf->Line(20, $pdf->GetY() + 2, 190, $pdf->GetY() + 2);
      $pdf->Ln(8);

      // Secciones del informe.
      $sections = $data['sections'] ?? [];
      foreach ($sections as $index => $section) {
        // Titulo de seccion.
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);

        $sectionNumber = $index + 1;
        $pdf->Cell(0, 9, $sectionNumber . '. ' . ($section['title'] ?? ''), 0, 1, 'L');

        // Pequena linea de acento debajo del titulo de seccion.
        $pdf->SetDrawColor($secondaryRgb[0], $secondaryRgb[1], $secondaryRgb[2]);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(20, $pdf->GetY(), 60, $pdf->GetY());
        $pdf->Ln(3);

        // Contenido de seccion.
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->MultiCell(0, 6, $section['content'] ?? '', 0, 'J');
        $pdf->Ln(6);
      }

      // Pie de pagina.
      $this->applyBrandFooter($pdf, $brand);

      // Guardar.
      $directory = 'private://reports/' . date('Y/m');

      return $this->savePdf($pdf, $directory, 'report-' . time() . '.pdf');
    }
    catch (\Throwable $e) {
      $this->logger->error('Error al generar informe PDF: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene la configuracion de marca del tenant.
   *
   * Consulta el TenantThemeConfig del modulo jaraba_theming para extraer
   * colores, logo y tipografias. Si no hay configuracion o el modulo no
   * esta disponible, retorna valores por defecto de la plataforma.
   *
   * @param int|null $tenantId
   *   ID del tenant. NULL retorna los valores por defecto de plataforma.
   *
   * @return array
   *   Array con las claves:
   *   - color_primary: (string) Color principal en hex.
   *   - color_secondary: (string) Color secundario en hex.
   *   - color_accent: (string) Color de acento en hex.
   *   - logo_path: (string|null) Ruta real al archivo de logo, o NULL.
   *   - font_heading: (string) Nombre de la fuente para titulos.
   *   - font_body: (string) Nombre de la fuente para cuerpo de texto.
   */
  protected function getBrandConfig(?int $tenantId): array {
    // Valores por defecto de la plataforma.
    $defaults = [
      'color_primary' => '#FF8C42',
      'color_secondary' => '#00A9A5',
      'color_accent' => '#233D63',
      'logo_path' => NULL,
      'font_heading' => 'Outfit',
      'font_body' => 'Inter',
    ];

    try {
      // Intentar cargar ThemeTokenService si esta disponible.
      if (!\Drupal::hasService('jaraba_theming.theme_token')) {
        return $defaults;
      }

      /** @var \Drupal\jaraba_theming\Service\ThemeTokenService $themeTokenService */
      $themeTokenService = \Drupal::service('jaraba_theming.theme_token');
      $config = $themeTokenService->getActiveConfig($tenantId);

      if (!$config) {
        return $defaults;
      }

      // Extraer colores.
      $brand = [
        'color_primary' => $config->get('color_primary')->value ?: $defaults['color_primary'],
        'color_secondary' => $config->get('color_secondary')->value ?: $defaults['color_secondary'],
        'color_accent' => $config->get('color_accent')->value ?: $defaults['color_accent'],
        'logo_path' => NULL,
        'font_heading' => $config->get('font_headings')->value ?: $defaults['font_heading'],
        'font_body' => $config->get('font_body')->value ?: $defaults['font_body'],
      ];

      // Extraer ruta del logo.
      if ($config->hasField('logo') && !$config->get('logo')->isEmpty()) {
        $logoEntity = $config->get('logo')->entity;
        if ($logoEntity) {
          $logoUri = $logoEntity->getFileUri();
          $realPath = $this->fileSystem->realpath($logoUri);
          if ($realPath && file_exists($realPath)) {
            $brand['logo_path'] = $realPath;
          }
        }
      }

      return $brand;
    }
    catch (\Throwable $e) {
      $this->logger->warning('No se pudo cargar la marca del tenant @id: @message', [
        '@id' => $tenantId ?? 'null',
        '@message' => $e->getMessage(),
      ]);
      return $defaults;
    }
  }

  /**
   * Aplica la cabecera de marca a una pagina del PDF.
   *
   * Dibuja una barra de color superior, coloca el logo (si existe)
   * y escribe el titulo del documento con los colores de la marca.
   *
   * @param \TCPDF $pdf
   *   Instancia de TCPDF sobre la que dibujar.
   * @param array $brand
   *   Configuracion de marca obtenida de getBrandConfig().
   * @param string $title
   *   Titulo del documento a mostrar en la cabecera.
   */
  protected function applyBrandHeader(TCPDF $pdf, array $brand, string $title): void {
    $primaryRgb = $this->hexToRgb($brand['color_primary']);
    $secondaryRgb = $this->hexToRgb($brand['color_secondary']);

    // Barra de color superior.
    $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    $pdf->Rect(0, 0, 210, 6, 'F');

    // Logo y titulo.
    $pdf->SetY(10);

    if (!empty($brand['logo_path']) && file_exists($brand['logo_path'])) {
      $pdf->Image($brand['logo_path'], 20, 10, 40, 0, '', '', 'T', TRUE, 300, 'L');
      // Titulo a la derecha del logo.
      $pdf->SetY(12);
      $pdf->SetFont('helvetica', 'B', 20);
      $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
      $pdf->Cell(0, 12, mb_strtoupper($title), 0, 1, 'R');
      $pdf->SetY(30);
    }
    else {
      // Sin logo: titulo centrado.
      $pdf->SetFont('helvetica', 'B', 22);
      $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
      $pdf->Cell(0, 14, mb_strtoupper($title), 0, 1, 'C');

      // Linea decorativa.
      $pdf->SetDrawColor($secondaryRgb[0], $secondaryRgb[1], $secondaryRgb[2]);
      $pdf->SetLineWidth(0.5);
      $y = $pdf->GetY() + 2;
      $pdf->Line(60, $y, 150, $y);
      $pdf->Ln(8);
    }
  }

  /**
   * Aplica el pie de pagina de marca al PDF.
   *
   * Dibuja una linea de color, el copyright y el numero de pagina
   * en la parte inferior de la pagina actual.
   *
   * @param \TCPDF $pdf
   *   Instancia de TCPDF sobre la que dibujar.
   * @param array $brand
   *   Configuracion de marca obtenida de getBrandConfig().
   */
  protected function applyBrandFooter(TCPDF $pdf, array $brand): void {
    $primaryRgb = $this->hexToRgb($brand['color_primary']);
    $pageHeight = $pdf->getPageHeight();

    // Posicionar en la zona del footer.
    $pdf->SetY($pageHeight - 25);

    // Linea de color.
    $pdf->SetDrawColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());

    $pdf->Ln(3);

    // Copyright.
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(120, 120, 120);
    $year = date('Y');
    $pdf->Cell(0, 4, 'Ecosistema Jaraba ' . $year . ' - Documento generado automaticamente', 0, 1, 'C');

    // Numero de pagina.
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(150, 150, 150);
    $pageNum = $pdf->getAliasNumPage();
    $totalPages = $pdf->getAliasNbPages();
    $pdf->Cell(0, 4, 'Pagina ' . $pageNum . ' de ' . $totalPages, 0, 1, 'C');
  }

  /**
   * Crea una instancia base de TCPDF preconfigurada.
   *
   * @param string $orientation
   *   Orientacion del documento: 'P' (vertical) o 'L' (horizontal).
   *
   * @return \TCPDF
   *   Instancia de TCPDF lista para usar.
   */
  protected function createPdfInstance(string $orientation = 'P'): TCPDF {
    return new TCPDF($orientation, 'mm', 'A4', TRUE, 'UTF-8', FALSE);
  }

  /**
   * Guarda un PDF en el sistema de archivos privado.
   *
   * Crea el directorio si no existe y escribe el archivo. Registra
   * el resultado en el log del modulo.
   *
   * @param \TCPDF $pdf
   *   Instancia de TCPDF con el contenido renderizado.
   * @param string $directory
   *   URI del directorio destino (ej. private://invoices/2026/02).
   * @param string $filename
   *   Nombre del archivo PDF.
   *
   * @return string|null
   *   URI completa del archivo generado, o NULL si no se pudo guardar.
   */
  protected function savePdf(TCPDF $pdf, string $directory, string $filename): ?string {
    $this->fileSystem->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    );

    $uri = $directory . '/' . $filename;
    $realPath = $this->fileSystem->realpath($directory);

    if (!$realPath) {
      $this->logger->error('No se pudo resolver la ruta del directorio: @dir', [
        '@dir' => $directory,
      ]);
      return NULL;
    }

    $fullPath = $realPath . '/' . $filename;
    $pdf->Output($fullPath, 'F');

    if (file_exists($fullPath)) {
      $this->logger->info('PDF generado correctamente: @uri', ['@uri' => $uri]);
      return $uri;
    }

    $this->logger->error('El archivo PDF no se genero en la ruta esperada: @path', [
      '@path' => $fullPath,
    ]);
    return NULL;
  }

  /**
   * Convierte un color hexadecimal a array RGB.
   *
   * @param string $hex
   *   Color en formato hex (#RRGGBB o RRGGBB).
   *
   * @return array
   *   Array con tres enteros [R, G, B] en rango 0-255.
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
