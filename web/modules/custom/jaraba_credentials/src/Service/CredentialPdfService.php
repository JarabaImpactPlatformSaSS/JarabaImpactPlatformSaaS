<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\jaraba_credentials\Entity\IssuedCredential;
use Drupal\jaraba_credentials\Entity\CredentialTemplate;
use TCPDF;

/**
 * Genera certificados PDF para credenciales digitales.
 *
 * Este servicio crea documentos PDF profesionales con diseño premium
 * para las credenciales Open Badge 3.0, incluyendo QR de verificación.
 */
class CredentialPdfService
{

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
        EntityTypeManagerInterface $entity_type_manager
    ) {
        $this->fileSystem = $file_system;
        $this->entityTypeManager = $entity_type_manager;
    }

    /**
     * Genera un PDF con los datos de la credencial.
     *
     * @param \Drupal\jaraba_credentials\Entity\IssuedCredential $credential
     *   La credencial emitida.
     *
     * @return string|null
     *   URI del archivo PDF generado, o NULL si falla.
     */
    public function generatePdf(IssuedCredential $credential): ?string
    {
        // Obtener template asociado.
        $template = $this->getTemplate($credential);

        // Extraer datos de la credencial.
        $datos = $this->extraerDatosCredencial($credential, $template);

        // Crear instancia de TCPDF en orientación horizontal para certificado.
        $pdf = new TCPDF('L', 'mm', 'A4', TRUE, 'UTF-8', FALSE);

        // Configurar metadatos del documento.
        $this->configurarMetadatos($pdf, $datos);

        // Configurar página.
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(FALSE);
        $pdf->SetPrintHeader(FALSE);
        $pdf->SetPrintFooter(FALSE);
        $pdf->AddPage();

        // Generar contenido del certificado.
        $this->generarFondo($pdf);
        $this->generarEncabezado($pdf, $datos);
        $this->generarCuerpo($pdf, $datos);
        $this->generarQrVerificacion($pdf, $datos);
        $this->generarPieDocumento($pdf, $datos);

        // Guardar PDF.
        return $this->guardarPdf($pdf, $credential);
    }

    /**
     * Obtiene el template asociado a la credencial.
     */
    protected function getTemplate(IssuedCredential $credential): ?CredentialTemplate
    {
        // El campo se llama 'template_id' en la entidad IssuedCredential.
        if ($credential->hasField('template_id') && !$credential->get('template_id')->isEmpty()) {
            return $credential->get('template_id')->entity;
        }
        return NULL;
    }

    /**
     * Extrae los datos de la credencial para el certificado.
     */
    protected function extraerDatosCredencial(IssuedCredential $credential, ?CredentialTemplate $template): array
    {
        // Datos básicos.
        $datos = [
            'credential_id' => $credential->get('credential_id_uri')->value ?? 'SIN-ID',
            'uuid' => $credential->uuid(),
            'fecha_emision' => $this->formatearFecha($credential->get('issued_on')->value),
            'fecha_expiracion' => $this->formatearFecha($credential->get('expires_on')->value ?? NULL),
        ];

        // Nombre del certificado.
        if ($template) {
            $datos['nombre_credencial'] = $template->get('name')->value;
            $datos['descripcion'] = $template->get('description')->value ?? '';
            $datos['criteria'] = $template->get('criteria')->value ?? '';
        } else {
            $datos['nombre_credencial'] = 'Credencial Digital';
            $datos['descripcion'] = '';
            $datos['criteria'] = '';
        }

        // Datos del receptor - usar recipient_id (entity_reference) o recipient_name (string).
        if ($credential->hasField('recipient_id') && !$credential->get('recipient_id')->isEmpty()) {
            $recipient = $credential->get('recipient_id')->entity;
            if ($recipient) {
                $datos['receptor_nombre'] = $recipient->getDisplayName();
                $datos['receptor_email'] = $recipient->getEmail();
            } else {
                // Fallback a recipient_name si el usuario fue eliminado.
                $datos['receptor_nombre'] = $credential->get('recipient_name')->value ?? 'Destinatario';
                $datos['receptor_email'] = $credential->get('recipient_email')->value ?? '';
            }
        } else {
            $datos['receptor_nombre'] = $credential->get('recipient_name')->value ?? 'Destinatario';
            $datos['receptor_email'] = $credential->get('recipient_email')->value ?? '';
        }

        // Datos del emisor - obtener del template si existe, sino valores por defecto.
        if ($template && $template->hasField('issuer') && !$template->get('issuer')->isEmpty()) {
            $issuer = $template->get('issuer')->entity;
            if ($issuer) {
                $datos['emisor_nombre'] = $issuer->get('name')->value ?? 'Jaraba Impact Platform';
                $datos['emisor_url'] = $issuer->get('url')->value ?? '';
            } else {
                $datos['emisor_nombre'] = 'Jaraba Impact Platform';
                $datos['emisor_url'] = '';
            }
        } else {
            $datos['emisor_nombre'] = 'Jaraba Impact Platform';
            $datos['emisor_url'] = '';
        }

        // URL de verificación.
        $base_url = \Drupal::request()->getSchemeAndHttpHost();
        $datos['verification_url'] = $base_url . '/verify/' . $credential->uuid();

        return $datos;
    }

    /**
     * Configura los metadatos del PDF.
     */
    protected function configurarMetadatos(TCPDF $pdf, array $datos): void
    {
        $pdf->SetCreator('Jaraba Impact Platform');
        $pdf->SetAuthor($datos['emisor_nombre']);
        $pdf->SetTitle($datos['nombre_credencial'] . ' - ' . $datos['receptor_nombre']);
        $pdf->SetSubject('Credencial Digital Verificable');
        $pdf->SetKeywords('credencial, badge, certificado, ' . $datos['nombre_credencial']);
    }

    /**
     * Genera el fondo decorativo del certificado.
     */
    protected function generarFondo(TCPDF $pdf): void
    {
        // Fondo con gradiente simulado (rectángulo de color).
        $pdf->SetFillColor(35, 61, 99); // Color corporativo #233D63
        $pdf->Rect(0, 0, 297, 30, 'F');

        // Borde decorativo.
        $pdf->SetDrawColor(0, 169, 165); // Color teal #00A9A5
        $pdf->SetLineWidth(0.8);
        $pdf->Rect(10, 40, 277, 160, 'D');

        // Esquinas decorativas.
        $this->dibujarEsquinaDecorativa($pdf, 10, 40);
        $this->dibujarEsquinaDecorativa($pdf, 277, 40, TRUE);
        $this->dibujarEsquinaDecorativa($pdf, 10, 190, FALSE, TRUE);
        $this->dibujarEsquinaDecorativa($pdf, 277, 190, TRUE, TRUE);
    }

    /**
     * Dibuja una esquina decorativa.
     */
    protected function dibujarEsquinaDecorativa(TCPDF $pdf, float $x, float $y, bool $espejo_x = FALSE, bool $espejo_y = FALSE): void
    {
        $size = 15;
        $offset_x = $espejo_x ? -$size : 0;
        $offset_y = $espejo_y ? -$size : 0;

        $pdf->SetDrawColor(0, 169, 165);
        $pdf->SetLineWidth(1.5);

        // Línea horizontal.
        $pdf->Line($x + $offset_x, $y + $offset_y, $x + $offset_x + ($espejo_x ? -$size : $size), $y + $offset_y);
        // Línea vertical.
        $pdf->Line($x + $offset_x, $y + $offset_y, $x + $offset_x, $y + $offset_y + ($espejo_y ? -$size : $size));
    }

    /**
     * Inserta el logo del SaaS en el encabezado del PDF.
     *
     * Usa el icono principal del ecosistema y lo invierte a blanco
     * para que contraste con el fondo oscuro del header.
     */
    protected function insertarLogo(TCPDF $pdf): void
    {
        // Logo principal del ecosistema.
        $logo_path = DRUPAL_ROOT . '/sites/default/files/EcosistemaJaraba_icono_v6.png';

        if (!file_exists($logo_path)) {
            // Fallback si no existe.
            $logo_path = DRUPAL_ROOT . '/sites/default/files/EcosistemaJaraba_icono_v6_0.png';
        }

        if (!file_exists($logo_path)) {
            return;
        }

        // Crear versión en negativo (blanco) usando GD.
        $inverted_path = $this->crearLogoNegativo($logo_path);

        if ($inverted_path && file_exists($inverted_path)) {
            // Insertar logo invertido en el header (posición izquierda).
            // Tamaño: 14mm de alto, ancho proporcional.
            $pdf->Image($inverted_path, 12, 7, 0, 16, 'PNG', '', '', FALSE, 300);
        }
    }

    /**
     * Crea una versión en negativo (blanco) del logo usando GD.
     *
     * Aplica el equivalente del CSS filter: brightness(0) invert(1).
     * Convierte todos los píxeles coloreados a blanco y el fondo blanco a transparente.
     *
     * @param string $original_path
     *   Ruta al logo original.
     *
     * @return string|null
     *   Ruta al logo invertido temporal, o NULL si falla.
     */
    protected function crearLogoNegativo(string $original_path): ?string
    {
        if (!function_exists('imagecreatefrompng')) {
            return NULL;
        }

        $source = @imagecreatefrompng($original_path);
        if (!$source) {
            return NULL;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // Crear nueva imagen con soporte de transparencia.
        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, FALSE);
        imagesavealpha($result, TRUE);

        // Color transparente y blanco.
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        $white = imagecolorallocatealpha($result, 255, 255, 255, 0);

        // Rellenar con transparente.
        imagefill($result, 0, 0, $transparent);

        // Umbral para considerar un píxel como "blanco" (fondo).
        $white_threshold = 240;

        // Procesar cada pixel.
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($source, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $a = ($rgba >> 24) & 0x7F;

                // Si el píxel es transparente, dejarlo transparente.
                if ($a >= 120) {
                    continue;
                }

                // Si el píxel es blanco o casi blanco, hacerlo transparente.
                if ($r >= $white_threshold && $g >= $white_threshold && $b >= $white_threshold) {
                    continue;
                }

                // Es un píxel del logo (coloreado), convertir a blanco.
                $new_alpha = (int) ($a * 0.1); // Mantener un poco de alpha para suavizado.
                $white_with_alpha = imagecolorallocatealpha($result, 255, 255, 255, $new_alpha);
                imagesetpixel($result, $x, $y, $white_with_alpha);
            }
        }

        // Guardar en archivo temporal.
        $temp_path = sys_get_temp_dir() . '/jaraba_logo_white_' . md5($original_path) . '_v2.png';
        imagepng($result, $temp_path);
        imagedestroy($source);
        imagedestroy($result);

        return $temp_path;
    }



    /**
     * Genera el encabezado del certificado.
     */

    protected function generarEncabezado(TCPDF $pdf, array $datos): void
    {
        // Logo del SaaS en la barra superior (izquierda).
        $this->insertarLogo($pdf);

        // Título en la barra superior (después del logo).
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(30, 10);
        $pdf->Cell(0, 10, mb_strtoupper($datos['emisor_nombre']), 0, 0, 'L');

        // Icono/Badge (texto de badge estilizado).
        $pdf->SetTextColor(0, 169, 165);
        $pdf->SetFont('zapfdingbats', '', 36);
        $pdf->SetXY(140, 45);
        $pdf->Cell(0, 20, chr(74), 0, 0, 'C'); // Símbolo de estrella/badge.


        // Título principal.
        $pdf->SetTextColor(35, 61, 99);
        $pdf->SetFont('helvetica', 'B', 28);
        $pdf->SetXY(20, 70);
        $pdf->Cell(257, 15, 'CERTIFICADO DE CREDENCIAL', 0, 1, 'C');

        // Subtítulo.
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(257, 8, 'Credencial Digital Verificable bajo estándar Open Badge 3.0', 0, 1, 'C');
    }

    /**
     * Genera el cuerpo del certificado.
     */
    protected function generarCuerpo(TCPDF $pdf, array $datos): void
    {
        $pdf->Ln(5);

        // Texto de otorgamiento.
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->SetX(20);
        $pdf->Cell(257, 10, 'Se certifica que', 0, 1, 'C');

        // Nombre del receptor.
        $pdf->SetFont('helvetica', 'B', 22);
        $pdf->SetTextColor(35, 61, 99);
        $pdf->SetX(20);
        $pdf->Cell(257, 12, $datos['receptor_nombre'], 0, 1, 'C');

        // Texto intermedio.
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->SetX(20);
        $pdf->Cell(257, 10, 'ha obtenido la credencial:', 0, 1, 'C');

        // Nombre de la credencial.
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetTextColor(0, 169, 165);
        $pdf->SetX(20);
        $pdf->Cell(257, 12, $datos['nombre_credencial'], 0, 1, 'C');

        // Descripción (si existe).
        if (!empty($datos['descripcion'])) {
            $pdf->SetFont('helvetica', 'I', 11);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->SetX(40);
            $pdf->MultiCell(217, 6, $datos['descripcion'], 0, 'C');
        }

        // Fechas.
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX(20);
        $fecha_texto = 'Emitida el ' . $datos['fecha_emision'];
        if (!empty($datos['fecha_expiracion']) && $datos['fecha_expiracion'] !== 'Sin expiración') {
            $fecha_texto .= ' • Válida hasta ' . $datos['fecha_expiracion'];
        }
        $pdf->Cell(257, 8, $fecha_texto, 0, 1, 'C');
    }

    /**
     * Genera el código QR de verificación.
     */
    protected function generarQrVerificacion(TCPDF $pdf, array $datos): void
    {
        // QR Code a la derecha.
        $style = [
            'border' => FALSE,
            'padding' => 2,
            'fgcolor' => [35, 61, 99],
            'bgcolor' => [255, 255, 255],
        ];

        $pdf->write2DBarcode($datos['verification_url'], 'QRCODE,M', 235, 155, 35, 35, $style);

        // Texto bajo el QR.
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetXY(225, 192);
        $pdf->MultiCell(55, 4, 'Escanea para verificar', 0, 'C');
    }

    /**
     * Genera el pie del documento.
     */
    protected function generarPieDocumento(TCPDF $pdf, array $datos): void
    {
        // ID de credencial.
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetXY(20, 190);
        $pdf->Cell(100, 5, 'ID: ' . substr($datos['uuid'], 0, 8) . '...', 0, 0, 'L');

        // URL de verificación.
        $pdf->SetFont('helvetica', 'U', 9);
        $pdf->SetTextColor(0, 169, 165);
        $pdf->SetXY(20, 195);
        $pdf->Cell(200, 5, $datos['verification_url'], 0, 0, 'L', FALSE, $datos['verification_url']);
    }

    /**
     * Formatea una fecha.
     */
    protected function formatearFecha(?string $date): string
    {
        if (!$date) {
            return 'Sin expiración';
        }
        $timestamp = strtotime($date);
        return $timestamp ? date('d/m/Y', $timestamp) : $date;
    }

    /**
     * Guarda el PDF en el sistema de archivos.
     */
    protected function guardarPdf(TCPDF $pdf, IssuedCredential $credential): ?string
    {
        // Crear directorio si no existe.
        $directory = 'public://credentials/pdfs/' . date('Y/m');
        $this->fileSystem->prepareDirectory(
            $directory,
            FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
        );

        // Nombre de archivo seguro.
        $uuid = $credential->uuid();
        $filename = 'credential-' . $uuid . '.pdf';

        // Ruta completa.
        $uri = $directory . '/' . $filename;
        $real_path = $this->fileSystem->realpath($directory);

        if (!$real_path) {
            \Drupal::logger('jaraba_credentials')->error(
                '🚫 No se pudo resolver la ruta: @dir',
                ['@dir' => $directory]
            );
            return NULL;
        }

        $full_path = $real_path . '/' . $filename;

        // Guardar PDF.
        $pdf->Output($full_path, 'F');

        if (file_exists($full_path)) {
            \Drupal::logger('jaraba_credentials')->info(
                '✅ PDF de credencial generado: @path',
                ['@path' => $full_path]
            );
            return $uri;
        }

        return NULL;
    }

}
