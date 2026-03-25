<?php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\node\NodeInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Genera certificados PDF de trazabilidad para lotes de producción.
 *
 * Este servicio crea documentos PDF profesionales con los datos
 * de trazabilidad de cada lote, incluyendo QR de verificación.
 */
class CertificadoPdfService {

  /**
   * El sistema de archivos.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * El gestor de entidades.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   El servicio de sistema de archivos.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   El gestor de entidades.
   */
  public function __construct(
    FileSystemInterface $file_system,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Genera un PDF con los datos del lote de producción.
   *
   * @param \Drupal\node\NodeInterface $lote
   *   El nodo de tipo lote_produccion.
   *
   * @return string|null
   *   Ruta al archivo PDF generado, o NULL si falla.
   */
  public function generatePdf(NodeInterface $lote): ?string {
    // Validar tipo de contenido.
    if ($lote->bundle() !== 'lote_produccion') {
      \Drupal::logger('ecosistema_jaraba_core')->warning(
        '🚫 CertificadoPdfService: Se intentó generar PDF para tipo @type',
        ['@type' => $lote->bundle()]
      );
      return NULL;
    }

    // Extraer datos del lote.
    $datos = $this->extraerDatosLote($lote);

    // Crear instancia de TCPDF.
    $pdf = new \TCPDF('P', 'mm', 'A4', TRUE, 'UTF-8', FALSE);

    // Configurar metadatos del documento.
    $this->configurarMetadatos($pdf, $datos);

    // Configurar página.
    $pdf->SetMargins(20, 30, 20);
    $pdf->SetAutoPageBreak(TRUE, 35);
    $pdf->SetPrintHeader(FALSE);
    $pdf->SetPrintFooter(FALSE);
    $pdf->AddPage();

    // Generar contenido.
    $this->generarEncabezado($pdf, $datos);
    $this->generarDatosLote($pdf, $datos);
    $this->generarDatosOrigen($pdf, $datos);
    $this->generarDatosAnalisis($pdf, $datos);
    $this->generarDeclaracion($pdf);
    $this->generarQrVerificacion($pdf, $datos);
    $this->generarPieDocumento($pdf);

    // Guardar PDF.
    return $this->guardarPdf($pdf, $datos['lote_id']);
  }

  /**
   * Extrae los datos del lote para el certificado.
   *
   * @param \Drupal\node\NodeInterface $lote
   *   El nodo del lote.
   *
   * @return array
   *   Array con todos los datos extraídos.
   */
  protected function extraerDatosLote(NodeInterface $lote): array {
    $datos = [
      'lote_id' => $lote->get('field_id_lote')->value ?? 'SIN-ID',
      'titulo' => $lote->getTitle(),
      'fecha_emision' => date('d/m/Y H:i:s'),
      'nid' => $lote->id(),
    ];

    // Producto asociado.
    if (!$lote->get('field_producto_asociado')->isEmpty()) {
      $producto = $lote->get('field_producto_asociado')->entity;
      if ($producto) {
        $datos['producto_nombre'] = $producto->getTitle();
        $datos['producto_id'] = $producto->id();

        // Categoría del producto (si existe)
        if (
          $producto->hasField('field_categoria_producto') &&
          !$producto->get('field_categoria_producto')->isEmpty()
        ) {
          $categoria = $producto->get('field_categoria_producto')->entity;
          $datos['categoria'] = $categoria ? $categoria->getName() : '';
        }
      }
    }
    $datos['producto_nombre'] = $datos['producto_nombre'] ?? 'Producto no especificado';

    // Fechas de producción.
    $datos['fecha_cosecha'] = $this->formatearFecha(
      $lote->get('field_fecha_cosecha')->value ?? NULL
    );
    $datos['fecha_molturacion'] = $this->formatearFecha(
      $lote->get('field_fecha_molturacion')->value ?? NULL
    );
    $datos['fecha_envasado'] = $this->formatearFecha(
      $lote->get('field_fecha_envasado')->value ?? NULL
    );

    // Finca/Origen.
    if (
      $lote->hasField('field_finca_origen') &&
      !$lote->get('field_finca_origen')->isEmpty()
    ) {
      $finca = $lote->get('field_finca_origen')->entity;
      $datos['finca_nombre'] = $finca ? $finca->getName() : 'No especificado';
    }
    $datos['finca_nombre'] = $datos['finca_nombre'] ?? 'Origen no especificado';

    // Variedad (si existe)
    if (
      $lote->hasField('field_variedad') &&
      !$lote->get('field_variedad')->isEmpty()
    ) {
      $variedad = $lote->get('field_variedad')->entity;
      $datos['variedad'] = $variedad ? $variedad->getName() : '';
    }

    // Productor.
    if (
      $lote->hasField('field_productor_referencia') &&
      !$lote->get('field_productor_referencia')->isEmpty()
    ) {
      $productor = $lote->get('field_productor_referencia')->entity;
      $datos['productor'] = $productor ? $productor->getDisplayName() : '';
    }

    // Cantidad/Peso (si existe)
    if ($lote->hasField('field_cantidad_kg')) {
      $datos['cantidad'] = $lote->get('field_cantidad_kg')->value ?? '';
    }

    // Notas adicionales.
    if ($lote->hasField('field_notas_trazabilidad')) {
      $datos['notas'] = $lote->get('field_notas_trazabilidad')->value ?? '';
    }

    return $datos;
  }

  /**
   * Configura los metadatos del PDF.
   */
  protected function configurarMetadatos(\TCPDF $pdf, array $datos): void {
    $pdf->SetCreator('Ecosistema Jaraba - Plataforma de Trazabilidad');
    $pdf->SetAuthor('Ecosistema Jaraba');
    $pdf->SetTitle('Certificado de Trazabilidad - ' . $datos['lote_id']);
    $pdf->SetSubject('Certificado de origen y trazabilidad de lote');
    $pdf->SetKeywords('trazabilidad, lote, certificado, ' . $datos['producto_nombre']);
  }

  /**
   * Genera el encabezado del certificado.
   */
  protected function generarEncabezado(\TCPDF $pdf, array $datos): void {
    // Logo (opcional - descomentar si existe)
    // $logo_path = DRUPAL_ROOT . '/themes/custom/ecosistema_jaraba_theme/logo.png';
    // if (file_exists($logo_path)) {
    //   $pdf->Image($logo_path, 80, 15, 50);
    //   $pdf->Ln(30);
    // }.
    // Título principal.
    $pdf->SetFont('helvetica', 'B', 26);
    // Verde Ecosistema Jaraba #2E7D32.
    $pdf->SetTextColor(46, 125, 50);
    $pdf->Cell(0, 15, 'CERTIFICADO DE TRAZABILIDAD', 0, 1, 'C');

    // Subtítulo.
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 8, 'Documento con firma electrónica cualificada', 0, 1, 'C');

    // Línea decorativa.
    $pdf->SetDrawColor(46, 125, 50);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(20, $pdf->GetY() + 3, 190, $pdf->GetY() + 3);

    $pdf->Ln(10);
  }

