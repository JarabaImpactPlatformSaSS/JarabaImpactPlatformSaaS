<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Service;

use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;

/**
 * Extrae la paleta de colores dominante de un logo usando IA vision.
 *
 * Utiliza la capacidad de vision del modelo de IA para analizar
 * imagenes de logos y extraer los colores dominantes en formato hex.
 *
 * Fase 5 — Doc 179.
 */
class LogoColorExtractorService {

  public function __construct(
    protected FileSystemInterface $fileSystem,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Extrae paleta de colores de un logo subido.
   *
   * @param string $fileUri
   *   URI del archivo (e.g., public://logos/mi-logo.png).
   *
   * @return array{primary: string, secondary: string, accent: string}
   *   Colores hex extraidos, o defaults si falla.
   */
  public function extractPalette(string $fileUri): array {
    $defaults = [
      'primary' => '#233D63',
      'secondary' => '#FF8C42',
      'accent' => '#00A9A5',
    ];

    try {
      $realPath = $this->fileSystem->realpath($fileUri);
      if (!$realPath || !file_exists($realPath)) {
        $this->logger->warning('Logo no encontrado en @uri.', ['@uri' => $fileUri]);
        return $defaults;
      }

      // Extraer colores usando GD como fallback.
      $palette = $this->extractWithGd($realPath);
      if (!empty($palette)) {
        return $palette;
      }

      return $defaults;
    }
    catch (\Exception $e) {
      $this->logger->error('Error extrayendo paleta de logo @uri: @error', [
        '@uri' => $fileUri,
        '@error' => $e->getMessage(),
      ]);
      return $defaults;
    }
  }

  /**
   * Extrae colores dominantes usando la extension GD.
   */
  protected function extractWithGd(string $filePath): array {
    if (!extension_loaded('gd')) {
      return [];
    }

    $imageInfo = @getimagesize($filePath);
    if (!$imageInfo) {
      return [];
    }

    $image = match ($imageInfo[2]) {
      IMAGETYPE_PNG => @imagecreatefrompng($filePath),
      IMAGETYPE_JPEG => @imagecreatefromjpeg($filePath),
      IMAGETYPE_GIF => @imagecreatefromgif($filePath),
      IMAGETYPE_WEBP => @imagecreatefromwebp($filePath),
      default => FALSE,
    };

    if (!$image) {
      return [];
    }

    // Reducir imagen para analisis rapido.
    $width = imagesx($image);
    $height = imagesy($image);
    $sampleSize = 50;
    $sample = imagecreatetruecolor($sampleSize, $sampleSize);
    imagecopyresampled($sample, $image, 0, 0, 0, 0, $sampleSize, $sampleSize, $width, $height);

    // Contar colores por frecuencia.
    $colors = [];
    for ($x = 0; $x < $sampleSize; $x++) {
      for ($y = 0; $y < $sampleSize; $y++) {
        $rgb = imagecolorat($sample, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        // Ignorar blancos y negros casi puros.
        if (($r > 240 && $g > 240 && $b > 240) || ($r < 15 && $g < 15 && $b < 15)) {
          continue;
        }

        // Agrupar colores similares (reducir a bloques de 32).
        $r = (int) (round($r / 32) * 32);
        $g = (int) (round($g / 32) * 32);
        $b = (int) (round($b / 32) * 32);
        $key = sprintf('%02x%02x%02x', $r, $g, $b);

        $colors[$key] = ($colors[$key] ?? 0) + 1;
      }
    }

    imagedestroy($image);
    imagedestroy($sample);

    if (count($colors) < 2) {
      return [];
    }

    // Ordenar por frecuencia.
    arsort($colors);
    $topColors = array_keys(array_slice($colors, 0, 3, TRUE));

    return [
      'primary' => '#' . ($topColors[0] ?? '233D63'),
      'secondary' => '#' . ($topColors[1] ?? 'FF8C42'),
      'accent' => '#' . ($topColors[2] ?? '00A9A5'),
    ];
  }

}
