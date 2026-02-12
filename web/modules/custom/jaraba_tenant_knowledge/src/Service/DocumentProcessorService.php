<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\jaraba_rag\Client\QdrantDirectClient;
use Drupal\ai\AiProviderPluginManager;
use Drupal\jaraba_tenant_knowledge\Entity\TenantDocument;
use Psr\Log\LoggerInterface;

/**
 * PROCESADOR DE DOCUMENTOS
 *
 * PROPÓSITO:
 * Procesa documentos subidos: extrae texto, divide en chunks e indexa en Qdrant.
 *
 * FLUJO:
 * 1. Detectar tipo de archivo (PDF, DOC, TXT)
 * 2. Extraer texto usando biblioteca apropiada
 * 3. Dividir texto en chunks de ~500 tokens
 * 4. Generar embeddings para cada chunk
 * 5. Indexar chunks en Qdrant con metadata del documento
 *
 * CHUNKING:
 * - Tamaño objetivo: 500 tokens (~2000 caracteres)
 * - Overlap: 100 tokens para mantener contexto
 * - Respeta límites de párrafos cuando es posible
 */
class DocumentProcessorService
{

    /**
     * Colección de Qdrant para documentos.
     */
    protected const COLLECTION_NAME = 'jaraba_knowledge';

    /**
     * Modelo de embedding.
     */
    protected const EMBEDDING_MODEL = 'text-embedding-3-small';

    /**
     * Dimensiones del vector.
     */
    protected const VECTOR_DIMENSIONS = 1536;

    /**
     * Tamaño de chunk en caracteres.
     */
    protected const CHUNK_SIZE = 2000;