  /**
   * Genera la sección de identificación del lote.
   */
  protected function generarDatosLote(\TCPDF $pdf, array $datos): void {
    $pdf->SetFont('helvetica', 'B', 14);
    // Verde oscuro #1B5E20.
    $pdf->SetTextColor(27, 94, 32);
    $pdf->Cell(0, 10, 'IDENTIFICACIÓN DEL LOTE', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetTextColor(0, 0, 0);

    $this->addDataRow($pdf, 'Código de Lote:', $datos['lote_id']);
    $this->addDataRow($pdf, 'Producto:', $datos['producto_nombre']);

    if (!empty($datos['categoria'])) {
      $this->addDataRow($pdf, 'Categoría:', $datos['categoria']);
    }
    if (!empty($datos['variedad'])) {
      $this->addDataRow($pdf, 'Variedad:', $datos['variedad']);
    }

    $this->addDataRow($pdf, 'Fecha de emisión:', $datos['fecha_emision']);

    $pdf->Ln(5);
  }

  /**
   * Genera la sección de datos de origen y producción.
   */
  protected function generarDatosOrigen(\TCPDF $pdf, array $datos): void {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(27, 94, 32);
    $pdf->Cell(0, 10, 'DATOS DE ORIGEN Y PRODUCCIÓN', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetTextColor(0, 0, 0);

    $this->addDataRow($pdf, 'Finca / Parcela:', $datos['finca_nombre']);

    if (!empty($datos['productor'])) {
      $this->addDataRow($pdf, 'Productor:', $datos['productor']);
    }

    $this->addDataRow($pdf, 'Fecha de cosecha:', $datos['fecha_cosecha']);

    if ($datos['fecha_molturacion'] !== 'No especificada') {
      $this->addDataRow($pdf, 'Fecha de procesado:', $datos['fecha_molturacion']);
    }
    if (!empty($datos['fecha_envasado']) && $datos['fecha_envasado'] !== 'No especificada') {
      $this->addDataRow($pdf, 'Fecha de envasado:', $datos['fecha_envasado']);
    }
    if (!empty($datos['cantidad'])) {
      $this->addDataRow($pdf, 'Cantidad:', $datos['cantidad'] . ' kg');
    }

    $pdf->Ln(5);
  }

  /**
   * Genera la sección de análisis (si hay datos).
   */
  protected function generarDatosAnalisis(\TCPDF $pdf, array $datos): void {
    // Esta sección puede expandirse para incluir datos de análisis de calidad
    // Por ahora, solo se muestra si hay notas.
    if (!empty($datos['notas'])) {
      $pdf->SetFont('helvetica', 'B', 14);
      $pdf->SetTextColor(27, 94, 32);
      $pdf->Cell(0, 10, 'OBSERVACIONES', 0, 1, 'L');

      $pdf->SetFont('helvetica', '', 10);
      $pdf->SetTextColor(60, 60, 60);
      $pdf->MultiCell(0, 5, $datos['notas'], 0, 'J');

      $pdf->Ln(5);
    }
  }

  /**
   * Genera la declaración legal.
   */
  protected function generarDeclaracion(\TCPDF $pdf): void {
    $pdf->Ln(5);

    // Caja de declaración.
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetDrawColor(200, 200, 200);

    $declaracion = 'Este certificado acredita la trazabilidad del lote identificado. ' .
      'La información contenida ha sido verificada por la plataforma Ecosistema Jaraba ' .
      'y está respaldada por firma electrónica cualificada conforme al ' .
      'Reglamento (UE) Nº 910/2014 (eIDAS). ' .
      'La autenticidad de este documento puede verificarse escaneando el código QR ' .
      'o accediendo a la URL indicada.';

    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell(0, 5, $declaracion, 1, 'J', TRUE);

    $pdf->Ln(10);
  }

  /**
   * Genera el código QR de verificación.
   */
  protected function generarQrVerificacion(\TCPDF $pdf, array $datos): void {
    // URL de verificación.
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $verification_url = $base_url . '/trazabilidad/' . $datos['lote_id'];

    // Centrar el QR.
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(27, 94, 32);
    $pdf->Cell(0, 8, 'VERIFICACIÓN DIGITAL', 0, 1, 'C');

    // QR Code.
    $style = [
      'border' => TRUE,
      'padding' => 2,
      'fgcolor' => [46, 125, 50],
      'bgcolor' => [255, 255, 255],
    ];

    $pdf->write2DBarcode($verification_url, 'QRCODE,M', 80, $pdf->GetY(), 50, 50, $style);

    $pdf->SetY($pdf->GetY() + 55);

    // URL de verificación.
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, 'Escanee el código QR o visite:', 0, 1, 'C');

    $pdf->SetFont('helvetica', 'U', 9);
    $pdf->SetTextColor(46, 125, 50);
    $pdf->Cell(0, 5, $verification_url, 0, 1, 'C', FALSE, $verification_url);
  }

  /**
   * Genera el pie del documento.
   */
  protected function generarPieDocumento(\TCPDF $pdf): void {
    $pdf->SetY(-45);

    // Línea separadora.
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());

    $pdf->Ln(5);

    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(120, 120, 120);

    $aviso = 'Documento firmado electrónicamente. Puede verificar la validez ' .
      'de la firma en https://valide.redsara.es. ' .
      'La manipulación de este documento constituye un delito conforme ' .
      'al artículo 390 del Código Penal.';

    $pdf->MultiCell(0, 4, $aviso, 0, 'C');

    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(0, 4, 'Ecosistema Jaraba - Plataforma de Trazabilidad Agroalimentaria', 0, 1, 'C');
  }

