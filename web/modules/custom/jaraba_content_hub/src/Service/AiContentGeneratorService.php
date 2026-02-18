<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Servicio de Generación de Contenido Asistida por IA.
 *
 * Implementa estrategias de "Answer Capsules" y optimización GEO (Generative Engine Optimization).
 *
 * ESTRUCTURA:
 * - Generación de artículos completos a partir de ideas.
 * - Generación de metadatos SEO (Title, Description, Schema.org).
 * - Generación de "Answer Capsules" para fragmentos destacados en buscadores de IA.
 *
 * F6 — Doc 128 / Plan Maestro v3.
 */
class AiContentGeneratorService {

  use StringTranslationTrait;

  public function __construct(
    protected AiProviderPluginManager $aiProvider,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera un borrador completo de artículo.
   */
  public function generateArticle(array $params): array {
    $topic = $params['topic'] ?? '';
    $tone = $params['tone'] ?? 'professional';
    $vertical = $params['vertical'] ?? 'general';

    $prompt = $this->buildArticlePrompt($topic, $tone, $vertical);
    
    try {
      // Usamos el proveedor configurado por defecto (Gemini/Claude)
      $provider_id = $this->configFactory->get('ai.settings')->get('default_provider') ?: 'google_gemini';
      $llm = $this->aiProvider->createInstance($provider_id);
      
      // Selección de modelo optimizado para escritura larga
      $model = ($provider_id === 'google_gemini') ? 'gemini-2.0-flash' : 'claude-3-5-sonnet';

      $response = $llm->chat([
        ['role' => 'system', 'content' => $this->getSystemInstructions($vertical)],
        ['role' => 'user', 'content' => $prompt],
      ], $model);

      return $this->parseAiResponse($response->getNormalizedResponse());
    }
    catch (\Exception $e) {
      $this->logger->error('Error generando contenido con IA: @error', ['@error' => $e->getMessage()]);
      throw $e;
    }
  }

  /**
   * Construye las instrucciones de sistema para el vertical.
   */
  protected function getSystemInstructions(string $vertical): string {
    $base = "Eres un redactor experto en marketing de contenidos para el vertical {$vertical}. 
    Tu objetivo es crear contenido que posicione en buscadores tradicionales (Google) 
    y en buscadores de IA (Perplexity, ChatGPT). 
    Debes seguir la estrategia de 'Answer Capsules': los primeros 150 caracteres de cada sección 
    deben responder directamente a la intención de búsqueda de forma concisa.";
    
    return $base;
  }

  /**
   * Construye el prompt de usuario.
   */
  protected function buildArticlePrompt(string $topic, string $tone, string $vertical): string {
    return "Escribe un artículo detallado sobre: '{$topic}'. 
    Tono: {$tone}.
    Estructura:
    1. Título SEO (H1).
    2. Meta-descripción (máx 160 caracteres).
    3. Introducción con Answer Capsule.
    4. 3-4 Secciones con subtítulos (H2).
    5. Conclusión con CTA.
    Devuelve la respuesta en formato JSON con las claves: title, meta_description, answer_capsule, body_html.";
  }

  /**
   * Parsea la respuesta de la IA.
   */
  protected function parseAiResponse(string $rawResponse): array {
    // Limpieza de posibles bloques de código markdown
    $json = preg_replace('/^```json\s*|\s*```$/', '', trim($rawResponse));
    $data = json_decode($json, TRUE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->logger->warning('Error parseando JSON de IA. Usando respuesta cruda.');
      return [
        'title' => 'Borrador generado',
        'body_html' => $rawResponse,
        'meta_description' => '',
        'answer_capsule' => '',
      ];
    }
    
    return $data;
  }

}
