<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de formateo de citas legales.
 *
 * Genera citas formateadas (texto y HTML) a partir de fragmentos
 * de normas legales, incluyendo referencia al articulo, norma
 * y enlace al BOE.
 *
 * FORMATOS DE CITA:
 * - Texto: "Art. 12, Ley 35/2006 (BOE-A-2006-20764)"
 * - HTML:  "<a href='https://www.boe.es/...'>Art. 12, Ley 35/2006</a>"
 */
class LegalCitationService {

  /**
   * URL base del BOE para enlaces a documentos.
   */
  protected const BOE_DOCUMENT_URL = 'https://www.boe.es/buscar/doc.php?id=';

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Formatea citas a partir de una lista de IDs de chunk.
   *
   * Carga las entidades LegalChunk y sus normas padre para generar
   * un array de citas estructuradas.
   *
   * @param array $chunkIds
   *   Array de IDs de entidades LegalChunk.
   *
   * @return array
   *   Array de citas. Cada cita contiene:
   *   - norm_id: (int) ID de la entidad LegalNorm.
   *   - norm_title: (string) Titulo de la norma.
   *   - boe_id: (string) Identificador BOE.
   *   - boe_url: (string) URL al documento en el BOE.
   *   - article_number: (string|null) Numero de articulo.
   *   - section_title: (string) Titulo de la seccion.
   *   - publication_date: (string|null) Fecha de publicacion.
   *   - text: (string) Cita formateada en texto plano.
   *   - html: (string) Cita formateada en HTML con enlace.
   */
  public function formatCitations(array $chunkIds): array {
    if (empty($chunkIds)) {
      return [];
    }

    $citations = [];
    $processedNorms = [];

    try {
      $chunkStorage = $this->entityTypeManager->getStorage('legal_chunk');
      $normStorage = $this->entityTypeManager->getStorage('legal_norm');
      $chunks = $chunkStorage->loadMultiple($chunkIds);

      foreach ($chunks as $chunk) {
        $normId = (int) $chunk->get('norm_id')->value;
        $articleNumber = $chunk->get('article_number')->value;

        // Clave unica por norma+articulo para evitar citas duplicadas.
        $citationKey = $normId . '_' . ($articleNumber ?? 'general');
        if (isset($processedNorms[$citationKey])) {
          continue;
        }
        $processedNorms[$citationKey] = TRUE;

        // Cargar la norma padre.
        $norm = $normStorage->load($normId);
        if (!$norm) {
          $this->logger->warning('Norma @id no encontrada para chunk @chunk_id.', [
            '@id' => $normId,
            '@chunk_id' => $chunk->id(),
          ]);
          continue;
        }

        $boeId = $norm->get('boe_id')->value ?? '';
        $normTitle = $norm->label() ?? '';
        $publicationDate = $norm->get('publication_date')->value ?? NULL;
        $sectionTitle = $chunk->get('section_title')->value ?? '';
        $boeUrl = self::BOE_DOCUMENT_URL . urlencode($boeId);

        $citation = [
          'norm_id' => $normId,
          'norm_title' => $normTitle,
          'boe_id' => $boeId,
          'boe_url' => $boeUrl,
          'article_number' => $articleNumber,
          'section_title' => $sectionTitle,
          'publication_date' => $publicationDate,
          'text' => $this->formatCitationText([
            'norm_title' => $normTitle,
            'article_number' => $articleNumber,
            'boe_id' => $boeId,
          ]),
          'html' => $this->formatCitationHtml([
            'norm_title' => $normTitle,
            'article_number' => $articleNumber,
            'boe_id' => $boeId,
            'boe_url' => $boeUrl,
          ]),
        ];

        $citations[] = $citation;
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error formateando citas: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $citations;
  }

  /**
   * Formatea una cita como texto plano.
   *
   * @param array $citation
   *   Datos de la cita. Claves:
   *   - norm_title: (string) Titulo de la norma.
   *   - article_number: (string|null) Numero de articulo.
   *   - boe_id: (string) Identificador BOE.
   *
   * @return string
   *   Cita formateada. Ejemplo: "Art. 12, Ley 35/2006 (BOE-A-2006-20764)".
   */
  public function formatCitationText(array $citation): string {
    $parts = [];

    if (!empty($citation['article_number'])) {
      $parts[] = 'Art. ' . $citation['article_number'];
    }

    if (!empty($citation['norm_title'])) {
      $parts[] = $citation['norm_title'];
    }

    $text = implode(', ', $parts);

    if (!empty($citation['boe_id'])) {
      $text .= ' (' . $citation['boe_id'] . ')';
    }

    return $text;
  }

  /**
   * Formatea una cita como HTML con enlace al BOE.
   *
   * @param array $citation
   *   Datos de la cita. Claves:
   *   - norm_title: (string) Titulo de la norma.
   *   - article_number: (string|null) Numero de articulo.
   *   - boe_id: (string) Identificador BOE.
   *   - boe_url: (string) URL al documento en el BOE.
   *
   * @return string
   *   Cita formateada como HTML con enlace.
   */
  public function formatCitationHtml(array $citation): string {
    $parts = [];

    if (!empty($citation['article_number'])) {
      $parts[] = 'Art. ' . htmlspecialchars($citation['article_number'], ENT_QUOTES, 'UTF-8');
    }

    if (!empty($citation['norm_title'])) {
      $parts[] = htmlspecialchars($citation['norm_title'], ENT_QUOTES, 'UTF-8');
    }

    $linkText = implode(', ', $parts);
    $boeUrl = $citation['boe_url'] ?? (self::BOE_DOCUMENT_URL . urlencode($citation['boe_id'] ?? ''));

    return '<a href="' . htmlspecialchars($boeUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">'
      . $linkText
      . '</a>';
  }

}