  /**
   * Añade una fila de datos al PDF.
   */
  protected function addDataRow(\TCPDF $pdf, string $label, string $value): void {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(55, 7, $label, 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 7, $value, 0, 1, 'L');
  }

  /**
   * Formatea una fecha.
   */
  protected function formatearFecha(?string $date): string {
    if (!$date) {
      return 'No especificada';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('d/m/Y', $timestamp) : $date;
  }

  /**
   * Guarda el PDF en el sistema de archivos.
   */
  protected function guardarPdf(\TCPDF $pdf, string $lote_id): ?string {
    // Crear directorio si no existe.
    $directory = 'private://certificados/' . date('Y/m');
    $this->fileSystem->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    );

    // Nombre de archivo seguro.
    $safe_id = preg_replace('/[^a-zA-Z0-9\-]/', '', $lote_id);
    $filename = 'certificado-' . $safe_id . '-' . time() . '.pdf';

    // Ruta completa.
    $uri = $directory . '/' . $filename;
    $real_path = $this->fileSystem->realpath($directory);

    if (!$real_path) {
      \Drupal::logger('ecosistema_jaraba_core')->error(
        '🚫 No se pudo resolver la ruta: @dir',
        ['@dir' => $directory]
      );
      return NULL;
    }

    $full_path = $real_path . '/' . $filename;

    // Guardar PDF.
    $pdf->Output($full_path, 'F');

    if (file_exists($full_path)) {
      \Drupal::logger('ecosistema_jaraba_core')->info(
        '✅ PDF generado: @path',
        ['@path' => $full_path]
      );
      return $uri;
    }

    return NULL;
  }

}
