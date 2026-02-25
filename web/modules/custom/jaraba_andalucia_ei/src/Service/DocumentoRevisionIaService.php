<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de revisión IA de documentos de expediente.
 *
 * Analiza documentos subidos (CV, plan de empleo, proyecto emprendedor)
 * usando el proveedor IA configurado. Genera feedback estructurado
 * con puntuación y sugerencias.
 *
 * AI-IDENTITY-001: Nunca revela el modelo subyacente.
 * Usa identidad Jaraba en todos los prompts.
 */
class DocumentoRevisionIaService {

  /**
   * Prompts específicos por categoría de documento.
   */
  const PROMPTS_POR_CATEGORIA = [
    'tarea_cv' => 'Evalúa este currículum vitae. Analiza: estructura, claridad, experiencia relevante, competencias digitales, y adecuación para inserción laboral en Andalucía. Puntúa de 0 a 100.',
    'tarea_carta' => 'Evalúa esta carta de motivación. Analiza: tono profesional, relevancia, personalización, y capacidad de persuasión. Puntúa de 0 a 100.',
    'tarea_plan_empleo' => 'Evalúa este plan de empleo/emprendimiento. Analiza: viabilidad, objetivos SMART, análisis de mercado, plan financiero, y diferenciación. Puntúa de 0 a 100.',
    'tarea_proyecto' => 'Evalúa este proyecto emprendedor. Analiza: innovación, viabilidad técnica, modelo de negocio, escalabilidad, e impacto social. Puntúa de 0 a 100.',
    'tarea_diagnostico' => 'Evalúa este diagnóstico inicial. Analiza: completitud, coherencia, identificación de fortalezas y áreas de mejora, y claridad de objetivos. Puntúa de 0 a 100.',
    'tarea_entregable' => 'Evalúa este entregable de formación. Analiza: comprensión del contenido, aplicación práctica, calidad de redacción, y originalidad. Puntúa de 0 a 100.',
  ];

  /**
   * Constructs a DocumentoRevisionIaService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param object|null $aiProvider
   *   The AI provider service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?object $aiProvider,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Solicita revisión IA de un documento.
   *
   * @param int $documentoId
   *   ID del documento.
   *
   * @return array
   *   Array con: success, score, feedback, puntos_fuertes, areas_mejora, sugerencias.
   */
  public function solicitarRevision(int $documentoId): array {
    $documento = $this->entityTypeManager->getStorage('expediente_documento')->load($documentoId);
    if (!$documento) {
      return ['success' => FALSE, 'error' => 'Document not found'];
    }

    $categoria = $documento->getCategoria();

    // Only review evaluable categories.
    if (!isset(self::PROMPTS_POR_CATEGORIA[$categoria])) {
      return ['success' => FALSE, 'error' => 'Category not evaluable by AI'];
    }

    // If AI provider not available, mark as pending for human review.
    if (!$this->aiProvider) {
      $documento->setEstadoRevision('pendiente');
      $documento->save();

      $this->logger->info('AI provider unavailable. Document @id marked for human review.', [
        '@id' => $documentoId,
      ]);

      return [
        'success' => FALSE,
        'error' => 'AI provider not available, marked for human review',
      ];
    }

    // Mark as in review.
    $documento->setEstadoRevision('en_revision');
    $documento->save();

    try {
      $prompt = $this->buildPrompt($categoria, $documento->getTitulo());
      $response = $this->aiProvider->complete($prompt);

      $parsed = $this->parseResponse($response);

      // Store feedback.
      $documento->set('revision_ia_score', $parsed['score']);
      $documento->set('revision_ia_feedback', json_encode($parsed, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

      // Auto-approve if score >= 70, otherwise require changes.
      if ($parsed['score'] >= 70) {
        $documento->setEstadoRevision('aprobado');
      }
      else {
        $documento->setEstadoRevision('requiere_cambios');
      }

      $documento->save();

      $this->logger->info('AI review completed for document @id: score @score', [
        '@id' => $documentoId,
        '@score' => $parsed['score'],
      ]);

      return ['success' => TRUE] + $parsed;
    }
    catch (\Exception $e) {
      $this->logger->error('AI review error for document @id: @message', [
        '@id' => $documentoId,
        '@message' => $e->getMessage(),
      ]);

      // Revert to pending on failure.
      $documento->setEstadoRevision('pendiente');
      $documento->save();

      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Builds the AI prompt for a document category.
   *
   * @param string $categoria
   *   Document category.
   * @param string $titulo
   *   Document title.
   *
   * @return string
   *   The prompt.
   */
  protected function buildPrompt(string $categoria, string $titulo): string {
    $basePrompt = self::PROMPTS_POR_CATEGORIA[$categoria] ?? '';

    // AI-IDENTITY-001: Use Jaraba identity.
    return sprintf(
      "Eres el asistente de revisión documental del programa Andalucía +ei de la Fundación Jaraba. " .
      "Documento: \"%s\". %s " .
      "Responde en formato JSON con las claves: score (número 0-100), " .
      "puntos_fuertes (array de strings), areas_mejora (array de strings), " .
      "sugerencias (array de strings). Solo JSON, sin texto adicional.",
      $titulo,
      $basePrompt,
    );
  }

  /**
   * Parses the AI response into structured feedback.
   *
   * @param string $response
   *   Raw AI response.
   *
   * @return array
   *   Parsed feedback with score, puntos_fuertes, areas_mejora, sugerencias.
   */
  protected function parseResponse(string $response): array {
    $default = [
      'score' => 50.0,
      'puntos_fuertes' => [],
      'areas_mejora' => [],
      'sugerencias' => [],
    ];

    // Try to parse JSON response.
    $cleaned = trim($response);
    // Strip markdown code fences if present.
    if (str_starts_with($cleaned, '```')) {
      $cleaned = preg_replace('/^```(?:json)?\s*/', '', $cleaned);
      $cleaned = preg_replace('/\s*```$/', '', $cleaned);
    }

    $decoded = json_decode($cleaned, TRUE);
    if (!is_array($decoded)) {
      return $default;
    }

    return [
      'score' => isset($decoded['score']) ? (float) $decoded['score'] : $default['score'],
      'puntos_fuertes' => $decoded['puntos_fuertes'] ?? [],
      'areas_mejora' => $decoded['areas_mejora'] ?? [],
      'sugerencias' => $decoded['sugerencias'] ?? [],
    ];
  }

}
