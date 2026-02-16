<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord;
use Psr\Log\LoggerInterface;

/**
 * Servicio de generacion de QR de verificacion AEAT VeriFactu.
 *
 * Genera la URL de verificacion AEAT y el codigo QR que debe
 * imprimirse en la factura junto con la etiqueta VERI*FACTU
 * conforme a la Orden HAC/1177/2024.
 *
 * URL pattern:
 *   https://www2.agenciatributaria.gob.es/wlpl/TIKE-CONT/ValidarQR
 *   ?nif={NIF}&numserie={NUM}&fecha={FECHA}&importe={TOTAL}
 *
 * Spec: Doc 179, Seccion 3.3. Plan: FASE 2, entregable F2-3.
 */
class VeriFactuQrService {

  /**
   * AEAT production QR verification base URL.
   */
  const AEAT_QR_BASE_URL = 'https://www2.agenciatributaria.gob.es/wlpl/TIKE-CONT/ValidarQR';

  /**
   * Default QR image size in pixels.
   */
  const DEFAULT_QR_SIZE = 300;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Builds the AEAT verification URL for a VeriFactu record.
   *
   * @param \Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord $record
   *   The invoice record.
   *
   * @return string
   *   The full AEAT verification URL.
   */
  public function buildVerificationUrl(VeriFactuInvoiceRecord $record): string {
    $nif = $record->get('nif_emisor')->value;
    $numSerie = $record->get('numero_factura')->value;
    $fecha = $record->get('fecha_expedicion')->value;
    $importe = $record->get('importe_total')->value;

    // Format date as DD-MM-YYYY per AEAT specification.
    $fechaFormatted = $this->formatDateForAeat($fecha);

    // Format importe with 2 decimal places.
    $importeFormatted = number_format((float) $importe, 2, '.', '');

    $params = [
      'nif' => $nif,
      'numserie' => $numSerie,
      'fecha' => $fechaFormatted,
      'importe' => $importeFormatted,
    ];

    return self::AEAT_QR_BASE_URL . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
  }

  /**
   * Generates a QR code image as a base64-encoded PNG.
   *
   * Uses the chillerlan/php-qrcode library to generate the QR code.
   * Falls back to a simple SVG-based QR placeholder if the library
   * is not available.
   *
   * @param string $url
   *   The URL to encode in the QR code.
   * @param int $size
   *   Size in pixels (default: 300).
   *
   * @return string
   *   Base64-encoded PNG image data (without data URI prefix).
   */
  public function generateQrImage(string $url, int $size = self::DEFAULT_QR_SIZE): string {
    try {
      // Check if chillerlan/php-qrcode is available.
      if (class_exists('\\chillerlan\\QRCode\\QRCode')) {
        return $this->generateWithChillerlan($url, $size);
      }

      // Fallback: generate a minimal SVG-based QR representation.
      $this->logger->warning('chillerlan/php-qrcode library not found. Using SVG fallback for QR generation.');
      return $this->generateSvgFallback($url, $size);
    }
    catch (\Exception $e) {
      $this->logger->error('QR code generation failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return '';
    }
  }

  /**
   * Generates QR using chillerlan/php-qrcode library.
   *
   * @param string $url
   *   The URL to encode.
   * @param int $size
   *   Size in pixels.
   *
   * @return string
   *   Base64-encoded PNG image data.
   */
  protected function generateWithChillerlan(string $url, int $size): string {
    $options = new \chillerlan\QRCode\QROptions([
      'outputType' => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
      'scale' => max(1, (int) ($size / 33)),
      'imageBase64' => FALSE,
      'eccLevel' => \chillerlan\QRCode\Common\EccLevel::M,
    ]);

    $qrCode = new \chillerlan\QRCode\QRCode($options);
    $imageData = $qrCode->render($url);

    return base64_encode($imageData);
  }

  /**
   * Generates a minimal SVG QR fallback.
   *
   * This is NOT a real QR code. It generates a placeholder SVG
   * with the verification URL encoded as text. This fallback
   * should only be used during development when the QR library
   * is not yet installed.
   *
   * @param string $url
   *   The URL to encode.
   * @param int $size
   *   Size in pixels.
   *
   * @return string
   *   Base64-encoded SVG image data.
   */
  protected function generateSvgFallback(string $url, int $size): string {
    $escapedUrl = htmlspecialchars($url, ENT_XML1, 'UTF-8');
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
  <rect width="{$size}" height="{$size}" fill="white" stroke="#ccc" stroke-width="2"/>
  <text x="50%" y="40%" text-anchor="middle" font-size="12" fill="#666">QR PLACEHOLDER</text>
  <text x="50%" y="55%" text-anchor="middle" font-size="8" fill="#999">{$escapedUrl}</text>
  <text x="50%" y="70%" text-anchor="middle" font-size="10" fill="#333">VERI*FACTU</text>
</svg>
SVG;

    return base64_encode($svg);
  }

  /**
   * Formats a date string for AEAT QR URL.
   *
   * Converts from Drupal datetime format (Y-m-d) to AEAT format (DD-MM-YYYY).
   *
   * @param string $date
   *   Date in Y-m-d format.
   *
   * @return string
   *   Date in DD-MM-YYYY format.
   */
  protected function formatDateForAeat(string $date): string {
    $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if ($dateObj === FALSE) {
      // Try parsing as full datetime.
      $dateObj = new \DateTimeImmutable($date);
    }
    return $dateObj->format('d-m-Y');
  }

}