    /**
     * Overlap entre chunks en caracteres.
     */
    protected const CHUNK_OVERLAP = 200;

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected QdrantDirectClient $qdrantClient,
        protected AiProviderPluginManager $aiProvider,
        protected FileSystemInterface $fileSystem,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Procesa un documento completo.
     *
     * @param \Drupal\jaraba_tenant_knowledge\Entity\TenantDocument $document
     *   El documento a procesar.
     *
     * @return bool
     *   TRUE si se procesó correctamente.
     */
    public function processDocument(TenantDocument $document): bool
    {
        try {
            $document->markProcessingStarted();

            // 1. Obtener archivo.
            $file = $document->getFile();
            if (!$file) {
                $document->markProcessingFailed('Archivo no encontrado.');
                return FALSE;
            }

            // 2. Extraer texto.
            $uri = $file->getFileUri();
            $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
            $text = $this->extractText($uri, strtolower($extension));

            if (empty($text)) {
                $document->markProcessingFailed('No se pudo extraer texto del documento.');
                return FALSE;
            }

            // Guardar texto extraído.
            $document->setExtractedText($text);

            // 3. Dividir en chunks.
            $chunks = $this->chunkText($text);

            if (empty($chunks)) {
                $document->markProcessingFailed('No se generaron chunks del texto.');
                return FALSE;
            }

            // 4. Eliminar chunks anteriores del documento.
            $this->deleteDocumentChunks($document);

            // 5. Indexar cada chunk.
            $indexed = 0;
            foreach ($chunks as $index => $chunk) {
                if ($this->indexChunk($document, $chunk, $index)) {
                    $indexed++;
                }
            }

            $document->markProcessingCompleted($indexed);

            $this->logger->info('Documento @id procesado: @chunks chunks indexados.', [
                '@id' => $document->id(),
                '@chunks' => $indexed,
            ]);

            return TRUE;
        } catch (\Exception $e) {
            $document->markProcessingFailed($e->getMessage());
            $this->logger->error('Error procesando documento @id: @error', [
                '@id' => $document->id(),
                '@error' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Extrae texto de un archivo.
     *
     * @param string $uri
     *   URI del archivo.
     * @param string $extension
     *   Extensión del archivo.
     *
     * @return string
     *   Texto extraído.
     */
    protected function extractText(string $uri, string $extension): string
    {
        $realPath = $this->fileSystem->realpath($uri);

        if (!$realPath || !file_exists($realPath)) {
            throw new \RuntimeException("Archivo no accesible: $uri");
        }

        return match ($extension) {
            'txt', 'md' => $this->extractFromPlainText($realPath),
            'pdf' => $this->extractFromPdf($realPath),
            'doc', 'docx' => $this->extractFromWord($realPath),
            default => throw new \RuntimeException("Formato no soportado: $extension"),
        };
    }

    /**
     * Extrae texto de archivo plano.
     */
    protected function extractFromPlainText(string $path): string
    {
        $content = file_get_contents($path);
        return $content !== FALSE ? $content : '';
    }

    /**
     * Extrae texto de PDF.
     *
     * Usa pdftotext si está disponible, o alternativa PHP.
     */
    protected function extractFromPdf(string $path): string
    {
        // Intentar con pdftotext (poppler-utils).
        if ($this->commandExists('pdftotext')) {
            $output = [];
            $returnVar = 0;
            exec("pdftotext -layout " . escapeshellarg($path) . " -", $output, $returnVar);

            if ($returnVar === 0 && !empty($output)) {
                return implode("\n", $output);
            }
        }

        // Fallback: usar biblioteca PHP (si existe).
        if (class_exists('\Smalot\PdfParser\Parser')) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);
            return $pdf->getText();
        }

        // Último recurso: intentar leer como texto.
        $content = file_get_contents($path);
        // Extraer texto básico de PDF (muy limitado).
        if (preg_match_all('/\((.*?)\)/', $content, $matches)) {
            return implode(' ', $matches[1]);
        }

        throw new \RuntimeException('No se encontró extractor de PDF disponible.');
    }

    /**
     * Extrae texto de Word.
     */
    protected function extractFromWord(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension === 'docx') {
            return $this->extractFromDocx($path);
        }

        // Para .doc antiguo, intentar con antiword.
        if ($this->commandExists('antiword')) {
            $output = [];
            exec("antiword " . escapeshellarg($path), $output);
            return implode("\n", $output);
        }

        throw new \RuntimeException('No se encontró extractor de DOC disponible.');
    }

    /**
     * Extrae texto de DOCX.
     */
    protected function extractFromDocx(string $path): string
    {
        $zip = new \ZipArchive();

        if ($zip->open($path) !== TRUE) {
            throw new \RuntimeException('No se pudo abrir archivo DOCX.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xml) {
            throw new \RuntimeException('Contenido no encontrado en DOCX.');
        }

        // Extraer texto del XML.
        $content = strip_tags($xml);
        // Limpiar espacios múltiples.
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }

    /**
     * Divide el texto en chunks.
     *
     * @param string $text
     *   Texto completo.
     *
     * @return array
     *   Array de chunks.
     */
    protected function chunkText(string $text): array
    {
        $chunks = [];
        $text = trim($text);

        if (strlen($text) <= self::CHUNK_SIZE) {
            return [$text];
        }

        // Dividir por párrafos primero.
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $currentChunk = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if (empty($paragraph)) {
                continue;
            }

            // Si el párrafo cabe en el chunk actual.
            if (strlen($currentChunk) + strlen($paragraph) + 2 <= self::CHUNK_SIZE) {
                $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
            } else {
                // Guardar chunk actual si tiene contenido.
                if (!empty($currentChunk)) {
                    $chunks[] = $currentChunk;
                }

                // Si el párrafo es muy largo, dividirlo.
                if (strlen($paragraph) > self::CHUNK_SIZE) {
                    $subChunks = $this->splitLongText($paragraph);
                    foreach ($subChunks as $subChunk) {
                        $chunks[] = $subChunk;
                    }
                    $currentChunk = '';
                } else {
                    $currentChunk = $paragraph;
                }
            }
        }

        // Añadir último chunk.
        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    /**
     * Divide texto largo en chunks con overlap.
     */
    protected function splitLongText(string $text): array
    {
        $chunks = [];
        $length = strlen($text);
        $position = 0;

        while ($position < $length) {
            $chunk = substr($text, $position, self::CHUNK_SIZE);

            // Intentar cortar en un espacio.
            if ($position + self::CHUNK_SIZE < $length) {
                $lastSpace = strrpos($chunk, ' ');
                if ($lastSpace !== FALSE && $lastSpace > self::CHUNK_SIZE / 2) {
                    $chunk = substr($text, $position, $lastSpace);
                }
            }

            $chunks[] = trim($chunk);
            $position += strlen($chunk) - self::CHUNK_OVERLAP;

            // Si quedó poco, terminar.
            if ($length - $position < self::CHUNK_OVERLAP * 2) {
                $remaining = substr($text, $position);
                if (!empty(trim($remaining))) {
                    $chunks[] = trim($remaining);
                }
                break;
            }
        }

        return $chunks;
    }

    /**
     * Indexa un chunk en Qdrant.
     */
    protected function indexChunk(TenantDocument $document, string $chunk, int $index): bool
    {
        try {
            // Generar embedding.
            $embedText = $this->buildChunkContext($document, $chunk, $index);
            $vector = $this->generateEmbedding($embedText);

            if (empty($vector)) {
                $this->logger->warning('Embedding vacío para chunk @index del documento @id.', [
                    '@index' => $index,
                    '@id' => $document->id(),
                ]);
                return FALSE;
            }

            // Generar point ID único para este chunk.
            $pointId = $this->qdrantClient->generatePointId(
                'doc_' . $document->id() . '_chunk_' . $index
            );

            $payload = [
                'type' => 'document_chunk',
                'entity_type' => 'tenant_document',
                'entity_id' => (int) $document->id(),
                'tenant_id' => $document->getTenantId(),
                'category' => $document->getCategory(),
                'document_title' => $document->getTitle(),
                'chunk_index' => $index,
                'chunk_content' => substr($chunk, 0, 500),
            ];

            $point = [
                'id' => $pointId,
                'vector' => $vector,
                'payload' => $payload,
            ];

            $this->qdrantClient->upsertPoints([$point], self::COLLECTION_NAME);

            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error indexando chunk @index: @error', [
                '@index' => $index,
                '@error' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Construye contexto para el embedding del chunk.
     */
    protected function buildChunkContext(TenantDocument $document, string $chunk, int $index): string
    {
        $context = [];
        $context[] = "Documento: " . $document->getTitle();
        $context[] = "Categoría: " . $document->getCategoryLabel();

        $description = $document->getDescription();
        if (!empty($description)) {
            $context[] = "Descripción: " . $description;
        }

        $context[] = "--- Contenido (fragmento " . ($index + 1) . ") ---";
        $context[] = $chunk;

        return implode("\n", $context);
    }

    /**
     * Elimina todos los chunks de un documento de Qdrant.
     */
    protected function deleteDocumentChunks(TenantDocument $document): void
    {
        try {
            // Buscar todos los chunks de este documento.
            $filter = [
                'must' => [
                    [
                        'key' => 'entity_type',
                        'match' => ['value' => 'tenant_document'],
                    ],
                    [
                        'key' => 'entity_id',
                        'match' => ['value' => (int) $document->id()],
                    ],
                ],
            ];

            // Eliminar por filtro.
            $this->qdrantClient->deletePointsByFilter($filter, self::COLLECTION_NAME);

            $this->logger->info('Chunks anteriores del documento @id eliminados.', [
                '@id' => $document->id(),
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('Error eliminando chunks del documento @id: @error', [
                '@id' => $document->id(),
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Genera embedding para un texto.
     */
    protected function generateEmbedding(string $text): array
    {
        try {
            $provider = $this->aiProvider->createInstance('openai');
            $response = $provider->embeddings($text, self::EMBEDDING_MODEL);

            if (!empty($response) && isset($response['embedding'])) {
                return $response['embedding'];
            }

            if (method_exists($provider, 'vectorize')) {
                $vector = $provider->vectorize($text);
                if (!empty($vector)) {
                    return $vector;
                }
            }

            return [];
        } catch (\Exception $e) {
            $this->logger->error('Error generando embedding: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Verifica si un comando existe en el sistema.
     */
    protected function commandExists(string $command): bool
    {
        $whereIsCommand = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $result = shell_exec("$whereIsCommand $command 2>&1");
        return !empty($result) && strpos($result, 'not found') === FALSE;
    }

}
